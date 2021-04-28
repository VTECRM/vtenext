{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@36871 *}
{if is_array($TODOLIST_DATE) && count($TODOLIST_DATE) > 0} {* crmv@167234 *}
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg" id="todos_list">
	{foreach item=todoperiod key=timestampAgo from=$TODOLIST_DATE}
		{counter assign=rowid}
		{assign var=rowidstr value="todos_list_tbody_$rowid"}
		{assign var=period_count value=$todoperiod|@count}
		{if $period_count >= $TODOLIST_TODOSINPERIOD}
			{assign var=hidenext value=true}
			<tr id="{$rowidstr}_toggle">
				<td colspan="3" onclick="toggleTodoPeriod('{$rowidstr}');" class="level3Bg" style="cursor:pointer;">
					<img width="15" border="0" align="top" src="{'open_details.png'|resourcever}" id="{$rowidstr}_img">&nbsp;{$timestampAgo} ({$period_count})
				</td>
			</tr>
		{else}
			{assign var=hidenext value=false}
		{/if}
		<tbody id="{$rowidstr}" style="display:{if $hidenext eq true}none{else}block{/if}">
		{foreach item=todorow from=$todoperiod}
			<tr id="todos_list_row_{$todorow.activityid}">
				<td class="trackerListBullet small" align="center" width="12">
					<input type="checkbox" id="todo_{$todorow.activityid}" onClick="closeTodo({$todorow.activityid},this.checked);" title="{'LBL_COMPLETED'|getTranslatedString:'Calendar'}" style="cursor: pointer;" />
				</td>
				<td class="trackerList small {if $todorow.unseen}ModCommUnseen{/if}" width="200">
					<a href="index.php?module=Calendar&action=DetailView&record={$todorow.activityid}">{$todorow.subject}</a>
					<br />{$todorow.expired_str} <a href="javascript:;" class="{if $todorow.unseen}ModCommUnseen{/if}" style="color: gray; text-decoration:none;" title="{$todorow.timestamp}">{$todorow.timestamp_ago}</span>
				</td>
				<td class="trackerList small {if $todorow.unseen}ModCommUnseen{/if}" width="288">{$todorow.description}</td>
			</tr>
		{/foreach}
		</tbody>
	{/foreach}

</table>
{/if}

{if is_array($TODOLIST_DURATION) && count($TODOLIST_DURATION) > 0} {* crmv@167234 *}
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="hdrNameBg" id="todos_list_duration" style="display:none">
	{foreach item=todoperiod key=duration from=$TODOLIST_DURATION}
		{counter assign=rowid}
		{assign var=rowidstr value="todos_list_tbody_$rowid"}
		{assign var=period_count value=$todoperiod|@count}
		{if $period_count >= 2}
			{assign var=hidenext value=true}
		{else}
			{assign var=hidenext value=false}
		{/if}
		<tr id="{$rowidstr}_toggle">
			<td colspan="3" onclick="toggleTodoPeriod('{$rowidstr}');" class="level3Bg" style="cursor:pointer;">
				{if $hidenext}
					{assign var=detailImg value='open_details.png'}
				{else}
					{assign var=detailImg value='close_details.png'}
				{/if}
				<img width="15" border="0" align="top" src="{$detailImg|resourcever}" id="{$rowidstr}_img">&nbsp;{$duration} ({$period_count})
			</td>
		</tr>

		<tbody id="{$rowidstr}" style="display:{if $hidenext eq true}none{else}block{/if}">
		{foreach item=todorow from=$todoperiod}
			<tr id="todos2_list_row_{$todorow.activityid}">
				<td class="trackerListBullet small" align="center" width="12">
					<input type="checkbox" id="todo2_{$todorow.activityid}" onClick="closeTodo({$todorow.activityid},this.checked);" title="{'LBL_COMPLETED'|getTranslatedString:'Calendar'}" style="cursor: pointer;" />
				</td>
				<td class="trackerList small {if $todorow.unseen}ModCommUnseen{/if}" width="200">
					<a href="index.php?module=Calendar&action=DetailView&record={$todorow.activityid}">{$todorow.subject}</a>
					<br />{$todorow.expired_str} <a href="javascript:;" class="{if $todorow.unseen}ModCommUnseen{/if}" style="color: gray; text-decoration:none;" title="{$todorow.timestamp}">{$todorow.timestamp_ago}</span>
				</td>
				<td class="trackerList small {if $todorow.unseen}ModCommUnseen{/if}" width="288">{$todorow.description}</td>
			</tr>
		{/foreach}
		</tbody>
	{/foreach}

</table>
{/if}