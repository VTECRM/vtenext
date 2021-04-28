<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@57183 crmv@112297 crmv@114144 crmv@150751 */
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
require_once('modules/Conditionals/ConditionalsVersioning.php');

class Conditionals extends CRMEntity{
	
	var $permissions = array();
	
	// crmv@124729
	public $skipFields = array(
		'Calendar' => array(
			'date_start', 'time_start', 'due_date', 'time_end', 'recurringtype', 
			'duration_hours', 'duration_minutes', 'parent_id', 'notime', 'is_all_day_event',
			'reminder_time', 'contact_id', 'ical_uuid', 'recurr_idx', 'exp_duration'
		),
		'Events' => array(
			'date_start', 'time_start', 'due_date', 'time_end', 'recurringtype', 
			'duration_hours', 'duration_minutes', 'parent_id', 'notime', 'is_all_day_event',
			'reminder_time', 'contact_id', 'ical_uuid', 'recurr_idx',
		),
	);
	// crmv@124729e
	
	function __construct()	{
		global $current_language;
		$this->log = LoggerManager::getLogger('Conditionals');
	}
	
	// crmv@134058
	function existsConditionalPermissions($module, $focus, &$fields = null) {
		global $adb;
		
		// get conditionals fields from sdk view
		$sdk_fields = array();
		$cache = RCache::getInstance();
		$sdk_conditional_fields = $cache->get('sdk_conditional_fields');
		if (!empty($sdk_conditional_fields)) {
			foreach ($sdk_conditional_fields as $conditional_field) {
				$sdk_fields[] = $conditional_field;
			}
			$sdk_fields = array_values(array_unique($sdk_fields));
		}

		// check Process Conditionals
		$PMUtils = ProcessMakerUtils::getInstance();
		$processConditionals = $PMUtils->getAllConditionals($focus->id);
		if (!empty($processConditionals)) {
			$fields = array();
			if (is_array($processConditionals)) {
				foreach ($processConditionals as $conditional) {
					if ($conditional['conditions']) {
						foreach ($conditional['conditions'] as $group) {
							if ($group['conditions']) {
								foreach ($group['conditions'] as $cond) {
									if (stripos($cond['fieldname'],'ml') === 0) {
										// skip table fields (ex. ml26)
									} else {
										$fields[] = $cond['fieldname'];
									}
								}
							}
						}
					}
				}
			}
			$fields = array_merge($fields,$sdk_fields);	// add sdk view fields
			$fields = array_values(array_unique($fields));
			return true;
		}

		//crmv@118335
		// check standard rules
		$flds = $this->getConditionalFields($module, $focus);
		if (is_array($flds) && count($flds) > 0) {
			// extract fields
			foreach ($flds as $fld) {
				$fields[] = $fld['fieldname'];
			}
			$fields = array_merge($fields,$sdk_fields);	// add sdk view fields
			$fields = array_values(array_unique($fields));
			return true;
		}
		//crmv@118335e
		
		// check sdk view fields
		if (!empty($sdk_fields)) {
			$fields = $sdk_fields;
			return true;
		}
		
		return false;
	}
	// crmv@134058e
	
	//crmv@164165
	function getCurrentUserGroups($userid) {
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		if (empty($current_user_groups)) {
			$userGroupFocus = new GetUserGroups();
			$userGroupFocus->getAllUserGroups($current_user->id);
			$current_user_groups = $userGroupFocus->user_groups;
		}
		return $current_user_groups;
	}
	//crmv@164165e
	
	// crmv@172864
	/**
	 * Return a list of roles used to search for the passed user
	 */
	protected function getMatchingRoles($userid) {
	
		$UIU = UserInfoUtils::getInstance();
		$roleid = $UIU->fetchUserRole($userid);

		//costruisco le condizioni in base a ruolo, ruolo e subordinati,gruppi.
		$conditions = array();
		
		//ruolo:
		$conditions[] = "roles::".$roleid;
		
		//ruoli e subordinati:
		$subordinates=getRoleAndSubordinatesInformation($roleid);
		$parent_role=$subordinates[$roleid][1];
		if (!is_array($parent_role)){
			$parent_role = explode('::',$parent_role);
			foreach ($parent_role as $parent_role_value){
				$conditions[] = "rs::".$parent_role_value;
			}
		}
		
		//gruppi:
		$current_user_groups = $this->getCurrentUserGroups($userid); //crmv@164165
		if (is_array($current_user_groups)){
			foreach ($current_user_groups as $current_user_groups_value){
				$conditions[] = "groups::".$current_user_groups_value;
			}
		}
		
		//tutti:
		$conditions[] = 'ALL';
		
		return $conditions;
	}
	// crmv@172864e
	
	function getAllRules($module, $tabid, $column_fields) {
		global $adb, $table_prefix, $current_user;
		
		// crmv@172565
		static $resCache = array();
		$key = $module.'_'.$current_user->roleid;
		$res = $resCache[$key];
		// crmv@172565e
		
		if (!$res) {
			$conditions = $this->getMatchingRoles($current_user->id); // crmv@173271

			$vh_info = array(
				'tbl_s_conditionals' => array('tbl_s_conditionals',''),
				'tbl_s_conditionals_rules' => array('tbl_s_conditionals_rules', 'tbl_s_conditionals.ruleid = tbl_s_conditionals_rules.ruleid'),
				'field' => array($table_prefix.'_field', $table_prefix.'_field.fieldid = tbl_s_conditionals.fieldid'),
			);
			if (!empty($column_fields['record_id'])) {
				$PMUtils = ProcessMakerUtils::getInstance();
				$cvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('conditionals','id'));
				if (!empty($cvh_id)) {
					$vh_info['tbl_s_conditionals'] = array('tbl_s_conditionals_vh','tbl_s_conditionals_vh.versionid = \''.$cvh_id.'\' and ');
					$vh_info['tbl_s_conditionals_rules'] = array('tbl_s_conditionals_rules_vh', 'tbl_s_conditionals_vh.versionid = tbl_s_conditionals_rules_vh.versionid and tbl_s_conditionals_vh.ruleid = tbl_s_conditionals_rules_vh.ruleid');
					$vh_info['field'] = array($table_prefix.'_field', $table_prefix.'_field.fieldid = tbl_s_conditionals_vh.fieldid');
				}
				$tvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('tabs',$module,'id'));
				if (!empty($tvh_id)) {
					$vh_info['field'] = array($table_prefix.'_field_vh', $table_prefix.'_field_vh.versionid = \''.$tvh_id.'\' and '.$table_prefix.'_field_vh.fieldid = '.$vh_info['tbl_s_conditionals'][0].'.fieldid');
				}
			}
			$sql = "SELECT {$vh_info['tbl_s_conditionals_rules'][0]}.ruleid, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_fieldname, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_criteria_id, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_field_value
				FROM {$vh_info['tbl_s_conditionals'][0]}
				LEFT JOIN {$vh_info['tbl_s_conditionals_rules'][0]} ON {$vh_info['tbl_s_conditionals_rules'][1]}
				LEFT JOIN {$vh_info['field'][0]} ON {$vh_info['field'][1]}
				WHERE {$vh_info['tbl_s_conditionals'][1]} {$vh_info['tbl_s_conditionals'][0]}.active = 1
					and {$vh_info['field'][0]}.tabid = ?
					and {$vh_info['field'][0]}.fieldname in (".generateQuestionMarks($column_fields).")
					and {$vh_info['tbl_s_conditionals'][0]}.role_grp_check in (".generateQuestionMarks($conditions).")
				group by {$vh_info['tbl_s_conditionals_rules'][0]}.ruleid, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_fieldname, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_criteria_id, {$vh_info['tbl_s_conditionals_rules'][0]}.chk_field_value
				order by {$vh_info['tbl_s_conditionals_rules'][0]}.ruleid";
			$params[] = $tabid;
			$params[] = array_keys($column_fields);
			$params[] = $conditions;
			$res = $adb->pquery($sql,$params);
			
			$resCache[$key] = $res; // crmv@172565
		}
		$res->MoveFirst();
		return $res;
	}
	function Initialize($module='',$tabid='',&$column_fields=''){	//crmv@112297
		global $adb, $table_prefix;
		
		if ($module == '' && $tabid == '' && $column_fields == '') return;
		
		// crmv@124729
		// shitty calendar, die!!!
		if ($module == 'Events') {
			$module = 'Calendar';
			$tabid = 9;
		}
		// crmv@124729e
		
		$rule_check = false;
		$rule_success = true;
		$rules = array();
		
		// priority to Process Conditionals
		$PMUtils = ProcessMakerUtils::getInstance();
		$processConditionals = $PMUtils->getAllConditionals($column_fields['record_id']);
		if (!empty($processConditionals)) {
			$this->permissions = $PMUtils->getConditionalPermissions($processConditionals,$column_fields);
		} else {
			// get standard rules
			$conditional_permissions = array();
			$res = $this->getAllRules($module, $tabid, $column_fields);
			if ($res && $adb->num_rows($res)>0){
				//per ogni regola controllo se le condizioni sono TUTTE soddisfatte
				while ($row = $adb->fetchByAssoc($res,-1,false)){
					if ($rule_check && $rule_check != $row['ruleid']){
						if ($rule_success){
							$rules[] = $rule_check;
						}
						$rule_success = true;
					}
					$rule_check = $row['ruleid'];
					// crmv@129301
					$fvalues = $this->getDisplayValue($module, $row['chk_fieldname'], $column_fields[$row['chk_fieldname']], $row['chk_field_value'], $column_fields['record_id']);
					if (!$this->check_rule($row['chk_criteria_id'],$fvalues[0],$fvalues[1])){
						$rule_success = false;
					}
					// crmv@129301e
					
				}
				if ($rule_success){
					$rules[] = $rule_check;
				}
			}
			if (!empty($rules)){
				// crmv@124729
				// extract the fields for that stupid calendar!
				if ($module == 'Calendar') {
					$tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$module,'id'));
					if (!empty($tvh_id)) {
						$query = "SELECT fieldid, fieldname FROM {$table_prefix}_field_vh WHERE versionid = ? and presence IN (0,2) AND tabid = ?";
						$params = array($tvh_id, getTabid2('Events'));
					} else {
						$query = "SELECT fieldid, fieldname FROM {$table_prefix}_field WHERE presence IN (0,2) AND tabid = ?";
						$params = array(getTabid2('Events'));
					}
					$res = $adb->pquery($query, $params);
					$calFields = array();
					while ($row = $adb->fetchByAssoc($res, -1, false)) {
						$calFields[$row['fieldname']] = $row['fieldid'];
					}
				}
				// crmv@124729e
			
				$vh_info = array(
					'tbl_s_conditionals' => array('tbl_s_conditionals',''),
					'field' => array($table_prefix.'_field', 'tbl_s_conditionals.fieldid = '.$table_prefix.'_field.fieldid'),
				);
				if (!empty($column_fields['record_id'])) {
					$PMUtils = ProcessMakerUtils::getInstance();
					$cvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('conditionals','id'));
					if (!empty($cvh_id)) {
						$vh_info['tbl_s_conditionals'] = array('tbl_s_conditionals_vh','tbl_s_conditionals_vh.versionid = \''.$cvh_id.'\' and ');
						$vh_info['field'] = array($table_prefix.'_field', $table_prefix.'_field.fieldid = tbl_s_conditionals_vh.fieldid');
					}
					$tvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('tabs',$module,'id'));
					if (!empty($tvh_id)) {
						$vh_info['field'] = array($table_prefix.'_field_vh', $table_prefix.'_field_vh.versionid = \''.$tvh_id.'\' and '.$table_prefix.'_field_vh.fieldid = '.$vh_info['tbl_s_conditionals'][0].'.fieldid');
					}
				}
				$sql_permissions = "select {$vh_info['tbl_s_conditionals'][0]}.fieldid, {$vh_info['field'][0]}.fieldname, min(read_perm) as read_perm, min(write_perm) as write_perm, min(mandatory) as mandatory 
					from {$vh_info['tbl_s_conditionals'][0]}
					inner join {$vh_info['field'][0]} on {$vh_info['field'][1]}
					where {$vh_info['tbl_s_conditionals'][1]} ruleid in (".generateQuestionMarks($rules).") ";
					
				// crmv@195054
                if ($adb->isMssql()) {
					$sql_permissions .= "group by {$vh_info['tbl_s_conditionals'][0]}.fieldid, {$vh_info['field'][0]}.fieldname"; 
				} else {
					$sql_permissions .= "group by fieldid";
				}
                // crmv@195054e
                
				$res_permissions = $adb->pquery($sql_permissions,$rules);
				if ($res_permissions && $adb->num_rows($res_permissions)>0){
					$i = 0;
					while ($row_permissions = $adb->fetchByAssoc($res_permissions,-1,false)){
						$this->setFieldConditionalPermissions($row_permissions, $i, $row_permissions['fieldname'], $conditional_permissions);
						$this->permissions[$row_permissions['fieldid']] = Array(
							'f2fp_visible'=>$row_permissions['read_perm'],
							'f2fp_editable'=>$row_permissions['write_perm'],
							'f2fp_mandatory'=>$row_permissions['mandatory'],
						);
						// crmv@124729
						if ($module == 'Calendar' && array_key_exists($row_permissions['fieldname'], $calFields)) {
							$evId = $calFields[$row_permissions['fieldname']];
							$this->permissions[$evId] = $this->permissions[$row_permissions['fieldid']];
						}
						// crmv@124729e
					}
				}
			}
			// set in request cache
			$cache = RCache::getInstance();
			$cache->set('conditional_permissions', $conditional_permissions);
		}
	}
	
	// crmv@129301
	// initial uitype support
	// TODO: don't duplicate code, use all the code from edit/detail/list utils...
	function getDisplayValue($module, $fieldname, $value, $checkValue, $record) {
		static $uitypeCache = array();
		if (!isset($uitypeCache[$module][$fieldname])) {
			global $adb, $table_prefix;
			$PMUtils = ProcessMakerUtils::getInstance();
			$tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$module,'id'));
			if (!empty($tvh_id)) {
				$query = "select uitype from {$table_prefix}_field_vh where versionid = ? and tabid = ? and fieldname = ?";
				$params = array($tvh_id, getTabid2($module), $fieldname);
			} else {
				$query = "select uitype from {$table_prefix}_field where tabid = ? and fieldname = ?";				
				$params = array(getTabid2($module), $fieldname);
			}
			$result = $adb->pquery($query, $params);
			if ($result && $adb->num_rows($result) > 0) {
				$uitypeCache[$module][$fieldname] = $adb->query_result($result,0,'uitype');
			}
		}
		$uitype = $uitypeCache[$module][$fieldname];
		if ($uitype == 10) {
			if(!empty($value)) {
				$parent_module = getSalesEntityType($value);
				$displayValueArray = getEntityName($parent_module, $value);
				if(!empty($displayValueArray)){
					foreach($displayValueArray as $key=>$value){
						$displayValue = $value;
					}
				}
			}
			$moduleFieldValue = $displayValue;
			$chk_field_value = $checkValue;
		} else {
			// original conditional code... terrible, really!
			$moduleFieldValue = getTranslatedString($value,$module);
			$chk_field_value = getTranslatedString($checkValue,$module);
		}
		
		return array($moduleFieldValue, $chk_field_value);
	}
	// crmv@129301e
	
	function setFieldConditionalPermissions($perm, $i, $fieldname, &$permissions) {
		//if ($perm['FpovManaged'] == 1) {
			if ($i == 0) {
				$permissions[$fieldname]['readonly'] = 1;
				$permissions[$fieldname]['mandatory'] = false;
			}
			if ($perm['read_perm'] == 1) {
				if ($perm['write_perm'] == 1) {
					$readonly = 1;
					if ($perm['mandatory'] == 1) {
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
			// TODO if ($perm['FpovValueActive'] == 1) $permissions[$fieldname]['value'] = $perm['FpovValueStr'];
			//crmv@103826e
		//}
	}
	function check_rule($criteriaID,$moduleFieldValue,$criteriaFieldValue){
		$criteriaPassed = false;
		switch ($criteriaID){
			case 0:
				// <=
				$criteriaPassed = ($moduleFieldValue <= $criteriaFieldValue);
				break;
			case 1:
				// <
				$criteriaPassed = ($moduleFieldValue < $criteriaFieldValue);
				break;
			case 2:
				// >=
				$criteriaPassed = ($moduleFieldValue >= $criteriaFieldValue);
				break;
			case 3:
				// >
				$criteriaPassed = ($moduleFieldValue > $criteriaFieldValue);
				break;
			case 4:
				// ==
				$criteriaPassed = ($moduleFieldValue == $criteriaFieldValue);
				break;
			case 5:
				// !=
				$criteriaPassed = ($moduleFieldValue != $criteriaFieldValue);
				break;
			case 6:
				// includes
				$criteriaPassed = (stristr($moduleFieldValue, $criteriaFieldValue) !== false);
				break;
		}
		return $criteriaPassed;
	}	
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
 					
		require_once('include/utils/utils.php');			
		global $adb,$mod_strings,$table_prefix;
 		
 		if($eventType == 'module.postinstall') {			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			
			
			$blockid = getSettingsBlockId('LBL_STUDIO');
			$fieldid = $adb->getUniqueID($table_prefix.'_settings_field');
			
			// changed, to put it after the workflows
			$sequence = 20;
			$seq_res = $adb->pquery("SELECT sequence FROM {$table_prefix}_settings_field WHERE blockid = ? AND name = ?", array($blockid, 'LBL_LIST_WORKFLOWS'));
			if ($adb->num_rows($seq_res) > 0) {
				$cur_seq = intval($adb->query_result_no_html($seq_res, 0, 'sequence'));
				// shift all the following ones
				$adb->pquery("UPDATE {$table_prefix}_settings_field SET sequence = sequence + 1 WHERE blockid = ? AND sequence > ?", array($blockid, $cur_seq));
				$sequence = $cur_seq+1;
			}

			$adb->pquery('INSERT INTO '.$table_prefix.'_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence) 
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'LBL_COND_MANAGER', 'workflow.gif', 'LBL_COND_MANAGER_DESCRIPTION', 'index.php?module=Conditionals&action=index&parenttab=Settings', $sequence));
					
			
		} else if($eventType == 'module.disabled') {
		// TODO Handle actions when this module is disabled.
		} else if($eventType == 'module.enabled') {
		// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
		// TODO Handle actions after this module is updated.
		}
 	}
 	function getTransitionConditionalWorkflowModulesList() {
 		global $adb;
 		foreach (com_vtGetModules($adb) as $key=>$value){
 			$modules_list[] = Array($key,$key);
 		}
 		return $modules_list;
 	}
 	function we_checkUserRoleGrp($userobj,$roleGrpCheck) {
 		if($roleGrpCheck == 'ALL') return true;
 		$conditions = explode("::",$roleGrpCheck);
 		switch($conditions[0]) {
 			case 'roles':
 				return ($userobj->roleid == $conditions[1]);
 				break;
 			case 'rs':
 				//crmv@18354
 				$subordinates=getRoleAndSubordinatesInformation($userobj->roleid);
 				$parent_role=$subordinates[$userobj->roleid][1];
 				$parent_rol_arr=explode('::',$parent_role);
 				if(in_array($conditions[1],$parent_rol_arr)) return true;
 				//crmv@18354e
 				break;
 			case 'groups':
 				$current_user_groups = $this->getCurrentUserGroups($userobj->id); //crmv@164165
 				if(sizeof($current_user_groups) > 0)
 				{
 					foreach ($current_user_groups as $grpid)
 					{
 						if($grpid == $conditions[1]) return true;
 					}
 				}
 				return false;
 				break;
 			default:
 				//@todo - gestione errori
 				return true;
 		}
 		return true;
 	}

 	function we_checkCriteria($criteriaID,$moduleFieldValue,$criteriaFieldValue,$roleGrpCheck="ALL") {
 		global $current_user;
 		$criteriaPassed = false;

 		switch ($criteriaID)
 		{
 			case 0:
 				// <=
 				$criteriaPassed = ($moduleFieldValue <= $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 1:
 				// <
 				$criteriaPassed = ($moduleFieldValue < $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 2:
 				// >=
 				$criteriaPassed = ($moduleFieldValue >= $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 3:
 				// >
 				$criteriaPassed = ($moduleFieldValue > $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 4:
 				// ==
 				$criteriaPassed = ($moduleFieldValue == $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 5:
 				// !=
 				$criteriaPassed = ($moduleFieldValue != $criteriaFieldValue) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 			case 6:
 				// includes
 				$criteriaPassed = (stristr($moduleFieldValue, $criteriaFieldValue) !== false) && $this->we_checkUserRoleGrp($current_user,$roleGrpCheck);
 				break;
 		}
 		return $criteriaPassed;
 	}

 	function _wui_check_rules($result,$fieldid,$module,$column_fields) {
 		global $adb;

 		if($result && $adb->num_rows($result)>0) {
 			$num_rows = $adb->num_rows($result);
 			for ($k = 0; $k < $num_rows; $k++) {
 				$chk_fieldname = $adb->query_result($result, $k, 'chk_fieldname');
 				$chk_criteria_id = $adb->query_result($result, $k, 'chk_criteria_id');
 				$chk_field_value = $adb->query_result($result, $k, 'chk_field_value');
 				$chk_role_grp = $adb->query_result($result, $k, 'role_grp_check');
 				if(array_key_exists($chk_fieldname,$column_fields)) {
 					$moduleFieldValue = $column_fields["$chk_fieldname"];
 					//crmv@9960
 					$moduleFieldValue = getTranslatedString($moduleFieldValue);
 					$chk_field_value = getTranslatedString($chk_field_value);
 					//crmv@9960e
 					if($this->we_checkCriteria($chk_criteria_id,$moduleFieldValue,$chk_field_value,$chk_role_grp)) {
 						//					if ($fieldid == '804')
 						//						echo "CONTINUO IL CICLO: $chk_fieldname $chk_criteria_id $chk_field_value<br />";
 						continue;
 					}
 					else {
 						//					if ($fieldid == '804')
 						//						echo "ESCO: $chk_fieldname $chk_criteria_id $chk_field_value<br />";
 						return null;
 					}
 				}
 			}
 			$read_perm  = $adb->query_result($result, 0, 'read_perm');
 			$write_perm = $adb->query_result($result, 0, 'write_perm');
 			$mandatory_perm = $adb->query_result($result, 0, 'mandatory');
 			if($write_perm == 1) $read_perm = 1;

 			//		if ($fieldid == '825')
 			//			print_r(Array('f2fp_visible'=>$read_perm,'f2fp_editable'=>$write_perm,'f2fp_mandatory'=>$mandatory_perm));

 			return Array('f2fp_visible'=>$read_perm,'f2fp_editable'=>$write_perm,'f2fp_mandatory'=>$mandatory_perm);
 		}
 		return null; // no rules defined - calles need to check null value
 	}

 	function wui_get_FieldPermissionsOnFieldValue($fieldid,$module,$column_fields) {
 		global $adb,$current_user,$table_prefix;
 		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
 		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

 		$vh_info = array(
 			'tbl_s_conditionals' => array('tbl_s_conditionals',''),
 			'tbl_s_conditionals_rules' => array('tbl_s_conditionals_rules','tbl_s_conditionals.ruleid = tbl_s_conditionals_rules.ruleid'),
 			'field' => array($table_prefix.'_field', $table_prefix.'_field.fieldid = tbl_s_conditionals.fieldid'),
 		);
 		if (!empty($column_fields['record_id'])) {
 			$PMUtils = ProcessMakerUtils::getInstance();
 			$cvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('conditionals','id'));
 			if (!empty($cvh_id)) {
 				$vh_info['tbl_s_conditionals'] = array('tbl_s_conditionals_vh','tbl_s_conditionals_vh.versionid = \''.$cvh_id.'\' and ');
 				$vh_info['tbl_s_conditionals_rules'] = array('tbl_s_conditionals_rules_vh','tbl_s_conditionals_vh.versionid = tbl_s_conditionals_rules_vh.versionid and tbl_s_conditionals_vh.ruleid = tbl_s_conditionals_rules_vh.ruleid');
 				$vh_info['field'] = array($table_prefix.'_field', $table_prefix.'_field.fieldid = tbl_s_conditionals_vh.fieldid');
 			}
 			$tvh_id = $PMUtils->getSystemVersion4Record($column_fields['record_id'],array('tabs',$module,'id'));
 			if (!empty($tvh_id)) {
 				$vh_info['field'] = array($table_prefix.'_field_vh', $table_prefix.'_field_vh.versionid = \''.$tvh_id.'\' and '.$table_prefix.'_field_vh.fieldid = '.$vh_info['tbl_s_conditionals'][0].'.fieldid');
 			}
 		}
 		$q = "SELECT DISTINCT ruleid
			FROM {$vh_info['tbl_s_conditionals'][0]}
			INNER JOIN {$vh_info['field'][0]} ON {$vh_info['field'][1]}
			INNER JOIN ".$table_prefix."_tab ON ".$table_prefix."_field.tabid = ".$table_prefix."_tab.tabid
			WHERE {$vh_info['tbl_s_conditionals'][1]} ".$table_prefix."_tab.name = '$module'";
 		$res = $adb->query($q);
 		$rules_returned = array();
 		if ($res && $adb->num_rows($res) > 0)
 		while($row=$adb->fetchByAssoc($res,-1,false)){
 			$fix_query = "SELECT chk_fieldname,chk_criteria_id,chk_field_value,read_perm,write_perm,mandatory,role_grp_check
	    				FROM {$vh_info['tbl_s_conditionals'][0]}
	    				LEFT JOIN {$vh_info['tbl_s_conditionals_rules'][0]} ON {$vh_info['tbl_s_conditionals_rules'][1]}
	    				WHERE {$vh_info['tbl_s_conditionals'][1]} {$vh_info['tbl_s_conditionals'][0]}.ruleid = ".$row['ruleid'];

 			// check rules fro roleandsubordinates
 			$parnet_role_array = explode("::",$current_user_parent_role_seq);
 			for($r=0;$r<count($parnet_role_array);$r++) {
 				$query = $fix_query." and active = 1 and fieldid = $fieldid and role_grp_check = 'rs::".$parnet_role_array[$r]."' order by sequence asc";
 				$result = $adb->query($query);
 				$rules = $this->_wui_check_rules($result,$fieldid,$module,$column_fields);
 				if($rules != null) break;
 			}
 			if($rules != null) {
 				if ($rules['f2fp_visible'] == 1)
 				$rules_returned['f2fp_visible'] = 1;
 				elseif ($rules_returned['f2fp_visible'] != 1)
 				$rules_returned['f2fp_visible'] = 0;

 				if ($rules['f2fp_editable'] == 1)
 				$rules_returned['f2fp_editable'] = 1;
 				elseif ($rules_returned['f2fp_editable'] != 1)
 				$rules_returned['f2fp_editable'] = 0;

 				if ($rules['f2fp_mandatory'] == 1)
 				$rules_returned['f2fp_mandatory'] = 1;
 				elseif ($rules_returned['f2fp_mandatory'] != 1)
 				$rules_returned['f2fp_mandatory'] = 0;
 			}

 			// no rules then check for role
 			$query = $fix_query." and active = 1 and fieldid = $fieldid and role_grp_check = 'roles::".$current_user->roleid."' order by sequence asc";
 			$result = $adb->query($query);
 			$rules = $this->_wui_check_rules($result,$fieldid,$module,$column_fields);
 			if($rules != null) {
 				if ($rules['f2fp_visible'] == 1)
 				$rules_returned['f2fp_visible'] = 1;
 				elseif ($rules_returned['f2fp_visible'] != 1)
 				$rules_returned['f2fp_visible'] = 0;

 				if ($rules['f2fp_editable'] == 1)
 				$rules_returned['f2fp_editable'] = 1;
 				elseif ($rules_returned['f2fp_editable'] != 1)
 				$rules_returned['f2fp_editable'] = 0;

 				if ($rules['f2fp_mandatory'] == 1)
 				$rules_returned['f2fp_mandatory'] = 1;
 				elseif ($rules_returned['f2fp_mandatory'] != 1)
 				$rules_returned['f2fp_mandatory'] = 0;
 			}
 			// no rules then check for groups
 			$user_groups = new GetUserGroups();
 			$user_groups->getAllUserGroups($current_user->id);
 			for($g=0;$g<count($user_groups->user_groups);$g++) {
 				$query = $fix_query." and active = 1 and fieldid = $fieldid and role_grp_check = 'groups::". $user_groups->user_groups[$g]."' order by sequence asc";
 				$result = $adb->query($query);
 				$rules = $this->_wui_check_rules($result,$fieldid,$module,$column_fields);
 				if($rules != null) break;
 			}
 			if($rules != null) {
 				if ($rules['f2fp_visible'] == 1)
 				$rules_returned['f2fp_visible'] = 1;
 				elseif ($rules_returned['f2fp_visible'] != 1)
 				$rules_returned['f2fp_visible'] = 0;

 				if ($rules['f2fp_editable'] == 1)
 				$rules_returned['f2fp_editable'] = 1;
 				elseif ($rules_returned['f2fp_editable'] != 1)
 				$rules_returned['f2fp_editable'] = 0;

 				if ($rules['f2fp_mandatory'] == 1)
 				$rules_returned['f2fp_mandatory'] = 1;
 				elseif ($rules_returned['f2fp_mandatory'] != 1)
 				$rules_returned['f2fp_mandatory'] = 0;
 			}
 			// no rules -> check rules for all ------------------------------------------------------------------------------------------
 			if($rules == null) {
 				$query = $fix_query." and active = 1 and fieldid = $fieldid and role_grp_check = 'ALL' order by sequence asc";
 				$result = $adb->query($query);
 				$rules = $this->_wui_check_rules($result,$fieldid,$module,$column_fields);
 			}
 			if($rules != null) {
 				if ($rules['f2fp_visible'] == 1)
 				$rules_returned['f2fp_visible'] = 1;
 				elseif ($rules_returned['f2fp_visible'] != 1)
 				$rules_returned['f2fp_visible'] = 0;

 				if ($rules['f2fp_editable'] == 1)
 				$rules_returned['f2fp_editable'] = 1;
 				elseif ($rules_returned['f2fp_editable'] != 1)
 				$rules_returned['f2fp_editable'] = 0;

 				if ($rules['f2fp_mandatory'] == 1)
 				$rules_returned['f2fp_mandatory'] = 1;
 				elseif ($rules_returned['f2fp_mandatory'] != 1)
 				$rules_returned['f2fp_mandatory'] = 0;
 			}
 		}
 		return $rules_returned;
 	}

 	//------------------------------------------------------------------
 	function wui_getFpofvListViewHeader() {
 		global $currentModule;
 		$header = Array("","LBL_FPOFV_RULE_NAME","LBL_MODULE","","","","","","","","FpofvChkRoleGroup","LBL_ACTION");
 		for($i=0;$i<count($header);$i++) {
 			$header[$i] = getTranslatedString($header[$i],$currentModule);
 		}
 		return $header;
 	}

 	//------------------------------------------------------------------
	function wui_getFpofvListViewEntries($fields_columnnames) {
 		global $adb,$mod_strings,$app_strings,$table_prefix;

 		$roleDetails=getAllRoleDetails();
 		unset($roleDetails['H1']);
 		$grpDetails=getAllGroupName();
 		
 		
 		// crmv@77249
 		$wherecond = "";
 		if (!empty($_REQUEST['formodule'])) {
			$tabid = intval(getTabid($_REQUEST['formodule']));
			$wherecond = " WHERE ".$table_prefix."_tab.tabid= '$tabid'";
 		}
 		
 		$query = "select
			    distinct
				ruleid, 
				name,
				description,
				role_grp_check 
				from tbl_s_conditionals 
				inner join ".$table_prefix."_field on ".$table_prefix."_field.fieldid = tbl_s_conditionals.fieldid
				inner join ".$table_prefix."_tab on ".$table_prefix."_field.tabid = ".$table_prefix."_tab.tabid
				$wherecond
				group by ruleid, name, description, role_grp_check 
				order by description";
		// crmv@77249e

 		$result = $adb->query($query);
 		$ret_val = Array();
 		if($result && $adb->num_rows($result)>0) {
 			$num_rows = $adb->num_rows($result);
 			for ($k = 0; $k < $num_rows; $k++) {
 					
 				$ret_val[$k][1] = $adb->query_result($result, $k, 'description');
 				
 				// crmv@126317
 				$module = $adb->query_result($result, $k, 'name');
 				$ret_val[$k][2] = getTranslatedString($module, $module);
 				// crmv@126317e
 					
 				$role_grp_check = $adb->query_result($result, $k, 'role_grp_check');
 				if($role_grp_check == "ALL")
 				$role_grp_string = $mod_strings['NO_CONDITIONS'];
 				$rolefound = false;
 				foreach($roleDetails as $roleid=>$rolename)
 				{
 					if('roles::'.$roleid == $role_grp_check) {
 						$role_grp_string = $mod_strings['LBL_ROLES']."::".$rolename[0];
 						$rolefound = true;
 						break;
 					}
 				}
 				if(!$rolefound)
 				foreach($roleDetails as $roleid=>$rolename)
 				{
 					if('rs::'.$roleid == $role_grp_check) {
 						$role_grp_string = $mod_strings['LBL_ROLES_SUBORDINATES']."::".$rolename[0];
 						$rolefound = true;
 						break;
 					}
 				}
 				if(!$rolefound)
 				foreach($grpDetails as $groupid=>$groupname)
 				{
 					if('groups::'.$groupid == $role_grp_check) {
 						$role_grp_string = $mod_strings['LBL_GROUP']."::".$groupname;
 						$rolefound = true;
 						break;
 					}
 				}

 				$ret_val[$k][12] = $role_grp_string;
 					
 				$ruleid = $adb->query_result($result, $k, 'ruleid');
 				
 				// crmv@77249
				if ($_REQUEST['included'] == true) {
					$params = array(
						'included' => 'true',
						'skip_vte_header' => 'true',
						'skip_footer' => 'true',
						'formodule' => $_REQUEST['formodule']
					);
					$otherParams = "&".http_build_query($params);
				}
				// crmv@77249e
 				
 				$edit_lnk = "<a href='index.php?module=Conditionals&action=EditView&ruleid=$ruleid&parenttab=Settings$otherParams'>".$app_strings['LNK_EDIT']."</a>";
 				$del_lnk = "<a href='index.php?module=Conditionals&action=Delete&ruleid=$ruleid&parenttab=Settings$otherParams'>".$app_strings['LNK_DELETE']."</a>";
 				$ret_val[$k][13] = $edit_lnk."&nbsp;|&nbsp;".$del_lnk;
 			}
 		}
 		return $ret_val;
 	}

 	function getRulesInfo($ruleid) {
 		global $adb,$table_prefix;
 		$info = array();

 		$res = $adb->query("SELECT
						tbl_s_conditionals.*,
						name as tablabel
						FROM tbl_s_conditionals 
						INNER JOIN ".$table_prefix."_field ON ".$table_prefix."_field.fieldid = tbl_s_conditionals.fieldid
						INNER JOIN ".$table_prefix."_tab ON ".$table_prefix."_field.tabid = ".$table_prefix."_tab.tabid
 		where ruleid = $ruleid");
 		$info = $adb->fetchByAssoc($res,-1,false);

 		$res = $adb->query("select chk_fieldname,chk_criteria_id,chk_field_value
 		from tbl_s_conditionals_rules
 		where ruleid = $ruleid");
 		while($row=$adb->fetchByAssoc($res,-1,false)) {
 			$info['rules'][] = $row;
 		}
 		return $info;
 	}

 	function wui_getFpofvData($ruleid='',$module) {
 		global $adb,$mod_strings,$table_prefix;

 		if ($ruleid == '') $ruleid = 0;
 		$tabid = getTabid($module);
 		
 		// crmv@124729
 		$params = array();
 		$excludeSql = '';
 		if (!empty($this->skipFields[$module])) {
			$params = array_merge($params, $this->skipFields[$module]);
			$excludeSql = " AND {$table_prefix}_field.fieldname NOT IN (".generateQuestionMarks($this->skipFields[$module]).")";
 		}
 		$query = "select
				tbl2.fieldid, 
				tbl2.fieldname, 
				tbl2.name as module, 
				tbl2.fieldlabel, 
				tbl2.uitype,
				 ".$adb->database->IfNull('read_perm',0)." as read_perm, 
				 ".$adb->database->IfNull('write_perm',0)." as write_perm, 
				 ".$adb->database->IfNull('mandatory',0)." as mandatory, 
				tbl2.sequence,  
				1 as active, 
				 ".$adb->database->IfNull('managed',0)." as managed,
				".$table_prefix."_blocks.blocklabel as blocklabel
				
				 from (
					select 
					tbl_s_conditionals.* , 
					".$table_prefix."_tab.name, 
					".$table_prefix."_field.fieldlabel, 
					".$table_prefix."_field.fieldname, 
					1 as managed 
					from tbl_s_conditionals
						inner join ".$table_prefix."_field on tbl_s_conditionals.fieldid = ".$table_prefix."_field.fieldid
						inner join ".$table_prefix."_tab on ".$table_prefix."_field .tabid = ".$table_prefix."_tab.tabid 
 		where
 		ruleid = $ruleid
 		) tbl1
 		right outer join (
			select ".$table_prefix."_field.*,  ".$table_prefix."_tab.name 
			from ".$table_prefix."_field 
			inner join ".$table_prefix."_tab on ".$table_prefix."_field .tabid = ".$table_prefix."_tab.tabid
			where ".$table_prefix."_field.tabid = $tabid $excludeSql
 		) tbl2 on tbl1.fieldid = tbl2.fieldid
 		inner join ".$table_prefix."_blocks on tbl2.block = ".$table_prefix."_blocks.blockid
				order by ".$table_prefix."_blocks.sequence, tbl2.sequence";

 		$result = $adb->pquery($query, $params);
 		// crmv@124729e
 		
 		$ret_val = Array();
 		if($result) {
 			for($i=0;$i<$adb->num_rows($result);$i++) {
 				//crmv@115268
 				$uitype = $adb->query_result($result,$i,'uitype');
				$HideFpovValue = false;
				$HideFpovManaged = false;
 				$HideFpovReadPermission = false;
 				$HideFpovWritePermission = false;
				$HideFpovMandatoryPermission = false;
 				if ($uitype == 220) {
 					$HideFpovValue = true;
 					// crmv@190916 removed code
 				}
 				$ret_val[] = array(
 					'FpofvFieldid' => $adb->query_result($result, $i, 'fieldid'),
 					'ModuleField' => $adb->query_result($result, $i, 'chk_fieldname'),
 					'Module' => $adb->query_result($result, $i, 'module'),
 					'FpovReadPermission' => $adb->query_result($result, $i, 'read_perm'),
 					'FpovWritePermission' => $adb->query_result($result, $i, 'write_perm'),
 					'FpovManaged' => $adb->query_result($result, $i, 'managed'),
 					'FpovMandatoryPermission' => $adb->query_result($result, $i, 'mandatory'),
 					'FpofvSequence' => $adb->query_result($result, $i, 'sequence'),
 					'FpofvActive' => $adb->query_result($result, $i, 'active'),
 					'FpofvBlockLabel' => $adb->query_result($result, $i, 'blocklabel'),
 					'FpofvChkFieldLabel' => $adb->query_result($result, $i, 'fieldlabel'),
 					'FpofvChkFieldName' => $adb->query_result($result, $i, 'fieldname'),
 					'uitype' => $uitype, //crmv@112297 in future add here the columns of the table here
					'HideFpovValue'=>$HideFpovValue,
					'HideFpovManaged'=>$HideFpovManaged,
					'HideFpovReadPermission'=>$HideFpovReadPermission,
					'HideFpovWritePermission'=>$HideFpovWritePermission,
					'HideFpovMandatoryPermission'=>$HideFpovMandatoryPermission,
 				);
 				//crmv@115268e
 			}
 			return $ret_val;
 		}
 		return null;
 	}

 	function wui_getCriteriaLabel($criteriaID) {
 		global $mod_strings;
 		switch ($criteriaID)
 		{
 			case 0:
 				return $mod_strings['LBL_CRITERIA_VALUE_LESS_EQUAL'];
 				// <=
 				break;
 			case 1:
 				// <
 				return $mod_strings['LBL_CRITERIA_VALUE_LESS_THAN'];
 				break;
 			case 2:
 				// >=
 				return $mod_strings['LBL_CRITERIA_VALUE_MORE_EQUAL'];
 				break;
 			case 3:
 				// >
 				return $mod_strings['LBL_CRITERIA_VALUE_MORE_THAN'];
 				break;
 			case 4:
 				// ==
 				return $mod_strings['LBL_CRITERIA_VALUE_EQUAL'];
 				break;
 			case 5:
 				// !=
 				return $mod_strings['LBL_CRITERIA_VALUE_NOT_EQUAL'];
 				break;
 			case 6:
 				// includes
 				return $mod_strings['LBL_CRITERIA_VALUE_INCLUDES'];
 				break;
 		}
 		return $criteriaID;
 	}

 	//------------------------------------------------------------------------------------------------
	//crmv@101719
 	// TODO add versioning support
	function getStatusBlockRules($module='',$column_fields=''){
		global $current_language, $adb, $current_user, $table_prefix;

		if ($module == '' && $column_fields == '' ) return;
		
		$rules=array();
		$tabid=getTabid($module);

		$conditions = $this->getMatchingRoles($current_user->id); // crmv@173271
		
		$sql = "SELECT tbl_s_conditionals_rules.ruleid,
			tbl_s_conditionals_rules.chk_fieldname,
			tbl_s_conditionals_rules.chk_criteria_id,
			tbl_s_conditionals_rules.chk_field_value
			FROM tbl_s_conditionals 
			LEFT JOIN tbl_s_conditionals_rules ON tbl_s_conditionals.ruleid = tbl_s_conditionals_rules.ruleid 
			left join ".$table_prefix."_field ON (".$table_prefix."_field.fieldid = tbl_s_conditionals.fieldid OR tbl_s_conditionals_rules.chk_fieldname = ".$table_prefix."_field.fieldname)
			WHERE tbl_s_conditionals.active = 1 
			and ".$table_prefix."_field.tabid = ?
			and ".$table_prefix."_field.fieldname in (".generateQuestionMarks($column_fields).")
			and tbl_s_conditionals.role_grp_check in (".generateQuestionMarks($conditions).")
			group by tbl_s_conditionals_rules.ruleid,
			tbl_s_conditionals_rules.chk_fieldname,
			tbl_s_conditionals_rules.chk_criteria_id,
			tbl_s_conditionals_rules.chk_field_value order by tbl_s_conditionals_rules.ruleid";
		$params[] = $tabid;
		$params[] = array_keys($column_fields);
		$params[] = $conditions;
		$res = $adb->pquery($sql,$params);
		$rule_check = false;
		$rule_success = true;
		if ($res && $adb->num_rows($res)>0){
			//per ogni regola controllo se le condizioni sono TUTTE soddisfatte
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				if ($rule_check && $rule_check != $row['ruleid']){
					if ($rule_success){
						$rules[] = $rule_check;
					}
					$rule_success = true;
				}
				$rule_check = $row['ruleid'];
				$moduleFieldValue = getTranslatedString($column_fields[$row['chk_fieldname']],$module);
				$chk_field_value = getTranslatedString($row['chk_field_value'],$module);
				if (!$this->check_rule($row['chk_criteria_id'],$moduleFieldValue,$chk_field_value)){
					$rule_success = false;
				}
			}
			if ($rule_success){
				$rules[] = $rule_check;
			}
		}
		
		return $rules;
	}
	//crmv@101719e
	
	// TODO add versioning support
 	function wui_sql_restric_status_on_mandatory_fields($vteobj,$module,$fieldname,$status,$rule2check=array()) { //crmv@101719
 		global $adb,$table_prefix;
 		$ret_val[0] = false;
 		$tabid = getTabid($module);
 		$status = getTranslatedString($status,$module);	//crmv@9960		//crmv@17935
		$params = array();
 		$query = "SELECT ".$table_prefix."_field.fieldname AS module_fieldname,
			  ".$table_prefix."_field.fieldlabel     AS module_fieldlabel,
			  ".$table_prefix."_field.uitype         AS field_uitype,
			  ".$table_prefix."_field.typeofdata     AS field_typeofdata,
			  tbl_s_conditionals_rules.*
			FROM tbl_s_conditionals
			INNER JOIN ".$table_prefix."_field ON ".$table_prefix."_field.fieldid = tbl_s_conditionals.fieldid
			INNER JOIN tbl_s_conditionals_rules on tbl_s_conditionals_rules.ruleid = tbl_s_conditionals.ruleid
			WHERE chk_fieldname = '".$fieldname."' and chk_field_value = '".$status."' and mandatory = 1";
		
		//crmv@101719
		if(!empty($rule2check)){
			$query .= " and tbl_s_conditionals.ruleid in (".generateQuestionMarks($rule2check).")";
			array_push($params,$rule2check);
		}
		//crmv@101719e
 		//@todo - vincolare la query al profilo
 		$index = 1;
 		$result = $adb->pquery($query,$params); //crmv@101719
 		if($result && $adb->num_rows($result)>0) {
 			$num_rows = $adb->num_rows($result);
 			for ($k = 0; $k < $num_rows; $k++) {
 				$module_fieldname = $adb->query_result($result, $k, 'module_fieldname');
 				$module_fieldlabel = $adb->query_result($result, $k, 'module_fieldlabel');
 				$chk_fieldname = $adb->query_result($result, $k, 'chk_fieldname');
 				$chk_criteria_id = $adb->query_result($result, $k, 'chk_criteria_id');
 				$chk_field_value = $adb->query_result($result, $k, 'chk_field_value');
 				$chk_role_grp = $adb->query_result($result, $k, 'role_grp_check');
 				$field_uitype = $adb->query_result($result, $k, 'field_uitype');
 				$field_typeofdata = $adb->query_result($result, $k, 'field_typeofdata');
 				if(array_key_exists($chk_fieldname,$vteobj->column_fields)) {
 					$moduleFieldValue = $vteobj->column_fields["$chk_fieldname"];
 					//crmv@9960		//crmv@17935
 					$moduleFieldValue = getTranslatedString($moduleFieldValue,$module);
 					$chk_field_value = getTranslatedString($chk_field_value,$module);
 					//crmv@9960e	//crmv@17935e
 					if($this->we_checkCriteria($chk_criteria_id,$moduleFieldValue,$chk_field_value,$chk_role_grp)) {
 						if ($this->check_value_field($vteobj->column_fields[$module_fieldname],$field_typeofdata,$field_uitype)) {}	//crmv@17935
 						else {
 							$ret_val[0] = true;
 							$ret_val[$index] = Array($module_fieldname,$module_fieldlabel);
 							$index++;
 						}
 					}
 				}
 			}
 		}
 		return $ret_val;
 	}

 	function check_value_field($value,$typeofdata,$uitype){
 		$type_arr = explode("~",$typeofdata);
 		$typeofdata = $type_arr[0];
 		if (in_array($uitype,Array(10,53))){
 			if ($value == '0'){
 				$value = '';
 			}
 		}
 		//crmv@17935
 		if (in_array($typeofdata,Array('N','I')))
 		if (ceil($value) == 0) return false;
 		if (in_array($uitype,Array(15,16,111)))
 		if (in_array(trim($value),array('--Nessuno--','--None--','--nd--'))) return false;
 		if ($value == '')
 		return false;
 		//crmv@17935e
 		return true;
 	}

 	//performance_conditiona_listview - i
 	function wui_get_FieldPermissionsOnFieldValueFields($module,$column_fields,$conditional_fieldid) {
 		$rules = Array();
 		foreach($conditional_fieldid as $fieldid) {
 			$rules[$fieldid] = $this->wui_get_FieldPermissionsOnFieldValue($fieldid,$module,$column_fields);
 		}
 		return $rules;
 	}

 	/**
 	 * Get the fields used as conditions for the passed module
 	 */
	//crmv@18039 crmv@118335
 	function getConditionalFields($module, $focus='') {
 		global $adb,$table_prefix;
 		static $cache = array();
 		if (!empty($focus->id) && isset($cache[$focus->id])) {
 			return $cache[$focus->id];
 		} else {
	 		$vh_info = array(
	 			'field' => array($table_prefix.'_field'),
	 			'tab' => array($table_prefix.'_tab', $table_prefix.'_field.tabid = '.$table_prefix.'_tab.tabid', $table_prefix.'_tab.name = ?', array($module)),
	 			'tbl_s_conditionals_rules' => array('tbl_s_conditionals_rules', $table_prefix.'_field.fieldname = tbl_s_conditionals_rules.chk_fieldname',$table_prefix.'_tab.name = tbl_s_conditionals_rules.module'), // crmv@167285
	 		);
	 		if (!empty($focus->id)) {
	 			$PMUtils = ProcessMakerUtils::getInstance();
	 			$tvh_id = $PMUtils->getSystemVersion4Record($focus->id,array('tabs',$module,'id'));
	 			if (!empty($tvh_id)) {
	 				$vh_info['field'] = array($table_prefix.'_field_vh');
	 				$vh_info['tab'] = array($table_prefix.'_tab_vh', $table_prefix.'_field_vh.versionid = '.$table_prefix.'_tab_vh.versionid and '.$table_prefix.'_field_vh.tabid = '.$table_prefix.'_tab_vh.tabid', $table_prefix.'_tab_vh.versionid = ? and '.$table_prefix.'_tab_vh.name = ?', array($tvh_id,$module));
	 				$vh_info['tbl_s_conditionals_rules'] = array('tbl_s_conditionals_rules', $table_prefix.'_field_vh.fieldname = tbl_s_conditionals_rules.chk_fieldname',$table_prefix.'_tab_vh.name = tbl_s_conditionals_rules.module'); // crmv@167285
	 			}
	 			$cvh_id = $PMUtils->getSystemVersion4Record($focus->id,array('conditionals','id'));
	 			if (!empty($cvh_id)) {
	 				$vh_info['tbl_s_conditionals_rules'] = array('tbl_s_conditionals_rules_vh', 'tbl_s_conditionals_rules_vh.versionid = \''.$cvh_id.'\' and '.$vh_info['field'][0].'.fieldname = tbl_s_conditionals_rules_vh.chk_fieldname');
	 			}
	 		}
	 		$query = "SELECT DISTINCT {$vh_info['field'][0]}.tablename, {$vh_info['field'][0]}.columnname, {$vh_info['field'][0]}.fieldname, {$vh_info['field'][0]}.fieldlabel
				FROM {$vh_info['field'][0]}
				INNER JOIN {$vh_info['tbl_s_conditionals_rules'][0]} ON {$vh_info['tbl_s_conditionals_rules'][1]}
				INNER JOIN {$vh_info['tab'][0]} ON {$vh_info['tab'][1]}
				WHERE {$vh_info['tab'][2]} "; // crmv@167285 crmv@180777
			// crmv@180777
			if (isset($vh_info['tbl_s_conditionals_rules'][2]) && !empty($vh_info['tbl_s_conditionals_rules'][2])) {
				$query .= " AND {$vh_info['tbl_s_conditionals_rules'][2]} ";
			}
			// crmv@180777e
	 		$result = $adb->pquery($query, $vh_info['tab'][3]);
	 		$ret_arr = false;
	 		while($row=$adb->fetchByAssoc($result,-1,false)){
	 			$ret_arr[] = $row;
	 		}
	 		if (!empty($focus->id)) $cache[$focus->id] = $ret_arr;
	 		return $ret_arr;
 		}
 	}
	//crmv@18039e crmv@118335e
 	//performance_conditiona_listview - e
 	
 	// crmv@173271
 	/**
 	 * Get the id of the conditional given its name
 	 */
 	public function getIdByName($name) {
		global $adb, $table_prefix;
		
		$res = $adb->limitpQuery("SELECT ruleid FROM tbl_s_conditionals WHERE description = ?", 0, 1, array($name));
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'ruleid');
		}
		
		return null;
	}
	
	/**
	 * Get id of conditionals for the given module
	 */
	public function getIdsForModule($module) {
		global $adb, $table_prefix;
		
		$list = array();
		
		$res = $adb->pquery(
			"SELECT DISTINCT cr.ruleid 
			FROM tbl_s_conditionals_rules cr
			INNER JOIN tbl_s_conditionals c ON c.ruleid = cr.ruleid
			WHERE cr.module = ?", 
			array($module)
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$list[] = $row['ruleid'];
			}
		}
		
		return $list;
	}
	
	/**
	 * Get id of conditionals for the given module
	 */
	public function getIdsForModuleAndUser($module, $userid) {
		global $adb, $table_prefix;
		
		$list = array();
		
		$roles = $this->getMatchingRoles($userid);

		$res = $adb->pquery(
			"SELECT DISTINCT cr.ruleid 
			FROM tbl_s_conditionals_rules cr
			INNER JOIN tbl_s_conditionals c ON c.ruleid = cr.ruleid
			WHERE cr.module = ? AND c.role_grp_check IN (".generateQuestionMarks($roles).")", 
			array($module, $roles)
		);

		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$list[] = $row['ruleid'];
			}
		}
		
		return $list;
	}
	
	/**
	 * Return a conditional rule in the format:
	 * array(
		'name' => 'Name of conditional',
		'module' => 'Contacts',
		'roles' => 'roles::H1',
		'conditions' => array(
			array('fieldname' => 'FIELDNAME', 'criteria' => 4, 'value' => 'VALUE'),
			...
		),
		'fields' => array(
			'FIELDNAME' => array('read' => false, 'write' => false, 'mandatory' => false),
			...
		),
	 */
	public function getConditional($ruleid) {
		global $adb, $table_prefix;
		
		$conditional = array();
		 
		$first = true;
		$res = $adb->pquery("SELECT * FROM tbl_s_conditionals_rules WHERE ruleid = ? ORDER BY id ASC", array($ruleid));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				if ($first) {
					$conditional['module'] = $row['module'];
					$first = false;
				}
				if (!is_array($conditional['conditions'])) $conditional['conditions'] = array();
				$conditional['conditions'][] = array(
					'fieldname' => $row['chk_fieldname'],
					'criteria' => $row['chk_criteria_id'],
					'value' => $row['chk_field_value'],
				);
			}
		} else {
			return false;
		}
		
		$first = true;
		$res = $adb->pquery(
			"SELECT c.*, f.fieldname FROM tbl_s_conditionals c
			INNER JOIN {$table_prefix}_field f ON f.fieldid = c.fieldid
			WHERE ruleid = ? ORDER BY c.sequence ASC", 
			array($ruleid)
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				if ($first) {
					$conditional['name'] = $row['description'];
					$conditional['roles'] = $row['role_grp_check'];
					$first = false;
				}
				if (!is_array($conditional['fields'])) $conditional['fields'] = array();
				$conditional['fields'][$row['fieldname']] = array(
					'read' => (bool)$row['read_perm'],
					'write' => (bool)$row['write_perm'],
					'mandatory' => (bool)$row['mandatory'],
				);
			}
		}
		
		return $conditional;
	}

	/**
	 * Delete a conditional rule by id
	 */
	public function deleteConditional($ruleid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM tbl_s_conditionals WHERE ruleid = ?", array($ruleid));
		$adb->pquery("DELETE FROM tbl_s_conditionals_rules WHERE ruleid = ?", array($ruleid));
	}
	
	/**
	 * Create or update a conditional rule
	 * The structure is the same as the one returned by getConditional
	 */
	public function saveConditional($ruleid, $conditional) {
		global $adb, $table_prefix;
		
		if ($ruleid > 0) {
			$mode = '';
			// update is implemented as delete + create
			$this->deleteConditional($ruleid);
		} else {
			$mode = 'edit';
			$ruleid = $adb->getUniqueID('tbl_s_conditionals');
			
			// get the original name before updating, needed for the logs
			$result = $adb->pquery("SELECT description FROM tbl_s_conditionals WHERE ruleid = ?", array($ruleid));
			if ($result && $adb->num_rows($result) > 0) $savedRuleName = $adb->query_result_no_html($result,0,'description');
		}
		
		$inserts = array();
		$insertRules = array();
		
		// get field ids
		$fieldids = array();
		$tabid = getTabid($conditional['module']);
		$fieldnames = array_keys($conditional['fields']);
		$res = $adb->pquery("SELECT fieldname, fieldid FROM {$table_prefix}_field WHERE tabid = ? AND fieldname IN (".generateQuestionMarks($fieldnames).")", array($tabid, $fieldnames));
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$fieldids[$row['fieldname']] = $row['fieldid'];
		}
		
		foreach ($conditional['fields'] as $fieldname => $fieldinfo) {
			
			$fieldid = $fieldids[$fieldname];
			
			// WTF is this for?
			$max_result = $adb->pquery("SELECT MAX(sequence) + 1 AS sequence FROM tbl_s_conditionals WHERE fieldid = ?", array($fieldid));
			$seq = (int)$adb->query_result_no_html($max_result,0,'sequence');
			
			$inserts[] = array(
				$ruleid,
				$fieldid,
				$seq,
				1,
				$conditional['name'],
				intval($fieldinfo['read']),
				intval($fieldinfo['write']),
				intval($fieldinfo['mandatory']),
				$conditional['roles'],
			);
		}
		
		foreach ($conditional['conditions'] as $cond) {
			$ruleid_rule = $adb->getUniqueID('tbl_s_conditionals_rules');
			$insertRules[] = array(
				$ruleid_rule,
				$ruleid,
				$cond['fieldname'],
				$cond['criteria'],
				$cond['value'],
				$conditional['module']
			);
		}
		
		// do bulk inserts
		$adb->startTransaction();
		
		$cols = array('ruleid','fieldid','sequence','active','description','read_perm','write_perm','mandatory','role_grp_check');
		$adb->bulkInsert('tbl_s_conditionals', $cols, $inserts);
		
		$cols = array('id','ruleid','chk_fieldname','chk_criteria_id','chk_field_value','module');
		$adb->bulkInsert('tbl_s_conditionals_rules', $cols, $insertRules);
		
		$adb->completeTransaction();
		
		// and save the log
		$this->saveLogs($mode, $ruleid, $conditional, $savedRuleName);
		
		return $ruleid;
	}
	
	protected function saveLogs($mode, $ruleid, $conditional, $savedRuleName = '') {
		global $metaLogs;
		
		$versioning = ConditionalsVersioning::getInstance();
		$condName = $conditional['name'];
		
		if ($mode == '') {
			if (count($conditional['fields']) > 0) {
				if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_ADDCONDITIONAL, $ruleid, array('rulename'=>$condName));
				if (!empty($metaLogId)) $versioning->versionOperation($metaLogId);
			}
		} else {
			if ($condName != $savedRuleName) {
				if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_RENAMECONDITIONAL, $ruleid, array('rulename'=>$savedRuleName,'new_rulename'=>$condName));
				if (!empty($metaLogId)) $versioning->versionOperation($metaLogId);
			}
			if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITCONDITIONAL, $ruleid, array('rulename'=>$condName));
			if (!empty($metaLogId)) $versioning->versionOperation($metaLogId);
		}
	}
	// crmv@173271e
	
}