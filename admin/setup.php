<?php
/* Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
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
 * \file    admin/setup.php
 * \ingroup brandpdf
 * \brief   BrandPDF media model page.
 */

// Load BrandPDF environment
if (file_exists('../brandpdf.main.inc.php')) {
	require_once __DIR__ . '/../brandpdf.main.inc.php';
} elseif (file_exists('../../brandpdf.main.inc.php')) {
	require_once __DIR__ . '/../../brandpdf.main.inc.php';
} else {
	die('Include of brandpdf main fails');
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once __DIR__ . '/../../saturne/lib/saturne.lib.php';
require_once __DIR__ . '/../lib/brandpdf.lib.php';

// Global variables definitions
global $conf, $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

$form       = new Form($db);

// Parameters
$action     = GETPOST('action', 'alpha');
$value      = GETPOST('value', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->brandpdf->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * Action
 */

if ($action == 'save') {
	$templatePdf = GETPOST('document_template', 'alpha');

	if (intval($templatePdf) >= 0) {
		dolibarr_set_const($db, 'MAIN_ADD_PDF_BACKGROUND', $templatePdf);
		setEventMessages($langs->trans('DefaultTemplateSave'), []);
	} else {
		setEventMessages($langs->trans('EmptyTemplateSelect'), [], 'errors');
	}
}

/*
 * View
 */

$title    = $langs->trans('ModuleSetup', 'BrandPDF');
$help_url = 'FR:Module_BrandPDF';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'brandpdf_color@brandpdf');

// Configuration header
$head = brandpdf_admin_prepare_head();
print dol_get_fiche_head($head, 'settings', $title, -1, 'brandpdf_color@brandpdf');

$uploadDir     = $conf->ecm->dir_output . '/brandpdf';
$templateArray = [];
// Retrieve templates
$templateFilesArray = dol_dir_list($uploadDir . '/template_pdf', 'files', 0, '.pdf$');
if (is_array($templateFilesArray) && !empty($templateFilesArray)) {
	foreach ($templateFilesArray as $templateFile) {
		$templateArray[$templateFile['name']] .= $templateFile['name'];
	}
}

print '<form method="POST" action="' . $_SERVER['REQUEST_URI'] . '" name="save">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="save">';

print load_fiche_titre($langs->trans('Config'), '', '');

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("SelectTemplate") . '</td>';
print '<td>' . $langs->trans("Action") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td><label for="DefaultTemplate">' . $langs->trans("DefaultTemplate") . '</label></td><td>';
print  $form::selectArray('document_template', $templateArray, $conf->global->MAIN_ADD_PDF_BACKGROUND, $langs->trans('SelectADefaultTemplate'), 0, 0, '', 0, 32, 0, '', 'minwidth300 maxwidth500');
print '<td><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
print '</td></tr>';

print '</table>';


// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
