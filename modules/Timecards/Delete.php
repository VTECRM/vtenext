<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule,$adb;
global $table_prefix;
$focus = CRMEntity::getInstance($currentModule);

$record = vtlib_purify($_REQUEST['record']);
$module = vtlib_purify($_REQUEST['module']);
$return_module = vtlib_purify($_REQUEST['return_module']);
$return_action = vtlib_purify($_REQUEST['return_action']);
$return_id = vtlib_purify($_REQUEST['return_id']);
$parenttab = getParentTab();

$focus->retrieve_entity_info($record, $currentModule);
$tticketid = $focus->column_fields['ticket_id'];
$sortorderid = $focus->column_fields['sortorder'];

//Added to fix 4600
$url = getBasic_Advance_SearchURL();

DeleteEntity($currentModule, $return_module, $focus, $record, $return_id);

// reorder timecards above deleted one
$sql = "select timecardsid from ".$table_prefix."_timecards where ticket_id=$tticketid and sortorder>$sortorderid order by sortorder";
$result = $adb->query($sql);
$num_row = $adb->num_rows($result);
for($i=0; $i<$num_row; $i++)
{
	$tttcid = $adb->query_result($result,$i,'timecardsid');
    $sql = "update ".$table_prefix."_timecards set sortorder=".($sortorderid+$i)." where timecardsid=$tttcid";
    $rdo = $adb->query($sql);
}

header("Location: index.php?module=$return_module&action=$return_action&record=$return_id&parenttab=$parenttab&relmodule=$module".$url);