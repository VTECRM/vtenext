<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@94084 crmv@115378 crmv@145781 crmv@170248 */

require_once('include/BaseClasses.php');
require_once('include/utils/Cache/CacheStorage.php');

/**
 * This class handles a basic key-value store with various configuration options for VTE.
 * For better performances, it uses a duble cache (file and request)
 */
class VTEProperties extends VTEUniqueClass {
	
	public $table_name_prop = '';
	
	public $use_cache = true;
	public $cache_mode = 'session'; // file | session
	public $cache_file = 'cache/sys/vteprops.json'; // for file mode
	public $cache_format = 'json'; // for file mode
	public $cache_key = 'vteprops'; // for session mode
	
	protected $rcache;
	protected $fcache;
	protected $scache;
	
	protected $rcache_initialized = false;
	protected $fcache_initialized = false;
	protected $scache_initialized = false;
	
	// overrides valid only during request
	protected $req_overrides = array();
	
	// values to save during install
	protected $default_values = array(
	
		'smtp_editable' => 1,
		
		/* old performance.config values */
		
		// Enable log4php debugging only if requried 
		'performance.log4php_debug' => 0,
		
		// Should the caller information be captured in SQL Logging?
		// It adds little overhead for performance but will be useful to debug
		'performance.sql_log_include_caller' => 0,
		
		// crmv@47905 write timing into table tbl_s_logtime
		// log sql timing 
		'performance.sql_log_timing' => 0,
		
		// log app timing
		'performance.app_log_timing' => 0,
		
		// include backtrace while timing
		'performance.backtrace_log_timing' => 0,
		// crmv@47905e
		
		// If database default charset is UTF-8, set this to true 
		// This avoids executing the SET NAMES SQL for each query!
		'performance.db_default_charset_utf8' => 1,
		
		// Compute record change indication for each record shown on listview
		'performance.listview_record_change_indicator' => 0,
		
		// Turn-off default sorting in ListView, could eat up time as data grows
		'performance.listview_default_sorting' => 1,
		
		// Control DetailView Record Navigation
		'performance.detailview_record_navigation' => 0,
		
		// To control the Email Notifications being sent to the Owner
		// By default it is set to true, if it is set to false, then notifications will not be sent
		'performance.notify_owner_emails' => 1,
		
		// reduce number of ajax requests on home page, reduce this value if home page widget dont show value
		'performance.home_page_widget_group_size' => 12,
		
		// take backup legacy style, whenever an admin user logs out.
		'performance.logout_backup' => 1,
		
		// true to use the standard pagination view in the listview, false to skip the count query and show only the next/prev page
		'performance.list_count' => 1,	// crmv@137476
		
		// Show the record count in the related lists
		'performance.related_list_count' => 1, //crmv@25809
		
		// Limit above which the massedits will be done in background
		'performance.listview_mass_check_with_workflow' => 100, //crmv@27096
		
		// Check the notifications every this number of seconds
		'performance.notification_interval_time' => 240000, //crmv@35676
		
		// crmv@47905bis
		// Enable global VTE Cache
		'performance.cache' => 1,
		
		// Type of cache ('session' or 'file')
		'performance.cache_type' => 'session',
		// crmv@47905bise
		
		// crmv@181165
		'performance.global_cache' => 'best', // possible values: 0, 'best', 'apcu', 'memcached'
		'performance.global_cache_keys' => array('vte_languages', 'tabdata', 'tablecache'), // vte_languages, sdk, or the specific name of the cache crmv@193294
		'performance.global_cache_config' => '', // not used yet
		// crmv@181165e
		
		'performance.add_relation_in_full_page' => 1, //crmv@54245
		
		// True if the crmentity table is partitioned by setype
		'performance.crmentity_partitioned' => 0, //crmv@64325
		
		// If true, when popup is opened, the first module is automatically selected
		'performance.popup_autoselect_module' => 0, //crmv@65506
		
		// set to false to disable almost all temporary tables, good for db replication
		// if you change this from true to false, remember to recalculate the privileges
		'performance.use_temp_tables' => 0, // crmv@63349
		
		// set to true to enable logging of Javascript errors. For the log to be written, 
		// you also have to activate the LOG4PHP_DEBUG
		'performance.js_debug' => 0, // crmv@92034
		
		// crmv@96019
		// set to 'enable' to enable imap actions when pressing update button
		// set to 'disable' to reload messages list and waiting for cron update
		// set to 'fast_sync' to enable imap actions with interval INTERVAL_IMAP_FAST_SYNC
		'performance.messages_update_icon_perform_imap_actions' => 'enable',
		'performance.interval_imap_fast_sync' => '1 days',
		// crmv@96019e
		
		//crmv@94125 // crmv@140887
		// if true, resources (js, css, images) will be versioned
		'performance.version_resources' => 1,
		// how to create the versioned file ('link': symbolic link or 'copy')
		'performance.version_resources_method' => 'link',
		// if true, versioned files will be checked automatically for changes
		// note: may degrade performances due to many stat calls
		'performance.version_resources_autorefresh' => 0,
		// the remote server used to load js, css, images (without final "/")
		// you also have to change the remote server in themes/THEME_NAME/scss/vars/_mixins.scss
		'performance.version_resources_cdn' => '',
		//crmv@94125e // crmv@140887e
		
		// set to 0 to disable ajax editing from detail view
		'performance.detailview_ajax_edit' => 1,

		// set to 1 to enable show_query and show_stats, you need also to activate them with request parameters
		'performance.show_query_stats' => 0,
		
		//crmv@118551
		'layout.default_detail_view' => '',	// (empty string) / summary
		'layout.enable_switch_detail_view' => 0,
		'layout.old_style' => 0,
		'layout.tb_relations_order' => 'num_of_records', // num_of_records / layout_editor
		'layout.enable_always_mandatory_css' => 0,	// use css class dvtCellInfoM also in edit view and other views
		//crmv@118551e

		'layout.template_editor' => 'grapesjs',	//crmv@197575 - alternative: ckeditor
		'layout.record_title_inline' => 0, // crmv@199229
		'layout.hide_update_info' => 1, // crmv@199229
		
		'layout.singlepane_view' => 1, // crmv@204438
		
		'settings.process_manager.show_logs_button' => 0,	//crmv@121416
		
		// crmv@152713
		'theme.cycle_login_background' => true, // Enable login background change
		'theme.current_login_background_color' => '#4C92DA', // Default login background color
		'theme.current_login_background_image' => '', // Default login background image
		'theme.login_background_image_strategy' => 'sequential', // Default strategy (sequential, random)
		'theme.primary_colors' => array(), // Default primary colors of the installed themes. They are automatically calculated by scss files. // crmv@202705
		// crmv@152713e
		
		'outlook_sdk' => 1,	// enable advanced features and SDK for outlook plugin
		
		// crmv@181231
		'session.handler' => '',				// type of the session handler ('' = default, 'apc', 'apcu', 'memcached', 'redis', 'db')
		'session.handler.params' => array(),	// parameters for the handler, see in include/utils/SessionHandlers/*
		// crmv@181231e
		
		//crmv@171832
		'performance.editview_changelog' => 1,
		'performance.editview_changelog_clean_interval' => 86400,
		'performance.editview_changelog_force_writable_uitypes' => '[220,5,6,23]', // crmv@180825
		//crmv@171832e
		
		//crmv@173186
		'performance.log_globalconfig' => array(
			'type' => array(
				'label' => 'LBL_TYPE',
				'value' => 'file', // file / db
				'ui' => 'picklist', // TODO add other ui (ex. string, checkbox, etc)
				'ui_prop' => array(
					'picklist_values' => array('file'=>'File','db'=>'Database'),
					'picklist_width' => '150px'
				),
				'db' => array(
					'external' => false, // if false use vte db, if true use the following configuration
					'server' => '',
					'port' => '',
					'username' => '',
					'password' => '',
					'name' => '',
					'type' => '',
					'charset' => '',
				)
			)
		),
		'performance.log_config' => array(
			'processes' => array(
				'label' => 'LBL_PROCESSES_LOG',
				'file' => 'logs/ProcessEngine/ProcessEngine.log',
				'rotate_maxsize' => 5, // MB
				'table' => '_log_processengine',
				'level' => 4, // default log level, from 1 = fatal to 5 = debug, default = 4
				'enabled' => 1,
			),
			'webservices' => array(
				'label' => 'LBL_WEBSERVICE_LOG',
				'file' => 'logs/webservices/webservices.log',
				'rotate_maxsize' => 5,
				'table' => '_log_webservices',
				'level' => 4,
				'enabled' => 0,
			),
			'restapi' => array(
				'label' => 'LBL_RESTAPI_LOG',
				'file' => 'logs/restapi/restapi.log',
				'rotate_maxsize' => 5,
				'table' => '_log_restapi',
				'level' => 4,
				'enabled' => 0,
			),
			'mailscanner' => array(
				'label' => 'LBL_MAIL_SCANNER',
				'file' => 'logs/MailScanner/MailScanner.log',
				'rotate_maxsize' => 5,
				'table' => '_log_mailscanner',
				'level' => 4,
				'enabled' => 0,
			),
			// crmv@172616
			'workflow' => array(
				'label' => 'Workflow',
				'file' => 'logs/Workflow/Workflow.log',
				'rotate_maxsize' => 5,
				'table' => '_log_workflow',
				'level' => 4,
				'enabled' => 0,
			),
			// crmv@172616e
		),
		//crmv@173186e
		
		//crmv@179773
		'performance.modcomments_parent_perm' => false,
		'performance.modcomments_parent_perm_users' => array('all'=>true,'users'=>array(),'groups'=>array(),'roles'=>array()),
		//crmv@179773e
		
		'performance.modules_without_crmentity' => array('Messages','Processes'), //crmv@185647
		
		//crmv@185894: view the TT-185894 for all the activation instructions
		'performance.slave_handler' => false,
		'performance.slave_functions' => array('Area','UnifiedSearch','ModNotificationsCount','ListViewCount','BadgeCount','TurboliftCount','Export','Reports'),
		'performance.slave_connection' => array(
			'db_server' => '',
			'db_port' => ':3306',
			'db_username' => '',
			'db_password' => '',
			'db_name' => '',
			'db_name_cache' => 'slave_cache',
			'db_type' => 'mysqli',
			'db_status' => 'true',
			'db_charset' => 'utf8',
			'db_dieOnError' => false,
		),
		//crmv@185894e
		
		//crmv@186476 crmv@189362
		'calendar_tracking.enabled' => true,
		'calendar_tracking.detailview_modules' => array('Accounts', 'Contacts', 'HelpDesk', 'ProjectTask'),
		'calendar_tracking.turbolift_modules' => array('Messages', 'Emails'),
		'calendar_tracking.status_fields' => array(),
		'calendar_tracking.status_values' => array(),
		//crmv@186476e crmv@189362e
		
		//crmv@186709
		'modules.messages.list_max_entries_first_page' => 50,
		'modules.messages.list_max_entries_per_page' => 10,
		'modules.messages.messages_by_schedule' => 20,
		'modules.messages.messages_by_schedule_inbox' => 20,
		'modules.messages.interval_schedulation' => '15 days',
		'modules.messages.max_message_cron_uid_attempts' => 3,
		'modules.messages.interval_storage' => '',
		'modules.messages.messages_cleaned_by_schedule' => 500,
		'modules.messages.preserve_search_results_date' => '-1 day', // crmv@200243
		'modules.messages.fetchBodyInCron' => 'yes',
		'modules.messages.IMAPDebug' => false,
		'modules.messages.view_related_messages_recipients' => false,
		'modules.messages.view_related_messages_drafts' => false,
		'modules.messages.interval_inline_cache' => '1 month',
		'modules.messages.force_index_querygenerator' => false,
		//crmv@186709e
		// crmv@206145
		'modules.messages.oauth2.credentials' => [
			'Microsoft' => [
				'clientId' => '',
				'clientSecret' => '',
				'redirectUri' => '',
			]
		],
		// crmv@206145e
		//crmv@186709
		'modules.emails.max_attachment_size' => 25,
		'modules.emails.max_message_size' => 10240000,
		'modules.emails.max_emails_send_queue_attempts' => 5,
		//crmv@186709e
		'modules.emails.send_mail_queue' => false, // crmv@129149
		'modules.emails.save_unknown_contacts' => true, // crmv@191584
		'modules.emails.auto_append_servers' => array('gmail','office365'), // crmv@202172
		
		// crmv@200009
		'modules.import.immediate_import_limit' => 200,
		'modules.import.import_batch_Limit' => 250,
		
		'modules.pdfmaker.enable_rtf' => false, // crmv@195354
		
		'loadrelations.limit' => 100,
		// crmv@200009e

		'masscreate.limit' => 50, // crmv@202577
		
		// crmv@171524 crmv@196871
		'performance.mq_rabbitmq_enabled' => false,
		'performance.mq_rabbitmq_connection' => array(
			'host' => '',					// host - host name where the RabbitMQ server is runing. ex. ex. 192.168.1.160
			'port' => 5672,					// port - port number of the service, 5672 is the default
			'user' => 'nexi',				// user - username to connect to server
			'password' => '',				// password. use $encryption->encrypt()
			'queue_name' => '',				// queue name. ex. processhandler
			'freeze_topic_name' => '',		// topic for freeze records. ex. freeze
			'defreeze_topic_name' => '',	// topic for defreeze records. ex. defreeze
			'max_trigger_attempts' => 50,
			'keep_history' => false,		// crmv@199641 move row from table _trigger_queue to _trigger_queue_history when all is ok
		),
		'performance.mq_webstomp_enabled' => false,
		'performance.mq_webstomp_connection' => array(
			'host' => '',					// websocket host. ex. http://192.168.1.160:15674/stomp
			'user' => '',					// websocket user. ex. stomp
			'password' => '',				// websocket password. use $encryption->encrypt()
			'virtual_host' => '/',			// websocket virtual host
		),
		// crmv@171524e crmv@196871e
		
		'performance.cf_prefix_length' => 3, // crmv@195213
		
		'performance.recalc_privileges_limit' => 50, // crmv@199834 Limit of users beyond which the share recalculation will be performed in the background
        
		'security.csrf.enabled' => true,	// crmv@171581
		
        'security.smtp.validate_certs' => true, // crmv@180739
        // 'security.imap.validate_certs' => true,	// not implemented yet, but reserve the name
        
        // crmv@202301
        'security.audit.enabled' => true,			// enable audit trail
        'security.audit.log_retention_time' => 3, 	// how long to keep audit records, in months, 0 to disable
        // crmv@202301e

	);
	
	/**
	 * These values are needed before the connection to the DB,
	 * so they need a special handling in case the filecache is not available.
	 * If you need such a variable, you have to add it here and in the array above
	 */
	protected $nodb_values = array(
		'performance.log4php_debug',
		'performance.sql_log_timing',
		'performance.db_default_charset_utf8',
		'performance.cache_query_result'
	);
	
	public function __construct() {
		global $table_prefix;
		
		$this->table_name_prop = $table_prefix.'_vteprop';
		
		// initializes the caches
		if ($this->use_cache) {
			$this->rcache = new CacheStorageVar();
			if ($this->cache_mode == 'file') {
				$this->fcache = new CacheStorageFile($this->cache_file, $this->cache_format);
				$this->fcache_initialized = !$this->fcache->isFileEmpty();
			} elseif ($this->cache_mode == 'session') {
				$this->scache = new CacheStorageSession();
				$scache = $this->scache->get($this->cache_key);
				$this->scache_initialized = (!empty($scache));			
				if (!$this->scache_initialized) {
					$this->initSCache();
				}
			}
		}
	}
	
	protected function isDBConnected() {
		global $adb;
		return ($adb && $adb->database && $adb->database->IsConnected());
	}
	
	protected function isInstallRunning() {
		return (basename($_SERVER['PHP_SELF']) == 'install.php');
	}
	
	public function initSCache() {
		if ($this->use_cache && $this->cache_mode == 'session' && !$this->scache_initialized) {
			$this->rebuildCache();
		}
	}
	
	/**
	 * Initialize default values (used during install)
	 */
	public function initDefaultProperties($checkExisting = true) { // crmv@148789
		// set properties if not already set
		foreach ($this->default_values as $prop=>$value) {
			$oldVal = $checkExisting ? $this->getProperty($prop, true) : null; // crmv@148789
			if ($oldVal === null) $this->setProperty($prop, $value);
		}
	}
	
	/**
	 * Rename a property, taking care to update all the involved caches
	 */
	public function renameProperty($oldKey, $newKey) {
		global $adb;
		
		// database
		$r = $adb->pquery("UPDATE {$this->table_name_prop} SET property = ? WHERE property = ?", array($newKey, $oldKey));
		
		// req overrides
		if (isset($this->req_overrides[$oldKey])) {
			$this->req_overrides[$newKey] = $this->req_overrides[$oldKey];
			unset($this->req_overrides[$oldKey]);
		}
		
		if ($this->use_cache) {
			if ($this->rcache_initialized) {
				$oldval = $this->rcache->get($oldKey);
				if ($oldval !== null) {
					$this->rcache->set($newKey, $oldval);
					$this->rcache->clear($oldKey);
				}
			}
			if ($this->scache_initialized) {
				$oldval = $this->scache->get($oldKey);
				if ($oldval !== null) {
					$this->scache->setArray(array($this->cache_key, $newKey), $oldval);
					$this->scache->clearArray(array($this->cache_key, $oldKey));
				}
			}
			if ($this->cache_mode == 'session') {
				$cache = Cache::getInstance($this->cache_key);
				$cache->clear();
			}
			if ($this->fcache_initialized) {
				$oldval = $this->fcache->get($oldKey);
				if ($oldval !== null) {
					$this->fcache->set($newKey, $oldval);
					$this->fcache->clear($oldKey);
				}
			}
		}
	}
	
	/**
	 * Alias for getProperty
	 */
	public function get($property, $noCache = false, $noOverride = false) {
		return $this->getProperty($property, $noCache, $noOverride);
	}
	
	/**
	 * Get all properties
	 */
	public function getAll($noCache = false, $noOverride = false) {
		// if skipping the override, skip the cache also (the cache stores only the overridden values)
		if ($noOverride) $noCache = true;

		if ($noCache || !$this->use_cache) {
			$values = $this->getAllPropertiesFromDB($noOverride);
		} elseif ($this->rcache_initialized) {
			$values = $this->rcache->getAll();
		} elseif ($this->scache_initialized) {
			$values = $this->scache->get($this->cache_key);
			if ($this->use_cache) $this->rebuildRCache(); // rebuild the request cache also
		} elseif ($this->fcache_initialized) {
			$values = $this->fcache->getAll();
			if ($this->use_cache) $this->rebuildRCache(); // rebuild the request cache also
		} else {
			$values = $this->getAllPropertiesFromDB($noOverride);
		}
		
		return $values;		
	}
	
	/**
	 * Return a stored value
	 */
	public function getProperty($property, $noCache = false, $noOverride = false) {

		// if skipping the override, skip the cache also (the cache stores only the overridden values)
		if ($noOverride) $noCache = true;
		
		if ($noCache || !$this->use_cache) {
			$value = $this->getPropertyFromDB($property, $noOverride);
		} elseif ($this->rcache_initialized) {
			$value = $this->rcache->get($property);
		} elseif ($this->scache_initialized) {
			$value = $this->scache->getArray(array($this->cache_key, $property));
			if ($this->use_cache) $this->rebuildRCache(); // rebuild the request cache also
		} elseif ($this->fcache_initialized) {
			$value = $this->fcache->get($property);
			if ($this->use_cache) $this->rebuildRCache(); // rebuild the request cache also
		} else {
			$value = $this->getPropertyFromDB($property, $noOverride);
		}
		
		return $value;
	}
	
	protected function getPropertyFromDB($property, $noOverride = false) {
		global $adb;
		
		// check if db connection is valid
		if (!$this->isDBConnected() || $this->isInstallRunning() || in_array($property, $this->nodb_values)) {
			return $this->getInstallValue($property);
		}

		$r = $adb->pquery("SELECT value, override_value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r && $adb->num_rows($r) > 0) {
			$value = $adb->query_result_no_html($r, 0, 'value');
			$ovalue = $adb->query_result_no_html($r, 0, 'override_value');
			if (!$noOverride && $ovalue !== '' && !is_null($ovalue)) {
				$value = $ovalue;
			}
			$value = $this->decodeDBValue($value); // crmv@139789
			return $value;
		}
		
		return null;
	}
	
	// crmv@139789
	protected function decodeDBValue($value) {
		if (is_string($value) && strlen($value) && ($value[0] === '[' || $value[0] === '{')) {
			$decoded = json_decode($value, true);
			if ($decoded !== null) {
				$value = $decoded;
			}
		}
		return $value;
	}
	
	protected function encodeDBValue($value) {
		if ($value === false) {
			$value = '0';
		} elseif ($value === true) {
			$value = '1';
		} elseif (is_array($value)) {
			$value = json_encode($value);
		}
		return $value;
	}
	// crmv@139789e
	
	protected function getAllPropertiesFromDB($noOverride = false) {
		global $adb;
		
		// check if db connection is valid
		if (!$this->isDBConnected() || $this->isInstallRunning()) {
			return $this->getAllInstallValues();
		}
		
		$values = array();
		$r = $adb->query("SELECT property, value, override_value FROM {$this->table_name_prop}");
		if ($r) {
			while ($row = $adb->fetchByAssoc($r, -1, false)) {
				$value = ($noOverride ? $row['value'] : ($row['override_value'] ?: $row['value']));
				$value = $this->decodeDBValue($value); // crmv@139789
				$values[$row['property']] = $value;
			}
		}
		return $values;
	}
	
	protected function getInstallValue($property) {
		if (isset($this->default_values[$property])) {
			return $this->default_values[$property];
		}
		return null;
	}
	
	protected function getAllInstallValues() {
		return $this->default_values;
	}
	
	/**
	 * Alias for setProperty
	 */
	public function set($property, $value) {
		return $this->setProperty($property, $value);
	}
	
	/**
	 * Set or update a property
	 */
	public function setProperty($property, $value) {
		global $adb;
		
		$r = $adb->pquery("SELECT property FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r) {
			$dbValue = $this->encodeDBValue($value); // crmv@139789
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_prop} SET value = ? WHERE property = ?", array($dbValue, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_prop} (property, value) VALUES (?,?)", array($property, $dbValue));
			}
			if ($this->use_cache) {
				$this->rcache->set($property, $value);
				if ($this->scache_initialized) {
					$this->scache->setArray(array($this->cache_key,$property), $value);
				}
				if ($this->cache_mode == 'session') {
					$cache = Cache::getInstance($this->cache_key);
					$cache->clear();
				}
				// crmv@164465
				if ($this->fcache_initialized) {
					$this->fcache->set($property, $value);
				}
				// crmv@164465e
			}
		}
	}
	
	/**
	 * Retrieve the overriden value, or null if not overridden
	 */
	public function getOverride($property) {
		global $adb;
		
		// if it was a request override, we have it here
		if (isset($this->req_overrides[$property])) {
			return $this->req_overrides[$property];
		}

		// otherwise check the db (in the file cache i don't know if it was the original value or not)
		$r = $adb->pquery("SELECT override_value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r && $adb->num_rows($r) > 0) {
			$ovalue = $adb->query_result_no_html($r, 0, 'override_value');
			if ($ovalue !== '' && !is_null($ovalue)) {
				$ovalue = $this->decodeDBValue($ovalue); // crmv@139789
				return $ovalue;
			}
		}
		
		return null;
	}
	
	/**
	 * Set an override for a property. The persistence is either "request" or "db"
	 */
	public function setOverride($property, $value, $persistence = 'db') {
		global $adb;
		
		if ($persistence == 'request') {
			$this->req_overrides[$property] = $value;
			if ($this->use_cache) {
				$this->rcache->set($property, $value);
			}
			return;
		}
		
		$r = $adb->pquery("SELECT value FROM {$this->table_name_prop} WHERE property = ?", array($property));
		if ($r) {
			$dbValue = $this->encodeDBValue($value); // crmv@139789
			if ($adb->num_rows($r) > 0) {
				// update
				$r = $adb->pquery("UPDATE {$this->table_name_prop} SET override_value = ? WHERE property = ?", array($dbValue, $property));
			} else {
				// insert
				$r = $adb->pquery("INSERT INTO {$this->table_name_prop} (property, value, override_value) VALUES (?,?,?)", array($property, $dbValue, $dbValue));
			}
			if ($this->use_cache) {
				$this->rcache->set($property, $value);
				if ($this->scache_initialized) {
					$this->scache->setArray(array($this->cache_key,$property), $value);
				}
				if ($this->cache_mode == 'session') {
					$cache = Cache::getInstance($this->cache_key);
					$cache->clear();
				}
				// crmv@164465
				if ($this->fcache_initialized) {
					$this->fcache->set($property, $value);
				}
				// crmv@164465e
			}
		}
	}
	
	/**
	 * Remove the override
	 */
	public function unsetOverride($property, $persistence = 'db') {
		global $adb;
		
		// get original value
		$value = $this->getPropertyFromDB($property, true);

		// remove it from req
		unset($this->req_overrides[$property]);
		
		if ($persistence == 'request') {
			if ($this->use_cache) {
				$this->rcache->set($property, $value);
			}
			return;
		}
		
		// unset it from the db
		$r = $adb->pquery("UPDATE {$this->table_name_prop} SET override_value = NULL WHERE property = ?", array($property));
		
		// restore caches
		if ($this->use_cache) {
			$this->rcache->set($property, $value);
			if ($this->scache_initialized) {
				$this->scache->setArray(array($this->cache_key,$property), $value);
			}
			if ($this->cache_mode == 'session') {
				$cache = Cache::getInstance($this->cache_key);
				$cache->clear();
			}
			// crmv@164465
			if ($this->fcache_initialized) {
				$this->fcache->set($property, $value);
			}
			// crmv@164465e
		}
	}
	
	/**
	 * Remove all overrides
	 */
	public function unsetAllOverrides($persistence = 'db') {
		global $adb;
		
		// remove it from req
		$this->req_overrides = array();
		
		if ($persistence == 'request') {
			if ($this->use_cache) {
				$this->clearRCache();
			}
			return;
		}
		
		// unset it from the db
		$r = $adb->query("UPDATE {$this->table_name_prop} SET override_value = NULL");
		
		// clear caches
		if ($this->use_cache) {
			$this->clearCache();
		}
	}
	
	/**
	 * Remove a property
	 */
	public function deleteProperty($property) {
		global $adb;
		$r = $adb->pquery("DELETE FROM {$this->table_name_prop} WHERE property = ?", array($property));
		unset($this->req_overrides[$property]);
		if ($this->use_cache) {
			$this->rcache->clear($property);
			if ($this->scache_initialized) {
				$this->scache->clearArray(array($this->cache_key,$property));
			}
			if ($this->cache_mode == 'session') {
				$cache = Cache::getInstance($this->cache_key);
				$cache->clear();
			}
			// crmv@164465
			if ($this->fcache_initialized) {
				$this->fcache->clear($property);
			}
			// crmv@164465e
		}
	}
	
	public function clearCache() {
		$this->clearRCache();
		$this->clearSCache();
		$this->clearFCache();
	}
	
	protected function clearRCache() {
		if ($this->rcache_initialized) {
			$this->rcache->clearAll();
			$this->rcache_initialized = false;
		}
	}
	
	protected function clearSCache() {
		if ($this->scache_initialized) {
			$this->scache->clear($this->cache_key);
			$this->scache_initialized = false;
		}
	}
	
	protected function clearFCache() {
		if ($this->fcache_initialized) {
			$this->fcache->clearAll();
			$this->fcache_initialized = false;
		}
	}
	
	public function rebuildCache() {
		if ($this->cache_mode == 'file') {
			$this->rebuildFCache();
		} elseif ($this->cache_mode == 'session') {
			$this->rebuildSCache();
		}
		$this->rebuildRCache();
	}
	
	protected function rebuildRCache() {
	
		// not using any cache if the DB is not ready!
		if (!$this->isDBConnected()) return;
		
		$this->rcache->clearAll();
		$this->rcache_initialized = false;

		// get the values from the session or the file cache
		if ($this->scache_initialized) {
			$cache = $this->scache->get($this->cache_key);
		} elseif ($this->fcache_initialized) {
			$cache = $this->fcache->getAll();
		} else {
			$cache = $this->getAllPropertiesFromDB();
		}
		// apply request overrides
		if (is_array($this->req_overrides)) {
			$cache = array_merge($cache, $this->req_overrides);
		}
		$this->rcache->setMulti($cache);
		$this->rcache_initialized = true;

	}
	
	protected function rebuildSCache($cache = null) {

		// not using any cache if the DB is not ready!
		if (!$this->isDBConnected()) return;

		$this->scache->clear($this->cache_key);
		$this->scache_initialized = false;
		
		if (!$cache) {
			$cache = $this->getAllPropertiesFromDB();
		}

		$this->scache->set($this->cache_key,$cache);
		$this->scache_initialized = true;

	}
	
	protected function rebuildFCache($cache = null) {

		// not using any cache if the DB is not ready!
		if (!$this->isDBConnected()) return;
		
		$this->fcache->clearAll();
		$this->fcache_initialized = false;
		
		if (!$cache) {
			$cache = $this->getAllPropertiesFromDB();
		}
		
		$this->fcache->setMulti($cache);
		$this->fcache_initialized = true;

	}
	
	// crmv@164465
	/**
	 * Check if cache file is valid
	 */
	public function checkCacheValidity() {
		if (!$this->use_cache) return true;
		if ($this->cache_mode != 'file') return true; // check only for file

		// check if exists and not empty
		if (!file_exists($this->cache_file) || filesize($this->cache_file) == 0) return false;

		// invalid file
		if (!$this->fcache || !$this->fcache_initialized) return false;
		
		// check if enough values
		$cacheData = $this->fcache->getAll();
		$savedData = $this->getAllPropertiesFromDB();
		if (count($cacheData) != count($savedData)) {
			return false;
		}

		return true;
	}
	// crmv@164465e

}