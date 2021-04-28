<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function get_active_languages($associative=false){
	global $adb,$table_prefix;
	$query = "select prefix,label from {$table_prefix}_language where active = 1";
	$result = $adb->pquery($query, array());
	while($row = $adb->fetch_array($result)){
		$language[] = Array('prefix'=>$row['prefix'],'label'=>$row['label']);
	}
	return $language;

}
function get_active_languages_prefix(){
	global $adb,$table_prefix;
	$query = "select prefix from {$table_prefix}_language where active = 1";
	$result = $adb->pquery($query, array());
	while($row = $adb->fetch_array($result)){
		$language[] = $row['prefix'];
	}
	return $language;

}
?>