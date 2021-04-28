{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}
{* crmv@101312 *}

{if $ACTIVITYDATA.activitytype neq 'Task'}
<table id="calendarExtraTable" border=0 cellspacing=0 cellpadding=0 width=100%> {* crmv@107341 *}
		<tr>
			<td>
				<table class="vte-tabs" border=0 cellspacing=0 cellpadding=3 width=100%>
					<tr>
						<td id="cellTabRelatedto" class="dvtSelectedCell" nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','on');ghide('addEventAlarmUI');ghide('addEventInviteUI');dispLayer('addEventRelatedtoUI');ghide('addEventRepeatUI');">{$MOD.LBL_LIST_RELATED_TO}</a></td>
						<td id="cellTabInvite" class="dvtUnSelectedCell" nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','on');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','off');ghide('addEventAlarmUI');dispLayer('addEventInviteUI');ghide('addEventRepeatUI');ghide('addEventRelatedtoUI');">{$MOD.LBL_INVITE}</a></td>
						{if $LABEL.reminder_time neq ''}
							<td id="cellTabAlarm" class="dvtUnSelectedCell" nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','on');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','off');dispLayer('addEventAlarmUI');ghide('addEventInviteUI');ghide('addEventRepeatUI');ghide('addEventRelatedtoUI');">{$MOD.LBL_REMINDER}</a></td>
						{/if}
						{if $LABEL.recurringtype neq ''}
							<td id="cellTabRepeat" class="dvtUnSelectedCell" nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','on');switchClass('cellTabRelatedto','off');ghide('addEventAlarmUI');ghide('addEventInviteUI');dispLayer('addEventRepeatUI');ghide('addEventRelatedtoUI');">{$MOD.LBL_REPEAT}</a></td>
						{/if}
						<td class="dvtTabCache" style="width:100%">&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width=100% valign=top align=left class="dvtContentSpace" style="padding:10px;height:150px;">
				<!-- Invite UI -->
				<div id="addEventInviteUI" class="vte-card" style="display:none;">
					{include file="modules/Calendar/EventInviteUIReadOnly.tpl" disableStyle=true}
				</div>
				<!-- Reminder UI -->
				<div id="addEventAlarmUI" class="vte-card" style="display:none;">
					{include file="modules/Calendar/EventAlarmUIReadOnly.tpl" disableStyle=true}
				</div>
				<!-- Repeat UI -->
				<div id="addEventRepeatUI" class="vte-card" style="display:none;">
					{include file="modules/Calendar/EventRepeatUIReadOnly.tpl" disableStyle=true}
				</div>
				<!-- Relatedto UI -->
				<div id="addEventRelatedtoUI" class="vte-card" style="display:block;">
					{include file="modules/Calendar/EventRelatedToUIReadOnly.tpl" disableStyle=true}
				</div>
			</td>
		</tr>
	</table>
{else}
	{if $LABEL.parent_id neq '' || $LABEL.contact_id neq ''}
		<table id="calendarExtraTable" border="0" cellpadding="0" cellspacing="0" width="100%"> {* crmv@107341 *}
			<tr>
				<td>
					<table class="vte-tabs" border="0" cellpadding="3" cellspacing="0" width="100%">
						<tr>
							{if ($LABEL.parent_id neq '') || ($LABEL.contact_id neq '') }
								<td id="cellTabRelatedto" class="dvtSelectedCell" nowrap><a href="javascript:doNothing()">{$MOD.LBL_RELATEDTO}</a></td>
							{/if}
							<td class="dvtTabCache" style="width: 100%;">&nbsp;</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td width=100% valign=top align=left class="dvtContentSpace" style="padding:10px;height:150px;">
					<div id="addTaskRelatedtoUI" class="vte-card" style="display:{$vision};">
						{include file="modules/Calendar/TodoRelatedToUIReadOnly.tpl" disableStyle=true}
					</div>
				</td>
			</tr>
		</table>
	{/if}
{/if}