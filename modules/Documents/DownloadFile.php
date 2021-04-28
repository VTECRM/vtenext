<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/


global $adb;
global $fileId;
global $current_user;
global $table_prefix;
$fileid = vtlib_purify($_REQUEST['fileid']);
$folderId = vtlib_purify($_REQUEST['folderid']);

$returnModule='Documents';
$noteQuery = $adb->pquery("select crmid from ".$table_prefix."_seattachmentsrel where attachmentsid = ?",array($fileid));
$noteId = $adb->query_result($noteQuery,0,'crmid');
$dbQuery = "SELECT * FROM ".$table_prefix."_notes WHERE notesid = ? and folderid= ?";
$result = $adb->pquery($dbQuery,array($noteId,$folderId)) or die("Couldn't get file list");
if ($adb->num_rows($result) == 1) {
    $fileType = @$adb->query_result($result, 0, "filetype");
    $name = @$adb->query_result($result, 0, "filename");
    $name = html_entity_decode($name, ENT_QUOTES);
    $pathQuery = $adb->pquery("select path from " . $table_prefix . "_attachments where attachmentsid = ?", array($fileid));
    $filePath = $adb->query_result($pathQuery, 0, 'path');

    $savedFilename = $fileid . "_" . $name;
    if (!$filePath . $savedFilename) {
        $savedFilename = $fileid . "_" . $name;
    }
    $fileSize = filesize($filePath . $savedFilename);
    if (!fopen($filePath . $savedFilename, "r")) {
        echo 'unable to open file';
    } else {
        $fileContent = fread(fopen($filePath . $savedFilename, "r"), $fileSize);
    }
    if ($fileContent != '') {
        $sql = "select filedownloadcount from " . $table_prefix . "_notes where notesid= ?";
        $result = $adb->pquery($sql, array($fileid));
        $downloadCount = $adb->query_result($result, 0, 'filedownloadcount') + 1;
        $sql = "update " . $table_prefix . "_notes set filedownloadcount= ? where notesid= ?";
        $res = $adb->pquery($sql, array($downloadCount, $fileid));
    }

    header("Content-type: $fileType");
	header("Content-length: $filesize");
    header("Cache-Control: private");
    header("Content-Disposition: attachment; filename=$name");
    header("Content-Description: PHP Generated Data");
    echo $fileContent;
} else {
    echo "Record doesn't exist.";
}
?>