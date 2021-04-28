{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@104566 *}
{if !empty($HISTORY)}
	<div class="vte-card">
		{foreach item=line from=$HISTORY}
			{include file="modules/ChangeLog/HistoryLine.tpl"}
		{/foreach}
	</div>
{/if}