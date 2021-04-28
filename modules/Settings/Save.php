<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $mod_strings,$enterprise_project;	//crmv@22252
global $table_prefix;
$server=$_REQUEST['server'];
$port=$_REQUEST['port'];
$server_username=$_REQUEST['server_username'];
$server_password=$_REQUEST['server_password'];
$server_type = $_REQUEST['server_type'];
$server_path = vtlib_purify($_REQUEST['server_path']);
$db_update = true;
if($_REQUEST['smtp_auth'] == 'on' || $_REQUEST['smtp_auth'] == 1)
	$smtp_auth = 'true';
else
	$smtp_auth = 'false';

//crmv@157490
$serverConfigUtils = ServerConfigUtils::getInstance();
$serverConfig = $serverConfigUtils->getConfiguration($server_type,array('id'));
$id = $serverConfig['id'];
//crmv@157490e

if($server_type == 'proxy')
{
	$action = 'ProxyServerConfig&proxy_server_mode=edit';
	if (!$sock =@fsockopen($server, $port, $errno, $errstr, 30))
	{
		$error_str = 'error='.sprintf(getTranslatedString('LBL_UNABLE_TO_CONNECT','Settings'),$server.':'.$port);
		$db_update = false;
	}else
	{
		$url = "http://www.google.co.in";
		$proxy_cont = '';
		$sock = fsockopen($server, $port);
		if (!$sock)    {return false;}
		fputs($sock, "GET $url HTTP/1.0\r\nHost: $server\r\n");
		fputs($sock, "Proxy-Authorization: Basic " . base64_encode ("$server_username:$server_password") . "\r\n\r\n");
		while(!feof($sock)) {$proxy_cont .= fread($sock,4096);}
		fclose($sock);
		$proxy_cont = substr($proxy_cont, strpos($proxy_cont,"\r\n\r\n")+4);
		
		if(substr_count($proxy_cont, "Cache Access Denied") > 0)
		{
			$error_str = 'error=LBL_PROXY_AUTHENTICATION_REQUIRED';
			$db_update = false;
		}
		else
		{
			$action = 'ProxyServerConfig';
		}
	}
}

if($server_type == 'ftp_backup')
{
	$action = 'BackupServerConfig&bkp_server_mode=edit&server='.$server.'&server_user='.$server_username.'&password='.$server_password;
	if(!function_exists('ftp_connect')){
		$error_str = 'error=FTP support is not enabled.';
		$db_update = false;
	}else
	{
		$conn_id = @ftp_connect($server);
		if(!$conn_id)
		{
			$error_str = 'error='.sprintf(getTranslatedString('LBL_UNABLE_TO_CONNECT','Settings'),$server);
			$db_update = false;
		}else
		{
			if(!@ftp_login($conn_id, $server_username, $server_password))
			{
				$error_str = 'error=Couldn\'t connect to "'.$server.'" as user "'.$server_username.'"';
				$db_update = false;
			}
			else
			{
				$action = 'BackupServerConfig';
			}
			ftp_close($conn_id);
		}
	}
}

if($server_type == 'local_backup')
{
	$action = 'BackupServerConfig&local_server_mode=edit&server_path="'.$server_path.'"';
	if(!is_dir($server_path)){
		$error_str = 'error1=Folder doesnt Exist or Specified a path which is not a folder';
		$db_update = false;
	}else
	{
		if(!is_writable($server_path))
		{
			$error_str = 'error1=Access Denied to write to "'.$server_path.'"';
			$db_update = false;
		}else
		{
			$action = 'BackupServerConfig';
		}
	}
}

if($server_type == 'asterisk')
{
	$inc_call = $_REQUEST['inc_call'];
	if (!isset($_REQUEST['disable'])){
	$action = 'AsteriskConfig&asterisk_server_mode=edit';
	global $mod_strings, $extension, $ASTERISK_OUTGOING_CONTEXT;
	include_once ("asterisk/phpagi/phpagi-asmanager.php");
	$channel = $extension;
	$context = $ASTERISK_OUTGOING_CONTEXT ;
	$priority = '1';
	$timeout = '';
	$callerid = '';
	$variable = '';
	$account = '';
	$application = '';
	$data = '';
	$as = new AGI_AsteriskManager('',Array('server'=>$server,'port'=>$port,'username'=>$server_username,'secret'=>$server_password));
	$res = $as->connect();
	if (!$res){ 
			$error_str = 'error='.$mod_strings['LBL_ASTERISK_SERVER_CANT_CONNECT'].' "'.$server.'"';
			$db_update = false;
	}
	else {
		$db_update = true;
		$action = 'AsteriskConfig';
	}
	}
	else {
		$db_update = true;
		$action = 'AsteriskConfig';
	}
}

if($server_type == 'proxy' || $server_type == 'ftp_backup' || $server_type == 'local_backup')
{
	if($db_update)
	{
		//crmv@157490
		if (!empty($id) && $server_password == '') $server_password = $serverConfigUtils->getConfiguration($id, array('server_password'), 'id', true);	//crmv@43764
		$serverConfigUtils->saveConfiguration($id, array('server'=>$server,'server_port'=>$port,'server_username'=>$server_username,'server_password'=>$server_password,'server_type'=>$server_type,'smtp_auth'=>$smtp_auth,'server_path'=>$server_path));
		//crmv@157490e
	}
}
if($server_type == 'asterisk')
{
	if($db_update)
	{
		//crmv@157490
		$serverConfigUtils->saveConfiguration($id, array('server'=>$server,'server_port'=>$port,'server_username'=>$server_username,'server_password'=>$server_password,'server_type'=>$server_type,'smtp_auth'=>$smtp_auth,'inc_call'=>$inc_call));
		//crmv@157490e
	}
}
//crmv@7216
if($server_type =='fax')
{
	require_once("modules/Fax/fax_.php");
	global $current_user;
	$service_type = $_REQUEST['service_type'];
	$domain = $_REQUEST['adv_domain'];
	$account = $_REQUEST['adv_account'];
	$prefix = $_REQUEST['adv_prefix'];
	$name = $_REQUEST['adv_name'];
	if ($service_type == 'hylafax'){
		//todo test fax
	}	
	elseif ($service_type == 'fax_mail'){
		$to_fax = getUserFaxId('id',$current_user->id);
		$from_fax = $to_fax;
		$subject = 'Test fax about the fax server configuration.';
		$description = 'Dear '.$current_user->user_name.', <br><br><b> This is a test fax sent to confirm if a fax is actually being sent through the smtp server that you have configured. </b><br>Feel free to delete this mail.<br><br>Thanks  and  Regards,<br> Team '.$enterprise_project.' <br><br>';	//crmv@22252
		if($to_fax != '')
		{
			$fax_status = send_fax('Users',$to_fax,$current_user->user_name,$from_fax,$subject,$description);
			$fax_status_str = $to_fax."=".$fax_status."&&&";
		}
		else
		{
			$fax_status_str = "'".$to_fax."'=0&&&";
		}
		$error_str = getFaxErrorString($fax_status_str);
		$action = 'FaxConfig';
		if($fax_status != 1){
			$action = 'FaxConfig&faxconfig_mode=edit&server_name='.$_REQUEST['server'].'&server_user='.$_REQUEST['server_username'].'&auth_check='.$_REQUEST['smtp_auth'].'&domain='.$_REQUEST['domain'].'&account='.$_REQUEST['account'].'&prefix='.$_REQUEST['prefix'].'&name='.$_REQUEST['name'];	
		}
	}
	if($db_update)
	{
		//crmv@157490
		if (!empty($id) && $server_password == '') $server_password = $serverConfigUtils->getConfiguration($id, array('server_password'), 'id', true);	//crmv@43764
		$serverConfigUtils->saveConfiguration($id, array('server'=>$server,'server_port'=>$port,'server_username'=>$server_username,'server_password'=>$server_password,'server_type'=>$server_type,'smtp_auth'=>$smtp_auth,'service_type'=>$service_type,'domain'=>$domain,'account'=>$account,'prefix'=>$prefix,'name'=>$name));
		//crmv@157490e
	}
}
//crmv@7216e
//crmv@7217
if($server_type =='sms')
{
	require_once("modules/Sms/sms_.php");
	$service_type = $_REQUEST['service_type'];
	$domain = $_REQUEST['adv_domain'];
	$account = $_REQUEST['adv_account'];
	$prefix = $_REQUEST['adv_prefix'];
	$name = $_REQUEST['adv_name'];
	if ($service_type == 'sms_mail'){
			$to_sms = getUserSmsId('id',$current_user->id);
		$from_sms = $to_sms;
		$subject = 'Test sms about the sms server configuration.';
		$description = 'Dear '.$current_user->user_name.', <br><br><b> This is a test sms sent to confirm if a sms is actually being sent through the smtp server that you have configured. </b><br>Feel free to delete this mail.<br><br>Thanks  and  Regards,<br> Team '.$enterprise_project.' <br><br>';	//crmv@22252
		if($to_sms != '')
		{
			$sms_status = send_sms('Users',$to_sms,$current_user->user_name,$from_sms,$subject,$description);
			$sms_status_str = $to_sms."=".$sms_status."&&&";
		}
		else
		{
			$sms_status_str = "'".$to_sms."'=0&&&";
		}
		$error_str = getSmsErrorString($sms_status_str);
		$action = 'SmsConfig';
		if($sms_status != 1){
			$action = 'SmsConfig&smsconfig_mode=edit&server_name='.$_REQUEST['server'].'&server_user='.$_REQUEST['server_username'].'&auth_check='.$_REQUEST['smtp_auth'].'&domain='.$_REQUEST['domain'].'&account='.$_REQUEST['account'].'&prefix='.$_REQUEST['prefix'].'&name='.$_REQUEST['name'];	
		}
	}	
	if($db_update)
	{
		//crmv@157490
		if (!empty($id) && $server_password == '') $server_password = $serverConfigUtils->getConfiguration($id, array('server_password'), 'id', true);	//crmv@43764
		$serverConfigUtils->saveConfiguration($id, array('server'=>$server,'server_port'=>$port,'server_username'=>$server_username,'server_password'=>$server_password,'server_type'=>$server_type,'smtp_auth'=>$smtp_auth,'service_type'=>$service_type,'domain'=>$domain,'account'=>$account,'prefix'=>$prefix,'name'=>$name));
		//crmv@157490e
	}
}
//crmv@7217e
//Added code to send a test mail to the currently logged in user
if($server_type =='email')
{
	//crmv@16265	//crmv@32079
	//crmv@94084
	$action = 'EmailConfig';
	require_once('include/utils/VTEProperties.php');
	$VTEProperties = VTEProperties::getInstance();
	if ($VTEProperties->getProperty('smtp_editable') == '1') {
		$account_smtp = $_REQUEST['account_smtp'];
		//crmv@157490
		if ($account_smtp == '') {
			if ($db_update) $serverConfigUtils->removeConfiguration($server_type);
		} else {
			require_once("modules/Emails/mail.php");
			global $current_user;
		
			$to_email = getUserEmailId('id',$current_user->id);
			$from_email = $to_email;
			$subject = 'Test mail about the mail server configuration.';
			$description = 'Dear '.$current_user->user_name.', <br><br><b> This is a test mail sent to confirm if a mail is actually being sent through the smtp server that you have configured. </b><br>Feel free to delete this mail.<br><br>Thanks  and  Regards,<br> Team '.$enterprise_project.' <br><br>';	//crmv@22252
			if($to_email != '') {
				$mail_status = send_mail('Users',$to_email,$current_user->user_name,$from_email,$subject,$description);
				$mail_status_str = $to_email."=".$mail_status."&&&";
			} else {
				$mail_status_str = "'".$to_email."'=0&&&";
			}
			$error_str = getMailErrorString($mail_status_str);
			
			if($mail_status != 1) {
				$action = 'EmailConfig&emailconfig_mode=edit&server_name='.$_REQUEST['server'].'&server_user='.$_REQUEST['server_username'].'&auth_check='.$_REQUEST['smtp_auth'].'&port='.$_REQUEST['port'].'&account_type=smtp&account_smtp='.$_REQUEST['account_smtp'];
			} else {
				if($db_update) {
					if (!empty($id) && $server_password == '') $server_password = $serverConfigUtils->getConfiguration($id, array('server_password'), 'id', true);	//crmv@43764
					$serverConfigUtils->saveConfiguration($id, array('server'=>$server,'server_port'=>$port,'server_username'=>$server_username,'server_password'=>$server_password,'smtp_auth'=>$smtp_auth,'server_type'=>$server_type,'account'=>$account_smtp));
				}
			}
		}
		//crmv@157490e
	}
	//crmv@94084e
	
	$server_type = 'email_imap';
	//crmv@2963m crmv@157490
	$focusMessages = CRMEntity::getInstance('Messages');
	$old_servers = $focusMessages->getConfiguredAccounts();
	$saved_ids = array();
	$deleted = 0;
	for($i=0;!empty($_REQUEST['account_imap_'.$i]);$i++) {
		if ($_REQUEST['account_imap_deleted_'.$i] == '1') {
			$deleted++;
			continue;
		}
		(array_key_exists($i,$old_servers)) ? $id = $old_servers[$i]['account'] : $id = '';
		$saved_ids[] = $serverConfigUtils->saveConfiguration($id, array('server'=>$_REQUEST['server_imap_'.$i],'server_port'=>$_REQUEST['port_imap_'.$i],'account'=>$_REQUEST['account_imap_'.$i],'ssl_tls'=>$_REQUEST['ssl_tls_imap_'.$i],'domain'=>$_REQUEST['domain_'.$i],'server_type'=>$server_type));
	}
	if (!empty($saved_ids)) {
		$serverConfigUtils->removeConfiguration($server_type, $saved_ids);
	} elseif (empty($saved_ids) && $i > 0 && $i == $deleted) {
		$serverConfigUtils->removeConfiguration($server_type);
	}
	//crmv@2963me crmv@157490e
	//crmv@16265e	//crmv@32079e
}
//While configuring Proxy settings, the submitted values will be retained when exception is thrown - dina
if($server_type == 'proxy' && $error_str != '') {
	header("Location: index.php?module=Settings&parenttab=Settings&action=$action&server=$server&port=$port&server_username=$server_username&$error_str");
} else {
	header("Location: index.php?module=Settings&parenttab=Settings&action=$action&$error_str");
}
?>