<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@113771
global $sdk_mode;
switch($sdk_mode) {
	case '':
	case 'create':
	case 'edit':
	case 'detail':
		if ($fieldname == 'assigned_user_id') {
			$readonly = 100;
			$success = true;
		}
		break;
}