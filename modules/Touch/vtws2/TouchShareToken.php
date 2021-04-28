<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchShareToken extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {

		$CRMVUtils = CRMVUtils::getInstance();
		$CRMVUtils->shareTokenDuration = 30; // 30 seconds

		$module = $request['module'];
		$recordid = $request['recordid'];

		if ($module == 'Messages' && isset($request['contentid'])) {
			$contentid = intval($request['contentid']);
			$params = array('contentid'=>$contentid);
		}

		$token = $CRMVUtils->generateShareToken($module, $recordid, false, $params);

		return $this->success(array('token' => $token ));
	}

}
