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

$shareid =  $_REQUEST['shareid'];
deleteSharingRule($shareid);

//crmv@7222
if (isset($_REQUEST['recalculate']) && $_REQUEST['recalculate']=='true' ) {
	require_once('modules/Users/CreateUserPrivilegeFile.php');
	createUserSharingPrivilegesfile($_REQUEST['record']);
	header("Location: index.php?module=".$_REQUEST['return_module']."&action=DetailView&parenttab=Settings&record=".$_REQUEST['record']."&sharing=true");
} elseif (isset($_REQUEST['return_module']) && isset($_REQUEST['record'])) {
	header("Location: index.php?module=".$_REQUEST['return_module']."&action=DetailView&parenttab=Settings&record=".$_REQUEST['record']);
} else {
	header("Location: index.php?module=Settings&action=OrgSharingDetailView&parenttab=Settings");
}
//crmv@7222e