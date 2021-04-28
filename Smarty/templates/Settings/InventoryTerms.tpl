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
				<form action="index.php" method="post" name="tandc" onsubmit="VteJS_DialogBox.block();">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="action">
					<input type="hidden" name="inv_terms_mode">
					<input type="hidden" name="parenttab" value="Settings">

					{include file="SetMenu.tpl"}
					{include file='Buttons_List.tpl'} {* crmv@30683 *} 

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'terms.gif'|resourcever}" width="48" height="48" border=0></td>
							<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.INVENTORYTERMSANDCONDITIONS}</b></td> <!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_INVEN_TANDC_DESC} </td>
						</tr>
					</table>
				
					<br>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$MOD.LBL_TANDC_TEXT}</strong></td>
							{if $INV_TERMS_MODE eq 'view'}
								<td align=right>
									<button class="crmbutton edit" onclick="this.form.action.value='OrganizationTermsandConditions';this.form.inv_terms_mode.value='edit'" type="submit" name="Edit">{$APP.LBL_EDIT_BUTTON_LABEL}</button>
								</td>
							{else}
								<td align=right> 
									<button class="crmbutton save" type="submit" name="button" onclick="this.form.action.value='savetermsandconditions';">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
									<button class="crmbutton cancel" onclick="window.history.back()" type="button" name="button">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
								</td>
							{/if}
						</tr>
					</table>
					
					{if $INV_TERMS_MODE eq 'view'}
						<table border=0 cellspacing=0 cellpadding=5 width=100%>
							<tr>
								<td class="listRow" valign=top style="padding:20px">
									{$INV_TERMSANDCONDITIONS}
								</td>
					 		 </tr>
						</table>
					{else}
						<table border=0 cellspacing=0 cellpadding=5 width=100%>
							<tr>
								<td>{$MOD.LBL_TYPE_TEXT_AND_SAVE}</td>
							</tr>
							<tr>
								<td>
									<textarea class="detailedViewTextBox" name="inventory_tandc" style="height:300px;">{$INV_TERMSANDCONDITIONS}</textarea>
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
