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
			$uploadDir       = $conf->ecm->dir_output . '/brandpdf/';
			$logoArray       = [];
			$backgroundArray = [];

			// Retrieve custom logos
			$logoFilesArray = dol_dir_list($uploadDir . '/logos');
			foreach($logoFilesArray as $file) {
				$logoArray[$file['name']] .= $file['name'];
			}

			// Retrieve custom backgrounds
			$backgroundFilesArray = dol_dir_list($uploadDir . '/template_pdf');
			foreach($backgroundFilesArray as $file) {
				$backgroundArray[$file['name']] .= $file['name'];
			}

			print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?facid='. $object->id . '" name="generateSpecial">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="builddoc">';

			print load_fiche_titre($langs->trans('BrandPDF'), '', '');

			print $langs->trans('Logo') . ' : ' . $form->selectArray('document_logo', $logoArray, 'ifone', 1, 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
			print '<br></td>';
			print $langs->trans('Background') . ' : ' . $form->selectArray('document_background', $backgroundArray, 'ifone', 1, 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
			print '<input class="button buttongen reposition nomargintop nomarginbottom" id="generatebutton" name="generatebutton" type="submit" value="'. $langs->trans('SpecialGenerate') .'"';
		}

		return 0;
	}
}
