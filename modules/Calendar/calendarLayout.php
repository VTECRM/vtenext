<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Calendar/CalendarCommon.php');
include_once("modules/Calendar/wdCalendar/php/functions.php"); //crmv@20211

/**
 *  Function creates HTML to display Events and  Todos div tags
 *  @param array    $param_arr      - collection of objects and strings
 *  @param string   $viewBox        - string 'listview' or 'hourview' or may be empty. if 'listview' means get Events ListView.if 'hourview' means gets Events HourView. if empty means get Todos ListView
 *  @param string   $subtab         - string 'todo' or 'event'. if 'todo' means Todos View else Events View
 */

function calendar_layout(& $param_arr,$viewBox='',$subtab='',$view_filter='',$activity_view='') // crmv@140887
{
	global $mod_strings, $cal_log, $current_user;
	$category = getParentTab();
	$cal_log->debug("Entering calendar_layout() method");
	$cal_header = array();
	if (isset($param_arr['size']) && $param_arr['size'] == 'small') $param_arr['calendar']->show_events = false;
	
	$cal_header['view'] = $param_arr['view'];
	$cal_header['IMAGE_PATH'] = $param_arr['IMAGE_PATH'];
	$cal_header['calendar'] = $param_arr['calendar'];
	$eventlabel = $mod_strings['LBL_EVENTS'];
	$todolabel = $mod_strings['LBL_TODOS'];

	$inIcal = (bool) $param_arr['in_ical']; // crmv@189225
	
	// Calendar header

	// crmv@189225
	if (!$inIcal) {
		include_once("modules/Calendar/addEventUI.php");
	}
	// crmv@189225e

	include_once("modules/Calendar/header.php");
	
	$smarty = new VteSmarty();
	
	$smarty->assign('EVENT_VIEW_OPTIONS', getEventViewOption($param_arr, $view_filter));
	
	get_cal_header_data($param_arr, $viewBox, $subtab, $view_filter);
	
	unset($activity_view_param);
	if ($activity_view != '') {
		$activity_view_param = '&activity_view=' . urlencode($activity_view);
	} else {
		$activity_view_param = '&activity_view=' . urlencode($current_user->column_fields['activity_view']);
	}
	
	// crmv@68357
	// if year is passed (assuming also month and day), pass it to the wdcalendar
	if (!empty($_REQUEST['year']) && $cal_header['calendar']->date_time) {
		$activity_view_param .= '&showday=' . urlencode($cal_header['calendar']->date_time->get_formatted_date());
	}
	// pass the ical info
	if ($inIcal && is_array($param_arr['icals']) && count($param_arr['icals']) > 0) { // crmv@189225
		// use only the first one
		$ical = $param_arr['icals'][0];
		$activity_view_param .= '&from_module=Messages&from_crmid=' . intval($ical['messagesid']) . '&icalid=' . intval($ical['sequence']);
	}
	// crmv@68357
	
	$calendarUrl = "index.php?module=Calendar&action=CalendarAjax&file=wdCalendar$activity_view_param";
	
	$smarty->assign('CALENDAR_URL', $calendarUrl);
	$smarty->assign('RELATED_ADD', $_REQUEST['related_add']);
	$smarty->assign('IN_ICAL', $inIcal); // crmv@189225
	
	$smarty->display('modules/Calendar/Calendar.tpl');
	
	$cal_log->debug("Exiting calendar_layout() method");
}

/**
 * Function creates HTML to display number of Events, Todos and pending list in calendar under header(Eg:Total Events : 5, 2 Pending / Total To Dos: 4, 1 Pending)
 * @param array  $cal_arr   - collection of objects and strings
 * @param string $viewBox   - string 'listview' or 'hourview'. if 'listview' means Events ListView.if 'hourview' means Events HourView.
 */
function get_cal_header_data(& $cal_arr,$viewBox,$subtab,$view_filter='') //crmv@7633s
{
	global $mod_strings,$cal_log,$current_user,$adb,$theme;
	$cal_log->debug("Entering get_cal_header_data() method...");
	global $current_user,$app_strings;
	$date_format = $current_user->date_format;
	$format = $cal_arr['calendar']->hour_format;
	$hour_startat = timeString(array('hour'=>date('H:i',(time() + (60 * 60))),'minute'=>0),'24');
	$hour_endat = timeString(array('hour'=>date('H:i',(time() + (60 * 60*2))),'minute'=>0),'24');
	$time_arr = getaddEventPopupTime($hour_startat,$hour_endat,$format);
	$temp_ts = $cal_arr['calendar']->date_time->ts;
	//To get date in user selected format
	$temp_date = (($date_format == 'dd-mm-yyyy')?(date('d-m-Y',$temp_ts)):(($date_format== 'mm-dd-yyyy')?(date('m-d-Y',$temp_ts)):(($date_format == 'yyyy-mm-dd')?(date('Y-m-d', $temp_ts)):(''))));
	$eventlist=getActivityTypeValues('all','string_separated_by',';');
	$headerdata = "";
	//        $headerdata .="<div style='display: none;' id='mnuTab'>";
	//        $headerdata .="<form name='EventViewOption' method='POST' action='index.php' style='display: none;'>";
	$headerdata .="<table align='center' border='0' cellpadding='5' cellspacing='0' width='98%' style='display: none;'>";
	$headerdata .="<tr><td colspan='3'>&nbsp;</td></tr>";
	if(isPermitted("Calendar","EditView") == "yes")
	{
		//crmv@20480	//crmv@22622
		if ($_COOKIE['crmvWinMaxStatus'] == 'close') {
			$minImg = "_min";
			$minIcon = "";
			//$menuOffset = ",-16"; //crmv@vte10usersFix
		}
		else {
			$minImg = "";
			$minIcon = "md-lg";
			//$menuOffset = ""; //crmv@vte10usersFix
		}
		//crmv@vte10usersFix
		//crmv@23696
		
		// Very very very very bad, I know .....
		if ($theme == 'next') {
			$headerdata .="<tr>
				<td id='addButton'>
					<button type=\"button\" class=\"crmbutton with-icon success crmbutton-nav\" onMouseOver='fnAddEvent(this,\"addEventDropDown\",\"".$temp_date."\",\"".$temp_date."\",\"".$time_arr['starthour']."\",\"".$time_arr['startmin']."\",\"".$time_arr['startfmt']."\",\"".$time_arr['endhour']."\",\"".$time_arr['endmin']."\",\"".$time_arr['endfmt']."\",\"".$viewBox."\",\"".$subtab."\",\"".$eventlist."\",\"".$view_filter."\",-20,\"".$date_format."\");'
						onmouseout='fnRemoveEvent();'>
						<i class=\"vteicon {$minIcon}\">add</i>
						".$app_strings['LBL_CREATE_BUTTON_LABEL']." ".$mod_strings['Calendar']."				
					</button>
				</td>";
		} else {
        $headerdata .="<tr>
                <td id='addButton'>
				<i class=\"vteicon {$minIcon}\" title='".$app_strings['LBL_CREATE_BUTTON_LABEL']." ".$mod_strings['Calendar']."'
					onMouseOver='fnAddEvent(this,\"addEventDropDown\",\"".$temp_date."\",\"".$temp_date."\",\"".$time_arr['starthour']."\",\"".$time_arr['startmin']."\",\"".$time_arr['startfmt']."\",\"".$time_arr['endhour']."\",\"".$time_arr['endmin']."\",\"".$time_arr['endfmt']."\",\"".$viewBox."\",\"".$subtab."\",\"".$eventlist."\",\"".$view_filter."\",-20,\"".$date_format."\");'
					onmouseout='fnRemoveEvent();'>add</i>
                </td>";
		}
        
		//crmv@23696e //crmv@20480e	//crmv@22622e //crmv@vte10usersFix e
	}
	else
	{
		$headerdata .="<tr><td>&nbsp;</td>";
	}
	$headerdata .="<td align='center' width='53%'><span id='total_activities'>";

	$headerdata .= "</span></td>";
	$headerdata .= "<td align='center' width='30%' id='filterCalendar'><table border=0 cellspacing=0 cellpadding=2><tr>";//crmv@7633s
	//crmv@7633
	//crmv@20629
	//crmv@36555
	$cal_class = CRMEntity::getInstance('Calendar');
	$headerdata .= "<td nowrap style='padding-top:7px'>".crmvGetAssignedToHTML($cal_arr,$cal_class->getShownUserId($current_user->id),"events",$view_filter)."</td></tr></table></td>";
	//crmv@36555 e
	//crmv@20221
	$headerdata .= "<td align='center' width='30%' id='filterUserCalendar'><table border=0 cellspacing=0 cellpadding=0 width='100%'><tr>";//crmv@7633s
	/*$headerdata .= "<td nowrap>".crmvGetUserAssignedToHTML($cal_arr,getAssignedTo(16),"events",$view_filter)."</td></tr></table>
                                </td>";*/
	//crmv@36555
	$headerdata .= "<td nowrap>".crmvGetUserAssignedToHTML($cal_class->getShownUserId($current_user->id,true),"events",false,$view_filter)."</td></tr></table>
                                </td>";
	//crmv@36555 e
	//crmv@20221e
	//crmv@20629e
	$headerdata .= "</tr>
                </table>";
	//        $headerdata .= "</form>";
	//crmv@7633e
	
	// crmv@131364
	// Terrible fix for the users list. The list is copied into the iframe, but data attribute is not preserved,
	// so I have to avoid the double checkbox by setting this attribute
	$headerdata .= '<script>jQuery("#filterDivCalendar").find("input[type=checkbox]").data("mdproc", true);</script>';
	// crmv@131364e
	
	echo $headerdata;
	$cal_log->debug("Exiting get_cal_header_data() method...");
}

//crmv@7633
function crmvGetAssignedToHTML(& $cal,$assignedto,$toggletype,$view_filter)
{
	global $app_strings,$current_user,$mod_strings;//crmv@20629
	//crmv@vte10usersFix
	$htmlFilterButtons = '<div style="display:none;">';
	$htmlFilterButtons .= '<div id="filterClick_mine" onclick="filterAssignedUser(\'mine\')"></div>';
	$htmlFilterButtons .= '<div id="filterClick_selected" onclick="filterAssignedUser(\'selected\')"></div>';
	//crmv@vte10usersFix e
	// crmv@43117	crmv@OPER6317
	if ($toggletype == 'task') {
		$htmlStr .= $mod_strings['LBL_CAL_FILTER'].": <span id='task_assign_user' class='dvtCellInfo'><select name='filter_assigned_user_id' class='detailedViewTextBox notdropdown' style='width:100px !important' id='filter_view_Option' onChange='filterAssignedUser(this.value);'>";//crmv@20629
	} else {
		$htmlStr .= $mod_strings['LBL_CAL_FILTER'].": <span id='event_assign_user' class='dvtCellInfo'><select name='filter_assigned_user_id' class='detailedViewTextBox notdropdown' style='width:100px !important' id='filter_view_Option' onChange='reloadShownList(this.value);filterAssignedUser(this.value);'>";	//crmv@20629	//crmv@26738
	}
	// crmv@43117e	crmv@OPER6317e

	// aggiungo i valori di default
	// crmv@20211
	/*if($view_filter == all)
		$htmlStr .= "<option value='all' selected>".$app_strings['LBL_ASSIGNED_TO_ALL']."</option>'";
	else    $htmlStr .= "<option value='all' >".$app_strings['LBL_ASSIGNED_TO_ALL']."</option>";*/

	if($view_filter == 'selected')
		$htmlStr .= "<option value='selected' selected>".$app_strings['LBL_ASSIGNED_TO_SELECTED']."</option>'";
	else    $htmlStr .= "<option value='selected' >".$app_strings['LBL_ASSIGNED_TO_SELECTED']."</option>";

	if($view_filter == 'mine') // crmv@167234
		$htmlStr .= "<option value='mine' selected>".$app_strings['LBL_ASSIGNED_TO_ME']."</option>";
	else    $htmlStr .= "<option value='mine' >".$app_strings['LBL_ASSIGNED_TO_ME']."</option>";

	/*if($view_filter == others)
		$htmlStr .= "<option value='others' selected>".$app_strings['LBL_ASSIGNED_TO_OTHERS']."</option>";
	else	$htmlStr .= "<option value='others' >".$app_strings['LBL_ASSIGNED_TO_OTHERS']."</option>";*/
	// crmv@20211e

	//crmv@20629
	foreach($assignedto as $key_one=>$name)
	{
		if($key_one != $current_user->id) {
			if($key_one == $view_filter)
				$htmlStr .= "<option value='".$key_one."' selected>".$name['name']."</option>";
			else
				$htmlStr .= "<option value='".$key_one."' >".$name['name']."</option>";
			$htmlFilterButtons .= '<div id="filterClick_'.$key_one.'" onclick="filterAssignedUser(\''.$key_one.'\')"></div>';//crmv@vte10usersFix
		}
	}
	//crmv@20629e

	$htmlStr .= '</select>
			</span>';

	//crmv@vte10usersFix
	$htmlFilterButtons .= '</div>';

	return $htmlStr.$htmlFilterButtons;
	//crmv@vte10usersFix e
}
//crmv@7633e

//crmv@20211	//crmv@20629	//crmv@33996 // crmv@98866
function crmvGetUserAssignedToHTML($assignedto,$toggletype,$only_contents=false,$view_filter='selected')
{
	global $mod_strings, $app_strings, $current_user;
	
	$ECU = EntityColorUtils::getInstance();

	$checkFunction = 'onClick="filterAssignedSingleUser(this.value);updateShownList();"';
	if (!$only_contents)
		$htmlStr .= '<div id="filterDivCalendar" style="border:none; overflow-x: hidden; overflow-y: auto; position: relative; padding-top:4px; max-width:250px">'; // crmv@43117 crmv@115361
	if($toggletype == 'task')
		$htmlStr .= '<span id="task_assign_user">';
	else
		$htmlStr .= '<span id="assign_user">';
	$htmlStr .= '<table style="width:100%; border:0;" cellpadding="1" cellspacing="4">'; // crmv@43117
	// crmv@201442
	if ($current_user->holiday_countries) {
		$countries = explode(' |##| ', $current_user->holiday_countries);
		if (count($countries) > 0) {
			$htmlStr .= '<tr><td><div class="checkbox"><label><input type="checkbox" class="checkbox" disabled="disabled" /></label></div></td><td class="tg-holiday" style="width:100%;">'.$mod_strings['LBL_HOLIDAYS'].'</td></tr>';	//crmv@51216
		}
	}
	// crmv@201442e
	$assignedto_tmp = array();
	if ($view_filter == 'mine') {
		$assignedto_tmp['mine'] = array('name'=>$assignedto['mine']['name'],'selected'=>1);
		$assignedto_tmp['all'] = array('name'=>$assignedto['all']['name'],'selected'=>0);
		$assignedto_tmp['none'] = array('name'=>$assignedto['none']['name'],'selected'=>0);//crmv@26030m
		$assignedto_tmp['others'] = array('name'=>$assignedto['others']['name'],'selected'=>0);
		unset($assignedto['mine']);
		unset($assignedto['all']);
		unset($assignedto['none']);//crmv@26030m
		unset($assignedto['others']);
		foreach($assignedto as $key_one=>$name)
			$assignedto_tmp[$key_one] = array('name'=>$name['name'],'selected'=>0);
	}
	else {
		$assignedto_tmp['mine'] = $assignedto['mine'];
		$assignedto_tmp['all'] = $assignedto['all'];
		$assignedto_tmp['none'] = $assignedto['none'];//crmv@26030m
		$assignedto_tmp['others'] = $assignedto['others'];
		unset($assignedto['mine']);
		unset($assignedto['all']);
		unset($assignedto['none']);//crmv@26030m
		unset($assignedto['others']);
		foreach($assignedto as $key_one=>$name)
			$assignedto_tmp[$key_one] = $name;
	}
	$assignedto = $assignedto_tmp;
	foreach($assignedto as $key_one=>$name)
	{
		$checked = '';
		$bgColor = '';
		$textColor = '';
		if(in_array($key_one,array('all','mine','others','none'))) {//crmv@26030m
			if ($name['selected'] == 1)
				$checked = 'checked="checked"';
			if ($key_one == 'mine') {
				$bgColor = '#'.substr(getUserColorDb($current_user->id),(1*6),6);
				$textColor = $ECU->getTitleTextColor($bgColor);
				$otherUsers = '<tr><td><div class="checkbox"><label><input id="singleUserMine" class="checkbox" type="checkbox" name="singleUser" value="mine" '.$checked.' '.$checkFunction.'></label></div></td><td style="background-color:'.$bgColor.'; color:'.$textColor.'; cursor: pointer; width:100%;"><label for="singleUserMine">'.$app_strings['LBL_ASSIGNED_TO_ME'].'</label></td></tr>';
			}
			elseif ($key_one == 'all')
				$otherUsers = '<tr><td><div class="checkbox"><label><input id="singleUserAll" class="checkbox" type="checkbox" name="singleUser" value="all" '.$checked.' '.$checkFunction.'></label></div></td><td class="list" style="cursor: pointer;"><label for="singleUserAll">'.$app_strings['LBL_ASSIGNED_TO_ALL'].'</label></td></tr>';
			//crmv@26030m
			elseif ($key_one == 'none')
				$otherUsers = '<tr '.($checked ? '' : 'style="display:none"').'><td><div class="checkbox"><label><input id="singleUserNone" type="checkbox" name="singleUser" value="none" '.$checked.' '.$checkFunction.'></label></div></td><td class="list" style="cursor: pointer;"><label for="singleUserNone">'.$app_strings['LBL_NOBODY'].'</label></td></tr>'; // crmv@43117
			//crmv@26030m e
			elseif ($key_one == 'others')
				$otherUsers = '<tr><td><div class="checkbox"><label><input id="singleUserOthers" type="checkbox" name="singleUser" value="others" '.$checked.' '.$checkFunction.'></label></div></td><td class="list" style="cursor: pointer;"><label for="singleUserOthers">'.$app_strings['LBL_ASSIGNED_TO_OTHERS'].'</label></td></tr>';
		}
		else {
			if ($name['selected'] == 1)
				$checked = 'checked="checked"';
			$bgColor = '#'.substr(getUserColorDb($key_one),(1*6),6);
			$textColor = $ECU->getTitleTextColor($bgColor);
			$otherUsers = '<tr><td><div class="checkbox"><label><input id="singleUser'.$key_one.'" type="checkbox" name="singleUser" value="'.$key_one.'" '.$checked.' '.$checkFunction.'></label></div></td><td nowrap style="background-color:'.$bgColor.'; color:'.$textColor.'; cursor: pointer;"><label for="singleUser'.$key_one.'" title="'.$name['name'].'">'.$name['name'].'</label></td></tr>'; //crmv@20874 crmv@115361
		}
		//$htmlStr .= "<tr><td width='20px'>$otherUsers</td></tr>";
		$htmlStr .= $otherUsers;
	}
	$htmlStr .= '</table></span>';
	if (!$only_contents)
		$htmlStr .= '</div>';
	return $htmlStr;
}
//crmv@20211e	//crmv@20629e	//crmv@33996e // crmv@98866 end

/**
 * Function creates HTML select statement to display View selection box
 * @param array  $cal     - collection of objects and strings
 * @param string $viewBox - string 'listview' or 'hourview'. if 'listview' means get Events ListView.if 'hourview' means get Events HourView.
 * return string $view   - html selection box
 */
function getEventViewOption(& $cal, $view_filter)
{
        global $mod_strings,$cal_log;
        $category = getParentTab();

        $cal_log->debug("Entering getEventViewOption() method...");
        $view = "<input type='hidden' name='view' value='".$cal['calendar']->view."'>
                        <input type='hidden' name='hour' value='".$cal['calendar']->date_time->hour."'>
                        <input type='hidden' name='day' value='".$cal['calendar']->date_time->day."'>
                        <input type='hidden' name='week' value='".$cal['calendar']->date_time->week."'>
                        <input type='hidden' name='month' value='".$cal['calendar']->date_time->month."'>
                        <input type='hidden' name='year' value='".$cal['calendar']->date_time->year."'>
                        <input type='hidden' name='parenttab' value='".$category."'>
                        <input type='hidden' name='module' value='Calendar'>
                        <input type='hidden' name='return_module' value='Calendar'>
                        <input type='hidden' name='action' value=''>
                        <input type='hidden' name='return_action' value=''>
                        <input type='hidden' name='filter_assigned_user_id' value='".$view_filter."'>";
        //crmv@62447
        if($_REQUEST['related_add']){
        	$view .= "<input type='hidden' name='from_related' id='from_related' value='1'>";
        }else{
        	$view .= "<input type='hidden' name='from_related' id='from_related' value='0'>";
        }
        //crmv@62447e
        $cal_log->debug("Exiting getEventViewOption() method...");
        return $view;
}

// kept for compatibility
function getEventInfo(& $cal, $mode,$view_filter) {
	return '';
}


//crmv@7210
// select a colo from a predefined palette
function crmv_get_user_color($uid){

	global $display_calendar_multiple_colors;

//	if($display_calendar_multiple_colors == true) {

		$colormap = array(
			'#FFFF00',
			'#CCFF00',
			'#FFFF00',
			'#99FF33',
			'#FFCC66',
			'#CCCC33',
			'#3399FF',
			'#FF6600',
			'#CC6699',
			'#CCFF99',
			'#3399CC',
			'#9966CC',
			'#996699',
			'#BBBBBB',
			'#669999',
			'#FFFF33',
			'#FFCCCC',
			'#FFE6CC',
			'#E6FFCC',
			'#CCFFCC',
			'#9999FF',
			'#CC99FF',
			'#FF99FF',
			'#FF99CC',
			'#E0E0E0',
			'#FFFF33',
			'#33FF33',
			'#FFFFCC',
			'#99FF99',
			'#FFFF99',
			'#FF00FF',
			'#CCCC00',
		);

		$index = $uid % (count($colormap));
		$textcolor = splitCompColor($colormap[$index]);
		return Array($colormap[$index],$textcolor);


//	} else {
//		$backcolor = "#C8C8C8";
//		$textcolor = splitCompColor($backcolor);
//		return Array($backcolor,$textcolor);
//	}

}

function splitCompColor($color) {

    //get red, green and blue
    $r = substr($color, 0, 2);
    $g = substr($color, 2, 2);
    $b = substr($color, 4, 2);

    //revert them, they are decimal now
    $r = 135+hexdec($r);
    $g = 135+hexdec($g);
    $b = 135+hexdec($b);

    //now convert them to hex and return.
    return substr(dechex($r),-1,2).substr(dechex($g),-1,2).substr(dechex($b),-1,2);
}
//crmv@7210s