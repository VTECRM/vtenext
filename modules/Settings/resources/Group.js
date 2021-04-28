/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.Group = VTE.Settings.Group || {

	deletegroup: function(obj, groupid) {
		jQuery("#status").show();
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Users&action=UsersAjax&file=GroupDeleteStep1&groupid='+groupid,
			success: function(result) {
				jQuery("#status").hide();
				jQuery("#tempdiv").html(result);
				showFloatingDiv('DeleteLay', obj);
			}
		});
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.Group class.
 */

function transferCurrency(obj, groupid) {
	return VTE.callDeprecated('deletegroup', VTE.Settings.Group.deletegroup, arguments);
}