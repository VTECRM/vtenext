{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92218 *}

{if $CHOOSEN_ENCODING == 'AUTO'}
	<span class="small">{'LBL_DETECTED_ENCODING'|@getTranslatedString:$MODULE}</span>&nbsp;&nbsp;
	<select name="use_file_encoding" id="use_file_encoding" class="detailedViewTextBox input-inline" onchange="ImportJs.changeEncoding(jQuery(this).val())">
		{foreach key=_FILE_ENCODING item=_FILE_ENCODING_LABEL from=$SUPPORTED_FILE_ENCODING}
			{if $DETECTED_ENCODING == $_FILE_ENCODING}
				<option value="{$_FILE_ENCODING}" selected="selected">{$_FILE_ENCODING_LABEL|@getTranslatedString:$MODULE}</option>
			{else}
				<option value="{$_FILE_ENCODING}">{$_FILE_ENCODING_LABEL|@getTranslatedString:$MODULE}</option>
			{/if}
		{/foreach}
	</select>
	&nbsp;&nbsp;
{/if}