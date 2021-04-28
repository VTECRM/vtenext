<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* editor di picklist collegate - crmv@30528 */
require_once('modules/SDK/examples/uitypePicklist/300Utils.php');

global $mod_strings, $app_strings, $app_list_strings;
global $current_language, $currentModule, $theme;
global $adb, $table_prefix;

$smarty = new VteSmarty();

$smarty->assign("MODULE",$fld_module);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("THEME", $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/"); // crmv@97692
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

linkedListInitTables();

if ($_REQUEST['subaction'] == 'getlinktable') {
	$id1 = vtlib_purify($_REQUEST['picklist1']);
	$id2 = vtlib_purify($_REQUEST['picklist2']);

	$modname = vtlib_purify($_REQUEST['modname']);

	// get fields name
	$res = $adb->pquery("select name,fieldname,fieldlabel from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid where {$table_prefix}_tab.name = ? and fieldname = ?", array($modname, $id1));
	if ($res) {
		$mod = $adb->query_result($res, 0, 'name');
		$picklist1 = $adb->query_result($res, 0, 'fieldname');
		$picklist1_label = getTranslatedString($adb->query_result($res, 0, 'fieldlabel'), $mod);
	}
	$res = $adb->pquery("select name,fieldname,fieldlabel from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid where {$table_prefix}_tab.name = ? and fieldname = ?", array($modname, $id2));
	if ($res) {
		$mod = $adb->query_result($res, 0, 'name');
		$picklist2 = $adb->query_result($res, 0, 'fieldname');
		$picklist2_label = getTranslatedString($adb->query_result($res, 0, 'fieldlabel'), $mod);
	}

	$rr = linkedListGetAllOptions($picklist1, $picklist2, $mod);

	$smarty->assign("PICKMATRIX", $rr);
	$smarty->assign("PICKLIST1", array('name'=>$picklist1, 'label'=>$picklist1_label));
	$smarty->assign("PICKLIST2", array('name'=>$picklist2, 'label'=>$picklist2_label));

	$smarty->display("Settings/LinkedPicklistTable.tpl");

} elseif ($_REQUEST['subaction'] == 'savepicklist') {

	$picklist1 = vtlib_purify($_REQUEST['picklist1']);
	$picklist2 = vtlib_purify($_REQUEST['picklist2']);
	$mod = vtlib_purify($_REQUEST['modname']);

	$flatmatrix = array_map(intval, explode(',',$_REQUEST['matrix']));

	if (count($flatmatrix) == 0) die('ERROR::NOMATRIX');

	// assicuro che l'uitype esista
	$pldir = 'modules/SDK/examples/uitypePicklist/';
	SDK::setUitype(300,$pldir.strval(300).'.php',$pldir.strval(300).'.tpl', $pldir.strval(300).'.js');
	Vtecrm_Link::addLink(getTabid('SDK'),'HEADERSCRIPT','SDKUitype', $pldir.'300Utils.js');


	// inizializzo e controllo cicli
	$cycles = checkCyclicPaths($picklist1, $picklist2, $mod);

	if ($cycles) die('ERROR::'.getTranslatedString('LBL_ERROR_CYCLE', 'Settings'));

	// converto l'uitype a 300
	$adb->pquery("update {$table_prefix}_field set uitype = 300 where fieldname in (?,?) AND tabid = ?", array($picklist1, $picklist2, getTabId($mod))); // crmv@130593

	// costruisco array per l'inserimento
	$allopt = linkedListGetAllOptions($picklist1, $picklist2, $mod);

	// transpose matrix
	$srclen = count($allopt['matrix']);
	foreach ($allopt['matrix'] as $src=>$destarray) {
		$destlen = count($destarray);
		break;
	}
	$newarr = array();
	for ($j=0; $j<$srclen; ++$j) {
		for ($i=0; $i<$destlen; ++$i) {
			$newarr[] = $flatmatrix[$i*$srclen + $j];
		}
	}
	$flatmatrix = $newarr;
	
	$i=0;
	linkedListDeleteLink($picklist1, $mod, $picklist2); // reset connections
	foreach ($allopt['matrix'] as $src=>$destarray) {
		$destlinks = array();
		foreach ($destarray as $dest=>$destval) {
			if ($flatmatrix[$i++] == 1) $destlinks[] = $dest;
		}
		// crmv@97692
		// allow empty set, so i can have a picklist linked to nothing
		//if (count($destlinks) > 0) {
			linkedListAddLink($picklist1, $picklist2, $mod, $src, $destlinks);
		//}
		// crmv@97692e
	}

} elseif ($_REQUEST['subaction'] == 'unlinkpicklist') {

	$picklist1 = vtlib_purify($_REQUEST['picklist1']);
	$picklist2 = vtlib_purify($_REQUEST['picklist2']);
	$mod = vtlib_purify($_REQUEST['modname']);
	$tabid = getTabid($mod);

	linkedListDeleteLink($picklist1, $mod, $picklist2); // reset connections

	// cambia il tipo se la picklist non ha altre subordinate
	// TODO: come faccio a sapere se rimetterla a 15 o 16 ?
	$res = $adb->pquery("select picksrc from {$table_prefix}_linkedlist where module = ? and picksrc = ?", array($mod, $picklist1));
	if ($res && $adb->num_rows($res) == 0) {
		$adb->pquery("update {$table_prefix}_field set uitype = 15 where tabid = ? and fieldname = ?", array($tabid, $picklist1));
	}
	$res = $adb->pquery("select picksrc from {$table_prefix}_linkedlist where module = ? and picksrc = ?", array($mod, $picklist2));
	if ($res && $adb->num_rows($res) == 0) {
		$adb->pquery("update {$table_prefix}_field set uitype = 15 where tabid = ? and fieldname = ?", array($tabid, $picklist2));
	}

} else {
	$plist = getAllPicklists();
	$modules = array();
	foreach ($plist as $module => $list) {
		$modules[$module] = getTranslatedString($module, $module);
	}
	asort($modules);
	$conn = getConnections('All');
	$smarty->assign("PLISTS", $plist);
	$smarty->assign("MODULES", $modules);
	$smarty->assign("PLIST_CONNECTIONS", $conn);
	$smarty->assign("MODULE", vtlib_purify($_REQUEST['moduleName']));
	$smarty->display("Settings/LinkedPicklist.tpl");
}