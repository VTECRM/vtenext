{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@83228 *}
<script type="text/javascript" src="modules/Charts/Charts.js"></script>

<div class="detailTabsMainDiv" style="display:none;">

	<div class="detailChartsDiv" id="detailCharts">
		<table border="0" cellspacing="2" cellpadding="2" width="100%">
		<tr>
			{foreach item=chart from=$CHARTS name=charts}
				<td>{$chart}</td>
				{if $smarty.foreach.charts.iteration % 2 == 0}</tr><tr>{/if}
			{/foreach}
		</tr>
	</table>
	</div>

</div>