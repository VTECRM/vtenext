<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/CustomView/CustomView.php');

global $current_user;
global $adb,$table_prefix;

$idlist = vtlib_purify($_POST['idlist']);
$viewid = vtlib_purify($_REQUEST['viewname']);
$camodule=vtlib_purify($_REQUEST['return_module']);
$storearray = explode(";",$idlist);
if(isset($viewid) && trim($viewid) != "")
{
	$oCustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
	$CustomActionDtls = $oCustomView->getCustomActionDetails($viewid);
	if(isset($CustomActionDtls))
	{
		$subject = $CustomActionDtls["subject"];
		$contents = $CustomActionDtls["content"];
	}
}

if(trim($subject) != "")
{
	if(isset($storearray) && $camodule != "")
	{
		foreach($storearray as $id)
		{
			if($id == '') continue;
			if($camodule == "Contacts")
			{
				$sql="select * from ".$table_prefix."_contactdetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_contactdetails.contactid where ".$table_prefix."_crmentity.deleted =0 and ".$table_prefix."_contactdetails.contactid=?";
				$result = $adb->pquery($sql, array($id));
				$camodulerow = $adb->fetch_array($result);
				if(isset($camodulerow))
				{
					$emailid = $camodulerow["email"];
					$otheremailid = $camodulerow["otheremail"];
					$yahooid = $camodulerow["yahooid"];

					if(trim($emailid) != "")
					{
						SendMailtoCustomView($camodule,$id,$emailid,$current_user->id,$subject,$contents);
					}elseif(trim($otheremailid) != "")
					{
						SendMailtoCustomView($camodule,$id,$otheremailid,$current_user->id,$subject,$contents);
					}elseif(trim($yahooid) != "")
					{
						SendMailtoCustomView($camodule,$id,$yahooid,$current_user->id,$subject,$contents);
					}
					else
					{
						$adb->println("There is no email id for this Contact. Please give any email id.");
					}
				}

			}elseif($camodule == "Leads")
			{
				$sql="select * from ".$table_prefix."_leaddetails inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_leaddetails.leadid where ".$table_prefix."_crmentity.deleted =0 and ".$table_prefix."_leaddetails.leadid=?";
				$result = $adb->pquery($sql, array($id));
				$camodulerow = $adb->fetch_array($result);
				if(isset($camodulerow))
				{
					$emailid = $camodulerow["email"];
					$yahooid = $camodulerow["yahooid"];

					if(trim($emailid) != "")
					{
						SendMailtoCustomView($camodule,$id,$emailid,$current_user->id,$subject,$contents);
					}
					elseif($trim($yahooid) != "")
					{
						SendMailtoCustomView($camodule,$id,$yahooid,$current_user->id,$subject,$contents);
					}
					else
					{
						$adb->println("There is no email id for this Lead. Please give any email id.");
					}
				}
			}elseif($camodule == "Accounts")
			{
				$sql="select * from ".$table_prefix."_account inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_account.accountid where ".$table_prefix."_crmentity.deleted =0 and ".$table_prefix."_account.accountid=?";
				$result = $adb->pquery($sql, array($id));
				$camodulerow = $adb->fetch_array($result);
				if(isset($camodulerow))
				{
					$emailid = $camodulerow["email1"];
					$otheremailid = $camodulerow["email2"];

					if(trim($emailid) != "")
					{
						SendMailtoCustomView($camodule,$id,$emailid,$current_user->id,$subject,$contents);
					}
					elseif(trim($otheremailid) != "")
					{
						SendMailtoCustomView($camodule,$id,$otheremailid,$current_user->id,$subject,$contents);
					}
					else
					{
						$adb->println("There is no email id for this Account. Please give any email id.");
					}
				}	
			}
		}
	}
}

function SendMailtoCustomView($module,$id,$to,$current_user_id,$subject,$contents)
{
	global $adb, $table_prefix;

	$mail = new VTEMailer(); // crmv@180739

	$mail->Subject = $subject;
	$mail->Body    = nl2br($contents);
	$mail->IsSMTP();

	if($current_user_id != '')
	{
		$sql = "select * from ".$table_prefix."_users where id= ?";
		$result = $adb->pquery($sql, array($current_user_id));
		$from = $adb->query_result($result,0,'email1');
		$initialfrom = $adb->query_result($result,0,'user_name');
	}
	//crmv@157490
	$serverConfigUtils = ServerConfigUtils::getInstance();
	$serverConfig = $serverConfigUtils->getConfiguration('email', array('server','server_username','server_password','smtp_auth'));
	$mail_server = $serverConfig['server'];
	$mail_server_username = $serverConfig['server_username'];
	$mail_server_password = $serverConfig['server_password'];
	$smtp_auth = $serverConfig['smtp_auth'];
	//crmv@157490e
	$adb->println("Mail Server Details : '".$mail_server."','".$mail_server_username."','".$mail_server_password."'");
	$_REQUEST['server']=$mail_server;

	$mail->Host = $mail_server;
	$mail->SMTPAuth = $smtp_auth;
	$mail->Username = $mail_server_username;
	$mail->Password = $mail_server_password;
	$mail->From = $from;
	$mail->FromName = $initialfrom;

	$mail->AddAddress($to);
	$mail->AddReplyTo($from);
	$mail->WordWrap = 50;

	$mail->IsHTML(true);
	$mail->AltBody = "This is the body in plain text for non-HTML mail clients";

	$adb->println("Mail sending process : To => '".$to."', From => '".$from."'");
	if(!$mail->Send())
	{
		$adb->println("(CustomView/SendMailAction.php) Error in Mail Sending : ".$mail->ErrorInfo);
		$errormsg = "Mail Could not be sent...";
	}
	else
	{
		$adb->println("(CustomView/SendMailAction.php) Mail has been Sent to => ".$to);
	}

}
header("Location: index.php?action=index&module=$camodule&viewname=$viewid");
?>