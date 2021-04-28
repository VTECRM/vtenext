<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/CustomFieldUtil.php');

global $mod_strings,$app_strings,$app_list_strings,$theme,$adb;
global $log;

$visible = vtlib_purify($_REQUEST['visible']);
$disable = vtlib_purify($_REQUEST['disable']);
$label = getTranslatedString(vtlib_purify($_REQUEST['label']));
require_once('modules/VteCore/layout_utils.php');	//crmv@30447
//crmv@171581  
$output  .= '<div class="layerPopup" style="position:relative; display:block">' .
		'	<form action="index.php" method="post" name="fieldinfoform" onsubmit="VteJS_DialogBox.block();"> 
			<input type="hidden" name="__csrf_token" value="'.RequestHandler::getCSRFToken().'">
		    <input type="hidden" name="module" value="Settings">
	  		<input type="hidden" name="action" value="SettingsAjax">
	  		<input type="hidden" name="fld_module" value="'.vtlib_purify($_REQUEST['fld_module']).'">
	  		<input type="hidden" name="parenttab" value="Settings">
          	<input type="hidden" name="file" value="UpdateMandatoryFields">
 			<input type="hidden" name="fieldid" value="'.vtlib_purify($_REQUEST['fieldid']).'">
 			<table width="100%" border="0" cellpadding="5" cellspacing="0" class="layerHeadingULine">
				<tr>
					<td width="95%" align="left" class="layerPopupHeading">'.$label.'</td>
			
					<td width="5%" align="right"><a href="javascript:fninvsh(\'fieldInfo\');"><img src="'. resourcever('editfield.gif') .'" border="0"  align="absmiddle" /></a></td>
				</tr>
			</table>
			<table border=0 cellspacing=0 cellpadding="5" width=99%> 
				<tr>
					<td valign="top">
						<input name="mandatory"  type="checkbox" '.$visible.'  '.$disable.' >
						&nbsp;<b>Mandatory Field</b>
					</td>
				</tr>
				<tr>
					<td valign="top">
					<input name="presence" value="" type="checkbox" >
					&nbsp;<b>Show</b>
					</td>
				</tr>
				<tr>
				<td align="center">
					<input type="submit" name="save"  value=" &nbsp; '.$app_strings['LBL_SAVE_BUTTON_LABEL'].'&nbsp; " class="crmButton small save" "/>&nbsp;
					<input type="button" name="cancel" value=" '.$app_strings['LBL_CANCEL_BUTTON_LABEL'].' " class="crmButton small cancel" onclick="fninvsh(\'fieldInfo\');" />
				</td>
			</tr>
			</table>
		</form></div>';
		
		echo $output;
?>