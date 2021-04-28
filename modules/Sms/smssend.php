<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
global $current_user;
global $table_prefix;
//die(PRINT_R($_REQUEST));
//set the return module and return action and set the return id based on return module and record
$returnmodule = $_REQUEST['return_module'];
$returnaction = $_REQUEST['return_action'];
if((($returnmodule != 'Emails') || ($returnmodule == 'Emails' && $_REQUEST['record'] == '')) && $_REQUEST['return_id'] != '')
{
	$returnid = $_REQUEST['return_id'];
}
else
{
	$returnid = $focus->id;//$_REQUEST['record'];
}


$adb->println("\n\nSms Sending Process has been started.");
//This function call is used to send mail to the assigned to user. In this mail CC and BCC addresses will be added.
if($focus->column_fields["assigned_user_id"]==0 && $_REQUEST['assigned_group_name']!='')
{
	//todo!"!!
//	$grp_obj = new GetGroupUsers();
//	$grp_obj->getAllUsersInGroup(getGrpId($_REQUEST['assigned_group_name']));
//	$users_list = constructList($grp_obj->group_users,'INTEGER');
//	if (count($users_list) > 0) {
//		$sql = "select first_name, last_name, email1, email2, yahoo_id from vte_users where id in (". generateQuestionMarks($users_list) .")";
//		$params = array($users_list);
//	} else {
//		$sql = "select first_name, last_name, email1, email2, yahoo_id from vte_users";
//		$params = array();
//	}
//	$res = $adb->pquery($sql, $params);
//	$user_email = '';
//	while ($user_info = $adb->fetch_array($res))
//	{
//		$email = $user_info['email1'];
//		if($email == '' || $email == 'NULL')
//		{
//			$email = $user_info['email2'];
//			if($email == '' || $email == 'NULL')
//			{
//				$email = $user_info['yahoo_id'];
//			}
//		}	
//		if($user_email=='')
//		$user_email .= $user_info['first_name']." ".$user_info['last_name']."<".$email.">";
//		else
//		$user_email .= ",".$user_info['first_name']." ".$user_info['last_name']."<".$email.">";
//		$email='';
//	}
//	$to_email = $user_email;
}
else
{
	$to_sms = getUserSmsId('id',$focus->column_fields["assigned_user_id"]);
}
if($to_sms == '')
{
	$adb->println("Sms Error : send_sms function not called because To sms id of assigned to user is empty");
	$sms_status_str = "'".$to_sms."'=0&&&";
	$errorheader1 = 1;
}
else
{
	$val=getUserSmsId('id',$focus->column_fields["assigned_user_id"]);

	$query = 'update '.$table_prefix.'_smsdetails set sms_flag ="SENT",from_number =? where smsid=?';
	$adb->pquery($query, array($val, $focus->id));
	//set the errorheader1 to 1 if the mail has not been sent to the assigned to user
	if($sms_status != 1)//when sms send fails
	{
		$errorheader1 = 1;
		$sms_status_str = $to_sms."=".$sms_status."&&&";
	}
	elseif($sms_status == 1 && $to_sms == '')
	{
		$sms_status_str = $to_sms."=".$sms_status."&&&";
	}
}

$parentid= $_REQUEST['parent_id'];
$myids=explode("|",$parentid);
$all_to_smsids = Array();
$from_name = $current_user->user_name;
$from_address = $current_user->column_fields['phone_mobile'];

//ds@6 send a sms to external receiver
if(isset($_REQUEST["to_sms"]) && $_REQUEST["to_sms"]!="" && isset($_REQUEST["check_to_sms"])){
  $Exploded_Sms = explode(";",$_REQUEST["to_sms"]);
  // DS-UP MaJu 6.3.2008 added foreach 
  foreach($Exploded_Sms as $smsadd){
    send_sms('Sms',$smsadd,$from_name,$from_address,$_REQUEST['subject'],$_REQUEST['description'],'','','all',$focus->id);
  }
}
//ds@6e

for ($i=0;$i<(count($myids)-1);$i++)
{
	$realid=explode("@",$myids[$i]);
	$nsms=count($realid);
	$mycrmid=$realid[0];
	if($realid[1] == -1)
        {
                //handle the sms send to vte_users
                $smsadd = $adb->query_result($adb->pquery("select phone_mobile from ".$table_prefix."_users where id=?", array($mycrmid)),0,'phone_mobile');
                $pmodule = 'Users';
		$description = getMergedDescription($_REQUEST['description'],$mycrmid,$pmodule);
                $sms_status=send_sms('Sms',$smsadd,$from_name,$from_address,$_REQUEST['subject'],$_REQUEST['description'],'','','all',$focus->id);
                $all_to_smsids []= $smsadd;
                $sms_status_str .= $smsadd."=".$sms_status."&&&";
        }
        else
        {
		//Send sms to vte_account or lead or contact based on their ids
		$pmodule=getSalesEntityType($mycrmid);
		for ($j=1;$j<$nsms;$j++)
		{
			$temp=$realid[$j];
			$myquery='Select columnname from '.$table_prefix.'_field where fieldid=?';
			$fresult=$adb->pquery($myquery, array($temp));			
			if ($pmodule=='Contacts')
			{
				$myfocus = CRMEntity::getInstance('Contacts');
				$myfocus->retrieve_entity_info($mycrmid,"Contacts");
			}
			elseif ($pmodule=='Accounts')
			{
				$myfocus = CRMEntity::getInstance('Accounts');
				$myfocus->retrieve_entity_info($mycrmid,"Accounts");
			} 
			elseif ($pmodule=='Leads')
			{
				$myfocus = CRMEntity::getInstance('Leads');
				$myfocus->retrieve_entity_info($mycrmid,"Leads");
			}
			elseif ($pmodule=='Vendors')
                        {
                                $myfocus = CRMEntity::getInstance('Vendors');
                                $myfocus->retrieve_entity_info($mycrmid,"Vendors");
                        }
			$fldname=$adb->query_result($fresult,0,"columnname");
			$smsadd=br2nl($myfocus->column_fields[$fldname]);

//This is to convert the html encoded string to original html entities so that in mail description contents will be displayed correctly
	//$focus->column_fields['description'] = from_html($focus->column_fields['description']);

			if($smsadd != '')
			{
				$description = getMergedDescription($_REQUEST['description'],$mycrmid,$pmodule);
				$pos = strpos($description, '$logo$');
				if ($pos !== false)
				{

					$description =str_replace('$logo$','<img src="cid:logo" />',$description);
					$logo=1;
				}
				if(isPermitted($pmodule,'DetailView',$mycrmid) == 'yes')
				{
					$sms_status=send_sms('Sms',$smsadd,$from_name,$from_address,$_REQUEST['subject'],$description,'','','all',$focus->id,$logo);
				}	

				$all_to_smsids []= $smsadd;
				$sms_status_str .= $smsadd."=".$sms_status."&&&";
				//added to get remain the EditView page if an error occurs in mail sending
				if($sms_status != 1)
				{
					$errorheader2 = 1;
				}
			}
		}
	}	

}

//Added to redirect the page to Sms/EditView if there is an error in sms sending
if($errorheader1 == 1 || $errorheader2 == 1)
{
	$returnset = 'return_module='.$returnmodule.'&return_action='.$returnaction.'&return_id='.$_REQUEST['return_id'];
	$returnmodule = 'Sms';
	$returnaction = 'EditView';
	//This condition is added to set the record(email) id when we click on send mail button after returning mail error
	if($_REQUEST['mode'] == 'edit')
	{
		$returnid = $_REQUEST['record'];
	}
	else
	{
		$returnid = $_REQUEST['currentid'];
	}
}
else
{
	global $adb,$table_prefix;
	$date_var = date('Ymd');
	$query = 'update '.$table_prefix.'_activity set date_start =? where activityid = ?';
	$adb->pquery($query, array($date_var, $returnid));
}
//The following function call is used to parse and form a encoded error message and then pass to result page
$sms_error_str = getSmsErrorString($sms_status_str);
$adb->println("Sms Sending Process has been finished.\n\n");