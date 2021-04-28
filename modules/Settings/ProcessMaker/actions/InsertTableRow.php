<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@115268 */

require_once(dirname(__FILE__).'/Base.php');

class PMActionInsertTableRow extends PMActionBase {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		global $adb, $table_prefix;
		$PMUtils = ProcessMakerUtils::getInstance();
		
		$_REQUEST['enable_editoptions'] = 'yes';	//crmv@131239

		require_once(dirname(__FILE__).'/Create.php');
		$action = new PMActionCreate();
		$action->edit($smarty,$id,$elementid,$retrieve,$action_type,$action_id);
		
		//crmv@182891
		if (substr_count($_REQUEST['inserttablerow_field'],':') === 1)
			list($metaid, $fieldname) = explode(':', $_REQUEST['inserttablerow_field']);
		else
			list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $_REQUEST['inserttablerow_field']);
		//crmv@182891e
		if (stripos($fieldname,'ml') !== false) {
			//ModLight
			$smarty->assign('TABLETYPE', 'ModLight');
			
			$modulelightname = 'ModLight'.str_replace('ml','',$fieldname);
			$smarty->assign('MODULELIGHT', $modulelightname);
			$smarty->assign('INSERT_TABLEROW_FIELD', $_REQUEST['inserttablerow_field']);
			
			$result = $adb->pquery("select * from {$table_prefix}_processmaker_metarec where id = ? and processid = ?", array($metaid,$id));
			if ($result && $adb->num_rows($result) > 0) {
				$row = $adb->fetchByAssoc($result);
				//crmv@182891
				if (!empty($fieldid)) {
					$fieldlabel = '';
					$moduleInstance = Vtecrm_Module::getInstance($row['module']);
					$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldid = ?", array($moduleInstance->id,$fieldid));
					$fieldInstance = WebserviceField::fromQueryResult($adb,$result,0);
					if (!empty($fieldInstance)) {
						if (empty($relatedmodule)) $relatedmodule = $fieldInstance->getReferenceList();
						if (is_array($relatedmodule)) $relatedmodule = $relatedmodule[0];
						$fieldlabel = getTranslatedString($fieldInstance->getFieldLabelKey(),$row['module']).' ('.getTranslatedString($relatedmodule,$relatedmodule).') : ';
					}
					$relModuleInstance = Vtecrm_Module::getInstance($relatedmodule);
					$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($relModuleInstance->id,$fieldname));
					if ($result && $adb->num_rows($result) > 0) {
						$fieldlabel .= $adb->query_result($result,0,'fieldlabel');
					}
					$smarty->assign('INSERT_TABLEROW_LABEL', getTranslatedString('LBL_INSERT_ON_TABLE_FIELD','Settings').' '.$fieldlabel.' '.getTranslatedString('LBL_LIST_OF').' '.$PMUtils->getRecordsInvolvedLabel($id,$metaid,$row));
				} else {
					$moduleInstance = Vtecrm_Module::getInstance($row['module']);
					$result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$fieldname));
					if ($result && $adb->num_rows($result) > 0) {
						$fieldlabel = $adb->query_result($result,0,'fieldlabel');
						$smarty->assign('INSERT_TABLEROW_LABEL', getTranslatedString('LBL_INSERT_ON_TABLE_FIELD','Settings').' '.$fieldlabel.' '.getTranslatedString('LBL_LIST_OF').' '.$PMUtils->getRecordsInvolvedLabel($id,$metaid,$row));	//crmv@115268
					}
				}
				//crmv@182891e
			}
		} else {
			//Dynaform
			$smarty->assign('TABLETYPE', 'Dynaform');
			$smarty->assign('INSERT_TABLEROW_FIELD', $_REQUEST['inserttablerow_field']);
			
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$meta = $processDynaFormObj->getMeta($id, false, $metaid);
			$blocks = $processDynaFormObj->getStructure($id, $meta['elementid']);
			$PMUtils = ProcessMakerUtils::getInstance();
			
			$editoptionsfieldnames = array();
			$picklist_values = array();
			$reference_values = array();
			$reference_users_values = array();	// ex. uitypes 52
			$boolean_values = array();
			$date_values = array();
			$metadata = $PMUtils->getMetadata($id,$elementid);
			$metadata_form = $metadata['actions'][$action_id];
			unset($metadata_form['conditions']);

			$editview_arr = array();
			$blockdata = array();
			$aBlockStatus = $blockVisibility = array();
			if (!empty($blocks)) {
				foreach($blocks as $blockid => $block) {
					if (!empty($block['fields'])) {
						foreach($block['fields'] as $field) {
							if ($field['fieldname'] == $fieldname) {
								$smarty->assign('INSERT_TABLEROW_LABEL', getTranslatedString('LBL_INSERT_ON_TABLE_FIELD','Settings').' '.$field['label'].' '.getTranslatedString('LBL_LIST_OF').' '.$processDynaFormObj->getLabel($id,$metaid));	//crmv@115268
								$columns = Zend_Json::decode($field['columns']);
								if (!empty($columns)) {
									$blockid = 1;
									$aBlockStatus['LBL_INFORMATION'] = 1;
									$blockVisibility[$blockid] = 1;
									$blockdata[$blockid] = array(
										'label' => getTranslatedString('LBL_INFORMATION'),
										'panelid' => 1,
									);
									foreach($columns as $column) {
										$uitype = $column['uitype'];
										//crmv@103373 crmv@106857
										$readonly = 1;
										// TODO creare un metodo o degli array per gestire questi casi
										if (in_array($uitype,array(69,220))) $readonly = 100;	// hide table fields
										if ($uitype == 300) $uitype = 15;	//crmv@111091
										if (in_array($uitype,array(7,9,71,72))) $uitype = 1;	//crmv@96450
										//crmv@103373e crmv@106857e
										$mandatory = $column['mandatory'];
										$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
										$typeofdata = $ModuleMakerGenerator->getTODForField($column);
										if ($mandatory) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
										$fld = getOutputHtml($uitype, $column['fieldname'], $column['label'], $column['length'], $metadata_form, 1, 'Processes', '', $readonly, $typeofdata, $column);
										$fld[] = $column['fieldname'];
										if (in_array($uitype,array(50,51,52))) unset($fld[3][1]['type_options'][2]); //crmv@160843
										$editview_arr[$blockid][] = $fld;
										
										if (!in_array($uitype,$PMUtils->editoptions_uitypes_not_supported)) $editoptionsfieldnames[] = $column['fieldname'];
										
										$field = WebserviceField::fromArray($adb,$column);
										if ($field->getFieldDataType() == 'picklist') {
											$picklist_values[$column['fieldname']] = $metadata_form[$column['fieldname']];
										} elseif ($field->getFieldDataType() == 'reference' && in_array('Users',$field->getReferenceList())) {
											$reference_users_values[$column['fieldname']] = $metadata_form[$column['fieldname']];
										} elseif ($field->getFieldDataType() == 'reference') {
											$reference_values[$column['fieldname']] = $metadata_form[$column['fieldname']];
										} elseif ($field->getFieldDataType() == 'boolean') {
											$boolean_values[$column['fieldname']] = $metadata_form[$column['fieldname']];
										} elseif (in_array($field->getFieldDataType(),array('date','datetime','time'))) {	//crmv@128159
											$date_values[$column['fieldname']] = $metadata_form[$column['fieldname']];
										}
									}
								}
							}
						}
					}
				}
			}
			if (!empty($editview_arr)) {
				foreach($editview_arr as $headerid=>$editview_value)
				{
					$editview_data = Array();
					for ($i=0,$j=0;$i<count($editview_value);$j++)
					{
						$key1=$editview_value[$i];
						if(is_array($editview_value[$i+1]) && ($key1[0][0]!=19 && $key1[0][0]!=20))
						{
							$key2=$editview_value[$i+1];
						}
						else
						{
							$key2 =array();
						}
						if($key1[0][0]!=19 && $key1[0][0]!=20){
							$editview_data[$j]=array(0 => $key1,1 => $key2);
							$i+=2;
						}
						else{
							$editview_data[$j]=array(0 => $key1);
							$i++;
						}
					}
					$editview_arr[$headerid] = $editview_data;
				}
				$returndata = array();
				foreach($blockdata as $blockid=>$blockinfo) {
					$label = $blockinfo['label'];
					if ($label != '') {
						$curBlock = $label;
					}
					$blocklabel = getTranslatedString($curBlock,$module);
					$key = $blocklabel;
					if(is_array($editview_arr[$blockid])) {
						if (!is_array($returndata[$key])) {
							$returndata[$key] = array(
								'blockid' => $blockid,
								'panelid' => $blockinfo['panelid'],
								'label' => $blocklabel,
								'fields' => array()
							);
						}
						$returndata[$key]['fields'] = array_merge((array)$returndata[$key]['fields'], (array)$editview_arr[$blockid]);
					}
				}
			}
			$_REQUEST['enable_editoptions'] = 'yes';
			$_REQUEST['editoptionsfieldnames'] = implode('|',$editoptionsfieldnames);
			$smarty->assign('SKIP_EDITFORM', '1');
			$smarty->assign('HIDE_BUTTON_LIST', '1');
			$smarty->assign('PANELID', 1);
			$smarty->assign('BLOCKS', $returndata);
			$smarty->assign('BLOCKVISIBILITY', $blockVisibility);
			
			$dynaform_options = array();
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$dynaform_options = $processDynaFormObj->getFieldsOptions($id,true);
			$PMUtils->getAllTableFieldsOptions($id, $dynaform_options);
			$PMUtils->getAllPBlockFieldsOptions($id, $dynaform_options); // crmv@195745
			
			$smarty->assign('EDITOPTIONSPARAMS', addslashes(Zend_Json::encode(array(	//crmv@135190
				'processid'=>$id, //crmv@158858
				'involved_records'=>$PMUtils->getRecordsInvolved($id,true),
				'form_data'=>$metadata_form,
				'picklist_values'=>$picklist_values,
				'reference_values'=>$reference_values,
				'reference_users_values'=>$reference_users_values,
				'boolean_values'=>$boolean_values,
				'date_values'=>$date_values,
				'dynaform_options'=>$dynaform_options,
				'elements_actors'=>$PMUtils->getElementsActors($id)
				// TODO extws_options
			))));
		}
	}
	
	function execute($engine,$actionid) {
		global $adb, $table_prefix;
		$action = $engine->vte_metadata['actions'][$actionid];
		
		(!empty($this->cycleRow['id'])) ? $cycleIndex = $this->cycleRow['id'] : $cycleIndex = $this->cycleIndex;

		//crmv@182891
		if (substr_count($action['inserttablerow_field'],':') === 1)
			list($metaid, $fieldname) = explode(':', $action['inserttablerow_field']);
		else
			list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $action['inserttablerow_field']);
		//crmv@182891e
		if (stripos($fieldname,'ml') !== false) {
			// ModLight
			$module = 'ModLight'.str_replace('ml','',$fieldname);
			$parent_id = $engine->getCrmid($metaid, $engine->running_process, $fieldid); //crmv@182891
			$assigned_user_id = getSingleFieldValue($table_prefix.'_crmentity', 'smownerid', 'crmid', $parent_id);
			$seq = 0;
			$result = $adb->limitpQuery("SELECT seq FROM {$table_prefix}_".strtolower($module)."
				INNER JOIN {$table_prefix}_crmentity ON ".strtolower($module)."id = crmid
				WHERE deleted = 0 AND parent_id = ?
				ORDER BY seq DESC", 0, 1, array($parent_id));
			if ($result && $adb->num_rows($result) > 0) {
				$seq = $adb->query_result($result,0,'seq')+1; // crmv@201421
			}
			$engine->vte_metadata['actions'][$actionid]['form']['parent_id'] = $parent_id;
			$engine->vte_metadata['actions'][$actionid]['form']['seq'] = $seq;
			$engine->vte_metadata['actions'][$actionid]['form']['assigned_user_id'] = $assigned_user_id;
			$engine->vte_metadata['actions'][$actionid]['form_module'] = $module;
			
			require_once(dirname(__FILE__).'/Create.php');
			$action = new PMActionCreate();
			$action->cycleIndex = $this->cycleIndex;
			$action->cycleRow = $this->cycleRow;
			$action->execute($engine,$actionid);
		} else {
			// Dynaform
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$values = $processDynaFormObj->getValues($engine->running_process, $metaid);
			$meta = $processDynaFormObj->getMeta($engine->processid, false, $metaid, $engine->running_process); //crmv@150751
			$blocks = $processDynaFormObj->getStructure($engine->processid, $meta['elementid'], false, $engine->running_process); //crmv@150751
			require_once('modules/Touch/TouchUtils.php');
			$touchUtils = TouchUtils::getInstance();
			if (!empty($blocks)) {
				foreach($blocks as $blockid => $block) {
					if (!empty($block['fields'])) {
						foreach($block['fields'] as $field) {
							if ($field['fieldname'] == $fieldname) {
								$columns = Zend_Json::decode($field['columns']);
								if (!empty($columns)) {
									$tmp = array();
									$date_fields = array();
									$hourFields = array();
									foreach($columns as $column) {
										$referenceFields = array();
										$ownerFields = array();
										if (in_array($column['uitype'],array(10))) $referenceFields[] = $column['fieldname'];
										if (in_array($column['uitype'],array(52,51,50))) $ownerFields[] = $column['fieldname'];
										
										$fieldWsType = $touchUtils->getFieldTypebyUIType($column['uitype']);
										if ($fieldWsType == 'date') $date_fields[] = $column['fieldname']; // EntityMeta:getDataFields
										if ($column['uitype'] == '73') $hourFields[] = $column['fieldname'];// EntityMeta:getHourFields
										
										$tmp[$column['fieldname']] = $action[$column['fieldname']];
										$tmp[$column['fieldname']] = $engine->replaceTags($column['fieldname'],$action[$column['fieldname']],$referenceFields,$ownerFields,$actionid,$cycleIndex);
									}
									// manage date and hour fields
									if (!empty($date_fields)) {
										foreach($date_fields as $date_field) {
											$date_arr = Zend_Json::decode($tmp[$date_field]);
											if ($date_arr['options'] == 'custom') {
												$date = $date_arr['custom'];
											} else {
												if ($date_arr['options'] == 'now') {
													$date = date('Y-m-d');
												} else {
													$date = $date_arr['options'];
												}
												//crmv@122245
												if (!empty($date_arr['num'])) {
													$date_arr['num'] = $engine->replaceTags($date_field,$date_arr['num'],array(),array(),$actionid,$cycleIndex);
												}
												//crmv@122245e
												if (!empty($date_arr['num'])) {
													$advanced = (($date_arr['operator']=='add')?'+':'-').' '.$date_arr['num'].' '.$date_arr['unit'];
												}
											}
											$date = date('Y-m-d',strtotime("$date $advanced"));
											$tmp[$date_field] = $date;
										}
									}
									if (!empty($hourFields)) {
										// TODO
									}
									if (empty($values[$fieldname])) $values[$fieldname] = array(); // crmv@205931
									$values[$fieldname][] = $tmp;
								}
								break 2;
							}
						}
					}
				}
			}
			if (!empty($values)) {
				$adb->pquery("update {$table_prefix}_process_dynaform set form = ? where running_process = ? and metaid = ?", array(Zend_Json::encode($values),$engine->running_process,$metaid));
			}
		}
	}
}