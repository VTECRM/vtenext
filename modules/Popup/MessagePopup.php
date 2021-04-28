<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42752 crmv@43050 */

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('THEME', $theme);

$mode = $_REQUEST['mode'];

$parentid = intval($_REQUEST['record']);
$contentid = $_REQUEST['contentid'];
$idlistReq = vtlib_purify($_REQUEST['idlist']);
$onlypeople = ($_REQUEST['onlypeople'] == true);
$showOnly = $_REQUEST['show_only'];

// callback javascript functions to be called when a user click on save in create or link
// params are "module" and "crmid" [per link]
// TODO: mettere nella classe, e generalizzare, più parametri
$callback_link = vtlib_purify($_REQUEST['callback_link']);
$callback_create = vtlib_purify($_REQUEST['callback_create']);

// crmv@43050
if ($parentid > 0 || $mode == 'compose') {
	$parentModule = getSalesEntityType($parentid);
	if ($parentModule == 'Messages' || $mode == 'compose') {
		$messageid = $parentid;
		if (empty($callback_link)) $callback_link = 'popupClickLinkModule';
		if (empty($callback_create)) $callback_create = 'popupCreateSave';
	}
}
// crmv@43050e

if (empty($parentModule) && !empty($_REQUEST['parent_module'])) $parentModule = $_REQUEST['parent_module']; // crmv@43448

$smarty->assign('PARENT_ID', $parentid);
$smarty->assign('PARENT_MODULE', $parentModule);
$smarty->assign('MESSAGEID', $messageid);
$smarty->assign('CONTENTID', $contentid);
$smarty->assign('CALLBACK_LINK', $callback_link);
$smarty->assign('CALLBACK_CREATE', $callback_create);

if ($showOnly == 'create') {
	$smarty->assign('DEFAULT_LINK_ACTION', 'create');
	$pageTitle = getTranslatedString('LBL_CREATE','APP_STRINGS');
} else {
	$smarty->assign('DEFAULT_LINK_ACTION', 'link');
	$pageTitle = getTranslatedString('LBL_LINK_ACTION',$currentModule);
	if ($mode == 'linkdocument') {
		$pageTitle .= " ".getTranslatedString('LBL_ATTACHMENT');
	}
}

$emails = array();

$focus = CRMEntity::getInstance($currentModule);

if ($mode != 'compose' && !empty($messageid)) {
	// get related ids from messageid

	// retrieve linked ids (use recipients for sent, sender/cc for inbox

	$focus->retrieve_entity_info($messageid, $currentModule, false);
	$focus->id = $messageid;

	// retrieve name and email
	$folder = $focus->column_fields['folder'];
	$specialFolders = $focus->getSpecialFolders();
	if (in_array($folder,array($specialFolders['Sent'],$specialFolders['Drafts']))) {
		$emails = array_filter(array_map('trim', explode(',', $focus->column_fields['mto'])));
		$emails = array_merge($emails, array_filter(array_map('trim', explode(',', $focus->column_fields['mcc']))));
		$name = trim(trim($focus->column_fields['mto_n']),'"');
	} else {
		$emails = array_filter(array_map('trim', explode(',', $focus->column_fields['mfrom'])));
		$emails = array_merge($emails, array_filter(array_map('trim', explode(',', $focus->column_fields['mcc']))));
		$name = trim(trim($focus->column_fields['mfrom_n']),'"');
	}

	// remove myself from emails - REMOVED
	/*
	$my_addresses[] = getSingleFieldValue($table_prefix.'_users', 'email1', 'id', $current_user->id);
	$my_addresses[] = getSingleFieldValue($table_prefix.'_users', 'email2', 'id', $current_user->id);
	$emails = array_diff($emails, $my_addresses);
	*/

	$email = $emails[0];

	if (empty($email) || $email == 'undisclosed-recipients:;') {
		die('Empty Email');
	}

	// use all possible emails to retrieve entitities, and also use a deep search
	$idlist = array();
	foreach ($emails as $em) {
		$list = $focus->getEntitiesFromEmail($em);
		foreach ($list as $mod => $ids) {
			if (!is_array($idlist[$mod])) {
				$idlist[$mod] = $ids;
			} else {
				$idlist[$mod] = array_unique(array_merge($idlist[$mod], $ids));
			}
		}
	}

} elseif (!empty($idlistReq)) {
	// get related ids from request (and related accounts/contacts)

	$idlist = array();
	$RM = RelationManager::getInstance();

	$idlistReq = array_filter(explode(';', $idlistReq));
	$others = array();
	foreach ($idlistReq  as $id) {
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

if ($onlypeople) {
	$linkMods = $focus->peopleModules;
} else {
	if ($showOnly == 'create') {
		$linkMods = $focus->getPopupCreateModules($mode);
	} else {
		$linkMods = $focus->getPopupLinkModules($mode);
	}
}

$smarty->assign('BROWSER_TITLE', $pageTitle);
$smarty->assign('PAGE_TITLE', $pageTitle);

$smarty->assign('POPUP_MODE', $mode);
$smarty->assign('LINK_MODULES', $linkMods);
$smarty->assign('RELEVANT_IDS', Zend_Json::encode($idlist));
$smarty->assign('UIKEY_FROM', $_REQUEST['uikey']); // crmv@43050 for conversations: TODO: generalize
$smarty->assign('ORIGINAL_EMAIL', $email);
$smarty->assign('ORIGINAL_NAME', $name);
$smarty->assign('HEADER_Z_INDEX', 10);

// and now attachment block (if permitted and not from direct attachment link)

if ($contentid == '' && isPermitted('Documents', 'EditView') == 'yes' && $focus->haveAttachments($messageid)) {
	$attachments = $focus->getAttachments();
	$smarty->assign('ATTACHMENTS', $attachments);
}

// crmv@43050
// add extra js default js for the parent module
$extraJs = array();
$extraJs[] = 'modules/Messages/Messages.js'; // because all the functions are there TODO: move them away
if ($parentModule) {
	$path = "modules/$parentModule/$parentModule.js";
	if (file_exists($path)) {
		$extraJs[] = $path;
	}
}

$smarty->assign('EXTRA_JS', array_unique($extraJs));
// crmv@43050e

$smarty->display('modules/Messages/MessagePopup.tpl');
?>