<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Handler for caching stuff within a touch request
 * Very similar to the class Cache
 */

/* crmv@56798 crmv@93148 */

class TouchCache extends SDKExtendableUniqueClass {

	protected $enabled = true;				// false to deactivate the cache
	protected $cacheType = 'session';		// only session is supported at the moment
											// the Touch session is not shared with the web session

	public function getType() {
		return $this->cacheType;
	}
	
	public function isEnabled() {
		return $this->enabled;
	}
	
	public function disable($clear = false) {
		if (!$this->enabled) return true;
		
		if ($clear) $this->clear();
		$this->enabled = false;
		return true;
	}
	
	public function enable() {
		$this->enabled = true;
		return true;
	}
	
	/* return FALSE if not available in cache, otherwise the stored value.
	 * Therefore, you cannot store the value FALSE in the cache
	 */
	public function get($key) {
		if (!$this->enabled) return false;
	
		$key = $this->transformKey($key);
		
		if ($this->cacheType == 'session') {
			if (VteSession::hasKeyArray(array('cache', $key))) {
				$expiration = VteSession::getArray(array('cache_expiration', $key));
				if (!empty($expiration) && time() > $expiration) {
					$cs = $this->checkSession();
					VteSession::removeArray(array('cache', $key));
					VteSession::removeArray(array('cache_expiration', $key));
					if ($cs) $this->closeSession();
					return false;
				}
				return VteSession::getArray(array('cache', $key));
			}
		} else {
			throw new Exception("The cache type is not supported");
		}
		return false;
	}
	
	public function set($key, $value, $life = null) {
		if (!$this->enabled) return false;
		
		$key = $this->transformKey($key);
		
		if ($this->cacheType == 'session') {
			$cs = $this->checkSession();
			VteSession::setArray(array('cache', $key), $value);
 			if (!empty($life) && is_int($life)) {
 				$expiration = time() + $life;
	 			VteSession::setArray(array('cache_expiration', $key), $expiration);
 			}
 			if ($cs) $this->closeSession();
		} else {
			throw new Exception("The cache type is not supported");
		}
		return true;
	}
	
	public function delete($key) {
		if (!$this->enabled) return false;
		
		$key = $this->transformKey($key);
		
		if ($this->cacheType == 'session') {
			$cs = $this->checkSession();
			VteSession::removeArray(array('cache', $key));
	 		VteSession::removeArray(array('cache_expiration', $key));
	 		if ($cs) $this->closeSession();
		} else {
			throw new Exception("The cache type is not supported");
		}
		return true;
	}
	
	// delete keys matching the regexp
	public function deleteMatching($regexp) {
		if (!$this->enabled) return false;
		
		if ($this->cacheType == 'session') {
			$cs = $this->checkSession();
			if (is_array(VteSession::get('cache'))) {
				foreach (VteSession::get('cache') as $key => $val) {
					if (preg_match($regexp, $key)) {
						VteSession::removeArray(array('cache', $key));
						VteSession::removeArray(array('cache_expiration', $key));
					}
				}
			}
			if ($cs) $this->closeSession();
		} else {
			throw new Exception("The cache type is not supported");
		}
		return true;
	}
	
	public function clear() {
		if ($this->cacheType == 'session') {
			$cs = $this->checkSession();
			VteSession::remove('cache');
			VteSession::remove('cache_expiration');
			if ($cs) $this->closeSession();
		} else {
			throw new Exception("The cache type is not supported");
		}
		return true;
	}
	
	protected function checkSession() {
		global $touchInst;
		return $touchInst->reopenWSSession();
	}
	
	protected function closeSession() {
		global $touchInst;
		return $touchInst->closeWSSession();
	}
	
	// in case the key should be changed (according to the user, or something...)
	protected function transformKey($key) {
		return $key;
	}

}