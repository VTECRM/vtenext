<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29615
global $app_strings, $app_list_strings, $log, $currentModule, $current_user;//crmv@203484 removed global singlepane
global $theme,$adb,$table_prefix;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once('modules/VteCore/layout_utils.php');	//crmv@30447

$smarty = new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("COLUMNAME", $columnname);

$module_name = $_REQUEST["parent_module"];
if ($module_name == 'Calendar')
	$tabid = '9,16';
else
	$tabid =  getTabid($module_name);
$language = VteSession::get('authenticated_user_language');
$mod_strings = return_module_language($language, $module_name);

$option = $_REQUEST['option'];
//list($tablename,$columnname,$fieldname,$fieldlabel,$typeofdata,$reallabel) = explode(":",$_REQUEST["value"]);
if ($_REQUEST['type'] == 'AdvancedSearch') {
	$value = explode(":",$_REQUEST["value"]);
	$value = explode('.',$value[0]);
	if ($value[1] != '') {
		$field = $value[1];
		$sql = "SELECT * FROM ".$table_prefix."_field WHERE tabid IN ($tabid) AND columnname=?";//crmv@208173
	}
	else {
		$field = $value[0];
		$sql = "SELECT * FROM ".$table_prefix."_field WHERE tabid IN ($tabid) AND fieldname=?";//crmv@208173
	}
} elseif ($_REQUEST['type'] == 'CustomView') {
	$value = explode(":",$_REQUEST["value"]);
	$value1 = explode('_',$value[3]);
    $field = $value[1];

    $tabid = getTabid($value1[0]);
	if ($tabid == 9) $tabid = '9,16';
	$sql = "SELECT * FROM ".$table_prefix."_field WHERE tabid IN ($tabid) AND columnname=?";//crmv@208173
	
	// get saved value for the view
	$filtervalue = null;
	//crmv@42329
	$viewid = $_REQUEST['viewid'];
	$seq = intval($_REQUEST['selectsequence'])-1;
	if (!empty($viewid)) {
		if (strpos($viewid,"ADVSHARE_") !== false){
			$viewid = intval(str_replace("ADVSHARE_",'',$viewid));
			if (!empty($viewid)) {
				$res = $adb->pquery("select value from tbl_s_advancedrulefilters where advrule_id = ? and columnindex = ? and columnname = ?", array($viewid, $seq, vtlib_purify($_REQUEST['value'])));
				if ($res && $adb->num_rows($res) > 0){
					$filtervalue = $adb->query_result($res, 0, 'value');
				}
			}
		}
		else{
			$viewid = intval($viewid);
			$res = $adb->pquery("select value from {$table_prefix}_cvadvfilter where cvid = ? and columnindex = ? and columnname = ?", array($viewid, $seq, vtlib_purify($_REQUEST['value'])));
			if ($res && $adb->num_rows($res) > 0){
				$filtervalue = $adb->query_result($res, 0, 'value');
			}
		}
	}
	//crmv@42329e
}
$result = $adb->pquery($sql, array($field));
$uitype = $adb->query_result($result,0,"uitype");
$typeofdata = $adb->query_result($result,0,"typeofdata");  
$generatedtype = $adb->query_result($result,0,"generatedtype");
$fieldname = $adb->query_result($result,0,"fieldname");
$fieldid = $adb->query_result($result,0,"fieldid");
$maxlength = "100";
$readonly = 1;
$col_fields = array();

if ($uitype == 56) {
	($filtervalue == 'Yes') ? $filtervalue = '1' : $filtervalue = '0';
}

if (!is_null($filtervalue)) $col_fields[$fieldname] = $filtervalue;

$field_obj = WebserviceField::fromQueryResult($adb,$result,0);
$fieldDataType = $field_obj->getFieldDataType();

if ($uitype == 4) $uitype = 1;	//crmv@122071
if (in_array($option,array('s','ew','c','k'))) {	//crmv@128159
	$uitype = 1;
} else {
	if ($fieldDataType == 'datetime' && in_array($option,array('e','n'))) $uitype = 5; //crmv@146461
	if($fieldDataType == 'picklist' || $fieldDataType == 'multipicklist') { // crmv@204256
		// crmv@201442
		if ($uitype == 310) {
			$uitype = 311;
		} else {
			$uitype = 33;
		}
		// crmv@201442e
		$col_fields[$fieldname] = str_replace(',',' |##| ',$col_fields[$fieldname]); // crmv@198652
	}
}

if ($_REQUEST['mode'] == 'DisplayFieldName')
	echo getDisplayFieldName($uitype,$fieldname);
else {
	if ($option != '') {
		global $showfullusername;
		$showfullusername_change = false;
		if($showfullusername) {
			$showfullusername_change = true;
			$showfullusername = false;
		}
		
		$custfld = getOutputHtml($uitype, $fieldname, $fieldlabel, $maxlength, $col_fields, $generatedtype, $module_name, '', $readonly, $typeofdata);
		
		// crmv@129841
		// change fieldname to avoid duplicates
		//crmv@166849
		if($fieldname == 'assigned_user_id'){
			$fldgroupname = 'assigned_group_id'.'_'.intval($_REQUEST['selectsequence']);
			$smarty->assign('fldgroupname',$fldgroupname);
		}
		//crmv@166849e
		$fieldname .= '_'.intval($_REQUEST['selectsequence']);
		$custfld[2][0] = $fieldname; 
		// crmv@129841e
		
		// crmv@148960
		// use a multiselect also for multilang picklist
		if ($fieldDataType == 'picklistmultilanguage' && in_array($option,array('n','e'))) {
			$custfld[0][0] = 33;
		}
		// crmv@148960e

		$custfld[] = $fieldid;
		$Column_Data[][] = $custfld;
		
		if($showfullusername) $showfullusername = true;
		
		echo "<table>";
		$smarty->assign('data', $Column_Data);
		$smarty->assign('NOLABEL', true);
		$smarty->assign('FROMCUSTOMVIEW', true); // crmv@129272
		echo $smarty->fetch("DisplayFields.tpl");
		echo "</table>";
	}	
	echo "$$$";
	echo getDisplayFieldName($uitype,$fieldname);
	echo "@@@";
	echo $reallabel;
	echo "@@@";
	echo $typeofdata;
}
//crmv@29615e
?>