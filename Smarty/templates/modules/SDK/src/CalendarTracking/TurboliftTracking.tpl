{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}
{* crmv@105588 *}

{if $TRACKER_FOR_COMPOSE}
	<div id="turbolift_tracker_cont">
		<span>{$APP.Tracking}</span>
		{include file="modules/SDK/src/CalendarTracking/TrackingButtonsCompose.tpl"}
		{include file="modules/SDK/src/CalendarTracking/PopupTracking.tpl" ID=0}
	</div>
{else}
	<div id="turbolift_tracker_cont" class="messagesTurboliftEntry btn">
		<div class="row no-gutter">
			<div class="col-sm-12">
				<div class="trackLabel col-sm-6 vcenter text-left">
					<span>{$APP.Tracking}</span>
				</div><!-- 
				 --><div class="trackButtons col-sm-6 vcenter text-right btn-group detail-view-topbar-group">
					{include file="modules/SDK/src/CalendarTracking/TrackingSmallButtons.tpl" ID=$RECORD}
				</div>
				{include file="modules/SDK/src/CalendarTracking/PopupTracking.tpl" ID=0}
			</div>
		</div>
	</div>
{/if}