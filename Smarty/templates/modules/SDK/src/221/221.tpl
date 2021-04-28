{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}"><font color="{$fontval}">{$keyval|@getTranslatedString:$MODULE}</font></span>
		<div id="editarea_{$label}" style="display:none;">
			<select id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox">
				{foreach item=arr from=$keyoptions}
					<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
				{/foreach}
			</select>
		</div>
	</div>
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=15}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=15}
	{else}
		{include file="EditViewUI.tpl" uitype=15}
	{/if}
{/if}