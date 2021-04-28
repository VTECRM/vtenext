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

function set_return_specific(product_id, product_name, mode) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.elements["salesorder_id_display"].value = product_name;
	form.salesorder_id.value = product_id;
	disableReferenceField(form.elements["salesorder_id_display"],form.salesorder_id,form.salesorder_id_mass_edit_check);	//crmv@29190
	if(enableAdvancedFunction(form) && mode != 'DetailView')	//crmv@29190
	{
		parent.VteJS_DialogBox.block();	//crmv@29190
		//crmv@21048m
		form.action.value = 'EditView';
    	form.convertmode.value = 'update_so_val';
    	form.submit();
    	//crmv@21048me
	}
}