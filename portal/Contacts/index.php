<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@173271 */
 
$block = 'Contacts';

@include ("../PortalConfig.php");
if (! isset ( $_SESSION ['customer_id'] ) || $_SESSION ['customer_id'] == '') {
	@header ( "Location: $Authenticate_Path/login.php" );
	exit ();
}

$customerid = $_SESSION ['customer_id'];
$sessionid = $_SESSION ['customer_sessionid'];
$id = portal_purify($_REQUEST['id']);
if (empty($id)) {
	include ("VteCore/List.php");
} elseif($id != '' && isset($_REQUEST['fun']) && $_REQUEST['fun'] == 'unsubscribe') {
	if($id != $customerid){
		$smarty->display('NotAuthorized.tpl');
		die();
	}else{	
		include('unsubscribe.php');
	}
} else {
	(file_exists("$block/Detail.php")) ? $detail = "$block/Detail.php" : $detail = 'VteCore/Detail.php';
	include($detail);
}
?>