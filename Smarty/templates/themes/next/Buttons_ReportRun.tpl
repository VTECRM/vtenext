{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<script type="text/javascript" src="modules/Charts/Charts.js"></script>

<div id="Buttons_List_3_Container">
	<ul class="vteUlTable">
		{if $FOLDERID > 0}
			<li>
				<a class="crmbutton only-icon save crmbutton-nav" href="index.php?module={$MODULE}&action=ListView&folderid={$FOLDERID}">
					<i class="vteicon" data-toggle="tooltip" title="{$APP.LBL_GO_BACK}" data-placement="bottom">folder_open</i>
				</a>
			</li>
		{/if}

		<li>
			{if $MOD.$REPORTNAME neq ''}
				{assign var="REPNAME" value=$MOD.$REPORTNAME}
			{else}
				{assign var="REPNAME" value=$REPORTNAME}
			{/if}
			<span class="dvHeaderText"><span class="recordTitle1">{$SINGLE_MOD|@getTranslatedString}:</span>&nbsp;{$REPNAME}</span> {* crmv@205568 *}
			<script type="text/javascript">
				updateBrowserTitle('{$SINGLE_MOD|@getTranslatedString:$MODULE} - {$REPNAME}');
			</script>
		</li>

		<li class="pull-right">
			<ul class="vteUlTable">
				<li>
					{if $IS_EDITABLE eq 'true'}
						<button type="button" class="crmbutton with-icon success crmbutton-nav" onclick="Reports.editReport('{$REPORTID}');">
							<i class="vteicon">mode_edit</i>
							{$APP.LBL_EDIT_BUTTON}
						</button>
					{/if}
				</li>
	
				{if $IS_EDITABLE eq 'true' && $SHOW_CHART_CREATE}
					<li>
						{if $REPORT_HAS_SUMMARY}
							<button type="button" class="crmbutton only-icon warning crmbutton-nav" onclick="QCreate('Charts','','', '&reportid={$REPORTID}');">
								<i class="vteicon" data-toggle="tooltip" title="{$CHARTS_LANG.LBL_CREATE_CHART}" data-placement="bottom">pie_chart</i>
							</button>
						{else}
							<button type="button" class="crmbutton only-icon warning crmbutton-nav" onclick="window.alert('{$CHARTS_LANG.LBL_REPORT_NO_SUMMARY}')">
								<i class="vteicon" data-toggle="tooltip" title="{$CHARTS_LANG.LBL_CREATE_CHART}" data-placement="bottom">pie_chart</i>
							</button>
						{/if}
					</li>
				{/if}
	
				{if $IS_DUPLICABLE}
					<li>
						<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.createNew(null, '{$REPORTID}');">
							<i class="vteicon" data-toggle="tooltip" title="{$APP.LBL_DUPLICATE_BUTTON}" data-placement="bottom">filter_none</i>
						</button>
					</li>
				{/if}
	
				{if $EXPORT_PERMITTED eq 'YES'}
					<li>
						<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.showExportOptions('{$REPORTID}','pdf');">
							<i class="vteicon2 fa-file-pdf-o" data-toggle="tooltip" title="{$MOD.LBL_EXPORTPDF_BUTTON}" data-placement="bottom"></i>
						</button>
					</li>
					<li>
						<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.showExportOptions('{$REPORTID}','xls');">
							<i class="vteicon2 fa-file-excel-o" data-toggle="tooltip" title="{$MOD.LBL_EXPORTXL_BUTTON}" data-placement="bottom"></i>
						</button>
					</li>
	 			{elseif $EXPORT_PERMITTED eq 'SELECT'}
	  				{if $ENABLE_EXPORT_PDF}
						<li>
							<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.showExportOptions('{$REPORTID}','pdf');">
								<i class="vteicon2 fa-file-pdf-o" data-toggle="tooltip" title="{$MOD.LBL_EXPORTPDF_BUTTON}" data-placement="bottom"></i>
							</button>
						</li>
	  				{/if}
	  				{if $ENABLE_EXPORT_XLS}
						<li>
							<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.showExportOptions('{$REPORTID}','xls');">
								<i class="vteicon2 fa-file-excel-o" data-toggle="tooltip" title="{$MOD.LBL_EXPORTXL_BUTTON}" data-placement="bottom"></i>
							</button>
						</li>
	  				{/if}
	 			{/if}
	 			{if $ENABLE_PRINT}
					<li>
						<button type="button" class="crmbutton only-icon save crmbutton-nav" onclick="Reports.showExportOptions('{$REPORTID}', 'print');">
							<i class="vteicon2 fa-print" data-toggle="tooltip" title="{$APP.LNK_PRINT}" data-placement="bottom"></i>
						</button>
					</li>
	 			{/if}
			</ul>
		</li>
	</ul>
</div>

{assign var="FLOAT_WIDTH" value="400px"} {* crmv@177381 *}
{capture assign="FLOAT_TITLE"}
<span id="report_choose_button_pdf">{$MOD.LBL_CHOOSE_EXPORT}</span>
<span id="report_choose_button_xls">{$MOD.LBL_CHOOSE_EXPORT}</span>
<span id="report_choose_button_print">{$MOD.LBL_CHOOSE_PRINT}</span>
{/capture}
{capture assign="FLOAT_BUTTONS"}
<input id="report_export_button_pdf" type="button" value="{$APP.LBL_EXPORT}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="Reports.startExport({$REPORTID}, 'pdf');">
<input id="report_export_button_xls" type="button" value="{$APP.LBL_EXPORT}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="Reports.startExport({$REPORTID}, 'xls');">
<input id="report_export_button_print" type="button" value="{$APP.LNK_PRINT}" name="button" class="crmbutton small edit" title="{$APP.LBL_ALL}" onClick="Reports.startExport({$REPORTID}, 'print');">
{/capture}
{capture assign="FLOAT_CONTENT"}
	<form name="export_report_list" id="export_report_list">
	{* crmv@177381 *}
	<table border="0" cellpadding="5" cellspacing="0" width="350" class="hdrNameBg" id="report_export_list">
		<tr><td>
			{if $REPORT_HAS_SUMMARY eq true}
				<input checked="" type="checkbox" name="export_report_summary" id="export_report_summary" value="1" />
			{else}
				<input disabled="" type="checkbox" name="export_report_summary" id="export_report_summary" value="1" />
			{/if}
			</td><td><label for="export_report_summary">{$MOD.LBL_REPORT_SUMMARY}</label></td>
			<td></td><td></td>
		</tr>
		<tr><td>
			<input checked="" type="checkbox" name="export_report_main" id="export_report_main" value="1" onchange="Reports.onExportMainChange(this)"/>
		</td>
			<td><label for="export_report_main">{$APP.Report}</label></td>
			<td><input type="checkbox" id="export_with_filters" value="1"/></td>
			<td><label for="export_with_filters">{$MOD.LBL_EXPORT_FILTERED}</label></td>
		</tr>
		<tr><td>
			{if $REPORT_HAS_TOTALS eq true}
				<input checked="" type="checkbox" name="export_report_totals" id="export_report_totals" value="1" />
			{else}
				<input disabled="" type="checkbox" name="export_report_totals" id="export_report_totals" value="1" />
			{/if}
			</td><td><label for="export_report_totals">{$MOD.LBL_REPORT_TOTALS}</label></td>
			<td></td>
		</tr>
	</table>
	{* crmv@177381e *}
	</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="ReportExport"}
	
<script type="text/javascript">calculateButtonsList3();</script>