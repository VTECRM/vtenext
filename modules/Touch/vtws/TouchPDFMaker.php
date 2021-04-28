<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@39106 */
global $login, $userId, $current_user, $currentModule;

$module = vtlib_purify($_REQUEST['module']);
$subaction = $_REQUEST['subaction'];
$recordid = intval($_REQUEST['record']);
$templateid = intval($_REQUEST['templateid']);


if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	require_once("modules/PDFMaker/PDFMaker.php");
	require_once("modules/PDFMaker/InventoryPDF.php");
	require_once("include/mpdf/mpdf.php");

	$currentModule = $module;
	$returnok = true;
	$errormsg = '';
	$returndata = null;

	if ($subaction == 'savedoc') {
		// the same as modules/PDFMaker/SavePDFDoc.php

		$focus = CRMEntity::getInstance('Documents'); // crmv@39106
		$focus->parentid = $recordid;

		$modFocus = CRMEntity::getInstance($module);
		if ($recordid > 0) {
			$modFocus->retrieve_entity_info($recordid,$module);
			$modFocus->id = $recordid;
		}

		// calculate filename
		$result = $adb->pquery("SELECT fieldname FROM {$table_prefix}_field WHERE uitype=4 AND tabid = ?", array(getTabId($module)));
		$fieldname = $adb->query_result_no_html($result,0,"fieldname");
		if (!empty($modFocus->column_fields[$fieldname])) {
			$file_name = generate_cool_uri($modFocus->column_fields[$fieldname]).".pdf";
		} else {
			$file_name = "doc_".$focus->parentid.date("ymdHi").".pdf";
		}

		// save document
		$focus->column_fields["notes_title"] = vtlib_purify($_REQUEST["notes_title"]);
		$focus->column_fields["assigned_user_id"] = $current_user->id;
		$focus->column_fields["filename"] = $file_name;
		$focus->column_fields["notecontent"] = vtlib_purify($_REQUEST["notecontent"]);
		$focus->column_fields["filetype"] = "application/pdf";
		$focus->column_fields["filesize"] = "";
		$focus->column_fields["filelocationtype"] = "I";
		$focus->column_fields["fileversion"] = '';
		$focus->column_fields["filestatus"] = "on";
		$focus->column_fields["folderid"] = intval($_REQUEST["folderid"]);
		$focus->save("Documents");

		// create and link pdf
		try {
			$language = $current_language;
			createPDFAndSaveFile($templateid,$focus,$modFocus,$file_name,$module,$language);
		} catch (Exception $e) {
			$returnok = false;
			$errormsg = 'Exception thrown during PDF creation';
		}

	} elseif ($subaction == 'sendemail') {

		// generate pdf
		$pdfmaker = new PDFMaker();
		$name = $pdfmaker->generatePDFForEmail($recordid, $module, $templateid, $current_language).'.pdf';
		$pdfpath = "cache/{$name}";

		// move in cache, otherwise gets removed
		rename("storage/{$name}", $pdfpath);

		// send email
		if (false && vtlib_isModuleActive("Messages")){
			// new module
			// TODO!!! use new method sendMail
		} elseif (is_readable('modules/Emails/mailsend.php') && vtlib_isModuleActive("Emails")) {
			// old style

			$to = explode(',', $_REQUEST['recipients']);
			$to = array_unique(array_filter(array_map('trim', $to)));
			$from_name = $current_user->user_name;
			$from_address = $current_user->column_fields['email1'];
			$cc = $bcc = '';
			$subject = $_REQUEST['subject'];
			$description = vtlib_purify($_REQUEST['message']);
			$logo = 0;
			$mail_tmp = '';
			$messageid = '';
			$message_mode = '';
			$_REQUEST['filename_hidden'] = $pdfpath;

			require_once('modules/Emails/mail.php');
			$mail_status = send_mail('Emails',$to,$from_name,$from_address,$subject,$description,$cc,$bcc,'current',0,$logo,'',$mail_tmp,$messageid,$message_mode);
			if ($mail_status != 1) {
				$returnok = false;
				$errormsg = 'Unable to send email';
			} else {

				// save entity
				$focus = CRMEntity::getInstance('Emails');
				$focus->column_fields["subject"] = $subject;
				$focus->column_fields["description"] = $description;
				$focus->column_fields["from_email"] = $from_address;
				$focus->column_fields["saved_toid"] = implode(',', $to);
				//$focus->column_fields["filename"] = $pdfpath;
				$_REQUEST['pdf_attachment'] = str_replace('storage/', '', $pdfpath);
				$focus->column_fields["parent_id"] = $_REQUEST['parentids'];
				$focus->column_fields["assigned_user_id"] = $current_user->id;
				$focus->column_fields["activitytype"] = "Emails";
				$focus->column_fields["date_start"] = date(getNewDisplayDate());//This will be converted to db date format in save
				$focus->column_fields["time_start"] = date('H:i:s');
				$focus->column_fields["email_flag"] = 'SENT';
				$focus->column_fields["send_mode"] = 'single';
				$focus->save("Emails",true);

				try {
					append_mail($mail_tmp,$to,$from_name,$from_address,$subject,$description,'','');
				} catch (Exception $e) {

				}
			}

		} else {
			$returnok = false;
			$errormsg = 'Mailer not found';
		}

	} elseif ($subaction == 'listrecipients') {

		$recids = array($recordid);
		// now use relation manager (if available) to get related records
		if (class_exists('RelationManager')) {
			$RM = RelationManager::getInstance();
			$relids = $RM->getRelatedIds($module, $recordid, array('Contacts', 'Leads', 'Accounts', 'Vendors'));
			if (is_array($relids)) $recids = array_unique(array_merge($recids, $relids));
		}
		$recipients = array();
		foreach ($recids as $rid) {
			$modtype = getSalesEntityType($rid);
			if ($modtype && isPermitted($modtype, 'DetailView', $rid) == 'yes') {
				$recipients = array_merge($recipients, touchFindEmailRecipients($modtype, $rid));
			}
		}
		echo Zend_Json::encode(array('total' => count($recipients), 'entries' => $recipients));
		die();

	} else {
		$returnok = false;
		$errormsg = 'Unknown PDFMaker action.';
	}

	echo Zend_Json::encode(array('success' => $returnok, 'result' => $returndata, 'error' => $errormsg));
}
?>