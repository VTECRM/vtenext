<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Calendar/CalendarCommon.php');
require_once('modules/Calendar/iCal/includeAll.php'); // crmv@68357

// Task is used to store customer information.
class Activity extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'activityid';
	var $reminder_table ;
	var $tab_name = Array();

	var $tab_name_index = Array();

	var $column_fields = Array();
	var $sortby_fields = Array('subject','due_date','date_start','smownerid','activitytype','lastname');	//Sorting is added for due date and start date

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'assigned_user_id', 'contactname', 'contact_phone', 'contact_email', 'parent_name');

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
       'Close'=>Array('activity'=>'status'),
       'Type'=>Array('activity'=>'activitytype'),
       'Subject'=>Array('activity'=>'subject'),
       'Related to'=>Array('seactivityrel'=>'crmid'),
       'Start Date'=>Array('activity'=>'date_start'),
       'Start Time'=>Array('activity'=>'time_start'),
       'End Date'=>Array('activity'=>'due_date'),
       'End Time'=>Array('activity'=>'time_end'),
       'Assigned To'=>Array('crmentity'=>'smownerid'),
       'Contact Name'=>Array('contactdetails'=>'lastname')
       );

       var $range_fields = Array(
		'name',
		'date_modified',
		'start_date',
		'id',
		'status',
		'date_due',
		'time_start',
		'description',
		'contact_name',
		'priority',
		'duehours',
		'dueminutes',
		'location'
	   );


       var $list_fields_name = Array(
       'Close'=>'status',
       'Type'=>'activitytype',
       'Subject'=>'subject',
       'Contact Name'=>'lastname',
       'Related to'=>'parent_id',
       'Start Date & Time'=>'date_start',
       'End Date & Time'=>'due_date',
	   'Recurring Type'=>'recurringtype',
       'Assigned To'=>'assigned_user_id');

       var $list_link_field= 'subject';

	//crmv@10759
	var $search_base_field = 'subject';
	//crmv@10759 e

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'due_date';
	var $default_sort_order = 'ASC';

	//crmv@40525
	// Event states processed by SendReminder.php. If it is empty all the states are processd.
	var $send_reminder_states = array('Planned');
	//crmv@40525e

	function __construct() {
		global $table_prefix;

		// crmv@37004
		parent::__construct();
		$this->relation_table = $table_prefix.'_seactivityrel';
		$this->relation_table_id = 'activityid';
		$this->relation_table_otherid = 'crmid';
		$this->relation_table_module = '';
		$this->relation_table_othermodule = '';
		$this->extra_relation_tables = array(
			'Contacts' => array(
				'relation_table' => "{$table_prefix}_cntactivityrel",
				'relation_table_id' => 'activityid',
				'relation_table_otherid' => 'contactid',
				//relation_table_module
				//relation_table_othermodule
			),
		);
		// crmv@37004e

		$this->reminder_table = $table_prefix.'_activity_reminder';
		$this->table_name = $table_prefix."_activity";
		// crmv@187823
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_activity',$table_prefix.'_activitycf', $table_prefix.'_activity_organizer');
		$this->tab_name_index = Array(
			$table_prefix.'_crmentity'=>'crmid',
			$table_prefix.'_activity'=>'activityid',
			$table_prefix.'_seactivityrel'=>'activityid',
			$table_prefix.'_cntactivityrel'=>'activityid',
			$table_prefix.'_salesmanactivityrel'=>'activityid',
			$table_prefix.'_activity_reminder'=>'activity_id',
			$table_prefix.'_recurringevents'=>'activityid',
			$table_prefix.'_activitycf'=>'activityid',
			$table_prefix.'_activity_organizer' => 'activityid'
		);
		// crmv@187823e
		$this->customFieldTable = Array($table_prefix.'_activitycf', 'activityid');
		$this->log = LoggerManager::getLogger('Calendar');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Calendar');
	}
	
	// crmv@187823
	function retrieve_entity_info($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $table_prefix;
		$r = parent::retrieve_entity_info($record, $module, $dieOnError, $onlyFields);
		
		// add organizer, since it's missing because this is the damned calendar and nothing works!!
		$this->column_fields['organizer'] = getSingleFieldValue($table_prefix.'_activity_organizer', 'email', $this->table_index, $this->id);
		
		return $r;
	}
	
	function retrieve_entity_info_no_html($record, $module, $dieOnError=true, $onlyFields = array()) {
		global $table_prefix;
		$r = parent::retrieve_entity_info_no_html($record, $module, $dieOnError, $onlyFields);
		
		// add organizer, since it's missing because this is the damned calendar and nothing works!!
		$this->column_fields['organizer'] = getSingleFieldValue($table_prefix.'_activity_organizer', 'email', $this->table_index, $this->id, false);
		
		return $r;
	}
	// crmv@187823e

	function save_module($module)
	{
		global $adb,$table_prefix, $current_user; // crmv@149399
		
		if ($module == 'Events') $module = 'Calendar'; // crmv@74608
		
		// crmv@149399
		// fill the full datetime columns
		if (!empty($this->column_fields['date_start']) && !empty($this->column_fields['due_date'])) {
			if ($adb->isMysql()) {
				$adb->pquery(
					"UPDATE {$this->table_name}
					SET activity_start = CAST(".$adb->sql_concat(array('date_start', "' '", 'time_start'))." AS DATETIME),
					activity_end = CAST(".$adb->sql_concat(array('due_date', "' '", 'time_end'))." AS DATETIME)
					WHERE {$this->table_index} = ?",
					array($this->id)
				);
			} elseif ($adb->isOracle()) {
				// crmv@165801
				$adb->pquery(
					"UPDATE {$this->table_name}
					SET activity_start = TO_TIMESTAMP(".$adb->sql_concat(array('SUBSTR(date_start, 1, 10)', "' '", 'time_start')).", 'RRRR-MM-DD HH24:MI'),
					activity_end = TO_TIMESTAMP(".$adb->sql_concat(array('SUBSTR(due_date, 1, 10)', "' '", 'time_end')).", 'RRRR-MM-DD HH24:MI')
					WHERE {$this->table_index} = ?",
					array($this->id)
				);
				// crmv@165801e
			}
		}
		// crmv@149399e
		
		//Handling module specific save
		//Insert into seactivity rel
		if(isset($this->column_fields['parent_id']) && $this->column_fields['parent_id'] != '')
		{
			$this->insertIntoEntityTable($table_prefix."_seactivityrel", $module);
		}
		elseif($this->column_fields['parent_id']=='' && $this->mode=="edit")
		{
			$this->deleteRelation($table_prefix."_seactivityrel");
		}
		//Insert into cntactivity rel
		if(isset($this->column_fields['contact_id']) && $this->column_fields['contact_id'] != '')
		{
			$this->insertIntoEntityTable($table_prefix.'_cntactivityrel', $module);
		}
		elseif($this->column_fields['contact_id'] =='' && $this->mode=="edit")
		{
			$this->deleteRelation($table_prefix.'_cntactivityrel');
		}
		$recur_type='';
		if(($recur_type == "--None--" || $recur_type == '') && $this->mode == "edit")
		{
			$sql = 'delete  from '.$table_prefix.'_recurringevents where activityid=?';
			$adb->pquery($sql, array($this->id));
		}
		//Handling for recurring type
		//Insert into vte_recurring event table
		if(isset($this->column_fields['recurringtype']) && $this->column_fields['recurringtype']!='' && $this->column_fields['recurringtype']!='--None--')
		{
			$recur_type = trim($this->column_fields['recurringtype']);
			$recur_data = $this->recurringObject ?: getrecurringObjValue(); // crmv@185576
			if(is_object($recur_data))
	      			$this->insertIntoRecurringTable($recur_data);
		}

		//Insert into vte_activity_remainder table
		if($_REQUEST['set_reminder'] == 'Yes') // crmv@150601
		{
			$this->insertIntoReminderTable($table_prefix.'_activity_reminder',$module,"");
		}

		//crmv@32334
		if (isZMergeAgent()) {
			//do nothing
		} else {
		//crmv@32334e
			//crmv@19555 //crmv@26807
			//crmv@26961
			if((isset($_REQUEST['inviteesid']) && $_REQUEST['inviteesid'] == '--none--')) {
				if($this->mode == 'edit'){
					$sql = "delete from ".$table_prefix."_invitees where activityid=?";
					$adb->pquery($sql, array($this->id));
				}

			}
			if ((isset($_REQUEST['inviteesid_con']) && $_REQUEST['inviteesid_con'] == '--none--')) {
				if($this->mode == 'edit'){
					$sql = "delete from ".$table_prefix."_invitees_con where activityid=?";
					$adb->pquery($sql, array($this->id));
				}
			}
			if(isset($_REQUEST['inviteesid']) || isset($_REQUEST['inviteesid_con']))
			//crmv@26961e
			{
				$selected_users_string = $_REQUEST['inviteesid'];
				if($selected_users_string != '--none--' && $selected_users_string !='') {
					$invitees_array = explode(';',$selected_users_string);
				} else {
					$_REQUEST['inviteesid'] = '';
					$invitees_array = array();
				}
				if($_REQUEST['inviteesid_con'] != '--none--' && $_REQUEST['inviteesid_con'] != '') {
					$invitees_con_array = explode(';',$_REQUEST['inviteesid_con']);
				} else {
					$_REQUEST['inviteesid_con'] = '';
					$invitees_con_array = array();
				}
				$previousInvitees = $this->getInvitees($this->id); // crmv@110994
				$this->insertIntoInviteeTable($module,$invitees_array,array(),$invitees_con_array);
				if($this->mode != 'edit') {
					//code added to send mail to the vte_invitees
					if (isset($_REQUEST['subject'])) {
						$mail_contents = $this->getRequestData($this->id); //crmv@32334
					} else {
						$mail_contents = $this->getRequestData($this->id,$this); //crmv@32334
					}
					$this->sendInvitation($_REQUEST['inviteesid'],$this->mode,$this->column_fields['subject'],$mail_contents,$this->id,$_REQUEST['inviteesid_con']); //crmv@32334
				} else {
					// crmv@110994
					// edit mode, I might have added users
					$invitees = array('Users' => array(), 'Contacts' => array());
					foreach ($previousInvitees as $invitee) {
						$invitees[$invitee['type']][] = $invitee['id'];
					}
					$newUsers = array_diff($invitees_array, $invitees['Users']);
					$newContacts = array_diff($invitees_con_array, $invitees['Contacts']);
					if (count($newUsers) > 0 || count($newContacts) > 0) {
						//crmv@174732
						if (isset($_REQUEST['subject'])) {
							$mail_contents = $this->getRequestData($this->id); //crmv@32334
						} else {
							$mail_contents = $this->getRequestData($this->id,$this); //crmv@32334
						}
						//crmv@174732e
						$this->sendInvitation(implode(';', $newUsers),$this->mode,$this->column_fields['subject'],$mail_contents,$this->id,implode(';',$newContacts));
					}
					// crmv@110994e
				}
			} elseif($this->mode != 'edit') {
				//mando la mail anche se modifico l'attivita' dal wdCalendar (es. spostando l'orario)
				$tab_array = array($table_prefix.'_invitees',$table_prefix.'_invitees_con');
				foreach ($tab_array as $k => $tab_name) {
					$partecipations = array();
					$res = $adb->pquery("select inviteeid, partecipation from ".$tab_name." where activityid=?", array($this->id));
					if ($res && $adb->num_rows($res)>0) {
						while($row=$adb->fetchByAssoc($res)) {
							$partecipations[$row['inviteeid']] = $row['partecipation'];
						}
						$inviteesid = implode(';',array_keys($partecipations));
						$mail_contents = $this->getRequestData($this->id,$this); //crmv@32334
						if ($k == 0) {
							$this->sendInvitation($inviteesid,$this->mode,$this->column_fields['subject'],$mail_contents,$this->id,''); //crmv@32334
						} elseif ($k == 1) {
							$this->sendInvitation('',$this->mode,$this->column_fields['subject'],$mail_contents,$this->id,$inviteesid); //crmv@32334
						}
					}
				}
			}
			//crmv@19555e //crmv@26807e
		}

		// crmv@30385 - aggiorno duration hours e minuti
		if (!empty($this->column_fields['date_start']) && !empty($this->column_fields['due_date'])) {

			$start_hour = empty($this->column_fields['time_start']) ? '00:00:00' : $this->column_fields['time_start'];
			$end_hour = empty($this->column_fields['time_end']) ? '00:00:00' : $this->column_fields['time_end'];

			$ts_start = strtotime($this->column_fields['date_start']." ".$start_hour);
			$ts_end = strtotime($this->column_fields['due_date']." ".$end_hour);

			$this->column_fields['duration_hours'] = (int)(abs($ts_end-$ts_start)/(3600));
			$this->column_fields['duration_minutes'] = (int)((abs($ts_end-$ts_start)/60)%60);
			$adb->pquery('update '.$table_prefix.'_activity set duration_hours = ?, duration_minutes = ? where activityid = ?', array($this->column_fields['duration_hours'], $this->column_fields['duration_minutes'], $this->id));
		}
		// crmv@30385e

		//Inserting into sales man activity rel
		$this->insertIntoSmActivityRel($module);

		$this->insertIntoActivityReminderPopup($module);

	}
	/** Function to insert values in vte_act_reminder_popup table for the specified module
  	  * @param $cbmodule -- module:: Type varchar
 	 */
	function insertIntoActivityReminderPopup($cbmodule) {

		global $adb, $table_prefix;

		$cbrecord = $this->id;
		if(isset($cbmodule) && isset($cbrecord)) {
			$cbdate = $this->column_fields['date_start'];
			//crmv@fix date on massedit
			if (!isset($_REQUEST['massedit_module']) && !isset($_REQUEST['massedit_recordids']) && $_REQUEST['ajaxCalendar'] != 'quickAdd' && !isset($_REQUEST['change_status'])) {	//crmv@21059	//crmv@25040 //crmv@73912
				$cbdate = getValidDBInsertDateValue($cbdate); // crmv@103354
			}
			//crmv@fix date	end
			//crmv@65492
			if($this->repeating){
				$cbdate = getValidDBInsertDateValue($cbdate); // crmv@103354
			}
			//crmv@65492 e
			$cbtime = $this->column_fields['time_start'];
			
			// crmv@103354
			$date = $cbdate . ' ' . $cbtime;
			$adjustedDate = adjustTimezone($date, 0, null, true);
			$cbdate = substr($adjustedDate, 0, 10);
			if (strlen($adjustedDate) > 10) {
				$cbtime = substr($adjustedDate, strpos($adjustedDate, ' ') + 1, 5);
			}
			// crmv@103354e

			$reminder_query = "SELECT reminderid FROM ".$table_prefix."_act_reminder_popup WHERE semodule = ? and recordid = ?";
			$reminder_params = array($cbmodule, $cbrecord);
			$reminderidres = $adb->pquery($reminder_query, $reminder_params);

			$reminderid = null;
			if($adb->num_rows($reminderidres) > 0) {
				$reminderid = $adb->query_result($reminderidres, 0, "reminderid");
			}

			//crmv@25034
			$status = 0;
			if(($this->column_fields['activitytype'] == 'Task' && $this->column_fields['taskstatus'] == 'Completed') || ($this->column_fields['activitytype'] != 'Task' && ($this->column_fields['eventstatus'] == 'Held' || $this->column_fields['eventstatus'] == 'Not Held')) ){ //crmv@78625
				$status = 1;
			}

			if(isset($reminderid)) {
				$callback_query = "UPDATE ".$table_prefix."_act_reminder_popup set status = ?, date_start = ?, time_start = ? WHERE reminderid = ?";
				$callback_params = array($status, $cbdate, $cbtime, $reminderid);
			} else {
				$reminderid = $adb->getUniqueID($table_prefix."_act_reminder_popup");
				$callback_params = array($reminderid,$cbrecord, $cbmodule, $cbdate, $cbtime, $status);
				$callback_query = "INSERT INTO ".$table_prefix."_act_reminder_popup (reminderid,recordid, semodule, date_start, time_start,status) VALUES (".generateQuestionMarks($callback_params).")";
			}
			//crmv@25034e
			$adb->pquery($callback_query, $callback_params);
			
			VteSession::remove('next_reminder_time'); // crmv@103354
		}
	}

	/** Function to insert values in vte_activity_remainder table for the specified module,
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
 	 */
	function insertIntoReminderTable($table_name,$module,$recurid)
	{
	 	global $log;
		$log->info("in insertIntoReminderTable  ".$table_name."    module is  ".$module);
		if($_REQUEST['set_reminder'] == 'Yes')
		{
			$log->debug("set reminder is set");
			$rem_days = $_REQUEST['remdays'];
			$log->debug("rem_days is ".$rem_days);
			$rem_hrs = $_REQUEST['remhrs'];
			$log->debug("rem_hrs is ".$rem_hrs);
			$rem_min = $_REQUEST['remmin'];
			$log->debug("rem_minutes is ".$rem_min);
			$reminder_time = $rem_days * 24 * 60 + $rem_hrs * 60 + $rem_min;
			$log->debug("reminder_time is ".$reminder_time);
			if ($recurid == "")
			{
				if($_REQUEST['mode'] == 'edit')
				{
					$this->activity_reminder($this->id,$reminder_time,0,$recurid,'edit');
				}
				else
				{
					$this->activity_reminder($this->id,$reminder_time,0,$recurid,'');
				}
			}
			else
			{
				$this->activity_reminder($this->id,$reminder_time,0,$recurid,'');
			}
		}
		elseif($_REQUEST['set_reminder'] == 'No')
		{
			$this->activity_reminder($this->id,'0',0,$recurid,'delete');
		}
	}


	// Code included by Jaguar - starts
	/** Function to insert values in vte_recurringevents table for the specified tablename,module
  	  * @param $recurObj -- Recurring Object:: Type varchar
 	 */
	function insertIntoRecurringTable(& $recurObj)
	{
		global $log,$adb, $table_prefix;
		$log->info("in insertIntoRecurringTable  ");
		$st_date = $recurObj->startdate->get_formatted_date();
		$log->debug("st_date ".$st_date);
		$end_date = $recurObj->enddate->get_formatted_date();
		$log->debug("end_date is set ".$end_date);
		$type = $recurObj->recur_type;
		$log->debug("type is ".$type);
	        $flag="true";

		if($_REQUEST['mode'] == 'edit')
		{
			$activity_id=$this->id;

			$sql='select min(recurringdate) AS min_date,max(recurringdate) AS max_date, recurringtype, activityid from '.$table_prefix.'_recurringevents where activityid=? group by activityid, recurringtype';
			$result = $adb->pquery($sql, array($activity_id));
			$noofrows = $adb->num_rows($result);
			for($i=0; $i<$noofrows; $i++)
			{
				$recur_type_b4_edit = $adb->query_result($result,$i,"recurringtype");
				$date_start_b4edit = $adb->query_result($result,$i,"min_date");
				$end_date_b4edit = $adb->query_result($result,$i,"max_date");
			}
			if(($st_date == $date_start_b4edit) && ($end_date==$end_date_b4edit) && ($type == $recur_type_b4_edit))
			{
				if($_REQUEST['set_reminder'] == 'Yes')
				{
					$sql = 'delete from '.$table_prefix.'_activity_reminder where activity_id=?';
					$adb->pquery($sql, array($activity_id));
					$sql = 'delete  from '.$table_prefix.'_recurringevents where activityid=?';
					$adb->pquery($sql, array($activity_id));
					$flag="true";
				}
				elseif($_REQUEST['set_reminder'] == 'No')
				{
					$sql = 'delete  from '.$table_prefix.'_activity_reminder where activity_id=?';
					$adb->pquery($sql, array($activity_id));
					$flag="false";
				}
				else
					$flag="false";
			}
			else
			{
				$sql = 'delete from '.$table_prefix.'_activity_reminder where activity_id=?';
				$adb->pquery($sql, array($activity_id));
				$sql = 'delete  from '.$table_prefix.'_recurringevents where activityid=?';
				$adb->pquery($sql, array($activity_id));
			}
		}
		$date_array = $recurObj->recurringdates;
		if(isset($recurObj->recur_freq) && $recurObj->recur_freq != null)
			$recur_freq = $recurObj->recur_freq;
		else
			$recur_freq = 1;
		if($recurObj->recur_type == 'Daily' || $recurObj->recur_type == 'Yearly')
			$recurringinfo = $recurObj->recur_type;
		elseif($recurObj->recur_type == 'Weekly')
		{
			$recurringinfo = $recurObj->recur_type;
			if($recurObj->dayofweek_to_rpt != null)
				$recurringinfo = $recurringinfo.'::'.implode('::',$recurObj->dayofweek_to_rpt);
		}
		elseif($recurObj->recur_type == 'Monthly')
		{
			$recurringinfo =  $recurObj->recur_type.'::'.$recurObj->repeat_monthby;
			if($recurObj->repeat_monthby == 'date')
				$recurringinfo = $recurringinfo.'::'.$recurObj->rptmonth_datevalue;
			else
				$recurringinfo = $recurringinfo.'::'.$recurObj->rptmonth_daytype.'::'.$recurObj->dayofweek_to_rpt[0];
		}
		else
		{
			$recurringinfo = '';
		}
		if($flag=="true")
		{
			for($k=0; $k< count($date_array); $k++)
			{
				$tdate=$date_array[$k];
				if($tdate <= $end_date)
				{
					$max_recurid_qry = 'select max(recurringid) AS recurid from '.$table_prefix.'_recurringevents';
					$result = $adb->pquery($max_recurid_qry, array());
					$noofrows = $adb->num_rows($result);
					for($i=0; $i<$noofrows; $i++)
					{
						$recur_id = $adb->query_result($result,$i,"recurid");
					}
					$current_id =$recur_id+1;
					$recurring_insert = "insert into ".$table_prefix."_recurringevents values (?,?,?,?,?,?)";
					$rec_params = array($current_id, $this->id, $tdate, $type, $recur_freq, $recurringinfo);
					$adb->pquery($recurring_insert, $rec_params);
					//crmv@62592
					/*
					if($_REQUEST['set_reminder'] == 'Yes')
					{
						$this->insertIntoReminderTable($table_prefix."_activity_reminder",$module,$current_id,'');
					}
					*/
					//crmv@62592e
				}
			}
		}
	}


	/** Function to insert values in vte_invitees table for the specified module,tablename ,invitees_array
  	  * @param $table_name -- table name:: Type varchar
  	  * @param $module -- module:: Type varchar
	  * @param $invitees_array Array
 	 */
	function insertIntoInviteeTable($module,$invitees_array=array(),$partecipations=array(),$invitees_con_array=array(),$other_partecipations=array())	//crmv@26807	//crmv@zmerge
	{
		global $log,$adb,$table_prefix;
		$log->debug("Entering insertIntoInviteeTable method ...");

		//crmv@26807

		//--Users--i//
		//crmv@17001 : Inviti
		$tmp_partecipations = array();
		$res = $adb->pquery("select inviteeid, partecipation from ".$table_prefix."_invitees where activityid=?", array($this->id));
		if ($res && $adb->num_rows($res)>0)
			while($row=$adb->fetchByAssoc($res)) {
				$tmp_partecipations[$row['inviteeid']] = $row['partecipation'];
			}
		//crmv@17001e

		//crmv@zmerge
		if ($adb->table_exist('tbl_s_zmerge_events')) {
			if (!empty($partecipations)) {
				foreach($partecipations as $inviteeid => $partecipation) {
					$tmp_partecipations[$inviteeid] = $partecipation;
				}
				if (in_array($this->column_fields['assigned_user_id'],array_keys($tmp_partecipations))
				&& !in_array($this->column_fields['assigned_user_id'],$invitees_array)) {
					$invitees_array[] = $this->column_fields['assigned_user_id'];
				}
			}
		}
		//crmv@zmerge e

		if($this->mode == 'edit'){
			$sql = "delete from ".$table_prefix."_invitees where activityid=?";
			$adb->pquery($sql, array($this->id));
		}
		foreach($invitees_array as $inviteeid)
		{
			if($inviteeid != '')
			{
				//crmv@17001 : Inviti
				$query="insert into ".$table_prefix."_invitees(activityid,inviteeid,partecipation) values(?,?,?)";
				if ($tmp_partecipations[$inviteeid] != '') $partecipation = $tmp_partecipations[$inviteeid];
				else $partecipation = 0;
				$adb->pquery($query, array($this->id, $inviteeid, $partecipation));
				//crmv@17001e
			}
		}
		//--Users--e//

		//--Contacts--i//
		$tmp_partecipations = array();
		$res = $adb->pquery("select inviteeid, partecipation from ".$table_prefix."_invitees_con where activityid=?", array($this->id));
		if ($res && $adb->num_rows($res)>0)
		while($row=$adb->fetchByAssoc($res)) {
			$tmp_partecipations[$row['inviteeid']] = $row['partecipation'];
		}

		if($this->mode == 'edit'){
			$sql = "delete from ".$table_prefix."_invitees_con where activityid=?";
			$adb->pquery($sql, array($this->id));
		}
		//crmv@36511
		if (!is_array($invitees_con_array)){
			$invitees_con_array = Array();
		}
		//crmv@36511 e
		foreach($invitees_con_array as $inviteeid)
		{
			if($inviteeid != '')
			{
				$query="insert into ".$table_prefix."_invitees_con(activityid,inviteeid,partecipation) values(?,?,?)";
				if ($tmp_partecipations[$inviteeid] != '') $partecipation = $tmp_partecipations[$inviteeid];
				else $partecipation = 0;
				$adb->pquery($query, array($this->id, $inviteeid, $partecipation));
			}
		}
		//--Contacts--e//

		//crmv@26807e

		//crmv@zmerge
		if ($adb->table_exist('tbl_s_zmerge_events')) {
			$tmp_other_partecipations = array();
			$res = $adb->pquery("select * from ".$table_prefix."_other_invitees where activityid=?", array($this->id));
			if ($res && $adb->num_rows($res)>0) {
				while($row=$adb->fetchByAssoc($res)) {
					$tmp_other_partecipations[$row['email']] = array('partecipation'=>$row['partecipation'],'deleted'=>$row['deleted']);
				}
			}
			$adb->pquery('delete from '.$table_prefix.'_other_invitees where activityid=?', array($this->id));
			foreach($other_partecipations as $email => $partecipation) {
				$tmp_other_partecipations[$email] = array('partecipation'=>$partecipation,'deleted'=>0);
			}
			foreach($tmp_other_partecipations as $email => $info) {
				if (is_array($other_partecipations) && !in_array($email,array_keys($other_partecipations))) {
					$info['deleted'] = 1;
				}
				$query="insert into ".$table_prefix."_other_invitees(activityid,email,partecipation,deleted) values(?,?,?,?)";
				$adb->pquery($query, array($this->id, $email, $info['partecipation'], $info['deleted']));
			}
		}
		//crmv@zmerge e

		$log->debug("Exiting insertIntoInviteeTable method ...");

	}


	/** Function to insert values in vte_salesmanactivityrel table for the specified module
  	  * @param $module -- module:: Type varchar
 	 */

  	function insertIntoSmActivityRel($module)
  	{
    		global $adb,$table_prefix;
    		global $current_user;
    		if($this->mode == 'edit'){
      			$sql = "delete from ".$table_prefix."_salesmanactivityrel where activityid=?";
      			$adb->pquery($sql, array($this->id));
    		}
		$sql_qry = "insert into ".$table_prefix."_salesmanactivityrel (smid,activityid) values(?,?)";
    		$adb->pquery($sql_qry, array($this->column_fields['assigned_user_id'], $this->id));

		if(isset($_REQUEST['inviteesid']) && $_REQUEST['inviteesid']!='')
		{
			$selected_users_string =  $_REQUEST['inviteesid'];
			$invitees_array = explode(';',$selected_users_string);
			foreach($invitees_array as $inviteeid)
			{
				if($inviteeid != '')
				{
					$resultcheck = $adb->pquery("select * from ".$table_prefix."_salesmanactivityrel where activityid=? and smid=?",array($this->id,$inviteeid));
					if($adb->num_rows($resultcheck) != 1){
						$query="insert into ".$table_prefix."_salesmanactivityrel values(?,?)";
						$adb->pquery($query, array($inviteeid, $this->id));
					}
				}
			}
		}
  	}

  	static public function isUserInvited($activityid, $userid = null) {
  		global $adb, $table_prefix, $current_user;

  		if (is_null($userid)) $userid = $current_user->id;

  		$res = $adb->pquery("select partecipation from {$table_prefix}_invitees where activityid = ? and inviteeid = ?", array($activityid, $userid));
  		return ($res && $adb->num_rows($res) > 0);
  	}

  	static public function getUserInvitationAnswer($activityid, $userid = null) {
  		global $adb, $table_prefix, $current_user;

  		if (is_null($userid)) $userid = $current_user->id;

  		$res = $adb->pquery("select partecipation from {$table_prefix}_invitees where activityid = ? and inviteeid = ?", array($activityid, $userid));
  		if ($res && $adb->num_rows($res) > 0) {
  			return $adb->query_result_no_html($res, 0, 'partecipation');
  		} else {
  			return false;
  		}
  	}

	// crmv@81126
  	static public function setUserInvitationAnswer($activityid, $userid = null, $answer = 0) {
  		global $adb, $table_prefix, $current_user;

  		if (is_null($userid)) $userid = $current_user->id;
		$asnwer = intval($answer);
		
  		$res = $adb->pquery("select partecipation from {$table_prefix}_invitees where activityid = ? and inviteeid = ?", array($activityid, $userid));
  		if ($res && $adb->num_rows($res) > 0) {
			// update
  			$part = $adb->query_result_no_html($res, 0, 'partecipation');
  			if ($part != $answer) {
				$adb->pquery("UPDATE {$table_prefix}_invitees SET partecipation = ? WHERE activityid = ? AND inviteeid = ?", array($answer, $activityid, $userid));
  			}
  		} else {
  			// insert
  			$adb->pquery("INSERT INTO {$table_prefix}_invitees (activityid, inviteeid, partecipation) VALUES (?,?,?)", array($activityid, $userid, $answer));
  		}
  	}
  	// crmv@81126e

  	//crmv@392267
	/**
	 *
	 * @param String $tableName
	 * @return String
	 */
	public function getJoinClause($tableName) {
		global $table_prefix;
        if($tableName == $table_prefix."_activity_reminder")
            return 'LEFT JOIN';
		return parent::getJoinClause($tableName);
	}
	//crmv@392267e

//Function Call for Related List -- Start
	/**
	 * Function to get Activity related Contacts
	 * @param  integer   $id      - activityid
	 * returns related Contacts record in array format
	 */
	function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix; //crmv@203484 removed global singlepane

		$log->debug("Entering get_contacts(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$parenttab = getParentTab();

		$returnset = '&return_module='.$this_module.'&return_action=DetailView&activity_mode=Events&return_id='.$id;

		$search_string = '';
		$button = '';
		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions); // crmv@43864
		}

		//crmv@103400 crmv@128655 - added join _contactscf
		$query = 'select '.$table_prefix.'_users.user_name,'.$table_prefix.'_contactdetails.accountid,'.$table_prefix.'_contactdetails.contactid, '.$table_prefix.'_contactdetails.firstname,'.$table_prefix.'_contactdetails.lastname, '.$table_prefix.'_contactdetails.department, '.$table_prefix.'_contactdetails.title, '.$table_prefix.'_contactdetails.email, '.$table_prefix.'_contactdetails.phone, '.$table_prefix.'_crmentity.crmid, '.$table_prefix.'_crmentity.smownerid, '.$table_prefix.'_crmentity.modifiedtime
			from '.$table_prefix.'_contactdetails
			inner join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid='.$table_prefix.'_contactdetails.contactid
			left join '.$table_prefix.'_contactsubdetails on '.$table_prefix.'_contactsubdetails.contactsubscriptionid = '.$table_prefix.'_contactdetails.contactid
			inner join '.$table_prefix.'_cntactivityrel on '.$table_prefix.'_cntactivityrel.contactid='.$table_prefix.'_contactdetails.contactid
			inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_contactdetails.contactid
			left join '.$table_prefix.'_users on '.$table_prefix.'_users.id = '.$table_prefix.'_crmentity.smownerid
			left join '.$table_prefix.'_groups on '.$table_prefix.'_groups.groupid = '.$table_prefix.'_crmentity.smownerid
			where '.$table_prefix.'_cntactivityrel.activityid='.$id.' and '.$table_prefix.'_crmentity.deleted=0';
		// crmv@128655

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_contacts method ...");
		return $return_value;
	}

	/**
	 * Function to get Activity related Users
	 * @param  integer   $id      - activityid
	 * returns related Users record in array format
	 */

	function get_users($id) {
		global $log,$table_prefix;
                $log->debug("Entering get_contacts(".$id.") method ...");
		global $app_strings;

		$focus = CRMEntity::getInstance('Users');

		$button = '<input title="Change" accessKey="" tabindex="2" type="button" class="crmbutton small edit"
					value="'.getTranslatedString('LBL_SELECT_USER_BUTTON_LABEL').'" name="button" LANGUAGE=javascript
					onclick=\'return window.open("index.php?module=Users&return_module=Calendar&return_action={$return_modname}&activity_mode=Events&action=Popup&popuptype=detailview&form=EditView&form_submit=true&select=enable&return_id='.$id.'&recordid='.$id.'","test","width=640,height=525,resizable=0,scrollbars=0")\';>';

		$returnset = '&return_module=Calendar&return_action=CallRelatedList&return_id='.$id;

		$query = 'SELECT '.$table_prefix.'_users.id, '.$table_prefix.'_users.first_name,'.$table_prefix.'_users.last_name, '.$table_prefix.'_users.user_name, '.$table_prefix.'_users.email1, '.$table_prefix.'_users.email2, '.$table_prefix.'_users.status, '.$table_prefix.'_users.is_admin, '.$table_prefix.'_user2role.roleid, '.$table_prefix.'_users.yahoo_id, '.$table_prefix.'_users.phone_home, '.$table_prefix.'_users.phone_work, '.$table_prefix.'_users.phone_mobile, '.$table_prefix.'_users.phone_other, '.$table_prefix.'_users.phone_fax,'.$table_prefix.'_activity.date_start,'.$table_prefix.'_activity.due_date,'.$table_prefix.'_activity.time_start,'.$table_prefix.'_activity.duration_hours,'.$table_prefix.'_activity.duration_minutes from '.$table_prefix.'_users inner join '.$table_prefix.'_salesmanactivityrel on '.$table_prefix.'_salesmanactivityrel.smid='.$table_prefix.'_users.id  inner join '.$table_prefix.'_activity on '.$table_prefix.'_activity.activityid='.$table_prefix.'_salesmanactivityrel.activityid inner join '.
		$table_prefix.'_user2role on '.$table_prefix.'_user2role.userid='.$table_prefix.'_users.id where '.$table_prefix.'_activity.activityid='.$id . 'and '.$table_prefix.'_users.status=\'Active\'';//crmv@203476

		$return_data = GetRelatedList('Calendar','Users',$focus,$query,$button,$returnset);

		if($return_data == null) $return_data = Array();
		$return_data['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_users method ...");
		return $return_data;
	}


//calendarsync
    /**
     * Function to get meeting count
     * @param  string   $user_name        - User Name
     * return  integer  $row["count(*)"]  - count
     */
    function getCount_Meeting($user_name)
	{
		global $log,$table_prefix;
	        $log->debug("Entering getCount_Meeting(".$user_name.") method ...");
      $query = "select count(*) from ".$table_prefix."_activity inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid inner join ".$table_prefix."_salesmanactivityrel on ".$table_prefix."_salesmanactivityrel.activityid=".$table_prefix."_activity.activityid inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_salesmanactivityrel.smid where user_name=? and ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_activity.activitytype='Meeting'";
      $result = $this->db->pquery($query, array($user_name),true,"Error retrieving contacts count");
      $rows_found =  $this->db->getRowCount($result);
      $row = $this->db->fetchByAssoc($result, 0);
	$log->debug("Exiting getCount_Meeting method ...");
      return $row["count(*)"];
    }

//calendarsync
	/**
	 * Function to get task count
	 * @param  string   $user_name        - User Name
	 * return  integer  $row["count(*)"]  - count
	 */
    function getCount($user_name)
    {
	    global $log,$table_prefix;
            $log->debug("Entering getCount(".$user_name.") method ...");
        $query = "select count(*) from ".$table_prefix."_activity inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid inner join ".$table_prefix."_salesmanactivityrel on ".$table_prefix."_salesmanactivityrel.activityid=".$table_prefix."_activity.activityid inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_salesmanactivityrel.smid where user_name=? and ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_activity.activitytype='Task'";
        $result = $this->db->pquery($query,array($user_name), true,"Error retrieving contacts count");
        $rows_found =  $this->db->getRowCount($result);
        $row = $this->db->fetchByAssoc($result, 0);

	$log->debug("Exiting getCount method ...");
        return $row["count(*)"];
    }

    /**
     * Function to process the activity list query
     * @param  string   $query     - query string
     * return  array    $response  - activity lists
     */
    function process_list_query1($query)
    {
	    global $log;
            $log->debug("Entering process_list_query1(".$query.") method ...");
        $result =& $this->db->query($query,true,"Error retrieving $this->object_name list: ");
        $list = Array();
        $rows_found =  $this->db->getRowCount($result);
        if($rows_found != 0)
        {
            $task = Array();
              for($index = 0 , $row = $this->db->fetchByAssoc($result, $index); $row && $index <$rows_found;$index++, $row = $this->db->fetchByAssoc($result, $index))

             {
                foreach($this->range_fields as $columnName)
                {
                    if (isset($row[$columnName])) {

                        $task[$columnName] = $row[$columnName];
                    }
                    else
                    {
                            $task[$columnName] = "";
                    }
	            }

                $task[contact_name] = return_name($row, 'cfn', 'cln');

                    $list[] = $task;
                }
         }

        $response = Array();
        $response['list'] = $list;
        $response['row_count'] = $rows_found;
        $response['next_offset'] = $next_offset;
        $response['previous_offset'] = $previous_offset;


	$log->debug("Exiting process_list_query1 method ...");
        return $response;
    }

    	/**
	 * Function to get reminder for activity
	 * @param  integer   $activity_id     - activity id
	 * @param  string    $reminder_time   - reminder time
	 * @param  integer   $reminder_sent   - 0 or 1
	 * @param  integer   $recurid         - recuring eventid
	 * @param  string    $remindermode    - string like 'edit'
	 */
	function activity_reminder($activity_id,$reminder_time,$reminder_sent=0,$recurid,$remindermode='')
	{
		global $log,$table_prefix;
		$log->debug("Entering ".$table_prefix."_activity_reminder(".$activity_id.",".$reminder_time.",".$reminder_sent.",".$recurid.",".$remindermode.") method ...");
		//Check for vte_activityid already present in the reminder_table
		$query_exist = "SELECT activity_id FROM ".$this->reminder_table." WHERE activity_id = ?";
		$result_exist = $this->db->pquery($query_exist, array($activity_id));
		if($recurid == ''){	//crmv@19165
			$recurid = 0;
		}//crmv@19165 - e
		if($remindermode == 'edit')
		{
			if($this->db->num_rows($result_exist) == 1)
			{
				$query = "UPDATE ".$this->reminder_table." SET";
				$query .=" reminder_sent = ?, reminder_time = ? WHERE activity_id =?";
				$params = array($reminder_sent, $reminder_time, $activity_id);
			}
			else
			{
				$query = "INSERT INTO ".$this->reminder_table." VALUES (?,?,?,?)";
				$params = array($activity_id, $reminder_time, 0, $recurid);
			}
		}
		elseif(($remindermode == 'delete') && ($this->db->num_rows($result_exist) == 1))
		{
			$query = "DELETE FROM ".$this->reminder_table." WHERE activity_id = ?";
			$params = array($activity_id);
		}
		else
		{
			$query = "INSERT INTO ".$this->reminder_table." VALUES (?,?,?,?)";
			$params = array($activity_id, $reminder_time, 0, $recurid);
		}
      	$this->db->pquery($query,$params,true,"Error in processing ".$table_prefix."_table $this->reminder_table");
		$log->debug("Exiting ".$table_prefix."_activity_reminder method ...");
	}

//Used for VteCRM Outlook Add-In
/**
 * Function to get tasks to display in outlookplugin
 * @param   string    $username     -  User name
 * return   string    $query        -  sql query
 */
function get_tasksforol($username)
{
	global $log,$adb,$table_prefix;
	$log->debug("Entering get_tasksforol(".$username.") method ...");
	global $current_user;
	require_once("modules/Users/Users.php");
	$seed_user=CRMEntity::getInstance('Users');
	$user_id=$seed_user->retrieve_user_id($username);
	$current_user=$seed_user;
	$current_user->retrieve_entity_info($user_id, 'Users');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
  {
    $sql1 = "select tablename,columnname from ".$table_prefix."_field where tabid=9 and tablename <> '".$table_prefix."_recurringevents' and tablename <> '".$table_prefix."_activity_reminder'";
	$params1 = array();
  }else
  {
    $profileList = getCurrentUserProfileList();
    $sql1 = "select tablename,columnname from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid=9 and tablename <> '".$table_prefix."_recurringevents' and tablename <> '".$table_prefix."_activity_reminder' and ".$table_prefix."_field.displaytype in (1,2,4,3) and ".$table_prefix."_def_org_field.visible=0";
	$params1 = array();
    $sql1.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
        if (count($profileList) > 0) {
	  	 	$sql1.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
	  	 	array_push($params1, $profileList);
	}
    $sql1.=" AND ".$table_prefix."_profile2field.visible = 0) ";
  }
  $result1 = $adb->pquery($sql1,$params1);
  for($i=0;$i < $adb->num_rows($result1);$i++)
  {
      $permitted_lists[] = $adb->query_result($result1,$i,'tablename');
      $permitted_lists[] = $adb->query_result($result1,$i,'columnname');
      /*if($adb->query_result($result1,$i,'columnname') == "parentid")
      {
        $permitted_lists[] = 'vte_account';
        $permitted_lists[] = 'accountname';
      }*/
  }
	$permitted_lists = array_chunk($permitted_lists,2);
	$column_table_lists = array();
	for($i=0;$i < count($permitted_lists);$i++)
	{
	   $column_table_lists[] = implode(".",$permitted_lists[$i]);
  }

	$query = "select ".$table_prefix."_activity.activityid as taskid, ".implode(',',$column_table_lists)." from ".$table_prefix."_activity
			 inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid
			 inner join ".$table_prefix."_activitycf on ".$table_prefix."_activity.activityid = ".$table_prefix."_activitycf.activityid
			 inner join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
			 left join ".$table_prefix."_cntactivityrel on ".$table_prefix."_cntactivityrel.activityid=".$table_prefix."_activity.activityid
			 left join ".$table_prefix."_contactdetails on ".$table_prefix."_contactdetails.contactid=".$table_prefix."_cntactivityrel.contactid
			 left join ".$table_prefix."_seactivityrel on ".$table_prefix."_seactivityrel.activityid = ".$table_prefix."_activity.activityid
			 where ".$table_prefix."_users.user_name='".$username."' and ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_activity.activitytype='Task' and '.$table_prefix.'_users.status='Active'"; //crmv@203476
	$log->debug("Exiting get_tasksforol method ...");
	return $query;
}

/**
 * Function to get calendar query for outlookplugin
 * @param   string    $username     -  User name                                                                            * return   string    $query        -  sql query                                                                            */
function get_calendarsforol($user_name)
{
	global $log,$adb,$table_prefix;
	$log->debug("Entering get_calendarsforol(".$user_name.") method ...");
	global $current_user;
	require_once("modules/Users/Users.php");
	$seed_user=CRMEntity::getInstance('Users');
	$user_id=$seed_user->retrieve_user_id($user_name);
	$current_user=$seed_user;
	$current_user->retrieve_entity_info($user_id, 'Users');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
  {
    $sql1 = "select tablename,columnname from ".$table_prefix."_field where tabid=9 and tablename <> '".$table_prefix."_recurringevents' and tablename <> '".$table_prefix."_activity_reminder'";
  	$params1 = array();
  }else
  {
    $profileList = getCurrentUserProfileList();
    $sql1 = "select tablename,columnname from ".$table_prefix."_field inner join ".$table_prefix."_profile2field on ".$table_prefix."_profile2field.fieldid=".$table_prefix."_field.fieldid inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid=9 and tablename <> '".$table_prefix."_recurringevents' and tablename <> '".$table_prefix."_activity_reminder' and ".$table_prefix."_field.displaytype in (1,2,4,3) and ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible=0";
	$params1 = array();
	if (count($profileList) > 0) {
		$sql1 .= " and ".$table_prefix."_profile2field.profileid in (". generateQuestionMarks($profileList) .")";
		array_push($params1,$profileList);
	}
  }
  $result1 = $adb->pquery($sql1, $params1);
  for($i=0;$i < $adb->num_rows($result1);$i++)
  {
      $permitted_lists[] = $adb->query_result($result1,$i,'tablename');
      $permitted_lists[] = $adb->query_result($result1,$i,'columnname');
      if($adb->query_result($result1,$i,'columnname') == "date_start")
      {
        $permitted_lists[] = $table_prefix.'_activity';
        $permitted_lists[] = 'time_start';
      }
      if($adb->query_result($result1,$i,'columnname') == "due_date")
      {
	$permitted_lists[] = $table_prefix.'_activity';
        $permitted_lists[] = 'time_end';
      }
  }
	$permitted_lists = array_chunk($permitted_lists,2);
	$column_table_lists = array();
	for($i=0;$i < count($permitted_lists);$i++)
	{
	   $column_table_lists[] = implode(".",$permitted_lists[$i]);
  }

	  $query = "select ".$table_prefix."_activity.activityid as clndrid, ".implode(',',$column_table_lists)." from ".$table_prefix."_activity
	  			inner join ".$table_prefix."_activitycf on ".$table_prefix."_activity.activityid = ".$table_prefix."_activitycf.activityid
				inner join ".$table_prefix."_salesmanactivityrel on ".$table_prefix."_salesmanactivityrel.activityid=".$table_prefix."_activity.activityid
				inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_salesmanactivityrel.smid
				left join ".$table_prefix."_cntactivityrel on ".$table_prefix."_cntactivityrel.activityid=".$table_prefix."_activity.activityid
				left join ".$table_prefix."_contactdetails on ".$table_prefix."_contactdetails.contactid=".$table_prefix."_cntactivityrel.contactid
				left join ".$table_prefix."_seactivityrel on ".$table_prefix."_seactivityrel.activityid = ".$table_prefix."_activity.activityid
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_activity.activityid
				where ".$table_prefix."_users.user_name='".$user_name."' and ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_activity.activitytype='Meeting'";
	$log->debug("Exiting get_calendarsforol method ...");
	return $query;
}
	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log,$table_prefix;

		$sql = 'DELETE FROM '.$table_prefix.'_activity_reminder WHERE activity_id=?';
		$this->db->pquery($sql, array($id));

		$sql = 'DELETE FROM '.$table_prefix.'_recurringevents WHERE activityid=?';
		$this->db->pquery($sql, array($id));

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log,$table_prefix;
		if(empty($return_module) || empty($return_id)) return;
		// crmv@OPER4876 crmv@189362
		require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
		CalendarTracking::unTrackActivity($id);
		// crmv@OPER4876e crmv@189362e
		if($return_module == 'Contacts') {
			$sql = 'DELETE FROM '.$table_prefix.'_cntactivityrel WHERE contactid = ? AND activityid = ?';
			$this->db->pquery($sql, array($return_id, $id));
		} else {
			$sql='DELETE FROM '.$table_prefix.'_seactivityrel WHERE activityid=?';
			$this->db->pquery($sql, array($id));

			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	/**
	 * this function sets the status flag of activity to true or false depending on the status passed to it
	 * @param string $status - the status of the activity flag to set
	 * @return:: true if successful; false otherwise
	 */
	function setActivityReminder($status){
		global $adb,$table_prefix;
		if($status == "on"){
			$flag = 0;
		}elseif($status == "off"){
			$flag = 1;
		}else{
			return false;
		}
		$sql = "update ".$table_prefix."_act_reminder_popup set status=1 where recordid=?";
		$adb->pquery($sql, array($this->id));
		return true;
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Contacts" => array($table_prefix."_cntactivityrel"=>array("activityid","contactid"),$table_prefix."_activity"=>"activityid"),
			"Leads" => array($table_prefix."_seactivityrel"=>array("activityid","crmid"),$table_prefix."_activity"=>"activityid"),
			"Accounts" => array($table_prefix."_seactivityrel"=>array("activityid","crmid"),$table_prefix."_activity"=>"activityid"),
			"Potentials" => array($table_prefix."_seactivityrel"=>array("activityid","crmid"),$table_prefix."_activity"=>"activityid"),
		);
		return $rel_tables[$secmodule];
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){	//crmv@131239
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_activity","activityid");
		//crmv@17001
		$query .=" left join ".$table_prefix."_activitycf on ".$table_prefix."_activitycf.activityid = ".$table_prefix."_crmentityCalendar.crmid
				left join ".$table_prefix."_seactivityrel on ".$table_prefix."_seactivityrel.activityid = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_activity_reminder on ".$table_prefix."_activity_reminder.activity_id = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_recurringevents on ".$table_prefix."_recurringevents.activityid = ".$table_prefix."_activity.activityid
				left join ".$table_prefix."_crmentity ".$table_prefix."_crmentityRelCalendar on ".$table_prefix."_crmentityRelCalendar.crmid = ".$table_prefix."_seactivityrel.crmid and ".$table_prefix."_crmentityRelCalendar.deleted=0
				left join ".$table_prefix."_account ".$table_prefix."_accountRelCalendar on ".$table_prefix."_accountRelCalendar.accountid=".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_leaddetails ".$table_prefix."_leaddetailsRelCalendar on ".$table_prefix."_leaddetailsRelCalendar.leadid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_potential ".$table_prefix."_potentialRelCalendar on ".$table_prefix."_potentialRelCalendar.potentialid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_quotes ".$table_prefix."_quotesRelCalendar on ".$table_prefix."_quotesRelCalendar.quoteid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_purchaseorder ".substr($table_prefix.'_purchaseorderRelCalendar',0,29)." on ".substr($table_prefix.'_purchaseorderRelCalendar',0,29).".purchaseorderid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_invoice ".$table_prefix."_invoiceRelCalendar on ".$table_prefix."_invoiceRelCalendar.invoiceid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_salesorder ".$table_prefix."_salesorderRelCalendar on ".$table_prefix."_salesorderRelCalendar.salesorderid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_troubletickets ".substr($table_prefix.'_troubleticketsRelCalendar',0,29)." on ".substr($table_prefix.'_troubleticketsRelCalendar',0,29).".ticketid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_campaign ".$table_prefix."_campaignRelCalendar on ".$table_prefix."_campaignRelCalendar.campaignid = ".$table_prefix."_crmentityRelCalendar.crmid
				left join ".$table_prefix."_groups ".$table_prefix."_groupsCalendar on ".$table_prefix."_groupsCalendar.groupid = ".$table_prefix."_crmentityCalendar.smownerid
				left join ".$table_prefix."_users ".$table_prefix."_usersCalendar on ".$table_prefix."_usersCalendar.id = ".$table_prefix."_crmentityCalendar.smownerid";
		//crmv@17001e
		return $query;
	}
	//crmv@38798e

	protected function setupTemporaryTable_tmp($tableName, $tabId, $user, $parentRole, $userGroups) { // crmv@81760
		global $table_prefix;
		$module = null;
		if (!empty($tabId)) {
			$module = getTabname($tabId);
		}
		$query = $this->getNonAdminAccessQuery($module, $user, $parentRole, $userGroups);
		$db = PearDatabase::getInstance();
		if ($db->isMysql()){
			$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key, shared ".
			"int(1) default 0) ignore ".$query;
			$result = $db->pquery($query, array());
			if(is_object($result)) {
				$query = "replace into $tableName select 1 as shared,userid as id from {$table_prefix}_sharedcalendar where ".
				"sharedid = $user->id"; //crmv@31342
				$result = $db->pquery($query, array());
			}
		}
		else {
			if (!$db->table_exist($tableName,true)){
				Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY,shared I(1) default 0",true,true);
			}
			$tableName = $db->datadict->changeTableName($tableName);
			$query = "insert into $tableName (id) ".
			$query.
			"where not exists (select * from $tableName where $tableName.id = un_table.id)";
			$result = $db->pquery($query, array(),true);
			if(is_object($result)) {
				$query = "insert into $tableName select userid as id,1 as shared from ".$table_prefix."_sharedcalendar where ".
				"sharedid = $user->id and not exists (select * from $tableName where $tableName.id = ".$table_prefix."_sharedcalendar.userid)";
				$result = $db->pquery($query, array());
			}
		}
		//crmv@17001
		$res = $db->query("SELECT id FROM ".$table_prefix."_users WHERE id NOT IN (SELECT id FROM $tableName) AND ".$table_prefix."_users.status = 'Active'"); //crmv@203476
		if ($res && $db->num_rows($res)>0) {
			while($row=$db->fetchByAssoc($res)) {
				$db->pquery("insert into $tableName(shared,id) values (?,?)",array(2,$row['id']));
			}
		}
		//crmv@17001e
		//crmv@25593
		$query = "select {$table_prefix}_crmentity.smownerid from {$table_prefix}_activity
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$table_prefix}_activity.activityid
				inner join {$table_prefix}_groups on {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid
				inner join (SELECT activityid FROM {$table_prefix}_invitees WHERE inviteeid = ? AND activityid > 0) t on t.activityid = {$table_prefix}_activity.activityid
				WHERE deleted = 0
				GROUP BY {$table_prefix}_crmentity.smownerid";
		$res = $db->pquery($query,array($user->id));
		if ($res && $db->num_rows($res)>0) {
			while($row=$db->fetchByAssoc($res)) {
				// crmv@28028 insert ignore for oracle
				if ($db->isOracle()) {
					$par = array(3,$row['smownerid'],$row['smownerid']);
					$db->pquery("insert into $tableName (shared,id) select ?,? from dual where not exists (select id from $tableName where $tableName.id = ?)", $par);
				} elseif ($db->isMysql()) {
					$db->pquery("insert ignore into $tableName(shared,id) values (?,?)",array(3,$row['smownerid']));
				} else {
					$db->pquery("insert into $tableName(shared,id) values (?,?)",array(3,$row['smownerid']));
				}
				// crmv@28028e
			}
		}
		//crmv@25593e
		return $result;
	}

	//crmv@26265e
	//crmv@32334
	function getActivityMailInfo($return_id,$status,$activity_type)
	{
		$mail_data = Array();
		global $adb,$table_prefix;
		$qry = "select * from ".$table_prefix."_activity where activityid=?";
		$ary_res = $adb->pquery($qry, array($return_id));
		$subject = $adb->query_result($ary_res,0,"subject");
		$priority = $adb->query_result($ary_res,0,"priority");
		$st_date = $adb->query_result($ary_res,0,"date_start");
		$st_time = $adb->query_result($ary_res,0,"time_start");
		$end_date = $adb->query_result($ary_res,0,"due_date");
		$end_time = $adb->query_result($ary_res,0,"time_end");
		$location = $adb->query_result($ary_res,0,"location");
		$description = $adb->query_result($ary_res,0,"description"); // crmv@150773

		$owner_qry = "select smownerid from ".$table_prefix."_crmentity where crmid=?";
		$res = $adb->pquery($owner_qry, array($return_id));
		$owner_id = $adb->query_result($res,0,"smownerid");

		$usr_res = $adb->pquery("select count(*) as count from ".$table_prefix."_users where id=?",array($owner_id));
		if($adb->query_result($usr_res, 0, 'count')>0) {
			$assignType = "U";
			$usr_id = $owner_id;
		}
		else {
			$assignType = "T";
			$group_qry = "select groupname from ".$table_prefix."_groups where groupid=?";
			$grp_res = $adb->pquery($group_qry, array($owner_id));
			$grp_name = $adb->query_result($grp_res,0,"groupname");
		}

		// crmv@150773 - removed description query

		$rel_qry = "select case ".$table_prefix."_crmentity.setype when 'Leads' then ".$table_prefix."_leaddetails.lastname when 'Accounts' then ".$table_prefix."_account.accountname when 'Potentials' then ".$table_prefix."_potential.potentialname when 'Quotes' then ".$table_prefix."_quotes.subject when 'PurchaseOrder' then ".$table_prefix."_purchaseorder.subject when 'SalesOrder' then ".$table_prefix."_salesorder.subject when 'Invoice' then ".$table_prefix."_invoice.subject when 'Campaigns' then ".$table_prefix."_campaign.campaignname when 'HelpDesk' then ".$table_prefix."_troubletickets.title  end as relname
					from ".$table_prefix."_seactivityrel
					inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_leaddetails on ".$table_prefix."_leaddetails.leadid = ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_account on ".$table_prefix."_account.accountid=".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_potential on ".$table_prefix."_potential.potentialid=".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_quotes on ".$table_prefix."_quotes.quoteid= ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_purchaseorder on ".$table_prefix."_purchaseorder.purchaseorderid = ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_salesorder on ".$table_prefix."_salesorder.salesorderid = ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_invoice on ".$table_prefix."_invoice.invoiceid = ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_campaign on ".$table_prefix."_campaign.campaignid = ".$table_prefix."_seactivityrel.crmid
					left join ".$table_prefix."_troubletickets on ".$table_prefix."_troubletickets.ticketid = ".$table_prefix."_seactivityrel.crmid
					where ".$table_prefix."_seactivityrel.activityid=?";
		$rel_res = $adb->pquery($rel_qry, array($return_id));
		$rel_name = $adb->query_result($rel_res,0,"relname");

		$cont_qry = "select * from ".$table_prefix."_cntactivityrel where activityid=?";
		$cont_res = $adb->pquery($cont_qry, array($return_id));
		$cont_id = $adb->query_result($cont_res,0,"contactid");
		$cont_name = '';
		if($cont_id != '')
		{
			$cont_name = getContactName($cont_id);
		}
		$mail_data['record'] = $return_id;	//crmv@18871
		$mail_data['mode'] = "edit";
		$mail_data['activity_mode'] = $activity_type;
		$mail_data['user_id'] = $usr_id;
		$mail_data['subject'] = $subject;
		$mail_data['status'] = $status;
		$mail_data['taskpriority'] = $priority;
		$mail_data['relatedto'] = $rel_name;
		$mail_data['contact_name'] = $cont_name;
		$mail_data['description'] = $description;
		$mail_data['assingn_type'] = $assignType;
		$mail_data['group_name'] = $grp_name;
		$value = getaddEventPopupTime($st_time,$end_time,'24');
		$start_hour = $value['starthour'].':'.$value['startmin'].''.$value['startfmt'];
		if($activity_type != 'Task' )
			$end_hour = $value['endhour'] .':'.$value['endmin'].''.$value['endfmt'];
		$mail_data['st_date_time']=getDisplayDate($st_date)." ".$start_hour;
		$mail_data['end_date_time']=getDisplayDate($end_date)." ".$end_hour;
		$mail_data['location']=$location;
		return $mail_data;
	}
	function sendInvitation($inviteesid,$mode,$subject,$desc,$record='',$inviteesid_con='')	//crmv@19555 //crmv@26807
	{
		global $current_user;
		require_once("modules/Emails/mail.php");
		$invites = getTranslatedString('INVITATION','Calendar');
		//crmv@29617
		if ($mode == 'edit') {
			$type = 'Calendar invitation edit';
		} else {
			$type = 'Calendar invitation';
		}
		//crmv@29617e
		//crmv@26807
		if ($inviteesid !='') {
			$invitees_array = explode(';',$inviteesid);
			$email_subject = $invites.': '.$subject;
			foreach($invitees_array as $inviteeid)
			{
				if($inviteeid != '')
				{
					$this->switchUserLanguage($inviteeid);	// crmv@150065
					$description=$this->getActivityDetails($desc,$inviteeid,"invite",$mode,$record);	//crmv@19555
					//crmv@29617
					$focus = ModNotifications::getInstance(); // crmv@164122
					$focus->saveFastNotification(
						array(
							'assigned_user_id' => $inviteeid,
							'related_to' => $record,
							'mod_not_type' => $type,
							'subject' => $email_subject,
							'description' => $description,
							'from_email' => $current_user->email1,
							'from_email_name' => getUserFullName($current_user->id),
						)
					);
					//crmv@29617e
				}
			}
			$this->switchUserLanguage($current_user->id);	// crmv@150065
		}
		// crmv@68357
		if ($inviteesid_con !='') {
			$invitees_array = explode(';',$inviteesid_con);
			$email_subject = $invites.': '.$subject;
			$attachment = '';
			$ics = $this->generateInvitationIcs($record, $this->column_fields); // crmv@69568
			if ($ics) {
				// prepare the linked ics as invitation
				$attachment = array(
					array(
						'sourcetype' => 'string',
						'content' => $ics->serialize(),
						'contenttype' => 'text/calendar',
						'altbody' => true,
						'charset' => 'UTF-8',
						'encoding' => '7bit',
						'method' => 'REQUEST',
					),
					array(
						'sourcetype' => 'string',
						'filename' => 'invite.ics',
						'content' => $ics->serialize(),
						'contenttype' => 'application/ics',
					),
				);
			}
			foreach($invitees_array as $inviteeid)
			{
				if($inviteeid != '')
				{
					$description=$this->getActivityDetails($desc,$inviteeid,"invite_con",$mode,$record);
					$to_email = getContactsEmailId($inviteeid);
					send_mail('Calendar',$to_email,$current_user->user_name,'',$email_subject,$description, '', '', $attachment);
				}
			}
		}
		//crmv@26807e crmv@68357e
	}
	function sendInvitationAnswer($partecipation,$activityid,$userid,$from='')	//crmv@26807
	{
		global $current_user;
		require_once("modules/Emails/mail.php");

		$focus_event = CRMEntity::getInstance('Calendar');
		$focus_event->id = $activityid;
		$focus_event->retrieve_entity_info($focus_event->id,'Events');
		$invites = getTranslatedString('INVITATION','Calendar');
		if ($partecipation == 2) {
			$answer = getTranslatedString('LBL_YES','Calendar');
			$type = 'Calendar invitation answer yes';	//crmv@29617
		}
		elseif ($partecipation == 1) {
			$answer = getTranslatedString('LBL_NO','Calendar');
			$type = 'Calendar invitation answer no';	//crmv@29617
		}
		$subject = $invites.': '.$focus_event->column_fields['subject'];
		$description = $this->getInvitationDescription($focus_event->column_fields,$focus_event->column_fields['assigned_user_id'],$activityid,$answer,$userid,$from);	//crmv@26807
		//crmv@29617
		$obj = ModNotifications::getInstance(); // crmv@164122
		if ($from == 'invite_con') {
			$contactid = $_REQUEST['userid'];
			$obj->saveFastNotification(
				array(
					'assigned_user_id' => $focus_event->column_fields['assigned_user_id'],
					'related_to' => $focus_event->id,
					'mod_not_type' => $type.' contact',
					'subject' => $subject,
					'description' => $description,
					'from_email' => $current_user->email1,
					'from_email_name' => getContactName($contactid),
				)
			);
		} else {
			$obj->saveFastNotification(
				array(
					'assigned_user_id' => $focus_event->column_fields['assigned_user_id'],
					'related_to' => $focus_event->id,
					'mod_not_type' => $type,
					'subject' => $subject,
					'description' => $description,
					'from_email' => $current_user->email1,
					'from_email_name' => getUserFullName($current_user->id),
				)
			);
		}
		//crmv@29617e
	}
	
	// crmv@68357 crmv@69568
	function generateInvitationIcs($activityid, $fields = null) {
		global $adb, $table_prefix; 

		$ics = null;
		$config = array("unique_id" => "VTECRM");
		$vcalendar = new VTEvcalendar($config);
		
		if (empty($fields)) {
			// generate from query
			// crmv@150773
			$query = 
				"SELECT 
					a.*, 
					c.smownerid, c.createdtime, c.modifiedtime,
					ar.reminder_time
				FROM {$table_prefix}_activity a
				INNER JOIN {$table_prefix}_crmentity c ON a.activityid = c.crmid
				LEFT JOIN {$table_prefix}_activity_reminder ar ON ar.activity_id = a.activityid AND ar.recurringid = 0
				WHERE a.activityid = ?";
			// crmv@150773e
			$ics = $vcalendar->generateFromSql($query, array($activityid));
		} else {
			if (empty($fields['activityid'])) $fields['activityid'] = $activityid; // crmv@81140
			$ics = $vcalendar->generateFromFields($fields);
		}
		
		$ics->add_property('METHOD', 'REQUEST');
		
		return $ics;
	}
	// crmv@69568e

	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true) {
		global $adb, $table_prefix;
		
		// generate the ical_uuid if missing
		if (($this->mode == '' || $this->mode == 'create')) {
			// crmv@187823
			if (empty($this->column_fields['ical_uuid'])) {
				$this->column_fields['ical_uuid'] = rfc2445_guid();
			}
			if (empty($this->column_fields['organizer'])) {
				$this->column_fields['organizer'] = array(
					'type' => 'Users',
					'value' => $this->column_fields['assigned_user_id'] ?: $current_user->id,
				);
			}
			// crmv@187823e
		} elseif ($this->id > 0) {
			// This is the calendar people! On save, not all columns are retrieved, so the uuid is empty and I have to reload it, damn!
			$res = $adb->pquery("SELECT ical_uuid FROM {$this->table_name} WHERE {$this->table_index} = ?", array($this->id));
			if ($res && $adb->num_rows($res) > 0) {
				$this->column_fields['ical_uuid'] = $adb->query_result_no_html($res, 0, 'ical_uuid');
			}
			// crmv@187823
			// avoid clearing the organizer user on edit
			if (empty($this->column_fields['organizer'])) {
				$this->column_fields['organizer'] = getSingleFieldValue($table_prefix.'_activity_organizer', 'email', 'activityid', $this->id);
			}
			// crmv@187823e
		}
		
		return parent::save($module_name,$longdesc,$offline_update,$triggerEvent);
	}
	
	// crmv@81126
	function getCrmidFromUuid($uuid, $recurrIdx = 0) {
		global $adb, $table_prefix;
		
		$crmid = null;
		if (!empty($uuid)) {
			$res = $adb->pquery("
				SELECT {$table_prefix}_crmentity.crmid 
				FROM {$table_prefix}_activity
				INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_activity.activityid = {$table_prefix}_crmentity.crmid
				WHERE deleted = 0 AND ical_uuid = ? AND recurr_idx = ?
			", array($uuid, intval($recurrIdx)));
			if ($res && $adb->num_rows($res) > 0) {
				$crmid = intval($adb->query_result_no_html($res, 0, 'crmid'));
			}
		}
		
		return $crmid;
	}
	// crmv@81126e
	// crmv@68357e

	//crmv@19555
	function getRequestData($return_id,$focus='')
	{
		global $adb,$table_prefix;
		$cont_qry = "select * from ".$table_prefix."_cntactivityrel where activityid=?";
		$cont_res = $adb->pquery($cont_qry, array($return_id));
		$noofrows = $adb->num_rows($cont_res);
		$cont_id = array();
		if($noofrows > 0) {
			for($i=0; $i<$noofrows; $i++) {
				$cont_id[] = $adb->query_result($cont_res,$i,"contactid");
			}
		}
		$cont_name = '';
		foreach($cont_id as $key=>$id) {
			if($id != '') {
				$cont_name .= getContactName($id).', ';
			}
		}
		$cont_name  = trim($cont_name,', ');
		$mail_data = Array();
		if ($focus == '') {
			$mail_data['user_id'] = $_REQUEST['assigned_user_id'];
			$mail_data['subject'] = $_REQUEST['subject'];
			$mail_data['status'] = (($_REQUEST['activity_mode']=='Task')?($_REQUEST['taskstatus']):($_REQUEST['eventstatus']));
			$mail_data['activity_mode'] = $_REQUEST['activity_mode'];
			$mail_data['taskpriority'] = $_REQUEST['taskpriority'];
			$mail_data['relatedto'] = $_REQUEST['parent_name'];
			$mail_data['contact_name'] = $cont_name;
			$mail_data['description'] = $_REQUEST['description'];
			$mail_data['assingn_type'] = $_REQUEST['assigntype'];
			$mail_data['group_name'] = getGroupName($_REQUEST['assigned_group_id']);
			$mail_data['mode'] = $_REQUEST['mode'];
			$value = getaddEventPopupTime($_REQUEST['time_start'],$_REQUEST['time_end'],'24');
			$start_hour = $value['starthour'].':'.$value['startmin'].''.$value['startfmt'];
			if($_REQUEST['activity_mode']!='Task')
				$end_hour = $value['endhour'] .':'.$value['endmin'].''.$value['endfmt'];
			$mail_data['st_date_time'] = getDisplayDate($_REQUEST['date_start'])." ".$start_hour;
			$mail_data['end_date_time']=getDisplayDate($_REQUEST['due_date'])." ".$end_hour;
			$mail_data['location']=vtlib_purify($_REQUEST['location']);
		}
		else {
			$info = getEntityName(getSalesEntityType($focus->column_fields['parent_id']),array($focus->column_fields['parent_id']));
			$parent_name = $info[$focus->column_fields['parent_id']];

			$mail_data['user_id'] = $focus->column_fields['assigned_user_id'];
			$mail_data['subject'] = $focus->column_fields['subject'];
			$mail_data['status'] = (($focus->column_fields['activitytype']=='Task')?($focus->column_fields['taskstatus']):($focus->column_fields['eventstatus']));
			$mail_data['activity_mode'] = (($focus->column_fields['activitytype']=='Task')?('Task'):('Events'));
			$mail_data['taskpriority'] = $focus->column_fields['taskpriority'];
			$mail_data['relatedto'] = $parent_name;
			$mail_data['contact_name'] = $cont_name;
			$mail_data['description'] = $focus->column_fields['description'];
			$mail_data['assingn_type'] = $focus->column_fields['assigntype'];
	//		$mail_data['group_name'] = getGroupName($_REQUEST['assigned_group_id']);
			$mail_data['mode'] = $focus->mode;
			$value = getaddEventPopupTime($focus->column_fields['time_start'],$focus->column_fields['time_end'],'24');
			$start_hour = $value['starthour'].':'.$value['startmin'].''.$value['startfmt'];
			if($focus->column_fields['activitytype']!='Task')
				$end_hour = $value['endhour'] .':'.$value['endmin'].''.$value['endfmt'];
			$mail_data['st_date_time'] = getDisplayDate($focus->column_fields['date_start'])." ".$start_hour;
			$mail_data['end_date_time']=getDisplayDate($focus->column_fields['due_date'])." ".$end_hour;
			$mail_data['location']=vtlib_purify($focus->column_fields['location']);
		}
		return $mail_data;
	}
	//crmv@19555e
	/**
	 * Function to get the '.$table_prefix.'_activity details for mail body
	 * @param   string   $description       - activity description
	 * @param   string   $from              - to differenciate from notification to invitation.
	 * return   string   $list              - HTML in string format
	 */
	function getActivityDetails($description,$user_id,$from='',$save_mode='',$record='')	//crmv@17001	//crmv@19555
	{
	    global $log,$current_user;
	    global $adb;
	    $log->debug("Entering getActivityDetails(".$description.") method ...");

		$updated = getTranslatedString('LBL_UPDATED','Calendar');
		$created = getTranslatedString('LBL_CREATED','Calendar');
		//crmv@17001
		if ($save_mode != '')
	    	$reply = (($save_mode == 'edit')?"$updated":"$created");
	    else
	    	$reply = (($description['mode'] == 'edit')?"$updated":"$created");
	    //crmv@17001e
		if($description['activity_mode'] == "Events")
		{
			$end_date_lable = getTranslatedString('End date and time','Calendar');
		}
		else
		{
			$end_date_lable = getTranslatedString('Due Date','Calendar');
		}

		//crmv@26807
		if ($from == 'invite_con') {
			$name = getContactName($user_id);
		}
		else {
			$name = getUserName($user_id);
		}
		//crmv@26807e

		if($from == "invite" || $from == "invite_con")
			$msg = getTranslatedString('LBL_ACTIVITY_INVITATION','Calendar');
		else
			$msg = getTranslatedString('LBL_ACTIVITY_NOTIFICATION','Calendar');

	    $current_username = getUserName($current_user->id);
	    $status = getTranslatedString($description['status'],'Calendar');

	    // crmv@25610
	    $tzinfo = '';
	    if (!empty($current_user->column_fields['user_timezone'])) {
	    	$tzinfo = " ({$current_user->column_fields['user_timezone']})";
	    }
	    // crmv@25610e
	    $list = $name.',';
		$list .= '<br><br>'.$msg.' '.$reply.'.<br> '.getTranslatedString('LBL_DETAILS_STRING','Calendar').':<br>';
	    $list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_SUBJECT",'Calendar').' '.$description['subject'];
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Start date and time",'Calendar').' : '.$description['st_date_time'].$tzinfo; // crmv@25610
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$end_date_lable.' : '.$description['end_date_time'].$tzinfo; // crmv@25610
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_STATUS",'Calendar').': '.$status;
	    $list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Priority",'Calendar').': '.getTranslatedString($description['taskpriority']);
	    $list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Related To",'Calendar').': '.getTranslatedString($description['relatedto']);
		if($description['activity_mode'] != 'Events')
		{
			$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_CONTACT",'Calendar').' '.$description['contact_name'];
		}
		else
			$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Location",'Calendar').' : '.$description['location'];

		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_APP_DESCRIPTION",'Calendar').': '.$description['description'];

		//crmv@17001 : Inviti	//crmv@26807
		if($from == "invite" || $from == "invite_con") {
			global $site_URL,$application_unique_key;
			//crmv@19555	//crmv@21995
			if ($record == '' && $_REQUEST['record'] != '') $record = $_REQUEST['record'];

			$confirm_url = $site_URL."/hub/cinv.php?from=$from&app_key=$application_unique_key&record=$record&userid=".$user_id; // crmv@192078
			$link_yes = "<a href='".$confirm_url."&partecipation=2'><b>".getTranslatedString('LBL_YES','Calendar')."</b></a>";
			$link_no = "<a href='".$confirm_url."&partecipation=1'><b>".getTranslatedString('LBL_NO','Calendar')."</b></a>";
			$list .= '<br><br>'.getTranslatedString('LBL_MAIL_INVITATION_CONFIRM','Calendar').' '.$link_yes.' - '.$link_no.'<br>';

			$link = "<a href='".$site_URL."/index.php?module=Calendar&action=DetailView&record=$record'>".getTranslatedString('LBL_HERE','Calendar')."</a>";
			//crmv@19555e	//crmv@21995e
			$list .= getTranslatedString('LBL_MAIL_INVITATION_1','Calendar').' '.$link.' '.getTranslatedString('LBL_MAIL_INVITATION_2','Calendar');
		}
		//crmv@17001e	//crmv@26807e

	    $list .= '<br><br>'.getTranslatedString("LBL_REGARDS_STRING",'Calendar').' ,';
	    $list .= '<br>'.$current_username.'.';

	    $log->debug("Exiting getActivityDetails method ...");
	    return $list;
	}
	function getInvitationDescription($description,$user_id,$record='',$answer='',$userid='',$from='')	//crmv@26807
	{
		global $adb,$log,$current_user,$site_URL,$table_prefix;
		$name = getUserName($user_id);
		$current_username = getUserName($current_user->id);
		$status = getTranslatedString($description['eventstatus'],'Calendar');
		//crmv@26807
		if ($from == 'invite_con') {
			$user_name = getContactName($userid);
		}
		else {
			$user_name = $current_user->user_name;
		}
		$desc = $user_name.' '.getTranslatedString('LBL_ANSWER','Calendar').' "'.$answer.'" '.getTranslatedString('LBL_TO_INVITATION','Calendar');
		//crmv@26807e

		// crmv@25610
		$tzinfo = '';
		if (!empty($current_user->column_fields['user_timezone'])) {
			$tzinfo = " ({$current_user->column_fields['user_timezone']})";
		}
		// crmv@25610e

		$list = $name.',';
		$list .= '<br> '.$desc.'.<br>';
		$list .= '<br> '.getTranslatedString('LBL_DETAILS_STRING','Calendar').':';
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_SUBJECT",'Calendar').' '.$description['subject'];
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Start date and time",'Calendar').' : '.$description['date_start'].' '.$description['time_start'].$tzinfo; // crmv@25610
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString('End date and time','Calendar').' : '.$description['due_date'].' '.$description['time_end'].$tzinfo; // crmv@25610
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_STATUS",'Calendar').': '.$status;
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Priority",'Calendar').': '.getTranslatedString($description['taskpriority']);
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Location",'Calendar').': '.getTranslatedString($description['location']);
		$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_APP_DESCRIPTION",'Calendar').': '.$description['description'];

		//crmv@26807
		$invitees = '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_CAL_INVITATION",'Calendar').':  ';
		$query = '	SELECT inviteeid, partecipation, last_name, first_name
						FROM '.$table_prefix.'_invitees
						LEFT JOIN '.$table_prefix.'_users ON '.$table_prefix.'_users.id = '.$table_prefix.'_invitees.inviteeid
						WHERE activityid = ?
						';
		$res = $adb->pquery($query, array($record));
		if ($res && $adb->num_rows($res)>0) {
			while($row = $adb->fetchByAssoc($res)) {
				if ($row['partecipation'] == 2) {
					$answer = getTranslatedString('LBL_YES','Calendar');
				}
				elseif ($row['partecipation'] == 1) {
					$answer = getTranslatedString('LBL_NO','Calendar');
				}
				else {
					$answer = '?';
				}
				$invitees .= $row['last_name'].' '.$row['first_name'].' ('.$answer.'), ';
			}
		}
		$query = 'SELECT inviteeid,partecipation FROM '.$table_prefix.'_invitees_con WHERE activityid = ? ';
		$res = $adb->pquery($query, array($record));
		if ($res && $adb->num_rows($res)>0) {
			while($row = $adb->fetchByAssoc($res)) {
				if ($row['partecipation'] == 2) {
					$answer = getTranslatedString('LBL_YES','Calendar');
				}
				elseif ($row['partecipation'] == 1) {
					$answer = getTranslatedString('LBL_NO','Calendar');
				}
				else {
					$answer = '?';
				}
				$invitees .= getContactName($row['inviteeid']).' ('.$answer.'), ';
			}
		}
		$list .= substr($invitees,0,strlen($invitees)-2);
		//crmv@26807e

		$link = "<a href='".$site_URL."/index.php?module=Calendar&action=DetailView&record=$record'>".getTranslatedString('LBL_HERE','Calendar')."</a>";
		$list .= '<br><br>'.getTranslatedString('LBL_MAIL_INVITATION_1','Calendar').' '.$link.' '.getTranslatedString('LBL_MAIL_INVITATION_3','Calendar');
		$list .= '<br><br>'.getTranslatedString("LBL_REGARDS_STRING",'Calendar').' ,';
		$list .= '<br>'.$current_username.'.';
		return $list;
	}
	//crmv@26030m e
	//crmv@32334 e
	//crmv@zmerge
	function trash($module, $id) {
		global $adb;
		// crmv@OPER4876 crmv@189362
		require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');
		CalendarTracking::unTrackActivity($id);
		// crmv@OPER4876e crmv@189362e
		CRMEntity::trash($module, $id);
		if ($adb->table_exist('tbl_s_zmerge_events')) {
			require_once('modules/Calendar/ZMergeUtils.php');
			ZMerge::setZMergeEvents($id);
		}
	}
	//crmv@zmerge e
	//crmv@20629 crmv@23461 crmv@24020 crmv@36555
	function getShownUserId($userid,$all=false){
		global $adb,$showfullusername,$table_prefix,$current_user;
		$shownid = Array();
		if ($all) {
			$query = "SELECT * FROM tbl_s_showncalendar WHERE userid = ? AND shownid IN ('all','mine','others')";
			$result = $adb->pquery($query, array($userid));
			$rows = $adb->num_rows($result);
			for($j=0;$j<$rows;$j++)
			{
				$id = $adb->query_result($result,$j,'shownid');
				$selected = $adb->query_result($result,$j,'selected');
				$shownname = $id;
				$shownid[$id] = array('name'=>$shownname,'selected'=>$selected);
			}
		}
		//crmv@152712
		$query = "SELECT
					  tbl_s_showncalendar.*,
					  ".$table_prefix."_users.last_name, ".$table_prefix."_users.first_name
					FROM tbl_s_showncalendar
					  INNER JOIN ".$table_prefix."_users
					    ON tbl_s_showncalendar.shownid = ".$table_prefix."_users.id
					WHERE userid = ? and ".$table_prefix."_users.status = 'Active' and shownid not in ('all','mine','others')
					ORDER BY ".$table_prefix."_users.user_name";
		$result = $adb->pquery($query, array($userid));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				$id = $row['shownid'];
				$selected = $row['selected'];
				$shownname = getUserName($id,false,array(
					'withoutname'=>$current_user->formatUserName($id,$row,false,Users::USERNAME_FORMAT_INVERTED),
					'withname'=>$current_user->formatUserName($id,$row,true,Users::USERNAME_FORMAT_INVERTED),
				));
				$shownid[$id] = array('name'=>$shownname,'selected'=>$selected);
			}
		}
		//crmv@152712e
		return $shownid;
	}
	function getSharedUserId($id, $onlyOccupation = false) { // crmv@187823
		global $adb,$showfullusername,$table_prefix;
		$sharedid = Array();
		// crmv@187823
		$query = "SELECT sc.* 
			FROM {$table_prefix}_sharedcalendar sc 
			LEFT JOIN {$table_prefix}_users u ON sc.sharedid = u.id 
			WHERE userid = ? AND only_occ = ? AND u.status='Active'";//crmv@203476 crmv@204903
		$params = array($id, $onlyOccupation ? 1 : 0);
		$result = $adb->pquery($query, $params);
		// crmv@187823e
		$rows = $adb->num_rows($result);
		for($j=0;$j<$rows;$j++)
		{
			$id = $adb->query_result($result,$j,'sharedid');
			$sharedname = getUserName($id,$showfullusername);
			$sharedid[$id]=$sharedname;
		}
		return $sharedid;
	}
	function getSharingUserName($id){
		global $adb,$table_prefix;
		$user_details=Array();
		require('user_privileges/sharing_privileges_'.$id.'.php');
		// crmv@39110
		$userid = $id;
		require('user_privileges/requireUserPrivileges.php');
		// crmv@39110e
		//crmv@23460
		$query = "SELECT id, user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users WHERE status=?";
		$params = array('Active');
		//crmv@23460e
		$query .= " order by user_name ASC";
		$result = $adb->pquery($query, $params, true, "Error filling in user array: ");
		while($row = $adb->fetchByAssoc($result))
		{
			$temp_result[$row['id']] = $row['user_name'];
		}
		$user_details = &$temp_result;
		unset($user_details[$id]);
		return $user_details;
	}
	function getShownUserList($id){
		global $current_user,$adb,$table_prefix;
		// crmv@39110
		$userid = $id;
		require('user_privileges/requireUserPrivileges.php');
		// crmv@39110e
		require('user_privileges/sharing_privileges_'.$id.'.php');

		//crmv@70053
		if($is_admin==false && $defaultOrgSharingPermission[9] != 3)
		{
			$query = "SELECT id, user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users WHERE status=?";
			$params = array('Active');
		}
		elseif($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[9] == 3 or $defaultOrgSharingPermission[9] == 0))
		//crmv@70053e
		{
			$query = "select id as id,user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users where id=? and status='Active'";
			$query.=" union select ".$table_prefix."_user2role.userid as id,".$table_prefix."_users.user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_user2role inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_user2role.userid inner join ".$table_prefix."_role on ".$table_prefix."_role.roleid=".$table_prefix."_user2role.roleid where ".$table_prefix."_role.parentrole like ? and status='Active' union select shareduserid as id,".$table_prefix."_users.user_name as user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_tmp_write_u_per inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_tmp_write_u_per.shareduserid where status='Active' and ".$table_prefix."_tmp_write_u_per.userid=? and ".$table_prefix."_tmp_write_u_per.tabid=?";
			$query.=" UNION SELECT userid AS id, ".$table_prefix."_users.user_name AS user_name, first_name AS first_name, last_name AS last_name FROM ".$table_prefix."_sharedcalendar INNER JOIN ".$table_prefix."_users ON ".$table_prefix."_users.id = ".$table_prefix."_sharedcalendar.userid WHERE STATUS = 'Active' AND ".$table_prefix."_sharedcalendar.sharedid = ?";
			$params = array($id,$current_user_parent_role_seq."::%",$id,9,$id);
		}
		else
		{
			$query = "SELECT id, user_name,first_name AS first_name,last_name AS last_name from ".$table_prefix."_users WHERE status=?";
			$params = array('Active');
		}
		$query .= " order by user_name ASC";
		$result = $adb->pquery($query, $params);
		while($row = $adb->fetchByAssoc($result))
		{
			$user_details[$row['id']] = getUserName($row['id']);
		}
		unset($user_details[$id]);
		return $user_details;
	}
	//crmv@20209e	crmv@23461e	crmv@24020e	crmv@36555e
	
	// crmv@187823
	/**
	 * Return wether the $viewer user can see the content of event owned by $owner.
	 * @param int $owner The owner of the record being viewed
	 * @param int $viewer The user viewing the record
	 * @return bool
	 */
	public function isOnlySharingOccupation($owner, $viewer) {
		global $adb, $table_prefix;
		static $occupCache = array();
		$key = $viewer.'_'.$owner;
		if (!array_key_exists($key, $occupCache)) {
			$res = $adb->pquery(
				"SELECT COUNT(*) as cnt FROM {$table_prefix}_sharedcalendar WHERE userid = ? AND sharedid = ? AND only_occ = 1", 
				array($owner, $viewer)
			);
			$occupCache[$key] = ($adb->query_result_no_html($res, 0, 'cnt') == 1);
		}
		return $occupCache[$key];
	}
	
	/**
	 * Return which fields are shown when others are masked (to allow occupation visibility)
	 * @return array
	 */
	public function getNonMaskedFields() {
		$fields = array('assigned_user_id','date_start','time_start','time_end','due_date','activitytype','visibility','duration_hours','duration_minutes');
		return $fields;
	}
	
	/**
	 * Return true if the field should not be visible to the $forUser argument
	 * @param int $record The record id of the event
	 * @param string $fieldname The fieldname to check
	 * @param array $recordInfo Additional fields of the event, if empty they will be retrieved from database
	 * @param int $forUser The user asking to see the field, if empty use $current_user
	 * @return bool
	 */
	public function isFieldMasked($record, $fieldname, $recordInfo = array(), $forUser = null) {
		global $current_user;
		
		if (empty($forUser)) {
			$forUser = $current_user->id;
			$isAdmin = is_admin($current_user);
		} else {
			$userid = $forUser;
			require('user_privileges/requireUserPrivileges.php');
			$isAdmin = $is_admin;
		}
		
		if (empty($recordInfo)) {
			$res = $adb->pquery(
				"SELECT c.smownerid as assigned_user_id, a.visibility
				FROM {$this->tablename} a
				INNER JOIN {$table_prefix}_crmentity c ON c.crmid = a.activityid AND c.deleted = 0
				WHERE a.activityid = ?",
				array($record)
			);
			$recordInfo = $adb->fetchByAssoc($res, -1, false);
		}

		$ownerId = $recordInfo['assigned_user_id'];
		$visibility = $recordInfo['visibility'];
		
		// now implement quick checks and fast exit first
		
		// admin and owner see everything!
		if ($isAdmin || $ownerId == $forUser) return false;

		// public events are world visible!
		if ($visibility == 'Public') return false;
		
		// some fields are always shown
		$allowedFields = $this->getNonMaskedFields();
		if (in_array($fieldname,$allowedFields)) return false;
		
		// then some slower checks
		if (
			($visibility == 'Private' || $this->isOnlySharingOccupation($ownerId, $forUser)) && 
			isCalendarInvited($forUser,$record,true) == 'no'
			) 
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Return true if the record has masked fields for the user $forUser
	 * @param int $record The record id of the event
	 * @param array $recordInfo Additional fields of the event, if empty they will be retrieved from database
	 * @param int $forUser The user asking to see the record, if empty use $current_user
	 * @return bool
	 */
	public function hasMaskedFields($record, $recordInfo = array(), $forUser = null) {
		return $this->isFieldMasked($record, 'subject', $recordInfo, $forUser);
	}
	
	/**
	 * Check if the passed user matches the organizer field of the $record event
	 * @param int $record the crmid of the event
	 * @param int $userid The id of the user
	 * @return bool
	 */
	public function isOrganizer($record, $userid = null) {
		global $current_user;
		require_once('modules/SDK/src/49/OrganizerField.php');
		$ofield = $ofield = OrganizerField::getInstance('Calendar', 'organizer');
		return $ofield->compareUser($userid ?: $current_user->id, $record);
	}
	// crmv@187823e

	// crmv@48267
	static function getInvitees($activityid) {
		global $adb, $table_prefix;

		$invitees = array();

		$q = "select
				i.inviteeid as id, i.partecipation, u.user_name, u.first_name as firstname, u.last_name as lastname, u.email1, u.email2, 'Users' as type
			from {$table_prefix}_invitees i
				inner join {$table_prefix}_activity a on i.activityid = a.activityid
				inner join {$table_prefix}_crmentity c on a.activityid = c.crmid
				inner join {$table_prefix}_users u on u.id = i.inviteeid
			where c.deleted = 0 and u.deleted = 0 and i.activityid = ?";
		$res = $adb->pquery($q,	array($activityid));

		if ($res) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$invitees[] = $row;
			}
		}

		$q = "select
				i.inviteeid as id, i.partecipation, cd.firstname, cd.lastname, cd.email as email1, cd.otheremail as email2, 'Contacts' as type
			from {$table_prefix}_invitees_con i
				inner join {$table_prefix}_activity a on i.activityid = a.activityid
				inner join {$table_prefix}_crmentity c on a.activityid = c.crmid
				inner join {$table_prefix}_contactdetails cd on cd.contactid = i.inviteeid
				inner join {$table_prefix}_crmentity cc on cd.contactid = cc.crmid
			where c.deleted = 0 and cc.deleted = 0 and i.activityid = ?";
		$res = $adb->pquery($q,	array($activityid));

		if ($res) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$invitees[] = $row;
			}
		}
		return $invitees;
	}
	//crmv@48267e
	
	// crmv@150065
	public function switchUserLanguage($userid){
		global $app_strings, $mod_strings;
		global $currentModule, $current_language;
		
		$user_language = getUserLanguage($userid);
		if ($user_language != $current_language) {
			$current_language = $user_language;
			$app_strings = return_application_language($current_language);
			$mod_strings = return_module_language($current_language, $currentModule);
		}		
	}
	//crmv@150065e

	// crmv@181170
	public function getCalendarShareContent($record, $mode) {
		$record = intval($record);

		$shareduser_ids = $this->getSharedUserId($record);
		$shareduserocc_ids = $this->getSharedUserId($record, true);
		$shownduser_ids = $this->getShownUserId($record);

		$smartyCal = new VteSmarty();

		$smartyCal->assign('MODE', $mode);
		$smartyCal->assign('SHAREDUSERS', $shareduser_ids);
		$smartyCal->assign('SHAREDUSERSOCC', $shareduserocc_ids);
		$smartyCal->assign('SHOWNUSERS', $shownduser_ids);

		// these only in edit
		if ($mode != 'detail' && $mode != 'create') {
			//crmv@36555
			$shareduser_list = $this->getSharingUserName($record);
			$shownuser_list = $this->getShownUserList($record);
			//crmv@36555e
			$smartyCal->assign('SHAREDUSERS_LIST', $shareduser_list);
			$smartyCal->assign('SHOWNUSERS_LIST', $shownuser_list);
		}

		return $smartyCal->fetch('modules/Calendar/ShareSettings.tpl');
	}
	// crmv@181170e

	// crmv@194723 crmv@202029
	function getUserResourceList($userid) {
		global $adb, $table_prefix, $current_user;
		
		$resourceList = array();
		
		$query = "
			SELECT cr.*, u.user_name, u.last_name, u.first_name, u.cal_color
			FROM tbl_s_calendar_resources cr
			INNER JOIN tbl_s_showncalendar ON tbl_s_showncalendar.userid = cr.userid AND tbl_s_showncalendar.shownid = cr.shownid
			INNER JOIN {$table_prefix}_users u ON u.id = cr.shownid
			WHERE cr.userid = ? AND u.status = 'Active'
			UNION
			SELECT cr.*, u.user_name, u.last_name, u.first_name, u.cal_color
			FROM tbl_s_calendar_resources cr
			INNER JOIN {$table_prefix}_users u ON u.id = cr.shownid
			WHERE cr.userid = ? AND cr.shownid = cr.userid AND u.status = 'Active'
			ORDER BY user_name";
		
		$result = $adb->pquery($query, array($userid, $userid));
		
		if (!!$result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$id = $row['shownid'];
				$shownname = getUserName($id, false, array(
					'withoutname' => $current_user->formatUserName($id, $row, false, Users::USERNAME_FORMAT_INVERTED), 
					'withname' => $current_user->formatUserName($id, $row, true, Users::USERNAME_FORMAT_INVERTED)
				));
				$row['name'] = $shownname;
				$resourceList[$id] = $row;
			}
		}

		return $resourceList;
	}
	// crmv@194723e crmv@202029e

}