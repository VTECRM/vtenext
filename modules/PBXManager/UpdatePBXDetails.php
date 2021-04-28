<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
savePBXDetails();

function savePBXDetails(){
	global $adb;
	global $table_prefix;
	$semodule = $_REQUEST['semodule'];
	
	if($semodule == 'asterisk'){
		$server = $_REQUEST['qserver'];
		$port = $_REQUEST['qport'];
		$username = $_REQUEST['qusername'];
		$password = $_REQUEST['qpassword'];
		$version = $_REQUEST['version'];
		
		//crmv@43764
		if ($password == '') {
			$result = $adb->query("select password from {$table_prefix}_asterisk");
			if ($result && $adb->num_rows($result) > 0) {
				$password = $adb->query_result($result,0,'password');
			}
		}
		//crmv@43764e
		
		$sql = "delete from ".$table_prefix."_asterisk";
		$adb->query($sql);	//delete older records (if any)
		
		$sql = "insert into ".$table_prefix."_asterisk (server, port, username, password, version) values (?,?,?,?,?)";
		$params = array($server,$port, $username, $password, $version);
		$adb->pquery($sql, $params);
	}
}
?>