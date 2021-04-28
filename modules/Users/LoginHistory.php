<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@91082 */

/** 
 * This class is used to store and display the login history of all the Users.
 * An Admin User can view his login history details  and of all the other users as well.
 * StandardUser is allowed to view only his login history details.
 */
class LoginHistory extends SDKExtendableUniqueClass {
	
	protected $log;
	protected $db;

	//public $module_name = "Users";
	public $list_fields = Array();
	public $table_name;

	public $object_name = "LoginHistory";

	public $column_fields = Array(
		"id",
		"login_id",
		"user_name",
		"user_ip",
		"type",
		"login_time",
		"logout_time",
		"status"
	);
	
	public $sortby_fields = Array('user_name', 'user_ip', 'type', 'login_time', 'logout_time', 'status');

	// This is the list of vte_fields that are in the lists.

	public $list_fields_name = Array(
		'User Name'=>'user_name',
		'User IP'=>'user_ip',
		'Type'=>'type',
		'Signin Time'=>'login_time',
		'Signout Time'=>'logout_time',
		'Status'=>'status'
	);
	
	public $default_order_by = "login_time";
	public $default_sort_order = 'DESC';

	public function __construct() {
		global $table_prefix;
		$this->table_name = $table_prefix."_loginhistory";
		$this->list_fields = Array(
			'User Name'=>Array($table_prefix.'_loginhistory'=>'user_name'),
			'User IP'=>Array($table_prefix.'_loginhistory'=>'user_ip'),
			'Type'=>Array($table_prefix.'_loginhistory'=>'type'),
			'Signin Time'=>Array($table_prefix.'_loginhistory'=>'login_time'),
			'Signout Time'=>Array($table_prefix.'_loginhistory'=>'logout_time'),
			'Status'=>Array($table_prefix.'_loginhistory'=>'status'),
		);
		$this->log = LoggerManager::getLogger('loginhistory');
		$this->db = PearDatabase::getInstance();
	}

	/**
	 * Function to get the Header values of Login History.
	 * Returns Header Values like UserName, IP, LoginTime etc in an array format.
	 **/
	public function getHistoryListViewHeader() {
		global $log;
		$log->debug("Entering getHistoryListViewHeader method ...");
		global $app_strings;

		$header_array = array($app_strings['LBL_LIST_USER_NAME'], $app_strings['LBL_LIST_USERIP'], $app_strings['LBL_TYPE'], $app_strings['LBL_LIST_SIGNIN'], $app_strings['LBL_LIST_SIGNOUT'], $app_strings['LBL_LIST_STATUS']);

		$log->debug("Exiting getHistoryListViewHeader method ...");
		return $header_array;

	}

	/**
	 * Function to get the Login History values of the User.
	 * @param $navigation_array - Array values to navigate through the number of entries.
	 * @param $sortorder - DESC
	 * @param $orderby - login_time
	 * Returns the login history entries in an array format.
	 **/
	public function getHistoryListViewEntries($username, $navigation_array, $sorder='', $orderby='') {
		global $log, $adb;
		
		$log->debug("Entering getHistoryListViewEntries() method ...");

		if($sorder != '' && $orderby != '') {
	    	$list_query = "Select * from {$this->table_name} where user_name=? order by ".$orderby." ".$sorder;
		} else {
			$list_query = "Select * from {$this->table_name} where user_name=? order by ".$this->default_order_by." ".$this->default_sort_order;
		}

		$result = $adb->pquery($list_query, array($username));
		$entries_list = array();

		if ($navigation_array['end_val'] != 0) {
			for($i = $navigation_array['start']; $i <= $navigation_array['end_val']; $i++) {
				$entries = array();
				$loginid = $adb->query_result_no_html($result, $i-1, 'login_id');

				$entries[] = $adb->query_result_no_html($result, $i-1, 'user_name');
				$entries[] = $adb->query_result_no_html($result, $i-1, 'user_ip');
				$entries[] = $adb->query_result_no_html($result, $i-1, 'type');
				$entries[] = $adb->query_result_no_html($result, $i-1, 'login_time');
				$entries[] = $adb->query_result_no_html($result, $i-1, 'logout_time');
				$entries[] = $adb->query_result_no_html($result, $i-1, 'status');

				$entries_list[] = $entries;
			}
			$log->debug("Exiting getHistoryListViewEntries() method ...");
			return $entries_list;
		}
	}
	
	/**
	 * Get the login type
	 */
	public function getLoginType() {
		global $current_user;
		
		$isPlugin = isZMergeAgent() || isVteSyncAgent();
		
		if (requestFromMobile()) {
			return 'app';
		} elseif (requestFromWebservice() && !$isPlugin) {
			return 'ws';
		} elseif ($isPlugin) {
			return 'plugin';
		} else {
			return 'web';
		}
	}
	
	/**
	 * This is valid only when the request come from the app
	 */
	public function getDeviceId() {
		require_once('modules/Touch/Touch.php');
		
		$touchInst = Touch::getInstance();
		
		return $touchInst->getCurrentDeviceId() ?: '';
	}

	/** Function that Records the Login info of the User
	 *  @param $usname :: Type varchar
	 *  @param $usip :: Type varchar
	 *  @param $intime :: Type timestamp
	 *  Returns the query result which contains the details of User Login Info
	 */
	public function user_login($usname, $usip = null, $intime = null, $type = 'auto') {
		global $adb;

		if (empty($usip)) $usip = getIP();
		if (empty($intime)) $intime = date("Y-m-d H:i:s");
		
		if ($type == 'auto') $type = $this->getLoginType();
		
		$deviceid = $sessionid = '';
		if ($type == 'plugin') {
			return null;	// plugins are not logged
		} elseif ($type == 'app') {
			$deviceid = $this->getDeviceId();
		} else {
			$sessionid = session_id();
		}
		
		$id = $adb->getUniqueId($this->table_name);
		//crmv@33867 crmv@202301
		$params = array($id,$usname,$type,$sessionid,$deviceid,RequestHandler::getId(),$usip,$this->db->formatDate($intime, true),'Signed in');
		$query = "INSERT INTO {$this->table_name} (login_id,user_name, type, sessionid, deviceid, request_id, user_ip, login_time, status) VALUES (".generateQuestionMarks($params).")";
		//crmv@33867e crmv@202301e
		$result = $adb->pquery($query, $params);
		
		// save it also in the current session, for faster retrieval
		if (!VteSession::hasKey('login_time')) VteSession::set('login_time', $intime);
		
		return $result;
	}

	/** Function that Records the Logout info of the User
	 *  @param ref variable $usname :: Type varchar
	 *  @param ref variable $usip :: Type varchar
	 *  @param ref variable $outime :: Type timestamp
	 *  Returns the query result which contains the details of User Logout Info
	 */
	public function user_logout($usname, $usip = null, $outtime = null, $type = 'auto', $forced = false) {
		global $adb;
		
		if (empty($usip)) $usip = getIP();
		if (empty($outtime)) $outtime = date("Y-m-d H:i:s");
		
		if ($type == 'auto') $type = $this->getLoginType();
		
		$wheres = array();
		$params = array();
		
		$wheres[] = "user_name = ? AND type = ?";
		$params[] = $usname;
		$params[] = $type;
		
		$deviceid = $sessionid = '';
		if ($type == 'plugin') {
			return null;	// plugins are not logged
		} elseif ($type == 'app') {
			$deviceid = $this->getDeviceId();
			$wheres[] = 'deviceid = ?';
			$params[] = $deviceid;
		} else {
			$sessionid = session_id();
			$wheres[] = 'sessionid = ?';
			$params[] = $sessionid;
		}
		
		$logid_qry = "SELECT MAX(login_id) AS login_id FROM {$this->table_name} WHERE ".implode(' AND ', $wheres);
		$result = $adb->pquery($logid_qry, $params);
		$loginid = $adb->query_result_no_html($result,0,"login_id");
		if ($loginid == '') return;
		
		$str = ($forced ? 'Evicted' : 'Signed off');
		
		// update the user login info.
		$query = "UPDATE {$this->table_name} SET logout_time = ?, status = ? WHERE login_id = ?";
		$params = array($this->db->formatDate($outtime, true), $str, $loginid);
		$result = $adb->pquery($query,$params);
		
		return $result;
	}
	
	/** Function that Records the activity for the user
	 *  @param ref variable $usname :: Type varchar
	 *  @param ref variable $usip :: Type varchar
	 *  @param ref variable $outime :: Type timestamp
	 *  Returns the query result which contains the details of User Logout Info
	 */
	public function user_activity($usname, $type = 'auto') {
		global $adb;

		$time = date('Y-m-d H:i:s');
		if ($type == 'auto') $type = $this->getLoginType();
		
		$wheres = array();
		$params = array();
		
		$wheres[] = "user_name = ? AND type = ?";
		$params[] = $usname;
		$params[] = $type;

		$deviceid = $sessionid = '';
		if ($type == 'plugin') {
			return null;	// plugins are not logged
		} elseif ($type == 'app') {
			$deviceid = $this->getDeviceId();
			$wheres[] = 'deviceid = ?';
			$params[] = $deviceid;
		} else {
			$sessionid = session_id();
			$wheres[] = 'sessionid = ?';
			$params[] = $sessionid;
		}
		
		$logid_qry = "SELECT max(login_id) AS login_id FROM {$this->table_name} WHERE ".implode(' AND ', $wheres);
		$result = $adb->pquery($logid_qry, $params);
		$loginid = $adb->query_result_no_html($result,0,"login_id");
		if ($loginid == '') return;
		
		// update the user login info.
		$query = "UPDATE {$this->table_name} SET last_activity = ? WHERE login_id = ?";
		$params = array($this->db->formatDate($time, true), $loginid);
		$result = $adb->pquery($query,$params);
		
		return $result;
	}
	
	/**
	 * Set as expired the open sessions older than $expMinutes
	 * If the username is provided, only sessions for that user will be checked.
	 */
	public function set_expired($expMinutes = 30, $username = '', $type = '') {
		global $adb;
		
		$params = array('Expired', 'Signed in', date('Y-m-d H:i:s', time() - $expMinutes*60));
		$sql = "UPDATE {$this->table_name} SET status = ? WHERE status = ? AND last_activity IS NOT NULL AND last_activity != '' AND last_activity < ?";
		
		if (!empty($username)) {
			$sql .= " AND user_name = ?";
			$params[] = $username;
		}
		
		if (!empty($type)) {
			$sql .= " AND type = ?";
			$params[] = $type;
		}
		
		$adb->pquery($sql, $params);
	}
	
	/**
	 * Get the login time of the current session
	 */
	public function getLoginTime($username, $type = 'auto') {
		global $adb;
		
		if (!VteSession::isEmpty('login_time')) return VteSession::get('login_time'); 

		$time = date('Y-m-d H:i:s');
		if ($type == 'auto') $type = $this->getLoginType();
		
		$wheres = array();
		$params = array();
		
		$wheres[] = "user_name = ? AND type = ? AND status = ?";
		$params[] = $username;
		$params[] = $type;
		$params[] = 'Signed in';
		
		$deviceid = $sessionid = '';
		if ($type == 'plugin') {
			return null;	// plugins are not logged
		} elseif ($type == 'app') {
			$deviceid = $this->getDeviceId();
			$wheres[] = 'deviceid = ?';
			$params[] = $deviceid;
		} else {
			$sessionid = session_id();
			$wheres[] = 'sessionid = ?';
			$params[] = $sessionid;
		}
		
		$logid_qry = "SELECT MAX(login_time) as login_time FROM {$this->table_name} WHERE ".implode(' AND ', $wheres);
		$result = $adb->pquery($logid_qry, $params);
		$ltime = $adb->query_result_no_html($result,0,"login_time");
		
		// and set it in the session if missing
		if (VteSession::isEmpty('login_time') && !empty($ltime)) VteSession::set('login_time', $ltime);
		
		return $ltime;
	}
	
	public function countOtherOpenSessions($username, $type = 'auto', $afterTime = null) {
		global $adb;
		
		if ($type == 'auto') $type = $this->getLoginType();
		
		$sql = "SELECT COUNT(*) AS cnt FROM {$this->table_name} WHERE user_name = ? AND type = ? AND status IN (?)";
		$params = array($username, $type, 'Signed in');
		
		$deviceid = $sessionid = '';
		if ($type == 'plugin') {
			return 0;	// plugins are ignored
		} elseif ($type == 'app') {
			$deviceid = $this->getDeviceId();
			$sql .= ' AND deviceid != ?';
			$params[] = $deviceid;
		} else {
			$sessionid = session_id();
			$sql .= ' AND sessionid != ?';
			$params[] = $sessionid;
		}
		
		if (!empty($afterTime)) {
			$sql .= ' AND login_time > ?';
			$params[] = $afterTime;
		}
		
		$count = 0;
		$res = $adb->pquery($sql, $params);
		if ($res) {
			$count = intval($adb->query_result_no_html($res, 0, 'cnt'));
		}
		
		return $count;
	}


}