<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("modules/Calendar/calendarLayout.php");
require_once("modules/Calendar/Calendar.php");

global $theme,$mod_strings,$app_strings, $current_language,$currentModule,$current_user;
global $adb, $table_prefix;

$theme_path = "themes/".$theme."/";
$image_path = $theme_path."images/";


$mysel = $_REQUEST['view'];
$view_filter = $_REQUEST['view_filter'];

if($_REQUEST['file'] == 'OpenListView') {
	$smarty = new VteSmarty();
	//crmv@208173
	$smarty->assign("APP",$app_strings);
	$smarty->assign("IMAGE_PATH",$image_path);
	
	if($_REQUEST['mode'] == '0') {
		$activities[0] = getPendingActivities(0);
		$smarty->assign("ACTIVITIES",$activities);
		$smarty->display("upcomingActivities.tpl");
	} elseif($_REQUEST['mode'] == '1') {
		$activities[1] = getPendingActivities(1);
		$smarty->assign("ACTIVITIES",$activities);
		$smarty->display("pendingActivities.tpl");
	}
	die();
}

$calendar_arr = Array();
$calendar_arr['IMAGE_PATH'] = $image_path;
$date_data = array();

if ( isset($_REQUEST['day'])) {
	$date_data['day'] = $_REQUEST['day'];
}

if ( isset($_REQUEST['month'])) {
	$date_data['month'] = $_REQUEST['month'];
}

if ( isset($_REQUEST['week'])) {
	$date_data['week'] = $_REQUEST['week'];
}

if ( isset($_REQUEST['year'])) {
	if ($_REQUEST['year'] > 2037 || $_REQUEST['year'] < 1970) {
		print("<font color='red'>".$app_strings['LBL_CAL_LIMIT_MSG']."</font>");
		exit;
	}
	$date_data['year'] = $_REQUEST['year'];
}


if((isset($_REQUEST['type']) && $_REQUEST['type'] !='') || (isset($_REQUEST['n_type']) && $_REQUEST['n_type'] !='')) {
	$type = $_REQUEST['type'];
	$n_type = $_REQUEST['n_type'];
	
	if($type == 'minical') {
	
		$temp_module = $currentModule;
		$mod_strings = return_module_language($current_language,'Calendar');
		$currentModule = 'Calendar';
		$calendar_arr['IMAGE_PATH'] = $image_path;
		$calendar_arr['calendar'] = new Calendar('month',$date_data);
		$calendar_arr['view'] = 'month';
		$calendar_arr['size'] = 'small';
		if($current_user->hour_format != '') {
			$calendar_arr['calendar']->hour_format=$current_user->hour_format;
		}
		$calendar_arr['calendar']->add_Activities($current_user);
		calendar_layout($calendar_arr);
		$mod_strings = return_module_language($current_language,$temp_module);
		$currentModule = $_REQUEST['module'];
	
	} elseif($type == 'settings') {
	
		require_once('modules/Calendar/calendar_share.php');	
	
	} else {
	
		$subtab = $_REQUEST['subtab']; 
		if(empty($mysel)) {
			$mysel = 'day';
		}
		$calendar_arr['calendar'] = new Calendar($mysel,$date_data);
		
		$calendar_arr['view'] = $mysel;
		if($calendar_arr['calendar']->view == 'day') {
			$start_date = $end_date = $calendar_arr['calendar']->date_time->get_formatted_date();
		} elseif($calendar_arr['calendar']->view == 'week') {
			$start_date = $calendar_arr['calendar']->slices[0];
			$end_date = $calendar_arr['calendar']->slices[6];
		} elseif($calendar_arr['calendar']->view == 'month') {
			$start_date = $calendar_arr['calendar']->date_time->getThismonthDaysbyIndex(0);
			$end_date = $calendar_arr['calendar']->date_time->getThismonthDaysbyIndex($calendar_arr['calendar']->date_time->daysinmonth - 1);
			$start_date = $start_date->get_formatted_date();
			$end_date = $end_date->get_formatted_date();
		} elseif($calendar_arr['calendar']->view == 'year') {
			$start_date = $calendar_arr['calendar']->date_time->getThisyearMonthsbyIndex(0);
			$end_date = $calendar_arr['calendar']->date_time->get_first_day_of_changed_year('increment');
			$start_date = $start_date->get_formatted_date();
			$end_date = $end_date->get_formatted_date();
		} else {
			die("view:".$calendar_arr['calendar']->view." is not defined");
		}
		
		if($type == 'change_owner' || $type == 'activity_delete' || $type == 'change_status' || $type == 'activity_postpone' || $n_type == 'nav') {
			if($current_user->hour_format != '') {
				$calendar_arr['calendar']->hour_format=$current_user->hour_format;
			}

			if($type == 'change_status') {
				$return_id = $_REQUEST['record'];
				
				if(isset($_REQUEST['status'])) {
					$status = $_REQUEST['status'];
					$activity_type = "Task";
				} elseif(isset($_REQUEST['eventstatus'])) {
					$status = $_REQUEST['eventstatus'];
					$activity_type = "Events";
				}
				
				ChangeStatus($status,$return_id,$activity_type);
				$mail_data = getActivityMailInfo($return_id,$status,$activity_type);
				$invitee_qry = "select inviteeid from ".$table_prefix."_invitees where activityid=?";
				$invitee_res = $adb->pquery($invitee_qry, array($return_id));
				$count = $adb->num_rows($invitee_res);
				
				if($count != 0) {
					for($j = 0; $j < $count; $j++) {
						$invitees_ids[]= $adb->query_result($invitee_res,$j,"inviteeid");
					}
					$invitees_ids_string = implode(';',$invitees_ids);
					sendInvitation($invitees_ids_string,$activity_type,$mail_data['subject'],$mail_data);
				}
			}
			
			if ($_REQUEST['viewOption'] == 'hourview' && ($mysel == 'day' || $mysel == 'week' || $mysel == 'month' || $mysel == 'year')) {
				$calendar_arr['calendar']->add_Activities($current_user,'',$view_filter);
			}

			if(isset($_REQUEST['viewOption']) && $_REQUEST['viewOption'] != null && $subtab == 'event') {
			
				if($_REQUEST['viewOption'] == 'hourview') {
					
					if($calendar_arr['view'] == 'day') {
						echo getDayViewLayout($calendar_arr)."####".getEventInfo($calendar_arr,'listcnt',$view_filter);
					} elseif($calendar_arr['view'] == 'week') {
						echo getWeekViewLayout($calendar_arr)."####".getEventInfo($calendar_arr,'listcnt',$view_filter);	
					} elseif($calendar_arr['view'] == 'month') {
						echo getMonthViewLayout($calendar_arr)."####".getEventInfo($calendar_arr,'listcnt',$view_filter);
					} elseif($calendar_arr['view'] == 'year') {
						echo getYearViewLayout($calendar_arr)."####".getEventInfo($calendar_arr,'listcnt',$view_filter);
					} else {
						die("view:".$view['view']." is not defined");
					}
				} elseif($_REQUEST['viewOption'] == 'listview') {
					//To get Events List
					$activity_arr = getEventList($calendar_arr, $start_date, $end_date,'',$view_filter);
					$activity_list = $activity_arr[0];
					$navigation_arr = $activity_arr[1];
					echo constructEventListView($calendar_arr,$activity_list,$navigation_arr,$view_filter)."####".getEventInfo($calendar_arr,'listcnt',$view_filter);
				}
			} elseif($subtab == 'todo') {
				//To get Todos List
				$todo_arr = getTodoList($calendar_arr, $start_date, $end_date);
				$todo_list = $todo_arr[0];
				$navigation_arr = $todo_arr[1];
				echo constructTodoListView($todo_list,$calendar_arr,$subtab,$navigation_arr)."####".getTodoInfo($calendar_arr,'listcnt');
			}
		} elseif($type == 'view') {
			require_once('modules/Calendar/'.$_REQUEST['file'].'.php');
		} else {
			die("View option is not defined");
		}
	}
} else {
	require_once('include/Ajax/CommonAjax.php');
}

/**
 * Function to get Pending/Upcoming activities
 * @param integer  $mode     - number to differentiate upcoming and pending activities
 * return array    $values   - activities record in array format
 */
function getPendingActivities($mode, $view=''){
    global $log;
    $log->debug("Entering getPendingActivities() method ...");
    require_once('data/Tracker.php');
    require_once('include/utils/utils.php');
    //crmv@203484 removed including file

    global $currentModule;
    global $theme;
    global $focus;
    global $adb;
    global $current_language;
    global $current_user;
    global $table_prefix;
    $current_module_strings = return_module_language($current_language, 'Calendar');

    $theme_path="themes/".$theme."/";
    $image_path=$theme_path."images/";

    $today = date("Y-m-d", time());
    if($view == 'today'){
        $upcoming_condition = " AND (date_start = '$today')";
    }else if($view == 'all'){
        $upcoming_condition = " AND (date_start >= '$today')";
    }

    if($mode == 1){
        $list_query = "select ".$table_prefix."_crmentity.crmid,".$table_prefix."_crmentity.smownerid,".$table_prefix."_crmentity".
            "setype, ".$table_prefix."_recurringevents.recurringdate, ".$table_prefix."_activity.activityid, ".$table_prefix."_activity".
            ".activitytype, ".$table_prefix."_activity.date_start, ".$table_prefix."_activity.due_date, from ".$table_prefix."_activity".
            "inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid ".
            "LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid ";
        $list_query .= getNonAdminAccessControlQuery('Calendar',$current_user);
        $list_query .= " WHERE ".$table_prefix."_crmentity.deleted=0 ";
        //crmv@8398
        $list_query .= getCalendarSql();
        //crmv@8398e
        $list_query .= $upcoming_condition;

        $list_query.= " ORDER BY date_start,time_start ASC";
        $res = $adb->query($list_query);
        $noofrecords = $adb->num_rows($res);
        $open_activity_list = [];
        $noofrows = $adb->num_rows($res);
        if (count($res)>0){
            for($i=0;$i<$noofrows;$i++){
                $open_activity_list[] = Array('name' => $adb->query_result($res,$i,'subject'),
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

        $title = [];
        $title[] = $view;
        $title[] = 'myUpcoPendAct.gif';
        $title[] = 'home_myact';
        $title[] = 'showActivityView';
        $title[] = 'MyUpcumingFrm';
        $title[] = 'activity_view';

        $header = [];
        $header[] = $current_module_strings['LBL_LIST_SUBJECT'];
        $header[] = 'Type';

        $return_url = "&return_module=$currentModule&return_action=DetailView&return_id=" . ((is_object($focus)) ? $focus->id : "");
        $entries = [];

        foreach($open_activity_list as $event){
            $recur_date=str_replace('--','',$event['recurringdate']);
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
        }
        $entries[$event['id']] = [
            '0' => '<a href="index.php?action=DetailView&module='.$event["module"].'&activity_mode='.$activity_type.'&record='.$event["id"].''.$return_url.'" style="'.$font_color.';">'.$event["name"].'</a>',
            'IMAGE' => '<IMG src="'.$image_path.$event["type"].'s.gif">',
        ];
    }
    $values = [
        'noofactivities'=>$noofrecords,
        'Title'=>$title,
        'Header'=>$header,
        'Entries'=>$entries
    ];
    $log->debug("Exiting getPendingActivities method ...");
    return $values;
}
