{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@30967 crmv@30976 *}

<table class="small lview_foldertt_table" cellpadding="0" cellspacing="0">
	<tr><td class="dvtSelectedCell lview_foldertt_title" align="center" colspan="2">{$APP.LBL_FOLDER_CONTENT}</td></tr>
	{foreach item=doc from=$FOLDERDATA}
		<tr>
			<td class="lview_foldertt_row" align="left">
				{$doc.chartname|truncate:30}
			</td>
			<td class="lview_foldertt_row">
				<img src="{$doc.chart_filename}" width="64" border="0" style="padding:4px" />
			</td>

		</tr>
	{foreachelse}
		<tr><td class="lview_foldertt_row" colspan="2">{$MOD.LBL_NO_DOCUMENTS}</td></tr>
	{/foreach}

	{if is_array($FOLDERDATA) && count($FOLDERDATA) < $TOTALCOUNT} {* crmv@167234 *}
		<tr><td class="lview_foldertt_row" colspan="2">...</td></tr>
	{/if}

	<tr><td class="lview_foldertt_lastrow" colspan="2"></td></tr>
</table>