{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@29686 crmv@97862 *}

<table cellpadding="0" cellspacing="0" width="100%" width="100%">
	<tbody>
	<tr><td colspan="2">
		<table border=0 cellspacing=0 cellpadding=3 width=100% class="small">
			<tr>
				<td class="dvtTabCache" style="width:10px" >&nbsp;</td>
				{if $REPORT_HAS_SUMMARY eq true}
					<td class="dvtUnSelectedCell" id="tdTabReportCount" align=center onclick="showReportTab('trReportCount', this)"><b>{$MOD.LBL_REPORT_SUMMARY}</b></td>
				{/if}
				<td class="dvtSelectedCell" id="tdTabReportMain" align=center onclick="showReportTab('trReportMain', this)"><b>{$APP.Report}</b></td>
				{if $REPORT_HAS_TOTALS eq true}
					<td class="dvtUnSelectedCell" id="tdTabReportTotal" align=center onclick="showReportTab('trReportTotal', this)"><b>{$MOD.LBL_REPORT_TOTALS}</b></td>
				{/if}
				{* crmv@30014 *}
				{if $REPORT_HAS_CHARTS eq true}
					<td class="dvtUnSelectedCell" id="tdTabReportCharts" align=center onclick="showReportTab('trReportCharts', this)"><b>{$CHARTS_LANG.Charts}</b></td>
				{/if}
				{* crmv@30014e *}
				<td class="dvtTabCache" style="width:10px">&nbsp;</td>
				<td class="dvtTabCache" style="width:100%">&nbsp;</td>
			</tr>
		</table>
	</td></tr>

	<tr><td>
		{* crmv@181170 *}
		{assign var=oReportRun value=$__REPORT_RUN_INSTANCE}
		{$oReportRun->setOutputFormat('HTML', true)}
		{* crmv@181170e *}
		
		<table class="dvtContentSpace" width="100%">

		<tr style="display:none">
			<td id="report_info" align="left">&nbsp;</td>
			<td align="right" width="25%"><span class="genHeaderGray">{$APP.LBL_TOTAL} : <span id='_reportrun_total'>{$REPORTHTML.1}</span>  {$APP.LBL_RECORDS}</span></td>
		</tr>

		<tr style="display: none" id="trReportCount"><td colspan="2" align="center">
		{* Creazione delle tabelle di conteggio *}
		{if $REPORT_HAS_SUMMARY && $DIRECT_OUTPUT eq true}
			<div style="width:70%">
				{* crmv@181170 *}
				{$oReportRun->setReportTab('COUNT')}
				{$oReportRun->GenerateReport()}
				{* crmv@181170e *}
			</div>
		{/if}
		{* END *}
		</td></tr>

		<tr id="trReportMain">
		<td colspan="2" id='table_reports' align="center">
		{* Performance Optimization: Direct result output *}
		{if $DIRECT_OUTPUT eq true}
			<div style="width:95%">
			<div id="modalProcessingDiv" style="position:absolute;width:100%;height:100%;background-color:#000;opacity:0.3;display:none"></div>
			{* crmv@181170 *}
			{if isset($oReportRun)}
				{$oReportRun->setReportTab('MAIN')}
				{assign var=oReportRunReturnValue value=$oReportRun->GenerateReport()}
				{assign var=TOTAL_RECORDS value=$oReportRun->getTotalCount()}
				{assign var=oclass value=$oReportRun->getOutputClass("HTML", true)}
				{assign var=headerData value=$oclass->getHeader()}
				{assign var=COLUMNS_DEF value=Zend_Json::encode($headerData)}
			{/if}
			{* crmv@181170e *}
			</div>
		{elseif $ERROR_MSG eq ''}
			{$REPORTHTML.0}
		{else}
			{$ERROR_MSG}
		{/if}
		{* END *}
		</td></tr>

		<tr style="display: none" id="trReportTotal"><td colspan="2" align="center">
		{* Performance Optimization: Direct result output *}
		{if $REPORT_HAS_TOTALS && $DIRECT_OUTPUT eq true}
			<div style="width:70%">
				{* crmv@181170 *}
				{$oReportRun->setReportTab('TOTAL')}
				{$oReportRun->GenerateReport()}
				{* crmv@181170e *}
			</div>
		{* crmv@73628 *}
		{elseif $ERROR_MSG eq ''}
			{$REPORTTOTALHTML}
		{* crmv@73628 e *}
		{/if}
		{* END *}
		</td></tr>

		{* crmv@30014 *}
		{if $REPORT_HAS_CHARTS eq true}
		<tr style="display: none" id="trReportCharts"><td colspan="2">
			{include file="modules/Charts/DisplayReportCharts.tpl"}
		</td></tr>
		{/if}
		{* crmv@30014 *}

		<tr><td colspan="2">&nbsp;</td></tr>

		</table></td></tr>

	</tbody>
</table>
{* crmv@81309e *}

{* crmv@96742 *}
<script type="text/javascript">
	jQuery(document).ready(function() {ldelim}
		
		{* crmv@30014 - show tab using url *}
		{if $REPORT_TAB neq ''}
			showReportTab('trReport{$REPORT_TAB}', document.getElementById('tdTabReport{$REPORT_TAB}'));
		{/if}
		{* crmv@30014e *}
		
		var reportid = '{$REPORTID}';
		var folderid = '{$FOLDERID}';
		
		var params = {ldelim}
			pageSize: parseInt('{$PAGESIZE}'),
			totalRecords: parseInt('{$TOTAL_RECORDS}'),
		{rdelim}

		{if $COLUMNS_DEF}
		params.columns = {$COLUMNS_DEF};
		{else}
		params.columns = [];
		{/if}

		ReportTable.initialize(reportid, folderid, params, null);
		
	});
</script>
{* crmv@96742e *}