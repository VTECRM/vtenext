<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43050 crmv@43864 crmv@56603 */

require_once('modules/Popup/Popup.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$from_module = vtlib_purify($_REQUEST['from_module']);
if ($from_module == 'Emails') $from_module = 'Messages';
$from_crmid = intval($_REQUEST['from_crmid']);
$mode = $_REQUEST['mode'];
$show_module = vtlib_purify($_REQUEST['show_module']);

$parentid = intval($_REQUEST['record']);
$contentid = $_REQUEST['contentid'];
$idlistReq = vtlib_purify($_REQUEST['idlist']);
$onlypeople = ($_REQUEST['onlypeople'] == true);
$showOnly = $_REQUEST['show_only'];
$relationId = intval($_REQUEST['relation_id']); // this is the relationid of the relation which opened the popup
$modules_list = array_filter(explode(',', $_REQUEST['modules_list']));
//crmv@153819
if ($_REQUEST['check_action_permissions'] === 'false') $check_action_permissions = false; else $check_action_permissions = true;
$force_show_module = ($_REQUEST['force_show_module'] === 'true');
//crmv@153819e

if (empty($from_module)) die('Module not specified');

$popup = Popup::getInstance();
$focus = CRMEntity::getInstance($from_module);

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('THEME', $theme);

// callback javascript functions to be called when a user click on save in create or link
// params are "module" and "crmid" [per link]
// TODO: mettere nella classe, e generalizzare, più parametri
$callback_link = vtlib_purify($_REQUEST['callback_link']);
$callback_create = vtlib_purify($_REQUEST['callback_create']);
$callback_close = vtlib_purify($_REQUEST['callback_close']);
$callback_addselected = vtlib_purify($_REQUEST['callback_addselected']); // crmv@126184
$callback_cancel = vtlib_purify($_REQUEST['callback_cancel']); // crmv@126184

if (empty($callback_link)) $callback_link = 'LPOP.link';
if (empty($callback_create)) $callback_create = 'LPOP.create';

if ($showOnly == 'create') {
	$defaultLinkAction = 'create';
	($callback_create == 'LPOP.convert') ? $pageTitle = getTranslatedString('LBL_CONVERT_ACTION','APP_STRINGS') : $pageTitle = getTranslatedString('LBL_CREATE','APP_STRINGS');	//crmv@44609
} else {
	$defaultLinkAction = 'link';
	$pageTitle = getTranslatedString('LBL_LINK_ACTION',$currentModule);
	if ($mode == 'linkdocument') {
		$pageTitle .= " ".getTranslatedString('LBL_ATTACHMENT');
	}
}

if ($from_module == 'Messages' && $mode != 'compose' && !empty($from_crmid)) {
	
	$focus->id = $from_crmid; //crmv@168655

} elseif (!empty($idlistReq)) {
	// get related ids from request (and related accounts/contacts)

	$idlist = array();
	$RM = RelationManager::getInstance();

	$idlistReq = array_filter(explode(';', $idlistReq));
	$others = array();
	foreach ($idlistReq  as $id) {
		if (strpos($id,'@') !== false) $id = substr($id,0,strpos($id,'@'));	//crmv@86304
		$mod = getSalesEntityType($id);
		if ($mod) {
			$others = array_merge($others, $RM->getRelatedIds($mod, $id, array('Accounts', 'Contacts')));
		}
	}

	$idlistReq = array_unique(array_merge($idlistReq, $others));

	foreach ($idlistReq  as $id) {
		$mod = getSalesEntityType($id);
		if ($mod) $idlist[$mod][] = $id;
	}

}

//crmv@153819
$linkMods = array();
if ($onlypeople) {
	$popAction = ($showOnly == 'create' ? 'create' : 'list');
	foreach ($focus->peopleModules as $m) {
		$linkMods[] = array('module'=>$m, 'action'=>$popAction);
	}
} else {
	$createMods = $popup->getCreateModules($from_module, $from_crmid, $mode, $check_action_permissions);
	
	if ($showOnly == 'create') {
		// modules with create
		foreach ($createMods as $m) {
			$linkMods[] = array('module'=>$m, 'action'=>'create');
		}
		
		if ($force_show_module && !empty($show_module) && !in_array($show_module,$createMods)) {
			$act = (in_array($m, $lMods) ? 'list' : 'create');
			$linkMods[] = array('module'=>$show_module, 'action'=>'create');
		}
	} else {
		// modules with select only or select and create
		$lMods = $popup->getLinkModules($from_module, $from_crmid, $mode, $check_action_permissions);
		if ($showOnly == 'link') {
			$allMods = $lMods;
		} else {
			$allMods =  $popup->getAllModules($from_module, $from_crmid, $mode, $check_action_permissions);
		}
		foreach ($allMods as $m) {
			$act = (in_array($m, $lMods) ? 'list' : 'create');
			$linkMods[] = array('module'=>$m, 'action'=>$act);
		}
		if ($force_show_module && !empty($show_module) && !in_array($show_module,$allMods)) {
			$act = (in_array($m, $lMods) ? 'list' : 'create');
			$linkMods[] = array('module'=>$show_module, 'action'=>$act);
		}
	}
}
//crmv@153819e

if (!empty($modules_list)) {
	foreach ($linkMods as $k=>$lmod) {
		if (!in_array($lmod['module'], $modules_list)) unset($linkMods[$k]);
	}
}

// now try to guess the relation id for the linked modules
$linkModsNames = array_map(function($m) {
	return $m["module"];
}, $linkMods);
if (!$RM) $RM = RelationManager::getInstance();
$rels = $RM->getRelations($from_module, null, $linkModsNames);
if ($rels) {
	foreach ($linkMods as $k=>&$lmod) {
		$foundRel = null;
		foreach ($rels as $rel) {
			if ($rel->getSecondModule() == $lmod['module']) {
				$foundRel = $rel;
				break;
			}
		}
		if ($foundRel && $foundRel->relationid > 0) {
			$lmod['relation_id'] = $foundRel->relationid;
		} else {
			$lmod['relation_id'] = 0;
		}
	}
}

$smarty->assign('LINK_MODULES', $linkMods);

$smarty->assign('BROWSER_TITLE', $pageTitle);
$smarty->assign('PAGE_TITLE', $pageTitle);
$smarty->assign('HEADER_Z_INDEX', 10);

//crmv@46678 crmv@65506
if (empty($show_module) && !empty($linkMods)) {
	if (count($linkMods) == 1 || (count($linkMods)>1 && PerformancePrefs::getBoolean('POPUP_AUTOSELECT_MODULE',true))) {
		$show_module = $linkMods[0]['module'];
	}
}
//crmv@46678e crmv@65506e

$extraInputs = array(
	'show_module' => $show_module,
	'modules_list' => Zend_Json::encode($modules_list),
	'popup_mode' => $mode,
	'from_module' => $from_module,
	'from_crmid' => $from_crmid,
	'original_email' => '',	//crmv@168655
	'original_name' => '', //crmv@168655
	'uikey_from' => $_REQUEST['uikey'], // crmv@43050 for conversations: TODO: generalize
	'relevant_ids' => Zend_Json::encode($idlist),
	'callback_link' => $callback_link,
	'callback_create' => $callback_create,
	'callback_close' => $callback_close,
	'callback_addselected' => $callback_addselected, // crmv@126184
	'callback_cancel' => $callback_cancel, // crmv@126184
	'contentid' => $contentid,
	'default_link_action' => $defaultLinkAction,
	'extra_popup_params' => $_REQUEST['extra_popup_params'], // crmv@44323 - you can use this to pass your parameters to the popup
	'extra_list_params' => $_REQUEST['extra_list_params'], // crmv@44323
	'sdk_view_all' => $_REQUEST['sdk_view_all'],	//crmv@48964
	'original_relation_id' => $relationId,	// relation_id of the opener related list
);

// and now attachment block (if permitted and not from direct attachment link)

if ($from_module == 'Messages' && $contentid == '' && isPermitted('Documents', 'EditView') == 'yes' && $focus->haveAttachments($from_crmid)) {
	$attachments = $focus->getAttachments();
	$smarty->assign('ATTACHMENTS', $attachments);
}

include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$hdrcustomlink_params = Array('MODULE'=>$from_module);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);
$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);

// crmv@43050
// add extra js default js for the parent module
$extraJs = array();
if ($from_module) {
	$path = "modules/$from_module/$from_module.js";
	if (file_exists($path)) {
		$extraJs[] = $path;
	}
}

$smarty->assign('EXTRA_JS', array_unique($extraJs));
$smarty->assign('EXTRA_INPUTS', $extraInputs);
// crmv@43050e

$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS',Zend_Json::encode($JSGlobals));

$smarty->display('modules/Popup/Popup.tpl');
?>