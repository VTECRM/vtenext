{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table width="100%" border="0">
	<tr>
		<td align="left">
			{if $LIST_ENTRIES neq ''}
				{$RECORD_COUNTS}
			{/if}
		</td>
		{$NAVIGATION}
	</tr>
</table>

<table class="vtetable">
	<thead>
		<tr>
			{foreach item=header from=$LIST_HEADER}
				<th>{$header}</th>
			{/foreach}
		</tr>
	</thead>
	<tbody>
		{foreach item=entity key=entity_id from=$LIST_ENTRIES}
			<tr>
				{foreach item=data from=$entity}
					{if $data neq "0000-00-00 00:00:00"}
						<td>{$data}</td>
					{else}
						<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;---</td>
					{/if}
				{/foreach}
			</tr>
		{foreachelse}
			<tr>
				<td colspan="{$LIST_HEADER|@count}" height="300px" align="center" class="genHeaderSmall">{$MOD.LBL_NO_DATA}</td>
			</tr>
		{/foreach}
	</tbody>
</table>