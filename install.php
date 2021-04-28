<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

include('adodb/adodb.inc.php');
require_once('vteversion.php'); // crmv@181168

if(version_compare(phpversion(), '7.0') < 0) { // crmv@180737
	require_once('errorpages/phpversionfail.php'); // crmv@138188
	die();
}

global $table_prefix;
if (empty($table_prefix)) {
	$table_prefix = 'vte';
}

require_once('include/install/language/en_us.lang.php');
require_once('include/install/resources/utils.php');
global $installationStrings, $vte_legacy_version;

// crmv@37463 check permissions
$oldinstall = glob('*install.php.txt');
if (is_array($oldinstall) && count($oldinstall) > 0) die('Unauthorized!');
// crmv@37463e


@include_once('config.db.php');
global $dbconfig, $vtconfig;
if(empty($_REQUEST['file']) && is_array($vtconfig) && $vtconfig['quickbuild'] == 'true') {
	$the_file = 'BuildInstallation.php';
} elseif (!empty($_REQUEST['file'])) {
	$the_file = $_REQUEST['file'];
} else {
	$the_file = "welcome.php";
}

Common_Install_Wizard_Utils::checkFileAccess("install/".$the_file);
include("install/".$the_file);
?>
