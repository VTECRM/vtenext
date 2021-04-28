{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@97862 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_CALCULATIONS}</span><br>
	<span style="font-size:90%">{$MOD.LBL_SELECT_COLUMNS_TO_TOTAL}</span><hr>
</div>

<table id="totalsTable" border="0" width="100%" {if !$TOTALS || count($TOTALS) == 0}style="display:none"{/if}>
	
	<tr id="totalsMasterRow0" class="totalsRow0" style="display:none;border-top: 1px solid #e0e0e0" height="40">
		<td align="right" valign="bottom"><b>{$APP.LBL_MODULE}</b>&nbsp;&nbsp;</td>
		<td colspan="7" class="rptChainContainer" valign="bottom">
			<div class="chainFirst">
				<span class="chainMainModule"></span>
				<span class="chainArrow">&gt;</span>
			</div>
			<div class="dvtCellInfo chainOthers">
				<select class="detailedViewTextBox chainModule" style="min-width:250px" onchange="EditReport.changeModulesPicklist(this, '#totalFieldsIDX', 'total')"></select>
			</div>
		</td>
	</tr>
	
	<tr id="totalsMasterRow1" class="totalsRow1" style="display:none">
		<td width="90" align="right"><b></b></td>
		<td width="25%" align="left"><b></b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_SUM}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_AVERAGE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_LOW_VALUE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_LARGE_VALUE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_REPORT_SUMMARY}</b></td>
		<td width="5%" align="center"></td>
		<td></td>
	</tr>
	
	<tr id="totalsMasterRow2" class="totalsRow2" height="50" style="display:none">
		<td align="right"><b>{$APP.Field}</b></td>
		<td>
			<select class="detailedViewTextBox summaryFields" style="min-width:300px"></select>
		</td>
		<td align="center"><input class="detailedViewTextBox" name="aggregatorSUM" type="checkbox" onchange="EditReport.changeFormulaTotal(this)"/></td>
		<td align="center"><input class="detailedViewTextBox" name="aggregatorAVG" type="checkbox" onchange="EditReport.changeFormulaTotal(this)"/></td>
		<td align="center"><input class="detailedViewTextBox" name="aggregatorMIN" type="checkbox" onchange="EditReport.changeFormulaTotal(this)"/></td>
		<td align="center"><input class="detailedViewTextBox" name="aggregatorMAX" type="checkbox" onchange="EditReport.changeFormulaTotal(this)"/></td>
		<td align="center"><input class="detailedViewTextBox summaryTotal" type="checkbox" disabled="" onchange="EditReport.changeSummaryTotal(this)"/></td>
		<td><i class="vteicon md-link" onclick="EditReport.removeTotalField(this)">delete</i></td>
		<td></td>
	</tr>
	
	{if $TOTALS}
	{foreach key=idx item=total from=$TOTALS}
	<tr class="totalsRow0" height="40" {if $idx > 0}style="border-top: 1px solid #e0e0e0"{/if}>
		<td align="right" valign="bottom"><b>{$APP.LBL_MODULE}</b>&nbsp;&nbsp;</td>
		<td colspan="7" class="rptChainContainer" valign="bottom">
			<div class="chainFirst">
				<span class="chainMainModule"></span>
				<span class="chainArrow">&gt;</span>
			</div>
			<div id="totalsChainModules{$idx}" class="dvtCellInfo chainOthers">
				{foreach item=listmod from=$total.listmodules}
				<select class="detailedViewTextBox chainModule" style="min-width:250px" onchange="EditReport.changeModulesPicklist(this, '#totalFields{$idx}', 'total')">
					{foreach item=mod from=$listmod.list}
						<option value="{$mod.value}" {if $mod.value eq $listmod.selected}selected=""{/if}>{$mod.label}</option>
					{/foreach}
				</select>
				{/foreach}
			</div>
		</td>
	</tr>
	
	<tr class="totalsRow1">
		<td width="90" align="right"><b></b></td>
		<td width="25%" align="left"><b></b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_SUM}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_AVERAGE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_LOW_VALUE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_COLUMNS_LARGE_VALUE}</b></td>
		<td width="12%" align="center"><b>{$MOD.LBL_REPORT_SUMMARY}</b></td>
		<td width="5%" align="center"></td>
		<td></td>
	</tr>
	
	<tr class="totalsRow2" height="50">
		<td align="right"><b>{$APP.Field}</b> </td>
		<td>
			<select id="totalFields{$idx}" class="detailedViewTextBox summaryFields" style="min-width:300px">
				{foreach item=block from=$total.listfields}
					{foreach item=fld from=$block.fields}
						<option value="{'"'|str_replace:'&quot;':$fld.value}" {if $total.name eq $fld.value}selected=""{/if}
							data-wstype="{$fld.wstype}" 
							data-uitype="{$fld.uitype}"
							data-module="{$fld.module}"
							data-fieldname="{$fld.fieldname}"
						>{$fld.label}</option>
					{/foreach}
				{/foreach}
			</select>
		</td>
		<td align="center">
			<input class="detailedViewTextBox" name="aggregatorSUM" type="checkbox" onchange="EditReport.changeFormulaTotal(this)" {if "SUM"|in_array:$total.aggregators}checked=""{/if}/>
		</td>
		<td align="center">
			<input class="detailedViewTextBox" name="aggregatorAVG" type="checkbox" onchange="EditReport.changeFormulaTotal(this)" {if "AVG"|in_array:$total.aggregators}checked=""{/if}/>
		</td>
		<td align="center">
			<input class="detailedViewTextBox" name="aggregatorMIN" type="checkbox" onchange="EditReport.changeFormulaTotal(this)" {if "MIN"|in_array:$total.aggregators}checked=""{/if}/>
		</td>
		<td align="center">
			<input class="detailedViewTextBox" name="aggregatorMAX" type="checkbox" onchange="EditReport.changeFormulaTotal(this)" {if "MAX"|in_array:$total.aggregators}checked=""{/if}/>
		</td>
		<td align="center">
			<input class="detailedViewTextBox summaryTotal" type="checkbox" onchange="EditReport.changeSummaryTotal(this)" {if $total.summary}checked=""{/if}/>
		</td>
		<td><i class="vteicon md-link" onclick="EditReport.removeTotalField(this)">delete</i></td>
		<td></td>
	</tr>
	{/foreach}
	{/if}
	
</table>

<table width="100%">
	<tr>
		<td align="center">
			<br>
			<button type="button" class="crmbutton edit" onclick="EditReport.addTotalField()">{$APP.LBL_ADD_BUTTON} {$APP.LBL_TOTAL}</button>
		</td>
	</tr>
</table>