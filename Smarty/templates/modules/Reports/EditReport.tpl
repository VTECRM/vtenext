{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@98764 crmv@98866 *}
{assign var="BROWSER_TITLE" value=$MOD.TITLE_VTECRM_CREATE_REPORT}
{include file="HTMLHeader.tpl" head_include="jquery,jquery_plugins,jquery_ui,fancybox,prototype,charts"}
	
<body class="small popup-edit-report">

{include file="Theme.tpl" THEME_MODE="body"}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

{* crmv@128369 *}
<script type="text/javascript" src="{"modules/Reports/Reports.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Reports/EditReport.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Charts/Charts.js"|resourcever}"></script>
{* crmv@128369e *}

<div id="popupContainer" style="display:none;"></div> {* crmv@128369 *}

{* popup status *}
<div id="editreport_busy" name="editreport_busy" style="display:none;position:fixed;right:200px;top:10px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>

{* header *}
<table id="reportHeaderTab" class="mailClientWriteEmailHeader level2Bg menuSeparation" width="100%" border="0" cellspacing="0" cellpadding="5" > {* crmv@21048m *}
	<tr>
		<td class="moduleName" width="80%">{if $REPORTID}{$MOD.LBL_CUSTOMIZE_REPORT}{else}{$MOD.LBL_CREATE_REPORT}{/if}</td>
		<td width=30% nowrap class="componentName" align="right">{$MOD.LBL_CUSTOM_REPORTS}</td>
	</tr>
</table>

{* content *}
<table id="reportMainTab" border="0" width="100%"> {* crmv@168644 - fix next theme hidden scrollbar *}
	<tr>
		<td id="leftPane" width="250" valign="top">
			<div>
				<table id="reportStepTable" width="100%">
					{* crmv@128369 *}
					<tr><td id="step1label" class="reportStepCell reportStepCellSelected" style="padding-left:10px;" {if $REPORTID}onclick="EditReport.gotoStep(1)"{/if}>{counter}. {$MOD.LBL_REPORT_DETAILS}</td></tr>
					<tr><td id="step2label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(2)"{/if}>{counter}. {$MOD.LBL_REPORT_TYPE}</td></tr>
					<tr><td id="step3label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(3)"{/if}>{counter}. {$MOD.LBL_TEMPORAL_FILTER}</td></tr>
					<tr><td id="step4label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(4)"{/if}>{counter}. {$MOD.LBL_ADVANCED_FILTER}</td></tr>
					{if $ENABLE_CLUSTERS}
					<tr><td id="step5label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(5)"{/if}>{counter}. {$MOD.LBL_CLUSTERS}</td></tr>
					{/if}
					<tr><td id="step6label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(6)"{/if}>{counter}. {$MOD.LBL_SELECT_COLUMNS}</td></tr>
					<tr><td id="step7label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(7)"{/if}>{counter}. {$MOD.LBL_CALCULATIONS}</td></tr>
					<tr><td id="step8label" class="reportStepCell" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(8)"{/if}>{counter}. {$MOD.LBL_SHARING}</td></tr>
					{if $CAN_CREATE_CHARTS && !$REPORTID}
					<tr><td id="step9label" class="reportStepCell" style="padding-left:10px">{counter}. {"Charts"|getTranslatedString:'Charts'}</td></tr>
					{/if}
					<tr><td id="step10label" class="reportStepCell {if !$IS_ADMIN}text-muted{/if}" style="padding-left:10px" {if $REPORTID}onclick="EditReport.gotoStep(10)"{/if}>{counter}. {$MOD.LBL_SCHEDULE_EMAIL} </td></tr> {* crmv@139057 *}
					{* crmv@128369e *}
				</table>
			</div>
		</td>
		
		<td id="rightPane" valign="top">

			<table id="reportTopButtons" border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td align="left"><input type="button" class="crmbutton cancel" onclick="EditReport.gotoPrevStep()" id="backButton" style="display:none" value="&lt; {$APP.LBL_BACK}"></td>
					<td align="right">
						<input type="button" class="crmbutton save" onclick="EditReport.gotoNextStep()" id="nextButton" value="{$APP.LBL_FORWARD} &gt;">
						<input type="button" class="crmbutton save" onclick="EditReport.saveReport()" id="saveButton" style="display:none" value="{$APP.LBL_SAVE_LABEL}">
					</td>
				</tr>
			</table>
			
			<br>
			
			<form id="NewReport" name="NewReport" onsubmit="return false">
			
			<input type="hidden" name="reportid" id="reportid" value="{$REPORTID}" />
			<input type="hidden" name="duplicate" id="duplicate" value="{$DUPLICATE}" />
			<input type="hidden" name="return_module" id="return_module" value="{$RETURN_MODULE}" /> {* crmv@139858 *}
			<input type="hidden" name="existing_charts" id="existing_charts" value="{$EXISTING_CHARTS}" /> {* crmv@172355 *}
			<input type="hidden" name="remove_charts" id="remove_charts" value="0" /> {* crmv@172355 *}
			
			<div id="reportStep1" style="">
				{include file="modules/Reports/EditStepInfo.tpl"}
			</div>

			<div id="reportStep2" style="display:none;">
				{include file="modules/Reports/EditStepType.tpl"}
			</div>
			
			<div id="reportStep3" style="display:none">
				{include file="modules/Reports/EditStepStdFilters.tpl"}
			</div>

			<div id="reportStep4" style="display:none">
				{include file="modules/Reports/EditStepAdvFilters.tpl"}
			</div>
			
			{* crmv@128369 *}
			{if $ENABLE_CLUSTERS}
			<div id="reportStep5" style="display:none">
				{include file="modules/Reports/EditStepClusters.tpl"}
			</div>
			{/if}

			<div id="reportStep6" style="display:none">
				{include file="modules/Reports/EditStepFields.tpl"}
			</div>

			<div id="reportStep7" style="display:none">
				{include file="modules/Reports/EditStepTotals.tpl"}
			</div>
			
			<div id="reportStep8" style="display:none">
				{include file="modules/Reports/EditStepSharing.tpl"}
			</div>
			
			{if $CAN_CREATE_CHARTS && !$REPORTID}
			<div id="reportStep9" style="display:none">
				{include file="modules/Reports/EditStepCharts.tpl"}
			</div>
			{/if}
			
			{* crmv@139057 *}
			<div id="reportStep10" style="display:none">
				{include file="modules/Reports/EditStepSchedule.tpl"}
			</div>
			{* crmv@139057e *}
			
			{* crmv@128369e *}

			</form>
			
		</td>
	</tr>
</table>

<script type="text/javascript">
	{if $PRELOAD_JS}
	(function() {ldelim}
		var preload_js = {$PRELOAD_JS};
		EditReport.preloadCache(preload_js);
	{rdelim})();
	{/if}
	{if $FIELD_FUNCTIONS_JS}
	var ReportFieldFormulas = {$FIELD_FUNCTIONS_JS};
	{/if}
	
	// initialize the first step
	EditReport.initializeStep(1);
</script>


</body>
</html>