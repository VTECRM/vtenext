<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@17001 crmv@54826 */

require_once('modules/Calendar/Activity.php');
require_once('modules/Calendar/CalendarCommon.php');
global $adb,$theme,$table_prefix;
$local_log =& LoggerManager::getLogger('index');
$focus = CRMEntity::getInstance('Activity');
$activity_mode = vtlib_purify($_REQUEST['activity_mode']);
$tab_type = 'Calendar';
//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);

$focus->column_fields["activitytype"] = 'Task';
if(isset($_REQUEST['record']))
{
	$focus->id = $_REQUEST['record'];
	$local_log->debug("id is ".$id);
}
if(isset($_REQUEST['mode']))
{
	$focus->mode = $_REQUEST['mode'];
}

if((isset($_REQUEST['change_status']) && $_REQUEST['change_status']) && ($_REQUEST['status']!='' || $_REQUEST['eventstatus']!=''))
{
	$status ='';
	$activity_type='';
	$return_id = $focus->id;
	if(isset($_REQUEST['status']))
	{
		$status = $_REQUEST['status'];
		$activity_type = "Task";
	}
	elseif(isset($_REQUEST['eventstatus']))
	{
		$status = $_REQUEST['eventstatus'];
		$activity_type = "Events";
		$tab_type = 'Events';	//crmv@24140
	}
	if(isPermitted("Calendar","EditView",$_REQUEST['record']) == 'yes')
	{
		//crmv@20896
		$focus = CRMEntity::getInstance($tab_type);
		if ($activity_type == 'Events'){
			$focus->retrieve_entity_info_no_html($return_id,$activity_type);
		}
		else{
			$focus->retrieve_entity_info_no_html($return_id,$tab_type);
		}
		$focus->mode = 'edit';
		$focus->id =$return_id;
		//$focus->column_fields["activitytype"] = $activity_type;
		if ($activity_type == 'Task'){
			$focus->column_fields["taskstatus"] = $status;
			unset($focus->column_fields["eventstatus"]);
		}
		else{
			$focus->column_fields["eventstatus"] = $status;
		}
		$tab_type = 'Calendar';	//crmv@25040
		$focus->save($tab_type);
		//crmv@20896 end
	}
	else
	{
		echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";
		echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
		echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

			<table border='0' cellpadding='5' cellspacing='0' width='98%'>
			<tbody><tr>
			<td rowspan='2' width='11%'><img src='<?php echo resourcever('denied.gif'). ?>' ></td>
			<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>$app_strings[LBL_PERMISSION]</span></td>
			</tr>
			<tr>
			<td class='small' align='right' nowrap='nowrap'>
			<a href='javascript:window.history.back();'>$app_strings[LBL_GO_BACK]</a><br>								   						     </td>
			</tr>
			</tbody></table>
		</div>";
		echo "</td></tr></table>";die;
	}
	//crmv@32334
	if (!$focus){
		$focus = CRMEntity::getInstance($tab_type);
		if ($activity_type == 'Events'){
			$focus->retrieve_entity_info_no_html($return_id,$activity_type);
		}
		else{
			$focus->retrieve_entity_info_no_html($return_id,$tab_type);
		}
	}
	$mail_data = $focus->getActivityMailInfo($return_id,$status,$activity_type);
	$invitee_qry = "select * from ".$table_prefix."_invitees where activityid=?";
	$invitee_res = $adb->pquery($invitee_qry, array($return_id));
	$count = $adb->num_rows($invitee_res);
	if($count != 0)
	{
		for($j = 0; $j < $count; $j++)
		{
			$invitees_ids[]= $adb->query_result($invitee_res,$j,"inviteeid");

		}
		$invitees_ids_string = implode(';',$invitees_ids);
		$focus->sendInvitation($invitees_ids_string,$activity_type,$mail_data['subject'],$mail_data,$return_id);	//crmv@19555 //crmv@32334
	}
	//crmv@32334 e
}
else
{
	foreach($focus->column_fields as $fieldname => $val)
	{
		if(isset($_REQUEST[$fieldname]))
		{
			if(is_array($_REQUEST[$fieldname]))
				$value = $_REQUEST[$fieldname];
			else
				$value = trim($_REQUEST[$fieldname]);
			$focus->column_fields[$fieldname] = $value;
			if(($fieldname == 'notime') && ($focus->column_fields[$fieldname]))
			{
				$focus->column_fields['time_start'] = '';
				$focus->column_fields['duration_hours'] = '';
				$focus->column_fields['duration_minutes'] = '';
			}
			if(($fieldname == 'recurringtype') && ! isset($_REQUEST['recurringcheck']))
				$focus->column_fields['recurringtype'] = '--None--';
		}
	}
	if(isset($_REQUEST['visibility']) && $_REQUEST['visibility']!= '')
        $focus->column_fields['visibility'] = $_REQUEST['visibility'];
	else
        $focus->column_fields['visibility'] = 'Standard';	//crmv@17001

	if($_REQUEST['assigntype'] == 'U') {
		$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
	} elseif($_REQUEST['assigntype'] == 'T') {
		$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
	}
	$focus->save($tab_type);
	/* For Followup START -- by Minnie */
	if(isset($_REQUEST['followup']) && $_REQUEST['followup'] == 'on' && $activity_mode == 'Events' && isset($_REQUEST['followup_time_start']) &&  $_REQUEST['followup_time_start'] != '')
	{
		$heldevent_id = $focus->id;
		$focus->column_fields['subject'] = '[Followup] '.$focus->column_fields['subject'];
		$focus->column_fields['date_start'] = $_REQUEST['followup_date'];
		$focus->column_fields['due_date'] = $_REQUEST['followup_due_date'];
		$focus->column_fields['time_start'] = $_REQUEST['followup_time_start'];
		$focus->column_fields['time_end'] = date("H:i", strtotime($_REQUEST['followup_time_end'].'+1 hours') ); // crmv@201266
		$focus->column_fields['eventstatus'] = 'Planned';
		$focus->mode = 'create';
		$focus->id = null; // crmv@185909
		$focus->save($tab_type);
	}
	/* For Followup END -- by Minnie */
	$return_id = $focus->id;

	// crmv@42752 crmv@81338 link with message, pay attention, this parameter is the crmid
	if ($_REQUEST['messageid'] > 0) {
		$focusMessage = CRMEntity::getInstance('Messages');
		$focusMessage->retrieve_entity_info_no_html(intval($_REQUEST['messageid']),'Messages');
		$focusMessage->id = intval($_REQUEST['messageid']);	//crmv@82688
		$focusMessage->save_related_module_small($focusMessage->column_fields['messageid'], 'Calendar', $return_id, $focusMessage->column_fields['subject']);
	}
	// crmv@42752e crmv@81338e
}

if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "")
	$return_module = vtlib_purify($_REQUEST['return_module']);
else
	$return_module = "Calendar";
if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "")
	$return_action = vtlib_purify($_REQUEST['return_action']);
else
	$return_action = "DetailView";
if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "")
	$return_id = vtlib_purify($_REQUEST['return_id']);

$activemode = "";
if($activity_mode != '')
	$activemode = "&activity_mode=".$activity_mode;

if(isset($_REQUEST['contactidlist']) && $_REQUEST['contactidlist'] != '')
{
	//split the string and store in an array
	$storearray = explode (";",$_REQUEST['contactidlist']);
	$del_sql = "delete from ".$table_prefix."_cntactivityrel where activityid=?";
	$adb->pquery($del_sql, array($record));
	foreach($storearray as $id)
	{
		if($id != '')
		{
			$record = $focus->id;
			$sql = "insert into ".$table_prefix."_cntactivityrel values (?,?)";
			$adb->pquery($sql, array($id, $record));
			if(!empty($heldevent_id)) {
				$sql = "insert into ".$table_prefix."_cntactivityrel values (?,?)";
				$adb->pquery($sql, array($id, $heldevent_id));
			}
		}
	}
}

//to delete contact account relation while editing event
if(isset($_REQUEST['deletecntlist']) && $_REQUEST['deletecntlist'] != '' && $_REQUEST['mode'] == 'edit')
{
	//split the string and store it in an array
	$storearray = explode (";",$_REQUEST['deletecntlist']);
	foreach($storearray as $id)
	{
		if($id != '')
		{
			$record = $focus->id;
			$sql = "delete from ".$table_prefix."_cntactivityrel where contactid=? and activityid=?";
			$adb->pquery($sql, array($id, $record));
		}
	}

}

//to delete activity and its parent table relation
if(isset($_REQUEST['del_actparent_rel']) && $_REQUEST['del_actparent_rel'] != '' && $_REQUEST['mode'] == 'edit')
{
	$parnt_id = $_REQUEST['del_actparent_rel'];
	$sql= 'delete from '.$table_prefix.'_seactivityrel where crmid=? and activityid=?';
	$adb->pquery($sql, array($parnt_id, $record));
}

//crmv@158871
$javascript_code = '';
$alert_private_invitation = false;
if ($focus->mode != 'edit' && $focus->column_fields['visibility'] == 'Private') {
	$invitee_res = $adb->pquery("select * from {$table_prefix}_invitees where activityid=?", array($focus->id));
	if ($adb->num_rows($invitee_res) != 0) $alert_private_invitation = true;
}
//crmv@158871e

if(isset($_REQUEST['view']) && $_REQUEST['view']!='')
	$view=vtlib_purify($_REQUEST['view']);
if(isset($_REQUEST['hour']) && $_REQUEST['hour']!='')
	$hour=vtlib_purify($_REQUEST['hour']);
if(isset($_REQUEST['day']) && $_REQUEST['day']!='')
	$day=vtlib_purify($_REQUEST['day']);
if(isset($_REQUEST['month']) && $_REQUEST['month']!='')
	$month=vtlib_purify($_REQUEST['month']);
if(isset($_REQUEST['year']) && $_REQUEST['year']!='')
	$year=vtlib_purify($_REQUEST['year']);
if(isset($_REQUEST['viewOption']) && $_REQUEST['viewOption']!='')
	$viewOption=vtlib_purify($_REQUEST['viewOption']);
if(isset($_REQUEST['view_filter']) && $_REQUEST['view_filter']!='')
	$view_filter=vtlib_purify($_REQUEST['view_filter']);
if(isset($_REQUEST['subtab']) && $_REQUEST['subtab']!='')
	$subtab=vtlib_purify($_REQUEST['subtab']);
//crmv@32334
if($_REQUEST['recurringcheck']) {
	include_once dirname(__FILE__) . '/RepeatEvents.php';
	Calendar_RepeatEvents::repeatFromRequest($focus);
}
//crmv@32334 e
//code added for returning back to the current view after edit from list view
if($_REQUEST['return_viewname'] == '')
	$return_viewname='0';
if($_REQUEST['return_viewname'] != '')
	$return_viewname=vtlib_purify($_REQUEST['return_viewname']);

$parenttab=getParentTab();

if($_REQUEST['start'] !='')
	$page='&start='.vtlib_purify($_REQUEST['start']);

if ($_REQUEST['ajaxCalendar'] != '') {
	global $returnid;
	$returnid = $return_id;
	//crmv@158871
	if ($_REQUEST['ajaxCalendar'] == 'detailedAdd') {
		if ($alert_private_invitation) $javascript_code .= "vtealert('".addslashes(getTranslatedString('LBL_ALERT_PRIVATE_INVITATION','Calendar'))."');";
		echo Zend_Json::encode(array('javascript'=>$javascript_code));
		exit;
	}
	//crmv@158871e
	// crmv@42752
	if ($_REQUEST['ajaxCalendar'] == 'onlyJson') {
		echo Zend_Json::encode(array('activityid' => $focus->id));
		exit;
	}
	// crmv@42752e
	//crmv@28295
	if ($_REQUEST['ajaxCalendar'] == 'closeTodo') {
		global $current_user;
		require_once('modules/SDK/src/Todos/Utils.php');
		getHtmlTodosList($current_user->id,'',$count);
		echo $count;
		exit;
	}
	//crmv@28295e
} else {
	
	//crmv@54375
	if($_REQUEST['return2detail'] == 'yes') {
		$return_module = 'Calendar';
		$return_action = 'DetailView';
		$return_id = $focus->id;
	}
	//crmv@54375e
	
	if ($alert_private_invitation) VteSession::set('vtealert',getTranslatedString('LBL_ALERT_PRIVATE_INVITATION','Calendar')); //crmv@158871
	
	if($_REQUEST['maintab'] == 'Calendar')
		$url = "index.php?action=".$return_action."&module=".$return_module."&view=".$view."&hour=".$hour."&day=".$day."&month=".$month."&year=".$year."&record=".$return_id."&viewOption=".$viewOption."&subtab=".$subtab."&parenttab=$parenttab";
	else
		$url = "index.php?action=$return_action&module=$return_module$view$hour$day$month$year&record=$return_id$activemode&viewname=$return_viewname$page&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']).$search;
	
	$from_module = vtlib_purify($_REQUEST['module']);
	if (!empty($from_module)) $url .= "&from_module=$from_module";
	
	header("Location: $url");
}
?>