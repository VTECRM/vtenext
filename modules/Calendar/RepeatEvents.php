<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * Class to handle repeating events
 */
class Calendar_RepeatEvents {

	/**
	 * Get timing using YYYY-MM-DD HH:MM:SS input string.
	 */
	static protected function mktime($fulldateString) {
		$splitpart = self::splittime($fulldateString);
		$datepart = explode('-', $splitpart[0]);
		$timepart = explode(':', $splitpart[1]);
		return mktime($timepart[0], $timepart[1], 0, $datepart[1], $datepart[2], $datepart[0]);
	}
	
	/**
	 * Increment the time by interval and return value in YYYY-MM-DD HH:MM format.
	 */
	static protected function nexttime($basetiming, $interval) {
		return date('Y-m-d H:i', strtotime($interval, $basetiming));
	}
	
	/**
	 * Split full timing into date and time part.
	 */
	static protected function splittime($fulltiming) {
		return explode(' ', $fulltiming);
	}
	
	/**
	 * Calculate the time interval to create repeated event entries.
	 */
	static public function getRepeatInterval($type, $frequency, $recurringInfo, $start_date, $limit_date) {
		$repeatInterval = Array();
		$starting = self::mktime($start_date);
		$limiting = self::mktime($limit_date);

		if($type == 'Daily') {	
			$count = 0;
			while(true) {
				++$count;
				$interval = ($count * $frequency);
				if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
					break;
				}
				$repeatInterval[] = $interval;
			}
		} else if($type == 'Weekly') {
			if($recurringInfo->dayofweek_to_rpt == null) {
				$count = 0;
				$weekcount = 7;
				while(true) {
					++$count;
					$interval = $count * $weekcount;
					if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
						break;
					}
					$repeatInterval[] = $interval;
				}
			} else {
				$count = 0;
				while(true) {
					++$count;
					$interval = $count;
					$new_timing = self::mktime(self::nexttime($starting, "+$interval days"));
					$new_timing_dayofweek = date('N', $new_timing);
					if($new_timing > $limiting) {
						break;
					}
					if(in_array($new_timing_dayofweek, $recurringInfo->dayofweek_to_rpt)) {	//crmv@20623
						$repeatInterval[] = $interval;
					}
				}
			}
		} else if($type == 'Monthly') {
			$count = 0;
			$avg_monthcount = 30; // TODO: We need to handle month increments precisely!
			while(true) {
				++$count;
				$interval = $count * $avg_monthcount;
				if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
					break;
				}
				$repeatInterval[] = $interval;
			}
		} else if($type == 'Yearly') {
			$count = 0;
			$avg_monthcount = 30;
				while(true) {
					++$count;
					$interval = $count * $avg_monthcount;
					if(self::mktime(self::nexttime($starting, "+$interval days")) > $limiting) {
						break;
				}
				$repeatInterval[] = $interval;
			}
		}
		return $repeatInterval;
	}

	/**
	 * Repeat Activity instance till given limit.
	 */
	static public function repeat($focus, $recurObj) {
		
		global $adb, $table_prefix;
		
		$frequency = $recurObj->recur_freq;
		$repeattype= $recurObj->recur_type;
		
		$base_focus = CRMEntity::getInstance('Activity');
		$base_focus->column_fields = $focus->column_fields;
		$base_focus->id = $focus->id;

		$skip_focus_fields = Array ('record_id', 'createdtime', 'modifiedtime', 'recurringtype');

		/** Create instance before and reuse */
		$new_focus = CRMEntity::getInstance('Activity');

		$eventStartDate = getValidDBInsertDateValue($focus->column_fields['date_start']);
		$interval = strtotime(getValidDBInsertDateValue($focus->column_fields['due_date'])) - 
				strtotime(getValidDBInsertDateValue($focus->column_fields['date_start']));

		foreach ($recurObj->recurringdates as $index => $startDate) {
			if($index == 0 && date('Y-m-d',strtotime($eventStartDate)) == $startDate) { //crmv@32334
				continue;
			}
			$startDateTimestamp = strtotime($startDate);
			$endDateTime = $startDateTimestamp + $interval;
			$endDate = date('Y-m-d', $endDateTime);
			
			// Reset the new_focus and prepare for reuse
			if(isset($new_focus->id)) unset($new_focus->id);
			$new_focus->column_fields = array();

			foreach($base_focus->column_fields as $key=>$value) {
				if(in_array($key, $skip_focus_fields)) {
					// skip copying few fields
				} else if($key == 'date_start') {
					$new_focus->column_fields['date_start'] = $startDate;
				} else if($key == 'due_date') {
					$new_focus->column_fields['due_date']   = $endDate;
				} else {
					$new_focus->column_fields[$key]         = $value;
				}
			}
			if($numberOfRepeats > 10 && $index > 10) {
				unset($new_focus->column_fields['sendnotification']);
			}
			$new_focus->column_fields['recurr_idx'] = $index; // crmv@81126
			$new_focus->repeating = true; //crmv@65492
			$new_focus->save('Calendar');
			
			//crmv@56945
			if(isset($_REQUEST['contactidlist']) && $_REQUEST['contactidlist'] != '')
			{
				//split the string and store in an array
				$storearray = explode (";",$_REQUEST['contactidlist']);
				// crmv@166422 - removed code
				foreach($storearray as $id)
				{
					if($id != '')
					{
						$record = $new_focus->id;
						$sql = "insert into ".$table_prefix."_cntactivityrel values (?,?)";
						$adb->pquery($sql, array($id, $record));
						// crmv@166422 - removed useless code
					}
				}
			}
			
			// crmv@166422 - removed useless code
			//crmv@56945e
		}
	}

	static public function repeatFromRequest($focus) {
		global $log, $default_charset, $current_user;
		$recurObj = getrecurringObjValue();
		self::repeat($focus, $recurObj);
	}
}
