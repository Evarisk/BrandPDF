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
	public function showDocuments(array $parameters, $object): int
	{
		global $conf, $db, $langs;

		if ($parameters['currentcontext'] == 'invoicecard') {
			$form            = new Form($db);
			$uploadDir       = $conf->ecm->dir_output . '/brandpdf';
			$logoArray       = [];
			$templateArray   = [];

			// Retrieve custom logos
			$logoFilesArray = dol_dir_list($uploadDir . '/logos', 'files', 0, '.(jpg|jpeg|png|svg)$');
			foreach($logoFilesArray as $logoFile) {
				$logoArray[$logoFile['name']] .= $logoFile['name'];
			}

			// Retrieve custom templates
			$templateFilesArray = dol_dir_list($uploadDir . '/template_pdf', 'files', 0, '.pdf$');
			foreach($templateFilesArray as $templateFile) {
				$templateArray[$templateFile['name']] .= $templateFile['name'];
			}

			print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" name="generateSpecial">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="builddoc">';

			print load_fiche_titre($langs->trans('BrandPDF'), '', '');

			print $langs->trans('Logo') . ' : ' . $form::selectArray('document_logo', $logoArray, '', 1, 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
			print '<br></td>';
			print $langs->trans('Template') . ' : ' . $form::selectArray('document_template', $templateArray, '', 1, 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
			print '<input class="button buttongen reposition nomargintop nomarginbottom" id="generatebutton" name="generatebutton" type="submit" value="'. $langs->trans('SpecialGenerate') .'"';
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
	public function doActions(array $parameters, Object $object, string $action): int
	{
		global $conf, $db, $langs, $mysoc;

		if ($parameters['currentcontext'] == 'invoicecard') {
			if ($action == 'builddoc' && GETPOST('generatebutton')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

                $template_pdf = GETPOST('document_template', 'alpha');
                if (intval($template_pdf) > 0) {
                    dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', 'template_pdf/' . $template_pdf);
                } else {
                    dolibarr_del_const($db, 'MAIN_ADD_PDF_BACKGROUND');
                }

                $logo = GETPOST('document_logo', 'alpha');
                if (intval($logo) >= 0) {
                    if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
                        dolibarr_set_const($db, 'MAIN_PDF_USE_LARGE_LOGO', 1);
                    } else {
                        dolibarr_set_const($db, 'BRAND_PDF_USE_LARGE_LOGO', 1);
                    }
                    $mysoc->logo = $logo;
                }

                if (intval($template_pdf) >= 0 || intval($logo) >= 0) {
                    $conf->mycompany->dir_output = DOL_DATA_ROOT . '/ecm/brandpdf';
                    if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                        $conf->mycompany->multidir_output[$object->entity] = DOL_DATA_ROOT . '/ecm/brandpdf';
                    }
                }

                if (intval($template_pdf) >= 0 && intval($logo) < 0) {
                    if (dol_strlen($mysoc->logo) > 0 && file_exists(DOL_DATA_ROOT . '/mycompany/logos/' . $mysoc->logo)) {
                        copy(DOL_DATA_ROOT . '/mycompany/logos/' . $mysoc->logo, DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo);
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
    public function afterPDFCreation(array $parameters, Object $object): int
    {
        global $conf, $db, $mysoc;

        if ($parameters['currentcontext'] == 'invoicecard') {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

            $template_pdf = GETPOST('document_template', 'alpha');
            if (intval($template_pdf) > 0) {
                dolibarr_del_const($db, 'MAIN_ADD_PDF_BACKGROUND');
            }

            $logo = GETPOST('document_logo', 'alpha');
            if (intval($logo) > 0) {
                if (empty($conf->global->BRAND_PDF_USE_LARGE_LOGO)) {
                    dolibarr_del_const($db, 'MAIN_PDF_USE_LARGE_LOGO');
                } else {
                    dolibarr_del_const($db, 'BRAND_PDF_USE_LARGE_LOGO');
                }
                $mysoc->logo = str_replace($mysoc->logo_small, '', '_small');
            }

            if (intval($template_pdf) >= 0 || intval($logo) >= 0) {
                $conf->mycompany->dir_output = DOL_DATA_ROOT . '/mycompany';
                if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                    $conf->mycompany->multidir_output[$object->entity] = DOL_DATA_ROOT . '/mycompany';
                }
            }

            if (intval($template_pdf) >= 0 && intval($logo) < 0) {
                if (dol_strlen($mysoc->logo) > 0 && file_exists(DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo)) {
                    unlink(DOL_DATA_ROOT . '/ecm/brandpdf/logos/' . $mysoc->logo);
                }
            }
        }

        return 0;
    }

}
