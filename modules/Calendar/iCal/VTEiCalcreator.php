<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@38878
class VTEvcalendar extends vcalendar {

	var $vevent_mapping = array(
    	'class'			=>	array('component'=>'visibility','function'=>'handleCLASS','type'=>'string'),
    	'description'	=>	array('component'=>'description','function'=>'handleDESCRIPTION','type'=>'string'),
    	'dtstart'		=>	array('component'=>array('date_start','time_start'),'type'=>'datetime'),
    	'dtend'			=>	array('component'=>array('due_date','time_end'),'type'=>'datetime'),
    	'dtstamp'		=>	array('component'=>array('date_start','time_start'),'type'=>'datetime'),
    	'location'		=>	array('component'=>'location','function'=>'setBlankIfNull','type'=>'string'),
    	'status'		=>	array('component'=>'eventstatus','function'=>'handleSTATUS','type'=>'string'),
    	'summary'		=>	array('component'=>'subject','type'=>'string'),
    	'priority'		=>	array('component'=>'priority','type'=>'string'),
    	'resources'		=>	array('component'=>array('location','eventstatus'),'type'=>'string'),
    	'uid'			=>	array('component'=>'ical_uuid','type'=>'string'), // crmv@68357
		'sequence'		=>	array('component'=>'recurr_idx','type'=>'integer'), // crmv@81126
    	'organizer'		=>	array('component'=>'activityid','type'=>'organizer'), // crmv@68357
    	'attendee'		=>	array('component'=>'activityid','type'=>'invitee'), // crmv@68357
    	'rrule'			=> 	array('component'=>'activityid','type'=>'recurrence'), // crmv@185576
    );
	
	var $vevent_field_mapping = array(
    	'priority'=>'taskpriority',
    );
	
	var $vtodo_mapping = array(
    	'description'	=>	array('component'=>'description','function'=>'handleDESCRIPTION','type'=>'string'),
    	'dtstamp'		=>	array('component'=>array('date_start','time_start'),'type'=>'datetime'),
    	'dtstart'		=>	array('component'=>array('date_start','time_start'),'type'=>'datetime'),
    	'due'			=>	array('component'=>array('due_date'),'type'=>'datetime'),
    	'status'		=>	array('component'=>'status','function'=>'handleSTATUS','type'=>'string'),
    	'summary'		=>	array('component'=>'subject','type'=>'string'),
    	'priority'		=>	array('component'=>'priority','type'=>'string'),
    	'resources'		=>	array('component'=>array('status'),'type'=>'string'),
    	'uid'			=>	array('component'=>'ical_uuid','type'=>'string'), // crmv@68357
    );
	
	var $vtodo_field_mapping = array(
    	'status'=>'taskstatus',
    	'priority'=>'taskpriority'
    );
    
    // crmv@68357
    protected $todo_fields = null;
	protected $event_fields = null; 
	protected $event_keys = null;
	protected $todo_keys = null;
	
	protected $keyvals_to_replace = array(
		'events'=> array('taskpriority'=>'priority', 'assigned_user_id' => 'smownerid'),
		'todo' => array('taskpriority'=>'priority','taskstatus'=>'status')
	);
	protected $keys_ical_replace = array(
		'events' => array(), // es: 'date_start' => 'dtstart'
		'todo' => array(),
	);
	
	public function generateFromSql($sql, $params = array()) {
		global $adb;
		
		require('vteversion.php'); // crmv@181168
		
		// execute query
		$calendar_results = $adb->pquery($sql, $params);

		// create the calendar instance
		$myical = new iCalendar;
		$myical->add_property('PRODID', "-//VTECRM//NONSGML $enterprise_mode $enterprise_current_version//EN");

		// add the timezone
		$tz = $this->generateTimezone();
		if ($tz && $tz->is_valid()) $myical->add_component($tz);

		// add the events/todos
		while ($this_event = $adb->FetchByAssoc($calendar_results, -1, false)) {
			$ev = $this->generateFromDbRow($this_event);
			if ($ev === false) continue;
			$myical->add_component($ev);
		}

		return $myical;
	}

	// crmv@69568
	public function generateFromFields($fields) {
		
		// add some values, do make it like a retrieved row
		if (!isset($fields['smownerid']) && isset($fields['assigned_user_id'])) {
			$fields['smownerid'] = $fields['assigned_user_id'];
		}
		if (!isset($fields['activityid']) && isset($fields['record_id'])) {
			$fields['activityid'] = $fields['record_id'];
		}
		if (!isset($fields['priority']) && isset($fields['taskpriority'])) {
			$fields['priority'] = $fields['taskpriority'];
		}
		
		require('vteversion.php'); // crmv@181168
		
		// create the calendar instance
		$myical = new iCalendar;
		$myical->add_property('PRODID', "-//VTECRM//NONSGML $enterprise_mode $enterprise_current_version//EN");

		// add the timezone
		$tz = $this->generateTimezone();
		if ($tz && $tz->is_valid()) $myical->add_component($tz);

		// add the event/todo
		$ev = $this->generateFromDbRow($fields);
		if ($ev !== false) {
			$myical->add_component($ev);
		}

		return $myical;
	}
	// crmv@69568e
	
	public function generateFromDbRow($datarow) {
		if ($datarow['activitytype'] != 'Task') {
			$ev = $this->generateEventFromDbRow($datarow);
		} else {		
			$ev = $this->generateTodoFromDbRow($datarow);
		}
		return $ev;
	}
    
    public function generateEventFromDbRow($datarow) {
		$this->loadEventFields();
		
		$id = $datarow['activityid'];

		$temp = $this->event_keys;
		foreach ($temp as $key=>$val) {
			$newkey = $key;
			if (array_key_exists($key, $this->keys_ical_replace['events'])) {
				unset($temp[$key]);
				$newkey = $this->keys_ical_replace['events'][$key]; 
			}
			//crmv@29407
			if (in_array($key,Array('date_start','due_date'))){
				$datarow[$key] = substr($datarow[$key],0,10);
			}
			//crmv@29407 e
			$temp[$newkey] = $datarow[$key];
		}
		$temp['id'] = $id;
    	$ev = new iCalendar_event;
    	$ev->assign_values($temp);
    	// add some more properties
    	$ev->add_property('TRANSP', 'OPAQUE');
    	if (!$ev->is_valid()) return false;

    	if (!empty($temp['reminder_time'])) {
			$al = new iCalendar_alarm;
			$al->assign_values($temp);
			if ($al->is_valid()) $ev->add_component($al);
		}
	    
	    return $ev;
    }
    
    public function generateTodoFromDbRow($datarow) {
		$this->loadTodoFields();
		
		$id = $datarow['activityid'];
		
		$temp = $this->todo_keys;
		foreach($temp as $key=>$val){
			$newkey = $key;
			if (array_key_exists($key, $this->keys_ical_replace['todo'])) {
				unset($temp[$key]);
				$newkey = $this->keys_ical_replace['todo'][$key]; 
			}
			//crmv@29407
			if (in_array($key,Array('date_start','due_date'))){
				$datarow[$key] = substr($datarow[$key],0,10);
			}
			//crmv@29407 e	
			$temp[$newkey] = $datarow[$key];
		}
    	$ev = new iCalendar_todo;
		$ev->assign_values($temp);
		$ev->add_property('TRANSP', 'OPAQUE');
		if (!$ev->is_valid()) return false;
		
		return $ev;
    }
    
    public function generateTimezone() {
		global $default_timezone;
    
		$tz = new iCalendar_timezone;
		if(!empty($default_timezone)){
			$tzid = explode('/',$default_timezone);
		} else {
			$default_timezone = date_default_timezone_get();
			$tzid = explode('/',$default_timezone);
		}
		
		$dtz = new DateTimeZone($default_timezone);
		
		// get all transitions for one year back/ahead
		$year = 86400 * 360;
		$from = $to = 0;
		$transitions = $dtz->getTransitions($from - $year, $to + $year);

		if (!empty($tzid[1])) {
			$tz->add_property('TZID', $tzid[1]);
		} else {
			$tz->add_property('TZID', $tzid[0]);
		}

		$std = null; $dst = null;
		foreach ($transitions as $i => $trans) {
			$cmp = null;

			// skip the first entry...
			if ($i == 0) {
				// ... but remember the offset for the next TZOFFSETFROM value
				$tzfrom = $trans['offset'] / 3600;
				continue;
			}
	
			// daylight saving time definition
			if ($trans['isdst']) {
				$t_dst = $trans['ts'];
				$dst = new iCalendar_timezone_daylight();
				$cmp = $dst;
			}
			// standard time definition
			else {
				$t_std = $trans['ts'];
				$std = new iCalendar_timezone_standard();
				$cmp = $std;
			}

			if ($cmp) {
				$dt = new DateTime($trans['time']);
				$offset = $trans['offset'] / 3600;

				$cmp->add_property('DTSTART', $dt->format('Ymd\THis'));
				$cmp->add_property('TZOFFSETFROM', sprintf('%s%02d%02d', $tzfrom >= 0 ? '+' : '', floor($tzfrom), ($tzfrom - floor($tzfrom)) * 60));
				$cmp->add_property('TZOFFSETTO', sprintf('%s%02d%02d', $offset >= 0 ? '+' : '', floor($offset), ($offset - floor($offset)) * 60));

				// add abbreviated timezone name if available
				if (!empty($trans['abbr'])) {
					//$cmp->TZNAME = $trans['abbr'];
					$cmp->add_property('TZNAME', $trans['abbr']);
				}

				$tzfrom = $offset;
				$tz->add_component($cmp);
			}

			// we covered the entire date range
			if ($std && $dst && min($t_std, $t_dst) < $from && max($t_std, $t_dst) > $to) {
				break;
			}
		}

		return $tz;
    }
    
    // load the info about the event fields
    protected function loadEventFields() {
		global $current_user;
    
		if (is_null($this->event_fields)) $this->event_fields = getColumnFields('Events');
		if (is_null($this->event_keys)) {
			$this->event_keys = array();
			foreach ($this->event_fields as $key=>$val) {
				if (getFieldVisibilityPermission('Events',$current_user->id,$key) == 0) {
					if (!array_key_exists($key, $this->keyvals_to_replace['events'])) {
						$this->event_keys[$key] = 'yes'; 
					} else {
						$this->event_keys[$this->keyvals_to_replace['events'][$key]] = 'yes'; 
					}
				}
			}
		}
    }
    
    protected function loadTodoFields() {
		global $current_user;

		if (is_null($this->todo_fields)) $this->todo_fields = getColumnFields('Calendar');
		if (is_null($this->todo_keys)) {
			$this->todo_keys = array();
			foreach($this->todo_fields as $key=>$val){
				if (getFieldVisibilityPermission('Calendar',$current_user->id,$key) == 0) {
					if (!array_key_exists($key, $this->keyvals_to_replace['todo'])) {
						$this->todo_keys[$key] = 'yes'; 
					} else {
						$this->todo_keys[$this->keyvals_to_replace['todo'][$key]] = 'yes'; 
					}
				}
			}
		}
    }
	
	function generateArray($ical_activity,$activitytype){
		global $current_user;
		
		$activity = array();
		if($activitytype == 'vevent'){
			$modtype = 'Events';
		} else {
			$modtype = 'Calendar';
		}
		
		$mapping = $activitytype.'_mapping';
		$field_mapping = $activitytype.'field_mapping';
		
		foreach($this->$mapping as $key=>$comp){
			$type = $comp['type'];
			$component = $comp['component'];
			$function = $comp['function'];
			if(!is_array($component)){
				if ($type == 'invitee') {
					// invitations
					if (!is_array($activity['invitees'])) $activity['invitees'] = array();
					while ($invit = $ical_activity->getProperty($key, false, true)) {
						$vteInvitee = $this->getVteInvitee($invit);
						if ($vteInvitee) $activity['invitees'][] = $vteInvitee;
					}
				} elseif ($type == 'organizer') {
					$org = $ical_activity->getProperty($key, false, true);
					$vteInvitee = $this->getVteInvitee($org, false);
					if ($vteInvitee) $activity['organizer'] = $vteInvitee;
				// crmv@185576
				} elseif ($type == 'recurrence') {
					$rrule = $ical_activity->getProperty($key, false, true);
					$rtype = RecurringType::fromRRule($rrule['value'], $activity);
					if ($rtype) {
						$activity['recurringtype'] = $rtype->getRecurringType();
						$activity['recurrence'] = $rtype;
						$repLabel = $rtype->getDisplayRecurringInfo();
						$activity['recurrence_text'] = $repLabel['full_text'];
					}
				// crmv@185576e
				} else {
					if(isset($this->$field_mapping) && isset($this->$field_mapping[$component])){ // crmv@74794
						if(getFieldVisibilityPermission($modtype,$current_user->id,$this->$field_mapping[$component])=='0')
							$activity[$this->$field_mapping[$component]] = $ical_activity->getProperty($key);
						else
							$activity[$this->$field_mapping[$component]] = '';
					} else {
						if(getFieldVisibilityPermission($modtype,$current_user->id,$component)=='0') {
							// crmv@81052
							$property = $ical_activity->getProperty($key);
							if ($property === false && $type == 'string') $property = '';
							$activity[$component] = $property;
							// crmv@81052e
						} else {
							$activity[$component] = '';
						}
					}
				}
			} 
			else {
				$temp = $ical_activity->getProperty($key);
				$count = 0;
				if($type == 'string'){
					$values = $temp;
				} else if($type == 'datetime'){
					$values = $this->strtodatetime($temp);
				}
				foreach($component as $index){
					if(!isset($activity[$index])){
						if(isset($this->$field_mapping) && isset($this->$field_mapping[$index])){ // crmv@74794
							if(getFieldVisibilityPermission($modtype,$current_user->id,$this->$field_mapping[$index])=='0')
								$activity[$this->$field_mapping[$index]] = $values[$count];
							else
								$activity[$this->$field_mapping[$index]] = '';
						} else {
							if(getFieldVisibilityPermission($modtype,$current_user->id,$index)=='0')
								$activity[$index] = $values[$count];
							else 
								$activity[$index] = '';
						}
					}
					$count++;
				}
				unset($values);
			}
			
			if(isset($function) && method_exists($this,$function)){
				$activity[$component] = $this->$function($component,$activity[$component]);
			}
		}
		
		if($activitytype == 'vevent'){
			$activity['activitytype'] = 'Meeting';

			// crmv@90495
			// fix status, if it's in the future
			$now = date('Y-m-d H:i');
			$eventStart = $activity['date_start'].' '.$activity['time_start'];
			if ($activity['eventstatus'] == 'Held' && $eventStart >= $now) {
				$activity['eventstatus'] = 'Planned';
			}
			// crmv@90495e
			
			// set the reminder
			$valarm = $ical_activity->getProperty('valarm');
			if(!empty($valarm)){
				$temp = str_replace("PT",'',$valarm['trigger']);
				$duration_type = $temp[strlen($temp)-1];
				$duration = intval($temp);
				if($duration_type=='H'){
					$reminder_time = $duration*60;
				} else if($duration_type=='M'){
					$reminder_time = $duration;
				}
				$activity['reminder_time'] = $reminder_time;
			}			
		} else {
			$activity['activitytype'] = 'Task';
		}
		
		// look for and existing record with same uid
		if (!empty($activity['ical_uuid'])) {
			if (!$this->calendarFocus) $this->calendarFocus = CRMEntity::getInstance('Calendar');
			$crmid = $this->calendarFocus->getCrmidFromUuid($activity['ical_uuid'], $activity['recurr_idx']); // crmv@81126
			if ($crmid > 0) {
				$activity['activityid'] = $crmid;
			}
		}
		
		return $activity;
	}
	
	function getVteInvitee($invitee, $searchVteRecord = true) {
		$result = null;
		$mail = $invitee['value'];
		if (preg_match('/^mailto:(.+)$/i', $mail, $matches)) {
			$result = array();
			$mail = $matches[1];
			$result['email'] = $mail;
			$result['partecipation'] = $this->handlePARTSTAT($invitee['params']['PARTSTAT']);
			$result['cname'] = $invitee['params']['CN'] ?: $mail;
			
			// now fond a record from the vte
			if ($searchVteRecord) {
				$rec = $this->searchVteInvitee($mail);
				if ($rec) $result['record'] = $rec;
			}
		}
		return $result;
	}
	
	// Search in the VTE for a User or Contact with this email. Only the first match is returned, Users have priority
	function searchVteInvitee($email) {
		if (!$this->helperObject) {
			require_once('include/Webservices/VtenextCRMObject.php');//crmv@207871
			$this->helperObject = new VtenextCRMObject('Events', false);//crmv@207871
		}
		
		$rec = null;
		$list = $this->helperObject->lookForInvitee($email, true, true); // crmv@189222
		if (is_array($list) && count($list) > 0) {
			if ($list[0] && $list[0]['crmid'] > 0) {
				$rec = $list[0];
				$rec['entityname'] = getEntityName($rec['module'], $rec['crmid'], true);
				if (is_array($rec['entityname'])) {
					$rec['entityname'] = $rec['entityname'][$rec['crmid']];
				}
			}
		}
		
		return $rec;
	}
	
	function handlePARTSTAT($value) {
		switch($value){
			case 'ACCEPTED':
				$value = 2;
			break;
			case 'DECLINED':
				$value = 1;
			break;
			default:
				$value = 0;
			break;
		}
		return $value;
	}
	
	function handleCLASS($fieldname,$value){
		switch($value){
			case 'PUBLIC':
				$value = 'Public';
			break;
			case 'PRIVATE':
				$value = 'Private';
			break;
			default:
				$value = 'Standard';
			break;
		}
		
		return $value;
	}
	
	function handleSTATUS($fieldname,$value){
		//event
		if ($fieldname == 'eventstatus') {
			switch ($value) {
				case 'TENTATIVE':
					$value = 'Planned';
				break;
				case 'CONFIRMED':
					$value = 'Held';
				break;
				case 'CANCELLED':
					$value = 'Not Held';
				break;
				default:
					$value = 'Planned';
				break;
			}
		} else {
		//todo
			switch ($value) {
				case 'NEEDS-ACTION':
					$value = 'Pending Input';
				break;
				case 'COMPLETED':
					$value = 'Completed';
				break;
				case 'IN-PROCESS':
					$value = 'In Progress';
				break;
				case 'CANCELLED':
					$value = 'Deferred';
				break;
				default:
					$value = 'Planned';
				break;
			}
		}
		
		return $value;
	}
	// crmv@68357e
	
	function setBlankIfNull($fieldname,$value){
		if(empty($value)){
			$value = '';
		}
		return $value;
	}
	
	function handleDESCRIPTION($fieldname,$value){
		if(!empty($value)){
			$value = str_replace('\r\n',"\r\n",$value);
			$value = str_replace('\n',"\n",$value);
		}
		return $value;
	}
	
	// crmv@68357 - use the timezone (only utc for now)
	function strtodatetime($date){
		global $default_timezone;
		
		if ($date['tz'] == 'Z') {
			$inputTz = new DateTimeZone('UTC');
		} else {
			$inputTz = new DateTimeZone($default_timezone);
		}
		$dt = new DateTime('now', $inputTz);
		$dt->setDate($date['year'], $date['month'], $date['day']);
		$dt->setTime(intval($date['hour']), intval($date['min']), intval($date['sec']));
		$dt->setTimezone(new DateTimeZone($default_timezone));
		$output = array(
			$dt->format('Y-m-d'),
			$dt->format('H:i'),
		);
		return $output;
	}
	// crmv@68357e

}
//crmv@38878