<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$smarty->assign('FOLDERID', $focus->column_fields['folderid']);

//needed when creating a new note with default values passed in
if (isset($_REQUEST['contact_name']) && is_null($focus->contact_name)) {
	$focus->contact_name = $_REQUEST['contact_name'];
}
if (isset($_REQUEST['contact_id']) && is_null($focus->contact_id)) {
	$focus->contact_id = $_REQUEST['contact_id'];
}
if (isset($_REQUEST['opportunity_name']) && is_null($focus->parent_name)) {
	$focus->parent_name = $_REQUEST['opportunity_name'];
}
if (isset($_REQUEST['opportunity_id']) && is_null($focus->parent_id)) {
	$focus->parent_id = $_REQUEST['opportunity_id'];
}
if (isset($_REQUEST['account_name']) && is_null($focus->parent_name)) {
	$focus->parent_name = $_REQUEST['account_name'];
}
if (isset($_REQUEST['account_id']) && is_null($focus->parent_id)) {
	$focus->parent_id = $_REQUEST['account_id'];
}

$filename = $focus->column_fields['filename'];
$folderid = $focus->column_fields['folderid'];
$filestatus = $focus->column_fields['filestatus'];
$filelocationtype = $focus->column_fields['filelocationtype'];

$fileattach = "select attachmentsid from ".$table_prefix."_seattachmentsrel where crmid = ?";
$res = $adb->pquery($fileattach,array($focus->id));
$fileid = $adb->query_result($res,0,'attachmentsid');

if ($filelocationtype == 'I' || $filelocationtype == 'B') {
	$pathQuery = $adb->pquery("select path from ".$table_prefix."_attachments where attachmentsid = ?",array($fileid));
	$filepath = $adb->query_result($pathQuery,0,'path');
} else {
	$filepath = $filename;
}

$smarty->assign("FILEID",$fileid);
$smarty->assign("FILENAME",$filename);	//crmv@46622
$smarty->assign("FILE_STATUS",$filestatus);
$smarty->assign("DLD_TYPE",$filelocationtype);
$smarty->assign("NOTESID",$focus->id);
$smarty->assign("FOLDERID",$folderid);
$smarty->assign("DLD_PATH",$filepath);

$flag = 0;
if (!empty($focus->column_fields['filename']) && $focus->column_fields['filename']) {
	$flag = 1;
}
if ($flag == 1) {
	$smarty->assign("FILE_EXIST","yes");
} elseif($flag == 0) {
	$smarty->assign("FILE_EXIST","no");
}

if(is_admin($current_user)){
 	$smarty->assign("CHECK_INTEGRITY_PERMISSION","yes");
    $smarty->assign("ADMIN","yes");
}
?>