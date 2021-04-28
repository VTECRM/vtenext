/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Mail.js');
loadFileJs('include/js/Fax.js');
loadFileJs('include/js/Sms.js');
loadFileJs('include/js/Merge.js');

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

function set_return_specific(vendor_id, vendor_name) 
{
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.vendor_name.value = vendor_name;
	form.vendor_id.value = vendor_id;
	disableReferenceField(form.vendor_name,form.vendor_id,form.vendor_id_mass_edit_check);	//crmv@29190
}

function set_return_address(vendor_id, vendor_name, street, city, state, code, country,pobox ) 
{
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	//crmv@21048m
	if(typeof(form.elements["vendor_id_display"]) != 'undefined')
		form.elements["vendor_id_display"].value = vendor_name; 
	if(typeof(form.vendor_id) != 'undefined')
		form.vendor_id.value = vendor_id;	
     if(typeof(form.bill_street) != 'undefined')
		form.bill_street.value = street;
     if(typeof(form.ship_street) != 'undefined')
		form.ship_street.value = street;
     if(typeof(form.bill_city) != 'undefined')
		form.bill_city.value = city;
     if(typeof(form.ship_city) != 'undefined')
		form.ship_city.value = city;
     if(typeof(form.bill_state) != 'undefined')
		form.bill_state.value = state;
     if(typeof(form.ship_state) != 'undefined')
		form.ship_state.value = state;
     if(typeof(form.bill_code) != 'undefined')
		form.bill_code.value = code;
     if(typeof(form.ship_code) != 'undefined')
		form.ship_code.value = code;
     if(typeof(form.bill_country) != 'undefined')
		form.bill_country.value = country;
     if(typeof(form.ship_country) != 'undefined')
		form.ship_country.value = country;
     if(typeof(form.bill_pobox) != 'undefined')
		form.bill_pobox.value = pobox;
     if(typeof(form.ship_pobox) != 'undefined')
		form.ship_pobox.value = pobox;
    //crmv@21048me
    disableReferenceField(form.elements["vendor_id_display"],form.vendor_id,form.vendor_id_mass_edit_check);	//crmv@29190
}