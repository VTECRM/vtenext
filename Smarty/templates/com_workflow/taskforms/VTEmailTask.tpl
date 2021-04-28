{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
var moduleName = '{$entityName}';
</script>
<script src="modules/com_workflow/resources/emailtaskscript.js" type="text/javascript" charset="utf-8"></script>

<table border="0" cellpadding="2" cellspacing="0" width="100%" class="small" style="padding-top:5px">
	<tr>
		<td width=15%></td>
		<td>
			<span class="helpmessagebox" style="font-style: italic;">{$MOD.LBL_WORKFLOW_NOTE_CRON_CONFIG}</span>
		</td>
	</tr>
{*//crmv@36510*}
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='From'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="sender" value="{$task->sender}" id="save_sender" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="float:left">
				<span id="task-emailfields_sender-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-emailfields_sender" class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
	</tr>
{*//crmv@36510 e*}	
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='To'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="recepient" value="{$task->recepient}" id="save_recepient" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="float:left">
				<span id="task-emailfields-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-emailfields" class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" label='Cc'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="emailcc" value="{$task->emailcc}" id="save_emailcc" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="float:left">
				<span id="task-emailfieldscc-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-emailfieldscc" class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" label='Bcc'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="emailbcc" value="{$task->emailbcc}" id="save_emailbcc" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="float:left">
				<span id="task-emailfieldsbcc-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-emailfieldsbcc" class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label='Subject'|@getTranslatedString:'Messages'}
		</td>
		<td>
			<div class="dvtCellInfo" style="float:left">
				<input type="text" name="subject" value="{$task->subject}" id="save_subject" class="detailedViewTextBox" style='width: 350px;'>
			</div>
			<div class="dvtCellInfo" style="float:left">
				<span id="task-subjectfields-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id="task-subjectfields" class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
	</tr>
</table>

<table border="0" cellpadding="5" cellspacing="0" width="100%" class="small" style='padding-top: 10px;'>
	<tr>
		<td><b>{'Body'|@getTranslatedString:'Messages'}</b></td>
	</tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" width="100%" class="small">
	<tr>
		<td>
			<div class="dvtCellInfo">
				<span id="task-fieldnames-busyicon"><b>{$MOD.LBL_LOADING}</b>{include file="LoadingIndicator.tpl"}</span>
				<select id='task-fieldnames' class="detailedViewTextBox" style="display: none;"><option value=''>{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option></select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select class="detailedViewTextBox" id="task_timefields">
					<option value="">{$MOD.LBL_SELECT_OPTION_DOTDOTDOT}</option>
					{foreach key=META_LABEL item=META_VALUE from=$META_VARIABLES}
						<option value="{$META_VALUE}">{$META_LABEL|@getTranslatedString:$MODULE_NAME}</option>
					{/foreach}
				</select>
			</div>
		</td>
	</tr>
</table>	

<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>

<div style="padding-top:5px">
	<textarea  style="width:90%;height:200px;" name="content" rows="55" cols="40" id="save_content" class="detailedViewTextBox"> {$task->content} </textarea>
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