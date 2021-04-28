/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.EditCompanyInfo = VTE.Settings.EditCompanyInfo || {

	verify_data: function(form, company_name) {
		if (form.organization_name.value == "") {
			alert(sprintf(alert_arr.CANNOT_BE_NONE, company_name));
			form.organization_name.focus();
			return false;
		} else if (form.organization_name.value.replace(/^\s+/g, '').replace(/\s+$/g, '').length == 0) {
			alert(sprintf(alert_arr.CANNOT_BE_EMPTY, company_name));
			form.organization_name.focus();
			return false;
		} else if (!upload_filter("binFile","jpg|jpeg|JPG|JPEG|png|PNG")) { //crmv@106075
			form.binFile.focus();
			return false;
		} else {
			return true;
		}
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.EditCompanyInfo class.
 */

function verify_data(form, company_name) {
	return VTE.callDeprecated('verify_data', VTE.Settings.EditCompanyInfo.verify_data, arguments);
}