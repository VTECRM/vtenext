{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@104180 crmv@106857 crmv@112297 crmv@115268 *}

<script type="text/javascript" src="modules/SDK/src/220/220.js"></script>

{if $sdk_mode eq 'detail'}

	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div>
		
		{assign var=COLUMNS value=$keyoptions.columns}
		{assign var=COLCOUNT value=$COLUMNS|@count}
		{assign var=ROWCOUNT value=$keyval|@count}
		{assign var=SINGLE_LINE value=$keyoptions.single_line}
		{assign var=SHOW_ACTIONS value=$keyoptions.show_actions}
		
		<table width="100%" name="{$fldname}" class="tablefield_table table-striped">
			{* intestazione *}
			{if $SINGLE_LINE}
			<thead>
			<tr>
				{if $SHOW_ACTIONS}
					<th width="100" class="tablefield_hcell dvtCellLabel">{$APP.LBL_ACTIONS}</th>
				{/if}
				{foreach item=COL from=$COLUMNS}
				{* if $COL.readonly neq 100 *}
				<th class="tablefield_hcell dvtCellLabel">{$COL.label}</th>
				{* /if *}
				{/foreach}
			</tr>
			</thead>
			{/if}
			
			{* righe esistenti *}
			<tbody class="tablefield_rows">
			{if $ROWCOUNT > 0}
				{$keyoptions.htmlrows}
			{/if}
			</tbody>
			
			{* no add row in detail! *}
		</table>
	</div>
	
{elseif $sdk_mode eq 'edit'}

	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}" {if $readonly eq 100}style="display:none"{/if}>
		{assign var=COLUMNS value=$maindata[3].columns}
		{assign var=COLCOUNT value=$COLUMNS|@count}
		{assign var=ROWCOUNT value=$fldvalue|@count}
		{assign var=SINGLE_LINE value=$maindata[3].single_line}
		{assign var=SHOW_ACTIONS value=$maindata[3].show_actions}

		<input type="hidden" name="{$fldname}" value="{if $ROWCOUNT > 0}1{/if}"> {* used only to have the key in the request *}
		<input type="hidden" name="{$fldname}_lastrowno" value="{$ROWCOUNT}"> {* used to keep track of the last row number (otherwise deletion can cause duplicates) *}
		<table width="100%" name="{$fldname}" class="tablefield_table">
			{* intestazione *}
			{if $SINGLE_LINE}
			<thead>
			<tr>
				{if $SHOW_ACTIONS}
					<th width="100" class="tablefield_hcell dvtCellLabel">{$APP.LBL_ACTIONS}</th>
				{/if}
				{foreach item=COL from=$COLUMNS}
				{* if $COL.readonly neq 100 *}
				<th class="tablefield_hcell dvtCellLabel">{$COL.label}
					{* crmv@199115 *}
					{if !empty($COL.helpinfo)}
						<i class="vteicon md-sm md-link valign-bottom" onclick="vtlib_field_help_show(this, '{$fldname}_{$COL.fieldname}');return false;">help</i>
					{/if}
					{* crmv@199115e *}
				</th>
				{* /if *}
				{/foreach}
			</tr>
			</thead>
			{/if}
			
			{* righe esistenti *}
			<tbody class="tablefield_rows">
			{if $ROWCOUNT > 0}
				{$maindata[3].htmlrows}
			{/if}
			</tbody>
			
			{* ultima riga *}
			<tfoot>
				<tr>
					<td></td>
					<td colspan="{$COLCOUNT}" align="right" class="tablefield_fcell">
						{if $readonly lt 99}
							<button type="button" class="crmbutton save" onclick="TableField.addRow('{$fldname}')">{'LBL_ADD_ROW'|getTranslatedString}</button>
						{/if}
					</td>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			jQuery(document).ready(function(){ldelim}
				TableField.setFieldInfo('{$fldname}', {$COLUMNS|@json_encode});
				{if !empty($maindata[3].typeofdata)}
					{foreach item=tmp from=$maindata[3].typeofdata name="validation"}
						TableField.addValidationInfo('{$fldname}', {$smarty.foreach.validation.index}, '{$tmp|@json_encode}');
					{/foreach}
				{/if}
				TableField.showUpDownRow('{$fldname}');
				if (jQuery('#enable_conditionals').val() == '1') {ldelim}
					ProcessScript.initEditViewConditionals(ProcessScript.condition_fields); // crmv@134058
				{rdelim}
			{rdelim});
		</script>
	</div>
	
{/if}