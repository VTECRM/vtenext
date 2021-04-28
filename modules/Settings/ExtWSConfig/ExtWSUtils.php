<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146670 crmv@146671 */

/**
 * Class to manage External WS Configuration
 */
class ExtWSUtils {

	public $table_name = '';
	
	public $ws_types = array('REST'); // only REST is supported for the moment
	public $ws_methods = array('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH');
	
	public function __construct() {
		global $table_prefix;
		$this->table_name = $table_prefix.'_extws';
	}
	
	/**
	 * Get informations about a single WS
	 */
	public function getWSInfo($id) {
		global $adb, $table_prefix;
		
		$ret = false;
		$res = $adb->pquery("SELECT * FROM {$this->table_name} WHERE extwsid = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$ret = $this->transformRowFromDb($row);
			}
		}
		return $ret;
	}
	
	/**
	 * Check if a specific WS is active
	 */
	public function isWSActive($id) {
		global $adb, $table_prefix;
		
		$ret = false;
		$res = $adb->pquery("SELECT active FROM {$this->table_name} WHERE extwsid = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			$active = $adb->query_result_no_html($res, 0, 'active');
			$ret = ($active == '1');
		}
		return $ret;
	}

	/**
	 * Get the list of external webservices configured
	 */
	public function getList($active = null) {
		global $adb, $table_prefix;
		
		$ret = array();
		
		$query = "SELECT * FROM {$this->table_name}";
		$params = array();
		
		if (!is_null($active)) {
			$query .= " WHERE active = ?";
			$params[] = $active ? 1 : 0;
		}
		
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$ret[] = $this->transformRowFromDb($row);
			}
		}
		return $ret;
	}
	
	/**
	 * Save a new external WS
	 */
	public function insertWS($data) {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		$id = $adb->getUniqueID($this->table_name);
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'extwsid' => $id,
			'wsname' => $data['wsname'],
			'wsdesc' => $data['wsdesc'],
			'wstype' => $data['wstype'],
			'method' => $data['method'],
			'wsurl' => $data['wsurl'],
			'active' => $data['active'],
			'createdtime' => $now,
			'modifiedtime' => $now,
		);
		$q = "INSERT INTO {$this->table_name} (".implode(',', array_keys($params)).") VALUES (".generateQuestionMarks($params).")";
		
		// insert the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('authinfo', 'headers', 'params', 'rawbody', 'results'); // crmv@190014
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "extwsid = $id", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		return $id;
	}
	
	/**
	 * Update an existing WS
	 */
	public function updateWS($id, $data) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'modifiedtime' => $now,
			'wsname' => $data['wsname'],
			'wsdesc' => $data['wsdesc'],
			'wstype' => $data['wstype'],
			'method' => $data['method'],
			'wsurl' => $data['wsurl'],
			'active' => $data['active'],
			'extwsid' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, wsname = ?, wsdesc = ?, wstype = ?, method = ?, wsurl = ?, active = ? WHERE extwsid = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);

		// update the long text fields
		if ($res) {
			$jsonFields = array('authinfo', 'headers', 'params', 'rawbody', 'results'); // crmv@190014
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "extwsid = $id", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		return true;
	}
	
	public function updateSingleField($id, $field, $value) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$params = array(
			'modifiedtime' => $now,
			$field => $value,
			'extwsid' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, {$field} = ? WHERE extwsid = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
	}

	/**
	 * Remove a configured WS
	 */
	public function deleteWS($id) {
		global $adb, $table_prefix;
		
		// remove the saved line
		$adb->pquery("DELETE FROM {$this->table_name} WHERE extwsid = ?", array($id));
		
		return true;
	}
	
	public function prepareDataFromRequest() {
		$data = array(
			'wsname' => vtlib_purify($_REQUEST['extws_name']),
			'wsdesc' => vtlib_purify($_REQUEST['extws_desc']),
			'wstype' => $_REQUEST['extws_type'],
			'method' => $_REQUEST['extws_method'],
			'wsurl' => $_REQUEST['extws_url'],
			'active' => ($_REQUEST['extws_active'] == 'on'),
			'authinfo' => Zend_Json::decode($_REQUEST['extws_auth']) ?: '',
			'headers' => Zend_Json::decode($_REQUEST['extws_headers']) ?: array(),
			'params' => Zend_Json::decode($_REQUEST['extws_params']) ?: array(),
			'rawbody' => $_REQUEST['extws_rawbody'] ?: '', // crmv@190014
			'results' => Zend_Json::decode($_REQUEST['extws_results']) ?: array(),
		);
		return $data;
	}
	
	/**
	 * Try to extract automatically the fields form the response
	 */
	 // TODO: use the EXTWSExtractor!
	public function automapFields($data, &$error) {
		if (empty($data)) {
			$error = getTranslatedString('LBL_NO_DATA', 'Settings');
			return false;
		}
		
		$fields = array();
		
		if ($data[0] == '[' || $data[0] == '{') {
			// try with json
			$decoded = Zend_Json::decode($data);
			if (!is_array($decoded)) {
				$error = getTranslatedString('LBL_NO_VALID_JSON', 'Settings');
				return false;
			}
			$fields = array_keys($decoded);
			$fields = array_combine($fields, $fields);
		} elseif ($data[0] == '<') {
			// try with xml
			$decoded = @simplexml_load_string($data);
			if (!is_object($decoded)) {
				$error = getTranslatedString('LBL_NO_VALID_XML', 'Settings');
				return false;
			}
			foreach ($decoded as $name => $val) {
				if (is_object($val)) {
					$fields[$name] = $name;
				}
			}
		} else {
			$error = getTranslatedString('LBL_NO_VALID_DATA_FORMAT', 'Settings');
			return false;
		}
		
		return $fields;
	}
	
	protected function transformRowFromDb($row) {
		global $default_charset; //crmv@169797
		$jsonFields = array('authinfo', 'headers', 'params', 'results');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::decode($row[$f]);
				if ($f != 'authinfo' && !is_array($row[$f])) {
					$row[$f] = array();
				}
				//crmv@169797
				if (!empty($row[$f])) {
					foreach($row[$f] as &$field) {
						if (!empty($field['value'])) $field['value'] = htmlentities($field['value'],ENT_QUOTES,$default_charset);
					}
				}
				//crmv@169797e
			}
		}
		$row['active'] = ($row['active'] == '1');
		if (isset($row['wsdesc'])) {
			$shorter = textlength_check($row['wsdesc'], 100);
			if (substr($shorter, -3) == '...') {
				$LVC = ListViewController::getInstance(null, null, null);
				$shorter .= $LVC->getMoreInformationsDiv($row['extwsid'], 'wsdesc', $row['wsdesc']);
			}
			$row['wsdesc_html'] = $shorter;
		}
		return $row;
	}
	
	protected function transformRowToDb($row) {
		$jsonFields = array('authinfo', 'headers', 'params', 'results');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::encode($row[$f]);
			}
		}
		$row['active'] = ($row['active'] ? '1' : '0');
		return $row;
	}
}