<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');

global $adb, $log, $current_user, $table_prefix;

$log =& LoggerManager::getLogger('Newsletter');
$log->debug("invoked Newsletter");

if (!$current_user) {
	$current_user = CRMEntity::getInstance('Users');
	$current_user->id = 1;
}

// crmv@155705
$focus = CRMEntity::getInstance('Newsletter');
$maxAttempts = $focus->max_attempts_permitted;
$limit = $focus->getNoEmailProcessedBySchedule();
unset($focus);

//crmv@24947
$query = "SELECT tbl_s_newsletter_queue.* FROM tbl_s_newsletter_queue
		INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.newsletterid
		WHERE ".$table_prefix."_crmentity.deleted = 0 AND tbl_s_newsletter_queue.status = ? AND tbl_s_newsletter_queue.attempts < ? AND tbl_s_newsletter_queue.date_scheduled <= ?
		ORDER BY tbl_s_newsletter_queue.newsletterid ASC";
$params = array('Scheduled', $maxAttempts, $adb->formatDate(date('Y-m-d H:i:s'), true));
//crmv@24947e
$newsletterid = null;
$result = $adb->limitpQuery($query,0,$limit, $params);
if ($result && $adb->num_rows($result)>0) {
	while ($row=$adb->fetchByAssoc($result)) {
		// load the object only once per newsletter
		if ($newsletterid != $row['newsletterid']) {
			$focus = CRMEntity::getInstance('Newsletter');
			$focus->id = $row['newsletterid'];
			$focus->retrieve_entity_info($focus->id,'Newsletter');
		}
		$newsletterid = $row['newsletterid'];
		$crmid = $row['crmid'];
		
		$mail_status = $focus->sendNewsletter($crmid);
		
		if ($mail_status == 1) {
			$adb->pquery("update tbl_s_newsletter_queue set status = ? where newsletterid = ? and crmid = ?",array('Sent',$newsletterid,$crmid));
			$adb->pquery("update tbl_s_newsletter_queue set date_sent = ? where newsletterid = ? and crmid = ?",array($adb->formatDate(date('Y-m-d H:i:s'), true),$newsletterid,$crmid));
		//crmv@25872	crmv@34219	crmv@55961
		} elseif (in_array($mail_status,array('LBL_RECORD_DELETE','LBL_RECORD_NOT_FOUND','LBL_OWNER_MISSING','LBL_ERROR_MAIL_UNSUBSCRIBED','LBL_INVALID_EMAILADDRESS'))) { //crmv@174550
			$adb->pquery("update tbl_s_newsletter_queue set status = ? where newsletterid = ? and crmid = ?",array('Failed',$newsletterid,$crmid));
			// crmv@38592
			$mail_status_id = intval(array_search($mail_status, $focus->status_list));
			$adb->pquery('insert into tbl_s_newsletter_failed (newsletterid,crmid,statusid) values (?,?,?)',array($newsletterid,$crmid,$mail_status_id));
			// crmv@38592e
		//crmv@25872e	crmv@34219e	crmv@55961e
		}
		//crmv@83542
		$attempts = $row['attempts']+1;
		$adb->pquery("update tbl_s_newsletter_queue set attempts = ? where newsletterid = ? and crmid = ?",array($attempts,$newsletterid,$crmid));
		$adb->pquery("update tbl_s_newsletter_queue set last_attempt = ? where newsletterid = ? and crmid = ?",array($adb->formatDate(date('Y-m-d H:i:s'), true),$newsletterid,$crmid));
		if ($attempts >= $focus->max_attempts_permitted) {
			$adb->pquery("update tbl_s_newsletter_queue set status = ? where newsletterid = ? and crmid = ?",array('Failed',$newsletterid,$crmid));
			$mail_status_id = intval(array_search('LBL_ATTEMPTS_EXHAUSTED', $focus->status_list));
			$adb->pquery('insert into tbl_s_newsletter_failed (newsletterid,crmid,statusid) values (?,?,?)',array($newsletterid,$crmid,$mail_status_id));
		}
		//crmv@83542e
		sleep($focus->getIntervalBetweenEmailDelivery());
	}
	// crmv@47611 - removed sleep
}
// crmv@155705e

$log->debug("end Newsletter procedure");