{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

<div class="row">
	<div class="col-sm-12">
		<ul id="calendar-options" data-content="#calendar-options-content" class="nav nav-tabs">
			<li class="active" style="text-align:center">
				<a data-toggle="tab" href="#addEventRelatedtoUICont">
					<i class="vteicon avatar">view_list</i><br>
					{$MOD.LBL_RELATEDTO}
				</a>
			</li>
			<li class="" style="text-align:center">
				<a data-toggle="tab" href="#addEventInviteUICont">
					<i class="vteicon">group</i><br>
					{$MOD.LBL_INVITE}
				</a>
			</li>
			<li class="" style="text-align:center">
				<a data-toggle="tab" href="#addEventAlarmUICont">
					<i class="vteicon">alarm_on</i><br>
					{$MOD.LBL_REMINDER}
				</a>
			</li>
			<li class="" style="text-align:center">
				<a data-toggle="tab" href="#addEventRepeatUICont">
					<i class="vteicon">repeat</i><br>
					{$MOD.LBL_REPEAT}
				</a>
			</li>
		</ul>
	</div>
	
	<div class="col-sm-12">
		<div id="calendar-options-content" class="tab-content" style="padding:15px">
			<div id="addEventRelatedtoUICont" class="tab-pane fade in active">
				<div id="addEventRelatedtoUI" class="calendar-widget" style="width:100%">
				{if empty($MODE) || $MODE eq 'edit'}
					{include file="modules/Calendar/EventRelatedToUI.tpl"}
				{else}
					{include file="modules/Calendar/EventRelatedToUIReadOnly.tpl"}
				{/if}
				</div>
			</div>
			
			<div id="addEventInviteUICont" class="tab-pane fade in">
				<div id="addEventInviteUI" class="calendar-widget" style="width:100%">
				{if empty($MODE) || $MODE eq 'edit'}
					{include file="modules/Calendar/EventInviteUI.tpl"}
				{else}
					{include file="modules/Calendar/EventInviteUIReadOnly.tpl"}
				{/if}
				</div>
			</div>
			
			<div id="addEventAlarmUICont" class="tab-pane fade in">
				<div id="addEventAlarmUI" class="calendar-widget" style="width:100%">
				{if empty($MODE) || $MODE eq 'edit'}
					{include file="modules/Calendar/EventAlarmUI.tpl"}
				{else}
					{include file="modules/Calendar/EventAlarmUIReadOnly.tpl"}
				{/if}
				</div>
			</div>
			
			<div id="addEventRepeatUICont" class="tab-pane fade in">
				<div id="addEventRepeatUI" class="calendar-widget" style="width:100%">
				{if empty($MODE) || $MODE eq 'edit'}
					{include file="modules/Calendar/EventRepeatUI.tpl"}
				{else}
					{include file="modules/Calendar/EventRepeatUIReadOnly.tpl"}
				{/if}
				</div>
			</div>
		</div>
	</div>
</div>