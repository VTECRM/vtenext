{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@115268 *}
{if $sdk_mode eq 'detail'}
	{* do nothing *}
{elseif $sdk_mode eq 'edit'}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}" {if $readonly eq 100}style="display:none"{/if}>
		{if $readonly lt 99}
			<input name="{$fldname}[]" type="file" multiple="multiple" tabindex="{$vt_tab}">
		{/if}
		<input name="{$fldname}_key" type="hidden" value="{$fldvalue}">
		{if !empty($secondvalue)}
			{foreach item=value from=$secondvalue}
				{$value}<br>
			{/foreach}
		{/if}
	</div>
{/if}