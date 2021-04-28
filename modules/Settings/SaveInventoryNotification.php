<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;

if(isset($_REQUEST['record']) && $_REQUEST['record']!='') {
	$query="UPDATE ".$table_prefix."_inventorynotify set notificationsubject=?, notificationbody=? where notificationid=?";
	$params = array($_REQUEST['notifysubject'], $_REQUEST['notifybody'], intval($_REQUEST['record']));
	$adb->pquery($query, $params);	
}

header("Location: index.php?action=SettingsAjax&file=listinventorynotifications&module=Settings&directmode=ajax");
