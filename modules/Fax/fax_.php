<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**   Function used to send fax 
  *   $module 		-- current module 
  *   $to_fax 	-- to fax address 
  *   $from_name	-- currently loggedin user name
  *   $from_fax	-- currently loggedin vte_users's fax id. you can give as '' if you are not in HelpDesk module
  *   $subject		-- subject of the fax you want to send
  *   $contents		-- body of the fax you want to send
  *   $attachment	-- whether we want to attach the currently selected file or all vte_files.[values = current,all] - optional
  *   $faxid		-- id of the fax object which will be used to get the vte_attachments
  */
function send_fax($module,$to_fax,$from_name,$from_fax,$subject,$contents,$cc='',$bcc='',$attachment='',$faxid='',$logo='')
{

	global $adb, $log;
	global $root_directory;

	$adb->println("To id => '".$to_fax."'\nSubject ==>'".$subject."'\nContents ==> '".$contents."'");

	if($from_fax == '')
		$from_fax = getUserFaxId('user_name',$from_name);
	
	$fax = new VTEMailer(); // crmv@180739

	setFaxerProperties($fax,$subject,$contents,$from_fax,$from_name,trim($to_fax,","),$attachment,$faxid,$module,$logo);
	$fax_status = FaxSend($fax);
	if($fax_status != 1)
	{
		$fax_error = getFaxError($fax,$fax_status,$faxto);
	}
	else
	{
		$fax_error = $fax_status;
	}

	return $fax_error;
}

/**	Function to get the user Email id based on column name and column value
  *	$name -- column name of the vte_users vte_table
  *	$val  -- column value 
  */
function getUserFaxId($name,$val)
{
	global $adb;
	global $table_prefix;
	$adb->println("Inside the function getUserFaxId. --- ".$name." = '".$val."'");
	if($val != '')
	{
		$sql = "select phone_fax from ".$table_prefix."_users where $name = ?";
		$res = $adb->pquery($sql, array($val));
		$fax = $adb->query_result($res,0,'phone_fax');
		$adb->println("Fax id is selected  => '".$fax."'");
		if ($fax == '') return 'no_user_fax_specified';
		return $fax;
	}
	else
	{
		$adb->println("User id is empty. so return value is ''");
		return '';
	}
}

function set_fax_to(&$fax,$to){
	switch($fax->fax_server_type){
		case 'fax_mail': {
			$fax->addAddress($fax->Prefix.$to."@".$fax->Domain);
			break;
		}
		default :{
			$fax->addAddress($to);
			break;			
		}
	}
}

function set_fax_from(&$fax,$from){
	switch($fax->fax_server_type){
		case 'fax_mail': {
			return $fax->Account;
			break;
		}
		default :{
			return $from;
			break;			
		}
	}
}

/**	Function to set all the Faxer properties
  *	$fax 		-- reference of the mail object
  *	$subject	-- subject of the fax you want to send
  *	$contents	-- body of the fax you want to send
  *	$from_fax	-- from fax id which will be displayed in the mail
  *	$from_name	-- from name which will be displayed in the mail
  *	$to_fax 	-- to fax address  -- This can be an fax in a single string, a comma separated
  *			   list of faxs or an array of fax addresses
  *	$attachment	-- whether we want to attach the currently selected file or all vte_files.
  				[values = current,all] - optional
  *	$faxid	-- id of the fax object which will be used to get the vte_attachments - optional
  */
function setFaxerProperties($fax,$subject,$contents,$from_fax,$from_name,$to_fax,$attachment='',$faxid='',$module='',$logo='')
{
	global $adb;
	global $table_prefix;
	$adb->println("Inside the function setFaxerProperties");
	if($module == "Support" || $logo ==1)
		$fax->AddEmbeddedImage('themes/images/logo_mail.jpg', 'logo', 'logo.jpg',"base64","image/jpg");

	$fax->Subject = $subject;
	$fax->Body = $contents;
	//$fax->Body = html_entity_decode(nl2br($contents));	//if we get html tags in mail then we will use this line
	$fax->AltBody = strip_tags(preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$contents));

	$fax->IsSMTP();		//set mailer to use SMTP
	//$fax->Host = "smtp1.example.com;smtp2.example.com";  // specify main and backup server

	setFaxServerProperties($fax);	

	//Handle the from name and fax for HelpDesk
	$fax->From = set_fax_from($fax,$from_fax);
	$rs = $adb->pquery("select first_name,last_name from ".$table_prefix."_users where user_name=?", array($from_name));
	if($adb->num_rows($rs) > 0)
		$from_name = $adb->query_result($rs,0,"first_name")." ".$adb->query_result($rs,0,"last_name");

	$fax->FromName = decode_html($from_name);

	if($to_fax != '')
	{
		if(is_array($to_fax)) {
			for($j=0,$num=count($to_fax);$j<$num;$j++) {
				set_fax_to($fax,$to_fax[$j]);
//				$fax->addAddress($to_fax[$j]);
			}
		} else {
			$_tmp = explode(",",$to_fax);
			for($j=0,$num=count($_tmp);$j<$num;$j++) {
				set_fax_to($fax,$_tmp[$j]);
//				$fax->addAddress($_tmp[$j]);
			}
		}
	}

	$fax->AddReplyTo($fax->From);
	$fax->WordWrap = 50;

	//If we want to add the currently selected file only then we will use the following function
	if($attachment == 'current' && $faxid != '')
	{
		if (isset($_REQUEST['filename_hidden'])) {
			$file_name = $_REQUEST['filename_hidden'];
		} else {
			$file_name = $_FILES['filename']['name'];
		}
		addFaxAttachment($fax,$file_name,$faxid); // crmv@152701
	}

	//This will add all the vte_files which are related to this record or fax
	if($attachment == 'all' && $faxid != '')
	{
		addAllFaxAttachments($fax,$faxid); // crmv@152701
	}

	$fax->IsHTML(false);		// set fax format to HTML		//crmv@16703

	return;
}

/**	Function to set the Fax Server Properties in the object passed
  *	$fax -- reference of the mailobject
  */
function setFaxServerProperties($fax)
{
	global $adb;
	global $table_prefix;
	$adb->println("Inside the function setFaxServerProperties");
	//crmv@157490
	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfig = $serverConfigUtils->getConfiguration('fax');
	if(isset($_REQUEST['server']))
		$server = $_REQUEST['server'];
	else
		$server = $serverConfig['server'];
	if(isset($_REQUEST['server_username']))
		$username = $_REQUEST['server_username'];
	else
		$username = $serverConfig['server_username'];
	if(isset($_REQUEST['server_password']))
		$password = $_REQUEST['server_password'];
	else
		$password = $serverConfig['server_password']; 
 	if(isset($_REQUEST['service_type']))
		$fax_server_type = $_REQUEST['service_type'];
	else       	      	
		$fax_server_type = $serverConfig['service_type'];
	if ($fax_server_type == 'fax_mail'){
	 	if(isset($_REQUEST['adv_domain']))
			$domain = $_REQUEST['adv_domain'];
		else
			$domain = $serverConfig['domain'];
		if(isset($_REQUEST['adv_account']))
			$account = $_REQUEST['adv_account'];
		else
			$account = $serverConfig['account'];
		if(isset($_REQUEST['adv_prefix']))
			$prefix = $_REQUEST['adv_prefix'];
		else
			$prefix = $serverConfig['prefix'];
		if(isset($_REQUEST['adv_name']))
			$name = $_REQUEST['adv_name'];
		else
			$name = $serverConfig['name'];  
	}      	      	        	        	       	
	// Prasad: First time read smtp_auth from the request	
	if(isset($_REQUEST['smtp_auth']))
	{
		$smtp_auth = $_REQUEST['smtp_auth'];
		if($smtp_auth == 'on')	
			$smtp_auth = 'true';
	}
	else if (isset($_REQUEST['module']) && $_REQUEST['module'] == 'Settings' && (!isset($_REQUEST['smtp_auth'])))
	{
		//added to avoid issue while editing the values in the outgoing fax server.
		$smtp_auth = 'false';
	}
	else
		$smtp_auth = $serverConfig['smtp_auth'];
	//crmv@157490e
	$adb->println("Fax server name,username & password => '".$server."','".$username."','".$password."'");
	if($smtp_auth == "true"){
		$fax->SMTPAuth = true;	// turn on SMTP authentication
	}
	$fax->Host = $server;		// specify main and backup server
	$fax->Username = $username ;	// SMTP username
	$fax->Password = $password; // SMTP password
	$fax->fax_server_type = $fax_server_type;
	if ($fax_server_type == 'fax_mail') {
		$fax->Domain = $domain;
		$fax->Account = $account;
		$fax->Prefix = $prefix;
		$fax->Name = $name;
	}
	return;
}

/**	Function to add the file as attachment with the mail object
  *	$fax -- reference of the mail object
  *	$filename -- filename which is going to added with the mail
  *	$record -- id of the record - optional 
  */
function addFaxAttachment($fax,$filename,$record) // crmv@152701
{
	global $adb, $root_directory;
	$adb->println("Inside the function addAttachment");
	$adb->println("The file name is => '".$filename."'");

	//This is the file which has been selected in Email EditView
        if(is_file($filename) && $filename != '')
        {
                $fax->AddAttachment($root_directory."cache/upload/".$filename);
        }
}

/**     Function to add all the vte_files as attachment with the mail object
  *     $fax -- reference of the mail object
  *     $record -- fax id ie., record id which is used to get the all vte_attachments from database
  */
function addAllFaxAttachments($fax,$record) // crmv@152701
{
	global $adb,$log, $root_directory;
	global $table_prefix;
        $adb->println("Inside the function addAllAttachments");

	//Retrieve the vte_files from database where avoid the file which has been currently selected
	$sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_attachments.attachmentsid = ".$table_prefix."_seattachmentsrel.attachmentsid inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_seattachmentsrel.crmid=?";
	$res = $adb->pquery($sql, array($record));
	$count = $adb->num_rows($res);

	for($i=0;$i<$count;$i++)
	{
		$fileid = $adb->query_result($res,$i,'attachmentsid');
		$filename = decode_html($adb->query_result($res,$i,'name'));
		$filepath = $adb->query_result($res,$i,'path');
		$filewithpath = $root_directory.$filepath.$fileid."_".$filename;

		//if the file is exist in cache/upload directory then we will add directly
		//else get the contents of the file and write it as a file and then attach (this will occur when we unlink the file)
		if(is_file($filewithpath))
		{
			$fax->AddAttachment($filewithpath,$filename);
		}
	}
}

/**	Function to send the mail which will be called after set all the mail object values
  *	$fax -- reference of the mail object
  */
function FaxSend($fax)
{
	global $log;
         $log->info("Inside of Send Fax function.");
	if(!$fax->Send())
        {
		$log->debug("Error in Fax Sending : Error log = '".$fax->ErrorInfo."'");
		return $fax->ErrorInfo;
        }
	else 
	{
		 $log->info("Fax has been sent from the vteCRM system : Status : '".$fax->ErrorInfo."'");
		return 1;
	}
}


/**	Function to parse and get the mail error
  *	$fax -- reference of the mail object
  *	$fax_status -- status of the mail which is sent or not
  *	$to -- the email address to whom we sent the mail and failes
  *	return -- Fax error occured during the mail sending process
  */
function getFaxError($fax,$fax_status,$to)
{
	//Error types in class.phpmailer.php
	/*
	provide_address, mailer_not_supported, execute, instantiate, file_access, file_open, encoding, data_not_accepted, authenticate, 
	connect_host, recipients_failed, from_failed
	*/

	global $adb;
	$adb->println("Inside the function getFaxError");

	$msg = array_search($fax_status,$fax->language);
	$adb->println("Error message ==> ".$msg);

	if($msg == 'connect_host')
	{
		$error_msg =  $msg;
	}
	elseif(strstr($msg,'from_failed'))
	{
		$error_msg = $msg;
	}
	elseif(strstr($msg,'recipients_failed'))
	{
		$error_msg = $msg;
	}
	else
	{
		$adb->println("Fax error is not as connect_host or from_failed or recipients_failed");
		//$error_msg = $msg;
	}

	$adb->println("return error => ".$error_msg);
	return $error_msg;
}

/**	Function to get the fax status string (string of sent fax status)
  *	$fax_status_str -- concatenated string with all the error messages with &&& seperation
  *	return - the error status as a encoded string
  */
function getFaxErrorString($fax_status_str)
{
	global $adb;
	$adb->println("Inside getFaxErrorString function.\nFax status string ==> ".$fax_status_str);

	$fax_status_str = trim($fax_status_str,"&&&");
	$fax_status_array = explode("&&&",$fax_status_str);
	$adb->println("All Fax status ==>\n".$fax_status_str."\n");

	foreach($fax_status_array as $key => $val)
	{
		$list = explode("=",$val);
		$adb->println("Fax id & status ==> ".$list[0]." = ".$list[1]);
		if($list[1] == 0)
		{
			$fax_error_str .= $list[0]."=".$list[1]."&&&";
		}
	}
	$adb->println("Fax error string => '".$fax_error_str."'");
	if($fax_error_str != '')
	{
		$fax_error_str = 'fax_error='.base64_encode($fax_error_str);
	}
	return $fax_error_str;
}

/**	Function to parse the error string
  *	$fax_error_str -- base64 encoded string which contains the mail sending errors as concatenated with &&&
  *	return - Error message to display
  */
function parseFaxErrorString($fax_error_str)
{
	//TODO -- we can modify this function for better email error handling in future
	global $adb,$current_language;
	$mod_strings=return_specified_module_language($current_language,'Fax');
	$adb->println("Inside the parseFaxErrorString function.\n encoded fax error string ==> ".$fax_error_str);

	$fax_error = base64_decode($fax_error_str);
	$adb->println("Original error string => ".$fax_error);
	$fax_status = explode("&&&",trim($fax_error,"&&&"));
	foreach($fax_status as $key => $val)
	{
		$status_str = explode("=",$val);
		$adb->println('Fax id => "'.$status_str[0].'".........status => "'.$status_str[1].'"');
		if($status_str[1] != 1 && $status_str[1] != '')
		{
			$adb->println("Error in fax sending");
			if($status_str[1] == 'connect_host')
			{
				$adb->println("if part - Fax sever is not configured");
				$errorstr .= '<br><b><font color=red>'.$mod_strings['MESSAGE_CHECK_FAX_SERVER_NAME'].'</font></b>';
				break;
			}
			elseif($status_str[1] == '0')
			{
				$adb->println("first elseif part - status will be 0 which is the case of assigned to vte_users's fax is empty.");
				$errorstr .= '<br><b><font color=red> '.$mod_strings['MESSAGE_FAX_COULD_NOT_BE_SEND'].' '.$mod_strings['MESSAGE_PLEASE_CHECK_FROM_THE_FAXID'].'</font></b>';
			}
			elseif(strstr($status_str[1],'from_failed'))
			{
				$adb->println("second elseif part - from fax id is failed.");
				$from = explode('from_failed',$status_str[1]);
				$errorstr .= "<br><b><font color=red>".$mod_strings['MESSAGE_PLEASE_CHECK_THE_FROM_FAXID']." '".$from[1]."'</font></b>";
			}
			else
			{
				$adb->println("else part - fax send process failed due to the following reason.");
				$errorstr .= "<br><b><font color=red> ".$mod_strings['MESSAGE_FAX_COULD_NOT_BE_SEND_TO_THIS_FAXID']." '".$status_str[0]."'. ".$mod_strings['PLEASE_CHECK_THIS_FAXID']."</font></b>";	
			}
		}
	}
	$adb->println("Return Error string => ".$errorstr);
	return $errorstr;
}
?>