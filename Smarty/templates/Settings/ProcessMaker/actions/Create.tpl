{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@92272 crmv@112297 crmv@115268 *}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

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

{if $ACTIONTYPE eq 'Create' || $CYCLE_ACTION eq 'Create'}
<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label=$APP.LBL_MODULE}
		</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select name="form_module" class="detailedViewTextBox" onchange="AlertNotifications.alert(1, null, ActionCreateScript.loadForm, [this.value,'{$ID}','{$ELEMENTID}','Create','{$ACTIONID}'])">
					{foreach key=k item=i from=$MODULES}
						<option value="{$k}" {$i.1}>{$i.0}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
</table>
<br>
{/if}

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
{if $SKIP_EDITFORM neq '1'}
	<div id="editForm"></div>
{/if}
<script type="text/javascript">
{* crmv@140949 *}
{if $ACTIONTYPE eq 'Create' || $CYCLE_ACTION eq 'Create'}
	jQuery(document).ready(function() {ldelim}
		{if $SHOW_ACTION_CONDITIONS}
			jQuery.fancybox.showLoading();
			ActionConditionScript.init('{$ID}','{$ELEMENTID}','{$METAID}','{$CYCLE_FIELDNAME}',function(){ldelim}
			jQuery.fancybox.hideLoading();
		{/if}
		{if $ACTIONID neq ''}
			ActionCreateScript.loadForm('{$METADATA.form_module}','{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}');
		{/if}
		{if $SHOW_ACTION_CONDITIONS}
			{rdelim});
		{/if}
	{rdelim});
{/if}
{* crmv@140949e *}
</script>