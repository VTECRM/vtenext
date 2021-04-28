<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings;
global $adb;

function getFldOutput($fieldListResult, $noofrows, $lang_strings) {
	global $adb;
	
	$standCustFld = Array();
	for ($i=0; $i<$noofrows; $i++) 	{
		$uitype = $adb->query_result($fieldListResult,$i,"uitype");
		$displaytype = $adb->query_result($fieldListResult,$i,"displaytype");
		$fieldlabel = $adb->query_result($fieldListResult,$i,"fieldlabel");
		$typeofdata = $adb->query_result($fieldListResult,$i,"typeofdata");
		$fieldtype = explode("~",$typeofdata);
                $mandatory = '';
		$readonly = '';
                if($uitype == 2 || $uitype == 3 || $uitype == 6 || $uitype == 22 || $uitype == 73 || $uitype == 24 || $uitype == 81 || $uitype == 50 || $uitype == 23 || $uitype == 16 || $uitype == 20 || $uitype == 53 || $uitype == 255 || $displaytype == 3 || ($displaytype != 3 && $fieldlabel == "Activity Type" && $uitype == 15) || ($uitype == 111 && $fieldtype[1] == "M"))
                {
                        $mandatory = '<font color="red">*</font>';
						$readonly = 'disabled';
                }

		if($lang_strings[$fieldlabel] !='')
			$standCustFld []= $mandatory.' '.$lang_strings[$fieldlabel];
		else
			$standCustFld []= $mandatory.' '.$fieldlabel;

		if($adb->query_result($fieldListResult,$i,"visible") == 0 && $displaytype!=3) {
			if($fieldlabel == 'Activity Type') {
				$visible = "checked";
				$readonly = 'disabled';
			} else {
				$visible = "checked";
			}
		} elseif($displaytype==3) {
			$visible = "checked";
			$readonly = 'disabled';
		} else {
			$visible = "";
		}
		
		$standCustFld []= '<input type="checkbox" name="'.$adb->query_result_no_html($fieldListResult,$i,"fieldid").'" '.$visible.' '.$readonly.'>';
	}
	
	$standCustFld=array_chunk($standCustFld,2);	
	$standCustFld=array_chunk($standCustFld,4);	
	
	return $standCustFld;
}

$field_module=getFieldModuleAccessArray();
$allfields=Array();
foreach($field_module as $fld_module) {
	$fieldListResult = getDefOrgFieldList($fld_module);
	$noofrows = $adb->num_rows($fieldListResult);
	$language_strings = return_module_language($current_language,$fld_module);
	$allfields[$fld_module] = getFldOutput($fieldListResult, $noofrows, $language_strings);
}


$smarty = new VteSmarty();	//crmv@83752

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

if($_REQUEST['fld_module'] != '')
	$smarty->assign("DEF_MODULE",$_REQUEST['fld_module']);
else
	$smarty->assign("DEF_MODULE",'Leads');

$smarty->assign("FIELD_INFO",$field_module);
$smarty->assign("FIELD_LISTS",$allfields);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME",$theme);	//crmv@83752
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MODE",'edit');

$smarty->display("FieldAccess.tpl");
