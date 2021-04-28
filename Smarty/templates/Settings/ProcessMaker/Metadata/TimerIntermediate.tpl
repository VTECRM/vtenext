{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@97566 crmv@182148 crmv@185361 *} {* crmv@181170 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}
{if !empty($ERROR_STRING)}
	{include file="Error.tpl" DESCR=$ERROR_STRING}
{else}
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" type="text/javascript" src="include/js/vtlib.js"></script>	{* crmv@92272 *}
<script src="modules/Settings/ProcessMaker/resources/ActionTaskScript.js" type="text/javascript"></script>
{literal}
<style type="text/css">
	/* crmv@112299 */
	.populateField, .populateFieldGroup {
		font-size:12px;
	}
	.populateFieldGroup option {
		font-weight:bold;
	}
	.populateFieldGroup option:nth-child(1) {
		font-weight:normal;
	}
	/* crmv@112299e */
</style>
{/literal}
<select id='task-fieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
	{if !empty($SDK_CUSTOM_FUNCTIONS)}
		{foreach key=SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL item=SDK_CUSTOM_FUNCTIONS_BLOCK from=$SDK_CUSTOM_FUNCTIONS}
		<optgroup label="{$SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL}">
			{foreach key=k item=i from=$SDK_CUSTOM_FUNCTIONS_BLOCK}
				<option value="{$k}">{$i}</option>
			{/foreach}
		</optgroup>
		{/foreach}
	{/if}
</select>

<div id="editForm" style="padding:50px;">
	<form class="form-config-shape" shape-id="{$ID}">
		<table border=0 align="center">
			<tr>
				<td>{$START_LABEL}</td>
				{foreach name="timerOptions" item=val_arr from=$TIMEROPTIONS}
					<td>
						{assign var=start value=$val_arr[0]}
						{assign var=end value=$val_arr[1]}
						{assign var=sendname value=$val_arr[2]}
						{assign var=disp_text value=$val_arr[3]}
						{assign var=sel_val value=$val_arr[4]}
						<select name="{$sendname}" class="detailedViewTextBox">
						{section name=reminder start=$start max=$end loop=$end step=1}
							{if $smarty.section.reminder.index eq $sel_val}
								{assign var=sel_value value="SELECTED"}
							{else}
								{assign var=sel_value value=""}
							{/if}
							<OPTION VALUE="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</OPTION>
						{/section}
						</select>
					</td>
					<td>{$disp_text}</td>
				{/foreach}
			</tr>
		</table>
		<table border=0 align="center">
			<tr>
				{if $TIMER_TYPE eq 'IntermediateCatchEvent'}
				<td>
					<select name="trigger_direction" class="detailedViewTextBox">
						<option value="after" {if $TIMERTRIGGER.direction eq 'after'}selected{/if}>{'LBL_AFTER'|getTranslatedString:'Settings'}</option>
						<option value="before" {if $TIMERTRIGGER.direction eq 'before'}selected{/if}>{'LBL_BEFORE'|getTranslatedString:'Settings'}</option>
					</select>
				</td>
				<td>
					<select name="trigger_date_type" class="detailedViewTextBox" onchange="ProcessMakerScript.changeTimerTriggerDataType(this)">
						<option value="" {if $TIMERTRIGGER.date_type eq ''}selected{/if}>{'LBL_NOW'|getTranslatedString}</option>
						<option value="date" {if $TIMERTRIGGER.date_type eq 'date'}selected{/if}>{'date'|getTranslatedString}</option>
						<option value="other" {if $TIMERTRIGGER.date_type eq 'other'}selected{/if}>{'LBL_OTHER'|getTranslatedString:'Users'}</option>
					</select>
				</td>
				<td>
					<div class="trigger_date_values" id="trigger_date_date" style="{if $TIMERTRIGGER.date_type neq 'date'}display:none{/if}">
						<div style="float:left">
							{include file="EditViewUI.tpl" uitype=5 fldname="trigger_date_value" fldvalue=$TIMERTRIGGER.date_ui.fldvalue secondvalue=$TIMERTRIGGER.date_ui.secondvalue NOLABEL=true DIVCLASS="dvtCellInfo"}
						</div>
						<div style="float:left">
							{include file="EditViewUI.tpl" uitype=73 fldname="trigger_hour_value" fldvalue=$TIMERTRIGGER.hour_ui.fldvalue NOLABEL=true DIVCLASS="dvtCellInfo"}
						</div>
					</div>
					<div class="trigger_date_values" id="trigger_date_other" {if $TIMERTRIGGER.date_type neq 'other'}style="display:none"{/if}>
						<div>
							<div class="editoptions" fieldname="trigger_other_value" optionstype="fieldnames" style="float:right;"></div>
						</div>
						{include file="EditViewUI.tpl" uitype=1 fldname="trigger_other_value" fldvalue=$TIMERTRIGGER.other_ui.fldvalue NOLABEL=true DIVCLASS="dvtCellInfo"}
					</div>
				</td>
				{/if}
				<td>{$END_LABEL} <b>{$NEXT_ELEMENT}</b></td>
			</tr>
		</table>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {ldelim}
	ActionCreateScript.loadForm('Home','{$PROCESSID}','{$ID}','TimerIntermediate','',false,false);
{rdelim});
</script>
{/if}