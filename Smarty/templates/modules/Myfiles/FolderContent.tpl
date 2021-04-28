{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<table class="small lview_foldertt_table" cellpadding="0" cellspacing="0">
	{foreach item=doc from=$FOLDERDATA}
		<tr><td class="lview_foldertt_row">
			{$doc.title|truncate:30}
			{$doc.link}
		</td></tr>
	{foreachelse}
		<tr><td class="lview_foldertt_row">{$MOD.LBL_NO_DOCUMENTS}</td></tr>
	{/foreach}
	<tr><td class="lview_foldertt_lastrow"></td></tr>
</table>