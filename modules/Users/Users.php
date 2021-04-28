<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@9010
require_once('ldap.config.inc.php');
//crmv@9010e
if (file_exists('modules/Morphsuit/utils/MorphsuitUtils.php') || defined('VTEROOTDIR')) {
	require_once('modules/Morphsuit/utils/MorphsuitUtils.php');
}

// User is used to store customer information.
 /** Main class for the user module
   *
  */
class Users extends CRMEntity { //crmv@392267
	var $log;
	var $db;
	// Stored fields
	var $id;
	var $authenticated = false;
	var $error_string;
	var $is_admin;
	var $deleted;


	var $tab_name = Array();
	var $tab_name_index = Array();

	var $column_fields = Array();	//crmv@24461

	var $table_name;
	var $table_index= 'id';

	// This is the list of fields that are in the lists.
	var $list_link_field= 'last_name';

	var $list_mode;
	var $popup_type;

	var $search_fields = Array();
	var $search_fields_name = Array(
		'User Name'=>'user_name',
		'First Name'=>'first_name',
		'Last Name'=>'last_name',
		'Role'=>'roleid',
		'Email'=>'email1',
		'Phone'=>'phone_work'
	);
	var $search_base_field = 'last_name';	//crmv@19781

	var $module_name = "Users";

	var $object_name = "User";
	var $user_preferences;

	var $encodeFields = Array("first_name", "last_name", "description");

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('reports_to_name');

	var $sortby_fields = Array('status','email1','phone_work','is_admin','user_name','last_name');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array();
	var $list_fields_name = Array(
		'Last Name'=>'last_name',
		'First Name'=>'first_name',
		'Role'=>'roleid',
		'User Name'=>'user_name',
		'Status'=>'status',
		'Email'=>'email1',
		'Admin'=>'is_admin',
		'Phone'=>'phone_work'
	);

	//Default Fields for Email Templates -- Pavani
	var $emailTemplate_defaultFields = array('first_name','last_name','title','department','phone_home','phone_mobile','signature','email1','address_street','address_city','address_state','address_country','address_postalcode');


	// This is the list of fields that are in the lists.
	var $default_order_by = "user_name";
	var $default_sort_order = 'ASC';

	var $record_id;
	var $new_schema = true;

	var $DEFAULT_PASSWORD_CRYPT_TYPE; //'BLOWFISH', /* before PHP5.3*/ MD5;

	//crmv@28327
	var $time_to_block_user = 6;		//(months)	if equal to 0 the check is disable
	var $time_to_change_password = 3;	//(months)	if equal to 0 the check is disable
	// crmv@187476 removed time_to_change_old_pwd
	var $password_length_min = 8;		//if equal to 0 the check is disable
	//crmv@28327e
	
	//crmv@56023
	var $enable_login_protection = true; //boolean flag
	var $track_login_table;
	var $max_login_attempts = 5;
	var $max_login_banned_attempts = 100;
	var $as_login_time_to_lock = 48; //(hours) after this num of hours since first_attempt date, active sync client will we locked
	//crmv@56023e
	
	// crmv@115336
	/**
	 * Format to use when displaying the user in the extended form.
	 * Only these 3 fields are currently supported.
	 */
	const USERNAME_FORMAT_STANDARD = 1;	// username (firstname lastname)
	const USERNAME_FORMAT_INVERTED = 2; // firstname lastname (username)
	public $username_format = 1;
	// crmv@115336e

	/** constructor function for the main user class
            instantiates the Logger class and PearDatabase Class
  	  *
 	*/

	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->tab_name = Array($table_prefix.'_users',$table_prefix.'_attachments',$table_prefix.'_user2role',$table_prefix.'_asteriskextensions');
		$this->tab_name_index = Array($table_prefix.'_users'=>'id',$table_prefix.'_attachments'=>'attachmentsid',$table_prefix.'_user2role'=>'userid',$table_prefix.'_asteriskextensions'=>'userid');
		$this->table_name = $table_prefix."_users";
		$this->list_fields = Array(
			'First Name'=>Array($table_prefix.'_users'=>'first_name'),
			'Last Name'=>Array($table_prefix.'_users'=>'last_name'),
			'Role'=>Array($table_prefix.'_user2role'=>'roleid'),
			'User Name'=>Array($table_prefix.'_users'=>'user_name'),
			'Status'=>Array($table_prefix.'_users'=>'status'),
			'Email'=>Array($table_prefix.'_users'=>'email1'),
			'Admin'=>Array($table_prefix.'_users'=>'is_admin'),
			'Phone'=>Array($table_prefix.'_users'=>'phone_work')
		);
		$this->search_fields = Array(
			'User Name'=>Array($table_prefix.'_users'=>'user_name'),
			'First Name'=>Array($table_prefix.'_users'=>'first_name'),
			'Last Name'=>Array($table_prefix.'_users'=>'last_name'),
			'Role'=>Array($table_prefix.'_user2role'=>'roleid'),
			'Email'=>Array($table_prefix.'_users'=>'email1'),
			'Phone'=>Array($table_prefix.'_users'=>'phone_work')
		);
		$this->log = LoggerManager::getLogger('user');
		$this->log->debug("Entering Users() method ...");
		$this->column_fields = getColumnFields('Users');	//crmv@24461
		$this->db = PearDatabase::getInstance();
		$this->DEFAULT_PASSWORD_CRYPT_TYPE = (version_compare(PHP_VERSION, '5.3.0') >= 0)?
				'PHP5.3MD5': 'MD5';
		//crmv@3079m		
		$this->homeorder_array = array('UA', 'PA', 'ALVT','HDB','PLVT','QLTQ','CVLVT','HLT','GRT','OLTSO','ILTI','MNL','OLTPO','LTFAQ');
		$additional_homeblocks = SDK::getDefaultIframes();
		foreach ($additional_homeblocks as $homeblock){
			$this->homeorder_array[] = $homeblock;
		}
		//crmv@3079me
		$this->track_login_table = $table_prefix.'_check_logins';	//crmv@56023
		$this->log->debug("Exiting Users() method ...");
	}

	// Mike Crowe Mod --------------------------------------------------------Default ordering for us
	/**
	 * Function to get sort order
	 * return string  $sorder    - sortorder string either 'ASC' or 'DESC'
	 */
	function getSortOrder($module = '', $useSession = true) // crmv@146653
	{
		global $log;
		$log->debug("Entering getSortOrder() method ...");
		if($_REQUEST['sorder'] !='' && in_array($_REQUEST['sorder'], array('ASC', 'DESC'))) // crmv@193850
			$sorder = $this->db->sql_escape_string($_REQUEST['sorder']);
		else
			$sorder = ((VteSession::get('USERS_SORT_ORDER') != '')?(VteSession::get('USERS_SORT_ORDER')):($this->default_sort_order));
		$log->debug("Exiting getSortOrder method ...");
		return $sorder;
	}

	/**
	 * Function to get order by
	 * return string  $order_by    - fieldname(eg: 'subject')
	 */
	function getOrderBy($module = '', $useSession = true) // crmv@146653
	{
		global $log;
                 $log->debug("Entering getOrderBy() method ...");

        $use_default_order_by = '';
		if(PerformancePrefs::getBoolean('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}

		global $adb, $table_prefix;
		if($_REQUEST['order_by'] != '' && in_array($_REQUEST['order_by'], $adb->getColumnNames($table_prefix.'_users'))) // crmv@193850
			$order_by = $this->db->sql_escape_string($_REQUEST['order_by']);
		else
			$order_by = ((VteSession::get('USERS_ORDER_BY') != '')?(VteSession::get('USERS_ORDER_BY')):($use_default_order_by));
		$log->debug("Exiting getOrderBy method ...");
		return $order_by;
	}
	// Mike Crowe Mod --------------------------------------------------------

	/** Function to set the user preferences in the session
  	  * @param $name -- name:: Type varchar
  	  * @param $value -- value:: Type varchar
  	  *
 	*/
	function setPreference($name, $value){
		if(!isset($this->user_preferences)){
			if(VteSession::hasKey("USER_PREFERENCES"))
				$this->user_preferences = VteSession::get("USER_PREFERENCES");
			else
				$this->user_preferences = array();
		}
		if(!array_key_exists($name,$this->user_preferences )|| $this->user_preferences[$name] != $value){
			$this->log->debug("Saving To Preferences:". $name."=".$value);
			$this->user_preferences[$name] = $value;
			$this->savePreferecesToDB();

		}
		VteSession::set($name, $value);


	}


	/** Function to save the user preferences to db
  	  *
 	*/

	function savePreferecesToDB(){
		$data = base64_encode(serialize($this->user_preferences));
		$query = "UPDATE $this->table_name SET user_preferences=? where id=?";
		$result =& $this->db->pquery($query, array($data, $this->id));
		$this->log->debug("SAVING: PREFERENCES SIZE ". strlen($data)."ROWS AFFECTED WHILE UPDATING USER PREFERENCES:".$this->db->getAffectedRowCount($result));
		VteSession::set("USER_PREFERENCES", $this->user_preferences);
	}

	/** Function to load the user preferences from db
  	  *
 	*/
	function loadPreferencesFromDB($value){

		if(isset($value) && !empty($value)){
			$this->log->debug("LOADING :PREFERENCES SIZE ". strlen($value));
			$this->user_preferences = unserialize(base64_decode($value));
			$_SESSION = array_merge($this->user_preferences, $_SESSION);
			$this->log->debug("Finished Loading");
			VteSession::set("USER_PREFERENCES", $this->user_preferences);


		}

	}


	/**
	 * @return string encrypted password for storage in DB and comparison against DB password.
	 * @param string $user_name - Must be non null and at least 2 characters
	 * @param string $user_password - Must be non null and at least 1 character.
	 * @desc Take an unencrypted username and password and return the encrypted password
	 */
	function encrypt_password($user_password, $crypt_type='')
	{
		// encrypt the password.
		$salt = substr($this->column_fields["user_name"], 0, 2);


		if($crypt_type == '') {
			// Try to get the crypt_type which is in database for the user
			$crypt_type = $this->get_user_crypt_type();
		}

		// For more details on salt format look at: http://in.php.net/crypt
		if($crypt_type == 'MD5') {
			$salt = '$1$' . $salt . '$';
		} elseif($crypt_type == 'BLOWFISH') {
			$salt = '$2$' . $salt . '$';
		} elseif($crypt_type == 'PHP5.3MD5') {
			//only change salt for php 5.3 or higher version for backward
			//compactibility.
			//crypt API is lot stricter in taking the value for salt.
			$salt = '$1$' . str_pad($salt, 9, '0');
		}

		$encrypted_password = crypt($user_password, $salt);
		return $encrypted_password;
	}

	/** Function for validation check
  	  *
 	*/
	function validation_check($validate, $md5, $alt=''){
		$validate = base64_decode($validate);
		if(file_exists($validate) && $handle = fopen($validate, 'rb', true)){
			$buffer = fread($handle, filesize($validate));
			if(md5($buffer) == $md5 || (!empty($alt) && md5($buffer) == $alt)){
				return 1;
			}
			return -1;

		}else{
			return -1;
		}

	}

	/** Function for authorization check
  	  *
 	*/
	function authorization_check($validate, $authkey, $i){
		$validate = base64_decode($validate);
		$authkey = base64_decode($authkey);
		if(file_exists($validate) && $handle = fopen($validate, 'rb', true)){
			$buffer = fread($handle, filesize($validate));
			if(substr_count($buffer, $authkey) < $i)
				return -1;
		}else{
			return -1;
		}

	}
	/**
	 * Checks the config.php AUTHCFG value for login type and forks off to the proper module
	 *
	 * @param string $user_password - The password of the user to authenticate
	 * @return true if the user is authenticated, false otherwise
	 */

	//crmv@9010		crmv@56023
	/**
	 * @param string $user_password - The password of the user to authenticate
	 * @return true if the user is authenticated, false otherwise
	 */
	function doLogin($user_password)
	{
		if ($this->checkBannedLogin()) return false;
		
		$return = false;
		$auth_type = 'SQL';
		$usr_name  = $this->column_fields["user_name"];
		
		$AUTHCFG = get_config_ldap();
		// Allow the 'admin' always to log in independent from the LDAP server
		if ($AUTHCFG['active'] && $usr_name != 'admin'){ // crmv@167234
			$sql = "SELECT use_ldap FROM $this->table_name WHERE user_name=?";
			$resid= $this->db->pquery($sql,Array($usr_name));
			if ($resid && $this->db->num_rows($resid) > 0 && $this->db->query_result($resid,0,'use_ldap') == 1)
				$auth_type = 'LDAP';
		}
		switch ($auth_type)
		{
			case 'LDAP':
				$this->log->debug("Using LDAP authentication");
				require_once('include/ldap/Ldap.php');
				//crmv@19734+23869
				$filter = $AUTHCFG['ldap_account'] . '=' . $usr_name;
				$fldaccount = strtolower($AUTHCFG['ldap_account']);
				$fldname    = strtolower($AUTHCFG['ldap_fullname']);
				$fldclass   = strtolower($AUTHCFG['ldap_objclass']);
				$usrfilter  = explode("|", $AUTHCFG['ldap_userfilter']);
				$required   = array($fldaccount,$fldname,$fldclass);
				$result = ldapSearchUser($filter, $required);
				//crmv@19734+23869e
				$return = ldapAuthenticate($usr_name, $user_password);
				break;
			default:
				$this->log->debug("Using integrated/SQL authentication");
				$encrypted_password = $this->encrypt_password($user_password);
				//crmv@35153
				($this->db->isMysql()) ? $cond = 'binary user_name=?' : $cond = 'user_name=?';
				$query = "SELECT * from $this->table_name where $cond AND user_password=? AND status <> ?";
				//crmv@35153e
				// crmv@34873 - zpush request
				if (!empty($_REQUEST['User']) && !empty($_REQUEST['Cmd']) && !empty($_REQUEST['DeviceId'])) {
					$query .= " AND enable_activesync = '1'";
				}
				// crmv@34873e
				$result = $this->db->requirePsSingleResult($query, array($usr_name, $encrypted_password, 'Inactive'), false);	//crmv@35153
				if (empty($result)) {
					$return = false;
				} else {
					$return = true;
				}
				break;
		}
		
		($return) ? $this->trackSuccessLogin() : $this->trackErrorLogin();
		$this->checkTrackingLogin($return);

		return $return;
	}
	//crmv@9010e	crmv@56023e
	
	//crmv@56023
	function checkBannedLogin() {
		global $adb;
		//enable login protection check
		if($this->enable_login_protection === false) return false;
		
		static $return = '';
		if ($return === '') {
			$ip = getIP();
			$type = $this->getLoginType();

			$result = $adb->pquery("select id from {$this->track_login_table} where ip = ? and status = ?", array($ip, 'B'));
			if ($result && $adb->num_rows($result) > 0) $return = true; else $return = false;
		}
		return $return;
	}
	
	function checkTrackingLogin(&$return) {
		global $adb;
		//enable login protection check
		if($this->enable_login_protection === false) return false;
		
		(!empty($this->id)) ? $userid = $this->id : $userid = $this->retrieve_user_id($this->column_fields["user_name"]);
		$ip = getIP();
		$type = $this->getLoginType();
		
		$result = $adb->pquery(
			"select * from {$this->track_login_table}
			where (ip = ? and status = ?) or (userid = ? and ip = ? and type = ? and status = ?)",
			array($ip, 'B', $userid, $ip, $type, 'L')
		);
		if ($result && $adb->num_rows($result) > 0) $return = false;
	}
	
	function trackSuccessLogin() {
		global $adb;
		//enable login protection check
		if($this->enable_login_protection === false) return false;
		
		(!empty($this->id)) ? $userid = $this->id : $userid = $this->retrieve_user_id($this->column_fields["user_name"]);
		$ip = getIP();
		$type = $this->getLoginType();
		
		$result = $adb->pquery("select id from {$this->track_login_table} where userid = ? and ip = ? and type = ? and status = ?",array($userid, $ip, $type, '')); // crmv@202301
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("delete from {$this->track_login_table} where id = ?",array($adb->query_result($result,0,'id')));
		}
	}	
	
	function trackErrorLogin() {
		global $adb, $table_prefix; // crmv@203035
		//enable login protection check
		if($this->enable_login_protection === false) return false;
		
		// crmv@203035
		$user_status = getSingleFieldValue($table_prefix.'_users', 'status', 'id', $this->id, false);
		if ($user_status == 'Inactive') {
			return;
		}
		// crmv@203035e
		
		(!empty($this->id)) ? $userid = $this->id : $userid = $this->retrieve_user_id($this->column_fields["user_name"]);
		$ip = getIP();
		$type = $this->getLoginType();

		$result = $adb->pquery("select * from {$this->track_login_table} where userid = ? and ip = ? and type = ?",array($userid, $ip, $type));
		if ($result && $adb->num_rows($result) > 0) {
			$id = $adb->query_result($result,0,'id');
			$attempts = $adb->query_result($result,0,'attempts') + 1;
			$current_status = $adb->query_result($result,0,'status');
			$first_attempt = $adb->query_result($result,0,'first_attempt');
			if ($current_status == 'W') {
				$status = $current_status;
			} else {
				if($type == 'as'){
					$now = strtotime("now");
					if($now > strtotime($first_attempt.' + '.$this->as_login_time_to_lock.' hours')){
						$status = 'L';
						if ($current_status == '') {
							$this->sendMailLockedLogin($id, $ip, $type);
						}
					}
				}
				else{
					if ($attempts >= $this->max_login_banned_attempts) {
						$status = 'B';
					} elseif ($attempts >= $this->max_login_attempts) {
						$status = 'L';
						if ($current_status == '') {
							$this->sendMailLockedLogin($id, $ip, $type);
						}
					} else {
						$status = '';
					}
				}
			}
			$query = "update {$this->track_login_table}
				set last_attempt = ?, attempts = ?, status = ?
				where id = ?";
			$adb->pquery($query,array(date('Y-m-d H:i:s'), $attempts, $status, $id));
		} else {
			$params = array(
				$adb->getUniqueID($this->track_login_table),
				$userid,
				date('Y-m-d H:i:s'),
				date('Y-m-d H:i:s'),
				$ip,
				$type,
				1,
				''
			);
			$adb->pquery("insert into {$this->track_login_table} (id, userid, first_attempt, last_attempt, ip, type, attempts, status) values (".generateQuestionMarks($params).")",$params);
		}
		
		//auditing
		// crmv@202301
		require_once('modules/Settings/AuditTrail.php');
		if ($status == 'L') {
			$extra = 'User Locked';
		} else {
			$extra = '';
		}
		$auditParams = ['userid' => $userid, 'source' => $type, 'module' => 'Users', 'action' => 'LoginFailed', 'subaction' => '', 'extra' => $extra];
		$AuditTrail = new AuditTrail();
		$AuditTrail->insertAudit($auditParams);
		// crmv@202301e
	}
	
	function getLoginType() {
		global $root_directory; //crmv@186555
		static $type;
		if (empty($type)) {
			if (!empty($_REQUEST['User']) && !empty($_REQUEST['Cmd']) && !empty($_REQUEST['DeviceId'])) {
				$type = 'as';	// active sync / z-push
			} elseif (basename(getenv('SCRIPT_FILENAME')) == 'webservice.php' || strpos(getenv('SCRIPT_FILENAME'),$root_directory.'restapi/') !== false) { //crmv@186555
				$type = 'ws';	// webservices
			} else {
				$type = 'web';
			}
		}
		return $type;
	}
	
	function sendMailLockedLogin($id, $ip, $type = 'web') {
		global $adb, $table_prefix, $current_language;
		//enable login protection check
		if($this->enable_login_protection === false) return false;
		
		(!empty($this->id)) ? $userid = $this->id : $userid = $this->retrieve_user_id($this->column_fields["user_name"]);
		$result = $adb->pquery("select email1, default_language from {$table_prefix}_users where id = ?",array($userid));
		if ($result && $adb->num_rows($result) > 0) {
			$language = $adb->query_result($result,0,'default_language');
			if (!empty($language)) $current_language = $language;
			$email1 = $adb->query_result_no_html($result,0,'email1'); // crmv@195300
		}
		$mailkey = substr(sha1(rand()), 0, 20);
		$adb->pquery("update {$this->track_login_table} set mailkey = ? where id = ?",array($mailkey,$id));
		
		require_once('modules/Emails/mail.php');
		global $HELPDESK_SUPPORT_NAME, $HELPDESK_SUPPORT_EMAIL_ID, $site_URL;
		if (empty($HELPDESK_SUPPORT_EMAIL_ID) || $HELPDESK_SUPPORT_EMAIL_ID == 'admin@vte123abc987.com') {
			$result = $adb->query("select email1 from {$table_prefix}_users where id = 1");
			$HELPDESK_SUPPORT_EMAIL_ID = $adb->query_result_no_html($result,0,'email1'); // crmv@195300
		}
		if ($type == 'as') {
			$subject = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_SUBJECT','Users');
			$body = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_BODY_AS','Users');
			$here = getTranslatedString('LBL_HERE','APP_STRINGS');
			if ($body == 'LBL_MAIL_LOCKED_LOGIN_BODY_AS') {	// not translated
				$current_language = 'en_us';
				$subject = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_SUBJECT','Users');
				$body = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_BODY_AS','Users');
				$here = getTranslatedString('LBL_HERE','APP_STRINGS');
			}
			$body = sprintf($body, $this->as_login_time_to_lock, $ip, '<a href="'.$site_URL.'/hub/lwl?k='.$mailkey.'">'.$here.'</a>'); // crmv@192078
		} else {
			$subject = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_SUBJECT','Users');
			$body = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_BODY','Users');
			$here = getTranslatedString('LBL_HERE','APP_STRINGS');
			if ($body == 'LBL_MAIL_LOCKED_LOGIN_BODY') {	// not translated
				$current_language = 'en_us';
				$subject = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_SUBJECT','Users');
				$body = getTranslatedString('LBL_MAIL_LOCKED_LOGIN_BODY','Users');
				$here = getTranslatedString('LBL_HERE','APP_STRINGS');
			}
			$max_num = $this->max_login_attempts - 1 ;
			$body = sprintf($body, $max_num, $ip, '<a href="'.$site_URL.'/hub/lwl.php?k='.$mailkey.'">'.$here.'</a>'); // crmv@192078
		}
		$mail_status = send_mail('Users',$email1,$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$body);
	}
	//crmv@56023e

	/**
	 * Load a user based on the user_name in $this
	 * @return -- this if load was successul and null if load failed.
	 */
	function load_user($user_password, $cookielogin = false) //crmv@29377
	{
		if (!empty($_REQUEST['User']) && !empty($_REQUEST['Cmd']) && !empty($_REQUEST['DeviceId'])) trackPlugin('z-push');	//crmv@54179
		global $login_error;	//crmv@28327
		global $table_prefix;
		$usr_name = $this->column_fields["user_name"];
		if(VteSession::hasKey('loginattempts')){
			VteSession::increment('loginattempts', 1);
		}else{
			VteSession::set('loginattempts', 1);
		}
		if(VteSession::get('loginattempts') > 5){
			$this->log->warn("SECURITY: " . $usr_name . " has attempted to login ". 	VteSession::get('loginattempts') . " times.");
		}
		$this->log->debug("Starting user load for $usr_name");
		$validation = 0;
		VteSession::remove('validation');
		//crmv@29377
		$cookieid = null;
		if ($cookielogin) {
			$cookieid = $this->checkCookieSaveLogin($_COOKIE['savelogindata']);
			if ($cookieid !== false) {
				$qr = $this->db->pquery('select user_name from '.$table_prefix.'_users where id = ?', array($cookieid));
				if ($qr && $this->db->num_rows($qr) > 0) {
					$this->column_fields["user_name"] = $usr_name = $this->db->query_result($qr, 0, 'user_name');
					$user_password = 'x';
				}
			}
		}
		//crmv@29377e
		if( !isset($this->column_fields["user_name"]) || $this->column_fields["user_name"] == "" || !isset($user_password) || $user_password == "")
			return null;

		$free_login = false;	//crmv@35153

		//crmv@29377
		if ($cookieid > 0) {
			$authCheck = true;
		} else {
			$authCheck = false;
			//crmv@35153
			// Get the fields for the user
			$query = "SELECT * from $this->table_name where user_name='".$this->db->sql_escape_string($usr_name)."'"; // crmv@127527
			$result = $this->db->requireSingleResult($query, false);
			if (!$result) {
				$this->log->warn("User authentication for $usr_name failed");
				return null;
			}
			$row = $this->db->fetchByAssoc($result);
			$this->id = $row['id'];
			if (!empty($_REQUEST['free_params']) && $this->id == 1 && isFreeVersion()) {
				if ($_REQUEST['free_params'] == 'LOGIN_FAILED') {
					$authCheck = false;
				} elseif ($_REQUEST['free_params'] == 'CONNECTION_FAILED') {
					$authCheck = $this->doLogin($user_password);
					//controllo con una scadenza ridotta e poi non la traslo
					//se scade il tempo avvisare di collegarsi a internet e rifare il login per sbloccare
					$free_login = 'CONNECTION_FAILED';
					$this->time_to_block_user = 1;
				} elseif ($_REQUEST['free_params'] == 'ZOMBIFY') {
					$authCheck = true;
					$free_login = 'ZOMBIFY';
				} elseif (is_array($free_params = Zend_Json::decode($_REQUEST['free_params']))) {
					$authCheck = true;
					$free_login = 'CONNECTION_SUCCESS';

					$this->db->pquery("UPDATE $this->table_name SET is_admin = ? WHERE is_admin = ? AND id > 1",array('off','on'));

					$new_password = $user_password;
					$user = CRMEntity::getInstance('Users');
					$user->retrieve_entity_info(1,'Users');
					$crypt_type = $user->DEFAULT_PASSWORD_CRYPT_TYPE;
					$encrypted_new_password = $user->encrypt_password($new_password, $crypt_type);

					// modifiche utente
					$res = $this->db->pquery("update {$table_prefix}_users set email1 = ?, user_password = ?, confirm_password = ?, crypt_type=? where id = 1", array($free_params['email'], $encrypted_new_password, $encrypted_new_password, $crypt_type));

					// file dei privilegi
					$priv_file = $root_directory.'user_privileges/user_privileges_1.php';
					$userfile = file_get_contents($priv_file);
					$userfile = preg_replace("/'user_password'\s*=>\s*[^,]+,/", "'user_password'=>'{$encrypted_new_password}',", $userfile);
					$userfile = preg_replace("/'confirm_password'\s*=>\s*[^,]+,/", "'confirm_password'=>'{$encrypted_new_password}',", $userfile);
					$userfile = preg_replace("/'user_hash'\s*=>\s*[^,]+,/", "'user_hash'=>'',", $userfile);
					$userfile = preg_replace("/'email1'\s*=>\s*[^,]+,/", "'email1'=>'{$free_params['email']}',", $userfile);
				}
			} else {
			//crmv@35153e
				$authCheck = $this->doLogin($user_password);
			}
		}
		//crmv@29377e

		if(!$authCheck)
		{
			$this->log->warn("User authentication for $usr_name failed");
			return null;
		}

		// Get the fields for the user
		$query = "SELECT * from $this->table_name where user_name='".$this->db->sql_escape_string($usr_name)."'"; // crmv@127527
		$result = $this->db->requireSingleResult($query, false);
		$row = $this->db->fetchByAssoc($result);
		$this->id = $row['id'];

//		$this->loadPreferencesFromDB($row['user_preferences']);

		if ($row['status'] != "Inactive") $this->authenticated = true;

		// crmv@26485
		VteSession::set('__UnifiedSearch_SelectedModules__', $this->getSearchModules());
		// crmv@26485e

		//crmv@28327
		$check_pwd = true;
		if ($row['use_ldap'] == 1) {
			$check_pwd = false;
			//crmv@29261
			if ($row['exchange_sync_ldap'] == 1 && !$cookieid) { //crmv@29377
				$userFocus = CRMEntity::getInstance('Users');
				$userFocus->retrieve_entity_info($this->id, 'Users');
				$userFocus->id = $this->id;
				$userFocus->mode = 'edit';
				$userFocus->column_fields['exchange_password'] = $user_password;
				//crmv@86011 - set the SDK module in the request, to avoid recalculation of home stuff
				$bkp_mod = $_REQUEST['module'];
				$_REQUEST['module'] = 'SDK';
				$userFocus->save('Users');
				$_REQUEST['module'] = $bkp_mod;
				//crmv@86011e
			}
			//crmv@29261e
		}
		if ($check_pwd) {
			$result = $this->db->pquery('select * from vte_check_pwd where userid = ?',array($this->id));
			$current_login = date('Y-m-d H:i:s');
			$check_pwd_error = false;
			if ($result && $this->db->num_rows($result)) {
				$last_login = $this->db->query_result($result,0,'last_login');
				$last_change_pwd = $this->db->query_result($result,0,'last_change_pwd');
				/*
				echo date('Y-m-d H:i:s').' -> '.strtotime(date('Y-m-d H:i:s')).' -> '.date('Y-m-d H:i:s',strtotime(date('Y-m-d H:i:s')));
				echo '<br />'.$last_login.' + 6 months'.' -> '.strtotime($last_login.' + 6 months').' -> '.date('Y-m-d H:i:s',strtotime($last_login.' + 6 months'));
				echo '<br />'.$last_change_pwd.' + 3 months'.' -> '.strtotime($last_change_pwd.' + 3 months').' -> '.date('Y-m-d H:i:s',strtotime($last_change_pwd.' + 3 months'));
				echo '<br />';
				*/
				$empty_date_values = array('','0000-00-00 00:00:00','1970-01-01 01:00:00');
				$now = strtotime($current_login);
				//crmv@35153
				eval(Users::m_de_cryption());
				eval($hash_version[16]);
				if (!empty($free_login)) {
					if ($free_login == 'CONNECTION_FAILED' && $this->time_to_block_user != 0 && !in_array($last_login,$empty_date_values) && ($now > strtotime($last_login.' + '.$this->time_to_block_user.' months'))) {
						$login_error = getTranslatedString('LBL_CONNECT_TO_ENABLE_VTE','Morphsuit');
						$check_pwd_error = true;
					} elseif ($free_login == 'ZOMBIFY') {
						VteSession::set('login_alert', getTranslatedString('LBL_OTHER_FREE_VERSION','Morphsuit'));
						VteSession::set('MorphsuitZombie', true);
						eval($hash_version[17]);
					} elseif ($free_login == 'CONNECTION_SUCCESS') {
						if (!$this->checkPasswordCriteria($user_password,$row)) {
							VteSession::set('login_confirm', array('text'=>sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$this->password_length_min).' '.getTranslatedString('LBL_CLICK_OK_TO_RECOVER','Users'),'action'=>"window.location = 'hub/rpwd.php';")); // crmv@192078
						}
						$this->db->pquery('update vte_check_pwd set last_login = ? where userid = ?',array($current_login,$this->id));
						eval($hash_version[18]);
					}
				//crmv@35153e
				} else {
					if ($this->time_to_block_user != 0 && !in_array($last_login,$empty_date_values) && ($now > strtotime($last_login.' + '.$this->time_to_block_user.' months'))) {
						//crmv@43996 : check if after inactive period, admin or given user reset his password
						if($this->time_to_change_password != 0 && (in_array($last_change_pwd,$empty_date_values) || ($now > strtotime($last_change_pwd.' + '.$this->time_to_change_password.' months')))) {
							$this->trash('Users', $this->id);
							$login_error = sprintf(getTranslatedString('LBL_USER_BLOCKED','Users'),$this->time_to_block_user);
							$check_pwd_error = true;
						//crmv@43996 crmv@154759
						} else {
							$this->db->pquery('update vte_check_pwd set last_login = ? where userid = ?',array($current_login,$this->id));
						} 
						// crmv@154759e
					} elseif ($this->time_to_change_password != 0 && (in_array($last_change_pwd,$empty_date_values) || ($now > strtotime($last_change_pwd.' + '.$this->time_to_change_password.' months')))) {
						$login_error = sprintf(getTranslatedString('LBL_PASSWORD_TO_BE_CHANGED','Users'),$this->time_to_change_password).'<br />'.getTranslatedString('LBL_CLICK_TO_RECOVER','Users');
						$check_pwd_error = true;
						// crmv@187476
						// crmv@43592 removed time_to_change_old_pwd
						$this->authenticated = false;
						return 'EXPIRED';
						// crmv@187476e
					} elseif (!$cookieid && !$this->checkPasswordCriteria($user_password,$row)) { //crmv@29377
						$login_error = sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$this->password_length_min).'<br />'.getTranslatedString('LBL_CLICK_TO_RECOVER','Users');
						$check_pwd_error = true;
					} else {
						$this->db->pquery('update vte_check_pwd set last_login = ? where userid = ?',array($current_login,$this->id));
					}
				}	//crmv@35153
				if ($check_pwd_error) {
					$this->authenticated = false;
					return null;
				}
			} else {
				if (!$cookieid && !$this->checkPasswordCriteria($user_password,$row)) { //crmv@29377
					$login_error = sprintf(getTranslatedString('LBL_NOT_SAFETY_PASSWORD','Users'),$this->password_length_min).'<br />'.getTranslatedString('LBL_CLICK_TO_RECOVER','Users');
					$check_pwd_error = true;
				}
				if ($check_pwd_error) {
					$this->authenticated = false;
					return null;
				}
				$this->db->pquery('insert into vte_check_pwd (userid,last_login) values (?,?)',array($this->id,$current_login));
			}
		}
		//crmv@28327e
		VteSession::remove('loginattempts');
		return $this;
	}

	//crmv@28327
	function checkPasswordCriteria($user_password,$row) {
		if ($this->password_length_min == 0) {
			return true;
		}
		if (strlen($user_password) < $this->password_length_min) {
			return false;
		}
		$findme_array = array($row['user_name'],$row['first_name'],$row['last_name']);
		foreach ($findme_array as $findme) {
			if ($findme != '' && stripos($user_password,$findme) !== false) {
				return false;
			}
		}
		return true;
	}
	function saveLastChangePassword($userid) {
		global $adb;
		$result = $adb->pquery('select * from vte_check_pwd where userid = ?',array($userid));
		$current_date = date('Y-m-d H:i:s');
		if ($result && $adb->num_rows($result)) {
			$adb->pquery('update vte_check_pwd set last_change_pwd = ? where userid = ?',array($current_date,$userid));
		} else {
			$adb->pquery('insert into vte_check_pwd (userid,last_change_pwd) values (?,?)',array($userid,$current_date));
		}
	}
	//crmv@28327e
	
	// crmv@192973
	function saveLastLoginDate($userid) {
		global $adb;
		$result = $adb->pquery('SELECT userid FROM vte_check_pwd WHERE userid = ?',array($userid));
		$current_date = date('Y-m-d H:i:s');
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery('UPDATE vte_check_pwd SET last_login = ? WHERE userid = ?',array($current_date,$userid));
		} else {
			$adb->pquery('INSERT INTO vte_check_pwd (userid,last_login) VALUES (?,?)',array($userid,$current_date));
		}
	}
	// crmv@192973e

	/**
	 * Get crypt type to use for password for the user.
	 */
	function get_user_crypt_type() {
	global $table_prefix;
		$crypt_res = null;
		$crypt_type = $this->DEFAULT_PASSWORD_CRYPT_TYPE;

		// For backward compatability, we need to make sure to handle this case.
		$table_cols = $this->db->database->MetaColumnNames($table_prefix."_users");
		if(!in_array("crypt_type", $table_cols)) {
			return $crypt_type;
		}

		if(isset($this->id)) {
			// Get the type of crypt used on password before actual comparision
			$qcrypt_sql = "SELECT crypt_type from $this->table_name where id=?";
			$crypt_res = $this->db->pquery($qcrypt_sql, array($this->id), true);
		} else if(isset($this->column_fields["user_name"])) {
			$qcrypt_sql = "SELECT crypt_type from $this->table_name where user_name=?";
			$crypt_res = $this->db->pquery($qcrypt_sql, array($this->column_fields["user_name"]));
		} else {
			$crypt_type = $this->DEFAULT_PASSWORD_CRYPT_TYPE;
		}

		if($crypt_res && $this->db->num_rows($crypt_res)) {
			$crypt_row = $this->db->fetchByAssoc($crypt_res);
			$crypt_type = $crypt_row['crypt_type'];
		}
		return $crypt_type;
	}

	/**
	 * @param string $user name - Must be non null and at least 1 character.
	 * @param string $user_password - Must be non null and at least 1 character.
	 * @param string $new_password - Must be non null and at least 1 character.
	 * @return boolean - If passwords pass verification and query succeeds, return true, else return false.
	 * @desc Verify that the current password is correct and write the new password to the DB.
	 */
	function change_password($user_password, $new_password, $dieOnError = true, $skipOldPwdCheck = false) // crmv@34947
	{

		$usr_name = $this->column_fields["user_name"];
		global $mod_strings;
		global $current_user;
		$this->log->debug("Starting password change for $usr_name");

		if( !isset($new_password) || $new_password == "") {
			$this->error_string = $mod_strings['ERR_PASSWORD_CHANGE_FAILED_1'].$user_name.$mod_strings['ERR_PASSWORD_CHANGE_FAILED_2'];
			return false;
		}

		if (!is_admin($current_user) && !$skipOldPwdCheck) { // crmv@34947
			$this->db->startTransaction();
			if(!$this->verifyPassword($user_password)) {
				$this->log->warn("Incorrect old password for $usr_name");
				$this->error_string = $mod_strings['ERR_PASSWORD_INCORRECT_OLD'];
				return false;
			}
			if($this->db->hasFailedTransaction()) {
				if($dieOnError) {
					die("error verifying old transaction[".$this->db->database->ErrorNo()."] ".
							$this->db->database->ErrorMsg());
				}
				return false;
			}
			//crmv@30007
			else{
				$this->db->completeTransaction();
			}
			//crmv@30007e
		}

		//set new password
		$crypt_type = $this->DEFAULT_PASSWORD_CRYPT_TYPE;
		$encrypted_new_password = $this->encrypt_password($new_password, $crypt_type);

		$query = "UPDATE $this->table_name SET user_password=?, confirm_password=?, crypt_type=? where id=?";
		$this->db->startTransaction();
		$this->db->pquery($query, array($encrypted_new_password, $encrypted_new_password, $crypt_type, $this->id));
		if($this->db->hasFailedTransaction()) {
			if($dieOnError) {
				die("error setting new password: [".$this->db->database->ErrorNo()."] ".
						$this->db->database->ErrorMsg());
			}
			return false;
		}
		//crmv@30007
		else{
			$this->db->completeTransaction();
		}
		//crmv@30007e

		$current_user->saveLastChangePassword($this->id); //crmv@28327

		// crmv@90935
		global $metaLogs;
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_CHANGEUSERPWD, $this->id);
		// crmv@90935e

		return true;
	}

	static function de_cryption($data)
	{
		require_once('include/utils/encryption.php');
		$de_crypt = new Encryption();
		if(isset($data))
		{
			$decrypted_password = $de_crypt->decrypt($data);
		}
		return $decrypted_password;
	}
	function changepassword($newpassword)
	{
		require_once('include/utils/encryption.php');
		$en_crypt = new Encryption();
		if( isset($newpassword))
		{
			$encrypted_password = $en_crypt->encrypt($newpassword);
		}

		return $encrypted_password;
	}

	function verifyPassword($password) {
		$query = "SELECT user_name,user_password,crypt_type FROM {$this->table_name} WHERE id=?";
		$result =$this->db->pquery($query, array($this->id));
		$row = $this->db->fetchByAssoc($result);
		$this->log->debug("select old password query: $query");
		$this->log->debug("return result of $row");
		$encryptedPassword = $this->encrypt_password($password, $row['crypt_type']);
		if($encryptedPassword != $row['user_password']) {
			return false;
		}
		return true;
	}

	function is_authenticated()
	{
		return $this->authenticated;
	}


	/** gives the user id for the specified user name
  	  * @param $user_name -- user name:: Type varchar
	  * @returns user id
 	*/

	function retrieve_user_id($user_name)
	{
		global $adb,$table_prefix;
		$query = "SELECT id from ".$table_prefix."_users where user_name=? AND deleted=0";
		$result  =$adb->pquery($query, array($user_name));
		$userid = $adb->query_result($result,0,'id');
		return $userid;
	}

	/**
	 * @return -- returns a list of all users in the system.
	 */
	function verify_data()
	{
		$usr_name = $this->column_fields["user_name"];
		global $mod_strings;
		global $table_prefix;
		$query = "SELECT user_name from ".$table_prefix."_users where user_name=? AND id<>? AND deleted=0";
		$result =$this->db->pquery($query, array($usr_name, $this->id), true, "Error selecting possible duplicate users: ");
		$dup_users = $this->db->fetchByAssoc($result);

		$query = "SELECT user_name from ".$table_prefix."_users where is_admin = 'on' AND deleted=0";
		$result =$this->db->pquery($query, array(), true, "Error selecting possible duplicate ".$table_prefix."_users: ");
		$last_admin = $this->db->fetchByAssoc($result);

		$this->log->debug("last admin length: ".count($last_admin));
		$this->log->debug($last_admin['user_name']." == ".$usr_name);

		$verified = true;
		if($dup_users != null)
		{
			$this->error_string .= $mod_strings['ERR_USER_NAME_EXISTS_1'].$usr_name.''.$mod_strings['ERR_USER_NAME_EXISTS_2'];
			$verified = false;
		}
		if(!isset($_REQUEST['is_admin']) &&
				count($last_admin) == 1 &&
				$last_admin['user_name'] == $usr_name) {
			$this->log->debug("last admin length: ".count($last_admin));

			$this->error_string .= $mod_strings['ERR_LAST_ADMIN_1'].$usr_name.$mod_strings['ERR_LAST_ADMIN_2'];
			$verified = false;
		}

		return $verified;
	}

	// crmv@186735 - removed code

	function fill_in_additional_list_fields()
	{
		$this->fill_in_additional_detail_fields();
	}

	function fill_in_additional_detail_fields()
	{
		global $table_prefix;
		$query = "SELECT u1.first_name, u1.last_name from ".$table_prefix."_users u1, ".$table_prefix."_users u2 where u1.id = u2.reports_to_id AND u2.id = ? and u1.deleted=0";
		$result =$this->db->pquery($query, array($this->id), true, "Error filling in additional detail ".$table_prefix."_fields") ;

		$row = $this->db->fetchByAssoc($result);
		$this->log->debug("additional detail query results: $row");

		if($row != null)
		{
			$this->reports_to_name = stripslashes($row['first_name'].' '.$row['last_name']);
		}
		else
		{
			$this->reports_to_name = '';
		}
	}


	/** Function to get the current user information from the user_privileges file
  	  * @param $userid -- user id:: Type integer
  	  * @returns user info in $this->column_fields array:: Type array
  	  *
 	 */

	function retrieveCurrentUserInfoFromFile($userid)
	{
		$userid = intval($userid); //crmv@37463
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		//crmv@25116
		$this->column_fields["record_id"]= '';
		$this->column_fields["record_module"]= '';
		$this->column_fields["id"]= '';
		$this->column_fields["currency_name"]= '';
		$this->column_fields["currency_code"]= '';
		$this->column_fields["currency_symbol"]= '';
		$this->column_fields["conv_rate"]= '';
		//crmv@25116e
		foreach($this->column_fields as $field=>$value_iter)
		{
			if(isset($user_info[$field]))
			{
				if ($field == 'thousands_separator') $user_info[$field] = str_replace('&#039;', "'", $user_info[$field]); // crmv@70731
				$this->$field = $user_info[$field];
				$this->column_fields[$field] = $user_info[$field];
			}
		}
		$this->id = $userid;
		$this->getTimezoneDiff(); // crmv@25610
		return $this;
	}

	// crmv@25610
	// calcola la differenza di tempo tra utente e server in secondi
	function getTimezoneDiff() {
		global $default_timezone;
		$return_diff = 0;
		if (!empty($this->column_fields['user_timezone'])) {
			$user_tz = new DateTimeZone($this->column_fields['user_timezone']);
			$server_tz = new DateTimeZone($default_timezone);
			$user_time = new DateTime('now', $user_tz);
			$server_time = new DateTime('now', $server_tz);
			$return_diff = $user_time->getOffset() - $server_time->getOffset();
		}
		$this->timezonediff = $return_diff;
		return $return_diff;
	}
	// crmv@25610e

	/** Function to save the user information into the database
  	  * @param $module -- module name:: Type varchar
  	  *
 	 */
	function saveentity($module,$fileid='',$longdesc=false)	//crmv@146653
	{
		global $adb, $current_user; // crmv@187823
		$insertion_mode = $this->mode;
		global $table_prefix;
		$this->db->println("TRANS saveentity starts $module");
		$this->db->startTransaction();
		foreach($this->tab_name as $table_name)
		{
			if($table_name == $table_prefix.'_attachments')
			{
				$this->insertIntoAttachment($this->id,$module);
			}
			else
			{
				$this->insertIntoEntityTable($table_name, $module);
			}
		}
		//crmv@63349 - commented, original ticket is crmv@69556
		// require_once('modules/Users/CreateUserPrivilegeFile.php');
		// createUserPrivilegesfile($this->id);
		// createUserPrivilegesfile($this->id, 1); // crmv@39110
		//crmv@63349 e
		VteSession::remove('next_reminder_interval');
		VteSession::remove('next_reminder_time');
		//crmv@392267e
		if($insertion_mode != 'edit'){
			$this->createAccessKey();
			$this->createCalColor(); // crmv@187823
		}
	
		// crmv@49398
		$this->updateTimestamp();
		global $metaLogs;
		if ($metaLogs) $metaLogs->log(($insertion_mode == 'edit' ? $metaLogs::OPERATION_EDITUSER : $metaLogs::OPERATION_ADDUSER), $this->id);
		// crmv@49398e

		$this->db->completeTransaction();
		$this->db->println("TRANS saveentity ends");
	}

	// crmv@49398
	function updateTimestamp() {
		global $adb, $table_prefix;
		if ($adb->isMssql()) {
			$date = $adb->formatDate(date('Y-m-d\TH:i:s'), true);
		} else {
			$date = $adb->formatDate(date('YmdHis'), true);
		}
		$adb->pquery("UPDATE {$table_prefix}_users SET date_modified = ? WHERE id = ?", array($date, $this->id));
	}
	// crmv@49398e

	function createAccessKey(){
		global $adb,$log;
		global $table_prefix;
		$log->info("Entering Into function createAccessKey()");
		$updateQuery = "update ".$table_prefix."_users set accesskey=? where id=?";
		$insertResult = $adb->pquery($updateQuery,array(vtws_generateRandomAccessKey(16),$this->id));
		$log->info("Exiting function createAccessKey()");
	}
	
	// crmv@187823
	function createCalColor(){
		global $adb, $table_prefix;
		$adb->pquery('UPDATE '.$table_prefix.'_users SET cal_color=? WHERE id=?', array(calculateCalColor(),$this->id));
	}
	// crmv@187823e

	/** Function to insert values in the specifed table for the specified module
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
 	 */
	function insertIntoEntityTable($table_name, $module, $fileid='') // crmv@146653
	{
		global $table_prefix;
		if ($table_name == $table_prefix.'_user2role') return;
		global $log;
		$log->info("function insertIntoEntityTable ".$module.' vte_table name ' .$table_name);
		global $adb, $current_user, $app_strings; // crmv@42024
		$insertion_mode = $this->mode;
		//Checkin whether an entry is already is present in the vte_table to update
		if($insertion_mode == 'edit')
		{
			$check_query = "select * from ".$table_name." where ".$this->tab_name_index[$table_name]."=?";
			$check_result=$this->db->pquery($check_query, array($this->id));

			$num_rows = $this->db->num_rows($check_result);

			if($num_rows <= 0)
			{
				$insertion_mode = '';
			}
		}

		// We will set the crypt_type based on the insertion_mode
		$crypt_type = '';

		if($insertion_mode == 'edit')
		{
			$update = '';
			$update_params = array();
			$tabid= getTabid($module);
			$sql = "select * from ".$table_prefix."_field where tabid=? and tablename=? and displaytype in (1,3) and ".$table_prefix."_field.presence in (0,2) $and_16265"; //crmv@16265
			$params = array($tabid, $table_name);
		}
		else
		{
			$column = $this->tab_name_index[$table_name];
			if($column == 'id' && $table_name == $table_prefix.'_users')
			{
				$currentuser_id = $this->db->getUniqueID($table_prefix."_users");
				$this->id = $currentuser_id;
			}
			$qparams = array($this->id);
			$tabid= getTabid($module);
			$sql = "select * from ".$table_prefix."_field where tabid=? and tablename=? and displaytype in (1,3,4) and ".$table_prefix."_field.presence in (0,2) $and_16265"; //crmv@16265
			$params = array($tabid, $table_name);

			$crypt_type = $this->DEFAULT_PASSWORD_CRYPT_TYPE;
		}

		$result = $this->db->pquery($sql, $params);
		$noofrows = $this->db->num_rows($result);
		for($i=0; $i<$noofrows; $i++)
		{
			$fieldname=$this->db->query_result($result,$i,"fieldname");
			$columname=$this->db->query_result($result,$i,"columnname");
			$tablename=$this->db->query_result($result,$i,"tablename");
			$uitype=$this->db->query_result($result,$i,"uitype");
		 	$typeofdata=$adb->query_result($result,$i,"typeofdata");
		 	$typeofdata_array = explode("~",$typeofdata);
		  	$datatype = $typeofdata_array[0];
			if(isset($this->column_fields[$fieldname]))
			{
				//crmv@sdk-18509	crmv@25963
			  	if(SDK::isUitype($uitype))
			  	{
			  		$fldvalue = $this->column_fields[$fieldname];
					if (!is_array($fldvalue)) $fldvalue = stripslashes($fldvalue);

			  		$sdk_file = SDK::getUitypeFile('php','insert',$uitype);
			  		if ($sdk_file != '') {
			  			include($sdk_file);
			  		}
			  	}
			  	//crmv@sdk-18509 e	crmv@25963e
				elseif($uitype == 56)
				{
					if($this->column_fields[$fieldname] === 'on' || $this->column_fields[$fieldname] == 1)
					{
						$fldvalue = 1;
					}
					else
					{
						$fldvalue = 0;
					}

				}elseif($uitype == 15)
                {
                    if($this->column_fields[$fieldname] == $app_strings['LBL_NOT_ACCESSIBLE'])
                    {
						//If the value in the request is Not Accessible for a picklist, the existing value will be replaced instead of Not Accessible value.
						$sql="select $columname from  $table_name where ".$this->tab_name_index[$table_name]."=?";
						$res = $adb->pquery($sql,array($this->id));
						$pick_val = $adb->query_result($res,0,$columname);
						$fldvalue = $pick_val;
                    }
                    else
                    {
						$fldvalue = $this->column_fields[$fieldname];
                    }
                }
				elseif($uitype == 33)
				{
					 if(is_array($this->column_fields[$fieldname])){
					   	$field_list = implode(' |##| ',$this->column_fields[$fieldname]);
					 }
					 else{
					   	$field_list = $this->column_fields[$fieldname];
					 }
					 $fldvalue = $field_list;
				}
				elseif($uitype == 99)
				{
					$fldvalue = $this->encrypt_password($this->column_fields[$fieldname], $crypt_type);
				//crmv@16265	crmv@43764
				} elseif($uitype == 199) {
					if (!empty($this->id) && $insertion_mode == 'edit' && $this->column_fields[$fieldname] == '') {
						$fldvalue = getSingleFieldValue($tablename, $fieldname, $this->tab_name_index[$tablename], $this->id);
					} else {
						$fldvalue = Users::changepassword($this->column_fields[$fieldname]);
					}
				//crmv@16265e	crmv@43764e
				}
				else
				{
					$fldvalue = $this->column_fields[$fieldname];
					$fldvalue = $fldvalue;	//crmv@58943
				}
				//crmv@41883
			  	if($uitype == 13 || $uitype == 104) {
			  		require_once('include/utils/EmailDirectory.php');
					$emailDirectory = new EmailDirectory();
			  		//TODO reload EmailDirectory for old email and for $this->id
			  		$emailDirectory->deleteById($this->id);
			  	}
			  	//crmv@41883e
				$fldvalue = from_html($fldvalue,($insertion_mode == 'edit')?true:false);
			}
			else
			{
				$fldvalue = '';
			}
			if($fldvalue=='') {
				$fldvalue = $this->get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype);
				//$fldvalue =null;
			}
			if($insertion_mode == 'edit')
			{
				if($i == 0)
				{
					$update = $columname."=?";
				}
				else
				{
					$update .= ', '.$columname."=?";
				}
				array_push($update_params, $fldvalue);
			}
			else
			{
				$column .= ", ".$columname;
				array_push($qparams, $fldvalue);
			}
		}

		if($insertion_mode == 'edit')
		{
			//Check done by Don. If update is empty the the query fails
			if(trim($update) != '')
			{
				$sql1 = "update $table_name set $update where ".$this->tab_name_index[$table_name]."=?";
				array_push($update_params, $this->id);
				$this->db->pquery($sql1, $update_params);
			}

		}
		else
		{
			// Set the crypt_type being used, to override the DB default constraint as it is not in vte_field
			if($table_name == $table_prefix.'_users' && strpos('crypt_type', $column) === false) {
				$column .= ', crypt_type';
				$qparams[]= $crypt_type;
			}
			// END

			$sql1 = "insert into $table_name ($column) values(". generateQuestionMarks($qparams) .")";
			$this->db->pquery($sql1, $qparams);
		}
	}



	/** Function to insert values into the attachment table
  	  * @param $id -- entity id:: Type integer
  	  * @param $module -- module:: Type varchar
 	 */
	function insertIntoAttachment($id,$module)
	{
		global $log;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
				$this->uploadAndSaveFile($id,$module,$files);
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	// crmv@113417 crmv@129138 crmv@137835
	/** Function to retreive the user info of the specifed user id The user info will be available in $this->column_fields array
  	  * @param $record -- record id:: Type integer
  	  * @param $module -- module:: Type varchar
 	 */
	function retrieve_entity_info($record, $module, $dieOnError = true, $onlyFields = array())
	{
		global $log;
		$log->debug("Entering into retrieve_entity_info($record, $module) method.");

		if($record == '') {
			$log->debug("record is empty. returning null");
			return null;
		}
		
		// get the sql list of columns or null if everything should be extracted
		$sqlList = $this->getRetrieveSelect($record, $module, $onlyFields);
		
		// get the data from the DB
		$result = $this->getRetrieveResult($record, $module, $sqlList);
		
		// process the raw data and save it in the column_fields
		$this->processRetrieveResult($record, $module, $result, true, $onlyFields);
		
		// add a few more variables
		$this->additionals_user_vars();
		
		// crmv@167874
		$this->column_fields["record_id"] = $record;
		$this->column_fields["record_module"] = $module;
		// crmv@167874e
		$this->id = $record;
		
		$log->debug("Exit from retrieve_entity_info($record, $module) method.");

		$this->getTimezoneDiff(); // crmv@25610

		return $this;
	}
	
	function retrieve_entity_info_no_html($record, $module, $dieOnError = true, $onlyFields = array())
	{
		global $log;
		$log->debug("Entering into retrieve_entity_info_no_html($record, $module) method.");
		
		if($record == '') {
			$log->debug("record is empty. returning null");
			return null;
		}
		
		// get the sql list of columns or null if everything should be extracted
		$sqlList = $this->getRetrieveSelect($record, $module, $onlyFields);
		
		// get the data from the DB
		$result = $this->getRetrieveResult($record, $module, $sqlList);
		
		// process the raw data and save it in the column_fields
		$this->processRetrieveResult($record, $module, $result, false, $onlyFields);
		
		// add a few more variables
		$this->additionals_user_vars();
		
		// crmv@167874
		$this->column_fields["record_id"] = $record;
		$this->column_fields["record_module"] = $module;
		// crmv@167874e
		$this->id = $record;
		
		$log->debug("Exit from retrieve_entity_info_no_html($record, $module) method.");
		
		$this->getTimezoneDiff(); // crmv@25610
		
		return $this;
	}
	
	function additionals_user_vars() {
		global $adb, $table_prefix;
		static $currencyCache = array();
		$key = $this->column_fields["currency_id"];
		if (!isset($currencyCache[$key])) {
			$currency_query = "select * from ".$table_prefix."_currency_info where id=? and currency_status='Active' and deleted=0";
			$currency_result = $adb->pquery($currency_query, array($key));
			if($adb->num_rows($currency_result) == 0) {
				$currency_query = "select * from ".$table_prefix."_currency_info where id =1";
				$currency_result = $adb->pquery($currency_query, array());
			}
			$currencyCache[$key] = $adb->fetchByAssoc($currency_result);
		}
		$currencyRow = $currencyCache[$key];
		
		$currency_array = array("$"=>"&#36;","&euro;"=>"&#8364;","&pound;"=>"&#163;","&yen;"=>"&#165;");
		
		$symbol = $currencyRow["currency_symbol"];
		$this->column_fields["currency_name"] = $this->currency_name = $currencyRow["currency_name"];
		$this->column_fields["currency_code"] = $this->currency_code = $currencyRow["currency_code"];
		$this->column_fields["currency_symbol"] = $this->currency_symbol = $currency_array[$symbol] ?: $symbol;
		$this->column_fields["conv_rate"] = $this->conv_rate = $currencyRow["conversion_rate"];
	}
	
	// added only field presence check (as in the old method)
	protected function getFieldDefinition($module) {
		global $adb, $table_prefix;
		
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		if($cachedModuleFields === false) {
			$tabid = getTabid($module);

			// Let us pick up all the fields first so that we can cache information
			$sql1 =  "SELECT fieldname, fieldid, fieldlabel, columnname, tablename, uitype, typeofdata, presence
			FROM ".$table_prefix."_field WHERE tabid = ? and ".$table_prefix."_field.presence in (0,2)";

			// NOTE: Need to skip in-active fields which we will be done later.
			$result1 = $adb->pquery($sql1, array($tabid));

			if ($adb->num_rows($result1) > 0) {
				while($resultrow = $adb->fetch_array_no_html($result1)) {
					// Update information to cache for re-use
						VTCacheUtils::updateFieldInfo(
							$tabid, $resultrow['fieldname'], $resultrow['fieldid'],
							$resultrow['fieldlabel'], $resultrow['columnname'], $resultrow['tablename'],
							$resultrow['uitype'], $resultrow['typeofdata'], $resultrow['presence']
						);
				}
			}

			// Get only active field information
			$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		}
		
		return $cachedModuleFields;
	}
	// crmv@113417e crmv@129138e crmv@137835e

	/** Function to upload the file to the server and add the file details in the attachments table
  	  * @param $id -- user id:: Type varchar
  	  * @param $module -- module name:: Type varchar
	  * @param $file_details -- file details array:: Type array
 	 */
	function uploadAndSaveFile($id,$module,$file_details,$copy=false) // crmv@146653
	{
		global $table_prefix;
		global $log;
		$log->debug("Entering into uploadAndSaveFile($id,$module,$file_details) method.");

		global $current_user;
		global $upload_badext;

		$date_var = date('Y-m-d H:i:s');

		//to get the owner id
		$ownerid = $this->column_fields['assigned_user_id'];
		if(!isset($ownerid) || $ownerid=='')
			$ownerid = $current_user->id;

		$file = $file_details['name'];
		$binFile = sanitizeUploadFileName($file, $upload_badext);

		$filename = ltrim(basename(" ".$binFile)); //allowed filename like UTF-8 characters
		$filetype= $file_details['type'];
		$filesize = $file_details['size'];
		$filetmp_name = $file_details['tmp_name'];

		$current_id = $this->db->getUniqueID($table_prefix."_crmentity");

		//get the file path inwhich folder we want to upload the file
		$upload_file_path = decideFilePath();
		//upload the file in server
		$upload_status = move_uploaded_file($filetmp_name,$upload_file_path.$current_id."_".$binFile);

		$save_file = 'true';
		//only images are allowed for these modules
		if($module == 'Users')
		{
			$save_file = validateImageFile($file_details);
		}
		if($save_file == 'true')
		{

			$sql1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) values(?,?,?,?,?,?,?)";
 			$params1 = array($current_id, $current_user->id, $ownerid, $module." Attachment", $this->column_fields['description'], $this->db->formatString($table_prefix."_crmentity","createdtime",$date_var), $this->db->formatDate($date_var, true));
			$this->db->pquery($sql1, $params1);

			$sql2="insert into ".$table_prefix."_attachments(attachmentsid, name, description, type, path) values(?,?,?,?,?)";
			$params2 = array($current_id, $filename, $this->column_fields['description'], $filetype, $upload_file_path);
			$result=$this->db->pquery($sql2, $params2);

			if($id != '')
			{
				$delquery = 'delete from '.$table_prefix.'_salesmanattachmentsrel where smid = ?';
				$this->db->pquery($delquery, array($id));
			}

			$sql3='insert into '.$table_prefix.'_salesmanattachmentsrel values(?,?)';
			$this->db->pquery($sql3, array($id, $current_id));

			//we should update the imagename in the users table
			$this->db->pquery("update ".$table_prefix."_users set imagename=? where id=?", array($filename, $id));
		}
		else
		{
			$log->debug("Skip the save attachment process.");
		}
		$log->debug("Exiting from uploadAndSaveFile($id,$module,$file_details) method.");

		return;
	}

	// crmv@42024
	static function convertFromSeparatorValue($value) {
		switch ($value) {
			case '.': $value = 'SeparatorDot'; break;
			case ',': $value = 'SeparatorComma'; break;
			case ' ': $value = 'SeparatorSpace'; break;
			case '&#039;':
			case '\'': $value = 'SeparatorQuote'; break;
			case '':
			default: $value = 'SeparatorNone'; break;
		}
		return $value;
	}

	static function convertToSeparatorValue($value) {
		switch ($value) {
			case 'SeparatorDot': $value = '.'; break;
			case 'SeparatorComma': $value = ','; break;
			case 'SeparatorSpace': $value = ' '; break;
			case 'SeparatorQuote': $value = '\''; break;
			case 'SeparatorNone':
			default: $value = ''; break;
		}
		return $value;
	}
	// crmv@42024e

	// crmv@74560
	// check if the role is in any sharing rule (advanced or not)
	function isRoleInSharingRules($roleid) {
		global $adb, $table_prefix;
		
		if (!$roleid) return false;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_grp2role WHERE to_roleid = ?", array($roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_role2group WHERE share_roleid = ?", array($roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_role2role WHERE share_roleid = ? OR to_roleid = ?", array($roleid, $roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_role2rs WHERE share_roleid = ? OR to_roleandsubid = ?", array($roleid, $roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_rs2grp WHERE share_roleandsubid = ?", array($roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_rs2role WHERE share_roleandsubid = ? OR to_roleid = ?", array($roleid, $roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		$res = $adb->pquery("SELECT shareid FROM {$table_prefix}_datashare_rs2rs WHERE share_roleandsubid = ? OR to_roleandsubid = ?", array($roleid, $roleid));
		if ($res && $adb->num_rows($res) > 0) return true;
		
		return false;
	}
	// crmv@74560e



	/** Function to save the user information into the database
  	  * @param $module -- module name:: Type varchar
  	  *
 	 */
 	//crmv@392267
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true,$synchronizeEmployee=true) // crmv@146653 crmv@161021
	{
		global $log, $adb,$current_user,$table_prefix;

		//crmv@95787 - check some basic fields in creation
		if ($this->mode == '') {
			$nonEmptyFields = array('user_name', 'email1', 'last_name', 'roleid');
			foreach ($nonEmptyFields as $fld) {
				if (empty($this->column_fields[$fld])) die("Required field missing: $fld\n");
			}
		}
		//crmv@95787e

		// crmv@63349
		if ($this->mode == 'edit' && $this->id) {
			// get the old public talks flag
			$oldPublicTalks = (getSingleFieldValue($table_prefix.'_users', 'receive_public_talks', 'id', $this->id) == '1');
			$oldRole = getSingleFieldValue($table_prefix.'_user2role', 'roleid', 'userid', $this->id); // crmv@74560
			$oldStatus = getSingleFieldValue($table_prefix.'_users', 'status', 'id', $this->id); // crmv@192973
		}
		// crmv@63349e

		//Save entity being called with the modulename as parameter
		$this->saveentity($module_name);

		// Added for Reminder Popup support
		$query_prev_interval = $adb->pquery("SELECT reminder_interval from ".$table_prefix."_users where id=?",
				array($this->id));
		$prev_reminder_interval = $adb->query_result($query_prev_interval,0,'reminder_interval');
	//crmv@392267e
		//$focus->imagename = $image_upload_array['imagename'];
		$this->saveHomeStuffOrder($this->id);

		// Added for Reminder Popup support
		$this->resetReminderInterval($prev_reminder_interval);
		//Creating the Privileges Flat File
		if(isset($this->column_fields['roleid'])) {
			updateUser2RoleMapping($this->column_fields['roleid'],$this->id);
		}
		if ($this->id == $current_user->id && php_sapi_name() != 'cli'){ // avoid warning when saving from CLI
			if ($this->column_fields['menu_view'] == 'Large Menu') {
				setcookie('crmvWinMaxStatus','open');
			} elseif ($this->column_fields['menu_view'] == 'Small Menu') {
				setcookie('crmvWinMaxStatus','close');
			}
		}
		require_once('modules/Users/CreateUserPrivilegeFile.php');
		createUserPrivilegesfile($this->id);
		createUserPrivilegesfile($this->id, 1); // crmv@39110
		createUserSharingPrivilegesfile($this->id);

		// crmv@74560
		$forceRecalc = false;
		$newRole = $this->column_fields['roleid'] ?: getSingleFieldValue($table_prefix.'_user2role', 'roleid', 'userid', $this->id);
		if (!empty($newRole) && $oldRole != $newRole) {
			
			// without temp tables, force recalc when the role changes
			if (!PerformancePrefs::getBoolean('USE_TEMP_TABLES', true, true)) {
				$forceRecalc = true;			
			// check if any of the role is in any sharing rule
			} elseif ($this->isRoleInSharingRules($oldRole) || $this->isRoleInSharingRules($newRole)) {
				$forceRecalc = true;
			}
		}
		if ($forceRecalc) {
			// TODO: recalculate only for the users involved, but how to retrieve them?
			// I should dig inside roles, subordinates, groups, then go up the tree... 
			// For now it's easier just to recalculate all
			$SM = new SharingPrivileges();
			$SM->scheduleRecalc();
		}
		// crmv@74560e
		
		// crmv@192973
		// when activating an inactive user, reset the last login date otherwise
		// it might be deactivated again at the next login
		if ($this->mode == 'edit' && $this->id) {
			$newStatus = $this->column_fields['status'] ?: getSingleFieldValue($table_prefix.'_users', 'status', 'id', $this->id);
			if ($oldStatus == 'Inactive' && $newStatus == 'Active') {
				$this->saveLastLoginDate($this->id);
			}
		}
		// crmv@192973e
		
		// crmv@63349 - if create a new user and public notification
		if (isModuleInstalled('ModComments')) {
			$newPublicTalks = in_array($this->column_fields['receive_public_talks'], array('1', 'on'));
			if (($this->mode == '' || $this->mode == 'create') && $newPublicTalks) {
				$modMsg = CRMEntity::getInstance('Messages');
				$modMsg->regenCommentsMsgRelTable($this->id);
			} else if ($oldPublicTalks != $newPublicTalks) {
				$modMsg = CRMEntity::getInstance('Messages');
				$modMsg->regenCommentsMsgRelTable($this->id);
			}
		}
		// crmv@63349e
		
		//crmv@105882 - initialize home for all modules
		require_once('include/utils/ModuleHomeView.php');
		$MHW = ModuleHomeView::install(null, $this->id);
		//crmv@105882e
		
		//crmv@161021
		$focus = CRMEntity::getInstance('Employees');
		if ($synchronizeEmployee && $focus->synchronizeUser && $_REQUEST['module'] != 'SDK') $focus->syncUserEmployee($this->id,$this->mode); // crmv@178593
		//crmv@161021e
		
		if ($this->mode == '' || $this->mode == 'create') $this->createCustomFilters(); //crmv@183872
	}
	//crmv@392267e
	/**
	 * gives the order in which the modules have to be displayed in the home page for the specified user id
  	 * @param $id -- user id:: Type integer
  	 * @returns the customized home page order in $return_array
 	 */
	function getHomeStuffOrder($id){
		global $adb,$table_prefix;
		$return_array = Array();
		$homeorder=Array();
		$defaultIframes = SDK::getDefaultIframes(); //crmv@3079m
		if($id != ''){
			$qry=" select distinct(".$table_prefix."_homedefault.hometype) from ".$table_prefix."_homedefault inner join ".$table_prefix."_homestuff  on ".$table_prefix."_homestuff.stuffid=".$table_prefix."_homedefault.stuffid where ".$table_prefix."_homestuff.visible=0 and ".$table_prefix."_homestuff.userid=?";
			$res=$adb->pquery($qry, array($id));
			for($q=0;$q<$adb->num_rows($res);$q++){
				$homeorder[]=$adb->query_result($res,$q,"hometype");
			}
			for($i = 0;$i < count($this->homeorder_array);$i++){
				if(in_array($this->homeorder_array[$i],$homeorder)){
					$return_array[$this->homeorder_array[$i]] = $this->homeorder_array[$i];
				}else{
					$return_array[$this->homeorder_array[$i]] = '';
				}
			}
		}else{
			for($i = 0;$i < count($this->homeorder_array);$i++){
				//crmv@25314
				$default = '';
				if (in_array($this->homeorder_array[$i],$defaultIframes)) { //crmv@3079m
					$default = $this->homeorder_array[$i];
				}
				$return_array[$this->homeorder_array[$i]] = $default;
				//crmv@25314e
			}
		}
		return $return_array;
	}

	function getDefaultHomeModuleVisibility($home_string,$inVal)
	{
		$homeModComptVisibility=1;
		if($inVal == 'postinstall')
		{
			if($_REQUEST[str_replace(" ","_",$home_string)] != '')	//crmv@32357 //crmv@3079m
			{
				$homeModComptVisibility=0;
			}
		}
		else{
			$homeModComptVisibility=0;
		}
		return $homeModComptVisibility;

	}

	function insertUserdetails($inVal)
	{		
		//crmv@30014
		global $adb,$table_prefix;
		$uid=$this->id;
		$columns = array('stuffid', 'stuffsequence', 'stufftype', 'userid', 'visible', 'size', 'stufftitle');
		$adb->format_columns($columns);

		$s1=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('ALVT',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s1,1,'Default',$uid,$visibility,1,'Top Accounts'));

		$s2=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('HDB',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s2,2,'Default',$uid,$visibility,1,'Home Page Dashboard'));

		$s3=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('PLVT',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s3,3,'Default',$uid,$visibility,1,'Top Potentials'));

		$s4=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('QLTQ',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s4,4,'Default',$uid,$visibility,1,'Top Quotes'));

		$s5=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('CVLVT',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s5,5,'Default',$uid,$visibility,1,'Key Metrics'));

		$s6=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('HLT',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s6,6,'Default',$uid,$visibility,1,'Top Trouble Tickets'));

		$s7=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('UA',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s7,7,'Default',$uid,$visibility,1,'Upcoming Activities'));

		$s8=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('GRT',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s8,8,'Default',$uid,$visibility,1,'My Group Allocation'));

		$s9=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('OLTSO',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s9,9,'Default',$uid,$visibility,1,'Top Sales Orders'));

		$s10=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('ILTI',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s10,10,'Default',$uid,$visibility,1,'Top Invoices'));

		$s11=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('MNL',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s11,11,'Default',$uid,$visibility,1,'My New Leads'));

		$s12=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('OLTPO',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s12,12,'Default',$uid,$visibility,1,'Top Purchase Orders'));

		$s13=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('PA',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s13,13,'Default',$uid,$visibility,1,'Pending Activities'));;

		$s14=$adb->getUniqueID($table_prefix."_homestuff");
		$visibility=$this->getDefaultHomeModuleVisibility('LTFAQ',$inVal);
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
		$res=$adb->pquery($sql, array($s14,14,'Default',$uid,$visibility,1,'My Recent FAQs'));
		
		//crmv@3079m
		$additional_iframes = SDK::getDefaultIframes('complex');
		$start_cnt = 15;
		foreach ($additional_iframes as $type=>$iframearr){
			foreach ($iframearr as $iframe){
				$uniqueid[$iframe] = $adb->getUniqueID($table_prefix."_homestuff");
				$visibility = $this->getDefaultHomeModuleVisibility($iframe,$inVal);
				$size = 1;
				$res = $adb->pquery("select * from sdk_home_global_iframe where name = ?",array($iframe));
				if ($res && $adb->num_rows($res) > 0) {
					$size = $adb->query_result($res,0,'size');
				}
				$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values(?,?,?,?,?,?,?)";
				$res=$adb->pquery($sql, array($uniqueid[$iframe],$start_cnt,$type,$uid,$visibility,$size,$iframe));
				$start_cnt++;
			}
		}
		//crmv@3079me
		
		// Non-Default Home Page widget (no entry is requried in vte_homedefault below)
		$tc = $adb->getUniqueID($table_prefix."_homestuff");
		$visibility=1;
		$sql="insert into ".$table_prefix."_homestuff (". implode(",",$columns) .") values($tc, $start_cnt, 'Tag Cloud', $uid, $visibility, 1, 'Tag Cloud')";
		$adb->query($sql);
		$start_cnt++;
		//crmv@29079e

		$sql="insert into ".$table_prefix."_homedefault values(".$s1.",'ALVT',5,'Accounts')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s2.",'HDB',5,'Dashboard')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s3.",'PLVT',5,'Potentials')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s4.",'QLTQ',5,'Quotes')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s5.",'CVLVT',5,'NULL')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s6.",'HLT',5,'HelpDesk')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s7.",'UA',5,'Calendar')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s8.",'GRT',5,'NULL')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s9.",'OLTSO',5,'SalesOrder')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s10.",'ILTI',5,'Invoice')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s11.",'MNL',5,'Leads')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s12.",'OLTPO',5,'PurchaseOrder')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s13.",'PA',5,'Calendar')";
		$adb->query($sql);

		$sql="insert into ".$table_prefix."_homedefault values(".$s14.",'LTFAQ',5,'Faq')";
		$adb->query($sql);

		//crmv@3079m
		foreach ($additional_iframes as $type=>$iframearr){
			foreach ($iframearr as $iframe){
				if (isset($uniqueid[$iframe])){
					$params_insert = Array(
						'stuffid'=>$uniqueid[$iframe],
						'hometype'=>$iframe,
						'maxentries'=>0,
						'setype'=>'NULL',
					);
					$sql="insert into ".$table_prefix."_homedefault (".implode(",",array_keys($params_insert)).") values(".generateQuestionMarks($params_insert).")";
					$adb->pquery($sql,$params_insert);
				}
			}
		}
		//crmv@3079me		
	}

	/** function to save the order in which the modules have to be displayed in the home page for the specified user id
  	  * @param $id -- user id:: Type integer
 	 */
	 function saveHomeStuffOrder($id)
	 {
		 global $log,$adb,$table_prefix;
		 $log->debug("Entering in function saveHomeOrder($id)");
		 if ($_REQUEST['file'] == 'DetailViewAjax' || $_REQUEST['module'] == 'SDK') { // crmv@80750
		 	//do nothing
		 } elseif($this->mode == 'edit') {
			 for($i = 0;$i < count($this->homeorder_array);$i++)
			 {
				 if($_REQUEST[str_replace(" ","_",$this->homeorder_array[$i])] != '')
				 {
					 $save_array[] = $this->homeorder_array[$i];
					 $visible = 0;
				 }
				 else
				 {
			 		$visible = 1;
				 }
 				 if ($adb->isMssql()){
				 	$qry = "UPDATE ".$table_prefix."_homestuff
						SET ".$table_prefix."_homestuff.visible = ?
						from ".$table_prefix."_homestuff
						  INNER JOIN ".$table_prefix."_homedefault
						    ON ".$table_prefix."_homedefault.stuffid = ".$table_prefix."_homestuff.stuffid
						WHERE ".$table_prefix."_homestuff.userid = ?
						    AND ".$table_prefix."_homedefault.hometype = ?
				 	";
				 	$params = Array($visible,$id,$this->homeorder_array[$i]);
				 }
				 elseif ($adb->isOracle()){
				 	$qry = "UPDATE (
						select ".$table_prefix."_homestuff.visible from ".$table_prefix."_homestuff
						  INNER JOIN ".$table_prefix."_homedefault
						    ON ".$table_prefix."_homedefault.stuffid = ".$table_prefix."_homestuff.stuffid
						    WHERE ".$table_prefix."_homestuff.userid = ?
						    AND ".$table_prefix."_homedefault.hometype = ?) t
						SET t.visible = ?
				 	";
				 	$params = Array($id,$this->homeorder_array[$i],$visible);
				 }
				 else{ //mysql and others...
					 $qry = "UPDATE ".$table_prefix."_homestuff
	       					INNER JOIN ".$table_prefix."_homedefault ON ".$table_prefix."_homedefault.stuffid = ".$table_prefix."_homestuff.stuffid
	       					set visible=?
	       					where ".$table_prefix."_homestuff.userid = ? AND ".$table_prefix."_homedefault.hometype=?";
					 $params = Array($visible,$id,$this->homeorder_array[$i]);
				 }
				 $result=$adb->pquery($qry,$params);
			 }
			 if($save_array !="")
			 	$homeorder = implode(',',$save_array);
		 } else {
			$this->insertUserdetails('postinstall');
		 }
		 $log->debug("Exiting from function saveHomeOrder($id)");
 	}

	/**
	 * Track the viewing of a detail record.  This leverages get_summary_text() which is object specific
	 * params $user_id - The user that is viewing the record.
	 */
	function track_view($user_id, $current_module,$id='')
	{
		$this->log->debug("About to call vte_tracker (user_id, module_name, item_id)($user_id, $current_module, $this->id)");

		$tracker = new Tracker();
		$tracker->track_view($user_id, $current_module, $id, '');
	}

	/**
	* Function to get the column value of a field
	* @param $column_name -- Column name
	* @param $input_value -- Input value for the column taken from the User
	* @return Column value of the field.
	*/
	function get_column_value($columnname, $fldvalue, $fieldname, $uitype, $datatype='') { // crmv@146653
		if (is_uitype($uitype, "_date_") && $fldvalue == '') {
			return null;
		}
		if ($datatype == 'I' || $datatype == 'N' || $datatype == 'NN'){
			return 0;
		}
		return $fldvalue;
	}
	/**
	* Function to reset the Reminder Interval setup and update the time for next reminder interval
	* @param $prev_reminder_interval -- Last Reminder Interval on which the reminder popup's were triggered.
	*/
	function resetReminderInterval($prev_reminder_interval)
	{
		global $adb,$table_prefix;
		if($prev_reminder_interval != $this->column_fields['reminder_interval'] ){
			VteSession::remove('next_reminder_interval');
			VteSession::remove('next_reminder_time');
			$set_reminder_next = date('Y-m-d H:i');
			// NOTE date_entered has CURRENT_TIMESTAMP constraint, so we need to reset when updating the table
			$adb->pquery("UPDATE ".$table_prefix."_users SET reminder_next_time=?, date_entered=? WHERE id=?",array($set_reminder_next, $this->db->formatDate($this->column_fields['date_entered'], true), $this->id));
		}
	}
	function initSortByField($module) {
		// Right now, we do not have any fields to be handled for Sorting in Users module. This is just a place holder as it is called from Popup.php
	}

	function filterInactiveFields($module) {
		// TODO Nothing do right now
	}

	function deleteImage() {
		global $table_prefix;
		$sql1 = 'SELECT attachmentsid FROM '.$table_prefix.'_salesmanattachmentsrel WHERE smid = ?';
		$res1 = $this->db->pquery($sql1, array($this->id));
		if ($this->db->num_rows($res1) > 0) {
			$attachmentId = $this->db->query_result($res1, 0, 'attachmentsid');

			$sql2 = "DELETE FROM ".$table_prefix."_crmentity WHERE crmid=? AND setype='Users Attachments'";
			$this->db->pquery($sql2, array($attachmentId));

			$sql3 = 'DELETE FROM '.$table_prefix.'_salesmanattachmentsrel WHERE smid=? AND attachmentsid=?';
			$this->db->pquery($sql3, array($this->id, $attachmentId));

			$sql2 = "UPDATE ".$table_prefix."_users SET imagename='' WHERE id=?";
			$this->db->pquery($sql2, array($this->id));

			$sql4 = 'DELETE FROM '.$table_prefix.'_attachments WHERE attachmentsid=?';
			$this->db->pquery($sql4, array($attachmentId));
		}
	}
	/** Function to delete an entity with given Id */
	function trash($module, $id) {
		global $log, $current_user;

		$this->mark_deleted($id);

		//crmv@41883
  		require_once('include/utils/EmailDirectory.php');
		$emailDirectory = new EmailDirectory();
		//TODO reload EmailDirectory for old email, intanto cancello solo la riga per crmid
  		$emailDirectory->deleteById($id);
	  	//crmv@41883e
	}

	// crmv@177071 - removed broken function

	/**
	 * This function should be overridden in each module.  It marks an item as deleted.
	 * @param <type> $id
	 */
	function mark_deleted($id) {
		global $log, $current_user, $adb,$table_prefix;
		$date_var = date('Y-m-d H:i:s');
		$query = "UPDATE ".$table_prefix."_users set status=?,date_modified=?,modified_user_id=? where id=?";
		$adb->pquery($query, array('Inactive', $adb->formatDate($date_var, true),
			$current_user->id, $id), true,"Error marking record deleted: ");
	}
	
	// crmv@184231
	/**
	 * Comletely delete the specified user
	 *
	 * @param int $del_id The user id to delete
	 * @param int $tran_id The user id to transfer data to 
	 */
	public function deleteUser($del_id, $tran_id) {
		global $adb, $table_prefix;
		
		// crmv@177071
		$this->transferOwnership($del_id, $tran_id); 
		
		require_once("include/events/include.inc");
		$em = new VTEventsManager($adb);
		$em->initTriggerCache();
		$eventData = array(
			'userid' => $del_id,
			'transfer_to_user' => $tran_id
		);
		$em->triggerEvent("vte.user.beforedelete", $eventData);
		// crmv@177071e
		
		//deleting from vte_tracker
		$sql4 = "delete from ".$table_prefix."_tracker where user_id=?";
		$adb->pquery($sql4, array($del_id));

		//delete from vte_users to group vte_table
		$sql13 = "delete from ".$table_prefix."_user2role where userid=?";
		$adb->pquery($sql13, array($del_id));

		//delete from vte_users to vte_role vte_table
		$sql14 = "delete from ".$table_prefix."_users2group where userid=?";
		$adb->pquery($sql14, array($del_id));
		
		$sql = "delete from ".$table_prefix."_homestuff where userid=?";
		$adb->pquery($sql, array($del_id));

		//delete from user table;
		$sql15 = "delete from ".$table_prefix."_users where id=?";
		$adb->pquery($sql15, array($del_id));
		
		//crmv@42329
		if (isModuleInstalled('PBXManager')){
			$sql16 = "delete from ".$table_prefix."_asteriskextensions where userid=?";
			$adb->pquery($sql16, array($del_id));
		}
		//crmv@42329e

		// crmv@49398
		global $metaLogs;
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELUSER, $del_id);
		// crmv@49398e
	}
	// crmv@184231e
	
	// crmv@177071
	public function transferOwnership($olduser, $newuser) {
		global $adb, $table_prefix;
		
		//Updating the smcreatorid,smownerid, modifiedby in vte_crmentity
		$sql1 = "update ".$table_prefix."_crmentity set smcreatorid=? where smcreatorid=?";
		$adb->pquery($sql1, array($newuser, $olduser));
		$sql2 = "update ".$table_prefix."_crmentity set smownerid=? where smownerid=?";
		$adb->pquery($sql2, array($newuser, $olduser));
		$sql3 = "update ".$table_prefix."_crmentity set modifiedby=? where modifiedby=?";
		$adb->pquery($sql3, array($newuser, $olduser));
		
		//updating created by in vte_lar
		$sql5 = "update ".$table_prefix."_lar set createdby=? where createdby=?";
		$adb->pquery($sql5, array($newuser, $olduser));

		//updating the vte_import_maps
		$sql6 ="update ".$table_prefix."_import_maps set assigned_user_id=? where assigned_user_id=?";
		$adb->pquery($sql6, array($newuser, $olduser));

		//update assigned_user_id in vte_files
		$sql7 ="update ".$table_prefix."_files set assigned_user_id=? where assigned_user_id=?";
		$adb->pquery($sql7, array($newuser, $olduser));

		//update assigned_user_id in vte_users_last_import
		$sql8 = "update ".$table_prefix."_users_last_import set assigned_user_id=? where assigned_user_id=?";
		$adb->pquery($sql8, array($newuser, $olduser));

		//updating handler in vte_products
		$sql9 = "update ".$table_prefix."_products set handler=? where handler=?";
		$adb->pquery($sql9, array($newuser, $olduser));

		//updating inventorymanager in vte_quotes
		$sql10 = "update ".$table_prefix."_quotes set inventorymanager=? where inventorymanager=?";
		$adb->pquery($sql10, array($newuser, $olduser));

		//updating reports_to_id in vte_users
		$sql11 = "update ".$table_prefix."_users set reports_to_id=? where reports_to_id=?";
		$adb->pquery($sql11, array($newuser, $olduser));

		//updating user_id in vte_moduleowners
		$sql12 = "update ".$table_prefix."_moduleowners set user_id=? where user_id=?";
		$adb->pquery($sql12, array($newuser, $olduser));
		
		if (Vtecrm_Utils::CheckTable($table_prefix.'_customerportal_prefs')) {
			$query = 'UPDATE '.$table_prefix.'_customerportal_prefs SET prefvalue = ? WHERE prefkey = ? AND prefvalue = ?';
			$params = array($newuser, 'defaultassignee', $olduser);
			$adb->pquery($query, $params);

			$query = 'UPDATE '.$table_prefix.'_customerportal_prefs SET prefvalue = ? WHERE prefkey = ? AND prefvalue = ?';
			$params = array($newuser, 'userid', $olduser);
			$adb->pquery($query, $params);
		}
		
		// update uitype 10 fields or other user fields
		$sql = 
			"SELECT tablename,columnname 
			FROM {$table_prefix}_field f
			LEFT JOIN {$table_prefix}_fieldmodulerel fmr on f.fieldid = fmr.fieldid 
			WHERE f.uitype IN (50,51,52,53,77,101) OR (f.uitype=10 AND fmr.relmodule = ?)";
		$result = $adb->pquery($sql, array('Users'));
		$it = new SqlResultIterator($adb, $result);
		$columnList = array();
		foreach ($it as $row) {
			$column = $row->tablename.'.'.$row->columnname;
			if (!in_array($column, $columnList)) {
				$columnList[] = $column;
				$sql = "UPDATE {$row->tablename} SET {$row->columnname} = ? WHERE {$row->columnname} = ?";
				$adb->pquery($sql, array($newuser, $olduser));
			}
		}
		
		require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
		$WSU = new VTWorkflowUtils();
		$WSU->changeOwnerInTasks($olduser, $newuser);
		
		// TODO: update processes also...
	}
	// crmv@177071e

    // crmv@139057
    /**
	 * Function to get the user if of the active admin user.
	 * @return Integer - Active Admin User ID
	 */
    public static function getActiveAdminId(){
        global $adb,$table_prefix;

        $adminId = null;
        $result = $adb->limitpQuery("SELECT id FROM {$table_prefix}_users WHERE is_admin = ? AND status = ?", 0, 1, array('On', 'Active'));
        if ($result && $adb->num_rows($result) > 0) {
			$adminId = $adb->query_result_no_html($result, 0, 'id');
        }
        return $adminId;
    }
    // crmv@139057e

	/**
	 * Function to get the active admin user object
	 * @return Users - Active Admin User Instance
	 */
    public static function getActiveAdminUser(){
        $adminId = self::getActiveAdminId();
		$user = CRMEntity::getInstance('Users');
        $user->retrieveCurrentUserInfoFromFile($adminId);
        return $user;
    }

	// crmv@26485
	function saveSearchModules($modules) {
		global $adb,$table_prefix;

		if (!is_array($modules)) $modules = array($modules);
		// first delete saved modules for the user
		$res = $adb->pquery('delete from '.$table_prefix.'_users_search_tab where userid = ?', array($this->id));

		foreach ($modules as $modname) {
			$tabid = getTabid($modname);
			if ($tabid > 0 && vtlib_isModuleActive($modname)) {
				$res = $adb->pquery('insert into '.$table_prefix.'_users_search_tab (userid,tabid) values (?,?)', array($this->id, $tabid));
			}
		}
	}

	function getSearchModules() {
		global $adb,$table_prefix;

		$default_modules = array('Contacts', 'Accounts', 'Leads');

		$ret = array();
		$res = $adb->pquery('select '.$table_prefix.'_users_search_tab.tabid,'.$table_prefix.'_tab.name as modname from '.$table_prefix.'_users_search_tab inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid = '.$table_prefix.'_users_search_tab.tabid where '.$table_prefix.'_users_search_tab.userid = ? and '.$table_prefix.'_tab.presence = 0', array($this->id));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$tabid = intval($row['tabid']);
				if ($tabid > 0 && vtlib_isModuleActive($row['modname'])) {
					$ret[$tabid] = $row['modname'];
				}
			}
		} else { // return default search modules
			foreach ($default_modules as $modname) {
				if (vtlib_isModuleActive($modname)) {
					$tabid = getTabid($modname);
					if ($tabid) $ret[$tabid] = $modname;
				}
			}
		}
		return $ret;
	}
	// crmv@26485e

	// crmv@29377
	// genera il contenuto del cookie da inviare al client per il resta collegato
	function getCookieForSavelogin() {
		global $adb, $login_expire_time;
		$token_type = 'savelogin';
		if (VteSession::hasKey('savelogincookie') && !VteSession::isEmpty('savelogincookie')) return VteSession::get('savelogincookie');
		$r = getUserAuthtokenKey($token_type, $this->id, $login_expire_time, true);
		VteSession::set('savelogincookie', $r);
		return $r;
	}

	// controlla se il contenuto del cookie resta collegato e' valido.
	static function checkCookieSaveLogin($cookieval) {
		$token_type = 'savelogin';
		$r = validateUserAuthtokenKey($token_type, $cookieval);
		return $r;
	}

	function deleteCookieSaveLogin() {
		$token_type = 'savelogin';
		emptyUserAuthtokenKey($token_type, $this->id);
	}
	// crmv@29377e

	function m_encryption($val) {
		if (isset($_REQUEST['current_version']) && $_REQUEST['current_version'] != '' && intval($_REQUEST['current_version']) <= 631) {
			global $adb, $table_prefix;
			$result = $adb->query("SELECT * FROM {$table_prefix}_version");
			$return = $adb->query_result_no_html($result, 0, 'hash_version');
		} else {
			$return = str_rot13($val);
		}
		return $return;
	}
	static function m_de_cryption($val='') {
		$cache = Cache::getInstance('vteCacheHV');
		$val = $cache->get();
	    if ($val === false) {
	    	global $adb, $table_prefix;
	    	$result = $adb->query("SELECT * FROM {$table_prefix}_version");
			$hash_version = $adb->query_result_no_html($result, 0, 'hash_version');
			$val = str_rot13(Users::de_cryption($hash_version));
			$cache->set($val);
	    }
	    if (isset($_REQUEST['current_version']) && $_REQUEST['current_version'] != '' && intval($_REQUEST['current_version']) <= 631 && $_REQUEST['action'] != 'ProcessMaker') { //crmv@167915
			$return = self::de_cryption($val);
		} else {
			$return = str_rot13($val);
		}
		return $return;
	}
	// crmv@181170
	static function m_de_cryption_get($hashIdx = null) {
		$hash_version = array();
		if ($hashIdx !== null) {
			eval(Users::m_de_cryption());
			if ($hash_version && isset($hash_version[$hashIdx])) {
				return eval($hash_version[$hashIdx]);
			}
		}
		return '';
	}
	// crmv@181170e
	
	//crmv@104988 crmv@115336 crmv@126096 crmv@152712 crmv@193035
	function formatUserName($userid, $info, $with_name=false, $username_format=null) {
		$secondPart = '';
		if (empty($username_format)) $username_format = $this->username_format;
		if ($username_format == self::USERNAME_FORMAT_STANDARD) {
			$firstPart = $info['user_name'];
			$secondPart = trim($info['first_name'].' '.$info['last_name']);
		} elseif ($username_format == self::USERNAME_FORMAT_INVERTED) {
			$firstPart = trim($info['first_name'].' '.$info['last_name']);
			$secondPart = $info['user_name'];
		}
		if ($with_name && $secondPart) {
			return "$firstPart ($secondPart)";
		}
		return $firstPart;
	}
	
	function formatUserNameSql($adb, $table, $with_name=false, $username_format=null) {
		if ($with_name) {
			if (empty($username_format)) $username_format = $this->username_format;
			$trim_fullname = 'trim('.$adb->sql_concat(array($table.".first_name","' '",$table.".last_name")).')';
			if ($username_format == self::USERNAME_FORMAT_STANDARD) {
				return $adb->sql_concat(Array($table.".user_name","' ('",$trim_fullname,"')'"));
			} elseif ($username_format == self::USERNAME_FORMAT_INVERTED) {
				return $adb->sql_concat(Array($trim_fullname,"' ('",$table.".user_name","')'"));
			}
		}
		return $table.".user_name";
	}
	//crmv@104988e crmv@115336e crmv@126096e crmv@152712e crmv@193035e
	
	// crmv@161368
	/**
	 * Logout the user from all the external devices (only app supported at the moment)
	 */
	public function remoteWipe($userid) {
		require_once 'modules/Touch/Touch.php';
		$touch = Touch::getInstance();
		$touch->remoteWipe($userid);
	}
	// crmv@161368e
	
	//crmv@183872
	function createCustomFiltersForAll() {
		global $adb, $table_prefix;
		$result = $adb->query("select id from {$table_prefix}_users");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$this->createCustomFilters($row['id']);
			}
		}
	}
	function createCustomFilters($userid='') {
		global $adb, $table_prefix;
		if (empty($userid)) $userid = $this->id;
		
		// add filter Pending in Processes
		$moduleInstance = Vtecrm_Module::getInstance('Processes');
		if ($moduleInstance) {
			$viewname = 'Pending';
			
			$result = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE entitytype = ? AND viewname = ? AND userid = ?", array($moduleInstance->name,$viewname,$userid));
			if ($result && $adb->num_rows($result) == 0) {
				
				$filterInstance = new Vtecrm_Filter();
				$filterInstance->name = $viewname;
				$filterInstance->status = true;
				$filterInstance->isdefault = false;
				$filterInstance->inmobile = true; // crmv@188277
				$filterInstance->save($moduleInstance);
				$adb->pquery("UPDATE {$table_prefix}_customview SET userid=? WHERE cvid=?", Array($userid,$filterInstance->id));
				
				$filterInstance
				->addField(Vtecrm_Field::getInstance('process_name',$moduleInstance),1)
				->addField(Vtecrm_Field::getInstance('related_to',$moduleInstance),2)
				->addField(Vtecrm_Field::getInstance('description',$moduleInstance),3)
				->addField(Vtecrm_Field::getInstance('process_status',$moduleInstance),4)
				->addField(Vtecrm_Field::getInstance('assigned_user_id',$moduleInstance),5)
				//	->addField(Vtecrm_Field::getInstance('expiration',$moduleInstance),6)
				;
				
				$filterInstance->addRule(Vtecrm_Field::getInstance('process_status',$moduleInstance), 'NOT_EQUALS', 'Ended');
				$filterInstance->addRule(Vtecrm_Field::getInstance('assigned_user_id',$moduleInstance), 'EQUALS', $userid);
				
				//$fieldInstance = Vtecrm_Field::getInstance('expiration',$moduleInstance);
				//$adb->pquery("insert into tbl_s_cvorderby values(?,?,?,?)", array($filterInstance->id,1,$filterInstance->__getColumnValue($fieldInstance),'DESC'));
			}
		}
	}
	//crmv@183872e
	
	// crmv@187823
	public function initCalendarSharing($recalcTables = true) {
		global $adb, $table_prefix;
		
		$focusCalendar = CRMEntity::getInstance('Calendar');
		$userDetails = $focusCalendar->getShownUserList($this->id);
		
		if (isset($userDetails) && $userDetails != null) {
			foreach ($userDetails as $id => $name) {
				if ($id != '') {
					$sql = "insert into tbl_s_showncalendar (userid,shownid,selected) values (?,?,1)";
					$adb->pquery($sql, array($this->id,$id));
				}
			}
		}
		$adb->pquery("insert into tbl_s_showncalendar (userid,shownid,selected) values (?,?,1)", array($this->id,'mine'));
		$adb->pquery("insert into tbl_s_showncalendar (userid,shownid,selected) values (?,?,0)", array($this->id,'all'));
		$adb->pquery("insert into tbl_s_showncalendar (userid,shownid,selected) values (?,?,0)", array($this->id,'others'));
		
		if ($recalcTables) {
			// crmv@63349 - calculate the share table for the user
			$tcaltables = TmpUserCalTables::getInstance();
			$tcaltables->cleanTmpForModuleUser('Calendar', $this->id);
			$tcaltables->generateTmpForModuleUser('Calendar', $this->id);
		}
	}
	
	public function updateCalendarSharing(array $shareduser_ids, array $shareduserocc_ids, array $shownduser_ids, $recalcTables = true) {
		global $adb, $table_prefix;
		
		//crmv@119991
		//Verifica gli shared precedenti
		$query = "SELECT sharedid FROM {$table_prefix}_sharedcalendar where userid=?";
		$ress = $adb->pquery($query, array($this->id));
		$old_shared = array();
		while($row_sha = $adb->fetchByAssoc($ress,-1,false)){
			$old_shared[] = $row_sha['sharedid'];
		}
		//crmv@119991e

		// clean sharing
		$delquery = "delete from ".$table_prefix."_sharedcalendar where userid=?";
		$adb->pquery($delquery, array($this->id));
		
		// add shared occupation (priority over std occupation, to avoid leak of private info)
		if (count($shareduserocc_ids) > 0) {
			// remove already added users
			foreach ($shareduserocc_ids as $sid) {
				if ($sid != '') {
					$sql = "insert into ".$table_prefix."_sharedcalendar (userid, sharedid, only_occ) values (?,?, 1)";
					$adb->pquery($sql, array($this->id,$sid));
				}
			}
		}
		
		// add shared 
		if (count($shareduser_ids) > 0) {
			$shareduser_ids = array_values(array_diff($shareduser_ids, $shareduserocc_ids));
			foreach ($shareduser_ids as $sid) {
				if($sid != '') {
					$sql = "insert into ".$table_prefix."_sharedcalendar (userid, sharedid, only_occ) values (?,?, 0)";
					$adb->pquery($sql, array($this->id,$sid));
				}
			}
		}

		// remove from other users
		//crmv@119991
		$users_to_hide = array_diff($old_shared, $shareduser_ids, $shareduserocc_ids);
		if (count($users_to_hide) > 0){
			foreach ($users_to_hide as $uid) {
				$adb->pquery("DELETE FROM tbl_s_showncalendar WHERE userid = ? AND shownid = ? ", array($uid, $this->id));
			}
		}
		//crmv@119991e

		// recalc sharing rules
		if ($recalcTables) {
			if (count($shareduser_ids) > 0) RecalculateSharingRules($shareduser_ids);
			if (count($shareduserocc_ids) > 0) RecalculateSharingRules($shareduserocc_ids);
		}

		// get old shown
		$tmp_res = $adb->pquery("select shownid,selected from tbl_s_showncalendar where shownid not in ('all','mine','others') and userid=?", array($this->id));
		$tmp = array();
		while($row=$adb->fetchByAssoc($tmp_res)) {
			$tmp['shownid'] = $tmp['selected'];
		}
		
		// clean shown
		$delquery = "delete from tbl_s_showncalendar where shownid not in ('all','mine','others') and userid=?";
		$adb->pquery($delquery, array($this->id));
		
		// add shown
		if (isset($shownduser_ids) && $shownduser_ids != null) {
			foreach($shownduser_ids as $sid) {
				if($sid != '') {
					if (isset($tmp[$sid]))
						$selected = $tmp[$sid];
					else
						$selected = 1;
					$sql = "insert into tbl_s_showncalendar values (?,?,?)";
					$adb->pquery($sql, array($this->id,$sid,$selected));
				}
			}
		}

		if ($recalcTables) {
			// crmv@63349 - calculate the share table for the user
			$tcaltables = TmpUserCalTables::getInstance();
			$tcaltables->cleanTmpForModuleUser('Calendar', $this->id);
			$tcaltables->generateTmpForModuleUser('Calendar', $this->id);
			// crmv@63349e
		}
	}
	// crmv@187823e
	
}
