{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}

{if !$TRACKER_ONLY_BUTTONS}
<div id="track_buttons" style="display:inline-block;vertical-align:middle;">
{/if}
	<input type="hidden" name="track_buttons_current_id" id="track_buttons_current_id" value="{$ID}">
	<input type="hidden" name="track_buttons_active_id" id="track_buttons_active_id" value="{$TRACKER_DATA.current_tracked}">
	{if $TRACKER_DATA.enable_buttons eq true}
		{foreach key=type item=enable from=$TRACKER_DATA.buttons}
			{if $enable eq true}
				<div class="btn btn-default track-button">
				{if $type == 'start'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);" >play_arrow</i>
				{elseif $type == 'pause'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);" >pause</i>
				{elseif $type == 'stop'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCalendar('{$ID}','{$type}', this);" >stop</i>
				{/if}
				</div>
			{/if}
		{/foreach}
	{else}
		<div class="track-label">
			{$APP.LBL_TRACKING_ALREADY_RUNNING}
			{if $TRACKER_DATA.current_tracked_name|strlen > 40}
				{* long name *}
				{$TRACKER_DATA.current_tracked_entity_type} (<a href="index.php?module={$TRACKER_DATA.current_tracked_module}&action=DetailView&record={$TRACKER_DATA.current_tracked}">{$APP.LBL_SHOW_DETAILS}</a>).
			{else}
				{* short name *}
				<a href="index.php?module={$TRACKER_DATA.current_tracked_module}&action=DetailView&record={$TRACKER_DATA.current_tracked}">{$TRACKER_DATA.current_tracked_name}</a> ({$TRACKER_DATA.current_tracked_entity_type}).
			{/if}
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