<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailRecord.php');
require_once('modules/Settings/MailScanner/core/MailScanner.php');

/**
 * Class to work with server mailbox. 
 */
class Vtenext_MailBox {//crmv@207843
	// Mailbox credential information
	var $_scannerinfo = false;
	// IMAP connection instance
	var $_imap = false;
	// IMAP url to use for connecting
	var $_imapurl = false;
	// IMAP folder currently opened
	var $_imapfolder = false;
	// Should we need to expunge while closing imap connection?
	var $_needExpunge = false;

	// Mailbox crendential information (as a map)
	var $_mailboxsettings = false;

	/** DEBUG functionality. */
	var $debug = false;
	function log($message, $force=false) {
		global $log;
		if($log && ($force || $this->debug)) { $log->debug($message); }
		else if( ($force || $this->debug) ) echo "$message\n";
	}

	/**
	 * Constructor
	 */
	function __construct($scannerinfo) {
		$this->_scannerinfo = $scannerinfo;
		$this->_mailboxsettings = $scannerinfo->getAsMap();

		//crmv@167234
		if($this->_mailboxsettings['ssltype'] == '')  $this->_mailboxsettings['ssltype'] = 'notls';
		if($this->_mailboxsettings['sslmethod']== '') $this->_mailboxsettings['sslmethod'] = 'novalidate-cert';

		if($this->_mailboxsettings['protocol'] == 'pop3') { $port = '110'; }
		else {
			if($this->_mailboxsettings['ssltype'] == 'tls' || 
				$this->_mailboxsettings['ssltype'] == 'ssl') {
					$port = '993';
			}
			else $port = '143';
		}
		$this->_mailboxsettings['port'] = $port;
		//crmv@167234e
	}

	/**
	 * Connect to mail box folder.
	 */
	function connect($folder='INBOX') {
		$imap = false;
		$mailboxsettings = $this->_mailboxsettings;
		
		// crmv@178441
		$options = 0;
		$retries = 0;
		$params = array();
		
		if ($mailboxsettings['imap_params'] && count($mailboxsettings['imap_params']) > 0)  {
			foreach ($mailboxsettings['imap_params'] as $pinfo) {
				$params[$pinfo['name']] = $pinfo['value'];
			}
		}
		// crmv@178441e

		$isconnected = false;
		
		Vtenext_MailScanner::performanceLog("Imap connection", 'imap_connection'); // crmv@170905 crmv@207843

		// Connect using last successful url
		if($mailboxsettings['connecturl']) {
			$connecturl = $mailboxsettings['connecturl'];
			$this->log("Trying to connect using connecturl $connecturl$folder", true);
			$imap = @imap_open("$connecturl$folder", $mailboxsettings['username'], $mailboxsettings['password'], $options, $retries, $params); //crmv@167234 crmv@178441
			if($imap) {
				$this->_imapurl = $connecturl;
				$this->_imapfolder = $folder;
				$isconnected = true;

				$this->log("Successfully connected", true);
			}
		} 
		
		if(!$imap) {
			$connectString = '{'. "{$mailboxsettings['server']}:{$mailboxsettings['port']}/{$mailboxsettings['protocol']}/{$mailboxsettings['ssltype']}/{$mailboxsettings['sslmethod']}" ."}"; //crmv@167234
			$connectStringShort = '{'. "{$mailboxsettings['server']}/{$mailboxsettings['protocol']}:{$mailboxsettings['port']}" ."}"; //crmv@167234

			$this->log("Trying to connect using $connectString$folder", true);
			if(!$imap = @imap_open("$connectString$folder", $mailboxsettings['username'], $mailboxsettings['password'], $options, $retries, $params)) { //crmv@167234 crmv@178441
				$this->log("Connect failed using $connectString$folder, trying with $connectStringShort$folder...", true);
				$imap = @imap_open("$connectStringShort$folder", $mailboxsettings['username'], $mailboxsettings['password'], $options, $retries, $params); //crmv@167234 crmv@178441
				if($imap) {
					$this->_imapurl = $connectStringShort;
					$this->_imapfolder = $folder;
					$isconnected = true;
					$this->log("Successfully connected", true);
				} else {
					$this->log("Connect failed using $connectStringShort$folder", true);
				}
			} else {
				$this->_imapurl = $connectString;
				$this->_imapfolder = $folder;
				$isconnected = true;
				$this->log("Successfully connected", true);
			}
		}
		
		Vtenext_MailScanner::performanceLog("Imap connection in {tac}", 'imap_connection', true); // crmv@170905 crmv@207843

		$this->_imap = $imap;
		return $isconnected;
	}

	/**
	 * Open the mailbox folder.
	 * @param $folder Folder name to open
	 * @param $reopen set to true for re-opening folder if open (default=false)
	 * @return true if connected, false otherwise
	 */
	function open($folder, $reopen=false) {
		/** Avoid re-opening of the box if not requested. */
		if(!$reopen && ($folder == $this->_imapfolder)) return true; 

		if(!$this->_imap) return $this->connect($folder);

		$mailboxsettings = $this->_mailboxsettings;
		
		// crmv@178441
		$options = 0;
		$retries = 0;
		$params = array();
		
		if ($mailboxsettings['imap_params'] && count($mailboxsettings['imap_params']) > 0)  {
			foreach ($mailboxsettings['imap_params'] as $pinfo) {
				$params[$pinfo['name']] = $pinfo['value'];
			}
		}
		// crmv@178441e

		$isconnected = false;
		$connectString = $this->_imapurl;
		$this->log("Trying to open folder using $connectString$folder");
		$imap = @imap_open("$connectString$folder", $mailboxsettings['username'], $mailboxsettings['password'], $options, $retries, $params); //crmv@167234 crmv@178441
		if($imap) {

			// Perform cleanup task before re-initializing the connection
			$this->close(); 

			$this->_imapfolder = $folder;
			$this->_imap = $imap;
			$isconnected = true;
		}
		return $isconnected;
	}

	/**
	 * Get the mails based on searchquery.
	 * @param $folder Folder in which mails to be read.
	 * @param $searchQuery IMAP query, (default false: fetches mails newer from lastscan)
	 * @return imap_search records or false
	 */
	function search($folder, $searchQuery=false) {
		if(!$searchQuery) {
			$lastscanOn = $this->_scannerinfo->getLastscan($folder);
			$searchfor = $this->_scannerinfo->searchfor;

			if($searchfor && $lastscanOn) {				
				if($searchfor == 'ALL') {
					$searchQuery = "SINCE $lastscanOn";
				} else {
					$searchQuery = "$searchfor SINCE $lastscanOn";
				}
			} else {
				$searchQuery = $lastscanOn? "SINCE $lastscanOn" : "BEFORE ". date('d-M-Y');
			}
		}
		if($this->open($folder)) {
			$this->log("Searching mailbox[$folder] using query: $searchQuery");
			//return imap_search($this->_imap, $searchQuery);
			return imap_search($this->_imap, $searchQuery,SE_UID); //crmv@70040
		}
		return false;
	}

	/**
	 * Get folder names (as list) for the given mailbox connection
	 */
	function getFolders() {
		$folders = false;
		if($this->_imap) { 
			$imapfolders = imap_list($this->_imap, $this->_imapurl, '*'); 
			if($imapfolders) {
				foreach($imapfolders as $imapfolder) {
					$folders[] = substr($imapfolder, strlen($this->_imapurl));
				}
			}
		}
		return $folders;
	}

	/**
	 * Fetch the email based on the messageid.
	 * @param $messageid messageid of the email
	 * @param $fetchbody set to false to defer fetching the body, (default: true)
	 */
	function getMessage($messageid, $fetchbody=true) {
		return new Vtenext_MailRecord($this->_imap, $this->_imapfolder, $messageid, $fetchbody, $this->_mailboxsettings['is_pec']);	//crmv@56233 crmv@178441 crmv@207843
	}

	/**
	 * Mark the message in the mailbox.
	 */
	function markMessage($messageid) {
		$markas = $this->_scannerinfo->markas;
		if($this->_imap && $markas) {
			if(strtoupper($markas) == 'SEEN') $markas = "\\Seen";
			//imap_setflag_full($this->_imap, $messageid, $markas);
			imap_setflag_full($this->_imap, $messageid, $markas,ST_UID); //crmv@70040
		}
	}
	
	/**
	 * Close the open IMAP connection.
	 */
	function close() {
		if($this->_needExpunge) {
			imap_expunge($this->_imap);
		}
		$this->_needExpunge = false;
		if($this->_imap) { 
			imap_close($this->_imap); 
			$this->_imap = false; 
		}
	}
	
	//crmv@2043m	crmv@56233
	function moveMessage($messageid, $folder, $uid=false) {
		if ($uid) {
			$return = imap_mail_move($this->_imap, $messageid, $folder, CP_UID);
		} else {
			$return = imap_mail_move($this->_imap, $messageid, $folder);
		}
		imap_expunge($this->_imap);
		return $return;
	}
	//crmv@2043me	crmv@56233e
}

?>