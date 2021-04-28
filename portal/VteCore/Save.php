<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@173271 */

$id = intval($_REQUEST['id']);
$r = $moduleObj->updateRecord($id, $_POST);

if ($r) {
	// success
	$_REQUEST['action'] = 'index'; 
	$customerid = $_SESSION['customer_id'];
	if ($moduleObj->getModule() == 'Contacts' && $id == $customerid) {
		unset($_REQUEST['update']);
		$_REQUEST['profile'] = 'yes';
		include('Contacts/Detail.php');
	} else {
		include('Detail.php');
	}
} else {
	$_REQUEST['action'] = 'Save';
	include('Edit.php');
}