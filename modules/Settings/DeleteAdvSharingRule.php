<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb;
$shareid =  $_REQUEST['shareid'];
deleteAdvSharingRule($shareid);
if (isset($_REQUEST['recalculate']) && $_REQUEST['recalculate']=='true' ){
	require_once('modules/Users/CreateUserPrivilegeFile.php');
	createUserSharingPrivilegesfile($_REQUEST['record']);
	header("Location: index.php?module=".$_REQUEST['return_module']."&action=DetailView&parenttab=Settings&record=".$_REQUEST['record']."&sharing=true");
}
elseif (isset($_REQUEST['return_module']) && isset($_REQUEST['record'])) header("Location: index.php?module=".$_REQUEST['return_module']."&action=DetailView&parenttab=Settings&record=".$_REQUEST['record']);
else header("Location: index.php?module=Settings&action=AdvRuleDetailView&parenttab=Settings");