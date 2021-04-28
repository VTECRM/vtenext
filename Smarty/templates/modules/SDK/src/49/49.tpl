{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@187823 *}
{if $sdk_mode eq 'detail'}
	{include file="DetailViewUI.tpl" keyid=$keyoptions.simulate_uitype}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=51}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=51}
	{else}
		{* edit not supported *}
	{/if}
{/if}