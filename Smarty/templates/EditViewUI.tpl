{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@160843 *} {* crmv@181170 *}

{* crmv@sdk-18509 *}
{if $SDK->isUitype($uitype) eq 'true'}
	{assign var="sdk_mode" value="edit"}
	{assign var="sdk_file" value=$SDK->getUitypeFile('tpl',$sdk_mode,$uitype)}
	{if $sdk_file neq ''}
		{if $SDK->isOldUitype($uitype)}
			{assign var="usefldlabel" value=$fldlabel}
			<div><table cellpadding="0" cellspacing="0" width="100%"><tr>
				{include file=$sdk_file}
			</tr></table></div>
		{else}
			{include file=$sdk_file}
		{/if}
	{/if}
{* crmv@sdk-18509 e *}
{* vtlib customization *}
{elseif $uitype eq '10'}
	{* crmv@171575 *}
	{if empty($field_type_name)}
		{assign var="field_type_name" value=$fldname|cat:"_type"}
	{/if}
	{* crmv@171575e *}
	{if $NOLABEL neq true && $OLD_STYLE eq false}	{* crmv@57221 *}
		<div {if $OLD_STYLE eq true}style="float:left; padding-top:5px;"{/if}>
			<span class="dvtCellLabel">
				{if $MASS_EDIT eq '1'}
					<input type="checkbox" name="{$fldname}_mass_edit_check" id="{$fldname}_mass_edit_check" class="small" >
					<label for="{$fldname}_mass_edit_check" class="dvtCellLabel">
				{else}
					<label for="{$fldname}_display" class="dvtCellLabel">
				{/if}
				{$fldlabel.displaylabel}</label>
				{if $FIELDHELPINFO && $FIELDHELPINFO.$fldname}
					<i class="vteicon" onclick="vtlib_field_help_show(this, '{$fldname}');">help_outline</i>
				{/if}
			</span>
			{* crmv@92272 crmv@106857 *}
			{if $smarty.request.enable_editoptions eq 'yes'}
				<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:right; display:none;" onclick="ActionUpdateScript.insertTableFieldValue(this,'{$fldname}','seq')">input</i>
				<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:right; display:none;">
				<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:right; display:none;">
					<select class="populateField" onchange="ActionUpdateScript.changeTableFieldOpt(this,'{$fldname}')">
						{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
					</select>
				</div>
				{assign var="editoptionsfieldnames" value='|'|explode:$smarty.request.editoptionsfieldnames}
				{if $fldname|in_array:$editoptionsfieldnames}
					<div class="editoptions" fieldname="other_{$fldname}" optionstype="referencefieldnames" style="float:right;"></div>
				{/if}
			{/if}
			{* crmv@92272e crmv@106857e *}
		</div>
	{/if}
	<table cellspacing="0" cellspacing="0" width="100%">
		<tr>
		{* crmv@57221 *}
		{if $OLD_STYLE eq true}
			<td width="10%">
				<div>
					<span class="dvtCellLabel">
						{if $MASS_EDIT eq '1'}
							<input type="checkbox" name="{$fldname}_mass_edit_check" id="{$fldname}_mass_edit_check" class="small" >
							<label for="{$fldname}_mass_edit_check">
						{/if}
						{$fldlabel.displaylabel}
						{if $MASS_EDIT eq '1'}
							</label>
						{/if}
						{if $FIELDHELPINFO && $FIELDHELPINFO.$fldname}
							<i class="vteicon" onclick="vtlib_field_help_show(this, '{$fldname}');">help_outline</i>
						{/if}
					</span>
				</div>
			</td>
		{/if}
		{* crmv@57221e *}
		{assign var="popup_params" value="&action=Popup&html=Popup_picker&form=vtlibPopupView&forfield=$fldname&srcmodule=$MODULE&forrecord=$ID$extra_popup_params"}	{* crmv@29190 crmv@126696 *} 
		{if count($fldlabel.options) eq 1}
			<td>
			{assign var="use_parentmodule" value=$fldlabel.options.0}
			<input type='hidden' class='small' name="{$field_type_name}" value="{$use_parentmodule}"> {* crmv@16312 crmv@171575 *}
		{else}
			<td width="20%" class="{$DIVCLASS}" style="padding:0px 5px;vertical-align:middle;text-align:center;">
				<select id="{$field_type_name}" class="detailedViewTextBox" name="{$field_type_name}" onChange='changeReferenceType(this,"{$fromlink}","{$fldname}","{$popup_params}");'>	{* crmv@29190 crmv@48241 crmv@171575 *}
				{foreach item=option from=$fldlabel.options}
					<option value="{$option}"
						{if $fldlabel.selected == $option}selected{assign var="FIELDTYPE" value=$fldlabel.selected}{/if}>	{* crmv@92272 *}
						{$option|@getTranslatedString:$MODULE}
					</option>
				{/foreach}
				</select>
			</td>
			<td width="{if $OLD_STYLE eq true}70{else}80{/if}%">	{* crmv@57221 *}
		{/if}
		{assign var=fld_displayvalue value=$fldvalue.displayvalue}
		<div {if $fld_displayvalue|trim eq ''}class="{$DIVCLASS}"{else}class="dvtCellInfoOff"{/if} style="position:relative; {if $smarty.request.enable_editoptions eq 'yes' && $FIELDTYPE eq 'Other'}display:none;{/if}">	{* crmv@92272 *}
			{* crmv@21048m *}	{* crmv@29190 *}
			<input id="{$fldname}" name="{$fldname}" type="hidden" value="{$fldvalue.entityid}">
			{assign var=fld_style value='class="detailedViewTextBox" readonly'}
			{if $fld_displayvalue|trim eq ''}
				{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
				{assign var=fld_style value='class="detailedViewTextBox"'}
			{/if}
			<input id="{$fldname}_display" name="{$fldname}_display" type="text" value="{$fld_displayvalue}" {$fld_style} autocomplete="off">  {* crmv@113776 *}
			{* crmv@126696 *}
			{if $editViewType eq ''}
				{if $fromlink eq 'qcreate'}
					{assign var="editViewType" value="QcEditView"}
				{else}
					{assign var="editViewType" value="EditView"}
				{/if}
			{/if}
			{* crmv@126696e *}
			<div class="dvtCellInfoImgRx">
				<script type="text/javascript">
				var sdk_popup_hidden_elements = eval({$SDK->getPopupHiddenElements($MODULE,$fldname,'autocomplete')});
				reloadAutocomplete('{$fldname}','{$fldname}_display',"module="+document.{$editViewType}.{$field_type_name}.value+"{$popup_params}",sdk_popup_hidden_elements); {* crmv@171575 *}
				</script>
				<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_SELECT}" onclick='openPopup("index.php?module="+document.{$editViewType}.{$field_type_name}.value+"&action=Popup&html=Popup_picker&form=vtlibPopupView&forfield={$fldname}&srcmodule={$MODULE}&forrecord={$ID}{$extra_popup_params}{$SDK->getPopupHiddenElements($MODULE,$fldname)}","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200");'>view_list</i> {* crmv@126696 crmv@171575 *}
				{* crmv@37211 *}
				<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_CLEAR}" onClick="document.{$editViewType}.{$fldname}.value=''; document.{$editViewType}.{$fldname}_display.value=''; enableReferenceField(document.{$editViewType}.{$fldname}_display); return false;">highlight_off</i> {* crmv@126696 *}
				{* crmv@37211e *}
				{* crmv@21048me *}	{* crmv@29190e *}
			</div>
		</div>
		{* crmv@92272 *}
		{if $smarty.request.enable_editoptions eq 'yes'}
			<div {if $fld_displayvalue|trim eq ''}class="{$DIVCLASS}"{else}class="dvtCellInfoOff"{/if} id="div_other_{$fldname}" {if $FIELDTYPE neq 'Other'}style="display:none"{/if}">
				<input type="text" id="other_{$fldname}" name="other_{$fldname}" value="{$fld_displayvalue}" class="detailedViewTextBox"/> {* crmv@195745 *}
			</div>
		{/if}
		{* crmv@92272e *}
		</td>
		</tr>
	</table>
{* END *}
{elseif $uitype eq 3 || $uitype eq 4}	<!-- Non Editable field, only configured value will be loaded -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASSOTHER}dvtCellInfoOff">
    	<input readonly type="text" tabindex="{$vt_tab}" name="{$fldname}" id ="{$fldname}" {if $MODE eq 'edit'} value="{$fldvalue}" {else} value="{$MOD_SEQ_ID}" {/if} class="detailedViewTextBox">
    </div>
<!--   //crmv@8056e -->
 <!--   //crmv@7231 - crmv@7216 crmv@7220-->
{elseif $uitype eq 11 || $uitype eq 1 || $uitype eq 13 || $uitype eq 7 || $uitype eq 9 || $uitype eq 1112 || $uitype eq 1013 || $uitype eq 1014}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<!--   //crmv@7231 -->
	{if $uitype eq 1112 && ($fldvalue neq '' && $fldvalue neq '--None--') }
		<div class="{$DIVCLASS}">{$fldvalue}<input type="hidden" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}"></div>
	{else}
		<div class="{$DIVCLASS}"><input type="text" tabindex="{$vt_tab}" name="{$fldname}" id="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox" {if $GRIDSEARCH eq true}onkeyup="callGridSearch(event,'input','{$FOLDERID}');"{/if}></div>
	{/if}
{elseif $uitype eq 19}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<!-- In Add Comment are we should not display anything -->
	{if $fldlabel eq $MOD.LBL_ADD_COMMENT}
		{if !$CONDITIONAL_LOAD}
			{assign var=fldvalue value=""}
			{* crmv@160733 *}
			<div style="text-align:center;width:50%;margin:-20px auto auto auto;" class="checkbox">
				<label for="confinfo_check">
					<input type="checkbox" name="confinfo_check" id="confinfo_check" onchange="VTE.HelpDesk.ConfidentialInfo.onChangeCheckbox('{$MODULE}', '{$ID}', this);" />
					{$MOD.LBL_SAVE_CONFIDENTIAL_COMMENT}
				</label>
				<i id="confinfo_edit_icon" class="vteicon md-link md-sm valign-bottom" style="display:none" title="{$APP.LBL_EDIT_BUTTON_LABEL}" onclick="VTE.HelpDesk.ConfidentialInfo.onEditPassword('{$MODULE}', '{$ID}');">create</i>
			</div>
			<input type="hidden" name="confinfo_save_pwd" id="confinfo_save_pwd" />
			<input type="hidden" name="confinfo_save_more" id="confinfo_save_more" />
			{include file="modules/HelpDesk/ConfidentialInfoPopups.tpl" editmode="editview" keyfldname=$fldname label=$fldlabel}
			{* crmv@160733e *}
		{/if}
	{/if}
	<div class="{$DIVCLASS}">
		<!-- crmv@manuele -->
		{if $MOBILE eq 'yes'}
			{assign var=cols value="25"}
		{else}
			{assign var=cols value="90"}
		{/if}
		<!-- crmv@manuele -->
		<textarea class="detailedViewTextBox" tabindex="{$vt_tab}" id="{$fldname}" name="{$fldname}" cols="{$cols}" rows="8">{$fldvalue}</textarea>
		{if $fldlabel eq $MOD.Solution}
			<input type="hidden" name="helpdesk_solution" value="{$fldvalue}">
		{/if}
	</div>
{elseif $uitype eq 21}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<textarea value="{$fldvalue}" id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox" rows=2>{$fldvalue}</textarea>
	</div>
{* <!-- ds@8 project tool --> *}
{elseif $uitype eq 25}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel}
	<div class="{$DIVCLASS}">
		<textarea readonly value="{$fldvalue|escape}" name="{$fldname}" tabindex="{$vt_tab}" rows="2" cols="5">{$fldvalue|escape}</textarea>
		<input type="hidden" name="projects_ids" value="{$PROJECTS_IDS}">
		<button onclick='openPopup("index.php?module=Projects&action=Popup&html=Popup_picker&form=HelpDeskEditView&select=enable&record={$RECORD}","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200"); return false;' class="crmbutton small save" style="margin-left:150px;">{$MOD.choose}</button>{* crmv@21048m *}
	</div>
{* <!--  ds@8e --> *}
{elseif $uitype eq 15 || $uitype eq 16 || $uitype eq 1015}	{* <!-- DS-ED VlMe 31.3.2008 - add uitype 504 --> *}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT EDITOPTIONSFIELDNAME="other_"|cat:$fldname EDITOPTIONSTYPE="pickfieldnames" EDITOPTIONSDISPLAY=$secondvalue.editoptions_div_display}
	{if $secondvalue.enable_editoptions eq 'yes'}
		<table cellspacing="0" width="100%">
		<tr>
			<td width="20%" class="{$DIVCLASS}">
			<select id="{$fldname}_type" class="detailedViewTextBox" name="{$fldname}_type" onChange="changePicklistType(this,'{$fldname}');">
				{foreach item=arr from=$secondvalue.type_options}
					<option value="{$arr.0}" {$arr.2}>{$arr.1}</option>
				{/foreach}
			</select>
			</td>
			<td width="80%">
	{/if}
	<div class="{$DIVCLASS}" {if $secondvalue.picklist_display neq ''}style="display:{$secondvalue.picklist_display}"{/if}>
   		<select id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox" 
			{if $GRIDSEARCH eq true}onChange="callGridSearch(this,'select','{$FOLDERID}');"{/if}
			{if $MODULE eq 'Calendar' && $fldname eq 'eventstatus'}onchange="getSelectedStatus()"{/if} {* crmv@191756 *}
		>
		{foreach item=arr from=$fldvalue}
			{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
				<option value="{$arr[0]}" {$arr[2]}>{$arr[0]}</option>
			{else}
				<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
			{/if}
		{foreachelse}
			<option value=""></option>
			<option value="" style='color: #777777' disabled>{$APP.LBL_NONE}</option>
		{/foreach}
		</select>
	</div>
	{if $secondvalue.enable_editoptions eq 'yes'}
		<div class="{$DIVCLASS}" id="div_other_{$fldname}" style="display:{$secondvalue.editoptions_div_display}">
			<input type="text" id="other_{$fldname}" name="other_{$fldname}" class="detailedViewTextBox" value="{$secondvalue.other_value}"/>
		</div>
		</td>
		</tr>
		</table>
	{/if}
{elseif $uitype eq 33}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT EDITOPTIONSFIELDNAME="other_"|cat:$fldname EDITOPTIONSTYPE="pickfieldnames" EDITOPTIONSDISPLAY=$secondvalue.editoptions_div_display}
	{if $secondvalue.enable_editoptions eq 'yes'}
		<table cellspacing="0" width="100%">
		<tr valign="top">
			<td width="20%" class="{$DIVCLASS}">
			<select id="{$fldname}_type" class="detailedViewTextBox" name="{$fldname}_type" onChange="changePicklistType(this,'{$fldname}');">
				{foreach item=arr from=$secondvalue.type_options}
					<option value="{$arr.0}" {$arr.2}>{$arr.1}</option>
				{/foreach}
			</select>
			</td>
			<td width="80%">
	{/if}
	{if $GRIDSEARCH eq true}
		{assign var="fldvaluehidden" value=""}
		{assign var="fldvaluestr" value=""}
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected' && $fldvaluestr eq ''}
				{assign var="fldvaluehidden" value=$arr[1]}
				{assign var="fldvaluestr" value=$arr[0]}
			{elseif $arr[2] eq 'selected'}
				{assign var="fldvaluehidden" value=$fldvaluehidden|cat:"|##|"|cat:$arr[1]}
				{assign var="fldvaluestr" value=$fldvaluestr|cat:", "|cat:$arr[0]}
			{/if}
		{/foreach}
		<div class="{$DIVCLASS}" id="{$fldname}GridInput" style="position:relative">
			<div style="width:80%;">
				<input type="hidden" name="{$fldname}" id="{$fldname}" value="{$fldvaluehidden}">
				<input type="text" name="{$fldname}Str" id="{$fldname}Str" value="{$fldvaluestr}" class="detailedViewTextBox" onFocus="jQuery('#{$fldname}GridInput').hide();jQuery('#{$fldname}GridSelect').show();">
			</div>
			<div class="dvtCellInfoImgRx" align="right" style="width:20%;cursor:pointer;" onClick="jQuery('#{$fldname}GridInput').hide();jQuery('#{$fldname}GridSelect').show();">
				<i class="vteicon vtesorticon md-text">arrow_drop_down</i>
			</div>
		</div>
		<div class="{$DIVCLASS}" id="{$fldname}GridSelect" style="display:none;">
			<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
				<tr>
					<td align="center" width="12">
						<input type="checkbox" id="{$fldname}GridAll" style="cursor: pointer;" onChange="gridSelectToggle(this,'{$fldname}Grid','{$fldname}');">
					</td>
					<td>
						<label for="{$fldname}GridAll"><i>{'LBL_ALL'|getTranslatedString}</i></label>
						<div style="float:right;">
							<i class="vteicon md-link" onClick="callGridSearch(event,'select','{$FOLDERID}');">search</i>
						</div>
					</td>
				</tr>
			</table>
			<div style="max-height:100px;overflow:auto;">
				<table border="0" cellpadding="0" cellspacing="5" width="100%" class="small">
					{foreach item=arr from=$fldvalue}
						<tr>
							<td align="center" width="12">
								<input type="checkbox" id="{$fldname}Grid{$arr[1]}" style="cursor: pointer;" value="{$arr[1]}" onChange="gridSelectValue(this,'{$fldname}');" {if $arr[2] eq "selected"}checked{/if}>
							</td>
							<td>
								<label for="{$fldname}Grid{$arr[1]}">{$arr[0]}</label>
							</td>
						</tr>
					{/foreach}
				</table>
			</div>
		</div>
	{else}
		<div class="{$DIVCLASS}" {if $secondvalue.picklist_display neq ''}style="display:{$secondvalue.picklist_display}"{/if}>
			{* crmv@177395 - reverted TT-@178347 *}
			<select MULTIPLE id="{$fldname}" name="{$fldname}[]" size="4" tabindex="{$vt_tab}" class="detailedViewTextBox">
			{foreach item=arr from=$fldvalue}
				<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
			{/foreach}
			</select>
		</div>
	{/if}
	{if $secondvalue.enable_editoptions eq 'yes'}
		<div class="{$DIVCLASS}" id="div_other_{$fldname}" style="display:{$secondvalue.editoptions_div_display}">
			<input type="text" id="other_{$fldname}" name="other_{$fldname}" class="detailedViewTextBox" value="{$secondvalue.other_value}"/>
		</div>
		</td>
		</tr>
		</table>
	{/if}
{* crmv@31171 crmv@106856 *}
{elseif $uitype eq 53}
	{* crmv@57221 *}
	{if $OLD_STYLE eq false}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT EDITOPTIONSTYPE="smownerfieldnames" EDITOPTIONSFIELDNAME="other_"|cat:$fldname EDITOPTIONSDISPLAY=$thirdvalue.editoptions_div_display}
	{/if}
	{* crmv@57221e *}
	{* crmv@106578 *}
	{if !$EditViewForm}
		{assign var="EditViewForm" value="EditView"}
	{/if}
	{* crmv@106578e *}
	{if $fromlink eq 'qcreate'}
		{assign var="editViewType" value="QcEditView"}
	{else}
		{assign var="editViewType" value="EditView"}
	{/if}
	{assign var="popup_params" value="&action=Popup&html=Popup_picker&form=vtlibPopupView&forfield=$fldname&srcmodule=$MODULE&forrecord=$ID"}	{* crmv@29190 *}
	{assign var=check value=1}
	{foreach key=key_one item=arr from=$fldvalue}
		{foreach key=sel_value item=value from=$arr}
			{if $value ne ''}
				{assign var=check value=$check*0}
			{else}
				{assign var=check value=$check*1}
			{/if}
		{/foreach}
	{/foreach}
	{* crmv@92272 *}
	{if $check eq 0}
		{assign var=select_user value='selected="selected"'}
		{assign var=select_group value=''}
		{assign var=select_other value=''}
		{assign var=select_adv value=''}
		{assign var=style_user value='display:block'}
		{assign var=style_group value='display:none'}
		{assign var=style_other value='display:none'}
	{else}
		{assign var=select_user value=''}
		{assign var=select_group value='selected="selected"'}
		{assign var=select_other value=''}
		{assign var=select_adv value=''}
		{assign var=style_user value='display:none'}
		{assign var=style_group value='display:block'}
		{assign var=style_other value='display:none'}
	{/if}
	{if $assigntype eq 'O' || ($thirdvalue.enable_editoptions && ($thirdvalue.editoptions_div_display eq 'block' || ($smarty.request.assigned_user_id neq '' && $smarty.request.assigned_user_id|is_numeric === false)))}
		{assign var=select_user value=''}
		{assign var=select_group value=''}
		{if $smarty.request.assigned_user_id eq 'advanced_field_assignment'}
			{assign var=select_adv value='selected="selected"'}
		{else}
			{assign var=select_other value='selected="selected"'}
		{/if}
		{assign var=style_user value='display:none'}
		{assign var=style_group value='display:none'}
		{assign var=style_other value='display:block'}
	{/if}
	{* crmv@92272e *}
	{if empty($fldgroupname)}
		{assign var=fldgroupname value="assigned_group_id"}
	{/if}
	{if empty($fldothername)}
		{assign var=fldothername value="other_"|cat:$fldname}
	{/if}
	{if empty($assign_user_div)}
		{assign var="assign_user_div" value="assign_user"}
	{/if}
	{if empty($assign_team_div)}
		{assign var="assign_team_div" value="assign_team"}
	{/if}
	{if empty($assign_other_div)}
		{assign var="assign_other_div" value="assign_other"}
	{/if}
	{if empty($assigntypename)}
		{assign var="assigntypename" value="assigntype"}
	{/if}
	<table cellspacing="0" cellspacing="0" width="100%">
		<tr>
			{* crmv@57221 *}
			{if $OLD_STYLE eq true}
				<td width="10%">
					{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
				</td>
			{/if}
			{* crmv@57221e *}
			<td width="20%" class="{$DIVCLASS}" style="padding:0px 5px;vertical-align:middle;text-align:center;">
				{if $secondvalue neq '' || $thirdvalue.enable_editoptions} {* crmv@139856 *}
					{if $fromlink eq 'qcreate'}
						<select id="{$fldname}_type" class="detailedViewTextBox" name="{$assigntypename}" onChange='toggleAssignType(this.value,"{$assign_user_div}","{$assign_team_div}","{$assign_other_div}"); document.QcEditView.{$fldname}_display.value=""; document.QcEditView.{$fldname}.value=""; enableReferenceField(document.QcEditView.{$fldname}_display); document.QcEditView.{$fldgroupname}_display.value=""; document.QcEditView.{$fldgroupname}.value=""; enableReferenceField(document.QcEditView.{$fldgroupname}_display); closeAutocompleteList("{$fldname}_display"); closeAutocompleteList("{$fldgroupname}_display");'>	{* crmv@29190 *}
					{else}
						<select id="{$fldname}_type" class="detailedViewTextBox" name="{$assigntypename}" onChange='toggleAssignType(this.value,"{$assign_user_div}","{$assign_team_div}","{$assign_other_div}"); document.{$EditViewForm}.{$fldname}_display.value=""; document.{$EditViewForm}.{$fldname}.value=""; enableReferenceField(document.{$EditViewForm}.{$fldname}_display); document.{$EditViewForm}.{$fldgroupname}_display.value=""; document.{$EditViewForm}.{$fldgroupname}.value=""; enableReferenceField(document.{$EditViewForm}.{$fldgroupname}_display); closeAutocompleteList("{$fldname}_display"); closeAutocompleteList("{$fldgroupname}_display");'>	{* crmv@29190 *}
					{/if}
						<option value="U" {$select_user}>{$APP.LBL_USER}</option>
					{* crmv@139856 *}
					{if $secondvalue neq ''}
						<option value="T" {$select_group}>{$APP.LBL_GROUP}</option>
					{/if}
					{* crmv@139856e *}
						{* crmv@92272 *}
						{if $thirdvalue.enable_editoptions}
							<option value="O" {$select_other}>{'LBL_OTHER'|getTranslatedString:'Users'}</option>
							{if $thirdvalue.skip_advanced_type_option neq true}<option value="A" {$select_adv}>{'LBL_ADVANCED'|getTranslatedString}</option>{/if}
						{/if}
						{* crmv@92272e *}
					</select>
				{else}
					<input type="hidden" id="{$fldname}_type" name="{$assigntypename}" value="U">
				{/if}
			</td>
			<td width="{if $OLD_STYLE eq true}70{else}80{/if}%" style="position:relative">	{* crmv@57221 *} {* crmv@98866 *}
				{assign var=fld_value value=""}
				{foreach key=key_one item=arr from=$fldvalue}
					{foreach key=sel_value item=value from=$arr}
						{if $value eq 'selected'}
							{assign var=fld_value value=$key_one}
							{assign var=fld_displayvalue value=$sel_value}
						{/if}
					{/foreach}
				{/foreach}
				<div {if $fld_displayvalue|trim eq ''}class="{$DIVCLASS}"{else}class="dvtCellInfoOff"{/if} id="{$assign_user_div}" style="position:relative; {$style_user}">
					<input id="{$fldname}" name="{$fldname}" type="hidden" value="{$fld_value}">
					{assign var=fld_style value='class="detailedViewTextBox" readonly'}
					{if $fld_displayvalue|trim eq ''}
						{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
						{assign var=fld_style value='class="detailedViewTextBox"'}
					{/if}
					<input id="{$fldname}_display" name="{$fldname}_display" type="text" value="{$fld_displayvalue}" {$fld_style} autocomplete="off">  {* crmv@113776 *}
					<script type="text/javascript">
					initAutocompleteUG('Users','{$fldname}','{$fldname}_display','{$JSON->encode($fldvalue)|addslashes}');	{* crmv@31950 *}
					</script>
					<div class="dvtCellInfoImgRx">
						<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_SELECT}" onclick='toggleAutocompleteList("{$fldname}_display");'>view_list</i>
						{* crmv@37211 *}
						{if $fromlink eq 'qcreate'}
							<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_CLEAR}" onClick="document.QcEditView.{$fldname}.value=''; document.QcEditView.{$fldname}_display.value=''; enableReferenceField(document.QcEditView.{$fldname}_display); return false;">highlight_off</i>
						{else}
							<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_CLEAR}" onClick="document.{$EditViewForm}.{$fldname}.value=''; document.{$EditViewForm}.{$fldname}_display.value=''; enableReferenceField(document.{$EditViewForm}.{$fldname}_display); return false;">highlight_off</i> {* crmv@152754 *}
						{/if}
						{* crmv@37211e *}
					</div>
				</div>
				{if $secondvalue neq ''}
					{assign var=fld_secondvalue value=""}
					{foreach key=key_one item=arr from=$secondvalue}
						{foreach key=sel_value item=value from=$arr}
							{if $value eq 'selected'}
								{assign var=fld_secondvalue value=$key_one}
								{assign var=fld_displaysecondvalue value=$sel_value}
							{/if}
						{/foreach}
					{/foreach}
					<div {if $fld_displaysecondvalue|trim eq ''}class="{$DIVCLASS}"{else}class="dvtCellInfoOff"{/if} id="{$assign_team_div}" style="position:relative; {$style_group}">
						<input id="{$fldgroupname}" name="{$fldgroupname}" type="hidden" value="{$fld_secondvalue}">
						{assign var=fld_style value='class="detailedViewTextBox" readonly'}
						{if $fld_displaysecondvalue|trim eq ''}
							{assign var=fld_displaysecondvalue value='LBL_SEARCH_STRING'|getTranslatedString}
							{assign var=fld_style value='class="detailedViewTextBox"'}
						{/if}
						<input id="{$fldgroupname}_display" name="{$fldgroupname}_display" type="text" value="{$fld_displaysecondvalue}" {$fld_style} autocomplete="off">  {* crmv@113776 *}
						<script type="text/javascript">
						initAutocompleteUG('Groups','{$fldgroupname}','{$fldgroupname}_display','{$JSON->encode($secondvalue)|addslashes}');	{* crmv@31950 *}
						</script>
						<div class="dvtCellInfoImgRx">
							<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_SELECT}" onclick='toggleAutocompleteList("{$fldgroupname}_display");'>view_list</i>
							{* crmv@37211 *}
							{if $fromlink eq 'qcreate'}
								<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_CLEAR}" onClick="document.QcEditView.{$fldgroupname}.value=''; document.QcEditView.{$fldgroupname}_display.value=''; enableReferenceField(document.QcEditView.{$fldgroupname}_display); return false;">highlight_off</i>
							{else}
								<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_CLEAR}" onClick="document.EditView.{$fldgroupname}.value=''; document.EditView.{$fldgroupname}_display.value=''; enableReferenceField(document.EditView.{$fldgroupname}_display); return false;">highlight_off</i>
							{/if}
							{* crmv@37211e *}
						</div>
					</div>
				{/if}
				{* crmv@92272 *}
				{if $thirdvalue.enable_editoptions}
					<div class="{$DIVCLASS}" id="{$assign_other_div}" style="position:relative; {$style_other}">
						<input type="text" id="{$fldothername}" name="{$fldothername}" class="detailedViewTextBox" style="display:{$thirdvalue.editoptions_div_display}" value="{$thirdvalue.other_value}">
						<i style="display:{$thirdvalue.advanced_field_assignment_display}" id="advanced_field_assignment_button_{$fldname}" class="vteicon md-link" title="{'LBL_PM_ADVANCED_FIELD_ASSIGNMENT'|getTranslatedString:'Settings'}" onClick="ActionTaskScript.openAdvancedFieldAssignment('{$smarty.request.id}','{$smarty.request.elementid}','{$smarty.request.action_id}','{$fldname}','{$MODULE}','popup',true)" storage="db">more_horiz</i>
					</div>
				{/if}
				{* crmv@92272e *}
			</td>
		</tr>
		{* crmv@113527 *}
		{if $thirdvalue.enable_editoptions}
			{assign var="sdk_params_field" value='sdk_params_'|cat:$fldname}
			<tr id="container_{$sdk_params_field}" style="display: none">
				{if $OLD_STYLE eq true}<td></td>{/if}
				<td class="dvtCellLabel">{'LBL_PM_SDK_PARAMS'|getTranslatedString:'Settings'}</td>
				<td colspan="3">
					<div class="editoptions" fieldname="{$sdk_params_field}" style="float:right;"></div>
					<div class="dvtCellInfo">
						<input type="input" name="{$sdk_params_field}" id="{$sdk_params_field}" value="{$smarty.request.$sdk_params_field}" class="detailedViewTextBox">
					</div>
				</td>
			</tr>
		{/if}
		{* crmv@113527e *}
	</table>
{* crmv@31171e crmv@106856e *}
{elseif $uitype eq 52 || $uitype eq 77 || $uitype eq 54}	{* crmv@101683 *}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT EDITOPTIONSFIELDNAME="other_"|cat:$fldname EDITOPTIONSTYPE="smownerfieldnames" EDITOPTIONSDISPLAY=$secondvalue.editoptions_div_display}
	{if $secondvalue.enable_editoptions eq 'yes'}
		<table cellspacing="0" width="100%">
		<tr>
			<td width="20%" class="{$DIVCLASS}">
			<select id="{$fldname}_type" class="detailedViewTextBox" name="{$fldname}_type" onChange="changePicklistType(this,'{$fldname}');">
				{foreach item=arr from=$secondvalue.type_options}
					<option value="{$arr.0}" {$arr.2}>{$arr.1}</option>
				{/foreach}
			</select>
			</td>
			<td width="80%">
	{/if}
	<div class="{$DIVCLASS}" {if $secondvalue.picklist_display neq ''}style="display:{$secondvalue.picklist_display}"{/if}>
		{if $uitype eq 77}
			<select id="assigned_user_id1" name="assigned_user_id1" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{else}
			<select id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{/if}
		{foreach key=key_one item=arr from=$fldvalue}
			{foreach key=sel_value item=value from=$arr}
				<option value="{$key_one}" {$value}>{$sel_value}</option>
			{/foreach}
		{/foreach}
		</select>
	</div>
	{if $secondvalue.enable_editoptions eq 'yes'}
		<div class="{$DIVCLASS}" id="div_other_{$fldname}" style="display:{$secondvalue.editoptions_div_display}">
			<input type="text" id="other_{$fldname}" name="other_{$fldname}" class="detailedViewTextBox" value="{$secondvalue.other_value}"/>
		</div>
		<i style="display:{$secondvalue.advanced_field_assignment_display}" id="advanced_field_assignment_button_{$fldname}" class="vteicon md-link" title="{'LBL_PM_ADVANCED_FIELD_ASSIGNMENT'|getTranslatedString:'Settings'}" onClick="ActionTaskScript.openAdvancedFieldAssignment('{$smarty.request.id}','{$smarty.request.elementid}','{$smarty.request.action_id}','{$fldname}','{$MODULE}','popup',true)" storage="db">more_horiz</i> {* crmv@106856 *}
		</td>
		</tr>
		</table>
	{/if}
{elseif $uitype eq 17}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		http://
		<input style="width:74%;" class="detailedViewTextBox" type="text" tabindex="{$vt_tab}" id="{$fldname}" name="{$fldname}" style="border:1px solid #bababa;" size="27" onkeyup="validateUrl('{$fldname}');" value="{$fldvalue}">
	</div>
{elseif $uitype eq 85}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<img src="{'skype.gif'|resourcever}" alt="Skype" title="Skype" LANGUAGE=javascript align="absmiddle"></img><input class="detailedViewTextBox" type="text" tabindex="{$vt_tab}" name="{$fldname}" style="border:1px solid #bababa;" size="27" value="{$fldvalue}">
	</div>
{elseif $uitype eq 71 || $uitype eq 72}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{if $fldname eq "unit_price" && $fromlink neq 'qcreate'}
			<span id="multiple_currencies">
				<input name="{$fldname}" id="{$fldname}" tabindex="{$vt_tab}" type="text" class="detailedViewTextBox" onBlur="updateUnitPrice('unit_price','{$BASE_CURRENCY}');" value="{$fldvalue}" style="width:60%;">
				{if $MASS_EDIT neq 1}
					<div style="float:right">
						<a href="javascript:void(0);" onclick="updateUnitPrice('unit_price', '{$BASE_CURRENCY}'); toggleShowHide('currency_class','multiple_currencies');">{$APP.LBL_MORE_CURRENCIES} &raquo;</a>
					</div>
				{/if}
			</span>
			{if $MASS_EDIT neq 1}
				<div id="currency_class" class="crmvDiv" style="display: none; position: absolute; padding:5px; z-index: 1000000002;">
					<div class="closebutton" onclick="toggleShowHide('multiple_currencies','currency_class');"></div>
					<input type="hidden" name="base_currency" id="base_currency" value="{$BASE_CURRENCY}" />
					<input type="hidden" name="base_conversion_rate" id="base_currency" value="{$BASE_CURRENCY}" />
					<table width="100%" height="100%" class="small" cellpadding="5">
					<tr class="detailedViewHeader">
						<th>{$APP.LBL_CURRENCY}</th>
						<th>{$APP.LBL_PRICE}</th>
						<th>{$APP.LBL_CONVERSION_RATE}</th>
						<th>{$APP.LBL_RESET_PRICE}</th>
						<th>{$APP.LBL_BASE_CURRENCY}</th>
					</tr>
					{foreach item=price key=count from=$PRICE_DETAILS}
						<tr>
							{if $price.check_value eq 1 || $price.is_basecurrency eq 1}
								{assign var=check_value value="checked"}
								{assign var=disable_value value=""}
							{else}
								{assign var=check_value value=""}
								{assign var=disable_value value="disabled=true"}
							{/if}
	
							{if $price.is_basecurrency eq 1}
								{assign var=base_cur_check value="checked"}
							{else}
								{assign var=base_cur_check value=""}
							{/if}
	
							{if $price.curname eq $BASE_CURRENCY}
								{assign var=call_js_update_func value="updateUnitPrice('$BASE_CURRENCY', 'unit_price');"}
							{else}
								{assign var=call_js_update_func value=""}
							{/if}
	
							<td align="right" class="dvtCellLabel">
								{$price.currencylabel|@getTranslatedCurrencyString} ({$price.currencysymbol})
								<input type="checkbox" name="cur_{$price.curid}_check" id="cur_{$price.curid}_check" class="small" onclick="fnenableDisable(this,'{$price.curid}'); updateCurrencyValue(this,'{$price.curname}','{$BASE_CURRENCY}','{$price.conversionrate}');" {$check_value}>
							</td>
							<td class="{$DIVCLASS}" align="left">
								<input {$disable_value} type="text" size="10" class="small" name="{$price.curname}" id="{$price.curname}" value="{$price.curvalue_display}" onBlur="{$call_js_update_func} fnpriceValidation('{$price.curname}');"> {* crmv@98748 *}
							</td>
							<td class="{$DIVCLASS}" align="left">
								<input disabled=true type="text" size="10" class="small" name="cur_conv_rate{$price.curid}" value="{$price.conversionrate}">
							</td>
							<td class="{$DIVCLASS}" align="center">
								<input {$disable_value} type="button" class="crmbutton small edit" id="cur_reset{$price.curid}"  onclick="updateCurrencyValue(this,'{$price.curname}','{$BASE_CURRENCY}','{$price.conversionrate}');" value="{$APP.LBL_RESET}"/>
							</td>
							<td class="{$DIVCLASS}">
								<input {$disable_value} type="radio" class="detailedViewTextBox" id="base_currency{$price.curid}" name="base_currency_input" value="{$price.curname}" {$base_cur_check} onchange="updateBaseCurrencyValue()" />
							</td>
						</tr>
					{/foreach}
					</table>
				</div>
			{/if}
		{else}
			<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="text" class="detailedViewTextBox" value="{$fldvalue}">
		{/if}
	</div>
{elseif $uitype eq 56}
	{if $secondvalue eq 'picklist'}
		{include file="EditViewUI.tpl" uitype=15 fldvalue=$thirdvalue secondvalue=","|explode:""} {* dirty fix *}
	{else}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		{* crmv@9010 *}	{* crmv@28327 *}
		{if $fldname eq 'use_ldap' && $MODULE eq 'Users' && $MODE eq 'create'}	{* crmv@42247 *}
		    <div class="{$DIVCLASS} checkbox">
				<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}">
			</div>
		{else}
			<div class="{$DIVCLASS} checkbox">
			{* crmv@177395 - reverted TT-@178347 *}
			<label>
			{* crmv@9010e *}	{* crmv@28327e *}
			{if $fldname eq 'notime' && $ACTIVITY_MODE eq 'Events'}
				{if $fldvalue eq 1}
					<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" onclick="toggleTime()" checked>
				{else}
					<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="checkbox" onclick="toggleTime()" >
				{/if}
			<!-- For Portal Information we need a hidden field existing_portal with the current portal value -->
			{elseif $fldname eq 'portal'}
				<input type="hidden" name="existing_portal" value="{$fldvalue}">
				<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" {if $fldvalue eq 1}checked{/if}>
			{* crmv@104562 *}
			{elseif $fldname eq 'auto_working_days'}
				{if $fldvalue eq 1}
					<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" onclick="toggleWorkingDays(this)" checked>
				{else}
					<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="checkbox" onclick="toggleWorkingDays(this)" >
				{/if}
				<script type="text/javascript">jQuery(document).ready(function(){ldelim}toggleWorkingDays(document.{if $fromlink eq 'qcreate'}QcEditView{else}EditView{/if}.{$fldname});{rdelim});</script>
			{* crmv@104562e *}	
			{else}
				{if $fldvalue eq 1}
					<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" checked>
				{elseif $fldname eq 'filestatus'&& $MODE eq 'create'}
					<input id="{$fldname}" name="{$fldname}" type="checkbox" tabindex="{$vt_tab}" checked>
				{else}
					<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="checkbox" {if (($PROD_MODE eq 'create' || $fromlink eq 'qcreate') && $fldname eq 'discontinued') ||($fldname|substr:0:3 neq 'cf_' && $PRICE_BOOK_MODE eq 'create')}checked{/if}>	{* crmv@26847 *}
				{/if}
				
			{/if}
			</label>
			<!-- crmv@9010 -->
			</div>
		{/if}
		<!-- crmv@9010e -->
	{/if}
{elseif $uitype eq 23 || $uitype eq 5 || $uitype eq 6}
	{* crmv@120769 *}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{foreach key=date_value item=time_value from=$fldvalue}
			{assign var=date_val value="$date_value"}
			{assign var=time_val value="$time_value"}
		{/foreach}
		{foreach key=date_format item=date_str from=$secondvalue}
			{assign var=dateFormat value="$date_format"}
			{assign var=dateStr value="$date_str"}
		{/foreach}
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
		{if $smarty.request.enable_editoptions eq 'yes'}
			<td width="50%">
				<div class="dvtCellInfo">
					<select class="detailedViewTextBox" name="{$fldname}_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'{$fldname}')">
						<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
						<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
						<option value="now">{'LBL_NOW'|getTranslatedString}</option>
					</select>
				</div>
			</td>
		{/if}
		<td>
		{* crmv@82419 crmv@100585 *}
			<input name="{$fldname}" class="detailedViewTextBox" tabindex="{$vt_tab}" id="jscal_field_{$fldname}" type="text" maxlength="10" value="{$date_val}" {if $fromlink eq 'qcreate' && $fldname eq 'date_start'}onchange="parent.calDuedatetimeQC(this.form,'date');"{/if}  {if $smarty.request.enable_editoptions eq 'yes'}style="display:none"{/if}>	{* //crmv@31315 *}
		</td>
		{* crmv@22583 *}
		<td style="padding-right:2px;">
			<i class="vteicon md-link" id="jscal_trigger_{$fldname}" {if $smarty.request.enable_editoptions eq 'yes'}style="display:none"{/if}>events</i>
		</td>
		{* crmv@82419 *}
		{* crmv@22583e *}
		<td nowrap>
		{if $uitype eq 6}
			{* crmv@181170 *}
			{getTimeCombo('am', 'start', date('H'), date('i'), '', '', true)}
			<input type="hidden" name="time_start" id="time_start" value="{date('H:i')}">
			{* crmv@181170e *}
		{/if}
		{if $uitype eq 6 && $ACTIVITY_MODE eq 'Events'}
			<input name="dateFormat" type="hidden" value="{$dateFormat}">
		{/if}
		{if $uitype eq 23 && $ACTIVITY_MODE eq 'Events'}
			{* crmv@181170 *}
			{getTimeCombo(null, 'end', date('H'), date('i'), '', '', true)}
			<input type="hidden" name="time_end" id="time_end" value="{date('H:i')}">
			{* crmv@181170e *}
		{/if}
		</td>
		</tr>
		<tr>
		<td colspan="3" {if $smarty.request.enable_editoptions eq 'yes'}style="display:none"{/if}>
		{if $uitype eq 5 || $uitype eq 23}
			<font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
		{else}
			<font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
		{/if}
		</td>
		</tr>
		{if $smarty.request.enable_editoptions eq 'yes'}
		<tr>
			<td colspan="4" nowrap>
				<div class="editoptions" fieldname="{$fldname}_opt_num" optionstype="fieldnames" style="float:right; display:none"></div> {* crmv@204994 *}
			</td>
		</tr>
		<tr>
			<td colspan="4" nowrap>
				<div id="{$fldname}_adv_options" style="display:none">
					<div style="float:left; width:10%; padding-right:10px">
						<select class="detailedViewTextBox" name="{$fldname}_opt_operator">
							<option value="add">+</option>
							<option value="sub">-</option>
						</select>
					</div>
					<div style="float:left; width:30%; padding-right:5px">
						<input type="text" class="detailedViewTextBox" name="{$fldname}_opt_num">
					</div>
					<div style="float:left; padding-right:10px">
						<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="{$fldname}_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('{$fldname}_opt_num')">more_horiz</i>
						{* <i class="vteicon md-link" title="{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}" id="{$fldname}_opt_num_editoptions_cancel" onclick="ActionTaskScript.toggleFieldEditOptions('{$fldname}_opt_num')" style="display:none">highlight_off</i> *}
					</div>
					<div style="float:left; width:30%; padding-right:10px">
						<select class="detailedViewTextBox" name="{$fldname}_opt_unit">
							<option value="day">{'lbl_days'|getTranslatedString:'ModComments'}</option>
							<option value="month">{'lbl_months'|getTranslatedString:'ModComments'}</option>
							<option value="year">{'lbl_years'|getTranslatedString:'ModComments'}</option>
						</select>
					</div>
				</div>
			</td>
		</tr>
		{/if}
		</table>
		<script type="text/javascript" id='massedit_calendar_{$fldname}'>
			{* crmv@82419 *}
			(function() {ldelim}
				setupDatePicker('jscal_field_{$fldname}', {ldelim}
					trigger: 'jscal_trigger_{$fldname}',
					date_format: "{$dateStr|strtoupper}",
					language: "{$APP.LBL_JSCALENDAR_LANG}",
				{rdelim});
			{rdelim})();
			{* crmv@82419e crmv@100585e *}
		</script>
	</div>
	{* crmv@120769e *}
{elseif $uitype eq 357}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="To"}
	<div class="{$DIVCLASS}">
		<input name="{$fldname}" type="hidden" value="{$secondvalue}">
		<textarea readonly name="parent_name" cols="70" rows="2">{$fldvalue}</textarea>&nbsp;
		<select name="parent_type" class="small">
			{foreach key=labelval item=selectval from=$fldlabel}
				<option value="{$labelval}" {$selectval}>{$labelval}</option>
			{/foreach}
		</select>
		&nbsp;
		{if $fromlink eq 'qcreate'}
			<img tabindex="{$vt_tab}" src="{'select.gif'|resourcever}" alt="{$APP.LBL_SELECT}" title="{$APP.LBL_SELECT}" LANGUAGE=javascript onclick='openPopup("index.php?module="+ document.QcEditView.parent_type.value +"&action=Popup&html=Popup_picker&form=HelpDeskEditView&fromlink={$fromlink}","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200");' align="absmiddle" style='cursor:hand;cursor:pointer'>&nbsp;<input type="image" src="{'clear_field.gif'|resourcever}" alt="{$APP.LBL_CLEAR}" title="{$APP.LBL_CLEAR}" LANGUAGE=javascript onClick="this.form.parent_id.value=''; this.form.parent_name.value=''; return false;" align="absmiddle" style='cursor:hand;cursor:pointer'>{* crmv@21048m *}
		{else}
			<img tabindex="{$vt_tab}" src="{'select.gif'|resourcever}" alt="{$APP.LBL_SELECT}" title="{$APP.LBL_SELECT}" LANGUAGE=javascript onclick='openPopup("index.php?module="+ document.EditView.parent_type.value +"&action=Popup&html=Popup_picker&form=HelpDeskEditView&fromlink={$fromlink}","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200");' align="absmiddle" style='cursor:hand;cursor:pointer'>&nbsp;<input type="image" src="{'clear_field.gif'|resourcever}" alt="{$APP.LBL_CLEAR}" title="{$APP.LBL_CLEAR}" LANGUAGE=javascript onClick="this.form.parent_id.value=''; this.form.parent_name.value=''; return false;" align="absmiddle" style='cursor:hand;cursor:pointer'>{* crmv@21048m *}
		{/if}
	</div>
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="Cc"}
	<div class="{$DIVCLASS}">
		<input name="ccmail" type="text" class="detailedViewTextBox" value="">
	</div>
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label="Bcc"}
	<div class="{$DIVCLASS}">
		<input name="bccmail" type="text" class="detailedViewTextBox" value="">
	</div>
{elseif $uitype eq 55 || $uitype eq 255}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS} field-horiz">
		{if $fldvalue neq ''}
			<select name="salutationtype" class="detailedViewTextBox field-horiz-input-prefix">
				{foreach item=arr from=$fldvalue}
					<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
				{/foreach}
			</select>
		{/if}
		<input type="text" id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox field-horiz-input" value= "{$secondvalue}" />
	</div>
{elseif $uitype eq 69}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{if $MODULE eq 'Products'}
			<input name="del_file_list" type="hidden" value="">
			<div id="files_list" style="padding: 5px; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; font-size: x-small">{$APP.Files_Maximum_6}
				<input id="my_file_element" type="file" name="file_1" tabindex="{$vt_tab}"  onchange="validateFilename(this)"/>
				<!--input type="hidden" name="file_1_hidden" value=""/-->
				{assign var=image_count value=0}
				{if $maindata[3].0 && isset($maindata[3].0.name) && $maindata[3].0.name neq '' && $DUPLICATE neq 'true'} {* crmv@65080 crmv@145085 *}
				   {foreach name=image_loop key=num item=image_details from=$maindata[3]}
					<div>
						<img src="{$image_details.path}{$image_details.name}" height="50">&nbsp;&nbsp;[{$image_details.orgname}]<input id="file_{$num}" value="Delete" type="button" class="crmbutton small delete" onclick='this.parentNode.parentNode.removeChild(this.parentNode);delRowEmt("{$image_details.orgname}")'>
					</div>
			   	   {assign var=image_count value=$smarty.foreach.image_loop.iteration}
			   	   {/foreach}
				{/if}
			</div>
			<script>
				{* Create an instance of the multiSelector class, pass it the output target and the max number of files *}
				var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 6 );
				multi_selector.count = {$image_count}
				{* Pass in the file element *}
				multi_selector.addElement( document.getElementById( 'my_file_element' ) );
			</script>
		{else}
			<input name="{$fldname}"  type="file" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" tabindex="{$vt_tab}" onchange="validateFilename(this);" /> {* crmv@167234 *}
			<input name="{$fldname}_hidden"  type="hidden" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" /> {* crmv@167234 *}
			<input type="hidden" name="id" value=""/>
			{if isset($maindata[3].0.name) && $maindata[3].0.name != "" && $DUPLICATE neq 'true'} {* crmv@105683 *}
				<div id="replaceimage">[{$maindata[3].0.orgname}] <a href="javascript:;" onClick="delimage({$ID})">Del</a></div>
			{/if}
		{/if}
	</div>
{elseif $uitype eq 61}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input name="{$fldname}" type="file" value="{$secondvalue}" tabindex="{$vt_tab}" onchange="validateFilename(this)"/>
		<input type="hidden" name="{$fldname}_hidden" value="{$secondvalue}"/>
		<input type="hidden" name="id" value=""/>{$fldvalue}
	</div>
{elseif $uitype eq 156}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS} checkbox">
		<label>
		{if $fldvalue eq 'on'}
			{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
				<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="checkbox" checked>
			{else}
				<input name="{$fldname}" type="hidden" value="on">
				<input id="{$fldname}" name="{$fldname}" disabled tabindex="{$vt_tab}" type="checkbox" checked>
			{/if}
		{else}
			{if ($secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record) || ($MODE == 'create')}
				<input id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" type="checkbox">
			{else}
				<input id="{$fldname}" name="{$fldname}" disabled tabindex="{$vt_tab}" type="checkbox">
			{/if}
		{/if}
		</label>
	</div>
{elseif $uitype eq 98}<!-- Role Selection Popup -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}" style="position:relative">
		{if $thirdvalue eq 1}
			<input name="role_name" id="role_name" readonly class="detailedViewTextBox" tabindex="{$vt_tab}" value="{$secondvalue}" type="text">
			<div class="dvtCellInfoImgRx">
				<a href="javascript:open_Popup();"><i class="vteicon">view_list</i></a>{* crmv@21048m *}
			</div>
		{else}
			<input name="role_name" id="role_name" tabindex="{$vt_tab}" class="detailedViewTextBox" readonly value="{$secondvalue}" type="text">
		{/if}
		<input name="user_role" id="user_role" value="{$fldvalue}" type="hidden">
	</div>
{elseif $uitype eq 104}<!-- Mandatory Email Fields -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="text" name="{$fldname}" id ="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
	</div>
{elseif $uitype eq 115}<!-- for Status field Disabled for nonadmin -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{if $secondvalue eq 1 && $CURRENT_USERID != $smarty.request.record}
			<select id="user_status" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{else}
			<select id="user_status" disabled name="{$fldname}" class="detailedViewTextBox">
		{/if}
		{foreach item=arr from=$fldvalue}
			<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
		{/foreach}
		</select>
	</div>
{elseif $uitype eq 105}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{if $MODE eq 'edit' && $IMAGENAME neq ''}
			{* crmv@167234 *}
			<input name="{$fldname}" type="file" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" tabindex="{$vt_tab}" onchange="validateFilename(this);" /><div id="replaceimage">[{$IMAGENAME}]&nbsp;<a href="javascript:;" onClick="delUserImage({$ID})">Del</a></div>
			<br>{'LBL_IMG_FORMATS'|@getTranslatedString:$MODULE}
			<input name="{$fldname}_hidden"  type="hidden" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" />
		{else}
			<input name="{$fldname}" type="file" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" tabindex="{$vt_tab}" onchange="validateFilename(this);" /><br>{'LBL_IMG_FORMATS'|@getTranslatedString:$MODULE}
			<input name="{$fldname}_hidden"  type="hidden" value="{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if}" />
			{* crmv@167234e *}
		{/if}
			<input type="hidden" name="id" value=""/>
			{if isset($maindata[3].0.name)}{$maindata[3].0.name}{/if} {* crmv@79177 *}
	</div>
{elseif $uitype eq 103}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="text" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
	</div>
{elseif $uitype eq 116 || $uitype eq 117}<!-- for currency in users details-->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{if $secondvalue eq 1 || $uitype eq 117}
			<select name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{else}
			<select disabled name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		{/if}
		{foreach item=arr key=uivalueid from=$fldvalue}
			{foreach key=sel_value item=value from=$arr}
				<option value="{$uivalueid}" {$value}>{$sel_value|@getTranslatedCurrencyString}</option>
				<!-- code added to pass Currency field value, if Disabled for nonadmin -->
				{if $value eq 'selected' && $secondvalue neq 1}
					{assign var="curr_stat" value="$uivalueid"}
				{/if}
				<!--code ends -->
			{/foreach}
		{/foreach}
	   </select>
	<!-- code added to pass Currency field value, if Disabled for nonadmin -->
	{if $curr_stat neq '' && $uitype neq 117}
		<input name="{$fldname}" type="hidden" value="{$curr_stat}">
	{/if}
	<!--code ends -->
	</div>
{elseif $uitype eq 106}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	{if $MODE eq 'edit'}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff">
			<input type="text" readonly id="{$fldname}" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		</div>
	{else}
		<div class="{$DIVCLASS}">
			<input type="text" id="{$fldname}" name="{$fldname}" value="{$fldvalue}" tabindex="{$vt_tab}" class="detailedViewTextBox">
		</div>
	{/if}
{elseif $uitype eq 99}
	{if $MODE eq 'create'}
		{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
		<div class="{$DIVCLASS}">
			<input type="password" id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" class="detailedViewTextBox">
		</div>
	{/if}
{elseif $uitype eq 30}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		{assign var=check value=$secondvalue[0]}
		{assign var=yes_val value=$secondvalue[1]}
		{assign var=no_val value=$secondvalue[2]}

		<input type="radio" name="set_reminder" tabindex="{$vt_tab}" value="Yes" {$check}>&nbsp;{$yes_val}&nbsp;
		<input type="radio" name="set_reminder" value="No">&nbsp;{$no_val}&nbsp;

		{foreach item=val_arr from=$fldvalue}
			{assign var=start value=$val_arr[0]}
			{assign var=end value=$val_arr[1]}
			{assign var=sendname value=$val_arr[2]}
			{assign var=disp_text value=$val_arr[3]}
			{assign var=sel_val value=$val_arr[4]}
			<select name="{$sendname}" class="small">
				{section name=reminder start=$start max=$end loop=$end step=1 }
					{if $smarty.section.reminder.index eq $sel_val}
						{assign var=sel_value value="SELECTED"}
					{else}
						{assign var=sel_value value=""}
					{/if}
					<OPTION VALUE="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</OPTION>
				{/section}
			</select>
			&nbsp;{$disp_text}
		{/foreach}
	</div>
<!-- vtc -->
{elseif $uitype eq 26}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<select id="{$fldname}" name="{$fldname}" tabindex="{$vt_tab}" class="detailedViewTextBox">
			{foreach item=v key=k from=$fldvalue}
			<option value="{$k}">{$v}</option>
			{/foreach}
		</select>
	</div>
{elseif $uitype eq 27}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$maindata[1][3] massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<select class="detailedViewTextBox" id="{$fldname}" name="{$fldname}" onchange="changeDldType((this.value=='I' || this.value == 'B')? 'file': 'text');"> {* crmv@95157 *}
			{section name=combo loop=$fldlabel}
				<option value="{$fldlabel_combo[combo]}" {$fldlabel_sel[combo]}>{$fldlabel[combo]}</option>
			{/section}
		</select>
		<script>
			function vte_{$fldname}Init(){ldelim}
				var d = document.getElementsByName('{$fldname}')[0];
				var type = (d.value=='I' || d.value == 'B') ? 'file': 'text';	{* crmv@95157 *}
				changeDldType(type, true);
			{rdelim}
			if(typeof window.onload =='function'){ldelim}
				var oldOnLoad = window.onload;
				document.body.onload = function(){ldelim}
					vte_{$fldname}Init();
					oldOnLoad();
				{rdelim}
			{rdelim}else{ldelim}
				window.onload = function(){ldelim}
					vte_{$fldname}Init();
				{rdelim}
			{rdelim}
		</script>
	</div>
{elseif $uitype eq 28}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<script type="text/javascript">
			{* crmv@18625 *}
			{* function changeDldType(type, onInit){ldelim} *}
			changeDldType = function(type, onInit){ldelim}
			{* crmv@18625e *}
				var fieldname = '{$fldname}';
				if(!onInit){ldelim}
					var dh = getObj('{$fldname}_hidden');
					if(dh) dh.value = '';
				{rdelim}
		
				var v1 = document.getElementById(fieldname+'_E__');
				var v2 = document.getElementById(fieldname+'_I__');
		
				var text = v1.type =="text"? v1: v2;
				var file = v1.type =="file"? v1: v2;
				var filename = document.getElementById(fieldname+'_value');
				{literal}
				if(type == 'file'){
					// Avoid sending two form parameters with same key to server
					file.name = fieldname;
					text.name = '_' + fieldname;
		
					file.style.display = '';
					text.style.display = 'none';
					text.value = '';
					filename.style.display = '';

					// crmv@95157 - display backend name (also if field is readonly)
					var $backend = jQuery('select[name=backend_name]');
					if ($backend.length == 0) $backend = jQuery('input[name=backend_name]');
					$backend.closest('td').show();
					// crmv@95157e
				}else{
					// Avoid sending two form parameters with same key to server
					text.name = fieldname;
					file.name = '_' + fieldname;
		
					file.style.display = 'none';
					text.style.display = '';
					file.value = '';
					filename.style.display = 'none';
					filename.innerHTML="";

					// crmv@95157 - hide backend name
					var $backend = jQuery('select[name=backend_name]');
					if ($backend.length == 0) $backend = jQuery('input[name=backend_name]');
					$backend.closest('td').hide();
					// crmv@95157e
				}
				{/literal}
			{rdelim}
		</script>
		<input name="{$fldname}" id="{$fldname}_I__" type="file" value="{$secondvalue}" tabindex="{$vt_tab}" onchange="validateFilename(this)" />
		<input type="hidden" name="{$fldname}_hidden" value="{$secondvalue}"/>
		<input type="hidden" name="id" value=""/>
		<input type="text" id="{$fldname}_E__" name="{$fldname}" class="detailedViewTextBox" value="{$secondvalue}" style="display: none;"/>
		<span id="{$fldname}_value">
			{* crmv@104365 *}
			{if $thirdvalue neq ''}
				[{$thirdvalue}]
			{/if}
			{* crmv@104365e *}
		</span>
	</div>
<!-- vtc-e -->
{elseif $uitype eq 83} <!-- Handle the Tax in Inventory -->
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<table border="0" cellspacing="2" cellpadding="5" width="100%" class="small">
		{foreach item=tax key=count from=$TAX_DETAILS}
			{if $tax.check_value eq 1}
				{assign var=check_value value="checked"}
				{assign var=show_value value="visible"}
			{else}
				{assign var=check_value value=""}
				{assign var=show_value value="hidden"}
			{/if}
			<tr style="height:25px">
				<td width="20%">
					<input type="checkbox" name="{$tax.check_name}" id="{$tax.check_name}" class="small" onclick="fnshowHide(this,'{$tax.taxname}')" {$check_value}>
					<label for="{$tax.check_name}">{$tax.taxlabel} {$APP.COVERED_PERCENTAGE}</label>
				</td>
				<td class="{$DIVCLASS}" width="80%">
					<input type="text" class="detailedViewTextBox" name="{$tax.taxname}" id="{$tax.taxname}" value="{$tax.percentage_fmt}" style="visibility:{$show_value};" onBlur="fntaxValidation('{$tax.taxname}')"> {* crmv@118512 *}
				</td>
			</tr>
		{/foreach}
	</table>
{* crmv@16265 crmv@43764 *}
{elseif $uitype eq 199}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="password" tabindex="{$vt_tab}" id="{$fldname}_display" value="{$fldvalue}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('{$fldname}').value=this.value;">
		<input type="hidden" name="{$fldname}" id="{$fldname}" value="" class="detailedViewTextBox">
	</div>
{* crmv@16265e crmv@43764e *}
{* crmv@18338 *}
{elseif $uitype eq 1020}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="input" tabindex="{$vt_tab}" name="{$fldname}" id="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">
	</div>
{elseif $uitype eq 1021}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<input type="input" tabindex="{$vt_tab}" name="{$fldname}" id="{$fldname}" value="{$fldvalue}" class="detailedViewTextBox">
	</div>
{* crmv@18338 end *}
{* crmv@146461 *}
{elseif $uitype eq 70}
	{include file="FieldHeader.tpl" uitype=$uitype mandatory=$keymandatory label=$fldlabel massedit=$MASS_EDIT}
	<div class="{$DIVCLASS}">
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
		<td>
			<input name="{$fldname}" class="detailedViewTextBox" tabindex="{$vt_tab}" id="jscal_field_{$fldname}" type="text" maxlength="20" value="{$fldvalue}">
		</td>
		<td style="padding-right:2px;">
			<i class="vteicon md-link" id="jscal_trigger_{$fldname}">events</i>
		</td>
		</tr>
		<tr>
		<td colspan="2">
			<font size=1>({$secondvalue.date_format_string})</em></font>
		</td>
		</tr>
		</table>
		<script type="text/javascript" id='massedit_calendar_{$fldname}'>
			(function() {ldelim}
				setupDatePicker('jscal_field_{$fldname}', {ldelim}
					trigger: 'jscal_trigger_{$fldname}',
					date_format: "{$secondvalue.date_format}",
					language: "{$APP.LBL_JSCALENDAR_LANG}",
					time:true,
				{rdelim});
			{rdelim})();
		</script>
	</div>
{* crmv@146461e *}
{/if}