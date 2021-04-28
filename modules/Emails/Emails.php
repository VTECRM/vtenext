<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include_once('config.php');
require_once('modules/Contacts/Contacts.php');
require_once('modules/Accounts/Accounts.php');
require_once('modules/Potentials/Potentials.php');
require_once('modules/Users/Users.php');

// Email is used to store customer information.
class Emails extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'activityid';
	// Stored vte_fields
  	// added to check email save from plugin or not
	var $plugin_save = false;

	var $rel_users_table ;
	var $rel_contacts_table;
	var $rel_serel_table;

	var $tab_name = Array();
	var $tab_name_index = Array();

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
		'LBL_FROM'=>array('emaildetails'=>'from_email'), //crmv@30521
		'Subject'=>Array('activity'=>'subject'),
		'Related to'=>Array('seactivityrel'=>'parent_id'),
		'Date Sent'=>Array('activity'=>'date_start'),
		'Assigned To'=>Array('crmentity','smownerid'),
		'Access Count'=>Array('email_track','access_count')
	);

	var $list_fields_name = Array(
		'LBL_FROM'=>'from_email', //crmv@30521
		'Subject'=>'subject',
		'Related to'=>'parent_id',
		'Date Sent'=>'date_start',
		'Assigned To'=>'assigned_user_id',
		'Access Count'=>'access_count'
	);

	var $list_link_field= 'subject';

	var $column_fields = Array();

	var $sortby_fields = Array('subject','date_start','saved_toid');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'date_start';
	var $default_sort_order = 'ASC';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('subject','assigned_user_id');

	//crmv@32079
	var $default_account = array(
		'smtp' => array(
			'Gmail' => array(
				'server'=>'ssl://smtp.gmail.com',
				'server_port'=>'465',
				'server_username'=>'username@gmail.com',
				'server_password'=>'required',
				'smtp_auth'=>'checked',
			),
			'Hotmail' => array(
				'server'=>'smtp.live.com',
				'server_port'=>'587',
				'server_username'=>'username@hotmail.com',
				'server_password'=>'required',
				'smtp_auth'=>'checked',
			),
			'Yahoo!' => array(
				'server'=>'smtp.mail.yahoo.com',
				'server_port'=>'25',
				'server_username'=>'username@yahoo.com',
				'server_password'=>'required',
				'smtp_auth'=>'checked',
				'note'=>'LBL_YAHOO_SMTP_INFO',
			),
			// crmv@206145
			'Office365' => array(
				'server'=>'smtp.office365.com',
				'server_port'=>'587',
				'server_username'=>'username@example.com',
				'server_password'=>'required',
				'smtp_auth'=>'checked',
			),
			// crmv@206145e
			'Other' => array(
				'server'=>'smtp.example.com',
				'server_port'=>'25',
				'server_username'=>'',
				'smtp_auth'=>'',
			),
		),
		'imap' => array(
			'Gmail' => array(
				'server'=>'imap.gmail.com',
				'server_port'=>'993',
				'ssl_tls'=>'ssl',
			),
			'Yahoo!' => array(
				'server'=>'imap-ssl.mail.yahoo.com',
				'server_port'=>'993',
				'ssl_tls'=>'ssl',
			),
			// crmv@206145
			'Office365' => array(
				'server'=>'outlook.office365.com',
				'server_port'=>'993',
				'ssl_tls'=>'ssl',
				'domain'=>'',
			),
			// crmv@206145e
			'Other' => array(
				'server'=>'imap.example.com',
				'server_port'=>'143',
				'ssl_tls'=>'',
			),
		),
	);
	//crmv@32079e
	
	//crmv@44037	crmv@49001
	var $signatureId;
	var $signatureStatus = false;
	//crmv@44037e	crmv@49001e
	//crmv@58893
	var $max_attachment_size;	//max attachments size in mb for plupload
	var $max_message_size;  	//max data size in bytes of smtp, fallback to default if not retrieved from server
	//crmv@58893 e
	var $max_emails_send_queue_attempts; //crmv@186704
	
	/** This function will set the columnfields for Email module
	*/
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_activity";
		$this->rel_users_table = $table_prefix."_salesmanactivityrel";
		$this->rel_contacts_table = $table_prefix."_cntactivityrel";
		$this->rel_serel_table = $table_prefix."_seactivityrel";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_activity',$table_prefix.'_emaildetails');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_activity'=>'activityid',
				$table_prefix.'_seactivityrel'=>'activityid',$table_prefix.'_cntactivityrel'=>'activityid',$table_prefix.'_email_track'=>'mailid',$table_prefix.'_emaildetails'=>'emailid');
		//crmv@186709
		$VTEP = VTEProperties::getInstance();
		$this->max_attachment_size = $VTEP->getProperty('modules.emails.max_attachment_size');
		$this->max_message_size = $VTEP->getProperty('modules.emails.max_message_size');
		$this->max_emails_send_queue_attempts = $VTEP->getProperty('modules.emails.max_emails_send_queue_attempts');
		//crmv@186709e		
		$this->log = LoggerManager::getLogger('email');
		$this->log->debug("Entering Emails() method ...");
		$this->log = LoggerManager::getLogger('email');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Emails');
		$this->log->debug("Exiting Email method ...");
	}
	
	function save_module($module) {}

	// crmv@66378 - don't overwrite the exising column fields
	function save($module_name,$longdesc=false,$offline_update=false,$triggerEvent=true) {
		
	    if (!empty($this->column_fields['date_start'])) {
	    	$date = getValidDBInsertDateValue($this->column_fields['date_start']);
	    	if (!empty($this->column_fields['time_start'])) {
	    		$date .= ' '.$this->column_fields['time_start'];
	    	}
	    }
		
		$column_fields = array_merge($this->column_fields, array(
			'subject'=>$this->column_fields['subject'],
			'description'=>$this->column_fields['description'],
			'mfrom'=>$this->column_fields['from_email'],
			'mfrom_f'=>$this->column_fields['from_email'],
			'mto'=>$this->column_fields['saved_toid'],
			'mto_f'=>$this->column_fields['saved_toid'],
			'mcc'=>$this->column_fields['ccmail'],
			'mcc_f'=>$this->column_fields['ccmail'],
			'mbcc'=>$this->column_fields['bccmail'],
			'mbcc_f'=>$this->column_fields['bccmail'],
			'mdate'=>$date,
			'assigned_user_id'=>$this->column_fields['assigned_user_id'],
			'parent_id'=>$this->column_fields['parent_id'],
			'mtype' => 'Link',
		));
		
		$focus = CRMEntity::getInstance('Messages');
		$focus->saveCacheLink($column_fields);

		//$this->insertIntoAttachment($this->id,$module);
	}
	// crmv@66378e

	function insertIntoAttachment($id,$module)
	{
		global $log, $adb, $current_user,$table_prefix;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;

		//Send document attachment
		if(isset($_REQUEST['pdf_attachment']) && $_REQUEST['pdf_attachment'] !='')
		{
			$file_saved = pdfAttach($this,$module,$_REQUEST['pdf_attachment'],$id);
		}

		//This is to added to store the existing attachment id of the contact where we should delete this when we give new image
		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);
			}
		}
		//crmv@22123
		$targetDir = 'storage/uploads_emails_'.$current_user->id;
		for($count_att=0;;$count_att++) {
			if (empty($_REQUEST['uploader_'.$count_att.'_tmpname'])) break;
			$files['name'] = $_REQUEST['uploader_'.$count_att.'_name'];
			$files['tmp_name'] = $targetDir."/".$_REQUEST['uploader_'.$count_att.'_tmpname'];
			$file_saved = $this->uploadAndSaveFile($id,$module,$files,true);
			//crmv@31456
			if(is_file($files['tmp_name']) && !isset($_REQUEST['save_in_draft'])){
				unlink($files['tmp_name']);
			}
			//crmv@31456e
		}
		//crmv@22123e
		if($module == 'Emails' && isset($_REQUEST['att_id_list']) && $_REQUEST['att_id_list'] != '')
		{
			$att_lists = explode(";",$_REQUEST['att_id_list'],-1);
			$id_cnt = count($att_lists);
			if($id_cnt != 0)
			{
				for($i=0;$i<$id_cnt;$i++)
				{
					$sql_rel='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
					$adb->pquery($sql_rel, array($id, $att_lists[$i]));
				}
			}
		}
		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/**
	* Used to releate email and contacts -- Outlook Plugin
	*/
	function set_emails_contact_invitee_relationship($email_id, $contact_id)
	{
		global $log;
		$log->debug("Entering set_emails_contact_invitee_relationship(".$email_id.",". $contact_id.") method ...");
		$query = "insert into $this->rel_contacts_table (contactid,activityid) values(?,?)";
		$this->db->pquery($query, array($contact_id, $email_id), true,"Error setting email to contact relationship: "."<BR>$query");
		$log->debug("Exiting set_emails_contact_invitee_relationship method ...");
	}

	/**
	* Used to releate email and salesentity -- Outlook Plugin
	*/
	function set_emails_se_invitee_relationship($email_id, $contact_id)
	{
		global $log;
		$log->debug("Entering set_emails_se_invitee_relationship(".$email_id.",". $contact_id.") method ...");
		$query = "insert into $this->rel_serel_table (crmid,activityid) values(?,?)";
		$this->db->pquery($query, array($contact_id, $email_id), true,"Error setting email to contact relationship: "."<BR>$query");
		$log->debug("Exiting set_emails_se_invitee_relationship method ...");
	}

	/**
	* Used to releate email and Users -- Outlook Plugin
	*/
	function set_emails_user_invitee_relationship($email_id, $user_id)
	{
		global $log;
		$log->debug("Entering set_emails_user_invitee_relationship(".$email_id.",". $user_id.") method ...");
		$query = "insert into $this->rel_users_table (smid,activityid) values (?,?)";
		$this->db->pquery($query, array($user_id, $email_id), true,"Error setting email to user relationship: "."<BR>$query");
		$log->debug("Exiting set_emails_user_invitee_relationship method ...");
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log,$table_prefix;

		//crmv@26265
		$sql='DELETE FROM '.$table_prefix.'_seactivityrel WHERE activityid=? AND crmid=?';
		$this->db->pquery($sql, array($id, $return_id));
		//crmv@26265e

		$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
		$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
		$this->db->pquery($sql, $params);

		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	//crmv@2963m
	function getMessageForwardHeader($message) {
		global $default_charset, $adb, $table_prefix;
		$editor_size = 76;

		$display = array(getTranslatedString('Subject','Messages') => strlen(getTranslatedString("Subject",'Messages')),
						getTranslatedString("From",'Messages') => strlen(getTranslatedString("From",'Messages')),
						getTranslatedString("Date",'Messages') => strlen(getTranslatedString("Date",'Messages')),
						getTranslatedString("To",'Messages') => strlen(getTranslatedString("To",'Messages')),
						getTranslatedString("Cc",'Messages') => strlen(getTranslatedString("Cc",'Messages'))
		);
		$maxsize = max($display);
		$indent = str_pad('',$maxsize+2);
		foreach($display as $key => $val) {
			$display[$key] = $key .': '. str_pad('', $maxsize - $val);
		}
		$from = htmlentities(htmlentities($message->column_fields['mfrom'], ENT_COMPAT, $default_charset), ENT_COMPAT, $default_charset);
		//crmv@97344
		$to = htmlentities(htmlentities($message->column_fields['mto_f'], ENT_COMPAT, $default_charset), ENT_COMPAT, $default_charset); 
		$cc = htmlentities(htmlentities($message->column_fields['mcc_f'], ENT_COMPAT, $default_charset), ENT_COMPAT, $default_charset);
		//crmv@97344e
		$subject = htmlentities(htmlentities($message->column_fields['subject'], ENT_COMPAT, $default_charset), ENT_COMPAT, $default_charset);
		$bodyTop =  str_pad(' '.getTranslatedString("Original Message",'Messages').' ', $editor_size-2, '-', STR_PAD_BOTH) ."<br />" .
					$display[getTranslatedString("Subject",'Messages')] . $subject . "<br />" .
					$display[getTranslatedString("From",'Messages')] . $from . "<br />" .
					$display[getTranslatedString("Date",'Messages')] . $message->getFullDate($message->column_fields['mdate']) . "<br />" .
					$display[getTranslatedString("To",'Messages')] . $to . "<br />";
		if (!empty($cc)) {
			$bodyTop .= $display[getTranslatedString("Cc",'Messages')] . $cc . "<br />";
		}
		$bodyTop .= str_pad('', $editor_size-2+9, '-')."<br /><br />";
		return $bodyTop;
	}
	function getMessageReplyHeader($message) {
		$orig_from = $message->column_fields['mfrom_n'];
		if (empty($orig_from)) {
			$orig_from = $message->column_fields['mfrom'];
		}
		$orig_date = $message->getFullDate($message->column_fields['mdate']);
		$full_reply_citation = sprintf(getTranslatedString("On %s, %s wrote:",'Messages'), $orig_date, $orig_from);
		return $full_reply_citation."\n";
	}
	//crmv@2963me
	//crmv@2051m
	function getFromEmailList($from_email,$account='') {
		global $adb, $current_user;
		$skip_select_option = false;	//crmv@60095
		$list = array();
		$focusMessages = CRMEntity::getInstance('Messages');
		$user_accounts = $focusMessages->getUserAccounts();
		$main_account = $focusMessages->getMainUserAccount();
		if (!empty($user_accounts)) {
			$commonDomains = array(
				'Gmail' => 'gmail.com',
			);
			foreach($user_accounts as $a) {
				$email = $a['email']; //crmv@50745
				//crmv@46012
				$domain = $a['domain'] ?: $commonDomains[$a['account']];
				if (strpos($email,'@') === false && !empty($domain)) $email = $email.'@'.$domain;
				//crmv@46012e
				if (!array_key_exists($email,$list)) {
					$list[$email] = array('email'=>$email,'name'=>$a['description'],'account'=>$a['id'],'selected'=>'');
				}
			}
		}
		//crmv@53659
		if (empty($list) || (!empty($list) && !empty($current_user->column_fields['email1']) && !array_key_exists($current_user->column_fields['email1'],$list))) {
			$list[$current_user->column_fields['email1']] = array('email'=>$current_user->column_fields['email1'],'name'=>trim(getUserFullName($current_user->id)),'account'=>$main_account['id'],'selected'=>'');
		}
		//crmv@53659e
		//crmv@80029
		$pop3_accounts = $focusMessages->getPop3();
		if (!empty($pop3_accounts)) {
			foreach($pop3_accounts as $a) {
				if ($a['active'] == 1) {
					//crmv@141050
					if (!array_key_exists($a['username'],$list)) {
						$list[$a['username']] = array('email'=>$a['username'],'name'=>$a['username'],'account'=>$a['accountid'],'selected'=>'');
					}
					//crmv@141050e
				}
			}
		}
		//crmv@80029e
		//crmv@2043m	
		if ($_REQUEST['reply_mail_user'] == 'mailconverter' && isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
			$HelpDeskFocus = CRMEntity::getInstance('HelpDesk');
			$HelpDeskFocus->retrieve_entity_info_no_html($_REQUEST['reply_mail_converter_record'], 'HelpDesk');
			if ($HelpDeskFocus->column_fields['helpdesk_from'] != '') {
				if (!array_key_exists($HelpDeskFocus->column_fields['helpdesk_from'], $list)) {
					//crmv@60095
					$list[$HelpDeskFocus->column_fields['helpdesk_from']] = array('email'=>$HelpDeskFocus->column_fields['helpdesk_from'],'name'=>$HelpDeskFocus->column_fields['helpdesk_from_name'],'account'=>$main_account['id'],'selected'=>'selected');
					$skip_select_option = true;
					//crmv@60095e
				}
			}
		}
		//crmv@2043me
		if (!$skip_select_option) {	//crmv@60095
			foreach ($list as $i => $info) {
				$selected = '';
				if (isset($info['account']) && $account !== '' && $account == $info['account']) {
					$selected = 'selected';
				} elseif ($from_email == $info['email']) {
					$selected = 'selected';
				}
				if ($selected != '') {
					$list[$i]['selected'] = $selected;
					break;
				}
			}
			//crmv@83942
			if ($selected == '') {
				foreach ($list as $i => $info) {
					if ($info['account'] == $main_account['id']) {
						$list[$i]['selected'] = 'selected';
						break;
					}
				}
			}
			//crmv@83942e
		}
		return $list;
	}
	function getFromEmailName($from_email) {
		$list = $this->getFromEmailList($from_email);
		foreach($list as $info) {
			if ($info['email'] == $from_email) {
				return $info['name'];
			}
		}
		return $from_email;
	}
	function getFromEmailAccount($from_email) {
		$list = $this->getFromEmailList($from_email);
		foreach($list as $info) {
			if ($info['email'] == $from_email) {
				return $info['account'];
			}
		}
	}
	//crmv@2051me
	//crmv@48501
	function add2SendingQueue($userid, $method, $request) {
		// crmv@187622
		global $adb, $table_prefix;
		$date = date('Y-m-d H:i:s');
		$scheduled = 0;
		if ($method == 'draft') {
			$adb->pquery("DELETE FROM {$table_prefix}_emails_send_queue WHERE method = ? AND request LIKE ?",array('draft','%"draft_id":"'.$request['draft_id'].'"%'));
		} else {
			if (!empty($request['scheduled_date'])) {
				list($d,$h) = explode(' ',$request['scheduled_date']);
				$date = adjustTimezone(getValidDBInsertDateValue($d).' '.$h, 0, $this->userTimezone, true);
				$scheduled = 1;
			}
		}
		unset($request['scheduled_date']);
		$id = $adb->getUniqueID($table_prefix."_emails_send_queue");
		$sql = "insert into {$table_prefix}_emails_send_queue (id,userid,method,request,date,scheduled) values (?,?,?,".$adb->getEmptyClob(true).",?,?)";
		$params = array($id, $userid, $method, $date, $scheduled);
		$adb->pquery($sql, $params);
		$adb->updateClob($table_prefix.'_emails_send_queue','request',"id=$id",Zend_Json::encode($request));
		if ($scheduled === 1) {
			$focusMessages = CRMentity::getInstance('Messages');
			$messagesid = $focusMessages->saveScheduledMessage($date, $request);
			$adb->pquery("update {$table_prefix}_emails_send_queue set messagesid = ? where id = ?", array($messagesid,$id));
		}
		// crmv@187622e
	}
	function processSendingQueue($user_start='',$user_end='') {	//crmv@71322
		global $adb, $table_prefix, $current_user;
		
		$mail = new VTEMailer(); // crmv@180739
		
		$original_request = $_REQUEST;	//crmv@53929

		// clean queue
		$adb->pquery("delete from {$table_prefix}_emails_send_queue where status = ?",array(1));
		
		// process queue
		$sql = "select * from {$table_prefix}_emails_send_queue where date <= ? and (status = ? or (status = ? and send_attempts < ?))"; //crmv@186704 crmv@187622
		//crmv@71322
		if ($user_start != '') {
			$sql .= " and userid >= $user_start";
			if ($user_end != '') {
				$sql .= " and userid <= $user_end";
			}
		}
		//crmv@71322e
		$sql .= " order by userid, id";
		$result = $adb->limitpQuery($sql,0,5,array(date('Y-m-d H:i:s'),0,2,$this->max_emails_send_queue_attempts)); //crmv@186704 crmv@187622
		if ($result && $adb->num_rows($result) > 0) {
			$userid = '';
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				if ($mail->SMTPDebug) echo "sending message {$row['id']}...\n";	//crmv@55094
				if (!empty($row['messagesid'])) $this->sendNow($row['messagesid']); // crmv@187622
				$adb->pquery("update {$table_prefix}_emails_send_queue set status = ?, date = ? where id = ?",array(2,date('Y-m-d H:i:s'),$row['id']));
				if ($userid != $row['userid']) {
					$userid = $row['userid'];
					$current_user = CRMEntity::getInstance('Users');
					$current_user->retrieve_entity_info($userid, 'Users');
				}
				$_REQUEST = array_merge($original_request,Zend_Json::decode($row['request']));	//crmv@53929
				$_REQUEST['sending_queue_currentid'] = $row['id'];
				$skip_exit = true;
				$error_message = '';
				
				$adb->pquery("update {$table_prefix}_emails_send_queue set send_attempts = send_attempts + 1 where id = ?",array($row['id'])); //crmv@186704

				$sdk_custom_file = 'Save';
				if (isModuleInstalled('SDK')) {
					$tmp_sdk_custom_file = SDK::getFile('Emails',$sdk_custom_file);
					if (!empty($tmp_sdk_custom_file)) {
						$sdk_custom_file = $tmp_sdk_custom_file;
					}
				}
				@include("modules/Emails/$sdk_custom_file.php");
				
				if (empty($error_message)) {
					$adb->pquery("update {$table_prefix}_emails_send_queue set status = ? where id = ?",array(1,$row['id']));
				} else {
					$adb->pquery("update {$table_prefix}_emails_send_queue set error = ? where id = ?",array($error_message,$row['id']));
				}
				if ($mail->SMTPDebug) echo "\nmessage {$row['id']} sent!\n\n";	//crmv@55094
			}
		}
	}
	//crmv@48501e
	//crmv@44037	crmv@49001
	function setSignatureId() {
		$this->signatureId = rand(10000,99999);
	}
	function addSignature() {
		if ($this->signatureStatus) return;
		if (empty($this->signatureId)) $this->setSignatureId();
		$this->column_fields['description'] .= '<p></p><div id="signature'.$this->signatureId.'"></div>';
		$this->signatureStatus = true;
	}
	//crmv@44037e	crmv@49001e
	//crmv@51130	crmv@55094
	function checkBeforeSending($request,&$error) {
		
		// crmv@180739 - removed code
		
		global $adb, $table_prefix, $current_user;
		
		$subject = $request['subject'];
		$description = $request['description'];
		$from_email = $request['from_email'];
		$cc = explode(',',$request['ccmail']);
		$cc = array_map('trim', $cc);
		$cc = array_filter($cc);
		$cc = implode(',',$cc);
		$bcc = explode(',',$request['bccmail']);
		$bcc = array_map('trim', $bcc);
		$bcc = array_filter($bcc);
		$bcc = implode(',',$bcc);
		
		$to_mail = $_REQUEST['to_mail'];
		$parentid = $_REQUEST['parent_id'];
		$myids = explode("|",$parentid);
		if (!empty($myids)) {
			$myids = array_filter($myids);
		}
		$to = array();
		if(isset($to_mail) && $to_mail != '') {
			$to = explode(',',$to_mail);
			$to = array_map('trim', $to);
			$to = array_filter($to);
		}
		foreach($myids as $myid) {
			$emailadd = '';
			$realid = explode("@",$myid);
			$nemail = count($realid);
			$mycrmid = $realid[0];
			
			// support to old mode
			if($realid[1] == -1) {
				$emailadd = $adb->query_result($adb->pquery("select email1 from ".$table_prefix."_users where id=?", array($mycrmid)),0,'email1');
				$to[] = $emailadd;
				continue;
			}
			
			$pmodule = getSalesEntityType($mycrmid);
			if ($pmodule == '') {
				$res = $adb->pquery('SELECT * FROM '.$table_prefix.'_users WHERE id = ?', array($mycrmid));//crmv@208173
				if ($res && $adb->num_rows($res)>0) {
					$pmodule = 'Users';
				}
			}
			for ($j=1;$j<$nemail;$j++) {
				$temp = $realid[$j];
				if (strpos($temp,'-') === 0) {
					$pmodule = 'Users';
					$temp = substr($temp,1);
				}
				$myquery = 'select fieldname from '.$table_prefix.'_field where fieldid = ? and '.$table_prefix.'_field.presence in (0,2)';
				$fresult = $adb->pquery($myquery, array($temp));
				// vtlib customization: Enabling mail send from other modules
				$myfocus = CRMEntity::getInstance($pmodule);
				$myfocus->retrieve_entity_info($mycrmid, $pmodule);
				// END
				$fldname = $adb->query_result($fresult,0,'fieldname');
				$emailadd = br2nl($myfocus->column_fields[$fldname]);
			}
			if($emailadd != '') {
				$to[] = $emailadd;
			}
		}

		$mail = new VTEMailer(); // crmv@180739
		$request_backup = $_REQUEST;
		$_REQUEST = $request;
		if ($mail->SMTPDebug) ob_start();
		setMailerProperties($mail,$subject,$description,$from_email,$current_user->column_fields['user_name'],$to,'all');
		$_REQUEST = $request_backup;
		setCCAddress($mail,'cc',$cc);
		setCCAddress($mail,'bcc',$bcc);
		
		// crmv@114260
		if ($from_email) {
			$account = $this->getFromEmailAccount($from_email);
			if ($account > 0) {
				// check if I can use the account smtp
				$msgFocus = CRMEntity::getInstance('Messages');
				if ($msgFocus->hasSmtpAccount($account)) {
					$smtpinfo = $msgFocus->getSmtpConfig($account);
					if ($smtpinfo) {
						$mail->SMTPAuth = ($smtpinfo['smtp_auth'] == "true");
						$mail->Host = $smtpinfo['smtp_server'];
						$mail->Username = $smtpinfo['smtp_username'];
						$mail->Password = $smtpinfo['smtp_password'];
						if ($smtpinfo['smtp_port']) {
							$mail->Port = $smtpinfo['smtp_port'];
						}
					}
				}
			}
		}
		// crmv@114260e
		
		// crmv@180739
		$mail->max_message_size = $this->max_message_size;
		
		// calculate size of forwarded attachments
		$attSize = 0;
		$message_mode = vtlib_purify($_REQUEST['message_mode']);
		$messageid = intval($_REQUEST['message']);
		if ($message_mode == 'forward' && $messageid > 0) {
			$focusMessages = CRMEntity::getInstance('Messages');
			$focusMessages->id = $messageid;		
			$result = $focusMessages->retrieve_entity_info($messageid,'Messages',false);
			if (empty($result)) {	// no errors
				$attSize = $focusMessages->getAttachmentsSize($messageid);
			}
		}

		$return = $mail->checkSend($attSize, $error); // crmv@201913
		// crmv@180739e
		
		if ($mail->SMTPDebug) {
			$smtplog = ob_get_contents();
			$smtplog = "Subject: {$subject}\nFrom: {$from_email}\nTo: ".implode(', ',$to)."\nDate: ".date('Y-m-d H:i:s')."\nSize: $message_size"."\n\n".$smtplog; // crmv@192217
			ob_end_clean();
			if (!empty($error)) $smtplog .= "\n".$error; // crmv@201913
			file_put_contents('logs/checkBeforeSending.log',$smtplog."\n\n--------------------------------------\n\n",FILE_APPEND);
		}
		return $return;
	}
	//crmv@51130e	crmv@55094e
	
	//crmv@114260
	public function checkSmtpServer($accountid = 0) {
		global $adb, $table_prefix;
		
		// check account server
		if ($accountid > 0) {
			$msg = CRMEntity::getInstance('Messages');
			$hasit = $msg->hasSmtpAccount($accountid);
			if ($hasit) return true;
		}
		
		// check global smtp server
		//crmv@157490
		$serverConfigUtils = ServerConfigUtils::getInstance();
		return ($serverConfigUtils->checkConfiguration('email'));
		//crmv@157490e
	}
	//crmv@114260e
	
	//crmv@167238
	public function checkDraftFolder($accountid = 0) {
		if ($accountid == 0) return false;
		
		$messageFocus = CRMEntity::getInstance('Messages');
		$messageFocus->setAccount($accountid);
		$specialFolders = $messageFocus->getSpecialFolders(false);
		$folder = $specialFolders['Drafts'];
		if (empty($folder)) {
			return false;
		} else {
			return true;
		}
	}
	//crmv@167238e
	
	//crmv@80029
	/*
	 * extends the class and use this method in order to change the object $mail
	 * ex. you can change the server smtp or the sender, the recipients, ecc.
	 */
	//function overwriteMailConfiguration(&$mail) {}
	//crmv@80029e
	
	// crmv@129149 crmv@131904
	function add2SendNotQueue($to_email, $from_name, $from_email, $subject, $other_params, $sendAfter = null) {
		global $adb, $table_prefix;
		$values = array(
			'id' => $adb->getUniqueID($table_prefix.'_emails_not_send_queue'),
			'to_email' => $to_email,
			'from_name' => $from_name,
			'from_email' => $from_email,
			'subject' => $subject,
			'other_params' => Zend_Json::encode($other_params),
			'send_after' => $sendAfter ?: '',
			'queue_date' => date('Y-m-d H:i:s'),
			'status' => 0,
		);
		$adb->pquery("insert into {$table_prefix}_emails_not_send_queue(".implode(",",array_keys($values)).") values(".generateQuestionMarks($values).")", $values);
	}
	function processSendNotQueue() {
		global $adb, $table_prefix, $current_user;
		require_once("modules/Emails/mail.php");
		
		$mail = new VTEMailer(); // crmv@55094 crmv@180739
		
		// clean queue
		$adb->pquery("delete from {$table_prefix}_emails_not_send_queue where status = ?",array(1));
		
		// process queue
		$now = date('Y-m-d H:i:s');
		$sql = "select * from {$table_prefix}_emails_not_send_queue where status = ?
				AND (send_after IS NULL OR send_after = '' OR send_after = '0000-00-00 00:00:00' OR send_after <= '$now')
				order by id";
		$result = $adb->limitpQuery($sql,0,50,array(0));
		if ($result && $adb->num_rows($result) > 0) {
			$userid = '';
			while($row=$adb->fetchByAssoc($result,-1,false)) {
				if ($mail->SMTPDebug) echo "sending message {$row['id']} from {$table_prefix}_emails_send_queue...\n";	//crmv@55094
				$adb->pquery("update {$table_prefix}_emails_not_send_queue set status = ? where id = ?",array(2,$row['id']));
				
				$other_params = Zend_Json::decode($row['other_params']);
				$mail_tmp = '';
				$mail_status = send_mail($other_params['module'],$row['to_email'],$row['from_name'],$row['from_email'],$row['subject'],$other_params['contents'],$other_params['cc'],$other_params['bcc'],$other_params['attachment'],$other_params['emailid'],$other_params['logo'],$other_params['newsletter_params'],$mail_tmp,$other_params['messageid'],$other_params['message_mode'],false);
				if ($mail_status == 1) {
					$adb->pquery("update {$table_prefix}_emails_not_send_queue set status = ?, send_date = ? where id = ?",array(1,date('Y-m-d H:i:s'),$row['id']));
				} else {
					$adb->pquery("update {$table_prefix}_emails_not_send_queue set error = ?, send_date = ? where id = ?",array($mail_status,date('Y-m-d H:i:s'),$row['id']));
				}
				if ($mail->SMTPDebug) echo "\nmessage {$row['id']} from {$table_prefix}_emails_send_queue sent!\n\n";	//crmv@55094
			}
		}
	}
	// crmv@129149e crmv@131904e
	// crmv@187622
	function getScheduleSendingOptions() {
		include_once('modules/SLA/SLA.php');
		$sla_config = SLA::get_module_config();
		$today = date("w");
		$tomorrow = date("w", strtotime('+1 day'));
		$now = strtotime('now');
		$options = array();
		if (!empty($sla_config['hours'][$today])) {
			foreach($sla_config['hours'][$today] as $interval) {
				foreach($interval as $hour) {
					$time = strtotime($hour);
					if ($time > $now) $options[] = array(
						'date_str' => getTranslatedString('Today','CustomView'),
						'date' => date('Y-m-d'),
						'hour' => date('H:i',$time),
					);
				}
			}
		}
		if (!empty($sla_config['hours'][$tomorrow])) {
			foreach($sla_config['hours'][$tomorrow] as $interval) {
				foreach($interval as $hour) {
					$time = strtotime('+1 day '.$hour);
					$options[] = array(
						'date_str' => getTranslatedString('Tomorrow','CustomView'),
						'date' => date('Y-m-d',$time),
						'hour' => date('H:i',$time),
					);
				}
			}
		}
		$options = array_slice($options, 0, 4);
		return $options;
	}
	function getToList($send_mode,$to_mail,$myids) {
		global $adb, $table_prefix;
		$to = array();
		if ($send_mode == 'single') {
			if(isset($to_mail) && $to_mail != '') {
				$to = explode(',',$to_mail);
				$to = array_map('trim', $to);
				$to = array_filter($to);
			}
			foreach($myids as $myid) {
				$emailadd = '';
				$realid = explode("@",$myid);
				$nemail = count($realid);
				$mycrmid = $realid[0];
				
				// support to old mode
				if($realid[1] == -1) {
					$emailadd = $adb->query_result($adb->pquery("select email1 from ".$table_prefix."_users where id=?", array($mycrmid)),0,'email1');
					$to[] = $emailadd;
					continue;
				}
				
				$pmodule = getSalesEntityType($mycrmid);
				if ($pmodule == '') {
                    $res = $adb->pquery('SELECT * FROM '.$table_prefix.'_users WHERE id = ?', array($mycrmid));//crmv@208173
					if ($res && $adb->num_rows($res)>0) {
						$pmodule = 'Users';
					}
				}
				for ($j=1;$j<$nemail;$j++) {
					$temp = $realid[$j];
					if (strpos($temp,'-') === 0) {
						$pmodule = 'Users';
						$temp = substr($temp,1);
					}
					$myquery = 'select fieldname from '.$table_prefix.'_field where fieldid = ? and '.$table_prefix.'_field.presence in (0,2)';
					$fresult = $adb->pquery($myquery, array($temp));
					$fldname = $adb->query_result_no_html($fresult,0,'fieldname');
					// vtlib customization: Enabling mail send from other modules
					$myfocus = CRMEntity::getInstance($pmodule);
					// crmv@77583 - if record is deleted, try to retrieve the address anyway
					$retrieve_err = $myfocus->retrieve_entity_info($mycrmid, $pmodule, false);
					if ($retrieve_err == 'LBL_RECORD_DELETE') {
						$modTabid = getTabid($pmodule);
						$sqlTab = "SELECT tablename, columnname FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?";
						$resTab = $adb->pquery($sqlTab,array($modTabid,$fldname));
						if($resTab && $adb->num_rows($resTab) > 0) {
							$tableField = $adb->query_result_no_html($resTab,0,'tablename');
							$columnname = $adb->query_result_no_html($resTab,0,'columnname');
							$tableindex = $myfocus->tab_name_index[$tableField];
							if (!empty($tableindex) && !empty($columnname)) {
								$resMail = $adb->pquery("SELECT {$columnname} FROM {$tableField} WHERE {$tableindex} = ?", array($mycrmid));
								if($resMail && $adb->num_rows($resMail) > 0) {
									$emailtosend = $adb->query_result($resMail,0,$columnname);
									$emailadd = br2nl($emailtosend);
								}
							}
						}
					} else {
						$emailadd = br2nl($myfocus->column_fields[$fldname]);
					}
					// crmv@77583e
					// END
				}
				if($emailadd != '') {
					$to[] = $emailadd;
				}
			}
		} elseif ($send_mode == 'multiple') {
			if(isset($to_mail) && $to_mail != '') {
				$Exploded_Mail = explode(',',$to_mail);
				$Exploded_Mail = array_map('trim', $Exploded_Mail);
				$Exploded_Mail = array_filter($Exploded_Mail);
				foreach($Exploded_Mail as $mailadd) {
					$to[] = $mailadd;
				}
			}
			if (!empty($myids)) {
				if (!is_array($myids)) {
					$myids = explode('|',$myids);
				}
				foreach($myids as $myid) {
					$subject_send = $subject;
					$description_send = $description;
					$realid = explode("@",$myid);
					$nemail = count($realid);
					$mycrmid = $realid[0];
					
					// support to old mode
					if($realid[1] == -1) {
						$to[] = $emailadd;
						continue;
					}
					
					$pmodule = getSalesEntityType($mycrmid);
					if ($pmodule == '') {
                        $res = $adb->pquery('SELECT * FROM '.$table_prefix.'_users WHERE id = ?', array($mycrmid));//crmv@208173
						if ($res && $adb->num_rows($res)>0) {
							$pmodule = 'Users';
						}
					}
					for ($j=1;$j<$nemail;$j++) {
						$temp = $realid[$j];
						if (strpos($temp,'-') === 0) {
							$pmodule = 'Users';
							$temp = substr($temp,1);
						}
						$myquery = 'Select fieldname from '.$table_prefix.'_field where fieldid = ? and '.$table_prefix.'_field.presence in (0,2)';
						$fresult = $adb->pquery($myquery, array($temp));
						// vtlib customization: Enabling mail send from other modules
						$myfocus = CRMEntity::getInstance($pmodule);
						$myfocus->retrieve_entity_info($mycrmid, $pmodule);
						// END
						$fldname = $adb->query_result($fresult,0,'fieldname');
						$emailadd = br2nl($myfocus->column_fields[$fldname]);
						if($emailadd != '') {
							$to[] = $emailadd;
						}
					}
				}
			}
		}
		return $to;
	}
	function sendNow($messagesid) {
		// set date to now, scheduled to 0 and delete the message in folder vteScheduled
		global $adb, $table_prefix;
		$res = $adb->pquery("update {$table_prefix}_emails_send_queue set scheduled = ?, date = ? where messagesid = ? and status in (?,?)",array(0,date('Y-m-d H:i:s'),$messagesid,0,3));
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			$adb->pquery("update {$table_prefix}_messages set deleted = ?, modifiedtime = ? where messagesid = ?",array(1,date('Y-m-d H:i:s'),$messagesid));
		}
	}
	function schedule($messagesid, $date) {
		global $adb, $table_prefix;
		list($d,$h) = explode(' ',$date);
		$date = adjustTimezone(getValidDBInsertDateValue($d).' '.$h, 0, $this->userTimezone, true);
		$res = $adb->pquery("update {$table_prefix}_emails_send_queue set status = ?, date = ? where messagesid = ? and status in (?,?)",array(0,$date,$messagesid,0,3));
		if ($res && $adb->getAffectedRowCount($res) > 0) {
			$adb->pquery("update {$table_prefix}_messages set mdate = ? where messagesid = ?",array($date,$messagesid));
		}
	}
	function setScheduleStatus($messagesid, $status) {
		global $adb, $table_prefix;
		$adb->pquery("update {$table_prefix}_emails_send_queue set status = ? where messagesid = ? and status in (?,?)",array($status,$messagesid,0,3));
	}
	// crmv@187622e
	
	// crmv@191584
	function saveUnknownContacts($to, $cc=array(), $bcc=array()) {
		global $current_user;
		$VTEP = VTEProperties::getInstance();
		if (!$VTEP->getProperty('modules.emails.save_unknown_contacts')) return false;
		
		if (!is_array($to)) $to = explode(",",trim($to,","));
		if (!is_array($cc)) $cc = explode(",",trim($cc,","));
		if (!is_array($bcc)) $bcc = explode(",",trim($bcc,","));
		if (!empty($cc)) {
			foreach($cc as $e) {
				if (!empty($e) && !in_array($e,$to)) $to[] = $e;
			}
		}
		if (!empty($bcc)) {
			foreach($bcc as $e) {
				if (!empty($e) && !in_array($e,$to)) $to[] = $e;
			}
		}
		
		if (!empty($to)) {
			require_once('include/utils/EmailDirectory.php');
			$emailDirectory = new EmailDirectory();
			$modules = $emailDirectory->getModules();
			$focusMessages = CRMEntity::getInstance('Messages');
			
			foreach($to as $t) {
				$arr = $focusMessages->parseAddressList($t);
				$name = $arr[0]['name'];
				$email = $arr[0]['email'];
				if (empty($name)) $name = $email;
				$entity = $focusMessages->getEntitiesFromEmail($email, false, false, $modules, true);
				if (empty($entity) || empty($entity['crmid'])) {
					// unknown email address
					$focusContacts = CRMEntity::getInstance('Contacts');
					$presetFields = $focusMessages->getPopupQCreateValues('Contacts', array(), $email, $name);
					if (is_array($presetFields)) {
						foreach($presetFields as $k=>$v) {
							$focusContacts->column_fields[$k] = $v;
						}
					}
					$focusContacts->column_fields['emailoptout'] = 1;
					$focusContacts->column_fields['assigned_user_id'] = $current_user->id;
					$focusContacts->save('Contacts');
					$focusNewsletter = CRMEntity::getInstance('Newsletter');
					$focusNewsletter->lockReceivingNewsletter($email,'lock');
				}
			}
		}
	}
	// crmv@191584e
}
/** Function to get the emailids for the given ids form the request parameters
 *  It returns an array which contains the mailids and the parentidlists
*/

function get_to_emailids($module)
{
	global $adb,$table_prefix;
	if(isset($_REQUEST["field_lists"]) && $_REQUEST["field_lists"] != "")
	{
		$field_lists = $_REQUEST["field_lists"];
		if (is_string($field_lists)) $field_lists = explode(":", $field_lists);
		$query = 'select columnname,fieldid,tablename from '.$table_prefix.'_field where fieldid in ('. generateQuestionMarks($field_lists) .') and '.$table_prefix.'_field.presence in (0,2)'; //crmv@138830
		$result = $adb->pquery($query, array($field_lists));
		$columns = Array();
		$idlists = '';
		$mailids = '';
		while($row = $adb->fetch_array($result))
    	{
			$columns[$row['columnname']] = $row['tablename']; // crmv@138830
			$fieldid[$row['columnname']] = $row['fieldid']; // crmv@166315
		}
		$columnlists = implode(',',array_keys($columns)); // crmv@138830
		//crmv@27096	//crmv@27917
		$idarray = getListViewCheck($module);
		if (empty($idarray)) {
			$idstring = $_REQUEST['idlist'];
		} else {
			$idstring = implode(':',$idarray);
		}
		//crmv@27096e	//crmv@27917e
		$single_record = false;
		if(!strpos($idstring,':'))
		{
			$single_record = true;
		}
		$crmids = str_replace(':',',',$idstring);
		$crmids = explode(",", $crmids);
		switch($module)
		{
			case 'Leads':
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_leaddetails inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_leaddetails.leadid left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_crmentity.deleted=0 and ((ltrim('.$table_prefix.'_leaddetails.email) is not null) or (ltrim('.$table_prefix.'_leaddetails.yahooid) is not null)) and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Contacts':
				//email opt out funtionality works only when we do mass mailing.
				if(!$single_record)
					$concat_qry = '(((ltrim('.$table_prefix.'_contactdetails.email) is not null)  or (ltrim('.$table_prefix.'_contactdetails.yahooid) is not null))) and '; //crmv@82025 removed emailoptout check
				else
					$concat_qry = '((ltrim('.$table_prefix.'_contactdetails.email) is not null)  or (ltrim('.$table_prefix.'_contactdetails.yahooid) is not null)) and ';
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_contactdetails inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_contactdetails.contactid left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid where '.$table_prefix.'_crmentity.deleted=0 and '.$concat_qry.'  '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Accounts':
				//added to work out email opt out functionality.
				if(!$single_record)
					$concat_qry = '(((ltrim('.$table_prefix.'_account.email1) is not null) or (ltrim('.$table_prefix.'_account.email2) is not null))) and '; //crmv@82025 removed emailoptout check
				else
					$concat_qry = '((ltrim('.$table_prefix.'_account.email1) is not null) or (ltrim('.$table_prefix.'_account.email2) is not null)) and ';
				$query = 'select crmid,accountname as entityname,'.$columnlists.' from '.$table_prefix.'_account inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_account.accountid left join '.$table_prefix.'_accountscf on '.$table_prefix.'_accountscf.accountid = '.$table_prefix.'_account.accountid where '.$table_prefix.'_crmentity.deleted=0 and '.$concat_qry.' '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Vendors':
				$query = 'select crmid,vendorname as entityname,'.$columnlists.' from '.$table_prefix.'_vendor inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_vendor.vendorid left join '.$table_prefix.'_vendorcf on '.$table_prefix.'_vendorcf.vendorid = '.$table_prefix.'_vendor.vendorid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			//crmv@48167 crmv@138830
			default:
				$focus = CRMEntity::getInstance($module);
				$query = 
					"select crmid, $columnlists from {$focus->table_name} 
					inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$focus->table_name}.{$focus->tab_name_index[$focus->table_name]} ";
				$tables = array_unique(array_values($columns));
				$tables = array_diff($tables, array($focus->table_name, "{$table_prefix}_crmentity"));
				foreach ($tables as $table) {
					if (array_key_exists($table, $focus->tab_name_index)) {
						$query .= " left join {$table} on {$table}.{$focus->tab_name_index[$table]} = {$focus->table_name}.{$focus->tab_name_index[$focus->table_name]}";
					}
				}
				$query .= " where deleted = 0 and {$focus->table_name}.{$focus->tab_name_index[$focus->table_name]} in (".generateQuestionMarks($crmids).")";
				break;
			//crmv@48167e crmv@138830e
		}
		$result = $adb->pquery($query, array($crmids));
		while($row = $adb->fetch_array($result))
		{
			$name = $row['entityname'];
			//crmv@48167
			if (empty($name)) {
				$tmp = getEntityName($module, $row['crmid']);
				$name = $tmp[$row['crmid']];
			}
			//crmv@48167e
			// crmv@138830
			foreach ($columns as $column=>$table) {
				if ($row[$column] != NULL && $row[$column] !='') {
					$idlists .= $row['crmid'].'@'.$fieldid[$column].'|'; // crmv@166315
					$mailids .= $name.'<'.$row[$column].'>,';
				}
			}
			// crmv@138830e
		}
		$return_data = Array('idlists'=>$idlists,'mailds'=>$mailids);
	} else {
		$return_data = Array('idlists'=>"",'mailds'=>"");
	}
	return $return_data;

}

//added for attach the generated pdf with email
function pdfAttach($obj,$module,$file_name,$id)
{
	global $log;
	$log->debug("Entering into pdfAttach() method.");

	global $adb, $current_user,$table_prefix;
	global $upload_badext;
	$date_var = date('Y-m-d H:i:s');

	$ownerid = $obj->column_fields['assigned_user_id'];
	if(!isset($ownerid) || $ownerid=='')
		$ownerid = $current_user->id;

	$current_id = $adb->getUniqueID($table_prefix."_crmentity");

	$upload_file_path = decideFilePath();

	//crmv@31456
	if (isset($_REQUEST['draft_id']) && !in_array($_REQUEST['draft_id'],array('','undefined'))) {
		$res = $adb->pquery("SELECT
							  {$table_prefix}_attachments.attachmentsid
							FROM {$table_prefix}_attachments
							  INNER JOIN {$table_prefix}_seattachmentsrel
							    ON {$table_prefix}_attachments.attachmentsid = {$table_prefix}_seattachmentsrel.attachmentsid
							  INNER JOIN {$table_prefix}_crmentity
							    ON {$table_prefix}_crmentity.crmid = {$table_prefix}_attachments.attachmentsid
							WHERE {$table_prefix}_crmentity.deleted = 0
							    AND {$table_prefix}_seattachmentsrel.crmid = ? AND {$table_prefix}_attachments.name = ?",array($_REQUEST['draft_id'],$file_name));
		if ($res && $adb->num_rows($res) > 0) {
			$query = "insert into {$table_prefix}_seattachmentsrel values(?,?)";
			$adb->pquery($query, array($id, $adb->query_result($res,0,'attachmentsid')));
		}
		return true;
	}
	//crmv@31456

	//Copy the file from temporary directory into storage directory for upload
	$source_file_path = "storage/".$file_name;
	if (!is_file($source_file_path)) {
		return false;
	}
	$status = copy($source_file_path, $upload_file_path.$current_id."_".$file_name);
	//Check wheather the copy process is completed successfully or not. if failed no need to put entry in attachment table
	if($status)
	{
		// crmv@150773
		$query1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values(?,?,?,?,?,?)";
		$params1 = array($current_id, $current_user->id, $ownerid, $module." Attachment", $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
		$adb->pquery($query1, $params1);
		// crmv@150773e

		$query2="insert into ".$table_prefix."_attachments(attachmentsid, name, description, type, path) values(?,?,?,?,?)";
		$params2 = array($current_id, $file_name, $obj->column_fields['description'], 'pdf', $upload_file_path);
		$adb->pquery($query2, $params2);

		$query3='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
		$adb->pquery($query3, array($id, $current_id));

		// Delete the file that was copied
		checkFileAccessForDeletion($source_file_path); // crmv@37463
		unlink($source_file_path);

		return true;
	}
	else
	{
		$log->debug("pdf not attached");
		return false;
	}
}
//this function check email fields profile permission as well as field access permission
function emails_checkFieldVisiblityPermission($fieldname) {
	global $current_user;
	$ret = getFieldVisibilityPermission('Emails',$current_user->id,$fieldname);
	return $ret;
}

//crmv@25356
function setAddressInfo($idlist, $to_email_array=Array(), $cleanAdv=false) {
	$tmp = explode('|',$idlist);
	$autosuggest = '';
	array_walk($to_email_array,'addressClean');
	if ($cleanAdv) {
		array_walk($to_email_array,'addressCleanAdv');
	}
	$to_email_array = array_filter($to_email_array);
	$to_email_array_tmp = $to_email_array;
	if (!empty($tmp)) {
		foreach($tmp as $k => $t) {
			if ($t == '') {
				continue;
			}
			$id = explode('@',$t);
			$crmid = $id[0];
			$fieldid = $id[1];
			//crmv@2043m
			if ($crmid == '' || $fieldid == '') {
				continue;
			}
			//crmv@2043me
			//crmv@30434 - support to old mode
			if ($fieldid == -1){
				$mod = 'Users';
				$name = array($crmid => getUserFullName($crmid));
				$em = getUserEmail($crmid);
			}
			//crmv@30434e
			elseif (strpos($fieldid,'-') === 0) {
				$mod = 'Users';
				$fieldid = substr($fieldid,1);
				$name = array($crmid => getUserFullName($crmid));
				$em = getEmailFromIdlist($mod,$crmid,$fieldid);
			} else {
				$mod = getSalesEntityType($crmid);
				$name = getEntityName($mod,array($crmid));
				$em = getEmailFromIdlist($mod,$crmid,$fieldid);
				$to_email_array_tmp[] = $em; // crmv@167842
			}
			if (in_array($em,$to_email_array)) {
				unset($to_email_array[array_search($em,$to_email_array)]);
			}

			$autosuggest .= '<span id="to_'.$t.'" class="addrBubble">'.$name[$crmid]
			.'<div id="to_'.$t.'_parent_id" style="display:none;">'.$t.'</div>'
			.'<div id="to_'.$t.'_parent_name" style="display:none;">'.$name[$crmid].'</div>'
			.'<div id="to_'.$t.'_hidden_toid" style="display:none;">'.$em.'</div>'
			.'<div id="to_'.$t.'_remove" class="ImgBubbleDelete" onClick="removeAddress(\'to\',\''.$t.'\');"><i class="vteicon small">clear</i></div>'
			.'</span>';
		}
	}
	return array('autosuggest'=>$autosuggest,'to_mail'=>implode(', ',array_diff($to_email_array_tmp,$to_email_array)),'other_to_mail'=>implode(', ',$to_email_array));
}
function addressClean(&$to_email_array) {
	$to_email_array = trim($to_email_array);
}
function addressCleanAdv(&$to_email_array) {
	$separatorl = strpos($to_email_array,'<');
	$separatorr = strpos($to_email_array,'>');
	if ($separatorl !== false && $separatorr !== false) {
		$to_email_array = substr($to_email_array,$separatorl+1,($separatorr-$separatorl-1));
	}
}
function getEmailFromIdlist($module,$crmid,$fieldid) {
	global $adb,$table_prefix;
	if ($fieldid != '') {
		$email = '';
		$result = $adb->pquery('select columnname, tablename from '.$table_prefix.'_field where fieldid = ?',array($fieldid));
		$columnname = $adb->query_result($result,0,'columnname');
		$tablename = $adb->query_result($result,0,'tablename');
		$moduleInstance = CRMEntity::getInstance($module);
		$result = $adb->pquery('select '.$columnname.' from '.$tablename.' where '.$moduleInstance->tab_name_index[$tablename].' = ?',array($crmid));
		if ($result && $adb->num_rows($result)>0) {
			$email = $adb->query_result($result,0,$columnname);
		}
		return $email;
	}
}
//crmv@25356e
//crmv@2043m
function getIdListReplyMailConverter($record, $email_list) {
	global $adb,$table_prefix;
	$module = getSalesEntityType($record);
	$focus = CRMEntity::getInstance($module);
	$query = "SELECT fieldid,tablename,columnname FROM ".$table_prefix."_field WHERE tabid=? and uitype=13";
	$result = $adb->pquery($query, array(getTabid($module)));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			foreach($email_list as $email) {
				$query1 = 'select '.$row['columnname'].' from '.$row['tablename'].' where '.$focus->tab_name_index[$row['tablename']].' = ? and '.$row['columnname'].' = ?';
				$result1 = $adb->pquery($query1,array($record, $email));
				if ($result1 && $adb->num_rows($result1) > 0) {
					return "$record@".$row['fieldid'].'|';
				}
			}
		}
	}
	return '';
}
function getFieldList($module) {
	global $adb,$table_prefix;
	$ids = array();
	$query = "SELECT fieldid FROM ".$table_prefix."_field WHERE tabid=? and uitype=13 and presence IN (0,2)";
	$result = $adb->pquery($query, array(getTabid($module)));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$ids[] = $row['fieldid'];
		}
	}
	return $ids;
}
//crmv@2043me
?>