{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@97566 *}
<table width="100%" border="0" cellspacing="0" cellpadding="5">
	<tr>
		<td class="detailedViewHeader" nowrap="nowrap">
			<b>{$MOD.LBL_PM_PREVIEW_RECURRENCE}</b>
		</td>
	</tr>
	{foreach item=PREVIEW from=$PREVIEWS}
		<tr><td style="padding:5px;">{$PREVIEW}</td></tr>
	{/foreach}
</table>