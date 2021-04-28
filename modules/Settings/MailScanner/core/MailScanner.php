<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailBox.php');
require_once('modules/Settings/MailScanner/core/MailAttachmentMIME.php');

/**
 * Mail Scanner provides the ability to scan through the given mailbox
 * applying the rules configured.
 */
class Vtenext_MailScanner {//crmv@207843
	// MailScanner information instance
	var $_scannerinfo = false;
	// Reference mailbox to use
	var $_mailbox = false;

	// Ignore scanning the folders always
	var $_generalIgnoreFolders = Array( "INBOX.Trash", "INBOX.Drafts", "[Gmail]/Spam", "[Gmail]/Trash", "[Gmail]/Drafts" );

	var $maxMessages = 100;	// stop after processing this amount of emails
	
	var $maxScanAttemts = 3;	//crmv@88974
	
	/** DEBUG functionality. */
	var $debug = false;
	function log($message) {
		global $log;
		if($log && $this->debug) { $log->debug($message); }
		else if($this->debug) echo "$message\n";
	}

	/**
	 * Constructor.
	 */
	function __construct($scannerinfo) {
		$this->_scannerinfo = $scannerinfo;
	}

	/**
	 * Get mailbox instance configured for the scan
	 */
	function getMailBox() {
		if(!$this->_mailbox) {
			$this->_mailbox = new Vtenext_MailBox($this->_scannerinfo);//crmv@207843
			$this->_mailbox->debug = $this->debug;
		}
		return $this->_mailbox;
	}

	/**
	 * Start Scanning.
	 */
	function performScanNow() {
		// Check if rules exists to proceed
		$rules = $this->_scannerinfo->rules;

		if(empty($rules)) {
			$this->log("No rules setup for scanner [". $this->_scannerinfo->scannername . "] SKIPING\n");
			return;
		}

		// Build ignore folder list
		$ignoreFolders =  Array() + $this->_generalIgnoreFolders;
		$folderinfoList = $this->_scannerinfo->getFolderInfo();
		foreach($folderinfoList as $foldername=>$folderinfo) {
			if(!$folderinfo['enabled']) $ignoreFolders[] = $foldername; //crmv@167234
		}

		// Get mailbox instance to work with
		$mailbox = $this->getMailBox();
		$mailbox->connect();

		/** Loop through all the folders. */
		$folders = $mailbox->getFolders();

		if($folders) $this->log("Folders found: " . implode(',', $folders) . "\n");

		$msgCount = 0;
		foreach($folders as $lookAtFolder) {
			// Skip folder scanning?
			if(in_array($lookAtFolder, $ignoreFolders)) {
				$this->log("\nIgnoring Folder: $lookAtFolder\n");
				continue;
			}
			// If a new folder has been added we should avoid scanning it
			if(!isset($folderinfoList[$lookAtFolder])) {
				$this->log("\nSkipping New Folder: $lookAtFolder\n");
				continue;
			}

			// Search for mail in the folder
			$mailsearch = $mailbox->search($lookAtFolder);
			$this->log($mailsearch? "Total Mails Found in [$lookAtFolder]: " . count($mailsearch) : "No Mails Found in [$lookAtFolder]");

			// No emails? Continue with next folder
			if(empty($mailsearch)) continue;

			// Loop through each of the email searched
			foreach($mailsearch as $messageid) {
				if ($msgCount >= $this->maxMessages) break;
				
				Vtenext_MailScanner::performanceLog("START_FETCH", false, false, array('messageid' => $messageid)); // crmv@170905 crmv@207843

				// Fetch only header part first, based on account lookup fetch the body.
				$mailrecord = $mailbox->getMessage($messageid, false);
				$mailrecord->debug = $mailbox->debug;
				$mailrecord->log();
				
				// If the email is already scanned & rescanning is not set, skip it
				if($this->isMessageScanned($mailrecord, $lookAtFolder)) {
					$this->log("\nMessage already scanned [$mailrecord->_subject], IGNORING...\n");
					Vtenext_MailScanner::performanceLog("Message already scanned [$mailrecord->_subject], IGNORING..."); // crmv@170905 crmv@207843
					Vtenext_MailScanner::performanceLog("END_FETCH", false, false, array('messageid' => $messageid, 'processed' => false)); // crmv@170905 crmv@207843
					unset($mailrecord);
					continue;
				}
				
				// increment attempts
				$this->newScanAttempt($mailrecord);	//crmv@88974
				
				Vtenext_MailScanner::performanceLog("Applying rules for mail..."); // crmv@170905 crmv@207843
				
				// Apply rules configured for the mailbox
				$crmid = false;
				foreach($rules as $mailscannerrule) {
					$crmid = $this->applyRule($mailscannerrule, $mailrecord, $mailbox, $messageid);
					if($crmid) {
						break; // Rule was successfully applied and action taken
					}
				}
				
				// Mark the email message as scanned
				$this->markMessageScanned($mailrecord, $crmid);
				$mailbox->markMessage($messageid);
				
				//crmv@2043m
				$move_to = '';
				if ($crmid) {	//on action success
					$move_to = $this->_scannerinfo->succ_moveto;
				} else {
					$move_to = $this->_scannerinfo->no_succ_moveto;
				}
				if ($move_to != '') {
					Vtenext_MailScanner::performanceLog("Moving message to folder $move_to...", 'move_message'); // crmv@170905 crmv@207843
					
					//$mailbox->moveMessage($messageid, $move_to);
					$mailbox->moveMessage($messageid, $move_to,true); //crmv@70040
					
					Vtenext_MailScanner::performanceLog("Message moved to folder $move_to in {tac}", 'move_message', true); // crmv@170905 crmv@207843
				}
				//crmv@2043me
				
				Vtenext_MailScanner::performanceLog("FETCH_DETAILS", false, false, array('mailrecord' => $mailrecord)); // crmv@170905 crmv@207843
				Vtenext_MailScanner::performanceLog("END_FETCH", false, false, array('messageid' => $messageid, 'processed' => true)); // crmv@170905 crmv@207843

				/** Free the resources consumed. */
				unset($mailrecord);
				++$msgCount;
			}
			if ($msgCount >= $this->maxMessages) break; // don't set the lastscan for the folder since next time we have to continue

			/* Update lastscan for this folder and reset rescan flag */
			// TODO: Update lastscan only if all the mail searched was parsed successfully?
			$rescanFolderFlag = false;
			$this->updateLastScan($lookAtFolder, $rescanFolderFlag);
		}
		if ($msgCount >= $this->maxMessages) {
			$this->log("Max number of messages reached.");
		}
		// Close the mailbox at end
		$mailbox->close();
	}

	/**
	 * Apply all the rules configured for a mailbox on the mailrecord.
	 */
	function applyRule($mailscannerrule, $mailrecord, $mailbox, $messageid) {
		// If no actions are set, don't proceed
		if(empty($mailscannerrule->actions)) return false;

		// Check if rule is defined for the body
		$bodyrule = $mailscannerrule->hasBodyRule();

		if($bodyrule) {
			// We need the body part for rule evaluation
			$mailrecord->fetchBody($mailbox->_imap, $messageid);
		}

		// Apply rule to check if record matches the criteria
		$matchresult = $mailscannerrule->applyAll($mailrecord, $bodyrule);

		// If record matches the conditions fetch body to take action.
		$crmid = false;
		if($matchresult) {
			$mailrecord->fetchBody($mailbox->_imap, $messageid);
			$crmid = $mailscannerrule->takeAction($this, $mailrecord, $matchresult);
		}
		// Return the CRMID
		return $crmid;
	}

	/**
	 * Mark the email as scanned.
	 */
	function markMessageScanned($mailrecord, $crmid=false) {
		global $adb,$table_prefix;
		if($crmid === false) $crmid = null;
		// TODO Make sure we have unique entry
		//crmv@88974
		$adb->pquery("UPDATE ".$table_prefix."_mailscanner_ids set status=?, crmid=? where scannerid=? AND messageid=?",
			Array(1, $crmid, $this->_scannerinfo->scannerid, $mailrecord->_uniqueid));
		//crmv@88974e
	}

	/**
	 * Check if email was scanned.
	 */
	function isMessageScanned($mailrecord, $lookAtFolder) {
		global $adb,$table_prefix;
		$messages = $adb->pquery("SELECT * FROM ".$table_prefix."_mailscanner_ids WHERE scannerid=? AND messageid=?",
			Array($this->_scannerinfo->scannerid, $mailrecord->_uniqueid));

		$folderRescan = $this->_scannerinfo->needRescan($lookAtFolder);
		$isScanned = false;

		if($adb->num_rows($messages) && ($adb->query_result($messages,0,'status') == 1 || $adb->query_result($messages,0,'attempts') >= $this->maxScanAttemts)) {	//crmv@88974
			$isScanned = true;

			// If folder is scheduled for rescan and earlier message was not acted upon?
			$relatedCRMId = $adb->query_result($messages, 0, 'crmid');

			if($folderRescan && empty($relatedCRMId)) {
				$adb->pquery("DELETE FROM ".$table_prefix."_mailscanner_ids WHERE scannerid=? AND messageid=?",
					Array($this->_scannerinfo->scannerid, $mailrecord->_uniqueid));
				$isScanned = false;
			}
		}
		return $isScanned;
	}
	
	//crmv@88974 crmv@113957
	function newScanAttempt($mailrecord) {
		global $adb,$table_prefix;
		$oper = "=";
		if ($adb->isMssql()) {
			$oper = 'LIKE';
		}
		$messages = $adb->pquery("SELECT * FROM ".$table_prefix."_mailscanner_ids WHERE scannerid=? AND messageid $oper ?",
			Array($this->_scannerinfo->scannerid, $mailrecord->_uniqueid));
		if($adb->num_rows($messages) > 0) {
			$adb->pquery("UPDATE ".$table_prefix."_mailscanner_ids set attempts=? where scannerid=? AND messageid $oper ?",
				Array($adb->query_result($messages,0,'attempts')+1, $this->_scannerinfo->scannerid, $mailrecord->_uniqueid)
			);
		} else {
			$adb->pquery("INSERT INTO ".$table_prefix."_mailscanner_ids(scannerid, messageid, crmid, attempts) VALUES (?,?,?,?)",
				Array($this->_scannerinfo->scannerid, $mailrecord->_uniqueid, $crmid, 1));
		}
	}
	//crmv@88974e crmv@113957

	/**
	 * Update last scan on the folder.
	 */
	function updateLastscan($folder) {
		$this->_scannerinfo->updateLastscan($folder);
	}

	/**
	 * Convert string to integer value.
	 * @param $strvalue
	 * @returns false if given contain non-digits, else integer value
	 */
	function __toInteger($strvalue) {
		$ival = intval($strvalue);
		$intvalstr = "$ival";
		if(strlen($strvalue) == strlen($intvalstr)) {
			return $ival;
		}
		return false;
	}
	
	// crmv@170905 crmv@173186 crmv@176614
	public static function performanceLog($message, $tic = false, $tac = false, $params = array()) {
		
		$logger = VTESystemLogger::getLogger('mailscanner');
		if (!$logger) return ;
		
		$params = $params ?: array();
		
		if ($message === 'START_FETCH') {
			$messageid = $params['messageid'];
			$logger->info("####################################################################################");
			$logger->info('Fetching message [IMAP-MSG-ID=' . $messageid . ']', 'fetch');
		} elseif ($message === 'FETCH_DETAILS') {
			$mailrecord = $params['mailrecord'];
			
			$info = '';
			$info .= 'FROM: [' . implode(',', $mailrecord->_from) . ']' . "\n";
			$info .= 'TO: [' . implode(',', $mailrecord->_to) . ']' . "\n";
			if (!empty($mailrecord->_cc)) $info .= 'CC: [' . implode(',', $mailrecord->_cc) . ']' . "\n";
			if (!empty($mailrecord->_bcc)) $info .= 'BCC: [' . implode(',', $mailrecord->_bcc) . ']' . "\n";
			$info .= 'DATE: [' . date('Y-m-d H:i:s', $mailrecord->_date) . ']' . "\n";
			$info .= 'SUBJECT: [' . $mailrecord->_subject . ']';
			
			$logger->info(str_replace("\n", ', ', $info));
		} elseif ($message === 'END_FETCH') {
			$processed = $params['processed'];
			if ($processed) {
				$logger->info('Mail converted and processed in {tac}', 'fetch', true);
			} else {
				$logger->info('Message fetched in {tac}', 'fetch', true);
			}
			$logger->info("####################################################################################\n");
		} else {
			$logger->info($message, $tic, $tac);
		}
	}
	// crmv@170905e crmv@173186e crmv@176614e

	/** Lookup functionality. */
	var $_cachedContactIds = Array();
	var $_cachedAccountIds = Array();
	var $_cachedTicketIds  = Array();
	var $_cachedLeadIds  = Array();		//crmv@2043m
	var $_cachedVendorIds  = Array();	//crmv@27657

	var $_cachedAccounts = Array();
	var $_cachedContacts = Array();
	var $_cachedTickets  = Array();
	var $_cachedLeads  = Array();		//crmv@2043m
	var $_cachedVendors  = Array();		//crmv@27657

	/**
	 * Lookup Contact record based on the email given.
	 */
	function LookupContact($email) {
		global $adb,$table_prefix;
		if($this->_cachedContactIds[$email]) {
			$this->log("Reusing Cached Contact Id for email: $email");
			return $this->_cachedContactIds[$email];
		}
		$contactid = false;
		$contactres = $adb->pquery("SELECT contactid FROM ".$table_prefix."_contactdetails inner join ".$table_prefix."_crmentity on crmid=contactid WHERE deleted=0 and email=?", Array($email));
		if($adb->num_rows($contactres)) {
			$contactid = $adb->query_result($contactres, 0, 'contactid');
			$crmres = $adb->pquery("SELECT deleted FROM ".$table_prefix."_crmentity WHERE crmid=?", Array($contactid));
			if($adb->num_rows($crmres) && $adb->query_result($crmres, 0, 'deleted')) $contactid = false;
		}
		if($contactid) {
			$this->log("Caching Contact Id found for email: $email");
			$this->_cachedContactIds[$email] = $contactid;
		} else {
			$this->log("No matching Contact found for email: $email");
		}
		return $contactid;
	}
	/**
	 * Lookup Account record based on the email given.
	 */
	function LookupAccount($email) {
		global $adb,$table_prefix;
		if($this->_cachedAccountIds[$email]) {
			$this->log("Reusing Cached Account Id for email: $email");
			return $this->_cachedAccountIds[$email];
		}

		$accountid = false;
		$accountres = $adb->pquery(
			"SELECT accountid 
			FROM ".$table_prefix."_account 
			INNER JOIN ".$table_prefix."_crmentity ON crmid = accountid 
			WHERE deleted=0 AND (email1 = ? OR email2 = ?)", Array($email, $email));
		if($adb->num_rows($accountres)) {
			$accountid = $adb->query_result_no_html($accountres, 0, 'accountid');
		}
		if($accountid) {
			$this->log("Caching Account Id found for email: $email");
			$this->_cachedAccountIds[$email] = $accountid;
		} else {
			$this->log("No matching Account found for email: $email");
		}
		return $accountid;
	}
	/**
	 * Lookup Ticket record based on the subject or id given.
	 */
	function LookupTicket($subjectOrId) {
		global $adb,$table_prefix;

		$checkTicketId = $this->__toInteger($subjectOrId);
		if(!$checkTicketId) {
			$ticketres = $adb->pquery("SELECT ticketid FROM ".$table_prefix."_troubletickets WHERE title = ?", Array($subjectOrId));
			if($adb->num_rows($ticketres)) $checkTicketId = $adb->query_result($ticketres, 0, 'ticketid');
		}
		if(!$checkTicketId) return false;

		if($this->_cachedTicketIds[$checkTicketId]) {
			$this->log("Reusing Cached Ticket Id for: $subjectOrId");
			return $this->_cachedTicketIds[$checkTicketId];
		}

		$ticketid = false;
		if($checkTicketId) {
			$crmres = $adb->pquery("SELECT setype, deleted FROM ".$table_prefix."_crmentity WHERE crmid=?", Array($checkTicketId));
			if($adb->num_rows($crmres)) {
				if($adb->query_result($crmres, 0, 'setype') == 'HelpDesk' &&
					$adb->query_result($crmres, 0, 'deleted') == '0') $ticketid = $checkTicketId;
			}
		}
		if($ticketid) {
			$this->log("Caching Ticket Id found for: $subjectOrId");
			$this->_cachedTicketIds[$checkTicketId] = $ticketid;
		} else {
			$this->log("No matching Ticket found for: $subjectOrId");
		}
		return $ticketid;
	}
	//crmv@2043m
	function LookupLead($email) {
		global $adb,$table_prefix;
		if($this->_cachedLeadIds[$email]) {
			$this->log("Reusing Cached Lead Id for email: $email");
			return $this->_cachedLeadIds[$email];
		}
		$leadid = false;
		$leadres = $adb->pquery("SELECT leadid FROM ".$table_prefix."_leaddetails inner join ".$table_prefix."_crmentity on crmid=leadid WHERE deleted=0 and converted = 0 and email=?", Array($email));
		if($adb->num_rows($leadres)) {
			$leadid = $adb->query_result($leadres, 0, 'leadid');
		}
		if($leadid) {
			$this->log("Caching Lead Id found for email: $email");
			$this->_cachedLeadIds[$email] = $leadid;
		} else {
			$this->log("No matching Lead found for email: $email");
		}
		return $leadid;
	}
	function CreateLead($email) {
		global $current_user;
		if(!$current_user) $current_user = CRMEntity::getInstance('Users');
		$current_user->id = 1;

		$lead = CRMEntity::getInstance('Leads');
		$lead->column_fields['assigned_user_id'] = $current_user->id;
		if ($email != '') {
			$tmp = explode('@',$email);
			if ($tmp[1] != '') {
				$company = ucfirst($tmp[1]);
				$pos = strpos($company, '.');
				if ($pos !== false) {
					$company = substr($company,0,$pos);
				}
			}
			if ($tmp[0] != '') {
				$tmp[0] = preg_replace('/[0-9-]+/','',$tmp[0]);
				$lastname = ucfirst($tmp[0]);
				$firstname = '';
				$separator = '.';
				$pos = strpos($tmp[0], $separator);
				if ($pos === false) {
					$separator = '_';
					$pos = strpos($tmp[0], $separator);
				}
				if ($pos !== false) {
					$firstname = trim(ucfirst(substr($tmp[0],0,$pos)));
					$lastname = trim(ucwords(str_replace($separator,' ',substr($tmp[0],$pos))));
				}
			}
			$lead->column_fields['email'] = $email;
			$lead->column_fields['lastname'] = $lastname;
			$lead->column_fields['firstname'] = $firstname;
			$lead->column_fields['company'] = $company;
			//crmv@56233
			$picklistValues = vtlib_getPicklistValues('leadsource');
			if (in_array('Mail Converter', $picklistValues)) {
				$lead->column_fields['leadsource'] = 'Mail Converter';
			}
			//crmv@56233e
		}
		$lead->save('Leads');
		return $lead->id;
	}
	//crmv@2043me
	/**
	 * Get Account record information based on email.
	 */
	function GetAccountRecord($email) {
		$accountid = $this->LookupAccount($email);
		$account_focus = false;
		if($accountid) {
			if($this->_cachedAccounts[$accountid]) {
				$account_focus = $this->_cachedAccounts[$accountid];
				$this->log("Reusing Cached Account [" . $account_focus->column_fields['accountname'] . "]"); //crmv@167234
			} else {
				$account_focus = CRMEntity::getInstance('Accounts');
				$account_focus->retrieve_entity_info($accountid, 'Accounts');
				$account_focus->id = $accountid;

				$this->log("Caching Account [" . $account_focus->column_fields['accountname'] . "]"); //crmv@167234
				$this->_cachedAccounts[$accountid] = $account_focus;
			}
		}
		return $account_focus;
	}
	/**
	 * Get Contact record information based on email.
	 */
	function GetContactRecord($email) {
		$contactid = $this->LookupContact($email);
		$contact_focus = false;
		if($contactid) {
			if($this->_cachedContacts[$contactid]) {
				$contact_focus = $this->_cachedContacts[$contactid];
				$this->log("Reusing Cached Contact [" . $contact_focus->column_fields['lastname'] . //crmv@167234
				   	'-' . $contact_focus->column_fields['firstname'] . "]"); //crmv@167234
			} else {
				$contact_focus = CRMEntity::getInstance('Contacts');
				$contact_focus->retrieve_entity_info($contactid, 'Contacts');
				$contact_focus->id = $contactid;

				$this->log("Caching Contact [" . $contact_focus->column_fields['lastname'] . //crmv@167234
				   	'-' . $contact_focus->column_fields['firstname'] . "]"); //crmv@167234
				$this->_cachedContacts[$contactid] = $contact_focus;
			}
		}
		return $contact_focus;
	}

	//crmv@2043m
	function GetLeadRecord($email) {
		$leadid = $this->LookupLead($email);
		$lead_focus = false;
		if($leadid) {
			if($this->_cachedLeads[$leadid]) {
				$lead_focus = $this->_cachedLeads[$leadid];
				$this->log("Reusing Cached Lead [" . $lead_focus->column_fields['lastname'] . //crmv@167234
				   	'-' . $lead_focus->column_fields['firstname'] . "]"); //crmv@167234
			} else {
				$lead_focus = CRMEntity::getInstance('Leads');
				$lead_focus->retrieve_entity_info($leadid, 'Leads');
				$lead_focus->id = $leadid;

				$this->log("Caching Lead [" . $lead_focus->column_fields['lastname'] . //crmv@167234
				   	'-' . $lead_focus->column_fields['firstname'] . "]"); //crmv@167234
				$this->_cachedLeads[$leadid] = $lead_focus;
			}
		}
		return $lead_focus;
	}

	/**
	 * Lookup Contact or Account based on from email and with respect to given CRMID
	 */
	function LookupContactOrAccountOrLead($fromemail, $checkWithId=false) {
		$recordid = $this->LookupContact($fromemail);
		if($checkWithId && $recordid != $checkWithId) {
			$recordid = $this->LookupAccount($fromemail);
			if($checkWithId && $recordid != $checkWithId) $recordid = false;
		}
		if($checkWithId && $recordid != $checkWithId) {
			$recordid = $this->LookupLead($fromemail);
			if($checkWithId && $recordid != $checkWithId) $recordid = false;
		}
		return $recordid;
	}

	/**
	 * Get Ticket record information based on subject or id.
	 */
	function GetTicketRecord($subjectOrId, $fromemail=false, $compare_parentid=1, $match_field='crmid') {	//crmv@78745 crmv@81643
		($match_field == 'crmid') ? $ticketid = $this->LookupTicket($subjectOrId) : $ticketid = $this->SearchByExternalCode($subjectOrId);	//crmv@81643
		$ticket_focus = false;
		if($ticketid) {
			if($this->_cachedTickets[$ticketid]) {
				$ticket_focus = $this->_cachedTickets[$ticketid];
				// Check the parentid association if specified.
				if($compare_parentid == 1 && $fromemail && !$this->LookupContactOrAccountOrLead($fromemail, $ticket_focus->column_fields['parent_id'])) {	//crmv@78745 //crmv@167234
					$ticket_focus = false;
				}
				if($ticket_focus) {
					$this->log("Reusing Cached Ticket [" . $ticket_focus->column_fields['ticket_title'] ."]"); //crmv@167234
				}
			} else {
				$ticket_focus = CRMEntity::getInstance('HelpDesk');
				$ticket_focus->retrieve_entity_info($ticketid, 'HelpDesk');
				$ticket_focus->id = $ticketid;
				// Check the parentid association if specified.
				if($compare_parentid == 1 && $fromemail && !$this->LookupContactOrAccountOrLead($fromemail, $ticket_focus->column_fields['parent_id'])) {	//crmv@78745 //crmv@167234
					$ticket_focus = false;
				}
				if($ticket_focus) {
					$this->log("Caching Ticket [" . $ticket_focus->column_fields['ticket_title'] . "]"); //crmv@167234
					$this->_cachedTickets[$ticketid] = $ticket_focus;
				}
			}
		}
		return $ticket_focus;
	}
	//crmv@2043me
	//crmv@27657
	function GetVendorRecord($email) {
		$vendorid = $this->LookupVendor($email);
		$vendor_focus = false;
		if($vendorid) {
			if($this->_cachedVendors[$vendorid]) {
				$vendor_focus = $this->_cachedVendors[$vendorid];
				$this->log("Reusing Cached Vendor [" . $vendor_focus->column_fields['vendorname'] . "]"); //crmv@167234
			} else {
				$vendor_focus = CRMEntity::getInstance('Vendors');
				$vendor_focus->retrieve_entity_info($vendorid, 'Vendors');
				$vendor_focus->id = $vendorid;

				$this->log("Caching Vendor [" . $vendor_focus->column_fields['vendorname'] . "]"); //crmv@167234
				$this->_cachedVendors[$vendorid] = $vendor_focus;
			}
		}
		return $vendor_focus;
	}
	function LookupVendor($email) {
		global $adb,$table_prefix;
		if($this->_cachedVendorIds[$email]) {
			$this->log("Reusing Cached Vendor Id for email: $email");
			return $this->_cachedVendorIds[$email];
		}

		$vendorid = false;
		$vendorres = $adb->pquery("SELECT vendorid FROM ".$table_prefix."_vendor inner join ".$table_prefix."_crmentity on crmid=vendorid WHERE deleted=0 and email=?", Array($email));
		if($adb->num_rows($vendorres)) {
			$vendorid = $adb->query_result($vendorres, 0, 'vendorid');
		}
		if($vendorid) {
			$this->log("Caching Vendor Id found for email: $email");
			$this->_cachedVendorIds[$email] = $vendorid;
		} else {
			$this->log("No matching Vendor found for email: $email");
		}
		return $vendorid;
	}
	//crmv@27657e
	//crmv@81643
	function SearchByExternalCode($id) {
		$id = intval($id);
		if (!empty($id)) {
			global $adb, $table_prefix;
			$external_code = strtoupper(substr($this->_scannerinfo->scannername,0,5)).'-'.$id;
			$result = $adb->pquery("select {$table_prefix}_troubletickets.ticketid from {$table_prefix}_troubletickets
				inner join {$table_prefix}_crmentity on {$table_prefix}_troubletickets.ticketid = {$table_prefix}_crmentity.crmid
				where {$table_prefix}_crmentity.deleted = 0 and {$table_prefix}_troubletickets.external_code = ?", array($external_code));
			if ($result && $adb->num_rows($result) > 0) {
				return $adb->query_result($result,0,'ticketid');
			}
		}
		return false;
	}
	//crmv@81643e
}
?>