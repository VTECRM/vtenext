<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@62414 */

include_once('include/utils/utils.php');
global $currentModule,$adb, $table_prefix,$root_directory,$current_user;

$record = vtlib_purify($_REQUEST['record']);
$contentid = vtlib_purify($_REQUEST['contentid']);

$return_array = array('success'=>false,'savepath'=>'');
$tmp_folder = "cache/upload/";
$tmp_fullpath = $root_directory.$tmp_folder;

$sql = "select n.notesid,fa.attachmentsid,fa.name,fa.path
from {$table_prefix}_messages_attach a 
inner join {$table_prefix}_seattachmentsrel s on s.crmid = a.document
inner join {$table_prefix}_notes n on n.notesid = a.document
inner join {$table_prefix}_crmentity e on e.crmid = n.notesid
inner join {$table_prefix}_attachments fa ON s.attachmentsid = fa.attachmentsid
where deleted = 0 and messagesid = ? and contentid = ? and coalesce(a.document,'') <> ''"; // crmv@198701
$params = Array($record,$contentid);
$res = $adb->pquery($sql,$params);
if ($res && $adb->num_rows($res)>0){
	// crmv@198701
	$notesid = intval($adb->query_result_no_html($res,0,'notesid'));
	$return_array['savepath'] = $notesid;
	// crmv@198701e
	$return_array['success'] = true;
	echo Zend_Json::encode($return_array);
	exit;
}

$focus = CRMEntity::getInstance($currentModule);
$focus->retrieve_entity_info($record,$currentModule);
$uid = $focus->column_fields['xuid'];
$accountid = $focus->column_fields['account'];

$result = $adb->pquery("select userid from {$table_prefix}_messages_account where id = ?", array($accountid));
if ($result && $adb->num_rows($result) > 0) {
	$userid = $adb->query_result($result,0,'userid');

	$focus->setAccount($accountid);
	$focus->getZendMailStorageImap($userid);
	$focus->selectFolder($focus->column_fields['folder']);
	
	$messageId = $focus->getMailResource()->getNumberByUniqueId($uid);
	$message = $focus->getMailResource()->getMessage($messageId);
	$parts = $focus->getMessageContentParts($message,$id,true);	//crmv@59492
	if (!empty($parts['other'][$contentid])) {
		$content = $parts['other'][$contentid];
		$str = $content['content'];
		$str = $focus->decodeAttachment($str,$content['parameters']['encoding'],$content['parameters']['charset']);
		
		$parameters = $content['parameters'];
		$name = $content['name'];
		//crmv@53651
		if (in_array($name,array('','Unknown'))) {
			$r = $adb->pquery("select contentname from {$table_prefix}_messages_attach where messagesid = ? and contentid = ?", array($record,$contentid));
			if ($r && $adb->num_rows($r) > 0) {
				$tmp = $adb->query_result($r,0,'contentname');
				if (in_array($name,array('','Unknown'))) $name = $tmp;
			}
		}
		//crmv@53651e
		
		$tmp_name = $root_directory.$tmp_folder.$name;
		file_put_contents($tmp_name,$str);
		$tmp_name_without_rootdir = str_replace($root_directory,'',$tmp_name);
		
		$return_array['savepath'] = $tmp_name_without_rootdir;
		$return_array['success'] = true;
	}
}

echo Zend_Json::encode($return_array);
exit;
?>