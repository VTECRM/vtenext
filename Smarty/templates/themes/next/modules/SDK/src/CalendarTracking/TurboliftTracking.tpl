{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}
{* crmv@105588 *}

<div id="turbolift_tracker_cont">
	{if $TRACKER_FOR_COMPOSE}
		<span>{$APP.Tracking}</span>
		{include file="modules/SDK/src/CalendarTracking/TrackingButtonsCompose.tpl"}
	{else}
		<button type="button" class="crmbutton with-icon edit btn-block crmbutton-turbolift">
			{include file="modules/SDK/src/CalendarTracking/TrackingSmallButtons.tpl" ID=$RECORD}
			<span>{$APP.Tracking}</span>
		</button>
	{/if}
</div>