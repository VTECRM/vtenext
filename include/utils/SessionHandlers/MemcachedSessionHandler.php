<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181231 */

/**
 * Save session data in APCu cache
 */
class MemcachedSessionHandler implements SessionHandlerInterface {

	protected $lifetime;
	protected $pid = 'sess';
	
	private $mc;
	
	public function __construct($params) {
		if (!class_exists('Memcached')) {
			throw new Exception('Memcached extension not installed');
		}
		if (isset($params['lifetime'])) {
			$this->lifetime = $params['lifetime'];
		} else {
			$this->lifetime = ini_get('session.gc_maxlifetime');
		}
		
		$this->mc = new Memcached($this->pid); 
		$this->mc->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		$this->mc->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY); // faster serializer
		// add servers only the first time
		if (!count($this->mc->getServerList())) {
			$this->mc->addServers($params['servers']);
		}
	}
	
	public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
		$v = $this->mc->get($id);
		if ($v !== false) {
			return $v;
		}
		return '';
    }

    public function write($id, $data) {
        return $this->mc->set($id, $data, intval($this->lifetime));
    }

    public function destroy($id) {
        return $this->mc->delete($id);
    }

    public function gc($maxlifetime) {
		// do nothing, use internal expiration time
        return true;
    }
    
}