{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62394 *}

<div id="trackerPopup" style="display:none; position:fixed;" class="crmvDiv">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="trackerPopup_Handle" style="padding:5px;font-size:14px" class="level3Bg" align="left">
				<b><span>{$APP.LBL_TRACK_MANAGER}</span></b>
			</td>
		</tr>
	</table>
	<div id="trackerPopup_Body" style="padding:6px">
		<table border=0 cellspacing=0 cellpadding=0 width="100%" class="small" id="track_message_tbl">
			<tr>
				<td class="dvtCellLabel" width="100%" align="left">{if $MODULE eq 'HelpDesk'}{'LBL_ADD_COMMENT'|getTranslatedString:'HelpDesk'}{else}{'Description'|getTranslatedString}{/if}</td>
			</tr>
			<tr>
				<td align="left">
					<div class="dvtCellInfo"><textarea id="track_message" name="track_message" class="detailedViewTextBox"></textarea></div>
				</td>
			</tr>
			<tr>
				<td align="center" id="track_message_tbl_buttons">
					<br>
					<input type="hidden" id="trackerPopup_type" name="trackerPopup_type" value="" />
					<input type="hidden" id="trackerPopup_id" name="trackerPopup_id" value="" />
					{if $MODULE eq 'HelpDesk'}
						<input type="button" title="{$APP.LBL_DO_TRACK}" value="{$APP.LBL_DO_TRACK}" name="button" onclick="CalendarTracking.changeTrackState('{$ID}', null, 'no', '');" class="crmbutton small save">
						<input type="button" title="{$APP.LBL_TRACK_AND_COMMENT}" value="{$APP.LBL_TRACK_AND_COMMENT}" name="button" onclick="CalendarTracking.changeTrackState('{$ID}', null, 'no');" class="crmbutton small save">
					{else}
						<input type="button" title="{$APP.LBL_DO_TRACK}" value="{$APP.LBL_DO_TRACK}" name="button" onclick="CalendarTracking.changeTrackState('{$ID}', null, 'no');" class="crmbutton small save">
						{if $TRACKER_DATA.tickets_available}
							<input type="button" title="{$APP.LBL_DO_TRACK_AND}{"SINGLE_HelpDesk"|getTranslatedString}" value="{$APP.LBL_DO_TRACK_AND}{"SINGLE_HelpDesk"|getTranslatedString}" name="button" onclick="CalendarTracking.changeTrackState('{$ID}', null, 'yes');" class="crmbutton small save">
						{/if}
					{/if}
					<input type="button" title="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}" value="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}" name="button" onclick="CalendarTracking.hidePopup()" class="crmbutton small cancel">
					<br><br>
				</td>
			</tr>
		</table>
	</div>
	<div class="closebutton" onClick="CalendarTracking.hidePopup();"></div>
</div>
<script type="text/javascript">
	// crmv@192014
	jQuery("#trackerPopup").draggable({ldelim}
		handle: '#trackerPopup_Handle'
	{rdelim});
	// crmv@192014e
</script>