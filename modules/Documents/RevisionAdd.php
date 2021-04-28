<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@43147 */

global $small_page_title, $small_page_title;
$small_page_title = getTranslatedString('Aggiungi Revisione al Documento','Documents');
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" class="crmbutton small edit" onclick="document.forms[\'AddRev\'].submit();" value="'.getTranslatedString('Aggiungi Revisione','Documents').'">
 	</td>
 </tr>
 </table>
';
include('themes/SmallHeader.php');

global $adb;

$record = $_REQUEST['record'];

$html = '<form name="AddRev" action="index.php?module=Documents&action=DocumentsAjax&file=RevisionSave" method="POST" enctype=multipart/form-data>
			<input type="hidden" value="" name="filename_hidden">
			<input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'"> <!-- crmv@171581 -->
		    <input type="hidden" value="'.$record.'" name="record">
			<table width="100%" border="0" cellspacing="0" cellpadding="20">
			  <tr>
			    <td class="dvtCellLabel">'.getTranslatedString('Seleziona documento','Documents').'</td>
			    <td><input type="file" style="" onchange="validateFilename(this);" tabindex="" value="" id="filename_I__" name="filename"></td>
			  </tr>
			</table>
		</form>';
echo $html;

include('themes/SmallFooter.php');
?>