{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript" charset="utf-8">
var moduleName = '{$entityName}';
var taskStatus = '{$task->status}';
var taskPriority = '{$task->priority}';
</script>

<script src="modules/com_workflow/resources/createtodotaskscript.js" type="text/javascript" charset="utf-8"></script>

<div id="view">
	<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.LBL_TODO}</td>
		<td class='dvtCellInfoM'><input type="text" name="todo" value="{$task->todo}" id="workflow_todo" class="detailedViewTextBox"></td>
	</tr>
	<tr valign="top">
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.Description}</td>
		<td class='dvtCellInfo'><textarea name="description" rows="8" cols="40" class='detailedViewTextBox'>{$task->description}</textarea></td>
	</tr>
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.Status}</td>
		<td class='dvtCellInfo'>
			<span id="task_status_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select id="task_status" value="{$task->status}" name="status" class="detailedViewTextBox" style="display: none;"></select>
		</td>
	</tr> 
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.Priority}</td>
		<td class='dvtCellInfo'>
			<span id="task_priority_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select id="task_priority" value="{$task->priority}" name="priority" class="detailedViewTextBox" style="display: none;"></select>
		</td>
	</tr>
	<tr><td colspan="2"><hr size="1" noshade="noshade" /></td></tr>
	<tr>
		<td align="right">{$MOD.LBL_TODODATETIME}</td>
		<td><input type="hidden" name="time" value="{$task->time}" id="workflow_time" style="width:60px" class="time_field"></td>
	</tr>
	<tr>
		<td align="right"></td>
		<td>
			<input type="text" name="days" value="{$task->days}" id="days" style="width:30px" class="small"> {$MOD.LBL_DAYS}
			<select name="direction" value="{$task->direction}" class="small">
				{* crmv@69992 *}
				<option {if $task->direction eq "after"}selected{/if} value='after'>{$MOD.LBL_AFTER}</option>
				<option {if $task->direction eq "before"}selected{/if} value='before'>{$MOD.LBL_BEFORE}</option>
				{* crmv@69992e *}
			</select>
			<select name="datefield" value="{$task->datefield}" class="small">
			{foreach key=name item=label from=$dateFields}
				<option value='{$name}' {if $task->datefield eq $name}selected{/if}>
					{$label}
				</option>
			{/foreach}
			</select>
			{*(The same value is used for the start date)*}
		</td>
		</tr>
	</table>
</div>