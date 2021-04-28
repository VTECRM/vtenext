<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@161021 */
global $sdk_mode;
switch($sdk_mode) {
	case 'edit':
	case 'detail':
		if (!isset($focusEmployees)) $focusEmployees = CRMEntity::getInstance('Employees');
		if (!empty($col_fields['role']) && in_array($fieldname,array_keys($focusEmployees->synchronizeUserMapping))) {
			$readonly = 99;
			$success = true;
		}
		break;
}