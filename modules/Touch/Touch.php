<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33097 - Initial offline support */
/* crmv@34559 - ModComments, filters */
/* crmv@38476 - SDK */
/* crmv@39106 - Documents, PDFMaker */
/* crmv@39110 - Profiling */
/* crmv@42537 - Messages */
/* crmv@49398 - New webservice version, offline support */
/* crmv@55371 - Bugfix */
/* crmv@56798 - Caching */
/* crmv@71388 - File upload */
/* crmv@99131 - Support for Charts */
/* crmv@99132 - Support for Wizards */
/* crmv@95788 - Support for replay check */
/* crmv@107655 - Support for Messages accounts */

/*
 * TODO Touch:
 * 1. gestione centralizzata dei messaggi di errore
 */

require_once('modules/Touch/TouchWSClass.php');
require_once('modules/Touch/TouchUtils.php');
require_once('modules/Touch/TouchCache.php');
require_once('include/Webservices/Login.php');


class Touch extends SDKExtendableUniqueClass { // crmv@42024

	// versione API Touch lato server - quando si cambia, l'app mostra un warning al login
	public $version = "2.2";

	// versione to show to legacy api calls
	public $legacyVersion = "1.4.1";

	// limite di elementi per pagina
	public $listPageLimit = 50;

	// limite per autocomplete
	public $autocompleteLimit = 5;		// crmv@86915
	
	public $longOperationTime = 600;	// seconds for long webservices

	/**
	 * These modules are completely excluded from the App
	 */
	public $excluded_modules = array('Fax', 'Sms', 'Emails', 'PriceBooks', 'PBXManager', 'WebMails', 'Projects', 'Newsletter', 'Targets', 'Campaigns', 'Telemarketing', 'Transitions'); // crmv@33311 crmv@164120
	
	/**
	 * These modules are excluded only from the main list
	 */
	public $excluded_modules_list = array('ModNotifications', 'ModComments');

	/** 
	 * Force the modules in the home page with this ordering
	 * Special values: Recents, Favourites, Search, Areas, PageBreak
	 *   PageBreak = force the creation of a new page
	 *   Areas = replaced with modules in the areas, separated by PageBreak
	 * All the remaining modules (not listed here, or in any Area) go to the end of the list
	 */
	public $modules_list_order = array(
		'Calendar', 'Events', 'Processes', 'Messages', 'MyNotes', 'Myfiles', 'Recents', 'Favourites', 'Search', 'PageBreak', // crmv@188277
		'Areas',
		/*'Contacts', 'Leads', 'Accounts', 'Vendors', 'PageBreak',
		'ProjectPlan', 'ProjectTask', 'ProjectMilestone', 'PageBreak',
		'Potentials', 'Quotes', 'Invoice', 'SalesOrder', 'PageBreak',
		'Products', 'Services', 'PriceBooks', 'ProductLines', 'PurchaseOrder', 'PageBreak',*/
	);

	// hide these pages from some modules (valid values: recents, favourites, filters, search, folders, foldercont)
	public $hide_module_pages = array(
		'Myfiles' => array('recents', 'favourites', 'filters'),
		'MyNotes' => array('recents', 'favourites', 'filters', 'folders', 'foldercont'),
		'Processes' => array('recents', 'favourites', 'folders', 'foldercont'), // crmv@188277
		'Charts' => array('filters'),
	);

	// force these permissions for these modules, instead of using the isPermitted check
	public $force_module_premissions = array(
		'Processes' => array('perm_create' => false), // crmv@198545
		//'Myfiles' => array('perm_create' => false, 'perm_write' => false),
	);

	
	/**
	 * DEPRECATED
	 */
	public $offline_max_items = 100;
	
	/**
	 * DEPRECATED
	 */
	public $offline_chunks = 50;
	
	// crmv@85213
	/**
	 * The strategy to use when handling with save collision generated from Offline App
	 * eg: A record is saved in the app offline, then updated in the VTE, than the app goes online
	 *   and try to save the record.
	 * 4 Modes available: 
	 *   "": no collision checking
	 *   "VTE": the edit from the VTE wins, regardless of the timestamp
	 *   "App": the edit from the App wins, regardless of the timestamp
	 *   "Last": the last edit wins
	 */
	public $collision_strategy = 'Last';
	
	/**
	 * Not implemented yet!
	 * "record" or "field"
	 */
	public $collision_level = 'record';
	// crmv@85213

	/**
	 * If true, the replay check is enabled (if the app supports this feature).
	 * This means that the same request cannot be processed more than once if 
	 * it was succesful. This is especially useful when the client is on a faulty
	 * connection, and it doesn't receive the server response
	 */
	public $replay_check = true;
	
	public $table_name_prop = '';
	public $table_name_user_prop = '';
	public $table_name_user_dev_prop = '';
	public $table_requests = '';

	/**
	 * List of Touch Webservices
	 * The value can be a single PHP file or an array in the form array('class'=>classname, 'file'=>filename)
	 * The latter is useful when overriding some webservices (only version >= 2), or part of them. 
	 * In this case you can extend the class Touch and redefine the desired webservice with something like 
	 *  array('class'=>'MyNewWebservice', 'file'=>'MyNewFile.php') and in the MyNewFile.php the class 
	 * MyNewWebservice extends the old webservice to add/modify functionalities
	 */
	public $webservices = array(
		'Login' => 'TouchLogin.php',
		'Logout' => 'TouchLogout.php', // crmv@91082
		'ModulesList' => 'TouchModulesList.php',
		'GetBlocks' => 'TouchGetBlocks.php',
		'GetComments' => 'TouchGetComments.php',
		'GetTicketComments' => 'TouchGetTicketComments.php',
		'WriteComment' => 'TouchWriteComment.php',
		'EditComment' => 'TouchEditComment.php',	// crmv@93148
		'WriteTicketComment' => 'TouchWriteTicketComment.php',
		'GetFavorites' => 'TouchGetFavorites.php',
		'SetFavorites' => 'TouchSetFavorites.php',
		'GetNotifications' => 'TouchGetNotifications.php',
		'SeeNotifications' => 'TouchSeeNotifications.php',
		'UnseeNotifications' => 'TouchUnseeNotifications.php',
		'GetChanges' => 'TouchGetChanges.php',
		'GetRecents' => 'TouchGetRecents.php',
		'GetTodos' => 'TouchGetTodos.php',
		'GetList' => 'TouchGetList.php',
		'GetFilterList' => 'TouchGetFilterList.php',
		'GetRecord' => 'TouchGetRecord.php',
		'GetRecords' => 'TouchGetRecords.php',
		'GetRelated' => 'TouchGetRelated.php',
		'GetRelatedList' => 'TouchGetRelatedList.php',
		'GetUsers' => 'TouchGetUsers.php',
		'GetRoles' => 'TouchGetRoles.php',
		'GetGroups' => 'TouchGetGroups.php',
		'GetAvatars' => 'TouchGetAvatars.php',
		'SaveRecord' => 'TouchSaveRecord.php',
		'DeleteRecord' => 'TouchDeleteRecord.php',
		'GetAssociatedProducts' => 'TouchGetAssociatedProducts.php',
		'DeleteRelation' => 'TouchDeleteRelation.php',
		'SaveRelation' => 'TouchSaveRelation.php',
		'SaveFilter' => 'TouchSaveFilter.php',
		'GetOfflineData' => 'TouchGetOfflineData.php',
		'GetOverrideFile' => 'TouchGetOverrideFile.php',
		'PDFMaker' => 'TouchPDFMaker.php',
		'SimpleEdit' => 'TouchSimpleEdit.php', // crmv@39110
		'GetMenuList' => 'TouchGetMenuList.php', // crmv@42707
		'GetMessagesMeta' => 'TouchGetMessagesMeta.php', // crmv@42537
		'GetMessagesCount' => 'TouchGetMessagesCount.php',
		'Autocomplete' => 'TouchAutocomplete.php',
		'SendMail' => 'TouchSendMail.php',
		'SetFlag' => 'TouchSetFlag.php',
		'MoveMessage' => 'TouchMoveMessage.php',	// crmv@57010
		'SetRecent' => 'TouchSetRecent.php',
		'GetLinkedRecords' => 'TouchGetLinkedRecords.php',
		'LinkModules' => 'TouchLinkModules.php',
		'GetAreas' => 'TouchGetAreas.php',
		'GlobalSearch' => 'TouchGlobalSearch.php',
		'ShareToken' => 'TouchShareToken.php',
		'AnswerInvitation' => 'TouchAnswerInvitation.php',
		'MultiCall' => 'TouchMultiCall.php',
		'Geolocation' => 'TouchGeolocation.php',
		'UploadFile' => 'TouchUploadFile.php',
		'ConvertLead' => 'TouchConvertLead.php',
		'ModuleAutocomplete' => 'TouchModuleAutocomplete.php', // crmv@86915
		'Geocoding' => 'TouchGeocoding.php', // crmv@86915
		'GetWebservices' => 'TouchGetWebservices.php', // crmv@93148
		'GetChartData' => 'TouchGetChartData.php',
		'GetWizards' => 'TouchGetWizards.php',
		'GetMessagesAccounts' => 'TouchGetMessagesAccounts.php',
		'SaveMessagesAccount' => 'TouchSaveMessagesAccount.php',
		'SaveMessagesFolders' => 'TouchSaveMessagesFolders.php',
		'DeleteMessagesAccount' => 'TouchDeleteMessagesAccount.php',
		'GetEmailDirectory' => 'TouchGetEmailDirectory.php',
		'GetCurrencies' => 'TouchGetCurrencies.php', // crmv@134732
		'TrackingList' => 'TouchTrackingList.php', // crmv@124979
		'MessagesIcs' => 'TouchMessagesIcs.php', // crmv@174249
		'GetProcessesCount' => 'TouchGetProcessesCount.php', // crmv@188277
	);

	protected $isPermitted = null;

	public function __construct() {
		global $table_prefix;
		
		$this->table_name_prop = $table_prefix.'_touch_prop';
		$this->table_name_user_prop = $table_prefix.'_touch_user_prop';
		$this->table_name_user_dev_prop = $table_prefix.'_touch_user_dev_prop';
		$this->table_requests = $table_prefix.'_touch_requests';
		$this->table_tempids = $table_prefix.'_touch_tempids'; // crmv@106521
		$this->table_wipedata = $table_prefix.'_touch_wipedata'; // crmv@161368
		$this->checkTables();
	}
	
	protected function checkTables() {
		global $adb;
		
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_name_prop.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="property" type="C" size="63">
					<KEY/>
				</field>
				<field name="value" type="C" size="1023" />
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_name_prop)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_name_user_prop.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="property" type="C" size="63">
					<KEY/>
				</field>
				<field name="value" type="C" size="1023" />
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_name_user_prop)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_name_user_dev_prop.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="deviceid" type="C" size="63">
					<KEY/>
				</field>
				<field name="property" type="C" size="63">
					<KEY/>
				</field>
				<field name="value" type="C" size="1023" />
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_name_user_dev_prop)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}

		// crmv@95788
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_requests.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="deviceid" type="C" size="63">
					<KEY/>
				</field>
				<field name="requestid" type="I" size="19">
					<KEY/>
				</field>
				<field name="status" type="C" size="31" />
				<field name="request_date" type="T">
					<default value="0000-00-00 00:00:00" />
				</field>
				<field name="completion_date" type="T">
					<default value="0000-00-00 00:00:00" />
				</field>
				<field name="wsname" type="C" size="63" />
				<field name="return_data" type="XL" />
				<index name="touch_requests_status">
					<col>status</col>
				</index>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_requests)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		// crmv@95788e
		
		// crmv@106521
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_tempids.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="deviceid" type="C" size="63">
					<KEY/>
				</field>
				<field name="crmid" type="I" size="19">
					<KEY/>
				</field>
				<field name="temp_crmid" type="I" size="19" />
				<field name="insert_date" type="T">
					<default value="0000-00-00 00:00:00" />
				</field>
				<index name="touch_tempids_date_idx">
					<col>insert_date</col>
				</index>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_tempids)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		// crmv@106521e
		
		// crmv@161368
		$schema_table =
		'<schema version="0.3">
			<table name="'.$this->table_wipedata.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="wipe_date" type="T">
					<default value="0000-00-00 00:00:00" />
				</field>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($this->table_wipedata)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		// crmv@161368e
	}

	public function initDefaultProperties() {
		// set properties if not already set
		$list = array(
			'use_offline_cache' => 1,	// crmv@73256
			'use_geolocation' => 0,		// crmv@59610
			'app_theme' => 'vte16',		// crmv@86915
		);

		foreach ($list as $prop=>$value) {
			$oldVal = $this->getProperty($prop);
			if ($oldVal === false) $this->setProperty($prop, $value);
		}
	}

	// FALSE if not found
	public function getProperty($property) {
		global $adb, $table_prefix;

		$r = $adb->pquery("SELECT value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r && $adb->num_rows($r) > 0) {
			return $adb->query_result_no_html($r, 0, 'value');
		}
		return false;
	}
	
	public function getUserProperty($userid, $property) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_user_prop} WHERE userid = ? AND property = ?", array($userid, $property));
		if ($r && $adb->num_rows($r) > 0) {
			return $adb->query_result_no_html($r, 0, 'value');
		}
		return false;
	}
	
	public function getUserDeviceProperty($userid, $deviceid, $property) {
		global $adb, $table_prefix;
	
		$r = $adb->pquery("SELECT value FROM {$this->table_name_user_dev_prop} WHERE userid = ? AND deviceid = ? AND property = ?", array($userid, $deviceid, $property));
		if ($r && $adb->num_rows($r) > 0) {
			return $adb->query_result_no_html($r, 0, 'value');
		}
		return false;
	}
	
	public function setProperty($property, $value) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r) {
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_prop} SET value = ? WHERE property = ?", array($value, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_prop} (property, value) VALUES (?,?)", array($property, $value));
			}
		}
	}
	
	public function setUserProperty($userid, $property, $value) {
		global $adb, $table_prefix;
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_user_prop} WHERE userid = ? AND property = ?", array($userid, $property));
		if ($r) {
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_user_prop} SET value = ? WHERE userid = ? AND property = ?", array($value, $userid, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_user_prop} (userid, property, value) VALUES (?,?,?)", array($userid, $property, $value));
			}
		}
	}
	
	public function setUserDeviceProperty($userid, $deviceid, $property, $value) {
		global $adb, $table_prefix;
	
		$r = $adb->pquery("SELECT value FROM {$this->table_name_user_dev_prop} WHERE userid = ? AND deviceid = ? AND property = ?", array($userid, $deviceid, $property));
		if ($r) {
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_user_dev_prop} SET value = ? WHERE userid = ? AND deviceid = ? AND property = ?", array($value, $userid, $deviceid, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_user_dev_prop} (userid, deviceid, property, value) VALUES (?,?,?,?)", array($userid, $deviceid, $property, $value));
			}
		}
	}
	
	public function deleteProperty($property) {
		global $adb, $table_prefix;
		$r = $adb->pquery("DELETE FROM {$this->table_name_prop} WHERE property = ?", array($property));
	}
	
	public function deleteUserProperty($userid, $property) {
		global $adb, $table_prefix;
		$r = $adb->pquery("DELETE FROM {$this->table_name_user_prop} WHERE userid = ? AND property = ?", array($userid, $property));
	}
	
	public function deleteUserDeviceProperty($userid, $deviceid, $property) {
		global $adb, $table_prefix;
		$r = $adb->pquery("DELETE FROM {$this->table_name_user_dev_prop} WHERE userid = ? AND deviceid = ? AND property = ?", array($userid, $deviceid, $property));
	}
	
	// crmv@106521
	public function setTempId($userid, $deviceId, $crmid, $tempid) {
		global $adb, $table_prefix;
		
		if (!$deviceId) {
			$deviceId = $this->getCurrentDeviceId();
		}
		
		if ($userid > 0 && $deviceId) {
			$params = array($userid, $deviceId, $crmid, $tempid, date('Y-m-d H:i:s'));
			$adb->pquery("INSERT INTO {$this->table_tempids} (userid, deviceid, crmid, temp_crmid, insert_date) VALUES (?,?,?,?,?)", $params);
			return true;
		}
		return false;
	}
	
	/**
	 * Get the pair tempid, crmid for the user
	 */
	public function getTempId($userid, $deviceId, $crmid) {
		global $adb, $table_prefix;
		
		if (!$deviceId) {
			$deviceId = $this->getCurrentDeviceId();
		}
		
		if ($userid > 0 && $deviceId) {
			$params = array($userid, $deviceId, $crmid);
			$res = $adb->pquery("SELECT temp_crmid FROM {$this->table_tempids} WHERE userid = ? AND deviceid = ? AND crmid = ?", $params);
			if ($res && $adb->num_rows($res) > 0) {
				$tcrmid = $adb->query_result_no_html($res, 0, 'temp_crmid');
				return $tcrmid;
			}
		}
		return null;
	}
	
	// crmv@159113
	/**
	 * Get the rela crmid for the specified temporary id
	 */
	public function getRealId($userid, $deviceId, $tcrmid) {
		global $adb, $table_prefix;
		
		if (!$deviceId) {
			$deviceId = $this->getCurrentDeviceId();
		}
		
		if ($userid > 0 && $deviceId) {
			$params = array($userid, $deviceId, $tcrmid);
			$res = $adb->pquery("SELECT crmid FROM {$this->table_tempids} WHERE userid = ? AND deviceid = ? AND temp_crmid = ?", $params);
			if ($res && $adb->num_rows($res) > 0) {
				$crmid = $adb->query_result_no_html($res, 0, 'crmid');
				return $crmid;
			}
		}
		return null;
	}
	// crmv@159113e
	
	/**
	 * Clean old temp ids
	 */
	public function cleanTempIds($userid = null) {
		global $adb, $table_prefix, $current_user;
		
		$pruneDays = 7;	// delete stuff older than 7 days
		
		$sql = "DELETE FROM {$this->table_tempids} WHERE insert_date < ?";
		$params = array(date('Y-m-d H:i:s', time()-$pruneDays*3600*24));
		
		if ($userid > 0) {
			$sql .= " AND userid = ?";
			$params[] = $userid;
		}

		$res = $adb->pquery($sql, $params);
	
	}
	// crmv@106521e
	
	public function processLoginData($data) {
		global $current_user;
		
		$data = Zend_Json::decode(base64_decode($data));
		
		if (is_array($data) && is_array($data['s']) && $data['d']) {
			$this->setUserDeviceProperty($current_user->id, $data['d'], 'login_data', base64_encode(implode(':', $data['s'])));
			// crmv@91082 - save the deviceid in the session
			VteSession::set('touch_device_id', $data['d']);
			// crmv@91082e
		}
	}
	
	public function retrieveLoginData($deviceid) {
		global $current_user;
		$sums = base64_decode($this->getUserDeviceProperty($current_user->id, $deviceid, 'login_data'));
		return base64_encode(Zend_Json::encode(array('s' => $sums, 'd'=>$deviceid)));
	}

	// crmv@91082
	public function getCurrentDeviceId() {
		$devid = VteSession::get('touch_device_id');
		if (empty($devid) && !empty($_REQUEST['deviceid'])) {
			$devid = $_REQUEST['deviceid'];
			VteSession::set('touch_device_id', $devid);
		}
		return $devid;
	}
	// crmv@91082e
	
	/**
	 * DEPRECATED
	 */
	public function isOfflineEnabled() {
		return true;
	}
	
	// cache the isPermitted result to avoid multiple checks
	protected function isTouchPermitted() {
		if (is_null($this->isPermitted)) {
			$this->isPermitted = (isPermitted('Touch', 'DetailView') == 'yes');
		}
		return $this->isPermitted;
	}

	/**
	 * Return the folder which contains the webservices for the specified version
	 */
	protected function getVersionFolder($version) {
		$folder = 'vtws';
		if (!empty($version) && version_compare($version, '2.0', '>=')) {
			$folder = 'vtws2';
		}
		return $folder;
	}
	
	/**
	 * Return True if the webservice exists
	 */
	public function wsExists($wsname, $version = null) {
		$fname = $this->getWSFile($wsname, $version);
		return ($fname && is_readable($fname));
	}

	/**
	 * Return the filename for the specified webservice and version. NULL if not found
	 */
	public function getWSFile($wsname, $version = null) {
		$folder = $this->getVersionFolder($version);
		$filename = $this->webservices[$wsname];
		if (is_array($filename) && !empty($filename['file'])) $filename = $filename['file'];
		if ($filename)
			return "modules/Touch/$folder/$filename";
		else 
			return null;
	}

	/**
	 * Get the class name for the specified webservice
	 */
	public function getWSClassName($wsname, $version = null) {
		$classname = str_replace('.', '', $wsname);
		if (!empty($version) && version_compare($version, '2.0', '>=')) {
			$wsfile = $this->webservices[$wsname];
			if (is_array($wsfile) && !empty($wsfile['class'])) {
				$classname =  $wsfile['class'];
			} else {
				$classname = 'Touch'.$classname;
			}
		}
		return $classname;
	}

	/**
	 * Get the filename containing the class for the specified webservice
	 */
	public function getWSClassFile($wsname, $version = null) {
		$folder = $this->getVersionFolder($version);
		if ($folder == 'vtws') {
			return "modules/Touch/$folder/classes/".$this->getWSClassName($wsname, $version).'.class.php';
		} else {
			return $this->getWSFile($wsname, $version);
		}
	}

	/**
	 * Create an instance of the webservice's class
	 */
	public function getWSClassInstance($wsname, $version = null) {
		$classfile = $this->getWSClassFile($wsname, $version);
		$classname = $this->getWSClassName($wsname, $version);

		if (!class_exists($classname)) require($classfile);
		$wsclass = new $classname($version);
		return $wsclass;
	}
	
	// start the session so the session-cache can be used
	/**
	 * Starts a session specific for the Touch webservices only.
	 * You can use this session to store some data as a cache.
	 */
	public function startWSSession() {
		VteSession::$sessionName = 'TOUCHSESSID';

		// set the sessionid from a custom header or a request parameter as fallback
		if (!empty($_SERVER['HTTP_TOUCH_SESSION_ID'])) {
			$_REQUEST[VteSession::$sessionName] = $_SERVER['HTTP_TOUCH_SESSION_ID'];
		} elseif (!empty($_REQUEST['touch_session_id'])) {
			$_REQUEST[VteSession::$sessionName] = $_REQUEST['touch_session_id'];
		}

		VteSession::start();
		header('Touch-Session-Id: '.VteSession::getId());

		// useful variable to detect the App-mode
		VteSession::set("app_unique_key", 'WSMobile_'.time());
	}

	public function resetWSSession() {
		VteSession::reset();
	}

	// crmv@91082
	public function destroyWSSession() {
		VteSession::destroy();
	}
	// crmv@91082e

	// crmv@93148
	/**
	 * Suspend the current session, so the session file doesn't lock concurrent requests
	 */
	public function closeWSSession() {
		VteSession::close(true);
	}
	
	/**
	 * Reopen the current session, in order to write in it
	 */
	public function reopenWSSession() {
		VteSession::reopen(true);
		return true;
	}
	// crmv@93148e

	/**
	 * For Post-Login webservices, validate the provided credentials
	 */
	protected function executeWSLogin(&$request) {
		global $current_user, $default_language, $current_language;

		$result = $this->checkLogin($request['username'], $request['password']);
		$userId = $result['userid'];

		// utente
		if ($result['success'] && $userId > 0) {
			$current_user = CRMEntity::getInstance('Users');
			$current_user->id = $userId;
			$current_user->retrieveCurrentUserInfoFromFile($userId);
			
			// check active state
			if ($current_user->column_fields['status'] != 'Active') return false;

			// lingua
			if (!empty($current_user->column_fields['default_language'])) {
				$default_language = $current_language = $current_user->column_fields['default_language'];
			}
			return true;
		}
		return false;
	}

	/**
	 * Executes a Touch Webservice
	 */
	public function executeWS($wsname, $wsversion, &$request) {

		$filename = $this->getWSClassFile($wsname, $wsversion);
		$classname = $this->getWSClassName($wsname, $wsversion);

		if (!is_readable($filename) || empty($filename)) {
			return $this->outputFailure('Webservice file is missing');
		}

		// include only if necessary
		if (!class_exists($classname)) require($filename);

		if (!class_exists($classname)) {
			return $this->outputFailure('Webservice class not found');
		} else {

			$wsclass = new $classname($wsversion);
			
			// check magic cache params
			if ($request['touch_no_cache'] == '1') {
				global $touchCache;
				$touchCache->disable();
			}

			if (!$wsclass->preLogin && !$this->executeWSLogin($request)) {
				return $this->outputFailure('Invalid credentials');
			} else {
			
				//auditing
				// crmv@202301
				require_once('modules/Settings/AuditTrail.php');
				$AuditTrail = new AuditTrail();
				$AuditTrail->processTouchWS($request);
				// crmv@202301e

				// Check if Touch module isactive, I can do it only after the login
				if (!$wsclass->preLogin && !$this->isTouchPermitted()) {
					return $this->outputFailure('Touch module is not active');
				}
				
				if ($wsclass->longOperation) {
					set_time_limit($this->longOperationTime);
				}
				
				// crmv@106521
				// clean temp ids in case of logout, login or every 2 hours
				global $current_user; // crmv@204438
				if ($wsname == 'Login' || $wsname == 'Logout' || VteSession::isEmpty('touch_clean_tempids_ts') || VteSession::get('touch_clean_tempids_ts') < time()-7200) {
					$this->cleanTempIds($current_user->id);
					VteSession::set('touch_clean_tempids_ts', time());
				}

				//crmv@95788
				$hasReplayCheck = ($this->replay_check && !empty($request['touch_request_id']));
				$return_data = null;
				if ($hasReplayCheck && !$this->checkReplay($request, $return_data)) {
					$replayInfo = array('already_processed' => true);
					if (is_array($return_data)) {
						$replayInfo['data'] = $return_data;
					}
					return $this->outputFailure('This request has already been processed', $replayInfo);
				}
				
				// crmv@204438
				if (VteSession::isEmpty('authenticated_user_id')) {
					VteSession::set('authenticated_user_id', $current_user->id);
				}
				// crmv@204438e
				
				$result = null;
				$r = $wsclass->execute($request, $result);
				
				if ($hasReplayCheck) {
					$this->closeReplay($request, $result);
				}
				
				return $r;
				//crmv@95788e crmv@106521e
			}

		}
	}

	/**
	 * Calls a webservice and returns its results
	 */
	public function callWS($wsname, $wsversion, &$request) {
		global $userId;

		$filename = $this->getWSClassFile($wsname, $wsversion);
		$classname = $this->getWSClassName($wsname, $wsversion);

		if (!is_readable($filename) || empty($filename)) {
			return $this->createOutput(null, 'Webservice file is missing', false);
		}

		// include only if necessary
		if (!class_exists($classname)) require($filename);

		if (!class_exists($classname)) {
			return $this->createOutput(null, 'Webservice class not found', false);
		} else {

			$wsclass = new $classname($wsversion);
			
			// Check if Touch module isactive, I can do it only after the login
			if (!$wsclass->preLogin && !$this->isTouchPermitted()) {
				return $this->createOutput(null, 'Touch module is not active', false);
			}
			
			if ($wsclass->longOperation) {
				set_time_limit($this->longOperationTime);
			}

			return $wsclass->call($request);
		}
	}

	public function checkLogin($username, $accesskey) {
		$login = false;
		$userInst = CRMEntity::getInstance('Users');

		$userId = $userInst->retrieve_user_id($username);
		$accessKey = vtws_getUserAccessKey($userId);
		if (strcmp($accessKey, $accesskey) === 0) {
			$login = true;
		}
		return array('success'=> $login, 'userid' => $userId);
	}

	protected function detectSuccess($data) {
		$ret = true;
		if (is_array($data) && ($data['success'] === false || !empty($data['error']))) $ret = false;
		return $ret;
	}

	public function createOutput($data = array(), $message = '', $success = null) {
		if (!is_array($data)) $data = array($data);
		if (is_null($success)) $success = $this->detectSuccess($data);
		if (!$success) {
			$data['success'] = false;
			$data['error'] = $message;
		} else {
			$data['success'] = true;
			$data['error'] = '';
		}
		return $data;
	}

	public function outputFailure($message, $extra = array()) {
		$payload = $this->createOutput($extra, $message, false);
		$this->outputRaw($payload);
		return false;
	}

	public function outputSuccess($data = array()) {
		$payload = $this->createOutput($data, '', true);
		$this->outputRaw($payload);
		return true;
	}

	public function outputRaw($data) {
		// add the session parameter
		$sessid = VteSession::getId();
		if (is_array($data) && array_key_exists('success', $data) && !empty($sessid)) {
			$data['touch_session_id'] = $sessid;
		}
		// output result
		header('Content-type: application/json');
		echo Zend_Json::encode($data);
		return $this->detectSuccess($data);
	}

	//crmv@95788
	/**
	 * Check if the request has already been processed (it's a replay)
	 */
	protected function checkReplay(&$request, &$return_data = null) { // crmv@106521
		global $adb, $table_prefix, $current_user;
		
		$skipCheckWs = array('GetWebservices', 'Login', 'Logout', 'MultiCall');
		
		$requestId = $request['touch_request_id'];
		$deviceId = $this->getCurrentDeviceId();
		$wsname = $request['wsname'];
		
		// no deviceid, the replay is not checked
		if (empty($requestId) || empty($deviceId)) return true;
		
		// clean the requests if login, logout or every hour
		if ($wsname == 'Login' || $wsname == 'Logout' || VteSession::isEmpty('touch_clean_replay_ts') || VteSession::get('touch_clean_replay_ts') < time()-3600) {
			$this->cleanReplayTable($current_user->id);
			VteSession::set('touch_clean_replay_ts', time());
		}
		
		if (in_array($wsname, $skipCheckWs)) return true;
		
		// check for an existing requests
		$res = $adb->pquery(
			"SELECT * FROM {$this->table_requests} WHERE userid = ? AND deviceid = ? AND requestid = ? AND status IN (?,?)", // crmv@189084
			array($current_user->id, $deviceId, $requestId, 'COMPLETED', 'PROCESSING') // crmv@189084
		);
		if ($res && $adb->num_rows($res) > 0) {
			// already processed
			$return_data = Zend_Json::decode($adb->query_result_no_html($res, 0, 'return_data')); // crmv@106521
			return false;
		} else {
			// new request, insert it!
			$values = array(
				'userid' => $current_user->id,
				'deviceid' => $deviceId,
				'requestid' => $requestId,
				'status' => 'PROCESSING',
				'request_date' => date('Y-m-d H:i:s'),
				'wsname' => $wsname,
			);
			$adb->pquery("INSERT INTO {$this->table_requests} (".implode(',', array_keys($values)).") VALUES (".generateQuestionMarks($values).")", $values);
		}
		
		return true;
	}
	
	// crmv@106521
	protected function closeReplay(&$request, $result) {
		global $adb, $table_prefix, $current_user;
		
		$requestId = $request['touch_request_id'];
		$deviceId = $this->getCurrentDeviceId();

		if (empty($requestId) || empty($deviceId)) return;
		
		$returnData = $this->extractReplayReturnData($request, $result);
		
		$params = array(date('Y-m-d H:i:s'), 'COMPLETED', Zend_Json::encode($returnData), $current_user->id, $deviceId, $requestId);
		$adb->pquery("UPDATE {$this->table_requests} SET completion_date = ?, status = ?, return_data = ? WHERE userid = ? AND deviceid = ? AND requestid = ?", $params);
		
	}
	
	protected function extractReplayReturnData($request, $result) {
		$wsname = $request['wsname'];
		$return = array();
		switch ($wsname) {
			case 'WriteComment':
				$return = $result['records'][0];
				break;
		}
		return $return;
	}
	// crmv@106521e
	
	public function cleanReplayTable($userid = null) {
		global $adb, $table_prefix, $current_user;
		
		$pruneHours = 6;	// delete stuff older than 6 hours
		
		$sql = "DELETE FROM {$this->table_requests} WHERE request_date < ?";
		$params = array(date('Y-m-d H:i:s', time()-$pruneHours*3600));
		
		if ($userid > 0) {
			$sql .= " AND userid = ?";
			$params[] = $userid;
		}

		$res = $adb->pquery($sql, $params);
	}
	//crmv@95788e
	
	// crmv@161368
	/**
	 * Logout the user from the app at the next access
	 */
	public function remoteWipe($userid) {
		global $adb;
		
		$now = date('Y-m-d H:i:s');
		$adb->pquery("DELETE FROM {$this->table_wipedata} WHERE userid = ?", array($userid));
		$adb->pquery("INSERT INTO {$this->table_wipedata} (userid, wipe_date) VALUES (?,?)", array($userid, $now));
	}
	
	/**
	 * Get the wipe date for the specified user
	 */
	public function getWipeDate($userid) {
		global $adb;
		
		$res = $adb->pquery("SELECT wipe_date FROM {$this->table_wipedata} WHERE userid = ?", array($userid));
		if ($res && $adb->num_rows($res) > 0) {
			return $adb->query_result_no_html($res, 0, 'wipe_date');
		}
		return false;
	}
	// crmv@161368e

	/**
	 * Convert field values from VTE style to App style
	 * If $onlydisplay == true, the field is formatted for display only
	 */
	public function field2Touch($module, $fieldname, $fieldvalue, $onlydisplay = false, &$focus = null, $fieldinfo = array()) {
		global $log, $adb, $table_prefix, $current_user, $site_URL, $touchUtils;

		if (empty($fieldinfo)) {
			$fields = $touchUtils->getModuleFields($module, $current_user->id);
			if (!is_array($fields)) return $fieldvalue;
			$fieldinfo = $fields[$fieldname];
		}
		
		if (is_array($fieldinfo)) {
			$type = $fieldinfo['type']['name'];
			switch ($type) {
				case 'reference':
					$fieldvalue = intval($fieldvalue);

					if ($fieldvalue > 0) {
						// trovo il modulo collegato (dato che possono essere multipli)
						// crmv@136394
						$refersTo = $fieldinfo['type']['refersTo'][0];
						if ($refersTo == 'Users') {
							$displayname = getOwnerName($fieldvalue);
							$fieldvalue = array('crmid'=>$fieldvalue, 'display'=>$displayname, 'setype'=>$refersTo);
						} else {
							$setype = getSalesEntityType($fieldvalue);
							if (!empty($setype) && in_array($setype, $fieldinfo['type']['refersTo'])) {
								$displayname = $touchUtils->getEntityNameFromFields($setype, $fieldvalue);
								$fieldvalue = array('crmid'=>$fieldvalue, 'display'=>$displayname, 'setype'=>$setype);
							// crmv@147766
							} elseif ($refersTo == 'DocumentFolders') {
								$folderinfo = getEntityFolder($fieldvalue);
								if ($folderinfo) {
									$displayname = getTranslatedString($folderinfo['foldername'], 'Documents');
								}
								$fieldvalue = strval($fieldvalue);
							}
							// crmv@147766e
						}
						// crmv@136394e
					}
					if ($onlydisplay) {
						$fieldvalue = (empty($displayname) ? '' : $displayname);
					}
					break;
				// crmv@167740
				case 'time':
					if ($fieldinfo['uitype'] == 73) {
						require_once('modules/SDK/src/73/73Utils.php');
						$uitypeTimeUtils = UitypeTimeUtils::getInstance();
						$fieldvalue = $uitypeTimeUtils->seconds2Time($fieldvalue);
					}
					break;
				// crmv@167740e
				case 'date':
					// reverse date format
					if (preg_match('/\d{2}-\d{2}\-\d{4}/', $fieldvalue)) {
						$ndate = date_parse_from_format('d-m-Y', $fieldvalue);
						$fieldvalue = $ndate['year'].'-'.$ndate['month'].'-'.$ndate['day'];
					}
					break;
				case 'picklist':
					if ($onlydisplay)
						$fieldvalue = getTranslatedString($fieldvalue, $module);
					break;
				case 'picklistmultilanguage':
					if ($onlydisplay) {
						$plid = $fieldvalue;
						$fieldvalue = Picklistmulti::getTranslatedPicklist($plid, $fieldname);
					}
					break;
				case 'multipicklist':
					if ($onlydisplay) {
						$values = explode(' |##| ', $fieldvalue);
						if (count($values) > 0) {
							foreach ($values as $k=>$v) $values[$k] = getTranslatedString($v, $module);
						}
						$fieldvalue = implode(', ', $values);
					} else {
						$fieldvalue = str_replace(' |##| ', ',', $fieldvalue);
					}
					break;
				case 'boolean':
					if ($onlydisplay) {
						$fieldvalue = getTranslatedString(($fieldvalue ? 'LBL_YES' : 'LBL_NO'), 'APP_STRINGS');
					}
					break;
				case 'owner':
					if ($onlydisplay) {
						$fieldvalue = $touchUtils->getOwnerName($fieldvalue); // crmv@148861
					}
					break;
				case 'file':
					// fix internal link
					if (!empty($fieldvalue) && $focus && in_array($module, array('Documents', 'Myfiles')) && in_array($focus->column_fields['filelocationtype'], array('I', 'B')) && $focus->column_fields['filestatus'] == 1) {					
						// get direct link
						$res = $adb->pquery("select * from {$table_prefix}_seattachmentsrel inner join {$table_prefix}_attachments on {$table_prefix}_attachments.attachmentsid = {$table_prefix}_seattachmentsrel.attachmentsid where {$table_prefix}_seattachmentsrel.crmid = ?", array($focus->column_fields['record_id']));
						if ($res && $adb->num_rows($res) > 0) {
							$attid = $adb->query_result_no_html($res, 0, 'attachmentsid');
							$name = $adb->query_result_no_html($res, 0, 'name');
							$filepath = $adb->query_result($res, 0, 'path');
							$saved_filename = $attid."_".$name;
							$fieldvalue = $site_URL."/".$filepath.$saved_filename;
							//$fieldvalue = $site_URL."/index.php?module=uploads&action=downloadfile&fileid={$attid}&entityid={$focus->column_fields['record_id']}";
						}
					}
					break;
			}

			// crmv@67656 crmv@187823 - calendar, masked fields
			if ($module == 'Events' && $focus && !empty($focus->column_fields['visibility'])) {
				if (empty($focus->column_fields['assigned_user_id']) && !empty($focus->column_fields['smownerid'])) {
					$focus->column_fields['assigned_user_id'] = $focus->column_fields['smownerid'];
				}
				if ($focus->isFieldMasked($focus->column_fields['record_id'], $fieldname, $focus->column_fields)) {
					if ($fieldname == 'subject') {
						$fieldvalue = getTranslatedString('Private Event', 'Calendar');
					} else {
						$fieldvalue = '';
					}
				}
			}
			// crmv@67656e crmv@187823e

			// campi documenti
			if (in_array($module, array('Documents', 'Myfiles')) && $fieldname == 'filesize' && !empty($fieldvalue)) {
				if ($fieldvalue < 1024)
					$fieldvalue = $fieldvalue.' B';
				elseif ($fieldvalue > 1024 && $fieldvalue < 1048576)
					$fieldvalue = round($fieldvalue/1024,2).' KB';
				else
					$fieldvalue = round($fieldvalue/(1024*1024),2).' MB';
			}
			// campi cifrati
			if ($fieldinfo['uitype'] == 208) {
				$fieldvalue = "-- ".getTranslatedString('LBL_CIPHERED', 'APP_STRINGS')." --";
			// crmv@99131
			} elseif ($fieldinfo['uitype'] == 206) {
				$res = $adb->pquery("select reportname from {$table_prefix}_report where reportid = ?", array($fieldvalue));
				if ($res) {
					$fieldvalue = $adb->query_result_no_html($res, 0, 'reportname');
				}
			}
			// crmv@99131e
			
			// crmv@187823 - organizer
			if ($fieldinfo['uitype'] == 49) {
				require_once('modules/SDK/src/49/OrganizerField.php');
				$ofield = OrganizerField::getInstance($module, $fieldname);
				$val = $ofield->getValue($focus->column_fields['record_id']);
				$fieldvalue = $ofield->getDisplayValue($val);
				$fieldvalue = strip_tags($fieldvalue);
			}
			// crmv@187823e
			
			// descrizione email, use the cleaned_body, avoiding the expensive call to magicHTML when possible
			if ($module == 'Messages' && $fieldname == 'description') {
				if ($focus) {
					if (empty($focus->column_fields['cleaned_body'])) {
						$attachments_info = $focus->getAttachmentsInfo();
						$message_data = array('other'=>$attachments_info);
						$magicHTML = $focus->magicHTML($fieldvalue, $focus->column_fields['xuid'], $message_data);
						$description = $magicHTML['html'];
						$content_ids = $magicHTML['content_ids'];
						// save them 
						$focus->saveCleanedBody($focus->id, $description, $content_ids);
						$focus->column_fields['cleaned_body'] = $description;
					}
					$fieldvalue = $focus->column_fields['cleaned_body'];
				}
				// fix for double encoding
				$fieldvalue = str_replace(array('&amp;gt;', '&amp;lt;'), array('&gt;', '&lt;'), $fieldvalue);

			}
		}

		return $fieldvalue;
	}

	/**
	 * Convert field values from App style to VTE style
	 */
	public function touch2Field($module, $fieldname, $fieldvalue, $fieldinfo = array()) {
		global $current_user, $touchUtils;

		if (empty($fieldinfo)) {
			$fields = $touchUtils->getModuleFields($module, $current_user->id);
			if (!is_array($fields)) return $fieldvalue;
			$fieldinfo = $fields[$fieldname];
		}
		
		if (is_array($fieldinfo)) {
			$type = $fieldinfo['type']['name'];
			switch ($type) {
				case 'owner':
					$fieldvalue = intval($fieldvalue);
					if ($fieldvalue <= 0) $fieldvalue = 1; // assegno ad admin in caso di dato non valido
					// crmv@34947
					try {
						$otype = vtws_getOwnerType($fieldvalue);
					} catch (Exception $e) {
						$otype = 'Users';
					}
					$fieldvalue = vtws_getWebserviceEntityId($otype, $fieldvalue);
					// crmv@34947e
					break;
				// crmv@167740
				case 'time':
					if ($fieldinfo['uitype'] == 73) {
						require_once('modules/SDK/src/73/73Utils.php');
						$uitypeTimeUtils = UitypeTimeUtils::getInstance();
						$fieldvalue = $uitypeTimeUtils->time2Seconds($fieldvalue);
					}
					break;
				// crmv@167740e
				// crmv@121586
				case 'date':
					$time = strtotime($fieldvalue);
					if ($time !== false) {
						$fieldvalue = date('Y-m-d', $time);
					} else {
						// old method
						$fieldvalue = substr(str_replace('T', ' ', $fieldvalue), 0, 10);
					}
					break;
				case 'datetime':
				case 'timestamp':
					$time = strtotime($fieldvalue);
					if ($time !== false) {
						$fieldvalue = date('Y-m-d H:i:s', $time);
					} else {
						// old method
						$fieldvalue = str_replace('T', ' ', $fieldvalue);
					}
					break;
				// crmv@121586e
				// gli id arrivano in formato ID, non webservice
				case 'reference': {
					$fieldvalue = intval($fieldvalue);

					// crmv@159113
					if ($fieldvalue != 0) {
						if ($fieldvalue < 0) {
							// temporary id, find the correct crmid
							$fieldvalue = $this->getRealId($current_user->id, null, $fieldvalue);
						}
						// trovo il modulo collegato (dato che possono essere multipli)
						if ($fieldinfo['type']['refersTo'][0] == 'DocumentFolders') {
							$setype = 'DocumentFolders';
						// crmv@153778
						} elseif (in_array($fieldinfo['uitype'], array(50,51,52))) {
							$setype = 'Users';
						// crmv@153778e
						} elseif ($fieldvalue > 0) {
							// crmv@186151
							if ($fieldinfo['uitype'] == 117) {
								$setype = 'Currency';
							} else {
								$setype = getSalesEntityType($fieldvalue);
								if (empty($setype)) $setype = $fieldinfo['type']['refersTo'][0]; // for Currency and maybe others
							}
							// crmv@186151e
						}
						if (!empty($setype) && in_array($setype, $fieldinfo['type']['refersTo'])) {
							$fieldvalue = vtws_getWebserviceEntityId($setype, $fieldvalue);
						}
					} else {
						$fieldvalue = '';
					}
					// crmv@159113e
					break;
				}
				case 'multipicklist':
					// TODO: e se i valori contengono la virgola?
					$fieldvalue = str_replace(',', ' |##| ', $fieldvalue);
					break;
				case 'boolean':
					$fieldvalue = ($fieldvalue ? 'on' : 'off');
					break;
				default:
					$fieldvalue = str_replace('&amp;', '&', $fieldvalue);
					break;
			}
		}

		return $fieldvalue;
	}

	/**
	 * The standard vtlib handler
	 */
	function vtlib_handler($moduleName, $event_type) {
		global $adb, $table_prefix;

		if($event_type == 'module.postinstall') {
			$adb->pquery("UPDATE {$table_prefix}_tab SET customized = 0 WHERE name=?", array($moduleName));
			$this->initDefaultProperties();
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			$this->initDefaultProperties();
		}
	}
}
