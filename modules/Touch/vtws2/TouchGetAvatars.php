<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetAvatars extends TouchWSClass {

	public $emptyAvatar = 'themes/images/no_avatar.png';

	function process(&$request) {
		$userids = array_filter(array_map('intval', Zend_Json::decode($request['userids']) ?: array()));
		$showEmpty = ($request['show_empty_avatar'] == 1);
		$format = $request['format'];	// One of: 'dataurl', 'raw'
		
		if ($showEmpty) $userids[] = 0;

		if (count($userids) == 0) {
			return $this->error("No users specified");
		}
		
		if ($format == 'raw' && count($userids) != 1) {
			return $this->error("With raw format, only one user can be specified");
		}
		
		if ($format == 'raw') {
			return $this->outputRawImage($userids[0]);
		} elseif ($format == 'dataurl') {
			return $this->outputDataUrlImages($userids);
		} else {
			return $this->error("No valid format specified");
		}
	}
	
	protected function outputDataUrlImages($userids) {
		
		// get all the users (should be cached anyway)
		$req = array();
		$users = $this->subcall('GetUsers', $req);
		$out = array();
		
		foreach ($users['users'] as $user) {
			$userid = $user['userid'];
			if (in_array($userid, $userids)) {
				$file = $user['preferencies']['avatar'];
				if (is_readable($file)) {
					if (preg_match('/\.png$/i', $file)) {
						$imageFormat = 'png';
					} elseif (preg_match('/\.jpe?g$/i', $file)) {
						$imageFormat = 'jpeg';
					} else {
						continue;
					}
					$out[] = array(
						'userid' => $userid,
						'avatar' => "data:image/$imageFormat;base64,". base64_encode(file_get_contents($file)),
					);
				}
			}
		}
		
		// show the empty one also, with userid 0
		if (in_array(0, $userids)) {
			$userid = 0;
			$file = $this->emptyAvatar;
			if (is_readable($file)) {
				if (preg_match('/\.png$/i', $file)) {
					$imageFormat = 'png';
				} elseif (preg_match('/\.jpe?g$/i', $file)) {
					$imageFormat = 'jpeg';
				// crmv@146653
				} else {
					$imageFormat = null;
				}
				if ($imageFormat) {
					$out[] = array(
						'userid' => $userid,
						'avatar' => "data:image/$imageFormat;base64,". base64_encode(file_get_contents($file)),
					);
				}
				// crmv@146653e
			}
		}
		
		return $this->success(array('avatars'=>$out, 'total'=>count($out)));
	}
	
	protected function outputRawImage($userid) {
		global $adb, $table_prefix;
		
		if ($userid > 0) {
			$res  = $adb->pquery("select avatar from {$table_prefix}_users where id = ?", array($userid));
			if (!$res || $adb->num_rows($res) != 1) {
				header("HTTP/1.0 404 Not Found");
				die();
			}
			$row = $adb->fetchByAssoc($res, -1, false);
			$file = $row['avatar'];
		} else {
			$file = $this->emptyAvatar;
		}
		
		if (!is_readable($file)) {
			header("HTTP/1.0 404 Not Found");
			die();
		}
		
		// output header
		if (preg_match('/\.png$/i', $file)) {
			header('Content-Type: image/png');
		} elseif (preg_match('/\.jpe?g$/i', $file)) {
			header('Content-Type: image/jpeg');
		} else {
			header("HTTP/1.0 500 Internal server error");
			die();
		}
		
		$res = @readfile($file);
		if ($res == 0) {
			header("HTTP/1.0 500 Internal server error");
		}
		
		die();
	}
	
}