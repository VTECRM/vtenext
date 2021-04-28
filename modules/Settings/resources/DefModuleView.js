/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.DefModuleView = VTE.Settings.DefModuleView || {

	// crmv@192033
	viewenabled: function(ochkbox) {
		if (ochkbox.checked == true) {
			var status = 'enabled';
			jQuery('#view_info').html('Singlepane View Enabled').show();
		} else {
			var status = 'disabled';
			jQuery('#view_info').html('Singlepane View Disabled').show();
		}
		
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'post',
			data: 'module=Users&action=UsersAjax&file=SaveDefModuleView&ajax=true', // crmv@202301
			success: function(result) {
				jQuery("#status").hide();
			}
		});
		
		setTimeout("hide('view_info')", 3000);
	}
	// crmv@192033e

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.DefModuleView class.
 */

function viewenabled(ochkbox) {
	return VTE.callDeprecated('viewenabled', VTE.Settings.DefModuleView.viewenabled, arguments);
}