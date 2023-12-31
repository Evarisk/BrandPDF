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
 * \file    admin/about.php
 * \ingroup brandpdf
 * \brief   About page of module BrandPDF.
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
require_once __DIR__ . '/../lib/brandpdf.lib.php';
require_once __DIR__ . '/../core/modules/modBrandPDF.class.php';

// Global variables definitions
global $db, $langs, $user;

// Load translation files required by the page
saturne_load_langs(['admin']);

// Initialize technical objects
$modBrandPDF = new modBrandPDF($db);

// Get parameters
$backtopage = GETPOST('backtopage', 'alpha');

// Security check - Protection if external user
$permissiontoread = $user->rights->brandpdf->adminpage->read;
saturne_check_access($permissiontoread);

/*
 * View
 */

$title    = $langs->trans('ModuleAbout', 'BrandPDF');
$help_url = 'FR:Module_BrandPDF';

saturne_header(0,'', $title, $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ?: DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans('BackToModuleList') . '</a>';
print load_fiche_titre($title, $linkback, 'brandpdf_color@brandpdf');

// Configuration header
$head = brandpdf_admin_prepare_head();
print dol_get_fiche_head($head, 'about', $title, -1, 'brandpdf_color@brandpdf');

print $modBrandPDF->getDescLong();

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
