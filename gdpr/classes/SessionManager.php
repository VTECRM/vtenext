<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

namespace GDPR;

defined('BASEPATH') OR exit('No direct script access allowed');

class SessionManager {
	
	private static $instance = null;

	public function set($key, $value = '') {
		if (is_array($key)) return self::setMulti($key);
		$_SESSION[$key] = $value;
	}

	public function get($key) {
		return $_SESSION[$key];
	}

	public function remove($key) {
		unset($_SESSION[$key]);
	}

	public function hasKey($key) {
		return isset($_SESSION[$key]);
	}

	public function setMulti($keys) {
		if (is_array($keys)) {
			foreach ($keys as $key => $value) {
				$_SESSION[$key] = $value;
			}
		}
	}

	public function removeMulti($keys) {
		if (is_array($keys)) {
			foreach ($keys as $key) {
				unset($_SESSION[$key]);
			}
		}
	}
	
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new SessionManager();
		}
		return self::$instance;
	}

}