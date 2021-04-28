{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@188364 *}
{if !empty($LOGS)}
	{foreach item=line from=$LOGS}
		{include file="modules/Processes/HistoryLine.tpl"}
	{/foreach}
{/if}