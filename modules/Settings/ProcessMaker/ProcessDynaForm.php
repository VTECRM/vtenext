<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96450 crmv@105685 crmv@105937 crmv@106857 crmv@112297 crmv@115268 crmv@134058 crmv@150751 */

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTSimpleTemplate.inc');//crmv@207901
require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

class ProcessDynaForm extends SDKExtendableClass {
	
	function getStructure($processmaker, $elementid=false, $metaid=false, $running_process='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		(!empty($running_process)) ? $xml_version = $PMUtils->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
		$data = $PMUtils->retrieve($processmaker,$xml_version);
		$helper = Zend_Json::decode($data['helper']);
		if (!$elementid) {
			$meta = $this->getMeta($processmaker, $elementid, $metaid, $running_process);
			$elementid = $meta['elementid'];
		}
		if (isset($helper[$elementid]['dynaform'])) {
			return $helper[$elementid]['dynaform']['mmaker_blocks'];
		}
		return false;
	}
	
	function getCurrentDynaForm($focus, &$elementid='') {
		global $adb, $table_prefix;
		static $current_dynaform = array();
		$blocks = array();
		$elementid = '';
		// crmv@195977
		$processesid = $focus->id;
		if (empty($processesid) && is_array($focus) && !empty($focus['record_id']) && $focus['record_module'] == 'Processes') $processesid = $focus['record_id'];
		$result = $adb->pquery("select current_dynaform from {$table_prefix}_process_gateway_conn where processesid = ?", array($processesid));
		// crmv@195977e
		if ($result && $adb->num_rows($result) > 0) {
			$elementid = $adb->query_result($result,0,'current_dynaform');
		}
		if (empty($elementid)) {
			$result = $adb->pquery("select current_dynaform from {$table_prefix}_running_processes where id = ?", array($focus->column_fields['running_process']));
			if ($result && $adb->num_rows($result) > 0) {
				$elementid = $adb->query_result($result,0,'current_dynaform');
			}
		}
		// crmv@203119_2
		if (!empty($elementid)) {
			if (!isset($current_dynaform[$focus->column_fields['processmaker']][$elementid])) {
				$current_dynaform[$focus->column_fields['processmaker']][$elementid] = $this->getStructure($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
			}
			$blocks = $current_dynaform[$focus->column_fields['processmaker']][$elementid];
		}
		// crmv@203119_2e
		//crmv@160837 TODO add displaytype
		if (!empty($blocks)) {
			foreach($blocks as &$block) {
				if (!empty($block['fields'])) {
					foreach($block['fields'] as &$field) {
						if (!isset($field['displaytype'])) {
							$field['displaytype'] = 1;
							if ($field['uitype'] == 70) $field['displaytype'] = 2;
						}
					}
				}
			}
		}
		//crmv@160837e
		return $blocks;
	}
	
	function formatField(&$field) {
		$fieldType = $this->getFieldTypeFromUIType($field['uitype']);
		if ($fieldType === null) {
			$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
			$typeofdata = $ModuleMakerGenerator->getTODForField($field);
			$typeofdata = explode('~',$typeofdata);
			$fieldType = $this->getFieldTypeFromTypeOfData($typeofdata[0]);
		}
		$field['type'] = $fieldType;
	}
	function getFields($focus, &$elementid='', $add_table_fields=false) {
		$fields = array();
		$blocks = $this->getCurrentDynaForm($focus, $elementid);
		if (!empty($blocks)) {
			foreach($blocks as $block) {
				if (!empty($block['fields'])) {
					foreach($block['fields'] as $field) {
						$this->formatField($field);
						
						if ($add_table_fields && $field['type'] == 'table') {
							if (!empty($field['columns'])) {
								$columns = Zend_Json::decode($field['columns']);
								$tmp_columns = array();
								foreach($columns as $column) {
									$this->formatField($column);
									$tmp_columns[$column['fieldname']] = $column;
								}
								$field['columns'] = $tmp_columns;
							}	
						}
						
						$fields[$field['fieldname']] = $field;
					}
				}
			}
		}
		return $fields;
	}
	
	function getFieldVisibilityPermission($focus, $fieldname) {
		$blocks = $this->getCurrentDynaForm($focus);
		if (!empty($blocks)) {
			foreach($blocks as $block) {
				foreach($block['fields'] as $field) {
					if ($field['fieldname'] == $fieldname) return true;
				}
			}
		}
		return false;
	}
	
	function getMeta($processmaker, $elementid=false, $metaid=false, $running_process='') {
		global $adb, $table_prefix;
		static $metaCache = array();
		if (!isset($metaCache[$processmaker])) {
			$PMUtils = ProcessMakerUtils::getInstance();
			(!empty($running_process)) ? $xml_version = $PMUtils->getSystemVersion4RunningProcess($running_process,array('processmaker','xml_version')) : $xml_version = '';
			if (!empty($xml_version)) {
				$query = "SELECT * FROM {$table_prefix}_process_dynaform_meta_vh WHERE versionid = ? and processid = ?";
				$params = array($xml_version, $processmaker);
			} else {
				$query = "SELECT * FROM {$table_prefix}_process_dynaform_meta WHERE processid = ?";
				$params = array($processmaker);
			}
			$result = $adb->pquery($query, $params);
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$metaCache[$processmaker]['elements'][$row['elementid']] = $row;
					$metaCache[$processmaker]['meta'][$row['id']] = $row;
				}
			} else {
				return false;
			}
		}
		if (isset($metaCache[$processmaker])) {
			if (!$elementid) {
				return $metaCache[$processmaker]['meta'][$metaid];
			} else {
				return $metaCache[$processmaker]['elements'][$elementid];
			}
		}
		return false;
	}
	
	function getProcessesId($running_process, $metaid) {
		global $adb, $table_prefix;
		static $processesIds = array();
		if (empty($processesIds[$running_process][$metaid])) {
			$result = $adb->pquery("SELECT p.processesid FROM {$table_prefix}_processes p
			INNER JOIN {$table_prefix}_process_dynaform d ON p.running_process = d.running_process
			WHERE p.deleted = ? and p.running_process = ? AND d.metaid = ?", array(0, $running_process, $metaid)); // crmv@185647 crmv@192951
			if ($result && $adb->num_rows($result) > 0) {
				$processesIds[$running_process][$metaid] = $adb->query_result($result,0,'processesid');
			} else {
				$processesIds[$running_process][$metaid] = false;
			}
		}
		return $processesIds[$running_process][$metaid];
	}
	/*
	public static $resetCache = array();
	public static function setResetCache($running_process, $metaid) {
		self::$resetCache[$running_process][$metaid] = true;
	}
	*/
	function getValues($running_process, $metaid) {
		global $adb, $table_prefix;
		/* TODO usare una cache e distruggerla al salvataggio del record Processes
		 * il problema accade se generi una form dinamica e poi un controllo alla sua modifica, alle volte ha in memoria ancora i vecchi valori
		static $valuesCache = array();
		
		if (self::$resetCache[$running_process][$metaid]) {
			$valuesCache[$running_process][$metaid] = null;
			unset(self::$resetCache[$running_process][$metaid]);
		}
		
		if (!isset($valuesCache[$running_process][$metaid])) {
		*/
			$form = array();
			$result = $adb->pquery("select form from {$table_prefix}_process_dynaform where running_process = ? and metaid = ?", array($running_process, $metaid));
			if ($result && $adb->num_rows($result) > 0) {
				$form = Zend_Json::decode($adb->query_result_no_html($result,0,'form'));
			}
			//crmv@105312
			$processesid = $this->getProcessesId($running_process, $metaid);
			if (!empty($processesid)) $form['crmid'] = $processesid;
			//crmv@105312e
		/*
			$valuesCache[$running_process][$metaid] = $form;
		}
		return $valuesCache[$running_process][$metaid];
		*/
		return $form;
	}
	
	function retrieveDynaform(&$focus,&$request) {
		$elementid = '';
		$blocks = $this->getCurrentDynaForm($focus,$elementid);
		if (!empty($blocks)) {
			$meta = $this->getMeta($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
			if ($meta) $save = $this->getValues($focus->column_fields['running_process'], $meta['id']);
			foreach($blocks as $block) {
				foreach($block['fields'] as $field) {
					$focus->column_fields[$field['fieldname']] = $request[$field['fieldname']] = $save[$field['fieldname']];
				}
			}
		}
	}
	
	// crmv@102879
	/**
	 * convert a value from the display format to the database format
	 */
	function formatValue($value, $field, &$form=null) { 
		//crmv@113771
		$crmvUtils = CRMVUtils::getInstance();
		return $crmvUtils->formatValue($value, $field, $form);
		//crmv@113771e
	}
	
	function parseTableField($field, &$form, $use_id=false) {

		$fieldname = $field['fieldname'];
		if (!is_array($field['columns'])) $columns = Zend_Json::decode($field['columns']); else $columns = $field['columns'];
		if (!is_array($columns) || count($columns) == 0) return null;
		
		$maxrowno = $form[$fieldname.'_lastrowno'];
		
		$result = array();
		
		for ($rowno = 0; $rowno < $maxrowno; ++$rowno) {
			$resrow = array();
			foreach ($columns as $column) {
				$colname = $column['fieldname'];
				if ($form[$fieldname.'_row_'.$rowno] == '1') {
					$colvalue = $form[$fieldname.'_'.$colname.'_'.$rowno];
					$colvalue = $this->formatValue($colvalue, $column);
					$resrow[$colname] = $colvalue;
				}
			}
			if (count($resrow) > 0) {
				if ($use_id) {
					$tmp = array(
						'id'=>$form[$fieldname.'_rowid_'.$rowno],
						'row'=>$resrow,
					);
				} else {
					$tmp = $resrow;
				}
				$sequence = $form[$fieldname.'_seq_'.$rowno];
				if (!isset($result[$sequence])) $result[$sequence] = $tmp;
				else $result[] = $tmp;	// in order to prevent overwrite due by javascript errors
			}
		}
		ksort($result);
		$result = array_values($result);
		return $result;
	}
	// crmv@102879e
	
	//crmv@141827
	function save($focus,$form) {
		global $adb, $table_prefix, $currentModule, $current_user;
		$save = array();
		$elementid = '';
		$blocks = $this->getCurrentDynaForm($focus,$elementid);
		$meta = $this->getMeta($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
		if ($meta) {
			$result = $adb->pquery("select * from {$table_prefix}_process_dynaform where running_process = ? and metaid = ?", array($focus->column_fields['running_process'],$meta['id']));
			($result && $adb->num_rows($result) > 0) ? $mode = 'update' : $mode = 'create';
			if (!empty($blocks)) {
				//crmv@105312 : fixed update bug (overwrites the values of other dynamic forms)
				if ($mode == 'update') {
					$save = $this->getValues($focus->column_fields['running_process'], $meta['id']);
					foreach($blocks as $block) {
						foreach($block['fields'] as $field) {
							if (array_key_exists($field['fieldname'],$form)) {
								// uso formatValue in quanto i valori arrivan da editview con le varie formattazioni
								$save[$field['fieldname']] = $this->formatValue($form[$field['fieldname']],$field, $form); // crmv@102879
							}
						}
					}
				} elseif ($mode == 'create' && isset($focus->engine)) {
					// load default values
					$form = $this->getDefaultValues($focus->engine,$blocks);
					foreach($blocks as $block) {
						foreach($block['fields'] as $field) {
							// non uso formatValue perchè i valori di default sono gia' in formatio db
							$save[$field['fieldname']] = $form[$field['fieldname']];
						}
					}
				}
				//crmv@105312e
			}
			if ($mode == 'create') {
				$adb->pquery("insert into {$table_prefix}_process_dynaform(running_process,metaid,form) values(?,?,?)", array($focus->column_fields['running_process'],$meta['id'],Zend_Json::encode($save)));
			} elseif ($mode == 'update') {
				$adb->pquery("update {$table_prefix}_process_dynaform set form = ? where running_process = ? and metaid = ?", array(Zend_Json::encode($save),$focus->column_fields['running_process'],$meta['id']));
			}
			//crmv@185786
			if (!empty($blocks)) {
				foreach($blocks as $block) {
					foreach($block['fields'] as $field) {
						$fieldname = $field['fieldname'];
						$sdk_file = SDK::getUitypeFile('php','dynaform.insert.after',$field['uitype']);
						if ($sdk_file != '') {
							include($sdk_file);
						}
					}
				}
			}
			//crmv@185786e
			$this->saveChangeLog($meta['id'], $focus, $save);
		}
		return $save;	//crmv@105312
	}
	//crmv@141827e
	
	function resetDynaForm($metaid, $engine, $empty=false) { // crmv@192951
		global $adb, $table_prefix;
		$save = array();
		
		$processesid = $this->getProcessesId($engine->running_process, $metaid);
		if (!empty($processesid)) {
			$focus = CRMEntity::getInstance('Processes');
			$focus->retrieve_entity_info($processesid,'Processes');
			
			$result = $adb->pquery("select elementid from {$table_prefix}_process_dynaform_meta where id = ? and processid = ?", array($metaid, $focus->column_fields['processmaker']));
			$elementid = $adb->query_result($result,0,'elementid');
			$blocks = $this->getStructure($focus->column_fields['processmaker'], $elementid, false, $engine->running_process);
			$form = $this->getDefaultValues($engine,$blocks);
			foreach($blocks as $block) {
				foreach($block['fields'] as $field) {
					// non uso formatValue perchè i valori di default sono gia' in formatio db
					$save[$field['fieldname']] = $form[$field['fieldname']];
				}
			}
			$adb->pquery("delete from {$table_prefix}_process_dynaform where running_process = ? and metaid = ?", array($engine->running_process,$metaid));
			// crmv@192951
			if (!$empty) {
				$adb->pquery("insert into {$table_prefix}_process_dynaform(running_process,metaid,form) values(?,?,?)", array($engine->running_process,$metaid,Zend_Json::encode($save)));
				$this->saveChangeLog($metaid, $focus, $save);
			}
			// crmv@192951e
			return true;
		}
		return false;
	}
	
	function saveChangeLog($metaid, $focus, $save) {
		global $adb, $table_prefix, $current_user;
		$result = $adb->pquery("select max(seq) as \"seq\" from {$table_prefix}_process_dynaform_cl where running_process = ? and metaid = ?", array($focus->column_fields['running_process'],$metaid));
		if ($result && $adb->num_rows($result) > 0) {
			$seq = $adb->query_result($result,0,'seq');
			if (is_numeric($seq)) $seq = $seq+1;
		}
		if (empty($seq)) $seq = 0;
		$adb->pquery("insert into {$table_prefix}_process_dynaform_cl(running_process,metaid,seq,userid,change_date,form) values(?,?,?,?,?,?)", array($focus->column_fields['running_process'],$metaid,$seq,$current_user->id,date('Y-m-d H:i:s'),Zend_Json::encode($save)));
	}
	
	// crmv@102879
	function getAllTableFields($processmaker) {
		$tfields = array();
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$data = $PMUtils->retrieve($processmaker);
		$helper = Zend_Json::decode($data['helper']);

		if (!empty($helper)) {
			foreach($helper as $elementid => $h) {
				if ($h['active'] == 'on' && isset($h['dynaform'])) {
					$blocks = $h['dynaform']['mmaker_blocks'];
					if (!empty($blocks)) {
						$meta = $this->getMeta($processmaker, $elementid);
						$bfields = array();
						foreach($blocks as $block) {
							if ($meta && !empty($block['fields'])) {
								// check for fields in the block
								foreach ($block['fields'] as $df) {
									if ($df['uitype'] == 220) {
										$fkey = $meta['id'].':'.$df['fieldname'];
										$bfields[$fkey] = $df['fieldlabel'];
									}
								}
							}
						}
						if (count($bfields) > 0) {									
							$key = $meta['id'].':DynaForm';
							$label = $this->getLabel($processmaker,$meta['id'],$meta);
							$tfields[$key] = array('label' => $label, 'fields' => $bfields);
						}
					}
				}
			}
		}
		return $tfields;
	}
	// crmv@102879e

	function getOptions($processmaker, $selected_value='') {
		$PMUtils = ProcessMakerUtils::getInstance();
		$data = $PMUtils->retrieve($processmaker);
		$helper = Zend_Json::decode($data['helper']);
		$values = array();
		if (!empty($helper)) {
			foreach($helper as $elementid => $h) {
				if ($h['active'] == 'on' && isset($h['dynaform'])) {
					/* crmv@141827 removed check blocks */
					$meta = $this->getMeta($processmaker, $elementid);
					if ($meta) {
						$key = $meta['id'].':DynaForm';
						$label = $this->getLabel($processmaker,$meta['id'],$meta);
						($selected_value == $key) ? $selected = 'selected' : $selected = '';
						$values[$key] = array($label, $selected);
					}
				}
			}
		}
		return $values;
	}
	
	function getLabel($processid,$metaid,$row=array()) {
		$PMUtils = ProcessMakerUtils::getInstance();
		if (empty($row)) {
			global $adb, $table_prefix;
			$result = $adb->pquery("select * from {$table_prefix}_process_dynaform_meta where id = ? and processid = ?", array($metaid,$processid));
			if ($result && $adb->num_rows($result) > 0) {
				$row = $adb->fetch_array_no_html($result);
			}
		}
		return '[$DF'.$row['id'].'] Form dinamica ('.$PMUtils->formatType($row['type'],true).': '.trim($row['text']).')';
	}
	
	// crmv@102879
	function categorizeFieldByType(&$return, $field, $groupLabel, $value, $recursive=true) { // crmv@176245
		global $current_user; // crmv@176245

		if (in_array($field['uitype'],array(15,33,300))) {	//crmv@140949
			$return['picklist'][$groupLabel][$value] = $field['fieldlabel'];
		} elseif (in_array($field['uitype'],array(10))) {
			$return['reference'][$groupLabel][$value] = $field['fieldlabel'];
			// crmv@176245
			if ($recursive) {
				// crmv@195745
				if (isset($field['relatedmods_selected'])) {
					$relatedmodules = is_array($field['relatedmods_selected']) ? $field['relatedmods_selected'] : explode(',',$field['relatedmods_selected']);
				} else {
					$relatedmodules = is_array($field['relatedmods']) ? $field['relatedmods'] : explode(',',$field['relatedmods']);
				}
				// crmv@195745e
				foreach($relatedmodules as $relatedmodule) {
					$relatedmod = vtws_describe($relatedmodule,$current_user,true);
					if (!empty($relatedmod['fields'])) {
						foreach($relatedmod['fields'] as $relatedmod_field) {
							if ($relatedmod_field['type']['name'] == 'autogenerated') continue;
							if (!isset($relatedmod_field['fieldlabel'])) $relatedmod_field['fieldlabel'] = $relatedmod_field['label'];
							$relatedmod_field['fieldlabel'] = $field['fieldlabel']." : ({$relatedmodule}) ".$relatedmod_field['fieldlabel'];
							list($m,$f) = explode('-',$value);
							$colvalue = "$m-($f : ($relatedmodule) {$relatedmod_field['name']})";
							$this->categorizeFieldByType($return, $relatedmod_field, $groupLabel, $colvalue, false);
						}
					}
				}
			}
			// crmv@176245e
		} elseif (in_array($field['uitype'],array(13))) {
			$return['email'][$groupLabel][$value] = $field['fieldlabel'];
		} elseif (in_array($field['uitype'],array(52,51,50))) {
			$return['user'][$groupLabel][$value] = $field['fieldlabel'];
		} elseif (in_array($field['uitype'],array(56))) {
			$return['boolean'][$groupLabel][$value] = $field['fieldlabel'];
		//crmv@108227
		} elseif (in_array($field['uitype'],array(5))) {
			$return['date'][$groupLabel][$value] = $field['fieldlabel'];
		//crmv@108227e
		//crmv@146187
		} elseif (in_array($field['uitype'],array(73))) {
			$return['time'][$groupLabel][$value] = $field['fieldlabel'];
		//crmv@146187e
		} elseif ($field['uitype'] == 220) {
			// table field, check if I have to display it
			$cols = Zend_Json::decode($field['columns']);
			if ($cols) {
				foreach ($cols as $col) {
					$col['fieldlabel'] = $field['fieldlabel'].': '.$col['fieldlabel'];
					$colvalue = $value.'::'.$col['fieldname'];
					$this->categorizeFieldByType($return, $col, $groupLabel, $colvalue);
				}
			}
			return;
		}
		$return['all'][$groupLabel][$value] = $field['fieldlabel'];
	}
	
	function getFieldsOptions($processmaker, $related=false) {
		$PMUtils = ProcessMakerUtils::getInstance();
		$data = $PMUtils->retrieve($processmaker);
		$helper = Zend_Json::decode($data['helper']);
		$return = array();
		if (!empty($helper)) {
			foreach($helper as $elementid => $h) {
				if ($h['active'] == 'on' && isset($h['dynaform'])) {
					$blocks = $h['dynaform']['mmaker_blocks'];
					if (!empty($blocks)) {
						$meta = $this->getMeta($processmaker, $elementid);
						if ($meta) {
							$groupLabel = $this->getLabel($processmaker,$meta['id'],$meta);
							//crmv@105312
							$value = '$DF'.$meta['id'].'-crmid';
							$return['reference'][$groupLabel][$value] = 'ID '.getTranslatedString('SINGLE_Processes','Processes');
							$return['all'][$groupLabel][$value] = 'ID '.getTranslatedString('SINGLE_Processes','Processes');
							//crmv@105312e
							foreach($blocks as $block) {
								if (!empty($block['fields'])) {
									foreach($block['fields'] as $field) {
										$value = '$DF'.$meta['id'].'-'.$field['fieldname'];
										$this->categorizeFieldByType($return, $field, $groupLabel, $value);
									}
								}
							}
						}
					}
				}
			}
		}
		//crmv@98809
		if ($related) {
			$processes = $PMUtils->getRelatedProcess($processmaker);
			if (!empty($processes)) {
				foreach($processes as $relid => $info) {
					$data = $PMUtils->retrieve($relid);
					$helper = Zend_Json::decode($data['helper']);
					if (!empty($helper)) {
						foreach($helper as $elementid => $h) {
							if ($h['active'] == 'on' && isset($h['dynaform'])) {
								$blocks = $h['dynaform']['mmaker_blocks'];
								if (!empty($blocks)) {
									$meta = $this->getMeta($relid, $elementid);
									if ($meta) {
										$groupLabel = $info['name'].' '.$this->getLabel($relid,$meta['id'],$meta);
										foreach($blocks as $block) {
											if (!empty($block['fields'])) {
												foreach($block['fields'] as $field) {
													$value = '$DF'.$info['id'].':'.$meta['id'].'-'.$field['fieldname'];
													$this->categorizeFieldByType($return, $field, $groupLabel, $value);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		//crmv@98809e
		return $return;
	}
	// crmv@102879e
	
	function getDefaultValues($engine,$blocks) {
		$form = array();
		if (!empty($blocks)) {	//crmv@141827
			foreach($blocks as $block) {
				if (!empty($block['fields'])) {	//crmv@141827
					foreach($block['fields'] as $field) {
						$referenceFields = array();
						$ownerFields = array();
						if (in_array($field['uitype'],array(10))) $referenceFields[] = $field['fieldname'];
						if (in_array($field['uitype'],array(52,51,50))) $ownerFields[] = $field['fieldname'];
						// crmv@177561
						$prepareValue = !in_array($field['uitype'],VTSimpleTemplate::$formatUitypeList);
						$form[$field['fieldname']] = $engine->replaceTags($field['fieldname'],$field['default'],$referenceFields,$ownerFields,false,null,false,$prepareValue);
						// crmv@177561e
					}
				}
			}
		}
		return $form;
	}
	
	private static $fieldTypeMapping = array();
	function getFieldTypeFromUIType($uitype){
		global $adb, $table_prefix;
		// Cache all the information for futher re-use
		if(empty(self::$fieldTypeMapping)) {
			$result = $adb->pquery("select * from ".$table_prefix."_ws_fieldtype", array());
			while($resultrow = $adb->fetch_array($result)) {
				self::$fieldTypeMapping[$resultrow['uitype']] = $resultrow;
			}
		}
		if(isset(ProcessDynaForm::$fieldTypeMapping[$uitype])){
			if(ProcessDynaForm::$fieldTypeMapping[$uitype] === false){
				return null;
			}
			$row = ProcessDynaForm::$fieldTypeMapping[$uitype];
			return $row['fieldtype'];
		} else {
			ProcessDynaForm::$fieldTypeMapping[$uitype] = false;
			return null;
		}
	}
	function getFieldTypeFromTypeOfData($tod){
		switch($tod){
			case 'T': return "time";
			case 'D':
			case 'DT': return "date";
			case 'E': return "email";
			case 'N':
			case 'NN': return "double";
			case 'P': return "password";
			case 'I': return "integer";
			case 'V':
			default: return "string";
		}
	}
	function getFieldTypeDetails($field) {
		global $current_user, $adb, $table_prefix;
		
		// crmv@102879
		$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
		$typeofdata = $ModuleMakerGenerator->getTODForField($field);
		
		$typeDetails = array();
		$fieldType = $this->getFieldTypeFromUIType($field['uitype']);
		if ($fieldType === null) {	
			$tod = explode('~',$typeofdata);
			$fieldType = $this->getFieldTypeFromTypeOfData($tod[0]);
		}
		$typeDetails['name'] = $fieldType;
		$typeDetails['typeofdata'] = $typeofdata;
		// crmv@102879e

		switch($fieldType){
			case 'reference':
				//crmv@110181
				if (empty($field['relatedmods'])) {
					$relatedmods = array();
					$result = $adb->pquery("select type from {$table_prefix}_ws_fieldtype
						inner join {$table_prefix}_ws_referencetype ON {$table_prefix}_ws_referencetype.fieldtypeid = {$table_prefix}_ws_fieldtype.fieldtypeid
						WHERE uitype = ?", array($field['uitype']));
					if ($result && $adb->num_rows($result) > 0) {
						while($row=$adb->fetchByAssoc($result)) {
							$relatedmods[] = $row['type'];
						}
					}
					$typeDetails['refersTo'] = $relatedmods;
				} else {
					$typeDetails['refersTo'] = explode(',',$field['relatedmods']);
				}
				//crmv@110181e
				break;
			case 'multipicklist':
			case 'picklist':
				$picklistvalues = array();
				$tmp = explode("\n", rtrim(str_replace("\r", "", $field['picklistvalues'])));
				foreach($tmp as $value) {
					$picklistvalues[] = array('label'=>getTranslatedString($value,'Processes'),'value'=>$value);	//crmv@112993
				}
				$typeDetails["picklistValues"] = $picklistvalues;
				$typeDetails['defaultValue'] = $typeDetails["picklistValues"][0]['value'];
				break;
			// crmv@31780
			case 'currency':
				$currid = 1;
				if (!empty($current_user)) {
					$currid =$current_user->column_fields['currency_id'];
				}
				list($symb_name, $symb) = explode(' : ', getCurrencyName($currid), 2);
				$typeDetails['symbol'] = $symb;
				$typeDetails['symbol_name'] = $symb_name;
				break;
			// crmv@31780e
			case 'date': $typeDetails['format'] = $current_user->date_format;
		}
		return $typeDetails;
	}
	
	// add blocks and fields of dynaform without applying conditionals
	// if there are conditionals reload blocks via js
	function addBlockInformation($col_fields, $mode, &$return_fields, &$blockdata, &$aBlockStatus, $dynaFormValues=array(), $conditionalPermissions=false) { // crmv@104568
		global $adb, $table_prefix, $currentModule, $current_user, $app_strings;
		$elementid = '';
		$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
		$focus = CRMEntity::getInstance('Processes');
		$focus->retrieve_entity_info_no_html($col_fields['record_id'],'Processes');
		$blocks = $this->getCurrentDynaForm($focus,$elementid);
		if (!empty($blocks)) {
			$meta = $this->getMeta($col_fields['processmaker'], $elementid, false, $col_fields['running_process']);
			if (empty($dynaFormValues)) $dynaFormValues = $this->getValues($col_fields['running_process'], $meta['id']);
			if ($conditionalPermissions) $permissions = $this->getConditionalPermissions($col_fields['record_id'],$dynaFormValues,true);	//crmv@99316
			$col_fields = array_merge($col_fields,$dynaFormValues);
			foreach($blocks as $blockid => $block) {
				if (!empty($block['fields'])) {
					// TODO translate $block['label']
					$blockid = 'DF'.$blockid;
					$aBlockStatus[$block['label']] = 1;
					// crmv@104568
					$blockdata[$blockid] = array(
						'label' => $block['label'],
						'panelid' => getCurrentPanelId('Processes'),
					);
					// crmv@104568e
					foreach($block['fields'] as $field) {
						if ($mode == 'edit' && $field['displaytype'] != 1) continue; //crmv@160837
						$generatedtype = 1; //crmv@157799
						$readonly = $field['readonly'];
						$mandatory = $field['mandatory'];
						//crmv@99316
						if (isset($permissions[$field['fieldname']])) {
							$readonly = $permissions[$field['fieldname']]['readonly'];
							$mandatory = $permissions[$field['fieldname']]['mandatory'];
						}
						//crmv@99316e
						switch ($field['uitype']) {
							case 5:
								$generatedtype = 2; //crmv@157799
								if ($current_user) {
									$fld_value = $col_fields[$field['fieldname']];
									$fld_value = substr($fld_value, 0, 10);
									$fld_value = adjustTimezone($fld_value, 0, null, false);
									$col_fields[$field['fieldname']] = $fld_value;
								}
								break;
						}
						// TODO translate $field['label']
						if ($mode == 'detail') {
							$custfld = getDetailViewOutputHtml($field['uitype'], $field['fieldname'], $field['label'], $col_fields, $generatedtype, '', $currentModule, $field); //crmv@157799
							if (is_array($custfld)) {
								$fieldtablename = '';
								$fieldname = $field['fieldname'];
								$fieldid = $field['fieldname'];
								$displaytype = 1;
								$return_fields[$blockid][] = array($custfld[0]=>array("value"=>$custfld[1],"ui"=>$custfld[2],"options"=>$custfld["options"],"secid"=>$custfld["secid"],"link"=>$custfld["link"],"cursymb"=>$custfld["cursymb"],"salut"=>$custfld["salut"],"notaccess"=>$custfld["notaccess"],"isadmin"=>$custfld["isadmin"],"tablename"=>$fieldtablename,"fldname"=>$fieldname,"fldid"=>$fieldid,"displaytype"=>$displaytype,"readonly"=>$readonly,'mandatory'=>$mandatory));
							}
						} elseif ($mode == 'edit') {
							$typeofdata = $ModuleMakerGenerator->getTODForField($field);
							if ($mandatory) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
							$fld = getOutputHtml($field['uitype'], $field['fieldname'], $field['label'], $field['length'], $col_fields, $generatedtype, $currentModule, $mode, $readonly, $typeofdata, $field); //crmv@157799
							$fld[] = $field['fieldname'];
							$return_fields[$blockid][] = $fld;
						}
					}
				}
			}
		}
	}
	
	// add validation data of dynaform without applyng conditionals
	// if there are conditionals reload blocks via js
	function addValidationData($focus, &$otherInfo, &$fieldinfos) {
		global $adb;
		$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
		$elementid = '';
		$blocks = $this->getCurrentDynaForm($focus, $elementid);
		if (!empty($blocks)) {
			$meta = $this->getMeta($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
			$dynaFormValues = $this->getValues($focus->column_fields['running_process'], $meta['id']);
			//$permissions = $this->getConditionalPermissions($focus->id,$dynaFormValues,true);	//crmv@99316
			foreach($blocks as $block) {
				if (!empty($block['fields'])) {
					foreach($block['fields'] as $field) {
						$fieldname = $field['fieldname'];
						$webservice_field = WebserviceField::fromArray($adb,$field);
						if (in_array($webservice_field->getFieldDataType(),array('multipicklist','file')) && $_REQUEST['action'] == 'EditView') {
							$fieldname = $fieldname.'[]';
						}
						$typeofdata = $ModuleMakerGenerator->getTODForField($field);
						if ($field['mandatory']) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
						/*
						if ($permissions[$field['fieldname']]['mandatory']) {
							$typeofdata = $ModuleMakerGenerator->getTODForField($field);
							$typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);	//crmv@99316
						}*/
						$fieldinfos[$fieldname] = Array($field['label'] => $typeofdata);
						$otherInfo['fielduitype'][$fieldname] = intval($field['uitype']);
						$otherInfo['fieldwstype'][$fieldname] = $webservice_field->getFieldDataType();
						
						if ($field['uitype'] == 220) {
							$field['columns'] = Zend_Json::decode($field['columns']);
							foreach($field['columns'] as $column) {
								if (!empty($dynaFormValues[$field['fieldname']])) {
									foreach($dynaFormValues[$field['fieldname']] as $seq => $row) {
										$fieldname = $field['fieldname'].'_'.$column['fieldname'].'_'.$seq;
										$webservice_field = WebserviceField::fromArray($adb,$column);
										if (in_array($webservice_field->getFieldDataType(),array('multipicklist','file')) && $_REQUEST['action'] == 'EditView') {
											$fieldname = $fieldname.'[]';
										}
										$typeofdata = $ModuleMakerGenerator->getTODForField($column);
										if ($column['mandatory']) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
										/*
										if ($permissions[$fieldname]['mandatory']) {
											$typeofdata = $ModuleMakerGenerator->getTODForField($column);
											$typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
										}*/
										$fieldinfos[$fieldname] = Array($column['label'] => $typeofdata);
										$otherInfo['fielduitype'][$fieldname] = intval($column['uitype']);
										$otherInfo['fieldwstype'][$fieldname] = $webservice_field->getFieldDataType();
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	//crmv@99316
	function existsConditionalPermissions($focus) {
		$elementid = '';
		$fields = $this->getFields($focus, $elementid);
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$xml_version = $PMUtils->getSystemVersion4RunningProcess($focus->column_fields['running_process'],array('processmaker','xml_version'));
		$data = $PMUtils->retrieve($focus->column_fields['processmaker'],$xml_version);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$dfconditionals = $vte_metadata[$elementid]['dfconditionals'];
		
		return !empty($dfconditionals);
	}
	function existsConditionalFpovValueActive($focus, &$conditionalFields = null) { // crmv@198388
		$elementid = '';
		$fields = $this->getFields($focus, $elementid);
		$conditionalFields = array(); // crmv@198388
		
		$PMUtils = ProcessMakerUtils::getInstance();
		$xml_version = $PMUtils->getSystemVersion4RunningProcess($focus->column_fields['running_process'],array('processmaker','xml_version'));
		$data = $PMUtils->retrieve($focus->column_fields['processmaker'],$xml_version);
		$vte_metadata = Zend_Json::decode($data['vte_metadata']);
		$dfconditionals = $vte_metadata[$elementid]['dfconditionals'];
		if (!empty($dfconditionals)) {
			// crmv@198388
			foreach($dfconditionals as $dfconditional) {
				if (!empty($dfconditional['conditions'])) {
					foreach ($dfconditional['conditions'] as $group) {
						if ($group['conditions']) {
							foreach ($group['conditions'] as $cond) {
								if (stripos($cond['fieldname'],'ml') === 0) {
									// skip table fields (ex. ml26)
								} else {
									$conditionalFields[] = $cond['fieldname'];
								}
							}
						}
					}
				}
			}
			// crmv@198388e
			foreach($dfconditionals as $dfconditional) {
				if (!empty($dfconditional['fpofv'])) {
					foreach($dfconditional['fpofv'] as $fieldname => $params) {
						if ($params['FpovValueActive'] == 1 || $params['FpovManaged'] == 1) { // crmv@196985
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	function getConditionalPermissions($record,$dynaform=array(),$use_default=false) {
		global $currentModule, $current_user;
		$permissions = array();
	
		$focus = CRMEntity::getInstance('Processes');
		$focus->retrieve_entity_info($record,'Processes');
		$elementid = '';
		$fields = $this->getFields($focus, $elementid);

		if (empty($dynaform)) {
			$meta = $this->getMeta($focus->column_fields['processmaker'], $elementid, false, $focus->column_fields['running_process']);
			$dynaform = $this->getValues($focus->column_fields['running_process'], $meta['id']);
		}
		if (!empty($dynaform)) {
			// ??? � giusto formatValue ?
//			foreach($dynaform as $fieldname => $value) {
//				$dynaform[$fieldname] = $this->formatValue($value,$fields[$fieldname]);
//			}
			$PMUtils = ProcessMakerUtils::getInstance();
			$xml_version = $PMUtils->getSystemVersion4RunningProcess($focus->column_fields['running_process'],array('processmaker','xml_version'));
			$data = $PMUtils->retrieve($focus->column_fields['processmaker'],$xml_version);
			$vte_metadata = Zend_Json::decode($data['vte_metadata']);
			$dfconditionals = $vte_metadata[$elementid]['dfconditionals'];

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
			
			$entityCache = new VTEntityCache($current_user);
			$PMUtils->setDefaultDataFormat();
			$entityCache->forId($record);
			$PMUtils->restoreDataFormat();
			$entityCache::$cache[$record]->data = $dynaform;
			
			if ($use_default) {
				// init permissions with layout editor configuration
				foreach($dynaform as $fieldname => $value) {
					$field = $fields[$fieldname];
					$permissions[$fieldname] = array('readonly'=>$field['readonly'], 'mandatory'=>($field['mandatory'] == 1));
				}
			}

			if (!empty($dfconditionals)) {
				// split standard and table-field conditionals
				$dfconditionals_std = array();
				$dfconditionals_tabs = array();
				foreach($dfconditionals as $i => $dfconditional) {
					$tab = false;
					if (!empty($dfconditional['conditions'])) {
						foreach($dfconditional['conditions'] as $subconditions) {
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
					($tab) ? $dfconditionals_tabs[] = $dfconditionals[$i] : $dfconditionals_std[] = $dfconditionals[$i];
				}
				if (!empty($dfconditionals_tabs)) {
					$actionType = $PMUtils->getActionTypes('Cycle');
					require_once($actionType['php_file']);
					$actionCycle = new $actionType['class']();
				}
				$i = 0;
				if (!empty($dfconditionals_std)) {
					foreach($dfconditionals_std as $dfconditional) {
						$role_grp_check = $dfconditional['role_grp_check'];
						if (in_array($role_grp_check,$role_grp_checks)) {
							$conditions = Zend_Json::encode($dfconditional['conditions']);
							if ($PMUtils->evaluateCondition($entityCache, $record, $conditions)) {
								foreach($dynaform as $fieldname => $value) {
									$perm = $dfconditional['fpofv'][$fieldname];
									$this->setFieldConditionalPermissions($perm, $i, $fieldname, $permissions);
									$field = $fields[$fieldname];
									if ($field['type'] == 'table') {
										if (is_array($value)) {
											foreach($value as $seq => $row) {
												foreach($row as $column => $column_value) {
													$perm = $dfconditional['fpofv'][$fieldname.'::'.$column];
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
				if (!empty($dfconditionals_tabs)) {
					foreach($dynaform as $fieldname => $value) {
						$field = $fields[$fieldname];
						if ($field['type'] == 'table') {
							if (is_array($value)) {
								foreach($value as $seq => $row) {
									foreach($dfconditionals_tabs as $dfconditional) {
										$role_grp_check = $dfconditional['role_grp_check'];
										if (in_array($role_grp_check,$role_grp_checks)) {
											$conditions = Zend_Json::encode($dfconditional['conditions']);
											if ($actionCycle->checkRowConditions(null, $dynaform, $conditions, $seq)) {
												// applico permessi alla riga e anche agli altri campi del modulo
												$fpofv = $dfconditional['fpofv'];
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
			}
		}
		// set in request cache
		$cache = RCache::getInstance();
		$cache->set('conditional_permissions', $permissions);
		return $permissions;
	}
	function setFieldConditionalPermissions($perm, $i, $fieldname, &$permissions) {
		$PMUtils = ProcessMakerUtils::getInstance();
		$PMUtils->setFieldConditionalPermissions($perm, $i, $fieldname, $permissions);
	}
	function replaceTags($value, $dynaform, $row) { // crmv@200250
		$PMUtils = ProcessMakerUtils::getInstance();
		return $PMUtils->replaceTags($value, $dynaform, '/(\$vcf_([0-9:]+))/', $row); // crmv@200250
	}
	
	// reload blocks, fields and validation info applying conditionals
	function applyConditionals($mode,$record,$dynaform) {
		global $currentModule, $current_user, $app_strings, $mod_strings, $theme, $default_charset;
		$focus = CRMEntity::getInstance('Processes');
		$focus->retrieve_entity_info($record,'Processes');
		$elementid = '';
		$fields = $this->getFields($focus, $elementid, true);
		foreach($dynaform as $fieldname => $value) {
			if ($fields[$fieldname]['uitype'] == 220)
				$dynaform[$fieldname] = $this->formatValue($value,$fields[$fieldname],$dynaform[$fieldname]);
			else
				$dynaform[$fieldname] = $this->formatValue($value,$fields[$fieldname]);
		}
		$dynaform_tmp = $dynaform;
		$permissions = $this->getConditionalPermissions($record,$dynaform,true);
		if (!empty($permissions)) {
			foreach($dynaform as $fieldname => $value) {
				if ($fields[$fieldname]['uitype'] == 220) {
					if (!empty($value)) {
						foreach($value as $row => $tab_fields) {
							foreach($tab_fields as $tab_fieldname => $tab_value) {
								if (isset($permissions[$fieldname.'_'.$tab_fieldname.'_'.$row]['value'])) {
									$dynaform[$fieldname][$row][$tab_fieldname] = $this->replaceTags($permissions[$fieldname.'_'.$tab_fieldname.'_'.$row]['value'],$dynaform_tmp,$row); // crmv@200250
								}
							}
						}
					}
				} else {
					if (isset($permissions[$fieldname]['value'])) {
						$dynaform[$fieldname] = $this->replaceTags($permissions[$fieldname]['value'],$dynaform_tmp);
					}
				}
			}
		}

		$output = array();
		$blockVisibility = array();	//crmv@99316
		
		// reload all dynaform blocks. for detail mode reload via javascript
		if ($mode == 'edit') {
			$focus = CRMEntity::getInstance('Processes');
			$focus->retrieve_entity_info($record,'Processes');
		
			$editview_arr = array();
			$blockdata = array();
			$aBlockStatus = array();
			$this->addBlockInformation($focus->column_fields, $mode, $editview_arr, $blockdata, $aBlockStatus, $dynaform);
			//crmv@99316
			foreach($editview_arr as $headerid => $editview_value) {
				foreach($editview_value as $i => $arr) {
					if (isset($permissions[$arr[2][0]])) {	// overwrite readonly and mandatory
						$editview_arr[$headerid][$i][4] = $arr[4] = $permissions[$arr[2][0]]['readonly'];
						$editview_arr[$headerid][$i][5] = $arr[5] = ($permissions[$arr[2][0]]['mandatory'] == '1')?'M':'O';
					}
					if ($arr[4] == 100) unset($editview_value[$i]);	// skip field
				}
				if (empty($editview_value)) $blockVisibility[$headerid] = 0;	// skip block if empty
			}
			//crmv@99316e
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
			foreach($blockdata as $blockid=>$blockinfo)
			{
				$label = $blockinfo['label'];
				if($label == '')
				{
					$returndata[$blockid]=array_merge((array)$returndata[$blockid],(array)$editview_arr[$blockid]);
				}
				else
				{
					$curBlock = $label;
					if(is_array($editview_arr[$blockid]))
						$returndata[$blockid]=array_merge((array)$returndata[$blockid],(array)$editview_arr[$blockid]);
				}
			}
			if (!empty($returndata)) {
				$smarty = new VteSmarty();
				$smarty->assign('MODE','');
				$smarty->assign('MODULE','Processes');
				$smarty->assign('APP',$app_strings);
				$smarty->assign('MOD',$mod_strings);
				$smarty->assign("THEME", $theme);
				$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
				$smarty->assign('ID', '');
				foreach($returndata as $header => $data) {
					$smarty->assign('data',$data);
					$smarty->assign('header',$header);
					$output['html'][$header] = htmlentities($smarty->fetch('DisplayFields.tpl'),ENT_QUOTES,$default_charset);
				}
			}
		}
		$output['block_visibility'] = $blockVisibility;	//crmv@99316
		
		if (!empty($permissions)) {
			$ModuleMakerGenerator = new ProcessModuleMakerGenerator();
			
			// fielddatatype for javascript validation
			$tabid = getTabid('Processes');
			$otherInfo = array();
			$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo,$focus->column_fields);
			$validationArray = getValidationdataArray($validationData, $otherInfo, true);
			$output = array_merge($output,$validationArray);
			foreach($fields as $field) {
				$fieldname = $field['fieldname'];
				if (in_array($field['uitype'],array(33,29)) && $mode == 'edit') $fieldname = $fieldname.'[]';
				$output['fieldname'][$fieldname] = $fieldname;
				$output['fieldlabel'][$fieldname] = $field['label'];
				$output['fielduitype'][$fieldname] = $field['uitype'];
				$typeofdata = $ModuleMakerGenerator->getTODForField($field);
				if ($field['mandatory']) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
				$output['datatype'][$fieldname] = $typeofdata;
				$output['fieldwstype'][$fieldname] = $field['type'];
				
				if ($field['type'] == 'table') {
					foreach($field['columns'] as $column) {
						foreach($dynaform[$field['fieldname']] as $seq => $row) {
							$fieldname = $field['fieldname'].'_'.$column['fieldname'].'_'.$seq;
							if (in_array($column['uitype'],array(33,29)) && $mode == 'edit') $fieldname = $fieldname.'[]';
							$output['fieldname'][$fieldname] = $fieldname;
							$output['fieldlabel'][$fieldname] = $column['label'];
							$output['fielduitype'][$fieldname] = $column['uitype'];
							$typeofdata = $ModuleMakerGenerator->getTODForField($column);
							if ($column['mandatory']) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
							$output['datatype'][$fieldname] = $typeofdata;
							$output['fieldwstype'][$fieldname] = $column['type'];
						}
					}
				}
			}
			// html
			foreach($permissions as $fieldname => $perm) {
				$field = $fields[$fieldname];
				if ($field['uitype'] == 213) continue;	// skip
				if (in_array($field['uitype'],array(33,29)) && $mode == 'edit') $fieldname = $fieldname.'[]';
				
				$readonly = $perm['readonly'];
				$mandatory = $perm['mandatory'];
				$typeofdata = $ModuleMakerGenerator->getTODForField($field);
				if ($mandatory) $typeofdata = $ModuleMakerGenerator->makeTODMandatory($typeofdata);
				/*
				if ($mode == 'edit') {
					$fld = getOutputHtml($field['uitype'], $field['fieldname'], $field['label'], $field['length'], $dynaform, 1, $currentModule, 'edit', $readonly, $typeofdata, $field);
					$fld[] = $field['fieldname'];
					$output['html'][$field['fieldname']] = $this->getEditHtmlField($fld);
				} */
				$output['datatype'][$fieldname] = $typeofdata;	// set new typeofdata for javascript validation
			}
			$output['fieldname'] = array_values($output['fieldname']);
			$output['fieldlabel'] = array_values($output['fieldlabel']);
			$output['datatype'] = array_values($output['datatype']);
			$output['fielduitype'] = array_values($output['fielduitype']);
			$output['fieldwstype'] = array_values($output['fieldwstype']);
		}
		return $output;
	}
	/*
	function getEditHtmlField($maindata) {
		$smarty = new VteSmarty();
		$smarty->assign("uitype",$maindata[0][0]);
		$smarty->assign("fldlabel",$maindata[1][0]);
		$smarty->assign("fldlabel_sel",$maindata[1][1]);
		$smarty->assign("fldlabel_combo",$maindata[1][2]);
		$smarty->assign("fldname",$maindata[2][0]);
		$smarty->assign("fldvalue",$maindata[3][0]);
		$smarty->assign("secondvalue",$maindata[3][1]);
		$smarty->assign("thirdvalue",$maindata[3][2]);
		$smarty->assign("readonly",$maindata[4]);
		$smarty->assign("typeofdata",$maindata[5]);
		$smarty->assign("isadmin",$maindata[6]);
		$smarty->assign("keyfldid",$maindata[7]);
		if ($maindata[5] == 'M') {
			$smarty->assign("mandatory_field",'*');
			$keymandatory = true;
		} else {
			$smarty->assign("mandatory_field",'');
			$keymandatory = false;
		}
		$smarty->assign("keymandatory",$keymandatory);
		if ($maindata[4] == 100) {
			$html = '<div style="display:none;">'.$smarty->fetch('DisplayFieldsHidden.tpl').'</div>';
		} elseif ($maindata[4] == 99) {
			$smarty->assign("DIVCLASS",'dvtCellInfoOff');
			$html = $smarty->fetch('DisplayFieldsReadonly.tpl');
		} else {
			if ($keymandatory)
				$smarty->assign("DIVCLASS",'dvtCellInfoM');
			else
				$smarty->assign("DIVCLASS",'dvtCellInfo');
			$html = $smarty->fetch('EditViewUI.tpl');
		}
		$html = str_replace("\n",'',$html);
		$html = str_replace("\r",'',$html);
		$html = str_replace("\t",'',$html);
		return $html;
	}*/
	//crmv@99316e
	
	//crmv@103534
	function propagateParallelsChangeLog($processesid,$changeLogFocus) {
		global $adb, $table_prefix;
		// check if the process is a fake and then propagate the change log to the main process
		$result = $adb->pquery("SELECT casperid FROM {$table_prefix}_process_gateway_conn WHERE processesid = ?", array($processesid));
		if ($result && $adb->num_rows($result) > 0) {
			$casperid = $adb->query_result($result,0,'casperid');
			$changeLogFocus->column_fields['audit_no'] = $changeLogFocus->get_revision_id($casperid);
			$changeLogFocus->column_fields['parent_id'] = $casperid;
			$changeLogFocus->save(); // crmv@164120
		}
	}
	//crmv@103534e
}