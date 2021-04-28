<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings, $app_strings;
global $adb, $table_prefix;
global $theme,$default_charset;

if(isset($_REQUEST['record']) && $_REQUEST['record']!='') {
    
    $id = intval($_REQUEST['record']);
	$sql = "SELECT * FROM {$table_prefix}_inventorynotify WHERE notificationid = ?";
	$result = $adb->pquery($sql, array($id));
	
	if ($adb->num_rows($result) == 1) {
		$label = $mod_strings[$adb->query_result($result,0,'notificationname')];
		$notification_subject = $adb->query_result($result,0,'notificationsubject');
		$notification_body = function_exists('iconv') ? iconv("UTF-8",$default_charset,$adb->query_result($result,0,'notificationbody')) : $adb->query_result($result,0,'notificationbody'); // crmv@167702
		$notification_id = $adb->query_result($result,0,'notificationid');

		$notification = Array();
		$notification['label'] = $label;
		$notification['subject'] = $notification_subject;
		$notification['body'] = $notification_body;
		$notification['id'] = $notification_id;
	}
	
	$theme_path="themes/".$theme."/";
	$image_path=$theme_path."images/";

	$smarty = new VteSmarty();
	
	$smarty->assign("NOTIFY_DETAILS",$notification);
	$smarty->assign("MOD", return_module_language($current_language,'Settings'));
	$smarty->assign("IMAGE_PATH",$image_path);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("CMOD", $mod_strings);
	
	$smarty->display("Settings/EditInventoryNotify.tpl");
	
} else {
	header("Location:index.php?module=Settings&action=listnotificationschedulers&directmode=ajax");
}
