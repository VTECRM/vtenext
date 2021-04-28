<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@38592 crmv@47490 */
$record = intval($_REQUEST['record']);
$crmid = intval($_REQUEST['crmid']);
$appkey = $_REQUEST['appkey']; // used only for non-logged users
$hideAddress = false;
$trackUser = false;

// allow un-logged access to this file
if (!VteSession::hasKey('authenticated_user_id')) {
 include(dirname(__FILE__).'/../../config.inc.php');

 // this file must be called from TrackLink.php
 if ($appkey != $application_unique_key) die('Unauthorized');

 chdir($root_directory);
 require_once('include/utils/utils.php');
 $currentModule = 'Newsletter';
 $theme = $default_theme;
 $small_page_path = '../'; // crmv@192078
 $hideAddress = true;
 $trackUser = true;
}

global $adb, $table_prefix;
global $theme, $small_page_path;

if (!vtlib_isModuleActive($currentModule) || $record <= 0) die('Unauthorized');

$focus = CRMEntity::getInstance($currentModule);
$focus->id = $record;
$focus->retrieve_entity_info($record, $currentModule, false);
if ($focus->column_fields["record_id"] != $record) die('Not found');

$focus->showNewsletter($crmid, $hideAddress, $trackUser, ($_REQUEST['show_back_button'] == 'true')); // crmv@135115