{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@82419 *}
{if $NOLABEL neq true}
	<div {if $OLD_STYLE eq true}style="float:left; padding-top:5px;"{/if}>	{* crmv@57221 *}
		{if $uitype eq 23 || $uitype eq 5 || $uitype eq 6}
			{assign var="labelfor" value="jscal_field_$fldname"}
		{else}
			{assign var="labelfor" value=$fldname}
		{/if}
		<label for="{$labelfor}" class="dvtCellLabel" {if $AJAXEDITTABLEPERM}ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',jQuery('#fieldCont_{$keyfldid}').get(0));"{/if}>
			{if $massedit eq '1'}
				{* crmv@109685 *}
				<input type="checkbox" name="{$fldname}_mass_edit_check" id="{$fldname}_mass_edit_check" class="small" {if $mass_edit_check}checked{/if}>
				<label for="{$fldname}_mass_edit_check" class="dvtCellLabel">
				{* crmv@109685e *}
			{/if}
			{$label}
			{if !empty($keycursymb) && ($uitype eq '71' || $uitype eq '72')}
				({$keycursymb})
			{/if}
			{if $massedit eq '1'}
				</label>
			{/if}
			{* vtlib customization: Help information for the fields *}
			{if $FIELDHELPINFO && $FIELDHELPINFO.$fldname}
				<i class="vteicon md-sm md-link valign-bottom" onclick="vtlib_field_help_show(this, '{$fldname}');return false;">help</i> {* crmv@138022 *}
			{/if}
			{* END *}
			{$FIELDHEADEROTHER}	{* crmv@147720 *}
		</label>
		{* crmv@57221 *}
		{if $OLD_STYLE eq false}
			{include file="FieldButtons.tpl"}
		{/if}
		{* crmv@57221e *}
		<div id="editbutton_{$label}" style="float:right;"></div>
		{* crmv@92272 crmv@106857 crmv@160843 *}
		{if $smarty.request.enable_editoptions eq 'yes'}
			{assign var="editoptionsfieldnames" value='|'|explode:$smarty.request.editoptionsfieldnames}
			<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:right; display:none;" onclick="ActionUpdateScript.insertTableFieldValue(this,'{$fldname}','seq')">input</i>
			<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:right; display:none;">
			<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:right; display:none;">
				<select class="populateField" onchange="ActionUpdateScript.changeTableFieldOpt(this,'{$fldname}')">
					{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
				</select>
			</div>
			{if $fldname|in_array:$editoptionsfieldnames}
				{if empty($EDITOPTIONSFIELDNAME)}
					{assign var="EDITOPTIONSFIELDNAME" value=$fldname}				
				{/if}
				{if empty($EDITOPTIONSTYPE)}
					{assign var="EDITOPTIONSTYPE" value="fieldnames"}				
				{/if}
				<div class="editoptions" fieldname="{$EDITOPTIONSFIELDNAME}" optionstype="{$EDITOPTIONSTYPE}" style="float:right;{if !empty($EDITOPTIONSDISPLAY)}display:{$EDITOPTIONSDISPLAY}{/if}"></div>
			{/if}
		{/if}
		{* crmv@92272e crmv@106857e crmv@160843e *}
	</div>
{/if}