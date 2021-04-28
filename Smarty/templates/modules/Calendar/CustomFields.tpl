{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *} 

{assign var="LBL_CUSTOM_INFORMATION_TRANS" value=$APP.LBL_CUSTOM_INFORMATION}
{if $CUSTOM_FIELDS_DATA|@count > 0 && $CUSTOM_FIELDS_DATA.$LBL_CUSTOM_INFORMATION_TRANS|@count > 0}
<table border="0" cellspacing="0" cellpadding="5" width="100%">
	<tr height="10px">
		<td></td>
	</tr>
	<tr>
		<td colspan="2">
			<b>{$APP.LBL_CUSTOM_INFORMATION}</b>
		</td>
	</tr>
	{include file="DisplayFields.tpl"}
</table>
{/if}