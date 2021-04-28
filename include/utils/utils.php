<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('vendor/autoload.php'); // crmv@180826
require_once('include/VTEAutoloader.php'); // crmv@150748
require_once('include/utils/LegacyFunctions.php'); // crmv@150748
require_once('include/utils/InventoryFunctions.php');
require_once('include/VteSession.php'); // crmv@128133
require_once('include/database/PearDatabase.php');
require_once('include/BaseClasses.php'); // crmv@42024
require_once('include/VTEBaseLogger.php'); // crmv@176614
require_once('include/utils/Cache.php'); //crmv@47905bis
require_once('include/ComboUtil.php'); //new
require_once('include/utils/ListViewUtils.php');
require_once('include/utils/EditViewUtils.php');
require_once('include/utils/PageHeader.php');//crmv@208173
require_once('include/utils/UserAuthtoken.php');//crmv@208173
require_once('include/utils/DetailViewUtils.php');
require_once('include/utils/CommonUtils.php');
require_once('include/utils/SearchUtils.php');
require_once('include/FormValidationUtil.php');
require_once('include/CustomFieldUtil.php');//crmv@208173
//crmv@208173
require_once('include/events/SqlResultIterator.inc');
require_once('data/CRMEntity.php');
require_once 'vtlib/Vtecrm/Language.php';
require_once('include/fields/DateTimeField.php'); //crmv@392267
require_once('include/fields/CurrencyField.php'); //crmv@392267
require_once('include/utils/ResourceVersion.php'); // crmv@128369
// crmv@150748 crmv@151308 - removed autoloadable classes
require_once('modules/ChangeLog/EditViewChangeLog.php'); //crmv@171832
require_once('include/RelatedListView.php');//crmv@208173

// Constants to be defined here

// For Customview status.
define("CV_STATUS_DEFAULT", 0);
define("CV_STATUS_PRIVATE", 1);
define("CV_STATUS_PENDING", 2);
define("CV_STATUS_PUBLIC", 3);

// For Restoration.
define("RB_RECORD_DELETED", 'delete');
define("RB_RECORD_INSERTED", 'insert');
define("RB_RECORD_UPDATED", 'update');

// creates a global instance
global $metaLogs;
if (!$metaLogs) $metaLogs = new MetaLogs(); // crmv@128133

/** Function to return a full name
  * @param $row -- row:: Type integer
  * @param $first_column -- first column:: Type string
  * @param $last_column -- last column:: Type string
  * @returns $fullname -- fullname:: Type string
  *
*/
function return_name(&$row, $first_column, $last_column)
{
	global $log;
	$log->debug("Entering return_name(".$row.",".$first_column.",".$last_column.") method ...");
	$first_name = "";
	$last_name = "";
	$full_name = "";

	if(isset($row[$first_column]))
	{
		$first_name = stripslashes($row[$first_column]);
	}

	if(isset($row[$last_column]))
	{
		$last_name = stripslashes($row[$last_column]);
	}

	$full_name = $first_name;

	// If we have a first name and we have a last name
	if($full_name != "" && $last_name != "")
	{
		// append a space, then the last name
		$full_name .= " ".$last_name;
	}
	// If we have no first name, but we have a last name
	else if($last_name != "")
	{
		// append the last name without the space.
		$full_name .= $last_name;
	}

	$log->debug("Exiting return_name method ...");
	return $full_name;
}

/** Function returns the user key in user array
  * @param $add_blank -- boolean:: Type boolean
  * @param $status -- user status:: Type string
  * @param $assigned_user -- user id:: Type string
  * @param $private -- sharing type:: Type string
  * @returns $user_array -- user array:: Type array
  *
*/

//used in module file
function get_user_array($add_blank=true, $status="Active", $assigned_user="",$private="",$read_write="")	//crmv@28496
{
	global $log,$showfullusername;
	$log->debug("Entering get_user_array(".$add_blank.",". $status.",".$assigned_user.",".$private.") method ...");
	global $current_user,$table_prefix;
	$focusUsers = CRMEntity::getInstance('Users');	//crmv@104988
	if(isset($current_user) && $current_user->id != '')
	{
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	}
	static $user_array = null;
	$module=$_REQUEST['module'];
	
	if($user_array == null)
	{
		require_once('include/database/PearDatabase.php');
		$db = PearDatabase::getInstance();
		$temp_result = Array();
		// Including deleted vte_users for now.
		if (empty($status)) {
			$query = "SELECT id from ".$table_prefix."_users";
			$params = array();
		} else {
			if($private == 'private')
			{
				$log->debug("Sharing is Private. Only the current user should be listed");
				$query = "select id as id,user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users where id=? and status='Active'";
				$params = array($current_user->id);
				if (!empty($assigned_user)) {
					$query .= " OR id=?";
					array_push($params, $assigned_user);
				}
				//crmv@28496
				$query.=" union select ".$table_prefix."_user2role.userid as id,".$table_prefix."_users.user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like ? and status='Active' union";
				if($read_write == 'Read') {
					$query .= " select shareduserid as id,".$table_prefix."_users.user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_tmp_read_u_per inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_tmp_read_u_per.shareduserid where status='Active' and ".$table_prefix."_tmp_read_u_per.userid=? and ".$table_prefix."_tmp_read_u_per.tabid=?";
				} else {
					$query .= " select shareduserid as id,".$table_prefix."_users.user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_tmp_write_u_per inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_tmp_write_u_per.shareduserid where status='Active' and ".$table_prefix."_tmp_write_u_per.userid=? and ".$table_prefix."_tmp_write_u_per.tabid=?";
				}
				//crmv@28496e
				array_push($params,$current_user_parent_role_seq."::%", $current_user->id, getTabid($module));
				//crmv@23460
				if ($module == 'Calendar') {
					$query.=" UNION SELECT userid AS id, ".$table_prefix."_users.user_name AS user_name, first_name AS first_name, last_name AS last_name FROM ".$table_prefix."_sharedcalendar INNER JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_sharedcalendar.userid WHERE STATUS = 'Active' AND ".$table_prefix."_sharedcalendar.sharedid = ?";
					array_push($params,$assigned_user);
				}
				//crmv@23460e
			}
			else
			{
				$log->debug("Sharing is Public. All ".$table_prefix."_users should be listed");
				$query = "SELECT id, user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users WHERE status=?";
				$params = array($status);
				//crmv@50796
				if (!empty($assigned_user)) {
					$query .= " OR id=?";
					array_push($params, $assigned_user);
				}
				//crmv@50796e
			}
		}


		$query .= " order by $focusUsers->default_order_by $focusUsers->default_sort_order";	//crmv@104988

		$result = $db->pquery($query, $params, true, "Error filling in user array: ");

		if ($add_blank==true){
			// Add in a blank row
			$temp_result[''] = '';
		}
		// Get the id and the name.
		while($row = $db->fetchByAssoc($result))
		{
			//crmv@60390 crmv@104988
			$usernames_cache['withname'] = $focusUsers->formatUserName($row['id'], $row, true);
			$usernames_cache['withoutname'] = $focusUsers->formatUserName($row['id'], $row, false);
			$temp_result[$row['id']] = getUserName($row['id'],$showfullusername,$usernames_cache);
			//crmv@60390e crmv@104988e
		}
		$user_array = &$temp_result;
	}
	$log->debug("Exiting get_user_array method ...");
	return $user_array;
}

// crmv@129138
function get_group_array($add_blank=true, $status="Active", $assigned_user="",$private="")
{
	global $log;
	$log->debug("Entering get_group_array(".$add_blank.",". $status.",".$assigned_user.",".$private.") method ...");
	global $current_user, $adb, $table_prefix;
	
	$temp_result = [];
	$module=$_REQUEST['module'];

	$query = "SELECT groupid, groupname from ".$table_prefix."_groups";
	$params = array();

	if($private == 'private'){
	
		if(isset($current_user) && $current_user->id != '') {
			require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
			require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		}

		$query .= " WHERE groupid=?";
		$params = array($assigned_user);	//crmv@68489

		if(count($current_user_groups) != 0) {
			$query .= " OR ".$table_prefix."_groups.groupid in (".generateQuestionMarks($current_user_groups).")";
			array_push($params, $current_user_groups);
		}
		$log->debug("Sharing is Private. Only the current user should be listed");
		$query .= " union select ".$table_prefix."_group2role.groupid as groupid,".$table_prefix."_groups.groupname as groupname from ".$table_prefix."_group2role inner join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_group2role.groupid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_group2role.roleid where ".$table_prefix."_role.parentrole like ?";
		array_push($params, $current_user_parent_role_seq."::%");

		if(count($current_user_groups) != 0) {
			$query .= " union select ".$table_prefix."_groups.groupid as groupid,".$table_prefix."_groups.groupname as groupname from ".$table_prefix."_groups inner join ".$table_prefix."_group2rs on ".$table_prefix."_groups.groupid=".$table_prefix."_group2rs.groupid where ".$table_prefix."_group2rs.roleandsubid in (".generateQuestionMarks($parent_roles).")";
			array_push($params, $parent_roles);
		}

		$query .= " union select sharedgroupid as groupid,".$table_prefix."_groups.groupname as groupname from ".$table_prefix."_tmp_write_g_per inner join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_tmp_write_g_per.sharedgroupid where ".$table_prefix."_tmp_write_g_per.userid=?";
		array_push($params, $current_user->id);

		$query .= " and ".$table_prefix."_tmp_write_g_per.tabid=?";
		array_push($params,  getTabid($module));
	}
	$query .= " order by groupname ASC";
	
	if ($add_blank==true){
		// Add in a blank row
		$temp_result[''] = '';
	}
	
	static $groupCache = array();
	$key = $adb->convert2Sql($query, $adb->flatten_array($params));
	if (!isset($groupCache[$key])) {
		$listGroups = array();
		$result = $adb->pquery($query, $params, true, "Error filling in user array: ");
		// Get the id and the name.
		while($row = $adb->fetchByAssoc($result)) {
			$listGroups[$row['groupid']] = $row['groupname'];
		}
		$groupCache[$key] = $listGroups;
	}
	
	foreach ($groupCache[$key] as $gid => $gname) {
		$temp_result[$gid] = $gname;
	}

	$log->debug("Exiting get_group_array method ...");
	return $temp_result;
}
// crmv@129138e

/** Function skips executing arbitary commands given in a string
  * @param $string -- string:: Type string
  * @param $maxlength -- maximun length:: Type integer
  * @returns $string -- escaped string:: Type string
  *
*/

function clean($string, $maxLength)
{
	global $log;
	$log->debug("Entering clean(".$string.",". $maxLength.") method ...");
	$string = substr($string, 0, $maxLength);
	$log->debug("Exiting clean method ...");
	return escapeshellcmd($string);
}

/**
 * A temporary method of generating GUIDs of the correct format for our DB.
 * @return String contianing a GUID in the format: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
*/
function create_guid()
{
	global $log;
	$log->debug("Entering create_guid() method ...");
        $microTime = microtime();
	list($a_dec, $a_sec) = explode(" ", $microTime);

	$dec_hex = sprintf("%x", $a_dec* 1000000);
	$sec_hex = sprintf("%x", $a_sec);

	ensure_length($dec_hex, 5);
	ensure_length($sec_hex, 6);

	$guid = "";
	$guid .= $dec_hex;
	$guid .= create_guid_section(3);
	$guid .= '-';
	$guid .= create_guid_section(4);
	$guid .= '-';
	$guid .= create_guid_section(4);
	$guid .= '-';
	$guid .= create_guid_section(4);
	$guid .= '-';
	$guid .= $sec_hex;
	$guid .= create_guid_section(6);

	$log->debug("Exiting create_guid method ...");
	return $guid;

}

/** Function to create guid section for a given character
  * @param $characters -- characters:: Type string
  * @returns $return -- integer:: Type integer``
  */
function create_guid_section($characters)
{
	global $log;
	$log->debug("Entering create_guid_section(".$characters.") method ...");
	$return = "";
	for($i=0; $i<$characters; $i++)
	{
		$return .= sprintf("%x", rand(0,15));
	}
	$log->debug("Exiting create_guid_section method ...");
	return $return;
}

/** Function to ensure length
  * @param $string -- string:: Type string
  * @param $length -- length:: Type string
  */

function ensure_length(&$string, $length)
{
	global $log;
	$log->debug("Entering ensure_length(".$string.",". $length.") method ...");
	$strlen = strlen($string);
	if($strlen < $length)
	{
		$string = str_pad($string,$length,"0");
	}
	else if($strlen > $length)
	{
		$string = substr($string, 0, $length);
	}
	$log->debug("Exiting ensure_length method ...");
}

/**
 * Return an array of directory names.
 */
function get_themes() {
	global $log;
	$log->debug("Entering get_themes() method ...");
	if ($dir = @opendir("./themes")) {
		while (($file = readdir($dir)) !== false) {
			if ($file != ".." && $file != "." && $file != "CVS" && $file != "Attic" && $file != "akodarkgem" && $file != "bushtree" && $file != "coolblue" && $file != "Amazon" && $file != "busthree" && $file != "Aqua" && $file != "nature" && $file != "orange" && $file != "blue") {
				if(is_dir("./themes/".$file)) {
					if(!($file[0] == '.')) {
						// set the initial theme name to the filename
						$name = $file;

						// if there is a configuration class, load that.
						if(is_file("./themes/$file/config.php"))
						{
							require_once("./themes/$file/config.php");
						}

						if(is_file("./themes/$file/style.css"))
						{
							$filelist[$file] = $name;
						}
					}
				}
			}
		}
		closedir($dir);
	}
	unset($filelist['ztv']);	// ztv theme is not supported
	ksort($filelist);
	$log->debug("Exiting get_themes method ...");
	return $filelist;
}

/**
 * Very cool algorithm for sorting multi-dimensional arrays.  Found at http://us2.php.net/manual/en/function.array-multisort.php
 * Syntax: $new_array = array_csort($array [, 'col1' [, SORT_FLAG [, SORT_FLAG]]]...);
 * Explanation: $array is the array you want to sort, 'col1' is the name of the column
 * you want to sort, SORT_FLAGS are : SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
 * you can repeat the 'col',FLAG,FLAG, as often you want, the highest prioritiy is given to
 * the first - so the array is sorted by the last given column first, then the one before ...
 */
function array_csort() {
   global $log;
   $log->debug("Entering array_csort() method ...");
   $args = func_get_args();
   $marray = array_shift($args);
   $i = 0;

   $msortline = "return(array_multisort(";
   foreach ($args as $arg) {
	   $i++;
	   if (is_string($arg)) {
		   foreach ($marray as $row) {
			   $sortarr[$i][] = $row[$arg];
		   }
	   } else {
		   $sortarr[$i] = $arg;
	   }
	   $msortline .= "\$sortarr[".$i."],";
   }
   $msortline .= "\$marray));";

   eval($msortline);
   $log->debug("Exiting array_csort method ...");
   return $marray;
}

/** Function to set default varibles on to the global variable
  * @param $defaults -- default values:: Type array
       */
function set_default_config(&$defaults)
{
	global $log;
	$log->debug("Entering set_default_config method ...");

	foreach ($defaults as $name=>$value)
	{
		if ( ! isset($GLOBALS[$name]) )
		{
			$GLOBALS[$name] = $value;
		}
	}
	$log->debug("Exiting set_default_config method ...");
}

/** Function to convert the given string to html
  * @param $string -- string:: Type string
  * @param $ecnode -- boolean:: Type boolean
  * @returns $string -- string:: Type string
  *
  */
// Possibly the worst function ever! 
// Changing the queries results using parameters from the request... what could go wrong?
function to_html($string, $encode=true)
{
	global $log,$default_charset;
	$module = $_REQUEST['module'] ?? '';
	$file = $_REQUEST['file'] ?? '';
	$action = $_REQUEST['action'] ?? '';
	$search = $_REQUEST['search'] ?? '';

	$doconvert = false;

	$ajax_action = '';
	if($module != 'Settings' && $file != 'ListView' && $module != 'Portal' && $module != "Reports") {
		$ajax_action = $module.'Ajax';
	}

	if(is_string($string))
	{
		if($action != 'CustomView' && $action != 'Export' && $action != $ajax_action && $action != 'LeadConvertToEntities' && $action != 'CreatePDF' && $action != 'CreatePDFFromTemplate' && $action != 'ConvertAsFAQ' && $action != 'CreateSOPDF' && $action != 'SendPDFMail' ) // crmv@104506 crmv@188369 crmv@208472
		{
			$doconvert = true;
		}
		else if($search == true)
		{
			// Fix for tickets #4647, #4648. Conversion required in case of search results also.
			$doconvert = true;
		}

		// crmv@124091
		$ajaxCall = $_REQUEST['ajaxCall'] ?? '';
		if ($ajaxCall == 'CalendarView') {
			$doconvert = true;
		}
		// crmv@124091e

		if ($doconvert == true)
		{
			if(strtolower($default_charset) == 'utf-8')
				$string = htmlentities($string, ENT_QUOTES, $default_charset);
			else
				$string = preg_replace(array('/</', '/>/', '/"/'), array('&lt;', '&gt;', '&quot;'), $string);
		}
	}

	//$log->debug("Exiting to_html method ...");
	return $string;
}

/** 
 * @deprecated
 * Please use getTabModuleName instead, which uses a cache
 * This function returns the name even if the module is not active
 */
function getTabname($tabid) {
	global $adb, $table_prefix;
	
	// crmv@193648
	$tabname = VTCacheUtils::lookupModulename($tabid);
	if ($tabname === false) {
		$result = $adb->pquery("select name from {$table_prefix}_tab where tabid = ?", array($tabid));
		$tabname = $adb->query_result_no_html($result,0,"name");
		VTCacheUtils::updateTabidInfo($tabid, $tabname);
	}
	
	return $tabname;
	// crmv@193648e
}

/** Function to get the tablabel for a given id
  * @param $tabid -- tab id:: Type integer
  * @returns $string -- string:: Type string
  *
  */
function getTabLabel($tabid) {
	global $log, $adb, $table_prefix;
	
	$log->debug("Entering getTabLabel(".$tabid.") method ...");
	$log->info("tab id is ".$tabid);
	
	$sql = "select tablabel from ".$table_prefix."_tab where tabid=?";
	$result = $adb->pquery($sql, array($tabid));
	$tablabel=  $adb->query_result_no_html($result,0,"tablabel");
	$log->debug("Exiting getTabLabel method ...");
	return $tablabel;
}

/** Function to get the tab module name for a given id
  * @param $tabid -- tab id:: Type integer
  * @returns $string -- string:: Type string
  *
  */
function getTabModuleName($tabid)
{
	global $log, $table_prefix;
	$log->debug("Entering getTabModuleName(".$tabid.") method ...");

	// Lookup information in cache first
	$tabname = VTCacheUtils::lookupModulename($tabid);
	if($tabname === false) {
		//crmv@140903
		$tab_info_array = TabdataCache::get('tab_info_array');
		if (!empty($tab_info_array)) {
		//crmv@140903e
			$tabname = array_search($tabid,$tab_info_array);

			// Update information to cache for re-use
			VTCacheUtils::updateTabidInfo($tabid, $tabname);

		} else {
			$log->info("tab id is ".$tabid);
	        global $adb;
	        $sql = "select name from ".$table_prefix."_tab where tabid=?";
	        $result = $adb->pquery($sql, array($tabid));
	        $tabname=  $adb->query_result($result,0,"name");

	        // Update information to cache for re-use
	        VTCacheUtils::updateTabidInfo($tabid, $tabname);
		}
	}
	$log->debug("Exiting getTabModuleName method ...");
    return $tabname;
}

/** Function to get column fields for a given module
  * @param $module -- module:: Type string
  * @returns $column_fld -- column field :: Type array
  *
  */
function getColumnFields($module)
{
	global $log, $adb, $table_prefix;
	$log->debug("Entering getColumnFields(".$module.") method ...");
	$log->debug("in getColumnFields ".$module);
	
	if (empty($module)) return array(); // crmv@193648

	// Lookup in cache for information
	$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);

	if($cachedModuleFields === false) {

		if (empty($adb) || !$adb->table_exist($table_prefix.'_field')) return array();	//crmv@25671 crmv@49398

		$tabid = getTabid($module);
		if ($module == 'Calendar') {
    		$tabid = array('9','16');
    	}

    	// Let us pick up all the fields first so that we can cache information
		$sql = "SELECT tabid, fieldname, fieldid, fieldlabel, columnname, tablename, uitype, typeofdata, presence
		FROM ".$table_prefix."_field WHERE ";
		//crmv@47905
		if (is_array($tabid)){
			$sql.=" tabid in (" . generateQuestionMarks($tabid) . ")";
		}
		else{
			$sql.=" tabid = ?";
		}

        $result = $adb->pquery($sql, array($tabid));
        if($result) {
        	while($resultrow = $adb->fetchByAssoc($result,-1,false)) { //crmv@47905
        		// Update information to cache for re-use
        		VTCacheUtils::updateFieldInfo(
        			$resultrow['tabid'], $resultrow['fieldname'], $resultrow['fieldid'],
        			$resultrow['fieldlabel'], $resultrow['columnname'], $resultrow['tablename'],
        			$resultrow['uitype'], $resultrow['typeofdata'], $resultrow['presence']
        		);
        	}
        }
		//crmv@47905 e
        // For consistency get information from cache
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
	}

	if($module == 'Calendar') {
		$cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
		if($cachedModuleFields == false) $cachedModuleFields = $cachedEventsFields;
		else $cachedModuleFields = array_merge($cachedModuleFields, $cachedEventsFields);
	}

	$column_fld = array();
	if($cachedModuleFields) {
		foreach($cachedModuleFields as $fieldinfo) {
			$column_fld[$fieldinfo['fieldname']] = '';
		}
	}

	$log->debug("Exiting getColumnFields method ...");
	return $column_fld;
}

// crmv@187823
/** 
 * Function to get a users's mail id
 * @param $userid -- userid :: Type integer
 * @returns $email -- email :: Type string
 *
 */
function getUserEmail($userid) {
	global $adb, $table_prefix;
	static $userEmailCache = array();
	if ($userid != '') {
		if (!array_key_exists($userid, $userEmailCache)) {
			$result = $adb->pquery("SELECT email1 FROM {$table_prefix}_users WHERE id = ?", array($userid));
			$userEmailCache[$userid] = $adb->query_result($result,0,"email1");
		}
		$email = $userEmailCache[$userid];
	}
	return $email;
}
// crmv@187823e


/** Function to get a userid for outlook
  * @param $username -- username :: Type string
    * @returns $user_id -- user id :: Type integer
       */

//outlook security
function getUserId_Ol($username)
{
	global $log;
	$log->debug("Entering getUserId_Ol(".$username.") method ...");
	$log->info("in getUserId_Ol ".$username);

	global $adb, $table_prefix;
	$sql = "select id from ".$table_prefix."_users where user_name=?";
	$result = $adb->pquery($sql, array($username));
	$num_rows = $adb->num_rows($result);
	if($num_rows > 0)
	{
		$user_id = $adb->query_result($result,0,"id");
    	}
	else
	{
		$user_id = 0;
	}
	$log->debug("Exiting getUserId_Ol method ...");
	return $user_id;
}

// crmv@150065
function getUserLanguage($userid) {
	static $userLangCache = array();
	
	if (!isset($userLangCache[$userid])) {
		$usr = CRMEntity::getInstance('Users');
		$usr->retrieveCurrentUserInfoFromFile($userid);
		$userLangCache[$userid] = $usr->column_fields['default_language'];
	}
		
	return $userLangCache[$userid];
}
// crmv@150065e

/** Function to get a action id for a given action name
  * @param $action -- action name :: Type string
    * @returns $actionid -- action id :: Type integer
       */

//outlook security

function getActionid($action)
{
	global $log;
	$log->debug("Entering getActionid(".$action.") method ...");
	global $adb, $table_prefix;
	$log->info("get Actionid ".$action);
	$actionid = '';
	//crmv@140903
	$action_id_array = TabdataCache::get('action_id_array');
	if (!empty($action_id_array)) {
	//crmv@140903e
		$actionid = $action_id_array[$action];
	}
	else
	{
		$query="select * from ".$table_prefix."_actionmapping where actionname=?";
       	$result =$adb->pquery($query, array($action));
       	$actionid=$adb->query_result($result,0,'actionid');
	}
	$log->info("action id selected is ".$actionid );
	$log->debug("Exiting getActionid method ...");
	return $actionid;
}

/** Function to get a action for a given action id
  * @param $action id -- action id :: Type integer
    * @returns $actionname-- action name :: Type string
       */


function getActionname($actionid)
{
	global $log;
	$log->debug("Entering getActionname(".$actionid.") method ...");
	global $adb, $table_prefix;

	$actionname='';

	//crmv@140903
	$action_name_array = TabdataCache::get('action_name_array');
	if (!empty($action_name_array)) {
	//crmv@140903e
		$actionname= $action_name_array[$actionid];
	}
	else
	{
		$query="select * from ".$table_prefix."_actionmapping where actionid=? and securitycheck=0";
		$result =$adb->pquery($query, array($actionid));
		$actionname=$adb->query_result($result,0,"actionname");
	}
	$log->debug("Exiting getActionname method ...");
	return $actionname;
}

// crmv@100399
/** Function to get a assigned user id for a given entity
 * @param $record -- entity id :: Type integer
 * @returns $user_id -- user id :: Type integer
 */
function getUserId($record) {
	global $adb, $table_prefix;
	$res = $adb->pquery("select smownerid from ".$table_prefix."_crmentity where crmid = ?", array($record));
    $user_id = $adb->query_result_no_html($res,0,'smownerid');
	return $user_id;
}
// crmv@100399e

/** Function to get a user id or group id for a given entity
  * @param $record -- entity id :: Type integer
    * @returns $ownerArr -- owner id :: Type array
       */

function getRecordOwnerId($record)
{
	global $log, $adb, $table_prefix;
	$log->debug("Entering getRecordOwnerId(".$record.") method ...");
	
	$ownerArr=Array();
	$query="select smownerid from ".$table_prefix."_crmentity where crmid = ?";
	$result=$adb->pquery($query, array($record));
	
	// crmv@198872
	if($adb->num_rows($result) == 0) {
		// try to read from processes
		$query="select smownerid from {$table_prefix}_processes where processesid = ?";
		$result=$adb->pquery($query, array($record));
	}
	// crmv@198872e
	
	if($adb->num_rows($result) > 0)
	{
		$ownerId = $adb->query_result_no_html($result,0,'smownerid');
		$sql_result = $adb->pquery("select count(*) as count from ".$table_prefix."_users where id = ?",array($ownerId));
		if ($adb->query_result_no_html($sql_result,0,'count') > 0) {
			$ownerArr['Users'] = $ownerId;
		} else {
			$ownerArr['Groups'] = $ownerId;
		}
	}
	
	$log->debug("Exiting getRecordOwnerId method ...");
	return $ownerArr;
}

/** Function to insert value to profile2field table
  * @param $profileid -- profileid :: Type integer
  */
function insertProfile2field($profileid) {
	global $log, $adb, $table_prefix;
	$log->debug("Entering insertProfile2field(".$profileid.") method ...");
    $log->info("in insertProfile2field ".$profileid);

    // crmv@39110
	$fld_result = $adb->pquery("select * from ".$table_prefix."_field where generatedtype=1 and displaytype in (1,2,3) and tabid != 29 order by tabid ASC, sequence ASC", array());
    $num_rows = $adb->num_rows($fld_result);
    for($i=0; $i<$num_rows; $i++) {
		$tab_id = $adb->query_result($fld_result,$i,'tabid');
		$field_id = $adb->query_result($fld_result,$i,'fieldid');
		$sequence = $adb->query_result($fld_result,$i,'fieldid');
		$params = array($profileid, $tab_id, $field_id, 0, 1, $sequence);
		$adb->pquery("insert into ".$table_prefix."_profile2field (profileid, tabid, fieldid, visible, readonly, sequence) values (?,?,?,?,?,?)", $params);
	}
	// crmv@39110e
	$log->debug("Exiting insertProfile2field method ...");
}

/** Function to insert into default org field
       */

function insert_def_org_field()
{
	global $log;
	$log->debug("Entering insert_def_org_field() method ...");
	global $adb, $table_prefix;
	$fld_result = $adb->pquery("select * from ".$table_prefix."_field where generatedtype=1 and displaytype in (1,2,3) and tabid != 29", array());
        $num_rows = $adb->num_rows($fld_result);
        for($i=0; $i<$num_rows; $i++)
        {
                 $tab_id = $adb->query_result($fld_result,$i,'tabid');
                 $field_id = $adb->query_result($fld_result,$i,'fieldid');
				 $params = array($tab_id, $field_id, 0, 1);
                 $adb->pquery("insert into ".$table_prefix."_def_org_field values (?,?,?,?)", $params);
	}
	$log->debug("Exiting insert_def_org_field() method ...");
}

/** Function to insert value to profile2field table
  * @param $fld_module -- field module :: Type string
  * @param $profileid -- profileid :: Type integer
  * @returns $result -- result :: Type string
  */

function getProfile2FieldList($fld_module, $profileid)
{
	global $log;
	$log->debug("Entering getProfile2FieldList(".$fld_module.",". $profileid.") method ...");
        $log->info("in getProfile2FieldList ".$fld_module. $table_prefix.'_profile id is  '.$profileid);

	global $adb, $table_prefix;
	//crmv@24665
	$moduleinstance = Vtecrm_Module::getInstance($fld_module);
	if ($moduleinstance){
		$tabid = $moduleinstance->id;
	}
	$query = "SELECT ".$table_prefix."_profile2field.visible,".$table_prefix."_field.* FROM ".$table_prefix."_field
	LEFT JOIN ".$table_prefix."_profile2field ON (".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid AND ".$table_prefix."_profile2field.profileid = ?)
	WHERE ".$table_prefix."_field.presence IN(0,2)
	AND ".$table_prefix."_field.tabid = ?";
	//crmv@24665e
	$result = $adb->pquery($query, array($profileid, $tabid));
	$log->debug("Exiting getProfile2FieldList method ...");
	return $result;
}

/** Function to insert value to profile2fieldPermissions table
  * @param $fld_module -- field module :: Type string
  * @param $profileid -- profileid :: Type integer
  * @returns $return_data -- return_data :: Type string
  */

//added by jeri

function getProfile2FieldPermissionList($fld_module, $profileid)
{
	global $log, $table_prefix;
	$log->debug("Entering getProfile2FieldPermissionList(".$fld_module.",". $profileid.") method ...");
    $log->info("in getProfile2FieldList ".$fld_module. $table_prefix.'_profile id is  '.$profileid);

    // Cache information to re-use
    static $_module_fieldpermission_cache = array();

    if(!isset($_module_fieldpermission_cache[$fld_module])) {
    	$_module_fieldpermission_cache[$fld_module] = array();
    }

    // Lookup cache first
    $return_data = VTCacheUtils::lookupProfile2FieldPermissionList($fld_module, $profileid);

    if($return_data === false) {

    	$return_data = array();

		global $adb;
		//crmv@24665	//crmv@49510
		$moduleinstance = Vtecrm_Module::getInstance($fld_module);
		if ($moduleinstance){
			$tabid = $moduleinstance->id;
		}
		else{
			return $return_data;
		}
		$col = 'mandatory';
		$adb->format_columns($col);
		$query = "SELECT ".$table_prefix."_profile2field.visible, ".$table_prefix."_profile2field.readonly as \"prof_readonly\", ".$table_prefix."_profile2field.$col as \"prof_mandatory\", ".$table_prefix."_field.*
		FROM ".$table_prefix."_field
		LEFT JOIN ".$table_prefix."_profile2field ON (".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid AND ".$table_prefix."_profile2field.profileid = ?)
		WHERE ".$table_prefix."_field.presence IN(0,2)
		AND ".$table_prefix."_field.tabid = ?";
    	$qparams = array($profileid, $tabid);
		$result = $adb->pquery($query, $qparams);

    	for($i=0; $i<$adb->num_rows($result); $i++) {
			$return_data[]=array(
				$adb->query_result($result,$i,"fieldlabel"),
				intval($adb->query_result($result,$i,"visible")),	// From _profile2field.visible
				$adb->query_result($result,$i,"uitype"),
				$adb->query_result($result,$i,"visible"),			// From _profile2field.visible
				$adb->query_result($result,$i,"fieldid"),
				$adb->query_result($result,$i,"displaytype"),
				$adb->query_result($result,$i,"typeofdata"),
				$adb->query_result($result,$i,"presence"),
				$adb->query_result($result,$i,"prof_readonly"),		// From _profile2field.readonly
				$adb->query_result($result,$i,"prof_mandatory"),	// From _profile2field.mandatory
			);
		}
		//crmv@24665e	//crmv@49510e
		// Update information to cache for re-use
		VTCacheUtils::updateProfile2FieldPermissionList($fld_module, $profileid, $return_data);
    }


	$log->debug("Exiting getProfile2FieldPermissionList method ...");
	return $return_data;
}

/** Function to getProfile2allfieldsListinsert value to profile2fieldPermissions table
  * @param $mod_array -- mod_array :: Type string
  * @param $profileid -- profileid :: Type integer
  * @returns $profilelist -- profilelist :: Type string
  */

function getProfile2AllFieldList($mod_array,$profileid)
{
	global $log, $table_prefix;
     $log->debug("Entering getProfile2AllFieldList(".$mod_array.",".$profileid.") method ...");
     $log->info("in getProfile2AllFieldList ".$table_prefix."_profile id is " .$profileid);

	global $adb;
	$profilelist=array();
	// crmv@164436
	foreach($mod_array as $k=>$v) {
		$profilelist[$k]=getProfile2FieldPermissionList($k, $profileid);
	}
	// crmv@164436e
	$log->debug("Exiting getProfile2AllFieldList method ...");
	return $profilelist;
}

/** Function to getdefaultfield organisation list for a given module
  * @param $fld_module -- module name :: Type string
  * @returns $result -- string :: Type object
  */

//end of fn added by jeri

function getDefOrgFieldList($fld_module)
{
	global $log;
	$log->debug("Entering getDefOrgFieldList(".$fld_module.") method ...");
        $log->info("in getDefOrgFieldList ".$fld_module);

	global $adb, $table_prefix;
	$tabid = getTabid($fld_module);

	$query = "select ".$table_prefix."_def_org_field.visible,".$table_prefix."_field.* from ".$table_prefix."_def_org_field inner join ".$table_prefix."_field on ".$table_prefix."_field.fieldid=".$table_prefix."_def_org_field.fieldid where ".$table_prefix."_def_org_field.tabid=?";
	$qparams = array($tabid);
	$result = $adb->pquery($query, $qparams);
	$log->debug("Exiting getDefOrgFieldList method ...");
	return $result;
}

/** Function to getQuickCreate for a given tabid
  * @param $tabid -- tab id :: Type string
  * @param $actionid -- action id :: Type integer
  * @returns $QuickCreateForm -- QuickCreateForm :: Type boolean
  */

function getQuickCreate($tabid,$actionid)
{
	global $log;
	$log->debug("Entering getQuickCreate(".$tabid.",".$actionid.") method ...");
	$module=getTabModuleName($tabid);
	$actionname=getActionname($actionid);
        $QuickCreateForm= 'true';

	$perr=isPermitted($module,$actionname);
	if($perr == 'no')
	{
                $QuickCreateForm= 'false';
	}
	$log->debug("Exiting getQuickCreate method ...");
	return $QuickCreateForm;

}

/** Function to getQuickCreate for a given tabid
  * @param $tabid -- tab id :: Type string
  * @param $actionid -- action id :: Type integer
  * @returns $QuickCreateForm -- QuickCreateForm :: Type boolean
  */

function ChangeStatus($status,$activityid,$activity_mode='')
 {
	global $log;
	$log->debug("Entering ChangeStatus(".$status.",".$activityid.",".$activity_mode."='') method ...");
        $log->info("in ChangeStatus ".$status. $table_prefix.'_activityid is  '.$activityid);

        global $adb, $table_prefix;
        if ($activity_mode == 'Task')
        {
                $query = "Update ".$table_prefix."_activity set status=? where activityid = ?";
        }
        elseif ($activity_mode == 'Events')
        {
                $query = "Update ".$table_prefix."_activity set eventstatus=? where activityid = ?";
        }
		if($query) {
        	$adb->pquery($query, array($status, $activityid));
		}
	$log->debug("Exiting ChangeStatus method ...");
 }

/** Function to set date values compatible to database (YY_MM_DD)
  * @param $value -- value :: Type string
  * @returns $insert_date -- insert_date :: Type string
  */

function getDBInsertDateValue($value)
{
	global $log;
	$log->debug("Entering getDBInsertDateValue(".$value.") method ...");
	global $current_user;
	$dat_fmt = $current_user->date_format;
	if($dat_fmt == '') {
		$dat_fmt = 'dd-mm-yyyy';
	}
	$insert_date='';
	if($dat_fmt == 'dd-mm-yyyy')
	{
		list($d,$m,$y) = explode('-',$value);
	}
	elseif($dat_fmt == 'mm-dd-yyyy')
	{
		list($m,$d,$y) = explode('-',$value);
	}
	elseif($dat_fmt == 'yyyy-mm-dd')
	{
		list($y,$m,$d) = explode('-',$value);
	}
	elseif($dat_fmt == 'yyyy.mm.dd')
	{
		list($y,$m,$d) = explode('[.]',$value);
	}
	elseif($dat_fmt == 'yyyy.mm.dd')
	{
		list($y,$m,$d) = explode('[.]',$value);
	}


	elseif($dat_fmt == 'mm/dd/yyyy')
	{
		list($m,$d,$y) = explode('/',$value);
	}
	elseif($dat_fmt == 'yyyy/mm/dd')
	{
		list($y,$m,$d) = explode('/',$value);
	}
	elseif($dat_fmt == 'yyyy/mm/dd')
	{
		list($y,$m,$d) = explode('/',$value);
	}
	//ds@30e

	if(!$y && !$m && !$d) {
		$insert_date = '';
	} else {
		$insert_date=$y.'-'.$m.'-'.$d;
	}
	$log->debug("Exiting getDBInsertDateValue method ...");
	return $insert_date;
}

/** Function to get unitprice for a given product id
  * @param $productid -- product id :: Type integer
  * @returns $up -- up :: Type string
  */

function getUnitPrice($productid, $module='Products')
{
	global $log, $adb, $table_prefix;
	$log->debug("Entering getUnitPrice($productid,$module) method ...");

	if($module == 'Services') {
    	$query = "select unit_price from ".$table_prefix."_service where serviceid=?";
	} else {
    	$query = "select unit_price from ".$table_prefix."_products where productid=?";
	}
    $result = $adb->pquery($query, array($productid));
    $unitpice = $adb->query_result($result,0,'unit_price');
	$log->debug("Exiting getUnitPrice method ...");
	return $unitpice;
}

/** Function to upload product image file
  * @param $id -- id :: Type integer
  * @param $deleted_array -- images to be deleted :: Type array
  * @returns $imagename -- imagelist:: Type array
  */

function getProductImageName($id,$deleted_array='')
{
	global $log;
	$log->debug("Entering getProductImageName(".$id.",".$deleted_array."='') method ...");
	global $adb, $table_prefix;
	$image_array=array();
	$query = "select imagename from ".$table_prefix."_products where productid=?";
	$result = $adb->pquery($query, array($id));
	$image_name = $adb->query_result($result,0,"imagename");
	$image_array=explode("###",$image_name);
	$log->debug("Inside getProductImageName. The image_name is ".$image_name);
	if($deleted_array!='')
	{
		$resultant_image = array();
		$resultant_image=array_merge(array_diff($image_array,$deleted_array));
		$imagelists=implode('###',$resultant_image);
		$log->debug("Exiting getProductImageName method ...");
		return	$imagelists;
	}
	else
	{
		$log->debug("Exiting getProductImageName method ...");
		return $image_name;
	}
}

/** Function to get Contact images
  * @param $id -- id :: Type integer
  * @returns $imagename -- imagename:: Type string
  */

function getContactImageName($id)
{
	global $log;
	$log->debug("Entering getContactImageName(".$id.") method ...");
        global $adb, $table_prefix;
        $query = "select imagename from ".$table_prefix."_contactdetails where contactid=?";
        $result = $adb->pquery($query, array($id));
        $image_name = $adb->query_result($result,0,"imagename");
        $log->debug("Inside getContactImageName. The image_name is ".$image_name);
	$log->debug("Exiting getContactImageName method ...");
        return $image_name;

}

/** Function to get Inventory Total
  * @param $return_module -- return module :: Type string
  * @param $id -- entity id :: Type integer
  * @returns $total -- total:: Type integer
  */

function getInventoryTotal($return_module,$id)
{
	global $log;
	$log->debug("Entering getInventoryTotal(".$return_module.",".$id.") method ...");
	global $adb, $table_prefix;
	if($return_module == "Potentials")
	{
		$query ="select ".$table_prefix."_products.productname,".$table_prefix."_products.unit_price,".$table_prefix."_products.qtyinstock,".$table_prefix."_seproductsrel.* from ".$table_prefix."_products inner join ".$table_prefix."_seproductsrel on ".$table_prefix."_seproductsrel.productid=".$table_prefix."_products.productid where crmid=?";
	}
	elseif($return_module == "Products")
	{
		$query="select ".$table_prefix."_products.productid,".$table_prefix."_products.productname,".$table_prefix."_products.unit_price,".$table_prefix."_products.qtyinstock,".$table_prefix."_crmentity.* from ".$table_prefix."_products inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid where ".$table_prefix."_crmentity.deleted=0 and productid=?";
	}
	$result = $adb->pquery($query, array($id));
	$num_rows=$adb->num_rows($result);
	$total=0;
	for($i=1;$i<=$num_rows;$i++)
	{
		$unitprice=$adb->query_result($result,$i-1,'unit_price');
		$qty=$adb->query_result($result,$i-1,'quantity');
		$listprice=$adb->query_result($result,$i-1,'listprice');
		if($listprice == '')
		$listprice = $unitprice;
		if($qty =='')
		$qty = 1;
		$total = $total+($qty*$listprice);
	}
	$log->debug("Exiting getInventoryTotal method ...");
	return $total;
}

/** Function to update product quantity
  * @param $product_id -- product id :: Type integer
  * @param $upd_qty -- quantity :: Type integer
  */

function updateProductQty($product_id, $upd_qty)
{
	global $log;
	$log->debug("Entering updateProductQty(".$product_id.",". $upd_qty.") method ...");
	global $adb, $table_prefix;
	$query= "update ".$table_prefix."_products set qtyinstock=? where productid=?";
    $adb->pquery($query, array($upd_qty, $product_id));
	$log->debug("Exiting updateProductQty method ...");

}


/** Function to get quick create form fields
  * @param $fieldlabel -- field label :: Type string
  * @param $uitype -- uitype :: Type integer
  * @param $fieldname -- field name :: Type string
  * @param $tabid -- tabid :: Type integer
  * @returns $return_field -- return field:: Type string
  */

//for Quickcreate-Form

function get_quickcreate_form($fieldlabel,$uitype,$fieldname,$tabid)
{
	global $log;
	$log->debug("Entering get_quickcreate_form(".$fieldlabel.",".$uitype.",".$fieldname.",".$tabid.") method ...");
	$return_field ='';
	switch($uitype)
	{
		case 1: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 2: $return_field .=get_textmanField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 6: $return_field .=get_textdateField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 11: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 13: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 15: $return_field .=get_textcomboField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 16: $return_field .=get_textcomboField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 17: $return_field .=get_textwebField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 19: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 22: $return_field .=get_textmanField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 23: $return_field .=get_textdateField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 50: $return_field .=get_textaccField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 51: $return_field .=get_textaccField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 55: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 63: $return_field .=get_textdurationField($fieldlabel,$fieldname,$tabid);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
		case 71: $return_field .=get_textField($fieldlabel,$fieldname);
			$log->debug("Exiting get_quickcreate_form method ...");
			return $return_field;
			break;
	}
}

/** Function to get quick create form fields
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @param $tid -- tabid :: Type integer
  * @returns $form_field -- return field:: Type string
  */

function get_textmanField($label,$name,$tid)
{
	global $log;
	$log->debug("Entering get_textmanField(".$label.",".$name.",".$tid.") method ...");
	$form_field='';
	if($tid == 9)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_T_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
		$log->debug("Exiting get_textmanField method ...");
		return $form_field;
	}
	if($tid == 16)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_E_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
		$log->debug("Exiting get_textmanField method ...");
		return $form_field;
	}
	else
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
		$log->debug("Exiting get_textmanField method ...");
		return $form_field;
	}

}

/** Function to get textfield for website field
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @returns $form_field -- return field:: Type string
  */

function get_textwebField($label,$name)
{
	global $log;
	$log->debug("Entering get_textwebField(".$label.",".$name.") method ...");

	$form_field='';
	$form_field .='<td>';
	$form_field .= $label.':<br>http://<br>';
	$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
	$log->debug("Exiting get_textwebField method ...");
	return $form_field;

}

/** Function to get textfield
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @returns $form_field -- return field:: Type string
  */

function get_textField($label,$name)
{
	global $log;
	$log->debug("Entering get_textField(".$label.",".$name.") method ...");
	$form_field='';
	if($name == "amount")
	{
		$form_field .='<td>';
		$form_field .= $label.':(U.S Dollar:$)<br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
		$log->debug("Exiting get_textField method ...");
		return $form_field;
	}
	else
	{

		$form_field .='<td>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="20" maxlength="" value=""></td>';
		$log->debug("Exiting get_textField method ...");
		return $form_field;
	}

}

/** Function to get account textfield
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @param $tid -- tabid :: Type integer
  * @returns $form_field -- return field:: Type string
  */

function get_textaccField($label,$name,$tid)
{
	global $log;
	$log->debug("Entering get_textaccField(".$label.",".$name.",".$tid.") method ...");

	global $app_strings;

	$form_field='';
	if($tid == 2)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="account_name" type="text" size="20" maxlength="" id="account_name" value="" readonly><br>';
		$form_field .='<input name="account_id" id="QCK_'.$name.'" type="hidden" value="">&nbsp;<input title="'.$app_strings[LBL_CHANGE_BUTTON_TITLE].'" accessKey="'.$app_strings[LBL_CHANGE_BUTTON_KEY].'" type="button" tabindex="3" class="button" value="'.$app_strings[LBL_CHANGE_BUTTON_LABEL].'" name="btn1" LANGUAGE=javascript onclick=\'return window.open("index.php?module=Accounts&action=Popup&popuptype=specific&form=EditView&form_submit=false","test","width=600,height=400,resizable=1,scrollbars=1");\'></td>';
		$log->debug("Exiting get_textaccField method ...");
		return $form_field;
	}
	else
	{
		$form_field .='<td>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="account_name" type="text" size="20" maxlength="" value="" readonly><br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="hidden" value="">&nbsp;<input title="'.$app_strings[LBL_CHANGE_BUTTON_TITLE].'" accessKey="'.$app_strings[LBL_CHANGE_BUTTON_KEY].'" type="button" tabindex="3" class="button" value="'.$app_strings[LBL_CHANGE_BUTTON_LABEL].'" name="btn1" LANGUAGE=javascript onclick=\'return window.open("index.php?module=Accounts&action=Popup&popuptype=specific&form=EditView&form_submit=false","test","width=600,height=400,resizable=1,scrollbars=1");\'></td>';
		$log->debug("Exiting get_textaccField method ...");
		return $form_field;
	}

}

/** Function to get combo field values
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @returns $form_field -- return field:: Type string
  */

function get_textcomboField($label,$name)
{
	global $log;
	$log->debug("Entering get_textcomboField(".$label.",".$name.") method ...");
	$form_field='';
	if($name == "sales_stage")
	{
		$comboFieldNames = Array('leadsource'=>'leadsource_dom'
                      ,'opportunity_type'=>'opportunity_type_dom'
                      ,'sales_stage'=>'sales_stage_dom');
		$comboFieldArray = getComboArray($comboFieldNames);
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<select name="'.$name.'">';
		$form_field .=get_select_options_with_id($comboFieldArray['sales_stage_dom'], "");
		$form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
		return $form_field;

	}
	if($name == "productcategory")
	{
		$comboFieldNames = Array('productcategory'=>'productcategory_dom');
		$comboFieldArray = getComboArray($comboFieldNames);
		$form_field .='<td>';
		$form_field .= $label.':<br>';
		$form_field .='<select name="'.$name.'">';
		$form_field .=get_select_options_with_id($comboFieldArray['productcategory_dom'], "");
		$form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
		return $form_field;

	}
	if($name == "ticketpriorities")
	{
		$comboFieldNames = Array('ticketpriorities'=>'ticketpriorities_dom');
		$comboFieldArray = getComboArray($comboFieldNames);
		$form_field .='<td>';
		$form_field .= $label.':<br>';
		$form_field .='<select name="'.$name.'">';
		$form_field .=get_select_options_with_id($comboFieldArray['ticketpriorities_dom'], "");
		$form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
		return $form_field;
	}
	if($name == "activitytype")
	{
		$comboFieldNames = Array('activitytype'=>'activitytype_dom',
			 'duration_minutes'=>'duration_minutes_dom');
		$comboFieldArray = getComboArray($comboFieldNames);
		$form_field .='<td>';
		$form_field .= $label.'<br>';
		$form_field .='<select name="'.$name.'">';
		$form_field .=get_select_options_with_id($comboFieldArray['activitytype_dom'], "");
		$form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
		return $form_field;


	}
        if($name == "eventstatus")
        {
                $comboFieldNames = Array('eventstatus'=>'eventstatus_dom');
                $comboFieldArray = getComboArray($comboFieldNames);
                $form_field .='<td>';
                $form_field .= $label.'<br>';
                $form_field .='<select name="'.$name.'">';
                $form_field .=get_select_options_with_id($comboFieldArray['eventstatus_dom'], "");
                $form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
                return $form_field;


        }
        if($name == "taskstatus")
        {
                $comboFieldNames = Array('taskstatus'=>'taskstatus_dom');
                $comboFieldArray = getComboArray($comboFieldNames);
                $form_field .='<td>';
                $form_field .= $label.'<br>';
                $form_field .='<select name="'.$name.'">';
                $form_field .=get_select_options_with_id($comboFieldArray['taskstatus_dom'], "");
                $form_field .='</select></td>';
		$log->debug("Exiting get_textcomboField method ...");
                return $form_field;
        }



}

/** Function to get date field
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @param $tid -- tabid :: Type integer
  * @returns $form_field -- return field:: Type string
  */


function get_textdateField($label,$name,$tid)
{
	global $log;
	$log->debug("Entering get_textdateField(".$label.",".$name.",".$tid.") method ...");
	global $theme;
	global $app_strings;
	global $current_user;

	$ntc_date_format = $app_strings['NTC_DATE_FORMAT'];
	$ntc_time_format = $app_strings['NTC_TIME_FORMAT'];

	$form_field='';
	$default_date_start = date('Y-m-d');
	$default_time_start = date('H:i');
	$dis_value=getNewDisplayDate();

	if($tid == 2)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<font size="1"><em old="ntc_date_format">('.$current_user->date_format.')</em></font><br>';
		$form_field .='<input name="'.$name.'"  size="12" maxlength="10" id="QCK_'.$name.'" type="text" value="">&nbsp';
		$form_field .='<img src="'.resourcever('btnL3Calendar.gif').'" id="jscal_trigger"></td>';
		$log->debug("Exiting get_textdateField method ...");
		return $form_field;

	}
	if($tid == 9)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_T_'.$name.'" tabindex="2" type="text" size="10" maxlength="10" value="'.$default_date_start.'">&nbsp';
		$form_field.= '<img src="'.resourcever('btnL3Calendar.gif').'" id="jscal_trigger_date_start">&nbsp';
		$form_field.='<input name="time_start" id="task_time_start" tabindex="1" type="text" size="5" maxlength="5" type="text" value="'.$default_time_start.'"><br><font size="1"><em old="ntc_date_format">('.$current_user->date_format.')</em></font>&nbsp<font size="1"><em>'.$ntc_time_format.'</em></font></td>';
		$log->debug("Exiting get_textdateField method ...");
		return $form_field;
	}
	if($tid == 16)
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_E_'.$name.'" tabindex="2" type="text" size="10" maxlength="10" value="'.$default_date_start.'">&nbsp';
		$form_field.= '<img src="'.resourcever('btnL3Calendar.gif').'" id="jscal_trigger_event_date_start">&nbsp';
		$form_field.='<input name="time_start" id="event_time_start" tabindex="1" type="text" size="5" maxlength="5" type="text" value="'.$default_time_start.'"><br><font size="1"><em old="ntc_date_format">('.$current_user->date_format.')</em></font>&nbsp<font size="1"><em>'.$ntc_time_format.'</em></font></td>';
		$log->debug("Exiting get_textdateField method ...");
		return $form_field;
	}

	else
	{
		$form_field .='<td>';
		$form_field .= '<font color="red">*</font>';
		$form_field .= $label.':<br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="10" maxlength="10" value="'.$default_date_start.'">&nbsp';
		$form_field.= '<img src="'.resourcever('btnL3Calendar.gif').'" id="jscal_trigger">&nbsp';
		$form_field.='<input name="time_start" type="text" size="5" maxlength="5" type="text" value="'.$default_time_start.'"><br><font size="1"><em old="ntc_date_format">('.$current_user->date_format.')</em></font>&nbsp<font size="1"><em>'.$ntc_time_format.'</em></font></td>';
		$log->debug("Exiting get_textdateField method ...");
		return $form_field;
	}

}

/** Function to get duration text field in activity
  * @param $label -- field label :: Type string
  * @param $name -- field name :: Type string
  * @param $tid -- tabid :: Type integer
  * @returns $form_field -- return field:: Type string
  */

function get_textdurationField($label,$name,$tid)
{
	global $log;
	$log->debug("Entering get_textdurationField(".$label.",".$name.",".$tid.") method ...");
	$form_field='';
	if($tid == 16)
	{

		$comboFieldNames = Array('activitytype'=>'activitytype_dom',
			 'duration_minutes'=>'duration_minutes_dom');
		$comboFieldArray = getComboArray($comboFieldNames);

		$form_field .='<td>';
		$form_field .= $label.'<br>';
		$form_field .='<input name="'.$name.'" id="QCK_'.$name.'" type="text" size="2" value="1">&nbsp;';
		$form_field .='<select name="duration_minutes">';
		$form_field .=get_select_options_with_id($comboFieldArray['duration_minutes_dom'], "");
		$form_field .='</select><br>(hours/minutes)<br></td>';
		$log->debug("Exiting get_textdurationField method ...");
		return $form_field;
	}
}

/** Function to get email text field
  * @param $module -- module name :: Type name
  * @param $id -- entity id :: Type integer
  * @returns $hidden -- hidden:: Type string
  */

//Added to get the parents list as hidden for Emails -- 09-11-2005
function getEmailParentsList($module,$id,$focus = false)
{
	global $log;
	$log->debug("Entering getEmailParentsList(".$module.",".$id.") method ...");
	global $adb, $table_prefix;
    // If the information is not sent then read it
	if($focus === false) {
		if($module == 'Contacts') {
			$focus = CRMEntity::getInstance('Contacts');
		}
		if($module == 'Leads') {
			$focus = CRMEntity::getInstance('Leads');
		}
		$focus->retrieve_entity_info($id,$module);
	}

    $fieldid = 0;
    $fieldname = 'email';
    if($focus->column_fields['email'] == '' && $focus->column_fields['yahooid'] != '') {
    	$fieldname = 'yahooid';
    }

    $res = $adb->pquery("select * from ".$table_prefix."_field where tabid = ? and fieldname= ? and ".$table_prefix."_field.presence in (0,2)", array(getTabid($module), $fieldname));
    $fieldid = $adb->query_result($res,0,'fieldid');

    $hidden .= '<input type="hidden" name="emailids" value="'.$id.'@'.$fieldid.'|">';
    $hidden .= '<input type="hidden" name="pmodule" value="'.$module.'">';

	$log->debug("Exiting getEmailParentsList method ...");
	return $hidden;
}

/** This Function returns the current status of the specified Purchase Order.
  * The following is the input parameter for the function
  *  $po_id --> Purchase Order Id, Type:Integer
  */
function getPoStatus($po_id)
{
	global $log;
	$log->debug("Entering getPoStatus(".$po_id.") method ...");

	global $log;
        $log->info("in getPoName ".$po_id);

        global $adb, $table_prefix;
        $sql = "select postatus from ".$table_prefix."_purchaseorder where purchaseorderid=?";
        $result = $adb->pquery($sql, array($po_id));
        $po_status = $adb->query_result($result,0,"postatus");
	$log->debug("Exiting getPoStatus method ...");
        return $po_status;
}

/** This Function adds the specified product quantity to the Product Quantity in Stock in the Warehouse
  * The following is the input parameter for the function:
  *  $productId --> ProductId, Type:Integer
  *  $qty --> Quantity to be added, Type:Integer
  */
function addToProductStock($productId,$qty)
{
	global $log;
	$log->debug("Entering addToProductStock(".$productId.",".$qty.") method ...");
	global $adb, $table_prefix;
	$qtyInStck=getProductQtyInStock($productId);
	$updQty=$qtyInStck + $qty;
	$sql = "UPDATE ".$table_prefix."_products set qtyinstock=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting addToProductStock method ...");

}

/**	This Function adds the specified product quantity to the Product Quantity in Demand in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be added
  */
function addToProductDemand($productId,$qty)
{
	global $log;
	$log->debug("Entering addToProductDemand(".$productId.",".$qty.") method ...");
	global $adb, $table_prefix;
	$qtyInStck=getProductQtyInDemand($productId);
	$updQty=$qtyInStck + $qty;
	$sql = "UPDATE ".$table_prefix."_products set qtyindemand=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting addToProductDemand method ...");

}

/**	This Function subtract the specified product quantity to the Product Quantity in Stock in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be subtracted
  */
function deductFromProductStock($productId,$qty)
{
	global $log;
	$log->debug("Entering deductFromProductStock(".$productId.",".$qty.") method ...");
	global $adb, $table_prefix;
	$qtyInStck=getProductQtyInStock($productId);
	$updQty=$qtyInStck - $qty;
	$sql = "UPDATE ".$table_prefix."_products set qtyinstock=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting deductFromProductStock method ...");

}

/**	This Function subtract the specified product quantity to the Product Quantity in Demand in the Warehouse
  *	@param int $productId - ProductId
  *	@param int $qty - Quantity to be subtract
  */
function deductFromProductDemand($productId,$qty)
{
	global $log;
	$log->debug("Entering deductFromProductDemand(".$productId.",".$qty.") method ...");
	global $adb, $table_prefix;
	$qtyInStck=getProductQtyInDemand($productId);
	$updQty=$qtyInStck - $qty;
	$sql = "UPDATE ".$table_prefix."_products set qtyindemand=? where productid=?";
	$adb->pquery($sql, array($updQty, $productId));
	$log->debug("Exiting deductFromProductDemand method ...");

}


/** This Function returns the current product quantity in stock.
  * The following is the input parameter for the function:
  *  $product_id --> ProductId, Type:Integer
  */
function getProductQtyInStock($product_id)
{
	global $log;
	$log->debug("Entering getProductQtyInStock(".$product_id.") method ...");
        global $adb, $table_prefix;
        $query1 = "select qtyinstock from ".$table_prefix."_products where productid=?";
        $result=$adb->pquery($query1, array($product_id));
        $qtyinstck= $adb->query_result($result,0,"qtyinstock");
	$log->debug("Exiting getProductQtyInStock method ...");
        return $qtyinstck;


}

/**	This Function returns the current product quantity in demand.
  *	@param int $product_id - ProductId
  *	@return int $qtyInDemand - Quantity in Demand of a product
  */
function getProductQtyInDemand($product_id)
{
	global $log;
	$log->debug("Entering getProductQtyInDemand(".$product_id.") method ...");
        global $adb, $table_prefix;
        $query1 = "select qtyindemand from ".$table_prefix."_products where productid=?";
        $result = $adb->pquery($query1, array($product_id));
        $qtyInDemand = $adb->query_result($result,0,"qtyindemand");
	$log->debug("Exiting getProductQtyInDemand method ...");
        return $qtyInDemand;
}

// crmv@187823
/**
 * Function to get the vte_table name from 'field' vte_table for the input vte_field based on the module
 * @param  : string $module - current module value
 * @param  : string $fieldname - vte_fieldname to which we want the vte_tablename
 * @return : string $tablename - vte_tablename in which $fieldname is a column, which is retrieved from 'field' vte_table per $module basis
 *
 * @warning: This function expects the $fieldname parameter to be a COLUMN name, and the match is NOT exact, but with substring match
 * 		Use the next function to have the expected behaviour
 */
function getTableNameForField($module,$fieldname)
{
	global $log;
	$log->debug("Entering getTableNameForField(".$module.",".$fieldname.") method ...");
	global $adb, $table_prefix;
	$tabid = getTabid($module);
	//Asha
	if($module == 'Calendar') {
		$tabid = array('9','16');
	}
	$sql = "select tablename from ".$table_prefix."_field where tabid in (". generateQuestionMarks($tabid) .") and ".$table_prefix."_field.presence in (0,2) and columnname like ?";
	$res = $adb->pquery($sql, array($tabid, '%'.$fieldname.'%'));

	$tablename = '';
	if($adb->num_rows($res) > 0)
	{
		$tablename = $adb->query_result($res,0,'tablename');
	}

	$log->debug("Exiting getTableNameForField method ...");
	return $tablename;
}

/**
 * This is what people expect the getTableNameForField function to do, but since it doesn't, here's the proper one!
 */
function getFieldTable($module,$fieldname) {
	global $adb, $table_prefix;
	
	$tabid = array(getTabid($module));
	if ($module == 'Calendar') {
		$tabid = array('9','16');
	}
	$sql = "select tablename from ".$table_prefix."_field where tabid in (". generateQuestionMarks($tabid) .") and presence in (0,2) and fieldname = ?";
	$res = $adb->pquery($sql, array($tabid, $fieldname));

	$tablename = '';
	if ($adb->num_rows($res) > 0) {
		$tablename = $adb->query_result_no_html($res,0,'tablename');
	}

	return $tablename;
}

/**
 * Similar to getFieldTable, but return also the columnname
 */
function getFieldTableAndColumn($module,$fieldname) {
	global $adb, $table_prefix;
	
	$tabid = array(getTabid($module));
	if ($module == 'Calendar') {
		$tabid = array('9','16');
	}
	$sql = "select tablename, columnname from ".$table_prefix."_field where tabid in (". generateQuestionMarks($tabid) .") and presence in (0,2) and fieldname = ?";
	$res = $adb->pquery($sql, array($tabid, $fieldname));

	$tablename = '';
	if ($adb->num_rows($res) > 0) {
		return $adb->fetchByAssoc($res, -1, false);
	}

	return null;
}
// crmv@187823e

/** Function to get Days and Dates in between the dates specified
 */
function get_days_n_dates($st,$en)
{
	global $log;
	$log->debug("Entering get_days_n_dates(".$st.",".$en.") method ...");
        $stdate_arr=explode("-",$st);
        $endate_arr=explode("-",$en);

        $dateDiff = mktime(0,0,0,$endate_arr[1],$endate_arr[2],$endate_arr[0]) - mktime(0,0,0,$stdate_arr[1],$stdate_arr[2],$stdate_arr[0]);//to get  dates difference

        $days   =  floor($dateDiff/60/60/24)+1; //to calculate no of. days
        for($i=0;$i<$days;$i++)
        {
                $day_date[] = date("Y-m-d",mktime(0,0,0,date("$stdate_arr[1]"),(date("$stdate_arr[2]")+($i)),date("$stdate_arr[0]")));
        }
        if(!isset($day_date))
                $day_date=0;
        $nodays_dates=array($days,$day_date);
	$log->debug("Exiting get_days_n_dates method ...");
        return $nodays_dates; //passing no of days , days in between the days
}//function end


/** Function to get the start and End Dates based upon the period which we give
 */
function start_end_dates($period)
{
	global $log;
	$log->debug("Entering start_end_dates(".$period.") method ...");
        $st_thisweek= date("Y-m-d",mktime(0,0,0,date("n"),(date("j")-date("w")),date("Y")));
        if($period=="tweek")
        {
                $st_date= date("Y-m-d",mktime(0,0,0,date("n"),(date("j")-date("w")),date("Y")));
                $end_date = date("Y-m-d",mktime(0,0,0,date("n"),(date("j")-1),date("Y")));
                $st_week= date("w",mktime(0,0,0,date("n"),date("j"),date("Y")));
                if($st_week==0)
                {
                        $start_week=explode("-",$st_thisweek);
                        $st_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-7),date("$start_week[0]")));
                        $end_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-1),date("$start_week[0]")));
                }
                $period_type="week";
                $width="360";
        }
        else if($period=="lweek")
        {
                $start_week=explode("-",$st_thisweek);
                $st_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-7),date("$start_week[0]")));
                $end_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-1),date("$start_week[0]")));
                $st_week= date("w",mktime(0,0,0,date("n"),date("j"),date("Y")));
                if($st_week==0)
                {
                        $start_week=explode("-",$st_thisweek);
                        $st_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-14),date("$start_week[0]")));
                        $end_date = date("Y-m-d",mktime(0,0,0,date("$start_week[1]"),(date("$start_week[2]")-8),date("$start_week[0]")));
                }
                $period_type="week";
                $width="360";
        }
        else if($period=="tmon")
        {
		$period_type="month";
		$width="840";
		$st_date = date("Y-m-d",mktime(0, 0, 0, date("m"), "01",   date("Y")));
		$end_date = date("Y-m-t");

        }
        else if($period=="lmon")
        {
                $st_date=date("Y-m-d",mktime(0,0,0,date("n")-1,date("1"),date("Y")));
                $end_date = date("Y-m-d",mktime(0, 0, 1, date("n"), 0,date("Y")));
                $period_type="month";
                $start_month=date("d",mktime(0,0,0,date("n"),date("j"),date("Y")));
                if($start_month==1)
                {
                        $st_date=date("Y-m-d",mktime(0,0,0,date("n")-2,date("1"),date("Y")));
                        $end_date = date("Y-m-d",mktime(0, 0, 1, date("n")-1, 0,date("Y")));
                }

                $width="840";
        }
        else
        {
                $curr_date=date("Y-m-d",mktime(0,0,0,date("m"),date("d"),date("Y")));
                $today_date=explode("-",$curr_date);
                $lastday_date=date("Y-m-d",mktime(0,0,0,date("$today_date[1]"),date("$today_date[2]")-1,date("$today_date[0]")));
                $st_date=$lastday_date;
                $end_date=$lastday_date;
                $period_type="yday";
		 $width="250";
        }
        if($period_type=="yday")
                $height="160";
        else
                $height="250";
        $datevalues=array($st_date,$end_date,$period_type,$width,$height);
	$log->debug("Exiting start_end_dates method ...");
        return $datevalues;
}//function ends


/**   Function to get the Graph and vte_table format for a particular date
        based upon the period
 */
function Graph_n_table_format($period_type,$date_value)
{
	global $log;
	$log->debug("Entering Graph_n_table_format(".$period_type.",".$date_value.") method ...");
        $date_val=explode("-",$date_value);
        if($period_type=="month")   //to get the vte_table format dates
        {
                $table_format=date("j",mktime(0,0,0,date($date_val[1]),(date($date_val[2])),date($date_val[0])));
                $graph_format=date("D",mktime(0,0,0,date($date_val[1]),(date($date_val[2])),date($date_val[0])));
        }
        else if($period_type=="week")
        {
                $table_format=date("d/m",mktime(0,0,0,date($date_val[1]),(date($date_val[2])),date($date_val[0])));
                $graph_format=date("D",mktime(0,0,0,date($date_val[1]),(date($date_val[2])),date($date_val[0])));
        }
        else if($period_type=="yday")
        {
                $table_format=date("j",mktime(0,0,0,date($date_val[1]),(date($date_val[2])),date($date_val[0])));
                $graph_format=$table_format;
        }
        $values=array($graph_format,$table_format);
	$log->debug("Exiting Graph_n_table_format method ...");
        return $values;
}

/** Function to get image count for a given product
  * @param $id -- product id :: Type integer
  * @returns count -- count:: Type integer
  */

function getImageCount($id)
{
	global $log;
	$log->debug("Entering getImageCount(".$id.") method ...");
	global $adb, $table_prefix;
	$image_lists=array();
	$query="select imagename from ".$table_prefix."_products where productid=?";
	$result=$adb->pquery($query, array($id));
	$imagename=$adb->query_result($result,0,'imagename');
	$image_lists=explode("###",$imagename);
	$log->debug("Exiting getImageCount method ...");
	return count($image_lists);

}

/** Function to get user image for a given user
  * @param $id -- user id :: Type integer
  * @returns $image_name -- image name:: Type string
  */

function getUserImageName($id)
{
	global $log;
	$log->debug("Entering getUserImageName(".$id.") method ...");
	global $adb, $table_prefix;
	$query = "select imagename from ".$table_prefix."_users where id=?";
	$result = $adb->pquery($query, array($id));
	$image_name = $adb->query_result($result,0,"imagename");
	$log->debug("Inside getUserImageName. The image_name is ".$image_name);
	$log->debug("Exiting getUserImageName method ...");
	return $image_name;

}

/** Function to get all user images for displaying it in listview
  * @returns $image_name -- image name:: Type array
  */

function getUserImageNames()
{
	global $log;
	$log->debug("Entering getUserImageNames() method ...");
	global $adb, $table_prefix;
	$query = "select imagename from ".$table_prefix."_users where deleted=0";
	$result = $adb->pquery($query, array());
	$image_name=array();
	for($i=0;$i<$adb->num_rows($result);$i++)
	{
		if($adb->query_result($result,$i,"imagename")!='')
			$image_name[] = $adb->query_result($result,$i,"imagename");
	}
	$log->debug("Inside getUserImageNames.");
	if(count($image_name) > 0)
	{
		$log->debug("Exiting getUserImageNames method ...");
		return $image_name;
	}
}

/** Function to check whether user has opted for internal mailer
  * @returns $int_mailer -- int mailer:: Type boolean
    */
function useInternalMailer() {
	// TODO: use db
	return 1;
}

/**
* the function is like unescape in javascript
* added by dingjianting on 2006-10-1 for picklist editor
*/
function utf8RawUrlDecode ($source) {
    global $default_charset;
    $decodedStr = "";
    $pos = 0;
    $len = strlen ($source);
    while ($pos < $len) {
        $charAt = substr ($source, $pos, 1);
        if ($charAt == '%') {
            $pos++;
            $charAt = substr ($source, $pos, 1);
            if ($charAt == 'u') {
                // we got a unicode character
                $pos++;
                $unicodeHexVal = substr ($source, $pos, 4);
                $unicode = hexdec ($unicodeHexVal);
                $entity = "&#". $unicode . ';';
                $decodedStr .= utf8_encode ($entity);
                $pos += 4;
            }
            else {
                // we have an escaped ascii character
                $hexVal = substr ($source, $pos, 2);
                $decodedStr .= chr (hexdec ($hexVal));
                $pos += 2;
            }
        } else {
            $decodedStr .= $charAt;
            $pos++;
        }
    }
    if(strtolower($default_charset) == 'utf-8')
	    return html_to_utf8($decodedStr);
    else
	    return $decodedStr;
    //return html_to_utf8($decodedStr);
}

/**
*simple HTML to UTF-8 conversion:
*/
function html_to_utf8 ($data) {
	return preg_replace_callback("/\\&\\#([0-9]{3,10})\\;/", function($matches) {
		return _html_to_utf8($matches[1]);
	}, $data); //crmv@56443
}

function _html_to_utf8 ($data)
{
	if ($data > 127)
	{
		$i = 5;
		while (($i--) > 0)
		{
			if ($data != ($a = $data % ($p = pow(64, $i))))
			{
				$ret = chr(base_convert(str_pad(str_repeat(1, $i + 1), 8, "0"), 2, 10) + (($data - $a) / $p));
				for ($i; $i > 0; $i--)
					$ret .= chr(128 + ((($data % pow(64, $i)) - ($data % ($p = pow(64, $i - 1)))) / $p));
				break;
			}
		}
	}
	else
		$ret = "&#$data;";
	return $ret;
}

/**
* Function to find the UI type of a field based on the uitype id
*/
function is_uitype($uitype, $reqtype) {
	$ui_type_arr = array(
		'_date_' => array(5, 6, 23, 70),
		'_picklist_' => array(15, 16, 52, 53, 54, 55, 59, 62, 63, 66, 68, 76, 77, 78, 80, 98, 101, 115, 357),
		'_users_list_' => array(52),
	);

	if ($ui_type_arr[$reqtype] != null) {
		if (in_array($uitype, $ui_type_arr[$reqtype])) {
			return true;
		}
	}
	return false;
}
/**
 * Function to escape quotes
 * @param $value - String in which single quotes have to be replaced.
 * @return Input string with single quotes escaped.
 */
function escape_single_quotes($value) {
	if (isset($value)) $value = str_replace("'", "\'", $value);
	return $value;
}

/**
 * Function to format the input value for SQL like clause.
 * @param $str - Input string value to be formatted.
 * @param $flag - By default set to 0 (Will look for cases %string%).
 *                If set to 1 - Will look for cases %string.
 *                If set to 2 - Will look for cases string%.
 * @return String formatted as per the SQL like clause requirement
 */
function formatForSqlLike($str, $flag=0,$is_field=false) {
	global $adb;
	if (isset($str)) {
		if($is_field==false){
			$str = str_replace('%', '\%', $str);
			$str = str_replace('_', '\_', $str);
			if ($flag == 0) {
				$str = '%'. $str .'%';
			} elseif ($flag == 1) {
				$str = '%'. $str;
			} elseif ($flag == 2) {
				$str = $str .'%';
			}
		} else {
			if ($flag == 0) {
				$str = 'concat("%",'. $str .',"%")';
			} elseif ($flag == 1) {
				$str = 'concat("%",'. $str .')';
			} elseif ($flag == 2) {
				$str = 'concat('. $str .',"%")';
			}
		}
	}
	return $adb->sql_escape_string($str);
}

/**
 * Get Current Module (global variable or from request)
 */
function getCurrentModule($perform_set=false) {
	global $currentModule,$root_directory;
	if(isset($currentModule)) return $currentModule;

	// Do some security check and return the module information
	if(isset($_REQUEST['module']))
	{
		$is_module = false;
		$module = $_REQUEST['module'];
		$dir = @scandir($root_directory."modules");
		$temp_arr = Array("CVS","Attic");
		$res_arr = @array_intersect($dir,$temp_arr);
		if(count($res_arr) == 0  && !preg_match("/[\/.]/",$module)) {
			if(@in_array($module,$dir))
				$is_module = true;
		}

		if($is_module) {
			if($perform_set) $currentModule = $module;
			return $module;
		}
	}
	return null;
}


/**
 * Set the language strings.
 */
function setCurrentLanguage($active_module=null) {
	global $current_language, $default_language, $app_strings, $app_list_strings, $mod_strings, $currentModule;

	if($active_module==null) {
		if (!isset($currentModule))
			$active_module = getCurrentModule();
		else
			$active_module = $currentModule;
	}

	if(VteSession::hasKey('authenticated_user_language') && VteSession::get('authenticated_user_language') != '')
	{
		$current_language = VteSession::get('authenticated_user_language');
	}
	else
	{
		$current_language = $default_language;
	}

	//set module and application string arrays based upon selected language
	if (!isset($app_strings))
		$app_strings = return_application_language($current_language);
	if (!isset($app_list_strings))
		$app_list_strings = return_app_list_strings_language($current_language);
	if (!isset($mod_strings) && isset($active_module))
		$mod_strings = return_module_language($current_language, $active_module);
}

/**	Function used to get all the picklists and their values for a module
	@param string $module - Module name to which the list of picklists and their values needed
	@return array $fieldlists - Array of picklists and their values
**/
function getAccessPickListValues($module)
{
	global $adb, $log;
	global $current_user, $table_prefix;
	$log->debug("Entering into function getAccessPickListValues($module)");

	$id = getTabid($module);
	$query = 'select fieldname,columnname,fieldid,fieldlabel,tabid,uitype from '.$table_prefix.'_field where tabid = ? and uitype in (15,16,111,33,55) and '.$table_prefix.'_field.presence in (0,2)';
	$result = $adb->pquery($query, array($id));

	$roleid = $current_user->roleid;
	$subrole = getRoleSubordinates($roleid);

	if(count($subrole)> 0)
	{
		$roleids = $subrole;
		array_push($roleids, $roleid);
	}
	else
	{
		$roleids = $roleid;
	}

	$temp_status = Array();
	for($i=0;$i < $adb->num_rows($result);$i++)
	{
		$fieldname = $adb->query_result($result,$i,"fieldname");
		$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
		$columnname = $adb->query_result($result,$i,"columnname");
		$tabid = $adb->query_result($result,$i,"tabid");
		$uitype = $adb->query_result($result,$i,"uitype");

		if(!in_array($fieldname,array('activitytype','visibility','duration_minutes','recurringtype','hdnTaxType')))
		{
			$keyvalue = $columnname;
			$fieldvalues = Array();

			//se la picklist supporta il nuovo metodo
			$columns = $adb->database->MetaColumnNames($table_prefix."_$fieldname");

			if (!$columns)
				continue;
			if (in_array('picklist_valueid',$columns) && $fieldname != 'product_lines'){
				$order_by = "sortid,$fieldname";
				$pick_query="select $fieldname from ".$table_prefix."_$fieldname inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid = ? ";
				$params = array($roleid);
			}
			//altrimenti uso il vecchio
			else {
				if (in_array('sortorderid',$columns))
					$order_by = "sortorderid,$fieldname";
				else
					$order_by = $fieldname;
				$pick_query="select $fieldname from ".$table_prefix."_$fieldname";
				$params = array();
			}
			$pick_query.=" order by $order_by asc";
			$pickListResult = $adb->pquery($pick_query, $params);
			$field_count = $adb->num_rows($pickListResult);
			if($field_count) {
				while($resultrow = $adb->fetch_array_no_html($pickListResult)) {
					$fieldvalues[] = $resultrow[$fieldname];
				}
			}
			if($uitype == 111 && $field_count > 0 && ($fieldname == 'taskstatus' || $fieldname == 'eventstatus'))
			{
				$temp_count =count($temp_status[$keyvalue]);
				if($temp_count > 0)
				{
					for($t=0;$t < $field_count;$t++)
					{
						$temp_status[$keyvalue][($temp_count+$t)] = $fieldvalues[$t];
					}
					$fieldvalues = $temp_status[$keyvalue];
				}
				else
					$temp_status[$keyvalue] = $fieldvalues;
			}
			if($uitype == 33)
				$fieldlists[1][$keyvalue] = $fieldvalues;
			else if($uitype == 55 && $fieldname == 'salutationtype')
				$fieldlists[$keyvalue] = $fieldvalues;
			else if($uitype == 16 || $uitype == 15 || $uitype == 111)
				$fieldlists[$keyvalue] = $fieldvalues;
		}
	}
	$log->debug("Exit from function getAccessPickListValues($module)");

	return $fieldlists;
}

function get_config_status() {
	global $default_charset;
	if(strtolower($default_charset) == 'utf-8')
		$config_status=1;
	else
		$config_status=0;
	return $config_status;
}

//vtc
function sanitizeUploadFileName($fileName, $badFileExtensions) {

	$fileName = preg_replace('/\s+/', '_', $fileName);//replace space with _ in filename
	$fileName = str_replace(':', '', $fileName); // crmv@95157 - remove colons (Windows doesn't like them!)
	$fileName = str_replace('/', '', $fileName); // crmv@132704

	$fileNameParts = explode(".", $fileName);
	$countOfFileNameParts = count($fileNameParts);
	$badExtensionFound = false;

	for ($i=0;$i<$countOfFileNameParts;++$i) {
		$partOfFileName = $fileNameParts[$i];
		if(in_array(strtolower($partOfFileName), $badFileExtensions)) {
			$badExtensionFound = true;
			$fileNameParts[$i] = $partOfFileName . 'file';
		}
	}

	$newFileName = implode(".", $fileNameParts);

	if ($badExtensionFound) {
		$newFileName .= ".txt";
	}
	return $newFileName;
}
//vtc e
/** Function to convert a given time string to Minutes */
function ConvertToMinutes($time_string)
{
	$interval = explode(' ', $time_string);
	$interval_minutes = intval($interval[0]);
	$interval_string = strtolower($interval[1]);
	if($interval_string == 'hour' || $interval_string == 'hours')
	{
		$interval_minutes = $interval_minutes * 60;
	}
	elseif($interval_string == 'day' || $interval_string == 'days')
	{
		$interval_minutes = $interval_minutes * 1440;
	}
	return $interval_minutes;
}
/**
 * Function to check if a given record exists (not deleted)
 * @param integer $recordId - record id
 */
function isRecordExists($recordId) {
	global $adb, $table_prefix;
	$query = "SELECT crmid FROM ".$table_prefix."_crmentity where crmid=? AND deleted=0";
	$result = $adb->pquery($query, array($recordId));
	if ($adb->num_rows($result)) {
		return true;
	}
	return false;
}
/** Function to set date values compatible to database (YY_MM_DD)
  * @param $value -- value :: Type string
  * @returns $insert_date -- insert_date :: Type string
  */
function getValidDBInsertDateValue($value) {
	global $log;
	$log->debug("Entering getDBInsertDateValue(".$value.") method ...");
	global $current_user;
	if (strpos($value,"-") !==false)
		$separator = "-";
	elseif (strpos($value,".") !==false)
		$separator = ".";
	elseif (strpos($value,"/") !==false)
		$separator = "/";
	if ($separator){
		list($y,$m,$d) = explode($separator,$value);
		if ($separator != "-"){
			$value = str_replace($separator,"-",$value);
		}
		if(strlen($y)<4){
			$insert_date = getDBInsertDateValue($value);
		} else {
			$insert_date = $value;
		}
	}
	else
		$insert_date = $value;
	$log->debug("Exiting getDBInsertDateValue method ...");
	return $insert_date;
}
/* Function to get the related tables data
 * @param - $module - Primary module name
 * @param - $secmodule - Secondary module name
 * return Array $rel_array tables and fields to be compared are sent
 * */
function getRelationTables($module,$secmodule){
	global $adb, $table_prefix;
	$primary_obj = CRMEntity::getInstance($module);
	$secondary_obj = CRMEntity::getInstance($secmodule);

	$ui10_query = $adb->pquery("SELECT ".$table_prefix."_field.tabid AS tabid,".$table_prefix."_field.tablename AS tablename, ".$table_prefix."_field.columnname AS columnname FROM ".$table_prefix."_field INNER JOIN ".$table_prefix."_fieldmodulerel ON ".$table_prefix."_fieldmodulerel.fieldid = ".$table_prefix."_field.fieldid WHERE (".$table_prefix."_fieldmodulerel.module=? AND ".$table_prefix."_fieldmodulerel.relmodule=?) OR (".$table_prefix."_fieldmodulerel.module=? AND ".$table_prefix."_fieldmodulerel.relmodule=?)",array($module,$secmodule,$secmodule,$module));
	if($adb->num_rows($ui10_query)>0){
		$ui10_tablename = $adb->query_result($ui10_query,0,'tablename');
		$ui10_columnname = $adb->query_result($ui10_query,0,'columnname');
		$ui10_tabid = $adb->query_result($ui10_query,0,'tabid');

		if($primary_obj->table_name == $ui10_tablename){
			$reltables = array($ui10_tablename=>array("".$primary_obj->table_index."","$ui10_columnname"));
		} else if($secondary_obj->table_name == $ui10_tablename){
			$reltables = array($ui10_tablename=>array("$ui10_columnname","".$secondary_obj->table_index.""),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
		} else {
			if(isset($secondary_obj->tab_name_index[$ui10_tablename])){
				$rel_field = $secondary_obj->tab_name_index[$ui10_tablename];
				$reltables = array($ui10_tablename=>array("$ui10_columnname","$rel_field"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
			} else {
				$rel_field = $primary_obj->tab_name_index[$ui10_tablename];
				$reltables = array($ui10_tablename=>array("$rel_field","$ui10_columnname"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
			}
		}
	}else {
		// crmv@96033
		if(method_exists($primary_obj,'setRelationTables')){ // crmv@172864
			$reltables = $primary_obj->setRelationTables($secmodule);
		} elseif(method_exists($secondary_obj,'setRelationTables')){ // crmv@172864
			$reltablesInv = $secondary_obj->setRelationTables($module);
			if (is_array($reltablesInv) && count($reltablesInv) == 2) {
				// swap the columns
				$first = reset($reltablesInv);
				$key = key($reltablesInv);
				$t = $first[0];	$first[0] = $first[1]; $first[1] = $t;
				if (count($first) == 4) {
					$t = $first[2]; $first[2] = $first[3]; $first[3] = $t;
				}
				$reltables = array($key => $first, "".$primary_obj->table_name."" => "".$primary_obj->table_index."");
			}
		} else {
			$reltables = '';
		}
		// crmv@96033e
	}
	if(is_array($reltables) && !empty($reltables)){
		$rel_array = $reltables;
	} else {
			//crmv@18829 crmv@38798
		$specialMods = array('Documents', 'Calendar');
		if (in_array($module, $specialMods)) {
			$rel_array = array(
				$primary_obj->relation_table => array($primary_obj->relation_table_id, $primary_obj->relation_table_otherid, $primary_obj->relation_table_module, $primary_obj->relation_table_othermodule),
				$primary_obj->table_name => $primary_obj->table_index
			);
		} elseif (in_array($secmodule, $specialMods)) {
			$rel_array = array(
				$secondary_obj->relation_table => array($secondary_obj->relation_table_otherid, $secondary_obj->relation_table_id, $secondary_obj->relation_table_module, $secondary_obj->relation_table_othermodule),
				$primary_obj->table_name => $primary_obj->table_index
			);
		} elseif (isInventoryModule($module) && isProductModule($secmodule)) { // crmv@64542
			$rel_array = array($table_prefix."_inventoryproductrel"=>array('id', "productid"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
		} else {
		//crmv@18829e crmv@38798e
			$rel_array = array($table_prefix."_crmentityrel"=>array("crmid","relcrmid", "module", "relmodule"),"".$primary_obj->table_name."" => "".$primary_obj->table_index."");
		}
			}
	return $rel_array;
}

/**
 * This function returns no value but handles the delete functionality of each entity.
 * Input Parameter are $module - module name, $return_module - return module name, $focus - module object, $record - entity id, $return_id - return entity id.
 */
function DeleteEntity($module,$return_module,$focus,$record,$return_id) {
	global $log,$adb,$table_prefix;
	$log->debug("Entering DeleteEntity method ($module, $return_module, $record, $return_id)");

	if ($return_module == 'Documents' && !empty($return_id) && $module != 'Messages') { // crmv@41776
		$query = 'DELETE FROM '.$table_prefix.'_senotesrel WHERE crmid=? AND notesid=?';
		$params = array($record,$return_id);
		$adb->pquery($query,$params);
	} elseif ($module != $return_module && !empty($return_module) && !empty($return_id)) {
		$focus->unlinkRelationship($record, $return_module, $return_id);
	} else {
		$focus->trash($module, $record);
	}
	$log->debug("Exiting DeleteEntity method ...");
}

/* Function to install Vtlib Compliant modules
 * @param - $packagename - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
 */
//crmv@27005
function installVtlibModule($packagename, $packagepath, $customized=false) {
	global $log;
	require_once('vtlib/Vtecrm/Package.php');
	require_once('vtlib/Vtecrm/Module.php');
    $vtlib_Utils_Log = true;//crmv@208038
	$package = new Vtecrm_Package();

	if($package->isLanguageType($packagepath)) {
		$package = new Vtecrm_Language();
		$package->import($packagepath, true);
		return;
	}
	$module = $package->getModuleNameFromZip($packagepath);

	// Customization
	if($package->isLanguageType()) {
		require_once('vtlib/Vtecrm/Language.php');
		$languagePack = new Vtecrm_Language();
		@$languagePack->import($packagepath, true);
		return;
	}
	// END

	$module_exists = false;
	$module_dir_exists = false;
	if($module == null) {
		$log->fatal("$packagename Module zipfile is not valid!");
	} else if(Vtecrm_Module::getInstance($module)) {
		$log->fatal("$module already exists!");
		$module_exists = true;
	}
	if($module_exists == false) {
		$log->debug("$module - Installation starts here");
		$package->import($packagepath, true);
		$moduleInstance = Vtecrm_Module::getInstance($module);
		if (empty($moduleInstance)) {
			$log->fatal("$module module installation failed!");
		}
	}
}
//crmv@27005e

/* Function to update Vtlib Compliant modules
 * @param - $module - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
 */
function updateVtlibModule($module, $packagepath) {
	global $log;
	require_once('vtlib/Vtecrm/Package.php');
	require_once('vtlib/Vtecrm/Module.php');
    $vtlib_Utils_Log = true;//crmv@208038
	$package = new Vtecrm_Package();

	if($module == null) {
		$log->fatal("Module name is invalid");
	} else {
		$moduleInstance = Vtecrm_Module::getInstance($module);
		if($moduleInstance) {
			$log->debug("$module - Module instance found - Update starts here");
			$package->update($moduleInstance, $packagepath);
		} else {
			$log->fatal("$module doesn't exists!");
		}
	}
}

/* Function to only initialize the update of Vtlib Compliant modules
 * @param - $module - Name of the module
 * @param - $packagepath - Complete path to the zip file of the Module
 */
function initUpdateVtlibModule($module, $packagepath) {
	global $log;
	require_once('vtlib/Vtecrm/Package.php');
	require_once('vtlib/Vtecrm/Module.php');
    $vtlib_Utils_Log = true;//crmv@208038
	$package = new Vtecrm_Package();

	if($module == null) {
		$log->fatal("Module name is invalid");
	} else {
		$moduleInstance = Vtecrm_Module::getInstance($module);
		if($moduleInstance) {
			$log->debug("$module - Module instance found - Init Update starts here");
			$package->initUpdate($moduleInstance, $packagepath, true);
		} else {
			$log->fatal("$module doesn't exists!");
		}
	}
}

/**
 * this function checks if a given column exists in a given table or not
 * @param string $columnName - the columnname
 * @param string $tableName - the tablename
 * @return boolean $status - true if column exists; false otherwise
 */
function columnExists($columnName, $tableName){
	global $adb;
	$columnNames = array();
	$columnNames = $adb->getColumnNames($tableName);

	if(in_array($columnName, $columnNames)){
		return true;
	}else{
		return false;
	}
}

/* To get modules list for which work flow and field formulas is permitted*/
function com_vtGetModules($adb) {
	global $table_prefix;
	$sql="select distinct ".$table_prefix."_field.tabid, name
		from ".$table_prefix."_field
		inner join ".$table_prefix."_tab
			on ".$table_prefix."_field.tabid=".$table_prefix."_tab.tabid
		where ".$table_prefix."_field.tabid not in(10,15,29) and ".$table_prefix."_tab.presence = 0 and ".$table_prefix."_tab.isentitytype=1";
	$it = new SqlResultIterator($adb, $adb->query($sql));
	$modules = array();
	foreach($it as $row) {
		//crmv@131239
		$moduleInstance = Vtecrm_Module::getInstance($row->name);
		if ($moduleInstance->is_mod_light) continue;
		//crmv@131239e
		if(isPermitted($row->name,'index') == "yes") {
			$modules[$row->name] = getTranslatedString($row->name, $row->name);
		}
	}
	asort($modules);
	return $modules;
}

/**
 * this function accepts a potential id returns the module name and entity value for the related field
 * @param integer $id - the potential id
 * @return array $data - the related module name and field value
 */
function getRelatedInfo($id){
	global $adb,$table_prefix;
	$data = array();
	$sql = "select related_to from ".$table_prefix."_potential where potentialid=?";
	$result = $adb->pquery($sql, array($id));
	if($adb->num_rows($result)>0){
		$relID = $adb->query_result($result, 0, "related_to");
		$setype =  getSalesEntityType($relID); //crmv@171021
		$data = array("setype"=>$setype, "relID"=>$relID);
	}
	return $data;
}

/**
 * this function accepts an ID and returns the entity value for that id
 * @param integer $id - the crmid of the record
 * @return string $data - the entity name for the id
 */
function getRecordInfoFromID($id){
	global $adb,$table_prefix;
	$data = array();
	//crmv@171021
	$setype =  getSalesEntityType($id);
	if (!empty($setype)) {
		$data = getEntityName($setype, $id);
	}
	//crmv@171021e
	$data = array_values($data);
	$data = $data[0];
	return $data;
}

/**
 * this function accepts an tabiD and returns the tablename, fieldname and fieldlabel for email field
 * @param integer $tabid - the tabid of current module
 * @return string $fields - the array of mail field's tablename, fieldname and fieldlabel
 */
function getMailFields($tabid){
	global $adb,$table_prefix;
	$fields = array();
	$result = $adb->pquery("SELECT tablename,fieldlabel,fieldname FROM ".$table_prefix."_field WHERE tabid=? AND uitype IN (13,104)", array($tabid));
	if($adb->num_rows($result)>0){
		$tablename = $adb->query_result($result, 0, "tablename");
		$fieldname = $adb->query_result($result, 0, "fieldname");
		$fieldlabel = $adb->query_result($result, 0, "fieldlabel");
		$fields = array("tablename"=>$tablename,"fieldname"=>$fieldname,"fieldlabel"=>$fieldlabel);
	}
	return $fields;
}



/** Function to set the PHP memory limit to the specified value, if the memory limit set in the php.ini is less than the specified value
 * @param $newvalue -- Required Memory Limit
 */
function _phpset_memorylimit_MB($newvalue) {
    $current = @ini_get('memory_limit');
    if(preg_match("/(.*)M/", $current, $matches)) {
        // Check if current value is less then new value
        if($matches[1] < $newvalue) {
            @ini_set('memory_limit', "{$newvalue}M");
        }
    }
}


/** Function to get the tab meta information for a given id
  * @param $tabId -- tab id :: Type integer
  * @returns $tabInfo -- array of preference name to preference value :: Type array
  */
function getTabInfo($tabId) {
	global $adb,$table_prefix;

	$tabInfoResult = $adb->pquery('SELECT prefname, prefvalue FROM '.$table_prefix.'_tab_info WHERE tabid=?', array($tabId));
	$tabInfo = array();
	for($i=0; $i<$adb->num_rows($tabInfoResult); ++$i) {
		$prefName = $adb->query_result($tabInfoResult, $i, 'prefname');
		$prefValue = $adb->query_result($tabInfoResult, $i, 'prefvalue');
		$tabInfo[$prefName] = $prefValue;
	}
	return $tabInfo;
}

// crmv@105600
/** Function to return block name
 * @param Integer -- $blockid
 * @return String - Block Name
 */
function getBlockName($blockid) {
	global $adb,$table_prefix;
	
	if(!empty($blockid)){
	
		$cache = RCache::getInstance();
		$key = "blocklabels_".$blockid;
		$label = $cache->get($key);
		
		if ($label) return $label;
	
		$block_res = $adb->pquery('SELECT blocklabel FROM '.$table_prefix.'_blocks WHERE blockid = ?',array($blockid));
		if($adb->num_rows($block_res)){
			$blockname = $adb->query_result($block_res,0,'blocklabel');
			$cache->set($key, $blockname);
			return $blockname;
		}
	}
	return '';
}
// crmv@105600e

/* Function to get the name of the Field which is used for Module Specific Sequence Numbering, if any
 * @param module String - Module label
 * return Array - Field name and label are returned */
function getModuleSequenceField($module) {
	global $adb, $log,$table_prefix;
	$log->debug("Entering function getModuleSequenceFieldName ($module)...");
	$field = null;

	if (!empty($module)) {

		// First look at the cached information
		$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
		if($cachedModuleFields === false) {
			//uitype 4 points to Module Numbering Field
			$seqColRes = $adb->pquery("SELECT fieldname, fieldlabel, columnname, tablename FROM ".$table_prefix."_field WHERE uitype=? AND tabid=? and ".$table_prefix."_field.presence in (0,2)", array('4', getTabid($module))); // crmv@199978
			if($adb->num_rows($seqColRes) > 0) {
				$fieldname = $adb->query_result($seqColRes,0,'fieldname');
				$columnname = $adb->query_result($seqColRes,0,'columnname');
				$fieldlabel = $adb->query_result($seqColRes,0,'fieldlabel');
				$tablename = $adb->query_result($seqColRes,0,'tablename'); // crmv@199978

				$field = array();
				$field['name'] = $fieldname;
				$field['column'] = $columnname;
				$field['label'] = $fieldlabel;
				$field['table'] = $tablename; // crmv@199978
			}
		} else {
			foreach($cachedModuleFields as $fieldinfo) {
				if($fieldinfo['uitype'] == '4') {
					$field = array();

					$field['name'] = $fieldinfo['fieldname'];
					$field['column'] = $fieldinfo['columnname'];
					$field['label'] = $fieldinfo['fieldlabel'];
					$field['table'] = $fieldinfo['tablename']; // crmv@199978

					break;
				}
			}
		}
	}

	$log->debug("Exiting getModuleSequenceFieldName...");
	return $field;
}

/* Function to get the Result of all the field ids allowed for Duplicates merging for specified tab/module (tabid) */
function getFieldsResultForMerge($tabid) {
	global $log, $adb,$table_prefix;
	$log->debug("Entering getFieldsResultForMerge(".$tabid.") method ...");

	$nonmergable_tabids = array(29);

	if (in_array($tabid, $nonmergable_tabids)) {
		return null;
	}

	// List of Fields not allowed for Duplicates Merging based on the module (tabid) [tabid to fields mapping]
	$nonmergable_field_tab = Array(
		4 => array('portal','imagename'),
		13 => array('filename','comments'),
	);

	$nonmergable_displaytypes = Array(4);
	$nonmergable_uitypes = Array('70','69','4');

	$sql = "SELECT fieldid,typeofdata FROM ".$table_prefix."_field WHERE tabid = ? and ".$table_prefix."_field.presence in (0,2)";
	$params = array($tabid);

	$where = '';

	if (isset($nonmergable_field_tab[$tabid]) && count($nonmergable_field_tab[$tabid]) > 0) {
		$where .= " AND fieldname NOT IN (". generateQuestionMarks($nonmergable_field_tab[$tabid]) .")";
		array_push($params, $nonmergable_field_tab[$tabid]);
	}

	if (count($nonmergable_displaytypes) > 0) {
		$where .= " AND displaytype NOT IN (". generateQuestionMarks($nonmergable_displaytypes) .")";
		array_push($params, $nonmergable_displaytypes);
	}
	if (count($nonmergable_uitypes) > 0) {
		$where .= " AND uitype NOT IN ( ". generateQuestionMarks($nonmergable_uitypes) .")" ;
		array_push($params, $nonmergable_uitypes);
	}

	if (trim($where) != '') {
		$sql .= $where;
	}

	$res = $adb->pquery($sql, $params);
	$log->debug("Exiting getFieldsResultForMerge method ...");
	return $res;
}

// Update all the data refering to currency $old_cur to $new_cur
function transferCurrency($old_cur, $new_cur) {

	// Transfer User currency to new currency
	transferUserCurrency($old_cur, $new_cur);

	// Transfer Product Currency to new currency
	transferProductCurrency($old_cur, $new_cur);

	// Transfer PriceBook Currency to new currency
	transferPriceBookCurrency($old_cur, $new_cur);
}

// Function to transfer the users with currency $old_cur to $new_cur as currency
function transferUserCurrency($old_cur, $new_cur) {
	global $log, $adb, $current_user, $table_prefix; // crmv@42266
	$log->debug("Entering function transferUserCurrency...");

	$sql = "update ".$table_prefix."_users set currency_id=? where currency_id=?";
	$adb->pquery($sql, array($new_cur, $old_cur));

	$current_user->retrieve_entity_info($current_user->id,"Users");
	$log->debug("Exiting function transferUserCurrency...");
}

// Function to transfer the products with currency $old_cur to $new_cur as currency
function transferProductCurrency($old_cur, $new_cur) {
	global $log, $adb,$table_prefix;
	$log->debug("Entering function updateProductCurrency...");
	$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024
	$prod_res = $adb->pquery("select productid from ".$table_prefix."_products where currency_id = ?", array($old_cur));
	$numRows = $adb->num_rows($prod_res);
	$prod_ids = array();
	for($i=0;$i<$numRows;$i++) {
		$prod_ids[] = $adb->query_result($prod_res,$i,'productid');
	}
	if(count($prod_ids) > 0) {
		$prod_price_list = $InventoryUtils->getPricesForProducts($new_cur,$prod_ids);

		for($i=0;$i<count($prod_ids);$i++) {
			$product_id = $prod_ids[$i];
			$unit_price = $prod_price_list[$product_id];
			$query = "update ".$table_prefix."_products set currency_id=?, unit_price=? where productid=?";
			$params = array($new_cur, $unit_price, $product_id);
			$adb->pquery($query, $params);
		}
	}
	$log->debug("Exiting function updateProductCurrency...");
}

// Function to transfer the pricebooks with currency $old_cur to $new_cur as currency
// and to update the associated products with list price in $new_cur currency
function transferPriceBookCurrency($old_cur, $new_cur) {
	global $log, $adb,$table_prefix;
	$log->debug("Entering function updatePriceBookCurrency...");
	$pb_res = $adb->pquery("select pricebookid from ".$table_prefix."_pricebook where currency_id = ?", array($old_cur));
	$numRows = $adb->num_rows($pb_res);
	$pb_ids = array();
	for($i=0;$i<$numRows;$i++) {
		$pb_ids[] = $adb->query_result($pb_res,$i,'pricebookid');
	}

	if(count($pb_ids) > 0) {
		for($i=0;$i<count($pb_ids);$i++) {
			$pb_id = $pb_ids[$i];
			$focus = CRMEntity::getInstance('PriceBooks');
			$focus->id = $pb_id;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($pb_id, "PriceBooks");
			$focus->column_fields['currency_id'] = $new_cur;
			$focus->save("PriceBooks");
		}
	}

	$log->debug("Exiting function updatePriceBookCurrency...");
}
//functions for asterisk integration start
/**
 * this function returns the caller name based on the phone number that is passed to it
 * @param $from - the number which is calling
 * returns caller information in name(type) format :: for e.g. Mary Smith (Contact)
 * if no information is present in database, it returns :: Unknown Caller (Unknown)
 */
function getCallerName($from) {
	global $adb;

	//information found
	$callerInfo = getCallerInfo($from);

	if($callerInfo != false){
		$callerName = decode_html($callerInfo['name']);
		$module = $callerInfo['module'];
		$callerModule = " (<a href='index.php?module=$module&action=index'>$module</a>)";
		$callerID = $callerInfo['id'];

		$caller =$caller."<a href='index.php?module=$module&action=DetailView&record=$callerID'>$callerName</a>$callerModule";

	}else{
		$caller = $caller."<br>
						<a target='_blank' href='index.php?module=Leads&action=EditView&phone=$from'>".getTranslatedString('LBL_CREATE_LEAD')."</a><br>
						<a target='_blank' href='index.php?module=Contacts&phone=$from'>".getTranslatedString('LBL_CREATE_CONTACT')."</a><br>
						<a target='_blank' href='index.php?module=Accounts&action=EditView&phone=$from'>".getTranslatedString('LBL_CREATE_ACCOUNT')."</a>";
	}
	return $caller;
}

/**
 * this function searches for a given number in vte and returns the callerInfo in an array format
 * currently the search is made across only leads, accounts and contacts modules
 *
 * @param $number - the number whose information you want
 * @return array in format array(name=>callername, module=>module, id=>id);
 */
function getCallerInfo($number){
	global $adb, $log;
	if(empty($number)){
		return false;
	}
	$caller = "Unknown Number (Unknown)"; //declare caller as unknown in beginning

	$params = array();
	$name = array('Contacts', 'Accounts', 'Leads', 'Vendors'); // crmv@100312
	foreach ($name as $module) {
		if(!vtlib_isModuleActive($module)) continue; // crmv@157736
		$focus = CRMEntity::getInstance($module);
		$query = $focus->buildSearchQueryForFieldTypes(array(11,1014), $number);
		if(empty($query)) return;

		$result = $adb->pquery($query, array());
		if($adb->num_rows($result) > 0 ){
			$callerName = $adb->query_result($result, 0, "name");
			$callerID = $adb->query_result($result,0,'id');
			$data = array("name"=>$callerName, "module"=>$module, "id"=>$callerID);
			return $data;
		}
	}
	return false;
}

/**
 * this function returns the tablename and primarykeys for a given module in array format
 * @param object $adb - peardatabase type object
 * @param string $module - module name for  which you want the array
 * @return array(tablename1=>primarykey1,.....)
 */
function get_tab_name_index($adb, $module){
	global $table_prefix;
	$tabid = getTabid($module);
	$sql = "select * from ".$table_prefix."_tab_name_index where tabid = ?";
	$result = $adb->pquery($sql, array($tabid));
	$count = $adb->num_rows($result);
	$data = array();

	for($i=0; $i<$count; $i++){
		$tablename = $adb->query_result($result, $i, "tablename");
		$primaryKey = $adb->query_result($result, $i, "primarykey");
		$data[$tablename] = $primaryKey;
	}
	return $data;
}

/**
 * this function returns the value of use_asterisk from the database for the current user
 * @param string $id - the id of the current user
 */
//crmv@18038 crmv@105600
function get_use_asterisk($id='',$mode='call'){ //crmv@36559
	global $adb,$current_user,$table_prefix;
	
	if ($id == '') $id = $current_user->id;
	
	$cache = RCache::getInstance();
	$key = "useasterisk_{$id}_{$mode}";
	$result = $cache->get($key);
	
	if (!empty($result)) return $result;
	
	if(!vtlib_isModuleActive('PBXManager')){
		$result = 'false';
		$cache->set($key, $result);
		return $result;
	}

	$sql = "select * from ".$table_prefix."_asteriskextensions where userid = ?";
	$res = $adb->pquery($sql, array($id));
	if($adb->num_rows($res)>0){
		$use_asterisk = $adb->query_result_no_html($res, 0, "use_asterisk");
		$asterisk_extension = $adb->query_result_no_html($res, 0, "asterisk_extension");
		if ($mode == "incoming"){
			//TODO: controllare se ho impostato il server asterisk, altrimenti non ha senso controllare le chiamate in entrata
			if ($use_asterisk == 0){
				$result = 'false';
			} else {
				$result = 'true';
			}
		} else {
			if(empty($asterisk_extension)){
				$result = 'false';
			} else {
				$result = 'true';
			}
		}
	}else{
		$result = 'false';
	}
	$cache->set($key, $result);
	return $result;
}
//crmv@18038e crmv@105600e

/**
 * this function adds a record to the callhistory module
 *
 * @param string $userExtension - the extension of the current user
 * @param string $callfrom - the caller number
 * @param string $callto - the called number
 * @param string $status - the status of the call (outgoing/incoming/missed)
 * @param object $adb - the peardatabase object
 */
function addToCallHistory($userExtension, $callfrom, $callto, $status, $adb, $useCallerInfo){
	global $table_prefix;
	$sql = "select * from ".$table_prefix."_asteriskextensions where asterisk_extension=?";
	$result = $adb->pquery($sql,array($userExtension));
	$userID = $adb->query_result($result, 0, "userid");
	if(empty($userID)) {
		// we have observed call to extension not configured in vte will returns NULL
		return;
	}
	$crmID = $adb->getUniqueID($table_prefix.'_crmentity');
	$timeOfCall = date('Y-m-d H:i:s');

	// crmv@150773
	$params = array($crmID, $userID, $userID, 0, "PBXManager", $timeOfCall, $timeOfCall, NULL, NULL, 0, 1, 0);
	$sql = "insert into ".$table_prefix."_crmentity (crmid, smcreatorid, smownerid, modifiedby, setype, createdtime, modifiedtime, viewedtime, status, version, presence, deleted) values (".generateQuestionMarks($params).")";
	$adb->pquery($sql, $params);
	// crmv@150773e

	if(empty($callfrom)){
		$callfrom = "Unknown";
	}
	if(empty($callto)){
		$callto = "Unknown";
	}

	if($status == 'outgoing'){
		//call is from user to record
		$sql = "select * from ".$table_prefix."_asteriskextensions where asterisk_extension=?";
		$result = $adb->pquery($sql, array($callfrom));
		if($adb->num_rows($result)>0){
			$userid = $adb->query_result($result, 0, "userid");
			$callerName = getUserFullName($userid);
		}

		$receiver = $useCallerInfo;
		if(empty($receiver)){
			$receiver = "Unknown";
		}else{
			$receiver = "<a href='index.php?module=".$receiver['module']."&action=DetailView&record=".$receiver['id']."'>".$receiver['name']."</a>";
		}
	}else{
		//call is from record to user
		$sql = "select * from ".$table_prefix."_asteriskextensions where asterisk_extension=?";
		$result = $adb->pquery($sql,array($callto));
		if($adb->num_rows($result)>0){
			$userid = $adb->query_result($result, 0, "userid");
			$receiver = getUserFullName($userid);
		}
		$callerName = $useCallerInfo;
		if(empty($callerName)){
			$callerName = "Unknown $callfrom";
		}else{
			$callerName = "<a href='index.php?module=".$callerName['module']."&action=DetailView&record=".$callerName['id']."'>".decode_html($callerName['name'])."</a>";
		}
	}

	$sql = "insert into ".$table_prefix."_pbxmanager (pbxmanagerid,callfrom,callto,timeofcall,status)values (?,?,?,?,?)";
	$params = array($crmID, $callerName, $receiver, $timeOfCall, $status);
	$adb->pquery($sql, $params);
	return $crmID;
}
//functions for asterisk integration end
//crmv@16312
function validateAlphaNumericInput($string){
    preg_match('/^[\w _\-]+$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;
}

function validateServerName($string){
    preg_match('/^[\w\-\.\\/:]+$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;
}

function validateEmailId($string){
    preg_match('/^[a-zA-Z0-9]+([\_\-\.]*[a-zA-Z0-9]+[\_\-]?)*@[a-zA-Z0-9]+([\_\-]?[a-zA-Z0-9]+)*\.+([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)*$/', $string, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;
}
//crmv@16312 end
//crmv@25610 crmv@50039 crmv@92843
// modifica il valore di una data/ora in accordo con il timezone passato
// il valore deve essere nel formato YYYY-MM-DD HH:II:SS
// the second parameter is obsolete and should not be used anymore
function adjustTimezone($value, $tzoffset = 0, $user_timezone = null, $reverse = false) {
	global $default_timezone, $current_user;

	if (empty($user_timezone)) {
		$user_timezone = $current_user->column_fields['user_timezone'];
	}
	
	$value = trim($value);
	
	// skip if empty
	if (substr($value, 0, 4) === '0000') return $value; // crmv@163361
	
	if (!empty($value) && !empty($user_timezone) && $user_timezone != $default_timezone) {
		// controlla se c'è un'ora nella data
		if (preg_match('/[0-5]?[0-9]:[0-5]?[0-9]/', $value, $matches)) {
			
			$onlyHour = ($matches[0] == $value);
			
			// add seconds if missing
			$times = explode(':', substr($value, 11));
			if (count($times) <= 2) $value .= ':00';
			
			// add a fake date
			if ($onlyHour) $value = '2010-10-10 '.$value;

			// changed to use the value as date reference
			$date = DateTime::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone($reverse ? $user_timezone : $default_timezone));
			if ($date !== false) {
				$date->setTimezone(new DateTimeZone($reverse ? $default_timezone : $user_timezone));
				$value = $date->format('Y-m-d H:i:s');
			}
			
			// remove the fake date
			if ($onlyHour) $value = substr($value, strpos($value, ' '));
		}

	}
	return $value;
}
//crmv@25610e crmv@50039e crmv@92843e
//crmv@31126
function getValidDBInsertDateTimeValue($value) {
	$value = trim($value);
	$valueList = explode(' ',$value);
	if(count($valueList) == 2) {
		$dbDateValue = getValidDBInsertDateValue($valueList[0]);
		$dbTimeValue = $valueList[1];
		if(!empty($dbTimeValue) && strpos($dbTimeValue, ':') === false) {
			$dbTimeValue = $dbTimeValue.':';
		}
		$timeValueLength = strlen($dbTimeValue);
		if(!empty($dbTimeValue) &&  strrpos($dbTimeValue, ':') == ($timeValueLength-1)) {
			$dbTimeValue = $dbTimeValue.'00';
		}
		try {
			$dateTime = new DateTimeField($dbDateValue.' '.$dbTimeValue);
			return $dateTime->getDBInsertDateTimeValue();
		} catch (Exception $ex) {
			return '';
		}
	} elseif(count($valueList) == 1) {
		return getValidDBInsertDateValue($value);
	}
}
//crmv@31126e
/*
 * Function to set, character set in the header, as given in include/language/*_lang.php
 */
function insert_charset_header()
{
	global $app_strings, $default_charset;
	$charset = $default_charset;

	if(isset($app_strings['LBL_CHARSET']))
	{
		$charset = $app_strings['LBL_CHARSET'];
	}
	header('Content-Type: text/html; charset='. $charset);
}