{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="nlWizStep5" style="display:none">
	<p>{$MOD.OkWhenDoWeScheduleIt}</p>
	<div id="nlw_newsletterTimes">
	
	<table border="0" width="400">
		<tr>
			<td width="20"><input type="radio" name="nlw_radioSend" id="nlw_radioSendNow" checked="" onclick="jQuery('#nlw_sendTimesRow').hide();jQuery('#nlw_saveNowButton').show();jQuery('#nlw_saveLaterButton').hide();"></td>
			<td><label for="nlw_radioSendNow">{$APP.LBL_NOW}</label></td>
		</tr>
		<tr>
			<td><input type="radio" name="nlw_radioSend" id="nlw_radioSendLater" onclick="jQuery('#nlw_sendTimesRow').show();jQuery('#nlw_saveNowButton').hide();jQuery('#nlw_saveLaterButton').show();"></td>
			<td><label for="nlw_radioSendLater">{$MOD.AnotherTime}</label></td>
		</tr>
		<tr id="nlw_sendTimesRow" style="display:none"><td></td><td>
			<table border="0" width="100%">
				{assign var=dateAndTime value="Date & Time"}
				<tr><td colspan="2">{$APP.$dateAndTime}:</td></tr>
				<tr>
					<td height="22">
						<div class="dvtCellInfo">
							<input type="text" class="detailedViewTextBox" id="nlw_sendDate" value="" />
							<div class="dvtCellInfoImgRx">
								<img src="{'btnL3Calendar.gif'|resourcever}" id="jscal_trigger_nlw_sendDate" valign="top">
							</div>
						</div>
					</td>
					<td>
						<div class="dvtCellInfo">
							<input type="text" class="detailedViewTextBox" id="nlw_sendTime" value="" style="width:100px"/>
						</div>
					</td>
				</tr>
			</table>
			<script type="text/javascript">
				Calendar.setup ({ldelim}
					inputField : "nlw_sendDate", ifFormat : "%Y-%m-%d", showsTime : false, button : "jscal_trigger_nlw_sendDate", singleClick : true, step : 1
				{rdelim})
			</script>

		</td></tr>
	</table>
	<br>
	
	<input type="button" class="crmbutton save" id="nlw_saveNowButton" value="{$MOD.SaveAndSend}" onclick="nlwSaveAll()"/>
	<input type="button" class="crmbutton save" id="nlw_saveLaterButton" value="{$MOD.SaveAndSchedule}" onclick="nlwSaveAll()" style="display:none" />
	</div>
	<div id="nlw_newsletterIndicator" style="display:none;width:400px;text-align:center">{include file="LoadingIndicator.tpl"}</div>
	<div id="nlw_newsletterStatus" style="display:none"></div>
	<div id="nlw_closeButtonDiv" style="display:none">
		<br><br>
		<input type="button" class="crmbutton save" value="{$APP.LBL_CLOSE}" onclick="closePopup()" /> {* crmv@151466 *}
	</div>
</div>