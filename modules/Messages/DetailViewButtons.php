<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if ($_REQUEST['file'] == 'DetailViewButtons') {
	global $currentModule, $current_folder;
	
	if (!empty($_REQUEST['folder'])) $current_folder = $_REQUEST['folder'];	//crmv@79192
	
	$smarty = new VteSmarty();
	
	$record = vtlib_purify($_REQUEST['record']);
	$focus = CRMEntity::getInstance($currentModule);
	$retrieve = $focus->retrieve_entity_info($record, $currentModule, false);
	if (in_array($retrieve,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING'))) {
		exit;
	}
	$focus->id = $record;
	$folder = $focus->column_fields['folder'];
	
	$smarty->assign('ID', $record);
	$smarty->assign('MODULE', $currentModule);
	$smarty->assign('FOCUS', $focus);
	$smarty->assign('FOLDER', $folder);
	
	$blocks = getBlocks($currentModule,'detail_view','',$focus->column_fields);
}

$flag_block = array();
if (isset($blocks[$mod_strings['LBL_FLAGS']])) {
	foreach($blocks[$mod_strings['LBL_FLAGS']]['fields'] as $detail) {  // crmv@104568
		foreach($detail as $label => $data) {
			$flag_block[$data['fldname']] = $data;
			$flag_block[$data['fldname']]['label'] = $label;
		}
	}
}
$smarty->assign('FLAG_BLOCK', $flag_block);

$detailviewbuttonsperm = array(
	'edit'=>false,
	// crmv@187622
	'send'=>false,
	'schedule'=>false,
	// crmv@187622e
	'reply'=>false,
	'reply_all'=>false,
	'forward'=>false,
	'seen'=>false,
	'flagged'=>false,
	'move'=>false,
	'spam'=>false,	//crmv@46601
	'delete'=>false,
);
// crmv@187622
if ($focus->column_fields['folder'] == 'vteScheduled') {
	$detailviewbuttonsperm['send'] = true;
	$detailviewbuttonsperm['schedule'] = true;
	$detailviewbuttonsperm['delete'] = true;
// crmv@187622e
} elseif (in_array($current_folder,array('Shared','Links')) || $focus->column_fields['mtype'] == 'Link' || $current_user->id != $focus->column_fields['assigned_user_id']) {	//crmv@61173
	//crmv@44788
	$detailviewbuttonsperm['reply'] = true;
	$detailviewbuttonsperm['reply_all'] = true;
	$detailviewbuttonsperm['forward'] = true;
	//crmv@44788e
//crmv@79192
} elseif ($current_folder == 'Flagged') {
	$detailviewbuttonsperm['reply'] = true;
	$detailviewbuttonsperm['reply_all'] = true;
	$detailviewbuttonsperm['forward'] = true;
	$detailviewbuttonsperm['seen'] = true;
	$detailviewbuttonsperm['flagged'] = true;
//crmv@79192e
} else {
	$specialFolders = $focus->getSpecialFolders();
	if ($focus->column_fields['folder'] == $specialFolders['Drafts']) {
		$detailviewbuttonsperm['edit'] = true;
	} else {
		$detailviewbuttonsperm['reply'] = true;
		$detailviewbuttonsperm['reply_all'] = true;
		$detailviewbuttonsperm['forward'] = true;
	}
	if ($focus->column_fields['mtype'] == 'Webmail') {
		$detailviewbuttonsperm['seen'] = true;
		$detailviewbuttonsperm['flagged'] = true;
		$detailviewbuttonsperm['move'] = true;
		//crmv@46601
		$detailviewbuttonsperm['spam'] = true;
		if (empty($specialFolders['Spam'])) {
			$detailviewbuttonsperm['spam_status'] = 'configure';
		} elseif ($focus->column_fields['folder'] == $specialFolders['Spam']) {
			$detailviewbuttonsperm['spam_status'] = 'on';
		} else {
			$detailviewbuttonsperm['spam_status'] = 'off';
		}
		//crmv@46601e
	}
	//if(isPermitted($currentModule, 'Delete', $record) == 'yes') {
		$detailviewbuttonsperm['delete'] = true;
	//}
}
$smarty->assign('DETAILVIEWBUTTONSPERM',$detailviewbuttonsperm);
$smarty->assign('SPECIAL_FOLDERS', $specialFolders);

if ($_REQUEST['file'] == 'DetailViewButtons') {
	$smarty->display('modules/Messages/DetailViewButtons.tpl');
}
?>