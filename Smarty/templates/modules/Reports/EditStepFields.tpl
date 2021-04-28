{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@98894 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_SELECT_COLUMNS}</span><br>
	<span style="font-size:90%">{$MOD.LBL_SELECT_COLUMNS_TO_GENERATE_REPORTS}</span><hr>
</div>

<table border="0" width="100%" align="center">

	<tr>
		<td width="20%" align="right"><b>{$APP.LBL_MODULE}</b>&nbsp;&nbsp;</td>
		<td width="50%" class="rptChainContainer">
			<div class="dvtCellInfo chainFirst">
				<span id="fieldsMainModule" class="chainMainModule"></span>
				<span class="chainArrow">&gt;</span>
			</div>
			<div id="fieldsChainModules" class="dvtCellInfo chainOthers">
				<select id="fieldsModuleChain1" class="detailedViewTextBox chainModule" style="min-width:250px" onchange="EditReport.changeModulesPicklist(this, '#availfields')"></select>
			</div>
		</td>
		<td width="20%"></td>
		<td></td>
	</tr>
	
	<tr>
		<td align="right"><b>{$MOD.LBL_AVAILABLE_FIELDS}</b>&nbsp;&nbsp;</td>
		<td>
			<select id="availfields" class="detailedViewTextBox" size="8" multiple="" style="min-width:600px">
			</select>
		</td>
		<td align="center" nowrap="" valign="center">
			<button name="add" class="crmbutton edit" type="button" onclick="EditReport.addFields()"> {$APP.LBL_ADD_SELECTED} </button>
		</td>
		<td></td>

	</tr>
	
</table>

<br>

<p><b>{$MOD.LBL_ORDER_OF_SELECTED_FIELDS}:</b></p>
<br>

{strip}
<div id="selectedfields" style="width:300px;overflow:auto;overflow-y:hidden;white-space:nowrap;">
	{foreach item=fld from=$FIELDS}
	<div class="selectedField">
		<input type="hidden" name="fldvalue" value="{'"'|str_replace:'&quot;':$fld.name}" />
		<input type="hidden" name="flddata"
			data-wstype="{$fld.wstype}"
			data-uitype="{$fld.uitype}"
			data-module="{$fld.module}"
			data-fieldname="{$fld.fieldname}"
		/>
		<table cellspacing="0" cellpadding="0" width="100%">
			<tr height="25">
				<td class="fieldname">
					<span name="fieldLabel" style="padding:5px;" title="{$fld.label}">{$fld.label}</span><br>
					<div>
						{* crmv@98866 *}
						{assign var="translated_module" value=$fld.module|getTranslatedString}
						{assign var="first_letter" value=$translated_module|substr:0:1|strtoupper}
						<span class="fieldLabel vcenter"><i name="fieldIcon" class="icon-module icon-{$fld.module|strtolower}" data-first-letter="{$first_letter}"></i></span>
						<span class="fieldLabel vcenter" name="fieldModuleName" style="padding:4px;">{$fld.single_label}</span>
						{* crmv@98866e *}
					</div>
				</td>
				<td align="right" valign="top">
					<i class="vteicon" onclick="EditReport.removeField(this)">clear</i>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding:2px;" valign="top">
				
<table border="0" align="center" cellspacing="2" cellpadding="2" class="small" style="white-space:normal">
	
	<tr name="fieldPropFormula" style="display:none">
		<td>
			{$MOD.LBL_FORMULA}
			<div style="display:inline-block">
			<select name="fieldFormula" class="detailedViewTextBox" onchange="EditReport.changeFieldProperties(this)" style="max-width:200px">
				<option value="">-- {$APP.LBL_NONE} --</option>
				{foreach item=formula from=$FIELD_FUNCTIONS}
					<option value="{$formula.name}">{$formula.label}</option>
				{/foreach}
			</select>
			</div>
		</td>
	</tr>
	
	<tr name="fieldPropGrouping" {if $REPORT_TYPE != 'summary'}style="display:none"{/if}>
		<td>
			{$MOD.LBL_GROUP_BY_FIELD}&nbsp;
			<input type="checkbox" name="fieldGroupCheck" onchange="EditReport.changeFieldProperties(this)" {if $fld.group}checked=""{/if} />
		</td>
	</tr>
	
	<tr name="fieldPropSummary" {if $REPORT_TYPE != 'summary' || !$fld.group}style="display:none"{/if}>
		<td>
			{$MOD.LBL_SHOW_SUMMARY}&nbsp;
			<input type="checkbox" name="fieldSummary" onchange="EditReport.changeFieldProperties(this)" {if $fld.summary}checked=""{/if} />
		</td>
	</tr>
	
	<tr name="fieldPropSortorder" {if $REPORT_TYPE != 'summary'}style="display:none"{/if}>
		<td>
			{$MOD.LBL_GROUPING_SORT}&nbsp;
			<div style="display:inline-block">
			<select name="fieldGroupOrder" class="detailedViewTextBox notdropdown" onchange="EditReport.changeFieldProperties(this)">
				<option value="ASC" {if $fld.sortorder eq 'ASC'}selected=""{/if}>{$APP.Ascending}</option>
				<option value="DESC" {if $fld.sortorder eq 'DESC'}selected=""{/if}>{$APP.Descending}</option>
			</select>
			</div>
		</td>
		
	</tr>
	
</table>
				</td>
			</tr>
		</table>
	</div>
	{/foreach}
</div>
{/strip}

{* template for field *}
{strip}
<div id="selectFieldTemplate" class="selectedField" style="display:none">
		<input type="hidden" name="fldvalue" value="" />
		<input type="hidden" name="flddata"	/>
		<table cellspacing="0" cellpadding="0" width="100%">
			<tr height="25">
				<td class="fieldname">
					<span name="fieldLabel" style="padding:5px;" title=""></span><br>
					<div>
						<span class="fieldLabel vcenter"><i name="fieldIcon" class="icon-module"></i></span>
						<span class="fieldLabel vcenter" name="fieldModuleName" style="padding:4px;"></span>
					</div>
				</td>
				<td align="right" valign="top">
					<i class="vteicon" onclick="EditReport.removeField(this)">clear</i>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border-top:2px solid #e0e0e0;padding:2px;background-color:#fff" valign="top">
				
<table border="0" align="center" cellspacing="2" cellpadding="2" style="white-space:normal">
	
	<tr name="fieldPropFormula" style="display:none">
		<td>
			{$MOD.LBL_FORMULA}
			<div style="display:inline-block">
			<select name="fieldFormula" class="detailedViewTextBox" onchange="EditReport.changeFieldProperties(this)" style="max-width:200px">
			</select>
			</div>
		</td>
	</tr>
	
	<tr name="fieldPropGrouping">
		<td>
			{$MOD.LBL_GROUP_BY_FIELD}&nbsp;
			<input type="checkbox" name="fieldGroupCheck" onchange="EditReport.changeFieldProperties(this)" />
		</td>
	</tr>
	
	<tr name="fieldPropSummary" style="display:none">
		<td>
			{$MOD.LBL_SHOW_SUMMARY}&nbsp;
			<input type="checkbox" name="fieldSummary" onchange="EditReport.changeFieldProperties(this)" />
		</td>
	</tr>
	
	<tr name="fieldPropSortorder">
		<td>
			{$MOD.LBL_GROUPING_SORT}&nbsp;
			<div style="display:inline-block">
			<select name="fieldGroupOrder" class="detailedViewTextBox notdropdown" onchange="EditReport.changeFieldProperties(this)">
				<option value="ASC">{$APP.Ascending}</option>
				<option value="DESC">{$APP.Descending}</option>
			</select>
			</div>
		</td>
		
	</tr>
	
</table>
				</td>
			</tr>
		</table>
	</div>
{/strip}