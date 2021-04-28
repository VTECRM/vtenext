<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181231 */

class VteSessionHandler {

	protected static $type;
	private static $handler;
    
    /**
     * Register a session handler of the specified type
     */
    public static function register($type, $params = array()) {
		if (self::$handler) {
			throw new Exception('A session handler is already registered');
		}
		
		$handlersDir = __DIR__.'/SessionHandlers/';
		
		if ($type === 'apc') {
			require_once($handlersDir.'APCSessionHandler.php');
			self::$handler = new APCSessionHandler($params);
		} elseif ($type === 'apcu') {
			require_once($handlersDir.'APCUSessionHandler.php');
			self::$handler = new APCUSessionHandler($params);
		} elseif ($type === 'memcached') {
			require_once($handlersDir.'MemcachedSessionHandler.php');
			self::$handler = new MemcachedSessionHandler($params);
		} elseif ($type === 'redis') {
			require_once($handlersDir.'RedisSessionHandler.php');
			self::$handler = new RedisSessionHandler($params);
		} elseif ($type === 'db') {
			require_once($handlersDir.'DBSessionHandler.php');
			self::$handler = new DBSessionHandler($params);
		} else {
			throw new Exception('Unknown session handler: '.$type);
		}
		
		self::$type = $type;
		session_set_save_handler(self::$handler, true);
    }
    
    /**
     * Get the type of the registerd handler
     */
    public static function getRegisteredType() {
		return self::$type;
    }
    
    /*public static function getRegisteredHandler() {
		return self::$handler;
    }*/
    
}