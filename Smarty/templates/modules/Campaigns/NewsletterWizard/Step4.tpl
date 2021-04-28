{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="nlWizStep4" style="display:none">
	<p>{$MOD.SendTestEmailTo}:</p>
	<table border="0" id="nlw_testEmailTable">
		<tr>
			<td>
				<div class="dvtCellInfo"><input type="text" class="detailedViewTextBox" name="nlw_testEmailAddress" id="nlw_testEmailAddress" value="{$TESTEMAILADDRESS}" size="40" />
			</td>
			<td>
				<input type="button" class="crmbutton save" id="nlw_sendTestEmailButton" value="{$APP.LBL_SEND}" onclick="nlwSendTestEmail()" />
				<input type="button" class="crmbutton save" id="nlw_resendTestEmailButton" value="{$APP.LBL_RESEND}" onclick="nlwSendTestEmail()" style="display:none"/>
			</td>
		</tr>
	</table>
	<div id="nlw_testEmailIndicator" style="display:none;width:400px;text-align:center">{include file="LoadingIndicator.tpl"}</div>
	<div id="nlw_testEmailStatus" style="display:none"></div>
	<br><br>
	{* crmv@151466 *}
	<p>{$MOD.YouCanSeeNewsletterPreview}</p>
	<input type="button" class="crmbutton edit" id="nlw_previewButton" value="{$MOD.LBL_PREVIEW_NEWSLETTER}" onclick="nlwShowPreview()" />
	<div id="nlw_previewIndicator" style="display:none;width:200px;text-align:center">{include file="LoadingIndicator.tpl"}</div>
	{* crmv@151466e *}
</div>