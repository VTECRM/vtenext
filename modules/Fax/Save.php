<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@152701 */

require_once('modules/Fax/Fax.php');

//check for fax server configuration through ajax
if(isset($_REQUEST['server_check']) && $_REQUEST['server_check'] == 'true')
{
	//crmv@157490
	$serverConfigUtils = ServerConfigUtils::getInstance();
	if ($serverConfigUtils->checkConfiguration('fax'))
	//crmv@157490e
		echo 'SUCESS';
	else
		echo 'FAILURE';	
	die;	
}

global $adb, $table_prefix;
global $current_user,$mod_strings,$app_strings;

$focus = CRMEntity::getInstance('Fax');

if(isset($_REQUEST['description']) && $_REQUEST['description'] !='')
	$_REQUEST['description'] = fck_from_html($_REQUEST['description']);
setObjectValuesFromRequest($focus);

//Check if the file is exist or not.
//$file_name = '';
if(isset($_REQUEST['filename_hidden'])) {
	$file_name = $_REQUEST['filename_hidden'];
} else {
	$file_name = $_FILES['filename']['name'];
}
$errorCode =  $_FILES['filename']['error'];
$errormessage = "";
if($file_name != '' && $_FILES['filename']['size'] == 0)
{
	if($errorCode == 4 || $errorCode == 0)
	{
		 if($_FILES['filename']['size'] == 0)
			 $errormessage = "<B><font color='red'>".$mod_strings['LBL_PLEASE_ATTACH']."</font></B> <br>";
	}
	else if($errorCode == 2)
	{
		  $errormessage = "<B><font color='red'>".$mod_strings['LBL_EXCEED_MAX'].$upload_maxsize.$mod_strings['LBL_BYTES']." </font></B> <br>";
	}
	else if($errorCode == 6)
	{
	     $errormessage = "<B>".$mod_strings['LBL_KINDLY_UPLOAD']."</B> <br>" ;
	}
	else if($errorCode == 3 )
	{
	     if($_FILES['filename']['size'] == 0)
		     $errormessage = "<b><font color='red'>".$mod_strings['LBL_PLEASE_ATTACH']."</font></b><br>";
	}
	else{}
	if($errormessage != ""){
		$ret_error = 1;
		$ret_parentid = $_REQUEST['parent_id'];
		$ret_toadd = $_REQUEST['parent_name'];
		$ret_subject = $_REQUEST['subject'];
		$ret_description = $_REQUEST['description'];
		echo $errormessage;
        	include("EditView.php");	
		exit();
	}
}

if($_FILES["filename"]["size"] == 0 && $_FILES["filename"]["name"] != '')
{
        $file_upload_error = true;
        $_FILES = '';
}

function checkIfContactExists($faxid)
{
	global $log;
	global $table_prefix;
	$log->debug("Entering checkIfContactExists(".$faxid.") method ...");
	global $adb;
	$sql = "select contactid from ".$table_prefix."_contactdetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid where ".$table_prefix."_crmentity.deleted=0 and fax= ?";
	$result = $adb->pquery($sql, array($faxid));
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
$focus->filename = $_REQUEST['file_name'];
$focus->parent_id = $_REQUEST['parent_id'];
$focus->parent_type = $_REQUEST['parent_type'] ?: $_REQUEST['parent_module'];
$focus->column_fields["assigned_user_id"]=$current_user->id;
$focus->column_fields["activitytype"]="Fax";
$focus->column_fields["date_start"]= date(getNewDisplayDate());//This will be converted to db date format in save
$focus->save("Fax");

$fax_id = $focus->id;
// crmv@152701 - moved code

require_once("modules/Fax/fax_.php");

// send a fax to external receiver
if(isset($_REQUEST['send_fax']) && $_REQUEST['send_fax'] != '' && ($_REQUEST['parent_id'] != '' || $_REQUEST['to_fax'] != '' ) && $_REQUEST['check_to_fax'] == 'on') 
{
		$user_fax_status = send_fax('Fax',$current_user->column_fields['phone_fax'],$current_user->user_name,'',$_REQUEST['subject'],$_REQUEST['description'],$_REQUEST['ccfax'],$_REQUEST['bccfax'],'all',$focus->id);

//if block added to fix the issue #3759
	if($user_fax_status != 1){
		$query  = "select crmid,attachmentsid from ".$table_prefix."_seattachmentsrel where crmid=?";
		$result = $adb->pquery($query, array($fax_id));
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
			
		$query = "delete from ".$table_prefix."_faxdetails where faxid=?";	
		$adb->pquery($query, array($focus->id));
        	
		$error_msg = "<font color=red><strong>".$mod_strings['LBL_CHECK_USER_FAXID']."</strong></font>";
	        $ret_error = 1;
		$ret_parentid = $_REQUEST['parent_id'];
	        $ret_toadd = $_REQUEST['parent_name'];
        	$ret_subject = $_REQUEST['subject'];
	        $ret_ccaddress = $_REQUEST['ccfax'];
        	$ret_bccaddress = $_REQUEST['bccfax'];
	        $ret_description = $_REQUEST['description'];
	        
          //ds@6 send a fax to external receiver	        
          $ret_to_fax = $_REQUEST["to_fax"];
	        if(isset($_REQUEST["check_to_fax"]))
          $ret_check_to_fax = $_REQUEST["check_to_fax"];
          //ds@6e
          
        	echo $error_msg;
	        include("EditView.php");
        	exit();
	}

}

$focus->retrieve_entity_info($fax_id,"Fax");

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
	$return_module = "Fax";

if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") 
	$return_action = $_REQUEST['return_action'];
else 
	$return_action = "DetailView";

if(isset($_REQUEST['filename']) && $_REQUEST['filename'] != "") 
	$filename = $_REQUEST['filename'];

//ds@6 send a fax to external receiver
if(isset($_REQUEST['send_fax']) && $_REQUEST['send_fax'] != '' && ($_REQUEST['parent_id'] != '' || $_REQUEST['to_fax'] != '' ) && $_REQUEST['check_to_fax'] == 'on'){
//ds@6e
} elseif( isset($_REQUEST['send_fax']) && $_REQUEST['send_fax'])
	include("modules/Fax/faxsend.php");

if($_REQUEST['return_viewname'] == '') $return_viewname='0';
if($_REQUEST['return_viewname'] != '')$return_viewname=$_REQUEST['return_viewname'];
	
//crmv@24834
echo '<script language="JavaScript" type="text/javascript" src="include/js/general.js"></script>';
echo "<script>closePopup();</script>";
//crmv@24834e