{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{* ---- buttons ---- *}
{if $TRACKER_DATA.enable_buttons eq true}
	{include file="modules/SDK/src/CalendarTracking/TrackingSmallButtons.tpl"}
{else}
	{include file="modules/SDK/src/CalendarTracking/TrackingSmallButtons.tpl"}
{/if}
&nbsp;&nbsp;