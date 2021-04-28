<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/database/PearDatabase.php');
require_once('vtlib/Vtecrm/Utils.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/ListViewUtils.php');
require_once('include/utils/EditViewUtils.php');
require_once('include/utils/DetailViewUtils.php');
require_once('include/utils/CommonUtils.php');
require_once('include/utils/SearchUtils.php');
require_once('include/FormValidationUtil.php');
require_once('include/Zend/Json.php');	//crmv@9183
require_once('modules/Picklistmulti/Picklistmulti_class.php');	//crmv@8982
if (file_exists('modules/Morphsuit/utils/MorphsuitUtils.php')) {
	require_once('modules/Morphsuit/utils/MorphsuitUtils.php');
}
require_once('modules/SDK/SDK.php');	//crmv@sdk
require_once('include/utils/db_utils.php');	//crmv@26666

// crmv@42024
// questa classe poco alla volta inglobera' tutte le funzioni crmv_utils
class CRMVUtils extends SDKExtendableUniqueClass {

	protected $cachedUserColorDb = array(); // crmv@194723
	
	static public function callMethodByName($name, $arguments = array()) {
		$CRMVUtils = CRMVUtils::getInstance();
		return call_user_func_array(array($CRMVUtils, $name), $arguments);
	}

	// ----- CRMV UTILS FUNCTIONS -----

	function getJSGlobalVars() {
		global $default_charset, $current_user, $current_language, $default_language;
		global $default_decimal_separator, $default_thousands_separator, $default_decimals_num;
		global $upload_maxsize; // crmv@97013
		
		$RV = ResourceVersion::getInstance();

		$theme = CRMVUtils::getApplicationTheme();
		$TU = ThemeUtils::getInstance($theme);
		
		$jsvar = array(
			'gVTModule' => $_REQUEST['module'],
			'default_charset' => $default_charset,
			// crmv@91082
			'current_user' => array(
				'id' => $current_user ? $current_user->id : 0,
				'user_name' => $current_user ? $current_user->column_fields['user_name'] : '',
				'weekstart' => $current_user && isset($current_user->column_fields['weekstart']) ? $current_user->column_fields['weekstart'] : 1, // crmv@150808
				'dark_mode' => $current_user ? boolval($current_user->column_fields['dark_mode']) : false, // crmv@195963
			),
			// crmv@91082e
			// crmv@96023
			'current_language' => $current_language ?: $default_language,
			// crmv@96023e
			'userDateFormat' => $current_user->column_fields['date_format'],
			'decimal_separator' => isset($current_user->column_fields['decimal_separator']) ? $current_user->column_fields['decimal_separator'] : $default_decimal_separator,
			'thousands_separator' => isset($current_user->column_fields['thousands_separator']) ? $current_user->column_fields['thousands_separator'] : $default_thousands_separator,
			'decimals_num' => $current_user->column_fields['decimals_num'] > 0 ? intval($current_user->column_fields['decimals_num']) : $default_decimals_num,
			'inventory_modules' => getInventoryModules(),
			'max_upload_size' => $upload_maxsize, // crmv@97013
			'script_included' => array(),
			'js_resource_version' => $RV->getJSResources(), // crmv@140887
			'theme_config' => $TU->getAll(),
		);
		return $jsvar;
	}

	// crmv@111926
	/**
	 * Check if the number of input variables exceeded the max_input_var setting
	 * The $counterVar should be populated with the number of inputs sent
	 * The check is skipped if compression is used
	 */
	public function checkMaxInputVars($counterVar, $type = 'POST') {
		// the max_input_vars was introduced in PHP 5.3.9
		if (PHP_VERSION_ID >= 50309 && $_REQUEST['compressedData'] !== 'true') { // crmv@162674
			$max = intval(ini_get("max_input_vars")) ?: 1000;
			$vname = '_'.strtoupper($type);
			if (isset($GLOBALS[$vname][$counterVar])) {
				$count = $GLOBALS[$vname][$counterVar];
			} else {
				// the counter var is missing, so the input has been truncated!
				return false;
			}
			if ($count > $max) {
				// too many variables!
				return false;
			}
		}
		return true;
	}
	// crmv@111926e

	//crmv@42707
	function isVteDesktop() {
		return ($_SERVER['HTTP_ORIGIN'] == 'app://biz.crmvillage.vtedesktop' || strpos($_SERVER['HTTP_USER_AGENT'], 'TideSDK') !== false);
	}
	//crmv@42707e

	// crmv@43147
	var $shareTokenDuration = 432000;	// 5 days (in seconds)

	// returns a string where the occurencies of {name} has been replaced with the values in "$params"
	function replaceStringTemplate($template, $params = array(), $clearExtraTags = false) {
		$maxTagLength = 20;
		// replace template params
		if (!empty($params) && is_array($params)) {
			$search = array_map(function($item) {
				return "{".$item."}";
			}, array_keys($params));
			$replace = array_values($params);
			$template = str_replace($search, $replace, $template);
			if ($clearExtraTags) $template = preg_replace('/\{[^}]{1,'.$maxTagLength.'}\}/', '', $template);
		}
		return $template;
	}

	function generateShareToken($module, $recordid, $edit = false, $extraParams = null) {
		global $adb, $table_prefix, $current_user;

		// 80 chars, unique
		do {
			$token = sha1(uniqid(mt_rand(), true)) . sha1(uniqid(mt_rand(), true));
			$res = $adb->pquery("select token from {$table_prefix}_sharetokens where token = ?", array($token));
			$count = $adb->num_rows($res);
		} while ($count > 0);

		// now insert into the table
		$adb->pquery("insert into {$table_prefix}_sharetokens (token, expiretime, userid, crmid, module, edit, otherinfo) values (?,?,?,?,?,?,?)", array($token, date('Y-m-d H:i:s', time()+$this->shareTokenDuration), $current_user->id, $recordid, $module, ($edit ? 1 : 0), Zend_Json::encode($extraParams)));

		return $token;
	}

	function deleteShareToken($token) {
		global $adb, $table_prefix;
		$adb->pquery("delete from {$table_prefix}_sharetokens where token = ?", array($token));
	}

	function validateShareToken($token) {
		global $adb, $table_prefix;

		// delete old tokens
		$adb->pquery("delete from {$table_prefix}_sharetokens where expiretime < ?", array(date('Y-m-d H:i:s')));

		// check now
		$res = $adb->pquery("select * from {$table_prefix}_sharetokens where token = ?", array($token));
		if ($adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			if (!empty($row['otherinfo'])) $row['otherinfo'] = Zend_Json::decode($row['otherinfo']);
			return $row;
		} else {
			return array();
		}
	}

	// returns an array (subject, body) of the email that should be sent to share a record
	function getSharedEmailTemplate($recordid, $edit = false) {
		global $adb, $table_prefix, $current_user, $site_URL;

		$ret = array();

		$username = getUserFullName($current_user->id);
		$module = getSalesEntityType($recordid);

		if ($module) {

			$focus = CRMEntity::getInstance($module);
			$focus->retrieve_entity_info($recordid, $module);
			$singleLabel = getTranslatedString('SINGLE_'.$module, $module);
			$focusName = getEntityName($module, $recordid);
			$focusName = $focusName[$recordid];

			$token = $this->generateShareToken($module, $recordid, $edit);

			$subjectTpl = getTranslatedString('LBL_SHARE_EMAIL_SUBJECT', 'APP_STRINGS');
			$subject = $this->replaceStringTemplate($subjectTpl, array('user'=>$username, 'type'=>$singleLabel));

			$bodyTpl = getTranslatedString('LBL_SHARE_EMAIL_BODY');
			$body = $this->replaceStringTemplate($bodyTpl, array(
				'type'=>$singleLabel,
				'entityname'=>$focusName,
				'user'=>$username,
				'site_url'=>$site_URL,
				'token'=>$token,
				'date'=>date('Y-m-d', time()+$this->shareTokenDuration),
			));

			$ret['subject'] = $subject;
			$ret['body'] = $body;
		}
		return $ret;
	}
	// crmv@43147e

	//crmv@3085m crmv@150751
	function getPinRelatedLists($tabid, $record='') {
		global $adb, $table_prefix, $current_user;
		
		$moduleInstance = Vtecrm_Module::getInstance($tabid);
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$PMUtils = ProcessMakerUtils::getInstance();
		$tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$moduleInstance->name,'id'));
		$vh_info = array(
				'relatedlists' => array($table_prefix.'_relatedlists',"{$table_prefix}_relatedlists.tabid=?",$tabid),
				'tab' => array($table_prefix.'_tab',"{$table_prefix}_relatedlists.related_tabid = {$table_prefix}_tab.tabid"),
		);
		if (!empty($tvh_id)) {
			$vh_info['relatedlists'] = array($table_prefix.'_relatedlists_vh',"{$table_prefix}_relatedlists_vh.versionid=? and {$table_prefix}_relatedlists_vh.tabid=?",array($tvh_id,$tabid));
			$vh_info['tab'] = array($table_prefix.'_tab_vh',"{$table_prefix}_relatedlists_vh.related_tabid = {$table_prefix}_tab_vh.tabid and {$table_prefix}_tab_vh.versionid = {$table_prefix}_relatedlists_vh.versionid");
		}
		
		$query = "select {$table_prefix}_relatedlists_pin.relation_id, {$vh_info['tab'][0]}.name, {$vh_info['relatedlists'][0]}.label
		from {$table_prefix}_relatedlists_pin
		inner join {$vh_info['relatedlists'][0]} on {$vh_info['relatedlists'][0]}.relation_id = {$table_prefix}_relatedlists_pin.relation_id
		inner join {$vh_info['tab'][0]} on {$vh_info['tab'][1]}
		where userid = ? and {$vh_info['relatedlists'][1]}";
		$result = $adb->pquery($query,array($current_user->id,$vh_info['relatedlists'][2]));
		$relatedlists = array();
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByASsoc($result)) {
				$relatedlists[$row['name']] = $row['label'];
			}
		}
		
		//crmv@61012
		$query = "select {$table_prefix}_relatedlists_pin.relation_id, {$vh_info['relatedlists'][0]}.label
		from {$table_prefix}_relatedlists_pin
		inner join {$vh_info['relatedlists'][0]} on {$vh_info['relatedlists'][0]}.relation_id = {$table_prefix}_relatedlists_pin.relation_id
		left join {$vh_info['tab'][0]} on {$vh_info['tab'][1]}
		where {$vh_info['tab'][0]}.tabid is null and userid = ? and {$vh_info['relatedlists'][1]}";
		$result = $adb->pquery($query,array($current_user->id,$vh_info['relatedlists'][2]));
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByASsoc($result)) {
				if (!isset($relatedlists[$row['label']])){
					$relatedlists[$row['label']] = $row['label'];
				}
			}
		}
		//crmv@61012 e
		
		return $relatedlists;
	}
	//crmv@3085me crmv@150751e
	
	//crmv@62415 crmv@150751
	function getPinRelationIds($tabid, $record='') {
		global $adb, $table_prefix, $current_user;
		$relatedlists = Array();
		
		$moduleInstance = Vtecrm_Module::getInstance($tabid);
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$PMUtils = ProcessMakerUtils::getInstance();
		$tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$moduleInstance->name,'id'));
		$vh_info = array(
				'relatedlists' => array($table_prefix.'_relatedlists',"{$table_prefix}_relatedlists.tabid=?",$tabid),
		);
		if (!empty($tvh_id)) {
			$vh_info['relatedlists'] = array($table_prefix.'_relatedlists_vh',"{$table_prefix}_relatedlists_vh.versionid=? and {$table_prefix}_relatedlists_vh.tabid=?",array($tvh_id,$tabid));
		}
		
		$query = "select {$table_prefix}_relatedlists_pin.relation_id
		from {$table_prefix}_relatedlists_pin
		inner join {$vh_info['relatedlists'][0]} on {$vh_info['relatedlists'][0]}.relation_id = {$table_prefix}_relatedlists_pin.relation_id
		where userid = ? and {$vh_info['relatedlists'][1]}";
		$result = $adb->pquery($query,array($current_user->id,$vh_info['relatedlists'][2]));
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByASsoc($result)) {
				$relatedlists[] = $row['relation_id'];
			}
		}
		return $relatedlists;
	}
	//crmv@62415 crmv@150751e

	// crmv@44323
	function getCrmvDivHtml($id, $title, $contents, $buttons = '', $draggable = true) {
		$smarty = new VteSmarty();

		$smarty->assign('CRMVDIV_ID', $id);
		$smarty->assign('CRMVDIV_TITLE', $title);
		$smarty->assign('CRMVDIV_CONTENT', $contents);
		$smarty->assign('CRMVDIV_BUTTONS', $buttons);
		$smarty->assign('CRMVDIV_DRAGGABLE', $draggable);
		return $smarty->fetch('CrmvDiv.tpl');
	}
	// crmv@44323e

	//crmv@56580 // crmv@194723
	function getUserColorDb($ownerId, $activityid='') {
		global $adb, $current_user, $table_prefix;
		
		if ($activityid != '') {
			$isInvited = isCalendarInvited($current_user->id, $activityid);
			if ($isInvited[0] == 'yes') {
				$ownerId = $current_user->id;
			}
		}
		if (!isset($this->cachedUserColorDb[$ownerId])) {
			$res = $adb->pquery("SELECT cal_color FROM {$table_prefix}_users WHERE id = ?", array($ownerId));
			if (!!$res && $adb->num_rows($res) > 0) {
				$this->cachedUserColorDb[$ownerId] = $adb->query_result($res, 0, 'cal_color');
			}
		}
		return $this->cachedUserColorDb[$ownerId];
	}
	//crmv@56580e // crmv@194723e
	
	//crmv@57221 crmv@104568 crmv@118551
	function getAllConfigurationLayout() {
		$VP = VTEProperties::getInstance();
		$values = $VP->getAll();
		$outvalues = array();
		// filter, get only layout prefs
		$prefix = 'layout.';
		$len = strlen($prefix);
		foreach ($values as $key => $val) {
			if (substr($key, 0, $len) == $prefix) {
				$outvalues[substr($key, $len)] = $val;
			}
		}
		return $outvalues;
	}
	
	function getConfigurationLayout($var) {
		$config = $this->getAllConfigurationLayout();
		if (isset($config[$var])) return $config[$var];
	}
	
	function setConfigurationLayout($var, $value) {
		require_once('include/utils/VTEProperties.php');
		$VP = VTEProperties::getInstance();
		$VP->set("layout.$var",$value);
	}
	//crmv@57221e crmv@104568e crmv@118551e
	
	//crmv@59091
	function getNewModuleLabel($module) {
		$label = 'LBL_NEW_'.strtoupper($module);
		$transLabel = getTranslatedString($label, $module);
		if($transLabel == $label) {
			global $app_strings;
			if ($module == 'Event') {
				$transLabel = $app_strings['LBL_CREATE'].' '.$app_strings['LBL_AN'].' '.$app_strings['Event'];
			} elseif ($module == 'Task' || $module == 'Calendar') {
				$transLabel = $app_strings['LBL_CREATE'].' '.$app_strings['LBL_A'].' '.$app_strings['Task'];
			} else {
				$transLabel = getTranslatedString('LBL_NEW').' '.getTranslatedString($module, $module);
			}
		}
		return $transLabel;
	}
	//crmv@59091e

	// crmv@97237
	function showAccessDenied($text, $useHeader = true) {
		global $app_strings, $mod_strings, $theme;
		
		if ($useHeader) {
			require('modules/VteCore/header.php');
		}
		
		$smarty = new VteSmarty();
		$smarty->assign("APP", $app_strings);
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("THEME", $theme);
		
		$smarty->assign("TEXT", $text);
		
		$smarty->display('AccessDenied.tpl');
	}
	// crmv@97237e
	
	// crmv@164654
	/**
	 * Format a date to be shown to the user
	 */
	static public function timestamp($datetime) {
		return getTranslatedString('LBL_DAY'.date('w',strtotime($datetime)),'Calendar').' '.getDisplayDate($datetime);
	}
	
	/**
	 * Format a past date in a human readably way with the time elapsed since then (eg: "2 days ago")
	 * If a future date is passed, it's formatted as "in 4 days"
	 */
	static public function timestampAgo($datetime) {
		
		// check empty dates
		if (in_array(substr($datetime, 0, 10),array('','1970-01-01','0000-00-00'))) {
			return '';
		}
		
		$periods = array("SECOND", "MINUTE", "HOUR", "DAY", "WEEK", "MONTH", "YEAR", "DECADE");
		$lengths = array("60", "60", "24", "7", "4.35", "12", "10");
		
		$difference = time() - strtotime($datetime);
		$absd = abs($difference);
		for($j = 0; isset($lengths[$j]) && $absd >= $lengths[$j]; $j++) {
			$absd /= $lengths[$j];
		}
		$absd = round($absd);
		if($absd != 1) {
			$periods[$j] .= "S";
		}
		if ($absd == 0 && $periods[$j] == 'SECONDS') {
			$text = getTranslatedString('LBL_NOW','APP_STRINGS');
		} else {
			$period = $absd.' '.getTranslatedString('LBL_'.$periods[$j],'APP_STRINGS');
			if ($difference > 0) {
				$text = sprintf(getTranslatedString('LBL_AGO','APP_STRINGS'), $period);
			} else {
				$text = sprintf(getTranslatedString('LBL_IN_TIME','APP_STRINGS'), $period);
			}
		}
		
		return strtolower($text);
	}
	// crmv@164654e

	// crmv@123658
	// crmv@201442 - moved to HolidaysUtils class
	
	/**
	 * @deprecated Please use the function in HolidaysUtils class
	 */
	static function getHolidaysForYear($year, $country = 'IT') {
		logDeprecated('The method CRMVUtils::getHolidaysForYear has been replaced by HolidaysUtils::getHolidaysForYear');
		return HolidaysUtils::getHolidaysForYear($year, $country);
	}

	/**
	 * @deprecated Please use the function in HolidaysUtils class
	 */
	function getHolidays($from = '', $to = '', $return_mode=null, $country = 'IT') {
		logDeprecated('The method CRMVUtils::getHolidays has been replaced by HolidaysUtils::getHolidays');
		$HU = HolidaysUtils::getInstance();
		return $HU->getHolidays($from, $to, $return_mode, $country);
	}

	/**
	 * @deprecated Please use the function in HolidaysUtils class
	 */
	function number_of_working_days($from, $to) {
		logDeprecated('The method CRMVUtils::number_of_working_days has been replaced by HolidaysUtils::number_of_working_days');
		$HU = HolidaysUtils::getInstance();
		return $HU->number_of_working_days($from, $to);
	}
	// crmv@201442e
	
	//crmv@113771 crmv@128159
	/**
	 * convert a value from the display format to the database format
	 */
	function formatValue($value, $field, &$form=null) {
		global $current_user, $currentModule;
		
		if (SDK::isUitype($field['uitype'])) {
			$sdk_file = SDK::getUitypeFile('php','formatvalue',$field['uitype']);
			if ($sdk_file != '') {
				include($sdk_file);
			}
		} else {
			switch ($field['uitype']) {
				case 56:
					//crmv@123745
					if (is_numeric($value)) $value = intval($value);
					($value === 'on' || $value === 1) ? $value = '1' : $value = '0';
					//crmv@123745e
					break;
				case 33:
					if (is_array($value)) $value = implode(' |##| ',$value);
					break;
				case 5:
				case 23: //crmv@158241
					if (isset($current_user->date_format)) $value = getValidDBInsertDateValue($value);
					$value = adjustTimezone($value, 0, null, true);
					break;
				case 71:
					$value = parseUserNumber($value);
					$currency_id = $current_user->currency_id;
					$curSymCrate = getCurrencySymbolandCRate($currency_id);
					$value = convertToDollar($value, $curSymCrate['rate']);
					break;
				case 7:
					$value = parseUserNumber($value);
					$value = str_replace(",","",$value);
					break;
			}
		}
		if ($value === null) $value = '';
		return $value;
	}
	//crmv@113771e crmv@128159e

	// crmv@192843
	function getTranslatedModuleFirstLetter($module) {
		return strtoupper(substr(getTranslatedString($module,$module),0,1));
	}
	// crmv@192843e

	// crmv@195213
	function getNewCFPrefix() {
		$VP = VTEProperties::getInstance();
		$length = $VP->get('performance.cf_prefix_length');
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$randomString = '';
		if ($length > 0) {
			for ($i = 0; $i < $length; $i++) {
				$index = rand(0, strlen($characters) - 1);
				$randomString .= $characters[$index];
			}
		}
		return $randomString;
	}
	function writeCFPrefix() {
		$cf_prefix = self::getNewCFPrefix();
		$configInc = file_get_contents('config.inc.php');
		if (empty($configInc)) {
			return false;
		} else {
			if (strpos($configInc, '$cf_prefix') === false) {
				// alter config inc
				$configInc = str_replace('?>', "// custom field prefix\n\$cf_prefix = '$cf_prefix';\n\n?>", $configInc);
			} else {
				$configInc = preg_replace('/^\$cf_prefix.*$/m', "\$cf_prefix = '$cf_prefix';", $configInc);
			}
			if (is_writable('config.inc.php')) {
				file_put_contents('config.inc.php', $configInc);
			} else {
				return false;
			}
		}
		return true;
	}
	// crmv@195213e

	// crmv@181170
	public static function getEnterpriseLogo($mode, $path = '') {
		$logoImg = '';
		
		// Define this function (SDK::setUtil) to override the logo with anything
		if (function_exists('get_logo_override')) {
			$logoImg = get_logo_override($mode);
		} else {
			global $enterprise_project;
			if (!empty($enterprise_project)) $logoImg = '<img src="'.$path.get_logo($mode).'" border="0">';
		}

		return $logoImg;
	}

	public static function getApplicationTheme() {
		global $theme, $default_theme;
		
		if (empty($theme)) {
			$theme = $default_theme;
		}
		
		return $theme;
	}
	// crmv@181170e
	
	// crmv@202301
	/**
	 * Return a list of time intervals to be used in reports/filters/...
	 * The object can easily be passed to js to implement client side population
	 * @param Array $options Options to control which interval to return. Valid options are:
	 *		'display_dates' => true/false	If true, get the display dates in the result
	 *		'labels' => true/false			If true, return labels for intervals
	 *		'dates' => Array				Which dates to return (default all)
	 */
	public function getTimeIntervals(Array $options = []) {
		global $current_user; // crmv@150808
		
		// default options
		$options = array_replace([
			'display_dates' => false,
			'labels' => false,
			'show_custom' => true,
			'dates' => ['past', 'future', 'until_today', 'from_today', 'around_today'],
		], $options);
		
		static $dayNames = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		
		$thisYear = date('Y');
		$thisMonth = date('m');
		$thisDay = date('d');
	
		// get first and last day of week
		$weekstart = $current_user->weekstart;
		if ($weekstart === null || $weekstart === '') $weekstart = 1;
		$weekstart = intval($weekstart);
		$weekend = ($weekstart + 6) % 7;
		// crmv@150808e

		$today = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay, $thisYear));
		$tomorrow  = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+1, $thisYear));
		$yesterday  = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-1, $thisYear));

		$currentmonth0 = date("Y-m-d",mktime(0, 0, 0, $thisMonth, "01",   $thisYear));
		$currentmonth1 = date("Y-m-t");
		$lastmonth0 = date("Y-m-d",mktime(0, 0, 0, $thisMonth-1, "01",   $thisYear));
		//crmv@50067
		//$lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
		$lastmonth1 = date("Y-m-t", strtotime($lastmonth0));
		//crmv@50067e
		$nextmonth0 = date("Y-m-d",mktime(0, 0, 0, $thisMonth+1, "01",   $thisYear));
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

		$next7days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+6, $thisYear));
		$next30days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+29, $thisYear));
		$next60days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+59, $thisYear));
		$next90days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+89, $thisYear));
		$next120days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay+119, $thisYear));

		$last7days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-6, $thisYear));
		$last30days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-29, $thisYear));
		$last60days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-59, $thisYear));
		$last90days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-89, $thisYear));
		$last120days = date("Y-m-d",mktime(0, 0, 0, $thisMonth  , $thisDay-119, $thisYear));

		$currentFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   $thisYear));
		$currentFY1 = date("Y-m-t",mktime(0, 0, 0, "12", $thisDay,   $thisYear));
		$lastFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   $thisYear-1));
		$lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", $thisDay, $thisYear-1));
		$nextFY0 = date("Y-m-d",mktime(0, 0, 0, "01", "01",   $thisYear+1));
		$nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", $thisDay, $thisYear+1));

		if($thisMonth <= 3) {
			$cFq = date("Y-m-d",mktime(0, 0, 0, "01","01",$thisYear));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",$thisYear));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "04","01",$thisYear));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",$thisYear));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "10","01",$thisYear-1));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",$thisYear-1));
		} else if($thisMonth > 3 and $thisMonth <= 6) {
			$pFq = date("Y-m-d",mktime(0, 0, 0, "01","01",$thisYear));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",$thisYear));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "04","01",$thisYear));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",$thisYear));
			$nFq = date("Y-m-d",mktime(0, 0, 0, "07","01",$thisYear));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",$thisYear));
		} else if($thisMonth > 6 and $thisMonth <= 9) {
			$nFq = date("Y-m-d",mktime(0, 0, 0, "10","01",$thisYear));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",$thisYear));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "04","01",$thisYear));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "06","30",$thisYear));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "07","01",$thisYear));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",$thisYear));
		} else if($thisMonth > 9 and $thisMonth <= 12) {
			$nFq = date("Y-m-d",mktime(0, 0, 0, "01","01",$thisYear+1));
			$nFq1 = date("Y-m-d",mktime(0, 0, 0, "03","31",$thisYear+1));
			$pFq = date("Y-m-d",mktime(0, 0, 0, "07","01",$thisYear));
			$pFq1 = date("Y-m-d",mktime(0, 0, 0, "09","30",$thisYear));
			$cFq = date("Y-m-d",mktime(0, 0, 0, "10","01",$thisYear));
			$cFq1 = date("Y-m-d",mktime(0, 0, 0, "12","31",$thisYear));
		}
		
		$dates = [
			'today' => ['from' => $today, 'to' => $today, 'types' => ['until_today', 'from_today', 'around_today']],
			'yesterday' => ['from' => $yesterday, 'to' => $yesterday, 'types' => ['past']],
			'tomorrow' => ['from' => $tomorrow, 'to' => $tomorrow, 'types' => ['future']],
			
			'thisweek' => ['from' => $thisweek0, 'to' => $thisweek1, 'types' => ['around_today']],
			'lastweek' => ['from' => $lastweek0, 'to' => $lastweek1, 'types' => ['past']],
			'nextweek' => ['from' => $nextweek0, 'to' => $nextweek1, 'types' => ['future']],
			
			'thismonth' => ['from' => $currentmonth0, 'to' => $currentmonth1, 'types' => ['around_today']],
			'lastmonth' => ['from' => $lastmonth0, 'to' => $lastmonth1, 'types' => ['past']],
			'nextmonth' => ['from' => $nextmonth0, 'to' => $nextmonth1, 'types' => ['future']],
			
			'next7days' => ['from' => $today, 'to' => $next7days, 'types' => ['from_today']],
			'next30days' => ['from' => $today, 'to' => $next30days, 'types' => ['from_today']],
			'next60days' => ['from' => $today, 'to' => $next60days, 'types' => ['from_today']],
			'next90days' => ['from' => $today, 'to' => $next90days, 'types' => ['from_today']],
			'next120days' => ['from' => $today, 'to' => $next120days, 'types' => ['from_today']],
			
			'last7days' => ['from' => $last7days, 'to' => $today, 'types' => ['until_today']],
			'last30days' => ['from' => $last30days, 'to' => $today, 'types' => ['until_today']],
			'last60days' => ['from' => $last60days, 'to' => $today, 'types' => ['until_today']],
			'last90days' => ['from' => $last90days, 'to' => $today, 'types' => ['until_today']],
			'last120days' => ['from' => $last120days, 'to' => $today, 'types' => ['until_today']],
			
			'thisfy' => ['from' => $currentFY0, 'to' => $currentFY1, 'types' => ['around_today']],
			'prevfy' => ['from' => $lastFY0, 'to' => $lastFY1, 'types' => ['past']],
			'nextfy' => ['from' => $nextFY0, 'to' => $nextFY1, 'types' => ['future']],
			
			'thisfq' => ['from' => $cFq, 'to' => $cFq1, 'types' => ['around_today']],
			'prevfq' => ['from' => $pFq, 'to' => $pFq1, 'types' => ['past']],
			'nextfq' => ['from' => $nFq, 'to' => $nFq1, 'types' => ['future']]
		];
		
		if ($options['show_custom']) {
			$dates = array_merge([
				'custom' => ['from' => '', 'to' => '']
			], $dates);
		}
		
		// filter out the not needed ones
		if (is_array($options['dates'])) {
			foreach ($dates as $k => $d) {
				if ($k != 'custom' && count(array_intersect($options['dates'], $d['types'])) == 0) unset($dates[$k]);
			}
		}
		
		// add display date
		if ($options['display_dates']) {
			foreach ($dates as $k => &$d) {
				if ($k == 'custom') {
					$d['from_display'] = '';
					$d['to_display'] = '';
				} else {
					$d['from_display'] = getDisplayDate($d['from']);
					$d['to_display'] = getDisplayDate($d['to']);
				}
			}
		}
		
		// add labels
		if ($options['labels']) {
			global $current_language;
			$rep_strings = return_module_language($current_language, 'Reports');
			$labels = Array(
				"custom" => $rep_strings['Custom'],
				"prevfy" => $rep_strings['Previous FY'],
				"thisfy" => $rep_strings['Current FY'],
				"nextfy" => $rep_strings['Next FY'],
				"prevfq" => $rep_strings['Previous FQ'],
				"thisfq" => $rep_strings['Current FQ'],
				"nextfq" => $rep_strings['Next FQ'],
				"yesterday" => $rep_strings['Yesterday'],
				"today" => $rep_strings['Today'],
				"tomorrow" => $rep_strings['Tomorrow'],
				"lastweek" => $rep_strings['Last Week'],
				"thisweek" => $rep_strings['Current Week'],
				"nextweek" => $rep_strings['Next Week'],
				"lastmonth" => $rep_strings['Last Month'],
				"thismonth" => $rep_strings['Current Month'],
				"nextmonth" => $rep_strings['Next Month'],
				"last7days" => $rep_strings['Last 7 Days'],
				"last30days" => $rep_strings['Last 30 Days'],
				"last60days" => $rep_strings['Last 60 Days'],
				"last90days" => $rep_strings['Last 90 Days'],
				"last120days" => $rep_strings['Last 120 Days'],
				"next7days" => $rep_strings['Next 7 Days'], // crmv@60091
				"next30days" => $rep_strings['Next 30 Days'],
				"next60days" => $rep_strings['Next 60 Days'],
				"next90days" => $rep_strings['Next 90 Days'],
				"next120days" => $rep_strings['Next 120 Days'],
			);
			foreach ($dates as $k => &$d) {
				$d['label'] = $labels[$k];
			}
		}
		
		return $dates;
	}
	// crmv@202301e
	
}

// functions' shortcuts
function getJSGlobalVars() { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function isVteDesktop() { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); } //crmv@42707
function replaceStringTemplate() { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); }
function getCrmvDivHtml() { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); } // crmv@44323
// crmv@42024e
function getUserColorDb() { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); }	//crmv@56580
function getNewModuleLabel($module) { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); }	//crmv@59091
function getTranslatedModuleFirstLetter($module) { return CRMVUtils::callMethodByName(__FUNCTION__, func_get_args()); }	// crmv@192843

// crmv@105538 - moved code

// crmv@187365
function logDeprecated($msg = '') {
	global $vtlib_Utils_Log;//crmv@208038
	
	static $logger = null;
	if (is_null($logger)) {
		$logger = new VTEFileLogger(array(
			'file' => 'logs/deprecated.log',
			'rotate' => false,
		));
	}
	
	// get backtrace in a more compact format
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	array_shift($trace); // remove myself
	$fn = $trace[0]['function'];
	$traceStr = "TRACE:\n";
	$i = 1;
	$trace = array_reverse($trace);
	foreach ($trace as $t) {
		if ($t['class']) {
			$traceStr .= "$i. {$t['class']}{$t['type']}{$t['function']}() at {$t['file']}, line {$t['line']}\n";
		} else {
			$traceStr .= "$i. {$t['function']}() at {$t['file']}, line {$t['line']}\n";
		}
		++$i;
	}

	// prepare the message
	$msg = "Deprecated function called: ".$fn."\n".($msg ? $msg."\n" : '');

	// enable this to show warnings
	if ($vtlib_Utils_Log) {
		trigger_error($msg, E_USER_DEPRECATED);
	}
	
	$msg .= $traceStr."\n";
	
	$logger->info($msg);
}
// crmv@187365e

//crm@7634
// return picklist on user array (for listview)
function getUserOptionsHTML($selected_user_id,$module_name,$parenttab,$file='',$modhomeid='') {	//crmv@OPER6288 crmv@141557

	global $current_user,$app_strings;
	$is_admin = is_admin($current_user);
	$tab_id = getTabid($module_name);
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	//crmv@28496
	if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[$tab_id] == 3)
	{
		$users_array = get_user_array(FALSE, "Active", $current_user->id,'private','Read');
	}
	else
	{
		$users_array = get_user_array(FALSE, "Active", $current_user->id,'','Read');
	}
	//crmv@28496e
	//crmv@18592 crmv@OPER6288 crmv@141557
	$htmlStr = "<select name='lv_user_id' class='detailedViewTextBox' id='lv_user_id' onChange='showDefaultCustomView(null,\"$module_name\",\"$parenttab\", \"{$_REQUEST['folderid']}\",\"{$file}\",\"{$modhomeid}\");'>"; // crmv@30967
	//crmv@18592e crmv@OPER6288e crmv@141557e
	if($selected_user_id == 'all') //crmv@167234
		$htmlStr .= "<option value='all' selected>".$app_strings['LBL_ASSIGNED_TO_ALL']."</option>'";
	else    $htmlStr .= "<option value='all' >".$app_strings['LBL_ASSIGNED_TO_ALL']."</option>";

	if($selected_user_id == 'mine') //crmv@167234
		$htmlStr .= "<option value='mine' selected>".$app_strings['LBL_ASSIGNED_TO_ME']."</option>";
	else    $htmlStr .= "<option value='mine' >".$app_strings['LBL_ASSIGNED_TO_ME']."</option>";

	if($selected_user_id == 'others') //crmv@167234
		$htmlStr .= "<option value='others' selected>".$app_strings['LBL_ASSIGNED_TO_OTHERS']."</option>";
	else	$htmlStr .= "<option value='others' >".$app_strings['LBL_ASSIGNED_TO_OTHERS']."</option>";

	foreach($users_array as $id=>$username)
	{
		if($id == $selected_user_id)
			$htmlStr .= "<option value='".$id."' selected>".$username."</option>";
		else
			$htmlStr .= "<option value='".$id."' >".$username."</option>";
	}

	$htmlStr .= "</select>";
	return $htmlStr;
}
//crm@7634e

//crmv@7221
/** Function to get the Advanced Sharing rule Info
 *  @param $shareId -- Sharing Rule Id
 *  @returns Sharing Rule Information Array in the following format:
 *    $shareRuleInfoArr=Array($shareId, $module_name, $type, $title, $desciption, $conditions);
 */
function getAdvSharingRuleInfo($shareId)
{
	global $log;
	$log->debug("Entering getAdvSharingRuleInfo(".$shareId.") method ...");
	global $adb;
	$shareRuleInfoArr=Array();
	$query = "select tbl_s_advancedrule.* from tbl_s_advancedrule
		 where tbl_s_advancedrule.advrule_id=?";
	$result=$adb->pquery($query, array($shareId));
	//Retreving the Sharing Tabid
	$module_name=$adb->query_result($result,0,'module_name');
	$title=$adb->query_result($result,0,'title');
	$desciption=$adb->query_result($result,0,'description');

	//Constructing the Array
	$shareRuleInfoArr['shareid']=$shareId;
	$shareRuleInfoArr['module']=$module_name;
	$shareRuleInfoArr['title']=$title;
	$shareRuleInfoArr['description']=$desciption;

	$log->debug("Exiting getAdvSharingRuleInfo method ...");
	return $shareRuleInfoArr;



}

      /** to get the Advanced filter criteria
	* @param $selected :: Type String (optional)
	* @returns  $AdvCriteria Array in the following format
	* $AdvCriteria = Array( 0 => array('value'=>$tablename:$colname:$fieldname:$fieldlabel,'text'=>$mod_strings[$field label],'selected'=>$selected),
	* 		     1 => array('value'=>$$tablename1:$colname1:$fieldname1:$fieldlabel1,'text'=>$mod_strings[$field label1],'selected'=>$selected),
	*		                             		|
	* 		     n => array('value'=>$$tablenamen:$colnamen:$fieldnamen:$fieldlabeln,'text'=>$mod_strings[$field labeln],'selected'=>$selected))
	*/
function getAdvRuleCriteriaHTML($selected="")
{
	global $app_list_strings;
	$customView = CRMEntity::getInstance('CustomView');
	$adv_filter_options = $customView->getAdvFilterOptions();	//crmv@26161
	$AdvCriteria = array();
	foreach($adv_filter_options as $key=>$value)
	{
		if($selected == $key)
		{
			$advfilter_criteria['value'] = $key;
			$advfilter_criteria['text'] = $value;
			$advfilter_criteria['selected'] = "selected";
		}else
		{
			$advfilter_criteria['value'] = $key;
			$advfilter_criteria['text'] = $value;
			$advfilter_criteria['selected'] = "";
		}
		$AdvCriteria[] = $advfilter_criteria;
	}

	return $AdvCriteria;
}

	/** to get the Advanced filter for the given customview Id
	  * @param $cvid :: Type Integer
	  * @returns  $stdfilterlist Array in the following format
	  * $stdfilterlist = Array( 0=>Array('columnname' =>  $tablename:$columnname:$fieldname:$module_$fieldlabel,'comparator'=>$comparator,'value'=>$value),
	  *			    1=>Array('columnname' =>  $tablename1:$columnname1:$fieldname1:$module_$fieldlabel1,'comparator'=>$comparator1,'value'=>$value1),
	  *		   			|
	  *			    4=>Array('columnname' =>  $tablename4:$columnname4:$fieldname4:$module_$fieldlabel4,'comparator'=>$comparatorn,'value'=>$valuen),
	  */
function getAdvRuleFilterByRuleid($id,$only_columns = false)
	{
		global $adb;
		global $modules;

		$sSQL = "select tbl_s_advancedrulefilters.* from tbl_s_advancedrulefilters inner join tbl_s_advancedrule on tbl_s_advancedrulefilters.advrule_id = tbl_s_advancedrule.advrule_id";
		$sSQL .= " where tbl_s_advancedrulefilters.advrule_id=?";
		$result = $adb->pquery($sSQL, array($id));

		while($advfilterrow = $adb->fetch_array($result))
		{
			if ($only_columns){
				if ($advfilterrow["columnname"] != null){
					$advfilterlist[] = $advfilterrow["columnname"];
				}
			}
			else {
				$advft["columnname"] = $advfilterrow["columnname"];
				$advft["comparator"] = $advfilterrow["comparator"];
				$advft["value"] = $advfilterrow["value"];
				$advfilterlist[] = $advft;
			}
		}
		return $advfilterlist;
	}

	/** to get the custom columns for the given module and columnlist
  * @param $module (modulename):: type String
  * @param $columnslist (Module columns list):: type Array
  * @param $selected (selected or not):: type String (Optional)
  * @returns  $advfilter_out array in the following format
  *	$advfilter_out = Array ('BLOCK1 NAME'=>
  * 					Array(0=>
  *						Array('value'=>$tablename:$colname:$fieldname:$fieldlabel:$typeofdata,
  *						      'text'=>$fieldlabel,
  *					      	      'selected'=><selected or ''>),
  *			      		      1=>
  *						Array('value'=>$tablename1:$colname1:$fieldname1:$fieldlabel1:$typeofdata1,
  *						      'text'=>$fieldlabel1,
  *					      	      'selected'=><selected or ''>)
  *					      ),
  *								|
  *								|
  *					      n=>
  *						Array('value'=>$tablenamen:$colnamen:$fieldnamen:$fieldlabeln:$typeofdatan,
  *						      'text'=>$fieldlabeln,
  *					      	      'selected'=><selected or ''>)
  *					      ),
  *				'BLOCK2 NAME'=>
  * 					Array(0=>
  *						Array('value'=>$tablename:$colname:$fieldname:$fieldlabel:$typeofdata,
  *						      'text'=>$fieldlabel,
  *					      	      'selected'=><selected or ''>),
  *			      		      1=>
  *						Array('value'=>$tablename1:$colname1:$fieldname1:$fieldlabel1:$typeofdata1,
  *						      'text'=>$fieldlabel1,
  *					      	      'selected'=><selected or ''>)
  *					      )
  *								|
  *								|
  *					      n=>
  *						Array('value'=>$tablenamen:$colnamen:$fieldnamen:$fieldlabeln:$typeofdatan,
  *						      'text'=>$fieldlabeln,
  *					      	      'selected'=><selected or ''>)
  *					      ),
  *
  *					||
  *					||
  *				'BLOCK_N NAME'=>
  * 					Array(0=>
  *						Array('value'=>$tablename:$colname:$fieldname:$fieldlabel:$typeofdata,
  *						      'text'=>$fieldlabel,
  *					      	      'selected'=><selected or ''>),
  *			      		      1=>
  *						Array('value'=>$tablename1:$colname1:$fieldname1:$fieldlabel1:$typeofdata1,
  *						      'text'=>$fieldlabel1,
  *					      	      'selected'=><selected or ''>)
  *					      )
  *								|
  *								|
  *					      n=>
  *						Array('value'=>$tablenamen:$colnamen:$fieldnamen:$fieldlabeln:$typeofdatan,
  *						      'text'=>$fieldlabeln,
  *					      	      'selected'=><selected or ''>)
  *					      ),

  *
  */

function getByModudddle_ColumnsHTML($module,$columnslist,$selected="")
{
	global $oCustomView, $current_language;
	global $app_list_strings;
	$advfilter = array();
	$mod_strings = return_specified_module_language($current_language,$module);

	$check_dup = Array();
	foreach($oCustomView->module_list[$module] as $key=>$value)
	{
		$advfilter = array();
		$label = $key;
		if(isset($columnslist[$module][$key]))
		{
			foreach($columnslist[$module][$key] as $field=>$fieldlabel)
			{
				if(!in_array($fieldlabel,$check_dup))
				{
					if(isset($mod_strings[$fieldlabel]))
					{
						if($selected == $field)
						{
							$advfilter_option['value'] = $field;
							$advfilter_option['text'] = $mod_strings[$fieldlabel];
							$advfilter_option['selected'] = "selected";
						}else
						{
							$advfilter_option['value'] = $field;
							$advfilter_option['text'] = $mod_strings[$fieldlabel];
							$advfilter_option['selected'] = "";
						}
					}else
					{
						if($selected == $field)
						{
							$advfilter_option['value'] = $field;
							$advfilter_option['text'] = $fieldlabel;
							$advfilter_option['selected'] = "selected";
						}else
						{
							$advfilter_option['value'] = $field;
							$advfilter_option['text'] = $fieldlabel;
							$advfilter_option['selected'] = "";
						}
					}
					$advfilter[] = $advfilter_option;
					$check_dup [] = $fieldlabel;
				}
			}
			$advfilter_out[$label]= $advfilter;
		}
	}

	$finalfield = Array();
	foreach($advfilter_out as $header=>$value)
	{
		if($header == $mod_strings['LBL_TASK_INFORMATION'])
		{
			$newLabel = $mod_strings['LBL_CALENDAR_INFORMATION'];
		    	$finalfield[$newLabel] = $advfilter_out[$header];

		}
		elseif($header == $mod_strings['LBL_EVENT_INFORMATION'])
		{
			$index = count($finalfield[$newLabel]);
			foreach($value as $key=>$result)
			{
				$finalfield[$newLabel][$index]=$result;
				$index++;
			}
		}
		else
		{
			$finalfield = $advfilter_out;
		}

		$advfilter_out=$finalfield;
	}
	return $advfilter_out;
}


	/** to get the customview AdvancedFilter Query for the given customview Id
  * @param $cvid :: Type Integer
  * @returns  $advfiltersql as a string
  * This function will return the advanced filter criteria for the given customfield
  *
  */
function getAdvRuleFilterSQL($cvid,$cv,$user)
{
	global $current_user, $table_prefix;
	$advfilter = getAdvRuleFilterByRuleid($cvid);
	if(isset($advfilter))
	{
		foreach($advfilter as $key=>$advfltrow)
		{
			if(isset($advfltrow))
			{
				$columns = explode(":",$advfltrow["columnname"]);
				$datatype = (isset($columns[4])) ? $columns[4] : "";
				if($advfltrow["columnname"] != "" && $advfltrow["comparator"] != "")
				{

					$valuearray = explode(",",trim($advfltrow["value"]));
					if(isset($valuearray) && count($valuearray) > 1)
					{
						$advorsql = array(); // crmv@182070
						for($n=0;$n<count($valuearray);$n++)
						{
							if (!strncasecmp($valuearray[$n],'$current_user->',14)){
								//sto usando un parametro dell'utente, quindi lo estraggo
								$val = substr($valuearray[$n], 18);
								$valuearray[$n] = $user->$val;
							}
							$advorsql[] = $cv->getRealValues($columns[0],$columns[1],$advfltrow["comparator"],trim($valuearray[$n]),$datatype,$user->id);	//crmv@63872
						}
						//If negative logic filter ('not equal to', 'does not contain') is used, 'and' condition should be applied instead of 'or'
						if($advfltrow["comparator"] == 'n' || $advfltrow["comparator"] == 'k')
							$advorsqls = implode(" and ",$advorsql);
						else
							$advorsqls = implode(" or ",$advorsql);
						$advfiltersql[] = " (".$advorsqls.") ";
					}else
					{
						if (!strncasecmp($advfltrow["value"],'$current_user->',14)){
							//sto usando un parametro dell'utente, quindi lo estraggo
							$val = substr($advfltrow["value"], 18);
							$advfltrow["value"] = $user->$val;
						}
						//Added for getting vte_activity Status -Jaguar
						if($cv->customviewmodule == "Calendar" && ($columns[1] == "status" || $columns[1] == "eventstatus"))
						{
							if(getFieldVisibilityPermission("Calendar", $current_user->id,'taskstatus') == '0')
							{
								$advfiltersql[] = "case when (".$table_prefix."_activity.status not like '') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end".$cv->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
							}
							else
								$advfiltersql[] = $table_prefix."_activity.eventstatus".$cv->getAdvComparator($advfltrow["comparator"],trim($advfltrow["value"]),$datatype);
						}
						else
						{
							$advfiltersql[] = $cv->getRealValues($columns[0],$columns[1],$advfltrow["comparator"],trim($advfltrow["value"]),$datatype,$user->id);	//crmv@63872
						}
					}
				}
			}
		}
	}
	if(isset($advfiltersql))
	{
		$advfsql = implode(" and ",$advfiltersql);
	}
	return $advfsql;
}

/** This function is to delete the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  */
function deleteAdvSharingRule($shareid)
{
	global $log;
	$log->debug("Entering deleteAdvSharingRule(".$shareid.") method ...");
	global $adb;
	$query3="delete from tbl_s_advancedrule where advrule_id=?";
	$adb->pquery($query3, array($shareid));
	$query4="delete from tbl_s_advancedrulefilters where advrule_id=?";
	$adb->pquery($query4, array($shareid));
	$log->debug("Exiting deleteAdvSharingRule method ...");

}

/** returns the list of sharing rules for the specified module
  * @param $module -- Module Name:: Type varchar
  * @returns $access_permission -- sharing rules list info array:: Type array
  *
 */
function getAdvSharingRuleList($module)
{
	global $adb,$mod_strings;
		$query = "select tbl_s_advancedrule.* from tbl_s_advancedrule
		 where tbl_s_advancedrule.module_name=?";
		$result=$adb->pquery($query, array($module));
		$num_rows=$adb->num_rows($result);
		for($j=0;$j<$num_rows;$j++)
		{
			$advrule_id=$adb->query_result($result,$j,"advrule_id");
			$title=$adb->query_result($result,$j,"title");
			$description=$adb->query_result($result,$j,"description");
			$permission=$adb->query_result($result,$j,"permission");

			$access_permission [] = $advrule_id;
			$access_permission [] = $title;
			$access_permission [] = $description;
			$access_permission [] = $permission;
		}

	if(is_array($access_permission))
		$access_permission = array_chunk($access_permission,4);
	return $access_permission;
}


/** returns the list of sharing rules for the specified module
  * @param $module -- Module Name:: Type varchar
  * @returns $access_permission -- sharing rules list info array:: Type array
  *
 */
function getAdvSharingRulePerm($advrule_id,$entity_type,$id)
{
	global $adb,$mod_strings;
		$query = "select tbl_s_advancedrule.title,tbl_s_advancedrule.module_name,tbl_s_advancedrule.title,tbl_s_advancedrule.description,
		tbl_s_advancedrule_rel.permission from tbl_s_advancedrule
		inner join tbl_s_advancedrule_rel on tbl_s_advancedrule_rel.advrule_id = tbl_s_advancedrule.advrule_id
		 where tbl_s_advancedrule.advrule_id = ? and entity_type=? and id =?";
		$result=$adb->pquery($query, array($advrule_id,$entity_type,$id));
		$num_rows=$adb->num_rows($result);
		$access_permission = array();
		if ($num_rows == 1){
			$title=$adb->query_result($result,0,"title");
			$module=$adb->query_result($result,0,"module_name");
			$description=$adb->query_result($result,0,"description");
			$permission=$adb->query_result($result,0,"permission");

			$access_permission[] = $advrule_id;
			$access_permission[] = $module;
			$access_permission[] = $title;
			$access_permission[] = $description;
			$access_permission[] = $permission;
		}
	return $access_permission;
}

/** returns the list of sharing rules for the specified module
  * @param $module -- Module Name:: Type varchar
  * @returns $access_permission -- sharing rules list info array:: Type array
  *
 */
function getAllAdvSharingRulePerm($module,$id)
{
	global $adb,$mod_strings;
		$query = "select tbl_s_advancedrule.advrule_id,tbl_s_advancedrule.title,tbl_s_advancedrule.description,
		tbl_s_advancedrule_rel.permission from tbl_s_advancedrule
		left join tbl_s_advancedrule_rel on tbl_s_advancedrule_rel.advrule_id = tbl_s_advancedrule.advrule_id
		 where tbl_s_advancedrule.module_name=?  and (id is null or id <> ?)";
		$result=$adb->pquery($query, array($module,$id));
		$num_rows=$adb->num_rows($result);
		for($j=0;$j<$num_rows;$j++)
		{
			$advrule_id=$adb->query_result($result,$j,"advrule_id");
			$title=$adb->query_result($result,$j,"title");
			$description=$adb->query_result($result,$j,"description");
			$permission=$adb->query_result($result,$j,"permission");

			$access_permission [] = $advrule_id;
			$access_permission [] = $title;
			$access_permission [] = $description;
			$access_permission [] = $permission;
		}

	if(is_array($access_permission))
		$access_permission = array_chunk($access_permission,4);
	return $access_permission;
}

/** returns the list of sharing rules for the specified module
  * @param $module -- Module Name:: Type varchar
  * @returns $access_permission -- sharing rules list info array:: Type array
  *
 */
function getAdvSharingRule($module,$id)
{
	global $adb,$mod_strings;
		$query = "select tbl_s_advancedrule.*,tbl_s_advancedrule_rel.* from tbl_s_advancedrule
		inner join tbl_s_advancedrule_rel on tbl_s_advancedrule_rel.advrule_id = tbl_s_advancedrule.advrule_id
		where tbl_s_advancedrule.module_name=? and id = ?";
		$result=$adb->pquery($query, array($module,$id));
		$num_rows=$adb->num_rows($result);
		for($j=0;$j<$num_rows;$j++)
		{
			$advrule_id=$adb->query_result($result,$j,"advrule_id");
			$title=$adb->query_result($result,$j,"title");
			$description=$adb->query_result($result,$j,"description");
			$permission=$adb->query_result($result,$j,"permission");
			if ($permission == 0) $permission=$mod_strings["Read Only "];
			else $permission=$mod_strings["Read/Write"];

			$access_permission [] = $advrule_id;
			$access_permission [] = $title;
			$access_permission [] = $description;
			$access_permission [] = $permission;
		}

	if(is_array($access_permission))
		$access_permission = array_chunk($access_permission,4);
	return $access_permission;
}

/** This function is to update the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  * 	$module -- Module name - Datatype::varchar
  * 	$sharePermisson -- This can have the following values:
  *                       0 - Read Only
  *                       1 - Read/Write
  * 	$entityid -- id of the entity - Datatype::Varchar
  * 	$entity -- The Entity Type may be vt_groups,roles,rs and vte_users - Datatype::String
  * This function will return the shareid as output
  */
function updateAdvSharingRulePerm($shareid,$module,$sharePermission,$entityid,$entity)
{
	global $log;
	$log->debug("Entering updateAdvSharingRulePerm(".$shareid.",".$module.",".$sharePermission.",".$entityid.",".$entity.") method ...");
	global $adb;
	$query1="update tbl_s_advancedrule_rel
	set permission = ? where advrule_id=? and entity_type = ? and id = ?";
	$adb->pquery($query1, array($sharePermission,$shareid,$entity,$entityid));
	$log->debug("Exiting updateAdvSharingRulePerm method ...");
	return $shareid;
}

/** This function is to update the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  * 	$tabid -- Tabid of module - Datatype::integer
  * 	$sharePermisson -- This can have the following values:
  *                       0 - Read Only
  *                       1 - Read/Write
  * 	$entityid -- id of the entity - Datatype::Varchar
  * 	$entity -- The Entity Type may be vte_groups,roles,rs and vte_users - Datatype::String
  * This function will return the shareid as output
  */
function updateRelatedModuleAdvSharingRulePerm($shareid,$tabid,$sharePermission,$entityid,$entity)
{
	global $log;
	$log->debug("Entering updateRelatedModuleAdvSharingRulePerm(".$shareid.",".$tabid.",".$sharePermission.",".$entityid.",".$entity.") method ...");
	global $adb;
	$query1="update tbl_s_advrule_relmod
	set rel_permission = ? where advrule_id=? and entity_type = ? and id = ? and rel_tabid = ?";
	$adb->pquery($query1, array($sharePermission,$shareid,$entity,$entityid,$tabid));
	$log->debug("Exiting updateRelatedModuleAdvSharingRulePerm method ...");
	return $shareid;
}

/** This function is to update the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  * 	$module -- Module name - Datatype::varchar
  * 	$sharePermisson -- This can have the following values:
  *                       0 - Read Only
  *                       1 - Read/Write
  * 	$entityid -- id of the entity - Datatype::Varchar
  * 	$entity -- The Entity Type may be vte_groups,roles,rs and vte_users - Datatype::String
  * This function will return the shareid as output
  */
function addAdvSharingRulePerm($shareid,$module,$sharePermission,$entityid,$entity)
{
	global $log;
	$log->debug("Entering addAdvSharingRulePerm(".$shareid.",".$module.",".$sharePermission.",".$entityid.",".$entity.") method ...");
	global $adb;
	$query1="insert into tbl_s_advancedrule_rel (advrule_id,entity_type,id,permission)
	values (?,?,?,?)";
	$adb->pquery($query1, array($shareid,$entity,$entityid,$sharePermission));
	$log->debug("Exiting addAdvSharingRulePerm method ...");
	return $shareid;
}

/** This function is to update the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  * 	$tabid -- Tabid of module - Datatype::integer
  * 	$sharePermisson -- This can have the following values:
  *                       0 - Read Only
  *                       1 - Read/Write
  * 	$entityid -- id of the entity - Datatype::Varchar
  * 	$entity -- The Entity Type may be vte_groups,roles,rs and vte_users - Datatype::String
  * This function will return the shareid as output
  */
function addRelatedModuleAdvSharingPerm($shareid,$tabid,$sharePermission,$entityid,$entity)
{
	global $log;
	$log->debug("Entering addRelatedModuleAdvSharingPerm(".$shareid.",".$tabid.",".$sharePermission.",".$entityid.",".$entity.") method ...");
	global $adb;
	$query1="insert into tbl_s_advrule_relmod (advrule_id,entity_type,id,rel_tabid,rel_permission)
	values (?,?,?,?,?)";
	$adb->pquery($query1, array($shareid,$entity,$entityid,$tabid,$sharePermission));
	$log->debug("Exiting addRelatedModuleAdvSharingPerm method ...");
	return $shareid;
}

/** This function is to retreive the Related Module Sharing Permissions for the specified Sharing Rule
  * It takes the following input parameters:
  *     $shareid -- The Sharing Rule Id:: Type Integer
  *This function will return the Related Module Sharing permissions in an Array in the following format:
  *     $PermissionArray=($relatedTabid1=>$sharingPermission1,
  *			  $relatedTabid2=>$sharingPermission2,
  *					|
  *                                     |
  *                       $relatedTabid-n=>$sharingPermission-n)
  */
function getRelatedModuleAdvSharingPerm($shareid,$entity,$entityid)
{
	global $log;
	$log->debug("Entering getRelatedModuleAdvSharingPerm(".$shareid.") method ...");
	global $adb;
	$relatedSharingModulePermissionArray=Array();
	$query="select tbl_s_advrule_relmod.* from tbl_s_advrule_relmod
	where tbl_s_advrule_relmod.advrule_id=? and entity_type = ? and id = ?";
	$result=$adb->pquery($query, array($shareid,$entity,$entityid));
	$num_rows=$adb->num_rows($result);
	for($i=0;$i<$num_rows;$i++)
	{
		$relatedto_tabid=$adb->query_result($result,$i,'rel_tabid');
		$permission=$adb->query_result($result,$i,'rel_permission');
		$relatedSharingModulePermissionArray[$relatedto_tabid]=$permission;


	}
	$log->debug("Exiting getRelatedModuleAdvSharingPerm method ...");
	return $relatedSharingModulePermissionArray;

}

/** This function is to delete the organisation level sharing rule
  * It takes the following input parameters:
  *     $shareid -- Id of the Sharing Rule to be updated
  */
function deleteAdvSharingRulePerm($shareid,$id)
{
	global $log;
	$log->debug("Entering deleteAdvSharingRulePerm(".$shareid.") method ...");
	global $adb;
	$query3="delete from tbl_s_advancedrule_rel where advrule_id=? and id = ?";
	$adb->pquery($query3, array($shareid,$id));
	$query4="delete from tbl_s_advrule_relmod where advrule_id=? and id = ?";
	$adb->pquery($query4, array($shareid,$id));
	$log->debug("Exiting deleteAdvSharingRulePerm method ...");

}
function getAdvSharingRules($module,$entityid){
	global $log;
	$log->debug("Entering getAdvSharingRules(".$module.",".$entityid.") method ...");
	global $adb;
	$query="select tbl_s_advancedrule.advrule_id,permission from tbl_s_advancedrule
	inner join tbl_s_advancedrule_rel on tbl_s_advancedrule_rel.advrule_id = tbl_s_advancedrule.advrule_id
	where id = ? and module_name =?";
	$result=$adb->pquery($query, array($entityid,$module));
	$num_rows=$adb->num_rows($result);
	$ret_array=null;
	for($i=0;$i<$num_rows;$i++)
	{
		$ruleid=$adb->query_result($result,$i,'advrule_id');
		$permission=$adb->query_result($result,$i,'permission');
		$ret_array[$ruleid]=$permission;
	}
	$log->debug("Exiting getAdvSharingRules method ...");
	return $ret_array;
}

function getAdvRelatedSharingRules($module,$relmodule,$entityid){
	global $log;
	$log->debug("Entering getAdvSharingRules(".$module.",".$entityid.") method ...");
	global $adb;
	$reltabid = getTabid($relmodule);
	$query="select tbl_s_advancedrule.advrule_id,tbl_s_advrule_relmod.rel_permission
			from tbl_s_advancedrule
			inner join tbl_s_advrule_relmod on tbl_s_advrule_relmod.advrule_id = tbl_s_advancedrule.advrule_id
			where id =? and module_name = ? and rel_tabid = ?";
	$result=$adb->pquery($query, array($entityid,$module,$reltabid));
	$num_rows=$adb->num_rows($result);
	$ret_array=null;
	for($i=0;$i<$num_rows;$i++)
	{
		$ruleid=$adb->query_result($result,$i,'advrule_id');
		$permission=$adb->query_result($result,$i,'rel_permission');
		$ret_array[$ruleid]=$permission;
	}
	$log->debug("Exiting getAdvSharingRules method ...");
	return $ret_array;
}

function get_advanced_query($adv_rule_arr,$cv,$user){
	if (is_array($adv_rule_arr) && $adv_rule_arr != null){
		$res["listview_before"]="'(";
		$res["read_before"]="' and (";
		$res["write_before"]="' and (";
		$res["listview_after"]=")'";
		$res["read_after"]=")'";
		$res["write_after"]=")'";
		$columns = array();		//crmv@22638
		foreach ($adv_rule_arr as $ruleid => $permission){
			$result=addslashes(getAdvRuleFilterSQL($ruleid,$cv,$user));
			//crmv@22638
			$columns_tmp = getAdvRuleFilterByRuleid($ruleid,true);
			foreach ($columns_tmp as $column_tmp) {
				$columns[] = $column_tmp;
			}
			//crmv@22638e
			$res["listview"].=$result." or ";
			if ($permission == 1){
				$w=true;
				$res["read"].=$result." or ";
				$res["write"].=$result." or ";
			}
			else {
				$res["read"].=$result." or ";
			}
		}
		$res["columns"]= Zend_Json::encode($columns);	//crmv@22638
		$res["listview"]=substr($res["listview"], 0, -4);
		$res["listview"] = $res["listview_before"].$res["listview"].$res["listview_after"];
		if ($w){
			$res["read"]=substr($res["read"], 0, -4);
			$res["read"] = $res["read_before"].$res["read"].$res["read_after"];
			$res["write"]=substr($res["write"], 0, -4);
			$res["write"] = $res["write_before"].$res["write"].$res["write_after"];
		}
		else {
			$res["read"]=substr($res["read"], 0, -4);
			$res["read"] = $res["read_before"].$res["read"].$res["read_after"];
			$res["write"]="''";
		}
	}
	else {
		$res["listview"]="''";
		$res["read"]="''";
		$res["write"]="''";
	}
	return $res;
}

/** This function is to retreive the list of related sharing modules for the specifed module
  * It takes the following input parameters:
  *     $tabid -- The module tabid:: Type Integer
  */

function getRelatedAdvSharingModules($tabid)
{
	global $log;
	$log->debug("Entering getRelatedAdvSharingModules(".$tabid.") method ...");
	global $adb;
	$relatedSharingModuleArray=Array();
	$query="select * from tbl_s_advrule_relmodlist where tabid=?";
	$result=$adb->pquery($query, array($tabid));
	$num_rows=$adb->num_rows($result);
	for($i=0;$i<$num_rows;$i++)
	{
		$ds_relmod_id=$adb->query_result($result,$i,'datashare_relatedmodule_id');
		$rel_tabid=$adb->query_result($result,$i,'relatedto_tabid');
		$relatedSharingModuleArray[$rel_tabid]=$ds_relmod_id;

	}
	$log->debug("Exiting getRelatedAdvSharingModules method ...");
	return $relatedSharingModuleArray;

}
//crmv@7221e

//crmv@8398
function getCalendarType($type,$history=''){
	global $adb,$current_user,$is_admin;
	if ($history == 'history') $history=1;
	else $history = 0;
	$config['event'] = Array('field'=>'activitytype','status_field'=>'eventstatus');
	$config['todo'] = Array('field'=>'activitytype','status_field'=>'taskstatus');
	$fieldnames=$config[$type];
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
	foreach ($fieldnames as $type=>$fieldname){
		global $table_prefix;
		$pick_query="select $fieldname from ".$table_prefix."_$fieldname inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid = ? ";
		$params = array($roleid);
		if (($history == 0 || $history == 1) && $type !='field') {
			$pick_query.=" where history = ?";
			$params[] = $history;
		}
		$pickListResult = $adb->pquery($pick_query, $params);
		$noofpickrows = $adb->num_rows($pickListResult);
		$ret_arr[$type]=$fieldname;
		$pickListValue=null;
		for($j = 0; $j < $noofpickrows; $j++)
		{
			$pickListValue[]=$adb->query_result($pickListResult,$j,strtolower($fieldname));
		}
			$ret_arr[$type.'_value'] = $pickListValue;

	}
	return $ret_arr;
}

function getActivityTypeValues($type,$mode,$param='',$skip_values=array()){	//crmv@OPER4876
	global $adb,$current_user, $table_prefix;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	$fieldname = 'activitytype';
	$roleid=$current_user->roleid;
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
	$pick_query="select $fieldname from ".$table_prefix."_$fieldname inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid = ? ";
	$params = array($roleid);
	$pick_query.=" order by sortid asc "; //crmv@32334
	$pickListResult = $adb->pquery($pick_query, $params);
	$noofpickrows = $adb->num_rows($pickListResult);
	$pickListValue=array(); // crmv@172864
	for($j = 0; $j < $noofpickrows; $j++)
	{
		if (is_array($skip_values) && in_array($adb->query_result($pickListResult,$j,strtolower($fieldname)),$skip_values)) continue;	//crmv@OPER4876
		$pickListValue[]=$adb->query_result($pickListResult,$j,strtolower($fieldname));
	}
	if ($_REQUEST['action'] != 'index') { //crmv@39760
		if (is_array($pickListValue)) {
			$taskkey = array_search('Task',$pickListValue);
		} else {
			$taskkey = false;
		}
		if ($type == 'todo') $pickListValue=Array('Task');
		elseif ($type == 'event' && $taskkey !== false) unset($pickListValue[$taskkey]); //crmv@39581
	} //crmv@39760
	switch($mode){
		case "array":
			return $pickListValue;
			break;
		case "string_separated_by":
			return implode($param,$pickListValue);
			break;
		case "format_sql":
			$pickListValue_comma = "(";
		   $noofpickrows=count($pickListValue);
		   if ($noofpickrows!=0){
			   for($k=0; $k < $noofpickrows; $k++)
			   {
			      $pickListSingleVal = $pickListValue[$k];
			      $pickListValue_comma .= $adb->quote($pickListSingleVal);	//crmv@59713
			      if($k < ($noofpickrows-1))
			        	$pickListValue_comma.=',';
			   }
			   $pickListValue_comma.= ")";
		   }
			else  $pickListValue_comma = "('')";
			return $pickListValue_comma;
			break;
		case "default":
			return $pickListValue;
			break;
	}
}

function getCalendarCondition($caltype,$mode=''){
	global $table_prefix;
	$table= $table_prefix.'_activity';
	$config['event'] = Array('field'=>'activitytype','status_field'=>'eventstatus');
	$config['todo'] = Array('field'=>'activitytype','status_field'=>'status');
	$fieldnames=$config[$caltype];
	$arr=getCalendarType($caltype,$mode);
	$condition = " in ";
	if ($arr) {
		$query = "";
	   foreach 	($fieldnames as $type=>$fieldname){
	   	if ($type == 'field') $conn = '';
	   	else $conn = 'and';
	   	$query.=" $conn $table.$fieldname $condition ";
	   	if ($caltype == 'todo' && $type == 'field') {
				$query.= "('Task')";
				continue;
			}
	   	$pickListValue_comma = "(";
		   $noofpickrows=count($arr[$type.'_value']);
		   if ($noofpickrows!=0){
			   for($k=0; $k < $noofpickrows; $k++)
			   {
			      $pickListValue = $arr[$type.'_value'][$k];
			      $pickListValue_comma.="'".$pickListValue."'";
			      if($k < ($noofpickrows-1))
			        	$pickListValue_comma.=',';
			   }
			   $pickListValue_comma.= ")";
		   }
			else  $pickListValue_comma = "('')";
			$query.=$pickListValue_comma;
	   }
	   return $query;
   }
	return null;
}
function getCalendarSql($mode=''){
	global $table_prefix;
	return " and ((".getCalendarCondition('todo',$mode).") or (".getCalendarCondition('event',$mode)."))";
}
function getCalendarSqlNoCondition(){
	global $table_prefix;
	return " and ".$table_prefix."_activity.activitytype in ".getActivityTypeValues('all','format_sql');
}
function getCalendarSqlCondition($mode){
	global $table_prefix;
	return " and ".$table_prefix."_activity.activitytype in ".getActivityTypeValues($mode,'format_sql');
}
//crmv@8398e

function enable_asterisk($id){
	global $adb, $table_prefix;
	//crmv@157490
	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfig = $serverConfigUtils->getConfiguration('asterisk',array('server','inc_call'));
	$server = $serverConfig['server'];
	$inc_call = $serverConfig['inc_call'];
	//crmv@157490e
	$sql="select extension from ".$table_prefix."_users where id = ?";
	$result = $adb->pquery($sql,Array($id));
	$extension = $adb->query_result($result,0,'extension');
	if ($server != '' && trim($extension) != '')
		VteSession::set('asterisk_'.$id, "true");
	else
		VteSession::set('asterisk_'.$id, "false");
	if ($inc_call == 1) VteSession::set('asterisk_inc_call', "true");
	else VteSession::set('asterisk_inc_call', "false");
}

//crmv@8719
/** Function to get permitted fields of current user of a particular module to find duplicate records --Pavani*/
function getMergeFields($module,$str){
	global $adb,$current_user, $table_prefix;
	$tabid = getTabid($module);
	if($str == "available_fields"){
		$result = getFieldsResultForMerge($tabid);
	}
	else { //if($str == fileds_to_merge)
		$sql="select * from ".$table_prefix."_user2mergefields where tabid=? and userid=? and visible=1";
		$result = $adb->pquery($sql, array($tabid,$current_user->id));
	}

	$num_rows=$adb->num_rows($result);

	$user_profileid = fetchUserProfileId($current_user->id);
	$permitted_list = getProfile2FieldPermissionList($module, $user_profileid);

	$sql_def_org="select fieldid from ".$table_prefix."_def_org_field where tabid=? and visible=0";
	$result_def_org=$adb->pquery($sql_def_org,array($tabid));
	$num_rows_org=$adb->num_rows($result_def_org);
	$permitted_org_list = Array();
	for($i=0; $i<$num_rows_org; $i++)
		$permitted_org_list[$i] = $adb->query_result($result_def_org,$i,"fieldid");

	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	for($i=0; $i<$num_rows;$i++)
	{
		$field_id = $adb->query_result($result,$i,"fieldid");
		foreach($permitted_list as $field=>$data)
			if($data[4] == $field_id and $data[1] == 0)
			{
				if($is_admin == 'true' || (in_array($field_id,$permitted_org_list)))
				{
					$field="<option value=\"".$field_id."\">".getTranslatedString($data[0],$module)."</option>";
					$fields.=$field;
						break;
				}
			}
	}
	return $fields;
}
/** Function to get a to find duplicates in a particular module*/
function getDuplicateQuery($module,$field_values,$ui_type_arr)
{
	global $current_user, $table_prefix;
	$tbl_col_fld = explode(",", $field_values);
	$i=0;
	foreach($tbl_col_fld as $val) {
		list($tbl[$i], $cols[$i], $fields[$i]) = explode(".", $val);
		$tbl_cols[$i] = $tbl[$i]. "." . $cols[$i];
		$i++;
	}
	$table_cols = implode(",",$tbl_cols);
	$sec_parameter = getSecParameterforMerge($module);
	if( stristr($_REQUEST['action'],'ImportStep') || ($_REQUEST['action'] == $_REQUEST['module'].'Ajax' && $_REQUEST['current_action'] == 'ImportSteplast'))
	{
		if($module == 'Contacts')
		{
			$ret_arr = get_special_on_clause($table_cols);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="select ".$table_prefix."_contactdetails.contactid as recordid,".$table_prefix."_users_last_import.deleted,$table_cols
					FROM ".$table_prefix."_contactdetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
					INNER JOIN ".$table_prefix."_contactaddress ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
					INNER JOIN ".$table_prefix."_contactsubdetails ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
					LEFT JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
					LEFT JOIN ".$table_prefix."_customerdetails ON ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					INNER JOIN (select $select_clause from ".$table_prefix."_contactdetails t
							INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.contactid
							INNER JOIN ".$table_prefix."_contactaddress addr ON t.contactid = addr.contactaddressid
							INNER JOIN ".$table_prefix."_contactsubdetails subd ON addr.contactaddressid = subd.contactsubscriptionid
							LEFT JOIN ".$table_prefix."_contactscf tcf ON t.contactid = tcf.contactid
    						LEFT JOIN ".$table_prefix."_account acc ON acc.accountid=t.accountid
							LEFT JOIN ".$table_prefix."_customerdetails custd ON custd.customerid=t.contactid
							WHERE crm.deleted=0 group by $select_clause  HAVING COUNT(*)>1) temp
						ON ".get_on_clause($field_values,$ui_type_arr,$module)."
					WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_contactdetails.contactid ASC";

		}

	else if($module == 'Accounts')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="SELECT ".$table_prefix."_account.accountid AS recordid,".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_account
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_account.accountid
				INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
				INNER JOIN ".$table_prefix."_accountshipads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
				LEFT JOIN ".$table_prefix."_accountscf ON ".$table_prefix."_account.accountid=".$table_prefix."_accountscf.accountid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_account.accountid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				INNER JOIN (select $select_clause from ".$table_prefix."_account t
							INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.accountid
							INNER JOIN ".$table_prefix."_accountbillads badd ON t.accountid = badd.accountaddressid
							INNER JOIN ".$table_prefix."_accountshipads sadd ON t.accountid = sadd.accountaddressid
							LEFT JOIN ".$table_prefix."_accountscf tcf ON t.accountid = tcf.accountid
							WHERE crm.deleted=0 group by $select_clause HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_account.accountid ASC";

		}
	else if($module == 'Leads')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="select ".$table_prefix."_leaddetails.leadid as recordid, ".$table_prefix."_users_last_import.deleted,$table_cols
					FROM ".$table_prefix."_leaddetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadsubdetails ON ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadaddress ON ".$table_prefix."_leadaddress.leadaddressid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
					LEFT JOIN ".$table_prefix."_leadscf ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_leaddetails.leadid
					INNER JOIN (select $select_clause from ".$table_prefix."_leaddetails t
							INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.leadid
							INNER JOIN ".$table_prefix."_leadsubdetails subd ON subd.leadsubscriptionid = t.leadid
							INNER JOIN ".$table_prefix."_leadaddress addr ON addr.leadaddressid = subd.leadsubscriptionid
							LEFT JOIN ".$table_prefix."_leadscf tcf ON tcf.leadid=t.leadid
							WHERE crm.deleted=0 and t.converted = 0 group by $select_clause HAVING COUNT(*)>1) temp
						ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0 AND ".$table_prefix."_leaddetails.converted = 0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_leaddetails.leadid ASC";

		}
	else if($module == 'Products')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];

			$nquery="SELECT ".$table_prefix."_products.productid AS recordid,".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_products
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_products.productid
				LEFT JOIN ".$table_prefix."_productcf ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid
				INNER JOIN (select $select_clause from ".$table_prefix."_products t
						INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.productid
						LEFT JOIN ".$table_prefix."_productcf tcf ON tcf.productid=t.productid
						WHERE crm.deleted=0 group by $select_clause HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0 ORDER BY $table_cols,".$table_prefix."_products.productid ASC";

		}
		else if($module == 'HelpDesk')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="SELECT ".$table_prefix."_troubletickets.ticketid AS recordid,".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_troubletickets
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid = ".$table_prefix."_troubletickets.parent_id
				LEFT JOIN ".$table_prefix."_contactdetails ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_troubletickets.parent_id
				LEFT JOIN ".$table_prefix."_ticketcf ON ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_users.id
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_attachments ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
				LEFT JOIN ".$table_prefix."_ticketcomments ON ".$table_prefix."_ticketcomments.ticketid = ".$table_prefix."_crmentity.crmid
				INNER JOIN (select $select_clause from ".$table_prefix."_troubletickets t
						INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.ticketid
						LEFT JOIN ".$table_prefix."_account acc ON acc.accountid = t.parent_id
						LEFT JOIN ".$table_prefix."_contactdetails contd ON contd.contactid = t.parent_id
						LEFT JOIN ".$table_prefix."_ticketcf tcf ON tcf.ticketid = t.ticketid
						WHERE crm.deleted=0 group by $select_clause HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0". $sec_parameter ." ORDER BY $table_cols,".$table_prefix."_troubletickets.ticketid ASC";

		}
		else if($module == 'Potentials')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="SELECT ".$table_prefix."_potential.potentialid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_potential
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_potentialscf ON ".$table_prefix."_potentialscf.potentialid = ".$table_prefix."_potential.potentialid
				INNER JOIN (select $select_clause from ".$table_prefix."_potential t
						INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.potentialid
						LEFT JOIN ".$table_prefix."_potentialscf tcf ON tcf.potentialid=t.potentialid
						WHERE crm.deleted=0 group by $select_clause HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_potential.potentialid ASC";

		}
		else if($module == 'Vendors')
		{
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$nquery="SELECT ".$table_prefix."_vendor.vendorid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_vendor
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_vendor.vendorid
				LEFT JOIN ".$table_prefix."_vendorcf ON ".$table_prefix."_vendorcf.vendorid=".$table_prefix."_vendor.vendorid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_vendor.vendorid
				INNER JOIN (select $select_clause from ".$table_prefix."_vendor t
						INNER JOIN ".$table_prefix."_crmentity crm ON crm.crmid=t.vendorid
						LEFT JOIN ".$table_prefix."_vendorcf tcf ON tcf.vendorid=t.vendorid
						WHERE crm.deleted=0 group by $select_clause HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module)."
				WHERE ".$table_prefix."_crmentity.deleted=0 ORDER BY $table_cols,".$table_prefix."_vendor.vendorid ASC";

		} else {
			$ret_arr = get_special_on_clause($field_values);
			$select_clause = $ret_arr['sel_clause'];
			$on_clause = $ret_arr['on_clause'];
			$modObj = CRMEntity::getInstance($module);
			if ($modObj != null && method_exists($modObj, 'getDuplicatesQuery')) {
				$nquery = $modObj->getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_clause);
			}
		}
	}
	else
	{
		//crmv@36508
		if (VteSession::get('duplicateshandling_empty_flag') === true){
			add_empty_clause($field_values,$sec_parameter);
		}
		VteSession::remove('duplicates_'.$module);
		//create temporary table with only duplicated stuff
		if($module == 'Contacts' || $module == 'Accounts' || $module == 'Leads'){  // crmv@77469
			global $adb,$current_user,$table_prefix;
			$tmptable_orig = 'tmp_dupl_'.$module.'_'.$current_user->id;	//crmv@58258
			$tmptable = $adb->datadict->changeTableName($tmptable_orig);	//crmv@58258
			if ($adb->table_exist($tmptable,true)){
				$sql = "drop table $tmptable";
				$adb->query($sql,false,'',true);	//crmv@70475
			}
			$field_create = Array();
			$field_tmptable = Array();
			$field_array = explode(",",$field_values);
			foreach($field_array as $fld){
				$sub_arr = explode(".",$fld);
				$tbl_name = $sub_arr[0];
				$col_name = $sub_arr[1];
				$fld_name = $sub_arr[2];
				$fields = $adb->database->MetaColumns($tbl_name);
				$field_params = $fields[strtoupper($col_name)];
				$field_otherparams = '';
				if (in_array($adb->datadict->MetaType($field_params),Array('X','XL'))){
					$field_metatype = "C";
					$field_otherparams = "(255)";
				}
				else{
					$field_metatype = $adb->datadict->MetaType($field_params);
				}
				$field_tmptable[] = $field_params->name;
				if ($field_params->max_length > 0){
					$field_otherparams = "(".$field_params->max_length.")";
				}
				if (VteSession::get('duplicateshandling_empty_flag') === true){
					$field_create[] = $field_params->name." $field_metatype $field_otherparams primary key";
				}
				else{
					$field_create[] = $field_params->name." $field_metatype $field_otherparams";
				}
			}
			//find entity name fields
			$fields_to_add = Array();
			$query = "select e.fieldname from ".$table_prefix."_entityname e
			where modulename = ?";
			$res = $adb->pquery($query, array($module));
			if ($res){
				while($row = $adb->fetchByAssoc($res,-1,false)){
					if (strpos($row['fieldname'],",") !==false){
						$rowfields = explode(",",$row['fieldname']);
						foreach ($rowfields as $fieldname_){
							$fields_to_add[] = $fieldname_;
						}
					}
					else{
						$fields_to_add[] = $row['fieldname'];
					}
				}
				$sql = "select columnname,tablename from {$table_prefix}_field f
				inner join {$table_prefix}_tab t on t.tabid = f.tabid
				where t.name = ? and f.fieldname in (".generateQuestionMarks($fields_to_add).")";
				$params = Array($module,$fields_to_add);
				$res2 = $adb->pquery($sql,$params);
				if ($res2){
					$columns_to_add = Array();
					while($row2 = $adb->fetchByAssoc($res2,-1,false)){
						$columns_to_add[$row2['columnname']] = $row2['tablename'].".".$row2['columnname'];
					}
				}
			}
			$add_field = Array();
			if (!empty($columns_to_add)){
				foreach ($columns_to_add as $column_=>$field_complete){
					if (!in_array($column_,$field_tmptable)){
						$add_field[] = $field_complete;
					}
				}
				if (!empty($add_field)){
					$add_field = implode(",",$add_field);
				}
				else{
					$add_field = false;
				}
			}
			else{
				$add_field = false;
			}
			Vtecrm_Utils::CreateTable($tmptable_orig,implode(",",$field_create),true,true);	//crmv@58258@
			if (VteSession::get('duplicateshandling_empty_flag') !== true){
				$adb->datadict->ExecuteSQLArray((Array)$adb->datadict->CreateIndexSQL('idx_'.$tmptable,$tmptable,implode(",",$field_tmptable)));
			}
		}
		if($module == 'Contacts')
		{
			$sql_insert = "insert into $tmptable (".implode(",",$field_tmptable).")
				SELECT $table_cols
				FROM ".$table_prefix."_contactdetails
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid
				INNER JOIN ".$table_prefix."_contactaddress ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
				INNER JOIN ".$table_prefix."_contactsubdetails ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
				LEFT JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
				LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
				LEFT JOIN ".$table_prefix."_customerdetails ON ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
				GROUP BY ".$table_cols." HAVING COUNT(*)>1";
			$res_insert = $adb->query($sql_insert);
			if ($res_insert){
				$count_query = "select count(*) as count from $tmptable";
				$res_count = $adb->query($count_query);
				if ($res_count){
					VteSession::set('duplicates_'.$module, $adb->query_result_no_html($res_count,0,'count'));
				}
				$nquery = "SELECT ".$table_prefix."_contactdetails.contactid AS recordid,
						".$table_prefix."_users_last_import.deleted,".$table_cols;
				if ($add_field !==false){
					$nquery.=",".$add_field;
				}
				$nquery.="
						FROM ".$table_prefix."_contactdetails
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
						INNER JOIN ".$table_prefix."_contactaddress ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
						INNER JOIN ".$table_prefix."_contactsubdetails ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
						LEFT JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
						LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_contactdetails.contactid
						LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
						LEFT JOIN ".$table_prefix."_customerdetails ON ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
						LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
						LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
						INNER JOIN $tmptable temp
							ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
		                                WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_contactdetails.contactid ASC";
			}
			/*
			$nquery = "SELECT ".$table_prefix."_contactdetails.contactid AS recordid,
					".$table_prefix."_users_last_import.deleted,".$table_cols."
					FROM ".$table_prefix."_contactdetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
					INNER JOIN ".$table_prefix."_contactaddress ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
					INNER JOIN ".$table_prefix."_contactsubdetails ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
					LEFT JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
					LEFT JOIN ".$table_prefix."_customerdetails ON ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					INNER JOIN (SELECT $table_cols
							FROM ".$table_prefix."_contactdetails
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid
							INNER JOIN ".$table_prefix."_contactaddress ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
							INNER JOIN ".$table_prefix."_contactsubdetails ON ".$table_prefix."_contactaddress.contactaddressid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
							LEFT JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
							LEFT JOIN ".$table_prefix."_account ON ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
							LEFT JOIN ".$table_prefix."_customerdetails ON ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
							LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
							LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
							WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
							GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
						ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
	                                WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_contactdetails.contactid ASC";
			*/
		}
		else if($module == 'Accounts')
		{
			$sql_insert = "insert into $tmptable (".implode(",",$field_tmptable).")
			SELECT $table_cols
					FROM ".$table_prefix."_account
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_account.accountid
					INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
					INNER JOIN ".$table_prefix."_accountshipads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
					LEFT JOIN ".$table_prefix."_accountscf ON ".$table_prefix."_account.accountid=".$table_prefix."_accountscf.accountid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
					GROUP BY ".$table_cols." HAVING COUNT(*)>1";
			$res_insert = $adb->query($sql_insert);
			if ($res_insert){
				$count_query = "select count(*) as count from $tmptable";
				$res_count = $adb->query($count_query);
				if ($res_count){
					VteSession::set('duplicates_'.$module, $adb->query_result_no_html($res_count,0,'count'));
				}
				$nquery="SELECT ".$table_prefix."_account.accountid AS recordid,
					".$table_prefix."_users_last_import.deleted,".$table_cols;
				if ($add_field !==false){
					$nquery.=",".$add_field;
				}
				$nquery.="
						FROM ".$table_prefix."_account
						INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_account.accountid
						INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
						INNER JOIN ".$table_prefix."_accountshipads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
						LEFT JOIN ".$table_prefix."_accountscf ON ".$table_prefix."_account.accountid=".$table_prefix."_accountscf.accountid
						LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_account.accountid
						LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
						LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
						INNER JOIN $tmptable temp ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
						WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_account.accountid ASC";
			}
			/*
			$nquery="SELECT ".$table_prefix."_account.accountid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_account
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_account.accountid
				INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
				INNER JOIN ".$table_prefix."_accountshipads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
				LEFT JOIN ".$table_prefix."_accountscf ON ".$table_prefix."_account.accountid=".$table_prefix."_accountscf.accountid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_account.accountid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				INNER JOIN (SELECT $table_cols
					FROM ".$table_prefix."_account
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_account.accountid
					INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountbillads.accountaddressid
					INNER JOIN ".$table_prefix."_accountshipads ON ".$table_prefix."_account.accountid = ".$table_prefix."_accountshipads.accountaddressid
					LEFT JOIN ".$table_prefix."_accountscf ON ".$table_prefix."_account.accountid=".$table_prefix."_accountscf.accountid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
					GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
				ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
                                WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_account.accountid ASC";
			*/
		}
		//crmv@36508 e
		else if($module == 'Leads')
		{
			//crmv@77469
			$sql_insert = 
				"INSERT INTO $tmptable (".implode(",",$field_tmptable).")
				SELECT $table_cols
					FROM ".$table_prefix."_leaddetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadsubdetails ON ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadaddress ON ".$table_prefix."_leadaddress.leadaddressid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
					LEFT JOIN ".$table_prefix."_leadscf ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					WHERE ".$table_prefix."_crmentity.deleted=0 AND ".$table_prefix."_leaddetails.converted = 0 $sec_parameter
					GROUP BY $table_cols HAVING COUNT(*)>1";
			$res_insert = $adb->query($sql_insert);
			if ($res_insert){
				$count_query = "select count(*) as count from $tmptable";
				$res_count = $adb->query($count_query);
				if ($res_count) {
					VteSession::set('duplicates_'.$module, $adb->query_result_no_html($res_count,0,'count'));
				}
				$nquery = "SELECT ".$table_prefix."_leaddetails.leadid AS recordid,
						".$table_prefix."_users_last_import.deleted,".$table_cols;
				if ($add_field !== false){
					$nquery .= ",".$add_field;
				}
				$nquery .= "
					FROM ".$table_prefix."_leaddetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadsubdetails ON ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadaddress ON ".$table_prefix."_leadaddress.leadaddressid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
					LEFT JOIN ".$table_prefix."_leadscf ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_leaddetails.leadid
					INNER JOIN $tmptable temp ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
					WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_leaddetails.leadid ASC";
			}

			/*
			$nquery = "SELECT ".$table_prefix."_leaddetails.leadid AS recordid, ".$table_prefix."_users_last_import.deleted,$table_cols
					FROM ".$table_prefix."_leaddetails
					INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadsubdetails ON ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
					INNER JOIN ".$table_prefix."_leadaddress ON ".$table_prefix."_leadaddress.leadaddressid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
					LEFT JOIN ".$table_prefix."_leadscf ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
					LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_leaddetails.leadid
					INNER JOIN (SELECT $table_cols
							FROM ".$table_prefix."_leaddetails
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
							INNER JOIN ".$table_prefix."_leadsubdetails ON ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
							INNER JOIN ".$table_prefix."_leadaddress ON ".$table_prefix."_leadaddress.leadaddressid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
							LEFT JOIN ".$table_prefix."_leadscf ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
							LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
							LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
							WHERE ".$table_prefix."_crmentity.deleted=0 AND ".$table_prefix."_leaddetails.converted = 0 $sec_parameter
							GROUP BY $table_cols HAVING COUNT(*)>1) temp
					ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
					WHERE ".$table_prefix."_crmentity.deleted=0  AND ".$table_prefix."_leaddetails.converted = 0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_leaddetails.leadid ASC";
			*/
			//crmv@77469e
		}
		else if($module == 'Products')
		{
			$nquery = "SELECT ".$table_prefix."_products.productid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_products
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_products.productid
				LEFT JOIN ".$table_prefix."_productcf ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid
				INNER JOIN (SELECT $table_cols
							FROM ".$table_prefix."_products
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_products.productid
							LEFT JOIN ".$table_prefix."_productcf ON ".$table_prefix."_productcf.productid = ".$table_prefix."_products.productid
							WHERE ".$table_prefix."_crmentity.deleted=0
							GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
				ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
                                WHERE ".$table_prefix."_crmentity.deleted=0  ORDER BY $table_cols,".$table_prefix."_products.productid ASC";
		}
		else if($module == "HelpDesk")
		{
			// crmv@110399
			$nquery = "SELECT ".$table_prefix."_troubletickets.ticketid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_troubletickets
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_ticketcf ON ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_troubletickets.ticketid
				LEFT JOIN ".$table_prefix."_attachments ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_contactdetails ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_troubletickets.parent_id
				INNER JOIN (SELECT $table_cols FROM ".$table_prefix."_troubletickets
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid
							LEFT JOIN ".$table_prefix."_ticketcf ON ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
							LEFT JOIN ".$table_prefix."_attachments ON ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_crmentity.crmid
							LEFT JOIN ".$table_prefix."_contactdetails ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_troubletickets.parent_id
							LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
							LEFT JOIN ".$table_prefix."_contactdetails contd ON contd.contactid = ".$table_prefix."_troubletickets.parent_id
				WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
							GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
				ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
                WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_troubletickets.ticketid ASC";
			// crmv@110399e
		}
		else if($module == "Potentials")
		{
			$nquery = "SELECT ".$table_prefix."_potential.potentialid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_potential
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_potentialscf ON ".$table_prefix."_potentialscf.potentialid = ".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_potential.potentialid
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				INNER JOIN (SELECT $table_cols
							FROM ".$table_prefix."_potential
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_potential.potentialid
							LEFT JOIN ".$table_prefix."_potentialscf ON ".$table_prefix."_potentialscf.potentialid = ".$table_prefix."_potential.potentialid
							LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
							LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
							WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter
							GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
				ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
                                WHERE ".$table_prefix."_crmentity.deleted=0 $sec_parameter ORDER BY $table_cols,".$table_prefix."_potential.potentialid ASC";
		}
		else if($module == "Vendors")
		{
			$nquery = "SELECT ".$table_prefix."_vendor.vendorid AS recordid,
				".$table_prefix."_users_last_import.deleted,".$table_cols."
				FROM ".$table_prefix."_vendor
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_vendor.vendorid
				LEFT JOIN ".$table_prefix."_vendorcf ON ".$table_prefix."_vendorcf.vendorid=".$table_prefix."_vendor.vendorid
				LEFT JOIN ".$table_prefix."_users_last_import ON ".$table_prefix."_users_last_import.bean_id=".$table_prefix."_vendor.vendorid
				INNER JOIN (SELECT $table_cols
							FROM ".$table_prefix."_vendor
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_vendor.vendorid
							LEFT JOIN ".$table_prefix."_vendorcf ON ".$table_prefix."_vendorcf.vendorid=".$table_prefix."_vendor.vendorid
							WHERE ".$table_prefix."_crmentity.deleted=0
							GROUP BY ".$table_cols." HAVING COUNT(*)>1) temp
				ON ".get_on_clause($field_values,$ui_type_arr,$module) ."
                                WHERE ".$table_prefix."_crmentity.deleted=0  ORDER BY $table_cols,".$table_prefix."_vendor.vendorid ASC";
		} else {
			$modObj = CRMEntity::getInstance($module);
			if ($modObj != null && method_exists($modObj, 'getDuplicatesQuery')) {
				$nquery = $modObj->getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr);
			}
		}
	}
	return $nquery;
}

/** Function to return the duplicate records data as a formatted array */

function getDuplicateRecordsArr($module)
{
	global $adb,$app_strings,$list_max_entries_per_page,$theme;
	$field_values_array=getFieldValues($module);
	//crmv@36508
	$field_values_array_all = getFieldValues($module,true);
	$field_values=$field_values_array['fieldnames_list'];
	$fld_arr=$field_values_array_all['fieldnames_array'];
	$col_arr=$field_values_array_all['columnnames_array'];
	$fld_labl_arr=$field_values_array_all['fieldlabels_array'];
	$ui_type=$field_values_array_all['fieldname_uitype'];
	$col_arr_restrict=$field_values_array['columnnames_array'];
	//crmv@36508 e

	$LVU = ListViewUtils::getInstance();

	$dup_query = getDuplicateQuery($module,$field_values,$ui_type);
	// added for page navigation
	$dup_count_query = mkCountQuery($dup_query);
	$count_res = $adb->query($dup_count_query);
	$no_of_rows = $adb->query_result($count_res,0,"count");

	if($no_of_rows <= $list_max_entries_per_page)
		VteSession::set('dup_nav_start'.$module, 1);
	else if(isset($_REQUEST["start"]) && $_REQUEST["start"] != "" && VteSession::get('dup_nav_start'.$module) != $_REQUEST["start"])
		VteSession::set('dup_nav_start'.$module, ListViewSession::getRequestStartPage());
	$start = (VteSession::get('dup_nav_start'.$module) != "")?VteSession::get('dup_nav_start'.$module):1;
	$navigation_array = $LVU->getNavigationValues($start, $no_of_rows, $list_max_entries_per_page);
	$start_rec = $navigation_array['start'];
	$end_rec = $navigation_array['end_val'];
	$navigationOutput = $LVU->getTableHeaderNavigation($navigation_array, "",$module,"FindDuplicate","");
	if ($start_rec == 0)
		$limit_start_rec = 0;
	else
		$limit_start_rec = $start_rec -1;

	//ends

	$nresult = $adb->limitQuery($dup_query,$limit_start_rec,$list_max_entries_per_page);
	$no_rows=$adb->num_rows($nresult);
	require_once('modules/VteCore/layout_utils.php');	//crmv@30447
	if($no_rows == 0)
	{
		if ($_REQUEST['action'] == 'FindDuplicateRecords')
		{
			//echo "<br><br><center>".$app_strings['LBL_NO_DUPLICATE']." <a href='javascript:window.history.back()'>".$app_strings['LBL_GO_BACK'].".</a></center>";
			//die;
			echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";
			echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
			echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

				<table border='0' cellpadding='5' cellspacing='0' width='98%'>
				<tbody><tr>
				<td rowspan='2' width='11%'><img src='" . resourcever('empty.jpg') . "' ></td>
				<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>$app_strings[LBL_NO_DUPLICATE]</span></td>
				</tr>
				<tr>
				<td class='small' align='right' nowrap='nowrap'>
				<a href='javascript:window.history.back();'>$app_strings[LBL_GO_BACK]</a><br>     </td>
				</tr>
				</tbody></table>
				</div>";
			echo "</td></tr></table>";
			exit();
		}
		else
		{
			echo "<br><br><table align='center' class='reportCreateBottom big' width='95%'><tr><td align='center'>".$app_strings['LBL_NO_DUPLICATE']."</td></tr></table>";
			die;
		}
	}
	$rec_cnt = 0;
	$temp = Array();
	$sl_arr = Array();
	$grp = "group0";
	$gcnt = 0;
	$ii = 0; //ii'th record in group
	while ( $rec_cnt < $no_rows )
	{
		$result = $adb->fetchByAssoc($nresult);
		//echo '<pre>';print_r($result);echo '</pre>';
		if($rec_cnt != 0)
		{
			//crmv@36508
			$sl_arr = Array();
			foreach ($col_arr_restrict as $col_restrict){
				$sl_arr[$col_restrict] = $result[$col_restrict];
			}
			//crmv@36508 e
			array_walk($temp,'lower_array');
			array_walk($sl_arr,'lower_array');
			$arr_diff = array_diff($temp,$sl_arr);
			if(count($arr_diff) > 0)
			{
				$gcnt++;
				$temp = $sl_arr;
				$ii = 0;
			}
			$grp = "group".$gcnt;
		}
		$fld_values[$grp][$ii]['recordid'] = $result['recordid'];
		//crmv@36508
		if ($rec_cnt == 0){
			$sl_arr = Array();
			foreach ($col_arr_restrict as $col_restrict){
				$temp[$col_restrict] = $result[$col_restrict];
			}
		}
		//crmv@36508 e
		for($k=0;$k<count($col_arr);$k++)
		{
			if($ui_type[$fld_arr[$k]] == 56)
			{
				if($result[$col_arr[$k]] == 0)
				{
					$result[$col_arr[$k]]=$app_strings['no'];
				}
				else
					$result[$col_arr[$k]]=$app_strings['yes'];
			}
			if($ui_type[$fld_arr[$k]] ==53 || $ui_type[$fld_arr[$k]] ==52)
			{
				if($result[$col_arr[$k]] != '')
				{
					$owner=getOwnerName($result[$col_arr[$k]]);
				}
				$result[$col_arr[$k]]=$owner;
			}
			/*uitype 10 handling*/
			if($ui_type[$fld_arr[$k]] == 10){
				$result[$col_arr[$k]] = getRecordInfoFromID($result[$col_arr[$k]]);
			}

			$fld_values[$grp][$ii][$fld_labl_arr[$k]] = $result[$col_arr[$k]];

		}
		$fld_values[$grp][$ii]['Entity Type'] = $result['deleted'];
		$ii++;
		$rec_cnt++;
	}

	$gro="group";
	for($i=0;$i<$no_rows;$i++)
	{
		$ii=0;
		$dis_group[]=$fld_values[$gro.$i][$ii];
		$count_group[$i] = is_array($fld_values[$gro.$i]) ? count($fld_values[$gro.$i]) : 0; // crmv@172864
		$ii++;
		$new_group[]=$dis_group[$i];
	}
	$fld_nam=$new_group[0];
	$ret_arr[0]=$fld_values;
	$ret_arr[1]=$fld_nam;
	$ret_arr[2]=$ui_type;
	$ret_arr["navigation"]=$navigationOutput;
	$ret_arr["noofrows"]=$no_of_rows; //crmv@36508
	return $ret_arr;
}

/** Function to get on clause criteria for sub tables like address tables to construct duplicate check query */
function get_special_on_clause($field_list)
{
	global $table_prefix;
	$field_array = explode(",",$field_list);
	$ret_str = '';
	$sel_clause = '';
	$i=1;
	$cnt = count($field_array);
	$spl_chk = ($_REQUEST['modulename'] != '')?$_REQUEST['modulename']:$_REQUEST['module'];
	foreach($field_array as $fld)
	{
		$sub_arr = explode(".",$fld);
		$tbl_name = $sub_arr[0];
		$col_name = $sub_arr[1];
		$fld_name = $sub_arr[2];

		//need to handle aditional conditions with sub tables for further modules of duplicate check
		if($tbl_name == $table_prefix.'_leadsubdetails' || $tbl_name == $table_prefix.'_contactsubdetails')
			$tbl_alias = "subd";
		else if($tbl_name == $table_prefix.'_leadaddress' || $tbl_name == $table_prefix.'_contactaddress')
			$tbl_alias = "addr";
		else if($tbl_name == $table_prefix.'_account' && $spl_chk == 'Contacts')
			$tbl_alias = "acc";
		else if($tbl_name == $table_prefix.'_accountbillads')
			$tbl_alias = "badd";
		else if($tbl_name == $table_prefix.'_accountshipads')
			$tbl_alias = "sadd";
		else if($tbl_name == $table_prefix.'_crmentity')
			$tbl_alias = "crm";
		else if($tbl_name == $table_prefix.'_customerdetails')
			$tbl_alias = "custd";
		else if($tbl_name == $table_prefix.'_contactdetails' && spl_chk == 'HelpDesk')
			$tbl_alias = "contd";
		else if(stripos($tbl_name, 'cf') === (strlen($tbl_name) - strlen('cf')))
			$tbl_alias = "tcf"; // Custom Field Table Prefix to use in subqueries
		else
			$tbl_alias = "t";

		$sel_clause .= $tbl_alias.".".$col_name.",";
		$ret_str .= " $tbl_name.$col_name = $tbl_alias.$col_name";
		if ($cnt != $i) $ret_str .= " and ";
		$i++;
	}
	$ret_arr['on_clause'] = $ret_str;
	$ret_arr['sel_clause'] = trim($sel_clause,",");
	return $ret_arr;
}

/** Function to get on clause criteria for duplicate check queries */
function get_on_clause($field_list,$uitype_arr,$module)
{
	global $adb;
	$field_array = explode(",",$field_list);
	$ret_str = '';
	$i=1;
	foreach($field_array as $fld)
	{
		$sub_arr = explode(".",$fld);
		$tbl_name = $sub_arr[0];
		$col_name = $sub_arr[1];
		$fld_name = $sub_arr[2];
		//crmv@36508
		if (($module == 'Accounts' || $module == 'Contacts' || $module == 'Leads') && VteSession::get('duplicateshandling_empty_flag') === true){ // crmv@77469
			$ret_str .= " ".$tbl_name.".".$col_name." = temp.".$col_name;
		}
		else{
			$ret_str .= " ".$adb->database->IfNull($tbl_name.".".$col_name,'null')." = ".$adb->database->IfNull('temp.'.$col_name,'null')." ";
		}
		//crmv@36508 e
		if (count($field_array) != $i) $ret_str .= " and ";
		$i++;
	}
	return $ret_str;
}
//crmv@36508
function add_empty_clause($field_list,&$sec_parameter)
{
	global $adb;
	$field_array = explode(",",$field_list);
	$ret_str = '';
	foreach($field_array as $fld)
	{
		$sub_arr = explode(".",$fld);
		$tbl_name = $sub_arr[0];
		$col_name = $sub_arr[1];
		$fld_name = $sub_arr[2];
		$ret_str .= " and coalesce({$tbl_name}.{$col_name},'') <> ''";
	}
	if ($ret_str != ''){
		$sec_parameter = $ret_str." ".$sec_parameter;
	}
}
//crmv@36508 e
/** call back function to change the array values in to lower case */
function lower_array(&$string){
	    $string = strtolower(trim($string));
}

/** Function to get recordids for subquery where condition */
// TODO - Need to check if this method is used anywhere?
function get_subquery_recordids($sub_query)
{
	global $adb;
	//need to update this module whenever duplicate check tool added for new modules
	$module_id_array = Array("Accounts"=>"accountid","Contacts"=>"contactid","Leads"=>"leadid","Products"=>"productid","HelpDesk"=>"ticketid","Potentials"=>"potentialid","Vendors"=>"vendorid");
	$id = ($module_id_array[$_REQUEST['modulename']] != '')?$module_id_array[$_REQUEST['modulename']]:$module_id_array[$_REQUEST['module']];
	$sub_res = '';
	$sub_result = $adb->query($sub_query);
	$row_count = $adb->num_rows($sub_result);
	$sub_res = '';
	if($row_count > 0)
	{
		while($rows = $adb->fetchByAssoc($sub_result))
		{
			$sub_res .= $rows[$id].",";
		}
		$sub_res = trim($sub_res,",");
	}
	else
		$sub_res .= "''";
	return $sub_res;
}

/** Function to get tablename, columnname, fieldname, fieldlabel and uitypes of fields of merge criteria for a particular module*/
function getFieldValues($module,$all=false) //crmv@36508
{
	global $adb,$current_user,$table_prefix;

	//In future if we want to change a id mapping to name or other string then we can add that elements in this array.
	//$fld_table_arr = Array("vte_contactdetails.account_id"=>"vte_account.accountname");
	//$special_fld_arr = Array("account_id"=>"accountname");

	$fld_table_arr = Array();
	$special_fld_arr = Array();
	$tabid = getTabid($module);

	$fieldname_query="select fieldname,fieldlabel,uitype,tablename,columnname from ".$table_prefix."_field where fieldid in
			(select fieldid from ".$table_prefix."_user2mergefields WHERE tabid=? AND userid=? AND visible = ?) and ".$table_prefix."_field.presence in (0,2)";
	//crmv@36508
	$params_query = Array($tabid, $current_user->id, 1);
	if ($all && ($module == 'Accounts' || $module == 'Contacts' || $module == 'Leads')){ //crmv@77469
		//find entity name fields
		$fields_to_add = Array();
		$query = "select e.fieldname from ".$table_prefix."_entityname e
		where modulename = ?";
		$res = $adb->pquery($query, array($module));
		if ($res){
			while($row = $adb->fetchByAssoc($res,-1,false)){
				if (strpos($row['fieldname'],",") !==false){
					$rowfields = explode(",",$row['fieldname']);
					foreach ($rowfields as $fieldname_){
						$fields_to_add[] = $fieldname_;
					}
				}
				else{
					$fields_to_add[] = $row['fieldname'];
				}
			}
			$fieldname_query .= "or fieldid in (select fieldid from {$table_prefix}_field f
			inner join {$table_prefix}_tab t on t.tabid = f.tabid
			where t.name = ? and f.fieldname in (".generateQuestionMarks($fields_to_add)."))";
			$params_query[] = $module;
			$params_query[] = $fields_to_add;
		}
	}

	$fieldname_result = $adb->pquery($fieldname_query,$params_query );
	//crmv@36508 e
	$field_num_rows = $adb->num_rows($fieldname_result);

	$fld_arr = array();
	$col_arr = array();
	for($j=0;$j< $field_num_rows;$j ++)
	{
		$tablename = $adb->query_result($fieldname_result,$j,'tablename');
		$column_name = $adb->query_result($fieldname_result,$j,'columnname');
		$field_name = $adb->query_result($fieldname_result,$j,'fieldname');
		$field_lbl = $adb->query_result($fieldname_result,$j,'fieldlabel');
		$ui_type = $adb->query_result($fieldname_result,$j,'uitype');
		$table_col = $tablename.".".$column_name;
		if(getFieldVisibilityPermission($module,$current_user->id,$field_name) == 0)
		{
			$fld_name = ($special_fld_arr[$field_name] != '')?$special_fld_arr[$field_name]:$field_name;

			$fld_arr[] = $fld_name;
			$col_arr[] = $column_name;
			if($fld_table_arr[$table_col] != '')
				$table_col = $fld_table_arr[$table_col];

			$field_values_array['fieldnames_list'][] = $table_col . "." . $fld_name;
			$fld_labl_arr[]=$field_lbl;
			$uitype[$field_name]=$ui_type;
		}
	}
	$field_values_array['fieldnames_list']=implode(",",(Array)$field_values_array['fieldnames_list']);
	$field_values=implode(",",$fld_arr);
	$field_values_array['fieldnames']=$field_values;
	$field_values_array["fieldnames_array"]=$fld_arr;
	$field_values_array["columnnames_array"]=$col_arr;
	$field_values_array['fieldlabels_array']=$fld_labl_arr;
	$field_values_array['fieldname_uitype']=$uitype;

	return $field_values_array;
}

function getSecParameterforMerge($module)
{
	global $current_user;
	$tab_id = getTabid($module);
	$sec_parameter="";
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	if($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[$tab_id] == 3)
	{
		if($module == "Products" || $module == "Vendors") {
			$sec_parameter = "";
		} else {
			$sec_parameter=getListViewSecurityParameter($module);
		}
	}
	return $sec_parameter;
}

//added to find duplicates
/** To get the converted record values which have to be display in duplicates merging tpl*/
function getRecordValues($id_array,$module) {
	global $adb,$current_user;
	global $app_strings,$table_prefix;
	$tabid=getTabid($module);
	$query="select fieldname,fieldlabel,uitype from ".$table_prefix."_field where tabid=? and fieldname  not in ('createdtime','modifiedtime') and ".$table_prefix."_field.presence in (0,2) and uitype not in('4')";
	$result=$adb->pquery($query, array($tabid));
	$no_rows=$adb->num_rows($result);

	$focus = CRMEntity::getInstance($module);
	if(isset($id_array) && $id_array !='') {
		foreach($id_array as $value) {
			$focus->id=$value;
			$focus->retrieve_entity_info($value,$module);
			$field_values[]=$focus->column_fields;
		}
	}
	$labl_array=array();
	$value_pair = array();
	$c = 0;
	for($i=0;$i<$no_rows;$i++) {
		$fld_name=$adb->query_result($result,$i,"fieldname");
		$fld_label=$adb->query_result($result,$i,"fieldlabel");
		$ui_type=$adb->query_result($result,$i,"uitype");

		if(getFieldVisibilityPermission($module,$current_user->id,$fld_name) == '0') {
			$fld_array []= $fld_name;
			$record_values[$c][$fld_label] = Array();
			$ui_value[]=$ui_type;
			for($j=0;$j < count($field_values);$j++) {
				if($ui_type ==56) {
					if($field_values[$j][$fld_name] == 0)
						$value_pair['disp_value']=$app_strings['no'];
					else
						$value_pair['disp_value']=$app_strings['yes'];
				} elseif($ui_type == 53) {
					$owner_id=$field_values[$j][$fld_name];
					$ownername=getOwnerName($owner_id);
					$value_pair['disp_value']=$ownername;
				} elseif($ui_type == 52) {
					$user_id = $field_values[$j][$fld_name];
					$user_name=getUserName($user_id);
					$value_pair['disp_value']=$user_name;
				} elseif($ui_type == 10) {
					$value_pair['disp_value'] = getRecordInfoFromID($field_values[$j][$fld_name]);
				} else {
					$value_pair['disp_value']=$field_values[$j][$fld_name];
				}
				$value_pair['org_value'] = $field_values[$j][$fld_name];

				array_push($record_values[$c][$fld_label],$value_pair);
			}
			$c++;
		}

	}
	$parent_array[0]=$record_values;
	$parent_array[1]=$fld_array;
	$parent_array[2]=$fld_array;
	return $parent_array;
}
//crmv@8719e

/**
 * this function takes an url and returns the module name from it
 */
function getPropertiesFromURL($url, $action){
	$result = array();
	preg_match("/$action=([^&]+)/",$url,$result);
	return $result[1];
}

//functions for settings page end


//vtc
function duplicateProduct($productid) {
	global $log, $table_prefix;
	if($productid != "") {
		global $adb;
		$product = CRMEntity::getInstance('Products');
		$product->retrieve_entity_info($productid,"Products");
		$product->mode = "";
		$product->id = "";
		$product->Save("Products");
		$adb->query("update ".$table_prefix."_products set associated = 1 where productid = ".$product->id);

		$log->debug("crmvillage : "."update ".$table_prefix."_products set associated = 1 where productid = ".$product->id);

		return $product->id;
	} else return "";
}

function associateProduct($productid,$crmid,$related_module) {
	if($productid != "" && $crmid != "" && $related_module != "") {
		global $adb,$log, $table_prefix;
		$adb->query("insert into ".$table_prefix."_seproductsrel values (".$crmid.",".$productid.",'".$related_module."')");
		$log->debug("crmvillage : "."insert into ".$table_prefix."_seproductsrel values (".$crmid.",".$productid.",'".$related_module."')");
	}
}

function duplicateAndAssociateProduct($productid,$crmid,$related_module) {
	if($productid != "" && $crmid != "" && $related_module != "") {
		$newproductid = duplicateProduct($productid);
		if($newproductid != "") {
			associateProduct($newproductid,$crmid,$related_module);
		}
	}
}

function GetControllante($accountid) {
	global $adb, $table_prefix;
	if($accountid != "") {
		$result = $adb->limitQuery("
			select
				".$table_prefix."_account_parent.accountid,
				".$table_prefix."_account_parent.accountname,
				".$table_prefix."_account.account_type
			from ".$table_prefix."_account
			inner join ".$table_prefix."_account ".$table_prefix."_account_parent on  ".$table_prefix."_account_parent.accountid = ".$table_prefix."_account.parentid
			where ".$table_prefix."_account.accountid = ".$accountid,0,1);
		if($result) {
			if($row = $adb->fetchByAssoc($result)) {
				return $row;
			} else return null;
		} else return null;
	} else return null;
}

function GetControllati($accountid) {
	if($accountid != "") {
		global $adb, $table_prefix;
		$result = $adb->query("SELECT
							".$table_prefix."_account.accountid,
							".$table_prefix."_account.accountname,
							".$table_prefix."_account.account_type
							FROM ".$table_prefix."_account
							INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_account.accountid
							INNER JOIN ".$table_prefix."_accountbillads ON ".$table_prefix."_accountbillads.accountaddressid = ".$table_prefix."_account.accountid
							LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
							LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
							WHERE
							".$table_prefix."_crmentity.deleted = 0
							AND ".$table_prefix."_account.parentid = ".$accountid." ORDER BY ".$table_prefix."_account.accountname"); //crmv@176252
		if($result) {
			$retval = Array();
			while($row = $adb->fetchByAssoc($result)) {
				$retval[] = $row;
			}
			return $retval;
		} else return null;
	} else return null;
}

function GetHierarchy($accountid) {
	$controllante = GetControllante($accountid);
	if($controllante) {
		$detail_url = "<a href=\"index.php?module=Accounts&action=DetailView&record=".$controllante['accountid']."&parenttab=Sales\"> ".$controllante['accountname']."</a>";
		$html  = "<ul class=\"uil\"><li>$detail_url</li>";
	} else {
		$html  = "<ul class=\"uil\"><li>".getTranslatedString('LBL_NO_PARENT_HIERARCHY','Accounts')."</li>";	//crmv@35750
	}
	$html .= "<ul class=\"uil\"><li>".getAccountName($accountid)."<ul>";
	$controllati = GetControllati($accountid);
	for($i=0;$i<count($controllati);$i++) {
		$detail_url = "<a href=\"index.php?module=Accounts&action=DetailView&record=".$controllati[$i]['accountid']."&parenttab=Sales\"> ".$controllati[$i]['accountname']."</a>";
		$html .= "<li>$detail_url</li>";
	}

	$html .= "</ul></ul></ul>";
	return $html;
}
//vtc e

//crmv@8820
function getOwnerId($crmid)
{
    global $log, $table_prefix;
    $log->debug("Entering getOwnerId(".$crmid.") method ...");
    $log->info("in getOwnerId ".$crmid);
    global $adb;
    if($crmid != '')
    {
        $sql = "select smownerid from ".$table_prefix."_crmentity where crmid=?";
        $result = $adb->pquery($sql, array($crmid));
        $smownerid = $adb->query_result($result,0,"smownerid");
    }
    $log->debug("Exiting getOwnerId method ...");
    return $smownerid;
}
function getLastName($userid)
{
    global $log, $table_prefix;
    $log->debug("Entering getLastName(".$userid.") method ...");
    $log->info("in getLastName ".$userid);
    global $adb;
    if($userid != '')
    {
        $sql = "select last_name from ".$table_prefix."_users where id=?";
        $result = $adb->pquery($sql, array($userid));
        $last_name = $adb->query_result($result,0,"last_name");
    }
    $log->debug("Exiting getLastName method ...");
    return $last_name;
}
//crmv@8820e

//crmv@9194
function get_rel_permissions($tabid){
	global $adb, $table_prefix;
	$sql = "select related_tabid,label from ".$table_prefix."_relatedlists where tabid = ?";
	$res = $adb->pquery($sql,Array($tabid));
	while($row = $adb->fetchByAssoc($res)){
		if(isPermitted(getTabModuleName($row['related_tabid']),'EditView','') == 'yes') {
			$ret_arr[$row['label']] = 1;
		}
		else $ret_arr[$row['label']] = 0;
	}
	return $ret_arr;
}
//crmv@9194e

//crmv@10488
function check_notification_scheduler($id){
	global $adb, $table_prefix;
	$sql ="select active from ".$table_prefix."_notifyscheduler where schedulednotificationid = ?";
	$res = $adb->pquery($sql,array($id));
	if ($res){
		$active = $adb->query_result($res,0,'active');
	}
	return $active;
}
//crmv@10488 e
//crmv@17613 crmv@64337
function get_navigation_values($list_query_count,$url_string,$currentModule,$type='',$forusers=false,$viewid = ''){
	require_once('include/ListView/ListView.php');
	global $adb,$app_strings,$list_max_entries_per_page,$current_user;
	$LVU = ListViewUtils::getInstance();
	$list_max_entries_per_page = $LVU->get_selection_options($currentModule, $noofrows, 'list');
	$queryMode = (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true');
	$start = ListViewSession::getRequestCurrentPage($currentModule, $list_query_count, $viewid, $queryMode);
	
	$doCount = PerformancePrefs::getBoolean('LIST_COUNT', true);
	if ($doCount) {
		$parameter = 'count(*) as cnt';
		if (!$list_query_count)
			return Zend_Json::encode(Array('nav_array'=>Array(),'rec_string'=>''));
		if (!$forusers){
			$mod_obj = CRMEntity::getInstance($currentModule);
			$mod_obj->getNonAdminAccessControlQuery($currentModule,$current_user);
		}
		$res = $adb->querySlave('ListViewCount',replaceSelectQuery($list_query_count,$parameter)); // crmv@185894
		if ($res){
			$noofrows = $adb->query_result($res,0,'cnt');
		}
	} else {
		// try yo get the rowcount from the session
		$limit_start_rec = ($start-1) * $list_max_entries_per_page;
		$skey = $currentModule.'_'.$viewid.'_list_nrows';
		if (VteSession::hasKey($skey)) {
			$sqlrows = VteSession::get($skey);
		} else {
			$list_result = $adb->limitQuerySlave('ListViewCount',$list_query_count,$limit_start_rec,$list_max_entries_per_page+1); // crmv@185894
			$sqlrows = $adb->num_rows($list_result);
		}
		if ($sqlrows == $list_max_entries_per_page+1) {
			// there is a next page
			$noofrows = $list_max_entries_per_page*($start+1);
		} else {
			// this is the last page
			$noofrows = $list_max_entries_per_page*($start-1) + $sqlrows;
		}
	}
	
	//crmv@29617
	if ($viewid != '') {
		$reload_notification_count = checkListNotificationCount($list_query_count,$current_user->id,$viewid,$noofrows);
	}
	//crmv@29617e
	$_REQUEST['noofrows'] = $noofrows;
	setLVSDetails($currentModule,$viewid,$noofrows,'noofrows');
	if(isPermitted($currentModule,'EditView','') == 'yes')
		$permitted = true;
	else
		$permitted = false;
			
	if ($noofrows == 0)
		return Zend_Json::encode(Array('nav_array'=>Array(),'rec_string'=>'','permitted'=>$permitted));
				
	if ($start > ceil($noofrows/$list_max_entries_per_page)) $start-=1; //crmv@15530
	$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);
	$limit_start_rec = ($start-1) * $list_max_entries_per_page;
	if ($doCount) $record_string = getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
	if ($noofrows >  $list_max_entries_per_page)
		$navigationOutput = $LVU->getTableHeaderSimpleNavigation($navigation_array,$url_string,$currentModule,$type,$viewid);
	else
		$navigationOutput = Array();
			
	return Zend_Json::encode(Array('nav_array'=>$navigationOutput,'rec_string'=>$record_string,'permitted'=>$permitted,'reload_notification_count'=>$reload_notification_count));	//crmv@29617
}
//crmv@17613e crmv@64337e
function get_allids($list_query_count,$ids_to_jump = false){
	require_once('include/ListView/ListView.php');
	global $adb,$app_strings,$list_max_entries_per_page,$currentModule,$current_user, $table_prefix;	//crmv@27096
	if ($forusers)
		$parameter = $table_prefix.'_users.id as crmid';
	else
		$parameter = $table_prefix."_crmentity.crmid";
	if (!$list_query_count)
		return Zend_Json::encode(Array('all_ids'=>false));
	//crmv@27096
	$mod_obj = CRMEntity::getInstance($currentModule);
	$mod_obj->getNonAdminAccessControlQuery($currentModule,$current_user);
	//crmv@27096e
	$query = replaceSelectQuery($list_query_count,$parameter);
	if ($ids_to_jump){
		$ids_to_jump = array_filter(explode(",",$ids_to_jump));
		$query.=" and $parameter not in (".implode(",",$ids_to_jump).")"; //crmv@160842
	}
	$res = $adb->query($query);
	//crmv@27096
	$all_ids = array();
	if ($res){
		while($row = $adb->fetchByAssoc($res)){
			$all_ids[] = $row['crmid'];
		}
	}
	saveListViewCheck($currentModule,$all_ids);
	return Zend_Json::encode(Array('all_ids'=>implode(';',$all_ids).';'));
	//crmv@27096e
}
//crmv@10759
//Make a count query
function replaceSelectQuery($query,$replace = "count(*) AS count",$group_by=false)
{
	// Remove all the \n, \r and white spaces to keep the space between the words consistent.
	// This is required for proper pattern matching for words like ' FROM ', 'ORDER BY', 'GROUP BY' as they depend on the spaces between the words.
	$query = preg_replace("/[\n\r\t]+/"," ",$query); //crmv@20049

	//Strip of the current SELECT fields and replace them by "select count(*) as count"
	// Space across FROM has to be retained here so that we do not have a clash with string "from" found in select clause
	//crmv@26753
	if (preg_match('/^\s*SELECT\s+distinct\s+/i', $query) && !preg_match('/ distinct /i', $replace)) { // there's a distinct // crmv@129940
		if (preg_match('/count\(\*\)/i', $replace)) { // is a count query
			// get select arguments
			$args = array();
			preg_match('/^\s*select\s+distinct\s+(.*?) from/i', $query, $args);
			if (count($args) > 1 && !empty($args[1])) {
				$listargs = explode(',', trim($args[1]));
				foreach ($listargs as $k=>$arg) {
					// search for a crmid
					//crmv@39729 parameter added
					if (stripos($arg, 'crmid') !== false) {
						if (preg_match('/.+ AS\s+(.+)/i',$replace,$matches) && !empty($matches[1])) {
							$replace = "COUNT(DISTINCT $arg) AS ".$matches[1];
						}else{
							$replace = "COUNT(DISTINCT $arg)";
						}
						break;
					}
					//crmv@39729 parameter added end
				}
			}
		} else { // not a count query
			$replace = "DISTINCT $replace";
		}
	}
	//crmv@26753e

	// change extract(xxx from...)
	$query = preg_replace('/extract\(([a-z]+)\s+from/i', 'EXTRACT(\1 EXTRAFROM', $query);
	
	$query = "SELECT $replace ".substr($query, stripos($query,' FROM '),strlen($query));
	
	// change it back
	$query = preg_replace('/extract\(([a-z]+) extrafrom/i', 'extract(\1 FROM', $query);

	//Strip of any "GROUP BY" clause
	//    if ($group_by){
	//    	if(stripos($query,'GROUP BY') > 0)
	//		$query = substr($query, 0, stripos($query,'GROUP BY'));
	//	}
	//Strip of any "ORDER BY" clause
	if(strripos($query,'ORDER BY') > 0)
	$query = substr($query, 0, strripos($query,'ORDER BY'));

	//That's it
	return( $query);
}
//crmv@10759 e

//crmv@208173
//Make a count query
function mkCountQuery($query)
{
    // Remove all the \n, \r and white spaces to keep the space between the words consistent.
    // This is required for proper pattern matching for words like ' FROM ', 'ORDER BY', 'GROUP BY' as they depend on the spaces between the words.
    $query = preg_replace("/[\n\r\s]+/"," ",$query);

    //Strip of the current SELECT fields and replace them by "select count(*) as count"
    // Space across FROM has to be retained here so that we do not have a clash with string "from" found in select clause
    //crmv@26753
    $replace = 'count(*)';
    if (preg_match('/SELECT\s+distinct\s+/i', $query)) { // there's a distinct
        // get select arguments
        $args = array();
        preg_match('/^\s*select\s+distinct\s+(.*) from/i', $query, $args);
        if (count($args) > 1 && !empty($args[1])) {
            $listargs = explode(',', trim($args[1]));
            foreach ($listargs as $k=>$arg) { // search for a crmid
                if (stripos($arg, 'crmid') !== false) {
                    $replace = "COUNT(DISTINCT {$arg})";
                    break;
                }
            }
        }
    }
    $query = "SELECT {$replace} AS count ".substr($query, stripos($query,' FROM '),strlen($query));
    //crmv@26753

    //Strip of any "ORDER BY" clause
    if(strripos($query,'ORDER BY') > 0)
        $query = substr($query, 0, strripos($query,'ORDER BY'));

    return( $query);
}
//crmv@208173e

//crmv@9587
function get_hidden_parenttab_array(){
	global $adb,$current_user,$table_prefix;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	//ds@23
	$sql = 'SELECT hidden,parenttabid FROM '.$table_prefix.'_parenttab';
	$res = $adb->query($sql);
	while($row = $adb->fetch_array($res)) {
		$id[$row['parenttabid']] = $row['hidden'];
	}
	//ds@23e
	return $id;
}
//crmv@9587 e
function getMemoryUsage($bytestoadd)
{
	return round((memory_get_usage() + $bytestoadd) / (1024*1024), 1);
}
function array_size($a){
    $size = 0;
    while(list($k, $v) = each($a)){
        $size += is_array($v) ? array_size($v) : strlen($v);
    }
    return $size;
}
function array_search_recursive($needle, $haystack){
    $path=array();
    foreach($haystack as $id => $val)
    {

         if($val === $needle)
              $path[]=$id;
         else if(is_array($val)){
             $found=array_search_recursive($needle, $val);
              if(count($found)>0){
                  $path[$id]=$found;
              }
          }
      }
      return $path;
}
function get_logo($mode){
	include_once('vteversion.php'); // crmv@181168
	global $enterprise_mode,$enterprise_project,$current_user,$theme;
	$logo_path = 'themes/logos/';
	if ($mode == 'favicon') {
		$extension = 'ico';
	} else {
		$extension = 'png';
	}
	
	$TU = ThemeUtils::getInstance($theme);
	$isDarkModePermitted = $TU->isDarkModePermitted($current_user); // crmv@187406
	
	if ($mode == 'project') {
		$logo_path.=$enterprise_project.".".$extension;
	// crmv@140887
	} elseif ($mode == 'toggle') {
		if ($isDarkModePermitted) {
			$logo_path .= $enterprise_mode . "_toggle_dm.png";
		} else {
			$logo_path .= $enterprise_mode . "_toggle.png";
		}
	// crmv@140887e
	// crmv@187403
	} elseif ($mode == 'login') {
		$logo_path .= $enterprise_mode . "_login.png";
	// crmv@187403e
	} elseif ($mode == 'header') {
		if ($isDarkModePermitted) {
			$logo_path .= $enterprise_mode . "_header_dm.png";
		} else {
			$logo_path .= $enterprise_mode . "_header.png";
		}
	} else {
		$logo_path.=$enterprise_mode."_".$mode.".".$extension;
	}
	return $logo_path;
}
function reflect_logo($mode){
	include_once('config.inc.php');
	global $reflection_logo;
	switch ($mode){
		case "rowspan":{
			return "3";
			break;
		}
		case "reflect":{
			if ($reflection_logo)
				return 'jQuery("#logo").reflect(1);';
			break;
		}
	}
}
function getEnterpriseProject() {
	global $enterprise_project;
	if (empty($enterprise_project)) {
		if (!VteSession::isEmpty('enterprise_project')) {
			$enterprise_project = VteSession::get('enterprise_project');
		} else {
			global $adb, $table_prefix;
			$result = $adb->query("SELECT enterprise_project FROM {$table_prefix}_version");
			VteSession::set('enterprise_project', $enterprise_project = $adb->query_result_no_html($result, 0, 'enterprise_project'));
		}
	}
	return $enterprise_project;
}
//crmv@23984
function get_merge_user_fields($module,$ajax=false){
	if (!$ajax){
		return Array();
	}
	global $adb,$current_user, $table_prefix;
	if (isPermitted($module,'DuplicatesHandling','') != 'yes') return Array();
	$module_tabid = getTabid($module);
	if($module_tabid =='' || $current_user->id =='')
		return Array();

	$sql="SELECT
			  ".$table_prefix."_field.fieldid,
			  ".$table_prefix."_field.tablename,
			  ".$table_prefix."_field.columnname,
			  ".$table_prefix."_field.fieldname,
			  ".$table_prefix."_field.fieldlabel,
			  ".$table_prefix."_field.uitype
			FROM ".$table_prefix."_user2mergefields
			  INNER JOIN ".$table_prefix."_field
			    ON ".$table_prefix."_field.fieldid = ".$table_prefix."_user2mergefields.fieldid
			WHERE ".$table_prefix."_user2mergefields.tabid = ?
			    AND ".$table_prefix."_user2mergefields.userid = ?
			    AND ".$table_prefix."_user2mergefields.visible = 1";
	$params=array($module_tabid,$current_user->id);
	$res=$adb->pquery($sql,$params);
	$num_rows = $adb->num_rows($res);
	$user_profileid = fetchUserProfileId($current_user->id);
	$permitted_list = getProfile2FieldPermissionList($module, $user_profileid);
	$sql_def_org="select fieldid from ".$table_prefix."_def_org_field where tabid=? and visible=0";
	$result_def_org=$adb->pquery($sql_def_org,array($module_tabid));
	$num_rows_org=$adb->num_rows($result_def_org);
	$permitted_org_list = Array();
	for($i=0; $i<$num_rows_org; $i++){
		$permitted_org_list[$i] = $adb->query_result_no_html($result_def_org,$i,"fieldid");
	}
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	$fieldname = Array();
	for($i=0; $i<$num_rows;$i++)
	{
		$field_id = $adb->query_result_no_html($res,$i,"fieldid");
		$field_colname = $adb->query_result_no_html($res,$i,"columnname");
		$field_fieldname = $adb->query_result_no_html($res,$i,"fieldname");
		$field_tablename = $adb->query_result_no_html($res,$i,"tablename");
		$field_fieldlabel = $adb->query_result_no_html($res,$i,"fieldlabel");
		$field_uitype= $adb->query_result_no_html($res,$i,"uitype");
		foreach($permitted_list as $field=>$data)
			if($data[4] == $field_id and $data[1] == 0)
			{
				if($is_admin == 'true' || (in_array($field_id,$permitted_org_list)))
				{
					$fieldname[] = Array(
						'fieldname' => $field_fieldname,
						'columnname' => $field_colname,
						'tablename' => $field_tablename,
						'fieldlabel' => $field_fieldlabel,
						'uitype' => $field_uitype,
					);
				}
			}
	}
	return $fieldname;
}
//crmv@23984e
//crmv@171949
function check_duplicate($module,$fieldvalues,$record=''){
	global $adb, $current_user;
	$record = intval($record); // crmv@188808
	$data = array();
	$queryGenerator = QueryGenerator::getInstance($module, $current_user);
	$queryGenerator->initForAllCustomView();
	$queryGenerator->setFields(array('id'));
	$fieldvalues = Zend_Json::decode($fieldvalues);
	$fieldvalues = array_filter($fieldvalues);
	foreach($fieldvalues as $arr){
		$queryGenerator->addConditionGlue(QueryGenerator::$AND);
		$queryGenerator->addCondition($arr['fieldname'], $arr['value'], 'e');
		$data[getTranslatedString($arr['fieldlabel'],$module)] = $arr['value'];
	}
	if ($record > 0) { // crmv@188808
		$obj = CRMEntity::getInstance($module);
		$queryGenerator->appendToWhereClause("and {$obj->table_name}.{$obj->table_index} <> $record");
	}
	$query = $queryGenerator->getQuery();
	$result = $adb->limitQuery($query,0,1);
	if ($adb->num_rows($result) == 0) {
		$data = array();
	}
	return $data;
}
//crmv@171949e
function getIP() {
	if (getenv("HTTP_CLIENT_IP"))
		$ip = getenv("HTTP_CLIENT_IP");
	else if(getenv("HTTP_X_FORWARDED_FOR"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if(getenv("REMOTE_ADDR"))
		$ip = getenv("REMOTE_ADDR");
	else
		$ip = "UNKNOWN";
	return $ip;
}
function convert_to_old_condition($cond){
	switch($cond){
		case 'e':
			return 'is';
		case 'n':
			return 'isn';
		case 's':
			return 'bwt';
		case 'ew':
			return 'ewt';
		case 'c':
			return 'cts';
		case 'k':
			return 'dcts';
		case 'l':
			return 'lst';
		case 'g':
			return 'grt';
		case 'm':
			return 'lsteq';
		case 'h':
			return 'grteq';
	}
	return $cond;
}
//crmv@19370
function replaceSelectQueryFromList($module,$instance,$query){
	global $adb,$current_user,$currentModule, $table_prefix;	//crmv@16532
//crmv@19370e
	$queryGenerator = QueryGenerator::getInstance($module, $current_user);
	$fields_to_jump =  Array('access_count','filename','idlists');
	$moduleFields = $queryGenerator->getModuleFields();
	$fields = array_values(array_diff($instance->list_fields_name,$fields_to_jump));
	$field_list = [];
	foreach ($moduleFields as $name){
		$fname = $name->getFieldName();
		if (in_array($fname,$fields) && strpos($query,$name->getTableName())!==false && !($module == 'Calendar' && ($name->getTableName() == $table_prefix.'_activity'))){
			$field_list[] = $fname;
		}
	}
	$queryGenerator->setFields($field_list);
	$columns = $queryGenerator->getQuery(true);
	//crmv@36529
	$dropped_fields = array_diff($fields,$field_list);
	if (!empty($dropped_fields)){
		foreach ($dropped_fields as $field_dropped){
			$key = array_search($field_dropped,$instance->list_fields_name);
			if ($key!== false && is_array($instance->list_fields[$key])){
				$arr = getRelationTables($currentModule,$module);
				if (is_array($arr) && in_array(key($instance->list_fields[$key]),array_keys($arr))){
					$columns[] = $table_prefix.'_'.$instance->list_fields[$key][0].".".$field_dropped; // crmv@203132
				}
				$currentObject = CRMEntity::getInstance($currentModule);
				if (method_exists($currentObject,'setRelationTablesAlt')){
					$arr = $currentObject->setRelationTablesAlt($module);
					if (is_array($arr) && in_array(key($instance->list_fields[$key]),array_keys($arr))){
						$columns[] = $table_prefix.'_'.$instance->list_fields[$key][0].".".$field_dropped; // crmv@203132
					}
				}
			}
		}
	}
	//crmv@36529 e
	//crmv@18124
	if ($module == 'Calendar'){
		//crmv@33982 crmv@54924 crmv@184477
		if (in_array($table_prefix.'_seactivityrel.crmid as parent_id',$columns)) {
			$columns[array_search($table_prefix.'_seactivityrel.crmid as parent_id',$columns)] = $table_prefix.'_seactivityrel.crmid AS "activity_related_to"';
		}
		//crmv@33982e crmv@54924e crmv@184477e
		if (!in_array($table_prefix.'_activity.*',$columns)){
			foreach($columns as $key=>$value){
				if(strpos($value,$table_prefix.'_activity') !== false){
					unset($columns[$key]);
				}
			}
			$columns[] = $table_prefix.'_activity.*';
		}
		//crmv@31420
		if (!in_array($table_prefix.'_contactdetails.lastname',$columns) && strpos($table_prefix.'_contactdetails',$query)!==false){
			$columns[] = $table_prefix.'_contactdetails.lastname';
		}
		if (!in_array($table_prefix.'_contactdetails.firstname',$columns) && strpos($table_prefix.'_contactdetails',$query)!==false){
			$columns[] = $table_prefix.'_contactdetails.firstname';
		}
		//crmv@31420e
		//crmv@55205
		if (!in_array($table_prefix.'_contactdetails.contactid',$columns) && strpos(substr($query,stripos($query,' from ')), $table_prefix.'_contactdetails') !== false) {
			$columns[] = $table_prefix.'_contactdetails.contactid';
		}
		//crmv@55205e
	}
	//crmv@16532
	if ($currentModule == 'Campaigns' && in_array($module,array('Accounts','Contacts','Leads'))){
		$columns []= $table_prefix.'_campaignrelstatus.*';
	}
	//crmv@16532e
	//crmv@22863
	if($module == 'Timecards'){
		$columns []= $table_prefix.'_timecards.product_id';
	}
	//crmv@22863e
	//crmv@18001
	if ($module == 'Documents') {
		if (!in_array($table_prefix.'_notes.filename',$columns))
			$columns []= $table_prefix.'_notes.filename';
		if (!in_array($table_prefix.'_notes.folderid',$columns))
			$columns []= $table_prefix.'_notes.folderid';
		if (!in_array($table_prefix.'_notes.filelocationtype',$columns))
			$columns []= $table_prefix.'_notes.filelocationtype';
		if (!in_array($table_prefix.'_notes.filestatus',$columns))
			$columns []= $table_prefix.'_notes.filestatus';
	}
	//crmv@18001e
	//crmv@171021
	if (in_array($table_prefix.'_crmentity',$instance->tab_name) && !in_array($table_prefix.'_crmentity.crmid',$columns))
		$columns[] = $table_prefix.'_crmentity.crmid';
	elseif (!in_array($table_prefix.'_crmentity',$instance->tab_name) && !in_array($instance->table_name.'.'.$instance->table_index,$columns))
		$columns[] = $instance->table_name.'.'.$instance->table_index.' as "crmid"';
	//crmv@171021e
	return replaceSelectQuery($query,implode(",",$columns));
	//crmv@18124 end
}
//crmv@18338
/**
 * A function for making time periods readable
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     2.0.0
 * @link        http://aidanlister.com/2004/04/making-time-periods-readable/
 * @param       int     number of seconds elapsed
 * @param       string  which time periods to display
 * @param       bool    whether to show zero time periods
 */
function time_duration($seconds, $use = null, $zeros = false,$short=true)
{
	$segments = [];
	$array = [];
    // Define time periods
    if (!$short){
    	$periods = array (
        'years'     => 31556926,
        'Months'    => 2629743,
        'weeks'     => 604800,
        'days'      => 86400,
        'hours'     => 3600,
        'minutes'   => 60,
        'seconds'   => 1
        );
        $space = " ";
        if ($seconds <= 0) return "0 seconds";
    }
    else {
    $periods = array (
        'Y'     => 31556926,
        'M'    => 2629743,
        'w'     => 604800,
        'd'      => 86400,
        'h'     => 3600,
        'm'   => 60,
        's'   => 1
        );
        $space = "";
        if ($seconds <= 0) return "0s";
    }
    // Break into periods
    $seconds = (float) $seconds;
    foreach ($periods as $period => $value) {
        if ($use && strpos($use, $period[0]) === false) {
            continue;
        }
        $count = floor($seconds / $value);
        if ($count == 0 && !$zeros) {
            continue;
        }
        $segments[$period] = $count; //crmv@55210
        $seconds = $seconds % $value;
    }
    // Build the string
    foreach ($segments as $key => $value) {
    	if (!$short){
        	$segment_name = substr($key, 0, -1);
    	}
        else {
        	$segment_name = $key;
        }
        $segment = $value.$space.$segment_name;
        if (!$short){
	        if ($value != 1) {
	            $segment .= 's';
	        }
        }
        $array[] = $segment;
    }

    $str = implode(' ', $array);
    return $str;
}
//crmv@18338 end
//crmv@18592
function getParentTabs()
{
    global $adb,$table_prefix;
    $return = [];
    $sql = 'SELECT * FROM '.$table_prefix.'_parenttab ORDER BY sequence';
    $result = $adb->query($sql);
    while($row = $adb->fetch_array($result))
        $return[$row['parenttabid']] = array('parenttab_label'=>$row['parenttab_label'],'hidden'=>$row['hidden']);

    return $return;
}
function getMenuLayout() {	/* crmv@33465 crmv@47905bis */
	global $adb;
	if (!$adb->table_exist('tbl_s_menu')){	
		return array();
	}
	$cache = Cache::getInstance('getMenuLayout');
	$return = $cache->get();
	if ($return === false) {
		$result = $adb->query('select type from tbl_s_menu');
		if ($result) {
			$return = array('type'=>$adb->query_result($result,0,'type'));
			$cache->set($return);
		}
	}
	return $return;
}
function getMenuModuleList($otherAll=false) {	/* crmv@30356	crmv@32217	crmv@31250	crmv@42707	crmv@47905bis */

	global $adb,$table_prefix;
	require('user_privileges/requireUserPrivileges.php');	
	
	$cache = Cache::getInstance('getMenuModuleList');
	$tmp = $cache->get();
	if ($tmp === false) {
		$tmp = array();
		$sql = 'SELECT '.$table_prefix.'_tab.tabid,'.$table_prefix.'_tab.name,tbl_s_menu_modules.fast,tbl_s_menu_modules.sequence FROM '.$table_prefix.'_tab
			INNER JOIN (SELECT DISTINCT tabid FROM '.$table_prefix.'_parenttabrel) parenttabrel ON parenttabrel.tabid = '.$table_prefix.'_tab.tabid
			LEFT JOIN tbl_s_menu_modules ON '.$table_prefix.'_tab.tabid = tbl_s_menu_modules.tabid
			WHERE '.$table_prefix.'_tab.presence = 0
			ORDER BY tbl_s_menu_modules.fast, tbl_s_menu_modules.sequence';
		$res = $adb->query($sql);
		while($row=$adb->fetchByAssoc($res)) {
			$tmp[$row['tabid']] = $row;
		}
		$cache->set($tmp);
	}
	$module_list = array();
	foreach($tmp as $tabid => $info) {
		if ($profileGlobalPermission[2] == 0 || $profileGlobalPermission[1] == 0 || $profileTabsPermission[$tabid] == 0) {
         	$module_list[$tabid] = $info;
		}
	}
	$max_menu = 0;
	$module_list_fast = array();
	$module_list_other = array();
	foreach($module_list as $id => $info) {
		$info['index_url'] = "index.php?module={$info['name']}&action=index";
		// modules without list
		if (!in_array($info['name'], array('Home'))) {
			$info['list_url'] = $info['index_url'];
		}
		// modules without standard EditView
		if (!in_array($info['name'], array('Home', 'Messages', 'PDFMaker', 'Rss', 'Sms', 'Fax', 'RecycleBin', 'Charts', 'Portal'))) {//crmv@208472
            $info['create_url'] = "index.php?module={$info['name']}&action=EditView";
		}
		// custom editview
		if ($info['name'] == 'Messages') {
			$info['create_url'] = "index.php?module=Emails&action=EmailsAjax&file=EditView";
		}
		$mod_transl = getTranslatedString($info['name'],$info['name']);
		$app_transl = getTranslatedString($info['name'],'APP_STRINGS');
		if (isMobile()){
			if ($info['fast'] == 1 && $max_menu < 5) {
				$module_list_fast[] = $info;
				$max_menu ++;
			} else {
				($app_transl === $info['name'] || $info['name'] === 'PBXManager') ? $info['translabel'] = $mod_transl : $info['translabel'] = $app_transl;
				if ($mod_transl != $info['name'] && $mod_transl != $app_transl) $info['translabel'] = $mod_transl;
				
				$module_list_other[] = $info;
			}
		} else {
			($app_transl === $info['name'] || $info['name'] === 'PBXManager') ? $info['translabel'] = $mod_transl : $info['translabel'] = $app_transl;
			if ($mod_transl != $info['name'] && $mod_transl != $app_transl) $info['translabel'] = $mod_transl;
			
			if ($info['fast'] == 1) {
				$module_list_fast[] = $info;
			}
			if ($otherAll) {
				$module_list_other[] = $info;
			} else {
				if ($info['fast'] != 1) {
					$module_list_other[] = $info;
				}
			}
		}
	}
	usort($module_list_other, function($a, $b) {
		return ($a['translabel'] > $b['translabel']);
	});
	return array($module_list_fast,$module_list_other);
}
//crmv@18592e
//crmv@20211	crmv@46109	crmv@59867
function calculateCalColor() {
	global $adb,$table_prefix;
	$query = 'SELECT tbl_s_cal_color.id AS "idcolor", color, COUNT(color) AS conteggio
				FROM tbl_s_cal_color
				INNER JOIN '.$table_prefix.'_users ON tbl_s_cal_color.color = '.$table_prefix.'_users.cal_color
				GROUP BY tbl_s_cal_color.color, tbl_s_cal_color.id
				UNION
				SELECT tbl_s_cal_color.id AS "idcolor", color, 0 AS conteggio
				FROM tbl_s_cal_color
				WHERE color NOT IN( SELECT color
									FROM tbl_s_cal_color
									INNER JOIN '.$table_prefix.'_users ON tbl_s_cal_color.color = '.$table_prefix.'_users.cal_color
									GROUP BY tbl_s_cal_color.color)
				ORDER BY conteggio,"idcolor"';
	$result = $adb->limitQuery($query,0,1);
	if ($result)
		return $adb->query_result($result,0,'color');
}
//crmv@20211e	crmv@46109e	crmv@59867e
//crmv@23515	crmv@52561
function getCalendarRelatedToModules($skip_modules=true) {
	global $adb,$table_prefix;
	$moduleInstance = Vtecrm_Module::getInstance('Calendar');
	$query = "SELECT ".$table_prefix."_tab.name FROM ".$table_prefix."_relatedlists
				INNER JOIN ".$table_prefix."_tab ON ".$table_prefix."_relatedlists.tabid = ".$table_prefix."_tab.tabid
				WHERE related_tabid = ".$moduleInstance->id." AND ".$table_prefix."_relatedlists.name IN ('get_activities','get_history')";
	if ($skip_modules) {
		$query .= " AND ".$table_prefix."_tab.name <> 'Contacts'";
	}
	$query .= " GROUP BY ".$table_prefix."_tab.name";
	$result = $adb->query($query);
	$modules = array();
	while($row=$adb->fetchByAssoc($result)) {
		$modules[] = $row['name'];
	}
	return $modules;
}
//crmv@23515e	crmv@52561e
function isModuleInstalled($module) {	/* crmv@22700	crmv@27624	crmv@25671	crmv@47905bis */
	global $adb,$table_prefix;
	static $rcache = array();
	
	if (isset($rcache[$module])) return $rcache[$module]; // crmv@162449
	if (empty($adb) || !Vtecrm_Utils::CheckTable($table_prefix.'_tab')) return false;
	
	// these modules are not in vte_tab, but they are always present
	if (in_array($module, array('Administration','com_workflow','CustomView','Help','Import','PickList','Picklistmulti','Settings','Yahoo'))) return true;//crmv@207901

	$cache = Cache::getInstance('installed_modules');
	$installed_modules = $cache->get();
	if ($installed_modules === false) {
		$installed_modules = array();
		$result = $adb->query("SELECT name FROM {$table_prefix}_tab");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$installed_modules[] = $row['name'];
			}
		}
		$cache->set($installed_modules);
	}
	
	// crmv@162449
	$rcache[$module] = in_array($module,$installed_modules);
	return $rcache[$module];
	// crmv@162449e
}
//crmv@24568
function get_short_language(){
	$langs = explode('_',VteSession::get('authenticated_user_language'));
	return $langs[0];
}
//crmv@24568e
//crmv@24715
function fix_query_advanced_filters($module,&$query,$columns_to_check=false,$scope=''){ // crmv@129114
	global $adb,$table_prefix;
	if ($columns_to_check === false){
		$columns_to_check = Zend_Json::decode(getAdvancedresList($module,'columns'));
	}
	$tabid = getTabid($module); // crmv@167125
	$query = preg_replace("/[\n\r\t]+/"," ",$query); //crmv@20049
	if (is_array($columns_to_check)) {
		foreach ($columns_to_check as $_to_split){
			$splitted=explode(":",$_to_split);
			$tablename = trim($splitted[0]);
			$columnname = trim($splitted[1]);
			$fieldname = trim($splitted[2]); //crmv@31423
			$uitype = 10; // crmv@167125 - changed later
			if ($columnname == 'smownerid') $found_users = true; //crmv@82220
			//crmv@72942 crmv@184929
			$sql = "SELECT {$table_prefix}_entityname.tablename, {$table_prefix}_entityname.fieldname, {$table_prefix}_field.fieldid
				FROM {$table_prefix}_field 
				INNER JOIN {$table_prefix}_fieldmodulerel ON {$table_prefix}_field.fieldid = {$table_prefix}_fieldmodulerel.fieldid 
				INNER JOIN {$table_prefix}_entityname ON {$table_prefix}_entityname.modulename = {$table_prefix}_fieldmodulerel.relmodule
				WHERE {$table_prefix}_field.tablename = ? AND {$table_prefix}_field.columnname = ? AND {$table_prefix}_field.fieldname = ? AND {$table_prefix}_field.uitype = ?";
			$result = $adb->pquery($sql, array($tablename, $columnname, $fieldname, '10'));
			if ($result && $adb->num_rows($result) > 0) {
				$tablename = $adb->query_result($result, 0, 'tablename');
				$entity_fieldname = explode(',',$adb->query_result($result, 0, 'fieldname'));
				$entity_fieldname = $entity_fieldname[0];
				$fieldname = $entity_fieldname;
				$fieldid = $adb->query_result_no_html($result, 0, 'fieldid'); // crmv@184929
				// crmv@184929e
				$result = $adb->pquery("select columnname from {$table_prefix}_field where tablename=? and fieldname=?", array($tablename, $fieldname));
				if ($result && $adb->num_rows($result) > 0) {
					$columnname = $adb->query_result($result, 0, 'columnname');
				}
			// crmv@167125 - check for new user fields
			} else {
				$sql = 
					"SELECT f.uitype, fieldid
					FROM {$table_prefix}_field f
					WHERE f.tabid = ? AND f.fieldname = ?";
				$res = $adb->pquery($sql, array($tabid, $fieldname));
				if ($res && $adb->num_rows($res) > 0) {
					$uitype = intval($adb->query_result_no_html($res, 0, 'uitype'));
					$fieldid = intval($adb->query_result_no_html($res, 0, 'fieldid'));
				}
			}
			//crmv@72942e crmv@167125e
			
			if (!preg_match("/join\s+$tablename\s+on/i", $query) && !preg_match("/from\s+$tablename\s+/i", $query)) { // crmv@167791 crmv@168197
				$obj = CRMEntity::getInstance($module);
				$module_table=$obj->table_name;
				$module_pk=$obj->tab_name_index[$obj->table_name];
				//la tabella � del modulo corrente
				if (in_array($tablename,array_keys($obj->tab_name_index))){
					$join_check = " left join $tablename on $tablename.{$obj->tab_name_index[$tablename]} = $module_table.$module_pk ";
					// crmv@167125
					// check if it's a new user field
					$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array($tabid,$fieldname)),0);
					if ($wsfield->getFieldDataType() == 'reference') {
						$fieldid = $wsfield->getFieldId();
						$reflist = $wsfield->getReferenceList();
						if (in_array('Users', $reflist)) {
							$join_check .= "LEFT JOIN {$table_prefix}_users {$table_prefix}_users_fld_{$fieldid} ON {$table_prefix}_users_fld_{$fieldid}.id = $tablename.$columnname ";
							$join_check .= "LEFT JOIN {$table_prefix}_groups {$table_prefix}_groups_fld_{$fieldid} ON {$table_prefix}_groups_fld_{$fieldid}.groupid = $tablename.$columnname ";
						}
					}
					// crmv@167125e
					$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
				}
				else{
					//cerco il modulo collegato
					$sql = "SELECT ".$table_prefix."_tab.name FROM ".$table_prefix."_field
									INNER JOIN ".$table_prefix."_tab ON ".$table_prefix."_tab.tabid = ".$table_prefix."_field.tabid
									WHERE tablename=? and fieldname=?";
					$params = Array($tablename,$fieldname);
					$result = $adb->pquery($sql,$params);
					if ($result && $adb->num_rows($result)==1){
						$rel_module = $adb->query_result($result,0,'name');
						$rel_obj = CRMEntity::getInstance($rel_module);
						$relmodule_table=$obj->table_name;
						$relmodule_pk=$obj->tab_name_index[$obj->table_name];
						$tables=Array();
						$fields=Array();
						$reltables = getRelationTables($module,$rel_module);
						//							echo "<pre>";
						//							print_r($reltables);die;
						foreach($reltables as $key=>$value){
							$tables[]=$key;
							$fields[] = $value;
						}
						$relation_table = $tables[0];
						$relation_table1 = $tables[1];
						$prifieldname = $fields[0][0];
						$secfieldname = $fields[0][1];
						$relation_table1_key = $fields[1];
						if ($relation_table1){ //relazione n a n
							//TODO:gestire la tabella vte_crmentityrel!
							if (stripos($query," join $relation_table ")===false){
								$join_check = " left join $relation_table on $relation_table.$prifieldname = $module_table.$module_pk ";
								$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
							}
							$join_check = " left join $tablename on $tablename.{$rel_obj->tab_name_index[$tablename]} = $relation_table.$secfieldname ";
							$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
						}
						else{ //relazione 1 a n
							if (stripos($query," join $relation_table ")===false && stripos($query," from $relation_table ")===false){
								$join_check = " left join $relation_table on $relation_table.$prifieldname = $relmodule_table.$relmodule_pk ";
								$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
							}
							if ($tablename != $relmodule_table){
								$join_check = " left join $tablename on $tablename.{$rel_obj->tab_name_index[$tablename]} = $relation_table.$secfieldname ";
								$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
							}
						}
					}
				}

			}
			
			// crmv@167125 crmv@184929
			$newjoins = '';
			if (in_array($uitype, array(50,51,52,54)) && $fieldname != 'smownerid') {
				if (stripos($query," JOIN {$table_prefix}_users {$table_prefix}_users_fld_{$fieldid}") === false) { // crmv@203079
					$newjoins .= "LEFT JOIN {$table_prefix}_users {$table_prefix}_users_fld_{$fieldid} ON {$table_prefix}_users_fld_{$fieldid}.id = $tablename.$columnname ";
				}
				if (stripos($query," JOIN {$table_prefix}_groups {$table_prefix}_groups_fld_{$fieldid}") === false) { // crmv@203079
					$newjoins .= "LEFT JOIN {$table_prefix}_groups {$table_prefix}_groups_fld_{$fieldid} ON {$table_prefix}_groups_fld_{$fieldid}.groupid = $tablename.$columnname ";
				}
				
			// crmv@167125e
			} elseif ($uitype == 10) {
				$alias = "entityname_fld_".$fieldid;
				if (stripos($query,"join {$table_prefix}_entity_displayname {$alias}") === false) {
					$newjoins .= "LEFT JOIN {$table_prefix}_entity_displayname {$alias} ON {$alias}.crmid = {$splitted[0]}.{$splitted[1]} ";
				}
			}
			
			if ($newjoins) {
				$query = preg_replace('/\swhere\s/i', " $newjoins where ", $query, 1);
			}
			// crmv@184929e
		}
		//crmv@82220
		if ($found_users == true) {
			if (stripos($query," join {$table_prefix}_users")===false && stripos($query," from {$table_prefix}_users")===false){
				$join_check = " inner join {$table_prefix}_users on {$table_prefix}_users.id = {$table_prefix}_crmentity{$scope}.smownerid"; // crmv@129114
				$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
			}
			if (stripos($query," join {$table_prefix}_groups")===false && stripos($query," from {$table_prefix}_groups")===false){
				$join_check = " inner join {$table_prefix}_groups on {$table_prefix}_groups.groupid = {$table_prefix}_crmentity{$scope}.smownerid"; // crmv@129114
				$query = preg_replace('/\swhere\s/i', " $join_check where ", $query, 1);
			}
		}
		//crmv@82220e
	}
}
//crmv@24715e
//crmv@27096
function saveListViewCheck($module,$ids) {
	global $adb, $current_user;
	$moduleInstance = Vtecrm_Module::getInstance($module);
	$adb->pquery('delete from vte_listview_check where userid = ? and tabid = ?',array($current_user->id,$moduleInstance->id));
	if (!is_array($ids)) {
		if (strpos($ids,';') !== false) {
			$ids = explode(';',$ids);
		} elseif (strpos($ids,',') !== false) {
			$ids = explode(',',$ids);
		} else {
			$ids = array($ids);
		}
	}
	if (is_array($ids)) {
		$ids = array_filter($ids);
	}
	if (!empty($ids)) {
		foreach($ids as $id) {
			$adb->pquery('insert into vte_listview_check (userid,tabid,crmid) values (?,?,?)',array($current_user->id,$moduleInstance->id,$id));
		}
	}
}
function getListViewCheck($module) {
	global $adb, $current_user;
	$moduleInstance = Vtecrm_Module::getInstance($module);
	$result = $adb->pquery('SELECT crmid FROM vte_listview_check WHERE userid = ? AND tabid = ?',array($current_user->id,$moduleInstance->id));
	$ids = array();
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$ids[] = $row['crmid'];
		}
	}
	return $ids;
}
//crmv@27096e
//crmv@2043m
// returns field value from fieldid
function getFieldValue($fieldid, $crmid) {
	global $adb,$table_prefix;
	$res = $adb->pquery(
	  "select
	   ".$table_prefix."_tab.name as modulename,
	   fieldid, fieldname, tablename, columnname
	  from ".$table_prefix."_field
	   inner join ".$table_prefix."_tab on ".$table_prefix."_tab.tabid = ".$table_prefix."_field.tabid
	  where fieldid=? and ".$table_prefix."_field.presence in (0,2)",
	array($fieldid)
	);
	if ($res && $adb->num_rows($res) > 0) {

		$row = $adb->FetchByAssoc($res, -1, false);
		$focus = CRMEntity::getInstance($row['modulename']);
		if (empty($focus)) return null;

		$indexname = $focus->tab_name_index[$row['tablename']];
		if (empty($indexname)) return null;

		if ($row['tablename'] != $table_prefix.'_crmentity') {
			$join = "inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = {$row['tablename']}.$indexname";
		} else {
			$join = "";
		}
		$res2 = $adb->query("select {$row['tablename']}.{$row['columnname']} as fieldval from {$row['tablename']} $join where ".$table_prefix."_crmentity.deleted = 0 and {$row['tablename']}.$indexname = $crmid"); // crmv@42752
		if ($res2) {
			$value = $adb->query_result($res2, 0, 'fieldval');
			return $value;
		}
	}
	return null;
}
//crmv@2043me
//crmv@27711
function getHideTab($mode='all') {
	global $adb;
	$result = $adb->query('select * from vte_hide_tab');
	$return = array();
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$tabid = $row['tabid'];
			$hide_module_manager = $row['hide_module_manager'];
			if ($hide_module_manager == '1') {
				$return['hide_module_manager'][] = $tabid;
			}
			$hide_profile = $row['hide_profile'];
			if ($hide_profile == '1') {
				$return['hide_profile'][] = $tabid;
			}
			$hide_report = $row['hide_report'];
			if ($hide_report == '1') {
				$return['hide_report'][] = $tabid;
			}
		}
	}
	if (!empty($return)) {
		if ($mode != 'all') {
			$return = $return[$mode];
		}
	}
	return $return;
}
//crmv@27711e
//crmv@29079
function getDefaultUserAvatar($mode='') {
	global $theme;
	if ($mode == 'menu') {
		return resourcever("no_avatar_$mode.png");
	} else {
		return resourcever('no_avatar.png');
	}
}
function getUserAvatar($id,$mode='') {
	global $current_user, $table_prefix;
	$avatar = '';
	if ($id == $current_user->id) {
		$avatar = $current_user->column_fields['avatar'];
	} elseif ($id != '') {
		global $adb;
		$result = $adb->pquery('SELECT avatar FROM '.$table_prefix.'_users WHERE id = ?',array($id));
		if ($result && $adb->num_rows($result) > 0) {
			$avatar = $adb->query_result($result,0,'avatar');
		}
	}
	if ($avatar == '') {
		$avatar = getDefaultUserAvatar($mode);
	}
	return $avatar;
}
function getUserAvatarImg($id,$params='',$mode='') {
	$imgpath = getUserAvatar($id,$mode);
	if (!empty($imgpath)) {
		$name = getUserFullName($id);
		return "<img src=\"$imgpath\" alt=\"$name\" title=\"$name\" class=\"userAvatar\" border=\"0\" {$params}>";
	}
}
function getSingleModuleName($module,$record='') {
	if ($module == 'Calendar' && $record != '') {
		global $adb,$table_prefix;
		$result = $adb->pquery('SELECT activitytype FROM '.$table_prefix.'_activity WHERE activityid = ?',array($record));
		if ($result && $adb->num_rows($result) > 0) {
			$activitytype = getTranslatedString($adb->query_result($result,0,'activitytype'),$module);
		}
		if ($activitytype != '') {
			return $activitytype;
		}
	}
	$single_module = getTranslatedString('SINGLE_'.$module,$module);
	if (in_array($single_module,array('','SINGLE_'.$module))) {
		$single_module = getTranslatedString($module,$module);
	}
	return $single_module;
}
//crmv@29079e
//crmv@100731
function getGroupAvatar($id='',$mode='') {
	$avatar = getDefaultGroupAvatar($mode);
	return $avatar;
}
function getDefaultGroupAvatar($mode='') {
	return resourcever('ico-groups_small.png');
}
//crmv@100731e
//crmv@29615
function getDisplayFieldName($uitype,$fieldname) {
	if ($uitype == '10')
		$fieldname = $fieldname.'_display';
	elseif ($uitype == '77')
		$fieldname = 'assigned_user_id1';
	elseif ($uitype == '357')
		$fieldname = 'parent_name';
	elseif ($uitype == '98')
		$fieldname = 'role_name';
	elseif ($uitype == '30')
		$fieldname = 'set_reminder';
	return $fieldname;
}
//crmv@29615e
//crmv@30356
function isMobile() {
	if (!VteSession::hasKey('isMobileClient')) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$mobile_agents = Array(
			"acer",
			"alcatel",
			"android",
			"applewebkit/525",
			"applewebkit/532",
			"asus",
			"blackberry",
			"hitachi",
			"htc",
			"huawei",
			"ipad",
			"ipaq",
			"ipod",
			"lg",
			"nintendo",
			"nokia",
			"panasonic",
			"philips",
			"phone",
			"playstation",
			"sanyo",
			"samsung",
			"sharp",
			"siemens",
			"sony",
			"symbian",
			"tablet",
			"toshiba",
		);
		$is_mobile = false;
		foreach ($mobile_agents as $device) {
			if (stristr($user_agent, $device)) {
				$is_mobile = true;
				break;
			}
		}
		/*
		if(array_search ($user_agent,$mobile_agents)){
			$is_mobile = true;
		}
		*/
		VteSession::set('isMobileClient', $is_mobile);
	}
	return VteSession::get('isMobileClient');
}
//crmv@30356e
//crmv@17001
function getCalendarColors() {
	global $adb,$current_user,$table_prefix;
	$arr = array();
	$i = 0;
	$arr[$current_user->id] = $i;
	//$res = $adb->query("SELECT id FROM vte_users WHERE id <> ".$current_user->id);
	$res = $adb->query("SELECT id FROM ".$table_prefix."_users ");

	if ($res && $adb->num_rows($res)>0) {
		while($row = $adb->fetchByAssoc($res)) {
			//$i++; //crm@20211
			$arr[$row['id']] = $i++;
		}
	}
	return $arr;
}
function getUserColor($id) {
	global $calendar_colors;
	if ($calendar_colors == '') $calendar_colors = getCalendarColors();
	return $calendar_colors[$id];
}
//crmv@17001e
//crmv@30967
function getEntityFolder($folderid) {
	global $adb, $table_prefix;

	$res = $adb->pquery("select * from {$table_prefix}_crmentityfolder where folderid = ?", array($folderid));

	if ($res !== false) {
		return $adb->fetchByAssoc($res);
	} else {
		return false;
	}
}

function getEntityFoldersByName($foldername = null, $module = null) {
	global $adb, $table_prefix;

	$params = array();
	$conds = array();
	$sql = "select * from {$table_prefix}_crmentityfolder";

	if (!is_null($foldername) || !is_null($module)) {
		$sql .= ' where ';
	}

	if (!is_null($foldername)) {
		$conds[] = ' foldername = ? ';
		$params[] = $foldername;
	}

	if (!is_null($module)) {
		$conds[] = ' tabid = ? ';
		$params[] = getTabId($module);
	}

	$sql .= implode(' and ', $conds);
	$sql .= ' order by foldername';

	$res = $adb->pquery($sql, $params);

	if ($res !== false) {
		$ret = array();
		while ($row = $adb->fetchByAssoc($res)) {
			//crmv@90004
			$row['editable'] = (empty($row['state']) || $row['state'] == 'CUSTOMIZED');
			if (in_array($row['foldername'],array('Default','Message attachments','Esempi'))) $row['editable'] = false;	// TODO : usare attributo nella classe del modulo
			//crmv@90004e
			$ret[] = $row;
		}
		return $ret;
	} else {
		return false;
	}
}

function addEntityFolder($module, $foldername, $description = '', $creator = 1, $state = '', $sequence = 0) {
	global $adb, $table_prefix;

	$tabid = getTabid($module);
	// try to do a direct query
	if (empty($tabid)) {
		$res = $adb->pquery("select tabid from {$table_prefix}_tab where name = ?", array($module));
		if ($res && $adb->num_rows($res) > 0) $tabid = $adb->query_result($res, 0, 'tabid');
	}
	if (empty($tabid)) return false;

	$folderid = $adb->getUniqueID($table_prefix."_crmentityfolder");

	$params = array($folderid, $tabid, $creator, $foldername, $description, $state, $sequence);
	$res = $adb->pquery("insert into {$table_prefix}_crmentityfolder (folderid, tabid, createdby, foldername, description, state, sequence) values (".generateQuestionMarks($params).")", $params);
	if ($res !== false)
		return $folderid;
	else
		return false;
}

function editEntityFolder($folderid, $foldername, $description = null, $state = null) {
	global $adb, $table_prefix;

	$params = array($foldername);
	$sql = "update {$table_prefix}_crmentityfolder set foldername = ?";

	if (!is_null($description)) {
		$sql .= ', description = ?';
		$params[] = $description;
	}

	if (!is_null($state)) {
		$sql .= ', state = ?';
		$params[] = $state;
	}

	$sql .= " where folderid = ?";
	$params[] = $folderid;

	$res = $adb->pquery($sql, $params);

	if ($res !== false) return $folderid; else return false;
}

function deleteEntityFolder($folderid) {
	global $adb, $table_prefix;

	$res = $adb->pquery("delete from {$table_prefix}_crmentityfolder where folderid = ?", array($folderid));
	if ($res !== false)
		return $folderid;
	else
		return false;
}
//crmv@30967e

//crmv@34627
function getRelationFields($module, $relmodules, $crmid='', $filter_ids=array(), $reportid='') {
	global $adb, $table_prefix;
	static $return = array();

	if (empty($crmid) || (!empty($crmid) && !isset($return[$module][$relmodules][$crmid]))) {

		// special uitypes for relations
		$uitype_rel = array(
			// removed 51
			'57' => "Contacts",
			'58' => "Campaigns",
			'59' => "Products",
			// removed 73
			'75' => "Vendors",
			'81' => "Vendors",
			'76' => "Potentials",
			'78' => "Quotes",
			'80' => "SalesOrder",
			// '68' => "*", TODO: parenti_id in tickets
		);

		$ret = array();
		$fieldids = array();

		if (!is_array($relmodules)) $relmodules = array($relmodules);
		$relmodules_noself = array_diff($relmodules, array($module));

		$moduleid = getTabid($module);
		$rel_moduleid = array_map('getTabid', $relmodules);
		$rel_moduleid_noself = array_diff($rel_moduleid, array($moduleid));

		// first go with field-relations (solo uitype 10)
		// TODO: che succede se in relmodules c'� module?
		$params = array($module);
		$params = array_merge($params, $relmodules, array($module), $relmodules_noself);
		$query = '
			select
				'.$table_prefix.'_fieldmodulerel.fieldid, relmodule, 1 as "direct", '.$table_prefix.'_field.columnname, '.$table_prefix.'_field.tablename, '.$table_prefix.'_field.fieldname
			from '.$table_prefix.'_fieldmodulerel
			inner join '.$table_prefix.'_field on '.$table_prefix.'_field.fieldid = '.$table_prefix.'_fieldmodulerel.fieldid
			where
				module = ? and relmodule in ('.generateQuestionMarks($relmodules).')
			union
			select
				'.$table_prefix.'_fieldmodulerel.fieldid, module as "relmodule", 0 as "direct", '.$table_prefix.'_field.columnname, '.$table_prefix.'_field.tablename, '.$table_prefix.'_field.fieldname
			from '.$table_prefix.'_fieldmodulerel
			inner join '.$table_prefix.'_field on '.$table_prefix.'_field.fieldid = '.$table_prefix.'_fieldmodulerel.fieldid
			where
				relmodule = ? and module in ('.generateQuestionMarks($relmodules_noself).')';
		// add select for special uitypes (direct relation)
		$uilist = array_intersect($uitype_rel, $relmodules);
		if (count($uilist) > 0) {
			$casemod = 'case';
			foreach ($uilist as $ui=>$rmod) {
				$casemod .= " when {$table_prefix}_field.uitype = $ui then '$rmod'";
			}
			$casemod .= ' end';
			$query .= '
				union

				select
					'.$table_prefix.'_field.fieldid, '.$casemod.' as "relmodule", 1 as "direct", '.$table_prefix.'_field.columnname, '.$table_prefix.'_field.tablename, '.$table_prefix.'_field.fieldname
				from '.$table_prefix.'_field
					inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid = '.$table_prefix.'_field.tabid
				where
					'.$table_prefix.'_field.tabid = ?
					and '.$table_prefix.'_field.uitype in ('.generateQuestionMarks($uilist).')
			';
			$params[] = $moduleid;
			$params = array_merge($params, array_keys($uilist));
		}
		// special uitypes - (indirect relations)
		$uilist = array_intersect($uitype_rel, array($module));
		if (count($uilist) > 0) {
			$query .= "
				union

				select
					{$table_prefix}_field.fieldid, {$table_prefix}_tab.name as \"relmodule\", 0 as \"direct\", {$table_prefix}_field.columnname, {$table_prefix}_field.tablename, {$table_prefix}_field.fieldname
				from {$table_prefix}_field
					inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
				where
					{$table_prefix}_field.tabid in (".generateQuestionMarks($rel_moduleid_noself).")
					and {$table_prefix}_field.uitype in (".generateQuestionMarks($uilist).')
			';
			$params = array_merge($params, $rel_moduleid_noself);
			$params = array_merge($params, array_keys($uilist));
		}
//$startTime = microtime();
		$res = $adb->pquery($query, $params);
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				if ($row['direct']) {
					$fieldids['direct'][$row['fieldid']] = array('module'=>$row['relmodule'],'tablename'=>$row['tablename'],'columnname'=>$row['columnname'],'fieldname'=>$row['fieldname']);
				} else {
					// reverse relation, search for right value
					$ids = searchFieldValue($row['fieldid'], $crmid);
					if (!empty($ids) && !empty($filter_ids)) {
						$ids = array_values(array_intersect($ids,$filter_ids));
					}
					$fieldids['indirect'][$row['fieldid']] = array('module'=>$row['relmodule'],'tablename'=>$row['tablename'],'columnname'=>$row['columnname'],'fieldname'=>$row['fieldname']
						,'ids'=>$ids	// this can be slow!!
					);
				}
			}
		}
//$endTime = microtime();
//list($usec, $sec) = explode(" ", $endTime);
//$endTime = ((float)$usec + (float)$sec);
//list($usec, $sec) = explode(" ", $startTime);
//$startTime = ((float)$usec + (float)$sec);
//$deltaTime = round($endTime - $startTime,2);
//echo('<br />Field Query: '.$deltaTime.' seconds.<br />');

		//and finally with general relation table
		$params = array();
		$params = array_merge($params, array($module), $relmodules);
		$query = '
			select
				relcrmid, relmodule
			from '.$table_prefix.'_crmentityrel
			where
				module = ? and relmodule in ('.generateQuestionMarks($relmodules).')';
		if (!empty($crmid)) {
			$query .= ' and crmid = ?';
			$params[] = $crmid;
		}
		$params = array_merge($params, array($module), $relmodules);
		$query .= '
			union
				select
					crmid AS "relcrmid", module AS "relmodule"
				from '.$table_prefix.'_crmentityrel
				where
					relmodule = ? and module in ('.generateQuestionMarks($relmodules).')';
		if (!empty($crmid)) {
			$query .= ' and relcrmid = ?';
			$params[] = $crmid;
		}
		if (in_array('Calendar', $relmodules)) {
			$query .= "
				union
					select
						activityid as \"relcrmid\",
						\"Calendar\" as \"relmodule\"
					from {$table_prefix}_seactivityrel
						inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$table_prefix}_seactivityrel.activityid and {$table_prefix}_crmentity.deleted = 0
					where {$table_prefix}_crmentity.setype not in ('Emails')"; // crmv@152701
			if (!empty($crmid)) {
				$query .= " and {$table_prefix}_seactivityrel.crmid = ?";
				$params[] = $crmid;
			}
		}
		// TODO: gestire email/fax/sms
		if ($module == 'Products' || in_array('Products', $relmodules)) {
			$inventory_modules = getInventoryModules(); // crmv@64542
			if ($module == 'Products') {
				$reltable = "{$table_prefix}_seproductsrel";
				$relcrmid = "$reltable.crmid";
				$relmodule = "$reltable.setype";
				$wherecrmid = "$reltable.productid";
				$crmentity_join_id = "$reltable.productid";
				foreach($relmodules as $relmod) {
					if (in_array($relmod,$inventory_modules)) {
						$reltable = "{$table_prefix}_inventoryproductrel";
						$relcrmid = "$reltable.id";
						$relmodule = "{$table_prefix}_crmentity.setype";
						$wherecrmid = "$reltable.productid";
						$crmentity_join_id = "$reltable.id";
					}
				}
			} else {
				if (in_array($module,$inventory_modules)) {
					$reltable = "{$table_prefix}_inventoryproductrel";
					$relcrmid = "$reltable.productid";
					$relmodule = "\"Products\"";
					$wherecrmid = "$reltable.id";
					$crmentity_join_id = "$reltable.productid";
				} else {
					$reltable = "{$table_prefix}_seproductsrel";
					$relcrmid = "$reltable.productid";
					$relmodule = "\"Products\"";
					$wherecrmid = "$reltable.crmid";
					$crmentity_join_id = "$reltable.productid";
				}
			}
			$query .= "
					union
						select
							$relcrmid as \"relcrmid\",
							$relmodule as \"relmodule\"
						from $reltable
							inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $crmentity_join_id and {$table_prefix}_crmentity.deleted = 0";
			if (!empty($crmid)) {
				$query .= " and $wherecrmid = ?";
				$params[] = $crmid;
			}
		}
		if ($module == 'Documents' || in_array('Documents', $relmodules)) {
			if ($module == 'Documents') {
				$relcrmid = "{$table_prefix}_senotesrel.crmid";
				$relmodule = "{$table_prefix}_crmentity.setype";
				$wherecrmid = "notesid";
			} else {
				$relcrmid = "notesid";
				$relmodule = "\"Documents\"";
				$wherecrmid = "{$table_prefix}_senotesrel.crmid";
			}
			$query .= "
					union
						select
							$relcrmid as \"relcrmid\",
							$relmodule as \"relmodule\"
						from {$table_prefix}_senotesrel
							inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = $relcrmid and {$table_prefix}_crmentity.deleted = 0";
			if (!empty($crmid)) {
				$query .= " and $wherecrmid = ?";
				$params[] = $crmid;
			}
		}
		$query .= " ORDER BY relcrmid";
		// seticketsrel non serve pi�
		// anche {$table_prefix}_campaign*rel sono vecchie
//$startTime = microtime();
		$res = $adb->pquery($query,	$params);
		$related = array();
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				if (!in_array($row['relmodule'],$relmodules)) {
					continue;
				}
				if (empty($crmid)) {
					if (!in_array($row['relmodule'],$related))
						$related[] = $row['relmodule'];
				} else {
					$related[$row['relmodule']][] = $row['relcrmid'];
				}
				if (!empty($related[$row['relmodule']]) && !empty($filter_ids)) {
					$related[$row['relmodule']] = array_values(array_intersect($related[$row['relmodule']],$filter_ids));	//filtro i valori collegati al record con quelli che rispettano il filtro del report
				}
			}
		}
//$endTime = microtime();
//list($usec, $sec) = explode(" ", $endTime);
//$endTime = ((float)$usec + (float)$sec);
//list($usec, $sec) = explode(" ", $startTime);
//$startTime = ((float)$usec + (float)$sec);
//$deltaTime = round($endTime - $startTime,2);
//echo('Related Query: '.$deltaTime.' seconds.<br /><br />');
		$retrn = array('fields'=>$fieldids,'related'=>$related);
		if (empty($crmid)) {
			return $retrn;
		} else {
			$relmodules = $relmodules[0];
			$return[$module][$relmodules][$crmid] = $retrn;
		}
	}
	return $return[$module][$relmodules][$crmid];
}
function searchFieldValue($fieldid, $value) {
	global $adb, $table_prefix;
	static $fieldinfo = array();
	if (!isset($fieldinfo[$fieldid])) {
		$res = $adb->pquery(
			"select
				{$table_prefix}_tab.name as modulename,
				fieldid, fieldname, tablename, columnname
			from {$table_prefix}_field
				inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
			where fieldid=? and {$table_prefix}_field.presence in (0,2)",
			array($fieldid)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$fieldinfo[$fieldid] = $adb->FetchByAssoc($res, -1, false);
		}
	}
	if (isset($fieldinfo[$fieldid])) {
		$focus = CRMEntity::getInstance($fieldinfo[$fieldid]['modulename']);
		if (empty($focus)) return null;

		$indexname = $focus->tab_name_index[$fieldinfo[$fieldid]['tablename']];
		if (empty($indexname)) return null;

		$ret = array();
		if ($fieldinfo[$fieldid]['tablename'] != $table_prefix.'_crmentity') {
			$join = "inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$fieldinfo[$fieldid]['tablename']}.$indexname";
		} else {
			$join = "";
		}
		$res2 = $adb->pquery("select crmid from {$fieldinfo[$fieldid]['tablename']} $join where {$table_prefix}_crmentity.deleted = 0 and {$fieldinfo[$fieldid]['columnname']} = ?", array($value));
		if ($res2 && $adb->num_rows($res2) > 0) {
			while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
				$ret[] = $row2['crmid'];
			}
			return $ret;
		}
	}
	return null;
}
//crmv@34627e
//crmv@32334
function isZMergeAgent() {
	if (function_exists('getallheaders')) {
		$headers = getallheaders();
		if (is_array($headers) && !empty($headers['User-Agent']) && stripos($headers['User-Agent'], 'zMerge') !== false) {
			trackPlugin($headers['User-Agent']);	//crmv@54179
			return true;
		}
	}
	return false;
}
//crmv@32334e
//crmv@48267
function isVteSyncAgent() {
	if (function_exists('getallheaders')) {
		$headers = getallheaders();
		if (is_array($headers) && !empty($headers['User-Agent']) && stripos($headers['User-Agent'], 'VteSync') !== false) {
			trackPlugin($headers['User-Agent']);	//crmv@54179
			return true;
		}
	}
	return false;
}
//crmv@48267e
//crmv@37362
function setWinMaxStatus() {
	global $current_user;
	if ($current_user->column_fields['menu_view'] != '') {
		if (isMobile()) {
			$cookie_value = 'open';
		} elseif ($current_user->column_fields['menu_view'] == 'Large Menu') {
			$cookie_value = 'open';
		} elseif ($current_user->column_fields['menu_view'] == 'Small Menu') {
			$cookie_value = 'close';
		}
	} else {
		$cookie_value = 'open';
	}
	$_COOKIE['crmvWinMaxStatus'] = $cookie_value;
}
//crmv@37362e
// crmv@39110
function requestFromMobile() {
	return (preg_match('/^WSMobile.*/', VteSession::get('app_unique_key')) > 0);
}
// crmv@39110e
// crmv@142358 crmv@181168
function requestFromPortal() {
	return (preg_match('/vteservice.php$/', $_SERVER['SCRIPT_NAME']) > 0);
}
// crmv@142358e crmv@181168e
// crmv@91082
function requestFromWebservice() {
	return (preg_match('/webservice.php$/', $_SERVER['SCRIPT_NAME']) > 0);
}
// crmv@91082e
//crmv@49510
function getFinalTypeOfData($typeofdata, $mandatory) {
	$tmp = explode('~',$typeofdata);
	if ($tmp[1] != 'M' && $mandatory == '0') {
		$tmp[1] = 'M';
		$typeofdata = implode('~',$tmp);
	}
	return $typeofdata;
}
//crmv@49510e
//crmv@2043m	crmv@56233
function checkMailScannerInfoRule($row) {
	global $currentModule, $table_prefix;
	//crmv@2043m	crmv@OPER6053
	if (in_array($row->linklabel,array('Rispondi via mail','Rispondi via mail (info)', 'Answer by mail', 'Answer by mail (info)'))) { // crmv@104782
		$mailscanner = getSingleFieldValue($table_prefix.'_troubletickets', 'mailscanner_action', 'ticketid', $_REQUEST['record']);
		if (!empty($mailscanner)) {
			return true;
		}
	}
	//crmv@2043me	crmv@OPER6053e
	if ($row->linktype == 'DETAILVIEWBASIC' && $row->linklabel == 'LBL_DO_NOT_IMPORT_ANYMORE') {
		$linkurl = $row->linkurl;
		$linkurl = str_replace(",'DetailView');",'',$linkurl);
		$id = str_replace("doNotImportAnymore('HelpDesk',",'',$linkurl);
		if (is_numeric($id)) {
			$focus = CRMEntity::getInstance('HelpDesk');
			$focus->retrieve_entity_info($id,'HelpDesk');
			if (!empty($focus->column_fields['mailscanner_action']) && isPermitted($currentModule,'EditView',$id) && isPermitted($currentModule,'Delete',$id)) { //crmv@179057
				return true;
			}
		}
	} elseif ($row->linktype == 'LISTVIEWBASIC' && $row->linklabel == 'LBL_DO_NOT_IMPORT_ANYMORE') {
		if (isPermitted($currentModule, 'EditView', '') == 'yes' && isPermitted($currentModule, 'Delete', '') == 'yes') { //crmv@179057
			return true;
		}
	}
	return false;
}
//crmv@2043me	crmv@56233e
function preprint($arr,$die=false) {
	echo '<pre>';
	print_r($arr);
	echo '</pre>';
	if ($die) die;
}
//crmv@54179
function getInstalledPlugins() {
	$cache = Cache::getInstance('installed_plugins');
	$installed_plugins = $cache->get();
	if ($installed_plugins === false) {
		global $adb, $table_prefix;
		$installed_plugins = array();
		$result = $adb->query("SELECT name, last_check FROM {$table_prefix}_plugins_tracking");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$installed_plugins[$row['name']] = $row['last_check'];
			}
		}
		$cache->set($installed_plugins);
	}
	return $installed_plugins;
}
function trackPlugin($name) {
	global $adb, $table_prefix;
	$plugins = getInstalledPlugins();
	if (!isset($plugins[$name])) {
		$cache = Cache::getInstance('installed_plugins');
		$cache->clear();
		$adb->pquery("INSERT INTO {$table_prefix}_plugins_tracking (name,last_check) VALUES (?,?)",array($name,date('Y-m-d H:i:s')));
	}
}
//crmv@54179e

/**
 * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
 *
 * @param string $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param string $ending Ending to be appended to the trimmed string.
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 *
 * @return string Trimmed string.
 */
/* crmv@59626 */
function truncateHtml($text, $length = 100, $ending = '...', $considerHtml = true) {
	if ($considerHtml) {
		// if the plain text is shorter than the maximum length, return the whole text
		if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
			return $text;
		}
		// splits all html-tags to scanable lines
		preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
		$total_length = strlen($ending);
		$open_tags = array();
		$truncate = '';
		foreach ($lines as $line_matchings) {
			// if there is any html-tag in this line, handle it and add it (uncounted) to the output
			if (!empty($line_matchings[1])) {
				// if it's an "empty element" with or without xhtml-conform closing slash
				if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
					// do nothing
				// if tag is a closing tag
				} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
					// delete tag from $open_tags list
					$pos = array_search($tag_matchings[1], $open_tags);
					if ($pos !== false) {
						unset($open_tags[$pos]);
					}
				// if tag is an opening tag
				} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
					// add tag to the beginning of $open_tags list
					array_unshift($open_tags, strtolower($tag_matchings[1]));
				}
				// add html-tag to $truncate'd text
				$truncate .= $line_matchings[1];
			}
			// clean html-tag at the end of the line
			$line_matchings[2] = preg_replace('/(<(\s*[^>]+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s[^>]+?)?)>)+$/is', '', $line_matchings[2]);
			// calculate the length of the plain text part of the line; handle entities as one character
			$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
			if ($total_length+$content_length> $length) {
				// the number of characters which are left
				$left = $length - $total_length;
				$entities_length = 0;
				// search for html entities
				if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
					// calculate the real length of all entities in the legal range
					foreach ($entities[0] as $entity) {
						if ($entity[1]+1-$entities_length <= $left) {
							$left--;
							$entities_length += strlen($entity[0]);
						} else {
							// no more characters left
							break;
						}
					}
				}
				$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
				$tmp = substr($line_matchings[2], 0, $left+$entities_length);
				// maximum lenght is reached, so get off the loop
				break;
			} else {
				$tmp = $line_matchings[2];
				$truncate .= $line_matchings[2];
				$total_length += $content_length;
			}
			// if the maximum length is reached, get off the loop
			if($total_length >= $length) {
				break;
			}
		}
	} else {
		if (strlen($text) <= $length) {
			return $text;
		} else {
			$truncate = substr($text, 0, $length - strlen($ending));
		}
	}
	// add the defined ending to the text
	$truncate .= $ending;
	if($considerHtml) {
		// close all unclosed html-tags
		foreach ($open_tags as $tag) {
			$truncate .= '</' . $tag . '>';
		}
	}
	return $truncate;
}

//crmv@63483
function checkPermittedLink(&$instance) {
	$module = vtlib_purify($_REQUEST['module']);
	$record = vtlib_purify($_REQUEST['record']);
	
	if (in_array($instance->linklabel,array('LBL_ADD_DOCREVISION'))) {
		$permission = isPermitted($module,'EditView',$record);
		if (empty($mailscanner) && $permission == 'no') {
			return false;
		}
	}
	return true;
}
//crmv@63483e

//crmv@102334
function oldModeSessionModules() {
	// modules without tabs
	return array('Calendar','Users');
}
function getLVS($module,$param='',$modhomeid='') {
	$oldMode = oldModeSessionModules();
	if (in_array($module,$oldMode)) {
		if (empty($param)) {
			return VteSession::getArray(array('lvs', $module));
		} else {
			return VteSession::getArray(array('lvs', $module, $param));
		}
	} else {
		if (empty($modhomeid)) {
			require_once('include/utils/ModuleHomeView.php');
			global $current_user;
			$MHW = ModuleHomeView::getInstance($module, $current_user->id);
			$modhomeid = $MHW->getModHomeId(null, false); // crmv@123718
		}
		if (empty($param)) {
			return VteSession::getArray(array('lvs', $module, $modhomeid));
		} else {
			return VteSession::getArray(array('lvs', $module, $modhomeid, $param));
		}
	}
}
function setLVS($module,$value,$param='',$modhomeid='') {
	$oldMode = oldModeSessionModules();
	if (in_array($module,$oldMode)) {
		if (empty($param)) {
			VteSession::setArray(array('lvs', $module), $value);
		} else {
			VteSession::setArray(array('lvs', $module, $param), $value);
		}
	} else {
		if (empty($modhomeid)) {
			require_once('include/utils/ModuleHomeView.php');
			global $current_user;
			$MHW = ModuleHomeView::getInstance($module, $current_user->id);
			$modhomeid = $MHW->getModHomeId(null, false); // crmv@123718
		}
		if (empty($param)) {
			VteSession::setArray(array('lvs', $module, $modhomeid), $value);
		} else {
			VteSession::setArray(array('lvs', $module, $modhomeid, $param), $value);
		}
	}		
}
function unsetLVS($module='',$param='') {
	if (!empty($param)) {
		$oldMode = oldModeSessionModules();
		if (in_array($module,$oldMode)) {
			VteSession::setArray(array('lvs', $module, $param), '');
		} else {
			require_once('include/utils/ModuleHomeView.php');
			global $current_user;
			$MHW = ModuleHomeView::getInstance($module, $current_user->id);
			$modhomeid = $MHW->getModHomeId(null, false); // crmv@123718
			VteSession::setArray(array('lvs', $module, $modhomeid, $param), '');
		}
	} elseif (!empty($module))
		VteSession::removeArray(array('lvs', $module));
	else
		VteSession::remove('lvs');
}
function getLVSDetails($module,$viewid,$param='') {
	$oldMode = oldModeSessionModules();
	if (in_array($module,$oldMode)) {
		if (!empty($param)) {
			return VteSession::getArray(array('lvs', $module, $viewid, $param));
		} else {
			return VteSession::getArray(array('lvs', $module, $viewid));
		}
	} else {
		require_once('include/utils/ModuleHomeView.php');
		global $current_user;
		$MHW = ModuleHomeView::getInstance($module, $current_user->id);
		$modhomeid = $MHW->getModHomeId(null, false); // crmv@123718
		if (!empty($param)) {
			return VteSession::getArray(array('lvs', $module, $modhomeid, $viewid, $param));
		} else {
			return VteSession::getArray(array('lvs', $module, $modhomeid, $viewid));
		}
	}	
}
function setLVSDetails($module,$viewid,$value,$param='') {
	$oldMode = oldModeSessionModules();
	if (in_array($module,$oldMode)) {
		if (!empty($param)) {
			VteSession::setArray(array('lvs', $module, $viewid, $param), $value);
		} else {
			VteSession::setArray(array('lvs', $module, $viewid), $value);
		}
	} else {
		require_once('include/utils/ModuleHomeView.php');
		global $current_user;
		$MHW = ModuleHomeView::getInstance($module, $current_user->id);
		$modhomeid = $MHW->getModHomeId(null, false); // crmv@123718
		if (!empty($param)) {
			VteSession::setArray(array('lvs', $module, $modhomeid, $viewid, $param), $value);
		} else {
			VteSession::setArray(array('lvs', $module, $modhomeid, $viewid), $value);
		}
	}
}
//crmv@102334e

//crmv@104566
function getModuleImg($module, $style='') {
	return '<i class="icon-module icon-'.strtolower($module).'" data-first-letter="'.strtoupper(substr($module,0,1)).'"></i>';
}
//crmv@104566e
