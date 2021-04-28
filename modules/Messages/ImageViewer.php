<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@62414 crmv@198701 */

global $root_directory;
include_once('include/utils/utils.php');

$requestedfile = vtlib_purify($_REQUEST['requestedfile']);

if (is_numeric($requestedfile)) {
	$FS = FileStorage::getInstance();
	$attachmentInfo = $FS->getFileInfoByCrmid($requestedfile);
	if ($attachmentInfo !== null) {
		$attachmentId = $attachmentInfo['attachmentsid'];
		$localPath = $attachmentInfo['local_path'];
		$requestedfile = "index.php?module=uploads&action=downloadfile&entityid={$requestedfile}&fileid={$attachmentId}";
	}
} else {
	$localPath = $requestedfile;
}

$image_info = getimagesize($root_directory.$localPath);

//crmv@91321
$focus = CRMEntity::getInstance($currentModule);
if ($focus->isConvertableFormat($localPath) && extension_loaded('imagick')) {
	$image = new Imagick($localPath);
	$image->setImageFormat('png');
	$requestedfile = 'data:image/png;base64,'.base64_encode($image);
}
//crmv@91321e

$html = "<img src='$requestedfile' {$image_info[3]} border=0 />";	
echo $html;