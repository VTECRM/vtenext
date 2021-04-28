<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146671 crmv@147433 crmv@150751 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
require_once('modules/Settings/ExtWSConfig/ExtWSUtils.php');
require_once('modules/Settings/ExtWSConfig/ExtWS.php');
require_once(dirname(__FILE__).'/Base.php');

class PMActionCallExtWS extends PMActionBase {
	
	public function save(&$request) {
		// alter the request to make order in the additional fields
		
		$EWSU = new ExtWSUtils();
		
		$id = intval($request['id']);
		$elementid = vtlib_purify($request['elementid']);
		$title = vtlib_purify($request['meta_action']['action_title']);
		$action_type = vtlib_purify($request['meta_action']['action_type']);
		
		$form = $request['meta_action'];
		$extwsid = intval($form['extwsid']);
		
		unset($form['param_name'], $form['param_value']);
		unset($form['result_name'], $form['result_value']);
		
		if ($form['auth_username'] == '') {
			unset($form['auth_username']);
			unset($form['auth_password']);
		}
		
		$wsinfo = $EWSU->getWSInfo($extwsid);
		
		$stdParams = self::array_pluck($wsinfo['params'], 'name');
		$stdResults = self::array_pluck($wsinfo['results'], 'name');
		
		$params = $results = array();
		$extra_params = array();
		$extra_results = array();
		
		foreach ($form as $rkey => $rval) {
			$matches = array();
			if (preg_match('/^param_name_([0-9]+)$/', $rkey, $matches)) {
				if ($rval != '') {
					$val = $form['param_value_'.$matches[1]];
					$extra_params[$rval] = $val;
				}
				unset($form[$rkey]);
				unset($form['param_value_'.$matches[1]]);
			} elseif (preg_match('/^result_name_([0-9]+)$/', $rkey, $matches)) {
				$val = $form['result_value_'.$matches[1]];
				if ($rval != '' && $val != '') {
					$extra_results[$rval] = $val;
				}
				unset($form[$rkey]);
				unset($form['result_value_'.$matches[1]]);
			} elseif (preg_match('/^param_([0-9]+)$/', $rkey, $matches)) {
				$ridx = $matches[1];
				$pname = $form['paramname_'.$ridx];
				$pidx = array_search($pname, $stdParams);
				if ($pidx !== false && array_key_exists($pidx, $wsinfo['params'])) {
					if ($wsinfo['params'][$pidx]['value'] === $rval) $rval = '!DEFAULT!';
					$params[$wsinfo['params'][$pidx]['name']] = $rval;
				}
				unset($form[$rkey]);
				unset($form['paramname_'.$ridx]);
			} elseif (preg_match('/^result_([0-9]+)$/', $rkey, $matches)) {
				$ridx = $matches[1];
				$pname = $form['resultname_'.$ridx];
				$pidx = array_search($pname, $stdResults);
				if (array_key_exists($pidx, $wsinfo['results'])) {
					if ($wsinfo['results'][$pidx]['value'] === $rval) $rval = '!DEFAULT!';
					$results[$wsinfo['results'][$pidx]['name']] = $rval;
				}
				unset($form[$rkey]);
				unset($form['resultname_'.$ridx]);
			}
		}
		
		$form['params'] = $params;
		$form['results'] = $results;
		$form['extra_params'] = $extra_params;
		$form['extra_results'] = $extra_results;
		$request['meta_action'] = $form;
		
		$r = parent::save($request);
		
		$this->setMeta($id, $elementid, $title, $action_type, $extwsid);
		
		return $r;
	}
	
	protected function getMetaId($processmaker, $elementid, $running_process='') {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		(!empty($running_process)) ? $xml_version = $PMUtils->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		if (!empty($xml_version)) {
			$query = "SELECT id FROM {$table_prefix}_process_extws_meta_vh WHERE versionid = ? and processid = ? and elementid = ?";
			$params = array($xml_version, $processmaker, $elementid);
		} else {
			$query = "SELECT id FROM {$table_prefix}_process_extws_meta WHERE processid = ? and elementid = ?";
			$params = array($processmaker, $elementid);
		}
		$metaid = false;
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			$metaid = $adb->query_result_no_html($result, 0, 'id');
		}
		return $metaid;
	}
	
	protected function setMeta($id,$elementid,$text,$type, $extwsid) {
		global $adb, $table_prefix, $default_charset;
		
		$text = html_entity_decode($text,ENT_QUOTES,$default_charset);
		
		$result = $adb->pquery("select id from {$table_prefix}_process_extws_meta where processid = ? and elementid = ?", array($id,$elementid));
		if ($result && $adb->num_rows($result) > 0) {
			$metarecid = $adb->query_result_no_html($result,0,'id');
			$adb->pquery("update {$table_prefix}_process_extws_meta set text = ?, type = ?, extwsid = ? where id = ? and processid = ? and elementid = ?", array($text,$type,$extwsid,$metarecid,$id,$elementid));
		} else {
			$metarecid = $adb->getUniqueID("{$table_prefix}_processmaker_metarec");
			$adb->pquery("insert into {$table_prefix}_process_extws_meta (id, processid, elementid, text, type, extwsid) values (?,?,?,?,?,?)", array($metarecid,$id,$elementid,$text,$type,$extwsid));
		}
	}
	
	protected function setResult($running_process, $metaid, $results = array()) {
		global $adb, $table_prefix;
		
		$results = Zend_Json::encode($results);
		
		$result = $adb->pquery("select running_process from {$table_prefix}_process_extws where running_process = ? and metaid = ?", array($running_process,$metaid));
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("update {$table_prefix}_process_extws set results = ?, done = ? where running_process = ? and metaid = ?", array($results,1,$running_process,$metaid));
		} else {
			$adb->pquery("insert into {$table_prefix}_process_extws (running_process, metaid, results, done) values (?,?,?,?)", array($running_process,$metaid, $results, 1));
		}
	}
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$EWSU = new ExtWSUtils();
		
		// get names of active webservices
		$fullList = $EWSU->getList(true);
		
		$list = array();
		foreach ($fullList as $ws) {
			$list[$ws['extwsid']] = $ws['wsname'];
		}
		$smarty->assign("WSLIST", $list);
		
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$record_involved = $metadata_action['record_involved'];
			}
			$smarty->assign('METADATA', $metadata_action);
		}
		
		$involvedRecords = $PMUtils->getRecordsInvolved($id,true);
		if (!empty($involvedRecords)) {
			$smarty->assign('INVOLVED_RECORDS', Zend_Json::encode($involvedRecords));
		}
		
		//crmv@106857
		$otherOptions = array();
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$otherOptions = $processDynaFormObj->getFieldsOptions($id,true);
		$PMUtils->getAllTableFieldsOptions($id, $otherOptions);
		$PMUtils->getAllPBlockFieldsOptions($id, $otherOptions); // crmv@195745
		$smarty->assign("OTHER_OPTIONS", Zend_Json::encode($otherOptions));
		//crmv@106857e
		
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
		
		$elementsActors = $PMUtils->getElementsActors($id,true);
		$smarty->assign('ELEMENTS_ACTORS', Zend_Json::encode($elementsActors));
		
	}
	
	function execute($engine,$actionid) {
		$action = $engine->vte_metadata['actions'][$actionid];
		$extwsid = intval($action['extwsid']);
		
		$engine->log("Action CallExtWS","action $actionid - {$action['action_title']}");
		
		if (!$extwsid) {
			$engine->log("Action CallExtWS","action $actionid FAILED - No ExtWS ID provided");
			return false;
		}
		
		$EWSU = new ExtWSUtils();
		$EWS = new ExtWS();
		
		// get the default data
		$wsinfo = $EWSU->getWSInfo($extwsid);
		
		if (!$wsinfo['active']) {
			$engine->log("Action CallExtWS","action $actionid FAILED - ExtWS is disabled");
			return false;
		}
		
		$paramNames = self::array_pluck($wsinfo['params'] ?: array(), 'name');
		$paramNames = array_flip($paramNames);
		
		// now merge the configuration
		
		// authentication
		if ($action['auth_username'] && $action['auth_username'] != '!DEFAULT!') {
			if ($action['auth_username'] == '!DONTUSE!') {
				unset($wsinfo['authinfo']);
			}
			$username = $engine->replaceTags('auth_username', $action['auth_username'], array(), array(), $actionid);
			$wsinfo['authinfo']['username'] = $username;
			if($action['auth_password'] == '!DONTUSE!') {
				$wsinfo['authinfo']['password'] = '';
			} elseif ($action['auth_password'] && $action['auth_password'] != '!DEFAULT!') {
				$password = $engine->replaceTags('auth_password', $action['auth_password'], array(), array(), $actionid);
				$wsinfo['authinfo']['password'] = $password;
			}
		}
		
		// standard parameters
		if (is_array($action['params'])) {
			foreach ($action['params'] as $param => $value) {
				if (array_key_exists($param, $paramNames) && $value != '!DEFAULT!') {
					// override of a standard parameter
					$idx = $paramNames[$param];
					if ($value == '!DONTUSE!') {
						unset($wsinfo['params'][$idx]);
						unset($paramNames[$param]);
					} else {
						$value = $engine->replaceTags($wsinfo['params'][$idx]['name'], $value, array(), array(), $actionid);
						$wsinfo['params'][$idx]['value'] = $value;
					}
				}
			}
		}
		
		// extra parameters
		if (is_array($action['extra_params'])) {
			foreach ($action['extra_params'] as $param => $value) {
				$finalParam = $engine->replaceTags('extra_param_name', $param, array(), array(), $actionid);
				if (!array_key_exists($finalParam, $paramNames)) {
					// add a new parameter
					$wsinfo['params'][] = array(
						'name' => $finalParam,
						'value' => $engine->replaceTags('extra_param_value', $value, array(), array(), $actionid),
					);
				}
			}
		}
		
		// crmv@190014
		if (isset($action['rawbody']) && $action['rawbody'] !== '') {
			$wsinfo['rawbody'] = $engine->replaceTags('rawbody', $action['rawbody'], array(), array(), $actionid);
		}
		// crmv@190014e
		
		$metaid = $this->getMetaId($engine->processid, $engine->elementid, $engine->running_process);
		
		// call the ws
		try {
			$result = $EWS->call($wsinfo);
		} catch (Exception $e) {
			$engine->log("Action CallExtWS","action $actionid FAILED - Unable to execute the call: ".$e->getMessage());
			//crmv@180651 store the result in the table
			$this->setResult($engine->running_process, $metaid, array(
				'success' => false,
				'code' => 500,
				'message' => $e->getMessage(),
				'headers' => array(),
				'body' => array()
			));
			//crmv@180651e
			return false;
		}
		
		// store the result in the table
		$this->setResult($engine->running_process, $metaid, $result); //crmv@OPER10174
		
		$engine->log("Action CallExtWS","action $actionid SUCCESS");
		
		$engine->logElement($engine->elementid, array(
			'action_type'=>$action['action_type'],
			'action_title'=>$action['action_title'],
			// result code
		));
	}
	
	protected static function array_pluck($array, $key) {
		return array_map(function($v) use ($key) {
			return is_object($v) ? $v->$key : $v[$key];
		}, $array);
	}
}