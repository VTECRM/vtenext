<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@120993 */

global $adb, $table_prefix;

// insert missing pdfmaker templates
$name = 'product block for individual tax';
$adb->query("INSERT INTO {$table_prefix}_pdfmaker_prodbloc_tpl (id, name, body) VALUES (1, '{$name}', ".$adb->getEmptyClob(true).")");
$body = 
'<table border="1" cellpadding="3" cellspacing="0" style="font-size:10px;" width="100%">
<tbody>
	<tr bgcolor="#c0c0c0">
		<td style="TEXT-ALIGN: center">
			<span><strong>Pos</strong></span></td>
		<td colspan="2" style="TEXT-ALIGN: center">
			<span><strong>%G_Qty%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><span style="font-weight: bold;">Text</span></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_LBL_LIST_PRICE%<br />
			</strong></span></td>
		<td style="text-align: center;">
			<span><strong>%G_LBL_SUB_TOTAL%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_Discount%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_LBL_NET_PRICE%<br />
			</strong></span></td>
		<td style="text-align: center;">
			<span><strong>%G_Tax% (%)</strong></span></td>
		<td style="text-align: center;">
			<span><strong>%G_Tax%</strong> (<strong>$CURRENCYCODE$</strong>)</span></td>
		<td style="text-align: center;">
			<span><strong>%M_Total%</strong></span></td>
	</tr>
	<tr>
		<td colspan="11">
			#PRODUCTBLOC_START#</td>
	</tr>
	<tr>
		<td style="text-align: center; vertical-align: top;">
			$PRODUCTPOSITION$</td>
		<td align="right" valign="top">
			$PRODUCTQUANTITY$</td>
		<td align="left" style="TEXT-ALIGN: center" valign="top">
			$PRODUCTUSAGEUNIT$</td>
		<td align="left" valign="top">
			$PRODUCTNAME$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTLISTPRICE$</td>
		<td align="right" style="TEXT-ALIGN: right" valign="top">
			$PRODUCTTOTAL$</td>
		<td align="right" style="TEXT-ALIGN: right" valign="top">
			$PRODUCTDISCOUNT$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTSTOTALAFTERDISCOUNT$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTVATPERCENT$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTVATSUM$</td>
		<td align="right" style="TEXT-ALIGN: right" valign="top">
			$PRODUCTTOTALSUM$</td>
	</tr>
	<tr>
		<td colspan="11">
			#PRODUCTBLOC_END#</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			%G_LBL_TOTAL%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALWITHOUTVAT$</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			%G_Discount%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALDISCOUNT$</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			%G_LBL_NET_TOTAL%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALAFTERDISCOUNT$</td>
	</tr>
	<tr>
		<td colspan="10" style="text-align: left;">
			%G_Tax% $VATPERCENT$ % %G_LBL_LIST_OF% $TOTALAFTERDISCOUNT$</td>
		<td style="text-align: right;">
			$VAT$</td>
	</tr>
	<tr>
		<td colspan="10" style="text-align: left;">
			Total with TAX</td>
		<td style="text-align: right;">
			$TOTALWITHVAT$</td>
	</tr>
	<tr>
		<td colspan="10" style="text-align: left;">
			%G_LBL_SHIPPING_AND_HANDLING_CHARGES%</td>
		<td style="text-align: right;">
			$SHTAXAMOUNT$</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			%G_LBL_TAX_FOR_SHIPPING_AND_HANDLING%</td>
		<td style="TEXT-ALIGN: right">
			$SHTAXTOTAL$</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			%G_Adjustment%</td>
		<td style="TEXT-ALIGN: right">
			$ADJUSTMENT$</td>
	</tr>
	<tr>
		<td colspan="10" style="TEXT-ALIGN: left">
			<span style="font-weight: bold;">%G_LBL_GRAND_TOTAL% </span><strong>($CURRENCYCODE$)</strong></td>
		<td nowrap="nowrap" style="TEXT-ALIGN: right">
			<strong>$TOTAL$</strong></td>
	</tr>
</tbody>
</table>';
$adb->updateClob("{$table_prefix}_pdfmaker_prodbloc_tpl", 'body', 'id=1', $body);


$name = 'product block for group tax';
$adb->query("INSERT INTO {$table_prefix}_pdfmaker_prodbloc_tpl (id, name, body) VALUES (2, '{$name}', ".$adb->getEmptyClob(true).")");
$body = '
<table border="1" cellpadding="3" cellspacing="0" style="font-size:10px;" width="100%">
<tbody>
	<tr bgcolor="#c0c0c0">
		<td style="TEXT-ALIGN: center">
			<span><strong>Pos</strong></span></td>
		<td colspan="2" style="TEXT-ALIGN: center">
			<span><strong>%G_Qty%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><span style="font-weight: bold;">Text</span></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_LBL_LIST_PRICE%<br />
			</strong></span></td>
		<td style="text-align: center;">
			<span><strong>%G_LBL_SUB_TOTAL%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_Discount%</strong></span></td>
		<td style="TEXT-ALIGN: center">
			<span><strong>%G_LBL_NET_PRICE%<br />
			</strong></span></td>
	</tr>
	<tr>
		<td colspan="8">
			#PRODUCTBLOC_START#</td>
	</tr>
	<tr>
		<td style="text-align: center; vertical-align: top;">
			$PRODUCTPOSITION$</td>
		<td align="right" valign="top">
			$PRODUCTQUANTITY$</td>
		<td align="left" style="TEXT-ALIGN: center" valign="top">
			$PRODUCTUSAGEUNIT$</td>
		<td align="left" valign="top">
			$PRODUCTNAME$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTLISTPRICE$</td>
		<td align="right" style="TEXT-ALIGN: right" valign="top">
			$PRODUCTTOTAL$</td>
		<td align="right" style="TEXT-ALIGN: right" valign="top">
			$PRODUCTDISCOUNT$</td>
		<td align="right" style="text-align: right;" valign="top">
			$PRODUCTSTOTALAFTERDISCOUNT$</td>
	</tr>
	<tr>
		<td colspan="8">
			#PRODUCTBLOC_END#</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			%G_LBL_TOTAL%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALWITHOUTVAT$</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			%G_Discount%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALDISCOUNT$</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			%G_LBL_NET_TOTAL%</td>
		<td style="TEXT-ALIGN: right">
			$TOTALAFTERDISCOUNT$</td>
	</tr>
	<tr>
		<td colspan="7" style="text-align: left;">
			%G_Tax% $VATPERCENT$ % %G_LBL_LIST_OF% $TOTALAFTERDISCOUNT$</td>
		<td style="text-align: right;">
			$VAT$</td>
	</tr>
	<tr>
		<td colspan="7" style="text-align: left;">
			Total with TAX</td>
		<td style="text-align: right;">
			$TOTALWITHVAT$</td>
	</tr>
	<tr>
		<td colspan="7" style="text-align: left;">
			%G_LBL_SHIPPING_AND_HANDLING_CHARGES%</td>
		<td style="text-align: right;">
			$SHTAXAMOUNT$</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			%G_LBL_TAX_FOR_SHIPPING_AND_HANDLING%</td>
		<td style="TEXT-ALIGN: right">
			$SHTAXTOTAL$</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			%G_Adjustment%</td>
		<td style="TEXT-ALIGN: right">
			$ADJUSTMENT$</td>
	</tr>
	<tr>
		<td colspan="7" style="TEXT-ALIGN: left">
			<span style="font-weight: bold;">%G_LBL_GRAND_TOTAL% </span><strong>($CURRENCYCODE$)</strong></td>
		<td nowrap="nowrap" style="TEXT-ALIGN: right">
			<strong>$TOTAL$</strong></td>
	</tr>
</tbody>
</table>';
$adb->updateClob("{$table_prefix}_pdfmaker_prodbloc_tpl", 'body', 'id=2', $body);