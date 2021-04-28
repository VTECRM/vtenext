<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

namespace GDPR;

defined('BASEPATH') OR exit('No direct script access allowed');

class Redirect {
	
	public static function to($action = null, $params = array()) {
		global $CFG, $GPDRManager;

		$contactId = $GPDRManager->getContactId();
		$accessToken = $GPDRManager->getAccessToken();
		
		if ($action) {
			if (is_numeric($action)) {
				switch ($action) {
					case 404:
						header('HTTP/1.0 404 Not Found');
						include('actions/404.php');
						exit();
				}
			}
			
			$params['action'] = $action;
			
			if (!empty($accessToken)) $params['accesstoken'] = $accessToken;
			
			$params = http_build_query($params);
			
			header('Location: index.php?' . $params);
			exit();
		}
	}
	
}