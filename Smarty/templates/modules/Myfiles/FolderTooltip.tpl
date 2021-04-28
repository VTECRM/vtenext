{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@30967 *}

<table class="small lview_foldertt_table" cellpadding="0" cellspacing="0">
	<tr><td class="dvtSelectedCell lview_foldertt_title" align="center">{$APP.LBL_FOLDER_CONTENT}</td></tr>
	{foreach item=doc from=$FOLDERDATA}
		<tr><td class="lview_foldertt_row">
			{$doc.title|truncate:30}
		</td></tr>
	{foreachelse}
		<tr><td class="lview_foldertt_row">{$MOD.LBL_NO_DOCUMENTS}</td></tr>
	{/foreach}

	{if is_array($FOLDERDATA) && count($FOLDERDATA) < $TOTALCOUNT} {* crmv@167234 *}
		<tr><td class="lview_foldertt_row">...</td></tr>
	{/if}

	<tr><td class="lview_foldertt_lastrow"></td></tr>
</table>