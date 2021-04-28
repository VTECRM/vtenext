<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@164122 */

class ModNotifications extends SDKExtendableClass {

	var $table_name;
	var $table_index= 'modnotificationsid';
	var $column_fields = Array();

	var $field_columns = array(
		// fieldname -> column
		'related_to' => 'related_to',
		'assigned_user_id' => 'smownerid',
		'creator' => 'smcreatorid',
		'createdtime' => 'createdtime',
		'modifiedtime' => 'modifiedtime',
		'mod_not_type' => 'mod_not_type',
		'seen' => 'seen',
		'sent_summary_not' => 'sent_summary_not',
		'subject' => 'subject',
		'description' => 'description',
		'from_email' => 'from_email',
		'from_email_name' => 'from_email_name',
	);

	var $skip_modules = array('Emails','Fax','Sms','Events','ModComments','Messages','MyFiles','MyNotes'); // crmv@164120

	//crmv@63483
	var $old_notification_types = array(
		'Changed followed record'=>array('action'=>'has changed'),
		'Changed record'=>array('action'=>'has changed'),
		'Created record'=>array('action'=>'has created and assigned to you'),
		'Ticket changed'=>array('action'=>'has changed'),
		'Ticket created'=>array('action'=>'has created and assigned to you'),
		'Ticket portal replied'=>array('action'=>'responded to'),
		'Ticket portal created'=>array('action'=>'has created'),
		'Product stock level'=>array('action'=>'MSG_STOCK_LEVEL'),
		'Calendar invitation'=>array('action'=>'has invited you to'),
		'Calendar invitation edit'=>array('action'=>'has changed your invitation to'),
		'Calendar invitation answer yes'=>array('action'=>'will attend'),

		'Calendar invitation answer no'=>array('action'=>'did not attend'),
		'Calendar invitation answer yes contact'=>array('action'=>'will attend'),
		'Calendar invitation answer no contact'=>array('action'=>'did not attend'),
		'Reminder calendar'=>array('action'=>'reminder activity'),
		'Relation'=>array('action'=>'has related'),
		'ListView changed'=>array('action'=>'Has been changed'),
		'Import Completed'=>array('action'=>'Import Completed'), //crmv@31126
		'MassEdit'=>array('action'=>'MassEdit'), //crmv@91571
		'MassEditError'=>array('action'=>'MassEditError'), //crmv@91571
		'MassCreate'=>array('action'=>'MassCreate'), // crmv@202577
		'MassCreateError'=>array('action'=>'MassCreateError'), // crmv@202577
	);
	var $notification_types = array();
	//crmv@63483e

	var $notification_summary_values = array(
		'Every week'=>'-1 week',
		'Every 2 days'=>'-2 days',
		'Every day'=>'-1 day',
		'Every 4 hours'=>'-4 hours',
		'Every 2 hours'=>'-2 hours',
		'Hourly'=>'-1 hour',
	);

	function __construct() {
		global $table_prefix;

		$this->table_name = $table_prefix.'_modnotifications';
		$this->tab_name = Array($table_prefix.'_modnotifications');
		$this->column_fields = array_fill_keys(array_keys($this->field_columns), '');
		
		$this->initializeNotificationTypes();	//crmv@63483
	}
	
	//crmv@63483 crmv@105600
	function initializeNotificationTypes() {
		global $log, $adb, $table_prefix;
		$log->debug("Entering initializeNotificationTypes() method ...");
		
		static $notTypesCache;

		if (is_array($notTypesCache)) {
			$this->notification_types = $notTypesCache;
			return;
		}
		
		if (Vtecrm_Utils::CheckTable($table_prefix.'_modnotifications_types')) {
			$res = $adb->query("SELECT type, action FROM {$table_prefix}_modnotifications_types");
			if($res && $adb->num_rows($res) > 0) {
				while($row = $adb->fetchByAssoc($res)) {
					$type = $row['type'];
					$action = $row['action'];
					$this->notification_types[$type] = array('action' => $action);
				}
			}
			$notTypesCache = $this->notification_types;
		}
		$log->debug("Exiting initializeNotificationTypes() method ...");
	}
	//crmv@105600e
	
	function addNotificationType($type, $action, $custom=1) {
		global $log, $adb, $table_prefix;
		$log->debug("Entering addNotificationType({$type}, {$action}) method ...");
		if(!empty($type) && !empty($action)) {
			if(!isset($this->notification_types[$type])) {
				$unique = $adb->getUniqueID("{$table_prefix}_modnotifications_types");
				$params = array($unique, $type, $action, $custom);
				$res = $adb->pquery("INSERT INTO {$table_prefix}_modnotifications_types(id, type, action, custom) VALUES(?, ?, ?, ?)", $params);
				if($res) {
					$this->notification_types[$type] = array('action' => $action);
					$log->debug("The notification type has been inserted.");
					return true;
				}
			}
		}
		$log->debug("Exiting addNotificationType({$type}, {$action}) method ...");
		return false;
	}
	
	function removeNotificationType($type) {
		global $log, $adb, $table_prefix;
		$log->debug("Entering removeNotificationType({$type}) method ...");
		if(!empty($type)) {
			if(isset($this->notification_types[$type])) {
				$res = $adb->pquery("SELECT custom FROM {$table_prefix}_modnotifications_types WHERE type=?", array($type));
				if($res && $adb->num_rows($res)) {
					$custom = $adb->query_result($res, 0, 'custom');
					if($custom == 1) {
						$res1 = $adb->pquery("DELETE FROM {$table_prefix}_modnotifications_types WHERE type=?", array($type));
						if($res1) {
							unset($this->notification_types[$type]);
							$log->debug("The notification type has been deleted.");
							return true;
						}
					}
				}
			}
		}
		$log->debug("Exiting removeNotificationType({$type}) method ...");
		return false;
	}
	//crmv@63483e
	
	public function hasFolders() {
		return false;
	}

	public function save() {
		// simulate crmentity save{
		if ($this->mode == 'edit' && $this->id > 0) {
			$this->updateRecord();
		} else {
			$this->insertRecord();
		}
		
		// post save actions, kept for compatibility
		$this->save_module();
	}
	
	public function retrieve_entity_info($id) {
		return $this->retrieveRecord($id, true);
	}
	
	public function retrieve_entity_info_no_html($id) {
		return $this->retrieveRecord($id, false);
	}
	
	
	protected function processFieldsForDb($values) {
		if ($values['seen'] == 'off')
			$values['seen'] = 0;
		elseif ($values['seen'] == 'on')
			$values['seen'] = 1;
		
		return $values;
	}
	
	protected function processFieldsFromDb($values) {
		$values['seen'] = ($values['seen'] == 0 ? 'off' : 'on');

		return $values;
	}
	
	public function retrieveRecord($id, $htmlEncode = false) {
		global $adb;
		
		$res = $adb->pquery("SELECT * FROM {$this->table_name} WHERE {$this->table_index} = ?", array($id));
		if ($res && $adb->num_rows($res) == 0) return 'LBL_RECORD_NOT_FOUND';
		
		// convert to field names
		$fieldNames = array_flip($this->field_columns);
		if ($htmlEncode) {
			$row = $adb->fetchByAssoc($res);
		} else {
			$row = $adb->fetchByAssoc($res, -1, false);
		}
		$row = $this->processFieldsFromDb($row);
		$row = array_intersect_key($row, $fieldNames);
		$fieldNames = array_replace($row, array_intersect_key($fieldNames, $row));
		
		$values = array_combine(array_values($fieldNames), array_values($row));
		$this->column_fields = $values;
		
		// set compatibility values
		$this->column_fields["record_id"] = $id;
		$this->column_fields["record_module"] = 'ModNotifications';
		$this->id = $id;
	}
	
	public function insertRecord() {
		global $adb, $current_user;
		
		$now = date('Y-m-d H:i:s');

		$values = array_intersect_key($this->column_fields, $this->field_columns);
		$columns = array_replace($values, array_intersect_key($this->field_columns, $values));
		$values = array_combine(array_values($columns), array_values($values));
		$values = $this->processFieldsForDb($values);
		$description = $values['description'];
		unset($values['description']);
		
		$id = $adb->getUniqueID($this->table_name);
		$values[$this->table_index] = $id;
		// force values
		$values['createdtime'] = $now;
		$values['modifiedtime'] = $now;
		$values['smcreatorid'] = $current_user->id;
		if (empty($values['smownerid'])) $values['smownerid'] = $current_user->id;
		
		$columns = array_keys($values);
		$adb->format_columns($columns);
		$sql = "INSERT INTO {$this->table_name} (".implode(',', $columns).") VALUES (".generateQuestionMarks($columns).")";
		
		$r = $adb->pquery($sql, $values);
		if ($r) {
			$this->id = $id;
			// update description
			$adb->updateClob($this->table_name,'description',$this->table_index."=".$this->id, $description);
		}

	}
	
	public function updateRecord() {
		global $adb, $current_user;
		
		$now = date('Y-m-d H:i:s');
		
		$values = array_intersect_key($this->column_fields, $this->field_columns);
		$columns = array_replace($values, array_intersect_key($this->field_columns, $values));
		$values = array_combine(array_values($columns), array_values($values));
		$values = $this->processFieldsForDb($values);
		$description = $values['description'];
		unset($values['description']);
		unset($values['createdtime']);
		//unset($values['smcreatorid']); //crmv@182384 allow update of creator
		
		// force values
		$values['modifiedtime'] = $now;
		
		$updSql = array();
		foreach ($values as $column => $value) {
			$updSql[] = "$column = ?";
		}
		
		$sql = "UPDATE {$this->table_name} SET ".implode(',', $updSql)." WHERE {$this->table_index} = ?";
		$values[$this->table_index] = $this->id;
		
		$r = $adb->pquery($sql, $values);
		if ($r) {
			// update description
			$adb->updateClob($this->table_name,'description',$this->table_index."=".$this->id, $description);
		}
	}
	
	public function canUserSeeRecord($id) {
		global $adb, $current_user;
		$res = $adb->pquery("SELECT smownerid FROM {$this->table_name} WHERE {$this->table_index} = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			$owner = $adb->query_result_no_html($res, 0, 'smownerid');
			return ($current_user->id == $owner);
		}
		return false;
	}
	
	public function canUserEditRecord($id) {
		return $this->canUserSeeRecord($id);
	}

	function save_module() {
		//crmv@47905
		static $cached_notify = Array();
		global $adb,$table_prefix,$current_user,$current_language,$default_language; //crmv@61399
		$userid = $this->column_fields['assigned_user_id'];
		$notify_me_via_email = false;
		if (!isset($cached_notify[$userid])){
			$sql = "select notify_me_via from {$table_prefix}_users where id = ?";
			$res = $adb->pquery($sql,Array($userid));
			if ($res){
				$cached_notify[$userid] = $adb->query_result_no_html($res,0,'notify_me_via');
			}
		}
		if ($cached_notify[$userid] == 'Emails') {
			$sql = "UPDATE {$this->table_name} set seen=? where {$this->table_index} = ?";
			$params = Array(1,$this->id);
			$adb->pquery($sql,$params);
			$notify_me_via_email = true;
		}
		if (in_array($this->column_fields['mod_not_type'],array('ListView changed','Reminder calendar'))) { //crmv@184676
			$adb->pquery("UPDATE {$this->table_name} SET smcreatorid = ? WHERE {$this->table_index} = ?",array(0,$this->id));
		}
		if ($notify_me_via_email) {
			require_once('modules/Emails/mail.php');
			global $HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID;
			$user_emailid = getUserEmailId('id',$this->column_fields['assigned_user_id']);
			if ($user_emailid != '') {
				$from_email = $this->column_fields['from_email'];
				if ($from_email == '') {
					$from_email = $HELPDESK_SUPPORT_EMAIL_ID;
				}
				$from_email_name = $this->column_fields['from_email_name'];
				if ($from_email_name == '') {
					$from_email_name = $HELPDESK_SUPPORT_NAME;
				}
				$subject = $this->column_fields['subject'];
				if ($subject == '') {
					//crmv@61399
					$default_language_tmp = $default_language;
					$current_language_tmp = $current_language;
					$user = CRMEntity::getInstance('Users');
					$user->retrieve_entity_info($this->column_fields['assigned_user_id'],'Users');
					$current_language = $default_language = $user->column_fields['default_language'];
					//crmv@61399 e
					$parent_module = getSalesEntityType($this->column_fields['related_to']);
					$recordName = getEntityName($parent_module,$this->column_fields['related_to']);
					$entityType = getSingleModuleName($parent_module,$this->column_fields['related_to']); //crmv@32334
					$subject = trim(getUserFullName($current_user->id)).' '.$this->translateNotificationType($this->column_fields['mod_not_type'],'action');
					if ($this->column_fields['mod_not_type'] == 'Relation') {
						$relation_parent_id = $this->column_fields['description'];
						$relation_parent_module = getSalesEntityType($relation_parent_id);
						$relation_recordName = getEntityName($relation_parent_module,$relation_parent_id);
						$relation_entityType = getSingleModuleName($relation_parent_module,$relation_parent_id);
						$subject .= ' '.$relation_recordName[$this->column_fields['description']]." ($relation_entityType) ";
						$subject .= getTranslatedString('LBL_TO','ModComments');
					}
					if ($this->column_fields['mod_not_type'] == 'ListView changed') {
						$subject = $this->translateNotificationType($this->column_fields['mod_not_type'],'action');
						global $app_strings;
						$result = $adb->query('SELECT * FROM '.$table_prefix.'_customview WHERE cvid = '.$this->column_fields['related_to']);
						if ($result) {
							$module = $adb->query_result($result,0,'entitytype');
							$entityType = getTranslatedString($module,$module);
							$viewname = $adb->query_result($result,0,'viewname');
							if ($viewname == 'All') {
								$viewname = $app_strings['COMBO_ALL'];
							} elseif($this->parent_module == 'Calendar' && in_array($viewname,array('Events','Tasks'))) {
								$viewname = $app_strings[$viewname];
							}
							$subject .= " $viewname ($entityType)";
						}
					} else {
						$subject .= ' '.$recordName[$this->column_fields['related_to']]." ($entityType)";
					}
					//crmv@61399
					$default_language = $default_language_tmp;
					$current_language = $current_language_tmp;
					//crmv@61399 e
				}
				$body = $this->column_fields['description'];
				if (in_array($this->column_fields['mod_not_type'],array('Relation','ListView changed','Generic'))) { //crmv@183346
					$body = '';
				}
				if ($body == '') {
					if ($this->column_fields['mod_not_type'] == 'ListView changed') {
						$body = $this->getBodyNotificationCV($this->id,$this->column_fields,$from_email_name);
					} else {
						$body = $this->getBodyNotification($this->id,$this->column_fields,$from_email_name);
					}
				}
				$mail_status = send_mail($parent_module,$user_emailid,$from_email_name,$from_email,$subject,$body,'','','','','','',$mail_tmp,'','',true); // crmv@129149
			}
		}
		//crmv@47905 e
	}
	
	static function getWidget($name) {
		if ($name == 'DetailViewBlockCommentWidget') {
			require_once dirname(__FILE__) . '/widgets/DetailViewBlockComment.php';
			return (new ModNotifications_DetailViewBlockCommentWidget());
		}
		return false;
	}
	
	/**
	 * @deprecated
	 */
	function addWidgetToAll() {

	}
	
	/**
	 * @deprecated
	 */
	function addWidgetTo($moduleNames) {

	}
	
	function getNotificationTypes() {
		return $this->notification_types;
	}
	
	/**
	 * @deprecated
	 */
	function setNotificationTypes($moduleInstance) {

	}
	
	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($moduleName, $eventType) {
		require_once('include/utils/utils.php');
		global $adb,$table_prefix;

 		if($eventType == 'module.postinstall') {

			$ModNotificationsCommonInstance = Vtecrm_Module::getInstance($moduleName);

 			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			$ModNotificationsCommonInstance->hide(array('hide_module_manager'=>1,'hide_profile'=>1,'hide_report'=>1));
			$adb->pquery("UPDATE {$table_prefix}_def_org_share SET editstatus = ? WHERE tabid = ?",array(2,$ModNotificationsCommonInstance->id));

			SDK::setUtil('modules/ModNotifications/ModNotificationsCommon.php');

			self::saveDefaultModuleSettings();
			self::setNotificationTypes($ModNotificationsCommonInstance);

			$em = new VTEventsManager($adb);
			$em->registerHandler('vte.entity.beforesave', 'modules/ModNotifications/ModNotificationsHandler.php', 'ModNotificationsHandler');
			$em->registerHandler('vte.entity.aftersave.notifications', 'modules/ModNotifications/ModNotificationsHandler.php', 'ModNotificationsHandler'); // crmv@198950 crmv@207852

			$columns = array_keys($adb->datadict->MetaColumns($table_prefix.'_activity'));
			if (in_array(strtoupper('sendnotification'),$columns)) {
				$result = $adb->query('SELECT smownerid, crmid FROM '.$table_prefix.'_activity
										INNER JOIN '.$table_prefix.'_crmentity ON activityid = crmid
										WHERE deleted = 0 AND sendnotification = 1');
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$this->migrateFollowFlag($row['smownerid'],$row['crmid']);
					}
				}
				$sqlarray = $adb->datadict->DropColumnSQL($table_prefix.'_activity','sendnotification');
				$adb->datadict->ExecuteSQLArray($sqlarray);
				$adb->query("delete from ".$table_prefix."_field where fieldname = 'sendnotification' and tabid in (9,16)");
			}

			$columns = array_keys($adb->datadict->MetaColumns($table_prefix.'_account'));
			if (in_array(strtoupper('notify_owner'),$columns)) {
				$result = $adb->query('SELECT smownerid, crmid FROM '.$table_prefix.'_account
										INNER JOIN '.$table_prefix.'_crmentity ON accountid = crmid
										WHERE deleted = 0 AND notify_owner = 1');
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$this->migrateFollowFlag($row['smownerid'],$row['crmid']);
					}
				}
				$sqlarray = $adb->datadict->DropColumnSQL($table_prefix.'_account','notify_owner');
				$adb->datadict->ExecuteSQLArray($sqlarray);
				$adb->query("delete from ".$table_prefix."_field where fieldname = 'notify_owner' and tabid = 6");
			}

			$columns = array_keys($adb->datadict->MetaColumns($table_prefix.'_contactdetails'));
			if (in_array(strtoupper('notify_owner'),$columns)) {
				$result = $adb->query('SELECT smownerid, crmid FROM '.$table_prefix.'_contactdetails
										INNER JOIN '.$table_prefix.'_crmentity ON contactid = crmid
										WHERE deleted = 0 AND notify_owner = 1');
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$this->migrateFollowFlag($row['smownerid'],$row['crmid']);
					}
				}
				$sqlarray = $adb->datadict->DropColumnSQL($table_prefix.'_contactdetails','notify_owner');
				$adb->datadict->ExecuteSQLArray($sqlarray);
				$adb->query("delete from ".$table_prefix."_field where fieldname = 'notify_owner' and tabid = 4");
			}

			// crmv@47611
			if (Vtecrm_Utils::CheckTable($table_prefix.'_cronjobs')) {
				require_once('include/utils/CronUtils.php');
				$CU = CronUtils::getInstance();

				$cj = new CronJob();
				$cj->name = 'ModNotifications';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/ModNotifications/ModNotifications.service.php';
				$cj->timeout = 600;             // 10min timeout
				$cj->repeat = 3600;				// crmv@189690 run every hour
				$CU->insertCronJob($cj);
			}
			// crmv@47611e
			
			//crmv@63483
			$focusModNotifications = CRMEntity::getInstance('ModNotifications');
			foreach($focusModNotifications->old_notification_types as $type => $values) {
				$adb->pquery("INSERT INTO {$table_prefix}_modnotifications_types (id, type, action, custom) VALUES (?, ?, ?, ?)", array($adb->getUniqueID("{$table_prefix}_modnotifications_types"), $type, $values['action'], 0));
			}
			$focusModNotifications->addNotificationType('Revisioned document', 'added a revision to', 0);
			$focusModNotifications->addNotificationType('Import Error', 'Import Error', 0);	// crmv@65455
			//crmv@63483e
			$focusModNotifications->addNotificationType('Generic','',0); //crmv@183346

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			$tmp_dir = 'packages/vte/mandatory/tmp1';
			mkdir($tmp_dir);
			$unzip = new Vtecrm_Unzip("packages/vte/mandatory/$moduleName.zip");
			$unzip->unzipAllEx($tmp_dir);
			if($unzip) $unzip->close();
			copy("$tmp_dir/cron/$moduleName.service.php","cron/modules/$moduleName/$moduleName.service.php");
			if ($handle = opendir($tmp_dir)) {
				FSUtils::deleteFolder($tmp_dir);
			}
		}
 	}
 	
	function migrateFollowFlag($owner,$record) {
		global $adb, $table_prefix;
		$result = $adb->pquery('SELECT * FROM '.$table_prefix.'_users WHERE id = ? AND deleted = 0 AND status = ?', array($owner,'Active'));
		if($result && $adb->num_rows($result) > 0) {
			$this->toggleFollowFlag($owner,$record);
		} else {
			$result = $adb->pquery('SELECT groupid FROM '.$table_prefix.'_groups WHERE groupid = ?', array($owner));
			if($result && $adb->num_rows($result) > 0) {
				$groupid = $adb->query_result($result,0,'groupid');
				require_once('include/utils/GetGroupUsers.php');
				$focus = new GetGroupUsers();
				$focus->getAllUsersInGroup($groupid);
				$group_users = $focus->group_users;
				if (!empty($group_users)) {
					$group_users_str = implode(',',$group_users);
					$query  = 'select id from '.$table_prefix.'_users where id in ('.$group_users_str.') and deleted = 0 and status = ?';
					$params = array('Active');
					if ($current_user) {
						$query .= ' and id <> ?';
						$params[] = $current_user->id;
					}
					$result = $adb->pquery($query,$params);
					if($result && $adb->num_rows($result) > 0) {
						while($row=$adb->fetchByAssoc($result)) {
							$this->toggleFollowFlag($row['id'],$record);
						}
					}
				}
			}
		}
	}
	
	function getFollowedRecords($user,$type='') {
		global $adb;
		$records = array();
		if ($type == 'customview') {
			$result = $adb->pquery('select cvid as record from vte_modnot_follow_cv where userid = ?',array($user));
		} else {
			$result = $adb->pquery('select record from vte_modnotifications_follow where userid = ?',array($user));
		}
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$records[] = $row['record'];
			}
		}
		return $records;
	}
	
	function getFollowingUsers($record) {
		global $adb;
		$users = array();
		$result = $adb->pquery('select * from vte_modnotifications_follow where record = ?',array($record));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$users[] = $row['userid'];
			}
		}
		return $users;
	}
	
	function toggleFollowFlag($user,$record,$type='') {
		global $adb;
		if ($type == 'customview') {
			$result = $adb->pquery('select * from vte_modnot_follow_cv where userid = ? and cvid = ?',array($user,$record));
		} else {
			$result = $adb->pquery('select * from vte_modnotifications_follow where userid = ? and record = ?',array($user,$record));
		}
		if ($result && $adb->num_rows($result) > 0) {
			$this->unsetFollowFlag($user,$record,$type);
		} else {
			$this->setFollowFlag($user,$record,$type);
		}
	}
	
	function setFollowFlag($user,$record,$type='') {
		global $adb,$table_prefix,$current_user; //crmv@62409
		if ($type == 'customview') {
			$adb->pquery('insert into vte_modnot_follow_cv (cvid,userid,count) values(?,?,-1)',array($record,$user));

			$result = $adb->pquery('SELECT entitytype FROM '.$table_prefix.'_customview WHERE cvid = ?',array($record));
			if ($result && $adb->num_rows($result) > 0) {
				$module = $adb->query_result($result,0,'entitytype');
				//crmv@62409
				$queryGenerator = QueryGenerator::getInstance($module, $current_user);
				$queryGenerator->initForCustomViewById($record);
				$queryGenerator->getQuery();
				//crmv@62409 e
				$list_query_count = VteSession::get($module.'_listquery');
				$list_query_result = $adb->query($list_query_count);
				checkListNotificationCount($list_query_count,$user,$record,$adb->num_rows($list_query_result));
			}
		} else {
			$adb->pquery('insert into vte_modnotifications_follow (userid,record) values (?,?)',array($user,$record));
		}
	}
	
	function unsetFollowFlag($user,$record,$type='') {
		global $adb;
		if ($type == 'customview') {
			$adb->pquery('delete from vte_modnot_follow_cv where userid = ? and cvid = ?',array($user,$record));
		} else {
			$adb->pquery('delete from vte_modnotifications_follow where userid = ? and record = ?',array($user,$record));
		}
	}
	
	function isEnabled($module,$record='',$user='') {
		if ($module == 'Activity') $module = 'Calendar';
		$modules = array_keys($this->getEnableModuleSettings());
		if (in_array($module,$modules)) {
			if ($record != '' && $user != '') {
				return $this->isPermitted($module,$record,$user);
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	function isPermitted($module,$record,$user) {
		if ($module == 'Activity') $module = 'Calendar';
		global $current_user;
		$tmp_current_user = $current_user;
		$current_user = CRMEntity::getInstance('Users');
		$current_user->retrieve_entity_info($user,'Users');
		$return = false;
		if (isPermitted($module,'DetailView',$record) != 'no') {
			$return = true;
		}
		$current_user = $tmp_current_user;
		return $return;
	}
	
	function getModuleSettings($record) {
		global $adb;
		$info = $this->getEnableModuleSettings();
		if ($record == '') {
			$default_module_settings = $this->getDefaultModuleSettings();
			if (!empty($default_module_settings)) {
				foreach($default_module_settings as $module => $row) {
					$info[$module] = array('create'=>$row['create'],'edit'=>$row['edit']);
				}
			}
		} else {
			$result = $adb->pquery('select * from vte_modnotifications_modules where userid = ? and (notify_create <> 0 or notify_edit <> 0) order by module',array($record));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$info[$row['module']] = array('create'=>$row['notify_create'],'edit'=>$row['notify_edit']);
				}
			}
		}
		return $info;
	}
	
	function getDefaultModuleSettings() {
		return array(
			'Potentials'=>array('create'=>1,'edit'=>1),
			'Calendar'=>array('create'=>1,'edit'=>1),
			'HelpDesk'=>array('create'=>1,'edit'=>1),
			'ProjectMilestone'=>array('create'=>1,'edit'=>1),
			'ProjectTask'=>array('create'=>1,'edit'=>1),
			'ProjectPlan'=>array('create'=>1,'edit'=>1),
		);
	}
	
	function getEnableModuleSettings() {
		global $adb,$table_prefix;
		if (VteSession::isEmpty('ModNotificationsModules')) {
			$info = array();
			$result = $adb->pquery('SELECT name FROM '.$table_prefix.'_tab WHERE isentitytype = 1 AND name NOT IN ('.generateQuestionMarks($this->skip_modules).')',$this->skip_modules);
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$info[$row['name']] = array('create'=>0,'edit'=>0);
				}
			}
			VteSession::set('ModNotificationsModules', $info);
		}
		return VteSession::get('ModNotificationsModules');
	}
	
	function saveModuleSettings($record,$request) {
		global $adb;

		$adb->pquery('delete from vte_modnotifications_modules where userid = ?',array($record));

		$info = array();
		foreach($request as $key => $value) {
			if (strpos($key,'_notify_create')) {
				$tmp = explode('_notify_create',$key);
				$module = $tmp[0];
				if ($value == 'on') {
					$value = 1;
				} elseif ($value == 'off') {
					$value = 1;
				}
				$info[$module]['create'] = $value;
			} elseif (strpos($key,'_notify_edit')) {
				$tmp = explode('_notify_edit',$key);
				$module = $tmp[0];
				if ($value == 'on') {
					$value = 1;
				} elseif ($value == 'off') {
					$value = 1;
				}
				$info[$module]['edit'] = $value;
			}
		}
		if (!empty($info)) {
			foreach($info as $module => $flags) {
				if (!isset($info[$module]['create'])) {
					$info[$module]['create'] = 0;
				}
				if (!isset($info[$module]['edit'])) {
					$info[$module]['edit'] = 0;
				}
				$adb->pquery('insert into vte_modnotifications_modules (userid,module,notify_create,notify_edit) values (?,?,?,?)',array($record,$module,$info[$module]['create'],$info[$module]['edit']));
			}
		}
	}
	
	function saveDefaultModuleSettings($users=null) {
		global $adb, $table_prefix;
		if (!isset($users)) {
			$result = $adb->query('SELECT id FROM '.$table_prefix.'_users');
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$users[] = $row['id'];
				}
			}
		} elseif (!is_array($users)) {
			$users = array($users);
		}
		if (!empty($users)) {
			foreach($users as $userid) {
				$adb->pquery('delete from vte_modnotifications_modules where userid = ?',array($userid));
				$default_module_settings = $this->getDefaultModuleSettings();
				if (!empty($default_module_settings)) {
					foreach($default_module_settings as $module => $info) {
						$adb->pquery('insert into vte_modnotifications_modules (userid,module,notify_create,notify_edit) values (?,?,?,?)',array($userid,$module,$info['create'],$info['edit']));
					}
				}
			}
		}
	}
	
	function getInterestedToModuleUsers($mode,$module) {
		global $adb;
		$users = array();
		if ($module == 'Activity') $module = 'Calendar';
		$result = $adb->pquery("SELECT userid FROM vte_modnotifications_modules WHERE module = ? AND notify_{$mode} = ?",array($module,1));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$users[] = $row['userid'];
			}
		}
		return $users;
	}
	
	//crmv@35105
	function setRecordSeen($record) {
		if (strpos($record,',') !== false) {
			$ids = array_filter(explode(',',$record));
		} else {
			$ids = array($record);
		}

		global $adb;
		$ids = array_map('intval', $ids); //crmv@66674
		$result = $adb->pquery("UPDATE {$this->table_name} SET seen = 1, modifiedtime = ? WHERE {$this->table_index} IN (".generateQuestionMarks($ids).")",array(date('Y-m-d H:i:s'), $ids));
	}
	
	function setRecordUnseen($record) {
		if (strpos($record,',') !== false) {
			$ids = array_filter(explode(',',$record));
		} else {
			$ids = array($record);
		}

		global $adb;
		$ids = array_map('intval', $ids); //crmv@66674
		$result = $adb->pquery("UPDATE {$this->table_name} SET seen = 0, modifiedtime = ? WHERE {$this->table_index} IN (".generateQuestionMarks($ids).")",array(date('Y-m-d H:i:s'), $ids));
	}

	//crmv@43194
	function setAllRecordsSeen() {
		global $adb, $current_user;
		// get all unseed ids
		$res = $adb->pquery("SELECT {$this->table_index}
			FROM {$this->table_name}
			WHERE seen = 0 AND smownerid = ?",array($current_user->id));
			
		if ($res && $adb->num_rows($res) > 0) {
			$idlist = array();
			for ($i=0; $i<$adb->num_rows($res); ++$i) {
				$idlist[] = $adb->query_result_no_html($res, $i, $this->table_index);
			}
			$chunks = array_chunk($idlist, 100);
			foreach ($chunks as $sublist) {
				$result = $adb->pquery("UPDATE {$this->table_name} SET seen = 1, modifiedtime = ? WHERE {$this->table_index} IN (".generateQuestionMarks($sublist).")",array(date('Y-m-d H:i:s'), $sublist));
			}
		}
	}
	//crmv@43194e

	function setSeenForRecord($user_id, $current_module, $id) {
		global $adb, $table_prefix;
		//crmv47905
		$result = $adb->pquery(
			"UPDATE {$this->table_name}
			SET seen = 1, modifiedtime = ?
			WHERE seen = 0 AND smownerid = ? AND related_to = ?",
			array(date('Y-m-d H:i:s'), $user_id,$id)
		);

		//crmv47905 e
	}
	//crmv@35105e
	
	function translateNotificationType($type,$mode) {
		$label = $type;
		$notification_types = $this->getNotificationTypes();
		if (isset($notification_types[$type][$mode])) {
			$label = $notification_types[$type][$mode];
		}
		return getTranslatedString($label,'ModNotifications');
	}
	
	//crmv@32334
	function skipNotifications() {
		global $global_skip_notifications;
		if (isZMergeAgent() || isVteSyncAgent() || $global_skip_notifications === true) { // crmv@48267
			return true;
		}
		return false;
	}
	//crmv@32334e

	// crmv@102955
	/*
	 * Create a Notification
	 * 
	 * In order to skip the creation of notifications you can set the global variable $global_skip_notifications = true before the save of your record.
	 * ex.
	 * global $global_skip_notifications;
	 * $tmp_global_skip_notifications = $global_skip_notifications;
	 * $global_skip_notifications = true;
	 * [...]
	 * $focus->save('Accounts');
	 * [...]
	 * $global_skip_notifications = $tmp_global_skip_notifications;
	 */
	function saveFastNotification($column_fields,$manage_group=true) {
		global $adb, $table_prefix, $current_user;
		
		$notified_users = array();
		//crmv@32334
		if ($this->skipNotifications()) {
			return $notified_users;
		}
		//crmv@32334e
		if (!$manage_group) {	//qui sono sicuro che mi arriva un utente e non un gruppo

			$this->saveUserNotification($column_fields);
			$notified_users[] = $column_fields['assigned_user_id'];

		} else {	//controllo se l'assegnatario e' un gruppo e in quel caso notifico tutti i partecipanti
			
			$result = $adb->pquery('SELECT id FROM '.$table_prefix.'_users WHERE id = ? AND deleted = 0 AND status = ?', array($column_fields['assigned_user_id'],'Active'));
			if($result && $adb->num_rows($result) > 0) {

				$this->saveUserNotification($column_fields);
				$notified_users[] = $column_fields['assigned_user_id'];
				
			} else {
				$result = $adb->pquery('SELECT groupid FROM '.$table_prefix.'_groups WHERE groupid = ?', array($column_fields['assigned_user_id']));
				if($result && $adb->num_rows($result) > 0) {
					$groupid = $adb->query_result_no_html($result,0,'groupid');
					require_once('include/utils/GetGroupUsers.php');
					$focus = new GetGroupUsers();
	        		$focus->getAllUsersInGroup($groupid);
	        		$group_users = $focus->group_users;
	        		if (!empty($group_users)) {
	        			$group_users_str = implode(',',$group_users);
	        			$query  = 'select id from '.$table_prefix.'_users where id in ('.$group_users_str.') and deleted = 0 and status = ?';
	        			$params = array('Active');
	        			if ($current_user) {
	        				$query .= ' and id <> ?';
	        				$params[] = $current_user->id;
	        			}
	        			$result = $adb->pquery($query,$params);
	        			if($result && $adb->num_rows($result) > 0) {
	        				while($row=$adb->fetchByAssoc($result, -1, false)) {

	        					$column_fields['assigned_user_id'] = $row['id'];
	        					$this->saveUserNotification($column_fields);
	        					$notified_users[] = $column_fields['assigned_user_id'];

	        				}
	        			}
	        		}
				}
			}
		}

		return $notified_users;
	}
	
	protected function saveUserNotification($column_fields) {
		global $adb, $table_prefix, $current_user;
		
		$ownerid = intval($column_fields['assigned_user_id']);
		$relatedTo = intval($column_fields['related_to']);
		
		$moduleName = 'ModNotifications';
		$obj = ModNotifications::getInstance();
		
		// check if this should be an edit, since the notifications for the same record are grouped together and only the last one is shown
		if ($relatedTo > 0) {
			
			$params = array($ownerid,$relatedTo);
			$query = 
				"SELECT MAX({$this->table_index}) AS {$this->table_index}
				FROM {$this->table_name}
				WHERE smownerid = ? AND related_to = ?";

			$res = $adb->pquery($query, $params);
			if ($res && $adb->num_rows($res) > 0) {
				// edit case!
				$modid = $adb->query_result_no_html($res, 0, $this->table_index);
				if ($modid > 0) {
					$obj->retrieve_entity_info($modid,$moduleName);
					$obj->column_fields['seen'] = 'off';	// forse unseen status
					$obj->column_fields['description'] = ''; //crmv@181842
					$obj->column_fields['creator'] = $current_user->id; //crmv@182384 update the sender of the notification
					$obj->mode = 'edit';
					$obj->id = $modid;
				}
			}
		}
		
		foreach ($column_fields as $key => $value) {
			$obj->column_fields[$key] = $value;
		}
		$obj->save();

		if ($obj->mode == 'edit') {
			// set the created time as if it was created now
			//crmv@183346 crmv@184676
			$params = array();
			$update_smcreatorid = '';
			if (in_array($column_fields['mod_not_type'],array('ListView changed','Reminder calendar'))) {
				$update_smcreatorid = ', smcreatorid = smownerid'; // crmv@182384
			}
			$params[] = $obj->id;
			$adb->pquery("UPDATE {$this->table_name} SET createdtime = modifiedtime {$update_smcreatorid} WHERE {$this->table_index} = ?", $params);
			//crmv@183346e crmv@184676e
		}
	}
	// crmv@102955e

	function saveRelatedModuleNotification($crmid, $module, $relcrmid, $with_module) {
		$skip_modules = array('ModNotifications','Targets'); // crmv@164120
		if (in_array($module,$skip_modules) || in_array($with_module,$skip_modules)) {
			return;
		}
		require_once('modules/ChangeLog/ChangeLogHandler.php');
		global $adb, $current_user,$table_prefix;
		$already_notified_users = array();

		$modified_date = date('Y-m-d H:i:s');
		$recordName = getEntityName($module,$crmid);
		$entityType = getSingleModuleName($module,$crmid); //crmv@32334
		$rel_recordName = getEntityName($with_module,$relcrmid);
		$rel_entityType = getSingleModuleName($with_module,$relcrmid); //crmv@32334

		//notifico la relazione come modifica del record $crmid - i
		$following_users = $this->getFollowingUsers($crmid);
		if (!empty($following_users)) {
			foreach($following_users as $following_user) {
				if ($current_user->id != $following_user) {
					if ($this->isEnabled($module,$crmid,$following_user) && $this->isEnabled($with_module,$relcrmid,$following_user)) {
						$notified_users = $this->saveFastNotification(
							array(
								'assigned_user_id' => $following_user,
								'related_to' => $crmid,
								'mod_not_type' => 'Relation',
								'description' => (string)$relcrmid, //crmv@73685
							)
						);
						if(!empty($notified_users)) {
							foreach($notified_users as $notified_user) {
								$already_notified_users[] = $notified_user;
							}
						}
					}
				}
			}
		}
		$focusCrmid = CRMEntity::getInstance($module);
		$focusCrmid->retrieve_entity_info($crmid,$module);
		$interested_users = $this->getInterestedToModuleUsers('edit',$module);
		$users = array();
		$result = $adb->pquery('SELECT id FROM '.$table_prefix.'_users WHERE id = ? AND deleted = 0 AND status = ?', array($focusCrmid->column_fields['assigned_user_id'],'Active'));
		if ($result && $adb->num_rows($result) > 0) {
			$users[] = $focusCrmid->column_fields['assigned_user_id'];
		} else {
			$result = $adb->pquery('SELECT groupid FROM '.$table_prefix.'_groups WHERE groupid = ?', array($focusCrmid->column_fields['assigned_user_id']));
			if($result && $adb->num_rows($result) > 0) {
				$groupid = $adb->query_result($result,0,'groupid');
				require_once('include/utils/GetGroupUsers.php');
				$focus = new GetGroupUsers();
        		$focus->getAllUsersInGroup($groupid);
        		$group_users = $focus->group_users;
        		if (!empty($group_users)) {
        			$group_users_str = implode(',',$group_users);
        			$result = $adb->pquery('select id from '.$table_prefix.'_users where id in ('.$group_users_str.') and deleted = 0 and status = ?',array('Active'));
        			if($result && $adb->num_rows($result) > 0) {
        				while($row=$adb->fetchByAssoc($result)) {
        					$users[] = $row['id'];
        				}
        			}
        		}
			}
		}
		foreach($interested_users as $interested_user) {
			if (in_array($interested_user,$already_notified_users)) {
				continue;
			}
			if (in_array($interested_user,$users) && $interested_user != $current_user->id) {
				$notified_users = $this->saveFastNotification(
					array(
						'assigned_user_id' => $interested_user,
						'related_to' => $crmid,
						'mod_not_type' => 'Relation',
						'description' => (string)$relcrmid, //crmv@73685
					),false
				);
				if(!empty($notified_users)) {
					foreach($notified_users as $notified_user) {
						$already_notified_users[] = $notified_user;
					}
				}
			}
		}
		// crmv@164120
		$obj = ChangeLog::getInstance();
		$obj->column_fields['modified_date'] = $modified_date;
		$obj->column_fields['audit_no'] = $obj->get_revision_id($crmid);
		$obj->column_fields['user_id'] = $current_user->id;
		$obj->column_fields['parent_id'] = $crmid;
		$obj->column_fields['user_name'] = $current_user->column_fields['user_name'];
		// crmv@164655
		$name = getEntityName($with_module, array($relcrmid), true);
		$clogData = array('ChangeLogRelationNN',$relcrmid,$with_module,$crmid,$module,$name);
		$obj->column_fields['description'] = Zend_Json::encode($clogData);	//crmv@104566
		// crmv@164655e		$obj->save(); //crmv@47905
		// crmv@164120e
		//notifico la relazione come modifica del record $crmid - e

		//notifico la relazione come modifica del record $relcrmid - i
		$following_users = $this->getFollowingUsers($relcrmid);
		if (!empty($following_users)) {
			foreach($following_users as $following_user) {
				if ($current_user->id != $following_user && !in_array($following_user,$already_notified_users)) {
					if ($this->isEnabled($module,$crmid,$following_user) && $this->isEnabled($with_module,$relcrmid,$following_user)) {
						$notified_users = $this->saveFastNotification(
							array(
								'assigned_user_id' => $following_user,
								'related_to' => $relcrmid,
								'mod_not_type' => 'Relation',
								'description' => (string)$crmid, //crmv@73685
							)
						);
						if(!empty($notified_users)) {
							foreach($notified_users as $notified_user) {
								$already_notified_users[] = $notified_user;
							}
						}
					}
				}
			}
		}
		$focusRelcrmid = CRMEntity::getInstance($with_module);
		$focusRelcrmid->retrieve_entity_info($relcrmid,$with_module);
		$interested_users = $this->getInterestedToModuleUsers('edit',$with_module);
		$users = array();
		$result = $adb->pquery('SELECT id FROM '.$table_prefix.'_users WHERE id = ? AND deleted = 0 AND status = ?', array($focusRelcrmid->column_fields['assigned_user_id'],'Active'));
		if ($result && $adb->num_rows($result) > 0) {
			$users[] = $focusRelcrmid->column_fields['assigned_user_id'];
		} else {
			$result = $adb->pquery('SELECT groupid FROM '.$table_prefix.'_groups WHERE groupid = ?', array($focusRelcrmid->column_fields['assigned_user_id']));
			if($result && $adb->num_rows($result) > 0) {
				$groupid = $adb->query_result($result,0,'groupid');
				require_once('include/utils/GetGroupUsers.php');
				$focus = new GetGroupUsers();
        		$focus->getAllUsersInGroup($groupid);
        		$group_users = $focus->group_users;
        		if (!empty($group_users)) {
        			$group_users_str = implode(',',$group_users);
        			$result = $adb->pquery('select id from '.$table_prefix.'_users where id in ('.$group_users_str.') and deleted = 0 and status = ?',array('Active'));
        			if($result && $adb->num_rows($result) > 0) {
        				while($row=$adb->fetchByAssoc($result)) {
        					$users[] = $row['id'];
        				}
        			}
        		}
			}
		}
		foreach($interested_users as $interested_user) {
			if (in_array($interested_user,$already_notified_users)) {
				continue;
			}
			if (in_array($interested_user,$users) && $interested_user != $current_user->id) {
				$notified_users = $this->saveFastNotification(
					array(
						'assigned_user_id' => $interested_user,
						'related_to' => $relcrmid,
						'mod_not_type' => 'Relation',
						'description' => (string)$crmid, //crmv@73685
					),false
				);
				if(!empty($notified_users)) {
					foreach($notified_users as $notified_user) {
						$already_notified_users[] = $notified_user;
					}
				}
			}
		}
		// crmv@164120
		$obj = ChangeLog::getInstance();
		$obj->column_fields['modified_date'] = $modified_date;
		$obj->column_fields['audit_no'] = $obj->get_revision_id($relcrmid);
		$obj->column_fields['user_id'] = $current_user->id;
		$obj->column_fields['parent_id'] = $relcrmid;
		$obj->column_fields['user_name'] = $current_user->column_fields['user_name'];
		// crmv@164655
		$name = getEntityName($module, array($crmid), true);
		$clogData = array('ChangeLogRelationNN',$crmid,$module,$relcrmid,$with_module,$name);
		$obj->column_fields['description'] = Zend_Json::encode($clogData);	//crmv@104566
		// crmv@164655e		$obj->save(); //crmv@47905
		// crmv@164120e
		//notifico la relazione come modifica del record $relcrmid - e
	}
	
	//crmv@183346
	function getBodyNotification($id,$column_fields,$signature,$only_content=false) {
		global $site_URL,$adb,$current_user,$current_language,$default_language,$table_prefix;
		$default_language_tmp = $default_language;
		$current_language_tmp = $current_language;

		$body = '';
		$user = CRMEntity::getInstance('Users');
		$user->retrieve_entity_info($column_fields['assigned_user_id'],'Users');
		$current_language = $default_language = $user->column_fields['default_language'];
		$related_to = $column_fields['related_to'];
		$type = $column_fields['mod_not_type'];
		$related_module = getSalesEntityType($related_to);
		$creator = getSingleFieldValue("{$table_prefix}_modnotifications", 'smcreatorid', 'modnotificationsid', $id); //crmv@182384

		if (!$only_content) {
			$body = getTranslatedString('MSG_DEAR','ModNotifications').' '.getUserFullName($user->id).',<br />';
		}

		$recordName = getEntityName($related_module,$related_to);
		$entityType = getSingleModuleName($related_module,$related_to); //crmv@32334

		if ($only_content) {
			require_once('modules/ModNotifications/models/Comments.php');
			$model = new ModNotifications_CommentsModel($column_fields);
			$body .= $model->timestampAgo().' ';
		}

		if ($type == 'Generic') {
			$body .= '<br>'.$column_fields['description'];
			if (!empty($related_to)) $body .= ' '.getTranslatedString('LBL_ABOUT','ModComments');
		} else {
			$body .= trim(getUserFullName($creator).' '.strtolower($this->translateNotificationType($type,'action'))); //crmv@182384
		}
		
		if ($type == 'Relation') {
			$relation_parent_id = $column_fields['description'];
			$relation_parent_module = getSalesEntityType($relation_parent_id);
			$relation_recordName = getEntityName($relation_parent_module,$relation_parent_id);
			$relation_entityType = getSingleModuleName($relation_parent_module,$relation_parent_id);
			$body .= " <a href='{$site_URL}/index.php?module=$relation_parent_module&action=DetailView&record=$relation_parent_id' title='$relation_entityType'>".$relation_recordName[$relation_parent_id]."</a> ($relation_entityType) ";
			$body .= getTranslatedString('LBL_TO','ModComments');
		}
		
		if (!empty($related_to)) $body .= " <a href='{$site_URL}/index.php?module=$related_module&action=DetailView&record=$related_to' title='$entityType'>".$recordName[$related_to]."</a> ($entityType).";

		if(in_array($column_fields['mod_not_type'],array('Changed followed record', 'Changed record'))) {
			if ($only_content) {	// only in summary mail because last changelog is not saved at this time
				$body .= '<br /><br />'.getTranslatedString('MSG_DETAILS_OF','ModNotifications').' <b>'.$recordName[$related_to].'</b> '.getTranslatedString('MSG_DETAILS_ARE','ModNotifications');
				
				// crmv@164120
				$q = "SELECT changelogid, description 
					FROM {$table_prefix}_changelog
					WHERE parent_id = ? AND hide = 0
					ORDER BY changelogid DESC ";	//crmv@135193
				$ress = $adb->limitpQuery($q,0,1,array($related_to));
				// crmv@164120e

				$changelogid = $adb->query_result_no_html($ress,0,"changelogid");
				$description = $adb->query_result_no_html($ress,0,"description");
				$description_elements = Zend_Json::decode($description);
				$ChangeLogFocus = ChangeLog::getInstance(); // crmv@164120

				$body .= $ChangeLogFocus->getFieldsTable($description, $related_module);
			}
		} elseif (!empty($related_to)) {
			$body .= '<br /><br />'.getTranslatedString('MSG_DETAILS_OF','ModNotifications').' <b>'.$recordName[$related_to].'</b> '.getTranslatedString('MSG_DETAILS_ARE','ModNotifications').'<br />';
			
			$focus = CRMEntity::getInstance($related_module);
			if(!isRecordExists($related_to)) return ''; //crmv@33364
			$focus->retrieve_entity_info($related_to,$related_module);
			$qcreate_array = QuickCreate($related_module);
			$query = "select fieldname from {$table_prefix}_entityname where modulename = ?";
			$result = $adb->pquery($query, array($related_module));
			if ($result && $adb->num_rows($result) > 0) {
				if(strpos($adb->query_result($result,0,'fieldname'),',') !== false) {
					$fieldlists = explode(',',$adb->query_result($result,0,'fieldname'));
				} else {
					$fieldlists = array($adb->query_result($result,0,'fieldname'));
				}
				foreach($fieldlists as $field) {
					unset($qcreate_array['data'][$field]);
				}
			}
			$fieldnames = array_keys($qcreate_array['data']);
			if (!empty($fieldnames)) {
				$result = $adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname in ('.generateQuestionMarks($fieldnames).') order by quickcreatesequence',array(getTabid($related_module),$fieldnames));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						//crmv@62727
						$current_user_bkp = $current_user;
						$user_bkp = $user;
						$current_user = $user;
						$field_visible = getFieldVisibilityPermission($related_module, $current_user->id, $row['fieldname']);
						$current_user = $current_user_bkp;
						$user = $user_bkp;
						if ($field_visible == '0') {
						//crmv@62727e
							$info = getDetailViewOutputHtml($row['uitype'],$row['fieldname'],$row['fieldlabel'],$focus->column_fields,$row['generatedtype'],$row['tabid'],$related_module);
							if ($info[1] != '') {
								if ($row['uitype'] == 19) {	//TODO: controllo piu' accurato
									$body .= '<b>'.$info[0].'</b>: '.$info[1].'<br />';
								} else {
									$body .= '<b>'.$info[0].'</b>: '.strip_tags($info[1]).'<br />';
								}
							}
						} //crmv@62727
					}
				}
			}
		}

		if (!empty($related_to)) $body .= "<a href='{$site_URL}/index.php?module=$related_module&action=DetailView&record=$related_to' title='".$recordName[$relation_parent_id]." ($entityType)'>".getTranslatedString('MSG_OTHER_INFO','ModNotifications')."</a>";

		if ($type == 'Relation') {
			$relation_focus = CRMEntity::getInstance($relation_parent_module);
			if(!isRecordExists($relation_parent_id)) return ''; //crmv@33364
			$relation_focus->retrieve_entity_info($relation_parent_id,$relation_parent_module);

			$qcreate_array = QuickCreate($relation_parent_module);
			$query = "select fieldname from {$table_prefix}_entityname where modulename = ?";
			$result = $adb->pquery($query, array($relation_parent_module));
			if ($result && $adb->num_rows($result) > 0) {
				if(strpos($adb->query_result($result,0,'fieldname'),',') !== false) {
					$fieldlists = explode(',',$adb->query_result($result,0,'fieldname'));
				} else {
					$fieldlists = array($adb->query_result($result,0,'fieldname'));
				}
				foreach($fieldlists as $field) {
					unset($qcreate_array['data'][$field]);
				}
			}
			$fieldnames = array_keys($qcreate_array['data']);
			if (!empty($fieldnames)) {
				$body .= '<br /><br />'.getTranslatedString('MSG_DETAILS_OF','ModNotifications').' <b>'.$relation_recordName[$relation_parent_id].'</b> '.getTranslatedString('MSG_DETAILS_ARE','ModNotifications').'<br />';

				$result = $adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname in ('.generateQuestionMarks($fieldnames).')',array(getTabid($relation_parent_module),$fieldnames));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$info = getDetailViewOutputHtml($row['uitype'],$row['fieldname'],$row['fieldlabel'],$relation_focus->column_fields,$row['generatedtype'],$row['tabid'],$relation_parent_module);
						if ($info[1] != '') {
							$body .= '<b>'.$info[0].'</b>: '.strip_tags($info[1]).'<br />';
						}
					}
				}

				$body .= "<a href='{$site_URL}/index.php?module=$relation_parent_module&action=DetailView&record=$relation_parent_id' title='".$relation_recordName[$relation_parent_id]." ($relation_entityType)'>".getTranslatedString('MSG_OTHER_INFO','ModNotifications')."</a>";
			}
		}
		if (!$only_content) {
			$body .= '<br /><br />'.getTranslatedString('LBL_REGARDS','HelpDesk').',<br />'.$signature;
		}

		$default_language = $default_language_tmp;
		$current_language = $current_language_tmp;

		return $body;
	}
	//crmv@183346e
	
	function getBodyNotificationCV($id,$column_fields,$signature,$only_content=false) {
		global $site_URL,$adb,$current_user,$current_language,$default_language,$table_prefix;
		$default_language_tmp = $default_language;
		$current_language_tmp = $current_language;

		$body = '';
		$user = CRMEntity::getInstance('Users');
		$user->retrieve_entity_info($column_fields['assigned_user_id'],'Users');
		$current_language = $default_language = $user->column_fields['default_language'];
		$related_to = $column_fields['related_to'];
		$type = $column_fields['mod_not_type'];

		if (!$only_content) {
			$body = getTranslatedString('MSG_DEAR','ModNotifications').' '.getUserFullName($user->id).',<br />';
		}

		if ($only_content) {
			require_once('modules/ModNotifications/models/Comments.php');
			$model = new ModNotifications_CommentsModel($column_fields);
			$body .= $model->timestampAgo().' ';
		}
		$body .= strtolower($this->translateNotificationType($type,'action'));

		$result = $adb->query('SELECT * FROM '.$table_prefix.'_customview WHERE cvid = '.$related_to);
		if ($result) {
			$related_module = $adb->query_result($result,0,'entitytype');
			$entityType = getTranslatedString($related_module,$related_module);
			$recordName = $adb->query_result($result,0,'viewname');
			if ($recordName == 'All') {
				$recordName = getTranslatedString('COMBO_ALL');
			} elseif($this->parent_module == 'Calendar' && in_array($recordName,array('Events','Tasks'))) {
				$recordName = getTranslatedString($recordName);
			}
			$body .= " <a href='{$site_URL}/index.php?module=$related_module&action=index&viewname=$related_to' title='$entityType' target='_parent'>$recordName</a> ($entityType)";
		}

		$body .= '&nbsp;:<br />';
		$changes = array_filter(explode(',',$column_fields['description']));
		if (!empty($changes)) {
			$body_changes = array(); // crmv@198449
			foreach($changes as $change_id) {
				$change_module = getSalesEntityType($change_id);
				$displayValueArray = getEntityName($change_module,$change_id);
				if(!empty($displayValueArray)){
					foreach($displayValueArray as $key=>$value){
						$displayValue = $value;
					}
				}
				$body_changes[] = "<a href='{$site_URL}/index.php?module=$change_module&action=DetailView&record=$change_id' target='_parent'>$displayValue</a>";
			}
			$body .= implode(', ',$body_changes);
		}

		if (!$only_content) {
			$body .= '<br /><br />'.getTranslatedString('LBL_REGARDS','HelpDesk').',<br />'.$signature;
		}

		$default_language = $default_language_tmp;
		$current_language = $current_language_tmp;
		return $body;
	}
	
	//crmv@33364
	function sendNotificationSummary($userid) {
		global $current_user, $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $current_language, $default_language;
		$current_user_tmp = $current_user;
		$current_user = CRMEntity::getInstance('Users');
		$current_user->retrieve_entity_info($userid,'Users');
		$current_language = $default_language = $current_user->column_fields['default_language'];

		$widgetInstance = $this->getWidget('DetailViewBlockCommentWidget');
		$unseen = $widgetInstance->getUnseenComments('',array('ID'=>''));
		if (!empty($unseen)) {
			$send_mail = false;
			$notification_details = array();
			$notification_sents = array();
			foreach($unseen as $not_id) {
				$not_focus = CRMEntity::getInstance('ModNotifications');
				$not_focus->retrieve_entity_info($not_id,'ModNotifications');
				$not_focus->column_fields['smcreatorid'] = $not_focus->column_fields['creator'];
				$limit_time = $this->notification_summary_values[$current_user->column_fields['notify_summary']];
				//echo $limit_time.' '.$not_id.': '.strtotime($not_focus->column_fields['createdtime']).' <= '.strtotime($limit_time).' : '.date('Y-m-d H:i:s',strtotime($not_focus->column_fields['createdtime'])).' <= '.date('Y-m-d H:i:s',strtotime($limit_time));
				if ($not_focus->column_fields['sent_summary_not'] != 1) {	//controllo se non ho gia' inviato la notifica
					if (strtotime($not_focus->column_fields['createdtime']) <= strtotime($limit_time)) {	//mi basta che ci sia una notifica non letta da piu' di X tempo per notificare via mail tutte le notifiche non lette
						$send_mail = true;
					}
					if ($not_focus->column_fields['mod_not_type'] == 'ListView changed') {
						$notification_details[] = $not_focus->getBodyNotificationCV($not_id,$not_focus->column_fields,$HELPDESK_SUPPORT_NAME,true).'<br />';
					} else {
						$notification_details[] = $not_focus->getBodyNotification($not_id,$not_focus->column_fields,$HELPDESK_SUPPORT_NAME,true).'<br />';
					}
					$notification_sents[] = $not_id;
				}
			}
			if ($send_mail && !empty($notification_details)) {
				$unseen_count = count($notification_sents);
				require_once('modules/Emails/mail.php');
				$subject = getTranslatedString('ModNotifications','Users').' '.getTranslatedString('Notification Summary','Users').' '.getTranslatedString('unseen','ModNotifications').' ('.$unseen_count.')';
				$body = getTranslatedString('MSG_DEAR','ModNotifications').' '.getUserFullName($current_user->id).',<br />';
				if ($unseen_count == 1) {
					$body .= getTranslatedString('MSG_1_NOTIFICATION_UNSEEN','ModNotifications').'.<br /><br />';
				} else {
					$body .= sprintf(getTranslatedString('MSG_NOTIFICATIONS_UNSEEN','ModNotifications'),$unseen_count).'.<br /><br />';
				}
				$body .= implode('<br /><br />',array_filter($notification_details));
				$body .= '<br /><br />'.getTranslatedString('LBL_REGARDS','HelpDesk').',<br />'.$HELPDESK_SUPPORT_NAME;
				$mail_status = send_mail('ModNotifications',$current_user->column_fields['email1'],$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$body);
			}
			if ($mail_status == 1) {
				if (!empty($notification_sents)) {
					foreach ($notification_sents as $notification_sent) {
						$focus = CRMEntity::getInstance('ModNotifications');
						$focus->retrieve_entity_info_no_html($notification_sent,'ModNotifications'); //crmv@183346
						$focus->mode = 'edit';
						$focus->id = $notification_sent;
						$focus->column_fields['sent_summary_not'] = 1;
						$focus->save('ModNotifications');
					}
				}
			}
		}
		$current_user = $current_user_tmp;
	}
	//crmv@33364e

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	//crmv@32429	crmv@54005
	function getListQuery($module, $where='', $skip_parent_join=false, $join='') {
		if (!$skip_parent_join) {
			return parent::getListQuery($module, $where);
		}
		global $current_user;
		
		$query = "SELECT ".$this->getListQuerySelect();
		$query .= " FROM $this->table_name";
		$query .= $join;
		$query .= "	WHERE smownerid = {$current_user->id} ".$where;
		
		return $query;
	}
	
	function getListQuerySelect() {		
		$select = "{$this->table_name}.*";
		return $select;
	}
	//crmv@32429e	crmv@54005e
	
	//crmv@64325
	function getUnseenCount() {
		global $current_user;
		// TODO use cache and clear it when I change the seen flag or when there is a new notification
		$widgetInstance = $this->getWidget('DetailViewBlockCommentWidget');
		// crmv@43520
		if ($widgetInstance) {
			$widgetInstance->setDefaultCriteria(0);
			//crmv@176619
			$count = 'yes';
			$widgetInstance->getModels('', $usecriteria, $count, true, 'no');
			$unseenCount = $count;
			//crmv@176619e
		}
		// crmv@43520e
		return $unseenCount;
	}
	//crmv@64325e
	
	function getAdvancedPermissionFunction($is_admin,$module,$actionname,$record_id='') {
		global $current_user;
		$actionid = getActionid($actionname);
		if ($actionid === '' || $actionid === null) {
			$permission = 'yes';
		} elseif (is_admin($current_user) == true) {
			$permission = 'yes';
		} else {
			$permission = 'no';
			switch ($actionid) {
				case 0: // save
				case 1: // editview
					if (empty($record_id) || $this->canUserEditRecord($record_id)) $permission = 'yes';
					break;
				case 2: // delete
					break;
				case 3: // index
					break;
				case 4: // detailview
					if (empty($record_id) || $this->canUserSeeRecord($record_id)) $permission = 'yes';
					break;
				default:
					break;
			}
		}
		return $permission;
	}
}