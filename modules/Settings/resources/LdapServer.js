/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTE = window.VTE || {};

VTE.Settings = VTE.Settings || {};

VTE.Settings.LdapServer = VTE.Settings.LdapServer || {

	validate: function() {
		if (!emptyCheck("ldap_host", "LDAP Server Name", "text")) return false;
		if (!emptyCheck("ldap_port", "Port Number", "text")) return false;
		if (!emptyCheck("ldap_basedn", "BaseDn", "text")) return false;
		if (!emptyCheck("ldap_objclass", "Objclass", "text")) return false;
		if (!emptyCheck("ldap_account", "User Account", "text")) return false;

		if (isNaN(document.tandc.ldap_port.value)) {
			alert(alert_arr.LBL_ENTER_VALID_PORT);
			return false;
		}

		return true;
	},

	open_Popup: function() {
		openPopup("index.php?module=Users&action=UsersAjax&file=RolePopup&parenttab=Settings", "roles_popup_window", "height=425,width=640,toolbar=no,menubar=no,dependent=yes,resizable =no", '', 640, 425);
	}

};

/**
 * @deprecated
 * This function has been moved to VTE.Settings.LdapServer class.
 */

function validate() {
	return VTE.callDeprecated('validate', VTE.Settings.LdapServer.validate, arguments);
}

/**
 * @deprecated
 * This function has been moved to VTE.Settings.LdapServer class.
 */

function open_Popup() {
	return VTE.callDeprecated('open_Popup', VTE.Settings.LdapServer.open_Popup, arguments);
}