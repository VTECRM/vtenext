<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181231 */

/**
 * Save session data in Redis
 */
class RedisSessionHandler implements SessionHandlerInterface {

	protected $lifetime;
	
	private $rd;
	
	public function __construct($params) {
		if (!class_exists('Redis')) {
			throw new Exception('Redis extension not installed');
		}
		if (isset($params['lifetime'])) {
			$this->lifetime = $params['lifetime'];
		} else {
			$this->lifetime = ini_get('session.gc_maxlifetime');
		}
		
		$this->rd = new Redis();
		$this->rd->pconnect($params['servers'][0][0], $params['servers'][0][1]); // same structure of memcached, but use the first server only
		$this->rd->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
		$this->rd->setOption(Redis::OPT_PREFIX, 'sess:');
	}
	
	public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
		$v = $this->rd->get($id);
		if ($v !== false) {
			return $v;
		}
		return '';
    }

    public function write($id, $data) {
        return $this->rd->set($id, $data, intval($this->lifetime));
    }

    public function destroy($id) {
        return $this->rd->delete($id);
    }

    public function gc($maxlifetime) {
		// do nothing, use internal expiration time
        return true;
    }
    
}