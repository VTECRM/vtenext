{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@139057 *}

{literal}
<style type="text/css">
#tableScheduledConfig {
	border-collapse:initial;
	border-spacing:5px;
}
#tableScheduledConfig > tbody > tr > td {
	border-bottom: 1px solid rgb(224, 224, 224);
	padding: 5px;
}
</style>
{/literal}

<table class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr valign='top'>
		<td colspan="1">
			<span class="genHeaderGray">{'LBL_SCHEDULE_EMAIL'|@getTranslatedString:'Reports'}</span>
			<br>
			{'LBL_SCHEDULE_EMAIL_DESCRIPTION'|@getTranslatedString:'Reports'}
			<hr>
		</td>
	</tr>
	<tr valign="top">
		<td>
			<div>
				<table class="small" border="0" cellpadding="5" cellspacing="1" width="100%">
					<tr class="small" valign="top">						
						<td width="5%" class="detailedViewHeader" align="center" valign="middle">
							<input type="checkbox" name="isReportScheduled" id="isReportScheduled"
							{if $IS_SCHEDULED} checked {/if}
							style="width:16px;height:16px"
							onchange="EditReport.changeScheduledReport()"
							{if !$IS_ADMIN}disabled=""{/if}
							>
						</td>
						<td width="90%" class="detailedViewHeader" class="cellText" valign="middle">
							<strong><label for="isReportScheduled" {if !$IS_ADMIN}class="text-muted"{/if}>{'LBL_SCHEDULE_REPORT'|@getTranslatedString:'Reports'}</label></strong>
						</td>
					</tr>
				</table>
				
				<br>
				
				{if $IS_ADMIN}
				<table class="small" border="0" width="100%" id="tableScheduledConfig" {if $IS_SCHEDULED neq 'true'}style="display:none;"{/if}>
					<tbody>
					<tr align="left" valign="top">
						<td valign="middle" class="small" width="20%" align="right"><strong>{'LBL_SCHEDULE_FREQUENCY'|@getTranslatedString:'Reports'}</strong></td>

						{assign var="schtypeid" value=$SCHEDULING.schedule.scheduletype}
						<td valign=top class="small">
							<span id="scheduledTypeSpan">
								<select class="detailedViewTextBox" style="width:100px" name="scheduledType" id="scheduledType" onchange="EditReport.setScheduleOptions()">
									<option id="schtype_1" value="1" {if $schtypeid eq 1}selected{/if}>{'LBL_HOURLY'|@getTranslatedString:'Reports'}</option>
									<option id="schtype_2" value="2" {if $schtypeid eq 2}selected{/if}>{'LBL_DAILY'|@getTranslatedString:'Reports'}</option>
									<option id="schtype_3" value="3" {if $schtypeid eq 3}selected{/if}>{'LBL_WEEKLY'|@getTranslatedString:'Reports'}</option>
									<option id="schtype_4" value="4" {if $schtypeid eq 4}selected{/if}>{'LBL_BIWEEKLY'|@getTranslatedString:'Reports'}</option>
									<option id="schtype_5" value="5" {if $schtypeid eq 5}selected{/if}>{'LBL_MONTHLY'|@getTranslatedString:'Reports'}</option>
									<option id="schtype_6" value="6" {if $schtypeid eq 6}selected{/if}>{'LBL_YEARLY'|@getTranslatedString:'Reports'}</option>
								</select>
							</span>
							<span id="scheduledMonthSpan" style="display: {if $schtypeid eq 6}inline{else}none{/if};">&nbsp;<strong>{'LBL_SCHEDULE_EMAIL_MONTH'|@getTranslatedString:'Reports'}</strong>
								<select class="detailedViewTextBox" style="width:100px" name="scheduledMonth" id="scheduledMonth">
									{foreach key=mid item=month from=$MONTH_STRINGS}
									<option value="{$mid}" {if $SCHEDULING.schedule.month eq $mid}selected{/if}>{$month}</option>
									{/foreach}
								</select>
							</span>

							<!-- day of month (monthly, annually) -->
							<span id="scheduledDOMSpan" style="display: {if $schtypeid eq 5 || $schtypeid eq 6}inline{else}none{/if};">&nbsp;<strong>{'LBL_SCHEDULE_EMAIL_DAY'|@getTranslatedString:'Reports'}</strong>:
								<select class="detailedViewTextBox" style="width:50px" name="scheduledDOM" id="scheduledDOM">
									{section name=day start=1 loop=32}
									<option value="{$smarty.section.day.iteration}" {if $SCHEDULING.schedule.date eq $smarty.section.day.iteration}selected{/if}>{$smarty.section.day.iteration}</option>
									{/section}
								</select>
							</span>

							<!-- day of week (weekly/bi-weekly) -->
							<span id="scheduledDOWSpan" style="display: {if $schtypeid eq 3 || $schtypeid eq 4}inline{else}none{/if};">&nbsp;<strong>{'LBL_SCHEDULE_EMAIL_DOW'|@getTranslatedString:'Reports'}</strong>:
								<select class="detailedViewTextBox" style="width:100px" name="scheduledDOW" id="scheduledDOW">
									{foreach key=wid item=week from=$WEEKDAY_STRINGS}
									<option value="{$wid}" {if $SCHEDULING.schedule.day eq $wid}selected{/if}>{$week}</option>
									{/foreach}
								</select>
							</span>

							<!-- time (daily, weekly, bi-weekly, monthly, annully) -->
							<span id="scheduledTimeSpan" style="display: {if $schtypeid > 1}inline{else}none{/if};">&nbsp;<strong>{$APP.LBL_TIME}</strong>:
								<input class="detailedViewTextBox small" style="width:50px" type="text" name="scheduledTime" id="scheduledTime" value="{$SCHEDULING.schedule.time}" size="5" maxlength="5" /> {'LBL_TIME_FORMAT_MSG'|@getTranslatedString:'Reports'}
							</span>

						</td>
						<td width="10%"></td>
					</tr>

					<tr align="left" valign="top">
						<td valign="middle" class="small" align="right"><strong>{'LBL_REPORT_FORMAT'|@getTranslatedString:'Reports'}</strong></td>

						<td valign=top class="small">
							<select id="scheduledReportFormat" name="scheduledReportFormat" class="detailedViewTextBox" style="width:80px">
								<option value="excel" {if $SCHEDULING.format eq 'excel'} selected {/if}>{'LBL_REPORT_FORMAT_EXCEL'|@getTranslatedString:'Reports'}</option>
								<option value="pdf" {if $SCHEDULING.format eq 'pdf'} selected {/if}>{'LBL_REPORT_FORMAT_PDF'|@getTranslatedString:'Reports'}</option>
								<option value="both" {if $SCHEDULING.format eq 'both'} selected {/if}>{'LBL_REPORT_FORMAT_BOTH'|@getTranslatedString:'Reports'}</option>
							</select>
						</td>
						<td></td>
					</tr>

					<tr align="left" valign="top">
						<td valign="middle" class="small" align="right"><strong>{'LBL_USERS_AVAILABLE'|@getTranslatedString:'Reports'}</strong></td>

						<td>
							<table border="0" width="100%">
								<tr>
									<td width="40%" valign=top class="small">
										{$APP.LBL_SELECT}:&nbsp;
										<select id="recipient_type" name="recipient_type" class="detailedViewTextBox" style="width:120px" onChange="EditReport.generateRecipientOption()">
											<option value="users">{'LBL_USERS'|@getTranslatedString:'Users'}</option>
											<option value="groups">{'LBL_GROUPS'|@getTranslatedString:'Users'}</option>
											<option value="roles">{'LBL_ROLES'|@getTranslatedString:'Users'}</option>
											<option value="rs">{'LBL_ROLES_SUBORDINATES'|@getTranslatedString:'Users'}</option>
										</select>
										<br><br>
									</td>
									<td width="140">&nbsp;</td>
									<td width="40%" class="small"><strong>{'LBL_USERS_SELECTED'|@getTranslatedString:'Reports'}</strong></td>
									<td width="30"></td>
									<td></td>
								</tr>

								<tr class=small>
									<td valign=top align="right">
										<div id="availableRecipientsWrapper">
											<select id="availableRecipients" name="availableRecipients" multiple size="10" class="detailedViewTextBox">
											</select>
										</div>
									</td>
									<td align="center">
										<button name="add" class="crmbutton edit" type="button" onclick="EditReport.addScheduledRecipient()">{$APP.LBL_ADD_ITEM} &gt;</button>
									</td>
									<td class="small" valign=top>
										<select id="selectedRecipients" name="selectedRecipients" multiple size="10" class="detailedViewTextBox">
											{foreach key="reckey" item="recname" from=$SCHEDULING.recipients.list}
												<option value="{$reckey}">{$recname}</option>
											{/foreach}
										</select>
									</td>
									<td valign="top">
										<i class="vteicon md-link" onclick="EditReport.delScheduledRecipient()">delete</i><br>
									</td>
									<td></td>
								</tr>
							</table>
						</td>
						<td></td>
					</tr>
					
					<tr>
						<td align="right">
							<b>{$MOD.LBL_NOTE}</b>
						</td>
						<td>
							{$MOD.LBL_SCHEDULED_AS_ADMIN}
						</td>
						<td></td>
					</tr>
					
					</tbody>
				</table>
				
				{else}
				
				<p>{$MOD.LBL_ONLY_ADMIN_CAN_SCHEDULE}</p>
				{/if}
				
			</div>
		</td>
	</tr>
</table>

<script type="text/JavaScript">
var reportSchedRecipients = {$SCHEDRECIPIENTS_JS};
</script>