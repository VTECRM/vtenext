{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@42024 - many changes *}
{* crmv@30721 *}

<script type="text/javascript" src="{"include/js/Inventory.js"|resourcever}"></script>
<!-- Added to display the Product Details -->
<script type="text/javascript">
function displayCoords(currObj,obj,mode,curr_row)
{ldelim}
	if(mode != 'discount_final' && mode != 'sh_tax_div_title' && mode != 'group_tax_div_title')
	{ldelim}
		var curr_productid = document.getElementById("hdnProductId"+curr_row).value;
		if(curr_productid == '')
		{ldelim}
			alert("{$APP.PLEASE_SELECT_LINE_ITEM}");
			return false;
		{rdelim}
		var curr_quantity = document.getElementById("qty"+curr_row).value;
		if(curr_quantity == '')
		{ldelim}
			alert("{$APP.PLEASE_FILL_QUANTITY}");
			return false;
		{rdelim}
	{rdelim}

	//Set the Header value for Discount
	if(mode == 'discount')
	{ldelim}
		document.getElementById("discount_div_title"+curr_row).innerHTML = '<b>{$APP.LABEL_SET_DISCOUNT_FOR_X_COLON} '+document.getElementById("productTotal"+curr_row).innerHTML+'</b>';
	{rdelim}
	else if(mode == 'tax')
	{ldelim}
		document.getElementById("tax_div_title"+curr_row).innerHTML = "<b>{$APP.LABEL_SET_TAX_FOR} "+document.getElementById("totalAfterDiscount"+curr_row).innerHTML+'</b>';
	{rdelim}
	else if(mode == 'discount_final')
	{ldelim}
		document.getElementById("discount_div_title_final").innerHTML = '<b>{$APP.LABEL_SET_DISCOUNT_FOR_COLON} '+document.getElementById("netTotal").innerHTML+'</b>';
	{rdelim}
	else if(mode == 'sh_tax_div_title')
	{ldelim}
		document.getElementById("sh_tax_div_title").innerHTML = '<b>{$APP.LABEL_SET_SH_TAX_FOR_COLON} '+document.getElementById("shipping_handling_charge").value+'</b>';
	{rdelim}
	else if(mode == 'group_tax_div_title')
	{ldelim}
		var net_total_after_discount = parseUserNumber(jQuery("#netTotal").html()) - parseUserNumber(jQuery("#discountTotal_final").html());
		jQuery("#group_tax_div_title").html('<b>{$APP.LABEL_SET_GROUP_TAX_FOR_COLON} '+formatUserNumber(net_total_after_discount)+'</b>');
	{rdelim}

	fnvshobj(currObj,'tax_container');
	if(document.all)
	{ldelim}
		var divleft = document.getElementById("tax_container").style.left;
		var divabsleft = divleft.substring(0,divleft.length-2);
		document.getElementById(obj).style.left = eval(divabsleft) - 120;

		var divtop = document.getElementById("tax_container").style.top;
		var divabstop =  divtop.substring(0,divtop.length-2);
		document.getElementById(obj).style.top = eval(divabstop);
	{rdelim}else
	{ldelim}
		document.getElementById(obj).style.left =  document.getElementById("tax_container").left;
		document.getElementById(obj).style.top = document.getElementById("tax_container").top;
	{rdelim}
	document.getElementById(obj).style.display = "block";

{rdelim}

function fnHidePopDiv(obj){ldelim}
	document.getElementById(obj).style.display = 'none';
{rdelim}
</script>

<div id="block_{$PRODBLOCKINFO.blockid}" class="vte-card editBlock" style="{if $PANELID != $PRODBLOCKINFO.panelid}display:none{/if}"> {* crmv@104568 *}
	<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="crmTable" id="proTab">
	{*include file="Inventory/ProductRowEdit.tpl"*}
	</table>

	<table width="100%"  border="0" align="center" cellpadding="5" cellspacing="0" class="crmTable" id="finalProTab">
	{*include file="Inventory/ProductFooterEdit.tpl"*}
	</table>
</div> {* crmv@104568 *}

<input type="hidden" name="totalProductCount" id="totalProductCount" value="{$row_no}">
<input type="hidden" name="subtotal" id="subtotal" value="">
<input type="hidden" name="total" id="total" value="">

{if $CONVERT_MODE eq '' && $smarty.request.convertmode neq ''}
	{assign var="CONVERT_MODE" value=$smarty.request.convertmode}
{/if}
<script type="text/javascript">
	jQuery(document).ready(function () {ldelim}
		jQuery.ajax({ldelim}
			url: 'index.php?module=Utilities&action=UtilitiesAjax&file=getProductsOrServices&rowid=1&mode={$MODE}&rel_module={$MODULE}&record={$smarty.request.record}&return_module={$smarty.request.return_module}&product_id={$smarty.request.product_id}&duplicate_from={$DUPLICATE_FROM}&parent_id={$smarty.request.parent_id}&convertmode={$CONVERT_MODE}&opportunity_id={$smarty.request.opportunity_id}&quote_id={$QUOTE_ID}&salesorder_id={$SALESORDER_ID}&load_header=1&load_footer=1',
			type: 'POST',
			dataType: 'html',
			success: function(data){ldelim}
				var htmlData = data.split('##%%##');
				jQuery('#proTab').append(htmlData[0]);
				if (htmlData.length > 1) jQuery('#finalProTab').append(htmlData[1]);
				decideTaxDiv();
				calcTotal();
				calcSHTax();
			{rdelim}
		{rdelim});
	{rdelim});
</script>