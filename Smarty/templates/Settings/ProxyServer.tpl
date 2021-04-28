{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/Settings/resources/ProxyServer.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="tandc" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="server_type" value="proxy">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="action" value="index">
					<input type="hidden" name="proxy_server_mode">
					<input type="hidden" name="parenttab" value="Settings">

					{include file="SetMenu.tpl"}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'proxy.gif'|resourcever}" alt="{$MOD.LBL_PROXY}" width="48" height="48" border=0 title="{$MOD.LBL_PROXY}"></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_PROXY_SERVER_SETTINGS}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_PROXY_SERVER_DESC} </td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_PROXY_SERVER_SETTINGS}<br>{$ERROR_MSG}</strong></td>
							{if $PROXY_SERVER_MODE neq 'edit'}
								<td align=right>
									<button class="crmbutton edit" onclick="this.form.action.value='ProxyServerConfig';this.form.proxy_server_mode.value='edit'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
								</td>
							{else}
								<td align=right>
									<button class="crmbutton save" type="submit" name="button" onclick="this.form.action.value='Save'; return VTE.Settings.ProxyServer.validate();">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									<button class="crmbutton cancel" onclick="javascript:document.location.href='index.php?module=Settings&action=ProxyServerConfig&parenttab=Settings'" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
								</td>
							{/if}
						</tr>
					</table>
				
					{if $PROXY_SERVER_MODE eq 'edit'}	
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
							<tr>
								<td valign=top>
									<table width="100%" border="0" cellspacing="0" cellpadding="5">
										<tr>
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_SERVER_ADDRESS}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.server neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.server}" name="server">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$PROXYSERVER}" name="server">
													{/if}
												</div>
											</td>
										</tr>
										<tr>
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_PROXY_PORT}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.port neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.port}" name="port">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$PROXYPORT}" name="port">
													{/if}
												</div>
											</td>
										</tr>
										<tr>
											<td nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_USERNAME}</strong></td>
											<td class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.server_username neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.server_username}" name="server_username">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$PROXYUSER}" name="server_username">
													{/if}
												</div>
											</td>
										</tr>
										<tr>
											<td nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_PASWRD}</strong></td>
											<td class="cellText">
												<div class="dvtCellInfo">
													{* crmv@43764 *}
													<input type="password" value="{if !empty($PROXYPASSWORD)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('server_password').value=this.value;" />
													<input type="hidden" id="server_password" name="server_password" value="">
													{* crmv@43764e *}
												</div>
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
									<table width="100%"  border="0" cellspacing="0" cellpadding="5">
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_SERVER_ADDRESS}</strong></td>
											<td width="80%" class="cellText"><strong>{$PROXYSERVER}&nbsp;</strong></td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_PROXY_PORT}</strong></td>
											<td class="cellText">{$PROXYPORT}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
											<td class="cellText">{$PROXYUSER}&nbsp;</td>
										</tr>
										<tr>
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
											<td class="cellText">
												{if $PROXYPASSWORD neq ''}******{/if}&nbsp;
											</td>
										</tr>
									</table>
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