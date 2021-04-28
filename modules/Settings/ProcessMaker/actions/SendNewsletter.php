<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@126696 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
require_once('modules/Settings/ProcessMaker/actions/Email.php');

class PMActionSendNewsletter extends PMActionEmail {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		global $table_prefix;
		
		$PMUtils = ProcessMakerUtils::getInstance();
	
		$ctypes = array(
			'new' => getTranslatedString('LBL_CREATE_NEW_CAMPAIGN', 'Settings'),
			'reuse' => getTranslatedString('LBL_REUSE_CAMPAIGN', 'Settings'),
			'existing' => getTranslatedString('LBL_EXISTING', 'APP_STRING'),
			'process' => getTranslatedString('LBL_FROM_PROCESS', 'Settings'),
		);
	
		$smarty->assign("CAMPAIGN_TYPES", $ctypes);
		
		// send email
		
		$involvedRecords = $PMUtils->getRecordsInvolved($id,true);
		if (!empty($involvedRecords)) {
			$smarty->assign('INVOLVED_RECORDS', Zend_Json::encode($involvedRecords));
		}
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			$vte_metadata_arr = array();
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
			}
			
			// prepare the array of recipients
			if ($metadata_action['recipients']) {
				$reclist = array();
				$list = explode(';', $metadata_action['recipients']);
				$list = array_filter($list);
				foreach ($list as $crmid) {
					if (strpos($crmid, ':') !== false) {
						// process record
						list($metaid,$mod) = explode(':', $crmid);
						$entityname = $PMUtils->getRecordsInvolvedLabel($id, $metaid);
						$shortname = (strlen($entityname) > 20 ? substr($entityname, 0,17) . '...' : $entityname);
					} else {
						$mod = getSalesEntityType($crmid);
						$singlemod = getTranslatedString('SINGLE_'.$mod, $mod);
						$entityname = getEntityName($mod, $crmid, true);
						$shortname = $singlemod.': '.(strlen($entityname) > 20 ? substr($entityname, 0,10) . '...' : $entityname);
					}
					$reclist[] = array(
						'crmid' => $crmid,
						'module' => $mod,
						'entityname' => $entityname,
						'ename' => $shortname,
					);
				}
				$metadata_action['recipients_boxes'] = Zend_Json::encode($reclist);
			}
			
			if ($metadata_action['templateid'] > 0) {
				$templatename = getSingleFieldValue($table_prefix.'_emailtemplates', 'templatename', 'templateid', $metadata_action['templateid']);
				$metadata_action['templatename'] = $templatename;
			}
			
			$smarty->assign('METADATA', $metadata_action);
		}
		
		// filter campaigns and recipients
		if (!empty($involvedRecords)) {
			$involvedCampaigns = $PMUtils->getRecordsInvolvedOptions($id, $metadata_action['campaign_proc_record'], false, array(), array('Campaigns'));
			$smarty->assign('INVOLVED_CAMPAIGNS', $involvedCampaigns);
			
			$involvedRecipients = $PMUtils->getRecordsInvolvedOptions($id, $metadata_action['recipient_proc_record'], false, array(), array('Contacts', 'Accounts', 'Leads', 'Targets'));
			$smarty->assign('INVOLVED_RECIPIENTS', $involvedRecipients);
		}
		
		// campaign id field definition
		// shitty array like the shitty getBlocks
		$cfield = array(
			array(10),						// uitype
			array(
				array(
					'displaylabel' => 'ciao',	// label
					'selected' => '',
					'options' => array('Campaigns'),
				),
				'',							// selected, again
				array('Campaigns'),			// options, again
			),
			array('campaignid'),			// fieldname
			array(
				array(
					'entityid' => $metadata_action['campaignid'], // value
					'displayvalue' => ($metadata_action['campaignid'] > 0 ? getEntityName('Campaigns', $metadata_action['campaignid'], true) : ''),
				),
			),			
			1,								// readonly
			'I~O',							// type of data
			null,							// is_admin
			0,								// fieldid
		);
		$smarty->assign("CAMPAIGN_FIELD", $cfield);
		
		
		require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
		require_once('modules/com_workflow/tasks/VTEmailTask.inc');//crmv@207901
		$task = new VTEmailTask();
		$metaVariables = $task->getMetaVariables();
		$smarty->assign("META_VARIABLES",$metaVariables);
		
		//crmv@106857
		$otherOptions = array();
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$otherOptions = $processDynaFormObj->getFieldsOptions($id,true);
		$PMUtils->getAllTableFieldsOptions($id, $otherOptions);
		$PMUtils->getAllPBlockFieldsOptions($id, $otherOptions); // crmv@195745
		$smarty->assign("OTHER_OPTIONS", Zend_Json::encode($otherOptions));
		//crmv@106857e
		
		// crmv@146671
		$extwsOptions = $PMUtils->getExtWSFields($id);
		$smarty->assign('EXTWS_OPTIONS',addslashes(Zend_Json::encode($extwsOptions)));
		// crmv@146671e
		
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
		
		$elementsActors = $PMUtils->getElementsActors($id,true);
		$smarty->assign('ELEMENTS_ACTORS', Zend_Json::encode($elementsActors));
	}
	
	function execute($engine,$actionid) {
		global $adb, $table_prefix;
		$action = $engine->vte_metadata['actions'][$actionid];
		
		$engine->log("Action SendNewsletter","action $actionid - {$action['action_title']}");
		
		// check the modules
		if (!vtlib_isModuleActive('Campaigns')) {
			$engine->log("Action SendNewsletter","action $actionid FAILED - module Campaigns is not active");
			return;
		}
		if (!vtlib_isModuleActive('Targets')) {
			$engine->log("Action SendNewsletter","action $actionid FAILED - module Targets is not active");
			return;
		}
		if (!vtlib_isModuleActive('Newsletter')) {
			$engine->log("Action SendNewsletter","action $actionid FAILED - module Newsletter is not active");
			return;
		}
		
		$ctype = $action['campaign_type'];
		
		$params = array(
			'subject'=>$action['subject'],
			'sender'=>$action['sender'],
			'sendername'=>$action['sendername'],
			'content' => $action['content'],
		);
		
		$this->replaceParams($engine, $params, $actionid); //crmv@183346
		
		// create/get the campaign
		switch ($ctype) {
			case 'new':
				$campaignid = $this->createCampaign($engine, $action, $actionid);
				break;
			case 'reuse':
				$campaignid = $this->findCampaign($engine, $action, $actionid);
				break;
			case 'existing':
				$campaignid = $action['campaignid'];
				break;
			case 'process':
				list($metaid,$module) = explode(':',$action['campaign_proc_record']);
				$campaignid = $engine->getCrmid($metaid);
				break;
			default:
				$engine->log("Action SendNewsletter","action $actionid FAILED - campaign type $ctype not supported");
				return;
		}
		
		if (empty($campaignid)) {
			$engine->log("Action SendNewsletter","action $actionid FAILED - unable to find/create campaign");
			return;
		}
			
		// create/get the target
		$targetid = $this->findTarget($engine, $action);
		
		// link campaign with target
		$campInstance = CRMEntity::getInstance('Campaigns');
		$campInstance->save_related_module('Campaigns', $campaignid, 'Targets', $targetid);
		
		// create the newsletter
		$newsletterid = $this->createNewsletter($action, $params, $campaignid);
		
		// insert into the process newsletter table 
		$insertparam = array($newsletterid, $engine->processid, $engine->getRunningProcess(), $engine->elementid, $actionid, $params['subject']);
		$adb->pquery("INSERT INTO {$table_prefix}_running_processes_nl (newsletterid, processmakerid, running_process, elementid, actionid, subject) VALUES (".generateQuestionMarks($insertparam).")", $insertparam);
		$adb->updateClob($table_prefix.'_running_processes_nl','body',"newsletterid = ".intval($newsletterid),$params['content']);
		
		// log and exit
		$engine->log("Action SendNewsletter","action $actionid SUCCESS");
		
		$engine->logElement($engine->elementid, array(
			'action_type'=>$action['action_type'],
			'action_title'=>$action['action_title'],
			'campaignid' => $campaignid,
			'targetid' => $targetid,
			'newsletterid'=>$newsletterid,
		));
		
	}
	
	protected function findCampaign($engine, $action, $actionid) {
		global $adb, $table_prefix;
		
		// find the last used campaign
		$res = $adb->limitpQuery("
			SELECT nl.campaignid
			FROM {$table_prefix}_running_processes_nl rpnl
			INNER JOIN {$table_prefix}_newsletter nl ON nl.newsletterid = rpnl.newsletterid
			INNER JOIN {$table_prefix}_campaign ca ON ca.campaignid = nl.campaignid
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = ca.campaignid
			WHERE c.deleted = 0 AND rpnl.processmakerid = ? and rpnl.actionid = ? and rpnl.elementid = ?
			ORDER BY rpnl.newsletterid DESC",
			0,1,
			array($engine->processid, $actionid, $engine->elementid)
		);
		
		if ($res && $adb->num_rows($res) > 0) {
			$campaignid = $adb->query_result_no_html($res, 0, 'campaignid');
		} else {
			// otherwise create a new one
			$campaignid = $this->createCampaign($engine, $action, $actionid);
		}
		
		return $campaignid;
	}
	
	protected function createCampaign($engine, $action, $actionid) {
		$focus = CRMEntity::getInstance('Campaigns');
		$focus->column_fields['campaignname'] = '[PROCESS] '.$action['action_title'];
		$focus->save($focus->modulename);
		
		$campaignid = $focus->id;
		
		return $campaignid;
	}
	
	//crmv@181281
	protected function getRecipientsHash($recids) {
		$idlist = array();
		foreach ($recids as $recmod => $ids) {
			foreach ($ids as $id) {
				$idlist[] = $id;
			}
		}
		$hash = md5(implode(';', $idlist));
		return $hash;
	}
	
	protected function getTargetRealRecipients($engine, $action) {
		$ids = array_filter(explode(';', $action['recipients']));
		$recipients = array();
		foreach ($ids as $id) {
			if (strpos($id, ':') !== false) {
				// record from process
				list($metaid,$recmod) = explode(':',$id);
				$id = $engine->getCrmid($metaid);
			} else {
				// static record
				$recmod = getSalesEntityType($id);
			}
			if (empty($recmod)) continue;
			$recipients[$recmod][] = $id;
		}
		return $recipients;
	}
	//crmv@181281e
	
	protected function findTarget($engine, $action) {
		global $adb, $table_prefix;
		
		$targetname = "[PROCESS] Action {$action['action_title']} ({$action['elementid']})";
		
		$recids = $this->getTargetRealRecipients($engine, $action);
		$hash = $this->getRecipientsHash($recids);
		
		// find the target by name and hash
		$where = " AND targetname = '".$adb->sql_escape_string($targetname)."' AND listhash = '".$adb->sql_escape_string($hash)."'";
		$sql = getListQuery('Targets', $where);
		$sql = replaceSelectQuery($sql, $table_prefix.'_crmentity.crmid');
		
		$res = $adb->query($sql);
		if ($res && $adb->num_rows($res) > 0) {
			$targetid = $adb->query_result_no_html($res, 0, 'crmid');
			return $targetid;
		}
		
		// create it!
		$targetid = $this->createTarget($engine, $action, $targetname, $hash);
		
		return $targetid;
	}
	
	protected function createTarget($engine, $action, $targetname = '', $hash = '') {
		$focus = CRMEntity::getInstance('Targets');
		$focus->column_fields['targetname'] = $targetname;
		$focus->column_fields['listhash'] = $hash;
		$focus->save($focus->modulename);
		
		$targetid = $focus->id;
		
		if ($targetid > 0) {
			// now link the recipients
			$recids = $this->getTargetRealRecipients($engine, $action);
			foreach ($recids as $recmod => $ids) {
				foreach ($ids as $id) {
					$focus->save_related_module('Targets', $targetid, $recmod, $id);
				}
			}
		}
		
		return $targetid;
	}
	
	protected function createNewsletter($action, $params, $campaignid = null) {
		$focus = CRMEntity::getInstance('Newsletter');
		$focus->column_fields['newslettername'] = '[PROCESS] '.$action['action_title'];
		$focus->column_fields['campaignid'] = $campaignid;
		$focus->column_fields['templateemailid'] = $action['templateid']; // TODO!! poi salvalo e usa il dato salvato
		$focus->column_fields['date_scheduled'] = date('Y-m-d');
		$focus->column_fields['time_scheduled'] = date('H:i');
		$focus->column_fields['from_name'] = $params['sendername'];
		$focus->column_fields['from_address'] = $params['sender'];
		$focus->column_fields['scheduled'] = 1;
		
		$focus->save($focus->modulename);
		
		$newsletterid = $focus->id;
		
		// now enqueue entries
		$focus->enqueueTargets();
		
		return $newsletterid;
	}
}