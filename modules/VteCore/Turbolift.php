<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43864 */

require_once('modules/Popup/Popup.php');
global $app_strings, $mod_strings, $theme, $currentModule;

$record = $_REQUEST['record'];
$popup = Popup::getInstance();

// if included from DetailView don't initialize anything
if (!isset($smarty)) {
	$smarty = new VteSmarty();

	$smarty->assign('APP', $app_strings);
	$smarty->assign('MOD', $mod_strings);
	$smarty->assign('MODULE', $currentModule);
	$smarty->assign('THEME', $theme);
}

if (empty($focus)) {
	$focus = CRMEntity::getInstance($currentModule);
	if (!empty($record)) {
		$focus->id = $record;
		$focus->retrieve_entity_info($record, $currentModule);
		$smarty->assign('ID', $record);
	}
}

// show buttons for calendar?
$showCalButtons = false;
$showCreateButton = true;
$showLinkButton = true;
//crmv@44609
$showConvertButton = false;
if (isPermitted($currentModule, 'Delete', $record) == 'yes' && in_array($currentModule,array('MyNotes'))) {
	$showConvertButton = true;
}
//crmv@44609e

if ($currentModule == 'Calendar') {
	$showCalButtons = false;
	$showCreateButton = false;
	$showLinkButton = false;
} elseif ($currentModule == 'Emails') {
	$showCalButtons = false;
	$showCreateButton = false;
	$showLinkButton = true;
} else {
	$calendarLinkModules = getCalendarRelatedToModules(false);	//crmv@52561
	if (is_array($calendarLinkModules) && in_array($currentModule, $calendarLinkModules)) {
		$showCalButtons = true;
	}
	$showCreateButton = true;
	$showLinkButton = true;

	// check for create/link modules
	$createMods = $popup->getCreateModules($currentModule, $record);
	//crmv@44609
	if (empty($createMods)) {
		$showCreateButton = false;
		$showConvertButton = false;
	}
	//crmv@44609e
}

$smarty->assign('SHOW_TURBOLIFT_CAL_BUTTONS', $showCalButtons);
$smarty->assign('SHOW_TURBOLIFT_CREATE_BUTTON', $showCreateButton);
$smarty->assign('SHOW_TURBOLIFT_LINK_BUTTON', $showLinkButton);
$smarty->assign('SHOW_TURBOLIFT_CONVERT_BUTTON', $showConvertButton);	//crmv@44609
$smarty->assign('SHOW_TURBOLIFT_BACK_BUTTON', $_REQUEST['show_turbolift_back_button']);
$href_target_location = 'location.href';
if ($_REQUEST['inOpenPopup'] == 'yes') {
	$href_target_location = 'window.top.location.href';
}
$smarty->assign('TURBOLIFT_HREF_TARGET_LOCATION',$href_target_location);

$rm = RelationManager::getInstance();
$excludedMods = array('ModComments','MyNotes','Events');	//crmv@51605
if ($currentModule == 'Calendar') $excludedMods[] = 'Contacts'; // otherwise if more than 1 contact there's no related list to show

// crmv@104568
$panelid = getCurrentPanelId($currentModule);
$relations = $rm->getTurboliftRelations($focus, $currentModule, $focus->id, null, $excludedMods, false, $panelid);
// crmv@104568e

$smarty->assign('RELATIONS', $relations['turbolift']);

if (!empty($relations['pin'])) {
	$smarty->assign("RELATEDLISTS", $relations['pin']);

	require_once('include/ListView/RelatedListViewSession.php');
	RelatedListViewSession::removeRelatedModuleFromSession(null,null);
	foreach($relations['pin'] as $label => $related) {
		RelatedListViewSession::addRelatedModuleToSession($related['relationId'],$label);
	}
	$open_related_modules = RelatedListViewSession::getRelatedModulesFromSession();
	$smarty->assign("SELECTEDHEADERS", $open_related_modules);
}

//crmv@54375
if (!empty($_REQUEST['from_module'])) {
	$result = $adb->pquery("SELECT label FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = ?",array(getTabid($_REQUEST['module']),getTabid($_REQUEST['from_module'])));
	if ($result && $adb->num_rows($result) > 0) {
		$smarty->assign("OPENRELATEDLIST", "tl_".$_REQUEST['module']."_".str_replace(' ','',$adb->query_result($result,0,'label')));
	}
}
//crmv@54375e

//crmv@62394
require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
if (CalendarTracking::isEnabledForTurbolift($currentModule)) {
	$smarty->assign('SHOW_TURBOLIFT_TRACKER', true);
	$trackerId = $focus->id;
	if ($currentModule == 'Emails') {
		$smarty->assign('TRACKER_FOR_COMPOSE', true);
		$trackerId = 0;
	}
	$smarty->assign('TRACKER_DATA', CalendarTracking::getTrackerData($currentModule, $trackerId));

}
//crmv@62394e

if ($_REQUEST['ajaxaction'] == 'show') {
	//crmv@57221
	$CU = CRMVUtils::getInstance();
	$oldStyle = $CU->getConfigurationLayout('old_style');
	($oldStyle) ? $template = 'TurboliftRelationsOldStyle.tpl' : $template = 'TurboliftRelations.tpl';
	$smarty->display($template);
	//crmv@57221e
}
?>