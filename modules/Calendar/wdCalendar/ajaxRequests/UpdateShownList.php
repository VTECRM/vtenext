<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb,$current_user;
$adb->pquery('update tbl_s_showncalendar set selected = ? where userid = ?',array(0,$current_user->id));
$checkedUsers = explode(',',$_REQUEST['checkedUsers']);
foreach($checkedUsers as $shownid)
	$adb->pquery('update tbl_s_showncalendar set selected = ? where userid = ? and shownid = ?',array(1,$current_user->id,$shownid));
?>