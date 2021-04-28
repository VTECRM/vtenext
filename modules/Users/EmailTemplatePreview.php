<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 */

$small_page_title = getTranslatedString('LBL_EMAIL_TEMPLATE','Settings');
$small_page_buttons = '
	<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td width="100%" style="padding:5px"></td>
	 	<td align="right" style="padding: 5px;" nowrap>
			<input class="crmbutton small save" onclick="submittemplate('.$_REQUEST['templateid'].')" type="button" title="'.getTranslatedString('LBL_SELECT').'" value="'.getTranslatedString('LBL_SELECT').'">
			<input class="crmbutton small cancel" onclick="history.back()" type="button" title="'.getTranslatedString('LBL_BACK').'" value="'.getTranslatedString('LBL_BACK').'">
	 	</td>
	 </tr>
	 </table>';
include('themes/SmallHeader.php');

$preview = true;
include('modules/Settings/detailviewemailtemplate.php');

include('themes/SmallFooter.php');
?>
<script>
function submittemplate(templateid)
{
	window.document.location.href = 'index.php?module=Users&action=UsersAjax&file=TemplateMerge&templateid='+templateid;
}
</script>