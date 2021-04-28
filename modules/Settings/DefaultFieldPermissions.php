<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings;
global $app_strings;
global $app_list_strings;

$smarty = new VteSmarty();


global $adb;
global $theme;
global $theme_path;
global $image_path;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

//$field_module = Array('Leads','Accounts','Contacts','Potentials','HelpDesk','Products','Documents','Calendar','Events','Vendors','PriceBooks','Quotes','PurchaseOrder','SalesOrder','Invoice','Campaigns','Faq');
$field_module=getFieldModuleAccessArray();
$allfields=Array();
foreach($field_module as $fld_module=>$mod_name)
{
	$fieldListResult = getDefOrgFieldList($fld_module);
	$noofrows = $adb->num_rows($fieldListResult);
	$language_strings = return_module_language($current_language,$fld_module);
	$allfields[$fld_module] = getStdOutput($fieldListResult, $noofrows, $language_strings,$profileid);
	// set the labels
	$field_module[$fld_module] = getTranslatedString($fld_module, $fld_module);
}
asort($field_module);

if($_REQUEST['fld_module'] != '')
	$smarty->assign("DEF_MODULE",$_REQUEST['fld_module']);
else
	$smarty->assign("DEF_MODULE",'Leads');

/** Function to get the field label/permission array to construct the default orgnization field UI for the specified profile 
  * @param $fieldListResult -- mysql query result that contains the field label and uitype:: Type array
  * @param $lang_strings -- i18n language mod strings array:: Type array
  * @param $profileid -- profile id:: Type integer
  * @returns $standCustFld -- field label/permission array :: Type varchar
  *
 */	
function getStdOutput($fieldListResult, $noofrows, $lang_strings,$profileid)
{
	global $adb;
	global $image_path;
	$standCustFld = Array();		
	for($i=0; $i<$noofrows; $i++,$row++)
	{
		$uitype = $adb->query_result($fieldListResult,$i,"uitype");
		$fieldlabel = $adb->query_result($fieldListResult,$i,"fieldlabel");
		$typeofdata = $adb->query_result($fieldListResult,$i,"typeofdata");
		$fieldtype = explode("~",$typeofdata);
		if($lang_strings[$fieldlabel] !='')
			$standCustFld []= $lang_strings[$fieldlabel];
		else
			$standCustFld []= $fieldlabel;
			
		
		if($adb->query_result($fieldListResult,$i,"visible") == 0 || ($uitype == 111 && $fieldtype[1] == "M"))
		{
			$visible = "<i class=\"vteicon checkok nohover\">check</i>";
		}
		else
		{
			$visible = "<i class=\"vteicon checkko nohover\">clear</i>";
		}	
		$standCustFld []= $visible;
	}
	$standCustFld=array_chunk($standCustFld,2);	
	$standCustFld=array_chunk($standCustFld,4);	
	return $standCustFld;
}

$smarty->assign("FIELD_INFO",$field_module);
$smarty->assign("FIELD_LISTS",$allfields);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MODE",'view');                    
$smarty->assign("THEME", $theme);                    
$smarty->display("FieldAccess.tpl");
