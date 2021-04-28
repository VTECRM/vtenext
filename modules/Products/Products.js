/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('modules/Products/multifile.js');
loadFileJs('include/js/Merge.js');

function updateListPrice(unitprice,fieldname, oSelect)
{
	if(oSelect.checked == true)
	{
		document.getElementById(fieldname).style.visibility = 'visible';
		document.getElementById(fieldname).value = unitprice;
	}else
	{
		document.getElementById(fieldname).style.visibility = 'hidden';
	}
}

function check4null(form)
{
	var isError = false;
	var errorMessage = "";
	if (trim(form.productname.value) =='') {
		isError = true;
		errorMessage += "\n Product Name";
		form.productname.focus();
	}
	if (isError == true) {
		alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
		return false;
	}
	return true;
}

function set_return(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
}

function set_return_specific(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.product_name.value = product_name;
	form.product_id.value = product_id;
	disableReferenceField(form.product_name,form.product_id,form.product_id_mass_edit_check);	//crmv@29190
}

function add_data_to_relatedlist(entity_id,recordid) {
	opener.document.location.href="index.php?module={RETURN_MODULE}&action=updateRelations&smodule={SMODULE}&destination_module=Products&entityid="+entity_id+"&parentid="+recordid;
}

//crmv@16267 crmv@48407 crmv@51631 crmv@55228
function set_return_inventory(product_id,product_name,unitprice,qtyinstock,taxstr,curr_row,desc,subprod_id,product_code,unit_cost) {
	var subprod = subprod_id.split("::");
	var jQuery_obj = jQuery;
	var popup = false;
	if (jQuery("form[name=EditView] #subproduct_ids"+curr_row).length <= 0){
		jQuery_obj = parent.jQuery;
		popup = true;
	}
	jQuery_obj("form[name=EditView] #subproduct_ids"+curr_row).val(subprod[0]);
	jQuery_obj("form[name=EditView] #subprod_names"+curr_row).val(subprod[1]);
	jQuery_obj("form[name=EditView] #productName"+curr_row).val(product_name);
	jQuery_obj("form[name=EditView] #hdnProductId"+curr_row).val(product_id);
	disableReferenceField(jQuery_obj("form[name=EditView] #hdnProductId"+curr_row));
	jQuery_obj("form[name=EditView] #listPrice"+curr_row).val(unitprice);
	jQuery_obj("form[name=EditView] #productDescription"+curr_row).val(desc);
	jQuery_obj("form[name=EditView] #hdnProductcode"+curr_row).val(product_code);
	jQuery_obj("form[name=EditView] #qtyInStock"+curr_row).html(qtyinstock);
	jQuery_obj("form[name=EditView] #qty"+curr_row).focus();
	if (unit_cost != undefined) jQuery_obj("form[name=EditView] #unit_cost"+curr_row).val(unit_cost);
	if (popup) parent.loadTaxes_Ajax(curr_row); else loadTaxes_Ajax(curr_row);
}

function set_return_inventory_po(product_id,product_name,unitprice,taxstr,curr_row,desc,subprod_id,product_code,unit_cost) {
	var subprod = subprod_id.split("::");
	var jQuery_obj = jQuery;
	var popup = false;
	if (jQuery("form[name=EditView] #subproduct_ids"+curr_row).length <= 0){
		jQuery_obj = parent.jQuery;
		popup = true;
	}
	jQuery_obj("form[name=EditView] #subproduct_ids"+curr_row).val(subprod[0]);
	jQuery_obj("form[name=EditView] #subprod_names"+curr_row).val(subprod[1]);
	jQuery_obj("form[name=EditView] #productName"+curr_row).val(product_name);
	jQuery_obj("form[name=EditView] #hdnProductId"+curr_row).val(product_id);
	disableReferenceField(jQuery_obj("form[name=EditView] #productName"+curr_row));
	jQuery_obj("form[name=EditView] #listPrice"+curr_row).val(unitprice);
	jQuery_obj("form[name=EditView] #productDescription"+curr_row).val(desc);
	jQuery_obj("form[name=EditView] #hdnProductcode"+curr_row).val(product_code);
	jQuery_obj("form[name=EditView] #qty"+curr_row).focus();
	if (unit_cost != undefined) jQuery_obj("form[name=EditView] #unit_cost"+curr_row).val(unit_cost);
	if (popup) parent.loadTaxes_Ajax(curr_row); else loadTaxes_Ajax(curr_row);
}
//crmv@16267e crmv@48407e crmv@51631e crmv@55228e

function getImageListBody() {
	if (browser_ie) {
		var ImageListBody=getObj("ImageList")
	} else if (browser_nn4 || browser_nn6) {
		if (getObj("ImageList").childNodes.item(0).tagName=="TABLE") {
			var ImageListBody=getObj("ImageList")
		} else {
			var ImageListBody=getObj("ImageList")
		}
	}
	return ImageListBody;
}

// crmv@198024 - if you change this function, remember to change it also in ConfProducts.js
function reload_variant_block(recordid, formodule, forfield) {
	if (window.parent !== window) {
		var context = window.parent;
	} else {
		var context = window;
	}
	var block = context.jQuery('#confprodinfo').closest('.editBlock'),
		blockid = block.attr('id').replace('block_', '');
	
	if (!blockid) return;
	
	if (recordid > 0) {
		// ok, show the block
		context.jQuery.ajax({
			url: 'index.php?module=ConfProducts&action=ConfProductsAjax&ajax=true&file=LoadBlock',
			method: 'POST',
			data: 'formodule='+formodule+'&forfield='+forfield+'&confproductid='+recordid,
			dataType: 'json',
			success: function(result) {
				if (result && result.success) {
					// remove old fields
					var hiddenField = context.jQuery('#confprodinfo').closest('tr');
					context.jQuery('#displayfields_'+blockid).get(0).innerHTML = hiddenField.html() + result.html; // don't execute scripts'
					context.jQuery('#displayfields_'+blockid).show();
					context.jQuery('.blockrow_'+blockid).show();
				}
			}
		});
	} else {
		// hide the block
		context.jQuery('#displayfields_'+blockid).hide();
		context.jQuery('.blockrow_'+blockid).hide();
	}
}
// crmv@198024e