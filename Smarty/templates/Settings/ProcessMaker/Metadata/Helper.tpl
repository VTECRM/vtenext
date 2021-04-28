{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@96450 crmv@109685 crmv@112297 *}

<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" type="text/javascript" src="include/js/vtlib.js"></script>	{* crmv@92272 *}
{literal}
<style type="text/css">
	/* crmv@112299 */
	.populateField, .populateFieldGroup {
		font-size:12px;
	}
	.populateFieldGroup option {
		font-weight:bold;
	}
	.populateFieldGroup option:nth-child(1) {
		font-weight:normal;
	}
	/* crmv@112299e */
</style>
{/literal}

{capture assign="SDK_CUSTOM_FUNCTIONS_CONTENT"}
	{if !empty($SDK_CUSTOM_FUNCTIONS)}
		{foreach key=SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL item=SDK_CUSTOM_FUNCTIONS_BLOCK from=$SDK_CUSTOM_FUNCTIONS}
		<optgroup label="{$SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL}">
			{foreach key=k item=i from=$SDK_CUSTOM_FUNCTIONS_BLOCK}
				<option value="{$k}">{$i}</option>
			{/foreach}
		</optgroup>
		{/foreach}
	{/if}
{/capture}

<script type="text/javascript">
	jQuery(document).ready(function(){ldelim}
		ProcessHelperScript.initPopulateField('{$PROCESSID}','{"'"|str_replace:"\'":$JSON_INVOLVED_RECORDS}','{"'"|str_replace:"\'":$JSON_DYNAFORM_OPTIONS}','{"'"|str_replace:"\'":$JSON_ELEMENTS_ACTORS}','{$JSON_HELPER_ARR}');	{* crmv@153321_5 *}
	{rdelim});
</script>
<select id='task-fieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@160843 *}
<select id='task-pickfieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option>
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@160843e *}
<select id='task-smownerfieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@160843 *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@160843 *}
<select id='task-referencefieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option>
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@160843e *}

<form name="EditView" class="form-helper-shape" shape-id="{$ID}" id="editForm">
	<input type="hidden" id="processid" value="{$PROCESSID}">
	<input type="hidden" id="elementid" value="{$ID}">
	<div style="padding:5px;">
		<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td class="dvInnerHeader" colspan="2">
					<input type="checkbox" class="small" id="active_process_helper" name="active" onchange="jQuery('.process_helper_content').toggle();" {if $HELPER.active eq 'on'}checked{/if} />
					<strong><label for="active_process_helper">{$MOD.LBL_PROCESS_HELPER}</label></strong>
				</td>
			</tr>
		</table>
		<table align="center" width="99%" border="0" cellspacing="0" cellpadding="5" class="process_helper_content" style="{if $HELPER.active neq 'on'}display:none{/if}">
			<tr style="height:25px" valign="top">
				<td width="50%">
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" MODULE="Processes"
						uitype=$PMH_ASSIGNEDTO[0][0]
						fldlabel=$PMH_ASSIGNEDTO[1][0]
						fldlabel_sel=$PMH_ASSIGNEDTO[1][1]
						fldlabel_combo=$PMH_ASSIGNEDTO[1][2]
						fldname=$PMH_ASSIGNEDTO[2][0]
						fldvalue=$PMH_ASSIGNEDTO[3][0]
						secondvalue=$PMH_ASSIGNEDTO[3][1]
						thirdvalue=$PMH_ASSIGNEDTO[3][2]
						readonly=$PMH_ASSIGNEDTO[4]
						typeofdata=$PMH_ASSIGNEDTO[5]
						isadmin=$PMH_ASSIGNEDTO[6]
						keyfldid=$PMH_ASSIGNEDTO[7]
						keymandatory=false
						fifthvalue=$PMH_OTHER_ASSIGNED_TO
					}
				</td>
				<td width="50%">
					{* crmv@160843 *}
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo"
						uitype=$PMH_RELATEDTO[0][0]
						fldlabel=$PMH_RELATEDTO[1][0]
						fldlabel_sel=$PMH_RELATEDTO[1][1]
						fldlabel_combo=$PMH_RELATEDTO[1][2]
						fldname=$PMH_RELATEDTO[2][0]
						fldvalue=$PMH_RELATEDTO[3][0]
						secondvalue=$PMH_RELATEDTO[3][1]
						thirdvalue=$PMH_RELATEDTO[3][2]
						readonly=$PMH_RELATEDTO[4]
						typeofdata=$PMH_RELATEDTO[5]
						isadmin=$PMH_RELATEDTO[6]
						keyfldid=$PMH_RELATEDTO[7]
						keymandatory=false
					}
					{* crmv@160843e *}
				</td>
			</tr>
			<tr style="height:25px" valign="top">
				{* crmv@103450 *}
				<td>
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo"
						uitype=$PMH_STATUS[0][0]
						fldlabel=$PMH_STATUS[1][0]
						fldlabel_sel=$PMH_STATUS[1][1]
						fldlabel_combo=$PMH_STATUS[1][2]
						fldname=$PMH_STATUS[2][0]
						fldvalue=$PMH_STATUS[3][0]
						secondvalue=$PMH_STATUS[3][1]
						thirdvalue=$PMH_STATUS[3][2]
						readonly=$PMH_STATUS[4]
						typeofdata=$PMH_STATUS[5]
						isadmin=$PMH_STATUS[6]
						keyfldid=$PMH_STATUS[7]
						keymandatory=false
					}
				</td>
				{* crmv@103450e *}
				<td>
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" MASS_EDIT=1 mass_edit_check=$HELPER.process_name_mass_edit_check uitype=1 fldlabel='Process Name'|getTranslatedString:'Processes' fldname="process_name" fldvalue=$HELPER.process_name}
				</td>
			</tr>
			<tr style="height:25px" valign="top">
				<td>
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" uitype=21 fldlabel='Requested action'|getTranslatedString:'Processes' fldname="description" fldvalue=$HELPER.description}
				</td>
				{* crmv@93990 *}
				<td>
					<table width="100%" border="0" cellspacing="0" cellpadding="5">
					<tr>
						{if $HELPER.related_to_popup eq 'on'}
							{assign var=fldvalue value=1}
						{else}
							{assign var=fldvalue value=0}
						{/if}
						<td align="right" width="30">{include file="EditViewUI.tpl" NOLABEL=true DIVCLASS="dvtCellInfo" uitype=56 fldlabel=$MOD.LBL_PMH_RELATED_TO_POPUP fldname="related_to_popup"}</td>
						<td>
							<div style="float:left; padding-top:3px">{include file="FieldHeader.tpl" fldname="related_to_popup" label=$MOD.LBL_PMH_RELATED_TO_POPUP}</div>
							<div class="dvtCellInfo relatedToPopupOptions" style="float:left; padding-left:10px; {if $HELPER.related_to_popup neq 'on'}display:none{/if}">
								<select id="related_to_popup_opt" name="related_to_popup_opt" class="detailedViewTextBox" style="display:none">	{* DISABLED FOR NOW *}
									<option value="everytime" {if $HELPER.related_to_popup_opt eq "everytime"}selected{/if}>{$MOD.LBL_PMH_RELATED_TO_POPUP_EVERYTIME}</option>
									<option value="once" {if $HELPER.related_to_popup_opt eq "once"}selected{/if}>{$MOD.LBL_PMH_RELATED_TO_POPUP_ONCE}</option>
								</select>
							</div>
						</td>
					</tr>
					</table>
				</td>
				{* crmv@93990e *}
			</tr>
		</table>
	</div>
</form>
{if empty($HELPER.assigned_user_id)}
	<script type="text/javascript">
		ProcessMakerScript.clearAssignedUserId('{$ID}');
	</script>
{/if}

<div class="process_helper_content" style="{if $HELPER.active neq 'on'}display:none{/if}">
	{* crmv@160837 some code removed *}
	<form id="module_maker_form" method="POST">
	{include file="Settings/ProcessMaker/Metadata/HelperFields.tpl"}
	</form>
</div>

{* crmv@93990 *}
<script type="text/javascript">
	jQuery('#related_to_popup').change(function() {ldelim}
	    if (jQuery(this).is(':checked')) jQuery('.relatedToPopupOptions').show(); else jQuery('.relatedToPopupOptions').hide();
	{rdelim});
</script>
{* crmv@93990e *}