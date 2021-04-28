{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="lview_file_rename" style="display:none; position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td id="Renamefile_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="80%"><b>
						<span>{'LBL_RENAME_FILE'|@getTranslatedString:$MODULE}</span>
					</b></td>
					<td width="20%" align="right">
						<input id="lview_rename_file" type="button" value="{'LBL_SAVE_LABEL'|@getTranslatedString:$MODULE}" name="button" class="crmbutton small save" title="{'LBL_SAVE_LABEL'|@getTranslatedString:$MODULE}" />
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="lview_file_rename_content">
		<form name="lview_file_renameform" id="lview_file_renameform">
		<input id="folderid" name="folderid" value="{$FOLDERID}" type="hidden" />
		<input id="uniqueid" name="uniqueid" value="{$UNIQUEID}" type="hidden" />
		<input id="fileids" name="fileids" value="{$FILEIDS}" type="hidden" />
		<input id="finalize_upload" name="finalize_upload" value="1" type="hidden" />
		<table cellpadding="5" cellspacing="0" class="hdrNameBg" >
			<tr>
				<td colspan="4">
					<span>
						<b>{'LBL_FILE_RENAME_MSG'|@getTranslatedString:$MODULE}</b>
						<br><b>{'LBL_FILE_RENAME'|@getTranslatedString:$MODULE}</b>:{'LBL_FILE_RENAME_HELP'|@getTranslatedString:$MODULE}
						<br><b>{'LBL_FILE_REPLACE'|@getTranslatedString:$MODULE}</b>:{'LBL_FILE_REPLACE_HELP'|@getTranslatedString:$MODULE}
						<br><b>{'LBL_FILE_JUMP'|@getTranslatedString:$MODULE}</b>:{'LBL_FILE_JUMP_HELP'|@getTranslatedString:$MODULE}
					</span>
				</td>
			</tr>		
			<tr>
				<td>
					{'LBL_FILE_NAME'|@getTranslatedString:$MODULE}
				</td>
				<td>
					{'LBL_EXTENSION'|@getTranslatedString:$MODULE}
				</td>
				<td>
					{'LBL_TITLE'|@getTranslatedString:$MODULE}
				</td>
				<td>
					{'LBL_ACTION'|@getTranslatedString:$MODULE}
				</td>
			</tr>		
			{foreach key=fileid item=arr from=$FILES_TO_RENAME}
			<tr>
				<td>
					<div class="dvtCellInfoOff">
						<input class="detailedViewTextBox" id="file_{$fileid}" name="file_{$fileid}" type="text" value="{$arr.filename}" />
						<input class="detailedViewTextBox" id="filebackup_{$fileid}" name="filebackup_{$fileid}" type="hidden" value="{$arr.filename}" />
					</div>
				</td>
				<td>
					<div class="dvtCellInfoOff">
						<input class="detailedViewTextBox" id="ext_{$fileid}" name="ext_{$fileid}" type="text" value="{$arr.extension}" size="4" />
					</div>
				</td>
				<td>
					<div class="dvtCellInfoOff">
						<input class="detailedViewTextBox" id="desc_{$fileid}" name="desc_{$fileid}" type="text" value="{$arr.filedescription}" />
						<input class="detailedViewTextBox" id="descbackup_{$fileid}" name="descbackup_{$fileid}" type="hidden" value="{$arr.filedescription}" />
					</div>
				</td>
				<td>
					<div class="dvtCellInfo">
						<select class="detailedViewTextBox" fileid="{$fileid}" name="action_{$fileid}" id="action_{$fileid}">
							<option value="replace">{'LBL_FILE_REPLACE'|@getTranslatedString:$MODULE}</option>
							<option value="rename">{'LBL_FILE_RENAME'|@getTranslatedString:$MODULE}</option>
							<option value="jump">{'LBL_FILE_JUMP'|@getTranslatedString:$MODULE}</option>
						</select>
					</div>
				</td>	
			</tr>
			{/foreach}
		</table>
		</form>
	</div>
	<br />
	{literal}
	<div class="closebutton" onClick="jQuery('#lview_file_rename').hide(function(){jQuery('#layerbg').css({'display': 'none'});jQuery(this).detach()});"></div>
	{/literal}
</div>