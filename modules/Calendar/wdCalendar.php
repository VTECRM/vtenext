<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@17001
if ($_REQUEST['subfile'] != '')
	$file = $_REQUEST['subfile'];
else
	$file = "sample";
	
//crmv@20324
global $adb,$current_user,$current_user_cal_color,$table_prefix,$theme;
$res = $adb->query('select cal_color from '.$table_prefix.'_users where id = '.$current_user->id);
$current_user_cal_color = $adb->query_result($res,0,'cal_color');
//crmv@20324e

$CSRF_TOKEN = RequestHandler::getCSRFToken(); // crmv@171581

// crmv@187406
if ($theme === 'next') {
	$darkMode = false;
	if (!empty($current_user) && $current_user->id !== null) {
		$darkMode = intval($current_user->column_fields['dark_mode']);
	}
}
// crmv@187406e

// crmv@345820
$file = preg_replace("/[^a-zA-Z0-9_\-\/]/", '', $file);
include("modules/Calendar/wdCalendar/{$file}.php");
// crmv@345820e
//crmv@17001e
?>