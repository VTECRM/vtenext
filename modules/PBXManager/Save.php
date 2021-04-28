<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user, $currentModule;

$focus = CRMEntity::getInstance($currentModule);

if($mode) $focus->mode = $mode;
if($record)$focus->id  = $record;

setObjectValuesFromRequest($focus);

$mode = $_REQUEST['mode'];
$record=$_REQUEST['record'];

if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
$focus->save($currentModule);
$return_id = $focus->id;

$parenttab = getParentTab();
if($_REQUEST['return_module'] != '') $return_module = vtlib_purify($_REQUEST['return_module']);
else $return_module = $currentModule;

if($_REQUEST['return_action'] != '') $return_action = vtlib_purify($_REQUEST['return_action']);
else $return_action = "DetailView";

if($_REQUEST['return_id'] != '') $return_id = vtlib_purify($_REQUEST['return_id']);

//crmv@54375
if($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e

$url = "index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']);

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");
?>