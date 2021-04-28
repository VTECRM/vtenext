{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@101506 crmv@22700 crmv@152532 crmv@172994 *}

<script type="text/javascript" src="modules/Charts/Charts.js"></script>

<script type="text/javascript">
	(function() {ldelim}
		{if $CHART_DATA}
		var chartData = {$CHART_DATA|replace:"'":"\'"};
		{else}
		var chartData = {ldelim}{rdelim};
		{/if}
		var chartPlugin = '{$CHART_PLUGIN}';
		StatisticsScript.initChart(chartData, chartPlugin);
	{rdelim})();
</script>

<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
<tr>
	<td class="showPanelBg" valign=top width=100% style="padding:0px">
		<!-- PUBLIC CONTENTS STARTS-->
		<div class="small" style="padding:0px">
			<table align="center" border="0" cellpadding="4" cellspacing="0" width="100%" class="small">
			<tr>
				<td align="left">
					<button type="button" class="crmbutton with-icon edit" onclick="StatisticsScript.filter_statistics_newsletter({$CAMPAIGNID},getObj('statistics_newsletter'));">
						{$APP.Refresh}
						<i class="vteicon md-link" title="{$APP.Refresh}">refresh</i>
					</button>
				</td>
			 	<td align="right" nowrap>
			 		<span id="vtbusy_info" style="display:none;" valign="bottom">{include file="LoadingIndicator.tpl"}</span>
					{if !$NEWSLETTER_STATISTICS}{'Filter by Newsletter'|getTranslatedString:'Newsletter'}:&nbsp;{/if}{$STATISTICS_SELECT}&nbsp;
			 	</td>
			</tr>
			</table>

			<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
			<tr>
				<td valign=top align=left >
					<table border=0 cellspacing=0 cellpadding=0 width=100%>
						<tr>
							<td>
								{include file='RelatedListsHidden.tpl' MODULE="Campaigns"}
								<div align="center">
									{if $CHART_PLUGIN eq 'pChart'}
										<img class="img-responsive" id="StatisticChar" src="" />
										<script type="text/javascript">
											jQuery("#StatisticChar").attr("src", "cache/charts/StatisticsChart.png?"+(new Date()).getTime()); // crmv@38600
										</script>
									{elseif $CHART_PLUGIN eq 'ChartJS'}
										<div class="chart-container">
											<canvas id="chart_img_statistics">Canvas element is not supported. Please update your browser.</canvas>
											<div class="hidden chart-legend legend-right" id="chart_legend_statistics"></div>
										</div>
									{/if}
								</div>
								<div id="RLContents">
									{include file='RelatedListContents.tpl' MODULE="Campaigns" ID=$CAMPAIGNID} {* crmv@126155 *}
								</div>
								</form>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
		</div>
	<!-- PUBLIC CONTENTS STOPS-->
	</td>
</tr>
</table>
</td>
</tr></table>

{* crmv@101503 *}
<div id="ModTarget" class="crmvDiv" style="display: none; position: fixed; left: 494px; top: 42px; visibility: visible; z-index: 1000000007; padding:0px">
	<table width="100%" cellspacing="0" cellpadding="5" border="0">
		<tr height="34" style="cursor:move;">
			<td id="ModTarget_Handle" class="level2Bg" style="padding:5px">
				<table width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td>
							<b>{$APP.LBL_CREATE} {$APP.Targets}</b>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="ModTarget_div" style="padding: 4px; width: 550px; height: 120px; overflow: auto;">
		<table width="100%">
			<tr>
				<td>
					<div>
						<span class='dvtCellLabel'> {$APP.Name} {$APP.Targets} </span>
					</div>
					<div class='dvtCellInfo'>
						<input id='targetname' class='detailedViewTextBox' type='text' value='' name='targetname'>
						<input id='title' class='detailedViewTextBox' type='hidden' value='' name='title'>
						<input id='campaignid' class='detailedViewTextBox' type='hidden' value='' name='campaignid'>
					</div>
				</td>
			</tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td align="right"><input class="crmButton small save" type="button" style="min-width: 70px" value="{$APP.LBL_SAVE_BUTTON_LABEL}" name="button" onclick="StatisticsScript.saveTarget();" accesskey="S" title="{$APP.LBL_SAVE_BUTTON_LABEL}"></td></tr> {* crmv@132891 *}
		</table>
	</div>
	<div class="closebutton" onclick="fninvsh('ModTarget');"></div>
</div>
{literal}
<script>
	// crmv@192014
	jQuery("#ModTarget").draggable({
		handle: '#ModTarget_Handle'
	});
	// crmv@192014e

	jQuery("#statistics_newsletter").click(function(){
   		fninvsh('ModTarget');
	});
</script>
{/literal}
{* crmv@101503e *}