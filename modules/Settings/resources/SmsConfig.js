/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.SmsConfig = VTE.Settings.SmsConfig || {

	validate_sms_server: function(form) {
		if (form.server.value == '') {
			alert(alert_arr.SERVERNAME_CANNOT_BE_EMPTY);
			return false;
		}

		return true;
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.SmsConfig class.
 */

function validate_sms_server(form) {
	return VTE.callDeprecated('validate_sms_server', VTE.Settings.SmsConfig.validate_sms_server, arguments);
}