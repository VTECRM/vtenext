<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');
require_once 'vtlib/Vtecrm/Module.php';//crmv@207871

class ModCommentsCore extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'modcommentsid';

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array();

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array();

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Comment' => Array('modcomments', 'commentcontent'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Comment' => 'commentcontent',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view
	var $list_link_field = 'commentcontent';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vte_'
		'Comment' => Array('modcomments', 'commentcontent')
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Comment' => 'commentcontent'
	);

	// For Popup window record selection
	var $popup_fields = Array ('commentcontent');

	// Allow sorting on the following (field column names)
	var $sortby_fields = Array ('commentcontent');

	// Should contain field labels
	//var $detailview_links = Array ('Comment');

	// For Alphabetical search
	var $def_basicsearch_col = 'commentcontent';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'commentcontent';

	// Required Information for enabling Import feature
	var $required_fields = Array ('assigned_user_id'=>1);

	var $default_order_by = 'commentcontent';
	var $default_sort_order='ASC';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'commentcontent');

	//crmv@10759
	var $search_base_field = 'commentcontent';
	//crmv@10759 e
	
	// these characters are removed before saving the comment
	public $badChars = array("\xe2\x80\xa8", "\xe2\x80\xa9"); // crmv@154947

	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_modcomments';
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity' => 'crmid',
			$table_prefix.'_modcomments' => 'modcommentsid',
			$table_prefix.'_modcommentscf'=>'modcommentsid');
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_modcomments', $table_prefix.'_modcommentscf');
		$this->customFieldTable = Array($table_prefix.'_modcommentscf', 'modcommentsid');
		$this->column_fields = getColumnFields('ModComments'); //crmv@146187 crmv@193294
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}
	
	// crmv@154947
	function presaveAlterValues() {
		parent::presaveAlterValues();
		
		// remove dangerous characters
		if (is_string($this->column_fields['commentcontent']) && !empty($this->column_fields['commentcontent']) && !empty($this->badChars)) {
			$this->column_fields['commentcontent'] = str_replace($this->badChars, '', $this->column_fields['commentcontent']);
		}
	}
	// crmv@154947e

	function save_module($module) {
		global $current_user, $adb,$table_prefix;
		require_once('modules/SDK/src/Notifications/Notifications.php');

		//crmv@2963m
		if (getSalesEntityType($this->column_fields['related_to']) == 'Messages') {
			$result = $adb->pquery("SELECT messagehash FROM {$table_prefix}_messages WHERE messagesid = ?",array($this->column_fields['related_to']));
			if ($result && $adb->num_rows($result) > 0) {
				$messagehash = $adb->query_result($result,0,'messagehash');
				if ($adb->isMysql()) {
					$adb->pquery("insert ignore into {$table_prefix}_messagesrel (messagehash,crmid,module) values (?,?,?)",array($messagehash,$this->id,'ModComments'));
				} else {
					$result = $adb->pquery("select * from {$table_prefix}_messagesrel where messagehash = ? and crmid = ?",array($messagehash,$this->id));
					if (!$result || $adb->num_rows($result) == 0) {
						$adb->pquery("insert into {$table_prefix}_messagesrel (messagehash,crmid,module) values (?,?,?)",array($messagehash,$this->id,'ModComments'));
					}
				}
			}

			// crmv@63349 - insert into the special messages-relation table
			if (empty($this->column_fields['parent_comments'])) { // crmv@109127
				$this->insertIntoMsgRelTable($this->column_fields['related_to']);
			}
			// crmv@63349e
		}
		//crmv@2963me

		if (!in_array($this->column_fields['parent_comments'],array('',0))) {
			//reply

			$focusFather = CRMEntity::getInstance('ModComments');
			$focusFather->retrieve_entity_info($this->column_fields['parent_comments'],'ModComments');

			$users_to_notify = array();
			if ($focusFather->column_fields['visibility_comm'] == 'All') {
				/*
				 //notifico a ( (assegnatario commento padre + utenti che hanno risp almeno una volta) - autore corrente )
				$result = $adb->pquery('SELECT smownerid FROM '.$table_prefix.'_modcomments INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_modcomments.modcommentsid WHERE deleted = 0 AND parent_comments = ?',array($this->column_fields['parent_comments']));
				if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['smownerid'],$users_to_notify)) {
				$users_to_notify[] = $row['smownerid'];
				}
				}
				}
				*/
				//notifico a (tutti gli utenti attivi e abilitati alle conversazioni pubbliche - autore corrente)
				$result = $adb->pquery('select id from '.$table_prefix.'_users where deleted = 0 and status = ? and receive_public_talks = ?',array('Active','1'));
				if ($result && $adb->num_rows($result) > 0) {
					while($row = $adb->fetchByAssoc($result)) {
						$users_to_notify[] = $row['id'];
					}
				}
			} elseif ($focusFather->column_fields['visibility_comm'] == 'Users') {
				//notifico a ( (assegnatario commento padre + utenti invitati) - autore corrente )
				$users_to_notify[] = $focusFather->column_fields['assigned_user_id'];
				$column = 'user';
				$adb->format_columns($column);
				$result = $adb->pquery('SELECT '.$column.' FROM '.$table_prefix.'_modcomments_users WHERE id = ?',array($this->column_fields['parent_comments']));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$users_to_notify[] = $row['user'];
					}
				}
			}
			$users_to_notify = array_filter($users_to_notify);
			foreach($users_to_notify as $user) {
				if ($user == $this->column_fields['assigned_user_id']) {
					continue;
				}
				$notifications = new Notifications($user,'ModComments');
				$notifications->addNotification($this->id);
			}
			$adb->pquery("update {$table_prefix}_modcomments set lastchild = ? where modcommentsid = ?", array($this->id, $this->column_fields['parent_comments'])); // crmv@53565
			$adb->pquery("update {$table_prefix}_crmentity set modifiedtime = ? where crmid = ?", array($adb->formatDate(date('Y-m-d H:i:s'), true), $this->column_fields['parent_comments'])); // crmv@49398 crmv@69690
		} elseif ($this->column_fields['visibility_comm'] == 'Users' && isset($_REQUEST['users_comm'])) {
			//new father comment to specified users

			$adb->pquery('delete from '.$table_prefix.'_modcomments_users where id = ?',array($this->id));
			$idlist = vtlib_purify($_REQUEST['users_comm']);
			if ($idlist != '') {
				$idlist = array_filter(explode('|',$idlist));
				$column = 'user';
				$adb->format_columns($column);
				foreach($idlist as $id) {
					$adb->pquery('insert into '.$table_prefix.'_modcomments_users (id,'.$column.') values (?,?)',array($this->id,$id));
					//notifico gli utenti invitati
					$notifications = new Notifications($id,'ModComments');
					$notifications->addNotification($this->id);
				}
			}
			$adb->pquery("update {$table_prefix}_modcomments set lastchild = ? where modcommentsid = ?", array($this->id, $this->id)); // crmv@53565
		} elseif ($this->column_fields['visibility_comm'] == 'All') {
			//new father comment to all

			//notifico a (tutti gli utenti attivi e abilitati alle conversazioni pubbliche - autore corrente)
			$result = $adb->pquery('select id from '.$table_prefix.'_users where deleted = 0 and status = ? and receive_public_talks = ?',array('Active','1'));
			if ($result && $adb->num_rows($result) > 0) {
				while($row = $adb->fetchByAssoc($result)) {
					if ($row['id'] == $this->column_fields['assigned_user_id']) {
						continue;
					}
					$notifications = new Notifications($row['id'],'ModComments');
					$notifications->addNotification($this->id);
				}
			}
			$adb->pquery("update {$table_prefix}_modcomments set lastchild = ? where modcommentsid = ?", array($this->id, $this->id)); // crmv@53565
		}
	}

	// crmv@43050 - add users to the current
	function addUsers($userlist = array()) {
		global $adb, $table_prefix;
		require_once('modules/SDK/src/Notifications/Notifications.php');

		$commentid = $this->id;

		if (!is_array($userlist)) $userlist = array($userlist);

		if (count($userlist) == 0 || $this->column_fields['visibility_comm'] != 'Users') return false;

		$column = 'user';
		$adb->format_columns($column);

		$addedUsers = false;
		foreach ($userlist as $userid) {
			// check if present
			$res = $adb->pquery("select id from {$table_prefix}_modcomments_users where id = ? and $column = ?", array($commentid,$userid));
			if ($res && $adb->num_rows($res) == 0) {
				// if not, add and notify
				$adb->pquery("insert into {$table_prefix}_modcomments_users (id,$column) values (?,?)", array($commentid,$userid));

				// notifico gli utenti invitati
				$notifications = new Notifications($userid,'ModComments');
				$notifications->addNotification($commentid);
				$addedUsers = true;
				
				// crmv@63349 - add to the relation table
				if ($this->column_fields['related_to'] > 0 && getSalesEntityType($this->column_fields['related_to']) == 'Messages') { // crmv@109127
					if ($adb->isMysql()) {
						$adb->pquery("insert ignore into {$table_prefix}_modcomments_msgrel (userid, messagesid) values (?,?)",array($userid,$this->column_fields['related_to']));
					} else {
						$result = $adb->pquery("select userid from {$table_prefix}_modcomments_msgrel where userid = ? and messagesid = ?",array($userid,$this->column_fields['related_to']));
						if ($result && $adb->num_rows($result) == 0) {
							$adb->pquery("insert into {$table_prefix}_modcomments_msgrel (userid, messagesid) values (?,?)",array($userid,$this->column_fields['related_to']));
						}
					}
				}
				// crmv@63349e
			}
		}
		if ($addedUsers) {
			// update modified time
			$adb->pquery("update {$table_prefix}_crmentity set modifiedtime = ? where crmid = ?", array(date('Y-m-d H:i:s'), $commentid));
		}
		return true;
	}
	// crmv@43050e
	
	//crmv@179773
	function checkParentPermissions($related_to, $commentid='', $visibility='', $users=array()) {
		global $adb, $table_prefix, $current_user;
		
		if ($this->isEnabledParentPermissionsProp()) {
			if (!empty($commentid)) {
				$visibility = getSingleFieldValue("{$table_prefix}_modcomments", 'visibility_comm', 'modcommentsid', $commentid);
				if ($visibility == 'Users' && empty($users)) {
					$result = $adb->pquery("SELECT user FROM {$table_prefix}_modcomments_users WHERE id = ?", array($commentid));
					if ($result && $adb->num_rows($result) > 0) {
						while($row=$adb->fetchByAssoc($result)) {
							$users[] = $row['user'];
						}
					}
				}
			}
			
			$tmp_current_user = $current_user;
			$parent_module = getSalesEntityType($related_to);
			if ($visibility == 'All') {
				$result = $adb->pquery('select id from '.$table_prefix.'_users where deleted = 0 and status = ? and receive_public_talks = ?',array('Active','1'));
				if ($result && $adb->num_rows($result) > 0) {
					while($row = $adb->fetchByAssoc($result,-1,false)) {
						$current_user = CRMEntity::getInstance('Users');
						$current_user->retrieveCurrentUserInfoFromFile($row['id']);
						if (isPermitted($parent_module, 'DetailView', $related_to) == 'no') return false;
					}
				}
			} elseif ($visibility == 'Users' && !empty($users)) {
				foreach($users as $user) {
					$current_user = CRMEntity::getInstance('Users');
					$current_user->retrieveCurrentUserInfoFromFile($user);
					if (isPermitted($parent_module, 'DetailView', $related_to) == 'no') return false;
				}
			}
			$current_user = $tmp_current_user;
		}
		return true;
	}
	function isParentPermitted($module,$actionname,$record_id) {
		global $adb, $table_prefix, $current_user;
		$permission = '';
		
		$VTEP = VTEProperties::getInstance();
		if ($VTEP->getProperty('performance.modcomments_parent_perm')) {
			$result = $adb->pquery("select max(related_to_perm) as \"related_to_perm\"
					from {$this->table_name}
					inner join {$table_prefix}_crmentity on {$this->table_name}.{$this->table_index} = {$table_prefix}_crmentity.crmid
					left join {$table_prefix}_modcomments_users on {$this->table_name}.{$this->table_index} = {$table_prefix}_modcomments_users.id
					where deleted = 0 and related_to = ? and (visibility_comm = ? or (visibility_comm = ? and user = ?))", array($record_id,'All','Users',$current_user->id));
			if ($result && $adb->num_rows($result) > 0) {
				$related_to_perm = $adb->query_result($result,0,'related_to_perm'); // 0 No Permission, 1 Read Only, 2 Read/Write
				$actionid = getActionid($actionname);
				switch ($actionid) {
					case 0: // save
					case 1: // editview
						if ($related_to_perm == 2) $permission = 'yes';
						break;
					case 4: // detailview
						if ($related_to_perm > 0) $permission = 'yes';
						break;
				}
			}
		}
		return $permission;
	}
	function isEnabledParentPermissionsProp() {
		global $current_user;
		$VTEP = VTEProperties::getInstance();
		if ($VTEP->getProperty('performance.modcomments_parent_perm')) {
			$users = $VTEP->getProperty('performance.modcomments_parent_perm_users');
			if ($users['all']) {
				return true;
			}
			if (!empty($users['users']) && in_array($current_user->id,$users['users'])) {
				return true;
			}
			if (!empty($users['groups'])) {
				require_once('include/utils/GetGroupUsers.php');
				foreach($users['groups'] as $group) {
					$groupUsers = new GetGroupUsers();
					$groupUsers->getAllUsersInGroup($group,true);
					if (in_array($current_user->id,$groupUsers->group_users)) {
						return true;
					}
				}
			}
			if (!empty($users['roles']) && in_array($current_user->roleid,$users['roles'])) {
				return true;
			}
		}
		return false;
	}
	//crmv@179773e

	// crmv@43448
	function setAsUnread($commentid, $userid = null, $children = false) {
		global $adb, $table_prefix, $current_user;
		require_once('modules/SDK/src/Notifications/Notifications.php');
		
		if (empty($userid)) $userid = $current_user->id;
		$comments = array($commentid);
		if ($children) {
			// retrieve also replies
			$comments = array_unique(array_merge($comments, $this->getRepliesIds($commentid)));
		}
		$comments = array_map('intval', $comments);	//crmv@85396
		
		// notifico l'utente
		$notifications = new Notifications($userid,'ModComments');
		foreach ($comments as $commentid) {
			$notifications->addNotification($commentid, 1);
		}
		
		// crmv@57366	crmv@64325 - update the modifiedtime (not the best thing, since notifications are per-user)
		if (count($comments) > 0) {
			(PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) ? $setypeCond = "AND setype = 'ModComments'" : $setypeCond = '';
			$adb->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (".generateQuestionMarks($comments).") ".$setypeCond, array(date('Y-m-d H:i:s'), $comments));
		}
		// crmv@57366e	crmv@64325e
	}

	function setAsRead($commentid, $userid = null, $children = true) {
		global $adb, $table_prefix, $current_user;
		require_once('modules/SDK/src/Notifications/Notifications.php');

		if (empty($userid)) $userid = $current_user->id;
		$comments = array($commentid);
		if ($children) {
			// retrieve also replies
			$comments = array_unique(array_merge($comments, $this->getRepliesIds($commentid)));
		}
		$comments = array_map('intval', $comments);	//crmv@85396

		// notifico l'utente
		$notifications = new Notifications($userid,'ModComments');
		foreach ($comments as $commentid) {
			$notifications->deleteNotification($commentid, true);
		}
		// crmv@57366 crmv@64325 - update the modifiedtime (not the best thing, since notifications are per-user)
		if (count($comments) > 0) {
			(PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) ? $setypeCond = "AND setype = 'ModComments'" : $setypeCond = '';
			$adb->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (".generateQuestionMarks($comments).") ".$setypeCond, array(date('Y-m-d H:i:s'), $comments));
		}
		// crmv@57366e crmv@64325e
	}

	function getRepliesIds($commentid) {
		global $adb, $table_prefix;

		$ret = array();
		$res = $adb->pquery("
		select modcommentsid
		from {$table_prefix}_modcomments mc
		inner join {$table_prefix}_crmentity c on c.crmid = mc.modcommentsid
		where c.deleted = 0 and mc.parent_comments = ?", array($commentid));
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$ret[] = $row['modcommentsid'];
			}
		}
		return $ret;
	}
	// crmv@43448e

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	function getListQuery($module, $where='', $replies=false, $skip_parent_join=false, $joinusers=false, $visibilityInherited=false) {	//crmv@32429 crmv@101978
		global $table_prefix;
		if ($visibilityInherited) $replies = true;	// crmv@101978
		
		$query = "SELECT * ";

		// Keep track of tables joined to avoid duplicates
		$joinedTables = array();

		// Select Custom Field Table Columns if present
		//		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $this->table_name.$this->table_index";

		$joinedTables[] = $this->table_name;
		$joinedTables[] = $table_prefix.'_crmentity';

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
			" = $this->table_name.$this->table_index";
			$joinedTables[] = $this->customFieldTable[0];
		}
		if (!$replies && !$joinusers) {
			$query .= " LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
			$query .= " LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
			$joinedTables[] = $table_prefix.'_users';
			$joinedTables[] = $table_prefix.'_groups';
		}

		if (!$skip_parent_join) {	//crmv@32429
			$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM ".$table_prefix."_field" .
				" INNER JOIN ".$table_prefix."_fieldmodulerel ON ".$table_prefix."_fieldmodulerel.fieldid = ".$table_prefix."_field.fieldid" .
				" WHERE uitype='10' AND ".$table_prefix."_fieldmodulerel.module=?", array($module));
			$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

			for($i=0; $i<$linkedFieldsCount; $i++) {
				$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
				$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
				$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

				$other =  CRMEntity::getInstance($related_module);
				vtlib_setup_modulevars($related_module, $other);

				if(!in_array($other->table_name, $joinedTables)) {
					$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
					$joinedTables[] = $other->table_name;
				}
			}
		}	//crmv@32429

		global $current_user;
		if (!$replies) {
			$query .= $this->getNonAdminAccessControlQuery($module,$current_user);
		}
		// crmv@64325
		$setypeCond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
			$setypeCond = "AND {$table_prefix}_crmentity.setype = 'ModComments'";
		}
		$query .= "	WHERE ".$table_prefix."_crmentity.deleted = 0 $setypeCond ".$where;
		// crmv@64325e
		if (!$replies) {
			$query = $this->listQueryNonAdminChange($query, $module);
		}
		// crmv@101978
		if ($visibilityInherited) {
			if ($_REQUEST['ajxaction'] != 'LOADRELATEDLIST') {
				$query .= " AND ($this->table_name.parent_comments IS NULL OR $this->table_name.parent_comments = '')";
			}
		}
		// crmv@101978e
		return $query;
	}

	public function listQueryNonAdminChange($query,$module,$scope='') {
		global $current_user,$adb,$table_prefix;
		$query = parent::listQueryNonAdminChange($query,$module,$scope);
		if ($_REQUEST['ajxaction'] != 'LOADRELATEDLIST') {
			$query .= " AND ($this->table_name.parent_comments IS NULL OR $this->table_name.parent_comments = '')";
			$query .= " AND (
			".$table_prefix."_crmentity.smcreatorid = $current_user->id";
			if ($current_user->column_fields['receive_public_talks'] == '1') {
				$query .= "	OR ".$table_prefix."_modcomments.visibility_comm = 'All'";
			}
			$column = 'user';
			$adb->format_columns($column);
			$query .= "		OR (".$table_prefix."_modcomments.visibility_comm = 'Users' AND EXISTS(SELECT * FROM ".$table_prefix."_modcomments_users WHERE ".$table_prefix."_modcomments_users.id = ".$table_prefix."_modcomments.modcommentsid AND ".$table_prefix."_modcomments_users.$column = $current_user->id))
			)";
		}
		return $query;
	}

	// crmv@63349 - handlers for the msgrel table
	public function insertIntoMsgRelTable($messageId) {
		global $adb, $table_prefix;
		
		$visib = $this->column_fields['visibility_comm'];
		$users = array();
		
		// if public, insert only for users with the receive public flag, otherwise only to the specified users
		if ($visib == 'All') {
			$result = $adb->pquery(
				"select u.id as userid
				from {$table_prefix}_users u
				left join {$table_prefix}_modcomments_msgrel mr on mr.userid = u.id and mr.messagesid = ?
				where u.deleted = 0 and u.receive_public_talks = ? AND mr.userid IS NULL",
				array($messageId, 1)
			);
		} elseif ($visib == 'Users' && !empty($_REQUEST['users_comm'])) {
			$idlist = vtlib_purify($_REQUEST['users_comm']);
			if (!empty($idlist)) {
				$idlist = array_filter(explode('|',$idlist));
			}
			if (is_array($idlist) && count($idlist) > 0) {
				$result = $adb->pquery(
					"SELECT u.id as userid
					FROM {$table_prefix}_users u
					LEFT JOIN {$table_prefix}_modcomments_msgrel mr ON mr.userid = u.id AND mr.messagesid = ?
					WHERE mr.userid IS NULL AND u.id in (".generateQuestionMarks($idlist).")",
					array($messageId, $idlist)
				);
			}
		}
		// retrieve the users to be put in the rel table
		if ($result && $adb->num_rows($result) > 0) {
			while($row = $adb->fetchByAssoc($result, -1, false)) {
				$uid = intval($row['userid']);
				if ($uid != $this->column_fields['assigned_user_id']) {
					$users[] = $uid;
				}
			}
		}
		
		// insert it
		foreach ($users as $userid) {
			$adb->pquery("INSERT INTO {$table_prefix}_modcomments_msgrel (userid, messagesid) VALUES (?,?)", array($userid, $messageId));
		}
		
		return true;
	}
	
	public function removeFromMsgRelTable($messageId) {
		global $adb, $current_user,$table_prefix;
		
		$visib = $this->column_fields['visibility_comm'];
		
		$users = array();
		if ($visib == 'All') {
			// get all the users
			$result = $adb->query("SELECT DISTINCT userid FROM {$table_prefix}_modcomments_msgrel");
		} elseif ($visib == 'Users') {
			$result = $adb->pquery("SELECT user AS userid FROM {$table_prefix}_modcomments_users WHERE id = ?", array($this->id));
		}
		
		if ($result && $adb->num_rows($result) > 0) {
			while($row = $adb->fetchByAssoc($result, -1, false)) {
				$users[] = intval($row['userid']);
			}
		}
		
		// delete those lines
		if (count($users) > 0) {
			$result = $adb->pquery("DELETE FROM {$table_prefix}_modcomments_msgrel WHERE userid IN (".generateQuestionMarks($users).") AND messagesid = ?", array($users, $messageId));
		}
		
		// regenerate the table for the message
		$msgfocus = CRMEntity::getInstance('Messages');
		foreach ($users as $userid) {
			$msgfocus->regenCommentsMsgRelTable($userid, $messageId);
		}
		
	}
	// crmv@63349e

	function generateReportsQuery($module, $reportid = 0, $joinProducts = false, $joinUitype10 = true) { // crmv@146653
		global $current_user,$table_prefix;
		//crmv@21249
		$query = "from ".$table_prefix."_modcomments inner join ".$table_prefix."_modcommentscf ".$table_prefix."_modcommentscf
		on ".$table_prefix."_modcommentscf.modcommentsid=".$table_prefix."_modcomments.modcommentsid
		inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_modcomments.modcommentsid
		left join ".$table_prefix."_groups ".$table_prefix."_groupsModComments on ".$table_prefix."_groupsModComments.groupid = ".$table_prefix."_crmentity.smownerid
		left join ".$table_prefix."_users ".$table_prefix."_usersModComments on ".$table_prefix."_usersModComments.id = ".$table_prefix."_crmentity.smownerid
		left join ".$table_prefix."_crmentity ".substr("".$table_prefix."_crmentityRelModComments",0,29)." on ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid = ".$table_prefix."_modcomments.related_to
		and ".$table_prefix."_crmentityRelModComments.deleted=0
		left join ".$table_prefix."_leaddetails ".substr("".$table_prefix."_leaddetailsRelModComments",0,29)." on ".substr("".$table_prefix."_leaddetailsRelModComments",0,29).".leadid = ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid
		left join ".$table_prefix."_contactdetails ".substr("".$table_prefix."_contactdetailsRelModComments",0,29)." on ".substr("".$table_prefix."_contactdetailsRelModComments",0,29).".contactid = ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid
		left join ".$table_prefix."_account ".$table_prefix."_accountRelModComments on ".$table_prefix."_accountRelModComments.accountid = ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid";
		$projectModule = Vtecrm_Module::getInstance('Project');
		if($projectModule !== false) {
			$query .= " left join ".$table_prefix."_projecttask as ".substr("".$table_prefix."_projecttaskRelModComments",0,29)." on ".substr("".$table_prefix."_projecttaskRelModComments",0,29).".projecttaskid = ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid
			left join ".$table_prefix."_project as ".$table_prefix."_projectRelModComments on ".$table_prefix."_projectRelModComments.projectid = ".substr("".$table_prefix."_crmentityRelModComments",0,29).".crmid";
		}
		//crmv@21249e
		return $query;
	}

	// crmv@43050
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) { // crmv@146653
		global $adb, $table_prefix, $current_user;

		if ($module != 'ModComments') {
			return parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check); // crmv@146653
		}

		if(!is_array($with_crmid)) $with_crmid = array($with_crmid);
		$with_crmid = array_filter($with_crmid);

		if ($crmid > 0 && $with_module && count($with_crmid) > 0)

			// save only the first
			$adb->pquery("update {$this->table_name} set related_to = ? where {$this->table_index} = ?", array($with_crmid[0], $crmid));
		$adb->pquery("update {$table_prefix}_crmentity set modifiedtime = ? where crmid = ?", array(date('Y-m-d H:i:s'), $crmid));

		if (isModuleInstalled('ModNotifications') && $current_user->id != getOwnerId($crmid)) {
			$obj = ModNotifications::getInstance(); // crmv@164122
			$owner = getOwnerId($crmid);
			$notified_users = $obj->saveFastNotification(
				array(
					'assigned_user_id' => $owner,
					'related_to' => $crmid,
					'mod_not_type' => 'Relation',
					'description' => $with_crmid[0],
				),false
			);

		}
	}
	// crmv@43050e

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
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
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	function delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse = true) { // crmv@146653
		if ($with_module == 'ModComments') {
			$destinationModule = vtlib_purify($_REQUEST['destination_module']);
			if (!is_array($with_crmid)) $with_crmid = Array($with_crmid);
			foreach($with_crmid as $relcrmid) {
				$child = CRMEntity::getInstance($destinationModule);
				$child->retrieve_entity_info($relcrmid, $destinationModule);
				$child->trash($destinationModule, $relcrmid);
			}
		} else {
			parent::delete_related_module($module, $crmid, $with_module, $with_crmid, $reverse); // crmv@146653
		}
	}

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	function trash($module, $id) {
		global $current_user, $adb, $table_prefix;
		if ($this->column_fields['creator']) {
			$creator = $this->column_fields['creator'];
		} else {
			$focus = CRMEntity::getInstance($module);
			$focus->retrieve_entity_info($id, $module);
			$creator = $focus->column_fields['creator'];
		}
		if ($current_user->id != $creator) {
			return false;
		}
		require_once('modules/SDK/src/Notifications/Notifications.php');
		$notifications = new Notifications($current_user->id,'ModComments');
		$notifications->deleteAllNotificationForComment($id);

		// crmv@63349
		if (empty($this->column_fields['creator'])) {
			$this->retrieve_entity_info($id, $module);
		}
		$relatedTo = $this->column_fields['related_to'];
		if (!empty($relatedTo) && getSalesEntityType($relatedTo) == 'Messages') {
			$removeMsg = true;
		}

		parent::trash($module, $id);
		
		if ($removeMsg) {
			$this->id = $id;
			$this->removeFromMsgRelTable($relatedTo);
		}
		// crmv@63349e

		//cancello le eventuali risposte
		$result = $adb->pquery("SELECT modcommentsid FROM {$table_prefix}_modcomments WHERE parent_comments = ?",array($id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)){
				$notifications->deleteAllNotificationForComment($row['modcommentsid']);
				parent::trash($module, $row['modcommentsid']);
			}
		}
	}

	function get_replies($id, $cur_tab_id, $rel_tab_id, $actions=false) {

		global $currentModule, $app_strings, $current_user,$table_prefix;//crmv@203484 removed global singlepane

		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e

		$parenttab = getParentTab();

		//		echo "$id, $currentModule";die;
		$focus = CRMEntity::getInstance($currentModule);
		$focus->retrieve_entity_info($id, $currentModule);
		//	echo '<pre>';print_r($focus->column_fields);die;

		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);

		// Some standard module class doesn't have required variables
		// that are used in the query, they are defined in this generic API
		vtlib_setup_modulevars($currentModule, $this);
		vtlib_setup_modulevars($related_module, $other);

		$button = '';

		// To make the edit or del link actions to return back to same view.
		if($singlepane_view == true) $returnset = "&return_module=$currentModule&return_action=DetailView&return_id=$id";//crmv@203484 changed to normal bool true, not string 'true'
		else $returnset = "&return_module=$currentModule&return_action=CallRelatedList&return_id=$id";

		$return_value = null;
		$dependentFieldSql = $this->db->pquery("SELECT tabid, fieldname, columnname FROM ".$table_prefix."_field WHERE uitype='10' AND" .
			" fieldid IN (SELECT fieldid FROM ".$table_prefix."_fieldmodulerel WHERE relmodule=? AND module=?)", array($currentModule, $related_module));
		$numOfFields = $this->db->num_rows($dependentFieldSql);

		if($numOfFields > 0) {
			$dependentColumn = $this->db->query_result($dependentFieldSql, 0, 'columnname');
			$dependentField = $this->db->query_result($dependentFieldSql, 0, 'fieldname');

			$button .= '<input type="hidden" name="'.$dependentColumn.'" id="'.$dependentColumn.'" value="'.$id.'">';
			$button .= '<input type="hidden" name="'.$dependentColumn.'_type" id="'.$dependentColumn.'_type" value="'.$currentModule.'">';
			if($actions) {
				if(is_string($actions)) $actions = explode(',', strtoupper($actions));
				if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes'
					&& getFieldVisibilityPermission($related_module,$current_user->id,$dependentField) == '0') {
					if (intval($focus->column_fields['parent_comments']) == 0) {
						$button .= '<input type="hidden" name="related_to" id="related_to" value="'.$focus->column_fields['related_to'].'">';
						$button .= "<input title='".getTranslatedString('LBL_ADD_NEW'). " ". getTranslatedString('LBL_MODCOMMENTS_REPLY',$related_module) ."' class='crmbutton small create'" .
							" onclick='this.form.action.value=\"EditView\";this.form.module.value=\"$related_module\"' type='submit' name='button'" .
							" value='". getTranslatedString('LBL_ADD_NEW'). " " . getTranslatedString('LBL_MODCOMMENTS_REPLY',$related_module) ."'>&nbsp;";
					}
				}
			}

			$query = "SELECT ".$table_prefix."_crmentity.*, $other->table_name.*";

			$query .= ", CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name";

			$more_relation = '';
			if(!empty($other->related_tables)) {
				foreach($other->related_tables as $tname=>$relmap) {
					$query .= ", $tname.*";

					// Setup the default JOIN conditions if not specified
					if(empty($relmap[1])) $relmap[1] = $other->table_name;
					if(empty($relmap[2])) $relmap[2] = $relmap[0];
					$more_relation .= " LEFT JOIN $tname ON $tname.$relmap[0] = $relmap[1].$relmap[2]";
				}
			}

			$query .= " FROM $other->table_name";
			$query .= " INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = $other->table_name.$other->table_index";
			//crmv@24527
			if (!empty($other->customFieldTable)) {
				$query .= " INNER JOIN ".$other->customFieldTable[0]." ON $other->table_name.$other->table_index = ".$other->customFieldTable[0].".".$other->customFieldTable[1];
			}
			//crmv@24527e
			$query .= " INNER  JOIN $this->table_name AS parent_modcomments ON parent_modcomments.$this->table_index = $other->table_name.$dependentColumn";
			$query .= $more_relation;
			$query .= " LEFT  JOIN ".$table_prefix."_users        ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid";
			if (!empty($other->groupTable) ){
				$query .= "	LEFT JOIN ".$other->groupTable[0]."
				ON ".$other->groupTable[0].".".$other->groupTable[1]." = $other->table_name.$other->table_index ";
				$query .= "	LEFT JOIN ".$table_prefix."_groups
				ON ".$other->groupTable[0].".groupname = ".$table_prefix."_groups.groupname ";
			}
			else {
				$query .= " LEFT  JOIN ".$table_prefix."_groups       ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
			}
			$query .= " WHERE ".$table_prefix."_crmentity.deleted = 0 AND parent_modcomments.$this->table_index = $id";
			$return_value = GetRelatedList($currentModule, $related_module, $other, $query, $button, $returnset);
		}
		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		return $return_value;
	}
}
?>