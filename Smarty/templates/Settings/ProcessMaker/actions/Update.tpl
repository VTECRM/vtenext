{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@112297 *}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" type="text/javascript" src="include/js/vtlib.js"></script>	{* crmv@92272 *}
<script src="{"include/js/ListView.js"|resourcever}" type="text/javascript" charset="utf-8"></script>
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

<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_ENTITY}
		</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select name="record_involved" class="detailedViewTextBox" onchange="AlertNotifications.alert(1, null, ActionUpdateScript.loadForm, [this.value,'{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}'])">
					{foreach key=k item=i from=$RECORDS_INVOLVED}
						{* crmv@135190 *}
						{if isset($i.group)}
							<optgroup label="{$i.group}">
								{foreach key=kk item=ii from=$i.values}
									<option value="{$kk}" {$ii.1}>{$ii.0}</option>
								{/foreach}
							</optgroup>
						{else}
							<option value="{$k}" {$i.1}>{$i.0}</option>
						{/if}
						{* crmv@135190e *}
					{/foreach}
				</select>
			</div>
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
</table>
<br>
<select id='task-fieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
<select id='task-pickfieldnames' class="notdropdown" style="display:none;">
	{* crmv@160843 *}
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option>
	{* crmv@160843e *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
<select id='task-smownerfieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@160843 *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
<select id='task-referencefieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@160843 *}
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
<select id='task-booleanfieldnames' class="notdropdown" style="display:none;">
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@108227 *}
<select id='task-datefieldnames' class="notdropdown" style="display:none;">
	{$SDK_CUSTOM_FUNCTIONS_CONTENT}
</select>
{* crmv@108227e *}
<div id="editForm"></div>
<script type="text/javascript">
{if $ACTIONID neq ''}
	jQuery(document).ready(function() {ldelim}
		ActionUpdateScript.loadForm('{$METADATA.record_involved}','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}');
	{rdelim});
{/if}
</script>