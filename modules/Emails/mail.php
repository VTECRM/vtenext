<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@2963m */

/**   Function used to send email
  *   $module 		-- current module
  *   $to_email 	-- to email address
  *   $from_name	-- currently loggedin user name
  *   $from_email	-- currently loggedin vte_users's email id. you can give as '' if you are not in HelpDesk module
  *   $subject		-- subject of the email you want to send
  *   $contents		-- body of the email you want to send
  *   $cc		-- add email ids with comma seperated. - optional
  *   $bcc		-- add email ids with comma seperated. - optional.
  *   $attachment	-- whether we want to attach the currently selected file or all vte_files.[values = current,all] - optional
  *   $emailid		-- id of the email object which will be used to get the vte_attachments
  */
function send_mail($module,$to_email,$from_name,$from_email,$subject,$contents,$cc='',$bcc='',$attachment='',$emailid='',$logo='',$newsletter_params='',&$mail_tmp='',$messageid='',$message_mode='',$queue=false, $sendAfter = null) //crmv@22700 crmv@25351 crmv@129149 crmv@131904
{
	global $adb, $log, $root_directory, $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME, $send_mail_ow;
	
	// crmv@197876
	if (isset($send_mail_ow['to_email'])) $to_email = $send_mail_ow['to_email'];
	if (isset($send_mail_ow['cc'])) $cc = $send_mail_ow['cc'];
	if (isset($send_mail_ow['bcc'])) $bcc = $send_mail_ow['bcc'];
	if (isset($send_mail_ow['subject_prefix'])) $subject = trim($send_mail_ow['subject_prefix']).' '.$subject;
	// crmv@197876e

	// crmv@129149
	$VTEP = VTEProperties::getInstance();
	$focusEmails = CRMentity::getInstance('Emails');
	if ($queue && $VTEP->getProperty('modules.emails.send_mail_queue')) {
		$focusEmails->add2SendNotQueue($to_email, $from_name, $from_email, $subject,
			array(
				'module'=>$module,
				'contents'=>$contents,
				'cc'=>$cc,
				'bcc'=>$bcc,
				'attachment'=>$attachment,
				'emailid'=>$emailid,
				'logo'=>$logo,
				'newsletter_params'=>$newsletter_params,
				//&$mail_tmp,
				'messageid'=>$messageid,
				'message_mode'=>$message_mode
			),
			$sendAfter	// crmv@131904
		);
		return 1;
	}
	// crmv@129149e
	
	$adb->println("To id => '".$to_email."'\nSubject ==>'".$subject."'\nContents ==> '".$contents."'");

	//Get the email id of assigned_to user -- pass the value and name, name must be "user_name" or "id"(field names of vte_users vte_table)
	//$to_email = getUserEmailId('id',$assigned_user_id);

	//if module is HelpDesk then from_email will come based on support email id
	if($from_email == '')//$module != 'HelpDesk')
		$from_email = getUserEmailId('user_name',$from_name);

	$mail = new VTEMailer(); // crmv@180739
	setMailerProperties($mail,$subject,$contents,$from_email,$from_name,$to_email,$attachment,$emailid,$module,$logo);

	//crmv@34245
	if($newsletter_params && $newsletter_params['smtp_config']['enable'] === true) {
		if($newsletter_params['smtp_config']['smtp_auth'] == "true"){
			$mail->SMTPAuth = true;	// turn on SMTP authentication
		} else {
			$mail->SMTPAuth = false;	// turn off SMTP authentication
		}
		$mail->Host = $newsletter_params['smtp_config']['server'];		// specify main and backup server
		$mail->Username = $newsletter_params['smtp_config']['server_username'] ;	// SMTP username
        $mail->Password = $newsletter_params['smtp_config']['server_password'] ;	// SMTP password
        // crmv@114260
        if ($newsletter_params['smtp_config']['server_port'] > 0) {
			$mail->Port = $newsletter_params['smtp_config']['server_port'];
        }
        // crmv@114260e
	}
	//crmv@34245e

	if (method_exists($focusEmails, 'overwriteMailConfiguration')) $focusEmails->overwriteMailConfiguration($mail); //crmv@80029

	setCCAddress($mail,'cc',$cc);
	setCCAddress($mail,'bcc',$bcc);

	// vtmailscanner customization: If Support Reply to is defined use it.
	global $HELPDESK_SUPPORT_EMAIL_REPLY_ID;
	if($HELPDESK_SUPPORT_EMAIL_REPLY_ID && $HELPDESK_SUPPORT_EMAIL_ID != $HELPDESK_SUPPORT_EMAIL_REPLY_ID) {
		$mail->addReplyTo($HELPDESK_SUPPORT_EMAIL_REPLY_ID); // crmv@200330
	}
	// END

	// Fix: Return immediately if Outgoing server not configured
    if(empty($mail->Host)) {
		return 0;
    }
    // END

    //crmv@22700
    if ($newsletter_params) {
    	if ($newsletter_params['sender'] != '') {
			$mail->Sender = $newsletter_params['sender'];
			$mail->addCustomHeader("Errors-To: ".$newsletter_params['sender']);
    	}
    	if ($newsletter_params['newsletterid'] != '') {
			$mail->addCustomHeader("X-MessageID: ".$newsletter_params['newsletterid']);
    	}
    	if ($newsletter_params['crmid'] != '') {
			$mail->addCustomHeader("X-ListMember: ".$newsletter_params['crmid']);
    	}
    	// crmv@151474
    	if ($newsletter_params['reply_to'] != '') {
			$mail->ClearReplyTos();
			// crmv@200330
			$addresses = getAddresses($newsletter_params['reply_to']);
			if (!empty($addresses)) {
				foreach($addresses as $address) {
					$mail->addReplyTo($address[0],$address[1]);
				}
			}
			// crmv@200330e
    	}
    	// crmv@151474e
    	$mail->addCustomHeader("Precedence: bulk");
    }
    //crmv@22700e

    if (!empty($messageid) && in_array($message_mode,array('reply','reply_all','forward'))) {
    	$focusMessage = CRMentity::getInstance('Messages');
    	$result = $focusMessage->retrieve_entity_info_no_html($messageid,'Messages',false);
    	if (empty($result)) {	// no errors
	    	$mail->addCustomHeader("In-Reply-To: ".$focusMessage->column_fields['messageid']);
	    	$mail->addCustomHeader("References: ".preg_replace('/\s+/',' ',$focusMessage->column_fields['mreferences']).' '.$focusMessage->column_fields['messageid']);	//crmv@131260
			//TODO: $mail->addCustomHeader("Thread-Index: ");
    	}
    }
    
    //crmv@174550
    if ($newsletter_params && $newsletter_params['debug'] === true) {
    	$_SMTPDebug_copy = $mail->SMTPDebug;
    	$mail->SMTPDebug = true;
    	ob_start();
    }
    //crmv@174550e

    $mail_status = MailSend($mail);
    
    //crmv@174550
    if ($newsletter_params && $newsletter_params['debug'] === true) {
    	$debug = ob_get_contents();
    	ob_end_clean();
    	$mail->SMTPDebug = $_SMTPDebug_copy;
    	if ($mail_status != 1) echo "Error for newsletterid:{$newsletter_params['newsletterid']}, crmid:{$newsletter_params['crmid']}, to_email:{$to_email}. SMTPDebug:\n".$debug;
    }
    //crmv@174550e

	if($mail_status == 1) {
		$mail_tmp = $mail;
	} else {
		$error_string ='Send mail failed! from '.$from_email.' to '.$to_email.' subject '.$subject.' reason:'.$mail_status;
		$log->fatal($error_string);
	}
	$mail_error = $mail_status;
	return $mail_error;
}

/**	Function to get the user Email id based on column name and column value
  *	$name -- column name of the vte_users vte_table
  *	$val  -- column value
  */
function getUserEmailId($name,$val)
{
	global $adb,$table_prefix;
	$adb->println("Inside the function getUserEmailId. --- ".$name." = '".$val."'");
	if($val != '')
	{
		//$sql = "select email1, email2, yahoo_id from vte_users where ".$name." = '".$val."'";
		//done to resolve the PHP5 specific behaviour
		$sql = "SELECT email1, email2, yahoo_id from ".$table_prefix."_users WHERE status='Active' AND ". $adb->sql_escape_string($name)." = ?";
		$res = $adb->pquery($sql, array($val));
		$email = $adb->query_result($res,0,'email1');
		if($email == '')
		{
			$email = $adb->query_result($res,0,'email2');
			if($email == '')
			{
				$email = $adb->query_result($res,0,'yahoo_id');
			}
		}
		$adb->println("Email id is selected  => '".$email."'");
		return $email;
	}
	else
	{
		$adb->println("User id is empty. so return value is ''");
		return '';
	}
}

//crmv@26807
function getContactsEmailId($contactid)
{
	global $adb,$table_prefix;
	$email = '';
	if($contactid != '') {
		$sql = "SELECT email,yahooid FROM ".$table_prefix."_contactdetails WHERE contactid = ?";
		$res = $adb->pquery($sql, array($contactid));
		$email = $adb->query_result($res,0,'email');
		if($email == '') {
			$email = $adb->query_result($res,0,'yahooid');
		}
		return $email;
	}
	else {
		return $email;
	}
}
//crmv@26807e

//crmv@61023	crmv@106075
function getCompanyLogoInformation(){
	global $adb,$table_prefix;
	$default_folder = "storage/logo/";
	$sql = "SELECT logoname FROM {$table_prefix}_organizationdetails";
	$res = $adb->pquery($sql,array());
	if($res && $adb->num_rows($res) > 0){
		$logoname = $adb->query_result($res,0,'logoname');
		(exif_imagetype($default_folder.$logoname) == IMAGETYPE_PNG) ? $default_filetype = "image/png" : $default_filetype = "image/jpeg";
		$fullpath = $default_folder.$logoname;
		if(file_exists($fullpath)){
			return array('fullpath'=>$fullpath,'logoname'=>$logoname,'filetype'=>$default_filetype);
		}
	}
	return false;
}
//crmv@61023e	crmv@106075e

/**	Function to set all the Mailer properties
  *	$mail 		-- reference of the mail object
  *	$subject	-- subject of the email you want to send
  *	$contents	-- body of the email you want to send
  *	$from_email	-- from email id which will be displayed in the mail
  *	$from_name	-- from name which will be displayed in the mail
  *	$to_email 	-- to email address  -- This can be an email in a single string, a comma separated
  *			   list of emails or an array of email addresses
  *	$attachment	-- whether we want to attach the currently selected file or all vte_files.
  				[values = current,all] - optional
  *	$emailid	-- id of the email object which will be used to get the vte_attachments - optional
  */
function setMailerProperties(&$mail,$subject,$contents,$from_email,$from_name,$to_email,$attachment='',$emailid='',$module='',$logo='')	//crmv@86304
{
	global $adb,$table_prefix;
	$adb->println("Inside the function setMailerProperties");
	if(($module == "Support" || $logo ==1) && strpos($contents,'cid:logo') !== false) { //crmv@61892
		//crmv@61023
		$logo_information = getCompanyLogoInformation();
		if(is_array($logo_information)){
			$mail->addEmbeddedImage($logo_information['fullpath'], 'logo', $logo_information['logoname'],"base64",$logo_information['filetype']);
		}
		else{
			$mail->addEmbeddedImage('storage/logo/logo.gif', 'logo', 'logo.gif',"base64","image/gif");	//crmv@20774
		}
		//crmv@61023e
	}

	$mail->Subject = $subject;
	//crmv@22700
	if (is_array($contents)) {
		$mail->Body = $contents['html'];
		$mail->AltBody = $contents['text'];
	} else {
		$mail->Body = $contents;
		//$mail->Body = html_entity_decode(nl2br($contents));	//if we get html tags in mail then we will use this line
		$mail->AltBody = strip_tags(preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$contents));
	}
	//crmv@22700e

	HandleInlineAttachments($mail,$_REQUEST['uploaddir']); //crmv@81704

	$mail->IsSMTP();		//set mailer to use SMTP
	//$mail->Host = "smtp1.example.com;smtp2.example.com";  // specify main and backup server

	setMailServerProperties($mail, $from_email);	//crmv@46572

	//Handle the from name and email for HelpDesk
	$mail->From = $from_email;
	$rs = $adb->pquery("select first_name,last_name from ".$table_prefix."_users where user_name=?", array($from_name));
	if($adb->num_rows($rs) > 0)
		$from_name = $adb->query_result($rs,0,"first_name")." ".$adb->query_result($rs,0,"last_name");

	$mail->FromName = decode_html($from_name);

	if($to_email != '')
	{
		if(is_array($to_email)) {
			foreach($to_email as $e) {
				// crmv@198780
				$addresses = getAddresses($e);
				if (!empty($addresses)) {
					foreach($addresses as $address) {
						$mail->addAddress($address[0],$address[1]);
					}
				}
				// crmv@198780e
			}
		} else {
			// crmv@198780
			$addresses = getAddresses($to_email);
			if (!empty($addresses)) {
				foreach($addresses as $address) {
					$mail->addAddress($address[0],$address[1]);
				}
			}
			// crmv@198780e
		}
	}

	$mail->addReplyTo($from_email); // crmv@200330
	$mail->WordWrap = 50;

	// crmv@68357 - advanced attachments
	if (is_array($attachment)) {
		addAdvancedAttachments($mail, $attachment);
		$attachment = '';
	}
	// crmv@68357e

	// crmv@187130
	if($attachment == 'current') {
		// If we want to add the currently selected file only then we will use the following function
		if (isset($_REQUEST['filename_hidden'])) {
			$file_name = $_REQUEST['filename_hidden'];
		} else {
			$file_name = $_FILES['filename']['name'];
		}
		addAttachment($mail,$file_name);
	} elseif($attachment == 'some') {
		if (!empty($emailid) && isset($_REQUEST['attach_contentids'])) {
			$contlist = array_filter(explode(',', $_REQUEST['attach_contentids']), function($v) {
				return $v !== "" && $v >= 0;
			});
			if (count($contlist) > 0) {
				addSomeAttachments($mail,$emailid, $contlist);
			}
		}
	} elseif($attachment == 'all') {
		// This will add all the files which are related to this record or email
		if (!empty($emailid)) {
			addAllAttachments($mail,$emailid);
		}
	}
	if (!empty($_REQUEST['uploaddir'])) {
		puploadAttachments($mail,$_REQUEST['uploaddir']);
	}
	if (!empty($_REQUEST['pdf_attachment']) && is_array($_REQUEST['pdf_attachment'])) {
		foreach ($_REQUEST['pdf_attachment'] as $att_pdf) {
			//crmv@193042
			$doc_id = GetDocumentIdByFileName($att_pdf);
			if($doc_id != null && $doc_id > 0 && isPermitted('Documents', 'DetailView', $doc_id) == 'yes')
				$mail->AddAttachment($root_directory . 'storage/' . $att_pdf);
			//crmv@193042e
		}
	} elseif (!empty($_REQUEST['pdf_attachment'])) {
		//crmv@193042
		$doc_id = GetDocumentIdByFileName($_REQUEST['pdf_attachment']);
		if($doc_id != null && $doc_id > 0 && isPermitted('Documents', 'DetailView', $doc_id) == 'yes')
			$mail->AddAttachment($root_directory . 'storage/' . $_REQUEST['pdf_attachment']);
		//crmv@193042e
	}
	if ($module == 'MorphsuitServer' && $attachment != '') {
		$mail->AddAttachment($attachment);
	}
	// crmv@187130e

	$mail->IsHTML(true);		// set email format to HTML

	return;
}

// crmv@193042
/**
 * Getting id of document by attachment id from file name
 * @param $file
 */
function GetDocumentIdByFileName($file) {
	$parts = explode('-', $file);
	$fileclass = FileStorage::getInstance();
	//crmv@204903
	$docid = $fileclass->getParentId($parts[0]);
	if($docid == null || $docid <= 0)
	{
		$parts = explode('/', $file);
		$parts = $parts[count($parts)-1];
		$parts = explode('_', $parts);
		$docid = $parts[0];
	}
	return $docid;
	//crmv@204903e
}
// crmv@193042e

/**	Function to set the Mail Server Properties in the object passed
  *	$mail -- reference of the mailobject
  */
//crmv@157490
function setMailServerProperties($mail, $from_email)	//crmv@46572
{
	global $adb;
	$adb->println("Inside the function setMailServerProperties");

	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfig = $serverConfigUtils->getConfiguration('email');
	if(isset($_REQUEST['server']))
		$server = $_REQUEST['server'];
	else
		$server = $serverConfig['server'];
	if(isset($_REQUEST['server_username']))
		$username = $_REQUEST['server_username'];
	else
		$username = $serverConfig['server_username'];
	if(isset($_REQUEST['server_password'])) {
		$password = $_REQUEST['server_password'];
		if (!empty($serverConfig['id']) && $password == '') $password = $serverConfigUtils->getConfiguration($serverConfig['id'], array('server_password'), 'id', true);	//crmv@43764
	}
	else
		$password = $serverConfig['server_password']; //crmv@20785
	// Prasad: First time read smtp_auth from the request
	//crmv@32079
	if(isset($_REQUEST['smtp_auth'])) {
		$smtp_auth = $_REQUEST['smtp_auth'];
		if($smtp_auth == 'on') {
			$smtp_auth = 'true';
		}
	} else if (isset($_REQUEST['module']) && $_REQUEST['module'] == 'Settings' && $_REQUEST['action'] == 'Save' && $_REQUEST['server_type'] == 'email' && (!isset($_REQUEST['smtp_auth']))) {
		//added to avoid issue while editing the values in the outgoing mail server.
		$smtp_auth = 'false';
	} else {
		$smtp_auth = $serverConfig['smtp_auth'];
	}
	if(isset($_REQUEST['port']))
		$port = $_REQUEST['port'];
	else
		$port = $serverConfig['server_port'];
	$adb->println("Mail server name,username & password => '".$server."','".$username."','".$password."'");
	if($smtp_auth == "true") {
		$mail->SMTPAuth = true;	// turn on SMTP authentication
	}
	$mail->Host = $server;		// specify main and backup server
	$mail->Hostname = $server;		// server name used in Message-Id
	$mail->Username = $username ;	// SMTP username
	$mail->Password = $password ;	// SMTP password
	if (isset($port) && $port != '' && $port != 0) {
		$mail->Port = $port;
	}
	//crmv@32079e
	//crmv@46572
	(isset($_REQUEST['account_smtp'])) ? $account = $_REQUEST['account_smtp'] : $account = $serverConfig['account'];	// smtp account type
	//crmv@94605
	$parsed_host = parse_url($server);
	if(isset($parsed_host['host']) && !empty($parsed_host['host'])){
		$mail->Helo = $parsed_host['host'];
	}
	//crmv@94605e
	if ($account == 'Gmail' && $username != $from_email) {	// if (smtp account type is Gmail) and (smtp username !=  )
		$emailsFocus = CRMEntity::getInstance('Emails');
		$accountid = $emailsFocus->getFromEmailAccount($from_email);
		$emailsFocus = CRMEntity::getInstance('Messages');
		$accountinfo = $emailsFocus->getUserAccounts('',$accountid);
		if (!empty($accountinfo)) {
			$accountinfo = $accountinfo[0];
			if ($accountinfo['account'] == 'Gmail' || strpos($accountinfo['server'],'gmail') !== false) {	// if imap account type is Gmail //crmv@61262
				$mail->Username = $accountinfo['username'];
				$mail->Password = $accountinfo['password'];
			}
		}
	}
	//crmv@46572e
	// crmv@206145
	if ($account == 'Office365' && $username != $from_email) {
		$emailsFocus = CRMEntity::getInstance('Emails');
		$accountid = $emailsFocus->getFromEmailAccount($from_email);
		$emailsFocus = CRMEntity::getInstance('Messages');
		$accountinfo = $emailsFocus->getUserAccounts('',$accountid);
		if (!empty($accountinfo)) {
			$accountinfo = $accountinfo[0];
			if ($accountinfo['account'] == 'Office365' || strpos($accountinfo['server'],'office365') !== false) {
				$mail->Username = $accountinfo['username'];
				$mail->Password = $accountinfo['password'];
			}
		}
	}
	// crmv@206145e
	return;
}
//crmv@157490e

// crmv@68357 - advanced attachments
// crmv@71388 - support for original email attachments
/**
 * Add one or more attachments from different sources and different formats
 * $attachment is an array of arrays of the form:
 * array(
 *   'sourcetype' => 'file', 'string' or 'email', mandatory
 *	 'content' => path/to/file, string or contentid(s), depending on sourcetype, mandatory
 *   'recordid' => crmid of the record to use to retrieve the attachments, mandatory when sourcetype = 'email'
 *	 'filename' => '...', mandatory if sourcetype = 'string', the filename of the attachment
 *   'encoding' => defaults to base64, optional
 *   'contenttype' => defaults to application/octet-stream, optional
 *   'altbody' => true/false, if true, this is not an attachment, but part of the multipart/alternatives
 *   'charset' => '...', charset to use, when altbody = true,
 *   'method' => '...', method to use, when altbody = true
 * )
 */
function addAdvancedAttachments(&$mail, $attachments = array()) {	//crmv@86304
	foreach ($attachments as $att) {
		if ($att['sourcetype'] == 'email') {
			$contentids = $att['content'];
			if (!is_array($contentids)) $contentids = array($contentids);
			if (count($contentids) > 0) {
				addSomeAttachments($mail, $att['recordid'], $contentids);
			}
		} elseif ($att['sourcetype'] == 'file') {
			$mail->AddAttachment($att['content'], $att['filename'], $att['encoding'] ?: "base64", $att['contenttype'] ?: "application/octet-stream");
		} elseif ($att['sourcetype'] == 'string') {
			if ($att['altbody']) {
				// crmv@180739
				if ($att['contenttype'] == 'text/calendar') {
					$mail->Ical = $att['content'];
					$mail->IcalMethod = $att['method'] ?: 'REQUEST';
				} else {
					// not supported!
				}
				// crmv@180739e
			} else {
				$mail->addStringAttachment($att['content'], $att['filename'], $att['encoding'] ?: "base64", $att['contenttype'] ?: "application/octet-stream");
			}
		}
	}
}
// crmv@71388e
// crmv@68357e

/**	Function to add the file as attachment with the mail object
  *	$mail -- reference of the mail object
  *	$filename -- filename which is going to added with the mail
  */
function addAttachment(&$mail,$filename)	//crmv@86304
{
	global $adb, $root_directory;
	$adb->println("Inside the function addAttachment");
	$adb->println("The file name is => '".$filename."'");

	//This is the file which has been selected in Email EditView
	if(is_file($filename) && $filename != '')
	{
		$mail->AddAttachment($filename); // crmv@39106
	}
}

/*
 * Add only the attachments specified in $contentids array
*/
function addSomeAttachments(&$mail,$record, $contentids) {	//crmv@86304
	if (!is_array($contentids)) $contentids = array($contentids);
	return addAllAttachments($mail, $record, $contentids);
}


/**     Function to add all the files as attachment with the mail object
  *     $mail -- reference of the mail object
  *     $record -- email id ie., record id which is used to get the all vte_attachments from database
  *     $onlythese -- if not null, only these contentids will be attached
  */
function addAllAttachments(&$mail,$record, $onlythese = null)	//crmv@86304
{
	global $adb,$log,$root_directory,$table_prefix,$current_user;
	$adb->println("Inside the function addAllAttachments");

	if (getSalesEntityType($record) == 'Messages') {
		$focusMessages = CRMEntity::getInstance('Messages');
		$focusMessages->id = $record;		
		$result = $focusMessages->retrieve_entity_info($record,'Messages',false);
    	if (empty($result)) {	// no errors
			$atts = $focusMessages->getAttachmentsInfo();
			if (!empty($atts)) {
				//crmv@47673	crmv@65328
				if ($focusMessages->column_fields['mtype'] == 'Link') {
					$sql = "select t.* from {$table_prefix}_messages_attach a 
					inner join {$table_prefix}_seattachmentsrel s on s.crmid = a.document
					inner join {$table_prefix}_notes n on n.notesid = a.document
					inner join {$table_prefix}_attachments t on t.attachmentsid = s.attachmentsid
					inner join {$table_prefix}_crmentity e on e.crmid = t.attachmentsid
					where messagesid = ? and coalesce(a.document,'') <> '' and e.deleted=0";
					$params = Array($record);
					$res = $adb->pquery($sql,$params);
					if ($res && $adb->num_rows($res)>0) {
						while($row=$adb->fetchByAssoc($res)) {
							$filename = $row['name'];
							$filewithpath = $root_directory.$row['path'].$row['attachmentsid']."_".$filename;
							if (is_file($filewithpath)) {
								$mail->AddAttachment($filewithpath,$filename);
							}
						}
					}
				//crmv@47673e	crmv@65328e
				} else {
					//$resetMailResource = false;
					//if (!empty($focusMessages->column_fields['assigned_user_id']) && $focusMessages->column_fields['assigned_user_id'] != $current_user->id) {
						// Shared folder (reset mail resource and connect to mailbox of the original mail)
						$focusMessages->setAccount($focusMessages->column_fields['account']);	//crmv@47673
						$focusMessages->getZendMailStorageImap($focusMessages->column_fields['assigned_user_id']);
					//	$resetMailResource = true;
					//} else {
					//	$focusMessages->getZendMailStorageImap();
					//}
					$focusMessages->selectFolder($focusMessages->column_fields['folder']);
					//crmv@57484
					try {
						$messageId = $focusMessages->getMailResource()->getNumberByUniqueId($focusMessages->column_fields['xuid']);
					} catch(Exception $e) {
						if ($e->getMessage() == 'unique id not found') {
							return;
						}
					}
					//crmv@57484e
					$message = $focusMessages->getMailResource()->getMessage($messageId);
					$data = $focusMessages->getMessageContentParts($message,$messageId,true);	//crmv@59492
					foreach($atts as $contentid => $att) {
						if (empty($data['other'][$contentid])) {
							continue;
						}
						if (!is_null($onlythese) && is_array($onlythese) && !in_array($contentid, $onlythese)) continue;
						$att = $att['parameters'];
						$str = $focusMessages->decodeAttachment($data['other'][$contentid]['content'],$att['encoding'],$att['charset']);
						AddStringAttachment($mail,$str,$att['name'],$att['encoding'],$att['contenttype'],$att['contentdisposition'],$att['content_id']);
					}
					/* crmv@47673
					if ($resetMailResource) {	// Recover mail resource to current user
						$focusMessages->resetMailResource();
						$focusMessages->getZendMailStorageImap($current_user->id);
					}*/
				}
			}
		}
	} else {
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
				$mail->AddAttachment($filewithpath,$filename);
			}
		}
	}
}

function AddStringAttachment($mail, $string, $filename, $encoding = "base64", $type = "application/octet-stream", $contentdisposition = "", $cid = "") {
	if (empty($encoding)) return;	//crmv@58687
	$mail->addStringAttachment($string, $filename, $encoding, $type, $contentdisposition, (empty($cid)) ? 0 : $cid); // crmv@198780
}

function puploadAttachments(&$mail,$dir) {	//crmv@86304
	$targetDir = 'storage/uploads_emails_'.$dir;
	
	// crmv@205309
	for($count_att=0;;$count_att++) {
		if (empty($_REQUEST['uploader_'.$count_att.'_tmpname'])) {
			break;
		}
		$filename = $_REQUEST['uploader_'.$count_att.'_name'];
		$filewithpath = $targetDir."/".$_REQUEST['uploader_'.$count_att.'_tmpname'];
		
		if(is_dir($targetDir) && is_file($filewithpath))
		{
			$mail->AddAttachment($filewithpath,$filename);
		
		} else {
			// try to search it in DB
			$FSDB = FileStorageDB::getInstance();
			$fileid = $FSDB->isFilePresentByPath($filewithpath);
			file_put_contents('logs/mlog.log', "FILEID: $fileid\n", FILE_APPEND);
			if ($fileid > 0) {
				$tname = $FSDB->createTempFile($fileid);
				file_put_contents('logs/mlog.log', "TNAME: $tname\n", FILE_APPEND);
				if ($tname) {
					$mail->AddAttachment($tname,$filename);
					file_put_contents('logs/mlog.log', "ADDED\n", FILE_APPEND);
				}
			}
		}
	}
	// crmv@205309e
	
}

function cleanPuploadAttachments($dir) {
	$targetDir = 'storage/uploads_emails_'.$dir;
	if (is_dir($targetDir)) {
		FSUtils::deleteFolder($targetDir);
	}
}

/**	Function to set the CC or BCC addresses in the mail
  *	$mail -- reference of the mail object
  *	$cc_mod -- mode to set the address ie., cc or bcc
  *	$cc_val -- addresss with comma seperated to set as CC or BCC in the mail
  */
function setCCAddress($mail,$cc_mod,$cc_val)
{
	global $adb;
	$adb->println("Inside the functin setCCAddress");

	if($cc_mod == 'cc')
		$method = 'AddCC';
	if($cc_mod == 'bcc')
		$method = 'AddBCC';
	if($cc_val != '')
	{
		// crmv@121744 crmv@198780
		$addresses = getAddresses($cc_val);
		if (!empty($addresses)) {
			foreach($addresses as $address) {
				$mail->$method($address[0],$address[1]);
			}
		}
		// crmv@121744e crmv@198780e
	}
}

// crmv@198780
function getAddresses($string) {
	$focus = CRMEntity::getInstance('Messages');
	$parsed_array = $focus->parseAddressList($string);
	$addresses = array();
	if (!empty($parsed_array)) {
		foreach($parsed_array as $parsed_email) {
			if (empty($parsed_email['name'])) {
				$parsed_email['name'] = preg_replace('/([^@]+)@(.*)/', '$1', $parsed_email['email']);
			}
			$addresses[] = array(
				$parsed_email['email'],
				$parsed_email['name'],
			);
		}
	}
	return $addresses;
}
// crmv@198780e

/**	Function to send the mail which will be called after set all the mail object values
  *	$mail -- reference of the mail object
  */
function MailSend($mail)
{
	global $log;
	$log->info("Inside of Send Mail function.");
	if(!$mail->Send())
	{
		$log->debug("Error in Mail Sending : Error log = '".$mail->ErrorInfo."'");
		return $mail->ErrorInfo;
	}
	else
	{
		$log->info("Mail has been sent from the vteCRM system : Status : '".$mail->ErrorInfo."'");
		return 1;
	}
}

/**	Function to get the Parent email id from HelpDesk to send the details about the ticket via email
  *	$returnmodule -- Parent module value. Contact or Account for send email about the ticket details
  *	$parentid -- id of the parent ie., contact or vte_account
  */
function getParentMailId($parentmodule,$parentid)
{
	global $adb,$table_prefix;
	$adb->println("Inside the function getParentMailId. \n parent module and id => ".$parentmodule."&".$parentid);

	if($parentmodule == 'Contacts')
	{
		$tablename = $table_prefix.'_contactdetails';
		$idname = 'contactid';
		$first_email = 'email';
		$second_email = 'yahooid';
	}
	if($parentmodule == 'Accounts')
	{
		$tablename = $table_prefix.'_account';
		$idname = 'accountid';
		$first_email = 'email1';
		$second_email = 'email2';
	}
	if($parentmodule == 'Users')
	{
		$tablename = $table_prefix.'_users';
		$idname = 'id';
		$first_email = 'email1';
		$second_email = 'email2';
	}
	if($parentid != '')
	{
		$query = 'select * from '.$tablename.' where '. $idname.' = ?';
		$res = $adb->pquery($query, array($parentid));
		$mailid = $adb->query_result($res,0,$first_email);
		$mailid2 = $adb->query_result($res,0,$second_email);
	}
	if($mailid == '' && $mailid2 != '') {
		$mailid = $mailid2;
	}
	return $mailid;
}

/**	Function to parse and get the mail error
  *	$mail -- reference of the mail object
  *	$mail_status -- status of the mail which is sent or not
  *	$to -- the email address to whom we sent the mail and failes
  *	return -- Mail error occured during the mail sending process
  */
function getMailError($mail,$mail_status,$to)
{
	//Error types in class.phpmailer.php
	/*
	provide_address, mailer_not_supported, execute, instantiate, file_access, file_open, encoding, data_not_accepted, authenticate,
	connect_host, recipients_failed, from_failed
	*/

	global $adb;
	$adb->println("Inside the function getMailError");

	$msg = array_search($mail_status,$mail->getTranslations());
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
		$adb->println("Mail error is not as connect_host or from_failed or recipients_failed");
		//$error_msg = $msg;
	}

	$adb->println("return error => ".$error_msg);
	return $error_msg;
}

/**	Function to get the mail status string (string of sent mail status)
  *	$mail_status_str -- concatenated string with all the error messages with &&& seperation
  *	return - the error status as a encoded string
  */
function getMailErrorString($mail_status_str)
{
	global $adb;
	$adb->println("Inside getMailErrorString function.\nMail status string ==> ".$mail_status_str);

	$mail_status_str = trim($mail_status_str,"&&&");
	$mail_status_array = explode("&&&",$mail_status_str);
	$adb->println("All Mail status ==>\n".$mail_status_str."\n");

	foreach($mail_status_array as $key => $val)
	{
		$list = explode("=",$val);
		$adb->println("Mail id & status ==> ".$list[0]." = ".$list[1]);
		if($list[1] == 0)
		{
			$mail_error_str .= $list[0]."=".$list[1]."&&&";
		}
	}
	$adb->println("Mail error string => '".$mail_error_str."'");
	if($mail_error_str != '')
	{
		$mail_error_str = 'mail_error='.base64_encode($mail_error_str);
	}
	return $mail_error_str;
}

/**	Function to parse the error string
  *	$mail_error_str -- base64 encoded string which contains the mail sending errors as concatenated with &&&
  *	return - Error message to display
  */
function parseEmailErrorString($mail_error_str)
{
	//TODO -- we can modify this function for better email error handling in future
	global $adb, $mod_strings;
	$adb->println("Inside the parseEmailErrorString function.\n encoded mail error string ==> ".$mail_error_str);

	$mail_error = base64_decode($mail_error_str);
	$adb->println("Original error string => ".$mail_error);
	$mail_status = explode("&&&",trim($mail_error,"&&&"));
	foreach($mail_status as $key => $val)
	{
		$status_str = explode("=",$val);
		$adb->println('Mail id => "'.$status_str[0].'".........status => "'.$status_str[1].'"');
		if($status_str[1] != 1 && $status_str[1] != '')
		{
			$adb->println("Error in mail sending");
			if($status_str[1] == 'connect_host')
			{
				$adb->println("if part - Mail sever is not configured");
				$errorstr .= '<br><b><font color=red>'.$mod_strings['MESSAGE_CHECK_MAIL_SERVER_NAME'].'</font></b>';
				break;
			}
			elseif($status_str[1] == '0')
			{
				$adb->println("first elseif part - status will be 0 which is the case of assigned to vte_users's email is empty.");
				$errorstr .= '<br><b><font color=red> '.$mod_strings['MESSAGE_MAIL_COULD_NOT_BE_SEND'].' '.$mod_strings['MESSAGE_PLEASE_CHECK_FROM_THE_MAILID'].'</font></b>';
				//Added to display the message about the CC && BCC mail sending status
				if($status_str[0] == 'cc_success')
				{
                                        $cc_msg = 'But the mail has been sent to CC & BCC addresses.';
					$errorstr .= '<br><b><font color=purple>'.$cc_msg.'</font></b>';
				}
			}
			elseif(strstr($status_str[1],'from_failed'))
			{
				$adb->println("second elseif part - from email id is failed.");
				$from = explode('from_failed',$status_str[1]);
				$errorstr .= "<br><b><font color=red>".$mod_strings['MESSAGE_PLEASE_CHECK_THE_FROM_MAILID']." '".$from[1]."'</font></b>";
			}
			else
			{
				$adb->println("else part - mail send process failed due to the following reason.");
				$errorstr .= "<br><b><font color=red> ".$mod_strings['MESSAGE_MAIL_COULD_NOT_BE_SEND_TO_THIS_EMAILID']." '".$status_str[0]."'. ".$mod_strings['PLEASE_CHECK_THIS_EMAILID']."</font></b>";
			}
		}
	}
	$adb->println("Return Error string => ".$errorstr);
	return $errorstr;
}

function append_mail($mail,$account,$parentid,$to_email,$from_name,$from_email,$subject,$contents,$cc='',$bcc='',$send_mode='Single')
{
	$subject = html_entity_decode(trim($subject), ENT_COMPAT, 'UTF-8'); //crmv@146141
	//crmv@32079	//crmv@49285
	$mail_server_smtp = $mail->Host; // crmv@206145
	// crmv@202172
	$VTEP = VTEProperties::getInstance();
	$auto_append_servers = $VTEP->getProperty('modules.emails.auto_append_servers');
	$auto_append = false;
	if (!empty($auto_append_servers)) {
		foreach($auto_append_servers as $auto_append_server) {
			if (strpos($mail_server_smtp,$auto_append_server) !== FALSE) {
				$auto_append = true;
				break;
			}
		}
	}
	if ($auto_append) {
	// crmv@202172e
		$messageFocus = CRMEntity::getInstance('Messages');
		try {
			$messageFocus->setAccount($account);
			$messageFocus->getZendMailStorageImap();
			$specialFolders = $messageFocus->getSpecialFolders();
			//crmv@61262
			$saved_message = $messageFocus->fetchNews($specialFolders['Sent']);
			//$saved_message = $messageFocus->getSavedMessages();
			//crmv@61262e
		} catch (Exception $e) {
			return false;
		}
		if (!empty($saved_message)) {
			$focus = CRMEntity::getInstance('Messages');
			$focus->retrieve_entity_info_no_html($saved_message[0],'Messages');
			$message_id = $focus->column_fields['messageid'];
			$subject = $focus->column_fields['subject']; // crmv@81338
			$parentids = $_REQUEST['relation'];
			if (!empty($parentids)) {
				$ids = array_filter(explode('|', $parentids));
				foreach ($ids as $relid) {
					list($elid, $fieldid) = explode('@', $relid, 2);
					if (strpos($elid,'x') !== false) {
						$elid = explode('x',$elid);
						$elid = $elid[1];
					}
					$mod = getSalesEntityType($elid);
					if ($mod) {
						$focus->save_related_module_small($message_id, $mod, $elid, $subject); // crmv@81338	crmv@82688
					}
				}
			}
			if (!empty($_REQUEST['ModCommentsMethod'])) {
				$messageFocus->saveModComment($saved_message[0],$message_id);
			}
			if (!empty($parentid)) {
				$messageFocus->setRecipients($saved_message[0],$parentid);
			}
			if (!empty($send_mode)) {
				$messageFocus->setSendMode($saved_message[0],$send_mode);
			}
			if(isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
				$messageFocus->setVisibility($saved_message[0],'Public');
			}
			return true;	//crmv@109607
		}
		return false;	//crmv@109607
	}
	//crmv@32079e	//crmv@49285e
	$mail->clearAddresses(); // crmv@180739
	if($to_email != '')
	{
		if(is_array($to_email)) {
			foreach($to_email as $e) {
				// crmv@198780
				$addresses = getAddresses($e);
				if (!empty($addresses)) {
					foreach($addresses as $address) {
						$mail->addAddress($address[0],$address[1]);
					}
				}
				// crmv@198780e
			}
		} else {
			// crmv@198780
			$addresses = getAddresses($to_email);
			if (!empty($addresses)) {
				foreach($addresses as $address) {
					$mail->addAddress($address[0],$address[1]);
				}
			}
			// crmv@198780e
		}
	}
	if($from_email == '') {
		$from_email = getUserEmailId('user_name',$from_name);
	}
	$mail->Subject = $subject;
	if (is_array($contents)) {
		$mail->Body = $contents['html'];
		$mail->AltBody = $contents['text'];
	} else {
		$mail->Body = $contents;
		$mail->AltBody = strip_tags(preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$contents));
	}
	HandleInlineAttachments($mail,$_REQUEST['uploaddir'],'append'); //crmv@81704
	setCCAddress($mail,'cc',$cc);
	setCCAddress($mail,'bcc',$bcc);
	try {
		$messageFocus = CRMEntity::getInstance('Messages');
		if ($messageFocus->appendMessage($mail,$account,'Sent',$_REQUEST['relation'])) {
			//fetch new messages from Sent folder
			$specialFolders = $messageFocus->getSpecialFolders();
			$saved_message = $messageFocus->fetchNews($specialFolders['Sent']);	//crmv@54904
			if (!empty($saved_message)) {
				if (!empty($_REQUEST['ModCommentsMethod'])) {
					$messageFocus->saveModComment($saved_message[0],$mail->getLastMessageID()); // crmv@180739
				}
			if (!empty($parentid)) {
					$messageFocus->setRecipients($saved_message[0],$parentid);
				}
				if (!empty($send_mode)) {
					$messageFocus->setSendMode($saved_message[0],$send_mode);
				}
				//crmv@2043m
				if(isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
					$messageFocus->setVisibility($saved_message[0],'Public');
				}
				//crmv@2043m
			}
			return true;
		} else {
			return false;
		}
	} catch (Exception $e) {
		//crmv@86304
		return false;
		/* if ($e->getMessage() == 'ERR_IMAP_CREDENTIALS_EMPTY') {
			// user has not configured the mailbox
			return;
		} else {
			// throw again the exception
			throw $e;
		} */
		//crmv@86304e
	}
}
function setflag_mail($messagesid, $action) {
	$flag = '';
	$return = '';
	if ($action == 'reply' || $action == 'reply_all') {
		$flag = "answered";
	} elseif ($action == 'forward') {
		$flag = "forwarded";
	}
	if (!empty($flag)) {
		$messageFocus = CRMEntity::getInstance('Messages');
		$messageFocus->id = $messagesid;
		$result = $messageFocus->retrieve_entity_info($messagesid,'Messages',false);
    	if (empty($result)) {	// no errors
			$messageFocus->setAccount($messageFocus->column_fields['account']);	//crmv@46021
			try {
				$messageFocus->getZendMailStorageImap();
			} catch (Exception $e) {
				return $return;
			}
			$messageFocus->setFlag($flag,1);
	
			$flags = $messageFocus->getCacheFlags();
			if (in_array(Zend\Mail\Storage::FLAG_ANSWERED,$flags) && in_array('Forwarded',$flags)) {
				$return = "window.opener.jQuery('#flag_{$messagesid}_answered').hide();window.opener.jQuery('#flag_{$messagesid}_forwarded').hide();window.opener.jQuery('#flag_{$messagesid}_answered_forwarded').show();";
			} elseif (in_array(Zend\Mail\Storage::FLAG_ANSWERED,$flags)) {
				$return = "window.opener.jQuery('#flag_{$messagesid}_answered').show();";
			} elseif (in_array('Forwarded',$flags)) {
				$return = "window.opener.jQuery('#flag_{$messagesid}_forwarded').show();";
			}
    	}
	}
	return $return;
}
//crmv@31263
function save_draft_mail($module,$account,$to_email,$from_name,$from_email,$subject,$contents,$cc='',$bcc='',$attachment='',$emailid='',&$mail='',$parentids='',$recipientids='',$send_mode='Single')	//crmv@22700	//crmv@25351
{
	global $root_directory;
	global $HELPDESK_SUPPORT_EMAIL_ID, $HELPDESK_SUPPORT_NAME;

	//if module is HelpDesk then from_email will come based on support email id
	if($from_email == '')//$module != 'HelpDesk')
		$from_email = getUserEmailId('user_name',$from_name);

	$mail = new VTEMailer(); // crmv@180739

	setMailerProperties($mail,$subject,$contents,$from_email,$from_name,$to_email,$attachment,$emailid,$module);
	setCCAddress($mail,'cc',$cc);
	setCCAddress($mail,'bcc',$bcc);

	// vtmailscanner customization: If Support Reply to is defined use it.
	global $HELPDESK_SUPPORT_EMAIL_REPLY_ID;
	if($HELPDESK_SUPPORT_EMAIL_REPLY_ID && $HELPDESK_SUPPORT_EMAIL_ID != $HELPDESK_SUPPORT_EMAIL_REPLY_ID) {
		$mail->addReplyTo($HELPDESK_SUPPORT_EMAIL_REPLY_ID); // crmv@200330
	}
	// END

	// Fix: Return immediately if Outgoing server not configured
    if(empty($mail->Host)) {
		return 0;
    }
    // END

	if (is_array($contents)) {
		$mail->Body = $contents['html'];
		$mail->AltBody = $contents['text'];
	} else {
		$mail->Body = $contents;
		$mail->AltBody = strip_tags(preg_replace(array("/<p>/i","/<br>/i","/<br \/>/i"),array("\n","\n","\n"),$contents));
	}
	$mail->preSend(); // crmv@180739

	$messageFocus = CRMEntity::getInstance('Messages');
	$append_result = $messageFocus->appendMessage($mail,$account,'Drafts',$parentids);
	if ($append_result) {
		//fetch new messages from Draft folder
		$messageFocus->getZendMailStorageImap();
		$specialFolders = $messageFocus->getSpecialFolders();
		$messageFocus->fetchNews($specialFolders['Drafts']);
		if (isset($_REQUEST['draft_id']) && $_REQUEST['draft_id'] != '') {
			global $adb, $table_prefix, $current_user;
			// crmv@198780
			if ($adb->isMysql()) {
				$adb->pquery("insert ignore into {$table_prefix}_messages_drafts (id, messagehash, userid) values (?,?,?)",array($_REQUEST['draft_id'],$messageFocus->getMessageHash($mail->getLastMessageID(), ''),$current_user->id)); // crmv@81338 crmv@180739
			} else {
				$result = $adb->pquery("select id from {$table_prefix}_messages_drafts where id = ? and messagehash = ?", array($_REQUEST['draft_id'],$messageFocus->getMessageHash($mail->getLastMessageID(), '')));
				if ($result && $adb->num_rows($result) == 0) {
					$adb->pquery("insert into {$table_prefix}_messages_drafts (id, messagehash, userid) values (?,?,?)",array($_REQUEST['draft_id'],$messageFocus->getMessageHash($mail->getLastMessageID(), ''),$current_user->id)); // crmv@81338 crmv@180739
				}
			}
			// crmv@198780e
		}
		$saved_message = $messageFocus->getSavedMessages();
		if (!empty($saved_message) && !empty($recipientids)) {
			$messageFocus->setRecipients($saved_message[0],$recipientids);
		}
		if (!empty($send_mode)) {
			$messageFocus->setSendMode($saved_message[0],$send_mode);
		}
	}
	return $append_result;
}
function delete_draft_mail($draftid,$skip_messageid='') {
	global $adb, $table_prefix;
	$messageFocus = CRMEntity::getInstance('Messages');
	//crmv@174073
	$query = "SELECT {$table_prefix}_messages_drafts.id, {$table_prefix}_messages_drafts.messagehash, messagesid, messageid, xuid, account
		FROM {$table_prefix}_messages_drafts
		INNER JOIN {$table_prefix}_messages ON {$table_prefix}_messages_drafts.messagehash = {$table_prefix}_messages.messagehash
		WHERE deleted = 0 AND id = ?";
	//crmv@174073e
	$params = array($draftid);
	if (!empty($skip_messageid)) {
		$query .= " AND {$table_prefix}_messages_drafts.messagehash <> ?";
		$params[] = $messageFocus->getMessageHash($skip_messageid, '');	//crmv@86194
	}
	$result = $adb->pquery($query,$params);
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$messageFocus->setAccount($row['account']);
			$messageFocus->trash('Messages',$row['messagesid']);
		}
	}
	$query = "delete from {$table_prefix}_messages_drafts where id = ?";
	if (!empty($skip_messageid)) {
		$query .= " AND {$table_prefix}_messages_drafts.messagehash <> ?";
	}
	$adb->pquery($query,$params);
	if (!empty($_REQUEST['sending_queue_currentid'])) $adb->pquery("update {$table_prefix}_emails_send_queue set s_clean_drafts = ? where id = ?",array(1,$_REQUEST['sending_queue_currentid']));	//crmv@48501
}
//crmv@31263e

// crmv@81704 crmv@205899
function HandleInlineAttachments(&$mail,$dir,$mode = 'send'){
	$targetDir = 'storage/uploads_emails_'.$dir;
	$targetDir_quoted = preg_quote($targetDir,'/');
	$cnt = 0;
	$replacements = Array();
	$cids = Array();
	preg_match_all('/<img[^>]*src=[\'"]?('.$targetDir_quoted.'[^\s<>\'"]+)[\'"]?/i', $mail->Body, $matches, PREG_SET_ORDER);
	if (!empty($matches)){
		foreach($matches as $match) {
			if (file_exists($match[1])) {
				$cidname = ++$cnt;
				$replacements['/'.preg_quote($match[1],'/').'/'] = "cid:".$cidname;
				$cids[$match[1]] = $cidname;
			}
		}
		unset($matches);
	}
	// ex. <img src="storage/uploads_emails_4_20200904151503/VTENEXT (2).png"
	preg_match_all('/<img[^>]*src=[\'"]('.$targetDir_quoted.'[^<>\'"]+)[\'"]/i', $mail->Body, $matches, PREG_SET_ORDER);
	if (!empty($matches)){
		foreach($matches as $match) {
			if (file_exists($match[1])) {
				$cidname = ++$cnt;
				$replacements['/'.preg_quote($match[1],'/').'/'] = "cid:".$cidname;
				$cids[$match[1]] = $cidname;
			}
		}
		unset($matches);
	}
	if ($cnt > 0) {
		if (!empty($replacements)){
			$mail->Body = preg_replace(array_keys($replacements), array_values($replacements), $mail->Body);
			$mail->AltBody = preg_replace(array_keys($replacements), array_values($replacements), $mail->AltBody);
		}
		unset($replacements);
		if ($mode == 'send'){
			if (!empty($cids)){
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				foreach ($cids as $filepath=>$cidname) {
					$mail->addEmbeddedImage($filepath, $cidname, pathinfo($filepath,PATHINFO_BASENAME), 'base64', finfo_file($finfo, $filepath));
				}
			}
		}
		unset($cids);
		unset($finfo);
	}
}
// crmv@81704e cmrv@205899e