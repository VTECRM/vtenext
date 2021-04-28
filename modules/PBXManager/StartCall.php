<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/**
 * this function starts the call, it writes the caller and called information to database where it is picked up from
 */
 echo startCall();
 
 
 
function startCall(){	
	global $current_user, $adb,$log;
	require_once 'include/utils/utils.php';
	require_once 'modules/PBXManager/utils/AsteriskClass.php';
	require_once('modules/PBXManager/AsteriskUtils.php');
	global $table_prefix;
	$id = $current_user->id;
	$number = $_REQUEST['number'];
	$record = $_REQUEST['recordid'];
	$result = $adb->query("select * from ".$table_prefix."_asteriskextensions where userid=".$current_user->id);
	$extension = $adb->query_result($result, 0, "asterisk_extension");
	$data = getAsteriskInfo($adb);
	$server = $data['server'];
	$port = $data['port'];
	$username = $data['username'];
	$password = $data['password'];
	$version = $data['version'];
	$errno = $errstr = NULL;
	$sock = fsockopen($server, $port, $errno, $errstr, 1);
	stream_set_blocking($sock, false);
	if( $sock === false ) {
		echo "Socket cannot be created due to error: $errno:  $errstr\n";
		$log->debug("Socket cannot be created due to error:   $errno:  $errstr\n");
		exit(0);
	}
	$asterisk = new Asterisk($sock, $server, $port);
	
	loginUser($username, $password, $asterisk);

	$asterisk->transfer($extension,$number);
	
	$callerModule = getSalesEntityType($record);
	$entityNames = getEntityName($callerModule, array($record));
	$callerName = $entityNames[$record];
	$callerInfo = array('id'=>$record, 'module'=>$callerModule, 'name'=>$callerName);
	
	//adds to pbx manager
	addToCallHistory($extension, $extension, $number, "outgoing", $adb, $callerInfo);
	
	// add to the records activity history
	addOutgoingcallHistory($current_user ,$extension,$record ,$adb);
				
}
?>