{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{literal}
	<script type="text/javascript">
		function toggleTodoPeriod1(id) {
			jQuery('#' + id).toggle();
			var open = jQuery('#' + id).is(':visible');
			var materialIcon = open ? 'keyboard_arrow_down' : 'keyboard_arrow_right';
			jQuery('#' + id + '_img').html(materialIcon);
		}
		function todoShowByDate() {
			jQuery('#todo_btn_date').parent().removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
			jQuery('#todo_btn_duration').parent().removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#todos_list').show();
			jQuery('#todos_list_duration').hide();
		}

		function todoShowByDuration() {
			jQuery('#todo_btn_date').parent().removeClass('dvtSelectedCell').addClass('dvtUnSelectedCell');
			jQuery('#todo_btn_duration').parent().removeClass('dvtUnSelectedCell').addClass('dvtSelectedCell');
			jQuery('#todos_list').hide();
			jQuery('#todos_list_duration').show();
		}
		
		jQuery(document).ready(function() {
			jQuery('ul.tabs').tabs();
		});
	</script>
	
	<style>
		#todos_list, #todos_list_duration {
			position: relative;
			height: calc(100% - 150px);
		}
	</style>
{/literal}

<ul class="vte-collection with-header">

	<li class="collection-header">
		<h4 class="vcenter">{'Todos'|getTranslatedString:'ModComments'}</h4>
		<div class="vcenter pull-right">
			{include file="LoadingIndicator.tpl" LIID="indicatorTodos" LIEXTRASTYLE="display:none;"}
			<input id="todos_button" type="button" value="{'LBL_ALL'|getTranslatedString}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="get_more_todos();"> {* crmv@175394 crmv@185303 *}
			<input type="button" value="{'LBL_CREATE'|getTranslatedString}" name="button" class="crmbutton small create" title="{'LBL_CREATE'|getTranslatedString}" onClick="NewQCreate('Calendar');">
		</div>
	</li>
	
</ul>

<div class="col-sm-12">
	<ul class="tabs tabs-fixed-width" id="menuTabs">
		<li class="tab"><a href="#todos_list">{"TodoByDate"|getTranslatedString}</a></li>
		<li class="tab"><a href="#todos_list_duration">{"TodoByDuration"|getTranslatedString}</a></li>
	</ul>
</div>
	
<div id="todos_list" class="col-sm-12">

{if is_array($TODOLIST_DATE) && count($TODOLIST_DATE) > 0} {* crmv@167234 *}
<table class="table table-hover">
	{foreach item=todoperiod key=timestampAgo from=$TODOLIST_DATE name="todo"}
		{counter assign=rowid}
		{assign var=rowidstr value="todos_list_tbody_$rowid"}
		{assign var=period_count value=$todoperiod|@count}
		
		{if $period_count >= $TODOLIST_TODOSINPERIOD}
			{assign var=hidenext value=true}
			<tr id="{$rowidstr}_toggle">
				<td colspan="3" onclick="toggleTodoPeriod1('{$rowidstr}');" style="cursor:pointer">
					<div class="vcenter">
						<i id="{$rowidstr}_img" class="vteicon">keyboard_arrow_right</i>
					</div><!-- 
				 	 --><div class="vcenter">&nbsp;{$timestampAgo} ({$period_count})</div>
				</td>
			</tr>
		{else}
			{assign var=hidenext value=false}
		{/if}
		
		<tbody id="{$rowidstr}" style="display:{if $hidenext eq true}none{else}table-row-group{/if}">
			{foreach item=todorow from=$todoperiod}
				<tr id="todos_list_row_{$todorow.activityid}">
					<td width="10%" align="center">
						<div class="checkbox">
							<label for="todo_{$todorow.activityid}">
								<input type="checkbox" id="todo_{$todorow.activityid}" onClick="closeTodo({$todorow.activityid},this.checked);" title="{'LBL_COMPLETED'|getTranslatedString:'Calendar'}" style="cursor: pointer;" />
							</label>
						</div>
					</td>
					<td width="90%" class="{if $todorow.unseen}ModCommUnseen{/if}">
						<a href="index.php?module=Calendar&action=DetailView&record={$todorow.activityid}">{$todorow.subject}</a>
						{if $todorow.description neq ''}<br /><span>{$todorow.description}</span>{/if}	{* crmv@188093 *}
						<br />{$todorow.expired_str} <a href="javascript:;" class="" style="color: gray; text-decoration:none;" title="{$todorow.timestamp}">{$todorow.timestamp_ago}</span>
					</td>
				</tr>
			{/foreach}
		</tbody>
	{/foreach}
</table>
{else}

<div class="vte-collection-empty">
	<div class="collection-item">
		<div class="circle">
			<i class="vteicon nohover">assignment_turned_in</i>
		</div>
		<h4 class="title">{"LBL_NO_TODOS"|getTranslatedString}</h4>
	</div>
</div>

{/if}

</div>

<div id="todos_list_duration" style="display:none" class="col-sm-12">
{if is_array($TODOLIST_DURATION) && count($TODOLIST_DURATION) > 0} {* crmv@167234 *}
<table class="table table-hover">
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
			<td colspan="3" onclick="toggleTodoPeriod1('{$rowidstr}');" style="cursor:pointer">
				<div class="vcenter">
					<i id="{$rowidstr}_img" class="vteicon">{if $hidenext}keyboard_arrow_right{else}keyboard_arrow_down{/if}</i>
				</div><!-- 
			 	 --><div class="vcenter">&nbsp;{$timestampAgo} ({$period_count})</div>
			</td>
		</tr>

		<tbody id="{$rowidstr}" style="display:{if $hidenext eq true}none{else}table-row-group{/if}">
		{foreach item=todorow from=$todoperiod}
			<tr id="todos2_list_row_{$todorow.activityid}">
				<td width="10%" align="center">
					<div class="checkbox">
						<label for="todo2_{$todorow.activityid}">
							<input type="checkbox" id="todo2_{$todorow.activityid}" onClick="closeTodo({$todorow.activityid},this.checked);" title="{'LBL_COMPLETED'|getTranslatedString:'Calendar'}" style="cursor: pointer;" />
						</label>
					</div>
				</td>
				<td width="90%" class="{if $todorow.unseen}ModCommUnseen{/if}">
					<a href="index.php?module=Calendar&action=DetailView&record={$todorow.activityid}">{$todorow.subject}</a>
					{if $todorow.description neq ''}<br /><span>{$todorow.description}</span>{/if}	{* crmv@188093 *}
					<br />{$todorow.expired_str} <a href="javascript:;" class="" style="color: gray; text-decoration:none;" title="{$todorow.timestamp}">{$todorow.timestamp_ago}</span>
				</td>
			</tr>
		{/foreach}
		</tbody>
	{/foreach}
</table>
{else}

<div class="vte-collection-empty">
	<div class="collection-item">
		<div class="circle">
			<i class="vteicon nohover">assignment_turned_in</i>
		</div>
		<h4 class="title">{"LBL_NO_TODOS"|getTranslatedString}</h4>
	</div>
</div>
	
{/if}

</div>