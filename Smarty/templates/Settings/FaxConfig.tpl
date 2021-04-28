{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/FaxConfig.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				{if $FAXCONFIG_MODE neq 'edit'}
					<form action="index.php" method="post" name="FaxServer" id="form" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="faxconfig_mode">
				{else}
					{literal}
					<form action="index.php" method="post" name="FaxServer" id="form" onsubmit="if (VTE.Settings.FaxConfig.validate_fax_server(FaxServer)) { VteJS_DialogBox.block(); return true; } else { return false; }">
					{/literal}		
					<input type="hidden" name="server_type" value="fax">
				{/if}

				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action">
				<input type="hidden" name="parenttab" value="Settings">
				<input type="hidden" name="return_module" value="Settings">
				<input type="hidden" name="return_action" value="FaxConfig">
			
				{include file="SetMenu.tpl"}
				{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
					<tr>
						<td width=50 rowspan=2 valign=top><img src="{'ogfaxserver.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
						<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_FAX_SERVER_SETTINGS} </b></td> <!-- crmv@30683 -->
					</tr>
					<tr>
						<td valign=top>{$MOD.LBL_FAX_SERVER_DESC} </td>
					</tr>
				</table>
				
				<br>
				
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_FAX_SERVER_SMTP}</strong></td>
						{if $FAXCONFIG_MODE neq 'edit'}	
							<td align=right>
								<button class="crmbutton edit" onclick="this.form.action.value='FaxConfig';this.form.faxconfig_mode.value='edit'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
							</td>
						{else}
							<td align=right>
								<button class="crmbutton save" onclick="this.form.action.value='Save';" type="submit" name="button">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
								<button class="crmbutton cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
							</td>
						{/if}
					</tr>
					{if $ERROR_MSG neq ''}
						<tr>
							{$ERROR_MSG}
						</tr>
					{/if}
				</table>
					
				{if $FAXCONFIG_MODE neq 'edit'}
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_OUTGOING_FAX_SERVER_TYPE}</strong></td>
										<td width="80%" class="cellText"><strong>{$MOD.$FAXSERVERTYPE}&nbsp;</strong></td>
									</tr>					
									<tr>
										<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_OUTGOING_FAX_SERVER}</strong></td>
										<td width="80%" class="cellText"><strong>{$FAXSERVER}&nbsp;</strong></td>
									</tr>
									<tr valign="top">
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
										<td class="cellText">{$USERNAME}&nbsp;</td>
									</tr>
									<tr>
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
										<td class="cellText">
											{if $PASSWORD neq ''}******{/if}&nbsp;
										</td>
									</tr>
									<tr> 
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_REQUIRES_AUTHENT}</strong></td>
										<td class="cellText">
											{if $SMTP_AUTH eq 'checked'}
												{$MOD.LBL_YES}
											{else}
												{$MOD.LBL_NO}
											{/if}
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				{else}
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" nowrap class="cellLabel"><font color="red">*</font>&nbsp;<strong>{$MOD.LBL_OUTGOING_FAX_SERVER_TYPE}</strong></td>
										<td width="80%" class="cellText">
											<div class="dvtCellInfo">
												<select name="service_type" id="service_type" class="detailedViewTextBox">
													{foreach item=arr from=$SERVER_TYPE}
													{if ($arr eq $FAXSERVERTYPE)}
														<option value="{$arr}" selected>
													{else}
														<option value="{$arr}">    
													{/if}	   
														{$MOD.$arr}
														</option>
													{/foreach} 
												</select>
											</div>
										</td>
									</tr>
									<tr>
										<td width="20%" nowrap class="cellLabel"><font color="red">*</font>&nbsp;<strong>{$MOD.LBL_OUTGOING_FAX_SERVER}</strong></td>
										<td width="80%" class="cellText">
											<div class="dvtCellInfo">
												<input type="text" class="detailedViewTextBox" value="{$FAXSERVER}" name="server">
											</div>
										</td>
									</tr>
									<tr valign="top">
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" class="detailedViewTextBox" value="{$USERNAME}" name="server_username">
											</div>
										</td>
									</tr>
									<tr>
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												{* crmv@43764 *}
												<input type="password" value="{if !empty($PASSWORD)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('server_password').value=this.value;" />
												<input type="hidden" id="server_password" name="server_password" value="">
												{* crmv@43764e *}
											</div>
										</td>
									</tr>
									<tr> 
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_REQUIRES_AUTHENT}</strong></td>
										<td class="cellText">
											<input type="checkbox" name="smtp_auth" {$SMTP_AUTH}/>
										</td>
									</tr>
								</table>
							</tr>
						</td>
					</table>
				{/if}
				
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_FAX_ADVANCED_SETTINGS}</strong></td>
					</tr>
				</table>

				{if $FAXCONFIG_MODE neq 'edit'}
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_DOMAIN}</strong></td>
										<td width="80%" class="cellText"><strong>{$ADVDOMAIN}&nbsp;</strong></td>
									</tr>
									<tr valign="top">
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_ACCOUNT}</strong></td>
										<td class="cellText">{$ADVACCOUNT}&nbsp;</td>
									</tr>
									<tr>
										<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_PREFIX}</strong></td>
										<td width="80%" class="cellText"><strong>{$ADVPREFIX}&nbsp;</strong></td>
									</tr>
									<tr>
										<td nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_NAME}</strong></td>
										<td class="cellText">{$ADVNAME}&nbsp;</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				{else}
					<table width="100%" border="0" cellspacing="0" cellpadding="5">
						<tr>
							<td width="20%" nowrap class="cellLabel"><font color="red">*</font>&nbsp;<strong>{$MOD.LBL_FAX_DOMAIN}</strong></td>
							<td width="80%" class="cellText">
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" value="{$ADVDOMAIN}" name="adv_domain">
								</div>
							</td>
						</tr>
						<tr>
							<td nowrap class="cellLabel"><font color="red">*</font>&nbsp;<strong>{$MOD.LBL_FAX_ACCOUNT}</strong></td>
							<td class="cellText">
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" value="{$ADVACCOUNT}" name="adv_account">
								</div>
							</td>
						</tr>
						<tr>
							<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_PREFIX}</strong></td>
							<td width="80%" class="cellText">
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" value="{$ADVPREFIX}" name="adv_prefix">
								</div>
							</td>
						</tr>
						<tr>
							<td nowrap class="cellLabel"><strong>{$MOD.LBL_FAX_NAME}</strong></td>
							<td class="cellText">
								<div class="dvtCellInfo">
									<input type="text" class="detailedViewTextBox" value="{$ADVNAME}" name="adv_name">
								</div>
							</td>
						</tr>
					</table>
				{/if}

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