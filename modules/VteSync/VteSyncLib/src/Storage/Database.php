<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Storage;

use VteSyncLib\Storage\DatabaseUtils;
use VteSyncLib\Exception\DBException;

class Database implements 
	StorageInterface,
	OAuthInterface,
	AuthInterface // crmv@190016
{

	//public $schema_version = '1.1';		// crmv@120777
	
	protected $db = null;
	protected $dbu = null;
	
	public function __construct($config = array()) {
		$this->config = $config;
		
		$this->tables = array(
			'version' => 'version',
			'mapping' => 'id_mapping',
			'last_sync' => 'last_sync',
			'auth' => 'auth', // crmv@190016
			'oauth2' => 'oauth2',
			'tokens' => 'oauth2_tokens',
		);
		
		$this->log = new \VteSyncLib\Logger();
		$this->log->setPrefix('[DB]');
	}
	
	public function connect() {
		
		$this->includeAdodb();

		$config = $this->config;

		$this->db = NewADOConnection($config['db_type']);
		$this->db->debug = $config['debug'];
		$this->db->PConnect($config['db_host'], $config['db_username'], $config['db_password'], $config['db_name']);
		
		if (!$this->db->IsConnected()) {
			throw new DBException('Unable to connect to local database');
		}
		
		$this->db->setFetchMode(ADODB_FETCH_ASSOC);
		
		// set default date format
		if ($config['db_type'] == 'oci8po' || $config['db_type'] == 'oci8') {
			$this->db->Execute("ALTER SESSION SET nls_date_format = 'YYYY-MM-DD HH24:MI:SS'");
		}

		$this->dbu = new DatabaseUtils($this->db); // crmv@120777
		
		return true;
	}
	
	protected function includeAdodb() {
		global $ADODB_FETCH_MODE;
		
		//define('ADODB_ASSOC_CASE', 0); // ADODB: force column names lowercase

		require_once VTESYNC_BASEDIR.'/libs/adodb5/adodb.inc.php';
		require_once VTESYNC_BASEDIR.'/libs/adodb5/adodb-xmlschema03.inc.php';
	}		
	
	public function initSchema() {
		// crmv@120777
		$r = $this->setupTables();
		if (!$r) return false;
		
		// TODO: schema update
		/*$r = $this->updateSchema();
		if (!$r) return false;*/
		// crmv@120777e
		
		return true;
	}
	
	protected function setupTables() {
		$this->log->debug('Entering '.__METHOD__);

		// crmv@120777
		/*if (!in_array($this->tables['version'], $this->db->MetaTables())) {
			$schema_table =
			'<schema version="0.3">
				<table name="'.$this->tables['version'].'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="schema_version" type="C" size="31">
						<KEY/>
					</field>
				</table>
			</schema>';
			$schema_obj = new \adoSchema($this->db);
			$r = $schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
			if (!$r) return $this->log->fatal('Unable to create local table '.$this->tables['version']);
		}*/

		if (!in_array($this->tables['mapping'], $this->db->MetaTables())) {
			$schema_table =
			'<schema version="0.3">
				<table name="'.$this->tables['mapping'].'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="groupid" type="I" size="19">
						<KEY/>
					</field>
					<field name="connector" type="C" size="31">
						<KEY/>
					</field>
					<field name="module" type="C" size="31">
						<KEY/>
					</field>
					<field name="conn_id" type="C" size="255">
						<KEY/>
					</field>
					<field name="etag" type="C" size="127" />
					<index name="mapping_all_idx">
						<col>connector</col>
						<col>module</col>
						<col>conn_id</col>
					</index>
					<index name="mapping_module_idx">
						<col>module</col>
					</index>
				</table>
			</schema>';
			$schema_obj = new \adoSchema($this->db);
			$r = $schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
			if (!$r) return $this->log->fatal('Unable to create local table '.$this->tables['mapping']);
		}
		// crmv@120777e
		
		if (!in_array($this->tables['lastsync'], $this->db->MetaTables())) {
			$schema_table =
			'<schema version="0.3">
				<table name="'.$this->tables['lastsync'].'">
					<opt platform="mysql">ENGINE=InnoDB</opt>
					<field name="vte_user" type="C" size="127">
						<KEY/>
					</field>
					<field name="connector" type="C" size="63">
						<KEY/>
					</field>
					<field name="module" type="C" size="63">
						<KEY/>
					</field>
					<field name="last_sync" type="T">
						<DEFAULT value="0000-00-00 00:0:00" />
					</field>
					<field name="last_page" type="C" size="63" />
					<index name="last_sync_user">
						<col>vte_user</col>
					</index>
					<index name="last_sync_connector">
						<col>connector</col>
					</index>
					<index name="last_sync_module">
						<col>module</col>
					</index>
				</table>
			</schema>';
			$schema_obj = new \adoSchema($this->db);
			$r = $schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
			if (!$r) return $this->log->fatal('Unable to create local table '.$this->tables['lastsync']);
		}
		
		// TODO: create auth, oauth2 and tokens tables

		return true;
	}
	
	// crmv@120777
	/*public function getSchemaVersion() {
		$ver = $this->db->GetOne("SELECT schema_version FROM {$this->tables['version']}");
		if ($ver !== false) {
			return $ver ?: '0.0';
		}
		return false;
	}
	
	public function updateSchema() {
		$this->log->debug('Entering '.__METHOD__);
		
		$ver = $this->getSchemaVersion();
		if ($ver === false) {
			return $this->log->fatal('Unable to retrieve schema version');
		}
		$this->log->info('Current schema version is '.$ver);
		
		if (version_compare($ver, $this->schema_version) < 0) {
			$this->log->info('Updating schema to version '.$this->schema_version."...");
			
			$steps = array(
				'0.0' => '1.1',
				// add here other steps
			);
			$start = $ver;
			while (isset($steps[$start])) {
				$next = $steps[$start];
				$r = $this->updateSchemaStep($start, $next);
				if (!$r) {
					return $this->log->error("Error while updating schema");
				}
				$start = $next;
			}
		}

		return true;
	}
	
	protected function updateSchemaStep($from, $to) {
		$this->log->debug("Updating schema from version $from to $to");
		
		$ok = false;
		if ($from == '0.0' && $to == '1.1') {
			$ok = $this->dbu->addColumnToTable('id_mapping', 'inotes_id', 'C(63)');
			if ($ok) {
				$ok = $this->dbu->addIndexToTable('id_mapping', 'mapping_inotes_id_idx', 'inotes_id');
			}
		// add here other steps
		} else {
			return $this->log->error("Invalid schema versions for update ($from -> $to)");
		}
		if ($ok) {
			$this->updateSchemaVersion($to);
			return true;
		}
		return false;
	}
	
	protected function updateSchemaVersion($ver) {
		// check if insert or update
		$cnt = $this->db->GetOne("SELECT COUNT(*) AS cnt FROM {$this->tables['version']}");
		if ($cnt == 0) {
			$r = $this->db->Execute("INSERT INTO {$this->tables['version']} (schema_version) VALUES (?)", array($ver));
		} else {
			$r = $this->db->Execute("UPDATE {$this->tables['version']} SET schema_version = ?", array($ver));
		}
		if ($r) return $ver;
		return false;
	}*/
	// crmv@120777e
	
	public function getLastSyncDate($vteuser, $connector, $module) {

		$r = $this->db->Execute("SELECT last_sync FROM {$this->tables['lastsync']} WHERE vte_user = ? AND connector = ? AND module = ?", array($vteuser, $connector, $module));
		if (!$r) {
			return false;
		}
		$row = $r->fetchRow();
		$date = $row['last_sync'];
		if (empty($date)) {
			// first sync, return 'never'
			$date = 'never';
		}

		return $date;
	}
	
	public function setLastSyncDate($vteuser, $connector, $module, $date = null) {
		if (empty($date)) {
			// current date if empty
			$date = date('Y-m-d H:i:s');
		}

		// crmv@195073
		// I need to select + update instead of delete+insert to preserve tha page parameter
		$r = $this->db->Execute("SELECT last_sync FROM {$this->tables['lastsync']} WHERE vte_user = ? AND connector = ? AND module = ?", array($vteuser, $connector, $module));
		if ($r && $r->RowCount() > 0) {
			$r = $this->db->Execute("UPDATE {$this->tables['lastsync']} SET last_sync = ? WHERE vte_user = ? AND connector = ? AND module = ?", array($date, $vteuser, $connector, $module));
		} else {
			$r = $this->db->Execute("INSERT INTO {$this->tables['lastsync']} (vte_user, connector, module, last_sync) VALUES (?,?,?,?)", array($vteuser, $connector, $module, $date));
		}
		// crmv@195073
		
		if (!$r) {
			$this->log->warning("Query error: ".$this->db->ErrorMsg());
		}
		
		return !!$r;
	}
	
	// crmv@195073
	public function getLastSyncPage($vteuser, $connector, $module) {

		$r = $this->db->Execute("SELECT last_page FROM {$this->tables['lastsync']} WHERE vte_user = ? AND connector = ? AND module = ?", array($vteuser, $connector, $module));
		if (!$r) {
			return false;
		}
		$row = $r->fetchRow();
		$page = $row['last_page'];
		
		return $page;
	}
	
	public function setLastSyncPage($vteuser, $connector, $module, $page = null) {

		$r = $this->db->Execute("UPDATE {$this->tables['lastsync']} SET last_page = ? WHERE vte_user = ? AND connector = ? AND module = ?", array($page, $vteuser, $connector, $module));

		if (!$r) {
			$this->log->warning("Query error: ".$this->db->ErrorMsg());
		}
		
		return !!$r;
	}
	// crmv@195073e
	
	public function resetAll() {
		$this->db->Execute("TRUNCATE TABLE {$this->tables['mapping']}");
		$this->db->Execute("TRUNCATE TABLE {$this->tables['lastsync']}");
	}
	
	public function resetConnector($connector) {
		$this->db->Execute("DELETE FROM {$this->tables['mapping']} WHERE connector = ?", array($connector));
		$this->db->Execute("DELETE FROM {$this->tables['lastsync']} WHERE connector = ?", array($connector));
	}
	
	public function getGroupId($connector, $module, $id) {
		$res = $this->db->Execute("SELECT groupid FROM {$this->tables['mapping']} WHERE connector = ? AND module = ? AND conn_id = ?", array($connector, $module, $id));
		if ($res && $res->RowCount() > 0) {
			$row = $res->fetchRow();
			return $row['groupid'];
		}
		return null;
	}
	
	public function getMappedIds($connector, $module, $id) {
		$ret = array();
		
		$groupid = $this->getGroupId($connector, $module, $id);
		if (!$groupid) return $ret;
		
		$r = $this->db->Execute("SELECT connector, conn_id, etag FROM {$this->tables['mapping']} WHERE groupid = ?", array($groupid));
		while ($row = $r->fetchRow()) {
			$cname = $row['connector'];
			$ret[$cname] = array('id' => $row['conn_id'], 'etag' => $row['etag']);
		}
	
		return $ret;
	}
	
	public function getAllMappedIds($module) {
		$ret = array();
		
		$r = $this->db->Execute("SELECT groupid, connector, conn_id FROM {$this->tables['mapping']} WHERE module = ?", array($module));
		while ($row = $r->fetchRow()) {
			$cname = $row['connector'];
			$ret[$row['groupid']][$cname] = array('id' => $row['conn_id']);
		}
	
		return $ret;
	}
	
	public function saveMappedIds($connector, $module, $id, $otherids = array(), $etags = array()) {
		
		// add the passed id in case of insert
		$otherids[$connector] = $id;
		
		// check for existence
		$groupid = $this->getGroupId($connector, $module, $id);
		$this->db->BeginTrans();
		if ($groupid) {
			// update
			foreach ($otherids as $conn => $otherid) {
				$etag = $etags[$conn] ?: null;
				$res = $this->db->Execute("UPDATE {$this->tables['mapping']} SET conn_id = ?, etag = ? WHERE groupid = ? AND connector = ? AND module = ?", array($otherid, $etag, $groupid, $conn, $module));
				if ($this->db->Affected_Rows() == 0) {
					// insert
					$res = $this->db->Execute("INSERT INTO {$this->tables['mapping']} (groupid, connector, module, conn_id, etag) VALUES (?,?,?,?,?)", array($groupid, $conn, $module, $otherid, $etag));
				}
			}
		} else {
			// insert
			$groupid = $this->dbu->getUniqueId($this->tables['mapping']);
			foreach ($otherids as $conn => $otherid) {
				$etag = $etags[$conn] ?: null;
				$res = $this->db->Execute("INSERT INTO {$this->tables['mapping']} (groupid, connector, module, conn_id, etag) VALUES (?,?,?,?,?)", array($groupid, $conn, $module, $otherid, $etag));
			}
		}
		$this->db->CommitTrans();
		
		return !!$res;
	}
	
	public function getEtag($connector, $module, $id) {
		$res = $this->db->Execute("SELECT etag FROM {$this->tables['mapping']} WHERE connector = ? AND module = ? AND conn_id = ?", array($connector, $module, $id));
		if ($res && $res->RowCount() > 0) {
			$row = $res->fetchRow();
			return $row['etag'];
		}
		return null;
	}
	
	public function setEtag($connector, $module, $id, $etag) {
		$res = $this->db->Execute("UPDATE {$this->tables['mapping']} SET etag = ? WHERE connector = ? AND module = ? AND conn_id = ?", array($etag, $connector, $module, $id));
		return ($this->db->Affected_Rows() > 0);
	}
	
	
	// --------------------------------- OAuthInterface ---------------------------------
	
	// TODO: pass connector/module
	public function getOAuthInfo($syncid) {
		$res = $this->db->Execute("SELECT * FROM {$this->tables['oauth2']} WHERE syncid = ?", array($syncid));
		if ($res && $res->RowCount() > 0) {
			$row = $res->fetchRow();
			unset($row['syncid']);
			return $row;
		}
		return null;
	}
	
	public function getTokenInfo($syncid) {
		$res = $this->db->Execute("SELECT * FROM {$this->tables['tokens']} WHERE syncid = ?", array($syncid));
		if ($res && $res->RowCount() > 0) {
			$row = $res->fetchRow();
			unset($row['syncid']);
			return $row;
		}
		return null;
	}
	
	public function setTokenInfo($syncid, $tokenInfo) {
		$res = $this->db->Execute("UPDATE {$this->tables['tokens']} SET token = ? WHERE syncid = ?", array($tokenInfo['access_token'], $syncid));
	}
	
	/*public function setRefreshToken($syncid, $token) {
	}*/
	
	// crmv@190016
	
	// --------------------------------- AuthInterface ---------------------------------
	
	public function getAuthInfo($syncid) {
		$res = $this->db->Execute("SELECT * FROM {$this->tables['auth']} WHERE syncid = ?", array($syncid));
		if ($res && $res->RowCount() > 0) {
			$row = $res->fetchRow();
			unset($row['syncid']);
			return $row;
		}
		return null;
	}
	// crmv@190016e
	
}