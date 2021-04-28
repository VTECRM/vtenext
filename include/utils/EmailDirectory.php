<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@41883 crmv@107655 */

/**
 * This class handles an index of emails and records, when a fast search by email is required
 * The index is divided by user, so each user has its own index, but it's also possible to search globally.
 * The index keeps for each user a timestamp when it was last updated
 */
class EmailDirectory {
	
	protected $table;
	protected $synctable;
	
	protected $userid;
	protected $modules = array('Users','Contacts','Accounts','Leads','Vendors');	// moduli ordinati per priorita'
	protected $uiypes = array(13,104);
	
	public function __construct($userid='') {
		global $table_prefix;
		$this->table = $table_prefix.'_email_directory';
		$this->synctable = $table_prefix.'_email_directory_sync';
		if (empty($userid)) {
			global $current_user;
			$this->userid = $current_user->id;
		}
	}
	
	/**
	 * Return the list of email uitypes
	 * @return array
	 */
	public function getUItypes() {
		return $this->uiypes;
	}
	
	/**
	 * Return the current userid used for store and retrieve operations
	 * @return int/null
	 */
	public function getUserid() {
		return $this->userid;
	}
	
	/**
	 * Return the list of modules for which the index is active
	 * @return array
	 */
	public function getModules() {
		return $this->modules;
	}
	
	//crmv@143630
	/**
	 * Return true if $module is enabled for the index
	 * @param string $module The module to check
	 * @return bool
	 */
	public function isModuleEnabled($module) {
		return in_array($module,$this->modules);
	}
	//crmv@143630e
	
	/**
	 * Get the record matching the provided $email
	 * @param string $email The email to search for
	 * @return array/false If found, an array with the crmid and the module
	 */
	public function getRecord($email) {
		global $adb;
		$result = $adb->pquery("select crmid, module from {$this->table} where userid = ? and email = ?",array($this->userid,$email));
		if ($result && $adb->num_rows($result) > 0) {
			return array('crmid'=>$adb->query_result_no_html($result,0,'crmid'),'module'=>$adb->query_result_no_html($result,0,'module')); // crmv@80298
		} else {
			return false;
		}
	}
	
	/**
	 * Get the record matching the provided $email
	 * @param string $email The email to search for
	 * @return int/false If found, only the crmid
	 */
	public function getId($email) {
		$record = $this->getRecord($email);
		if (!empty($record)) {
			return $record['crmid'];
		} else {
			return false;
		}
	}
	
	/**
	 * Get the email addresses for the specified record (every record can have multiple addresses)
	 * @param int $crmid The record
	 * @return array/false If found, an array with the email addresses
	 */
	public function getEmail($crmid) {
		global $adb;
		$email = array();
		$result = $adb->pquery("select email from {$this->table} where userid = ? and crmid = ?",array($this->userid,$crmid));
		if ($result && $adb->num_rows($result) > 0) {
			while ($row=$adb->fetchByAssoc($result, -1, false)) { // crmv@80298
				$email[] = $row['email'];
			}
			return $email;
		} else {
			return false;
		}
	}
	
	// crmv@187823
	/**
	 * Get all unique emails stored for the specified record, regardless of the owning user
	 * @param int $crmid The recordid
	 * @return array
	 */
	public function getAllEmails($crmid) {
		global $adb;
		$email = array();
		$result = $adb->pquery("select email from {$this->table} where crmid = ?", array($crmid));
		if ($result && $adb->num_rows($result) > 0) {
			while ($row=$adb->fetchByAssoc($result, -1, false)) { // crmv@80298
				$email[] = $row['email'];
			}
			$email = array_unique($email);
			return $email;
		} else {
			return false;
		}
	}
	
	/**
	 * Get the first email for the specified record, regardless of the owning user
	 * @param int $crmid The recordid
	 * @return string/false
	 */
	public function getAnyEmail($crmid) {
		global $adb;
		$email = false;
		$result = $adb->limitpQuery("select email from {$this->table} where crmid = ?", 0, 1, array($crmid));
		if ($result && $adb->num_rows($result) > 0) {
			$email = $adb->query_result_no_html($result, 0, 'email');
		}
		return $email;
	}	
	// crmv@187823e
	
	/**
	 * Get all stored emails for the index user
	 * @param bool $getEntityNames If true, return also the entity name of the record
	 * @return array
	 */
	public function getAll($getEntityNames = true) {
		global $adb;
		$list = array();
		$result = $adb->pquery("SELECT email, crmid, module FROM {$this->table} WHERE userid = ?",array($this->userid));
		if ($result && $adb->num_rows($result) > 0) {
			while ($row=$adb->fetchByAssoc($result, -1, false)) {
				if ($getEntityNames && $row['crmid'] && $row['module']) {
					$row['entityname'] = getEntityName($row['module'], array($row['crmid']), true);
					// remove html chars
					if ($row['entityname']) {
						$row['entityname'] = html_entity_decode($row['entityname'], ENT_QUOTES, 'UTF-8');
					}
				}
				$list[] = $row;
			}
		}
		return $list;
	}
	
	//crmv@151522
	public function save($email,$crmid=null,$module=null) {
		global $adb;
		$record = $this->getRecord($email);
		if (empty($record)) {
			$success = $this->create($email,$crmid,$module);
		} elseif (empty($record['crmid'])) {
			$success = $this->update($email,$crmid,$module);
		} else {
			$success = $this->checkPriority($email,$crmid,$module,$record);
		}
		if ($success && !empty($crmid)) {
			$result = $adb->pquery("select userid from {$this->table} where email = ? and crmid is null", array($email));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$this->update($email,$crmid,$module,$row['userid']);
				}
			}
		}
	}
	
	public function create($email,$crmid=null,$module=null) {
		global $adb;
		//crmv@86188 - sometimes the entry is already there, this creates problems with a slave
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into {$this->table} (userid,email,crmid,module) values (?,?,?,?)",array($this->userid,$email,$crmid,$module));
		} else {
			$adb->pquery("insert into {$this->table} (userid,email,crmid,module) values (?,?,?,?)",array($this->userid,$email,$crmid,$module));
		}
		//crmv@86188e
		$this->updateSyncTime();
		return true;
	}
	
	public function update($email,$crmid,$module,$userid=null) {
		global $adb;
		if (empty($userid)) $userid = $this->userid;
		$adb->pquery("update {$this->table} set crmid = ?, module = ? where userid = ? and email = ?",array($crmid,$module,$userid,$email));
		$this->updateSyncTime($userid);
		return true;
	}
	
	protected function checkPriority($email,$crmid,$module,$current) {
		$current_priority = array_search($current['module'],$this->modules);
		$new_priority = array_search($module,$this->modules);
		if (empty($current_priority)) $current_priority = 999;	//crmv@143630 if is a module not in enabled modules
		
		if ($new_priority == $current_priority) {
			// scelgo il record piu' vecchio
		} elseif ($new_priority < $current_priority) {
			$this->update($email,$crmid,$module);
			return true;
		}
		return false;
	}
	//crmv@151522e
	
	/**
	 * Delete an entry by crmid for all users
	 * @param int $crmid The crmid to remove
	 */
	public function deleteById($crmid) {
		global $adb;
		// first get list of users that are going to change
		$users = array();
		$res = $adb->pquery("SELECT DISTINCT userid FROM {$this->table} WHERE crmid = ?", array($crmid));
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$users[] = $row['userid'];
		}
		// then delete the rows
		$adb->pquery("delete from {$this->table} where crmid = ?",array($crmid));
		// and update the times
		foreach ($users as $userid) {
			$this->updateSyncTime($userid);
		}
	}
	
	/**
	 * Delete an entry by email for all users
	 * @param string $email The email to remove
	 */
	public function deleteByEmail($email) {
		global $adb;
		// first get list of users that are going to change
		$users = array();
		$res = $adb->pquery("SELECT DISTINCT userid FROM {$this->table} WHERE email = ?", array($email));
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$users[] = $row['userid'];
		}
		// then delete the rows
		$adb->pquery("delete from {$this->table} where email = ?",array($email));
		// and update the times
		foreach ($users as $userid) {
			$this->updateSyncTime($userid);
		}
	}
	
	/**
	 * Get the last update time for the index user
	 * @return string/null
	 */
	public function getLastUpdate() {
		global $adb;
		$res = $adb->pquery("SELECT last_update FROM {$this->synctable} WHERE userid = ?", array($this->userid));
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'last_update');
		}
		return null;
	}
	
	protected function updateSyncTime($userid = null) {
		global $adb;
		if (empty($userid)) $userid = $this->userid;
		$now = date('Y-m-d H:i:s');
		
		$res = $adb->pquery("SELECT userid FROM {$this->synctable} WHERE userid = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			$adb->pquery("UPDATE {$this->synctable} SET last_update = ? WHERE userid = ?",array($now,$userid));
		} else {
			$adb->pquery("INSERT INTO {$this->synctable} (userid, last_update) VALUES (?,?)",array($userid,$now));
		}
	}
	
}