<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@38798


global $default_charset;
global $adb, $table_prefix;

$check = $_REQUEST['check'];

if ($check == 'reportCheck') {
	$reportName = $_REQUEST['reportName'];
	$reportId = intval($_REQUEST['reportid']);
	$isDuplicate = $_REQUEST['isDuplicate'];
	$sSQL = "select reportid from ".$table_prefix."_report where reportname=?";
	if ($reportId > 0 && $isDuplicate != 'true') $sSQL .= " and reportid != ?";

	$sqlresult = $adb->pquery($sSQL, array(trim($reportName), $reportId));
	echo $adb->num_rows($sqlresult);

} elseif ($check == 'folderCheck') {
	$folderName = function_exists('iconv') ? @iconv("UTF-8",$default_charset, $_REQUEST['folderName']) : $_REQUEST['folderName']; // crmv@167702
	$folderName =str_replace(array("'",'"'),'',$folderName);
	if($folderName == "" || !$folderName) {
		echo "999";
	} else {
		// crmv@30967
		$SQL="select folderid from ".$table_prefix."_crmentityfolder where tabid = ? and foldername=?";
		$sqlresult = $adb->pquery($SQL, array(getTabid('Reports'), trim($folderName)));
		// crmv@30967e
		$id = $adb->query_result($sqlresult,0,"folderid");
		echo trim($adb->num_rows($sqlresult)."::".$id);
	}
}
exit;
?>