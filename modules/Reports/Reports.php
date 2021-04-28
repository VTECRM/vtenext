<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/Webservices/Utils.php');
require_once('include/Webservices/DescribeObject.php');
require_once('modules/Reports/ScheduledReports.php'); // crmv@139057

/* crmv@97862 crmv@100905 crmv@100399 */

class Reports extends SDKExtendableClass {

	var $tab_name = array(); // keep this for WS compatibility
	var $column_fields = Array();

	var $sort_values = Array();

	var $primodule;
	var $secmodule;
	var $columnssummary;
	var $columnscountsummary = array();  // crmv@29686

	var $folderid;

	var $adv_rel_fields = Array();

	var $module_list = Array();
	
	public $max_relation_levels = 5; // crmv@154814
	public $max_grouping_levels = 7;
	public $enable_clusters = true; // crmv@128369
	
	/* IDs for fake fields and blocks */
	/* deprecated */
	protected $maxTabid = 200;
	protected $tabidPB = 200;
	protected $baseFieldIdPB = 10000;
	protected $baseBlockIdPB = 10000;
	protected $baseTaxFieldId = 20000;
	protected $baseTaxBlockId = 20000;
	
	/* internal caches */
	static protected $subuser_cache = array();
	static protected $groups_cache = array();
	static protected $viewable_cache = array();
	static protected $editable_cache = array();
	static protected $exportable_cache = array();
	static protected $report_cache = array();

	// crmv@38798
	// various functions that can be applied to columns to alter the extracted value
	// uitypes are not used yet, but datatype is (see Reports.js)
	// parameters are {column} {param1} {param2}
	var $db_functions = array(
		'extract_year' => array(
			'label' => 'LBL_REP_EXTRACT_YEAR',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'EXTRACT(YEAR FROM {column})',
				'mssql' => 'DATEPART(YEAR, {column})',
				'oracle'=> 'EXTRACT(YEAR FROM {column})',
			),
		),
		'extract_quarter' => array(
			'label' => 'LBL_REP_EXTRACT_QUARTER',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'EXTRACT(QUARTER FROM {column})',
				'mssql' => 'DATEPART(QUARTER, {column})',
				'oracle'=> 'TO_CHAR({column}, \'Q\')',
			),
		),
		'extract_yearmonth' => array(
			'label' => 'LBL_REP_EXTRACT_YEARMONTH',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'DATE_FORMAT({column}, \'%Y-%m\')',
				'mssql' => 'CONVERT(CHAR(7), {column}, 120)',
				'oracle'=> 'TO_CHAR({column}, \'YYYY-MM\')',
			),
		),
		'extract_month' => array(
			'label' => 'LBL_REP_EXTRACT_MONTH',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'EXTRACT(MONTH FROM {column})',
				'mssql' => 'DATEPART(MONTH, {column})',
				'oracle'=> 'EXTRACT(MONTH FROM {column})',
			),
		),
		'extract_week' => array(
			'label' => 'LBL_REP_EXTRACT_WEEK',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'EXTRACT(WEEK FROM {column})',
				'mssql' => 'DATEPART(WEEK, {column})',
				'oracle'=> 'TO_CHAR({column}, \'WW\')',
			),
		),
		'extract_day' => array(
			'label' => 'LBL_REP_EXTRACT_DAY',
			'uitypes' => array(5,6,70),
			'wstypes' => array('date', 'datetime'),
			'sql' => array(
				'mysql' => 'EXTRACT(DAY FROM {column})',
				'mssql' => 'DATEPART(DAY, {column})',
				'oracle'=> 'EXTRACT(DAY FROM {column})',
			),
		),
		'extract_date' => array(
			'label' => 'LBL TCDate',
			'uitypes' => array(70),
			'wstypes' => array('datetime'),
			'sql' => array(
				'mysql' => 'DATE({column})',
				'mssql' => 'CONVERT(date, {column})',
				'oracle'=> 'TO_CHAR({column}, \'YYYY-MM-DD\')',
			),
		),
	);
	// crmv@38798e


	public function __construct() {
		// nothing at the moment
	}
	
	public function getSubordinateUsers($userid = null) {
		global $current_user;
		
		if (!$userid) $userid = $current_user->id;
		
		if (!isset(self::$subuser_cache[$userid])) {
			$subordinate_users = Array();
			$user_array = getRoleAndSubordinateUsers($current_user->roleid,true);
			foreach ($user_array as $userid => $username) {
				$subordinate_users[] = array( // crmv@127805
					'userid' => $userid,
					'username' => $username,
					'label' => $username,
					'value' => "users::$userid",
				);
			}

			usort($subordinate_users, function($a, $b) { // crmv@127805 
				return strcasecmp($a['label'], $b['label']);
			});

			self::$subuser_cache[$userid] = $subordinate_users;
		}
		
		return self::$subuser_cache[$userid];
	}

	// crmv@127805
	public function getSubordinateUsersIds($userid = null) {
		$usersById = array();
		$list = $this->getSubordinateUsers($userid);
		foreach ($list as $user) {
			$usersById[$user['userid']] = $user['username'];
		}
		return $usersById;
	}
	// crmv@127805e
	
	public function getUserGroups($userid = null) {
		global $current_user;
		
		if (!$userid) $userid = $current_user->id;
		
		if (!isset(self::$groups_cache[$userid])) {
			
			$userGroups = new GetUserGroups();
			$userGroups->getAllUserGroups($userid);
			$user_groups = array();
			
			foreach ($userGroups->user_groups as $groupid) {
				$ginfo = getGroupDetails($groupid);
				$user_groups[$groupid] = array(
					'groupid' => $groupid,
					'groupname' => $ginfo[1],
					'label' => $ginfo[1],
					'value' => "groups::$groupid",
				);
			}

			uasort($user_groups, function($a, $b) {
				return strcasecmp($a['label'], $b['label']);
			});

			self::$groups_cache[$userid] = $user_groups;
		}
		
		return self::$groups_cache[$userid];
	}

	public function getModuleLabel($module) {
		if ($module == 'Calendar') {
			$trans = getTranslatedString('Tasks', 'APP_STRINGS');
		// crmv@127526
		} elseif (FakeModules::isFakeModule($module)) {
			$trans = FakeModules::getModuleLabel($module);
		// crmv@127526e
		} else {
			$trans = getTranslatedString($module,$module);
		}
		return $trans;
	}
	
	public function getAvailableModules() {
		global $adb, $table_prefix;
		
		if (empty($this->module_list)) {
		
			$modules = Array();
			$restricted_tabs = getHideTab('hide_report');	//crmv@27711

			// get available modules
			if (is_array($restricted_tabs) && count($restricted_tabs) > 0) {
				$res = $adb->pquery("SELECT tabid,name FROM {$table_prefix}_tab WHERE presence IN (0,2) AND isentitytype = 1 AND tabid NOT IN (".generateQuestionMarks($restricted_tabs).")", $restricted_tabs);
			} else {
				$res = $adb->query("SELECT tabid,name FROM {$table_prefix}_tab WHERE presence IN (0,2) AND isentitytype = 1");
			}
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$module = $row['name'];
				if (isPermitted($module,'index') == "yes") {
					$modules[$module] = $this->getModuleLabel($module);
					// add fake product block module
					if (isInventoryModule($module)) {
						$modules['ProductsBlock'] = getTranslatedString('LBL_RELATED_PRODUCTS', 'Settings');
					}
				}
			}
			// sort by name
			asort($modules);
			$this->module_list = $modules;
		}
			
		return $this->module_list;
	}

	// crmv@38798
	function get_available_functions() {
		$ret = array();
		foreach ($this->db_functions as $fkey => $finfo) {
			$ret[] = array(
				'name'=>$fkey,
				'label'=>getTranslatedString($finfo['label'], 'Reports'),
				'uitypes'=> $finfo['uitypes'],
				'wstypes'=> $finfo['wstypes'],
			);
		}
		return $ret;
	}

	// crmv@38798e


	/** Function to get the Listview of Reports
	 *  This function accepts no argument
	 *  This generate the Reports view page and returns a string
	 *  contains HTML
	 */
	function sgetRptFldr($mode='', $folderid = null, $special_request=false) // crmv@30967 crmv@163922
	{

		global $adb,$log,$mod_strings,$table_prefix;
		$returndata = Array();
		// crmv@30967
		$params = array($this->getTabId('Reports'));
		$sql = "select * from ".$table_prefix."_crmentityfolder where tabid = ? ";
		if (!is_null($folderid) && $folderid > 0) {
			$sql .= ' and folderid = ? ';
			$params[] = $folderid;
		}
		
		// crmv@163922
		if ($special_request !== false){
			switch($special_request){
				case 'getAllButSDKFolders':
					$sql .= ' and state <> ? ';
					$params[] = "SDK";
					$sql .= ' order by foldername';
					$result = $adb->pquery($sql, $params);
					while ($reportfldrow = $adb->fetch_array($result)) {
						$details = Array();
						$details['state'] = $reportfldrow["state"];
						$details['id'] = $reportfldrow["folderid"];
						$details['name'] = ($mod_strings[$reportfldrow["foldername"]] == '' ) ? $reportfldrow["foldername"]:$mod_strings[$reportfldrow["foldername"]];
						$details['description'] = $reportfldrow["description"];
						$details['fname'] = popup_decode_html($details['name']);
						$details['fdescription'] = popup_decode_html($reportfldrow["description"]);
						$returndata[] = $details;						
					}
					return $returndata;
					break;
			}

		}
		// crmv@163922e

		$sql .= ' order by foldername';
		$result = $adb->pquery($sql, $params);
		
		// Fetch details of all reports of folder at once
		$reportsInAllFolders = $this->sgetRptsforFldr($folderid > 0 ? $folderid : false); // crmv@163922

		// crmv@30967e
		$reportfldrow = $adb->fetch_array($result);
		if($mode != '')
		{
			do
			{
				if ((is_array($mode) && in_array($reportfldrow["state"], $mode)) || ($mode == $reportfldrow["state"]))
				{
					$details = Array();
					$details['state'] = $reportfldrow["state"];
					$details['id'] = $reportfldrow["folderid"];
					$details['name'] = ($mod_strings[$reportfldrow["foldername"]] == '' ) ? $reportfldrow["foldername"]:$mod_strings[$reportfldrow["foldername"]];
					$details['name'] = html_entity_decode($details['name']); // crmv@169262
					$details['description'] = $reportfldrow["description"];
					$details['fname'] = popup_decode_html($details['name']);
					$details['fdescription'] = popup_decode_html($reportfldrow["description"]);
					// crmv@163922
					if ($folderid > 0) {
						$details['details'] = $reportsInAllFolders;
					} else {
						$details['details'] = $reportsInAllFolders[$reportfldrow["folderid"]];
					}
					// crmv@163922e
					$returndata[] = $details;
				}
			}while($reportfldrow = $adb->fetch_array($result));
		}else
		{
			do
			{
				$details = Array();
				$details['state'] = $reportfldrow["state"];
				$details['id'] = $reportfldrow["folderid"];
				$details['name'] = ($mod_strings[$reportfldrow["foldername"]] == '' ) ? $reportfldrow["foldername"]:$mod_strings[$reportfldrow["foldername"]];
				$details['name'] = html_entity_decode($details['name']); // crmv@169262
				$details['description'] = $reportfldrow["description"];
				$details['fname'] = popup_decode_html($details['name']);
				$details['fdescription'] = popup_decode_html($reportfldrow["description"]);
				// crmv@163922
				if ($folderid > 0) {
					$details['details'] = $reportsInAllFolders;
				} else {
					$details['details'] = $reportsInAllFolders[$reportfldrow["folderid"]];
				}
				// crmv@163922e
				$returndata[] = $details;
			}while($reportfldrow = $adb->fetch_array($result));
		}

		$log->info("Reports :: ListView->Successfully returned vte_report folder HTML");
		return $returndata;
	}

	// crmv@38798 - overridden
	function countAllRecordsInFolder($module, $folderid) {
		global $adb, $table_prefix;

		// find columnname
		$fieldinfo = array('columnname' => 'folderid', 'tablename'=>$table_prefix.'_report');

		$res = $adb->pquerySlave('Reports',"select count(*) as cnt from {$fieldinfo['tablename']} where {$fieldinfo['columnname']} = ?", array($folderid)); // crmv@185894
		if ($res) {
			return $adb->query_result_no_html($res, 0, 'cnt');
		}
		return false;
	}
	// crmv@38798e

	// crmv@30967 crmv@163922
	function getFolderContent($folderid) {
		$count = $this->sgetRptsforFldr($folderid,false,true);
		return array('count'=>$count, 'html'=>'');
	}
	// crmv@163922e

	function getFolderList() {
		$flds = getEntityFoldersByName(null, 'Reports');
		// translate folder names
		foreach ($flds as $k=>$fold) {
			$fold['foldername'] = html_entity_decode($fold['foldername']); // crmv@169262
			$flds[$k]['foldername'] = getTranslatedString($fold['foldername'], 'Reports');
			$flds[$k]['description'] = getTranslatedString($fold['description'], 'Reports');
		}
		return $flds;
	}
	// crmv@30967e

	/** Function to get the Reports inside each modules
	 *  This function accepts the folderid
	 *  This Generates the Reports under each Reports module
	 *  This Returns a HTML sring
	 */
	function sgetRptsforFldr($rpt_fldr_id,$module=false,$onlycount=false)	//crmv@31775 crmv@163922
	{
		global $log, $adb, $table_prefix;
		global $current_user;
		static $cached_perm = Array(); // crmv@163922
		
		if ($module && !is_array($module)) $module = array($module); // crmv@159603
		
		$returndata = Array();

		require_once('include/utils/UserInfoUtil.php');

		$sql = 
			"SELECT 
				r.*, rc.module, cf.folderid, cf.foldername
			FROM {$table_prefix}_report r
			LEFT JOIN {$table_prefix}_reportconfig rc ON rc.reportid = r.reportid
			INNER JOIN {$table_prefix}_crmentityfolder cf on cf.folderid = r.folderid";

		$params = array();

		// If information is required only for specific report folder?
		if($rpt_fldr_id !== false) {
			$sql .= " WHERE cf.folderid = ?";
			$params[] = $rpt_fldr_id;
			$haswhere = true;
		}

		require('user_privileges/requireUserPrivileges.php');
		require_once('include/utils/GetUserGroups.php');
		$userGroups = new GetUserGroups();
		$userGroups->getAllUserGroups($current_user->id);
		$user_groups = $userGroups->user_groups;
		if(!empty($user_groups) && $is_admin==false){
			$user_group_query = " (shareid IN (".generateQuestionMarks($user_groups).") AND setype='groups') OR";
			array_push($params, $user_groups);
		}

		$non_admin_query = " r.reportid IN (SELECT reportid from ".$table_prefix."_reportsharing WHERE $user_group_query (shareid=? AND setype='users'))";
		if($is_admin==false){
			$sql .= ($haswhere ? ' and ' : ' where ')." ( (".$non_admin_query.") or r.sharingtype='Public' or r.owner = ? or r.owner in(select ".$table_prefix."_user2role.userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%'))";
			array_push($params, $current_user->id);
			array_push($params, $current_user->id);
			$haswhere = true;	//crmv@31775
		}
		$query = $adb->pquery("select userid from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like '".$current_user_parent_role_seq."::%'",array());
		$subordinate_users = Array();
		for($i=0;$i<$adb->num_rows($query);$i++){
			$subordinate_users[] = $adb->query_result($query,$i,'userid');
		}
		
		// crmv@163922
		if ($onlycount){
			$result = $adb->pquerySlave('Reports',$sql, $params); // crmv@185894
			return $adb->num_rows($result);
		}
		// crmv@163922e

		// order reports by name
		$sql .= " ORDER BY r.reportname";

		$result = $adb->pquery($sql, $params);

		while ($report = $adb->FetchByAssoc($result, -1, false)) { // crmv@169289
			$modules = $this->getAllModules($report["reportid"]);
			if (is_array($module) && count($module) > 0 && count(array_intersect($module, $modules)) == 0) continue; // crmv@159603
			
			$report_details = Array();
			$report_details ['customizable'] = $report["customizable"];
			$report_details ['reportid'] = $report["reportid"];
			$report_details ['folderid'] = $report["folderid"];
			$report_details ['owner'] = getUserName($report["owner"]);
			$report_details ['module'] = $report["module"];
			$report_details ['primarymodule'] = $report["module"]; // kept for compatibility
			$report_details ['modules'] = $modules;
			$report_details ['state'] = $report["state"];
			$report_details ['description'] = $report["description"];
			$report_details ['reportname'] = $report["reportname"];
			$report_details ['sharingtype'] = $report["sharingtype"];
			$report_details ['foldername'] = $report["foldername"]; // crmv@30967
			if($is_admin==true || in_array($report["owner"],$subordinate_users) || $report["owner"]==$current_user->id)
				$report_details ['editable'] = 'true';
			else
				$report_details['editable'] = 'false';

			// crmv@163922
			if (!isset($cached_perm[$report["module"]])) {	
				$cached_perm[$report["module"]] = (isPermitted($report["module"],'index') == 'yes') ? true : false;
			}
			if ($cached_perm[$report["module"]]) {
				$returndata[$report["folderid"]][] = $report_details;
			}
			// crmv@163922e
		}

		// crmv@30967	//crmv@31775
		if($module === false) {
			if($rpt_fldr_id !== false) {
				$returndata = $returndata[$rpt_fldr_id];
			}
		}
		// crmv@30967e	//crmv@31775e

		$log->info("Reports :: ListView->Successfully returned vte_report details HTML");
		return $returndata;
	}

	// crmv@67929
	function generateTaxNames() {
		global $table_prefix;
		
		if (empty($this->taxNames) || empty($this->taxProdNames)) {
			$IUtils = InventoryUtils::getInstance();
			$allTaxes = $IUtils->getAllTaxes('all');
		
			$taxnames = array();
			$taxprodnames = array();
			foreach ($allTaxes as $tax) {
				$taxnames[$tax['taxname']] = getTranslatedString('LBL_TAX').' ('.$tax['taxlabel'].')';
				$taxprodnames[$tax['taxname']] = getTranslatedString('LBL_TAX').' '.getTranslatedString('LBL_PRODUCT').' ('.$tax['taxlabel'].')';
			}
			$taxnames['tax_total'] = getTranslatedString('LBL_TAX').' ('.getTranslatedString('LBL_TOTAL').')';
			$taxprodnames['tax_total'] = getTranslatedString('LBL_TAX').' '.getTranslatedString('LBL_PRODUCT').' ('.getTranslatedString('LBL_TOTAL').')';
			$this->taxNames = $taxnames;
			$this->taxProdNames = $taxprodnames;
		}
	}
	// crmv@67929e

	public function getStdFilterOptions($reportid, $selected = '') {
		$customview = CRMEntity::getInstance('CustomView'); // crmv@115329
		$opts = $customview->getStdFilterCriteria($selected);
		return $opts;
	}
	
	/**
	 * Get a list of all the fields usable for standard filters
	 * Valid fields are those choosable among all the involved modules
	 */
	public function getStdFilterFields($reportid) {
		$config = $this->loadReport($reportid);
		
		$allfields = array();
		$allmods = $this->getAllModules($reportid);
		
		$chains = $this->getAllChains($reportid);

		foreach ($chains as $chainmod) {
			$list = $this->getStdFiltersFieldsListForChain($reportid, $chainmod['chain']);
			if (is_array($list)) {
				foreach($list as &$group) {
					foreach ($group['fields'] as &$fld) {
						$fld['label'] = $this->getModuleLabel($fld['module']) .' - ' . $fld['label'];
					}
					unset($fld);
				}
				unset($group);
			}
			$allfields = array_merge($allfields, $list); // crmv@128192
		}
		
		return $allfields;
	}

	/** Function to form a javascript to determine the start date and end date for a standard filter
	 *  This function is to form a javascript to determine
	 *  the start date and End date from the value selected in the combo lists
	 */
	function getCriteriaJS() {
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
		//crmv@50067
		//$lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
		$lastmonth1 = date("Y-m-t", strtotime($lastmonth0));
		//crmv@50067e
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

		// crmv@192261
		$sjsStr = '<script language="JavaScript" type="text/javaScript">
			function showDateRange(type, fldstart, fldend, triggerstart, triggerend) {
			
				if (!triggerstart) triggerstart = "jscal_trigger_date_start";
				if (!triggerend) triggerend = "jscal_trigger_date_end";
				
				var field1 = document.NewReport[fldstart || "startdate"];
				var field2 = document.NewReport[fldend || "enddate"];
				
				if (!field1 || !field2) return;
				
				// hide/show cal buttons
				if (type != "custom") {
					field1.readOnly=true;
					field2.readOnly=true;
					jQuery("#"+triggerstart).css("visibility", "hidden");
					jQuery("#"+triggerend).css("visibility", "hidden");
				} else {
					field1.readOnly=false;
					field2.readOnly=false;
					jQuery("#"+triggerstart).css("visibility", "visible");
					jQuery("#"+triggerend).css("visibility", "visible");
				}
				
				var dates = {
					today: ["'.getDisplayDate($today).'", "'.getDisplayDate($today).'"],
					yesterday: ["'.getDisplayDate($yesterday).'", "'.getDisplayDate($yesterday).'"],
					tomorrow: ["'.getDisplayDate($tomorrow).'", "'.getDisplayDate($tomorrow).'"],
					
					thisweek: ["'.getDisplayDate($thisweek0).'", "'.getDisplayDate($thisweek1).'"],
					lastweek: ["'.getDisplayDate($lastweek0).'", "'.getDisplayDate($lastweek1).'"],
					nextweek: ["'.getDisplayDate($nextweek0).'", "'.getDisplayDate($nextweek1).'"],
					
					thismonth: ["'.getDisplayDate($currentmonth0).'", "'.getDisplayDate($currentmonth1).'"],
					lastmonth: ["'.getDisplayDate($lastmonth0).'", "'.getDisplayDate($lastmonth1).'"],
					nextmonth: ["'.getDisplayDate($nextmonth0).'", "'.getDisplayDate($nextmonth1).'"],
					
					next7days: ["'.getDisplayDate($today).'", "'.getDisplayDate($next7days).'"],
					next30days: ["'.getDisplayDate($today).'", "'.getDisplayDate($next30days).'"],
					next60days: ["'.getDisplayDate($today).'", "'.getDisplayDate($next60days).'"],
					next90days: ["'.getDisplayDate($today).'", "'.getDisplayDate($next90days).'"],
					next120days: ["'.getDisplayDate($today).'", "'.getDisplayDate($next120days).'"],
					
					last7days: ["'.getDisplayDate($last7days).'", "'.getDisplayDate($today).'"],
					last30days: ["'.getDisplayDate($last30days).'", "'.getDisplayDate($today).'"],
					last60days: ["'.getDisplayDate($last60days).'", "'.getDisplayDate($today).'"],
					last90days: ["'.getDisplayDate($last90days).'", "'.getDisplayDate($today).'"],
					last120days: ["'.getDisplayDate($last120days).'", "'.getDisplayDate($today).'"],
					
					thisfy: ["'.getDisplayDate($currentFY0).'", "'.getDisplayDate($currentFY1).'"],
					prevfy: ["'.getDisplayDate($lastFY0).'", "'.getDisplayDate($lastFY1).'"],
					nextfy: ["'.getDisplayDate($nextFY0).'", "'.getDisplayDate($nextFY1).'"],
					
					thisfq: ["'.getDisplayDate($cFq).'", "'.getDisplayDate($cFq1).'"],
					prevfq: ["'.getDisplayDate($pFq).'", "'.getDisplayDate($pFq1).'"],
					nextfq: ["'.getDisplayDate($nFq).'", "'.getDisplayDate($nFq1).'"]
				}
				
				field1.value = dates[type] ? dates[type][0] : "";
				field2.value = dates[type] ? dates[type][1] : "";
			}
		</script>';
		// crmv@192261e

		return $sjsStr;
	}
	
	//crmv@26161
	function getAdvFilterOptions() {
		$adv_filter_options = array(
			"e" => getTranslatedString('equals', 'CustomView'),
			"n" => getTranslatedString('not equal to', 'CustomView'),
			"s" => getTranslatedString('starts with', 'CustomView'),
			"ew"=> getTranslatedString('ends with', 'CustomView'),
			"c" => getTranslatedString('contains', 'CustomView'),
			"k" => getTranslatedString('does not contain', 'CustomView'),
			"l" => getTranslatedString('less than', 'CustomView'),
			"g" => getTranslatedString('greater than', 'CustomView'),
			"m" => getTranslatedString('less or equal', 'CustomView'),
			"h" => getTranslatedString('greater or equal', 'CustomView'),
			"bw" => getTranslatedString('between', 'CustomView'),
			"a" => getTranslatedString('after', 'CustomView'),
			"b" => getTranslatedString('before', 'CustomView'),
		);
		array_walk($adv_filter_options, function(&$label) {
			$label = strtolower($label);
		});
		return $adv_filter_options;
	}
	//crmv@26161e

	//crmv@3085m
	function getEntityPreview($id,$module='Reports') {
		$this->Reports($id);
		$name = $this->reportname;
		$preview = array(
			'id'=>$id,
			'module'=>'Reports',
			'name'=>$name,
			'onclick'=>"location.href='index.php?module=Reports&action=SaveAndRun&tab=Charts&record=$id';",
		);
		$details = array();
		$preview['details'] = $details;
		return $preview;
	}
	//crmv@3085me
	
	public function isViewable($reportid) {
		global $current_user;
		
		$userid = $current_user->id;
		
		if (!isset(self::$viewable_cache[$reportid][$userid])) {
			$config = $this->loadReport($reportid);
			
			$owner = $config['owner'];
			$sharingType = $config['sharingtype'];
			
			$permitted = true;
			require('user_privileges/requireUserPrivileges.php');
			
			if (!$is_admin && $sharingType != 'Public' && $owner != $userid) {
				
				if ($sharingType == 'Private') {
					// I'm not admin and it's not mine, nobody else can see it
					// TODO: maybe upper users in the role chain??
					$permitted = false;
				} elseif ($sharingType == 'Shared') {
					// shared
					$subusers = $this->getSubordinateUsersIds($userid); // crmv@127805
					$user_groups = $this->getUserGroups($userid);
				
					if (array_key_exists($owner, $subusers)) {
						$permitted = true;
					} elseif (is_array($config['sharing'])) {
						// check the sharing
						$permitted = false;
						foreach ($config['sharing'] as $share) {
							if ($share['setype'] == 'users' && $share['shareid'] == $userid) {
								// ok
								$permitted = true;
								break;
							} elseif ($share['setype'] == 'groups' && array_key_exists($share['shareid'], $user_groups)) {
								// ok
								$permitted = true;
								break;
							}
						}
					} else {
						$permitted = false;
					}
				}
			}

			// check single modules
			if (!$is_admin && $permitted) {
				$modules = $this->getAllModules($reportid);
				foreach ($modules as $mod) {
					if (FakeModules::isFakeModule($mod)) continue; //crmv@129274
					if (!vtlib_isModuleActive($mod) || isPermitted($mod,'index') != 'yes') {
						$permitted = false;
						break;
					}
				}
			}
			
			self::$viewable_cache[$reportid][$userid] = $permitted;
		}
		
		return self::$viewable_cache[$reportid][$userid];
	}
	
	public function isEditable($reportid, $userid = null) {
		global $current_user;
		if (!$userid) $userid = $current_user->id;
		
		if (!isset(self::$editable_cache[$reportid][$userid])) {
			$config = $this->loadReport($reportid);
			$subusers = $this->getSubordinateUsersIds($userid); // crmv@127805
			$sharing = $config['sharingtype'];
		
			require('user_privileges/requireUserPrivileges.php');
			if ($is_admin==true || array_key_exists($config["owner"],$subusers) || $config["owner"]==$userid) {
				self::$editable_cache[$reportid][$userid] = true;
			} else {
				self::$editable_cache[$reportid][$userid] = false;
			}
		}
		
		return self::$editable_cache[$reportid][$userid];
	}
	
	public function isExportable($reportid) {
		global $current_user;
		
		$userid = $current_user->id;
		
		if (!isset(self::$exportable_cache[$reportid][$userid])) {
			$modules = $this->getAllModules($reportid);
			
			$permitted = true;
			foreach ($modules as $mod) {
				if (FakeModules::isFakeModule($mod)) continue; //crmv@129274
				if (!vtlib_isModuleActive($mod) || isPermitted($mod,'Export') != 'yes') {
					$permitted = false;
					break;
				}
			}
			
			self::$exportable_cache[$reportid][$userid] = $permitted;
		}
		
		return self::$exportable_cache[$reportid][$userid];
	}
	
	/**
	 * Return all the involved modules for this report
	 */
	public function getAllModules($reportid) {
		
		$modules = array();
		$config = $this->loadReport($reportid);
		
		$modules[] = $config['module'];
		if (is_array($config['relations'])) {
			foreach ($config['relations'] as $rel) {
				$mod = $rel['module'];
				$modules[] = $mod;
			}
		}
		
		$modules = array_unique($modules);
		
		return $modules;
	}
	
	/**
	 * Return all the involved modules and the relation chain for each
	 */
	public function getAllChains($reportid) {
		$modules = array();
		$config = $this->loadReport($reportid);
		
		$modules[$config['module']] = array(
			'module' => $config['module'],
			'chain' => array($config['module']),
		);
		if (is_array($config['relations'])) {
			foreach ($config['relations'] as $rel) {
				$mod = $rel['module'];
				if ($mod != 'ProductsBlock') {
					$modules[$rel['module']] = array(
						'module' => $rel['module'],
						'relation' => $rel['name'],
						'chain' => $this->getChainFromRelations($rel['name'], $config['relations']),
					);
				}
			}
		}
		
		$modules = array_values($modules);
		
		return $modules;
	}
	
	/**
	 * Load data of the specified report from the DB
	 */
	public function loadReport($reportid) {
		global $adb, $table_prefix;
		
		if (!self::$report_cache[$reportid]) {
		
			$blobs = array('relations', 'fields', 'stdfilters', 'advfilters', 'clusters', 'totals', 'summary', 'scheduling'); // crmv@128369 crmv@139057
			
			$config = null;
			$res = $adb->pquery("SELECT * FROM {$table_prefix}_report r INNER JOIN {$table_prefix}_reportconfig rc ON r.reportid = rc.reportid WHERE r.reportid = ?", array($reportid));
			if ($res && $adb->num_rows($res) > 0) {
				$config = $adb->fetchByAssoc($res, -1, false);
				unset($config['reportid']);
				foreach ($blobs as $column) {
					if (!empty($config[$column])) {
						$config[$column] = Zend_Json::decode($config[$column]);
					}
				}
				// add the sharing info
				if ($config['sharingtype'] == 'Shared') {
					$sharing = array();
					$res = $adb->pquery("SELECT * FROM {$table_prefix}_reportsharing WHERE reportid = ?", array($reportid));
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						unset($row['reportid']);
						$row['value'] = $row['setype'].'::'.$row['shareid'];
						$row['label'] = getOwnerName($row['shareid']);
						$sharing[] = $row;
					}
					// crmv@200819
					usort($sharing, function ($item1, $item2) {
						return $item1['label'] <=> $item2['label'];
					});
					// crmv@200819e
					$config['sharing'] = $sharing;
				}
			}
			
			self::$report_cache[$reportid] = $config;
		}
		
		return self::$report_cache[$reportid];
	}
	
	public function createChart($reportid, $chartinfo) {
		global $current_user;

		$focus = CRMEntity::getInstance('Charts');
		$focus->mode = '';
		
		$focus->column_fields['assigned_user_id'] = $current_user->id;
		$focus->column_fields['reportid'] = $reportid;
		
		foreach($focus->column_fields as $fieldname => $val) {
			if(isset($chartinfo[$fieldname])) {
				$value = trim($chartinfo[$fieldname]);
				$focus->column_fields[$fieldname] = $value;
			}
		}

		$focus->save('Charts');
		return $focus->id;
	}
	
	/**
	 * Checks whether a report with the provided id exists
	 */
	public function reportExists($reportid) {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT r.reportid FROM {$table_prefix}_report r INNER JOIN {$table_prefix}_reportconfig rc ON r.reportid = rc.reportid WHERE r.reportid = ?", array($reportid));
		return ($res && $adb->num_rows($res) > 0);
	}
	
	/**
	 * Save the report to DB (update if existing)
	 */
	public function saveReport($reportid, $config) {
		if (!empty($reportid) && $this->reportExists($reportid)) {
			$r = $this->updateReport($reportid, $config);
		} else {
			$r = $this->insertReport($config);
		}
		
		// crmv@139057 - reset scheduling 
		if ($r && $config['scheduling'] && $config['scheduling']['format']) { // crmv@172017
			$SR = ScheduledReports::getInstance();
			$SR->updateNextExecution($r, $config['scheduling']); // crmv@172017
		}
		// crmv@139057e
		
		return $r;
	}
	
	/**
	 * Insert a new report in the db
	 */
	public function insertReport($config) {
		global $adb, $table_prefix, $current_user;
		
		$reportid = $adb->getUniqueId($table_prefix.'_report');
		
		$columns1 = array(
			'reportid' => $reportid,
			'folderid' => intval($config['folderid']),
			'reportname' => $config['reportname'],
			'description' => $config['description'],
			'reporttype' => $config['reporttype'] ?: 'tabular',
			'state' => $config['state'] ?: 'CUSTOM',
			'customizable' => isset($config['customizable']) ? intval($config['customizable']) : 1,
			'owner' => $config['owner'] ?: $current_user->id,
			'sharingtype' => $config['sharingtype'] ?: 'Public',
		);
		
		$columns = array_keys($columns1);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$table_prefix}_report (".implode(',',$columns).") VALUES (".generateQuestionMarks($columns1).")", $columns1);
		
		$columns2 = array(
			'reportid' => $reportid,
			'module' => $config['module'],
		);
		$columns = array_keys($columns2);
		$adb->format_columns($columns);
		$adb->pquery("INSERT INTO {$table_prefix}_reportconfig (".implode(',',$columns).") VALUES (".generateQuestionMarks($columns2).")", $columns2);
		
		$blobs = array(
			'relations' => $config['relations'],
			'fields' => $config['fields'],
			'stdfilters' => $config['stdfilters'],
			'advfilters' => $config['advfilters'],
			'clusters' => $config['clusters'], // crmv@128369
			'totals' => $config['totals'],
			'summary' => $config['summary'],
			'scheduling' => $config['scheduling'], // crmv@139057
		);
		
		foreach ($blobs as $column => $data) {
			if (!empty($data)) {
				$adb->updateClob($table_prefix.'_reportconfig',$column,"reportid = $reportid",Zend_Json::encode($data));
			}
		}
		
		// sharing
		if ($config['sharingtype'] == 'Shared' && is_array($config['sharing'])) {
			foreach ($config['sharing'] as $share) {
				$rshare = array(
					'reportid' => $reportid,
					'shareid' => $share['shareid'],
					'setype' => $share['setype'],
				);
				$adb->pquery("INSERT INTO {$table_prefix}_reportsharing (".implode(',', array_keys($rshare)).") VALUES (".generateQuestionMarks($rshare).")", $rshare);
			}
		}
		
		self::$report_cache[$reportid] = $config;
		
		return $reportid;
	}
	
	/**
	 * Update an existing report
	 */
	public function updateReport($reportid, $config) {
		global $adb, $table_prefix;
		
		$reportid = intval($reportid);
		
		// fields that must be passed always passed
		$columns1 = array(
			'folderid' => intval($config['folderid']),
			'reportname' => $config['reportname'],
			'description' => $config['description'],
		);
		
		// optional fields
		if ($config['reporttype']) $columns1['reporttype'] = $config['reporttype'];
		if (isset($config['customizable'])) $columns1['customizable'] = intval($config['customizable']);
		if ($config['state']) $columns1['state'] = $config['state'];
		if ($config['owner']) $columns1['owner'] = $config['owner'];
		if ($config['sharingtype']) $columns1['sharingtype'] = $config['sharingtype'];
		
		$upd = array();
		foreach ($columns1 as $col => $value) {
			$upd[] = "$col = ?";
		}
		
		$sql = "UPDATE {$table_prefix}_report SET ".implode(',', $upd)." WHERE reportid = ?";
		$adb->pquery($sql, array($columns1, $reportid));
		
		$columns2 = array(
			'module' => $config['module'],
		);
		
		$upd = array();
		foreach ($columns2 as $col => $value) {
			$upd[] = "$col = ?";
		}
		
		$sql = "UPDATE {$table_prefix}_reportconfig SET ".implode(',', $upd)." WHERE reportid = ?";
		$adb->pquery($sql, array($columns2, $reportid));
		
		$blobs = array(
			'relations' => $config['relations'],
			'fields' => $config['fields'],
			'stdfilters' => $config['stdfilters'],
			'advfilters' => $config['advfilters'],
			'clusters' => $config['clusters'], // crmv@128369
			'totals' => $config['totals'],
			'summary' => $config['summary'],
			'scheduling' => $config['scheduling'], // crmv@139057
		);
		
		foreach ($blobs as $column => $data) {
			if (empty($data)) {
				$adb->pquery("UPDATE {$table_prefix}_reportconfig SET $column = NULL WHERE reportid = ?", array($reportid));
			} else {
				$adb->updateClob($table_prefix.'_reportconfig',$column,"reportid = $reportid",Zend_Json::encode($data));
			}
		}
		
		// sharing
		$adb->pquery("DELETE FROM {$table_prefix}_reportsharing WHERE reportid = ?", array($reportid));
		if ($config['sharingtype'] == 'Shared' && is_array($config['sharing'])) {
			foreach ($config['sharing'] as $share) {
				$rshare = array(
					'reportid' => $reportid,
					'shareid' => $share['shareid'],
					'setype' => $share['setype'],
				);
				$adb->pquery("INSERT INTO {$table_prefix}_reportsharing (".implode(',', array_keys($rshare)).") VALUES (".generateQuestionMarks($rshare).")", $rshare);
			}
		}
		
		self::$report_cache[$reportid] = $config;
		
		return $reportid;
	}
	
	public function deleteReport($reportid) {
		global $adb,$table_prefix;
		
		unset(self::$report_cache[$reportid]);
		
		$adb->pquery("DELETE FROM ".$table_prefix."_report WHERE reportid = ?", array($reportid));
		$adb->pquery("DELETE FROM ".$table_prefix."_reportconfig WHERE reportid = ?", array($reportid));
		$adb->pquery("DELETE FROM ".$table_prefix."_reportsharing WHERE reportid = ?", array($reportid));
		
		// crmv@42024 - delete chart and summary data
		for ($i=1; $i<=$this->max_grouping_levels; ++$i) {
			$adb->pquery("DELETE FROM vte_rep_count_liv{$i} where reportid = ?", array($reportid));
		}
		$adb->pquery("DELETE FROM vte_rep_count_levels where reportid = ?", array($reportid));
		// crmv@185894
		require_once('modules/Reports/ReportRun.php');
		$oReportRun = ReportRun::getInstance($reportid);
		$oReportRun->enableCacheDb();
		if ($oReportRun->cacheDb != '') {
			for ($i=1; $i<=$this->max_grouping_levels; ++$i) {
				$adb->pquerySlave('Reports',"DELETE FROM ".$oReportRun->getLivTable('liv',$i)." where reportid = ?", array($reportid));
			}
			$adb->pquerySlave('Reports',"DELETE FROM ".$oReportRun->getLivTable('levels')." where reportid = ?", array($reportid));
		}
		// crmv@185894e
		$res = $adb->pquery("SELECT {$table_prefix}_charts.chartid FROM {$table_prefix}_charts inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$table_prefix}_charts.chartid where deleted = 0 and reportid = ?", array($reportid));
		if ($res && $adb->num_rows($res) > 0) {
			$chartFocus = CRMEntity::getInstance('Charts');
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$chid = $row['chartid'];
				$chartFocus->id = $chid;
				DeleteEntity('Charts', 'Charts', $chartFocus, $chid, null);
			}
		}
		// crmv@42024e

		// crmv@121612 crmv@185894
		// delete report tables
		$adbSlave = $adb->getSlaveObject('Reports');
		$tables = $adbSlave->get_tables();
		if (is_array($tables)) {
			$prefix = $table_prefix.'_rep_subq_';
			foreach ($tables as $table) {
				if (substr($table, 0, strlen($prefix)) == $prefix) {
					// ok, it's a report table, check the id
					$pieces = explode('_', $table);
					if ($pieces[4] == $reportid) {
						// ok, it's for this report
						$adb->querySlave('Reports',"DROP TABLE $table");
					}
				}
			}
		}
		// crmv@121612e crmv@185894e
		
		$adb->pquery("UPDATE ".$table_prefix."_customview SET reportid = 0 WHERE reportid = ?", array($reportid)); //crmv@40613
		
		// crmv@150024
		// remove the report from dynamic targets
		$focusTargets = CRMEntity::getInstance('Targets');
		$focusTargets->deleteDynamicReport($reportid);
		// crmv@150024e
	}
	
	// crmv@138170
	/**
	 * Remove old temporary tables for non existing reports/users
	 */
	public function cleanOldTables() {
		global $adb, $table_prefix;
		
		// get list of reportids
		$reports = array();
		$res = $adb->query("SELECT reportid FROM {$table_prefix}_report");
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$reports[] = intval($row['reportid']);
		}

		// get list of active userids
		$users = array();
		$res = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE deleted = 0 AND status = ?", array('Active'));
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$users[] = intval($row['id']);
		}
		
		// get list of tables
		// crmv@185894
		$adbSlave = $adb->getSlaveObject('Reports');
		$tables = $adbSlave->get_tables() ?: array();
		// crmv@185894e
		
		// check for report tables
		foreach ($tables as $table) {
			if (preg_match("/^{$table_prefix}_rep_subq_([0-9]+)_([0-9]+)/", $table, $matches)) {
				$userid = intval($matches[1]);
				$reportid = intval($matches[2]);
				if (!in_array($userid, $users) || !in_array($reportid, $reports)) {
					// remove the table!
					$adb->querySlave('Reports',"DROP TABLE $table"); // crmv@185894
				}
			}
		}
		
	}
	// crmv@138170e

	// crmv@101691
	/**
	 * Remove a field from all the reports
	 */
	public function deleteFieldFromAll($fieldid) {
		global $adb, $table_prefix;
		
		$sql = 
			"SELECT r.reportid FROM {$table_prefix}_report r
			INNER JOIN {$table_prefix}_reportconfig rc ON rc.reportid = r.reportid
			WHERE r.state != 'SDK'";
		$res = $adb->query($sql);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$this->deleteFieldFromReport($row['reportid'], $fieldid);
			}
		}
	}
	
	/**
	 * Remove a field from a report
	 */
	public function deleteFieldFromReport($reportid, $fieldid) {
		$config = $this->loadReport($reportid);
		
		$newFields = array();
		foreach ($config['fields'] as $field) {
			if ($field['fieldid'] != $fieldid) {
				$newFields[] = $field;
			}
		}
		$config['fields'] = $newFields;
		
		// stdfilters
		if (is_array($config['stdfilters'])) {
			$newStdFilters = array();
			foreach ($config['stdfilters'] as $cond) {
				if ($cond['fieldid'] != $fieldid) {
					$newStdFilters[] = $cond; // crmv@126285
				}
			}
			$config['stdfilters'] = $newStdFilters;
		}
		
		// advanced filters
		if (is_array($config['advfilters'])) {
			$newAdvFilters = array();
			foreach ($config['advfilters'] as $group) {
				if ($group['conditions']) {
					$newConds = array();
					foreach ($group['conditions'] as $cond) {
						if ($cond['fieldid'] != $fieldid && (empty($cond['ref_fieldid']) || $cond['ref_fieldid'] != $fieldid)) {
							$newConds[] = $cond;
						}
					}
					$group['conditions'] = $newConds;
					$newAdvFilters[] = $group;
				}
			}
			$config['advfilters'] = $newAdvFilters;
		}
		
		// totals and summary
		if (is_array($config['totals'])) {
			$newTotals = array();
			foreach ($config['totals'] as $field) {
				if ($field['fieldid'] != $fieldid) {
					$newTotals[] = $field;
				}
			}
			$config['totals'] = $newTotals;
		}
		
		// now save it again
		
		return $this->updateReport($reportid, $config);
	}
	// crmv@101691e

	
	public function getRelations($reportid) {
		$config = $this->loadReport($reportid);
		
		return $config['relations'];
		
		/*
		Format of Relations:
		
		array(
			array(
				'name' => 'Contacts',		// name of the relation, if it's the root, then the module name
				'module' => 'Contacts',		// module of the relation, can be the special ProductsBlock
				'parent' => null,			// parent relation, null for the root
			),
			array(
				// this is a NtoN relation
				'name' => 'Contacts_Products_rel_21',	// relation name, which sould be [Module1]_[Module2]_rel_[RelationID]
				'module' => 'Products',					// module
				'parent' => 'Contacts',					// name of the parent relation
				'type' => ModuleRelation::$TYPE_NTON,	// type of the relation, should be NTON
				'relationid' => 21,						// id of the relation, (can be a fake id)
			),
			array(
				'name' => 'Contacts_Accounts_fld_75',	// for 1-N or N-1 the last part is _fld_[FIELDID]
				'module' => 'Accounts',				
				'parent' => 'Contacts',
				'type' => ModuleRelation::$TYPE_NTO1,	// here is NTO1 or 1TON
				'fieldid' => 75,						// here the fieldid must be specified, the relationid is not used
			),
		);
		
		*/
	}
	
	public function getColumns($reportid) {
		$config = $this->loadReport($reportid);
		return $config['fields'];
		
		/*
		Format of Fields:
		
		array(
			array(
				'fieldid' => 449,			// basic form, just the fieldid
											// if no parent is specified, the root module is used
			),
			array(
				'fieldid' => 453,
				'parent' => 'Contacts_Accounts_fld_75',		// the name of the parent relation
			),
			/*array(
				'fieldid' => 22,			
				'group' => true,				// group by this field
				'sortorder' => 'ASC',			// with this order
				'summary' => true,				// show it in summary as well
			),
			array(
				'module' => 'Events',
				'fieldid' => 268,
				'formula' => 'extract_year',	// apply a formula to the field
			),
		);
		*/
	}
	
	public function getTotalColumns($reportid) {
		$config = $this->loadReport($reportid);
		return $config['totals'];
		
		/*
		Format of Totals:
		
		array(
			array(
				'fieldid' => 9,					// fieldid
				'relation' => null,				// you can also specify the relation
				'aggregator' => 'SUM',			// which formula to use, to use more than one, specify the field more than once
			),
		);		
		*/
	}
	
	public function getSummaryColumns($reportid) {
		$config = $this->loadReport($reportid);
		return $config['summary'];
		
		/*
		Format of Summary columns:
		
		You can have maximum one column here
		
		array(
			array(
				'fieldid' => 9,									// the fieldid
				'aggregators' => array('SUM', 'MAX', 'MIN'),	// the aggregator formulas to use
			),
		);
		*/
	}
	
	public function getStdFilters($reportid) {
		$config = $this->loadReport($reportid);
		return $config['stdfilters'];
		
		/*
		Format of Std filters:
		array(
			array(
				'fieldid' => 98,				// fieldid
				'relation' => null,				// you can specify the relation
				'type' => 'datefilter',			// type of the std filter, only "datefilter" is supported
				'value' => 'thisfy',			// value for the datefilter
				'startdate' => '',				// if custom, here is the start date
				'enddate' => '',				// and end date
			)
		);
		*/
	}
	
	public function getAdvFilters($reportid) {
		$config = $this->loadReport($reportid);
		return $config['advfilters'];
		
		/*
		Format of advanced filters:
		
		$advfilters = array(
			// groups
			array(
				'conditions' => array(					// conditions for this group
					array(
						'fieldid' => 70,				// fieldid
						'comparator' => 'c',			// comparator
						'value' => 'b',					// value to compare with
						'glue' => 'or'					// glue condition for the next field, you can omit if it's the last field
					),
					array(
						'fieldid' => 90,
						'comparator' => 'e',
						'value' => 'admin',
					),
				),
				'glue' => 'and',						// glue for the next group
			),
			array(
				'conditions' => array(
					array(
						'fieldid' => 78,
						'comparator' => 'k',
						'reference' => true,			// this is a reference comparison, no need to use "value"
						'ref_fieldid' => 123,			// but you should specify the comparison fieldid
						'ref_relation' => 'ddfgdfg'		// and relation name
					),
				),
			),
		);
		*/
	}
	
	// crmv@128369
	public function getClusters($reportid) {
		$config = $this->loadReport($reportid);
		return $config['clusters'];
		
		/*
		Format of clusters:
		
		$clusters = array(
			array(
				'name' => 'clustername',	// name for the cluster
				'color' => '#rrbbgg',		// color
				'conditions' => array(), 	// conditions, same format of advanced filters
			),
		);
		*/
	}
	// crmv@128369e
	
	/**
	 * Prepare the report config for the edit panel, adding necessary fields and other informations
	 */
	public function prepareForEdit(&$config) {
		
		// fields
		foreach ($config['fields'] as &$field) {
			$finfo = $this->getFieldInfoById($field['fieldid']);
			if ($finfo['label']) {
				$field['label'] = $finfo['label'];
			} else {
				$field['label'] = getTranslatedString($finfo['fieldlabel'], $finfo['module']);
			}
			// value used in the <option> tag
			$chain = $this->getChainFromRelations($field['relation'], $config['relations']);
			$name = array(
				'fieldid' => intval($field['fieldid']),
				'chain' => $chain,
			);
			if ($field['formula']) {
				$name['formula'] = $field['formula'];
			}
			if ($field['group']) {
				$name['group'] = true;
				$name['sortorder'] = strtoupper($field['sortorder']) ?: 'ASC';
				if ($field['summary']) {
					$name['summary'] = true;
				}
			}
			$field['name'] = Zend_Json::encode($name);
			$field['module'] = $finfo['module'];
			// crmv@127526
			if (FakeModules::isFakeModule($finfo['module'])) {
				$field['single_label'] = FakeModules::getModuleLabel($finfo['module']);
			// crmv@127526e
			} else {
				$field['single_label'] = getTranslatedString('SINGLE_'.$finfo['module'], $finfo['module']);
			}
			$field['fieldname'] = $finfo['fieldname'];
			$field['wstype'] = $finfo['wstype'];
			$field['uitype'] = intval($finfo['uitype']);
		}
		unset($field);
		
		// stdfilters
		if (is_array($config['stdfilters'])) {
			foreach ($config['stdfilters'] as &$cond) {
				$finfo = $this->getFieldInfoById($cond['fieldid']);
				if ($finfo['label']) {
					$cond['label'] = $finfo['label'];
				} else {
					$cond['label'] = getTranslatedString($finfo['fieldlabel'], $finfo['module']);
				}
				$chain = $this->getChainFromRelations($cond['relation'], $config['relations']);
				$name = array(
					'fieldid' => intval($finfo['fieldid']),
					'chain' => $chain,
				);
				$cond['name'] = Zend_Json::encode($name);
				$cond['module'] = $finfo['module'];
				$cond['fieldname'] = $finfo['fieldname'];
				$cond['wstype'] = $finfo['wstype'];
				$cond['uitype'] = intval($finfo['uitype']);

				// crmv@106298
				if ($cond['value'] == 'custom') {
					// convert date from db format
					if ($cond['startdate']) $cond['startdate'] = getDisplayDate($cond['startdate']);
					if ($cond['enddate']) $cond['enddate'] = getDisplayDate($cond['enddate']);
				}
				// crmv@106298e

				// add the list of fields and modules chain
				$listmodules = array();
				for ($i=0; $i<count($chain); ++$i) {
					$subchain = array_slice($chain, 0, $i+1);
					$modules = $this->getModulesListForChain($config['reportid'], $subchain);
					$listmodules[] = array(
						'selected' => $chain[$i+1], // crmv@154814
						'list' => $modules,
					);
				}
				$listfields = $this->getStdFiltersFieldsListForChain($config['reportid'], $chain);
				$cond['listmodules'] = $listmodules;
				$cond['listfields'] = $listfields;
			}
			unset($cond);
		}
		
		// crmv@128369
		// advanced filters
		$this->prepareForEditAdvFilt($config['advfilters'], $config);
		
		// clusters
		if (is_array($config['clusters'])) {
			foreach ($config['clusters'] as &$cluster) {
				$this->prepareForEditAdvFilt($cluster['conditions'], $config);
			}
			unset($cluster);
			
			$config['clusterdata'] = $this->prepareForEditClusterData($config);
		}
		// crmv@128369e
		
		// totals and summary
		if (is_array($config['totals'])) {
			$totals = array();
			foreach ($config['totals'] as $field) {
				$finfo = $this->getFieldInfoById($field['fieldid']);
				if ($finfo['label']) {
					$field['label'] = $finfo['label'];
				} else {
					$field['label'] = getTranslatedString($finfo['fieldlabel'], $finfo['module']);
				}
				// value used in the <option> tag
				// crmv@117461
				if (is_array($field['chain'])) {
					$chain = $field['chain'];
				} else {
					$chain = $this->getChainFromRelations($field['relation'], $config['relations']);
				}
				// crmv@117461e
				$name = array(
					'fieldid' => intval($field['fieldid']),
					'chain' => $chain,
				);
				$field['name'] = Zend_Json::encode($name);
				$field['module'] = $finfo['module'];
				$field['fieldname'] = $finfo['fieldname'];
				$field['wstype'] = $finfo['wstype'];
				$field['uitype'] = intval($finfo['uitype']);
				
				// check if it's in summary
				if (is_array($config['summary'])) {
					foreach ($config['summary'] as $sfield) {
						if ($sfield['fieldid'] == $field['fieldid']) $field['summary'] = true;
					}
				}
				
				// add the list of fields and modules chain
				$listmodules = array();
				for ($i=0; $i<count($chain); ++$i) {
					$subchain = array_slice($chain, 0, $i+1);
					$modules = $this->getModulesListForChain($config['reportid'], $subchain);
					$listmodules[] = array(
						'selected' => $chain[$i+1], // crmv@154814
						'list' => $modules,
					);
				}
				$listfields = $this->getTotalsFieldsListForChain($config['reportid'], $chain);
				$field['listmodules'] = $listmodules;
				$field['listfields'] = $listfields;
				
				if (array_key_exists($field['name'], $totals)) {
					// add the formula
					$totals[$field['name']]['aggregators'][] = $field['aggregator'];
				} else {
					$field['aggregators'] = array($field['aggregator']);
					unset($field['aggregator']);
					$totals[$field['name']] = $field;
				}
			}
			$config['totals'] = array_values($totals);
		}
		
		// crmv@139057 - scheduling
		if($config['scheduling'] && !empty($config['scheduling']['recipients'])) {
			$recipients = array();
			foreach($config['scheduling']['recipients'] as $recipientType => $list) {
				foreach($list as $recipientId) {
					$opt = self::generateRecipientOption($recipientType, $recipientId);
					$recipients[$opt[0]] = $opt[1];
				}
			}
			$config['scheduling']['recipients']['list'] = $recipients;
		}
		// crmv@139057e
	}
	
	// crmv@139057
	public static function generateRecipientOption($type, $value, $name='') {
		$optionValue = strtolower($type).'::'.$value;
		switch($type) {
			case "users":
				if(empty($name)) $name = getUserFullName($value);
				$optionName = 'User::'.addslashes(decode_html($name));
				break;
			case "groups":
				if(empty($name)) {
					$groupInfo = getGroupName($value);
					$name = $groupInfo[0];
				}
				$optionName = 'Group::'.addslashes(decode_html($name));
				break;
			case "roles":
				if(empty($name)) $name = getRoleName ($value);
				$optionName = 'Roles::'.addslashes(decode_html($name));
				break;
			case "rs":
				if(empty($name)) $name = getRoleName ($value);
				$optionName = 'RoleAndSubordinates::'.addslashes(decode_html($name));
				break;
		}
		return array($optionValue, $optionName);
	}
	
	public function getSchedulingRecipients() {
		$recipients = array();
		
		$userDetails = getAllUserName();
		foreach($userDetails as $userId=>$userName) {
			$entry = self::generateRecipientOption('users', $userId, $userName);
			$recipients['users'][] = $entry;
		}
		
		$grpDetails = getAllGroupName();
		foreach($grpDetails as $groupId=>$groupName) {
			$entry = self::generateRecipientOption('groups', $groupId, $groupName);
			$recipients['groups'][] = $entry;
		}
		
		$roleDetails = getAllRoleDetails();
		foreach($roleDetails as $roleId=>$roleInfo) {
			$entry = self::generateRecipientOption('roles', $roleId, $roleInfo[0]);
			$recipients['roles'][] = $entry;
			$entry = self::generateRecipientOption('rs', $roleId, $roleInfo[0]);
			$recipients['rs'][] = $entry;
		}

		return $recipients;
	}
	// crmv@139057e
	
	// crmv@128369
	public function prepareForEditAdvFilt(&$filters, $config) {
		if (!is_array($filters)) return;
		
		foreach ($filters as &$group) {
			if ($group['conditions']) {
				foreach ($group['conditions'] as &$cond) {
					$finfo = $this->getFieldInfoById($cond['fieldid']);
					if ($finfo['label']) {
						$cond['label'] = $finfo['label'];
					} else {
						$cond['label'] = getTranslatedString($finfo['fieldlabel'], $finfo['module']);
					}
					$chain = $this->getChainFromRelations($cond['relation'], $config['relations']);
					$name = array(
						'fieldid' => intval($finfo['fieldid']),
						'chain' => $chain,
					);
					$cond['name'] = Zend_Json::encode($name);
					$cond['module'] = $finfo['module'];
					$cond['fieldname'] = $finfo['fieldname'];
					$cond['wstype'] = $finfo['wstype'];
					$cond['uitype'] = intval($finfo['uitype']);
					
					if ($cond['wstype'] == 'date' || $cond['wstype'] == 'datetime') {
						if ($cond['wstype'] == 'datetime') {
							// timezone adjustment
							$cond['value'] = adjustTimezone($cond['value'], 0, null, false);
						}
						// convert the date to the user format
						$cond['value'] = getDisplayDate($cond['value']);
						if ($cond['value2']) {
							if ($cond['wstype'] == 'datetime') {
								// timezone adjustment
								$cond['value2'] = adjustTimezone($cond['value2'], 0, null, false);
							}
							// convert the date to the user format
							$cond['value2'] = getDisplayDate($cond['value2']);
						}
					} elseif ($cond['wstype'] == 'time') {
						// adjust timezone for time fields
						$cond['value'] = adjustTimezone($cond['value'], 0, null, false);
						if ($cond['value2']) {
							$cond['value2'] = adjustTimezone($cond['value2'], 0, null, false);
						}
					}
					
					// reference
					if ($cond['reference']) {
						$relfinfo = $this->getFieldInfoById($cond['ref_fieldid']);
						if ($relfinfo['label']) {
							$cond['reflabel'] = $relfinfo['label'];
						} else {
							$cond['reflabel'] = getTranslatedString($relfinfo['fieldlabel'], $relfinfo['module']);
						}
						$refchain = $this->getChainFromRelations($cond['ref_relation'], $config['relations']);
						$name = array(
							'fieldid' => intval($cond['ref_fieldid']),
							'chain' => $refchain,
						);
						$cond['refvalue'] = Zend_Json::encode($name);
					}

					// add the list of fields and modules chain
					$listmodules = array();
					for ($i=0; $i<count($chain); ++$i) {
						$subchain = array_slice($chain, 0, $i+1);
						$modules = $this->getModulesListForChain($config['reportid'], $subchain);
						$listmodules[] = array(
							'selected' => $chain[$i+1], // crmv@154814
							'list' => $modules,
						);
					}
					$listfields = $this->getAdvFiltersFieldsListForChain($config['reportid'], $chain);
					$cond['listmodules'] = $listmodules;
					$cond['listfields'] = $listfields;
				}
				unset($cond);
			}
		}
		unset($group);

	}
	
	public function prepareForEditClusterData($config) {
		$data = array(); // crmv@179008
		
		$delKeys = array('listmodules', 'listfields', 'name', 'relation', 'wstype', 'uitype', 'fieldname', 'label', 'module', 'refvalue', 'reflabel', 'ref_fieldid');
		
		if ($config['clusters']) {
			foreach ($config['clusters'] as $cluster) {
				if (is_array($cluster['conditions'])) {
					foreach ($cluster['conditions'] as &$group) {
						$newconditions = array();
						foreach ($group['conditions'] as $cond) {
							$newcond = $cond;
							if ($cond['name']) {
								$nameinfo = Zend_Json::decode($cond['name']);
								$newcond['chain'] = $nameinfo['chain'];
							}
							if (!empty($cond['refvalue'])) {
								$refinfo = Zend_Json::decode($cond['refvalue']);
								$newcond['reference'] = true;
								$newcond['reffieldid'] = $refinfo['fieldid'];
								$newcond['refchain'] = $refinfo['chain'];
							} else {
								$newcond['reference'] = false;
							}
							$newcond = array_diff_key($newcond, array_flip($delKeys));
							$newconditions[] = $newcond;
						}
						$group['conditions'] = $newconditions;
					}
					unset($group);
				}
				$data[] = $cluster;
			}
		}

		$data = htmlspecialchars(Zend_Json::encode($data), ENT_COMPAT, $default_charset);
		return $data;
	}
	// crmv@128369e
	
	/**
	 * Prepare the data from the request, in the config format, ready to be saved
	 */
	public function prepareForSave($reportid, &$request) {
		global $current_user; // crmv@139057
		
		$config = array();
		
		$mode = ($reportid > 0 ? 'edit' : 'create');
		
		if ($mode == 'create') {
			$oldConfig = array();
			$config['module'] = vtlib_purify($request['primarymodule']);
			if (empty($config['module'])) throw new Exception("Primary module not specified");
			if (isPermitted($config['module'], 'index') != 'yes') {
				throw new Exception("You don't have the permission to read this module");
			}
			
		} else {
			$oldConfig = $this->loadReport($reportid);
			$config['module'] = $oldConfig['module'];
			$config['state'] = $oldConfig['state'];
			$config['owner'] = $oldConfig['owner'];
		}

		$config['reportname'] = vtlib_purify($request['reportname']);
		if (empty($config['reportname'])) throw new Exception("No report name specified");
		
		if (!empty($request['reportnewfolder'])) {
			// create the folder
			$folderid = $this->createFolder($request['reportnewfolder']);
			if (!$folderid) throw new Exception("Unable to create the report folder");
			$config['folderid'] = $folderid;
		} elseif (!empty($request['reportfolder'])) {
			$config['folderid'] = intval($request['reportfolder']);
		} else {
			throw new Exception("No folder specified");
		}
		
		$config['description'] = html_entity_decode(vtlib_purify($request['reportdes'])); // crmv@188917
		
		if (!empty($request['rep_assigned_to'])) {
			$config['owner'] = $request['rep_assigned_to'];
		}
		
		$config['reporttype'] = strtolower($request['reportType']) == 'summary' ? 'summary' : 'tabular';
		
		$fields = Zend_Json::decode($request['selectedfields']);
		
		if (empty($fields) || !is_array($fields)) {
			throw new Exception("No fields specified");
		}
		
		// now build the relations from the fields
		$relations = array();
		
		// add primary module
		$relations[$config['module']] = array(
			'name' => $config['module'],
			'module' => $config['module'],
		);
		
		$cfgFields = array();
		foreach ($fields as $fld) {
			$cfield = array(
				'fieldid' => $fld['fieldid'],
			);
			if ($fld['chain'] && count($fld['chain']) > 1) {
				$fieldrels = $this->getRelationsFromChain($fld['chain']);
				$lastrel = end($fieldrels);
				$relations = array_merge($relations, $fieldrels);
				$cfield['relation'] = $lastrel['name'];
			}
			if ($fld['group'] && $config['reporttype'] == 'summary') { // crmv@172355
				$cfield['group'] = true;
				$cfield['sortorder'] = ($fld['sortorder'] == 'DESC' ? 'DESC' : 'ASC');
				if ($fld['summary']) {
					$cfield['summary'] = true;
				}
			}
			if ($fld['formula']) {
				$cfield['formula'] = $fld['formula'];
			}
			
			$cfgFields[] = $cfield;
		}
		
		// stdfilters
		$stdfilters = Zend_Json::decode($request['stdfilters']);
		if ($stdfilters) {
			foreach ($stdfilters as &$cond) {
				$newcond = array(
					'fieldid' => $cond['fieldid'],
					'type' => 'datefilter',
					'value' => $cond['value'],
				);
				if ($cond['value'] == 'custom') {
					// crmv@106298
					// convert date from db format
					if ($cond['startdate']) $cond['startdate'] = getValidDBInsertDateTimeValue($cond['startdate']);
					if ($cond['enddate']) $cond['enddate'] = getValidDBInsertDateTimeValue($cond['enddate']);
					// crmv@106298e
					$newcond['startdate'] = $cond['startdate'];
					$newcond['enddate'] = $cond['enddate'];
				}
				if ($cond['chain'] && count($cond['chain']) > 1) {
					$fieldrels = $this->getRelationsFromChain($cond['chain']);
					$lastrel = end($fieldrels);
					$relations = array_merge($relations, $fieldrels);
					$newcond['relation'] = $lastrel['name'];
				}
				$cond = $newcond;
			}
			unset($cond);
			$config['stdfilters'] = $stdfilters;
		}
		
		// crmv@128369
		// advfilters
		$advfilters = Zend_Json::decode($request['advfilters']);
		if ($advfilters) {
			$config['advfilters'] = $this->prepareForSaveAdvFilt($advfilters, $relations);
		}
		
		// clusters
		$clusters = Zend_Json::decode($request['clusters']);
		if ($clusters) {
			foreach ($clusters as &$cluster) {
				if ($cluster['name'] && $cluster['conditions']) {
					$cluster['conditions'] = $this->prepareForSaveAdvFilt($cluster['conditions'], $relations);
				}
			}
			unset($cluster);
			$config['clusters'] = $clusters;
		}
		// crmv@128369e
		
		// totals
		$totals = Zend_Json::decode($request['totals']);
		if ($totals) {
			// crmv@117461
			foreach ($totals as &$fld) {
				if ($fld['chain'] && count($fld['chain']) > 1) {
					$fieldrels = $this->getRelationsFromChain($fld['chain']);
					$lastrel = end($fieldrels);
					$relations = array_merge($relations, $fieldrels); // crmv@174507
					$fld['relation'] = $lastrel['name'];
				}
			}
			unset($fld);
			// crmv@117461e
			$config['totals'] = $totals;
		}
		
		// summary
		$summary = Zend_Json::decode($request['summary']);
		if ($summary) {
			// crmv@174507
			// check if there are new relations
			foreach ($summary as &$fld) {
				if ($fld['chain'] && count($fld['chain']) > 1) {
					$fieldrels = $this->getRelationsFromChain($fld['chain']);
					$lastrel = end($fieldrels);
					$relations = array_merge($relations, $fieldrels);
					$fld['relation'] = $lastrel['name'];
				}
				unset($fld);
			}
			// crmv@174507e
			$config['summary'] = $summary;
		}
		
		// crmv@174507
		// TODO order relations by nesting level
		$config['relations'] = array_values($relations);
		$config['fields'] = $cfgFields;
		// crmv@174507e
		
		$config['sharingtype'] = $request['sharingtype'];
		if ($config['sharingtype'] == 'Shared') {
			// get the shared infos
			$sharing = array();
			$shareinfo = Zend_Json::decode($request['sharinginfo']);
			if (empty($shareinfo) || !is_array($shareinfo)) throw new Exception("No sharing members provided");
			foreach ($shareinfo as $member) {
				list($type, $id) = explode('::', $member);
				if ($type != 'users' && $type != 'groups') throw new Exception("Invalid member type: $type");
				$id = intval($id);
				if ($id > 0) {
					// TODO: check if allowed!
					$sharing[] = array(
						'shareid' => $id,
						'setype' => strtolower($type),
					);
				}
			}
			$config['sharing'] = $sharing;
		}
		
		// crmv@139057 - scheduling
		if ($mode == 'edit' && !is_admin($current_user)) {
			$config['scheduling'] = $oldConfig['scheduling'];
		} else {
			$scheduling = Zend_Json::decode($request['scheduling']);
			if(!empty($scheduling)) {
				$config['scheduling'] = $scheduling;
			}
		}
		// crmv@139057e
		
		return $config;
	}
	
	// crmv@128369
	public function prepareForSaveAdvFilt($advfilters, &$relations) {
		foreach ($advfilters as &$group) {
			if ($group['conditions']) {
				$newconditions = array();
				foreach ($group['conditions'] as $cond) {
					$finfo = $this->getFieldInfoById($cond['fieldid']);
					
					if ($finfo['wstype'] == 'date' || $finfo['wstype'] == 'datetime') {
						// convert the date from the user format to the db format
						// timezone is already converted in the function
						$cond['value'] = getValidDBInsertDateTimeValue($cond['value']);
					} elseif ($finfo['wstype'] == 'time') {
						$cond['value'] = adjustTimezone($cond['value'], 0, null, true);
					}
					
					$newcond = array(
						'fieldid' => $cond['fieldid'],
						'value' => $cond['value'],
						'comparator' => $cond['comparator'],
						'glue' => $cond['glue'],
					);
					if ($cond['comparator'] == 'bw' && $cond['value2']) {
						if ($finfo['wstype'] == 'date' || $finfo['wstype'] == 'datetime') {
							// convert the date from the user format to the db format
							// timezone is already converted in the function
							$cond['value2'] = getValidDBInsertDateTimeValue($cond['value2']);
						} elseif ($finfo['wstype'] == 'time') {
							// adjust timezone
							$cond['value2'] = adjustTimezone($cond['value2'], 0, null, true);
						}
						$newcond['value2'] = $cond['value2'];
					}
					if ($cond['chain'] && count($cond['chain']) > 1) {
						$fieldrels = $this->getRelationsFromChain($cond['chain']);
						$lastrel = end($fieldrels);
						$relations = array_merge($relations, $fieldrels);
						$newcond['relation'] = $lastrel['name'];
					}
					
					// reference
					if ($cond['reference'] && $cond['reffieldid'] && $cond['refchain']) {
						$newcond['reference'] = true;
						$newcond['ref_fieldid'] = $cond['reffieldid'];
						unset($newcond['value']);
						if (count($cond['refchain']) > 1) {
							$fieldrels = $this->getRelationsFromChain($cond['refchain']);
							$lastrel = end($fieldrels);
							$relations = array_merge($relations, $fieldrels);
							$newcond['ref_relation'] = $lastrel['name'];
						}
					}
					
					$newconditions[] = $newcond;
				}
				$group['conditions'] = $newconditions;
			}
		}
		unset($group);
		return $advfilters;
	}
	// crmv@128369e
	
	public function createFolder($name, $description = '') {
		global $current_user;
		
		// check if it exists
		$folderinfo = getEntityFoldersByName($name, 'Reports');

		if (!empty($folderinfo)) {
			throw new Exception(getTranslatedString('FOLDER_NAME_ALREADY_EXISTS'));
		}

		$folderid = addEntityFolder('Reports', $name, $description, $current_user->id, 'CUSTOMIZED');
		return $folderid;
	}
	
	protected function getChainFromRelations($entry, &$relations) {
		$chain = array();
		
		//search the main module
		foreach ($relations as $rel) {
			if (!$rel['parent']) {
				$mainModule = $rel['module'];
				break;
			}
		}
		
		if (!$entry) {
			$chain[] = $mainModule;
		} else {
			$rchain = array();
			do {
				$foundRel = false;
				foreach ($relations as $rel) {
					if ($rel['name'] == $entry) {
						$foundRel = $rel;
						$rchain[] = $rel['name'];
						break;
					}
				}
				$entry = ($foundRel ? $foundRel['parent'] : false);
			} while ($entry);
			$chain = array_reverse($rchain);
		}
		return $chain;
	}
	
	public function getRelationsFromChain($chain) {
		$list = array();
		$mainModule = reset($chain);

		if (count($chain) == 1) {
			return $list;
		} elseif (count($chain) > $this->max_relation_levels) {
			throw new Exception("Maximum level of relation nesting reached");
		}
		
		array_shift($chain);
		$previous = array(
			'name' => $mainModule,
			'module' => $mainModule,
		);
		foreach ($chain as $relname) {
			$rel = $this->parseRelationName($relname);
			$orel = $this->generateRelation($rel, $previous);
			$list[$orel['name']] = $orel;
			$previous = $orel;
		}
		
		return $list;
	}
	
	/**
	 *
	 */
	public function getModulesListForChain($reportid, $chain) {
		global $current_user; //crmv@125725
		
		$list = null;
		
		if (count($chain) >= $this->max_relation_levels) {
			return $list;
		}

		$RR = RelationManager::getInstance();
		$RR->enableFakeRelations(); // crmv@127526
		
		$availmods = $this->getAvailableModules();
		$availmods = array_keys($availmods);
		
		$last = $chain[count($chain)-1];
		$duplist = array();
		
		if (count($chain) == 1) {
			$prevmodule = $last;
		} else {
			$prevrel = $this->parseRelationName($last);
			if ($prevrel) {
				$prevmodule = $prevrel->getSecondModule();
				foreach ($chain as $c) {
					$pieces = explode('_', $c);
					$duplist[] = implode('_', array_slice($pieces, -2));
					// for N-N the relation ids are different, so I need to use the modules
					if (preg_match('/_rel_/', $c)) {
						$duplist[] = implode('_', array_reverse(array_slice($pieces, 0,2)));
					}
				}
			}
		}

		$rels = $RR->getRelations($prevmodule, null, $availmods);
		
		$list['none'] = array(
			'label' => '-- '.getTranslatedString('LBL_USE_THE_FIELDS_OF', 'Reports').' '.$this->getModuleLabel($prevmodule).' --',
			'value' => '',
		);
		if ($rels) {
			foreach ($rels as $rel) {

				$relmod = $rel->getSecondModule();
				
				// exclude some combinations of modules
				if (!$this->isRelationUsable($rel, $prevmodule)) {
					continue;
				}
				
				// generate label and name for the relation
				$relname = $this->generateRelationName($rel);
				$label = $this->getModuleLabel($relmod);
				
				// crmv@127526
				if (FakeModules::isFakeModule($prevmodule) || FakeModules::isFakeModule($relmod)) { // crmv@135260
					// use the module label
				// crmv@127526e
				} elseif ($rel->getType() != ModuleRelation::$TYPE_NTON) {
					$finfo = $this->getFieldInfoById($rel->getFieldId());
					//crmv@125725
					$test = getFieldVisibilityPermission($finfo['module'],$current_user->id, $finfo['fieldname']);
					if ($test != 0) continue;
					//crmv@125725e
					$flabel = $finfo['label'] ?: getTranslatedString($finfo['fieldlabel'], $finfo['module']);
					// crmv@121372
					$ftype = '';
					if ($relmod == $prevmodule) {
						$ftype = ', '.($rel->getType() == ModuleRelation::$TYPE_1TON ? '1-N' : 'N-1');
					}
					$label .= " (".strtolower(getTranslatedString('Field', 'APP_STRINGS')).' '.$flabel.$ftype.')';
					// crmv@121372e
				// crmv@125816
				} elseif ($rel->direction) {
					$relinfo = getRelatedListInfoById($rel->relationid);
					$label .= ' ('.getTranslatedString($relinfo['label'], $prevmodule).')';
				}
				// crmv@125816e
				
				// check for already used relations
				$pieces = explode('_', $relname);
				if (preg_match('/_rel_/', $relname)) {
					$dupkey = implode('_', array_slice($pieces, 0, 2));
				} else {
					$dupkey = implode('_', array_slice($pieces, -2));
				}

				if (!array_key_exists($relname, $list) && !in_array($dupkey, $duplist)) {
					$list[$relname] = array(
						'label' => $label,
						'value' => $relname,
					);
				}
			}
		}
		
		$list = array_values($list);
		
		// order by label
		usort($list, function($a, $b) {
			return strcasecmp($a['label'], $b['label']);
		});

		return $list;
	}
	
	/**
	 * Check if a particular relation is usable in the report
	 */
	protected function isRelationUsable(&$rel, $prevmodule = null) {
	
		$nlModules = array('Leads', 'Contacts', 'Accounts'); // crmv@127526
		
		$relmod = $rel->getSecondModule();
		if ($rel->getType() == ModuleRelation::$TYPE_NTON) {
			if (($prevmodule == 'Accounts' && $relmod == 'Potentials') || ($prevmodule == 'Potentials' && $relmod == 'Accounts')) {
				return false;
			} elseif (($prevmodule == 'Contacts' && $relmod == 'Potentials') || ($prevmodule == 'Potentials' && $relmod == 'Contacts')) {
				return false;
			// crmv@127526
			} elseif (($prevmodule == 'Newsletter' && in_array($relmod, $nlModules)) || (in_array($prevmodule, $nlModules) && $relmod == 'Newsletter')) {
				// direct newsletter - contact not supported
				return false;
			// crmv@127526e
			} elseif ($prevmodule == 'Products' && $relmod == 'Products') {
				// bundle products not supported
				return false;
			} elseif (($prevmodule == 'Calendar' && $relmod == 'Contacts') || ($prevmodule == 'Contacts' && $relmod == 'Calendar')) {
				// In tasks, it's a normal uitype 10
				return false;
			// crmv@155041
			} elseif (($prevmodule == 'Corsi' && $relmod == 'Contacts' && $rel->relationfn == 'get_contacts')) {
				return false;
			}
			// crmv@155041e
		} else {
			// calendar-contacts relation, with field (but it's a N-N)
			if (($prevmodule == 'Events' && $relmod == 'Contacts') || ($prevmodule == 'Contacts' && $relmod == 'Events')) {
				return false;
			}
		}
		
		return true;
	}
	
	public function isCalendarModule($module) {
		return in_array($module, array('Calendar', 'Events'));
	}
	
	public function getFieldsListForChain($reportid, $chain) {
		$list = array();
		
		$last = $chain[count($chain)-1];

		if (count($chain) == 1) {
			$prevmodule = $last;
		} else {
			$prevrel = $this->parseRelationName($last);
			if ($prevrel) {
				$prevmodule = $prevrel->getSecondModule();
			}
		}
		
		if ($prevmodule) {
			$list = $this->getFieldsForModule($prevmodule, $chain);
		}

		return $list;
	}
	
	public function getStdFiltersFieldsListForChain($reportid, $chain) {
		
		$useUitypes = array(5,6,23,70);
		
		$list = $this->getFieldsListForChain($reportid, $chain);
		
		// remove unwanted fields
		foreach ($list as &$block) {
			$newfields = array();
			foreach ($block['fields'] as $field) {
				if (in_array($field['wstype'], array('date', 'datetime')) && in_array($field['uitype'], $useUitypes)) {
					// by default, the modified time is selected
					if ($field['fieldname'] == 'modifiedtime') $field['selected'] = true;
					$newfields[] = $field;
				}
			}
			$block['fields'] = $newfields;
		}
		
		return $list;
	}
	
	public function getAdvFiltersFieldsListForChain($reportid, $chain) {
		return $this->getFieldsListForChain($reportid, $chain);
	}
	
	public function getTotalsFieldsListForChain($reportid, $chain) {

		$skipUitypes = array(50,70,10,26,51,52,53,77, 57,58,59, 66,68, 73,75,76,78,80,117);
		
		$list = $this->getFieldsListForChain($reportid, $chain);
		// TODO: this field: worktime
		
		// remove unwanted fields
		foreach ($list as &$block) {
			$newfields = array();
			foreach ($block['fields'] as $field) {
				if (in_array($field['wstype'], array('integer', 'double', 'currency')) && !in_array($field['uitype'], $skipUitypes)) {
					$newfields[] = $field;
				} elseif ($field['module'] == 'Timecards' && $field['fieldname'] == 'worktime') {
					$newfields[] = $field;
				}
			}
			$block['fields'] = $newfields;
		}
		
		return $list;
	}
	
	public function getFieldsForModule($module, $chain = null) {
		global $adb, $table_prefix, $current_user;
		
		// crmv@146138
		static $fieldsCache = array();
		// crmv@187367
		if (is_array($chain)) {
			$cchain = implode(':', $chain);
		} else {
			$cchain = $chain;
		}
        $cacheKey = $module.'_'.$cchain.'_'.$current_user->id;
        // crmv@187367e
        if (array_key_exists($cacheKey, $fieldsCache)) return $fieldsCache[$cacheKey];
        // crmv@146138e

		$list = array();
		$flist = array();
		
		// crmv@127526
		if (FakeModules::isFakeModule($module)) {
			$ifields = FakeModules::getFields($module);
			foreach ($ifields as $ifield) {
				$blockid = intval($ifield['block']);
				if (!$blockid) continue;
				if (!$this->isFieldAvailable($module, $ifield)) continue;
				$fieldval = array(
					'fieldid' => intval($ifield['fieldid']),
					'chain' => $chain
				);
				$flist[$blockid][] = array(
					'value' => Zend_Json::encode($fieldval),
					'label' => $ifield['label'],
					'single_label' => '', // needed ?
					'uitype' => $ifield['uitype'],
					'wstype' => $ifield['wstype'],
					'module' => $ifield['module'],
					'trans_module' => $ifield['trans_module'],
					'fieldname' => $ifield['fieldname'],
					'sequence' => intval($ifield['sequence']),
				);
			}
		// crmv@127526e
		} else {
			$info = vtws_describe($module, $current_user);
			if ($info && $info['fields']) {
				foreach ($info['fields'] as $wsfield) {
					$blockid = intval($wsfield['blockid']);
					if (!$blockid) continue;
					if (!$this->isFieldAvailable($module, $wsfield)) continue;
					$fieldval = array(
						'fieldid' => intval($wsfield['fieldid']),
						'chain' => $chain
					);

					// alter some labels
					if ($module == 'Events' && $wsfield['name'] == 'eventstatus') {
						$wsfield['label'] = getTranslatedString('LBL_ACTIVITY_STATUS', $module);
					} elseif ($module == 'Calendar' && $wsfield['name'] == 'taskstatus') {
						$wsfield['label'] = getTranslatedString('LBL_TASK_STATUS', $module);
					}

					$flist[$blockid][] = array(
						'value' => Zend_Json::encode($fieldval),
						'label' => $wsfield['label'],
						'single_label' => getTranslatedString('SINGLE_'.$module, $module),
						'uitype' => $wsfield['uitype'],
						'wstype' => $wsfield['type']['name'],
						'module' => $module,
						'trans_module' => $this->getModuleLabel($module),
						'fieldname' => $wsfield['name'],
						'sequence' => intval($wsfield['sequence']),
					);
				}
				// add tax fields for the whole record
				if (isInventoryModule($module)) {
					$taxFields = FakeModules::getTaxFields($module); // crmv@127526
					foreach ($taxFields as $k => &$tax) { // crmv@142670
						// crmv@142670
						if (!$this->isFieldAvailable($module, $tax)) {
							unset($taxFields[$k]);
							continue;
						}
						// crmv@142670e
						$blockid = $tax['block'];
						$fieldval = array(
							'fieldid' => intval($tax['fieldid']),
							'chain' => $chain
						);
						$tax['value'] = Zend_Json::encode($fieldval);
					}
					unset($tax);
					// crmv@142670
					if (count($taxFields) > 0) {
						$flist[$blockid] = array_values($taxFields);
					}
					// crmv@142670e
				}
				
				// crmv@198024
				if ($module == 'Products' && vtlib_isModuleActive('ConfProducts') && $adb->supportsJSON()) {
					$blockid = getBlockId(getTabid('Products'), 'LBL_VARIANT_INFORMATION');
					$confProd = CRMEntity::getInstance('ConfProducts');
					$struct = $confProd->getAllAttributes();
					foreach ($struct as $prodattr) {
						$fieldname = $prodattr['fieldname'];
						$fieldval = array(
							'fieldid' => intval($prodattr['fieldid']),
							'chain' => $chain
						);
						$flist[$blockid][] = array(
							'value' => Zend_Json::encode($fieldval),
							'label' => $prodattr['productname'].': '.$prodattr['fieldlabel'],
							'single_label' => getTranslatedString('SINGLE_'.$module, $module),
							'uitype' => $prodattr['uitype'],
							'wstype' => $prodattr['wstype'],
							'module' => $module,
							'trans_module' => $this->getModuleLabel($module),
							'fieldname' => $wsfield['fieldname'],
							//'sequence' => intval($wsfield['sequence']),
						);
					}
				}
				// crmv@198024e
			}
		}

		// TODO: special fields / permissions
		
		// now get the blocks informations
		foreach ($flist as $blockid => $fields) {
			$blockinfo = $this->getBlockInfoById($blockid);
			// order fields
			usort($fields, function($a, $b) {
				return $a['sequence'] - $b['sequence'];
			});
			$list[] = array(
				'blockid' => $blockid,
				'sequence' => intval($blockinfo['sequence']),
				'label' => $blockinfo['label'],
				'fields' => $fields
			);
		}
		
		usort($list, function($a, $b) {
			return $a['sequence'] - $b['sequence'];
		});
		
		$fieldsCache[$cacheKey] = $list; // crmv@146138
		
		return $list;
	}
	
	/**
	 * Return true if the field is enabled to be used in the report (either columns, filters, totals...)
	 */
	protected function isFieldAvailable($module, $finfo) {
		
		// list of fields not available in reports
		$skipFields = array(
			'ProductsBlock' => array('id'),
			'Calendar' => array('eventstatus', 'ical_uuid', 'recurr_idx', 'reminder_time', 'exp_duration'),
			'Events' => array('taskstatus', 'ical_uuid', 'recurr_idx', 'reminder_time', 'exp_duration', 'contact_id'),
		);
		
		$fieldname = $finfo['name'] ?: $finfo['fieldname'];
		if (is_array($skipFields[$module]) && in_array($fieldname, $skipFields[$module])) {
			return false;
		}
		
		return true;
	}
	
	protected function generateRelation(&$relation, &$parent = null) {
		$rel = array();
		
		$rel['module'] = $relation->getSecondModule();
		$rel['name'] = $this->generateRelationName($relation);
		$rel['type'] = $relation->getType();
		if ($rel['type'] == ModuleRelation::$TYPE_NTON) {
			$rel['relationid'] = $relation->relationid;
		} else {
			$rel['fieldid'] = $relation->getFieldId();
		}
		
		if ($parent) {
			$rel['parent'] = $parent['name'];
		}
		return $rel;
	}
	
	protected function generateRelationName(&$relation) {
		global $table_prefix;
		
		$parentmod = $relation->getFirstModule();
		$relmod = $relation->getSecondModule();
		if ($relation->getType() == ModuleRelation::$TYPE_NTON) {
			$relname = $parentmod.'_'.$relmod.'_rel_'.$relation->relationid;
		// crmv@121372
		} elseif ($parentmod == $relmod) {
			$type = $relation->getType();
			$relname = $parentmod.'_'.$relmod."_fld{$type}_".$relation->getFieldId();
		// crmv@121372e
		} else {
			$relname = $parentmod.'_'.$relmod.'_fld_'.$relation->getFieldId();
		}
		return $relname;
	}
	
	protected function parseRelationName($relname) {
		
		if (preg_match('/_fld_([0-9]+)$/', $relname, $matches)) {
			$fieldid = $matches[1];
			$rname = preg_replace('/_fld_.*/', '', $relname);
			list($module1, $module2) = explode('_', $rname);
			$rels = ModuleRelation::createFromFieldId($fieldid);
			// find the correct one
			foreach ($rels as $frel) {
				if (
					($frel->getFirstModule() == $module1 && $frel->getSecondModule() == $module2) ||
					($frel->getFirstModule() == $module2 && $frel->getSecondModule() == $module1)
				) {
					$rel = $frel;
					break;
				}
			}
		// crmv@121372
		// uitype 10 with same module, can be read in 2 ways!
		} elseif (preg_match('/_fld([24])_([0-9]+)$/', $relname, $matches)) {
			$type = $matches[1];
			$fieldid = $matches[2];
			$rname = preg_replace('/_fld[24]_.*/', '', $relname);
			list($module1, $module2) = explode('_', $rname); // should be the same!
			$rels = ModuleRelation::createFromFieldId($fieldid);
			// find the correct one
			foreach ($rels as $frel) {
				if ($frel->getFirstModule() == $module1 && $frel->getSecondModule() == $module2) {
					$rel = $frel;
					break;
				}
			}
			if ($rel && $rel->getType() != $type) {
				$rel->invert();
			}
		// crmv@121372e
		} elseif (preg_match('/_rel_([0-9]+)$/', $relname, $matches)) {
			$relationid = $matches[1];
			$rname = preg_replace('/_rel_.*/', '', $relname);
			list($module1, $module2) = explode('_', $rname);
			$rel = ModuleRelation::createFromRelationId($relationid);
		} else {
			throw new Exception("Unable to find a relation for $relname");
		}
		
		// invert the relation if needed
		if ($rel && $module1 != $module2 && $module1 == $rel->getSecondModule() && $module2 == $rel->getFirstModule()) { // crmv@121372
			$rel->invert();
		}
		
		return $rel;
	}
	
	function getVisibleCriteria($recordid='') {
		global $adb, $table_prefix;

		$filter = array();
		if ($recordid!='') {
			$config = $this->loadReport($recordid);
			$selcriteria = $config['sharingtype'] ?: 'Public';
		} else {
			$selcriteria = 'Public';
		}
		$res = $adb->query("SELECT name FROM ".$table_prefix."_reportvisibility");
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$filtername = trim($row['name']);
			if ($filtername == 'Private') {
				$FilterValue=getTranslatedString('PRIVATE_FILTER');
			} elseif($filtername=='Shared') {
				$FilterValue=getTranslatedString('SHARE_FILTER');
			} else {
				$FilterValue=getTranslatedString('PUBLIC_FILTER');
			}
			$shtml['value'] = $filtername;
			$shtml['text'] = $FilterValue;
			$shtml['selected'] = ($filtername == $selcriteria ? "selected" : "");
			$filter[] = $shtml;
		}		
		return $filter;
	}

	function getShareInfo($recordid=''){
		global $adb,$table_prefix;
		$member_query = $adb->pquery("SELECT ".$table_prefix."_reportsharing.setype,".$table_prefix."_users.id,".$table_prefix."_users.user_name FROM ".$table_prefix."_reportsharing INNER JOIN ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_reportsharing.shareid WHERE ".$table_prefix."_reportsharing.setype='users' AND ".$table_prefix."_reportsharing.reportid = ?",array($recordid));
		$noofrows = $adb->num_rows($member_query);
		if($noofrows > 0){
			for($i=0;$i<$noofrows;$i++){
				$userid = $adb->query_result($member_query,$i,'id');
				$username = $adb->query_result($member_query,$i,'user_name');
				$setype = $adb->query_result($member_query,$i,'setype');
				$member_data[] = Array('id'=>$setype."::".$userid,'name'=>$setype."::".$username);
			}
		}
		
		$member_query = $adb->pquery("SELECT ".$table_prefix."_reportsharing.setype,".$table_prefix."_groups.groupid,".$table_prefix."_groups.groupname FROM ".$table_prefix."_reportsharing INNER JOIN ".$table_prefix."_groups on ".$table_prefix."_groups.groupid = ".$table_prefix."_reportsharing.shareid WHERE ".$table_prefix."_reportsharing.setype='groups' AND ".$table_prefix."_reportsharing.reportid = ?",array($recordid));
		$noofrows = $adb->num_rows($member_query);
		if($noofrows > 0){
			for($i=0;$i<$noofrows;$i++){
				$grpid = $adb->query_result($member_query,$i,'groupid');
				$grpname = $adb->query_result($member_query,$i,'groupname');
				$setype = $adb->query_result($member_query,$i,'setype');
				$member_data[] = Array('id'=>$setype."::".$grpid,'name'=>$setype."::".$grpname);
			}
		}
		return $member_data;
	}
	
	/**
	 * Return the tabid event if the module is not active
	 */
	public function getTabId($module) {
		global $adb, $table_prefix;
		$res = $adb->pquery("SELECT tabid FROM {$table_prefix}_tab WHERE name = ?", array($module));
		$tabid = intval($adb->query_result_no_html($res, 0, 'tabid'));
		return $tabid;
	}
	
	public function getFieldInfoById($fieldid) {
		global $adb, $table_prefix;
		
		if (!is_array($this->fields_cache_id)) $this->fields_cache_id = array();

		if (!$this->fields_cache_id[$fieldid]) {
			// crmv@127526
			$module = '';
			if (FakeModules::isFakeFieldId($fieldid, $module)) {
				$finfo = FakeModules::getFieldInfoById($fieldid, $module);
			// crmv@198024
			} elseif ($fieldid >= FakeModules::$baseConfProdFieldId) {
				if (vtlib_isModuleActive('ConfProducts')) {
					$confprod = CRMEntity::getInstance('ConfProducts');
					$attr = $confprod->getAttributeFromFieldid($fieldid);
					if ($attr) {
						$finfo = $attr;
						$finfo['fieldid'] = intval($fieldid);
						$finfo['module'] = 'Products';
						$finfo['tablename'] = $table_prefix.'_products';
						$finfo['columnname'] = 'confprodinfo';
						$finfo['typeofdata'] = 'V~O';
					}
				}
			// crmv@198024e
			} else {
			// crmv@127526e
				$res = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE fieldid = ?", array($fieldid));
				if ($res && $adb->num_rows($res) > 0) {
					$finfo = $adb->FetchByAssoc($res, -1, false);
					
					$wsfield = WebserviceField::fromArray($adb,$finfo);
					
					// fixes for the stupid calendar
					if ($finfo['fieldname'] == 'eventstatus') {
						$finfo['fieldlabel'] = 'LBL_ACTIVITY_STATUS';
					} elseif ($finfo['fieldname'] == 'taskstatus') {
						$finfo['fieldlabel'] = 'LBL_TASK_STATUS';
					} elseif ($finfo['fieldname'] == 'date_start') {
						$finfo['fieldlabel'] = 'Start Date';
					}
			
					$finfo['module'] = getTabname($finfo['tabid']);
					$finfo['wstype'] = $wsfield->getFieldDataType();
					$finfo['typeofdata'] = $wsfield->getTypeOfData();
					$finfo['is_reference'] = ($finfo['wstype'] == 'reference');
					$finfo['is_entityname'] = $wsfield->isEntityNameField();
					if ($finfo['uitype'] == 10) {
						// add related modules
						$res2 = $adb->pquery("SELECT relmodule FROM {$table_prefix}_fieldmodulerel WHERE fieldid = ?",array($fieldid));
						if ($res2 && $adb->num_rows($res2) > 0) {
							$relmods = array();
							while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
								$relmods[] = $row2['relmodule'];
							}
							$finfo['relmodules'] = array_unique($relmods);
						}
					}
					if (in_array($finfo['wstype'], array('picklist', 'multipicklist', 'picklistmultilanguage'))) {
						$aval = array();
						$allowedValues = $wsfield->getPicklistDetails();
						foreach ($allowedValues as $av) {
							$aval[$av['value']] = $av;
						}
						$finfo['allowed_values'] = $aval;
					}
					
				}
			}
			$this->fields_cache_id[$fieldid] = $finfo;
		}
		
		return $this->fields_cache_id[$fieldid];
	}
	
	public function getFieldInfoByName($module, $fieldname) {
		global $adb, $table_prefix;
		
		if (!is_array($this->fields_cache_name)) $this->fields_cache_name = array();
		
		if (!$this->fields_cache_name[$module][$fieldname]) {
			// crmv@127526
			if (FakeModules::isFakeModule($module)) {
				$finfo = FakeModules::getFieldInfo($fieldname, $module);
			} elseif (isInventoryModule($module) && substr($fieldname, 0, 3) == 'tax') {
				$finfo = FakeModules::getTaxFieldInfo($module, $fieldname);
			// crmv@127526e
			} else {
				$tabid = $this->getTabid($module);
				$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array($tabid, $fieldname));
				if ($res && $adb->num_rows($res) > 0) {
					$fieldid = $adb->query_result_no_html($res, 0, 'fieldid');
					$finfo = $this->getFieldInfoById($fieldid);
				}
			}
			if ($finfo) {
				$this->fields_cache_name[$module][$finfo['fieldname']] = $finfo;
			}
		}
		
		return $this->fields_cache_name[$module][$fieldname];
		
	}
	
	public function getBlockInfoById($blockid) {
		global $adb, $table_prefix, $current_language;
		
		if (!is_array($this->blocks_cache_id)) $this->blocks_cache_id = array();
		
		if (!$this->blocks_cache_id[$blockid]) {
			// crmv@127526
			$module = '';
			if (FakeModules::isFakeBlockId($blockid, $module)) {
				$binfo = FakeModules::getBlockInfoById($blockid, $module);
			// crmv@127526e
			} else {
				$res = $adb->pquery("SELECT * FROM {$table_prefix}_blocks WHERE blockid = ?", array($blockid));
				if ($res && $adb->num_rows($res) > 0) {
					$binfo = $adb->FetchByAssoc($res, -1, false);
					if ($binfo) {
						$module = getTabname($binfo['tabid']);
						if ($this->isCalendarModule($module) && empty($binfo['blocklabel'])) {
							// why do I always have to deal with that stupid calendar??
							//if (in_array($block['blockid'], array())
							$binfo['blocklabel'] = 'LBL_DESCRIPTION_INFORMATION';
						}
						$binfo['label'] = getTranslatedString($binfo['blocklabel'], $module);
						if ($binfo['label'] == $binfo['blocklabel']) {
							$binfo['label'] = getTranslatedString($binfo['blocklabel'], 'APP_STRINGS');
						}
					}
				}
			}
			$this->blocks_cache_id[$blockid] = $binfo;
		}
		
		return $this->blocks_cache_id[$blockid];
	}
	
	// crmv@127526 - removed functions

	/** Function to get the reports under a report folder
	 *  @ param $folderid : Type Integer
	 *  This Returns $reports_array in the following format
	 *    $reports_array = array ($reportid=>$reportname,$reportid=>$reportname1,...,$reportidn=>$reportname)
	 */
	static public function getReportsinFolder($folderid) {
		global $adb, $table_prefix;

		$query = 'select reportid,reportname from '.$table_prefix.'_report where folderid=?';
		$result = $adb->pquery($query, array($folderid));
		$reports_array = Array();
		for($i=0;$i < $adb->num_rows($result);$i++) {
			$reportid = $adb->query_result_no_html($result,$i,'reportid');
			$reportname = $adb->query_result($result,$i,'reportname');
			$reports_array[$reportid] = $reportname;
		}
		return (count($reports_array) > 0 ? $reports_array : false);
	}

	// crmv@181170
	public static function saveReportAndRun($reportId) {
		global $mod_strings, $app_strings, $current_language, $currentModule;
		$oldCurrentMod = $currentModule;
		$oldModStrings = $mod_strings;
		$_REQUEST['record'] = $reportId;
		$_REQUEST['tab'] = '';
		$_REQUEST['embedded'] = '1';
		$currentModule = 'Reports';
		$mod_strings = return_module_language($current_language, $currentModule);
		require('modules/Reports/SaveAndRun.php');
		$mod_strings = $oldModStrings;
		$currentModule = $oldCurrentMod;
	}
	// crmv@181170e

}

// here for compatibility only
function getReportsinFolder($folderid) {
	return Reports::getReportsinFolder($folderid);
}
