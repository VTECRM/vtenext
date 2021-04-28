{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table width="100%" cellspacing="0" cellpadding="5">
	<tr>
		<td width="15%" class="heading2">{'LBL_IMPORT_STEP_2'|@getTranslatedString:$MODULE}:</td>
		<td class="big">{'LBL_IMPORT_STEP_2_DESCRIPTION'|@getTranslatedString:$MODULE}</td>
		<td>&nbsp;</td>
	</tr>
	<tr id="file_type_container">
		<td>&nbsp;</td>
		<td><span>{'LBL_FILE_TYPE'|@getTranslatedString:$MODULE}</span></td>
		<td>
			<select name="type" id="type" class="detailedViewTextBox input-inline" onchange="ImportJs.handleFileTypeChange();">
				{foreach item=_FILE_TYPE from=$SUPPORTED_FILE_TYPES}
				<option value="{$_FILE_TYPE}">{$_FILE_TYPE|@getTranslatedString:$MODULE}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr id="file_encoding_container">
		<td>&nbsp;</td>
		<td><span>{'LBL_CHARACTER_ENCODING'|@getTranslatedString:$MODULE}</span></td>
		<td>
			<select name="file_encoding" id="file_encoding" class="detailedViewTextBox input-inline">
				<option value="AUTO">{'LBL_AUTOMATIC'|@getTranslatedString:'APP_STRINGS'}</option> {* crmv@56463 *}
				{foreach key=_FILE_ENCODING item=_FILE_ENCODING_LABEL from=$SUPPORTED_FILE_ENCODING}
				<option value="{$_FILE_ENCODING}">{$_FILE_ENCODING_LABEL|@getTranslatedString:$MODULE}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr id="delimiter_container">
		<td>&nbsp;</td>
		<td><span>{'LBL_DELIMITER'|@getTranslatedString:$MODULE}</span></td>
		<td>
			<select name="delimiter" id="delimiter" class="detailedViewTextBox input-inline">
				<option value="AUTO">{'LBL_AUTOMATIC'|@getTranslatedString:'APP_STRINGS'}</option> {* crmv@56463 *}
				{foreach key=_DELIMITER item=_DELIMITER_LABEL from=$SUPPORTED_DELIMITERS}
				<option value="{$_DELIMITER}">{$_DELIMITER_LABEL|@getTranslatedString:$MODULE}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr id="has_header_container">
		<td>&nbsp;</td>
		<td><span>{'LBL_HAS_HEADER'|@getTranslatedString:$MODULE}</span></td>
		<td><input type="checkbox" class="small" id="has_header" name="has_header" checked /></td>
	</tr>
</table>