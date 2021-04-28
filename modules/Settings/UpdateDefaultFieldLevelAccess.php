<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix, $metaLogs; // crmv@49398

$field_module = getFieldModuleAccessArray();

foreach ($field_module as $fld_module=>$fld_name) {
	$fieldListResult = getDefOrgFieldList($fld_module);
	$noofrows = $adb->num_rows($fieldListResult);
	$tab_id = getTabid($fld_module);
	
	for ($i=0; $i<$noofrows; $i++) {
	
		$fieldid =  $adb->query_result($fieldListResult,$i,"fieldid");
		$displaytype = $adb->query_result($fieldListResult,$i,"displaytype");
		$visible = $_REQUEST[$fieldid];
		$visible_value = ($visible == 'on' ? 0 : 1);
		
		//Updating the Mandatory vte_fields
		$uitype = $adb->query_result($fieldListResult,$i,"uitype");
		$fieldname = $adb->query_result($fieldListResult,$i,"fieldname");
		$typeofdata = $adb->query_result($fieldListResult,$i,"typeofdata");
		$fieldtype = explode("~",$typeofdata);
		
		if (
			in_array($uitype, [2,3,6,22,73,24,81,50,23,16,53,255,20]) || 
			$displaytype == 3 || 
			($displaytype != 3 && $fieldname == "activitytype" && $uitype == 15) || 
			($uitype == 111 && $fieldtype[1] == "M"))
		{
			$visible_value = 0;
		}

		//Updating the database
		$update_query = "update ".$table_prefix."_def_org_field set visible=? where fieldid=? and tabid=?";
		$update_params = array($visible_value, $fieldid, $tab_id);
		$adb->pquery($update_query, $update_params);
	}

	if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFIELDS, $tab_id, array('module'=>$fld_module)); // crmv@49398
}

header("Location: index.php?action=DefaultFieldPermissions&module=Settings&parenttab=Settings&fld_module=".$_REQUEST['fld_module']);
