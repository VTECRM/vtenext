{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr>
	<td class="dvtCellLabel">
		File:
	</td>
	<td class="dvtCellInfo">
		{$data.filename}
	</td>
	</tr>
	<tr>
	<td class="dvtCellLabel">
		Tipologia:
	</td>
	<td class="dvtCellInfo">
		{$data.filetype}
	</td>
	</tr>
	<tr>
	<td class="dvtCellLabel">
		Dimensione:
	</td>
	<td class="dvtCellInfo">
		{$data.filesize}
	</td>
	</tr>
	<tr>
	<td class="dvtCellLabel">
		Data inserimento:
	</td>
	<td class="dvtCellInfo">
		{$data.createdtime}
	</td>
	</tr>
	<tr>
	<td class="dvtCellLabel">
		Data ultima modifica:
	</td>
	<td class="dvtCellInfo">
		{$data.modifiedtime}
	</td>
	</tr>
	<tr>
	<td class="dvtCellLabel">
		Titolo:
	</td>
	<td class="dvtCellInfo">
		<input name="title" id="name" value="{$data.title}"/>
	</td>
	</tr>
</table>