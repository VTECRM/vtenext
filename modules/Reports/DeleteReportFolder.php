<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


/* crmv@97237 */

global $adb,$table_prefix;

$local_log =& LoggerManager::getLogger('index');
$rfid = intval($_REQUEST['record']);
if($rfid != "") {
	$records_in_folder = $adb->pquery("SELECT reportid from ".$table_prefix."_report WHERE folderid=?",array($rfid));
	if($adb->num_rows($records_in_folder)>0){
		echo getTranslatedString('LBL_FLDR_NOT_EMPTY',"Reports");
	} else {
		// crmv@30967
		$result = deleteEntityFolder($rfid);
		// crmv@30967e
		if ($result) {
			header("Location: index.php?action=ReportsAjax&mode=ajax&file=ListView&module=Reports");
		} else {
			include('modules/VteCore/header.php');	//crmv@30447
			$errormessage = "<font color='red'><B>Error Message<ul>
			<li><font color='red'>Error while deleting the folder</font>
			</ul></B></font> <br>" ;
			echo $errormessage;
		}
	}
}
