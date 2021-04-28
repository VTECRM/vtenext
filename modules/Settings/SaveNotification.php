<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $default_charset;
global $adb, $table_prefix;

$conv_sub = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $_REQUEST['notifysubject']) : $_REQUEST['notifysubject']; // crmv@167702
$conv_body = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $_REQUEST['notifybody']) : $_REQUEST['notifybody']; // crmv@167702
$notifysubject = str_replace(array("'",'"'),'',$conv_sub);
$notifybody = str_replace(array("'",'"'),'',$conv_body);

if($notifysubject != '' && $notifybody != '') {
	if(isset($_REQUEST['record']) && $_REQUEST['record']!='') {	
		$query="UPDATE ".$table_prefix."_notifyscheduler set notificationsubject=?, notificationbody=?, active =? where schedulednotificationid=?";
		$params = array($notifysubject, $notifybody, $_REQUEST['active'], $_REQUEST['record']);
		$adb->pquery($query, $params);
	}

	header("Location: index.php?action=SettingsAjax&file=listnotificationschedulers&module=Settings&directmode=ajax");
} else {
	echo ":#:FAILURE";
}
