<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@102879 crmv@115268 */ 

class PMActionBase {

	public $isCycleAction = false;	// true if the action is executed inside a cycleleAction
	public $cycleIndex = null;		// the current index of the cycle
	public $cycleRow = null;		// the current row of the cycle
	public $cycleRelModule = null;  // crmv@203075 for cycle related record
	protected $options = array();

	public function __construct($options = array()) {
		$this->options = $options;
	}
	
	public function getOptions() {
		return $this->options;
	}
	
	public function setOptions($options) {
		$this->options = $options;
	}
	
	//crmv@108227 crmv@146671
	// TODO: move each part in the specific action class
	/**
	 * Save the action from the request
	 */
	public function save(&$request){
		global $adb, $table_prefix, $currentModule;
		$PMUtils = ProcessMakerUtils::getInstance();
		
		$id = vtlib_purify($request['id']);
		$elementid = vtlib_purify($request['elementid']);
		$action_id = vtlib_purify($request['action_id']);
		$action_type = vtlib_purify($request['meta_action']['action_type']);
		if ($action_type == 'Cycle' || $action_type == 'CycleRelated') $action_type = vtlib_purify($request['meta_action']['cycle_action']);//crmv@203075

		if (in_array($action_type,array('Create','InsertTableRow'))) {
			$currentModule_bkp = $currentModule;
			if ($action_type == 'InsertTableRow') {
				$currentModule = $request['meta_action']['form']['module'];
				//crmv@182891
				if (substr_count($request['meta_action']['inserttablerow_field'],':') === 1)
					list($metaid, $fieldname) = explode(':', $request['meta_action']['inserttablerow_field']);
				else
					list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $request['meta_action']['inserttablerow_field']);
				//crmv@182891e
				$module_mode = (stripos($fieldname,'ml') !== false);
			} else {
				$currentModule = $request['meta_action']['form_module'];
				$module_mode = true;
			}
			if (!$module_mode) {
				// insert row in a field table of a dynaform
				require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
				$processDynaFormObj = ProcessDynaForm::getInstance();
				$meta = $processDynaFormObj->getMeta($id, false, $metaid);
				$blocks = $processDynaFormObj->getStructure($id, $meta['elementid']);
				if (!empty($blocks)) {
					foreach($blocks as $blockid => $block) {
						if (!empty($block['fields'])) {
							foreach($block['fields'] as $field) {
								if ($field['fieldname'] == $fieldname) {
									$columns = Zend_Json::decode($field['columns']);
									if (!empty($columns)) {
										$tmp = array();
										foreach($columns as $column) {
											$column_fieldname = $column['fieldname'];
											$field = WebserviceField::fromArray($adb,$column);
											//crmv@160843 crmv@166678
											if ($field->getFieldDataType() == 'reference' || $field->getUIType() == 221) { //crmv@174986 add role field
												if (in_array($request['meta_action'][$column_fieldname.'_type'],array('o','Other','A'))) $request['meta_action'][$column_fieldname] = $request['meta_action']['other_'.$column_fieldname];
											} elseif (in_array($field->getFieldDataType(),array('picklist','multipicklist')) && $request['meta_action'][$column_fieldname.'_type'] == 'o') {
												$request['meta_action'][$column_fieldname] = $request['meta_action']['other_'.$column_fieldname];
											} elseif ($field->getFieldDataType() == 'multipicklist') {
												if (is_array($request['meta_action'][$column_fieldname])) $request['meta_action'][$column_fieldname] = implode(' |##| ', $request['meta_action'][$column_fieldname]);
											//crmv@160843e crmv@166678e
											} elseif (in_array($field->getFieldDataType(),array('date','datetime','time')) && $field->getUIType() != '1') {	//crmv@128159 crmv@158782
												//crmv@120769 crmv@131239
												$request['meta_action'][$column_fieldname] = Zend_Json::encode(array(
													'options'=>$request['meta_action'][$column_fieldname.'_options'],
													'custom'=>getValidDBInsertDateValue($request['meta_action'][$column_fieldname]),	//crmv@116011
													'operator'=>$request['meta_action'][$column_fieldname.'_opt_operator'],
													'num'=>$request['meta_action'][$column_fieldname.'_opt_num'],
													'unit'=>$request['meta_action'][$column_fieldname.'_opt_unit'],
												));
												//crmv@120769e crmv@131239e
											}
											//crmv@106856
											if ($request['meta_action'][$column_fieldname] == 'advanced_field_assignment') {
												$request['meta_action']['advanced_field_assignment'][$column_fieldname] = $PMUtils->getAdvancedFieldAssignment($column_fieldname);
											}
											//crmv@106856e
											//crmv@113527
											if (isset($request['meta_action']['sdk_params_'.$column_fieldname])) {
												$request['meta_action']['sdk_params'][$column_fieldname] = $request['meta_action']['sdk_params_'.$column_fieldname];
											}
											//crmv@113527e
										}
									}
									break 2;
								}
							}
						}
					}
				}
			} else {
				if ($currentModule == 'Calendar' && $request['meta_action']['activity_mode'] == 'Events') $currentModule = 'Events';
				$tabid = getTabid($currentModule);
				$focus = CRMEntity::getInstance($currentModule);
				$_REQUEST = $request['meta_action']['form'];
				// set correct owner
				if ($_REQUEST['assigntype'] == 'U') $_REQUEST['assigned_user_id'] = $_REQUEST['assigned_user_id'];
				elseif($_REQUEST['assigntype'] == 'T') $_REQUEST['assigned_user_id'] = $_REQUEST['assigned_group_id'];
				elseif($_REQUEST['assigntype'] == 'O' || $_REQUEST['assigntype'] == 'A') $_REQUEST['assigned_user_id'] = $_REQUEST['other_assigned_user_id']; //crmv@160843
				// end
				// save reference fields
				$i=0;
				$result = $adb->pquery("select * from {$table_prefix}_field where tabid=?",array($tabid));
				while($row=$adb->fetchByASsoc($result,-1,false)) {
					$fieldname = $row['fieldname'];
					$field = WebserviceField::fromQueryResult($adb,$result,$i);
					//crmv@160843 crmv@166678
					if ($field->getFieldDataType() == 'reference' || $field->getUIType() == 221) { //crmv@174986 add role field
						(in_array($currentModule,array('Calendar','Events')) && $fieldname == 'parent_id') ? $fieldname_type = 'parent_type' : $fieldname_type = $fieldname.'_type';
						if (in_array($_REQUEST[$fieldname_type],array('o','Other','A'))) $_REQUEST[$fieldname] = $_REQUEST['other_'.$fieldname];
					} elseif (in_array($field->getFieldDataType(),array('picklist','multipicklist')) && $_REQUEST[$fieldname.'_type'] == 'o') {
						$_REQUEST[$fieldname] = $_REQUEST['other_'.$fieldname];
					} elseif ($field->getFieldDataType() == 'multipicklist') {
						if (is_array($_REQUEST[$fieldname])) $_REQUEST[$fieldname] = implode(' |##| ', $_REQUEST[$fieldname]);
					//crmv@160843e crmv@166678e
					} elseif (in_array($field->getFieldDataType(),array('date','datetime','time')) && $field->getUIType() != '1') {	//crmv@128159 crmv@158782
						//crmv@120769
						$_REQUEST[$fieldname] = Zend_Json::encode(array(
							'options'=>$_REQUEST[$fieldname.'_options'],
							'custom'=>getValidDBInsertDateValue($_REQUEST[$fieldname]),	//crmv@116011
							'operator'=>$_REQUEST[$fieldname.'_opt_operator'],
							'num'=>$_REQUEST[$fieldname.'_opt_num'],
							'unit'=>$_REQUEST[$fieldname.'_opt_unit'],
						));
						//crmv@120769e
					}
					if (in_array($currentModule,array('Calendar','Events')) && in_array($fieldname,array('time_start','time_end'))) {
						if ($fieldname == 'time_start') $custom = $_REQUEST['starthr'].':'.$_REQUEST['startmin'].''.$_REQUEST['startfmt'];
						else $custom = $_REQUEST['endhr'].':'.$_REQUEST['endmin'].''.$_REQUEST['endfmt'];
						$_REQUEST[$fieldname] = Zend_Json::encode(array(
							'options'=>$_REQUEST[$fieldname.'_options'],
							'custom'=>$custom,
							'operator'=>$_REQUEST[$fieldname.'_opt_operator'],
							'num'=>$_REQUEST[$fieldname.'_opt_num'],
							'unit'=>$_REQUEST[$fieldname.'_opt_unit'],
						));
					}
					//crmv@106856
					if ($_REQUEST[$fieldname] == 'advanced_field_assignment') {
						$request['meta_action']['advanced_field_assignment'][$fieldname] = $PMUtils->getAdvancedFieldAssignment($fieldname);
					}
					//crmv@106856e
					//crmv@113527
					if (isset($_REQUEST['sdk_params_'.$fieldname])) {
						$request['meta_action']['sdk_params'][$fieldname] = $request['meta_action']['form']['sdk_params_'.$fieldname];
					}
					//crmv@113527e
					$i++;
				}
				// end
				setObjectValuesFromRequest($focus);
				$request['meta_action']['form'] = $focus->column_fields;
			}
			$currentModule = $currentModule_bkp;
		} elseif ($action_type == 'Update') {
			$currentModule_bkp = $currentModule;
			//crmv@124836
			$isInventory = isInventoryModule($request['meta_action']['form']['return_module']);
			if ($isInventory) {
				$currentModule = $request['meta_action']['form']['return_module'];
			} else {
				$currentModule = $request['meta_action']['form']['module'];
			}
			//crmv@124836e
			if ($currentModule == 'Calendar' && $request['meta_action']['form']['activity_mode'] == 'Events') $currentModule = 'Events';
			$tabid = getTabid($currentModule);
			require_once('include/utils/MassEditUtils.php');
			$massEditUtils = MassEditUtils::getInstance();
			// set correct owner
			if ($request['meta_action']['form']['assigntype'] == 'U') $request['meta_action']['form']['assigned_user_id'] = $request['meta_action']['form']['assigned_user_id'];
			elseif($request['meta_action']['form']['assigntype'] == 'T') $request['meta_action']['form']['assigned_user_id'] = $request['meta_action']['form']['assigned_group_id'];
			elseif($request['meta_action']['form']['assigntype'] == 'O' || $request['meta_action']['form']['assigntype'] == 'A') $request['meta_action']['form']['assigned_user_id'] = $request['meta_action']['form']['other_assigned_user_id']; //crmv@160843
			$request['meta_action']['form']['assigntype'] = 'U';
			// end
			// save reference fields
			$i=0;
			$result = $adb->pquery("select * from {$table_prefix}_field where tabid=?",array($tabid));
			while($row=$adb->fetchByASsoc($result,-1,false)) {
				$fieldname = $row['fieldname'];
				$field = WebserviceField::fromQueryResult($adb,$result,$i);
				//crmv@160843 crmv@166678
				if ($field->getFieldDataType() == 'reference' || $field->getUIType() == 221) { //crmv@174986 add role field
					(in_array($currentModule,array('Calendar','Events')) && $fieldname == 'parent_id') ? $fieldname_type = 'parent_type' : $fieldname_type = $fieldname.'_type';
					if (in_array($request['meta_action']['form'][$fieldname_type],array('o','Other','A'))) $request['meta_action']['form'][$fieldname] = $request['meta_action']['form']['other_'.$fieldname];
				} elseif (in_array($field->getFieldDataType(),array('picklist','multipicklist')) && $request['meta_action']['form'][$fieldname.'_type'] == 'o') {
					$request['meta_action']['form'][$fieldname] = $request['meta_action']['form']['other_'.$fieldname];
				} elseif ($field->getFieldDataType() == 'multipicklist') {
					if (is_array($request['meta_action']['form'][$fieldname])) $request['meta_action']['form'][$fieldname] = implode(' |##| ', $request['meta_action']['form'][$fieldname]);
				//crmv@160843e crmv@166678e
				} elseif (in_array($field->getFieldDataType(),array('date','datetime','time')) && $field->getUIType() != '1') {	//crmv@128159 crmv@158782
					//crmv@120769
					$request['meta_action']['form'][$fieldname] = Zend_Json::encode(array(
						'options'=>$request['meta_action']['form'][$fieldname.'_options'],
						'custom'=>getValidDBInsertDateValue($request['meta_action']['form'][$fieldname]),
						'operator'=>$request['meta_action']['form'][$fieldname.'_opt_operator'],
						'num'=>$request['meta_action']['form'][$fieldname.'_opt_num'],
						'unit'=>$request['meta_action']['form'][$fieldname.'_opt_unit'],
					));
					//crmv@120769e
				}
				//crmv@106856
				if ($request['meta_action']['form'][$fieldname] == 'advanced_field_assignment') {
					$request['meta_action']['advanced_field_assignment'][$fieldname] = $PMUtils->getAdvancedFieldAssignment($fieldname);
				}
				//crmv@106856e
				//crmv@113527
				if (isset($request['meta_action']['form']['sdk_params_'.$fieldname])) {
					$request['meta_action']['sdk_params'][$fieldname] = $request['meta_action']['form']['sdk_params_'.$fieldname];
				}
				//crmv@113527e
				$i++;
			}
			// end
			$form = $massEditUtils->extractValuesFromRequest($currentModule, $request['meta_action']['form']);
			if (in_array($currentModule,array('Calendar','Events'))) {
				$time_fields = array('time_start','time_end');
				foreach($time_fields as $fieldname) {
					if (array_key_exists($fieldname,$form)) {
						if ($fieldname == 'time_start') $custom = $request['meta_action']['form']['starthr'].':'.$request['meta_action']['form']['startmin'].''.$request['meta_action']['form']['startfmt'];
						else $custom = $request['meta_action']['form']['endhr'].':'.$request['meta_action']['form']['endmin'].''.$request['meta_action']['form']['endfmt'];
						$form[$fieldname] = Zend_Json::encode(array(
							'options'=>$request['meta_action']['form'][$fieldname.'_options'],
							'custom'=>$custom,
							'operator'=>$request['meta_action']['form'][$fieldname.'_opt_operator'],
							'num'=>$request['meta_action']['form'][$fieldname.'_opt_num'],
							'unit'=>$request['meta_action']['form'][$fieldname.'_opt_unit'],
						));
					}
				}
			}
			$request['meta_action']['form'] = $form;
			$currentModule = $currentModule_bkp;
		} elseif ($action_type == 'SDK') {
			$sdkActions = SDK::getProcessMakerActions();
			$request['meta_action']['action_title'] = $sdkActions[$request['meta_action']['function']]['label'];
		//crmv@183346
		} elseif ($action_type == 'ModNotification') {
			$focus = CRMEntity::getInstance('ModNotifications');
			$_REQUEST = $request['meta_action'];
			// set correct owner
			if ($_REQUEST['assigntype'] == 'U') $_REQUEST['assigned_user_id'] = $_REQUEST['assigned_user_id'];
			elseif($_REQUEST['assigntype'] == 'T') $_REQUEST['assigned_user_id'] = $_REQUEST['assigned_group_id'];
			elseif($_REQUEST['assigntype'] == 'O' || $_REQUEST['assigntype'] == 'A') $_REQUEST['assigned_user_id'] = $_REQUEST['other_assigned_user_id']; //crmv@160843
			// end
			//crmv@182891
			$actionType = $PMUtils->getActionTypes('ModNotification');
			require_once($actionType['php_file']);
			$pMActionModNotification = new $actionType['class']($this->options);
			foreach($pMActionModNotification->fields as $fieldname => $field) {
			//crmv@182891e
				//crmv@160843 crmv@166678
				if ($field['type'] == 'reference' || $field['uitype'] == 221) { //crmv@174986 add role field
					(in_array($currentModule,array('Calendar','Events')) && $fieldname == 'parent_id') ? $fieldname_type = 'parent_type' : $fieldname_type = $fieldname.'_type';
					if (in_array($_REQUEST[$fieldname_type],array('o','Other','A'))) $_REQUEST[$fieldname] = $_REQUEST['other_'.$fieldname];
				} elseif (in_array($field['type'],array('picklist','multipicklist')) && $_REQUEST[$fieldname.'_type'] == 'o') {
					$_REQUEST[$fieldname] = $_REQUEST['other_'.$fieldname];
				} elseif ($field['type'] == 'multipicklist') {
					if (is_array($_REQUEST[$fieldname])) $_REQUEST[$fieldname] = implode(' |##| ', $_REQUEST[$fieldname]);
					//crmv@160843e crmv@166678e
				} elseif (in_array($field['type'],array('date','datetime','time')) && $field['uitype'] != '1') {	//crmv@128159 crmv@158782
					//crmv@120769
					$_REQUEST[$fieldname] = Zend_Json::encode(array(
						'options'=>$_REQUEST[$fieldname.'_options'],
						'custom'=>getValidDBInsertDateValue($_REQUEST[$fieldname]),	//crmv@116011
						'operator'=>$_REQUEST[$fieldname.'_opt_operator'],
						'num'=>$_REQUEST[$fieldname.'_opt_num'],
						'unit'=>$_REQUEST[$fieldname.'_opt_unit'],
					));
					//crmv@120769e
				}
				if (in_array($currentModule,array('Calendar','Events')) && in_array($fieldname,array('time_start','time_end'))) {
					if ($fieldname == 'time_start') $custom = $_REQUEST['starthr'].':'.$_REQUEST['startmin'].''.$_REQUEST['startfmt'];
					else $custom = $_REQUEST['endhr'].':'.$_REQUEST['endmin'].''.$_REQUEST['endfmt'];
					$_REQUEST[$fieldname] = Zend_Json::encode(array(
						'options'=>$_REQUEST[$fieldname.'_options'],
						'custom'=>$custom,
						'operator'=>$_REQUEST[$fieldname.'_opt_operator'],
						'num'=>$_REQUEST[$fieldname.'_opt_num'],
						'unit'=>$_REQUEST[$fieldname.'_opt_unit'],
					));
				}
				//crmv@106856
				if ($_REQUEST[$fieldname] == 'advanced_field_assignment') {
					$request['meta_action']['advanced_field_assignment'][$fieldname] = $PMUtils->getAdvancedFieldAssignment($fieldname);
				}
				//crmv@106856e
				//crmv@113527
				if (isset($_REQUEST['sdk_params_'.$fieldname])) {
					$request['meta_action']['sdk_params'][$fieldname] = $request['meta_action']['form']['sdk_params_'.$fieldname];
				}
				//crmv@113527e
				$i++;
			}
			// end
			setObjectValuesFromRequest($focus);
			$request['meta_action'] = array(
				'id' => $request['meta_action']['id'],
				'elementid' => $request['meta_action']['elementid'],
				'metaid' => $request['meta_action']['metaid'],
				'action_type' => $request['meta_action']['action_type'],
				'cycle_action' => $request['meta_action']['cycle_action'],
				'cycle_field' => $request['meta_action']['cycle_field'],
				'inserttablerow_field' => $request['meta_action']['inserttablerow_field'],
				'insertproductrow_inventory_fields' => $request['meta_action']['insertproductrow_inventory_fields'], // crmv@195745
				'action_title' => $request['meta_action']['action_title'],
				'form' => $focus->column_fields,
				'advanced_field_assignment' => $request['meta_action']['advanced_field_assignment'],
				'sdk_params' => $request['meta_action']['sdk_params'],
				'conditions' => $request['meta_action']['conditions'], // crmv@195745
			);
			$request['meta_action']['form'] = $focus->column_fields;
		//crmv@183346e
		// crmv@187729
		} elseif($action_type == 'CreatePDF') {
			$pdf_entity = $request['meta_action']['pdf_entity'];
			list($metaid,$module,$reference) = explode(':',$pdf_entity);
			if(isset($reference) && !empty($reference)){
				list($reference_values,$module) = explode('::',$pdf_entity);
				if(empty($module)){
					$module = getSingleFieldValue($table_prefix."_fieldmodulerel", "relmodule", "fieldid", $reference);
				}
			}
			
			//set correct related_to_entity
			if ($request['meta_action']['related_to_entity_type'] != 'Other') $related_to_entity = $request['meta_action']['related_to_entity'];
			else $related_to_entity = $request['meta_action']['other_related_to_entity'];
			// set correct owner
			if ($request['meta_action']['assigntype'] == 'U') $request['meta_action']['assigned_user_id'] = $request['meta_action']['assigned_user_id'];
			elseif($request['meta_action']['assigntype'] == 'T') $request['meta_action']['assigned_user_id'] = $request['meta_action']['assigned_group_id'];
			elseif($request['meta_action']['assigntype'] == 'O' || $request['meta_action']['assigntype'] == 'A') $request['meta_action']['assigned_user_id'] = $request['meta_action']['other_assigned_user_id']; //crmv@160843
			
			//set correct template
			if ($request['meta_action']['templatename_type'] == 'v') $request['meta_action']['templatename'] = $request['meta_action']['templatename'];
			elseif($request['meta_action']['templatename_type'] == 'o') $request['meta_action']['templatename'] = $request['meta_action']['other_templatename']; //crmv@160843
			
			//set correct folder
			if ($request['meta_action']['foldername_type'] == 'v') $request['meta_action']['foldername'] = $request['meta_action']['foldername'];
			elseif($request['meta_action']['foldername_type'] == 'o') $request['meta_action']['foldername'] = $request['meta_action']['other_foldername']; //crmv@160843
			
			
			if(is_numeric($request['meta_action']['templatename'])){
				$templateid = $request['meta_action']['templatename'];
				$templateid_query = "SELECT filename FROM {$table_prefix}_pdfmaker WHERE templateid = ? AND module = ?";
				$res_templateid = $adb->pquery($templateid_query, array($templateid, $module));
				if($res_templateid && $adb->num_rows($res_templateid) > 0) $filename = $adb->query_result($res_templateid, 0, "filename");
				$request['meta_action']['templatename'] = $filename;
			} else{
				$templateid_query = "SELECT templateid FROM {$table_prefix}_pdfmaker WHERE filename = ? AND module = ?";
				$res_templateid = $adb->pquery($templateid_query, array($request['meta_action']['templatename'], $module));
				if($res_templateid && $adb->num_rows($res_templateid) > 0) $templateid = $adb->query_result($res_templateid, 0, "templateid");
			}
			
			if(is_numeric($request['meta_action']['foldername'])){
				$folderid = $request['meta_action']['foldername'];
				$folderid_query = "SELECT foldername FROM {$table_prefix}_crmentityfolder WHERE folderid = ? AND tabid = ?";
				$res_folderid = $adb->pquery($folderid_query, array($folderid, 8));
				if($res_folderid && $adb->num_rows($res_folderid) > 0) $foldername = $adb->query_result($res_folderid, 0, "foldername");
				$request['meta_action']['foldername'] = $foldername;
			} else{
				$folderid_query = "SELECT folderid FROM {$table_prefix}_crmentityfolder WHERE foldername = ? AND tabid = ?";
				$res_folderid = $adb->pquery($folderid_query, array($request['meta_action']['foldername'], 8));
				if($res_folderid && $adb->num_rows($res_folderid) > 0) $folderid = $adb->query_result($res_folderid, 0, "folderid");
			}
			
			$request['meta_action']['templateid'] = $templateid;
			$request['meta_action']['folderid'] = $folderid;
			
			$column_fields = array(
				'subject' => $request['meta_action']['subject'],
				'language' => $request['meta_action']['language'],
				'pdf_entity' => $request['meta_action']['pdf_entity'],
				'related_to_entity' => $related_to_entity,
				'assigned_user_id' => $request['meta_action']['assigned_user_id'],
				'templateid' => $request['meta_action']['templateid'],
				'templatename' => $request['meta_action']['templatename'],
				'folderid' => $request['meta_action']['folderid'],
				'foldername' => $request['meta_action']['foldername']
			);
			
			$request['meta_action'] = array(
				'id' => $request['meta_action']['id'],
				'elementid' => $request['meta_action']['elementid'],
				'metaid' => $request['meta_action']['metaid'],
				'action_type' => $request['meta_action']['action_type'],
				'cycle_action' => $request['meta_action']['cycle_action'],
				'cycle_field' => $request['meta_action']['cycle_field'],
				'inserttablerow_field' => $request['meta_action']['inserttablerow_field'],
				'insertproductrow_inventory_fields' => $request['meta_action']['insertproductrow_inventory_fields'], // crmv@195745
				'action_title' => $request['meta_action']['action_title'],
				'form' => $column_fields,
				'advanced_field_assignment' => $request['meta_action']['advanced_field_assignment'],
				'sdk_params' => $request['meta_action']['sdk_params'],
			);
			$request['meta_action']['form'] = $column_fields;
		// crmv@187729e
		// crmv@192951
		} elseif($action_type == 'ResetDynaform') {
			if (isset($request['meta_action']['empty_fields']) && $request['meta_action']['empty_fields'] == 'on') $request['meta_action']['empty_fields'] = 1;
		}
		// crmv@192951e

		$retrieve = $PMUtils->retrieve($id);
		$element_metadata = Zend_Json::decode($retrieve['vte_metadata']);
		if ($action_id != '')
			$element_metadata[$elementid]['actions'][$action_id] = $request['meta_action'];
		else
			$element_metadata[$elementid]['actions'][] = $request['meta_action'];

		$retrieve['vte_metadata'] = Zend_Json::encode($element_metadata);
		$retrieve['pending_changes'] = 1; //crmv@163905		
		$PMUtils->edit($id,$retrieve);
	}
	//crmv@108227e crmv@146671e

}