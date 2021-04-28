{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP6_INTRO}</p>
</div>
<br>

{* some JS variables *}
<script type="text/javascript">
	var DataImporterVars = {ldelim}{rdelim};
	DataImporterVars['sched'] = ({$SCHED_VARS|@json_encode});
</script>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="20%"><span>{$MOD.LBL_START_EVERY}</span>&nbsp;&nbsp;</td>
		<td align="left">
			<table border="0" cellspacing="2" cellpadding="1"><tr>
				<td>
					<div class="dvtCellInfo"><input type="text" class="detailedViewTextBox" name="dimport_sched_every" id="dimport_sched_every" value="{$STEPVARS.dimport_sched_every}" maxlength="3" onkeyup="DataImporterSched.onEveryKey()" /></div>
				</td>
				<td><div class="dvtCellInfo">
					<select class="detailedViewTextBox" name="dimport_sched_everywhat" id="dimport_sched_everywhat" onchange="DataImporterSched.onIntervalChange()">
						<option value="day" id="dimport_sched_every_day" {if $STEPVARS.dimport_sched_everywhat eq "day"}selected=""{/if}>
							{if $STEPVARS.dimport_sched_every eq 1}
								{$SCHED_VARS.labels.day}
							{else}
								{$SCHED_VARS.labels.days}
							{/if}
						</option>
						<option value="hour" id="dimport_sched_every_hour" {if $STEPVARS.dimport_sched_everywhat eq "hour"}selected=""{/if}>
							{if $STEPVARS.dimport_sched_every eq 1}
								{$SCHED_VARS.labels.hour}
							{else}
								{$SCHED_VARS.labels.hours}
							{/if}
						</option>
						<option value="minute" id="dimport_sched_every_minute" {if $STEPVARS.dimport_sched_everywhat eq "minute"}selected=""{/if}>
							{if $STEPVARS.dimport_sched_every eq 1}
								{$SCHED_VARS.labels.minute}
							{else}
								{$SCHED_VARS.labels.minutes}
							{/if}
						</option>
					</select>
				</div></td>
				<td id="dimport_cell_atlabel" {if $STEPVARS.dimport_sched_everywhat eq 'minute'}style="display:none"{/if}>
					&nbsp;
					<span id="dimport_sched_atlabel">
					{if $STEPVARS.dimport_sched_everywhat eq 'hour'}
						{$SCHED_VARS.labels.at_minute}
					{else}
						{$SCHED_VARS.labels.at_hour}
					{/if}
					</span>
					&nbsp;
				</td>
				<td id="dimport_cell_at" {if $STEPVARS.dimport_sched_everywhat eq 'minute'}style="display:none"{/if}>
					<div class="dvtCellInfo">
						<input type="text" class="detailedViewTextBox" name="dimport_sched_at" id="dimport_sched_at" value="{$STEPVARS.dimport_sched_at}" maxlength="5" />
					</div>
				</td>
			</tr></table>
		</td>
		<td width="50">&nbsp;</td>

	</tr>
	
</table>