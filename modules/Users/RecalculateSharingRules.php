<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user;

//crmv@184240
if (!is_admin($current_user)) {
	// redirect to settings, where an error will be shown
	header("Location: index.php?module=Settings&action=index&parenttab=Settings");
	die();
}
//crmv@184240e

/* crmv@74560 */
require_once('modules/Users/CreateUserPrivilegeFile.php');

// crmv@199834
$SP = new SharingPrivileges();
$SP->recalcNowOrSchedule();
// crmv@199834e

header("Location: index.php?action=OrgSharingDetailView&parenttab=Settings&module=Settings");