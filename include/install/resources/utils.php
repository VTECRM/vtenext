<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * Provides few utility functions for installation process
 * @package install
 */

class Installation_Utils {
	
	//crmv@28327
	var $password_length_min = 8;		//if equal to 0 the check is disable
	//crmv@28327e
	
	static function getInstallableOptionalModules() {
		$optionalModules = Common_Install_Wizard_Utils::getInstallableModulesFromPackages();
		return $optionalModules;
	}
	
	// crmv@151405
	static function getInstallableBetaModules() {
		$optionalModules = Common_Install_Wizard_Utils::getInstallableBetaModulesFromPackages();
		return $optionalModules;
	}
	// crmv@151405e

	// Function to install Vtlib Compliant - Optional Modules
	static function installOptionalModules($selected_modules){
		Common_Install_Wizard_Utils::installSelectedOptionalModules($selected_modules);
	}
	
	// crmv@151405
	static function installBetaModules($selected_modules){
		Common_Install_Wizard_Utils::installSelectedBetaModules($selected_modules);
	}
	// crmv@151405e

	static function getDbOptions() {
		$dbOptions = array();
		// crmv@56443 - if PHP >= 5.5, use mysqli, since mysql is deprecated
		if (function_exists('mysqli_connect') && version_compare(PHP_VERSION, '5.5') >= 0) {
			$dbOptions['mysqli'] = 'MySQL';
		} elseif (function_exists('mysql_connect')) {
			$dbOptions['mysql'] = 'MySQL';
		}
		// crmv@56443e

		//crmv@add oracle/mssql support
		if(function_exists('OCIPLogon')) {
			$dbOptions['oci8po'] = 'Oracle';
		}
		// crmv@155585
		if(function_exists('mssql_pconnect')) {
			$dbOptions['mssql'] = 'Sql server';
		} elseif (function_exists('sqlsrv_connect')) {
			$dbOptions['mssqlnative'] = 'Sql server';
		}
		// crmv@155585e
		//crmv@add oracle/mssql support end
		return $dbOptions;
	}
	
	static function checkDbConnection($db_type, $db_hostname, $db_hostport, $db_username, $db_password, $db_name, $create_db=false, $create_utf8_db=true, $root_user='', $root_password='') {
		global $installationStrings, $vte_legacy_version;
		
		$dbCheckResult = array();
        //crmv@208173
		
		$db_type_status = false; // is there a db type?
		$db_server_status = false; // does the db server connection exist?
		$db_creation_failed = false; // did we try to create a database and fail?
		$db_exist_status = false; // does the database exist?
		$db_utf8_support = false; // does the database support utf8?
		$vt_charset = ''; // set it based on the database charset support
		
		//Checking for database connection parameters
		if($db_type) {
			$conn = &NewADOConnection($db_type);
			$db_type_status = true;
			//crmv@constructy hostname
			$db_hostname = Common_Install_Wizard_Utils::constructHostname($db_type,$db_hostname,$db_hostport);
			//crmv@constructy hostname end
			//crmv@fix-oracle
			if ($db_type == 'oci8po') {
				$result_conn = @$conn->Connect($db_hostname,$db_username,$db_password,$db_name);
			} else {
				$result_conn = @$conn->Connect($db_hostname,$db_username,$db_password);
			}
						
			if($result_conn) {
			//crmv@fix-oracle e
				$db_server_status = true;
				$serverInfo = $conn->ServerInfo();
				//crmv@fix version
				$sql_server_version = Common_Install_Wizard_Utils::getSQLVersion($serverInfo);
				$mysql_server_version = $sql_server_version;
				//crmv@fix version end
				if($create_db) {
					// drop the current database if it exists
					$dropdb_conn = &NewADOConnection($db_type);
					if(@$dropdb_conn->Connect($db_hostname, $root_user, $root_password, $db_name)) {
						$query = "drop database ".$db_name;
						if (@$dropdb_conn->Execute($query))
						$dropdb_conn->Close();
					}

					// create the new database
					$db_creation_failed = true;
					$createdb_conn = &NewADOConnection($db_type);
					if($createdb_conn->Connect($db_hostname, $root_user, $root_password)) {
						//crmv@fix utf8
						if($create_utf8_db == 'true') { 
							if(Common_Install_Wizard_Utils::isMySQL($db_type))
								$options['MYSQL'] = " default character set utf8 default collate utf8_general_ci"; 
							$db_utf8_support = true;
						}	
						//crmv@fix utf8 end
						//crmv@fix create database					
						$datadict = NewDataDictionary($createdb_conn,$createdb_conn->dataProvider);
						$sql = @$datadict->CreateDatabase($db_name,$options);
						if ($sql){
							if (@$datadict->ExecuteSQLArray($sql) == 2)
								$db_creation_failed = false;
						}	
						//crmv@fix create database end
						$createdb_conn->Close();
					}
				}
				// test the connection to the database
				if($conn->Connect($db_hostname, $db_username, $db_password, $db_name))
				{
					$db_exist_status = true;
					if(!$db_utf8_support) {
						// Check if the database that we are going to use supports UTF-8
						$db_utf8_support = check_db_utf8_support($conn);
					}
				}
				$conn->Close();
			}
		}
		$dbCheckResult['db_utf8_support'] = $db_utf8_support;
		
		$error_msg = '';
		$error_msg_info = '';
		
		if(!$db_type_status || !$db_server_status) {
			$error_msg = $installationStrings['ERR_DATABASE_CONNECTION_FAILED'].'. '.$installationStrings['ERR_INVALID_MYSQL_PARAMETERS'];
			$error_msg_info = $installationStrings['MSG_LIST_REASONS'].':<br>
					-  '.$installationStrings['MSG_DB_PARAMETERS_INVALID'].'<BR>
					-  '.$installationStrings['MSG_DB_USER_NOT_AUTHORIZED'];
		}
		elseif(Common_Install_Wizard_Utils::isMySQL($db_type) && (float)$mysql_server_version < (float)'4.1') {
			$error_msg = $mysql_server_version.' -> '.$installationStrings['ERR_INVALID_MYSQL_VERSION'];
		}
		elseif($db_creation_failed) {
			$error_msg = $installationStrings['ERR_UNABLE_CREATE_DATABASE'].' '.$db_name;
			$error_msg_info = $installationStrings['MSG_DB_ROOT_USER_NOT_AUTHORIZED'];
		}
		elseif(!$db_exist_status) {
			$error_msg = $db_name.' -> '.$installationStrings['ERR_DB_NOT_FOUND'];
		}
		else {
			$dbCheckResult['flag'] = true;
			return $dbCheckResult;
		}
		$dbCheckResult['flag'] = false;
		$dbCheckResult['error_msg'] = $error_msg;
		$dbCheckResult['error_msg_info'] = $error_msg_info;
		return $dbCheckResult;
	}

    //crmv@208173
    //Added to check database charset and $default_charset are set to UTF8.
    //If both are not set to be UTF-8, Then we will show an alert message.
    function check_db_utf8_support($conn) {
        global $db_type;
        //crmv@charset
        if($db_type !== 'mysql')
            return true;
        //crmv@charset end
        $dbvarRS = &$conn->Execute("show variables like '%_database' ");
        $db_character_set = null;
        $db_collation_type = null;
        while(!$dbvarRS->EOF) {
            $arr = $dbvarRS->FetchRow();
            $arr = array_change_key_case($arr);
            switch($arr['variable_name']) {
                case 'character_set_database' : $db_character_set = $arr['value']; break;
                case 'collation_database'     : $db_collation_type = $arr['value']; break;
            }
            // If we have all the required information break the loop.
            if($db_character_set !== null && $db_collation_type !== null) break;
        }
        return (stristr($db_character_set, 'utf8') && stristr($db_collation_type, 'utf8'));
    }
    //crmv@208173e
	
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
	//crmv@28327e
}

class ConfigFile_Utils {
	
	private $rootDirectory;
	private $dbHostname;
	private $dbPort;
	private $dbUsername;
	private $dbPassword;
	private $dbName;
	private $dbType;
	private $siteUrl;
	private $cacheDir;
	private $vtCharset;
	private $currencyName;
	private $adminEmail;
	
	function __construct($configFileParameters) {
		if (isset($configFileParameters['root_directory']))
			$this->rootDirectory = $configFileParameters['root_directory'];
			
		if (isset($configFileParameters['db_hostname'])) {
			//crmv@fix connection string
			$this->dbHostname = $configFileParameters['db_hostname'];
			$this->dbPort = $configFileParameters['db_hostport'];
			//crmv@fix connection string end
		}
		if (isset($configFileParameters['db_username'])) $this->dbUsername = $configFileParameters['db_username'];
		if (isset($configFileParameters['db_password'])) $this->dbPassword = $configFileParameters['db_password'];
		if (isset($configFileParameters['db_name'])) $this->dbName = $configFileParameters['db_name'];
		if (isset($configFileParameters['db_type'])) $this->dbType = $configFileParameters['db_type'];
		if (isset($configFileParameters['site_URL'])) $this->siteUrl = $configFileParameters['site_URL']; 
		if (isset($configFileParameters['admin_email'])) $this->adminEmail = $configFileParameters['admin_email'];
		if (isset($configFileParameters['currency_name'])) $this->currencyName = $configFileParameters['currency_name'];
		if (isset($configFileParameters['vt_charset'])) $this->vtCharset = $configFileParameters['vt_charset'];
		//crmv@fix connection string
		// update default port with the right separator
		if ($this->dbPort)
			$this->dbPort = ConfigFile_Utils::getDbDefaultPortSeparator($this->dbType).$this->dbPort;
		else
			$this->dbPort = ConfigFile_Utils::getDbDefaultPortSeparator($this->dbType).ConfigFile_Utils::getDbDefaultPort($this->dbType);
		//crmv@fix connection string end
		$this->cacheDir = 'cache/';
	}
	//crmv@add mssql support
	static function getDbDefaultPort($dbType) {
		if(Common_Install_Wizard_Utils::isMySQL($dbType)) {
			return "3306";
		}
		if(Common_Install_Wizard_Utils::isOracle($dbType)) {
			return '1521';
		}
		if(Common_Install_Wizard_Utils::isMssql($dbType)) {
			return '1433';
		}
	}
	//crmv@add mssql support end
	//crmv@fix connection string
	static function getDbDefaultPortSeparator($dbType) {
		if(Common_Install_Wizard_Utils::isMySQL($dbType)) {
			return ":";
		}
		if(Common_Install_Wizard_Utils::isOracle($dbType)) {
			return ":";
		}
		if(Common_Install_Wizard_Utils::isMssql($dbType)) {
			//crmv@57238
			if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				return ",";
			}else{
				return ":";
			}
			//crmv@57238e
		}
	}
	//crmv@fix connection string end
	function createConfigFile() {

		if (is_file('config.inc.php'))
		    $is_writable = is_writable('config.inc.php');
		else
			$is_writable = is_writable('.');
	
		/* open template configuration file read only */
		$templateFilename = 'config.template.php';
		$templateHandle = fopen($templateFilename, "r");
		if($templateHandle) {
			/* open include configuration file write only */
			$includeFilename = 'config.inc.php';
	      	$includeHandle = fopen($includeFilename, "w");
			if($includeHandle) {
			   	while (!feof($templateHandle)) {
	  				$buffer = fgets($templateHandle);
	
		 			/* replace _DBC_ variable */
		  			$buffer = str_replace( "_DBC_SERVER_", $this->dbHostname, $buffer);
		  			$buffer = str_replace( "_DBC_PORT_", $this->dbPort, $buffer);
		  			$buffer = str_replace( "_DBC_USER_", $this->dbUsername, $buffer);
		  			$buffer = str_replace( "_DBC_PASS_", $this->dbPassword, $buffer);
		  			$buffer = str_replace( "_DBC_NAME_", $this->dbName, $buffer);
		  			$buffer = str_replace( "_DBC_TYPE_", $this->dbType, $buffer);
		
		  			$buffer = str_replace( "_SITE_URL_", $this->siteUrl, $buffer);
		
		  			/* replace dir variable */
		  			$buffer = str_replace( "_VT_ROOTDIR_", $this->rootDirectory, $buffer);
		  			$buffer = str_replace( "_VT_CACHEDIR_", $this->cacheDir, $buffer);
		  			$buffer = str_replace( "_VT_TMPDIR_", $this->cacheDir."images/", $buffer);
		  			$buffer = str_replace( "_VT_UPLOADDIR_", $this->cacheDir."upload/", $buffer);
			      	$buffer = str_replace( "_DB_STAT_", "true", $buffer);
			      	//crmv@add db options
			      	$buffer = str_replace( "_DB_CHARSET_", "utf8", $buffer);
			      	$buffer = str_replace( "_DB_DIEONERROR_", false, $buffer);
					//crmv@add db options end
					/* replace charset variable */
					$buffer = str_replace( "_VT_CHARSET_", $this->vtCharset, $buffer);
		
			      	/* replace master currency variable */
		  			$buffer = str_replace( "_MASTER_CURRENCY_", $this->currencyName, $buffer);
		
			      	/* replace the application unique key variable */
		      		// crmv@167234
					$string = time().rand(1,9999999).md5($this->rootDirectory);
		      		$buffer = str_replace( "_VT_APP_UNIQKEY_", md5($string) , $buffer);
					// crmv@167234e
					
					$buffer = str_replace( "_CSRF_SECRET_", $this->csrf_generate_secret(), $buffer); // crmv@171581
					
					/* replace support email variable */
					$buffer = str_replace( "_USER_SUPPORT_EMAIL_", $this->adminEmail, $buffer);
					
					// crmv@195213
					$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
					$cf_prefix = '';
					for ($i = 0; $i < 3; $i++) {
						$index = rand(0, strlen($characters) - 1);
						$cf_prefix .= $characters[$index];
					}
					$buffer = str_replace( "_CF_PREFIX_", $cf_prefix, $buffer);
					// crmv@195213e
		
		      		fwrite($includeHandle, $buffer);
	      		}	
	  			fclose($includeHandle);
	  		}	
	  		fclose($templateHandle);
	  	}
	  	
	  	if ($templateHandle && $includeHandle) { 
	  		return true;
	  	} 
	  	return false;
	}
	
	// crmv@171581
	public function csrf_generate_secret($len = 32) {
		$r = '';
		for ($i = 0; $i < $len; $i++) {
			$r .= chr(mt_rand(0, 255));
		}
		$r .= time() . microtime();
		return sha1($r);
	}
	// crmv@171581e
	
	// crmv@178158 - removed unused function
}

class Common_Install_Wizard_Utils {
	
	public static $login_expire_time = 2592000; // crmv@27520 (one month)
	
	public static $recommendedDirectives = array (
		'safe_mode' => 'Off',
		'display_errors' => 'On',
		'file_uploads' => 'On',
		'register_globals' => 'On',
		'output_buffering' => 'On',
		'max_execution_time' => '600',
		'memory_limit' => '128',
		'error_reporting' => 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED', // crmv@146653
		'log_errors' => 'On',	//crmv@146653
		'mod_rewrite' => 'On',	//crmv@24713m
		'APCu' => 'On', // crmv@181165
		'BCMath' => 'On', // crmv@171524
		//'session.gc_maxlifetime' => 2592000,  // crmv@27520 (one month)
	);
	
	// crmv@127567 crmv@140903
	public static $writableFilesAndFolders = array (
		'Configuration File' => './config.inc.php',
		'Installation File' => './install.php',
		'Cache Directory' => './cache/',
		'Image Cache Directory' => './cache/images/',
		'Import Cache Directory' => './cache/import/',
		'Vtlib Cache Directory' => './cache/vtlib/',
		'Vtlib Cache HTML Directory' => './cache/vtlib/HTML',
		'Storage Directory' => './storage/',
		'Install Directory' => './install/',
		'User Privileges Directory' => './user_privileges/',
		'Smarty Cache Directory' => './Smarty/cache/',
		'Smarty Compile Directory' => './Smarty/templates_c/',
		'Email Templates Directory' => './modules/Emails/templates/',
		'Modules Directory' => './modules/',
		'Cron Modules Directory' => './cron/modules/',
		'Backup Directory' => './backup/',
		'Smarty Modules Directory' => './Smarty/templates/modules/',
		'Logo Directory' => './storage/logo/',
		'Logs Directory' => './logs/',
		'SmartOptimizer Cache Directory' => './smartoptimizer/cache/',	//crmv@24713m
	);
	// crmv@127567e crmv@140903e
	
	public static $gdInfoAlternate = 'function gd_info() {
		$array = Array(
	               "GD Version" => "",
	               "FreeType Support" => 0,
	               "FreeType Support" => 0,
	               "FreeType Linkage" => "",
	               "T1Lib Support" => 0,
	               "GIF Read Support" => 0,
	               "GIF Create Support" => 0,
	               "JPG Support" => 0,
	               "PNG Support" => 0,
	               "WBMP Support" => 0,
	               "XBM Support" => 0
	             );
		       $gif_support = 0;
		
		       ob_start();
		       eval("phpinfo();");
		       $info = ob_get_contents();
		       ob_end_clean();
		
		       foreach(explode("\n", $info) as $line) {
		           if(strpos($line, "GD Version")!==false)
		               $array["GD Version"] = trim(str_replace("GD Version", "", strip_tags($line)));
		           if(strpos($line, "FreeType Support")!==false)
		               $array["FreeType Support"] = trim(str_replace("FreeType Support", "", strip_tags($line)));
		           if(strpos($line, "FreeType Linkage")!==false)
		               $array["FreeType Linkage"] = trim(str_replace("FreeType Linkage", "", strip_tags($line)));
		           if(strpos($line, "T1Lib Support")!==false)
		               $array["T1Lib Support"] = trim(str_replace("T1Lib Support", "", strip_tags($line)));
		           if(strpos($line, "GIF Read Support")!==false)
		               $array["GIF Read Support"] = trim(str_replace("GIF Read Support", "", strip_tags($line)));
		           if(strpos($line, "GIF Create Support")!==false)
		               $array["GIF Create Support"] = trim(str_replace("GIF Create Support", "", strip_tags($line)));
		           if(strpos($line, "GIF Support")!==false)
		               $gif_support = trim(str_replace("GIF Support", "", strip_tags($line)));
		           if(strpos($line, "JPG Support")!==false)
		               $array["JPG Support"] = trim(str_replace("JPG Support", "", strip_tags($line)));
		           if(strpos($line, "PNG Support")!==false)
		               $array["PNG Support"] = trim(str_replace("PNG Support", "", strip_tags($line)));
		           if(strpos($line, "WBMP Support")!==false)
		               $array["WBMP Support"] = trim(str_replace("WBMP Support", "", strip_tags($line)));
		           if(strpos($line, "XBM Support")!==false)
		               $array["XBM Support"] = trim(str_replace("XBM Support", "", strip_tags($line)));
		       }
		
		       if($gif_support==="enabled") {
		           $array["GIF Read Support"]  = 1;
		           $array["GIF Create Support"] = 1;
		       }
		
		       if($array["FreeType Support"]==="enabled"){
		           $array["FreeType Support"] = 1;    }
		
		       if($array["T1Lib Support"]==="enabled")
		           $array["T1Lib Support"] = 1;
		
		       if($array["GIF Read Support"]==="enabled"){
		           $array["GIF Read Support"] = 1;    }
		
		       if($array["GIF Create Support"]==="enabled")
		           $array["GIF Create Support"] = 1;
		
		       if($array["JPG Support"]==="enabled")
		           $array["JPG Support"] = 1;
		
		       if($array["PNG Support"]==="enabled")
		           $array["PNG Support"] = 1;
		
		       if($array["WBMP Support"]==="enabled")
		           $array["WBMP Support"] = 1;
		
		       if($array["XBM Support"]==="enabled")
		           $array["XBM Support"] = 1;
		
		       return $array;
		
		}';
		
	function getRecommendedDirectives() {
		return self::$recommendedDirectives;
	}		
	
	/** Function to check the file access is made within web root directory. */
	static function checkFileAccess($filepath) {
		global $root_directory, $installationStrings;
		// Set the base directory to compare with
		$use_root_directory = $root_directory;
		if(empty($use_root_directory)) {
			$use_root_directory = realpath(dirname(__FILE__).'/../../..');
		}
	
		$realfilepath = realpath($filepath);
	
		/** Replace all \\ with \ first */
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath  = str_replace('\\\\', '\\', $use_root_directory);
	
		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath  = str_replace('\\', '/', $rootdirpath);
		
		if(stripos($realfilepath, $rootdirpath) !== 0) {
			die($installationStrings['ERR_RESTRICTED_FILE_ACCESS']);
		}
	}
	
	static function getFailedPermissionsFiles() {
		$writableFilesAndFolders = Common_Install_Wizard_Utils::$writableFilesAndFolders;
		$failedPermissions = array();
		require_once ('include/utils/VtlibUtils.php');
		foreach ($writableFilesAndFolders as $index => $value) {
			if (!vtlib_isWriteable($value)) {
				$failedPermissions[$index] = $value;
			}
		}
		return $failedPermissions;
	}
	
	static function getCurrentDirectiveValue() {
		$directiveValues = array();
		if (ini_get('safe_mode') == '1' || stripos(ini_get('safe_mode'), 'On') > -1)
			$directiveValues['safe_mode'] = 'On';
		if (ini_get('display_errors') != '1' || stripos(ini_get('display_errors'), 'Off') > -1)
			$directiveValues['display_errors'] = 'Off';
		if (ini_get('file_uploads') != '1' || stripos(ini_get('file_uploads'), 'Off') > -1)
			$directiveValues['file_uploads'] = 'Off';
		if (ini_get('register_globals') == '1' || stripos(ini_get('register_globals'), 'On') > -1)
			$directiveValues['register_globals'] = 'On';
		if (ini_get(('output_buffering') < '4096' && ini_get('output_buffering') != '0') || stripos(ini_get('output_buffering'), 'Off') > -1)
			$directiveValues['output_buffering'] = 'Off';
		if (ini_get('max_execution_time') < 600)
			$directiveValues['max_execution_time'] = ini_get('max_execution_time');
		if (ini_get('memory_limit') < 128)
			$directiveValues['memory_limit'] = ini_get('memory_limit');
		$errorReportingValue = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT; // crmv@146653
		if (ini_get('error_reporting') != $errorReportingValue)
			$directiveValues['error_reporting'] = 'NOT RECOMMENDED';
		
		// crmv@146653
		if (ini_get('log_errors') == '' || ini_get('log_errors') == '0' || stripos(ini_get('log_errors'), 'Off') > -1)
			$directiveValues['log_errors'] = 'Off';
		// crmv@146653e
		//crmv@27520
//		if (ini_get('session.gc_maxlifetime') < self::$login_expire_time)
//			$directiveValues['session.gc_maxlifetime'] = ini_get('session.gc_maxlifetime');
		//crmv@27520e
		//crmv@24713m crmv@187692
		$mod_rewrite = false;
		$modules = array();
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
		} else {
			$out = array();
			$ret = 0;
			exec('apachectl -M', $out, $ret);
			if ($ret == 0) {
				foreach ($out as $line) {
					if ($line[0] == ' ') {
						$modname = trim(preg_replace('/\(.*\)/', '', $line));
						$modname = 'mod_'.str_replace('_module', '', $modname);
						$modules[] = $modname;
					}
				}
			}
		}
		if (is_array($modules) && count($modules) > 0) {
			$mod_rewrite = in_array('mod_rewrite', $modules);
		} else {
			$mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
		}
		if (!$mod_rewrite) {
			$directiveValues['mod_rewrite'] = 'Off';
		}
		//crmv@24713me crmv@187692e
		// crmv@181165
		if (!function_exists('apcu_add')) {
			$directiveValues['APCu'] = 'Off';
		}
		// crmv@181165e
		// crmv@171524
		if (!function_exists('bcadd')) {
			$directiveValues['BCMath'] = 'Off';
		}
		// crmv@171524e
		return $directiveValues;
	}
	// Fix for ticket 6605 : detect mysql extension during installation
	static function check_mysql_extension() {
		//crmv@98338
		if (function_exists('mysqli_connect') && version_compare(PHP_VERSION, '5.5') >= 0) {
			$mysql_extension = true;
		}
		elseif(function_exists('mysql_connect')) {
		//crmv@98338e
			$mysql_extension = true;
		}
		else {
			$mysql_extension = false;
		}
		return $mysql_extension;
	}
	
	static function isMySQL($dbType) { 
		return (stripos($dbType ,'mysql') === 0);
	}
	
    static function isOracle($dbType) { 
    	return (stripos($dbType ,'oci8') === 0); 
    }
    //crmv@add mssql support
    static function isMssql($dbType) { 
    	return (stripos($dbType ,'mssql') === 0); 
    }
    //crmv@add mssql support end
	
	// crmv@151405
	
	public static function getInstallableModulesFromPackages() {
		$packageDir = 'packages/vte/optional/';
		$optionalModules = (array) self::getInstallableModulesFromDirectory($packageDir);
		
		return $optionalModules;
	}

	public static function getInstallableBetaModulesFromPackages() {
		$packageDir = 'packages/vte/beta/vte/';
		$betaModules = (array) self::getInstallableModulesFromDirectory($packageDir);
		
		foreach ($betaModules as $option => &$modules) {
			if (is_array($modules)) {
				foreach ($modules as $module => &$details) {
					$details['selected'] = false;
					unset($details);
				}
			}
			unset($modules);
		}
		
		return $betaModules;
	}
	
	public static function getInstallableModulesFromDirectory($packageDir) {
		global $optionalModuleStrings;
		global $install_tmp;
		$install_tmp = true;
		require_once('vtlib/Vtecrm/Package.php');//crmv@207871
		require_once('vtlib/Vtecrm/Module.php');//crmv@207871
		require_once('vtlib/Vtecrm/Version.php');//crmv@207871

		$handle = opendir($packageDir);
		$installableModules = array();
		while (false !== ($file = readdir($handle))) {
			$packageNameParts = explode(".", $file);
			
			if ($packageNameParts[count($packageNameParts) - 1] != 'zip') {
				continue;
			}
			
			array_pop($packageNameParts);
			$packageName = implode("", $packageNameParts);
			
			if (!empty($packageName)) {
				$packagepath = $packageDir.$file;
				$package = new Vtecrm_Package();
				$moduleName = $package->getModuleNameFromZip($packagepath);
				
				if ($package->isModuleBundle()) {
					$bundleOptionalModule = array();
					$unzip = new Vtecrm_Unzip($packagepath);
					$unzip->unzipAllEx($package->getTemporaryFilePath());
					$moduleInfoList = $package->getAvailableModuleInfoFromModuleBundle();
					
					foreach ($moduleInfoList as $moduleInfo) {
						$moduleInfo = (array) $moduleInfo;
						$packagepath = $package->getTemporaryFilePath($moduleInfo['filepath']);
						$subModule = new Vtecrm_Package();
						$subModule->getModuleNameFromZip($packagepath);
						$bundleOptionalModule = self::getOptionalModuleDetails($subModule, $bundleOptionalModule);
					}
					
					$moduleDetails = array();
					$moduleDetails['description'] = $optionalModuleStrings[$moduleName . '_description'];
					$moduleDetails['selected'] = true;
					$moduleDetails['enabled'] = true;
					
					$migrationAction = 'install';
					if (count($bundleOptionalModule['update']) > 0) {
						$moduleDetails['enabled'] = false;
						$migrationAction = 'update';
					}
					
					$installableModules[$migrationAction]['module'][$moduleName] = $moduleDetails;
				} else {
					if ($package->isLanguageType()) {
						$package = new Vtecrm_Language();
						$package->getModuleNameFromZip($packagepath);
					}
					
					$installableModules = self::getOptionalModuleDetails($package, $installableModules);
				}
			}
		}
		
		if (is_array($installableModules['install']['language']) && is_array($installableModules['install']['module'])) {
			$installableModules['install'] = array_merge($installableModules['install']['module'], $installableModules['install']['language']);
		} elseif (is_array($installableModules['install']['language']) && !is_array($installableModules['install']['module'])) {
			$installableModules['install'] = $installableModules['install']['language'];
		} else {
			$installableModules['install'] = $installableModules['install']['module'];
		}
		
		if (is_array($installableModules['update']['language']) && is_array($installableModules['update']['module'])) {
			$installableModules['update'] = array_merge($installableModules['update']['module'], $installableModules['update']['language']);
		} elseif (is_array($installableModules['update']['language']) && !is_array($installableModules['update']['module'])) {
			$installableModules['update'] = $installableModules['update']['language'];
		} else {
			$installableModules['update'] = $installableModules['update']['module'];
		}
		
		return $installableModules;
	}
	
	// crmv@151405e
	
	/**
	 *
	 * @param String $packagepath - path to the package file.
	 * @return Array
	 */
	static function getOptionalModuleDetails($package, $optionalModulesInfo) {
		global $optionalModuleStrings,$table_prefix;
		
		$moduleUpdateVersion = $package->getVersion();
		$moduleForVersion = $package->getDependentVersion();//crmv@207991
		$moduleMaxVersion = $package->getDependentMaxVersion();//crmv@207991
		if($package->isLanguageType()) {
			$type = 'language';
		} else {
			$type = 'module';
		}
		$moduleDetails = null;
		$moduleName = $package->getModuleName();
		if($moduleName != null) {
			$moduleDetails = array();
			$moduleDetails['description'] = $optionalModuleStrings[$moduleName.'_description'];

			if(Vtecrm_Version::check($moduleForVersion,'>=') && ($moduleMaxVersion == '' || Vtecrm_Version::check($moduleMaxVersion,'<'))) {
				$moduleDetails['selected'] = true;
				$moduleDetails['enabled'] = true;
			} else {
				$moduleDetails['selected'] = false;
				$moduleDetails['enabled'] = false;
			}

			$migrationAction = 'install';
			if(!$package->isLanguageType()) {
				$moduleInstance = null;
				if(Vtecrm_Utils::checkTable($table_prefix.'_tab')) {
					$moduleInstance = Vtecrm_Module::getInstance($moduleName);
				}
				if($moduleInstance) {
					$migrationAction = 'update';
					if(version_compare($moduleUpdateVersion, $moduleInstance->version, '>=')) {
						$moduleDetails['enabled'] = false;
					}
				}
			} else {
				if(Vtecrm_Utils::CheckTable($table_prefix.Vtecrm_Language::TABLENAME)) {
					$languageList = array_keys(Vtecrm_Language::getAll());
					$prefix = $package->getPrefix();
					if(in_array($prefix, $languageList)) {
						$migrationAction = 'update';
					}
				}
			}
			$optionalModulesInfo[$migrationAction][$type][$moduleName] = $moduleDetails;
		}
		return $optionalModulesInfo;
	}	
	
	// Function to install/update mandatory modules
	public static function installMandatoryModules($skip_modules=array()) {
		require_once('vtlib/Vtecrm/Package.php');//crmv@207871
		require_once('vtlib/Vtecrm/Module.php');//crmv@207871
		require_once('include/utils/utils.php');
		//crmv@change packets path
		if ($handle = opendir('packages/vte/mandatory')) {		 
		//crmv@change packets path end	   
		    while (false !== ($file = readdir($handle))) {
				$packageNameParts = explode(".",$file);
				if($packageNameParts[count($packageNameParts)-1] != 'zip'){
					continue;
				}
				array_pop($packageNameParts);
				$packageName = implode("",$packageNameParts);
		        if (!empty($packageName)) {
		        	//crmv@cahnge path
		        	$packagepath = "packages/vte/mandatory/$file";
		        	//crmv@cahnge path end
					$package = new Vtecrm_Package();
	        		$module = $package->getModuleNameFromZip($packagepath);
	        		if($module != null) {
	        			if (!empty($skip_modules) && in_array($module,$skip_modules)) {
	        				continue;
	        			}
	        			$moduleInstance = Vtecrm_Module::getInstance($module);
				        if($moduleInstance) {
		        			updateVtlibModule($module, $packagepath);
		        		} else {
		        			installVtlibModule($packageName, $packagepath);
		        		}
	        		}
		        }
		    }
		    closedir($handle);
		}
	}

	public static function getMandatoryModuleList() {
		require_once('vtlib/Vtecrm/Package.php');//crmv@207871
		require_once('vtlib/Vtecrm/Module.php');//crmv@207871
		require_once('include/utils/utils.php');

		$moduleList = array();
		//crmv@change packets path
		if ($handle = opendir('packages/vte/mandatory')) {
		//crmv@change packets path end	
		    while (false !== ($file = readdir($handle))) {
				$packageNameParts = explode(".",$file);
				if($packageNameParts[count($packageNameParts)-1] != 'zip'){
					continue;
				}
				array_pop($packageNameParts);
				$packageName = implode("",$packageNameParts);
		        if (!empty($packageName)) {
		        	//crmv@change packets path
		        	$packagepath = "packages/vte/mandatory/$file";
		        	//crmv@change packets path end
					$package = new Vtecrm_Package();
	        		$moduleList[] = $package->getModuleNameFromZip($packagepath);
		        }
		    }
		    closedir($handle);
		}
		return $moduleList;
	}

	// crmv@151405
	
	public static function installSelectedOptionalModules($selected_modules, $source_directory = '', $destination_directory = '') {
		$packageDir = 'packages/vte/optional/';
		self::installSelectedModules($packageDir, $selected_modules, $source_directory, $destination_directory);
	}

	public static function installSelectedBetaModules($selected_beta_modules, $source_directory = '', $destination_directory = '') {
		$packageDir = 'packages/vte/beta/vte/';
		self::installSelectedModules($packageDir, $selected_beta_modules, $source_directory, $destination_directory);
	}

	public static function installSelectedModules($packageDir, $selected_modules, $source_directory = '', $destination_directory = '') {
		require_once('vtlib/Vtecrm/Package.php');//crmv@207871
		require_once('vtlib/Vtecrm/Module.php');//crmv@207871
		require_once('include/utils/utils.php');

		$selected_modules = explode(":", $selected_modules);
		
		$languagePacks = array();
		
		if ($handle = opendir($packageDir)) {
			while (false !== ($file = readdir($handle))) {
				$filename_arr = explode(".", $file);
				
				if ($filename_arr[count($filename_arr) - 1] != 'zip') {
					continue;
				}
				
				$packagename = $filename_arr[0];
				$packagepath = $packageDir.$file;
				
				$package = new Vtecrm_Package();
				$module = $package->getModuleNameFromZip($packagepath);
				
				if (!empty($packagename) && in_array($module, $selected_modules)) {
					if ($package->isLanguageType($packagepath)) {
						$languagePacks[$module] = $packagepath;
						continue;
					}
					
					if ($module != null) {
						if ($package->isModuleBundle()) {
							$unzip = new Vtecrm_Unzip($packagepath);
							$unzip->unzipAllEx($package->getTemporaryFilePath());
							$moduleInfoList = $package->getAvailableModuleInfoFromModuleBundle();
							
							foreach ($moduleInfoList as $moduleInfo) {
								$moduleInfo = (array) $moduleInfo;
								$packagepath = $package->getTemporaryFilePath($moduleInfo['filepath']);
								$subModule = new Vtecrm_Package();
								$subModuleName = $subModule->getModuleNameFromZip($packagepath);
								$moduleInstance = Vtecrm_Module::getInstance($subModuleName);
								if ($moduleInstance) {
									updateVtlibModule($subModuleName, $packagepath);
								} else {
									installVtlibModule($subModuleName, $packagepath);
								}
							}
						} else {
							$moduleInstance = Vtecrm_Module::getInstance($module);
							if ($moduleInstance) {
								updateVtlibModule($module, $packagepath);
							} else {
								installVtlibModule($module, $packagepath);
							}
						}
					}
				}
			}
			
			closedir($handle);
		}
		
		foreach ($languagePacks as $module => $packagepath) {
			installVtlibModule($module, $packagepath);
			continue;
		}
	}
	
	// crmv@151405e
	
	//Function to to rename the installation file and folder so that no one destroys the setup
	public static function renameInstallationFiles() {
		$renamefile = uniqid(rand(), true);
		
		$ins_file_renamed = true;
		if(!@rename("install.php", $renamefile."install.php.txt")) {
			if (@copy ("install.php", $renamefile."install.php.txt")) {
				if(!@unlink("install.php")) {
					$ins_file_renamed = false;			
				}
			} else {
				$ins_file_renamed = false;
			}
		}
		
		$ins_dir_renamed = true;
		if(!@rename("install/", $renamefile."install/")) {
			if (@copy ("install/", $renamefile."install/")) {
				if(!@unlink("install/")) {
					$ins_dir_renamed = false;			
				}
			} else {
				$ins_dir_renamed = false;
			}
		}
		
		$result = array();
		$result['renamefile'] = $renamefile;
		$result['install_file_renamed'] = $ins_file_renamed;
		$result['install_directory_renamed'] = $ins_dir_renamed;
		
		return $result;
	}

	public static function getSQLVersion($serverInfo) {
		if(!is_array($serverInfo)) {
			$version = explode('-',$serverInfo);
			$mysql_server_version=$version[0];
		} else {
			$mysql_server_version = $serverInfo['version'];
		}
		return $mysql_server_version;
	}
	//crmv@fix hostname
	public static function constructHostname($dbtype,$hostname,$port){
		if ($dbtype == 'mysqli' || $dbtype == 'mssqlnative') { // crmv@155585
			return $hostname;
		} else {
			if ($port == '') $port =ConfigFile_Utils::getDbDefaultPort($dbtype);
			$separator =ConfigFile_Utils::getDbDefaultPortSeparator($dbtype);
			return $hostname.$separator.$port;
		}
	}
	//crmv@fix hostname end

	public static function disableMorph() {
		if (file_exists('DisableMorphsuit.php')) {
			ob_start();
			@include_once('DisableMorphsuit.php');
			ob_end_clean();
			@unlink('DisableMorphsuit.php');
		}
	}
}

//Function used to execute the query and display the success/failure of the query
function ExecuteQuery($query,$params = Array()) {
	global $adb, $installationStrings, $conn;
	global $migrationlog;

	//For third option migration we have to use the $conn object because the queries should be executed in 4.2.3 db
	$status = $adb->pquery($query,$params);
	if(is_object($status)) {
		echo '
			<tr width="100%">
				<td width="10%"><font color="green"> '.$installationStrings['LBL_SUCCESS'].' </font></td>
				<td width="80%">'.$query.'</td>
			</tr>';
		$migrationlog->debug("Query Success ==> $query");
	} else {
		echo '
			<tr width="100%">
					<td width="5%"><font color="red"> '.$installationStrings['LBL_FAILURE'].' </font></td>
				<td width="70%">'.$query.'</td>
			</tr>';
		$migrationlog->debug("Query Failed ==> $query \n Error is ==> [".$adb->database->ErrorNo()."]".$adb->database->ErrorMsg());
	}
	return $status;
}

//crmv@18123
function get_logo_install($mode){
	include_once('vteversion.php'); // crmv@181168
	global $enterprise_mode;
	$logo_path = 'themes/logos/';
	if ($mode == 'favicon')
		$extension = 'ico';
	else		
		$extension = 'png';
	$logo_path.=$enterprise_mode."_".$mode.".".$extension;
	return $logo_path;
}
//crmv@18123e
?>