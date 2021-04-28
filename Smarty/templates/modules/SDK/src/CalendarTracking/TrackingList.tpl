{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}

<script type="text/javascript" src="modules/SDK/src/CalendarTracking/CalendarTracking.js"></script>
<br>

{if count($TRACKLIST) > 0}
<table cellpadding="3" cellspacing="1" border="0" width="100%" class="lvt small" id="track_buttons">
	{foreach key=id item=info from=$TRACKLIST}
		<tr class="lvtColData" height="34">
			<td width="140" nowrap style="white-space:nowrap;letter-spacing:-8px">
				{if $info.enable eq true}
					<i class="vteicon md-link md-36" title="{$APP.LBL_PAUSE}" onClick="CalendarTracking.trackInCalendarList('{$id}','{$info.module}','pause');" >pause</i>
					<i class="vteicon md-link md-36" title="{$APP.LBL_FINISH}" onClick="CalendarTracking.trackInCalendarList('{$id}','{$info.module}','stop');" >stop</i>
					<i class="vteicon disabled md-36" title="{$APP.LBL_EJECT_TRACKING}">eject</i>
				{elseif $ACTIVE_TRACKED neq false && $ID neq $ACTIVE_TRACKED}
					<i class="vteicon disabled md-36" title="{$APP.LBL_START}">play_arrow</i>
					<i class="vteicon disabled md-36" title="{$APP.LBL_START}">stop</i>
					<i class="vteicon md-link md-36" title="{$APP.LBL_EJECT_TRACKING}" onClick="CalendarTracking.trackInCalendarList('{$id}','{$info.module}','eject');">eject</i>
				{else}
					<i class="vteicon md-link md-36" title="{$APP.LBL_START}" onClick="CalendarTracking.trackInCalendarList('{$id}','{$info.module}','start');" >play_arrow</i>
					<i class="vteicon disabled md-36" title="{$APP.LBL_START}">stop</i>
					<i class="vteicon md-link md-36" title="{$APP.LBL_EJECT_TRACKING}" onClick="CalendarTracking.trackInCalendarList('{$id}','{$info.module}','eject');">eject</i>
				{/if}
			</td>
			{* <td>{$info.number}</td> *}
			<td><a href="index.php?module={$info.module}&action=DetailView&record={$id}" target="_parent">{$info.name}</a></td>
			<td>{$info.entity_type}</td>
		</tr>
	{/foreach}
</table>
{else}
	<div style="width:98%;text-align:center;margin:10px">{$APP.LBL_TRACKING_NO_ENTRIES}</div>
{/if}

<table border=0 cellspacing=1 cellpadding=3 width="100%" class="small" id="track_message_tbl" style="display:none;">
	<tr>
		<td align="center">
			<textarea id="track_message" name="track_message" class="detailedViewTextBox" style="width:98%"></textarea>
		</td>
	</tr>
	<tr>
		<td align="right" id="track_message_tbl_buttons">
			<input type="hidden" name="track_message_id" id="track_message_id" value="" />
			<input type="hidden" name="track_message_module" id="track_message_module" value="" />
			<input type="hidden" name="track_message_type" id="track_message_type" value="" />
			{* crmv@79996 *}
			<span id="track_message_btns_helpdesk" style="display:none">
				<button type="button" title="{$APP.LBL_DO_TRACK}" name="button" onclick="CalendarTracking.changeTrackStateList(null, null, null, null, '');" class="crmbutton small save">{$APP.LBL_DO_TRACK}</button>&nbsp;
				<button type="button" title="{$APP.LBL_TRACK_AND_COMMENT}" name="button" onclick="CalendarTracking.changeTrackStateList();" class="crmbutton small save">{$APP.LBL_TRACK_AND_COMMENT}</button>
			</span>
			{* crmv@79996e *}
			<span id="track_message_btns_standard" style="display:none">
				<button type="button" title="{$APP.LBL_DO_TRACK}" name="button" onclick="CalendarTracking.changeTrackStateList();" class="crmbutton small save">{$APP.LBL_DO_TRACK}</button>&nbsp;
				{if $TICKETS_AVAILABLE}
					<button type="button" title="{$APP.LBL_DO_TRACK_AND}{"SINGLE_HelpDesk"|getTranslatedString}" name="button" onclick="CalendarTracking.changeTrackStateList(null, null, null, 'yes');" class="crmbutton small save">{$APP.LBL_DO_TRACK_AND}{"SINGLE_HelpDesk"|getTranslatedString}</button>
				{/if}
			</span>
			
			<button type="button" title="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}" name="button" onclick="location.reload();" class="crmbutton small cancel">{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}</button>
		</td>
	</tr>
</table>

<div id="detailview_block_indicator" width="100%" align="center" style="display:none;">
	{include file="LoadingIndicator.tpl"}
</div>