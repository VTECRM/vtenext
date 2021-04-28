/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

loadFileJs('include/js/Mail.js');
loadFileJs('include/js/Fax.js');
loadFileJs('include/js/Sms.js');
loadFileJs('include/js/Merge.js');

function copyAddressRight(form) {

	if(typeof(form.otherstreet) != 'undefined' && typeof(form.mailingstreet) != 'undefined')
		form.otherstreet.value = form.mailingstreet.value;

	if(typeof(form.othercity) != 'undefined' && typeof(form.mailingcity) != 'undefined')
		form.othercity.value = form.mailingcity.value;

	if(typeof(form.otherstate) != 'undefined' && typeof(form.mailingstate) != 'undefined')
		form.otherstate.value = form.mailingstate.value;

	if(typeof(form.otherzip) != 'undefined' && typeof(form.mailingzip) != 'undefined')
		form.otherzip.value = form.mailingzip.value;

	if(typeof(form.othercountry) != 'undefined' && typeof(form.mailingcountry) != 'undefined')
		form.othercountry.value = form.mailingcountry.value;

	if(typeof(form.otherpobox) != 'undefined' && typeof(form.mailingpobox) != 'undefined')
		form.otherpobox.value = form.mailingpobox.value;
	
	// crmv@124434
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
	// crmv@124434e

	return true;

}

function copyAddressLeft(form) {

	if(typeof(form.otherstreet) != 'undefined' && typeof(form.mailingstreet) != 'undefined')
		form.mailingstreet.value = form.otherstreet.value;

	if(typeof(form.othercity) != 'undefined' && typeof(form.mailingcity) != 'undefined')
		form.mailingcity.value = form.othercity.value;

	if(typeof(form.otherstate) != 'undefined' && typeof(form.mailingstate) != 'undefined')
		form.mailingstate.value = form.otherstate.value;

	if(typeof(form.otherzip) != 'undefined' && typeof(form.mailingzip) != 'undefined')
		form.mailingzip.value =	form.otherzip.value;

	if(typeof(form.othercountry) != 'undefined' && typeof(form.mailingcountry) != 'undefined')
		form.mailingcountry.value = form.othercountry.value;

	if(typeof(form.otherpobox) != 'undefined' && typeof(form.mailingpobox) != 'undefined')
		form.mailingpobox.value = form.otherpobox.value;
		
	// crmv@124434
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
	// crmv@124434e

	return true;

}


function toggleDisplay(id){

	if(this.document.getElementById( id).style.display=='none'){
		this.document.getElementById( id).style.display='inline'
		this.document.getElementById(id+"link").style.display='none';

	}else{
		this.document.getElementById(  id).style.display='none'
		this.document.getElementById(id+"link").style.display='none';
	}
}

function set_return(product_id, product_name) {
	//crmv@30408
	if(top.jQuery('div#addEventInviteUI').css('display') == 'block'){
		var linkedMod = 'Contacts';
		var entity_id = product_id;
		var strVal = product_name;
		if (top.jQuery('#addEventInviteUI').contents().find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
			strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
			'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
			'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
			'</tr>';
			top.jQuery('#selectedTable').append(strHtlm);
			jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id);
		}
	//crmv@30408e
	// crmv@104061
	} else if (parent.jQuery('#quick_parent_type').val() === 'Contacts') {
		var container = parent.jQuery('#selectedTable');
		var linkedMod = 'Contacts';
		var entity_id = product_id;
		var strVal = product_name;
		if (container.find('#' + entity_id + '_' + linkedMod + '_dest').length < 1) {
			strHtlm = '<tr id="' + entity_id + '_' + linkedMod + '_dest' + '" onclick="checkTr(this.id)">' +
			'<td align="center" style="display:none;"><input type="checkbox" value="' + entity_id + '_' + linkedMod + '"></td>' +
			'<td nowrap align="left" class="parent_name" style="width:100%">' + strVal + '</td>' +
			'</tr>';
			parent.jQuery('#selectedTable').append(strHtlm);
			jQuery('#parent_id_link_contacts').val(jQuery('#parent_id_link_contacts').val() + ';' + entity_id);
		}
	// crmv@104061e
	} else {
		//crmv@29190
		var formName = getReturnFormName();
		var form = getReturnForm(formName);
		//crmv@29190e
		form.parent_name.value = product_name;
		form.parent_id.value = product_id;
		disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);	//crmv@29190
	}
}

//crmv@42752
function add_data_to_relatedlist_incal(id,name) {
	var shouldClosePopup = (parent.document.EditView ? true : false),
		parentDoc = (parent.document.EditView ? parent : window),
		editView = parentDoc.document.EditView;

	//crmv@21048m
	var idval = editView.contactidlist.value;
	var nameval = editView.contactlist.value;
	if (idval != '')	{
		if(idval.indexOf(id) != -1)	{
			editView.contactidlist.value = idval;
			editView.contactlist.value = nameval;

		} else {
			editView.contactidlist.value = idval+';'+id;
			if (name != '') {
				// this has been modified to provide delete option for Contacts in Calendar
				//this function is defined in script.js ------- Jeri
				parentDoc.addOption(id,name);
			}
		}
	} else {
		editView.contactidlist.value = id;
		if(name != '') {
			parentDoc.addOption(id,name);
		}
		//end
	}
	if (shouldClosePopup) closePopup();
	//crmv@21048me
}
//crmv@42752e

function set_return_specific(product_id, product_name) {
	//Used for DetailView, Removed 'EditView' formname hardcoding
	var fldName = getOpenerObj("contact_name");
	var fldId = getOpenerObj("contact_id");
	fldName.value = product_name;
	fldId.value = product_id;
}
//only for Todo
function set_return_toDospecific(product_id, product_name) {
	var fldName = getOpenerObj("task_contact_name");
	var fldId = getOpenerObj("task_contact_id");
	fldName.value = product_name;
	fldId.value = product_id;
}

function submitform(id){
	document.massdelete.entityid.value=id;
	document.massdelete.submit();
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

function set_return_contact_address(contact_id,contact_name, mailingstreet, otherstreet, mailingcity, othercity, mailingstate, otherstate, mailingcode, othercode, mailingcountry, othercountry,mailingpobox,otherpobox,formName) {

	//crmv@42247
	if (formName == undefined || typeof(formName) == 'undefined' || formName == '') {
		var formName = getReturnFormName();
	}
	var form = getReturnForm(formName);

	if(typeof(form.elements["contact_id"]) != 'undefined')
		form.elements["contact_id"].value = contact_id;
	if(typeof(form.elements["contact_id_display"]) != 'undefined'){
		form.elements["contact_id_display"].value = contact_name;
		var disable_name = form.elements["contact_id_display"];
	}
	if(typeof(form.elements["contact_name"]) != 'undefined'){
		form.elements["contact_name"].value = contact_name;
		var disable_name = form.elements["contact_name"];
	}

	disableReferenceField(disable_name,form.elements["contact_id"],form.elements["contact_id_mass_edit_check"]);
	//crmv@42247e

	if(enableAdvancedFunction(form) && typeof(form.bill_street) != 'undefined')	//crmv@29190
	if(confirm(alert_arr.OVERWRITE_EXISTING_CONTACT1+contact_name+alert_arr.OVERWRITE_EXISTING_CONTACT2))
	{
		//made changes to avoid js error -- ref : hidding fields causes js error(ticket#4017)
		if(typeof(form.bill_street) != 'undefined')
			form.bill_street.value = mailingstreet;
		if(typeof(form.ship_street) != 'undefined')
			form.ship_street.value = otherstreet;
		if(typeof(form.bill_city) != 'undefined')
			form.bill_city.value = mailingcity;
		if(typeof(form.ship_city) != 'undefined')
			form.ship_city.value = othercity;
		if(typeof(form.bill_state) != 'undefined')
			form.bill_state.value = mailingstate;
		if(typeof(form.ship_state) != 'undefined')
			form.ship_state.value = otherstate;
		if(typeof(form.bill_code) != 'undefined')
			form.bill_code.value = mailingcode;
		if(typeof(form.ship_code) != 'undefined')
			form.ship_code.value = othercode;
		if(typeof(form.bill_country) != 'undefined')
			form.bill_country.value = mailingcountry;
		if(typeof(form.ship_country) != 'undefined')
			form.ship_country.value = othercountry;
		if(typeof(form.bill_pobox) != 'undefined')
			form.bill_pobox.value = mailingpobox;
		if(typeof(form.ship_pobox) != 'undefined')
			form.ship_pobox.value = otherpobox;
		//end
	}
}