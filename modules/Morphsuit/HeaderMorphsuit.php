<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@35153 */
/* crmv@103922 */
global $current_language, $path;
if ($installation_mode) {
	global $currentModule, $mod_strings, $app_strings, $default_language, $theme;
	$currentModule = 'Morphsuit';
	$current_language = $default_language;
	$path = '../../';
	$mod_strings = return_module_language($current_language, $currentModule);
	$app_strings = return_application_language($current_language);
	$small_page_path = $path;
}
$small_browser_title = 'VTE Activation';
$small_page_title = 'SKIP_TITLE';
include('themes/SmallHeader.php');
?>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>include/js/<?php echo $current_language; ?>.lang.js"></script>	
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>include/js/general.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $path; ?>themes/<?php echo $theme; ?>/morphsuit.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $path; ?>themes/<?php echo $theme; ?>/vte_bootstrap.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $path; ?>themes/<?php echo $theme; ?>/style.css" />