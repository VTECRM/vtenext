<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

global $mod_strings;
global $adb;

$ECU = EntityColorUtils::getInstance(); //crmv@134668

//crmv@10445
if ($_REQUEST['remove_all'] == 'true'){
	$tabid = getTabid($_REQUEST['clv_module']);
	$delete = " delete from tbl_s_lvcolors where tabid = ?";
	$adb->pquery($delete, array($tabid));
}
elseif($_REQUEST['fieldname'] != "" && $_REQUEST['clv_module'] != "") {
//crmv@10445e	
	global $adb;
	$tabid = getTabid($_REQUEST['clv_module']);
	$delete = " delete from tbl_s_lvcolors where tabid = ?";
	$adb->pquery($delete, array($tabid));
	foreach($_REQUEST as $key => $value) {
		if (preg_match("/^{$_REQUEST['fieldname']}/", $key)) {
			$arr = explode("_",$key);
			// crmv@167234 - remove useless code
			$fldid = $arr[count($arr)-1];
              
			if($fldid != "" && $value != "") {
				if ($_REQUEST["value_".$_REQUEST['fieldname'].$fldid] == 'yes') $_REQUEST["value_".$_REQUEST['fieldname'].$fldid] = 1;
				if ($_REQUEST["value_".$_REQUEST['fieldname'].$fldid] == 'no') $_REQUEST["value_".$_REQUEST['fieldname'].$fldid] = 0;
				//crmv@134668
				$fieldvalue = $ECU->transformValueForSave($_REQUEST['clv_module'],$_REQUEST['fieldname'],$_REQUEST["value_".$_REQUEST['fieldname'].$fldid]);
				$query = " insert into tbl_s_lvcolors values (?,?,?,?)";
				//crmv@134668e
				$adb->pquery($query, array($tabid, $_REQUEST['fieldname'], $fieldvalue, $value));
			}				
		}
	}
}
header("Location: index.php?module=Settings&action=ColoredListView&parenttab=Settings");