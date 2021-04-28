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
	<tr>
		{if $LABEL.parent_id neq ''}
		<td width="30%" align=right>
			<b>{$LABEL.parent_id}</b>
		</td>
		<td width="70%" align=left>{$ACTIVITYDATA.parent_name}</td>
		{/if}
	</tr>
	<tr>
		{if $LABEL.contact_id neq ''}
		<td width="30%" align=right>
			<b>{$MOD.LBL_CONTACT_NAME}</b>
		</td>
		<td width="70%" align=left>
			<a href="{$ACTIVITYDATA.contact_idlink}">{$ACTIVITYDATA.contact_id}</a>
		</td>
		{/if}
	</tr>
</table>