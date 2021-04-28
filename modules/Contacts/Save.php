<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("modules/Emails/mail.php");
$local_log =& LoggerManager::getLogger('index');

global $log,$adb,$currentModule;
global $table_prefix;
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

$focus = CRMEntity::getInstance('Contacts');
//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);

setObjectValuesFromRequest($focus);

if($_REQUEST['salutation'] == '--None--')	$_REQUEST['salutation'] = '';
if (!isset($_REQUEST['email_opt_out'])) $focus->email_opt_out = 'off';
if (!isset($_REQUEST['do_not_call'])) $focus->do_not_call = 'off';

//Checking If image is given or not
//$image_upload_array=SaveImage($_FILES,'contact',$focus->id,$focus->mode);
$image_name_val=$image_upload_array['imagename'];
$image_error="false";
$errormessage=$image_upload_array['errormessage'];
$saveimage=$image_upload_array['saveimage'];

//code added for returning back to the current view after edit from list view
if($_REQUEST['return_viewname'] == '') $return_viewname='0';
if($_REQUEST['return_viewname'] != '')$return_viewname=vtlib_purify($_REQUEST['return_viewname']);

if($image_error=="true") //If there is any error in the file upload then moving all the data to EditView.
{
        //re diverting the page and reassigning the same values as image error occurs
        if($_REQUEST['activity_mode'] != '')$activity_mode=vtlib_purify($_REQUEST['activity_mode']);
        if($_REQUEST['return_module'] != '')$return_module=vtlib_purify($_REQUEST['return_module']);
        if($_REQUEST['return_action'] != '')$return_action=vtlib_purify($_REQUEST['return_action']);
        if($_REQUEST['return_id'] != '')$return_id=vtlib_purify($_REQUEST['return_id']);

        $log->debug("There is an error during the upload of contact image.");
        $field_values_passed.="";
        foreach($focus->column_fields as $fieldname => $val)
        {
                if(isset($_REQUEST[$fieldname]))
                {
			$log->debug("Assigning the previous values given for the contact to respective vte_fields ");
                        $field_values_passed.="&";
                        $value = $_REQUEST[$fieldname];
                        $focus->column_fields[$fieldname] = $value;
                        $field_values_passed.=$fieldname."=".$value;

                }
        }
        $values_pass=$field_values_passed;
        $encode_field_values=base64_encode($values_pass);

        $error_module = "Contacts";
        $error_action = "EditView";

		$return_action .= '&activity_mode='.vtlib_purify($_request['activity_mode']);

        if($mode=="edit") {
			$return_id=vtlib_purify($_REQUEST['record']);
        }
        header("location: index.php?action=$error_action&module=$error_module&record=$return_id&return_id=$return_id&return_action=$return_action&return_module=$return_module&activity_mode=$activity_mode&return_viewname=$return_viewname".$search."&saveimage=$saveimage&error_msg=$errormessage&image_error=$image_error&encode_val=$encode_field_values");
}
if($saveimage=="true")
{
        $focus->column_fields['imagename']=$image_name_val;
        $log->debug("Assign the Image name to the vte_field name ");
}

//if image added then we have to set that $_FILES['name'] in imagename field then only the image will be displayed
if($_FILES['imagename']['name'] != '')
{
	if(isset($_REQUEST['imagename_hidden'])) {
		$focus->column_fields['imagename'] = vtlib_purify($_REQUEST['imagename_hidden']);
	} else {
		$focus->column_fields['imagename'] = $_FILES['imagename']['name'];
	}
}
elseif($focus->id != '')
{
	$result = $adb->pquery("select imagename from ".$table_prefix."_contactdetails where contactid = ?", array($focus->id));
	$focus->column_fields['imagename'] = $adb->query_result($result,0,'imagename');
}

if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
//Saving the contact
if($image_error=="false")
{
	$focus->save("Contacts");
	$return_id = $focus->id;

	if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = vtlib_purify($_REQUEST['return_module']);
	else $return_module = "Contacts";
	if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") $return_action = vtlib_purify($_REQUEST['return_action']);
	else $return_action = "DetailView";
	if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") $return_id = vtlib_purify($_REQUEST['return_id']);

	if(isset($_REQUEST['activity_mode']) && $_REQUEST['activity_mode'] != '') $activitymode = vtlib_purify($_REQUEST['activity_mode']);

	$local_log->debug("Saved record with id of ".$return_id);
	if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] == "Campaigns")
	{
		if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "")
		{
			$campContStatusResult = $adb->pquery("select campaignrelstatusid from ".$table_prefix."_campaigncontrel where campaignid=? AND contactid=?",array($_REQUEST['return_id'], $focus->id));
			$contactStatus = $adb->query_result($campContStatusResult,0,'campaignrelstatusid');
			$sql = "delete from ".$table_prefix."_campaigncontrel where contactid = ?";
			$adb->pquery($sql, array($focus->id));
			if(isset($contactStatus) && $contactStatus!=''){
				$sql = "insert into ".$table_prefix."_campaigncontrel values (?,?,?)";
				$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id,$contactStatus));
			}
			else
			{
				$sql = "insert into ".$table_prefix."_campaigncontrel values (?,?,1)";
				$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id));
			}
		}
	}
	
	// crmv@137993 - portal code moved to main class

	$log->info("This Page is redirected to : ".$return_module." / ".$return_action."& return id =".$return_id);

	//code added for returning back to the current view after edit from list view
	if($_REQUEST['return_viewname'] == '') $return_viewname='0';
	if($_REQUEST['return_viewname'] != '')$return_viewname=vtlib_purify($_REQUEST['return_viewname']);

	$parenttab = getParentTab();
	
	//crmv@54375
	if($_REQUEST['return2detail'] == 'yes') {
		$return_module = $currentModule;
		$return_action = 'DetailView';
		$return_id = $focus->id;
	}
	//crmv@54375e
	
	$url = "index.php?action=$return_action&module=$return_module&parenttab=$parenttab&record=$return_id&activity_mode=$activitymode&viewname=$return_viewname&start=".vtlib_purify($_REQUEST['pagenumber']);

	$from_module = vtlib_purify($_REQUEST['module']);
	if (!empty($from_module)) $url .= "&from_module=$from_module";

	header("Location: $url");
}
?>