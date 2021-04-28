{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/Currency.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
	
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
			<!-- DISPLAY -->
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
			<form action="index.php" onsubmit="VteJS_DialogBox.block();">
			<input type="hidden" name="module" value="Settings">
			<input type="hidden" name="action" value="CurrencyEditView">
			<input type="hidden" name="parenttab" value="{$PARENTTAB}">
			<tr>
					<td width=50 rowspan=2 valign=top><img src="{'currency.gif'|resourcever}" alt="{$MOD.LBL_USERS}" width="48" height="48" border=0 title="{$MOD.LBL_USERS}"></td>
				<td class="heading2" valign="bottom" ><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_CURRENCY_SETTINGS} </b></td> <!-- crmv@30683 -->
			</tr>
			<tr>
				<td valign=top class="small">{$MOD.LBL_CURRENCY_DESCRIPTION}</td>
			</tr>
			</table>
			<br>
			<table border=0 cellspacing=0 cellpadding=10 width=100% >
			<tr>
			<td>

			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
				<tr>
					<td class="big"><strong>{$MOD.LBL_CURRENCY_LIST}</strong></td>
                	       		<td class="small" align="right">&nbsp;</td>
				</tr>
			</table>

			<table width="100%" border="0" cellpadding="5" cellspacing="0" class="listTableTopButtons">
	                <tr>
				<td class=small align=right>
					<input type="submit" value="{$MOD.LBL_NEW_CURRENCY}" class="crmButton create small">
				</td>
	                </tr>
	                </table>

			<div id="CurrencyListViewContents">
				{include file="CurrencyListViewEntries.tpl"}
			</div>

	{include file="Settings/ScrollTop.tpl"}
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</form>
	</table>
		
	</div>

</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>

<div id="currencydiv" style="display:block;position:absolute;width:250px;"></div>