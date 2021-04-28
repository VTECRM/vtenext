<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
global $table_prefix;
global $current_user;
$notesid = vtlib_purify($_REQUEST['record']);
//crmv@16312 crmv@193042
if (isPermitted('Documents', 'DetailView', $notesid) == 'yes') {
	$dbQuery = "select filename,folderid,filestatus from ".$table_prefix."_notes where notesid= ? ";
	$result = $adb->pquery($dbQuery,array($notesid));
	$folderid = $adb->query_result($result,0,'folderid');
	$filename = $adb->query_result($result,0,'filename');
	$filestatus = $adb->query_result($result,0,'filestatus');

	$fileidQuery = "select attachmentsid from ".$table_prefix."_seattachmentsrel where crmid = ?";
	$fileidRes = $adb->pquery($fileidQuery,array($notesid));
	$fileid = $adb->query_result($fileidRes,0,'attachmentsid');

	$pathQuery = $adb->pquery("select path from ".$table_prefix."_attachments where attachmentsid = ?",array($fileid));
	$filepath = $adb->query_result($pathQuery,0,'path');

	$fileinattachments = $root_directory.$filepath.$fileid.'_'.$filename;
	if(!file($fileinattachments))$fileinattachments = $root_directory.$filepath.$fileid."_".$filename;

	$newfileinstorage = $root_directory."/storage/$fileid-".$filename;

	if($filestatus == 1){
		copy($fileinattachments,$newfileinstorage);
	}
//crmv@16312 end
//crmv@22139
//echo "<script>window.history.back();</script>";
//exit();
	print_r($fileinattachments.','.$newfileinstorage);
//crmv@22139e
}
// crmv@193042e
