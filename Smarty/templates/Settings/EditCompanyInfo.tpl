{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/CompanyInfo.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php?module=Settings&action=add2db" method="post" name="index" enctype="multipart/form-data" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="return_module" value="Settings">
					<input type="hidden" name="parenttab" value="Settings">
					<input type="hidden" name="return_action" value="OrganizationConfig">

					{include file="SetMenu.tpl"}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}    	

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'company.gif'|resourcever}" width="48" height="48" border=0 ></td>
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_EDIT} {$MOD.LBL_COMPANY_DETAILS} </b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_COMPANY_DESC}</td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big">
								<strong>{$MOD.LBL_COMPANY_DETAILS}</strong>
								{$ERRORFLAG}<br>
							</td>
							<td align=right>
								<button class="crmbutton save" type="submit" name="button" onclick="return VTE.Settings.EditCompanyInfo.verify_data(form,'{$MOD.LBL_ORGANIZATION_NAME}');">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
								<button class="crmbutton cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
							</td>
						</tr>
					</table>
					
					<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
						<tr>
							<td valign=top>
								<table width="100%" border="0" cellspacing="0" cellpadding="5">
									<tr>
										<td width="20%" class="cellLabel"><font color="red">*</font><strong>{$MOD.LBL_ORGANIZATION_NAME}</strong></td>
										<td width="80%" class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_name" class="detailedViewTextBox" value="{$ORGANIZATIONNAME}">
												<input type="hidden" name="org_name" value="{$ORGANIZATIONNAME}">
											</div>
										</td>
									</tr>
									<tr valign="top">
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_LOGO}</strong></td>
										<td class="cellText">
											{if $ORGANIZATIONLOGONAME neq ''}	
												<img src="storage/logo/{$ORGANIZATIONLOGONAME}" height="48" />
											{else}
												<img src="{'noimage.gif'|resourcever}" height="96" />
											{/if}
											<br /><br />{$MOD.LBL_SELECT_LOGO}
											<div class="dvtCellInfo">
												<INPUT TYPE="HIDDEN" NAME="MAX_FILE_SIZE" VALUE="800000">
												<INPUT TYPE="HIDDEN" NAME="PREV_FILE" VALUE="{$ORGANIZATIONLOGONAME}">	 
												<input type="file" name="binFile" value="{$ORGANIZATIONLOGONAME}" onchange="validateFilename(this);">[{$ORGANIZATIONLOGONAME}]
												<input type="hidden" name="binFile_hidden" value="{$ORGANIZATIONLOGONAME}" />
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_ADDRESS}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_address" class="detailedViewTextBox" value="{$ORGANIZATIONADDRESS}">
											</div>
										</td>
									</tr>
									<tr> 
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CITY}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_city" class="detailedViewTextBox" value="{$ORGANIZATIONCITY}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_STATE}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_state" class="detailedViewTextBox" value="{$ORGANIZATIONSTATE}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CODE}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_code" class="detailedViewTextBox" value="{$ORGANIZATIONCODE}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_COUNTRY}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_country" class="detailedViewTextBox" value="{$ORGANIZATIONCOUNTRY}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_PHONE}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_phone" class="detailedViewTextBox" value="{$ORGANIZATIONPHONE}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_FAX}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_fax" class="detailedViewTextBox" value="{$ORGANIZATIONFAX}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_WEBSITE}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_website" class="detailedViewTextBox" value="{$ORGANIZATIONWEBSITE}">
											</div>
										</td>
									</tr>
									{* crmvillage 510 release start *}
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_VAT}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_vat_registration_number" class="detailedViewTextBox" value="{$ORGANIZATIONVAT}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_REA}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_rea" class="detailedViewTextBox" value="{$ORGANIZATIONREA}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_CAPITAL}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_issued_capital" class="detailedViewTextBox" value="{$ORGANIZATIONCAPITAL}">
											</div>
										</td>
									</tr>
									<tr>
										<td class="cellLabel"><strong>{$MOD.LBL_ORGANIZATION_BANKING}</strong></td>
										<td class="cellText">
											<div class="dvtCellInfo">
												<input type="text" name="organization_banking" class="detailedViewTextBox" value="{$ORGANIZATIONBANKING}">
											</div>
										</td>
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