<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 crmv@96450 crmv@97566 crmv@102879 crmv@115579 crmv@115268 crmv@126096 crmv@150751 crmv@163905 */
require_once('include/Zend/Json.php');

class ProcessMakerUtils extends SDKExtendableUniqueClass {
	
	var $uploadMaxSize;
	var $importDirectory;
	var $table_name;
	var $edit_permission_mode = 'all';	//crmv@105685 all | assigned
	var $limit_processes = 2;	//crmv@98899
	var $modules_not_supported = array('Documents','Emails','Faq','Users','ModComments','Messages','Fax','Sms','MyFiles','Processes','PBXManager');	//crmv@37660 crmv@106461 crmv@113771 crmv@148531 crmv@153819 crmv@164120 crmv@164122
	// excluded modules from link to
	var $modules_excluded_link = array('Emails','Users','ModComments','Messages','Fax','Sms','MyFiles','Processes'); // crmv@126184 crmv@148531 crmv@153819 crmv@164120 crmv@164122
	var $editoptions_uitypes_not_supported = array(4,54,56,26,27,212,5,6,23,73); //crmv@128159 crmv@160843
	//crmv@182172
	var $blocks_not_supported = array(
		'HelpDesk' => array('LBL_COMMENTS','LBL_SIGNATURE_BLOCK'),
	);
	//crmv@182172e
	var $megaFunctions = false;	//crmv@135190
	//crmv@135193
	var $skip_notifications_actions = false;	// skip notifications created by actions
	var $skip_notifications_helper = false;		// skip notifications of ProcessHelper
	var $hide_changelog = false;				// hide changelog made by processes
	//crmv@135193e
	var $versioning_skip_modules = array('Events'); //crmv@180014
	var $startVersionNumber = '1.0';	//crmv@147720
	var $cacheStorage = 'session';	//crmv@153321_5 session | file
	var $preserved_request = array();
	// crmv@189728
	var $debugLogs = array(
		'Conditions' => array(
			'active' => false,
			'file' => 'logs/ProcessEngine/Conditions.log',
		)
	);
	// crmv@189728e
	
	var $metadataTypes = array(
		'ConditionTask'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/ConditionTask.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/ConditionTask.tpl',
		),
		'ActionTask'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/ActionTask.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/ActionTask.tpl',
		),
		'ParallelGateway'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/ParallelGateway.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/ParallelGateway.tpl',
		),
		'Gateway'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/Gateway.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/Gateway.tpl',
		),
		'TimerStart'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/TimerStart.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/TimerStart.tpl',
		),
		'TimerIntermediate'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/TimerIntermediate.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/TimerIntermediate.tpl',
		),
		'TimerBoundaryInterr'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/TimerBoundary.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/TimerBoundary.tpl',
		),
		'TimerBoundaryNonInterr'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/TimerBoundary.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/TimerBoundary.tpl',
		),
		//crmv@97575
		'SubProcess'=>array(
			'php'=>'modules/Settings/ProcessMaker/Metadata/SubProcess.php',
			'tpl'=>'Settings/ProcessMaker/Metadata/SubProcess.tpl',
		),
		//crmv@97575e
	);
	var $actionTypes = array(
		'Create' => array(
			'class'=>'PMActionCreate',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Create.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Create.tpl'
		),
		'Update' => array(
			'class'=>'PMActionUpdate',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Update.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Update.tpl'
		),
		'Delete' => array(
			'class'=>'PMActionDelete',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Delete.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Delete.tpl'
		),
		//crmv@183346
		'ModNotification' => array(
			'class'=>'PMActionModNotification',
			'php_file'=>'modules/Settings/ProcessMaker/actions/ModNotification.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/ModNotification.tpl'
		),
		//crmv@183346e
		'Email' => array(
			'class'=>'PMActionEmail',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Email.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Email.tpl'
		),
		//crmv@126696
		'SendNewsletter' => array(
			'class'=>'PMActionSendNewsletter',
			'php_file'=>'modules/Settings/ProcessMaker/actions/SendNewsletter.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/SendNewsletter.tpl'
		),
		//crmv@126696e
		// crmv@187729
		'CreatePDF' => array(
			'class'=>'PMActionCreatePDF',
			'php_file'=>'modules/Settings/ProcessMaker/actions/CreatePDF.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/CreatePDF.tpl'
		),
		// crmv@187729e
		//crmv@105685
		'ResetDynaform' => array(
			'class'=>'PMActionResetDynaform',
			'php_file'=>'modules/Settings/ProcessMaker/actions/ResetDynaform.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/ResetDynaform.tpl'
		),
		//crmv@105685e
		'Cycle' => array(
			'class'=>'PMActionCycle',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Cycle.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Cycle.tpl',
			'actions'=>array('Email','Create','Update','ModNotification','InsertTableRow','DeleteTableRow','InsertProductRow','DeleteProductRow') //crmv@183346 crmv@195745
		),
		//crmv@203075
		'CycleRelated' => array(
			'class'=>'PMActionCycleRelated',
			'php_file'=>'modules/Settings/ProcessMaker/actions/CycleRelated.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/CycleRelated.tpl',
			'actions'=>array('Email','Create','Update','ModNotification','InsertTableRow', 'CreatePDF') //crmv@183346 crmv@195745
		),
		//crmv@203075e
		//crmv@112297
		'DeleteConditionals' => array(
			'class'=>'PMActionDeleteConditionals',
			'php_file'=>'modules/Settings/ProcessMaker/actions/DeleteConditionals.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/DeleteConditionals.tpl'
		),
		//crmv@112297e
		//crmv@113775
		'Relate' => array(
			'class'=>'PMActionRelate',
			'php_file'=>'modules/Settings/ProcessMaker/actions/Relate.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/Relate.tpl'
		),
		//crmv@113775e
		//crmv@126184
		'RelateStatic' => array(
			'class'=>'PMActionRelateStatic',
			'php_file'=>'modules/Settings/ProcessMaker/actions/RelateStatic.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/RelateStatic.tpl'
		),
		//crmv@126184e
		//crmv@185548
		'TransferRelations' => array(
			'class'=>'PMActionTransferRelations',
			'php_file'=>'modules/Settings/ProcessMaker/actions/TransferRelations.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/TransferRelations.tpl'
		),
		//crmv@185548e
		'InsertTableRow' => array(
			'class'=>'PMActionInsertTableRow',
			'php_file'=>'modules/Settings/ProcessMaker/actions/InsertTableRow.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/InsertTableRow.tpl'
		),
		'DeleteTableRow' => array(
			'class'=>'PMActionDeleteTableRow',
			'php_file'=>'modules/Settings/ProcessMaker/actions/DeleteTableRow.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/DeleteTableRow.tpl',
			'hide_main_menu'=>true
		),
		// crmv@195745
		'CloneProductsBlock' => array(
			'class'=>'PMActionCloneProductsBlock',
			'php_file'=>'modules/Settings/ProcessMaker/actions/CloneProductsBlock.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/CloneProductsBlock.tpl'
		),
		'InsertProductRow' => array(
			'class'=>'PMActionInsertProductRow',
			'php_file'=>'modules/Settings/ProcessMaker/actions/InsertProductRow.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/InsertTableRow.tpl'
		),
		'DeleteProductRow' => array(
			'class'=>'PMActionDeleteProductRow',
			'php_file'=>'modules/Settings/ProcessMaker/actions/DeleteProductRow.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/DeleteTableRow.tpl',
			'hide_main_menu'=>true
		),
		// crmv@195745e
		// crmv@146671
		'CallExtWS' => array(
			'class'=>'PMActionCallExtWS',
			'php_file'=>'modules/Settings/ProcessMaker/actions/CallExtWS.php',
			'tpl_file'=>'Settings/ProcessMaker/actions/CallExtWS.tpl'
		),
		// crmv@146671e
	);
	
	function __construct() {
		global $upload_maxsize, $import_dir, $table_prefix;
		$this->uploadMaxSize = $upload_maxsize;
		$this->importDirectory = $import_dir;
		$this->table_name = $table_prefix.'_processmaker';
		eval(Users::m_de_cryption());
		eval($hash_version[22]);
	}
	// crmv@189728
	function debug($type, $str, $start=false) {
		if ($this->debugLogs[$type]['active']) {
			if ($start) file_put_contents($this->debugLogs[$type]['file'],"-------------------- ".date('Y-m-d H:i:s')." --------------------\n",FILE_APPEND);
			if (is_array($str))
				file_put_contents($this->debugLogs[$type]['file'],print_r($str,true),FILE_APPEND);
			else
				file_put_contents($this->debugLogs[$type]['file'],$str."\n",FILE_APPEND);
		}
	}
	// crmv@189728e
	//crmv@98899
	function limitProcessesExceeded() {
		global $adb, $table_prefix;
		$result = $adb->query("SELECT COUNT(*) as \"count\" FROM {$table_prefix}_processmaker WHERE active = 1");
		if ($result && $adb->num_rows($result) > 0) {
			$count = $adb->query_result($result,0,'count');
			if ($count > $this->limit_processes) return $count;
		}
		return false;
	}
	//crmv@98899e
	function checkActiveProcesses() {
		global $adb, $table_prefix;
		$result = $adb->query("SELECT COUNT(*) as \"count\" FROM {$table_prefix}_processmaker WHERE active = 1");
		if ($result && $adb->num_rows($result) > 0) {
			$count = $adb->query_result($result,0,'count');
			if ($count < $this->limit_processes) return true;
		}
		return false;
	}
	function formatType($type, $display=false) {
		if (strpos($type,':') !== false) $type = substr($type,strpos($type,':')+1);
		if ($display) $type = "BPMN-$type";
		return $type;
	}
	function getMetadataTypes($type='',$structure=array()) {
		if (!empty($type)) {
			//$metadataType = $this->metadataTypes[$type];
			//if (empty($metadataType)) {
				if ($type == 'Task') {
					$metadataType = $this->metadataTypes['ConditionTask'];
				} elseif (strpos($type,'Task') !== false || $type == 'EndEvent') {
					$metadataType = $this->metadataTypes['ActionTask'];
				} elseif ($type == 'ParallelGateway') {
					$metadataType = $this->metadataTypes['ParallelGateway'];
				} elseif (strpos($type,'Gateway') !== false) {
					$metadataType = $this->metadataTypes['Gateway'];
				} elseif ($type == 'StartEvent' && $structure['subType'] == 'TimerEventDefinition') {
					$metadataType = $this->metadataTypes['TimerStart'];
				} elseif ($type == 'IntermediateCatchEvent' && $structure['subType'] == 'TimerEventDefinition') {
					$metadataType = $this->metadataTypes['TimerIntermediate'];
				} elseif ($type == 'BoundaryEvent' && $structure['subType'] == 'TimerEventDefinition') {
					($structure['cancelActivity']) ? $metadataType = $this->metadataTypes['TimerBoundaryInterr'] : $metadataType = $this->metadataTypes['TimerBoundaryNonInterr'];
				//crmv@97575
				} elseif ($type == 'SubProcess') {
					$metadataType = $this->metadataTypes['SubProcess'];
				}
				//crmv@97575e
			//}
			return $metadataType;
		} else {
			return $this->metadataTypes;
		}
	}
	function getEngineType($structure) {
		$engineType = '';
		if ($structure['type'] == 'Task') {
			$engineType = 'Condition';
		} elseif (strpos($structure['type'],'Task') !== false || $structure['type'] == 'EndEvent') {
			$engineType = 'Action';
		} elseif (strpos($structure['type'],'Gateway') !== false) {
			$engineType = 'Gateway';
		} elseif ($structure['type'] == 'StartEvent' && $structure['subType'] == 'TimerEventDefinition') {
			$engineType = 'TimerStart';
		} elseif ($structure['type'] == 'IntermediateCatchEvent' && $structure['subType'] == 'TimerEventDefinition') {
			$engineType = 'TimerIntermediate';
		} elseif ($structure['type'] == 'BoundaryEvent' && $structure['subType'] == 'TimerEventDefinition') {
			($structure['cancelActivity']) ? $engineType = 'TimerBoundaryInterr' : $engineType = 'TimerBoundaryNonInterr';
		//crmv@97575
		} elseif ($structure['type'] == 'SubProcess') {
			$engineType = 'SubProcess';
		}
		//crmv@97575e
		return $engineType;
	}
//	function getVteType($id,$elementid) {
//		$structure = $this->getStructure($id);
//		$element = $structure['tree'][$elementid];
//		preprint($element);
//	}
	function getActionTypes($type='') {
		$actionTypes = $this->actionTypes;
		foreach($actionTypes as $actionType => $info) {
			$actionTypes[$actionType]['label'] = getTranslatedString('LBL_PM_ACTION_'.$actionType,'Settings');
		}
		$sdkActions = SDK::getProcessMakerActions();
		if (!empty($sdkActions)) {
			foreach($sdkActions as $sdkAction) {
				$actionTypes['SDK:'.$sdkAction['funct']] = $sdkAction;
			}
		}
		if (!empty($type)) {
			return $actionTypes[$type];
		} else {
			return $actionTypes;
		}
	}
	function isStartTask($id,$elementid,$running_process='') {
		$incoming = $this->getIncoming($id,$elementid,$running_process);
		return ($incoming[0]['shape']['type'] == 'StartEvent');
	}
	function isEndTask($bpmnType) {
		if ($bpmnType == 'EndEvent') {	// TODO add other types of end events
			return true;
		} else {
			return false;
		}
	}
	
	function getHeaderList($relate=false) {
		if ($relate)
			return array(
				'',
				getTranslatedString('LBL_PROCESS_MAKER_RECORD_NAME','Settings'),
				getTranslatedString('LBL_PROCESS_MAKER_RECORD_DESC','Settings'),
				getTranslatedString('LBL_PM_SUBPROCESSES','Settings'),
				getTranslatedString('LBL_MODULE'), //crmv@185705
				getTranslatedString('Active')
			);
		else
			return array(
				getTranslatedString('LBL_ACTIONS'),
				getTranslatedString('LBL_PROCESS_MAKER_RECORD_NAME','Settings'),
				getTranslatedString('LBL_PROCESS_MAKER_RECORD_DESC','Settings'),
				getTranslatedString('LBL_PM_SUBPROCESSES','Settings'),
				getTranslatedString('LBL_MODULE'), //crmv@185705
				getTranslatedString('Active')
			);
	}
	
	function getList($relate=false, $skip_ids='', $record_checked='') { //crmv@161211
		global $adb, $table_prefix, $theme, $app_strings; //crmv@185705
		$list = array();
		//crmv@185705
		$query = "select {$this->table_name}.id, {$this->table_name}.name, {$this->table_name}.description, {$this->table_name}.active, {$this->table_name}.version, {$table_prefix}_processmaker_metarec.module
			from {$this->table_name}
			left join {$table_prefix}_processmaker_metarec on {$table_prefix}_processmaker.id = {$table_prefix}_processmaker_metarec.processid and {$table_prefix}_processmaker_metarec.start = 1";
		//crmv@185705e
		$params = array();
		if ($relate) {
			//crmv@161211
			if (!is_array($skip_ids)) $skip_ids = array($skip_ids);
			$query .= " where {$this->table_name}.id not in (".generateQuestionMarks($skip_ids).")";
			$params[] = $skip_ids;
			//crmv@161211e
		}
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$subprocesses_text = array();
				$subprocesses = $this->getSubprocesses($row['id']);
				if (!empty($subprocesses)) {
					foreach($subprocesses as $elementid => $subprocess) {
						if ($relate) {
							$subprocesses_text[] = textlength_check($subprocess['name']);
						} else {
							$subprocesses_text[] = '<a href="index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&parenttab=Settings&mode=detail&id='.$subprocess['subprocess'].'">'.textlength_check($subprocess['name']).'</a>';							
						}
					}
				}
				if (count($subprocesses_text) > 3) $subprocesses_text = $subprocesses_text[0].', '.$subprocesses_text[1].', '.$subprocesses_text[2].', ...';
				else $subprocesses_text = implode(',',$subprocesses_text);
				if ($relate) {
					(!empty($record_checked) && $record_checked == $row['id']) ? $checked = 'checked' : $checked = '';
					$list[] = array(
						'<input type="radio" name="subprocess" id="subprocess_'.$row['id'].'" value="'.$row['id'].'" '.$checked.'/>',
						'<a href="javascript:;"><label for="subprocess_'.$row['id'].'" style="font-weight:normal">'.textlength_check($row['name']).'</label></a>',
						textlength_check($row['description']),
						$subprocesses_text,
						getTranslatedString($row['module'],$row['module']), //crmv@185705
						($row['active'] == 1) ? $app_strings['yes'] : $app_strings['no'],
					);
				} else {
					//crmv@147720
					$list[] = array(
						'<a href="javascript:ProcessMakerScript.confirmdelete(\'index.php?module=Settings&action=ProcessMaker&parenttab=Settings&mode=delete&id='.$row['id'].'\')"><i class="vteicon" title="'.getTranslatedString('LBL_DELETE_BUTTON').'">clear</i></a>
						<a href="javascript:ProcessMakerScript.download(\'bpmn\',\''.$row['id'].'\',\''.$row['version'].'\',false)"><i class="vteicon" title="'.getTranslatedString('LBL_DOWNLOAD_BPMN','Settings').'">device_hub</i></a>
						<a href="javascript:ProcessMakerScript.download(\'vtebpmn\',\''.$row['id'].'\',\''.$row['version'].'\',true)"><i class="vteicon" title="'.getTranslatedString('LBL_DOWNLOAD_VTEBPMN','Settings').'">file_download</i></a>
						<a href="javascript:ProcessMakerScript.upload(\'vtebpmn\',\''.$row['id'].'\')"><i class="vteicon" title="'.getTranslatedString('LBL_UPLOAD_VTEBPMN','Settings').'">file_upload</i></a>',
						'<a href="index.php?module=Settings&action=SettingsAjax&file=ProcessMaker&parenttab=Settings&mode=detail&id='.$row['id'].'">'.textlength_check($row['name']).'</a>',
						textlength_check($row['description']),
						$subprocesses_text,
						getTranslatedString($row['module'],$row['module']), //crmv@185705
						($row['active'] == 1) ? $app_strings['yes'] : $app_strings['no'],
					);
					//crmv@147720e
				}
			}
		}
		return $list;
	}
	
	//crmv@100972 crmv@147720
	function checkUploadBPMN(&$err='', $upload_ext=array()) {
		if (empty($upload_ext)) $upload_ext = array('bpmn','vtebpmn');
		if (isset($_FILES['bpmnfile']['tmp_name']) && !empty($_FILES['bpmnfile']['tmp_name'])) {
			$xml = file_get_contents($_FILES['bpmnfile']['tmp_name']);
		}
		if (!empty($xml)) {
			$ext = pathinfo($_FILES['bpmnfile']['name'], PATHINFO_EXTENSION);
			if (!in_array($ext,$upload_ext)) {
				$err = getTranslatedString('LBL_INVALID_FILE_EXTENSION', 'Settings');
				return false;
			}
			if(!is_uploaded_file($_FILES['bpmnfile']['tmp_name'])) {
				$err = getTranslatedString('LBL_FILE_UPLOAD_FAILED', 'Import');
				return false;
			}
			if ($_FILES['bpmnfile']['size'] > $this->uploadMaxSize) {
				$err = getTranslatedString('LBL_IMPORT_ERROR_LARGE_FILE', 'Import').' $uploadMaxSize.'.getTranslatedString('LBL_IMPORT_CHANGE_UPLOAD_SIZE', 'Import');
				return false;
			}
			if(!is_writable($this->importDirectory)) {
				$err = getTranslatedString('LBL_IMPORT_DIRECTORY_NOT_WRITABLE', 'Import');
				return false;
			}
		}
		return true;
	}
	
	function readUploadedFile($file_content) {
		$data = array();
		$data['file'] = $file_content;
		if (stripos($file_content,'<vtebpmn>') !== false) {
			$xmlObj = new SimpleXMLElement($file_content);
			$data['name'] = strval($xmlObj->name);
			$data['description'] = strval($xmlObj->description);
			$data['version'] = strval($xmlObj->version);
			$data['bpmn'] = base64_decode($xmlObj->bpmn);
			$data['vte_metadata'] = base64_decode($xmlObj->vte_metadata);
			$data['structure'] = base64_decode($xmlObj->structure);
			$data['helper'] = base64_decode($xmlObj->helper);
			$data['metarec'] = base64_decode($xmlObj->metarec);
			$data['dynameta'] = base64_decode($xmlObj->dynameta);
			$data['system_versions'] = base64_decode($xmlObj->system_versions);
			// TODO wsmeta
		} else {
			$data['bpmn'] = $file_content;
		}
		return $data;
	}
	
	function save($data, $id=null, $overwrite=false, $pending_changes=true) {
		global $adb, $table_prefix, $default_charset;	//crmv@132240 crmv@193096
		
		if (empty($id) || $overwrite) {
			$name = $data['name'];
			$description = $data['description'];
			$version = $data['version'];
			$xml = $data['bpmn'];
			$vte_metadata = $data['vte_metadata'];
			$structure = $data['structure'];
			$helper = $data['helper'];
			$metarec = $data['metarec'];
			$dynameta = $data['dynameta'];
			$wsmeta = $data['wsmeta'];	// TODO export wsmeta!!!
			
			if (empty($version)) $version = $this->startVersionNumber;
			
			if (empty($id)) {
				// create
				// crmv@193096
				$id = $adb->getUniqueID($this->table_name);
				$values = array(
					'id' => $id,
					'name' => $name,
					'description' => $description,
					'vte_metadata' => $vte_metadata,
					'structure' => $structure,
					'helper' => $helper,
					'version' => $version
				);
				$cols = $adb->getColumnNames($this->table_name);
				// crmv@205568
				if (in_array('creatorid', $cols)) {
					global $current_user;
					(!empty($current_user)) ? $user = $current_user : $user = Users::getActiveAdminUser();
					$values['creatorid'] = $user->id;
				}
				// crmv@205568e
				if (in_array('createdtime', $cols)) $values['createdtime'] = date('Y-m-d H:i:s');
				$adb->pquery("insert into {$this->table_name}(".implode(',',array_keys($values)).") values(".generateQuestionMarks($values).")", array($values));
				// crmv@193096e
			} elseif ($overwrite) {
				// update in overwrite mode
				$adb->pquery("update {$this->table_name} set name = ?, description = ?, vte_metadata = ?, structure = ?, helper = ?, version = ? where id = ?", array($name,$description,$vte_metadata,$structure,$helper,$version,$id));
				$adb->pquery("delete from {$table_prefix}_processmaker_metarec where processid = ?", array($id));
				$adb->pquery("delete from {$table_prefix}_process_dynaform_meta where processid = ?", array($id));
				$adb->pquery("delete from {$table_prefix}_process_extws_meta where processid = ?", array($id));
				// TODO reset subprocesses and timers such as $this->edit()
			}
			$adb->updateClob($this->table_name,'xml',"id=$id",$xml);
			
			// save records involved
			if (!empty($metarec)) {
				foreach($metarec as $r) {
					$r['processid'] = $id;
					$r['text'] = html_entity_decode($r['text'],ENT_QUOTES,$default_charset);	//crmv@132240
					$adb->pquery("insert into {$table_prefix}_processmaker_metarec(".implode(',',array_keys($r)).") values (".generateQuestionMarks($r).")", $r);
				}
			}
			// save dynaform informations
			if (!empty($dynameta)) {
				foreach($dynameta as $r) {
					$r['processid'] = $id;
					$r['text'] = html_entity_decode($r['text'],ENT_QUOTES,$default_charset);	//crmv@132240
					$adb->pquery("insert into {$table_prefix}_process_dynaform_meta(".implode(',',array_keys($r)).") values (".generateQuestionMarks($r).")", $r);
				}
			}
			// crmv@146671
			// save wscall informations
			if (!empty($wsmeta)) {
				foreach($wsmeta as $r) {
					$r['processid'] = $id;
					$r['text'] = html_entity_decode($r['text'],ENT_QUOTES,$default_charset);	//crmv@132240
					$adb->pquery("insert into {$table_prefix}_process_extws_meta(".implode(',',array_keys($r)).") values (".generateQuestionMarks($r).")", $r);
				}
			}
			// crmv@146671e
		} else {
			// update in merge mode
			// TODO call $this->edit()
		}
		
		if ($pending_changes) $adb->pquery("update {$this->table_name} set pending_changes=? where id=?", array(1, $id));
		
		return $id;
	}
	//crmv@100972e crmv@147720e
	
	//crmv@161988
	function importFile($filepath, $active=false) {
		$data = $this->readUploadedFile(file_get_contents($filepath));
		if (!empty($data['metarec'])) $data['metarec'] = Zend_Json::decode($data['metarec']);
		if (!empty($data['dynameta'])) $data['dynameta'] = Zend_Json::decode($data['dynameta']);
		if (empty($data['name'])) {
			$pathinfo = pathinfo($filepath);
			$data['name'] = $pathinfo['filename'];
		}
		$id = $this->save($data,null,false,false);
		if ($active) $this->edit($id,array('active'=>1));
	}
	//crmv@161988e
	
	function edit($id,$data) {
		global $adb, $table_prefix, $default_charset;	//crmv@132240
		$columns = array('name','description','active','pending_changes');
		$update = array();
		foreach($columns as $column) {
			if (isset($data[$column])) $update["$column=?"] = vtlib_purify($data[$column]);
		}
		$retrieve = $this->retrieve($id);

		if (isset($data['vte_metadata'])) {
			(empty($retrieve['vte_metadata']) || $retrieve['vte_metadata'] == 'null') ? $vte_metadata = array() : $vte_metadata = $vte_metadata_old = Zend_Json::decode($retrieve['vte_metadata']);
			(empty($data['vte_metadata'])) ? $vte_metadata_new = array() : $vte_metadata_new = Zend_Json::decode($data['vte_metadata']);
			$vte_metadata = array_merge($vte_metadata, $vte_metadata_new);
			$update["vte_metadata=?"] = Zend_Json::encode($vte_metadata);
		} else {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
		}
		if (isset($data['helper'])) {
			(empty($retrieve['helper']) || $retrieve['helper'] == 'null') ? $helper = array() : $helper = Zend_Json::decode($retrieve['helper']);
			(empty($data['helper'])) ? $helper_new = array() : $helper_new = Zend_Json::decode($data['helper']);
			$helper = array_merge($helper, $helper_new);
			$update["helper=?"] = Zend_Json::encode($helper);
		}
		$adb->pquery("update {$this->table_name} set ".implode(',',array_keys($update))." where id = ?", array($update,$id));

		// save records involved
		if (isset($vte_metadata)) {
			//$this->clearRecordsInvolved($id);
			$structure = $this->getStructure($id);
			foreach($structure['shapes'] as $shapeid => $shape) {
				// for all start events search the first task
				if ($shape['type'] == 'StartEvent') {
					$outgoing = $this->getOutgoing($id,$shapeid);
					foreach($outgoing as $out) {
						$engineType = $this->getEngineType($out['shape']);
						if ($engineType == 'Condition') {
							$metadata = $vte_metadata[$out['shape']['id']];
							$this->setRecordInvolved($id,$out['shape']['id'],$out['shape']['text'],$out['shape']['type'],$metadata['moduleName'],0,1, $metadata['moduleName1'], $metadata['execution_condition']);//crmv@204903
						}
					}
				}
				//crmv@97575
				if ($shape['type'] == 'SubProcess') {
					$subprocess = $vte_metadata[$shapeid]['subprocess'];
					if (!empty($subprocess)) $this->setSubprocess($id,$shapeid,$subprocess);
					else $this->unsetSubprocess($id,$shapeid);	//crmv@136524
				}
				//crmv@97575e
			}
			foreach($vte_metadata as $elementid => $m) {
				if (!empty($m['actions'])) {
					foreach($m['actions'] as $action_id => $a) {
						if ($a['action_type'] == 'Create') {
							$start = intval($this->isStartTask($a['id'],$a['elementid']));
							$this->setRecordInvolved($a['id'],$a['elementid'],$structure['shapes'][$elementid]['text'],$structure['shapes'][$elementid]['type'],$a['form_module'],$action_id,$start);
						}
					}
				}
			}
		}
		
		// save dynaform
		if (!empty($helper)) {
			foreach($helper as $elementid => $h) {
				if (!empty($h['dynaform']['mmaker_blocks'])) {
					$result = $adb->pquery("select * from {$table_prefix}_process_dynaform_meta where processid = ? and elementid = ?", array($id,$elementid));
					if ($result && $adb->num_rows($result) == 0) {
						$structure = $this->getStructureElementInfo($id,$elementid,'shapes');
						$metaid = $adb->getUniqueID("{$table_prefix}_process_dynaform_meta");
						$adb->pquery("insert into {$table_prefix}_process_dynaform_meta(id,processid,elementid,text,type) values(?,?,?,?,?)", array($metaid,$id,$elementid,html_entity_decode($structure['text'],ENT_QUOTES,$default_charset),$structure['type']));	//crmv@132240
					}
				}
			}
		}
		
		// if there is a start timer schedule the running process
		$startElementid = '';
		$isTimerProcess = $this->isTimerProcess($id,$startElementid);
		if ($isTimerProcess && (($retrieve['active'] == 0 && $data['active'] == 1) || ($retrieve['active'] == 1 && $this->isChangedTimerCondition($vte_metadata_new[$startElementid],$vte_metadata_old[$startElementid])))) {
			// cancello eventuali processi schedulati non ancora partiti
			$result = $adb->pquery("SELECT running_process FROM {$table_prefix}_running_processes_timer
				INNER JOIN {$table_prefix}_running_processes ON {$table_prefix}_running_processes.id = {$table_prefix}_running_processes_timer.running_process
				WHERE mode = ? and {$table_prefix}_running_processes.processmakerid = ?", array('start',$id));
			$delete = array();
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$delete[] = $row['running_process'];
				}
			}
			if (!empty($delete)) {
				$adb->pquery("delete from {$table_prefix}_running_processes where id in (".generateQuestionMarks($delete).")", $delete);
				$this->deleteTimer('start',$delete);
			}
			// schedule the first occourence
			$date_start = $vte_metadata[$startElementid]['date_start'].' '.$vte_metadata[$startElementid]['starthr'].':'.$vte_metadata[$startElementid]['startmin'].':00';
			($vte_metadata['date_end_mass_edit_check'] == 'on') ? $date_end = getValidDBInsertDateValue($vte_metadata['date_end']).' '.$vte_metadata['endhr'].':'.$vte_metadata['endmin'] : $date_end = false;
			$timer = $this->getTimerRecurrences($date_start,$date_end,($vte_metadata[$startElementid]['recurrence'] == 'on'),$vte_metadata[$startElementid]['cron_value'],1);
			if (!empty($timer[0])) {
				$running_process = $adb->getUniqueID("{$table_prefix}_running_processes");
				$adb->pquery("insert into {$table_prefix}_running_processes(id,processmakerid,current,xml_version) values(?,?,?,?)", array($running_process,$id,$startElementid,$retrieve['xml_version']));
				$info = array('processid'=>$id,'elementid'=>$startElementid,'running_process'=>$running_process,'calculate_next_occourence'=>true);
				$this->createTimer('start',$timer,$running_process,null,$startElementid,'',$info);	//crmv@127048
			}
		}
	}
	//crmv@134058
	function retrieve($id, $xml_version=null) {
		global $adb;
		if (empty($xml_version)) $xml_version = 'curr';
		if ($xml_version != 'curr') { //crmv@173112
			$result = $adb->pquery("select count(*) as \"count\" from {$this->table_name}_versions where processmakerid = ? and xml_version = ?", array($id,$xml_version));
			if ($adb->query_result($result,0,'count') == 0) $xml_version = 'curr';
		}
		static $retrieveCache = array();
		if (!isset($retrieveCache[$id][$xml_version])) {
			if ($xml_version == 'curr') {
				$result = $adb->pquery("select * from {$this->table_name} where id = ?", array($id));
			} else {
				$result = $adb->pquery("select pmv.processmakerid as \"id\", pm.name, pm.description, pmv.version, pm.active, pmv.xml, pmv.vte_metadata, pmv.structure, pmv.helper, pmv.xml_version
					from {$this->table_name}_versions pmv
					inner join {$this->table_name} pm on pm.id = pmv.processmakerid
					where pmv.processmakerid = ? and pmv.xml_version = ?", array($id,$xml_version));				
			}
			if ($result && $adb->num_rows($result) > 0) {
				$retrieveCache[$id][$xml_version] = $adb->fetchByAssoc($result,-1,false);
			} else {
				$retrieveCache[$id][$xml_version] = false;
			}
		}
		return $retrieveCache[$id][$xml_version];
	}
	//crmv@134058e
	function delete($id) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$this->table_name} where id = ?", array($id));
		$adb->pquery("delete from {$table_prefix}_processmaker_metarec where processid = ?", array($id));
		$adb->pquery("delete from {$table_prefix}_process_dynaform_meta where processid = ?", array($id));
		$adb->pquery("delete from {$table_prefix}_process_extws_meta where processid = ?", array($id)); // crmv@146671
	}
	function getMetadata($id,$elementid='',$running_process='') {
		(!empty($running_process)) ? $xml_version = $this->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		$data = $this->retrieve($id, $xml_version);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		if (!empty($elementid))
			return $vte_metadata[$elementid];
		else
			return $vte_metadata;
	}
	//crmv@160805
	function getMetaRecords($id,$elementid='',$running_process='') {
		global $adb, $table_prefix;
		(!empty($running_process)) ? $xml_version = $this->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		if (!empty($xml_version)) {
			$query = "SELECT id, elementid, text, type, module, action, start FROM {$table_prefix}_processmaker_metarec_vh WHERE versionid = ? and processid = ?";
			$params = array($xml_version, $processid);
		} else {
			$query = "SELECT id, elementid, text, type, module, action, start FROM {$table_prefix}_processmaker_metarec WHERE processid = ?";
			$params = array($id);
		}
		if (!empty($elementid)) {
			$query .= " and elementid = ?";
			$params[] = $elementid;
		}
		$metas = array();
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$metas[$row['elementid']] = $row;
			}
		}
		return $metas;
	}
	//crmv@160805e
	//crmv@153321_5
	function formatMetadata($id,$elementid,$vte_metadata,$helper=array(),$dynaform=null) {
		$data = array();
		$vte_metadata = Zend_Json::decode($vte_metadata);
		
		// format values
		$structure = $this->getStructureElementInfo($id,$elementid,'shapes');
		if ($structure['type'] == 'StartEvent' && $structure['subType'] == 'TimerEventDefinition') {
			$vte_metadata['date_start'] = getValidDBInsertDateValue($vte_metadata['date_start']);
			$vte_metadata['date_end'] = getValidDBInsertDateValue($vte_metadata['date_end']);
		}
		//crmv@182148
		if ($structure['type'] == 'IntermediateCatchEvent' && $structure['subType'] == 'TimerEventDefinition') {
			$vte_metadata['trigger_date_value'] = getValidDBInsertDateValue($vte_metadata['trigger_date_value']);
		}
		//crmv@182148e
		
		if (!empty($vte_metadata)) {
			$data['vte_metadata'] = Zend_Json::encode(array($elementid=>$vte_metadata));
		}
		if (!empty($helper)) {
			$helper = Zend_Json::decode($helper);
			if ($helper['assigntype'] == 'U') $helper['assigned_user_id'] = $helper['assigned_user_id'];
			elseif($helper['assigntype'] == 'T') $helper['assigned_user_id'] = $helper['assigned_group_id'];
			elseif($helper['assigntype'] == 'O' || $helper['assigntype'] == 'A') $helper['assigned_user_id'] = $helper['other_assigned_user_id']; //crmv@160843
			unset($helper['assigntype']); unset($helper['assigned_user_id_display']); unset($helper['assigned_group_id']); unset($helper['assigned_group_id_display']); unset($helper['other_assigned_user_id']);
			if (!empty($dynaform)) $helper['dynaform'] = $dynaform;
			//crmv@106856
			if ($helper['assigned_user_id'] == 'advanced_field_assignment') {
				$tmp = $this->getAdvancedFieldAssignment('assigned_user_id');;
				if (!empty($tmp)) $helper['advanced_field_assignment']['assigned_user_id'] = $tmp;
			}
			//crmv@106856e
			//crmv@160843
			if($helper['process_status_type'] == 'o') {
				$helper['process_status'] = $helper['other_process_status'];
				unset($helper['process_status_type']);
				unset($helper['other_process_status']);
			}
			//crmv@160843e
			$data['helper'] = Zend_Json::encode(array($elementid=>$helper));
		}
		return $data;
	}
	function saveMetadata($id,$elementid,$vte_metadata,$helper=array(),$dynaform=null) {
		$data = $this->formatMetadata($id,$elementid,$vte_metadata,$helper,$dynaform);
		$data['pending_changes'] = 1;
		$this->edit($id,$data);
	}
	//crmv@153321_5e
	//crmv@97575
	function setSubprocess($id,$elementid,$subprocess) {
		global $adb, $table_prefix;
		
		// TODO use only the table _processmaker_rel and delete the _subprocesses
		$adb->pquery("delete from {$table_prefix}_subprocesses where processid = ? and elementid = ?",array($id,$elementid));
		$adb->pquery("insert into {$table_prefix}_subprocesses(processid,elementid,subprocess) values(?,?,?)",array($id,$elementid,$subprocess));
		
		$result = $adb->pquery("select id from {$table_prefix}_processmaker_rel where processid = ? and elementid = ?",array($id,$elementid));
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("update {$table_prefix}_processmaker_rel set related = ?, related_role = ? where id = ?",array($subprocess,'son',$adb->query_result($result,0,'id')));
		} else {
			$adb->pquery("insert into {$table_prefix}_processmaker_rel(id,processid,elementid,related,related_role) values(?,?,?,?,?)",array($adb->getUniqueID("{$table_prefix}_processmaker_rel"),$id,$elementid,$subprocess,'son'));
		}
		$result = $adb->pquery("select id from {$table_prefix}_processmaker_rel where related = ? and elementid = ?",array($id,$elementid));
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("update {$table_prefix}_processmaker_rel set processid = ?, related_role = ? where id = ?",array($subprocess,'father',$adb->query_result($result,0,'id')));
		} else {
			$adb->pquery("insert into {$table_prefix}_processmaker_rel(id,processid,elementid,related,related_role) values(?,?,?,?,?)",array($adb->getUniqueID("{$table_prefix}_processmaker_rel"),$subprocess,$elementid,$id,'father'));
		}
	}
	//crmv@136524
	function unsetSubprocess($id,$elementid) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_subprocesses where processid = ? and elementid = ?",array($id,$elementid));
		$adb->pquery("delete from {$table_prefix}_processmaker_rel where processid = ? and elementid = ? and related_role = ?",array($id,$elementid,'son'));
		$adb->pquery("delete from {$table_prefix}_processmaker_rel where related = ? and elementid = ? and related_role = ?",array($id,$elementid,'father'));
	}
	//crmv@136524e
	function getSubprocesses($id,$elementid='') {
		global $adb, $table_prefix;
		$query = "select {$table_prefix}_subprocesses.*, {$table_prefix}_processmaker.name
			from {$table_prefix}_subprocesses
			inner join {$table_prefix}_processmaker on {$table_prefix}_processmaker.id = {$table_prefix}_subprocesses.subprocess
			where {$table_prefix}_subprocesses.processid = ?";
		$params = array($id);
		if (!empty($elementid)) {
			$query .= " and elementid = ?";
			$params[] = $elementid;
		}
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			$return = array();
			while($row=$adb->fetchByAssoc($result)) {
				if (!empty($elementid)) return $row;
				else $return[$row['elementid']] = $row;
			}
			return $return;
		}
		return false;
	}
	function getRelatedProcess($id,$meta='') {
		global $adb, $table_prefix;
		$query = "select {$table_prefix}_processmaker_rel.*, {$table_prefix}_processmaker.name
			from {$table_prefix}_processmaker_rel
			inner join {$table_prefix}_processmaker on {$table_prefix}_processmaker.id = {$table_prefix}_processmaker_rel.related
			where {$table_prefix}_processmaker_rel.processid = ?";
		$params = array($id);
		if (!empty($meta)) {
			$query .= " and {$table_prefix}_processmaker_rel.id = ?";
			$params[] = $meta;
		}
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			$return = array();
			while($row=$adb->fetchByAssoc($result)) {
				$return[$row['related']] = $row;
				// $return[] = $row;  //crmv@161211 TODO
			}
			return $return;
		}
		return false;
	}
	function clearSubProcesses($processid,&$vte_metadata,$structure) {	//crmv@136524
		$vte_metadata = Zend_Json::decode($vte_metadata);
		$structure = Zend_Json::decode($structure);
		foreach($vte_metadata as $elementid => &$metadata) {
			if ($structure['shapes'][$elementid]['type'] == 'SubProcess') {
				unset($metadata['subprocess']);
			}
		}
		$vte_metadata = Zend_Json::encode($vte_metadata);
	}
	function relateSubProcessesRun($running_processes) {
		global $adb, $table_prefix;
		if (!empty($running_processes)) {
			$all_running_process = array();
			foreach($running_processes as $info) {
				$all_running_process[$info['running_process']] = $info['processid'];
			}
			foreach($running_processes as $info) {
				if ($info['new']) {
					$new_running_process = $info['running_process'];
					$result = $adb->pquery("SELECT processid FROM {$table_prefix}_subprocesses
						INNER JOIN {$table_prefix}_running_processes ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_subprocesses.subprocess
						WHERE {$table_prefix}_running_processes.id = ?", array($new_running_process));
					if ($result && $adb->num_rows($result) > 0) {
						$father_processid = $adb->query_result($result,0,'processid');
						$father_running_process = array_search($father_processid, $all_running_process);
						if ($father_running_process !== false) {
							// set the father of the new running process
							$adb->pquery("update {$table_prefix}_running_processes set father = ? where id = ?", array($father_running_process,$new_running_process));
							// set the father in the Processes record (if exists)
							$father = $this->getProcessFatherRun($new_running_process);
							if (!empty($father)) {
								$adb->pquery("update {$table_prefix}_processes set father = ? where running_process = ?", array($father,$new_running_process));
							}
						}
					}
				}
			}
		}
	}
	function getProcessFatherRun($running_process='') {
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT {$table_prefix}_processes.processesid
			FROM {$table_prefix}_running_processes
			INNER JOIN {$table_prefix}_processes ON {$table_prefix}_processes.running_process = {$table_prefix}_running_processes.father
			WHERE {$table_prefix}_running_processes.id = ?", array($running_process));
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result,0,'processesid');
		}
		return false;
	}
	function getRelatedRunningProcess($running_process, $processid, $meta_processid = '') {  // crmv@206002
		global $adb, $table_prefix;
		$this->relateSubProcessesRun(ProcessMakerHandler::$running_processes);	// try to set sub processes
		$related = $this->getRelatedProcess($processid,$meta_processid);
		if ($related !== false) {
			$related = array_shift($related);
			if ($related['related_role'] == 'father') {
				$result = $adb->pquery("SELECT rel.id
					FROM {$table_prefix}_running_processes running_processes
					INNER JOIN {$table_prefix}_running_processes rel ON rel.id = running_processes.father
					WHERE running_processes.id = ? AND rel.processmakerid = ?",
					array($running_process,$related['related']));
			} elseif ($related['related_role'] == 'son') {
				$result = $adb->pquery("SELECT rel.id
					FROM {$table_prefix}_running_processes running_processes
					INNER JOIN {$table_prefix}_running_processes rel ON rel.father = running_processes.id
					WHERE running_processes.id = ? AND rel.processmakerid = ?",
					array($running_process,$related['related']));								
			}
			if ($result && $adb->num_rows($result) > 0) {
				$running_process = $adb->query_result($result,0,'id');
			}
			return $running_process;
		}
		return false;
	}
	//crmv@97575e
	function clearRecordsInvolved($id) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_processmaker_metarec where processid = ?", array($id));
	}
	
	// crmv@200009 crmv@204903
	function setRecordInvolved($id,$elementid,$text,$type,$module,$action,$start=0,$module2='',$exec_cond='') {
		global $adb, $table_prefix, $default_charset;	//crmv@132240
		
		$result = $adb->pquery("select id from {$table_prefix}_processmaker_metarec where processid = ? and elementid = ? and action = ? and module = ?", array($id,$elementid,$action,$module));
		
		if ($result && $adb->num_rows($result) > 0) {
			$metarecid = $adb->query_result($result,0,'id');
			$adb->pquery("update {$table_prefix}_processmaker_metarec set module = ?, action = ?, start = ? where id = ? and processid = ? and elementid = ? and module = ?", array($module,$action,$start,$metarecid,$id,$elementid,$module));
		} else {
			$metarecid = $adb->getUniqueID("{$table_prefix}_processmaker_metarec");
			$adb->pquery("insert into {$table_prefix}_processmaker_metarec values (?,?,?,?,?,?,?,?)", array($metarecid,$id,$elementid,html_entity_decode($text,ENT_QUOTES,$default_charset),$type,$module,$action,$start));
		}
		
		if ($exec_cond == 'ON_RELATE_RECORD' && $module2 != '') {
			$result2 = $adb->limitpquery("select id from {$table_prefix}_processmaker_metarec where processid = ? and elementid = ? and action = ? and module <> ? ORDER BY id DESC", 0, 1, array($id,$elementid,$action, $module));
			if ($result2 && $adb->num_rows($result2) > 0) {
				$metarecid2 = $adb->query_result($result2,0,'id');
				$adb->pquery("update {$table_prefix}_processmaker_metarec set module = ?, action = ?, start = ? where id = ? and processid = ? and elementid = ?", array($module2,$action,0,$metarecid2,$id,$elementid));
			} else {
				$metarecid2 = $adb->getUniqueID("{$table_prefix}_processmaker_metarec");
				$adb->pquery("insert into {$table_prefix}_processmaker_metarec values (?,?,?,?,?,?,?,?)", array($metarecid2,$id,$elementid,html_entity_decode($text,ENT_QUOTES,$default_charset),$type,$module2,$action,0));	//crmv@132240
			}
		}
	}
	// crmv@200009e crmv@204903e
	
	function getRecordsInvolvedLabel($processid,$metaid,$row=array(),$related=false) {
		if (empty($row)) {
			global $adb, $table_prefix;
			$result = $adb->pquery("select * from {$table_prefix}_processmaker_metarec where id = ? and processid = ?", array($metaid,$processid));
			if ($result && $adb->num_rows($result) > 0) {
				$row = $adb->fetch_array_no_html($result);
			}
		}
		$label = '[$'.$row['id'].'] '.getTranslatedString($row['module'],$row['module']).' ('.$this->formatType($row['type'],true).': '.trim($row['text']).')';
		if ($related !== false) {
			/* crmv@161211 TODO
			 $structure = $this->getStructure($processid);
			 $related_label = $this->getElementTitle($structure['shapes'][$related['elementid']]);
			 $label = '('.$related_label.') '.$label;
			 */
			$label = $related['name'].' '.$label;	//crmv@139690
		}
		return $label;
	}
	function getRecordsInvolved($id,$related=false,$elementid='',$action='',$inventory_filter=false) { // crmv@195745
		global $adb, $table_prefix;
		$query = "select * from {$table_prefix}_processmaker_metarec where processid = ?";
		$params = array($id);
		if (!empty($elementid)) {
			$query .= " and elementid = ?";
			$params[] = $elementid;
		}
		if ($action !== '') {
			$query .= " and action = ?";
			$params[] = $action;
		}
		// crmv@195745
		if ($inventory_filter) {
			$inventory_modules = getInventoryModules();
			$query .= " and module in (".generateQuestionMarks($inventory_modules).")";
			$params[] = $inventory_modules;
		}

        // crmv@195745e
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if ($row['start'] == '1' && empty($row['module'])) continue; //crmv@160368 hide record if start condition is ON_SUBPROCESS
				$records[] = array(
					'seq'=>$row['id'],
					'id'=>$row['elementid'],
					'text'=>$row['text'],
					'type'=>$row['type'],
					'module'=>$row['module'],
					'action'=>$row['action'],
					'translatedModule'=>getTranslatedString($row['module'],$row['module']),
					'label'=>$this->getRecordsInvolvedLabel($id,$row['id'],$row),
				);
			}
		}
		//crmv@98809
		if ($related) {
			$processes = $this->getRelatedProcess($id);
			if (!empty($processes)) {
				foreach($processes as $info) {
					// crmv@195745
					$query = "select * from {$table_prefix}_processmaker_metarec where processid = ?";
					$params = array($info['related']);
					if ($inventory_filter) {
						$query .= " and module in (".generateQuestionMarks($inventory_modules).")";
						$params[] = $inventory_modules;
					}
					$result = $adb->pquery($query, $params);
					// crmv@195745e
					if ($result && $adb->num_rows($result) > 0) {
						while($row=$adb->fetchByAssoc($result)) {
							if ($row['start'] == '1' && empty($row['module'])) continue; //crmv@160368 crmv@161211 hide record if start condition is ON_SUBPROCESS
							$records[] = array(
								'meta_processid'=>$info['id'],
								'seq'=>$row['id'],
								'id'=>$row['elementid'],
								'text'=>$row['text'],
								'type'=>$row['type'],
								'module'=>$row['module'],
								'action'=>$row['action'],
								'translatedModule'=>getTranslatedString($row['module'],$row['module']),
								'label'=>$this->getRecordsInvolvedLabel($id,$row['id'],$row,$info),
							);
						}
					}
				}
			}
		}
		//crmv@98809e
		return $records;
	}
	function getRecordsInvolvedModules($id, $related=false, $add_reference=false) {
		$involved_records = $this->getRecordsInvolved($id,$related);
		$modules_involved_records = array();
		if (!empty($involved_records)) {
			foreach($involved_records as $involved_record) {
				if (!in_array($involved_record['module'],$this->versioning_skip_modules) && !in_array($involved_record['module'],$modules_involved_records)) $modules_involved_records[] = $involved_record['module']; //crmv@180014
				if ($add_reference) {
					$relationManager = RelationManager::getInstance();
					$relationsN1 = $relationManager->getRelations($involved_record['module'], ModuleRelation::$TYPE_NTO1);
					if (!empty($relationsN1)) {
						foreach($relationsN1 as $rel) {
							if (!in_array($rel->getSecondModule(),$this->versioning_skip_modules) && !in_array($rel->getSecondModule(),$modules_involved_records)) $modules_involved_records[] = $rel->getSecondModule(); //crmv@180014
						}
					}
				}
			}
		}
		return $modules_involved_records;
	}
	function getRecordsInvolvedOptions($id, $selected_value='', $startTask=false, $excluded_values=array(), $onlyModules = array(), $add_reference=false, $inventory_filter=false) { // crmv@126696 crmv@135190 crmv@195745
		global $adb, $table_prefix;	//crmv@135190
		$records = $this->getRecordsInvolved($id,true,'','',$inventory_filter);	//crmv@160368 crmv@195745
		$values = array(''=>array(getTranslatedString('LBL_PLEASE_SELECT'),''));
		if ($startTask) {
			($selected_value == 'current') ? $selected = 'selected' : $selected = '';
			$values['current'] = array(getTranslatedString('LBL_PMH_CURRENT_ENTITY','Settings'), $selected);
		} else {
			if (!empty($records)) {
				foreach($records as $r) {
					$key = $r['seq'].':'.$r['module'].((empty($r['meta_processid']))?'':'::'.$r['meta_processid']); //crmv@160368
					if (!empty($onlyModules) && !in_array($r['module'], $onlyModules)) continue; // crmv@126696
					if (!empty($excluded_values) && in_array($key,$excluded_values)) continue;
					($selected_value == $key) ? $selected = 'selected' : $selected = '';
					//crmv@135190
					if ($add_reference) {
						$tmp_values = array();
						if (empty($referenceInfo[$r['module']])) {
							// crmv@195745
							($inventory_filter) ? $relmodules = getInventoryModules() : $relmodules = array();
							$relationManager = RelationManager::getInstance();
							$relationsN1 = $relationManager->getRelations($r['module'], ModuleRelation::$TYPE_NTO1, $relmodules);
							// crmv@195745e
							if (!empty($relationsN1)) {
								$referenceInfo[$r['module']] = array();
								foreach($relationsN1 as $rel) {
									if (!isset($referenceInfo[$r['module']][$rel->fieldid])) {
										$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where fieldid = ?", array($rel->fieldid));
										if ($result && $adb->num_rows($result) > 0) {
											$referenceInfo[$r['module']][$rel->fieldid] = array(
												'fieldid'=>$rel->fieldid,
												'fieldlabel'=>getTranslatedString($adb->query_result($result,0,'fieldlabel'),$rel->getFirstModule()),
											);
										}
									}
								}
							}
						}
						$tmp_values[$key] = array('ID', $selected);
						if (!empty($referenceInfo[$r['module']])) {
							foreach($referenceInfo[$r['module']] as $rr) {
								//crmv@160368 crmv@160859
								$relatedmodres = $adb->pquery("SELECT relmodule FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?", Array($rr['fieldid']));
								if ($relatedmodres && $adb->num_rows($relatedmodres) > 1) {
									// check for multiple related modules
									while($row_relmod=$adb->fetchByAssoc($relatedmodres)) {
										$referenceKey = $r['seq'].':'.$r['module'].':'.$rr['fieldid'].':'.$r['meta_processid'].':'.$row_relmod['relmodule'];
										($selected_value == $referenceKey) ? $selected = 'selected' : $selected = '';
										$tmp_values[$referenceKey] = array($rr['fieldlabel'].' ('.getTranslatedString($row_relmod['relmodule'],$row_relmod['relmodule']).')', $selected);
									}
								} else {
									$referenceKey = $r['seq'].':'.$r['module'].':'.$rr['fieldid'].((empty($r['meta_processid']))?'':':'.$r['meta_processid']);
									($selected_value == $referenceKey) ? $selected = 'selected' : $selected = '';
									$tmp_values[$referenceKey] = array($rr['fieldlabel'], $selected);
								}
								//crmv@160368e crmv@160859e
							}
						}
						$values[] = array(
							'group'=>$r['label'],
							'values'=>$tmp_values,
						);
					} else {
						$values[$key] = array($r['label'], $selected);
					}
					//crmv@135190e
				}
			}
		}
		return $values;
	}
	function getOwnerFieldOptions($id, $selected_value='', $startTask=false, $related=false) {
		global $adb, $table_prefix, $app_strings;
		$records = $this->getRecordsInvolved($id,$related);
		$values = array();
		$values[''][''] = array(getTranslatedString('LBL_PLEASE_SELECT'),'');
		if ($startTask) {
			($selected_value == 'current') ? $selected = 'selected' : $selected = '';
			$values['']['current'] = array(getTranslatedString('LBL_PMH_CURRENT_ENTITY','Settings'), $selected);
		} else {
			if (!empty($records)) {
				foreach($records as $r) {
					$moduleInstance = Vtecrm_Module::getInstance($r['module']);
					$result = $adb->pquery("select fieldname, fieldlabel from {$table_prefix}_field where tabid = ? and uitype in (?,?,?,?,?)", array($moduleInstance->id,53,52,51,50,77));	//crmv@101683
					if ($result && $adb->num_rows($result) > 0) {
						while($row=$adb->fetchByAssoc($result)) {
							$key = $r['meta_processid'].':'.$r['seq'].':'.$r['module'].':'.$row['fieldname'];
							($selected_value == $key) ? $selected = 'selected' : $selected = '';
							$values[$r['label']][$key] = array(getTranslatedString($row['fieldlabel'],$r['module']), $selected);
						}
					}
				}
			}
		}
		//crmv@100591
		$elementsActors = $this->getElementsActors($id);
		if (!empty($elementsActors)) {
			foreach($elementsActors as $key => $value) {
				($selected_value == $key) ? $selected = 'selected' : $selected = '';
				$values[$app_strings['LBL_PM_ELEMENTS_ACTORS']][$key] = array($value, $selected);
			}
		}
		//crmv@100591e
		return $values;
	}
	
	/*
	 * crmv@109589
	 * if vte_metadata is not empty remove shapes deleted from vte_metadata, helper, vte_processmaker_metarec, vte_process_dynaform_meta, ecc.
	 */
	function saveStructure($id,$value) {
		global $adb, $table_prefix, $default_charset;	//crmv@132240
		$columns = array('structure = ?');
		$values = array($value);
		
		$structure = Zend_Json::decode($value);
		$shapes = array_keys($structure['shapes']);
		
		$retrieve = $this->retrieve($id);
		$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
		if (!empty($vte_metadata)) {
			foreach($vte_metadata as $elementid => $info) {
				if (!in_array($elementid,$shapes)) {
					unset($vte_metadata[$elementid]);
				}
			}
			$columns[] = 'vte_metadata = ?';
			$values[] = Zend_Json::encode($vte_metadata);
		}
		$helper = Zend_Json::decode($retrieve['helper']);
		if (!empty($helper)) {
			foreach($helper as $elementid => $info) {
				if (!in_array($elementid,$shapes)) {
					unset($helper[$elementid]);
				}
			}
			$columns[] = 'helper = ?';
			$values[] = Zend_Json::encode($helper);
		}
		$adb->pquery("update {$this->table_name} set ".implode(',',$columns)." where id = ?", array($values,$id));
		
		// clean _processmaker_metarec
		$result = $adb->pquery("select id, elementid from {$table_prefix}_processmaker_metarec where processid = ?", array($id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['elementid'],$shapes)) {
					// TODO delete from other tables (ex. _processmaker_rec)
					$adb->pquery("delete from {$table_prefix}_processmaker_metarec where id = ? and processid = ?", array($row['id'],$id));
				}
			}
		}
		// clean _process_dynaform_meta
		$result = $adb->pquery("select id, elementid from {$table_prefix}_process_dynaform_meta where processid = ?", array($id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['elementid'],$shapes)) {
					// TODO delete from other tables (ex. _process_dynaform)
					$adb->pquery("delete from {$table_prefix}_process_dynaform_meta where id = ? and processid = ?", array($row['id'],$id));
				}
			}
		}
		// crmv@146671
		// clean _process_extws_meta
		$result = $adb->pquery("select id, elementid from {$table_prefix}_process_extws_meta where processid = ?", array($id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['elementid'],$shapes)) {
					$adb->pquery("delete from {$table_prefix}_process_extws_meta where id = ? and processid = ?", array($row['id'],$id));
				}
			}
		}
		// crmv@146671e
		//crmv@136524 clean subprocesses
		$result = $adb->pquery("select elementid from {$table_prefix}_subprocesses where processid = ?", array($id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['elementid'],$shapes)) {
					$this->unsetSubprocess($id, $row['elementid']);
				}
			}
		}
		//crmv@136524e
		//crmv@110601 update meta info
		$adb->pquery("update {$table_prefix}_processmaker_metarec set start = ? where processid = ?", array(0,$id));
		foreach($structure['shapes'] as $shapeid => $shape) {
			$adb->pquery("update {$table_prefix}_processmaker_metarec set type = ?, text = ? where processid = ? and elementid = ?", array($shape['type'],html_entity_decode($shape['text'],ENT_QUOTES,$default_charset),$id,$shapeid));	//crmv@132240
			$adb->pquery("update {$table_prefix}_process_dynaform_meta set type = ?, text = ? where processid = ? and elementid = ?", array($shape['type'],html_entity_decode($shape['text'],ENT_QUOTES,$default_charset),$id,$shapeid));	//crmv@132240
			// crmv@199886: removed crmv@146671
			if ($shape['type'] == 'StartEvent') {
				$outgoing = $this->getOutgoing($id,$shapeid);
				foreach($outgoing as $out) {
					$adb->pquery("update {$table_prefix}_processmaker_metarec set start = ? where processid = ? and elementid = ?", array(1,$id,$out['shape']['id']));
				}
			}
		}
		//crmv@110601e
	}
	// crmv@129138
	function getStructure($id,$running_process='') {
		global $adb, $table_prefix;
		static $structCache = array();
		if (empty($running_process)) $running_process = 'curr';
		if (!isset($structCache[$id][$running_process])) {
			($running_process != 'curr') ? $pmvh_id = $this->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $pmvh_id = '';
			if (!empty($pmvh_id)) {
				$result = $adb->pquery("select structure from {$table_prefix}_processmaker_versions where processmakerid = ? and xml_version = ?", array($id,$pmvh_id));
			} else {
				$result = $adb->pquery("select structure from {$this->table_name} where id = ?", array($id));
			}
			if ($result && $adb->num_rows($result) > 0) {
				$structCache[$id][$running_process] = Zend_Json::decode($adb->query_result_no_html($result,0,'structure'));
			} else {
				$structCache[$id][$running_process] = false;
			}
		}
		return $structCache[$id][$running_process];
	}
	// crmv@129138e
	//crmv@185361
	function getStructureElementInfo($id,$elementId,$type,$running_process='') {
		$structure = $this->getStructure($id,$running_process);
		if (isset($structure[$type][$elementId]))
			return array_merge(array('id'=>$elementId),$structure[$type][$elementId]);
		else
			return false;
	}
	//crmv@185361e
	function getIncoming($id,$shapeid,$running_process='') {
		$return = array();
		$structure = $this->getStructure($id,$running_process);
		$outgoing = $structure['tree'][$shapeid]['incoming'];
		if (!empty($outgoing)) {
			foreach($outgoing as $connection => $shape) {
				$return[] = array('connection'=>$this->getStructureElementInfo($id,$connection,'connections',$running_process),'shape'=>$this->getStructureElementInfo($id,$shape,'shapes',$running_process));
			}
		}
		return $return;
	}
	function getOutgoing($id,$shapeid,$running_process='') {
		$return = array();
		$structure = $this->getStructure($id,$running_process);
		$outgoing = $structure['tree'][$shapeid]['outgoing'];
		if (!empty($outgoing)) {
			foreach($outgoing as $connection => $shape) {
				$connection_info = $this->getStructureElementInfo($id,$connection,'connections',$running_process);
				if (in_array($connection_info['type'],array('SequenceFlow','MessageFlow'))) {	// SequenceFlow is the standard arrow, MessageFlow is for link Participants
					$return[] = array('connection'=>$connection_info,'shape'=>$this->getStructureElementInfo($id,$shape,'shapes',$running_process));
				}
			}
		}
		return $return;
	}
	//crmv@103534
	function getParallelFlowSons($id,$running_process,$gateway,$elementid,&$elementsons,&$conditionssons) {
		static $checkInfiniteLoops = array();
		$structure = $this->getStructureElementInfo($id,$elementid,'shapes',$running_process);
		if ($elementid == $gateway || $this->isEndTask($structure['type']) || $structure['type'] == 'ParallelGateway' || ($this->getEngineType($structure) == 'Gateway' && $elementid == $this->getClosingParallelGateway($id,$gateway,$running_process))) {
			if ($structure['type'] == 'ParallelGateway') {	// add also the next Parallel Gateway before end the recursion
				$elementsons[] = $elementid;
			}
			return;
		} else {
			if (!isset($checkInfiniteLoops[$id][$gateway][$elementid])) {
				$checkInfiniteLoops[$id][$gateway][$elementid] = 0;
			}
			if ($this->getEngineType($structure) == 'Action') {
				if (!in_array($elementid,$elementsons)) $elementsons[] = $elementid;
				else $checkInfiniteLoops[$id][$gateway][$elementid]++;
			}
			if ($this->getEngineType($structure) == 'Condition') {
				if (!in_array($elementid,$conditionssons)) $conditionssons[] = $elementid;
				else $checkInfiniteLoops[$id][$gateway][$elementid]++;
			}
			if ($checkInfiniteLoops[$id][$gateway][$elementid] > 10) {
				return;
			}
			$outgoings = $this->getOutgoing($id,$elementid,$running_process);
			if (!empty($outgoings)) {
				foreach($outgoings as $outgoing) {
					$this->getParallelFlowSons($id,$running_process,$gateway,$outgoing['shape']['id'],$elementsons,$conditionssons);
				}
			}
		}
	}
	function getClosingParallelGateway($id,$gateway,$running_process) {
		$metadata = $this->getMetadata($id,$gateway,$running_process);
		return $metadata['closing_gateway'];
	}
	function searchParentParallelFlow($running_process,$gateway) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select processesid, elementsons from {$table_prefix}_process_gateway_conn where running_process = ? and elementid <> ?", array($running_process,$gateway));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$elementsons = Zend_Json::decode($row['elementsons']);
				if (in_array($gateway,$elementsons)) {
					// the gateway is son of this flow
					return $row['processesid'];
				}
			}
		}
		return false;
	}
	//crmv@103534e
	
	function actionEdit($id,$elementid,$action_type,$action_id='', $actionOptions = array()){
		global $mod_strings, $app_strings, $theme;
		
		$actionType = $this->getActionTypes($action_type);
		require_once($actionType['php_file']);
		$action = new $actionType['class']($actionOptions);
		
		$smarty = new VteSmarty();
		
		$smarty->assign('THEME',$theme);
		$smarty->assign('APP',$app_strings);
		$smarty->assign('MOD',$mod_strings);
		$smarty->assign("PAGE_TITLE", $mod_strings['LBL_PM_ACTION'].": ".$mod_strings['LBL_PM_ACTION_'.$action_type]);
		$smarty->assign("HEADER_Z_INDEX", 1);
		$buttons = '
			<table class="tableHeading" width="100%" border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td class="small" align="right">
						<input type="button" class="crmButton small save" value="'.$app_strings['LBL_SAVE_BUTTON_LABEL'].'" onclick="ActionTaskScript.saveaction(\''.$id.'\',\''.$elementid.'\',\''.$action_type.'\',\''.$action_id.'\',jQuery(\'#action_title\').val());">
						<input type="button" class="crmbutton small cancel" value="'.$app_strings['LBL_CANCEL_BUTTON_LABEL'].'" onclick="history.back(-1);">
					</td>
				</tr>
			</table>';
		$smarty->assign("BUTTON_LIST", $buttons);
		
		include_once('vtlib/Vtecrm/Link.php');
		$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERSCRIPT'));
		$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
		
		//crmv@131239
		$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
		$smarty->assign('JS_GLOBAL_VARS', Zend_Json::encode($JSGlobals));
		//crmv@131239e
		
		$smarty->assign('ID',$id);
		$smarty->assign('ELEMENTID',$elementid);
		$smarty->assign('ACTIONTYPE',$action_type);
		$smarty->assign('ACTIONID',$action_id);
		$smarty->assign('TEMPLATE',$actionType['tpl_file']);
		$action->edit($smarty,$id,$elementid,$this->retrieve($id),$action_type,$action_id);
		$smarty->display("Settings/ProcessMaker/Metadata/ActionTaskEdit.tpl");
		
		include('themes/SmallFooter.php');
	}
	
	// crmv@146671
	function actionSave($request){
		$action_type = vtlib_purify($request['meta_action']['action_type']);
		
		$actionType = $this->getActionTypes($action_type);
		if (!empty($actionType)) {
			require_once($actionType['php_file']);
			
			$actionOptions = array();
			$action = new $actionType['class']($actionOptions);
		}
		// use the base class in case it's not extended properly
		if (empty($actionType) || !method_exists($action, 'save')) {
			require_once('modules/Settings/ProcessMaker/actions/Base.php');
			$action = new PMActionBase($actionOptions);
		}
		
		//crmv@153321_5 clear only the processmaker_entity_options_ session beacause the processmaker_describe_modules_ session will be add the new module automatically
		if (in_array($action_type,array('Create','Update')) && $request['action_id'] !== '') {
			$retrieve = $this->retrieve($request['id']);
			$element_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			$saved_meta_action = $element_metadata[$request['elementid']]['actions'][$request['action_id']];
		}
		if (empty($request['action_id'])																							// on create of a new action
			|| $action_type == 'CallExtWS'																							// on create or update of CallExtWS
			|| ($action_type == 'Create' && $request['meta_action']['form_module'] != $saved_meta_action['form_module'])			// on change of the module to create
			|| ($action_type == 'Update' && $request['meta_action']['record_involved'] != $saved_meta_action['record_involved'])	// on change of the record to update
		) {
			$this->clearCache('processmaker_entity_options_'.$request['id']);
		}
		//crmv@153321_5e
		
		return $action->save($request);
	}
	// crmv@146671e
	
	function actionDelete($id,$elementid,$action_id){
		$retrieve = $this->retrieve($id);
		$element_metadata = Zend_Json::decode($retrieve['vte_metadata']);
		unset($element_metadata[$elementid]['actions'][$action_id]);
		$retrieve['vte_metadata'] = Zend_Json::encode($element_metadata);
		$retrieve['pending_changes'] = 1;
		$this->edit($id,$retrieve);
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$record_involved = $this->getRecordsInvolved($id,false,$elementid,$action_id);
		$metaid = $record_involved[0]['seq'];
		if ($metaid !== '') {
			global $adb, $table_prefix;
			$adb->pquery("delete from {$table_prefix}_processmaker_metarec where id = ? and processid = ?", array($metaid,$id));
			$adb->pquery("delete from {$table_prefix}_processmaker_rec where id = ?", array($metaid));
		}
		
		//crmv@153321_5 clear only the processmaker_entity_options_ session beacause the processmaker_describe_modules_ session will be add the new module automatically
		$this->clearCache('processmaker_entity_options_'.$id);
	}
	
	//crmv@108227 crmv@106857
	function getModuleList($mode='',$selectedvalue='') {
		global $adb, $table_prefix;
		$modules_not_supported = $this->modules_not_supported;
		// skip also light modules
		require_once('include/utils/ModLightUtils.php'); 
		$MLUtils = ModLightUtils::getInstance();
		$light_modules = $MLUtils->getModuleList();
		if (!empty($light_modules)) $modules_not_supported = array_merge($modules_not_supported,$light_modules);

		$sql="select distinct {$table_prefix}_field.tabid, name
				from {$table_prefix}_field 
				inner join {$table_prefix}_tab on {$table_prefix}_field.tabid={$table_prefix}_tab.tabid 
				where {$table_prefix}_tab.name not in (".generateQuestionMarks($modules_not_supported).") and {$table_prefix}_tab.isentitytype=1 and {$table_prefix}_tab.presence in (0,2)";
		$it = new SqlResultIterator($adb, $adb->pquery($sql,array($modules_not_supported)));
		if ($mode == 'picklist') {
			$modules = array(''=>array(getTranslatedString('LBL_NONE'),''));
			foreach($it as $row){
				($selectedvalue == $row->name) ? $selected = 'selected' : $selected = '';
				$modules[$row->name] = array(getSingleModuleName($row->name),$selected);
			}
			asort($modules);
		} else {
			$modules = array();
			foreach($it as $row){
				$modules[] = $row->name;
			}
		}
		return $modules;
	}
	//crmv@108227e crmv@106857e
	
	function translateConditionFieldname($fieldname, $module, $processmakerid='', $metaid='', $running_process='') {
		global $adb, $table_prefix;
		$related_module = false;
		if (strpos($fieldname,' : ') !== false && strpos($fieldname,' (') !== false && strpos($fieldname,') ') !== false) {
			list($columnname,$tmp) = explode(' : ',$fieldname);
			list($module,$fieldname) = explode(') ',$tmp);
			$module = ltrim($module,'(');
			$related_module = true;
		}
		if ($module == 'DynaForm') {
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$blocks = $processDynaFormObj->getStructure($processmakerid, false, $metaid, $running_process);
			if (!empty($blocks)) {
				foreach($blocks as $block) {
					foreach($block['fields'] as $field) {
						if ($field['fieldname'] == $fieldname) {
							$trans_field = $field['label'];
							if ($related_module) $trans_field .= ' ('.getSingleModuleName($module).')';
							break(2);
						}
					}
				}
			}
		} else {
			$moduleInstance = Vtecrm_Module::getInstance($module);
			$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$fieldname));
			if ($result && $adb->num_rows($result) > 0) {
				$trans_field = getTranslatedString($adb->query_result($result,0,'fieldlabel'),$module);
				if ($related_module) $trans_field .= ' ('.getSingleModuleName($module).')';
			}
		}
		return $trans_field;
	}
	function translateConditionOperation($value) {
		global $adb, $current_language;
		$labels = array(
			'is'=>'EQUALS',
			'equal to'=>'EQUALS',
			'is not'=>'NOT_EQUALS_TO',
			'does not equal'=>'NOT_EQUALS_TO',
			'has changed'=>'HAS_CHANGED',	//crmv@56962
			'contains'=>'CONTAINS',
			'does not contain'=>'DOES_NOT_CONTAINS',
			'starts with'=>'STARTS_WITH',
			'ends with'=>'ENDS_WITH',	//crmv@56962
			'less than'=>'LESS_THAN',
			'greater than'=>'GREATER_THAN',
			'less than or equal to'=>'LESS_OR_EQUALS',
			'greater than or equal to'=>'GREATER_OR_EQUALS',
		);
		static $trans_labels = array();
		if (empty($trans_labels)) {
			$res = $adb->pquery("select module, label, trans_label from sdk_language where language = ? and module = ? and label in (".generateQuestionMarks($labels).") order by module", array($current_language,'ALERT_ARR',$labels));
			if ($res && $adb->num_rows($res) > 0) {
				while($row=$adb->fetchByAssoc($res,-1,false)) {
					$trans_labels[$row['label']] = $row['trans_label'];
				}
			}
		}
		return $trans_labels[$labels[$value]];
	}
	// crmv@129138
	public static function getFieldWSType($module, $fieldname) {
		global $adb, $table_prefix;
		static $wsCache = array();
		$key = $module.'_'.$fieldname;
		if (!isset($wsCache[$key])) {
			$moduleInstance = Vtecrm_Module::getInstance($module);
			$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$fieldname));
			$wsField = WebserviceField::fromQueryResult($adb,$result,0);
			$wsCache[$key] = $wsField->getFieldDataType();
		}
		return $wsCache[$key];
	}
	
	// crmv@195745
	public static function getFieldRawValue($module, $fieldname, $crmid) {
		global $adb, $table_prefix;
		$wsField = WebserviceField::fromCachedWS($module, $fieldname);
		if ($wsField) {
			$focus = CRMEntity::getInstance($module);
			$index = $focus->tab_name_index[$wsField->getTableName()];
			$res = $adb->pquery("SELECT {$wsField->getColumnName()} as val FROM {$wsField->getTableName()} WHERE $index = ?", array($crmid));
			return $adb->query_result_no_html($res, 0, 'val');
		}
		return null;
	}
	// crmv@195745e
	
	function translateConditionValue($fieldname, $module, $value) {
		if (self::getFieldWSType($module, $fieldname) == 'picklist') {
			$value = getTranslatedString($value,$module);
		}
		if ($value == '') $value = getTranslatedString('LBL_EMPTY_LABEL','Charts');
		return $value;
	}
	// crmv@129138e
	function translateConditionGlue($glue) {
		static $trans_glue = array();
		if (empty($trans_glue)) {
			$trans_glue = array(
				'and'=>getTranslatedString('LBL_AND'),
				'or'=>getTranslatedString('LBL_OR'),
			);
		}
		return $trans_glue[$glue];
	}
	function translateConditions($id,$elementid,$running_process,$metadata='') {
		if (empty($metadata)) $metadata = $this->getMetadata($id,$elementid,$running_process);
		$conditions = Zend_Json::decode($metadata['conditions']);
		if (strpos($metadata['moduleName'],':') !== false) {
			list($entityId,$module) = explode(':',$metadata['moduleName']);
		} else {
			$module = $metadata['moduleName'];
		}
		$c = array();
		if (!empty($conditions)) {
			$i=0;
			foreach($conditions as $condition) {
				$sub_conditions = $condition['conditions'];
				$label = '';
				foreach($sub_conditions as $ii => $sub_condition) {
					$label .= $this->translateConditionFieldname($sub_condition['fieldname'],$module,$id,$entityId,$running_process).' '.$this->translateConditionOperation($sub_condition['operation']).' <i>'.$this->translateConditionValue($sub_condition['fieldname'],$module,$sub_condition['value']).'</i>';
					if ($ii < count($sub_conditions)) $label .= ' '.$this->translateConditionGlue($sub_condition['glue']).' ';
				}
				if ($i < count($conditions)-1) {
					if (count($sub_conditions) > 1) $label = "($label)";
					$label .= ' '.$this->translateConditionGlue($condition['glue']);
				}
				$c[] = $label;
				$i++;
			}
		}
		return $c;
	}
	function getGatewayConditions($id,$elementid,$vte_metadata_arr,&$show_required2go_check,$running_process='') {
		$incoming = $this->getIncoming($id,$elementid,$running_process);
		$groups = array();
		$j = 0;
		$enable_cond_else = true;	//crmv@114116
		foreach($incoming as $inc) {
			$metadata = $this->getMetadata($id,$inc['shape']['id'],$running_process);
			if ($metadata['execution_condition'] == 'EVERY_TIME') $enable_cond_else = false;	//crmv@114116
			$conditions = Zend_Json::decode($metadata['conditions']);
			if (strpos($metadata['moduleName'],':') !== false) {
				list($entityId,$module) = explode(':',$metadata['moduleName']);
			} else {
				$module = $metadata['moduleName'];
			}
			$c = array();
			if (!empty($conditions)) {
				$all_or = true;
				for($i=0;$i<count($conditions)-1;$i++) {
					if ($conditions[$i]['glue'] != 'or') $all_or = false;
				}
				if (!$all_or) {
					$c[] = array('label'=>getTranslatedString('LBL_EXCLUSIVEGATEWAY_SUCCESS','Settings'),'cond'=>'cond_all','elementid'=>$vte_metadata_arr['cond_all']);
				} else {
					$i=0;
					foreach($conditions as $condition) {
						$sub_conditions = $condition['conditions'];
						$label = '';
						foreach($sub_conditions as $ii => $sub_condition) {
							$label .= $this->translateConditionFieldname($sub_condition['fieldname'],$module,$id,$entityId,$running_process).' '.$this->translateConditionOperation($sub_condition['operation']).' <i>'.$this->translateConditionValue($sub_condition['fieldname'],$module,$sub_condition['value']).'</i>';
							if ($ii < count($sub_conditions)) $label .= ' '.$this->translateConditionGlue($sub_condition['glue']).' ';
						}
						$c[] = array(
							'label'=>$label,
							'cond'=>"cond_{$j}_{$i}",
							'elementid'=>$vte_metadata_arr["cond_{$j}_{$i}"],
							'json_condition'=>Zend_Json::encode($sub_conditions)
						);
						$i++;
					}
				}
			}
			if (!empty($c)) {
				$groups[$j] = array(
					'elementid'=>$inc['shape']['id'],
					'metaid'=>$metadata['moduleName'],
					'name'=>$inc['shape']['text'],
					'conditions'=>$c,
					//'required2go'=>$vte_metadata_arr["required2go_{$j}"],
				);
			}
			$j++;
		}
		if (!empty($groups) && $enable_cond_else) {	//crmv@114116
			$groups[] = array(
				'name'=>'',
				'conditions'=>array(array('label'=>getTranslatedString('LBL_EXCLUSIVEGATEWAY_OTHER','Settings'),'cond'=>'cond_else','elementid'=>$vte_metadata_arr['cond_else'])),
			);
		}
		if ($j > 1) {
			$show_required2go_check = true;
		}
		return $groups;
	}
	
	//crmv@106857
	function evaluateCondition($entityCache, $id, $conditions, $cycleIndex=null) {
		global $current_user;
		$conditions = Zend_Json::decode($conditions);
		if (empty($conditions)) return true;
		$this->debug('Conditions', $conditions, true); // crmv@189728
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$PMUtils->setDefaultDataFormat();
		$entityData = $entityCache->forId($id);
		$PMUtils->restoreDataFormat();
		$data = $entityData->getData();

		$i = 0;
		$string = "\$result = ";
		foreach($conditions as $condition) {
			$string .= '(';
			$j = 0;
			foreach($condition['conditions'] as $sub_condition) {
				if (strpos($sub_condition['fieldname'],'sdk:') !== false) {
					$sdkTaskConditions = SDK::getProcessMakerTaskConditions();
					list($tmp,$sdkId) = explode(':',$sub_condition['fieldname']);
					if (isset($sdkTaskConditions[$sdkId])) {
						require_once($sdkTaskConditions[$sdkId]['src']);
						$return = call_user_func_array($sdkTaskConditions[$sdkId]['funct'], array($entityData->moduleName,$id,$entityData->data));
						$entityData->data[$sub_condition['fieldname']] = $return;
					}
				}
				if (strpos($sub_condition['fieldname'],'::') !== false) {
					list($tfield, $tcol) = explode('::',$sub_condition['fieldname']);
					if (stripos($tfield,'ml') !== false) {
						if (empty($id)) {
							$replacement = $this->applyTableFieldFunct('', $entityData->data[$tfield], $tfield, $tcol.':'.$sub_condition['tabfieldopt'].':'.$sub_condition['tabfieldseq'], $cycleIndex);
						} else {
							$replacement = $this->replaceTableFieldTag($id, $tfield, $tcol.':'.$sub_condition['tabfieldopt'].':'.$sub_condition['tabfieldseq']);
						}
					} else {
						$replacement = $this->applyTableFieldFunct('dynaform', $entityData->data[$tfield], $tfield, $tcol.':'.$sub_condition['tabfieldopt'].':'.$sub_condition['tabfieldseq'], $cycleIndex);
					}
					require_once("modules/Settings/ProcessMaker/actions/Cycle.php");
					require_once("modules/com_workflow/VTJsonCondition.inc");//crmv@207901
					//crmv@121616
					if (is_array($replacement) && in_array($sub_condition['tabfieldopt'],array('all','at_least_one'))) {
						$sub_condition_res = false;
						if (!empty($replacement)) {
							if ($sub_condition['tabfieldopt'] == 'all') {
								$sub_condition_res = true;
								foreach($replacement as $temp_row) {
									$temp_fieldname = substr($sub_condition['fieldname'],strpos($sub_condition['fieldname'],'::')+2);
									$tmpEntityCache = new PMCycleWorkflowEntity($current_user, array($sub_condition['fieldname']=>$temp_row[$temp_fieldname]));
									$workflow_condition = new VTJsonCondition();
									if ($workflow_condition->checkCondition($tmpEntityCache, $sub_condition) !== true) {
										$sub_condition_res = false;
										break;
									}
								}
							} elseif ($sub_condition['tabfieldopt'] == 'at_least_one') {
								foreach($replacement as $temp_row) {
									$temp_fieldname = substr($sub_condition['fieldname'],strpos($sub_condition['fieldname'],'::')+2);
									$tmpEntityCache = new PMCycleWorkflowEntity($current_user, array($sub_condition['fieldname']=>$temp_row[$temp_fieldname]));
									$workflow_condition = new VTJsonCondition();
									if ($workflow_condition->checkCondition($tmpEntityCache, $sub_condition) === true) {
										$sub_condition_res = true;
										break;
									}
								}
							}
						}
						$string .= ($sub_condition_res) ? 'true' : 'false';
					} else {
						//crmv@121616e
						$tmpEntityCache = new PMCycleWorkflowEntity($current_user, array($sub_condition['fieldname']=>$replacement));
						$workflow_condition = new VTJsonCondition();
						$string .= ($workflow_condition->checkCondition($tmpEntityCache, $sub_condition) === true) ? 'true' : 'false';
					} //crmv@121616
				} else {
					//crmv@134058
					if (in_array($sub_condition['operation'],array('has exactly','has more than','has less than')) && stripos($sub_condition['fieldname'],'ml') !== false) {
						if (!is_array($data[$sub_condition['fieldname']])) {
							list($wsModId,$recordId) = explode('x',$id);
							$module = getSalesEntityType($recordId);
							static $tableFieldValues = array();
							if (!isset($tableFieldValues[$sub_condition['fieldname']][$recordId])) {
								require_once('include/utils/ModLightUtils.php'); 
								$MLUtils = ModLightUtils::getInstance();
								$columns = $MLUtils->getColumns($module,$sub_condition['fieldname']);
								$tableFieldValues[$sub_condition['fieldname']][$recordId] = array();
								$values = $MLUtils->getValues($module,$recordId,$sub_condition['fieldname'],$columns);
								if (!empty($values)) {
									foreach($values as $tmp) {
										array_push($tableFieldValues[$sub_condition['fieldname']][$recordId],$tmp['row']);
									}
								}
							}
							$entityCache::$cache[$id]->data[$sub_condition['fieldname']] = $tableFieldValues[$sub_condition['fieldname']][$recordId]; //crmv@OPER10174
						} else {
							if (isset($data[$sub_condition['fieldname']]['rows']))
								$entityCache::$cache[$id]->data[$sub_condition['fieldname']] = $data[$sub_condition['fieldname']]['rows']; //crmv@OPER10174
							else
								$entityCache::$cache[$id]->data[$sub_condition['fieldname']] = $data[$sub_condition['fieldname']]; //crmv@OPER10174
						}
					}
					//crmv@134058e
					require_once('modules/com_workflow/VTWorkflowManager.inc');//crmv@207901
					$workflow = new Workflow();
					$workflow->test = Zend_Json::encode(array($sub_condition));
					$string .= ($workflow->evaluate($entityCache, $id) === true) ? 'true' : 'false';
				}				
				if ($j < count($condition['conditions'])-1) $string .= ($sub_condition['glue'] == 'and') ? '&&' : '||';
				$j++;
			}
			$string .= ')';
			if ($i < count($conditions)-1) $string .= ($condition['glue'] == 'and') ? '&&' : '||';
			$i++;
		}
		$string .= ";";
		$this->debug('Conditions', $string); // crmv@189728
		if ($string != "\$result = ();") eval($string);
		return $result;
	}
	//crmv@106857e
	
	function getStartingEvents($record, $module='', $processmakerid='', $executionCondition='', $eventName='vte.entity.aftersave.processes', $module_with = '') {	//crmv@111639 crmv@200009
		global $adb, $table_prefix;
		$col = 'start';
		$adb->format_columns($col);
		$query = "select {$table_prefix}_processmaker_metarec.id, {$table_prefix}_processmaker_metarec.processid, {$table_prefix}_processmaker_metarec.elementid
			from {$table_prefix}_processmaker_metarec
			inner join {$table_prefix}_processmaker ON {$table_prefix}_processmaker_metarec.processid = {$table_prefix}_processmaker.id
			where $col = ? and active = ?";
		$params = array(1,1);
		if (!empty($module)) {
			$query .= " and module = ?";
			$params[] = $module;
		}
		if (!empty($processmakerid)) {
			$query .= " and {$table_prefix}_processmaker.id = ?";
			$params[] = $processmakerid;
		}
		// crmv@200009
        if (!empty($module_with)) {
            $query .= " and {$table_prefix}_processmaker.vte_metadata LIKE ? and {$table_prefix}_processmaker.vte_metadata LIKE ?";
            $params[] = '%"' . $module . '"%';
            $params[] = '%"' . $module_with . '"%';
        }
        // crmv@200009e
		$result = $adb->pquery($query,$params);
		$return = array();
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				// check if the process is already started for this record
				if ($record !== false && $eventName != 'vte.entity.relate') { // crmv@200009 crmv@207852
					$check = $this->getRunningProcess($record,$row['id'],$row['processid']);
					if ($check !== false) {
						// code removed crmv@93990
						continue;
					}
				}
				//crmv@111639
				$metadata = $this->getMetadata($row['processid'],$row['elementid']);
				if (empty($executionCondition) || $executionCondition == $metadata['execution_condition']) {
					$return[] = array(
						'start'=>true,
						'running_process'=>false,
						'processid'=>$row['processid'],
						'elementid'=>$row['elementid'],
						'metaid'=>$row['id'],
						'metadata'=>$metadata,
					);
				}
				//crmv@111639e
			}
		}
		return $return;
	}
	
	function getNextEvents($record, $module, $parallel_current_info='', $executionCondition='') {
		global $adb, $table_prefix;
		$col = 'current';
		$adb->format_columns($col);
		$result = $adb->pquery("select
			{$table_prefix}_running_processes.id as running_process, {$table_prefix}_running_processes.processmakerid, {$table_prefix}_running_processes.$col, {$table_prefix}_processmaker_rec.id
  			from {$table_prefix}_running_processes
  			inner join {$table_prefix}_processmaker ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker.id
			inner join {$table_prefix}_processmaker_rec on {$table_prefix}_running_processes.id = {$table_prefix}_processmaker_rec.running_process
			inner join {$table_prefix}_processmaker_metarec on {$table_prefix}_processmaker_rec.id = {$table_prefix}_processmaker_metarec.id and {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker_metarec.processid
			where {$table_prefix}_processmaker.active = ? and {$table_prefix}_running_processes.active = ? and crmid = ?",
			array(1,1,$record)
		);
		$return = array();
		if ($result && $adb->num_rows($result) > 0) {
			//crmv@155499
			if ($module == 'Calendar') {
				$activitytype = getActivityType($record);
				($activitytype == 'Task') ? $module = 'Calendar' : $module = 'Events';
			}
			//crmv@155499e
			while($row=$adb->fetchByAssoc($result)) {
				//crmv@159135
				$metaid = $row['id'];
				$structure = $this->getStructure($row['processmakerid'],$row['running_process']);
				$current_list = explode('|##|',$row['current']);
				foreach($current_list as $current) {
					if ($this->getEngineType($structure['shapes'][$current]) == 'Condition') {
						// exclude conditions of other modules
						$metadata = $this->getMetadata($row['processmakerid'],$current,$row['running_process']);
						if (strpos($metadata['moduleName'],':') !== false) {
							list($metaid,$moduleName) = explode(':',$metadata['moduleName']);
						} else {
							$moduleName = $metadata['moduleName'];
						}
						if ($module == $moduleName && $row['id'] == $metaid) {
							if (empty($executionCondition) || $executionCondition == $metadata['execution_condition']) {
								if (empty($parallel_current_info) || ($parallel_current_info['running_process'] == $row['running_process'] && $parallel_current_info['elementid'] == $current)) {
									$return[] = array(
										'running_process'=>$row['running_process'],
										'processid'=>$row['processmakerid'],
										'elementid'=>$current,
										'metaid'=>$metaid,
										'metadata'=>$metadata
									);
								}
							}
						}
						// code removed
					}
				}
				//crmv@159135e
			}
		}
		//crmv@105312
		$processes_ids = array();
		if ($module == 'Processes') {
			$processes_ids[] = $record;
		} else {
			//crmv@185647
			$result = $adb->pquery("SELECT processesid
				FROM {$table_prefix}_processes p
				INNER JOIN {$table_prefix}_processmaker pm ON p.processmaker = pm.id
				INNER JOIN {$table_prefix}_processmaker_rec r ON r.running_process = p.running_process
				WHERE pm.active = ? and p.deleted = ? AND r.crmid = ?", array(1,0,$record));
			//crmv@185647e
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$processes_ids[] = $row['processesid'];
				}
			}
		}
		if (!empty($processes_ids)) {
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			foreach($processes_ids as $processes_id) {
				$result = $adb->pquery("select {$table_prefix}_processes.processmaker, {$table_prefix}_running_processes.$col, {$table_prefix}_processes.running_process
					from {$table_prefix}_processes
					inner join {$table_prefix}_running_processes on {$table_prefix}_running_processes.id = {$table_prefix}_processes.running_process
					inner join {$table_prefix}_processmaker ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker.id
					where processesid = ? and {$table_prefix}_processmaker.active = ? and {$table_prefix}_running_processes.active = ?",
					array($processes_id,1,1)
				);
				if ($result && $adb->num_rows($result) > 0) {
					$processmaker = $adb->query_result($result,0,'processmaker');
					$running_process = $adb->query_result($result,0,'running_process');
					$structure = $this->getStructure($processmaker,$running_process);
					$current_list = explode('|##|',$adb->query_result($result,0,'current'));
					foreach($current_list as $current) {
						if ($this->getEngineType($structure['shapes'][$current]) == 'Condition') {
							$metadata = $this->getMetadata($processmaker,$current,$running_process);
							list($metaid,$mod) = explode(':',$metadata['moduleName']);
							if ($mod == 'DynaForm') {
								$dynaformvalues = $processDynaFormObj->getValues($running_process, $metaid);
								if (empty($executionCondition) || $executionCondition == $metadata['execution_condition']) {
									if (empty($parallel_current_info) || ($parallel_current_info['running_process'] == $running_process && $parallel_current_info['elementid'] == $current)) {
										$return[] = array(
											'running_process'=>$running_process,
											'processid'=>$processmaker,
											'elementid'=>$current,
											'metaid'=>false,
											'metadata'=>$metadata,
											'dynaformmetaid'=>$metaid,
											'dynaformvalues'=>$dynaformvalues
										);
									}
								}
							}
						}
						// code removed
					}
				}
			}
		}
		//crmv@105312e
		return $return;
	}
	function getOtherEvents($record, $module, $parallel_current_info='') {
		global $adb, $table_prefix;
		$return = array();
		$running_processes = array();
		$result = $adb->pquery("SELECT running_process
			FROM {$table_prefix}_processmaker_rec
			INNER JOIN {$table_prefix}_running_processes ON {$table_prefix}_running_processes.id = {$table_prefix}_processmaker_rec.running_process
			INNER JOIN {$table_prefix}_processmaker ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker.id
			WHERE crmid = ? AND {$table_prefix}_processmaker.active = ? AND {$table_prefix}_running_processes.active = ?", array($record,1,1));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$running_processes[] = $row['running_process'];
			}
		}
		if ($module == 'Processes') {
			$result = $adb->pquery("SELECT {$table_prefix}_processes.running_process
				FROM {$table_prefix}_processes
				inner join {$table_prefix}_running_processes on {$table_prefix}_running_processes.id = {$table_prefix}_processes.running_process
				inner join {$table_prefix}_processmaker ON {$table_prefix}_running_processes.processmakerid = {$table_prefix}_processmaker.id
				WHERE processesid = ? and {$table_prefix}_processmaker.active = ? and {$table_prefix}_running_processes.active = ?", array($record,1,1));
			if ($result && $adb->num_rows($result) > 0) {
				$running_processes[] = $adb->query_result($result,0,'running_process');
			}
		}
		//crmv@169519
		$result1 = $adb->pquery("SELECT {$table_prefix}_processmaker_rec.crmid, setype
			FROM {$table_prefix}_processmaker_rec
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_processmaker_rec.crmid = {$table_prefix}_crmentity.crmid
			WHERE running_process in (".generateQuestionMarks($running_processes).") AND {$table_prefix}_processmaker_rec.crmid <> ?", array($running_processes,$record));
		if ($result1 && $adb->num_rows($result1) > 0) {
			while($row1=$adb->fetchByAssoc($result1)) {
				//crmv@111639
				$nextEvents = $this->getNextEvents($row1['crmid'], $row1['setype'], $parallel_current_info, 'EVERY_TIME');
				$startingEvents = $this->getStartingEvents($row1['crmid'], $row1['setype'], '', 'EVERY_TIME');
				$events = array_merge($nextEvents,$startingEvents);
				//crmv@111639e
				if (!empty($events)) {
					foreach($events as &$event) {
						if (!empty($event['metaid']) && !empty($event['running_process'])) {
							$crmid = ProcessMakerEngine::getCrmid($event['metaid'],$event['running_process']);
						} elseif (!empty($event['dynaformmetaid'])) {
							require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
							$processDynaFormObj = ProcessDynaForm::getInstance();
							$crmid = $processDynaFormObj->getProcessesId($event['running_process'],$event['dynaformmetaid']);
						}
						if (!empty($crmid)) {
							$moduleName = getSalesEntityType($crmid);
							$webserviceObject = VtenextWebserviceObject::fromName($adb,$moduleName);//crmv@207871
							$id = vtws_getId($webserviceObject->getEntityId(),$crmid);
							$event['entity'] = array('id'=>$id,'entity_id'=>$crmid,'moduleName'=>$moduleName);
						}
					}
				}
				$return = array_merge($return,$events);
			}
		}
		//crmv@169519e
		return $return;
	}
	function cleanDuplicateEvents(&$events) {
		$new_events = array();
		if (!empty($events)) {
			foreach($events as $e) {
				$check = $this->checkDuplicateEvent($new_events,$e);
				if (!$check) $new_events[] = $e;
			}
		}
		$events = $new_events;
	}
	function checkDuplicateEvent($events,$event) {
		if (!empty($events)) {
			foreach($events as $e) {
				if (
					$e['running_process'] == $event['running_process'] &&
					$e['processid'] == $event['processid'] &&
					$e['elementid'] == $event['elementid'] &&
					$e['metaid'] == $event['metaid'] &&
					$e['dynaformmetaid'] == $event['dynaformmetaid']
				) return true;
			}
		}
		return false;
	}
	
	function getCurrentElementId($running_process,$processid='',$elementid='') {
		global $adb, $table_prefix;
		$current = false;
		$result = $adb->pquery("select current from {$table_prefix}_running_processes where id = ?", array($running_process));
		if ($result && $adb->num_rows($result) > 0) {
			$current = $adb->query_result($result,0,'current');
		}
		// if parallels ways search current using the $element
		if (stripos($current,'|##|') !== false) {
			$currents = explode('|##|',$current);
			if (in_array($elementid,$currents)) {
				$current = $elementid;
			} else {	//TODO forse questo else non serve piu'
				$PMUtils = ProcessMakerUtils::getInstance();
				$incomings = $PMUtils->getIncoming($processid,$elementid,$running_process);
				if (!empty($incomings)) {
					foreach($incomings as $incoming) {
						if (in_array($incoming['shape']['id'],$currents)) {
							$current = $incoming['shape']['id'];
							break;
						}
					}
				}
			}
			if (stripos($current,'|##|') !== false) {
				$current = false;
			}
		}
		return $current;
	}
	
	function getRunningProcess($crmid,$metaid,$processid) {
		// il check lo faccio sui processi in corso non su quelli salvati
		$running_process = false;
		$processes = ProcessMakerHandler::$running_processes;
		if (!empty($processes)) {
			foreach($processes as $p) {
				if ($p['new'] && $crmid == $p['record'] && $metaid == $p['metaid'] && $processid == $p['processid']) {
					$running_process = $p['running_process'];
					break;
				}
			}
		}
		/*
		global $adb, $table_prefix;
		$running_process = false;
		$result = $adb->pquery(
			"select running_process from {$table_prefix}_processmaker_rec
			inner join {$table_prefix}_running_processes on {$table_prefix}_processmaker_rec.running_process = {$table_prefix}_running_processes.id
			where {$table_prefix}_processmaker_rec.crmid = ? and {$table_prefix}_processmaker_rec.id = ? and {$table_prefix}_running_processes.processmakerid = ?
			order by running_process desc",	//crmv@93990
			//and {$table_prefix}_running_processes.end = 0 
			array($crmid,$metaid,$processid)
		);
		if ($result && $adb->num_rows($result) > 0) {
			$running_process = $adb->query_result($result,0,'running_process');
		}*/
		return $running_process;
	}
	
	//crmv@105312
	function checkTimerExists($mode,$running_process,$prev_elementid,$elementid,&$occurrence) {
		global $adb, $table_prefix;
		// check occurrence
		$occurrence = 0;
		$result = $adb->pquery("select id from {$table_prefix}_running_processes_logs where running_process = ? and elementid = ?", array($running_process,$prev_elementid));
		if ($result && $adb->num_rows($result) > 0) $occurrence = $adb->num_rows($result)-1;
		$result = $adb->pquery("select id from {$table_prefix}_running_processes_timer where mode = ? and running_process = ? and prev_elementid = ? and elementid = ? and occurrence = ?", array($mode,$running_process,$prev_elementid,$elementid,$occurrence));
		return ($result && $adb->num_rows($result) > 0);
	}
	function createTimer($mode,$timer,$running_process,$prev_elementid,$elementid,$occurrence=0,$info=array()) {	//crmv@127048
		global $adb, $table_prefix;
		(empty($info)) ? $info = null : $info = Zend_Json::encode($info);
		$adb->pquery("insert into {$table_prefix}_running_processes_timer(id,mode,timer,running_process,prev_elementid,elementid,occurrence,info) values(?,?,?,?,?,?,?,?)",
			array($adb->getUniqueID("{$table_prefix}_running_processes_timer"),$mode,$timer,$running_process,$prev_elementid,$elementid,$occurrence,$info)
		);
	}
	//crmv@105312e
	//crmv@134058
	function deleteTimer($mode,$running_process,$prev_elementid='',$elementid='') {
		global $adb, $table_prefix;
		$query = "delete from {$table_prefix}_running_processes_timer where mode = ?";
		$params = array($mode);
		if (is_array($running_process)) {
			$query .= " and running_process in (".generateQuestionMarks($running_process).")";
			$params[] = $running_process;
		} else {
			$query .= " and running_process = ?";
			$params[] = $running_process;
		}
		if (!empty($prev_elementid)) {
			$query .= " and prev_elementid = ?";
			$params[] = $prev_elementid;
		}
		if (!empty($elementid)) {
			$query .= " and elementid = ?";
			$params[] = $elementid;			
		}
		$adb->pquery($query,$params);
	}
	//crmv@134058e
	function includeCronDependencies() {
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/FieldInterface.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/AbstractField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/CronExpression.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/DayOfMonthField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/DayOfWeekField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/FieldFactory.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/HoursField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/MinutesField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/MonthField.php';
		require_once 'modules/Settings/ProcessMaker/thirdparty/cron-expression/src/Cron/YearField.php';
	}
	function previewTimerStart($vte_metadata) {
		$return = array();
		$date_start = getValidDBInsertDateValue($vte_metadata['date_start']).' '.$vte_metadata['starthr'].':'.$vte_metadata['startmin'];
		($vte_metadata['date_end_mass_edit_check'] == 'on') ? $date_end = getValidDBInsertDateValue($vte_metadata['date_end']).' '.$vte_metadata['endhr'].':'.$vte_metadata['endmin'] : $date_end = false;
		$return = $this->getTimerRecurrences($date_start,$date_end,($vte_metadata['recurrence'] == 'on'),$vte_metadata['cron_value'],5);
		if (!empty($return)) {
			foreach($return as &$date) {
				$date = getDisplayDate($date);
			}
		}
		return $return;
	}
	function getTimerRecurrences($date_start,$date_end=false,$recurrence=false,$cron_string='',$iterations=1) {
		$return = array();
		$i=0;
		if (!$recurrence) {
			$return[] = $date_start;
		} elseif(!empty($cron_string)) {
			$this->includeCronDependencies();
			$cron = Cron\CronExpression::factory($cron_string);
			$runDates = $cron->getMultipleRunDates($iterations*2, $date_start, false, true);
			if (!empty($runDates)) {
				foreach($runDates as $runDate) {
					$runDate = $runDate->format('Y-m-d H:i:s');
					if ($date_end === false || strtotime($runDate) <= strtotime($date_end)) {
						$return[] = $runDate;
						$i++;
						if ($iterations == $i) break;
					}
				}
			}
		}
		return $return;
	}
	function isTimerProcess($id,&$shapeid) {
		$structure = $this->getStructure($id);
		foreach($structure['shapes'] as $shapeid => $shape) {
			if ($shape['type'] == 'StartEvent') {
				return ($shape['subType'] == 'TimerEventDefinition');
			}
		}
		return false;
	}
	function isChangedTimerCondition($vte_metadata_new,$vte_metadata) {
		if (empty($vte_metadata_new) && empty($vte_metadata)) return false;
		else {
			foreach($vte_metadata_new as $k => $v) {
				if ($v != $vte_metadata[$k]) return true;
			}
		}
		return false;
	}
	
	function getElementTitle($structure) {
		$text = $structure['text'];
		$subType = $this->formatType($structure['subType']);
		$cancelActivity = $structure['cancelActivity'];
		
		$title = $this->formatType($structure['type'],true);
		if (!empty($subType)) {
			$title .= "($subType";
			if (isset($cancelActivity)) ($cancelActivity) ? $title .= ': Interrupting' : $title .= ': Non-Interrupting';
			$title .= ")";
		}
		if (!empty($text)) $title .= ': '.trim($text);
		
		return $title;
	}
	
	//crmv@100495
	function showRunProcessesButton($module, $record='') {
		return false;
	}
	//crmv@100495e
	
	//crmv@100591
	function getElementsActors($processid,$email_fields=false) {
		$actors = array();
		if ($email_fields) {
			global $adb, $table_prefix;
			$fieldnames = array();
			$result = $adb->pquery("SELECT fieldname, fieldlabel FROM {$table_prefix}_field LEFT JOIN {$table_prefix}_ws_fieldtype ON {$table_prefix}_field.uitype = {$table_prefix}_ws_fieldtype.uitype WHERE tabid = ? AND ({$table_prefix}_field.uitype = ? OR fieldtype = ?)", array(29,104,'email'));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByASsoc($result)) {
					$fieldnames[$row['fieldname']] = $row['fieldlabel'];
				}
			}
		}
		$structure = $this->getStructure($processid);
		if (!empty($structure['shapes'])) {
			foreach($structure['shapes'] as $elementid => $structure) {
				if ($this->getEngineType($structure) == 'Condition') {
					if ($email_fields) {
						foreach($fieldnames as $fieldname => $fieldlabel) {
							$actors['$ACTOR-'.$elementid.'-'.$fieldname] = $this->getElementTitle($structure).' - '.getTranslatedString($fieldlabel,'Users');
						}
					} else {
						$actors['$ACTOR-'.$elementid] = $this->getElementTitle($structure);
					}
				}
			}
		}
		return $actors;
	}
	function getActor($running_process, $elementid, $fieldname='') {
		global $adb, $table_prefix;
		$result = $adb->limitpQuery("SELECT userid FROM {$table_prefix}_running_processes_logs WHERE running_process = ? AND prev_elementid = ? ORDER BY logtime DESC", 0, 1, array($running_process, $elementid));
		if ($result && $adb->num_rows($result) > 0) {
			if (!empty($fieldname)) {
				$user = CRMEntity::getInstance('Users');
				$user->retrieveCurrentUserInfoFromFile($adb->query_result($result,0,'userid'));
				return $user->column_fields[$fieldname];
			} else {
				return $adb->query_result($result,0,'userid');
			}
		}
		return false;
	}
	function getProcessActors($running_process) {
		global $adb, $table_prefix;
		$actors = array();
		$result = $adb->pquery("SELECT DISTINCT userid FROM {$table_prefix}_running_processes_logs WHERE running_process = ?", array($running_process));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$actors[] = $row['userid'];
			}
		}
		return $actors;
	}
	//crmv@100591e
	
	//crmv@103450
	function getElementsExecutedByActors($processid, $running_process) {
		global $adb, $table_prefix;
		$structure = $this->getStructure($processid,$running_process);
		$return = array();
		$result = $adb->pquery("SELECT userid, prev_elementid FROM {$table_prefix}_running_processes_logs WHERE running_process = ? ORDER BY id", array($running_process));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (empty($return[$row['userid']]) || !in_array($row['prev_elementid'],$return[$row['userid']])) {
					if ($structure['shapes'][$row['prev_elementid']]['type'] == 'Task')
						$return[$row['userid']][] = $row['prev_elementid'];
				}
			}
		}
		return $return;
	}
	//crmv@103450e
	
	// crmv@146671
	public function getExtWSMeta($processid, $running_process) {
		global $adb, $table_prefix;
		(!empty($running_process)) ? $xml_version = $this->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		if (!empty($xml_version)) {
			$query = "SELECT * FROM {$table_prefix}_process_extws_meta_vh WHERE versionid = ? and processid = ?";
			$params = array($xml_version, $processid);
		} else {
			$query = "SELECT * FROM {$table_prefix}_process_extws_meta WHERE processid = ?";
			$params = array($processid);
		}
		$metas = array();
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$metas[$row['elementid']] = $row;
			}
		}
		return $metas;
	}
	
	public function getExtWSMetaById($metaid, $running_process) {
		global $adb, $table_prefix;
		(!empty($running_process)) ? $xml_version = $this->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		if (!empty($xml_version)) {
			$query = "SELECT * FROM {$table_prefix}_process_extws_meta_vh WHERE versionid = ? and id = ?";
			$params = array($xml_version, $metaid);
		} else {
			$query = "SELECT * FROM {$table_prefix}_process_extws_meta WHERE id = ?";
			$params = array($metaid);
		}
		$meta = array();
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			$meta = $adb->fetchByAssoc($result, -1, false);
		}
		return $meta;
	}
	
	// get the fields returned by all the webservice calls in the process
	public function getExtWSFields($processid, $skipElement=null, $running_process='') { // crmv@147433
		require_once('modules/Settings/ExtWSConfig/ExtWSUtils.php');
		$EWSU = new ExtWSUtils();
		
		$wsfields = array();
		$meta = $this->getMetadata($processid, '', $running_process);
		$wsmeta = $this->getExtWSMeta($processid, $running_process);
		if (!empty($meta)) {
			foreach ($meta as $taskinfo) {
				if (is_array($taskinfo['actions'])) {
					foreach ($taskinfo['actions'] as $action) {
						if ($action['action_type'] == 'CallExtWS') {
							$elementid = $action['elementid'];
							if ($skipElement && $skipElement == $elementid) continue; // crmv@147433
							$metaid = $wsmeta[$elementid]['id'];
							$extwsid = intval($action['extwsid']);
							$wsinfo = $EWSU->getWSInfo($extwsid);
							
							// now prepare the available fields
							$fields = array(
								array(
									'value' => '$WS'.$metaid.'-extws_success',
									'label' => getTranslatedString('LBL_EXTWS_RESULTFIELD_OUTCOME', 'Settings').'?',
									'expression' => 'extws_success',
								),
								array(
									'value' => '$WS'.$metaid.'-extws_returncode',
									'label' => getTranslatedString('LBL_EXTWS_RESULTFIELD_CODE', 'Settings'),
									'expression' => 'extws_returncode',
								),
								array(
									'value' => '$WS'.$metaid.'-extws_returnmessage',
									'label' => getTranslatedString('LBL_EXTWS_RESULTFIELD_MESSAGE', 'Settings'),
									'expression' => 'extws_returnmessage',
								),
							);
							if (is_array($wsinfo['results'])) {
								foreach ($wsinfo['results'] as $rinfo) {
									$fields[] = array(
										'value' => '$WS'.$metaid.'-'.$rinfo['name'],
										'label' => $rinfo['name'],
										'expression' => $rinfo['value'],
									);
								}
							}
							if (is_array($action['extra_results'])) {
								foreach ($action['extra_results'] as $rname => $rval) {
									$fields[] = array(
										'value' => '$WS'.$metaid.'-'.$rname,
										'label' => $rname,
										'expression' => $rval,
									);
								}
							}
							// and add it to the final array
							$wsfields[] = array(
								'metaid' => $metaid,
								'label' => "[\$WS$metaid] Web service (BPMN-ScriptTask: {$action['action_title']})",
								'fields' => $fields,
							);
						}
					}
				}
			}
		}
		return $wsfields;
	}
	
	/**
	 * Get the full response of a WS call
	 */
	public function getExtWSResult($running_process, $metaid) {
		global $adb, $table_prefix;
		
		$results = array();
		$query = "SELECT results FROM {$table_prefix}_process_extws WHERE running_process = ? AND metaid = ?";
		$params = array($running_process, $metaid);
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			$results = $adb->query_result_no_html($result, 0, 'results');
			$results = Zend_Json::decode($results);
		}
		
		return $results;
	}
	
	/**
	 * Get only the extracted data of the WS call
	 */
	public function getExtWSResultValues($running_process, $metaid, $actionid, $engine) {
		require_once('modules/Settings/ExtWSConfig/ExtWSExtractor.php');
		
		// get the meta (to have the processid and the extwsid)
		$wsmeta = $this->getExtWSMetaById($metaid, $running_process);
		$extwsid = intval($wsmeta['extwsid']);
		
		// get the process metadata
		$meta = $this->getMetadata($wsmeta['processid'], $wsmeta['elementid'], $running_process);
		// crmv@199886: removed crmv@OPER10174
		$actionmeta = null;
		if (is_array($meta['actions'])) {
			foreach ($meta['actions'] as $action) {
				if ($action['action_title'] == $wsmeta['text']) {
					$actionmeta = $action;
					break;
				}
			}
		}
		if (!$actionmeta) return array();
		
		// get all the response
		$results = $this->getExtWSResult($running_process, $metaid);		
		$fields = array();
		$allfields = $this->getExtWSFields($wsmeta['processid'], null, $running_process);

		// get only the fields for my process
		foreach ($allfields as $group) {
			if ($group['metaid'] == $metaid) {
				foreach ($group['fields'] as $fld) {
					$expr = $fld['expression'];
					if ($engine) {
						$expr = $engine->replaceTags('extra_result', $expr, array(), array(), $actionid);
					}
					$fields[] = array(
						'name' => preg_replace('/^\$WS[0-9]*-/', '', $fld['value']),
						'value' => $expr,
					);
				}
				break;
			}
		}

		$EWSE = new ExtWSExtractor();
		try {
			$values = $EWSE->extractFields($results, $fields);
		} catch (Exception $e) {
			return array();
		}
		
		return $values;
	}
	
	// crmv@146671e
	
	//crmv@100731
	function getTranslatedProcessResource($processid,$value) {
		global $adb, $table_prefix;
		if (is_numeric($value)) {
			$ownerType = getOwnerType($value);
			if ($ownerType == 'Users') {
				global $showfullusername;
				$name = getUserName($value,$showfullusername);
			} else {
				$tmp = getGroupName($value);
				$name = $tmp[0];
			}
			$value = $name;
		} else {
			if (strpos($value,':') == 3) {	// old mode
				list($meta_processid,$metaid,$module,$fieldname) = explode(':',$value);
				$moduleInstance = Vtecrm_Module::getInstance($module);
				$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$fieldname));
				$fieldlabel = getTranslatedString($adb->query_result($result,0,'fieldlabel'),$module);
				
				$structure = $this->getStructure($processid);
				$value = $fieldlabel.' '.getTranslatedString('LBL_OF','Settings').' '.$this->getRecordsInvolvedLabel($processid,$metaid);
			} else {
				if (stripos($value,'$ACTOR-') !== false) {
					$structure = $this->getStructure($processid);
					list($actor,$elementid) = explode('$ACTOR-',$value);
					$value = getTranslatedString('LBL_PM_PARTECIPANT_OF','Settings').' '.$this->getElementTitle($structure['shapes'][$elementid]);
				} elseif (stripos($value,'$sdk:') !== false) {
					$sdkFieldConditions = SDK::getProcessMakerFieldActions();
					$tmp_sdk_function = str_replace('$sdk:','',$value);
					$funct = substr($tmp_sdk_function,0,strpos($tmp_sdk_function,'('));
					if (isset($sdkFieldConditions[$funct])) {
						$value = getTranslatedString('LBL_PM_SDK_CUSTOM_FUNCTION','Settings').': '.$sdkFieldConditions[$funct]['label'];
					}
				} elseif (stripos($value,'$DF') !== false) {
					$tmp = str_replace('$DF','',$value);
					list($dynaform_metaid,$dynaform_fieldname) = explode('-',$tmp);
					if (strpos($dynaform_metaid,':') !== false) {
						list($processid,$dynaform_metaid) = explode(':',$dynaform_metaid);
					}
					require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
					$processDynaFormObj = ProcessDynaForm::getInstance();
					$options = $processDynaFormObj->getOptions($processid);
					$dflabel = $options["$dynaform_metaid:DynaForm"][0];
					$fieldOptions = $processDynaFormObj->getFieldsOptions($processid);
					$fieldlabel = $fieldOptions['all'][$dflabel][$value];
					$value = $fieldlabel.' '.getTranslatedString('LBL_OF','Settings').' '.$dflabel;
				} else {
					$tmp = str_replace('$','',$value);
					list($metaid,$fieldname) = explode('-',$tmp);
					if (strpos($metaid,':') !== false) {
						list($processid,$metaid) = explode(':',$metaid);
					}
					$records = $this->getRecordsInvolved($processid);
					foreach($records as $r) {
						if ($r['seq'] == $metaid) {
							$moduleInstance = Vtecrm_Module::getInstance($r['module']);
							$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$fieldname));
							if ($result && $adb->num_rows($result) > 0) {
								$fieldlabel = getTranslatedString($adb->query_result($result,0,'fieldlabel'),$module);
								$value = $fieldlabel.' '.getTranslatedString('LBL_OF','Settings').' '.$r['label'];
							}
							break;
						}
					}
					if (empty($fieldlabel)) $value = $fieldname.' '.getTranslatedString('LBL_OF','Settings').' '.$this->getRecordsInvolvedLabel($processid,$metaid);
				}
			}
		}
		return $value;
	}
	function getAdvancedPermissions($return_mode) {
		global $adb, $table_prefix, $current_user;
		static $ids = array();
		if (empty($ids)) {
			$inserts = array();
			require('user_privileges/requireUserPrivileges.php');
			if (empty($current_user_groups)) {
				$userGroupFocus = new GetUserGroups();
				$userGroupFocus->getAllUserGroups($current_user->id);
				$current_user_groups = $userGroupFocus->user_groups;
			}
			$smowners = array($current_user->id);
			if (!empty($current_user_groups)) $smowners = array_filter(array_merge($smowners, $current_user_groups));
			$result = $adb->pquery("SELECT crmid, read_perm, write_perm FROM {$table_prefix}_process_adv_permissions WHERE ".$adb->format_column('resource')." in (".generateQuestionMarks($smowners).")", array($smowners)); // crmv@165801
			$tmp = array();
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$tmp[$row['crmid']][] = array('read_perm'=>$row['read_perm'],'write_perm'=>$row['write_perm']);
				}
				foreach($tmp as $id => $permissions) {
					foreach($permissions as $permission) {
						// if there are more conditions verified (ex. I'm part of groups) select the most restrictive
						if (!isset($ids[$id]) || $permission['read_perm'] < $ids[$id]['read_perm'] || $permission['write_perm'] < $ids[$id]['write_perm']) {
							if (!isset($ids[$id])) $inserts[] = array($current_user->id, $id);	// crmv@133311
							$ids[$id] = $permission;
						}
					}
				}
			}
			if ($return_mode == 'sql' && !empty($inserts)) {
				$adb->pquery("delete from {$table_prefix}_process_adv_perm_tmp where userid = ?", array($current_user->id));
				$adb->bulkInsert("{$table_prefix}_process_adv_perm_tmp", null, $inserts);
			}
		}
		if ($return_mode == 'sql') {
			(empty($ids)) ? $sql = '' : $sql = " OR {$table_prefix}_crmentity.crmid IN (select crmid from {$table_prefix}_process_adv_perm_tmp where userid = ".$current_user->id.")";
			return $sql;
		} elseif ($return_mode == 'array') {
			return $ids;
		}
	}
	function checkAdvancedPermissions($module,$actionname,$record_id) {
		$return = '';
		$actionid = getActionid($actionname);
		$permissions = $this->getAdvancedPermissions('array');
		if (isset($permissions[$record_id])) {
			if ($actionid === 4) {	// detailview
				($permissions[$record_id]['read_perm'] == 1) ? $return = 'yes' : $return = 'no';
			} elseif ($actionid === 0 || $actionid === 1) {	// save, edit
				($permissions[$record_id]['write_perm'] == 1) ? $return = 'yes' : $return = 'no';
			}
		}
		return $return;
	}
	function getAdvancedPermissionsResources($record) {
		global $adb, $table_prefix;
		//crmv@169362
		static $resources = array();
		if (!isset($resources[$record])) {
			//crmv@169362e
			$resources[$record] = array();
			$result = $adb->pquery("SELECT * FROM {$table_prefix}_process_adv_permissions WHERE crmid = ?", array($record));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					if ($row['read_perm'] == 1 && $row['write_perm'] == 1) {
						$visibility = getTranslatedString('Read/Write','Settings');
					} elseif ($row['read_perm'] == 1) {
						$visibility = getTranslatedString('Read Only ','Settings');
					}
					//crmv@180505
					if ($row['resource_type'] == 'O') {
						$checkGroup = $adb->pquery("select groupid from {$table_prefix}_groups where groupid = ?", array($row['resource']));
						if ($checkGroup && $adb->num_rows($checkGroup) > 0) $row['resource_type'] = 'T';
					}
					//crmv@180505e
					if ($row['resource_type'] == 'T') {
						$group = getGroupName($row['resource']);
						$img = getGroupAvatar();
						$name = '&nbsp;';
						$fullname = $group[0];
					} else {
						$img = getUserAvatar($row['resource']);
						$name = getUserName($row['resource'],false);
						$fullname = getUserFullName($row['resource']);
					}
					$resources[$record][] = array(
						'id'=>$row['resource'],
						'img'=>$img,
						'name'=>$name,
						'fullname'=>$fullname,
						'read_perm'=>$row['read_perm'],
						'write_perm'=>$row['write_perm'],
						'alt'=>getTranslatedString('LBL_PM_ADVANCED_PERMISSIONS_VISIBILITY','Settings').': '.$visibility,
					);
				}
			}
			//crmv@169362
		}
		return $resources[$record];
		//crmv@169362e
	}
	//crmv@100731e
	//crmv@93990
	function getProcessRelatedTo($record, $field) {
		global $adb, $table_prefix, $current_user;
		static $relatedTo = array();
		if (!isset($relatedTo[$record])) {
			$relatedTo[$record]['processesid'] = false;
			require('user_privileges/requireUserPrivileges.php');
			if (empty($current_user_groups)) {
				$userGroupFocus = new GetUserGroups();
				$userGroupFocus->getAllUserGroups($current_user->id);
				$current_user_groups = $userGroupFocus->user_groups;
			}
			$smowners = array($current_user->id);
			if (!empty($current_user_groups)) $smowners = array_filter(array_merge($smowners, $current_user_groups));
			// crmv@137082 crmv@180440 crmv@185647
			$endcol = 'end';
			$adb->format_columns($endcol);
			// crmv@195119
			$result = $adb->pquery("SELECT processesid, processmaker, dynaform_meta.elementid AS current_dynaform, dynaform_meta.id AS dynaformmetaid
				FROM {$table_prefix}_processes
				INNER JOIN {$table_prefix}_running_processes ON {$table_prefix}_running_processes.id = {$table_prefix}_processes.running_process
				INNER JOIN {$table_prefix}_process_dynaform_meta dynaform_meta ON dynaform_meta.processid = {$table_prefix}_processes.processmaker
				INNER JOIN {$table_prefix}_process_dynaform dynaform ON dynaform.running_process = {$table_prefix}_processes.running_process AND dynaform.metaid = dynaform_meta.id
				WHERE deleted = 0 AND $endcol = 0 AND related_to = ? AND smownerid in (".generateQuestionMarks($smowners).") AND dynaform.done = 0
				ORDER BY createdtime ASC", array($record, $smowners));
			// crmv@137082e crmv@180440e
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result,-1,false)) {
					$data = $this->retrieve($row['processmaker']);
					$helper = Zend_Json::decode($data['helper']);
					$helper = $helper[$row['current_dynaform']];
					if ($helper['related_to_popup'] == 'on') {
						$relatedTo[$record] = array(
							'processesid'=>$row['processesid'],
							'dynaformmetaid'=>$row['dynaformmetaid'],
							'current_dynaform'=>$row['current_dynaform'],
							'related_to_popup'=>$helper['related_to_popup'],
							'related_to_popup_opt'=>$helper['related_to_popup_opt'],
						);
						break;
					}
				}
			}
			// crmv@195119e
		}
		return $relatedTo[$record][$field];
	}
	//crmv@93990e
	
	//crmv@103450
	function getProcessHelperDefault($processid,$elementid,$type) {
		if ($this->isEndTask($type))
			return 'Ended';
		else {
			$structure = $this->getStructureElementInfo($processid,$elementid,'tree');
			$attachers = $structure['attachers'];
			if (!empty($attachers)) {
				foreach($attachers as $attacher) {
					$attacher_structure = $this->getStructureElementInfo($processid,$attacher,'shapes');
					if ($attacher_structure['subType'] == 'TimerEventDefinition') {
						return 'Waiting';
					}
				}
			}
		}
		return 'Running';
	}
	//crmv@103450e
	
	//crmv@106856
	function addConditionTranslations(&$rules, $processmakerid) {
		if (!empty($rules)) {
			foreach($rules as &$a) {
				$conditions = $a['conditions'];
				list($entityId,$module) = explode(':',$a['meta_record']);
				if ($module == 'DynaForm') {
					require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
					$processDynaFormObj = ProcessDynaForm::getInstance();
					$label = $processDynaFormObj->getLabel($processmakerid,$entityId).' ';
				} else {
					$label = $this->getRecordsInvolvedLabel($processmakerid,$entityId).' ';
				}
				$i=0;
				foreach($conditions as $condition) {
					$sub_conditions = $condition['conditions'];
					if (count($sub_conditions) > 1) $label .= '(';
					foreach($sub_conditions as $ii => $sub_condition) {
						$label .= $this->translateConditionFieldname($sub_condition['fieldname'],$module,$processmakerid,$entityId).' '.$this->translateConditionOperation($sub_condition['operation']).' <i>'.$this->translateConditionValue($sub_condition['fieldname'],$module,$sub_condition['value']).'</i>';
						if ($ii < count($sub_conditions)) $label .= ' '.$this->translateConditionGlue($sub_condition['glue']).' ';
					}
					$i++;
					if (count($sub_conditions) > 1) $label .= ')';
					if ($i < count($conditions)) $label .= ' '.$this->translateConditionGlue($condition['glue']).' ';
				}
				$a['conditions_translate'] = $label;
			}
		}
	}
	function getAdvancedFieldAssignment($fieldname) {
		return VteSession::getArray(array('AdvancedFieldAssignment',$fieldname));
	}
	function setAdvancedFieldAssignment($fieldname, $rules) {
		VteSession::setArray(array('AdvancedFieldAssignment',$fieldname),$rules);
	}
	function unsetAdvancedFieldAssignment($fieldname='') {
		if (empty($fieldname)) VteSession::removeArray(array('AdvancedFieldAssignment'));
		else VteSession::removeArray(array('AdvancedFieldAssignment',$fieldname));
	}
	//crmv@160843 codes removed
	function saveAdvancedFieldAssignment($fieldname,$action,$info) {
		$rules = $this->getAdvancedFieldAssignment($fieldname);
		if ($action == 'condition') {
			$ruleid = $info[0];
			$meta_record = $info[1];
			$conditions = Zend_Json::decode($info[2]);
			if ($ruleid === '') {
				$rules[] = array(
					'meta_record' => $meta_record,
					'conditions' => $conditions,
				);
			} else {
				$rules[$ruleid]['meta_record'] = $meta_record;
				$rules[$ruleid]['conditions'] = $conditions;
			}
		} elseif ($action == 'values') {
			$form = Zend_Json::decode($info[0]);
			$count = $form['conditions_count'];
			if (!empty($count)) {
				for($i=0;$i<$count;$i++) {
					//crmv@160843
					if (isset($form['assigntype'.$i]) || isset($form['assigned_user_id'.$i.'_type'])) {
						(isset($form['assigntype'.$i])) ? $assigntype = $form['assigntype'.$i] : $assigntype = $form['assigned_user_id'.$i.'_type'];
						if ($assigntype == 'U' || $assigntype == 'v') $value = $form['assigned_user_id'.$i];
						elseif ($assigntype == 'T') $value = $form['assigned_group_id'.$i];
						elseif (strtolower($assigntype) == 'o') $value = $form['other_assigned_user_id'.$i];
						$rules[$i]['value'] = $value;
						$rules[$i]['assigntype'] = $assigntype;
						$rules[$i]['sdk_params'] = $form['sdk_params_assigned_user_id'.$i];
					} else {
						$rules[$i]['value'] = $form[$fieldname.$i];
					}
					//crmv@160843e
				}
			}
		/*
		} elseif ($action == 'db') {
			$id = $info[0];
			$elementid = $info[1];
			$actionid = $info[2];
			$data = $this->retrieve($id);
			$vte_metadata = Zend_Json::decode($data['vte_metadata']);
			$vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname] = $rules;
			$this->saveMetadata($id,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		*/
		}
		$this->setAdvancedFieldAssignment($fieldname, $rules);
	}
	function removeAdvancedFieldAssignment($processmakerid,$elementid,$actionid,$fieldname,$ruleid) {
		/*
		$data = $this->retrieve($processmakerid);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		unset($vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname][$ruleid]);
		$vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname] = array_values($vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname]);
		$this->saveMetadata($processmakerid,$elementid,Zend_Json::encode($vte_metadata[$elementid]));
		$this->setAdvancedFieldAssignment($fieldname, $vte_metadata[$elementid]['actions'][$actionid]['advanced_field_assignment'][$fieldname]);
		*/
		$rules = $this->getAdvancedFieldAssignment($fieldname);
		unset($rules[$ruleid]);
		$this->setAdvancedFieldAssignment($fieldname, array_values($rules));
	}
	//crmv@106856e
	
	//crmv@106857
	function getAllTableFields($processmaker) {
		global $adb, $table_prefix;
		$tfields = array();
		$records = $this->getRecordsInvolved($processmaker);
		if (!empty($records)) {
			foreach($records as $r) {
				$bfields = array();
				$key = $r['seq'].':'.$r['module'];
				$moduleInstance = Vtecrm_Module::getInstance($r['module']);
				$result = $adb->pquery("select fieldname, fieldlabel from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance->id,220));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$fkey = $r['seq'].':::'.$row['fieldname']; //crmv@182891
						$bfields[$fkey] = $row['fieldlabel'];
					}
				}
				//crmv@182891
				$tmp_values = array();
				if (empty($referenceInfo[$r['module']])) {
					$relationManager = RelationManager::getInstance();
					$relationsN1 = $relationManager->getRelations($r['module'], ModuleRelation::$TYPE_NTO1);
					if (!empty($relationsN1)) {
						$referenceInfo[$r['module']] = array();
						foreach($relationsN1 as $rel) {
							if (!isset($referenceInfo[$r['module']][$rel->fieldid])) {
								$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where fieldid = ?", array($rel->fieldid));
								if ($result && $adb->num_rows($result) > 0) {
									$referenceInfo[$r['module']][$rel->fieldid] = array(
										'fieldid'=>$rel->fieldid,
										'fieldlabel'=>getTranslatedString($adb->query_result($result,0,'fieldlabel'),$rel->getFirstModule()),
										'module'=>$rel->getSecondModule(),
									);
								}
							}
						}
					}
				}
				if (!empty($referenceInfo[$r['module']])) {
					foreach($referenceInfo[$r['module']] as $rr) {
						//crmv@160368 crmv@160859
						$relatedmodres = $adb->pquery("SELECT relmodule FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?", Array($rr['fieldid']));
						if ($relatedmodres && $adb->num_rows($relatedmodres) > 1) {
							// check for multiple related modules
							while($row_relmod=$adb->fetchByAssoc($relatedmodres)) {
								$referenceKey = $r['seq'].':'.$rr['fieldid'].':'.$row_relmod['relmodule'];
								
								$moduleInstance1 = Vtecrm_Module::getInstance($row_relmod['relmodule']);
								$result1 = $adb->pquery("select fieldname, fieldlabel from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance1->id,220));
								if ($result1 && $adb->num_rows($result1) > 0) {
									while($row1=$adb->fetchByAssoc($result1)) {
										$bfields[$referenceKey.':'.$row1['fieldname']] = $rr['fieldlabel'].' ('.getTranslatedString($row_relmod['relmodule'],$row_relmod['relmodule']).') : '.$row1['fieldlabel'];
									}
								}
							}
						} else {
							$referenceKey = $r['seq'].':'.$rr['fieldid'].':';
							
							$moduleInstance1 = Vtecrm_Module::getInstance($rr['module']);
							$result1 = $adb->pquery("select fieldname, fieldlabel from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance1->id,220));
							if ($result1 && $adb->num_rows($result1) > 0) {
								while($row1=$adb->fetchByAssoc($result1)) {
									$bfields[$referenceKey.':'.$row1['fieldname']] = $rr['fieldlabel'].' ('.getTranslatedString($rr['module'],$rr['module']).') : '.$row1['fieldlabel'];
								}
							}							
						}
						//crmv@160368e crmv@160859e
					}
				}
				//crmv@182891e
				if (!empty($bfields)) {
					$tfields[$key] = array('label'=>$r['label'], 'fields'=>$bfields);
				}
			}
		}
		return $tfields;
	}
	//crmv@203075
	function getAllRelatedModulesForCycle($processmaker) {
		//getting modulename
		$modulename = '';
		$metadata = $this->getMetadata($processmaker);
		if(empty($metadata) || !is_array($metadata)){
		    return [];
        }
		foreach($metadata as $k => $v)
		{
			if($this->isStartTask($processmaker, $k))
			{
				$modulename = $v['moduleName'];
				break;
			}
		}

		$focus = RelationManager::getInstance();
		$rel = $focus->getRelations($modulename, ModuleRelation::$TYPE_NTON | ModuleRelation::$TYPE_1TON);
		$allrelations = [];
		foreach($rel as $k)
		{
			if(!in_array($k->getSecondModule(), $this->modules_excluded_link))
				$allrelations[] = $k->getSecondModule();
		}

		$tfields = array();
		$records = $this->getRecordsInvolved($processmaker);

		if (!empty($records)) {
			foreach ($records as $r) {
				$bfields = array();
				$key = $r['seq'].':'.$r['module'];

				foreach($allrelations as $k => $relation)
				{
					$referenceKey = $r['seq'].':'.$k.':' . $modulename . ':' . $relation;
					$bfields[$referenceKey] = $relation;
				}
				if (!empty($bfields)) {
					$tfields[$key] = array('label'=>$r['label'], 'fields'=>$bfields);
				}
				break;
			}
		}
		return $tfields;
	}
	//crmv@203075e
	function getAllTableFieldsOptions($processmaker, &$return) {
		global $adb, $table_prefix;
		$records = $this->getRecordsInvolved($processmaker);
		if (!empty($records)) {
			foreach($records as $r) {
				$moduleInstance = Vtecrm_Module::getInstance($r['module']);
				$result = $adb->pquery("select fieldname, fieldlabel from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance->id,220));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$this->getTableFieldsOptions($processmaker, $r['seq'], $row['fieldname'], $return, $row['fieldlabel']);
					}
				}
			}
		}
		return $return;
	}
	function getTableFieldsOptions($processmaker, $metaid, $fieldname, &$return, $fieldlabel='') {
		require_once('include/utils/ModLightUtils.php');
		global $adb, $table_prefix;
		$modulelightid = str_replace('ml','',$fieldname);
		if (empty($fieldlabel)) {
			$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where fieldname = ? and uitype = ?", array($fieldname,220));
			if ($result && $adb->num_rows($result) > 0) {
				$fieldlabel = $adb->query_result($result,0,'fieldlabel');
			}
		}
		$MLUtils = ModLightUtils::getInstance();
		$processDynaForm = ProcessDynaForm::getInstance();
		$columns = $MLUtils->getColumns('', $fieldname);
		if (!empty($columns)) {
			$groupLabel = $this->getRecordsInvolvedLabel($processmaker,$metaid)." : $fieldlabel";
			foreach($columns as $column) {
				$value = "\${$metaid}-{$fieldname}::".$column['fieldname'];
				$processDynaForm->categorizeFieldByType($return, $column, $groupLabel, $value);
			}
		}
        return $return;
	}
	
	// crmv@195745
	public function getAllProductsBlocks($processmaker) {
		global $adb, $table_prefix;
		
		$tfields = array();
		$records = $this->getRecordsInvolved($processmaker);
		if (!empty($records)) {
		
			$FM = new FakeModules();
			$label = $FM->getModuleLabel('ProductsBlock');
			
			foreach($records as $r) {
				if (isInventoryModule($r['module'])) {
					$key = $r['seq'].':'.$r['module'];
					$fkey = $r['seq'].':0:ProductsBlock:prodblock'; //crmv@182891
					$tfields[$key] = array('label'=>$r['label'], 'fields'=>array($fkey => $label));
				}
			}
		}
		return $tfields;
	}
	
	function getAllPBlockFieldsOptions($processmaker, &$return) {
		$records = $this->getRecordsInvolved($processmaker);
		if (!empty($records)) {
		
			$FM = new FakeModules();
			$label = $FM->getModuleLabel('ProductsBlock');
			
			$pfields = null;
			
			$processDynaForm = ProcessDynaForm::getInstance();
			foreach($records as $r) {
				if (isInventoryModule($r['module'])) {
				
					if (is_null($pfields)) {
						$pfields = $FM->getFields('ProductsBlock');
						unset($pfields['id'], $pfields['total_notaxes']);
					}
					
					$metaid = $r['seq'];
					$groupLabel = $this->getRecordsInvolvedLabel($processmaker,$metaid)." : $label";
					
					foreach ($pfields as $fld) {
						$value = "\${$metaid}-prodblock::".$fld['fieldname'];
						$fld['fieldlabel'] = $fld['label'];
						if ($fld['relmodules']) {
							$fld['relatedmods'] = implode(',',$fld['relmodules']);
						}
						$processDynaForm->categorizeFieldByType($return, $fld, $groupLabel, $value);
					}
				}
			}
		}
		return $return;
	}
	
	function replacePBlockFieldTag($parent, $tfield, $tcol, $cycleIndex=null, $relmod = null, $relfield = null) {

		list($wsModule,$parent_id) = explode('x',$parent);
		$parent_module = getSalesEntityType($parent_id);
		
		static $pblockFieldValues = array();
		if (!isset($pblockFieldValues[$parent_id])) {
			$IU = InventoryUtils::getInstance();
			$values = $IU->getProductBlockRows($parent_module, $parent_id, false);
			$pblockFieldValues[$parent_id] = $values;
		}
		$values = $pblockFieldValues[$parent_id];
		
		$replace = $this->applyTableFieldFunct('prodblock', $values, $tfield, $tcol, $cycleIndex, $relmod, $relfield);
		
		return $replace;
	}
	// crmv@195745e
	
	function replaceTableFieldTag($parent, $tfield, $tcol, $cycleIndex=null, $relmod = null, $relfield = null) { // crmv@195745
		require_once('include/utils/ModLightUtils.php');
		list($wsModule,$parent_id) = explode('x',$parent);
		$parent_module = getSalesEntityType($parent_id);
		
		static $tableFieldValues = array();
		if (!isset($tableFieldValues[$tfield][$parent_id])) {
			$MLUtils = ModLightUtils::getInstance();
			$columns = $MLUtils->getColumns($parent_module,$tfield);
			$tableFieldValues[$tfield][$parent_id] = array();
			$values = $MLUtils->getValues($parent_module,$parent_id,$tfield,$columns);
			if (!empty($values)) {
				foreach($values as $tmp) {
					array_push($tableFieldValues[$tfield][$parent_id],$tmp['row']);
				}
			}
		}
		$values = array_values($tableFieldValues[$tfield][$parent_id]);
		$replace = $this->applyTableFieldFunct('modulelight', $values, $tfield, $tcol, $cycleIndex, $relmod, $relfield); // crmv@195745
		return $replace;
	}
	function applyTableFieldFunct($mode, $values, $tfield, $tcol, $cycleIndex=null, $relmod = null, $relfield = null) { // crmv@195745
		// crmv@195745 - removed code
		list($tcol, $funct, $seq) = explode(':',$tcol);
		$replace = '';
		if (!empty($values) && ($funct == 'curr' || isset($values[0][$tcol]))) { // crmv@195745
			switch ($funct) {
				case 'sum':
				case 'min':
				case 'max':
				case 'average':
					$col_values = array_column($values,$tcol);
					if ($funct == 'sum') $replace = array_sum($col_values);
					elseif ($funct == 'min') $replace = min($col_values);
					elseif ($funct == 'max') $replace = max($col_values);
					elseif ($funct == 'average') $replace = array_sum($col_values) / count($col_values);
					break;
				//crmv@121616
				case 'all':
				case 'at_least_one':
					$replace = $values;
					break;
				//crmv@121616e
				case 'last':
					$row = end($values);
					$replace = $row[$tcol];
					break;
				case 'seq':
					$row = array_slice($values, $seq-1, 1);
					$replace = $row[0][$tcol];
					break;
				case 'curr':
				case '':
					if (!is_null($cycleIndex)) {
						if ($mode == 'modulelight') {
							static $retrievedObject = array();
							if (!isset($retrievedObject[$cycleIndex])) {
								$modulelightname = 'ModLight'.str_replace('ml','',$tfield);
								$retrievedObject[$cycleIndex] = CRMEntity::getInstance($modulelightname);
								$retrievedObject[$cycleIndex]->retrieve_entity_info_no_html($cycleIndex,$modulelightname);
							}
							$replace = $retrievedObject[$cycleIndex]->column_fields[$tcol];
						} else {
							$row = $values[$cycleIndex];
							if (is_array($row) && array_key_exists($tcol, $row)) {
								$replace = $row[$tcol];
							}
						}
						// crmv@195745
						if ($relmod && $relfield && !empty($replace) && $replace > 0) {
							// replace is the crmid of another module
							$replaceMod = getSalesEntityType($replace);
							if ($replaceMod == $relmod) {
								$replace = self::getFieldRawValue($relmod, $relfield, $replace);
								// TODO: handle uitypes formatting
							}
						}
						// crmv@195745e
					}
					break;
			}
		}
		return $replace;
	}
	//crmv@106857e
	
	//crmv@112539
	function getLogElement($running_process,$elementid) {
		global $adb, $table_prefix;
		$return = array();
		$result = $adb->pquery("select info from {$table_prefix}_running_processes_logsi where running_process = ? and elementid = ?", array($running_process, $elementid));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$return[] = Zend_Json::decode($row['info']);
			}
		}
		return $return;
	}
	function deleteRecord($processesid,$elementid,$module,$record) {
		global $adb, $table_prefix;
		
		$focusProcesses = CRMEntity::getInstance('Processes');
		$focusProcesses->retrieve_entity_info_no_html($processesid,'Processes');
		
		$result = $adb->pquery("select id, info from {$table_prefix}_running_processes_logsi where running_process = ? and elementid = ?", array($focusProcesses->column_fields['running_process'],$elementid));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$info = Zend_Json::decode($row['info']);
				if ($info['crmid'] == $record) {
					$adb->pquery("delete from {$table_prefix}_running_processes_logsi where id = ?", array($row['id']));
				}
			}
		}

		$adb->pquery("delete from {$table_prefix}_processmaker_rec where crmid = ?", array($record));
		
		$focus = CRMEntity::getInstance($module);
		$focus->trash($module,$record);
	}
	function rollback($mode,$focusProcesses,$elementid='',$prev_elementid='') { //crmv@182554
		global $adb, $table_prefix, $current_user;
		require_once('modules/Settings/ProcessMaker/ProcessMakerEngine.php');
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		$entityCache = new VTEntityCache($current_user);
		
		//crmv@125434 crmv@180440 crmv@182554
		// in TT-180440 you can find a script example for contine execution with processes with multiple flows inside (parallel gateway(s))
		$prev_elementid = $this->getCurrentElementId($focusProcesses->column_fields['running_process'],$focusProcesses->id,$elementid);
		if (empty($prev_elementid)) return false;
		//crmv@125434e crmv@180440e crmv@182554e
		
		$prev_elementid_info = $this->getStructureElementInfo($focusProcesses->column_fields['processmaker'],$prev_elementid,'shapes',$focusProcesses->column_fields['running_process']);
		if ($mode == 'continue_execution') {
			$elementid = $prev_elementid;
			$info = $prev_elementid_info;
		} else {
			$info = $this->getStructureElementInfo($focusProcesses->column_fields['processmaker'],$elementid,'shapes',$focusProcesses->column_fields['running_process']);
		}
		$engineType = $this->getEngineType($info);
		if (!in_array($engineType,array('Condition','Action')) || $this->isStartTask($focusProcesses->column_fields['processmaker'],$elementid,$focusProcesses->column_fields['running_process'])) return false;

		// calculate metaid and wsId
		if ($engineType == 'Condition') {
			$data = $this->retrieve($focusProcesses->column_fields['processmaker']);
			$vte_metadata = Zend_Json::decode($data['vte_metadata']);
			$metadata = $vte_metadata[$elementid];
			if (strpos($metadata['moduleName'],':') === false) {
				$result = $adb->pquery("SELECT id FROM {$table_prefix}_processmaker_metarec WHERE processid = ? AND elementid = ?", array($focusProcesses->column_fields['processmaker'],$elementid));
				if ($result && $adb->num_rows($result) > 0) {
					$metaid = $adb->query_result($result,0,'id');
					$related_to = ProcessMakerEngine::getCrmid($metaid,$focusProcesses->column_fields['running_process']);
					if (!empty($related_to)) {
						$wsId = $related_to; //crmv@OPER10174
					}
				}
			} else {
				list($metaid,$module) = explode(':',$metadata['moduleName']);
				if ($module == 'DynaForm') {
					$processDynaFormObj = ProcessDynaForm::getInstance();
					$crmid = $processDynaFormObj->getProcessesId($focusProcesses->column_fields['running_process'],$metaid);
					$metaid = '';
					$wsId = $crmid; //crmv@OPER10174
				} else {
					$related_to = ProcessMakerEngine::getCrmid($metaid,$focusProcesses->column_fields['running_process']);
					if (!empty($related_to)) {
						$wsId = $related_to; //crmv@OPER10174
					}
				}
			}
		} else {
			// search record in the element
			$result = $adb->pquery("SELECT rec.id, rec.crmid
				FROM {$table_prefix}_processmaker_metarec metarec
				INNER JOIN {$table_prefix}_processmaker_rec rec ON metarec.id = rec.id AND metarec.processid = ? AND rec.running_process = ?
				WHERE metarec.elementid = ?", array($focusProcesses->column_fields['processmaker'],$focusProcesses->column_fields['running_process'],$elementid));
			if ($adb->num_rows($result) == 0) {
				// if do not found get the last record
				$result = $adb->limitpQuery("SELECT id, crmid FROM {$table_prefix}_processmaker_rec WHERE running_process = ? ORDER BY crmid DESC", 0, 1, array($focusProcesses->column_fields['running_process']));
			}
			if ($result && $adb->num_rows($result) > 0) {
				$metaid = $adb->query_result($result,0,'id');
				$wsId = $adb->query_result($result,0,'crmid'); //crmv@OPER10174
			}
		}
		$wsId = vtws_getWebserviceEntityId(getSalesEntityType($wsId),$wsId); //crmv@OPER10174
		
		//echo $focusProcesses->column_fields['running_process'].','.$focusProcesses->column_fields['processmaker'].",$prev_elementid,$elementid,$wsId,$metaid";die;
		$PMEngine = ProcessMakerEngine::getInstance($focusProcesses->column_fields['running_process'],$focusProcesses->column_fields['processmaker'],$prev_elementid,$elementid,$wsId,$metaid,$entityCache);
		
		$processEnded = $PMEngine->isEndProcess($prev_elementid_info['type']);
		if ($processEnded) $PMEngine->endProcess(0);
		
		if ($mode == 'continue_execution') {
			$PMEngine->activateProcess();
			$PMEngine->execute($engineType,$info['type']);
		} elseif ($mode == 'change_position') {
			$PMEngine->log_rollback = $current_user->id;
			$PMEngine->activateProcess(false);
			$PMEngine->trackProcess($prev_elementid,$elementid);
		}
		
		return true;
	}
	function isEnableRollback() {
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		return $is_admin;
	}
	function isActiveRunningProcess($running_process) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select active from {$table_prefix}_running_processes where id = ?", array($running_process));
		$active = false;
		if ($result && $adb->num_rows($result) > 0) {
			$active = ($adb->query_result($result,0,'active') == '1');
		}
		return $active;
	}
	//crmv@112539e
	function getAllConditionals($record) {
		require_once('modules/Settings/ProcessMaker/ProcessMakerEngine.php');
		global $adb, $table_prefix;
		static $conditionals = array();
		static $cache = false;
		if (!$cache) {
			$result = $adb->pquery("select {$table_prefix}_running_processes.processmakerid, {$table_prefix}_processmaker_conditionals.running_process, {$table_prefix}_running_processes.xml_version_forced, {$table_prefix}_processmaker_conditionals.elementid
				from {$table_prefix}_processmaker_conditionals
				inner join {$table_prefix}_running_processes on {$table_prefix}_running_processes.id = {$table_prefix}_processmaker_conditionals.running_process
				inner join {$table_prefix}_processmaker on {$table_prefix}_processmaker.id = {$table_prefix}_running_processes.processmakerid
				where {$table_prefix}_processmaker.active = ? and {$table_prefix}_running_processes.active = ? and {$table_prefix}_processmaker_conditionals.crmid = ?",
				array(1,1,$record));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result,-1,false)) {
					$processmakerid = $row['processmakerid'];
					$running_process = $row['running_process'];
					$elementid = $row['elementid'];
					
					$data = $this->retrieve($processmakerid, $row['xml_version_forced']);
					$vte_metadata = Zend_Json::decode($data['vte_metadata']);
					$vte_metadata_conditionals = $vte_metadata[$elementid]['conditionals'];
					if (!empty($vte_metadata_conditionals)) {
						foreach($vte_metadata_conditionals as $tmp) {
							list($metaid,$module) = explode(':',$tmp['moduleName']);
							$crmid = ProcessMakerEngine::getCrmid($metaid,$running_process);
							if ($record == $crmid) {
								$conditionals[] = $tmp;
							}
						}
					}
				}
			}
			$cache = true;
		}
		return $conditionals;
	}
	function getConditionalPermissions($conditionals, &$column_fields) {
		global $adb, $table_prefix, $current_user;
		$column_fields_bkp = $column_fields;
		$record = $column_fields['record_id'];
		$module = $column_fields['record_module'];
		$webserviceObject = VtenextWebserviceObject::fromName($adb,$module);//crmv@207871
		$wsRecord = vtws_getId($webserviceObject->getEntityId(),$record);

		// force cache with column_fields of $_REQUEST in order to manage the live conditionals
		$cache_column_fields = $column_fields;
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		$entityCache = new VTEntityCache($current_user);
		$entityCache->forId($wsRecord);
		unset($cache_column_fields['record_id']);
		unset($cache_column_fields['record_module']);
		$cache_column_fields['id'] = $wsRecord;
		$entityCache::$cache[$wsRecord]->data = $cache_column_fields; //crmv@OPER10174

		// get fields informations
		$column_fields_check = $column_fields_bkp;	// for table fields check
		$fields = array();
		$tvh_id = $this->getSystemVersion4Record($record,array('tabs',$module,'id'));
		if (!empty($tvh_id)) {
			$result = $adb->pquery("select * from {$table_prefix}_field_vh where versionid = ? and tabid = ?", array($tvh_id,getTabid2($module)));
		} else {
			$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ?", array(getTabid2($module)));
		}
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$fields[$row['fieldname']] = WebserviceField::fromArray($adb,$row);
				if ($fields[$row['fieldname']]->getFieldDataType() == 'table') {
					if (is_array($column_fields[$row['fieldname']])) {
						// retrieve from column_fields (EditViewConditionals)
						$column_fields_check[$row['fieldname']] = $column_fields[$row['fieldname']]['rows'];
					} else {
						// retrieve from db
						require_once('include/utils/ModLightUtils.php');
						$MLUtils = ModLightUtils::getInstance();
						$columns = $MLUtils->getColumns($module,$row['fieldname']);
						$column_fields_check[$row['fieldname']] = array();
						$values = $MLUtils->getValues($module,$record,$row['fieldname'],$columns);
						if (!empty($values)) {
							foreach($values as $tmp) {
								array_push($column_fields_check[$row['fieldname']],$tmp['row']);
							}
						}
					}
				}
			}
		}

		$role_grp_checks = array();
		//tutti
		$role_grp_checks[] = 'ALL';
		//ruoli
		$role_grp_checks[] = "roles::".$current_user->roleid;
		//ruoli e subordinati
		$subordinates=getRoleAndSubordinatesInformation($current_user->roleid);
		$parent_role=$subordinates[$current_user->roleid][1];
		if (!is_array($parent_role)){
			$parent_role = explode('::',$parent_role);
			foreach ($parent_role as $parent_role_value){
				$role_grp_checks[] = "rs::".$parent_role_value;
			}
		}
		//gruppi
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		if (is_array($current_user_groups)){
			foreach ($current_user_groups as $current_user_groups_value){
				$role_grp_checks[] = "groups::".$current_user_groups_value;
			}
		}

		// split standard and table-field conditionals
		$conditionals_std = array();
		$conditionals_tabs = array();
		foreach($conditionals as $i => $conditional) {
			$tab = false;
			if (!empty($conditional['conditions'])) {
				foreach($conditional['conditions'] as $subconditions) {
					if (!empty($subconditions['conditions'])) {
						foreach($subconditions['conditions'] as $subcondition) {
							if (isset($subcondition['tabfieldopt'])) {
								$tab = true;
								break;
							}
						}
					}
				}
			}
			($tab) ? $conditionals_tabs[] = $conditionals[$i] : $conditionals_std[] = $conditionals[$i];
		}
		if (!empty($conditionals_tabs)) {
			$actionType = $this->getActionTypes('Cycle');
			require_once($actionType['php_file']);
			$actionCycle = new $actionType['class']();
		}

		global $edit_view_conditionals_mode;
		$edit_view_conditionals_mode = true;
		$permissions = array();
		$i = 0;
		if (!empty($conditionals_std)) {
			foreach($conditionals_std as $conditional) {
				$role_grp_check = $conditional['role_grp_check'];
				if (in_array($role_grp_check,$role_grp_checks)) {
					$conditions = Zend_Json::encode($conditional['conditions']);
					if ($this->evaluateCondition($entityCache, $wsRecord, $conditions)) {
						foreach($column_fields_check as $fieldname => $value) {
							$perm = $conditional['fpofv'][$fieldname];
							$this->setFieldConditionalPermissions($perm, $i, $fieldname, $permissions);
							$field = $fields[$fieldname];
							if (isset($fields[$fieldname]) && $field->getFieldDataType() == 'table') {
								if (is_array($value)) {
									foreach($value as $seq => $row) {
										foreach($row as $column => $column_value) {
											$perm = $conditional['fpofv'][$fieldname.'::'.$column];
											$this->setFieldConditionalPermissions($perm, $i, $fieldname.'_'.$column.'_'.$seq, $permissions);
										}
									}
								}
							}
						}
						$i++;
					}
				}
			}
		}
		// check for conditionals in table-fields
		if (!empty($conditionals_tabs)) {
			foreach($column_fields_check as $fieldname => $value) {
				$field = $fields[$fieldname];
				if (isset($fields[$fieldname]) && $field->getFieldDataType() == 'table') {
					if (is_array($value)) {
						foreach($value as $seq => $row) {
							foreach($conditionals_tabs as $conditional) {
								$role_grp_check = $conditional['role_grp_check'];
								if (in_array($role_grp_check,$role_grp_checks)) {
									$conditions = Zend_Json::encode($conditional['conditions']);
									if ($actionCycle->checkRowConditions(null, $column_fields_check, $conditions, $seq)) {
										// applico permessi alla riga e anche agli altri campi del modulo
										$fpofv = $conditional['fpofv'];
										foreach($fpofv as $f => $fp) {
											// if it is a column of the current table-field OK
											// if it is a column of another table-field SKIP
											// if it is a standard field OK
											if (strpos($f,'::') !== false) {
												list($f1,$f2) = explode('::',$f);
												if ($f1 == $fieldname) {
													$this->setFieldConditionalPermissions($fp, $i, $f1.'_'.$f2.'_'.$seq, $permissions);
												}
											} else {
												$this->setFieldConditionalPermissions($fp, $i, $f, $permissions);
											}
										}
										$i++;
									}
								}
							}
						}
					}
				}
			}
		}
		
		// set in request cache
		$cache = RCache::getInstance();
		$cache->set('conditional_permissions', $permissions);
		
		$conditional_permissions = array();
		if (!empty($permissions)) {
			// permissions for table field are managed in TableFieldUtils::generateRowVars
			$fieldids = array();
			$result = $adb->pquery("select fieldid, fieldname from {$table_prefix}_field where tabid = ? and fieldname in (".generateQuestionMarks(array_keys($permissions)).")", array(getTabid($module),array_keys($permissions)));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$fieldids[$row['fieldname']] = $row['fieldid'];
				}
			}
			foreach($permissions as $fieldname => $permission) {
				if (!isset($fieldids[$fieldname])) continue;
				if ($permission['readonly'] == 99) {
					$f2fp_visible = 1;
					$f2fp_editable = 0;
				} elseif ($permission['readonly'] == 100) {
					$f2fp_visible = 0;
					$f2fp_editable = 0;
				} else {
					$f2fp_visible = 1;
					$f2fp_editable = 1;
				}
				$conditional_permissions[$fieldids[$fieldname]] = Array(
					'f2fp_visible'=>$f2fp_visible,
					'f2fp_editable'=>$f2fp_editable,
					'f2fp_mandatory'=>($permission['mandatory'] == 1)?1:0,
				);
				if (isset($permission['value'])) {
					$column_fields[$fieldname] = $this->replaceTags($permission['value'],$column_fields_bkp);
				}
			}
		}
		return $conditional_permissions;
	}
	//crmv@105312 crmv@112297
	function setFieldConditionalPermissions($perm, $i, $fieldname, &$permissions) {
		if ($perm['FpovManaged'] == 1) {
			if ($i == 0) {
				$permissions[$fieldname]['readonly'] = 1;
				$permissions[$fieldname]['mandatory'] = false;
			}
			if ($perm['FpovReadPermission'] == 1) {
				if ($perm['FpovWritePermission'] == 1) {
					$readonly = 1;
					if ($perm['FpovMandatoryPermission'] == 1) {
						$permissions[$fieldname]['mandatory'] = true;
					}
				} else {
					$readonly = 99;
				}
			} else {
				$readonly = 100;
			}
			//crmv@103826
			// the first conditional overwrite the standard permissions
			// or if there are more conditionals verified set the most restrictive rule
			if ($i == 0 || $readonly > $permissions[$fieldname]['readonly']) {
				$permissions[$fieldname]['readonly'] = $readonly;
			}
			if ($perm['FpovValueActive'] == 1) $permissions[$fieldname]['value'] = $perm['FpovValueStr'];
			//crmv@103826e
		}
	}
	function replaceTags($value, $columns, $selector='/(\$([a-zA-Z0-9_]+))/', $row=null) { // crmv@200250
		// apply sdk functions
		preg_match_all('/\$sdk:([a-zA-Z0-9_]+)\(([^)]*)\)/', $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			$sdkFieldConditions = SDK::getProcessMakerFieldActions();
			foreach($matches as $match) {
				$tag = trim($match[0]);
				$funct = $match[1];
				if (isset($sdkFieldConditions[$funct])) {
					$params = trim($match[2]);
					if (!empty($params)) {
						$params = explode(',',$params);
						array_walk($params, function(&$v,$k) {
							$v = trim($v);
						});
						// repalce tags
						foreach($params as &$param) {
							preg_match_all($selector, $param, $matches, PREG_SET_ORDER);
							if (!empty($matches)) {
								// crmv@200250
								if ($row !== null && strpos($param,'::') !== false) {
									list($ml,$fieldname) = explode('::',$param);
									$ml = str_replace('$','',$ml);
									$param = $columns[$ml][$row][$fieldname];
								} else {
									$fieldname = str_replace('$','',$param);
									$param = $columns[$fieldname];
								}
								// crmv@200250e
							}
						}
					} else {
						$params = array();
					}
					require_once($sdkFieldConditions[$funct]['src']);
					$replacement = call_user_func_array($funct, $params);
				} else {
					$replacement = '';
				}
				$value = str_replace($tag,$replacement,$value);
			}
		}
		// replace tags
		preg_match_all($selector, $value, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $match) {
				$fieldname = str_replace('$','',$match[0]);
				$fieldvalue = $columns[$fieldname];
				$value = str_replace($match[0],$fieldvalue,$value);
			}
		}
		return $value;
	}
	//crmv@105312e crmv@112297e
	
	//crmv@115268
	function preserveRequest() {
		array_push($this->preserved_request, $_REQUEST);
		$_REQUEST = array();
	}
	function restoreRequest() {
		$_REQUEST = array_pop($this->preserved_request);
	}
	function setDefaultDataFormat() {
		global $current_user;
		$this->preserved_date_format = $current_user->date_format;
		$current_user->date_format = 'yyyy-mm-dd';
		$current_user->column_fields['date_format'] = 'yyyy-mm-dd';
	}
	function restoreDataFormat() {
		global $current_user;
		$current_user->date_format = $this->preserved_date_format;
		$current_user->column_fields['date_format'] = $this->preserved_date_format;
	}
	//crmv@115268e
	
	// crmv@171524
	function getRecordsBrothers($crmid, $status = 'all', $check_trigger_queue = false) {
		global $adb, $table_prefix;
		$brothers = array();
		
		$query = "select {$table_prefix}_processmaker_rec.running_process from {$table_prefix}_processmaker_rec";
		if ($status != 'all') $query .= " inner join {$table_prefix}_running_processes on {$table_prefix}_running_processes.id = {$table_prefix}_processmaker_rec.running_process";
		$query .= " where crmid = ?";
		$params = array($crmid);
		if ($status != 'all') {
			$query .= " and {$table_prefix}_running_processes.end = ?";
			$params[] = ($status == 'running') ? 0 : 1;
		}
		$result = $adb->pquery($query, $params);
		
		if ($result && $adb->num_rows($result) > 0) {
			$running_process = array();
			while ($row = $adb->fetchByAssoc($result)) {
				$running_process[] = $row['running_process'];
			}
			$query = "select distinct {$table_prefix}_processmaker_rec.crmid from {$table_prefix}_processmaker_rec";
			if ($check_trigger_queue) $query .= " inner join {$table_prefix}_trigger_queue on {$table_prefix}_trigger_queue.crmid = {$table_prefix}_processmaker_rec.crmid";
			$query .= " where running_process in (" . generateQuestionMarks($running_process) . ") and {$table_prefix}_processmaker_rec.crmid <> ?";
			$result = $adb->pquery($query, array($running_process, $crmid));
			if ($result && $adb->num_rows($result) > 0) {
				while ($row = $adb->fetchByAssoc($result)) {
					$brothers[] = $row['crmid'];
				}
			}
		}
		
		return $brothers;
	}
	// crmv@171524e

	//crmv@147720 crmv@155375
	function compareVersionNumber($version1, $operator, $version2) {
		return version_compare($version1, $version2, $operator);
	}
	function getNewVersionNumber($processmakerid) {
		$data = $this->retrieve($processmakerid);
		$current_version = $data['version'];
		if (empty($current_version)) {
			$version = $this->startVersionNumber;
		} else {
			$v = explode('.', $current_version);
			$v[count($v)-1]++;
			$version = implode('.', $v);
		}
		return $version;
	}
	function incrementVersion($processmakerid, $force_version=false) {
		global $adb, $table_prefix;
		
		$this->historicizeSaveForceVersion($processmakerid, $force_version);
		
		$version = $this->getNewVersionNumber($processmakerid);
		$adb->pquery("update {$this->table_name} set version=?, pending_changes=? where id=?", array($version, 0, $processmakerid));
		
		return $version;
	}
	function historicizeSaveForceVersion($processmakerid, $force_version=false) {
		global $adb, $table_prefix;
		
		$data = $this->retrieve($processmakerid);
		$current_xml_version = $data['xml_version'];
		
		$this->historicizeVersionTables($current_xml_version, $processmakerid);
		$new_xml_version = $this->saveVersion($processmakerid);	// force new row in _processmaker_versions with the last structure and metadata
		if ($force_version) {
			// close pending versions of tabs, roles, profiles, conditionals
			$pendings = array();
			if ($this->checkPendingRelatedSystemVersions($processmakerid,$pendings)) {
				foreach($pendings as $type => $info) {
					if ($type == 'tabs') {
						foreach($info as $m => $p) {
							if ($p) {
								require_once('modules/Settings/LayoutBlockListUtils.php');
								$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
								$layoutBlockListUtils->closeVersion($m);
							}
						}
					} else {
						if ($info) {
							if ($type == 'roles') {
								require_once('include/utils/UserInfoUtil.php');
								$userInfoUtils = UserInfoUtils::getInstance();
								$userInfoUtils->closeVersion_role();
							} elseif ($type == 'profiles') {
								require_once('include/utils/UserInfoUtil.php');
								$userInfoUtils = UserInfoUtils::getInstance();
								$userInfoUtils->closeVersion_profile();
							} elseif ($type == 'conditionals') {
								require_once('modules/Conditionals/ConditionalsVersioning.php');
								$conditionalsVersioning = ConditionalsVersioning::getInstance();
								$conditionalsVersioning->closeVersion();
							}
						}
					}
				}
			}
			// save version of tabs, roles, profiles, conditionals associated to this process version
			$this->saveRelatedSystemVersions($processmakerid,$current_xml_version);
			// save xml_version_forced in _running_processes
			$adb->pquery("update {$table_prefix}_running_processes set xml_version_forced = ? where processmakerid = ? and active = 1 and end = 0 and version_chosen = 0 and (xml_version_forced is null or xml_version_forced = '')", array($current_xml_version,$processmakerid));
		} else {
			// update xml_version (used only for the graph) with the last version because the user choose to use LBL_NEW_VERSION(Usa recente) instead of LBL_OLD_VERSION(Congela)
			$adb->pquery("update {$table_prefix}_running_processes set xml_version = ? where processmakerid = ? and active = 1 and end = 0 and version_chosen = 0 and (xml_version_forced is null or xml_version_forced = '')", array($new_xml_version, $processmakerid));
		}
		$adb->pquery("update {$table_prefix}_running_processes set version_chosen = 1 where processmakerid = ? and active = 1 and end = 0 and version_chosen = 0", array($processmakerid));
	}
	function saveVersion($processmakerid) {
		global $adb, $table_prefix, $current_user;
		$result = $adb->pquery("select * from {$this->table_name} where id = ?", array($processmakerid));
		$xml_version = $adb->query_result($result,0,'xml_version');
		$xml_old = $adb->query_result_no_html($result,0,'xml');
		$vte_metadata = $adb->query_result_no_html($result,0,'vte_metadata');
		$structure = $adb->query_result_no_html($result,0,'structure');
		$helper = $adb->query_result_no_html($result,0,'helper');
		$version = $adb->query_result_no_html($result,0,'version');
		$adb->pquery("insert into {$table_prefix}_processmaker_versions(processmakerid,xml_version,userid,date_version,vte_metadata,structure,helper,version) values(?,?,?,?,?,?,?,?)",
			array($processmakerid,$xml_version,$current_user->id,date('Y-m-d H:i:s'),$vte_metadata,$structure,$helper,$version));
		$adb->updateClob("{$table_prefix}_processmaker_versions",'xml',"processmakerid=$processmakerid and xml_version=$xml_version",$xml_old);
		
		// increment xml_version
		$next_xml_version = $xml_version+1;
		$adb->pquery("update {$this->table_name} set xml_version=? where id=?", array($next_xml_version, $processmakerid));

		return $next_xml_version;
	}
	function historicizeVersionTables($versionid, $processmakerid) {
		global $adb, $table_prefix;
		
		require_once('include/utils/UserInfoUtil.php');
		$userInfoUtils = UserInfoUtils::getInstance();
		
		$userInfoUtils->historicizeVersionTables($versionid, array(
			array(
				'table'=>$table_prefix.'_process_dynaform_meta',
				'condition'=>"processid = $processmakerid",
			),
			array(
				'table'=>$table_prefix.'_process_extws_meta',
				'condition'=>"processid = $processmakerid",
			),
			array(
				'table'=>$table_prefix.'_processmaker_metarec',
				'condition'=>"processid = $processmakerid",
			),
			array(
				'table'=>$table_prefix.'_processmaker_rel',
				'condition'=>"processid = $processmakerid",
			),
			array(
				'table'=>$table_prefix.'_processmaker_rel',
				'condition'=>"related = $processmakerid",
			),
			array(
				'table'=>$table_prefix.'_subprocesses',
				'condition'=>"processid = $processmakerid",
			),
		));
	}
	function checkIncrementVersion($processmakerid) {
		global $adb, $table_prefix;
		$check = '0';
		$result = $adb->pquery("select count(*) as \"count\" from {$table_prefix}_running_processes where processmakerid = ? and active = 1 and end = 0 and version_chosen = 0 and (xml_version_forced is null or xml_version_forced = '')", array($processmakerid));
		if ($result && $adb->num_rows($result) > 0 && intval($adb->query_result($result,0,'count')) > 0) {
			$check = '1';
			$pendings = array();
			if ($this->checkPendingRelatedSystemVersions($processmakerid,$pendings)) {
				$check = array();
				foreach($pendings as $type => $info) {
					if ($type == 'tabs') {
						foreach($info as $m => $p) {
							if ($p) $check[] = getTranslatedString('LBL_LAYOUT_EDITOR','Settings').' '.getTranslatedString($m,$m);
						}
					} else {
						if ($info) {
							if ($type == 'roles') $check[] = getTranslatedString('LBL_ROLES','Settings');
							elseif ($type == 'profiles') $check[] = getTranslatedString('LBL_PROFILES','Settings');
							elseif ($type == 'conditionals') $check[] = getTranslatedString('Conditionals','Conditionals');
						}
					}
				}
				$check = Zend_Json::encode($check);
			}
		}
		return $check;
	}
	function checkPendingRelatedSystemVersions($processmakerid, &$pendings=array()) {
		require_once('include/utils/UserInfoUtil.php');
		require_once('modules/Settings/LayoutBlockListUtils.php');
		require_once('modules/Conditionals/ConditionalsVersioning.php');
		$userInfoUtils = UserInfoUtils::getInstance();
		$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
		$conditionalsVersioning = ConditionalsVersioning::getInstance();
		$pending = false;

		$tabs = $this->getRecordsInvolvedModules($processmakerid,true,true);
		if (!empty($tabs)) {
			foreach($tabs as $module) {
				$p = $layoutBlockListUtils->getPendingVersion(getTabid2($module));
				$pendings['tabs'][$module] = (!empty($p));
				if(!empty($p)) $pending = true;
			}
		}
		
		$rolePendingVersion = $userInfoUtils->getPendingVersion_role();
		$pendings['roles'] = (!empty($rolePendingVersion));
		if(!empty($rolePendingVersion)) $pending = true;
		
		$profilePendingVersion = $userInfoUtils->getPendingVersion_profile();
		$pendings['profiles'] = (!empty($profilePendingVersion));
		if(!empty($profilePendingVersion)) $pending = true;
		
		$conditionalPendingVersion = $conditionalsVersioning->getPendingVersion();
		$pendings['conditionals'] = (!empty($conditionalPendingVersion));
		if(!empty($conditionalPendingVersion)) $pending = true;

		return $pending;
	}
	function saveRelatedSystemVersions($processmakerid, $xml_version) {
		global $adb, $table_prefix;
		require_once('include/utils/UserInfoUtil.php');
		$userInfoUtils = UserInfoUtils::getInstance();
		$versions = $userInfoUtils->getCurrentVersionNumbers(array('tabs','roles','profiles','conditionals'),array('tabs'=>$this->getRecordsInvolvedModules($processmakerid,true,true)));
		$adb->pquery("update {$table_prefix}_processmaker_versions set system_versions = ? where processmakerid = ? and xml_version = ?", array(Zend_Json::encode($versions), $processmakerid, $xml_version));
	}
	function getSystemVersion4Record($record, $params=array()) {
		global $adb, $table_prefix;
		if (empty($adb) || !Vtecrm_Utils::CheckTable($table_prefix.'_processmaker_rec')) return false;
		static $cache = array();
		if (!isset($cache[$record])) {
			$cache[$record] = false;	// use current system configuration (default)
			
			$result = $adb->pquery("select vers.xml_version, vers.system_versions
				from {$table_prefix}_processmaker_rec rec
				inner join {$table_prefix}_running_processes run on rec.running_process = run.id
				left join {$table_prefix}_processmaker_versions vers on run.processmakerid = vers.processmakerid and run.xml_version_forced = vers.xml_version
				where rec.crmid = ?
				order by vers.xml_version", array($record));
			if ($result && $adb->num_rows($result) > 0) {
				$xml_version = $adb->query_result($result,0,'xml_version');
				if (empty($xml_version)) {
					// use current system configuration
				} else {
					// use last configuration applied
					$system_versions = Zend_Json::decode($adb->query_result_no_html($result,0,'system_versions'));
					//crmv@150751 filtro e tengo solo le versioni vecchie: se la versione salvata è uguale a quella corrente salto
					if (!empty($system_versions) && !empty($system_versions['tabs'])) {
						require_once('include/utils/UserInfoUtil.php');
						$userInfoUtils = UserInfoUtils::getInstance();
						$current_versions = $userInfoUtils->getCurrentVersionNumbers(array('tabs','roles','profiles','conditionals'),array('tabs'=>array_keys($system_versions['tabs'])));
						if (!empty($current_versions)) {
							foreach($current_versions as $cv_type => $cv_info) {
								if ($cv_type == 'tabs') {
									if (!empty($cv_info)) {
										foreach($cv_info as $c_module => $c_module_info) {
											if ($c_module_info['id'] == $system_versions['tabs'][$c_module]['id']) unset($system_versions['tabs'][$c_module]);
										}
									}
									if (empty($system_versions['tabs'])) unset($system_versions['tabs']);
								} else {
									if ($cv_info['id'] == $system_versions[$cv_type]['id']) unset($system_versions[$cv_type]);
								}
							}
						}
					}
					//crmv@150751e
					$cache[$record] = $system_versions;
					$cache[$running_process]['processmaker'] = array('xml_version'=>$xml_version,'version'=>$adb->query_result($result,0,'version'));
				}
			}
		}
		if ($cache[$record] === false) {
			return false;
		} else {
			$return = $cache[$record];
			if (!empty($params)) {
				foreach($params as $param) {
					$return = $return[$param];
				}
			}
			return $return;
		}
	}
	function getSystemVersion4RunningProcess($running_process, $params=array()) {
		global $adb, $table_prefix;
		static $cache = array();
		if (!isset($cache[$running_process])) {
			$cache[$running_process] = false;	// use current system configuration (default)
			
			$result = $adb->pquery("select vers.xml_version, vers.version, vers.system_versions
				from {$table_prefix}_running_processes run
				left join {$table_prefix}_processmaker_versions vers on run.processmakerid = vers.processmakerid and run.xml_version_forced = vers.xml_version
				where run.id = ?
				order by vers.xml_version", array($running_process));
			if ($result && $adb->num_rows($result) > 0) {
				$xml_version = $adb->query_result($result,0,'xml_version');
				if (empty($xml_version)) {
					// use current system configuration
				} else {
					// use last configuration applied
					$system_versions = Zend_Json::decode($adb->query_result_no_html($result,0,'system_versions'));
					$cache[$running_process] = $system_versions;
					$cache[$running_process]['processmaker'] = array('xml_version'=>$xml_version,'version'=>$adb->query_result($result,0,'version'));
				}
			}
		}
		if ($cache[$running_process] === false) {
			return false;
		} else {
			$return = $cache[$running_process];
			if (!empty($params)) {
				foreach($params as $param) {
					$return = $return[$param];
				}
			}
			return $return;
		}
	}
	//crmv@147720e crmv@155375e
	
	//crmv@153321_5
	function getCache($item) {
		if ($this->cacheStorage == 'session') {
			$cacheInstance = SCache::getInstance();
			$cache = $cacheInstance->get('processmaker');
			return $cache[$item];
		} elseif ($this->cacheStorage == 'file') {
			$cacheInstance = new CacheStorageFile('cache/processmaker.tmp', 'json');
			return $cacheInstance->get($item);
		}
	}
	function setCache($item,$value) {
		if ($this->cacheStorage == 'session') {
			$cacheInstance = SCache::getInstance();
			$cache = $cacheInstance->get('processmaker');
			if (empty($cache)) $cache = array();
			$cache[$item] = $value;
			$cacheInstance->set('processmaker',$cache);
		} elseif ($this->cacheStorage == 'file') {
			$cacheInstance = new CacheStorageFile('cache/processmaker.tmp', 'json');
			$cacheInstance->set($item,$value);
		}
	}
	function clearCache($item) {
		if ($this->cacheStorage == 'session') {
			$cacheInstance = SCache::getInstance();
			$cache = $cacheInstance->get('processmaker');
			unset($cache[$item]);
			$cacheInstance->set('processmaker',$cache);
		} elseif ($this->cacheStorage == 'file') {
			$cacheInstance = new CacheStorageFile('cache/processmaker.tmp', 'json');
			$cacheInstance->clear($item);
		}
	}
	function clearProcessMakerCache($processmakerid) {
		if ($this->cacheStorage == 'session') {
			$cacheInstance = SCache::getInstance();
			$cache = $cacheInstance->get('processmaker');
			unset($cache['processmaker_describe_modules_'.$processmakerid]);
			unset($cache['processmaker_entity_options_'.$processmakerid]);
			$cacheInstance->set('processmaker',$cache);
		} elseif ($this->cacheStorage == 'file') {
			$cacheInstance = new CacheStorageFile('cache/processmaker.tmp', 'json');
			$cacheInstance->clear('processmaker_describe_modules_'.$processmakerid);
			$cacheInstance->clear('processmaker_entity_options_'.$processmakerid);
		}
	}
	//crmv@153321_5e
	
	//crmv@182554
	function sortTags(&$matches) {
		$ordered_matches = Array();
		foreach($matches as $matchid=>$matcharr){
			$ordered_matches[$matchid] = strlen($matcharr[0]);
		}
		arsort($ordered_matches);
		$new_matches = Array();
		foreach($ordered_matches as $ordid=>$ordlen){
			$new_matches[] = $matches[$ordid];
		}
		$matches = $new_matches;
		unset($new_matches,$ordered_matches);
	}
	//crmv@182554e
	
	//crmv@185361
	function getAttacherFather($id,$elementid) {
		$structure = $this->getStructure($id);
		foreach($structure['tree'] as $shapeid => $info) {
			$attachers = $info['attachers'];
			if (!empty($attachers)) {
				if (in_array($elementid,$attachers)) return $shapeid;
			}
		}
	}
	//crmv@185361e

	//crmv@185548
	function get_all_related_modules($related_modules){
		global $adb, $table_prefix;
		
		$modules_not_supported = array("Sms", "Fax", "Processes"); // crmv@202102
		
		$modules_list = array();
		
		foreach($related_modules as $index => $field){
			$relationid = $field->relationid;
			$query = "SELECT {$table_prefix}_tab.name FROM {$table_prefix}_tab
			INNER JOIN {$table_prefix}_relatedlists ON {$table_prefix}_tab.tabid = {$table_prefix}_relatedlists.related_tabid
			WHERE relation_id = ?";
			$res = $adb->pquery($query, array($relationid));
			
			if($res && $adb->num_rows($res) > 0){
				$rel_modulename = $adb->query_result($res, 0, "name");
				if(!(in_array($rel_modulename, $modules_not_supported))){
					if($rel_modulename == "Calendar"){
						//Split Activities module
						$modules_list[] = "Events";
						$modules_list[] = "Calendar";
					}
					elseif($rel_modulename == "Newsletter"){
						$modules_list[] = "Newsletter";
					}
					else{
						$modules_list[] = $rel_modulename;
					}
				}
			}
		}
		
		return $modules_list;
	}
	//crmv@185548e
}

require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
class ProcessModuleMakerGenerator extends ModuleMakerGenerator {
	function __construct() {}
	//crmv@145432
	function getTODForField($field) {
		$tod = parent::getTODForField($field);
		if (in_array($field['fieldwstype'],array('integer','double')) && isset($field['length']) && isset($field['decimals'])) {
			$decimals = intval($field['decimals']);
			$length = $field['length'] - $decimals;
			$tod = 'N~O~'.$length.','.$decimals;
		} elseif ($field['fieldwstype'] == 'string') {
			if (!empty($field['length'])) $tod = 'V~O~LE~'.$field['length'];
		}
		return $tod;
	}
	//crmv@145432e
	function makeTODMandatory($tod) {
		return parent::makeTODMandatory($tod);
	}
}

require_once('modules/Settings/ModuleMaker/ModuleMakerSteps.php');
class ProcessModuleMakerSteps extends ModuleMakerSteps {
	function getNewFields() {
		//crmv@160837
		static $fields = array();
		if (!empty($fields)) return $fields;
		//crmv@160837e
		
		$unsupported_uitypes = array(1015,4);
		$fields = parent::getNewFields();
		foreach($fields as $i => $field) {
			if(in_array($field['uitype'],$unsupported_uitypes)) unset($fields[$i]);
		}
		//crmv@98570 crmv@102879
		$PMUtils = ProcessMakerUtils::getInstance();
		if (SDK::isUitype(213)) {
			$fields[] = array(
				'uitype' => 213,
				'label' => getTranslatedString('LBL_FIELD_BUTTON'),
				'vteicon2' => 'fa-hand-pointer-o',
				'properties' => array('label','onclick','code'),
				'defaults' => array('onclick'=>'function(view[,param])'),
			);
		}
		if (SDK::isUitype(220)) {
			$fields[] = array(
				'uitype' => 220,
				'label' => getTranslatedString('LBL_FIELD_TABLE'),
				'vteicon' => 'grid_on',
				'properties' => array('label','columns'),
			);
		}
		//crmv@98570e crmv@102879e
		/* crmv@174986
		if (SDK::isUitype(221)) {
			$fields[] = array(
				'uitype' => 221,
				'label' => getTranslatedString('LBL_ROLE'),
				'vteicon' => 'people',
				'properties' => array('label'),
			);
		}
		crmv@174986e */
		$fields = array_values($fields);	//crmv@106857
		return $fields;
	}
	//crmv@106857
	function getNewTableFieldColumns() {
		//crmv@160837
		static $fields = array();
		if (!empty($fields)) return $fields;
		//crmv@160837e
		
		$unsupported_uitypes = array(213,220/*,10*/,29);
		$fields = $this->getNewFields();
		foreach($fields as $i => $field) {
			if(in_array($field['uitype'],$unsupported_uitypes)) {
				unset($fields[$i]);
				continue;
			}
			// add other properties
			$fields[$i]['properties'][] = 'readonly';
			$fields[$i]['properties'][] = 'mandatory';
			$fields[$i]['properties'][] = 'newline';
			// add defaults for other properties
			$fields[$i]['defaults']['readonly'] = 1;
			$fields[$i]['defaults']['mandatory'] = false;
		}
		return $fields;
	}
	//crmv@106857e
}