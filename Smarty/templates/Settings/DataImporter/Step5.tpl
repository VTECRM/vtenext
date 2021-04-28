{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@65455 *}

<div>
	<p>{$MOD.LBL_DIMPORT_STEP5_INTRO}</p>
</div>
<br>

{* some JS variables *}
<script type="text/javascript">
	var DataImporterVars = {ldelim}{rdelim};
	DataImporterVars['module'] = '{$DESTMODULE}';
	DataImporterVars['modulelabel'] = '{$DESTMODULE|getTranslatedString:$DESTMODULE}'; // crmv@90287
	DataImporterVars['formats'] = ({$ALLFORMATS|@json_encode});
	DataImporterVars['formulas'] = ({$ALLFORMULAS|@json_encode});
	DataImporterVars['fields'] = ({$FIELDSPROPS|@json_encode});
</script>

{assign var=MAPPING value=$STEPVARS.dimport_mapping}
{assign var=KEYCOL value=false}

<table border="0" width="100%" class="small listTable" id="dimport_table_mapping" cellspacing="0" cellpadding="5">
	<tr>
		{if $HAS_HEADER eq true}
		<td class="small colHeader" width="16%"><b>
			{if $SOURCETYPE eq 'database'}
				{'LBL_SF_COLUMNS'|@getTranslatedString:'CustomView'}
			{else}
			
				{'LBL_FILE_COLUMN_HEADER'|@getTranslatedString:'Import'}
			{/if}
		</b></td>
		{/if}
		<td class="small colHeader" width="14%"><b>{'LBL_ROW_1'|@getTranslatedString:'Import'}</b></td>
		<td class="small colHeader" width="14%"><b>{$MOD.LBL_VALUE_FORMAT}</b></td>
		<td class="small colHeader" width="14%"><b>{'Chart Formula'|@getTranslatedString:'Charts'}</b></td>
		
		<td class="small colHeader" width="14%"><b>{'LBL_CRM_FIELDS'|@getTranslatedString:'Import'}</b></td>
		{if $DESTMODULE != 'ProductRows'}
		<td class="small colHeader" width="10%"><b>{$APP.LBL_KEY_FIELD}</b></td>
		{/if}
		<td class="small colHeader"><b>{'LBL_DEFAULT_VALUE'|@getTranslatedString:'Import'}</b></td>
	</tr>
	
	{foreach key=srccol item=row from=$MAPPING}
	<tr id="dimport_row_{$srccol}">
	
		{if $STEPVARS.dimport_mapping_keycol eq $srccol && $row.field}
			{assign var=KEYCOL value=$srccol}
		{/if}
	
		{* name of first row *}
		{if $HAS_HEADER eq true}
		<td class="listTableRow" id="dimport_cell_{$srccol}_srccol">{$row.label}</td> {* crmv@71496 crmv@105144 *}
		{/if}
		
		{* value of first row *}
		<td class="listTableRow" id="dimport_cell_{$srccol}_srcvalue">{$row.srcvalue}</td>
		
		{* format of value *}
		<td class="listTableRow" id="dimport_cell_{$srccol}_srcformat">
			<select id="dimport_map_{$srccol}_srcformat" name="dimport_map_{$srccol}_srcformat" onchange="DataImporterFields.onSelectFormat('{$srccol}')" style="display:none">
				<option value="">{$APP.LBL_NONE}</option>
				{foreach item=format from=$ALLFORMATS}
					<option value="{$format.name}" {if $row.srcformat eq $format.name}selected=""{/if}>{$format.label}</option>
				{/foreach}
			</select>
			<input type="text" id="dimport_map_{$srccol}_srcformatval" name="dimport_map_{$srccol}_srcformatval" value="{$row.srcformatval}" {if $row.srcformatval eq ""}style="display:none"{/if} />
			<select id="dimport_map_{$srccol}_srcformatlist" name="dimport_map_{$srccol}_srcformatlist" style="width:100%;{if $row.srcformatval eq ""}display:none;{/if}"></select> {* crmv@117880 *}
		</td>
		
		{* formula for value *}
		<td class="listTableRow" id="dimport_cell_{$srccol}_formula" >
			<select id="dimport_map_{$srccol}_formula" name="dimport_map_{$srccol}_formula" onchange="DataImporterFields.onSelectFormula('{$srccol}')" style="display:none">
				<option value="">{$APP.LBL_NONE}</option>
				{foreach item=formula from=$ALLFORMULAS}
					<option value="{$formula.name}" {if $row.formula eq $formula.name}selected=""{/if}>{$formula.label}</option>
				{/foreach}
			</select>
			<input type="text" id="dimport_map_{$srccol}_formulaval" name="dimport_map_{$srccol}_formulaval" value="{$row.formulaval}" {if $row.formulaval eq ""}style="display:none"{/if} />
		</td>
		
		{* crm field *}
		<td class="listTableRow" id="dimport_cell_{$srccol}_field">
			<select id="dimport_map_{$srccol}_field" name="dimport_map_{$srccol}_field" onchange="DataImporterFields.onFieldChange('{$srccol}')">
				<option value="">{$APP.LBL_NONE}</option>
				{foreach item=field from=$ALLFIELDS}
					<option value="{$field.fieldname}" {if $row.field eq $field.fieldname}selected=""{/if}>{$field.label} {if $field.mandatory}(*){/if}</option>
				{/foreach}
			</select>
			
			<span id="dimport_map_{$srccol}_reference_label" style="display:none"><p>{$MOD.LBL_IMPORT_LINKKEY_FIELD}:</p><span>
			{* the relation field *}
			<select id="dimport_map_{$srccol}_reference" name="dimport_map_{$srccol}_reference" onchange="" {if $row.reference eq ""}style="display:none"{/if}> {* DataImporterFields.onFieldChange('{$srccol}') *}
				<option value="">{$APP.LBL_NONE}</option>
				{foreach item=field from=$OTHERKEYS}
					{assign var=fieldkey value="`$field.module`:`$field.keyfield.field`"}
					<option value="{$fieldkey}" {if $row.reference eq $fieldkey}selected=""{/if}>{$field.modulelabel}: {$field.keyfield.label}</option>
				{/foreach}
			</select>
			<span id="dimport_map_{$srccol}_reference_keylabel" style="display:none"><p>{$MOD.LBL_IMPORT_LINKKEY_KEYFIELD}</p><span> {* crmv@90287 *}
		</td>
		
		{* key field *}
		{if $DESTMODULE != 'ProductRows'}
		<td class="listTableRow" id="dimport_cell_{$srccol}_keyfield">
			<input type="checkbox" class="dimport_map_keycol" id="dimport_map_{$srccol}_keycol" {if $KEYCOL === $srccol}checked=""{/if} {if !$row.field}disabled=""{/if} onclick="return DataImporterFields.onKeyFieldCheck('{$srccol}');" /> {* crmv@90497 *}
		</td>
		{/if}
		
		{* default value *}
		<td class="listTableRow" id="dimport_cell_{$srccol}_default"></td>
		
	</tr>
	{/foreach}
</table>

{* other global inputs *}
<input type="hidden" name="dimport_mapping_keycol" id="dimport_mapping_keycol" value="{$KEYCOL}"/>

{* default elements *}
{include file="Settings/DataImporter/Step5Defaults.tpl" FOR_MODULE=$DESTMODULE}

{* default fields for create *}
<div id="dimport_div_deffields">
{include file="Settings/DataImporter/Step5DefFields.tpl"}
</div>


{* set the defaults, formats and formulas *}
<script type="text/javascript">
	{foreach key=srccol item=row from=$MAPPING}
		DataImporterFields.alignFormat('{$srccol}', '{$row.srcformat}', '{$row.srcformatval}', '{$row.srcformatlist}'); {* crmv@117880 *}
		DataImporterFields.alignFormula('{$srccol}', '{$row.formula}', '{$row.formulaval}');
		{if $row.field neq ''}
		DataImporterFields.alignDefault('{$srccol}', '{$row.default}');
		DataImporterFields.alignReference('{$srccol}', '{$row.reference}');
		{/if}
	{/foreach}
</script>