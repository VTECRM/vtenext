<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@39106 */

class TouchPDFMaker extends TouchWSClass {

	public $validateModule = true;

	// TODO: separate actions in different methods
	public function process(&$request) {
		global $touchInst, $touchUtils, $currentModule, $current_user, $adb, $table_prefix;

		$module = vtlib_purify($request['module']);
		$subaction = $request['subaction'];
		$recordid = intval($request['record']);
		$templateid = intval($request['templateid']);

		require_once("modules/PDFMaker/PDFMaker.php");
		require_once("modules/PDFMaker/InventoryPDF.php");
		require_once("include/mpdf/mpdf.php");

		$currentModule = $module;
		$returnok = true;
		$errormsg = '';
		$returndata = null;

		// returns a list of all pdf (for specific module and record or generic)
		if ($subaction == 'getlist') {
			$result = $this->getPDFList($module, $recordid);
			if ($result !== false) return $this->success($result);
			else return $this->error('Unable to retrieve PDF list');

		} elseif ($subaction == 'savedoc') {
			// the same as modules/PDFMaker/SavePDFDoc.php

			// TODO: check if Documents module is enabled

			$focus = $touchUtils->getModuleInstance('Documents'); // crmv@39106
			$focus->parentid = $recordid;

			$modFocus = $touchUtils->getModuleInstance($module);
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
			$focus->column_fields["notes_title"] = vtlib_purify($request["notes_title"]);
			$focus->column_fields["assigned_user_id"] = $current_user->id;
			$focus->column_fields["filename"] = $file_name;
			$focus->column_fields["notecontent"] = vtlib_purify($request["notecontent"]);
			$focus->column_fields["filetype"] = "application/pdf";
			$focus->column_fields["filesize"] = "";
			$focus->column_fields["filelocationtype"] = "I";
			$focus->column_fields["fileversion"] = '';
			$focus->column_fields["filestatus"] = "on";
			$focus->column_fields["folderid"] = intval($request["folderid"]);
			$focus->save("Documents");

			// create and link pdf
			try {
				$language = $current_language;
				createPDFAndSaveFile($templateid,$focus,$modFocus,$file_name,$module,$language);
				$touchUtils->updateTimestamp($module, $recordid);
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

			$to = explode(',', $request['recipients']);
			$to = array_unique(array_filter(array_map('trim', $to)));
			$from_name = $current_user->user_name;
			$from_address = $current_user->column_fields['email1'];
			$cc = $bcc = '';
			$subject = $request['subject'];
			$description = vtlib_purify($request['message']);
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

				// save email
				if (vtlib_isModuleActive("Messages")) {
				
					// crmv@167465
					// ugly trick to attach to parent record
					$_REQUEST['relation'] = $recordid.'@-1';
					// crmv@167465ee

					$emailsFocus = $touchUtils->getModuleInstance('Emails');
					$account = $emailsFocus->getFromEmailAccount($from_address);

					// send to imap
					try {
						append_mail($mail_tmp,$account,$request['parentids'], $to,$from_name,$from_address,$subject,$description,'','');
						$touchUtils->updateTimestamp($module, $recordid);
					} catch (Exception $e) {
					}

				} else {
					$returnok = false;
					$errormsg = 'Mail sent, but not saved in IMAP folder';
				}

			}

		} elseif ($subaction == 'listrecipients') {

			$recids = array($recordid);
			// now use relation manager (if available) to get related records
			if (class_exists('RelationManager')) {
				$RM = RelationManager::getInstance();
				$relids = $RM->getRelatedIds($module, $recordid, array('Contacts', 'Leads', 'Accounts', 'Vendors'));
				if (is_array($relids)) $recids = array_unique(array_merge($recids, $relids));
				// crmv@124393
				// find second-order links
				if (empty($relids)) {
					$excludeMods = array('Contacts', 'Leads', 'Accounts', 'Vendors', 'Messages', 'Documents', 'Calendar', 'Events', 'ModComments', 'Targets', 'Newsletter', 'Products', 'Services'); // crmv@164122
					$relids = $RM->getRelatedIds($module, $recordid, array(), $excludeMods);
					if (is_array($relids)) {
						foreach ($relids as $relid) {
							$modtype = getSalesEntityType($relid);
							$relids2 = $RM->getRelatedIds($modtype, $relid, array('Contacts', 'Leads', 'Accounts', 'Vendors'));
							if (is_array($relids2)) $recids = array_unique(array_merge($recids, $relids2));
						}
					}
				}
				// crmv@124393e
			}
			$recipients = array();
			foreach ($recids as $rid) {
				$modtype = getSalesEntityType($rid);
				if ($modtype && isPermitted($modtype, 'DetailView', $rid) == 'yes') {
					$recipients = array_merge($recipients, $this->findEmailRecipients($modtype, $rid));
				}
			}
			return $this->success(array('total' => count($recipients), 'entries' => $recipients));

		} else {
			$returnok = false;
			$errormsg = 'Unknown PDFMaker action.';
		}

		return $touchInst->createOutput(array('result' => $returndata), $errormsg, $returnok);
	}

	protected function findEmailRecipients($module, $record) {
		global $touchUtils, $adb, $table_prefix, $current_user;

		$reclist = array();
		$modInst = $touchUtils->getModuleInstance($module);
		// email fields
		$res = $adb->pquery("select tablename,columnname,fieldname,fieldid from {$table_prefix}_field where tabid = ? and uitype = ?", array(getTabid($module), 13));
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$idx = $modInst->tab_name_index[$row['tablename']];
				if ($idx) {
					$emvalue = getSingleFieldValue($row['tablename'], $row['columnname'], $idx, $record);
					if (!empty($emvalue)) {
						// split it
						$ename = $touchUtils->getEntityNameFromFields($module, $record);
						$emlist = preg_split('/[,; ]/', $emvalue, -1, PREG_SPLIT_NO_EMPTY);
						foreach ($emlist as $email) {
							$reclist[] = array(
								'email' => $email,
								'entityname' => $ename,
								'module' => $module,
								'crmid' => intval($record),
								'fieldid' => intval($row['fieldid'])
							);
						}
					}
				}
			}
		}
		return $reclist;
	}

	protected function getPDFList($module, $record) {
		global $touchUtils;
		return $touchUtils->getPDFMakerDetails($module, $record);
	}

}
