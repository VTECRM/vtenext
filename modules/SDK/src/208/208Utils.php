<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@37679
 * Implements an encrypted uitype
 *
 * TODO:
 * . in sessione mettere il valore dei campi, non la password
 *
 */

require_once('modules/Morphsuit/utils/RSA/Crypt/Rijndael.php');
require_once('modules/Morphsuit/utils/RSA/Crypt/AES.php');

class EncryptedUitype {

	public $uitype = 208;
	public $convertible_uitypes = array(1,19,20,21);
	public $config_table;

	// default config values
	public $defaults = array(
		'pwd_timeout' 	=> 3600,	// 1 hour
		'valid_ip'		=> '',		// any ip
		'valid_roles'	=> '',		// any role
	);

	public $timeout_min = 300;		// 5 min
	public $timeout_max = 86400;	// 24 hours

	// list of ciphers to use (the first available)
	protected $crypt_algos = array(
		// crmv@167234 - mcrypt extension has been removed from PHP 7.2
		// mcrypt algorithms - not used yet
		//MCRYPT_RIJNDAEL_256, // aes 256
		//MCRYPT_TWOFISH256,
		//MCRYPT_3DES,
		//crmv@167234e
		// internal php implementation
		'INTERNAL_AES',
	);
	protected $cipher;

	// defaults settings


	function __construct() {
		global $table_prefix;
		$this->config_table = $table_prefix.'_uitype208';
		$this->fieldCache = array();
		// check ciphers
		if (function_exists('mcrypt_list_algorithms') && function_exists('mcrypt_encrypt')) {
			// TODO... mcrypt library
			$this->cipher = 'INTERNAL_AES';
		} else {
			// fallback to PHP implementation
			$this->cipher = 'INTERNAL_AES';
		}
		// init tables
		$this->initTables();
	}

	// create config table
	/*
	 * fieldid:		id of the field
	 * pwd_hash:	sha1 hash of the password, to check validity
	 * valid_ip:	list of ip/mask (mask is optional), separated by ;
	 * 				empty means no ip filtering
	 * valid_roles:	list of roles enabled to see the field, separated by ;
	 * 				empty means all roles are allowed
	 */
	protected function initTables() {
		if(!Vtecrm_Utils::CheckTable($this->config_table)) {
			Vtecrm_Utils::CreateTable(
				$this->config_table,
				"fieldid I(19) PRIMARY,
				pwd_hash C(63),
				pwd_timeout I(11),
				old_uitype I(11),
				valid_ip C(63),
				valid_roles C(127)",
				true);
		}
	}

	public function registerUitype() {
		$basedir = 'modules/SDK/src/208/';
		SDK::setUitype(
			$this->uitype,
			$basedir."{$this->uitype}.php",
			$basedir."{$this->uitype}.tpl",
			$basedir."{$this->uitype}.js",
			'text' // TODO: nuovo wstype: ciphered
		);
		$moduleInstance = Vtecrm_Module::getInstance('SDK');
		Vtecrm_Link::addLink($moduleInstance->id,'HEADERSCRIPT','SDKUitype',$basedir."{$this->uitype}Utils.js");
	}

	// careful, it erases all data in the table
	public function rebuildTables() {
		global $adb;
		if (Vtecrm_Utils::CheckTable($this->config_table)) {
			$adb->query("drop table {$this->config_table}");
		}
		$this->initTables();
	}

	public function clearCache() {
		$this->fieldCache = array();
	}

	public function getFieldIdFromName($module, $fieldname) {
		global $adb, $table_prefix;
		$res = $adb->pquery("select fieldid,tablename,columnname,uitype, {$table_prefix}_tab.name as module from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid where {$table_prefix}_tab.name = ? and {$table_prefix}_field.fieldname = ?", array($module, $fieldname));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			$this->fieldCache[$row['fieldid']] = $row;
			return $row['fieldid'];
		}
		return 0;
	}

	public function getFieldInfo($fieldid) {
		global $adb, $table_prefix;
		if (!array_key_exists($fieldid, $this->fieldCache)) {
			$res = $adb->pquery("select fieldid,tablename,columnname,uitype,fieldlabel,{$table_prefix}_tab.name as module from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid where {$table_prefix}_field.fieldid = ?", array($fieldid));
			if ($res && $adb->num_rows($res) > 0) {
				$row = $adb->FetchByAssoc($res, -1, false);
				$this->fieldCache[$fieldid] = $row;
			}
		}
		return $this->fieldCache[$fieldid];
	}

	protected function hashPassword($pwd) {
		return sha1($pwd);
	}

	protected function validatePassword($pwd, $hash) {
		return ($this->hashPassword($pwd) == $hash);
	}

	// a 16byte block is prepended (checksum + semirandom stuff)
	public function encryptData($data, $password) {
		// add stuff to data -> same data encoded differently
		$predata = substr(uniqid(), -8);
		$data = $predata.$data;
		$csum = substr(sprintf('%08x', crc32($data)), 0, 8);
		$data = $csum.$data;
		switch ($this->cipher) {
			case 'INTERNAL_AES':
				$aes = new Crypt_AES();
				$aes->setKey(md5($password));
				$ciphered = $aes->encrypt($data);
				break;
		}
		return base64_encode($ciphered);
	}

	// returns false in case of wrong password or corrupted data
	public function decryptData($data, $password) {
		$data = base64_decode($data);
		switch ($this->cipher) {
			case 'INTERNAL_AES':
				$aes = new Crypt_AES();
				$aes->setKey(md5($password));
				$decoded = $aes->decrypt($data);
				break;
		}
		if ($decoded === false || strlen($decoded) < 16) return false;
		$csum = substr($decoded, 0, 8);
		$decoded = substr($decoded, 8);
		$csumCheck = substr(sprintf('%08x', crc32($decoded)), 0, 8);
		if ($csum !== $csumCheck) return false;
		if (strlen($decoded) == 8) return ''; else return substr($decoded, 8);
	}

	// do an encryption test
	public function selfCheck($password) {
		$data = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 20);
		$enc = $this->encryptData($data, $password);
		$dec = $this->decryptData($enc, $password);
		return ($data === $dec);
	}


	public function getConfig($fieldid) {
		global $adb;
		$res = $adb->pquery("select * from {$this->config_table} where fieldid = ?", array($fieldid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			$row['valid_roles'] = array_filter(explode(';', $row['valid_roles']));
			$row['valid_ip'] = array_filter(explode(';', $row['valid_ip']));
		}
		return $row;
	}

	public function saveConfig($fieldid, $config) {
		global $adb, $table_prefix;

		$sql = "update {$this->config_table} set ";
		$params = array();
		$upd = array();
		if (!empty($config['pwd_timeout'])) {
			$upd[] = 'pwd_timeout = ?';
			$params[] = max($this->timeout_min, min(intval($config['pwd_timeout']), $this->timeout_max));
		}

		// IP config
			if (!is_array($config['valid_ip'])) $config['valid_ip'] = array_filter(explode(';', $config['valid_ip']));

			// validate ips (just basic syntactic validation)
			foreach ($config['valid_ip'] as $k=>$ip) {
				if (!preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(/\d{1,2})?$#', $ip)) unset($config['valid_ip'][$k]);
			}
			$config['valid_ip'] = implode(';', $config['valid_ip']);

			$upd[] = 'valid_ip = ?';
			$params[] = $config['valid_ip'];

		// ROLES config

			if (!is_array($config['valid_roles'])) $config['valid_roles'] = array_filter(explode(';', $config['valid_roles']));
			foreach ($config['valid_roles'] as &$role) $role = substr($role, 0, 2);
			$config['valid_roles'] = implode(';', $config['valid_roles']);

			$upd[] = 'valid_roles = ?';
			$params[] = $config['valid_roles'];

		if (count($params) > 0) {
			$sql .= implode(', ', $upd).' where fieldid = ?';
			$params[] = $fieldid;
			$adb->pquery($sql, $params);
		}
		return true;
	}

	public function checkFieldPassword($fieldid, $password) {
		$fconfig = $this->getConfig($fieldid);
		$hash = $fconfig['pwd_hash'];
		if (empty($hash)) return false;
		return $this->validatePassword($password, $hash);
	}

	// convert a standard field to this encrypted uitype
	// returns true on success
	public function convertField($fieldid, $password, $config = array()) {
		global $adb, $table_prefix;
		$finfo = $this->getFieldInfo($fieldid);
		if (empty($finfo['columnname']) || $finfo['uitype'] == $this->uitype) return false;

		// do the database encryption
		$r = $this->encryptColumn($finfo, $password);
		if (!$r) return false;

		// update uitype and config
		$adb->pquery("update {$table_prefix}_field set uitype = ? where fieldid = ?", array($this->uitype, $fieldid));
		$adb->pquery("insert into {$this->config_table} (fieldid, pwd_hash, old_uitype) values (?,?,?)", array($fieldid, $this->hashPassword($password), $finfo['uitype']));
		$this->saveConfig($fieldid, $this->defaults);
		
		FieldUtils::invalidateCache($finfo['module']); // crmv@193294
		return true;
	}

	public function restoreField($fieldid, $password) {
		global $adb, $table_prefix;
		$finfo = $this->getFieldInfo($fieldid);
		if (empty($finfo['columnname']) || $finfo['uitype'] != $this->uitype) return false;
		$fconfig = $this->getConfig($fieldid);
		if (!$this->validatePassword($password, $fconfig['pwd_hash'])) {
			return false;
		}

		// restore encrypted data
		$r = $this->decryptColumn($finfo, $password);
		if (!$r) return false;

		$adb->pquery("update {$table_prefix}_field set uitype = ? where fieldid = ?", array($fconfig['old_uitype'], $fieldid));
		$adb->pquery("delete from {$this->config_table} where fieldid = ?", array($fieldid));
		
		FieldUtils::invalidateCache($finfo['module']); // crmv@193294
		return true;
	}

	public function getColumnLength($table, $column) {
		global $adb;
		$dbname = $adb->database->database;

		if ($adb->isMysql()) {
			$query =
				"SELECT character_maximum_length as maxlen
				FROM information_schema.columns
				WHERE table_schema = ? AND table_name = ? AND column_name = ?";
			$params = array($dbname, $table, $column);
		} elseif ($adb->isOracle()) {
			$query =
				"SELECT data_type, data_length as maxlen
				FROM user_tab_columns
				where table_name = ?  and column_name = ?";
			$params = array(strtoupper($table), $column);
		} elseif ($adb->isMssql()) {
			$query =
			"SELECT character_maximum_length as maxlen
			FROM information_schema.columns
			WHERE table_schema = ? AND table_name = ? AND column_name = ?";
			$params = array('dbo', $table, $column);
		}

		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			$len = $adb->query_result_no_html($res, 0, 'maxlen');
			return $len;
		}
		return false;
	}

	// given the input data length, returns the safe column size to hold encrypted data
	public function getSafeLength($datalen) {
		return 16+2*($datalen+16);
	}

	protected function getColumnDataLength($table, $column) {
		global $adb;
		if ($adb->isMysql() || $adb->isOracle()) {
			$query = "select MAX(LENGTH($column)) as clen FROM $table";
		} elseif ($adb->isMssql()) {
			$query = "select MAX(DATALENGTH($column)) as clen FROM $table";
		}
		$res = $adb->query($query);

		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'clen');
		} else {
			return false;
		}

	}

	// check if column is large enough to hold encrypted data (when convertitng entire column)
	// if $extend, extend the column as needed
	public function checkColumnLength($table, $column, $extend = false) {
		global $adb;

		$colLength = $this->getColumnLength($table, $column);
		if ($colLength === false) return false;

		// get max data size
		$clen = $this->getColumnDataLength($table, $column);
		if ($clen === false) return false;

		// this is a safe size to hold encrypted data;
		$safeLength = $this->getSafeLength($clen);

		if ($safeLength > $colLength) {
			// extend the column?
			if ($extend) {
				require_once('modules/Update/Update.php');
				// slow function, but safer
				Update::change_field($table, $column, 'C', $safeLength);
				return true;
			}
			return false;
		}

		return true;
	}

	// encrypt the whole column for a field (ignore deleted records)
	protected function encryptColumn(&$finfo, $password, $decrypt = false, $newpwd = '') {
		global $adb, $table_prefix;

		$modInstance = CRMEntity::getInstance($finfo['module']);
		$tableidx = $modInstance->tab_name_index[$finfo['tablename']];

		if (empty($tableidx)) return false;

		// check col length (and extend it if needed)
		if (!$decrypt) {
			$r = $this->checkColumnLength($finfo['tablename'], $finfo['columnname'], true);
			if (!$r) return false;
		}

		$tabfield = $finfo['tablename'].'.'.$finfo['columnname'];
		$selquery = "select $tabfield as fieldvalue, {$table_prefix}_crmentity.crmid from {$finfo['tablename']}";
		if ($finfo['tablename'] != $table_prefix.'_crmentity') {
			$selquery .= " inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$finfo['tablename']}.$tableidx";
		}
		$selquery .= " where {$table_prefix}_crmentity.setype = ? and $tabfield is not null and $tabfield != ''";
		$params = array($finfo['module']);

		$res = $adb->pquery($selquery, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				if ($decrypt) {
					$encvalue = $this->decryptData($row['fieldvalue'], $password);
					if (!empty($newpwd)) {
						// re-encrypt data
						$encvalue = $this->encryptData($encvalue, $newpwd);
					}
				} else {
					$encvalue = $this->encryptData($row['fieldvalue'], $password);
				}
				if ($encvalue === false) return false;
				$updatequery = "update {$finfo['tablename']} set {$finfo['columnname']} = ? where $tableidx = ?";
				$params2 = array($encvalue, $row['crmid']);
				$adb->pquery($updatequery, $params2);
			}
		}

		return true;
	}

	protected function decryptColumn(&$finfo, $password) {
		return $this->encryptColumn($finfo, $password, true);
	}

	public function changePassword($fieldid, $oldpwd, $newpwd) {
		global $adb, $table_prefix;
		$finfo = $this->getFieldInfo($fieldid);
		if (empty($finfo['columnname']) || $finfo['uitype'] != $this->uitype || empty($newpwd)) return false;

		$fconfig = $this->getConfig($fieldid);
		if (!$this->validatePassword($oldpwd, $fconfig['pwd_hash'])) {
			return false;
		}

		$r = $this->encryptColumn($finfo, $oldpwd, true, $newpwd);
		if ($r) {
			// update pwd hash
			$adb->pquery("update {$this->config_table} set pwd_hash = ? where fieldid = ?", array($this->hashPassword($newpwd), $fieldid));
			// clear cached pwd
			$this->clearCachedPassword($fieldid);
		}
		return $r;
	}

	public function getRawValue($fieldid, $crmid) {
		global $adb, $table_prefix;

		$finfo = $this->getFieldInfo($fieldid);
		if (empty($finfo['columnname']) || $finfo['uitype'] != $this->uitype) return false;

		$modInstance = CRMEntity::getInstance($finfo['module']);
		$tableidx = $modInstance->tab_name_index[$finfo['tablename']];
		if (empty($tableidx)) return false;

		$selquery = "select {$finfo['tablename']}.{$finfo['columnname']} as fieldvalue, {$table_prefix}_crmentity.crmid from {$finfo['tablename']}";
		if ($finfo['tablename'] != $table_prefix.'_crmentity') {
			$selquery .= " inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$finfo['tablename']}.$tableidx";
		}
		$selquery .= " where {$table_prefix}_crmentity.crmid = ?";
		$params = array($crmid);
		$res = $adb->pquery($selquery, $params);
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'fieldvalue');
		}
		return false;
	}

	// returns decoded value
	public function getValue($fieldid, $password, $crmid, $rawvalue = '') {
		global $adb;

		$fconfig = $this->getConfig($fieldid);
		if (!$this->validatePassword($password, $fconfig['pwd_hash'])) {
			return false;
		}

		if (empty($rawvalue)) {
			$rawvalue = $this->getRawValue($fieldid, $crmid);
		}
		if ($rawvalue == '') return '';

		$ret = $this->decryptData($rawvalue, $password);

		if ($ret !== false) {
			// metti in sessione
		}
		return $ret;
		// TODO: gestire con sessione
	}

	/*public function saveValue($fieldid, $password, $value) {
		// empty
	}*/

	// retrieve cached password from session
	public function getCachedPassword($fieldid) {
		$fconfig = $this->getConfig($fieldid);

		//check expiration
		$expired = false;
		if ($fconfig['pwd_timeout'] > 0 && VteSession::getArray(array('uitype208', $fieldid, 'last_insert_time')) > 0) {
			$now = time();
			$delta = $now - VteSession::getArray(array('uitype208', $fieldid, 'last_insert_time'));
			if ($delta > $fconfig['pwd_timeout']) $expired = true;
		}

		// retrieve
		$pwd = VteSession::getArray(array('uitype208', $fieldid, 'password'));

		// validate
		if ($expired || empty($pwd) || !$this->validatePassword($pwd, $fconfig['pwd_hash'])) {
			$pwd = false;
			// crmv@128133
			VteSession::setArray(array('uitype208', $fieldid), array(
				'expired' =>  true,
				'password' => false,
				'old_uitype' => $fconfig['old_uitype'],
				'last_insert_time' => '',
			));
			// crmv@128133e
		} else {
			VteSession::setArray(array('uitype208', $fieldid, 'expired'), false);
			VteSession::setArray(array('uitype208', $fieldid, 'old_uitype'), $fconfig['old_uitype']);
		}

		return $pwd;
	}

	public function setCachedPassword($fieldid, $password, $permitted = true) {
		//$fconfig = $this->getConfig($fieldid);

		// set password
		VteSession::setArray(array('uitype208', $fieldid, 'password'), $password);

		// set insert time
		VteSession::setArray(array('uitype208', $fieldid, 'last_insert_time'), time());

		// permitted
		VteSession::setArray(array('uitype208', $fieldid, 'permitted'), $permitted);
	}

	public function clearCachedPassword($fieldid = null) {
		if ($fieldid > 0) {
			VteSession::removeArray(array('uitype208', $fieldid));
		} else {
			VteSession::remove('uitype208');
		}
	}

	public function getAllFields() {
		global $adb, $table_prefix;

		$ret = array();
		$res = $adb->query(
			"select {$table_prefix}_field.*, {$table_prefix}_tab.name as module
			from {$table_prefix}_field
			inner join {$table_prefix}_tab on {$table_prefix}_field.tabid = {$table_prefix}_tab.tabid
			inner join {$this->config_table} on {$this->config_table}.fieldid = {$table_prefix}_field.fieldid
			order by {$table_prefix}_tab.name ASC"
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$row['fieldlabel_trans'] = getTranslatedString($row['fieldlabel'], $row['module']);
				$ret[] = $row;
			}
		}
		return $ret;
	}

	public function getAllRoles() {
		global $adb, $table_prefix;

		$ret = array();
		$result = $adb->query("select * from {$table_prefix}_role order by parentrole asc");
		if ($result) {
			while ($row = $adb->FetchByAssoc($result)) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

	// get all fields that can be encrypted, for the specified modules
	// TODO: permissions
	public function getConvertibleFields($modules = array()) {
		global $adb, $table_prefix;
		if (!is_array($modules)) $modules = array($modules);

		$skipModules = array('Projects', 'ModComments', 'Charts', 'Fax', 'Sms', 'Newsletter', 'Emails', 'Calendar'); // crmv@164120 crmv@164122

		$params = array();
		$query =
			"select f.fieldid, f.fieldname, f.fieldlabel, t.name as module
			from {$table_prefix}_field f
			inner join {$table_prefix}_tab t on t.tabid = f.tabid
			where t.presence = 0 and t.isentitytype = 1 and f.readonly != 100 and f.fieldname not like 'hdn%' and f.typeofdata like 'V~%'
				and f.tablename != '{$table_prefix}_crmentity'
				and f.uitype in (".generateQuestionMarks($this->convertible_uitypes).")
				and t.name not in (".generateQuestionMarks($skipModules).")
		";
		$params[] = $this->convertible_uitypes;
		$params[] = $skipModules;

		if (count($modules) > 0) {
			$query .= ' and t.name in ('.generateQuestionMarks($modules).')';
			$params[] = $modules;
		}

		$ret = array();
		$res = $adb->pquery($query, $params);
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$mod = $row['module'];
				unset($row['module']);
				if (!is_array($ret[$mod])) $ret[$mod] = array();
				$row['fieldlabel_trans'] = getTranslatedString($row['fieldlabel'], $mod);
				$ret[$mod][] = $row;
			}
		}
		return $ret;
	}

	/*
	 * checks if the field can be seen
	 * 1. check IP
	 * 2. check role (if not admin)
	 */
	public function isPermitted($fieldid) {
		global $current_user;

		$fconfig = $this->getConfig($fieldid);

		// check IP
		$sourceip = $_SERVER['REMOTE_ADDR'];
		if (!empty($fconfig['valid_ip'])) {
			if (!$this->isIPAllowed($sourceip, $fconfig['valid_ip'])) return false;
		}

		// then roles
		if (!is_admin($current_user) && !empty($fconfig['valid_roles'])) {
			if (!$this->isRoleAllowed($current_user, $fconfig['valid_roles'])) return false;
		}

		return true;
	}

	// validate an ip against a standard ip/mask (or an array of masks)
	public function isIPAllowed($ip, $ipmask = '') {
		if (empty($ipmask)) return true;

		if (is_array($ipmask)) {
			foreach ($ipmask as $msk) {
				$ret = $this->isIPAllowed($ip, $msk);
				if ($ret) return $ret;
			}
			return false;
		}

		list($net, $mask) = explode("/", $ipmask);
		$ip_mask = ~((1 << (32 - $mask)) - 1);
		$ip_net = ip2long($net) & $ip_mask;
		$ip_ip_net = ip2long($ip) & $ip_mask;
		return ($ip_ip_net == $ip_net);
	}

	// validate user against a list of roles
	// field is allowed for all users of the specified roles and their parents
	public function isRoleAllowed(&$user, $rolelist = '') {
		// check valid roles
		@include('user_privileges/user_privileges_'.$user->id.'.php');
		if ($is_admin) return true;
		if (empty($current_user_roles)) return false;
		if (empty($rolelist)) return true;

		foreach ($rolelist as $role) {
			if ($current_user_roles == $role) return true;
			// cheeck parents
			$parents = getParentRole($role);
			if (is_array($parents)) if (in_array($current_user_roles, $parents)) return true;
		}

		return false;
	}

}

?>