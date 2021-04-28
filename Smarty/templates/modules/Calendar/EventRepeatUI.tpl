{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

{if $LABEL.recurringtype neq ''}
<table border=0 cellspacing=0 cellpadding=2 width=100% bgcolor="#FFFFFF">
	<tr>
		<td>
			<div class="checkbox">
				<label>
				{if $ACTIVITYDATA.recurringcheck eq 'Yes'}
					{assign var=rptstyle value='style="display:block"'}
					{if $ACTIVITYDATA.eventrecurringtype eq 'Daily'}
						{assign var=rptmonthstyle value='style="display:none"'}
						{assign var=rptweekstyle value='style="display:none"'}
					{elseif $ACTIVITYDATA.eventrecurringtype eq 'Weekly'}
						{assign var=rptmonthstyle value='style="display:none"'}
						{assign var=rptweekstyle value='style="display:block"'}
					{elseif $ACTIVITYDATA.eventrecurringtype eq 'Monthly'}
						{assign var=rptmonthstyle value='style="display:block"'}
						{assign var=rptweekstyle value='style="display:none"'}
					{elseif $ACTIVITYDATA.eventrecurringtype eq 'Yearly'}
						{assign var=rptmonthstyle value='style="display:none"'}
						{assign var=rptweekstyle value='style="display:none"'}
					{/if}
					<input type="checkbox" name="recurringcheck" id="recurringcheck" onClick="showhideCalendar('repeatOptions')" checked>
				{else}
					{assign var=rptstyle value='style="display:none"'}
					{assign var=rptmonthstyle value='style="display:none"'}
					{assign var=rptweekstyle value='style="display:none"'}
					<input type="checkbox" name="recurringcheck" id="recurringcheck" onClick="showhideCalendar('repeatOptions')">
				{/if}
				</label>
				<label for="recurringcheck">&nbsp;{$MOD.LBL_ENABLE_REPEAT}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<table cellspacing=0 cellpadding=0 width="100%">
				<tr>
					<td colspan=2>
						<div id="repeatOptions" {$rptstyle}>
							<table border=0 cellspacing=0 cellpadding=2 bgcolor="#FFFFFF" width="100%">
								<tr>
									<td>
										{$MOD.LBL_REPEATEVENT}
									</td>
									<td class="dvtCellInfo">
										<select name="repeat_frequency" class="small detailedViewTextBox">
											{section name="repeat" loop=15 start=1 step=1}
											{if $smarty.section.repeat.iteration eq $ACTIVITYDATA.repeat_frequency}
												{assign var="test" value="selected"}
											{else}
												{assign var="test" value=""}                                                                                                                                                                                                                  
											{/if}
											<option {$test} value="{$smarty.section.repeat.iteration}">{$smarty.section.repeat.iteration}</option>
											{/section}
										</select>
									</td>
									<td class="dvtCellInfo">
										<select name="recurringtype" onChange="rptoptDisp(this)" class="small detailedViewTextBox">
											<option value="Daily" {if $ACTIVITYDATA.eventrecurringtype eq 'Daily'} selected {/if}>{$MOD.LBL_DAYS}</option>
											<option value="Weekly" {if $ACTIVITYDATA.eventrecurringtype eq 'Weekly'} selected {/if}>{$MOD.LBL_WEEKS}</option>
											<option value="Monthly" {if $ACTIVITYDATA.eventrecurringtype eq 'Monthly'} selected {/if}>{$MOD.LBL_MONTHS}</option>
											<option value="Yearly" {if $ACTIVITYDATA.eventrecurringtype eq 'Yearly'} selected {/if}>{$MOD.LBL_YEAR}</option>
										</select>
									</td>
									<td>
										<!-- Limit for Repeating Event -->
										{$MOD.LBL_UNTIL}
									</td>
									<td><input type="text" name="calendar_repeat_limit_date" id="calendar_repeat_limit_date" class="form-control" value="{$FORMATTED_DATE}" ></td>
									{* crmv@100585 *}
									<td><i class="vteicon md-link" id="calendar_repeat_limit_date_trigger">event</i></td>
									{foreach key=date_fmt item=date_str from=$calsecondvalue.date_start}
										{assign var=date_vl value="$date_fmt"}
										{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
									{/foreach}
									<script type="text/javascript">
										(function() {ldelim}
											setupDatePicker('calendar_repeat_limit_date', {ldelim}
												trigger: 'calendar_repeat_limit_date_trigger',
												date_format: "{$dateStr|strtoupper}",
												language: "{$APP.LBL_JSCALENDAR_LANG}",
											{rdelim});
										{rdelim})();
									</script>
									{* crmv@100585e *}
								</tr>
							</table>
							<div id="repeatWeekUI" {$rptweekstyle}>
								<table border=0 cellspacing=0 cellpadding=2>
									<tr>
										{if $WEEKSTART eq 0} {* crmv@183418 *}
										<td>
											<div class="checkbox">
												<label>
													<input name="sun_flag" value="sunday" {$ACTIVITYDATA.week0} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_SUN}
											</div>
										</td>
										<td></td>
										{/if} {* crmv@183418 *}
										<td>
											<div class="checkbox">
												<label>
													<input name="mon_flag" value="monday" {$ACTIVITYDATA.week1} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_MON}
											</div>
										</td>
										<td></td>
										<td>
											<div class="checkbox">
												<label>
													<input name="tue_flag" value="tuesday" {$ACTIVITYDATA.week2} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_TUE}
											</div>
										</td>
										<td></td>
										<td>
											<div class="checkbox">
												<label>
													<input name="wed_flag" value="wednesday" {$ACTIVITYDATA.week3} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_WED}
											</div>
										</td>
										<td></td>
										<td>
											<div class="checkbox">
												<label>
													<input name="thu_flag" value="thursday" {$ACTIVITYDATA.week4} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_THU}
											</div>
										</td>
										<td></td>
										<td>
											<div class="checkbox">
												<label>
													<input name="fri_flag" value="friday" {$ACTIVITYDATA.week5} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_FRI}
											</div>
										</td>
										<td></td>
										<td>
											<div class="checkbox">
												<label>
													<input name="sat_flag" value="saturday" {$ACTIVITYDATA.week6} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_SAT}
											</div>
										</td>
										<td></td>
										{* crmv@183418 *}
										{if $WEEKSTART eq 1}
										<td>
											<div class="checkbox">
												<label>
													<input name="sun_flag" value="sunday" {$ACTIVITYDATA.week0} type="checkbox">
												</label>
												&nbsp;{$MOD.LBL_SM_SUN}
											</div>
										</td>
										<td></td>
										{/if}
										{* crmv@183418e *}
									</tr>
								</table>
							</div>
							<div id="repeatMonthUI" {$rptmonthstyle}>
								<table border=0 cellspacing=0 cellpadding=2 bgcolor="#FFFFFF">
									<tr>
										<td>
											<table border=0 cellspacing=0 cellpadding=2>
												<tr>
													<td>
														<div class="radio radio-primary">
															<label for="repeatMonth0">
																<input type="radio" checked id="repeatMonth0" name="repeatMonth" {if $ACTIVITYDATA.repeatMonth eq 'date'} checked {/if} value="date">
																&nbsp;{$MOD.on}
															</label>
														</div>
													</td>
													<td><input type="text" class="detailedViewTextBox" style="width:20px" value="{$ACTIVITYDATA.repeatMonth_date}" name="repeatMonth_date" ></td>
													<td>{$MOD.DAY_OF_MONTH}</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr>
										<td>
											<table border=0 cellspacing=0 cellpadding=2>
												<tr>
													<td>
														<div class="radio radio-primary">
															<label for="repeatMonth1">
																<input type="radio" id="repeatMonth1" name="repeatMonth" value="day">
																&nbsp;{$MOD.on}
															</label>
														</div>
													</td>
													<td>
														<select name="repeatMonth_daytype" class="small detailedViewTextBox">
															<option value="first" {if $ACTIVITYDATA.repeatMonth_daytype eq 'first'} selected {/if}>{$MOD.First}</option>
															{* crmv@185501 *}
															<option value="second" {if $ACTIVITYDATA.repeatMonth_daytype eq 'second'} selected {/if}>{$MOD.Second}</option>
															<option value="third" {if $ACTIVITYDATA.repeatMonth_daytype eq 'third'} selected {/if}>{$MOD.Third}</option>
															<option value="fourth" {if $ACTIVITYDATA.repeatMonth_daytype eq 'fourth'} selected {/if}>{$MOD.Fourth}</option>
															{* crmv@185501e *}
															<option value="last" {if $ACTIVITYDATA.repeatMonth_daytype eq 'last'} selected {/if}>{$MOD.Last}</option>
														</select>
													</td>
													<td>
														<select name="repeatMonth_day" class="small detailedViewTextBox">
															<option value=1 {if $ACTIVITYDATA.repeatMonth_day eq 1} selected {/if}>{$MOD.LBL_DAY1}</option>
															<option value=2 {if $ACTIVITYDATA.repeatMonth_day eq 2} selected {/if}>{$MOD.LBL_DAY2}</option>
															<option value=3 {if $ACTIVITYDATA.repeatMonth_day eq 3} selected {/if}>{$MOD.LBL_DAY3}</option>
															<option value=4 {if $ACTIVITYDATA.repeatMonth_day eq 4} selected {/if}>{$MOD.LBL_DAY4}</option>
															<option value=5 {if $ACTIVITYDATA.repeatMonth_day eq 5} selected {/if}>{$MOD.LBL_DAY5}</option>
															<option value=6 {if $ACTIVITYDATA.repeatMonth_day eq 6} selected {/if}>{$MOD.LBL_DAY6}</option>
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
{/if}