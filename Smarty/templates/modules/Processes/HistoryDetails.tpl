{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@188364 *}
{if !empty($INFO[$line.elementid])}
	<a style="text-decoration:none;" href="javascript:void(0);" onclick="ModNotificationsCommon.toggleChangeLog('{$line.id}');">
		<i class="vteicon" id="img_{$line.id}">keyboard_arrow_down</i><span style="position: relative; bottom: 7px;">{'LBL_DETAILS'|@getTranslatedString:'ModNotifications'}</span>
	</a>
	<div id="div_{$line.id}" style="display:block;">
		<table class="table">
			{foreach key=k item=v from=$INFO[$line.elementid]}
				<tr>
					<td with="100%">
						{include file="modules/Processes/HistoryDetail.tpl" type=$k info=$v elementid=$line.elementid}
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
{else}
	<div style="height:28px">&nbsp;</div>
{/if}