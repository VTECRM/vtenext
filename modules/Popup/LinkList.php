<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43864 */
require_once('include/ListView/SimpleListView.php');

$from_module = vtlib_purify($_REQUEST['from_module']);
$from_crmid = intval($_REQUEST['from_crmid']);
$module = vtlib_purify($_REQUEST['mod']);
$mode = $_REQUEST['popup_mode'];
$relationId = intval($_REQUEST['relation_id']); // crmv@56603

$callback_link = vtlib_purify($_REQUEST['callback_link']);
$callback_cancel = vtlib_purify($_REQUEST['callback_cancel']); // crmv@126184
$callback_addselected = vtlib_purify($_REQUEST['callback_addselected']); // crmv@126184
if (empty($callback_link)) $callback_link = 'LPOP.link';

$popup = Popup::getInstance();

$createMods = $popup->getCreateModules($from_module, $from_crmid, $mode);
$canCreate = ($mode != 'compose' && in_array($module, $createMods)); // crmv@43050

if ($mode = 'linkrecord' && empty($from_crmid)) $canCreate = false;

if ($module == 'Recents') {
	require_once('data/Tracker.php');
	
	global $current_user;
	
	$smarty = new VteSmarty();
	
	$tracker = new Tracker();
	$history = $tracker->get_recently_viewed($current_user->id);
	
	$linkMods = $popup->getLinkModules($from_module, $from_crmid, $mode);
	
	$excludeIds = array();
	
	if ($from_crmid > 0) {
		$rm = RelationManager::getInstance();
		$excludeIds = $rm->getRelatedIds(getSalesEntityType($from_crmid), $from_crmid, array());
	}
	
	$history = array_filter($history, function($h) use ($linkMods, $excludeIds) {
		return in_array($h['module_name'], $linkMods) && !in_array($h['crmid'], $excludeIds);
	});
	
	$smarty->assign('TITLE', false);
	$smarty->assign('HISTORY', $history);
	$smarty->assign('SELECT_FUNC', $callback_link);
	
	$smarty->display("FastLastViewed.tpl");
	exit();
}

// crmv@56603
$Slv = SimpleListView::getInstance($module); // fake module, but works as well
$Slv->entriesPerPage = 20;
$Slv->showSuggested = ($from_module == 'Messages');
$Slv->showCreate = $canCreate;
$Slv->showCheckboxes = true;
if ($from_module == 'ModComments') $Slv->showCheckboxes = false;	//crmv@58208
if ($Slv->showSuggested && !empty($from_crmid)) {
	$fromFocus = CRMEntity::getInstance($from_module);
	$fromFocus->retrieve_entity_info($from_crmid, $from_module);
	$fromFocus->id = $from_crmid;
	//crmv@168655
	$sugg = $fromFocus->getSuggestedRelIds();
	$suggestedIds = $sugg['idlist'];
	//crmv@168655e
	$Slv->setSuggestedIds($suggestedIds);
	$Slv->setParentId($from_crmid);
	$Slv->hideLinkedRecords = true;
	echo '<script type="text/javascript">jQuery(\'#relevant_ids\').val(\''.Zend_Json::encode($suggestedIds).'\');</script>'; //crmv@168655
} elseif (!empty($from_crmid)) {
	$Slv->setParentId($from_crmid);
}
if ($from_module == 'Messages' && $_REQUEST['contentid'] > 0) $Slv->setParentId(null); // crmv@159305
$Slv->selectFunction = 'LPOP.select';
$Slv->createFunction = 'LPOP.showCreatePanel';
$Slv->addSelectedFunction = 'LPOP.linkSelected';

// crmv@126184
if ($callback_addselected) {
	$Slv->addSelectedFunction = $callback_addselected;
}
if ($callback_cancel) {
	$Slv->showCancel = true;
	$Slv->cancelFunction = $callback_cancel;
}
// crmv@126184e

// hide the already linked records
if ($relationId > 0) {
	$Slv->setRelationId($relationId);
	$Slv->hideLinkedRecords = true;
}
// crmv@56603e

// crmv@44323
// query alter functions
$for_focus = CRMEntity::getInstance($from_module);
if (method_exists($for_focus, 'PopupQueryChange')) {
	$Slv->queryChangeFunction = array($from_module, 'PopupQueryChange'); // crmv@45949
}
if (isset($_REQUEST['extra_list_params'])) {
	$Slv->extraInputs = $_REQUEST['extra_list_params'];
}
// crmv@44323e

//crmv@48964
$sdk_file = SDK::getPopupQuery('related',$from_module,$module);
if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {
	$Slv->sdkPopupQuery = $sdk_file;
}
//crmv@48964e

$list = $Slv->render();
echo $list;
?>