{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@32257 crmv@162158 *}

<table id="field_table" class="vtetable">
	<thead>
		<tr>
			<th style="width:2%;"></th>
			<th style="width:30%;">{'LBL_FIELDLABEL'|@getTranslatedString:$MODULE}</th>
			<th style="width:44%;">{'LBL_DEFAULT_VALUE'|@getTranslatedString:$MODULE}</th>
			<th style="width:2%;">{'LBL_HIDDEN'|@getTranslatedString:$MODULE}</th>
			<th style="width:2%;">{'LBL_REQUIRED'|@getTranslatedString:$MODULE}</th>
			<th style="width:20%;">{'LBL_NEUTRALIZEDFIELD'|@getTranslatedString:$MODULE}</th>
		</tr>
	</thead>
	<tbody>
		{assign var="CNT" value=0}
		{foreach item=field from=$WEBFORMFIELDS name=fieldloop}
			{assign var="CNT" value=$CNT+1}
			{if $WEBFORM->isWebformFieldPermitted($field)}
				<tr id="field_row">
					<td align="right" valign="top" colspan="1">
						{if $field.mandatory eq 1}
							<input type="checkbox" name="fields[]" checked="checked"  value="{$field.name}" record="true" disabled="true">
							<input type="hidden" name="fields[]" value="{$field.name}" record="true" >
						{else}
							{if $WEBFORMID}
								{if $WEBFORM->isWebformField($WEBFORMID,$field.name) eq true}
									<input type="checkbox" name="fields[]" id="check_{$field.name}" record="false" checked="checked" value="{$field.name}" onClick=Webforms.showHideElement('value[{$field.name}]','required[{$field.name}]','jscal_trigger_{$field.name}','mincal_{$field.name}','hidden[{$field.name}]','meta_variables_{$field.name}')>
								{else}
									<input type="checkbox" name="fields[]" id="check_{$field.name}" record="false" value="{$field.name}" onClick=Webforms.showHideElement('value[{$field.name}]','required[{$field.name}]','jscal_trigger_{$field.name}','mincal_{$field.name}','hidden[{$field.name}]','meta_variables_{$field.name}')>
								{/if}
							{else}
								<input type="checkbox" name="fields[]" id="check_{$field.name}" record="false" value="{$field.name}" onClick=Webforms.showHideElement('value[{$field.name}]','required[{$field.name}]','jscal_trigger_{$field.name}','mincal_{$field.name}','hidden[{$field.name}]','meta_variables_{$field.name}')>
							{/if}
						{/if}
					</td>
					<td class="dvtCellLabel" align="left" valign="top" colspan="1">
						{if $field.mandatory neq 1}
							<label for="check_{$field.name}">
						{/if}
						{if $field.mandatory eq 1}
							<font color="red">*</font>
						{/if}
						{$field.label|@getTranslatedString:$MODULE}
						{if $field.mandatory neq 1}
							</label>
						{/if}
					</td>
					<td class="dvtCellInfo">
						{if $field.mandatory neq 1}
							{if $WEBFORMID && $WEBFORM->isWebformField($WEBFORMID,$field.name) eq true}
								{assign var="defaultvalue" value=$WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name)}
								{if $field.type.name eq 'picklist' || $field.type.name eq 'multipicklist'} {* crmv@167234 *}
									{assign var="val_arr" value=$WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name)}
									{assign var="values" value=","|explode:$val_arr.0}
									<select fieldtype="{$field.type.name}" fieldlabel="{$field.label}" class="small" name="value[{$field.name}][]" id="value[{$field.name}]" style="display:inline;" {if $field.type.name eq 'multipicklist'}multiple="multiple" size="5"{/if}>
											<option value="">{'LBL_SELECT_VALUE'|@getTranslatedString:$MODULE}</option>
										{foreach item=option from=$field.type.picklistValues name=optionloop}
											<option value="{$option.value}" {if in_array($option.value,$defaultvalue)}selected="selected"{/if}>{$option.label}</option>
										{/foreach}
									</select>
								{elseif $field.type.name eq 'date'} {* crmv@167234 *}
									{* crmv@190519 *}
									<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="text" onblur="this.className='detailedViewTextBox input-inline';" onfocus="this.className='detailedViewTextBoxOn input-inline';" class="detailedViewTextBox input-inline" id="value[{$field.name}]"  name="value[{$field.name}]" value="{$defaultvalue[0]}" >
									<i class="vteicon md-link md-text" id="jscal_trigger_{$field.name}">event</i>
									<font size=1 id="mincal_{$field.name}"><em old="(yyyy-mm-dd)">({$DATE_FORMAT})</em></font>
									<script type="text/javascript" id="date_{$CNT}">
										(function() {ldelim}
											setupDatePicker('value[{$field.name}]', {ldelim}
												trigger: 'jscal_trigger_{$field.name}',
												date_format: "{$DATE_FORMAT|strtoupper}",
												language: "{$APP.LBL_JSCALENDAR_LANG}",
											{rdelim});
										{rdelim})();
									</script>
									{* crmv@190519e *}
								{elseif $field.type.name eq 'text'}
									<textarea fieldtype="{$field.type.name}" fieldlabel="{$field.label}" rows="2" onblur="this.className='detailedViewTextBox'" onfocus="this.className='detailedViewTextBoxOn'" class="detailedViewTextBox"  id="value[{$field.name}]" name="value[{$field.name}]"  value="{$defaultvalue[0]}">{$defaultvalue[0]}</textarea>
								{elseif $field.type.name eq 'boolean'}
									<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="checkbox"  id="value[{$field.name}]" name="value[{$field.name}]" {if $defaultvalue[0] eq 'on'}checked="checked"{/if}" >
								{else}
									{if $field.name eq 'salutationtype'}
										<select fieldtype="{$field.type.name}" fieldlabel="{$field.label}" class="small" id="value[{$field.name}]" name="value[{$field.name}]">
											<option value="" {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq ""}selected="selected"{/if}>--None--</option>
											<option value="Mr." {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq "Mr."}selected="selected"{/if}>Mr.</option>
											<option value="Ms." {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq "Ms."}selected="selected"{/if}>Ms.</option>
											<option value="Mrs." {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq "Mrs."}selected="selected"{/if}>Mrs.</option>
											<option value="Dr." {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq "Dr."}selected="selected"{/if}>Dr.</option>
											<option value="Prof." {if $WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name) eq "Prof."}selected="selected"{/if}>Prof</option>
										</select>
									{else}
										<select class="small" id="meta_variables_{$field.name}" style="display:inline;" onchange="Webforms.insertMetaVar('value[{$field.name}]',this.value)">
											<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
											{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
												<option value="{$META_VALUE}">{$META_LABEL}</option>
											{/foreach}
										</select>
										<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="text" onblur="this.className='detailedViewTextBox';" onfocus="this.className='detailedViewTextBoxOn';" class="detailedViewTextBox" id="value[{$field.name}]"  name="value[{$field.name}]" value="{$defaultvalue[0]}" style="display:inline;" />
									{/if}
								{/if}
							{else}
								{if $field.type.name eq 'picklist' || $field.type.name eq 'multipicklist'} {* crmv@197996 *}
									{assign var="val_arr" value=$WEBFORM->retrieveDefaultValue($WEBFORMID,$field.name)}
									{assign var="values" value=","|explode:$val_arr}
									<select fieldtype="{$field.type.name}" fieldlabel="{$field.label}" class="small" name="value[{$field.name}][]" id="value[{$field.name}]" style="display:none;" class="small" {if $field.type.name eq 'multipicklist'}multiple="multiple" size="5"{/if}>
										<option value="" {if $field.default eq $option.value} selected="selected"{/if}>{'LBL_SELECT_VALUE'|@getTranslatedString:$MODULE}</option>
										{foreach item=option from=$field.type.picklistValues name=optionloop}
											<option value="{$option.value}" {if $field.default eq $option.value} selected="selected"{/if} >{$option.label}</option>
										{/foreach}
									</select>
								{elseif $field.type.name eq 'date'}
									{* crmv@190519 *}
									<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="text" onblur="this.className='detailedViewTextBox input-inline';" onfocus="this.className='detailedViewTextBoxOn input-inline';" class="detailedViewTextBox input-inline" id="value[{$field.name}]"  name="value[{$field.name}]" value="{$field.default}" style="display:none;">
									<i class="vteicon md-link md-text" id="jscal_trigger_{$field.name}" style="display:none;">event</i>
									<font size=1 id="mincal_{$field.name}" style="display:none;"><em old="(yyyy-mm-dd)">({$DATE_FORMAT})</em></font>
									<script type="text/javascript" id="date_{$CNT}">
										(function() {ldelim}
											setupDatePicker('value[{$field.name}]', {ldelim}
												trigger: 'jscal_trigger_{$field.name}',
												date_format: "{$DATE_FORMAT|strtoupper}",
												language: "{$APP.LBL_JSCALENDAR_LANG}",
											{rdelim});
										{rdelim})();
									</script>
									{* crmv@190519e *}
								{elseif $field.type.name eq 'text'}
										<textarea fieldtype="{$field.type.name}" fieldlabel="{$field.label}" rows="2" onblur="this.className='detailedViewTextBox'" onfocus="this.className='detailedViewTextBoxOn'" class="detailedViewTextBox"  id="value[{$field.name}]" name="value[{$field.name}]"  value="{$field.default}" style="display:none;">{$field.default}</textarea>
								{elseif $field.type.name eq 'boolean'}
									<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="checkbox"  id="value[{$field.name}]" name="value[{$field.name}]" style="display:none;" {if $field.default}checked="checked"{/if}>
								{else}
									{if $field.name eq 'salutationtype'}
										<select fieldtype="{$field.type.name}" fieldlabel="{$field.label}" class="small" id="value[{$field.name}]" name="value[{$field.name}]" style="display:none;">
											<option value="" {if $field.default eq ""}selected="selected"{/if}>--None--</option>
											<option value="Mr." {if $field.default eq "Mr."}selected="selected"{/if}>Mr.</option>
											<option value="Ms." {if $field.default eq "Ms."}selected="selected"{/if}>Ms.</option>
											<option value="Mrs." {if $field.default eq "Mrs."}selected="selected"{/if}>Mrs.</option>
											<option value="Dr." {if $field.default eq "Dr."}selected="selected"{/if}>Dr.</option>
											<option value="Prof." {if $field.default eq "Prof."}selected="selected"{/if}>Prof</option>
										</select>
									{else}
										<select class="small" id="meta_variables_{$field.name}" style="display:none;" onchange="Webforms.insertMetaVar('value[{$field.name}]',this.value)">
											<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
											{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
												<option value="{$META_VALUE}">{$META_LABEL}</option>
											{/foreach}
										</select>
										<input fieldtype="{$field.type.name}" fieldlabel="{$field.label}" type="text" onblur="this.className='detailedViewTextBox';" onfocus="this.className='detailedViewTextBoxOn';" class="detailedViewTextBox" id="value[{$field.name}]"  name="value[{$field.name}]" value="{$field.default}" style="display:none;" />
									{/if}
								{/if}
							{/if}
						{/if}
					</td>
					<td align="center" colspan="1">
						{if $field.mandatory eq 1}
							<input type="checkbox" disabled="disabled" value="{$field.name}" style="display:inline;" >
						{else}
							{if $WEBFORMID}
								{if $WEBFORM->isWebformField($WEBFORMID,$field.name) eq true && $WEBFORM->isRequired($WEBFORMID,$field.name) eq true}
									<input type="checkbox" id="hidden[{$field.name}]" name="hidden[]" value="{$field.name}" disabled="disabled" style="display:inline;" >
								{elseif $WEBFORM->isWebformField($WEBFORMID,$field.name) eq true && $WEBFORM->isHidden($WEBFORMID,$field.name) eq true}
									<input type="checkbox" id="hidden[{$field.name}]" name="hidden[]" value="{$field.name}" checked="checked" style="display:inline;" >
								{else}
									{if $WEBFORM->isWebformField($WEBFORMID,$field.name)}
										<input type="checkbox" id="hidden[{$field.name}]" name="hidden[]" value="{$field.name}" style="display:inline;">
									{else}
										<input type="checkbox" id="hidden[{$field.name}]" name="hidden[]" value="{$field.name}" style="display:none;">
									{/if}
								{/if}
							{else}
								<input type="checkbox" id="hidden[{$field.name}]" name="hidden[]" value="{$field.name}" style="display:none;">
							{/if}
						{/if}
					</td>
					<td align="center" colspan="1">
						{if $field.mandatory eq 1}
							<input type="checkbox" checked="checked" disabled="disabled" value="{$field.name}" style="display:inline;" >
							<input type="hidden" id="required[{$field.name}]" name="required[]" value="{$field.name}" />
						{else}
							{if $WEBFORMID}
								{if $WEBFORM->isWebformField($WEBFORMID,$field.name) eq true && $WEBFORM->isRequired($WEBFORMID,$field.name) eq true}
									<input type="checkbox" id="required[{$field.name}]" name="required[]" value="{$field.name}" checked="checked" style="display:inline;" onClick="Webforms.checkHidden(this.value,this.checked);">
								{else}
									{if $WEBFORM->isWebformField($WEBFORMID,$field.name)}
										<input type="checkbox" id="required[{$field.name}]" name="required[]" value="{$field.name}" style="display:inline;" onClick="Webforms.checkHidden(this.value,this.checked);">
									{else}
										<input type="checkbox" id="required[{$field.name}]" name="required[]" value="{$field.name}" style="display:none;" onClick="Webforms.checkHidden(this.value,this.checked);">
									{/if}
								{/if}
							{else}
								<input type="checkbox" id="required[{$field.name}]" name="required[]" value="{$field.name}" style="display:none;" onClick="Webforms.checkHidden(this.value,this.checked);">
							{/if}
						{/if}
					</td>
					<td class="dvtCellLabel" align="left" colspan="1">
						{$field.name}  {* crmv@179954 *}
					</td>
				</tr>
			{/if}
		{/foreach}
		<script type="test/javascript" id="counter">
			var count={$CNT};
		</script>
	</tbody>
</table>