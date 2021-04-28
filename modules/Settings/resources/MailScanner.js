/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.MailScannerInfo = VTE.Settings.MailScannerInfo || {

	performScanNow: function(app_key, scannername) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=MailScanner&mode=scannow&service=MailScanner&app_key=' + encodeURIComponent(app_key) + '&scannername=' + encodeURIComponent(scannername),
			success: function(result) {
				jQuery("#status").hide();
			}
		});
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.MailScannerInfo class.
 */

function performScanNow(app_key, scannername) {
	return VTE.callDeprecated('performScanNow', VTE.Settings.MailScannerInfo.performScanNow, arguments);
}