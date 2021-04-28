<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* installer:

require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();
$cj = new CronJob();
$cj->name = 'CheckCron';
$cj->active = 1;
$cj->singleRun = false;
$cj->fileName = 'cron/CheckCron.service.php';
$cj->timeout = 3600;
$cj->repeat = 1800;
$CU->insertCronJob($cj);
*/

$installation_name = '';
$from = 'supporto@crmvillage.biz';
$to = 'supporto@crmvillage.biz';
$SMTPAuth = false;
$Host = 'mail.crmvillage.biz';
$Username = '';
$Password = '';

require_once('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

ini_set('memory_limit','256M');

global $log;
$log =& LoggerManager::getLogger('CheckCron');
$log->debug("invoked CheckCron");

$error_cron = false;
$body = '';
$err_cronjobs = '';

global $adb, $table_prefix;
$focus = CRMEntity::getInstance('Messages');

// check cron stopped
$result = $adb->pquery("select * from {$table_prefix}_cronjobs where active = 1 and (status in (?,?) or attempts >= max_attempts)",array('ERROR','TIMEOUT'));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		$error_cron = true;
		$err_cronjobs .= "cronid:{$row['cronid']}, cronname:{$row['cronname']}, status:{$row['status']}, attempts:{$row['attempts']}, lastrun:{$row['lastrun']}<br />";
	}
}
if (!empty($err_cronjobs)) {
	$body .= '*** Cron errors:<br /><br />'.$err_cronjobs.'<br /><br />';
}

// check messages not downloaded
$result = $adb->pquery("SELECT * FROM {$table_prefix}_cronjobs WHERE cronname = ? AND status = ?",array('Messages','PROCESSING'));
if ($result && $adb->num_rows($result) == 0) {
	$resultuid = $adb->pquery("select count(*) as count from {$table_prefix}_messages_cron_uid where status = ? and attempts = ?",array(2,$focus->max_message_cron_uid_attempts));
	if ($resultuid && $adb->num_rows($resultuid) > 0) {
		$count = $adb->query_result($resultuid,0,'count');
		if ($count > 0) {
			$error_cron = true;
			$body .= "*** $count messaggi con status 2 in {$table_prefix}_messages_cron_uid<br /><br />";
		}
	}
}
$result = $adb->pquery("SELECT * FROM {$table_prefix}_cronjobs WHERE cronname = ? AND status = ?",array('MessagesInbox','PROCESSING'));
if ($result && $adb->num_rows($result) == 0) {
	$resultuidi = $adb->pquery("select count(*) as count from {$table_prefix}_messages_cron_uidi where status = ? and attempts = ?",array(2,$focus->max_message_cron_uid_attempts));
	if ($resultuidi && $adb->num_rows($resultuidi) > 0) {
		$count = $adb->query_result($resultuidi,0,'count');
		if ($count > 0) {
			$error_cron = true;
			$body .= "*** $count messaggi con status 2 in {$table_prefix}_messages_cron_uidi";
		}
	}
}

// check messages not sended
$result = $adb->pquery("SELECT * FROM {$table_prefix}_cronjobs WHERE cronname = ? AND status = ?",array('MessagesSend','PROCESSING'));
if ($result && $adb->num_rows($result) == 0) {
	$resultuidi = $adb->pquery("select count(*) as count from {$table_prefix}_emails_send_queue where status = ?",array(2));
	if ($resultuidi && $adb->num_rows($resultuidi) > 0) {
		$count = $adb->query_result($resultuidi,0,'count');
		if ($count > 0) {
			$error_cron = true;
			$body .= "*** $count messaggi con status 2 in {$table_prefix}_emails_send_queue";
		}
	}
}

if ($error_cron) {
	require_once("include/utils/utils.php");
	require_once("modules/Emails/mail.php");
	$mail = new VTEMailer(); // crmv@180739
	setMailerProperties($mail,'['.$installation_name.'] Check Cron',$body,$from,$from,$to);
	$mail->SMTPAuth = $SMTPAuth;
	$mail->Host = $Host;
	$mail->Username = $Username;
	$mail->Password = $Password;
	$mail_status = MailSend($mail);
	if ($mail_status != 1) {
		echo $mail_status;
	}
}

$log->debug("end CheckCron procedure");
?>