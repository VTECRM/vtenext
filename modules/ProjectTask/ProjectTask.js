/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function set_return(product_id, product_name) {
	//crmv@29190
	var formName = getReturnFormName();
	var form = getReturnForm(formName);
	//crmv@29190e
	form.parent_name.value = product_name;
	form.parent_id.value = product_id;
	disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check); //crmv@29190
}

//crmv@104562
function toggleWorkingDays(field) {
	if (field.checked) {
		jQuery('#working_days').attr('readonly', true);
		jQuery('#working_days').parent('div').attr('class','dvtCellInfoOff');
	} else {
		jQuery('#working_days').attr('readonly', false);
		jQuery('#working_days').parent('div').attr('class','dvtCellInfo');
		jQuery('#working_days').focus();
	}
}
//crmv@104562e