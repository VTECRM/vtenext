{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/PBXManager/PBXManager.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" id="form">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type='hidden' name='module' value='Users'>
					<input type='hidden' name='action' value='DefModuleView'>
					<input type='hidden' name='return_action' value='ListView'>
					<input type='hidden' name='return_module' value='Users'>
					<input type='hidden' name='parenttab' value='Settings'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'ogasteriskserver.gif'|resourcever}" alt="{$MOD.LBL_SOFTPHONE_SERVER_SETTINGS}" width="48" height="48" border=0 title="{$MOD.LBL_SOFTPHONE_SERVER_SETTINGS}"></td>	{* crmv@22660 *}
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_SOFTPHONE_SERVER_SETTINGS}</b></td> <!-- crmv@30683 -->
						</tr> 
						<tr>
							<td valign=top>{$MOD.LBL_SOFTPHONE_SERVER_SETTINGS_DESCRIPTION}</td>
						</tr>
						<tr>
							<td valign="top">
								{$ERROR}
							</td>
						</tr>
					</table>
					
					<br>
					<table border=0 cellspacing=0 cellpadding=10 width=100%>
						<tr>
							<td>
								<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
									<tr>
										<td width='70%'>
										<table border=0 cellspacing=0 cellpadding=5 width=100%>
											<tr>
												<td id='asterisk' class="big" height="20px;" width="75%">
													<strong>{$MOD.ASTERISK_CONFIGURATION}</strong>
												</td>
												<!-- for now only asterisk is there :: later we can add a dropdown here and add settings for all -->
											</tr>
										</table>
										</td>
									</tr>
								</table>

								<div id="AsteriskCustomization">
									<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
										<tr>
											<td valign=top>
											<table width="100%" border="0" cellspacing="0" cellpadding="5">
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.ASTERISK_SERVER_IP}</strong></td>
													<td width="80%" class="cellText">
														<div class="dvtCellInfo">
															<input type="text" id="asterisk_server_ip" name="asterisk_server_ip" class="detailedViewTextBox" style="width:30%" value="{$ASTERISK_SERVER_IP}" title="{$MOD.ASTERISK_SERVER_IP_TITLE}"/>
														</div>
													</td>
												</tr>
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.ASTERISK_PORT}</strong></td>
													<td width="80%" class="cellText">
														<div class="dvtCellInfo">
															<input type="text" id="asterisk_port" name="asterisk_port" class="detailedViewTextBox" style="width:30%" value="{$ASTERISK_PORT}" title="{$MOD.ASTERISK_PORT_TITLE}"/>
														</div>
													</td>
												</tr>
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.ASTERISK_USERNAME}</strong></td>
													<td width="80%" class="cellText">
														<div class="dvtCellInfo">
															<input type="text" id="asterisk_username" name="asterisk_username" class="detailedViewTextBox" style="width:30%" value="{$ASTERISK_USERNAME}" title="{$MOD.ASTERISK_USERNAME_TITLE}"/>
														</div>
													</td>
												</tr>
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.ASTERISK_PASSWORD}</strong></td>
													<td width="80%" class="cellText">
														<div class="dvtCellInfo">
															{* crmv@43764 *}
															<input type="password" value="{if !empty($ASTERISK_PASSWORD)}********{/if}" class="detailedViewTextBox" style="width:30%" onFocus="this.value='';" onChange="document.getElementById('asterisk_password').value=this.value;" />
															<input type="hidden" id="asterisk_password" name="asterisk_password" value="">
															{* crmv@43764e *}
														</div>
													</td>
												</tr>
												<tr>
													<td width="20%" nowrap class="cellLabel"><strong>{$MOD.ASTERISK_VERSION}</strong></td>
													<td width="80%" class="cellText">
														<div class="dvtCellInfo">
															<select name="asterisk_version" class="detailedViewTextBox input-inline" id="asterisk_version" title="{$MOD.ASTERISK_VERSION_TITLE}">
																	<option value="1.4" {if $ASTERISK_VERSION eq '1.4'}selected{/if}>1.4</option>
																	<option value="1.6" {if $ASTERISK_VERSION eq '1.6'}selected{/if}>1.6</option>
															</select>
														</div>
													</td>
												</tr>
												<tr>
													<td width="20%" nowrap colspan="2" align ="center">
														<button type="button" name="update" class="crmbutton create" onclick="VTE.Settings.PBXManagerEdit.validatefn1('asterisk');">{$MOD.LBL_UPDATE_BUTTON}</button>
														<button type="button" name="cancel" class="crmbutton cancel" onClick="window.history.back();">{$MOD.LBL_CANCEL_BUTTON}</button>
													</td>
												</tr>
											</table>
											</td>
										</tr>
									</table>
								</div>
								<!-- asterisk ends :: can add another <span> for another SIP, say asterisk -->
							</td>
						</tr>
					</table>

					<!-- End of Display -->
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