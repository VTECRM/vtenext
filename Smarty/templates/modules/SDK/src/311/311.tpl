{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@201442 *}

{if $sdk_mode eq 'detail'}
	{include file="DetailViewUI.tpl" keyid=33}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=33}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=33}
	{else}
		{include file="EditViewUI.tpl" uitype=33}
	{/if}
{/if}