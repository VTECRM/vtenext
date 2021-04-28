<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


function getComboList($name, $value, $defaultval='', $selectval='')
{
	$list = '<select name="'.$name.'" class="form-control">';

	//Add the default value as a first option
	if($defaultval != '')
		$list .= '<OPTION value="'.$defaultval.'">'.$defaultval.'</OPTION>';

	foreach($value as $index => $val)
	{
		$selected = '';
		if($selectval == $val)
			$selected = ' selected ';
		$list .= '<OPTION value="'.$val.'" '.$selected.'>'.getTranslatedString($val).'</OPTION>'; // crmv@81291
	}
	$list .= '</select>';

	return $list;
}

function UpdateComment()
{
	global $client,$Server_Path;
	$ticketid = $_REQUEST['ticketid'];
	$ownerid = $_SESSION['customer_id'];
	$comments = $_REQUEST['comments'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid",'ownerid'=>"$customerid",'comments'=>"$comments"));

	$commentresult = $client->call('update_ticket_comment', $params, $Server_Path, $Server_Path);

}

// crmv@160733
function provideConfidentialInfo() {
	global $client,$Server_Path;
	
	$ticketid = $_REQUEST['ticketid'];
	$data = $_REQUEST['confinfo_data'];
	$comment = $_REQUEST['confinfo_data_comment'];
	$request_commentid = $_REQUEST['confinfo_commentid'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid",'ownerid'=>"$customerid",'data'=>"$data",'comment'=>"$comment", 'request_commentid'=>"$request_commentid"));

	$commentresult = $client->call('provide_confidential_info', $params, $Server_Path, $Server_Path);
}
// crmv@160733e

function Close_Ticket($ticketid)
{
	global $client,$Server_Path;
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid"));

	$result = $client->call('close_current_ticket', $params, $Server_Path, $Server_Path);
	return $result;
}

function getPicklist($picklist_name)
{
	
	// Static cache to re-use information
	static $_picklist_cache = array();	
	if(isset($_picklist_cache[$picklist_name])) {
		return $_picklist_cache[$picklist_name];
	}
	
	global $client,$Server_Path;
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'picklist_name'=>"$picklist_name"));
	$ticket_picklist_array = $client->call('get_picklists', $params, $Server_Path, $Server_Path);
	
	// Save the result for re-use
	$_picklist_cache[$picklist_name] = $ticket_picklist_array;

	return $ticket_picklist_array;
}

function getStatusComboList($selectedvalue='')
{
	$temp_array = getPicklist('ticketstatus');

	$status_combo = "<option value=''>".getTranslatedString('LBL_ALL')."</option>";
	foreach($temp_array as $index => $val)
	{
		$select = '';
		if($val == $selectedvalue)
			$select = ' selected';

		$status_combo .= '<option value="'.$val.'"'.$select.'>'.getTranslatedString($val).'</option>';
	}

	return $status_combo;
}

//Added for My Settings - Save Password
function SavePassword($version)
{
	global $client;
	
	$customer_id = $_SESSION['customer_id'];
	$customer_name = $_SESSION['customer_name'];
	$oldpw = trim($_REQUEST['old_password']);
	$newpw = trim($_REQUEST['new_password']);
	$confirmpw = trim($_REQUEST['confirm_password']);

	$params = Array('user_name'=>"$customer_name",'user_password'=>"$oldpw",'version'=>"$version",'login'=>'false');
	$result = $client->call('authenticate_user',$params);
	$sessionid = $_SESSION['customer_sessionid'];
	if($oldpw == $result[0]['user_password'])
	{
		if(strcasecmp($newpw,$confirmpw) == 0)
		{
			$customerid = $result[0]['id'];
						
		//	$customerid = $_SESSION['customer_id'];
			$sessionid = $_SESSION['customer_sessionid'];

			$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'username'=>"$customer_name",'password'=>"$newpw",'version'=>"$version"));

			$result_change_password = $client->call('change_password',$params);
			if($result_change_password[0] == 'MORE_THAN_ONE_USER'){
				$errormsg .= getTranslatedString('MORE_THAN_ONE_USER');
			}else{
				$errormsg .= getTranslatedString('MSG_PASSWORD_CHANGED');
			}
		}
		else
		{
			$errormsg .= getTranslatedString('MSG_ENTER_NEW_PASSWORDS_SAME');
		}
	}elseif($result[0] == 'INVALID_USERNAME_OR_PASSWORD') {
		$errormsg .= getTranslatedString('LBL_ENTER_VALID_USER');	
	}elseif($result[0] == 'MORE_THAN_ONE_USER'){
		$errormsg .= getTranslatedString('MORE_THAN_ONE_USER');
	}
	else
	{
		$errormsg .= getTranslatedString('MSG_YOUR_PASSWORD_WRONG');
	}

	return $errormsg;
}

function getTicketAttachmentsList($ticketid)
{
	global $client;
	
	$customer_name = $_SESSION['customer_name'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$params = Array(Array('id'=>"$customerid", 'sessionid'=>"$sessionid", 'ticketid'=>"$ticketid"));
	$result = $client->call('get_ticket_attachments',$params);

	return $result;
}

// crmv@173153
function AddAttachment($ticketid) {
	global $client, $Server_Path, $upload_dir;
	
	$upload_error = '';
	
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];
	$customerFile = $_FILES['customerfile'];
	
	if (!empty($customerFile)) {
		$fileKeys = array_keys($customerFile);
		$fileCount = count($customerFile['name']);
		
		$files = array();
		
		for ($i = 0; $i < $fileCount; $i++) {
			foreach ($fileKeys as $key) {
				$files[$i][$key] = $customerFile[$key][$i];
			}
		}
		
		foreach ($files as $file) {
			$filename = $file['name'];
			$filetype = $file['type'];
			$filesize = $file['size'];
			$fileerror = $file['error'];
			$tmpname = $file['tmp_name'];
			
			if ($fileerror == 4) {
				$upload_error = getTranslatedString('LBL_GIVE_VALID_FILE');
				break;
			} elseif ($fileerror == 2) {
				$upload_error = getTranslatedString('LBL_UPLOAD_FILE_LARGE');
				break;
			} elseif ($fileerror == 3) {
				$upload_error = getTranslatedString('LBL_PROBLEM_UPLOAD');
				break;
			}
			
			if (!is_dir($upload_dir)) {
				$upload_error = getTranslatedString('LBL_NOTSET_UPLOAD_DIR');
				break;
			}
			
			if ($filesize > 0) {
				$filecontents = '';
				if (move_uploaded_file($tmpname, $upload_dir . '/' . $filename)) {
					$filecontents = base64_encode(fread(fopen($upload_dir . '/' . $filename, "r"), $filesize));
				}
				if ($filecontents != '') {
					$params = array(Array('id' => "$customerid", 'sessionid' => "$sessionid", 'ticketid' => "$ticketid", 'filename' => "$filename", 'filetype' => "$filetype", 'filesize' => "$filesize", 'filecontents' => "$filecontents"));
					$client->call('add_ticket_attachment', $params, $Server_Path, $Server_Path);
				} else {
					$upload_error = getTranslatedString('LBL_FILE_HAS_NO_CONTENTS');
					break;
				}
			} else {
				$upload_error = getTranslatedString('LBL_UPLOAD_VALID_FILE');
				break;
			}
			
			if (!empty($upload_error)) return $upload_error;
		}
	}
	
	return $upload_error;
}
// crmv@173153e

function getTicketSearchQuery() {
	global $table_prefix;

	if(trim($_REQUEST['search_ticketid']) != '')
	{
		$where .= $table_prefix."_troubletickets.ticket_no = '".addslashes($_REQUEST['search_ticketid'])."'&&&";	// crmv@153703
	}
	if(trim($_REQUEST['search_title']) != '')
	{
		//$where .= $table_prefix."_troubletickets.title = '".$_REQUEST['search_title']."'&&&";
		$where .= $table_prefix."_troubletickets.title like '%".addslashes(trim($_REQUEST['search_title']))."%'&&&";
	}

	if(trim($_REQUEST['search_ticketstatus']) != '')
	{
		$where .= $table_prefix."_troubletickets.status = '".$_REQUEST['search_ticketstatus']."'&&&";
	}
	if(trim($_REQUEST['search_ticketpriority']) != '')
	{
		$where .= $table_prefix."_troubletickets.priority = '".$_REQUEST['search_ticketpriority']."'&&&";
	}
	if(trim($_REQUEST['search_ticketcategory']) != '')
	{
		$where .= $table_prefix."_troubletickets.category = '".$_REQUEST['search_ticketcategory']."'&&&";
	}
	$where = trim($where,'&&&');
	return $where;
}
?>