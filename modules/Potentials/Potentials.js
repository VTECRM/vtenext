/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Merge.js');

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
	form.potential_name.value = product_name;
	form.potential_id.value = product_id;
	disableReferenceField(form.potential_name,form.potential_id,form.potential_id_mass_edit_check);	//crmv@29190
}

function add_data_to_relatedlist(entity_id,recordid) 
{
	opener.document.location.href="index.php?module=Emails&action=updateRelations&destination_module=Contacts&entityid="+entity_id+"&parentid="+recordid;
}

function set_return_address(potential_id, potential_name, account_id, account_name, bill_street, ship_street, bill_city, ship_city, bill_state, ship_state, bill_code, ship_code, bill_country, ship_country,bill_pobox,ship_pobox) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	if(typeof(form.elements["potential_id_display"]) != 'undefined')
		form.elements["potential_id_display"].value = potential_name;
   if(typeof(form.elements["potential_id"]) != 'undefined')
		form.elements["potential_id"].value = potential_id;
	disableReferenceField(form.elements["potential_id_display"],form.elements["potential_id"],form.elements["potential_id_mass_edit_check"]);
	if (enableAdvancedFunction(form)) { //crmv@29190
		if(typeof(form.elements["account_id_display"]) != 'undefined') {
			form.elements["account_id_display"].value = account_name; 
			disableReferenceField(form.elements["account_id_display"],form.account_id,form.account_id_mass_edit_check);	//crmv@29190
		}
	    if(typeof(form.account_id) != 'undefined')
			form.account_id.value = account_id;
	    if(typeof(form.bill_street) != 'undefined')
			form.bill_street.value = bill_street;
	    if(typeof(form.ship_street) != 'undefined')
			form.ship_street.value = ship_street;
	    if(typeof(form.bill_city) != 'undefined')
			form.bill_city.value = bill_city;
	    if(typeof(form.ship_city) != 'undefined')
			form.ship_city.value = ship_city;
	    if(typeof(form.bill_state) != 'undefined')
			form.bill_state.value = bill_state;
	    if(typeof(form.ship_state) != 'undefined')
			form.ship_state.value = ship_state;
	    if(typeof(form.bill_code) != 'undefined')
			form.bill_code.value = bill_code;
	    if(typeof(form.ship_code) != 'undefined')
			form.ship_code.value = ship_code;
	    if(typeof(form.bill_country) != 'undefined')
			form.bill_country.value = bill_country;
	    if(typeof(form.ship_country) != 'undefined')
			form.ship_country.value = ship_country;
	    if(typeof(form.bill_pobox) != 'undefined')
			form.bill_pobox.value = bill_pobox;
	    if(typeof(form.ship_pobox) != 'undefined')
			form.ship_pobox.value = ship_pobox;
	}
}

function set_return_contact(potential_id, potential_name, contact_id, contact_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	if(typeof(form.elements["potential_id_display"]) != 'undefined')
		form.elements["potential_id_display"].value = potential_name;
	if(typeof(form.elements["potential_id"]) != 'undefined')
		form.elements["potential_id"].value = potential_id;
	disableReferenceField(form.elements["potential_id_display"],form.elements["potential_id"],form.elements["potential_id_mass_edit_check"]);
	if (enableAdvancedFunction(form)) { //crmv@29190
		if(typeof(form.elements["contact_id_display"]) != 'undefined')
			form.elements["contact_id_display"].value = contact_name;
		if(typeof(form.elements["contact_id"]) != 'undefined')
			form.elements["contact_id"].value = contact_id;
		disableReferenceField(form.elements["contact_id_display"],form.elements["contact_id"],form.elements["contact_id_mass_edit_check"]);
	}
}