{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@104566 *}
{if $line.log.type eq 'edit'}
	<a style="text-decoration:none;" href="javascript:void(0);" onclick="ModNotificationsCommon.toggleChangeLog('{$line.crmid}');">
		<i class="vteicon" id="img_{$line.crmid}">keyboard_arrow_down</i><span style="position: relative; bottom: 7px;">{'LBL_DETAILS'|@getTranslatedString:'ModNotifications'}</span>
	</a>
	<div id="div_{$line.crmid}" style="display:block;">
		<table class="table">
			<tr>
				<td style="width: 33%;"><b>{'Field'|@getTranslatedString:'ChangeLog'}</b></td>
				<td style="width: 33%;"><b>{'Earlier value'|@getTranslatedString:'ChangeLog'}</b></td>
				<td style="width: 33%;"><b>{'Actual value'|@getTranslatedString:'ChangeLog'}</b></td>
			</tr>
			{foreach item=field from=$line.log.info}
				<tr>
					<td>{$field.fieldname_trans}</td>
					<td>{$field.previous}</td>
					<td>{$field.current}</td>
				</tr>
			{/foreach}
		</table>
	</div>
{else}
	<div style="height:28px">&nbsp;</div>
{/if}