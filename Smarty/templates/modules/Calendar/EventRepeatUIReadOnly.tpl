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

{if $LABEL.recurringtype neq ''}
	<table class="{$tableClass}" width="100%" cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td width="30%" align=right><b>{$MOD.LBL_ENABLE_REPEAT}</b></td>
			<td width="70%" align=left>{$ACTIVITYDATA.recurringcheck}</td>
		</tr>
		{if $ACTIVITYDATA.recurringcheck != 'No'}
			<tr>
				<td width="30%" align=right>&nbsp;</td>
				<td>{$MOD.LBL_REPEATEVENT}&nbsp;{$ACTIVITYDATA.repeat_frequency}&nbsp;{$MOD[$ACTIVITYDATA.recurringtype]}</td>
			</tr>
			<tr>
				<td width="30%" align=right>&nbsp;</td>
				<td>{$ACTIVITYDATA.repeat_str}</td>
			</tr>
		{/if}
	</table>
{/if}