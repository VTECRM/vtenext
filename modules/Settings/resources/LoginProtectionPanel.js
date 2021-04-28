/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.LoginProtectionPanel = VTE.Settings.LoginProtectionPanel || {

	fetchLoginProtection: function(id, url_string) {
		if (id == 'none') {
			jQuery('#login_protection_content').fadeOut(); // crmv@168103
		} else {
			jQuery("#status").show();
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Settings&action=SettingsAjax&file=LoginProtectionPanel&ajax=true&userid='+id+url_string,
				success: function(result) {
					jQuery("#status").hide();
					jQuery('#login_protection_content').html(result);
					jQuery('#login_protection_content').fadeIn(); // crmv@168103
				}
			});
		}
	},

	whiteListRecord: function(recordid) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=LoginProtectionActions&ajax=true&mode=whitelist&id='+recordid,
			success: function(result) {
				var obj = getObj('user_list');
				fetchloginprotection_js(obj,'');
			}
		});
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.LoginProtectionPanel class.
 */

function fetchLoginProtection(id, url_string) {
	return VTE.callDeprecated('fetchLoginProtection', VTE.Settings.LoginProtectionPanel.fetchLoginProtection, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.LoginProtectionPanel class.
 */

function whiteListRecord(recordid) {
	return VTE.callDeprecated('whiteListRecord', VTE.Settings.LoginProtectionPanel.whiteListRecord, arguments);
}