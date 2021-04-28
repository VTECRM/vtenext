/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42024 - many changes

// crmv@150748
window.VTE = window.VTE || {};

VTE.Inventory = VTE.Inventory || {
	
	// crmv@162674
	/**
	 * @deprecated
	 * Please use the more general VTE.submitForm
	 */
	saveRecord: function(self, options) {
		return VTE.submitCompressedForm(self, options);
	}
	// crmv@162674e
	
}
// crmv@150748e

function copyAddressRight(form) {

	if(typeof(form.bill_street) != 'undefined' && typeof(form.ship_street) != 'undefined')
		form.ship_street.value = form.bill_street.value;

	if(typeof(form.bill_city) != 'undefined' && typeof(form.ship_city) != 'undefined')
		form.ship_city.value = form.bill_city.value;

	if(typeof(form.bill_state) != 'undefined' && typeof(form.ship_state) != 'undefined')
		form.ship_state.value = form.bill_state.value;

	if(typeof(form.bill_code) != 'undefined' && typeof(form.ship_code) != 'undefined')
		form.ship_code.value = form.bill_code.value;

	if(typeof(form.bill_country) != 'undefined' && typeof(form.ship_country) != 'undefined')
		form.ship_country.value = form.bill_country.value;

	if(typeof(form.bill_pobox) != 'undefined' && typeof(form.ship_pobox) != 'undefined')
		form.ship_pobox.value = form.bill_pobox.value;

	return true;

}

function copyAddressLeft(form) {

	if(typeof(form.bill_street) != 'undefined' && typeof(form.ship_street) != 'undefined')
		form.bill_street.value = form.ship_street.value;

	if(typeof(form.bill_city) != 'undefined' && typeof(form.ship_city) != 'undefined')
		form.bill_city.value = form.ship_city.value;

	if(typeof(form.bill_state) != 'undefined' && typeof(form.ship_state) != 'undefined')
		form.bill_state.value = form.ship_state.value;

	if(typeof(form.bill_code) != 'undefined' && typeof(form.ship_code) != 'undefined')
		form.bill_code.value =	form.ship_code.value;

	if(typeof(form.bill_country) != 'undefined' && typeof(form.ship_country) != 'undefined')
		form.bill_country.value = form.ship_country.value;

	if(typeof(form.bill_pobox) != 'undefined' && typeof(form.ship_pobox) != 'undefined')
		form.bill_pobox.value = form.ship_pobox.value;

	return true;

}

function settotalnoofrows() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067
	
	var max_row_count = document.getElementById('proTab').rows.length;
        max_row_count = eval(max_row_count)-2;

	//set the total number of products
	document.EditView.totalProductCount.value = max_row_count;
}

// crmv@83877 - moved user number funcs

//crmv@54013
function alertInvalid(id, label) {
	alert(alert_arr.INVALID + label);
	window.setTimeout(function(){
		jQuery('#'+id).focus();
	}, 100);
}
//crmv@54013e

function productPickList(currObj,module,row_no,autocomplete) {	//crmv@29190
	var record_id = '',
		currentRowId = parseInt(currObj.id.match(/([0-9]+)$/)[1]),
		// If we have mismatching rowId and currentRowId, it is due swapping of rows
		rowId = (rowId != currentRowId ? currentRowId : row_no),
		currencyid = document.getElementById("inventory_currency").value,
		popuptype = (module == 'PurchaseOrder' ? 'inventory_prod_po' : 'inventory_prod');

    if (document.getElementsByName("account_id").length != 0)
    	record_id = document.EditView.account_id.value;

	//crmv@21048m	//crmv@29190
    var options = "&return_module="+module+"&currencyid="+currencyid;

	if (record_id != '')
		options += "&relmod_id="+record_id+"&parent_module=Accounts";

	var url = "module=Products&action=Popup&html=Popup_picker&select=enable&form=HelpDeskEditView&popuptype="+popuptype+"&curr_row="+rowId+options;
	if (autocomplete == 'yes')
		return url;
	else
		openPopup("index.php?"+url,"productWin","width=640,height=600,resizable=0,scrollbars=0,status=1,top=150,left=200");
	//crmv@21048me	//crmv@29190e
}

function priceBookPickList(currObj, row_no) {
	var rowId=row_no,
		currencyid = jQuery('#inventory_currency').val(),
		productId = jQuery("#hdnProductId"+rowId).val() || -1;
	//crmv@21048m
	openPopup("index.php?module=PriceBooks&action=Popup&html=Popup_picker&form=EditView&popuptype=inventory_pb&fldname=listPrice"+rowId+"&productid="+productId+"&currencyid="+currencyid,"priceBookWin","width=640,height=565,resizable=0,scrollbars=0,top=150,left=200");
	//crmv@21048m e
}

function deleteRow(module,i,image_path) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var tableName = document.getElementById('proTab');
	var prev = tableName.rows.length;
	var iCount;

	jQuery('#row'+i).hide();

	image_path = document.getElementById("hidImagePath").value;
	iMax = tableName.rows.length;
	// find previous visible index
	for (iCount=i; iCount>=1; iCount--)	{
		if (document.getElementById("row"+iCount) && document.getElementById("row"+iCount).style.display != 'none') {
			iPrevRowIndex = iCount;
			break;
		}
	}
	iPrevCount = iPrevRowIndex;
	oCurRow = eval(document.getElementById("row"+i));
	sTemp = oCurRow.cells[0].innerHTML;
	ibFound = sTemp.indexOf("down_layout.gif");

	if (i != 2 && ibFound == -1 && iPrevCount != 1) {
		oPrevRow = eval(document.getElementById("row"+iPrevCount));

		iPrevCount = eval(iPrevCount);
		// add icon for recycle bin, deleted=0, arrow up (deletes DOWN arrow)
		oPrevRow.cells[0].innerHTML = '<i class="vteicon md-link" onclick="deleteRow(\''+module+'\','+iPrevCount+')">delete</i><input id="deleted'+iPrevCount+'" name="deleted'+iPrevCount+'" type="hidden" value="0"><br/><br/><a href="javascript:moveUpDown(\'UP\',\''+module+'\','+iPrevCount+')" title="Move Upward"><i class="vteicon">arrow_upward</i></a>';

	} else if(iPrevCount == 1) {
		iSwapIndex = i;
		for(iCount=i;iCount<=iMax-2;iCount++) {
			if(document.getElementById("row"+iCount) && document.getElementById("row"+iCount).style.display != 'none') {
				iSwapIndex = iCount;
				break;
			}
		}
		if(iSwapIndex == i)	{
			// enter here when only 1 prod is left
			oPrevRow = eval(document.getElementById("row"+iPrevCount));
			iPrevCount = eval(iPrevCount);
			// delete all arrows and set deleted = 0 for first item
			oPrevRow.cells[0].innerHTML = '<input type="hidden" id="deleted1" name="deleted1" value="0">&nbsp;';
		}
	}

	jQuery("#hdnProductId"+i).val("");
	jQuery("#deleted"+i).val(1);

	calcTotal();
}

// Function to Calcuate the Inventory total including all products
function calcTotal() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	// validation
	if (!validateUserNumber(jQuery('#adjustment').val())) {
		alert(alert_arr.VALID_ADJUSTMENT);
		window.setTimeout(function(){
			jQuery('#adjustment').focus();
		}, 100);
		return;
	}

	var max_row_count = document.getElementById('proTab').rows.length;
	max_row_count = eval(max_row_count)-2; //Because the table has two header rows. so we will reduce two from row length
	var netprice = 0.00;
	for (var i=1; i<=max_row_count; ++i) {
		calcProductTotal(i);
	}
	calcGrandTotal();
}

// Function to Calculate the Total for a particular product in an Inventory
// calculate margin	: crmv@44323 crmv@55228
function calcProductTotal(rowId) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	// skip if deleted
	if (jQuery('#deleted'+rowId).val() != 0) return;

	// do the calculations (using float types)
	var quantity = parseUserNumber(jQuery("#qty"+rowId).val()),
		listprice = parseUserNumber(jQuery("#listPrice"+rowId).val()),
		total = quantity * listprice,
		discountTotal = parseUserNumber(jQuery("#discountTotal"+rowId).html()),
		totalAfterDiscount = total - discountTotal,
		taxTotal = 0.0,
		netprice = totalAfterDiscount,
		tax_type = jQuery('#taxtype').val(),
		unit_cost = jQuery('#unit_cost'+rowId).val(),
		total_cost = unit_cost * quantity,
		margin = '';

	//if the tax type is individual then add the tax with net price
	if (tax_type == 'individual') {
		callTaxCalc(rowId);	//crmv@23660
		netprice += parseUserNumber(jQuery("#taxTotal"+rowId).html());
	}

	// margin
	if (unit_cost != '' && totalAfterDiscount != 0) {
		var t_margin = (totalAfterDiscount - total_cost)/totalAfterDiscount;
		if (!isNaN(t_margin)) {
			margin = Math.round(t_margin * 100) + '%';
		}
	}
	
	// now set the results (formatting them)
	jQuery("#productTotal"+rowId).html(formatUserNumber(total));
	jQuery("#totalAfterDiscount"+rowId).html(formatUserNumber(totalAfterDiscount));
	jQuery("#netPrice"+rowId).html(formatUserNumber(netprice));
	jQuery("#netPriceInput"+rowId).val(netprice); // crmv@29686
	jQuery("#margin"+rowId).html(margin);
}
// crmv@44323e crmv@55228e

// Function to Calculate the Net and Grand total for all the products together of an Inventory
function calcGrandTotal() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var i,
		netTotal = 0.0,
		grandTotal = 0.0,
		discountTotal_final = parseUserNumber(jQuery("#discountTotal_final").html()),
		sh_amount = parseUserNumber(jQuery("#shipping_handling_charge").val()),
		sh_tax = parseUserNumber(jQuery("#shipping_handling_tax").html()),
		adjustment = parseUserNumber(jQuery("#adjustment").val()),
		taxtype = jQuery('#taxtype').val(),
		adj_type = jQuery("#adjustmentType").val(),
		finalTax = (taxtype == 'group' ? parseUserNumber(jQuery("#tax_final").html()) : 0.0);

	// calculate the net total using non deleted products
	jQuery('#proTab input[id^=hdnProductId]').each(function(index, item) {
		var pid = item.id.replace('hdnProductId', '');
		if (jQuery('#deleted'+pid).val() == 0) {
			netTotal += parseUserNumber(jQuery('#netPrice'+pid).html());
		}
	});

	setDiscount(this,'_final');
	calcGroupTax();

	// calc the total, minus discounts + taxes (if present)
	grandTotal = netTotal - discountTotal_final + finalTax;

	// add shipping charges
	if (sh_amount > 0 ) {
		grandTotal += sh_amount + sh_tax;
	}

	// add/subtract adjustment
	if (adjustment > 0) {
		if (adj_type == '+') {
			grandTotal += adjustment;
		} else {
			grandTotal -= adjustment;
		}
	}

	// set the output
	jQuery("#netTotal").html(formatUserNumber(netTotal));
	jQuery("#subtotal").val(netTotal);
	jQuery("#grandTotal").html(formatUserNumber(grandTotal));
	jQuery("#total").val(grandTotal);
}

//This function is used to validate the Inventory modules
function validateInventory(module) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	if (!formValidate()) return false

	//for products, vendors and pricebook modules we won't validate the product details. here return the control
	if (module == 'Products' || module == 'Vendors' || module == 'PriceBooks' || module == 'Services') {
		return true;
	}

	var max_row_count = document.getElementById('proTab').rows.length;
	max_row_count = eval(max_row_count)-2;//As the table has two header rows, we will reduce two from table row length
	//crmv@30721
	if (max_row_count == 0) {
		alert(alert_arr.NO_LINE_ITEM_SELECTED);
		return false;
	}

	if(!FindDuplicate())
		return false;
	//crmv@30721e

	for (var i=1;i<=max_row_count;i++) {
		var qty = jQuery("#qty"+i).val(),
			listPrice = jQuery('#listPrice'+i).val();

		//if the row is deleted then avoid validate that row values
		if (jQuery("#deleted"+i).val() == '1') continue;

		if (jQuery('#hdnProductId'+i).val() <= 0) {
			alertInvalid('productName'+i, alert_arr.LINE_ITEM);
			return false;
		}

		if (!validateUserNumber(qty) || parseUserNumber(qty) <= 0) {
			alertInvalid('qty'+i, 'Qty');
			return false
		}
		if (!validateUserNumber(listPrice)) {
			alertInvalid("listPrice"+i,alert_arr.LIST_PRICE);
			return false
		}
	}

	//Product - Discount validation
	if(!validateProductDiscounts())
		return false;

	//Final Discount validation - not allow negative values
	discount_checks = document.getElementsByName("discount_final");

	//Percentage selected, so validate the percentage
	if(discount_checks[1].checked == true) {
		//crmv@2539m
		var discount_percentage_value = jQuery("#discount_percentage_final").val() || '0',
			discount_percentage_values = trim(discount_percentage_value).split("+");

		for (var j=0; j<discount_percentage_values.length; ++j) {
			if(!validateUserNumber(discount_percentage_values[j])) {
				alert(alert_arr.VALID_FINAL_PERCENT);
				return false;
			}
		}
		//crmv@2539me
	}
	if(discount_checks[2].checked == true) {
		if (!validateUserNumber(jQuery("#discount_amount_final").val())) {
			alert(alert_arr.VALID_FINAL_AMOUNT);
			return false;
		}
	}

	//Shipping & Handling validation - not allow negative values
	if (!validateUserNumber(jQuery("#shipping_handling_charge").val())) {
		alert(alert_arr.VALID_SHIPPING_CHARGE);
		return false;
	}

	//Adjustment validation - allow negative values
	if (!validateUserNumber(jQuery("#adjustment").val())) {
		alert(alert_arr.VALID_ADJUSTMENT);
		return false;
	}

	//Group - Tax Validation  - not allow negative values
	//We need to validate group tax only if taxtype is group.
	var taxtype = jQuery("#taxtype").val();
	if (taxtype == "group") {
		var tax_count = jQuery("#group_tax_count").val();
		for (var i=1; i<=tax_count; ++i) {
			if (!validateUserNumber(jQuery('#group_tax_percentage'+i).val())) {
				alert(alert_arr.VALID_TAX_PERCENT);
				return false;
			}
		}
	}

	//Taxes for Shippring and Handling  validation - not allow negative values
	var shtax_count=document.getElementById("sh_tax_count").value;
	for (var i=1; i<=shtax_count; ++i) {

		temp = /^(0|[1-9]{1}\d{0,})(\.(\d{1}\d{0,}))?$/.test(document.getElementById("sh_tax_percentage"+i).value);
		if(!validateUserNumber(jQuery('#sh_tax_percentage'+i).val())) {
			alert(alert_arr.VALID_SH_TAX);
			return false;
		}
	}

	calcTotal(); /* Product Re-Ordering Feature Code Addition */

	return true;
}

function FindDuplicate() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var duplicate_products = '',
		prodIds = [],
		prodRealIds = [],
		duplicateIds = [],
		i,j;

	// get the ids
	jQuery('#proTab input[id^=hdnProductId]').each(function(index, item) {
		if (item.value > 0) {
			prodIds.push(item.id.replace('hdnProductId', ''));
			prodRealIds.push(item.value);
		}
	});

	// scan the array
	for (i=0; i<prodRealIds.length; ++i) {
		for (j=i+1; j<prodRealIds.length; ++j) {
			if (prodRealIds[i] == prodRealIds[j] && duplicateIds.indexOf(prodRealIds[i]) == -1) {
				duplicateIds.push(prodRealIds[i]);
				duplicate_products += jQuery('#productName'+prodIds[i]).val() + "\n";
			}
		}
	}

	if (duplicateIds.length > 0) {
		if (!confirm(alert_arr.SELECTED_MORE_THAN_ONCE + "\n" + duplicate_products + "\n" + alert_arr.WANT_TO_CONTINUE)) {
			return false;
		}
	}

	return true;
}

// crmv@192033
function loadTaxes_Ajax(curr_row) {
//Retrieve all the tax values for the currently selected product
	var lineItemType = jQuery("#lineItemType"+curr_row).val();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module='+lineItemType+'&action='+lineItemType+'Ajax&file=InventoryTaxAjax&productid='+jQuery("#hdnProductId"+curr_row).val()+'&curr_row='+curr_row+'&productTotal='+jQuery('#totalAfterDiscount'+curr_row).html(),
		success: function(result) {
			jQuery("#tax_div"+curr_row).html(result);
			jQuery("#taxTotal"+curr_row).html( formatUserNumber(jQuery('#hdnTaxTotal'+curr_row).val()) );
			calcTotal();
			callTaxCalc(curr_row); //crmv@65492
			calcTotal();	// crmv@92378 - added to refresh the product total with the tax total
		}
	});
}
// crmv@192033e

function fnAddTaxConfigRow(sh) {

	var table_id = 'add_tax';
	var td_id = 'td_add_tax';
	var label_name = 'addTaxLabel';
	var label_val = 'addTaxValue';
	var add_tax_flag = 'add_tax_type';

	if(sh != '' && sh == 'sh')
	{
		table_id = 'sh_add_tax';
		td_id = 'td_sh_add_tax';
		label_name = 'sh_addTaxLabel';
		label_val = 'sh_addTaxValue';
		add_tax_flag = 'sh_add_tax_type';
	}
	var tableName = document.getElementById(table_id);
	var prev = tableName.rows.length;
   	var row = tableName.insertRow(0);

	var colone = row.insertCell(0);
	var coltwo = row.insertCell(1);

	colone.className = "cellLabel small";
	coltwo.className = "cellText small";

	colone.innerHTML="<div class='dvtCellInfo'><input type='text' id='"+label_name+"' name='"+label_name+"' value='"+tax_labelarr.TAX_NAME+"' class='detailedViewTextBox' onclick=\"this.form."+label_name+".value=''\";/></div>";
	coltwo.innerHTML="<div class='dvtCellInfo'><input type='text' id='"+label_val+"' name='"+label_val+"' value='"+tax_labelarr.TAX_VALUE+"' class='detailedViewTextBox' onclick=\"this.form."+label_val+".value=''\";/></div>";

	document.getElementById(td_id).innerHTML="<input type='submit' name='Save' value=' "+tax_labelarr.SAVE_BUTTON+" ' class='crmButton small save' onclick=\"this.form.action.value='TaxConfig'; this.form."+add_tax_flag+".value='true'; this.form.parenttab.value='Settings'; return validateNewTaxType('"+label_name+"','"+label_val+"');\">&nbsp;<input type='submit' name='Cancel' value=' "+tax_labelarr.CANCEL_BUTTON+" ' class='crmButton small cancel' onclick=\"this.form.action.value='TaxConfig'; this.form.module.value='Settings'; this.form."+add_tax_flag+".value='false'; this.form.parenttab.value='Settings';\">";
}

function validateNewTaxType(fieldname, fieldvalue) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	if(trim(document.getElementById(fieldname).value)== '')
	{
		alert(alert_arr.VALID_TAX_NAME);
		return false;
	}
	if(trim(document.getElementById(fieldvalue).value)== '')
	{
		alert(alert_arr.CORRECT_TAX_VALUE);
		return false;
	}
	else
	{
		if(!validateUserNumber(document.getElementById(fieldvalue).value)) // crmv@118512
		{
			alert(alert_arr.NOT_VALID_ENTRY); //crmv@43358
			return false;
		}
	}

	return true;
}

// crmv@118512
function validateTaxes(countname) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var taxcount = parseInt(jQuery('#'+countname).val())+1; // crmv@198415

	if(countname == 'tax_count') {
		var taxprefix = 'tax';
		var taxLabelPrefix = 'taxlabel_tax';
	} else {
		var taxprefix = 'shtax';
		var taxLabelPrefix = 'taxlabel_shtax';
	}

	for (var i=1;i<=taxcount;i++) {
		var taxname = taxprefix + i;
		var labelname = taxLabelPrefix + i;
		var taxval = jQuery('#'+taxname).val();
		var taxLabelVal = jQuery('#'+labelname).val();
		
		// remove spaces from label
		jQuery('#'+labelname).val(taxLabelVal.replace(/\s+/g,''));

		// check if label empty
		if (!jQuery('#'+(taxLabelPrefix+i)).val()) {
			alert(alert_arr.LABEL_SHOULDNOT_EMPTY);
			return false
		}

		//Tax value - numeric validation
		if(!validateUserNumber(taxval)) {
			alert("'"+taxval+"' "+alert_arr.NOT_VALID_ENTRY);
			return false;
		}
	}
	return true;
}
// crmv@118512e

function decideTaxDiv() {
	var taxtype = document.getElementById("taxtype").value;

	calcTotal();

	if (taxtype == 'group') {
		//if group tax selected then we have to hide the individual taxes and also calculate the group tax
		hideIndividualTaxes();
		calcGroupTax();
	} else if (taxtype == 'individual') {
		hideGroupTax();
	}

}

function hideIndividualTaxes() {
	jQuery('#proTab tr[id^=individual_tax_row]').removeClass('TaxShow').addClass('TaxHide');
	jQuery('#proTab td[id^=taxTotal]').hide();
	jQuery('#finalProTab #group_tax_row').removeClass('TaxHide').addClass('TaxShow');
}

function hideGroupTax() {
	jQuery('#proTab tr[id^=individual_tax_row]').removeClass('TaxHide').addClass('TaxShow');
	jQuery('#proTab td[id^=taxTotal]').show();
	jQuery('#finalProTab #group_tax_row').removeClass('TaxShow').addClass('TaxHide');
}

function setDiscount(currObj,curr_row) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	// validation
	if (curr_row != '_final') {
		if (!validateUserNumber(jQuery('#qty'+curr_row).val())) {
			alertInvalid('qty'+curr_row, 'Qty');
			return;
		}
		if (!validateUserNumber(jQuery('#listPrice'+curr_row).val())) {
			alertInvalid('listPrice'+curr_row, alert_arr.LIST_PRICE);
			return;
		}
	}

	// stock check
	if (gVTModule == 'Invoice' && jQuery('#lineItemType'+curr_row).val() != 'Services') {
		stock_alert(curr_row);
	}

	var i, j,
		discount_amount = 0.0,
		discount_checks = document.getElementsByName("discount"+curr_row) || [];

	if (discount_checks[0].checked == true) {
		jQuery("#discount_type"+curr_row).val('zero');
		jQuery("#discount_percentage"+curr_row).hide();
		jQuery("#discount_amount"+curr_row).hide();

	} else if(discount_checks[1].checked == true) {
		jQuery("#discount_type"+curr_row).val('percentage');
		jQuery("#discount_percentage"+curr_row).show();
		jQuery("#discount_amount"+curr_row).hide();

		//This is to calculate the final discount
		//crmv@2539m
		var discount_percentage_final_value = trim(jQuery("#discount_percentage"+curr_row).val()) || '0',
			discount_percentage_final_values = discount_percentage_final_value.split("+"),
			totalName = (curr_row == '_final' ? 'netTotal' : "productTotal"+curr_row ),
			total_tmp = parseUserNumber(jQuery('#'+totalName).html());

		if (discount_percentage_final_values.length > 5) {
			alert(alert_arr.VALID_DISCOUNT_PERCENT);
			return false;
		}

		// crmv@193848 - round the discount only at the end
		var new_total = total_tmp;
		for (i=0; i<discount_percentage_final_values.length; ++i) {
			var discountPerc = parseUserNumber(discount_percentage_final_values[i]);

			// check validity
			if (discountPerc < 0 || discountPerc > 100) {
				alert(alert_arr.VALID_DISCOUNT_PERCENT);
				return false;
			}

			new_total -= new_total * discountPerc / 100.0;
		}
		new_total = roundValueFloat(new_total);
		discount_amount = total_tmp - new_total;
		// crmv@193848e
		
		//crmv@2539me

	} else if(discount_checks[2].checked == true) {

		jQuery("#discount_type"+curr_row).val('amount');
		jQuery("#discount_percentage"+curr_row).hide();
		jQuery("#discount_amount"+curr_row).show();

		if (!validateUserNumber(jQuery('#discount_amount'+curr_row).val())) {
			alertInvalid('discount_amount'+curr_row, alert_arr.VALID_DISCOUNT_AMOUNT);
			return;
		}

		discount_amount = parseUserNumber(jQuery("#discount_amount"+curr_row).val());
	}

	jQuery("#discountTotal"+curr_row).html(formatUserNumber(discount_amount));

	// Update product total as discount would have changed.
	if (curr_row != '_final') {
		calcProductTotal(curr_row);
	}

}

//This function is added to call the tax calculation function
function callTaxCalc(curr_row) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	//when we change discount or list price, we have to calculate the taxes again before calculate the total
	var tax_count = jQuery('#tax_table'+curr_row+' tr').length-1; //subtract the title tr length
	for (var i=0; i<tax_count; i++)	{
		var tax_hidden_name = "hidden_tax"+(i+1)+"_percentage"+curr_row,
			tax_name = jQuery('#'+tax_hidden_name).val();
		calcCurrentTax(tax_name,curr_row,i);
	}
}

function calcCurrentTax(tax_name, curr_row, tax_row) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var product_total = parseUserNumber(jQuery("#totalAfterDiscount"+curr_row).html()),
		new_tax_percent = parseUserNumber(jQuery('#'+tax_name).val()),
		new_amount_lbl = jQuery("#popup_tax_row"+curr_row+'_'+tax_row),

		//calculate the new tax amount
		new_tax_amount = product_total * new_tax_percent / 100.0,
		tax_total = 0.00;
		
	// assign the new tax amount in the corresponding text box
	new_amount_lbl.val(formatUserNumber(new_tax_amount));

	// recalculate total
	jQuery('#tax_table'+curr_row+' input[id^=popup_tax_row'+curr_row+']').each(function(index, item) {
		tax_total += parseUserNumber(item.value);
	});

	jQuery("#taxTotal"+curr_row).html(formatUserNumber(tax_total));
}

function calcGroupTax() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var group_tax_count = jQuery("#group_tax_count").val(),
		netTotal_value = parseUserNumber(jQuery("#netTotal").html()),
		discountTotal_final_value = parseUserNumber(jQuery("#discountTotal_final").html()),
		net_total_after_discount = netTotal_value - discountTotal_final_value,
		group_tax_total = 0.00,
		total_percentage = 0.00;

	for (var i=1; i<=group_tax_count; ++i) {
		// validation
		if (!validateUserNumber(jQuery('#group_tax_percentage'+i).val())) {
			alertInvalid('group_tax_percentage'+i, alert_arr.VALID_TAX_PERCENT);
			return;
		}

		var group_tax_percentage = parseUserNumber(jQuery("#group_tax_percentage"+i).val()),
			tax_amount = roundValueFloat(net_total_after_discount * group_tax_percentage / 100.0);
		if (group_tax_percentage < -100 || group_tax_percentage > 100) { //crmv@43358
			alert(alert_arr.VALID_TAX_PERCENT+' : '+group_tax_percentage);
			return false;
		}

		group_tax_total += tax_amount;
		jQuery("#group_tax_amount"+i).val(formatUserNumber(tax_amount));
	}

	jQuery("#tax_final").html(formatUserNumber(group_tax_total));
}


function calcSHTax() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	// validation
	if (!validateUserNumber(jQuery('#shipping_handling_charge').val())) {
		alertInvalid('shipping_handling_charge', alert_arr.VALID_SHIPPING_CHARGE);
		return;
	}

	var sh_tax_count = jQuery("#sh_tax_count").val(),
		sh_charge = parseUserNumber(jQuery("#shipping_handling_charge").val()),
		sh_tax_total = 0.00,
		total_tax = 0.00;

	// ROUND NUMBERS AT EVERY ROUND
	for (var i=1; i<=sh_tax_count; ++i) {
		// validation
		if (!validateUserNumber(jQuery('#sh_tax_percentage'+i).val())) {
			alertInvalid('sh_tax_percentage'+i, alert_arr.VALID_SH_TAX);
			return;
		}

		var sh_tax_percentage = parseUserNumber(jQuery("#sh_tax_percentage"+i).val()),
			tax_amount = roundValueFloat(sh_charge * sh_tax_percentage / 100.0);

		if (sh_tax_percentage < -100 || sh_tax_percentage > 100) { //crmv@43358
			alert(alert_arr.VALID_SH_TAX);
			return false;
		}

		sh_tax_total += tax_amount;
		jQuery("#sh_tax_amount"+i).val(formatUserNumber(tax_amount));
	}


	jQuery("#shipping_handling_tax").html(formatUserNumber(sh_tax_total));
	calcTotal();
}

function validateProductDiscounts() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var max_row_count = document.getElementById('proTab').rows.length;
	max_row_count = eval(max_row_count)-2;//As the table has two header rows, we will reduce two from table row length

	for(var i=1; i<=max_row_count; ++i) {
		//if the row is deleted then avoid validate that row values
		if(jQuery("#deleted"+i).val() == '1')	continue;

		discount_checks = document.getElementsByName("discount"+i);

		//Percentage selected, so validate the percentage
		if(discount_checks[1].checked == true) {
			//crmv@2539m
			var discount_percentage_value = jQuery("#discount_percentage"+i).val() || '0',
				discount_percentage_values = trim(discount_percentage_value).split("+");
			for (var j=0; j<discount_percentage_values.length; ++j) {
				if(!validateUserNumber(discount_percentage_values[j])) {
					alert(alert_arr.VALID_DISCOUNT_PERCENT+' : '+discount_percentage_values[j]);
					return false;
				}
			}
			//crmv@2539me
		}

		if(discount_checks[2].checked == true) {
			if(!validateUserNumber(jQuery('#discount_amount'+i).val())) {
				alert(alert_arr.VALID_DISCOUNT_AMOUNT);
				return false;
			}
		}
	}
	return true;
}

function stock_alert(curr_row) {
	var stock = parseUserNumber(jQuery("#qtyInStock"+curr_row).html()),
       	qty = parseUserNumber(jQuery("#qty"+curr_row).val());

	if(eval(qty) > eval(stock)) {
		jQuery("#stock_alert"+curr_row).html('<font color="red" size="1">'+alert_arr.STOCK_IS_NOT_ENOUGH+'</font>');
	} else {
		jQuery("#stock_alert"+curr_row).html('');
	}
	//getObj("stock_alert"+curr_row).innerHTML='<font color="red" size="1">'+alert_arr.INVALID_QTY+'</font>';
}

// Function to Get the price for all the products of an Inventory based on the Currency choosen by the User
function updatePrices() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var prev_cur = document.getElementById('prev_selected_currency_id');
	var inventory_currency = document.getElementById('inventory_currency');
	if(confirm(alert_arr.MSG_CHANGE_CURRENCY_REVISE_UNIT_PRICE)) {
		var productsListElem = document.getElementById('proTab');
		if (productsListElem == null) return;

		var max_row_count = productsListElem.rows.length;
		max_row_count = eval(max_row_count)-2;//Because the table has two header rows. so we will reduce two from row length

	    var products_list = "";
		for(var i=1;i<=max_row_count;i++)
		{
			var productid = document.getElementById("hdnProductId"+i).value;
			if (i != 1)
				products_list = products_list + "::";
			products_list = products_list + productid;
		}

		if (prev_cur != null && inventory_currency != null)
			prev_cur.value = inventory_currency.value;

		var currency_id = inventory_currency.value;
		//Retrieve all the prices for all the products in currently selected currency
		// crmv@192033
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Products&action=ProductsAjax&file=InventoryPriceAjax&currencyid='+currency_id+'&productsList='+products_list,
			success: function(result) {
				if(trim(result).indexOf('SUCCESS') == 0) {
					var res = trim(result).split("$");
					updatePriceValues(res[1]);
				} else {
					alert(alert_arr.OPERATION_DENIED);
				}
			}
		});
		// crmv@192033e
	} else {
		if (prev_cur != null && inventory_currency != null)
			inventory_currency.value = prev_cur.value;
	}
}

// Function to Update the price for the products in the Inventory Edit View based on the Currency choosen by the User.
function updatePriceValues(pricesList) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	if (pricesList == null || pricesList == '') return;
	var prices_list = pricesList.split("::");

	var productsListElem = document.getElementById('proTab');
	if (productsListElem == null) return;

	var max_row_count = productsListElem.rows.length;
	max_row_count = eval(max_row_count)-2;//Because the table has two header rows. so we will reduce two from row length

    var products_list = "";
	for(var i=1;i<=max_row_count;i++)
	{
		var list_price_elem = document.getElementById("listPrice"+i);
		var unit_price = prices_list[i-1]; // Price values index starts from 0
		list_price_elem.value = unit_price;

		// Set Direct Discout amount to 0
		var discount_amount = document.getElementById("discount_amount"+i);
		if(discount_amount != null) discount_amount.value = '0';

		calcProductTotal(i);
		setDiscount(list_price_elem,i);
		callTaxCalc(i);
	}
	resetSHandAdjValues();
	calcTotal();
}

// Function to Reset the S&H Charges and Adjustment value with change in Currency
function resetSHandAdjValues() {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var sh_amount = document.getElementById('shipping_handling_charge');
	if (sh_amount != null) sh_amount.value = '0';

	var sh_amount_tax = document.getElementById('shipping_handling_tax');
	if (sh_amount_tax != null) sh_amount_tax.innerHTML = '0';

	var adjustment = document.getElementById('adjustment');
	if (adjustment != null) adjustment.value = '0';

	var final_discount = document.getElementById('discount_amount_final');
	if (final_discount != null) final_discount.value = '0';
}
// End



/** Function for Product Re-Ordering Feature Code Addition Starts
 * It will be responsible for moving record up/down, 1 step at a time
 */
function moveUpDown(sType,oModule,iIndex) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return; // crmv@65067

	/* crmv@16267 crmv@29686 crmv@55232 */
	var aFieldIds = Array('hidtax_row_no','productName','subproduct_ids','comment','qty','listPrice','discount_type','discount_percentage','discount_amount','popup_tax_row','lineItemType','productDescription','hdnProductcode','netPriceInput','unit_cost','hdnProductId'); // crmv@102215 - hdnProductId va spostato in fondo
	var aContentIds = Array('qtyInStock','netPrice','subprod_names');
	var aOnClickHandlerIds = Array('searchIcon');

	iIndex = eval(iIndex) + 1;
	var oTable = document.getElementById('proTab');
	iMax = oTable.rows.length;
	iSwapIndex = 1;
	if(sType == 'UP')
	{
		for(iCount=iIndex-2;iCount>=1;iCount--)
		{
			if(document.getElementById("row"+iCount))
			{
				if(document.getElementById("row"+iCount).style.display != 'none' && document.getElementById('deleted'+iCount).value == 0)
				{
					iSwapIndex = iCount+1;
					break;
				}
			}
		}
	}
	else
	{
		for(iCount=iIndex;iCount<=iMax-2;iCount++)
		{
			if(document.getElementById("row"+iCount) && document.getElementById("row"+iCount).style.display != 'none' && document.getElementById('deleted'+iCount).value == 0)
			{
				iSwapIndex = iCount;
				break;
			}
		}
		iSwapIndex += 1;
	}

	var oCurTr = oTable.rows[iIndex];
	var oSwapRow = oTable.rows[iSwapIndex];

	iMaxCols = oCurTr.cells.length;
	iIndex -= 1;
	iSwapIndex -= 1;

	iCheckIndex = 0;
	iSwapCheckIndex = 0;
	for(j=0;j<=2;j++)
	{
		if(eval('document.getElementById(\'frmEditView\').discount'+iIndex+'['+j+']'))
		{
			sFormElement = eval('document.getElementById(\'frmEditView\').discount'+iIndex+'['+j+']');
			if(sFormElement.checked)
			{
				iCheckIndex = j;
				break;
			}
		}
	}

	for(j=0;j<=2;j++)
	{
		if(eval('document.getElementById(\'frmEditView\').discount'+iSwapIndex+'['+j+']'))
		{
			sFormElement = eval('document.getElementById(\'frmEditView\').discount'+iSwapIndex+'['+j+']');
			if(sFormElement.checked)
			{
				iSwapCheckIndex = j;
				break;
			}
		}
	}
	if(eval('document.getElementById(\'frmEditView\').discount'+iIndex+'['+iSwapCheckIndex+']'))
	{
		oElement = eval('document.getElementById(\'frmEditView\').discount'+iIndex+'['+iSwapCheckIndex+']');
		oElement.checked = true;
	}
	if(eval('document.getElementById(\'frmEditView\').discount'+iSwapIndex+'['+iCheckIndex+']'))
	{
		oSwapElement = eval('document.getElementById(\'frmEditView\').discount'+iSwapIndex+'['+iCheckIndex+']');
		oSwapElement.checked = true;
	}

	iMaxElement = aFieldIds.length;
	for(iCt=0;iCt<iMaxElement;iCt++)
	{
		sId = aFieldIds[iCt] + iIndex;
		sSwapId = aFieldIds[iCt] + iSwapIndex;
		if(document.getElementById(sId) && document.getElementById(sSwapId))
		{
			sTemp = document.getElementById(sId).value;
			document.getElementById(sId).value = document.getElementById(sSwapId).value;
			document.getElementById(sSwapId).value = sTemp;
			//crmv@30721
			if (aFieldIds[iCt] == 'hdnProductId') {
				if (document.getElementById(sId).value != '') {
					disableReferenceField(document.getElementById('productName'+iIndex));
				} else {
					resetReferenceField(document.getElementById('productName'+iIndex));
				}
				initAutocompleteInventoryRow(document.getElementById('lineItemType'+iIndex).value,'hdnProductId'+iIndex,'productName'+iIndex,getObj('searchIcon'+iIndex),oModule,iIndex,'yes'); //crmv@102215
				if (document.getElementById(sSwapId).value != '') {
					disableReferenceField(document.getElementById('productName'+iSwapIndex));
				} else {
					resetReferenceField(document.getElementById('productName'+iSwapIndex));
				}
				initAutocompleteInventoryRow(document.getElementById('lineItemType'+iSwapIndex).value,'hdnProductId'+iSwapIndex,'productName'+iSwapIndex,getObj('searchIcon'+iSwapIndex),oModule,iSwapIndex,'yes'); //crmv@102215
			}
			//crmv@30721e
		}
		//oCurTr.cells[iCt].innerHTML;
	}
	
	iMaxElement = aContentIds.length;
	for(iCt=0;iCt<iMaxElement;iCt++)
	{
		sId = aContentIds[iCt] + iIndex;
		sSwapId = aContentIds[iCt] + iSwapIndex;
		if(document.getElementById(sId) && document.getElementById(sSwapId))
		{
			sTemp = document.getElementById(sId).innerHTML;
			document.getElementById(sId).innerHTML = document.getElementById(sSwapId).innerHTML;
			document.getElementById(sSwapId).innerHTML = sTemp;
		}
	}
	
	iMaxElement = aOnClickHandlerIds.length;
	for(iCt=0;iCt<iMaxElement;iCt++)
	{
		sId = aOnClickHandlerIds[iCt] + iIndex;
		sSwapId = aOnClickHandlerIds[iCt] + iSwapIndex;
		if(document.getElementById(sId) && document.getElementById(sSwapId))
		{
			sTemp = document.getElementById(sId).onclick;
			document.getElementById(sId).onclick = document.getElementById(sSwapId).onclick;
			document.getElementById(sSwapId).onclick = sTemp;

			sTemp = document.getElementById(sId).src;
			document.getElementById(sId).src = document.getElementById(sSwapId).src;
			document.getElementById(sSwapId).src = sTemp;

			sTemp = document.getElementById(sId).title;
			document.getElementById(sId).title = document.getElementById(sSwapId).title;
			document.getElementById(sSwapId).title = sTemp;
		}
	}
	
	moveUpDownTaxes(iIndex,iSwapIndex);	//crmv@55228

	settotalnoofrows();
	
	// this has to stay here, or the discounts won't be calculated correctly
	calcTotal(); // crmv@144058

	//loadTaxes_Ajax(iIndex);
	//loadTaxes_Ajax(iSwapIndex);
	//callTaxCalc(iIndex);
	//callTaxCalc(iSwapIndex);
	setDiscount(this,iIndex);
	setDiscount(this,iSwapIndex);

	//sId = 'tax1_percentage' + iIndex;
	sTaxRowId = 'hidtax_row_no' + iIndex;
	if(document.getElementById(sTaxRowId))
	{
		if(!(iTaxVal = document.getElementById(sTaxRowId).value))
			iTaxVal = 0;
		//calcCurrentTax(sId,iIndex,iTaxVal);
	}
	//sSwapId = 'tax1_percentage' + iSwapIndex;
	sSwapTaxRowId = 'hidtax_row_no' + iSwapIndex;
	if(document.getElementById(sSwapTaxRowId))
	{
		if(!(iSwapTaxVal = document.getElementById(sSwapTaxRowId).value))
			iSwapTaxVal = 0;
		//calcCurrentTax(sSwapId,iSwapIndex,iSwapTaxVal);
	}
	calcTotal();
}

//crmv@55228	crmv@55232
function moveUpDownTaxes(iIndex,iSwapIndex) {
	var tax_percentage = {};
	var div1 = jQuery('#tax_div'+iIndex).html();
	var div2 = jQuery('#tax_div'+iSwapIndex).html();
	
	for(j = 0; j < 2; j++) {
		if (j == 0) {
			var div = div1;
			var oldIndex = iIndex;
			var newIndex = iSwapIndex;
		} else {
			var div = div2;
			var oldIndex = iSwapIndex;
			var newIndex = iIndex;
		}
		div = div
			.replace(new RegExp('tax_div'+oldIndex,'g'), 'tax_div'+newIndex)
			.replace(new RegExp('tax_table'+oldIndex,'g'), 'tax_table'+newIndex)
			.replace(new RegExp('tax_div_title'+oldIndex,'g'), 'tax_div_title'+newIndex)
			.replace(new RegExp('hdnTaxTotal'+oldIndex,'g'), 'hdnTaxTotal'+newIndex);
		var rowsTaxes = document.getElementById('tax_table'+oldIndex).rows.length - 1;
		if (rowsTaxes > 0) {
			for(i = 0; i < rowsTaxes; i++) {
				var tmp = jQuery('#hidden_tax'+(i+1)+'_percentage'+oldIndex).val();
				tmp = tmp.split('_');
				var taxname = tmp[0];
				div = div
					.replace(new RegExp('hidden_tax'+(i+1)+'_percentage'+oldIndex,'g'), 'hidden_tax'+(i+1)+'_percentage'+newIndex)
					.replace("calcCurrentTax('"+taxname+"_percentage"+oldIndex+"',"+oldIndex, "calcCurrentTax('"+taxname+"_percentage"+newIndex+"',"+newIndex)
					.replace(new RegExp(taxname+'_percentage'+oldIndex,'g'), taxname+'_percentage'+newIndex)
					.replace(new RegExp('popup_tax_row'+oldIndex+'_'+i,'g'), 'popup_tax_row'+newIndex+'_'+i);
				tax_percentage[taxname+'_percentage'+newIndex] = jQuery('#'+taxname+'_percentage'+oldIndex).val();
			}
		}
		if (j == 0) {
			var div1 = div;
		} else {
			var div2 = div;
		}
	}
	
	jQuery('#tax_div'+iIndex).html(div2);
	jQuery('#tax_div'+iSwapIndex).html(div1);
	jQuery("#taxTotal"+iIndex).html(formatUserNumber(jQuery('#hdnTaxTotal'+iIndex).val()));
	jQuery("#taxTotal"+iSwapIndex).html(formatUserNumber(jQuery('#hdnTaxTotal'+iSwapIndex).val()));
	
	for (k in tax_percentage) {
		jQuery('#'+k).val(tax_percentage[k]);
	}
}
//crmv@55228e	crmv@55232e

// crmv@107661
function InventorySelectAll(mod,image_pth) {
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067
	
	function addFromRecordId(entityid, rowid, rawjson) {
		//crmv@32840
		var prod_array = JSON.parse(rawjson || document.getElementById('popup_product_'+entityid).attributes['vt_prod_arr'].nodeValue, function (key, value) { // crmv@192033
		    if (key == 'desc') {
		        value = value.replace(/\\r/g, '\r');
		        value = value.replace(/\\n/g, '\n');
		    }
		    return value;
		});
		//crmv@32840e
		if (!prod_array) return false;
		var prod_id = prod_array['entityid'];
		var prod_name = prod_array['prodname'];
		var prod_code = prod_array['prod_code'];	//crmv@16267
		var unit_price = prod_array['unitprice'];
		var taxstring = prod_array['taxstring'];
		var desc = prod_array['desc'];
		var row_id = rowid || prod_array['rowid'];
		var subprod_ids = prod_array['subprod_ids'];
		var unit_cost = prod_array['unit_cost']; // crmv@204432
		
		// remove from the list of other pages
		if (window.otherPagesSelections) delete otherPagesSelections[prod_id]; // crmv@107661
		
		if(mod!='PurchaseOrder') {
			var qtyinstk = prod_array['qtyinstk'];
			set_return_inventory(prod_id,prod_name,unit_price,qtyinstk,taxstring,parseInt(row_id),desc,subprod_ids,prod_code,unit_cost);	//crmv@16267 crmv@204432
		} else {
			set_return_inventory_po(prod_id,prod_name,unit_price,taxstring,parseInt(row_id),desc,subprod_ids,prod_code,unit_cost);	//crmv@16267 crmv@204432
		}

		return true;
	}

    if(document.selectall.selected_id != undefined)
    {
		var x = document.selectall.selected_id.length;
		var y = 0;
		var idstring = "";
		if ( x == undefined) {
			if (document.selectall.selected_id.checked) {
				idstring = document.selectall.selected_id.value;
				var c = document.selectall.selected_id.value;
				addFromRecordId(c);
				y=1;
			} else {
				alert(alert_arr.SELECT);
				return false;
			}
		} else {
			var row_id = null;
			for(i = 0; i < x ; ++i) {
				if(document.selectall.selected_id[i].checked) {
					idstring = document.selectall.selected_id[i].value+";"+idstring;
					var c = document.selectall.selected_id[i].value;
					if (y>0) {
						row_id = parent.fnAddProductOrServiceRowNew(mod,image_pth, document.selectall.pmodule.value);	//crmv@21048m	//crmv@30721
					}
					addFromRecordId(c, row_id);
					++y;
				}
			}
		}
		
		// add items from other pages
		if (window.otherPagesSelections) {
			var row_id = null;
			for (c in window.otherPagesSelections) {
				if (y>0) {
					row_id = parent.fnAddProductOrServiceRowNew(mod,image_pth, document.selectall.pmodule.value);	//crmv@21048m	//crmv@30721
				}
				addFromRecordId(c, row_id, window.otherPagesSelections[c]);
				++y;
			}
		}
		
		if (y != 0) {
			document.selectall.idlist.value=idstring;
			return true;
		} else {
			alert(alert_arr.SELECT);
			return false;
		}
    }
}
// crmv@107661e

//crmv@30721
function fnAddProductOrServiceRowNew(module,image_path,rel_module,mode){
	if (checkJSOverride(arguments)) return callJSOverride(arguments); //crmv@35654
	if (checkJSExtension(arguments)) if (!callJSExtension(arguments)) return false; // crmv@65067

	var tableName = document.getElementById('proTab');
	var prev = tableName.rows.length;
	var count = eval(prev)-1;//As the table has two headers, we should reduce the count

	var res = getFile('index.php?module=Utilities&action=UtilitiesAjax&file=getProductsOrServices&mode='+mode+'&rowid='+count+'&rel_module='+module+'&entityType='+rel_module);
	jQuery('#proTab > tbody').append(res); // crmv@126296

	/* Product Re-Ordering Feature Code Addition Starts */
	iMax = tableName.rows.length;
	for(iCount=1;iCount<=iMax-3;iCount++)
	{
		if(document.getElementById("row"+iCount) && document.getElementById("row"+iCount).style.display != 'none')
		{
			iPrevRowIndex = iCount;
		}
	}
	iPrevCount = eval(iPrevRowIndex);
	var oPrevRow = tableName.rows[iPrevRowIndex+1];
	if(iPrevCount != 1)
	{
		oPrevRow.cells[0].innerHTML = '<i class="vteicon md-link" onclick="deleteRow(\''+module+'\','+iPrevCount+')">delete</i><input id="deleted'+iPrevCount+'" name="deleted'+iPrevCount+'" type="hidden" value="0"><br/><br/><a href="javascript:moveUpDown(\'UP\',\''+module+'\','+iPrevCount+')" title="Move Upward"><i class="vteicon">arrow_upward</i></a>&nbsp;<a href="javascript:moveUpDown(\'DOWN\',\''+module+'\','+iPrevCount+')" title="Move Downward"><i class="vteicon">arrow_downward</i></a>';
	}
	else
	{
		oPrevRow.cells[0].innerHTML = '<input id="deleted'+iPrevCount+'" name="deleted'+iPrevCount+'" type="hidden" value="0"><br/><br/><a href="javascript:moveUpDown(\'DOWN\',\''+module+'\','+iPrevCount+')" title="Move Downward"><i class="vteicon">arrow_downward</i></a>';
	}
	/* Product Re-Ordering Feature Code Addition ends */

	decideTaxDiv();
	calcTotal();

	return count;
}
//crmv@30721e

//crmv@29190 crmv@198024
function initAutocompleteInventoryRow(rel_module,field,display,icon,module,row_no,autocomplete, useCategories) {
	if (rel_module == 'Products') {
		initAutocomplete(field,display,encodeURIComponent(productPickList(icon,module,row_no,autocomplete)), '', useCategories); 
	} else if (rel_module == 'Services') {
		initAutocomplete(field,display,encodeURIComponent(servicePickList(icon,module,row_no,autocomplete)), '', useCategories);
	}
}
//crmv@29190e crmv@198024e