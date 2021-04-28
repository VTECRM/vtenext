<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule;
global $mod_strings;

$record = vtlib_purify($_REQUEST['record']);
$module = vtlib_purify($_REQUEST['module']);
$return_module = vtlib_purify($_REQUEST['return_module']);
$return_action = vtlib_purify($_REQUEST['return_action']);
$return_id = vtlib_purify($_REQUEST['return_id']);
$parenttab = getParentTab();

$url = "index.php?module=$return_module&action=$return_action&record=$return_id&parenttab=$parenttab&relmodule=$module";

if(!isset($_REQUEST['record'])) die($mod_strings['ERR_DELETE_RECORD']);

$focus = CRMEntity::getInstance($currentModule);
DeleteEntity($currentModule, $return_module, $focus, $record, $return_id);

$parenttab = getParentTab();
$url .= getBasic_Advance_SearchURL();

// crmv@197516
if (stripos($url, 'searchtype') === false) {
	$url .= '&searchtype=BasicSearch&query=true';
}
// crmv@197516e

if(isset($_REQUEST['activity_mode']))
	$url .= '&activity_mode='.vtlib_purify($_REQUEST['activity_mode']);
	
@include("modules/$currentModule/DeleteCustomisations.php");

header("Location: $url");
?>