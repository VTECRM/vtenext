{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@OPER6317 crmv@98866 crmv@105416 crmv@108227 crmv@181170 *}

<script type="text/javascript" src="modules/{$MODULE}/Calendar.js"></script>

<div class="container-fluid mainContainer pt-3">
	<div class="row">
		<div class="col-sm-12">
			<!-- Contents -->
			<form name="EditView" method="POST" action="index.php">
				<input type="hidden" name="view" value="{$view}">
				<input type="hidden" name="hour" value="{$hour}">
				<input type="hidden" name="day" value="{$day}">
				<input type="hidden" name="month" value="{$month}">
				<input type="hidden" name="year" value="{$year}">

				<input type="hidden" name="time_start" id="time_start">
				<input type="hidden" name="time_end" id="time_end">

				<input type="hidden" name="followup_due_date" id="followup_due_date">
				<input type="hidden" name="followup_time_start" id="followup_time_start">
				<input type="hidden" name="followup_time_end" id="followup_time_end">
				<input type="hidden" name="duration_hours" value="0">
				<input type="hidden" name="duration_minutes" value="0">

				<input type="hidden" name="inviteesid" id="inviteesid" value="">
				<input type="hidden" name="inviteesid_con" id="inviteesid_con" value="">

				<input type="hidden" name="viewOption" value="{$viewOption}">
				<input type="hidden" name="view_filter" value="{$view_filter}">
				<input type="hidden" name="subtab" value="{$subtab}">
				<input type="hidden" name="maintab" value="{$maintab}">
				<input type="hidden" name="dateformat" value="{$DATEFORMAT}">
				<input type="hidden" name="ajaxCalendar" value="">
				{* crmv@52311 *}
				<script type="text/javascript"> 
				function stopRKey(evt) {ldelim}
					var evt = (evt) ? evt : ((event) ? event : null); 
					var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null); 
					if ((evt.keyCode == 13) && (node.type=="text"))  {ldelim}return false;{rdelim} 
				{rdelim}
				document.onkeypress = stopRKey; 
				</script>
				{* crmv@52311e *}

			<table width="100%" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					{include file='EditViewHidden.tpl'}
					{* crmv@42752 *}
					{if $HIDE_BUTTON_LIST neq '1'}
						{include file='Buttons_List.tpl'}	{* crmv@27061 *}
						{include file='Buttons_List_Edit.tpl'}	{* crmv@27061 *}
					{/if}
					{* crmv@42752e *}
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr>
							<td valign=top align=left >
									<table border=0 cellspacing=0 cellpadding=0 width=100%>
											<tr>
								<td align=left>
								<!-- content cache -->

								<table border=0 cellspacing=0 cellpadding=0 width=100%>
								<tr>
									<td>
									
										<!-- included to handle the edit fields based on ui types -->
										{foreach key=header item=maindata from=$BLOCKS}
										{assign var="blockid" value=$maindata.blockid} {* crmv@124729 *}
										
										<div id="block_{$blockid}" class="vte-card editBlock">
											<div class="dvInnerHeader">
												<table border=0 cellspacing=0 cellpadding=0 width=100% class="small editBlockHeader"> {* crmv@124729 *}
													<tr>
														<td colspan=4>
															<div class="dvInnerHeaderTitle">{$header}</div>
														</td>
													</tr>
												</table>
											</div>
										{/foreach}
										
										{if $ACTIVITY_MODE neq 'Task'}
										<table border=0 cellspacing=0 cellpadding=5 width=100% >
											<tbody id="displayfields_{$blockid}" class="blockrow_{$blockid}"> {* crmv@124729 *}
										{if $LABEL.activitytype neq ''}
										<tr>
											<td width="50%">
												{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="activitytype"} {* crmv@187674 *}
											</td>
											<!-- crmv@17001 -->
											<td width="50%">
												{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="visibility"} {* crmv@158871 TODO: apply to other fields! *}
											</td>
											<!-- crmv@17001 -->
										</tr>
										{/if}
										<tr>
											<td colspan="2">
												<div>
													{include file="FieldHeader.tpl" label=$MOD.LBL_EVENTNAME fldname="subject" massedit=$MASS_EDIT}
													<div class="dvtCellInfo">
														<input name="subject" type="text" class="detailedViewTextBox" value="{$ACTIVITYDATA.subject}" style="width:50%">
													</div>
												</div>
											</td>
										</tr>
										{if $LABEL.description neq ''}
											<tr>
												<td colspan="2">
													<div>
														{include file="FieldHeader.tpl" label=$LABEL.description fldname="description" massedit=$MASS_EDIT}
														<div class="dvtCellInfo">
															<textarea style="width:100%; height : 60px;" name="description" class="detailedViewTextBox">{$ACTIVITYDATA.description}</textarea>
														</div>
													</div>
												</td>
											</tr>
										{/if}
										{* crmv@124729 *}
										<tr>
											<td width="50%">
												{if $LABEL.location neq ''}
												<div>
													{include file="FieldHeader.tpl" label=$MOD.LBL_APP_LOCATION fldname="location" massedit=$MASS_EDIT}
													<div class="dvtCellInfo">
														<input name="location" type="text" class="detailedViewTextBox" value="{$ACTIVITYDATA.location}">
													</div>
												</div>
												{/if}
											</td>
											<td width="50%">
												{* crmv@187674 *}
												{if $LABEL.eventstatus neq ''}
													{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="eventstatus"}
												{/if}
												{* crmv@187674e *}
											</td>
										</tr>
										<tr>
											<td width="50%">
												{if $ACTIVITYDATA.assigned_user_id != ''}
													{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="assigned_user_id"} {* crmv@158871 crmv@171575 *}
												{else}
													<input name="assigned_user_id" value="{$CURRENTUSERID}" type="hidden">
												{/if}
											</td>
											<td width="50%">
												{* crmv@187674 *}
												{if $LABEL.taskpriority neq ''}
													{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="taskpriority"}
												{/if}
												{* crmv@187674e *}
											</td>
										</tr>
										</tbody>
										<tr class="blockrow_{$blockid}" style="height:25px;"><td>&nbsp;</td></tr>
									</table>
									</div>
									{* crmv@124729e *}

									<!-- crmv@31315 -->
									<div class="vte-card">
										<table border=0 id="date_table" cellspacing=0 cellpadding=0 width=100% align=center>
											<tr>
												<td>
													<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
													<tr><td width=50% id="date_table_firsttd" valign=top>
														<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
															<tr><td colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$MOD.LBL_EVENTSTAT}</div></div></td></tr>
															<tr><td colspan="2" nowrap>
																<div class="dvtCellInfo">
																	{foreach key=date_value item=time_value from=$ACTIVITYDATA.date_start}
																		{assign var=date_val value="$date_value"}
																		{assign var=time_val value="$time_value"}
																	{/foreach}
																	{if $PROCESSMAKER_MODE}
																		<table border="0" cellpadding="0" cellspacing="0" width="100%">
																			<tr valign="bottom">
																				<td width="50%">
																					<label class="dvtCellLabel">
																					{if $MASS_EDIT eq '1'}
																						<input type="checkbox" name="date_start_mass_edit_check" id="date_start_mass_edit_check" class="small">
																						<label for="date_start_mass_edit_check">
																					{/if}
																					{'LBL_LIST_DATE'|getTranslatedString:'Calendar'}
																					{if $MASS_EDIT eq '1'}
																						</label>
																					{/if}
																					</label>
																					<div class="dvtCellInfo">
																						<select class="detailedViewTextBox" name="date_start_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'date_start')">
																							<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																							<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																							<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																						</select>
																					</div>
																				</td>
																				<td>
																					<input type="text" name="date_start" id="jscal_field_date_start" class="form-control" onChange="dochange('jscal_field_date_start','jscal_field_due_date');" value="{$date_val}" style="display:none">
																				</td>
																				<td style="padding-right:2px;">
																					<i class="vteicon md-link" id="jscal_trigger_date_start" title="{$MOD.LBL_SET_DATE}" style="display:none">events</i>
																				</td>
																			</tr>
																			<tr>
																				<td colspan="3" nowrap>
																					<div class="editoptions" fieldname="date_start_opt_num" style="float:right; display:none"></div>
																				</td>
																			</tr>
																			<tr>
																				<td colspan="3" nowrap>
																					<div id="date_start_adv_options" style="display:none">
																						<div style="float:left; width:10%; padding-right:10px">
																							<select class="detailedViewTextBox" name="date_start_opt_operator">
																								<option value="add">+</option>
																								<option value="sub">-</option>
																							</select>
																						</div>
																						<div style="float:left; width:30%; padding-right:5px">
																							<input type="text" class="detailedViewTextBox" name="date_start_opt_num">
																						</div>
																						<div style="float:left; padding-right:10px">
																							<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="date_start_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('date_start_opt_num')">more_horiz</i>
																						</div>
																						<div style="float:left; width:30%; padding-right:10px">
																							<select class="detailedViewTextBox" name="date_start_opt_unit">
																								<option value="day">{'lbl_days'|getTranslatedString:'ModComments'}</option>
																								<option value="month">{'lbl_months'|getTranslatedString:'ModComments'}</option>
																								<option value="year">{'lbl_years'|getTranslatedString:'ModComments'}</option>
																							</select>
																						</div>
																					</div>
																				</td>
																			</tr>
																		</table>
																	{else}
																		<table border="0" cellpadding="0" cellspacing="0">
																			<tr>
																				{* crmv@82419 crmv@100585 *}
																				<td>
																					<input type="text" name="date_start" id="jscal_field_date_start" class="form-control" onChange="dochange('jscal_field_date_start','jscal_field_due_date');" value="{$date_val}">
																				</td>
																				<td style="padding-right:2px;">
																					<i class="vteicon md-link" id="jscal_trigger_date_start" title="{$MOD.LBL_SET_DATE}">events</i>
																				</td>
																				{* crmv@82419e *}
																			</tr>
																		</table>
																	{/if}
																	{foreach key=date_fmt item=date_str from=$calsecondvalue.date_start}
																		{assign var=date_vl value="$date_fmt"}
																		{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
																	{/foreach}
																	<script type="text/javascript">
																		{* crmv@82419 *}
																		(function() {ldelim}
																			setupDatePicker('jscal_field_date_start', {ldelim}
																				trigger: 'jscal_trigger_date_start',
																				date_format: "{$dateStr|strtoupper}",
																				language: "{$APP.LBL_JSCALENDAR_LANG}",
																			{rdelim});
																		{rdelim})();
																		{* crmv@82419e crmv@100585e *}
																	</script>
																</div>
															</td></tr>
															<tbody id="time_event_start">
															<tr valign="bottom">
																{if $PROCESSMAKER_MODE}
																	<td width="50%">
																		<label class="dvtCellLabel">
																		{if $MASS_EDIT eq '1'}
																			<input type="checkbox" name="time_start_mass_edit_check" id="time_start_mass_edit_check" class="small">
																			<label for="time_start_mass_edit_check">
																		{/if}
																		{'LBL_TIME'|getTranslatedString:'Calendar'}
																		{if $MASS_EDIT eq '1'}
																			</label>
																		{/if}
																		</label>
																		<div class="dvtCellInfo">
																			<select class="detailedViewTextBox" name="time_start_options" onChange="ActionTaskScript.calendarTimeOptions(this.value,'time_start')">
																				<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																				<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																				<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																			</select>
																		</div>
																	</td>
																{/if}
																<td><div id="time_start_custom" class="dvtCellInfo" {if $PROCESSMAKER_MODE}style="display:none"{/if}>{$STARTHOUR}</div></td>
															</tr>
															{if $PROCESSMAKER_MODE}
																<tr>
																	<td colspan="2" nowrap>
																		<div class="editoptions" fieldname="time_start_opt_num" style="float:right; display:none"></div>
																	</td>
																</tr>
																<tr>
																	<td colspan="2" nowrap>
																		<div id="time_start_adv_options" class="dvtCellInfo" style="display:none">
																			<div style="float:left; width:10%; padding-right:10px">
																				<select class="detailedViewTextBox" name="time_start_opt_operator">
																					<option value="add">+</option>
																					<option value="sub">-</option>
																				</select>
																			</div>
																			<div style="float:left; width:30%; padding-right:5px">
																				<input type="text" class="detailedViewTextBox" name="time_start_opt_num">
																			</div>
																			<div style="float:left; padding-right:10px">
																				<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="time_start_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('time_start_opt_num')">more_horiz</i>
																			</div>
																			<div style="float:left; width:30%; padding-right:10px">
																				<select class="detailedViewTextBox" name="time_start_opt_unit">
																					<option value="hour">{'lbl_hours'|getTranslatedString:'ModComments'}</option>
																					<option value="minute">{'lbl_minutes'|getTranslatedString:'ModComments'}</option>
																				</select>
																			</div>
																		</div>
																	</td>
																</tr>
															{/if}
															</tbody>
														</table></td>
														<td width=50% valign=top id="date_table_secondtd">
															<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
																<tr><td colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$MOD.LBL_EVENTEDAT}</div></div></td></tr>
																<tr><td colspan="2">
																	<div class="dvtCellInfo">
																		{foreach key=date_value item=time_value from=$ACTIVITYDATA.due_date}
																			{assign var=date_val value="$date_value"}
																			{assign var=time_val value="$time_value"}
																		{/foreach}
																		{if $PROCESSMAKER_MODE}
																			<table border="0" cellpadding="0" cellspacing="0" width="100%">
																				<tr valign="bottom">
																					<td width="50%">
																						<label class="dvtCellLabel">
																						{if $MASS_EDIT eq '1'}
																							<input type="checkbox" name="due_date_mass_edit_check" id="due_date_mass_edit_check" class="small">
																							<label for="due_date_mass_edit_check">
																						{/if}
																						{'LBL_LIST_DATE'|getTranslatedString:'Calendar'}
																						{if $MASS_EDIT eq '1'}
																							</label>
																						{/if}
																						</label>
																						<div class="dvtCellInfo">
																							<select class="detailedViewTextBox" name="due_date_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'due_date')">
																								<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																								<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																								<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																							</select>
																						</div>
																					</td>
																					<td>
																						<input type="text" name="due_date" id="jscal_field_due_date" class="form-control" value="{$date_val}" style="display:none">
																					</td>
																					<td style="padding-right:2px;">
																						<i class="vteicon md-link" id="jscal_trigger_due_date" title="{$MOD.LBL_SET_DATE}" style="display:none">events</i>
																					</td>
																				</tr>
																				<tr>
																					<td colspan="3" nowrap>
																						<div class="editoptions" fieldname="due_date_opt_num" style="float:right; display:none"></div>
																					</td>
																				</tr>
																				<tr>
																					<td colspan="3" nowrap>
																						<div id="due_date_adv_options" style="display:none">
																							<div style="float:left; width:10%; padding-right:10px">
																								<select class="detailedViewTextBox" name="due_date_opt_operator">
																									<option value="add">+</option>
																									<option value="sub">-</option>
																								</select>
																							</div>
																							<div style="float:left; width:30%; padding-right:5px">
																								<input type="text" name="due_date_opt_num" class="form-control">
																							</div>
																							<div style="float:left; padding-right:10px">
																								<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="due_date_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('due_date_opt_num')">more_horiz</i>
																							</div>
																							<div style="float:left; width:30%; padding-right:10px">
																								<select class="detailedViewTextBox" name="due_date_opt_unit">
																									<option value="day">{'lbl_days'|getTranslatedString:'ModComments'}</option>
																									<option value="month">{'lbl_months'|getTranslatedString:'ModComments'}</option>
																									<option value="year">{'lbl_years'|getTranslatedString:'ModComments'}</option>
																								</select>
																							</div>
																						</div>
																					</td>
																				</tr>
																			</table>
																		{else}
																			<table border="0" cellpadding="0" cellspacing="0">
																				<tr>
																					{* crmv@82419 crmv@100585 *}
																					<td>
																						<input type="text" name="due_date" id="jscal_field_due_date" class="form-control" value="{$date_val}">
																					</td>
																					<td style="padding-right:2px;">
																						<i class="vteicon md-link" id="jscal_trigger_due_date" title="{$MOD.LBL_SET_DATE}">events</i>
																					</td>
																					{* crmv@82419e *}
																				</tr>
																			</table>
																		{/if}
																		{foreach key=date_format item=date_str from=$calsecondvalue.due_date}
																			{assign var=dateFormat value="$date_format"}
																			{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
																		{/foreach}
																		<script type="text/javascript">
																			{* crmv@82419 *}
																			(function() {ldelim}
																				setupDatePicker('jscal_field_due_date', {ldelim}
																					trigger: 'jscal_trigger_due_date',
																					date_format: "{$dateStr|strtoupper}",
																					language: "{$APP.LBL_JSCALENDAR_LANG}",
																				{rdelim});
																			{rdelim})();
																			{* crmv@82419e crmv@100585e *}
																		</script>
																	</div>
																</td></tr>
																<tbody id="time_event_end">
																<tr valign="bottom">
																	{if $PROCESSMAKER_MODE}
																		<td width="50%">
																			<label class="dvtCellLabel">
																			{if $MASS_EDIT eq '1'}
																				<input type="checkbox" name="time_end_mass_edit_check" id="time_end_mass_edit_check" class="small">
																				<label for="time_end_mass_edit_check">
																			{/if}
																			{'LBL_TIME'|getTranslatedString:'Calendar'}
																			{if $MASS_EDIT eq '1'}
																				</label>
																			{/if}
																			</label>
																			<div class="dvtCellInfo">
																				<select class="detailedViewTextBox" name="time_end_options" onChange="ActionTaskScript.calendarTimeOptions(this.value,'time_end')">
																					<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																					<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																					<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																				</select>
																			</div>
																		</td>
																	{/if}
																	<td><div id="time_end_custom" class="dvtCellInfo" {if $PROCESSMAKER_MODE}style="display:none"{/if}>{$ENDHOUR}</div></td>
																</tr>
																{if $PROCESSMAKER_MODE}
																	<tr>
																		<td colspan="2" nowrap>
																			<div class="editoptions" fieldname="time_end_opt_num" style="float:right; display:none"></div>
																		</td>
																	</tr>
																	<tr>
																		<td colspan="2" nowrap>
																			<div id="time_end_adv_options" class="dvtCellInfo" style="display:none">
																				<div style="float:left; width:10%; padding-right:10px">
																					<select class="detailedViewTextBox" name="time_end_opt_operator">
																						<option value="add">+</option>
																						<option value="sub">-</option>
																					</select>
																				</div>
																				<div style="float:left; width:30%; padding-right:5px">
																					<input type="text" class="detailedViewTextBox" name="time_end_opt_num">
																				</div>
																				<div style="float:left; padding-right:10px">
																					<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="time_end_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('time_end_opt_num')">more_horiz</i>
																				</div>
																				<div style="float:left; width:30%; padding-right:10px">
																					<select class="detailedViewTextBox" name="time_end_opt_unit">
																						<option value="hour">{'lbl_hours'|getTranslatedString:'ModComments'}</option>
																						<option value="minute">{'lbl_minutes'|getTranslatedString:'ModComments'}</option>
																					</select>
																				</div>
																			</div>
																		</td>
																	</tr>
																{/if}
																</tbody>
															</table>
														</td>
														<td width="100%" valign=top style="display:none;" id="date_table_thirdtd">
															<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
																<tr><td><div class="dvInnerHeader"><div class="dvInnerHeaderTitle"><input type="checkbox" name="followup"> {$MOD.LBL_HOLDFOLLOWUP}</div></div></td></tr>
																<tr><td><div class="dvtCellInfo">{$FOLLOWUP}</div></td></tr>
																<tr><td>
																	<div class="dvtCellInfo">
																		{foreach key=date_value item=time_value from=$ACTIVITYDATA.due_date}
																			{assign var=date_val value="$date_value"}
																			{assign var=time_val value="$time_value"}
																		{/foreach}
																		<table border="0" cellpadding="0" cellspacing="0">
																			<tr>
																				{* crmv@82419 crmv@100585 *}
																				<td>
																					<input type="text" name="followup_date" id="jscal_field_followup_date" class="form-control" value="{$date_val}">
																				</td>
																				<td style="padding-right:2px;">
																					<i class="vteicon md-link" id="jscal_trigger_followup_date" title="{$MOD.LBL_SET_DATE}">events</i>
																				</td>
																				{* crmv@82419e *}
																			</tr>
																		</table>
																		{foreach key=date_fmt item=date_str from=$calsecondvalue.due_date}
																			{assign var=date_vl value="$date_fmt"}
																			{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
																		{/foreach}
																		<script type="text/javascript">
																			{* crmv@82419 *}
																			(function() {ldelim}
																				setupDatePicker('jscal_field_followup_date', {ldelim}
																					trigger: 'jscal_trigger_followup_date',
																					date_format: "{$dateStr|strtoupper}",
																					language: "{$APP.LBL_JSCALENDAR_LANG}",
																				{rdelim});
																			{rdelim})();
																			{* crmv@82419e crmv@100585e *}
																		</script>
																	</div>
																</td></tr>
															</table>
														</td>
													</tr>
												<!-- crmv@31315 -->
												{* crmv@17001 crmv@153818 *}
													<tr><td colspan="3">
														{if $PROCESSMAKER_MODE}
														<label class="dvtCellLabel" for="is_all_day_event">{'All day'|getTranslatedString:$MODULE}</label>
														<div style="width:50%">
														{include file="EditViewUI.tpl" uitype=56 fldname="is_all_day_event" secondvalue="picklist" thirdvalue=$IS_ALL_DAY_THIRDVALUE}
														</div>
														{else}
														<input type="checkbox" id="is_all_day_event" name="is_all_day_event" onclick="presetAllDayEvent(this.checked);">
														<label for="is_all_day_event">{'All day'|getTranslatedString:$MODULE}</label>
														{/if}
													</td></tr>
												</table>
												{* crmv@17001e crmv@153818e *}
											</td>
											</tr>
										</table>
									</div>

									{assign var="LBL_CUSTOM_INFORMATION_TRANS" value=$APP.LBL_CUSTOM_INFORMATION}
									{if $CUSTOM_FIELDS_DATA|@count > 0 && $CUSTOM_FIELDS_DATA.$LBL_CUSTOM_INFORMATION_TRANS|@count > 0}
										<div class="vte-card">
											<table border=0 cellspacing=0 cellpadding=5 width=100%>
												<tr>
													<td colspan=4>
														<div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_CUSTOM_INFORMATION}</div></div>
													</td>
												</tr>
												{include file="DisplayFields.tpl"}
											</table>
										</div>
									{/if}
									
									<br>

									<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>
										<tr><td>
											<table border=0 cellspacing=0 cellpadding=3 width=100% class="vte-tabs">
												<tr>
													<td id="cellTabRelatedto" class="dvtSelectedCell" align=center nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','on');ghide('addEventAlarmUI');ghide('addEventInviteUI');gshow('addEventRelatedtoUI','',document.EditView.date_start.value,document.EditView.due_date.value,document.EditView.starthr.value,document.EditView.startmin.value,document.EditView.startfmt.value,document.EditView.endhr.value,document.EditView.endmin.value,document.EditView.endfmt.value);ghide('addEventRepeatUI');">{$MOD.LBL_RELATEDTO}</a></td>
													{if $HIDE_INVITE_TAB neq true}
														<td id="cellTabInvite" class="dvtUnSelectedCell" align=center nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','on');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','off');ghide('addEventAlarmUI');gshow('addEventInviteUI','',document.EditView.date_start.value,document.EditView.due_date.value,document.EditView.starthr.value,document.EditView.startmin.value,document.EditView.startfmt.value,document.EditView.endhr.value,document.EditView.endmin.value,document.EditView.endfmt.value);ghide('addEventRepeatUI');ghide('addEventRelatedtoUI');">{$MOD.LBL_INVITE}</a></td>
													{/if}
													{if $LABEL.reminder_time neq ''}
														<td id="cellTabAlarm" class="dvtUnSelectedCell" align=center nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','on');switchClass('cellTabRepeat','off');switchClass('cellTabRelatedto','off');gshow('addEventAlarmUI','',document.EditView.date_start.value,document.EditView.due_date.value,document.EditView.starthr.value,document.EditView.startmin.value,document.EditView.startfmt.value,document.EditView.endhr.value,document.EditView.endmin.value,document.EditView.endfmt.value);ghide('addEventInviteUI');ghide('addEventRepeatUI');ghide('addEventRelatedtoUI');">{$MOD.LBL_REMINDER}</a></td>
													{/if}
													{if $LABEL.recurringtype neq ''}
														<td id="cellTabRepeat" class="dvtUnSelectedCell" align=center nowrap><a href="javascript:doNothing()" onClick="switchClass('cellTabInvite','off');switchClass('cellTabAlarm','off');switchClass('cellTabRepeat','on');switchClass('cellTabRelatedto','off');ghide('addEventAlarmUI');ghide('addEventInviteUI');gshow('addEventRepeatUI','',document.EditView.date_start.value,document.EditView.due_date.value,document.EditView.starthr.value,document.EditView.startmin.value,document.EditView.startfmt.value,document.EditView.endhr.value,document.EditView.endmin.value,document.EditView.endfmt.value);ghide('addEventRelatedtoUI');">{$MOD.LBL_REPEAT}</a></td>
													{/if}
													<td class="dvtTabCache" style="width:100%">&nbsp;</td>
												</tr>
											</table>
										</td></tr>
										<tr>
											{* crmv@26807 *}
											<td width="100%" valign="top" align="center" class="dvtContentSpace" style="padding:10px;height:120px">
											<!-- Invite UI -->
												<div id="addEventInviteUI" class="vte-card" style="display:none;width:100%">
													{include file="modules/Calendar/EventInviteUI.tpl"}
												</div>
												{* crmv@26807e *}
												<!-- Reminder UI -->
												<DIV id="addEventAlarmUI" class="vte-card" style="display:none;width:100%">
												{if $LABEL.reminder_time neq ''}
													<table>
														{assign var=secondval value=$calsecondvalue.reminder_time}
														{assign var=check value=$secondval[0]}
														{assign var=yes_val value=$secondval[1]}
														{assign var=no_val value=$secondval[2]}

														<tr><td>{$LABEL.reminder_time}</td><td>
														<input type="radio" name="set_reminder" value="Yes" {$check} onClick="showBlock('reminderOptions')">&nbsp;{$yes_val}&nbsp;
														<input type="radio" name="set_reminder" value="No" onClick="fnhide('reminderOptions')">&nbsp;{$no_val}&nbsp;
														</td></tr>
													</table>
												{if $check eq 'CHECKED'}
													{assign var=reminstyle value='style="display:block;width:100%"'}
												{else}
													{assign var=reminstyle value='style="display:none;width:100%"'}
												{/if}
												<DIV id="reminderOptions" {$reminstyle}>
													<table border=0 cellspacing=0 cellpadding=2  width=100%>
														<tr>
															<td nowrap align=right width=20%><b>{$MOD.LBL_RMD_ON} : </b></td>
															<td width=80%>
																<table border=0>
																<tr>
																	{foreach item=val_arr from=$ACTIVITYDATA.reminder_time}
																	<td>
																	{assign var=start value=$val_arr[0]}
																	{assign var=end value=$val_arr[1]}
																	{assign var=sendname value=$val_arr[2]}
																	{assign var=disp_text value=$val_arr[3]}
																	{assign var=sel_val value=$val_arr[4]}
																	<select name="{$sendname}">
																	{section name=reminder start=$start max=$end loop=$end step=1 }
																	{if $smarty.section.reminder.index eq $sel_val}
																	{assign var=sel_value value="SELECTED"}
																	{else}
																	{assign var=sel_value value=""}
																	{/if}
																	<OPTION VALUE="{$smarty.section.reminder.index}" {$sel_value}>{$smarty.section.reminder.index}</OPTION>
																	{/section}
																	</select>
																	</td>
																	<td>{$disp_text}</td>
																	{/foreach}
																	<td colspan="3" align="center">{$MOD.LBL_BEFOREEVENT}</td> {* crmv@98866 *}
																</tr>
																</table>
															</td>
														</tr>
														<!--This is now required as of now, as we aree not allowing to change the email id
													and it is showing logged in User's email id, instead of Assigned to user's email id

														<tr>
															<td nowrap align=right>
																{$MOD.LBL_SDRMD}
															</td>
															<td >
																<input type=text name="toemail" readonly="readonly" class=textbox style="width:90%" value="{$USEREMAILID}">
															</td>
														</tr> -->
													</table>
												{/if}
												</DIV>
												</DIV>
												<!-- Repeat UI -->
												<div id="addEventRepeatUI" class="vte-card" style="display:none;width:100%">
												{if $LABEL.recurringtype neq ''}
												<table border=0 cellspacing=0 cellpadding=2  width=100%>
													<tr>
														<td nowrap align=right width=20% valign=top>
															<strong>{$MOD.LBL_REPEAT}</strong>
														</td>
														<td nowrap width=80% valign=top>
															<table border=0 cellspacing=0 cellpadding=0>
															<tr>

																<td width=20>
																{if $ACTIVITYDATA.recurringcheck eq 'Yes'}
																	{assign var=rptstyle value='style="display:block"'}
																	{if $ACTIVITYDATA.eventrecurringtype eq 'Daily'}
																		{assign var=rptmonthstyle value='style="display:none"'}
																		{assign var=rptweekstyle value='style="display:none"'}
																	{elseif $ACTIVITYDATA.eventrecurringtype eq 'Weekly'}
																		{assign var=rptmonthstyle value='style="display:none"'}
																		{assign var=rptweekstyle value='style="display:block"'}
																	{elseif $ACTIVITYDATA.eventrecurringtype eq 'Monthly'}
																		{assign var=rptmonthstyle value='style="display:block"'}
																		{assign var=rptweekstyle value='style="display:none"'}
																	{elseif $ACTIVITYDATA.eventrecurringtype eq 'Yearly'}
																		{assign var=rptmonthstyle value='style="display:none"'}
																		{assign var=rptweekstyle value='style="display:none"'}
																	{/if}
																<input type="checkbox" name="recurringcheck" onClick="showhideCalendar('repeatOptions')" checked> {* crmv@69922 *}
																{else}
																	{assign var=rptstyle value='style="display:none"'}
																	{assign var=rptmonthstyle value='style="display:none"'}
																	{assign var=rptweekstyle value='style="display:none"'}
																<input type="checkbox" name="recurringcheck" onClick="showhideCalendar('repeatOptions')"> {* crmv@69922 *}
																{/if}
																</td>
																<td>{$MOD.LBL_ENABLE_REPEAT}<td>
															</tr>
															<tr>
																<td colspan=2>
																<div id="repeatOptions" {$rptstyle}>
																<table border=0 cellspacing=0 cellpadding=2>
																<tr>
																<td>{$MOD.LBL_REPEAT_ONCE}</td>
																<td>
																	<select name="repeat_frequency">
																	{section name="repeat" loop=15 start=1 step=1}
																	{if $smarty.section.repeat.iteration eq $ACTIVITYDATA.repeat_frequency}
																		{assign var="test" value="selected"}
																	{else}
																		{assign var="test" value=""}                                                                                                                                                                                                                  
																	{/if}
																	<option {$test} value="{$smarty.section.repeat.iteration}">{$smarty.section.repeat.iteration}</option>
																	{/section}
																	</select>
																</td>
																<td>
																	<select name="recurringtype" onChange="rptoptDisp(this)">
																	<option value="Daily" {if $ACTIVITYDATA.eventrecurringtype eq 'Daily'} selected {/if}>{$MOD.LBL_DAYS}</option>
																	<option value="Weekly" {if $ACTIVITYDATA.eventrecurringtype eq 'Weekly'} selected {/if}>{$MOD.LBL_WEEKS}</option>
																	<option value="Monthly" {if $ACTIVITYDATA.eventrecurringtype eq 'Monthly'} selected {/if}>{$MOD.LBL_MONTHS}</option>
																	<option value="Yearly" {if $ACTIVITYDATA.eventrecurringtype eq 'Yearly'} selected {/if}>{$MOD.LBL_YEAR}</option>
																	</select>
																</td>
																<td>
																	<!-- Repeat Feature Enhanced -->
																	<b>{$MOD.LBL_UNTIL}</b> <input type="text" name="calendar_repeat_limit_date" id="calendar_repeat_limit_date" class="form-control" value="" >
																</td>
																{* crmv@100585 *}
																<td align="left"><i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_calendar_repeat_limit_date">events</i>
																<script type="text/javascript">
																(function() {ldelim}
																	setupDatePicker('calendar_repeat_limit_date', {ldelim}
																		trigger: 'jscal_trigger_calendar_repeat_limit_date',
																		date_format: "{$dateStr|strtoupper}",
																		language: "{$APP.LBL_JSCALENDAR_LANG}",
																	{rdelim});
																{rdelim})();
																</script>
																{* crmv@100585e *}
																</td>
															</tr>
															</table>
															<div id="repeatWeekUI" {$rptweekstyle}>
															<table border=0 cellspacing=0 cellpadding=2>
															<tr>
																{* crmv@183418 *}
																{if $WEEKSTART eq 0}
																	<td><input name="sun_flag" value="sunday" {$ACTIVITYDATA.week0} type="checkbox"></td><td>{$MOD.LBL_SM_SUN}</td>
																{/if}
																{* crmv@183418e *}
																<td><input name="mon_flag" value="monday" {$ACTIVITYDATA.week1} type="checkbox"></td><td>{$MOD.LBL_SM_MON}</td>
																<td><input name="tue_flag" value="tuesday" {$ACTIVITYDATA.week2} type="checkbox"></td><td>{$MOD.LBL_SM_TUE}</td>
																<td><input name="wed_flag" value="wednesday" {$ACTIVITYDATA.week3} type="checkbox"></td><td>{$MOD.LBL_SM_WED}</td>
																<td><input name="thu_flag" value="thursday" {$ACTIVITYDATA.week4} type="checkbox"></td><td>{$MOD.LBL_SM_THU}</td>
																<td><input name="fri_flag" value="friday" {$ACTIVITYDATA.week5} type="checkbox"></td><td>{$MOD.LBL_SM_FRI}</td>
																<td><input name="sat_flag" value="saturday" {$ACTIVITYDATA.week6} type="checkbox"></td><td>{$MOD.LBL_SM_SAT}</td>
																{* crmv@183418 *}
																{if $WEEKSTART eq 1}
																	<td><input name="sun_flag" value="sunday" {$ACTIVITYDATA.week0} type="checkbox"></td><td>{$MOD.LBL_SM_SUN}</td>
																{/if}
																{* crmv@183418e *}
															</tr>
															</table>
															</div>

															<div id="repeatMonthUI" {$rptmonthstyle}>
															<table border=0 cellspacing=0 cellpadding=2>
															<tr>
																<td>
																	<table border=0 cellspacing=0 cellpadding=2>
																	<tr>
																	<td><input type="radio" checked name="repeatMonth" {if $ACTIVITYDATA.repeatMonth eq 'date'} checked {/if} value="date"></td><td>{$MOD.on}</td><td><input type="text" class=textbox style="width:20px" value="{$ACTIVITYDATA.repeatMonth_date}" name="repeatMonth_date" ></td><td>{$MOD.DAY_OF_MONTH}</td>
																	</tr>
																	</table>
																</td>
															</tr>
															<tr>
																<td>
																	<table border=0 cellspacing=0 cellpadding=2>
																	<tr><td>
																	<input type="radio" name="repeatMonth" {if $ACTIVITYDATA.repeatMonth eq 'day'} checked {/if} value="day"></td>
																	<td>{$MOD.on}</td>
																	<td>
																	<select name="repeatMonth_daytype">
																		<option value="first" {if $ACTIVITYDATA.repeatMonth_daytype eq 'first'} selected {/if}>{$MOD.First}</option>
																		<option value="last" {if $ACTIVITYDATA.repeatMonth_daytype eq 'last'} selected {/if}>{$MOD.Last}</option>
																	</select>
																	</td>
																	<td>
																	<select name="repeatMonth_day">
																		<option value=1 {if $ACTIVITYDATA.repeatMonth_day eq 1} selected {/if}>{$MOD.LBL_DAY1}</option>
																		<option value=2 {if $ACTIVITYDATA.repeatMonth_day eq 2} selected {/if}>{$MOD.LBL_DAY2}</option>
																		<option value=3 {if $ACTIVITYDATA.repeatMonth_day eq 3} selected {/if}>{$MOD.LBL_DAY3}</option>
																		<option value=4 {if $ACTIVITYDATA.repeatMonth_day eq 4} selected {/if}>{$MOD.LBL_DAY4}</option>
																		<option value=5 {if $ACTIVITYDATA.repeatMonth_day eq 5} selected {/if}>{$MOD.LBL_DAY5}</option>
																		<option value=6 {if $ACTIVITYDATA.repeatMonth_day eq 6} selected {/if}>{$MOD.LBL_DAY6}</option>
																	</select>
																	</td>
																	</tr>
																	</table>
																</td>
															</tr>
															</table>
															</div>

														</div>
													</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									{/if}
									</div>
									<div id="addEventRelatedtoUI" class="vte-card" style="display:block;width:100%">
									<table width="100%" cellpadding="5" cellspacing="0" border="0">
										{* crmv@29190 *}
									{* crmv@42247 *}
									{if $LABEL.parent_id neq ''}
									<tr>
										<td>
											{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="parent_id" field_type_name="parent_type"} {* crmv@171575 *}
										</td>
									</tr>
									{/if}
									{* crmv@42247e *}
										{if $HIDE_REFERENCE_CONTACT_FIELD neq true}
										<tr valign="top">
											<td>
												{include file="FieldHeader.tpl" label=$APP.Contacts massedit=$MASS_EDIT} {* crmv@171575 *}
												<table cellpadding="0" cellspacing="0" width="100%">
												<tr>
													<td>
														<div class="dvtCellInfo">
															<input type="text" id="multi_contact_autocomplete" class="detailedViewTextBox" value="{'LBL_SEARCH_STRING'|getTranslatedString}">
														</div>
													</td>
													<td>
														<i class="vteicon md-link" onclick="openPopup('index.php?'+selectContact('true','general',document.EditView));" title="{$APP.LBL_SELECT}">view_list</i>	<!-- crmv@29190 -->
													</td>
												</tr>
												<tr valign="top">
													<td style="padding-top: 5px;">
														<script type="text/javascript">
														var empty_search_str = "{'LBL_SEARCH_STRING'|getTranslatedString}";
														initMultiContactAutocomplete('multi_contact_autocomplete','ActivityEditView',encodeURIComponent('module=Contacts&action=Popup&html=Popup_picker&form=EditView&return_module=Calendar&select=enable&popuptype=detailview&form_submit=false'));
														</script>
														<input name="contactidlist" id="contactidlist" value="{$CONTACTSID}" type="hidden">
														<input name="deletecntlist" id="deletecntlist" type="hidden">
														<div class="dvtCellInfo">
															<select name="contactlist" size=5 class="detailedViewTextBox" style="height: 100px;" id="parentid" multiple>
															{$CONTACTSNAME}
															</select>
														</div>
													</td>
													<td align="left" width="20px" style="padding:5px;">
														<i class="vteicon md-link" onclick="removeActContacts();" title="{$APP.LBL_CLEAR}">highlight_off</i>
													</td>
												</tr>
												</table>
											</td>
										</tr>
										{/if}
										{* crmv@29190e *}
									</table>
								</div>
						</td>
					</tr>
					</table>
					<!-- Alarm, Repeat, Invite stops-->
					{else}
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td colspan="2">
								{include file="FieldHeader.tpl" label=$MOD.LBL_TODO fldname="subject" massedit=$MASS_EDIT}
								<div class="dvtCellInfo">
									<input name="subject" value="{$ACTIVITYDATA.subject}" class="detailedViewTextBox" type="text">
								</div>
							</td>
						</tr>
						{if $LABEL.description != ''}
						<tr>
							<td colspan="2">
								{include file="FieldHeader.tpl" label=$LABEL.description fldname="description" massedit=$MASS_EDIT}
								<div class="dvtCellInfo">
									<textarea style="height: 60px;" name="description" class="detailedViewTextBox">{$ACTIVITYDATA.description}</textarea>
								</div>
							</td>
						</tr>
						{/if}
						<tr>
							<td width="50%">
								{* crmv@187674 *}
								{if $LABEL.taskstatus != ''}
									{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="taskstatus"}
								{/if}
								{* crmv@187674e *}
							</td>
							<td width="50%">
								{* crmv@187674 *}
								{if $LABEL.taskpriority != ''}
									{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="taskpriority"}
								{/if}
								{* crmv@187674e *}
							</td>
						</tr>
						{if $LABEL.assigned_user_id != ''}
							<tr>
								<td width="50%">
									{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="assigned_user_id"} {* crmv@158871 crmv@171575 *}
								</td>
								<td width="50%"></td>
							</tr>
						{else}
							<input name="assigned_user_id" value="{$CURRENTUSERID}" type="hidden">
						{/if}
						</table>
						</div>

						<div class="vte-card">
							<table border="0" cellpadding="0" cellspacing="1" width="100%" align=center>
							<tr>
								<td width=50% valign=top>
									<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
										<tr><td colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$MOD.LBL_TODODATETIME}</div></div></td></tr>
										<tr><td colspan="2" nowrap>
											<div class="dvtCellInfo">
												{foreach key=date_value item=time_value from=$ACTIVITYDATA.date_start}
													{assign var=date_val value="$date_value"}
													{assign var=time_val value="$time_value"}
												{/foreach}
												{if $PROCESSMAKER_MODE}
													<table border="0" cellpadding="0" cellspacing="0" width="100%">
														<tr valign="bottom">
															<td width="50%">
																<label class="dvtCellLabel">
																{if $MASS_EDIT eq '1'}
																	<input type="checkbox" name="date_start_mass_edit_check" id="date_start_mass_edit_check" class="small">
																	<label for="date_start_mass_edit_check">
																{/if}
																{'LBL_LIST_DATE'|getTranslatedString:'Calendar'}
																{if $MASS_EDIT eq '1'}
																	</label>
																{/if}
																</label>
																<div class="dvtCellInfo">
																	<select class="detailedViewTextBox" name="date_start_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'date_start')">
																		<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																		<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																		<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																	</select>
																</div>
															</td>
															{* crmv@82419 crmv@100585 *}
															<td>
																<input name="date_start" id="date_start" class="form-control" onChange="dochange('date_start','due_date');" value="{$date_val}" type="text" style="display:none">
															</td>
															<td style="padding-right:2px;">
																<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_date_start" style="display:none">events</i>
															</td>
															{* crmv@82419e *}
														</tr>
														<tr>
															<td colspan="3" nowrap>
																<div class="editoptions" fieldname="date_start_opt_num" style="float:right; display:none"></div>
															</td>
														</tr>
														<tr>
															<td colspan="3" nowrap>
																<div id="date_start_adv_options" style="display:none">
																	<div style="float:left; width:10%; padding-right:10px">
																		<select class="detailedViewTextBox" name="date_start_opt_operator">
																			<option value="add">+</option>
																			<option value="sub">-</option>
																		</select>
																	</div>
																	<div style="float:left; width:30%; padding-right:5px">
																		<input type="text" name="date_start_opt_num" class="form-control">
																	</div>
																	<div style="float:left; padding-right:10px">
																		<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="date_start_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('date_start_opt_num')">more_horiz</i>
																	</div>
																	<div style="float:left; width:30%; padding-right:10px">
																		<select class="detailedViewTextBox" name="date_start_opt_unit">
																			<option value="day">{'lbl_days'|getTranslatedString:'ModComments'}</option>
																			<option value="month">{'lbl_months'|getTranslatedString:'ModComments'}</option>
																			<option value="year">{'lbl_years'|getTranslatedString:'ModComments'}</option>
																		</select>
																	</div>
																</div>
															</td>
														</tr>
													</table>
												{else}
													<table border="0" cellpadding="0" cellspacing="0">
														<tr>
															{* crmv@82419 crmv@100585 *}
															<td>
																<input name="date_start" id="date_start" class="form-control" onChange="dochange('date_start','due_date');" value="{$date_val}" type="text">
															</td>
															<td style="padding-right:2px;">
																<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_date_start">events</i>
															</td>
															{* crmv@82419e *}
														</tr>
													</table>
												{/if}
												{foreach key=date_fmt item=date_str from=$calsecondvalue.date_start}
													{assign var=date_vl value="$date_fmt"}
													{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
												{/foreach}
												<script type="text/javascript">
													{* crmv@82419 *}
													(function() {ldelim}
														setupDatePicker('date_start', {ldelim}
															trigger: 'jscal_trigger_date_start',
															date_format: "{$dateStr|strtoupper}",
															language: "{$APP.LBL_JSCALENDAR_LANG}",
														{rdelim});
													{rdelim})();
													{* crmv@82419e crmv@100585e *}
												</script>
											</div>
										</td></tr>
										<tbody id="time_event_start">
										<tr valign="bottom">
											{if $PROCESSMAKER_MODE}
												<td width="50%">
													<label class="dvtCellLabel">
													{if $MASS_EDIT eq '1'}
														<input type="checkbox" name="time_start_mass_edit_check" id="time_start_mass_edit_check" class="small">
														<label for="time_start_mass_edit_check">
													{/if}
													{'LBL_TIME'|getTranslatedString:'Calendar'}
													{if $MASS_EDIT eq '1'}
														</label>
													{/if}
													</label>
													<div class="dvtCellInfo">
														<select class="detailedViewTextBox" name="time_start_options" onChange="ActionTaskScript.calendarTimeOptions(this.value,'time_start')">
															<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
															<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
															<option value="now">{'LBL_NOW'|getTranslatedString}</option>
														</select>
													</div>
												</td>
											{/if}
											<td><div id="time_start_custom" class="dvtCellInfo" {if $PROCESSMAKER_MODE}style="display:none"{/if}>{$STARTHOUR}</div></td>
										</tr>
										{if $PROCESSMAKER_MODE}
											<tr>
												<td colspan="2" nowrap>
													<div class="editoptions" fieldname="time_start_opt_num" style="float:right; display:none"></div>
												</td>
											</tr>
											<tr>
												<td colspan="2" nowrap>
													<div id="time_start_adv_options" class="dvtCellInfo" style="display:none">
														<div style="float:left; width:10%; padding-right:10px">
															<select class="detailedViewTextBox" name="time_start_opt_operator">
																<option value="add">+</option>
																<option value="sub">-</option>
															</select>
														</div>
														<div style="float:left; width:30%; padding-right:5px">
															<input type="text" class="detailedViewTextBox" name="time_start_opt_num">
														</div>
														<div style="float:left; padding-right:10px">
															<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="time_start_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('time_start_opt_num')">more_horiz</i>
														</div>
														<div style="float:left; width:30%; padding-right:10px">
															<select class="detailedViewTextBox" name="time_start_opt_unit">
																<option value="hour">{'lbl_hours'|getTranslatedString:'ModComments'}</option>
																<option value="minute">{'lbl_minutes'|getTranslatedString:'ModComments'}</option>
															</select>
														</div>
													</div>
												</td>
											</tr>
										{/if}
										</tbody>
									</table>
								</td>
								
								<td width=50% valign="top">
									<table border="0" cellpadding="2" cellspacing="0" width="100%" align=center>
										<tr><td colspan="2"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$LABEL.due_date}</div></div></td></tr>
										<tr><td colspan="2">
											<div class="dvtCellInfo">
												{foreach key=date_value item=time_value from=$ACTIVITYDATA.due_date}
													{assign var=date_val value="$date_value"}
													{assign var=time_val value="$time_value"}
												{/foreach}
												{if $PROCESSMAKER_MODE}
													<table border="0" cellpadding="0" cellspacing="0" width="100%">
														<tr valign="bottom">
															<td width="50%">
																<label class="dvtCellLabel">
																{if $MASS_EDIT eq '1'}
																	<input type="checkbox" name="due_date_mass_edit_check" id="due_date_mass_edit_check" class="small">
																	<label for="due_date_mass_edit_check">
																{/if}
																{'LBL_LIST_DATE'|getTranslatedString:'Calendar'}
																{if $MASS_EDIT eq '1'}
																	</label>
																{/if}
																</label>
																<div class="dvtCellInfo">
																	<select class="detailedViewTextBox" name="due_date_options" onChange="ActionTaskScript.calendarDateOptions(this.value,'due_date')">
																		<option value="">{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}</option>
																		<option value="custom">{'Custom'|getTranslatedString:'CustomView'}</option>
																		<option value="now">{'LBL_NOW'|getTranslatedString}</option>
																	</select>
																</div>
															</td>
															{* crmv@82419 crmv@100585 *}
															<td>
																<input name="due_date" id="due_date" class="form-control" value="{$date_val}" type="text" style="display:none">
															</td>
															<td style="padding-right:2px;">
																<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_due_date" style="display:none">events</i>
															</td>
															{* crmv@82419e *}
														</tr>
														<tr>
															<td colspan="3" nowrap>
																<div class="editoptions" fieldname="due_date_opt_num" style="float:right; display:none"></div>
															</td>
														</tr>
														<tr>
															<td colspan="3" nowrap>
																<div id="due_date_adv_options" style="display:none">
																	<div style="float:left; width:10%; padding-right:10px">
																		<select class="detailedViewTextBox" name="due_date_opt_operator">
																			<option value="add">+</option>
																			<option value="sub">-</option>
																		</select>
																	</div>
																	<div style="float:left; width:30%; padding-right:5px">
																		<input type="text" name="due_date_opt_num" class="form-control">
																	</div>
																	<div style="float:left; padding-right:10px">
																		<i class="vteicon md-link" title="{'LBL_SELECT_OPTION_DOTDOTDOT'|getTranslatedString:'com_workflow'}" id="due_date_opt_num_editoptions_more" onclick="ActionTaskScript.toggleFieldEditOptions('due_date_opt_num')">more_horiz</i>
																	</div>
																	<div style="float:left; width:30%; padding-right:10px">
																		<select class="detailedViewTextBox" name="due_date_opt_unit">
																			<option value="day">{'lbl_days'|getTranslatedString:'ModComments'}</option>
																			<option value="month">{'lbl_months'|getTranslatedString:'ModComments'}</option>
																			<option value="year">{'lbl_years'|getTranslatedString:'ModComments'}</option>
																		</select>
																	</div>
																</div>
															</td>
														</tr>
													</table>
												{else}
													<table border="0" cellpadding="0" cellspacing="0">
														<tr>
															{* crmv@82419 crmv@100585 *}
															<td>
																<input name="due_date" id="due_date" class="form-control" value="{$date_val}" type="text">
															</td>
															<td style="padding-right:2px;">
																<i class="vteicon md-link" title="{$MOD.LBL_SET_DATE}" id="jscal_trigger_due_date">events</i>
															</td>
															{* crmv@82419e crmv@100585e *}
														</tr>
													</table>
												{/if}
												{foreach key=date_fmt item=date_str from=$calsecondvalue.due_date}
													{assign var=date_vl value="$date_fmt"}
													{assign var=dateStr value="$date_str"|substr:0:10}	{* crmv@82419 *}
												{/foreach}
												<script type="text/javascript">
												{* crmv@82419 *}
												(function() {ldelim}
													setupDatePicker('due_date', {ldelim}
														trigger: 'jscal_trigger_due_date',
														date_format: "{$dateStr|strtoupper}",
														language: "{$APP.LBL_JSCALENDAR_LANG}",
													{rdelim});
												{rdelim})();
												{* crmv@82419e *}
												</script>
											</div>
										</td></tr>
									</table>
								</td>
							</tr>
							</table>
						</div>

					<div class="vte-card">
						{* crmv@36871 *}
						<table border=0 cellspacing=0 cellpadding=2 width=100% align=center>
							<tr><td align=left>
								<div class="dvInnerHeader">
									{if $MASS_EDIT eq '1'}
										<input type="checkbox" name="exp_duration_mass_edit_check" id="exp_duration_mass_edit_check" class="small">
										<label for="exp_duration_mass_edit_check">&nbsp;
									{/if}
									<div class="dvInnerHeaderTitle">{$MOD.ExpDuration}</div>
									{if $MASS_EDIT eq '1'}
										</label>
									{/if}
								</div>
							</td></tr>
						</table>
						<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
							<tr>
								<td width=50%>
									<div class="dvtCellInfo">
										{if count($EXPDURATIONPLIST) > 0}
											<select id="exp_duration" name="exp_duration" class="detailedViewTextBox">
												{foreach item=plabel key=pkey from=$EXPDURATIONPLIST}
													<option value="{$pkey}" {if $pkey eq $ACTIVITYDATA.exp_duration}selected="selected"{/if}>{$plabel}</option>
												{/foreach}
											</select>
										{/if}
									</div>
								</td>
								<td width=50%></td>
							</tr>
						</table>
						{* crmv@36871e *}
					</div>

					{assign var="LBL_CUSTOM_INFORMATION_TRANS" value=$APP.LBL_CUSTOM_INFORMATION}
					{if $CUSTOM_FIELDS_DATA|@count > 0 && $CUSTOM_FIELDS_DATA.$LBL_CUSTOM_INFORMATION_TRANS|@count > 0}
						<div class="vte-card">
							<table border=0 cellspacing=0 cellpadding=5 width=100% >
								<tr>{strip}
									<td colspan=4>
									<div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$APP.LBL_CUSTOM_INFORMATION}</div></div>
									</td>{/strip}
								</tr>
								{assign var=data value=$CUSTOM_FIELDS_DATA}
								{include file="DisplayFields.tpl"}
							</table>
						</div>
					{/if}
					
					<br>

					{if $LABEL.parent_id neq '' || $LABEL.contact_id neq ''}
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td>
									<table border="0" cellpadding="3" cellspacing="0" width="100%" class="vte-tabs">
										<tr>
											{if ($LABEL.parent_id neq '') || ($LABEL.contact_id neq '') }
												<td id="cellTabRelatedto" class="dvtSelectedCell" align=center nowrap><a href="javascript:doNothing()">{$MOD.LBL_RELATEDTO}</a></td>
											{/if}
											<td class="dvtTabCache" style="width:100%">&nbsp;</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="dvtContentSpace" style="padding: 10px; height: 120px;" align="left" valign="top" width="100%">
							<!-- Reminder UI -->
							<div id="addTaskRelatedtoUI" class="vte-card" style="display:block;width:100%">
								<table width="100%" cellpadding="5" cellspacing="0" border="0">
								{* crmv@29190 *}
								{* crmv@42247 *}
								{if $LABEL.parent_id neq ''}
								<tr>
									<td>
										{include file="modules/Calendar/ActivityEditViewField.tpl" FIELDNAME="parent_id" field_type_name="parent_type"} {* crmv@171575 *}
									</td>
								</tr>
								{/if}
								{if $LABEL.contact_id neq ''}
								<tr>
									<td>
										{include file="FieldHeader.tpl" label=$LABEL.contact_id.displaylabel massedit=$MASS_EDIT} {* crmv@171575 *}
										{* crmv@97221 *}
										{assign var=fld_displayvalue value=$ACTIVITYDATA.contact_id.displayvalue}
										<div {if $fld_displayvalue|trim eq ''}class="dvtCellInfo"{else}class="dvtCellInfoOff"{/if} style="position:relative">
											{assign var=fld_style value='class="detailedViewTextBox" readonly'}
											{if $fld_displayvalue|trim eq ''}
												{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
												{assign var=fld_style value='class="detailedViewTextBox"'}
											{/if}
											<input name="contact_name" id="contact_name" type="text" {$fld_style} value="{$fld_displayvalue}" style="width: 63%;">
											<input name="contact_id" type="hidden" value="{$ACTIVITYDATA.contact_id.entityid}">
											<input name="deletecntlist" id="deletecntlist" type="hidden">
											<script type="text/javascript">
											reloadAutocomplete('contact_id','contact_name',selectContact('false','task',document.EditView,'yes'));
											</script>
											<div class="dvtCellInfoImgRx">
												<i class="vteicon md-link" title="{$APP.LBL_SELECT}" onclick="openPopup('index.php?'+selectContact('false','task',document.EditView));">view_list</i>
												<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onclick="document.EditView.deletecntlist.value=document.EditView.contact_id.value;document.EditView.contact_name.value='';document.EditView.contact_id.value='';enableReferenceField(document.EditView.contact_name);">highlight_off</i>
											</div>
										</div>
										{* crmv@97221e *}
									</td>
								</tr>
								{/if}
								{* crmv@42247e *}
								{* crmv@29190e *}
						</table>
					{/if}
					
					</div>
					</td></tr></table>
				{/if}
				
				</td></tr>
				</table>
				</td></tr></table>
				</td></tr></table>
				</td></tr></table>
					
				<input name='search_url' id="search_url" type='hidden' value='{$SEARCH}'>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	{* crmv@95751 crmv@112297 *}
	var fieldname = {$VALIDATION_DATA_FIELDNAME};
	var fieldlabel = {$VALIDATION_DATA_FIELDLABEL};
	var fielddatatype = {$VALIDATION_DATA_FIELDDATATYPE};
	var fielduitype = {$VALIDATION_DATA_FIELDUITYPE};
	var fieldwstype = {$VALIDATION_DATA_FIELDWSTYPE};
	
	{if $ACTIVITY_MODE eq 'Task'}
	var fieldnameTaskCustom = {$VALIDATION_DATA_CUS_FIELDNAME};
	var fieldlabelTaskCustom = {$VALIDATION_DATA_CUS_FIELDLABEL};
	var fielddatatypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDDATATYPE};
	var fielduitypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDUITYPE};
	var fieldwstypeTaskCustom = {$VALIDATION_DATA_CUS_FIELDWSTYPE};
	{else}
	var fieldnameCustom = {$VALIDATION_DATA_CUS_FIELDNAME};
	var fieldlabelCustom = {$VALIDATION_DATA_CUS_FIELDLABEL};
	var fielddatatypeCustom = {$VALIDATION_DATA_CUS_FIELDDATATYPE};
	var fielduitypeCustom = {$VALIDATION_DATA_CUS_FIELDUITYPE};
	var fieldwstypeCustom = {$VALIDATION_DATA_CUS_FIELDWSTYPE};
	{/if}
	{* crmv@95751e crmv@112297e *}

	var ProductImages=new Array();
	var count=0;

	function delRowEmt(imagename)
	{ldelim}
		ProductImages[count++]=imagename;
	{rdelim}

	function displaydeleted()
	{ldelim}
		var imagelists='';
		for(var x = 0; x < ProductImages.length; x++)
		{ldelim}
			imagelists+=ProductImages[x]+'###';
		{rdelim}

		if(imagelists != '')
			document.EditView.imagelist.value=imagelists
	{rdelim}

{* crmv@17001 *}
{if $ACTIVITY_MODE neq 'Task'}
	setAllDayEvent({$ACTIVITYDATA.is_all_day_event});
{/if}
{* crmv@17001e *}

{* crmv@105416 *}
{literal}
jQuery(document).ready(function() {
	jQuery('form[name=EditView]').submit(function(e) {
		var activityMode = "{/literal}{$ACTIVITY_MODE}{literal}";
		if (activityMode != 'Task') {
			if (check_form() && formValidate(this)) { 
				return true;
			}
		} else {
			if (maintask_check_form() && formValidate(this)) {
				return true;
			}
		}
		VteJS_DialogBox.unblock();
		e.preventDefault();
	});
});
{/literal}
{* crmv@105416e *}

</script>

{include file="modules/Processes/InitEditViewConditionals.tpl"} {* crmv@112297 *}