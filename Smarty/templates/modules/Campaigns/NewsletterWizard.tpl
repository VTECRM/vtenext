{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@43611 *}

{include file="SmallHeader.tpl" BODY_EXTRA_CLASS="popup-newsletter-wizard"}

<script type="text/javascript" src="include/js/vtlib.js"></script>
<script type="text/javascript" src="modules/Campaigns/Campaigns.js"></script>
<script type="text/javascript" src="modules/Newsletter/Newsletter.js"></script>
<script type="text/javascript" src="include/js/{$AUTHENTICATED_USER_LANGUAGE}.lang.js"></script>
{include file='CachedValues.tpl'}	{* crmv@26316 crmv@55961 *}

<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>

<input type="hidden" name="newsletterid" id="newsletterid" value="{$NEWSLETTERID}" />
<input type="hidden" name="campaignid" id="campaignid" value="{$CAMPAIGNID}" />

{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:2px;top:45px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

{* crmv@197575 *}
{include file="modules/Campaigns/NewsletterWizard/Navbar.tpl"}	

<div id="nlWizMainTab" class="main">
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				<div id="nlwTopButtons" style="max-height:50px;margin-top:15px">
					<div class="row">
						<div class="col-sm-4 text-left">
							<button type="button" class="crmbutton cancel" onclick="nlwGotoPrevStep()" id="nlw_backButton" style="height:35px;width:150px;display:none">&lt; {$APP.LBL_BACK}</button>
						</div>
						<div class="col-sm-4 text-center">
							<div id="nlWizStepTable">
								<div style="display:flex;align-items:center;justify-content:flex-end;">
									{include file="modules/Campaigns/NewsletterWizard/Stepper.tpl"}
								</div>
							</div>
						</div>
						<div class="col-sm-4 text-right">
							<button type="button" class="crmbutton save success" onclick="nlwGotoNextStep()" id="nlw_nextButton" style="height:35px;width:150px;">{$APP.LBL_FORWARD} &gt;</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12" id="nlWizRightPane">
				<div class="vte-card" style="min-height:500px;padding:10px;">
					{include file="modules/Campaigns/NewsletterWizard/Step1.tpl"}
					{include file="modules/Campaigns/NewsletterWizard/Step2.tpl"}
					{include file="modules/Campaigns/NewsletterWizard/Step3.tpl"}
					{include file="modules/Campaigns/NewsletterWizard/Step4.tpl"}
					{include file="modules/Campaigns/NewsletterWizard/Step5.tpl"}
				</div>

				<!-- TODO: VA BENE QUI ? Si potrebbe fare un pulsante vicino a Next simil tabella che scrolla al summary_table -->
				<div class="vte-card">
					<table id="summary_table" class="vtetable vtetable-props">
						<tr><td colspan="2"><b>{$MOD.NewsletterProgress}</b></td></tr>
						<tr><td class="cellLabel">{$MOD.Recipients}:</td><td class="cellText"><span id="nlw_selTargetsCount"></span></td></tr>
						<tr><td class="cellLabel">Template:</td><td class="cellText"><span id="nlw_selTemplate"></span></td></tr>
						<tr><td class="cellLabel">{$MOD.TestEmail}:</td><td class="cellText"><span id="nlw_testEmailOk"></span></td></tr>
						<tr><td class="cellLabel">{$MOD.NewsletterStatus}:</td><td class="cellText"><span id="nlw_newsletterSaved"></span></td></tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
{* crmv@197575e *}