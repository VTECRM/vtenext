<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - fix vari */
/* crmv@33097 - fix vari */
/* crmv@71388 - support for uploaded attachments */

global $login, $userId, $current_user, $currentModule;

$module = $_REQUEST['module'];
$values = $_REQUEST['values']; // no vtlib_purify here, there mught be html stuff in the description

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$currentModule = $module;
	$values = Zend_Json::decode($values);

	if (is_readable('modules/Emails/mailsend.php') && vtlib_isModuleActive("Emails")) {

		require_once('modules/Emails/mail.php');

		// old style

		$to = explode(',', $values['mto_addr']);
		$to = array_unique(array_filter(array_map('trim', $to)));

		$cc = explode(',', $values['mcc_addr']);
		$cc = array_unique(array_filter(array_map('trim', $cc)));

		$bcc = explode(',', $values['mccn_addr']);
		$bcc = array_unique(array_filter(array_map('trim', $bcc)));

		// gather crmids
		$sendCrmid = array();
		$sendCrmid = explode(',', $values['mto_crmid']);
		$sendCrmid = array_merge($sendCrmid, explode(',', $values['mcc_crmid']));
		$sendCrmid = array_merge($sendCrmid, explode(',', $values['mccn_crmid']));
		$parentid = implode('|', $sendCrmid);
		
		// link to other records
		$linkToCrmids = array_filter(explode(',', $values['link_to_crmids']));
		$linkToCrmids = implode('|', $linkToCrmids);

		$from_name = $current_user->user_name;
		$from_address = $current_user->column_fields['email1'];

		$focus = CRMEntity::getInstance('Emails');
		$focus->column_fields['send_mode'] = ($values['send_mode'] ? strtolower($values['send_mode']) : 'single');
		$_REQUEST['from_email'] = ($values['sender'] ? $values['sender'] : $current_user->column_fields['email1']);
		$_REQUEST['to_mail'] = implode(',', $to);
		$_REQUEST['ccmail'] = implode(',', $cc);
		$_REQUEST['bccmail'] = implode(',', $bcc);
		$_REQUEST['subject'] = $values['subject'];
		$_REQUEST['description'] = $values['body'];
		$_REQUEST['parent_id'] = $parentid;
		$_REQUEST['relation'] = $linkToCrmids;	// crmv@54449
		$_REQUEST['message_mode'] = $values['message_mode']; // forward or draft
		$_REQUEST['messageid'] = '';
		$_REQUEST['message'] = $values['messageid']; // crmid of the message (in case of forward, to get the attachments)

		$attachments = array();
		
		// select only these attachments from the original email
		$attach = array_filter(array_unique(explode(',', $values['attachments_ids'])), function($v) {
			return $v !== "" && $v >= 0;
		});
		if ($values['message_mode'] == 'forward') {
			$attachments[] = array(
				'sourcetype' => 'email',
				'content' => $attach,
				'recordid' => $values['messageid'],
			);
		}
		
		// add these attachments uploaded from the app
		$uploads = array_filter(array_unique(explode(',', $values['upload_ids'])), function($v) {
			return $v !== "" && $v >= 0;
		});
		if (count($uploads) > 0) {
			require_once('modules/Touch/vtws/classes/UploadFile.class.php');
			$wsclass = new TouchUploadFile();
			// add other uploaded files
			// retrieve the file information
			$list = $wsclass->getTouchUploadList($uploads);
			$base = 'storage/touch_uploads/';
			foreach ($list as $uinfo) {
				if (is_readable($base.$uinfo['path'])) {
					$attachments[] = array(
						'sourcetype' => 'file',
						'content' => $base.$uinfo['path'],
					);
				}
			}
		}
		
		// set them!
		if (count($attachments) > 0) {
			$_REQUEST['attachments_mode'] = $attachments;
		}

		$success = false;
		require('modules/Emails/mailsend.php');

		if ($success == true) {
			$returnok = true;
			
			// remove the temporary uploads
			if (count($uploads) > 0) {
				$wsclass->removeUploads($uploads);
			}
			
		} else {
			$returnok = false;
			$errormsg = $error_message;
			if ($errormsg == 0) {
				// happen when mail server not configured
				$errormsg = 'Error: SMTP Server not configured';
			}
		}

	} else {
		$returnok = false;
		$errormsg = 'Mailer not found';
	}

	echo Zend_Json::encode(array('success' => $returnok, 'result' => $returndata, 'error' => $errormsg));
}