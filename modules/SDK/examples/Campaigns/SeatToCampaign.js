/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function return_seat_to_campaign(recordid,value,target_fieldname,course_address) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	if (form) {
	//crmv@29190e
		var domnode_id = form.elements[target_fieldname];
		var domnode_display = form.elements[target_fieldname+'_display'];
		if(domnode_id) domnode_id.value = recordid;
		if(domnode_display) domnode_display.value = value.replace(/&amp;/g, '&');
		if (enableAdvancedFunction(form)) {	//crmv@29190
			if (form.elements['course_address']) {
				form.elements['course_address'].value = course_address;
			}
		}
		// moved disableReferenceField after for keep the new course_address
		parent.disableReferenceField(domnode_display,domnode_id,form.elements[target_fieldname+'_mass_edit_check']);	//crmv@29190
		return true;
	} else {
		return false;
	}
}