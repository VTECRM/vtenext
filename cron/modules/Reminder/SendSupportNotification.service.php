<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 crmv@57925 */

require('config.inc.php');
require_once('include/utils/utils.php');
require_once("include/database/PearDatabase.php");
require_once('include/logging.php');
require_once("modules/Emails/mail.php");

global $adb, $table_prefix, $log;
global $HELPDESK_SUPPORT_EMAIL_ID,$HELPDESK_SUPPORT_NAME;

$log =& LoggerManager::getLogger('SendSupportNotification');
$log->debug(" invoked SendSupportNotification ");

// retrieve the translated strings.
$app_strings = return_application_language($current_language);

//crmv@10488
	if (check_notification_scheduler(6)){
		//To send email notification before a week
		$query="SELECT
			  ".$table_prefix."_contactdetails.contactid,
			  ".$table_prefix."_contactdetails.email,
			  ".$table_prefix."_contactdetails.firstname,
			  ".$table_prefix."_contactdetails.lastname,
			  contactid
			FROM ".$table_prefix."_customerdetails
			  INNER JOIN ".$table_prefix."_crmentity
			    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_customerdetails.customerid
			  INNER JOIN ".$table_prefix."_contactdetails
			    ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_customerdetails.customerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			    AND support_end_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 WEEK),GET_FORMAT(DATE,'ISO'))";

		$result = $adb->pquery($query, array());


		if($adb->num_rows($result) >= 1)
		{
			while($result_set = $adb->fetch_array($result))
			{

				$content=getcontent_week($result_set["contactid"]);
				$body=$content["body"];
				$body = str_replace('$logo$','<img src="cid:logo" />',$body);
				$subject=$content["subject"];

				$status=send_mail("Support",$result_set["email"],$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$body,'',$HELPDESK_SUPPORT_EMAIL_ID
				);

			}

		}
		//comment / uncomment this line if you want to hide / show the sent mail status
		//showstatus($status);
		$log->debug(" Send Support Notification Befoe a week - Status: ".$status);
	}
	if (check_notification_scheduler(7)){
		//To send email notification before a month
		$query="SELECT
			  ".$table_prefix."_contactdetails.contactid,
			  ".$table_prefix."_contactdetails.email,
			  ".$table_prefix."_contactdetails.firstname,
			  ".$table_prefix."_contactdetails.lastname,
			  contactid
			FROM ".$table_prefix."_customerdetails
			  INNER JOIN ".$table_prefix."_crmentity
			    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_customerdetails.customerid
			  INNER JOIN ".$table_prefix."_contactdetails
			    ON ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_customerdetails.customerid
			WHERE ".$table_prefix."_crmentity.deleted = 0
			    AND support_end_date = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 1 MONTH),GET_FORMAT(DATE,'ISO'))";
		$result = $adb->pquery($query, array());


		if($adb->num_rows($result) >= 1)
		{
			while($result_set = $adb->fetch_array($result))
			{
				$content=getcontent_month($result_set["contactid"]);
				$body=$content["body"];
				$body = str_replace('$logo$','<img src="cid:logo" />',$body);
				$subject=$content["subject"];

				$status=send_mail("Support",$result_set["email"],$HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID,$subject,$body,'',$HELPDESK_SUPPORT_EMAIL_ID);
			}

		}

		//comment / uncomment this line if you want to hide / show the sent mail status
		//showstatus($status);
		$log->debug(" Send Support Notification Befoe a Month - Status: ".$status);
	}
//crmv@10488 e

//used to dispaly the sent mail status
function showstatus($status)
{

	if($status == 1)
		echo "Mails sent successfully";
	else if($status == "")
		echo "No contacts matched";
	else
		echo "Error while sending mails: ".$status;
}



//function used to get the header and body content of the mail to be sent.
function getcontent_month($id)
{
	global $adb, $table_prefix;
	//crmv@12035
    $query="select notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid = ?";
    $res = $adb->pquery($query,Array(5));
    if ($res && $adb->num_rows($res) == 1){
    	$body = $adb->query_result($res,0,'notificationbody');
    	if (is_numeric($body)){
    		$query2 = "SELECT ".$table_prefix."_emailtemplates.subject,".$table_prefix."_emailtemplates.body FROM ".$table_prefix."_emailtemplates where ".$table_prefix."_emailtemplates.templateid = ?";
    		$result = $adb->pquery($query2,Array($body));
    		if ($result && $adb->num_rows($result) == 1){
    			$body = $adb->query_result_no_html($result,0,'body');
    			$subject = $adb->query_result($result,0,'subject');
    		}
    	}
    }
    //crmv@12035 end
	$body=getMergedDescription($body,$id,"Contacts");
	$body=getMergedDescription($body,$id,"Users");
	$res_array["subject"]=$subject;
	$res_array["body"]=$body;
	return $res_array;

}

//function used to get the header and body content of the mail to be sent.
function getcontent_week($id)
{
	global $adb,$table_prefix;
	//crmv@12035
    $query="select notificationbody from ".$table_prefix."_notifyscheduler where schedulednotificationid = ?";
    $res = $adb->pquery($query,Array(5));
    if ($res && $adb->num_rows($res) == 1){
    	$body = $adb->query_result($res,0,'notificationbody');
    	if (is_numeric($body)){
    		$query2 = "SELECT ".$table_prefix."_emailtemplates.subject,".$table_prefix."_emailtemplates.body FROM ".$table_prefix."_emailtemplates where ".$table_prefix."_emailtemplates.templateid = ?";
    		$result = $adb->pquery($query2,Array($body));
    		if ($result && $adb->num_rows($result) == 1){
    			$body = $adb->query_result_no_html($result,0,'body');
    			$subject = $adb->query_result($result,0,'subject');
    		}
    	}
    }
    //crmv@12035 end
	$body=getMergedDescription($body,$id,"Contacts");
	$body=getMergedDescription($body,$id,"Users");
	$res_array["subject"]=$subject;
	$res_array["body"]=$body;
	return $res_array;

}

?>