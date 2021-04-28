{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

{if !$disableStyle}
	{assign var="tableClass" value="table"}
{else}
	{assign var="tableClass" value=""}
{/if}

{if $LABEL.reminder_time != ''}
	<table class="{$tableClass}" width="100%" cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td width="30%" align=right><b>{$MOD.LBL_SENDREMINDER}</b></td>
			<td width="70%" align=left>{$ACTIVITYDATA.set_reminder}</td>
		</tr>
		{if $ACTIVITYDATA.set_reminder != 'No'}
		<tr>
			<td width="30%" align=right><b>{$MOD.LBL_RMD_ON}</b></td>
			<td width="70%" align=left>{$ACTIVITYDATA.reminder_str}</td>
		</tr>
		{/if}
	</table>
{/if}