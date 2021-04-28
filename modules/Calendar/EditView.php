<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95751 */

require_once('modules/Calendar/calendarLayout.php');
require_once("modules/Emails/mail.php");
require_once("include/CustomFieldUtil.php");

global $app_strings;
global $mod_strings,$current_user;
global $table_prefix;
// Unimplemented until jscalendar language vte_files are fixed

$focus = CRMEntity::getInstance($currentModule);
$smarty =  new VteSmarty();
//added to fix the issue4600
$searchurl = getBasic_Advance_SearchURL();
$smarty->assign("SEARCH", $searchurl);
//4600 ends

$activity_mode = vtlib_purify($_REQUEST['activity_mode']);
if($activity_mode == 'Task')
{
	$tab_type = 'Calendar';
	$taskcheck = true;
	$smarty->assign("SINGLE_MOD",$mod_strings['LBL_TODO']);
}
//crmv@20602
else
{
	$tab_type = 'Events';
	$activity_mode = 'Events';
	$taskcheck = false;
	$smarty->assign("SINGLE_MOD",$mod_strings['LBL_EVENT']);
}
//crmv@20602e

if(isset($_REQUEST['record']) && $_REQUEST['record']!='') {
    $focus->id = $_REQUEST['record'];
    $focus->mode = 'edit';
    $focus->retrieve_entity_info($_REQUEST['record'],$tab_type);
    $focus->name=$focus->column_fields['subject'];
    $sql = 'select '.$table_prefix.'_users.*,'.$table_prefix.'_invitees.* from '.$table_prefix.'_invitees left join '.$table_prefix.'_users on '.$table_prefix.'_invitees.inviteeid='.$table_prefix.'_users.id where activityid=?';	//crmv26807
    $result = $adb->pquery($sql, array($focus->id));
    $num_rows=$adb->num_rows($result);
    // crmv@103922
    $invited_users=Array();
    for($i=0;$i<$num_rows;$i++)
    {
	    $userid=$adb->query_result($result,$i,'inviteeid');
	    $username=$adb->query_result($result,$i,'user_name');
	    $firstname=$adb->query_result($result,$i,'first_name');
	    $lastname=$adb->query_result($result,$i,'last_name');
	    $avatar = $adb->query_result($result,$i,'avatar');
	    if ($avatar == '') {
	    	$avatar = getDefaultUserAvatar();
	    }
	    $full_name = trim($firstname . ' ' . $lastname);
	    $invited_users[$userid] = array('username' => $username, 'full_name' => $full_name, 'img' => $avatar);
    }
    $invited_users_list = '|' . implode('|', array_keys($invited_users)) . '|';
    
    $smarty->assign("INVITEDUSERS", $invited_users);
    $smarty->assign("INVITEDUSERS_LIST", $invited_users_list);

    //crmv@26807
    $query = 'SELECT inviteeid FROM '.$table_prefix.'_invitees_con WHERE activityid = ? ';
    $res = $adb->pquery($query, array($_REQUEST['record']));
    $invited_contacts = array();
    if ($res && $adb->num_rows($res)>0) {
    	while($row = $adb->fetchByAssoc($res)) {
    		$invited_contacts[$row['inviteeid']] = getContactName($row['inviteeid']);
    	}
    }
    $invited_contacts_list = '|' . implode('|', array_keys($invited_contacts)) . '|';
    
    $smarty->assign("INVITEDCONTACTS",$invited_contacts);
    $smarty->assign("INVITEDCONTACTS_LIST",$invited_contacts_list);
    //crmv@26807e
    // crmv@103922e

    $smarty->assign("UPDATEINFO",updateInfo($focus->id));
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
	$is_fname_permitted = getFieldVisibilityPermission("Contacts", $current_user->id, 'firstname');
    $cnt_idlist = '';
    $cnt_namelist = '';
    if($cntlist != '')
    {
	    $i = 0;
	    foreach($cntlist as $key=>$cntvalue)
	    {
		    if($i != 0)
		    {
			    $cnt_idlist .= ';';
			    $cnt_namelist .= "\n";
		    }
		    $cnt_idlist .= $key;
		    $contName = preg_replace("#(<a[^>]*>)(.*)(</a>)#i", "\\2", $cntvalue[2]);
			if ($is_fname_permitted == '0') $contName .= ' '.preg_replace("#(<a[^>]*>)(.*)(</a>)#i", "\\2", $cntvalue[1]);
		    $cnt_namelist .= '<option value="'.$key.'">'.$contName.'</option>';
		    $i++;
	    }
    }
    $smarty->assign("CONTACTSID",$cnt_idlist);
    $smarty->assign("CONTACTSNAME",$cnt_namelist);
    $query = 'select '.$table_prefix.'_recurringevents.recurringfreq,'.$table_prefix.'_recurringevents.recurringinfo from '.$table_prefix.'_recurringevents where '.$table_prefix.'_recurringevents.activityid = ?';
    $res = $adb->pquery($query, array($focus->id));
    $rows = $adb->num_rows($res);
    if($rows != 0)
    {
	    $value['recurringcheck'] = 'Yes';
	    $value['repeat_frequency'] = $adb->query_result($res,0,'recurringfreq');
	    $recurringinfo =  explode("::",$adb->query_result($res,0,'recurringinfo'));
	    $value['eventrecurringtype'] = $recurringinfo[0];
	    if($recurringinfo[0] == 'Weekly')
	    {
		   for($i=0;$i<6;$i++)
		   {
			   $label = 'week'.$recurringinfo[$i+1];
			   $value[$label] = 'checked';
		   }
	    }
	    elseif($recurringinfo[0] == 'Monthly')
	    {
		    $value['repeatMonth'] = $recurringinfo[1];
		    if($recurringinfo[1] == 'date')
		    {
			    $value['repeatMonth_date'] = $recurringinfo[2];
		    }
		    else
		    {
			    $value['repeatMonth_daytype'] = $recurringinfo[2];
			    $value['repeatMonth_day'] = $recurringinfo[3];
		    }
	    }
    }
    else
    {
	    $value['recurringcheck'] = 'No';
    }

}else
{
	if(isset($_REQUEST['contact_id']) && $_REQUEST['contact_id']!=''){
		$smarty->assign("CONTACTSID",intval($_REQUEST['contact_id']));	// crmv@26907
		$contact_name = "<option value=".intval($_REQUEST['contact_id']).">".getContactName(intval($_REQUEST['contact_id']))."</option>"; 	// crmv@26907
		$smarty->assign("CONTACTSNAME",$contact_name);
		$account_id = $_REQUEST['account_id'];
		$account_name = getAccountName($account_id);
	}
	//crmv@62447 crmv@106011
	if ($_REQUEST['invited_users'] != ''){
		$invited_users=vtlib_purify($_REQUEST['invited_users']);
		$invited_users = @Zend_Json::decode($invited_users);
		if (is_array($invited_users) && !empty($invited_users)){
		    $sql = "select id,user_name,last_name,first_name, avatar from {$table_prefix}_users where id in (".generateQuestionMarks($invited_users).")";
		    $res = $adb->pquery($sql,$invited_users);
		    $invited_users=Array();
		    while($row = $adb->fetchByAssoc($res)){
			    $invited_users[$row['id']] = array(
					'username' => $row['user_name'],
					'full_name' => trim($row['last_name'].' '.$row['first_name']),
					'img' => $row['avatar'] ?: getDefaultUserAvatar(),
				);
		    }
		    $invited_users_list = '|' . implode('|', array_keys($invited_users)) . '|';
		    $smarty->assign("INVITEDUSERS",$invited_users);
		    $smarty->assign("INVITEDUSERS_LIST", $invited_users_list);
		}
	}
	//crmv@62447e crmv@106011e
}
//crmv@27001
$modulesSelectable = array('Users','Contacts');
$quick_parent_type = array();
foreach ($modulesSelectable as $modVal) {
	$quick_parent_type[$modVal] = getTranslatedString($modVal);
}
$smarty->assign("QUICKPARENTTYPE",$quick_parent_type);
//crmv@27001e
if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$duplicated_record = $_REQUEST['record'];	//crmv@17001
	$focus->id = "";
	$focus->mode = '';
}
if(empty($_REQUEST['record']) && $focus->mode != 'edit'){
	setObjectValuesFromRequest($focus);
}
$userDetails=get_user_array(false);	//crmv@18194
$to_email = getUserEmailId('id',$current_user->id);
$smarty->assign("CURRENTUSERID",$current_user->id);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end
$disp_view = getView($focus->mode);
if($disp_view == 'edit_view')
{
	$act_data = getBlocks($tab_type,$disp_view,$mode,$focus->column_fields);
}
else
{
	$act_data = getBlocks($tab_type,$disp_view,$mode,$focus->column_fields,'BAS');
}
unset($act_data[$app_strings['LBL_CUSTOM_INFORMATION']]);
$smarty->assign("BLOCKS",$act_data);
$value = array();
foreach($act_data as $header=>$blockitem)
{
	foreach($blockitem['fields'] as $row=>$data) // crmv@104568
	{
		foreach($data as $key=>$maindata)
		{
			$uitype[$maindata[2][0]] = $maindata[0][0];
			$fldlabel[$maindata[2][0]] = $maindata[1][0];
			$fldlabel_sel[$maindata[2][0]] = $maindata[1][1];
			$fldlabel_combo[$maindata[2][0]] = $maindata[1][2];
			$value[$maindata[2][0]] = $maindata[3][0];
			$secondvalue[$maindata[2][0]] = $maindata[3][1];
			$thirdvalue[$maindata[2][0]] = $maindata[3][2];
		}
	}
}
//crmv@54901
if (strlen($account_name) > 0) {
	$value['parent_id'] = array(
		'entityid'=>$account_id,
		'displayvalue'=>$account_name,
	);
}
//crmv@54901e

//crmv@108227
$smarty->assign("HIDE_INVITE_TAB", ($_REQUEST['hide_invite_tab']));
if ($_REQUEST['hide_reminder_time']) $fldlabel['reminder_time'] = '';
if ($_REQUEST['hide_recurringtype']) $fldlabel['recurringtype'] = '';
if ($_REQUEST['hide_reference_contact_field']) {
	$fldlabel['contact_id'] = '';
	$smarty->assign("HIDE_REFERENCE_CONTACT_FIELD", ($_REQUEST['hide_reference_contact_field']));
}

$format = (trim($current_user->hour_format) == '')?'am/pm':$current_user->hour_format;
$smarty->assign('PROCESSMAKER_MODE',($_REQUEST['enable_editoptions']=='yes'));
if ($_REQUEST['enable_editoptions'] == 'yes') {
	$date_start = Zend_Json::decode($_REQUEST['date_start']);
	$due_date = Zend_Json::decode($_REQUEST['due_date']);
	$time_start = Zend_Json::decode($_REQUEST['time_start']);
	$time_end = Zend_Json::decode($_REQUEST['time_end']);
	$stdate = $date_start['custom'];
	$enddate = $due_date['custom'];
	$sttime = $time_start['custom'];
	$endtime = $time_end['custom'];
	//crmv@153818
	$smarty->assign('IS_ALL_DAY_THIRDVALUE',array(
		array($app_strings['LBL_NO'],0,($_REQUEST['is_all_day_event'] == 0)?'selected':''),
		array($app_strings['LBL_YES'],1,($_REQUEST['is_all_day_event'] == 1)?'selected':''),
	));
	//crmv@153818e
} else {
	$stdate = key($value['date_start']);
	$enddate = key($value['due_date']);
	$sttime = $value['date_start'][$stdate];
	$endtime = $value['due_date'][$enddate];
}
//crmv@108227e
$time_arr = getaddEventPopupTime($sttime,$endtime,$format);
$value['starthr'] = $time_arr['starthour'];
$value['startmin'] = $time_arr['startmin'];
$value['startfmt'] = $time_arr['startfmt'];
$value['endhr'] = $time_arr['endhour'];
$value['endmin'] = $time_arr['endmin'];
$value['endfmt'] = $time_arr['endfmt'];
// crmv@36871
$value['exp_duration'] = $focus->column_fields['exp_duration'];
$smarty->assign("EXPDURATIONPLIST", getAssignedPicklistValues('exp_duration', $current_user->roleid, $adb, 'Calendar'));
// crmv@36871e
$smarty->assign("STARTHOUR",getTimeCombo($format,'start',$time_arr['starthour'],$time_arr['startmin'],$time_arr['startfmt'],$taskcheck));
$smarty->assign("ENDHOUR",getTimeCombo($format,'end',$time_arr['endhour'],$time_arr['endmin'],$time_arr['endfmt']));
$smarty->assign("FOLLOWUP",getTimeCombo($format,'followup_start',$time_arr['endhour'],$time_arr['endmin'],$time_arr['endfmt']));
$smarty->assign("ACTIVITYDATA",$value);
$smarty->assign("LABEL",$fldlabel);
$smarty->assign("calsecondvalue",$secondvalue); // crmv@98866
$smarty->assign("thirdvalue",$thirdvalue);
//$smarty->assign("fldlabel_combo",$fldlabel_combo);	//crmv@42247
//$smarty->assign("fldlabel_sel",$fldlabel_sel);	//crmv@42247
$smarty->assign("OP_MODE",$disp_view);
$smarty->assign("ACTIVITY_MODE",$activity_mode);
$smarty->assign("HOURFORMAT",$format);
$smarty->assign("USERSLIST",$userDetails);
$smarty->assign("USEREMAILID",$to_email);
$smarty->assign("MODULE",$currentModule);
$smarty->assign("RECORD",$focus->id);
$smarty->assign("DATEFORMAT",parse_calendardate($app_strings['NTC_DATE_FORMAT']));

// crmv@42752
if ($_REQUEST['hide_button_list'] == '1') {
	$smarty->assign('HIDE_BUTTON_LIST', '1');
}
// crmv@42752e

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info("Activity edit view");

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);

// crmv@104536
$entityName = isset($focus->name) ? $focus->name : '';
$smarty->assign("NAME", $entityName);
$smarty->assign("JS_NAME", Zend_Json::encode($entityName));
// crmv@104536e

if($focus->mode == 'edit')
{
        $smarty->assign("MODE", $focus->mode);
}

$category = getParentTab();
$smarty->assign("CATEGORY",$category);

// Unimplemented until jscalendar language vte_files are fixed
$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

$smarty->assign("WEEKSTART", $current_user->weekstart); // crmv@183418

if (isset($_REQUEST['return_module']))
	$smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
if (isset($_REQUEST['return_action']))
	$smarty->assign("RETURN_ACTION",vtlib_purify( $_REQUEST['return_action']));
if (isset($_REQUEST['return_id']))
	$smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
if (isset($_REQUEST['ticket_id']))
	$smarty->assign("TICKETID", $_REQUEST['ticket_id']);
if (isset($_REQUEST['product_id']))
	$smarty->assign("PRODUCTID", $_REQUEST['product_id']);
if (isset($_REQUEST['return_viewname']))
	$smarty->assign("RETURN_VIEWNAME", vtlib_purify($_REQUEST['return_viewname']));
if(isset($_REQUEST['view']) && $_REQUEST['view']!='')
	$smarty->assign("view",vtlib_purify($_REQUEST['view']));
if(isset($_REQUEST['hour']) && $_REQUEST['hour']!='')
	$smarty->assign("hour",vtlib_purify($_REQUEST['hour']));
if(isset($_REQUEST['day']) && $_REQUEST['day']!='')
	$smarty->assign("day",vtlib_purify($_REQUEST['day']));
if(isset($_REQUEST['month']) && $_REQUEST['month']!='')
	$smarty->assign("month",vtlib_purify($_REQUEST['month']));
if(isset($_REQUEST['year']) && $_REQUEST['year']!='')
	$smarty->assign("year",vtlib_purify($_REQUEST['year']));
if(isset($_REQUEST['viewOption']) && $_REQUEST['viewOption']!='')
	$smarty->assign("viewOption",vtlib_purify($_REQUEST['viewOption']));
if(isset($_REQUEST['view_filter']) && $_REQUEST['view_filter']!='')
	$smarty->assign("view_filter",vtlib_purify($_REQUEST['view_filter']));
if(isset($_REQUEST['subtab']) && $_REQUEST['subtab']!='')
	$smarty->assign("subtab",vtlib_purify($_REQUEST['subtab']));
if(isset($_REQUEST['maintab']) && $_REQUEST['maintab']!='')
	$smarty->assign("maintab",vtlib_purify($_REQUEST['maintab']));


$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);

// fields to be validated
// TODO: don't hardcode stuff in the code, please!!!
if ($activity_mode == 'Task') {
	$validateFields = array('subject','date_start','time_start','due_date','taskstatus','assigned_user_id');
	$custom_fields_data = getCalendarCustomFields(getTabid('Calendar'),'edit',$focus->column_fields);
} else {
	$validateFields = array('subject','date_start','time_start','due_date','eventstatus','taskpriority','parent_id','contact_id','reminder_time','recurringtype','assigned_user_id');
	$custom_fields_data = getCalendarCustomFields(getTabid('Events'),'edit',$focus->column_fields);
}

$tabid = getTabid($tab_type);
$validationArray = array();
$validationArrayCustom = array();
//crmv@112297
$otherInfo = array();
$rawValidation = getDBValidationData($focus->tab_name,$tabid,$otherInfo);

// prepare the validation array
foreach ($validateFields as $fieldname) {
	if (array_key_exists($fieldname, $rawValidation)) {
		$data = $rawValidation[$fieldname];
		$validationArray['fieldname'][] = $fieldname;
		$validationArray['datatype'][] = reset($data);
		$validationArray['fieldlabel'][] = key($data);
		$validationArray['uitype'][] = intval($otherInfo['fielduitype'][$fieldname]);
		$validationArray['wstype'][] = $otherInfo['fieldwstype'][$fieldname];
	}
}
$smarty->assign("VALIDATION_DATA_FIELDNAME",Zend_Json::encode($validationArray['fieldname']));
$smarty->assign("VALIDATION_DATA_FIELDDATATYPE",Zend_Json::encode($validationArray['datatype']));
$smarty->assign("VALIDATION_DATA_FIELDLABEL",Zend_Json::encode($validationArray['fieldlabel']));
$smarty->assign("VALIDATION_DATA_FIELDUITYPE",Zend_Json::encode($validationArray['uitype']));
$smarty->assign("VALIDATION_DATA_FIELDWSTYPE",Zend_Json::encode($validationArray['wstype']));

// prepare the validation array (custom fields)
if (is_array($custom_fields_data)) {
	// extract fieldids
	$customids = array();
	foreach ($custom_fields_data as $fld) {
		$customids[] = intval($fld[7]);
	}
	$customids = array_filter($customids);
	if (count($customids) > 0)  {
		$res = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE fieldid IN (".generateQuestionMarks($customids).")", $customids);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$webservice_field = WebserviceField::fromArray($adb,$row);
				$validationArrayCustom['fieldname'][] = $row['fieldname'];
				$validationArrayCustom['datatype'][] = $row['typeofdata'];
				$validationArrayCustom['fieldlabel'][] = getTranslatedString($row['fieldlabel']);
				$validationArrayCustom['uitype'][] = intval($row['uitype']);
				$validationArrayCustom['wstype'][] = $webservice_field->getFieldDataType();
			}
		}
	}
}
$smarty->assign("VALIDATION_DATA_CUS_FIELDNAME",Zend_Json::encode($validationArrayCustom['fieldname']));
$smarty->assign("VALIDATION_DATA_CUS_FIELDDATATYPE",Zend_Json::encode($validationArrayCustom['datatype']));
$smarty->assign("VALIDATION_DATA_CUS_FIELDLABEL",Zend_Json::encode($validationArrayCustom['fieldlabel']));
$smarty->assign("VALIDATION_DATA_CUS_FIELDUITYPE",Zend_Json::encode($validationArrayCustom['uitype']));
$smarty->assign("VALIDATION_DATA_CUS_FIELDWSTYPE",Zend_Json::encode($validationArrayCustom['wstype']));
//crmv@112297e

$check_button = Button_Check($currentModule);
$smarty->assign("CHECK", $check_button);
$smarty->assign("DUPLICATE",vtlib_purify($_REQUEST['isDuplicate']));

$custom_fields_data = Array($app_strings['LBL_CUSTOM_INFORMATION']=>$custom_fields_data);
$smarty->assign("CUSTOM_FIELDS_DATA", $custom_fields_data);
$smarty->assign("data", $custom_fields_data);

$smarty->assign("REPEAT_LIMIT_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

//crmv@92272
if ($_REQUEST['mass_edit_mode'] == '1') {
	$smarty->assign('MASS_EDIT','1');
}
//crmv@92272e

//crmv@112297
if ($_REQUEST['disable_conditionals'] != '1') {
	$conditionalsFocus = CRMEntity::getInstance('Conditionals');
	$smarty->assign('ENABLE_CONDITIONALS', $conditionalsFocus->existsConditionalPermissions($currentModule, $focus));
}
//crmv@112297e

// crmv@98866
$smarty_template = 'ActivityEditView.tpl';

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