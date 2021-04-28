/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.ListLoginHistory = VTE.Settings.ListLoginHistory || {

	fetchLoginHistory: function(id) {
		var oUser_combo = jQuery('#user_list');
		var id = oUser_combo.val();
		if (id == 'none') {
			jQuery('#login_history_cont').fadeOut(); // crmv@168103
		} else {
			jQuery("#status").show();
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: 'module=Users&action=UsersAjax&file=ShowHistory&ajax=true&record='+id,
				success: function(result) {
					jQuery("#status").hide();
					jQuery('#login_history_cont').html(result);
					jQuery('#login_history_cont').fadeIn(); // crmv@168103
				}
			});
		}
	},

	getListViewEntries_js: function(module, url) {
		var oUser_combo = jQuery('#user_list');
		var id = oUser_combo.val();
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: "module="+module+"&action="+module+"Ajax&file=ShowHistory&record="+id+"&ajax=true&"+url,
			success: function(result) {
				jQuery("#status").hide();
				jQuery('#login_history_cont').html(result);
			}
		});
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.ListLoginHistory class.
 */

function fetchLoginHistory(id) {
	return VTE.callDeprecated('fetchLoginHistory', VTE.Settings.ListLoginHistory.fetchLoginHistory, arguments);
}