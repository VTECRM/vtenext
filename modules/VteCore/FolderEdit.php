<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@90004 */

global $adb, $table_prefix;

$folderid = intval($_REQUEST['folderid']);
$mode = $_REQUEST['mode'];

if ($mode == 'save') {
	$foldername = $_REQUEST['foldername'];
	$description = $_REQUEST['description'];
	$up_info_folder = "UPDATE {$table_prefix}_crmentityfolder SET foldername = ?, description = ? WHERE folderid = ? ";
	$array = array('foldername'=>$foldername,'description'=>$description);
 	$adb->pquery($up_info_folder,array($foldername,$description,$folderid));
} else {
	$sql_info_folder = "SELECT foldername, description FROM {$table_prefix}_crmentityfolder WHERE folderid = ? ";
	$ris_info_folder = $adb->pquery($sql_info_folder, array($folderid));
	
	while($row_info_folder = $adb->fetchByAssoc($ris_info_folder)){
		$foldername = $row_info_folder['foldername'];
		$description = $row_info_folder['description'];
	}
	$array = array('foldername'=>$foldername,'description'=>$description);
}

echo json_encode($array);
exit();