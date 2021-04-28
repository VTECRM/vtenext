<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@139057 */
 
require_once 'modules/Reports/Reports.php';
require_once 'modules/Reports/ReportRun.php';

class ScheduledReports extends SDKExtendableUniqueClass {

	const SCHEDULED_HOURLY = 1;
	const SCHEDULED_DAILY = 2;
	const SCHEDULED_WEEKLY = 3;
	const SCHEDULED_BIWEEKLY = 4;
	const SCHEDULED_MONTHLY = 5;
	const SCHEDULED_YEARLY = 6;
	
	public $zipThreshold = 5;		// zip files if the total size is greater than this number of MB
	public $reportTimeout = 120; 	// if a report is processing for more than this (minutes), it's rescheduled for the next time!
	public $enableLog = true;
	//public $logFile = 'logs/scheduled_reports.log';
	
	protected $reports;

	public function  __construct() {
	}
	
	protected function log($text) {
		if ($this->enableLog) {
			// log is automatic with cron
			//file_put_contents($this->logFile, '['.date('Y-m-d H:i:s').'] '.$text."\n", FILE_APPEND);
			echo $text."\n";
			//@ob_flush();
			//flush();
		}
	}
	
	/**
	 * Execute all the scheduled reports
	 */
	public function runScheduledReports() {
		
		$this->rescheduleStaleReports();
		$this->initGlobalVars();
		
		while ($reportid = $this->getNextScheduledReport()) {
			$this->executeReport($reportid);
		}

		$this->restoreGlobalVars();
	}
	
	protected function initGlobalVars() {
		global $current_user, $currentModule, $current_language;
		global $theme, $default_theme;
		global $mod_strings, $app_strings;
		
		$currentModule = 'Reports';
		
		if (!$this->savedUser) {
			$this->savedUser = $current_user;
		}
		$current_user = Users::getActiveAdminUser();
		
		if (!$this->savedLanguage) {
			$this->savedLanguage = $current_language;
		}
		if (empty($current_language)) $current_language = $current_user->default_language ?: 'en_us';
		
		$app_strings = return_application_language($current_language);
		$mod_strings = return_module_language($current_language, $currentModule);
		
		$theme = $current_user->default_theme ?: $default_theme;
		
		$this->log("Current user set to #{$current_user->id} and language to '{$current_language}'");
	}
	
	protected function restoreGlobalVars() {
		global $current_user, $currentModule, $current_language;
		
		$current_language = $this->savedLanguage;
		$current_user = $this->savedUser;
		
		$this->log("Current user and language restored");
	}
	
	/**
	 * Check for reports "running"" for too long and schedule them for the next cycle
	 */
	public function rescheduleStaleReports() {
		global $adb, $table_prefix;
		
		$timeoutTime = date('Y-m-d H:i:s', time()-$this->reportTimeout*60);
		
		$result = $adb->pQuery(
			"SELECT s.reportid, rc.scheduling
			FROM {$table_prefix}_report_scheduled s
			INNER JOIN {$table_prefix}_report r on r.reportid = s.reportid
			INNER JOIN {$table_prefix}_reportconfig rc on rc.reportid = r.reportid
			WHERE rc.scheduling IS NOT NULL AND s.status IN (?) AND (s.last_execution IS NOT NULL AND s.last_execution <= ?)", 
			array('PROCESSING', $timeoutTime)
		);
		
		$reportid = null;
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->FetchByAssoc($result, -1, false)) {
				$reportid = $row['reportid'];
				$scheduling = Zend_Json::decode($row['scheduling']);
				$this->updateNextExecution($reportid, $scheduling);
				$adb->pquery("UPDATE {$table_prefix}_report_scheduled SET status = ? WHERE reportid = ?", array('', $reportid));
				$this->log("Report #$reportid rescheduled");
			}
		}
	}
	
	public function calcNextExecution($scheduling, $now = null) {
		
		if (is_null($now)) $now = time();
		
		$weekDays = array(
			'0'=>'Sunday','1'=>'Monday','2'=>'Tuesday','3'=>'Wednesday',
			'4'=>'Thursday','5'=>'Friday','6'=>'Saturday'
		);
		$weekDays = array_flip($weekDays);

		$scheduleType		= $scheduling['schedule']['scheduletype'];
		$scheduledMonth		= $scheduling['schedule']['month'];
		$scheduledDayOfMonth= $scheduling['schedule']['date'];
		$scheduledDayOfWeek = $scheduling['schedule']['day'];
		$scheduledTime		= $scheduling['schedule']['time'];
		
		if(empty($scheduledTime)) {
			$scheduledTime = '10:00';
		} elseif(stripos(':', $scheduledTime) === false) {
			$scheduledTime = $scheduledTime .':00';
		}

		if($scheduleType == self::SCHEDULED_HOURLY) {
			$now = strtotime(date('Y-m-d H:00:00'),$now);
			return date("Y-m-d H:i:s",strtotime("+1 hour",$now));
		} elseif($scheduleType == self::SCHEDULED_DAILY) {
			if (strtotime(date("Y-m-d {$scheduledTime}",$now)) < $now){
				return date("Y-m-d H:i:s",strtotime("+ 1 day ".$scheduledTime,$now));
			}
			else{
				return date("Y-m-d H:i:s",strtotime($scheduledTime,$now));
			}
		} elseif ($scheduleType == self::SCHEDULED_WEEKLY) {
			$scheduledDayOfWeekText = $scheduledDayOfWeek;
			$scheduledDayOfWeek = $weekDays[$scheduledDayOfWeek];
			if(date('w',time()) == $scheduledDayOfWeek) {
				if (strtotime(date("Y-m-d {$scheduledTime}",$now)) < $now){
					return date("Y-m-d H:i:s",strtotime('+1 week '.$scheduledTime,$now));
				}
				else{
					return date("Y-m-d H:i:s",strtotime($scheduledTime,$now));
				}
				
			} else {
				return date("Y-m-d H:i:s",strtotime("next ". $scheduledDayOfWeekText.' '.$scheduledTime,$now));
			}
		} elseif ($scheduleType == self::SCHEDULED_BIWEEKLY) {
			$scheduledDayOfWeekText = $scheduledDayOfWeek;
			$scheduledDayOfWeek = $weekDays[$scheduledDayOfWeek];			
			if(date('w',time()) == $scheduledDayOfWeek) {
				if (strtotime(date("Y-m-d {$scheduledTime}",$now)) < $now){
					return date("Y-m-d H:i:s",strtotime('+2 weeks '.$scheduledTime,$now));
				}
				else{
					return date("Y-m-d H:i:s",strtotime($scheduledTime,$now));
				}				
			} else {
				return date("Y-m-d H:i:s",strtotime("next ". $scheduledDayOfWeekText.' '.$scheduledTime,$now));
			}
		} elseif ($scheduleType == self::SCHEDULED_MONTHLY) {
			
			$currentTime = time();
			$currentDayOfMonth = date('j',$currentTime);

			if($scheduledDayOfMonth == $currentDayOfMonth) {
				if (strtotime(date("Y-m-d {$scheduledTime}",$now)) < $now){
					return date("Y-m-d H:i:s",strtotime('+1 month '.$scheduledTime,$now));
				}
				else{
					return date("Y-m-d H:i:s",strtotime($scheduledTime,$now));
				}				
			} else {
				$monthInFullText = date('F',$currentTime);
				$yearFullNumberic = date('Y',$currentTime);
				if($scheduledDayOfMonth < $currentDayOfMonth) {
					$nextMonth = date("Y-m-d H:i:s",strtotime('next month'));
					$monthInFullText = date('F',strtotime($nextMonth));
				}
				return date("Y-m-d H:i:s",strtotime($scheduledDayOfMonth.' '.$monthInFullText.' '.$yearFullNumberic.' '.$scheduledTime,$now));
			}
		} elseif ($scheduleType == self::SCHEDULED_YEARLY) {
		
            // crmv@189679
            $scheduledMoment = date("Y-m-d H:i:s",strtotime(date('Y').'-'.$scheduledMonth.'-'.$scheduledDayOfMonth.' '.$scheduledTime,$now));
            $nowMoment = date("Y-m-d H:i:s",$now);
            if($scheduledMoment <= $nowMoment) $scheduledMoment = date("Y-m-d H:i:s",strtotime('+1 year ',strtotime($scheduledMoment)));
		
            return $scheduledMoment;
            // crmv@189679e
		}
	}
	
	public function updateNextExecution($reportid, $scheduling, $now = null) {
		global $adb, $table_prefix;
		
		if (is_null($now)) $now = time();

		$nextTime = $this->calcNextExecution($scheduling, $now);
		$res = $adb->pquery("SELECT reportid FROM {$table_prefix}_report_scheduled WHERE reportid = ?", array($reportid));
		if ($res && $adb->num_rows($res) > 0) {
			$adb->pquery("UPDATE {$table_prefix}_report_scheduled SET next_execution=? WHERE reportid=?", array($nextTime, $reportid));
		} else {
			$params = array($reportid, '', $nextTime);
			$adb->pquery("INSERT INTO {$table_prefix}_report_scheduled (reportid, status, next_execution) VALUES (?,?,?)", $params); // crmv@157618
		}
		return $nextTime;
	}
	
	public function setStatus($reportid, $status) {
		global $adb, $table_prefix;
		if ($status == 'PROCESSING') {
			$sql = "UPDATE {$table_prefix}_report_scheduled SET status = ?, last_execution = ? WHERE reportid=?";
			$params = array($status, date('Y-m-d H:i:s'), $reportid);
		} else {
			$sql = "UPDATE {$table_prefix}_report_scheduled SET status = ? WHERE reportid=?";
			$params = array($status, $reportid);
		}
		$adb->pquery($sql, $params);
	}
	
	public function getNextScheduledReport() {
		global $adb, $table_prefix;
		
		$currentTime = date('Y-m-d H:i:s');
		
		// crmv@203726 - mysql 8s
		$result = $adb->limitpQuery(
			"SELECT s.reportid
			FROM {$table_prefix}_report_scheduled s
			INNER JOIN {$table_prefix}_report r on r.reportid = s.reportid
			INNER JOIN {$table_prefix}_reportconfig rc on rc.reportid = r.reportid
			WHERE rc.scheduling IS NOT NULL AND s.status IN ('', 'ERROR') AND (s.next_execution IS NULL OR s.next_execution = ? OR s.next_execution <= ?)
			ORDER BY s.last_execution ASC", 
			0,1,
			array('0000-00-00 00:00:00',$currentTime)
		);
		// crmv@203726e
		
		$reportid = null;
		if ($result && $adb->num_rows($result) > 0) {
			$reportid = $adb->query_result_no_html($result, 0, 'reportid');
		}
		
		return $reportid;
	}

	public function generateFileName($reportid, $config) {
		static $nameCache = array();
		
		if (empty($nameCache[$reportid])) {
			$name = preg_replace('/[^a-zA-Z0-9_\s-]/', '', $config['reportname']).'_'. date('Ymd_Hms');
			$nameCache[$reportid] = $name;
		}
		
		return $nameCache[$reportid];
	}
	
	public function executeReport($reportid) {
	
		if (!$this->reports) $this->reports = Reports::getInstance();
		
		$config = $this->reports->loadReport($reportid);
		$folderid = $config['folderid'];
		$scheduling = $config['scheduling'];
		
		$this->log("Executing report #$reportid in folder #{$folderid}...");
		$this->setStatus($reportid, 'PROCESSING');

		$oReportRun = ReportRun::getInstance($reportid);
		if ($reportinfo = SDK::getReport($reportid, $folderid)) {
			require_once($reportinfo['reportrun']);
			$oReportRun = new $reportinfo['runclass']($reportid);	
		}
		
		// forge a special request
		$_REQUEST = array();
		$_REQUEST['module'] = 'Reports';
		$_REQUEST['record'] = $reportid;
		$_REQUEST['folder'] = $_REQUEST['folderid'] = $folderid; //fix sdk reports
		$_REQUEST['batch_export'] = 1;
		
		// what to export (all)
		$_REQUEST['export_report_main'] = 1;
		if ($oReportRun->hasSummary()){
			$_REQUEST['export_report_summary'] = 1;
		}
		if ($oReportRun->hasTotals()){
			$_REQUEST['export_report_totals'] = 1;
		}
		
		$baseFileName = $this->generateFileName($reportid, $config);
		
		$reportFormat = $scheduling['format'];
		$attachments = array();
		
		$ts0 = microtime(true);
		try{
			if($reportFormat == 'pdf' || $reportFormat == 'both') {
				$fileName = $baseFileName.'.pdf';
				$filePath = 'storage/'.$fileName;
				$attachments[$fileName] = $filePath;
				$_REQUEST['action'] = 'CreatePDF';
				@unlink($filePath);
				$this->log("Generating PDF...");
				include('modules/Reports/CreatePDF.php');
			}
			if ($reportFormat == 'excel' || $reportFormat == 'both') {
				$fileName = $baseFileName.'.xls';
				$filePath = 'storage/'.$fileName;
				$attachments[$fileName] = $filePath;
				$_REQUEST['action'] = 'CreateXL';
				@unlink($filePath);
				$this->log("Generating XLS...");
				include('modules/Reports/CreateXL.php');
			}
		} catch(Exception $e) {
			$this->log("Error generating report: ".$e->getMessage());
			$this->setStatus($reportid, 'ERROR');
			return false;
		}
		
		$ts1 = microtime(true);
		$delta = round($ts1-$ts0);
		$this->log("Report executed in {$delta}s");
		
		// now check for files
		foreach ($attachments as $name=>$path) {
			if (!is_readable($path) || filesize($path) == 0) {
				$this->log("Warning, missing or empty file: {$path}");
				unset($attachments[$name]);
			}
		}
		
		if (count($attachments) == 0) {
			$this->log("Error, no files to attach");
			$this->setStatus($reportid, 'ERROR');
			return false;
		}
		
		$r = $this->sendEmail($config, $attachments);
		
		// and remove attachments
		foreach($attachments as $attachmentName => $path) {
			@unlink($path);
		}
		
		// reschedule ignore if there were mail errors
		$nextTime = $this->updateNextExecution($reportid, $config['scheduling']);
		$this->log("Report rescheduled for $nextTime");
		
		if ($r) {
			$this->setStatus($reportid, '');
		} else {
			$this->setStatus($reportid, 'ERROR');
		}
		
		return $r;
	}
	
	public function sendEmail($config, $attachments) {
		global $current_user;

		require_once('vtlib/Vtecrm/Mailer.php');
		require_once('vtlib/Vtecrm/Zip.php');

		$mailer = new Vtecrm_Mailer();
		$mailer->ConfigSenderInfo(getUserEmail($current_user->id),'Report Batch Send');
		
		$recipientEmails = $this->getRecipientEmails($config['scheduling']);
		foreach($recipientEmails as $name => $email) {
			$mailer->AddAddress($email, $name);
		}
		
		$currentTime = date('Y-m-d H:i:s');
		$subject = $config['reportname'] .' - '. $currentTime .' ('. DateTimeField::getDBTimeZone() .')';

		$contents = getTranslatedString('LBL_AUTO_GENERATED_REPORT_EMAIL', 'Reports') .'<br/><br/>';
		$contents .= '<b>'.getTranslatedString('LBL_REPORT_NAME', 'Reports') .' :</b> '. $config['reportname'] .'<br/>';
		$contents .= '<b>'.getTranslatedString('LBL_DESCRIPTION', 'Reports') .' :</b><br/>'. $config['description'] .'<br/><br/>';
		
		$mailer->Subject = $subject;
		$mailer->Body    = $contents;
		$mailer->ContentType = "text/html";
		
		$totalsize = 0;
		foreach($attachments as $attachmentName => $path) {
			if (is_file($path)){
				$size = filesize($path);
				$totalsize += intval($size)/1024/1024;
			}				
		}
		
		if ($totalsize >= $this->zipThreshold){
			//zip files
			$baseFileName = $this->generateFileName($config['reportid'], $config);
			$zippath = "storage/".$baseFileName.".zip";
			$zipname = $baseFileName.".zip";
			$zip = new Vtecrm_Zip($zippath);
			foreach($attachments as $attachmentName => $path) {
				$zip->addFile($path,$attachmentName);
			}
			$zip->save();
			$mailer->AddAttachment($zippath, $zipname);
			$attachments[$zipname] = $zippath;
		} else {
			foreach($attachments as $attachmentName => $path) {
				$mailer->AddAttachment($path, $attachmentName);
			}
		}
		
		$this->log("Sending email to ".count($recipientEmails)." recipients...");
		//$mailer->SMTPDebug = true;
		// crmv@203907
		$mailer->SMTPOptions=array(
			//Some men just want to watch the world burn...
			'ssl' => array(
				'verify_peer' => false,
				'allow_self_signed' => true, 
				'verify_peer_name' => false,
			)
		);
		// crmv@203907e
		
		$result = $mailer->Send(true);
		
		if ($result != 1) {
			$this->log("Unable to send email");
		}

		return ($result == 1);
	}

	public function getRecipientEmails($scheduling) {
		global $adb, $table_prefix; // crmv@205967
		
		$recipientsInfo = $scheduling['recipients'];

		$recipientsList = array();
		if(!empty($recipientsInfo)) {
			if(!empty($recipientsInfo['users'])) {
				//crmv@203476
				foreach($recipientsInfo['users'] as $oneuser) {
					$result = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE id=? AND status=?", array($oneuser, 'Active'));
					if ($result && $adb->num_rows($result) > 0) {
						$recipientsList[] = $oneuser;
					}
				}
				//crmv@203476e
			}

			if(!empty($recipientsInfo['roles'])) {
				foreach($recipientsInfo['roles'] as $roleId) {
					$roleUsers = getRoleUsers($roleId, true);//crmv@203476
					foreach($roleUsers as $userId => $userName) {
						array_push($recipientsList, $userId);
					}
				}
			}

			if(!empty($recipientsInfo['rs'])) {
				foreach($recipientsInfo['rs'] as $roleId) {
					$users = getRoleAndSubordinateUsers($roleId, true);//crmv@203476
					foreach($users as $userId => $userName) {
						array_push($recipientsList, $userId);
					}
				}
			}

			if(!empty($recipientsInfo['groups'])) {
				require_once 'include/utils/GetGroupUsers.php';
				foreach($recipientsInfo['groups'] as $groupId) {
					$userGroups = new GetGroupUsers();
					$userGroups->getAllUsersInGroup($groupId, true); //crmv@203476
					$recipientsList = array_merge($recipientsList, $userGroups->group_users);
				}
			}
		}
		$recipientsEmails = array();
		if(!empty($recipientsList) && count($recipientsList) > 0) {
			foreach($recipientsList as $userId) {
				$userName = getUserFullName($userId);
				$userEmail = getUserEmail($userId);
				if(!in_array($userEmail, $recipientsEmails)) {
					$recipientsEmails[$userName] = $userEmail;
				}
			}
		}
		return $recipientsEmails;
	}

}