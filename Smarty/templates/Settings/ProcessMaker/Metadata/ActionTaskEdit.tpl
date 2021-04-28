{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@104180 crmv@115268 crmv@131239 *}
{include file="SmallHeader.tpl"}

<script src="{"modules/Settings/ProcessMaker/resources/ProcessMakerScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ActionTaskScript.js"|resourcever}" type="text/javascript"></script>
<script src="{"modules/Settings/ProcessMaker/resources/ConditionTaskScript.js"|resourcever}" type="text/javascript"></script>

{if $SHOW_ACTION_CONDITIONS}
<script src="modules/com_workflow/resources/functional.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/fieldvalidator.js" type="text/javascript" charset="utf-8"></script>
<script src="include/js/GroupConditions.js" type="text/javascript"></script>
{/if}

{include file='modules/SDK/src/Reference/Autocomplete.tpl'} {* crmv@195745 *}

<form id="actionform" 
	{* crmv@195745 *}
	{if $CYCLE_ACTION eq 'InsertTableRow' || $CYCLE_ACTION eq 'InsertProductRow' || $CYCLE_ACTION eq 'ModNotification' ||
	($ACTIONTYPE eq 'InsertTableRow' && $TABLETYPE eq 'Dynaform') || 
	$ACTIONTYPE eq 'ModNotification' || $ACTIONTYPE eq 'CreatePDF' || $ACTIONTYPE eq 'InsertProductRow' || $CYCLE_ACTION eq 'CreatePDF'} {* crmv@203075 *}
		name="EditView"
	{else}
		name="actionform"
	{/if} method="post" onsubmit="VteJS_DialogBox.block();"> {* crmv@126696 crmv@160843 crmv@183346 crmv@187729 *}
	{* crmv@195745e *}

	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="id" value="{$ID}">
	<input type="hidden" name="elementid" value="{$ELEMENTID}">
	<input type="hidden" name="metaid" value="{$METAID}">
	<input type="hidden" name="action_type" value="{$ACTIONTYPE}">
	<input type="hidden" name="cycle_action" value="{$CYCLE_ACTION}">
	<input type="hidden" name="cycle_field" value="{$CYCLE_FIELD}">
	<input type="hidden" id="cycle_fieldname" name="cycle_fieldname" value="{$CYCLE_FIELDNAME}"> {* crmv@203075 *}
	<input type="hidden" name="inserttablerow_field" value="{$INSERT_TABLEROW_FIELD}">
	<input type="hidden" name="insertpblockrow_field" value="{$INSERT_PBLOCKROW_FIELD}"> {* crmv@195745 *}
	<table border="0" cellpadding="2" cellspacing="0" width="100%">
		<tr>
			<td align=right width=15% nowrap="nowrap">
				{include file="FieldHeader.tpl" mandatory=true label="LBL_PM_ACTION_TITLE"|getTranslatedString:'Settings'}
			</td>
			<td align="left">
				<div class="dvtCellInfo">
					<input type="text" class="detailedViewTextBox" id="action_title" name="action_title" value="{$METADATA.action_title}">
				</div>
			</td>
			<td align=right width=15% nowrap="nowrap">&nbsp;</td>
		</tr>
		{if !empty($INSERT_TABLEROW_LABEL)}
			<tr>
				<td></td>
				<td>{$INSERT_TABLEROW_LABEL}</td>
				<td></td>
			</tr>
		{/if}
	</table>
	{if $SHOW_ACTION_CONDITIONS}
		<!-- Workflow Conditions -->
		<br>
		<div style="padding: 0px 13px">
			<div id="conditions" style="display:none;">{$ACTION_CONDITIONS}</div>
			<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
				<tr height="40">
					<td class="big detailedViewHeader" nowrap="nowrap">
						<strong>{$MOD.LBL_CONDITIONS}{$CYCLE_FIELDLABEL}</strong>
					</td>
					<td class="small detailedViewHeader" align="right">
						<span id="group_conditions_loading" style="display:none">{include file="LoadingIndicator.tpl"}</span>
						<input type="button" class="crmButton create small" value="{$MOD.LBL_NEW_GROUP}" id="group_conditions_add" style="display:none"/>
					</td>
				</tr>
			</table>
			<div id="save_conditions"></div>
			<div id="dump" style="display:none;"></div>
		</div>
		<hr>
	{/if}
	{include file="$TEMPLATE"}
</form>

{if $SHOW_ACTION_CONDITIONS && $CYCLE_ACTION neq 'Create' && $CYCLE_ACTION neq 'InsertTableRow'}	{* crmv@140949 do it already in the Create template *}
<script type="text/javascript">
jQuery(document).ready(function(){ldelim}
	ActionConditionScript.init('{$ID}','{$ELEMENTID}','{$METAID}','{$CYCLE_FIELDNAME}');
{rdelim});
</script>
{/if}