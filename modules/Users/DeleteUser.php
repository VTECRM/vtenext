<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@37463
global $adb, $table_prefix;
global $current_user;
$del_id =  intval($_REQUEST['delete_user_id']);
$tran_id = intval($_REQUEST['transfer_user_id']);

if (isPermitted('Users', 'Delete') != 'yes') {
	header("Location: index.php?action=ListView&module=Users");
	die();
}
// crmv@37463e

//crmv@161021
$focus = CRMEntity::getInstance('Employees');
if ($focus->synchronizeUser) $focus->syncUserEmployee($del_id,'delete');
//crmv@161021e

// crmv@184231
$focusUsers = CRMEntity::getInstance('Users');
$focusUsers->deleteUser($del_id, $tran_id);
// crmv@184231e

//if check to delete user from detail view
if(isset($_REQUEST["ajax_delete"]) && $_REQUEST["ajax_delete"] == 'false')
	header("Location: index.php?action=ListView&module=Users");
else
	header("Location: index.php?action=UsersAjax&module=Users&file=ListView&ajax=true&deleteuser=true");