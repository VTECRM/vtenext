/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.FieldAccess = VTE.Settings.FieldAccess || {

	changemodules: function(selectmodule) {
		jQuery('#' + def_field).hide();
		var module = selectmodule.options[selectmodule.options.selectedIndex].value;
		document.getElementById('fld_module').value = module; 
		window.def_field = module + "_fields";
		jQuery('#' + def_field).show();
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.FieldAccess class.
 */

function changemodules(selectmodule) {
	return VTE.callDeprecated('changemodules', VTE.Settings.FieldAccess.changemodules, arguments);
}