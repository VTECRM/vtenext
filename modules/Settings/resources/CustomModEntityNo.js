/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.CustomModEntityNo = VTE.Settings.CustomModEntityNo || {

	getModuleEntityNoInfo: function(form) {
		var module = form.selmodule.value;
	
		jQuery("#status").show();
	
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=CustomModEntityNo&ajax=true&selmodule=' + encodeURIComponent(module),
			success: function(result) {
				jQuery("#status").hide();
				jQuery('#customentity_infodiv').html(result);
			}
		});
	},

	updateModEntityNoSetting: function(button, form) {
		var module = form.selmodule.value;
		var recprefix = form.recprefix.value;
		var recnumber = form.recnumber.value;
		var mode = 'UPDATESETTINGS';
	
		if (recnumber == '') {
			alert("Start sequence cannot be empty!");
			return;
		}
	
		if (recnumber.match(/[^0-9]+/) != null) {
			alert("Start sequence should be numeric.");
			return;
		}
	
		jQuery("#status").show();
		button.disabled = true;
	
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=CustomModEntityNo&ajax=true' + 
						'&selmodule=' + encodeURIComponent(module) +
						'&recprefix=' + encodeURIComponent(recprefix) +
						'&recnumber=' + encodeURIComponent(recnumber) +
						'&mode=' + encodeURIComponent(mode),
			success: function(result) {
				jQuery("#status").hide();
				jQuery('#customentity_infodiv').html(result);
			}
		});
	},

	updateModEntityExisting: function(button, form) {
		var module = form.selmodule.value;
		var recprefix = form.recprefix.value;
		var recnumber = form.recnumber.value;
		var mode = 'UPDATEBULKEXISTING';
	
		if (recnumber == '') {
			alert("Start sequence cannot be empty!");
			return;
		}
	
		if (recnumber.match(/[^0-9]+/) != null) {
			alert("Start sequence should be numeric.");
			return;
		}
	
		VteJS_DialogBox.progress();
		button.disabled = true;
	
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Settings&action=SettingsAjax&file=CustomModEntityNo&ajax=true' + 
						'&selmodule=' + encodeURIComponent(module) +
						'&mode=' + encodeURIComponent(mode),
			success: function(result) {
				VteJS_DialogBox.hideprogress();
				jQuery('#customentity_infodiv').html(result);
			}
		});
	},

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.CustomModEntityNo class.
 */

function getModuleEntityNoInfo(form) {
	return VTE.callDeprecated('getModuleEntityNoInfo', VTE.Settings.CustomModEntityNo.getModuleEntityNoInfo, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.CustomModEntityNo class.
 */

function updateModEntityNoSetting(button, form) {
	return VTE.callDeprecated('updateModEntityNoSetting', VTE.Settings.CustomModEntityNo.updateModEntityNoSetting, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.CustomModEntityNo class.
 */

function updateModEntityExisting(button, form) {
	return VTE.callDeprecated('updateModEntityExisting', VTE.Settings.CustomModEntityNo.updateModEntityExisting, arguments);
}