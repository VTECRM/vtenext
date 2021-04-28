{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<table id="date_table" width="100%" align="center">
	<tr>
		<td width=50% id="date_table_firsttd">
			<table width="100%" align="center">
				<tr><td><b>{$MOD.LBL_EVENTSTAT}</b></td></tr>
				<tr id="time_event_start"><td><div class="dvtCellInfo">{$STARTHOUR}</div></td></tr>
				<tr><td nowrap>
					<div class="dvtCellInfo">
						{foreach key=date_value item=time_value from=$ACTIVITYDATA.date_start}
							{assign var=date_val value="$date_value"}
							{assign var=time_val value="$time_value"}
						{/foreach}
						<table width="100%">
							<tr>
								{* crmv@82419 crmv@100585 *}
								<td>
									<input type="text" name="date_start" id="jscal_field_date_start" class="form-control" onChange="dochange('jscal_field_date_start','jscal_field_due_date');" value="{$date_val}">
								</td>
								<td style="padding-right:2px;">
									<i class="vteicon md-link" id="jscal_trigger_date_start" title="{$MOD.LBL_SET_DATE}">events</i>
								</td>
								{* crmv@82419e *}
							</tr>
						</table>
						{foreach key=date_fmt item=date_str from=$calsecondvalue.date_start}
							{assign var=date_vl value="$date_fmt"}
							{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
						{/foreach}
						<script type="text/javascript">
							{* crmv@82419 *}
							(function() {ldelim}
								setupDatePicker('jscal_field_date_start', {ldelim}
									trigger: 'jscal_trigger_date_start',
									date_format: "{$dateStr|strtoupper}",
									language: "{$APP.LBL_JSCALENDAR_LANG}",
								{rdelim});
							{rdelim})();
							{* crmv@82419e crmv@100585e *}
						</script>
					</div>
				</td></tr>
			</table>
		</td>
		<td width=50% id="date_table_secondtd">
			<table width="100%" align="center">
				<tr><td><b>{$MOD.LBL_EVENTEDAT}</b></td></tr>
				<tr id="time_event_end"><td><div class="dvtCellInfo">{$ENDHOUR}</div></td></tr>
				<tr><td>
					<div class="dvtCellInfo">
						{foreach key=date_value item=time_value from=$ACTIVITYDATA.due_date}
							{assign var=date_val value="$date_value"}
							{assign var=time_val value="$time_value"}
						{/foreach}
						<table width="100%">
							<tr>
								{* crmv@82419 crmv@100585 *}
								<td>
									<input type="text" name="due_date" id="jscal_field_due_date" class="form-control" value="{$date_val}">
								</td>
								<td style="padding-right:2px;">
									<i class="vteicon md-link" id="jscal_trigger_due_date" title="{$MOD.LBL_SET_DATE}">events</i>
								</td>
								{* crmv@82419e *}
							</tr>
						</table>
						{foreach key=date_format item=date_str from=$calsecondvalue.due_date}
							{assign var=dateFormat value="$date_format"}
							{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
						{/foreach}
						<script type="text/javascript">
							{* crmv@82419 *}
							(function() {ldelim}
								setupDatePicker('jscal_field_due_date', {ldelim}
									trigger: 'jscal_trigger_due_date',
									date_format: "{$dateStr|strtoupper}",
									language: "{$APP.LBL_JSCALENDAR_LANG}",
								{rdelim});
							{rdelim})();
							{* crmv@82419e crmv@100585e *}
						</script>
					</div>
				</td></tr>
			</table>
		</td>
		<td width="100%" style="display:none;" id="date_table_thirdtd">
			<table width="100%" align="center">
				<tr><td>
					<div class="checkbox">
						<label><input type="checkbox" name="followup">&nbsp;<b>{$MOD.LBL_HOLDFOLLOWUP}</b></label>
					</div>
				</td></tr>
				<tr><td><div class="dvtCellInfo">{$FOLLOWUP}</div></td></tr>
				<tr><td>
					<div class="dvtCellInfo">
						{foreach key=date_value item=time_value from=$ACTIVITYDATA.due_date}
							{assign var=date_val value="$date_value"}
							{assign var=time_val value="$time_value"}
						{/foreach}
						<table width="100%">
							<tr>
								{* crmv@82419 crmv@100585 *}
								<td>
									<input type="text" name="followup_date" id="jscal_field_followup_date" class="form-control" value="{$date_val}">
								</td>
								<td style="padding-right:2px;">
									<i class="vteicon md-link" id="jscal_trigger_followup_date" title="{$MOD.LBL_SET_DATE}">events</i>
								</td>
								{* crmv@82419e *}
							</tr>
						</table>
						{foreach key=date_fmt item=date_str from=$calsecondvalue.due_date}
							{assign var=date_vl value="$date_fmt"}
							{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
						{/foreach}
						<script type="text/javascript">
							{* crmv@82419 *}
							(function() {ldelim}
								setupDatePicker('jscal_field_followup_date', {ldelim}
									trigger: 'jscal_trigger_followup_date',
									date_format: "{$dateStr|strtoupper}",
									language: "{$APP.LBL_JSCALENDAR_LANG}",
								{rdelim});
							{rdelim})();
							{* crmv@82419e crmv@100585e *}
						</script>
					</div>
				</td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="3">
		  <div class="checkbox">
			<label for="is_all_day_event">
		      <input type="checkbox" name="is_all_day_event" onclick="presetAllDayEvent(this.checked)" id="is_all_day_event">
		        &nbsp;{'All day'|getTranslatedString:$MODULE}
		      </label>
		    </div>
		</td>
	</tr>
</table>