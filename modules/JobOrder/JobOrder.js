/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@29190 crmv@69568
function set_return(product_id, product_name) {
	var formName = getReturnFormName();
	var form = (formName ? getReturnForm(formName) : null);
	if (form) {
		form.parent_name.value = product_name;
		form.parent_id.value = product_id;
		disableReferenceField(form.parent_name,form.parent_id,form.parent_id_mass_edit_check);
	}
}
//crmv@29190e crmv@69568e