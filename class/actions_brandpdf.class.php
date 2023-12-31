<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_brandpdf.class.php
 * \ingroup brandpdf
 * \brief   BrandPDF hook overload.
 */

/**
 * Class ActionsBrandPdf
 */
class ActionsBrandPdf
{
    /**
     * @var DoliDB Database handler.
     */
    public DoliDB $db;

    /**
     * @var string Error code (or message)
     */
    public string $error = '';

    /**
     * @var array Errors
     */
    public array $errors = [];

    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public array $results = [];

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public string $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

	/**
	 *  Overloading the showdocuments function : replacing the parent's function with the one below
	 *
	 * @param  array  $parameters Hook metadatas (context, etc...)
	 * @param  Object $object     Hook object data
	 * @return int                0 < on error, 0 on success, 1 to replace standard code
	 */
	public function showDocuments(array $parameters): int
	{
		global $conf, $db, $langs;

        $langs->load('brandpdf@brandpdf');

		if ($parameters['currentcontext'] == 'invoicecard' || $parameters['currentcontext'] == 'propalcard') {
			$form            = new Form($db);
			$uploadDir       = $conf->ecm->dir_output . '/brandpdf';
			$logoArray       = [];
			$templateArray   = [];

			// Retrieve custom logos
			$logoFilesArray = dol_dir_list($uploadDir . '/logos', 'files', 0, '.(jpg|jpeg|png)$');
			if (is_array($logoFilesArray) && !empty($logoFilesArray)) {
				foreach ($logoFilesArray as $logoFile) {
					$logoArray[$logoFile['name']] .= $logoFile['name'];
				}
			}

			// Retrieve custom templates
			$templateFilesArray = dol_dir_list($uploadDir . '/template_pdf', 'files', 0, '.pdf$');
			if (is_array($templateFilesArray) && !empty($templateFilesArray)) {
				foreach ($templateFilesArray as $templateFile) {
					$templateArray[$templateFile['name']] .= $templateFile['name'];
				}
			}

			print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" name="generateSpecial">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="builddoc">';

			print load_fiche_titre($langs->trans('DocumentDetails'), '', '');

            print '<table class="noborder centpercent editmode">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Name") . '</td>';
            print '<td>' . $langs->trans("SelectTemplate") . '</td>';
            print '</tr>';

            print '<tr class="oddeven"><td><label for="DefaultLogo">' . $langs->trans("Logo") . '</label></td><td>';
            print $form::selectArray('document_logo', $logoArray, !empty($conf->global->MAIN_ADD_PDF_BACKGROUND) ? $conf->global->BRANDPDF_DEFAULT_LOGO : '', $langs->trans('SelectACustomLogo'), 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
            print '</td></tr>';

            print '<tr class="oddeven"><td><label for="DefaultTemplate">' . $langs->trans("Template") . $form->textwithpicto('', $langs->trans('InfoHowToDefaultTemplate')) .' </label></td><td>';
            print $form::selectArray('document_template', $templateArray, !empty($conf->global->MAIN_ADD_PDF_BACKGROUND) ? $conf->global->MAIN_ADD_PDF_BACKGROUND : '', $langs->trans('SelectACustomTemplate'), 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
            print '</td></tr>';

            print '</table>';
		}

		return 0;
	}

	/**
	 *  Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param  array  $parameters Hook metadatas (context, etc...)
	 * @param  Object $object     Hook object data (id, ref, etc...)
	 * @param  string $action     Hook current actions (add, update etc...)
	 * @return int                0 < on error, 0 on success, 1 to replace standard code
	 */
	public function beforePDFCreation(array $parameters, ?object $object, string $action): int
	{
		global $conf, $db, $mysoc;

		if ($parameters['currentcontext'] == 'invoicecard' || $parameters['currentcontext'] == 'propalcard') {
			if ($action == 'builddoc') {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

				$defaultTemplate = !empty($conf->global->MAIN_ADD_PDF_BACKGROUND) ? $conf->global->MAIN_ADD_PDF_BACKGROUND : '';
                $defaultLogo     = !empty($conf->global->BRANDPDF_DEFAULT_LOGO) ? $conf->global->BRANDPDF_DEFAULT_LOGO : '';
                $templatePdf     = GETPOST('document_template', 'alpha');
				$logo            = GETPOST('document_logo', 'alpha');

                if (intval($templatePdf) >= 0 || !empty($defaultTemplate)) {
					if (!empty($defaultTemplate) && intval($templatePdf) < 0) {
						dolibarr_set_const($db, 'BRAND_PDF_PREVIOUS_BACKGROUND', $defaultTemplate);
                        dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', 'template_pdf/' . $defaultTemplate);
                    } else if (!empty($defaultTemplate) && intval($templatePdf) >= 0) {
                        dolibarr_set_const($db, 'BRAND_PDF_PREVIOUS_BACKGROUND', $defaultTemplate);
                        dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', 'template_pdf/' . $templatePdf);
                    } else {
                        dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', 'template_pdf/' . $templatePdf);
                    }

					if (intval($logo) < 0) {
						if (dol_strlen($mysoc->logo) > 0 && file_exists(DOL_DATA_ROOT . '/mycompany/logos/' . $mysoc->logo)) {
							copy(DOL_DATA_ROOT . '/mycompany/logos/' . $mysoc->logo, DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo);
						}
					}
                }

                if (intval($logo) >= 0 || !empty($defaultTemplate)) {
                    if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
                        dolibarr_set_const($db, 'MAIN_PDF_USE_LARGE_LOGO', 1);
                    } else {
                        dolibarr_set_const($db, 'BRAND_PDF_USE_LARGE_LOGO', 1);
                    }

                    if ((intval($templatePdf) < 0) && !empty($defaultTemplate) && file_exists(DOL_DATA_ROOT . '/mycompany/' . $defaultTemplate)) {
                        copy(DOL_DATA_ROOT . '/mycompany/' . $defaultTemplate, DOL_DATA_ROOT . '/ecm/brandpdf/' . $defaultTemplate);
                    }
                }

                if (intval($logo) >= 0) {
                    $mysoc->logo = $logo;
                } else if (!empty($conf->global->BRANDPDF_DEFAULT_LOGO)) {
                    $mysoc->logo = $conf->global->BRANDPDF_DEFAULT_LOGO;
                }

                if (intval($templatePdf) >= 0 || intval($logo) >= 0 || !empty($defaultTemplate)) {
                    $conf->mycompany->dir_output = DOL_DATA_ROOT . '/ecm/brandpdf';
                    if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                        $conf->mycompany->multidir_output[$object->entity] = DOL_DATA_ROOT . '/ecm/brandpdf';
                    }
                }
            }
		}

		return 0;
	}

    /**
     *  Overloading the afterPDFCreation function : replacing the parent's function with the one below
     *
     * @param  array  $parameters Hook metadatas (context, etc...)
     * @param  Object $object     Hook object data (id, ref, etc...)
     * @return int                0 < on error, 0 on success, 1 to replace standard code
     */
    public function afterPDFCreation(array $parameters, ?object $object): int
    {
        global $conf, $db, $mysoc;

        if ($parameters['currentcontext'] == 'invoicecard' || $parameters['currentcontext'] == 'propalcard') {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

			$defaultTemplate = !empty($conf->global->MAIN_ADD_PDF_BACKGROUND) ? $conf->global->MAIN_ADD_PDF_BACKGROUND : '';
			$templatePdf     = GETPOST('document_template', 'alpha');
			$logo            = GETPOST('document_logo', 'alpha');

			if (intval($templatePdf) >= 0 || !empty($defaultTemplate)) {
				dolibarr_del_const($db, 'MAIN_ADD_PDF_BACKGROUND');
				if (!empty($conf->global->BRAND_PDF_PREVIOUS_BACKGROUND)) {
					dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', $conf->global->BRAND_PDF_PREVIOUS_BACKGROUND);
					dolibarr_del_const($db, 'BRAND_PDF_PREVIOUS_BACKGROUND');
				}
			}

			if (intval($logo) >= 0) {
				if (empty($conf->global->BRAND_PDF_USE_LARGE_LOGO)) {
					dolibarr_del_const($db, 'MAIN_PDF_USE_LARGE_LOGO');
				} else {
					dolibarr_del_const($db, 'BRAND_PDF_USE_LARGE_LOGO');
				}
				$mysoc->logo = str_replace($mysoc->logo_small, '', '_small');
			}

			if (intval($templatePdf) >= 0 || intval($logo) >= 0) {
				$conf->mycompany->dir_output = DOL_DATA_ROOT . '/mycompany';
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$conf->mycompany->multidir_output[$object->entity] = DOL_DATA_ROOT . '/mycompany';
				}
			}

			if (intval($templatePdf) >= 0 && intval($logo) < 0) {
				if (dol_strlen($mysoc->logo) > 0 && file_exists(DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo)) {
					unlink(DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo);
				}
			} else if (intval($templatePdf) < 0 && intval($logo) >= 0 && !empty($defaultTemplate)) {
				if (file_exists(DOL_DATA_ROOT . '/mycompany/logos/' . $defaultTemplate)) {
					unlink(DOL_DATA_ROOT . '/ecm/brandpdf/' . $defaultTemplate);
				}
			}
		}

        return 0;
    }

}
