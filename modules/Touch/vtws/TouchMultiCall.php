<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* Calls multiple webservices at once */
global $adb, $table_prefix;
global $login, $userId, $current_user, $currentModule;

// array in the form [wsname=>'', 'params'=>array())
$wslist = Zend_Json::decode($_REQUEST['wslist']);

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (!is_array($wslist)) {
	echo "Wrong data supplied";
} else {

	$globalWsOutput = array();
	foreach ($wslist as $multiwsinfo) {
		$wsname = $multiwsinfo['wsname'];
		$filename = $touchInst->getWSFile($wsname);

		// TODO: in some WS ob_* is used, better to remove it
		if ($filename && is_readable($filename)) {
			$_REQUEST = $multiwsinfo['wsparams'];
			$_REQUEST['wsname'] = $wsname;
			ob_start();
			require($filename);
			$wsOut = ob_get_clean();
			ob_end_clean();
			// unencode if was json encoded
			try {
				$wsOut_dec = Zend_Json::decode($wsOut);
				if ($wsOut_dec != null) $wsOut = $wsOut_dec;
			} catch (Exception $e) {

			}
			$globalWsOutput[$wsname] = $wsOut;
		}
	}

	echo Zend_Json::encode($globalWsOutput);
}
?>