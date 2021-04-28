{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@100905 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_FILTERS}</span><br>
	{* crmv@128369 *}
	<span style="font-size:90%">
		{if $FILTERDESC}{$FILTERDESC}{else}{$MOD.LBL_SELECT_FILTERS_TO_STREAMLINE_REPORT_DATA}{/if}
	</span><hr>
	{* crmv@128369e *}
</div>

{* block template *}
{* START OF TEMPLATE *}
<div id="advFiltersMaster" class="advFilterGroup" style="display:none">

	<div id="advFiltersBlockMaster" class="advFilterGroupBox">
	
		<table width="100%">
			<tr>
				<td align="right">
					<i class="vteicon md-link" onclick="EditReport.removeFilterGroup(this)">cancel</i>
				</td>
			</tr>
		</table>
	
		<table class="advFilterList" border="0" width="100%" align="center" cellspacing="2">

			<tr id="advFilterMasterRow0" height="40">
				<td width="80" valign="bottom"><b>{$APP.LBL_MODULE}</b></td>
				<td width="25%" colspan="6" class="rptChainContainer" valign="bottom">
					<div class="dvtCellInfo chainFirst">
						<span class="chainMainModule"></span>
						<span class="chainArrow">&gt;</span>
					</div>
					<div class="dvtCellInfo chainOthers">
						<select class="detailedViewTextBox chainModule" style="min-width:250px" onchange="EditReport.changeModulesPicklist(this, '#advFilterFields_GROUPIDX_CONDIDX')"></select>
					</div>
				</td>
			</tr>
			
			<tr id="advFilterMasterRow1">
				<td><b>{$APP.Field}</b></td>
				<td width="30%">
					<select class="detailedViewTextBox filterFields" style="min-width:300px" onchange="EditReport.alignFilterField(this)"></select>
				</td>
				<td width="100">
					<select name="advFilterComparator" class="detailedViewTextBox" onclick="EditReport.changeFilterComparator(this)">
							<option value=""></option>
						{foreach key=compval item=complabel from=$COMPARATORS}
							<option value="{$compval}">{$complabel}</option>
						{/foreach}
					</select>
				</td>
				<td>
					<input type="text" name="advFilterValue" class="detailedViewTextBox" value="" />
					<span name="advFilterValueAnd" style="display:none"> {$APP.LBL_AND} </span>
					<input type="text" name="advFilterValue2" class="detailedViewTextBox" value="" style="display:none;width:40%" />
					
					<input type="text" name="advFilterReferenceLabel" class="detailedViewTextBox" value="" style="display:none" readonly="" />
					<input type="hidden" name="advFilterReferenceValue" value="" />
				</td>
				<td>
					<i class="vteicon md-link" name="setReferenceIcon" onclick="EditReport.setReferenceFilter(this)">launch</i>
					<i class="vteicon md-link" name="clearReferenceIcon" style="display:none" onclick="EditReport.clearReferenceFilter(this)">highlight_remove</i>
				</td>
				<td width="60">
					<select class="detailedViewTextBox advFilterGlue" name="advFilterGlue" style="display:none">
						<option value="and">{$APP.LBL_AND}</option>
						<option value="or">{$APP.LBL_OR}</option>
					</select>
				</td>
				<td width="40" align="right">
					<i class="vteicon md-link" onclick="EditReport.removeFilter(this)">delete</i>
				</td>
			</tr>
			
		</table>
		
		<table width="100%">
			<tr>
				<td align="center">
					<button class="crmbutton edit" onclick="EditReport.addFilter(this)">{$MOD.LBL_NEW_CONDITION}</button>
				</td>
			</tr>
		</table>
		
	</div>

	<div id="advFiltersBlockGlueMaster" class="advFilterGroupGlue" style="padding:8px;text-align:center;display:none">
		<select class="detailedViewTextBox" style="width:60px">
			<option value="and">{$APP.LBL_AND}</option>
			<option value="or">{$APP.LBL_OR}</option>
		</select>
	</div>

</div>
{* END OF TEMPLATE *}


{* container for filters *}
<div id="advFiltersContainer">

	{if $ADVFILTERS}
	{assign var="GROUPCOUNT" value=$ADVFILTERS|@count}
	{foreach item=GROUP key=GROUPIDX from=$ADVFILTERS}
	<div class="advFilterGroup">

		<div class="advFilterGroupBox">
			
			<table width="100%">
				<tr>
					<td align="right">
						<i class="vteicon md-link" onclick="EditReport.removeFilterGroup(this)">cancel</i>
					</td>
				</tr>
			</table>
		
			<table class="advFilterList" border="0" width="100%" align="center" cellspacing="2">
				
				{if $GROUP.conditions}
				{assign var="CONDCOUNT" value=$GROUP.conditions|@count}
				{foreach item=COND key=CONDIDX from=$GROUP.conditions}
				<tr height="40">
					<td width="80" valign="bottom"><b>{$APP.LBL_MODULE}</b></td>
					<td width="25%" colspan="6" class="rptChainContainer" valign="bottom">
						<div class="dvtCellInfo chainFirst">
							<span class="chainMainModule"></span>
							<span class="chainArrow">&gt;</span>
						</div>
						<div id="totalsChainModules{$GROUPIDX}_{$CONDIDX}" class="dvtCellInfo chainOthers">
							{foreach item=listmod from=$COND.listmodules}
								<select class="detailedViewTextBox chainModule" style="min-width:250px" onchange="EditReport.changeModulesPicklist(this, '#advFilterFields_{$GROUPIDX}_{$CONDIDX}')">
								{foreach item=mod from=$listmod.list}
									<option value="{$mod.value}" {if $mod.value eq $listmod.selected}selected=""{/if}>{$mod.label}</option>
								{/foreach}
								</select>
							{/foreach}
						</div>
					</td>
				</tr>
				
				<tr>
					<td><b>{$APP.Field}</b></td>
					<td width="30%">
						<select id="advFilterFields_{$GROUPIDX}_{$CONDIDX}" class="detailedViewTextBox filterFields" style="min-width:300px" onchange="EditReport.alignFilterField(this)">
							{foreach item=block from=$COND.listfields}
								<optgroup label="{$block.label}">
								{foreach item=fld from=$block.fields}
									<option value="{'"'|str_replace:'&quot;':$fld.value}" {if $COND.name eq $fld.value}selected=""{/if} 
										data-wstype="{$fld.wstype}" 
										data-uitype="{$fld.uitype}"
										data-module="{$fld.module}"
										data-fieldname="{$fld.fieldname}"
									>{$fld.label}</option>
								{/foreach}
								</optgroup>
							{/foreach}
						</select>
					</td>
					<td width="100">
						<select name="advFilterComparator" class="detailedViewTextBox" onclick="EditReport.changeFilterComparator(this)">
							{foreach key=compval item=complabel from=$COMPARATORS}
							<option value="{$compval}" {if $compval eq $COND.comparator}selected=""{/if}>{$complabel}</option>
							{/foreach}
						</select>
					</td>
					<td>
						<input type="text" name="advFilterValue" class="detailedViewTextBox" value="{$COND.value}" style="{if $COND.reference}display:none;{/if}{if $COND.comparator == 'bw'}width:40%;{/if}" />
						<span name="advFilterValueAnd" style="{if $COND.comparator != 'bw'}display:none{/if}"> {$APP.LBL_AND} </span>
						<input type="text" name="advFilterValue2" class="detailedViewTextBox" value="{$COND.value2}" style="{if $COND.comparator != 'bw'}display:none;{/if}width:40%" />
						
						<input type="text" name="advFilterReferenceLabel" class="detailedViewTextBox" value="{if $COND.reference}{$COND.reflabel}{/if}" {if !$COND.reference}style="display:none"{/if} readonly="" />
						<input type="hidden" name="advFilterReferenceValue" value="{if $COND.reference}{'"'|str_replace:'&quot;':$COND.refvalue}{/if}" />
					</td>
					<td>
						<i class="vteicon md-link" name="setReferenceIcon" onclick="EditReport.setReferenceFilter(this)" {if $COND.reference || $COND.comparator == 'bw'}style="display:none"{/if}>launch</i>
						<i class="vteicon md-link" name="clearReferenceIcon" onclick="EditReport.clearReferenceFilter(this)" {if !$COND.reference}style="display:none"{/if}>highlight_remove</i>
					</td>
					<td width="60">
						<select class="detailedViewTextBox advFilterGlue" name="advFilterGlue" {if $CONDIDX >= $CONDCOUNT-1}style="display:none;"{/if}>
							<option value="and" {if $COND.glue eq 'and'}selected=""{/if}>{$APP.LBL_AND}</option>
							<option value="or" {if $COND.glue eq 'or'}selected=""{/if}>{$APP.LBL_OR}</option>
						</select>
					</td>
					<td width="40" align="right">
						<i class="vteicon md-link" onclick="EditReport.removeFilter(this)">delete</i>
					</td>
				</tr>
				{/foreach}
				{/if}
				
			</table>
			<table width="100%">
				<tr>
					<td align="center">
						<button class="crmbutton edit" onclick="EditReport.addFilter(this)">{$MOD.LBL_NEW_CONDITION}</button>
					</td>
				</tr>
			</table>
		</div>

		<div class="advFilterGroupGlue" style="padding:8px;text-align:center;{if $GROUPIDX >= $GROUPCOUNT-1}display:none;{/if}">
			<select class="detailedViewTextBox" style="width:60px">
				<option value="and" {if $GROUP.glue eq 'and'}selected=""{/if}>{$APP.LBL_AND}</option>
				<option value="or" {if $GROUP.glue eq 'or'}selected=""{/if}>{$APP.LBL_OR}</option>
			</select>
		</div>
	
	</div>
	{/foreach}
	{/if}

</div>

<br>

<table width="100%">
	<tr>
		<td align="center">
			<br>
			<button class="crmbutton edit" onclick="EditReport.addFilterGroup()">{$MOD.LBL_NEW_GROUP}</button>
		</td>
	</tr>
</table>

{assign var="FLOAT_TITLE" value=$MOD.LBL_COMPARE_WITH_FIELD}
{assign var="FLOAT_WIDTH" value="700px"}
{capture assign="FLOAT_BUTTONS"}
<button class="crmbutton save" type="button" onclick="EditReport.applyReferenceFilter()">{$APP.LBL_SAVE_LABEL}</button>
{/capture}
{capture assign="FLOAT_CONTENT"}
<br>
<input type="hidden" id="compareFieldRef" value="" />
<table border="0" width="700">
	<tr>
		<td width="80"><b>{$APP.LBL_MODULE}</b></td>
		<td class="rptChainContainer" colspan="2">
			<div class="dvtCellInfo chainFirst">
				<span class="chainMainModule"></span>
				<span class="chainArrow">&gt;</span>
			</div>
			<div class="dvtCellInfo chainOthers">
				<select id="chainModuleComp1" class="detailedViewTextBox chainModule" style="min-width:200px" onchange="EditReport.changeModulesPicklist(this, '#selectCompareField')">
			</div>
		</td>
	</tr>
	<tr>
		<td width="80"><b>{$APP.Field}</b></td>
		<td width="50%">
			<select id="selectCompareField" class="detailedViewTextBox filterFields" style="min-width:300px"></select>
		</td>
		<td></td>
	</tr>
</table>
<br>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="CompareField"}

<script type="text/javascript">
EditReport.alignFilterFields();
</script>