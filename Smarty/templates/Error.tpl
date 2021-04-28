{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@125629 *}
{if $smarty.request.file eq 'ListView'}&#&#&#&#&#&#{/if}
<!-- error page message (do not delete this comment!!!) -->
<table border="0" cellpadding="10" cellspacing="0" class="small" align="center" {* style="border: 1px solid rgb(204, 204, 204);"*}>
	<tr valign="top">
		<td width="75%">
			<span {if !empty($DESCR)}class="genHeaderSmall"{/if}>{$TITLE}</span>
		</td>
	</tr>
	{if !empty($DESCR)}
		<tr>
			<td>{$DESCR}</td>
		</tr>
	{/if}
</table>