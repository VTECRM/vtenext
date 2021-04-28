<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181231 */

/**
 * Save session data in APC cache
 */
class APCSessionHandler implements SessionHandlerInterface {

	protected $lifetime;
	
	public function __construct($params = array()) {
		if (!function_exists('apc_store')) {
			throw new Exception('APC extension not installed');
		}
		if (isset($params['lifetime'])) {
			$this->lifetime = $params['lifetime'];
		} else {
			$this->lifetime = ini_get('session.gc_maxlifetime');
		}
	}
	
	public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
        return true;
    }

    public function read($id) {
		return apc_fetch('sess_'.$id);
    }

    public function write($id, $data) {
        return apc_store('sess_'.$id, $data, $this->lifetime);
    }

    public function destroy($id) {
        return apc_delete('sess_'.$id);
    }

    public function gc($maxlifetime) {
		// do nothing, use internal expiration time
        return true;
    }
    
}