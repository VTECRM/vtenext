{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@106856 *}
{include file="Settings/ProcessMaker/Metadata/Header.tpl"}

<script src="{"modules/Settings/ProcessMaker/resources/ActionTaskScript.js"|resourcever}" type="text/javascript"></script>
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

<div id="editForm" style="padding:5px;">
	<form name="EditView">
		<input type="hidden" name="conditions_count" value="{$RULES|@count}" />
		<table border="0" width="100%">
		{foreach key=KEY item=RULE from=$RULES}
			<tr valign="top">
				<td width="1%" nowrap>
					<a href="javascript:;" onClick="ActionTaskScript.editAdvancedFieldAssignment('{$PROCESSID}','{$ELEMENTID}','{$ACTIONID}','{$FIELDNAME}','{$FORM_MODULE}','{$KEY}')"><i class="vteicon" title="{$APP.LBL_EDIT}">create</i></a>
					<a href="javascript:;" onClick="ActionTaskScript.deleteAdvancedFieldAssignment('{$PROCESSID}','{$ELEMENTID}','{$ACTIONID}','{$FIELDNAME}','{$FORM_MODULE}','{$KEY}')"><i class="vteicon" title="{$APP.LBL_DELETE}">clear</i></a>
				</td>
				<td>
					{* crmv@160843 *}
					{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo"
						uitype=$RULE[0][0]
						fldlabel=$RULE[1][0]
						fldlabel_sel=$RULE[1][1]
						fldlabel_combo=$RULE[1][2]
						fldname=$RULE[2][0]
						fldvalue=$RULE[3][0]
						secondvalue=$RULE[3][1]
						thirdvalue=$RULE[3][2]
						readonly=$RULE[4]
						typeofdata=$RULE[5]
						isadmin=$RULE[6]
						keyfldid=$RULE[7]
						keymandatory=false
						
						fldgroupname="assigned_group_id"|cat:$KEY
						assigntypename="assigntype"|cat:$KEY
						assign_user_div="assign_user"|cat:$KEY
						assign_team_div="assign_team"|cat:$KEY
						assign_other_div="assign_other"|cat:$KEY
					}
					{* crmv@160843e *}
				</td>
			</tr>
		{/foreach}
		</table>
		<div style="height:5px;"></div>
		<div style="float:right">
			<input type="button" onclick="ActionTaskScript.openAdvancedFieldAssignmentCondition('{$PROCESSID}','{$ELEMENTID}','{$ACTIONID}','{$FIELDNAME}','{$FORM_MODULE}')" class="crmbutton small create" value="{$MOD.LBL_ADD_RULE}" title="{$MOD.LBL_SAVE_LABEL}">
		</div>
	</form>
</div>
{* crmv@160843 *}
<script type="text/javascript">
	jQuery('#editForm .editoptions[optionstype="smownerfieldnames"]').each(function(){ldelim}
		jQuery(this).html('<select class="populateFieldGroup" onfocus="parent.ActionTaskScript.populateSelectBox(this,\'smownerfieldnames\')"><option value="">'+alert_arr.LBL_SELECT_OPTION_DOTDOTDOT+'</option></select><select style="display:none" class="populateField" onchange="ActionUpdateScript.populateField(this)"></select>');	//crmv@112299 crmv@139690
		var fieldname = jQuery(this).attr('fieldname').replace('other_','');
		if (jQuery('#other_'+fieldname).length > 0) ActionTaskScript.showSdkParamsInput(jQuery('#other_'+fieldname),fieldname);	//crmv@113527
	{rdelim});
	filterPopulateField();
</script>
{* crmv@160843e *}