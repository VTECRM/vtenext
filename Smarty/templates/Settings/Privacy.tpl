{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@35153 *}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				<form action="index.php" method="post" id="form" onsubmit="VteJS_DialogBox.block();">
					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'PrivacySettings.png'|resourcever}" alt="{$MOD.LBL_PRIVACY}" width="48" height="48" border=0 title="{$MOD.LBL_PRIVACY}"></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_PRIVACY}</b></td>
						</tr>
						<tr>
							<td valign=top>&nbsp;</td>
						</tr>
					</table>

					<br>

					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								{$MOD.LBL_PRIVACY_DESC}
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