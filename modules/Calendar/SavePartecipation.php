<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@26807
//crmv@17001
global $adb,$table_prefix;
if ($from == 'invite_con') {
	$tab_name = $table_prefix.'_invitees_con';
}
else {
	$tab_name = $table_prefix.'_invitees';
}

$adb->pquery("update ".$tab_name." set partecipation = ? where activityid = ? and inviteeid = ?",array($_REQUEST['partecipation'],$_REQUEST['activityid'],$_REQUEST['userid']));
//crmv@17001e

//crmv@zmerge
if ($adb->table_exist('tbl_s_zmerge_events')) {
	require_once('modules/Calendar/ZMergeUtils.php');
	ZMerge::setZMergeEvents($_REQUEST['activityid']);
}
//crmv@zmerge e

//crmv@32334
$focus = CRMEntity::getInstance('Events');
$focus->id = $_REQUEST['activityid'];
$focus->sendInvitationAnswer($_REQUEST['partecipation'],$_REQUEST['activityid'],$_REQUEST['userid'],$from);	//crmv@26030m
//crmv@32334 e
//crmv@26807e
?>