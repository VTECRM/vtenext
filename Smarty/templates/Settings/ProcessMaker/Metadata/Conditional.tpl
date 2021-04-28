{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@112297 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}

<script src="modules/com_workflow/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="include/js/GroupConditions.js" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ActionTaskScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ConditionTaskScript.js"|resourcever}" type="text/javascript"></script>
<script type="text/javascript">
jQuery(document).ready(function(){ldelim}
	ConditionTaskScript.init('{$PROCESSID}','{$ID}',{literal}{'otherParams':{'dynaFormConditional':true}}{/literal});
	{literal}
	jQuery('[name="moduleName"]').change(function(){
		ProcessMakerScript.load_field_permissions_table(jQuery(this).val());
	});
	{/literal}
{rdelim});

function toggleValue(field){ldelim}
	jQuery('[id="FpovValueOpt'+field+'"]').toggle();
	jQuery('[name="FpovValueStr'+field+'"]').toggle();
{rdelim}
</script>

<div style="padding:5px;">
	<form action="index.php" id="ConditionalForm" shape-id="{$ID}">
		<input type="hidden" name="module" value="Settings">
		<input type="hidden" name="action" value="SettingsAjax">
		<input type="hidden" name="file" value="ProcessMaker">
		<input type="hidden" name="mode" value="{$SAVE_MODE}">
		<input type="hidden" name="processmakerid" id="processmakerid" value="{$PROCESSID}">
		<input type="hidden" name="elementid" id="elementid" value="{$ID}">
		<input type="hidden" name="ruleid" id="ruleid" value="{$RULEID}">
		<div id="conditions" style="display:none;">{$CONDITIONS}</div>
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
					{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_ENTITY}
				</td>
				<td align="left">
					<div class="dvtCellInfo" style="float:left">
						<select name="moduleName" class="dvtCellInfo detailedViewTextBox">
							{foreach key=k item=i from=$moduleNames}
								<option value="{$k}" {$i.1}>{$i.0}</option>
							{/foreach}
						</select>
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
		<div id='field_permissions_table' name='field_permissions_table' style="display:{$FIELD_PERMISSIONS_DISPLAY};"> 
			{include file="Settings/ProcessMaker/Metadata/ConditionalFieldTable.tpl"}
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