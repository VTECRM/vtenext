<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/ComboUtil.php');
$fld_module=$_REQUEST["fld_module"];
$tableName=$_REQUEST["table_name"];
$fldPickList =  $_REQUEST['listarea'];
$roleid =  $_REQUEST['roleid'];
//changed by dingjianting on 2006-10-1 for picklist editor
$fldPickList = utf8RawUrlDecode($fldPickList); 
$uitype = $_REQUEST['uitype'];
global $adb, $default_charset,$table_prefix;

$sql = "select picklistid from ".$table_prefix."_picklist where name=?";
$picklistid = $adb->query_result($adb->pquery($sql, array($tableName)),0,'picklistid');

//Deleting the already existing values

if($uitype == 111 || $uitype == 16)
{
	$qry="select roleid,picklistvalueid from ".$table_prefix."_role2picklist left join ".$table_prefix."_$tableName on ".$table_prefix."_$tableName.picklist_valueid=".$table_prefix."_role2picklist.picklistvalueid where roleid=? and picklistid=? and presence=1";
	$res = $adb->pquery($qry, array($roleid, $picklistid));
	$num_row = $adb->num_rows($res);
	for($s=0;$s < $num_row; $s++)
	{
		$valid = $adb->query_result($res,$s,'picklistvalueid');
		$sql="delete from ".$table_prefix."_role2picklist where roleid=? and picklistvalueid=?";
		$adb->pquery($sql, array($roleid, $valid));
	}
//$sql = "delete from vte_role2picklist left join vte_$tableName on vte_$tableName.picklist_valueid=vte_role2picklist.picklistvalueid where roleid='$roleid' and picklistid=$picklistid and presence=0";
	//$adb->query($sql);
}
else
{
	$sql = "delete from ".$table_prefix."_role2picklist where roleid=? and picklistid=?";
	$adb->pquery($sql, array($roleid, $picklistid));
}
$pickArray = explode("\n",$fldPickList);
$count = count($pickArray);

$tabname=explode('cf_',$tableName);

if($tabname[1]!='')
       	$custom=true;

/* ticket2369 fixed */
$columnName = $tableName;
 for($i = 0; $i < $count; $i++)
 {
	 $pickArray[$i] = trim(from_html($pickArray[$i]));

	 //if UTF-8 character input given, when configuration is latin1, then avoid the entry which will cause mysql empty object exception in line 101
	 $stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$pickArray[$i]) : $pickArray[$i]; // crmv@167702
	 $pickArray[$i] = trim($stringConvert);
	 
	 if($pickArray[$i] != '')
	 {
		 $picklistcount=0;
		 //This uitype is for non-editable  picklist
		 $sql ="select $tableName from ".$table_prefix."_$tableName";
		 $res = $adb->pquery($sql, array());
		 $numrow = $adb->num_rows($res);
		 for($x=0;$x < $numrow ; $x++)
		 {
			 $picklistvalues = decode_html($adb->query_result($res,$x,$tableName));


			 global $current_language;
			 if($current_language != 'en_us') {
				 // Translate the value in database and compare with input.
				 if($fld_module == 'Events') $temp_module_strings = return_module_language($current_language, 'Calendar');
				 else $temp_module_strings = return_module_language($current_language, $fld_module);

				 $mod_picklistvalue = trim($temp_module_strings[$picklistvalues]);
				 if($mod_picklistvalue == $pickArray[$i]) {
					 $pickArray[$i] = $picklistvalues;
				 }
			 }
			 // End
			 
			 if($pickArray[$i] == $picklistvalues)
			 {
				 $picklistcount++;	
			 }

		 }

		 if($picklistcount == 0)
		 {	//Inserting a new pick list value to the corresponding picklist table
		 $picklistvalue_id = getUniquePicklistID();
		 $picklist_id = $adb->getUniqueID($table_prefix."_".$tableName);
		 if($uitype == 111)
		 {
			 $query = "insert into ".$table_prefix."_".$tableName." values(?,?,?,?)";		
			 $params = array($picklist_id, $pickArray[$i], 1, $picklistvalue_id);
		 }
		 else
		 {
			 $query = "insert into ".$table_prefix."_".$tableName." values(?,?,?,?)";		
			 $params = array($picklist_id, $pickArray[$i], 1, $picklistvalue_id);
		 }

		 $adb->pquery($query, $params);

	 }
	 $picklistcount =0;
	 $sql = "select picklist_valueid from ".$table_prefix."_$tableName where $tableName=?";
	 $pick_valueid = $adb->query_result($adb->pquery($sql, array($pickArray[$i])),0,'picklist_valueid');
	 if($uitype == 111 || $uitype==16)
	 {
		 //To get the max sortid for the non editable picklist and the inserting by increasing the sortid for editable values....
		 $sql ="select max(sortid)+1 as sortid from ".$table_prefix."_role2picklist left join ".$table_prefix."_$tableName on ".$table_prefix."_$tableName.picklist_valueid=".$table_prefix."_role2picklist.picklistvalueid where roleid=? and picklistid=?  and presence=0";
		 $sortid = $adb->query_result($adb->pquery($sql, array($roleid, $picklistid)),0,'sortid');

		 $sql = "insert into ".$table_prefix."_role2picklist values(?,?,?,?)";
		 $adb->pquery($sql, array($roleid, $pick_valueid, $picklistid, $sortid));
	 }
	 else
	 {		
		 $sql = "insert into ".$table_prefix."_role2picklist values(?,?,?,?)";
		 $adb->pquery($sql, array($roleid, $pick_valueid, $picklistid, $i));
	 }	
 }
} 

header("Location:index.php?action=SettingsAjax&module=Settings&directmode=ajax&file=PickList&fld_module=".$fld_module."&roleid=".$roleid);
?>