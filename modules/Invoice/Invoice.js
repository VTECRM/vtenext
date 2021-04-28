/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Inventory.js');

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

function set_return_inventory(product_id,product_name,unitprice,qtyinstock,curr_row) {
	//crmv@21048m
    parent.document.EditView.elements["txtProduct"+curr_row].value = product_name;
    parent.document.EditView.elements["hdnProductId"+curr_row].value = product_id;
	parent.document.EditView.elements["txtListPrice"+curr_row].value = unitprice;
	getOpenerObj("unitPrice"+curr_row).innerHTML = unitprice;
	getOpenerObj("qtyInStock"+curr_row).innerHTML = qtyinstock;
	parent.document.EditView.elements["txtQty"+curr_row].focus();
	//crmv@21048me
}