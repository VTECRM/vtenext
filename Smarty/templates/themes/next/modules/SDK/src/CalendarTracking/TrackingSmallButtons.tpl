{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{if !$TRACKER_ONLY_BUTTONS}
<div id="track_buttons" class="vcenter">
{/if}

<input type="hidden" name="track_buttons_current_id" id="track_buttons_current_id" value="{$ID}">
<input type="hidden" name="track_buttons_active_id" id="track_buttons_active_id" value="{$TRACKER_DATA.current_tracked}">

{if $TRACKER_DATA.enable_buttons eq true}
	{foreach key=type item=enable from=$TRACKER_DATA.buttons}
		{if $enable eq true}
			{if $type == 'start'}
				<div class="vcenter">
					<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);">play_arrow</i>
				</div>
			{elseif $type == 'pause'}
				<div class="vcenter">
					<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);">pause</i>
				</div>
			{elseif $type == 'stop'}
				<div class="vcenter">
					<i data-toggle="tooltip" data-placement="bottom" class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);">stop</i>
				</div>
			{/if}
		{/if}
	{/foreach}
{else}
	<div>
		<div class="vcenter">
			<i data-toggle="tooltip" data-placement="bottom" id="runningTrack" title="{$APP.LBL_TRACKING_ALREADY_RUNNING} {$TRACKER_DATA.current_tracked_name}" class="vteicon md-link blink" onclick="CalendarTracking.runningTrackClicked()">timer</i>
		</div>
		<div class="vcenter">
			<a href="index.php?module={$TRACKER_DATA.current_tracked_module}&action=DetailView&record={$TRACKER_DATA.current_tracked}">
				<i data-toggle="tooltip" data-placement="bottom" id="openRunningTrack" title="{$TRACKER_DATA.current_tracked_name}" class="vteicon md-link">open_in_new</i>
			</a>
		</div>
	</div>
{/if}

{if $TRACKER_DATA.already_tracked_by_other neq false}
	{$APP.LBL_TRACKING_ALREADY_RUNNING_BY_USER}
	{foreach item=userid from=$TRACKER_DATA.already_tracked_by_other}
		({$userid|getUserName}) {$userid|getUserFullName}, 
	{/foreach}
{/if}
	
{if !$TRACKER_ONLY_BUTTONS}
</div>
{/if}