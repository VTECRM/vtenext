<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$table_prefix;

$record = intval(vtlib_purify($_REQUEST['record']));

if ($_REQUEST['mode'] == 'save' && $_REQUEST['avatar'] != '') {
	$focus = CRMEntity::getInstance('Users');
	$focus->retrieve_entity_info($record,'Users');
	$focus->id = $record;
	$focus->mode = 'edit';
	$focus->column_fields['avatar'] = $_REQUEST['avatar'];
	$focus->save("Users");
	
	echo "<script>
		parent.jQuery('#avatar').attr('src','".$_REQUEST['avatar']."');
		parent.closePopup();
	</script>";
	exit;
}

global $small_page_title, $small_page_title;
$small_page_title = getTranslatedString('LBL_CROP_AVATAR','ModComments');
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" class="crmbutton small cancel" value="'.getTranslatedString('LBL_BACK').'" onclick="history.back();">
 		<input type="button" class="crmbutton small save" value="'.getTranslatedString('LBL_SAVE_LABEL').'" onclick="document.form.submit();">
 	</td>
</tr>
</table>';
include('themes/SmallHeader.php');

$sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments left join ".$table_prefix."_salesmanattachmentsrel on ".$table_prefix."_salesmanattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_salesmanattachmentsrel.smid=?";
$image_res = $adb->pquery($sql, array($record));
$image_id = $adb->query_result($image_res,0,'attachmentsid');
$image_path = $adb->query_result($image_res,0,'path');
$image_name = $adb->query_result($image_res,0,'name');
$imgpath = $image_path.$image_id."_".$image_name;
$filetype = substr($image_name,strrpos($image_name,'.')+1);

if ($filetype == 'jpg') {
    $img_r = imagecreatefromjpeg($imgpath);
} elseif ($filetype == 'jpeg') {
    $img_r = imagecreatefromjpeg($imgpath);
} elseif ($filetype == 'png') {
    $img_r = imagecreatefrompng($imgpath);
} elseif ($filetype == 'gif') {
    $img_r = imagecreatefromgif($imgpath);
} else {
	die('La fotografia deve essere nei formati jpg/jpeg/png/gif per poter creare una miniatura.');
}
// crmv@167874

$targ_w = $targ_h = 32;
$quality = 100;

$dst_r = imagecreatetruecolor($targ_w,$targ_h);

// preserve transparency
if ($filetype == "png" || $filetype == "gif") {
    imagecolortransparent($dst_r, imagecolorallocatealpha($dst_r, 0, 0, 0, 127));
    imagealphablending($dst_r, false);
    imagesavealpha($dst_r, true);
    $desttype = 'png';
} else {
	$desttype = 'jpg';
}

imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);

$current_id = $adb->getUniqueID($table_prefix."_crmentity");
$upload_file_path = decideFilePath();
$new_image = $upload_file_path.$current_id."_".str_replace('.'.$filetype,'',$image_name).'.'.$desttype;

// use original format to preserve transparency
if ($desttype == 'png') {
	imagepng($dst_r, $new_image);
} else {
	imagejpeg($dst_r, $new_image, $quality);
}
// crmv@167874e
?>
<form name="form" method="post" action="index.php?module=SDK&action=SDKAjax&file=src/205/SaveCrop&record=<?php echo $record; ?>">
	<input type="hidden" name="__csrf_token" value="<?php echo RequestHandler::getCSRFToken(); //crmv@171581 ?>"> 	
	<input type="hidden" name="avatar" value="<?php echo $new_image; ?>">
	<input type="hidden" name="mode" value="save">
	<table cellspacing="0" cellpadding="0" width="100%" height="100px" align="center">
	<tr>
		<td align="center">
			<?php echo getTranslatedString('LBL_SAVE_AVATAR','ModComments'); ?><br /><br />
			<img src="<?php echo $new_image; ?>" />
		</td>
	</tr>
	</table>
</form>
<?php
include('themes/SmallFooter.php');
?>