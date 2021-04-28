<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if (!defined('VTESYNC_BASEDIR')) {
	define('VTESYNC_BASEDIR', dirname(__FILE__));
}

function VteSyncLibAutoload($class) {
	list($ns, $xx) = explode('\\', $class, 2);
	if ($ns === 'VteSyncLib') {
		$file = VTESYNC_BASEDIR.'/src/'.str_replace('\\', '/', $xx).'.php';
		if (file_exists($file)) {
			require_once($file);
		}
	}
}

spl_autoload_register('VteSyncLibAutoload');