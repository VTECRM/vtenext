<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@183346 */

require_once(dirname(__FILE__).'/Base.php');
require_once(dirname(__FILE__).'/Email.php');
require_once('modules/ModNotifications/ModNotifications.php');

class PMActionModNotification extends PMActionBase {
	
	var $fields = array(
		'description' => array('label'=>'Description','type'=>'string','uitype'=>19,'typeofdata'=>'V~M'),
		'related_to' => array('label'=>'Related To','type'=>'reference','uitype'=>10,'typeofdata'=>'I~O'),
		'creator' => array('label'=>'Creator','type'=>'reference','uitype'=>52,'typeofdata'=>'I~M','relatedmods'=>array('Users')),
		'assigned_user_id' => array('label'=>'Assigned To','type'=>'owner','uitype'=>53,'typeofdata'=>'I~M'),
		'subject' => array('label'=>'Subject','type'=>'string','uitype'=>1,'typeofdata'=>'V~O'),
		'from_email' => array('label'=>'From Email','type'=>'email','uitype'=>13,'typeofdata'=>'E~O'),
		'from_email_name' => array('label'=>'From Email Name','type'=>'string','uitype'=>1,'typeofdata'=>'V~O'),
	);
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		$module = 'ModNotifications';
		$focusModNotifications = ModNotifications::getInstance();

		$_REQUEST['enable_editoptions'] = 'yes';
		$_REQUEST['editoptionsfieldnames'] = implode('|',array_keys($this->fields));
		
		$PMUtils = ProcessMakerUtils::getInstance();
		if ($action_id != '') {
			$metadata = $PMUtils->getMetadata($id,$elementid);
			$metadata_action = $metadata['actions'][$action_id];
			$metadata_form = $metadata_action['form'];
			if (!empty($metadata_form)) {
				foreach($metadata_form as $name => $value) {
					$col_fields[$name] = $value;
					$_REQUEST[$name] = $value;
				}
			}
			$smarty->assign('METADATA', $metadata_action);
		} else {
			// in create se default
			global $HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID;
			$col_fields['creator'] = intval(Users::getActiveAdminId());
			$col_fields['subject'] = 'Process Notification';
			$col_fields['from_email'] = $HELPDESK_SUPPORT_EMAIL_ID;
			$col_fields['from_email_name'] = $HELPDESK_SUPPORT_NAME;
		}
		
		$description = getOutputHtml($this->fields['description']['uitype'], 'description', $this->fields['description']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['description']['typeofdata']);
		$description[] = 1;

		$relatedmods = array_keys($focusModNotifications->getEnableModuleSettings());
		$related_to = getOutputHtml($this->fields['related_to']['uitype'], 'related_to', $this->fields['related_to']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['related_to']['typeofdata'], array('relatedmods'=>implode(',',$relatedmods)));
		$related_to[] = 2;
		
		$assigned_user_id = getOutputHtml($this->fields['assigned_user_id']['uitype'], 'assigned_user_id', $this->fields['assigned_user_id']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['assigned_user_id']['typeofdata']);
		$assigned_user_id[] = 3;
		
		$creator = getOutputHtml($this->fields['creator']['uitype'], 'creator', $this->fields['creator']['label'], 100, $col_fields, 1, $module, 'edit', 1, $this->fields['creator']['typeofdata'], array('relatedmods'=>implode(',',$this->fields['creator']['relatedmods'])));
		$creator[] = 4;
		
		$subject = getOutputHtml($this->fields['subject']['uitype'], 'subject', $this->fields['subject']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['subject']['typeofdata']);
		$subject[] = 5;
		
		$from_email = getOutputHtml($this->fields['from_email']['uitype'], 'from_email', $this->fields['from_email']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['from_email']['typeofdata']);
		$from_email[] = 6;
		
		$from_email_name = getOutputHtml($this->fields['from_email_name']['uitype'], 'from_email_name', $this->fields['from_email_name']['label'], 100, $col_fields, 1, $module, '', 1, $this->fields['from_email_name']['typeofdata']);
		$from_email_name[] = 7;
		
		$blocks = array(
			'LBL_MODNOTIFICATION_INFORMATION' => array(
				'blockid' => 0,
				'panelid' => 0,
				'label' => getTranslatedString('LBL_MODNOTIFICATION_INFORMATION',$module),
				'fields' => array(
					array(
						$description,
					),
					array(
						$creator,
						$assigned_user_id,
					),
					array(
						$related_to,
					),
				)
			),
			'LBL_EMAIL_INFORMATION' => array(
				'blockid' => 0,
				'panelid' => 0,
				'label' => getTranslatedString('LBL_EMAIL_INFORMATION',$module).' - <i>'.getTranslatedString('LBL_EMAIL_INFORMATION_NOTE',$module).'</i>',
				'fields' => array(
					array(
						$subject,
						$from_email
					),
					array(
						$from_email_name
					),
				)
			),
		);
		$smarty->assign("BLOCKS",$blocks);
		
		$smarty->assign("MODULE",$module);
		$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
	}
	
	function execute($engine,$actionid) {
		
		$action = $engine->vte_metadata['actions'][$actionid];
		
		$engine->log("Action Notification","action $actionid - {$action['action_title']}");
		
		$emailObj = new PMActionEmail();
		$params = array();

		foreach($this->fields as $fieldname => $fieldinfo) {
			$params[$fieldname] = $action['form'][$fieldname];
		}
		$emailObj->cycleIndex = $this->cycleIndex;
		$emailObj->cycleRow = $this->cycleRow;
		$emailObj->replaceParams($engine, $params, $actionid, array('related_to'), array('creator','assigned_user_id'), $this->cycleRow['row']['record_id']);//crmv@203075
		if (strpos($params['related_to'],'x')) list(,$params['related_to']) = explode('x',$params['related_to']);
		if (strpos($params['assigned_user_id'],'x')) list(,$params['assigned_user_id']) = explode('x',$params['assigned_user_id']);
		$params['mod_not_type'] = 'Generic';
		$focus = ModNotifications::getInstance();
		$notified_users = $focus->saveFastNotification($params);
		
		if (!empty($notified_users)) {
			$engine->log("Action Notification","action $actionid SUCCESS");
		} else {
			$engine->log("Action Notification","action $actionid FAILED: No users notified");
		}
	}
}