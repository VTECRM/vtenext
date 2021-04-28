{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<script type="text/javascript" src="{"modules/Calendar/CalendarPopup.js"|resourcever}"></script>

{* crmv@138006 *}
<div class="calAddEvent layerPopup" id="addEvent" style="display:none">

	<div class="closebutton"></div>
	
	{include file="modules/Calendar/PopupHeader.tpl"}
	
	<div class="main-content container-fluid">
		<div class="row">
			<div class="col-xs-12">
				<input type="hidden" name="pview" value="{$CALENDAR_OBJ.view}">
				<input type="hidden" name="phour" value="{$CALENDAR_OBJ.calendar->date_time->hour}">
				<input type="hidden" name="pday" value="{$CALENDAR_OBJ.calendar->date_time->day}">
				<input type="hidden" name="pmonth" value="{$CALENDAR_OBJ.calendar->date_time->month}">
				<input type="hidden" name="pyear" value="{$CALENDAR_OBJ.calendar->date_time->year}">
				
				<div id="header-tab-content" class="tab-content">
					<div id="event-tab" class="tab-pane fade in active">
						<div class="tab-container"></div>
					</div>
				
					<div id="todo-tab" class="tab-pane fade">
						<div class="tab-container"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
{* crmv@138006e *}

<div id="eventcalAction" class="calAction" style="width:125px;" onMouseout="fninvsh('eventcalAction')" onMouseover="fnvshNrm('eventcalAction')">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF">
		<tr>
			<td>
				{if $EDITVIEW_PERMITTED}
					{if $VISIBILITY_PERMISSIONS.eventstatus}
						<a href="javascript:;" id="complete" onClick="fninvsh('eventcalAction')" class="calMnu">- {$MOD.LBL_HELD}</a>
						<a href="javascript:;" id="pending" onClick="fninvsh('eventcalAction')" class="calMnu">- {$MOD.LBL_NOTHELD}</a>
					{/if}
					<span style="border-top:1px dashed #CCCCCC;width:99%;display:block;"></span>
					<a href="javascript:;" id="postpone" onClick="fninvsh('eventcalAction')" class="calMnu">- {$MOD.POSTPONE}</a>
					<a href="javascript:;" id="changeowner" onClick="cal_fnvshobj(this,'act_changeowner');fninvsh('eventcalAction')" class="calMnu">- {$MOD.LBL_CHANGEOWNER}</a>
				{/if}
				{if $DELETE_PERMITTED}
					<a href="" id="actdelete" onclick ="fninvsh('eventcalAction');return confirm('Are you sure?');" class="calMnu">- {$MOD.LBL_DEL}</a>
				{/if}
			</td>
		</tr>
	</table>
</div>

<div id="addEventDropDown" class="drop_mnu" style="width:160px" onmouseover="fnShowEvent()" onmouseout="fnRemoveEvent()">
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
		{* crmv@8398 *}
		{foreach from=$EVENT_LIST_ARR item=eventlist}
			{* crmv@123806 *}
			{assign var="_eventlist" value=$eventlist|strtolower}
			{assign var="eventlist_id" value=$_eventlist|replace:' ':'_'}
			<tr><td><a href="" id="add{$eventlist_id}" class="drop_down">{$eventlist|getTranslatedString}</a></td></tr>
			{* crmv@123806e *}
		{/foreach}
		{* crmv@8398e *}
		<tr><td><a href="" id="addtodo" class="drop_down">{$MOD.LBL_ADDTODO}</a></td></tr>
	</table>
</div>

<div id="act_changeowner" class="statechange" style="left:250px;top:200px;z-index:5000">
	<form name="change_owner">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" value="" name="idlist" id="idlist">
		<input type="hidden" value="" name="action">
		<input type="hidden" value="" name="hour">
		<input type="hidden" value="" name="day">
		<input type="hidden" value="" name="month">
		<input type="hidden" value="" name="year">
		<input type="hidden" value="" name="view">
		<input type="hidden" value="" name="module">
		<input type="hidden" value="" name="subtab">
		
		<table width="100%" border="0" cellpadding="3" cellspacing="0">
			<tr>
				<td class="genHeaderSmall" align="left" style="border-bottom:1px solid #CCCCCC;" width="60%">{$APP.LBL_CHANGE_OWNER}</td>
				<td style="border-bottom: 1px solid rgb(204, 204, 204);">&nbsp;</td>
				<td align="right" style="border-bottom:1px solid #CCCCCC;" width="40%"><a href="javascript:fninvsh('act_changeowner')"><img src="{'close.gif'|resourcever}" align="absmiddle" border="0"></a></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td width="50%"><b>{$APP.LBL_TRANSFER_OWNERSHIP}</b></td>
				<td width="2%"><b>:</b></td>
				<td width="48%">
	            	<input type="radio" id="user_checkbox" name="user_lead_owner" {if !empty($GROUP_LIST)}onclick="checkgroup();"{/if} checked>{$APP.LBL_USER}&nbsp;
				
					{if !empty($GROUP_LIST)}
						<input type="radio" id="group_checkbox" name="user_lead_owner" onclick="checkgroup();">{$APP.LBL_GROUP}<br>
						<select name="lead_group_owner" id="lead_group_owner" class="detailedViewTextBox" style="display:none;">
							{$GROUP_LIST}
						</select>
					{/if}
					
		            <select name="lead_owner" id="lead_owner" class="detailedViewTextBox" style="display:block">
						{$USERS_LIST}
		            </select>
        		</td>
			</tr>
			<tr><td colspan="3" style="border-bottom:1px dashed #CCCCCC;">&nbsp;</td></tr>
			<tr>
				<td colspan="3" align="center">&nbsp;&nbsp;
					<input type="button" name="button" class="crm button small save" value="{$APP.LBL_UPDATE_OWNER}" onClick="calendarChangeOwner();fninvsh('act_changeowner');">
					<input type="button" name="button" class="crm button small cancel" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onClick="fninvsh('act_changeowner')">
				</td>
			</tr>
		</table>
	</form>
</div>


<div id="taskcalAction" class="calAction" style="width:125px;" onMouseout="fninvsh('taskcalAction')" onMouseover="fnvshNrm('taskcalAction')">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF">
		<tr>
			<td>
				{if $EDITVIEW_PERMITTED}
					{if $VISIBILITY_PERMISSIONS.taskstatus}
						<a href="" id="taskcomplete" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_COMPLETED}</a>
						<a href="" id="taskpending" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_DEFERRED}</a>
						<!--ds@45-->
						<a href="" id="tasknotstarted" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_NOT_STARTED}</a>
						<a href="" id="taskinprogress" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_IN_PROGRESS}</a>
						<a href="" id="taskpendinginput" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_PENDING_INPUT}</a>
						<a href="" id="taskplanned" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_PLANNED}</a>
						<!--ds@45e-->
					{/if}
					<span style="border-top:1px dashed #CCCCCC;width:99%;display:block;"></span>
					<a href="" id="taskpostpone" onClick="fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_POSTPONE}</a>
					<a href="" id="taskchangeowner" onClick="cal_fnvshobj(this,'act_changeowner'); fninvsh('taskcalAction');" class="calMnu">- {$MOD.LBL_CHANGEOWNER}</a>
				{/if}
				{if $DELETE_PERMITTED}
					<a href="" id="taskactdelete" onClick ="fninvsh('taskcalAction');return confirm('Are you sure?');" class="calMnu">- {$MOD.LBL_DEL}</a>
				{/if}
			</td>
		</tr>
	</table>
</div>