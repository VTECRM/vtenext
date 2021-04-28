<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@164120 */


class ChangeLog extends SDKExtendableClass {

	public $table_name;
	public $table_index = 'changelogid';

	// list of "fields"
	public $column_fields = array(
		'audit_no' => '',
		'user_id' => 0,
		'user_name' => '',
		'display_id' => 0,
		'display_module' => '',
		'display_name' => '',
		'modified_date' => '',
		'parent_id' => 0,
		'description' => '',
		'hide' => 0,
	);
	
	public $modules_to_skip = array('Emails','Fax','Sms','Events','ModComments','ChangeLog','Targets', 'Messages', 'Users');

	// crmv@109801	
	// fields to jump in every module
	public $fields_to_jump = array();
	
	//fields to jump in specific modules
	public $fields_to_jump_module = array(
		'HelpDesk' => array('modifiedtime','time_elapsed','time_remaining','start_sla','end_sla','time_refresh','time_change_status','time_elapsed_change_status','ended_sla','time_elapsed_idle','time_elapsed_out_sla'),
		'Events' => array('duration_hours','duration_minutes'),
		'MorphsuitServer' => array('morphsuit_key','morphsuit_new_key'),
	);
	
	// fields to be saved, but not shown in the list of changes (global)
	public $fields_to_hide = array();
	
	// fields to be saved, but not shown in the list of changes (per module)
	public $fields_to_hide_module = array();
	// crmv@109801e
	
	// true to merge record updates in the same request
	public $merge_updates = true; // crmv@177677
	
	protected static $lastSavedChangelogs = []; // crmv@198950

	function __construct() {
		global $table_prefix;
		$this->table_name = $table_prefix.'_changelog';
	}	

	/**
	 * Save a new changelog record
	 */
	function save() {
		global $adb, $global_hide_changelog;
		
		// do save
		$columns = $adb->getColumnNames($this->table_name);
		
		// compatibility fix
		if (isset($this->column_fields['assigned_user_id']) && !isset($this->column_fields['user_id'])) {
			$this->column_fields['user_id'] = $this->column_fields['assigned_user_id'];
		}
		
		$id = $adb->getUniqueId($this->table_name);
		$inserts = array(
			'changelogid' => $id
		);
		foreach ($this->column_fields as $column => $value) {
			if ($column != $this->table_index && in_array($column, $columns)) {
				$inserts[$column] = $value;
			}
		}
		
		// hide the record if requested
		if ($global_hide_changelog === true) {
			$description = Zend_Json::decode($this->column_fields['description']);
			if ($description[0] != 'ChangeLogCreation') {
				$inserts['hide'] = 1;
			}
		}
		
		if (empty($inserts['modified_date'])) $inserts['modified_date'] = date('Y-m-d H:i:s');
		
		$insCols = array_keys($inserts);
		$adb->format_columns($insCols);
		
		$sql = "INSERT INTO {$this->table_name} (".implode(',',$insCols).") VALUES (".generateQuestionMarks($inserts).")";
		
		$adb->pquery($sql, $inserts);
		
		$this->addSavedChangelog($this->column_fields['parent_id'], $id); // crmv@198950
		
		return $id;
	}
	
	// crmv@198950
	public function addSavedChangelog($parent_id, $changelogid) {
		if ($parent_id > 0) {
			self::$lastSavedChangelogs[$parent_id][] = $changelogid;
		}
	}
	
	public function hasSavedChangelog($parent_id) {
		return array_key_exists($parent_id, self::$lastSavedChangelogs);
	}
	
	public function clearSavedChangelogs($parent_id) {
		unset(self::$lastSavedChangelogs[$parent_id]);
	}
	// crmv@198950e
	
	/**
	 * Delete changelogs for record
	 */
	public function deleteforRecord($module, $parentid) {
		global $adb;
		$adb->pquery("DELETE FROM {$this->table_name} WHERE parent_id = ?", array($parentid));
	}

	// crmv@109801
	public function isFieldSkipped($module, $fieldname, $uitype = 1) {
		return 
			in_array($fieldname, $this->fields_to_jump)
			|| (is_array($this->fields_to_jump_module[$module]) && in_array($fieldname,$this->fields_to_jump_module[$module]))
			|| $uitype == 208;
	}
	
	public function isFieldHidden($module, $fieldname) {
		return in_array($fieldname, $this->fields_to_hide) || 
			(is_array($this->fields_to_hide_module[$module]) && in_array($fieldname,$this->fields_to_hide_module[$module]));
	}
	// crmv@109801e

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			global $adb, $table_prefix;

			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$moduleInstance->hide(array('hide_module_manager'=>1));
			$adb->pquery("UPDATE {$table_prefix}_def_org_share SET editstatus = ? WHERE tabid = ?",array(2,$moduleInstance->id));
			
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));
						
			$em = new VTEventsManager($adb);
			$em->registerHandler('history_first', 'modules/ChangeLog/ChangeLogHandler.php', 'ChangeLogHandler');
			$em->registerHandler('history_last', 'modules/ChangeLog/ChangeLogHandler.php', 'ChangeLogHandler');
			
			//self::enableWidgetToAll();
			
			//crmv@171832
			require_once('include/utils/CronUtils.php');
			$CU = CronUtils::getInstance();
			$cj = CronJob::getByName('CleanEditViewEtag');
			if (empty($cj)) {
				$cj = new CronJob();
				$cj->name = 'CleanEditViewEtag';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/ChangeLog/CleanEditViewEtag.php';
				$cj->timeout = 120; // 2 min
				$cj->repeat = 3600; // 60min
				$CU->insertCronJob($cj);
			}
			//crmv@171832e
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * @deprecated
	 */
	static function addWidgetTo($moduleNames, $widgetType='DETAILVIEWWIDGET', $widgetName='DetailViewBlockChangeLogWidget') {
		
	}
	
	/**
	 * @deprecated
	 */
	function enableWidgetToAll() {

	}

	/**
	 * @deprecated
	 */
	function disableRelatedForAll() {

	}
	
	/**
	 * @deprecated
	 */
	function disableWidgetToAll() {

	}
	
	/**
	 * @deprecated
	 */
	function enableWidget($moduleNames){

	}
	
	/**
	 * @deprecated
	 */
	function disableWidget($moduleNames){

	}
	
	function isEnabled($module){
		// by default enabled for every module
		return !in_array($module, $this->modules_to_skip);
	}
	
	// crmv@31780	crmv@53684	crmv@57348	crmv@104566
	function getFieldsTable($description, $module, $nohtml=false, &$log_type=''){
		global $table_prefix, $current_user; // crmv@160858 crmv@164655
		global $app_strings;

		$html = '';
		$ret = array();
		$description_elements = Zend_Json::decode($description);
		if(is_array($description_elements)) {
			if ($description_elements[0] == 'GenericChangeLog') {
				$log_type = 'generic';
				($nohtml) ? $ret = $description_elements[1] : $html .= $description_elements[1];
			} elseif ($description_elements[0] == 'ChangeLogCreation') {
				$log_type = 'create';
			} elseif ($description_elements[0] == 'ChangeLogRemoveRelation1N') {
				$log_type = 'remove_relation';
				$record1 = $description_elements[1];
				$module1 = $description_elements[2];
				if (isPermitted($module1,'DetailView',$record1) !== 'no') {
					$recordName = $description_elements[6] ?: getEntityName($module1,array($record1)); // crmv@164655
					if ($nohtml) {
						$ret = array(
							'record1' => $record1,
							'module1' => $module1,
							'entityname1' => $recordName, // crmv@164655
						);
					} else {
						$html = sprintf(getTranslatedString('LBL_HAS_REMOVED_LINK_WITH_RECORD','ChangeLog'), "<a href='{$match1[1]}'>{$recordName}</a> (".getSingleModuleName($module1).")"); // crmv@164655
					}
				} else {
					if ($nohtml) {
						$ret = false;
					} else {
						$html = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
					}
				}
			} elseif ($description_elements[0] == 'ModNotification_Relation' || $description_elements[0] == 'ChangeLogRelationNN' || $description_elements[0] == 'ChangeLogRelation1N') {
				$log_type = 'relation';
				if ($description_elements[0] == 'ModNotification_Relation') {
					$tmp = $description_elements[1];
					$tmp = explode(' LBL_LINKED_TO ',$tmp);
	
					$tmp1 = substr($tmp[0],0,strpos($tmp[0],' ('));
					$url1 = preg_match("/<a href='(.+)'>/", $tmp1, $match1);
					$info1 = parse_url($match1[1]);
					parse_str($info1['query'], $info1);
					$module1 = $info1['module'];
					$record1 = $info1['record'];
					
					$tmp2 = substr($tmp[1],0,strpos($tmp[1],' ('));
					$url2 = preg_match("/<a href='(.+)'>/", $tmp2, $match2);
					$info2 = parse_url($match2[1]);
					parse_str($info2['query'], $info2);
					$module2 = $info2['module'];
					$record2 = $info2['record'];
				} else {
					$record1 = $description_elements[1];
					$module1 = $description_elements[2];
					$record2 = $description_elements[3];
					$module2 = $description_elements[4];
					$recordName1 = $description_elements[5]; // crmv@164655
				}					
				if (isPermitted($module1,'DetailView',$record1) !== 'no' && isPermitted($module2,'DetailView',$record2) !== 'no') {
					$name1 = $recordName1 ?: getEntityName($module1,array($record1), true); // crmv@164655
					$name2 = getEntityName($module2,array($record2), true); // crmv@164655
					if ($nohtml) {
						$ret = array(
							'record1' => $record1,
							'module1' => $module1,
							'record2' => $record2,
							'module2' => $module2,
							'entityname1' => $name1, // crmv@164655
							'entityname2' => $name2, // crmv@164655
						);
					} else {
						// crmv@160858
						if ($module2 == 'Calendar') {
							$activitytype = getSingleFieldValue($table_prefix.'_activity', 'activitytype', 'activityid', $record2);
							$setype2 = getTranslatedString($activitytype);
						} else {
							$setype2 = getSingleModuleName($module2);
						}
						$html = "<a href='{$match1[1]}'>{$name1}</a> (".getSingleModuleName($module1).") ".getTranslatedString('LBL_LINKED_TO','ChangeLog')." <a href='{$match2[1]}'>{$name2}</a> (".$setype2.")"; // crmv@164655
						// crmv@160858e
					}
				} else {
					if ($nohtml) {
						$ret = false;
					} else {
						$html = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
					}
				}
			// crmv@151474
			} elseif ($description_elements[0] == 'ChangeLogNLUnsubscribe') {
				// unsubscribe from newsletter (doesn't handle the change of email address)
				$log_type = 'generic';
				if ($description_elements[2] == 'campaign') {
					$nllink = $description_elements[3];
					if (isPermitted('Newsletter', 'DetailView', $description_elements[1]) === 'yes') {
						$nllink = '<a href="index.php?module=Newsletter&action=DetailView&record='.$description_elements[1].'">'.$nllink.'</a>';
					}
					$html = str_replace('%s', $nllink, getTranslatedString('LBL_RECORD_UNSUBSCRIBED_FROM', 'ChangeLog'));
				} elseif ($description_elements[2] == 'all') {
					$html = getTranslatedString('LBL_RECORD_UNSUBSCRIBED_FROM_ALL', 'ChangeLog');
				}
				$ret = $html;
			} elseif ($description_elements[0] == 'ChangeLogNLSubscribe') {
				$log_type = 'generic';
				if ($description_elements[2] == 'campaign') {
					$nllink = $description_elements[3];
					if (isPermitted('Newsletter', 'DetailView', $description_elements[1]) === 'yes') {
						$nllink = '<a href="index.php?module=Newsletter&action=DetailView&record='.$description_elements[1].'">'.$nllink.'</a>';
					}
					$html = str_replace('%s', $nllink, getTranslatedString('LBL_RECORD_SUBSCRIBED_TO', 'ChangeLog'));
				} elseif ($description_elements[2] == 'all') {
					$html = getTranslatedString('LBL_RECORD_SUBSCRIBED_TO_ALL', 'ChangeLog');
				}
				$ret = $html;
			// crmv@151474e
			// crmv@152087
			} elseif ($description_elements[0] == 'DownloadFile') {
				$log_type = 'generic';
				$html = getTranslatedString('LBL_RECORD_DOWNLOADED', 'ChangeLog');
				$ret = $html;
			// crmv@152087e
			} else {
				$log_type = 'edit';
				if (!$nohtml) {
					$html .= '<table class="table">';
					$html .= '<tr>
							 	<td style="width: 33%;"><b>'.getTranslatedString('Field','ChangeLog').'</b></td>
							    <td style="width: 33%;"><b>'.getTranslatedString('Earlier value','ChangeLog').'</b></td>
							    <td style="width: 33%;"><b>'.getTranslatedString('Actual value','ChangeLog').'</b></td>
							  </tr>';
				}
				// crmv@109801
				$rowsAdded = 0;
				foreach($description_elements as $value){
					if ($value[3] && $this->isFieldHidden($module, $value[3])) continue;
					$uitype = $this->getFieldUitype($module, $value[3]); // crmv@128160 crmv@164655
					$previous_value = $current_value = '';
					if ($current_user->is_admin == 'on' || getFieldVisibilityPermission($module, $current_user->id, $value[3]) == '0') { // crmv@107449 crmv@108128
						if(isset($value[4]) && $value[4] == 'boolean'){
							if($value[1] == 1){
								$previous_value = $app_strings['yes'];
							}else{
								$previous_value = $app_strings['no'];
							}
							if($value[2] == 1){
								$current_value = $app_strings['yes'];
							}else{
								$current_value = $app_strings['no'];
							}
							// handle equivalent changes (NULL to 0 or '')
							if ($previous_value === $current_value) continue;
						// crmv@164655
						}elseif(isset($value[4]) && $value[4] == 'owner' || ($value[4] == 'reference' && in_array($uitype, array(50,51,52,53,54)))){
							if (is_numeric($value[2])) {
								// crmv@164655
								global $showfullusername;
								$previous_value = $value[6] ?: getOwnerName($value[1], $showfullusername);
								$current_value = $value[8] ?: getOwnerName($value[2], $showfullusername);
								if (is_admin($current_user)) {
									if ($value[5] == 'Groups') {
										$previous_value = "<a href=\"index.php?module=Settings&action=GroupDetailView&groupId={$value[1]}\">$previous_value</a>";
									} else {
										$previous_value = "<a href=\"index.php?module=Users&action=DetailView&record={$value[1]}\">$previous_value</a>";
									}
									if ($value[7] == 'Groups') {
										$current_value = "<a href=\"index.php?module=Settings&action=GroupDetailView&groupId={$value[2]}\">$current_value</a>";
									} else {
										$current_value = "<a href=\"index.php?module=Users&action=DetailView&record={$value[2]}\">$current_value</a>";
									}
								}
								// crmv@164655e
							} else {
								$previous_value = getTranslatedString($value[1], $module);
								$current_value = getTranslatedString($value[2], $module);
							}
						// crmv@164655e
						}elseif(isset($value[4]) && $value[4] == 'reference'){
							//previous value
							if($value[1] != '' && $value[1] !='0'){
								if ($value[3] == 'folderid') {
									$entityFolder = getEntityFolder($value[1]);
									if (!empty($entityFolder['foldername'])) $previous_value = $entityFolder['foldername'];
								} else {
									// crmv@164655
									$relation_previuos_module = $value[5] ?: getSalesEntityType($value[1]);
									if (isset($value[6])) {
										$previous_value = $value[6];
									} else {
										$tmp = getEntityName($relation_previuos_module,array($value[1]));
										$previous_value = $tmp[$value[1]];
									}
									// crmv@164655e
									if (!empty($previous_value)) {
										$previous_value = '<a href="index.php?module='.$relation_previuos_module.'&action=DetailView&record='.$value[1].'">'.getModuleImg($relation_previuos_module).' '.$previous_value.'</a>';
									}
								}
								if (empty($previous_value)) $previous_value = getTranslatedString('LBL_RECORD_DELETE');
							}
							//current value
							if($value[2] != '' && $value[2] !='0'){
								if ($value[3] == 'folderid') {
									$entityFolder = getEntityFolder($value[2]);
									if (!empty($entityFolder['foldername'])) $current_value = $entityFolder['foldername'];
								} else {
									// crmv@164655
									$relation_current_module = $value[7] ?: getSalesEntityType($value[2]);
									if (isset($value[8])) {
										$current_value = $value[8];
									} else {
										$tmp = getEntityName($relation_current_module,array($value[2]));
										$current_value = $tmp[$value[2]];
									}
									// crmv@164655e
									if (!empty($current_value)) {
										$current_value = '<a href="index.php?module='.$relation_current_module.'&action=DetailView&record='.$value[2].'">'.getModuleImg($relation_current_module).' '.$current_value.'</a>';
									}
								}
								if (empty($current_value)) $current_value = getTranslatedString('LBL_RECORD_DELETE');
							}
						// crmv@163361
						}elseif(isset($value[4]) && $value[4] == 'datetime'){
							$previous_value = adjustTimezone($value[1], 0, null, false);
							$current_value = adjustTimezone($value[2], 0, null, false);
						// crmv@163361e
						// crmv@185788
						} elseif(isset($value[4]) && $value[4] == 'date'){
							$previous_value = !empty($value[1]) ? getDisplayDate($value[1]) : '';
							$current_value = !empty($value[2]) ? getDisplayDate($value[2]) : '';
						// crmv@185788e
						// crmv@168681
						} elseif ($module == 'HelpDesk' && $value[3] == 'comments') {
							$previous_value = str_replace('{HERELINK}', getTranslatedString('LBL_HERE'), $value[1]);
							$current_value = str_replace('{HERELINK}', getTranslatedString('LBL_HERE'), $value[2]);
						// crmv@168681e
						// crmv@200267
						} elseif (isset($value[4]) && $value[4] == 'text') {
							$previous_value = vtlib_purify($value[1]);
							$current_value = vtlib_purify($value[2]);
						// crmv@200267e
						// crmv@205568
						} elseif (isset($value[4]) && $value[4] == 'json') {
							$previous_value_arr = Zend_Json::decode($value[1]);
							if (!empty($previous_value_arr)) {
								$previous_value_tmp = array();
								foreach($previous_value_arr as $k => $v) {
									$previous_value_tmp[] = "$k: $v";
								}
								$previous_value = implode('<br>',$previous_value_tmp);
							}
							$current_value_arr = Zend_Json::decode($value[2]);
							if (!empty($current_value_arr)) {
								$current_value_tmp = array();
								foreach($current_value_arr as $k => $v) {
									$current_value_tmp[] = "$k: $v";
								}
								$current_value = implode('<br>',$current_value_tmp);
							}
						// crmv@205568e
						} else{
							$previous_value = getTranslatedString($value[1], $module);
							$current_value = getTranslatedString($value[2], $module);
						}
					} else {
						$previous_value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
						$current_value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE')."</font>";
					}
					// crmv@107449e
					if ($nohtml) {
						$ret[] = array(
							'fieldname' => $value[0],
							'fieldname_trans' => getTranslatedString($value[0], $module),
							'previous' => $previous_value,
							'current' => $current_value,
						);
					} else {
						$html .= '<tr>
								    <td>'.getTranslatedString($value[0], $module).'</td>
								    <td>'.$previous_value.'</td>
							    	<td>'.$current_value.'</td>
							  	</tr>';
					}
					++$rowsAdded;
				}
				if (!$nohtml) $html .= '</table>';
				if ($rowsAdded == 0) $html = '';
				// crmv@109801e
			}
		}
		if ($nohtml) {
			return $ret;
		} else {
			return $html;
		}
	}
	// crmv@31780e	crmv@53684e	crmv@57348e	crmv@104566e

	// crmv@128160 crmv@164655
	function getFieldUitype($module, $fieldname) {
		global $adb, $table_prefix;
		static $cache = array();
		if (!isset($cache[$module])) {
			$list = array();
			$res = $adb->pquery("SELECT fieldname, uitype FROM {$table_prefix}_field WHERE tabid = ?", array(getTabid($module)));
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$list[$row['fieldname']] = $row['uitype'];
			}
			$cache[$module] = $list;
		}
		return $cache[$module][$fieldname];
	}
	// crmv@128160e crmv@164655e
	
	//crmv@103534 crmv@170349
	function get_revision_id($id){
		global $adb, $table_prefix;

		$last_autid_no = 1.0;
		$sql = "SELECT audit_no FROM {$this->table_name} WHERE parent_id= ? ORDER BY audit_no DESC";
		$res = $adb->limitpQuery($sql, 0, 1, array($id));
		if ($res && $adb->num_rows($res) > 0){
			$last_autid_no = (float)$adb->query_result_no_html($res,0,'audit_no') + 0.1;
		}

		return $last_autid_no;
	}
	//crmv@103534e crmv@170349e
	
	//crmv@104566
	function get_history_query($module, $record) {
		global $adb, $table_prefix, $currentModule, $onlyquery;
		
		$params = array($record);
		$query = "SELECT * FROM {$this->table_name} WHERE parent_id = ? ORDER BY {$this->table_index} DESC";
		$query_result = $adb->pquery($query, $params);
		if ($query_result && $adb->num_rows($query_result) > 0) {
			return $query_result;
		} else {
			return false;
		}
	}
	
	function get_history_log($module, $record, $query_result, $format='array') {
		global $adb, $current_user; // crmv@164655
		if (!$query_result || $adb->num_rows($query_result) == 0) return false;
		
		// crmv@177677
		if ($this->merge_updates) {
			$merges = $skipMerges = array();
			$this->calcMergeIds($query_result, $merges, $skipMerges);
			$mergeDesc = array();
		}
		// crmv@177677e
		
		$return = array();
		while($row=$adb->fetchByAssoc($query_result)) {
			$log_type = '';
			$log_text = '&nbsp;';
			
			// crmv@177677
			if ($this->merge_updates) {
				$r = $this->mergeRows($row, $mergeDesc, $merges, $skipMerges);
				if (!$r) continue;
			}
			// crmv@177677e
			
			$log_info = $this->getFieldsTable($row['description'], $module, true, $log_type); // crmv@105520
			if ($log_type == 'generic') {
				$log_text = $log_info;
			} elseif ($log_type == 'create') {
				$log_text = getTranslatedString('LBL_HAS_CREATED_THE_RECORD','ChangeLog');
			} elseif ($log_type == 'edit') {
				if (is_array($log_info) && count($log_info) == 0) continue; // crmv@109801
				$log_text = getTranslatedString('LBL_HAS_CHANGED_THE_RECORD','ChangeLog');
			} elseif ($log_type == 'remove_relation') {
				$log_text = sprintf(getTranslatedString('LBL_HAS_REMOVED_LINK_WITH_RECORD','ChangeLog'), '<a href="index.php?module='.$log_info['module1'].'&action=DetailView&record='.$log_info['record1'].'">'.$log_info['entityname1'].'</a>'); // crmv@164655
			} elseif ($log_type == 'relation') {
				$log_text = sprintf(getTranslatedString('LBL_HAS_LINKED_THE_RECORD','ChangeLog'), '<a href="index.php?module='.$log_info['module1'].'&action=DetailView&record='.$log_info['record1'].'">'.$log_info['entityname1'].'</a>'); // crmv@164655
			}
			$modDate = adjustTimezone($row['modified_date'] ?: $row['createdtime'],0, null, false); // crmv@163361
			// crmv@164655
			$link = false;
			if ($row['display_id'] > 0 && $row['display_module']) {
				if ($row['display_module'] == 'Users') {
					$displayName = $row['display_name'] ?: getUserFullName($row['user_id']);
					$displayImg = getUserAvatar($row['user_id']);
					if (is_admin($current_user)) {
						$link = "index.php?module=Users&action=DetailView&record=".$row['user_id'];
					}
				} else {
					$displayName = $row['display_name'] ?: getEntityName($row['display_module'], $row['display_id'], true);
					$displayImg = resourcever('portal_avatar.png');
					if (isPermitted($row['display_module'], 'DetailView', $row['display_id']) == 'yes') {
						$link = "index.php?module={$row['display_module']}&action=DetailView&record=".$row['display_id'];
					}
				}
			} else {
				$displayName = getUserFullName($row['user_id']);
				$displayImg = getUserAvatar($row['user_id']);
				if (is_admin($current_user)) {
					$link = "index.php?module=Users&action=DetailView&record=".$row['user_id'];
				}
			}
			// crmv@164655e
			$return[] = array(
				'crmid' => $row['changelogid'],
				'version' => $row['audit_no'],
				'user' => array(
					'id'=>$row['user_id'],
					'user_name'=>$row['user_name'], // not used
					'full_name'=>$displayName,
					'link' => $link, // crmv@164655
					'img'=>$displayImg,
				),
				// crmv@163361
				'date' => array(
					'db' => $modDate,
					'formatted' => getDisplayDate($modDate),
					'friendly' => getFriendlyDate($row['modified_date'] ?: $row['createdtime']), // this is a relative time, compare with db timezone
				),
				// crmv@163361e
				'log' => array(
					'type'=>$log_type,
					'text'=>$log_text,
					'info'=>$log_info,
					'img'=>$this->getLogImg($log_type,$log_info),
				),
			);
		}
		if ($format == 'json') {
			$return = Zend_Json::encode($return);
		}
		return $return;
	}
	
	function getLogImg($type,$info) {
		if ($type == 'generic') {
			$return = array(
				'element'=>'i',
				'class'=>'vteicon',
				'html'=>'!',
			);
		} elseif ($type == 'create') {
			$return = array(
				'element'=>'i',
				'class'=>'vteicon',
				'html'=>'add',
			);
		} elseif ($type == 'edit') {
			$return = array(
				'element'=>'i',
				'class'=>'vteicon',
				'html'=>'edit',
			);
		} elseif ($type == 'relation' || $type == 'remove_relation') {
			$module = $info['module1'];
			$return = array(
				'element'=>'i',
				'class'=>'icon-module icon-'.strtolower($module),
				'data_first_letter'=>strtoupper(substr(getTranslatedString($module,$module),0,1)),
			);
		}
		return $return;
	}
	//crmv@104566e
	
	// crmv@177677
	protected function calcMergeIds($query_result, &$merges, &$skipMerges) {
		global $adb;
		
		// decide what to merge
		$lastGroupkey = null;
		$tlist = array();
		while ($row=$adb->fetchByAssoc($query_result)) {
			// check if field update
			$chid = $row['changelogid'];
			if (empty($row['request_id'])) continue; // skip changelogs without request id
			
			$desc = Zend_Json::decode($row['description']);
			$simpleUpdate = (is_array($desc) && is_array($desc[0]));
			
			// if fields update group by record, user, type, requestid
			if ($simpleUpdate) {
				$groupkey = $row['parent_id'].'_'.$row['user_id'].'_'.'generic'.'_'.$row['request_id'];
			} else {
				$groupkey = $chid;  // always different
			}
			
			if ($groupkey != $lastGroupkey) {
				if (count($tlist) > 1) {
					$last = array_pop($tlist);
					$merges[$last] = array_reverse($tlist);
					$skipMerges = array_merge($skipMerges, $tlist);
				}
				$tlist = array();
			}
			
			if ($simpleUpdate) $tlist[] = $chid;
			$lastGroupkey = $groupkey;	
		}
		
		// check if something left
		if (count($tlist) > 1) {
			$last = array_pop($tlist);
			$merges[$last] = array_reverse($tlist);
			$skipMerges = array_merge($skipMerges, $tlist);
		}
		
		// restore pointer
		$query_result->MoveFirst();
	}
	
	protected function mergeRows(&$row, &$mergeDesc, $merges, $skipMerges) {
		$chid = $row['changelogid'];
				
		if (in_array($chid, $skipMerges)) {
			// this log should be merged with another one
			$desc = Zend_Json::decode($row['description']);
			// index  by fieldname
			$descField = array();
			foreach ($desc as $edit) {
				$descField[$edit[3]] = $edit;
			}
			$mergeDesc[$chid] = $descField;
			return false;
		} elseif (array_key_exists($chid, $merges)) {
			$desc = Zend_Json::decode($row['description']); // oldest change
			$descField = array();
			foreach ($desc as $edit) {
				$descField[$edit[3]] = $edit;
			}

			// from oldest to newest
			foreach ($merges[$chid] as $mergeid) {
				$oldDesc = $mergeDesc[$mergeid];
				
				$common = array_intersect_key($oldDesc, $descField);
				$oldDelta = array_diff_key($oldDesc, $common);
				
				// add the shared fields keeping the starting value
				$descField = array_replace($descField, $oldDelta);
				foreach ($common as $fldname => $edit) {
					$prevvalue = $descField[$fldname][1];
					$edit[1] = $prevvalue;
					$descField[$fldname] = $edit;
				}
				unset($mergeDesc[$mergeid]);
			}
			
			// remove null changes
			foreach ($descField as $fldname => $edit) {
				if ($edit[1] == $edit[2]) unset($descField[$fldname]);
			}
			
			$row['description'] = Zend_Json::encode(array_values($descField));
		}
		return true;
	}
	// crmv@177677e

}