<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@202301 */


class AuditTrailExtractor {

	protected $config;
	
	public $outputFields = array(
		// key => [info...]
		'actiondate' 	=> array('label' => 'Date & Time', 'type' => 'datetime'),
		'ip' 			=> array('label' => 'LBL_IP_ADDRESS'),
		'module_name' 	=> array('label' => 'LBL_MODULE'),
		'action_name' 	=> array('label' => 'LBL_ACTION'),
		'link' 			=> array('label' => 'LBL_LINK_ACTION', 'type' => 'link', 'display' => 'link_name'),
		'details' 		=> array('label' => 'LBL_SHOW_DETAILS', 'type' => 'details'),
	);
	
	protected $userid;
	
	/*
	* Format of history objects:
	* actiondate: date of the entry
	* ip: ip address (if available)
	* module: module name for the action (if applicable)
	* module_name: translated module name
	* action: raw name of the action
	* action_name: user friendly name for the action
	* record_info.id: id of the involved record
	* record_info.display_name: name of the involved record
	* link: link to the record/entry (if applicable)
	* details: details about the entry
	*/
	
	public function __construct($config) {
		$this->config = $config;
		if ($config['userid']) {
			// get the username
			$this->config['username'] = getUserName($config['userid'], false);
		} elseif ($config['username']) {
			// get the userid
			$this->config['userid'] = $this->getUserId($config['username']);
		}
	}

	public function extract() {
	
		$username = $this->config['username'];
		$from = $this->config['from'];
		$to = $this->config['to'];
		
		if ($from && strlen($from) < 12) $from .= ' 00:00:00';
		if ($to && strlen($to) < 12) $to .= ' 23:59:59';
		
		$userid = $this->getUserId($username);
		
		if (!$userid) return $this->error('User not found');
		
		$this->userid = $userid;

		$auditInfo = $this->getAuditTrialInfo($userid, $from, $to);
		$loginInfo = $this->getLoginInfo($username, $from, $to);
		$changelogInfo = $this->getChangelogInfo($userid, $from, $to);
		$checkLoginInfo = $this->getCheckLoginInfo($userid, $from, $to);
		
		$history = $this->mergeLists($auditInfo, $loginInfo);
		$history = $this->mergeLists($history, $checkLoginInfo);
		$history = $this->mergeLists($history, $changelogInfo);
		
		// add links
		foreach ($history as &$hist) {
			$this->addLink($hist);
		}
		
		foreach ($history as &$hist) {
			$this->addActionName($hist);
		}
		
		// switch login with home view
		
		$modActOld = $history[0]['module'].$history[0]['action'];
		for ($i=1; $i<count($history); ++$i) {
			$modActNew = $history[$i]['module'].$history[$i]['action'];
			if ($modActOld == 'Homeindex' && $modActNew == 'Login' && $history[$i-1]['actiondate'] == $history[$i]['actiondate']) {
				// do the switch
				$t = $history[$i-1];
				$history[$i-1] = $history[$i];
				$history[$i] = $t;
			}
			$modActOld = $modActNew;
		}
		
		return $history;
	}
	
	public function getHeaderLabels() {
		$header = array();
		foreach ($this->outputFields as $fld) {
			$header[] = getTranslatedString($fld['label'], 'APP_STRINGS');
		}
		return $header;
	}
	
	public function getListViewData($history, $start = 0, $pageSize = 20) {
		$history = array_slice($history, $start, $pageSize);

		$cleanKeys = array_fill_keys(array_keys($this->outputFields), '');
		$history = array_map(function($el) use ($cleanKeys) {
			$el['link'] = "<a href=\"{$el['link']}\">{$el['link_name']}</a>";
			$el['details'] = $el['details_html'];
			return array_replace($cleanKeys, array_intersect_key($el, $cleanKeys));
		}, $history);

		return $history;
	}
	
	public function displayHtml($history) {
	
		$username = $this->config['username'];
		$from = $this->config['from'];
		$to = $this->config['to'];
		
		$html = "<html><body><h1>Estrazione operazioni utente {$username} ({$from} - {$to})</h1><br>\n";
		$html .= "<table border=1 width=\"100%\" cellpadding=3>\n";
		$html .= "<thead><tr>";
		foreach ($this->outputFields as $key=>$info) {
			$html .= "<th>{$info['label']}</th>";
		}
		$html .= "<tbody>";
		
		foreach ($history as $hist) {
			$html .= "<tr>";
			foreach ($this->outputFields as $key=>$info) {
				$val = $hist[$key];
				if ($info['type'] == 'link') {
					$linkname = $hist[$info['display']];
					$val = "<a href=\"{$val}\">$linkname</a>";
				} elseif ($info['type'] == 'details') {
					$val = $info[$key.'_html'];
				}
				$nowrap = '';
				if (in_array($key, array('actiondate', 'ip', 'module_name', 'action_name'))) $nowrap = 'nowrap';
				$html .= "<td $nowrap>{$val}</td>";
			}
			$html .= "</tr>\n";
		}
		$html .= "</tbody>";
		$html .= "</table>\n";
		
		$html .= "</body></html>\n";
		echo $html;
		//return $html;
	}
	
	/**
	 * Export the history in Excel format
	 * @param Array $history The history extracted from the extract function
	 * @param String $format "csv"/"xlsx"/"auto"
	 */
	public function exportXls($history, $format = 'auto') {
		
		$username = $this->config['username'];
		$userid = $this->config['userid'];
		$from = $this->config['from'];
		$to = $this->config['to'];
		
		$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$objPHPExcel->getProperties()
			->setCreator("VTE CRM")
			->setLastModifiedBy("VTE CRM")
			->setTitle("Audit Trial ".date('Y-m-d H:i'));
		
		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
		$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
		
		$sheet0 = $objPHPExcel->getActiveSheet();
		
		$xlsStyle1 = new \PhpOffice\PhpSpreadsheet\Style\Style();

		$xlsStyle1->applyFromArray(
			array('font' => array(
				'name' => 'Arial',
				'bold' => true,
				'size' => 12,
				//'color' => array( 'rgb' => '0000FF' )
			),
		));
		
		$sheet0->setTitle(\PhpOffice\PhpSpreadsheet\Shared\StringHelper::Substring(getTranslatedString('LBL_AUDIT_TRAIL','Settings'),0,29));
		$sheet0->duplicateStyle($xlsStyle1, 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->outputFields)).'1');
		
		// add header
		$colcount = 0;
		$rowcount = 1;
		foreach ($this->outputFields as $key=>$info) {
			$sheet0->setCellValueByColumnAndRow($colcount++, $rowcount, $info['label']);
		}
		
		// add rows
		foreach ($history as $hist) {
			++$rowcount;
			$colcount = 0;
			foreach ($this->outputFields as $key=>$info) {
				$val = $hist[$key];
				if ($info['type'] == 'details') {
					if ($val) {
						$val = Zend_Json::encode($val);
					} else {
						$val = '';
					}
				}
				
				if ($info['type'] == 'datetime') {
					$datevalue = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($val);
					$dateFormat = 'yyyy-mm-dd h:mm:ss';
					$sheet0->setCellValueByColumnAndRow($colcount, $rowcount, $datevalue);
					$sheet0->getStyleBycolumnAndRow($colcount, $rowcount)->getNumberFormat()->setFormatCode($dateFormat);
				} elseif ($info['type'] == 'link') {
					$linkname = $hist[$info['display']];
					$sheet0->setCellValueExplicitByColumnAndRow($colcount, $rowcount, $linkname, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
					if ($val) {
						$hlink = new \PhpOffice\PhpSpreadsheet\Cell\Hyperlink($val);
						$cell = $sheet0->getCellByColumnAndRow($colcount, $rowcount);
						$cell->setHyperlink($hlink);
					}
				} else {
					$sheet0->setCellValueByColumnAndRow($colcount, $rowcount, $val);
				}
				++$colcount;
			}
		}

		// get user info
		$user = CRMEntity::getInstance('Users');
		$user->retrieve_entity_info_no_html($userid, 'Users');
		$fname = preg_replace('/[^a-z0-9_]/i', '', $user->first_name);
		$lname = preg_replace('/[^a-z0-9_]/i', '', $user->last_name);
		
		if ($format == 'auto') $format = (count($history) > 80000 ? 'csv' : 'xlsx');

		if ($format == 'xlsx') {
			$excel_type = 'Xlsx';
			$app_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		} elseif ($format == 'csv') {
			$excel_type = 'CSV';
			$app_type = 'text/csv';
		}
		
		$filename = "Audit_{$lname}_{$fname}_{$userid}.".$format;
		
		// stream result
		$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, $excel_type);
		$objWriter->setPreCalculateFormulas(false);
		
		header("Content-Disposition:attachment;filename=\"{$filename}\"");
		header("Content-Type:$app_type;charset=UTF-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); // to disable cache
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: post-check=0, pre-check=0", false);
		
		$objWriter->save('php://output');
	}
	
	protected function getAuditTrialInfo($userid, $from = null, $to = null) {
		global $adb, $table_prefix;
		
		$params = array($userid);
		$wheres = array("userid = ?");
		
		if ($from) {
			$wheres[] = "actiondate >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "actiondate <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_audit_trial WHERE ".implode(' AND ', $wheres).' ORDER BY actiondate ASC';
		
		$list = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			// exclude some automatic stuff
			if ($row['module'] == 'Utilities' && $row['action'] == 'CheckSession') continue;
			if ($row['module'] == 'Utilities' && $row['action'] == 'Card') continue;
			if ($row['module'] == 'Utilities' && $row['action'] == 'CheckDuplicate') continue;
			if ($row['module'] == 'ChangeLog' && $row['action'] == 'SaveEditViewEtag') continue;
			if ($row['module'] == 'Home' && $row['action'] == 'HomeWidgetBlockList') continue;
			if ($row['module'] == 'Home' && $row['action'] == 'HeaderSearchMenu') continue;
			if ($row['module'] == 'Myfiles' && $row['action'] == 'HomeBlock') continue;
			if ($row['module'] == 'ModComments' && $row['action'] == 'ModCommentsWidgetHandler') continue;
			if ($row['module'] == 'Reports' && $row['action'] == 'CheckReportFilter') continue;
			if ($row['module'] == 'Charts' && $row['action'] == 'QuickCreate') continue;
			
			if ($row['module'] && $row['recordid']) {
				$row['record_info'] = $this->getRecordInfo($row['module'], $row['recordid'], $row['action']);
				if ($row['record_info']['customview_module']) {
					$row['module'] = $row['record_info']['customview_module'];
					$row['action'] = 'CustomViewSave';
				}
			}
			
			$row['module_name'] = getTranslatedString($row['module'], $row['module']);
			$row['ip'] = $row['ip_address'];
			$list[] = $row;
		}
		return $list;
	}
	
	protected function getLoginInfo($username, $from = null, $to = null) {
		global $adb, $table_prefix;
		
		$wheres = array("user_name = ?");
		$params = array($username, );
		
		if ($from) {
			$wheres[] = "login_time >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "login_time <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_loginhistory WHERE ".implode(' AND ', $wheres).' ORDER BY login_time ASC';
		
		$list = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$row['action'] = 'Login';
			$row['actiondate'] = $row['login_time'];
			$row['ip'] = $row['user_ip'];
			$list[] = $row;
		}
		
		// now add logouts/evictions
		
		$wheres = array("user_name = ?", 'status IN (?,?)');
		$params = array($username, 'Signed off', 'Evicted');
		
		if ($from) {
			$wheres[] = "logout_time >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "logout_time <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_loginhistory WHERE ".implode(' AND ', $wheres).' ORDER BY logout_time ASC';
		
		$list2 = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$row['action'] = $row['status'];
			$row['actiondate'] = $row['logout_time'];
			$row['ip'] = $row['user_ip'];
			$list2[] = $row;
		}
		
		return $this->mergeLists($list, $list2);
	}
	
	protected function getCheckLoginInfo($username, $from = null, $to = null) {
		global $adb, $table_prefix;
		
		$wheres = array("userid = ?");
		$params = array($username, );
		
		if ($from) {
			$wheres[] = "date_whitelist >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "date_whitelist <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_check_logins WHERE status = 'W' AND date_whitelist IS NOT NULL AND ".implode(' AND ', $wheres).' ORDER BY date_whitelist ASC';
		
		$list = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$row['action'] = 'Whitelist user';
			$row['actiondate'] = $row['date_whitelist'];
			$row['ip'] = $row['ip'];
			$list[] = $row;
		}
		
		// now log first attempt
		
		$wheres = array("userid = ?");
		$params = array($username, );
		
		if ($from) {
			$wheres[] = "first_attempt >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "first_attempt <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_check_logins WHERE ".implode(' AND ', $wheres).' ORDER BY first_attempt ASC';
		
		$list2 = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$row['action'] = 'First failed login';
			$row['actiondate'] = $row['first_attempt'];
			$row['ip'] = $row['ip'];
			$list2[] = $row;
		}
		
		// and last attempt
		
		$wheres = array("userid = ?");
		$params = array($username, );
		
		if ($from) {
			$wheres[] = "last_attempt >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "last_attempt <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_check_logins WHERE attempts > 1 AND ".implode(' AND ', $wheres).' ORDER BY last_attempt ASC';
		
		$list3 = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$row['action'] = 'Last failed login';
			$row['actiondate'] = $row['last_attempt'];
			$row['ip'] = $row['ip'];
			$list3[] = $row;
		}
		
		
		$list = $this->mergeLists($list, $list2);
		$list = $this->mergeLists($list, $list3);
		
		return $list;
	}
	
	// merge 2 already sorted lists into a new one, using actiondate as date field
	protected function mergeLists($list1, $list2, $field = 'actiondate') {
		$list = array();
		
		$c1 = count($list1);
		$c2 = count($list2);
		
		if ($c2 == 0) return $list1;
		if ($c1 == 0) return $list2;
		
		$i = $j = 0;
		do {
			$el1 = $list1[$i];
			$el2 = $list2[$j];
			if ($el1[$field] <= $el2[$field]) {
				$list[] = $el1;
				++$i;
			} else {
				$list[] = $el2;
				++$j;
			}
			if ($i == $c1) {
				// end of first list, copy the second one
				$list = array_merge($list, array_slice($list2, $j));
				break;
			}
			if ($j == $c2) {
				$list = array_merge($list, array_slice($list1, $i));
				break;
			}
		} while (true);
		
		return $list;
	}
	
	protected function getchangelogInfo($userid, $from = null, $to = null) {
		global $adb, $table_prefix;
		
		$params = array($userid);
		$wheres = array("user_id = ?");
		
		if ($from) {
			$wheres[] = "modified_date >= ?";
			$params[] = $from;
		}
		
		if ($to) {
			$wheres[] = "modified_date <= ?";
			$params[] = $to;
		}
		
		$sql = "SELECT * FROM {$table_prefix}_changelog WHERE ".implode(' AND ', $wheres).' ORDER BY modified_date ASC';
		
		$clogFocus = CRMEntity::getInstance('ChangeLog');
		
		$list = array();
		$res = $adb->pquery($sql, $params);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			if ($row['parent_id']) {
				$module = getSalesEntityType($row['parent_id']);
				$row['module'] = $module;
				$row['module_name'] = getTranslatedString($module, $module);
				$row['record_info'] = $this->getRecordInfo($module, $row['parent_id']);
			}
			$row['actiondate'] = $row['modified_date'];
			$row['action'] = 'Changelog';
			//$desc = json_decode($row['description'], true);
			$log_type= null;
			$row['details_html'] = $clogFocus->getFieldsTable($row['description'], $module, false, $log_type);
			$row['details'] = $clogFocus->getFieldsTable($row['description'], $module, true, $log_type);
			$row['action'] = 'CLOG_'.$log_type;
			$list[] = $row;
		}
		return $list;
	}
	
	protected function getRecordInfo($module, $crmid, $action = '') {
		global $site_URL;
		$info = array();
		
		if ($module == 'Reports') {
			$info['displayname'] = $this->getReportName($crmid);
			
		} elseif ($module == 'ChangeLog') {
		
		} elseif ($module == 'CustomView') {
			$cvModule = $this->getFilterModule($crmid);
			$cvFocus = CRMEntity::getInstance('CustomView', $cvModule);
			$viewinfo = $cvFocus->getCustomViewByCvid($crmid);
			$info['displayname'] = $viewinfo['viewname'];
			$info['customview_module'] = $cvModule;
		} elseif ($action == 'CustomView') {
			$cvFocus = CRMEntity::getInstance('CustomView', $module);
			$viewinfo = $cvFocus->getCustomViewByCvid($crmid);
			$info['displayname'] = $viewinfo['viewname'];
		} elseif ($module == 'PDFMaker') {
			// TODO
		} elseif (isModuleInstalled($module) && getTabid2($module) > 0) {
			$info['displayname'] = getEntityName($module, $crmid, true);
		}
		
		$info['id'] = $crmid;
		
		return $info;
	}
	
	protected function getFilterModule($cvid) {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT entitytype FROM {$table_prefix}_customview WHERE cvid = ?", array($cvid));
		return $adb->query_result_no_html($res, 0, 'entitytype');
	}
	
	protected function addLink(&$entry) {
		global $site_URL;
		
		$module = $entry['module'];
		$action = $entry['action'];
		$id = $entry['record_info']['id'];
		$name = $entry['record_info']['displayname'];
		
		
		if ($module == 'Reports') {
			if ($action == 'ListView' || $action == 'index') {
				$entry['link'] = $site_URL.'/index.php?module='.$module.'&action='.$action;
				$entry['link_name'] = 'Lista';
			} else {
				$entry['link'] = $site_URL.'/index.php?module=Reports&action=SaveAndRun&record='.$id;
				$entry['link_name'] = $name;
			}
		} elseif ($action == 'CustomViewSave') {
			$entry['link'] = $site_URL.'/index.php?module='.$module.'&action=CustomView&record='.$id;
			$entry['link_name'] = $name;
		} elseif ($action == 'CustomView') {
			$entry['link'] = $site_URL.'/index.php?module='.$module.'&action=CustomView&record='.$id;
			$entry['link_name'] = $name;
		} elseif ($id) {
			$entry['link'] = $site_URL.'/index.php?module='.$module.'&action=DetailView&record='.$id;
			$entry['link_name'] = $name;
		} elseif ($action == 'index' || $action == 'ListView') {
			$entry['link'] = $site_URL.'/index.php?module='.$module.'&action='.$action;
			$entry['link_name'] = 'Lista';
		}
		return $entry;
	}
	
	protected function addActionName(&$entry) {
		global $site_URL;
		
		$module = $entry['module'];
		$action = $entry['action'];
		$subaction = $entry['subaction'];
		
		// change some action names
		if ($module == 'Home' && ($action == 'index' || $action == 'ListView')) {
			$entry['action_name'] = 'View Home';
		} elseif ($action == 'index' || $action == 'ListView') {
			$entry['action_name'] = 'View Module List';
		} elseif ($action == 'HomeView') {
			$entry['action_name'] = 'View Home Module';
		} elseif ($module == 'Users' && $action == 'Authenticate') {
			$entry['action_name'] = 'Login via web';
		} elseif ($action == 'Login') {
			$entry['action_name'] = 'Login';
		} elseif ($action == 'Signed off') {
			$entry['action_name'] = 'Logout';
		} elseif ($action == 'Evicted') {
			$entry['action_name'] = 'Forced logout';
		} elseif ($action == 'DetailView') {
			$entry['action_name'] = 'Record opened';
		} elseif ($action == 'SaveAndRun') {
			$entry['action_name'] = 'Report executed';
		} elseif ($module == 'Reports' && $action == 'CreatePDF') {
			$entry['action_name'] = 'Export report in PDF';
		} elseif ($module == 'Reports' && $action == 'CreateXL') {
			$entry['action_name'] = 'Export report in Excel';
		} elseif ($module == 'Reports' && $action == 'EditReport') {
			$entry['action_name'] = 'Edit report';
		} elseif ($module == 'Reports' && $action == 'EditCluster') {
			$entry['action_name'] = 'Edit report (cluster)';
		} elseif ($module == 'Reports' && $action == 'SaveReport') {
			$entry['action_name'] = 'Report saved';
		} elseif ($action == 'ChartPreview') {
			$entry['action_name'] = 'Chart preview';
		} elseif ($action == 'CLOG_create') {
			$entry['action_name'] = 'Record created';
		} elseif ($action == 'CLOG_edit') {
			$entry['action_name'] = 'Record modified';
		} elseif ($action == 'CLOG_remove_relation') {
			$entry['action_name'] = 'Relation removed';
		} elseif ($action == 'CLOG_relation') {
			$entry['action_name'] = 'Relation added';
		} elseif ($module == 'CustomView' && $action == 'Save') {
			$entry['action_name'] = 'Filter saved';
		} elseif ($action == 'CustomViewSave') {
			$entry['action_name'] = 'Filter saved';
		} elseif ($action == 'CustomView') {
			$entry['action_name'] = 'Edit filter';
		} elseif ($action == 'EditView') {
			$entry['action_name'] = 'Edit record';
		} elseif ($action == 'DetailViewAjax' && $subaction != 'DETAILVIEW') {
			$entry['action_name'] = 'View Related list';
		} elseif ($action == 'DetailViewAjax' && $subaction == 'DETAILVIEW') {
			$entry['action_name'] = 'Record saved';
		} elseif ($action == 'Save') {
			$entry['action_name'] = 'Record saved';
		} else {
			$entry['action_name'] = $action;
		}
		
		
		return $entry;
	}
	
	protected function getReportName($reportid) {
		global $adb, $table_prefix;
	
		$rname = '';
		$res = $adb->pquery("select reportname from {$table_prefix}_report where reportid = ?", array($reportid));
		if ($res) {
			$rname = $adb->query_result_no_html($res, 0, 'reportname'); // crmv@183235
		}
		return $rname;
	}
	
	protected function getUserId($username) {
		global $adb, $table_prefix;
		$res = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE user_name = ?", array($username));
		return $adb->query_result_no_html($res, 0, 'id');
	}
	
	protected function error($text) {
		echo "ERROR: $text\n";
		return false;
	}
}