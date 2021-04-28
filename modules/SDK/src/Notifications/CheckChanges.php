<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@OPER5904 */
$plugin = vtlib_purify($_REQUEST['plugin']);
if (strpos($plugin,',') !== false) {
	$plugins = explode(',',$plugin);
} else {
	$plugins = array($plugin);
}
$res = array();
foreach ($plugins as $plugin) {
	ob_start();
	include('modules/SDK/src/Notifications/plugins/'.$plugin.'CheckChanges.php');
	$count = ob_get_contents();
	ob_end_clean();
	$res[$plugin] = $count;
}
echo Zend_Json::encode($res);
exit;
?>