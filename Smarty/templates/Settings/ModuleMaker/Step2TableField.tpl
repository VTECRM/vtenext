{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@102879 - add table field *}
{* crmv@106857 *}

{* find the fieldno for the table *}
{foreach key=nfieldno item=nfield from=$NEWFIELDS}
	{if $nfield.uitype eq 220}
		{assign var=tablefieldno value=$nfieldno}
	{/if}
{/foreach}

<div id="mmaker_div_addtablefield" {if !$LAYOUT_MANAGER}class="crmvDiv floatingDiv"{/if} style="width:100%">
	<input type="hidden" id="mmaker_newtablefield_blockno"  value="" />
	<input type="hidden" id="mmaker_newtablefield_fieldno"  value="{$tablefieldno}" />
	<input type="hidden" id="mmaker_newtablefield_editfieldno"  value="" />
	
	{if !$LAYOUT_MANAGER}
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg"> {* removed class floatingHandle, to disable the draggable *}
				<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td width="50%"><b>{$APP.LBL_ADD_FIELD_TABLE}</b></td>
					<td width="50%" align="right">
						<input type="button" name="save" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmButton small save" onclick="TableFieldConfig.saveConfig()"/>
						<input type="button" name="cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmButton small cancel" onclick="TableFieldConfig.cancelConfig()"/>
					</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
	{/if}
	<div class="crmvDivContent" style="max-height:100%;height:95%">
		<table border="0" cellpadding="5" cellspacing="1" width="98%" align="center">
			{* crmv@190916 *}
			<tr>
				<td width="20%" align="right">
					<b>{"FieldLabel"|getTranslatedString:"Settings"}</b>&nbsp;&nbsp;
				</td>
				<td>
					<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" name="newtablefieldprop_val_label" id="newtablefieldprop_val_label" value="" maxlength="50" />
					</div>
				</td>
				<td width="20%"></td>
			</tr>
			<tr>
				<td width="20%" align="right">
					<b>{$MOD.LBL_PERMISSIONS}</b>&nbsp;&nbsp;
				</td>
				<td>
					<div class="dvtCellInfo">
						<select name="newtablefieldprop_val_readonly" id="newtablefieldprop_val_readonly" onchange="TableFieldConfig.changePermission(this)">
							<option value="1">{'Read/Write'|getTranslatedString:'Settings'}</option>
							<option value="99">{'Read Only '|getTranslatedString:'Settings'}</option>
							<option value="100">{'LBL_HIDDEN'|getTranslatedString:'Users'}</option>
						</select>
					</div>
				</td>
				<td width="20%"></td>
			</tr>
			<tr name="newtablefieldprop_mandatory">
				<td width="20%" align="right">
					<b>{$MOD.LBL_MANDATORY_FIELD}</b>&nbsp;&nbsp;
				</td>
				<td>
					<div class="dvtCellInfo">
					<input type="checkbox" name="newtablefieldprop_val_mandatory" id="newtablefieldprop_val_mandatory" />
					</div>
				</td>
				<td width="20%"></td>
			</tr>
			{* crmv@190916e *}
			<tr>
				<td align="right">
					<b>{$APP.LBL_COLUMNS}</b>&nbsp;&nbsp;
				</td>
				<td align="center">
					<div name="cfcombo" class="layoutEditorFieldPicker">
						<table border="0" width="100%" id="newtablefields">
							{foreach key=nfieldno item=nfield from=$NEWTABLEFIELDCOLUMNS}
								<tr><td align="left"><a id="newtablefield_{$nfieldno}" href="javascript:void(0);" class="newFieldMnu" 
									onclick="TableFieldConfig.toggleField('{$nfieldno}');"
									ondblclick="TableFieldConfig.toggleField('{$nfieldno}', 1);" 
									data-fieldno="{$nfieldno}"
									data-uitype="{$nfield.uitype}"
									data-props="{","|implode:$nfield.properties}"
								>
									{if isset($nfield.vteicon)}
										<i class="vteicon customMnuIcon nohover">{$nfield.vteicon}</i> {* crmv@102879 *}
									{elseif isset($nfield.vteicon2)}
										<i class="vteicon2 {$nfield.vteicon2} customMnuIcon nohover"></i> {* crmv@102879 *}
									{/if}
									&nbsp;
									<span class="newFieldLabel customMnuText">{$nfield.label}</span>
								</a></td></tr>
								{if $nfield.relatedmods}
									{assign var=relmods value=$nfield.relatedmods}
									{assign var=relfieldno value=$nfieldno}
								{/if}
								{* crmv@101683 *}
								{if $nfield.users}
									{assign var=users value=$nfield.users}
								{/if}
								{* crmv@101683e *}
							{/foreach}
							{* <input type="hidden" id="newrelfieldno" value="{$relfieldno}" /> *}
						</table>
					</div>
				</td>
				<td>
					<button class="crmbutton edit" type="button" onclick="TableFieldConfig.addSelectedFields()">{$APP.LBL_ADD_SELECTED}</button>
				</td>
			</tr>
		</table>
		<div style="margin:10px">
			<p><b>{"LBL_SELECTED_COLUMNS"|getTranslatedString:'Reports'}</b></p>
		</div>
		<div id="selectedcolumns" style="width:100%;height:90px;overflow:auto;{if $LAYOUT_MANAGER}overflow-y:auto;{else}overflow-y:hidden;margin:10px;{/if}white-space:nowrap;">
		</div>
		
		<div id="selectColumnTemplate" class="selectedField" style="display:none">
			
			<input type="hidden" name="fldvalue" value="" />
			<input type="hidden" name="fldno" value="" />
			<input type="hidden" name="fldname" value="" />
			<input type="hidden" name="flddata"	/>
		<table cellspacing="0" cellpadding="0" width="100%">
			<tr height="25">
				<td class="fieldname">
					<input type="text" name="fieldLabel" style="padding:5px;font-size:14px;max-width:120px" title="" value="" placeholder="{$APP.LBL_COLUMN_NAME}"/><br>
					<div>
						<span class="fieldLabel vcenter"><i name="fieldIcon" class="vteicon"></i></span>
						<span class="fieldLabel vcenter" name="fieldModuleName" style="padding:4px;"></span>
					</div>
				</td>
				<td align="right" valign="top">
					<i class="vteicon" onclick="TableFieldConfig.removeBox(this)">clear</i>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border-top:2px solid #e0e0e0;padding:2px;background-color:#fff" valign="top">
				
					{* field properties *}
					<table border="0" name="mmaker_newtablefield_props" width="100%">
						<tr name="newtablefieldprop_label" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_LABEL}</td>
							<td><input type="text" name="newtablefieldprop_val_label" value="" maxlength="50" style="width:100px" /></td>
						</tr>
						<tr name="newtablefieldprop_length" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_LENGTH}</td>
							<td><input type="text" name="newtablefieldprop_val_length" value="" maxlength="4" style="width:100px" /></td>
						</tr>
						<tr name="newtablefieldprop_decimals" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_DECIMAL_PLACES}</td>
							<td><input type="text" name="newtablefieldprop_val_decimals" value="" maxlength="1" style="width:100px" /></td>
						</tr>
						<tr name="newtablefieldprop_autoprefix" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_USE_PREFIX}</td>
							<td><input type="text" name="newtablefieldprop_val_autoprefix" value="" maxlength="5" style="width:100px" /></td>
						</tr>
						<tr name="newtablefieldprop_picklistvalues" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_PICK_LIST_VALUES}</td>
							<td><textarea name="newtablefieldprop_val_picklistvalues"></textarea></td>
						</tr>
						{if $relmods}
						{* crmv@131239 *}
						<tr name="newtablefieldprop_relatedmods_selected" class="newfieldprop">
							<td><input type="hidden" name="newtablefieldprop_val_relatedmods" value="" /></td>
						</tr>
						{* crmv@131239e *}
						<tr name="newtablefieldprop_relatedmods" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_RELATED_MODULES}</td>
							<td><select name="newtablefieldprop_val_relatedmods" multiple="multiple" size="8">
							{foreach key=modname item=modlabel from=$relmods}
								<option value="{$modname}">{$modlabel}</option>
							{/foreach}
							</select></td>
						</tr>
						{/if}
						{* crmv@98570 *}
						<tr name="newtablefieldprop_onclick" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_FIELD_BUTTON_ONCLICK}</td>
							<td><input type="text" name="newtablefieldprop_val_onclick" value="" maxlength="50" /></td>
						</tr>
						<tr name="newtablefieldprop_code" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_FIELD_BUTTON_CODE}</td>
							<td><textarea name="newtablefieldprop_val_code"></textarea></td>
						</tr>
						{* crmv@98570e *}
						{* crmv@101683 *}
						<tr name="newtablefieldprop_users" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{'Users'|getTranslatedString}</td>
							<td><select name="newtablefieldprop_val_users" multiple="multiple" size="8" style="width:100px">
							{foreach key=id item=name from=$users}
								<option value="{$id}">{$name}</option>
							{/foreach}
							</select></td>
						</tr>
						{* crmv@101683e *}
						<tr name="newtablefieldprop_readonly" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_PERMISSIONS}</td>
							<td><select name="newtablefieldprop_val_readonly" onchange="TableFieldConfig.changePermission(this)">
								<option value="1" selected="">{'Read/Write'|getTranslatedString:'Settings'}</option>
								<option value="99">{'Read Only '|getTranslatedString:'Settings'}</option>
								<option value="100">{'LBL_HIDDEN'|getTranslatedString:'Users'}</option>
							</select></td>
						</tr>
						<tr name="newtablefieldprop_mandatory" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">{$MOD.LBL_MANDATORY_FIELD}</td>
							<td><input type="checkbox" value="1" name="newtablefieldprop_val_mandatory" /></td>
						</tr>
						<tr name="newtablefieldprop_newline" class="newfieldprop">
							<td class="dataLabel" align="right" width="40%">Vai a capo</td>
							<td><input type="checkbox" value="1" name="newtablefieldprop_val_newline" /></td>
						</tr>
					</table>
				
				
				</td>
			</tr>
		</table>
		
		</div>
		
		{* defaults *}
		<script type="text/javascript">
			if (window.ModuleMakerFields) {ldelim}
				ModuleMakerFields.newTableFieldsDefaults = {ldelim}{rdelim};
				{foreach item=fld key=nfield from=$NEWTABLEFIELDCOLUMNS}
					{if !empty($fld.defaults)}
						ModuleMakerFields.newTableFieldsDefaults[{$nfield}] = {ldelim}{rdelim};
						{foreach key=prop item=val from=$fld.defaults}
							ModuleMakerFields.newTableFieldsDefaults[{$nfield}]['{$prop}'] = '{$val}';
						{/foreach}
					{/if}
				{/foreach}
			{rdelim}
		</script>
	</div>
	{* <div class="closebutton" onclick="ModuleMakerFields.hideFloatingDiv('mmaker_div_addtablefield')"></div> *}
</div>