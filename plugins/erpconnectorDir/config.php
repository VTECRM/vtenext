<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $erpconnector_dir;
$erpconnector_dir = dirname( __FILE__ ).'/'; //ex. $erpconnector_dir = 'plugins/erpconnectorDir/'; // crmv@177497
if ($erpconnector_dir == '') {
	die("Configurare cartella erpconnector in config.php\n");
}

global $log_script_state, $log_script_content;
$log_script_state = 'erp_log_script_state';
$log_script_content = 'erp_log_script_content';

// crmv@177497
chdir($erpconnector_dir);
require("../../config.inc.php");
// crmv@177497e
chdir($root_directory);
require_once('include/utils/utils.php');
require_once($erpconnector_dir."utils.php");
require_once($erpconnector_dir."classes.php");

/* impersonate admin user */
include_once("modules/Users/Users.php");
global $current_user;
if(!isset($current_user)){
	$current_user= CRMEntity::getInstance('Users');
	$current_user->id = 1;
}

/* use an external database */
global $dbconfig_external;
if (!isset($adbext) && is_array($dbconfig_external)) {
	$adbext = new PearDatabase();
	$adbext->resetSettings($dbconfig_external['db_type'],$dbconfig_external['db_hostname'],$dbconfig_external['db_name'],$dbconfig_external['db_username'],$dbconfig_external['db_password'], $dbconfig_external['db_charset']); // crmv@124454
	$adbext->usePersistent = false; // crmv@65455
	$adbext->connect();
	if (!$adbext->database->IsConnected()) die('Connection to external database failed');
}

// memory limit
ini_set('memory_limit','1024M');

require_once('modules/SDK/SDK.php');
SDK::getUtils();

/* select import query */
//require_once($erpconnector_dir.'query_repository/Zucchetti_AdHoc_Enterprise_7.php');
?>