{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}

{if !$TRACKER_ONLY_BUTTONS}
<div id="track_buttons" style="display:inline-block;vertical-align:middle;overflow:hidden;text-overflow:ellipsis;" 
	{if $TRACKER_DATA.enable_buttons neq true}title="{$APP.LBL_TRACKING_ALREADY_RUNNING} {$TRACKER_DATA.current_tracked_name|replace:'"':"&quot;"} ({$TRACKER_DATA.current_tracked_entity_type})"{/if}
>
{/if}
	{if $TRACKER_DATA.enable_buttons eq true}
		{foreach key=type item=enable from=$TRACKER_DATA.buttons}
			{if $enable eq true}
				{if $type == 'start'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCompose('{$type}');" >play_arrow</i>
				{elseif $type == 'pause'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCompose('{$type}');" >pause</i>
				{elseif $type == 'stop'}
					<i class="vteicon md-link" title="{$TRACKER_DATA.buttons_labels[$type]}" onClick="CalendarTracking.trackInCompose('{$type}');" >stop</i>
				{/if}
			{/if}
		{/foreach}
	{else}
		{$APP.LBL_TRACKING_ALREADY_RUNNING}
		{if $TRACKER_DATA.current_tracked_name|strlen > 40}
			{* long name *}
			{$TRACKER_DATA.current_tracked_entity_type} (<a href="index.php?module={$TRACKER_DATA.current_tracked_module}&action=DetailView&record={$TRACKER_DATA.current_tracked}">{$APP.LBL_SHOW_DETAILS}</a>).
		{else}
			{* short name *}
			<a href="index.php?module={$TRACKER_DATA.current_tracked_module}&action=DetailView&record={$TRACKER_DATA.current_tracked}">{$TRACKER_DATA.current_tracked_name}</a> ({$TRACKER_DATA.current_tracked_entity_type}).
		{/if}
	{/if}

{if !$TRACKER_ONLY_BUTTONS}
</div>
<span id="track_buttons_active_lbl" style="line-height:24px;display:none"><b><a>{$APP.Active}</a></b></span>
{/if}