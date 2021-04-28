<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@173271 */
 
$block = 'Accounts';

$customerid = $_SESSION['customer_id'];

// force to view the customer's account
if($_REQUEST['id'] == '') {
	$params = Array('id'=>$customerid);
	$id = $client->call('get_check_account_id', $params, $Server_Path, $Server_Path);
} else {
	$id = $_REQUEST['id'];
}

if (!empty($id)) {
	(file_exists("$block/Detail.php")) ? $detail = "$block/Detail.php" : $detail = 'VteCore/Detail.php';
	include($detail);
}else{
	$moduleObj->displayNotAvailable();
}