<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $result;
global $client;
global $Server_Path;


$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

$fieldnames = array();
$values = array();
include('Contacts/config.php');
foreach ($_REQUEST as $l => $v) {
	if (in_array($l,$permittedFields)) {
		$fieldnames[] = $l;
		$values[] = $v;
	}
}

$params = array('contactid'=>"$customerid",'sessionid'=>"$sessionid",'fieldnames'=>$fieldnames,'values'=>$values);
$result = $client->call('save_contact_profile', $params, $Server_Path, $Server_Path);

header("Location: index.php?module=Contacts&action=index&id={$customerid}&profile=yes");
exit;
?>