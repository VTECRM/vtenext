<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@42024 - separators

global $sdk_mode, $current_user, $default_timezone; // crmv@25610
global $default_decimal_separator, $default_thousands_separator, $default_decimals_num;

switch($sdk_mode) {
	case '':
	case 'create':
		// crmv@25610
		if ($fieldname == 'user_timezone') {
			$col_fields[$fieldname] = $default_timezone;
		}
		// crmv@25610e
	case 'edit':
		if ($fieldname == 'exchange_sync_ldap') {
			$readonly = 100;
			$success = true;
		}
	case 'detail':
		if ($fieldname == 'exchange_password' && $col_fields['exchange_sync_ldap'] == 1) {
			$readonly = 99;
			$success = true;
		}
		//crmv@188009
		if ($fieldname == 'email1' && !is_admin($current_user)) {
			$readonly = 99;
			$success = true;
		}
		//crmv@188009e
		break;
}

// switch con break
switch($sdk_mode) {
	case '':
	case 'create':
		if ($_REQUEST['isDuplicate'] != 'true') {
			if ($fieldname == 'decimal_separator') {
				$col_fields[$fieldname] = $current_user->convertFromSeparatorValue($default_decimal_separator);
			} elseif ($fieldname == 'thousands_separator') {
				$col_fields[$fieldname] = $current_user->convertFromSeparatorValue($default_thousands_separator);
			// crmv@53923
			} elseif ($fieldname == 'decimals_num') {
				$col_fields[$fieldname] = $default_decimals_num;
			}
			// crmv@53923e
		} elseif (in_array($fieldname, array('decimal_separator', 'thousands_separator'))) {
			$col_fields[$fieldname] = $current_user->convertFromSeparatorValue($col_fields[$fieldname]);
		}
		break;
	case 'edit':
		if ($fieldname == 'decimal_separator' || $fieldname == 'thousands_separator') {
			$col_fields[$fieldname] = $current_user->convertFromSeparatorValue($col_fields[$fieldname]);
		}
		break;
	case 'detail':
		if ($fieldname == 'decimal_separator' || $fieldname == 'thousands_separator') {
			$col_fields[$fieldname] = $current_user->convertFromSeparatorValue($col_fields[$fieldname]);
		}
		break;
}

if (($sdk_mode == '' || $sdk_mode == 'create') && in_array($fieldname,array('internal_mailer','no_week_sunday','allow_generic_talks','receive_public_talks','enable_activesync'))) {
	$col_fields[$fieldname] = 1;
}
if (in_array($fieldname,array('allow_generic_talks','receive_public_talks'))) {
	if ($sdk_mode == '') {
		$col_fields[$fieldname] = 1;
		$success = true;
	}
	if (!is_admin($current_user)) {
		$readonly = 100;
		$success = true;
	}
}
// crmv@34873
if (in_array($fieldname,array('enable_activesync',))) {
	if ($sdk_mode == '' || $sdk_mode == 'create') $col_fields[$fieldname] = 0;
	if (!is_admin($current_user)) $readonly = 100;
	$success = true;
}
// crmv@34873e
eval(Users::m_de_cryption());
eval($hash_version[12]);
?>