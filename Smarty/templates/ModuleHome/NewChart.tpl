{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@83340 *}

{if count($CHARTS) > 0}
	<table width="100%" cellspacing="5" cellpadding="2" border="0">
		<tr>
			<td align="right" width="50%">
				{$APP.SINGLE_Charts}
			</td>
			<td align="left" width="50%">
			<select name="select_chart" id="select_chart">
				{foreach item=CHART from=$CHARTS}
					<option value="{$CHART.chartid}">{$CHART.chartname}</option>
				{/foreach}
			</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right" colspan="2">
				<button type="button" class="crmbutton save" onclick="ModuleHome.addBlock('{$MODHOMEID}', 'Chart')">{$APP.LBL_SAVE_LABEL}</button>
			</td>
		</tr>
	</table>
{else}
	<center>
		<p>{$APP.LBL_NO_AVAILABLE_CHARTS}</p>
	</center>
{/if}