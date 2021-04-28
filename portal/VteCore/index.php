<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@173271 */
 
$module = trim($module, '/');
$block = $module;

// TODO: deprecate these htmls
(file_exists ( "$block/header.html" )) ? $header = "$block/header.html" : $header = 'VteCore/header.html';
include ($header);

@include ("../PortalConfig.php");
if (! isset ( $_SESSION ['customer_id'] ) || $_SESSION ['customer_id'] == '') {
	@header ( "Location: $Authenticate_Path/login.php" );
	exit ();
}

$customerid = $_SESSION ['customer_id'];
$id = portal_purify($_REQUEST['id']);

if (empty($id)) {
	(file_exists("$block/List.php")) ? $list = "$block/List.php" : $list = 'VteCore/List.php';
	include ($list);
} else {
	$status = $_REQUEST['status'];
	(file_exists("$block/Detail.php")) ? $detail = "$block/Detail.php" : $detail = 'VteCore/Detail.php';
	include($detail);
}

// TODO: deprecate these htmls
(file_exists ( "$block/footer.html" )) ? $footer = "$block/footer.html" : $footer = 'VteCore/footer.html';
include ($footer);