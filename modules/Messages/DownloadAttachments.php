<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@62340 crmv@198701 */

global $currentModule,$adb, $table_prefix,$default_charset,$root_directory;
$tmp_folder = "cache/upload/";
$tmp_fullpath = $root_directory.$tmp_folder;
$excludeDisposition = array();	//crmv@65648

$record = vtlib_purify($_REQUEST['record']);

$focus = CRMEntity::getInstance($currentModule);
$focus->retrieve_entity_info($record,$currentModule);
$uid = $focus->column_fields['xuid'];
$accountid = $focus->column_fields['account'];
//sanitize subject for filename
$zipname = preg_replace('/[\/:*?"<>|]/','',$focus->column_fields['subject']);
$zipname = preg_replace('/\s+/', '_', $zipname);
$zipname .= ".zip";
$files = $files2clean = array(); // crmv@73907

//cicle for attachment converter into document
$documents = array();
$sql = "SELECT fa.attachmentsid, n.notesid, a.contentname, fa.path
		FROM {$table_prefix}_messages_attach a 
		INNER JOIN {$table_prefix}_seattachmentsrel s ON s.crmid = a.document
		INNER JOIN {$table_prefix}_notes n ON n.notesid = a.document
		INNER JOIN {$table_prefix}_crmentity e ON e.crmid = n.notesid
		INNER JOIN {$table_prefix}_attachments fa ON s.attachmentsid = fa.attachmentsid
		WHERE deleted = 0 AND messagesid = ? AND COALESCE(a.document,'') <> ''";
$params = Array($record);
$res = $adb->pquery($sql,$params);
while($row = $adb->fetchByAssoc($res)){
	$name = $row["contentname"];
	$filepath = $row["path"];
	$attachmentsid = $row["attachmentsid"];
	$name = html_entity_decode($name, ENT_QUOTES, $default_charset);
	$saved_filename = $attachmentsid."_".$name;
	$fullpath = $filepath.$saved_filename;
	if(is_file($fullpath)) { //crmv@73907
		$files[$saved_filename]=$fullpath; //crmv@181250
		$documents[] = $row['notesid'];
	}
}

//cicle for email attachments
$result = $adb->pquery("select userid from {$table_prefix}_messages_account where id = ?", array($accountid));
if ($result && $adb->num_rows($result) > 0) {
	$userid = $adb->query_result($result,0,'userid');
	
	try{ //crmv@73907
		$focus->setAccount($accountid);
		$focus->getZendMailStorageImap($userid);
		$focus->selectFolder($focus->column_fields['folder']);
		$messageId = $focus->getMailResource()->getNumberByUniqueId($uid);
		$message = $focus->getMailResource()->getMessage($messageId);
		$parts = $focus->getMessageContentParts($message,$id,true);
		
		$params = array($record,$focus->other_contenttypes_attachment);
		if (!is_array($excludeDisposition)) $excludeDisposition = array_filter(array($excludeDisposition));
		if (count($excludeDisposition) > 0) {
			$dispQuery = " AND contentdisposition not in (".generateQuestionMarks($excludeDisposition).")";
			$params[] = $excludeDisposition;
		} else {
			$dispQuery = '';
		}

		$query = "select *
			from {$table_prefix}_messages_attach
			where messagesid = ?
			and (
				contenttype IN (".generateQuestionMarks($focus->other_contenttypes_attachment).")
				OR (contentdisposition IS NOT NULL $dispQuery)
			)";
		if (!empty($documents)) {
			$query .= " and coalesce(document,'') not in (".generateQuestionMarks($documents).")";
			$params[] = $documents;
		}
		$result1 = $adb->pquery($query,$params);
		while($row1 = $adb->fetchByAssoc($result1)){
			$contentid = $row1['contentid'];

			if (!empty($parts['other'][$contentid])) {
				$content = $parts['other'][$contentid];
				$str = $content['content'];
				$str = $focus->decodeAttachment($str,$content['parameters']['encoding'],$content['parameters']['charset']);
				$tmp_name = tempnam($tmp_fullpath,'attach');
				file_put_contents($tmp_name,$str);
				
				$parameters = $content['parameters'];
				$name = $content['name'];
				
				if (in_array($name,array('','Unknown'))) {
					$r = $adb->pquery("select contentname from {$table_prefix}_messages_attach where messagesid = ? and contentid = ?", array($record,$contentid));
					if ($r && $adb->num_rows($r) > 0) {
						$tmp = $adb->query_result($r,0,'contentname');
						if (in_array($name,array('','Unknown'))) $name = $tmp;
					}
				}
				
				$files[$name]=$tmp_name;
				$files2clean[$name]=$tmp_name; //crmv@73907
			}
		}
	//crmv@73907
	} catch (Exception $e) {
		
	}
	//crmv@73907e
}

require_once('vtlib/thirdparty/dZip.inc.php');

$zip=new dZip($tmp_fullpath.$zipname);
foreach($files as $filename=>$filepath){
	$zip->addFile($filepath,$filename);
}
$zip->save();

//cleaning temp files
foreach($files2clean as $filepath){ //crmv@73907
	unlink($filepath);
}

$filesize = filesize($tmp_fullpath.$zipname);
//$filesize = $filesize + ($filesize % 1024); //for zip files //crmv@79484
$fileContent = fread(fopen($tmp_fullpath.$zipname, "r"), $filesize);

header("Content-type: application/zip");
header("Content-length: $filesize");
header("Cache-Control: private");
header("Content-Disposition: attachment; filename=\"$zipname\"");
header("Content-Description: PHP Generated Data");
echo $fileContent;

unlink($tmp_fullpath.$zipname);
exit;