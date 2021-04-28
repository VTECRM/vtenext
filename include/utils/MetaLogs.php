<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@49398 */

/** 
 * This is a simple utility class to track changes in 
 * the structure of VTE (fields, permissions, modules... ) 
 */
class MetaLogs { // crmv@128133

	public $table = '';
	protected $enabled = true;

	const OPERATION_UNKNOWN = 'UNKNOWN';

	// users
	const OPERATION_ADDUSER = 'ADDUSER';
	const OPERATION_DELUSER = 'DELUSER';
	const OPERATION_EDITUSER = 'EDITUSER';
	const OPERATION_CHANGEUSERPWD = 'CHANGEUSERPWD'; // crmv@90935

	// groups
	const OPERATION_ADDGROUP = 'ADDGROUP';
	const OPERATION_DELGROUP = 'DELGROUP';
	const OPERATION_EDITGROUP = 'EDITGROUP';

	// roles
	const OPERATION_ADDROLE = 'ADDROLE';
	const OPERATION_DELROLE = 'DELROLE';
	const OPERATION_EDITROLE = 'EDITROLE';
	const OPERATION_RENAMEROLE = 'RENAMEROLE';	//crmv@150266

	// profiles
	const OPERATION_ADDPROFILE = 'ADDPROFILE';
	const OPERATION_DELPROFILE = 'DELPROFILE';
	const OPERATION_EDITPROFILE = 'EDITPROFILE';
	const OPERATION_RENAMEPROFILE = 'RENAMEPROFILE';	//crmv@150592

	// sharing rules
	const OPERATION_ADDSHRULE = 'ADDSHRULE';
	const OPERATION_DELSHRULE = 'DELSHRULE';
	const OPERATION_EDITSHRULE = 'EDITSHRULE';

	const OPERATION_REBUILDSHARES = 'REBUILDSHARES';

	// fields
	const OPERATION_ADDFIELD = 'ADDFIELD';
	const OPERATION_DELFIELD = 'DELFIELD';
	const OPERATION_EDITFIELD = 'EDITFIELD';

	// blocks
	const OPERATION_ADDBLOCK = 'ADDBLOCK';
	const OPERATION_DELBLOCK = 'DELBLOCK';
	const OPERATION_EDITBLOCK = 'EDITBLOCK';

	// filters
	const OPERATION_ADDFILTER = 'ADDFILTER';
	const OPERATION_DELFILTER = 'DELFILTER';
	const OPERATION_EDITFILTER = 'EDITFILTER';

	// modules
	const OPERATION_ADDMODULE = 'ADDMODULE';
	const OPERATION_EDITMODULE = 'EDITMODULE';
	const OPERATION_DELMODULE = 'DELMODULE';
	const OPERATION_EDITMODFIELDS = 'EDITMODFIELDS';
	const OPERATION_EDITMODFILTERS = 'EDITMODFILTERS';
	const OPERATION_EDITMODPANELS = 'EDITMODPANELS';	//crmv@146434
	
	//crmv@146434
	// panels
	const OPERATION_ADDPANEL = 'ADDPANEL';
	const OPERATION_DELPANEL = 'DELPANEL';
	const OPERATION_EDITPANEL = 'EDITPANEL';
	
	// relatedlists
	const OPERATION_ADDRELATEDLISTTOTAB = 'ADDRELATEDLISTTOTAB';
	const OPERATION_EDITRELATEDLISTTOTAB = 'EDITRELATEDLISTTOTAB';
	const OPERATION_DELRELATEDLISTTOTAB = 'DELRELATEDLISTTOTAB';
	const OPERATION_EDITRELATEDLIST = 'EDITRELATEDLIST';
	//crmv@146434e
	
	// conditionals
	//crmv@155145
	const OPERATION_ADDCONDITIONAL = 'ADDCONDITIONAL';
	const OPERATION_EDITCONDITIONAL = 'EDITCONDITIONAL';
	const OPERATION_DELCONDITIONAL = 'DELCONDITIONAL';
	const OPERATION_RENAMECONDITIONAL = 'RENAMECONDITIONAL';
	//crmv@155145e

	// sdk
	// TODO: log also sdk modifications or other operations ?

	// languages
	// TODO: log when label changes. How to log when a lot of lines change?

	// others
	const OPERATION_INSTALLED = 'VTEINSTALLED';
	const OPERATION_UPDATED = 'VTEUPDATED';

	public function __construct() {
		global $table_prefix;

		$this->table = $table_prefix.'_meta_logs';
	}
	
	public function enable() {
		$this->enabled = true;
	}
	
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Logs something
	 */
	public function log($operation, $objectid = null, $data = null) {
		global $adb;
		
		if (!$this->enabled) return null;

		$table = $this->table;
		if (empty($operation)) $operation = self::OPERATION_UNKNOWN;
		
		if(!Vtecrm_Utils::CheckTable($table)) {
			$schema_table =
			'<schema version="0.3">
				<table name="'.$table.'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="logid" type="I" size="19">
						<KEY/>
					</field>
					<field name="timestamp" type="T">
						<DEFAULT value="0000-00-00 00:00:00" />
					</field>
					<field name="operation" type="C" size="63">
						<NOTNULL/>
					</field>
					<field name="objectid" type="I" size="19" />
					<field name="data" type="C" size="255" />
					<index name="metalogs_time_idx">
						<col>timestamp</col>
					</index>
					<index name="metalogs_op_idx">
						<col>operation</col>
					</index>
				</table>
			</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}

		$id = $adb->getUniqueId($table);
		$columns = array(
			'logid' => $id,
			'timestamp' => date('Y-m-d H:i:s'),
			'operation' => $operation,
		);

		if (!is_null($objectid)) $columns['objectid'] = $objectid;
		if (!is_null($data)) $columns['data'] = Zend_Json::encode($data);

		$query = "INSERT INTO $table (".implode(',', array_keys($columns)).") VALUES (".generateQuestionMarks($columns).")";
		$adb->pquery($query, array_values($columns));
		
		return $id;	//crmv@146434
	}

	/**
	 * Clears the log table
	 */
	public function clearAll() {
		global $adb;
		if (!$this->enabled) return null;
		$adb->query("DELETE FROM {$this->table}");
	}

	/**
	 * Gets only last change, optionally filtered with the operation
	 */
	public function getLastChange($operation = null) {
		global $adb;

		$res = $adb->limitPquery("SELECT * FROM {$this->table}".(is_null($operation) ? "" : " WHERE operation = ?")." ORDER BY logid DESC", 0, 1, array($operation));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			if ($row['data']) $row['data'] = Zend_Json::decode($row['data']);
			return $row;
		}
		return null;
	}

	/**
	 * Retrieves the changes, optionally filtered by the operation, start date (inclusive) and end date (esclusive)
	 */
	public function getChanges($operation = null, $dateStart = null, $dateEnd = null) {
		global $adb;

		$params = array();
		$query = "SELECT * FROM {$this->table}";

		if (!empty($operation) || !empty($dateStart) || !empty($dateEnd)) {
			$clauses = array();

			if (!empty($operation)) {
				$clauses[] = "operation = ?";
				$params[] = $operation;
			}
			if (!empty($dateStart)) {
				$clauses[] = "timestamp >= ?";
				$params[] = $dateStart;
			}
			if (!empty($dateEnd)) {
				$clauses[] = "timestamp < ?";
				$params[] = $dateEnd;
			}

			if (count($clauses) > 0) {
				$query .= " WHERE ".implode(' AND ', $clauses);
			}
		}

		$query .= " ORDER BY logid ASC";

		$list = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				if ($row['data']) $row['data'] = Zend_Json::decode($row['data']);
				$list[] = $row;
			}
		}
		return $list;
	}

	/**
	 * Returns a list of modules which were influenced directly by the change for the current user.
	 * Returns ['All'] in case avery module is potentially influenced
	 * Returns false in case this information cannot be retrieved
	 * TODO: refine this function
	 */
	public function getAffectedModules($logrow) {
		global $current_user;

		$userid = $current_user->id;
		$modules = array();

		switch ($logrow['operation']) {
			case self::OPERATION_ADDROLE:
			case self::OPERATION_EDITROLE:
				$roleid = $logrow['data']['roleid'];
				if ($roleid && $current_user->roleid == $roleid) $modules = array('All');
				break;

			case self::OPERATION_ADDPROFILE:
			case self::OPERATION_EDITPROFILE:
				$profileid = $logrow['objectid'];
				$uprofiles = getUserProfile($userid, 0);
				$uprofiles = array_merge($uprofiles, getUserProfile($userid, 1));
				if (in_array($profileid, $uprofiles)) $modules = array('All');
				break;

			case self::OPERATION_DELROLE:
			case self::OPERATION_DELPROFILE:
			case self::OPERATION_DELMODULE:
				// I don't know if the current user was related to the role/profile
				// So I assume everything
				$modules = array('All');
				break;

			case self::OPERATION_ADDSHRULE:
			case self::OPERATION_DELSHRULE:
			case self::OPERATION_EDITSHRULE:
			case self::OPERATION_ADDMODULE:
			case self::OPERATION_EDITMODULE:
			case self::OPERATION_EDITMODFIELDS:
			case self::OPERATION_EDITMODFILTERS:
			case self::OPERATION_DELFIELD:
			case self::OPERATION_DELBLOCK:
			case self::OPERATION_DELFILTER:
				$mod = $logrow['data']['module'];
				if ($mod) {
					$modules[] = $mod;
				} else {
					$modules[] = 'All';
				}
				break;

			case self::OPERATION_ADDFIELD:
			case self::OPERATION_EDITFIELD:
				$fieldid = $logrow['objectid'];
				if ($fieldid) {
					$mod = $this->getModuleForFieldid($fieldid);
					if ($mod) {
						$modules[] = $mod;
					} else {
						$modules[] = 'All';
					}
				}
				break;

			case self::OPERATION_ADDBLOCK:
			case self::OPERATION_EDITBLOCK:
				$blockid = $logrow['objectid'];
				if ($blockid) {
					$mod = $this->getModuleForBlockid($blockid);
					if ($mod) {
						$modules[] = $mod;
					} else {
						$modules[] = 'All';
					}
				}
				break;

			case self::OPERATION_ADDFILTER:
			case self::OPERATION_EDITFILTER:
				$filterid = $logrow['objectid'];
				if ($filterid) {
					$mod = $this->getModuleForFilterid($filterid);
					if ($mod) {
						$modules[] = $mod;
					} else {
						$modules[] = 'All';
					}
				}
				break;
			case self::OPERATION_REBUILDSHARES:
				break;

			case self::OPERATION_INSTALLED:
			case self::OPERATION_UPDATED:
			default:
				$modules = array('All');
				break;
		}

		return $modules;
	}

	// TODO: cache this info
	protected function getModuleForFieldid($fieldid) {
		global $adb, $table_prefix;

		$res = $adb->pquery("select name from {$table_prefix}_tab t inner join {$table_prefix}_field f on f.tabid = t.tabid where f.fieldid = ?", array($fieldid));
		if ($res && $adb->num_rows($res) > 0) {
			$mod = $adb->query_result_no_html($res, 0, 'name');
			return $mod;
		}
		return null;
	}

	protected function getModuleForBlockid($blockid) {
		global $adb, $table_prefix;

		$res = $adb->pquery("select name from {$table_prefix}_tab t inner join {$table_prefix}_blocks b on b.tabid = t.tabid where b.blockid = ?", array($blockid));
		if ($res && $adb->num_rows($res) > 0) {
			$mod = $adb->query_result_no_html($res, 0, 'name');
			return $mod;
		}
		return null;
	}

	protected function getModuleForFilterid($filterid) {
		global $adb, $table_prefix;

		$res = $adb->pquery("select entitytype as name from {$table_prefix}_customview where cvid = ?", array($filterid));
		if ($res && $adb->num_rows($res) > 0) {
			$mod = $adb->query_result_no_html($res, 0, 'name');
			return $mod;
		}
		return null;
	}
}