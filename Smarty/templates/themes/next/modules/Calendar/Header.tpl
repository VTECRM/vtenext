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
		<!-- Calendar Tabs starts -->
			<div>
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td>
							<div id="Buttons_List_3_Container" style="display:block;">
								<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100%>
									<tr>
										<td>{include file="Buttons_List_Contestual.tpl"}</td>
										
										<td align="right">
											<table>
												<tr>
													<td>
														<div class="dropdown calendarViewButton">
															<a class="crmbutton edit dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
																{if $VIEW eq 'day'} 
																	{$MOD.LBL_DAY}
																{elseif $VIEW eq 'week'}
																	{$MOD.LBL_WEEK}
																{elseif $VIEW eq 'month'}
																	{$MOD.LBL_MONTH}
																{/if}
																<span class="caret"></span>
															</a>
															<ul class="dropdown-menu dropdown-autoclose">
																<li><a id="showdaybtn" href="javascript:void(0)" onclick="VTE.CalendarView.switchView('showdaybtn')">{$MOD.LBL_DAY}</a></li>
																<li><a id="showweekbtn" href="javascript:void(0)" onclick="VTE.CalendarView.switchView('showweekbtn')">{$MOD.LBL_WEEK}</a></li>
																<li><a id="showmonthbtn" href="javascript:void(0)" onclick="VTE.CalendarView.switchView('showmonthbtn')">{$MOD.LBL_MONTH}</a></li>
																{if empty($smarty.request.from_module)}
																	<li><a id="showresbtn" href="javascript:void(0)" onclick="VTE.CalendarView.switchView('showresbtn')">{$MOD.LBL_RESOURCES}</a></li> {* crmv@194723 *}
																{/if}
																{* crmv@193138 *}
																{if empty($smarty.request.from_module)}
																	<li><a href="javascript:void(0)" onclick="VTE.CalendarView.calendarToList()">{$MOD.LBL_CAL_TO_FILTER}</a></li>
																{/if}
																{* crmv@193138e *}
															</ul>
														</div>
													</td>
													<td><div id="errorpannel_new" style="display:none;color:red;">{$MOD.LBL_CAL_INTERRUPTED}</div></td>
													<td><div class="todayButton"><button type="button" class="crmbutton edit" onclick="wdCalendar.jQuery('#BBIT-DP-TODAY').click();">{$APP.LBL_TODAY}</button></div></td>
													<td><div id="filterCalendar_new" style="float:right;"></div></td>
													<td><i class="vteicon md-link" onclick="jClickCalendar('sfprevbtn')">arrow_back</i></td>
													<td><button id="txtdatetimeshow_new" class="crmbutton" onclick="wdCalendar.jQuery('#BBIT_DP_CONTAINER').toggle()"></button></td>
													<td><i class="vteicon md-link" onclick="jClickCalendar('sfnextbtn')">arrow_forward</i></td>
												</tr>
											</table>
										</td>
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