<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */
global $login, $userId, $current_user;


if (!$login || !$userId) {
	echo 'Login Failed';
} else {


	$files = glob('modules/Touch/overrides/*.js');
	if ($files && count($files) > 0) {
		foreach ($files as $k=>$f) {
			if (!is_readable($f) || filesize($f) <= 0) {
				unset($files[$k]);
			}
		}
	} else {
		$files = array();
	}
	
	// crmv@78141
	$cssFiles = glob('modules/Touch/overrides/css_overrides/*.css');
	if ($cssFiles && count($cssFiles) > 0) {
		foreach ($cssFiles as $k=>$f) {
			if (is_readable($f) && filesize($f) > 0) {
				$files[] = $f;
			}
		}
	}
	
	$cssFiles = glob('modules/Touch/overrides/css_extra/*.css');
	if ($cssFiles && count($cssFiles) > 0) {
		foreach ($cssFiles as $k=>$f) {
			if (is_readable($f) && filesize($f) > 0) {
				$files[] = $f;
			}
		}
	}
	// crmv@78141e

	echo Zend_Json::encode($files);
}
?>