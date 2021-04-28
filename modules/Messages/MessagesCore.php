<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class MessagesCore extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name;
	var $table_index= 'messagesid';
	var $column_fields = Array();

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
	var $list_fields = Array ();
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Date' => 'mdate',
		'Subject'=> 'subject',
		'From Name'=> 'mfrom_f'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'subject';

	// For Popup listview and UI type support
	var $search_fields = Array();
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Subject'=> 'subject'
	);

	// For Popup window record selection
	var $popup_fields = Array('subject');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'subject';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'subject';

	// Required Information for enabling Import feature
	var $required_fields = Array('subject'=>1);

	var $default_order_by = 'mdate';
	var $default_sort_order='DESC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'subject');
	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e

	function __construct() {
		global $log, $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix.'_messages';
		//crmv@171021
		$this->tab_name = Array($table_prefix.'_messages');
		$this->tab_name_index = Array(
			$table_prefix.'_messages'   => 'messagesid'
		);
		//crmv@171021e
		$this->list_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Date'=> Array($table_prefix.'_messages', 'mdate'),
			'Subject'=> Array($table_prefix.'_messages', 'subject'),
			'From Name'=> Array($table_prefix.'_messages', 'mfrom_f'),
		);
		$this->search_fields = Array(
			/* Format: Field Label => Array(tablename, columnname) */
			// tablename should not have prefix 'vte_'
			'Subject'=> Array($table_prefix.'_messages', 'subject')
		);
		if (empty($this->column_fields)) {
			$this->column_fields = getColumnFields('Messages'); //crmv@146187
		}
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb,$table_prefix;
		if($event_type == 'module.postinstall') {
			
			$messagesModule = Vtecrm_Module::getInstance($modulename);
			$msgId = $messagesModule->id;

			$adb->pquery("UPDATE {$table_prefix}_tab SET customized=0 WHERE name=?", array($modulename));
			
			//crmv@61173
			$adb->pquery("insert into {$table_prefix}_org_share_action2tab values(?,?)",array(8,$msgId));
			$adb->pquery("delete from {$table_prefix}_org_share_action2tab where tabid = ? and share_action_id in (1,2)",array($msgId));
			//crmv@61173e

			//crmv@29617
			$result = $adb->pquery('SELECT isentitytype FROM '.$table_prefix.'_tab WHERE name = ?',array($modulename));
			if ($result && $adb->num_rows($result) > 0 && $adb->query_result($result,0,'isentitytype') == '1') {
				$ModCommentsFocus = CRMEntity::getInstance('ModComments');
				$ModCommentsFocus->addWidgetTo($modulename);
			}
			//crmv@29617e

			// add related
			$excludemods = array(
				'ModComments', // crmv@164120 crmv@164122
				'Emails', 'Sms', 'Fax', 'Newsletter', 'Messages', 'PBXManager', 'Charts', 'Faq', 'Projects',
			);
			$res = $adb->pquery("select {$table_prefix}_tab.name from {$table_prefix}_tab left join {$table_prefix}_relatedlists rl on rl.tabid = {$table_prefix}_tab.tabid and rl.related_tabid = ? where isentitytype = 1 and rl.tabid is null and {$table_prefix}_tab.name not in (".generateQuestionMarks($excludemods).")", array($msgId, $excludemods));
			if ($res) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$lmod = $row['name'];
					$modInst = Vtecrm_Module::getInstance($lmod);
					$modInst->setRelatedList($messagesModule, $modulename, Array('ADD'), 'get_messages_list');
				}
			}

			// edit quick create fields
			/*
			 * 1 : not active
			 * 0 : active
			 */
			$qcreate = array(
				'HelpDesk' => array(
					'description' => 1,
				),
			);
			foreach ($qcreate as $mod=>$qlist) {
				foreach ($qlist as $fname=>$qval) {
					$adb->pquery("update {$table_prefix}_field set quickcreate = ? where fieldname = ? and tabid = ?", array($qval, $fname, getTabid($mod)));
				}
			}

			$adb->query("update tbl_s_menu_modules set sequence = sequence + 1 where fast = 1 and sequence > 1");
			$adb->pquery('insert into tbl_s_menu_modules (tabid,fast,sequence) values (?,?,?)',array($messagesModule->id,1,2));

			$messagesModule->hide(array('hide_report'=>1)); // crmv@38798

			// crmv@42264	crmv@49395	crmv@51862
			// now setup cronjobs
			if (Vtecrm_Utils::CheckTable($table_prefix.'_cronjobs')) {
				require_once('include/utils/CronUtils.php');
				$CU = CronUtils::getInstance();
				
				$cj = new CronJob();
				$cj->name = 'MessagesPop3';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/Pop3.service.php';
				$cj->timeout = 300;		// 5min timeout
				$cj->repeat = 900;		// run every 15 min
				$CU->insertCronJob($cj);

				$cj = new CronJob();
				$cj->name = 'MessagesUids';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/MessagesUids.service.php';
				$cj->timeout = 1800;
				$cj->repeat = 600;
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'Messages';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/Messages.service.php';
				$cj->timeout = 600;
				$cj->repeat = 60;
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);

				$cj = new CronJob();
				$cj->name = 'MessagesInboxUids';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/InboxUids.service.php';
				$cj->timeout = 600;
				$cj->repeat = 60;
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'MessagesInbox';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/Inbox.service.php';
				$cj->timeout = 600;
				$cj->repeat = 60;
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'MessagesPropagateToImap';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/PropagateToImap.service.php';
				$cj->timeout = 300;			// 5 min timeout
				$cj->repeat = 60;			// run every 1 min
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'MessagesSend';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/SendMessages.service.php';
				$cj->timeout = 300;			// 5 min timeout
				$cj->repeat = 60;			// run every 1 min
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				// crmv@129149
				$cj = new CronJob();
				$cj->name = 'MessagesSendNotifications';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/SendNotifications.service.php';
				$cj->timeout = 300;
				$cj->repeat = 60;
				$cj->maxAttempts = 0;
				$CU->insertCronJob($cj);
				// crmv@129149e
				
				$cj = new CronJob();
				$cj->name = 'MessagesSyncFolders';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/SyncFolders.service.php';
				$cj->timeout = 600;
				$cj->repeat = 120;
				$cj->maxAttempts = 2147483647;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'MessagesAllUids';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/AllUids.service.php';
				$cj->timeout = 5400;
				$cj->repeat = 600;
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'CleanMessageStorage';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/CleanStorage.service.php';
				//crmv@186732
				$cj->timeout = 600; // 10min timeout
				$cj->repeat = 3600; // run every hour
				$cj->maxAttempts = 0;
				//crmv@186732e
				$CU->insertCronJob($cj);
				
				$cj = new CronJob();
				$cj->name = 'CleanInlineCache';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/CleanInlineCache.service.php';
				$cj->timeout = 300;		// 5min timeout
				$cj->repeat = 14400;	// run every 4 hour
				$CU->insertCronJob($cj);
				
				// crmv@191351
				$cj = new CronJob();
				$cj->name = 'MessagesSendOutOfOfficeReplies';
				$cj->active = 1;
				$cj->singleRun = false;
				$cj->fileName = 'cron/modules/Messages/SendOutOfOfficeReplies.service.php';
				$cj->timeout = 300;
				$cj->repeat = 60;
				$cj->maxAttempts = 0;
				$CU->insertCronJob($cj);
				// crmv@191351e
			}
			// crmv@42264e	crmv@49395e	crmv@51862e

			// crmv@85493 not needed anymore
			/*
			$idxs_messages = array_keys($adb->database->MetaIndexes($table_prefix.'_messages'));
			$indexes = array(
				array("{$table_prefix}_messages", "{$table_prefix}_messages_adoptchildren", 'folder, mreferences(200)'),
				array("{$table_prefix}_messages", "{$table_prefix}_messages_referencechildren_idx", 'mdate, folder, mreferences(200)'),
			);
			foreach($indexes as $index) {
				if (!in_array($index[1], $idxs_messages)) {
					$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL($index[1], $index[0], $index[2]));
				}
			}
			*/

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

	function trash($module, $id) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_messages_recipients where messagesid = ?",array($id));
		$adb->pquery("delete from {$table_prefix}_messages_inline_cache where messagesid = ?",array($id));	//crmv@125629
		if ($this->column_fields['mtype'] == 'Webmail') {
			$this->beforeTrashFunctions($id);
		}
		$adb->pquery("delete from {$table_prefix}_emails_send_queue where messagesid = ?",array($id)); // crmv@187622
		parent::trash($module, $id);
		// crmv@187622
		if ($this->column_fields['folder'] == 'vteScheduled') {
			$this->reloadCacheFolderCount($this->column_fields['assigned_user_id'],$this->column_fields['account'],$this->column_fields['folder']);
		}
		// crmv@187622e
	}

	function getFixedOrderBy($module,$order_by,$sorder){
		$tablename = getTableNameForField($module, $order_by);
		$tablename = ($tablename != '')? ($tablename . '.') : '';
		return  ' ORDER BY ' . $tablename . $order_by . ' ' . $sorder;
	}
}