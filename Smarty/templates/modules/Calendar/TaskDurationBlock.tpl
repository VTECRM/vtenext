{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<table width="100%" align="center">
	<tr>
		<td width="50%" valign="top">
			<table width="100%" align="center">
				<tr><td><b>{$MOD.LBL_TODODATETIME}</b></td></tr>
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
									<input name="date_start" id="date_start" class="form-control" onChange="dochange('date_start','due_date');" value="{$date_val}" type="text">
								</td>
								<td style="padding-right:2px;">
									<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_date_start">events</i>
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
								setupDatePicker('date_start', {ldelim}
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
		
		<td width="50%" valign="top">
			<table width="100%" align="center">
				<tr><td colspan=3><b>{$LABEL.due_date}</b></td></tr>
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
									<input name="due_date" id="due_date" class="form-control" value="{$date_val}" type="text">
								</td>
								<td style="padding-right:2px;">
									<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_due_date">events</i>
								</td>
								{* crmv@82419e crmv@100585e *}
							</tr>
						</table>
						{foreach key=date_fmt item=date_str from=$calsecondvalue.due_date}
							{assign var=date_vl value="$date_fmt"}
							{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
						{/foreach}
	      				<script type="text/javascript">
						{* crmv@82419 *}
						(function() {ldelim}
							setupDatePicker('due_date', {ldelim}
								trigger: 'jscal_trigger_due_date',
								date_format: "{$dateStr|strtoupper}",
								language: "{$APP.LBL_JSCALENDAR_LANG}",
							{rdelim});
						{rdelim})();
			   			{* crmv@82419e *}
						</script>
					</div>
   				</td></tr>
			</table>
		</td>
	</tr>
</table>

{* crmv@36871 *}
<table width=100% align="center">
	<tr><td align=left ><b>{$MOD.ExpDuration}</b></td></tr>
</table>

<table width="100%" align="center">
	<tr>
		<td width="50%">
			<div class="dvtCellInfo">
				{if count($EXPDURATIONPLIST) > 0}
					<select id="exp_duration" name="exp_duration" class="detailedViewTextBox">
						{foreach item=plabel key=pkey from=$EXPDURATIONPLIST}
							<option value="{$pkey}" {if $pkey eq $ACTIVITYDATA.exp_duration}selected="selected"{/if}>{$plabel}</option>
						{/foreach}
					</select>
				{/if}
			</div>
		</td>
		<td width="50%"></td>
	</tr>
</table>
{* crmv@36871e *}