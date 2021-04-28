<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$block = 'Potentials';

$only_mine = (isset ( $_REQUEST ['only_mine'] )) ? " checked " : "";

@include ("../PortalConfig.php");
if (! isset ( $_SESSION ['customer_id'] ) || $_SESSION ['customer_id'] == '') {
	@header ( "Location: $Authenticate_Path/login.php" );
	exit ();
}

global $result;

$customerid = $_SESSION ['customer_id'];
$sessionid = $_SESSION ['customer_sessionid'];
$id = portal_purify($_REQUEST['id']);

if (empty($id) && !isset($_REQUEST['fun'])) {
	include ($block."List.php");
} elseif(isset($_REQUEST['fun']) && $_REQUEST['fun'] == 'newpotentials'){  //elseif($id != '' && isset($_REQUEST['fun']) && $_REQUEST['fun'] == 'newpotentials') {	
	include('NewPotentials.php');
}elseif($_REQUEST['fun'] == 'SavePotentials'){
		include("SavePotentials.php");
} else {
	(file_exists($block."/Detail.php")) ? $detail = $block."/Detail.php" : $detail = 'VteCore/Detail.php';
	include($detail);
}
?>