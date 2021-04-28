<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include_once('modules/CustomView/CustomView.php');
global $mod_strings;
global $app_strings;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
global $adb,$current_language;
$smarty = new VteSmarty();

$mode = $_REQUEST['mode'];
$module = $_REQUEST['sharing_module'];
$mode = $_REQUEST['mode'];
$shareid = $_REQUEST['shareid'];
if (isset($_REQUEST['userid']) && $_REQUEST['userid'] != ''){
	$return_action='DetailView';
	$return_module='Users';
	$record = $_REQUEST['userid'];
	$adv_sharing = 'true';
}
else{
	$return_action='AdvRuleDetailView';
	$return_module='Settings';
	$record = 'ADVSHARE_'.$shareid;
}
if(isset($_REQUEST['shareid']) && $_REQUEST['shareid'] != '')
{	
	$shareid=$_REQUEST['shareid'];
	$shareInfo=getAdvSharingRuleInfo($shareid);
}

eval(Users::m_de_cryption());
eval($hash_version[9]);

$oCustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
$modulecollist = $oCustomView->getModuleColumnsList($module);
if(isset($modulecollist))
{
	$choosecolhtml = $oCustomView->getByModule_ColumnsHTML($module,$modulecollist);
}

$c_strings=return_module_language($current_language,'CustomView');

if ($mode == 'edit') {
	$advfilterlist = getAdvRuleFilterByRuleid($shareid);
	$log->info('CustomView :: Successfully got Advanced Filter for the Viewid'.$shareid,'info');
	for($i=1;$i<6;$i++)
	{
		$advfilterhtml = getAdvRuleCriteriaHTML($advfilterlist[$i-1]["comparator"]);
		$advcolumnhtml = $oCustomView->getByModule_ColumnsHTML($module,$modulecollist,$advfilterlist[$i-1]["columnname"],false);	//crmv@34627
		$smarty->assign("FOPTION".$i,$advfilterhtml);
		$smarty->assign("BLOCK".$i,$advcolumnhtml);
		$col = explode(":",$advfilterlist[$i-1]["columnname"]);
		$temp_val = explode(",",$advfilterlist[$i-1]["value"]);
		$and_text = "&nbsp;".$mod_strings['LBL_AND'];
		if($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || $col[4] == 'DT')
		{
			$val = Array();
			for($x=0;$x<count($temp_val);$x++)
			if(trim($temp_val[$x] != ""))
				$val[$x] = getDisplayDate(trim($temp_val[$x]));
			$advfilterlist[$i-1]["value"] = implode(", ",$val);
			$and_text = "<em old='(yyyy-mm-dd)'>(".$current_user->date_format.")</em>&nbsp;".$mod_strings['LBL_AND'];
		}
		$smarty->assign("VALUE".$i,$advfilterlist[$i-1]["value"]);
		$smarty->assign("AND_TEXT".$i,$and_text);
	}
}
else{
	$advfilterhtml = getAdvRuleCriteriaHTML();
	for($i=1;$i<6;$i++)
	{
		$smarty->assign("FOPTION".$i,$advfilterhtml);
		$smarty->assign("BLOCK".$i,$choosecolhtml);
	}
}
$smarty->assign("module",$module);
$smarty->assign("MODULE",$module);	//crmv@51071
$smarty->assign("mode",$mode);
$smarty->assign("shareid",$shareid);

$smarty->assign("shareInfo", $shareInfo);
$smarty->assign("return_action", $return_action);
$smarty->assign("return_module", $return_module);
$smarty->assign("record", $record);
$smarty->assign("adv_sharing", $adv_sharing);

$smarty->assign("image_path",$image_path);
$smarty->assign("app_strings", $app_strings);
$smarty->assign("mod_strings", $mod_strings);
$smarty->assign("MOD", $c_strings);
$smarty->assign("CHOOSECOLUMN",$choosecolhtml);

$smarty->display("CreateAdvSharingRule.tpl");
die;
?>