<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - fix vari */
/* crmv@33097 - fix vari */
/* crmv@71388 - file upload */
/* crmv@85213 - collision handling */

class TouchSaveRecord extends TouchWSClass {

	public $validateModule = true;
	public $useChangelog = true;
	
	protected $rawValues = array();

	function process(&$request) {
		global $currentModule, $touchInst, $touchUtils;

		// save the values as they arrive, without any validation, for customization
		$this->rawValues = Zend_Json::decode($request['values']);

		$module = $request['module'];
		$recordid = intval($request['record']);
		$values = vtlib_purify($request['values']);
		$postActions = Zend_Json::decode($request['post_save_actions']);

		$currentModule = $module;

		$returndata = null;
		$errormsg = '';

		$newRecord = Zend_Json::decode($values);
		if (empty($newRecord)) {
			return $this->error('Invalid values passed');
		}

		if ($recordid > 0) {
			// UPDATE
			$this->preSaveRecord($module, $recordid, 'update', $newRecord);
			
			// collision handling
			$coll = 0;
			if (!empty($touchInst->collision_strategy)) {
				$coll = $this->checkCollisions($touchInst->collision_strategy, $touchInst->collision_level, $module, $recordid, $newRecord);
			}
			if ($coll != 2) {
				$response = $this->updateRecord($module, $recordid, $newRecord, $returndata);
			} else {
				// there was a collision and I don't have to save the record
				$returndata['crmid'] = $recordid;
				$response = array('success' => true);
			}
			$returndata['collision_result'] = $coll;
		} else {
			// CREATE
			$tempCrmid = intval($newRecord['temp_crmid']);
			$this->preSaveRecord($module, $tempCrmid, 'create', $newRecord);
			$response = $this->createRecord($module, $tempCrmid, $newRecord, $returndata);
		}
		$returnok = ($response['success'] === true);

		if ($returnok) {
			// handles for post save customizations
			$this->postSaveRecord($returndata);
			// process post save actions
			if (is_array($postActions)) {
				$message = '';
				$r = $this->processPostActions($returndata, $postActions, $message);
				if (!$r) {
					$returnok = false;
					$errormsg = $message;
				}
			}
		} else {
			$errormsg = $response['error']->message;
		}

		return $touchInst->createOutput(array('result' => $returndata),$errormsg, $returnok);
	}
	
	/**
	 * Check for collisions. Returns:
	 * 0: no collisions
	 * 1: collision resolved, save the record
	 * 2: collision resolved, don't save the record
	 * 3: collision resolved, record merged
	 * 5: collision not resolved
	 * 10: no collision check possible (missing timestamps)
	 * 11: error retrieving record
	 */
	public function checkCollisions($strategy, $level, $module, $crmid, &$values) {
		global $touchInst, $touchUtils;
		
		$appTS = intval($values['timestamp']);
		$vteTS = $this->getRecordTs($module, $crmid);
		
		if ($level == 'field') {
			// TODO:
			// 1. retrieve old record, diff, and find the changed fields
			// 2. find last changelog with at least one of those fields changed and use that timestamp
			// 3. compare the ts
		}
		
		if ($appTS && $vteTS) {
			if ($appTS > $vteTS) {
				// no collisions
				return 0;
			} else {
				if ($strategy == 'App') {
					// do nothing, the app will overwrite the VTE
					return 1;
				} else {
					// VTE wins or last, which means the VTE in this case
					return 2;
				}
			}
		} else {
			return 10;
		}
	}
	
	// crmv@167773
	protected function setRecordTs($module, $crmid, $ts, $mode = 'update') {
		global $adb, $table_prefix;
		
		$date = date('Y-m-d H:i:s', $ts);
		
		$table = "{$table_prefix}_crmentity";
		$index = "crmid";
		
		// TODO: don't check for the name, but for $focus->tab_name
		// but this might degrade performance, check first!
		if ($module == 'Messages') {
			$table = $table_prefix.'_messages';
			$index = 'messagesid';
		}
		
		if ($mode == 'update') {
			$sql = "UPDATE $table SET modifiedtime = ? WHERE $index = ?";
			$params = array($date, $crmid);
		} elseif ($mode == 'create') {
			$sql = "UPDATE $table SET createdtime = ?, modifiedtime = ? WHERE $index = ?";
			$params = array($date, $date, $crmid);
		}
		
		$r = $adb->pquery($sql, $params);
	}
	// crmv@167773e
	
	protected function getRecordTs($module, $crmid) {
		global $adb, $table_prefix, $touchInst, $touchUtils;
		
		if ($module == 'Messages') {
			$ts = strtotime(getSingleFieldValue($table_prefix.'_messages', 'modifiedtime', 'messagesid', $crmid));
		} else {
			$ts = strtotime(getSingleFieldValue($table_prefix.'_crmentity', 'modifiedtime', 'crmid', $crmid));
		}
		
		// try to use the changelog to skip non-field modifications (ex: related lists)
		if ($this->useChangelog && isModuleInstalled('ChangeLog') && vtlib_isModuleActive('ChangeLog')) {
			$clfocus = $touchUtils->getModuleInstance('ChangeLog');
			if ($clfocus && $clfocus->isEnabled($module)) {
				$changes = array();
				// crmv@161363 crmv@164120
				$res = $adb->limitPquery(
					"SELECT modified_date, description 
					FROM {$clfocus->table_name}
					WHERE parent_id = ?
					ORDER BY modified_date DESC, {$clfocus->table_index} DESC", 
					0,50,
					array($crmid)
				);
				// crmv@161363e crmv@164120e
				if ($res && $adb->num_rows($res) > 0) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						$change = $this->parseChangeLog($row['description'], $module, $crmid);
						$change['timestamp'] = strtotime($row['modified_date']); // crmv@167773
						$changes[] = $change;
					}
				}
				// now examine the logs (ordered from the newest)
				if (count($changes) > 0) {
					foreach ($changes as $change) {
						if ($change['type'] == 'relation') continue;
						if ($change['timestamp'] < $ts) {
							$ts = $change['timestamp'];
						}
						break;
					}
				}
			}
		}
		
		return $ts;
	}
	
	// the function in the ChangeLog module, returns partial informations
	protected function parseChangeLog($description, $module, $crmid) {
		$ret = array();
		$description_elements = Zend_Json::decode($description);
		
		if (is_array($description_elements)) {
			if ($description_elements[0] == 'GenericChangeLog') {
				$ret['type'] = 'generic';
				$ret['text'] = $description_elements[1];
			} elseif ($description_elements[0] == 'ModNotification_Relation') {
				$ret['type'] = 'relation';
				
				$tmp = $description_elements[1];
				$tmp = explode(' LBL_LINKED_TO ',$tmp);

				$tmp1 = substr($tmp[0],0,strpos($tmp[0],' ('));
				$url1 = preg_match("/<a href=[\"'](.+)[\"']>/", $tmp1, $match1);
				$info1 = parse_url($match1[1]);
				parse_str($info1['query'], $info1);
				$module1 = $info1['module'];
				$record1 = $info1['record'];
				
				$tmp2 = substr($tmp[1],0,strpos($tmp[1],' ('));
				$url2 = preg_match("/<a href=[\"'](.+)[\"']>/", $tmp2, $match2);
				$info2 = parse_url($match2[1]);
				parse_str($info2['query'], $info2);
				$module2 = $info2['module'];
				$record2 = $info2['record'];
				
				if ($module1 == $module && $record1 == $crmid) {
					$ret['module'] = $module2;
					$ret['crmid'] = $record2;
				} else {
					$ret['module'] = $module1;
					$ret['crmid'] = $record1;
				}
				
			} else {
				$ret['type'] = 'fields';
				
				foreach($description_elements as $value){
					$fieldname = $value[0];
					
					$ret['changes'][$fieldname] = array(
						'fieldname' => $fieldname,
						// not needed here, so not retrieved
						//'previous' => $previous_value,
						//'current' => $current_value,
					);
				}
			}
		}
		return $ret;
	}
	
	public function preSaveRecord($module, $crmid, $mode, &$values) {
		global $touchInst;
		// fix for calendar
		if ($module == 'Events') {
			$start = substr($values['date_start'], 0, 10).'T'.substr($values['time_start'], 0, 5).':00';
			$end = substr($values['due_date'], 0, 10).'T'.substr($values['time_end'], 0, 5).':00';
			$tsStart = DateTime::createFromFormat('Y-m-d\TH:i:s', $start);
			$tsEnd = DateTime::createFromFormat('Y-m-d\TH:i:s', $end);
			$diff = $tsEnd->getTimestamp() - $tsStart->getTimestamp();
			$delta_hours = floor($diff / 3600);
			$delta_min = floor($diff / 60) % 60;
			$values['duration_hours'] = $delta_hours;
			$values['duration_minutes'] = $delta_min;
		} else if ($module == 'Processes' && isset($values['dynaform'])) { // crmv@100158
			$dynaform = Zend_Json::decode($values['dynaform']) ?: array();
			$blocks = $dynaform['blocks'] ?: array();
			foreach ($blocks as $blocks) {
				$fields = $blocks['fields'] ?: array();
				foreach ($fields as $field) {
					$fieldname = $field['name'];
					$value = $field['value'];
					
					$type = $field['type']['name'];
					if ($type === 'reference') {
						$_REQUEST[$fieldname] = $value;
					} else {
						$_REQUEST[$fieldname] = $touchInst->touch2Field($module, $fieldname, $value, $field);
					}
				}
			}
		} else if ($module == 'HelpDesk') {
			// crmv@104567
			if (isset($values['signature'])) {
				$path = 'storage/signatures/';
				if (!file_exists($path)) {
					mkdir($path);
					chmod($path, 0755);
				}
				$img = $values['signature'];
				$img = str_replace('data:image/png;base64,', '', $img);
				$img = str_replace(' ', '+', $img);
				$data = base64_decode($img);
				$file = $path . 'signature_' . md5($crmid) . '.png';
				$success = file_put_contents($file, $data);
				if ($success) {
					$values['signature'] = $file;
				}
			}
			// crmv@104567e
		}
	}
	
	public function postSaveRecord(&$returndata = null) {
		// do nothing for now... you can extend the class and do something
	}
	
	protected function retrieveOldRecord($module, $crmid) {
		global $touchInst, $touchUtils, $current_user;
		
		$wsrecordid = vtws_getWebserviceEntityId($module, $crmid);

		$wsname = 'retrieve';
		if (isInventoryModule($module)) $wsname = 'retrieveInventory';
		$response = $touchUtils->wsRequest($current_user->id,$wsname, array('id'=>$wsrecordid));
		
		return $response;
	}
	
	public function updateRecord($module, $crmid, $newRecord, &$returndata = null) {
		global $touchInst, $touchUtils, $current_user;

		// retrieve old record
		$response = $this->retrieveOldRecord($module, $crmid);
		if (!empty($response['error'])) {
			return $response;
		}
		$record = $response['result'];

		// other fix for stupid calendar
		if ($module == 'Events') {
			unset($record['contact_id']);
		}

		if (is_array($newRecord) && is_array($record)) {
			// aggiungo blocco prodotti se manca
			if (!array_key_exists('product_block', $record) && !empty($newRecord['product_block'])) $record['product_block'] = array();
			foreach ($newRecord as $fldname => $fldval) {
				if (array_key_exists($fldname, $record)) {
					// to prevent notes html content from being stripped out
					if ($module == 'Documents' && $fldname == 'notecontent') {
						if (is_array($this->rawValues)) {
							$fldval = $this->rawValues[$fldname];
						}
					}
					$fldval = $touchInst->touch2Field($module, $fldname, $fldval);
					$record[$fldname] = $fldval;
				} else {
					// crmv@100158
					if ($module === 'Processes') {
						$hidden_fields = array('processmaker', 'running_process');
						if (in_array($fldname, $hidden_fields)) {
							$fldval = $touchInst->touch2Field($module, $fldname, $fldval);
							$record[$fldname] = $fldval;
						}
					}
					// crmv@100158e
				}
			}
		}

		if ($module == 'MyNotes' && !array_key_exists('assigned_user_id', $record)) {
			$record['assigned_user_id'] = $current_user->id;
		}
		
		$wsclass = $touchInst->getWSClassInstance('UploadFile', $this->requestedVersion);
		$uploads = $this->checkUploadedFile($module, $newRecord, $record, $wsclass); // crmv@182645

		$response = $touchUtils->wsRequest($current_user->id,'update', array('element'=>$record) );
		
		if ($response['success'] === true) {
			// and delete the uploads
			if ($uploads && count($uploads) > 0) {
				$this->createDocsFromUploads($module, $crmid, $uploads, $wsclass); // crmv@182645
				$wsclass->removeUploads($uploads);
			}
			
			// crmv@167773
			// update the timestamp to the one passed by the app, to
			if (!empty($touchInst->collision_strategy) && !empty($newRecord['timestamp'])) {
				$this->setRecordTs($module, $crmid, $newRecord['timestamp'], 'update');
				$record['modifiedtime'] = date('Y-m-d H:i:s', $newRecord['timestamp']);
			}
			// crmv@167773e
			
			$record['crmid'] = $crmid;
			$returndata = $record;
		}
		
		return $response;
	}
	
	public function createRecord($module, $tempCrmid, $newRecord, &$returndata = null) {
		global $touchInst, $touchUtils, $current_user;
		
		$updateRecord = array();
		
		if (is_array($newRecord)) {
			foreach ($newRecord as $fldname => $fldval) {
				$fldval = $touchInst->touch2Field($module, $fldname, $fldval);
				if ($fldval == '') continue;
				$updateRecord[$fldname] = $fldval;
			}
			// aggiunta campi per calendario
			if ($module == 'Calendar') {
				$updateRecord['activitytype'] = 'Task';
				$updateRecord['visibility'] = 'Standard';
			}

			if ($module == 'MyNotes' && !array_key_exists('assigned_user_id', $updateRecord)) {
				$updateRecord['assigned_user_id'] = $current_user->id;
			}
		}
		
		// add attachment uploaded from the app
		$wsclass = $touchInst->getWSClassInstance('UploadFile', $this->requestedVersion);
		$uploads = $this->checkUploadedFile($module, $newRecord, $updateRecord, $wsclass); // crmv@182645

		$response = $touchUtils->wsRequest($current_user->id,'create',
			array(
				'elementType'=>$module,
				'element'=>$updateRecord,
			)
		);
		//$record = $response['result'];

		// in caso di creazione ritorno il record appena creato
		if ($response['success'] === true) {
			list($modid, $recordid) = explode('x', $response['result']['id'], 2);
			
			// and delete the uploads
			if ($uploads && count($uploads) > 0) {
				$this->createDocsFromUploads($module, $recordid, $uploads, $wsclass); // crmv@182645
				$wsclass->removeUploads($uploads);
			}

			$focus = $touchUtils->getModuleInstance($module);
			$focus->retrieve_entity_info($recordid, $module);
			$record = $focus->column_fields;
			foreach ($record as $fldname=>$fldvalue) {
				$record[$fldname] = $touchInst->field2Touch($module, $fldname, $fldvalue);
			}
			
			$touchInst->setTempId($current_user-id, null, $recordid, $tempCrmid); // crmv@106521
			
			// crmv@167773
			// update the timestamp to the one passed by the app, to
			if (!empty($touchInst->collision_strategy) && !empty($newRecord['timestamp'])) {
				$this->setRecordTs($module, $crmid, $newRecord['timestamp'], 'create');
				$record['createdtime'] = date('Y-m-d H:i:s', $newRecord['timestamp']);
				$record['modifiedtime'] = date('Y-m-d H:i:s', $newRecord['timestamp']);
			}
			// crmv@167773e

			$record['crmid'] = $recordid;
			$record['temp_crmid'] = $tempCrmid; // pass old temporary id so the app knows what to update
			$returndata = $record;
		}
			
		return $response;
	}
	
	protected function checkUploadedFile($module, $inputValues, &$outputValues, $wsclass) { // crmv@182645

		$uploads = array_filter(array_unique(explode(',', $inputValues['upload_ids'])), function($v) {
			return $v !== "" && $v >= 0;
		});
		
		$docMods = array('Documents', 'Myfiles'); // crmv@182645
		
		// if it's a document module, include the file info into the record
		// otherwise link it as a document later
		if (in_array($module, $docMods) && count($uploads) > 0) { // crmv@182645
			
			// add other uploaded files
			// retrieve the file information
			$list = $wsclass->getTouchUploadList($uploads);
			$base = 'storage/touch_uploads/';
			$_FILES = array();
			foreach ($list as $uinfo) {
				if (is_readable($base.$uinfo['path'])) {
					$_FILES['filename'] = array(
						'name' => $uinfo['realname'] ?: $uinfo['path'],
						'tmp_name' => $base.$uinfo['path'],
						'type' => $uinfo['filetype'],
						'size' => filesize($base.$uinfo['path'])
					);
					$_POST['copy_not_move'] = 'true';
					$outputValues['filelocationtype'] = 'I';
					unset($outputValues['upload_ids']);
					return $uploads;
					break;
				}
			}
		// crmv@182645
		} elseif (count($uploads) > 0) {
			return $uploads;
		}
		// crmv@182645e
		return false;
	}
	
	// crmv@182645
	protected function createDocsFromUploads($module, $crmid, $uploads, $wsclass) {
	
		$docMods = array('Documents', 'Myfiles');
		$base = 'storage/touch_uploads/';
		$docids = array();
		
		// check if already saved with the record
		if (in_array($module, $docMods)) return $docids;
		
		// otherwise, create a document and link it to the record
		
		$docFocus = CRMEntity::getInstance('Documents');
		
		$list = $wsclass->getTouchUploadList($uploads);
		foreach ($list as $uinfo) {
			$docid = $docFocus->createDocumentFromPathFile($base.$uinfo['path'], null, $crmid);
			if ($docid > 0) {
				$docids[] = $docid;
			}
		}
		
		return $docids;
	}
	// crmv@182645e
	
	/**
	 * Executes extra operations after the save operation
	 */
	protected function processPostActions($record, $postActions, &$message) {
		global $touchInst, $touchUtils;
		
		$returnok = true;
		
		foreach ($postActions as $action) {
			$actionName = $action['name'];
			$actionParams = $action['params'];
			
			if ($actionName == 'LinkToRecord' || $actionName == 'LinkToRecordRev') {
				$linkModule = $actionParams['module'];
				$linkCrmid = $actionParams['crmid'];
				if (empty($linkModule) || empty($linkCrmid)) {
					$returnok = false;
					$message = "Wrong parameters for post action: $actionName";
					break;
				} else {
					if ($actionName == 'LinkToRecord') {
						$req = array(
							'module_from' => $record['record_module'],
							'crmid_from' => $record['record_id'],
							'module_to' => $linkModule,
							'crmid_to' => $linkCrmid,
						);
					} elseif ($actionName == 'LinkToRecordRev') {
						$req = array(
							'module_to' => $record['record_module'],
							'crmid_to' => $record['record_id'],
							'module_from' => $linkModule,
							'crmid_from' => $linkCrmid,
						);
					}
					$r = $this->subcall('LinkModules', $req);
					if (!$r['success']) {
						$returnok = false;
						$message = "Post action failed: ".$r['error'];
						break;
					}
				}
			} else {
				$returnok = false;
				$message = "Unknown post action: $actionName";
				break;
			}
		}
		return $returnok;
	}

}