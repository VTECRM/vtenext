{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@83878 *}

<div id="formatsElementsContainer" style="display:none;">
	{foreach key=_FIELD_NAME item=_FIELD_INFO from=$AVAILABLE_FIELDS}
		<span id="{$_FIELD_NAME}_format_container" name="{$_FIELD_NAME}_format">
			{assign var="_FIELD_FORMATS" value=$FIELDS_FORMATS[$_FIELD_NAME]}
			{if is_array($_FIELD_FORMATS) && count($_FIELD_FORMATS) > 0} {* crmv@167234 *}
				<select id="{$_FIELD_NAME}_format" name="{$_FIELD_NAME}_format" class="detailedViewTextBox">
					{html_options options=$_FIELD_FORMATS}
				</select>
			{/if}
		</span>
	{/foreach}
</div>