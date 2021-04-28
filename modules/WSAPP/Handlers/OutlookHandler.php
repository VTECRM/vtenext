<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'include/fields/DateTimeField.php';
require_once 'modules/WSAPP/SyncServer.php';
require_once 'modules/WSAPP/Handlers/SyncHandler.php';

Class OutlookHandler extends SyncHandler {

    public function __construct($appkey){
        $this->syncServer = new SyncServer();
        $this->key = $appkey;
    }

    public function get($module,$token,$user){
        $this->syncModule = $module;
        $this->user = $user;
        $result = $this->syncServer->get($this->key,$module,$token,$user);
        $nativeForamtElementList = $result;
        $nativeForamtElementList['created'] = $this->syncToNativeFormat($result['created']);
        $nativeForamtElementList['updated'] = $this->syncToNativeFormat($result['updated']);
        return $nativeForamtElementList;
    }
    public function put($element,$user){
        $this->user = $user;
        $element = $this->nativeToSyncFormat($element);
        if($element=='Events'){
            //To convert minutes to seconds. Since the webservices require the reminder to be in seconds
            $this->convertReminderTimeToSecond($element);
        }
        return $this->syncServer->put($this->key,$element,$user);
    }
    function map($olMapElement,$user){
        $this->user = $user;
        $element = $this->convertMapRecordsToSyncFormat($olMapElement);
        return $this->syncServer->map($this->key,$element, $user);
    }
    function nativeToSyncFormat($element){
        $syncFormatElementList = array();
        foreach($element as $recordDetails){
            if(!empty($recordDetails['values'])){
                $recordDetails['values'] = $this->convertRecordToSyncFormat($recordDetails['module'], $recordDetails['values']);
            }
            $syncFormatElementList[] = $recordDetails;
        }
        return $syncFormatElementList;
    }
    function syncToNativeFormat($recordList){
        $nativeFormatRecordList = array();
        foreach($recordList as $record){
            $nativeFormatRecordList[] = $this->convertRecordToNativeFormat($this->syncModule, $record);
        }
        return $nativeFormatRecordList;
    }

    private function convertRecordToSyncFormat($module,$record){
        if($module == 'Events' || $module =='Calendar'){
            $startTime = $record['start_time'];
            $endTime = $record['end_time'];
            $dateFormat = "Y-m-d";
            $timeFormat = "H:i:s";

            $record['date_start'] = date($dateFormat,strtotime($startTime));
            $record['time_start'] = date($timeFormat,strtotime($startTime));

            $record['due_date'] = date($dateFormat,strtotime($endTime));
            $record['time_end'] = date($timeFormat,strtotime($endTime));

            $record['duration_hours'] = date('H',(strtotime($endTime)-strtotime($startTime)));
            $record['duration_minutes'] = date('i',(strtotime($endTime)-strtotime($startTime)));
	
			//TODO:Make the Oulook client not to send the activity type parameter
			/* crmv@2390m
			if($module=="Events"){
				unset($record['activitytype']);
			}
			crmv@2390me */
        }
        $record['modifiedtime'] = $record['utclastmodifiedtime'];
        return $record;
    }
	//crmv@2390m
    private function convertRecordToNativeFormat($module, $record){
    	if($module == 'Events'){
    		$record['start_time'] = $record['date_start']." ".$record['time_start'];
    		$record['end_time'] = $record['due_date']." ".$record['time_end'];

			// crmv@50039
			$record['start_time'] = adjustTimezone($record['start_time'], 0, $this->user->column_fields['user_timezone'], false);
			$record['end_time'] = adjustTimezone($record['end_time'], 0, $this->user->column_fields['user_timezone'], false);
			// crmv@50039e

    	} else if($module == 'Calendar') {
    		$dformat = "Y-m-d H:i:s";

    		$record['start_time'] = date($dformat,strtotime($record['date_start']));
    		$record['end_time'] = date($dformat,strtotime($record['due_date']));

    		//$record['start_time'] = adjustTimezone($record['start_time'], $this->user->timezonediff);
    		//$record['end_time'] = adjustTimezone($record['start_time'], $this->user->timezonediff);

    		/*crmv@TODO: convert timezone ///////////////////
    		 // convert the start time and end time to user time zone as outlook does not take the datetime in utc
    		 $oldDateFormat = $this->user->date_format;
    		 $this->user->date_format = 'yyyy-mm-dd';
    		 $dateTimeField = new DateTimeField($record['start_time']);
    		 $record['start_time'] = adjustTimezone($dateTimeField->getDisplayDateTimeValue($this->user), $this->user->timezonediff);

    		 $dateTimeField = new DateTimeField($record['end_time']);
    		 $record['end_time'] = adjustTimezone($dateTimeField->getDisplayDateTimeValue($this->user), $this->user->timezonediff);
    		 $this->user->date_format = $oldDateFormat;
    		 */
    	}
        return $record;
    }
	//crmv@2390me
    private function convertMapRecordsToSyncFormat($elements){
        $syncMapFormatElements = array();
        $syncMapFormatElements['create'] = array();
        $syncMapFormatElements['delete'] = array();
        $syncMapFormatElements['update'] = array();
        
        foreach($elements as $olElement){
            if($olElement['mode']=="create"){
                $syncMapFormatElements['create'][$olElement['clientid']] = $olElement['values'];
            }
            else if($olElement['mode']=="update"){
                $syncMapFormatElements['update'][$olElement['clientid']] = $olElement['values'];
            }
            else if($olElement['mode']=="delete"){
                $syncMapFormatElements['delete'][] = $olElement['clientid'];
            }
        }
        return $syncMapFormatElements;
    }

    private function convertReminderTimeToSecond($records){
        foreach($records as $record){
            if(!empty($record['values'])){
                //converting mins to seconds
                $record['reminder_time']  = $record['reminder_time']*60;
            }
        }
    }





}

?>