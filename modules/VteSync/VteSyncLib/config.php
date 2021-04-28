<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


// general config

$sync = array(
	//'modules' => array(),						// deprecated
	'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
	'simulate' => false,						// if true, nothing will be written on any system!
	'only_cli' => true,							// when invoked directly, can be executed only from CLI
	'local_timezone' => 'Europe/Rome',			// timezone of the server where VteSync is run
	'user_mapping' => 'VTE',					// which connector will create the user mapping
	//'firstsync_interval_past' => 'P30D', 		// at the first sync, get records between (now - this interval) and ...
	//'firstsync_interval_future' => 'P150D',	// ... (now + this interval), only for Events and Tasks!
	'conflicts' => 'Last',						// Last to use the last one who modified something, other values not supported
	'use_pidfile' => false,						// if true, allow only 1 running instance at time, using a pid file for locking
	'pid_timeout' => 3600*4,					// number of seconds after which the pid file is ignored if exists

	// local database
	'storage' => array(
		'type' => 'vte',						// can be "vte" or "database". VTE uses the VTE db connection
		'vte' => array(							// config as to where to find VTE
			'path' => realpath(dirname(__FILE__).'/../../../'),
		),
		'database' => array(					// database configuration in case type was "DB"
			'db_type' => 'mysqli',
			'db_host' => 'localhost:3306',
			'db_username' => 'root',
			'db_password' => '',
			'db_name' => 'vtesync',
			'debug' => false,
		),
	),

	'connectors' => array(
	
		'VTE' => array(
			'enabled' => true,
			'loglevel' => 4,						// 1 = fatal, 5 = debug, default 4
			'auth_type' => 'local', 				// local or webservice
			// config only for local auth
			'vte_path' => realpath(dirname(__FILE__).'/../../../'),
			// config only for webservice auth
			'vte_url' => 'http://localhost/dev/',
			'vte_admin_user' => 'admin',
			'vte_admin_accesskey' => 'XXX',

			//'vte_match_field', = 'sync_google_cal',	// extra field to check for valid users
			//'vte_match_re', = '/^1$/',					// RE used to check the previous field
			
			// modules to sync and sync properties:
			// Direction:
			//     In = records are written to this system
			//     Out = records are read from this system
			//     Both = records are read and written to and from this system
			//   If no direction is specified, the default is Both
			// Delete:
			//     true/false: default true, if false records won't be deleted in this connector
			// Picklist:
			//     true/false: default false, if true, add missing picklist values
			// 
			'modules' => array(
				'Users' => array('direction' => 'In'),
				'Leads' => array('direction' => 'Both', 'picklist' => true),
				'Accounts' => array('direction' => 'Both', 'delete' => false),
				'Contacts' => array('direction' => 'Both'),
			),
			
			'update_owners' => false,		// true to update owners of existing records
			
			// default values to use when fields are not set (in creation, also if they are empty)
			'defaults' => array(
				'Users' => array(
					'create' => array(
						'is_admin' => 'off',
						'user_password' => '123456789',
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
					),
					'update' => array(
					)
				),
				'Leads' => array(
					'create' => array(
						'lastname' => '???',
						'company' => '???',
					)
				)
			),
		),
		
		'SalesForce' => array(
			'enabled' => true,
			'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
			'syncid' => false,							// when called withn the VteSync module, pass the syncid
			'modules' => array(
				'Users' => array('direction' => 'Out'),
				'Leads' => array('direction' => 'Both', 'picklist' => true),
				'Accounts' => array('direction' => 'Both'),
				'Contacts' => array('direction' => 'Both'),
			),
			'auth_type' => 'oauth2',					// oauth2 is the only supported mechanism
			
			'update_owners' => false,
		),
		
		// crmv@190016
		'Jira' => array(
			'enabled' => true,
			'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
			'syncid' => false,							// when called withn the VteSync module, pass the syncid
			'instance_url' => 'https://my-instance.atlassian.net/',
			'modules' => array(
				'Users' => array('direction' => 'Out'),
				//'Leads' => array('direction' => 'Both', 'picklist' => true),
				//'Accounts' => array('direction' => 'Both'),
				//'Contacts' => array('direction' => 'Both'),
			),
			'auth_type' => 'http',						// http is the only supported mechanism
			
			'update_owners' => false,
		),
		// crmv@190016e
		
		// crmv@195073
		'HubSpot' => array(
			'enabled' => true,
			'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
			'syncid' => false,							// when called withn the VteSync module, pass the syncid
			'modules' => array(
				'Users' => array('direction' => 'In'),
				'Accounts' => array('direction' => 'Both'),
				'Contacts' => array('direction' => 'Both', 'picklist' => true),
				'Potentials' => array('direction' => 'Both', 'picklist' => true),
				'Targets' => array('direction' => 'Both'),
				'HelpDesk' => array('direction' => 'Both'),
			),
			'auth_type' => 'oauth2',					// oauth2 is the only supported mechanism
			
			'update_owners' => false,
		),
		// crmv@195073e
		
		//crmv@196666
		'SuiteCRM' => array(
			'enabled' => true,
			'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
			'syncid' => false,							// when called withn the VteSync module, pass the syncid
			'modules' => array(
				'Users' => array('direction' => 'Out'),
				'Leads' => array('direction' => 'Both', 'picklist' => true),
				'Accounts' => array('direction' => 'Both'),
				'Contacts' => array('direction' => 'Both'),
			),
			'auth_type' => 'oauth2',					// oauth2 is the only supported mechanism
			
			'update_owners' => false,
		),
		//crmv@196666e
		
		// crmv@197423
		'Vtiger' => array(
			'enabled' => true,
			'loglevel' => 4,							// 1 = fatal, 5 = debug, default 4
			'syncid' => false,							// when called withn the VteSync module, pass the syncid
			'instance_url' => 'https://your_instance.odx.vtiger.com',
			'modules' => array(
				'Users' => array('direction' => 'Out'),
				//'Leads' => array('direction' => 'Both', 'picklist' => true),
				//'Accounts' => array('direction' => 'Both'),
				//'Contacts' => array('direction' => 'Both'),
			),
			'auth_type' => 'http',						// http is the only supported mechanism
			
			'update_owners' => false,
		),
		// crmv@197423e
		
	),
		
);