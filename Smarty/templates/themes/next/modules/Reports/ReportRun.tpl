{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@29686 crmv@97862 *}

{* crmv@96742 *}
{assign var=DATATABLE_THEME value="bootstrap"}
<link rel="stylesheet" type="text/css" href="include/js/dataTables/css/dataTables.{$DATATABLE_THEME}.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/ColReorder/css/colReorder.{$DATATABLE_THEME}.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/FixedColumns/css/fixedColumns.{$DATATABLE_THEME}.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/FixedHeader/css/fixedHeader.{$DATATABLE_THEME}.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/Responsive/css/responsive.{$DATATABLE_THEME}.min.css"/>
<link rel="stylesheet" type="text/css" href="include/js/dataTables/plugins/Select/css/select.{$DATATABLE_THEME}.min.css"/>
 
<script type="text/javascript" src="include/js/dataTables/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/dataTables.{$DATATABLE_THEME}.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/plugins/FixedHeader/js/dataTables.fixedHeader.min.js"></script>
{* some other plugins, enable them if needed *}
{*
<script type="text/javascript" src="include/js/dataTables/plugins/ColReorder/js/dataTables.colReorder.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/plugins/FixedColumns/js/dataTables.fixedColumns.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/plugins/Responsive/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="include/js/dataTables/plugins/Select/js/dataTables.select.min.js"></script>
*}

{* crmv@96742e *}

<script language="JavaScript" type="text/javascript">
	/* labels for reports used in javascript */
	var ReportLabels = {ldelim}
		LBL_CHOOSE_EMPTY: '{$MOD.LBL_CHOOSE_EMPTY}',
		LBL_Print_REPORT: '{$MOD.LBL_Print_REPORT}',
		NO_FILTER_SELECTED: '{$MOD.NO_FILTER_SELECTED}',
		LBL_REPORTING: '{$MOD.LBL_REPORTING}',
	{rdelim}
</script>

{$BLOCKJS}

{* crmv@98431 *}
{if $smarty.request.embedded == '1'}
<script type="text/javascript" src="{"modules/Reports/Reports.js"|resourcever}"></script> {* crmv@128369 *}
{else}
{include file='Buttons_List1.tpl'}
{include file='Buttons_ReportRun.tpl'}
{/if}
{* crmv@98431e *}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
    <td valign="top"></td>
	<td class="showPanelBg" valign="top" width="100%">
	
		<input type="hidden" id="reportHasSummary" value="{$REPORT_HAS_SUMMARY}" />
		<input type="hidden" id="reportHasTotals" value="{$REPORT_HAS_TOTALS}" />

		<form name="NewReport" action="index.php" method="POST" onsubmit="VteJS_DialogBox.block();">
		   <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		    <input type="hidden" name="booleanoperator" value="5"/>
		    <input type="hidden" name="record" value="{$REPORTID}"/>
		    <input type="hidden" name="reload" value=""/>
		    <input type="hidden" name="module" value="Reports"/>
		    <input type="hidden" name="action" value="SaveAndRun"/>
		    <input type="hidden" name="dlgType" value="saveAs"/>
		    <input type="hidden" name="reportName"/>
		    <input type="hidden" name="folderid" value="{$FOLDERID}"/>
		    <input type="hidden" name="reportDesc"/>
		    <input type="hidden" name="folder"/>
		
			<!-- Generate Report UI Filter -->
			<div style="padding:10px;">
				<table class="small searchUIBasic" align="center" cellpadding="0" cellspacing="0" width="100%" border=0>
					<tr>
						<td><span class="moduleName">{$MOD.TIME_INTERVAL}</span></td>
					</tr>
				</table>
				<table class="small searchUIBasic" align="center" cellpadding="0" cellspacing="0" width="100%" border=0 style="padding-top:10px;">
					{* crmv@sdk-25785 *}
					{if $HIDE_PARAMS_BLOCK}
						<tr style="display:none">
					{else}
						<tr>
					{/if}
					{* crmv@sdk-25785e *}
						<td align=left class=dvtCellLabel>{$MOD.LBL_SELECT_COLUMN}</td>
						<td align=left class=dvtCellLabel>{$MOD.LBL_SELECT_TIME}</td>
						<td align=left class=dvtCellLabel>{$MOD.LBL_SF_STARTDATE}</td>
						<td align=left class=dvtCellLabel>{$MOD.LBL_SF_ENDDATE}</td>
					</tr>
					{* crmv@sdk-25785 *}
			  		{if $HIDE_PARAMS_BLOCK}
			  			<tr style="display:none">
				  	{else}
						<tr>
					{/if}
					{* crmv@sdk-25785e *}
						<td align="left" width="30%">
							<div class="dvtCellInfo">
								<select id="stdDateFilterField" name="stdDateFilterField" class="detailedViewTextBox" onchange="standardFilterDisplay();">
									{foreach item=group from=$STDFILTERFIELDS}
										{foreach item=fld from=$group.fields}
										<option value="{'"'|str_replace:'&quot;':$fld.value}" {if $STDFILTER.name eq $fld.value}selected=""{/if}>{$fld.label}</option>
										{/foreach}
									{/foreach}
								</select>
							</div>
						</td>
						<td align=left width="30%">
							<div class="dvtCellInfo">
								<select id="stdDateFilter" name="stdDateFilter" class="detailedViewTextBox" onchange='showDateRange( this.options[ this.selectedIndex ].value )'>
									{foreach item=stdfilter from=$STDFILTEROPTIONS}
										<option value="{$stdfilter.value}" {if $STDFILTER.value eq $stdfilter.value}selected=""{/if}>{$stdfilter.text}</option>
									{/foreach}
								</select>
							</div>
						</td>
						<td align=left width="20%">
							<table border=0 cellspacing=0 cellpadding=2>
							<tr>
								{* crmv@100585 *}
								<td align=left><div class="dvtCellInfo"><input name="startdate" id="jscal_field_date_start" type="text" class="detailedViewTextBox" value="{$STDFILTER.startdate}"></div></td>
								<td align=left nowrap><i class="vteicon md-link md-text" id="jscal_trigger_date_start">events</i><font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
									<script type="text/javascript">
										(function() {ldelim}
											setupDatePicker('jscal_field_date_start', {ldelim}
												trigger: 'jscal_trigger_date_start',
												date_format: "{$DATEFORMAT|strtoupper}",
												language: "{$APP.LBL_JSCALENDAR_LANG}",
											{rdelim});
										{rdelim})();
									</script>
			
								</td>
							</tr>
							</table>
						</td>
						<td align=left width=20%>
							<table border=0 cellspacing=0 cellpadding=2>
							<tr>
								<td align=left><div class="dvtCellInfo"><input name="enddate" id="jscal_field_date_end" type="text" class="detailedViewTextBox" value="{$STDFILTER.enddate}"></div></td>
								<td align=left nowrap><i class="vteicon md-link md-text" id="jscal_trigger_date_end">events</i><font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
									<script type="text/javascript">
										(function() {ldelim}
											setupDatePicker('jscal_field_date_end', {ldelim}
												trigger: 'jscal_trigger_date_end',
												date_format: "{$DATEFORMAT|strtoupper}",
												language: "{$APP.LBL_JSCALENDAR_LANG}",
											{rdelim});
										{rdelim})();
									</script>
								</td>
								{* crmv@100585e *}
							</tr>
							</table>
						</td>
					</tr>
					{* crmv@sdk-25785 *}
					<tr>
						<td align="center" colspan="4">{$SDKBLOCK}</td>
					</tr>
			  		{* crmv@sdk-25785e *}
					<tr>
						<td align="center" colspan="4" style="padding-top:10px"><button name="generatenw" class="crmbutton small create" type="button" onClick="generateReport({$REPORTID});"> {$MOD.LBL_REFRESH} </button></td>
					</tr>
				</table>
			</div>
		</form>
		
	</td>
	<td valign="top"></td>
</tr>
</table>

<div style="display: block;" id="Generate" align="center">
	{include file="modules/Reports/ReportRunContents.tpl"}
</div>

<script language="Javascript" type="text/javascript">

{* crmv@sdk-25785 *}
function getSdkParams(id) {ldelim}
	var sdk_params = "";
	{if "$SDKJSFUNCTION" neq ""}
		if (typeof({$SDKJSFUNCTION}) == "function") sdk_params = {$SDKJSFUNCTION}(id);
  	{/if}
	return sdk_params;
{rdelim}
{* crmv@sdk-25785e *}

Reports.initialize();

</script>