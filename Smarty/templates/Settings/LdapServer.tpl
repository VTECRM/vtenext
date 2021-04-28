{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/Settings/resources/LdapServer.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="tandc">
					<input type="hidden" name="server_type" value="ldap">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="action" value="index">
					<input type="hidden" name="delete" value="0">
					<input type="hidden" name="ldap_server_mode">
					<input type="hidden" name="parenttab" value="Settings">

					{include file="SetMenu.tpl"}
					{include file='Buttons_List.tpl'} {* crmv@30683 *} 

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'ldap.gif'|resourcever}" alt="{$MOD.LBL_LDAP}" width="48" height="48" border=0 title="{$MOD.LBL_LDAP}"></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_LDAP_SERVER_SETTINGS}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_LDAP_SERVER_DESC} </td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_LDAP_SERVER_SETTINGS}<br>{$ERROR_MSG}</strong></td>
							{if $LDAP_SERVER_MODE neq 'edit'}
								<td align=right>
									<button class="crmbutton edit" onclick="this.form.action.value='LdapConfig';this.form.ldap_server_mode.value='edit'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
								</td>
							{else}
								<td align=right>
									<button class="crmbutton save" type="submit" name="button" onclick="this.form.action.value='SaveLdap'; return VTE.Settings.LdapServer.validate()">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									<button class="crmbutton cancel" onclick="javascript:document.location.href='index.php?module=Settings&action=LdapConfig&parenttab=Settings'" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
								</td>
							{/if}
						</tr>
					</table>
				
					{if $LDAP_SERVER_MODE eq 'edit'}	
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
							<tr>
								<td valign=top>
									<table width="100%" border="0" cellspacing="0" cellpadding="5">
										<tr valign="top">
											<td nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_ACTIVE}</strong></td>
											<td class="cellText">
												<input type="checkbox" value="1" name="ldap_active" id="ldap_active" {if ($LDAPACTIVE eq 1)}checked{/if}>
											</td>
										</tr>			    
										<tr valign="top">
											<td nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_SERVER_ADDRESS}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_host neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_host}" name="ldap_host" id="ldap_host">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPHOST}" name="ldap_host" id="ldap_host">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_SERVER_ADDRESS} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_PORT}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_port neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_port}" name="ldap_port" id="ldap_port">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPPORT}" name="ldap_port" id="ldap_port">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_PORT} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_LDAPBSEDN}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_basedn neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_basedn}" name="ldap_basedn" id="ldap_basedn">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPBSEDN}" name="ldap_basedn" id="ldap_basedn">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_LDAPBSEDN} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
											<td class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_username neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_username}" name="ldap_username" id="ldap_username">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPSUSER}" name="ldap_username" id="ldap_username">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_USERNAME} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
											<td class="cellText">
												<div class="dvtCellInfo">
													{* crmv@43764 *}
													<input type="password" value="{if !empty($LDAPSPASSWORD)}********{/if}" class="detailedViewTextBox" onFocus="this.value='';" onChange="document.getElementById('ldap_pass').value=this.value;" />
													<input type="hidden" id="ldap_pass" name="ldap_pass" value="">
													{* crmv@43764e *}
												</div>
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_OBJCLASS}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_objclass neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_objclass}" name="ldap_objclass" id="ldap_objclass">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPOBJCLASS}" name="ldap_objclass" id="ldap_objclass">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_OBJCLASS} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_LDAPACCOUNT}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_account neq ''}
														<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_account}" name="ldap_account" id="ldap_account">
													{else}
														<input type="text" class="detailedViewTextBox" value="{$LDAPACCOUNT}" name="ldap_account" id="ldap_account">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_LDAPACCOUNT} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_LDAPFULLNAME}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_fullname neq ''}
													<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_fullname}" name="ldap_fullname" id="ldap_fullname">
													{else}
													<input type="text" class="detailedViewTextBox" value="{$LDAPFULLNAME}" name="ldap_fullname" id="ldap_fullname">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_LDAPFULLNAME} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_LDAP_LDAPFILTER}</strong></td>
											<td width="80%" class="cellText">
												<div class="dvtCellInfo">
													{if $smarty.request.ldap_userfilter neq ''}
													<input type="text" class="detailedViewTextBox" value="{$smarty.request.ldap_userfilter}" name="ldap_userfilter" id="ldap_userfilter">
													{else}
													<input type="text" class="detailedViewTextBox" value="{$LDAPFILTER}" name="ldap_userfilter" id="ldap_userfilter">
													{/if}
												</div>
												{$MOD.LDAP_EXAMPLE_LDAPFILTER} {* esempi *}
											</td>
										</tr>
										<tr valign="top">
											<td width="20%" nowrap class="cellLabel" align=left><font color="red">*</font><strong>{$MOD.LBL_LDAP_LDAPROLE}</strong></td>
											<td width="80%" align=left>
												<div class="dvtCellInfoOff" style="position:relative">
													<input name="role_name" id="role_name" readonly class="detailedViewTextBox" tabindex="{$vt_tab}" value="{$secondvalue}" type="text">&nbsp;
													<input name="user_role" id="user_role" value="{if $smarty.request.role neq ''}{$smarty.request.roleid}{else}{$roleid}{/if}" type="hidden">
													<div class="dvtCellInfoImgRx">
														<a href="javascript:VTE.Settings.LdapServer.open_Popup();"><i class="vteicon">view_list</i></a>
													</div>
												</div>
												{$MOD.LDAP_EXAMPLE_LDAPROLE} {* esempi *}
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
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_ACTIVE}</strong></td>
											<td class="cellText">
												{if ($LDAPACTIVE eq 1)}
													{$MOD.LBL_YES}
												{else} 
													{$MOD.LBL_NO}
												{/if}
											</td>
										</tr>
										<tr>
											<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_SERVER_ADDRESS} </strong></td>
											<td width="80%" class="cellText"><strong>{$LDAPHOST}&nbsp;</strong></td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_PORT}</strong></td>
											<td class="cellText">{$LDAPPORT}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_LDAPBSEDN}</strong></td>
											<td class="cellText">{$LDAPBSEDN}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_USERNAME}</strong></td>
											<td class="cellText">{$LDAPSUSER}&nbsp;</td>
										</tr>
										<tr>
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_PASWRD}</strong></td>
											<td class="cellText">
											{if $LDAPSPASSWORD neq ''}******{/if}&nbsp;
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_OBJCLASS}</strong></td>
											<td class="cellText">{$LDAPOBJCLASS}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_LDAPACCOUNT}</strong></td>
											<td class="cellText">{$LDAPACCOUNT}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_LDAPFULLNAME}</strong></td>
											<td class="cellText">{$LDAPFULLNAME}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_LDAPFILTER}</strong></td>
											<td class="cellText">{$LDAPFILTER}&nbsp;</td>
										</tr>
										<tr valign="top">
											<td nowrap class="cellLabel"><strong>{$MOD.LBL_LDAP_LDAPROLE}</strong></td>
											<td class="cellText">{$secondvalue}&nbsp;</td>
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