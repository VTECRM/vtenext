{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/ListLoginHistory.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="ListLoginHistory" id="form" onsubmit="VteJS_DialogBox.block();">
					<input type='hidden' name='module' value='Users'>
					<input type='hidden' name='action' value='ListLoginHistory'>
					<input type='hidden' name='record' id='record' value="{$ID}">
					<input type='hidden' name='parenttab' value='Settings'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'set-IcoLoginHistory.gif'|resourcever}" alt="{$MOD.LBL_AUDIT_TRAIL}" width="48" height="48" border=0 title="{$MOD.LBL_LOGIN_HISTORY_DETAILS}"></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_LOGIN_HISTORY_DETAILS}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_LOGIN_HISTORY_DESCRIPTION}</td>
						</tr>
					</table>
				
					<br>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big" height="30px;"><strong>{$MOD.LBL_LOGIN_HISTORY_DETAILS}</strong></td>
							<td align="left">&nbsp;</td>
						</tr>
					</table>
			
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%"  border="0" cellspacing="0" cellpadding="5">
									<tr valign="top">
										<td nowrap width="18%" class="cellLabel"><strong>{$MOD.LBL_USER_AUDIT}</strong></td>
										<td class="cellText">
											<select name="user_list" id="user_list" class="detailedViewTextBox input-inline" onchange="VTE.Settings.ListLoginHistory.fetchLoginHistory({$ID});">
												<option value="none" selected="true">{$APP.LBL_NONE}</option>
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
							<td class="big">
								<strong>{$CMOD.LBL_LOGIN_HISTORY}</strong>
							</td>
						</tr>
					</table>

					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
							<td align="left">
								<div id="login_history_cont" style="display:none;"></div>
							</td>
						</tr>
					</table>

					<br>
	
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

<script type="text/javascript">
function getListViewEntries_js(module, url) {ldelim}
	VTE.Settings.ListLoginHistory.getListViewEntries_js(module, url);
{rdelim}
</script>