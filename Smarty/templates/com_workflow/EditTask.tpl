{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{include file='com_workflow/Header.tpl'}
<script src="modules/{$module->name}/resources/jquery.timepicker.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/{$module->name}/resources/edittaskscript.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
	fn.addStylesheet('modules/{$module->name}/resources/style.css');
	var returnUrl = '{$returnUrl}';
	var validator;
	edittaskscript(jQuery);
</script>

<!--Error message box popup-->
{include file='com_workflow/ErrorMessageBox.tpl'}
<!--Done popups-->

{include file='SetMenu.tpl'}
<div id="view">
	{include file='com_workflow/ModuleTitle.tpl'}
	<form name="new_task" id="new_task_form" method="post" onsubmit="VteJS_DialogBox.block();">
	
		<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td class="big" nowrap="nowrap">
					<strong>{$MOD.LBL_SUMMARY}</strong>
				</td>
				<td class="small" align="right">
					<input type="submit" name="{$APP.LBL_SAVE_LABEL}" class="crmButton small save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" id="save">
					<input type="button" id="edittask_cancel_button" class="crmbutton small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
				</td>
			</tr>
		</table>
	
		<table border="0" cellpadding="2" cellspacing="0" width="100%">
			<tr>
				<td align=right width=15% nowrap="nowrap">
					{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_TASK_TITLE}
				</td>
				<td align="left">
					<div class="dvtCellInfo">
						<input type="text" class="detailedViewTextBox" name="summary" value="{$task->summary}" id="save_summary">
					</div>
				</td>
			</tr>
			<tr>
				<td align=right width=15% nowrap="nowrap">
					{include file="FieldHeader.tpl" label=$MOD.LBL_PARENT_WORKFLOW}
				</td>
				<td align="left">
					<div class="dvtCellInfoOff">
						{$workflow->description} {* crmv@121317 *}
						<input type="hidden" name="workflow_id" value="{$workflow->id}" id="save_workflow_id">
					</div>
				</td>
			</tr>
			<tr>
				<td align=right width=15% nowrap="nowrap">
					{include file="FieldHeader.tpl" label=$MOD.LBL_STATUS}
				</td>
				<td align="left">
					<div class="dvtCellInfo">
						<select name="active" class="detailedViewTextBox">
							<option value="true">{$MOD.LBL_ACTIVE}</option>
							<option value="false" {if not $task->active}selected{/if}>{$MOD.LBL_INACTIVE}</option>
						</select> 
					</div>
				</td>
			</tr>
		</table>
		{* crmv@22921 *}	{* crmv@32366 *}
		{if not $task->executeImmediately}
			<table border="0" cellpadding="5" cellspacing="0" width="100%" class="small">
			<tr>
				<td width='15%' nowrap="nowrap">
					<input type="checkbox" name="check_select_date" value="" id="check_select_date" {if $trigger neq null}checked{/if}> 
					<label for="check_select_date">{$MOD.MSG_EXECUTE_TASK_DELAY}</label>
				</td>
				<td>
					<div id="select_date" style="{if $trigger eq null}display:none;{/if}">
						<input type="text" name="select_date_days" value="{$trigger.days}" id="select_date_days" class="small"> {$MOD.LBL_DAYS}
						<select name="select_date_direction" class="small">
							<option {if $trigger.direction eq 'after'}selected{/if} value='after'>{$MOD.LBL_AFTER}</option>
							<option {if $trigger.direction eq 'before'}selected{/if} value='before'>{$MOD.LBL_BEFORE}</option>
						</select> 
						<select name="select_date_field" class="small">
						{*//crmv@28874*}
							{foreach key=name item=label from=$dateFields}
								<option value='{$name}' {if $trigger.field eq $name}selected{/if}> 
									{$label}
								</option>
							{/foreach}
						{*//crmv@28874 e*}
						</select>					
					</div>				
				</td>
			</tr>
			</table>
		{/if}
		{* crmv@22921e *}	{* crmv@32366e *}
		<table class="tableHeading" border="0"  width="100%" cellspacing="0" cellpadding="5">
			<tr>
				<td class="big" nowrap="nowrap">
					<strong>{$MOD.LBL_TASK_OPERATIONS}</strong>
				</td>
			</tr>
		</table>
{include file="$taskTemplate"}
		<input type="hidden" name="save_type" value="{$saveType}" id="save_save_type">
{if $edit}
		<input type="hidden" name="task_id" value="{$task->id}" id="save_task_id">
{/if}
		<input type="hidden" name="task_type" value="{$taskType}" id="save_task_type">
		<input type="hidden" name="action" value="savetask" id="save_action">
		<input type="hidden" name="module" value="{$module->name}" id="save_module">
		<input type="hidden" name="return_url" value="{$returnUrl}" id="save_return_url">
	</form>
</div>
{include file='com_workflow/Footer.tpl'}