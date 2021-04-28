{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript" charset="utf-8">
var moduleName = '{$entityName}';
var eventStatus = '{$task->status}';
var eventType = '{$task->eventType}';
</script>
<script src="modules/com_workflow/resources/createeventtaskscript.js" type="text/javascript" charset="utf-8"></script>


<div id="view">
	<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.LBL_EVENTNAME}</td>
		<td class='dvtCellInfoM'><input type="text" name="eventName" value="{$task->eventName}" id="workflow_eventname" class="detailedViewTextBox"></td>
	</tr>
	<tr valign="top">
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.Description}</td>
		<td class='dvtCellInfo'><textarea name="description" rows="8" cols="40" class='detailedViewTextBox'>{$task->description}</textarea></td>
	</tr>
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.Status}</td>
		<td class='dvtCellInfo'>
			<span id="event_status_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select id="event_status" value="{$task->status}" name="status" class="detailedViewTextBox" style="display: none;"></select>
		</td>
	</tr> 
	<tr>
		<td class='dvtCellLabel' align="right" width=15% nowrap="nowrap">{$MOD.LBL_EVENTTYPE}</td>
		<td class='dvtCellInfo'>
			<span id="event_type_busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
			<select id="event_type" value="{$task->eventType}" name="eventType" class="detailedViewTextBox" style="display: none;"></select>
		</td>
	</tr>
	<tr><td colspan="2"><hr size="1" noshade="noshade" /></td></tr>
	<tr>
		<td align="right">{'Start date and time'|getTranslatedString:'Calendar'}</td>
		<td><input type="hidden" name="startTime" value="{$task->startTime}" id="workflow_time" style="width:60px"  class="time_field"></td>
	</tr>
	<tr>
		<td align="right"></td>
		<td>
			<input type="text" name="startDays" value="{$task->startDays}" id="start_days" style="width:30px" class="small"> {$MOD.LBL_DAYS}
			<select name="startDirection" value="{$task->startDirection}" class="small">
				{* crmv@69992 *}
				<option {if $task->startDirection eq 'after'}selected{/if} value='after' >{$MOD.LBL_AFTER}</option>
				<option {if $task->startDirection eq 'before'}selected{/if} value='before'>{$MOD.LBL_BEFORE}</option>
				{* crmv@69992e *}
			</select>
			<select name="startDatefield" value="{$task->startDatefield}" class="small">
				{foreach key=name item=label from=$dateFields}
				<option value='{$name}' {if $task->startDatefield eq $name}selected{/if}>
					{$label}
				</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td align="right">{'End date and time'|getTranslatedString:'Calendar'}</td>
		<td><input type="hidden" name="endTime" value="{$task->endTime}" id="end_time" style="width:60px" class="time_field"></td>
	</tr>
	<tr>
		<td align="right"></td>
		<td><input type="text" name="endDays" value="{$task->endDays}" id="end_days" style="width:30px" class="small"> {$MOD.LBL_DAYS}
			<select name="endDirection" value="{$task->endDirection}" class="small">
				{* crmv@69992 *}
				<option {if $task->endDirection eq 'after'}selected{/if} value='after' >{$MOD.LBL_AFTER}</option>
				<option {if $task->endDirection eq 'before'}selected{/if} value='before' >{$MOD.LBL_BEFORE}</option>
				{* crmv@69992e *}
			</select>
			<select name="endDatefield" value="{$task->endDatefield}" class="small">
				{foreach key=name item=label from=$dateFields}
				<option value='{$name}' {if $task->endDatefield eq $name}selected{/if}>
					{$label}
				</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td nowrap align=right width=20% valign=top>{$MOD.LBL_REPEAT}</td>
		<td nowrap width=80% valign=top>
			<table border=0 cellspacing=0 cellpadding=0>
			<tr>
				<td width=20><input type="checkbox" name="recurringcheck" onClick="showhideCalendar('repeatOptions')" {if $task->recurringcheck eq 'on'}checked{/if}></td> {* crmv@69922 *}
				<td>{$MOD.LBL_ENABLE_REPEAT}</td>
			</tr>
			<tr>
				<td colspan=2>
				<div id="repeatOptions" style="display:{if $task->recurringcheck neq 'on'}none{else}block{/if}">
					<table border=0 cellspacing=0 cellpadding=2 bgcolor="#FFFFFF">
					<tr>
					<td>
						{$MOD.LBL_REPEATEVENT}
					</td>
					<td><select name="repeat_frequency" class="small">
					{section name="numdays" start=1 loop=15}
					<option value="{$smarty.section.numdays.index}" {if $task->repeat_frequency eq $smarty.section.numdays.index}selected{/if}>{$smarty.section.numdays.index}</option>
					{/section}
					</select></td>
					<td>
						<select name="recurringtype" onChange="rptoptDisp(this)" class="small">
							<option value="Daily" {if $task->recurringtype eq "Daily"}selected{/if}>{$MOD.LBL_DAYS}</option>
							<option value="Weekly" {if $task->recurringtype eq "Weekly"}selected{/if}>{$MOD.LBL_WEEKS}</option>
							<option value="Monthly" {if $task->recurringtype eq "Monthly"}selected{/if}>{$MOD.LBL_MONTHS}</option>
							<option value="Yearly" {if $task->recurringtype eq "Yearly"}selected{/if}>{$MOD.LBL_YEAR}</option>
						</select>
						<!-- Limit for Repeating Event -->
						<b>{$MOD.LBL_UNTIL}:</b>
					</td>
					<td align="left">
						<input type="text" name="calendar_repeat_limit_date" id="calendar_repeat_limit_date" class="form-control" size="11" maxlength="10" value="{$task->calendar_repeat_limit_date|@getDisplayDate}">
					</td>
					<td align="left">
						<i class="vteicon md-link" id="jscal_trigger_calendar_repeat_limit_date" title="{$MOD.LBL_SET_DATE}">events</i>
						<script type="text/javascript">
						{* crmv@82419 *}
						(function() {ldelim}
							setupDatePicker('calendar_repeat_limit_date', {ldelim}
								trigger: 'jscal_trigger_calendar_repeat_limit_date',
								date_format: "{$dateFormat|replace:"%d":"DD"|replace:"%m":"MM"|replace:"%Y":"YYYY"}",
								language: "{$APP.LBL_JSCALENDAR_LANG}",
							{rdelim});
						{rdelim})();
						</script>
						<!-- END -->
					</td>
					</tr>
					</table>

					<div id="repeatWeekUI" style="display:{if $task->recurringtype neq "Weekly"}none{else}block{/if};">
					<table border=0 cellspacing=0 cellpadding=2>
						<tr>
					<td><input name="sun_flag" value="sunday" type="checkbox" {if $task->sun_flag eq "sunday"}checked{/if}></td><td>{$MOD.LBL_SM_SUN}</td>
					<td><input name="mon_flag" value="monday" type="checkbox" {if $task->mon_flag eq "monday"}checked{/if}></td><td>{$MOD.LBL_SM_MON}</td>
					<td><input name="tue_flag" value="tuesday" type="checkbox" {if $task->tue_flag eq "tuesday"}checked{/if}></td><td>{$MOD.LBL_SM_TUE}</td>
					<td><input name="wed_flag" value="wednesday" type="checkbox" {if $task->wed_flag eq "wednesday"}checked{/if}></td><td>{$MOD.LBL_SM_WED}</td>
					<td><input name="thu_flag" value="thursday" type="checkbox" {if $task->thu_flag eq "thursday"}checked{/if}></td><td>{$MOD.LBL_SM_THU}</td>
					<td><input name="fri_flag" value="friday" type="checkbox" {if $task->fri_flag eq "friday"}checked{/if}></td><td>{$MOD.LBL_SM_FRI}</td>
					<td><input name="sat_flag" value="saturday" type="checkbox" {if $task->sat_flag eq "saturday"}checked{/if}></td><td>{$MOD.LBL_SM_SAT}</td>
						</tr>
					</table>
					</div>

					<div id="repeatMonthUI" style="display:{if $task->recurringtype neq "Monthly"}none{else}block{/if};">
					<table border=0 cellspacing=0 cellpadding=2 bgcolor="#FFFFFF">
						<tr>
							<td>
								<table border=0 cellspacing=0 cellpadding=2>
									<tr>
										<td><input type="radio" {if $task->repeatMonth eq "date"}checked{/if} name="repeatMonth" value="date"></td><td>{$MOD.on}</td><td><input type="text" class=textbox style="width:20px" value="{$task->repeatMonth_date}" name="repeatMonth_date" ></td><td>{assign var=languageKey value='day of the month'}{$MOD[$languageKey]}</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td>
								<table border=0 cellspacing=0 cellpadding=2>
									<tr>
										<td>
											<input type="radio" {if $task->repeatMonth eq "day"}checked{/if} name="repeatMonth" value="day"></td>
										<td>{$MOD.on}</td>
										<td>
											<select name="repeatMonth_daytype">
												<option value="first" {if $task->repeatMonth_daytype eq "first"}selected{/if}>{$MOD.First}</option>
												<option value="last" {if $task->repeatMonth_daytype eq "last"}selected{/if}>{$MOD.Last}</option>
											</select>
										</td>
										<td>
											<select name="repeatMonth_day">
												<option value=1 {if $task->repeatMonth_day eq 1}selected{/if}>{$MOD.LBL_DAY1}</option>
												<option value=2 {if $task->repeatMonth_day eq 2}selected{/if}>{$MOD.LBL_DAY2}</option>
												<option value=3 {if $task->repeatMonth_day eq 3}selected{/if}>{$MOD.LBL_DAY3}</option>
												<option value=4 {if $task->repeatMonth_day eq 4}selected{/if}>{$MOD.LBL_DAY4}</option>
												<option value=5 {if $task->repeatMonth_day eq 5}selected{/if}>{$MOD.LBL_DAY5}</option>
												<option value=6 {if $task->repeatMonth_day eq 6}selected{/if}>{$MOD.LBL_DAY6}</option>
											</select>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					</div>

				</div>

				</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
</div>