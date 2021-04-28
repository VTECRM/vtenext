{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@42024 - righe per totali *}

<!-- Add Product Button -->
<tr>
<td colspan="3">
	<button type="button" name="Button" class="crmbutton create" onclick="fnAddProductOrServiceRowNew('{$MODULE}','{$IMAGE_PATH}','Products','{$MODE}');">{$APP.LBL_ADD_PRODUCT}</button>
	<button type="button" name="Button" class="crmbutton create" onclick="fnAddProductOrServiceRowNew('{$MODULE}','{$IMAGE_PATH}','Services','{$MODE}');">{$APP.LBL_ADD_SERVICE}</button>
</td>
</tr>

{*
All these details are stored in the first element in the array with the index name as final_details
so we will get that array, parse that array and fill the details
*}
{assign var="FINAL" value=$FINAL_DETAILS.1.final_details}

<!-- Product Details Final Total Discount, Tax and Shipping&Hanling  - Starts -->
<tr valign="top">
	<td width="88%" colspan="2" class="crmTableRow small lineOnTop" align="right"><b>{$APP.LBL_NET_TOTAL}</b></td>
	<td width="12%" id="netTotal" class="crmTableRow small lineOnTop" align="right">{0.00|formatUserNumber}</td>
</tr>

<tr valign="top">
	<td class="crmTableRow small lineOnTop" width="60%" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow small lineOnTop" align="right">
		(-)&nbsp;<b><a href="javascript:void(0);" onClick="displayCoords(this,'discount_div_final','discount_final','1')">{$APP.LBL_DISCOUNT}</a></b>

		<!-- Popup Discount DIV -->
		<div class="discountUI" id="discount_div_final">
			<input type="hidden" id="discount_type_final" name="discount_type_final" value="{$FINAL.discount_type_final}">
			<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small">
			   <tr>
				<td id="discount_div_title_final" class="level3Bg" nowrap align="left" colspan="2"></td>
			   </tr>
			   <tr>
				<td align="left" class="lineOnTop"><input type="radio" name="discount_final" checked onclick="setDiscount(this,'_final'); calcGroupTax(); calcTotal();">&nbsp; {$APP.LBL_ZERO_DISCOUNT}</td>
				<td class="lineOnTop">&nbsp;</td>
			   </tr>
			   <tr>
			   {* crmv@2539m *}
				<td align="left"><input type="radio" name="discount_final" onclick="setDiscount(this,'_final');  calcTotal(); calcGroupTax();" {$FINAL.checked_discount_percentage_final}>&nbsp;<label for="discount_percent_final"> % {$APP.LBL_OF_PRICE}</label>&nbsp;<i class="vteicon md-link md-sm md-text" onclick="alert(fieldhelpinfo['hdnDiscountPercent']);">help</i></td> {* crmv@174683 *}
				<td align="right"><input type="text" class="detailedViewTextBox input-inline" size="5" id="discount_percentage_final" name="discount_percentage_final" value="{$FINAL.discount_percentage_final}" {$FINAL.style_discount_percentage_final} onChange="setDiscount(this,'_final'); calcGroupTax(); calcTotal();">&nbsp;%</td>
				{* crmv@2539me *}
			   </tr>
			   <tr>
				<td align="left" nowrap><input type="radio" name="discount_final" onclick="setDiscount(this,'_final');  calcTotal(); calcGroupTax();" {$FINAL.checked_discount_amount_final}>&nbsp;{$APP.LBL_DIRECT_PRICE_REDUCTION}</td>
				<td align="right"><input type="text" class="detailedViewTextBox input-inline" id="discount_amount_final" name="discount_amount_final" size="5" value="{$FINAL.discount_amount_final|formatUserNumber}" {$FINAL.style_discount_amount_final} onChange="setDiscount(this,'_final');  calcGroupTax(); calcTotal();"></td>
			   </tr>
			</table>
			<div class="closebutton" onClick="fnHidePopDiv('discount_div_final')"></div>
		</div>
		<!-- End Div -->

	</td>
	<td id="discountTotal_final" class="crmTableRow small lineOnTop" align="right">{$FINAL.discountTotal_final|formatUserNumber}</td>
</tr>

<!-- Group Tax - starts -->
<tr id="group_tax_row" valign="top" class="TaxHide">
	<td class="crmTableRow small lineOnTop" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow small lineOnTop" align="right">
		(+)&nbsp;<b><a href="javascript:void(0);" onClick="displayCoords(this,'group_tax_div','group_tax_div_title','');  calcTotal(); calcGroupTax();" >{$APP.LBL_TAX}</a></b>

				<!-- Pop Div For Group TAX -->
				<div class="discountUI" id="group_tax_div">
					<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small">
					   <tr>
						<td id="group_tax_div_title" class="level3Bg" colspan="3" nowrap align="left" ></td>
					   </tr>
					{foreach item=tax_detail name=group_tax_loop key=loop_count from=$FINAL.taxes}
					   <tr>
						<td align="left" class="lineOnTop">
							<input type="text" class="detailedViewTextBox input-inline" size="5" name="{$tax_detail.taxname}_group_percentage" id="group_tax_percentage{$smarty.foreach.group_tax_loop.iteration}" value="{$tax_detail.percentage|formatUserNumber}" onChange="calcTotal()">&nbsp;%
						</td>
						<td align="center" class="lineOnTop">{$tax_detail.taxlabel}</td>
						<td align="right" class="lineOnTop">
							<input type="text" class="detailedViewTextBox input-inline" size="6" name="{$tax_detail.taxname}_group_amount" id="group_tax_amount{$smarty.foreach.group_tax_loop.iteration}" style="cursor:pointer;" value="{0.00|formatUserNumber}" readonly>
						</td>
					   </tr>
					{/foreach}
					<input type="hidden" id="group_tax_count" value="{$smarty.foreach.group_tax_loop.iteration}">
					</table>
					<div class="closebutton" onClick="fnHidePopDiv('group_tax_div')"></div>
				</div>
				<!-- End Popup Div Group Tax -->


	</td>
	<td id="tax_final" class="crmTableRow small lineOnTop" align="right">{$FINAL.tax_totalamount|formatUserNumber}</td>
</tr>
<!-- Group Tax - ends -->

<tr valign="top">
	<td class="crmTableRow small" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow small" align="right">
		(+)&nbsp;<b>{$APP.LBL_SHIPPING_AND_HANDLING_CHARGES} </b>
	</td>
	<td class="crmTableRow small" align="right">
		<input id="shipping_handling_charge" name="shipping_handling_charge" type="text" class="detailedViewTextBox" style="width:80px" align="right" value="{$FINAL.shipping_handling_charge|formatUserNumber}" onChange="calcSHTax();">
	</td>
</tr>

<tr valign="top">
	<td class="crmTableRow small" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow small" align="right">
		(+)&nbsp;<b><a href="javascript:void(0);" onClick="displayCoords(this,'shipping_handling_div','sh_tax_div_title',''); calcSHTax();" >{$APP.LBL_TAX_FOR_SHIPPING_AND_HANDLING} </a></b>

				<!-- Pop Div For Shipping and Handlin TAX -->
				<div class="discountUI" id="shipping_handling_div">
					<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small">
					   <tr>
						<td id="sh_tax_div_title" class="level3Bg" colspan="3" nowrap align="left" ></td>
					   </tr>
					{foreach item=tax_detail name=sh_loop key=loop_count from=$FINAL.sh_taxes}
					   <tr>
						<td align="left" class="lineOnTop">
							<input type="text" class="detailedViewTextBox input-inline" size="3" name="{$tax_detail.taxname}_sh_percent" id="sh_tax_percentage{$smarty.foreach.sh_loop.iteration}" value="{$tax_detail.percentage|formatUserNumber}" onChange="calcSHTax()">&nbsp;%
						</td>
						<td align="center" class="lineOnTop">{$tax_detail.taxlabel}</td>
						<td align="right" class="lineOnTop">
							<input type="text" class="detailedViewTextBox input-inline" size="4" name="{$tax_detail.taxname}_sh_amount" id="sh_tax_amount{$smarty.foreach.sh_loop.iteration}" style="cursor:pointer;" value="0.00" readonly>
						</td>
					   </tr>
					{/foreach}
					<input type="hidden" id="sh_tax_count" value="{$smarty.foreach.sh_loop.iteration}">
					</table>
					<div class="closebutton" onClick="fnHidePopDiv('shipping_handling_div')"></div>
				</div>
				<!-- End Popup Div for Shipping and Handling TAX -->

	</td>
	<td id="shipping_handling_tax" class="crmTableRow small" align="right">{$FINAL.shtax_totalamount|formatUserNumber}</td>
</tr>

<tr valign="top">
	<td class="crmTableRow small" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow small" align="right">
		{$APP.LBL_ADJUSTMENT}
		<select id="adjustmentType" name="adjustmentType" class="detailedViewTextBox input-inline" onchange="calcTotal();">
			<option value="+">{$APP.LBL_ADJUSTMENT_ADD}</option>
			<option value="-" {if $FINAL.adjustment < 0}selected=""{/if}>{$APP.LBL_DEDUCT}</option>
		</select>
	</td>
	<td class="crmTableRow small" align="right">
		<input id="adjustment" name="adjustment" type="text" class="detailedViewTextBox" style="width:80px" align="right" value="{$FINAL.adjustment|abs|formatUserNumber}" onChange="calcTotal();"> {* crmv@113949 *}
	</td>
</tr>

<tr valign="top">
	<td class="crmTableRow big lineOnTop" style="border-right:1px #dadada;">&nbsp;</td>
	<td class="crmTableRow big lineOnTop" align="right"><b>{$APP.LBL_GRAND_TOTAL}</b></td>
	<td id="grandTotal" name="grandTotal" class="crmTableRow big lineOnTop" align="right">{$FINAL.grandTotal|formatUserNumber}</td>
</tr>

{* crmv@58638 *}
<script type="text/javascript" language="JavaScript">
	jQuery('.inventory_submit').removeAttr('disabled');
</script>
{* crmv@58638e *}