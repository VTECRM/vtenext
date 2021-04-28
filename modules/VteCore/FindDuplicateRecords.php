<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings, $current_language, $theme;
$image_path = "themes/$theme/images/";

require_once('modules/VteCore/layout_utils.php');

$req_module = vtlib_purify($_REQUEST['module']);
$focus = CRMEntity::getInstance($req_module);

$return_module=vtlib_purify($_REQUEST['module']);
$delete_idstring=vtlib_purify($_REQUEST['idlist']);

$parenttab = getParenttab();

$smarty = new VteSmarty();

$ids_list = array();
$errormsg = '';
if(isset($_REQUEST['save_mapping_flag']) && $_REQUEST['save_mapping_flag']=='true'){
	include("include/saveMergeCriteria.php");
	echo '<script>history.back();</script>';
	exit;
}
if(isset($_REQUEST['del_rec'])) {
	$delete_id_array=explode(",",$delete_idstring,-1);

	foreach ($delete_id_array as $id) {
		if(isPermitted($req_module,'Delete',$id) == 'yes') {
			DeleteEntity($req_module,$return_module,$focus,$id,"");
		}
		else {
        	$ids_list[] = $id;
		}
	}
	if(count($ids_list) > 0) {
		$ret = getEntityName($req_module,$ids_list);
		if(count($ret) > 0) {
	       	$errormsg = implode(',',$ret);
		}
		echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
		echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

			<table border='0' cellpadding='5' cellspacing='0' width='98%'>
			<tbody><tr>
			<td rowspan='2' width='11%'><img src='".resourcever('denied.gif')."'></td>
			<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'>
				<span class='genHeaderSmall'>$app_strings[LBL_DUP_PERMISSION] $req_module $errormsg</span></td>
			</tr>
			<tr>
			<td class='small' align='right' nowrap='nowrap'>
			<a href='javascript:window.location.reload();'>$app_strings[LBL_GO_BACK]</a><br>
			</td>
			</tr>
			</tbody></table>
			</div>";
		echo "</td></tr></table>";
		exit;
	}
}

include("include/saveMergeCriteria.php");
//crmv@36508
if (isset($_REQUEST['empty_flag'])){
	VteSession::set('duplicateshandling_empty_flag', ($_REQUEST['empty_flag'] == '1')?true:false);
}
//crmv@36508 e
$ret_arr=getDuplicateRecordsArr($req_module);

$fld_values=$ret_arr[0];
$total_num_group=count($fld_values);
$fld_name=$ret_arr[1];

$smarty->assign("NAVIGATION",$ret_arr["navigation"]);//Added for page navigation
$smarty->assign("MODULE",$req_module);
$smarty->assign("NUM_GROUP",$total_num_group);
$smarty->assign("CURRENT_PAGE",VteSession::get('dup_nav_start'.$module)); //crmv@36508
if (VteSession::get('duplicates_'.$module) > 0){
	$ret_arr['noofrows'].=" ".getTranslatedString('LBL_SELECT_MERGECRITERIA_DUPLICATES').":".VteSession::get('duplicates_'.$module);
}
$smarty->assign("NOOFROWS",$ret_arr['noofrows']);
$smarty->assign("FIELD_NAMES",$fld_name);
$smarty->assign("ALL_VALUES",$fld_values);
if(isPermitted($req_module,'Delete','') == 'yes')
	$button_del = $app_strings['LBL_MASS_DELETE'];
$smarty->assign("DELETE",$button_del);

$smarty->assign("MOD", return_module_language($current_language,$req_module));
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("MODE",'view');
if(isset($_REQUEST['button_view'])) {
	$smarty->assign("VIEW",'true');
}
if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')
	$smarty->display("FindDuplicateAjax.tpl");
else
	$smarty->display('FindDuplicateDisplay.tpl');

?>