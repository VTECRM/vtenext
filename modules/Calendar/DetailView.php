<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Calendar/calendarLayout.php');

global $mod_strings, $currentModule,$adb, $current_user,$table_prefix;
if( VteSession::get('mail_send_error')!="")
{
	echo '<b><font color=red>'. $mod_strings{"LBL_NOTIFICATION_ERROR"}.'</font></b><br>';
}
VteSession::remove('mail_send_error');
$focus = CRMEntity::getInstance('Activity');
$smarty =  new VteSmarty();
$activity_mode = $_REQUEST['activity_mode'];
//If activity_mode == null

if($activity_mode =='' || strlen($activity_mode) < 1)
{
	$query = "select activitytype from ".$table_prefix."_activity where activityid=?";
	$result = $adb->pquery($query, array($_REQUEST['record']));
	$actType = $adb->query_result($result,0,'activitytype');
	if( $actType == 'Task')
	{
		$activity_mode = $actType;
	}
	else{	//crmv@20602
		$activity_mode = 'Events';
	}
}

if($activity_mode == 'Task')
{
        $tab_type = 'Calendar';
	$smarty->assign("SINGLE_MOD",$mod_strings['LBL_TODO']);
}
elseif($activity_mode == 'Events')
{
        $tab_type = 'Events';
	$smarty->assign("SINGLE_MOD",$mod_strings['LBL_EVENT']);
}

if(isset($_REQUEST['record']) && isset($_REQUEST['record'])) {
    $focus->retrieve_entity_info($_REQUEST['record'],$tab_type);
    $focus->id = $_REQUEST['record'];
    $focus->name=$focus->column_fields['subject'];
}

if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$focus->id = "";
}

//needed when creating a new task with default values passed in
if (isset($_REQUEST['contactname']) && is_null($focus->contactname)) {
	$focus->contactname = $_REQUEST['contactname'];
}
if (isset($_REQUEST['contact_id']) && is_null($focus->contact_id)) {
	$focus->contact_id = $_REQUEST['contact_id'];
}
if (isset($_REQUEST['opportunity_name']) && is_null($focus->parent_name)) {
	$focus->parent_name = $_REQUEST['opportunity_name'];
}
if (isset($_REQUEST['opportunity_id']) && is_null($focus->parent_id)) {
	$focus->parent_id = $_REQUEST['opportunity_id'];
}
if (isset($_REQUEST['accountname']) && is_null($focus->parent_name)) {
	$focus->parent_name = $_REQUEST['accountname'];
}
if (isset($_REQUEST['accountid']) && is_null($focus->parent_id)) {
	$focus->parent_id = $_REQUEST['accountid'];
}

$act_data = getBlocks($tab_type,"detail_view",'',$focus->column_fields);

foreach($act_data as $block=>$entry)
{
	foreach($entry['fields'] as $key=>$value) // crmv@104568
	{
		foreach($value as $label=>$field)
		{
			$fldlabel[$field['fldname']] = $label;
			if($field['ui'] == 15 || $field['ui'] == 16 || $field['ui'] == 111)
			{
				foreach($field['options'] as $index=>$arr_val)
				{
					if($arr_val[2] == "selected")
			//crmv@8169
					$finaldata[$field['fldname']] = $arr_val[1];
			//crmv@8169e
				}
			}
			else
			{
				$fldvalue = $field['value'];
				if($field['fldname'] == 'description') { $fldvalue = nl2br($fldvalue); }
				$finaldata[$field['fldname']] = $fldvalue;
			}
			$finaldata[$field['fldname'].'link'] = $field['link'];
		}
	}
}

//Start
//To set user selected hour format
if($current_user->hour_format == '')
	$format = 'am/pm';
else
	$format = $current_user->hour_format;
list($stdate,$sttime) = explode('&nbsp;',$finaldata['date_start']);
list($enddate,$endtime) = explode('&nbsp;',$finaldata['due_date']);
$time_arr = getaddEventPopupTime($sttime,$endtime,$format);
$data['starthr'] = $time_arr['starthour'];
$data['startmin'] = $time_arr['startmin'];
$data['startfmt'] = $time_arr['startfmt'];
$data['endhr'] = $time_arr['endhour'];
$data['endmin'] = $time_arr['endmin'];
$data['endfmt'] = $time_arr['endfmt'];
$data['record'] = $focus->id;
$data['subject'] = $finaldata['subject'];
$data['date_start'] = $stdate;
$data['due_date'] = $enddate;
$data['assigned_user_id'] = $finaldata['assigned_user_id'];
if($mod_strings[$finaldata['taskpriority']] != '')
	$data['taskpriority'] = $mod_strings[$finaldata['taskpriority']];
else
	$data['taskpriority'] = $finaldata['taskpriority'];
$data['modifiedtime'] = $finaldata['modifiedtime'];
$data['createdtime'] = $finaldata['createdtime'];
$data['parent_name'] = $finaldata['parent_id'];
$data['description'] = $finaldata['description'];
$data['exp_duration'] = $finaldata['exp_duration']; // crmv@36871
if($activity_mode == 'Task')
{
	if($mod_strings[$finaldata['taskstatus']] != '')
		$data['taskstatus'] = $mod_strings[$finaldata['taskstatus']];
	else
		$data['taskstatus'] = $finaldata['taskstatus'];
	$data['activitytype'] = $activity_mode;
	$data['contact_id'] = $finaldata['contact_id'];
	$data['contact_idlink'] = $finaldata['contact_idlink'];
}
elseif($activity_mode == 'Events')
{
	$data['visibility'] = $finaldata['visibility'];
	if($mod_strings[$finaldata['eventstatus']] != '')
		$data['eventstatus'] = $mod_strings[$finaldata['eventstatus']];
	else
		$data['eventstatus'] = $finaldata['eventstatus'];
	$data['activitytype'] = $finaldata['activitytype'];
	$data['location'] = $finaldata['location'];
	$data['organizer'] = $finaldata['organizer']; // crmv@187823
	//Calculating reminder time
	$rem_days = 0;
	$rem_hrs = 0;
	$rem_min = 0;
	if($focus->column_fields['reminder_time'] != null)
	{
		$data['set_reminder'] = $mod_strings['LBL_YES'];
		$data['reminder_str'] = $finaldata['reminder_time'];
	}
	else
		$data['set_reminder'] = $mod_strings['LBL_NO'];
	//To set recurring details
	$query = 'select '.$table_prefix.'_recurringevents.recurringfreq,'.$table_prefix.'_recurringevents.recurringinfo from '.$table_prefix.'_recurringevents where '.$table_prefix.'_recurringevents.activityid = ?';
	$res = $adb->pquery($query, array($focus->id));
	$rows = $adb->num_rows($res);
	if($rows != 0)
	{
		$data['recurringcheck'] = $mod_strings['LBL_YES'];
		$data['repeat_frequency'] = $adb->query_result($res,0,'recurringfreq');
		$recurringinfo =  explode("::",$adb->query_result($res,0,'recurringinfo'));
		$data['recurringtype'] = $recurringinfo[0];
		if($recurringinfo[0] == 'Weekly')
		{
			$weekrpt_str = '';
			if(count($recurringinfo) > 1)
			{
				$weekrpt_str .= 'on ';
				for($i=1;$i<count($recurringinfo);$i++)
				{
					$label = 'LBL_DAY'.$recurringinfo[$i];
					if($i != 1)
						$weekrpt_str .= ', ';
					$weekrpt_str .= $mod_strings[$label];
				}
			}
			$data['repeat_str'] = $weekrpt_str;
		}
		elseif($recurringinfo[0] == 'Monthly')
		{
			$monthrpt_str = '';
			$data['repeatMonth'] = $recurringinfo[1];
			if($recurringinfo[1] == 'date')
			{
				$data['repeatMonth_date'] = $recurringinfo[2];
				$monthrpt_str .= $mod_strings['on'].'&nbsp;'.$recurringinfo[2].'&nbsp;'.$mod_strings['day of the month'];
			}
			else
			{
				$data['repeatMonth_daytype'] = $recurringinfo[2];
				$data['repeatMonth_day'] = $recurringinfo[3];
				switch($data['repeatMonth_day'])
				{
					case 0 :
						$day = $mod_strings['LBL_DAY0'];
						break;
					case 1 :
						$day = $mod_strings['LBL_DAY1'];
						break;
					case 2 :
						$day = $mod_strings['LBL_DAY2'];
						break;
					case 3 :
						$day = $mod_strings['LBL_DAY3'];
						break;
					case 4 :
						$day = $mod_strings['LBL_DAY4'];
						break;
					case 5 :
						$day = $mod_strings['LBL_DAY5'];
						break;
					case 6 :
						$day = $mod_strings['LBL_DAY6'];
						break;
				}

				$monthrpt_str .= 'on '.$mod_strings[ucfirst($recurringinfo[2])].' '.$day; // crmv@185501
			}
			$data['repeat_str'] = $monthrpt_str;
		}
	}
	else
	{
		$data['recurringcheck'] = $mod_strings['LBL_NO'];
		$data['repeat_month_str'] = '';
	}
	$sql = 'select '.$table_prefix.'_users.user_name,'.$table_prefix.'_invitees.* from '.$table_prefix.'_invitees left join '.$table_prefix.'_users on '.$table_prefix.'_invitees.inviteeid='.$table_prefix.'_users.id where activityid=?';
	$result = $adb->pquery($sql, array($focus->id));
	$num_rows=$adb->num_rows($result);
	$invited_users=Array();
	for($i=0;$i<$num_rows;$i++)
	{
		$userid=$adb->query_result($result,$i,'inviteeid');
		$username=$adb->query_result($result,$i,'user_name');
		$invited_users[$userid]=array($username,$adb->query_result($result,$i,'partecipation')); //crmv@17001
	}
	$smarty->assign("INVITEDUSERS",$invited_users);

	//crmv@26807
	$query = 'SELECT inviteeid,partecipation FROM '.$table_prefix.'_invitees_con WHERE activityid = ? ';
	$res = $adb->pquery($query, array($record));
	$invited_contacts = array();
	if ($res && $adb->num_rows($res)>0) {
		while($row = $adb->fetchByAssoc($res)) {
			$invited_contacts[$row['inviteeid']] = array(getContactName($row['inviteeid']),$row['partecipation']);
		}
	}
	$smarty->assign("INVITEDCONTACTS",$invited_contacts);
	//crmv@26807e

	$related_array = getRelatedLists("Calendar", $focus);
	$relationInfo = getRelatedListInfoById($related_array['Contacts']['relationId']);
	$relatedModule = getTabModuleName($related_array['Contacts']['relatedTabId']);
	$function_name = $relationInfo['functionName'];
	if($function_name != ''){	//crmv@23959
		$relatedListData = $focus->$function_name($focus->id, getTabid($currentModule),
		$relationInfo['relatedTabId'], $actions);
		$cntlist = $relatedListData['entries'];
	}	//crmv@23959
    //crmv@7230 clean color from relatedlist
    if (is_array($cntlist)){
    foreach ($cntlist as $key=>$column)
    	unset($cntlist['clv_color']);
	}
    //crmv@7230 e
	$smarty->assign("CONTACTS",$cntlist);

	$is_fname_permitted = getFieldVisibilityPermission("Contacts", $current_user->id, 'lastname'); //crmv@32363
	$smarty->assign("IS_PERMITTED_CNT_FNAME",$is_fname_permitted);

}

// remove unwanted fields from the block array
// I'm using a whitelist to avoid showing fields that were previously hidden
// TODO: find a way to avoid hardcoding stuff here (I can't use displaytype = 3, otherwise I will loose 
// important fields like parent_id, recurrence...)
if($activity_mode == 'Task') {
	$keepFields = array(
		'subject',
		'description',
		'date_start', 'due_date', 
		'taskstatus', 'eventstatus',
		'assigned_user_id', 'taskpriority', 
		'createdtime', 'modifiedtime',
		'exp_duration'
	);
} else {
	$keepFields = array(
		'subject', 'assigned_user_id', 'date_start', 'due_date', 
		'eventstatus', 'activitytype', 'location', 'createdtime',
		'modifiedtime','taskpriority', 'visibility', 'description',
		'organizer' // crmv@187823
	);
}

$customBlockLabel = getTranslatedString('LBL_CUSTOM_INFORMATION');
foreach ($act_data as $blocklabel => &$blockinfo) {
	// keep all fields in the custom block
	if ($blocklabel == $customBlockLabel) continue;
	foreach ($blockinfo['fields'] as $k => &$fields) { // crmv@104568
		// keep only selected fields
		foreach ($fields as $fieldlabel => $finfo) {
			if ($finfo['fldname'] && !in_array($finfo['fldname'], $keepFields)) {
				unset($fields[$fieldlabel]);
			}
		}
	}
	unset($fields);
}
unset($blockinfo);

// crmv@104568
$panelid = getCurrentPanelId($tab_type);
$smarty->assign("PANELID", $panelid);
$panelsAndBlocks = getPanelsAndBlocks($tab_type);
$smarty->assign("PANEL_BLOCKS", Zend_Json::encode($panelsAndBlocks));
// crmv@104568e

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("Calendar-Activities detail view");
$category = getParentTab();
$smarty->assign("CATEGORY",$category);

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("ACTIVITY_MODE", $activity_mode);

// crmv@104536
$entityName = isset($focus->name) ? $focus->name : '';
$smarty->assign("NAME", $entityName);
$smarty->assign("JS_NAME", Zend_Json::encode($entityName));
// crmv@104536e

$smarty->assign("UPDATEINFO",updateInfo($focus->id));
if (isset($_REQUEST['return_module']))
$smarty->assign("RETURN_MODULE", $_REQUEST['return_module']);
if (isset($_REQUEST['return_action']))
$smarty->assign("RETURN_ACTION", $_REQUEST['return_action']);
if (isset($_REQUEST['return_id']))
$smarty->assign("RETURN_ID", $_REQUEST['return_id']);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);
$smarty->assign("NAME", $focus->name);
$smarty->assign("BLOCKS", $act_data);
$smarty->assign("LABEL", $fldlabel);
$smarty->assign("VIEWTYPE", $_REQUEST['viewtype']);
$smarty->assign("CUSTOMFIELD", $cust_fld);
$smarty->assign("ACTIVITYDATA", $data);
$smarty->assign("ID", $_REQUEST['record']);

//get Description Information
if(isPermitted("Calendar","EditView",$_REQUEST['record']) == 'yes')
	$smarty->assign("EDIT_DUPLICATE","permitted");

if(isPermitted("Calendar","Delete",$_REQUEST['record']) == 'yes')
	$smarty->assign("DELETE","permitted");

$check_button = Button_Check($module);
$smarty->assign("CHECK", $check_button);

// crmv@83877 crmv@112297
// Field Validation Information
$tabid = getTabid($currentModule);
$otherInfo = array();
$validationData = getDBValidationData($focus->tab_name,$tabid,$otherInfo);
$validationArray = split_validationdataArray($validationData, $otherInfo);
$smarty->assign("VALIDATION_DATA_FIELDNAME",$validationArray['fieldname']);
$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",$validationArray['datatype']);
$smarty->assign("VALIDATION_DATA_FIELDLABEL",$validationArray['fieldlabel']);
$smarty->assign("VALIDATION_DATA_FIELDUITYPE",$validationArray['fielduitype']);
$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",$validationArray['fieldwstype']);
// crmv@83877e crmv@112297e

$smarty->assign("MODULE",$currentModule);
$smarty->assign("EDIT_PERMISSION",isPermitted($currentModule,'EditView',$_REQUEST['record']));
$smarty->assign('DETAILVIEW_AJAX_EDIT', PerformancePrefs::getBoolean('DETAILVIEW_AJAX_EDIT', true)); // crmv@101312

//crmv@107341
if (method_exists($focus, 'getExtraDetailBlock')) {
	$smarty->assign("EXTRADETAILBLOCK", $focus->getExtraDetailBlock());
}
if (method_exists($focus, 'getDetailTabs')) {
	$smarty->assign("DETAILTABS", $focus->getDetailTabs());
}
//crmv@107341e

// Gather the custom link information to display
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$customlink_params = Array('MODULE'=>$currentModule, 'RECORD'=>$focus->id, 'ACTION'=>vtlib_purify($_REQUEST['action']));
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid($currentModule), Array('DETAILVIEWBASIC','DETAILVIEW','DETAILVIEWWIDGET'), $customlink_params));
// END

$custom_fields_data = getCalendarCustomFields($tabid,'detail_view',$focus->column_fields);
$smarty->assign("CUSTOM_FIELDS_DATA", $custom_fields_data);

//crmv@17001
if(PerformancePrefs::getBoolean('DETAILVIEW_RECORD_NAVIGATION', true) && VteSession::hasKey($currentModule.'_listquery')){
	$recordNavigationInfo = ListViewSession::getListViewNavigation($focus->id);
	VT_detailViewNavigation($smarty,$recordNavigationInfo,$focus->id);
}

$smarty->assign("IS_REL_LIST",isPresentRelatedLists($currentModule));
$smarty->assign("SinglePane_View",'true');

// crmv@178822
$ajaxCall = $_REQUEST['ajaxCall'];
if (!empty($ajaxCall) && $ajaxCall === 'CalendarView') {
	// do nothing
} else {
	include('modules/VteCore/Turbolift.php'); // crmv@43864
}
// crmv@178822e

require_once('include/ListView/RelatedListViewSession.php');
if(!empty($_REQUEST['selected_header']) && !empty($_REQUEST['relation_id'])) {
	RelatedListViewSession::addRelatedModuleToSession(vtlib_purify($_REQUEST['relation_id']),
			vtlib_purify($_REQUEST['selected_header']));
}
$open_related_modules = RelatedListViewSession::getRelatedModulesFromSession();
$smarty->assign("SELECTEDHEADERS", $open_related_modules);

$smarty->assign("CURRENT_USER", $current_user->id);
//crmv@17001e

// crmv@98866
$smarty->assign("MODE", 'detail');

//crmv@112297
$conditionalsFocus = CRMEntity::getInstance('Conditionals');
$enable = $conditionalsFocus->existsConditionalPermissions($currentModule, $focus);
if ($enable) {
	$smarty->assign('AJAXONCLICKFUNCT', 'ProcessMakerScript.alertDisableAjaxSave');
}
//crmv@112297e

// crmv@167019 crmv@186446
$RM = RelationManager::getInstance();
$relatedModules = $RM->getRelatedModules($currentModule);
if (in_array('Documents', $relatedModules) || $currentModule === 'Documents') {
	$smarty->assign('DROPAREA_ACTIVE', true);
}
// crmv@167019e crmv@186446e

// crmv@171524 crmv@196871
$triggerQueueManager = TriggerQueueManager::getInstance();
if ($triggerQueueManager->isStompEnabled()) {
	$smarty->assign('STOMP_ENABLED', true);
	$smarty->assign('IS_FREEZED', $triggerQueueManager->checkFreezed($focus->id));
	$smarty->assign('STOMP_CONNECTION', Zend_Json::encode($triggerQueueManager->getConnectionParams('stomp')));
	$smarty->assign('IS_FETCHING', false);
	if (isset($_REQUEST['fetch_only']) && !empty($_REQUEST['fetch_only'])) {
		$smarty->assign('IS_FETCHING', true);
		if ($_REQUEST['fetch_only'] === 'navbar') {
			include_once('modules/SDK/src/Favorites/Utils.php');
			$smarty->assign('FETCH_ONLY_NAVBAR', true);
			$html = $smarty->fetch('Buttons_List_Detail.tpl');
			$data = array('success' => true, 'html' => $html);
			echo Zend_Json::encode($data);
			exit();
		}
	}
} else {
	$smarty->assign('STOMP_ENABLED', false);
}
// crmv@171524e crmv@196871e

$smarty_template = 'DetailView.tpl';

$ajaxCall = $_REQUEST['ajaxCall'];
if (!empty($ajaxCall) && $ajaxCall === 'CalendarView') {
	$phpFile = 'modules/Calendar/addEventForm.php';
	if ($activity_mode == 'Task') {
		$phpFile = 'modules/Calendar/addTodoForm.php';
	}
	checkFileAccess($phpFile);
	require_once $phpFile;
}

$smarty->display($smarty_template);
// crmv@98866e

?>