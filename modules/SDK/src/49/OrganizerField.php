<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@187823 */

class OrganizerField {

	protected $module;
	protected $fieldname;
	protected $tablename;
	protected $tableindex;
	
	protected static $instances = array();

	public static function getInstance($module, $fieldname) {
		$cachekey = "{$module}_{$fieldname}";
		
		if (!array_key_exists($cachekey, self::$instances)) {
			self::$instances[$cachekey] = new self($module, $fieldname);
		}
		
		return self::$instances[$cachekey];
	}
	
	public function __construct($module, $fieldname) {
		global $table_prefix;
		$this->module = $module;
		$this->fieldname = $fieldname;
		
		// for performance, calendar is hardcoded
		if ($module == 'Calendar' || $module == 'Events' || $module == 'Activity') {
			$this->tablename = $table_prefix.'_activity_organizer';
			$this->tableindex = 'activityid';
		} else {
			$inst = CRMEntity::getInstance($module);
			$this->tablename = getFieldTable($module, $fieldname);
			$this->tableindex = $inst->tab_name_index[$this->tablename];
		}
	}
	
	public function getValue($recordid) {
		global $adb;
		
		$res = $adb->pquery("SELECT userid, contactid, email FROM {$this->tablename} WHERE {$this->tableindex} = ?", array($recordid));
		$row = $adb->FetchByAssoc($res, -1, false);
		if (empty($row)) {
			$orgType = '';
			$orgValue = '';
			$simUitype = 1;
		} else {
			if ($row['userid'] > 0) {
				$orgType = 'Users';
				$orgValue = $row['userid'];
				$simUitype = 51;
			} elseif ($row['contactid'] > 0) {
				$orgType = 'Contacts';
				$orgValue = $row['contactid'];
				$simUitype = 10;
			} elseif ($row['email'] != '') {
				$orgType = 'Other';
				$orgValue = $row['email'];
				$simUitype = 13;
			} else {
				$orgType = '';
				$orgValue = '';
				$simUitype = 1;
			}
		}
		
		$value = array(
			'type' => $orgType,
			'value' => $orgValue,
			'simulate_uitype' => $simUitype,
		);
		
		return $value;
	}
	
	public function getDisplayValue(array $value) {
		global $current_user, $showfullusername;
		
		$orgType = $value['type'];
		$orgValue = $value['value'];
		
		$display = '';
		if ($orgType == 'Users') {
			$user_name = getUserName($orgValue, $showfullusername);
			if (is_admin($current_user)) {
				$display = '<a href="index.php?module=Users&action=DetailView&record='.$orgValue.'">'.$user_name.'</a>';
			} else {
				$display = $user_name;
			}
		} elseif ($orgType == 'Contacts') {
			$name = getEntityName('Contacts', $orgValue, true);
			$display = '<a href="index.php?module=Contacts&action=DetailView&record='.$orgValue.'">'.$name.'</a>';
		} elseif ($orgType == 'Other') {
			$display = $orgValue;
		} else {
			$display = '';
		}
		
		return $display;
	}
	
	public function setValue($recordid, array $value) {
		global $adb;
		
		$colTypes = array('Users' => 'userid', 'Contacts' => 'contactid', 'Other' => 'email');
		$col = $colTypes[$value['type']];
		if ($col) {
			$adb->pquery("UPDATE {$this->tablename} SET $col = ? WHERE {$this->tableindex} = ?", array($value['value'], $recordid));
		}
	}
	
	/**
	 * Check if the passed user matches the organizer field of the $record
	 * @param int $userid The id of the user
	 * @param int $record the crmid of the event
	 * @return bool
	 */
	public function compareUser($userid, $record) {
		$val = $this->getValue($record);
		
		if ($val['type'] == 'Users') {
			// match userid
			return $userid == $val['value'];
		} else {
			// match email
			$userEmail = getUserEmail($userid);
			if ($val['type'] == 'Contacts') {
				$ED = new EmailDirectory();
				$orgEmail = $ED->getAnyEmail($val['value']);
			} else {
				$orgEmail = $val['value'];
			}
			
			if ($orgEmail) {
				return (strcasecmp($userEmail, $orgEmail) === 0);
			}
		}
		
		return false;
	}
	
}