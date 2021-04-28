{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@181161 *}

<script type="text/javascript" src="{"modules/Update/Update.js"|resourcever}"></script>

{include file='Buttons_List1.tpl'} {* crmv@182073 *}

<form method="POST" name="ScheduleUpdate" action="index.php?module=Update&amp;action=ScheduleUpdate&amp;parenttab=Settings&amp;subaction=do_schedule" >
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<div class="container" style="width:80%">
	<h3>{$MOD.LBL_SCHEDULE_UPDATE}</h3>
	<br><br>
	
	<div class="row">
		<div class="col-xs-12 text-right">
			<button class="crmbutton cancel" type="button" onclick="location.href='index.php'">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			<button class="crmbutton save" type="submit" onclick="return VTE.Update.onScheduleUpdate()" id="submitBtn">{$MOD.LBL_SCHEDULE}</button> {* crmv@182073 *}
		</div>
	</div>
	
	{if $ERROR}
	<div class="row">
		<br>
		<div class="col-xs-12">
			<div class="alert alert-warning"><b>{$ERROR}</b></div>
		</div>
		<br>
	</div>
	{/if}
	
	{* crmv@182073 *}
	{if $SHOW_DIFF_ALERT}
	<br>
	<div class="row">
		<div class="alert" style="background-color:#ffbb45;color:white">
			<div class="dvtCellInfo checkbox">
				<label><input type="checkbox" name="alert_changes" id="alert_changes" {if $DIFF_ALERT}checked{/if} onchange="VTE.Update.onChangeDiffAlert(this)"> <b>{$MOD.LBL_ALERT_CHANGES}</b></label><br>
				<span style="margin-left:24px;text-decoration:underline"><b><a href="javascript:void(0);" onclick="VTE.Update.viewDiffFiles()">{$MOD.LBL_VIEW_FILES_LIST}</a></b></span>
			</div>
		</div>
	</div>
	{literal}
	<script>
		(function() {
			VTE.Update.onChangeDiffAlert(jQuery('#alert_changes')[0]);
		})();
	</script>
	{/literal}
	{/if}
	{* crmv@182073e *}
	
	<div id="allfields" {if $SHOW_DIFF_ALERT && !$DIFF_ALERT}style="display:none"{/if}> {* crmv@182073 *}
	
	<div class="row">
		<div class="col-xs-12">
			<p style="font-size:120%">{$MOD.LBL_WHEN_SCHEDULE_UPDATE}</p>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-3">
			{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" fldname="schedule_date" uitype=5 fldlabel=$APP.date dateStr=$DATE_FORMAT date_val=$DATE_VALUE}
		</div>
		<div class="col-xs-3">
			{include file="EditViewUI.tpl" DIVCLASS="dvtCellInfo" fldname="schedule_hour" uitype=73 fldlabel=$APP.LBL_HOUR fldvalue=$HOUR_VALUE}
		</div>
	</div>
	<br><hr>
	<div class="row">
		<div class="col-xs-12">
			<div class="dvtCellInfo checkbox">
				<label><input type="checkbox" id="schedule_alert" name="schedule_alert" {if $SCHEDULE_ALERT}checked{/if} onchange="VTE.Update.onchangeAlertUsers(this)"> {$MOD.LBL_ALERT_USER_OF_UPDATE}</label>
			</div>
		</div>
	</div>
	<div class="row" id="users_box" {if !$SCHEDULE_ALERT}style="display:none"{/if}>
		<div class="col-xs-10 col-xs-offset-1" >
			
			<table id="shareMembersTable" border="0" width="100%" align="center">

				<tr>
					<td></td>
					<td width="30%">
						<span><b>{"Entity Type"|getTranslatedString}</b></span><br>
						<select id="shareMemberType" class="detailedViewTextBox" onchange="VTE.Update.changeMemberType()">
							<option value="groups">{"LBL_GROUPS"|getTranslatedString:"Settings"}</option>
							<option value="users" selected>{"LBL_USERS"|getTranslatedString:"Settings"}</option>
						</select>
					</td>
					<td width="140"></td>
					<td width="30%">
						<b>{$MOD.LBL_MEMBERS}</b><br>
					</td>
					<td></td>
				</tr>

				<tr>
					<td></td>
					<td>
						<select id="availmembers" class="notdropdown" size="8" multiple="" style="min-width:340px">
						</select>
					</td>
					<td align="center" nowrap="" valign="center">
						<button name="add" class="crmbutton edit" type="button" onclick="VTE.Update.addMembers()">{$APP.LBL_ADD_ITEM} &gt;</button>
					</td>
					<td>
						<select id="sharedmembers" class="notdropdown" size="8" multiple="" style="min-width:340px">
						</select>
						<input type="hidden" id="schedule_users" name="schedule_users" value="{$SCHEDULE_USERS|@escape}"> {* crmv@183486 *}
					</td>
					<td valign="top">
						<i class="vteicon md-link" onclick="VTE.Update.removeMembers()">delete</i><br>
					</td>
				</tr>
				
			</table>
			
		</div>
	</div>
	<br><br>
	<div class="row" id="message_box" {if !$SCHEDULE_ALERT}style="display:none"{/if}>
		<div class="col-xs-10 col-xs-offset-1">
			<label>{$MOD.LBL_SEND_THIS_MESSAGE}</label><br><br>
			<div class="dvtCellInfo">
				<textarea id="schedule_message" name="schedule_message" class="detailedViewTextBox" rows="8" style="height:initial">{$MESSAGE_TEXT}</textarea>
			</div>
		</div>
	</div>
	
	</div> {* crmv@182073e *}
</div>

</form>

{assign var="FLOAT_TITLE" value=$MOD.LBL_MODIFIED_FILES}
{assign var="FLOAT_WIDTH" value="720px"}
{assign var="FLOAT_BUTTONS" value=""}
{capture assign="FLOAT_CONTENT"}
<textarea id="diff_content" style="font-family:monospace,sans-serif;height:initial" rows="12">
</textarea>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="DiffDetails"}


<script type="text/javascript">
var reportUsers = {$SHAREUSERS_JS};
var reportGroups = {$SHAREGROUPS_JS};
VTE.Update.changeMemberType();
{if $SCHEDULE_USERS}
VTE.Update.populateUsers({$SCHEDULE_USERS});
{/if}
</script>