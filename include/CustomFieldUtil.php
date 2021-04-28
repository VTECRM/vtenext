<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/database/PearDatabase.php');
require_once('include/utils/utils.php');

/**
 * Function to get vte_field typename
 * @param $uitype :: uitype -- Type integer
 * returns the vte_field type name -- Type string
 */
function getCustomFieldTypeName($uitype)
{

	global $mod_strings,$app_strings;
	global $log;
	$log->debug("Entering getCustomFieldTypeName(".$uitype.") method ...");
	global $log;
        $log->info("uitype is ".$uitype);
	$fldname = '';

	if($uitype == 1 || $uitype == 255 || $uitype == 55) //crmv@125366
	{
		$fldname = $mod_strings['Text'];
	}
	elseif($uitype == 7)
	{
		$fldname = $mod_strings['Number'];
	}
	elseif($uitype == 9)
	{
		$fldname = $mod_strings['Percent'];
	}
	elseif($uitype == 5)
	{
		$fldname = $mod_strings['Date'];
	}
	elseif($uitype == 13)
	{
		$fldname = $mod_strings['Email'];
	}
	elseif($uitype == 11)
	{
		$fldname = $mod_strings['Phone'];
	}
	elseif($uitype == 15 || $uitype == 300) //crmv@136405
	{
		$fldname = $mod_strings['PickList'];
	}
	elseif($uitype == 17)
	{
		$fldname = $mod_strings['LBL_URL'];
	}
	elseif($uitype == 56)
	{
		$fldname = $mod_strings['LBL_CHECK_BOX'];
	}
	elseif($uitype == 71)
	{
		$fldname = $mod_strings['Currency'];
	}
	elseif($uitype == 21)
	{
		$fldname = $mod_strings['LBL_TEXT_AREA'];
	}
	elseif($uitype == 33)
	{
		$fldname = $mod_strings['LBL_MULTISELECT_COMBO'];
	}
	elseif($uitype == 85)
	{
		$fldname = $mod_strings['Skype'];
	}
	//crmv@picklistmultilanguage
	elseif($uitype == 1015)
	{
		$fldname = $mod_strings['Picklistmulti'];
	}
	//crmv@picklistmultilanguage end
$log->debug("Exiting getCustomFieldTypeName method ...");
	return $fldname;
}

/**
 * Function to get custom vte_fields
 * @param $module :: vte_table name -- Type string
 * returns customfields in key-value pair array format
 */
function getCustomFieldArray($module)
{
	global $log;
	$log->debug("Entering getCustomFieldArray(".$module.") method ...");
	global $adb,$table_prefix;
	$custquery = "select tablename,fieldname from ".$table_prefix."_field where tablename=? and ".$table_prefix."_field.presence in (0,2) order by tablename";
	$custresult = $adb->pquery($custquery, array($table_prefix.'_'.strtolower($module).'cf'));
	$custFldArray = Array();
	$noofrows = $adb->num_rows($custresult);
	for($i=0; $i<$noofrows; $i++)
	{
		$colName=$adb->query_result($custresult,$i,"fieldname");
		$custFldArray[$colName] = $i;
	}
	$log->debug("Exiting getCustomFieldArray method ...");
	return $custFldArray;
}

/**
 * Function to get columnname and vte_fieldlabel from vte_field vte_table
 * @param $module :: module name -- Type string
 * @param $trans_array :: translated column vte_fields -- Type array
 * returns trans_array in key-value pair array format
 */
function getCustomFieldTrans($module, $trans_array)
{
	global $log;
	$log->debug("Entering getCustomFieldTrans(".$module.",". $trans_array.") method ...");
	global $adb,$table_prefix;
	$tab_id = getTabid($module);
	$custquery = "select columnname,fieldlabel from ".$table_prefix."_field where generatedtype=2 and ".$table_prefix."_field.presence in (0,2) and tabid=?";
	$custresult = $adb->pquery($custquery, array($tab_id));
	$custFldArray = Array();
	$noofrows = $adb->num_rows($custresult);
	for($i=0; $i<$noofrows; $i++)
	{
		$colName=$adb->query_result($custresult,$i,"columnname");
		$fldLbl = $adb->query_result($custresult,$i,"fieldlabel");
		$trans_array[$colName] = $fldLbl;
	}
	$log->debug("Exiting getCustomFieldTrans method ...");
}


/**
 * Function to get customfield record from vte_field vte_table
 * @param $tab :: Tab ID -- Type integer
 * @param $datatype :: vte_field name -- Type string
 * @param $id :: vte_field Id -- Type integer
 * returns the data result in string format
 */
function getCustomFieldData($tab,$id,$datatype)
{
	global $log;
	$log->debug("Entering getCustomFieldData(".$tab.",".$id.",".$datatype.") method ...");
	global $adb,$table_prefix;
	$query = "select * from ".$table_prefix."_field where tabid=? and fieldid=? and ".$table_prefix."_field.presence in (0,2)";
	$result = $adb->pquery($query, array($tab, $id));
	$return_data=$adb->fetch_array($result);
	$log->debug("Exiting getCustomFieldData method ...");
	return $return_data[$datatype];
}


/**
 * Function to get customfield type,length value,decimal value and picklist value
 * @param $label :: vte_field typename -- Type string
 * @param $typeofdata :: datatype -- Type string
 * returns the vte_field type,length,decimal
 * and picklist value in ';' separated array format
 */
function getFldTypeandLengthValue($label,$typeofdata)
{
	global $log;
	global $mod_strings,$app_strings;
	$log->debug("Entering getFldTypeandLengthValue(".$label.",".$typeofdata.") method ...");
	if($label == $mod_strings['Text'])
	{
		$types = explode("~",$typeofdata);
		$data_array=array('0',$types[3]);
		$fieldtype = implode(";",$data_array);
	}
	elseif($label == $mod_strings['Number'])
	{
		$types = explode("~",$typeofdata);
		$data_decimal = explode(",",$types[2]);
		$data_array=array('1',$data_decimal[0],$data_decimal[1]);
		$fieldtype = implode(";",$data_array);
	}
	elseif($label == $mod_strings['Percent'])
	{
		$types = explode("~",$typeofdata);
		$data_array=array('2','5',$types[3]);
		$fieldtype = implode(";",$data_array);
	}
	elseif($label == $mod_strings['Currency'])
	{
		$types = explode("~",$typeofdata);
		$data_decimal = explode(",",$types[2]);
		$data_array=array('3',$data_decimal[0],$data_decimal[1]);
		$fieldtype = implode(";",$data_array);
	}
	elseif($label == $mod_strings['Date'])
	{
		$fieldtype = '4';
	}
	elseif($label == $mod_strings['Email'])
	{
		$fieldtype = '5';
	}
	elseif($label == $mod_strings['Phone'])
	{
		$fieldtype = '6';
	}
	elseif($label == $mod_strings['PickList'])
	{
		$fieldtype = '7';
	}
	elseif($label == $mod_strings['LBL_URL'])
	{
		$fieldtype = '8';
	}
	elseif($label == $mod_strings['LBL_CHECK_BOX'])
	{
		$fieldtype = '9';
	}
	elseif($label == $mod_strings['LBL_TEXT_AREA'])
	{
		$fieldtype = '10';
	}
	elseif($label == $mod_strings['LBL_MULTISELECT_COMBO'])
        {
                $fieldtype = '11';
        }
	elseif($label == $mod_strings['Skype'])
	{
		$fieldtype = '12';
	}
	$log->debug("Exiting getFldTypeandLengthValue method ...");
	return $fieldtype;
}

function getCalendarCustomFields($tabid,$mode='edit',$col_fields=array()) { //crmv@54188
	global $adb, $log, $current_user,$table_prefix, $iAmAProcess;	//crmv@105685
	$log->debug("Entering getCalendarCustomFields($tabid, $mode, $col_fields)");

	require('user_privileges/requireUserPrivileges.php'); // crmv@39110

	$block = getBlockId($tabid,"LBL_CUSTOM_INFORMATION");
	$custparams = array($block, $tabid);

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0 || $iAmAProcess === true) {	//crmv@105685
		$custquery = "select * from ".$table_prefix."_field where block=? AND ".$table_prefix."_field.tabid=? ORDER BY sequence";
	} else {
		//crmv@fix field profile
		$profileList = getCurrentUserProfileList();
 		$custquery = "SELECT ".$table_prefix."_field.* FROM ".$table_prefix."_field" .
 				" INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid" .
 				" WHERE ".$table_prefix."_field.block=? AND ".$table_prefix."_field.tabid=? " .
 				" AND ".$table_prefix."_def_org_field.visible=0 ";
	    $custquery.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
	        if (count($profileList) > 0) {
		  	 	$custquery.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
		  	 	array_push($custparams, $profileList);
		}
	    $custquery.=" AND ".$table_prefix."_profile2field.visible = 0) ";
 		$custquery.=" ORDER BY ".$table_prefix."_field.sequence";
 		//crmv@fix field profile end
	}
	$custresult = $adb->pquery($custquery, $custparams);

	$custFldArray = Array();
	$noofrows = $adb->num_rows($custresult);
	for($i=0; $i<$noofrows; $i++)
	{
		$fieldid=$adb->query_result($custresult,$i,"fieldid");
		$fieldname=$adb->query_result($custresult,$i,"fieldname");
		$fieldlabel=$adb->query_result($custresult,$i,"fieldlabel");
		$columnName=$adb->query_result($custresult,$i,"columnname");
		$uitype=$adb->query_result($custresult,$i,"uitype");
		$maxlength = $adb->query_result($custresult,$i,"maximumlength");
		$generatedtype = $adb->query_result($custresult,$i,"generatedtype");
		$typeofdata = $adb->query_result($custresult,$i,"typeofdata");
		$readonly = $adb->query_result($custresult,$i,"readonly");	//crmv@27597

		//crmv@sdk-18508  crmv@27597
		$sdk_files = SDK::getViews('Calendar',$mode);
		if (!empty($sdk_files)) {
			foreach($sdk_files as $sdk_file) {
				$success = false;
				$readonly_old = $readonly;
				include($sdk_file['src']);
				SDK::checkReadonly($readonly_old,$readonly,$sdk_file['mode']);
				if ($success && $sdk_file['on_success'] == 'stop') {
					break;
				}
			}
		}
		//crmv@sdk-18508e  crmv@27597e

		if ($mode == 'edit' || $mode == '') { // crmv@95751
			//crmv@103373 crmv@106857 crmv@161211
			global $processMakerView;
			if ($processMakerView) {
				$readonly = 1;
				// TODO creare un metodo o degli array per gestire questi casi
				if (in_array($uitype,array(69,220))) $readonly = 100;	// hide table fields
				if ($uitype == 300) $uitype = 15;	//crmv@111091
				if (in_array($uitype,array(7,9,71,72))) $uitype = 1;	//crmv@96450
			}
			//crmv@103373e crmv@106857e crmv@161211e
			$custfld = getOutputHtml($uitype, $fieldname, $fieldlabel, $maxlength, $col_fields,$generatedtype,getTabName($tabid),$mode, $readonly, $typeofdata);	//crmv@27597+61692
			$custfld[] = $fieldid;
		}
		if ($mode == 'detail_view') {
			$custfld = getDetailViewOutputHtml($uitype, $fieldname, $fieldlabel, $col_fields,$generatedtype,$tabid);
			$custfld['readonly'] = $readonly;	//crmv@56533
		}
		$custFldArray[] = $custfld;
	}
	$log->debug("Exiting getCalendarCustomFields()");
	return $custFldArray;
}