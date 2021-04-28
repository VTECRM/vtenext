<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix, $currentModule;
$record = vtlib_purify($_REQUEST['record']);
$contentid = vtlib_purify($_REQUEST['contentid']);
$mode = vtlib_purify($_REQUEST['mode']);	//crmv@80250
$checkOnly = vtlib_purify($_REQUEST['checkOnly']);//crmv@204525

(isset($_REQUEST['headers']) && $_REQUEST['headers'] === 'no') ? $headers = false : $headers = true; // crmv@189246
(isset($_REQUEST['exit']) && $_REQUEST['exit'] === 'no') ? $exit = false : $exit = true; // crmv@189246

$focus = CRMEntity::getInstance($currentModule);

//crmv@125629
if ($mode == 'inline') {
	$res = $adb->pquery("select content, parameters from {$table_prefix}_messages_inline_cache where messagesid = ? and contentid = ?", array($record,$contentid));
	if ($res && $adb->num_rows($res)>0){
		$str = $adb->query_result_no_html($res,0,'content');
		$parameters = $adb->query_result_no_html($res,0,'parameters');
		if (!empty($parameters)) $parameters = Zend_Json::decode($parameters);
		
		// crmv@189246
		if ($headers) {
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Headers: X-Requested-With");
			header('Content-Type: '.$parameters['contenttype']);
			header("Content-Disposition: {$parameters['contentdisposition']}; filename=\"{$parameters['name']}\"");
		}
		echo $str;
		if ($exit) exit;
		// crmv@189246e
	}
}
//crmv@125629e

//crmv@46760	crmv@91321
$sql = "select s.attachmentsid, n.notesid, a.contentname
from {$table_prefix}_messages_attach a 
inner join {$table_prefix}_seattachmentsrel s on s.crmid = a.document
inner join {$table_prefix}_notes n on n.notesid = a.document
inner join {$table_prefix}_crmentity e on e.crmid = n.notesid
where deleted = 0 and messagesid = ? and contentid = ? and coalesce(a.document,'') <> ''";
$params = Array($record,$contentid);
$res = $adb->pquery($sql,$params);
if ($res && $adb->num_rows($res)>0){
	$attachmentsid = $adb->query_result_no_html($res,0,'attachmentsid');
	$name = $adb->query_result_no_html($res,0,'contentname');
	if ($mode == 'inline' && $focus->isConvertableFormat($name) && extension_loaded('imagick')) {
		$dbQuery = "SELECT * FROM ".$table_prefix."_attachments WHERE attachmentsid = ?" ;
		$result = $adb->pquery($dbQuery, array($attachmentsid));
		$saved_filename = $adb->query_result_no_html($result, 0, "path").$attachmentsid."_".$adb->query_result_no_html($result, 0, "name");

		$image = new Imagick($saved_filename);
		$image->setImageFormat('png');
		$str = $image;
		$pathinfo = pathinfo($name);
		$name = $pathinfo['filename'].'.png';
		
		// crmv@189246
		if ($headers) {
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Headers: X-Requested-With");
			header('Content-Type: image/png');
			header("Content-Disposition: attachment; filename=\"{$name}\"");
		}
		echo $image;
		if ($exit) exit;
		// crmv@189246e
	}
	// crmv@189246
	$entityid = $adb->query_result_no_html($res,0,'notesid');
	$returnmodule = getSalesEntityType($entityid);
	$SBU = StorageBackendUtils::getInstance();
	$SBU->downloadFile($returnmodule, $entityid, $attachmentsid, !$headers);
	if ($exit) exit;
	// crmv@189246e
}
//crmv@46760e	crmv@91321e

$focus->retrieve_entity_info($record,$currentModule);
$uid = $focus->column_fields['xuid'];
$accountid = $focus->column_fields['account'];

$result = $adb->pquery("select userid from {$table_prefix}_messages_account where id = ?", array($accountid));
if ($result && $adb->num_rows($result) > 0) {
	$userid = $adb->query_result($result,0,'userid');

	$focus->setAccount($accountid);
	$focus->getZendMailStorageImap($userid);
	$focus->selectFolder($focus->column_fields['folder']);

	//crmv@204525
    try {
        $messageId = $focus->getMailResource()->getNumberByUniqueId($uid);
    } catch(Exception $e) {
        if ($e->getMessage() == 'unique id not found') {
            if($checkOnly == 1)
                echo '-1';
            else
                echo getTranslatedString('LBL_MESSAGE_MOVED', 'Messages');
            exit();
        }
    }
    if($checkOnly == 1){
        echo 1;
        exit();
    }
    //crmv@204525e

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
		//crmv@112756
		if ($parameters['contenttype'] == 'application/ms-tnef') {
			$filesize = '';
			if (!empty($focus->column_fields['subject'])) $name = $focus->column_fields['subject'];
			$tmp_zipname = $focus->extractTnefAndZip($name, $str, $filesize);
			// crmv@189246
			if ($headers) {
				header("Content-type: application/zip");
				header("Content-length: $filesize");
				header("Cache-Control: private");
				header("Content-Disposition: attachment; filename=\"{$name}\"");
				header("Content-Description: PHP Generated Data");
			}
			echo $str;
			unlink($tmp_zipname);
			if ($exit) exit;
			// crmv@189246e
		}
		//crmv@112756e
		//crmv@80250
		if ($mode == 'inline' && !$focus->isSupportedInlineFormat($name)) {
			if ($exit) exit; // crmv@189246
		}
		//crmv@80250e
		//crmv@91321
		elseif ($mode == 'inline' && $focus->isSupportedInlineFormat($name) && $focus->isConvertableFormat($name)) {
			if (extension_loaded('imagick')) {
				$image = new Imagick();
				$image->readimageblob($str);
				$image->setImageFormat('png');
				$parameters['contenttype'] = 'image/png';
				$str = $image;
			} else {
				if ($exit) exit; // crmv@189246
			}
		}
		//crmv@91321e
		//crmv@125629
		if ($mode == 'inline') {
			$focus->saveInlineCache($record,$contentid,$str,array(
				'name'=>$name,
				'contenttype'=>$parameters['contenttype'],
				'contentdisposition'=>$parameters['contentdisposition'],
			));
		}
		//crmv@125629e
		// crmv@189246
		if ($headers) {
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Allow-Headers: X-Requested-With");
			header('Content-Type: '.$parameters['contenttype']);
			header("Content-Disposition: {$parameters['contentdisposition']}; filename=\"{$name}\"");
		}
		echo $str;
		// crmv@189246e
	}
}
?>