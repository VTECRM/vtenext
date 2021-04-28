/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Inventory.js');
loadFileJs('include/js/Mail.js');
loadFileJs('include/js/Fax.js');
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
	form.account_name.value = product_name;
	form.account_id.value = product_id;
	disableReferenceField(form.account_name,form.account_id,form.account_id_mass_edit_check);	//crmv@29190
}

function add_data_to_relatedlist(entity_id,recordid) {
	opener.document.location.href="index.php?module=Emails&action=updateRelations&destination_module=Accounts&entityid="+entity_id+"&parentid="+recordid;
}

function set_return_address(account_id, account_name, bill_street, ship_street, bill_city, ship_city, bill_state, ship_state, bill_code, ship_code, bill_country, ship_country,bill_pobox,ship_pobox) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.elements["account_id_display"].value = account_name; 
	form.elements["account_id"].value = account_id;
	disableReferenceField(form.elements["account_id_display"],form.elements["account_id"],form.elements["account_id_mass_edit_check"]);	//crmv@29190
	if (enableAdvancedFunction(form)) {	//crmv@29190
		//Ask the user to overwite the address or not - Modified on 06-01-2007
		if(confirm(alert_arr.OVERWRITE_EXISTING_ACCOUNT1+account_name+alert_arr.OVERWRITE_EXISTING_ACCOUNT2))
		{
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
		//crmv@21048me
	}
}
//crmv@14536
function set_return_account(account_id, account_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.elements["account_name"].value = account_name;
	form.elements["account_id"].value = account_id;
	disableReferenceField(form.elements["account_name"],form.elements["account_id"],form.elements["account_id_mass_edit_check"]);	//crmv@29190
}
//crmv@14536e
//added to populate address
function set_return_contact_address(account_id, account_name, bill_street, ship_street, bill_city, ship_city, bill_state, ship_state, bill_code, ship_code, bill_country, ship_country,bill_pobox,ship_pobox,phone,fax ) { //crmv@65940
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	//crmv@17789
	if(typeof(form.elements["account_id_display"]) != 'undefined')
		form.elements["account_id_display"].value = account_name;
	if(typeof(form.elements["account_id"]) != 'undefined')
		form.elements["account_id"].value = account_id;
	disableReferenceField(form.elements["account_id_display"],form.elements["account_id"],form.elements["account_id_mass_edit_check"]);	//crmv@29190
	if (enableAdvancedFunction(form) && formName == 'EditView') {
		if(confirm(alert_arr.OVERWRITE_EXISTING_ACCOUNT1+account_name+alert_arr.OVERWRITE_EXISTING_ACCOUNT2))
		{
			if(typeof(form.mailingstreet) != 'undefined')
				form.mailingstreet.value = bill_street;
			if(typeof(form.otherstreet) != 'undefined')
				form.otherstreet.value = ship_street;
			if(typeof(form.mailingcity) != 'undefined')
				form.mailingcity.value = bill_city;
			if(typeof(form.othercity) != 'undefined')
				form.othercity.value = ship_city;
			if(typeof(form.mailingstate) != 'undefined')
				form.mailingstate.value = bill_state;
			if(typeof(form.otherstate) != 'undefined')
				form.otherstate.value = ship_state;
			if(typeof(form.mailingzip) != 'undefined')
				form.mailingzip.value = bill_code;
			if(typeof(form.otherzip) != 'undefined')
				form.otherzip.value = ship_code;
			if(typeof(form.mailingcountry) != 'undefined')
				form.mailingcountry.value = bill_country;
			if(typeof(form.othercountry) != 'undefined')
				form.othercountry.value = ship_country;
			if(typeof(form.mailingpobox) != 'undefined')
				form.mailingpobox.value = bill_pobox;
			if(typeof(form.otherpobox) != 'undefined')
				form.otherpobox.value = ship_pobox;
			//crmv@65940
			if(typeof(form.elements["otherphone"]) != 'undefined')
				form.elements["otherphone"].value = phone;
			if(typeof(form.elements["fax"]) != 'undefined')
				form.elements["fax"].value = fax;
			//crmv@65940e
		}
	}
	//crmv@17789e
}

/**
 * @deprecated
 * This function has been moved to VTE.MapLocation class.
 */

// crmv@194390
function searchMapLocation(addressType) {
	return VTE.callDeprecated('searchMapLocation', VTE.MapLocation.searchMapLocation, arguments);
}
// crmv@194390e

// crmv@192033
//When changing the Account Address Information  it should also change the related contact address.
function checkAddress(form,id) {
	var url='';
	if(typeof(form.bill_street) != 'undefined')
			url +="&bill_street="+form.bill_street.value;
	if(typeof(form.ship_street) != 'undefined')
			url +="&ship_street="+form.ship_street.value;
	if(typeof(form.bill_city) != 'undefined')
			url +="&bill_city="+form.bill_city.value;
	if(typeof(form.ship_city) != 'undefined')
			url +="&ship_city="+form.ship_city.value;
	if(typeof(form.bill_state) != 'undefined')
			url +="&bill_state="+form.bill_state.value;
	if(typeof(form.ship_state) != 'undefined')
			url +="&ship_state="+form.ship_state.value;
	if(typeof(form.bill_code) != 'undefined')
			url +="&bill_code="+ form.bill_code.value;
	if(typeof(form.ship_code) != 'undefined')
		url +="&ship_code="+ form.ship_code.value;
	if(typeof(form.bill_country) != 'undefined')
		url +="&bill_country="+form.bill_country.value;
	if(typeof(form.ship_country) != 'undefined')
		url +="&ship_country="+form.ship_country.value;
	if(typeof(form.bill_pobox) != 'undefined')
		url +="&bill_pobox="+ form.bill_pobox.value;
	if(typeof(form.ship_pobox) != 'undefined')
		url +="&ship_pobox="+ form.ship_pobox.value;

	url +="&record="+id;		
	
	jQuery("#status").show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: "module=Accounts&action=AccountsAjax&ajax=true&file=AddressChange"+url,
		success: function(result) {
			if (result == 'address_change') {
				if (confirm(alert_arr.WANT_TO_CHANGE_CONTACT_ADDR) == true) {
					form.address_change.value = 'yes';
					form.submit();	
				} else {	
					form.submit();
				}
			} else {
				form.submit();	
			}
		}
	});
}
// crmv@192033e