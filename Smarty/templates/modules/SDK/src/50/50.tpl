{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@101683 *}
{if $sdk_mode eq 'detail'}
	{include file="DetailViewUI.tpl" keyid=52}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		{include file='DisplayFieldsReadonly.tpl' uitype=52}
	{elseif $readonly eq 100}
		{include file="DisplayFieldsHidden.tpl" uitype=52}
	{else}
		{include file="EditViewUI.tpl" uitype=52}
	{/if}
{/if}