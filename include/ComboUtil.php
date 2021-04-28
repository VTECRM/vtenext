<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/CommonUtils.php');

/** Function to  returns the combo field values in array format
  * @param $combofieldNames -- combofieldNames:: Type string array
  * @returns $comboFieldArray -- comboFieldArray:: Type string array
 */
function getComboArray($combofieldNames)
{
	global $log,$mod_strings;
        $log->debug("Entering getComboArray(".$combofieldNames.") method ...");
	global $adb,$current_user,$table_prefix;
        $roleid=$current_user->roleid;
	$comboFieldArray = Array();
	foreach ($combofieldNames as $tableName => $arrayName)
	{
		$fldArrName= $arrayName;
		$arrayName = Array();
		
		$sql = "select $tableName from ".$table_prefix."_$tableName";
		$params = array();
		if(!is_admin($current_user))
		{
			$subrole = getRoleSubordinates($roleid);
			if(count($subrole)> 0)
			{
				$roleids = $subrole;
				array_push($roleids, $roleid);
			}
			else
			{
				$roleids = $roleid;
			}
			$sql = "select $tableName from ".$table_prefix."_$tableName  inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$tableName.picklist_valueid where roleid in(". generateQuestionMarks($roleids) .") order by sortid";
			$params = array($roleids);
		}
		$result = $adb->pquery($sql, $params);	
		while($row = $adb->fetch_array($result))
		{
			$val = $row[$tableName];
			$arrayName[$val] = getTranslatedString($val);
		}
		$comboFieldArray[$fldArrName] = $arrayName;
	}
	$log->debug("Exiting getComboArray method ...");
	return $comboFieldArray;	
}

function getUniquePicklistID()
{
	global $adb,$table_prefix;
	return $adb->getUniqueID($table_prefix."_picklistvalues");
}