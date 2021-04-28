{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $st_table neq ''}
	<table width="300" id="initial_state_table" class="small"> {* crmv@77249 *}
	<!-- crmv@16604 -->
		{include file="DisplayFields.tpl"}
	<!-- crmv@16604e -->
	</table>
	<br>
	<table width=100% id="rule_table">
		<tr>
			<td class="lvtCol">&nbsp;</td>
			{foreach from=$st_table item=state_row key=state}
				<td class="lvtCol" align='center'>{$state|@getTranslatedString:$MODULE}</td>
			{/foreach}
		</tr>
		{foreach from=$st_table item=state_row key=state}
			<tr bgcolor=white onMouseOver="this.className='lvtColDataHover'" onMouseOut="this.className='lvtColData'">
				<td class="lvtCol" align='right' width='{$st_table_td_width}%'>{$state|@getTranslatedString:$MODULE}&nbsp;</td>
				{foreach from=$state_row item=to_state key=to_state_name}
					<td class="listTableRow small" align='center' width='{$st_table_td_width}%' nowrap> 
						{if $state eq $to_state_name}
							<img src="{'small_left.gif'|resourcever}" height=8 width=8>&nbsp;<i>{$TMOD.LBL_YOU_ARE_HERE}</if>&nbsp;<img src="{'small_right.gif'|resourcever}" height=8 width=8>
						{else}
							{if $to_state.1 eq 1}
								<input type="checkbox" id="st_ruleid_{$to_state.0}"  id="st_ruleid_{$to_state.0}" checked>
							{else}
								<input type="checkbox" id="st_ruleid_{$to_state.0}"  id="st_ruleid_{$to_state.0}" >	
							{/if}
						{/if}
					</td>
				
				{/foreach}		 
			</tr>			
		{/foreach}
	</table>
	<br>
	<table width=100% class="listTableTopButtons">
		<tr>
			<td align='left' nowrap>
				<input type="button" value="{$TMOD.LBL_RESET_ALL}" onclick="sttSetAll(false);" class="crmButton create small">&nbsp;
				<input type="button" value="{$TMOD.LBL_SELECT_ALL}" onclick="sttSetAll(true);" class="crmButton create small">&nbsp;
			</td>
			<td align='center'>
				&nbsp;
			</td>
			<td align='right'>
				<input type="button" value="{$TMOD.LBL_UPDATE}" onclick="sttUpdate();" class="crmButton delete small">&nbsp;
			</td>
		</tr>
	</table>
{else}
{/if}