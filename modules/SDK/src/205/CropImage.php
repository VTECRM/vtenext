<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $small_page_title, $small_page_title;
$small_page_title = getTranslatedString('LBL_CROP_AVATAR','ModComments');
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" class="crmbutton small save" value="'.getTranslatedString('LBL_CROP','ModComments').'" onclick="if (checkCoords()) document.form.submit();">
 	</td>
 </tr>
 </table>
';
include('themes/SmallHeader.php');

global $adb,$table_prefix;

$record = intval(vtlib_purify($_REQUEST['record']));

$sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments left join ".$table_prefix."_salesmanattachmentsrel on ".$table_prefix."_salesmanattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_salesmanattachmentsrel.smid=?";
$image_res = $adb->pquery($sql, array($record));
$image_id = $adb->query_result($image_res,0,'attachmentsid');
$image_path = $adb->query_result($image_res,0,'path');
$image_name = $adb->query_result($image_res,0,'name');
$imgpath = $image_path.$image_id."_".$image_name;

?>
<script language="JavaScript" type="text/javascript" src="modules/SDK/src/205/js/jquery.Jcrop.min.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/SDK/src/205/js/jquery.FieldsetToggle.js"></script>
<link href="modules/SDK/src/205/css/jquery.Jcrop.min.css" rel="stylesheet" type="text/css" />

<table cellpadding="0" cellspacing="0" border="0" width="99%" class="small" align="center">
<tr><td align="center">
	<img id="cropbox" src="<?php echo $imgpath; ?>" style="display: none; visibility: hidden;">
	<form name="form" method="post" action="index.php?module=SDK&action=SDKAjax&file=src/205/SaveCrop&record=<?php echo $record; ?>">
		<input type="hidden" name="__csrf_token" value="<?php echo RequestHandler::getCSRFToken(); //crmv@171581 ?>"> 	
		<input type="hidden" name="x" id="x" value="0">
		<input type="hidden" name="y" id="y" value="0">
		<input type="hidden" name="w" id="w" value="0">
		<input type="hidden" name="h" id="h" value="0">
		<input type="hidden" name="width_image" id="width_image">
		<input type="hidden" name="height_image" id="height_image">
	</form>
</td></tr>
</table>

<script language="Javascript">
jQuery(function(){
	jQuery('#cropbox').Jcrop({
		aspectRatio: 1,
		bgColor: 'transparent', // crmv@167874
		onSelect: updateCoords
	});
});
function updateCoords(c)
{
	jQuery('#x').val(c.x);
	jQuery('#y').val(c.y);
	jQuery('#w').val(c.w);
	jQuery('#h').val(c.h);
}
function checkCoords()
{
	getObj('width_image').value = jQuery('#cropbox').width();
	getObj('height_image').value = jQuery('#cropbox').height();

	if (parseInt(jQuery('#w').val())>0) return true;
	alert('<?php echo getTranslatedString('LBL_PLEASE_SELECT_REGION','ModComments'); ?>');
	return false;
}
jQuery(document).ready(function() {
	getObj('vte_menu').style.zIndex = findZMax()+1;
	getObj('Buttons_List').style.zIndex = findZMax()+1;
});
</script>

<?php
include('themes/SmallFooter.php');
?>