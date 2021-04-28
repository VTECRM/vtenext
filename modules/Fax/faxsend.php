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


$adb->println("\n\nFax Sending Process has been started.");
//This function call is used to send mail to the assigned to user. In this mail CC and BCC addresses will be added.
if($_REQUEST['assigntype' == 'T'] && $_REQUEST['assigned_group_id']!='')
{
	$grp_obj = new GetGroupUsers();
	$grp_obj->getAllUsersInGroup($_REQUEST['assigned_group_id']);
	$users_list = constructList($grp_obj->group_users,'INTEGER');
	if (count($users_list) > 0) {
		$sql = "select first_name, last_name, phone_fax from ".$table_prefix."_users where id in (". generateQuestionMarks($users_list) .")";
		$params = array($users_list);
	} else {
		$sql = "select first_name, last_name, phone_fax from ".$table_prefix."_users";
		$params = array();
	}
	$res = $adb->pquery($sql, $params);
	$user_email = '';
	while ($user_info = $adb->fetch_array($res))
	{
		$email = $user_info['phone_fax'];
		if($user_email=='')
			$user_email .= $user_info['first_name']." ".$user_info['last_name']."<".$email.">";
		else
			$user_email .= ",".$user_info['first_name']." ".$user_info['last_name']."<".$email.">";
			$email='';
	}
	$to_email = $user_email;
}
else
{
	$to_fax = getUserFaxId('id',$focus->column_fields["assigned_user_id"]);
}
if($to_fax == '')
{
	$adb->println("Fax Error : send_fax function not called because To fax id of assigned to user is empty");
	$fax_status_str = "'".$to_fax."'=0&&&";
	$errorheader1 = 1;
}
else
{
	$val=getUserFaxId('id',$focus->column_fields["assigned_user_id"]);

	$query = 'update '.$table_prefix.'_faxdetails set fax_flag ="SENT",from_number =? where faxid=?';
	$adb->pquery($query, array($val, $focus->id));
	//set the errorheader1 to 1 if the mail has not been sent to the assigned to user
	if($fax_status != 1)//when fax send fails
	{
		$errorheader1 = 1;
		$fax_status_str = $to_fax."=".$fax_status."&&&";
	}
	elseif($fax_status == 1 && $to_fax == '')
	{
		$fax_status_str = $to_fax."=".$fax_status."&&&";
	}
}


$parentid= $_REQUEST['parent_id'];
$myids=explode("|",$parentid);
$all_to_faxids = Array();
$from_name = $current_user->user_name;
$from_address = $current_user->column_fields['phone_fax'];

//ds@6 send a fax to external receiver
if(isset($_REQUEST["to_fax"]) && $_REQUEST["to_fax"]!="" && isset($_REQUEST["check_to_fax"])){
  $Exploded_Fax = explode(";",$_REQUEST["to_fax"]);
  // DS-UP MaJu 6.3.2008 added foreach 
  foreach($Exploded_Fax as $faxadd){
    send_fax('Fax',$faxadd,$from_name,$from_address,$_REQUEST['subject'],$_REQUEST['description'],'','','all',$focus->id);
  }
}
//ds@6e

for ($i=0;$i<(count($myids)-1);$i++)
{
	$realid=explode("@",$myids[$i]);
	$nfax=count($realid);
	$mycrmid=$realid[0];
	if($realid[1] == -1)
        {
                //handle the mail send to vte_users
                $faxadd = $adb->query_result($adb->pquery("select phone_fax from ".$table_prefix."_users where id=?", array($mycrmid)),0,'phone_fax');
                $pmodule = 'Users';
		$description = getMergedDescription($_REQUEST['description'],$mycrmid,$pmodule);
                $fax_status=send_fax('Fax',$faxadd,$from_name,$from_address,$_REQUEST['subject'],$_REQUEST['description'],'','','all',$focus->id);
                $all_to_faxids []= $faxadd;
                $fax_status_str .= $faxadd."=".$fax_status."&&&";
        }
        else
        {
		//Send mail to vte_account or lead or contact based on their ids
		$pmodule=getSalesEntityType($mycrmid);
		for ($j=1;$j<$nfax;$j++)
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
			$faxadd=br2nl($myfocus->column_fields[$fldname]);

//This is to convert the html encoded string to original html entities so that in mail description contents will be displayed correctly
	//$focus->column_fields['description'] = from_html($focus->column_fields['description']);

			if($faxadd != '')
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
					$fax_status=send_fax('Fax',$faxadd,$from_name,$from_address,$_REQUEST['subject'],$description,'','','all',$focus->id,$logo);
				}	

				$all_to_faxids []= $faxadd;
				$fax_status_str .= $faxadd."=".$fax_status."&&&";
				//added to get remain the EditView page if an error occurs in mail sending
				if($fax_status != 1)
				{
					$errorheader2 = 1;
				}
			}
		}
	}	

}
//Added to redirect the page to Fax/EditView if there is an error in fax sending
if($errorheader1 == 1 || $errorheader2 == 1)
{
	$returnset = 'return_module='.$returnmodule.'&return_action='.$returnaction.'&return_id='.$_REQUEST['return_id'];
	$returnmodule = 'Fax';
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
	global $adb;
	$date_var = date('Ymd');
	$query = 'update '.$table_prefix.'_activity set date_start =? where activityid = ?';
	$adb->pquery($query, array($date_var, $returnid));
}
//The following function call is used to parse and form a encoded error message and then pass to result page
$fax_error_str = getFaxErrorString($fax_status_str);
$adb->println("Fax Sending Process has been finished.\n\n");
if(isset($_REQUEST['popupaction']) && $_REQUEST['popupaction'] != '')
{
	//crmv@24834
	echo '<script language="JavaScript" type="text/javascript" src="include/js/general.js"></script>';
	echo "<script>closePopup();</script>";
	//crmv@24834e
}
//header("Location:index.php?module=$returnmodule&action=$returnaction&record=$returnid&$returnset&$mail_error_str");

?>