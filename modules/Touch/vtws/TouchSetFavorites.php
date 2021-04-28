<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

global $login, $userId;

if (!$login || !$userId) {
	echo 'Login Failed';
} else {

	require_once('modules/SDK/src/Favorites/Utils.php');

	$ids = array_map('intval', explode(':', $_REQUEST['records']));
	foreach ($ids as $id) {
		setFavorite($userId, $id);
	}

	echo "SUCCESS";
}
?>