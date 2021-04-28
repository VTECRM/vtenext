<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@17001
include_once("functions.php");

function listCalendarByRange($sd, $ed, $view_filter, $noCompletedTask){ //crmv@vte10usersFix

	global $app_strings, $mod_strings, $adb, $currentModule, $current_user,$table_prefix;

	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

	$ret = array();
	$ret['events'] = array();
  	$ret["issort"] =true;
  	$ret["start"] = php2JsTime($sd);
  	$ret["end"] = php2JsTime($ed);
  	$ret['error'] = null;
  	try{

	    global $current_user;
	    require_once('modules/Calendar/Appointment.php'); // crmv@39106
	    require_once('modules/Calendar/Date.php');
	    $start_date = array('ts'=>$sd);
	    $start_date = new vt_DateTime($start_date,true);
	    $end_date = array('ts'=>$ed);
	    $end_date = new vt_DateTime($end_date,true);

	    //crmv@26030m

		//Events - i
		$appointmentInstance = Appointment::getInstance();
		$sql = $appointmentInstance->readAppointment($current_user->id,$start_date,$end_date,'',$view_filter,true);
	    $res = $adb->query($sql);

	    $rows_event = array();
	    $activityid_array = array();
		$activityid_related_to = array();

		while ($row_tmp = $adb->fetchByAssoc($res)) {
	    	$rows_event[$row_tmp['crmid']] = $row_tmp;
	    	$activityid_array[$row_tmp['activityid']] = $row_tmp['activityid'];
	    }
	    //Events - e

	    //Task - i	//crmv@20628 crmv@103023
		$queryGenerator = QueryGenerator::getInstance('Calendar', $current_user);
		$res = $adb->query("SELECT cvid FROM ".$table_prefix."_customview WHERE entitytype = 'Calendar' AND viewname = 'Tasks' AND STATUS = 0");
		$queryGenerator->initForCustomViewById($adb->query_result($res,0,'cvid'));
		$fields = $queryGenerator->getFields();
		$fields[] = 'description';
		$queryGenerator->setFields($fields);
		$sql = $queryGenerator->getQuery();
		
		$and = '';
        $sqlFrom = $adb->formatDate($start_date->get_formatted_date(), true);
		$sqlTo = $adb->formatDate($end_date->get_formatted_date(), true);
		$dateConds = array(
			array(
				"(".$table_prefix."_activity.date_start between ? AND ?)",
				array($sqlFrom, $sqlTo)
			),
            array(
				"(".$table_prefix."_activity.date_start < ? AND ".$table_prefix."_activity.due_date > ?)",
				array($sqlFrom, $sqlTo)
			),
			array(
				"(".$table_prefix."_activity.due_date between ? AND ?)",
				array($sqlFrom, $sqlTo)
			),
		);

		//crmv@vte10usersFix
        if ($noCompletedTask == '' || $noCompletedTask == 1) {
        	$and .= " AND ".$table_prefix."_activity.status <> 'Completed' ";
        }
        //crmv@vte10usersFix e

		if( $view_filter == "all" || $view_filter == "") {

		} elseif( $view_filter == "none") {
			$and .= " and ".$table_prefix."_crmentity.smownerid = 0";
		} elseif ($view_filter == 'mine') {
			$and .= " and (".$table_prefix."_crmentity.smownerid = ".$current_user->id." ";
			//crmv@46797 
			if ($current_user->is_admin == 'on') {					
				require_once('modules/Users/CreateUserPrivilegeFile.php');
				$userGroupFocus=new GetUserGroups();
				$userGroupFocus->getAllUserGroups($current_user->id);
				$current_user_groups = $userGroupFocus->user_groups;
			}
			//crmv@46797e
			if(sizeof($current_user_groups) > 0)
			{
				$and .= " or ".$table_prefix."_groups.groupid in (". implode(",", $current_user_groups) .")";
			}
			$and.=")";
		} elseif ( $view_filter == "others") {
			$and .= " and (".$table_prefix."_crmentity.smownerid <> ".$current_user->id.")";
		} elseif (strlen($view_filter) > 0) {
			$view_filters = explode(',', $view_filter);
			$and .= " and ( ";
			$andd = array();
			foreach ($view_filters as $key => $filter) {
				if ($filter == 'mine')
					$filter = $current_user->id;
				elseif (in_array($filter,array('all','others')))
					continue;
				require('user_privileges/user_privileges_'.$filter.'.php');
				require('user_privileges/sharing_privileges_'.$filter.'.php');
				$tmp_andd = $table_prefix."_crmentity.smownerid = $filter";
				//crmv@46797 
				if ($current_user->is_admin == 'on') {					
					require_once('modules/Users/CreateUserPrivilegeFile.php');
					$userGroupFocus=new GetUserGroups();
					$userGroupFocus->getAllUserGroups($filter);	// crmv@159553
					$current_user_groups = $userGroupFocus->user_groups;
				}
				//crmv@46797e
				if(sizeof($current_user_groups) > 0)
				{
					$tmp_andd .= " or ".$table_prefix."_groups.groupid in (". implode(",", $current_user_groups) .")";
				}
				$andd[] = $tmp_andd;
			}
			$and .= implode(' or ',$andd);
			$and.=")";
		}
		$sql .= $and;
		
		// crmv@113804
		$params = array();
		$cloneTables = array();
		if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true) && $adb->isMysql()) {
			// find temp table name
			if (preg_match('/vt_tmp_[0-9a-z_]+/', $sql, $matches)) {
				$cloneTables[] = $matches[0];
			}
		}
		
		$sql = $adb->makeUnionSelect($sql, $dateConds, true, $params, $cloneTables);
		$sql .= " ORDER BY date_start ASC, time_start ASC"; //crmv@133436
		// crmv@113804e

		// crmv@150773
		if ($adb->isOracle()) {
			$sql = str_replace($table_prefix.'_activity.description', "CAST({$table_prefix}_activity.description AS VARCHAR2(2000)) as description", $sql);
		// crmv@129940
		} elseif ($adb->isMssql()) {
			$sql = str_replace($table_prefix.'_activity.description', "CAST({$table_prefix}_activity.description AS VARCHAR(2000)) as description", $sql);
		}
		// crmv@129940e crmv@150773e
		
	    $res = $adb->query($sql);
	    $rows_task = array();
	    unset($row_tmp);

  		while ($row_tmp = $adb->fetchByAssoc($res)) {
	    	$rows_task[$row_tmp['activityid']] = $row_tmp;
	    	$activityid_array[$row_tmp['activityid']] = $row_tmp['activityid'];
	    }
	    //Task - e crmv@103023e
	    
	    $actClass = CRMEntity::getInstance('Activity'); // crmv@187823

    	$related_array = getRelatedLists("Calendar",'');
	    if (in_array('Contacts',array_keys($related_array)) && count($activityid_array) > 0) {
			$relationInfo = getRelatedListInfoById($related_array['Contacts']['relationId']);
			$relatedModule = getTabModuleName($related_array['Contacts']['relatedTabId']);
			$function_name = $relationInfo['functionName'];

			global $onlyquery;
			$onlyquery = true;
			$relatedListData = $actClass->$function_name($row['activityid'], getTabid($currentModule), $relationInfo['relatedTabId'], $actions);
			unset($onlyquery);

			$activityid_where = $table_prefix.'_cntactivityrel.activityid IN ('.implode(",", $activityid_array).') ';
			$activityid_select = $table_prefix.'_cntactivityrel.activityid,'.$table_prefix.'_cntactivityrel.contactid';	//crmv@26945
			$query = VteSession::get('contacts_listquery');
			$query = str_replace('SELECT','SELECT '.$activityid_select.', ',$query);
			$query = str_replace($table_prefix.'_cntactivityrel.activityid=',$activityid_where,$query);

			$result = $adb->pquery($query, array());
			$num_rows=$adb->num_rows($result);
			$cnt_related_to = '';
			for ($i = 0; $i < $num_rows; $i++) {
				$contactid = $adb->query_result($result,$i,'contactid');	//crmv@26945
				$activityid = $adb->query_result($result,$i,'activityid');
				$firstname = $adb->query_result($result,$i,'firstname');
				$lastname = $adb->query_result($result,$i,'lastname');
				//crmv@26945
				$parent_name = $firstname.' '.$lastname;
				$cnt_related_to = '<a href="index.php?module=Contacts&action=DetailView&record='.$contactid.'">'.$parent_name.'</a><br />';
				//crmv@26945e
				$activityid_related_to[$activityid] = $activityid_related_to[$activityid].$cnt_related_to;
			}
		}
		//crmv@26030m e

		//Events - i
	    foreach ($rows_event as $row) { //crmv@26030m

	    	$start = substr($row['date_start'],0,10).' '.$row['time_start'];
	    	$end = substr($row['due_date'],0,10).' '.$row['time_end'];

	    		    	// crmv@25610 crmv@50039
	    	$start = substr(adjustTimezone($start, 0, null, false), 0, 16);
	    	$end = substr(adjustTimezone($end, 0, null, false), 0, 16);
	    	// crmv@25610 crmv@50039e

	    	////crmv@481398
	    	$time_start = strtotime($start);
	    	$time_end = strtotime($end);
			if (($time_start-$time_end)/60/60 >= 24 || date('d',$time_start) != date('d',$time_end)) $row['is_all_day_event'] = 1;
			////crmv@481398e

			//crmv@26932
			$description = textlength_check($row['description']);
			$description = str_replace('$','!#dollar#!',$description);
			//crmv@26932e

			$activitytype = getTranslatedString($row['activitytype'],'Calendar');
			$subject = $row['subject'];
			$subject = str_replace('$','!#dollar#!',$subject);	//crmv@26932
			$location = $row['location'];

			$editable = 1;
			//crmv@24041
			if (isPermitted('Calendar','EditView',$row['activityid']) != 'yes') {
				$editable = 0;
			}
			//crmv@24041e
			// crmv@120227
			$deletable = 1;
			if ($editable == 0 || isPermitted('Calendar','Delete',$row['activityid']) != 'yes') {
				$deletable = 0;
			}
			// crmv@120227e
			//crmv@24883
			$visible = 1;
    		if (isPermitted('Calendar','DetailView',$row['activityid']) != 'yes') {
				$visible = 0;
			}
			//crmv@24883e
			//crmv@158871
			if ($actClass->hasMaskedFields($row['activityid'], array('assigned_user_id' => $row['smownerid'], 'visibility' => $row['visibility']))) { // crmv@187823
				$subject = $mod_strings['Private Event'];
				$location = $app_strings['LBL_NOT_ACCESSIBLE'];
				$description = $app_strings['LBL_NOT_ACCESSIBLE'];
				$editable = 0;
			}
			//crmv@158871e
			
			$related_to = '';
			$res1 = $adb->query("SELECT ".$table_prefix."_seactivityrel.crmid
								FROM ".$table_prefix."_seactivityrel
								INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_seactivityrel.crmid
								WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_crmentity.setype <> 'Calendar' AND activityid = ".$row['crmid']);
			if ($res1) {
				while($row1=$adb->fetchByAssoc($res1)) {
					$tmp = getDetailViewOutputHtml(10,'parent_id','Related To',array('parent_id'=>$row1['crmid']),1,'','Calendar');
					if ($tmp[0] != 10) $related_to .= '<br /><b>'.$tmp[0].'</b>&nbsp;&nbsp;'.$tmp[1];
				}
			}

	    	//crmv@25396
	    	unset($status);
	    	unset($statusStr);
			$sql = 'SELECT eventstatus FROM '.$table_prefix.'_activity where activityid=?';
	    	$result = $adb->pquery($sql, array($row['activityid']));
			$num_rows=$adb->num_rows($result);
			$invited_users=Array();
			if ($num_rows > 0) {
				$status = $adb->query_result($result,0,'eventstatus');
				$statusStr = getTranslatedString('LBL_STATUS','Calendar').': '.getTranslatedString($adb->query_result($result,0,'eventstatus'),'Calendar');
			}
			//crmv@25396e

			//crmv@26807
			if ($activityid_related_to[$row['activityid']] != '') {
				$related_to = $related_to.'<br>'.'<b>'.getTranslatedString('Contacts','Contacts').'</b>&nbsp;'.$activityid_related_to[$row['activityid']];	//crmv@26945
			}
			//crmv@26807e

			$closable = $editable;	//crmv@40498

			$event = array(
		        $row['activityid'],
		        $subject,
		        php2JsTime(mySql2PhpTime($start)),
		        php2JsTime(mySql2PhpTime($end)),
		        $row['is_all_day_event'],
		        0, //more than one day event
		        //$row['InstanceType'],
		        0,//Recurring event,
		        getUserColorDb($row['smownerid'],$row['activityid']), //Color //crmv@20324
		        $editable,//editable
		        $location,
		        '',//$attends
		        $activitytype,
		        $description,
		        $related_to, //crmv@26807
		        getInvitedIcon($row['smownerid'],$row['activityid']), //icon //crmv@20324
		        'Events', //crmv@21618
		        $visible, //crmv@24883
		        $status,  //crmv@25396
		        $statusStr, //crmv@25396
		        getOwnerName($row['smownerid']),	//crmv@24270
		        $closable, //crmv@40498
				$deletable, // crmv@120227
			);
			array_push($ret['events'],$event);
		}
		//Events - e

		//Tasks - i
	    foreach ($rows_task as $row) { //crmv@26030m

	    	$subject = $row['subject'];
	    	$subject = str_replace('$','!#dollar#!',$subject);	//crmv@26932
	    	$start = substr($row['date_start'],0,10).' '.$row['time_start'];
	    	$end = substr($row['due_date'],0,10).' '.$row['time_end'];
	    	$row['is_all_day_event'] = 1;	//lo forzo a 1 per mostrare i Compiti nella riga delle attivit� che durano almeno 1 giorno
	    	$editable = 1;
	    	//crmv@24041
	    	if (isPermitted('Calendar','EditView',$row['activityid']) != 'yes') {
				$editable = 0;
			}
			//crmv@24041e
			// crmv@120227
			$deletable = 1;
			if ($editable == 0 || isPermitted('Calendar','Delete',$row['activityid']) != 'yes') {
				$deletable = 0;
			}
			//crmv@24883
			$visible = 1;
    		if (isPermitted('Calendar','DetailView',$row['activityid']) != 'yes') {
				$visible = 0;
			}
			//crmv@24883e
	    	$location = '';
	    	$activitytype = $mod_strings['Task'];

			//crmv@26932
			$description = textlength_check($row['description']);
			$description = str_replace('$','!#dollar#!',$description);
			//crmv@26932e
			
			// crmv@203978
			if ($actClass->hasMaskedFields($row['activityid'], array('assigned_user_id' => $row['smownerid'], 'visibility' => 'Standard'))) { // crmv@187823
				$subject = $mod_strings['Private Event'];
				$location = $app_strings['LBL_NOT_ACCESSIBLE'];
				$description = $app_strings['LBL_NOT_ACCESSIBLE'];
				$editable = 0;
			}
			// crmv@203978e

			$related_to = '';
			$res1 = $adb->query("SELECT ".$table_prefix."_seactivityrel.crmid
								FROM ".$table_prefix."_seactivityrel
								INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_seactivityrel.crmid
								WHERE ".$table_prefix."_crmentity.deleted = 0 AND ".$table_prefix."_crmentity.setype <> 'Calendar' AND activityid = ".$row['activityid']);
			if ($res1) {
				while($row1=$adb->fetchByAssoc($res1)) {
					$tmp = getDetailViewOutputHtml(10,'parent_id','Related To',array('parent_id'=>$row1['crmid']),1,'','Calendar');
					if ($tmp[0] != 10) $related_to .= '<br /><b>'.$tmp[0].'</b>&nbsp;&nbsp;'.$tmp[1];
				}
			}

	    	//crmv@25396
	    	unset($status);
	    	unset($statusStr);
			$sql = 'SELECT status FROM '.$table_prefix.'_activity where activityid=?';
	    	$result = $adb->pquery($sql, array($row['activityid']));
			$num_rows=$adb->num_rows($result);
			$invited_users=Array();
			if ($num_rows > 0) {
				$status = $adb->query_result($result,0,'status');
				$statusStr = getTranslatedString('LBL_STATUS','Calendar').': '.getTranslatedString($adb->query_result($result,0,'status'),'Calendar');
			}
			//crmv@25396e

			//crmv@26807
			if ($activityid_related_to[$row['activityid']] != '') {
				$related_to = $related_to.'<br>'.'<b>'.getTranslatedString('Contacts','Contacts').'</b>'.$activityid_related_to[$row['activityid']];
			}
			//crmv@26807e

			$closable = $editable; //crmv@40498

	    	$task = array(
		        $row['activityid'],
		        $subject,
		        php2JsTime(mySql2PhpTime($start)),
		        php2JsTime(mySql2PhpTime($end)),
		        $row['is_all_day_event'],
		        0, //more than one day event
		        //$row['InstanceType'],
		        0,//Recurring event,
		        getUserColorDb($row['smownerid'],$row['activityid']),
		        $editable,//editable
		        $location,
		        '',//$attends
		        $activitytype,
		        $description,
		        $related_to,   //crmv@26807
		        getInvitedIcon($row['smownerid'],$row['activityid']),
		        'Task', //crmv@21618
		        $visible, //crmv@24883
		        $status, //crmv@25396
		        $statusStr, //crmv@25396
		        getOwnerName($row['smownerid']),	//crmv@24270
		        $closable, //crmv@40498
				$deletable, // crmv@120227
			);
			array_push($ret['events'],$task);
	    }
	    //Task - e	//crmv@20628e

		// crmv@123735 crmv@201442
	    // add holidays for the requested year
	    if ($ed - $sd > 3600*24*14 && date('Y', $sd) != date('Y', $ed)) {
			// if the range is more than 2 weeks (so monthly view),
			// and years are different (showing january), show january's year
			$year = date('Y', $ed);
	    } else {
			$year = date('Y', $sd);
	    }
       	if ($current_user->holiday_countries) {
			$countries = explode(' |##| ', $current_user->holiday_countries);
       	} else {
			$countries = [];
       	}
       	$allHolidays = $holidaysCountry = [];
       	foreach ($countries as $country) {
			$holidays = HolidaysUtils::getHolidaysForYear($year, $country); // crmv@123658
			$allHolidays = array_merge($allHolidays, $holidays);
			foreach ($holidays as $d) {
				$holidaysCountry[$d][] = $country;
			}
       	}
		$allHolidays = array_values(array_unique($allHolidays));
	    
	    $ret['holidays'] = $allHolidays;
	    $ret['holidays_countries'] = $countries;
	    $ret['holidays_by_date'] = $holidaysCountry;
	    // crmv@123735e crmv@201442e

	}catch(Exception $e){
		$ret['error'] = $e->getMessage();
	}
  	return $ret;
}

function listCalendar($day, $type, $view_filter,$start_date='',$end_date='',$noCompletedTask=''){ //crmv@vte10usersFix
	$phpTime = js2PhpTime($day);
	//echo $phpTime . "+" . $type;
	switch($type){
		case "month":
			//crmv@24648
			if ($start_date != '' && $end_date != ''){
				$st = strtotime($start_date);
				$et = strtotime($end_date);
			}
			else{
				$st = mktime(0, 0, 0, date("m", $phpTime), 1, date("Y", $phpTime));
				//crmv@103401
				//get the last day of current month
				$last_day_this_month  = date('Y-m-t');
				$last_day_this_month_date  = strtotime($last_day_this_month);
				//check if returned date is not sunday
				if(date("w", $last_day_this_month_date) != 0){
					//get next sunday date
					$et = strtotime("next sunday",$last_day_this_month_date);
				}
				else{
					//default
					$et = mktime(0, 0, -1, date("m", $phpTime)+1, 1, date("Y", $phpTime));
				}
				//crmv@103401e
			}
			//crmv@24648e
			break;
		case "week":
			// crmv@155307 crmv@163649
			global $current_user;
			if ($current_user) {
				$weekstart = $current_user->column_fields['weekstart'];
				if ($weekstart === '' || $weekstart === null) $weekstart = 1;
			} else {
				$weekstart = 1;
			}
			
			$weekstart = (int) $weekstart;
			$showDate = new DateTime();
			$showDate->setTimestamp($phpTime);
			
			// Calculate how many days to reach the first day of week:
			//	- If the difference is positive, the period of the show date is the last week
			//	- If the difference is negative, the period of the show date is this week
			
			$diff = ($weekstart - ((int) $showDate->format('N') % 7));
			$diff -= $diff > 0 ? 7 : 0;
			$showDate->modify("$diff days");
			$firstDateOfWeek = $showDate->format('d');
			// crmv@170772
			$month = $showDate->format('m');
			$year = $showDate->format('Y');
			// crmv@155307e crmv@163649e
			
			$st = mktime(0, 0, 0, $month, $firstDateOfWeek, $year);
			$et = mktime(0, 0, -1, $month, $firstDateOfWeek + 7, $year);
			// crmv@170772e
			
			break;
		case "day":
			$st = mktime(0, 0, 0, date("m", $phpTime), date("d", $phpTime), date("Y", $phpTime));
			$et = mktime(0, 0, -1, date("m", $phpTime), date("d", $phpTime)+1, date("Y", $phpTime));
			break;
	}
	//echo $st . "--" . $et;
	return listCalendarByRange($st, $et, $view_filter, $noCompletedTask); //crmv@vte10usersFix
}

function addCalendar($st, $et, $sub, $ade, $event_type, $description, $location){

	$ret = array();
  	try{
		global $current_user;
		$_REQUEST['mode'] = 'create';
		$_REQUEST['subject'] = $sub;
		$_REQUEST['activitytype'] = $event_type;
		$_REQUEST['assigntype'] = 'U';
		$_REQUEST['assigned_user_id'] = $current_user->id;
		if ($ade == 1)	$_REQUEST['is_all_day_event'] = 'on';
		$_REQUEST['description'] = $description;
		$_REQUEST['location'] = $location;

		$st = js2PhpTime($st);
		$start_date = array();
		$start_date = explode(' ',php2MySqlTime($st));
		$_REQUEST['date_start'] = $start_date[0];
		$start_time = explode(':',$start_date[1]);
		$_REQUEST['time_start'] = $start_time[0].':'.$start_time[1];
		$_REQUEST['starthr'] = $start_time[0];
		$_REQUEST['startmin'] = $start_time[1];

		$et = js2PhpTime($et);
		$end_date = array();
		$end_date = explode(' ',php2MySqlTime($et));
		$_REQUEST['due_date'] = $end_date[0];
		$end_time = explode(':',$end_date[1]);
		$_REQUEST['time_end'] = $end_time[0].':'.$end_time[1];
		$_REQUEST['endhr'] = $end_time[0];
		$_REQUEST['endmin'] = $end_time[1];

		$_REQUEST['eventstatus'] = 'Planned';
		$_REQUEST['taskpriority'] = 'Low';

		$_REQUEST['ajaxCalendar'] = 'quickAdd';

		require_once('modules/Calendar/Save.php');

		global $returnid;
		if ($returnid != '') {
			$ret['IsSuccess'] = true;
	      	$ret['Msg'] = 'add success';
			$ret['Data'] = $returnid;
		}
		else {
			$ret['IsSuccess'] = false;
	    	$ret['Msg'] = "Errore nella crezione dell'evento";
		}
	}catch(Exception $e){
		$ret['IsSuccess'] = false;
	    $ret['Msg'] = $e->getMessage();
  	}
 	return $ret;
}

function updateCalendar($id, $st, $et){
	global $adb,$table_prefix;
	$ret = array();
	try{
		//crmv@20628
		$query = "select activitytype from ".$table_prefix."_activity where activityid=?";
		$result = $adb->pquery($query, array($id));
		$actType = $adb->query_result($result,0,'activitytype');
		//crmv@32334
		if (isPermitted("Calendar","EditView",$id) != 'yes'){
			$ret['IsSuccess'] = false;
			$ret['Msg'] = getTranslatedString('LBL_PERMISSION');
			return $ret;
		}
		//crmv@32334 e
		if($actType == 'Task') {
			$focus = CRMEntity::getInstance('Activity');
			$focus->id = $id;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($id,'Calendar');

			$st = js2PhpTime($st);
			$start_date = array();
			$start_date = explode(' ',php2MySqlTime($st));
			$focus->column_fields['date_start'] = $start_date[0];
			$start_time = explode(':',$start_date[1]);
			$focus->column_fields['time_start'] = $start_time[0].':'.$start_time[1];

			$et = js2PhpTime($et);
			$focus->column_fields['due_date'] = php2MySqlTime($et);

			$_REQUEST['ajaxCalendar'] = 'quickAdd';
			$focus->save('Calendar');
		}
		else {
		//crmv@20628e
			$focus = CRMEntity::getInstance('Calendar');
			$focus->id = $id;
			$focus->mode = 'edit';
			$focus->retrieve_entity_info($id,'Events');

			$st = js2PhpTime($st);
			$start_date = array();
			$start_date = explode(' ',php2MySqlTime($st));
			$focus->column_fields['date_start'] = $start_date[0];
			$start_time = explode(':',$start_date[1]);
			$focus->column_fields['time_start'] = $start_time[0].':'.$start_time[1];

			$et = js2PhpTime($et);
			$end_date = array();
			$end_date = explode(' ',php2MySqlTime($et));
			$focus->column_fields['due_date'] = $end_date[0];
			$end_time = explode(':',$end_date[1]);
			$focus->column_fields['time_end'] = $end_time[0].':'.$end_time[1];
			$_REQUEST['ajaxCalendar'] = 'quickAdd';
			$focus->save('Calendar');
		}
		$ret['IsSuccess'] = true;
		$ret['Msg'] = 'Succefully';
	}catch(Exception $e){
		$ret['IsSuccess'] = false;
		$ret['Msg'] = $e->getMessage();
	}
	return $ret;
}

//crmv@24041
function removeCalendar($id){
	require_once('include/utils/utils.php');
	$ret = array();
	try{
		$focus = CRMEntity::getInstance('Activity');
		if (isPermitted('Calendar','Delete',$id) == 'yes') {
			DeleteEntity('Calendar','Calendar',$focus,$id,$id);
			$ret['IsSuccess'] = true;
	    	$ret['Msg'] = 'Succefully';
		} else {
			$ret['IsSuccess'] = false;
		    $ret['Msg'] = getTranslatedString('LBL_PERMISSION');
		}
	}catch(Exception $e){
    	$ret['IsSuccess'] = false;
    	$ret['Msg'] = $e->getMessage();
  	}
  	return $ret;
}
//crmv@24041e

//header('Content-type:text/javascript;charset=UTF-8');
$method = $_GET["method"];
switch ($method) {
    case "add":
        $ret = addCalendar($_POST["CalendarStartTime"], $_POST["CalendarEndTime"], $_POST["CalendarTitle"], $_POST["IsAllDayEvent"], $_POST["EventType"], $_POST["Description"], $_POST["Location"]);	//crmv@17001
        break;
    case "list":
        $ret = listCalendar($_POST["showdate"], $_POST["viewtype"], $_REQUEST["filter_assigned_user_id"],$_REQUEST["date_start"],$_REQUEST["date_end"],$_REQUEST["noCompletedTask"]); //crmv@24648 //crmv@vte10usersFix
        break;
    case "update":
        $ret = updateCalendar($_POST["calendarId"], $_POST["CalendarStartTime"], $_POST["CalendarEndTime"]);
        break;
    case "remove":
        $ret = removeCalendar( $_POST["calendarId"]);
        break;
}
echo Zend_Json::encode($ret);
//crmv@17001e
?>