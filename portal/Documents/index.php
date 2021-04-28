<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@90004 crmv@173271 */

$block = 'Documents';

(file_exists ( "$block/header.html" )) ? $header = "$block/header.html" : $header = 'VteCore/header.html';
include ($header);

@include ("../PortalConfig.php");
if (! isset ( $_SESSION ['customer_id'] ) || $_SESSION ['customer_id'] == '') {
	@header ( "Location: $Authenticate_Path/login.php" );
	exit ();
}

$sessionid = $_SESSION ['customer_sessionid'];
$customerid = $_SESSION ['customer_id'];
$id = portal_purify($_REQUEST['id']);

$folderid = portal_purify($_REQUEST['folderid']); 

if (empty($id) && empty($folderid)) {
	include ("DocumentsListFolder.php");
} else if(!empty($folderid)) {
	include ("VteCore/List.php");
} else {
	(file_exists("$block/Detail.php")) ? $detail = "$block/Detail.php" : $detail = 'VteCore/Detail.php';
	include($detail);
}

(file_exists ( "$block/footer.html" )) ? $footer = "$block/footer.html" : $footer = 'VteCore/footer.html';
include ($footer);