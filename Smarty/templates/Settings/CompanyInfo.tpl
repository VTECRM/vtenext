{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="company" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="parenttab" value="Settings">
					<input type="hidden" name="action">

					{include file="SetMenu.tpl"}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'company.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_COMPANY_DETAILS} </b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_COMPANY_DESC} </td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_COMPANY_DETAILS} </strong></td>
							<td align=right>
								<button class="crmbutton edit" onclick="this.form.action.value='EditCompanyDetails'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
							</td>
						</tr>
					</table>
					
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%"  border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_NAME}</strong></td>
										<td width="80%" class="cellText"><strong>{$ORGANIZATIONNAME}</strong></td>
									</tr>
									<tr valign="top">
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_LOGO}</strong></td>
										<td class="cellText" style="background-image: url({$ORGANIZATIONLOGOPATH}/{$ORGANIZATIONLOGONAME}); background-position: left; background-repeat: no-repeat;" width="48" height="48" border="0"></td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_ADDRESS}</strong></td>
										<td class="cellText">{$ORGANIZATIONADDRESS}</td>
									</tr>
									<tr> 
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CITY}</strong></td>
										<td class="cellText">{$ORGANIZATIONCITY}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_STATE}</strong></td>
										<td class="cellText">{$ORGANIZATIONSTATE}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CODE}</strong></td>
										<td class="cellText">{$ORGANIZATIONCODE}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_COUNTRY}</strong></td>
										<td class="cellText">{$ORGANIZATIONCOUNTRY}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_PHONE}</strong></td>
										<td class="cellText">{$ORGANIZATIONPHONE}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_FAX}</strong></td>
										<td class="cellText">{$ORGANIZATIONFAX}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_WEBSITE}</strong></td>
										<td class="cellText">{$ORGANIZATIONWEBSITE}</td>
									</tr>
									{* crmvillage 510 release start *}
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_VAT}</strong></td>
										<td class="cellText">{$ORGANIZATIONVAT}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_REA}</strong></td>
										<td class="cellText">{$ORGANIZATIONREA}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CAPITAL}</strong></td>
										<td class="cellText">{$ORGANIZATIONCAPITAL}</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_BANKING}</strong></td>
										<td class="cellText">{$ORGANIZATIONBANKING}</td>
									</tr>
									{* crmvillage 510 release stop *}
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