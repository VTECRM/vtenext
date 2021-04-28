{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@82770 *}

{* display the box with the chart *}

{if $HOME_STUFFID > 0}
	{include file="modules/Charts/HomeStuffHeader.tpl"}
{/if}

<div class="small" style="padding:10px;" id="div_chart_{$CHART_ID}">
	<table cellspacing="0" cellpadding="0">

	{if $CHART_SHOWBORDER}
		<tr>
			<td class="dvtSelectedCell" style="text-align:center">
				<a class="small" href="index.php?module=Charts&amp;action=DetailView&amp;record={$CHART_ID}">{$CHART_TITLE}</a>
			</td>
		</tr>
	{/if}

	{if $CHART_SHOWBORDER}
		<tr>
			<td class="dvtContentSpace" align="right">
	{else}
		<tr>
			<td align="right">
	{/if}

				<div id="chart_bccont_{$CHART_ID}" class="chart-breadcrumbs hidden"></div>
				<div class="chart-container">
					<canvas id="chart_img_{$CHART_ID}" width="{$CHART_DATA.canvas_width}" height="{$CHART_DATA.canvas_height}">Canvas element is not supported. Please update your browser.</canvas>
					<div class="hidden chart-legend legend-right" id="chart_legend_{$CHART_ID}"></div>
					<div class="chart-limited" id="chart_limited_{$CHART_ID}" style="display:none">{'LBL_PARTIAL_DATA'|getTranslatedString:'Charts'} <i class="vteicon md-sm valign-bottom" style="cursor:pointer"  onclick="vtlib_field_help_show(this, 'chart_partial_tooltip', '{'LBL_PARTIAL_DATA_HELP'|getTranslatedString:'Charts'|escape:"quotes"|replace:'"':'&quot;'|replace:'%i':$CHART_LIMIT_DATA_N}'); return false;">help</i></div> {* crmv@177382 crmv@191909 *}
				</div>
			</td>
		</tr>
	
		<tr>
			<td>
				<table width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td align="left">
							{if $CHART_SHOWDATE && $CHART_LASTUPDATE > 0}
								<span><a style="color: gray; text-decoration: none; " href="javascript:;" title="{$CHART_LASTUPDATE_DISPLAY}">{$MOD.LBL_UPDATED_TO} {$CHART_LASTUPDATE_RELATIVE}</a></span> {* crmv@134727 *}
							{/if}
						</td>
						<td align="right">
							{* crmv@128369 *}
							{if $CHART_SHOWREPORTLINK && $REPORTID > 0}
								<span><a href="index.php?module=Reports&amp;action=SaveAndRun&amp;record={$REPORTID}" target="_blank" title="{$MOD.LBL_VIEW_REPORT}">{$MOD.LBL_VIEW_REPORT}</a></span>
							{/if}
							{* crmv@128369e *}
						</td>
					</tr>
				</table>
			</td>
		</tr>

	</table>
</div>

<script type="text/javascript">
	(function() {ldelim}
		// initialize the chart
		var chartData = {$CHART_DATA|@json_encode};
		VTECharts.generateCanvasChart('{$CHART_ID}', chartData, {ldelim}initialize: true{rdelim});
	{rdelim})();
</script>