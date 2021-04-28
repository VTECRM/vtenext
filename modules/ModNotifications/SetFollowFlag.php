<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$record = vtlib_purify($_REQUEST['record']);
$type = vtlib_purify($_REQUEST['type']);
if (vtlib_purify($_REQUEST['mode']) != 'get_image') {
	$focus = CRMEntity::getInstance('ModNotifications');
	$focus->toggleFollowFlag($current_user->id,$record,$type);
}
echo ':#:SUCCESS';
echo getFollowCls($record,$type);
exit;