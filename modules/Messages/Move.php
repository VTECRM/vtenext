<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $currentModule, $table_prefix;
$mode = vtlib_purify($_REQUEST['mode']);
$account = vtlib_purify($_REQUEST['account']);
$folder = vtlib_purify($_REQUEST['folder']);
$record = vtlib_purify($_REQUEST['record']);
$current_folder = vtlib_purify($_REQUEST['current_folder']);
$thread = vtlib_purify($_REQUEST['thread']);
$focus = CRMEntity::getInstance($currentModule);

if (in_array($_REQUEST['view'],array('display','create'))) {
	$smarty = new VteSmarty();
	if (!empty($record)) {
		$account = getSingleFieldValue($table_prefix.'_messages', 'account', 'messagesid', $record);
	}
	// crmv@201190
	if ($account == 'all') {
		$accounts = array();
		$ids = getListViewCheck($currentModule);
		if (!empty($ids) && is_array($ids)) {
			foreach($ids as $id) {
				$_account = getSingleFieldValue($table_prefix.'_messages', 'account', 'messagesid', $id);
				(!isset($accounts[$_account])) ? $accounts[$_account] = 1 : $accounts[$_account]++;
			}
		}
		if (count($accounts) > 1) {
			die(getTranslatedString('LBL_MASS_MOVE_MULTIPLE_ACCOUNT_ERR','Messages'));
		} else {
			$account = $_account;
		}
	}
	// crmv@201190e
	$focus->setAccount($account);
	$focus->getZendMailStorageImap();
	if ($_REQUEST['view'] == 'display') {
		$view = 'move';
	} else {
		$view = $_REQUEST['view'];
	}
	$smarty->assign('FOLDERS', $focus->getFoldersList($view,$current_folder,$mode));
	$smarty->assign('VIEW', $view);
	$smarty->assign('MODE', $mode);
	$smarty->assign('ID', $record);
	$smarty->assign('FOCUS', $focus);
	$smarty->display("modules/Messages/Folders.tpl");
} else {
	if ($mode == 'single') {
		$focus->id = $record;
		$focus->retrieve_entity_info($record, $currentModule);
		$focus->setAccount($focus->column_fields['account']);
		$focus->moveMessage($folder);
	} elseif ($mode == 'mass') {
		$focus->massMoveMessage($account,$current_folder,$folder);
		$viewid = vtlib_purify($_REQUEST['viewname']);
		$return_module = $currentModule;
		$return_action = 'ListView';
		$url = getBasic_Advance_SearchURL();
		$rstart = "&start=".getLVSDetails($currentModule,$viewid,'start').'&load_all=true';	//crmv@48307
		$parenttab = getParentTab();
		header("location: index.php?module=$return_module&action={$return_module}Ajax&file=$return_action&ajax=true&parenttab=$parenttab$rstart&account=$account&folder=$current_folder&thread=$thread");
	} elseif ($mode == 'folders') {
		if ($focus->folderMove($account,$current_folder,$folder) === false) {
			die('FAILED');
		}
	} elseif ($mode == 'create') {
		if ($focus->folderCreate($account,$folder,$current_folder) === false) {
			die('FAILED');
		}
	}
	die('SUCCESS');
}
?>