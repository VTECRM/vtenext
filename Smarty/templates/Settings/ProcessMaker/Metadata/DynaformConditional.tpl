{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@99316 crmv@106857 crmv@112297 crmv@115268 *}

{include file="Settings/ProcessMaker/Metadata/Header.tpl"}

{if !empty($ERROR)}
	<div style="width:100%; text-align:center; padding-top:10px">{$ERROR}</div>
{else}

<script src="modules/com_workflow/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="include/js/GroupConditions.js" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ActionTaskScript.js"|resourcever}" type="text/javascript"></script>
<script type="text/javascript">
jQuery(document).ready(function(){ldelim}
	{if $MMODE eq 'edit'}
		var conditions = JSON.parse('{$CONDITIONS}');
	{else}
		var conditions = null;
	{/if}
	{literal}
	GroupConditions.init(jQuery, 'DynaForm', 'save_conditions', null, conditions, {'otherParams':{'processmakerId':'{/literal}{$PROCESSID}{literal}','metaId':'{/literal}{$METAID}{literal}','dynaFormConditional':true}} );
	{/literal}
{rdelim});

function toggleValue(field){ldelim}
	jQuery('[id="FpovValueOpt'+field+'"]').toggle();
	jQuery('[name="FpovValueStr'+field+'"]').toggle();
{rdelim}
</script>

<div style="padding:5px;">
	<form action="index.php" id="DynaformConditionalForm">
		<input type="hidden" name="module" value="Settings">
		<input type="hidden" name="action" value="SettingsAjax">
		<input type="hidden" name="file" value="ProcessMaker">
		<input type="hidden" name="mode" value="save_dynaform_conditional">
		<input type="hidden" name="processmakerid" id="processmakerid" value="{$PROCESSID}">
		<input type="hidden" name="elementid" id="elementid" value="{$ID}">
		<input type="hidden" name="ruleid" id="ruleid" value="{$RULEID}">
		<input type="hidden" name="metaid" id="metaid" value="{$METAID}">
		<table border="0" cellpadding="2" cellspacing="0" width="100%">
			<tr>
				<td align=right width=15% nowrap="nowrap">
					{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_PM_RULE}
				</td>
				<td align="left">
					<div class="dvtCellInfo">
						<input type="text" class="detailedViewTextBox" id="title" name="title" value="{$TITLE}">
					</div>
				</td>
				<td align=right width=15% nowrap="nowrap">&nbsp;</td>
			</tr>
			<tr>
				<td align=right width=15% nowrap="nowrap">
					{include file="FieldHeader.tpl" mandatory=true label="LBL_FPOFV_CRITERIA_NAME"|getTranslatedString:'Conditionals'}
				</td>
				<td align="left">
					<div class="dvtCellInfo">
						<div class="dvtCellInfo">{$ROLE_GRP_CHECK_PICKLIST}</div>
					</div>
				</td>
				<td align=right width=15% nowrap="nowrap">&nbsp;</td>
			</tr>
		</table>
		<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr height="40">
				<td class="big" nowrap="nowrap">
					<strong>{$MOD.LBL_CONDITIONS}</strong>
				</td>
				<td class="small" align="right">
					<span id="group_conditions_loading" style="display:none">{include file="LoadingIndicator.tpl"}</span>
					<input type="button" class="crmButton create small" value="{$MOD.LBL_NEW_GROUP}" id="group_conditions_add" style="display:none"/>
				</td>
			</tr>
		</table>
		<div id="save_conditions"></div>
		<div id="dump" style="display:none;"></div>
		<br>
		<table width="100%" cellspacing="0" cellpadding="5" border="0" class="tableHeading">
			<tr class="tableHeading">
				<td nowrap="nowrap" class="big">
					<strong>{'LBL_FPOFV_ACTION_NAME'|getTranslatedString:'Conditionals'}</strong>
				</td>
		</table>
		<div id='field_permissions_table' name='field_permissions_table'> 
			<table align=center width=100% id='rule_table'> 
				<tr>
					<td class="colHeader"></td>
					<td class="colHeader small" align="center">
						{'LBL_FIELD_VALUE'|getTranslatedString:'com_workflow'}
					</td>
					<td class="colHeader small" align="center">
						{'LBL_FPOFV_MANAGE_PERMISSION'|getTranslatedString:'Conditionals'}<br>
						<input type="checkbox" name="FpovManaged" onClick="setAll(this.checked,'FpovManaged');" value="0" id="FpovManaged" >
					</td>
					<td class="colHeader small" align="center">
						{'LBL_FPOFV_READ_PERMISSION'|getTranslatedString:'Conditionals'}<br>
						<input type="checkbox" name="FpovReadPermission" onClick="setAll(this.checked,'FpovReadPermission');" value="0" id="FpovReadPermission" >
					</td>
					<td class="colHeader small" align="center">
						{'LBL_FPOFV_WRITE_PERMISSION'|getTranslatedString:'Conditionals'}<br>
						<input type="checkbox" name="FpovWritePermission" onClick="setAll(this.checked,'FpovWritePermission');" value="0" id="FpovWritePermission" >
					</td>
					<td class="colHeader small" align="center">
						{'LBL_FPOFV_MANDATORY_PERMISSION'|getTranslatedString:'Conditionals'}<br>
						<input type="checkbox" name="FpovMandatoryPermission" onClick="setAll(this.checked,'FpovMandatoryPermission');" value="0" id="FpovMandatoryPermission" >		
					</td>
				</tr>       
				{assign var=current_block_label value=""}           
				{foreach from=$FPOFV_PIECE_DATA item=field_piece_of_data key=index}
					<tr>
						{if $current_block_label neq $field_piece_of_data.FpofvBlockLabel}
							<tr>
								<td colspan=6 class="colHeader small">
									{$field_piece_of_data.FpofvBlockLabel}
								</td>
							</tr>       
							{assign var=current_block_label value=$field_piece_of_data.FpofvBlockLabel}			
						{/if}
						<td align=left class="listTableRow small">
	   						{$field_piece_of_data.TaskFieldLabel}
			   			</td>
						<td align=left class="listTableRow small">
							{if $field_piece_of_data.HideFpovValue eq true}
			   					{assign var="display" value="none"}
			   				{else}
			   					{assign var="display" value="block"}
			   				{/if}
							<div style="float:left; width:10%; display:{$display}"><input type="checkbox" name="FpovValueActive{$field_piece_of_data.TaskField}" value="1" onClick="toggleValue('{$field_piece_of_data.TaskField}')" {if $field_piece_of_data.FpovValueActive eq "1"}checked{/if}>&nbsp;</div>
	   						<div style="float:left; width:90%; display:{$display}">
	   							<select id="FpovValueOpt{$field_piece_of_data.TaskField}" style="{if $field_piece_of_data.FpovValueActive eq "1"}display:block;{else}display:none{/if}" onChange="ProcessMakerScript.populateField(this,'FpovValueStr{$field_piece_of_data.TaskField}')">
	   								<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
	   								{* <option value="current">{'LBL_FPOFV_CURRENT_VALUE'|getTranslatedString:'Settings'}</option> *}
									{if !empty($SDK_CUSTOM_FUNCTIONS)}
										{foreach key=SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL item=SDK_CUSTOM_FUNCTIONS_BLOCK from=$SDK_CUSTOM_FUNCTIONS}
										<optgroup label="{$SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL}">
											{foreach key=k item=i from=$SDK_CUSTOM_FUNCTIONS_BLOCK}
												<option value="{$k}">{$i}</option>
											{/foreach}
										</optgroup>
										{/foreach}
									{/if}
									{if !empty($FPOFV_VALUE_OPTIONS)}
										{foreach key=k item=i from=$FPOFV_VALUE_OPTIONS}
											<option value="{$k}">{$i}</option>
										{/foreach}
									{/if}
	   							</select>
	   						</div>
	   						<input type="text" class="detailedViewTextBox" id="FpovValueStr{$field_piece_of_data.TaskField}" name="FpovValueStr{$field_piece_of_data.TaskField}" value="{$field_piece_of_data.FpovValueStr}" style="{if $field_piece_of_data.FpovValueActive eq "1"}display:block;{else}display:none{/if}">
			   			</td>
			   			<td align=center class="listTableRow small" width=15%>
			   				{if $field_piece_of_data.HideFpovManaged eq true}
			   					{assign var="display" value="none"}
			   				{else}
			   					{assign var="display" value="block"}
			   				{/if}
			   				&nbsp;<input type="checkbox" name="FpovManaged{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovManaged{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovManaged eq "1"}checked{/if} style="display:{$display}">&nbsp;
			   			</td>
			   			<td align=center class="listTableRow small" width=15%>
			   				{if $field_piece_of_data.HideFpovReadPermission eq true}
			   					{assign var="display" value="none"}
			   				{else}
			   					{assign var="display" value="block"}
			   				{/if}
							&nbsp;<input type="checkbox" name="FpovReadPermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovReadPermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovReadPermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
						</td>
						<td align=center class="listTableRow small" width=15%>
							{if $field_piece_of_data.HideFpovWritePermission eq true}
			   					{assign var="display" value="none"}
			   				{else}
			   					{assign var="display" value="block"}
			   				{/if}
							&nbsp;<input type="checkbox" name="FpovWritePermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovWritePermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovWritePermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
						</td>
						<td align=center class="listTableRow small" width=15%>
							{if $field_piece_of_data.HideFpovMandatoryPermission eq true}
			   					{assign var="display" value="none"}
			   				{else}
			   					{assign var="display" value="block"}
			   				{/if}
							&nbsp;<input type="checkbox" name="FpovMandatoryPermission{$field_piece_of_data.TaskField}" onClick="toggle_permissions('{$field_piece_of_data.TaskField}');" value="1" id="FpovMandatoryPermission{$field_piece_of_data.TaskField}" {if $field_piece_of_data.FpovMandatoryPermission eq "1"}checked{/if} {if $field_piece_of_data.FpovManaged eq "1"}{else}disabled{/if} style="display:{$display}">&nbsp;
						</td>
					</tr>                   			
				{/foreach}
			</table>
		</div>
	</form>
</div>

{literal}
<script>
function toggle_permissions(taskfield) {
	var obj_ = document.getElementById("FpovManaged"+taskfield);
	var obj1 = document.getElementById("FpovReadPermission"+taskfield);
	var obj2 = document.getElementById("FpovWritePermission"+taskfield);
	var obj3 = document.getElementById("FpovMandatoryPermission"+taskfield);
	
	if(obj_.checked) {
		obj1.disabled = false;
		obj2.disabled = false;
		obj3.disabled = false;
	
		if(obj2.checked)
			obj1.checked = 1;	
		
		if(obj3.checked) {
			obj1.checked = 1;
			obj2.checked = 1;	
		}
	} else {
		obj1.disabled = true;
		obj2.disabled = true;
		obj3.disabled = true;			
	
		obj1.checked = false;
		obj2.checked = false;
		obj3.checked = false;
	}
}
function setAll(boolset,type) {
	var table = document.getElementById("rule_table"); 
	var checks = table.getElementsByTagName("input"); 
	
	for (var i = 0; i < checks.length; i++) {
		if(checks[i].id.indexOf(type)>-1) {
			var taskfield = checks[i].id.replace(type,"");
			checks[i].checked = boolset; 
			if(taskfield != "")
				toggle_permissions(taskfield);
		}
	}
}
</script>
{/literal}
{/if}