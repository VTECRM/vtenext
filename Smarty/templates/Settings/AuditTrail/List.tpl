{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@202301 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/Settings/resources/AuditTrail.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="AuditTrail" id="form" onsubmit="VteJS_DialogBox.block();">
					<input type='hidden' name='module' value='Settings'>
					<input type='hidden' name='action' value='AuditTrail'>
					<input type='hidden' name='return_action' value='ListView'>
					<input type='hidden' name='return_module' value='Settings'>
					<input type='hidden' name='parenttab' value='Settings'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				
					<!-- DISPLAY -->
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'audit.gif'|resourcever}" alt="{$MOD.LBL_AUDIT_TRAIL}" width="48" height="48" border=0 title="{$MOD.LBL_AUDIT_TRAIL}"></td>
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_AUDIT_TRAIL}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_AUDIT_TRAIL_DESC}</td>
						</tr>
					</table>
				
					<br>

					<table border=0 cellspacing=0 cellpadding=10 width=100%>
						<tr>
							<td>
								<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
									<tr>
										<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_AUDIT_TRAIL}</strong></td>
									</tr>
								</table>
				
								<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
									<tr>
										<td valign=top>
											<table width="100%"  border="0" cellspacing="0" cellpadding="5">
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_ENABLE_AUDIT_TRAIL} </strong></td>
													<td width="80%" class="cellText">
														{if $AuditStatus eq 'enabled'}
															<input type="checkbox" checked name="enable_audit" onclick="VTE.Settings.AuditTrail.auditenabled(this)" />
														{else}
															<input type="checkbox" name="enable_audit" onclick="VTE.Settings.AuditTrail.auditenabled(this)" />
														{/if}
													</td>
												</tr>
												<tr valign="top">
													<td nowrap class="cellLabel"><strong>{$MOD.LBL_USER_AUDIT}</strong></td>
													<td class="cellText">
														<select name="user_list" id="user_list" class="detailedViewTextBox input-inline">
															<option value="" selected="">{$APP.LBL_NONE}</option>
															{$USERLIST}
														</select>	
													</td>
													<!-- td class="cellText" align=right nowrap -->
														<!-- button class="crmbutton edit" onclick="VTE.Settings.AuditTrail.exportAuditTrail();" type="button" name="button">{$MOD.LBL_EXPORT_AUDIT_TRAIL}</button --> {* crmv@164355 *}
														<!-- button class="crmbutton edit" onclick="VTE.Settings.AuditTrail.showAuditTrail();" type="button" name="button">{$MOD.LBL_VIEW_AUDIT_TRAIL}</button -->
													<!-- /td -->
												</tr>
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{'TIME_INTERVAL'|getTranslatedString:'Reports'} </strong></td>
													<td width="80%" class="cellText">
														<table width="100%" border="0">
														<tr>
															<td></td>
															<td width="20"></td>
															<td class="dvtCellLabel">{$APP.LBL_START_DATE}</td>
															<td class="dvtCellLabel">{$APP.LBL_END_DATE}</td>
														</tr>
														<tr><td>
															<select id="interval_selector" class="detailedViewTextBox input-inline" onchange="changeDateRangePicklist(jQuery(this).val())"></select>
														</td>
														<td></td>
														<td>
														
															<table border=0 cellspacing=0 cellpadding=2 style="display:inline-block">
																<tr>
																	{* crmv@100585 *}
																	<td align=left><div class="dvtCellInfo"><input name="startdate" id="jscal_field_date_start" type="text" class="detailedViewTextBox" value="{$STDFILTER.startdate}"></div></td>
																	<td align=left nowrap><i class="vteicon md-link md-text" id="jscal_trigger_date_start">events</i><font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
																		<script type="text/javascript">
																			(function() {ldelim}
																				setupDatePicker('jscal_field_date_start', {ldelim}
																					trigger: 'jscal_trigger_date_start',
																					date_format: "{$DATEFORMAT|strtoupper}",
																					language: "{$APP.LBL_JSCALENDAR_LANG}",
																				{rdelim});
																			{rdelim})();
																		</script>
																	</td>
																</tr>
															</table>
														</td>														
														<td>

															<table border=0 cellspacing=0 cellpadding=2 style="display:inline-block">
																<tr>
																	{* crmv@100585 *}
																	<td align=left><div class="dvtCellInfo"><input name="enddate" id="jscal_field_date_end" type="text" class="detailedViewTextBox" value="{$STDFILTER.enddate}"></div></td>
																	<td align=left nowrap><i class="vteicon md-link md-text" id="jscal_trigger_date_end">events</i><font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
																		<script type="text/javascript">
																			(function() {ldelim}
																				setupDatePicker('jscal_field_date_end', {ldelim}
																					trigger: 'jscal_trigger_date_end',
																					date_format: "{$DATEFORMAT|strtoupper}",
																					language: "{$APP.LBL_JSCALENDAR_LANG}",
																				{rdelim});
																			{rdelim})();
																		</script>
												
																	</td>
																</tr>
															</table>

													</td></tr>
													</table>
							
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
								
								<br>
								<div class="text-center">
									<button class="crmbutton edit" onclick="VTE.Settings.AuditTrail.showAuditTrail();" type="button" name="button">{$MOD.LBL_VIEW_AUDIT_TRAIL}</button>
									&nbsp;&nbsp;
									<button class="crmbutton edit" onclick="VTE.Settings.AuditTrail.exportAuditTrail();" type="button" name="button">{$MOD.LBL_EXPORT_AUDIT_TRAIL}</button> {* crmv@164355 *}
								</div>
								<br>
								<div id="AuditTrailContents">
								</div>
								
							</td>
						</tr>
					</table>

					{* SetMenu.tpl *}
					</td>
					</tr>
					</table>
					</td>
					</tr>
					</table>
				</form>
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>

{* crmv@203590 *}
{* file chooser includes *}
<link href="include/js/jquery_plugins/fileTree/jqueryFileTree.css" rel="stylesheet" type="text/css" media="screen" />
<script src="include/js/jquery_plugins/fileTree/jqueryFileTree.js" type="text/javascript"></script>

{* file chooser div *}
<div id="logs_div_filechooser" class="crmvDiv floatingDiv" style="z-index:100009;width:500px;height:400px;display:none">
	<input type="hidden" id="mmaker_logs_moduleid" value=""/>
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr height="34">
			<td class="level3Bg floatingHandle">
				<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="50%"><b>{$MOD.LBL_CHOOSE_AUDIT_LOG_TITLE}</b></td>
						<td width="50%" align="right">&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<div class="crmvDivContent" style="padding:10px">
		<p>{$MOD.LBL_CHOOSE_AUDIT_LOG_DESC}</p>
		<div class="" id="fileChooserTree"
			 style="width:480px;height:300px;overflow-y:scroll;overflow-x:hidden"></div>
	</div>
	<div class="closebutton" onclick="VTE.Settings.AuditTrail.hideFloatingDiv('logs_div_filechooser')"></div>
</div>
{* crmv@203590e *}

<script type="text/javascript">
	var time_intervals = {$TIME_INTERVALS_JS};
	var audit_log_interval = {$AUDIT_LOG_INTERVAL}; {*//crmv@203590*}
	VTE.Settings.AuditTrail.initTimeIntervals();
	function getListViewEntries_js(module, url) {
		VTE.Settings.AuditTrail.getListViewEntries_js(module, url);
	}
</script>

{* crmv@204903 *}
<script type="text/javascript">
	var userid = {$UID};
	jQuery( document ).ready(function() {
		if(userid.length !== 0)
		{
			jQuery("select[name=user_list]").val(userid).change();
			VTE.Settings.AuditTrail.showAuditTrail();
		}
	});
</script>
{* crmv@204903e *}