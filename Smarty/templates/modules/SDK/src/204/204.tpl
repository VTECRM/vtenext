{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@2043m crmv@56233 *}
{if $sdk_mode eq 'detail'}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div class="dvtCellInfoOff">
		<input type="hidden" name="{$keyfldname}" value="{$keyval}">
		{$keyoptions}
	</div>
{elseif $sdk_mode eq 'edit'}
	{* crmv@129272 *}
	{if $FROMCUSTOMVIEW}
		{include file="EditViewUI.tpl" uitype=1}
	{else}
		{include file="DisplayFieldsHidden.tpl" uitype=1}
	{/if}
	{* crmv@129272e *}
{/if}