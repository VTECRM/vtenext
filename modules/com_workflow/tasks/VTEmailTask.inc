<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
require_once('modules/com_workflow/VTEmailRecipientsTemplate.inc');//crmv@207901
require_once('modules/Emails/mail.php');
class VTEmailTask extends VTTask{
	// Sending email takes more time, this should be handled via queue all the time.
	public $executeImmediately = false; 

	public function getFieldNames(){
		return array("subject", "content", "sender", "recepient", 'emailcc', 'emailbcc'); //crmv@36510
	}
	public function doTask($entity){
		global $adb, $current_user,$table_prefix;
		
		$logger = VTESystemLogger::getLogger('workflow'); // crmv@172616
		
		$util = new VTWorkflowUtils();

		$admin = $util->adminUser();
		$module = $entity->getModuleName();

		//crmv@36510
		$entityCache = new VTEntityCache($admin);
		//crmv@123476 - removed sender name
		// crmv@167668
		$es = new VTEmailRecipientsTemplate($this->sender);
		$from_email = $es->render($entityCache, $entity->getId());
		// crmv@167668e
		//$username = "$(assigned_user_id : (Users) user_name)";
		//$eu = new VTEmailRecipientsTemplate($username);
		//$from_name = $eu->render($entityCache, $entity->getId());
		if ($from_email == ''){
			$result = $adb->query("select user_name, email1, email2 from {$table_prefix}_users where id=1");
			$from_email = $adb->query_result($result,0,'email1');
			//$from_name  = $adb->query_result($result,0,'user_name');
		}
		//crmv@123476e
		$et = new VTEmailRecipientsTemplate($this->recepient);
		$to_email = $et->render($entityCache, $entity->getId());
		$ecct = new VTEmailRecipientsTemplate($this->emailcc);
		$cc = $ecct->render($entityCache, $entity->getId());
		$ebcct = new VTEmailRecipientsTemplate($this->emailbcc);
		$bcc = $ebcct->render($entityCache, $entity->getId());
		if(strlen(trim($to_email, " \t\n,")) == 0 && strlen(trim($cc, " \t\n,")) == 0 &&
				strlen(trim($bcc, " \t\n,")) == 0) {
			if ($logger) $logger->warning("WORKFLOW #{$this->workflowId} TASK #{$this->id}: [SENDMAIL] [$module] No recipients found"); // crmv@172616
			return ;
		}

		$st = new VTSimpleTemplate($this->subject);
		$subject = $st->render($entityCache, $entity->getId());
		$ct = new VTSimpleTemplate($this->content);
		$content = $ct->render($entityCache, $entity->getId());
		//send mail and link to entity
		$module = $entity->getModuleName();
		$entityid = $entity->getId();
		list($modid, $crmid) = explode('x', $entityid); // crmv@172616
		$parts = explode('x', $entity->get('assigned_user_id'));
		$assigned_user_id = $parts[1];
		global $current_user;
		$current_user = new Users();
		$current_user->retrieve_entity_info($assigned_user_id,'Users');
		$current_user->id = $assigned_user_id;
		//crmv@2963m
		try {
			$params = array(
				'subject'=>$subject,
				'description'=>$content,
				'mfrom'=>$from_email,
				'mfrom_n'=>$from_name,
				'mfrom_f'=>(empty($from_name) ? $from_email : $from_email." <{$from_name}>"),
				'mto'=>$to_email,
				'mcc'=>$cc,
				'mbcc'=>$bcc,
				'assigned_user_id'=>$assigned_user_id,
				'parent_id'=>"$entityid@1|",
			);
			$focus = CRMentity::getInstance('Messages');
			$focus->send($params,false);
			if ($logger) $logger->info("WORKFLOW #{$this->workflowId} TASK #{$this->id}: [SENDMAIL] [$module #$crmid] Mail sent to $to_email"); // crmv@172616
		}
		//crmv@2963me
		catch(Exception $e){
			global $log;
			$log->fatal('Error creating email from wf');
			if ($logger) $logger->error("WORKFLOW #{$this->workflowId} TASK #{$this->id}: [SENDMAIL] [$module #$crmid] Unable to send email: ".$e->getMessage()); // crmv@172616
		}
		$util->revertUser();
		//crmv@36510 e
	}
}