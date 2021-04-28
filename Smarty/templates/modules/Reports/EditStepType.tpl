{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@97862 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_REPORT_TYPE}</span><br>
	<span style="font-size:90%">{$MOD.LBL_SELECT_REPORT_TYPE_BELOW}</span><hr>
</div>

<table border="0" width="100%">
	
	<tr>
		<td class="dimport_step_field_cell" align="right" width="30%">
			<img src="{'tabular.gif'|resourcever}" align="absmiddle" onclick="jQuery('#reportTypeTab').click()">
		</td>
		<td align="center" width="30" style="padding:10px">
			{if $REPORT_TYPE eq 'tabular' || $REPORT_TYPE eq ''}
				<input checked type="radio" name="reportType" id="reportTypeTab" value="tabular" onChange="EditReport.changeReportType()">
			{else}
				<input type="radio" name="reportType" id="reportTypeTab" value="tabular" onChange="EditReport.changeReportType()">
			{/if}
		</td>
		<td align="left" width="60%" style="padding:10px">
			<label for="reportTypeTab"><b> {$MOD.LBL_TABULAR_FORMAT}</b></label><br>
			<div>
			{$MOD.LBL_TABULAR_REPORTS_ARE_SIMPLEST}
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	
	<tr>
		<td colspan="4">
		&nbsp;
		</td>
	</tr>
	
	<tr>
		<td class="dimport_step_field_cell" align="right">
			<img src="{'summarize.gif'|resourcever}" align="absmiddle" onclick="jQuery('#reportTypeSum').click()">
		</td>
		<td align="center" style="padding:10px">
			{if $REPORT_TYPE eq 'summary'}
				<input checked type="radio" name="reportType" id="reportTypeSum" value="summary" onChange="EditReport.changeReportType()">
			{else}
				<input type="radio" name="reportType" id="reportTypeSum" value="summary" onChange="EditReport.changeReportType()">
			{/if}
		</td>
		<td align="left" style="padding:10px">
			<label for="reportTypeSum"><b> {$MOD.LBL_SUMMARY_REPORT}</b></label><br>
			<div>
			{$MOD.LBL_SUMMARY_REPORT_VIEW_DATA_WITH_SUBTOTALS}
			</div>
		</td>
		<td align="left">
		</td>
	</tr>
	
</table>