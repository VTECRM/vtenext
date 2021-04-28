<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require_once('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/language/en_us.lang.php');
require_once('modules/Emails/mail.php');

global $app_strings,$adb,$table_prefix;

//query the vte_notifyscheduler vte_table and get data for those notifications which are active
//crmv@10448
$sql = "select id,email1 from ".$table_prefix."_users where is_admin = 'on'";
$res = $adb->pquery($sql,Array());
$admin_arr = array();
if ($res){
	while ($row = $adb->FetchByAssoc($res,-1,false)){
		$admin_arr[$row['id']] = $row['email1'];
	}
}

if (check_notification_scheduler(1)){
	//Delayed Tasks Notification
	//get all those activities where the status is not completed even after 24 hours from due date
	$sql="SELECT
		  ".$table_prefix."_activity.status,
		  ".$table_prefix."_activity.activityid,
		  subject,
		  ".$table_prefix."_crmentity.smownerid
		FROM ".$table_prefix."_activity
		  INNER JOIN ".$table_prefix."_crmentity
		    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_activity.activityid
		WHERE ".$table_prefix."_crmentity.deleted = 0
		    AND ".$table_prefix."_activity.status <> 'Completed'
		    AND activitytype = 'Task'
		    AND NOW() > DATE_ADD(STR_TO_DATE(".$adb->sql_concat(Array('".$table_prefix."_activity.due_date',"' '","'19:00:00'")).",'%Y-%m-%d %H:%i'), INTERVAL 1 DAY)
		    ";
	$result = $adb->query($sql);
	if ($result){
		// Retriving the Subject and message from reminder table
		$sql = "select notificationsubject,notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid=1";
		$result_main = $adb->FetchByAssoc($adb->pquery($sql, array()),-1,false);
		while ($row = $adb->FetchByAssoc($result)){
			$status=$row['status'];
			$subject = $row['subject'];
			$user_id = $row['smownerid'];
			$activity_id = $row['activityid'];
			$activitymode = 'Task';
			if ($user_id)
				$assigned_user = getUserName($user_id);
			$mail_body = $result_main['notificationbody']."<br> ".
			$app_strings['LBL_SUBJECT'].": ".$subject."<br> ".
			$app_strings['LBL_ASSIGNED_TO'].": ".$assigned_user."<br><br>".
			$app_strings['Visit_Link'].
			" <a href='".$site_URL."/index.php?action=DetailView&module=Calendar&record=".$activity_id."&activity_mode=".$activitymode."'>".$app_strings['Click here']."</a>";
			$subject = $result_main['notificationsubject'].': '.$subject;
			foreach ($admin_arr as $to_email){
		 		$mail_status = send_mail('Calendar',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$mail_body);
			}
		}
	}
}
//Big Deal Alert
if (check_notification_scheduler(2)){
	$result = $adb->pquery("SELECT sales_stage,amount,potentialid,potentialname FROM ".$table_prefix."_potential inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_potential.potentialid where ".$table_prefix."_crmentity.deleted=0 and sales_stage='Closed Won' and amount > 10000",array());
	if ($result){
		// Retriving the Subject and message from reminder table
		$sql = "select notificationsubject,notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid=2";
		$result_main = $adb->FetchByAssoc($adb->pquery($sql, array()),-1,false);
		while ($myrow = $adb->fetch_array($result))
		{
			$pot_id = $myrow['potentialid'];
			$pot_name = $myrow['potentialname'];
			$body_content = $result_main['notificationbody']."<br><br>".$app_strings['Potential_Id']." ".$pot_id;
			$body_content .= $app_strings['Potential_Name']." ".$pot_name."<br><br>".
			$app_strings['Visit_Link'].
			"<a href='".$site_URL."/index.php?action=DetailView&module=Potentials&record=".$pot_id."'>".$app_strings['Click here']."</a>";
			$subject = $result_main['notificationsubject'].': '.$pot_name;
			foreach ($admin_arr as $to_email){
				$mail_status = send_mail('Potentials',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$body_content);
			}
		}
	}
}
//Pending tickets
if (check_notification_scheduler(3)){
	$result = $adb->pquery("SELECT ".$table_prefix."_troubletickets.status,ticketid FROM ".$table_prefix."_troubletickets INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_troubletickets.ticketid WHERE ".$table_prefix."_crmentity.deleted='0' AND ".$table_prefix."_troubletickets.status <> 'Completed' AND ".$table_prefix."_troubletickets.status <> 'Closed'", array());
	if ($result){
		// Retriving the Subject and message from reminder table
		$sql = "select notificationsubject,notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid=3";
		$result_main = $adb->FetchByAssoc($adb->pquery($sql, array()),-1,false);
		while ($myrow = $adb->fetch_array($result))
		{
			$ticketid = $myrow['ticketid'];
			$body = $result_main['notificationbody']."<br><br> Ticket ".$ticketid."<br>".
			$app_strings['Visit_Link'].
			"<a href='".$site_URL."/index.php?action=DetailView&module=HelpDesk&record=".$ticketid."'>".$app_strings['Click here']."</a>";
			$subject = $result_main['notificationsubject'];
			foreach ($admin_arr as $to_email){
				$mail_status = send_mail('HelpDesk',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$body);
			}
		}
	}
}
//Too many tickets with pending state in the system
if (check_notification_scheduler(4)){
	$maximum_ticket_level_alert=5;
	$fields = array("parent_id","product_id");
	foreach ($fields as $field){
		$sql = "SELECT
				  $field,
				  COUNT(*) AS COUNT
				FROM ".$table_prefix."_troubletickets
				  INNER JOIN ".$table_prefix."_crmentity
				    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid
				WHERE ".$table_prefix."_crmentity.deleted = '0'
				    AND ".$table_prefix."_troubletickets.status <> 'Completed'
				    AND ".$table_prefix."_troubletickets.status <> 'Closed'
				    and $field <> ''
				    GROUP BY $field HAVING COUNT(*) > ?";
		$result = $adb->pquery($sql, array($maximum_ticket_level_alert));
		if ($result){
			// Retriving the Subject and message from reminder table
			$sql = "select notificationsubject,notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid=4";
			$result_main = $adb->FetchByAssoc($adb->pquery($sql, array()),-1,false);
			$count = $adb->query_result($result,0,'count');
			$entity_id = $adb->query_result($result,0,$field);
			$module = getSalesEntityType($entity_id);
			switch ($module){
				case "Accounts":{
					$name = getAccountName($entity_id);
					break;
				}
				case "Contacts":{
					$name = getContactName($entity_id);
					break;
				}
				case "Products":{
					$name = getProductName($entity_id);
					break;
				}
			}
			$body = $result_main['notificationbody'];
			$bocy .="<br> entity name: ".$name."<br>"
			."entity type: ".$module."<br>".
			$app_strings['Visit_Link'].
			"<a href='".$site_URL."/index.php?action=DetailView&module=$module&record=".$entity_id."'>".$app_strings['Click here']."</a>";
			$subject = $result_main['notificationsubject'];
			foreach ($admin_arr as $to_email){
				$mail_status = send_mail('HelpDesk',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$body);
			}
		}
	}
}

////Support Starting
//if (check_notification_scheduler(5)){
//	$result = $adb->pquery("SELECT vte_products.productname FROM vte_products inner join vte_crmentity on vte_products.productid = vte_crmentity.crmid where vte_crmentity.deleted=0 and start_date like ?", array(date('Y-m-d'). "%"));
//	while ($myrow = $adb->fetch_array($result))
//	{
//		$productname=$myrow[0];
//		$body = nl2br($app_strings['Hello_Support'].$productname ."\n ".$app_strings['Congratulations']);
//		$subject = $app_strings['Support_starting'];
//		foreach ($admin_arr as $to_email){
//			$mail_status = send_mail('HelpDesk',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$body);
//		}
//	}
//}
////Support ending
//if (check_notification_scheduler(6)){
//	$result = $adb->pquery("SELECT vte_products.productname from vte_products inner join vte_crmentity on vte_products.productid = vte_crmentity.crmid where vte_crmentity.deleted=0 and expiry_date like ?", array(date('Y-m-d') ."%"));
//	while ($myrow = $adb->fetch_array($result))
//	{
//		$productname=$myrow[0];
//		$body = $app_strings['Support_Ending_Content'].$productname.$app_strings['kindly_renew'];
//		$subject = $app_strings['Support_Ending_Subject'];
//		foreach ($admin_arr as $to_email){
//			$mail_status = send_mail('HelpDesk',$to_email,$REMINDER_NAME,$REMINDER_EMAIL_ID,$subject,$body);
//		}
//	}
//}
//crmv@10448e
?>