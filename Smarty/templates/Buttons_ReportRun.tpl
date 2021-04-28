{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@29686 crmv@96742 crmv@97862 *}
<script type="text/javascript" language="javascript" src="modules/Charts/Charts.js"></script>
<div id="Buttons_List_3_Container">
	<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			{* crmv@30976 *}
			{if $FOLDERID > 0}
			<td style="padding:5px">
				<a href="index.php?module={$MODULE}&action=ListView&folderid={$FOLDERID}"><img src="{'folderback.png'|resourcever}" alt="{$APP.LBL_GO_BACK}" title="{$APP.LBL_GO_BACK}" align="absbottom" border="0" /></a>
			</td>
			{/if}
			{* crmv@30976e *}

			<td style="padding:5px" align="left" width="100%">
					{if $MOD.$REPORTNAME neq ''}
						{assign var="REPNAME" value=$MOD.$REPORTNAME}
					{else}
						{assign var="REPNAME" value=$REPORTNAME}
					{/if}
		 		<span class="dvHeaderText"><span class="recordTitle1">{$SINGLE_MOD|@getTranslatedString}:</span>&nbsp;{$REPNAME}</span> {* crmv@205568 *}
				{* crmv@25620 *}
 				<script type="text/javascript">
					updateBrowserTitle('{$SINGLE_MOD|@getTranslatedString:$MODULE} - {$REPNAME}');
				</script>
				{* crmv@25620e *}
 			</td>

			<!-- td align="left" style="padding:5px" width="100%" >&nbsp;</td -->

			<td align="right" style="padding:5px" >
			{if $IS_EDITABLE eq 'true'}
				<input type="button" name="custReport" value="{$APP.LBL_EDIT_BUTTON}" class="crmButton small edit" onClick="Reports.editReport('{$REPORTID}');">
			{/if}
			</td>

			{* crmv@30014 *}
			{if $IS_EDITABLE eq 'true' && $SHOW_CHART_CREATE}
			<td align="right" style="padding:5px" >
				{if $REPORT_HAS_SUMMARY}
					<input type="button" name="custReport" value="{$CHARTS_LANG.LBL_CREATE_CHART}" class="crmButton small edit" onClick="QCreate('Charts','','', '&reportid={$REPORTID}');" />
				{else}
					<input type="button" name="custReport" value="{$CHARTS_LANG.LBL_CREATE_CHART}" class="crmButton small edit" onClick="window.alert('{$CHARTS_LANG.LBL_REPORT_NO_SUMMARY}')" />
				{/if}
			</td>
			{/if}
			{* crmv@30014e *}

			{* crmv@38798 *}
			{if $IS_DUPLICABLE}
			<td align="right" style="padding:5px" >
				<input type="button" name="custReport" value="{$APP.LBL_DUPLICATE_BUTTON}" class="crmButton small create" onClick="Reports.createNew(null, '{$REPORTID}');">
			</td>
			{/if}
			{* crmv@38798e *}

			{if $EXPORT_PERMITTED eq 'YES'}
				<td align="right" style="padding:5px"><input class="crmbutton small create" id="btnExport" name="btnExport" value="{$MOD.LBL_EXPORTPDF_BUTTON}" type="button" onclick="Reports.showExportOptions('{$REPORTID}','pdf');" title="{$MOD.LBL_EXPORTPDF_BUTTON}"></td>
				<td align="right" style="padding:5px"><input class="crmbutton small create" id="btnExport" name="btnExport" value="{$MOD.LBL_EXPORTXL_BUTTON}" type="button" onclick="Reports.showExportOptions('{$REPORTID}','xls');" title="{$MOD.LBL_EXPORTXL_BUTTON}" ></td>
			{* crmv@sdk-25785 *}
 			{elseif $EXPORT_PERMITTED eq 'SELECT'}
  				{if $ENABLE_EXPORT_PDF}
   					<td align="right" style="padding:5px"><input class="crmbutton small create" id="btnExport" name="btnExport" value="{$MOD.LBL_EXPORTPDF_BUTTON}" type="button" onclick="Reports.showExportOptions('{$REPORTID}','pdf');" title="{$MOD.LBL_EXPORTPDF_BUTTON}"></td>
  				{/if}
  				{if $ENABLE_EXPORT_XLS}
   					<td align="right" style="padding:5px"><input class="crmbutton small create" id="btnExport" name="btnExport" value="{$MOD.LBL_EXPORTXL_BUTTON}" type="button" onclick="Reports.showExportOptions('{$REPORTID}','xls');" title="{$MOD.LBL_EXPORTXL_BUTTON}" ></td>
  				{/if}
 			{/if}
 			{if $ENABLE_PRINT}
  				<td align="right" style="padding:5px"><input name="PrintReport" value="{$APP.LNK_PRINT}" onclick="Reports.showExportOptions('{$REPORTID}', 'print');" class="crmbutton small create" type="button"></td>
 			{/if}
			{* crmv@sdk-25785e *}
		</tr>
	</table>
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