<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 crmv@97206 crmv@100905 crmv@100399 crmv@103881 */

require_once("modules/Reports/Reports.php");
require_once("modules/Reports/ConditionsTree.php");
require_once("modules/Reports/FakeQueryGenerator.php"); // crmv@127526
require_once('modules/Reports/output/OutputArray.php');
require_once('modules/Reports/output/OutputJson.php');
require_once('modules/Reports/output/OutputNull.php');
require_once('modules/Reports/output/OutputHTML.php');
require_once('modules/Reports/output/OutputHTMLDirect.php');

class ReportException extends Exception {

}

class ReportRun extends SDKExtendableClass {

	public $reporttype;
	public $reportname;
	
	public $primaryModule = null;
	public $secondaryModules = array();
	
	public $no_group_levels; 		// crmv@81019
	public $pageSize = 50;				// pagination size
	
	// query optimization flags
	public $subqueryMaterialization = true;	// true to materialize subquery into "temporary" tables
	public $pushdownConditions = true;		// true to push down conditions (where applicable) to the subqueries
	public $subqueryParentCondition = true; // true to enable an optimization for secondary materialized subqueries (crmv@146138)
	
	// config for the report generation
	protected $outputFormat = "HTML";	// one of "HTML", "PRINT", "XLS", "JSON", "NULL"
	protected $directOutput = false;	// if true, the output is emitted directly (valid only with html format)
	protected $reportTab = "MAIN";		// what to output: "COUNT", "MAIN", "TOTAL", "CV"

	protected $groupingLevels = 0;
	protected $summaryLevels = 0;
	
	protected $queryLimit = null;		// limits used for pagination
	protected $queryOrdering = null;	// additional columns to sort by (if report is summary, the main grouping always order by thos columns)
	
	protected $querySearchCols = null;
	protected $querySearch = null;
	
	protected $cvInfo = null;			// data used when calling the report from the custom view
	
	protected $reuseMaterialized = false;
	
	// list of fields that have currency appended to value, and therefore are not converted into the default currency
	protected $append_currency_symbol_to_value_mods = array();

	protected $outputClass = array();
	
	
	private $accessible_cache = array();
	private $prepared_cache = array();
	
	private $generatedQuery = array();
	
	// crmv@185894
	public $cacheDb = ''; // used in Reports
	private $forceCacheDb = false;
	// crmv@185894e

	/** 
	 * Constructor for the ReportRun class. If a reportid is provided,
	 * it loads the informations of the report.
	 */
	public function __construct($reportid = null) {
		$this->reports = Reports::getInstance($reportid);
		$this->reportid = $reportid;
		$this->no_group_levels = $this->reports->max_grouping_levels;
		$this->db_functions = $this->reports->db_functions; // crmv@38798
		
		// fill the append currency symbol array
		$prodMods = getProductModules();
		foreach ($prodMods as $prodMod) {
			$this->append_currency_symbol_to_value_mods[$prodMod] = array('unit_price');
		}
		$invMods = getInventoryModules();
		foreach ($invMods as $prodMod) {
			$this->append_currency_symbol_to_value_mods[$prodMod] = array('hdnGrandTotal', 'hdnSubTotal', 'hdnS_H_Amount', 'hdnDiscountAmount', 'txtAdjustment');
		}
		$this->append_currency_symbol_to_value_mods['ProductsBlock'] = array('listprice', 'linetotal', 'discount');
		
		// retrieve the report info
		if ($reportid > 0) {
			$this->retrieveReportInfo();
		}
	}
	
	public function setReportId($reportid) {
		$this->reports = Reports::getInstance($reportid);
		$this->reportid = $reportid;
		$this->retrieveReportInfo(true);
	}
	
	public function setStdFilterFromRequest(&$request) {
		$field = Zend_Json::decode($request["stdDateFilterField"]);
		$filter = vtlib_purify($request["stdDateFilter"]);
		$startdate = getDBInsertDateValue($request["startdate"]);//Convert the user date format to DB date format
		$enddate = getDBInsertDateValue($request["enddate"]);//Convert the user date format to DB date format
		
		if ($field && $filter && $startdate && $enddate && $startdate != '0000-00-00' && $enddate != '0000-00-00') {
			$stdfilter = array(
				'type' => 'datefilter',
				'fieldid' => intval($field['fieldid']),
				'value' => $filter,
			);
			$finfo = $this->reports->getFieldInfoById($field['fieldid']);
			$fieldrels = $this->reports->getRelationsFromChain($field['chain']);
			if (count($fieldrels) > 0) { // crmv@143750
				$lastrel = end($fieldrels);
				$stdfilter['relation'] = $lastrel['name'];
			}
			if ($filter == 'custom') {
				$stdfilter['startdate'] = $startdate;
				$stdfilter['enddate'] = $enddate;
			}
			$this->stdfilters = array($stdfilter);
		// crmv@148966
		} elseif ($filter == 'custom' && empty($startdate) && empty($enddate)) {
			$this->stdfilters = array();
		}
		// crmv@148966e

		return $this->stdfilters;
	}
	
	public function isCalendarModule($module) {
		return in_array($module, array('Calendar', 'Events'));
	}
	
	// crmv@38798
	// applica una funzione ad una colonna (simple column, no alias)
	function apply_function($column, $funcname) {
		global $adb;

		if (!array_key_exists($funcname, $this->db_functions)) return $column;

		if ($adb->isMysql()) {
			$dbkey = 'mysql';
		} elseif ($adb->isMssql()) {
			$dbkey = 'mssql';
		} elseif ($adb->isOracle()) {
			$dbkey = 'oracle';
		} else {
			// skip filters
			return $column;
		}

		// get the sql template
		$sqltemplate = $this->db_functions[$funcname]['sql'][$dbkey];
		if (empty($sqltemplate)) return $column;

		// sostituisco i parametri
		$outsql = str_replace('{column}', $column, $sqltemplate);
		// add parameters here if needed
		//$outsql = str_replace('{param1}', 'PARAM1', $outsql);
		//$outsql = str_replace('{param2}', 'PARAM2', $outsql);
		
		return $outsql;
	}
	// crmv@38798e

		
	// crmv@107467
	/** Function to get field columns based on profile
	 *  @ param $module : Type string
	 *  returns permitted fields in array format
	 */
	public function getPermittedFields($module) {
		global $current_user;
		global $adb,$table_prefix;

		if (!$this->accessible_cache[$module]) {
		
			$access_fields = Array();
			$profileList = getCurrentUserProfileList();
			
			$params = array();
			$query = "SELECT {$table_prefix}_field.fieldid, {$table_prefix}_field.fieldname
				FROM {$table_prefix}_field 
				INNER JOIN {$table_prefix}_def_org_field ON ".$table_prefix."_def_org_field.fieldid = {$table_prefix}_field.fieldid
				WHERE ";
			
			// compatibility fix, since old reports might have the "calendar" module set
			if ($module == "Calendar") {
				$query .= $table_prefix."_field.tabid IN (9,16)";
			} else {
				$query .= $table_prefix."_field.tabid = ?";
				$params[] = $this->reports->getTabid($module);
			}
			
			$query .= " AND ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_field.presence in (0,2) and ".$table_prefix."_field.readonly != 100 and ".$table_prefix."_def_org_field.visible=0
				AND EXISTS (
					SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
			if (count($profileList) > 0) {
				$query.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
				$params[] = $profileList;
			}
			$query .= " AND {$table_prefix}_profile2field.visible=0 )";

			$result = $adb->pquerySlave('Reports', $query, $params); // crmv@185894
			
			while ($row = $adb->FetchByAssoc($result, -1, false)) {
				$access_fields[$row['fieldid']] = $row;
			}
			
			$this->accessible_cache[$module] = $access_fields;
		}

		return $this->accessible_cache[$module];
	}
	
	/**
	 * Get permitted fields for admin user
	 */
	public function getAdminPermittedFields($module) {
		global $adb,$table_prefix;

		if (!$this->accessible_cache[$module]) {
		
			$access_fields = Array();
			
			$params = array();
			$query = "SELECT {$table_prefix}_field.fieldid, {$table_prefix}_field.fieldname
				FROM {$table_prefix}_field WHERE ";
			
			// compatibility fix, since old reports might have the "calendar" module set
			if ($module == "Calendar") {
				$query .= $table_prefix."_field.tabid IN (9,16)";
			} else {
				$query .= $table_prefix."_field.tabid = ?";
				$params[] = $this->reports->getTabid($module);
			}
			
			$query .= " AND ".$table_prefix."_field.displaytype in (1,2,3) and ".$table_prefix."_field.presence in (0,2) and ".$table_prefix."_field.readonly != 100";

			$result = $adb->pquerySlave('Reports',$query, $params); // crmv@185894
			
			while ($row = $adb->FetchByAssoc($result, -1, false)) {
				$access_fields[$row['fieldid']] = $row;
			}
			
			$this->accessible_cache[$module] = $access_fields;
		}

		return $this->accessible_cache[$module];
	}
	// crmv@107467e

	// simple wrapper
	protected function getFieldInfoById($fieldid) {
		return $this->reports->getFieldInfoById($fieldid);
	}
	
	// simple wrapper
	protected function getFieldInfoByName($module, $fieldname) {
		return $this->reports->getFieldInfoByName($module, $fieldname);
	}
	

	/** Function to get advanced comparator in query form for the given Comparator and value
	 *  @ param $comparator : Type String
	 *  @ param $value : Type String
	 *  returns the check query for the comparator
	 */
	function getAdvComparator(&$condition) {
		global $adb,$default_charset;
		
		$comparator = $condition['comparator'];
		$value = $condition['value'];
		$uitype = $condition['uitype'];
		$wstype = $condition['wstype'];
		$datatype = $condition['typeofdata'];
		
		$value = html_entity_decode(trim($value),ENT_QUOTES,$default_charset);

		if ($condition['reference']) {
			$is_field = true;
			$value = 'aaa';
			$condref = $condition['reference'];
		}
		
		if ($uitype==56 && !$is_field) {
			$value = str_replace(getTranslatedString("yes"),"1",str_replace("no","0",$value)); //crmv@51002
		}
		//crmv@33666
		if ($adb->isOracle()) {
			if ($wstype == 'date') {
				$value = "to_date('$value','YYYY-mm-dd')";
			} elseif ($wstype == 'datetime') {
				$value = "to_date('$value','YYYY-mm-dd HH24:Mi:SS')";
			}
		}
		//crmv@33666e

		if ($is_field) {
			//$value = $this->getFilterComparedField($value);
			$value = $condref['table'].'.'.$condref['alias'];
		}
		
		if ($comparator == "e") {
			if (trim($value) == "NULL") {
				$rtvalue = " IS NULL";
			} elseif(trim($value) != "") {
				$rtvalue = " = ".$adb->quote($value);
			} elseif(trim($value) == "" && $datatype == "V") {
				$rtvalue = " = ".$adb->quote($value);
			} else {
				$rtvalue = " = ''"; //crmv@33466
			}
		
		} elseif ($comparator == "n") {
			if (trim($value) == "NULL") {
				$rtvalue = " IS NOT NULL";
			} elseif(trim($value) != "") {
				$rtvalue = " <> ".$adb->quote($value);
			} elseif(trim($value) == "" && $datatype == "V") {
				$rtvalue = " <> ".$adb->quote($value);
			} else {
				$rtvalue = " <> ''"; //crmv@33466
			}
		
		} elseif ($comparator == "s") {
			$rtvalue = " like '". formatForSqlLike($value, 2,$is_field) ."'";
		
		} elseif ($comparator == "ew") {
			$rtvalue = " like '". formatForSqlLike($value, 1,$is_field) ."'";
			
		} elseif ($comparator == "c") {
			$rtvalue = " like '". formatForSqlLike($value,0,$is_field) ."'";
			
		} elseif ($comparator == "k") {
			$rtvalue = " not like '". formatForSqlLike($value,0,$is_field) ."'";
			
		} elseif ($comparator == "l") {
			$rtvalue = " < ".$adb->quote($value);
			
		} elseif ($comparator == "g") {
			$rtvalue = " > ".$adb->quote($value);
			
		} elseif ($comparator == "m") {
			$rtvalue = " <= ".$adb->quote($value);
			
		} elseif ($comparator == "h") {
			$rtvalue = " >= ".$adb->quote($value);
			
		} elseif($comparator == "b") {
			$rtvalue = " < ".$adb->quote($value);
			
		} elseif ($comparator == "a") {
			$rtvalue = " > ".$adb->quote($value);
			
		}
		
		// unquote if it's a field
		if ($is_field) {
			$rtvalue = str_replace("'","",$rtvalue);
			$rtvalue = str_replace("\\","",$rtvalue);
		}
		
		//crmv@33666
		if ($adb->isOracle()) {
			if ($wstype == 'date' || $wstype == 'datetime') {
				$rtvalue = str_replace("'to_date","to_date",$rtvalue);
				$rtvalue = str_replace(")'",")",$rtvalue);
				$rtvalue = str_replace("''","'",$rtvalue);				
			}
		}
		//crmv@33666e
		
		return $rtvalue;
	}
	
	function convertAdvSearchValue(&$condition) {
		global $current_user;

		//crmv@21198
		//$moduleFieldLabel = $selectedfields[2];
		//list($moduleName, $fieldLabel) = explode('_', $moduleFieldLabel, 2);
		$value = $condition['value'];
		$comparator = $condition['comparator'];
		
		$module = $condition['module'];
		$fieldname = $condition['fieldname'];
		$uitype = $condition['uitype'];
		$wstype = $condition['wstype'];
		
		//crmv@21198e

		if ($condition['reference']) {
			// do nothing
		
		// yes/no checkbox
		} elseif ($uitype == 56) {
			if(strcasecmp(trim($value),"yes")==0) {
				$value = "1";
			} elseif(strcasecmp(trim($value),"no")==0) {
				$value = "0";
			}
		//crmv@20889
		} elseif ($wstype == 'picklistmultilanguage' && !empty($value)) {
			if(is_string($value)) {
				$mp_valueArray = explode(',' , $value);
				foreach($mp_valueArray as $mp_key => $mp_val) {
					$mp_valueArray[$mp_key] = trim($mp_val);
				}
			} elseif(is_array($value)) {
				$mp_valueArray = $value;
			}else{
				$mp_valueArray = array($value);
			}
			$value = Picklistmulti::get_search_values($fieldname,$mp_valueArray,$comparator);
			if ($value[1] && $value[1] != $comparator) {
				$condition['comparator'] = $value[1];
			}
			$value = implode(',',$value[0]);
		//crmv@20889e
		//crmv@20630	crmv@52522
		} elseif ($wstype == 'picklist' || $wstype == 'multipicklist' && !empty($value)) {
			if(is_string($value)) {
				$value = explode(",",trim($value));
			}
			// crmv@127526
			if (FakeModules::isFakeModule($module)) {
				$queryGenerator = FakeQueryGenerator::getInstance($module, $current_user, $this, $this->reports);
				$wsfield = null; // crmv@166586
			} else {
				$queryGenerator = QueryGenerator::getInstance($module, $current_user); // crmv@31205
				$wsfield = $queryGenerator->getWSField($fieldname, false); // crmv@166586
			}
			// crmv@127526e
			foreach ($value as $val){
				// crmv@166586
				$all_trans = $queryGenerator->getReverseTranslate($val,$comparator, $wsfield, false);
				foreach($all_trans as $val_trans) {
					if ($val_trans != $val)
						$value[] = $val_trans;
				}
				// crmv@166586e
			}
			$value = implode(',',$value);
		}
		//crmv@20630e	crmv@52522e
		// crmv@118320
		elseif (($wstype == 'datetime' || in_array($uitype,array(5,6,23))) && !empty($value)) {
			if (isset($current_user->date_format)) {
				$value = getValidDBInsertDateValue($value);
			}
		}
		//crmv@118320e
		
		return trim($value);
	}
	
	function generateAdvSearchClause(&$condition) {
		global $adb, $table_prefix;

		$value = $condition['value'];
		$value2 = $condition['value2'];
		$comparator = $condition['comparator'];
		
		$module = $condition['module'];
		$fieldname = $condition['fieldname'];
		$table = $condition['table'];
		$column = $condition['alias'];
		
		$uitype = $condition['uitype'];
		$wstype = $condition['wstype'];
		$datatype = $condition['typeofdata'];
		
		// crmv@141878
		$sqlColumn = $condition['rawcolumn'] ?: $table.'.'.$column;
		$sqlColumnId = $condition['rawcolumn_id'] ?: $sqlColumn.'_id';
		// crmv@141878e
		
		if ($condition['reference']) $value = 'aaa';	// simulate a value, so we don't end up the empty case
		
		if ($comparator == 'bw') {
			$value .= ",".$value2;
		}

		$valuearray = explode(",",$value);

		// crmv@141878
		// array of values
		if(count($valuearray) > 1 && $comparator != 'bw') {

			$advcolumnsql = "";
			foreach ($valuearray as $value) {
				$newcond = $condition;
				$newcond['value'] = trim($value);
				$advcolsql[] = $this->generateAdvSearchClause($newcond);
			}
			//If negative logic filter ('not equal to', 'does not contain') is used, 'and' condition should be applied instead of 'or'
			if($comparator == 'n' || $comparator == 'k')
				$advcolumnsql = implode(" and ",$advcolsql);
			else
				$advcolumnsql = implode(" or ",$advcolsql);
			$fieldvalue = " (".$advcolumnsql.") ";
		
		// crmv@141599
		// special search for username and exact match to solve the problem that the usrename can contain also first and last name
		} elseif(in_array($comparator, array('e', 'n')) && ($wstype == 'owner' || (in_array($uitype, array(50,51,52,77)) && $value !== ''))) { // crmv@180881
			$valueIsFull = (strpos($value, '(') !== false);
			$ids = $this->getMatchingOwnerName($value, $valueIsFull);
			if (count($ids) > 0) {
				$fieldvalue = $sqlColumnId.' '.($comparator == 'n' ? 'NOT ' : '').'IN ('.implode(', ', $ids).')';
			} else {
				// no ids found, so always false or true
				$fieldvalue = ($comparator == 'n' ? '1' : '0').' = 1';
			}

		// crmv@141599e
		} elseif($comparator == 'e' && ($value == "NULL" || $value == '')) {
			$fieldvalue = "(".$sqlColumn." IS NULL OR ".$sqlColumn." = '')";
		
		// crmv@38798	crmv@51557
		} elseif($comparator == 'n' && ($value != "NULL" && $value != '') && !$condition['reference']) { // crmv@160692
			$fieldvalue = "(".$sqlColumn." != '$value')";
		
		} elseif($comparator == 'n' && ($value == "NULL" || $value == '')) {
			$fieldvalue = "(".$sqlColumn." IS NOT NULL AND ".$sqlColumn." != '')";
		// crmv@38798e	crmv@51557e
		
		} elseif($comparator == 'bw' && count($valuearray) == 2) {
			$value1 = trim($valuearray[0]);
			$value2 = trim($valuearray[1]);
			//crmv@33666
			if ($adb->isOracle() && ($wstype == 'date' || $wstype == 'datetime')) {
				if ($wstype == 'date') {								
					$fieldvalue = "(".$sqlColumn." BETWEEN to_date('".$value1."','YYYY-mm-dd') AND to_date('".$value2."','YYYY-mm-dd'))";
				} elseif ($wstype == 'datetime') {
					$fieldvalue = "(".$sqlColumn." BETWEEN to_date('".$value1."','YYYY-mm-dd HH24:Mi:SS') AND to_date('".$value2."','YYYY-mm-dd HH24:Mi:SS'))";
				}
			} else {
			//crmv@33666e
				$fieldvalue = "(".$sqlColumn." BETWEEN '".$value1."' AND '".$value2."')";
			}
		} else {
			$fieldvalue = $sqlColumn.$this->getAdvComparator($condition);
		}
		// crmv@141878e

		return $fieldvalue;
	}
	
	// crmv@141599
	/**
	 * Get the ids of users and groups matching the specified name
	 */
	function getMatchingOwnerName($name, $fullname = null) {
		global $current_user;
		global $adb, $table_prefix;
		
		static $ownerCache = array();
		
		if (!isset($ownerCache[$name])) {
			$ids = array();
			
			$sqlname = $current_user->formatUserNameSql($adb, $table_prefix.'_users', $fullname);
			$res = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE $sqlname = ? AND deleted = 0", array($name));
			if ($res && $adb->num_rows($res) > 0) {
				$ids[] = intval($adb->query_result_no_html($res, 0, 'id'));
			}
			
			// add also the group
			$res = $adb->pquery("SELECT groupid AS id FROM {$table_prefix}_groups WHERE groupname = ?", array($name));
			if ($res && $adb->num_rows($res) > 0) {
				$ids[] = intval($adb->query_result_no_html($res, 0, 'id'));
			}
			$ownerCache[$name] = array_filter($ids);
		}
		
		return $ownerCache[$name];
	}
	// crmv@141599e
	
	// crmv@140813
	// added to use std filters in sdk reports
	public function getStdFilterFields($reportid = null) {
		return $this->reports->getStdFilterFields($reportid ?: $this->reportid);
	}
	// crmv@140813e
	
	/** Function to get standardfilter startdate and enddate for the given type
	 *  @ param $type : Type String
	 *  returns the $datevalue Array in the given format
	 * 		$datevalue = Array(0=>$startdate,1=>$enddate)
	 */
	function getStandarFiltersStartAndEndDate($type) {
		global $current_user; // crmv@150808
		
		static $dayNames = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
		// get first and last day of week
		$weekstart = $current_user->weekstart;
		if ($weekstart === null || $weekstart === '') $weekstart = 1;
		$weekstart = intval($weekstart);
		$weekend = ($weekstart + 6) % 7;
		// crmv@150808e
		
		$today = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d"), date("Y")));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, date("m")  , date("d")-1, date("Y")));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")-1, "01",   date("Y")));
		$lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, date("m")+1, "01",   date("Y")));
		$nextmonth1 = date("Y-m-t", strtotime("+1 Month"));

		// crmv@150808
		$todayNum = date('w');

		$prevstart = ($todayNum == $weekstart ? time() : strtotime("last {$dayNames[$weekstart]}"));
		$nextend = ($todayNum == $weekend ? time() : strtotime("next {$dayNames[$weekend]}"));
	
		$lastweek0 = date('Y-m-d', strtotime('-1 week', $prevstart));
		$lastweek1 = date('Y-m-d', strtotime('-1 week', $nextend));
		$thisweek0 = date('Y-m-d', $prevstart);
		$thisweek1 = date('Y-m-d', $nextend);
		$nextweek0 = date('Y-m-d', strtotime('+1 week', $prevstart));
		$nextweek1 = date('Y-m-d', strtotime('+1 week', $nextend));
		// crmv@150808e

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
		}else if(date("m") > 3 and date("m") <= 6)
		{
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",date("Y")));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",date("Y")));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",date("Y")));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",date("Y")));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",date("Y")));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",date("Y")));

		}else if(date("m") > 6 and date("m") <= 9)
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

		if($type == "today" )
		{
			$datevalue[0] = $today;
			$datevalue[1] = $today;
		}
		elseif($type == "yesterday" )
		{
			$datevalue[0] = $yesterday;
			$datevalue[1] = $yesterday;
		}
		elseif($type == "tomorrow" )
		{
			$datevalue[0] = $tomorrow;
			$datevalue[1] = $tomorrow;
		}
		elseif($type == "thisweek" )
		{
			$datevalue[0] = $thisweek0;
			$datevalue[1] = $thisweek1;
		}
		elseif($type == "lastweek" )
		{
			$datevalue[0] = $lastweek0;
			$datevalue[1] = $lastweek1;
		}
		elseif($type == "nextweek" )
		{
			$datevalue[0] = $nextweek0;
			$datevalue[1] = $nextweek1;
		}
		elseif($type == "thismonth" )
		{
			$datevalue[0] =$currentmonth0;
			$datevalue[1] = $currentmonth1;
		}

		elseif($type == "lastmonth" )
		{
			$datevalue[0] = $lastmonth0;
			$datevalue[1] = $lastmonth1;
		}
		elseif($type == "nextmonth" )
		{
			$datevalue[0] = $nextmonth0;
			$datevalue[1] = $nextmonth1;
		}
		elseif($type == "next7days" )
		{
			$datevalue[0] = $today;
			$datevalue[1] = $next7days;
		}
		elseif($type == "next30days" )
		{
			$datevalue[0] =$today;
			$datevalue[1] =$next30days;
		}
		elseif($type == "next60days" )
		{
			$datevalue[0] = $today;
			$datevalue[1] = $next60days;
		}
		elseif($type == "next90days" )
		{
			$datevalue[0] = $today;
			$datevalue[1] = $next90days;
		}
		elseif($type == "next120days" )
		{
			$datevalue[0] = $today;
			$datevalue[1] = $next120days;
		}
		elseif($type == "last7days" )
		{
			$datevalue[0] = $last7days;
			$datevalue[1] = $today;
		}
		elseif($type == "last30days" )
		{
			$datevalue[0] = $last30days;
			$datevalue[1] =  $today;
		}
		elseif($type == "last60days" )
		{
			$datevalue[0] = $last60days;
			$datevalue[1] = $today;
		}
		else if($type == "last90days" )
		{
			$datevalue[0] = $last90days;
			$datevalue[1] = $today;
		}
		elseif($type == "last120days" )
		{
			$datevalue[0] = $last120days;
			$datevalue[1] = $today;
		}
		elseif($type == "thisfy" )
		{
			$datevalue[0] = $currentFY0;
			$datevalue[1] = $currentFY1;
		}
		elseif($type == "prevfy" )
		{
			$datevalue[0] = $lastFY0;
			$datevalue[1] = $lastFY1;
		}
		elseif($type == "nextfy" )
		{
			$datevalue[0] = $nextFY0;
			$datevalue[1] = $nextFY1;
		}
		elseif($type == "nextfq" )
		{
			$datevalue[0] = $nFq;
			$datevalue[1] = $nFq1;
		}
		elseif($type == "prevfq" )
		{
			$datevalue[0] = $pFq;
			$datevalue[1] = $pFq1;
		}
		elseif($type == "thisfq" )
		{
			$datevalue[0] = $cFq;
			$datevalue[1] = $cFq1;
		}
		else
		{
			$datevalue[0] = "";
			$datevalue[1] = "";
		}
		return $datevalue;
	}

	public function getSearchFilterSql($reportid, $selectlist = array()) {
		$searchsql = "";
		
		// generic search
		if (!empty($this->querySearch) && $selectlist) {
			$searchConds = array();
			$comparator = 'c';	// contains
			foreach($selectlist as $sel => $searchinfo) {
				$sel = explode(':', $sel);
				$value = $this->convertAdvSearchValue($this->querySearch, $comparator, $sel);
				$sql = $this->generateAdvSearchClause($value, $comparator, $sel);
				$searchConds[] = $sql;
			}
			if (count($searchConds) > 0) {
				$searchsql .= '('.implode(' OR ', $searchConds).')';
			}
		}

		// single column searches
		if (!empty($this->querySearchCols)) {
			$selsDef = array_keys($selectlist);
			$searchConds = array();
			$comparator = 'c';	// contains
			foreach($this->querySearchCols as $searchinfo) {
				$sel = $selsDef[$searchinfo['index']];
				if ($sel) {
					$sel = explode(':', $sel);
					$value = $searchinfo['search'];
					$value = $this->convertAdvSearchValue($value, $comparator, $sel);
					$sql = $this->generateAdvSearchClause($value, $comparator, $sel);
					$searchConds[] = $sql;
				}
			}
			if (count($searchConds) > 0) {
				if (!empty($searchsql)) $searchsql .= "AND ";
				$searchsql .= '('.implode(' AND ', $searchConds).')';
			}
		}

		return $searchsql;
	}
	
	public function getOrderingSql($reportid, $selectlist = array(), $groupslist = array()) {
		$ordering = "";

		if (!empty($this->queryOrdering)) {
			$cols = array();
			$sels = array_values($selectlist);
			
			// get the columns used for grouping
			$groups = array();
			if (is_array($groupslist)) {
				foreach ($groupslist as $k => $groupord) {
					$groupord = explode(' ', $groupord);
					$groups[] = trim($groupord[0]);
				}
			}

			// i can order in the way I want!
			foreach ($this->queryOrdering as $ordinfo) {
				$colidx = $ordinfo['index']; // crmv@128369
				$dir = (strtolower($ordinfo['dir']) == 'desc' ? 'DESC' : 'ASC');
				$sel = trim($sels[$colidx]);
				
				if ($sel) {
					$colname = $this->extractSqlAlias($sel);
					// when in summery mode I need not to mess with the grouping order,
					// I can only change the direction of those, and it's done in the getGroupingList method
					// here I only add the sorting for columns not used for grouping (or all the columns for tabulare report)
					if ($colname) {
						if ($this->reporttype != 'summary' || !in_array($colname, $groups)) {
							$cols[] = "$colname $dir";
						}
					}
					
				}
			}

			$ordering = implode(', ', $cols);
		}

		return $ordering;
	}
	
	function extractSqlAlias($column) {
		$colname = null;
		
		if (($p = stripos($column, ' as ')) !== false) {
			// has an alias, take the alias
			$column = trim(substr($column, $p+4));
			if ($column[0] == '"') {
				// is quoted, stop at the next quote
				if (($e = strpos($column, '"', 1)) ){
					$colname = substr($column, 1, $e-1);
				} else {
					// missing end quote ???
				}
			} else {
				// not quoted, stop ad first space char
				if (preg_match('@[\s,;/-]@', $column, $matches, PREG_OFFSET_CAPTURE)) {
					$e = $matches[0][1];
					$colname = substr($column, 0, $e);
				} else {
					// to the end of string
					$colname = $column;
				}
			}
		} else {
			// simple column
			$colname = $column;
		}
		return $colname;
	}

	public function getPrimaryModule() {
		return $this->primaryModule;
	}
	
	public function getSecondaryModules() {
		return $this->secondaryModules;
	}
	
	public function setOutputFormat($format, $direct = null) {
		$this->outputFormat = $format;
		if (!is_null($direct)) $this->directOutput = (bool)$direct;
	}
	
	public function isOutputDirect() {
		return (bool)$this->directOutput;
	}
	
	public function setReportTab($tab) {
		$this->reportTab = $tab;
	}
	
	public function getReportTab($tab) {
		return $this->reportTab;
	}
	
	public function getTotalCount() {
		return $this->total_count;
	}
	
	public function getSelectColumn($idx) {
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			return $this->columns[$idx];
		} elseif ($this->reportTab == 'COUNT') {
			return $this->summarycolumns[$idx];
		} elseif ($this->reportTab == 'TOTAL') {
			return $this->totalcolumns[$idx];
		}
	}
	
	function alterSelectColumn($idx, $data) {
		// find the column
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			if (isset($this->columns[$idx])) {
				$this->columns[$idx] = array_merge($this->columns[$idx], $data);
			}
		} elseif ($this->reportTab == 'COUNT') {
			if (isset($this->summarycolumns[$idx])) {
				$this->summarycolumns[$idx] = array_merge($this->summarycolumns[$idx], $data);
			}
		} elseif ($this->reportTab == 'TOTAL') {
			if (isset($this->totalcolumns[$idx])) {
				$this->totalcolumns[$idx] = array_merge($this->totalcolumns[$idx], $data);
			}
		}
	}
	
	function existsSelectColumn($data, $tab = null) {
		if (is_null($tab)) $tab = $this->reportTab;
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			$list = &$this->columns;
		} elseif ($tab == 'COUNT') {
			$list = &$this->summarycolumns;
		} elseif ($tab == 'TOTAL') {
			$list = &$this->totalcolumns;
		}

		if (is_array($list)) {
			foreach ($list as $col) {
				if (
					$col['global_alias'] == $data['global_alias'] &&
					$col['table'] == $data['table'] && 
					$col['alias'] == $data['alias']
				) {
					return $col['idx'];
				}
			}
		}
		return false;
	}
	
	function addSelectColumn($data, $tab = null) {
		if (is_null($tab)) $tab = $this->reportTab;
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			$list = &$this->columns;
		} elseif ($tab == 'COUNT') {
			$list = &$this->summarycolumns;
		} elseif ($tab == 'TOTAL') {
			$list = &$this->totalcolumns;
		}
		
		$idx = $this->existsSelectColumn($data);
		if ($idx === false) {
			$idx = count($list);
			$data['idx'] = $idx;
			$list[] = $data;
		}
		return $idx;
	}
	
	public function getStdFilter($idx) {
		return $this->stdfilters[$idx];
	}
	
	public function getAdvFilter($idx) {
		list($gid, $fid) = explode('_', $idx);
		return $this->advfilters[$gid]['conditions'][$idx];
	}
	
	// crmv@143750
	function alterStdFilter($idx, $data) {
		if (isset($this->stdfilters[$idx])) {
			$this->stdfilters[$idx] = array_merge($this->stdfilters[$idx], $data);
		}
	}
	// crmv@143750e
	
	function alterAdvFilter($idx, $data) {
		// find the column
		$isRef = (strpos($idx, ':ref') !== false);
		list($gid, $fid) = explode('_', str_replace(':ref', '', $idx));
		if (isset($this->advfilters[$gid]['conditions'][$fid])) {
			if ($isRef) {
				$this->advfilters[$gid]['conditions'][$fid]['reference'] = array_merge($this->advfilters[$gid]['conditions'][$fid]['reference'], $data);
			} else {
				$this->advfilters[$gid]['conditions'][$fid] = array_merge($this->advfilters[$gid]['conditions'][$fid], $data);
			}
		}
	}
	
	// crmv@128369
	function alterClusterFilter($idx, $data) {
		// find the column
		$isRef = (strpos($idx, ':ref') !== false);
		list($cid, $gid, $fid) = explode('_', str_replace(':ref', '', $idx));
		if (isset($this->clusters[$cid]['conditions'][$gid]['conditions'][$fid])) {
			if ($isRef) {
				$this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['reference'] = array_merge($this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['reference'], $data);
			} else {
				$this->clusters[$cid]['conditions'][$gid]['conditions'][$fid] = array_merge($this->clusters[$cid]['conditions'][$gid]['conditions'][$fid], $data);
				// crmv@134993
				if ($this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['reference'] && empty($this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['reference']['table'])) {
					$this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['reference']['table'] = $this->clusters[$cid]['conditions'][$gid]['conditions'][$fid]['table'];
				}
				// crmv@134993e
			}
		}
	}
	// crmv@128369e
	
	// Sort an array in a stable way, using the Schwartzian transform
	static protected function stableSort(&$array, $fn = null) {
		$key = 0;
		foreach ($array as &$col) $col = array($col, $key++);
		unset($col);
		
		if ($fn) {
			usort($array, function($a, $b) use (&$fn) {
				return $fn($a[0], $b[0]) ?: ($a[1] - $b[1]);
			});
		} else {
			sort($array);
		}
		
		foreach ($array as &$col) $col = $col[0];
	}
	
	/**
	 * Retrieve all the informations needed to generate the report
	 */
	public function retrieveReportInfo($force = false, $applyPermissions = true) {
	
		$config = $this->reports->loadReport($this->reportid);
		
		$this->reporttype = $config['reporttype'];
		$this->reportname = $config['reportname'];

		// this is the new array with the field definition
		if (empty($this->relations) || $force) $this->relations = $this->reports->getRelations($this->reportid);
		if (empty($this->columns) || $force) $this->columns = $this->reports->getColumns($this->reportid);
		if (empty($this->totalcolumns) || $force) $this->totalcolumns = $this->reports->getTotalColumns($this->reportid) ?: array();
		if (empty($this->summarycolumns) || $force) $this->summarycolumns = $this->reports->getSummaryColumns($this->reportid) ?: array();
		if (empty($this->stdfilters) || $force) $this->stdfilters = $this->reports->getStdFilters($this->reportid) ?: array();
		if (empty($this->advfilters) || $force) $this->advfilters = $this->reports->getAdvFilters($this->reportid) ?: array();
		if (empty($this->clusters) || $force) $this->clusters = $this->reports->getClusters($this->reportid) ?: array(); // crmv@128369

		if ($applyPermissions) {
			$this->filterAccessibleColumns();
		}

	}
	
	/**
	 * Remove columns and fields that are not accessible for my user
	 */
	protected function filterAccessibleColumns() {
		if (!empty($this->columns)) {
			$filtered = array();
			foreach($this->columns as $field) {
				if ($this->isFieldAccessible($field)) {
					$filtered[] = $field;
				}
			}
			$this->columns = $filtered;
		}
		
		// TODO: filter summary columns also
	}
	
	/**
	 * Set the report info from an array, so I can run report on the fly!
	 */
	public function setReportInfo($config) {
		$this->reporttype = $config['reporttype'];
		$this->reportname = $config['reportname'];
		
		$this->relations = $config['relations'];
		$this->columns = $config['fields'];
		$this->totalcolumns = $config['totals'] ?: array();
		$this->summarycolumns = $config['summary'] ?: array();
		$this->stdfilters = $config['stdfilters'] ?: array();
		$this->advfilters = $config['advfilters'] ?: array();
		$this->clusters = $config['clusters'] ?: array(); // crmv@128369
		
	}
	
	protected function prepareColumn(&$col) {
		// here you can add specific variables to the field
		
		$col['visible'] = true;

		$finfo = $this->getFieldInfoById($col['fieldid']);

		if (isInventoryModule($col['module']) && $col['fieldname'] == 'currency_id') {
			$col['alias'] = "currency_id";
		} else {
			$col['alias'] = "f_".$col['fieldid'];
		}
		
		// crmv@147366
		if (is_array($col['chain']) && !$col['relation']) {
			$col['relation'] = end($col['chain']);
			reset($col['chain']);
		}
		// crmv@147366e
		
		if (!$col['module']) $col['module'] = $finfo['module'];
		if (!$col['fieldname']) $col['fieldname'] = $finfo['fieldname'];
		if (!$col['fieldlabel']) $col['fieldlabel'] = $finfo['fieldlabel'];

		$col['tablename'] = $finfo['tablename'];
		$col['columnname'] = $finfo['columnname'];
		$col['uitype'] = $finfo['uitype'];
		$col['wstype'] = $finfo['wstype'];
		$col['typeofdata'] = $finfo['typeofdata'];
		// crmv@127526
		if (FakeModules::isFakeModule($col['module'])) {
			$col['label'] = FakeModules::getFieldLabel($col['module'], $finfo['fieldname']);
		} else {
			$col['label'] = getTranslatedString($finfo['fieldlabel'], $col['module']);
		}
		// crmv@127526e
		$col['relmodules'] = $finfo['relmodules'];
		$col['is_entityname'] = $finfo['is_entityname'];
		
		$module = $col['module'];
		
		if (in_array($col['uitype'], array(116,117))) {
			$col['currency_name'] = true;
		} elseif (is_array($this->append_currency_symbol_to_value_mods[$module]) && in_array($col['fieldname'], $this->append_currency_symbol_to_value_mods[$module])) {
			$col['append_currency'] = true;
		} elseif (in_array($col['uitype'], array(71,72))) {
			$col['convert_currency'] = true;
		} elseif (in_array($col['uitype'], array(15,33,55,300)) && $col['fieldname'] != 'firstname') { // crmv@166664
			$col['translate'] = true;
		} elseif ($this->isCalendarModule($module) && in_array($col['fieldname'], array('date_start', 'time_start', 'due_date', 'time_end'))) {
			$col['adjust_timezone'] = true;
		}
		
	}
	
	protected function prepareColumns() {
		// add info to columns
		if ($this->prepared_cache['columns']) return;
		
		$this->groupingLevels = 0;

		// I need a stable sort here, to preserve provided order
		self::stableSort($this->columns, function($a, $b) {
			return ((int)$b['group']) - ((int)$a['group']);
		});
		
		// crmv@128369
		if (count($this->clusters) > 0) {
			array_unshift($this->columns, $this->getClusterColumnInfo());
		}
		// crmv@128369e
		
		// prepare them
		foreach ($this->columns as $idx => &$col) {
			// crmv@128369
			$col['idx'] = $idx;
			if ($col['fieldname'] != 'cluster') {
				if (!$col['fieldid']) throw new ReportException("Missing fieldid in column definition");
				$this->prepareColumn($col);
			}
			// crmv@128369e
			if ($col['group'] && $col['summary']) $this->summarycolumns[] = $col;
			if ($col['group']) $this->groupingLevels++;
		}
		unset($col); // correct to free, otherwise the pointer can be reused later
		$this->prepared_cache['columns'] = true;
	}
	
	protected function prepareTotalColumns() {
		if ($this->prepared_cache['totalcolumns']) return;
		
		foreach ($this->totalcolumns as $idx => &$col) {
			if (!$col['fieldid']) throw new ReportException("Missing fieldid in total column definition");
			
			$col['idx'] = $idx;
			$this->prepareColumn($col);
		}
		unset($col);
		$this->prepared_cache['totalcolumns'] = true;
	}
	
	protected function prepareSummaryColumns() {
		if ($this->prepared_cache['summarycolumns']) return;
		
		//$this->summarycolumns = array_merge($this->columns, $this->summarycolumns);

		// check if the summary columns are a subset of the standard columns
		// in this case, I can use the same query for the subquery,
		// thus reducing the time
		$colNames = array_map(function($f) {
			return $f['fieldid'];
		}, $this->columns);
		$sumColNames = array_map(function($f) {
			return $f['fieldid'];
		}, $this->summarycolumns);
		$commonNames = array_intersect($colNames, $sumColNames);
		if (count($commonNames) == count($sumColNames) && count($colNames) > count($sumColNames)) {
			// merge the columns
			$outcols = array();
			foreach ($this->columns as $col) {
				if (in_array($col['fieldid'], $sumColNames)) {
					$k = array_search($col['fieldid'], $sumColNames);
					$outcols[] = $this->summarycolumns[$k];
				} else {
					$outcols[] = $col;
				}
			}
			$this->summarycolumns = $outcols;
		}
		
		$this->summaryLevels = 0;
		
		// I need a stable sort here, to preserve provided order
		self::stableSort($this->summarycolumns, function($a, $b) {
			return ((int)$b['group']) - ((int)$a['group']);
		});
		
		foreach ($this->summarycolumns as $idx => &$col) {
			// crmv@128369
			$col['idx'] = $idx;
			if ($col['fieldname'] != 'cluster') {
				if (!$col['fieldid']) throw new ReportException("Missing fieldid in summary column definition");
				$this->prepareColumn($col);
			}
			// crmv@128369e
			if ($col['group'] && $col['summary']) $this->summaryLevels++;
		}
		unset($col);

		//$this->summarycolumns = array_unique($this->summarycolumns);
		$this->prepared_cache['summarycolumns'] = true;
	}
	
	protected function prepareStdFilters() {
		// add info to standard filters
		if ($this->prepared_cache['stdfilters']) return;
		foreach ($this->stdfilters as $idx => &$flt) {
			if (!$flt['fieldid']) throw new ReportException("Missing fieldid in standard filter definition");
			
			if ($flt['value'] == 'custom' && (empty($flt['startdate']) || empty($flt['enddate']))) {
				// ignore empty filters
				unset($this->stdfilters[$idx]);
			}
			
			$flt['idx'] = $idx;
			$flt['alias'] = "f_".$flt['fieldid'];
			
			$finfo = $this->getFieldInfoById($flt['fieldid']);
			if (!$flt['module']) $flt['module'] = $finfo['module'];
			if (!$flt['fieldname']) $flt['fieldname'] = $finfo['fieldname'];
		}
		unset($flt);
		$this->prepared_cache['stdfilters'] = true;
	}
	
	/**
	 * Check if with the current set of conditions, it's possibile to push them down to the subqueries.
	 * The push down is only possible if there are no OR between different subqueries (even accounting children conditions)
	 * To do so, the conditions are first converted in a binary tree, then each OR node is checked to see if the left module
	 * is different from the right module.
	 */
	protected function checkPushDown($conditions) {
		$ctree = new ConditionsTree();
		$ctree->parse($conditions);
		return $ctree->checkOrNodes();
	}
	
	protected function prepareAdvFilters() {
		// add info to advanced
		if ($this->prepared_cache['advfilters']) return;
		
		// check for pushdown
		$pushdown = ($this->pushdownConditions && $this->checkPushDown($this->advfilters));
		$checkGroupPushdown = false;
		
		// no global pushdown, but maybe I can push down only a group ?
		// this is possible if all groups are joined in AND and all conditions inside are related to the same module
		if ($this->pushdownConditions && !$pushdown) {
			$checkGroupPushdown = true;
			foreach ($this->advfilters as $i=>$group) {
				if ($i < count($this->advfilters)-1) {
					$g = strtolower($group['glue']);
					if ($g == 'or') {
						$checkGroupPushdown = false;
						break;
					}
				}
			}
		}
		
		foreach ($this->advfilters as $gid => &$group) {
			if ($group['glue']) $group['glue'] = strtolower($group['glue']);
			
			$groupRelations = array();
			foreach ($group['conditions'] as $fid => &$flt) {
				if (!$flt['fieldid']) throw new ReportException("Missing fieldid in advanced filter definition");
				
				$flt['idx'] = $gid.'_'.$fid;
				$flt['alias'] = "f_".$flt['fieldid'];
				
				$finfo = $this->getFieldInfoById($flt['fieldid']);
				if ($flt['glue']) $flt['glue'] = strtolower($flt['glue']);
				if (!$flt['module']) $flt['module'] = $finfo['module'];
				if (!$flt['fieldname']) $flt['fieldname'] = $finfo['fieldname'];
				
				if (isInventoryModule($flt['module']) && $flt['fieldname'] == 'currency_id') {
					$flt['alias'] = "currency_id";
				}
				
				$flt['uitype'] = $finfo['uitype'];
				$flt['wstype'] = $finfo['wstype'];
				$flt['typeofdata'] = $finfo['typeofdata'];
				
				if ($flt['reference'] && $flt['ref_fieldid']) {
					$refinfo = $this->getFieldInfoById($flt['ref_fieldid']);
					$refinfo['idx'] = $flt['idx'].':ref';
					$refinfo['alias'] = "f_".$flt['ref_fieldid'];
					$refinfo['relation'] = $flt['ref_relation'];
					$refinfo['parent_filter'] = $flt['idx'];
					$flt['reference'] = $refinfo;
				} else {
					// reference fields are not pushed down!
					// crmv@111996 - null comparison are used as rudimentary conditions on relations
					$isNullComparison = (in_array($flt['comparator'], array('e', 'n')) && $flt['value'] === 'NULL');
					$flt['pushdown'] = ($pushdown && !FakeModules::isFakeModule($flt['module']) && !$isNullComparison); // crmv@127526
					// crmv@111996e
				}
				$groupRelations[] = $flt['relation'] ?: 'Main';

			}
			unset($flt);
			
			// check if all conditions involve the same module
			if ($checkGroupPushdown) {
				$groupRelations = array_unique($groupRelations);
				if (count($groupRelations) == 1) {
					foreach ($group['conditions'] as &$flt) {
						// crmv@111996
						$isNullComparison = (in_array($flt['comparator'], array('e', 'n')) && $flt['value'] === 'NULL');
						if (!$flt['reference'] && !FakeModules::isFakeModule($flt['module']) && !$isNullComparison) { // crmv@127526
							$flt['pushdown'] = true;
						}
						// crmv@111996e
					}
					unset($flt);
				}
			}
		}
		unset($group);
		
		$this->prepared_cache['advfilters'] = true;
	}
	
	// crmv@128369
	protected function prepareClusters() {
		if ($this->prepared_cache['clusters']) return;
		
		foreach ($this->clusters as $cid => &$cluster) {
			if (is_array($cluster['conditions'])) {
				foreach ($cluster['conditions'] as $gid => &$group) {
					if ($group['glue']) $group['glue'] = strtolower($group['glue']);
					
					foreach ($group['conditions'] as $fid => &$flt) {
						if (!$flt['fieldid']) throw new ReportException("Missing fieldid in cluster filter definition");
						
						$flt['idx'] = $cid.'_'.$gid.'_'.$fid;
						$flt['forcluster'] = true;
						$flt['alias'] = "f_".$flt['fieldid'];
						
						$finfo = $this->getFieldInfoById($flt['fieldid']);
						if ($flt['glue']) $flt['glue'] = strtolower($flt['glue']);
						if (!$flt['module']) $flt['module'] = $finfo['module'];
						if (!$flt['fieldname']) $flt['fieldname'] = $finfo['fieldname'];
						
						if (isInventoryModule($flt['module']) && $flt['fieldname'] == 'currency_id') {
							$flt['alias'] = "currency_id";
						}
						
						$flt['uitype'] = $finfo['uitype'];
						$flt['wstype'] = $finfo['wstype'];
						$flt['typeofdata'] = $finfo['typeofdata'];
						
						if ($flt['reference'] && $flt['ref_fieldid']) {
							$refinfo = $this->getFieldInfoById($flt['ref_fieldid']);
							$refinfo['idx'] = $flt['idx'].':ref';
							$refinfo['alias'] = "f_".$flt['ref_fieldid'];
							$refinfo['relation'] = $flt['ref_relation'];
							$refinfo['parent_filter'] = $flt['idx'];
							$flt['reference'] = $refinfo;
						}
					}
					unset($flt);
				}
				unset($group);
			}
		}
		unset($cluster);
		
		$this->prepared_cache['clusters'] = true;
	}
	// crmv@128369e
	
	protected function prepareRelations() {
		if ($this->prepared_cache['relations']) return;
		// merge info in relations
		$relModules = array();
		foreach ($this->relations as &$rel) {
			$module = $rel['module'];
			
			if (!$rel['parent']) {
				// primary module!
				if ($this->primaryModule) throw new ReportException('Multiple primary modules defined');
				$this->primaryModule = $module;
			} else {
				$this->secondaryModules[] = $module;
			}
			$relModules[$rel['name']] = $module;
			
			$parentModule = $relModules[$rel['parent']];
			
			if ($rel['fieldid']) {
				$mrels = ModuleRelation::createFromFieldId($rel['fieldid']);
				foreach ($mrels as $r) {
					if ($r->getSecondModule() == $module) {
						$rel['relation'] = $r;
						break;
					}
				}
				// crmv@121372
				// invert relation if it's to the same module and the type is wrong
				if ($rel['relation'] && preg_match('/_fld([24])_/', $rel['name'], $matches)) {
					$type = $matches[1];
					if ($rel['relation']->getType() != $type) {
						$rel['relation']->invert();
					}
				}
				// crmv@121372e
			} elseif ($rel['relationid']) {
				// crmv@166086
				$relation = ModuleRelation::createFromRelationId($rel['relationid']);
				if ($relation && $parentModule != $module && $parentModule == $relation->getSecondModule() && $module == $relation->getFirstModule()) {
					$relation->invert();
				}
				$rel['relation'] = $relation;
				// crmv@166086e
			}
			
			// add field infos
			foreach ($this->columns as $col) {
				$inventoryBlock = ModuleRelation::isFakeRelationId($rel['relationid']);
				if ($inventoryBlock && $col['module'] == 'ProductsBlock') {
					// ok, keep it
				} elseif ($rel['module'] != $col['module']) {
					continue;
				}
				
				// crmv@103885
				$added = false;
				if (!$rel['parent'] && !$col['relation']) {
					$rel['fields'][] = $col['idx'];
					$added = true;
				} elseif ($rel['name'] == $col['relation']) {
					$rel['fields'][] = $col['idx'];
					$added = true;
				}
				
				// add CV fields for uitype 10 of a different module
				if ($added && $this->reportTab == 'CV' && $this->cvInfo['module'] != $module) {
					if ($col['uitype'] == 10 && in_array($this->cvInfo['module'], $col['relmodules'])) {
						$rel['cvfields'][] = $col['idx'];
					}
				}
				// crmv@103885e
			}
			
			foreach ($this->totalcolumns as $col) {
				$inventoryBlock = ModuleRelation::isFakeRelationId($rel['relationid']);
				if ($inventoryBlock && $col['module'] == 'ProductsBlock') {
					// ok, keep it
				} elseif ($rel['module'] != $col['module']) {
					continue;
				}
				
				if (!$rel['parent'] && !$col['relation']) {
					$rel['totalfields'][] = $col['idx'];
				} elseif ($rel['name'] == $col['relation']) {
					$rel['totalfields'][] = $col['idx'];
				}
			}
			
			foreach ($this->summarycolumns as $col) {
				$inventoryBlock = ModuleRelation::isFakeRelationId($rel['relationid']);
				if ($inventoryBlock && $col['module'] == 'ProductsBlock') {
					// ok, keep it
				} elseif ($rel['module'] != $col['module']) {
					continue;
				}
				
				if (!$rel['parent'] && !$col['relation']) {
					$rel['summaryfields'][] = $col['idx'];
				} elseif ($rel['name'] == $col['relation']) {
					$rel['summaryfields'][] = $col['idx'];
				}
			}
			
			// add std filters
			foreach ($this->stdfilters as $flt) {
				if ($rel['module'] != $flt['module']) continue;
				if (!$rel['parent'] && !$flt['relation']) {
					$rel['filters'][] = $flt;
				} elseif ($rel['name'] == $flt['relation']) {
					$rel['filters'][] = $flt;
				}
				
				// crmv@143750
				$flt['type'] = 'stdfilter';
				// add it also to global filters
				$modMatch = (
					($rel['module'] == $flt['module']) || 
					($this->isCalendarModule($rel['module']) && $this->isCalendarModule($flt['module']))
				);
				if ($modMatch) {
					if (!$rel['parent'] && !$flt['relation']) {
						$rel['global_filters'][] = $flt;
					} elseif ($rel['name'] == $flt['relation']) {
						$rel['global_filters'][] = $flt;
					}
				}
				// crmv@143750e
			}
			
			// add adv filters only as columns
			foreach ($this->advfilters as $gid=>$group) {
				foreach ($group['conditions'] as $flt) {
					// a reference comparison, check if I have to add the field here
					if ($flt['reference'] && $flt['reference']['module'] == $rel['module']) {
						if (!$rel['parent'] && !$flt['reference']['relation']) {
							$rel['global_filters'][] = $flt['reference'];
						} elseif ($rel['name'] == $flt['reference']['relation']) {
							$rel['global_filters'][] = $flt['reference'];
						}
					}
					
					// a field for this module
					// terrible fix for Calendar module
					$modMatch = (
						($rel['module'] == $flt['module']) || 
						($this->isCalendarModule($rel['module']) && $this->isCalendarModule($flt['module']))
					);
					if ($modMatch) {
						if (!$rel['parent'] && !$flt['relation']) {
							$rel['global_filters'][] = $flt;
							if (!$flt['reference'] && $flt['pushdown']) {
								$rel['pushed_filters'][$gid]['glue'] = $group['glue'];
								$rel['pushed_filters'][$gid]['conditions'][] = $flt;
							}
						} elseif ($rel['name'] == $flt['relation']) {
							$rel['global_filters'][] = $flt;
							if (!$flt['reference'] && $flt['pushdown']) {
								$rel['pushed_filters'][$gid]['glue'] = $group['glue'];
								$rel['pushed_filters'][$gid]['conditions'][] = $flt;
							}
						}
					}
				}
			}
			
			// crmv@128369
			// add adv filters from clusters
			foreach ($this->clusters as $cid=>$cluster) {
				if (is_array($cluster['conditions'])) {
					foreach ($cluster['conditions'] as $gid=>$group) {
						foreach ($group['conditions'] as $flt) {
							// a reference comparison, check if I have to add the field here
							if ($flt['reference'] && $flt['reference']['module'] == $rel['module']) {
								if (!$rel['parent'] && !$flt['reference']['relation']) {
									$rel['global_filters'][] = $flt['reference'];
								} elseif ($rel['name'] == $flt['reference']['relation']) {
									$rel['global_filters'][] = $flt['reference'];
								}
							}
							
							// a field for this module
							// terrible fix for Calendar module
							$modMatch = (
								($rel['module'] == $flt['module']) || 
								($this->isCalendarModule($rel['module']) && $this->isCalendarModule($flt['module']))
							);
							if ($modMatch) {
								if (!$rel['parent'] && !$flt['relation']) {
									$rel['global_filters'][] = $flt;
								} elseif ($rel['name'] == $flt['relation']) {
									$rel['global_filters'][] = $flt;
								}
							}
						}
					}
				}
			}
			// crmv@128369e
			
			if (is_array($rel['pushed_filters'])) {
				$rel['pushed_filters'] = array_values($rel['pushed_filters']);
			}
		}
		unset($rel);
		$this->prepared_cache['relations'] = true;
	}
	
	/**
	 * Prepare the report informations for the next phase of query generation
	 * Do also some basic sanity checks
	 */
	public function prepareReportInfo() {
		
		$this->prepareColumns();
		$this->prepareSummaryColumns();
		$this->prepareTotalColumns();
		$this->prepareStdFilters();
		$this->prepareAdvFilters();
		$this->prepareClusters(); // crmv@128369
		$this->prepareRelations();

		$this->secondaryModules = array_unique($this->secondaryModules);
	}
	
	/**
	 * Prepare the hierarchical (later the joins are flattened anyway, but it usefult for the future)
	 */
	public function prepareQueryPlan() {
	
		if ($this->prepared_cache['queryplan']) return;
		$this->queryPlan = null;
		
		// copy the relations
		$rels = $this->relations;

		// build the "query plan", a handy array with the modules and the needed joins
		$joinplan = array();
		$countRel = count($rels);
		$done = 0;
		$joinPtr = array();
		$i = 0;
		$maxIter = $countRel*$countRel;
		while ($done < count($rels) && $i++ < $maxIter) {
			foreach ($rels as &$rel) {
				if ($rel['done']) continue;
				if (!$rel['parent']) {
					// root
					$joinplan[$rel['name']] = $rel;
					$joinPtr[$rel['name']] = &$joinplan[$rel['name']];
					$rel['done'] = true;
					++$done;
				} elseif ($joinPtr[$rel['parent']]) {
					// I have a pointer
					$joinPtr[$rel['parent']]['children'][] = $rel;
					end($joinPtr[$rel['parent']]['children']); // crmv@113237 ensure the array pointer is at the end
					$lastkey = key($joinPtr[$rel['parent']]['children']);
					$joinPtr[$rel['name']] = &$joinPtr[$rel['parent']]['children'][$lastkey];
					$rel['done'] = true;
					++$done;
				} else {
					//$rel['skipped']++;
				}
			}
		}

		$this->queryPlan = $joinplan;
		unset($joinPtr, $joinplan, $rels);
		$this->prepared_cache['queryplan'] = true;
	}
	
	// crmv@127526
	function translateFakeField($value, $module, $fieldname) {
		$finfo = FakeModules::getFieldInfo($fieldname, $module);
		if ($finfo && is_array($finfo['allowed_values']) && array_key_exists($value, $finfo['allowed_values'])) {
			$value = $finfo['allowed_values'][$value];
		}
		return $value;
	}
	// crmv@127526e
	
	/**
	 * Prepare the subquery for the report
	 */
	function prepareSubQueries() {
		// TODO: check if i can use a cache (the sub queries changes with the tab)
		//if ($this->prepared_cache['subqueries']) return;
		$this->subQueries = array();

		// crmv@161689
		// don't materialize if there's only one module (seems to be faster)
		reset($this->queryPlan);
		$first = current($this->queryPlan);
		if (count($this->queryPlan) == 1 && is_array($first['children']) && count($first['children']) == 0) $this->subqueryMaterialization = false; // crmv@172864
		// crmv@161689e

		// generate subqueries (no multiple roots supported)
		$mainmod = $this->primaryModule;
		$this->walkRecursiveTree($this->queryPlan[$mainmod], 'children', array($this, 'generateSubQueries'));

		//$this->prepared_cache['subqueries'] = true;
	}
	

	function generateSubQueries(&$node, &$parent = null) {
		global $adb, $table_prefix, $current_user;
		
		$direct = false;
		$appendJoin = '';
		$appendWhere = '';
		$appendSelects = array();
		$module = $node['module'];
		$materialize = $this->subqueryMaterialization;

		// crmv@174507
		// cvfields is empty by design, since I don't want all the other fields
		$keys = array('MAIN' => 'fields', 'CV'=> 'cvfields', 'TOTAL' => 'totalfields', 'COUNT' => 'summaryfields');
		$fieldkey = $keys[$this->reportTab];
		
		if ($this->reportTab != 'CV' && empty($node[$fieldkey]) && empty($node['children']) && empty($node['global_filters'])) {
			// skip the join, if there aren't fields or filters, and don't have children
			return;
		}
		// crmv@174507e

		// crmv@148915
		$suffix = '';
		$aliasIdx = 1;
		while (array_key_exists($alias = $this->generateSubQueryAlias($node, $suffix), $this->subQueries)) {
			$suffix = '_'.$aliasIdx++;
		}
		$node['alias'] = $alias;
		// crmv@148915e
		
		$joinedTables = array();
		$aliasedTables = array();

		// crmv@127526
		if (FakeModules::isFakeModule($module)) {
			$qgen = FakeQueryGenerator::getInstance($module, $current_user, $this, $this->reports);
			$meta = $qgen->getMeta($module);
			
			$idcolumn = $meta->getIdColumn();
			$qgen->setFields(array($idcolumn));
		// crmv@127526e
		} else {
			$qgenModule = ($module == 'Events' ? 'Calendar' : $module);
			$qgen = QueryGenerator::getInstance($qgenModule, $current_user);
			$qgen->useSelectPermissions = false; // crmv@133387
			$meta = $qgen->getMeta($module);
			
			$qgen->setFields(array('id'));
			$idcolumn = 'crmid';
			$joinedTables[] = $table_prefix.'_crmentity';
		}

		// always add the primary id for the module
		// crmv@129940 crmv@148336
		$this->addSelectColumn(array(
			'alias' => $idcolumn, // was crmid
			'table' => $alias,
			'global_alias' => 'id@'.$alias,
			'visible' => false,
		));
		// crmv@129940e crmv@148336e
		$joinedTables[] = $meta->getEntityBaseTable();
		
		// crmv@103885
		// for cv, I need the sub modules to have the tabid, but not the main module
		if ($this->reportTab == 'CV' && !FakeModules::isFakeModule($module)) { // crmv@127526
			// add the tabid alias
			$cvModule = $this->cvInfo['module'];
			// crmv@159603 - another brick in the broken wall of the shitty calendar!
			$cvCal = $this->isCalendarModule($cvModule);
			$modCal = $this->isCalendarModule($module);
			if ($cvModule != $module && !($cvCal && $modCal)) { // crmv@120690 crmv@184370
				$cvtabid = $this->reports->getTabid($module);
			} elseif ($cvModule == $module || ($cvCal && $modCal)) {
				$cvtabid = 0;
			} else {
				$cvtabid = null;
			}
			// crmv@159603e
			if (!is_null($cvtabid)) {
				$this->addSelectColumn(array(
					'alias' => $idcolumn, // was crmid
					'table' => $alias,
					'global_alias' => 'id@tab_'.$cvtabid,
					'visible' => false,
				));
			}
		}
		// crmv@103885e
		
		// crmv@109657
		// add the currency column
		if (isInventoryModule($module)) {
			$appendJoin .= "LEFT JOIN {$table_prefix}_inventorytotals ON {$table_prefix}_inventorytotals.id = {$table_prefix}_crmentity.crmid";
			$currencyField = $this->getFieldInfoByName($module, 'currency_id');
			if ($this->isFieldAccessible($currencyField)) {
				$qgen->addField('currency_id');
				$this->addSelectColumn(array(
					'alias' => 'currency_id',
					'table' => $alias,
					'global_alias' => 'currency_id@'.$alias,
					'visible' => false,
				));
			}
		} elseif (isProductModule($module)) {
			$appendSelects[] = $meta->getEntityBaseTable().".currency_id";
			$this->addSelectColumn(array(
				'alias' => 'currency_id',
				'table' => $alias,
				'global_alias' => 'currency_id@'.$alias,
				'visible' => false,
			));
		} elseif ($module == 'ProductsBlock' && $parent) {
			$currencyField = $this->getFieldInfoByName($parent['module'], 'currency_id');
			if ($this->isFieldAccessible($currencyField)) {
				$this->addSelectColumn(array(
					'alias' => 'currency_id',
					'table' => $parent['alias'],
					'global_alias' => 'currency_id@'.$alias,
					'visible' => false,
				));
			}
		} elseif ($module == 'Events') {
			// query generator always insert the visibility column,
			// I only have to add it to the main query
			$this->addSelectColumn(array(
				'alias' => 'visibility',
				'table' => $alias,
				'global_alias' => 'visibility@'.$alias,
				'visible' => false,
			));
		}
		// crmv@109657
		
		$manualFields = array();
		$delayFields = array();

		// add fields for reverse relations (1toN or Nto1)
		if ($node['fieldid']) {
			$finfo = $this->getFieldInfoById($node['fieldid']);
			if ($finfo['module'] == $module) {
				$relfieldname = $finfo['fieldname'];
				$relalias = 'fieldrel_'.$finfo['fieldid'];
				$qgen->addField($relfieldname);
				$qgen->addFieldAlias($relfieldname, $relalias);
				if (!in_array($finfo['tablename'], $joinedTables)) $joinedTables[] = $finfo['tablename'];
			}
		}
		
		// add fields for children relations (Nto1)
		if ($node['children']) {
			// Nto1 relation, add the relation field
			foreach ($node['children'] as $child) {
				if ($child['fieldid'] && $child['relation']) {
					$relfieldname = $child['relation']->fieldname;
					$relalias = 'fieldrel_'.$child['fieldid'];
					$qgen->addField($relfieldname);
					$qgen->addFieldAlias($relfieldname, $relalias);
					if (!in_array($child['relation']->fieldtable, $joinedTables)) $joinedTables[] = $child['relation']->fieldtable; // crmv@98894
				} elseif ($child['relationid']) {
					
				}
			}
		}

		$dupFieldCheck = array(); // crmv@165479
		
		// fields for select
		if (is_array($node[$fieldkey])) {
			foreach ($node[$fieldkey] as $fldidx) {
				$fld = $this->getSelectColumn($fldidx);

				$column = null;
				$fieldalias = $fld['alias'];
				$globalalias = $fieldalias.'@'.$alias;

				// crmv@165479
				// check if already inserted (in total tab, fields might be duplicated)
				if ($this->reportTab == 'TOTAL') {
					if (array_key_exists($globalalias, $dupFieldCheck)) {
						// still alter the column for the total query
						$this->alterSelectColumn($fldidx, array(
							//'column' => $column,
							'alias' => $fieldalias,
							'table' => $alias,
							'global_alias' => $globalalias,
						));
						continue;
					}
					$dupFieldCheck[$globalalias] = true;
				}
				// crmv@165479e
				
				// crmv@129978
				$orderAlias = null;
				$displayAlias = null;
				// crmv@129978e

				// crmv@148336 - removed total changes
				
				if ($fld['uitype'] == 10) {
					$appendSelects = array_merge($appendSelects, $this->generateSelectForReference($fld, $aliasedTables));
					$appendJoin .= $this->generateJoinForReference($fld, $joinedTables, $aliasedTables, $meta);
					$this->addSelectColumn(array(
						'alias' => $fieldalias.'_setype',
						'table' => $alias,
						'global_alias' => $fieldalias.'_setype@'.$alias,
						'module' => $fld['module'],
						'label' => getTranslatedString('Entity Type'),
						'visible' => ($this->reportTab == 'MAIN' && count($fld['relmodules']) > 1),
						'after_column' => $fieldalias.'@'.$alias,
					));
					$this->addSelectColumn(array(
						'alias' => $fieldalias.'_id',
						'table' => $alias,
						'global_alias' => $fieldalias.'_id@'.$alias,
						'visible' => false,
					));
					// crmv@103885
					if ($this->reportTab == 'CV' && in_array($cvModule, $fld['relmodules'])) {
						if ($parent) {
							$cvtabid = $this->reports->getTabid($module);
						} else {
							$cvtabid = 0;
						}
						$this->addSelectColumn(array(
							'alias' => $fieldalias.'_id',
							'table' => $alias,
							'global_alias' => 'id@tab_'.$cvtabid,
							'visible' => false,
						));
					}
					// crmv@103885e
					$manualFields[] = $fld['fieldname'];
				} elseif ($fld['wstype'] == 'owner' || in_array($fld['uitype'], array(50,51,52,77,54))) {	//crmv@126096 crmv@180881
					$appendSelects = array_merge($appendSelects, $this->generateSelectForOwner($fld));
					$appendJoin .= $this->generateJoinForOwner($fld, $joinedTables);
					$qgen->removeField($fld['fieldname']);
					$qgen->addModuleJoin($fld['tablename']); // crmv@153111
					$this->addSelectColumn(array(
						'alias' => $fieldalias.'_setype',
						'table' => $alias,
						'global_alias' => $fieldalias.'_setype@'.$alias,
						'visible' => false,
					));
					$this->addSelectColumn(array(
						'alias' => $fieldalias.'_id',
						'table' => $alias,
						'global_alias' => $fieldalias.'_id@'.$alias,
						'visible' => false,
					));
					$manualFields[] = $fld['fieldname'];
				} elseif (FakeModules::isInventoryTaxField($fld['fieldid'])) { // crmv@127526
					// this field is not supported in the QueryGenerator, adding it manually here!
					$taxSelect = "{$table_prefix}_inventorytotals.{$fld['columnname']} AS \"$fieldalias\"";
					array_push($appendSelects, $taxSelect);
				} elseif ($module == 'ProductsBlock' && $fld['fieldname'] == 'discount') {
					$tbl = $fld['tablename'];
					// crmv@165479
					$cast = "DECIMAL(19,3)";
					$discountSql = " CAST(CASE WHEN $tbl.discount_amount IS NOT NULL THEN $tbl.discount_amount ELSE ROUND($tbl.listprice * $tbl.quantity * $tbl.discount_percent / 100, 3) END AS $cast)"; // crmv@129940
					// crmv@165479e
					$appendSelects[] = "$discountSql AS \"$fieldalias\"";
				} elseif ($fld['uitype'] == '26') {
					// folderid
					$appendSelects[] = $table_prefix."_crmentityfolder.foldername AS \"$fieldalias\"";
					$manualFields[] = $fld['fieldname']; // crmv@164384
				} elseif ($fld['uitype'] == '27') {
					// filelocationtype
					$appendSelects[] = "CASE {$fld['tablename']}.{$fld['columnname']} WHEN 'I' THEN 'File' WHEN 'B' THEN 'File' WHEN 'E' THEN 'URL' ELSE '' END AS \"$fieldalias\"";
				} elseif (isInventoryModule($module) && $fld['fieldname'] == 'currency_id') {
					$fieldalias = 'currency_id';
				} elseif ($fld['fieldname'] == 'newsletter_unsubscrpt') {
					$qgen->addField($fld['fieldname']);
					// add another select manually
					$appendSelects[] = "IF(tbl_s_newsletter_g_unsub.email IS NULL, 1, 0) AS \"$fieldalias\"";
					$manualFields[] = $fld['fieldname'];
				} else {
					$qgen->addField($fld['fieldname']);
					$qgen->addFieldAlias($fld['fieldname'], $fieldalias);
					if (!in_array($fld['tablename'], $joinedTables)) $joinedTables[] = $fld['tablename'];
					
					// now add some extra columns for some special fields
					// crmv@129978
					if ($fld['wstype'] == 'picklistmultilanguage') {
						$valueTable = "tbl_pick_lang".$fld['fieldname'];
						$orderAlias = "{$fieldalias}_order";
						$displayAlias = $orderAlias;
						$appendSelects[] = "$valueTable.value AS \"{$orderAlias}\"";
						$this->addSelectColumn(array(
							'alias' => $orderAlias,
							'table' => $alias,
							'global_alias' => $orderAlias.'@'.$alias,
							'visible' => false,
							'order' => true, // column used for ordering
						));
					}
					// crmv@129978e
				}
				
				// special case for listprice, when the parent module is the pricebook
				if (isProductModule($module) && $fld['fieldname'] == 'unit_price' && $parent['module'] == 'PriceBooks') {
					$relalias = $this->generateSubQueryNtoNAlias($node);
					$column = "$relalias.listprice";
				}
				
				$this->alterSelectColumn($fldidx, array(
					'column' => $column,
					'alias' => $fieldalias,
					'table' => $alias,
					'global_alias' => $globalalias,
					// crmv@129978
					'order_alias' => $orderAlias, 
					'display_alias' => $displayAlias,
					// crmv@129978e
				));
			}
		}

		// other fields used in global filters
		if (is_array($node['global_filters'])) {
			foreach ($node['global_filters'] as $flt) {
				$fieldalias = $flt['alias'];
				if (!in_array($flt['fieldname'], $manualFields)) {
					// TODO: unify with above
					// TODO: optimization: I can compare directly the IDS!!
					if ($flt['uitype'] == 10) {
						$appendSelects = array_merge($appendSelects, $this->generateSelectForReference($flt, $aliasedTables));
						$appendJoin .= $this->generateJoinForReference($flt, $joinedTables, $aliasedTables, $meta);
						$manualFields[] = $flt['fieldname'];
					} elseif ($flt['wstype'] == 'owner' || in_array($flt['uitype'], array(52, 77))) {
						$appendSelects = array_merge($appendSelects, $this->generateSelectForOwner($flt));
						$appendJoin .= $this->generateJoinForOwner($flt, $joinedTables);
						$manualFields[] = $flt['fieldname'];
					// crmv@164384
					} elseif ($flt['uitype'] == '26') {
						// folderid
						$qgen->addField($flt['fieldname']);
						$appendSelects[] = $table_prefix."_crmentityfolder.foldername AS \"$fieldalias\"";
						$manualFields[] = $fld['fieldname'];
					// crmv@164384e
					} elseif ($flt['fieldname'] == 'newsletter_unsubscrpt') {
						$qgen->addField($flt['fieldname']);
						// add another select manually
						$appendSelects[] = "IF(tbl_s_newsletter_g_unsub.email IS NULL, 1, 0) AS \"$fieldalias\"";
						$manualFields[] = $flt['fieldname'];
					} else {
						$qgen->addField($flt['fieldname']);
						if ($fieldalias) $qgen->addFieldAlias($flt['fieldname'], $fieldalias);
						if (!in_array($fld['tablename'], $joinedTables)) $joinedTables[] = $fld['tablename'];
					}
				}
				// crmv@128369
				if ($flt['forcluster']) {
					$this->alterClusterFilter($flt['idx'], array(
						'alias' => $fieldalias,
						'table' => $alias,
						'global_alias' => $fieldalias.'@'.$alias,
					));
				// crmv@143750
				} elseif ($flt['type'] == 'stdfilter') {
					$this->alterStdFilter($flt['idx'], array(
						'alias' => $fieldalias,
						'table' => $alias,
						'global_alias' => $fieldalias.'@'.$alias,
					));
				// crmv@143750e
				} else {
					$this->alterAdvFilter($flt['idx'], array(
						'alias' => $fieldalias,
						'table' => $alias,
						'global_alias' => $fieldalias.'@'.$alias,
					));
					// crmv@160692
					if ($flt['reference']) {
						if ($flt['reference']['relation'] == $flt['relation']) {
							// same module for comparison
							$this->alterAdvFilter($flt['reference']['idx'], array(
								'table' => $alias,
							));
						} else {
							// TODO: comparison with field from other module not handled yet
						}
					}
					// crmv@160692e
				}
				// crmv@128369e
			}
		}

		// add simple filters
		$glue = '';
		if (is_array($node['filters'])) {
			foreach ($node['filters'] as $flt) {
				$this->addFilter($qgen, $flt);
			}
			$glue = QueryGenerator::$AND;
		}
		
		// add push down advanced filters
		if (is_array($node['pushed_filters'])) {
			$this->addAdvFilters($qgen, $node['pushed_filters'], $glue, $manualFields);
		}
		
		if ($module == 'Events') {
			$appendWhere .= "AND {$table_prefix}_activity.activitytype != 'Task'";
		} elseif ($module == 'Calendar') {
			$appendWhere .= "AND {$table_prefix}_activity.activitytype = 'Task'";
		}

		if ($appendSelects) {
			$qgen->appendRawSelect($appendSelects);
		}
		if ($appendJoin) {
			$qgen->appendToFromClause($appendJoin);
		}
		if ($appendWhere) {
			$qgen->appendToWhereClause($appendWhere);
		}

		$sql = $qgen->getQuery();

		// TODO: make sure isn't already included and that there aren't columns with the same name
		// add crmid field
		if (!FakeModules::isFakeModule($module)) { // crmv@127526
			// crmv@203132
			if ($module == 'Processes') {
				$sql = preg_replace('/^\s*SELECT\s*/i', "SELECT {$table_prefix}_processes.processesid as crmid," , $sql);
			} else {
				$sql = preg_replace('/^\s*SELECT\s*/i', "SELECT {$table_prefix}_crmentity.crmid," , $sql);
			}
			// crmv@203132e
		}
		
		// alter/fix the sql with ugly regexp! :D
		$sql = $this->alterSubquerySql($node, $sql, $aliasedTables);

		$VP = VTEProperties::getInstance();
		if ($VP->get('performance.show_query_stats') == 1 && $_REQUEST['show_query'] == 'true' && !$materialize) { // crmv@146138
			echo "MODULE SUBQUERY ($module):<br>\n";
			echo $sql."<br>\n<br>\n";
		}

		if ($direct) {
			$sql .= " $alias";
		}

		if ($parent) {
			$selfJoin = $alias.'.crmid';
			$parentQuery = $this->subQueries[$parent['alias']];
			$parentNode = $parentQuery['node'];
			if ($node['fieldid']) {
				if ($node['type'] == ModuleRelation::$TYPE_NTO1) {
					// the field is in the parent module
					// TODO: pull out the inner query
					if (false && ModuleRelation::isFakePBFieldId($node['fieldid'])) {
						$finfo = $this->getFieldInfoById($node['fieldid']);
						$parentJoin = $parent['alias'].".".$finfo['columnname'];
					} else {
						$parentJoin = $parent['alias'].'.'.'fieldrel_'.$node['fieldid'];
					}
				} else {
					// the field is in this module
					// TODO: idem
					if (false && ModuleRelation::isFakePBFieldId($node['fieldid'])) {
						$finfo = $this->getFieldInfoById($node['fieldid']);
						$selfJoin = $alias.".".$finfo['columnname'];
					} else {
						$selfJoin = $alias.'.fieldrel_'.$node['fieldid'];
					}
					
					$parentJoin = $parent['alias'].'.crmid';
				}
			} else {
				// add N-to-N join (productsrel) TODO: remove!
				$nton = $this->generateNtoNSubQuery($node, $parent);
				$parentJoin = $nton['idcolumn'];
			}
		} else {
			$selfJoin = null;
			$parentJoin = null;
		}
		
		// crmv@146138
		// reduce the number of secondary records using the previous (already materialized) query
		$useSubqueryParentCond = false;
		if ($parent && $this->subqueryParentCondition) {
			if (!FakeModules::isFakeModule($module) && !FakeModules::isFakeModule($parent['module'])) {
				$useSubqueryParentCond = true;
			}
		}
		// crmv@146138e
		
		// process the delayed fields (they probably need the n2n table)
		/*foreach ($delayFields as $fld) {
			if ($fld['module'] == 'ProductsBlock' && $nton) {
				$fieldalias = $fld['fieldname'];
				$column = null;
				if ($fld['append_currency'] && isInventoryModule($parent['module'])) {
					$column = $adb->sql_concat(Array($parent['alias'].".currency_id","'::'",$nton['alias'].'.'.$fieldalias));
				}
				$this->alterSelectColumn($fld['idx'], array(
					'alias' => $fieldalias,
					'column' => $column,
					'table' => $nton['alias'],
					'global_alias' => $fieldalias.'@'.$nton['alias'],
				));
			}
		}*/
		
		
		$this->subQueries[$alias] = array(
			'module' => $module,
			'node' => $node,
			'alias' => $alias,
			'sql' => $sql,
			'direct' => $direct,
			'idcolumn' => $alias.'.crmid',
			'joincolumn1' => $selfJoin,
			'joincolumn2' => $parentJoin,
			'materialize' => $materialize,		// whether to use a real (temporary) table
			'useparentsubq' => $useSubqueryParentCond, // crmv@146138
		);
	}
	
	protected function alterSubquerySql(&$node, $sql, $aliasedTables = array()) {
		global $table_prefix;
		
		$module = $node['module'];
		
		if (!FakeModules::isFakeModule($module)) { // crmv@127526
			// clean joins with same alias (might happen with advanced sharing rules / sdk)
			// not super safe code...
			$tables = array("{$table_prefix}_users", "{$table_prefix}_groups");
			foreach ($tables as $table) {
				if (preg_match_all("/left join $table\s+(.*?)on/i", $sql, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
					$userAliases = array();
					$delPos = array();
					$ujoins = $matches[0];
					$aliases = $matches[1];
					if (is_array($ujoins) && count($ujoins) > 0) {
						foreach($ujoins as $i=>$uj) {
							$alias = trim($aliases[$i][0]) ?: $table;
							if (in_array($alias, $userAliases)) {
								$delPos[] = $uj[1];
							} else {
								$userAliases[] = $alias;
							}
						}
					}
					// now remove the duplicated joins, starting from the last
					$delPos = array_reverse($delPos);
					foreach($delPos as $dpos) {
						if (preg_match('/where|inner join|left join|straight_join/i', $sql, $matches, PREG_OFFSET_CAPTURE, $dpos+1)) {
							$epos = $matches[0][1];
						} else {
							$epos = strlen($sql)-1;
						}
						// cut the offending join
						$sql = substr($sql, 0, $dpos)." ".substr($sql, $epos);
					}
				}
			}

			// now clean join which should be with smownerid
			// any join with the _users table that is not the owner, has its own alias
			$sql = preg_replace("/JOIN\s+{$table_prefix}_users\s+ON\s+{$table_prefix}_crmentity.smcreatorid/i", "JOIN {$table_prefix}_users ON {$table_prefix}_crmentity.smownerid", $sql);
			
			// check for advanced sharing rules, that might introduce spurious table names without the alias
			$focus = CRMEntity::getInstance($module);
			$sharecols = $focus->getListViewAdvSecurityParameter_fields($module);
			if ($sharecols && count($sharecols) > 0) {
				foreach ($sharecols as $scol) {
					list($tab, $col, $field, $label, $type) = explode(':', $scol);
					$tabalias = null;
					// find the right alias
					foreach ($aliasedTables as $alias => $tinfo) {
						if ($tinfo['table'] == $tab && $tinfo['column'] == $col) {
							$tabalias = $alias;
							break;
						}
					}
					if ($tabalias) {
						$sql = str_replace("$tab.$col", "$tabalias.$col", $sql);
					}
				}
			}

		}
		
		return $sql;
	}
	
	/**
	 * Handle the different select for reference fields uitype10
	 */
	protected function generateSelectForReference($fld, &$aliasedTables) {
		global $adb, $table_prefix, $current_user;
		
		$sels = array();
		
		$fieldid = $fld['fieldid'];
		$module = $fld['module'];
		
		$finfo = $this->getFieldInfoById($fieldid);

		$relmods = $finfo['relmodules'];
		$modCount = count($relmods);
		
		$crmentityAlias = "crmentityRel$fieldid";
		
		// crmv@139551
		$sel = "CASE $crmentityAlias.setype ";
		foreach ($relmods as $relmod){
			$rel_obj = CRMEntity::getInstance($relmod);
			
			$efieldinfo =  $this->getEntityNameInfo($relmod);
			$rel_tab_name = $efieldinfo['tablename'];

			$tablealias = self::createRelAlias($rel_tab_name, $module, $fieldid);
			// crmv@123443
			
			$efield = $efieldinfo['fieldname'];
			if (is_array($efield)) {
				$linkField = $rel_obj->list_link_field;
			} else {
				$linkField = $efield;
			}
			$relfinfo = $this->getFieldInfoByName($relmod, $linkField);
			// crmv@123443e
			
			$link_field = $tablealias.".".$relfinfo['columnname'];
			
			// TODO read from the entityname tables, don't hardcode this crap
			if ($relmod=="Contacts" || $relmod=="Leads") {
				if(getFieldVisibilityPermission($relmod,$current_user->id,'firstname')==0){
					$link_field = $adb->sql_concat(Array($link_field,"' '",$tablealias.".firstname"));
				}
			}
			if ($modCount > 1) {
				$sel .= "WHEN '$relmod' THEN $link_field ";
			} else {
				$sel = "$link_field ";
			}

			if (!is_array($aliasedTables[$tablealias])) $aliasedTables[$tablealias] = array();
			$aliasedTables[$tablealias] = array_merge($aliasedTables[$tablealias], array(
				'table' => $rel_tab_name,
				'column' => $relfinfo['columnname']
			));
		}
		// crmv@139551e
		
		if ($modCount > 1) {
			$sel .= "END AS \"{$fld['alias']}\"";
		// crmv@139551
		} elseif ($modCount == 0) {
			$sel = $fld['tablename'].'.'.$fld['columnname']." AS {$fld['alias']}";
		// crmv@139551e
		} else {
			$sel .= "AS \"{$fld['alias']}\"";
		}
		$sels[] = $sel;
		$sels[] = "$crmentityAlias.setype as \"{$fld['alias']}_setype\"";
		$sels[] = "$crmentityAlias.crmid AS \"{$fld['alias']}_id\"";

		return $sels;
	}
	
	protected function generateJoinForReference($fld, &$joinedTables, &$aliasedTables, &$meta = null) {
		global $table_prefix;
		
		$join = "";
		$fieldid = $fld['fieldid'];
		$module = $fld['module'];
		
		$finfo = $this->getFieldInfoById($fieldid);
		
		$tableColumn = $finfo['columnname'];
		$tableColumnIdx = $finfo['columnname'];
		
		if ($this->isCalendarModule($finfo['module']) && in_array($finfo['fieldname'], array('parent_id', 'contact_id'))) {
			$tableColumn = 'activityid';
		}

		// join with the field table
		if (!in_array($finfo['tablename'], $joinedTables)) {
			$modTable = $meta->getEntityBaseTable();
			$modIdx = $meta->getIdColumn();
			$join .= " LEFT JOIN {$finfo['tablename']} ON {$finfo['tablename']}.$tableColumn = $modTable.$modIdx";
			$joinedTables[] = $finfo['tablename'];
		}

		$crmentityAlias = "crmentityRel$fieldid";
		
		$join .= " LEFT JOIN {$table_prefix}_crmentity $crmentityAlias ON $crmentityAlias.crmid = {$finfo['tablename']}.$tableColumnIdx AND $crmentityAlias.deleted = 0";
		if (!is_array($aliasedTables[$crmentityAlias])) $aliasedTables[$crmentityAlias] = array();
		$aliasedTables[$crmentityAlias] = array_merge($aliasedTables[$crmentityAlias], array(
			'table' => "{$table_prefix}_crmentity",
			'forfield' => $fld,
		));
		
		$relmods = $finfo['relmodules'];
		
		foreach ($relmods as $relmod) {

			// crmv@139551
			$entintyInfo = $this->getEntityNameInfo($relmod);		
			$rel_tab_name = $entintyInfo['tablename'];
			$rel_tab_index = $entintyInfo['entityidfield'];
			// crmv@139551e

			$tablealias = self::createRelAlias($rel_tab_name, $module, $fieldid);
			
			$join .= " LEFT JOIN $rel_tab_name $tablealias ON $tablealias.$rel_tab_index = $crmentityAlias.crmid";
			if (!is_array($aliasedTables[$tablealias])) $aliasedTables[$tablealias] = array();
			$aliasedTables[$tablealias] = array_merge($aliasedTables[$tablealias], array(
				'table' => $rel_tab_name,
				'forfield' => $fld,
			));
		}
		
		return $join;
	}
	
	// crmv@139551 crmv@144125
	protected function getEntityNameInfo($module) {
		$ENU = EntityNameUtils::getInstance();
		$einfo = $ENU->getFieldNames($module);
		return $einfo;
	}
	// crmv@139551e crmv@144125e
	
	protected function generateSelectForOwner($fld) {
		global $adb, $table_prefix, $showfullusername;	//crmv@126096
		
		$sels = array();
		
		$fieldid = $fld['fieldid'];
		$module = $fld['module'];
		
		$finfo = $this->getFieldInfoById($fieldid);
		
		$table = $finfo['tablename'];
		$column = $finfo['columnname'];
		
		list($tableAliasU, $tableAliasG) = $this->generateOwnerAlias($finfo);

		//crmv@126096
		if ($showfullusername) {
			$focusUsers = CRMEntity::getInstance('Users');
			$sel = "CASE WHEN $tableAliasU.user_name IS NOT NULL THEN ";
			$sel .= $focusUsers->formatUserNameSql($adb, $tableAliasU, $showfullusername);
			$sel .= " ELSE $tableAliasG.groupname END AS \"{$fld['alias']}\"";
			$sels[] = $sel;
		} else {
			$sels[] = "COALESCE($tableAliasU.user_name, $tableAliasG.groupname) AS \"{$fld['alias']}\"";
		}
		//crmv@126096e
		$sels[] = "CASE WHEN $tableAliasU.id IS NOT NULL THEN 'Users' WHEN $tableAliasG.groupid IS NOT NULL THEN 'Groups' END AS \"{$fld['alias']}_setype\"";
		$sels[] = "$table.$column AS \"{$fld['alias']}_id\"";

		return $sels;
	}
	
	protected function generateJoinForOwner($fld, &$joinedTables) {
		global $adb, $table_prefix;
		
		$fieldid = $fld['fieldid'];
		$finfo = $this->getFieldInfoById($fieldid);
		
		$table = $finfo['tablename'];
		$column = $finfo['columnname'];
		
		list($tableAliasU, $tableAliasG) = $this->generateOwnerAlias($finfo);

		$join = "
		LEFT JOIN {$table_prefix}_users $tableAliasU ON $tableAliasU.id = $table.$column
		LEFT JOIN {$table_prefix}_groups $tableAliasG ON $tableAliasG.groupid = $table.$column
		";
		
		return $join;
	}
	
	function addFilter(&$queryGenerator, $filter) {
		$value = $filter['value'];
		$fieldname = $filter['fieldname'];
		if ($filter['type'] == 'datefilter') {
			$operator = 'BETWEEN';
			if ($value == 'custom') {
				$value = array($filter['startdate'], $filter['enddate']);
			} else {
				$cv = CRMEntity::getInstance('CustomView'); // crmv@127526 crmv@115329
				$value = $cv->getDateforStdFilterBytype($value);
			}
			$value[0] = $this->fixDateTimeValue($queryGenerator, $fieldname, $value[0]);
			$value[1] = $this->fixDateTimeValue($queryGenerator, $fieldname, $value[1], false);
		} else {
			// new types might be added in the future
		}

		$queryGenerator->addCondition($filter['fieldname'], $value, $operator);
	}
	
	// crmv@141878 crmv@146097
	protected function addAdvFilters(&$queryGenerator, $filters, $firstGlue = '', $manualFields = array()) {
		global $current_user;

		$firstGroup = true;
		$lastGlue = $firstGlue;
		foreach ($filters as $pgroup) {
			if (is_array($pgroup['conditions'])) {
				if (count($pgroup['conditions']) > 0) {
					if ($firstGroup) {
						$queryGenerator->startGroup($lastGlue);
						$lastGlue = '';
						$firstGroup = false;
					}
					$queryGenerator->startGroup($lastGlue);
					foreach ($pgroup['conditions'] as $idx=>$flt) {
						if (in_array($flt['fieldname'], $manualFields)) {
							// field has to be handled manually!
							if ($flt['wstype'] == 'owner' || in_array($flt['uitype'], array(50,51,52,77,54))) { // crmv@180881
								$sel = $this->generateSelectForOwner($flt);
								$cond = $flt;
								$cond['rawcolumn'] = preg_replace('/ AS .*$/', '', $sel[0]);
								$cond['rawcolumn_id'] = preg_replace('/ AS .*$/', '', $sel[2]);
								$sql = $this->generateAdvSearchClause($cond);
								$queryGenerator->addRawCondition($sql);
							// crmv@149679
							} elseif ($flt['uitype'] == 10) {
								$aliasedTables = array();
								$sel = $this->generateSelectForReference($flt,$aliasedTables);
								$cond = $flt;
								$cond['rawcolumn'] = preg_replace('/ AS .*$/', '', $sel[0]);
								$sql = $this->generateAdvSearchClause($cond);
								$queryGenerator->addRawCondition($sql);
							// crmv@149679e
							//crmv@162057
							} elseif($flt['fieldname'] == 'newsletter_unsubscrpt'){
								$cond = $flt;
								$cond['rawcolumn'] = 'IF(tbl_s_newsletter_g_unsub.email IS NULL, 1, 0)';
								$cond['value'] = str_replace(getTranslatedString("yes"),"1",str_replace("no","0",$cond['value']));
								$sql = $this->generateAdvSearchClause($cond);
								$queryGenerator->addRawCondition($sql);
							//crmv@162057e
							} else {
								// TODO: support other manual fields
								continue;
							}
						} else {
							//crmv@128159
							(isset($flt['value2'])) ? $value = array($flt['value'], $flt['value2']) : $value = $flt['value'];
							$queryGenerator->addCondition($flt['fieldname'], $value, $flt['comparator'], null, false, null, false); // crmv@133387
							//crmv@128159e
						}
						if ($idx < count($pgroup['conditions'])-1) {
							$queryGenerator->addConditionGlue($flt['glue']);
						}
					}
					$queryGenerator->endGroup();
				}
				$lastGlue = $pgroup['glue'];
			}
		}
		
		if (!$firstGroup) {
			$queryGenerator->endGroup();
		}
	}
	// crmv@141878e crmv@146097e
	
	protected function fixDateTimeValue(&$queryGenerator, $name, $value, $first = true) {
		$moduleFields = $queryGenerator->getModuleFields();
		$field = $moduleFields[$name];
		// crmv@127526
		if (is_object($field)) {
			$type = $field->getFieldDataType();
		} elseif (is_array($field)) {
			$type = $field['wstype'];
		}
		// crmv@127526e
		if($type == 'datetime' || (in_array($name, array('date_start', 'due_date'))) && in_array($field->getTabId(),array(9,16))) { // crmv@149399 crmv@193031
			if(strrpos($value, ' ') === false) {
				// crmv@149399
				if($first) {
					$value .= ' 00:00:00';
				}else{
					$value .= ' 23:59:59';
				}
				$value = adjustTimezone($value, 0, null, true);
				// crmv@149399e
			}
		}
		return $value;
	}
	
	// crmv@111996
	function generateNtoNSubQuery(&$node, &$parent = null) {
		global $adb, $table_prefix;
		
		$bothDirection = false;
		$materialize = false;
		$relation = $node['relation'];
		$relinfo = $relation->relationinfo;

		$alias = $this->generateSubQueryNtoNAlias($node);

		if ($relinfo['reltab'] == $table_prefix.'_crmentityrel') {
			// I don't know in which order are the modules, I need to consider both directions
			// This might be a performance problem, but unless an order is enforced, there are no other options :(
			$bothDirection = true;
		}
		
		if ($bothDirection) {
			// materialize for non Oracle DB (the alias can be too long)
			$materialize = !$adb->isOracle();
			$wheres1 = $wheres2 = array();
			$setypeCond = "";
			if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
				$setypeCond = "AND c2.setype = '{$node['module']}'";
			}
			if ($relinfo['relmod1'] && $parent) {
				$wheres1[] = "r.{$relinfo['relmod1']} = '{$parent['module']}'";
				$wheres2[] = "r.{$relinfo['relmod2']} = '{$parent['module']}'";
			}
			if ($relinfo['relmod2'] && $node) {
				$wheres1[] = "r.{$relinfo['relmod2']} = '{$node['module']}'";
				$wheres2[] = "r.{$relinfo['relmod1']} = '{$node['module']}'";
			}
			$sql = 
				"SELECT r.{$relinfo['relidx']}, r.{$relinfo['relidx2']} FROM {$relinfo['reltab']} r
				INNER JOIN {$table_prefix}_crmentity c2 ON c2.crmid = r.{$relinfo['relidx2']} $setypeCond AND c2.deleted = 0
				".(count($wheres1) > 0 ? "WHERE ".implode(' AND ', $wheres1) : "")."
				UNION
				SELECT r.{$relinfo['relidx2']} AS {$relinfo['relidx']}, r.{$relinfo['relidx']} AS {$relinfo['relidx2']} FROM {$relinfo['reltab']} r
				INNER JOIN {$table_prefix}_crmentity c2 ON c2.crmid = r.{$relinfo['relidx']} $setypeCond AND c2.deleted = 0
				".(count($wheres2) > 0 ? "WHERE ".implode(' AND ', $wheres2) : "");
			if (!$materialize) {
				// enclose it in brackets if not materialized
				$sql = "($sql) $alias";
			}
		} else {
			$sql = "{$relinfo['reltab']} $alias";
		}
		
		$selfJoin = $alias.".{$relinfo['relidx']}";
		$parentJoin = $parent['alias'].'.crmid';
		
		$nton = array(
			'module' => $node['module'],
			'node' => $node,
			'alias' => $alias,
			'sql' => $sql,
			'direct' => true,
			'idcolumn' => $alias.".{$relinfo['relidx2']}",
			'joincolumn1' => $selfJoin,
			'joincolumn2' => $parentJoin,
			'materialize' => $materialize,
		);

		$this->subQueries[$alias] = $nton;
		
		return $nton;
	}
	// crmv@111996e
	
	function generateOwnerAlias(&$finfo) {
		global $table_prefix;
		
		if ($finfo['fieldname'] == 'assigned_user_id') {
			// keep the table name for compatibility
			$tableAliasU = "{$table_prefix}_users";
			$tableAliasG = "{$table_prefix}_groups";
		} else {
			$tableAliasU = "users_f".$finfo['fieldid'];
			$tableAliasG = "groups_f".$finfo['fieldid'];
		}
		
		return array($tableAliasU, $tableAliasG);
	}
	
	function generateSubQueryNtoNAlias(&$node) {
		$alias = 'rel_'.$node['relationid'];
		return strtolower($alias);
	}
	
	function generateSubQueryAlias(&$node, $suffix = '') { // crmv@148915
		$alias = substr($node['module'], 0, 20).'_rel_';
		if (!$node['parent']) {
			// main module
			$alias = substr($node['module'], 0, 30);
		} elseif ($node['type'] == ModuleRelation::$TYPE_NTON) {
			$alias .= "r".$node['relationid'];
		// crmv@121372
		} elseif ($node['relation'] && $node['relation']->getFirstModule() == $node['relation']->getSecondModule()) {
			$type = $node['relation']->getType();
			$alias .= "f".$node['fieldid']."_".$type;
		// crmv@121372e
		} else {
			$alias .= "f".$node['fieldid'];
		}
		return strtolower($alias.$suffix); // crmv@148915
	}
	
	function walkRecursiveTree(&$root, $subprop, $callback, &$parent = null, $level = 0) {
		if ($root) {
			call_user_func_array($callback, array(&$root, &$parent));
			if ($root[$subprop]) {
				foreach($root[$subprop] as &$child) {
					$this->walkRecursiveTree($child, $subprop, $callback, $root, $level + 1);
				}
			}
		}
	}
	
	function prepareSelectColumn($col) {
		$sel = false;
		
		// crmv@128369
		if ($col['fieldname'] == 'cluster') {
			// the query is available only after the subqueries have been prepared
			$cinfo = $this->getClusterColumnInfo();
			$col['column'] = $cinfo['column'];
		}
		// crmv@128369e

		if ($col['column']) {
			$sel = $col['column'];
		} elseif ($col['table'] && $col['alias']) {
			$sel = $col['table'].'.'.$col['alias'];
		}
		if ($sel) {
			if ($col['formula']) {
				$sel = $this->apply_function($sel, $col['formula']);
			}
			if ($col['global_alias']) {
				$sel .= " AS \"{$col['global_alias']}\"";
			}
		}
		return $sel;
	}

	// crmv@101490
	function reorderColumns(&$colList) {
		// reorder according to "after_column"
		
		$list = $tomove = array();
		foreach ($colList as $idx => $col) {
			if ($col['after_column']) {
				$tomove[] = $col;
			} else {
				$list[] = $col;
			}
		}
		if (count($tomove) > 0) {
			while (count($tomove) > 0) {
				$movecol = array_pop($tomove);
				// search for the after column
				$found = false;
				foreach ($list as $idx => $lcol) {
					if ($lcol['global_alias'] == $movecol['after_column']) {
						array_splice($list, $idx+1, 0, array($movecol));
						$found = true;
						break;
					}
				}
				if (!$found) {
					// add to the end anyway
					$list[] = $movecol;
				}
			}
			$colList = array_values($list);
		}
		
		return $colList;
	}
	// crmv@101490e
	
	// crmv@128369
	protected function getClusterColumn($alias = 'cluster') {
		global $adb;
		
		if (count($this->clusters) == 0) return '';
		
		$col = "CASE \n";
		foreach ($this->clusters as $cid => $cluster) {
			$where = '';
			if (is_array($cluster['conditions'])) {
				$countGrp = count($cluster['conditions']);
				$grpidx = 0;
				foreach ($cluster['conditions'] as $group) {
					
					$countCond = count($group['conditions']);
					$condidx = 0;
					$grpsql = "";
					foreach ($group['conditions'] as $cond) {
						$cond['value'] = $this->convertAdvSearchValue($cond);
						$sql = $this->generateAdvSearchClause($cond);
						if (!$sql) continue;
						
						$grpsql .= $sql;
						if ($cond['glue'] && $condidx < ($countCond-1)) {
							$glue = ($cond['glue'] == 'or' ? 'OR' : 'AND');
							$grpsql .= " $glue ";
						}
						
						++$condidx;
					}
					
					$grpsql = trim($grpsql);
					if ($grpsql) {
						$where .= "( $grpsql )";
						if ($group['glue'] && $grpidx < ($countGrp-1)) {
							$glue = ($group['glue'] == 'or' ? 'OR' : 'AND');
							$where .= " $glue ";
						}
					}
					
					++$grpidx;
				}

				$where = trim($where);
				if ($where != "" && $countGrp > 1) $where = '('.$where.')';
			}
			$col .= "WHEN $where THEN '".$adb->sql_escape_string($cluster['name'])."'\n";
		}
		$col .= " ELSE NULL END";
		if ($alias) {
			$col .= " AS $alias";
		}
		
		return $col;
	}
	// crmv@128369e
	
	function getSelectSql() {
		// fields
		$select = "";
		$selectArray = array();
		
		if ($this->reportTab == 'COUNT') {
			$colList = $this->summarycolumns;
		} elseif ($this->reportTab == 'MAIN') {
			$colList = $this->columns;
		} elseif ($this->reportTab == 'TOTAL') {
			$colList = $this->totalcolumns;
		} elseif ($this->reportTab == 'CV') {
			$colList = $this->columns;
		}
		
		// crmv@101490
		if ($this->reportTab == 'MAIN') {
			$this->reorderColumns($colList);
		}
		// crmv@101490e
		
		if ($this->reportTab == 'CV') {
			// customview query
			foreach ($colList as $col) {
				if ($col['global_alias'] && preg_match('/^id@tab_[0-9]+$/', $col['global_alias'])) {
					$sel = $this->prepareSelectColumn($col);
					if ($sel) {
						$selectArray[] = $sel;
					}
				}
			}
		} else {
			foreach ($colList as $col) {
				$sel = $this->prepareSelectColumn($col);
				if ($sel) {
					$selectArray[] = $sel;
				}
			}
		}
		
		$selectArray = array_unique($selectArray);
		
		$select = implode(",", $selectArray);
		return $select;
	}
	
	function getFromSql() {
		$from = '';
		
		foreach ($this->subQueries as &$sq) {
			if ($sq['joincolumn1'] && $sq['joincolumn2']) {
				$from .= "LEFT JOIN ";
			}
			
			if ($sq['materialize']) {
				$this->materializeSubquery($sq);
				$from .= "{$sq['temp_table']} {$sq['alias']} ";
			} elseif ($sq['direct']) {
				$from .= "{$sq['sql']} ";
			} else {
				$from .= "( {$sq['sql']} ) {$sq['alias']} ";
			}
			
			if ($sq['joincolumn1'] && $sq['joincolumn2']) {
				$from .= "ON {$sq['joincolumn1']} = {$sq['joincolumn2']}";
			}
			
			$from .= "\n";
		}
		
		return $from;
	}
	
	public function reuseSubqueries() {
		$this->reuseMaterialized = true;
	}
	
	public function resetSubqueries() {
		$this->reuseMaterialized = false;
	}
	
	protected function materializeSubquery(&$sq) {
		global $adb;
		
		$useTemp = PerformancePrefs::getBoolean('USE_TEMP_TABLES', true);
		
		// name for the "temporary" table
		$name = $this->chooseMaterializedName($sq);
		
		// set the name of the temp table
		$sq['temp_table'] = $name;

		$opts = "";
		if ($adb->isMysql()) {
			// mysql tip: myisam is faster (for this use case) than innodb, enable it if needed.
			//$opts = "ENGINE=MyISAM";
		}
		
		// crmv@146138
		// add a piece to the query to extract only the linked records
		$joinQuery = $this->getMaterializedSubCondition($sq);
		if ($joinQuery) {
			$sq['sql'] .= ' '.$joinQuery;
		}
		
		$VP = VTEProperties::getInstance();
		if ($VP->get('performance.show_query_stats') == 1 && $_REQUEST['show_query'] == 'true') {
			echo "MODULE SUBQUERY ({$sq['node']['module']}):<br>\n";
			echo $sq['sql']."<br>\n<br>\n";
		}
		// crmv@146138e
		
		// crmv@129940
		if ($adb->isMssql()) {
			// sql server version (temp tables start with #)
			if ($useTemp) {
				$name = $adb->datadict->changeTableName($name);
				$sq['temp_table'] = $name;
			}
			
			$sql = preg_replace('/\s+from\s+/i', " INTO $name FROM ", $sq['sql'], 1);
		} else {
			// standard creation query
			$sql = "CREATE ".($useTemp ? 'TEMPORARY ' : '')."TABLE $name $opts AS {$sq['sql']}";
		}
		// crmv@129940e

		// crmv@146138
		// subquery cache - works only if the query is the same!
		if ($this->materializeCache[$name]['sql'] == $sql) {
			return;
		} else {
			$this->materializeCache[$name] = array(
				'sql' => $sql,
				'alias' => $sq['alias'],
				'temp_table' => $name,
				'node' => $sq['node']['name'],
			);
		}
		// crmv@146138e
		
		// drop the old table
		// crmv@185894
		if ($this->cacheDb) {
			$shortName = str_replace($this->cacheDb.'.', '', $name);
			$dbname = $this->cacheDb;
		} else {
			global $dbconfig;
			$shortName = $name;
			$dbname = $dbconfig['db_name'];
		}
		$res = $adb->pquerySlave('Reports', ($adb->isOracle()) ? "SELECT count(*) as cnt FROM all_tables WHERE owner = ? and table_name = ?" : "SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?", array($dbname, $shortName));
		$cnt = $adb->query_result_no_html($res, 0, 'cnt');
		if ($cnt > 0) {
			// removed if crmv@99131 crmv@129940
			if ($this->reuseMaterialized) return;
			$sqlarray = $adb->DropTableSQLSlave('Reports',$shortName,$dbname);
			$adb->ExecuteSQLArraySlave('Reports',$sqlarray);
		}
		// crmv@185894e
		
		// execute the query, creating the (temp) table
		$adb->querySlave('Reports',$sql); // crmv@185894

		// in case other DB don't support creating a table from a query, 
		// select 1 row and use the following to get the datataype of each column
		//$defs = $adb->getFieldsDefinition($res);

		// now create a primary key (faster to do it at the end than before inserting the rows)
		if ($sq['joincolumn1']) {
			$primary = false;
			$joincol = preg_replace('/^.*\./', '', $sq['joincolumn1']);
		} elseif (!$sq['parent'] && $sq['idcolumn']) {
			// the root query, use the main id
			if ($this->isCalendarModule($sq['module']) && (stripos($sq['sql'], "{$table_prefix}_seactivityrel") > 0 || stripos($sq['sql'], "{$table_prefix}_cntactivityrel") > 0)) {
				// this stupid calendar - contact relation is not 1-n, so crmids are duplicated
				$primary = false;
			} else {
				$primary = true;
			}
			$joincol = preg_replace('/^.*\./', '', $sq['idcolumn']);
		}
		if ($primary) {
			$adb->format_columns($joincol);
			$adb->querySlave('Reports',"ALTER TABLE $name ADD PRIMARY KEY ($joincol)"); // crmv@185894
		} else {
			$idxsql = $adb->CreateIndexSQLSlave('Reports', $name.'_key', $name, $joincol); // crmv@185894
			if ($joincol && $idxsql) {
				$adb->ExecuteSQLArraySlave('Reports', $idxsql, true, false); // crmv@129940 crmv@185894
			}
			// TODO: might be useful to create a primary key instead, but only if
			// it's unique, maybe using the idcolumn ??
		}
		
		// now create indexes for the parent join column
		if ($sq['joincolumn2']) {
			list($parentAlias, $fieldAlias) = explode('.', $sq['joincolumn2']);
			$parentq = $this->getSubqueryFromAlias($parentAlias);
			if ($parentq && $fieldAlias) {
				$this->addMaterializedIndex($parentq, array('alias' => $fieldAlias));
			}
		}
		
	}
	
	// crmv@146138
	protected function getMaterializedSubCondition($sq) {
		global $table_prefix;
		
		$joinQuery = '';
		
		if ($this->subqueryParentCondition && $sq['useparentsubq'] && $sq['node']['parent']) {
			$relType = $sq['node']['type'];
			// find parent info
			foreach ($this->materializeCache as $name => $info) {
				if ($info['node'] == $sq['node']['parent'] && !preg_match('/^rel_/', $info['alias'])) { // crmv@178597
					$parentInfo = $info;
					break;
				}
			}
			$parentTable = $parentInfo['temp_table'];
			$parentAlias = $parentInfo['alias'];
			if ($relType == ModuleRelation::$TYPE_NTO1 && $parentTable) {
				$joinCol2 = str_replace($parentAlias.'.', '', $sq['joincolumn2']);
				$joinQuery = "AND {$table_prefix}_crmentity.crmid IN (SELECT $joinCol2 FROM $parentTable)";
			} elseif ($relType == ModuleRelation::$TYPE_1TON && $parentTable) {
				$fieldid = $sq['node']['fieldid'];
				$finfo = $this->getFieldInfoById($fieldid);
				$focus = CRMEntity::getInstance($sq['module']);
				$tableIdx = $focus->tab_name_index[$finfo['tablename']];
				if ($tableIdx) {
					// crmv@203132
					if (in_array($table_prefix.'_crmentity', $focus->tab_name)) {
						$joinQuery = "AND {$table_prefix}_crmentity.crmid IN (
							SELECT {$finfo['tablename']}.$tableIdx FROM {$finfo['tablename']}
							INNER JOIN $parentTable $parentAlias ON {$sq['joincolumn2']} = {$finfo['tablename']}.{$finfo['columnname']}
						)";
					} else {
						$joinQuery = "AND {$focus->table_name}.{$focus->table_index} IN (
							SELECT {$finfo['tablename']}.$tableIdx FROM {$finfo['tablename']}
							INNER JOIN $parentTable $parentAlias ON {$sq['joincolumn2']} = {$finfo['tablename']}.{$finfo['columnname']}
						)";
					}
					// crmv@203132e
				}
			}
		}
		
		return $joinQuery;
	}
	// crmv@146138e
	
	protected function chooseMaterializedName($sq) {
		global $table_prefix, $current_user;
		$sqpart = implode('_', array_slice(explode('_', $sq['alias']), -2));
		if (substr($sqpart, 0, 4) == 'rel_' && $sq['module']) {
			// add also the target module, since i can have multiple relations from the same field
			$sqpart .= '_'.getTabid($sq['module']);
		}
		// crmv@104014
		// this is necessary, otherwise the ajax calls might use again the total table, which has different columns
		if ($this->reportTab == 'TOTAL') {
			$sqpart = 't_'.$sqpart;
		}
		// crmv@104014e
		$name = ($this->cacheDb ? $this->cacheDb.'.' : '').$table_prefix."_rep_subq_{$current_user->id}_{$this->reportid}_$sqpart"; // crmv@185894
		return $name;
	}
	
	protected function addMaterializedIndex($sq, $colinfo) {
		global $adb;
		
		$table = $sq['temp_table'];
		$column = $colinfo['alias'];
		$indexName = $table.'_'.$column.'_idx';
		
		if (!$table) return;
		
		$adbSlave = $adb->getSlaveObject('Reports'); // crmv@185894
		
		// crmv@129978
		// extract the columns without peardatabase, otherwise I can't have the type
		$cols = array_change_key_case($adbSlave->database->MetaColumns($table), CASE_LOWER); // crmv@185894
		
		//check if column exists
		if (array_key_exists($column, $cols)) return;
		
		$colInfo = $cols[$column];
		if ($colInfo->type == 'text') {
			if ($adb->isMySql()) {
				// limit the index to 100 chars
				$column .= '(100)';
			} else {
				// no index possible for these columns with other dbs
				return;
			}
		}
		// crmv@129978e

		// check if index exists
		// crmv@185894
		$indexes = $adbSlave->database->MetaIndexes($table);
		if (!$indexes || !array_key_exists($indexName, $indexes)) {
			$indexsql = $adbSlave->datadict->CreateIndexSQL($indexName, $table, $column);
			if ($indexsql) {
				$adbSlave->datadict->ExecuteSQLArray($indexsql, true, false); // crmv@129940
			}
		}
		// crmv@185894e
	}
	
	function getWhereSql() {
		global $adb, $current_user; // crmv@128369 crmv@143750
		
		$where = "";
		
		// crmv@143750
		if (!empty($this->stdfilters)) {
			$stdWhere = '';
			foreach ($this->stdfilters as $filter) {
				if ($filter['type'] == 'datefilter') {
					$module = $filter['module'];
					if (FakeModules::isFakeModule($module)) {
						$queryGenerator = FakeQueryGenerator::getInstance($module, $current_user, $this, $this->reports);
					} else {
						$queryGenerator = QueryGenerator::getInstance($module, $current_user);
					}
					$operator = 'BETWEEN';
					if ($filter['value'] == 'custom') {
						$value = array($filter['startdate'], $filter['enddate']);
					} else {
						$cv = CRMEntity::getInstance('CustomView');
						$value = $cv->getDateforStdFilterBytype($filter['value']);
					}
					$value[0] = $this->fixDateTimeValue($queryGenerator, $filter['fieldname'], $value[0]);
					$value[1] = $this->fixDateTimeValue($queryGenerator, $filter['fieldname'], $value[1], false);
					$stdWhere .= "({$filter['table']}.{$filter['alias']} BETWEEN '{$value[0]}' AND '{$value[1]}') ";
				} else {
					// other types are not supported
				}
			}
			if ($where) $where .= 'AND ';
			if ($stdWhere) $where .= $stdWhere;
		}
		
		if (!empty($this->advfilters)) {
			$advWhere = '';
			$countGrp = count($this->advfilters);
			$grpidx = 0;
			foreach ($this->advfilters as $group) {
				
				$countCond = count($group['conditions']);
				$condidx = 0;
				$grpsql = "";
				foreach ($group['conditions'] as $cond) {
				
					$cond['value'] = $this->convertAdvSearchValue($cond);
					$sql = $this->generateAdvSearchClause($cond);
					if (!$sql) continue;
					
					$grpsql .= $sql;
					if ($cond['glue'] && $condidx < ($countCond-1)) {
						$glue = ($cond['glue'] == 'or' ? 'OR' : 'AND');
						$grpsql .= " $glue ";
					}
					
					++$condidx;
				}
				
				$grpsql = trim($grpsql);
				if ($grpsql) {
					$advWhere .= "( $grpsql )";
					if ($group['glue'] && $grpidx < ($countGrp-1)) {
						$glue = ($group['glue'] == 'or' ? 'OR' : 'AND');
						$advWhere .= " $glue ";
					}
				}
				
				++$grpidx;
			}

			$advWhere = trim($advWhere);
			if ($advWhere != "" && $countGrp > 0) {
				$advWhere = '('.$advWhere.')';
			
				if ($where) $where .= 'AND ';
				$where .= $advWhere;
			}
		}
		// crmv@143750e
		
		// all columns search
		if (!empty($this->querySearch)) {
			$searchConds = array();
			$cond = array(
				'comparator' => 'c', // contains
				'glue' => 'or',
			);
			foreach($this->columns as $cinfo) {
				unset($cinfo['glue'], $cinfo['comparator']);
				$cond = array_merge($cond, $cinfo);
				$cond['value'] = $this->querySearch;
				$cond['value'] = $this->convertAdvSearchValue($cond);
				$sql = $this->generateAdvSearchClause($cond);
				if ($sql) {
					$searchConds[] = $sql;
				}
			}
			if (count($searchConds) > 0) {
				$searchsql = '('.implode(" {$cond['glue']} ", $searchConds).')';
				if ($where) $where .= " AND ";
				$where .= $searchsql;
			}
		}
		
		// single column searches
		if (!empty($this->querySearchCols)) {
			$searchConds = array();
			$cond = array(
				'comparator' => 'c',
				'glue' => 'and',
			);
			foreach($this->querySearchCols as $searchinfo) {
				$column = $searchinfo['column'];
				// crmv@128369
				// TODO: si può togliere ?
				if ($column == 'cluster') {
					$sql = $this->getClusterColumn(false);
					$sql .= " LIKE '%".$adb->sql_escape_string($searchinfo['search'])."%'";
					$searchConds[] = $sql;
					continue;
				}
				// crmv@128369e
				$cinfo = $this->getColumnInfoFromAlias($column);
				if ($cinfo) {
					unset($cinfo['glue'], $cinfo['comparator']);
					$cond = array_merge($cond, $cinfo);
					$cond['value'] = $searchinfo['search'];
					$cond['value'] = $this->convertAdvSearchValue($cond);
					$sql = $this->generateAdvSearchClause($cond);
					if ($sql) {
						$searchConds[] = $sql;
					}
				}
			}
			if (count($searchConds) > 0) {
				$searchsql = '('.implode(" {$cond['glue']} ", $searchConds).')';
				if ($where) $where .= " AND ";
				$where .= $searchsql;
			}
		}
		
		return $where;
	}
	
	function getOrderSql() {
		global $adb;
		
		if ($this->reportTab == 'TOTAL' || $this->reportTab == 'CV') return "";
		
		$quoteChar = '"';
		if ($adb->isMysql()) $quoteChar = '`';
		
		$order = "";
		$orderArray = array();
		$orderAliases = array();
		
		$list = &$this->columns;
		if ($this->reportTab == 'COUNT') $list = &$this->summarycolumns;
		
		// single column ordering
		if (!empty($this->queryOrdering)) {
			foreach ($this->queryOrdering as $ordinfo) {
				$cinfo = $this->getColumnInfoFromAlias($ordinfo['column']); // crmv@128369
				$dir = (strtolower($ordinfo['dir']) == 'desc' ? 'DESC' : 'ASC');
				
				if ($cinfo) {
					$colname = $cinfo['order_alias'] ?: $cinfo['global_alias']; // crmv@129978
					// when in summary mode I can change the default order, thus changing the groups
					if ($colname && !in_array($colname, $orderAliases)) {
						$orderAliases[] = $colname;
						$orderArray[] = "$quoteChar{$colname}$quoteChar $dir";
					}
				}
			}
		}
		
		foreach ($list as $col) {
			if (!$col['group']) continue;

			$dir = 'ASC';
			if ($col['sortorder']) $dir = strtoupper($col['sortorder']);
			
			// crmv@129978
			$ordCol = $col['order_alias'] ?: $col['global_alias'];
			if ($ordCol && !in_array($ordCol, $orderAliases)) {
				$ord = "$quoteChar{$ordCol}$quoteChar $dir";
				$orderAliases[] = $ordCol;
				$orderArray[] = $ord;
			}
			// crmv@129978e
		}
		
		// check in materialized subqueries and add an index on the order column
		foreach ($orderAliases as $alias) {
			$col = $this->getColumnInfoFromAlias($alias);
			if ($col && $col['table']) {
				$subq = $this->getSubqueryFromAlias($col['table']);
				if ($subq && $subq['materialize'] && $subq['temp_table'] && $col['wstype'] != 'text') {
					$this->addMaterializedIndex($subq, $col);
				}
			}
		}

		$order = implode(', ', $orderArray);
		
		return $order;
	}
	
	public function sGetSQLforReport() {
		
		/*
		1. estrai i moduli necessari dai campi
		2. genera le subquery
			-> select campi che mi servono
			-> join che mi servono
			-> where del modulo
		3. genera i campi nella select (fatto anche dalle singole subquery)
		4. incolla assieme le subquery (1 flat level o ricorsivo?)
		5. genera condizioni globali
		6. ordinamento/raggruppamento
		*/
		
		$this->prepareReportInfo();

		$this->prepareQueryPlan();
		$this->prepareSubQueries();
		
		// now create the main query
		$select = $this->getSelectSql();
		$from = $this->getFromSql();
		$where = $this->getWhereSql();
		if ($this->reportTab != 'COUNT') $orderby = $this->getOrderSql(); // crmv@165479
		
		$sql = "SELECT\n $select\n FROM\n $from\n";
		if ($where) $sql .= " WHERE\n $where\n";
		if ($orderby) $sql .= " ORDER BY\n $orderby";
		
		//crmv@show_query
		if ($this->outputFormat == 'HTML' && $this->reportTab == 'MAIN') {
			VteSession::set('query_show', $sql);
			
			// DEBUG: print query
			//echo "<pre>".preg_replace('/left|inner|straight_join|where|from|,/i', '<br>\0', $sql)."</pre>";
			//die();
		}
		
		// crmv@148336 crmv@165479
		if ($this->reportTab == 'COUNT') {
			$sql = $this->prepareGroupingQuery($sql);
		} elseif ($this->reportTab == 'TOTAL') {
			$sql = $this->prepareTotalQuery($sql);
		}
		// crmv@148336e crmv@165479e
		
		$this->generatedQuery[$this->reportTab] = $sql;

		return $sql;
	}
	
	// crmv@172034
	/**
	 * Standard implementation to create the count query
	 * Doesn't work with subqueries in the main SELECT
	 */
	public function sGetCountSQLforReport($sql) {
		return replaceSelectQuery($sql);
	}
	// crmv@172034e
	
	// crmv@148336
	/*
	The idea is to execute a query for each module:
	
	SELECT
		0,
		...
		0,
		OPERATION1(field) as field#oper1,
		OPERATION2(field2) AS field2#oper2
		0,
		... (padded with 0s to reach the total number of fields)
		0
	FROM ( 
		SELECT field, field2 FROM ( 
			standard full query (can be materialized)
		) tot_temp
		GROUP BY id@module, field, field2
	) tot_temp_2; 
	
	Then put all of them in a big matrix using union (if more than 1 module):
	
	SELECT 0 as alias1, 0 as alias2, 0 as alias3 ... (only to have sane names)
	UNION ALL
	SELECT field1, 	field2, field3, 0, 		0, 		0
	UNION ALL
	SELECT 0, 		0, 		0, 		field1, field2, 0
	UNION ALL
	SELECT 0, 		0, 		0, 		0, 		0, 		field3
	...
		
	And then sum them, to have only 1 final row, with fields from different modules
	
	SELECT SUM(alias1) as alias1, SUM(alias2) as alias2...
	
	and voilà!
	*/
	protected function prepareTotalQuery($sql) {
		global $adb;
		
		// materialize the global query, so it's executed only once
		if ($this->subqueryMaterialization) {
			$sq = array(
				'node' => array(
					'module' => 'Total',
					'name' => 'Total',
				),
				'alias' => 'total',
				'sql' => $sql,
			);
			$this->materializeSubquery($sq);
			$fromTotTable = $sq['temp_table'];
		} else {
			$fromTotTable = "($sql) tot_temp";
		}
		
		// group columns by relation
		$colByRel = array();
		$totcols = 0;
		foreach ($this->totalcolumns as $col) {
			if ($col['aggregator']) {
				$colByRel[$col['relation']][] = $col;
				++$totcols;
			}
		}
		
		if ($totcols == 0) return $sql;

		$queries = array();
		$fullaliases = array();
		$sumaliases = array();
		$index = 0;
		foreach ($colByRel as $relation => $cols) {

			// prepare the columns to extract
			$collist = array();
			foreach ($cols as $col) {
				$collist[] = $col['global_alias'];
			}
			$collist = array_unique($collist);
			$adb->format_columns($collist, true);
			
			// get the id column from the first column
			$groupcol = "id@".$cols[0]['table'];
			$adb->format_columns($groupcol, true);
			
			// prepare the basic query
			$query = "SELECT ".implode(', ', $collist)." FROM $fromTotTable GROUP BY $groupcol";
			if ($adb->isMssql() || $adb->isOracle()) {
				$query .= ",".implode(', ', $collist);
			}

			// now prepare the upper query 
			$list = array();
			
			// add initial empty columns
			if ($index > 0) {
				$list = array_merge($list, array_fill(0, $index, '0'));
			}
			
			foreach ($cols as $col) {
				$colAlias = $col['global_alias'];
				$globalAlias = $colAlias.'#'.strtolower($col['aggregator']);
				$fullaliases[] = "0 AS \"$globalAlias\"";
				$globalalias2 = $globalAlias;
				$adb->format_columns($globalalias2, true);
				$sumaliases[] = "SUM($globalalias2) as \"$globalAlias\"";
				$adb->format_columns($colAlias, true);
				
				$list[] = $this->prepareTotalColumn($colAlias, $col) . " as \"$globalAlias\"";
				++$index;
				
				// set the new alias for the cell generation
				$this->alterSelectColumn($col['idx'], array(
					'global_alias' => $globalAlias,
				));
			}
			
			// add final empty fields now
			if ($index < $totcols) {
				$list = array_merge($list, array_fill(0, $totcols-$index, '0'));
			}
			
			$query = "SELECT ".implode(', ', $list)." FROM ($query) tot_temp_2";
			$queries[] = $query;
		}

		// prepare the unions (just to have all the results in the same row)
		if (count($queries) > 1) {
			// add first row of aliases
			array_unshift($queries, "SELECT ".implode(', ', $fullaliases)).($adb->isOracle() ? " FROM DUAL" : "");
			// join then
			$query = implode("\nUNION ALL\n", $queries);
			// and now the final query!
			$query = "SELECT ".implode(', ', $sumaliases)." FROM ($query) tot_temp_3";
		} else {
			$query = $queries[0];
		}
		
		return $query;
	}
	
	public function prepareTotalColumn($column, $fld) {
		global $adb;
		
		$operator = strtoupper($fld['aggregator']);

		// crmv@129940
		if ($fld['module'] == 'Timecards' && $fld['fieldname'] == 'worktime') {
			if ($adb->isMssql()) {
				$column = $operator.'(DATEDIFF(SECOND, 0, '.$column.'))';
			} else {
				$column = $operator.'(TIME_TO_SEC('.$column.'))';
			}
		// crmv@129940e
		} else {
			$column = $operator.'('.$column.")";
		}
		
		return $column;
	}
	// crmv@148336e

	//crmv@29686
	function hasSummary() {
		if (is_array($this->columns)) {
			foreach ($this->columns as $col) {
				if ($col['summary']) return true;
			}
		}
		if (is_array($this->clusters) && count($this->clusters) > 0 && $this->reporttype == 'summary') return true; // crmv@128369 crmv@167234
		return false;
	}

	function hasTotals() {
		return (!empty($this->totalcolumns));
	}
	//crmv@29686e
	
	public function getGeneratedQuery($tab = null) { // crmv@98894
		if (empty($tab)) $tab = $this->reportTab;
		return $this->generatedQuery[$tab];
	}

	public function setQueryLimit($start, $end = null) {
		if (!$end) $end = $start += $this->pageSize;
		$this->queryLimit = array($start, $end);
	}
	
	public function setDefaultQueryLimit() {
		$this->queryLimit = array(0, $this->pageSize);
	}
	
	public function clearQueryLimit() {
		$this->queryLimit = null;
	}
	
	public function setQueryOrdering($ordering) {
		$this->queryOrdering = $ordering;
	}
	
	public function clearQueryOrdering() {
		$this->queryOrdering = null;
	}
	
	public function setQuerySearch($text) {
		$this->querySearch = $text;
	}
	
	public function cleanQuerySearch() {
		$this->querySearch = null;
	}
	
	public function setQuerySearchColumns($searchCols) {
		$this->querySearchCols = $searchCols;
	}
	
	public function cleanQuerySearchColumns() {
		$this->querySearchCols = null;
	}
	
	public function setCVInfo($info) {
		$this->cvInfo = $info;
	}

	function getOutputClass() {
	
		$oformat = $this->outputFormat;
		$tab = $this->reportTab;
		$direct = $this->directOutput;

		$cachekey = $oformat.'_'.$tab;
		if ($this->outputClass[$cachekey]) return $this->outputClass[$cachekey];
	
		if ($oformat == 'JSON') {
			$output = ReportOutputJson::getInstance();
		} elseif ($oformat == "HTML" || $oformat == "PDF") {
			if ($direct) {
				$output = ReportOutputHTMLDirect::getInstance();
			} else {
				$output = ReportOutputHTML::getInstance();
			}
		} elseif ($oformat == "PRINT") {
			$output = ReportOutputHTML::getInstance();
		} elseif ($oformat == "XLS") {
			$output = ReportOutputArray::getInstance();
		} elseif ($oformat == "NULL") {
			$output = ReportOutputNull::getInstance();
		}
		
		// set the table id for html output
		if (is_a($output, 'ReportOutputHTML')) {
			if ($tab == "TOTAL") {
				$output->setTableId('tableContentTotal');
			} elseif ($tab == "COUNT") {
				$output->setTableId('tableContentCount');
			} else {
				$output->setTableId('tableContentMain');
			}
		}
		
		$this->outputClass[$cachekey] = $output;
		return $this->outputClass[$cachekey];
	}
	
	// crmv@134727
	/**
	 * Save some basic statistics for the report
	 */
	public function setReportStats($rows) {
		global $adb, $table_prefix, $current_user;
		
		if ($this->reportid > 0) {
			$res = $adb->pquery("SELECT reportid FROM {$table_prefix}_report_stats WHERE reportid = ? AND userid = ?", array($this->reportid, $current_user->id));
			// crmv@188001
			$rowCol = $adb->format_column('rows');
			if ($res && $adb->num_rows($res) > 0) {
				$params = array(date('Y-m-d H:i:s'), $rows, $this->reportid, $current_user->id);
				$adb->pquery("UPDATE {$table_prefix}_report_stats SET generatedtime = ?, $rowCol = ? WHERE reportid = ? AND userid = ?", $params);
			} else {
				$params = array($this->reportid, $current_user->id, date('Y-m-d H:i:s'), $rows);
				$adb->pquery("INSERT INTO {$table_prefix}_report_stats (reportid, userid, generatedtime, $rowCol) VALUES (".generateQuestionMarks($params).")", $params);
			}
			// crmv@188001e
		}
	}
	// crmv@134727e
	
	/** 
	 * Function to get the report output 
	 *
	 */
	function GenerateReport($outputformat = "", $filterlist = null, $directOutput=false) {
		global $php_max_execution_time;
		global $adb, $table_prefix, $current_user;
		
		// compatibility, please use set them with the proper methods
		if (!empty($outputformat)) {
			$format = "HTML";
			$tab = "MAIN";
		
			if (strpos($outputformat, 'HTML') !== false) $format = "HTML";
			if (strpos($outputformat, 'PRINT') !== false) $format = "PRINT";
			if (strpos($outputformat, 'PDF') !== false) $format = "PDF";
			if (strpos($outputformat, 'XLS') !== false) $format = "XLS";
			if (strpos($outputformat, 'JSON') !== false) $format = "JSON";
			if (strpos($outputformat, 'CV') !== false) $format = "NULL";
			
			if (strpos($outputformat, 'COUNT') !== false) $tab = "COUNT";
			if (strpos($outputformat, 'TOTAL') !== false) $tab = "TOTAL";
			if (strpos($outputformat, 'CV') !== false) $tab = "CV";
			
			$this->setOutputFormat($format, $directOutput);
			$this->setReportTab($tab);
		} else {
			$format = $this->outputFormat;
			$tab = $this->reportTab;
		}
		
		$format = $this->outputFormat;
		$direct = $this->directOutput;
		$tab = $this->reportTab;
		
		$this->enableCacheDb($tab); // crmv@185894

		// prepare the output class
		$output = $this->getOutputClass();
		$output->clearAll();
		
		$return_data = array();
		
		//crmv@29686

		if ($tab == 'COUNT' && $this->hasSummary()) {

			$sSQL = $this->sGetSQLforReport();
			
			$result = $adb->querySlave('Reports',$sSQL); // crmv@185894
			$this->total_count = $adb->num_rows($result);
			
			$options = array();
			if ($format == "XLS") $options['format_numbers'] = false;
			
			$this->groupingOutputHeader($result, $output, $options);
			$this->groupingOutputData($result, $output, $options);
			
			// change the output array to match the expected format for XLS export
			if ($format == 'XLS') {
				$head = $output->getSimpleHeaderArray();
				$data = $output->getSimpleDataArray();
				foreach ($data as $row) {
					$return_data[] = array_combine($head, $row);
				}
			} else {
				$return_data = $output->output(!$direct);
			}

		//crmv@31775
		} elseif ($tab == 'CV') {
		
			$sSQL = $this->sGetSQLforReport();
			$customView = CRMEntity::getInstance('CustomView', $this->cvInfo['module']); // crmv@115329
			$customView->createReportFilterTable($this->reportid,$current_user->id,$sSQL,$this->cvInfo['prefix']);
		//crmv@31775e
		
		} elseif ($tab == 'MAIN') { //crmv@29686

			//crmv@29686
			$sSQL = $this->sGetSQLforReport();

			if (empty($this->queryLimit)) {
				$result = $adb->querySlave('Reports',$sSQL); // crmv@185894
				$this->total_count = $adb->num_rows($result);
			} else{
				// get the toal number of rows
				// TODO: do it only once per report, don't calculate it every time
				$countSql = $this->sGetCountSQLforReport($sSQL); // crmv@172034
				$resCount = $adb->querySlave('Reports',$countSql); // crmv@185894
				$this->total_count = $adb->query_result_no_html($resCount, 'count');
				
				$result = $adb->limitQuerySlave('Reports',$sSQL, $this->queryLimit[0], $this->queryLimit[1]); // crmv@185894

			}
			//crmv@29686e
			
			$error_msg = $adb->ErrorMsgSlave('Reports'); // crmv@185894
			if(!$result && $error_msg!=''){
				// Performance Optimization: If direct output is requried
				if($direct) {
					echo getTranslatedString('LBL_REPORT_GENERATION_FAILED', 'Reports') . "<br>" . $error_msg;
					$error_msg = false;
				}
				// END
				return $error_msg;
			}
			
			if($result) {
			
				$colCount = $adb->num_fields($result);
				
				$groupCells = ($this->reporttype == 'summary');
				$hoptions = $coptions = array();
				if ($format == "HTML" || $format == "JSON") {
					$coptions['use_links'] = true;
				} elseif ($format == "PRINT") {
					$hoptions['skip_actions'] = true;
				} elseif ($format == "XLS") {
					$hoptions['skip_actions'] = true;
					$hoptions['unique_columns'] = true;
					$coptions['format_numbers'] = false;
					$coptions['format_dates'] = false; // crmv@133409
					$groupCells = false;
				// crmv@169562
				} elseif ($format == "PDF") {
					$hoptions['abs_url'] = true;
				}
				// crmv@169562

				$this->generateHeader($result, $output, $hoptions);

				$count_limit = 0;
				$group_values = array();
				
				while ($custom_field_values = $adb->fetch_array($result)) {

					//crmv@29686 crmv@81019
					if ($groupCells && $this->groupingLevels > 0) {
						$row_group_values = array();
						for ($i=0; $i<$this->groupingLevels; $i++) {
							($custom_field_values[$i] != '') ? $row_group_values[$i] = $custom_field_values[$i] : $row_group_values[$i] = '-';
						}
					}
					//crmv@29686e crmv@81019e
					
					for ($i=0; $i<$colCount; $i++) {
						$fld = $adb->field_name($result, $i);
						
						$cell = $this->generateCell($fld->name, $custom_field_values[$i], $custom_field_values, $coptions);

						if ($cell) {
							if ($groupCells) {
								if (isset($row_group_values[$i]) && $group_values[$i] === $row_group_values[$i]) {
									$cell['value'] = '';
									$cell['class'] = 'rptEmptyGrp';
								}else {
									unset($group_values);	//crmv@81019
									$cell['class'] = 'rptGrpHead';
								}
							} else {
								$cell['class'] = 'rptData';
							}
							$output->addCell($cell);
						}
					}
					
					if (!$hoptions['skip_actions']) {
						$cell = $this->generateActionCell($custom_field_values,$hoptions); // crmv@169562
						if ($cell) {
							$output->addCell($cell);
						}
					}
					
					$output->endCurrentRow();

					// crmv@81019
					if ($groupCells && $this->groupingLevels > 0) {
						foreach($row_group_values as $k => $v) {
							$group_values[$k] = $v;
						}
					}
					// crmv@81019e

					set_time_limit($php_max_execution_time);
					
					//crmv@29686
					$count_limit++;
					if ($count_limit >= $this->pageSize && !empty($this->queryLimit)) {
						break;
					}
					//crmv@29686e
				}
				
				$output->countTotal = $this->total_count;
				$output->countFiltered = $this->total_count;
				
				if ($format == 'XLS') {
					$head = $output->getSimpleHeaderArray();
					$data = $output->getSimpleDataArray();
					// crmv@171787 - check duplicate columns
					$labels = array();
					$dupCount = array();
					foreach ($head as $k => $hlabel) {
						if (array_key_exists($hlabel, $labels)) {
							$count = intval($dupCount[$hlabel])+1;
							$dupCount[$hlabel] = $count;
							$hlabel .= " ##".$count."##";
						}
						$labels[$hlabel] = $hlabel;
					}
					$head = $labels;
					// crmv@171787e
					foreach ($data as $row) {
						$return_data[] = array_combine($head, $row);
					}
				} else {
					$return_data[] = $output->output(!$direct);
					$return_data[] = $this->total_count; //crmv@36519
					$return_data[] = $sSQL;
					$return_data[] = $colCount; // number of columns - crmv@29686
				}
				
			}

		} elseif ($tab == "TOTAL" && $this->hasTotals()) {
		
			$sSQL = $this->sGetSQLforReport();
			
			if ($sSQL) {
			
				$VP = VTEProperties::getInstance();
				if ($VP->get('performance.show_query_stats') == 1 && $_REQUEST['show_query'] == 'true') {
					echo "TOTAL query:<br>\n";
					echo $sSQL."<br>\n<br>\n";
				}

				$result = $adb->querySlave('Reports',$sSQL); // crmv@185894

				$output->addHeader(array('column' => 'fieldname', 'label' => getTranslatedString('Totals')));
				$output->addHeader(array('column' => 'sum', 'label' => getTranslatedString('SUM')));
				$output->addHeader(array('column' => 'avg', 'label' => getTranslatedString('AVG')));
				$output->addHeader(array('column' => 'min', 'label' => getTranslatedString('MIN')));
				$output->addHeader(array('column' => 'max', 'label' => getTranslatedString('MAX')));
				
				if ($format == "XLS") {
					
					$this->generateTotalRows($result, $output, array('format_numbers' => false));
					
					// change the output array to match the expected format for XLS export
					$return_data = array();
					$data = $output->getSimpleDataArray();
					$fieldName = '';
					foreach ($data as $row) {
						$nrow = array();
						foreach ($row as $key => $value) {
							if ($key == 'fieldname') {
								$fieldName = $value;
								continue;
							}
							$klabel = $fieldName.'_'.strtoupper($key);
							$nrow[$klabel] = $value;
						}
						$return_data[] = $nrow;
					}
				
				} else {
				
					$this->generateTotalRows($result, $output);
					$return_data = $output->output(!$direct);
					
				}
			}
		}
		
		$this->setReportStats($this->total_count); // crmv@134727
		
		return $return_data;
	}
	
	// crmv@165479
	protected function prepareGroupingQuery($sql) {
		global $adb;
		
		$this->groupingInfo = array('totals' => array());
		$operations = array('SUM', 'AVG', 'MIN', 'MAX');
		
		$quoteChar = '"';
		if ($adb->isMysql()) $quoteChar = '`';
		
		$selects = array();
		$groupBy = $orderBy = array();
		$opersList = array();

		$counttotals = array();
		if (is_array($this->summarycolumns) && count($this->summarycolumns) > 0) {
			// find aggregators early
			foreach ($this->summarycolumns as $colinfo) {
				$opers = $colinfo['aggregators'] ?: array();
				$opers = array_intersect($opers, $operations);
				if (count($opers) > 0) {
					foreach ($opers as $oper) {
						$bigOper = strtoupper($oper);
						$colinfoOper = $colinfo;
						$colinfoOper['aggregator'] = $oper;
						$opersList[] = $this->prepareTotalColumn($colinfo['table'].'.'.$colinfo['alias'], $colinfoOper)." AS oper_".strtolower($oper);
						$this->groupingInfo['totals'][$bigOper][] = array(
							'module' => $colinfo['module'],
							'colspec' => $colinfo
						);
					}
					
				}
			}
			
			$VP = VTEProperties::getInstance();
			
			$this->groupingCleanSubTables();
			
			// now use other columns
			$level = 1;
			$levelVars = array();
			$aliases = array();
			foreach ($this->summarycolumns as $colinfo) {
				$module = $colinfo['module'];

				if (!$module || !$colinfo['summary']) continue;

				/*$dir = 'ASC';
				if ($colinfo['sortorder']) $dir = strtoupper($colinfo['sortorder']);
				*/
				
				$colSql = $colinfo['table'].'.'.$colinfo['alias'];
				if ($colinfo['fieldname'] == 'cluster') {
					$colinfo = $this->getClusterColumnInfo();
					$colSql = $colinfo['column'];
				}
				
				// crmv@177587
				// apply the function only to the select, since the group by will use it later
				if ($colinfo['formula']) {
					$colSql = $this->apply_function($colSql, $colinfo['formula']);
				}
				// crmv@177587e
				
				$aliases[$level] = $colinfo['global_alias'];
				$selects[] = $colSql.' AS "'.$colinfo['global_alias'].'"';
				$selects[] = $colSql." AS value_liv{$level}"; // same column with different alias, for simplicity
				if ($adb->isOracle()) {
					// crmv@177587
					// oracle doesn't use the column from the select, so I have to re-apply the formula
					$groupsel = "{$colinfo['table']}.{$colinfo['alias']}";
					if ($colinfo['formula']) {
						$groupsel = $this->apply_function($groupsel, $colinfo['formula']);
					}
					// crmv@177587e
					//$orderBy[] = "{$colinfo['table']}.{$colinfo['alias']} $dir"; // TODO: is the order needed ?
				} else {
					$groupsel = "$quoteChar{$colinfo['global_alias']}$quoteChar"; // crmv@177587
					//$orderBy[] = "$quoteChar{$colinfo['global_alias']}$quoteChar $dir"; // TODO: is the order needed ?
				}
				$groupBy[] = $groupsel; // crmv@177587
				
				$selSql = implode(', ', $selects).', COUNT(*) as count_liv'.$level;
				if (count($opersList) > 0) {
					$selSql .= ", ".implode(', ', $opersList);
				}
				
				// change the select
				$subsql = replaceSelectQuery($sql, $selSql);
				// and add the group by
				$subsql .= " GROUP BY ".implode(', ', $groupBy); // . " ORDER BY ".implode(', ', $orderBy);
				
				if ($VP->get('performance.show_query_stats') == 1 && $_REQUEST['show_query'] == 'true') { // crmv@146138
					echo "GROUP QUERY LEVEL $level ($module):<br>\n";
					echo $subsql."<br>\n<br>\n";
				}
				
				// execute the query and populate the levels tables
				$res = $adb->querySlave('Reports', $subsql); // crmv@185894
				
				// fill the tables
				$this->groupingFillSubTable($res, $levelVars, $aliases, $level);
				
				++$level;
			}
		}
		
		$newsql = $this->groupingFillMainTable();

		return $newsql;
	}
	
	protected function groupingCleanSubTables() {
		global $adb, $current_user;
		
		for ($i=1; $i<=$this->no_group_levels; $i++) {
			$adb->pquerySlave('Reports',"DELETE FROM ".$this->getLivTable('liv',$i)." WHERE reportid = ? AND userid = ?", array($this->reportid, $current_user->id)); // crmv@185894
		}
	}
	
	protected function groupingFillSubTable(&$sqlres, &$levelVars, $aliases, $level = 1) {
		global $adb, $table_prefix, $current_user;
		
		$userid = $current_user->id;
		
		$table = $this->getLivTable('liv',$level); // crmv@185894
		
		// done before for all tables
		//$adb->pquery("DELETE FROM $table WHERE reportid = ? AND userid = ?", array($this->reportid, $userid));
		
		$coptions = array(
			'format_numbers' => false,
			'format_dates' => false, // crmv@109353
			'use_links' => false,
			'append_currency' => false, // crmv@148336
		);
		
		$columns = array(
			'reportid', 'userid', 'id_liv'.$level,
			'value_liv'.$level, 'count_liv'.$level,
			'formula'.$level.'_sum', 'formula'.$level.'_avg',
			'formula'.$level.'_min', 'formula'.$level.'_max'
		);
		for ($j=1; $j<$level; ++$j) {
			$columns[] = 'id_liv'.$j;
		}
		
		$count = $adb->num_rows($sqlres);
		$ids = $adb->getMultiUniqueIDSlave('Reports', $table, $count); // crmv@185894
		$i=0;
		$rows = array();
		$limitRows = 10000; // don't store more than this number of rows

		while ($row = $adb->fetchByAssoc($sqlres, -1, false)) {
			$params = array();
			$params[] = $this->reportid;
			$params[] = $userid;
			$params['id_liv'.$level] = $ids[$i];
			
			$value = $row['value_liv'.$level];
			
			// transform the value
			$cell = $this->generateCell($aliases[$level], $value, $row, $coptions);
			if ($cell) {
				$value = $cell['value'];
			}
			$params['value_liv'.$level] = $value;
			
			$params[] = $row['count_liv'.$level];
			$params[] = $row['oper_sum'];
			$params[] = $row['oper_avg'];
			$params[] = $row['oper_min'];
			$params[] = $row['oper_max'];
			
			// crmv@178258
			// fill other ids
			$valueChain = '';
			for ($j=1; $j<$level; ++$j) {
				$oldValue = $row['value_liv'.$j];
				$cell = $this->generateCell($aliases[$j], $oldValue, $row, $coptions);
				if ($cell) {
					$oldValue = $cell['value'];
				}
				// order and group by are case-insensitive, so this comparison should be the same
				$valueChain .= ($j > 1 ? '::' : '').strtolower($oldValue); // crmv@179356
				// TODO: is this secure (comparing values directly)?
				$params['id_liv'.$j] = $levelVars['level_ids'][$j][$valueChain];
			}
			$valueChain .= ($level > 1 ? '::' : '' ).strtolower($value); // crmv@179356

			$levelVars['level_ids'][$level][$valueChain] = $ids[$i];
			// crmv@178258e
			
			// buffer to keep memory size controlled
			$rows[] = $params;
			if (count($rows) >= $limitRows) {
				$adb->bulkInsertSlave('Reports', $table, $columns, $rows); // crmv@185894
				$rows = array();
			}
			
			++$i;
		}
		// store the remaining rows
		if (count($rows) > 0) {
			$adb->bulkInsertSlave('Reports', $table, $columns, $rows); // crmv@185894
		}

	}
	
	protected function groupingFillMainTable() {
		global $adb, $table_prefix, $current_user;
		
		$userid = $current_user->id;
		
		// aggiorno la tabella con i conti se non ci sono filtri
		// la query la faccio fuori dall'if perche' mi serve anche sotto
		// crmv@185894
		$monsterQuery = "SELECT ".$this->getLivTable('liv',1).".reportid, '$userid' AS userid,\n";
		for ($i=1; $i<=$this->no_group_levels; ++$i) {
			$tableLev = $this->getLivTable('liv',$i);
			$monsterQuery .= "COALESCE({$tableLev}.id_liv{$i}, 0), {$tableLev}.value_liv{$i}, {$tableLev}.count_liv{$i},\n";
			$monsterQuery .= "{$tableLev}.formula{$i}_sum, {$tableLev}.formula{$i}_avg, {$tableLev}.formula{$i}_min, {$tableLev}.formula{$i}_max,\n";
		}
		// remove trailing comma
		$monsterQuery = substr($monsterQuery, 0, -2)."\n";
		$monsterQuery .= "FROM ".$this->getLivTable('liv',1)."\n";
		for ($i=2; $i<=$this->no_group_levels; ++$i) {
			$tableLev = $this->getLivTable('liv',$i);
			$tableLevPrev = $this->getLivTable('liv',$i-1);
			$monsterQuery .= "LEFT JOIN {$tableLev} ON ";
			$conds = array();
			for ($j=1; $j<$i; ++$j) {
				$conds[] = "{$tableLev}.id_liv{$j} = {$tableLevPrev}.id_liv{$j}";
			}
			$monsterQuery .= implode(" AND ", $conds)."\n";
		}
		$monsterQuery .= "WHERE ".$this->getLivTable('liv',1).".reportid = ? AND ".$this->getLivTable('liv',1).".userid = ?\n";
		// crmv@185894e
		$monsterNoOrder = $monsterQuery;
		
		// now insert into the global table
		$adb->pquerySlave('Reports',"DELETE FROM ".$this->getLivTable('levels')." WHERE reportid = ? AND userid = ?", array($this->reportid, $userid)); // crmv@185894
		$params = array($this->reportid, $userid);
		$cols = array('reportid', 'userid');
		for ($i=1; $i<=$this->no_group_levels; ++$i) {
			$cols[] = "id_liv".$i;
			$cols[] = "value_liv".$i;
			$cols[] = "count_liv".$i;
			$cols[] = "formula{$i}_sum";
			$cols[] = "formula{$i}_avg";
			$cols[] = "formula{$i}_min";
			$cols[] = "formula{$i}_max";
		}
		$adb->pquerySlave('Reports',"INSERT INTO ".$this->getLivTable('levels')." (".implode(",",$cols).") ".$monsterNoOrder, $params); // crmv@185894
		
		// prepare the final query
		$params = array($this->reportid, $userid);
		$finalQuery = "SELECT * FROM ".$this->getLivTable('levels')." WHERE reportid = ? AND userid = ?";
		$finalQuery .= " ORDER BY ";
		foreach ($this->summarycolumns as $idx => $colinfo) {
			if ($colinfo['summary']) {
				// crmv@109353
				$dir = $colinfo['sortorder'] ?: 'ASC';
				$finalQuery .= "value_liv".($idx+1)." $dir, ";
				// crmv@109353e
			}
		}
		$finalQuery = substr($finalQuery, 0, -2);
		$finalQuery = $adb->convert2Sql($finalQuery, $params);
		
		return $finalQuery;
	}

	protected function groupingOutputHeader($sqlres, &$output, $options = array()) {
		global $adb, $table_prefix;

		$counttotals = &$this->groupingInfo['totals'];
		
		//crmv@56410
		foreach ($this->summarycolumns as $col) {
			if ($col['group'] && $col['summary']) {
				// crmv@118320
				$headcell = array(
					'label' => getTranslatedString($col['fieldlabel'], $col['module']),
					'uitype' =>  $col['uitype'],
					'wstype' =>  $col['wstype'],
				);
				$output->addHeader($headcell);
				// crmv@118320e
			}
		}
		//crmv@56410e
			
		$output->addHeader(getTranslatedString('LBL_HOME_COUNT', 'APP_STRINGS'));
		
		// colonne con calcoli
		if (count($counttotals) > 0) {
			foreach ($counttotals as $oper=>$operdata) {
				// retrieve field name
				$oinfo = $operdata[0];
				$colinfo = $oinfo['colspec'];
				$transfield = $colinfo['label'];
				// crmv@118320
				$headcell = array(
					'label' => getTranslatedString($oper, 'Reports')." (".$transfield.")",
					'uitype' =>  $colinfo['uitype'],
					'wstype' =>  $colinfo['wstype'],
				);
				$output->addHeader($headcell);
				// crmv@118320e
			}
		}
		
	}
	
	protected function groupingOutputData(&$sqlres, &$output, $options = array()) {
		global $adb;
		
		$counttotals = &$this->groupingInfo['totals'];
		
		// try to output data from query directly
		// apply default options
		$options = array_merge(array(
			'format_numbers' => true,
			'format_dates' => true, // crmv@109353
		), $options ?: array());
			
		$totLevels = 0;
		foreach ($this->summarycolumns as $col) {
			if ($col['group'] && $col['summary']) {
				++$totLevels;
			}
		}

		if (!$sqlres) return;
			
		$sqlres->MoveFirst();
		$levels = $prevLevels = $prevCells = array(); // crmv@177381
		while($row = $adb->fetchByAssoc($sqlres, -1, false)){	
			for ($level=1; $level<=$this->summaryLevels; ++$level) {
				$curid = $row['id_liv'.$level];
				$levels[] = $curid;
				
				if ($prevLevels[$level-1] == $curid) {
					// repeating value, empty cell
					$cell = array(
						'value' => '',
						'class' => 'rptEmptyGrp',
						// crmv@177381
						'data' => array(
							'search' => $prevCells[$level-1]['data']['search'],
							'order' => $prevCells[$level-1]['data']['order'],
							'fullvalue' => $prevCells[$level-1]['value'],
						)
						// crmv@177381e
					);
				} else {
					$val = $row['value_liv'.$level];
					
					if ($level < $totLevels) {
						$bracket_count = ' (<b>'.$row['count_liv'.$level].'</b>)';
						if (count($counttotals) > 0) {
							foreach ($counttotals as $oper=>$operdata) {
								$oinfo = $operdata[0];
								$colinfo = $oinfo['colspec'];
								$colname = strtolower("formula{$level}_".$oper);
								$operval = $row['formula'.$level.'_'.strtolower($oper)];
								// crmv@65298
								$module = $oinfo['module'];
								if ($module == 'Timecards' && $colinfo['fieldname'] == 'worktime') {
									$operval = sprintf("%02d:%02d", floor($operval/3600), floor(($operval % 3600)/60));
									$bracket_count .= "<br />\n".getTranslatedString($oper, 'Reports').': '.$operval;
								} else {
									$bracket_count .= "<br />\n".getTranslatedString($oper, 'Reports').': '.formatUserNumber($operval); // crmv@92350
								}
								// crmv@65298e
							}
						}
					} else {
						$bracket_count = '';
					}
					
					if (!is_a($output, 'ReportOutputHTML')) {
						$bracket_count = strip_tags($bracket_count);
					}
					
					// crmv@118320 - format dates and numbers
					$idx = $output->getNextCellIndex();
					$head = $output->getHeaderByIndex($idx);
					if ($head && $val !== '' && $head['uitype'] > 0) {
						if (in_array($head['uitype'], array(7,9)) && $options['format_numbers']) {
							$val = formatUserNumber($val, true);
						} elseif (in_array($head['uitype'], array(71,72)) && $options['format_numbers']) {
							$val = formatUserNumber($val);
						} elseif (in_array($head['uitype'], array(5,6)) && $options['format_dates']) {
							$val = getDisplayDate($val);
						}
					}
					//crmv@118320e
		
					$cell = array(
						'value' => $val.$bracket_count,
						'class' => 'rptGrpHead',
						// crmv@177381
						'data' => array(
							'search' => $val,
							'order' => $val,
						)
						// crmv@177381e
					);
					$prevCells[$level-1] = $cell; // crmv@177381
				}
				$output->addCell($cell);
			}
			
			// count (last level)
			$cell = array(
				'value' => $row['count_liv'.$this->summaryLevels],
				'class' => 'rptGrpHead',
				// crmv@177381
				'data' => array(
					'search' => $row['count_liv'.$this->summaryLevels],
					'order' => $row['count_liv'.$this->summaryLevels],
				)
				// crmv@177381e
			);
			$output->addCell($cell);
				
			// formulas
			foreach ($counttotals as $oper=>$operdata) {
				$oinfo = $operdata[0];
				$colinfo = $oinfo['colspec'];
				// crmv@177381
				$rawval = $row['formula'.$this->summaryLevels.'_'.strtolower($oper)];
				if ($colinfo['module'] == 'Timecards' && $colinfo['fieldname'] == 'worktime') {
					$operval = sprintf("%02d:%02d", floor($rawval/3600), floor(($rawval % 3600)/60 ));
				} elseif ($options['format_numbers']) {
					$operval = formatUserNumber($rawval);
				// crmv@186806
				} else {
					$operval = $rawval;
				}
				// crmv@186806e
				// crmv@177381e
				$cell = array(
					'value' => $operval,
					'class' => 'rptGrpHead',
					// crmv@177381
					'data' => array(
						'search' => $operval,
						'order' => $rawval,
					)
					// crmv@177381e
				);
				$output->addCell($cell);
			}
			
			$output->endCurrentRow();
			
			$prevLevels = $levels;
			$levels = array();
		}
		
	}
	// crmv@165479e
	
	public function getSubqueryFromAlias($alias) {
		foreach ($this->subQueries as $sq) {
			if ($sq['alias'] == $alias) return $sq;
		}
		return null;
	}
	
	public function getColumnInfoFromAlias($alias) {
	
		// crmv@128369
		if ($alias == 'cluster' && !empty($this->clusters)) {
			return $this->getClusterColumnInfo();
		}
		// crmv@128369e
		
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			$list = &$this->columns;
		} elseif ($this->reportTab == 'TOTAL') {
			$list = &$this->totalcolumns;
		} elseif ($this->reportTab == 'COUNT') {
			$list = &$this->summarycolumns;
		}
		
		static $cache = array();
		if (!is_array($cache[$this->reportTab]) || !array_key_exists($alias, $cache[$this->reportTab])) {
			$cache[$this->reportTab][$alias] = null;
			foreach ($list as $col) {
				if ($col['global_alias'] == $alias) {
					$cache[$this->reportTab][$alias] = $col;
					break;
				}
			}
		}
		
		return $cache[$this->reportTab][$alias];
	}
	
	// crmv@128369
	public function getClusterColumnInfo() {
		$sql = $this->getClusterColumn(false);
		$info = array(
			'fieldid' => -1,
			'module' => 'Cluster', // crmv@165479
			'fieldname' => 'cluster',
			'column' => $sql,
			'wstype' => 'string',
			'uitype' => 1,
			'label' => getTranslatedString('LBL_CLUSTER_NAME', 'Reports'),
			'group' => true,
			'summary' => ($this->reporttype == 'summary'),
			'global_alias' => 'cluster',
		);
		return $info;
	}
	// crmv@128369e
	
	/**
	 * @deprecated
	 * Use getColumnInfoFromAlias instead
	 */
	public function getColumnInfoFromIndex($index) {
		
		if ($this->reportTab == 'MAIN' || $this->reportTab == 'CV') {
			// crmv@101490
			$list = $this->columns;
			if ($this->reportTab == 'MAIN') {
				$this->reorderColumns($list);
			}
			// crmv@101490e
		} elseif ($this->reportTab == 'TOTAL') {
			$list = &$this->totalcolumns;
		} elseif ($this->reportTab == 'COUNT') {
			$list = &$this->summarycolumns;
		}
		
		$idx = 0;
		foreach ($list as $col) {
			if ($col['visible']) {
				if ($idx++ == $index) {
					return $col;
				}
			}
		}
		
		return null;
	}
	
	function generateHeader($result, $output, $options = array()) {
		global $adb, $table_prefix, $current_user;
		
		// apply default options
		$options = array_merge(array(
			'unique_columns' => (count($this->relations) > 1), // show the module name if there are more modules
			'skip_actions' => false,
		), $options ?: array());
		
		$count = $adb->num_fields($result);

		for ($x=0; $x<$count; ++$x) {
			$fld = $adb->field_name($result, $x);
			
			$colinfo = $this->getColumnInfoFromAlias($fld->name);
			if (!$colinfo || $colinfo['visible'] === false) continue;

			$orderable = true;
			$searchable = true;
			$headerLabel = $colinfo['label'];
			
			if ($colinfo['convert_currency']) {
				$headerLabel .= " (".getTranslatedString('LBL_IN')." ".$current_user->currency_symbol.")";
			}
			
			if ($options['unique_columns']) {
				if ($this->outputFormat == 'XLS') {
					$headerLabel = $this->reports->getModuleLabel($colinfo['module'])." ".$headerLabel;
				} else {
					$headerLabel = $this->reports->getModuleLabel($colinfo['module'])."<br>\n".$headerLabel;
				}
			}
			
			$hcell = array(
				'column' => $fld->name,
				'label' => $headerLabel,
				'orderable' => $orderable,
				'searchable' => $searchable,
				// crmv@118320
				'uitype' => intval($colinfo['uitype']),
				'wstype' => $colinfo['wstype'],
				// crmv@118320e
			);
			
			// crmv@163307
			// keep the alignment after searching/ordering
			$wstype = $colinfo['wstype'];
			if ($wstype == 'integer' || $wstype == 'real' || $wstype == 'double' || $wstype == 'currency') {
				$hcell['className'] = 'dt-body-right';
			}
			// crmv@163307e
			
			$output->addHeader($hcell);
		}
		
		if (!$options['skip_actions']) {
			$hcell = array(
				'column' => 'actions',
				'label' => getTranslatedString('LBL_ACTION'),
				'orderable' => false,
				'searchable' => false,
			);
			$output->addHeader($hcell);
		}
		
	}
	
	function generateActionCell($custom_field_values, $options = array()) {
		global $site_URL; // crmv@169562
		
		$module = $this->primaryModule;
		$mainid = strtolower('id@'.$module);
		
		$recordid = $custom_field_values[$mainid];
		if ($recordid) {
			if ($module == 'Events') $module = 'Calendar'; // crmv@100399
			// crmv@169562
			$root_url = '';
			if ($options['abs_url'] === true) $root_url = $site_URL.'/';
			$value = "<a href='{$root_url}index.php?module={$module}&action=DetailView&record={$recordid}' target='_blank'>".getTranslatedString('LBL_VIEW_DETAILS')."</a>";
			// crmv@169562e
			$cell = array(
				'value' => $value,
				'column' => 'actions',
				'class' => 'rptAction',
			);
			return $cell;
		}
		
		return false;
	}
	
	function generateCell($colalias, $value, $custom_field_values, $options = array()) {
		global $adb, $table_prefix, $current_user;
		global $mod_strings, $app_strings, $default_charset; // crmv@127526
		
		$cell = array();
		$css_style = null;
		
		// apply default options
		$options = array_merge(array(
			'format_numbers' => true,
			'format_dates' => true, // crmv@109353
			'use_links' => false,
			'append_currency' => true, // crmv@148336
		), $options ?: array());
		
		$colinfo = $this->getColumnInfoFromAlias($colalias);
		if (!$colinfo || $colinfo['visible'] === false) return false;

		list($alias, $table) = explode('@', $colalias);
		$module = $colinfo['module'];
		$uitype = $colinfo['uitype'];
		$wstype = $colinfo['wstype'];
		$formula = $colinfo['formula'];

		$mainid = intval($custom_field_values['id@'.strtolower($this->primaryModule)]);
		$recordid = intval($custom_field_values['id@'.$table]);
		

		$cell_align = 'left'; // crmv@29686
		if ($wstype == 'integer' || $wstype == 'real' || $wstype == 'double' || $wstype == 'currency') $cell_align = 'right';

		//crmv@44447
		if (!$this->CheckFieldVisibility($colinfo, $custom_field_values)) {
			$fieldvalue = getTranslatedString('LBL_NOT_ACCESSIBLE', 'APP_STRINGS');
		//crmv@44447e
		//crmv@92843
		} elseif ($colinfo['adjust_timezone'] && !$formula) {
			$fieldvalue = $this->formatTimezone($value, $colinfo, $custom_field_values);
		//crmv@92843e
		} elseif ($colinfo['convert_currency']) {
			if ($value != '') {
				$fieldvalue = convertFromMasterCurrency($value,$current_user->conv_rate); // crmv@42024
				if ($options['format_numbers']) $fieldvalue = formatUserNumber($fieldvalue);
			} else {
				$fieldvalue = null;
			}
		// crmv@42024
		} elseif ($colinfo['append_currency']) {
			$curkey = 'currency_id@'.$colinfo['table'];
			$curid = intval($custom_field_values[$curkey]);
			if ($curid > 0) {
				$cur_sym_rate = getCurrencySymbolandCRate($curid);
			}
			if ($value != '') {
				$fieldvalue = floatval($value);
				if ($options['format_numbers']) $fieldvalue = formatUserNumber($fieldvalue);
				if ($options['append_currency']) {
					$fieldvalue = $cur_sym_rate['symbol']." ".$fieldvalue; // crmv@148336
					$cell_currency = $cur_sym_rate['symbol']; // crmv@157509
				}
			} else {
				$fieldvalue = null;
			}
			$cell_align = 'right'; // crmv@29686
		// crmv@42024e
		} elseif ($colinfo['currency_name'] && $value != '') { // crmv@38798
			$fieldvalue = getCurrencyName($value);
		// crmv@83877
		} elseif ($uitype == 7 || $uitype == 9) { // crmv@118320
			if ($options['format_numbers']) {
				$fieldvalue = formatUserNumber($value, true);
			} else {
				$fieldvalue = $value;
			}
		// crmv@83877e
		} elseif($colinfo['wstype'] == 'boolean'){
			if ($value == 1) {
				$fieldvalue = getTranslatedString("yes");
			}else {
				$fieldvalue = "no";
			}
		} elseif ($uitype == 10 && !empty($value)) {
			// crmv@185656
			$relmodule = $custom_field_values[$alias.'_setype@'.$table];
			$relid = $custom_field_values[$alias.'_id@'.$table];
			$fieldvalue = $value;
			// crmv@185656e
			if ($fieldvalue && $relmodule && $options['use_links']) {
				$singlemod = getTranslatedString('SINGLE_'.$relmodule);
				if ($relmodule == 'Events') $relmodule = 'Calendar'; // crmv@100399
				$fieldvalue = "<a href=\"index.php?module={$relmodule}&action=DetailView&record={$relid}\" target=\"_blank\" title=\"$singlemod\">{$fieldvalue}</a>";
			}
		} elseif ($colinfo['wstype'] == 'owner') {
			$fieldvalue = $value;
			$ownerid = $custom_field_values[$alias.'_id@'.$table];
			$ownertype = $custom_field_values[$alias.'_setype@'.$table];
			if ($ownerid > 0 && is_admin($current_user) && $ownertype == 'Users' && $options['use_links']) {
				$fieldvalue = "<a href=\"index.php?module=Users&action=DetailView&record={$ownerid}\" target=\"_blank\" title=\"User\">{$fieldvalue}</a>";
			}
		//crmv@21249
		} elseif ($colinfo['wstype'] == 'picklistmultilanguage' && $value !== '') {
			// crmv@129978
			if ($colinfo['display_alias'] && !empty($custom_field_values[$colinfo['display_alias']])) {
				$fieldvalue = $custom_field_values[$colinfo['display_alias']];
			} else {
				$fieldvalue = Picklistmulti::getTranslatedPicklist($value,$colinfo['fieldname']);
			}
			// crmv@129978e
		}
		//crmv@21249e
		//crmv@18544
		elseif($uitype == 210){ // crmv@38798
			$fieldvalue = strip_tags(htmlspecialchars_decode($value));
		//crmv@18544e crmv@38798e
		// crmv@127526
		} elseif ($colinfo['wstype'] == 'url') {
			if ($options['use_links']) {
				$fieldvalue = "<a href=\"".htmlspecialchars($value, ENT_COMPAT, $default_charset)."\" target=\"_blank\">{$value}</a>";
			}
		}
		// crmv@127526e
		//crmv@126096
		elseif ($uitype == 1020) {
			$fieldvalue = time_duration(abs($value));
		}
		//crmv@126096e
		//crmv@65492 - 28
		elseif (SDK::isUitype($uitype)) {
			$sdk_file = SDK::getUitypeFile('php','report',$uitype);
			$sdk_value = $fieldvalue = $value;
			if ($sdk_file != '') {
				include($sdk_file);
			}
		} else {
		//crmv@65492e - 28
			$fieldvalue = $value;
		}
		
		
		// it's a field of another module, check if I have to use a link
		if ($module && $module != $this->primaryModule) {
			if ($options['use_links'] && $colinfo['is_entityname']) {
				$relid = $recordid;
				$relmodule = $module;
				if ($relid && $relmodule) {
					$singlemod = getTranslatedString('SINGLE_'.$relmodule);
					if ($relmodule == 'Events') $relmodule = 'Calendar'; // crmv@100399
					$fieldvalue = "<a href=\"index.php?module={$relmodule}&action=DetailView&record={$relid}\" target=\"_blank\" title=\"$singlemod\">{$fieldvalue}</a>";
				}
			}
		}
		
		// safe html 
		if (!$options['use_links']) {
			$fieldvalue = str_replace("<", "&lt;", $fieldvalue);
			$fieldvalue = str_replace(">", "&gt;", $fieldvalue);
		}

		$this->checkValueAccessible($colinfo, $fieldvalue, $custom_field_values);

		//crmv@30970 crmv@127526
		if ($colinfo['translate']) {
			if (FakeModules::isFakeModule($module)) {
				$fieldvalue = $this->translateFakeField($fieldvalue,$module,$colinfo['fieldname']);
			} else {
				$fieldvalue = getTranslatedString($fieldvalue,$module);
			}
		}
		//crmv@30970e crmv@127526e
		
		if($fieldvalue == "")
		{
			$fieldvalue = "-";
		}
		elseif(stristr($fieldvalue,"|##|"))
		{
			$fieldvalue = str_ireplace(' |##| ',', ',$fieldvalue);
		}
		elseif ($options['format_dates'] && ($wstype == "date" || $wstype == "datetime" || $uitype == 6)) { // crmv@105038 crmv@109353
			//crmv@fix date
			//TODO: can't check if date or datetime, get field id to take the right parameter of the field
			if ($wstype == 'date' || strpos($fieldvalue,'00:00:00') !== false) {
				$fieldvalue = substr($fieldvalue,0,10);
			}
			//crmv@fix date	end
			// crmv@127526
			if (in_array($fieldvalue, array('0000-00-00','0000-00-00 00:00:00'))) {
				$fieldvalue = '-';
			} else {
				if (!$formula) {
					$fieldvalue = getDisplayDate($fieldvalue);
				}
			}
			// crmv@127526e
		}

		// crmv@81019
		$cell = array(
			'value' => $fieldvalue,
			'column' => $colalias,
		);
		if ($cell_align == 'right') $cell['align'] = $cell_align;
		if ($css_style) $cell['style'] = $css_style;
		if ($cell_currency) $cell['currency_symbol'] = $cell_currency; // crmv@157509
		
		return $cell;
	}
	
	function generateTotalRows(&$result, $output, $options = array()) {
		global $adb, $table_prefix, $current_user;
		global $modules, $app_strings, $mod_strings;
		
		// apply default options
		$options = array_merge(array(
			'format_numbers' => true,
		), $options ?: array());
		
		$aggregators = Array('SUM','AVG','MIN','MAX');
		
		$row = $adb->FetchByAssoc($result, -1, false);

		// prepare the data by field name
		$data = array();
		foreach ($row as $alias => $value) {
			$colinfo = $this->getColumnInfoFromAlias($alias);
			if (!$colinfo || $colinfo['visible'] === false) continue;
			
			$fieldid = $colinfo['fieldid'];
			$faggr = strtoupper($colinfo['aggregator']);
			
			// special case: worktime
			if ($colinfo['module'] == 'Timecards' && $colinfo['fieldname'] == 'worktime') {
				$value = substr($value, 0, 5);
			}
			
			// clear the data for the field
			if (!isset($data[$fieldid])) {
				foreach ($aggregators as $aggr) {
					$data[$fieldid]['info'] = $colinfo;
					$data[$fieldid]['values'][$aggr] = null;
				}
			}
			// put the values
			foreach ($aggregators as $aggr) {
				if ($faggr == $aggr) {
					$data[$fieldid]['values'][$aggr] = $value;
				}
			}
			
		}

		// now the data array is ready for output
		foreach ($data as $fieldid => $totals) {
			$colinfo = $totals['info'];

			$label = getTranslatedString($colinfo['fieldlabel'], $colinfo['module']);
			
			// TODO: for the append currency, convert the price in the query itself
			if ($colinfo['convert_currency']) {
				$label .= " (".$app_strings['LBL_IN']." ".$current_user->currency_symbol.")";
			}
			
			$cell = array('column' => 'fieldname', 'value' => $label, 'class' => 'rptData');
			$output->addCell($cell);
			
			foreach ($totals['values'] as $aggr => $value) {
				
				if ($value != '') {
					if ($colinfo['convert_currency']) {
						$value = convertFromMasterCurrency($value,$current_user->conv_rate); // crmv@42024
						if ($options['format_numbers']) $value = formatUserNumber(floatval($value));
					} elseif ($colinfo['append_currency']) {
						if ($options['format_numbers']) $value = formatUserNumber(floatval($value));
					// crmv@148336
					// the total query extracts values in seconds, convert it in hh:mm
					} elseif ($colinfo['module'] == 'Timecards' && $colinfo['fieldname'] == 'worktime') {
						$time = sprintf("%02d:%02d", floor($value/3600), floor(($value % 3600)/60 ));
						$value = $time;
					// crmv@148336e
					// crmv@182078
					}elseif (in_array($colinfo['uitype'],array(1020))){
						$value = time_duration(abs($value));
                    // crmv@182078e
					// crmv@156542
					} elseif (in_array($colinfo['uitype'],array(7,9))){
						if ($options['format_numbers']) $value = formatUserNumber(floatval($value));
					}
					// crmv@156542e
					
				}
				
				$cell = array('column' => strtolower($aggr), 'value' => $value, 'class' => 'rptTotal');
				$output->addCell($cell);
			}
			$output->endCurrentRow();
		}

	}
	
	// crmv@92843
	function formatTimezone($fieldvalue, &$colinfo ,&$row) {
		global $table_prefix;
		
		$removetime = false;
		$fname = $colinfo['fieldname'];

		// Check if I have to add the hour
		if (in_array($fname, array('date_start', 'due_date'))) {
			
			if ($fname == 'date_start') {
				$timeField = 'time_start';
			} else {
				$timeField = 'time_end';
			}
			
			$timeinfo = $this->getFieldInfoByName($colinfo['module'], $timeField);
			$timealias = "f_".$timeinfo['fieldid'].'@'.$colinfo['table'];

			$time = $row[$timealias];
			$crmid = $row['id@'.$colinfo['table']];
			
			// start time
			if (!$time) {
				if ($crmid > 0) {
					$time = getSingleFieldValue($table_prefix.'_activity', $timeField, 'activityid', $crmid, false);
				}
			}

			if ($time) {
				$fieldvalue .= ' '.$time;
				$removetime = true;
			}
		}
		
		$fieldvalue = adjustTimezone($fieldvalue, 0, null, false);
		if ($removetime) $fieldvalue = substr($fieldvalue, 0, 10);

		if ($fname == 'time_start' || $fname == 'time_end') {
			$fieldvalue = substr(trim($fieldvalue), 0, 5);
		} elseif ($fname == 'date_start') {
			$fieldvalue = substr(trim($fieldvalue), 0, 16);
		}
		
		return $fieldvalue;
	}
	// crmv@92843e

	static function createRelAlias($table, $secmod = null, $fieldid = null) {
		return CRMEntity::createRelAlias($table, $secmod, $fieldid);
	}
	
	// crmv@107467 crmv@111593
	public function isFieldAccessible($fld) {
		global $current_user;

		$fieldmod = $fld['module'];
		if (empty($fieldmod)) {
			$finfo = $this->getFieldInfoById($fld['fieldid']);
			$fieldmod = $finfo['module'];
		}
		
		require('user_privileges/requireUserPrivileges.php');
		if (!FakeModules::isFakeModule($fieldmod) && $finfo['fieldid'] < FakeModules::$baseConfProdFieldId) { // crmv@127526 crmv@198024
			if (FakeModules::isInventoryTaxField($fld['fieldid'])) return true; // crmv@165479
			
			if (!$finfo) {
				$finfo = $this->getFieldInfoById($fld['fieldid']);
			}
			if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0 ){
				$visibleFields = $this->getAdminPermittedFields($finfo['module']);
			} else {
				$visibleFields = $this->getPermittedFields($finfo['module']);
			}
			if (!array_key_exists($fld['fieldid'], $visibleFields)) return false;
		}
	
		return true;
	}
	// crmv@107467e crmv@111593e
	
	public function checkValueAccessible($colinfo, &$value, &$custom_field_values = array()) {
		global $adb, $table_prefix;
		global $app_strings, $current_user;
		
		if (!$value) return true;
		
		if (substr($colinfo['fieldname'], 0, 9) == 'prodattr_') return true; // crmv@198024
		
		$access = true;
		$fieldinfo = $this->getFieldInfoById($colinfo['fieldid']);
		
		list($alias, $table) = explode('@', $colinfo['global_alias']);
		$module = $colinfo['module'];
		$uitype = $fieldinfo['uitype'];
		$wstype = $fieldinfo['wstype'];
		
		$mainid = intval($custom_field_values['id@'.strtolower($this->primaryModule)]);
		
		//crmv@17001 crmv@187823 : Private Permissions
		if ($module == 'Events' && $mainid > 0 && !is_admin($current_user)) {
			if (!$this->focusCal) {
				$this->focusCal = CRMEntity::getInstance('Calendar');
			}
			$allowedCalFields = $this->focusCal->getNonMaskedFields();
			if (!in_array($colinfo['fieldname'], $allowedCalFields)) {
				// get the owner (try to guess the owner field and check if present, othwerwise retrieve it)
				$ownerField = $this->reports->getFieldInfoByName($module, 'assigned_user_id');
				$ownerKey = "f_".$ownerField['fieldid'].'_id@'.$table;
				$ownerId = $custom_field_values[$ownerKey] ?: getUserId($mainid);
				// get the visibility
				$visibility = $custom_field_values['visibility@'.$table];
				if (empty($visibility)) {
					// retrieve from db if not in the query
					$visibility = $adb->query_result_no_html($adb->pquerySlave('Reports','SELECT visibility from '.$table_prefix.'_activity where activityid = ?', array($mainid)),0,'visibility'); // crmv@185894
				}
				if ($this->focusCal->isFieldMasked($mainid, $colinfo['fieldname'], array('assigned_user_id' => $ownerId, 'visibility' => $visibility))) { // crmv@187823
					if ($colinfo['fieldname'] == 'subject') {
						$value = getTranslatedString('Private Event','Calendar');
					} else {
						$value = $app_strings['LBL_NOT_ACCESSIBLE'];
					}
					return false;
				}
			}
		}
		//crmv@17001e crmv@187823e
		
		if ($wstype == 'picklist' || $wstype == 'picklistmultilanguage') {
			$valueRaw = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
			// crmv@121514
			if ($wstype == 'picklistmultilanguage') {
				// find the value, what arrives is the label
				foreach ($fieldinfo['allowed_values'] as $key => $val) {
					if ($valueRaw == $val['label'] || $value == $val['label']) {
						$valueRaw = $key;
						break;
					}
				}
			}
			// crmv@121514e
			if ($colinfo['fieldname'] != 'activitytype' && !array_key_exists($value, $fieldinfo['allowed_values']) && !array_key_exists($valueRaw, $fieldinfo['allowed_values'])) {
				$value = $app_strings['LBL_NOT_ACCESSIBLE'];
				$access = false;
			}
		} elseif ($wstype == 'multipicklist') {
			// remove only the unavailable values
			$list = explode(' |##| ', $value);
			foreach ($list as &$lval) {
				$valueRaw = html_entity_decode($lval, ENT_QUOTES, 'UTF-8');
				if (!array_key_exists($lval, $fieldinfo['allowed_values']) && !array_key_exists($valueRaw, $fieldinfo['allowed_values'])) {
					$lval = $app_strings['LBL_NOT_ACCESSIBLE'];
					$access = false;
				}
			}
			if (!$access) $value = implode(' |##| ', $list);
		}
		
		return $access;
	}

	//crmv@44447
	/**
	 * Runtime visibility check. Used to check the visibility based also on other fields of the module
	 */
	function CheckFieldVisibility($colinfo, $values) {
		global $current_user;

		$fieldinfo = $this->getFieldInfoById($colinfo['fieldid']);
		
		$module = $fieldinfo['module'];
		$readonly = $fieldinfo['readonly'];
		$fieldname = $fieldinfo['fieldname'];

		// TODO check Conditionals

		$sdk_files = SDK::getViews($module,'report');
		if (!empty($sdk_files)) {

			static $aliasCache = array();
			if (!isset($aliasCache[$this->reportTab]) && is_array($values)) {
				// create mapping alias -> fieldname
				$aliasCache[$this->reportTab] = array();
				$lastTables = array();
				foreach($values as $k => $v) {
					if (!is_numeric($k)) {
						$cinfo = $this->getColumnInfoFromAlias($k);
						if ($cinfo && $cinfo['module'] && $cinfo['fieldname']) {
							$aliasCache[$this->reportTab][$cinfo['module']][$k] = $cinfo['fieldname'];
							$lastTables[$cinfo['module']] = $cinfo['table'];
						}
					}
				}
				// add the crmid column
				foreach ($lastTables as $mod=>$table) {
					$idKey = 'id@'.$table;
					if (array_key_exists($idKey, $values)) {
						$aliasCache[$this->reportTab][$mod][$idKey] = 'crmid';
					}
				}
			}
			
			// now extract values for sdk views
			$sdk_columnvalues = array();
			if (is_array($aliasCache[$this->reportTab][$module])) {
				$sdkVal = array_intersect_key($values, $aliasCache[$this->reportTab][$module]);
				foreach ($sdkVal as $alias => $val) {
					$sdk_columnvalues[$aliasCache[$this->reportTab][$module][$alias]] = $val;
				}
			}

			// now include the sdk views
			foreach($sdk_files as $sdk_file) {
				$success = false;
				$readonly_old = $readonly;
				include($sdk_file['src']);
				SDK::checkReadonly($readonly_old,$readonly,$sdk_file['mode']);
				if ($success && $sdk_file['on_success'] == 'stop') {
					break;
				}
			}
		}

		if ($readonly == 100) {
			return false;
		} else {
			return true;
		}
	}
	//crmv@44447e
	
	// crmv@185894
	function enableCacheDb($tab='') {
		if ($tab != 'CV' || $this->forceCacheDb) {
			$slaveHandler = SlaveHandler::getInstance();
			if ($slaveHandler->isActive('Reports') && $slaveHandler->checkDatabaseConnection()) {
				$this->cacheDb = $slaveHandler->getCacheDbName();
			}
		}
	}
	function setforceCacheDb($flag) {
		$this->forceCacheDb = ($flag)?true:false;
	}
	function getLivTable($mode, $i='') {
		switch($mode){
			case 'levels':
				$name = ($this->cacheDb ? $this->cacheDb.'.' : '')."vte_rep_count_levels";
				break;
			case 'liv':
				$name = ($this->cacheDb ? $this->cacheDb.'.' : '')."vte_rep_count_liv{$i}";
				break;
		}
		return $name;
	}
	// crmv@185894e

	// crmv@192411
	public function generateExportFileName($extension, $options = []) {
		global $default_charset;

		$options = array_merge([
			'prefix' => 'Report',
			'append_date' => true,
			'date_format' => 'Y-m-d',
		], $options);

		$reportName = html_entity_decode($this->reportname, ENT_QUOTES, $default_charset);
		$reportName = preg_replace('/[^a-z0-9_]/i', ' ', $reportName);
		$reportName = preg_replace('/\s+/i', '_', $reportName);

		$rname = !empty($options['prefix']) ? $options['prefix'] . '_' : '';
		$rname .= $reportName;
		$rname .= $options['append_date'] ? '_' . date($options['date_format']) : '';
		$rname .= '.' . $extension;

		return $rname;
	}
	// crmv@192411e

}