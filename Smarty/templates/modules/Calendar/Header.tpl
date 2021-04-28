{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{literal}
<style>
	.showPanelBg, .showPanelBg * {
		padding: 0px;
	}
	.showPanelBg {
		padding-top: 5px;
	}
</style>
{/literal}

<table border="0" cellspacing="0" cellpadding="0" width="100%" align="center">
	<tr>
		<td class="showPanelBg" valign="top" width="100%">
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td>
						<div id="Buttons_List_3_Container" style="display:block;">
							<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
								<tr>
									{if $smarty.request.action eq 'index'}
			                
										<td align="left">
										
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_DAY}" onclick="VTE.CalendarView.switchView('showdaybtn')" /> {* crmv@194723 *}
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_WEEK}" onclick="VTE.CalendarView.switchView('showweekbtn')" /> {* crmv@194723 *}
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_MONTH}" onclick="VTE.CalendarView.switchView('showmonthbtn')" /> {* crmv@194723 *}
											{if empty($smarty.request.from_module)}
												<input class="crmbutton small edit" type="button" value="{$MOD.LBL_RESOURCES}" onclick="VTE.CalendarView.switchView('showresbtn')" /> {* crmv@194723 *}
											{/if}
											{if empty($smarty.request.from_module)}
												<input class="crmbutton small edit" type="button" value="{$MOD.LBL_CAL_TO_FILTER}" onclick="VTE.CalendarView.calendarToList()" /> {* crmv@194723 *}
											{/if}
											{if 'Geolocalization'|vtlib_isModuleActive} {* crmv@186646 *}
												<input class="crmbutton small edit" type="button" value="{'Geolocalization'|getTranslatedString:'Geolocalization'}" onclick="window.wdCalendar.GeoCalendar();" />
											{/if}
										
										</td>

										<td align="right">
											<table>
												<tr>
													<td><div id="errorpannel_new" style="display:none;color:red;">{$MOD.LBL_CAL_INTERRUPTED}</div></td>
													<td><input class="crmbutton small edit" type="button" value="{$APP.LBL_TODAY}" onclick="wdCalendar.jQuery('#BBIT-DP-TODAY').click();" /></td>
													<td><div id="filterCalendar_new" style="float:right;"></div></td>
													<td><i class="vteicon md-link" onclick="jClickCalendar('sfprevbtn')">arrow_back</i></td>
													<td><button id="txtdatetimeshow_new" class="crmbutton small" onclick="wdCalendar.jQuery('#BBIT_DP_CONTAINER').toggle()"></button></td>
													<td><i class="vteicon md-link" onclick="jClickCalendar('sfnextbtn')">arrow_forward</i></td>
												</tr>
											</table>
										</td>
										
									{else}
									
										<td style="padding:5px" nowrap>
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_DAY}" onclick="listToCalendar('Today')" />
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_WEEK}" onclick="listToCalendar('This Week')" />
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_MONTH}" onclick="listToCalendar('This Month')" />
											<input class="crmbutton small edit" type="button" value="{$MOD.LBL_CAL_TO_FILTER}" onclick="location.href = 'index.php?action=ListView&module=Calendar&parenttab={$CATEGORY}'" />
										</td>
									
									{/if}
								</tr>
							</table>
						</div>
					</td>
				</tr>
            
				<tr>
					<td align="left" valign="top">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td align="left">
									<!-- content cache -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%">
										<tr>
											<td>

<script>calculateButtonsList3();</script>
				                
{if $USE_ICAL}
	<div id="Button_List_Ical_Cont" style="display:none">
		<input type="hidden" id="ical_{$ICALID}_activityid" value="{$ACTIVITY_ID}" />
		
		{'LBL_INVITATION_QUESTION'|getTranslatedString:'ModNotifications'}?
		<button type="button" class="crmbutton edit" onclick="Messages_iCal.previewReplyYes('{$MESSAGE_ID}', '{$ICALID}');">{$APP.LBL_YES}</button>
		<button type="button" class="crmbutton edit" onclick="Messages_iCal.previewReplyNo('{$MESSAGE_ID}', '{$ICALID}');">{$APP.LBL_NO}</button>
	</div>
	
	{literal}
		<script type="text/javascript">
			jQuery('#Button_List_Ical').html(jQuery('#Button_List_Ical_Cont').html());
			jQuery('#Button_List_Ical_Cont').html('');
		</script>
	{/literal}
{/if}