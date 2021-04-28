{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<div id="ActivityRemindercallback-container">
	<div id="ActivityRemindercallback-fixed-header">
		<table border="0" cellpadding="2" cellspacing="0">
			<tr>
				<td style="text-indent:10px;"><b>{$APP.LBL_APPOINTMENT_REMINDER}</b></td>
			</tr>
		</table>
	</div>
	<div id="ActivityRemindercallback-content"></div>
	<div id="ActivityRemindercallback-fixed-footer">
		<table border="0" cellpadding="2" cellspacing="0">
			<tr>
				<td align="left" style="padding-left:6px">
					<input class="crmbutton small edit" type="button" value="{$APP.LBL_SNOOZE_ALL}" onclick="ActivityReminderPostponeAll();" />
				</td>
				<td align="right" style="padding-right:6px">
					<input class="crmbutton small edit" type="button" value="{$APP.LBL_DISMISS_ALL}" onclick="ActivityReminderCloseAll();" />
				</td>
			</tr>
		</table>
	</div>
</div>