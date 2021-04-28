<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/logging.php');
include('adodb/adodb.inc.php');
//crmv@fix schema
require_once("adodb/adodb-xmlschema03.inc.php");
//crmv@fix schema end

$log = LoggerManager::getLogger('VT');
$logsqltm = LoggerManager::getLogger('SQLTIME');

// crmv@115378
require_once('include/utils/PerformancePrefs.php');

// ---- helper functions ----

//crmv@27811
function add_brakets(&$key,&$item){
	$key = "[$key]";
}
//crmv@27811e
//crmv@24791 crmv@165479
function add_doublequotes(&$key,&$item, $force = false){
	if($force || in_array($key,getOracleReservedWords())) {
		$key = '"'.$key.'"';
	}
}
//crmv@24791e crmv@165479e
//crmv@26687
function add_backtick(&$key,&$item){
	$key = "`{$key}`";
}

// Return Question mark
function _questionify($v){
	return "?";
}

/**
* Function to generate question marks for a given list of items
*/
function generateQuestionMarks($items_list) {
	// array_map will call the function specified in the first parameter for every element of the list in second parameter
	if (is_array($items_list)) {
		return implode(",", array_map("_questionify", $items_list));
	} else {
		return implode(",", array_map("_questionify", explode(",", $items_list)));
	}
}
//crmv@26687e


// Callback class useful to convert PreparedStatement Question Marks to SQL value
// See function convertPS2Sql in PearDatabase below
class PreparedQMark2SqlValue {
	// Constructor
	function __construct($vals){
        $this->ctr = 0;
        $this->vals = $vals;
    }
    function call($matches){ 
            /** 
             * If ? is found as expected in regex used in function convert2sql 
             * /('[^']*')|(\"[^\"]*\")|([?])/ 
             * 
             */ 
            if($matches[3]=='?'){ 
                    $this->ctr++; 
                    return $this->vals[$this->ctr-1]; 
            }else{ 
                    return $matches[0]; 
            } 
    } 
}


/**
 * Cache Class for PearDatabase
 */
class PearDatabaseCache {
	var $_queryResultCache = Array();
	var $_parent;

	// Cache the result if rows is less than this
	var $_CACHE_RESULT_ROW_LIMIT;

	/**
	 * Constructor
	 */
	function __construct($parent) {
		$this->_parent = $parent;
		$this->_CACHE_RESULT_ROW_LIMIT = PerformancePrefs::getInteger('CACHE_RESULT_ROW_LIMIT', 100);
	}

	/**
	 * Reset the cache contents
	 */
	function resetCache() {
		unset($this->_queryResultCache);
		$this->_queryResultCache = Array();
	}

	/**
	 * Cache SQL Query Result (perferably only SELECT SQL)
	 */
	function cacheResult($result, $sql, $params=false) {
		// We don't want to cache NON-SELECT query results now
		if(stripos(trim($sql), 'SELECT ') !== 0) {
			return;
		}
		// If the result is too big, don't cache it
		if($this->_parent->num_rows($result) > $this->_CACHE_RESULT_ROW_LIMIT) {
			global $log;
			$log->fatal("[" . get_class($this) . "] Cannot cache result! $sql [Exceeds limit ".
				$this->_CACHE_RESULT_ROW_LIMIT . ", Total Rows " . $this->_parent->num_rows($result) . "]");
			return false;
		}
		$usekey = $sql;
		if(!empty($params)) $usekey = $this->_parent->convert2Sql($sql, $this->_parent->flatten_array($params));
		$this->_queryResultCache[$usekey] = $result;
	}

	/**
	 * Get the cached result for re-use
	 */
	function getCacheResult($sql, $params=false) {
		$result = false;
		$usekey = $sql;
		if(!empty($params)) $usekey = $this->_parent->convert2Sql($sql, $this->_parent->flatten_array($params));
		$result = $this->_queryResultCache[$usekey];
		// Rewind the result for re-use
		if($result) {
			// If result not in use rewind it
			if($result->EOF) $result->MoveFirst();
			else if($result->CurrentRow() != 0) {
				global $log;
				$log->fatal("[" . get_class($this) . "] Cannot reuse result! $usekey [Rows Total " .
					$this->_parent->num_rows($result) . ", Currently At: " . $result->CurrentRow() . "]");
				// Do no allow result to be re-used if it is in use.
				$result = false;
			}
		}
		return $result;
	}
}

class PearDatabase{
    var $database = null;
    var $dieOnError = false;
	var $exceptOnError = false; // crmv@64542
    var $usePersistent = true; // crmv@65455
    var $dbType = null;
    var $dbHostName = null;
    var $dbName = null;
    var $dbOptions = null;
    var $userName=null;
    var $userPassword=null;
	var $dbCharset=null; // crmv@124454
    var $query_time = 0;
    var $log = null;
    var $lastmysqlrow = -1;
    var $enableSQLlog = false;
    var $continueInstallOnError = true;
    //crmv@datadict
    var $datadict = null;
    //crmv@datadict end
    var $enableVTELog = true;
	
	public $deadlockRetry = 2; // crmv@62863
	
	public $statistics = array();

    // If you want to avoid executing PreparedStatement, set this to true
    // PreparedStatement will be converted to normal SQL statement for execution
	var $avoidPreparedSql = false;

	/**
	 * Performance tunning parameters (can be configured through performance.prefs.php)
	 * See the constructor for initialization
	 */
	var $isdb_default_utf8_charset = false;
	var $enableCache = false;

	var $_cacheinstance = false; // Will be auto-matically initialized if $enableCache is true
	
	var $slave = false; // crmv@185894
	
	var $clientFlags = false; // crmv@193619
	
	/**
	 * API's to control cache behavior
	 */
	function __setCacheInstance($cacheInstance) {
		$this->_cacheinstance = $cacheInstance;
	}
	/** Return the cache instance reference (using &) */
	function &getCacheInstance() {
		return $this->_cacheinstance;
	}
	function isCacheEnabled() {
		return ($this->enableCache && ($this->getCacheInstance() != false));
	}
	function clearCache() {
		if($this->isCacheEnabled()) $this->getCacheInstance()->resetCache();
	}
	function toggleCache($newstatus) {
		$oldstatus = $this->enableCache;
		$this->enableCache = $newstatus;
		return $oldstatus;
	}
	// END

	/**
	 * Manage instance usage of this class
	 */
	static function &getInstance() {
		global $adb, $log;

		if(!isset($adb)) {
			$adb = new self();
		}
		return $adb;
	}
	// END

	/*
	 * Reset query result for resuing if cache is enabled.
	 */
	function resetQueryResultToEOF(&$result) {
		if($result) {
			if($result->MoveLast()) {
				$result->MoveNext();
			}
		}
	}
	// END

    function isMySQL() { return (stripos($this->dbType ,'mysql') === 0);}
    //crmv@add mssql && oracle
    function isMssql() { return (stripos($this->dbType ,'mssql') === 0);}
    function isOracle() { return (stripos($this->dbType ,'oci8') === 0); }
    //crmv@add mssql && oracle end
    function isPostgres() { return $this->dbType=='pgsql'; }

    function println($msg)
    {
		require_once('include/logging.php');
		$log1 = LoggerManager::getLogger('VT');
		if(is_array($msg)) {
		    $log1->info("PearDatabse ->".print_r($msg,true));
		} else {
		    $log1->info("PearDatabase ->".$msg);
		}
		return $msg;
    }

    function setDieOnError($value){	 $this->dieOnError = $value; }
	function setExceptOnError($value){	 $this->exceptOnError = $value; } // crmv@64542
    function setDatabaseType($type){ $this->dbType = $type; }
    function setUserName($name){ $this->userName = $name; }

    function setOption($name, $value){
		if(isset($this->dbOptions)) $this->dbOptions[$name] = $value;
		if(isset($this->database)) $this->database->setOption($name, $value);
    }
    
    // crmv@193619
	function setSSLconnection(){
		if ($this->isMySQL()) {
			$this->clientFlags = MYSQLI_CLIENT_SSL;
		}
	}
	// crmv@193619e

    function setUserPassword($pass){ $this->userPassword = $pass; }
    function setDatabaseName($db){ $this->dbName = $db;	}
    function setDatabaseHost($host){ $this->dbHostName = $host;	}
	function setCharset($charset){ $this->dbCharset = $charset; } // crmv@124454

    function getDataSourceName(){
		return 	$this->dbType. "://".$this->userName.":".$this->userPassword."@". $this->dbHostName . "/". $this->dbName;
    }

    function startTransaction() {
	    if($this->isPostgres()) return;
		$this->checkConnection();
		$this->println("TRANS Started");
		$this->database->BeginTrans();
    }

    function completeTransaction() {
	    if($this->isPostgres()) return;
		if($this->database->HasFailedTrans()) $this->println("TRANS  Rolled Back");
		else $this->println("TRANS  Commited");

		$this->database->CommitTrans();
		$this->println("TRANS  Completed");
    }

    function hasFailedTransaction(){ return $this->database->HasFailedTrans();   }

    function checkError($msg='', $dieOnError=false) {
		// crmv@64542
		if ($this->exceptOnError) {
			if ($this->isMssql() && $this->hasFailedTransaction()) $this->database->RollbackTrans(); // crmv@198115
			$error = "ADODB error ".$msg."->[".$this->database->ErrorNo()."]".$this->database->ErrorMsg();
			throw new Exception($error);
			return false;
		}
		// crmv@64542e
		if($this->dieOnError || $dieOnError) {
			$bt = debug_backtrace();
			$ut = array();
			foreach ($bt as $t) {
				$ut[] = array('file'=>$t['file'],'line'=>$t['line'],'function'=>$t['function']);
			}
			echo '<pre>';
			var_export($ut);
			echo '</pre>';
		    $this->println("ADODB error ".$msg."->[".$this->database->ErrorNo()."]".$this->database->ErrorMsg());
		    $msg_head = trim(substr($msg,0,stripos($msg,':')));
		    $msg_content = trim(substr($msg,stripos($msg,':'),strlen($msg)));
		    $query = "SELECT $replace ".substr($query, stripos($query,' FROM '),strlen($query));
		    echo "<br> $msg_head => ";
		    echo "<br> $msg_content";
		    echo "<br> ADODB error => ";
		    echo "<br> [".$this->database->ErrorNo()."] ".$this->database->ErrorMsg();
		    if ($this->isMssql() && $this->hasFailedTransaction()) $this->database->RollbackTrans(); // crmv@198115
		    die ();
		} else {
		    $this->println("ADODB error ".$msg."->[".$this->database->ErrorNo()."]".$this->database->ErrorMsg());
		}
		return false;
    }
    // crmv@155585
    //crmv@fix space values in mssql
	function TrimArray($Input){
	 	if (is_string($Input))
			return trim($Input);
		elseif (is_array($Input)) {
			return array_map(array($this,__FUNCTION__), $Input);
		}
	 	return $Input;
	}
	//crmv@fix space values in mssql end
	// crmv@155585e
    function change_key_case($arr) {
		//crmv@fix space values in mssql
		if ($this->isMssql()){
			$arr = $this->TrimArray($arr);
		}
		//crmv@fix space values in mssql end
		return is_array($arr)?array_change_key_case($arr):$arr;
    }

    var $req_flist;
    function checkConnection(){
		global $log;

		if(!isset($this->database)) {
		    $this->println("TRANS creating new connection");
		    $this->connect(false);
		} else {
		    //$this->println("checkconnect using old connection");
		}
    }

	/**
	 * Put out the SQL timing information
	 */
	function logSqlTiming($startat, $endat, $sql, $params=false) {
		if ($this->enableVTELog) vtelog::log($sql,'SQL',$startat,$endat,$params); //crmv@47905
	}

	/**
	 * Execute SET NAMES UTF-8 on the connection based on configuration.
	 */
	function executeSetNamesUTF8SQL($force = false) {
		global $default_charset;
		// Performance Tuning: If database default charset is UTF-8, we don't need this
		if(strtoupper($default_charset) == 'UTF-8' && ($force || !$this->isdb_default_utf8_charset)) {

			$sql_start_time = microtime(true);

			$setnameSql = "SET NAMES utf8";
			$this->database->Execute($setnameSql);
			$this->logSqlTiming($sql_start_time, microtime(true), $setnameSql);
		}
	}

	// crmv@63349
	// Log queries to catch other temporary table creation.
	// You can edit the code and log any kind of query
	function logQuery($query, $params = null) {
		/*
		$log = false;
		if (preg_match('/^create temporary/i', $query)) $log = true;
		if ($log) {
			$str = "LOGGED QUERY: $query\n";
			file_put_contents('/tmp/logsql.txt', $str, FILE_APPEND);
		}
		*/
		$showQuery = $_REQUEST['show_query'] ?? '';
		$showStats = $_REQUEST['show_stats'] ?? '';

		if ($showQuery == 'true' && $showStats == 'true') {
			$this->statistics['query_count']++;
			$sub6 = strtolower(substr($query, 0, 6));
			if ($sub6 == 'select') {
				$this->statistics['select']++;
			} elseif ($sub6 == 'insert') {
				$this->statistics['insert']++;
				if (PHP_VERSION_ID >= 50306) {
					$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$caller = $db[1]['file'].' : '.$db[1]['line'];
				}
				$this->statistics['inserts'][] = array('query' => $query, 'params' => $params, 'caller' => $caller);
			} elseif ($sub6 == 'update') {
				$this->statistics['update']++;
				if (PHP_VERSION_ID >= 50306) {
					$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$caller = $db[1]['file'].' : '.$db[1]['line'];
				}
				$this->statistics['updates'][] = array('query' => $query, 'params' => $params, 'caller' => $caller);
			} elseif ($sub6 == 'delete') {
				$this->statistics['delete']++;
				if (PHP_VERSION_ID >= 50306) {
					$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$caller = $db[1]['file'].' : '.$db[1]['line'];
				}
				$this->statistics['deletes'][] = array('query' => $query, 'params' => $params, 'caller' => $caller);
			} else {
				$this->statistics['other']++;
				if (PHP_VERSION_ID >= 50306) {
					$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$caller = $db[1]['file'].' : '.$db[1]['line'];
				}
				$this->statistics['others'][] = array('query' => $query, 'params' => $params, 'caller' => $caller);
			}
			if (!is_array($this->queryHashes)) $this->queryHashes = array();
			$hash = md5(strtolower($query).'_'.serialize($params));
			$this->queryHashes[$hash]++;
			if ($this->queryHashes[$hash] > 1) {
				$this->statistics['duplicates_count']++;
				if (PHP_VERSION_ID >= 50306) {
					$db = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
					$caller = $db[1]['file'].' : '.$db[1]['line'];
				}
				$this->statistics['duplicates'][$hash] = array('query' => $query, 'params' => $params, 'count' => $this->queryHashes[$hash], 'caller' => $caller);
			}
		}
	}
	// crmv@63349e

	/**
	 * Execute query in a batch.
	 *
	 * For example:
	 * INSERT INTO TABLE1 VALUES (a,b);
	 * INSERT INTO TABLE1 VALUES (c,d);
	 *
	 * like: INSERT INTO TABLE1 VALUES (a,b), (c,d)
	 */
	function query_batch($prefixsql, $valuearray) {
		if(PerformancePrefs::getBoolean('ALLOW_SQL_QUERY_BATCH')) {
			$sql = $prefixsql;
			$suffixsql = $valuearray;
			if(!is_array($valuearray)) $suffixsql = implode(',', $valuearray);
			$this->query($prefixsql . $suffixsql);
		} else {
			if(is_array($valuearray) && !empty($valuearray)) {
				foreach($valuearray as $suffixsql) {
					$this->query($prefixsql . $suffixsql);
				}
			}
		}
	}

	function query($sql, $dieOnError=false, $msg='', $temp=false)	//crmv@70475
    {
		global $log, $default_charset;
		// Performance Tuning: Have we cached the result earlier?
		if($this->isCacheEnabled()) {
			$fromcache = $this->getCacheInstance()->getCacheResult($sql);
			if($fromcache) {
				$log->debug("Using query result from cache: $sql");
				return $fromcache;
			}
		}
		// END
		$log->debug('query being executed : '.$sql);
		$this->checkConnection();
	
		$this->executeSetNamesUTF8SQL();
		$this->logQuery($sql); // crmv@63349
	
		$sql_start_time = microtime(true);
		// crmv@62863 - MySql deadlock handling
		$tries = 0;
		do {
			$tryAgain = false;
			$result = $this->database->Execute($sql);
			++$tries;
			// try again in case of deadlock
			if (!$result && $tries <= $this->deadlockRetry && $this->isMySQL() && $this->database->ErrorNo() == 1213) {
				$tryAgain = true;
			}
		} while ($tryAgain);
		// crmv@62863e
		$this->logSqlTiming($sql_start_time, microtime(true), $sql);
	
		$this->lastmysqlrow = -1;
		if(!$result)$this->checkError($msg.' Query Failed:' . $sql . '::', $dieOnError);
	
		// Performance Tuning: Cache the query result
		if($this->isCacheEnabled()) {
			$this->getCacheInstance()->cacheResult($result, $sql);
		}
		// END
		
		//crmv@47905bis		crmv@70475
		if (!$temp && !$this->slave) { // crmv@185894
	    	$cache_emptiers = array('create table','drop table','update table');
			foreach($cache_emptiers as $ce) {
				if (stripos($sql,$ce) === 0) {
					$cache = Cache::getInstance('table_exist');
					$cache->clear();
					break;
				}
			}
		}
		//crmv@47905bis e	crmv@70475e
		
		return $result;
    }


	/**
	 * Convert PreparedStatement to SQL statement
	 */
	function convert2Sql($ps, $vals) {
		if(empty($vals)) { return $ps; }
		// TODO: Checks need to be added array out of bounds situations
		for($index = 0; $index < count($vals); $index++) {
            // Package import pushes data after XML parsing, so type-cast it
            if(is_a($vals[$index], 'SimpleXMLElement')) {
                $vals[$index] = (string) $vals[$index];
            }
			if(is_string($vals[$index])) {
				if($vals[$index] == '') {
					$vals[$index] = $this->database->Quote($vals[$index]);
				}
				else {
					$vals[$index] = "'".$this->sql_escape_string($vals[$index]). "'";
				}
			}
			elseif($vals[$index] === null) {
				$vals[$index] = "NULL";
			}
		}
		$sql = preg_replace_callback("/('[^']*')|(\"[^\"]*\")|([?])/", array(new PreparedQMark2SqlValue($vals),"call"), $ps);
		return $sql;
	}

  	/* ADODB prepared statement Execution
   	* @param $sql -- Prepared sql statement
   	* @param $params -- Parameters for the prepared statement
   	* @param $dieOnError -- Set to true, when query execution fails
   	* @param $msg -- Error message on query execution failure
   	*/
	function pquery($sql, $params, $dieOnError=false, $msg='', $temp=false) {	//crmv@70475
		global $log, $default_charset;
		// Performance Tuning: Have we cached the result earlier?
		if($this->isCacheEnabled()) {
			$fromcache = $this->getCacheInstance()->getCacheResult($sql, $params);
			if($fromcache) {
				$log->debug("Using query result from cache: $sql");
				return $fromcache;
			}
		}
		// END
		$log->debug('Prepared sql query being executed : '.$sql);
		$this->checkConnection();

		$this->executeSetNamesUTF8SQL();
		$this->logQuery($sql, $params); // crmv@63349

		$sql_start_time = microtime(true);
		$params = $this->flatten_array($params);
		if (count($params) > 0) {
			$log->debug('Prepared sql query parameters : [' . implode(",", $params) . ']');
		}

		// crmv@62863 - MySql deadlock handling
		if($this->avoidPreparedSql || empty($params)) {
			$sql = $this->convert2Sql($sql, $params);
		}
		$tries = 0;
		do {
			$tryAgain = false;
			if($this->avoidPreparedSql || empty($params)) {
				$result = $this->database->Execute($sql);	// crmv@65455 - E_STRICT warning
			} else {
				$result = $this->database->Execute($sql, $params); // crmv@65455
			}
			++$tries;
			// try again in case of deadlock
			if (!$result && $tries <= $this->deadlockRetry && $this->isMySQL() && $this->database->ErrorNo() == 1213) {
				$tryAgain = true;
			}
		} while ($tryAgain);
		// crmv@62863e
		$sql_end_time = microtime(true);
		$this->logSqlTiming($sql_start_time, $sql_end_time, $sql, $params);

		$this->lastmysqlrow = -1;
		if(!$result)$this->checkError($msg.' Query Failed:' . $sql . '::', $dieOnError);

		// Performance Tuning: Cache the query result
		if($this->isCacheEnabled()) {
			$this->getCacheInstance()->cacheResult($result, $sql, $params);
		}
		// END
		
		//crmv@47905bis		crmv@70475
		if (!$temp && !$this->slave) { // crmv@185894
	    	$cache_emptiers = array('create table','drop table','update table','rename table');
			foreach($cache_emptiers as $ce) {
				if (stripos($sql,$ce) === 0) {
					$cache = Cache::getInstance('table_exist');
					$cache->clear();
					break;
				}
			}
		}
		//crmv@47905bis e	crmv@70475e
		
		return $result;
	}

	// crmv@74560
	/**
	 * Insert many rows into the same table. This is considerably faster than
	 * issuing multiple insert queries.
	 */
	function bulkInsert($table, $columns, $rows = array(), $chunkSize = 100, $ignore = false) { // crmv@185894
		
		if (!is_array($rows) || count($rows) == 0) return;
		
		if (is_array($columns)) $this->format_columns($columns);
		
		if ($this->isMysql() || $this->isMsSql()) {
			$chunks = array_chunk($rows, $chunkSize);
			$ignoreSql = ($ignore ? 'IGNORE ' : ''); // crmv@185894
			foreach ($chunks as $rowsInChunk) {
				if (is_array($columns) && count($columns) > 0) {
					$sql = "INSERT $ignoreSql INTO $table (".implode(',', $columns).") VALUES "; // crmv@185894
				} else {
					$sql = "INSERT $ignoreSql INTO $table VALUES "; // crmv@185894
				}
				$i=0;
				foreach ($rowsInChunk as $row) {
					if ($i > 0) $sql .= ", ";
					$sql .= "(".implode(',', array_map(array($this, 'quote'), $row)).")";
					++$i;
				}
				$this->query($sql);
			}
		} elseif ($this->isOracle()) {
			$chunks = array_chunk($rows, $chunkSize);
			foreach ($chunks as $rowsInChunk) {
				$sql = "INSERT ALL ";
				foreach ($rowsInChunk as $row) {
					if (is_array($columns) && count($columns) > 0) {
						$sql .= " INTO $table (".implode(',', $columns).") VALUES ";
					} else {
						$sql .= " INTO $table VALUES ";
					}				
					$sql .= "(".implode(',', array_map(array($this, 'quote'), $row)).")";
				}
				$sql .= " SELECT * FROM dual";
				$this->query($sql);
			}
		} else {
			//fallback on multiple inserts
			if (is_array($columns) && count($columns) > 0) {
				$sql = "INSERT INTO $table (".implode(',', $columns).") VALUES (".generateQuestionMarks($rows[0]).")";
			} else {
				$sql = "INSERT INTO $table VALUES (".generateQuestionMarks($rows[0]).")";
			}
			foreach ($rows as $row) {
				$this->pquery($sql, $row);
			}
		}
	}
	// crmv@74560e

	/**
	 * Flatten the composite array into single value.
	 * Example:
	 * $input = array(10, 20, array(30, 40), array('key1' => '50', 'key2'=>array(60), 70));
	 * returns array(10, 20, 30, 40, 50, 60, 70);
	 */
	function flatten_array($input, $output=null) {
		if($input == null) return array(); // crmv@167234
		if($output == null) $output = array();
		foreach($input as $value) {
			if(is_array($value)) {
				$output = $this->flatten_array($value, $output);
			} else {
				array_push($output, $value);
			}
		}
		return $output;
	}

    function getEmptyBlob($is_string=true)
    {
    //crmv@fix oracle blob
		if($this->isOracle())
			return 'empty_blob()';
		if ($is_string)
			return 'null';
		return null;
	//crmv@fix oracle blob end
    }
    //crmv@fix clob
    function getEmptyClob($is_string=true)
    {
		if($this->isOracle())
			return 'empty_clob()';
		if ($is_string)
			return 'null';
		return null;
    }
	//crmv@fix clob end
    function updateBlob($tablename, $colname, $id, $data)
    {
		$this->println("updateBlob t=".$tablename." c=".$colname." id=".$id);
		$this->checkConnection();
		$this->executeSetNamesUTF8SQL();

		$sql_start_time = microtime(true);
		$result = $this->database->UpdateBlob($tablename, $colname, $data, $id);
		$this->logSqlTiming($sql_start_time, microtime(true), "Update Blob $tablename, $colname, $id");

//		$this->println("updateBlob t=".$tablename." c=".$colname." id=".$id." status=".$result);
		return $result;
    }
    function updateClob($tablename, $colname, $id, $data)
    {
		$this->println("updateBlob t=".$tablename." c=".$colname." id=".$id);
		$this->checkConnection();
		$this->executeSetNamesUTF8SQL();

		$sql_start_time = microtime(true);
		$result = $this->database->UpdateBlob($tablename, $colname, $data, $id,'CLOB');
		$this->logSqlTiming($sql_start_time, microtime(true), "Update Clob $tablename, $colname, $id");

//		$this->println("updateClob t=".$tablename." c=".$colname." id=".$id." status=".$string_result);
		return $result;
    }

    function updateBlobFile($tablename, $colname, $id, $filename)
    {
	$this->println("updateBlobFile t=".$tablename." c=".$colname." id=".$id." f=".$filename);
	$this->checkConnection();
	$this->executeSetNamesUTF8SQL();

	$sql_start_time = microtime(true);
	$result = $this->database->UpdateBlobFile($tablename, $colname, $filename, $id);
	$this->logSqlTiming($sql_start_time, microtime(true), "Update Blob $tablename, $colname, $id");

	$this->println("updateBlobFile t=".$tablename." c=".$colname." id=".$id." f=".$filename." status=".$result);
	return $result;
    }

    function limitQuery($sql,$start,$count, $dieOnError=false, $msg='')
    {
	global $log;
	if ($start == '') $start = 0;
	//$this->println("ADODB limitQuery sql=".$sql." st=".$start." co=".$count);
	$log->debug(' limitQuery sql = '.$sql .' st = '.$start .' co = '.$count);
	$this->checkConnection();

	$this->executeSetNamesUTF8SQL();

	$sql_start_time = microtime(true);
	$result = $this->database->SelectLimit($sql,$count,$start);
	$this->logSqlTiming($sql_start_time, microtime(true),$result->sql); //crmv@47905
	
	if(!$result) $this->checkError($msg.' Limit Query Failed:' . $sql . '::', $dieOnError);	
	return $result;		
    }    
    //crmv@limit multi-database
    function limitpQuery($sql,$start,$count,$params=Array(),$dieOnError=false, $msg='')
    {
	global $log;
	if ($start == '') $start = 0;
	//$this->println("ADODB limitQuery sql=".$sql." st=".$start." co=".$count);
	$log->debug(' limitQuery sql = '.$sql .' st = '.$start .' co = '.$count);
	$this->checkConnection();

	$this->executeSetNamesUTF8SQL();
	$params = $this->flatten_array($params);
	if (count($params) > 0) {
		$log->debug('Prepared sql query parameters : [' . implode(",", $params) . ']');
	}
	if($this->avoidPreparedSql || empty($params)) {
		$sql = $this->convert2Sql($sql, $params);
	}
	$sql_start_time = microtime(true);
	$result = $this->database->SelectLimit($sql,$count,$start);
	$this->logSqlTiming($sql_start_time, microtime(true),$result->sql); //crmv@47905
	
	if(!$result) $this->checkError($msg.' Limit pQuery Failed:' . $sql . '::', $dieOnError);	
	return $result;		
    }
    //crmv@limit multi-database end
    function getOne($sql, $dieOnError=false, $msg='')
    {
	$this->println("ADODB getOne sql=".$sql);
	$this->checkConnection();

	$this->executeSetNamesUTF8SQL();

	$sql_start_time = microtime(true);
	$result = $this->database->GetOne($sql);
	$this->logSqlTiming($sql_start_time, microtime(true),$sql); //crmv@47905
	
	if(!$result) $this->checkError($msg.' Get one Query Failed:' . $sql . '::', $dieOnError);
	return $result;
    }

    function getFieldsDefinition(&$result)
    {
	//$this->println("ADODB getFieldsArray");
	$field_array = array();
	if(! isset($result) || empty($result))
	{
		return 0;
	}

	$i = 0;
	$n = $result->FieldCount();
	while ($i < $n)
	{
		$meta = $result->FetchField($i);
		if (!$meta)
		{
			return 0;
		}
		array_push($field_array,$meta);
		$i++;
	}

	//$this->println($field_array);
	return $field_array;
    }

    function getFieldsArray(&$result)
    {
	//$this->println("ADODB getFieldsArray");
	$field_array = array();
	if(! isset($result) || empty($result))
	{
	    return 0;
	}

	$i = 0;
	$n = $result->FieldCount();
	while ($i < $n)
	{
	    $meta = $result->FetchField($i);
	    if (!$meta)
	    {
		return 0;
	    }
	    array_push($field_array,$meta->name);
	    $i++;
	}

	//$this->println($field_array);
	return $field_array;
    }

    function getRowCount(&$result){
		global $log;
		if(isset($result) && !empty($result))
		    $rows= $result->RecordCount();
		return $rows;
    }

    /* ADODB newly added. replacement for mysql_num_rows */
    function num_rows(&$result) {
		return $this->getRowCount($result);
    }

    /* ADODB newly added. replacement form mysql_num_fields */
    function num_fields(&$result) {
		return $result->FieldCount();
    }

    /* ADODB newly added. replacement for mysql_fetch_array() */
    function fetch_array(&$result) {
		if($result->EOF) {
		    //$this->println("ADODB fetch_array return null");
		    return NULL;
		}
		$arr = $result->FetchRow();
        if(is_array($arr))
			$arr = array_map('to_html', $arr);
        return $this->change_key_case($arr);
    }
    //crmv@get no-html data
    function fetch_array_no_html(&$result) {
		if($result->EOF) {
		    //$this->println("ADODB fetch_array return null");
		    return NULL;
		}
		$arr = $result->FetchRow();
        return $this->change_key_case($arr);
    }
	//crmv@get no-html data end
    ## adds new functions to the PearDatabase class to come around the whole
    ## broken query_result() idea
    ## Code-Contribution given by weigelt@metux.de - Starts
    function run_query_record_html($query) {
	    if (!is_array($rec = $this->run_query_record($query)))
	    	return $rec;
	    foreach ($rec as $walk => $cur)
	    	$r[$walk] = to_html($cur);
	    return $r;
    }

    function sql_quote($data) {
		if (is_array($data)) {
			switch($data['type']) {
			case 'text':
			case 'numeric':
			case 'integer':
			case 'oid':
				return $this->quote($data['value']);
				break;
			case 'timestamp':
				return $this->formatDate($data['value']);
				break;
			default:
				throw new Exception("unhandled type: ".serialize($cur));
			}
		} else
			return $this->quote($data);
    }

    function sql_insert_data($table, $data) {
		if (!$table)
			throw new Exception("missing table name");
		if (!is_array($data))
			throw new Exception("data must be an array");
		if (!count($table))
	    	throw new Exception("no data given");

		$sql_fields = '';
		$sql_data = '';
		foreach($data as $walk => $cur) {
			$sql_fields .= ($sql_fields?',':'').$walk;
			$sql_data   .= ($sql_data?',':'').$this->sql_quote($cur);
		}
		return 'INSERT INTO '.$table.' ('.$sql_fields.') VALUES ('.$sql_data.')';
    }

    function run_insert_data($table,$data) {
	    $query = $this->sql_insert_data($table,$data);
	    $res = $this->query($query);
	    $this->query("commit;");
    }

    function run_query_record($query) {
	    $result = $this->query($query);
	    if (!$result)
	    	return;
	    if (!is_object($result))
	    	throw new Exception("query \"$query\" failed: ".serialize($result));
	    $res = $result->FetchRow();
	    $rowdata = $this->change_key_case($res);
	    return $rowdata;
    }

    function run_query_allrecords($query) {
	    $result = $this->query($query);
	    $records = array();
	    $sz = $this->num_rows($result);
	    for ($i=0; $i<$sz; $i++)
			$records[$i] = $this->change_key_case($result->FetchRow());
	    return $records;
    }

    function run_query_field($query,$field='') {
	    $rowdata = $this->run_query_record($query);
	    if(isset($field) && $field != '')
	    	return $rowdata[$field];
	    else
	    	return array_shift($rowdata);
    }

    function run_query_list($query,$field){
	    $records = $this->run_query_allrecords($query);
	    foreach($records as $walk => $cur)
			$list[] = $cur[$field];
    }

    function run_query_field_html($query,$field){
	    return to_html($this->run_query_field($query,$field));
    }

    function result_get_next_record($result){
	    return $this->change_key_case($result->FetchRow());
    }

    // create an IN expression from an array/list
    function sql_expr_datalist($a) {
	    if (!is_array($a))
	    	throw new Exception("not an array");
	    if (!count($a))
	    	throw new Exception("empty arrays not allowed");

	    foreach($a as $walk => $cur)
	    	$l .= ($l?',':'').$this->quote($cur);
	    return ' ( '.$l.' ) ';
    }

    // create an IN expression from an record list, take $field within each record
    function sql_expr_datalist_from_records($a,$field) {
	    if (!is_array($a))
	    	throw new Exception("not an array");
	    if (!$field)
	    	throw new Exception("missing field");
	    if (!count($a))
	    	throw new Exception("empty arrays not allowed");

	    foreach($a as $walk => $cur)
	    	$l .= ($l?',':'').$this->quote($cur[$field]);

	    return ' ( '.$l.' ) ';
    }

    function sql_concat($list) {
    	//crmv@fix concat multi-database
    	return call_user_func_array(array($this->database,'Concat'),$list);
    	//crmv@fix concat multi-database end
    }
    ## Code-Contribution given by weigelt@metux.de - Ends

    /* ADODB newly added. replacement for mysql_result() */
    function query_result(&$result, $row, $col=0) {
		if (!is_object($result))
	                throw new Exception("result is not an object");
		$result->Move($row);
		$rowdata = $this->change_key_case($result->FetchRow());
		//$this->println($rowdata);
		if($col == 'fieldlabel') $coldata = $rowdata[$col];
		else $coldata = to_html($rowdata[$col]);
		return $coldata;
    }
    //crmv@fix no-html data
    function query_result_no_html(&$result, $row, $col=0) {
		if (!is_object($result))
	                throw new Exception("result is not an object");
		$result->Move($row);
		$rowdata = $this->change_key_case($result->FetchRow());
		//$this->println($rowdata);
		$coldata = $rowdata[$col];
		return $coldata;
    }
	//crmv@fix no-html data end
	// Function to get particular row from the query result
	function query_result_rowdata(&$result, $row=0) {
		if (!is_object($result))
                throw new Exception("result is not an object");
		$result->Move($row);
		$rowdata = $this->change_key_case($result->FetchRow());
		if ($rowdata) {
			foreach($rowdata as $col => $coldata) {
				if($col != 'fieldlabel')
					$rowdata[$col] = to_html($coldata);
			}
		}
		return $rowdata;
	}

	/**
	 * Get an array representing a row in the result set
	 * Unlike it's non raw siblings this method will not escape
	 * html entities in return strings.
	 *
	 * The case of all the field names is converted to lower case.
	 * as with the other methods.
	 *
	 * @param &$result The query result to fetch from.
	 * @param $row The row number to fetch. It's default value is 0
	 *
	 */
	function raw_query_result_rowdata(&$result, $row=0) {
		if (!is_object($result))
                throw new Exception("result is not an object");
		$result->Move($row);
		$rowdata = $this->change_key_case($result->FetchRow());
		return $rowdata;
	}



    function getAffectedRowCount(&$result){
		global $log;
		$log->debug('getAffectedRowCount');
		$rows =$this->database->Affected_Rows();
		$log->debug('getAffectedRowCount rows = '.$rows);
		return $rows;
    }

    function requireSingleResult($sql, $dieOnError=false,$msg='', $encode=true) {
		$result = $this->query($sql, $dieOnError, $msg);

		if($this->getRowCount($result ) == 1)
	    	return $result;
		$this->log->error('Rows Returned:'. $this->getRowCount($result) .' More than 1 row returned for '. $sql);
		return '';
    }
	/* function which extends requireSingleResult api to execute prepared statment
	 */

    function requirePsSingleResult($sql, $params, $dieOnError=false,$msg='', $encode=true) {
		$result = $this->pquery($sql, $params, $dieOnError, $msg);

		if($this->getRowCount($result ) == 1)
	    	return $result;
		$this->log->error('Rows Returned:'. $this->getRowCount($result) .' More than 1 row returned for '. $sql);
		return '';
    }

    function fetchByAssoc(&$result, $rowNum = -1, $encode=true) {
		if($result->EOF) {
		    $this->println("ADODB fetchByAssoc return null");
		    return NULL;
		}
		if(isset($result) && $rowNum < 0) {
		    $row = $this->change_key_case($result->GetRowAssoc(false));
		    $result->MoveNext();
		    if($encode&& is_array($row))
				return array_map('to_html', $row);
		    return $row;
		}

		if($this->getRowCount($result) > $rowNum) {
		    $result->Move($rowNum);
		}
		$this->lastmysqlrow = $rowNum;
		$row = $this->change_key_case($result->GetRowAssoc(false));
		$result->MoveNext();
		$this->println($row);

		if($encode&& is_array($row))
			return array_map('to_html', $row);
		return $row;
    }

    function getNextRow(&$result, $encode=true){
		global $log;
		$log->info('getNextRow');
		if(isset($result)){
	    	$row = $this->change_key_case($result->FetchRow());
		    if($row && $encode&& is_array($row))
				return array_map('to_html', $row);
	    	return $row;
		}
		return null;
    }

    function fetch_row(&$result, $encode=true) {
		return $this->getNextRow($result);
    }

    function field_name(&$result, $col) {
		return $result->FetchField($col);
    }

    function getQueryTime(){
		return $this->query_time;
    }

    function connect($dieOnError = false) {
    		global $dbconfigoption,$dbconfig;
		if(!isset($this->dbType)) {
		    $this->println("ADODB Connect : DBType not specified");
		    return;
		}
		$this->database = ADONewConnection($this->dbType);
		//crmv@fix charset
		$this->database->charSet = $this->dbCharset; // crmv@124454
		//crmv@fix charset end
		//crmv@fix assoc
		$this->database->SetFetchMode(ADODB_FETCH_DEFAULT);
		//crmv@fix assoc end
		//crmv@datadict add
		$this->datadict = NewDataDictionary($this->database);
		//crmv@datadict add end
		
		// crmv@193619
		if (!empty($this->clientFlags)) {
			$this->database->clientFlags = $this->clientFlags;
		}
		// crmv@193619e
		
		// crmv@65455
		if ($this->usePersistent) {
			$this->database->PConnect($this->dbHostName, $this->userName, $this->userPassword, $this->dbName);
		} else {
			$this->database->Connect($this->dbHostName, $this->userName, $this->userPassword, $this->dbName);
		}
		// crmv@65455e
		$this->database->LogSQL($this->enableSQLlog);
		// 'SET NAMES UTF8' needs to be executed even if database has default CHARSET UTF8
		// as mysql server might be running with different charset!
		// We will notice problem reading UTF8 characters otherwise.
		if($this->isdb_default_utf8_charset) {
			//crmv@fix charset mysql
			if ($this->isMySQL())
				$this->executeSetNamesUTF8SQL(true);
			//crmv@fix charset mysql end
		}
		// crmv@146653
		// executes special initialization for different DBs
		if ($this->isMySQL()) {
			$this->database->Execute("SET SESSION sql_mode = ''"); // disable sql mode
		} elseif ($this->isMssql()) {
			$this->database->Execute("SET DATEFORMAT 'Ymd'");
			// crmv@155585
			if ($this->dbType != 'mssqlnative') {
				$this->database->Execute("SET TEXTSIZE 1000000"); // crmv@129940
			}
			// crmv@155585e
		}
		// crmv@146653e
		//crmv@db fix
		$this->avoidPreparedSql = true;
		//crmv@db fix end
		//crmv@db config switch
		if ($dbconfig['db_dieOnError'] === true){
			$this->dieOnError = true;
		}
		//crmv@db config switch end
    }

	/**
	 * Constructor
	 */
	function __construct($dbtype='',$host='',$dbname='',$username='',$passwd='', $charset='') { // crmv@124454
    	global $currentModule;
		$this->log = LoggerManager::getLogger('PearDatabase_'. $currentModule);
		$this->resetSettings($dbtype,$host,$dbname,$username,$passwd,$charset); // crmv@124454

		// Initialize performance parameters
		$this->isdb_default_utf8_charset = PerformancePrefs::getBoolean('DB_DEFAULT_CHARSET_UTF8');
		$this->enableCache = PerformancePrefs::getBoolean('CACHE_QUERY_RESULT', false);
		// END

	if(!isset($this->dbType))
	{
	    $this->println("ADODB Connect : DBType not specified");
	    return;
	}
		// Initialize the cache object to use.
		if(isset($this->enableCache) && $this->enableCache) {
			$this->__setCacheInstance(new PearDatabaseCache($this));
		}
		// END
    }

    function resetSettings($dbtype,$host,$dbname,$username,$passwd, $charset = ''){ // crmv@124454
		global $dbconfig, $dbconfigoption;

		if($host == '') {
		    $this->disconnect();
		    $this->setDatabaseType($dbconfig['db_type']);
	    	$this->setUserName($dbconfig['db_username']);
		    $this->setUserPassword($dbconfig['db_password']);
		    //crmv@56443
			if($this->dbType == 'mysqli' || $this->dbType == 'mssqlnative'){ // crmv@155585
				$this->setDatabaseHost( $dbconfig['db_server']);
			} else {
				$this->setDatabaseHost( $dbconfig['db_hostname']);
			}
			//crmv@56443e
	    	$this->setDatabaseName($dbconfig['db_name']);
		    $this->dbOptions = $dbconfigoption;
		    if($dbconfig['log_sql'])
	    		$this->enableSQLlog = ($dbconfig['log_sql'] == true);
		} else {
		    $this->disconnect();
		    $this->setDatabaseType($dbtype);
	    	$this->setDatabaseName($dbname);
		    $this->setUserName($username);
		    $this->setUserPassword($passwd);
	    	$this->setDatabaseHost( $host);
		}
		// crmv@124454
		if ($charset) {
			$this->setCharset($charset);
		} elseif (isset($dbconfig['db_charset'])) {
			$this->setCharset($dbconfig['db_charset']);
		}
		// crmv@124454e
    }

    function quote($string){
		return $this->database->qstr($string);
    }

    //crmv@offline
    function disconnect() {
    	$this->println("ADODB disconnect");
    	if(isset($this->database)){
    		$this->database->disconnect();
    		unset($this->database);
    	}
    }
    //crmv@offline end

    function setDebug($value) {
		$this->database->debug = $value;
    }

    // ADODB newly added methods
    function createTables($schemaFile, $dbHostName=false, $userName=false, $userPassword=false, $dbName=false, $dbType=false) {
		$this->println("ADODB createTables ".$schemaFile);
		if($dbHostName!=false) $this->dbHostName=$dbHostName;
		if($userName!=false) $this->userName=$userPassword;
		if($userPassword!=false) $this->userPassword=$userPassword;
		if($dbName!=false) $this->dbName=$dbName;
		if($dbType!=false) $this->dbType=$dbType;

		$this->checkConnection();
		$db = $this->database;
		$schema = new adoSchema( $db );
		//Debug Adodb XML Schema
		$schema->XMLS_DEBUG = TRUE;
		//Debug Adodb
		$schema->debug = true;
		$sql = $schema->ParseSchema( $schemaFile );

		$this->println("--------------Starting the table creation------------------");
		$result = $schema->ExecuteSchema( $sql, $this->continueInstallOnError );
		if($result) print $db->errorMsg();
		// needs to return in a decent way
		$this->println("ADODB createTables ".$schemaFile." status=".$result);
		return $result;
    }

    function createTable($tablename, $flds) {
		$this->println("ADODB createTable table=".$tablename." flds=".$flds);
		$this->checkConnection();
		$dict = NewDataDictionary($this->database);
		$sqlarray = $dict->CreateTableSQL($tablename, $flds);
		$result = $dict->ExecuteSQLArray($sqlarray);
		$this->println("ADODB createTable table=".$tablename." flds=".$flds." status=".$result);
		return $result;
    }

    function alterTable($tablename, $flds, $oper, $printError = true) { // crmv@150747
		$this->println("ADODB alterTableTable table=".$tablename." flds=".$flds." oper=".$oper);
		$this->checkConnection();
		$dict = NewDataDictionary($this->database);

		if($oper == 'Add_Column') {
		    $sqlarray = $dict->AddColumnSQL($tablename, $flds);
		} else if($oper == 'Delete_Column') {
		    $sqlarray = $dict->DropColumnSQL($tablename, $flds);
		}
		$this->println("sqlarray");
		$this->println($sqlarray);

		$result = $dict->ExecuteSQLArray($sqlarray, true, $printError); // crmv@150747

		$this->println("ADODB alterTableTable table=".$tablename." flds=".$flds." oper=".$oper." status=".$result);
		return $result;
    }

    function addColumnToTable($tablename, $columnname, $type, $extra = '') {
    	// check if already present
    	$cols = $this->getColumnNames($tablename);
    	if (in_array($columnname, $cols)) {
    		return;
    	}
    	$col = $columnname.' '.$type.' '.$extra;
    	$this->alterTable($tablename, $col, 'Add_Column');
    }

	function getColumnNames($tablename) {
		$this->println("ADODB getColumnNames table=".$tablename);
		$this->checkConnection();
		//crmv@19893
		if ($this->table_exist($tablename) == 0)
			return Array();
		//crmv@19893
		$adoflds = $this->database->MetaColumns($tablename);
		$i=0;
		foreach($adoflds as $fld) {
		    $colNames[$i] = $fld->name;
		    $i++;
		}
		return $colNames;
    }

    function formatString($tablename,$fldname, $str) {
		$this->checkConnection();
		$adoflds = $this->database->MetaColumns($tablename);

		foreach ( $adoflds as $fld ) {
		    if(strcasecmp($fld->name,$fldname)==0) {
				$fldtype =strtoupper($fld->type);
				if(strcmp($fldtype,'CHAR')==0 || strcmp($fldtype,'VARCHAR') == 0 || strcmp($fldtype,'VARCHAR2') == 0 || strcmp($fldtype,'LONGTEXT')==0 || strcmp($fldtype,'TEXT')==0) {
				    return $this->database->Quote($str);
				} else if(strcmp($fldtype,'DATE') ==0 || strcmp($fldtype,'TIMESTAMP')==0) {
				    return $this->formatDate($str);
				} else {
				    return $str;
				}
		    }
		}
		$this->println("format String Illegal field name ".$fldname);
		return $str;
    }

    function formatDate($datetime, $strip_quotes=false) {
		$this->checkConnection();
		$db = &$this->database;
		//crmv@fix datetime
		if (trim($datetime) == ''){
			$datetime = null;
		}
		//crmv@fix datetime end
		/* Asha: Stripping single quotes to use the date as parameter for Prepared statement */
		//crmv@fix date null
		if($strip_quotes == true && $date !== null) {
			return trim($datetime, "'");
		}
		//crmv@fix date null end
		return $datetime;
    }

    function getDBDateString($datecolname) {
		$this->checkConnection();
		$db = &$this->database;
		//crmv@date fix
		$datestr = $db->SQLDate("Y-m-d H:i:s" ,$datecolname);
		//crmv@date fix end
		return $datestr;
    }

    function getUniqueID($seqname) {
		$this->checkConnection();
		return $this->database->GenID($seqname."_seq",1);
	}

	/**
	 * Optimized function to generate multiple ids at once.
	 * Returns an array of ids (usually sequential, but this depends
	 * on the database type) to be used for the INSERT queries.
	 * Returns false if any of the id could not be generated.
	 */
	function getMultiUniqueID($seqname, $length) {
		$list = array();
		if ($length == 0) return $list;
		
		// first iteration is standard, to be sure the table is created
		// this also calls the checkConnection
		$list[] = $this->getUniqueID($seqname);
		
		// check if some error occurred
		if ($list[0] === false) return false; // crmv@155585
		
		// crmv@155585
		if ($list[0] === 0) {
			$list[0] = $this->getUniqueID($seqname);
		}
		// crmv@155585e
		
		// return immediately if no more ids are required
		if ($length == 1) return $list;
		
		$table = $seqname.'_seq';
		$inc = intval($length-1);
		
		// disable log
		$savelog = $this->database->_logsql;
		$this->database->_logsql = false;
		
		$connid = $this->database->_connectionID;
		
		// now do some optimized calls, according to the db type
		if ($this->isMysql()) {
			$table = str_replace('.', '`.`', $table); //crmv@185894
			$rs = @$this->database->Execute("UPDATE `{$table}` SET id = LAST_INSERT_ID(id+{$inc})");
			if ($rs) {
				if ($this->dbType == 'mysqli') {
					$lastid = mysqli_insert_id($connid);
				} else {
					$lastid = mysql_insert_id($connid);
				}
				$rs->Close();
			} else {
				$lastid = 0;
			}
			if ($lastid) {
				// I'm sure the ids are sequential, because I issued one single query
				$list = array_merge($list, range($lastid-$inc+1, $lastid));
			} else {
				$list = false;
			}
		} elseif ($this->isMssql()) {
			if ($this->dbType == 'mssqlnative') {
				sqlsrv_begin_transaction($connid);
				$rs = @$this->database->Execute("UPDATE {$table} WITH (tablock,holdlock) SET id = id + {$inc}");
				if ($rs) {
					$lastid = $this->database->GetOne("SELECT id FROM {$table}");
					sqlsrv_commit($connid);
				} else {
					$lastid = 0;
					sqlsrv_rollback($connid);
				}
			} else {
				$this->database->Execute('BEGIN TRANSACTION adodbseq');
				$rs = @$this->database->Execute("UPDATE {$table} WITH (tablock,holdlock) SET id = id + {$inc}");
				if ($rs) {
					$lastid = $this->database->GetOne("SELECT id FROM {$table}");
					$this->database->Execute('COMMIT TRANSACTION adodbseq');
				} else {
					$lastid = 0;
					$this->database->Execute('ROLLBACK TRANSACTION adodbseq');
				}
			}
			if ($lastid) {
				// I'm sure the ids are sequential, because I issued one single query
				$list = array_merge($list, range($lastid-$inc+1, $lastid));
			} else {
				$list = false;
			}
		//} elseif ($this->isOracle()) {
			// oracle requires some complicated stuff, like connect by level ...
			// better to stick with the standard loop
		} else {
			// standard fallback
			for ($i=1; $i<$length; $i++) {
				$id = $this->getUniqueID($seqname);
				if ($id) {
					$list[] = $id;
				} else {
					$list = false;
					break;
				}
			}
		}
		
		// restore the log
		$this->_logsql = $savelog;
				
		return $list;
	}

    function get_tables() {
		$this->checkConnection();
		$result = & $this->database->MetaTables('TABLES');
		$this->println($result);
		return $result;
    }

	//To get a function name with respect to the database type which escapes strings in given text
	function sql_escape_string($str)
	{
		//crmv@fix escape string
		if($this->isPostgres())
			$result_data = pg_escape_string($str);
		elseif($this->isMysql())
			//crmv@56443 crmv@89444
			if (empty($this->database->_connectionID)) {
				$result_data = $this->mysql_escape_mimic($str);
			} else {
				if($this->dbType == 'mysqli') {
					$result_data = mysqli_real_escape_string($this->database->_connectionID,$str);
				} else {
					$result_data = mysql_real_escape_string($str, $this->database->_connectionID);	//crmv@56973
				}
			}
			//crmv@56443e crmv@89444e
		elseif ($this->isMssql() || $this->isOracle()){
			$result_data = str_replace("'", "''", $str);
		}
		else{
			$result_data = str_replace("'", "\'", $str);
		}
		return $result_data;
		//crmv@fix escape string end
	}

	//crmv@89444
	function mysql_escape_mimic($inp) { 
		if(is_array($inp)) 
			return array_map(__METHOD__, $inp); 

		if(!empty($inp) && is_string($inp)) { 
			return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
		} 

		return $inp; 
	}
	//crmv@89444e

	// Function to get the last insert id based on the type of database
	function getLastInsertID($seqname = '') {
		if($this->isPostgres()) {
			$result = pg_query("SELECT currval('".$seqname."_seq')");
			if($result)
			{
				$row = pg_fetch_row($result);
				$last_insert_id = $row[0];
			}
		} else {
			$last_insert_id = $this->database->Insert_ID();
		}
		return $last_insert_id;
	}
	// Function to escape the special characters in database name based on database type.
	function escapeDbName($dbName='') {
		$dbName = $this->dbName;
		if($this->isMySql()) {
			$dbName = "`{$dbName}`";
		}
		return $dbName;
	}
	//crmv@add functions
	function format_columns(&$columns, $force = false){ // crmv@165479
		if ($this->isMssql()){
			//crmv@27811
			if (is_array($columns))
				array_walk_recursive($columns,'add_brakets');
			else {
				$columns = "[$columns]";
			}
			//crmv@27811e
		}
		elseif ($this->isOracle()){
			//crmv@24791 crmv@165479
			if (is_array($columns))
				array_walk_recursive($columns,'add_doublequotes', $force);
			elseif ($force || in_array($columns,getOracleReservedWords()))
				$columns = '"'.$columns.'"';
			//crmv@24791e crmv@165479e
		}
		//crmv@26687
		elseif ($this->isMySql()){
			if (is_array($columns))
				array_walk_recursive($columns,'add_backtick');
			else
				$columns = "`{$columns}`";
		}
		//crmv@26687e
	}
	
	// crmv@165801
	/**
	 * like format_columns, but work on a single column only
	 */
	function format_column($column, $force = false) {
		$this->format_columns($column, $force);
		return $column;
	}
	// crmv@165801e
	
	//crmv@78573
	/**
	 * Check if a temporary table exists
	 */
	function temp_table_exist($table) {
		if ($this->isMysql()) {
			//crmv@33979
			// this is the only safe way in mysql to check the existance of a temporary table
			$rs = $this->database->Execute("SELECT NULL FROM $table");
			if ($rs){
				return true;
			} else {
				//cancello la tabella temporanea così devo ricrearla
				// ma per quale cagione dovrei fare codesto barbatrucco?
				//$rs = $this->Execute("drop temporary table if exists $table");
			}
			//crmv@33979e
		} elseif ($this->isMssql()) {
			$sql = "SELECT OBJECT_ID('tempdb..#$table') AS id";
			return !!$this->GetOne($sql);
		}
		return false;
	}
	//crmv@78573e
	
	// crmv@99131
	function table_exist($tablename,$temp = false, $skipCache = false){
		if (($this->isMssql() || $this->isMysql()) && $temp) {	//crmv@21249
			return $this->temp_table_exist($tablename); // crmv@148761
		} else {
			//crmv@47905bis
			//return count($this->database->MetaTables('TABLES',false,$tablename));
			if ($skipCache) {
				$tables = array_flip($this->database->MetaTables('TABLES'));
			} else {
				$cache = Cache::getInstance('table_exist');
				$tables = $cache->get();
				if ($tables === false) {
					$tables = array_flip($this->database->MetaTables('TABLES'));
					$cache->set($tables);
				}			
			}
	 		(isset($tables[$tablename])) ? $return = 1 : $return = 0;
	 		return $return;
	 		//crmv@47905bis e
		}
	}
	// crmv@99131e

	// crmv@103023 crmv@113804
	/**
	 * Often, when using conditions in OR, for example (WHERE field1 = 'a' OR field2 = 'b'),
	 * performances are poor because the db engine cannot use indexes for each field.
	 * Using 2 queries and joining the results with union sometimes gives better speed.
	 * This function takes a sql select statement and an array of conditions (which were
	 * orignially in OR in the query) and generates a new sql statement using unions.
	 * If you are sure that the conditions generate non overlapping sets, specify false
	 * in the 3rd parameter to skip the standard union merge.
	 */
	function makeUnionSelect($sql, $conditions, $distinct = true, &$params = array(), $cloneTables = array()) {
		
		if (empty($conditions)) return $sql;
		
		$repTables = array();
		if (count($cloneTables) > 0 && count($conditions) > 1) {
			foreach ($cloneTables as $table) {
				for ($i=2; $i<=count($conditions); ++$i) {
					$newname = $table.'_'.$i;
					$this->cloneTemporaryTable($table, $newname);
					$repTables[$i-1][$table] = $newname;
				}
			}
		}
		
		$i = 0;
		$newsql = array();
		foreach ($conditions as $cond) {
			$addSql = null;
			if (is_string($cond)) {
				// simple string condition
				$addSql = $sql . " AND $cond\n";
			} elseif (is_array($cond)) {
				// sql and array of parameers
				$addSql = $sql . $this->convert2Sql(" AND {$cond[0]}\n",$this->flatten_array($cond[1]));
			}
			if ($addSql) {
				if (is_array($repTables[$i])) {
					$addSql = str_replace(array_keys($repTables[$i]), array_values($repTables[$i]), $addSql);
				}
				$newsql[] = $addSql;
			}
			++$i;
		}
		
		if ($params && count($params) > 0) {
			// I have to duplicate the params too
			$params = $this->flatten_array(array_fill(0, count($conditions), $params));
		}
		
		$join = $distinct ? "UNION" : "UNION ALL";
		
		return implode(" $join \n", $newsql);
	}
	// crmv@103023e
	
	/**
	 * Clone a temporary table. This method only works for MySql
	 */
	function cloneTemporaryTable($table, $newtable) {
		$this->query("CREATE TEMPORARY TABLE IF NOT EXISTS $newtable LIKE $table");
		$this->query("TRUNCATE TABLE $newtable");
		$this->query("INSERT INTO $newtable SELECT * FROM $table");
	}
	// crmv@113804e
	
	//crmv@add functions end
	//crmv@offline
	function get_sequences(){
		if ($this->isOracle()){
			$sql = "select sequence_name,last_number from user_sequences";
			$res = $this->query($sql);
			if ($res && $this->num_rows($res)>0){
				while($row = $this->fetchByAssoc($res,-1,false)){
					$sequences[substr($row[sequence_name],0,-4)] = $row[last_number];
				}
			}
		}
		else {
			$tables_seq = $this->database->Metatables(false,false,"%_seq");
			$sql = "select id from ";
			if (is_array($tables_seq)){
				foreach ($tables_seq as $table){
					$id = $this->query_result_no_html($this->query($sql.$table),0,'id');
					$sequences[substr($table,0,-4)] = $id;
				}
			}
		}
		return $sequences;
	}
	//crmv@offline end

	// crmv@90924
	function getPartitions($table) {
		$parts = array();
		if ($this->isMysql()) {	
			$sql = "
				SELECT partition_name, table_rows, partition_expression, partition_description
				FROM INFORMATION_SCHEMA.PARTITIONS
				WHERE table_schema = ? AND table_name = ? AND partition_name IS NOT NULL";
			$res = $this->pquery($sql, array($this->dbName, $table));
			if ($res && $this->num_rows($res) > 0) {
				while ($row = $this->fetchByAssoc($res, -1, false)) {
					$parts[] = $row;
				}
			}
			return $parts;
		} else {
			// TODO: other databases
			throw new Exception("Database type not supported");
		}
	}
	
	function hasPartition($table, $partName) {
		$parts = $this->getPartitions($table);
		foreach ($parts as $pinfo) {
			if ($pinfo['partition_name'] == $partName) return true;
		}
		return false;
	}
	// crmv@90924e
	
	// crmv@197204
	/**
	 * Return possible values for each partition
	 */
	function getPartitionsValues($table) {
		$parts = $this->getPartitions($table);
		$values = array();
		foreach ($parts as $pinfo) {
			$partitionName = $pinfo['partition_name'];
			if (!isset($values[$partitionName])) {
				$values[$partitionName] = array();
			}
			$partitionDesc = $pinfo['partition_description'];
			if (!empty($partitionDesc)) {
				$setypes = array_map(function($v) {
					return trim(trim($v, "'"));
				}, explode(',', $partitionDesc));
				$values[$partitionName] = array_merge($values[$partitionName], $setypes);
			}
		}
		return $values;
	}
	
	/**
	 * Return true if the specified value is in any partition
	 */
	function checkPartitionValue($table, $value) {
		$patitionsValues = $this->getPartitionsValues($table);
		if (!empty($patitionsValues)) {
			$allValues = $this->flatten_array($patitionsValues);
			return in_array($value, $allValues);
		}
		return true;
	}
	// crmv@197204e
	
	// crmv@185894
	function getSlaveObject(string $slaveFunction) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				return $slaveHandler->getPearDatabaseObject();
			}
		}
		return $this;
	}
	function ErrorMsgSlave(string $slaveFunction) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				return $adbSlave->database->ErrorMsg();
			}
		}
		return $this->database->ErrorMsg();
	}
	function getUniqueIDSlave(string $slaveFunction, $seqname) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				return $adbSlave->getUniqueID($seqname);
			}
		}
		return $this->getUniqueID($seqname);
	}
	function getMultiUniqueIDSlave(string $slaveFunction, $seqname, $length) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				return $adbSlave->getMultiUniqueID($seqname, $length);
			}
		}
		return $this->getMultiUniqueID($seqname, $length);
	}
	function bulkInsertSlave(string $slaveFunction, $table, $columns, $rows = array(), $chunkSize = 100, $ignore = false) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				return $adbSlave->bulkInsert($table, $columns, $rows, $chunkSize, $ignore);
			}
		}
		return $this->bulkInsert($table, $columns, $rows, $chunkSize, $ignore);
	}
	function DropTableSQLSlave(string $slaveFunction, $tabname, $schema='') {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				if (!empty($schema)) $adbSlave->datadict->schema = $schema;
				$return = $adbSlave->datadict->DropTableSQL($tabname);
				if (!empty($schema)) $adbSlave->datadict->schema = '';
				return $return;
			}
		}
		return $this->datadict->DropTableSQL($tabname);
	}
	function CreateIndexSQLSlave(string $slaveFunction, $idxname, $tabname, $flds, $idxoptions = false) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				return $adbSlave->datadict->CreateIndexSQL($idxname, $tabname, $flds, $idxoptions);
			}
		}
		return $this->datadict->CreateIndexSQL($idxname, $tabname, $flds, $idxoptions);
	}
	function ExecuteSQLArraySlave(string $slaveFunction, $sql, $continueOnError = true, $printError = true) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				global $globSkipClearCache;
				$globSkipClearCache = true;
				$result = $adbSlave->datadict->ExecuteSQLArray($sql, $continueOnError, $printError);
				$globSkipClearCache = false;
				return $result;
			}
		}
		return $this->datadict->ExecuteSQLArray($sql, $continueOnError, $printError);
	}
	function querySlave(string $slaveFunction, string $sql, bool $dieOnError=false, string $msg='', bool $temp=false) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				$adbSlave->slave = true;
				$result = $adbSlave->pquery($sql, $dieOnError, $msg, $temp);
				$adbSlave->slave = false;
				return $result;
			}
		}
		// se sla_handler disattivo, funzione non supportata o connessione assente vado sul db master
		return $this->pquery($sql, $dieOnError, $msg, $temp);
	}
	function pquerySlave(string $slaveFunction, string $sql, array $params, bool $dieOnError=false, string $msg='', bool $temp=false) {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				$adbSlave->slave = true;
				$result = $adbSlave->pquery($sql, $params, $dieOnError, $msg, $temp);
				$adbSlave->slave = false;
				return $result;
			}
		}
		return $this->pquery($sql, $params, $dieOnError, $msg, $temp);
	}
	function limitQuerySlave(string $slaveFunction, string $sql, int $start, int $count, bool $dieOnError=false, string $msg='') {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				$adbSlave->slave = true;
				$result = $adbSlave->limitQuery($sql, $start, $count, $dieOnError, $msg);
				$adbSlave->slave = false;
				return $result;
			}
		}
		return $this->limitQuery($sql, $start, $count, $dieOnError, $msg);
	}
	function limitpQuerySlave(string $slaveFunction, string $sql, int $start, int $count, array $params=array(), bool $dieOnError=false, string $msg='') {
		$slaveHandler = SlaveHandler::getInstance();
		if ($slaveHandler->isActive($slaveFunction)) {
			$success = $slaveHandler->checkDatabaseConnection();
			if ($success) {
				$adbSlave = $slaveHandler->getPearDatabaseObject();
				$adbSlave->slave = true;
				$result = $adbSlave->limitpQuery($sql, $start, $count, $params, $dieOnError, $msg);
				$adbSlave->slave = false;
				return $result;
			}
		}
		return $this->limitpQuery($sql, $start, $count, $params, $dieOnError, $msg);
	}
	// crmv@185894e
	
	// crmv@198024
	/**
	 * Return true if the database supports json functions
	 */
	public function supportsJSON() {
		$info = $this->database->ServerInfo();
		if ($this->isMysql()) {
			return version_compare($info['version'], '5.7.8', '>=');
		} elseif ($this->isMssql()) {
			return version_compare($info['version'], '13.0', '>=');
		} elseif ($this->isOracle()) {
			return version_compare($info['version'], '12.1.0.2', '>=');
		}
		return false;
	}
	
	/**
	 * Generate an sql expression to extract values from a json-encoded column or null if not a valid json
	 * Only mysql has been tested
	 */
	public function jsonValue($column, $path, $emptyCheck = true) {
		if ($this->isMysql()) {
			$sqlcolumn = "JSON_UNQUOTE(JSON_EXTRACT($column, '{$path}'))";
		} elseif ($this->isOracle() || $this->isMsSql()) {
			// not tested!
			$sqlcolumn = "JSON_VALUE($column, '{$path}')";
		}
		
		if ($emptyCheck) {
			$sqlcolumn = "IF($column IS NULL OR $column = '', '', $sqlcolumn)";
		}
		
		return $sqlcolumn;
	}
	// crmv@198024

} /* End of class */
if(empty($adb)) {
	$adb = new PearDatabase();
	$adb->connect();
}
