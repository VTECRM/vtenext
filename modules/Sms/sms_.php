<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**   Function used to send sms 
  *   $module 		-- current module 
  *   $to_sms 	-- to sms address 
  *   $from_name	-- currently loggedin user name
  *   $from_sms	-- currently loggedin vte_users's sms id. you can give as '' if you are not in HelpDesk module
  *   $subject		-- subject of the sms you want to send
  *   $contents		-- body of the sms you want to send
  *   $attachment	-- whether we want to attach the currently selected file or all vte_files.[values = current,all] - optional
  *   $smsid		-- id of the sms object which will be used to get the vte_attachments
  */
function send_sms($module,$to_sms,$from_name,$from_sms,$subject,$contents,$cc='',$bcc='',$attachment='',$smsid='',$logo='')
{

	global $adb, $log;
	global $root_directory;

	$adb->println("To id => '".$to_sms."'\nSubject ==>'".$subject."'\nContents ==> '".$contents."'");

	if($from_sms == '')
		$from_sms = getUserSmsId('user_name',$from_name);
	
	$sms = new VTEMailer(); // crmv@180739

	setSmserProperties($sms,$subject,$contents,$from_sms,$from_name,trim($to_sms,","),$attachment,$smsid,$module,$logo);

	$sms_status = SmsSend($sms);
	
	if($sms_status != 1)
	{
		$sms_error = getSmsError($sms,$sms_status,$smsto);
	}
	else
	{
		$sms_error = $sms_status;
	}

	return $sms_error;
}

/**	Function to get the user Email id based on column name and column value
  *	$name -- column name of the vte_users vte_table
  *	$val  -- column value 
  */
function getUserSmsId($name,$val)
{
	global $adb,$table_prefix;
	$adb->println("Inside the function getUserSmsId. --- ".$name." = '".$val."'");
	if($val != '')
	{
		$sql = "select phone_mobile from ".$table_prefix."_users where $name = ?";
		$res = $adb->pquery($sql, array($val));
		$sms = $adb->query_result($res,0,'phone_mobile');
		$adb->println("Sms id is selected  => '".$sms."'");
		if ($sms == '') return 'no_user_sms_specified';
		return $sms;
	}
	else
	{
		$adb->println("User id is empty. so return value is ''");
		return '';
	}
}

function set_sms_to($sms,$to){
	switch($sms->sms_server_type){
		case 'sms_mail': {
			$sms->addAddress($sms->Prefix.$to."@".$sms->Domain);
			break;
		}
		default :{
			$sms->addAddress($to);
			break;			
		}
	}
}

function set_sms_from($sms,$from){
	switch($sms->sms_server_type){
		case 'sms_mail': {
			return $sms->Account;
			break;
		}
		default :{
			return $from;
			break;			
		}
	}
}

/**	Function to set all the Smser properties
  *	$sms 		-- reference of the mail object
  *	$subject	-- subject of the sms you want to send
  *	$contents	-- body of the sms you want to send
  *	$from_sms	-- from sms id which will be displayed in the mail
  *	$from_name	-- from name which will be displayed in the mail
  *	$to_sms 	-- to sms address  -- This can be an sms in a single string, a comma separated
  *			   list of smss or an array of sms addresses
  *	$attachment	-- whether we want to attach the currently selected file or all vte_files.
  				[values = current,all] - optional
  *	$smsid	-- id of the sms object which will be used to get the vte_attachments - optional
  */
function setSmserProperties($sms,$subject,$contents,$from_sms,$from_name,$to_sms,$attachment='',$smsid='',$module='',$logo='')
{
	global $adb,$table_prefix;
	$adb->println("Inside the function setSmserProperties");
	if($module == "Support" || $logo ==1)
		$sms->AddEmbeddedImage('themes/images/logo_mail.jpg', 'logo', 'logo.jpg',"base64","image/jpg");

	$sms->Subject = $subject;
	$sms->Body = $contents;
	//$sms->Body = html_entity_decode(nl2br($contents));	//if we get html tags in mail then we will use this line
//	$sms->AltBody = strip_tags(preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$contents));

	$sms->IsSMTP();		//set mailer to use SMTP
	//$sms->Host = "smtp1.example.com;smtp2.example.com";  // specify main and backup server

	setSmsServerProperties($sms);	

	//Handle the from name and sms for HelpDesk
	$sms->From = set_sms_from($sms,$from_sms);
	$rs = $adb->pquery("select first_name,last_name from ".$table_prefix."_users where user_name=?", array($from_name));
	if($adb->num_rows($rs) > 0)
		$from_name = $adb->query_result($rs,0,"first_name")." ".$adb->query_result($rs,0,"last_name");

	$sms->FromName = decode_html($from_name);

	if($to_sms != '')
	{
		if(is_array($to_sms)) {
			for($j=0,$num=count($to_sms);$j<$num;$j++) {
				set_sms_to($sms,$to_sms[$j]);
//				$sms->addAddress($to_sms[$j]);
			}
		} else {
			$_tmp = explode(",",$to_sms);
			for($j=0,$num=count($_tmp);$j<$num;$j++) {
				set_sms_to($sms,$_tmp[$j]);
//				$sms->addAddress($_tmp[$j]);
			}
		}
	}
	
	//crmv@16703
	$sms->CharSet='ISO-8859-15';
	$sms->Encoding='7bit';
	//crmv@16703e

	$sms->AddReplyTo($sms->From);
	$sms->WordWrap = 50;

	$sms->IsHTML(false);	//crmv@16703

	return;
}

/**	Function to set the Sms Server Properties in the object passed
  *	$sms -- reference of the mailobject
  */
//crmv@157490
function setSmsServerProperties($sms)
{
	global $adb,$table_prefix;
	$adb->println("Inside the function setSmsServerProperties");
	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfig = $serverConfigUtils->getConfiguration('sms');
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
		$sms_server_type = $_REQUEST['service_type'];
	else       	      	
		$sms_server_type = $serverConfig['service_type'];
	if ($sms_server_type == 'sms_mail'){        	
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
		//added to avoid issue while editing the values in the outgoing sms server.
		$smtp_auth = 'false';
	}
	else
		$smtp_auth = $serverConfig['smtp_auth'];

	$adb->println("Sms server name,username & password => '".$server."','".$username."','".$password."'");
	if($smtp_auth == "true"){
		$sms->SMTPAuth = true;	// turn on SMTP authentication
	}
	$sms->Host = $server;		// specify main and backup server
	$sms->Username = $username ;	// SMTP username
	$sms->Password = $password ;	// SMTP password
	$sms->sms_server_type = $sms_server_type;
	if ($sms_server_type == 'sms_mail'){
		$sms->Domain = $domain ;
		$sms->Account = $account ; 
		$sms->Prefix = $prefix ;
		$sms->Name = $name ;     
	}
	return;
}
//crmv@157490e

/**	Function to send the mail which will be called after set all the mail object values
  *	$sms -- reference of the mail object
  */
function SmsSend($sms)
{
	global $log;
         $log->info("Inside of Send Sms function.");
	if(!$sms->Send())
        {
		$log->debug("Error in Sms Sending : Error log = '".$sms->ErrorInfo."'");
		return $sms->ErrorInfo;
        }
	else 
	{
		 $log->info("Sms has been sent from the vteCRM system : Status : '".$sms->ErrorInfo."'");
		return 1;
	}
}


/**	Function to parse and get the mail error
  *	$sms -- reference of the mail object
  *	$sms_status -- status of the mail which is sent or not
  *	$to -- the email address to whom we sent the mail and failes
  *	return -- Sms error occured during the mail sending process
  */
function getSmsError($sms,$sms_status,$to)
{
	//Error types in class.phpmailer.php
	/*
	provide_address, mailer_not_supported, execute, instantiate, file_access, file_open, encoding, data_not_accepted, authenticate, 
	connect_host, recipients_failed, from_failed
	*/

	global $adb;
	$adb->println("Inside the function getSmsError");

	$msg = array_search($sms_status,$sms->language);
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
		$adb->println("Sms error is not as connect_host or from_failed or recipients_failed");
		//$error_msg = $msg;
	}

	$adb->println("return error => ".$error_msg);
	return $error_msg;
}

/**	Function to get the sms status string (string of sent sms status)
  *	$sms_status_str -- concatenated string with all the error messages with &&& seperation
  *	return - the error status as a encoded string
  */
function getSmsErrorString($sms_status_str)
{
	global $adb;
	$adb->println("Inside getSmsErrorString function.\nSms status string ==> ".$sms_status_str);

	$sms_status_str = trim($sms_status_str,"&&&");
	$sms_status_array = explode("&&&",$sms_status_str);
	$adb->println("All Sms status ==>\n".$sms_status_str."\n");

	foreach($sms_status_array as $key => $val)
	{
		$list = explode("=",$val);
		$adb->println("Sms id & status ==> ".$list[0]." = ".$list[1]);
		if($list[1] == 0)
		{
			$sms_error_str .= $list[0]."=".$list[1]."&&&";
		}
	}
	$adb->println("Sms error string => '".$sms_error_str."'");
	if($sms_error_str != '')
	{
		$sms_error_str = 'sms_error='.base64_encode($sms_error_str);
	}
	return $sms_error_str;
}

/**	Function to parse the error string
  *	$sms_error_str -- base64 encoded string which contains the mail sending errors as concatenated with &&&
  *	return - Error message to display
  */
function parseSmsErrorString($sms_error_str)
{
	//TODO -- we can modify this function for better sms error handling in future
	global $adb, $current_language;
	$mod_strings = return_specified_module_language($current_language,'Sms');
	$adb->println("Inside the parseSmsErrorString function.\n encoded sms error string ==> ".$sms_error_str);

	$sms_error = base64_decode($sms_error_str);
	$adb->println("Original error string => ".$sms_error);
	$sms_status = explode("&&&",trim($sms_error,"&&&"));
	foreach($sms_status as $key => $val)
	{
		$status_str = explode("=",$val);
		$adb->println('Sms id => "'.$status_str[0].'".........status => "'.$status_str[1].'"');
		if($status_str[1] != 1 && $status_str[1] != '')
		{
			$adb->println("Error in mail sending");
			if($status_str[1] == 'connect_host')
			{
				$adb->println("if part - Sms sever is not configured");
				$errorstr .= '<br><b><font color=red>'.$mod_strings['MESSAGE_CHECK_SMS_SERVER_NAME'].'</font></b>';
				break;
			}
			elseif($status_str[1] == '0')
			{
				$adb->println("first elseif part - status will be 0 which is the case of assigned to vte_users's sms is empty.");
				$errorstr .= '<br><b><font color=red> '.$mod_strings['MESSAGE_SMS_COULD_NOT_BE_SEND'].' '.$mod_strings['MESSAGE_PLEASE_CHECK_FROM_THE_SMSID'].'</font></b>';
			}
			elseif(strstr($status_str[1],'from_failed'))
			{
				$adb->println("second elseif part - from sms id is failed.");
				$from = explode('from_failed',$status_str[1]);
				$errorstr .= "<br><b><font color=red>".$mod_strings['MESSAGE_PLEASE_CHECK_THE_FROM_SMSID']." '".$from[1]."'</font></b>";
			}
			else
			{
				$adb->println("else part - sms send process failed due to the following reason.");
				$errorstr .= "<br><b><font color=red> ".$mod_strings['MESSAGE_SMS_COULD_NOT_BE_SEND_TO_THIS_SMSID']." '".$status_str[0]."'. ".$mod_strings['PLEASE_CHECK_THIS_SMSID']."</font></b>";	
			}
		}
	}
	$adb->println("Return Error string => ".$errorstr);
	return $errorstr;
}
?>