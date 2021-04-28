<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

class TouchSetFavorites extends TouchWSClass {

	public function process(&$request) {
		global $current_user;

		require_once('modules/SDK/src/Favorites/Utils.php');

		$ids = array_map('intval', explode(':', $request['records']));
		foreach ($ids as $id) {
			setFavorite($current_user->id, $id);
		}

		return $this->success();
	}
}
