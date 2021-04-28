{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* display the box with the chart *}
{* crmv@82770 *}

{if $HOME_STUFFID > 0}
{include file="modules/Charts/HomeStuffHeader.tpl"}
{/if}

<div class="small" style="padding:10px;" id="div_chart_{$CHART_ID}">
	<table cellspacing="0" cellpadding="0">

		{if $CHART_SHOWBORDER}
		<tr><td class="dvtSelectedCell" style="text-align:center">
			<a class="small" href="index.php?module=Charts&amp;action=DetailView&amp;record={$CHART_ID}">{$CHART_TITLE}</a> {* crmv@30976 *}
		</td></tr>
		{/if}

		{if $CHART_SHOWBORDER}
		<tr><td class="dvtContentSpace" align="right">
		{else}
		<tr><td align="right">
		{/if}

			{if count($CHART_MAP) > 0}
				<map name="chart_map_{$CHART_ID}">
					{foreach item=maparea key=mapid from=$CHART_MAP}
						<area id="chart_maparea_{$CHART_ID}_{$mapid}" shape="{$maparea.shape}" coords="{$maparea.coords}" alt="{$maparea.label}" onmouseover="chartTipShow('{$mapid}', '{$CHART_ID}')" onmouseout="chartTipHide('{$mapid}', '{$CHART_ID}')" />
						<input type="hidden" id="chart_maparea_label_{$CHART_ID}_{$mapid}" value="{$maparea.label}" />
						<input type="hidden" id="chart_maparea_value_{$CHART_ID}_{$mapid}" value="{$maparea.value}" />
						<input type="hidden" id="chart_maparea_percent_{$CHART_ID}_{$mapid}" value="{$maparea.percent}" />
						<input type="hidden" id="chart_maparea_color_{$CHART_ID}_{$mapid}" value="{$maparea.color}" />
					{/foreach}
				</map>
				{assign var="mapattr" value="usemap='#chart_map_$CHART_ID'"}
				<div id="chart_tooltip_{$CHART_ID}" class="small" style="display:none; position:absolute; left:0px; top:0px">
					<table cellspacing="0" cellpadding="0">
						<tr><td class="dvtSelectedCell" id="chart_tooltip_title_{$CHART_ID}" colspan="2">
						</td></tr>
						<tr>
							<td width="40%" align="center" class="dvtContentSpace" id="chart_tooltip_value_{$CHART_ID}"></td>
							<td width="60%" align="center" class="dvtContentSpace" id="chart_tooltip_percent_{$CHART_ID}"></td>
						</tr>
					</table>
				</div>
			{else}
				{assign var="mapattr" value=""}
			{/if}
			{* crmv@31209 *}
			{if $CHART_PATH eq ''}
				<p style="margin:4px">{$APP.LBL_NO_DATA}</p>
			{else}
				<img src="{$CHART_PATH}" alt="{$CHART_TITLE}" id="chart_img_{$CHART_ID}" border="0" {$mapattr} /><br />
			{/if}
			{* crmv@31209e *}

			{if $CHART_SHOWDATE && $CHART_LASTUPDATE > 0}
				<span style="text-align:right;"><a style="color: gray; text-decoration: none; " href="javascript:;" title="{$CHART_LASTUPDATE_DISPLAY}">{$MOD.LBL_UPDATED_TO} {$CHART_LASTUPDATE_RELATIVE}</a></span>
			{/if}
		</td></tr>

	</table>

</div>