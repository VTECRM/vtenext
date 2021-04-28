<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 //check for sms server configuration through ajax
global $table_prefix;
if(isset($_REQUEST['server_check']) && $_REQUEST['server_check'] == 'true')
{
	//crmv@157490
	$serverConfigUtils = ServerConfigUtils::getInstance();
	if ($serverConfigUtils->checkConfiguration('sms'))
	//crmv@157490e
		echo 'SUCESS';
	else
		echo 'FAILURE';	
	die;	
}

require_once('modules/Sms/Sms.php');

$local_log =& LoggerManager::getLogger('index');

$focus = CRMEntity::getInstance('Sms');

global $current_user,$mod_strings,$app_strings;
if(isset($_REQUEST['description']) && $_REQUEST['description'] !='')
	$_REQUEST['description'] = fck_from_html($_REQUEST['description']);
setObjectValuesFromRequest($focus);

function checkIfContactExists($smsid)
{
	global $log;
	$log->debug("Entering checkIfContactExists(".$smsid.") method ...");
	global $adb,$table_prefix;
	$sql = "select contactid from ".$table_prefix."_contactdetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid where ".$table_prefix."_crmentity.deleted=0 and sms= ?";
	$result = $adb->pquery($sql, array($smsid));
	$numRows = $adb->num_rows($result);
	if($numRows > 0)
	{
		$log->debug("Exiting checkIfContactExists method ...");
		return $adb->query_result($result,0,"contactid");
	}
	else
	{
		$log->debug("Exiting checkIfContactExists method ...");
		return -1;
	}
}

//assign the focus values
$focus->parent_id = $_REQUEST['parent_id'];
$focus->parent_type = $_REQUEST['parent_type'] ?: $_REQUEST['parent_module']; // crmv@152701
$focus->column_fields["assigned_user_id"]=$current_user->id;
$focus->column_fields["activitytype"]="Sms";
$focus->column_fields["date_start"]= date(getNewDisplayDate());//This will be converted to db date format in save
$focus->save("Sms");

// crmv@152701 - removed code
$sms_id = $focus->id;

require_once("modules/Sms/sms_.php");

// send a sms to external receiver
if(isset($_REQUEST['send_sms']) && $_REQUEST['send_sms'] != '' && ($_REQUEST['parent_id'] != '' || $_REQUEST['to_sms'] != '' ) && $_REQUEST['check_to_sms'] == 'on') 
{
		$user_sms_status = send_sms('Sms',$current_user->column_fields['phone_mobile'],$current_user->user_name,'',$_REQUEST['subject'],$_REQUEST['description'],$_REQUEST['ccsms'],$_REQUEST['bccsms'],'all',$focus->id);

//if block added to fix the issue #3759
	if($user_sms_status != 1){
		$query  = "select crmid,attachmentsid from ".$table_prefix."_seattachmentsrel where crmid=?";
		$result = $adb->pquery($query, array($sms_id));
		$numOfRows = $adb->num_rows($result);
		for($i=0; $i<$numOfRows; $i++)
		{
			$attachmentsid = $adb->query_result($result,0,"attachmentsid");		
			if($attachmentsid > 0)
			{	
				$query1="delete from ".$table_prefix."_crmentity where crmid=?";
			 	$adb->pquery($query1, array($attachmentsid));
			}

			$crmid=$adb->query_result($result,0,"crmid");
			$query2="delete from ".$table_prefix."_crmentity where crmid=?";
			$adb->pquery($query2, array($crmid));
		}
			
		$query = "delete from ".$table_prefix."_smsdetails where smsid=?";	
		$adb->pquery($query, array($focus->id));
        	
		$error_msg = "<font color=red><strong>".$mod_strings['LBL_CHECK_USER_SMSID']."</strong></font>";
        $ret_error = 1;
		$ret_parentid = $_REQUEST['parent_id'];
        $ret_toadd = $_REQUEST['parent_name'];
        $ret_subject = $_REQUEST['subject'];
        $ret_ccaddress = $_REQUEST['ccsms'];
        $ret_bccaddress = $_REQUEST['bccsms'];
        $ret_description = $_REQUEST['description'];
	        
		//ds@6 send a sms to external receiver	        
		$ret_to_sms = $_REQUEST["to_sms"];
		if(isset($_REQUEST["check_to_sms"]))
			$ret_check_to_sms = $_REQUEST["check_to_sms"];
		//ds@6e
          
        echo $error_msg;
        include("EditView.php");
        exit();
	}

}

// crmv@152701 - removed line

//this is to receive the data from the Select Users button
if($_REQUEST['source_module'] == null)
{
	$module = 'users';
}
//this will be the case if the Select Contact button is chosen
else
{
	$module = $_REQUEST['source_module'];
}

if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") 
	$return_module = $_REQUEST['return_module'];
else 
	$return_module = "Sms";

if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") 
	$return_action = $_REQUEST['return_action'];
else 
	$return_action = "DetailView";

if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") 
	$return_id = $_REQUEST['return_id'];

if(isset($_REQUEST['filename']) && $_REQUEST['filename'] != "") 
	$filename = $_REQUEST['filename'];

$local_log->debug("Saved record with id of ".$return_id);

//ds@6 send a sms to external receiver
if(isset($_REQUEST['send_sms']) && $_REQUEST['send_sms'] != '' && ($_REQUEST['parent_id'] != '' || $_REQUEST['to_sms'] != '' ) && $_REQUEST['check_to_sms'] == 'on'){
//ds@6e
} elseif( isset($_REQUEST['send_sms']) && $_REQUEST['send_sms'])
	include("modules/Sms/smssend.php");

if($_REQUEST['return_viewname'] == '') $return_viewname='0';
if($_REQUEST['return_viewname'] != '')$return_viewname=$_REQUEST['return_viewname'];
//Added for 4600

die;
?>