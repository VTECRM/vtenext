<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@48159 (diff rev 860-863) */
/* crmv@49395 (diff rev 889-893) */
/* crmv@51862 (diff rev 909-910) */

require_once('modules/Messages/MessagesRelationManager.php');
require_once('modules/Messages/src/Squirrelmail.php');
require_once('modules/Messages/Utils.php');	//crmv@49432
require_once('modules/Calendar/iCal/includeAll.php'); // crmv@68357

class Messages extends MessagesRelationManager {

	protected $folder;			// Selected folder
	private $saved_messages;
	protected $account;
	var $folderSeparator = '/';
	var $rootFolder = '/';	//crmv@125287
	var $defaultSpecialFolders = array(
		'INBOX'=>'',
		'Drafts'=>'',
		'Sent'=>'',
		'Spam'=>'',
		//'Junk'=>'',
		'Trash'=>'',
	);
	var $fakeFolders = array('Shared','Flagged','Links','vteScheduled'); // crmv@187622
	// crmv@192843
	var $folderImgs = array(
		'INBOX'=>'inbox',
		'Drafts'=>'note',
		'Sent'=>'send',
		'Spam'=>'whatshot',
		'Trash'=>'delete',
		'Shared'=>'folder_shared',
		'Flagged'=>'flag',
		'Links'=>'arrow_downward',
		'vteScheduled'=>'schedule',
	);
	// crmv@192843e
	var $resetMailResource = false;
	var $other_contenttypes_attachment = array(
		'application/pgp-signature',
		'application/octet-stream',
		'application/pdf',
		//crmv@53651
		'message/delivery-status',
		'text/rfc822-headers',
		//crmv@53651e
		'text/calendar',
		'message/rfc822', //crmv@62340
	);
	var $ical_methods = array('REQUEST', 'REPLY'); // crmv@68357 : attachments with this method and contenttype text/calendar are invitations or replies
	var $list_max_entries_first_page;	//crmv@46154
	var $list_max_entries_per_page;		// navigation, fetch and append

	// IMAP Cron configuration
	var $messages_by_schedule;			// max number of messages processed by cron Messages.service.php (0 = no limit)
	var $messages_by_schedule_inbox;	// max number of messages processed by cron Inbox.service.php (0 = no limit)
	var $interval_schedulation;			// '' = all (no temporary interval)
	var $max_message_cron_uid_attempts;	//crmv@55450
	var $update_duplicates = false;		// crmv@57585 : if true in saveCache if I try to save a message already downloaded I update its informations. if false skip saving.
	var $fetch_array_chunk_limit = 500;	//crmv@174681
	
	//crmv@OPER8279
	// store in db only messages in this interval (ex. 90 days, 4 months)
	// if it is empty it store all messagaes in db
	// if (interval_storage < interval_schedulation) interval_storage = interval_schedulation
	// this value must be in a range of search_intervals but not in the least
	// Lotus do not support SEARCH SINCE so we suggest to store all in order to do not use the imap search
	var $interval_storage;
	var $messages_cleaned_by_schedule;	// number of messages to delete in a schedulation of cleanStorage
	var $preserve_search_results_date;	// store for 1 day search results before delete them
	//crmv@OPER8279e
	
	var $fetchBodyInCron;				// crmv@59094 : (string) yes | no | no_disposition_notification_to
	var $relatedEditButton = false;		//crmv@61173
	//crmv@62414
	var $view_image_supported_extensions = array('png','bmp','gif','jpeg','jpg','tiff','tif');	//crmv@91321 
	var $viewerJS_supported_extensions = array('pdf','odt','ods','ots','ott','otp'); 
	var $action_view_JSfunction_array = array(
		'eml'=>'ViewEML',
		'pdf'=>'ViewDocument',
		'odt'=>'ViewDocument',
		'ods'=>'ViewDocument',
		'ots'=>'ViewDocument',
		'ott'=>'ViewDocument',
		'otp'=>'ViewDocument',
		'png'=>'ViewImage',
		'bmp'=>'ViewImage',
		'gif'=>'ViewImage',
		'jpeg'=>'ViewImage',
		'jpg'=>'ViewImage',
		//crmv@91321
		'tiff'=>'ViewImage',
		'tif'=>'ViewImage',
		//crmv@91321e
	);
	//crmv@62414e
	//crmv@76756
	var $IMAPDebug;
	var $IMAPLogMaxSize = 5242880;	// 5MB per logfile (more or less)
	var $IMAPLogDir = 'logs/imap/';
	//crmv@76756e
	var $inline_image_supported_extensions = array('jpeg','jpg','gif','png','apng','svg','bmp','ico','tiff','tif');	//crmv@80250 crmv@91321
	var $inline_image_convertible_extensions = array('tiff','tif');	//crmv@91321
	var $view_related_messages_recipients;	//crmv@86301
	var $view_related_messages_drafts;		//crmv@141429 to view messages with column draft = 0/1

	//crmv@87055
	var $search_intervals = array(
		array('','-2 months'),
		array('-2 months','-6 months'),
		array('-6 months','-1 year'),
		array('-1 year','-2 years'),
		array('-2 years',''),
	);
	//crmv@87055e
	
	//crmv@125629
	var $interval_inline_cache; // to keep in cache inline attachments for this time. If empty, the cache will never be emptied.
	var $force_check_imap_connection = false;
	/* 
	 * true: when you open module Messages if the conection to server is down or the credentials are wrong you are not permit to use the module
	 * false: even if the conection to server is down or the credentials are wrong you can use the module
	 */
	//crmv@125629e

	var $force_index_querygenerator; //crmv@146435
	
	protected $max_login_attempts = 5; //crmv@171904
	
	function __construct() {
		parent::__construct();
		//crmv@186709
		$VTEP = VTEProperties::getInstance();
		$this->list_max_entries_first_page = $VTEP->getProperty('modules.messages.list_max_entries_first_page');
		$this->list_max_entries_per_page = $VTEP->getProperty('modules.messages.list_max_entries_per_page');
		$this->messages_by_schedule = $VTEP->getProperty('modules.messages.messages_by_schedule');
		$this->messages_by_schedule_inbox = $VTEP->getProperty('modules.messages.messages_by_schedule_inbox');
		$this->interval_schedulation = $VTEP->getProperty('modules.messages.interval_schedulation');
		$this->max_message_cron_uid_attempts = $VTEP->getProperty('modules.messages.max_message_cron_uid_attempts');
		$this->interval_storage = $VTEP->getProperty('modules.messages.interval_storage');
		$this->messages_cleaned_by_schedule = $VTEP->getProperty('modules.messages.messages_cleaned_by_schedule');
		$this->preserve_search_results_date = $VTEP->getProperty('modules.messages.preserve_search_results_date');
		$this->fetchBodyInCron = $VTEP->getProperty('modules.messages.fetchBodyInCron');
		$this->IMAPDebug = $VTEP->getProperty('modules.messages.IMAPDebug');
		$this->view_related_messages_recipients = $VTEP->getProperty('modules.messages.view_related_messages_recipients');
		$this->view_related_messages_drafts = $VTEP->getProperty('modules.messages.view_related_messages_drafts');
		$this->interval_inline_cache = $VTEP->getProperty('modules.messages.interval_inline_cache');
		$this->force_index_querygenerator= $VTEP->getProperty('modules.messages.force_index_querygenerator');
		//crmv@186709e
	}

	/**
	 * @deprecated
	 */
	function loadZendFramework() {
		// crmv@196384
		// do nothing, everything is already autoloaded
	}
	
	function getZendMailStorageImap($userid='') {
		global $current_user, $current_folder;
		if (empty(self::$mail)) {
			$this->loadZendFramework();
			try {
				$this->getZendMailProtocolImap($userid);
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				// if error in cron skip
				if (isset($_REQUEST['app_key']) && $_REQUEST['service'] == 'Messages') {
					throw new Exception('ERR_IMAP_CRON');
				}
				//crmv@125629
				if ($_REQUEST['file'] == 'Settings/index' && $_REQUEST['operation'] == 'SaveAccount') {
					throw new Exception('ERR_IMAP_AUTENTICATION');
				}
				//crmv@125629e
				// show Shared folder even if no mail server configured
				if ($_REQUEST['action'] == 'DetailView' && !empty($_REQUEST['record']) && in_array($current_folder,$this->fakeFolders)) {
					return false;
				}
				$this->manageConnectionError($e,$userid);
			}
			$mail = new Zend\Mail\Storage\Imap(self::$protocol);
			self::$mail = $mail;
			return true;
		}
	}
	
	//crmv@133893
	function getZendMailStorageImapGeneric($server,$port,$ssl_tls,$username,$password) {
		if (empty(self::$mail)) {
			$this->loadZendFramework();
			try {
				$this->getZendMailProtocolImap(null,array(
						'server'=>$server,
						'port'=>$port,
						'ssl_tls'=>$ssl_tls,
						'username'=>$username,
						'password'=>$password
				));
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);
				return false;
			}
			$mail = new Zend\Mail\Storage\Imap(self::$protocol);
			self::$mail = $mail;
			return true;
		}
	}
	//crmv@133893e
	
	//crmv@133893
	function getZendMailProtocolImap($userid='',$params=array()) {
		if (empty(self::$protocol)) {
			if (empty($userid) && !empty($params)) {
				$server = $params['server'];
				$port = $params['port'];
				$ssl_tls = $params['ssl_tls'];
				$username = $params['username'];
				$password = $params['password'];
				$authentication = 'password'; // crmv@206145
			} else {
				if (!empty($userid)) {
					$user = CRMEntity::getInstance('Users');
					$user->retrieve_entity_info($userid,'Users');
				} else {
					global $current_user;
					$user = $current_user;
				}
				$accountid = $this->getAccount();
				$account = $this->getUserAccounts($user->id,$accountid);
				$account = $account[0];
				$server = $account['server'];
				$port = (!empty($account['port']) ? $account['port'] : null);
				$ssl_tls = (!empty($account['ssl_tls']) ? $account['ssl_tls'] : false);
				if (empty($server)) {
					throw new Exception('ERR_IMAP_SERVER_EMPTY');
				}
				$username = $account['username'];
				// crmv@206145
				$authentication = $account['authentication'];
				if ($authentication == 'password') {
					$password = $account['password'];
				}
				// crmv@206145e
			}
			try {
				$protocol = new Zend\Mail\Protocol\Imap($server,$port,$ssl_tls);
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				throw new Exception('ERR_IMAP_CONNECTION_FAILED');
			}
			$protocol->crmMessage = $this;	//crmv@76756
			// crmv@206145
			if ($authentication == 'password') {
				$login = $protocol->login($username,$password);
			} elseif ($authentication == 'oauth2') {
				$accessToken = $this->checkOauthToken($user->id,$accountid);
				$login = $protocol->loginOauth($username,$accessToken);
			}
			// crmv@206145e
			//crmv@125629 crmv@171904
			global $adb, $table_prefix;
			if ($login === false) {
				if (empty($username) || ($authentication == 'password' && empty($password))) { // crmv@206145
					$error = 'ERR_IMAP_CREDENTIALS_EMPTY';
				} else {
					$error = 'ERR_IMAP_LOGIN_FAILED';
				}
				$result = $adb->pquery("select attempts from {$table_prefix}_messages_account where id = ?", array($accountid));
				$attempts = $adb->query_result($result,0,'attempts');
				if ($attempts < $this->max_login_attempts) {
					$adb->pquery("update {$table_prefix}_messages_account set error = ?, attempts = ? where id = ?", array($error,($attempts+1),$accountid));
				}
				throw new Exception($error);
			} else {
				$adb->pquery("update {$table_prefix}_messages_account set error = ?, attempts = ? where id = ?", array('',0,$accountid));
			}
			//crmv@125629e crmv@171904e
			self::$protocol = $protocol;
		}
		return self::$protocol; // crmv@38592
	}
	//crmv@133893e
	
	// crmv@206145
	function checkOauthToken($userid, $accountid, $forceTokenRefresh=false) {
		global $adb, $table_prefix;
		
		$account = $this->getUserAccounts($userid,$accountid);
		$account = $account[0];
		$accessToken = $account['token'];
		$refreshToken = $account['refresh_token'];
		$expires = $account['expires'];
		
		$accessTokenObj = new League\OAuth2\Client\Token\AccessToken([
			'access_token' => $accessToken,
			'refresh_token' => $refreshToken,
			'expires' => $expires
		]);
		if ($accessTokenObj->hasExpired() || $forceTokenRefresh) {
			$providerName = $this->getOuathProviderName($account);
			$provider = $this->getOuathProvider($providerName);
			if ($provider !== false) {
				$newAccessToken = $provider->getAccessToken('refresh_token', [
					'refresh_token' => $accessTokenObj->getRefreshToken()
				]);
				$accessToken = $newAccessToken->getToken();
				// update token and expires
				$adb->pquery("update {$table_prefix}_messages_account set token = ?, expires = ? where id = ?", [$accessToken, $newAccessToken->getExpires(), $accountid]);
			}
		}
		
		return $accessToken;
	}
	function getOuathProviderName($accountinfo) {
		if ($accountinfo['account'] == 'Gmail' || strpos($accountinfo['server'],'gmail') !== false) {
			$providerName = 'Google';
		} elseif ($accountinfo['account'] == 'Yahoo!' || strpos($accountinfo['server'],'yahoo') !== false) {
			$providerName = 'Yahoo';
		} elseif ($accountinfo['account'] == 'Office365' || strpos($accountinfo['server'],'office365') !== false) {
			$providerName = 'Microsoft';
		}
		return $providerName;
	}
	function getOuathProvider($providerName) {
		$VTEP = VTEProperties::getInstance();
		$credentials = $VTEP->getProperty('modules.messages.oauth2.credentials');
		$clientId = $credentials[$providerName]['clientId'];
		$clientSecret = $credentials[$providerName]['clientSecret'];
		$redirectUri = $credentials[$providerName]['redirectUri'];
		
		switch ($providerName) {
			case 'Microsoft':
				if (!empty($clientId) && !empty($clientSecret)) {
					// direct access
					return new Stevenmaguire\OAuth2\Client\Provider\Microsoft([
						'clientId' => $clientId,
						'clientSecret' => $clientSecret,
						'redirectUri' => $redirectUri,
						'accessType' => 'offline'
					]);
				} else {
					// proxy
					require_once('modules/Messages/OAuth2Proxy/Microsoft.php');
					return new Messages\OAuth2\Proxy\MicrosoftProxy([
						'accessType' => 'offline'
					]);
				}
			default:
				// TODO Google, Yahoo
				return false;
		}
	}
	function getOuathProviderOptions($providerName, $state='', $login_hint='', $force=false) {
		switch ($providerName) {
			case 'Microsoft':
				$options = [
					'scope' => [
						'wl.emails',
						'wl.imap',
						'wl.offline_access'
					],
				];
				break;
			default:
				// TODO Google, Yahoo
				return false;
		}
		if (!empty($state)) $options['state'] = $state;
		if (!empty($login_hint)) $options['login_hint'] = $login_hint;
		if ($force) {
			if ($providerName == 'Microsoft') $options['prompt'] = 'consent';
			elseif ($providerName == 'Google') $options['approval_prompt'] = 'force';
			// TODO Yahoo
		}
		return $options;
	}
	// crmv@206145e

	//crmv@125629
	function manageConnectionError($e,$userid='') {
		if (!empty($userid)) {
			$user = CRMEntity::getInstance('Users');
			$user->retrieve_entity_info($userid,'Users');
		} else {
			global $current_user;
			$user = $current_user;
		}
		echo $this->fetchConnectionError($e->getMessage(),$this->getAccount());
		exit;
	}
	
	function fetchConnectionError($error,$account) {
		global $theme;
		$title = getTranslatedString($error,'Messages');
		if ($title != $error) {
			$descr = getTranslatedString($error.'_DESCR','Messages');
		}
		//if (in_array($error,array('ERR_IMAP_SERVER_EMPTY','ERR_IMAP_CONNECTION_FAILED'))) {}
		//if (in_array($error,array('ERR_IMAP_CREDENTIALS_EMPTY','ERR_IMAP_LOGIN_FAILED'))) {}
		$link = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Accounts';
		$accounts = $this->getUserAccounts();
		if ($error == 'ERR_IMAP_SERVER_EMPTY' && empty($accounts)) {
			$link = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditAccount';
		} elseif ($account !== '') {
			$link = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=EditAccount&id='.$account;
		}
		$descr = sprintf($descr,'<a href="javascript:;" onClick="openPopup(\''.$link.'\',\'\',\'\',\'auto\',720,500);">'.getTranslatedString('LBL_HERE').'</a>');	//crmv@46468 crmv@114260
		$smarty = new VteSmarty();
		$smarty->assign('THEME', $theme);
		$smarty->assign('TITLE', $title);
		$smarty->assign('DESCR', $descr);
		return $smarty->fetch('Error.tpl');
	}
	
	function checkAccountError($userid, $account='') {
		global $adb, $table_prefix;
		$query = "select error, attempts from {$table_prefix}_messages_account where userid = ?"; //crmv@171904
		$params = array($userid);
		if ($account !== '') {
			$query .= " and id = ?";
			$params[] = $account;
		}
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			$error = $adb->query_result($result,0,'error');
			//crmv@171904
			$attempts = $adb->query_result($result,0,'attempts');
			if ($attempts < $this->max_login_attempts) {
				return '';
			}
			//crmv@171904e
			if (!empty($error)) return $this->fetchConnectionError($error,$account);
		}
		return '';
	}
	//crmv@125629e

	function getMailResource() {
		return self::$mail;
	}

	function resetMailResource() {
		if (!empty(self::$mail)) {
			self::$mail->__destruct();
			self::$mail = '';
		}
		if (!empty(self::$protocol)) {
			self::$protocol->__destruct();
			self::$protocol = '';
		}
		$this->__construct();
	}

	function setAccount($id) {
		if ($this->account !== $id) {
			$this->account = $id;
			$this->resetMailResource();
		}
	}

	function getAccount() {
		if (!is_numeric($this->account) && $this->account == '') {
			$this->account = $this->column_fields['account'];
		}
		if (!is_numeric($this->account) && $this->account == '') {
			$this->account = '-1';
		}
		return $this->account;
	}
	
	//crmv@76756
	function logIMAP($mode, $str) {
		if (!$this->IMAPDebug) return false;
		global $root_directory;
		
		$now = date('Y-m-d H:i:s');
		
		$dir = $root_directory.$this->IMAPLogDir;
		if (!is_dir($dir)) {
			mkdir($dir, 0755);
		}
		// find a free name
		$logfile = false;
		for ($i=1; $i<1000; ++$i) {
			$logfile = $dir.str_pad(strval($i), 2, '0', STR_PAD_LEFT).'.log';
			if (!file_exists($logfile) || filesize($logfile) < $this->IMAPLogMaxSize) break;
		}
		
		if (stripos($str,' LOGIN ') !== false) {
			$tmp = explode('"',$str);
			$tmp[3] = 'password';
			$str = implode('"',$tmp);
		}
		
		if ($logfile) {
			@file_put_contents($logfile, "[$now] [ACCOUNT:{$this->getAccount()}] {$mode}: {$str}\n", FILE_APPEND);
			if (!file_exists($logfile)) @chmod($logfile, 0777);
		}
	}
	//crmv@76756e

	//crmv@178164
	function setFolderSeparator($account,$separator) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_messages_account set folder_separator = ? where id = ?", array($separator,$account));
	}
	function getFolderSeparator($account) {
		static $folderSeparators = array();
		if (!isset($folderSeparators[$account])) {
			global $adb, $table_prefix;
			$result = $adb->pquery("select folder_separator from {$table_prefix}_messages_account where id = ?", array($account));
			if ($result && $adb->num_rows($result) > 0) {
				$folderSeparators[$account] = $adb->query_result($result,0,'folder_separator');
			}
		}
		if (empty($folderSeparators[$account])) {
			$folderSeparators[$account] = $this->folderSeparator;
		}
		return $folderSeparators[$account];
	}
	//crmv@178164e
	
	function setSpecialFolders($specialFolders,$accountid) {
		global $current_user, $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_messages_sfolders where userid = ? and accountid = ?",array($current_user->id,$accountid));
		foreach($specialFolders as $special => $folder) {
			$adb->pquery("insert into {$table_prefix}_messages_sfolders (userid, accountid, special, folder) values (?,?,?,?)",array($current_user->id,$accountid,$special,$folder));
		}
	}

	function getSpecialFolders($dieOnError=true) {
		global $adb, $table_prefix;
		$specialFolders = $this->defaultSpecialFolders;
		$accountid = $this->getAccount();
		$result = $adb->pquery("select special, folder from {$table_prefix}_messages_sfolders where accountid = ?",array($accountid));	//crmv@44788
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$specialFolders[$row['special']] = $row['folder'];
			}
		}
		if ($dieOnError) {
			if (!in_array($_REQUEST['file'],array('Settings/index','MessagePopup'))
				&& (empty($specialFolders['INBOX']) || empty($specialFolders['Sent']) || empty($specialFolders['Drafts']) || empty($specialFolders['Trash']))
			) {
				$accounts = $this->getUserAccounts();	// check after set account
				if (!empty($accounts)) {
					// if error in cron skip
					if (isset($_REQUEST['app_key']) && $_REQUEST['service'] == 'Messages') {
						throw new Exception('ERR_IMAP_CRON');
					} else {
						global $theme;
						$link = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders';
						if ($accountid !== '') {
							$link = 'index.php?module=Messages&action=MessagesAjax&file=Settings/index&operation=Folders&account='.$accountid;
						}
						$smarty = new VteSmarty();
						$smarty->assign('THEME', $theme);
						$smarty->assign('TITLE', getTranslatedString('LBL_ERROR_SPECIALFOLDERS_TITLE','Messages'));
						$descr = getTranslatedString('LBL_ERROR_SPECIALFOLDERS_DESCR','Messages');
						$descr .= '<br />- '.getTranslatedString('LBL_Folder_INBOX','Messages');
						$descr .= '<br />- '.getTranslatedString('LBL_Folder_Drafts','Messages');
						$descr .= '<br />- '.getTranslatedString('LBL_Folder_Sent','Messages');
						$descr .= '<br />- '.getTranslatedString('LBL_Folder_Trash','Messages');
						$smarty->assign('DESCR', sprintf($descr,'<a href="javascript:;" onClick="openPopup(\''.$link.'\',\'\',\'\',\'auto\',720,500,\'top\');">'.getTranslatedString('LBL_HERE').'</a>'));	//crmv@46468 crmv@114260
						$smarty->display('Error.tpl');
						exit;
					}
				}
			}
		}
		return $specialFolders;
	}

	function getAllSpecialFolders($special='',$userid='') {
		global $adb, $table_prefix;
		if (empty($userid)) {
			global $current_user;
			$userid = $current_user->id;
		}
		$specialFolders = array();
		$query = "select accountid, special, folder from {$table_prefix}_messages_sfolders where userid = ?";
		$params = array($userid);
		if (!empty($special)) {
			$query .= " and special = ?";
			$params[] = $special;
		}
		$query .= " order by accountid";
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!empty($row['folder'])) $specialFolders[$row['accountid']][$row['special']] = $row['folder']; // crmv@192843
			}
		}
		return $specialFolders;
	}

	/*
	 * Find differences from imap and vte (new messages, changes in flags and messages to delete)
	 * - change of flag is made immediately beacouse is only an update in table _messages
	 * - actions of fetching and deleting of messages are recorded in a queue and it will be processed by another cron
	 * 
	 * Parameter $skip_inbox:
	 * - true : sync all folders except inbox ones
	 * - false : sync all folders
	 */
	function syncUids($skip_inbox=true,$userid=null,$sync_account=null) {
		global $adb, $table_prefix, $current_user;
		$query = "SELECT {$table_prefix}_users.id FROM {$table_prefix}_users WHERE {$table_prefix}_users.status = ?";
		$params = array('Active');
		if (!empty($userid)) {
			$query .= " and {$table_prefix}_users.id = ?";
			$params[] = $userid;
		}
		$result = $adb->pquery($query,$params);
		
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$tmp_current_user_id = $current_user->id;
				if (!$current_user) $current_user = CRMEntity::getInstance('Users'); // crmv@167234
				$current_user->id = $user = $row['id'];
				if ($skip_inbox) $specialFolders = $this->getAllSpecialFolders('INBOX',$user);
				$accounts = $this->getUserAccounts($user);
				foreach($accounts as $account) {
					$accountid = $account['id'];
					if (!empty($sync_account) && $sync_account != $accountid) continue;
					try {
						$this->setAccount($accountid);
						$this->getZendMailStorageImap($user);
						
						// check special folders, do not download message for accounts not totally configured
						$specialFolders = $this->getSpecialFolders(false);
						if (empty($specialFolders['INBOX']) || empty($specialFolders['Sent']) || empty($specialFolders['Drafts']) || empty($specialFolders['Trash'])) continue;
						$special_folders_list = array_keys($specialFolders);	//crmv@56609
						
						//crmv@56609 get folder list
						$tmp2 = array();
						$tmp1 = array();
						$folders = self::$mail->getFolders();
						foreach ($folders as $folder) {
							$foldername = $folder->getGlobalName();
							$in_array = array_search(preg_replace('#'.$this->getFolderSeparator($accountid).'.*#','',$foldername),$special_folders_list); //crmv@178164
							if ($in_array !== false) {
								$tmp1[$in_array.$foldername] = $folder;
							} else {
								$tmp2[$foldername] = $folder;
							}
							$tmp[$foldername] = $folder;
						}
						ksort($tmp1);
						$tmp = array_merge($tmp1, $tmp2);
						//crmv@56609e
						
						$folder_root = new Zend\Mail\Storage\Folder($this->rootFolder,$this->rootFolder,false,$tmp);	//crmv@125287
						$folders_it = new RecursiveIteratorIterator($folder_root,RecursiveIteratorIterator::SELF_FIRST);
						foreach ($folders_it as $folder) {
							if (!$folder->isSelectable()) continue;
							$foldername = $folder->getGlobalName();
							if ($skip_inbox && $foldername == $specialFolders['INBOX']) continue;
							
							//crmv@51946
							try {
								$this->selectFolder($foldername);
							} catch (Exception $e) {	// reset connection
								$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
								$this->resetMailResource();
								$this->getZendMailStorageImap($user);
								//crmv@59095
								try {
									$this->selectFolder($foldername);
								} catch (Exception $e) {
									$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
									continue;
								}
								//crmv@59095e
							}
							//crmv@51946e
							
							$server_ids = array();			// populated in checkFlagsChanges
							$server_ids_dates = array();	// populated in checkFlagsChanges
							$cache_ids = array();			// populated in checkFlagsChanges

							//Update flags of cached mail messages - start
							$flag_changed = $this->checkFlagsChanges($user,$server_ids,$server_ids_dates,$cache_ids);
							//end

							//merge cache_ids (ids saved in vte) with the ids in _messages_cron_uid
							$query = "select uid from {$table_prefix}_messages_cron_uid where action = ? and userid = ? and accountid = ? and folder = ?";
							$params = array('fetch',$user,$accountid,$foldername);
							if (!empty($this->interval_schedulation)) {
								$dateCol = 'date';
								$adb->format_columns($dateCol);
								$query .= " AND $dateCol >= ?";
								$params[] = date('Y-m-d',strtotime("-{$this->interval_schedulation}"));
							}
							$result1 = $adb->pquery($query,$params);
							if ($result1 && $adb->num_rows($result1) > 0) {
								while($row1=$adb->fetchByAssoc($result1)) {
									$cache_ids[] = $row1['uid'];
								}
							}

							//Save new mail messages and delete - start
							$delete_ids = array_diff($cache_ids,$server_ids);
							if (!empty($delete_ids)) {
								$this->populateSyncUidsQueue('noinbox','delete',$user,$accountid,$foldername,$delete_ids,$server_ids_dates);
							}

							$new_ids = array_diff($server_ids,$cache_ids);
							$new_ids = array_reverse($new_ids,true);	//scarico dai piu recenti ai piu vecchi
							if (!empty($new_ids)) {
								$this->populateSyncUidsQueue('noinbox','fetch',$user,$accountid,$foldername,$new_ids,$server_ids_dates);
							}
							//end
						}
					} catch (Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						//echo "ERROR {$e->getMessage()} user:$user account:$accountid\n";
						continue;
					}
				}
				$current_user->id = $tmp_current_user_id;
			}
		}
	}
	
	function syncUidsInbox($userid=null,$account=null) {
		global $adb, $table_prefix, $current_user;
		// crmv@146653
		if (!$current_user) {
			$current_user = CRMEntity::getInstance('Users');
			$current_user->id = 1;
		}
		// crmv@146653e
		$query = "SELECT {$table_prefix}_users.id FROM {$table_prefix}_users WHERE {$table_prefix}_users.status = ?";
		$params = array('Active');
		if (!empty($userid)) {
			$query .= " and {$table_prefix}_users.id = ?";
			$params[] = $userid;
		}
		$result = $adb->pquery($query,$params);
		
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$tmp_current_user_id = $current_user->id;
				$current_user->id = $user = $row['id'];
				$allSpecialFolders = $this->getAllSpecialFolders('INBOX',$user);
				foreach($allSpecialFolders as $accountid => $folders) {
					try {
						if (!empty($account) && $account != $accountid) continue;
						
						$foldername = $folders['INBOX'];
						
						$this->setAccount($accountid);
						$this->getZendMailStorageImap($user);
						$this->selectFolder($foldername);
						
						// check special folders, do not download message for accounts not totally configured
						$specialFolders = $this->getSpecialFolders(false);
						if (empty($specialFolders['INBOX']) || empty($specialFolders['Sent']) || empty($specialFolders['Drafts']) || empty($specialFolders['Trash'])) continue;
						
						$server_ids = array();			// populated in checkFlagsChanges
						$server_ids_dates = array();	// populated in checkFlagsChanges
						$cache_ids = array();			// populated in checkFlagsChanges
	
						//Update flags of cached mail messages - start
						$flag_changed = $this->checkFlagsChanges($user,$server_ids,$server_ids_dates,$cache_ids);
						//end
	
						//merge cache_ids (ids saved in vte) with the ids in _messages_cron_uidi
						$query = "select uid from {$table_prefix}_messages_cron_uidi where action = ? and userid = ? and accountid = ? and folder = ?";
						$params = array('fetch',$user,$accountid,$foldername);
						if (!empty($this->interval_schedulation)) {
							$dateCol = 'date';
							$adb->format_columns($dateCol);
							$query .= " AND $dateCol >= ?";
							$params[] = date('Y-m-d',strtotime("-{$this->interval_schedulation}"));
						}
						$result1 = $adb->pquery($query,$params);
						if ($result1 && $adb->num_rows($result1) > 0) {
							while($row1=$adb->fetchByAssoc($result1)) {
								$cache_ids[] = $row1['uid'];
							}
						}
						
						//Save new mail messages and delete - start
						$delete_ids = array_diff($cache_ids,$server_ids);
						if (!empty($delete_ids)) {
							$this->populateSyncUidsQueue('inbox','delete',$user,$accountid,$foldername,$delete_ids,$server_ids_dates);
						}
	
						$new_ids = array_diff($server_ids,$cache_ids);
						$new_ids = array_reverse($new_ids,true);	//scarico dai piu recenti ai piu vecchi
						if (!empty($new_ids)) {
							$this->populateSyncUidsQueue('inbox','fetch',$user,$accountid,$foldername,$new_ids,$server_ids_dates);
						}
						//end
					} catch (Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						//echo "ERROR {$e->getMessage()} user:$user account:$accountid\n";
						continue;
					}
				}
				$current_user->id = $tmp_current_user_id;
			}
		}
	}
	
	function syncUidsAll() {
		global $adb, $table_prefix;
		$adb->query("delete from {$table_prefix}_messages_sync_all where inbox = 1 and other = 1");
		$result = $adb->limitQuery("select * from {$table_prefix}_messages_sync_all where inbox = 0 or other = 0",0,1);
		if ($result && $adb->num_rows($result) > 0) {
			$this->interval_schedulation = '';
			//crmv@OPER8279
			if (!empty($this->interval_storage)) {	// if isset interval_storage set the limit of sync
				$interval_storage = $this->getIntervalStorage();
				$this->interval_schedulation = $interval_storage['interval'];
			}
			//crmv@OPER8279e
			$userid = $adb->query_result($result,0,'userid');
			$accountid = $adb->query_result($result,0,'accountid');
			if ($adb->query_result($result,0,'inbox') == 0) {
				$adb->pquery("update {$table_prefix}_messages_sync_all set inbox = 1 where accountid = ?",array($accountid));
				$this->syncUidsInbox($userid, $accountid);
			} else {
				$adb->pquery("update {$table_prefix}_messages_sync_all set other = 1 where accountid = ?",array($accountid));
				$this->syncUids(true, $userid, $accountid);
			}
		}
	}
	
	function populateSyncUidsQueue($mode,$action,$userid,$accountid,$folder,$uids,$server_ids_dates) {
		global $adb, $table_prefix;
		($mode == 'inbox') ? $table = "{$table_prefix}_messages_cron_uidi" : $table = "{$table_prefix}_messages_cron_uid";
		$uids = array_filter($uids);
		if (!empty($uids)) {
			foreach($uids as $uid) {
				($action == 'delete' || empty($server_ids_dates[$uid])) ? $date = date('Y-m-d H:i:s') : $date = $server_ids_dates[$uid];
				$values = array(
					'sequence'=>$adb->getUniqueID($table),
					'userid'=>$userid,
					'accountid'=>$accountid,
					'folder'=>$folder,
					'uid'=>$uid,
					'date'=>$date,
					'action'=>$action,
					'cdate'=>date('Y-m-d H:i:s'),
				);
				if ($adb->isMysql()) {
					$adb->pquery("insert ignore into {$table} (".implode(',',array_keys($values)).") values (".generateQuestionMarks($values).")",array($values));
				} else {
					$columns = array_keys($values);
					$adb->format_columns($columns);
					$result = $adb->pquery("select * from {$table} where userid = ? and accountid = ? and folder = ? and uid = ?",array($userid,$accountid,$folder,$uid));
					if (!$result || $adb->num_rows($result) == 0) {
						$adb->pquery("insert into {$table} (".implode(',',$columns).") values (".generateQuestionMarks($values).")",array($values));
					}
				}
			}
		}
	}
	
	function cleanSyncUidsQueue($userid,$accountid,$folder,$uids='',$crmids='') {
		global $adb, $table_prefix;
		$err_uids = $this->getErrUids();	//crmv@50124
		$this->err_uids = array();	//crmv@53430
		if (!empty($uids)) $uids = array_filter($uids);
		if (!empty($crmids)) $crmids = array_filter($crmids);
		if (!empty($err_uids)) $err_uids = array_filter($err_uids);
		
		if (!empty($uids)) {
			$uids = array_map('intval',$uids);
			if (!empty($uids)) {
				$adb->pquery("delete from {$table_prefix}_messages_cron_uid where userid = ? and accountid = ? and folder = ? and uid in (".generateQuestionMarks($uids).")",array($userid,$accountid,$folder,$uids));
				$adb->pquery("delete from {$table_prefix}_messages_cron_uidi where userid = ? and accountid = ? and folder = ? and uid in (".generateQuestionMarks($uids).")",array($userid,$accountid,$folder,$uids));
			}
		}
		if (!empty($crmids)) {
			$crmids = array_map('intval',$crmids);
			$adb->pquery("delete from {$table_prefix}_messages_cron_uid where userid = ? and accountid = ? and folder = ? and uid in (
					select xuid from {$table_prefix}_messages
					where messagesid in (".generateQuestionMarks($crmids)."))",
				array($userid,$accountid,$folder,$crmids)
			);
			$adb->pquery("delete from {$table_prefix}_messages_cron_uidi where userid = ? and accountid = ? and folder = ? and uid in (
					select xuid from {$table_prefix}_messages
					where messagesid in (".generateQuestionMarks($crmids)."))",
				array($userid,$accountid,$folder,$crmids)
			);
		}
		//crmv@50124
		if (!empty($err_uids)) {
			$err_uids = array_map('intval',$err_uids);
			$adb->pquery("update {$table_prefix}_messages_cron_uid set status = 1 where userid = ? and accountid = ? and folder = ? and uid in (".generateQuestionMarks($err_uids).")",array($userid,$accountid,$folder,$err_uids));
			$adb->pquery("update {$table_prefix}_messages_cron_uidi set status = 1 where userid = ? and accountid = ? and folder = ? and uid in (".generateQuestionMarks($err_uids).")",array($userid,$accountid,$folder,$err_uids));
		}
		//crmv@50124e
	}
	
	function checkSyncUidsErrors() {
		global $adb, $table_prefix, $current_user;
		$error = false;
		$tables = array("{$table_prefix}_messages_cron_uid"=>'Messages',"{$table_prefix}_messages_cron_uidi"=>'MessagesInbox');
		foreach($tables as $table => $cron) {
			$resultCron = $adb->pquery("select lastrun from {$table_prefix}_cronjobs where cronname = ?",array($cron));
			if ($resultCron && $adb->num_rows($resultCron) > 0) {
				$lastrun = $adb->query_result($resultCron,0,'lastrun');
				if (empty($lastrun)) continue;
				$result = $adb->pquery("select * from {$table} where userid = ? and action = ? and status = ? and cdate < ? and attempts = ?",array($current_user->id,'fetch',2,$lastrun,$this->max_message_cron_uid_attempts));	//crmv@55450
				if ($result && $adb->num_rows($result) > 0) {
					$error = true;
					break;
				}
			}
		}
		return $error;
	}
	
	function checkSendQueueErrors() {
		global $adb, $table_prefix, $current_user;
		$error = false;
		$resultCron = $adb->pquery("select lastrun from {$table_prefix}_cronjobs where cronname = ?",array('MessagesSend'));
		if ($resultCron && $adb->num_rows($resultCron) > 0) {
			$lastrun = $adb->query_result($resultCron,0,'lastrun');
			//crmv@98338
			if (!empty($lastrun)){
				$dateCol = 'date';
				$adb->format_columns($dateCol);
				$result = $adb->pquery("select * from {$table_prefix}_emails_send_queue where userid = ? and method = ? and status = ? and s_send = ? and $dateCol < ?",array($current_user->id,'send',2,0,$lastrun));
				if ($result && $adb->num_rows($result) > 0) {
					$error = true;
				}
			}
			//crmv@98338e
		}
		return $error;
	}
	
	function cronSync($user_start='',$user_end='') {

		// first of all propagate to server pending changes
		$this->propagateToImap();

		// process uids in queue
		$i = 1;
		while($this->processSyncUidsQueue('noinbox',$user_start,$user_end)) {
			if ($this->messages_by_schedule > 0 && $i == $this->messages_by_schedule) break;
			$i++;
		}
	}
	
	function cronSyncInbox($user_start='',$user_end='') {
		
		// first of all propagate to server pending changes
		$this->propagateToImap('fast');

		// process uids in queue only for inbox folders
		$i = 1;
		while($this->processSyncUidsQueue('inbox',$user_start,$user_end)) {
			if ($this->messages_by_schedule_inbox > 0 && $i == $this->messages_by_schedule_inbox) break;
			$i++;
		}
	}
	
	/*
	 * Process sync queue in order to fetch or delete messages in vte
	 * 
	 * Parameter $mode:
	 * - 'inbox' : only process uids of inbox folders
	 * - 'noinbox' : process all except uids of inbox folders
	 * - any other value : process all
	 */
	function processSyncUidsQueue($mode='',$user_start='',$user_end='') {
		global $adb, $table_prefix, $current_user;
		($mode == 'inbox') ? $table = "{$table_prefix}_messages_cron_uidi" : $table = "{$table_prefix}_messages_cron_uid";
		// crmv@103120
		$where = '';
		if ($user_start != '') {
			$where = " WHERE mu.userid >= $user_start";
			if ($user_end != '') {
				$where .= " AND mu.userid <= $user_end";
			}
		}
		(empty($where)) ? $where .= ' WHERE ' : $where .= ' AND ';
		$where .= "(mu.status = 0 OR (mu.status = 2 AND mu.attempts < {$this->max_message_cron_uid_attempts}))";	//crmv@55450 : not already processed or attempts < max attempts
		$query = 
			"SELECT mu.*
			FROM {$table} mu
			INNER JOIN {$table_prefix}_users u ON u.id = mu.userid AND u.status = 'Active'
			{$where}
			ORDER BY mu.date DESC";
		// crmv@103120e
		$result = $adb->limitQuery($query,0,1);
		if ($result && $adb->num_rows($result) > 0) {
			$sequence = $adb->query_result($result,0,'sequence');
			if (!$current_user) $current_user = CRMEntity::getInstance('Users'); // crmv@167234
			$current_user->id = $userid = $adb->query_result($result,0,'userid');
			$accountid = $adb->query_result($result,0,'accountid');
			$folder = $adb->query_result_no_html($result,0,'folder');
			$uid = $adb->query_result($result,0,'uid');
			$action = $adb->query_result($result,0,'action');
			//echo "$user_start-$user_end: $action u:$userid a:$accountid f:$folder uid:$uid\n";
			
			$this->setAccount($accountid);
			try {
				$this->getZendMailStorageImap($userid);
			} catch (Exception $e) {	// problems with connection (ex. account not configured)
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$this->cleanSyncUidsQueue($userid,$accountid,$folder,array($uid));
				return true;
			}
			try {
				$this->selectFolder($folder);
			} catch (Exception $e) {	// problems with reading of folder (es. folder deleted)
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$this->cleanSyncUidsQueue($userid,$accountid,$folder,array($uid));
				return true;
			}
			
			// set as processing, so if there are problems or timeout with this message, next run skip this
			$adb->pquery("update {$table} set status = ?, cdate = ?, attempts = attempts+1 where sequence = ?",array(2,date('Y-m-d H:i:s'),$sequence));	//crmv@55450

			if ($action == 'delete') {
				$result1 = $adb->pquery("select messagesid from {$table_prefix}_messages where deleted = 0 and smownerid = ? and account = ? and folder = ? and xuid = ?", array($userid,$accountid,$folder,$uid)); //crmv@171021
				if ($result1 && $adb->num_rows($result1) > 0) {
					$crmid = $adb->query_result($result1,0,'messagesid');
					if (!empty($crmid)) {
						$this->deleteCache(array($crmid=>$uid));
					}
				}
				$this->cleanSyncUidsQueue($userid,$accountid,$folder,array($uid));
			} elseif ($action == 'fetch') {
				try {
					$messageId = self::$mail->getNumberByUniqueId($uid);
					$this->saveCache(array($messageId=>$uid));
				} catch(Exception $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					if ($e->getMessage() == 'unique id not found') {
						$this->cleanSyncUidsQueue($userid,$accountid,$folder,array($uid));
					}
					//crmv@50124 if error status remain 2 -- $this->setSkippedUids($uid);
				}
				$this->cleanSyncUidsQueue($userid,$accountid,$folder,$this->getSkippedUids(),$this->getSavedMessages());
				$this->skipped_uids = array();		//crmv@53430
				$this->saved_messages = array();	//crmv@53430
			}
			return true;
		} else {
			return false;
		}
	}
	
	/*
	 * Sync immediately server mail folder with vte cache instead of syncUids/syncUidsInbox and then processSyncUidsQueue that made it in two steps.
	 * 
	 * Parameters
	 * - $num_news : if empty (null,false,0,'') download all $news_ids
	 * - $only_news : download only new messages from the previous time
	 */
	function syncFolder($folder, $num_news=0, $only_news=false) {
		global $current_user, $adb, $table_prefix;

		$this->getZendMailStorageImap();
		$this->selectFolder($folder);

		$server_ids = array();			// populated in checkFlagsChanges
		$server_ids_dates = array();	// populated in checkFlagsChanges
		$cache_ids = array();			// populated in checkFlagsChanges

		//Update flags of cached mail messages - start
		$flag_changed = $this->checkFlagsChanges($current_user->id,$server_ids,$server_ids_dates,$cache_ids);
		//end

		// if no messages cached disable $only_news
		if (empty($cache_ids)) {
			$only_news = false;
		}

		//Save new mail messages and delete - start
		$delete_ids = array_diff($cache_ids,$server_ids);
		$this->deleteCache($delete_ids);
		$this->cleanSyncUidsQueue($current_user->id,$this->account,$folder,$delete_ids);

		$new_ids = array_diff($server_ids,$cache_ids);
		$new_ids = array_reverse($new_ids,true);	//scarico dai piu recenti ai piu vecchi
		if ($only_news) {
			$tmp = array();
			//crmv@171021
			$result = $adb->limitpQuery("SELECT xuid FROM {$this->table_name}
				WHERE deleted = 0 AND mtype = ? AND smownerid = ? AND account = ? AND folder = ?
				ORDER BY mdate DESC",0,1,
				array('Webmail',$current_user->id,$this->account,$folder));
			//crmv@171021e
			if ($result && $adb->num_rows($result) > 0) {
				$last_uid = $adb->query_result($result,0,'xuid');
				$last_messageid = array_search($last_uid,$server_ids);
				if (empty($last_messageid)) {
                    //crmv@204525
                    try {
                        $last_messageid = self::$mail->getNumberByUniqueId($last_uid);
                    } catch(Exception $e) {
                        if ($e->getMessage() == 'unique id not found') {
                            return;
                        }
                    }
                    //crmv@204525e
				}
				foreach($new_ids as $messageid => $uid) {
					if ($messageid > $last_messageid) {
						$tmp[$messageid] = $uid;
					}
				}
			}
			$new_ids = $tmp;
		}
		if (!empty($num_news) && !empty($new_ids)) {
			$new_ids = array_slice($new_ids, 0, $num_news, true);
		}

		$this->saveCache($new_ids);
		$this->cleanSyncUidsQueue($current_user->id,$this->account,$folder,$this->getSkippedUids(),$this->getSavedMessages());
		$this->skipped_uids = array();		//crmv@53430
		$this->saved_messages = array();	//crmv@53430
		//end

		return array(
			'delete_ids'=>$delete_ids,
			'new_ids'=>$new_ids,
			'flag_changed'=>$flag_changed
		);
	}
	
	function addToPropagationCron($operation, $params, $max_attempts=3) {
		global $adb, $table_prefix;
		if (is_array($params)) {
			$params = Zend_Json::encode($params);
		}
		$adb->pquery("insert into {$table_prefix}_messages_prop2imap (sequence,operation,params,status,attempts,max_attempts) values (?,?,?,?,?,?)",array(
			$adb->getUniqueID($table_prefix.'_messages_prop2imap'), $operation, $params, 0, 0, $max_attempts
		));
	}
	
	function propagateToImap($mode='full') {
		global $adb, $table_prefix;
		// skip running propagations and attempts exceeded
		$query = "select * from {$table_prefix}_messages_prop2imap where status <> ? and attempts < max_attempts";
		$params = array(2);
		if ($mode == 'fast') {	// skip slow operations
			$query .= " and operation <> ?";
			$params[] = 'empty';
		}
		$query .= " order by sequence";
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$adb->pquery("update {$table_prefix}_messages_prop2imap set status = ?, attempts = ? where sequence = ?",array(2,$row['attempts']+1,$row['sequence']));
				try {
					$params = Zend_Json::decode($row['params']);
					switch ($row['operation']) {
						case 'flag':
							$this->propagateSetFlag($params['id'],$params['flag'],$params['value']);
							break;
						case 'flag_folder':
							$this->propagateFlagFolder($params['userid'],$params['account'],$params['folder'],$params['flag'],$params['value']);
							break;
						case 'move':
							$this->propagateMoveMessage($params['userid'],$params['account'],$params['folder'],$params['uid'],$params['new_folder'],$params['skip_fetch']);
							break;
						case 'move_mass':
							$this->propagateMassMoveMessage($params['userid'],$params['account'],$params['folder'],$params['uid'],$params['new_folder']);
							break;
						case 'trash':
							$this->propagateTrash($params['userid'],$params['account'],$params['folder'],$params['uid'],$params['fetch']);
							break;
						case 'empty':
							$this->propagateEmpty($params['userid'],$params['account'],$params['folder']);
							break;
					}
					$adb->pquery("delete from {$table_prefix}_messages_prop2imap where sequence = ?",array($row['sequence']));
				} catch (Exception $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					$adb->pquery("update {$table_prefix}_messages_prop2imap set status = ?, error = ? where sequence = ?",array(1,$e->getMessage(),$row['sequence']));
				}
			}
			// crmv@102274
			// This is necessary because the propagateSetFlag (and probably others too) creates a new instance of Messages and use a different account.
			// The problem is that the self::$mail and self::$protocol are static, therefore are shared between all the instances.
			// When the fetch method set the account with setAccount, the self::$protocol is not cleared, since $this->account wasn't changed
			// (another instance was used) and the old $protocol is still used, even if connected with a different user, causing wrong messages
			// to be retrieved.
			$this->setAccount('');
			// crmv@102274e
		}
	}
	
	function syncFolders($userid='', $account='',$skip_empty=false) { //crmv@49843
		global $adb, $table_prefix;
		// crmv@138936
		$query = 
			"SELECT m.userid, m.id FROM {$table_prefix}_messages_account m
			INNER JOIN {$table_prefix}_users u ON u.id = m.userid AND u.status = 'Active'";
		$params = array();
		if (!empty($userid) && !empty($account)) {
				$query .= " WHERE m.userid = ? AND m.id = ?";
				$params[] = $userid;
				$params[] = $account;
		}
		$query .= " ORDER BY m.userid, m.id";
		// crmv@138936e
		$res = $adb->pquery($query,$params);
		if ($res && $adb->num_rows($res) > 0) {
			while($r=$adb->fetchByAssoc($res)) {
				
				$account = $r['id'];
				$userid = $r['userid'];

				$this->setAccount($account);
				try {
					$this->getZendMailStorageImap($userid);
				} catch (Exception $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					//crmv@125629
					if ($e->getMessage() == 'ERR_IMAP_AUTENTICATION') {
						throw new Exception($e->getMessage());
					}
					//crmv@125629e
					continue;
				}
		
				$specialFolders = $this->getSpecialFolders(false);
				if (empty($specialFolders['INBOX']) && $skip_empty)	continue;	//crmv@49843
				$special_folders_list = array_keys($specialFolders);
		
				//Order folder list with $special_folders_list
				$tmp1 = array();
				$tmp2 = array();
				$depths = array();
				$folders = self::$mail->getFolders();
				$this->setFolderSeparator($account,self::$mail->folderSeparator); //crmv@178164
				foreach ($folders as $folder) {
					$foldername = $folder->getGlobalName();
					// crmv@178164 crmv@200733
					$safeRegexpSeparator = str_replace('.', '\.', $this->getFolderSeparator($account));
					$in_array = array_search(preg_replace('#'.$safeRegexpSeparator.'.*#','',$foldername),$special_folders_list);
					// crmv@178164e crmv@200733e
					if ($in_array !== false) {
						$tmp1[$in_array.$foldername] = $folder;
					} else {
						$tmp2[$foldername] = $folder;
					}
					if (strpos($foldername,$this->getFolderSeparator($account)) !== false) { //crmv@178164
						$depths[$foldername] = substr_count($foldername,$this->getFolderSeparator($account)); //crmv@178164
					}
				}
				ksort($tmp1);
				$folders = array_merge($tmp1, $tmp2);
				$folder_root = new Zend\Mail\Storage\Folder($this->rootFolder,$this->rootFolder,false,$folders);	//crmv@125287
				//end
		
				$folders_it = new RecursiveIteratorIterator($folder_root,RecursiveIteratorIterator::SELF_FIRST);
				$folders = array();
				
				$folders[] = array(
					'localname'=>$folder_root->getLocalName(),
					'globalname'=>$folder_root->getGlobalName(),
					'depth'=>0,
					'selectable'=>0,
					'count'=>0,
				);
		
				// crmv@192843 removed count of Shared falder
				
				//crmv@171021 removed code
				
				// crmv@192843 removed count of Links falder
		
				// folder of Flagged mail
				$count = 0;
				//crmv@79192 crmv@171021
				$result = $adb->pquery("SELECT count(distinct messagehash) AS count FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND account = ? AND flagged = ? and mtype = ?",
					array($userid,$account,1,'Webmail'));
				//crmv@79192e crmv@171021e
				if ($result && $adb->num_rows($result) > 0) {
					$count = $adb->query_result($result,0,'count');
				}
				$folders[] = array(
					'localname'=>'Flagged',
					'globalname'=>'Flagged',
					'depth'=>0,
					'selectable'=>1,
					'count'=>$count,
				);
				// end

				// crmv@187622
				$count = 0;
				$result = $adb->pquery("SELECT count(*) AS count FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND account = ?  and mtype = ? and folder = ?", array($userid,$account,'Link','vteScheduled'));
				if ($result && $adb->num_rows($result) > 0) {
					$count = $adb->query_result($result,0,'count');
				}
				$folders[] = array(
					'localname'=>'vteScheduled',
					'globalname'=>'vteScheduled',
					'depth'=>0,
					'selectable'=>1,
					'count'=>$count,
				);
				// crmv@187622e
				
				//crmv@85634 crmv@171021
				$folder_counts = array();
				$result_folder = $adb->pquery("SELECT folder FROM {$this->table_name} WHERE account = ? GROUP BY folder", array($account));
				if ($result_folder && $adb->num_rows($result_folder) > 0) {
					while($row_folder=$adb->fetchByAssoc($result_folder, -1, false)) {
						$result = $adb->pquery("SELECT count(*) AS count FROM {$this->table_name}
							WHERE deleted = 0 AND smownerid = ? AND account = ? AND folder = ? AND mtype = ? AND seen = ?",
							array($userid,$account,$row_folder['folder'],'Webmail',0)
						);
						if ($result && $adb->num_rows($result) > 0) {
							while($row=$adb->fetchByAssoc($result, -1, false)) {
								$folder_counts[$row_folder['folder']] = $row['count'];
							}
						}
					}
				}
				//crmv@85634e crmv@171021e
					
				foreach ($folders_it as $folder) {
					$localName = $folder->getLocalName();
					$globalName = $folder->getGlobalName();
		
					$depth1 = $depths[$globalName];
					$depth2 = $folders_it->getDepth();
					$depth = max($depth1,$depth2);
					if (empty($depth)) $depth = 0;
					
					$folders[] = array(
						'localname'=>$localName,
						'globalname'=>$globalName,
						'depth'=>$depth,
						'selectable'=>$folder->isSelectable(),
						'count'=>$folder_counts[$globalName],	//crmv@51191
					);
				}
				
				$adb->pquery("delete from {$table_prefix}_messages_folders where userid = ? and accountid = ?",array($userid,$account));

				$sequence = 0;
				foreach($folders as $folder) {
					(empty($folder['selectable'])) ? $selectable = 0 : $selectable = 1;
					(empty($folder['count'])) ? $folder['count'] = 0 : $folder['count']; //crmv@60402
					if ($adb->isMysql()) {
						$adb->pquery("insert ignore into {$table_prefix}_messages_folders (userid,accountid,globalname,localname,depth,selectable,count,sequence) values (?,?,?,?,?,?,?,?)",array(
							$userid,$account,$folder['globalname'],$folder['localname'],$folder['depth'],$selectable,$folder['count'],$sequence
						));
					} else {
						$result = $adb->pquery("select * from {$table_prefix}_messages_folders where userid = ? and accountid = ? and globalname = ?",array($userid,$account,$folder['globalname']));
						if (!$result || $adb->num_rows($result) == 0) {
							$adb->pquery("insert into {$table_prefix}_messages_folders (userid,accountid,globalname,localname,depth,selectable,count,sequence) values (?,?,?,?,?,?,?,?)",array(
								$userid,$account,$folder['globalname'],$folder['localname'],$folder['depth'],$selectable,$folder['count'],$sequence
							));
						}
					}
					$sequence++;
				}
			}
		}
	}
	
	//crmv@79192 crmv@171021 crmv@187622 crmv@192843 crmv@201605
	function reloadCacheFolderCount($userid,$accountid,$folder) {
		global $adb, $table_prefix;
		($folder == 'vteScheduled') ? $mtype = 'Link' : $mtype = 'Webmail';
		($folder == 'Flagged') ? $select_count = 'count(distinct messagehash)' : $select_count = 'count(*)';
		$query = "SELECT account, folder, $select_count AS count FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? and mtype = ?";
		$params = array($userid,$mtype);
		if ($accountid != 'all') {
			$query .= ' AND account = ?';
			$params[] = $accountid;
		}
		if ($folder == 'Flagged') {
			$query .= " AND flagged = ?";
			$params[] = 1;
			$query .= ' GROUP BY account';
		} elseif ($folder == 'vteScheduled') {
			$query .= " and folder = ?";
			$params[] = $folder;
			$query .= ' GROUP BY account';
		} else {
			$query .= " AND seen = ?";
			$params[] = 0;
			if ($accountid == 'all') {
				$folders = $this->getAllSpecialFolders($folder);
				$tmp = array();
				foreach($folders as $a => $specialFolders) {
					$tmp[] = "(account = ? AND folder = ?)";
					$params[] = array($a,$specialFolders[$folder]);
				}
				$query .= ' AND ('.implode(' OR ',$tmp).')';
			} else {
				$query .= " AND folder = ?";
				$params[] = $folder;
			}
			$query .= ' GROUP BY account, folder';
		}
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$adb->pquery("update {$table_prefix}_messages_folders set count = ? where userid = ? and accountid = ? and globalname = ?", array(
					$row['count'],
					$userid,
					$row['account'],
					($folder == 'Flagged' || $folder == 'vteScheduled') ? $folder : $row['folder']
				));
			}
		} else {
			if ($accountid != 'all') {
				$adb->pquery("update {$table_prefix}_messages_folders set count = ? where userid = ? and accountid = ? and globalname = ?", array(0,$userid,$accountid,$folder));
			} elseif ($accountid == 'all' && ($folder == 'Flagged' || $folder == 'vteScheduled')) {
				$adb->pquery("update {$table_prefix}_messages_folders set count = ? where userid = ? and globalname = ?", array(0,$userid,$folder));
			} elseif ($accountid == 'all') {
				$params = array(0,$userid);
				$folders = $this->getAllSpecialFolders($folder,$userid);
				$tmp = array();
				foreach($folders as $account => $specialFolders) {
					$tmp[] = "(account = ? AND folder = ?)";
					$params[] = array($account,$specialFolders[$folder]);
				}
				if(!empty($tmp)) {
					$adb->pquery("update {$table_prefix}_messages_folders set count = ? where userid = ?".' AND ('.implode(' OR ',$tmp).')', $params);
				}
			}
		}
	}
	//crmv@79192e crmv@171021e crmv@187622e crmv@192843e crmv@201605e

	/*
	 * Fetch new messages from folder, if $num empty -> fetch only the most recent
	 */
	/* crmv@53430 crmv@54904 */
	function fetchNews($folder, $num=1) {
		global $current_user;
		$this->selectFolder($folder);
		$cache_ids = $this->getSavedUids($current_user->id);
		$server_ids = $this->getServerUids();
		$new_ids = array_diff($server_ids,$cache_ids);
		$new_ids = array_slice(array_reverse($new_ids,true),0,$num,true);
		$this->saveCache($new_ids);
		$savedMessages = $this->getSavedMessages();
		$this->cleanSyncUidsQueue($current_user->id,$this->account,$folder,$this->getSkippedUids(),$savedMessages);
		$this->skipped_uids = array();
		$this->saved_messages = array();
		return $savedMessages;
	}

	function fetch($account, $folder, $only_news = false) {
		
		global $current_user, $adb, $table_prefix;

		//crmv@48471
		if (empty($folder) || $account == '' || in_array($folder,$this->fakeFolders)) {	// || $account == 'all' //crmv@62140
			return '';
		}
		/* crmv@62140
		$specialFolders = $this->getAllSpecialFolders('INBOX');
		if (empty($specialFolders[$account]) || $folder == $specialFolders[$account]['INBOX']) {
			return '';
		}
		crmv@62140e */
		
		//crmv@125629
		$err = $this->checkAccountError($current_user->id, $account);
		if ($err) return $err;
		//crmv@125629e

		// crmv@96019
		if (PerformancePrefs::get('MESSAGES_UPDATE_ICON_PERFORM_IMAP_ACTIONS', '') == 'disable') {
			return 'RELOAD';
		}
		// crmv@96019e
		
		// first of all propagate to server pending changes
		$this->propagateToImap();	//TODO parameterize by user
		
		//crmv@48471e

		// crmv@192843
		if ($account == 'all') {
			$accounts = array();
			$folders = $this->getAllSpecialFolders($folder);
			$tmp = $this->getUserAccounts();
			foreach($tmp as $t) {
				$accountid = $t['id'];
				$accounts[$accountid] = $folders[$accountid][$folder];
			}
		} else {
			$accounts = array($account => $folder);
		}
		$reload = false;
		foreach ($accounts as $account => $folder) {
		// crmv@192843e
			if (empty($folder)) continue; // crmv@192843
			$this->setAccount($account);
			$sync_result = $this->syncFolder($folder, $this->list_max_entries_per_page, $only_news);
			$delete_ids = $sync_result['delete_ids'];
			$new_ids = $sync_result['new_ids'];
			$flag_changed = $sync_result['flag_changed'];
			if (!empty($delete_ids) || !empty($new_ids) || $flag_changed) {
				$reload = true;
			}
		}
		if ($reload) return 'RELOAD';
		return '';
	}
	
	// crmv@166575
	/**
	 * Fetch the body of the email from IMAP and save it into the message
	 */
	public function fetchBody() {
		global $default_charset;
		
		$this->setAccount($this->column_fields['account']);
		$this->getZendMailStorageImap($this->column_fields['assigned_user_id']);
		$this->selectFolder($this->column_fields['folder']);
        //crmv@204525
        try {
            $messageId = $this->getMailResource()->getNumberByUniqueId($this->column_fields['xuid']);
        } catch(Exception $e) {
            if ($e->getMessage() == 'unique id not found') {
                return;
            }
        }
        //crmv@204525e
		$message = $this->getMailResource()->getMessage($messageId);

		$data = $this->getMessageContentParts($message,$messageId,true);
		if (!empty($data['text/plain'])) $data['text/plain'] = implode("\n\n",$data['text/plain']);
		if (!empty($data['text/html'])) $data['text/html'] = implode('<br><br>',$data['text/html']);
		$body = '';
		if (isset($data['text/html'])) {
			$body = $data['text/html'];
			$body = str_replace('&lt;','&amp;lt;',$body);
			$body = str_replace('&gt;','&amp;gt;',$body);
		} elseif (isset($data['text/plain'])) {
			$body = nl2br(htmlentities($data['text/plain'], ENT_COMPAT, $default_charset));
		}
		$this->column_fields['cleaned_body'] = '';	// in order to recalculate it
		$this->column_fields['description'] = $body;
		$this->column_fields['other'] = $data['other'];
		$this->mode = 'edit';
		$this->save('Messages');
		
		return $data;
	}
	// crmv@166575e

	function selectFolder($folder) {
		$this->folder = $folder;
		self::$mail->selectFolder($folder);
	}

	function getServerUids() {
		return self::$mail->getUniqueId();
	}

	function getSavedUids($userid) {
		if (empty($this->folder) || $this->account === '') {
			return false;
		}
		global $adb, $table_prefix;
		$external_codes = array();
		$result = $adb->pquery("SELECT messagesid as crmid, xuid FROM {$this->table_name} WHERE deleted = 0 AND mtype = ? AND smownerid = ? AND account = ? AND folder = ?",array('Webmail',$userid,$this->account,$this->folder)); //crmv@171021
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$external_codes[$row['crmid']] = $row['xuid'];
			}
		}
		return $external_codes;
	}
	
	function checkFlagsChanges($userid,&$server_ids,&$server_ids_dates,&$cache_ids) {
		if (empty($this->folder) || $this->account === '') {
			return;
		}

		// crmv@96019
		if (PerformancePrefs::get('MESSAGES_UPDATE_ICON_PERFORM_IMAP_ACTIONS', '') == 'fast_sync') {
			if ($_REQUEST['file'] == 'Fetch') {
				$interval_imap_fast_sync = PerformancePrefs::get('INTERVAL_IMAP_FAST_SYNC', false);
				if (!empty($interval_imap_fast_sync)) {
					$this->interval_schedulation = $interval_imap_fast_sync;
				}
			}
		}
		// crmv@96019e

		if (!empty($this->interval_schedulation)) {
			$date = date('j-M-Y',strtotime("-{$this->interval_schedulation}"));
			$messageids = self::$protocol->search(array('SINCE "'.$date.'"','NOT DELETED')); //crmv@146115
			/* Lotus do not support SEARCH SINCE... crmv@52514 crmv@57585 crmv@136224 */
			if ($messageids !== false) {
				$chunk = array_chunk($messageids, 50);
				$tmp_server_ids = array();
				foreach($chunk as $i=>$v){
					$tmp = self::$protocol->fetch(array('UID','INTERNALDATE'),$v);
					if (!empty($tmp)) $tmp_server_ids += $tmp;
				}
				$searchSinceSupported = true;
			} else {
				$tmp_server_ids = self::$protocol->fetch(array('UID','INTERNALDATE','FLAGS'),1,INF); //crmv@146115
				$searchSinceSupported = false;
				$limitTime = strtotime("-{$this->interval_schedulation}");
			}
			/* crmv@52514e crmv@57585e crmv@136224e */
		} else {
			/* all */
			$tmp_server_ids = self::$protocol->fetch(array('UID','INTERNALDATE','FLAGS'),1,INF); //crmv@146115
		}
		$server_ids = array();
		foreach($tmp_server_ids as $messageid => $val) {
			//crmv@57585
			$save = false;
			if (!empty($this->interval_schedulation) && !$searchSinceSupported) {
				(strtotime($val['INTERNALDATE']) >= $limitTime) ? $save = true : $save = false;
			} else {
				$save = true;
			}
			//crmv@146115
			if (isset($val['FLAGS'])) {
				$flags = array_map('format_flags', $val['FLAGS']);
				if (in_array(Zend\Mail\Storage::FLAG_DELETED,$flags)) {
					$save = false;
				}
			}
			//crmv@146115e
			if ($save) {
				$server_ids[$messageid] = $val['UID'];
				$server_ids_dates[$val['UID']] = date('Y-m-d H:i:s',strtotime($val['INTERNALDATE']));			
			}
			//crmv@57585e
		}

		$found_changes = false;
		global $adb, $table_prefix;
		//crmv@58931 crmv@171021
		$query = "SELECT xuid, seen, answered, flagged, forwarded, messagesid as crmid FROM {$this->table_name}
			WHERE deleted = 0 AND smownerid = ? AND mtype = ? AND account = ? AND folder = ?";
		$params = array($userid,'Webmail',$this->account,$this->folder);
		//crmv@58931e crmv@171021e
		if (!empty($this->interval_schedulation)) {
			$query .= " AND mdate >= ?"; //crmv@171021
			$params[] = date('Y-m-d',strtotime("-{$this->interval_schedulation}"));
		}
		$result = $adb->pquery($query,$params);		
		if (!$result || $adb->num_rows($result)== 0) {
			return;
		} else {
			$cache_flags = array();
			while($row=$adb->fetchByAssoc($result)) {
				$tmp = array();
				if ($row['seen'] == '1') {
					$tmp[] = Zend\Mail\Storage::FLAG_SEEN;
				}
				if ($row['answered'] == '1') {
					$tmp[] = Zend\Mail\Storage::FLAG_ANSWERED;
				}
				if ($row['flagged'] == '1') {
					$tmp[] = Zend\Mail\Storage::FLAG_FLAGGED;
				}
				if ($row['forwarded'] == '1') {
					//$tmp[] = '$Forwarded';
					$tmp[] = 'Forwarded';
				}
				$cache_flags[$row['xuid']] = $tmp;
				$cache_ids[$row['crmid']] = $row['xuid'];
			}
		}

		$cache_uids = array_keys($cache_flags);
		$server_message_ids = array_flip($server_ids);
		$cache_list = array_intersect($server_ids,$cache_uids);
		//$cache_list = array_slice($cache_list,-1000,1000,true);	//crmv@42701	//crmv@54310

		$managed_flags = array(Zend\Mail\Storage::FLAG_SEEN,Zend\Mail\Storage::FLAG_ANSWERED,Zend\Mail\Storage::FLAG_FLAGGED,'$Forwarded','Forwarded');
		$server_flags = array();

		//crmv@54310 crmv@70424 crmv@174681
		if (count($cache_list) <= $this->fetch_array_chunk_limit) {
			try {
				$server_flags_tmp = self::$protocol->fetch(array('UID','FLAGS'),array_keys($cache_list));	// read only messages already cached
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);
			}
		} else {	// read all messages
			//$server_flags_tmp = self::$protocol->fetch(array('UID','FLAGS'),1,INF);
			$exception = false;
			$server_flags_tmp = array();
			$tmp_cache_list = array_chunk($cache_list, $this->fetch_array_chunk_limit, true);
			foreach($tmp_cache_list as $tmp1) {
				try {
					$server_flags_tmp += self::$protocol->fetch(array('UID','FLAGS'),array_keys($tmp1));
				} catch (Exception $e) {
					$exception = $e;
					break;
				}
			}
			if ($exception) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);
				$server_flags_tmp = array();
			}
		}
		//crmv@54310e crmv@70424e crmv@174681e
		
		if (!empty($server_flags_tmp)) { //crmv@174681
			foreach ($server_flags_tmp as $i => $info) {
				$uid = $info['UID'];
				//crmv@49432
				$flags = array_map('format_flags', $info['FLAGS']);
				$server_flags[$uid] = array_intersect($flags,$managed_flags);
				//crmv@49432e
				if (in_array('Forwarded',$server_flags[$uid]) && in_array('$Forwarded',$server_flags[$uid])) {
					$server_flags[$uid] = array_diff($server_flags[$uid],array('$Forwarded'));
				}
				if (empty($server_flags[$uid])) $server_flags[$uid] = array();
				if (empty($cache_flags[$uid])) $cache_flags[$uid] = array();
				$inter = array_intersect($server_flags[$uid],$cache_flags[$uid]);
				if (count($inter) != count($server_flags[$uid]) || count($inter) != count($cache_flags[$uid])) {
					//$inters[$uid] = array('server'=>$server_flags[$uid],'cache'=>$cache_flags[$uid],'inter'=>$inter);
					$this->updateCacheFlags($userid,$uid,$server_flags[$uid]);	//crmv@42701
					$found_changes = true;
				}
			}
		} //crmv@174681
		return $found_changes;
	}

	function updateCacheFlags($userid,$uid,$flags) {	//crmv@42701
		global $adb, $table_prefix;

		$sql_update = array(
			'seen = ?' => 0,
			'answered = ?' => 0,
			'flagged = ?' => 0,
			'forwarded = ?' => 0,
		);
		foreach($flags as $flag) {
			switch ($flag) {
				case Zend\Mail\Storage::FLAG_SEEN :
					$sql_update['seen = ?'] = 1;
					break;
				case Zend\Mail\Storage::FLAG_ANSWERED :
					$sql_update['answered = ?'] = 1;
					break;
				case Zend\Mail\Storage::FLAG_FLAGGED :
					$sql_update['flagged = ?'] = 1;
					break;
				case 'Forwarded':
				case '$Forwarded':
					$sql_update['forwarded = ?'] = 1;
					break;
			}
		}
		//crmv@42701 crmv@63611 crmv@171021
		$query = "UPDATE {$this->table_name}
			SET ".implode(',',array_keys($sql_update))."
			WHERE xuid = ? AND deleted = 0 AND smownerid = ? AND account = ? AND folder = ?";
		$adb->pquery($query,array($sql_update,$uid,$userid,$this->account,$this->folder));
		//crmv@42701e crmv@63611e crmv@171021e
	}

	function getCacheFlags() {
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT seen, answered, flagged, forwarded FROM {$this->table_name} WHERE {$this->table_name}.messagesid = ?",array($this->id));
		$flags = array();
		if ($result && $adb->num_rows($result) > 0) {
			if ($adb->query_result($result,0,'seen') == '1') {
				$flags[Zend\Mail\Storage::FLAG_SEEN] = Zend\Mail\Storage::FLAG_SEEN;
			}
			if ($adb->query_result($result,0,'answered') == '1') {
				$flags[Zend\Mail\Storage::FLAG_ANSWERED] = Zend\Mail\Storage::FLAG_ANSWERED;
			}
			if ($adb->query_result($result,0,'flagged') == '1') {
				$flags[Zend\Mail\Storage::FLAG_FLAGGED] = Zend\Mail\Storage::FLAG_FLAGGED;
			}
			if ($adb->query_result($result,0,'forwarded') == '1') {
				$flags['$Forwarded'] = '$Forwarded';
				$flags['Forwarded'] = 'Forwarded';
			}
		}
		return $flags;
	}

	function getAddressListString($header_obj,$param) {
		if (get_class($header_obj) != 'ArrayIterator') {
			$header_obj = array($header_obj);
		}
		if ($param == 'full') {
			$return = array();
			foreach ($header_obj as $i) {
				$return[] = $i->toString();
			}
			return implode(', ',$return);
		} else {
			$return = array();
			foreach ($header_obj as $i) {
				$addresslist = $i->getAddressList();
				foreach($addresslist as $address_obj) {
					if ($param == 'email') {
						$return[] = $address_obj->getEmail();
					} elseif ($param == 'name') {
						$return[] = $address_obj->getName();
					}
				}
			}
			return implode(', ',array_filter($return)); // crmv@111982
		}
	}

	function getMessageHeader($message) {
		$headerkeys_addr_type = array('From','To','ReplyTo','Cc','Bcc');
		$headerkeys = array('From','To','ReplyTo','Cc','Bcc','Date','Subject','Sender','Messageid','Xmailer','In-Reply-To','References','Thread-Index','X-Rcpt-To','X-MDRcpt-To','X-MDArrival-Date','Content-Class','Delivery-Date'); // crmv@64178	crmv@84628 crmv@86123
		$return = array();
		$squirrelmail = new Squirrelmail($this,true);
		foreach($headerkeys as $headerkey) {
			try {
				$isset = isset($message->{strtolower($headerkey)});
			} catch(Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$isset = false;
			}
			if ($isset) {
				if (in_array($headerkey,$headerkeys_addr_type)) {
					try {
						$headerobj = $message->getHeader($headerkey);
					} catch(Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						continue;
					}
					$full = str_replace("$headerkey: ",'',$this->getAddressListString($headerobj,'full'));
					if ($headerkey == 'ReplyTo') {
						$full = str_replace('Reply-To: ','',$full);
					}
					$full = $squirrelmail->decodeHeader($full,true,false);

					$name = $this->getAddressListString($headerobj,'name');
					$name = $squirrelmail->decodeHeader($name,true,false);

					$email = $this->getAddressListString($headerobj,'email');

					$return[$headerkey] = array(
						'email'=>strval($email),
						'name'=>strval($name),
						'full'=>strval($full),
					);
				} else {
					//crmv@49548
					try {
						$value = $message->{strtolower($headerkey)};
					} catch(Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						$headers_arr = $message->getHeaders()->toArray();
						$value = $headers_arr[$headerkey];
					}
					//crmv@49548e
					$return[$headerkey] = strval($squirrelmail->decodeHeader($value,true,false));
				}
			}
		}
		return $return;
	}

	/* crmv@59492 crmv@59094 */
	function getMessageData($message,$id,$include_attach_content=false) {
		global $default_charset;

		$data = array();
		$data['header'] = $this->getMessageHeader($message);
		$data['flags'] = $this->getMessageFlags($message);

		try {
			$dispositionNotificationTo = $message->getHeaderField('Disposition-Notification-To');
		} catch (Exception $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			$dispositionNotificationTo = '';
		}
		if ($this->fetchBodyInCron == 'yes' || ($this->fetchBodyInCron == 'no_disposition_notification_to' && empty($dispositionNotificationTo))) {
			$content = $this->getMessageContentParts($message,$id,$include_attach_content);
			if ($content === false)
				return false;
			elseif (!empty($content))
				$data = array_merge($data, $content);
		}
		if (empty($data['flags']['seen'])) {
			$this->restoreSeenFlag($message,$id);
		}
		if (!empty($data['text/plain'])) {
			$data['text/plain'] = implode("\n\n",$data['text/plain']);
		}
		if (!empty($data['text/html'])) {
			$data['text/html'] = implode('<br><br>',$data['text/html']);
		}
		// crmv@68357
		if (!empty($data['text/calendar'])) {
			$data['text/calendar'] = $this->parseAndSplitIcal($data['text/calendar']);
		}
		// crmv@68357e

		// crmv@64178
		// fix missing headers for some servers (For example: MailDaemon)
		if (empty($data['header']['Messageid'])) {
			// generate a fake messageid
			$uniq_id = md5(strval($uid) . '_' . $data['header']['Subject']);
			$mid = sprintf('<%s@%s>', $uniq_id, 'localhost');
			$data['header']['Messageid'] = $mid;
		}
		// crmv@86123
		if (!empty($data['header']['Date']) && strpos($data['header']['Date'],"\n") !== false) {
			$data['header']['Date'] = substr($data['header']['Date'],0,strpos($data['header']['Date'],"\n"));
		}
		if (empty($data['header']['Date']) && !empty($data['header']['X-MDArrival-Date'])) {
			$data['header']['Date'] = $data['header']['X-MDArrival-Date'];
		}
		if (empty($data['header']['Date']) && !empty($data['header']['Delivery-Date'])) {
			$data['header']['Date'] = $data['header']['Delivery-Date'];
		}
		// crmv@86123e
		if (empty($data['header']['To']['full']) && !empty($data['header']['X-Rcpt-To'])) {
			$data['header']['To']['full'] = $data['header']['X-Rcpt-To'];
		}
		if (empty($data['header']['To']['full']) && !empty($data['header']['X-MDRcpt-To'])) {
			$data['header']['To']['full'] = $data['header']['X-MDRcpt-To'];
		}
		// crmv@64178e

		return $data;
	}

	// crmv@68357
	// parse several ics/ical inline parts and split them in order to have one event/todo per item
	public function parseAndSplitIcal($icals) {

		$list = array();
		if (!is_array($icals)) $icals = array($icals);
		
		foreach ($icals as $icalTxt) {
			$pieces = array();
			//$config = array( "unique_id" => "VTECRM");
			$vcalendar = new VTEvcalendar();
			$r = $vcalendar->parse($icalTxt);
			if ($r === false) continue;
			
			// add the prodid, since it's not read properly
			if (preg_match('/^PRODID:(.*)$/m', $icalTxt, $matches)) {
				$vcalendar->prodid = $matches[1];
			}
			// now parse events and todos, other components are not supported
			while ($piece = $vcalendar->getComponent("vevent")) {
				$pieces[] = $piece;
			}
			while ($piece = $vcalendar->getComponent("vtodo")) {
				$pieces[] = $piece;
			}
			
			if (count($pieces) == 0) {
				continue; // unknown components
			} elseif (count($pieces) == 1) {
				// only 1, output as it was
				$list[] = trim($icalTxt);
			} else {
				// more than 1, must split
				$tzone = $vcalendar->getComponent("vtimezone");
				foreach ($pieces as $piece) {
					$newcal = new VTEvcalendar();
					if ($tzone) $newcal->addComponent($tzone);
					if ($vcalendar->version) $newcal->setVersion($vcalendar->version);
					if ($vcalendar->prodid) $newcal->prodid = $vcalendar->prodid;
					if ($vcalendar->method) $newcal->setMethod($vcalendar->method);
					if ($vcalendar->calscale) $newcal->setCalscale($vcalendar->calscale);
					$newcal->addComponent($piece);
					$out = $newcal->createCalendar();
					if ($out !== false) $list[] = trim($out);
				}
				
			}
		}

		return $list;
	}
	// crmv@68357e
	
	/* crmv@59094 */
	function getMessageContentParts($message,$id,$include_attach_content=false) {
		
		$data = array();
		
		$isMultipart = false;
		try {
			if ($message->isMultipart()) {
				$isMultipart = true;
			}
		} catch (Exception $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
		}
		//crmv@150957
		$boundary = '';
		try {
			$boundary = $message->getHeaderField('content-type', 'boundary');
		} catch (Exception $e) {}
		if (!$isMultipart && empty($boundary)) {
			// try to search the boundary in the content
			$content = $message->__toString($message->getContent());
			$matches = array();
			preg_match('#Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"#i', $content, $matches);
			list(, $boundary) = $matches;
			if (!empty($boundary)) {
				$isMultipart = true;
				$message->contentType = 'multipart/alternative';
				$message->boundary = $boundary;
			}
		}
		//crmv@150957e
		
		if (!$isMultipart) {
			try {
				$contentobj = $message->getHeader('Content-Type');
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			}
			if (get_class($contentobj) == 'Zend\Mail\Header\ContentType') {
				$contenttype = strtolower($contentobj->getType());
				$parameters = $contentobj->getParameters();	//crmv@90966
			} elseif (get_class($contentobj) == 'ArrayIterator') {
				foreach ($contentobj as $contenttmp) {
					$contenttype = strtolower($contenttmp->getType());
					$parameters = $contenttmp->getParameters();	//crmv@90966
					break;
				}
			} else {
				$contenttype = 'text/plain';
				$parameters = '';	//crmv@90966
			}
			if (!in_array($contenttype,array('text/plain','text/html', 'text/calendar')) && strpos($contenttype,'text/') !== false) $contenttype = 'text/plain';	//crmv@59605 crmv@68357
			try {
				$charset = $message->getHeaderField('Content-Type', 'charset');
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$charset = '';
			}
			$encoding = '';
			try {
				$isset = (isset($message->contentTransferEncoding) && !empty($message->contentTransferEncoding));
			} catch(Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$isset = false;
			}
			if ($isset) {
				$encoding = $message->contentTransferEncoding;
			}
			$content = $message->__toString($message->getContent());	// <- this slow down!!!!!
			//crmv@90966
			if (!in_array($contenttype,array('text/plain','text/html', 'text/calendar'))) {
				$otherContent = array('content'=>$content);
				$otherContent['parameters'] = $parameters;
				$otherContent['parameters']['contenttype'] = $contenttype;
				if (isset($message->contentdisposition)) {
					$otherContent['parameters']['contentdisposition'] = $message->getHeaderField('contentdisposition');
				} else {
					$otherContent['parameters']['contentdisposition'] = 'attachment';
				}
				if (!empty($charset)) {
					$otherContent['parameters']['charset'] = $charset;
				}
				if (!empty($encoding)) {
					$otherContent['parameters']['encoding'] = $encoding;
				}
				$otherContent['parameters']['size'] = $message->getSize();	//crmv@65328
				try {
					$contentid = $message->getHeader('Content-ID');
					//crmv@58436
					$contentidClass = get_class($contentid);
					if ($contentidClass !== false && $contentidClass == 'ArrayIterator') {
						foreach($contentid as $c) {
							try {
								$contentid = $c->getFieldValue();
								break;
							} catch (Exception $e) {}
						}
					} else {
						$contentid = $contentid->getFieldValue();
					}
					//crmv@58436e
					$contentid = ltrim($contentid,'<');
					$contentid = rtrim($contentid,'>');
					$otherContent['parameters']['content_id'] = $contentid;
				} catch (Exception $e) {}
				//crmv@45179	crmv@43245	crmv@53651
				//crmv@136313
				if (!empty($otherContent['parameters']['name'])) {
					$filename_parts = pathinfo($otherContent['parameters']['name']);
					if (empty($filename_parts['filename'])) unset($otherContent['parameters']['name']);
				}
				//crmv@136313e
				if (empty($otherContent['parameters']['name'])) {
					$filename = 'Unknown';
					try {
						$filename_tmp = $message->getHeader('Content-Disposition')->getFieldValue();
						$pos = stripos($filename_tmp,'filename=');	//crmv@129689
						if (!empty($filename_tmp) && $pos !== false) {
							$r = preg_match('/filename="([^"]+)"/i', $filename_tmp, $matches);	//crmv@129689
							if (!empty($matches[1])) $filename = $matches[1];
						}
					} catch (Exception $e) {}
					if ($filename == 'Unknown' && $contenttype == 'message/delivery-status') {
						$filename = 'details.txt';
					}
					if ($filename == 'Unknown' && $contenttype == 'text/rfc822-headers') {
						$filename = 'message.txt';
					}
					$otherContent['parameters']['name'] = $filename;
				}
				//crmv@45179e	crmv@43245e	crmv@53651e
				if (!$include_attach_content) unset($otherContent['content']);	//crmv@59492
				$data['other'][] = $otherContent;
			} else {
				$content = $this->decodePart($content,$encoding,$charset);
				$data[$contenttype][] = $content;
			}
			//crmv@90966e
		} else {
			try {
				foreach (new RecursiveIteratorIterator($message) as $part) {	// <- this slow down!!!!!
					/*
					echo $id.'<br /><pre>';
					print_r($part);
					echo '</pre><br /><br />';
					*/
					try {
						$contentobj = $part->getHeader('Content-Type');
					} catch (Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					}
					if (get_class($contentobj) == 'Zend\Mail\Header\ContentType') {
						$contenttype = strtolower($contentobj->getType());
						$parameters = $contentobj->getParameters();
					} elseif (get_class($contentobj) == 'ArrayIterator') {
						foreach ($contentobj as $contenttmp) {
							$contenttype = strtolower($contenttmp->getType());
							$parameters = $contenttmp->getParameters();
							break;
						}
					} else {
						$contenttype = 'text/plain';
						$parameters = '';
					}
					try {
						$charset = $part->getHeaderField('Content-Type', 'charset');
					} catch (Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						$charset = '';
					}
					$encoding = '';
					try {
						$isset = (isset($part->contentTransferEncoding) && !empty($part->contentTransferEncoding));
					} catch(Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						$isset = false;
					}
					if ($isset) {
						$encoding = $part->contentTransferEncoding;
					}
					$content = $part->__toString($part->getContent());

					// text/html content
					$txtContent = $this->decodePart($content,$encoding,$charset);

					// attachment content
					$otherContent = array('content'=>$content);
					$otherContent['parameters'] = $parameters;
					$otherContent['parameters']['contenttype'] = $contenttype;
					if (isset($part->contentdisposition)) {
						$otherContent['parameters']['contentdisposition'] = $part->getHeaderField('contentdisposition');
					}
					if (!empty($charset)) {
						$otherContent['parameters']['charset'] = $charset;
					}
					if (!empty($encoding)) {
						$otherContent['parameters']['encoding'] = $encoding;
					}
					$otherContent['parameters']['size'] = $part->getSize();	//crmv@65328
					try {
						$contentid = $part->getHeader('Content-ID');
						//crmv@58436
						$contentidClass = get_class($contentid);
						if ($contentidClass !== false && $contentidClass == 'ArrayIterator') {
							foreach($contentid as $c) {
								try {
									$contentid = $c->getFieldValue();
									break;
								} catch (Exception $e) {
									$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
								}
							}
						} else {
							$contentid = $contentid->getFieldValue();
						}
						//crmv@58436e
						$contentid = ltrim($contentid,'<');
						$contentid = rtrim($contentid,'>');
						$otherContent['parameters']['content_id'] = $contentid;
					} catch (Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					}

					// check if is text/html part or attachment
					$isText = false;
					if (in_array($contenttype,array('text/plain','text/html', 'text/calendar'))) { // crmv@68357
						$isText = true;
					}
					//crmv@46629
					if (in_array($contenttype,array('text/plain','text/html', 'text/calendar')) && $otherContent['parameters']['contentdisposition'] == 'inline') { // crmv@68357
						$isText = true;
					//crmv@46629e
					} elseif (!in_array($contenttype,array('text/plain','text/html', 'text/calendar')) || !empty($otherContent['parameters']['name']) || !empty($otherContent['parameters']['contentdisposition'])) { // crmv@68357
						if ($isText && $contenttype == 'text/calendar') $data[$contenttype][] = $txtContent; // crmv@68357, split it as an attachment + ical
						$isText = false;
						//crmv@45179	crmv@43245	crmv@53651
						//crmv@136313
						if (!empty($otherContent['parameters']['name'])) {
							$filename_parts = pathinfo($otherContent['parameters']['name']);
							if (empty($filename_parts['filename'])) unset($otherContent['parameters']['name']);
						}
						//crmv@136313e
						if (empty($otherContent['parameters']['name'])) {
							$filename = 'Unknown';
							try {
								$filename_tmp = $part->getHeader('Content-Disposition')->getFieldValue();
								$pos = stripos($filename_tmp,'filename=');	//crmv@129689
								if (!empty($filename_tmp) && $pos !== false) {
									$r = preg_match('/filename="([^"]+)"/i', $filename_tmp, $matches);	//crmv@129689
									if (!empty($matches[1])) $filename = $matches[1];
								}
							} catch (Exception $e) {
								$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
							}
							if ($filename == 'Unknown' && $contenttype == 'message/delivery-status') {
								$filename = 'details.txt';
							}
							if ($filename == 'Unknown' && $contenttype == 'text/rfc822-headers') {
								$filename = 'message.txt';
							}
							//crmv@129689
							if ($filename == 'Unknown' && $contenttype == 'message/rfc822') {
								$messagesid = 0;
								$error = '';
								$str = $otherContent['content'];
								$str = $this->decodeAttachment($str,$otherContent['parameters']['encoding'],$otherContent['parameters']['charset']);
								$eml_message = $this->parseEML(0, $messagesid, $error, $str, true);
								if (empty($error) && !empty($eml_message['subject'])) $filename = $eml_message['subject'];
							}
							//crmv@129689e
							//crmv@90697
							if ($filename == 'Unknown' && stripos($contenttype,'image/') === 0) {
								$extension = substr($contenttype,6);
								if(in_array(strtolower($extension),$this->inline_image_supported_extensions)){
									$filename .= '.'.$extension;
								}
							}
							if ($filename == 'Unknown' && empty($otherContent['parameters']['contentdisposition'])) {
								$otherContent['parameters']['contentdisposition'] = 'attachment';
							}
							//crmv@90697e
							$otherContent['parameters']['name'] = $filename;
						}
						//crmv@45179e	crmv@43245e	crmv@53651e
					}
					if ($isText) {
						$data[$contenttype][] = $txtContent;
					} else {
						if (!$include_attach_content) unset($otherContent['content']);	//crmv@59492
						$data['other'][] = $otherContent;
					}
				}
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				if ($e->getMessage() == 'Not a valid Mime Message: End Missing') {
					return false;
				}
			}
		}
		return $data;
	}

	function getMessageFlags($message) {
		$flags = array(
			'seen' => '',
			'answered' => '',
			'flagged' => '',
			'forwarded' => '',
			'draft' => '',	//crmv@84628
			'deleted' => '', //crmv@146115
		);
		if ($message->hasFlag(Zend\Mail\Storage::FLAG_SEEN)) {
			$flags['seen'] = 'on';
		}
		if ($message->hasFlag(Zend\Mail\Storage::FLAG_ANSWERED)) {
			$flags['answered'] = 'on';
		}
		if ($message->hasFlag(Zend\Mail\Storage::FLAG_FLAGGED)) {
			$flags['flagged'] = 'on';
		}
		if ($message->hasFlag('Forwarded') || $message->hasFlag('$Forwarded')) {
			$flags['forwarded'] = 'on';
		}
		//crmv@84628
		if ($message->hasFlag(Zend\Mail\Storage::FLAG_DRAFT)) {
			$flags['draft'] = 'on';
		}
		//crmv@84628e
		//crmv@146115
		if ($message->hasFlag(Zend\Mail\Storage::FLAG_DELETED)) {
			$flags['deleted'] = 'on';
		}
		//crmv@146115e
		return $flags;
	}

	function restoreSeenFlag($message,$id) {
		$flags = $message->getFlags();
		//crmv@49432
		unset($flags[array_search(Zend\Mail\Storage::FLAG_SEEN, $flags)]);
		unset($flags[array_search(Zend\Mail\Storage::FLAG_RECENT, $flags)]);	// error to set recent flag
		//crmv@49432e
		if (!empty(self::$mail)) //crmv@90941
		try {
			self::$mail->setFlags($id, $flags);
		} catch (Zend\Mail\Storage\Exception\RuntimeException $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
		} catch (Zend\Mail\Protocol\Exception\RuntimeException $e) { // crmv@191000
			$this->logException($e,__FILE__,__LINE__,__METHOD__);
		}
	}

	function decodePart($content,$encoding='',$charset='') {
		global $default_charset;
		if (isset($encoding)) {
			switch (strtolower($encoding)) {	//crmv@46629
				case 'base64':
					$content = base64_decode($content);
					break;
				case 'quoted-printable':
					$content = quoted_printable_decode($content);
					break;
			}
		}
		//crmv@54247 crmv@80351
		if (function_exists('mb_detect_encoding') && (empty($charset) || strtolower(substr($charset, 0, 4)) == 'iso-' || in_array(strtolower($charset),array('ascii','utf-8')))) {	//crmv@127068 crmv@134869 crmv@142043
			// add here new encodings to check, pay attention to the order!
			if (strtolower(substr($charset, 0, 4)) == 'iso-') {
				// use the provided charset as the fallback during detection,
				// since there is no way to tell from the different ISO charsets
				$encorder = 'ASCII,UTF-8,'.strtoupper($charset);
			} else {
				// otherwise do it as usual
				$encorder = 'ASCII,UTF-8,ISO-8859-1';
			}
			$detect_charset = mb_detect_encoding($content, $encorder);
			if (!empty($detect_charset)) $charset = $detect_charset;
		}
		//crmv@54247e crmv@80351e
		//crmv@90390
		$content_encoded = correctEncoding($content, $default_charset, $charset);
		if ($content_encoded !== false) $content = $content_encoded;
		//crmv@90390e
		return $content;
	}
	
	function decodeAttachment($content,$encoding='',$charset='') {
		global $default_charset;
		if (isset($encoding)) {
			switch (strtolower($encoding)) {	//crmv@46629
				case 'base64':
					$content = base64_decode($content);
					break;
				case 'quoted-printable':
					$content = quoted_printable_decode($content);
					break;
			}
		}
		return $content;
	}
	
	function propagateEmpty($userid,$account,$folder) {
		global $adb, $table_prefix, $current_user;
		$tmp_current_user_id = $current_user->id;
		$current_user->id = $userid;
						
		$focus = CRMEntity::getInstance('Messages');
		$focus->setAccount($account);
		$focus->getZendMailStorageImap($userid);
		
		$focus->emptyFolder($folder);
		$focus->syncFolder($folder);
		$focus->reloadCacheFolderCount($userid,$account,$folder);
		
		$current_user->id = $tmp_current_user_id;
	}

	/* crmv@56636 */
	function emptyFolder($folder) {
		
		// delete messages in folder
		self::$mail->selectFolder($folder);
		$uids = $this->getServerUids();
		if (!empty($uids)) {
			foreach($uids as $messageId => $uid) {
				//$messageId = self::$mail->getNumberByUniqueId($uid);
				try {
					self::$mail->removeMessage($messageId);
				} catch (Zend\Mail\Storage\Exception\RuntimeException $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				}
			}
		}
		
		// delete messages in subfolders
		$subfolders = array();
		try {
			$folders = self::$mail->getFolders($folder);
			if ($folders->getGlobalName() == $this->getFolderSeparator($this->account)) {	// check to have the correct tree //crmv@178164
				$folders_it = new RecursiveIteratorIterator($folders,RecursiveIteratorIterator::CHILD_FIRST);
				foreach ($folders_it as $tmp_folders) {
					if ($tmp_folders->getGlobalName() == $folder) {
						$folders = $tmp_folders;
						break;
					}
				}
			}
			$specialFolders = $this->getSpecialFolders();
			$folders_it = new RecursiveIteratorIterator($folders,RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($folders_it as $localName => $leave_folder) {
				if ($leave_folder == $folder || in_array($leave_folder,$specialFolders)) {	// check to not delete Trash folder or other special folders
					continue;
				}
				$leave_folder = htmlspecialchars($leave_folder);
				self::$mail->selectFolder($leave_folder);
				$uids = $this->getServerUids();
				if (!empty($uids)) {
					foreach($uids as $messageId => $uid) {
						//$messageId = self::$mail->getNumberByUniqueId($uid);
						self::$mail->removeMessage($messageId);
					}
				}
				$subfolders[] = $leave_folder;
			}
		} catch (Zend\Mail\Exception\InvalidArgumentException $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
		}
		
		// delete subfolders
		if (!empty($subfolders)) {
			try {
				self::$mail->selectFolder($folder);
				foreach ($subfolders as $leave_folder) {
					self::$mail->removeFolder($leave_folder);
				}
			} catch (Zend\Mail\Exception\InvalidArgumentException $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			}
		}
	}

	function flagFolder($account,$folder,$flag) {
		global $adb, $table_prefix, $current_user;
		
		if ($flag == 'seen') {
			$field = 'seen';
			$value = 1;
		} elseif ($flag == 'unseen') {
			$field = 'seen';
			$value = 0;
		}
		
		$this->addToPropagationCron('flag_folder', array('userid'=>$current_user->id,'account'=>$account,'folder'=>$folder,'flag'=>$field,'value'=>$value));
		
		//crmv@63611 crmv@171021
		$adb->pquery("UPDATE {$this->table_name}
			SET $field = ?
			WHERE deleted = 0 AND mtype = ? AND smownerid = ? AND account = ? AND folder = ? AND $field <> ? ",array($value,'Webmail',$current_user->id,$account,$folder,$value)); //crmv@57797
		//crmv@63611e crmv@171021e

		if ($field == 'seen') {
			$this->reloadCacheFolderCount($current_user->id,$account,$folder);
		}
	}
	
	function propagateFlagFolder($userid,$account,$folder,$flag,$value) {
		$focus = CRMEntity::getInstance('Messages');

		$focus->setAccount($account);
		$focus->getZendMailStorageImap($userid);
		self::$mail->selectFolder($folder);

		$managed_flags = array(Zend\Mail\Storage::FLAG_SEEN,Zend\Mail\Storage::FLAG_ANSWERED,Zend\Mail\Storage::FLAG_FLAGGED,'$Forwarded','Forwarded');
		$server_flags = array();
		$server_flags_tmp = self::$protocol->fetch(array('UID','FLAGS'),1,INF);
		foreach ($server_flags_tmp as $i => $info) {
			$uid = array_shift($info);
			//crmv@49432
			$flags = array_map('format_flags', $info['FLAGS']);
			$oldflags = $flags = array_intersect($flags,$managed_flags);
			//crmv@49432e
			if ($flag == 'seen') {
				if ($value == 0) {
					unset($flags[array_search(Zend\Mail\Storage::FLAG_SEEN,$flags)]);
				} elseif ($value == 1 && !in_array(Zend\Mail\Storage::FLAG_SEEN,$flags)) {
					$flags[] = Zend\Mail\Storage::FLAG_SEEN;
				}
			}
			$server_flags[$uid] = $flags;
			try {
				self::$mail->setFlags($i, $flags);
			} catch (Zend\Mail\Storage\Exception\RuntimeException $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			}
		}
	}

	function folderMove($account,$folder,$move_in) {
		global $current_user; // crmv@205127
		$this->setAccount($account);
		$specialFolders = $this->getSpecialFolders();
		if (in_array($folder,$specialFolders)) {
			return false;
		}
		$this->getZendMailStorageImap();
		$folder_tree = explode($this->getFolderSeparator($account),$folder); //crmv@178164
		//crmv@125287
		if ($move_in == $this->rootFolder) $move_in = $folder_tree[count($folder_tree)-1];
		else $move_in .= $this->getFolderSeparator($account).$folder_tree[count($folder_tree)-1];	//crmv@47411 crmv@178164
		//crmv@125287e
		try {
			self::$mail->renameFolder($folder,$move_in);
			$this->syncFolders($current_user->id,$account); // crmv@205127 force sync
			return true;
		} catch (Exception $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			/*
			try {
				// new folder already exixsts
				$folder = self::$mail->getFolders();
				foreach($folder_tree as $f) {
					$folder = $folder->__get($f);
				}
			} catch (Exception $e) {
				// old folder do not exists
			}
			*/
			return false;
		}
	}

	function folderCreate($account,$folder,$current_folder) {
		global $current_user; // crmv@205127
		if ($current_folder == $this->rootFolder) $current_folder = null;	//crmv@125287
		$this->setAccount($account);
		$this->getZendMailStorageImap();
		try {
			//crmv@91187
			global $default_charset;
			if (function_exists('mb_convert_encoding')) {
				$folder = mb_convert_encoding($folder, "UTF7-IMAP",$default_charset);
			}
			//crmv@91187e
			self::$mail->createFolder($folder,$current_folder,$this->getFolderSeparator($account));	//crmv@47411 crmv@178164
			$this->syncFolders($current_user->id,$account); // crmv@205127 force sync
		} catch (Exception $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			return false;
		}
	}

	function getFoldersList($mode='list',$current_folder='',$move_mode='') {
		global $adb, $table_prefix, $current_user;
		
		$folders = array();
		$query = "select * from {$table_prefix}_messages_folders where userid = ? and accountid = ?";
		$params = array($current_user->id,$this->getAccount());
		// crmv@187622
		if ($mode != 'list') {
			$query .= " and localname not in (".generateQuestionMarks($this->fakeFolders).")";
			$params[] = $this->fakeFolders;
		}
		// crmv@187622e
		if ($move_mode != 'folders') {
			$query .= " and localname <> ?";
			$params[] = $this->rootFolder;	//crmv@125287
		}
		$query .= " order by sequence";
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			//crmv@61520
			while($row=$adb->fetchByAssoc($result, -1, false)) {
				$localName = htmlentities(str_replace("\x00", '', imap_utf7_decode($row['localname'])), ENT_NOQUOTES, 'ISO-8859-1');
				$globalName = htmlentities(str_replace("\x00", '', imap_utf7_decode($row['globalname'])), ENT_NOQUOTES, 'ISO-8859-1');
				switch ($localName) {
					case $this->rootFolder:	//crmv@125287
						$folders[($globalName)] = array(
							'label'=>'<span style="padding-right:10px;color:#464646">'.($localName).'</span>'.getTranslatedString('LBL_ROOT','Messages'),
							//crmv@61520 e
							'selectable'=>true,
							'depth'=>0
						);
						break;
					case (in_array($localName,$this->fakeFolders)): // crmv@187622
						if ($row['count'] > 0 && in_array($localName,array('Flagged','vteScheduled'))) { // crmv@192843
							$arr = array(
								'label'=>getTranslatedString('LBL_Folder_'.$localName,'Messages'),
								'selectable'=>$row['selectable'],
								'depth'=>$row['depth'],
								'count'=>$row['count']
							);
							(isset($this->folderImgs[$localName])) ? $arr['vteicon'] = $this->folderImgs[$localName] : $arr['vteicon'] = 'folder'; // crmv@192843
							$folders[$globalName] = $arr;
						}
						break;
					default:
						$specialFolders = $this->getSpecialFolders();
						$aliasSpecialFolder = array_search($globalName,$specialFolders);
						if (!empty($aliasSpecialFolder)) {
							$label = $aliasSpecialFolder;
						} else {
							$label = ($localName); //crmv@61520
						}
						$label_trans = getTranslatedString('LBL_Folder_'.$label,'Messages');
						if ($label_trans != 'LBL_Folder_'.$label) {
							$label = $label_trans;
						}
						(!empty($aliasSpecialFolder)) ? $img_str = $aliasSpecialFolder : $img_str = $localName; // crmv@192843
						$arr = array(
							'label'=>$label,
							// crmv@192843 removed
							'selectable'=>$row['selectable'],
							'depth'=>$row['depth'],
							'count'=>$row['count'],
							'bg_notification_color'=>'#2c80c8'
						);
						if ($mode == 'move' && !empty($current_folder) && $globalName == $current_folder) {
							$arr['selectable'] = false;
						}
						(isset($this->folderImgs[$img_str])) ? $arr['vteicon'] = $this->folderImgs[$img_str] : $arr['vteicon'] = 'folder'; // crmv@192843
						$folders[$globalName] = $arr;
						break;
				}
			}
		/*
		} else {
			// force sync
			$this->syncFolders($current_user->id,$this->getAccount());
			$folders = $this->getFoldersList($mode,$current_folder,$move_mode);
		*/
		}
		return $folders;
	}

	function getStrUnreadMessageCount($folder='') {
		$string = '';
		$count = $this->getUnreadMessageCount($folder);
		if (intval($count) > 0) {
			$string = ' ('.$count.')';
		}
		return $string;
	}

	function getUnreadMessageCount($folder='') {
		if (empty($folder)) {
			global $current_folder;
			$folder = $current_folder;
		}
		$account = $this->getAccount();
		if (!empty($folder) && !in_array($folder,$this->fakeFolders)) { // crmv@192843
			global $adb, $table_prefix, $current_user;
			//crmv@171021
			$query = "SELECT count(*) AS count FROM {$this->table_name}
				WHERE deleted = 0 AND smownerid = ? AND seen = ? and mtype = ?";
			//crmv@171021e
			$params = array($current_user->id,0,'Webmail');
			if ($account == 'all') {
				// crmv@192843
				$folders = $this->getAllSpecialFolders($folder);
				$tmp = array();
				foreach($folders as $account => $specialFolders) {
					$tmp[] = "(account = ? AND folder = ?)"; //crmv@171021
					$params[] = array($account,$specialFolders[$folder]);
				}
				// crmv@192843e
				if(!empty($tmp)) $query .= ' AND ('.implode(' OR ',$tmp).')'; // crmv@170276
			// crmv@42537
			} elseif ($folder == 'any') {
				$query .= ' AND account = ?';
				$params[] = array($account);
			// crmv@42537e
			} else {
				$query .= " and account = ? and folder = ?";
				$params[] = array($account,$folder);
			}
			$result = $adb->pquery($query,$params);
			if ($result && $adb->num_rows($result) > 0) {
				return $adb->query_result($result,0,'count');
			}
		} else {
			return false;
		}
	}

	// crmv@63349
	public function getRelatedModComments($return_query=false, $userid='') {
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			return $this->getRelatedModComments_tmp($return_query, $userid);
		} else {
			return $this->getRelatedModComments_notmp($return_query, $userid);
		}
	}

	public function getRelatedModComments_notmp($return_query=false, $userid='') {
		global $adb, $table_prefix, $current_user;

		if (empty($userid)) {
			global $current_user;
			$user = $current_user;
		} else {
			$user = CRMEntity::getInstance('Users');
			$user->retrieveCurrentUserInfoFromFile($userid);
		}

		$query = "SELECT messagesid AS \"id\" FROM {$table_prefix}_modcomments_msgrel WHERE userid = ?";
		$params = array($user->id);
		if ($return_query) {
			return $adb->convert2Sql($query,$adb->flatten_array($params));
		}
		$result = $adb->pquery($query,$params);
		$tmp = array();
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result, -1, false)) {
				$tmp[] = $row['id'];
			}
		}	
		return $tmp;
	}
	
	public function isMessageRelatedModComments($messageId) {
		global $adb, $table_prefix, $current_user;
		
		$cnt = 0;
		$result = $adb->pquery("SELECT COUNT(*) AS cnt FROM {$table_prefix}_modcomments_msgrel WHERE userid = ? AND messagesid = ?", array($current_user->id, $messageId));
		if ($result && $adb->num_rows($result) > 0) {
			$cnt = intval($adb->query_result_no_html($result, 0, 'cnt'));
		}	
		return ($cnt > 0);
	}
	
	public function countRelatedModComments() {
		global $adb, $table_prefix, $current_user;
		
		$cnt = 0;
		$result = $adb->pquery("SELECT COUNT(*) AS cnt FROM {$table_prefix}_modcomments_msgrel WHERE userid = ?", array($current_user->id));
		if ($result && $adb->num_rows($result) > 0) {
			$cnt = intval($adb->query_result_no_html($result, 0, 'cnt'));
		}	
		return $cnt;
	}
	
	public function regenCommentsMsgRelTable($userid, $messagesid = 0) {
		global $adb, $table_prefix;
		
		if (empty($userid)) {
			global $current_user;
			$user = $current_user;
		} else {
			$user = CRMEntity::getInstance('Users');
			$user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		// clean
		$this->cleanCommentsMsgRelTable($userid, $messagesid);
		
		//crmv@58931 crmv@60402 crmv@171021
		$params = Array();
		if($adb->isMssql() || $adb->isOracle()){
			$col_arr = array('user');
			$adb->format_columns($col_arr);
			$userCol = $col_arr[0];
		} else {
			$userCol = 'user';
		}	
		
		$idCol = 'ID';	// leave uppercase, or oracle will have problems
		$adb->format_columns($idCol);
		
		$msgidSql = '';
		if ($messagesid > 0) {
			$msgidSql = "AND {$table_prefix}_messages.messagesid = $messagesid";
		}
		
		if ($user->column_fields['receive_public_talks'] == '1') {
			$query1 = "SELECT {$table_prefix}_modcomments.related_to AS \"ID\", {$table_prefix}_modcomments.modcommentsid
			FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			WHERE {$table_prefix}_crmentity.deleted = 0 $msgidSql AND {$table_prefix}_messages.deleted = 0 and visibility_comm = ? AND {$table_prefix}_crmentity.smownerid <> ? AND {$table_prefix}_modcomments.parent_comments = 0 AND {$table_prefix}_modcomments.related_to <> 0 AND {$table_prefix}_modcomments.related_to <> '' ";  // crmv@175523
			$params[] = 'All';
			$params[] = $user->id;
			$query2 = "SELECT {$table_prefix}_modcomments.related_to AS \"ID\", {$table_prefix}_modcomments.modcommentsid
			FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			INNER JOIN {$table_prefix}_modcomments_users ON {$table_prefix}_modcomments_users.$idCol = {$table_prefix}_modcomments.modcommentsid
			WHERE {$table_prefix}_crmentity.deleted = 0 $msgidSql AND {$table_prefix}_messages.deleted = 0 AND visibility_comm = ? AND {$table_prefix}_modcomments_users.{$userCol} = ? AND {$table_prefix}_modcomments.parent_comments = 0 AND {$table_prefix}_modcomments.related_to <> 0 AND {$table_prefix}_modcomments.related_to <> '' "; // crmv@175523
			$params[] = 'Users';
			$params[] = $user->id;			
			$query = "select t.$idCol, MIN(t.modcommentsid) AS \"modcommentsid\" from ($query1 union $query2) t GROUP BY $idCol";
		} else {
			$query = "SELECT {$table_prefix}_modcomments.related_to AS \"ID\", MIN({$table_prefix}_modcomments.modcommentsid) AS \"modcommentsid\"
			FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			INNER JOIN {$table_prefix}_modcomments_users ON {$table_prefix}_modcomments_users.$idCol = {$table_prefix}_modcomments.modcommentsid
			WHERE {$table_prefix}_crmentity.deleted = 0 AND $msgidSql {$table_prefix}_messages.deleted = 0 AND visibility_comm = ? AND {$table_prefix}_modcomments_users.{$userCol} = ? AND {$table_prefix}_modcomments.parent_comments = 0 AND {$table_prefix}_modcomments.related_to <> 0 AND {$table_prefix}_modcomments.related_to <> '' 
			GROUP BY {$table_prefix}_modcomments.related_to"; // crmv@175523
			$params[] = 'Users';
			$params[] = $user->id;
		}
		$q = $adb->convert2Sql($query,$adb->flatten_array($params));
		//crmv@58931e crmv@60402e crmv@171021e

		// now insert into the table
		$q = "INSERT INTO {$table_prefix}_modcomments_msgrel (userid, messagesid) SELECT $userid as userid, tcomments.$idCol FROM ($q) tcomments";
		$adb->query($q);
	}
	
	/**
	 * Clean the Talks-Messages table for the specified user or messageid
	 */
	public function cleanCommentsMsgRelTable($userid = 0, $messagesid = 0) {
		global $adb, $table_prefix;
		
		$params = array();
		$wheres = array();
		$q = "DELETE FROM {$table_prefix}_modcomments_msgrel";
		
		if ($userid > 0) {
			$wheres[] = "userid = ?";
			$params[] = $userid;
		}
		if ($messagesid > 0) {
			$wheres[] = "messagesid = ?";
			$params[] = $messagesid;
		}
		if (count($wheres) > 0) {
			$q .= " WHERE ".implode(' AND ', $wheres);
		}
		
		$adb->pquery($q, $params);
	}
	// crmv@63349e

	function getRelatedModComments_tmp($return_query=false,$userid='') { // crmv@63349
		
		if (empty($userid)) {
			global $current_user;
			$user = $current_user;
		} else {
			$user = CRMEntity::getInstance('Users');
			$user->retrieveCurrentUserInfoFromFile($userid);
		}
		
		static $ids_msg_with_comments = array();
		static $ids_msg_with_comments_presence = array();
		if (empty($ids_msg_with_comments_presence[$user->id])) $ids_msg_with_comments_presence[$user->id] = false;
		if (!$return_query && $ids_msg_with_comments_presence[$user->id]) {
			return $ids_msg_with_comments[$user->id];
		}
		
		//crmv@171021 removed code
		
		global $adb, $table_prefix;
		//crmv@58931 crmv@60402 crmv@171021
		$params = Array();
		if($adb->isMssql()){
			$col_arr = array('user');
			$adb->format_columns($col_arr);
			$userCol = $col_arr[0];
		} else {
			$userCol = 'user';
		}	
		
		if ($user->column_fields['receive_public_talks'] == '1') {
			$query1 = "SELECT {$table_prefix}_modcomments.related_to AS \"id\" FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			WHERE {$table_prefix}_crmentity.deleted = 0 AND {$table_prefix}_messages.deleted = 0 and visibility_comm = ? AND {$table_prefix}_crmentity.smownerid <> ? group by {$table_prefix}_modcomments.related_to";
			$params[] = 'All';
			$params[] = $user->id;
			$query2="SELECT {$table_prefix}_modcomments.related_to AS \"id\" FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			INNER JOIN {$table_prefix}_modcomments_users ON {$table_prefix}_modcomments_users.id = {$table_prefix}_modcomments.modcommentsid
			WHERE {$table_prefix}_crmentity.deleted = 0 AND {$table_prefix}_messages.deleted = 0 AND visibility_comm = ? AND {$table_prefix}_modcomments_users.{$userCol} = ?  group by {$table_prefix}_modcomments.related_to";
			$params[] = 'Users';
			$params[] = $user->id;			
			$query = "select id from ($query1 union $query2) t group by id";
		} else {
			$query="SELECT {$table_prefix}_modcomments.related_to AS \"id\" FROM {$table_prefix}_modcomments
			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
			INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages.messagesid = {$table_prefix}_modcomments.related_to
			INNER JOIN {$table_prefix}_modcomments_users ON {$table_prefix}_modcomments_users.id = {$table_prefix}_modcomments.modcommentsid
			WHERE {$table_prefix}_crmentity.deleted = 0 AND {$table_prefix}_messages.deleted = 0 AND visibility_comm = ? AND {$table_prefix}_modcomments_users.{$userCol} = ?  group by {$table_prefix}_modcomments.related_to";
			$params[] = 'Users';
			$params[] = $user->id;				
		}
		//crmv@58931e crmv@60402e crmv@171021e
		if ($return_query) {
			return $adb->convert2Sql($query,$adb->flatten_array($params));
		}
		$result = $adb->pquery($query,$params);
		$tmp = array();
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$tmp[] = $row['id'];
			}
		}
		
		$ids_msg_with_comments[$user->id] = $tmp;
		$ids_msg_with_comments_presence[$user->id] = true;
		
		return $tmp;
	}

	function magicHTML($body, $uid, $data='') {
		$squirrelmail = new Squirrelmail($this,true);
		// quick and dirty trick to remove scripts, because the method magicHTML is buggy
		// it removes everything between 2 tags, but without checking if they are closed in the
		// middle
		$body = preg_replace('/<script\s.*?<\/script>/i', '', $body);
		$html = $squirrelmail->magicHTML($body, $uid, $data, $this->folder, false);	//TODO ultimo parametro per convertire i mailto:... in link interni
		$content_ids = $squirrelmail->getContentIds();
		return array('html'=>$html,'content_ids'=>$content_ids);
	}

	// crmv@49398
	function saveCleanedBody($messageid, $body, $content_ids = array()) {
		global $adb, $table_prefix;
		$messageid = intval($messageid);
		if (is_array($content_ids)) $content_ids = Zend_Json::encode($content_ids);
		$adb->pquery("update {$this->table_name} set content_ids = ? where {$this->table_index} = ?", array($content_ids, $messageid));
		$adb->updateClob($this->table_name,'cleaned_body',"{$this->table_index} = $messageid",$body);
	}
	// crmv@49398e
	
	//crmv@59097 crmv@120786
	function stripHTML($str) {
		
		$str = preg_replace('/(<|>)\1{2}/is', '', $str);
		$str = preg_replace(
		    array(// Remove invisible content
		        '@<head[^>]*?>.*?</head>@siu',
		        '@<style[^>]*?>.*?</style>@siu',
		        '@<script[^>]*?.*?</script>@siu',
		        '@<noscript[^>]*?.*?</noscript>@siu',
		        ),
		    "", //replace above with nothing
		    $str );
		    
		// remove inline styles so next replacements work better
		// doesn't work... why??
		//$str = preg_replace('/(<[^>]+)style=[\'"].*?[\'"]/siu', '\1', $str);
		
		// add some newlines where needed
		$str = preg_replace('#<br\s*/?>\s*#i', "<br>\n", $str);
		$str = preg_replace('#<hr\s*/?>\s*#i', str_repeat('-', 40)."\n", $str);
		$str = preg_replace('#</p>\s*#i', "</p>\n", $str);
		$str = preg_replace('#(<li[^>]*>)#i', '\1 * ', $str);
		$str = preg_replace('#</li>\s*#i', "</li>\n", $str);
		$str = preg_replace('#</[ou]l>\s*#i', "</ul>\n", $str);
		$str = preg_replace('#(</h[1-6]>)#i', '\1'."\n", $str);

		$str = strip_tags($str);
		
		// convert basic entitites
		$str = str_replace(array('&nbsp;', '&#39;', '&quot;'), array(' ', "'", '"'), $str);
		
		// convert Win newlines to unix style
		$str = str_replace("\r\n", "\n", $str);
		
		// collapse empty lines
		$str = preg_replace('/^[ \t]+$/m', '', $str);
		
		// no more than 2 consecutive new lines
		$str = preg_replace("/[\n\r]{3,}/", "\n\n", $str);
		
		// and collapse multiple spaces
		$str = preg_replace("/[\t ]{2,}/", " ", $str);
		
		// remove initial and final spaces
		$str = trim($str);
		
		return $str;
	}
	//crmv@59097e crmv@120786e

	function getRecipientEmails($field='') {
		if (empty($field)) {
			$fields = array('mto','mcc','mbcc');
		} else {
			$fields = array($field);
		}
		$recipients = array();
		foreach ($fields as $field) {
			//crmv@128409 crmv@191584
			$parsed_array = $this->parseAddressList($this->column_fields[$field.'_f']);
			$emails = array();
			foreach($parsed_array as $mail_arr){
				$email = $this->cleanEmail($mail_arr['email']);
				(!empty($mail_arr['name'])) ? $name = $mail_arr['name'] : $name = $email;
				$recipients[$field][$name] = $email;
			}
			//crmv@128409e crmv@191584e
		}
		return $recipients;
	}

	//crmv@128409 crmv@141432
	/**
	 * Parse an email address list according to RFC822
	 */
	function parseAddressList($addressList){
		if (!class_exists('\Zend\Loader\StandardAutoloader', false)) {
			$this->loadZendFramework();
		}
		
		$result = array();
		try {
			$h = \Zend\Mail\Header\Cc::fromString('Cc: '.$addressList);
		} catch (Exception $e) {
			return $result;
		}
		$list = $h->getAddressList();
		foreach ($list as $addr) {
			$result[] = array(
				'name' => $addr->getName(),
				'email' => $addr->getEmail()
			);
		}
		return $result;
	}
	//crmv@128409e crmv@141432e

	function getAddressName($email,$name,$full,$textlength_check=false) {
		global $default_charset;
		$name = html_entity_decode($name, ENT_QUOTES, $default_charset);
		$name = trim(trim($name),'"');
		if (empty($name)) {
			$name = trim($email);
		}
		if (substr_count($full,',') > substr_count($name, ',')) { // crmv@111982 crmv@152633
			$name = trim($full);
		}
		$name = strip_tags($name); // crmv@196013
		if ($textlength_check) {
			global $listview_max_textlength;
			$listview_max_textlength_tmp = $listview_max_textlength;
			$listview_max_textlength = 30;
			$name = textlength_check($name);
			$listview_max_textlength = $listview_max_textlength_tmp;
		}
		return $name;
	}

	// crmv@192843
	function getAddressImage($mode,$email,$business_card) {
		if (strpos($email,', ') !== false) {
			$img = "<i class='vteicon md-lg'>people</i>";
			return $img;
		}
		$module = $business_card['module'];
		$id = $business_card['id'];
		$name = $business_card['name'];
		$type = getSingleModuleName($module);
		switch ($module) {
			case 'Users':
				$avatar = getUserAvatar($id);
				$img = "<img title='$type' alt='$name' src='$avatar' border='0' class='userAvatar'>";
				break;
			case 'Contacts':
			case 'Accounts':
			case 'Leads':
			case 'Vendors':
				$img = "<i class='icon-module icon-".strtolower($module)." md-lg' data-first-letter='".getTranslatedModuleFirstLetter($module)."' title='$type'></i>";
				break;
			default:
				if ($mode == 'addsender') {
					$title = getTranslatedString('Add sender','Messages');
				} elseif ($mode == 'addrecipient') {
					$title = getTranslatedString('Add recipient','Messages');
				}
				$img = "<a href='javascript:;' onClick=\"LPOP.openPopup('Messages', '{$this->id}','{$mode}');\"><i class='vteicon md-lg'>person_add</i></a>"; // crmv@43864
				break;
		}
		return $img;
	}
	// crmv@192843e

	function saveCache($ids) {

		if (empty($ids)) return;

		global $adb, $table_prefix;
		global $current_user, $default_charset;

		$filtered = array();	// apply filters
		$crmid = array();
		$skipped_uids = array();
		$err_uids = array();	//crmv@50124
		foreach ($ids as $messageId => $uid) {
			/*
			$uid = self::$mail->getUniqueId($messageId);
			$uid = 17130;
			$messageId = self::$mail->getNumberByUniqueId($uid);
			*/
			//crmv@62140
			if (empty(self::$mail)) {
				$this->resetMailResource();
				$this->getZendMailStorageImap();
				$this->selectFolder($this->folder);
			}
			//crmv@62140e
			try {
				$message = self::$mail->getMessage($messageId);
			} catch (Zend\Mail\Exception\RuntimeException $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				// ignore parse errors and continue
				//crmv@50124 if error status remain 2 -- $skipped_uids[] = $uid;
				//echo "$messageId($uid),";
				$error_message = $e->getMessage();
				if ($error_message == 'unique id not found') {
					$skipped_uids[] = $uid;
				} elseif ($error_message == 'the single id was not found in response') {
					$err_uids[] = $uid;
				}
				continue;
			}
			/*
			if ($uid == 5545) {
				echo '<pre>';
				print_r($message);
				echo '</pre>';
				die;
			}
			*/
			
			//crmv@57876	crmv@59492
			/*
			$memory_usage = memory_get_usage();
			try {
				$message_size = $message->getSize();
			} catch (Zend\Mail\Exception\RuntimeException $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$message_size = 0;
			}
			if ($message_size > $memory_usage) {
				// error status remain 2 but there isn't fatal error
				continue;
			}
			*/
			//crmv@57876e	crmv@59492e
			
			//echo "$current_user->id,$this->account,$this->folder,$uid\n";
			(empty($this->interval_inline_cache)) ? $include_attach_content = false : $include_attach_content = true;	//crmv@125629
			$data = $this->getMessageData($message,$messageId,$include_attach_content);	//crmv@59094 crmv@125629
			if (empty($data)) {
				//crmv@50124 if error status remain 2 -- $skipped_uids[] = $uid;
				continue;
			}
			
			// crmv@64178e
			// skip the message if no messageid is present
			if (empty($data['header']['Messageid'])) {
				$err_uids[] = $uid;
				continue;
			}
			// crmv@64178e
			
			$date = $this->getImap2DbDate($data);
			
			//crmv@OPER8279 check date if is out of range
			if (!empty($this->interval_storage) && ($_REQUEST['service'] == 'Messages' || $_REQUEST['file'] == 'Fetch')) {
				$interval_storage = $this->getIntervalStorage();
				$limit_storage_time = $interval_storage['time'];
				if (strtotime($date) < $limit_storage_time) continue;
			}
			//crmv@OPER8279e

			// check if exists
			$existingCrmid = 0;
			//crmv@81338
			$query = "select messagesid, xuid from {$this->table_name} where deleted = 0 and mtype = ? and messageid = ? and smownerid = ? and account = ? and folder = ? and subject = ?"; //crmv@171021
			$params = array('Webmail',$data['header']['Messageid'],$current_user->id,$this->account,$this->folder,$data['header']['Subject']);
			if (!empty($date) && substr($date,0,10) != '1970-01-01') {
				$query .= " and mdate = ?";
				$params[] = $date;
			}
			$res = $adb->pquery($query, $params);
			//crmv@81338e
			if ($res && $adb->num_rows($res) > 0) {
				$existingCrmid = $adb->query_result_no_html($res, 0, 'messagesid');
				$existingUid = $adb->query_result_no_html($res, 0, 'xuid');
				//crmv@59094
				if ($this->update_duplicates && $existingCrmid > 0) {
					// do nothing
				} elseif ($existingCrmid > 0) {	//crmv@57585
					// crmv@90388 - set as error, not skipped, otherwise they are downloaded again nex time
					//$skipped_uids[] = $uid;
					$err_uids[] = $uid;
					// crmv@90388e
					continue;
				} elseif ($existingUid >= $uid) {	// if lower/equal uid, skip - crmv@58645
					//crmv@50124
					// set status = 1 : don't check again
					$err_uids[] = $uid;
					//crmv@50124e
					continue;
				}
				//crmv@59094e
			}
			
			$body = $this->getImap2DbBody($data);

			// crmv@68357
			if (!empty($data['text/calendar'])) {
				$data['icals'] = $data['text/calendar'];
			}
			// crmv@68357e
			
			if ($data['header']['Content-Class'] == 'VTECRM-DRAFT') $data['flags']['draft'] = 'on';	//crmv@84628

			$focus = CRMentity::getInstance('Messages');
			$focus->column_fields = array(
				'subject'=>$data['header']['Subject'],
				'description'=>$body,
				'mdate'=>$date,

				'mfrom'=>$data['header']['From']['email'],
				'mfrom_n'=>$data['header']['From']['name'],
				'mfrom_f'=>$data['header']['From']['full'],

				'mto'=>$data['header']['To']['email'],
				'mto_n'=>$data['header']['To']['name'],
				'mto_f'=>$data['header']['To']['full'],

				'mcc'=>$data['header']['Cc']['email'],
				'mcc_n'=>$data['header']['Cc']['name'],
				'mcc_f'=>$data['header']['Cc']['full'],

				'mbcc'=>$data['header']['Bcc']['email'],
				'mbcc_n'=>$data['header']['Bcc']['name'],
				'mbcc_f'=>$data['header']['Bcc']['full'],

				'mreplyto'=>$data['header']['ReplyTo']['email'],
				'mreplyto_n'=>$data['header']['ReplyTo']['name'],
				'mreplyto_f'=>$data['header']['ReplyTo']['full'],

				'messageid'=>$data['header']['Messageid'],
				'in_reply_to'=>$data['header']['In-Reply-To'],
				'mreferences'=>$data['header']['References'],
				'thread_index'=>$data['header']['Thread-Index'],
				'xmailer'=>$data['header']['Xmailer'],
				'xuid'=>$uid,
				'account'=>$this->account,
				'folder'=>self::$mail->getCurrentFolder(),
				'seen'=>$data['flags']['seen'],
				'answered'=>$data['flags']['answered'],
				'flagged'=>$data['flags']['flagged'],
				'forwarded'=>$data['flags']['forwarded'],
				'draft'=>$data['flags']['draft'],	//crmv@84628

				'assigned_user_id'=>$current_user->id,
				'mtype'=>'Webmail',

				'other'=>$data['other'],
				'icals'=>$data['icals'], // crmv@68357
			);
			
			$outOfOffice = $focus->outOfOfficeReply(); // crmv@191351

			// apply filters
			$filtered_status = $focus->applyFilters($messageId,$filtered);
			if ($filtered_status) {
				$skipped_uids[] = $uid;
				continue;
			}

			//crmv@63453
			$retrySave = false;
			try {	//crmv@44482
				if ($existingCrmid > 0) {
					$focus->id = $existingCrmid;
					$focus->mode = 'edit';
				}
				$dieOnErrorTmp = $adb->dieOnError; $adb->dieOnError = false;
				$focus->save('Messages', true);
				$adb->dieOnError = $dieOnErrorTmp;
				$crmid[] = $focus->id;
			} catch (Exception $e) {
				//ERR_SAVING_IN_DB
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				$retrySave = true;
			}
			if ($retrySave) {
				try {
					$adb = new PearDatabase();
					$adb->connect();
					$columns = $focus->column_fields;
					$focus = CRMentity::getInstance('Messages');
					$focus->column_fields = $columns;
					$focus->column_fields['description'] = substr($focus->column_fields['description'],0,50000);
					if ($existingCrmid > 0) {
						$focus->id = $existingCrmid;
						$focus->mode = 'edit';
					}
					$focus->save('Messages');
					$crmid[] = $focus->id;
				} catch (Exception $e) {
					//ERR_SAVING_IN_DB
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				}
			}
			//crmv@63453e
			// crmv@191351
			if ($outOfOffice !== false && !empty($focus->id)) {
				$adb->pquery("update {$table_prefix}_messages_outofo_q set in_reply_to = ? where id = ?", array($focus->id, $outOfOffice));
			}
			// crmv@191351e
		}
		$this->setSavedMessages($crmid);
		$this->setSkippedUids($skipped_uids);
		$this->setErrUids($err_uids);	//crmv@50124
		$this->fetchFiltered($filtered);	// apply filters
	}
	function getImap2DbDate($data) {
		$date = $this->imap2DbDate($data['header']['Date']);
		if ((empty($date) || substr($date,0,10) == '1970-01-01') && !empty($data['header']['X-MDArrival-Date'])) {
			$date = $this->imap2DbDate($data['header']['X-MDArrival-Date']);
		}
		if ((empty($date) || substr($date,0,10) == '1970-01-01') && !empty($data['header']['Delivery-Date'])) {
			$date = $this->imap2DbDate($data['header']['Delivery-Date']);
		}
		return $date;
	}
	function getImap2DbBody($data) {
		global $default_charset;
		$body = '';
		if (isset($data['text/html'])) {
			$body = $data['text/html'];
			$body = str_replace('&lt;','&amp;lt;',$body);
			$body = str_replace('&gt;','&amp;gt;',$body);
		} elseif (isset($data['text/plain'])) {
			$body = nl2br(htmlentities($data['text/plain'], ENT_COMPAT, $default_charset));
		}
		$body = preg_replace('/[\xF0-\xF7].../s', '', $body);	//crmv@65555
		return $body;
	}

	function setSavedMessages($crmid) {
		if (!empty($this->saved_messages)) {
			$this->saved_messages = array_merge($this->saved_messages, $crmid);
		} else {
			$this->saved_messages = $crmid;
		}
	}

	function getSavedMessages() {
		return $this->saved_messages;
	}
	
	function setSkippedUids($uids) {
		if (!empty($this->skipped_uids)) {
			$this->skipped_uids = array_merge($this->skipped_uids, $uids);
		} else {
			$this->skipped_uids = $uids;
		}
	}

	function getSkippedUids() {
		return $this->skipped_uids;
	}
	
	//crmv@50124
	function setErrUids($uids) {
		if (!empty($this->err_uids)) {
			$this->err_uids = array_merge($this->err_uids, $uids);
		} else {
			$this->err_uids = $uids;
		}
	}

	function getErrUids() {
		return $this->err_uids;
	}
	//crmv@50124e

	function saveModComment($crmid,$messageid) {
		global $current_user;
		$focus = CRMEntity::getInstance('Messages');
		$focus->retrieve_entity_info_no_html($crmid,'Messages');
		if ($messageid == $focus->column_fields['messageid']) {
			if (isPermitted('ModComments', 'EditView', '') == 'yes') {
				$modObj = CRMEntity::getInstance('ModComments');
				$modObj->column_fields['commentcontent'] = vtlib_purify(strip_tags($_REQUEST['comment']));
				$modObj->column_fields['related_to'] = $crmid;
				$modObj->column_fields['visibility_comm'] = vtlib_purify($_REQUEST['ModCommentsMethod']);
				$modObj->column_fields['users_comm'] = vtlib_purify($_REQUEST['users_comm']);
				$modObj->column_fields['assigned_user_id'] = $current_user->id;
				$modObj->save('ModComments');
			}
		}
	}

	function saveCacheLink($column_fields) {
		global $current_user;

		$mailer = new VTEMailer(); // crmv@180739
		$uniq_id = md5(uniqid(time()));
		$messageid = sprintf('<%s@%s>', $uniq_id, $mailer->getHostname()); // in this way, we won't duplicate messages, crmv@180739

		$xuid = '';
		$mfrom = (!empty($column_fields['mfrom']) ? $column_fields['mfrom'] : '');
		$mto = (!empty($column_fields['mto']) ? $column_fields['mto'] : '');
		$mcc = (!empty($column_fields['mcc']) ? $column_fields['mcc'] : '');
		$mbcc = (!empty($column_fields['mbcc']) ? $column_fields['mbcc'] : '');
		$mreplyto = (!empty($column_fields['mreplyto']) ? $column_fields['mreplyto'] : '');

		if ($column_fields['mtype'] != 'Link'){
			$account = '';
			if ($column_fields['account'] !== '') {
				$account = $column_fields['account'];
			} elseif (!empty($mfrom)) {
				$focusEmails = CRMentity::getInstance('Emails');
				$account = $focusEmails->getFromEmailAccount($mfrom);
			}
			if ($account === '') {
				$main_account = $this->getMainUserAccount();
				$account = $main_account['id'];
			}
			$this->setAccount($account);
			$specialFolders = $this->getSpecialFolders($column_fields);
		}
		else{
			$specialFolders = Array('INBOX'=>'','Sent'=>'','Drafts'=>'','Trash'=>'');
			$account = $column_fields['account'];	//crmv@86304	crmv@80216
		}

		$focus = CRMentity::getInstance('Messages');
		// crmv@66378
		$focus->column_fields = array_merge($column_fields, array(
			'subject'=>(!empty($column_fields['subject']) ? $column_fields['subject'] : ''),
			'description'=>(!empty($column_fields['description']) ? $column_fields['description'] : ''),
			'mdate'=>(!empty($column_fields['mdate']) ? $column_fields['mdate'] : date('Y-m-d H:i:s')),
			'mfrom'=>$mfrom,
			'mfrom_n'=>(!empty($column_fields['mfrom_n']) ? $column_fields['mfrom_n'] : ''),
			'mfrom_f'=>(!empty($column_fields['mfrom_f']) ? $column_fields['mfrom_f'] : $mfrom),
			'mto'=>$mto,
			'mto_n'=>(!empty($column_fields['mto_n']) ? $column_fields['mto_n'] : ''),
			'mto_f'=>(!empty($column_fields['mto_f']) ? $column_fields['mto_f'] : $mto),
			'mcc'=>$mcc,
			'mcc_n'=>(!empty($column_fields['mcc_n']) ? $column_fields['mcc_n'] : ''),
			'mcc_f'=>(!empty($column_fields['mcc_f']) ? $column_fields['mcc_f'] : $mcc),
			'mbcc'=>$mbcc,
			'mbcc_n'=>(!empty($column_fields['mbcc_n']) ? $column_fields['mbcc_n'] : ''),
			'mbcc_f'=>(!empty($column_fields['mbcc_f']) ? $column_fields['mbcc_f'] : $mbcc),
			'mreplyto'=>$mreplyto,
			'mreplyto_n'=>(!empty($column_fields['mreplyto_n']) ? $column_fields['mreplyto_n'] : ''),
			'mreplyto_f'=>$mreplyto,
			'in_reply_to'=>(!empty($column_fields['in_reply_to']) ? $column_fields['in_reply_to'] : ''),
			'mreferences'=>(!empty($column_fields['mreferences']) ? $column_fields['mreferences'] : ''),
			'thread_index'=>(!empty($column_fields['thread_index']) ? $column_fields['thread_index'] : ''),
			'xmailer'=>(!empty($column_fields['xmailer']) ? $column_fields['xmailer'] : 'VTECRM-WEBMAIL'),
			'xuid'=>(!empty($column_fields['xuid']) ? $column_fields['xuid'] : $xuid),
			'messageid'=>(!empty($column_fields['messageid']) ? $column_fields['messageid'] : $messageid),
			'seen'=>(!empty($column_fields['seen']) ? $column_fields['seen'] : '1'),
			'answered'=>(!empty($column_fields['answered']) ? $column_fields['answered'] : '0'),
			'flagged'=>(!empty($column_fields['flagged']) ? $column_fields['flagged'] : '0'),
			'forwarded'=>(!empty($column_fields['forwarded']) ? $column_fields['forwarded'] : '0'),
			'folder'=>(!empty($column_fields['folder']) ? $column_fields['folder'] : $specialFolders['Sent']),
			'assigned_user_id'=>(!empty($column_fields['assigned_user_id']) ? $column_fields['assigned_user_id'] : $current_user->id),
			'mtype'=>(!empty($column_fields['mtype']) ? $column_fields['mtype'] : 'Link'),
			'mvisibility'=>(!empty($column_fields['mvisibility']) ? $column_fields['mvisibility'] : ''),
			'send_mode'=>(!empty($column_fields['send_mode']) ? $column_fields['send_mode'] : ''),
			'other'=>(!empty($column_fields['other']) ? $column_fields['other'] : ''),
			'parent_id'=>(!empty($column_fields['parent_id']) ? $column_fields['parent_id'] : ''),
			'recipients'=>(!empty($column_fields['recipients']) ? $column_fields['recipients'] : ''),
			'messagehash'=>(!empty($column_fields['messagehash']) ? $column_fields['messagehash'] : ''), // crmv@119358
			'account'=>$account,
		));
		// crmv@66378e
		$focus->save('Messages');
		return $focus->id;
	}

	function deleteCache($ids) {
		if (empty($ids)) return;
		foreach ($ids as $crmid => $uid) {
			if (!$this->isDuplicated($crmid) && $this->haveRelations($crmid,'','-')) {	// crmv@139797
				$this->convertToLink($crmid);	// crmv@139797
			} else {
				parent::trash('Messages', $crmid);
			}
		}
		$this->cleanDraftCache(array_keys($ids));
	}

	function trash($module, $id) {	// move to Trash
		if (empty($this->column_fields['folder']) || empty($this->column_fields['xuid']) || empty($this->column_fields['account'])) {
			$this->retrieve_entity_info($id,$module);
		}
		if ($this->column_fields['mtype'] == 'Webmail') {
			$this->cleanDraftCache($id);
			$specialFolders = $this->getSpecialFolders();
			if (!$this->isDuplicated($id) && $this->column_fields['folder'] == $specialFolders['Trash'] && $this->haveRelations($id,'','-')) {	// crmv@139797
				$this->convertToLink($id);	// crmv@139797
				
				$this->addToPropagationCron('trash', array(
					'userid'=>$this->column_fields['assigned_user_id'],
					'account'=>$this->column_fields['account'],
					'folder'=>$this->column_fields['folder'],
					'uid'=>$this->column_fields['xuid'],
					'fetch'=>false
				));
			} else {
				parent::trash($module, $id);
				$this->addToPropagationCron('trash', array(
					'userid'=>$this->column_fields['assigned_user_id'],
					'account'=>$this->column_fields['account'],
					'folder'=>$this->column_fields['folder'],
					'uid'=>$this->column_fields['xuid'],
					'fetch'=>true
				));
			}
		} else {
			parent::trash($module, $id);
		}
	}

	function propagateTrash($userid,$account,$folder,$uid,$fetch=false) {
		$focus = CRMEntity::getInstance('Messages');

		$focus->setAccount($account);
		$focus->getZendMailStorageImap($userid);
		$focus->selectFolder($folder);
        //crmv@204525
        try {
            $messageId = self::$mail->getNumberByUniqueId($uid);
        } catch(Exception $e) {
            if ($e->getMessage() == 'unique id not found') {
                return;
            }
        }
        //crmv@204525e
		
		$specialFolders = $focus->getSpecialFolders();
		if ($folder == $specialFolders['Trash'] || $folder == $specialFolders['Drafts']) {	//crmv@49923
			self::$mail->removeMessage($messageId);
		} else {
			self::$mail->moveMessage($messageId,$specialFolders['Trash']);
		}
		
		//fetch new messages from Trash folder
		if ($fetch) {
			global $current_user;
			$tmp = $current_user->id;
			$current_user->id = $userid;
			
			$focus->fetchNews($specialFolders['Trash']);
			
			$current_user->id = $tmp;
		}
	}
	
	// crmv@139797
	function convertToLink($messagesid) {
		global $adb, $table_prefix;
		$adb->pquery("update {$this->table_name} set mtype = ? where {$this->table_index} = ?",array('Link',$messagesid));
		$this->saveAllDocuments($messagesid);	//crmv@63475
		$this->beforeTrashFunctions($messagesid);
	}
	
	function isDuplicated($messagesid) {
		// check duplicates in the same account. ex. messages can be already moved in an other folder by an other client
		global $adb, $table_prefix;
		
		//crmv@171021
		$result = $adb->pquery("select messagehash, smownerid, account from {$this->table_name} where $this->table_index = ?", array($messagesid));
		$hash = $adb->query_result($result,0,'messagehash');
		$owner = $adb->query_result($result,0,'smownerid');
		$account = $adb->query_result($result,0,'account');
		//crmv@171021e
		
		$query = "select messagesid from {$this->table_name} where deleted = 0 and mtype = ? and messagehash = ? and smownerid = ? and account = ? and messagesid <> ?";
		$params = array('Webmail',$hash,$owner,$account,$messagesid);
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			// there is a duplicate!
			return true;
		}
		return false;
	}
	// crmv@139797e

	function cleanDraftCache($ids) {
		global $adb, $table_prefix, $current_user;
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		$draftids = array();
		$result = $adb->pquery("SELECT messagehash FROM {$this->table_name} WHERE messagesid IN (".generateQuestionMarks($ids).")",$ids);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$draftids[] = $row['messagehash'];
			}
			$adb->pquery("DELETE FROM {$table_prefix}_messages_drafts WHERE userid = ? AND messagehash IN (".generateQuestionMarks($draftids).")",array($current_user->id,$draftids));
		}
	}

	function markAsViewed($userid,$skip_update_flag='no') {
		parent::markAsViewed($userid);
		if ($skip_update_flag != 'yes') {
			$this->setFlag('seen',1);
		}
	}

	//crmv@44179
	function massSetFlag($flag,$value,$ids) {
		global $adb, $current_user, $current_account, $current_folder;
		if (!empty($ids) && is_array($ids)) {
			foreach ($ids as $id) {
				$this->addToPropagationCron('flag', array('id'=>$id,'flag'=>$flag,'value'=>$value));
			}
			if (!empty($ids)) {
				$adb->pquery("update {$this->table_name} set {$flag} = ? where messagesid in (".implode(',',$ids).") and {$flag} <> ?",array($value,$value));
			}
			if ($flag == 'seen') {
				$this->reloadCacheFolderCount($current_user->id,$current_account,$current_folder);
			} elseif ($flag == 'flagged') {
				$this->reloadCacheFolderCount($current_user->id,$current_account,'Flagged');
			}
		}
	}

	function setFlag($flag,$value) {
		global $adb, $table_prefix, $current_user;  // crmv@49398
		$status = false;
		if ($this->column_fields[$flag] != $value																					// if flag change
			&& !empty($this->column_fields['assigned_user_id']) && $current_user->id == $this->column_fields['assigned_user_id']) {	// if message is assigned to me (not in folder Shared)
			$this->addToPropagationCron('flag', array('id'=>$this->id,'flag'=>$flag,'value'=>$value));
			$adb->pquery("update {$this->table_name} set {$flag} = ?, modifiedtime = ? where messagesid = ?",array($value, $adb->formatDate(date('Y-m-d H:i:s'), true), $this->id)); // crmv@49398 crmv@69690 crmv@171021
			$this->column_fields[$flag] = $value;
			if ($flag == 'seen') {
				$this->reloadCacheFolderCount($this->column_fields['assigned_user_id'],$this->column_fields['account'],$this->column_fields['folder']);				
			} elseif ($flag == 'flagged') {
				$this->reloadCacheFolderCount($this->column_fields['assigned_user_id'],$this->column_fields['account'],'Flagged');				
			}
			$status = true;
		}
		return $status;
	}
	//crmv@44179e

	function propagateSetFlag($messagesid,$flag,$value) {
		global $adb, $table_prefix;
		
		$focus = CRMEntity::getInstance('Messages');
		$error = $focus->retrieve_entity_info($messagesid,'Messages',false);
		if (!empty($error)) {
			throw new Exception($error);
		}
		$focus->id = $messagesid;
		
		$focus->resetMailResource();
		$focus->getZendMailStorageImap($focus->column_fields['assigned_user_id']);
		$focus->selectFolder($focus->column_fields['folder']);
        //crmv@204525
        try {
            $messageId = self::$mail->getNumberByUniqueId($focus->column_fields['xuid']);
        } catch(Exception $e) {
            if ($e->getMessage() == 'unique id not found') {
                return;
            }
        }
        //crmv@204525e

		//Get current flags with server call
		$message = self::$mail->getMessage($messageId);
		$current_flags = $flags = $message->getFlags();
		//Get current flags using cached flags
		//$current_flags = $flags = $focus->getCacheFlags();	//need $focus->id

		switch ($flag) {
			case 'seen':
				if ($value == '1') {
					$flags = array_merge($flags,array(Zend\Mail\Storage::FLAG_SEEN));
				} elseif ($value == '0') {
					unset($flags[array_search(Zend\Mail\Storage::FLAG_SEEN, $flags)]);	//crmv@49432
				}
				break;
			case 'flagged':
				if ($value == '1') {
					$flags = array_merge($flags,array(Zend\Mail\Storage::FLAG_FLAGGED));
				} elseif ($value == '0') {
					unset($flags[array_search(Zend\Mail\Storage::FLAG_FLAGGED, $flags)]);	//crmv@49432
				}
				break;
			case 'answered':
				if ($value == '1') {
					$flags = array_merge($flags,array(Zend\Mail\Storage::FLAG_ANSWERED));
				}
				break;
			case 'forwarded':
				if ($value == '1') {
					$flags = array_merge($flags,array('$Forwarded','Forwarded'));
				}
				break;
		}
		if ($current_flags != $flags) {
			try {
				self::$mail->setFlags($messageId, $flags);
				return true;
			} catch (Zend\Mail\Storage\Exception\RuntimeException $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				//crmv@49432
				if (empty($flags))
					return self::$protocol->store($current_flags, $messageId, null, '-');
				else
				//crmv@49432e
					throw new Exception($e->getMessage());
			}
		}
	}

	function getPreviewBody($rawValue) {
		global $default_charset, $listview_max_textlength, $current_user;
		$listview_max_textlength_tmp = $listview_max_textlength;
		$listview_max_textlength = 120;

		$temp_val = preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$rawValue);
		$temp_val = str_replace('&nbsp;',' ',$temp_val);
		$temp_val = html_entity_decode($temp_val, ENT_QUOTES, $default_charset);

		$search = array(
			'@<title[^>]*?>.*?</title>@si',		// Strip out title tag
			'@<script[^>]*?>.*?</script>@si',	// Strip out javascript
			'@<style[^>]*?>.*?</style>@siU',	// Strip style tags properly
		);
		$temp_val = preg_replace($search, "\n", $temp_val);

		$temp_val = preg_replace('/\s+/',' ',strip_tags($temp_val));
		$value = textlength_check($temp_val);
		$listview_max_textlength = $listview_max_textlength_tmp;

		return $value;
	}

	function cleanEmail($email) {
		if (strpos($email,'<') !== false) {
			$email = substr($email,strpos($email,'<')+1);
		}
		if (strpos($email,'>') !== false) {
			$email = substr($email,0,strpos($email,'>'));
		}
		return trim($email);
	}

	// crmv@107655
	function getBusinessCard($type) {
		// crmv@111982
		if ($type == 'TO') {
			$email = $this->column_fields['mto'];
			$name = $this->column_fields['mto_n'];
			$full = $this->column_fields['mto_f'];
		} elseif ($type == 'FROM') {
			$email = $this->column_fields['mfrom'];
			$name = $this->column_fields['mfrom_n'];
			$full = $this->column_fields['mfrom_f'];
		} elseif ($type == 'CC') {
			$email = $this->column_fields['mcc'];
			$name = $this->column_fields['mcc_n'];
			$full = $this->column_fields['mcc_f'];
		}
		if (substr_count($full,',') > substr_count($email,',') + substr_count($name,',')) {
			$email = trim($full);
		}
		// crmv@111982e
		if (strpos($email,',') !== false) {
			$emails = explode(',',$email);
		} else {
			$emails = array($email);
		}
		$entitiesInfo = array();
		if (!empty($emails)) {
			foreach($emails as $email) {
				$email = $this->cleanEmail($email);
				$entityid = '';
				$entity = $this->getEntitiesFromEmail($email,false,false,array('Contacts','Accounts','Leads','Vendors','Users'),true);
				if (!empty($entity)) {
					$entityid = $entity['crmid'];
					$entitytype = $entity['module'];
				}
				$entityInfo = array();
				if (!empty($entityid)) {
					$retrieveFields = array();
					if ($entitytype == 'Contacts') {
						$retrieveFields = array('account_id', 'salutationtype', 'mobile', 'phone', 'homephone', 'otherphone');
					} elseif ($entitytype == 'Accounts') {
						$retrieveFields = array('bill_city', 'phone', 'otherphone');
					} elseif ($entitytype == 'Leads') {
						$retrieveFields = array('company', 'leadsource', 'mobile', 'phone');
					} elseif ($entitytype == 'Vendors') {
						$retrieveFields = array('website', 'phone');
					} elseif ($entitytype == 'Users') {
						$retrieveFields = array('title', 'department', 'phone_mobile', 'phone_work', 'phone_home', 'phone_other');
					}
					if (count($retrieveFields) > 0) {
						$skypeFields = $this->getUitypeFields($entitytype, 85);	// skype uitype
						if (!empty($skypeFields)) {
							foreach ($skypeFields as $sfield) $retrieveFields[] = $sfield['name'];
							$retrieveFields = array_unique($retrieveFields);
						}
					}
					
					$entityFocus = CRMEntity::getInstance($entitytype);
					$entityFocus->id = $entityid;
					$error = $entityFocus->retrieve_entity_info($entityid,$entitytype,false, $retrieveFields);
					if ($entitytype == 'Users') $error = '';
					if (!empty($error)) continue;
					$entityName = $entityFocus->getRecordName();	//crmv@104310
					if ($entitytype == 'Users') {
						$entityInfo = array(
							'module'=>$entitytype,
							'id'=>$entityid,
							'name'=>getUserFullName($entityid),
							'title'=>implode(' - ',array_filter(array($entityFocus->column_fields['title'],$entityFocus->column_fields['department']))),
						);
						$phone = array(
							'phone_mobile'=>array('label'=>getTranslatedString('Mobile',$entitytype),'value'=>$entityFocus->column_fields['phone_mobile']),
							'phone_work'=>array('label'=>getTranslatedString('Office Phone',$entitytype),'value'=>$entityFocus->column_fields['phone_work']),
							'phone_home'=>array('label'=>getTranslatedString('Home Phone',$entitytype),'value'=>$entityFocus->column_fields['phone_home']),
							'phone_other'=>array('label'=>getTranslatedString('Other Phone',$entitytype),'value'=>$entityFocus->column_fields['phone_other']),
						);
					} elseif ($entitytype == 'Contacts') {
						$accName = '';
						if (!empty($entityFocus->column_fields['account_id'])) {
							$accEntityName = getEntityName('Accounts',$entityFocus->column_fields['account_id']);
							if (!empty($accEntityName)) {
								$accName = array_values($accEntityName);
								$accName = $accName[0];
							}
						}
						$salutationtype = ''; // crmv@184459
						if ($entityFocus->column_fields['salutationtype'] != '--None--') {
							$salutationtype = getTranslatedString($entityFocus->column_fields['salutationtype'],$entitytype);
						}
						$entityInfo = array(
							'module'=>$entitytype,
							'id'=>$entityid,
							'name'=>implode(' ',array_filter(array($salutationtype,$entityName))),
							'accountid'=>$entityFocus->column_fields['account_id'],
							'accountname'=>$accName,
						);
						$phone = array(
							'mobile'=>array('label'=>getTranslatedString('Mobile',$entitytype),'value'=>$entityFocus->column_fields['mobile']),
							'phone'=>array('label'=>getTranslatedString('Office Phone',$entitytype),'value'=>$entityFocus->column_fields['phone']),
							'homephone'=>array('label'=>getTranslatedString('Home Phone',$entitytype),'value'=>$entityFocus->column_fields['homephone']),
							'otherphone'=>array('label'=>getTranslatedString('Other Phone',$entitytype),'value'=>$entityFocus->column_fields['otherphone']),
						);
					} elseif ($entitytype == 'Accounts') {
						$entityInfo = array(
							'module'=>$entitytype,
							'id'=>$entityid,
							'name'=>$entityName,
							'bill_city'=>$entityFocus->column_fields['bill_city'],
						);
						$phone = array(
							'phone'=>array('label'=>getTranslatedString('Phone',$entitytype),'value'=>$entityFocus->column_fields['phone']),
							'otherphone'=>array('label'=>getTranslatedString('Other Phone',$entitytype),'value'=>$entityFocus->column_fields['otherphone']),
						);
					} elseif ($entitytype == 'Leads') {
						$entityInfo = array(
							'module'=>$entitytype,
							'id'=>$entityid,
							'name'=>$entityName,
							'company'=>$entityFocus->column_fields['company'],
							'leadsource'=>$entityFocus->column_fields['leadsource'],
						);
						$phone = array(
							'mobile'=>array('label'=>getTranslatedString('Mobile',$entitytype),'value'=>$entityFocus->column_fields['mobile']),
							'phone'=>array('label'=>getTranslatedString('Phone',$entitytype),'value'=>$entityFocus->column_fields['phone']),
						);
					} elseif ($entitytype == 'Vendors') {
						$entityInfo = array(
							'module'=>$entitytype,
							'id'=>$entityid,
							'name'=>$entityName,
							'website'=>$entityFocus->column_fields['website'],
						);
						$phone = array(
							'phone'=>array('label'=>getTranslatedString('Phone',$entitytype),'value'=>$entityFocus->column_fields['phone']),
						);
					}
					// check for skype fields
					if ($entityInfo && !empty($skypeFields)) {
						if (!is_array($phone)) $phone = array();
						foreach ($skypeFields as $sfield) {
							$value = trim($entityFocus->column_fields[$sfield['name']]);
							if (!empty($value)) {
								$phone['skype'] = array('label'=>$sfield['label'],'value'=>$value);
								// take the first valid one
								break;
							}
						}
					}
					if (!empty($phone)) {
						foreach ($phone as $k => $v) {
							if (empty($v['value'])) {
								unset($phone[$k]);
							}
						}
					}
					$entityInfo['phone'] = $phone;
					$entityInfo['module_permitted'] = (isPermitted($entitytype, 'DetailView', $entityid) == 'yes');
				}
				$entityInfo['email'] = $email;
				$entitiesInfo[] = $entityInfo;
			}
		}
		return $entitiesInfo;
	}
	
	/**
	 * Return the field names of all the field of the specified uitypes
	 */
	protected function getUitypeFields($module, $uitypes) {
		global $current_user;
		
		$fields = array();
		if (!is_array($uitypes)) $uitypes = array($uitypes);
		
		$RC = RCache::getInstance();
		$allFields = $RC->get('ws_fields_mod_'.$module);
		
		if ($allFields == null) {
			require_once('include/Webservices/DescribeObject.php');
			try {
				$modinfo = vtws_describe($module, $current_user);
				$allFields = $modinfo['fields'];
			} catch (Exception $e) {
				// ignore errors and skip the fields
				$allFields = array();
			}
			$RC->set('ws_fields_mod_'.$module, $allFields);
		}
		
		if ($allFields && is_array($allFields)) {
			foreach ($allFields as $field) {
				if (in_array($field['uitype'], $uitypes)) {
					$fields[] = $field;
				}
			}
		}
		
		return $fields;
	}
	// crmv@107655e

	function getLuckyMessage($account,$folder,$record='') {
		global $adb, $table_prefix, $current_user;
		$id = '';
		$query = "SELECT messagesid FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND seen = ?"; //crmv@171021
		$params = array($current_user->id,1);
		if ($account == 'all') {
			// crmv@192843
			if (in_array($folder,array_keys($this->defaultSpecialFolders))) {
				$folders = $this->getAllSpecialFolders($folder);
				$tmp = array();
				foreach($folders as $account => $account_folders) {
					$tmp[] = "(account = ? AND folder = ?)"; //crmv@171021
					$params[] = array($account,$account_folders[$folder]);
				}
				$query .= ' AND ('.implode(' OR ',$tmp).')';
			} elseif (in_array($folder,$this->fakeFolders)) {
				if ($folder == 'Shared') {
					return ''; // TODO
				} elseif ($folder == 'Links') {
					$query .= ' AND mtype = ? AND folder <> ?';
					$params[] = array('Link','vteScheduled');
				} elseif ($folder == 'Flagged') {
					$query .= ' AND mtype = ? AND flagged = ?';
					$params[] = array('Webmail',1);
				} elseif ($folder == 'vteScheduled') {
					$query .= ' AND mtype = ? AND folder = ?';
					$params[] = array('Link','vteScheduled');
				}
			}
			// crmv@192843e
		} else {
			$query .= " AND account = ? AND folder = ?";
			$params[] = $account;
			$params[] = $folder;
		}
		if (!empty($record)) {
			if (!is_array($record)) {
				$record = array($record);
			}
			$result = $adb->pquery("SELECT messagesid, mdate FROM {$table_prefix}_messages WHERE messagesid IN (".generateQuestionMarks($record).")",$record);
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					if (!empty($row['mdate'])) {
						$query .= "	AND (messagesid <> ? AND mdate <= ?)";
						$params[] = $row['messagesid'];
						$params[] = $row['mdate'];
					}
				}
			}
		}
		$query .= "	ORDER BY mdate DESC";
		$result = $adb->limitPquery($query,0,1,$params);
		if ($result && $adb->num_rows($result) > 0) {
			$id = $adb->query_result($result,0,'messagesid');
		}
		return $id;
	}

	// crmv@48677
	function haveAttachments($id, $excludeDisposition = array()) {	//crmv@65648 
		global $adb,$table_prefix;

		$params = array($id,$this->other_contenttypes_attachment);

		if (!is_array($excludeDisposition)) $excludeDisposition = array_filter(array($excludeDisposition));
		if (count($excludeDisposition) > 0) {
			$dispQuery = " AND contentdisposition not in (".generateQuestionMarks($excludeDisposition).")";
			$params[] = $excludeDisposition;
		} else {
			$dispQuery = '';
		}

		$query = "select messagesid
			from {$this->table_name}_attach
			where {$this->table_index} = ?
			and (
				contenttype IN (".generateQuestionMarks($this->other_contenttypes_attachment).")
				OR (contentdisposition IS NOT NULL $dispQuery)
			)";
		$result = $adb->pquery($query,$params);

		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}

	function getAttachments($excludeDisposition = array()) {	//crmv@65648 
		global $adb,$table_prefix;

		$attachments = array();

		$params = array($this->id,$this->other_contenttypes_attachment);

		if (!is_array($excludeDisposition)) $excludeDisposition = array_filter(array($excludeDisposition));
		if (count($excludeDisposition) > 0) {
			$dispQuery = " AND contentdisposition not in (".generateQuestionMarks($excludeDisposition).")";
			$params[] = $excludeDisposition;
		} else {
			$dispQuery = '';
		}

		// crmv@68357 - if there is an embedded invitation/reply in ical format, don't show it as attachment unless it has a filename
		$icalExcludeSql = " AND NOT (contentmethod IN (".generateQuestionMarks($this->ical_methods).") AND contenttype = 'text/calendar' AND (contentname IS NULL OR contentname = 'Unknown'))";
		$params[] = $this->ical_methods;
		$query = "select *
			from {$this->table_name}_attach
			where {$this->table_index} = ?
			and (
				contenttype IN (".generateQuestionMarks($this->other_contenttypes_attachment).")
				OR (contentdisposition IS NOT NULL $dispQuery)
			) $icalExcludeSql";
		// crmv@68357e
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				// crmv@88997
				if (empty($row['document'])) {	// search if the same attach is already saved
					$query1 = "SELECT {$this->table_name}_attach.* FROM {$this->table_name}_attach
						INNER JOIN {$this->table_name} ON {$this->table_name}_attach.{$this->table_index} = {$this->table_name}.{$this->table_index}
						INNER JOIN {$table_prefix}_crmentity ON setype = ? AND document = {$table_prefix}_crmentity.crmid
						WHERE {$table_prefix}_crmentity.deleted = 0 AND messagehash = ? AND {$this->table_name}.{$this->table_index} <> ? AND document IS NOT NULL AND document > 0
						AND contentid = ?";
					$params1 = array('Documents', $this->column_fields['messagehash'], $this->id, $row['contentid']);
					if ($row['content_id'] == null) {
						$query1 .= ' AND content_id IS NULL';
					} else {
						$query1 .= ' AND content_id = ?';
						$params1[] = $row['content_id'];
					}
					if ($row['contentname'] == null) {
						$query1 .= ' AND contentname IS NULL';
					} else {
						$query1 .= ' AND contentname = ?';
						$params1[] = $row['contentname'];
					}
					$result1 = $adb->pquery($query1, $params1);
					if ($result1 && $adb->num_rows($result1) > 0) {
						$row['document'] = $adb->query_result($result1,0,'document');
						$adb->pquery("update {$this->table_name}_attach set document = ? where {$this->table_index} = ? and contentid = ?", array($row['document'], $this->id, $row['contentid']));
					}
				}
				// crmv@88997e

				$target = '';
				if (stripos($row['contenttype'],'image') !== false || $row['contenttype'] == 'text/rfc822-headers') {	//crmv@53651
					$target = '_blank';
				}
				$document = '';
				if (!empty($row['document'])) {
					$result1 = $adb->pquery("SELECT crmid FROM {$table_prefix}_crmentity WHERE crmid = ? AND deleted = 0",array($row['document']));
					if ($result1 && $adb->num_rows($result1) > 0) {
						$document = $row['document'];
					}
				}
				if ($row['contentid'] < 0) {
					$attachmentid = $adb->query_result($adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($document)),0,'attachmentsid');
					$link = "index.php?module=uploads&action=downloadfile&fileid=$attachmentid&entityid=$document";
				} else {
                    $link = "index.php?module=Messages&action=MessagesAjax&file=Download&record={$this->id}&contentid={$row['contentid']}";
                }
				$action_download = true;
				$action_download_tnef = false;	//crmv@112756
				$action_save = true;
				$action_link = true;
				//crmv@62340	crmv@62414
				$action_view = false;
				$action_view_JSfunction = false;
				$action_label = false;
				$extension = substr(strrchr($row['contentname'], "."), 1);
				if(in_array(strtolower($extension),$this->viewerJS_supported_extensions)){
					$action_view = true;
					$action_view_JSfunction=$this->action_view_JSfunction_array[strtolower($extension)];
					$action_label = 'LBL_VIEW_DOCUMENT';
				}
				if(in_array(strtolower($extension),$this->view_image_supported_extensions)){
					$action_view = true;
					$action_view_JSfunction=$this->action_view_JSfunction_array[strtolower($extension)];
					$action_label = 'LBL_VIEW_DOCUMENT';
				}
				// crmv@107356 crmv@129689
				if(strtolower($extension) == 'eml' || $row['contenttype'] == 'message/rfc822'){
					$extension = strtolower($extension) ?: 'eml';
					if (!in_array($extension,$this->action_view_JSfunction_array)) $extension = 'eml';
					$action_view = true;
					$action_view_JSfunction=$this->action_view_JSfunction_array[$extension];
					$action_label = 'LBL_VIEW_AS_EMAIL';
				}
				// crmv@107356e crmv@129689e
				if($this->isEML()){
					$action_save = false;
					$action_link = false;
				}
				//crmv@112756
				if ($row['contenttype'] == 'application/ms-tnef') {
					$action_download = false;
					$action_download_tnef = true;
				}
				//crmv@112756e
				// crmv@187622
				if ($this->column_fields['folder'] == 'vteScheduled') {
					$action_download = false;
					$action_save = false;
					$action_link = false;
					$action_view = false;
					$action_view_JSfunction = false;
				}
				// crmv@187622e
				$attachments[] = array(
					'action_download'=>$action_download,
					'action_download_tnef'=>$action_download_tnef,	//crmv@112756
					'action_save'=>$action_save,
					'action_link'=>$action_link,
					'action_view'=>$action_view,
					'action_view_JSfunction'=>$action_view_JSfunction,
					'action_view_label'=>$action_label,
					'contentid'=>$row['contentid'],
					'name'=>$row['contentname'],
					'link'=>$link,
					// crmv@192843 removed img
					'target'=>$target,
					'document'=>$document,
				);
				//crmv@62340e	crmv@62414e
			}
		}
		return $attachments;
	}
	// crmv@48677e

	function getAttachmentsInfo() {
		global $adb,$table_prefix;
		$attachments = array();
		$result = $adb->pquery("select * from {$this->table_name}_attach where {$this->table_index} = ?",array($this->id));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$attachments[$row['contentid']]['parameters'] = array(
					'content_id'=>$row['content_id'],
					'name'=>$row['contentname'],
					'contenttype'=>$row['contenttype'],
					'contentdisposition'=>$row['contentdisposition'],
					'charset'=>$row['contentcharset'],
					'encoding'=>$row['contentencoding'],
					'method'=>$row['contentmethod'], // crmv@68357
					'size'=>$row['size'],	//crmv@65328
				);
			}
		}
		return $attachments;
	}

	// crmv@68357 crmv@81126
	function processIcalReply($uuid, $recurrIdx, $content) {
		global $adb, $table_prefix;
		
		$recurrIdx = intval($recurrIdx) ?: 0;
		$calendar = CRMEntity::getInstance('Calendar');
		
		// check for an existing event
		$activityid = $calendar->getCrmidFromUuid($uuid, $recurrIdx);
		if ($activityid > 0 && isPermitted('Calendar', 'DetailView', $activityid) == 'yes') {
			// first link with that event
			$this->save_related_module('Messages', $this->id, 'Calendar', $activityid);
			
			// then parse the event to get my address
			$vcalendar = new VTEvcalendar();
			$r = $vcalendar->parse($content);
			if ($r === false) return false;
			
			// get the event
			$event = $vcalendar->getComponent('vevent');
			if (empty($event)) $event = $vcalendar->getComponent('vtodo');
			
			$att = $event->getProperty('ATTENDEE', false, true);
			$attMail = preg_replace('/^MAILTO:/i', '', $att['value']);
			$part = $att['params']['PARTSTAT'];
			$partNo = 0;
			if ($part == 'DECLINED') {
				$partNo = 1;
			} elseif ($part == 'ACCEPTED') {
				$partNo = 2;
			}
			if (!empty($attMail) && $partNo > 0) {
				// now get invitees 
				$updateList = array();
				$invitees = $calendar->getInvitees($activityid);
				foreach ($invitees as $inv) {
					if ($inv['type'] == 'Contacts' && (strcasecmp($inv['email1'], $attMail) == 0 || strcasecmp($inv['email2'], $attMail) == 0)) {
						// ok, this is the invitee
						$updateList[$inv['id']] = $partNo;
					}
				}
				// and update the partecipations
				if (count($updateList) > 0) {
					foreach ($updateList as $inviteeid => $partecipation) {
						// now, this is ugly!!
						$from = 'invite_con';
						$_REQUEST['partecipation'] = $partecipation;
						$_REQUEST['activityid'] = $activityid;
						$_REQUEST['userid'] = $inviteeid;
						include('modules/Calendar/SavePartecipation.php');
					}
				}
			}
		}
		return true;
	}
	
	function processIcalRequest($uuid, $recurrIdx, $content) {
		global $current_user;
		
		$recurrIdx = intval($recurrIdx) ?: 0;
		$calendar = CRMEntity::getInstance('Calendar');
		
		// check for an existing event and link it to the email if I'm one of the invitees
		$activityid = $calendar->getCrmidFromUuid($uuid, $recurrIdx);
		if ($activityid > 0 && isPermitted('Calendar', 'DetailView', $activityid) == 'yes') {
			if ($this->id && $calendar->isUserInvited($activityid, $current_user->id)) {
				// ok, I'm invited, link the event to the message
				$this->save_related_module('Messages', $this->id, 'Calendar', $activityid);
			}
		}
	}
	// crmv@81126e
	
	function sendIcalReply($icalid, $answer = 'yes') {
		global $current_user;
		$ical = $this->getIcals($icalid);
		$ical = $ical[0];
		if (empty($ical)) return false;

		$myself = $ical['myemail'];
		// parse the event
		$vcalendar = new VTEvcalendar();
		$r = $vcalendar->parse($ical['content']);
		if ($r === false) return false;
		
		// crmv@199992
		$method = $vcalendar->getProperty('METHOD');
		if ($method == 'PUBLISH') {
			// if you receive a publish ics instead of request, you aren't allowed to
			// reply, since it's not an invitation, so we can ignore the reply and 
			// create the event anyway
			$this->setIcalPartecipation($icalid, $answer == 'yes' ? 2 : 1);
			return true;
		}
		// crmv@199992e
		
		// get the timezone
		$tzone = $vcalendar->getComponent("vtimezone");
		
		// prepare the new calendar
		$newcal = new VTEvcalendar();
		if ($tzone) $newcal->addComponent($tzone);
		if ($vcalendar->version) $newcal->setVersion($vcalendar->version);
		if ($vcalendar->prodid) $newcal->prodid = $vcalendar->prodid;
		if ($vcalendar->calscale) $newcal->setCalscale($vcalendar->calscale);
		$newcal->setMethod('REPLY');
		
		// get the original event
		$event = $vcalendar->getComponent('vevent');
		if (empty($event)) $event = $vcalendar->getComponent('vtodo');
		
		// search for myself
		$myselfInvitee = null;
		while ($att = $event->getProperty('ATTENDEE', false, true)) {
			$amail = preg_replace('/^mailto:/i', '', $att['value']);
			if (strcasecmp($amail, $myself) == 0) {
				// myself
				$myselfInvitee = $att;
				break;
			}
		}
		if (!$myselfInvitee) return false;
		
		// remove all attendees
		while ($event->deleteProperty('ATTENDEE')) ;
		
		// add myself with participation
		$myselfInvitee['params']['PARTSTAT'] = ($answer == 'yes' ? 'ACCEPTED' : 'DECLINED');
		unset($myselfInvitee['params']['RSVP']);
		
		$event->setAttendee($myselfInvitee['value'], $myselfInvitee['params']);
		
		// get some params
		$organizer = $event->getProperty('ORGANIZER', false, true);
		$subject = $event->getProperty('SUMMARY');
		
		// add it to the new calendar
		$newcal->addComponent($event);
		$out = $newcal->createCalendar();
		if (empty($out)) return false;
		$out = trim($out);
		
		// now prepare the email and send it!
		$attachment = array(
			array(
				'sourcetype' => 'string',
				'content' => $out,
				'contenttype' => 'text/calendar',
				'altbody' => true,
				'charset' => 'UTF-8',
				'encoding' => '7bit',
				'method' => 'REPLY',
			),
			array(
				'sourcetype' => 'string',
				'filename' => 'invite.ics',
				'content' => $out,
				'contenttype' => 'application/ics',
			),
		);
		
		// find the sender (organizator, otherwise the sender of the email)
		$to_email = preg_replace('/^mailto:/i', '', $organizer['value']) ?: $ical['sender'];

		$myname = $myselfInvitee['params']['CN'] ?: getUserFullName($current_user->id);
		if ($answer == 'yes') {
			$description = "$myname ({$myself}) ".getTranslatedString('LBL_INVITATION_ACCEPTED', 'Calendar');
			$email_subject = getTranslatedString('LBL_INVITATION_ACCEPTED_SUBJECT', 'Calendar').": $subject";
		} else {
			$description = "$myname ({$myself}) ".getTranslatedString('LBL_INVITATION_DECLINED', 'Calendar');
			$email_subject = getTranslatedString('LBL_INVITATION_DECLINED_SUBJECT', 'Calendar').": $subject";
		}
		
		// send
		// crmv@78362
		$myemail = $myself ?: getUserEmailId('id', $current_user->id); 
		$mail_status = send_mail('Emails',$to_email,$myname,$myemail,$email_subject,$description, '', '', $attachment);
		// crmv@78362e
		
		if ($mail_status == 1) {
			$this->setIcalPartecipation($icalid, $answer == 'yes' ? 2 : 1);
		}

		return ($mail_status == 1);
	}
	
	//crmv@81126
	function createEventFromIcal($icalid, &$activityid) {
		global $current_user, $table_prefix, $adb;
		
		$ical = $this->getIcals($icalid);
		$ical = $ical[0];
		if (empty($ical)) return false;
		
		// parse the ical
		$vcalendar = new VTEvcalendar();
		$r = $vcalendar->parse($ical['content']);
		if ($r === false) return false;
		
		// get the event or todo
		$isTodo = false;
		$event = $vcalendar->getComponent('vevent');
		if (empty($event)) {
			$event = $vcalendar->getComponent('vtodo');
			if (empty($event)) return false;
			$isTodo = true;
		}
		
		$calendar = CRMEntity::getInstance('Calendar');
		$messagesid = $ical['messagesid'];
		
		// check for an existing event
		$activityid = $calendar->getCrmidFromUuid($ical['uuid'], $ical['recurring_idx']);

		if ($activityid > 0) {
			// get the owner and the invitees, if I'm the owner, do nothing, if I'm an invitee, update the participation
			$owner = getSingleFieldValue($table_prefix.'_crmentity', 'smownerid', 'crmid', $activityid);
			if ($owner == $current_user->id) {
				// crmv@189405
				// it's mine, do nothing, but check if it's an update
				if ($ical['is_update']) {
					$res = $vcalendar->generateArray($event,$isTodo ? 'vtodo' : "vevent");
					if (!$res['description']) unset($res['description']);
					unset($res['recurring_idx']);
					$calendar->retrieve_entity_info_no_html($activityid, 'Calendar');
					$calendar->column_fields = array_replace($calendar->column_fields,$res);
					$calendar->recurringObject = $res['recurrence']; // crmv@185576
					$calendar->mode = 'edit';
					$calendar->save('Calendar');
				}
				// crmv@189405e
			} else {
				// check if I'm one of the invitees
				if ($messagesid && $calendar->isUserInvited($activityid, $current_user->id)) {
					// ok, it's me, set the answer to yes!
					$calendar->setUserInvitationAnswer($activityid , $current_user->id, 2); // 2 = yes!
					
					// crmv@202383
					// if invited and can modify, change the event
					if ($ical['is_update'] && isPermitted('Calendar', 'EditView', $activityid) == 'yes') {
						$res = $vcalendar->generateArray($event,$isTodo ? 'vtodo' : "vevent");
						if (!$res['description']) unset($res['description']);
						unset($res['recurring_idx']);
						$calendar->retrieve_entity_info_no_html($activityid, 'Calendar');
						$calendar->column_fields = array_replace($calendar->column_fields,$res);
						$calendar->recurringObject = $res['recurrence']; // crmv@185576
						$calendar->mode = 'edit';
						$calendar->save('Calendar');
					}
					// crmv@202383e
				}
			}
		} else {
			// create a new one
			$res = $vcalendar->generateArray($event,$isTodo ? 'vtodo' : "vevent");
			if (!$res['description']) $res['description'] = '';
			$calendar->column_fields = array_merge($calendar->column_fields,$res);
			$calendar->column_fields['assigned_user_id'] = $current_user->id;
			$calendar->recurringObject = $res['recurrence']; // crmv@185576
			$calendar->save('Calendar');
			if (empty($calendar->id)) return false;
			$activityid = $calendar->id;
			
			// crmv@185576
			// create repeated events
			if ($calendar->recurringObject) {
				require_once 'modules/Calendar/RepeatEvents.php';
				Calendar_RepeatEvents::repeat($calendar, $calendar->recurringObject);
			}
			// crmv@185576e
			
			// add the invitees (users only, no notifications)
			if (is_array($ical['values']['invitees'])) {
				$calInvitees = array();
				$calInviteesPart = array();
				foreach ($ical['values']['invitees'] as $invitee) {
					if ($invitee['record'] && $invitee['record']['module'] == 'Users') {
						$userid = $invitee['record']['crmid'];
						if ($userid != $current_user->id) {
							$calInvitees[] = $userid;
							$calInviteesPart[$userid] = $invitee['partecipation'];
						}
					}
				}
				if (count($calInvitees) > 0) {
					$calendar->insertIntoInviteeTable('Calendar', $calInvitees, $calInviteesPart);
				}
			}
		}
		
		// and save again the relation, to be sure
		if ($messagesid > 0) {
			$this->save_related_module('Messages', $messagesid, 'Calendar', $activityid);
			// crmv@189222
			// link the event to all email with the same invitation
			$res = $adb->pquery("SELECT DISTINCT messagesid FROM {$table_prefix}_messages_ical WHERE uuid = ? AND messagesid != ?", array($ical['uuid'], $messagesid));
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$this->save_related_module('Messages', $row['messagesid'], 'Calendar', $activityid);
				}
			}
			// crmv@189222e
		}
		
		return true;
	}
	
	function deleteEventFromIcal($icalid) {
		global $current_user, $table_prefix, $adb;
		
		$ical = $this->getIcals($icalid);
		$ical = $ical[0];
		if (empty($ical)) return false;
		
		$calendar = CRMEntity::getInstance('Calendar');
		$activityid = $calendar->getCrmidFromUuid($ical['uuid'], $ical['recurring_idx']);
		if ($activityid > 0) {
			$owner = getSingleFieldValue($table_prefix.'_crmentity', 'smownerid', 'crmid', $activityid);
			if ($owner == $current_user->id) {
				// I'm the owner, recreate the event for all the users that accepted
				$usersInv = array();
				$invitees = $calendar->getInvitees($activityid);
				if (is_array($invitees)) {
					foreach ($invitees as $inv) {
						if ($inv['type'] == 'Users' && $inv['id'] != $current_user->id && $inv['partecipation'] == 2) {
							$usersInv[] = $inv['id'];
						}
					}
				}
				// delete the event
				$calendar->trash('Calendar', $activityid);
				
				// now create a new one with the first user found (the other invitees will be added automatically)
				if (count($usersInv) > 0) {
					// change the user
					$saveCurrentUser = $current_user;
					$current_user = CRMEntity::getInstance('Users');
					$current_user->retrieveCurrentUserInfoFromFile($usersInv[0]);
					$this->createEventFromIcal($icalid);
					// switch back
					$current_user = $saveCurrentUser;
				}
			} else {
				// otherwise reply no, and unlink from message
				$this->cancelEventFromUuid($ical['uuid'], $ical['recurring_idx']);
			}
		}
		
		return true;
	}
	
	function cancelEventFromIcal($icalid) {
		global $current_user, $table_prefix, $adb;
		
		$ical = $this->getIcals($icalid);
		$ical = $ical[0];
		if (empty($ical)) return false;
		
		return $this->cancelEventFromUuid($ical['uuid'], $ical['recurring_idx']);
	}
	
	// send the invitation answer If I'm invited to an event
	function cancelEventFromUuid($uuid, $recurrIdx = 0) {
		global $current_user, $table_prefix, $adb;
		
		$calendar = CRMEntity::getInstance('Calendar');
		$activityid = $calendar->getCrmidFromUuid($uuid, $recurrIdx);
		if ($activityid > 0) {
			$owner = getSingleFieldValue($table_prefix.'_crmentity', 'smownerid', 'crmid', $activityid);
			if ($owner != $current_user->id) {
				// check If I'm invited, and change my partecipation
				if ($calendar->isUserInvited($activityid, $current_user->id)) {
					$calendar->setUserInvitationAnswer($activityid , $current_user->id, 1); // 1 = no!
				}
				// and remove the link with the email
				if ($this->id > 0) {
					$this->unlinkRelationship($this->id, 'Calendar', $activityid);
				}
			}
		}
		
		return true;
	}
	//crmv@81126e
	
	function setIcalPartecipation($icalid, $part = 0) {
		global $adb, $table_prefix;
		
		$adb->pquery("UPDATE {$this->table_name}_ical SET partecipation = ? WHERE messagesid = ? AND sequence = ?", array($part, $this->id, $icalid));
	}
	
	function getIcals($icalid = null) {
		global $adb,$table_prefix;
		$icals = array();
		
		$calFocus = CRMEntity::getInstance('Calendar');
		// crmv@174249
		$query = "SELECT 
				{$this->table_name}_ical.*,
				{$this->table_name}.mfrom as sender,
				{$this->table_name}_account.email as myemail,
				{$this->table_name}_account.userid as recipient_userid
			FROM {$this->table_name}_ical 
			INNER JOIN {$this->table_name} ON {$this->table_name}_ical.messagesid = {$this->table_name}.messagesid
			LEFT JOIN {$this->table_name}_account ON {$this->table_name}_account.id = {$this->table_name}.account
			WHERE {$this->table_name}_ical.{$this->table_index} = ?";
		// crmv@174249e
		$params = array($this->id);
		if ($icalid > 0) {
			// crmv@182005
			if (is_numeric($icalid)) {
				$query .= " AND sequence = ?";
			} else {
				$query .= " AND uuid = ?";
			}
			// crmv@182005e
			$params[] = $icalid;
		}
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$row['method'] = trim($row['method']);
				$row['recipient_userid'] = intval($row['recipient_userid']); // crmv@174249
				$row['activityid'] = $calFocus->getCrmidFromUuid($row['uuid'], $row['recurring_idx']); // crmv@81126
				
				$vcalendar = new VTEvcalendar();
				$r = $vcalendar->parse($row['content']);
				if ($r === false) continue;
				$event = $vcalendar->getComponent('vevent');
				if (!$event) continue;
				$values = $vcalendar->generateArray($event, 'vevent');
				
				$values['subject'] = nl2br(htmlentities($values['subject'], ENT_COMPAT, 'UTF-8'));
				$values['location'] = nl2br(htmlentities($values['location'], ENT_COMPAT, 'UTF-8'));
				$values['description_html'] = nl2br(htmlentities($values['description'], ENT_COMPAT, 'UTF-8'));
				$values['description_html'] = $this->linkToTags($values['description_html']);
				
				// crmv@189405
				// check if it's an update to existing event
				if ($row['activityid'] > 0) {
					$row['is_update'] = $this->isIcalUpdate($values, $row['activityid']);
				}
				// crmv@189405e
				
				// format the when
				$values['when_formatted'] = $this->formatIcalDateRange($values['date_start'].' '.$values['time_start'], $values['due_date'].' '.$values['time_end']);
				$row['values'] = $values;
				$icals[] = $row;
			}
		}
		return $icals;
	}
	
	// crmv@189405
	/**
	 * Check if the passed ical values represent an update to the event $activityid;
	 */
	public function isIcalUpdate($values, $activityid) {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT date_start, due_date, time_start, time_end FROM {$table_prefix}_activity WHERE activityid = ?", array($activityid));
		$arow = $adb->fetchByAssoc($res, -1, false);
		if ($arow) {
			if (
				$arow['date_start'] != $values['date_start'] 
				|| $arow['due_date'] != $values['due_date']
				|| $arow['time_start'] != $values['time_start']
				|| $arow['time_end'] != $values['time_end']
			) 
			{
				return true;
			}
		}
		
		return false;
	}
	// crmv@189405e
	
	// quick function to get only the start datetime, in local timezone
	function getIcalStartDate($icalid, &$icalRow) {
		global $default_timezone;
		$icalRow = $this->getIcals($icalid);
		$icalRow = $icalRow[0];
		if (empty($icalRow)) return false;
		
		$vcalendar = new VTEvcalendar();
		$r = $vcalendar->parse($icalRow['content']);
		if ($r === false) return false;
		
		$event = $vcalendar->getComponent('vevent');
		if (!$event) $event = $vcalendar->getComponent('vtodo');
		if (!$event) return false;
		
		$dt = $event->getProperty('DTSTART');
		$dt = $vcalendar->strtodatetime($dt);
		$dt = $dt[0].' '.$dt[1];
		
		return $dt;
	}
	
	function formatIcalDateRange($start, $end, $allday = false) {
		$monthList = array(
			'LBL_MONTH_JANUARY',
			'LBL_MONTH_FEBRUARY',
			'LBL_MONTH_MARCH',
			'LBL_MONTH_APRIL',
			'LBL_MONTH_MAY',
			'LBL_MONTH_JUNE',
			'LBL_MONTH_JULY',
			'LBL_MONTH_AUGUST',
			'LBL_MONTH_SEPTEMBER',
			'LBL_MONTH_OCTOBER',
			'LBL_MONTH_NOVEMBER',
			'LBL_MONTH_DECEMBER',
		);
		$ts1 = strtotime($start);
		$ts2 = strtotime($end);
		if (!$ts1 || !$ts2) return null;
		$day1 = substr($start, 0, 10);
		$day2 = substr($end, 0, 10);
		$date = '';
		$dow = date('w', $ts1);
		$mn = date('m', $ts1);
		$date .= getTranslatedString('LBL_DAY'.$dow, 'Calendar');
		$date .= date(' j ', $ts1);
		$date .= getTranslatedString($monthList[$mn-1]);
		$date .= date(' Y', $ts1);
		if ($day1 == $day2) {
			// same day
			if (!$allday) $date .= ', '.date('H:i', $ts1).' - '.date('H:i', $ts2) ;
		} else {
			// spans on multiple days
			$dow2 = date('w', $ts2);
			$mn2 = date('m', $ts2);
			if (!$allday) $date .= ', '.date('H:i', $ts1);
			$date .= ' - ';
			$date .= getTranslatedString('LBL_DAY'.$dow2, 'Calendar');
			$date .= date(' j ', $ts2);
			$date .= getTranslatedString($monthList[$mn2-1]);
			$date .= date(' Y', $ts2);
			if (!$allday) $date .= ', '.date('H:i', $ts2);
		}
		return $date;
	}
	
	function linkToTags($text) {
		global $adb;
		preg_match_all("/([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/",$text,$links1);
		preg_match_all("/((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/",$text,$links2);
		$links = array_merge($links1,$links2);
		if (is_array($links)) {
			$links = $adb->flatten_array(array_filter($links));
			if (is_array($links)) {
				$links = array_filter($links,function($var) {
					if ($var == "" || $var == "www") return false; else return true;
				});
				if (is_array($links)) {
					$links = array_unique($links);
				}
			}
		}
		
		$text = preg_replace("/(^|[\n ])([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>",$text);
		$text = preg_replace("/(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/","\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>",$text);
		$text = preg_replace("/,\"|\.\"|\)\"|\)\.\"|\.\)\"/","\"",$text);
		
		$searchkey = '';
		if (!empty($links)) {
			// clean links
			foreach ($links as $url) {
				$dirty_url = str_ireplace($searchkey, '<mark>'.$searchkey.'</mark>', $url);
				$text = str_ireplace($dirty_url, $url, $text);
			}
			// replace marks
			foreach ($links as $url) {
				if (strlen($url) > 60) {
					$first_part = str_ireplace($searchkey, '<mark>'.$searchkey.'</mark>', substr($url,0,45));
					$last_part = str_ireplace($searchkey, '<mark>'.$searchkey.'</mark>', substr($url,-12));
					$link = $first_part.'...'.$last_part;
				} else {
					$link = str_ireplace($searchkey, '<mark>'.$searchkey.'</mark>', $url);
				}
				$text = str_replace(">$url<",'>'.$link.'<',$text);
			}
		}
		
		return $text;
	}
	// crmv@68357e

	// crmv@91980 crmv@113417
	// put the cleaned body in the description
	function retrieve_entity_info($record, $module, $dieOnError=true, $onlyFields = array()) {
	
		$return = parent::retrieve_entity_info($record, $module, $dieOnError, $onlyFields);
		if (empty($this->column_fields['description']) && !empty($this->column_fields['cleaned_body'])) {
			$this->column_fields['description'] = $this->column_fields['cleaned_body'];
		}
		
		return $return;
	}
	
	function retrieve_entity_info_no_html($record, $module, $dieOnError=true, $onlyFields = array()) {
	
		$return = parent::retrieve_entity_info_no_html($record, $module, $dieOnError, $onlyFields);
		if (empty($this->column_fields['description']) && !empty($this->column_fields['cleaned_body'])) {
			$this->column_fields['description'] = $this->column_fields['cleaned_body'];
		}
		
		return $return;
	}
	// crmv@113417e
	
	// avoid to save the description, use directly the cleaned body
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true) {
	
		// save the description for later
		$this->description_backup = $this->column_fields['description'];
		$this->column_fields['description'] = null;
		
		// call the parent
		return parent::save($module_name,$longdesc,$offline_update,$triggerEvent);
	}
	// crmv@91980e

	function save_module($module) {
		global $adb, $table_prefix;

		//crmv@171021
		//crmv@44482 : check if saving has been successfully completed
		$result = $adb->pquery("SELECT {$this->table_index} FROM {$this->table_name} WHERE {$this->table_index} = ?",array($this->id));
		//crmv@171021e

		if ($adb->num_rows($result) == 0) {
			throw new Exception('ERR_SAVING_IN_DB');
		}

		if (empty($this->column_fields['messageid'])) {
			$mailer = new VTEMailer(); // crmv@180739
			$uniq_id = md5(uniqid(time()));
			$messageid = sprintf('<%s@%s>', $uniq_id, $mailer->ServerHostname());

			$adb->pquery("update {$this->table_name} set messageid = ? where {$this->table_name}.{$this->table_index} = ?", array($messageid, $this->id));
			$this->column_fields['messageid'] = $messageid;
		}

		//crmv@37004 crmv@81338 crmv@86194
		// save the hash when saving the record
		$specialFolders = $this->getSpecialFolders(false);
		if (!empty($this->column_fields['folder']) && $this->column_fields['folder'] == $specialFolders['Drafts']) {
			$hash = $this->getMessageHash($this->column_fields['messageid'], '');
		} else {
			$cleanSubject = html_entity_decode($this->column_fields['subject'], ENT_COMPAT, 'UTF-8');
			$hash = $this->getMessageHash($this->column_fields['messageid'], $cleanSubject);
		}
		if ($hash && $this->id && empty($this->column_fields['messagehash'])) { // crmv@119358
			$adb->pquery("update {$this->table_name} set messagehash = ? where {$this->table_name}.{$this->table_index} = ?", array($hash, $this->id));
			$this->column_fields['messagehash'] = $hash;
		}
		//crmv@37004e crmv@81338e crmv@86194e

		// crmv@109127 crmv@171021
		// recover ModComments relations of deleted Messages
		$query = "SELECT {$this->relation_table}.{$this->relation_table_otherid}, {$this->table_name}.{$this->table_index} as \"oldmessagesid\"
					FROM {$this->relation_table}
					INNER JOIN {$this->table_name} ON {$this->table_name}.messagehash = {$this->relation_table}.{$this->relation_table_id}
					WHERE {$this->table_name}.deleted = ? AND {$this->relation_table}.{$this->relation_table_id} = ? AND {$this->relation_table}.{$this->relation_table_othermodule} = ?";
		$result = $adb->pquery($query,array(1,$hash,'ModComments'));
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$modcommentsid = $row[$this->relation_table_otherid];
				$oldMessagesid = $row['oldmessagesid'];
				$adb->pquery("update {$table_prefix}_modcomments set related_to = ? where modcommentsid = ?",array($this->id,$modcommentsid));
				$adb->pquery("UPDATE {$table_prefix}_modcomments_msgrel SET messagesid = ? WHERE messagesid = ?", array($this->id, $oldMessagesid));
			}
		}
		// crmv@109127e crmv@171021e

		// thread
		if (empty($this->column_fields['mreferences']) && !empty($this->column_fields['in_reply_to'])) {
			$adb->pquery("update {$this->table_name} set mreferences = ? where {$this->table_name}.{$this->table_index} = ?", array($this->column_fields['in_reply_to'], $this->id));
			$this->column_fields['mreferences'] = $this->column_fields['in_reply_to'];
		}
		// crmv@85493
		$this->deleteMrefs($this->id);
		$this->insertMrefs($this->id, $this->column_fields['mreferences']);
		// crmv@85493e
		if ($this->column_fields['mtype'] == 'Webmail') {
			//$this->updateThreadCount($hash);
			$this->updateThreadCount();
		}

		// save attachments information
		if (!empty($this->column_fields['other'])) {
			if ($this->mode == 'edit') {
				$adb->pquery("delete from {$this->table_name}_attach where {$this->table_index} = ?",array($this->id));
			}
			foreach ($this->column_fields['other'] as $id => $content) {
				//crmv@65328 crmv@68357
				$adb->pquery("insert into {$this->table_name}_attach ({$this->table_index},contentid,content_id,contentname,contenttype,contentdisposition,contentcharset,contentencoding,contentmethod,size) values (?,?,?,?,?,?,?,?,?,?)",
					array($this->id,$id,$content['parameters']['content_id'],$content['parameters']['name'],$content['parameters']['contenttype'],$content['parameters']['contentdisposition'],$content['parameters']['charset'],$content['parameters']['encoding'],$content['parameters']['method'],$content['parameters']['size']));
				//crmv@65328e crmv@68357e
			}
		}
		
		//crmv@63475 crmv@171021 recover attach relations of deleted Messages
		$query = "SELECT {$table_prefix}_messages_attach.*
					FROM {$this->table_name}
					INNER JOIN {$table_prefix}_messages_attach ON {$this->table_name}.messagesid = {$table_prefix}_messages_attach.messagesid
					WHERE deleted = ? AND messagehash = ? AND document IS NOT NULL";
		$result = $adb->pquery($query,array(1,$hash));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByASsoc($result)) {
				$adb->pquery("update {$table_prefix}_messages_attach set document = ? where messagesid = ? and contentid = ?",array($row['document'],$this->id,$row['contentid']));
			}
		}
		//crmv@63475e crmv@171021e

		// crmv@68357 crmv@81126 - save text/calendar parts to be able to show the invitation/reply
		if (!empty($this->column_fields['icals'])) {
			foreach ($this->column_fields['icals'] as $seq => $ical) {
				//crmv@177526 crmv@185956
				// extract uid
				$vcalendar = new VTEvcalendar();
				$r = $vcalendar->parse($ical);
				if ($r === false) continue;
				$uuid = $vcalendar->getProperty('UID');
				$method = $vcalendar->getProperty('METHOD');
				$recurrIdx = $vcalendar->getProperty('SEQUENCE');
				if (is_array($uuid)) $uuid = array_keys($uuid)[0];
				($recurrIdx === false) ? $recurrIdx = 0 : $recurrIdx = intval($recurrIdx);
				if (!empty($uuid)) {
				//crmv@177526e crmv@185956e
					$res = $adb->pquery("SELECT messagesid FROM {$this->table_name}_ical WHERE messagesid = ? AND sequence = ?", array($this->id,$seq));
					if ($res && $adb->num_rows($res) > 0) {
						// update
						$adb->pquery("UPDATE {$this->table_name}_ical SET uuid = ?, recurring_idx = ?, method = ?, content = ? WHERE messagesid = ? AND sequence = ?", array($uuid, $recurrIdx, $method, $ical, $this->id,$seq));
					} else {
						// insert
						$adb->pquery("INSERT INTO {$this->table_name}_ical ({$this->table_index},sequence,uuid,recurring_idx,method,content) values (?,?,?,?,?,?)", array($this->id,$seq, $uuid, $recurrIdx, $method, $ical));
					}
					if ($method == 'REPLY') {
						$this->processIcalReply($uuid, $recurrIdx, $ical);
					} elseif ($method == 'REQUEST') {
						$this->processIcalRequest($uuid, $recurrIdx, $ical);
					}
				}
			}
		}
		// crmv@68357e crmv@81126e
		
		//crmv@46760
		if (isset($_FILES) && !empty($_FILES) && isset($_REQUEST['element'])){
			$elements = @Zend_Json::decode($_REQUEST['element']);
			if (isset($elements) && $elements['hasattachments'] == 'True' && $elements['external_plugin'] == 'true'){
				$files_arr = $_FILES;
				$contentid = 0;
				foreach($files_arr as $fileindex => $files){
					if($files['name'] != '' && $files['size'] > 0){
						$_FILES = Array();
						$_FILES['filename'] = $files;
						//TODO:check if other plugins than outlook put unique id before real name...
						if (strpos($files['name'],"_")!== false){
							$files['name'] = explode("_",$files['name'],2);
							$files['name'] = $files['name'][1];
						}
						// Create document record
						//crmv@86304
						$resFolder = $adb->pquery("select folderid from {$table_prefix}_crmentityfolder where foldername = ?", array('Message attachments'));
						($resFolder && $adb->num_rows($resFolder) > 0) ? $folderid = $adb->query_result($resFolder,0,'folderid') : $folderid = 1;
						//crmv@86304e
						$document = CRMEntity::getInstance('Documents');
						$document->column_fields['notes_title']      = $files['name'];
						$document->column_fields['filelocationtype'] = 'I';
						$document->column_fields['folderid']         = $folderid;	//crmv@86304
						$document->column_fields['filestatus']    	 = 1; // Active
						$document->column_fields['assigned_user_id'] = $this->column_fields['assigned_user_id'];
						$document->parentid = $this->id;
						if (method_exists($document,'autoSetBUMC')) $document->autoSetBUMC('Documents',$current_user->id);	//crmv@93302
						$document->save('Documents');
						$documentid = $document->id;
						if ($documentid != ''){
							$params = Array(
							$this->table_index=>$id,
								'messagesid'=>$this->id,
								'contentid'=>$contentid,
								'contentname'=>$files['name'],
								'contenttype'=>$files['type'],
								'contentdisposition'=>'external attachment',
								'document'=>$documentid,
								'size'=>$files['size'],	//crmv@65328
							);
							$sql = "insert into {$this->table_name}_attach (".implode(",",array_keys($params)).") values (".generateQuestionMarks($params).")";
							$adb->pquery($sql,$params);
						}
						$contentid++;
					}
				}
			}
		}
		//crmv@46760e

		// crmv@49398 crmv@56409 crmv@91980
		if ($this->id > 0 && !empty($this->description_backup)) {
			// clean the body and save it
			if (empty($this->column_fields['cleaned_body'])) {
				$attachments_info = $this->getAttachmentsInfo();
				$message_data = array('other'=>$attachments_info);
				$description = str_replace('&amp;', '&', $this->description_backup);
				$magicHTML = $this->magicHTML($description, $this->column_fields['xuid'], $message_data);
				$this->saveCleanedBody($this->id, $magicHTML['html'], $magicHTML['content_ids']);
				$this->column_fields['cleaned_body'] = $magicHTML['html'];
				$this->column_fields['content_ids'] = $magicHTML['content_ids'];
			}
			// save the phone numbers
			$numbers = $this->extractPhoneNumbers($this->description_backup);
			if (count($numbers) > 0) {
				$this->deletePhoneNumbers($this->id);
				$this->savePhoneNumbers($this->id, $numbers);
			}
		}
		// and unset, to release memory
		unset($this->description_backup);
		// crmv@49398e crmv@56409e crmv@91980e

		// set recipients
		if (!empty($this->column_fields['recipients'])) {
			$adb->pquery("delete from {$table_prefix}_messages_recipients where messagesid = ?",array($this->id));
			$this->setRecipients($this->id,$this->column_fields['recipients']);
		}

		// set/update relations
		if (!empty($this->column_fields['parent_id'])) {
			$ids = array_filter(explode('|', $this->column_fields['parent_id']));
			foreach ($ids as $relid) {
				list($elid, $fieldid) = explode('@', $relid, 2);
				if (strpos($elid,'x') !== false) {
					$elid = explode('x',$elid);
					$elid = $elid[1];
				}
				$mod = getSalesEntityType($elid);
				if ($mod) {
					$this->save_related_module_small($messageid, $mod, $elid);
				}
			}
		}
		
		//crmv@125629 : save in cache inline attachments
		if (!empty($this->column_fields['content_ids'])) {
			$content_ids = $this->column_fields['content_ids'];
			foreach($content_ids as $contentid) {
				if (isset($this->column_fields['other'][$contentid])) {
					$content = $this->column_fields['other'][$contentid];
					$content['content'] = $this->decodeAttachment($content['content'],$content['parameters']['encoding'],$content['parameters']['charset']);
					$this->saveInlineCache($this->id,$contentid,$content['content'],array(
						'name'=>$content['parameters']['name'],
						'contenttype'=>$content['parameters']['contenttype'],
						'contentdisposition'=>$content['parameters']['contentdisposition'],
					));
				}
			}
		}
		//crmv@125629e
	}

	// crmv@81338	crmv@81889
	function getParentMessage($id,$folder,$prev_mid=array()) {
		global $adb, $table_prefix;

		$focus = CRMEntity::getInstance('Messages');
		$focus->retrieve_entity_info_no_html($id,'Messages');

		if (!empty($focus->column_fields['mreferences'])) {
			if (preg_match_all('/<[^<>]+>/',$focus->column_fields['mreferences'],$matches) && !empty($matches[0])) {
				$references = $matches[0];
				foreach($references as $reference) {
					$mid = trim($reference);
					if (is_array($prev_mid) && count($prev_mid) > 0 && in_array($mid, $prev_mid)) {
						return $id;
					}
					$result = $adb->pquery("SELECT {$this->table_index} FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND folder = ? AND messageid = ?", array($focus->column_fields['assigned_user_id'],$folder,$mid)); //crmv@171021
					if ($result && $adb->num_rows($result)>0) {
						$prev_mid[] = $mid;
						return $this->getParentMessage($adb->query_result_no_html($result,0,$this->table_index),$folder,array_unique($prev_mid));
					} else {	// search father in other folders (ex. Sent) and so search the next father in the current folder
						$result = $adb->pquery("SELECT {$this->table_index} FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND folder <> ? AND messageid = ?", array($focus->column_fields['assigned_user_id'],$folder,$mid)); //crmv@171021
						if ($result && $adb->num_rows($result)>0) {
							$result1 = $adb->pquery("SELECT in_reply_to FROM {$this->table_name} WHERE {$this->table_index} = ?", array($adb->query_result_no_html($result,0,$this->table_index)));
							if ($result1 && $adb->num_rows($result1) > 0) {
								$in_reply_to = $adb->query_result_no_html($result1,0,'in_reply_to');
								if (!empty($in_reply_to)) {
									$mid = trim($in_reply_to);
									$result = $adb->pquery("SELECT {$this->table_index} FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND folder = ? AND messageid = ?", array($focus->column_fields['assigned_user_id'],$folder,$mid)); //crmv@171021
									if ($result && $adb->num_rows($result)>0) {
										$prev_mid[] = $mid;
										return $this->getParentMessage($adb->query_result_no_html($result,0,$this->table_index),$folder,array_unique($prev_mid));
									}
								}
							}
						}
					}
				}
			}
		}
		return $id;
	}
	// crmv@81338e	crmv@81889e

	function updateThreadCount() {
		$folder = $this->column_fields['folder'];
		$father = $this->getParentMessage($this->id,$folder);
		$this->insertIntoTh($folder,$father,$this->id);
		$adopt_result = $this->adoptChildren($folder,$father);
		if (!$adopt_result && !empty($this->column_fields['mreferences'])) {
			$this->referenceChildren($folder,$this->column_fields['mreferences']);
			//TODO: $this->adoptReferenceChildren($folder,$this->column_fields['mreferences']);
		}
		$this->updateLastSon($folder);
	}

	function insertIntoTh($folder,$father,$son) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_messages_th where folder = ? and father = ? and son = ?",array($folder,$son,$son));	//prevent duplicate rows (only 1 row for son)
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into {$table_prefix}_messages_th (folder,father,son) values (?,?,?)",array($folder,$father,$son));
		} else {
			$result = $adb->pquery("SELECT * FROM {$table_prefix}_messages_th
									WHERE {$table_prefix}_messages_th.folder = ? AND {$table_prefix}_messages_th.father = ? AND {$table_prefix}_messages_th.son = ?",
									array($folder,$father,$son));
			if (!$result || $adb->num_rows($result) == 0) {
				$adb->pquery("insert into {$table_prefix}_messages_th (folder,father,son) values (?,?,?)",array($folder,$father,$son));
			}
		}
	}

	function adoptChildren($folder,$id) {
		global $adb, $table_prefix;
		$messageid = '';
		$result = $adb->pquery("SELECT messageid FROM {$this->table_name} WHERE deleted = 0 AND {$this->table_index} = ?",array($id)); //crmv@171021
		if ($result && $adb->num_rows($result)>0) {
			$messageid = $adb->query_result_no_html($result,0,'messageid');	//crmv@81889
		}
		if (!empty($messageid)) {
			// crmv@85493 crmv@171021
			$result = $adb->pquery(
				"SELECT {$this->table_name}.messagesid FROM {$this->table_name}
				INNER JOIN {$table_prefix}_messages_mref ON {$table_prefix}_messages_mref.messagesid = {$this->table_name}.{$this->table_index}
				WHERE deleted = 0 AND smownerid = ? AND folder = ? AND {$table_prefix}_messages_mref.mreference = ?",
				array($this->column_fields['assigned_user_id'],$folder,$messageid)
			);
			// crmv@85493e crmv@171021e
			if ($result && $adb->num_rows($result)>0) {
				while($row=$adb->fetchByAssoc($result)) {
					if ($adb->isMysql()) {
						$adb->pquery("update ignore {$table_prefix}_messages_th set father = ? where father = ?",array($id,$row['messagesid']));
					} else {	//TODO
						$adb->pquery("update {$table_prefix}_messages_th set father = ? where father = ?",array($id,$row['messagesid']));
					}
				}
				return true;
			}
		}
		return false;
	}

	function updateLastSon($folder,$father='') {
		global $adb, $table_prefix;
		if (empty($father)) {
			$father = $this->getFather($this->id, $folder);
		}
		if (!empty($father)) {
			$children = $this->getChildren($father,$folder);
			if (!empty($children)) {
				global $adb, $table_prefix;
				$lastson = $children[0];
				if (!empty($lastson)) {
					$adb->pquery("update {$table_prefix}_messages set lastson = ? where messagesid = ?",array($lastson,$father));
				}
				$children = array_diff($children,array($father));
				if (!empty($children)) {
					$adb->pquery("update {$table_prefix}_messages set lastson = null where messagesid IN (".generateQuestionMarks($children).")",array($children));
				}
			}
		}
	}

	function referenceChildren($folder,$mreferences) {
		// se trovo un Messaggio piu vecchio con reference simile al mio diventa mio padre
		global $adb, $table_prefix;
		
		// crmv@85493 crmv@171021
		$reflist = $this->splitMrefs($mreferences);
		if (count($reflist) == 0) return false;

		$result = $adb->limitpQuery(
			"SELECT {$this->table_name}.messagesid FROM {$this->table_name}
			INNER JOIN {$table_prefix}_messages_mref ON {$table_prefix}_messages_mref.messagesid = {$this->table_name}.{$this->table_index}
			WHERE deleted = 0 AND smownerid = ? AND folder = ? AND {$table_prefix}_messages_mref.mreference IN (".generateQuestionMarks($reflist).")
				AND {$this->table_name}.{$this->table_index} <> ? AND mdate < ?
			ORDER BY mdate DESC",
			0,1,
			array($this->column_fields['assigned_user_id'],$folder,$reflist,$this->id,$this->column_fields['mdate'])
		);
		// crmv@85493e crmv@171021e

		if ($result && $adb->num_rows($result)>0) {
			$messagesid = $adb->query_result($result,0,$this->table_index);
			$father = $this->getFather($messagesid, $folder);
			if (!empty($messagesid) && !empty($father)) {
				$this->insertIntoTh($folder,$father,$this->id);
			}
			return true;
		}
		return false;
	}

	// crmv@85493
	function rebuildMrefTable() {
		global $adb, $table_prefix;
		
		// empty the table
		if ($adb->isMysql()) {
			$adb->query("TRUNCATE TABLE {$table_prefix}_messages_mref");
		} else {
			$adb->query("DELETE FROM {$table_prefix}_messages_mref");
		}
		
		$query = "SELECT messagesid, mreferences FROM {$this->table_name} WHERE deleted = 0 AND mreferences IS NOT NULL"; //crmv@171021
		($adb->isMssql()) ? $query .= " AND mreferences NOT LIKE ''" : $query .= " AND mreferences != ''";
		$result = $adb->query($query);
		if ($result && $adb->num_rows($result)>0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$messagesid = $row[$this->table_index];
				$refs = trim($row['mreferences']);
				if ($refs) {
					$this->insertMrefs($messagesid, $refs);
				}
			}
		}
	}
	
	function splitMrefs($mrefs) {
		$list = array();
		
		// convert strange spaces to regular space
		$mrefs = str_replace(array("\t", "\n", "\r"), "", $mrefs);
		
		// split
		$refs = array_filter(array_map('trim', explode(' ', $mrefs)));
		
		if (count($refs) > 0) {
			foreach ($refs as $mref) {
				// now explode again, because some stupid mrefs are not space separated
				$refs2 = preg_split('/><|>,\s*</', $mref, null, PREG_SPLIT_NO_EMPTY);
				if (count($refs2) > 1) {
					foreach ($refs2 as $mref) {
						if ($mref[0] != '<') $mref = "<".$mref;
						if (substr($mref, -1) != '>') $mref .= ">";
						$list[] = $mref;
					}
				} else {
					// single mref
					$mref = trim($mref, ",;");
					if ($mref[0] != '<') $mref = "<".$mref;
					if (substr($mref, -1) != '>') $mref .= ">";
					$list[] = $mref;
				}
			}
		}
		
		return $list;
	}
	
	function insertMrefs($messagesid, $mrefs) {
		$refs = $this->splitMrefs($mrefs);
		if (is_array($refs) && count($refs) > 0) {
			foreach ($refs as $mref) {
				$this->insertMref($messagesid,$mref);
			}
		}
	}
	
	function insertMref($messagesid, $mref) {
		global $adb, $table_prefix;
		
		// sanitize mref
		$mref = trim(str_replace(array('&lt;', '&gt;'), array('<', '>'), $mref));
		
		// insert
		if ($adb->isMysql()) {
			$adb->pquery("INSERT IGNORE INTO {$table_prefix}_messages_mref (messagesid, mreference) VALUES (?,?)", array($messagesid, $mref));
		} else {
			$result = $adb->pquery("SELECT messagesid FROM {$table_prefix}_messages_mref WHERE messagesid = ? AND mreference = ?",array($messagesid, $mref));
			if ($result && $adb->num_rows($result) == 0) {
				$adb->pquery("INSERT INTO {$table_prefix}_messages_mref (messagesid, mreference) VALUES (?,?)", array($messagesid, $mref));
			}
		}
	}
	
	function deleteMref($messagesid, $mref) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$table_prefix}_messages_mref WHERE messagesid = ? AND mreference = ?",array($messagesid, $mref));
	}
	
	function deleteMrefs($messagesid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$table_prefix}_messages_mref WHERE messagesid = ?",array($messagesid));
	}
	
	function getMrefs($messagesid) {
		// TODO
	}
	
	function searchMref($search) {
		$msgids = array();
		// TODO
		return $msgids;
	}
	// crmv@85493e

	/* TODO
	function adoptReferenceChildren($folder,$mreferences) {
		// se trovo Messaggi piu recenti con reference simile al mio diventano miei figli
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT {$this->table_index} FROM {$this->table_name} WHERE deleted = 0 AND smownerid = ? AND folder = ? AND mreferences LIKE ? AND {$this->table_index} <> ? AND mdate >= ?", //crmv@171021
			array($this->column_fields['assigned_user_id'],$folder,"%{$mreferences}%",$this->id,$this->column_fields['mdate']));
		if ($result && $adb->num_rows($result)>0) {
			while ($row=$adb->fetchByAssoc($result)) {
				$messagesid = $row[$this->table_index];
				$father = $this->getFather($messagesid, $folder);
				if (!empty($messagesid) && !empty($father) && ($messagesid == $father)) {
					//delete vecchie righe...
					$this->insertIntoTh($folder,$this->id,$messagesid);
				}
			}
			return true;
		}
		return false;
	}
	*/
	function getFather($record,$folder='') {
		global $adb, $table_prefix, $current_folder;
		if (empty($folder)) {
			$folder = $current_folder;
		}
		//crmv@171021
		$query = "SELECT messageFather.messagesid
			FROM {$table_prefix}_messages_th
			INNER JOIN {$table_prefix}_messages messageSon ON messageSon.messagesid = {$table_prefix}_messages_th.son
			INNER JOIN {$table_prefix}_messages messageFather ON messageFather.messagesid = {$table_prefix}_messages_th.father
			WHERE messageFather.deleted = 0 AND messageSon.deleted = 0
			AND {$table_prefix}_messages_th.folder = ? AND messageSon.messagesid = ?";
		//crmv@171021e
		$result = $adb->pquery($query,array($folder,$record));
		if ($result && $adb->num_rows($result) > 0) {
			$father = $adb->query_result($result,0,'messagesid');
			return $father;
		}
		return false;
	}

	function getChildren($father,$folder='',$return_count=false,$select='') {
		global $adb, $table_prefix, $current_folder;
		
		// crmv@138980
		if (empty($father)){
			return $return_count ? 0 : Array();
		}
		// crmv@138980e
		
		if (empty($folder)) {
			$folder = $current_folder;
		}
		if (empty($select)) {
			$select = 'DISTINCT messageSon.messagesid';
		}
		$query = "SELECT $select as \"messagesid\"";
		if ($adb->isMssql() || $adb->isOracle()) $query .= ", messageSon.mdate";	//crmv@63611
		//crmv@171021
		$query .= " FROM {$table_prefix}_messages_th
			INNER JOIN {$table_prefix}_messages messageFather ON messageFather.messagesid = {$table_prefix}_messages_th.father
			INNER JOIN {$table_prefix}_messages messageSon ON messageSon.messagesid = {$table_prefix}_messages_th.son
			WHERE messageFather.deleted = 0 AND messageSon.deleted = 0
			AND {$table_prefix}_messages_th.folder = ? AND messageFather.messagesid = ?
			ORDER BY messageSon.mdate DESC";
		//crmv@171021e
		$result = $adb->pquery($query,array($folder,$father));
		$count = $adb->num_rows($result);
		if ($result && $count > 0) {
			if ($return_count) {
				return $count;
			} else {
				$children = array();
				while($row=$adb->fetchByAssoc($result)) {
					$children[] = $row['messagesid'];
				}
				return $children;
			}
		}
	}

	function getParents($record,$folder='') {
		$father = $this->getFather($record,$folder);
		if ($father) {
			$children = $this->getChildren($father,$folder);
			if (!empty($children)) {
				$children = array_diff($children,array($record));
				return $children;
			}
		}
		return false;
	}

	function appendMessage($sendmail, $account, $specialFolder, $parentids='') {	//crmv@84628
		$this->setAccount($account);
		$specialFolders = $this->getSpecialFolders(false);	//crmv@53929
		$folder = $specialFolders[$specialFolder];	//crmv@84628
		if (empty($folder)) {
			return false;
		}
		//crmv@53929
		try {
			$this->getZendMailStorageImap();
		} catch (Exception $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			return false;
		}
		//crmv@53929e

		//crmv@34888
		$sendmail->resetErrors(); // crmv@180739 reset errors 
        //crmv@34888e
		$sendmail->Mailer = 'sendmail';
		if ($specialFolder == 'Drafts') $sendmail->addCustomHeader("Content-Class: VTECRM-DRAFT");	//crmv@84628
		// crmv@198780
		$header = $sendmail->CreateHeader($sendmail->message_id);
		$body = $sendmail->CreateBody();
		if (empty($body)) $body = $sendmail->AltBody;
		$message = "$header\r\n$body";
		// crmv@198780e
		$flags = array(Zend\Mail\Storage::FLAG_SEEN);
		if ($specialFolder == 'Drafts') $flags[] = Zend\Mail\Storage::FLAG_DRAFT;	//crmv@84628
		try {
			self::$mail->appendMessage($message, $folder, $flags);
			// set/update relations
			if (!empty($parentids)) {
				$ids = array_filter(explode('|', $parentids));
				foreach ($ids as $relid) {
					list($elid, $fieldid) = explode('@', $relid, 2);
					if (strpos($elid,'x') !== false) {
						$elid = explode('x',$elid);
						$elid = $elid[1];
					}
					$mod = getSalesEntityType($elid);
					if ($mod) {
						($specialFolder == 'Drafts') ? $this->save_related_module_small($sendmail->message_id, $mod, $elid, '') : $this->save_related_module_small($sendmail->message_id, $mod, $elid, $sendmail->Subject);	// crmv@81338 crmv@86194
					}
				}
			}
			return true;
		} catch (Zend\Mail\Exception\RuntimeException $e) {
			$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
			return false;
		}
	}

	function moveMessage($folder,$skip_fetch=false) {
		parent::trash('Messages', $this->id);
		$this->addToPropagationCron('move', array(
			'userid'=>$this->column_fields['assigned_user_id'],
			'account'=>$this->column_fields['account'],
			'folder'=>$this->column_fields['folder'],
			'uid'=>$this->column_fields['xuid'],
			'new_folder'=>$folder,
			'skip_fetch'=>$skip_fetch
		));
	}
	
	function propagateMoveMessage($userid,$account,$folder,$uid,$new_folder,$skip_fetch=false) {
		$focus = CRMEntity::getInstance('Messages');
				
		$focus->setAccount($account);
		$focus->getZendMailStorageImap($userid);
		$focus->selectFolder($folder);
        //crmv@204525
        try {
            $messageId = self::$mail->getNumberByUniqueId($uid);
        } catch(Exception $e) {
            if ($e->getMessage() == 'unique id not found') {
                return;
            }
        }
        self::$mail->moveMessage($messageId,$new_folder);
        //crmv@204525e

		//fetch new message from destination folder
		if (!$skip_fetch) {
			global $current_user;
			$tmp = $current_user->id;
			$current_user->id = $userid;
			
			$focus->fetchNews($new_folder);
			
			$current_user->id = $tmp;
		}
	}

	function massMoveMessage($account,$old_folder,$folder) {
		global $adb, $table_prefix, $currentModule, $current_user;
		$ids = getListViewCheck($currentModule);

		if (!empty($ids) && is_array($ids)) {
			$idstring = implode(',',$ids);
			$result = $adb->query("SELECT messagesid, xuid FROM {$this->table_name} WHERE messagesid in ({$idstring})");
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					parent::trash('Messages', $row['messagesid']);
					$uids[] = $row['xuid'];
				}
				$this->addToPropagationCron('move_mass', array(
					'userid'=>$current_user->id,
					'account'=>$account,
					'folder'=>$old_folder,
					'uid'=>$uids,
					'new_folder'=>$folder,
				));
			}
		}
	}
	
	function propagateMassMoveMessage($userid,$account,$folder,$uids,$new_folder) {
		$focus = CRMEntity::getInstance('Messages');

		$focus->setAccount($account);
		$focus->getZendMailStorageImap($userid);
		$focus->selectFolder($folder);
		foreach($uids as $uid) {
            //crmv@204525
            try {
                $messageId = self::$mail->getNumberByUniqueId($uid);
            } catch(Exception $e) {
                if ($e->getMessage() == 'unique id not found') {
                    continue;
                }
            }
            self::$mail->moveMessage($messageId,$new_folder);
            //crmv@204525e
		}
		
		global $current_user;
		$tmp = $current_user->id;
		$current_user->id = $userid;
		
		$focus->fetchNews($new_folder,count($uids));
		
		$current_user->id = $tmp;
	}

	function beforeTrashFunctions($record) {
		$focus = CRMEntity::getInstance('Messages');
		$focus->id = $record;
		//crmv@80636
		$result = $focus->retrieve_entity_info($record,'Messages',false);
		if (in_array($result,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND'))) {
			return false;
		}
		//crmv@80636e
		// functions
		$focus->removeFromThread();
	}

	function removeFromThread() {
		global $adb, $table_prefix;
		$record = $this->id;
		$folder = $this->column_fields['folder'];
		$father = $this->getFather($record,$folder);
		if (empty($father)) {
			$father = $record;
		}
		if ($record == $father) {
			$children = $this->getChildren($record,$folder);
			if (!empty($children)) {
				// delete _messages_th
				$adb->pquery("delete from {$table_prefix}_messages_th where father = ? and folder = ? and son = ?",array($record,$folder,$record));
				// if it has children, set new father and reload lastson
				if (count($children) > 1) {
					$newfather = $children[count($children)-2];
					$adb->pquery("update {$table_prefix}_messages_th set father = ? where folder = ? and father = ?",array($newfather,$folder,$record));
					$this->updateLastSon($folder,$newfather);
				}
			}
		} else {
			// if it is son, delete from _messages_th and reload lastson
			$adb->pquery("delete from {$table_prefix}_messages_th where father = ? and folder = ? and son = ?",array($father,$folder,$record));
			$this->updateLastSon($folder,$father);
		}
	}

	function getNonAdminAccessControlQuery($module,$user,$scope='',$join_cond=''){
		//crmv@131239
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			global $table_prefix;
			$userid = $user->id;
			require('user_privileges/requireUserPrivileges.php'); // crmv@39110
			require('user_privileges/sharing_privileges_'.$user->id.'.php');
			$query = ' ';
			$tabId = getTabid($module);
			if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2]
					== 1 && $defaultOrgSharingPermission[$tabId] == 3) {
						$tableName = 'vt_tmp_u'.$user->id;
						$sharingRuleInfoVariable = $module.'_share_read_permission';
						$sharingRuleInfo = $$sharingRuleInfoVariable;
						$sharedTabId = null;
						if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
								count($sharingRuleInfo['GROUP']) > 0
								|| count($sharingRuleInfo['USR']) > 0)) {
									$tableName = $tableName.'_t'.$tabId;
									$sharedTabId = $tabId;
								}elseif($module == 'Calendar' || !empty($scope)) {
									$tableName .= '_t'.$tabId;
								}
								$this->setupTemporaryTable_tmp($tableName, $sharedTabId, $user, $current_user_parent_role_seq, $current_user_groups); // crmv@63349
					}
		}
		//crmv@131239e
		return '';
	}
	
	//crmv@47243	crmv@61173
	function getNonAdminUserAccessQuery($user,$parentRole,$userGroups){
		$defOrgSharingPermission = getAllDefaultSharingAction();
		if ($defOrgSharingPermission[getTabid('Messages')] == 8) {
			global $table_prefix;
			$query = "select id from (SELECT id from ".$table_prefix."_users where id = '$user->id'";
			return $query;
		} else {
			return parent::getNonAdminUserAccessQuery($user,$parentRole,$userGroups);		
		}
	}
	//crmv@47243e	crmv@61173e

	// crmv@63349
	function getQueryExtraJoin() {
		//crmv@79192
		$sql = '';
		global $table_prefix, $currentModule, $current_user, $current_folder, $current_account;
		if ($current_folder == 'Flagged') {
			$sql .= " INNER JOIN (
				SELECT MIN({$this->table_name}.{$this->table_index}) AS {$this->table_index}
				FROM {$this->table_name}
				WHERE {$this->table_name}.deleted = 0
					AND {$this->table_name}.smownerid = {$current_user->id}
					AND {$this->table_name}.mtype = 'Webmail'";
			if ($current_account != 'all') $sql .= " AND {$this->table_name}.account = {$current_account}"; // crmv@192843
			$sql .= " AND {$this->table_name}.flagged = 1
				GROUP BY {$this->table_name}.messagehash
			) flagged_messages ON flagged_messages.{$this->table_index} = {$this->table_name}.{$this->table_index}";
		}
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
			$sql .= $this->getQueryExtraJoin_tmp();
		} else {
			$sql .= $this->getQueryExtraJoin_notmp();
		}
		return $sql;
		//crmv@79192e
	}

	function getQueryExtraJoin_notmp() {
		global $adb, $table_prefix, $current_folder, $current_user;

		$sql = $query = '';
		if ($current_folder == 'Shared') {
			$tableName = $table_prefix."_modcomments_msgrel";
			$sql = " INNER JOIN $tableName ON $tableName.userid = {$current_user->id} AND $tableName.messagesid = {$this->table_name}.{$this->table_index}";
		}
		return $sql;
	}
	// crmv@63349e

	function getQueryExtraJoin_tmp() { // crmv@63349
		global $adb, $current_folder, $current_user;
		$sql = $query = '';
		if ($current_folder == 'Shared') {
			$query = $this->getRelatedModComments(true);
		}
		if (!empty($query)) {
			$tableName = 'vt_tmp_s_'.$current_user->id;
			if ($adb->isMysql()) {
				$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".$query;
				$result = $adb->query($query);
			} else {
				if (!$adb->table_exist($tableName,true)){
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);
				}
				$tableName = $adb->datadict->changeTableName($tableName);
				$query = "insert into $tableName $query where not exists (select * from $tableName where $tableName.id = un_table.id)";
				$result = $adb->query($query);
			}
			$sql = " INNER JOIN $tableName ON $tableName.id = {$this->table_name}.{$this->table_index}";
		}
		return $sql;
	}

	function getQueryExtraWhere() {
		global $current_account, $current_folder, $current_user, $thread;
		$sql = '';
		if ($current_folder == 'Links') {
			$sql .= " and {$this->table_name}.mtype = 'Link'";
			$sql .= " and {$this->table_name}.smownerid = {$current_user->id}"; //crmv@171021
			$sql .= " and {$this->table_name}.folder <> 'vteScheduled'"; // crmv@187622
		// crmv@187622
		} elseif ($current_folder == 'vteScheduled') {
			if ($current_account != 'all') $sql .= " and {$this->table_name}.account = '$current_account'"; // crmv@192843
			$sql .= " and {$this->table_name}.folder = '$current_folder'";
			$sql .= " and {$this->table_name}.smownerid = {$current_user->id}";
			$sql .= " and {$this->table_name}.mtype = 'Link'";
		// crmv@187622e
		} elseif (in_array($current_folder, array('Shared','Flagged'))) {	//crmv@79192
			// do nothing, checks done in getQueryExtraJoin
		} elseif (!empty($current_folder)) {
			if ($current_account != 'all') $account_condition = " and {$this->table_name}.account = '{$current_account}'"; // crmv@192843
			if ($current_account == 'all') {
				$folders = $this->getAllSpecialFolders($current_folder); // crmv@192843
				$tmp = array();
				foreach($folders as $account => $folder) {
					$tmp[] = "({$this->table_name}.account = '{$account}' AND {$this->table_name}.folder = '{$folder[$current_folder]}')"; // crmv@192843
				}
				$account_condition = ' AND ('.implode(' OR ',$tmp).')';
			} else {
				$sql .= " and {$this->table_name}.folder = '$current_folder'";
			}
			$sql .= " and {$this->table_name}.smownerid = {$current_user->id}"; //crmv@171021
			$sql .= " and {$this->table_name}.mtype = 'Webmail'";
		}
		$sql .= $account_condition;
		if (!empty($thread)) {
			$children = $this->getChildren($thread);
			if (!empty($children)) {
				$sql .= " and {$this->table_name}.messagesid in (".implode(',',$children).")";
			} else {
				$sql .= " and {$this->table_name}.messagesid in (0)";	//force empty list
			}
		}
		return trim($sql);
	}

	//crmv@47243	crmv@61173
	function getAdvancedPermissionFunction($is_admin,$module,$actionname,$record_id='') {
		
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$defOrgSharingPermission = getAllDefaultSharingAction(); //crmv@160797
		
		if (in_array($actionname,array('Import','Export','Merge','DuplicatesHandling'))) {
			return 'no';
		} elseif (in_array($actionname,array('PopupDetailForm'))) {	// real check done in the file
			return 'yes';
		}
		//if (!$is_admin && !empty($record_id)) {	//crmv@44747: give to admin all permissions for workflow
		if (!empty($record_id)) {	//crmv@55336
			global $current_user, $adb, $table_prefix;
			
			$smownerid = getSingleFieldValue($table_prefix.'_messages', 'smownerid', 'messagesid', $record_id);

			// the owner can do everything (performance fix, avoid following code when not needed)
			if ($smownerid == $current_user->id) return 'yes';
			
			// only owner can delete
			//crmv@153789
			if ($actionname == 'Delete' && $_REQUEST['module'] != $_REQUEST['return_module']) {
				// deleting relation
				if ($current_user->id == $smownerid) {
					return 'yes';
				} else {
					// check if the current user has a message with the same messagehash
					$messagehash = getSingleFieldValue($table_prefix.'_messages', 'messagehash', 'messagesid', $record_id);
					$result = $adb->pquery("select messagesid from {$table_prefix}_messages where deleted = 0 and smownerid = ? and messagehash = ?", array($current_user->id,$messagehash)); //crmv@171021
					if ($result && $adb->num_rows($result) > 0) {
						return 'yes';
					} else {
						return 'no';
					}
				}
			} elseif ($actionname == 'Delete' && $current_user->id != $smownerid) {
				//echo 'delete';
				return 'no';
			}
			//crmv@153789e

			$mvisibility = getSingleFieldValue($table_prefix.'_messages', 'mvisibility', 'messagesid', $record_id);
			if ($mvisibility == 'Public') {
				//echo 'public';
				return 'yes';
			}

			//crmv@61173
			$mtype = getSingleFieldValue($table_prefix.'_messages', 'mtype', 'messagesid', $record_id);
			if ($mtype == 'Link' && in_array($actionname,array('EditView','Delete'))) {
				//echo 'link';
				return 'no';
			}
			//crmv@61173e

			$account = getSingleFieldValue($table_prefix.'_messages', 'account', 'messagesid', $record_id);
			$this->setAccount($account);
			// crmv@63349
			if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
				if (in_array($record_id,$this->getRelatedModComments())) return 'yes';
			} else {
				if ($this->isMessageRelatedModComments($record_id)) return 'yes';
			}
			// crmv@63349e

			$tabid = getTabid($module);
			
			// check owner
			// crmv@63349
			if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
				$tableName = 'vt_tmp_u'.$current_user->id;
				$sharingRuleInfoVariable = $module.'_share_read_permission';
				$sharingRuleInfo = $$sharingRuleInfoVariable;
				if(!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 ||
						count($sharingRuleInfo['GROUP']) > 0
						|| count($sharingRuleInfo['USR']) > 0)) {
					$tableName = $tableName.'_t'.$tabid;
				}elseif(!empty($scope)) {
					$tableName .= '_t'.$tabid;
				}
				if (empty($current_user_parent_role_seq)) {
					$user_role = $current_user->column_fields['roleid'];
					$user_role_info = getRoleInformation($user_role);
					$current_user_parent_role_seq = $user_role_info[$user_role][1];
				}
				if (empty($current_user_groups)) {
					$userGroupFocus = new GetUserGroups();
					$userGroupFocus->getAllUserGroups($current_user->id);
					$current_user_groups = $userGroupFocus->user_groups;
				}
				$this->setupTemporaryTable($tableName, $tabid, $current_user, $current_user_parent_role_seq, $current_user_groups);
				if($adb->isMssql()) $tableName = $adb->datadict->changeTableName($tableName);	//crmv@60402
				$result = $adb->pquery("select id from $tableName where id = ?",array($smownerid));
				if ($result && $adb->num_rows($result) > 0) {
					return 'yes';
				}
			} else {
				if ($smownerid != $current_user->id) {
					$tutables = TmpUserTables::getInstance();
					$tumtables = TmpUserModTables::getInstance();
					// crmv@146653 crmv@160797
					if ($defOrgSharingPermission[$tabid] == 3) {
						if ($tutables->hasSubUser($current_user->id, $smownerid) || $tumtables->hasSubUser($current_user->id, $smownerid, 'Messages')) {
							return 'yes';
						}
					} elseif ($defOrgSharingPermission[$tabid] == 8) {
						if ($tumtables->hasSubUser($current_user->id, $smownerid, 'Messages')) {
							return 'yes';
						}
					}
					// crmv@146653e crmv@160797e
				} else {
					return 'yes';
				}
			}
			// crmv@63349e

			if ($defOrgSharingPermission[$tabid] == 0) {
				$rm = RelationManager::getInstance();
				$relIds = $rm->getRelatedIds($module,$record_id);
				foreach($relIds as $id) {
					$m = getSalesEntityType($id);
					if (isPermitted($m, 'DetailView', $id) == 'yes' && in_array($actionname,array('DetailView','Download','DownloadAttachments','Print','PrintHeader','ViewDocument','ViewImage'))) {	//crmv@61173 crmv@66929 crmv@89037 crmv@128077
						//echo 'ereditato '.$defOrgSharingPermission[$tabid];
						return 'yes';
					}
				}
				//crmv@56829
				$result = $adb->pquery("select id from {$table_prefix}_messages_recipients where messagesid = ?",array($record_id));
				if ($result && $adb->num_rows($result) > 0) {
					while($row=$adb->fetchByAssoc($result)) {
						$id = $row['id'];
						$m = getSalesEntityType($id);
						if (isPermitted($m, 'DetailView', $id) == 'yes') return 'yes';
					}
				}
				//crmv@56829e
			}
			
			return 'no';
		}
	}
	//crmv@47243e	crmv@61173e

	/*
	 * $params : array width column_fields of message
	 * ex. $params = array('subject'=>'Test','description'=>'test message','mto'=>'to@domain.com','mfrom'=>'from@domain.org',...);
	 * NB. you can also relate message to records by $params['parent_id'] (permitted formats: 12, 3x12, 3x12|3x14, 12@200|14@202)
	 *
	 * TODO : gestire l'invio di allegati passando in send_mail_attachment una stringa o un array di percorsi di file da inviare
	 */
	function send($params,$append=true,$queue=false) {	// crmv@129149
		$queue = (!$append && $queue);	// crmv@129149 enable queue only if append is false
		$mail_tmp = (!empty($params['mail_tmp']) ? $params['mail_tmp'] : '');
		$mail_status = send_mail(
			'Emails',
			$params['mto'],
			(!empty($params['mfrom_n']) ? $params['mfrom_n'] : $params['mfrom']),
			$params['mfrom'],
			$params['subject'],
			$params['description'],
			$params['mcc'],
			$params['mbcc'],
			(!empty($params['send_mail_attachment']) ? $params['send_mail_attachment'] : 'all'),
			(!empty($params['send_mail_emailid']) ? $params['send_mail_emailid'] : 0),
			(!empty($params['send_mail_logo']) ? $params['send_mail_logo'] : ''),
			(!empty($params['send_mail_newsletter_params']) ? $params['send_mail_newsletter_params'] : ''),
			$mail_tmp,
			(!empty($params['send_mail_messageid']) ? $params['send_mail_messageid'] : ''),
			(!empty($params['send_mail_message_mode']) ? $params['send_mail_message_mode'] : ''),
			$queue	// crmv@129149
		);
		if ($append) {
			$append_status = false;

			$mainAccount = $this->getMainUserAccount();
			$account = (!empty($params['account']) ? $params['account'] : $mainAccount['id']);

			if ($mail_status == 1 && !empty($account)) {
				try {
					$append_status = append_mail(
						$mail_tmp,
						$account,
						$params['parent_id'],
						$params['mto'],
						(!empty($params['mfrom_n']) ? $params['mfrom_n'] : $params['mfrom']),
						$params['mfrom'],
						$params['subject'],
						$params['description'],
						$params['mcc'],
						$params['mbcc']
					);
				} catch (Exception $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					//echo $e->getMessage()."\n";
				}
			}
			if ($append_status === false) {
				$focus = CRMentity::getInstance('Messages');
				$focus->saveCacheLink($params);
			}
		}
		return $mail_status;
	}

	function setRecipients($messagesid,$recipientids) {
		global $adb, $table_prefix;
		if (!is_array($recipientids)) {
			$recipientids = explode('|',$recipientids);
		}
		$recipientids = array_filter($recipientids);
		if (!empty($recipientids)) {
			foreach ($recipientids as $relid) {
				list($elid, $fieldid) = explode('@', $relid, 2);
				// check existence
				$r = $adb->pquery("select messagesid from {$table_prefix}_messages_recipients where messagesid = ? and id = ? and fieldid = ?",array($messagesid,$elid,$fieldid));
				if ($r && $adb->num_rows($r) == 0) {
					$adb->pquery("insert into {$table_prefix}_messages_recipients (messagesid,id,fieldid) values (?,?,?)",array($messagesid,$elid,$fieldid));
				}
			}
		}
	}

	function getRecipients($format='array') {
		global $adb, $table_prefix;
		$recipientids = array();
		$result = $adb->pquery("select id, fieldid from {$table_prefix}_messages_recipients where messagesid = ?",array($this->id));
		if($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByAssoc($result)) {
				$recipientids[] = $row['id'].'@'.$row['fieldid'];
			}
		}
		if ($format == 'string') {
			return implode('|',$recipientids);
		} else {
			return $recipientids;
		}
	}

	function setSendMode($messagesid,$send_mode) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_messages set send_mode = ? where messagesid = ?",array($send_mode,$messagesid));
	}

	function setVisibility($messagesid,$visibility) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_messages set mvisibility = ? where messagesid = ?",array($visibility,$messagesid));
	}

	function checkThreadFlag($flag,$id,$thread) {
		//TODO do a unique query and cache values
		global $current_account, $current_folder, $current_user, $adb, $table_prefix;
		if ($flag == 'unseen') {
			$condition = 'AND seen = 0';
		} elseif ($flag == 'flagged') {
			$condition = 'AND flagged = 1';
		}
		$query = "select messagesid from {$table_prefix}_messages where deleted = 0 ".$condition; //crmv@171021
		$children = $this->getChildren($thread,'',false,'distinct messageSon.messagehash');
		$query .= " and {$this->relation_table_id} in (".generateQuestionMarks($children).")";
		$params = array($children);
		if ($current_account != 'all') {
			$query .= " and account = ?";
			$params[] = $current_account;
		}
		$query .= " and folder = ? and smownerid = ?";
		$params[] = array($current_folder,$current_user->id);
		$result = $adb->pquery($query,$params);
		if ($result && $adb->num_rows($result) > 0) {
			return true;
		}
		return false;
	}

	//crmv@44037
	function getAccountSignature($id) {
		$account = $this->getUserAccounts('',$id);
		$return = $account[0]['signature'];
		$return = str_replace("\r",'',$return);
		$return = str_replace("\n",'',$return);
		return $return;
	}
	//crmv@44037e
	
	//crmv@3086m
	function relatedlist_preview_link($module, $entity_id, $current_module, $header, $relation_id) {
		return null;
	}
	//crmv@3086me
	
	//crmv@48693
	function getAdvancedSearchOptionString($old_mode=false,&$controller,&$queryGenerator) {
		$module = $queryGenerator->getModule();
		$meta = $queryGenerator->getMeta($module);
		$moduleFields = $meta->getModuleFields();
		$i =0;
		foreach ($moduleFields as $fieldName=>$field) {
			if(!in_array($field->getPresence(), array('0','2'))){
				continue;
			}
			if(!in_array($fieldName, array('subject','cleaned_body','mdate','seen'))){
				continue;
			}
			if($field->getFieldDataType() == 'reference' || $field->getFieldDataType() == 'owner') {
				$typeOfData = 'V';
			} else if($field->getFieldDataType() == 'boolean') {
				$typeOfData = 'C';
			} else {
				$typeOfData = $field->getTypeOfData();
				$typeOfData = explode("~",$typeOfData);
				$typeOfData = $typeOfData[0];
			}
			$label = getTranslatedString($field->getFieldLabelKey(), $module);
			if(empty($label)) {
				$label = $field->getFieldLabelKey();
			}
			$selected = '';
			if($i++ == 0) {
				$selected = "selected";
			}
			// place option in array for sorting later
			if ($old_mode){
				$tableName = $field->getTableName();
				$columnName = $field->getColumnName();
				$OPTION_SET[$fieldName] = "<option value=\'$tableName.$columnName::::$typeOfData\' $selected>$label</option>";
			}
			else
				$OPTION_SET[$fieldName] = "<option value=\'$fieldName::::$typeOfData\' $selected>$label</option>";
		}
		if (!is_array($OPTION_SET)) return '';
		
		$options = array(
			"<option value=\'senders::::V\'>".getTranslatedString('Senders','Messages')."</option>",
			"<option value=\'recipients::::V\'>".getTranslatedString('Recipients','Messages')."</option>",
			$OPTION_SET['subject'],
			$OPTION_SET['cleaned_body'],
			$OPTION_SET['mdate'],
			$OPTION_SET['seen'],
			// TODO	"<option value=\'links::::C\'>".getTranslatedString('LBL_FLAG_LINK','Messages')."</option>",
		);
		return implode('',$options);
	}
	
	function addUserSearchConditions($input,&$queryGenerator) {
		global $log,$default_charset;
		if($input['searchtype']=='advance') {
			if(empty($input['search_cnt'])) {
				return ;
			}
			$noOfConditions = vtlib_purify($input['search_cnt']);
			if($input['matchtype'] == 'all') {
				$matchType = $queryGenerator::$AND;
			} else {
				$matchType = $queryGenerator::$OR;
			}
			if($queryGenerator->getconditionInstanceCount() > 0) {
				$queryGenerator->startGroup($queryGenerator::$AND);
			} else {
				$queryGenerator->startGroup('');
			}
			for($i=0; $i<$noOfConditions; $i++) {
				$fieldInfo = 'Fields'.$i;
				$condition = 'Condition'.$i;
				$value = 'Srch_value'.$i;

				list($fieldName,$typeOfData) = explode("::::",str_replace('\'','',
						stripslashes($input[$fieldInfo])));
				$moduleFields = $queryGenerator->getModuleFields();
				$field = $moduleFields[$fieldName];
				
				if (in_array($fieldName,array('senders','recipients'))) {
					$whereFields = $queryGenerator->getWhereFields();
					if(($i-1) >= 0 && !empty($whereFields)) {
						$queryGenerator->addConditionGlue($matchType);
					}
					$operator = str_replace('\'','',stripslashes($input[$condition]));
					$searchValue = $input[$value];
					$searchValue = urldecode($searchValue); //crmv@60585
					$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167702
							$searchValue) : $searchValue;
					if (in_array($operator,array('n','k'))) {
						$intertnalGlue = $queryGenerator::$AND;
					} elseif (in_array($operator,array('e','s','ew','c'))) {
						$intertnalGlue = $queryGenerator::$OR;
					}
					$queryGenerator->startGroup('');
					if ($fieldName == 'senders') {
						$queryGenerator->addCondition('mfrom', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mfrom_n', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mfrom_f', $searchValue, $operator);
					} elseif ($fieldName == 'recipients') {
						$queryGenerator->addCondition('mto', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mto_n', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mto_f', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mcc', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mcc_n', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mcc_f', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mbcc', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mbcc_n', $searchValue, $operator);
						$queryGenerator->addConditionGlue($intertnalGlue);
						$queryGenerator->addCondition('mbcc_f', $searchValue, $operator);
					}
					$queryGenerator->endGroup();
				/* TODO
				} elseif ($fieldName == 'links') {
					$operator = str_replace('\'','',stripslashes($input[$condition]));
					$searchValue = $input[$value];
					$searchValue = urldecode($searchValue); //crmv@60585
					$searchValue = function_exists(iconv) ? @iconv("UTF-8",$default_charset,
							$searchValue) : $searchValue;
					$subselectCondition = '';
					if (($operator == 'e' && $searchValue == 'Yes') || ($operator == 'n' && $searchValue == 'No')) {
						$subselectCondition = 'in';
					} elseif (($operator == 'n' && $searchValue == 'Yes') || ($operator == 'e' && $searchValue == 'No')) {
						$subselectCondition = 'not in';
					}
					if (!empty($subselectCondition)) {
						$whereFields = $queryGenerator->getWhereFields();
						if(($i-1) >= 0 && !empty($whereFields)) {
							$queryGenerator->addConditionGlue($matchType);
						}
						global $table_prefix;
						$sql = "{$table_prefix}_messages.messagehash $subselectCondition (select messagehash from {$table_prefix}_messagesrel)";
						$queryGenerator->appendToWhereClause($sql);
					}
				*/
				} elseif ($fieldName == 'mdate') {
					if (!$field)
						continue;
					$type = $field->getFieldDataType();
					$whereFields = $queryGenerator->getWhereFields();
					if(($i-1) >= 0 && !empty($whereFields)) {
						$queryGenerator->addConditionGlue($matchType);
					}
					$operator = str_replace('\'','',stripslashes($input[$condition]));
					if (in_array($operator,array('custom','yesterday','today','lastweek','thisweek','lastmonth','thismonth','last60days','last90days'))) {
						$searchValue = urldecode($input[$value]);
						$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167702
							$searchValue) : $searchValue;
						list($start,$end) = explode('|##|',$searchValue);
						if (strlen($start) == 10) $start .= ' 00:00:00';
						if (strlen($end) == 10) $end .= ' 23:59:59';
						$queryGenerator->startGroup('');
						$queryGenerator->addCondition('mdate', $start, 'h');
						$queryGenerator->addConditionGlue($queryGenerator::$AND);
						$queryGenerator->addCondition('mdate', $end, 'm');
						$queryGenerator->endGroup();
					} else {
						$searchValue = $input[$value];
						$searchValue = urldecode($searchValue); //crmv@60585
						$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167702
								$searchValue) : $searchValue;
						$queryGenerator->addCondition($fieldName, $searchValue, $operator);
					}
				} else {
					if (!$field)
						continue;
					$type = $field->getFieldDataType();
					$whereFields = $queryGenerator->getWhereFields();
					if(($i-1) >= 0 && !empty($whereFields)) {
						$queryGenerator->addConditionGlue($matchType);
					}
					$operator = str_replace('\'','',stripslashes($input[$condition]));
					$searchValue = $input[$value];
					$searchValue = urldecode($searchValue); //crmv@60585
					$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167702
							$searchValue) : $searchValue;
					$queryGenerator->addCondition($fieldName, $searchValue, $operator);
				}
			}
			$queryGenerator->endGroup();
		} else {
			return 'continue';
		}
	}
	
	function getAdvCriteriaJS() {
		
		$today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
		$lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));
		$nextmonth1 = date("Y-m-t", strtotime("+1 Month"));

		$lastweek0 = date("Y-m-d",strtotime("-2 week Sunday"));
		$lastweek1 = date("Y-m-d",strtotime("-1 week Saturday"));

		$thisweek0 = date("Y-m-d",strtotime("-1 week Sunday"));
		$thisweek1 = date("Y-m-d",strtotime("this Saturday"));

		$nextweek0 = date("Y-m-d",strtotime("this Sunday"));
		$nextweek1 = date("Y-m-d",strtotime("+1 week Saturday"));

		$next7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+6, date("Y")));
		$next30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+29, date("Y")));
		$next60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+59, date("Y")));
		$next90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+89, date("Y")));
		$next120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+119, date("Y")));

		$last7days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-6, date("Y")));
		$last30days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-29, date("Y")));
		$last60days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-59, date("Y")));
		$last90days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-89, date("Y")));
		$last120days = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-119, date("Y")));

		$currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")));
		$currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", date("d"),   date("Y")));
		$lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")-1));
		$lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")-1));

		$nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   date("Y")+1));
		$nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")+1));

		if(date("m") <= 3)
		{
			$cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")-1));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")-1));
		}
		else if(date("m") > 3 and date("m") <= 6)
		{
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
		}
		else if(date("m") > 6 and date("m") <= 9)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
		}
		else if(date("m") > 9 and date("m") <= 12)
		{
			$nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")+1));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")+1));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",date("Y")));
		}
		$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
		$sjsStr = '<script language="JavaScript" type="text/javaScript">
			var js_date_format = "'.$date_format.'";
			function showADvSearchDateRange(index, type)
			{
				if (type!="custom")
				{
					document.advSearch.elements["startdate"+index].readOnly=true;
					document.advSearch.elements["enddate"+index].readOnly=true;
					getObj("jscal_trigger_date_start"+index).style.visibility="hidden";
					getObj("jscal_trigger_date_end"+index).style.visibility="hidden";
				}
				else
				{
					document.advSearch.elements["startdate"+index].readOnly=false;
					document.advSearch.elements["enddate"+index].readOnly=false;
					getObj("jscal_trigger_date_start"+index).style.visibility="visible";
					getObj("jscal_trigger_date_end"+index).style.visibility="visible";
				}
				if( type == "today" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($today).'";
				}
				else if( type == "yesterday" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($yesterday).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($yesterday).'";
				}
				else if( type == "tomorrow" )
				{

					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($tomorrow).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($tomorrow).'";
				}
				else if( type == "thisweek" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($thisweek0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($thisweek1).'";
				}
				else if( type == "lastweek" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($lastweek0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($lastweek1).'";
				}
				else if( type == "nextweek" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($nextweek0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($nextweek1).'";
				}
				else if( type == "thismonth" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($currentmonth0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($currentmonth1).'";
				}
				else if( type == "lastmonth" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($lastmonth0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($lastmonth1).'";
				}
				else if( type == "nextmonth" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($nextmonth0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($nextmonth1).'";
				}
				else if( type == "next7days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($next7days).'";
				}
				else if( type == "next30days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($next30days).'";
				}
				else if( type == "next60days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($next60days).'";
				}
				else if( type == "next90days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($next90days).'";
				}
				else if( type == "next120days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($today).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($next120days).'";
				}
				else if( type == "last7days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($last7days).'";
					document.advSearch.elements["enddate"+index].value =  "'.getDisplayDate($today).'";
				}
				else if( type == "last30days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($last30days).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($today).'";
				}
				else if( type == "last60days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($last60days).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($today).'";
				}
				else if( type == "last90days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($last90days).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($today).'";
				}
				else if( type == "last120days" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($last120days).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($today).'";
				}
				else if( type == "thisfy" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($currentFY0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($currentFY1).'";
				}
				else if( type == "prevfy" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($lastFY0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($lastFY1).'";
				}
				else if( type == "nextfy" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($nextFY0).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($nextFY1).'";
				}
				else if( type == "nextfq" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($nFq).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($nFq1).'";
				}
				else if( type == "prevfq" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($pFq).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($pFq1).'";
				}
				else if( type == "thisfq" )
				{
					document.advSearch.elements["startdate"+index].value = "'.getDisplayDate($cFq).'";
					document.advSearch.elements["enddate"+index].value = "'.getDisplayDate($cFq1).'";
				}
				else
				{
					document.advSearch.elements["startdate"+index].value = "";
					document.advSearch.elements["enddate"+index].value = "";
				}
				setAdvSearchIntervalDateValue(index);
			}
		</script>';
		return $sjsStr;
	}
	//crmv@48693e
	
	//crmv@63475
	function saveAllDocuments($record) {
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT * FROM {$table_prefix}_messages_attach WHERE messagesid = ? AND document IS NULL AND contentmethod IS NULL",array($record)); // crmv@68357
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$this->saveDocument($record,$row['contentid']);
			}
		}
	}
	function saveDocument($record,$contentid,$linkto=null,$linkto_module=null,$content_part=null,$decode_attachment=true) {	//crmv@84807	crmv@86304

		global $adb, $table_prefix, $root_directory, $currentModule;
		
		$document = CRMEntity::getInstance('Documents'); // crmv@201830
		
		// If contentid has been already converted in Document we use the existing Document
		$documentid = '';
		$result = $adb->pquery("SELECT {$table_prefix}_messages_attach.document FROM {$table_prefix}_messages_attach
								INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_messages_attach.document
								WHERE deleted = 0 AND {$table_prefix}_messages_attach.messagesid = ? AND {$table_prefix}_messages_attach.contentid = ?",
								array($record,$contentid));
		if ($result && $adb->num_rows($result) > 0) {
			$documentid = $adb->query_result($result,0,'document');
		}
		if (empty($documentid)) {
			$this->retrieve_entity_info($record,$currentModule);
			$userid = $this->column_fields['assigned_user_id'];
			//crmv@84807
			if (empty($content_part)) {
				$uid = $this->column_fields['xuid'];
			
				$this->setAccount($this->column_fields['account']);
				try {
					$this->getZendMailStorageImap($userid);
				} catch (Exception $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);
					return;
				}
				$this->selectFolder($this->column_fields['folder']);
			
                //crmv@204525
                try {
                    $messageId = self::$mail->getNumberByUniqueId($uid);
                } catch(Exception $e) {
                    if ($e->getMessage() == 'unique id not found') {
                        return;
                    }
                }
                //crmv@204525e

				$message = self::$mail->getMessage($messageId);
				$parts = $this->getMessageContentParts($message,$messageId,true);	//crmv@59492
				$content_part = $parts['other'][$contentid];
			}
			if (!empty($content_part)) {
				$parameters = $content_part['parameters'];
				//crmv@86304
				// crmv@111124
				$FS = FileStorage::getInstance();
				$FSDB = FileStorageDB::getInstance(); // crmv@205309
				$filename = $FS->sanitizeFilename($parameters['name']);
				// crmv@111124e
				$current_id = $adb->getUniqueID($table_prefix."_crmentity");
				$date_var = date('Y-m-d H:i:s');
				$upload_file_path = decideFilePath();
				// crmv@105191 crmv@205309
				$destPath = $root_directory.$upload_file_path.$current_id."_".$filename;
				if (!empty($content_part['file'])) {	// path of existing file
					$filesize = filesize($content_part['file']);
					$fileType = mime_content_type($content_part['file']);
					
					$r = copy($content_part['file'], $destPath);
					if ($r === false) {
						$r = $FSDB->saveFile($content_part['file'], $destPath, $current_id);
					}
				} else {								// content of file
					$str = $content_part['content'];
					if ($decode_attachment) $str = $this->decodeAttachment($str,$parameters['encoding'],$parameters['charset']);
					$filesize = strlen($str);
					
					// crmv@198701
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$fileType = finfo_buffer($finfo, $str);
					finfo_close($finfo);
					// crmv@198701e
					
					$fp = fopen($destPath, 'wb');
					if ($fp !== false) {
						$r = fwrite($fp,$str,$filesize);
						fclose ($fp);
					} else {
						$r = false;
					}
					if ($r === false) {
						@unlink($destPath);
						$r = $FSDB->saveFileData($str, $destPath, $current_id);
					}
					
				}
				//crmv@86304e
				//crmv@84807e
				//crmv@205309e
		
				$sql1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values(?,?,?,?,?,?)";
				$params1 = array($current_id, $userid, $userid, "Documents Attachment", $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
				$adb->pquery($sql1, $params1);

				// crmv@205309 - finfo moved up
		
				$sql2 = "insert into ".$table_prefix."_attachments(attachmentsid, name, type, path) values(?,?,?,?)";
				$params2 = array($current_id, $filename, $fileType, $upload_file_path); // crmv@198701
				$adb->pquery($sql2, $params2);
		
				// Create document record
				//crmv@86304
				$resFolder = $adb->pquery("select folderid from {$table_prefix}_crmentityfolder where foldername = ?", array('Message attachments'));
				($resFolder && $adb->num_rows($resFolder) > 0) ? $folderid = $adb->query_result($resFolder,0,'folderid') : $folderid = 1;
				//crmv@86304e
				$document->column_fields['notes_title']      = $filename;
				$document->column_fields['filename']         = $filename;
				$document->column_fields['filestatus']       = 1;
				$document->column_fields['filelocationtype'] = 'I';
				$document->column_fields['folderid']         = $folderid;	//crmv@86304
				$document->column_fields['assigned_user_id'] = $userid;
				$document->column_fields['filesize'] = $filesize; // crmv@205309
				$document->column_fields['filetype'] = $fileType; // crmv@198701
				// crmv@105191e

				if (method_exists($document,'autoSetBUMC')) $document->autoSetBUMC('Documents',$current_user->id);	//crmv@93302
				$document->save('Documents');
				$documentid = $document->id;
		
				// Link file attached to document
				$adb->pquery("insert into ".$table_prefix."_seattachmentsrel(crmid, attachmentsid) values(?,?)",Array($documentid, $current_id));
		
				// Link documentid to the real attachment for faster next relations
				$adb->pquery("update {$table_prefix}_messages_attach set document = ? where messagesid = ? and contentid = ?",array($documentid, $record, $contentid));
			}
		}
		// crmv@42752 crmv@110370
		if (!empty($linkto) && !empty($document)) {
			$ids = array();
			// Split the string of ids
			$ids = array_filter(explode(",", trim($linkto,",")));
			// Link document to linkto
			$document->save_related_module('Documents', $documentid, $linkto_module, $ids);
		}
		// Link document to message in any case
		$this->save_related_module($currentModule, $record, 'Documents', $documentid);
		// crmv@42752e crmv@110370e
	}
	//crmv@63475e
	
	//crmv@62340
	public function parseEmlFile($filepath){
		$zend_mail_storage_message = new \Zend\Mail\Storage\Message(array(
			'file' => $filepath
		));
		
		return $zend_mail_storage_message;
	}
	
	//crmv@84807
	public function saveEmlAttachments($record,$other){
		global $adb;
		if (!empty($other)){
			foreach($other as $contentid => $tmp_files){
				$this->saveDocument($record,$contentid,null,null,$other[$contentid]);
			}
		}
		return true;
	}
	//crmv@84807e
	
	public function isEML(){
		$messageid = $this->column_fields['messageid'];
		$compare_str = '_eml';
		$cnt = -1 * abs(strlen($compare_str));
		if(substr($messageid,$cnt) == $compare_str){
			return true;
		}
		else{
			return false;
		}
		
	}
	//crmv@62340e

	// crmv@62340 crmv@84807 crmv@88981 crmv@90941 crmv@129689
	public function parseEML($contentid, &$messagesid, &$error=null, $str=null, $return_column_fields=false) {
		global $adb, $table_prefix, $default_charset;
		
		$userid = $this->column_fields['assigned_user_id'];
		$uid = $this->column_fields['xuid'];
		$accountid = $this->column_fields['account'];
		$folder = $this->column_fields['folder'];

		if (empty($str)) {
			// if the message is already scanned by mailconverter
			if ($this->column_fields['mtype'] == 'Link') {
				$new_messageid = $this->column_fields['messageid']."_{$contentid}_eml";
				$result1 = $adb->pquery("SELECT messagesid FROM {$table_prefix}_messages WHERE deleted = 0 AND messageid = ?", array($new_messageid)); //crmv@171021
				if ($result1 && $adb->num_rows($result1) > 0) {
					$messagesid = $adb->query_result_no_html($result1,0,'messagesid');
					if ($return_column_fields) {
						$focus = CRMEntity::getInstance('Messages');
						$focus->retrieve_entity_info_no_html($messagesid,'Messages');
						return $focus->column_fields;
					}
					return true;
				} else {
					return false;
				}
			}
			
			$this->setAccount($accountid);
			$this->getZendMailStorageImap($userid);
			$this->selectFolder($folder);
            //crmv@204525
            try {
                $messageId = $this->getMailResource()->getNumberByUniqueId($uid);
            } catch(Exception $e) {
                if ($e->getMessage() == 'unique id not found') {
                    return false;
                }
            }
            //crmv@204525e
			$message = $this->getMailResource()->getMessage($messageId);
			$parts = $this->getMessageContentParts($message,$messageId,true);
		
			if (!empty($parts['other'][$contentid])) {
				$content = $parts['other'][$contentid];
				$str = $content['content'];
				$str = $this->decodeAttachment($str,$content['parameters']['encoding'],$content['parameters']['charset']);
			}
		} else {
			$this->loadZendFramework();
		}
		
		if (!empty($str)) {
			$savepath = "./cache/emlattach_{$this->id}_{$contentid}.eml";
			$r = @file_put_contents($savepath,$str);
			if (!$r) {
				$error = 'Unable to save the temporary file';
				return false;
			}
			
			try {
				$eml_message = $this->parseEmlFile($savepath);
			} catch (Exception $e) {
				$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
				@unlink($savepath);
				$error = 'Malformed eml attachment';
				return false;
			}
			unlink($savepath);
			
			$headers = $this->getMessageHeader($eml_message);
			$data = $this->getMessageData($eml_message,$headers['Messageid'], true);
			
			/* crmv@158850 */
			if (!empty($data['header']['Messageid'])) {
				$new_messageid = $data['header']['Messageid']."_{$contentid}_eml";
			} elseif ($this->column_fields['mtype'] == 'Link' && !empty($this->column_fields['messageid'])) {
				$new_messageid = $this->column_fields['messageid']."_{$contentid}_eml";
			} else {
				$error = 'Messageid not found';
				return false;
			}
			
			//check for mail already "scanned"
			$result1 = $adb->pquery("SELECT messagesid FROM {$table_prefix}_messages WHERE deleted = 0 AND messageid = ?", array($new_messageid)); //crmv@171021
			if ($result1 && $adb->num_rows($result1) > 0) {
				$messagesid = $adb->query_result_no_html($result1,0,'messagesid');
				if ($return_column_fields) {
					$focus = CRMEntity::getInstance('Messages');
					$focus->retrieve_entity_info_no_html($messagesid,'Messages');
					return $focus->column_fields;
				}
				return true;
			} else{
				$date = $this->imap2DbDate($data['header']['Date']);	//crmv@49480

				$body = '';
				if (isset($data['text/html'])) {
					$body = $data['text/html'];
					$body = str_replace('&lt;','&amp;lt;',$body);
					$body = str_replace('&gt;','&amp;gt;',$body);
				} elseif (isset($data['text/plain'])) {
					$body = nl2br(htmlentities($data['text/plain'], ENT_COMPAT, $default_charset));
				}
				$body = preg_replace('/[\xF0-\xF7].../s', '', $body);	//crmv@65555
				
				$column_fields = array(
					'subject'=>$data['header']['Subject'],
					'description'=>$body,
					'mdate'=>$date,
					'mfrom'=>$data['header']['From']['email'],
					'mfrom_n'=>$data['header']['From']['name'],
					'mfrom_f'=>$data['header']['From']['full'],
					'mto'=>$data['header']['To']['email'],
					'mto_n'=>$data['header']['To']['name'],
					'mto_f'=>$data['header']['To']['full'],
					'mcc'=>$data['header']['Cc']['email'],
					'mcc_n'=>$data['header']['Cc']['name'],
					'mcc_f'=>$data['header']['Cc']['full'],
					'mbcc'=>$data['header']['Bcc']['email'],
					'mbcc_n'=>$data['header']['Bcc']['name'],
					'mbcc_f'=>$data['header']['Bcc']['full'],
					'mreplyto'=>$data['header']['ReplyTo']['email'],
					'mreplyto_n'=>$data['header']['ReplyTo']['name'],
					'mreplyto_f'=>$data['header']['ReplyTo']['full'],
					'messageid'=>$new_messageid,
					'in_reply_to'=>$data['header']['In-Reply-To'],
					'xuid'=>0,
					'account'=>$accountid,
					'folder'=>'',
					'assigned_user_id'=>$userid,
					'mtype'=>'Link',
					'other'=>$data['other'],
					'parent_id'=>"",
					'mvisibility'=>'Public',
				);
				if ($return_column_fields) return $column_fields;

				$newfocus = CRMentity::getInstance('Messages');
				$messagesid = $newfocus->saveCacheLink($column_fields);
				if(!empty($messagesid)){
					$newfocus->saveEmlAttachments($messagesid,$data['other']);
					return true;
				} else {
					$error = 'Unable to save the attachment';
					return false;
				}
			}
		}
		$error = 'Unknown error';
		return false;
	}
	// crmv@62340e crmv@84807e crmv@88981e crmv@90941e crmv@129689e
	
	//crmv@65328
	function getAttachmentsSize($messageId) {
		global $adb, $table_prefix;
		$size = 0;
		$atts = $this->getAttachmentsInfo();
		if (!empty($atts)) {
			if (empty($atts[0]['parameters']['size'])) {
				if ($this->column_fields['mtype'] == 'Link') {
					$sql = "select t.* from {$table_prefix}_messages_attach a 
					inner join {$table_prefix}_seattachmentsrel s on s.crmid = a.document
					inner join {$table_prefix}_notes n on n.notesid = a.document
					inner join {$table_prefix}_attachments t on t.attachmentsid = s.attachmentsid
					inner join {$table_prefix}_crmentity e on e.crmid = t.attachmentsid
					where messagesid = ? and coalesce(a.document,'') <> '' and e.deleted=0";
					$params = Array($this->id);
					$res = $adb->pquery($sql,$params);
					if ($res && $adb->num_rows($res)>0) {
						while($row=$adb->fetchByAssoc($res)) {
							$filewithpath = $root_directory.$row['path'].$row['attachmentsid']."_".$row['name'];
							if (is_file($filewithpath)) {
								$size += filesize($filewithpath);
							}
						}
					}
				} else {
					$this->setAccount($this->column_fields['account']);
					$this->getZendMailStorageImap($this->column_fields['assigned_user_id']);
					$this->selectFolder($this->column_fields['folder']);
					try {
						$messageId = self::$mail->getNumberByUniqueId($this->column_fields['xuid']);
					} catch(Exception $e) {
						$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
						if ($e->getMessage() == 'unique id not found') {
							return;
						}
					}
					$size = self::$mail->getSize($messageId);
				}
			} else {
				foreach($atts as $contentid => $att) {
					$size += (int)$att['parameters']['size'];
				}
			}
		}
		return $size;
	}
	//crmv@65328e
	
	//crmv@80250
	function isSupportedInlineFormat($filename) {
		$extension = substr(strrchr($filename, "."), 1);
		if(in_array(strtolower($extension),$this->inline_image_supported_extensions)){
			return true;
		}
		return false;
	}
	//crmv@80250e
	
	//crmv@91321
	function isConvertableFormat($filename) {
		$extension = substr(strrchr($filename, "."), 1);
		if(in_array(strtolower($extension),$this->inline_image_convertible_extensions)){
			return true;
		}
		return false;
	}
	//crmv@91321e

	//crmv@81766
	function convertInternalMailerLinks($description) {
		// trasforma in link al compositore interno gli indirizzi email
		$description = preg_replace("/(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/i","\\1<a href=\"javascript:InternalMailer('\\2@\\3','','','','email_addy');\">\\2@\\3</a>",$description);
		// sostituisce mailto con il compositore interno
		$r = '`\<a([^>]+)href\=\"mailto\:([^">]+)\"([^>]*)\>(.*?)\<\/a\>`ism';
		if (preg_match_all($r, $description, $regs, PREG_SET_ORDER)) {
			foreach ($regs as $reg) {
				list($email,$params) = explode('?',$reg[2]);
				//TODO manage $params (subject, body, ecc.)
				$internal_mailto = "javascript:InternalMailer('{$email}','','','','email_addy');";
				$mailto_after = '<a'.$reg[1].'href="'.$internal_mailto.'"'.$reg[3].'>'.$reg[4].'</a>';
				$description = str_replace($reg[0], $mailto_after, $description);
			}
		}
		return $description;
	}
	//crmv@81766e

	//crmv@86304
	function internalAppendMessage($mail,$account,$parentid,$to,$from_name,$from_address,$subject,$description,$cc,$bcc,$send_mode,$save_documents=true,$date='',$folder='') { // crmv@187622
		$mreplyto = array();
		$mreplyto_n = array();
		if (!empty($mail->ReplyTo)) {
			foreach($mail->ReplyTo as $r) {
				$mreplyto[] = $r[0];
				$mreplyto_n[] = $r[1];
			}
		}
		if (!empty($mail->CustomHeader)) {
			foreach($mail->CustomHeader as $c) {
				if ($c[0] == 'In-Reply-To') $in_reply_to = trim($c[1]);
				elseif ($c[0] == 'References') $mreferences = trim($c[1]);
			}
		}
		$other = array();
		// crmv@206153
		$attachments = $mail->getAttachments();
		if (!empty($attachments)) {
			foreach($attachments as $a) {
		// crmv@206153e
				$content = $file = '';
				($a[5]) ? $content = $a[0] : $file = $a[0];
				$other[] = array(
					'parameters'=>array(
						'name'=>$a[2],
						'contenttype'=>$a[4],
						'contentdisposition'=>$a[6],
						'encoding'=>$a[3],
					),
					'content'=>$content,
					'file'=>$file,
				);
			}
		}
		$record = $this->saveCacheLink(array(
			'subject'=>$subject,
			'description'=>$description,
			'mfrom'=>$from_address,
			'mfrom_n'=>$from_name,
			'mfrom_f'=>"$from_name <$from_address>",
			'mto'=>(is_array($to)) ? implode(', ',$to) : $to,
			'mcc'=>(is_array($cc)) ? implode(', ',$cc) : $cc,
			'mbcc'=>(is_array($bcc)) ? implode(', ',$bcc) : $bcc,
			'mreplyto'=>$replyTo['emails'],
			'mreplyto_n'=>$replyTo['names'],
			'in_reply_to'=>$in_reply_to,
			'mreferences'=>$mreferences,
			'xmailer'=>null,
			'messageid'=>$mail->message_id,
			'send_mode'=>$send_mode,
			'other'=>$other,
			'parent_id'=>$_REQUEST['relation'],
			'recipients'=>$parentid,
			//crmv@80216
			'mtype'=>'Link',
			'account'=>$account,
			//crmv@80216e
			// crmv@187622
			'mdate'=>$date,
			'folder'=>$folder,
			// crmv@187622e
		));
		if ($save_documents && !empty($record) && !empty($other)) { // crmv@187622
			foreach($other as $contentid => $content){
				$this->saveDocument($record,$contentid,null,null,$content,false);
			}
		}
		return $record; // crmv@187622
	}
	//crmv@86304e

	// crmv@91980
	function extractPhoneNumbers($description) {
		$allPhoneNumbers = array();
		if (empty($description)) return $allPhoneNumbers;
		
		if (preg_match_all('/href\s*=\s*[\'"](tel|sms):([%0-9 +.)(-]+)[\'"]/i', $description, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$type = strtolower(trim($match[1]));
				$num = trim(rawurldecode($match[2]));
				if (($type == 'tel' || $type == 'sms') && !empty($num)) {
					$allPhoneNumbers[] = array(
						'number' => $num,
						'type' => ($type == 'sms' ? 'mobile' : 'phone')
					);
				}
			}
		}
		return $allPhoneNumbers;
	}
	
	// retrieve them from the table
	function getPhoneNumbers($messagesid) {
		global $adb, $table_prefix;
		
		$list = array();
		$res = $adb->pquery("SELECT phone, type FROM {$table_prefix}_messages_ntel WHERE messagesid = ?", array($messagesid));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByASsoc($res,-1, false)) {
				$list[] = array('number' => $row['phone'], 'type' => $row['type']);
			}
		}
		return $list;
	}
	
	// save the numbers in the table
	function savePhoneNumbers($messagesid, $numbers) {
		global $adb, $table_prefix;
		
		$inserts = array();
		foreach ($numbers as $entry) {
			$inserts[] = array($messagesid, $entry['number'], $entry['type']);
		}
		
		if (count($inserts) > 0) {
			$adb->bulkInsert($table_prefix."_messages_ntel", array('messagesid', 'phone', 'type'), $inserts);
		}
		
	}
	
	function deletePhoneNumbers($messagesid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$table_prefix}_messages_ntel WHERE messagesid = ?", array($messagesid));
	}
	// crmv@91980e

	//crmv@94282
	function get_navigation_values($list_query_count,$url_string,$currentModule,$type='',$forusers=false,$viewid = '') {
		return Zend_Json::encode(Array('nav_array'=>Array(),'rec_string'=>''));
	}
	//crmv@94282e

	//crmv@112756	crmv@130734
	function extractTnefAndZip(&$zipname, &$content, &$filesize) {
		define('SM_PATH', $root_directory."modules/Messages/src/attachment_tnef/");
		require_once('modules/Messages/src/attachment_tnef/plugins/attachment_tnef/constants.php');
		require_once('modules/Messages/src/attachment_tnef/plugins/attachment_tnef/functions.php');
		include_once('modules/Messages/src/attachment_tnef/plugins/attachment_tnef/class/tnef.php');
		require_once('vtlib/thirdparty/dZip.inc.php');
		
		global $root_directory;
		$tmp_folder = "cache/upload/";
		$tmp_fullpath = $root_directory.$tmp_folder;
		
		//sanitize subject for filename
		$zipname = preg_replace('/[\/:*?"<>|]/','',$zipname);
		$zipname = preg_replace('/\s+/', '_', $zipname);
		$zipname = str_replace('.dat', '', $zipname);
		$zipname .= ".zip";
		
		$tmp_zipname = '';
		$files2clean = array();
		$tnef_debug = false;
		$attachment = new TnefAttachment($tnef_debug); // crmv@146653
		$result = $attachment->decodeTnef($content);
		$tnef_files = &$attachment->getFilesNested();
		$attachments = array();
		if (!empty($tnef_files)) {
			$tmp_zipname = tempnam($tmp_fullpath,'tnef');
			$zip=new dZip($tmp_zipname);
			foreach($tnef_files as $tnef_file) {
				$name = $tnef_file->getName();
				$tmp_name = tempnam($tmp_fullpath,'attach');
				file_put_contents($tmp_name,$tnef_file->getContent());
				$files2clean[$name]=$tmp_name;
				$zip->addFile($tmp_name, $name);
			}
			$zip->save();
		}
		
		//cleaning temp files
		if (!empty($files2clean)) {
			foreach($files2clean as $filepath){
				unlink($filepath);
			}
		}
		
		if (empty($tmp_zipname)) {
			$filesize = 0;
			return false;
		}
		
		$filesize = filesize($tmp_zipname);
		//$filesize = $filesize + ($filesize % 1024); //for zip files //crmv@79484
		$content = fread(fopen($tmp_zipname, "r"), $filesize);
		
		return $tmp_zipname;
	}
	//crmv@112756e	crmv@130734e

	//crmv@OPER8279
	function getIntervalStorage() {
		// use max from interval_storage and interval_schedulation (if not empty)
		$interval_schedulation = strtotime("-{$this->interval_schedulation}");
		$interval_storage = strtotime("-{$this->interval_storage}");
		((!empty($this->interval_schedulation)) && $interval_storage > $interval_schedulation) ? $interval = $this->interval_schedulation : $interval = $this->interval_storage;
		return array(
			'interval'=>$interval,
			'time'=>strtotime("-{$interval}"),
			'date'=>date('Y-m-d',strtotime("-{$interval}")),
		);
	}
	function cleanStorage() {
		global $adb, $table_prefix;
		
		// exit if interval_storage is set to store all
		if (empty($this->interval_storage)) return false;
		
		// first of all propagate to server pending changes
		$this->propagateToImap();
		
		$interval_storage = $this->getIntervalStorage();
		$this->interval_storage = $interval_storage['interval'];
		$limit_date = $interval_storage['date'];
		
		if (empty($this->interval_storage)) return false;
		
		// crmv@186732
		// delete from db all messages with mdate < interval_storage and no relations
		$adb->pquery("delete from {$table_prefix}_messages_cron_uid where date < ?", array($limit_date));
		$adb->pquery("delete from {$table_prefix}_messages_cron_uidi where date < ?", array($limit_date));
		// crmv@186732e
		
		//crmv@171021 crmv@197432
		$result = $adb->limitpQuery("select {$table_prefix}_messages.messagesid, {$table_prefix}_messages.messagehash, mdate, folder
			from {$table_prefix}_messages
			left join {$table_prefix}_messagesrel on {$table_prefix}_messagesrel.messagehash = {$table_prefix}_messages.messagehash
			where mtype = ? and mdate < ? and {$table_prefix}_messagesrel.crmid is null and ({$table_prefix}_messages.createdtime is null or {$table_prefix}_messages.createdtime <= ?)", 0, $this->messages_cleaned_by_schedule,
			array('Webmail', $limit_date, date('Y-m-d H:i:s',strtotime($this->preserve_search_results_date))));
		//crmv@171021e crmv@197432e
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				// echo "INTERVAL: ".$this->interval_storage.' - LIMIT DATE: '.$limit_date.' - MESSAGE: '.$row['messagesid'].' '.$row['messagehash'].' '.$row['folder'].' '.$row['mdate']."\n";
				$this->cleanMessage($row['messagesid'],$row['messagehash']);
			}
		}
		
		return true;
	}
	function cleanMessage($messagesid, $messagehash) {
		global $adb, $table_prefix;

		$tables = array(
			//crmv@171021 removed table _crmentity
			'_messages'=>'messagesid',
			'_messages_attach'=>'messagesid',
			'_messages_deflist'=>'id',
			'_messages_ical'=>'messagesid',
			'_messages_mref'=>'messagesid',
			'_messages_ntel'=>'messagesid',
			'_messages_recipients'=>'messagesid',
			'_messages_th'=>'son',
			'_messages_tmp_prel'=>'id',
			'_messages_tmp_rlist'=>'id',
			//crmv@171021 removed table _messagescf
		);
		foreach($tables as $table => $key) {
			$adb->pquery("delete from {$table_prefix}{$table} where $key = ?", array($messagesid));
		}
		
		// if do not exists other records with the same messagehash clean tables based on messagehash
		$result_hash = $adb->pquery("select messagesid from {$table_prefix}_messages where messagehash = ?", array($messagehash)); //crmv@171021
		if ($adb->num_rows($result_hash) == 0) {
			$tables = array(
				'_messages_drafts'=>'messagehash',
				'_messagesrel'=>'messagehash',
			);
			foreach($tables as $table => $key) {
				$adb->pquery("delete from {$table_prefix}{$table} where $key = ?", array($messagehash));
			}
		}
	}
	function getSearchIntervals() {
		static $search_intervals = array();
		if (empty($search_intervals)) {
			$search_in_imap = false;
			$check_interval_storage_1 = false;
			$check_interval_storage_2 = false;
	
			foreach($this->search_intervals as $start => $si) {
				$search_intervals_0 = $this->search_intervals[$start][0];
				$search_intervals_1 = $this->search_intervals[$start][1];
				
				if (!empty($this->interval_storage)) {
					$interval_storage = $this->getIntervalStorage();
					$limit_storage_time = $interval_storage['time'];
				}
				if (!empty($search_intervals_1)) {
					$time = strtotime($search_intervals_1);
					if (!$check_interval_storage_1 && $limit_storage_time > $time) {
						$time = $limit_storage_time;
						$search_intervals_1 = '-'.$interval_storage['interval'];
						$check_interval_storage_1 = true;
					}
					// mdate >= '".date('Y-m-d',$time)." 00:00:00'
				}
				if (!empty($search_intervals_0)) {
					$time = strtotime($search_intervals_0);
					if (!$check_interval_storage_2 && $limit_storage_time > strtotime($this->search_intervals[$start-1][1])) {
						$time = $limit_storage_time;
						$search_intervals_0 = '-'.$interval_storage['interval'];
						$check_interval_storage_2 = true;
						$search_in_imap = true;
					}
					// mdate < '".date('Y-m-d',$time)." 00:00:00'
				}
				$search_intervals[] = array($search_intervals_0, $search_intervals_1, $search_in_imap);
			}
		}
		return $search_intervals;
	}
	// first step contains messages stored (from now to limit_storage) and then the steps are te same of getSearchIntervals
	function getImapNavigationIntervals() {
		$intervals = array();
		$search_intervals = $this->getSearchIntervals();
		foreach($search_intervals as $i => $search_interval) {
			if ($search_interval[2] == 0 && $i == 0) {
				$intervals[0] = $search_interval;
			} elseif ($search_interval[2] == 0) {
				$intervals[0][1] = $search_interval[1];
			} elseif ($search_interval[2] == 1) {
				$intervals[] = $search_interval;
			}
		}
		return $intervals;
	}
	function getSearchParams($input) {
		global $current_user, $currentModule;
		$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
		
		$search_params = array(
			'searchtype'=>$input['searchtype'],
		);
		if ($input['searchtype'] == 'BasicSearch') {
			$search_params['search_text'] = $input['search_text'];
		} elseif ($input['searchtype'] == 'advance') {
			$search_params['matchtype'] = ($input['matchtype'] == 'all') ? $queryGenerator::$AND : $queryGenerator::$OR;
			$noOfConditions = vtlib_purify($input['search_cnt']);
			for($i=0; $i<$noOfConditions; $i++) {
				$fieldInfo = 'Fields'.$i;
				$condition = 'Condition'.$i;
				$value = 'Srch_value'.$i;
				
				list($fieldName,$typeOfData) = explode("::::",str_replace('\'','',stripslashes($input[$fieldInfo])));
				$operator = str_replace('\'','',stripslashes($input[$condition]));
				$searchValue = $input[$value];
				$searchValue = urldecode($searchValue); //crmv@60585
				$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$searchValue) : $searchValue; // crmv@167702
				
				$search_params['conditions'][] = array('field'=>$fieldName,'operator'=>$operator,'value'=>$searchValue);
			}
		}
		return $search_params;
	}
	/*
	 * imap search sources
	 * https://tools.ietf.org/html/rfc3501#section-6.4.4
	 * https://www.limilabs.com/blog/imap-search-requires-parentheses
	 */
	function getListViewEntriesImap($before, $since, $search_params=array(), $list_result, &$lv_error='') {
		global $adb, $current_user, $currentModule, $default_charset, $current_folder;
		$userid = $current_user->id;
		$account = $this->getAccount();
		$folder = $current_folder;
		
		$current_user->id = $userid;
		$this->setAccount($account);
		$this->getZendMailStorageImap($userid);
		$this->selectFolder($folder);
		
		// search messages where date >= $since and date < $before
		if (empty($before) && empty($since)) {
			$lv_error = getTranslatedString('LBL_IMAP_SEARCH_ERROR_1','Messages');
			return false;
		}
		
		$queryGenerator = QueryGenerator::getInstance($currentModule, $current_user);
		$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
		
		$listview_rows = array();
		$listview_xuid = array();
		if ($list_result && $adb->num_rows($list_result) > 0) {
			while($row=$adb->fetchByAssoc($list_result,-1,false)) {
				$listview_rows[$row['messagesid']] = $row;
				$listview_xuid[$row['xuid']] = $row['messagesid'];
			}
		}

		$imap_params = array();
		if (!empty($before)) $imap_params[] = 'BEFORE "'.date('j-M-Y',strtotime($before)).'"';
		if (!empty($since)) $imap_params[] = 'SINCE "'.date('j-M-Y',strtotime($since)).'"';
		if ($search_params['searchtype'] == 'BasicSearch') {
			$search_string = urldecode($search_params['search_text']);
			$stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$search_string) : $search_string; // crmv@167702
			$search_string = trim($stringConvert);
			
			if (empty($search_string)) {
				$lv_error = getTranslatedString('LBL_IMAP_SEARCH_ERROR_2','Messages');
				return false;
			}
			
			$imap_params[] = 'TEXT "'.$search_string.'"';
			
		} elseif ($search_params['searchtype'] == 'advance') {
			$conditions = $search_params['conditions'];
			if (empty($conditions)) {
				$lv_error = getTranslatedString('LBL_IMAP_SEARCH_ERROR_3','Messages');
				return false;
			}
			
			$imap_conditions = array();
			foreach($conditions as $condition) {
				// check supported operators
				if (in_array($condition['field'],array('seen','mdate'))) {}	// for seen and date all operators are supported
				elseif (!in_array($condition['operator'],array('c','k'))) {
					$lv_error = getTranslatedString('LBL_IMAP_SEARCH_ERROR_4','Messages');
					return false;
				}
				
				if ($condition['field'] == 'subject') {
					$str = ($condition['operator'] == 'k') ? 'NOT ' : '';
					$str .= 'SUBJECT "'.$condition['value'].'"';
					$imap_conditions[] = $str;
				} elseif ($condition['field'] == 'description') {
					$str = ($condition['operator'] == 'k') ? 'NOT ' : '';
					$str .= 'BODY "'.$condition['value'].'"';
					$imap_conditions[] = $str;
				} elseif ($condition['field'] == 'mdate') {
					if (in_array($condition['operator'],array('custom','yesterday','today','lastweek','thisweek','lastmonth','thismonth','last60days','last90days'))) {
						list($start,$end) = explode('|##|',$condition['value']);
						if (strlen($start) == 10) $imap_conditions[] = 'SINCE "'.date('j-M-Y',strtotime(getValidDBInsertDateValue($start))).'"';
						if (strlen($end) == 10) $imap_conditions[] = 'BEFORE "'.date('j-M-Y',strtotime(getValidDBInsertDateValue($end))).'"';
					} else {
						$value = getValidDBInsertDateValue($condition['value']);
						if ($condition['operator'] == 'e') {	// equal
							$imap_conditions[] = 'SINCE "'.date('j-M-Y',strtotime($value)).'"';
							$imap_conditions[] = 'BEFORE "'.date('j-M-Y',strtotime('+1 day',strtotime($value))).'"'; // since + 1 day
						} elseif ($condition['operator'] == 'n') {	// not equal
							$imap_conditions[] = 'NOT (SINCE "'.date('j-M-Y',strtotime($value)).'" BEFORE "'.date('j-M-Y',strtotime('+1 day',strtotime($value))).'")';
						} elseif ($condition['operator'] == 'l') {	// less than
							$imap_conditions[] = 'BEFORE "'.date('j-M-Y',strtotime($value)).'"';
						} elseif ($condition['operator'] == 'g') {	// greater than
							$imap_conditions[] = 'SINCE "'.date('j-M-Y',strtotime('+1 day',strtotime($value))).'"';
						} elseif ($condition['operator'] == 'm') {	// less or equal
							$imap_conditions[] = 'BEFORE "'.date('j-M-Y',strtotime('+1 day',strtotime($value))).'"';
						} elseif ($condition['operator'] == 'h') {	// greater or equal
							$imap_conditions[] = 'SINCE "'.date('j-M-Y',strtotime($value)).'"';
						}
					}
				} elseif ($condition['field'] == 'senders') {
					$str = ($condition['operator'] == 'k') ? 'NOT ' : '';
					$str .= 'FROM "'.$condition['value'].'"';
					$imap_conditions[] = $str;
				} elseif ($condition['field'] == 'recipients') {
					$str = ($condition['operator'] == 'k') ? 'NOT ' : '';
					$str .= '(OR TO "'.$condition['value'].'" OR CC "'.$condition['value'].'" BCC "'.$condition['value'].'")';
					$imap_conditions[] = $str;
				} elseif ($condition['field'] == 'seen') {
					if ($condition['operator'] == 'e' && strtolower($condition['value']) == 'yes') $imap_conditions[] = 'SEEN';
					elseif ($condition['operator'] == 'e' && strtolower($condition['value']) == 'no') $imap_conditions[] = 'UNSEEN';
					elseif ($condition['operator'] == 'n' && strtolower($condition['value']) == 'yes') $imap_conditions[] = 'NOT SEEN';
					elseif ($condition['operator'] == 'n' && strtolower($condition['value']) == 'no') $imap_conditions[] = 'NOT UNSEEN';
				}
			}
			if (!empty($imap_conditions)) {
				if ($search_params['matchtype'] == $queryGenerator::$AND) {
					foreach($imap_conditions as $imap_condition) {
						$imap_params[] = $imap_condition;
					}
				} elseif ($search_params['matchtype'] == $queryGenerator::$OR) {
					if (count($imap_conditions) == 1) {
						$imap_params[] = $imap_conditions[0];
					} else {
						$imap_param = '(';
						for($i=0;$i<count($imap_conditions);$i++) {
							if ($i > 0) $imap_param .= ' ';
							($i<count($imap_conditions)-1) ? $imap_param .= 'OR ' : $imap_param .= '';
							$imap_param .= $imap_conditions[$i];
						}
						$imap_param .= ')';
						$imap_params[] = $imap_param;
					}
				}
			}
		}
		$imap_params[] = 'NOT DELETED';	//crmv@146115
		$messageids = self::$protocol->search($imap_params);
		if ($messageids === false) {
			/* Lotus do not support SEARCH SINCE */
			return false;
		} else {
			$tmp_server_ids = self::$protocol->fetch(array('UID','INTERNALDATE'),$messageids);
		}
		$ids = array();
		foreach($tmp_server_ids as $messageid => $val) {
			$ids[$messageid] = $val['UID'];
			//$ids_dates[$val['UID']] = date('Y-m-d H:i:s',strtotime($val['INTERNALDATE']));
		}
		krsort($ids);

		$ret_arr = array();
		foreach ($ids as $messageId => $uid) {
			if (isset($listview_xuid[$uid])) {
				// if message is saved use the saved one
				$row = $listview_rows[$listview_xuid[$uid]];
				($this->haveAttachments($row['messagesid'])) ? $row['has_attachments'] = 1 : $row['has_attachments'] = 0;
				$ret_arr[$listview_xuid[$uid]] = $controller->getListViewEntryLight($this,$currentModule,$row);
			} else {
				try {
					$message = self::$mail->getMessage($messageId);
				} catch (Zend\Mail\Exception\RuntimeException $e) {
					$this->logException($e,__FILE__,__LINE__,__METHOD__);	//crmv@90390
					// ignore parse errors and continue
					continue;
				}
				
				$data = $this->getMessageData($message,$messageId);	//crmv@59094
				if (empty($data)) continue;
				
				// skip the message if no messageid is present
				if (empty($data['header']['Messageid'])) continue;
				
				$date = $this->getImap2DbDate($data);
				
				$body = $this->getImap2DbBody($data);
				$description = str_replace('&amp;', '&', $body);
				$magicHTML = $this->magicHTML($description, $uid);
				$cleaned_body = $magicHTML['html'];
				
				$row = array(
					'subject'=>$data['header']['Subject'],
					'mdate'=>$date,
					'mfrom'=>$data['header']['From']['email'],
					'mfrom_n'=>$data['header']['From']['name'],
					'mfrom_f'=>$data['header']['From']['full'],
					'mto'=>$data['header']['To']['email'],
					'mto_n'=>$data['header']['To']['name'],
					'mto_f'=>$data['header']['To']['full'],
					'cleaned_body'=>$cleaned_body,
					'seen'=>($data['flags']['seen']=='on')?'1':'0',
					'answered'=>($data['flags']['answered']=='on')?'1':'0',
					'forwarded'=>($data['flags']['forwarded']=='on')?'1':'0',
					'flagged'=>($data['flags']['flagged']=='on')?'1':'0',
					'has_attachments'=>!empty($data['other'])?'1':'0',
					//'thread'=>'', // TODO ?
					'xuid'=>$uid,
					'folder'=>$folder,
					'account'=>$account,
				);
				$ret_arr['xuid_'.$uid] = $controller->getListViewEntryLight($this,$currentModule,$row);
				$ret_arr['xuid_'.$uid]['ghost'] = true;
			}
		}
		return $ret_arr;
	}
	//crmv@OPER8279e
	
	//crmv@125629
	function saveInlineCache($messagesid,$contentid,$content,$parameters=array()) {
		global $adb, $table_prefix;
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into {$table_prefix}_messages_inline_cache (messagesid,contentid,cachedate,content,parameters) values (?,?,?,?,?)", array($messagesid,$contentid,date('Y-m-d H:i:s'),$content,Zend_Json::encode($parameters)));
		} else {
			$result = $adb->pquery("select messagesid from {$table_prefix}_messages_inline_cache where messagesid = ? and contentid = ?",array($messagesid,$contentid));
			if (!$result || $adb->num_rows($result) == 0) {
				$adb->pquery("insert into {$table_prefix}_messages_inline_cache (messagesid,contentid,cachedate,content,parameters) values (?,?,?,?,?)", array($messagesid,$contentid,date('Y-m-d H:i:s'),$content,Zend_Json::encode($parameters)));
			}
		}
	}
	function cleanInlineCache() {
		global $adb, $table_prefix;
		
		// exit if interval_storage is set to store all
		if (empty($this->interval_inline_cache)) return false;
		
		$result = $adb->pquery("delete from {$table_prefix}_messages_inline_cache where cachedate < ?", array(date('Y-m-d H:i:s',strtotime("-{$this->interval_inline_cache}"))));
		
		return true;
	}
	function forceInlineCache() {
		global $adb, $table_prefix;
		
		// force 1 month in order to prevent timeout
		$this->interval_inline_cache = '1 month';
		//crmv@171021
		$result = $adb->pquery("select messagesid, smownerid, account, folder, xuid
			from {$table_prefix}_messages
			where deleted = 0 and mtype = 'Webmail' and mdate >= ?
			order by mdate desc",
			array(date('Y-m-d H:i:s',strtotime("-{$this->interval_inline_cache}"))));
		//crmv@171021e
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByASsoc($result,-1, false)) {
				// TODO download inline or simulate save
			}
		}
	}
	//crmv@125629e

	// crmv@187622
	function saveScheduledMessage($date, $request) {
		$emailsFocus = CRMEntity::getInstance('Emails');
		
		$account = $emailsFocus->getFromEmailAccount($request['from_email']);
		$parentid = $request['parent_id'];
		$myids = explode("|",$parentid);
		if (!empty($myids)) $myids = array_filter($myids);
		$to = $emailsFocus->getToList($request['send_mode'],$request['to_mail'],$myids);
		
		$cc = explode(',',$request['ccmail']);
		$cc = array_map('trim', $cc);
		$cc = array_filter($cc);
		$bcc = explode(',',$request['bccmail']);
		$bcc = array_map('trim', $bcc);
		$bcc = array_filter($bcc);
		
		$message_mode = vtlib_purify($request['message_mode']);
		$messageid = vtlib_purify($request['message']);
		($message_mode == 'forward') ? $attach_messageid = $messageid : $attach_messageid = '';
		(!empty($request['attachments_mode'])) ? $attach_mode = $request['attachments_mode'] : $attach_mode = 'all';
		$mail = new VTEMailer(); // crmv@180739
		setMailerProperties($mail,$request['subject'],$request['description'],$request['from_email'],$request['from_email'],$to,$attach_mode,$attach_messageid,'Emails');
		
		$messagesid = $this->internalAppendMessage($mail,$account,$parentid,$to,$request['from_email'],$request['from_email'],$request['subject'],$request['description'],$cc,$bcc,$request['send_mode'],false,$date,'vteScheduled');

		$this->retrieve_entity_info($messagesid,'Messages');
		$this->reloadCacheFolderCount($this->column_fields['assigned_user_id'],$account,'vteScheduled');
		
		return $messagesid;
	}
	// crmv@187622e
	
	// crmv@191351
	function outOfOfficeReply() {
		global $adb, $table_prefix;
		$settings = $this->getOutOfOfficeSettings();
		// echo $this->column_fields['mdate'].' '.$this->column_fields['subject']."\n";
		
		// check if outOfOffice is active
		if (!$settings['active']) {
			// echo "not active\n";
			return false;
		}
		
		// check if outOfOffice is active for the current account
		if (!in_array($this->column_fields['account'],$settings['accounts'])) {
			// echo "not active account\n";
			return false;
		}
		
		// check if this message is in the inbox
		$specialFolders = $this->getAllSpecialFolders('INBOX');
		if (empty($specialFolders[$this->column_fields['account']]['INBOX']) || $this->column_fields['folder'] != $specialFolders[$this->column_fields['account']]['INBOX']) {
			// echo "not inbox\n";
			return false;
		}
		
		// check time interval
		if (!$settings['start_date_allday']) $start_time = $settings['start_time']; else $start_time = '00:00:00';
		if (!$settings['start_date_allday']) $end_time = $settings['end_time']; else $end_time= '23:59:59';
		if (strtotime($this->column_fields['mdate']) < strtotime($settings['start_date'].' '.$start_time)) {
			// echo "not start date\n";
			return false;
		}
		if ($settings['end_date_active'] && strtotime('now') > strtotime($settings['end_date'].' '.$end_time)) {
			// echo "end of interval is in the past\n";
			return false;
		}
		if ($settings['end_date_active'] && strtotime($this->column_fields['mdate']) > strtotime($settings['end_date'].' '.$end_time)) {
			// echo "not end date\n";
			return false;
		}
		
		// if the option only_known_addresses_active is true, check if the sender is a known address
		if ($settings['only_known_addresses_active']) {
			require_once('include/utils/EmailDirectory.php');
			$emailDirectory = new EmailDirectory();
			$modules = $emailDirectory->getModules();
			$entity = $this->getEntitiesFromEmail($this->column_fields['mfrom'], false, false, $modules, true);
			if (empty($entity) || empty($entity['crmid'])) {
				// echo "not known address\n";
				return false;
			}
		}
		
		// check the notification interval
		$check_date = date('Y-m-d H:i:s', strtotime('-5 days'));
		$result = $adb->pquery("select id from {$table_prefix}_messages_outofo_q where to_email = ? and date > ?", array($this->column_fields['mfrom'],$check_date));
		if ($result && $adb->num_rows($result) > 0) {
			// echo "notification already sent less than 5 days ago\n";
			return false;
		}
		
		$subject = 'Re: '.$this->column_fields['subject'];
		if (!empty($settings['message_subject'])) $subject .= ' '.$settings['message_subject'];
		
		// enqueue the reply
		$account = $this->getUserAccounts($this->column_fields['assigned_user_id'],$this->column_fields['account']);
		$params = array(
			'id'=>$adb->getUniqueID($table_prefix.'_messages_outofo_q'),
			'from_email'=>$account[0]['email'],
			'to_email'=>$this->column_fields['mfrom'],
			'subject'=>$subject,
			'body'=>$settings['message_body'],
			'date'=>date('Y-m-d H:i:s'),
			'in_reply_to'=>$this->column_fields['messageid'],
		);
		$adb->pquery("insert into {$table_prefix}_messages_outofo_q (".implode(',',array_keys($params)).") values (".generateQuestionMarks($params).")",array($params));
		
		return $params['id'];
	}
	function processOutOfOfficeQueue() {
		global $adb, $table_prefix, $current_user;
		require_once("modules/Emails/mail.php");
		$focusEmails = CRMEntity::getInstance('Emails');
		
		$mail = new VTEMailer(); // crmv@55094 crmv@180739
		
		// clean queue
		$clean_date = date('Y-m-d H:i:s', strtotime('-7 days'));
		$adb->pquery("delete from {$table_prefix}_messages_outofo_q where date < ?", array($clean_date));
		
		// process queue
		$sql = "select * from {$table_prefix}_messages_outofo_q where status = ? or (status = ? and attempts < ?) order by id";
		$result = $adb->limitpQuery($sql,0,50,array(0,2,$focusEmails->max_emails_send_queue_attempts));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				if ($mail->SMTPDebug) echo "sending message {$row['id']} from {$table_prefix}_messages_outofo_q...\n";	//crmv@55094
				$adb->pquery("update {$table_prefix}_messages_outofo_q set status = ?, attempts = attempts + 1 where id = ?",array(2,$row['id']));
				
				$mail_tmp = '';
				$mail_status = send_mail('Emails',$row['to_email'],$row['from_email'],$row['from_email'],$row['subject'],$row['body'],'','','','','','',$mail_tmp,$row['in_reply_to'],'reply',false);
				if ($mail_status == 1) {
					$adb->pquery("update {$table_prefix}_messages_outofo_q set status = ? where id = ?",array(1,$row['id']));
				} else {
					$adb->pquery("update {$table_prefix}_messages_outofo_q set error = ? where id = ?",array($mail_status,date('Y-m-d H:i:s'),$row['id']));
				}
				if ($mail->SMTPDebug) echo "\nmessage {$row['id']} from {$table_prefix}_messages_outofo_q sent!\n\n";	//crmv@55094
			}
		}
	}
	// crmv@191351e
}