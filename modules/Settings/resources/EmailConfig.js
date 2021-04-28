/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.EmailConfig = VTE.Settings.EmailConfig || {

	validate_mail_server: function(form) {
		if (form.account_smtp.value != '' && form.server.value == '') {
			alert(alert_arr.SERVERNAME_CANNOT_BE_EMPTY);
			return false;
		}
		return true;
	},

	calculateAccount: function(value, account, seq) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=EmailConfig&mode=ajax&calculate_account='+value+'&account_type='+account+'&seq='+seq,
			success: function(result) {
				if (account == 'imap') {
					var container = 'imap_account_div_'+seq;
					jQuery('#' + container).replaceWith(result);
				} else {
					var container = 'account_container_'+account;
					jQuery('#' + container).html(result);
				}
				jQuery("#status").hide();
			}
		});
	},

	addAccount: function(account) {
		var num = jQuery('[id^="imap_account_div_"]').length;
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=EmailConfig&mode=ajax&calculate_account=&account_type='+account+'&seq='+num,
			type: 'post',
			success: function(data) {
				jQuery('#account_container_imap tbody').append(data);
			}
		});
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.EmailConfig class.
 */

function validate_mail_server(form) {
	return VTE.callDeprecated('validate_mail_server', VTE.Settings.EmailConfig.validate_mail_server, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.EmailConfig class.
 */

function calculateAccount(value, account, seq) {
	return VTE.callDeprecated('calculateAccount', VTE.Settings.EmailConfig.calculateAccount, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.EmailConfig class.
 */

function addAccount(account) {
	return VTE.callDeprecated('addAccount', VTE.Settings.EmailConfig.addAccount, arguments);
}