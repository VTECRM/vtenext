{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@126696 *}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

{include file='modules/SDK/src/Reference/Autocomplete.tpl'}

<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>

{literal}
<style type="text/css">
	/* crmv@112299 */
	.populateField, .populateFieldGroup {
		font-size:13px;
		min-width: 400px;
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

<table border="0" cellpadding="2" cellspacing="0" width="100%" class="small" style="padding-top:5px">
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='SINGLE_Campaigns'|getTranslatedString}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<select name="campaign_type" id="campaign_type" class="detailedViewTextBox" style="width:350px" onchange="ActionNewsletterScript.changeCampaignType()">
					{foreach key="CTYPE" item="CLABEL" from=$CAMPAIGN_TYPES}
						<option value="{$CTYPE}" {if $METADATA.campaign_type eq $CTYPE}selected=""{/if}>{$CLABEL}</option>
					{/foreach}
				</select>
				{* TODO: select campaign *}
			</div>
			<div class="dvtCellInfo" id="campaign_proc_cont" style="margin-left:5px; float:left;min-width:350px;{if $METADATA.campaign_type neq 'process'}display:none;{/if}">
				<select id="campaign_proc_record" name="campaign_proc_record" class="detailedViewTextBox">
					{foreach key=k item=i from=$INVOLVED_CAMPAIGNS}
						<option value="{$k}" {$i.1}>{$i.0}</option>
					{/foreach}
				</select>
			</div>
			<div class="dvtCellInfo" id="campaign_id_cont" style="margin-left:5px; float:left;min-width:350px;{if $METADATA.campaign_type neq 'existing'}display:none;{/if}">
				{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" MODULE="Newsletter" NOLABEL=true editViewType="actionform"
					uitype=$CAMPAIGN_FIELD[0][0]
					fldlabel=$CAMPAIGN_FIELD[1][0]
					fldlabel_sel=$CAMPAIGN_FIELD[1][1]
					fldlabel_combo=$CAMPAIGN_FIELD[1][2]
					fldname=$CAMPAIGN_FIELD[2][0]
					fldvalue=$CAMPAIGN_FIELD[3][0]
					secondvalue=$CAMPAIGN_FIELD[3][1]
					thirdvalue=$CAMPAIGN_FIELD[3][2]
					readonly=$CAMPAIGN_FIELD[4]
					typeofdata=$CAMPAIGN_FIELD[5]
					isadmin=$CAMPAIGN_FIELD[6]
					keyfldid=$CAMPAIGN_FIELD[7]
					keymandatory=false
					extra_popup_params="&override_fn=(parent.ActionNewsletterScript || window.ActionNewsletterScript).setReturnCampaign"
				}
				{* ehehe... visto che astuzia qui sopra? *}
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='From Name'|@getTranslatedString:'Newsletter'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="sendername" value="{$METADATA.sendername}" id="save_sendername" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="margin-left:5px; float:left">
				<span id="task-emailfields_sendername-busyicon"><b>{'LBL_LOADING'|getTranslatedString:'com_workflow'}</b>{include file="LoadingIndicator.tpl"}</span>
				<select class="detailedViewTextBox notdropdown populateFieldGroup" style="display:none"></select>
				<select id="task-emailfields_sendername" class="detailedViewTextBox notdropdown populateField" style="display: none;">
					<option value=''>{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
					<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
					{$SDK_CUSTOM_FUNCTIONS_CONTENT}
				</select>
			</div>
			{* crmv@106857 *}
			{assign var="target_mode" value="overwrite_input"}
			{assign var="target" value="jQuery(jQuery('#save_sendername').get())"}
			{assign var="dropdownid" value="task-emailfields_sendername"}
			{assign var="fldname" value="sendername"}
			<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:left; display:none;">
				<select class="populateField" onchange="ActionNewsletterScript.changeTableFieldOpt('{$target_mode}',{$target},'{$fldname}','{$dropdownid}',this)">
					{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
				</select>
			</div>
			<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:left; display:none;">
			<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:left; display:none;" onclick="ActionNewsletterScript.insertTableFieldValue('{$target_mode}',{$target},'{$fldname}','{$dropdownid}','seq')">input</i>
			{* crmv@106857e *}
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='From Address'|@getTranslatedString:'Newsletter'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="sender" value="{$METADATA.sender}" id="save_sender" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="margin-left:5px; float:left">
				<span id="task-emailfields-busyicon"><b>{'LBL_LOADING'|getTranslatedString:'com_workflow'}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-emailfields" class="detailedViewTextBox notdropdown populateField" style="display: none;">
					<option value=''>{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
					{$SDK_CUSTOM_FUNCTIONS_CONTENT}
				</select>
			</div>
			{* crmv@106857 *}
			{assign var="target_mode" value="append_input_comma"}
			{assign var="target" value="jQuery(jQuery('#save_sender').get())"}
			{assign var="dropdownid" value="task-emailfields"}
			{assign var="fldname" value="sender"}
			<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:left; display:none;">
				<select class="populateField" onchange="ActionEmailScript.changeTableFieldOpt('{$target_mode}',{$target},'{$fldname}','{$dropdownid}',this)">
					{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
				</select>
			</div>
			<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:left; display:none;">
			<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:left; display:none;" onclick="ActionEmailScript.insertTableFieldValue('{$target_mode}',{$target},'{$fldname}','{$dropdownid}','seq')">input</i>
			{* crmv@106857e *}
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='Recipients'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" id="recipients_boxes" style="float:left;width:350px;height:100px">
			</div>
			<input type="hidden" name="recipients" id="save_recipients" value="{$METADATA.recipients}">
			{if $METADATA.recipients_boxes}
				<script>
				ActionNewsletterScript.loadRecipients('{$METADATA.recipients_boxes}');
				</script>
			{/if}
			<div class="dvtCellInfo" style="margin-left:5px;float:left">
				<span id="task-emailfields_recipients-busyicon"><b>{'LBL_LOADING'|getTranslatedString:'com_workflow'}</b>{include file="LoadingIndicator.tpl"}</span>
				<span id="recipients_selects" style="display:none">
					<span>{$MOD.LBL_SELECT_STATIC}:</span>
					<i class="vteicon valign-bottom md-link" onclick="ActionNewsletterScript.openSelectRecipients()">view_list</i><br>
					<span>{$APP.LBL_OR} {$MOD.LBL_SELECT_FROM_PROCESS|strtolower}:</span><br>
					<select id="recipient_proc_record" name="recipient_proc_record" class="detailedViewTextBox populateField" onchange="ActionNewsletterScript.addProcessRecipient()">
						{foreach key=k item=i from=$INVOLVED_RECIPIENTS}
							<option value="{$k}" {$i.1}>{$i.0}</option>
						{/foreach}
					</select>
				</span>
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='Subject'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="subject" value="{$METADATA.subject}" id="save_subject" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="margin-left:5px; float:left">
				<span id="task-subjectfields-busyicon"><b>{'LBL_LOADING'|getTranslatedString:'com_workflow'}</b>{include file="LoadingIndicator.tpl"}</span>
				<select class="detailedViewTextBox notdropdown populateFieldGroup" style="display:none"></select>
				<select id="task-subjectfields" class="detailedViewTextBox notdropdown populateField" style="display: none;">
					<option value=''>{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
					<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
					{$SDK_CUSTOM_FUNCTIONS_CONTENT}
				</select>
			</div>
			{* crmv@106857 *}
			{assign var="target_mode" value="append_input_space"}
			{assign var="target" value="jQuery(jQuery('#save_subject').get())"}
			{assign var="dropdownid" value="task-subjectfields"}
			{assign var="fldname" value="subject"}
			<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:left; display:none;">
				<select class="populateField" onchange="ActionNewsletterScript.changeTableFieldOpt('{$target_mode}',{$target},'{$fldname}','{$dropdownid}',this)">
					{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
				</select>
			</div>
			<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:left; display:none;">
			<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:left; display:none;" onclick="ActionNewsletterScript.insertTableFieldValue('{$target_mode}',{$target},'{$fldname}','{$dropdownid}','seq')">input</i>
			{* crmv@106857e *}
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='LBL_EMAIL_TEMPLATE'|@getTranslatedString:'Settings'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" value="{$METADATA.templatename}" id="templatename" class="detailedViewTextBox" style='width: 350px;' readonly="">
				<input type="hidden" name="templateid" value="{$METADATA.templateid}" id="save_template">
			</div>
			<div class="dvtCellInfo" style="margin-left:5px;float:left">
				<i class="vteicon md-link" onclick="ActionNewsletterScript.openSelectTemplate()">view_list</i>
			</div>
		</td>
	</tr>
</table>

<div style="padding: 5px;">
	<table border="0" cellpadding="5" cellspacing="0" width="100%" class="small">
		<tr>
			<td><b>{'Body'|@getTranslatedString:'Messages'}</b></td>
		</tr>
	</table>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" class="small">
		<tr>
			<td>
				<div class="dvtCellInfo">
					<span id="task-fieldnames-busyicon"><b>{'LBL_LOADING'|getTranslatedString:'com_workflow'}</b>{include file="LoadingIndicator.tpl"}</span>
					<select class="detailedViewTextBox notdropdown populateFieldGroup" style="display:none"></select>
					<select id='task-fieldnames' class="detailedViewTextBox notdropdown populateField" style="display: none;">
						<option value=''>{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
						<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
						{$SDK_CUSTOM_FUNCTIONS_CONTENT}
					</select>
				</div>
				{* crmv@106857 *}
				{assign var="target_mode" value="append_textarea"}
				{assign var="target" value="CKEDITOR.instances.save_content"}
				{assign var="dropdownid" value="task-fieldnames"}
				{assign var="fldname" value="content"}
				<div class="tablefields_options" id="tablefields_options_{$fldname}" style="float:left; display:none;">
					<select class="populateField" onchange="ActionNewsletterScript.changeTableFieldOpt('{$target_mode}',{$target},'{$fldname}','{$dropdownid}',this)">
						{include file="Settings/ProcessMaker/actions/TablefieldsOptions.tpl"}
					</select>
				</div>
				<input type="text" id="tablefields_seq_{$fldname}" size="2" style="padding-left:5px; float:left; display:none;">
				<i id="tablefields_seq_btn_{$fldname}" class="vteicon md-link" style="float:left; display:none;" onclick="ActionNewsletterScript.insertTableFieldValue('{$target_mode}',{$target},'{$fldname}','{$dropdownid}','seq')">input</i>
				{* crmv@106857e *}
			</td>
			<td>
				{* crmv@140599 *}
				<div class="dvtCellInfo" style="float:left; margin-left:5px;">
					<select class="detailedViewTextBox notdropdown populateField" id="task_timefields">
						<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
						{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
							<option value="${$META_VALUE}">{$META_LABEL|@getTranslatedString:$MODULE_NAME}</option>
						{/foreach}
					</select>
				</div>
				<div class="dvtCellInfo" style="float:left; display:none">
					<select class="detailedViewTextBox notdropdown populateField" id="task_timefields_metavars">
						{foreach key=k item=i from=$RECORDS_INVOLVED}
							<option value="{$k}" {$i.1}>{$i.0}</option>
						{/foreach}
					</select>
				</div>
				{* crmv@140599e *}
			</td>
		</tr>
	</table>
</div>	

<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>

<div style="padding-top:5px">
	<textarea style="width:90%;height:200px;" name="content" rows="55" cols="40" id="save_content" class="detailedViewTextBox"> {$METADATA.content} </textarea>
</div>

<script type="text/javascript" defer="1">
var current_language_arr = "{$AUTHENTICATED_USER_LANGUAGE}".split("_"); // crmv@181170
var curr_lang = current_language_arr[0];
{literal}
CKEDITOR.replace('save_content', {
	filebrowserBrowseUrl: 'include/ckeditor/filemanager/index.html',
	language : curr_lang
});	
{/literal}
</script>
<script type="text/javascript">
ActionNewsletterScript.loadForm('{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}','{$INVOLVED_RECORDS}','{$OTHER_OPTIONS}','{$ELEMENTS_ACTORS}','{$EXTWS_OPTIONS}');	{* crmv@106857 crmv@147433 *}
</script>