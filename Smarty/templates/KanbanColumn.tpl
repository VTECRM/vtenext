{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@OPER6288 *}

{assign var=ENTRIES value=$KANBAN_COL.entries}
{assign var=OTHER_INFO value=$KANBAN_COL.other_information}
{if !empty($ENTRIES)}
	{foreach key=ID item=entry from=$ENTRIES}
		<li id="{$ID}" class="kanbanSortableItem">
			{assign var=listentry1 value=""}
			{assign var=listentry2 value=""}
			{foreach name=listentry key=index item=value from=$entry}
				{* crmv@201232 *}
				{if $value neq '' && $index neq 'clv_color' && $index neq 'clv_status' && $index neq 'clv_foreground'} {* crmv@105538 *}
					{assign var=currentIndex value=($smarty.foreach.listentry.iteration-1)}
					{if $currentIndex eq $KANBAN_COL.user_field_position && $KANBAN_COL.user_field_position neq ''} {* crmv@115214 *}
						{assign var=smownerid value=$value}
					{elseif $currentIndex|in_array:$KANBAN_COL.name_field_position}
						{if empty($listentry1)}
							{assign var=listentry1 value=$value}
						{else}
							{assign var=listentry1 value=$listentry1|cat:" "|cat:$value}
						{/if}
					{else}
						{if empty($listentry2)}
							{assign var=listentry2 value=$value}
						{else}
							{assign var=listentry2 value=$listentry2|cat:", "|cat:$value}
						{/if}
					{/if}
				{/if}
				{* crmv@201232e *}
			{/foreach}
			{* crmv@105538 *}
			{if $entry.clv_color}
				<div class="kanbanColorBar" style="background-color:{$entry.clv_color}" title="{$entry.clv_status}"></div>
			{/if}
			{* crmv@105538e *}
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr valign="top">
					{if $smarty.foreach.kanban_foreach.total le 6}
					<td width="35">
						<div style="text-align:center">{$smownerid|getUserAvatarImg}</div>
					</td>
					{/if}
					<td>
						{if $smarty.foreach.kanban_foreach.total gt 6}
							<div style="padding-bottom:2px">{$smownerid|getUserAvatarImg}</div>
						{/if}
						<div class="listMessageSubject" style="font-weight:bold;">
							<a href="javascript:;" onCliCk="KanbanView.showPreView('{$MODULE}','{$ID}')">{$listentry1|strip_tags}</a>
						</div>
						<div class="gray linkNoPropagate">{$listentry2}</div>
					</td>
				</tr>
				<tr>
					<td align="right" colspan="2">
						{if $OTHER_INFO.$ID.related_count neq ''}
							<span class="badge pull-right" title="{$OTHER_INFO.$ID.related_module}">{$OTHER_INFO.$ID.related_count}</span>
						{/if}
					</td>
				</tr>
			</table>
		</li>
	{/foreach}
{else}
	{*
	<table border="0" cellspacing="0" cellpadding="5" width="100%">
		<tr><td>{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND}</td></tr>
	</table>
	*}
{/if}