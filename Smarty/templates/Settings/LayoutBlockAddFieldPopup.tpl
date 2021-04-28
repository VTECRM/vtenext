{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@158543 *}

{* Add Fields floating div *}
{assign var="FLOAT_TITLE" value=$MOD.LBL_ADD_FIELD}
{assign var="FLOAT_WIDTH" value="600px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<input type="hidden" name="mode" id="cfedit_mode" value="add">
<table border="0" celspacing="0" cellpadding="5" align="center" style="width:100%;">
	<tr valign="top">
		<td width="220">
			<table>
				<tr>
					<td>{$APP.LBL_SELECT_FIELD_TYPE}</td>
				</tr>
				<tr>
					<td>
						{* crmv@101683 *}
						<div name="cfcombo" id="cfcombo" class="layoutEditorFieldPicker">
							<table border="0">
								{foreach key=nfieldno item=nfield from=$NEWFIELDS}
									<tr><td align="left"><a id="field{$nfieldno}_{$entries.blockid}" href="javascript:void(0);" class="customMnu" onclick="makeFieldSelected(this,{$nfieldno},{$entries.blockid});">
										{if isset($nfield.vteicon)}
											<i class="vteicon customMnuIcon nohover">{$nfield.vteicon}</i>
										{elseif isset($nfield.vteicon2)}
											<i class="vteicon2 {$nfield.vteicon2} customMnuIcon nohover" aria-hidden="true"></i>
										{/if}
										&nbsp;
										<span class="customMnuText">{$nfield.label}</span>
									</a></td></tr>
									{if $nfield.relatedmods}
										{assign var=relmods value=$nfield.relatedmods}
										{assign var=relfieldno value=$nfieldno}
									{/if}
								{/foreach}
							</table>
						</div>
						{* crmv@101683e *}
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" align="left">
			<table width="98%" border="0" cellpadding="5" cellspacing="0">
				<tr>
					<td class="dataLabel" nowrap="nowrap" align="right" width="30%">{$MOD.LBL_LABEL}</td>
					<td align="left" width="70%">
					<input id="fldLabel_{$entries.blockid}" value="" type="text" class="detailedViewTextBox">
					</td>
				</tr>
				<tr id="lengthdetails_{$entries.blockid}">
					<td class="dataLabel" nowrap="nowrap" align="right">{$MOD.LBL_LENGTH}</td>
					<td align="left">
					<input type="text" id="fldLength_{$entries.blockid}" value="" class="detailedViewTextBox">
					</td>
				</tr>
				<tr id="decimaldetails_{$entries.blockid}" style="display:none">
					<td class="dataLabel_{$entries.blockid}" nowrap="nowrap" align="right">{$MOD.LBL_DECIMAL_PLACES}</td>
					<td align="left">
					<input type="text" id="fldDecimal_{$entries.blockid}" value="" class="detailedViewTextBox">
					</td>
				</tr>
				<tr id="picklistdetails_{$entries.blockid}" style="display:none">
					<td class="dataLabel" nowrap="nowrap" align="right">{$MOD.LBL_PICK_LIST_VALUES}</td>
					<td align="left">
					<textarea id="fldPickList_{$entries.blockid}" rows="10"></textarea>
					</td>
				</tr>
				{* crmv@98570 *}
				<tr id="onclickdetails_{$entries.blockid}" style="display:none">
					<td class="dataLabel" align="right" nowrap="nowrap">{$MOD.LBL_FIELD_BUTTON_ONCLICK}</td>
					<td><input type="text" id="fldOnclick_{$entries.blockid}" value="function(view[,param])" maxlength="50" class="detailedViewTextBox"/></td>
				</tr>
				<tr id="codedetails_{$entries.blockid}" style="display:none">
					<td class="dataLabel" align="right" nowrap="nowrap">{$MOD.LBL_FIELD_BUTTON_CODE}</td>
					<td><textarea id="fldCode_{$entries.blockid}"></textarea></td>
				</tr>
				{* crmv@98570e *}
				{* crmv@101683 *}
				<tr id="usersdetails_{$entries.blockid}" style="display:none">
					<td class="dataLabel" nowrap="nowrap" align="right">{'Users'|getTranslatedString}</td>
					<td align="left">
						<select class="small notdropdown" id="fldCustomUserPick_{$entries.blockid}" style="width:225px" size="10" multiple>
						{foreach key=id item=name from=$USERSLIST}
							<option value="{$id}">{$name}</option>
						{/foreach}
						</select>
					</td>
				</tr>
				{* crmv@101683e *}
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="2">
			<button type="button" name="cancel" class="crmbutton cancel" onclick="hideFloatingDiv('addfield_{$entries.blockid}');">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			<button type="button" name="save" class="crmbutton save" onclick="LayoutEditor.getCreateCustomFieldForm('{$MODULE}','{$entries.blockid}','add');">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
			<input type="hidden" name="fieldType_{$entries.blockid}" id="fieldType_{$entries.blockid}" value="">
			<input type="hidden" name="selectedfieldtype_{$entries.blockid}" id="selectedfieldtype_{$entries.blockid}" value="">
		</td>
	</tr>
</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="addfield_`$entries.blockid`"}
<!-- end custom field -->