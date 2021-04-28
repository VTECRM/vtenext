<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@26986
global $current_user;
$record = (int)$_REQUEST['record'];
if ($record != '' && $record != 0) {
	require_once('modules/SDK/src/Favorites/Utils.php');
	echo setFavorite($current_user->id,$record);
	echo '###'.getHtmlFavoritesList($current_user->id);
	exit;
}
//crmv@26986e
?>