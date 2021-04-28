<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 crmv@182114 crmv@190016 */

/**
 * Module to handle real time synchronizations with external systems
 */
class VteSync extends SDKExtendableUniqueClass {
	
	public $tables = array(
		'main' => '',
		'types' => '',
		'modules' => '',
		'auth' => '',
		'tokens' => '',
	);
	
	// TODO: define default sync directions
	protected $defaultSyncTypes = array(
		1 => array(
			'name' => 'SalesForce', 
			'modules' => array('Users', 'Leads', 'Contacts', 'Accounts', 'Potentials', 'Campaigns', 'HelpDesk', 'Products', 'Assets'),
			'has_system_url' => false,
			'oauth2_flow_types' => array('oauth2_flow_authorization'),
		),
		2 => array(
			'name' => 'Jira', 
			'modules' => array('Users', 'HelpDesk', 'ProjectPlan', 'ProjectTask', 'HelpDesk', 'TicketComments'),
			'has_system_url' => true,
			'system_url_example' => 'https://your-instance.atlassian.net',
		),
		// crmv@195073
		3 => array(
			'name' => 'HubSpot', 
			'modules' => array('Users', 'Contacts', 'Accounts', 'Potentials', 'HelpDesk', 'Targets'),
			'has_system_url' => false,
			'oauth2_flow_types' => array('oauth2_flow_authorization'),
		),
		// crmv@195073e
		//crmv@196666
		4 => array(
			'name' => 'SuiteCRM', 
			'modules' => array('Users', 'Leads', 'Contacts', 'Accounts', 'Potentials', 'Campaigns', 'HelpDesk', 'Products', 'Assets'),
			'has_system_url' => true,
			'oauth2_flow_types' => array('oauth2_flow_client_cred'),
		),
		//crmv@196666e
		// crmv@197423
		5 => array(
			'name' => 'Vtiger', 
			'modules' => array('Users', 'Leads', 'Contacts', 'Vendors', 'Accounts', 'Potentials', 'HelpDesk', 'Products', 'Services','Assets'),
			'has_system_url' => true,
			'system_url_example' => 'https://your_instance.odx.vtiger.com',
		),
		// crmv@197423e
	);
	
	protected static $typesCache = null;
	protected static $typesModsCache = null;
	

	public function __construct() {
		global $table_prefix;
		
		// meta tables
		$this->tables['types'] = $table_prefix.'_vtesync_types';
		$this->tables['types_mods'] = $table_prefix.'_vtesync_types_mods';
		// data tables
		$this->tables['main'] = $table_prefix.'_vtesync';
		$this->tables['auth'] = $table_prefix.'_vtesync_auth';
		$this->tables['tokens'] = $table_prefix.'_vtesync_tokens';
	}
	
	public function loadLibrary() {
		require_once(__DIR__.'/vendor/autoload.php');
	}
	
	// --------------------- cron ---------------------
	public function runCron($syncid = null) {
		
		
		if (!$syncid) {
			$syncid = $this->getNextCronSyncid();
		}
		// nothing to sync
		if (!$syncid) return;
		
		$config = $this->generateVSLConfig($syncid);
		
		require_once('VteSyncLib/autoloader.php');
		$vsl = new \VteSyncLib\Main($config);
		if ($vsl->isReady()) {
			$this->preLaunchSync($config); //crmv@195073
			$this->setSyncLastRun($syncid);
			$vsl->synchronize();
		}
	}
	
	/**
	 * Get the next syncid to be launched (oldest active one)
	 */
	public function getNextCronSyncid() {
		global $adb;
		
		$syncid = null;
		$res = $adb->limitquery("SELECT syncid FROM {$this->tables['main']} WHERE active = 1 ORDER BY lastrun ASC",0, 1);
		if ($res && $adb->num_rows($res) > 0) {
			$syncid = $adb->query_result_no_html($res, 0, 'syncid');
		}
		return $syncid;
	}
	
	//crmv@195073
	//crmv@196666
	public function preLaunchSync($config) {
		if ($config['connectors']['HubSpot']) {
			// create needed fields before launching the sync
			require_once('modules/Update/Update.php');
			$fields = array();
			$fields[] = array('module'=>'Contacts','block'=>'LBL_CONTACT_INFORMATION','name'=>'lifecyclestage','label'=>'Lifecycle Stage','uitype'=>'15','columntype'=>'C(125)','typeofdata'=>'V~O', 'picklist'=>array('lead'));
			Update::create_fields($fields);
		}
		if($config['connectors']['SuiteCRM'])
		{	
			require_once('modules/PickList/PickListUtils.php');
			
			foreach($config['connectors']['SuiteCRM']['modules'] as $module => $conf)
			{
				if($module == "Leads")
				{
					$mod = \Vtecrm_Module::getInstance($module);
					
					$fld = \Vtecrm_Field::getInstance('leadstatus', $mod);
					$fld2 = \Vtecrm_Field::getInstance('leadsource', $mod);
					
					$oldpick = getAllPickListValues('leadstatus',$module);
					$oldpick2 = getAllPickListValues('leadsource',$module);
					
					$values = array("New" => "New","Assigned" => "Assigned","In Process" => "In Process","Converted" => "Converted","Recycled" => "Recycled","Dead" => "Dead");
					$values2 = array("Cold Call" => "Cold Call","Existing Customer" => "Existing Customer","Self Generated" => "Self Generated","Employee" => "Employee","Partner" => "Partner", "Public Relations" => "Public Relations","Direct Mail" => "Direct Mail","Conference" => "Conference","Trade Show" => "Trade Show","Web Site" => "Web Site","Word of mouth" => "Word of mouth","Email" => "Email","Campaign" => "Campaign","Other"=>"Other");
					
					$picklist_value = array_diff($values, $oldpick);
					$picklist_value2 = array_diff($values2, $oldpick2);
					if ($fld) {
						$fld->setPicklistValues(array_keys($picklist_value));
					}
					if ($fld2) {
						$fld2->setPicklistValues(array_keys($picklist_value2));
					}
				}
				if($module == "HelpDesk")
				{
					$mod = \Vtecrm_Module::getInstance($module);
					
					$fld = \Vtecrm_Field::getInstance('ticketstatus', $mod);
					$fld2 = \Vtecrm_Field::getInstance('ticketpriorities', $mod);
					
					$oldpick = getAllPickListValues('ticketstatus',$module);
					$oldpick2 = getAllPickListValues('ticketpriorities',$module);
					
					$values = array("Not Started" => "Not Started","In Progress" => "In Progress","Completed" => "Completed","Pending Input" => "Pending Input","Deferred" => "Deferred");
					$values2 = array("High" => "High","Medium" => "Medium","Low" => "Low");
					
					$picklist_value = array_diff($values, $oldpick);
					$picklist_value2 = array_diff($values2, $oldpick2);
					
					if ($fld) {
						$fld->setPicklistValues(array_keys($picklist_value));
					}
					if ($fld2) {
						$fld2->setPicklistValues(array_keys($picklist_value2));
					}
				}
				if($module == "Campaigns")
				{
					$mod = \Vtecrm_Module::getInstance($module);
					$fld = \Vtecrm_Field::getInstance('campaigntype', $mod);
					$fld2 = \Vtecrm_Field::getInstance('campaignstatus', $mod);
					
					$oldpick = getAllPickListValues('campaigntype',$module);
					$oldpick2 = getAllPickListValues('campaignstatus',$module);
					
					$values = array("Telesales" => "Telesales","Mail" => "Mail","Print" => "Print","Web" => "Web","Radio" => "Radio","Television" => "Television");
					$values2 = array("Planning" => "Planning","Active" => "Active","Inactive" => "Inactive","Complete" => "Complete");
					$picklist_value = array_diff($values, $oldpick);
					$picklist_value2 = array_diff($values2, $oldpick2);
					if ($fld) {
						$fld->setPicklistValues(array_keys($picklist_value));
					}
					if ($fld2) {
						$fld2->setPicklistValues(array_keys($picklist_value2));
					}
				
				}
			}
			
			
		} 
	}
	//crmv@195073e
	//crmv@196666e
	/**
	 * Generate the config for the specified syncid, to be passed to the VteSyncLib
	 */
	public function generateVSLConfig($syncid) {
		global $root_directory, $default_timezone;
		
		$loglevel = 4; // info, 1=fatal ... 5 = debug
		$info = $this->getSync($syncid);
		
		// create modules config
		$connector = $info['type_name'];
		$modconfig = $info['modconfig'];
		
		// crmv@190016
		$modlist = array_column($modconfig, 'module');
		if ($connector == 'Jira' && in_array('HelpDesk', $modlist)) {
			// inject ticket comments also
			array_push($modlist, "TicketComments");
			$ticket = array(
				"module" => "TicketComments", 
				"sync_direction" => "both",
				"deletions" => "none", 
				"sync_picklist" => "none"
			);
			array_push($modconfig,$ticket);
		}
		// crmv@190016e
	
		// crmv@195073e
		if ($connector == 'HubSpot' && in_array('Targets', $modlist)) {
			// inject ticket rargets/contacts related
			array_push($modlist, "Targets_Contacts");
			$targcont = array(
				'module'=>'Targets_Contacts',
				'sync_direction'=>'both',
				'deletions'=>'none',
				'sync_picklists'=>'none',
			);
			array_push($modconfig,$targcont);
		}
		// crmv@195073e
	
		// 1 = VTE, 2 = other
		$cmods1 = $cmods2 = array();
		$defaults1 = $defaults2 = array();
		foreach ($modconfig as $modinfo) {
			$mod = $modinfo['module'];
			// read from cfg, or use defaults
			$dir = $modinfo['sync_direction'] ?: 'both';
			$del = $modinfo['deletions'] ?: 'none';
			$plist = $modinfo['sync_picklist'] ?: 'none';

			// force some values
			if ($mod == 'Users') $dir = 'to_vte';
			
			$cmods1[$modinfo['module']] = array(
				'direction' => ($dir == 'both' ? 'Both' : ($dir == 'from_vte' ? 'Out' : 'In')),
				'picklist' => $plist == 'to_vte',
				'delete' => ($del == 'both' || $del == 'in_vte'),
			);
			$defaults1[$mod] = array(
				'create' => $this->getSyncDefaults('VTE', $mod, 'create'),
				'update' => $this->getSyncDefaults('VTE', $mod, 'update'),
			);
			// crmv@190016
			$forced1[$mod] = array(
				'create' => $this->getSyncForcedFields('VTE', $mod, 'create'),
				'update' => $this->getSyncForcedFields('VTE', $mod, 'update'),
			);
			// crmv@190016e
			
			$cmods2[$mod] = array(
				'direction' => ($dir == 'both' ? 'Both' : ($dir == 'from_vte' ? 'In' : 'Out')),
				'picklist' => false, // only to VTE supported
				'delete' => ($del == 'both' || $del == 'in_external'),
			);
			$defaults2[$mod] = array(
				'create' => $this->getSyncDefaults($connector, $mod, 'create'),
				'update' => $this->getSyncDefaults($connector, $mod, 'update'),
			);
			// crmv@190016
			$forced2[$mod] = array(
				'create' => $this->getSyncForcedFields($connector, $mod, 'create'),
				'update' => $this->getSyncForcedFields($connector, $mod, 'update'),
			);
			// crmv@190016e
		}
		
		// sort modules to avoid problems with owners and uitype 10
		$order = array('Users', 'Leads', 'Accounts','Contacts', 'ProjectPlan', 'ProjectTask'); // crmv@190016
		$cmods1 = array_replace(array_intersect_key(array_flip($order), $cmods1), $cmods1);
		$cmods2 = array_replace(array_intersect_key(array_flip($order), $cmods2), $cmods2);

		// vte connector
		$conn1 = array(
			'enabled' => true,
			'loglevel' => $loglevel,
			'auth_type' => 'local',
			'vte_path' => $root_directory,
			'update_owners' => false,
			'modules' => $cmods1,
			'defaults' => $defaults1,
			'forcefields' => $forced1, // crmv@190016
		);
	
		// other connector
		$conn2 = array(
			'enabled' => true,
			'loglevel' => $loglevel,
			'syncid' => $syncid,
			'auth_type' => $info['authtype'],
			'update_owners' => false,
			'modules' => $cmods2,
			'defaults' => $defaults2,
			'forcefields' => $forced2, // crmv@190016
		);
			
		// crmv@190016
		if ($info['system_url']) {
			$conn2['instance_url'] = $info['system_url'];
		}
		// crmv@190016e
		
		// see the file 'VteSyncLib/config.php' for a full description
		$config = array(
			'simulate' => false,
			'loglevel' => $loglevel,
			'only_cli' => true,
			'local_timezone' => $default_timezone,
			'user_mapping' => 'VTE', // TODO?
			'conflicts' => 'Last',
			'use_pidfile' => false,

			// local database
			'storage' => array(
				'type' => 'vte',
				'vte' => array(
					'path' => $root_directory,
				),
			),

			'connectors' => array(
				'VTE' => $conn1,
				$connector => $conn2
			),
		);

		return $config;
	}
	
	protected function getSyncDefaults($connector, $module, $mode) {
		$defaults = array();
		// ids are in webservice format
		if ($connector == 'VTE' && $module == 'Users' && $mode == 'create') {
			$defaults = array(
				'is_admin' => 'off',
				'user_password' => ':RANDOM:', // use ":RANDOM:" to genrate a random password for each user
				'status' => 'Inactive',
				'last_name' => 'Imported User',
				'roleid' => 'H3',
				'currency_id' => '22x1',
				'lead_view' => 'Today',
				'menu_view' => 'Small Menu',
				'default_module' => 'Home',
				'allow_generic_talks' => 1,
				'receive_public_talks' => 1,
				'notify_me_via' => 'ModNotifications',
				'notify_summary' => 'Never',
				'internal_mailer' => 1,
				'decimal_separator' => ',',
				'thousands_separator' => '.',
				'decimals_num' => 2,
				'activity_view' => 'This Week',
				'date_format' => 'dd-mm-yyyy',
				'reminder_interval' => 'None',
				'start_hour' => '08:00',
				'no_week_sunday' => 1,
				'weekstart' => 1,
			);
		// crmv@190016
		} elseif ($connector == 'Jira' && $module == 'ProjectPlan' && $mode == 'create') {
			$defaults = array(
				'Key' => 'VTE'.rand(10,100), // TODO: can break in case of duplicates
				'AssigneeType' => 'PROJECT_LEAD', // or UNASSIGNED
				'Type' => 'software',
			);
		} elseif ($connector == 'Jira' && $module == 'HelpDesk' && $mode == 'create') {
			$defaults = array(
				'Type' => 'Task',
			);
		// crmv@190016e
		// crmv@195073
		} elseif ($connector == 'VTE' && $module == 'Targets' && $mode == 'create') {
			$defaults = array(
				'target_type' => '-- Nessuno --'
			);
		}
		// crmv@195073e
		
		return $defaults;
	}
	
	// crmv@190016
	// these fields will be set regardless of the source value
	protected function getSyncForcedFields($connector, $module, $mode) {
		$values = array();
		if ($connector == 'Jira' && $module == 'ProjectPlan' /* && $mode == 'create' */) {
			$values = array(
				'Type' => 'software',
			);
		}
		return $values;
	}
	// crmv@190016e
	
	// --------------------- sync ---------------------
	
	public function getSyncs() {
		global $adb;
		
		$list = array();
		$res = $adb->query("SELECT * FROM {$this->tables['main']} ORDER BY syncid ASC");
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$list[] = $this->processDbRow($row);
		}
		
		return $list;
	}
	
	public function getSync($syncid) {
		global $adb;
		
		$res = $adb->pquery("SELECT * FROM {$this->tables['main']} WHERE syncid = ?", array($syncid));
		if ($adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			$sync = $this->processDbRow($row);
			return $sync;
		}
		
		return null;
	}
	
	protected function processDbRow($row) {
		$row['type_name'] = $this->getSyncTypeName($row['typeid']);
		$row['modconfig'] = Zend_Json::decode($row['modconfig']) ?: array();
		$modules = array();
		foreach ($row['modconfig'] as $modinfo) {
			$mod = $modinfo['module'];
			$modules[$mod] = getTranslatedString($mod, $mod);
		}
			
		$row['modules'] = implode(', ', $modules);
		
		if (!$row['lastrun'] || substr($row['lastrun'], 0, 10) == '0000-00-00') {
			$row['lastrun_friendly'] = getTranslatedString('Never', 'Users');
		} else {
			$row['lastrun_friendly'] = getFriendlyDate($row['lastrun']);
		}
		
		return $row;
	}
	
	public function setSyncActive($syncid, $active = true) {
		global $adb;
		$res = $adb->pquery("UPDATE {$this->tables['main']} SET active = ? WHERE syncid = ?", array($active ? 1 : 0, $syncid));
	}
	
	public function setSyncLastRun($syncid, $when = null) {
		global $adb;
		if (!$when) $when = date('Y-m-d H:i:s');
		$res = $adb->pquery("UPDATE {$this->tables['main']} SET lastrun = ? WHERE syncid = ?", array($when, $syncid));
	}
	
	/**
	 * Validate submitted data before save
	 */
	public function validateSave($post, $mode, &$error = "") {
		global $adb;
		
		// check if empty typeid
		$typeid = intval($post['synctype']);
		
		if (!$typeid) {
			$error = 'Please specifiy a service';
			return false;
		}
		
		if ($mode == 'create') {
			// check if other sync of same type with same oauth client_id or same username
			// crmv@190016
			$res = $adb->limitpQuery(
				"SELECT t.syncid FROM {$this->tables['main']} t
				INNER JOIN {$this->tables['auth']} o ON t.syncid = o.syncid
				WHERE t.typeid = ? AND t.authtype = ? AND o.client_id = ?
				UNION ALL
				SELECT t.syncid FROM {$this->tables['main']} t
				INNER JOIN {$this->tables['auth']} o ON t.syncid = o.syncid
				WHERE t.typeid = ? AND t.authtype = ? AND o.username = ?",
				0,1,
				array($typeid, 'oauth2', $post['client_id'], $typeid, 'http', $post['http_username'])
			);
			// crmv@190016e
			if ($res && $adb->num_rows($res) > 0) {
				$error = getTranslatedString('LBL_VTESYNC_DUP_TYPE', 'Settings');
				return false;
			}
		}
		
		// crmv@190016
		if ($this->hasSystemUrl($typeid)) {
			if (empty($post['system_url'])) {
				$error = 'Please type the instance url';
				return false;
			} elseif (!preg_match('#^https?://#i', $post['system_url'])) {
				$error = 'Please type an url with the schema part (ex: http or https)';
				return false;
			}
		}
		// crmv@190016e
		
		if (empty($post['modconfig'])) {
			$error = 'Please select some modules to synchronize';
			return false;
		}
		
		if (empty($post['authtype'])) {
			$error = 'Please select an authentication method';
			return false;
		}

		if ($post['authtype'] == 'oauth2') {
			if (empty($post['client_id']) || empty($post['client_secret'])) {
				$error = 'Please fill all the fields for OAuth2';
				return false;
			}
			$saveid = $post['oauth2_saveid'];
			if ($mode == 'create' && !$saveid && $post['oauthtypeflow'] == 'oauth2_flow_authorization') { // crmv@196666
				$error = 'Please authorize the Client ID';
				return false;
			}
		// crmv@190016
		} elseif ($post['authtype'] == 'http') {
			if (empty($post['http_username']) || empty($post['http_password'])) {
				$error = 'Please fill all the fields for HTTP Authentication';
				return false;
			}
		}
		// crmv@190016e
		
		// check if empty modules
		return true;
	}
	
	public function insertSync($typeid, $post = array(), $saveid = null) {
		global $adb;

		$syncid = $adb->getUniqueID($this->tables['main']);
		
		$columns = $this->prepareColumnsForSave($typeid, $post);
		$columns['syncid'] = $syncid;
		$res = $adb->pquery("INSERT INTO {$this->tables['main']} (".implode(',', array_keys($columns)).") VALUES (".generateQuestionMarks($columns).")", $columns);
		
		// blob insert (array of json arrives)
		$modconfig = '['.implode(',', $post['modconfig']).']';
		$adb->updateClob($this->tables['main'], 'modconfig', "syncid = $syncid", $modconfig);
		
		if ($post['authtype'] === 'oauth2') {
			$this->insertSyncAuth($post['authtype'], $syncid, $post); // crmv@190016
			
			if ($saveid > 0) {
				$data = $this->loadOAuthData($saveid);
				if ($data) {
					$this->insertSyncToken($syncid, $data);
				}
			}
		// crmv@190016
		} elseif ($post['authtype'] === 'http') {
			$this->insertSyncAuth($post['authtype'], $syncid, $post);
		}
		// crmv@190016e
		
		return $syncid;
	}
	
	// crmv@190016
	public function getSyncAuth($syncid) {
		global $adb, $current_user;
		
		$res = $adb->pquery("SELECT * FROM {$this->tables['auth']} WHERE syncid = ?", array($syncid));
		if ($adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			if ($row['password']) {
				$row['password'] = $current_user->de_cryption($row['password']);
			}
			return $row;
		}
		
		return null;
	}
	
	protected function insertSyncAuth($type, $syncid, $post) {
		global $adb, $current_user;
		
		if ($type == 'oauth2') {
			$columns = array(
				'syncid' => $syncid,
				'client_id' => $post['client_id'],
				'client_secret' => $post['client_secret'],
				'scope' => $post['scope'],
			);
		} elseif ($type == 'http') {
			$columns = array(
				'syncid' => $syncid,
				'username' => $post['http_username'],
				'password' => $current_user->changepassword($post['http_password']),
			);
		}
		
		$res = $adb->pquery("INSERT INTO {$this->tables['auth']} (".implode(',', array_keys($columns)).") VALUES (".generateQuestionMarks($columns).")", $columns);
	}
	
	protected function updateSyncAuth($type, $syncid, $post) {
		global $adb, $current_user;
		
		if ($type == 'oauth2') {
			$columns = array(
				'client_id' => $post['client_id'],
				'client_secret' => $post['client_secret'],
				'scope' => $post['scope'],
				'username' => null,
				'password' => null,
			);
		} elseif ($type == 'http') {
			$columns = array(
				'client_id' => null,
				'client_secret' => null,
				'scope' => null,
				'username' => $post['http_username'],
				'password' => $current_user->changepassword($post['http_password']),
			);
		}
		
		$updates = array();
		foreach ($columns as $k => $v) {
			$updates[] = $k.' = ?';
		}
		$columns['syncid'] = $syncid;
		
		$res = $adb->pquery("UPDATE {$this->tables['auth']} SET ".implode(', ', $updates)." WHERE syncid = ?", $columns);
	
	}
	// crmv@190016e
	
	protected function insertSyncToken($syncid, $data) {
		global $adb;
		
		$columns = array(
			'syncid' => $syncid,
			'token' => $data['access_token'],
			'refresh_token' => $data['refresh_token'],
			'instance_url' => $data['instance_url'],
		);
		
		$res = $adb->pquery("INSERT INTO {$this->tables['tokens']} (".implode(',', array_keys($columns)).") VALUES (".generateQuestionMarks($columns).")", $columns);
	}
	
	protected function deleteSyncToken($syncid) {
		global $adb;
		$res = $adb->pquery("DELETE FROM {$this->tables['tokens']} WHERE syncid = ?", array($syncid));
	}
	
	protected function prepareColumnsForSave($typeid, $post) {
		$columns = array(
			'typeid' => $typeid,
			'authtype' => $post['authtype'],
		);
		// crmv@190016
		if ($this->hasSystemUrl($typeid)) {
			$url = $post['system_url'];
			if ($url[-1] != '/') $url .= '/'; // append slash
			$columns['system_url'] = $url;
		} else {
			$columns['system_url'] = null;
		}
		// crmv@190016e
		return $columns;
	}
	
	public function updateSync($syncid, $post = array(), $saveid = null) {
		global $adb;

		$typeid = intval($post['synctype']);

		$columns = $this->prepareColumnsForSave($typeid, $post);
		$updates = array();
		foreach ($columns as $k => $v) {
			$updates[] = $k.' = ?';
		}
		$columns['syncid'] = $syncid;
		$res = $adb->pquery("UPDATE {$this->tables['main']} SET ".implode(', ', $updates)." WHERE syncid = ?", $columns);
		
		// blob insert (array of json arrives)
		$modconfig = '['.implode(',', $post['modconfig']).']';
		$adb->updateClob($this->tables['main'], 'modconfig', "syncid = $syncid", $modconfig);
		
		if ($post['authtype'] === 'oauth2') {
			$this->updateSyncAuth($post['authtype'], $syncid, $post); // crmv@190016
			
			if ($saveid > 0) {
				$data = $this->loadOAuthData($saveid);
				if ($data) {
					$this->deleteSyncToken($syncid);
					$this->insertSyncToken($syncid, $data);
				}
			}
		// crmv@190016
		} elseif ($post['authtype'] === 'http') {
			
			$this->updateSyncAuth($post['authtype'], $syncid, $post);
		}
		// crmv@190016e
		
		return $syncid;
	}
	
	public function deleteSync($syncid) {
		global $adb;
		$adb->pquery("DELETE FROM {$this->tables['main']} WHERE syncid = ?", array($syncid));
		$adb->pquery("DELETE FROM {$this->tables['auth']} WHERE syncid = ?", array($syncid)); // crmv@190016
		$adb->pquery("DELETE FROM {$this->tables['tokens']} WHERE syncid = ?", array($syncid));
	}
	
	// --------------------- oauth ---------------------
	
	/**
	 * Save in session the configuration data, since it's required to retrieve the token
	 */
	public function insertOAuthData($post) {
		$saveid = rand(100,10000);
		while (VteSession::hasKeyArray(array('vtesync_oauth_data', $saveid))) {
			$saveid = rand(100,10000);
		}
		
		VteSession::setArray(array('vtesync_oauth_data', $saveid), $post);
		
		return $saveid;
	}
	
	public function replaceOAuthData($saveid, $data) {
		VteSession::setArray(array('vtesync_oauth_data', $saveid), $data);
	}
	
	public function loadOAuthData($saveid) {
		if (VteSession::hasKeyArray(array('vtesync_oauth_data', $saveid))) {
			return VteSession::getArray(array('vtesync_oauth_data', $saveid));
		}
		return null;
	}
	
	public function searchOAuthData($state) {
		$all = VteSession::get('vtesync_oauth_data');
		if (is_array($all)) {
			foreach ($all as $saveid => $data) {
				if ($data['state'] === $state) return $saveid;
			}
		}
				
		return null;
	}
	
	public function clearOAuthData($saveid) {
		if (VteSession::hasKeyArray(array('vtesync_oauth_data', $saveid))) {
			VteSession::removeArray(array('vtesync_oauth_data', $saveid));
		}
	}
	
	public function getOAuthRedirectUrl() {
		global $site_URL;
		
		$url = rtrim($site_URL, '/').'/syncauth.php';
		return $url;
	}
	
	// crmv@190016 crmv@195073
	public function getOAuthScopes($type = '') { 
		$scopes = array();
		if ($type == 'SalesForce' || $type == 'SuiteCRM') { // crmv@196666
			$scopes = array('api', 'refresh_token', 'offline_access');
		} elseif ($type == 'HubSpot') {
			$scopes = array('contacts', 'tickets');
		}
		return $scopes;
	}
	// crmv@190016e crmv@195073e
	
	public function getOAuthAuthUrl($typeid, $client_id, &$state = '') {
		// check if type is valid
		$types = $this->getSyncTypes();
		if (!array_key_exists($typeid, $types)) return false;
		// check if clientid is present
		if (empty($client_id)) return false;
		
		$provider = $this->getOAuthProvider($typeid, array('client_id' => $client_id));

		// crmv@195073
		$type = $this->getSyncTypeName($typeid);
		if ($type == 'HubSpot') {
			$options = [
				'scope' => $this->getOAuthScopes($type)
			];
			$authorizationUrl = $provider->getAuthorizationUrl($options);
		} else{
			$authorizationUrl = $provider->getAuthorizationUrl();
		}
		// crmv@195073e
		
		$authorizationUrl .= '&display=popup';
		
		// save the state, shoudl ne 
		$state = $provider->getState();
		
		return $authorizationUrl;
	}
	
	public function getOAuthProvider($typeid, $data = array()) {
		$type = $this->getSyncTypeName($typeid);
		
		$this->loadLibrary();
		
		if ($type == 'SalesForce') {
			$class = 'Stevenmaguire\OAuth2\Client\Provider\Salesforce';
		// crmv@190016
		} elseif ($type == 'Jira') {
			$class = 'Mrjoops\OAuth2\Client\Provider\Jira';
		// crmv@190016e
		// crmv@195073
		} elseif ($type == 'HubSpot') {
			$class = 'Flipbox\OAuth2\Client\Provider\HubSpot';
		// crmv@195073e
		// crmv@196666
		}elseif ($type == 'SuiteCRM') {
			$class = 'League\OAuth2\Client\Provider\GenericProvider';
		}
		// crmv@196666e
		if ($type == 'SuiteCRM') {
			$provider = new $class(array(
				'clientId'          => $data['client_id'],
				'clientSecret'      => $data['client_secret'],
				'redirectUri'       => $this->getOAuthRedirectUrl(),
				'urlAuthorize'		=> 'http://192.168.8.21/suitecrm/Api/access_token',						 
				'urlAccessToken' 	=> 'http://192.168.8.21/suitecrm/Api/access_token',
				'urlResourceOwnerDetails' =>	'',
				'scope' 			=> $this->getOAuthScopes($type), // crmv@190016
			));
		} else {	
			$provider = new $class(array(
				'clientId'          => $data['client_id'],
				'clientSecret'      => $data['client_secret'],
				'redirectUri'       => $this->getOAuthRedirectUrl(),
				'scope' 			=> $this->getOAuthScopes($type), // crmv@190016
				//'domain'            => '{custom-salesforce-domain}' // optional, defaults to https://login.salesforce.com
			));
		}
		
		return $provider;
	}
	
	public function checkOAuthState($state, $data) {
		return (!empty($state) && $state === $data['state']);
	}
	
	public function getAccessToken($code, $saveid) {
		
		$data = $this->loadOAuthData($saveid);
		
		$typeid = $data['typeid'];
		
		$provider = $this->getOAuthProvider($typeid, $data);
				
		try {

			// Try to get an access token using the authorization code grant.
			$accessToken = $provider->getAccessToken('authorization_code', array(
				'code' => $code,
			));
			
			// save the obtained code in the session
			// crmv@195073
			if ($typeid != 3) {
				$instanceUrl = $accessToken->getInstanceUrl();
			}
			// crmv@195073e
			
			$data['instance_url'] = $instanceUrl;
			$data['access_token'] = $accessToken->getToken();
			$data['refresh_token'] = $accessToken->getRefreshToken();

			$this->replaceOAuthData($saveid, $data);

		} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

			exit($e->getMessage());
			//return false;
		}
		
		return true;
	}
	
	// --------------------- tokens ---------------------
	
	
	// --------------------- auth types ---------------------
	
	// crmv@190016
	public function getAuthTypes($typeid) {
		// key -> label
		
		$type = $this->getSyncTypeName($typeid);
		
		if ($type == 'SalesForce' || $type == 'HubSpot' || $type == 'SuiteCRM') { // crmv@195073 crmv@196666
			$list = array('oauth2' => 'OAuth2');
		} elseif ($type == 'Jira' || $type == "Vtiger") { // crmv@197423
			$list = array('http' => 'Basic HTTP');
		}
		return $list;
	}
	// crmv@190016e
	
	//crmv@196666
	public function getOAuth2Flows($typeid) {
		$typeFlows = array();
		if (is_array($this->defaultSyncTypes[$typeid]['oauth2_flow_types'])) {
			foreach ($this->defaultSyncTypes[$typeid]['oauth2_flow_types'] as $type) {
				$typeFlows[$type] = getTranslatedString($type, 'Settings');
			}
		}
		
		return $typeFlows;
	}
	//crmv@196666e
	
	// --------------------- sync types ---------------------
	
	/**
	 * Return the list of possible synchronization types
	 * @return array
	 */
	public function getSyncTypes() {
		global $adb;
		
		if (!is_array(static::$typesCache)) {
			$types = array();
			$res = $adb->query("SELECT * FROM {$this->tables['types']}");
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$types[$row['typeid']] = $row['type'];
			}
			asort($types);
			static::$typesCache = $types;
		}
		
		return static::$typesCache;
	}
	
	/**
	 * Return the name for the specified typeid
	 * @param int $typeid The id of the type
	 * @return null|string
	 */
	public function getSyncTypeName($typeid) {
		$list = $this->getSyncTypes();
		if (array_key_exists($typeid, $list)) {
			return $list[$typeid];
		} else {
			return null;
		}
	}
	
	/**
	 * Return the id for the specified sync type
	 * @param string $name The name of the type
	 * @return null|int
	 */
	public function getSyncTypeId($name) {
		$list = $this->getSyncTypes();
		$k = array_search($name, $list, true);
		if ($k === false) $k = null;
		return $k;
	}

	/**
	 * Add a new sync type
	 */
	public function addSyncType($name, $modules = array()) {
		global $adb;
		
		// check if existing
		$id = $this->getSyncTypeId($name);
		if ($id !== null) return null;
		
		// I know there might be a race condition here, but inserts in this table are extremely rare
		$res = $adb->query("SELECT MAX(typeid) AS id FROM {$this->tables['types']}");
		$newid = intval($adb->query_result_no_html($res, 0, 'id')) + 1;
		
		$res = $adb->pquery("INSERT INTO {$this->tables['types']} (typeid, type) VALUES (?,?)", array($newid, $name));
		static::$typesCache = null;
		
		if (count($modules) > 0) {
			$this->addTypeModules($newid, $modules);
		}
		
		return $newid;
	}
	
	public function getTypeModules($typeid) {
		global $adb;
		
		if (!is_array(static::$typesModsCache[$typeid])) {
			$mods = array();
			$res = $adb->pquery("SELECT module FROM {$this->tables['types_mods']} WHERE typeid = ?", array($typeid));
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$mods[$row['module']] = getTranslatedString($row['module'], $row['module']);
			}
			asort($mods);
			static::$typesModsCache[$typeid] = $mods;
		}
		
		return static::$typesModsCache[$typeid];
	}
	
	public function addTypeModule($typeid, $module) {
		return $this->addTypeModules($typeid, array($module));
	}
	
	public function addTypeModules($typeid, $modules) {
		global $adb;
		
		foreach ($modules as $module) {
			$res = $adb->pquery("SELECT typeid FROM {$this->tables['types_mods']} WHERE typeid = ? AND module = ?", array($typeid, $module)); 
			if ($res && $adb->num_rows($res) == 0) {
				$adb->pquery("INSERT INTO {$this->tables['types_mods']} (typeid, module) VALUES (?,?)", array($typeid, $module));
			}
		}
	}
	
	// crmv@190016
	public function hasSystemUrl($typeid) {
		$hasit = !!$this->defaultSyncTypes[$typeid]['has_system_url'];
		return $hasit;
	}
	
	public function getSystemUrlExample($typeid) {
		return $this->defaultSyncTypes[$typeid]['system_url_example'];
	}
	// crmv@190016e
	
	// --------------------- install functions ---------------------
	
	protected function installSyncTypes() {
		global $adb;

		$adb->startTransaction();
		foreach ($this->defaultSyncTypes as $typeid => $typeinfo) {
			$this->addSyncType($typeinfo['name'], $typeinfo['modules']);
		}
		$adb->completeTransaction();
	}
	
	protected function installSettings() {
		global $adb, $table_prefix;
		
		require_once('vtlib/Vtecrm/SettingsBlock.php');//crmv@207871
		require_once('vtlib/Vtecrm/SettingsField.php');//crmv@207871
		$block = Vtecrm_SettingsBlock::getInstance('LBL_STUDIO');
		$res = $adb->pquery("select fieldid from {$table_prefix}_settings_field where name = ?", array('LBL_SYNC_SETTINGS'));
		if ($res && $adb->num_rows($res) == 0) {
			$field = new Vtecrm_SettingsField();
			$field->name = 'LBL_SYNC_SETTINGS';
			$field->iconpath = 'vtesync.png';
			$field->description = 'LBL_SYNC_SETTINGS_DESC';
			$field->linkto = 'index.php?module=Settings&action=VteSync&parenttab=Settings';
			$block->addField($field);
			
			$res = $adb->pquery("SELECT sequence FROM {$table_prefix}_settings_field WHERE blockid = ? AND name = ?", array($block->id, 'LBL_DATA_IMPORTER'));
			$dataImpSeq = intval($adb->query_result_no_html($res, 0, 'sequence'));
			
			// move after import data
			$adb->pquery(
				"UPDATE {$table_prefix}_settings_field SET sequence = sequence + 1 WHERE blockid = ? AND sequence > ?", 
				array($block->id, $dataImpSeq)
			);
			$adb->pquery(
				"UPDATE {$table_prefix}_settings_field SET sequence = ? WHERE blockid = ? AND name = ?", 
				array($dataImpSeq + 1, $block->id, 'LBL_SYNC_SETTINGS')
			);
		}
	}
	
	protected function installCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('VteSync');
		if (empty($cj)) {
			$CU = CronUtils::getInstance();
			$cj = new CronJob();
			$cj->name = 'VteSync';
			$cj->active = 1;
			$cj->singleRun = false;
			$cj->maxAttempts = 0;	// disable attempts check
			$cj->timeout = 3600;	// 60 minutes timeout
			$cj->repeat = 60;		// repeat every min
			$cj->fileName = 'cron/modules/VteSync/VteSync.service.php';
			$CU->insertCronJob($cj);
		}

	}
	
	protected function installWebservice() {
		global $adb, $table_prefix;
		
		require_once('include/Webservices/Utils.php');
		$operationName = 'vtesync.get_changes';

		$res = $adb->pquery("select operationid from {$table_prefix}_ws_operation where name = ?", array($operationName));
		if ($res && $adb->num_rows($res) == 0) {
			$operationId = vtws_addWebserviceOperation($operationName, 'modules/VteSync/api/GetChanges.php', 'vteSync_getChanges', 'GET');
		} else {
			$operationId = $adb->query_result_no_html($res, 0, 'operationid');
		}

		if ($operationId > 0) {

			$paramName = 'module';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',1);
			}

			$paramName = 'dateFrom';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',2);
			}

			$paramName = 'dateTo';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',3);
			}

			$paramName = 'dateField';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',4);
			}

			$paramName = 'maxEntries';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',5);
			}

			$paramName = 'showDeleted';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'Boolean',6);
			}

			$paramName = 'delegatedUser';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',7);
			}
		}
		
		// crmv@195073
		$operationName = 'vtesync.get_all_related_ids';
		$res = $adb->pquery("select operationid from {$table_prefix}_ws_operation where name = ?", array($operationName));
		if ($res && $adb->num_rows($res) == 0) {
			$operationId = vtws_addWebserviceOperation($operationName, 'modules/VteSync/api/GetChanges.php', 'vteSync_getAllRelatedIds', 'GET');
		} else {
			$operationId = $adb->query_result_no_html($res, 0, 'operationid');
		}
		if ($operationId > 0) {
			$paramName = 'firstModule';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',1);
			}
			
			$paramName = 'secondModule';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',2);
			}
			
			$paramName = 'relationId';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',3);
			}
			
			$paramName = 'crmid';
			$checkQuery = "SELECT * FROM {$table_prefix}_ws_operation_parameters where operationid=? and name=?";
			$operationResult = $adb->pquery($checkQuery,array($operationId,$paramName));
			if ($adb->num_rows($operationResult) <= 0) {
				$status = vtws_addWebserviceOperationParam($operationId, $paramName, 'String',4);
			}
		}
		// crmv@195073e
	}
	
	protected function installLanguages() {
		$trans = array(
			'Settings' => array(
				'it_it' => array(
					'LBL_SYNC_SETTINGS' => 'Sincronizzazioni',
					'LBL_SYNC_SETTINGS_DESC' => 'Configura sistemi esterni per la sincronizzazione dei dati',
					'LBL_VTESYNC_SYNCLIST' => 'Sincronizzazioni configurate',
					'LBL_VTESYNC_CREATE' => 'Crea sincronizzazione',
					'LBL_VTESYNC_EDIT' => 'Modifica sincronizzazione',
					'LBL_VTESYNC_TYPE' => 'Sistema esterno',
					'LBL_VTESYNC_MODULES' => 'Moduli da sincronizzare',
					'LBL_VTESYNC_TYPE_DESC' => 'Il sistema esterno a cui connettersi',
					'LBL_VTESYNC_AVAILMODS' => 'Moduli disponibili',
					'LBL_VTESYNC_SELECTEDMODS' => 'Moduli selezionati',
					'LBL_VTESYNC_AUTH_SETTINGS' => 'Autenticazione',
					'LBL_VTESYNC_AUTHTYPE' => 'Tipo',
					'LBL_VTESYNC_AUTHTYPE_DESC' => 'Meccanismo di autenticazione da usare',
					'LBL_VTESYNC_CLIENTID' => 'Client ID',
					'LBL_VTESYNC_CLIENTID_DESC' => 'Client ID dell\'app creata',
					'LBL_VTESYNC_CLIENTSECRET' => 'Client Secret',
					'LBL_VTESYNC_CLIENTSECRET_DESC' => 'Chiave associata al Client ID',
					'LBL_VTESYNC_AUTHORIZE' => 'Autorizza',
					'LBL_VTESYNC_REVOKE' => 'Revoca autorizzazione',
					'LBL_VTESYNC_AUTHORIZED' => 'Autorizzato',
					'LBL_VTESYNC_NOT_AUTHORIZED' => 'Non autorizzato',
					'LBL_VTESYNC_CONFIG_MODULE' => 'Configurazione modulo',
					'LBL_VTESYNC_MODCFG_DIRECTION' => 'Direzione',
					'LBL_VTESYNC_MODCFG_DIR_TO_VTE' => 'Da sistema esterno a VTE',
					'LBL_VTESYNC_MODCFG_DIR_FROM_VTE' => 'Da VTE a sistema esterno',
					'LBL_VTESYNC_MODCFG_DIR_BOTH' => 'Entrambe',
					'LBL_VTESYNC_MODCFG_DELETIONS' => 'Propaga dati eliminati',
					'LBL_VTESYNC_MODCFG_DEL_IN_VTE' => 'In VTE',
					'LBL_VTESYNC_MODCFG_DEL_IN_EXT' => 'Nel sistema esterno',
					'LBL_VTESYNC_MODCFG_DEL_NONE' => 'Nessuna',
					'LBL_VTESYNC_MODCFG_SYNC_PLIST' => 'Sincronizza voci picklist',
					'LBL_VTESYNC_DUP_TYPE' => 'Esiste già una sincronizzazione di questo tipo con lo stesso Client ID',
					'LBL_VTESYNC_LAST_RUN' => 'Ultima esecuzione',
					'LBL_VTESYNC_OAUTH_HELP_1' => 'Per ottenere un Client ID e la relativa chiave, seguire le istruzioni al link seguente:',
					'LBL_VTESYNC_OAUTH_HELP_2' => 'usando come Callback URL il seguente:',
					'LBL_VTESYNC_OAUTH_HELP_3' => 'e gli scope seguenti:',
					// crmv@190016
					'LBL_VTESYNC_USERNAME' => 'Nome utente',
					'LBL_VTESYNC_USERNAME_DESC' => 'L\'utente da usare per connettersi. Deve essere una utenza amministrativa',
					'LBL_VTESYNC_PASSWORD' => 'Password',
					'LBL_VTESYNC_SYSTEMURL' => 'Indirizzo dell\'istanza',
					// crmv@190016e
				),
				'en_us' => array(
					'LBL_SYNC_SETTINGS' => 'Synchronizations',
					'LBL_SYNC_SETTINGS_DESC' => 'Configure external systems for data synchronization',
					'LBL_VTESYNC_SYNCLIST' => 'Configured synchronizations',
					'LBL_VTESYNC_CREATE' => 'Create synchronization',
					'LBL_VTESYNC_EDIT' => 'Edit synchronization',
					'LBL_VTESYNC_TYPE' => 'External system',
					'LBL_VTESYNC_MODULES' => 'Modules to synchronize',
					'LBL_VTESYNC_TYPE_DESC' => 'The external system to connect to',
					'LBL_VTESYNC_AVAILMODS' => 'Available modules',
					'LBL_VTESYNC_SELECTEDMODS' => 'Selected modules',
					'LBL_VTESYNC_AUTH_SETTINGS' => 'Authentication',
					'LBL_VTESYNC_AUTHTYPE' => 'Type',
					'LBL_VTESYNC_AUTHTYPE_DESC' => 'Authentication method to use',
					'LBL_VTESYNC_CLIENTID' => 'Client ID',
					'LBL_VTESYNC_CLIENTID_DESC' => 'Client ID of the app',
					'LBL_VTESYNC_CLIENTSECRET' => 'Client Secret',
					'LBL_VTESYNC_CLIENTSECRET_DESC' => 'Key associated to the Client ID',
					'LBL_VTESYNC_AUTHORIZE' => 'Authorize',
					'LBL_VTESYNC_REVOKE' => 'Revoke authorization',
					'LBL_VTESYNC_AUTHORIZED' => 'Authorized',
					'LBL_VTESYNC_NOT_AUTHORIZED' => 'Not authorized',
					'LBL_VTESYNC_CONFIG_MODULE' => 'Configure module',
					'LBL_VTESYNC_MODCFG_DIRECTION' => 'Direction',
					'LBL_VTESYNC_MODCFG_DIR_TO_VTE' => 'From external system to VTE',
					'LBL_VTESYNC_MODCFG_DIR_FROM_VTE' => 'From VTE to external system',
					'LBL_VTESYNC_MODCFG_DIR_BOTH' => 'Both',
					'LBL_VTESYNC_MODCFG_DELETIONS' => 'Propagate deleted data',
					'LBL_VTESYNC_MODCFG_DEL_IN_VTE' => 'In VTE',
					'LBL_VTESYNC_MODCFG_DEL_IN_EXT' => 'In the external system',
					'LBL_VTESYNC_MODCFG_DEL_NONE' => 'None',
					'LBL_VTESYNC_MODCFG_SYNC_PLIST' => 'Synchronize picklists entries',
					'LBL_VTESYNC_DUP_TYPE' => 'A synchronization of this type already exists with the same Client ID',
					'LBL_VTESYNC_LAST_RUN' => 'Last execution',
					'LBL_VTESYNC_OAUTH_HELP_1' => 'To obtain a Client ID, follow the instructions at the following link:',
					'LBL_VTESYNC_OAUTH_HELP_2' => 'using as Callback Url the following:',
					'LBL_VTESYNC_OAUTH_HELP_3' => 'and the following scopes:',
					// crmv@190016
					'LBL_VTESYNC_USERNAME' => 'Username',
					'LBL_VTESYNC_USERNAME_DESC' => 'It should be an administrative user',
					'LBL_VTESYNC_PASSWORD' => 'Password',
					'LBL_VTESYNC_SYSTEMURL' => 'Instance URL',
					// crmv@190016e
				),
			),
			// these are problematic at install time!
			/*'ALERT_ARR' => array(
				'it_it' => array(
					'LBL_VTESYNC_SELECT_TYPE' => 'Devi selezionare un sistema esterno',
					'LBL_VTESYNC_SELECT_MODS' => 'Devi selezionare almeno un modulo',
					'LBL_VTESYNC_SELECT_AUTH' => 'Devi selezionare un tipo di autenticazione',
					'LBL_VTESYNC_FILL_OAUTH2' => 'Devi inserire tutti i dati necessari all\'autenticazione',
					'LBL_VTESYNC_OAUTH2_AUTH' => 'Devi autorizzare le credenziali inserite',
				),
				'en_us' => array(
					'LBL_VTESYNC_SELECT_TYPE' => 'You have to select an external system',
					'LBL_VTESYNC_SELECT_MODS' => 'You have to select at least one module',
					'LBL_VTESYNC_SELECT_AUTH' => 'You have to select an authentication method',
					'LBL_VTESYNC_FILL_OAUTH2' => 'You have to fill all fields needed for authentication',
					'LBL_VTESYNC_OAUTH2_AUTH' => 'You have to authorize the provided credentials',
				),
			),*/
			'APP_STRINGS' => array(
				'it_it' => array(
					'LBL_REMOVE_ITEM' => 'Rimuovi',
				),
				'en_us' => array(
					'LBL_REMOVE_ITEM' => 'Remove',
				),
			),
		);
		
		foreach ($trans as $module=>$modlang) {
			foreach ($modlang as $lang=>$translist) {
				foreach ($translist as $label=>$translabel) {
					SDK::setLanguageEntry($module, $lang, $label, $translabel);
				}
			}
		}
	}
	
	public function enableSettings() {
		global $adb, $table_prefix;
		$adb->pquery("UPDATE {$table_prefix}_settings_field SET active = 0 WHERE name = ?", array('LBL_SYNC_SETTINGS'));
	}
	
	public function disableSettings() {
		global $adb, $table_prefix;
		$adb->pquery("UPDATE {$table_prefix}_settings_field SET active = 1 WHERE name = ?", array('LBL_SYNC_SETTINGS'));
	}
	
	public function enableCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('VteSync');
		if ($cj) {
			$cj->activate();
		}
	}
	
	public function disableCronjob() {
		require_once('include/utils/CronUtils.php');
		$cj = CronJob::getByName('VteSync');
		if ($cj) {
			$cj->deactivate();
		}
	}
	
	/**
	 * The standard vtlib handler
	 */
	public function vtlib_handler($moduleName, $event_type) {
		global $adb, $table_prefix;

		if($event_type == 'module.postinstall') {
			$adb->pquery("UPDATE {$table_prefix}_tab SET customized = 0 WHERE name=?", array($moduleName));
			$this->installSyncTypes();
			$this->installSettings();
			$this->installCronjob();
			$this->installWebservice();
			$this->installLanguages();
		} else if($event_type == 'module.disabled') {
			$this->disableSettings();
			$this->disableCronjob();
		} else if($event_type == 'module.enabled') {
			$this->enableSettings();
			$this->enableCronjob();
		} else if($event_type == 'module.preuninstall') {
			
		} else if($event_type == 'module.preupdate') {
			
		} else if($event_type == 'module.postupdate') {
			$this->installSyncTypes(); // crmv@190016
			$this->installWebservice(); // crmv@195073
		}
	}
	
}