<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6288 */

global $currentModule, $current_user, $adb, $table_prefix, $theme, $mod_strings, $app_strings;

$category = getParentTab();
if (empty($homepage)) $smarty = new VteSmarty();	//crmv@102334

if (!isset($modhomeid)) $modhomeid = intval($_REQUEST['modhomeid']);	//crmv@141557

$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329
$viewid = $customView->getViewId($currentModule);
$customview_html = $customView->getCustomViewCombo($viewid);
$viewinfo = $customView->getCustomViewByCvid($viewid);

// Feature available from 5.1
if(method_exists($customView, 'isPermittedChangeStatus')) {
	// Approving or Denying status-public by the admin in CustomView
	$statusdetails = $customView->isPermittedChangeStatus($viewinfo['status']);

	// To check if a user is able to edit/delete a CustomView
	$edit_permit = $customView->isPermittedCustomView($viewid,'EditView',$currentModule);
	$delete_permit = $customView->isPermittedCustomView($viewid,'Delete',$currentModule);

	$smarty->assign("CUSTOMVIEW_PERMISSION",$statusdetails);
	$smarty->assign("CV_EDIT_PERMIT",$edit_permit);
	$smarty->assign("CV_DELETE_PERMIT",$delete_permit);
}
// END

$smarty->assign("CUSTOMVIEW_OPTION",$customview_html);
if ($_REQUEST['hide_cv_follow'] == '1') $smarty->assign('HIDE_CV_FOLLOW', '1');
if ($_REQUEST['hide_custom_links'] == '1') $smarty->assign('HIDE_CUSTOM_LINKS', '1');
$smarty->assign("OWNED_BY",getTabOwnedBy($currentModule));
//crmv@7634
if(isset($_REQUEST['lv_user_id'])) {
	VteSession::set('lv_user_id_'.$currentModule, $_REQUEST['lv_user_id']); // crmv@107328
} else {
	$_REQUEST['lv_user_id'] = VteSession::get('lv_user_id_'.$currentModule); // crmv@107328
}
$smarty->assign("LV_USER_PICKLIST",getUserOptionsHTML($_REQUEST['lv_user_id'],$currentModule,"",'KanbanView',$modhomeid));	//crmv@141557
//crmv@7634e

if($viewinfo['viewname'] == 'All') $smarty->assign('ALL', 'All');
$smarty->assign("VIEWID", $viewid);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('APP', $app_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule));
$smarty->assign('CATEGORY', $category);
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("LAST_PAGE_APPENDED", 0);

$kanbanView = KanbanView::getInstance($viewid);
if (!$kanbanView->getId()) {
	$smarty->assign('KANBAN_NOT_AVAILABLE', true);
} else {
	$smarty->assign('KANBAN_ARR', $kanbanView->getGrid($_REQUEST['lv_user_id']));
}

$smarty_template = 'KanbanView.tpl';
$smarty_ajax_template = 'KanbanGrid.tpl';

$sdk_custom_file = 'KanbanViewCustomisations';
if (isModuleInstalled('SDK')) {
    $tmp_sdk_custom_file = SDK::getFile($currentModule,$sdk_custom_file);
    if (!empty($tmp_sdk_custom_file)) {
    	$sdk_custom_file = $tmp_sdk_custom_file;
    }
}
@include("modules/$currentModule/$sdk_custom_file.php");

if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '')
	$smarty->display($smarty_ajax_template);
/* crmv@102334 */