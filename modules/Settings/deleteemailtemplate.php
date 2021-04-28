<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb, $table_prefix;
$idlist = $_REQUEST['idlist'];
$id_array = array_filter(explode(';', $idlist));

//crmv@37290
if ($_REQUEST['mode'] == 'check') {
	$result = $adb->query("SELECT newsletterid FROM {$table_prefix}_newsletter WHERE templateemailid IN (".$adb->sql_escape_string(implode(',',$id_array)).")");//crmv@208173
	if ($result && $adb->num_rows($result) > 0) {
		echo 'NOT_SUCCESS';
	} else {
		echo 'SUCCESS';
	}
	exit;
}
//crmv@37290e

for($i=0;$i<=count($id_array);$i++) {
	$sql = "delete from {$table_prefix}_emailtemplates where templateid = ?";
	$adb->pquery($sql,array($id_array[$i]));
	//crmv@37290
	$sql = "update {$table_prefix}_newsletter set templateemailid = ? where templateemailid = ?";
	$adb->pquery($sql,array(0,$id_array[$i]));
	//crmv@37290e
}

header("Location:index.php?module=Settings&action=listemailtemplates");
?>