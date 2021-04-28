<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@181231 */

/**
 * Save session data in DB
 */
class DBSessionHandler implements SessionHandlerInterface {

	protected $lifetime;
	protected $table = '';
	
	public function __construct($params = array()) {
		global $adb, $table_prefix;
		
		$this->table = $table_prefix.'_sessions';
		
		if (!$adb || !$adb->database->IsConnected()) {
			throw new Exception('DB connection failed');
		}
		
		// TODO: don't check every time!
		if(!Vtecrm_Utils::CheckTable($this->table)) {
			$schema = '<?xml version="1.0"?>
						<schema version="0.3">
						<table name="'.$this->table.'">
						<opt platform="mysql">ENGINE=InnoDB</opt>
							<field name="sid" type="C" size="63">
								<KEY/>
							</field>
							<field name="ts" type="I" size="19">
								<NOTNULL/>
							</field>
							<field name="data" type="XL"/>
							<index name="session_ts_idx">
								<col>ts</col>
							</index>
						</table>
						</schema>';
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
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
		global $adb;
		
		$res = $adb->pquery("SELECT data FROM {$this->table} WHERE sid = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'data');
		}
		return '';
    }

    public function write($id, $data) {
        global $adb;
        
        $ts = time();
		if ($adb->isMysql()) {
			$res = $adb->pquery(
				"INSERT INTO {$this->table} (sid, ts, data) VALUES (?,?,?) ON DUPLICATE KEY UPDATE data=?, ts = ?",
				array($id, $ts, $data, $data, $ts)
			);
		} else {
			// untested code!!
			$res = $adb->pquery(
				"UPDATE {$this->table} SET data=?, ts = ? WHERE sid = ?",
				array($data, $ts, $id)
			);
			if ($res && $adb->getAffectedRowCount($res) == 0) {
				$res = $adb->pquery(
					"INSERT INTO {$this->table} (sid, ts, data) VALUES (?,?,?)",
					array($id, $ts, $data)
				);
			}
		}
		
		return !!$res;
    }

    public function destroy($id) {
        global $adb;
		$res = $adb->pquery("DELETE FROM {$this->table} WHERE sid = ?", array($id));
		return !!$res;
    }

    public function gc($maxlifetime) {
		global $adb;
		$oldts = time()-$maxlifetime;
		$res = $adb->pquery("DELETE FROM {$this->table} WHERE ts < = ?", array($oldts));
        return !!$res;
    }
    
}