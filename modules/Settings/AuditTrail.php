<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@164355 crmv@202301 */

/** This class is used to track all the operation{s done by the particular User while using crm. 
 *  It is intended to be called when the check for audit trail is enabled.
 **/
class AuditTrail {
		
	var $default_order_by = "actiondate";
	var $default_sort_order = 'DESC';
	
	/**
	 * List of module-action-subaction-file to skip
	 */
	public $skipActions = array(
		'web' => [
			['module' => 'Calendar', 'action' => 'ActivityReminderCallbackAjax'],
			['module' => 'Calendar', 'file' => 'ActivityReminderCallbackAjax'],
			['module' => 'Accounts', 'action' => 'AddressChange'],
			['module' => 'PBXManager', 'action' => 'TraceIncomingCall'],
			['module' => 'PBXManager', 'file' => 'TraceIncomingCall'],
			['module' => 'Home', 'action' => 'HomeWidgetBlockList'],
			['module' => 'ModComments', 'action' => 'ModCommentsWidgetHandler'],
			['module' => 'Myfiles', 'action' => 'HomeBlock'],
			['module' => 'Utilities', 'action' => 'CheckSession'],
			['module' => 'Utilities', 'action' => 'CheckDuplicate'],
			['module' => 'ChangeLog', 'action' => 'SaveEditViewEtag'],
			['module' => 'Processes', 'action' => 'DetailViewAjax', 'subaction' => 'CHECKDYNAFORMPOPUP'],
			['module' => 'SDK'],
			['action' => 'chat'],
		],
		'app' => [
			
			/*['action' => 'GetChanges'],
			['action' => 'GetOverrideFile'],
			*/
			// make with a simple array, it's faster
		],
		'app_actions' => [
			'GetComments', 'GetNotifications', 'GetMessagesCount', 'GetProcessesCount', 'GetTodos',
			'GetChanges', 'GetOfflineData'
		],
		
	);

	public function __construct() {

	}
	
	/**
	 * Enable the auditing
	 */
	public function enable() {
		$VP = VTEProperties::getInstance();
		$VP->setProperty('security.audit.enabled', 1);
	}
	
	/**
	 * Disable the auditing
	 */
	public function disable() {
		$VP = VTEProperties::getInstance();
		$VP->setProperty('security.audit.enabled', 0);
	}
	
	public function isEnabled() {
		$VP = VTEProperties::getInstance();
		$enabled = !!$VP->getProperty('security.audit.enabled');
		return $enabled;
	}
	
	public function processIndex(Array $request) {
		global $current_user;
		
		if (!$this->isEnabled()) return;
		
		$module = (isset($request['module'])) ? vtlib_purify($request['module']) : "";
		$action = (isset($request['action'])) ? vtlib_purify($request['action']) : "";
		$file = (isset($request['file'])) ? vtlib_purify($request['file']) : "";
		$record = (isset($request['record'])) ? vtlib_purify($request['record']) : "";
		if (!$record) {
			$record = (isset($request['reportid'])) ? vtlib_purify($request['reportid']) : "";
		}
		$subaction = (isset($request['ajxaction'])) ? vtlib_purify($request['ajxaction']) : "";
		if (!$subaction) {
			$subaction = (isset($request['subaction'])) ? vtlib_purify($request['subaction']) : "";
		}
		
		if($action == $module.'Ajax') {
			$audit_action = $file;
		} else {
			$audit_action = $action;
		}
		
		if ($action == 'Popup') {
			$record = $request['forrecord'];
			$subaction = $request['forfield'];
		}
		
		if ($audit_action == 'EditReportAjax' && $subaction) {
			$audit_action = $subaction;
			$subaction = '';
		}

		if($record == '') {
			$auditrecord = '';
		} else {
			$auditrecord = $record;
		}
		
		$params = [
			'userid' => $current_user->id,
			'source' => 'web',
			'module' => $module,
			'action' => $audit_action,
			'subaction' => $subaction,
			'recordid' => $auditrecord,
		];
		
		// Skip audit trial log for special request types
		foreach ($this->skipActions['web'] as $skipAct) {
			// take only needed parameters
			$par = array_intersect_key($params, $skipAct);
			
			ksort($par);
			ksort($skipAct);
			if ($par === $skipAct) return; // skip audit
		}
		
		return $this->insertAudit($params);
	}
	
	public function processAuthenticate($user, $source = 'web') {
		
		if (!$this->isEnabled()) return;
		
		$params = [
			'userid' => $user->id,
			'source' => $source,
			'module' => 'Users',
			'action' => 'Authenticate',
			'recordid' => '',
		];
		
		return $this->insertAudit($params);
		
	}
	
	public function processTouchWS(Array $request) {
		global $current_user;
		
		if (!$this->isEnabled()) return;
		
		$wsname = substr($request['wsname'], 0, 64);
		
		if ($wsname == 'MultiCall') {
			$subcalls = Zend_Json::decode($request['wslist']);
			if (is_array($subcalls)) {
				foreach ($subcalls as $wscall) {
					$newreq = $request;
					$newreq = array_replace($newreq, $wscall['wsparams']);
					$newreq['wsname'] = $wscall['wsname'];
					$this->processTouchWS($newreq);
				}
			}
			return;
		}
		
		$module = $request['module'];
		$audit_action = $wsname;
		$subaction = '';
		$auditrecord = $request['record'] ?: $request['crmid'];
		
		if (in_array($audit_action, $this->skipActions['app_actions'])) return;
		
		if ($audit_action == 'ConvertLead') $subaction = $request['mode'];
		if ($audit_action == 'TrackingList') $subaction = $request['method'];
		
		$params = [
			'userid' => $current_user->id,
			'source' => 'app',
			'module' => $module,
			'action' => $audit_action,
			'subaction' => $subaction,
			'recordid' => $auditrecord,
		];
		
		// Skip audit trial log for special request types
		foreach ($this->skipActions['app'] as $skipAct) {
			// take only needed parameters
			$par = array_intersect_key($params, $skipAct);
			
			ksort($par);
			ksort($skipAct);
			if ($par === $skipAct) return; // skip audit
		}
		
		return $this->insertAudit($params);
	}
	
	public function processWS($operation, Array $request, $user) {
	
		if (!$this->isEnabled()) return;
		
		$module = $request['elementType'] ?: $request['module'];
		$audit_action = $operation;
		$extra = null;
		$subaction = '';
		$auditrecord = $request['id'];
		
		if (!$auditrecord && $request['element']) {
			$auditrecord = $request['element']['id'];
		}
		
		if ($auditrecord && strpos($auditrecord, 'x') !== false) {
			list($modid, $auditrecord) = explode('x', $auditrecord);
		}
		
		if (empty($module) && !empty($auditrecord)) {
			$module = getSalesEntityType($auditrecord);
		}

		if ($operation == 'query') {
			$extra = $request['query'];
		}
		
		$params = [
			'userid' => $user ? $user->id : null,
			'source' => 'ws',
			'module' => $module,
			'action' => $audit_action,
			'subaction' => $subaction,
			'recordid' => $auditrecord,
			'extra' => $extra,
		];
		
		return $this->insertAudit($params);
	}
	
	public function insertAudit($params) {
		global $adb, $table_prefix;
		
		$date_var = $adb->formatDate(date('Y-m-d H:i:s'), true);
		$auditid = $adb->getUniqueID($table_prefix.'_audit_trial');
		$reqid = RequestHandler::getId();
		if (php_sapi_name() == 'cli') {
			$ip = null;
		} else {
			$ip = getIP();
		}
		$extra = $params['extra'] ?: null;
		if (is_array($extra)) $extra = Zend_Json::encode($extra);

 	    $sqlparams = array($auditid, $reqid, $ip, $params['userid'], $params['source'], $params['module'], $params['action'], $params['subaction'], $params['recordid'],$date_var, $extra);
 	    $query = "INSERT INTO {$table_prefix}_audit_trial (auditid, request_id, ip_address, userid, source, module, action, subaction, recordid, actiondate, extra) VALUES (".generateQuestionMarks($sqlparams).")";
		
		$res = $adb->pquery($query, $sqlparams);
		return !!$res;
	}
	
	/**
	 * Count the entries in the audit table, optionally filtering by user
	 */
	/*public function countEntries($userid = null) {
		global $adb, $table_prefix;
		
		$params = array();
		$sql = "SELECT COUNT(*) AS cnt FROM {$table_prefix}_audit_trial";
		
		if ($userid > 0) {
			$sql .= " WHERE userid = ?";
			$params[] = $userid;
		}
		
		$res = $adb->pquery($sql, $params);
		$count = intval($adb->query_result_no_html($res, 0, 'cnt'));
		
		return $count;
	}*/
	
	/**
	 * @deprecated
	 */
	function getAuditTrailHeader() {
		logDeprecated("The method AuditTrail::getAuditTrailHeader has been replaced by AuditTrailExtractor::getHeaderLabels");
		require_once('modules/Settings/AuditTrail/Extractor.php');
		$ATE = new AuditTrailExtractor();
		return $ATE->getHeaderLabels();
	}

	/**
	 * @deprecated
	 */
	function getAuditTrailEntries($userid, $navigation_array, $moreInfo = false) {
		logDeprecated("The method AuditTrail::getAuditTrailEntries has been replaced by AuditTrailExtractor::getListViewData");
		require_once('modules/Settings/AuditTrail/Extractor.php');
		
		$config = array('userid' => $userid);
		$ATE = new AuditTrailExtractor($config);
		
		$history = $ATE->extract();
		$showRows = $navigation_array['end_val'] - $navigation_array['start'] + 1;
		return $ATE->getListViewData($history, $navigation_array['start'], $showRows);
	}
	
	/**
	 * Removes old entries from database and save them to files
	 */
	public function cleanOldEntries() {
		global $adb, $table_prefix;
		
		if (!$this->isEnabled()) return;
		
		$VP = VTEProperties::getInstance();
		$interval = intval($VP->getProperty('security.audit.log_retention_time'));

		if ($interval == 0) return;
		
		$outdir = 'logs/AuditTrail/';
		if (!is_dir($outdir)) mkdir($outdir, 0755);
		
		$now = time();
		
		// get first day of month and take previous month
		$limitDate = date('Y-m-01', strtotime("- $interval month", $now)).' 00:00:00';
		
		$users = [];
		$res = $adb->pquery("SELECT userid, COUNT(*) as cnt FROM {$table_prefix}_audit_trial WHERE actiondate < ? GROUP BY userid", array($limitDate));
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$users[$row['userid']] = $row['cnt'];
		}

		$lastDate = substr($limitDate, 0, 7);

		// now extract every month for this user
		foreach ($users as $userid => $cnt) {
			// get oldest date for user
			$res = $adb->limitpQuery("SELECT actiondate FROM {$table_prefix}_audit_trial WHERE userid = ? ORDER BY actiondate ASC", 0, 1, array($userid));
			$start = $adb->query_result_no_html($res, 0, 'actiondate');
			$startDate = substr($start, 0, 7);
			while ($startDate < $lastDate) {
				$nextDate = date('Y-m', strtotime('+1 month', strtotime($startDate.'-01 12:00:00')));
				//echo "EXTRACT USER $userid, $startDate -> $nextDate\n";
				
				$filename = "audit_".str_replace('-', '', $startDate)."_{$userid}.csv";
				$path = $outdir.$filename;
				
				$params = array($userid, $startDate.'-01 00:00:00', $nextDate.'-01 00:00:00');
				$res = $adb->pquery(
					"SELECT actiondate, userid, module, action, recordid 
					FROM {$table_prefix}_audit_trial 
					WHERE userid = ? AND actiondate >= ? AND actiondate < ? 
					ORDER BY auditid", 
					$params
				);
				if ($res && $adb->num_rows($res) > 0) {
					
					$h = fopen($path, 'w');
					if ($h === false) {
						echo "Unable to open file $path for writing, skipping user\n";
						break;
					}
					$first = true;
					while ($row = $adb->fetchByAssoc($res, -1, false)) {
						// write header
						if ($first) {
							$r = fputcsv($h, array_keys($row));
							if ($r === false) {
								echo "Unable to open write to file $path for writing, skipping user\n";
								break 2;
							}
							$first = false;
						}
						// write rows
						$r = fputcsv($h, $row);
						if ($r === false) {
							echo "Unable to open write to file $path for writing, skipping user\n";
							break 2;
						}
					}
					fclose($h);
					
					// ok, file written, compress it
					$r = LogUtils::gzCompressFile($path);
					if ($r) {
						unlink($path);
					} else {
						echo "Unable to compress audit file $path, skipping user\n";
						break;
					}
					
					// all well, delete rows from db
					$adb->pquery("DELETE FROM {$table_prefix}_audit_trial WHERE userid = ? AND actiondate >= ? AND actiondate < ?", $params);
				}
				
				$startDate = $nextDate;
			}
		}
		
	}
}