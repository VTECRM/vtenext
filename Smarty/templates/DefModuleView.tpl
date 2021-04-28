{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/Settings/resources/DefModuleView.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
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
							<td width=50 rowspan=2 valign=top><img src="{'set-IcoTwoTabConfig.gif'|resourcever}" alt="{$MOD.LBL_DEFAULT_MODULE_VIEW}" width="48" height="48" border=0 title="{$MOD.LBL_DEFAULT_MODULE_VIEW}"></td>
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_DEFAULT_MODULE_VIEW}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_DEFAULT_MODULE_VIEW_DESC}</td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big" height="40px;" width="70%"><strong>{$MOD.LBL_DEFAULT_DETAIL_VIEW}</strong></td>
							<td align="center" width="30%">&nbsp;
								<span id="view_info" class="crmButton cancel" style="display:none;"></span>
							</td>
						</tr>
					</table>
			
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" nowrap class="cellLabel"><strong>{$MOD.LBL_ENABLE_SINGLEPANE_VIEW}</strong></td>
										<td width="80%" class="cellText">
											{if $ViewStatus eq 'enabled'}
												<input type="checkbox" checked name="enable_audit" onclick="VTE.Settings.DefModuleView.viewenabled(this)" />
											{else}
												<input type="checkbox" name="enable_audit" onclick="VTE.Settings.DefModuleView.viewenabled(this)" />
											{/if}
										</td>
									</tr>
								</table>
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