{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{if empty($BN_COLOR)}
	{assign var="BN_COLOR" value="#FFF"}
{/if}
{if empty($BN_BGCOLOR)}
	{assign var="BN_BGCOLOR" value="#7B7E84"}
{/if}
<span {if !empty($BN_ID)}id="{$BN_ID}"{/if} {if !empty($BN_ONCLICK)}onClick="{$BN_ONCLICK}"{/if} style="font-weight:normal;font-size:11px;padding:2px 6px;border-radius:1em;color:{$BN_COLOR};background:{$BN_BGCOLOR};">{$COUNT}</span>