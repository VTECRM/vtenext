<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 */

global $default_charset;

if(isset($_REQUEST['templateid']) && $_REQUEST['templateid'] !='')
{
	$templatedetails = getTemplateDetails($_REQUEST['templateid']);
}
?>
<form name="frmrepstr">
<input type="hidden" name="subject" value="<?php echo $templatedetails['subject'];?>" />
<textarea name="repstr" style="visibility:hidden">
<?php echo htmlentities($templatedetails['body'], ENT_NOQUOTES, $default_charset); ?>
</textarea>
</form>
<!-- crmv@21048m crmv@97469 -->
<script language="JavaScript" type="text/javascript" src="include/js/general.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/jquery.js"></script>
<script language="javascript">
var signatureId = parent.jQuery('#signature_id').val();
var ckeditor = parent.CKEDITOR.instances.description;

<?php if (intval($templatedetails['overwrite_message']) == 0) { ?>
	var html = ckeditor.getData();
	var div = document.createElement('div');
	var useSignature = <?php echo intval($templatedetails['use_signature']); ?>;
	div.innerHTML = html;
	
	if (useSignature && jQuery(div).find('div#template'+signatureId).length > 0) {
		jQuery(div).find('div#template'+signatureId).html(window.document.frmrepstr.repstr.value);
		parent.document.getElementById('description').value = jQuery(div).html();
	} else {
		parent.document.getElementById('description').value = '<div id="template'+signatureId+'">' + window.document.frmrepstr.repstr.value + '</div>' + html;
	}
<?php } else { ?>
	parent.document.getElementById('subject').value = window.document.frmrepstr.subject.value;
	parent.document.getElementById('description').value = '<div id="template'+signatureId+'">' + window.document.frmrepstr.repstr.value + '</div><div id="signature'+signatureId+'"></div>';
<?php } ?>
ckeditor.setData(parent.document.getElementById('description').value, function(){
	parent.jQuery('#send_mode_multiple').prop('checked', true);	//crmv@26639
	parent.document.getElementById('use_signature').value = <?php echo intval($templatedetails['use_signature']); ?>;
	parent.changeSignature();
	closePopup();
});
</script>
<!-- crmv@21048m e crmv@97469e -->