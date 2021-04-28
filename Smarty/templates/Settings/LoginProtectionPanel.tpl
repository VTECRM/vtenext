{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@56023 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/LoginProtectionPanel.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				<!-- crmv@30683 -->
				<form action="index.php" method="post" name="LoginProtectionForm" id="form" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type='hidden' name='parenttab' value='{$CATEGORY}'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'}
					
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'ico-profile.gif'|resourcever}" alt="{$APP.LoginProtectionPanel}" width="48" height="48" border=0 title="{$APP.LoginProtectionPanel}"></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$APP.LoginProtectionPanel}</b></td>
						</tr>
						<tr>
							<td valign=top>{$APP.LoginProtectionPanel_description}</td>
						</tr>
					</table>

					<br>

					{if $ENABLED eq true}
						<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
							<tr>
								<td class="big" height="30px;"><strong>{$MOD.LBL_LOGIN_HISTORY_DETAILS}</strong></td>
								<td align="left">&nbsp;</td>
							</tr>
						</table>

						<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
							<tr>
								<td valign=top>
									<table width="100%" border="0" cellspacing="0" cellpadding="5">
										<tr valign="top">
											<td nowrap width="18%" class="cellLabel"><strong>{$MOD.LBL_USER_AUDIT}</strong></td>
											<td class="cellText">
												<select name="user_list" id="user_list" class="detailedViewTextBox input-inline" onchange="fetchloginprotection_js(this,'');">
													<option value="none" selected="true">{$APP.LBL_NONE}</option>
													<option value="all">{$APP.LBL_ALL}</option> 
													{$USERLIST}
												</select>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>

						<table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td class="big"><strong>{$CMOD.LBL_LOGIN_HISTORY}</strong></td>
							</tr>
						</table>

						<table border="0" cellpadding="5" cellspacing="0" width="100%">
							<tr>
								<td align="left">
									<div id="login_protection_content" style="display:none;"></div>
								<td>
							</tr>
						</table>

						<br>
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

{literal}
<script type="text/javascript">
	function fetchloginprotection_js(obj, url_string) {
		var id = obj.options[obj.selectedIndex].value;
		VTE.Settings.LoginProtectionPanel.fetchLoginProtection(id,url_string);
	}
</script>
{/literal}