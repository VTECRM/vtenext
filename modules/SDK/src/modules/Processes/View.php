<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@103450 */
global $sdk_mode;
switch($sdk_mode) {
	case '':
	case 'edit':
	case 'detail':
	case 'mass_edit':
	case 'popup':
	case 'related':
	case 'list':
	case 'cv_columns':
		if ($fieldname == 'process_actor') {
			$readonly = 100;
			$success = true;
		}
		break;
}