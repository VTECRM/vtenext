<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29079
global $sdk_mode;
switch($sdk_mode) {
	case '':
	case 'edit':
		if ($fieldname == 'parent_comments') {
			$readonly = 100;
			$success = true;
		}
		if (intval($col_fields['parent_comments']) > 0) {
			if (in_array($fieldname,array('assigned_user_id','visibility_comm'))) {
				$readonly = 100;
				$success = true;
			}
		}
		break;
}
//crmv@29079e
?>