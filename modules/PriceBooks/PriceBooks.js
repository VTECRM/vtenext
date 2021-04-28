/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

prod_array = [];
selectedCheckboxes = {};

function addToPriceBook()
{
	let x = document.addToPB.selected_id.length;
	let prod_array = new Array(x);
	let idstring = "";

	if ( x == undefined)
	{
		if (document.addToPB.selected_id.checked)
		{
			yy = document.addToPB.selected_id.value+"_listprice";
			document.addToPB.idlist.value=document.addToPB.selected_id.value;

			var elem = document.addToPB.elements;
			var ele_len =elem.length;
			var i=0,j=0;

			for(i=0; i<ele_len; i++)
			{
				if(elem[i].name == yy)
				{
					if (elem[i].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
					{
						alert(alert_arr.LISTPRICE_CANNOT_BE_EMPTY);
						return false;
					}
					else if(isNaN(parseUserNumber(elem[i].value))) // crmv@173281
					{
						alert(alert_arr.INVALID_LIST_PRICE);
						return false;
					}
				}
			}
		}
		else
		{
			alert(alert_arr.SELECT);
			return false;
		}
	}
	else
	{
		xx = 0;
		for(i = 0; i < x ; i++)
		{
			if(document.addToPB.selected_id[i].checked)
			{
				idstring = document.addToPB.selected_id[i].value +";"+idstring;
				prod_array[xx] = document.addToPB.selected_id[i].value;

				xx++;
			}
		}
		if (xx != 0)
		{
			document.addToPB.idlist.value=idstring;
			var elem = document.addToPB.elements;
			var ele_len =elem.length;
			var i=0,j=0;
			for(i=0; i<ele_len; i++)
			{
				for(j=0; j < xx; j++)
				{
					var xy= prod_array[j]+"_listprice";
					if(elem[i].name == xy)
					{
						if (elem[i].value.replace(/^\s+/g, '').replace(/\s+$/g, '').length==0)
						{

							alert(alert_arr.LISTPRICE_CANNOT_BE_EMPTY);
							return false;
						}
						else if(isNaN(parseUserNumber(elem[i].value)) || parseUserNumber(elem[i].value) < 0) // crmv@173281
						{
							alert(alert_arr.INVALID_LIST_PRICE);
							return false;

						}
					}
				}

			}
		}
		else
		{
			alert(alert_arr.SELECT);
			return false;
		}
	}
	document.addToPB.action="index.php?module=Products&action=addPbProductRelToDB&return_module=Products&return_action=AddProductsToPriceBook&parenttab="+parenttab;
}

// crmv@111998
function updateAllListPrice() {
	let fieldNameArray = prepareFieldNameArray();
	let unitPriceArray = prepareUnitPriceArray();

	let unitPrice, fieldName;
	let id;
	let fieldInfo;
	let checkId;

	for (let j = 0; j < unitPriceArray.length; j++) {
		fieldInfo = fieldNameArray[j].split("_");
		id = fieldInfo[0];
		checkId = "check_" + id;

		unitPrice = unitPriceArray[j];
		fieldName = fieldNameArray[j];
		updateListPrice(unitPrice, fieldName, jQuery('#' + checkId).get(0));
	}
}

function updateListPrice(unitPrice,fieldName,oSelect)
{
	let element = jQuery('#'+fieldName).get(0);

	if(oSelect.checked === true)
	{
		if (element.type !== 'hidden') {
			element.style.visibility = 'visible';
			element.value = unitPrice;
		}
	}else
	{
		element.style.visibility = 'hidden';
	}
}

function prepareUnitPriceArray() {
	let unitPriceArray = [];
	jQuery('input[name="selected_id"]').each(function () {
		unitPriceArray.push(jQuery('#'+this.value+'_unit_price').text());
	});
	return unitPriceArray;
}

function prepareFieldNameArray() {
	let fieldNameArray = [];
	jQuery('input[name="selected_id"]').each(function () {
		fieldNameArray.push(this.value + "_listprice");
	});
	return fieldNameArray;
}
// crmv@111998e

function check4null(form)
{
	var isError = false;
	var errorMessage = "";
	if (trim(form.productname.value) =='') 
	{
		isError = true;
		errorMessage += "\n Product Name";
		form.productname.focus();
	}
	if (isError == true) 
	{
		alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
		return false;
	}
	return true;
}

function set_return_specific(vendor_id, vendor_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.vendor_name.value = vendor_name;
	form.vendor_id.value = vendor_id;
	disableReferenceField(form.vendor_name,form.vendor_id,form.vendor_id_mass_edit_check);	//crmv@29190
}

function set_return_inventory_pb(listprice, fldname) 
{
	//crmv@21048m
    parent.document.EditView.elements[fldname].value = listprice;
	parent.document.EditView.elements[fldname].focus();
	//crmv@21048m e
	disableReferenceField(parent.document.EditView.elements[fldname]);	//crmv@29190
	
	//crmv@69922
	var field = document.getElementById(fldname) || parent.document.getElementById(fldname);
	var onChangeFunction = field.onchange;
	if (typeof onChangeFunction === "function") {
		onChangeFunction();
	}
	//crmv@69922e
}

//crmv@128983 crmv@192033
function deletePriceBookProductRel(id,pbid)
{
	vteconfirm(alert_arr.ARE_YOU_SURE, function(yes) {
		if (yes) {
			jQuery("#status").show();
			jQuery.ajax({
				url : 'index.php',
				method: 'POST',
				data: 'module=Products&action=ProductsAjax&file=DeletePriceBookProductRel&ajax=true&return_action=CallRelatedList&return_module=PriceBooks&record='+id+'&pricebook_id='+pbid+'&return_id='+pbid,
				success: function(result) {
					jQuery("#status").hide();
					VteJS_DialogBox.block();
					document.location.reload();
				}
			});
		}
	});
}
//crmv@128983e

function verify_data(fieldLabel)
{
	if (typeof(fieldLabel) == 'undefined') fieldLabel = 'List Price';
	var returnValue = true;
	var list_price_val = jQuery('#list_price').val();
	if(list_price_val != '' && list_price_val != 0)
	{
		intval= intValidate('list_price',fieldLabel);
		if(!intval)
		{
			returnValue =  false;
		}
	       
	}
	else
	{
		if(list_price_val == '')
		{
			alert(alert_arr.LISTPRICE_CANNOT_BE_EMPTY);
			returnValue = false;
		}
	}
	return returnValue;
}
// crmv@192033e

// crmv@111998
function getPBListViewEntries_js(module, url, pricebookId) {
	updateListPricesForPagination();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Products&action=ProductsAjax&file=AddProductsToPriceBook&ajax=true&'
			+ url + '&pricebook_id=' + pricebookId,
		success: function (result) {
			jQuery('#ProductListContent').html(result);
			setRowValues();
			console.log(selectedCheckboxes);
		}
	});
}

function togglePBSelectAll(relCheckName,selectAllName) {
	let relObj = jQuery('input[name="'+relCheckName+'"]');
	let selectAllObj = jQuery('input[name="'+selectAllName+'"]');
	if (relObj.length === undefined) {
		selectAllObj.checked = relObj.checked;
	} else {
		let atleastOneFalse = false;
		for (let i = 0; i < relObj.length; i++) {
			if (relObj[i].checked === false) {
				atleastOneFalse = true;
				break;
			}
		}
		selectAllObj.checked = !atleastOneFalse;
	}

}

function updateSelectedCheckboxes() {
	jQuery('input[name="selected_id"]').each(function (i) {
		let id = this.value;
		if (this.checked) {
			if (selectedCheckboxes.hasOwnProperty(id) === false) {
				selectedCheckboxes[id] = jQuery('#' + id + '_listprice').val();
			}
		} else {
			if (selectedCheckboxes.hasOwnProperty(id) === true) {
				delete selectedCheckboxes[id];
			}
		}
	});
}

function updateListPricesForPagination() {
	jQuery('input[name="selected_id"]').each(function (i) {
		let id = this.value;
		if (this.checked) {
			selectedCheckboxes[id] = jQuery('#' + id + '_listprice').val();
		}
	});
}

function setRowValues() {
	const keys = Object.keys(selectedCheckboxes);
	const parent = jQuery('#ProductListContent');

	for (let i = 0; i < keys.length; i++) {
		let selector = jQuery('input[value="'+keys[i]+'"]');
		selector.prop("checked", true);
		let listpriceInput = jQuery('#' + keys[i] + '_listprice');
		listpriceInput.val(selectedCheckboxes[keys[i]]);

		if (jQuery('#check_'+keys[i]).length === 0) {

			parent.append('<input id="check_'+keys[i]+'" ' +
				'type="hidden" ' +
				'name="selected_id" ' +
				'value="'+keys[i]+'" ' +
				'checked="checked">'
			);
		}

		if (listpriceInput.length === 0) {

			parent.append('<input id="'+keys[i]+'_listprice" ' +
				'type="hidden" name="'+keys[i]+'_listprice" ' +
				'value="'+selectedCheckboxes[keys[i]]+'">'
			);
		}

		if (selector.get(0) !== undefined) {
			updateListPrice(selectedCheckboxes[keys[i]], keys[i]+'_listprice', selector.get(0));
		}
	}
}
// crmv@111998e