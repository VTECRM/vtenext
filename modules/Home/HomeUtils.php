<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * this file will contain the utility functions for Home module
 */

/**
 * function to get upcoming activities for today
 * @param integer $maxval - the maximum number of records to display
 * @param integer $calCnt - returns the count query if this is set
 * return array    $values   - activities record in array format
 */
function homepage_getUpcomingActivities($maxval,$calCnt){
	require_once("data/Tracker.php");
	require_once('include/utils/utils.php');
	
	global $adb,$table_prefix;
	global $current_user;

	$today = date("Y-m-d", time());
	$upcoming_condition = " AND date_start = '$today' ";

	$list_query = " select ".$table_prefix."_crmentity.crmid,".$table_prefix."_crmentity.smownerid,".$table_prefix."_crmentity.setype,".$table_prefix."_activity.* 
	from ".$table_prefix."_activity 
	inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid 
	LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid  ";
	$list_query .= getNonAdminAccessControlQuery('Calendar',$current_user);
	$list_query .= "WHERE 
	".$table_prefix."_crmentity.deleted=0 ";
//crmv@8398 		
	$list_query.=getCalendarSql();
//crmv@8398e
	$list_query.=" ".$upcoming_condition;
	$list_query = listQueryNonAdminChange($list_query, 'Calendar');
	$list_query.= " ORDER BY date_start,time_start ASC";
	$res = $adb->limitQuery($list_query,0,$maxval);
	$noofrecords = $adb->num_rows($res);
	if($calCnt == 'calculateCnt'){
		return $noofrecords;
	}
	
	$open_activity_list = array();
	if ($noofrecords>0){
		for($i=0;$i<$noofrecords;$i++){
			//crmv@49816
			$ownerId = $adb->query_result($res,$i,'smownerid');
			$visibility = $adb->query_result($res,$i,'visibility');
			if (!is_admin($current_user) && $ownerId != $current_user->id && $visibility == 'Private' && isCalendarInvited($current_user->id,$adb->query_result($res,$i,'activityid'),true) == 'no') { //crmv@158871
				$subject = getTranslatedString('Private Event','Calendar');
			} else {
            	$subject = $adb->query_result($res,$i,'subject');
			}
			//crmv@49816e
			$open_activity_list[] = array('name' => $subject, //crmv@49816
										'id' => $adb->query_result($res,$i,'activityid'),
										'type' => $adb->query_result($res,$i,'activitytype'),
										'module' => $adb->query_result($res,$i,'setype'),
										'date_start' => getDisplayDate($adb->query_result($res,$i,'date_start')),
										'due_date' => getDisplayDate($adb->query_result($res,$i,'due_date')),
										'recurringdate' => getDisplayDate($adb->query_result($res,$i,'recurringdate')),
										'priority' => $adb->query_result($res,$i,'priority'),
									);
		}
	}
	$values = getActivityEntries($open_activity_list);
	$values['ModuleName'] = 'Calendar';
	$values['search_qry'] = "&action=ListView&from_homepage=upcoming_activities";
	
	return $values;
}

/**
 * this function returns the activity entries in array format
 * it takes in an array containing activity details as a parameter
 * @param array $open_activity_list - the array containing activity details
 * return array $values - activities record in array format
 */
function getActivityEntries($open_activity_list){
	global $current_language, $app_strings;
	$current_module_strings = return_module_language($current_language, 'Calendar');
	if(!empty($open_activity_list)){
		$header=array();
		$header[] =$current_module_strings['LBL_LIST_SUBJECT'];
		$header[] =$current_module_strings['Type'];
		
		$entries = array();
		foreach($open_activity_list as $event){
			$recur_date=preg_replace('/--/','',$event['recurringdate']);
			if($recur_date!=""){
				$event['date_start']=$event['recurringdate'];
			}
			$font_color_high = "color:#00DD00;";
			$font_color_medium = "color:#DD00DD;";
	
			switch ($event['priority']){
				case 'High':
					$font_color=$font_color_high;
					break;
				case 'Medium':
					$font_color=$font_color_medium;
					break;
				default:
					$font_color='';
			}
	
			if($event['type'] != 'Task' && $event['type'] != 'Emails' && $event['type'] != ''){
				$activity_type = 'Events';
			}else{
				$activity_type = 'Task';
			}
			
			$entries[$event['id']] = array(
					'0' => '<a href="index.php?action=DetailView&module='.$event["module"].'&activity_mode='.$activity_type.'&record='.$event["id"].'" style="'.$font_color.';">'.$event["name"].'</a>',
					'1' => $event["type"],
					);
		}
		$values = array('noofactivities'=>count($open_activity_list),'Header'=>$header,'Entries'=>$entries);
	}else{
		$values = array('noofactivities'=>count($open_activity_list), 'Entries'=>
			'<div class="componentName">'.$app_strings['LBL_NO_DATA'].'</div>');
	}
	return $values;
}


/**
 * function to get pending activities for today
 * @param integer $maxval - the maximum number of records to display
 * @param integer $calCnt - returns the count query if this is set
 * return array    $values   - activities record in array format
 */
function homepage_getPendingActivities($maxval,$calCnt){
	require_once("data/Tracker.php");
	require_once("include/utils/utils.php");
	require_once('include/utils/CommonUtils.php');
	
	global $adb,$table_prefix;
	global $current_user;
	require('user_privileges/user_privileges_'.$current_user->id.'.php');
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	
	$today = date("Y-m-d", time());
	$upcoming_condition = " AND date_start = '$today' ";

	$list_query = " select ".$table_prefix."_crmentity.crmid,".$table_prefix."_crmentity.smownerid,".$table_prefix."_crmentity.setype,".$table_prefix."_activity.* 
	from ".$table_prefix."_activity 
	inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid 
	LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid  ";
	$list_query .= getNonAdminAccessControlQuery('Calendar',$current_user);
	$list_query .= "WHERE 
	".$table_prefix."_crmentity.deleted=0 ";  
//crmv@8398 		
	$list_query.=getCalendarSql();
//crmv@8398e
	$list_query.=" ".$upcoming_condition;
	$res = $adb->limitQuery($list_query,0,$maxval);
	$noofrecords = $adb->num_rows($res);
	if($calCnt == 'calculateCnt'){
		return $noofrecords;
	}
	
	$open_activity_list = array();
	$noofrows = $adb->num_rows($res);
	if ($res){ // crmv@203101
		for($i=0;$i<$noofrows;$i++){
			//crmv@49816
			$ownerId = $adb->query_result($res,$i,'smownerid');
			$visibility = $adb->query_result($res,$i,'visibility');
			if (!is_admin($current_user) && $ownerId != $current_user->id && $visibility == 'Private' && isCalendarInvited($current_user->id,$adb->query_result($res,$i,'activityid'),true) == 'no') { //crmv@158871
				$subject = getTranslatedString('Private Event','Calendar');
			} else {
				$subject = $adb->query_result($res,$i,'subject');
			}
			//crmv@49816e
			$open_activity_list[] = array(
				'name' => $subject, //crmv@49816
				'id' => $adb->query_result($res,$i,'activityid'),
				'type' => $adb->query_result($res,$i,'activitytype'),
				'module' => $adb->query_result($res,$i,'setype'),
				'date_start' => getDisplayDate($adb->query_result($res,$i,'date_start')),
				'due_date' => getDisplayDate($adb->query_result($res,$i,'due_date')),
				'recurringdate' => getDisplayDate($adb->query_result($res,$i,'recurringdate')),
				'priority' => $adb->query_result($res,$i,'priority'),
			);
		}
	}	
	$values = getActivityEntries($open_activity_list);
	$values['ModuleName'] = 'Calendar';
	$values['search_qry'] = "&action=ListView&from_homepage=pending_activities";
	
	return $values;
}


/**
 * this function returns the number of columns in the home page for the current user.
 * if nothing is found in the database it returns 4 by default
 * return integer $data - the number of columns
 */
function getNumberOfColumns(){
	global $current_user, $adb,$table_prefix;
	
	$sql = "select * from ".$table_prefix."_home_layout where userid=?";
	$result = $adb->pquery($sql, array($current_user->id));
	
	if($adb->num_rows($result)>0){
		$data = $adb->query_result($result,0,"layout");
	}else{
		$data = 4;	//default is 4 column layout for now
	}
	return $data;
}
?>