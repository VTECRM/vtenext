<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146670 */

global $adb, $table_prefix;
global $mod_strings,$app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;

require_once('modules/Settings/ExtWSConfig/ExtWSUtils.php');

if ($_REQUEST['ajax'] == 1) {
	require('modules/Settings/ExtWSConfig/ExtWSAjax.php');
	return;
}

$mode = $_REQUEST['mode'] ?: '';
$extwsid = intval($_REQUEST['extwsid']);

$EWSU = new ExtWSUtils();

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

if ($mode == 'create' || $mode == 'edit') {

	$info = $EWSU->getWSInfo($extwsid);

	$smarty->assign("WSINFO", $info);
	$smarty->assign("WSTYPES", $EWSU->ws_types);
	$smarty->assign("WSMETHODS", $EWSU->ws_methods);
	
} elseif ($mode == 'save') {

	$data = $EWSU->prepareDataFromRequest();

	if ($extwsid > 0) {
		$r = $EWSU->updateWS($extwsid, $data);
	} else {
		$r = $EWSU->insertWS($data);
		$extwsid = $r;
	}
	
	if ($r) {
		$mode = '';
		$list = $EWSU->getList();
		$smarty->assign("WSLIST", $list);
	} else {
		// error
		// TODO
	}
	
	header('Location: index.php?module=Settings&action=ExtWSConfig&parentTab=Settings');
	die();
	
} elseif ($mode == 'delete') {
	$error = false;
	
	$info = $EWSU->getWSInfo($extwsid);

	if (empty($info)) {
		$error = getTranslatedString('LBL_NO_RECORD');
	} else {
		$r = $EWSU->deleteWS($extwsid);
	}
	
	if (!empty($error)) {
		$smarty->assign("LIST_ERROR", $error);
	}
	
	// and display the list
	$list = $EWSU->getList();
	$smarty->assign("WSLIST", $list);
} else {
	$list = $EWSU->getList();
	$smarty->assign("WSLIST", $list);
}

$smarty->assign("MODE", $mode);
$smarty->assign("EXTWSID", $extwsid);

$smarty->display('Settings/ExtWSConfig/ExtWS.tpl');