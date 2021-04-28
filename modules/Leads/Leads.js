/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@19438 crmv@29463 crmv@41880 */

loadFileJs('include/js/Mail.js');
loadFileJs('include/js/Fax.js');
loadFileJs('include/js/Sms.js');
loadFileJs('include/js/Merge.js');

function verifyConvertLead(form) {
	//crmv@sdk-18501	//crmv@sdk-26260
	sdkValidate = SDKValidate(form);
	if (sdkValidate) {
		sdkValidateResponse = eval('('+sdkValidate.responseText+')');
		if (!sdkValidateResponse['status']) {
			return false;
		}
	}
	//crmv@sdk-18501e	//crmv@sdk-26260e
	if (!AjaxDuplicateValidate('Accounts',form)) return false;

	if(! form.createpotential.checked == true){
        if (trim(form.potential_name.value) == ""){
            alert(alert_arr.OPPORTUNITYNAME_CANNOT_BE_EMPTY);
			return false;	
		}
		if(form.closingdate_mandatory != null && form.closingdate_mandatory.value == '*'){
			if (form.closedate.value == ""){
	        	alert(alert_arr.CLOSEDATE_CANNOT_BE_EMPTY);
				return false;	
			}
		}
		if (form.closedate.value != "" ){
			var x = dateValidate('closedate','Potential Close Date','DATE');
			if(!x){
				return false;
			}
		}
		if(form.amount_mandatory.value == '*'){
			if (form.potential_amount.value == ""){
	            alert(alert_arr.AMOUNT_CANNOT_BE_EMPTY);
				return false;					
			}
		}	
		intval= intValidate('potential_amount','Potential Amount');
		if(!intval){
			return false;
		}
		return true;
	}
	else{	
		return true;
	}
}

function togglePotFields(form)
{
	if (form.createpotential.checked == true)
	{
		form.potential_name.disabled = true;
		form.closedate.disabled = true;
		form.potential_amount.disabled = true;
		form.potential_sales_stage.disabled = true;
		
	}
	else
	{
		form.potential_name.disabled = false;
		form.closedate.disabled = false;
		form.potential_amount.disabled = false;
		form.potential_sales_stage.disabled = false;
		form.potential_sales_stage.value="";
	}	
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
	form.lead_name.value = product_name;
	form.lead_id.value = product_id;
	disableReferenceField(form.lead_name,form.lead_id,form.lead_id_mass_edit_check);	//crmv@29190
}

function add_data_to_relatedlist(entity_id,recordid) {
	opener.document.location.href="index.php?module=Emails&action=updateRelations&destination_module=leads&entityid="+entity_id+"&parentid="+recordid;
}
//added by rdhital/Raju for emails
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

function selectTransferTo(module){
	if(module=='Accounts'){
		if(document.getElementById('transfertoacc').checked){
			jQuery('#account_block').show(); // crmv@192033
			document.getElementById('select_account').checked="checked";
		}
	}
	if(module=='Contacts'){
		if(document.getElementById('transfertocon').checked){
			jQuery('#contact_block').show(); // crmv@192033
			document.getElementById('select_contact').checked="checked";
		}
	}
}

function verifyConvertLeadData(form) {
	var convertForm=document.ConvertLead;
	var no_ele=convertForm.length;
	
	//crmv@sdk-18501	//crmv@sdk-26260
	sdkValidate = SDKValidate(form);
	if (sdkValidate) {
		sdkValidateResponse = eval('('+sdkValidate.responseText+')');
		if (!sdkValidateResponse['status']) {
			return false;
		}
	}
	//crmv@sdk-18501e	//crmv@sdk-26260e
	
	// crmv@148875
	if(form.select_account.checked) {
		if (!AjaxDuplicateValidate('Accounts',form)) return false;
	}
	// crmv@148875e
	
	if((form.select_account!=null)&&(form.select_contact!=null)){
		if(!(form.select_account.checked || form.select_contact.checked)){
			alert(alert_arr["ERR_SELECT_EITHER"]);
			return false;
		}
	}
	else if(form.select_account!=null){
		if(!form.select_account.checked){
			alert(alert_arr["ERR_SELECT_ACCOUNT"]);
			return false;
		}
	}
	else if(form.select_contact!=null){
		if(!form.select_contact.checked){
			alert(alert_arr["ERR_SELECT_CONTACT"]);
			return false;
		}
	}

	if(form.select_account!=null && form.select_account.checked){
		for(i=0;i<no_ele;i++){
			if((convertForm[i].getAttribute('module')=='Accounts') && (convertForm[i].getAttribute('record')=='true')){
				if(convertForm[i].value==''){
					alert(alert_arr["ERR_MANDATORY_FIELD_VALUE"])
					return false;
				}
			}
		}
	}
	if(form.select_potential!=null && form.select_potential.checked){
		for(i=0;i<no_ele;i++){
			if((convertForm[i].getAttribute('module')=='Potentials') && (convertForm[i].getAttribute('record')=='true')){
				if(convertForm[i].value==''){
					alert(alert_arr["ERR_MANDATORY_FIELD_VALUE"])
					return false;
				}
			}
		}
		if(form.jscal_field_closedate!=null && form.jscal_field_closedate.value!=''){
			if(!dateValidate('closingdate',alert_arr['LBL_CLOSE_DATE'],'date')){
				return false;
			}
		}
		if(form.amount.value!=null && isNaN(form.amount.value)){
			alert(alert_arr["ERR_POTENTIAL_AMOUNT"]);
			return false;
		}
	}
	if(form.select_contact!=null && form.select_contact.checked){
		for(i=0;i<no_ele;i++){
			if((convertForm[i].getAttribute('module')=='Contacts') && (convertForm[i].getAttribute('record')=='true')){
				if(convertForm[i].value==''){
					alert(alert_arr["ERR_MANDATORY_FIELD_VALUE"])
					return false;
				}
			}
		}
		var emailpattern=/^[a-zA-Z0-9]+([!"#$%&'()*+,./:;<=>?@\^_`{|}~-]?[a-zA-Z0-9])*@[a-zA-Z0-9]+([\_\-\.]?[a-zA-Z0-9]+)*\.([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)?$/;
		if(form.email.value!=''){
			if(!patternValidate('email',alert_arr['LBL_EMAIL'],'email')){
				return false;
			}
		}
	}

	if(document.getElementById('transfertoacc').checked && !form.select_account.checked){
		alert(alert_arr["ERR_TRANSFER_TO_ACC"]);
		return false;
	}
	if(document.getElementById('transfertocon').checked && !form.select_contact.checked){
		alert(alert_arr["ERR_TRANSFER_TO_CON"]);
		return false;
	}
	return true;
}

function callConvertLeadDiv(id) {
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php?module=Leads&action=LeadsAjax&file=ConvertLead&record='+id,
		method: 'POST',
		success: function(res) {
			jQuery("#convertleaddiv").css('zIndex', findZMax()+1);
			jQuery("#convertleaddiv").html(res);
			jQuery('#status').hide();
		}
	});
}