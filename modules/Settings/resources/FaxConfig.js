/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.FaxConfig = VTE.Settings.FaxConfig || {

	validate_fax_server: function(form) {
		if (form.server.value == '') {
			alert(alert_arr.SERVERNAME_CANNOT_BE_EMPTY);
			return false;
		}

		return true;
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.FaxConfig class.
 */

function validate_fax_server(form) {
	return VTE.callDeprecated('validate_fax_server', VTE.Settings.FaxConfig.validate_fax_server, arguments);
}