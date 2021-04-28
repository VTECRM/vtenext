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

$del_id =  $_REQUEST['delete_role_id'];
$tran_id = $_REQUEST['user_role'];

deleteRole($del_id,$tran_id);

header("Location: index.php?action=listroles&module=Settings");