/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.LogConfig = VTE.Settings.LogConfig || {

	saveGlobalConfig: function(prop, value) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=LogConfigAjax&ajax=true&ajxaction=SAVEGLOBALCONFIG&prop='+prop+'&value='+value,
			type: 'POST',
			success: function(data) {
				if (data != 'SUCCESS') {
					alert(alert_arr.ERROR);
				}
				jQuery("#status").hide();
			}
		});
	},

	toggleLogProp: function(logid) {
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=LogConfigAjax&ajax=true&ajxaction=TOGGLELOGPROP&log='+logid,
			type: 'POST',
			success: function(data) {
				if (data == 'SUCCESS') {
					document.location.reload();
				} else {
					alert(alert_arr.ERROR);
				}
			}
		});
	}

};

/**
 * @deprecated
 * This object has been moved to VTE.Settings.LogConfig class.
 */

window.LogConfig = window.LogConfig || {

	saveGlobalConfig: function(prop, value) {
		return VTE.callDeprecated('LogConfig.saveGlobalConfig', VTE.Settings.LogConfig.saveGlobalConfig, arguments);
	},

	toggleLogProp: function(logid) {
		return VTE.callDeprecated('LogConfig.toggleLogProp', VTE.Settings.LogConfig.toggleLogProp, arguments);
	},

}