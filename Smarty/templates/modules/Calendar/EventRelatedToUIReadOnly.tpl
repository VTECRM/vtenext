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

<table class="{$tableClass}" width="100%" cellpadding="5" cellspacing="0" border="0">
	{if $LABEL.parent_id neq ''}
	<tr>
		<td width="30%" align=right valign="top"><b>{$LABEL.parent_id}</b></td>
		<td width="70%" align=left valign="top">{$ACTIVITYDATA.parent_name}</td>
	</tr>
	{/if}
	<tr>
		<td width="30%" valign="top" align=right><b>{$MOD.LBL_CONTACT_NAME}</b></td>
		<td width="70%" valign="top" align=left>
			{foreach item=contactname key=cntid from=$CONTACTS}
				{if $IS_PERMITTED_CNT_FNAME == '0'}
					{$contactname.2}{$contactname.1}<br />
				{/if}
			{/foreach}
		</td>
	</tr>
</table>