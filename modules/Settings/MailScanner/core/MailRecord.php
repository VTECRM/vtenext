<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/**
 * This class provides structured way of accessing details of email.
 */
class Vtenext_MailRecord {//crmv@207843
	// FROM address(es) list 
	var $_from;
	// TO address(es) list
	var $_to;
	//var $_replyto;

	// CC address(es) list
	var $_cc;
	// BCC address(es) list
	var $_bcc;
	// DATE
	var $_date;
	// SUBJECT
	var $_subject;
	// BODY (either HTML / PLAIN message)
	var $_body;
	// CHARSET of the body content
	var $_charset;
	// If HTML message was set as body content
	var $_isbodyhtml;
	// PLAIN message of the original email
	var $_plainmessage = false;
	// HTML message of the original email
	var $_htmlmessage = false;
	// ATTACHMENTS list of the email
	var $_attachments = false;
	// UNIQUEID associated with the email
	var $_uniqueid = false;
	var $_xuid = false;		//crmv@56233
	var $_folder = false;	//crmv@56233

	// Flag to avoid re-parsing the email body.
	var $_bodyparsed = false;
	
	var $eml = null; //crmv@178441

	/** DEBUG Functionality. */
	var $debug = false;
	function log($message=false) {
		if(!$message) $message = $this->__toString();

		global $log;
		if($log && $this->debug) { $log->debug($message); }
		else if($this->debug) {
			echo var_export($message, true) . "\n";
		}
	}

	/**
	 * String representation of the object.
	 */
	function __toString() {
		$tostring = '';
		$tostring .= 'FROM: ['. implode(',', $this->_from) . ']';
		$tostring .= ',TO: [' . implode(',', $this->_to) .   ']';
		if(!empty($this->_cc)) $tostring .= ',CC: [' . implode(',', $this->_cc) .   ']';
		if(!empty($this->_bcc))$tostring .= ',BCC: [' . implode(',', $this->_bcc) .   ']';
		$tostring .= ',DATE: ['. $this->_date . ']';
		$tostring .= ',SUBJECT: ['. $this->_subject . ']';
		return $tostring;
	}

	/**
	 * Constructor.
	 */
	function __construct($imap, $folder, $messageid, $fetchbody=true, $is_pec=false) {	//crmv@56233 crmv@178441
		$this->__parseHeader($imap, $messageid);
		//crmv@178441
		if ($is_pec) {
			$this->__parseBody($imap, $messageid);
			if (!empty($this->eml)) $this->__initObjectFromEml();
		}
		if ($fetchbody && !$this->_bodyparsed) {
			$this->__parseBody($imap, $messageid);
			if (!empty($this->eml) && $this->checkCertifiedEmailMessage()) $this->__initObjectFromEml();
		}
		//crmv@178441e
		$this->_folder = $folder;	//crmv@56233
	}

	/**
	 * Get body content as Text.
	 */
	function getBodyText($striptags=true) {
		$bodytext = $this->_body;

		if($this->_plainmessage) {
			$bodytext = $this->_plainmessage;
		} else if($this->_isbodyhtml) {
			// TODO This conversion can added multiple lines if 
			// content is displayed directly on HTML page
			$bodytext = preg_replace("#<br\s*/?>#i", "\n", $bodytext); // crmv@120786
			//crmv@81643
			$bodytext = preg_replace("/<style\\b[^>]*>(.*?)<\\/style>/s", "", $bodytext);
			$bodytext = preg_replace('/\<[\/]?(tr)([^\>]*)\>/i', "\n", $bodytext);
			$bodytext = preg_replace('/\<[\/]?(td)([^\>]*)\>/i', ' ', $bodytext);
			$bodytext = str_replace(array('&nbsp;', '&#39;', '&quot;'), array(' ', "'", '"'), $bodytext); // crmv@120786
			$bodytext = str_replace("\n\n", "\n", $bodytext);
			$bodytext = preg_replace('/[^\S\x0a\x0d]+/', ' ', $bodytext);
			//crmv@81643e			
			$bodytext = strip_tags($bodytext);
		}
		return $bodytext;
	}

	/**
	 * Get body content as HTML.
	 */
	function getBodyHTML() {
		$bodyhtml = $this->_body;
		if(!$this->_isbodyhtml) {
			$bodyhtml = preg_replace( Array("/\r\n/", "/\n/"), Array('<br>','<br>'), $bodyhtml );
		}
		return $bodyhtml;
	}		

	/**
	 * Fetch the mail body from server.
	 */
	function fetchBody($imap, $messageid) {
		if(!$this->_bodyparsed) {
			$this->__parseBody($imap, $messageid);
			if (!empty($this->eml) && $this->checkCertifiedEmailMessage()) $this->__initObjectFromEml(); //crmv@178441
		}
	}

	/**
	 * Parse the email id from the mail header text.
	 * @access private
	 */
	function __getEmailIdList($inarray) {
		if(empty($inarray)) return Array();
		$emails = Array();
		foreach($inarray as $emailinfo) {
			$emails[] = $emailinfo->mailbox . '@' . $emailinfo->host;
		}
		return $emails;
	}
	
	/**
	 * Helper function to convert the encoding of input to target charset.
	 */
	static function __convert_encoding($input, $to, $from = false) {
		if(function_exists('mb_convert_encoding')) {
			if(!$from) $from = mb_detect_encoding($input);

			if(strtolower(trim($to)) == strtolower(trim($from))) {				
				return $input;
			} else {
				return mb_convert_encoding($input, $to, $from);
			}
		}
		return $input;
	}
	
	/**
	 * MIME decode function to parse IMAP header or mail information
	 */
	static function __mime_decode($input, $targetEncoding='UTF-8', &$words=null) {
		if(is_null($words)) $words = array();
		$returnvalue = $input;
		
		if(preg_match_all('/=\?([^\?]+)\?([^\?]+)\?([^\?]+)\?=/', $input, $matches)) {
			$totalmatches = count($matches[0]);
			
			for($index = 0; $index < $totalmatches; ++$index) {
				$charset = $matches[1][$index];
				$encoding= strtoupper($matches[2][$index]); // B - base64 or Q - quoted printable
				$data    = $matches[3][$index];
				
				if($encoding == 'B') {
					$decodevalue = base64_decode($data);
				} else if($encoding == 'Q') {
					$data = str_replace('_','=20',$data); // crmv@91773
					$decodevalue = quoted_printable_decode($data);
				}
				$value = self::__convert_encoding($decodevalue, $targetEncoding, $charset);				
				array_push($words, $value);				
			}
		}
		if(!empty($words) && is_array($words)) {
			$returnvalue = implode('', $words);
		}
		return $returnvalue;
	}
	
	/**
	 * MIME encode function to prepare input to target charset supported by normal IMAP clients.
	 */
	static function __mime_encode($input, $encoding='Q', $charset='iso-8859-1') {
		$returnvalue = $input;		
		$encoded = false;
		
		if(strtoupper($encoding) == 'B' ) {
			$returnvalue = self::__convert_encoding($input, $charset);
			$returnvalue = base64_encode($returnvalue);
			$encoded = true;
		} else {
			$returnvalue = self::__convert_encoding($input, $charset);
			if(function_exists('imap_qprint')) {
				$returnvalue = imap_qprint($returnvalue);
				$encoded = true;
			} else {
				// TODO: Handle case when imap_qprint is not available.
			}
		}
		if($encoded) {
			$returnvalue = "=?$charset?$encoding?$returnvalue?=";
		}
		return $returnvalue;
	}
	
	// crmv@178426
	function __filename_decode($string) {
		global $default_charset;
		$tabChaine = imap_mime_header_decode($string);
		$texte = '';
		for ($i=0; $i<count($tabChaine); $i++) {
			switch (strtoupper($tabChaine[$i]->charset)) { // convert charset to uppercase
				case 'UTF-8':
					$texte .= $tabChaine[$i]->text; // utf8 is ok
					break;
				case 'DEFAULT':
					$texte .= $tabChaine[$i]->text; // no convert
					break;
				default:
					if (in_array(strtoupper($tabChaine[$i]->charset), $this->upperListEncode())) { // crmv@206242
						// found in mb_list_encodings()
						$texte .= mb_convert_encoding($tabChaine[$i]->text, $default_charset, $tabChaine[$i]->charset);
					} else {
						// try to convert with iconv()
						$ret = iconv($tabChaine[$i]->charset, $default_charset, $tabChaine[$i]->text);
						if (!$ret) $texte .= $tabChaine[$i]->text;  //an error occurs (unknown charset)
						else $texte .= $ret;
					}
					break;
			}
		}
		return $texte;
	}
	// crmv@178426e
	
	// crmv@206242
	function upperListEncode() { // convert mb_list_encodings() to uppercase
		$encodes = mb_list_encodings();
		foreach ($encodes as $encode) $tencode[] = strtoupper($encode);
		return $tencode;
 	}
	// crmv@206242e

	/**
	 * Parse header of the email.
	 * @access private
	 */
	function __parseHeader($imap, $messageid) {
		global $default_charset;
		
		$this->_from = Array();
		$this->_to = Array();

		//$this->_xuid = imap_uid($imap, $messageid);	//crmv@56233
		
		$this->_xuid = $messageid; //crmv@70040
		
		// crmv@170905
		Vtenext_MailScanner::performanceLog('Fetching header [IMAP-MSG-ID=' . $messageid . ']', 'fetch_header');//crmv@207843
		
		$messageid = imap_msgno($imap,$this->_xuid); //crmv@70040
		$mailheader = imap_headerinfo($imap, $messageid);
		
		Vtenext_MailScanner::performanceLog('Fetched header in {tac}', 'fetch_header', true);//crmv@207843
		
		$base = log($mailheader->Size) / log(1024);
		$suffix = array("", "k", "M", "G", "T");
		$suffix = $suffix[floor($base)];
		Vtenext_MailScanner::performanceLog('Size: ' . round(pow(1024, $base - floor($base)), 2) . $suffix);//crmv@207843
		// crmv@170905e
		
		$this->_uniqueid = $mailheader->message_id;
		
		$this->_from = $this->__getEmailIdList($mailheader->from);
		$this->_to   = $this->__getEmailIdList($mailheader->to);
		$this->_cc   = $this->__getEmailIdList($mailheader->cc);
		$this->_bcc  = $this->__getEmailIdList($mailheader->bcc);

		$this->_date = $mailheader->udate;

		$this->_subject = self::__mime_decode($mailheader->subject, $default_charset);
		if(!$this->_subject) $this->_subject = 'Untitled';
	}
	// Modified: http://in2.php.net/manual/en/function.imap-fetchstructure.php#85685
	function __parseBody($imap, $messageid) {
		// crmv@170905
		Vtenext_MailScanner::performanceLog('Fetching body [IMAP-MSG-ID=' . $messageid . ']', 'fetch_body');//crmv@207843
		
		//$structure = imap_fetchstructure($imap, $messageid);
		$structure = imap_fetchstructure($imap, $messageid,FT_UID); //crmv@70040
		$messageid = imap_msgno($imap,$messageid); //crmv@70040
		
		$errors = imap_errors();
		if (!empty($errors)) {
			Vtenext_MailScanner::performanceLog('Errors: ' . print_r($errors, true));//crmv@207843
		}
		
		Vtenext_MailScanner::performanceLog('Fetched body in {tac}', 'fetch_body', true);//crmv@207843
		// crmv@170905e

		$this->_plainmessage = '';
		$this->_htmlmessage = '';
		$this->_body = '';
		$this->_isbodyhtml = false;

		if($structure->parts) { /* multipart */
			foreach($structure->parts as $partno0=>$p) {
				$this->__getpart($imap, $messageid, $p, $partno0+1);
			}
		} else { /* not multipart */
			$this->__getpart($imap, $messageid, $structure, 0);
		}

		// Set the body (either plain or html content)
		if($this->_htmlmessage != '') {
			$this->_body = $this->_htmlmessage;
			$this->_isbodyhtml = true;
		} else {
			$this->_body = $this->_plainmessage;
		}

		if($this->_attachments) {
			$this->log("Attachments: ".count($this->_attachments));	//crmv@132704
			Vtenext_MailScanner::performanceLog('Attachments: ' . count($this->_attachments)); // crmv@170905 crmv@207843
		}

		$this->_bodyparsed = true;
	}
	// Modified: http://in2.php.net/manual/en/function.imap-fetchstructure.php#85685
	//crmv@132704
	function __getpart($imap, $messageid, $p, $partno) {
		global $default_charset;
	    // $partno = '1', '2', '2.1', '2.1.3', etc if multipart, 0 if not multipart
    	
	    // DECODE DATA
    	$data = ($partno)? 
			imap_fetchbody($imap,$messageid,$partno,FT_PEEK):  // multipart		//crmv@45881
			imap_body($imap,$messageid,FT_PEEK);               // not multipart	//crmv@45881
    	
		// Any part may be encoded, even plain text messages, so check everything.
    	if ($p->encoding==4) $data = quoted_printable_decode($data);
		elseif ($p->encoding==3) $data = base64_decode($data);
		// no need to decode 7-bit, 8-bit, or binary

    	// PARAMETERS
	    // get all parameters, like charset, filenames of attachments, etc.
    	$params = array();
	    if ($p->parameters) {
			foreach ($p->parameters as $x) $params[ strtolower( $x->attribute ) ] = $x->value;
		}
	    if ($p->dparameters) {
			foreach ($p->dparameters as $x) $params[ strtolower( $x->attribute ) ] = $x->value;
		}
		
		$types = array(0=>'text',1=>'multipart',2=>'message',3=>'application',4=>'audio',5=>'image',6=>'video',7=>'model',8=>'other');
		$contenttype = strtolower($types[$p->type].'/'.$p->subtype);
		
		// ATTACHMENT
    	// Any part with a filename is an attachment,
	    // so an attached text file (type 0) is not mistaken as the message.
	    $eml_file = false;	//crmv@90941
	    //crmv@36562: some attachments have not disposition, so I check if type == 3 (application ex. pdf)
    	if (($params['filename'] || $params['name']) && (!empty($p->disposition) || $p->type == 3)) {
    	//crmv@36562e
        	// filename may be given as 'Filename' or 'Name' or both
	        $filename = ($params['filename'])? $params['filename'] : $params['name'];
	        $filename = self::__filename_decode($filename); // crmv@178426
	        
			if(!$this->_attachments) $this->_attachments = array();
			$this->_attachments[] = array(
				'contentname'=>$filename,
				'contenttype'=>$contenttype,
				'contentdisposition' => strtolower($p->disposition), // crmv@172106
				'data'=>$data,
			);
			//crmv@90941
			$extension = substr(strrchr($filename,'.'), 1);
			if (strtolower($extension) == 'eml' || strtolower($p->subtype) == 'rfc822') $eml_file = true;
			//crmv@90941e
	    }

	    // TEXT
    	elseif ($p->type==0 && $data) {    		
    		$this->_charset = $params['charset'];  // assume all parts are same charset
    		$data = self::__convert_encoding($data, $default_charset, $this->_charset);
    		
        	// Messages may be split in different parts because of inline attachments,
	        // so append parts together with blank row.
    	    if (strtolower($p->subtype)=='plain') $this->_plainmessage .= trim($data) ."\n\n";
	        else $this->_htmlmessage .= $data ."<br><br>";
		}

	    // EMBEDDED MESSAGE
    	// Many bounce notifications embed the original message as type 2,
	    // but AOL uses type 1 (multipart), which is not handled here.
    	// There are no PHP functions to parse embedded messages,
	    // so this just appends the raw source to the main message.
    	elseif ($p->type==2 && $data) {
    		if (strtolower($p->subtype) == 'rfc822') {
    			// filename may be given as 'Filename' or 'Name' or both
    			$filename = ($params['filename'])? $params['filename'] : $params['name'];
    			$filename = self::__filename_decode($filename); // crmv@178426
    			
    			// try to read filename in the first part
    			if (empty($filename) && $p->parts) {
    				$messagesid = 0;
    				$error = '';
    				$focusMessages = CRMEntity::getInstance('Messages');
    				$eml_message = $focusMessages->parseEML(0, $messagesid, $error, $data, true);
    				if (empty($error) && !empty($eml_message['subject'])) $filename = $eml_message['subject'];
    			}
    			if (empty($filename)) $filename = 'Unknown';
    			
    			if(!$this->_attachments) $this->_attachments = array();
    			$this->_attachments[] = array(
    				'contentname'=>$filename,
    				'contenttype'=>$contenttype,
    				'contentdisposition' => strtolower($p->disposition), // crmv@172106
    				'data'=>$data,
    			);
    			$eml_file = true;
    		} else {
				$this->_plainmessage .= trim($data) ."\n\n";
    		}
	    }
	    
	    //crmv@178441
	    if ($eml_file && empty($this->eml)) {
	    	$messagesid = 0;
	    	$error = '';
	    	$focusMessages = CRMEntity::getInstance('Messages');
	    	$eml_message = $focusMessages->parseEML(0, $messagesid, $error, $data, true);
	    	if (empty($error)) {
	    		$this->eml = $eml_message;
	    	}
	    }
	    //crmv@178441e

	    // SUBPART RECURSION
	    if ($p->parts && !$eml_file) {	//crmv@90941
        	foreach ($p->parts as $partno0=>$p2)
            	$this->__getpart($imap,$messageid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
    	}
	}
	//crmv@132704e
	
	//crmv@178441
	function __initObjectFromEml() {
		$this->_from = array($this->eml['mfrom']);
		$this->_to   = array($this->eml['mto']);
		$this->_cc   = array($this->eml['mcc']);
		$this->_bcc  = array($this->eml['mbcc']);
		$this->_date = strtotime($this->eml['mdate']);
		$this->_subject = $this->eml['subject'];
		$this->_body = $this->eml['description'];
		$this->_isbodyhtml = true;
		$this->_plainmessage = '';
		$this->_plainmessage = trim($this->getBodyText());
		
		$this->_attachments = array();
		if (!empty($this->eml['other'])) {
			foreach($this->eml['other'] as $attachment) {
				$content = $attachment['content'];
				switch (strtolower($attachment['parameters']['encoding'])) {
					case 'base64':
						$content = base64_decode($content);
						break;
					case 'quoted-printable':
						$content = quoted_printable_decode($content);
						break;
				}
				$this->_attachments[] = array(
					'contentname'=>$attachment['parameters']['name'],
					'contenttype'=>$attachment['parameters']['contenttype'],
					'contentdisposition'=>$attachment['parameters']['contentdisposition'],
					'encoding'=>$attachment['parameters']['encoding'],
					'size'=>$attachment['parameters']['size'],
					'data'=>$content,
				);
			}
		}
	}
	function checkCertifiedEmailMessage() {
		$body = $this->getBodyText();
		return (stripos($body, 'Certified email message') !== false);
	}
	//crmv@178441e
}
?>