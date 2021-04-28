<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@184240 */

global $current_user;

if (!is_admin($current_user)) {
	// redirect to settings, where an error will be shown
	header("Location: index.php?module=Settings&action=index&parenttab=Settings");
	die();
}

$del_id =  $_REQUEST['delete_group_id'];
$transfer_group_id = $_REQUEST['transfer_group_id'];
$assignType = $_REQUEST['assigntype'];

if($assignType == 'T') {
	$transferId = $_REQUEST['transfer_group_id'];
} elseif($assignType == 'U') {
	$transferId = $_REQUEST['transfer_user_id'];
}

//Updating the user2 vte_role vte_table
deleteGroup($del_id,$transferId);

header("Location: index.php?action=listgroups&module=Settings&parenttab=Settings");