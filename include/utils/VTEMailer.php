<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@180739 */

use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;

class VTEMailerSMTP extends SMTP {
	
	//crmv@58893
	public function getHelloOutput(){
		return $this->helo_rply;
	}
	//crmv@58893e
}

class VTEMailer extends PHPMailer {

	public $XMailer = 'VTECRM-WEBMAIL';
	
	public $CharSet = self::CHARSET_UTF8;

	public $max_message_size = null;
	
	protected $preSendOK = false;
	
	public function __construct($exceptions = null) {
		parent::__construct($exceptions);
		
		$VP = VTEProperties::getInstance();
		if ($VP->get("security.smtp.validate_certs") == '0') {
			$this->SMTPOptions['ssl'] = array(
				//Some men just want to watch the world burn...
				'verify_peer' => false,
				'allow_self_signed' => true, 
				'verify_peer_name' => false,
			);
		}
		
		//$this->SMTPDebug = 2; // crmv@201913
	}
	
	
	public function getSMTPInstance() {
        if (!is_object($this->smtp)) {
            $this->smtp = new VTEMailerSMTP();
        }

        return $this->smtp;
    }
    
    // crmv@198780
    public function createHeader($message_id='') {
    	if (!empty($message_id)) $this->MessageID = $message_id;
    	
    	$r = parent::createHeader();
    	
    	// compatibility fix: set old message_id
    	$this->message_id = $this->lastMessageID;
    	if (!empty($message_id)) $this->uniqueid = preg_replace('/<([^@]+)@(.*)/', '$1', $message_id);
    	
    	return $r;
    }
    // crmv@198780e
    
    /**
     * Added a flag to return the same id in case of multiple calls to CreateBody or CreateHeader
     */
    protected function generateId() {
		return $this->uniqueid ?: parent::generateId();
    }
    
    // clear the uniqueid
    public function postSend() {
		$r = parent::postSend();
		$this->uniqueid = '';
		return $r;
    }
    
    public function preSend() {
		// alter the text only body to translate html
		if (!empty($this->Body) && !empty($this->AltBody)) {
			$this->AltBody = html_entity_decode($this->AltBody, ENT_QUOTES, $this->CharSet);
		}
		return parent::preSend();
    }
    
    /**
     * Wrapper to have the function serverHostname public
     */
    public function getHostname() {
		return $this->serverHostname();
    }
    
    /**
     * Clear errors
     */
    public function resetErrors() {
		$this->error_count = 0;
		$this->ErrorInfo = '';
    }
    
    /**
     * Do a preliminary check to see if the SMTP server accepts the email
     */
    public function checkSend($attSize = 0, &$return_error) { // crmv@201913
    
		try {
    
			// code copied from original class
			$bad_rcpt = [];
			if (!$this->smtpConnect($this->SMTPOptions)) {
				throw new Exception($this->lang('smtp_connect_failed'), self::STOP_CRITICAL);
			}
			//Sender already validated in preSend()
			if ('' == $this->Sender) {
				$smtp_from = $this->From;
			} else {
				$smtp_from = $this->Sender;
			}
			if (!$this->smtp->mail($smtp_from)) {
				$this->setError($this->lang('from_failed') . $smtp_from . ' : ' . implode(',', $this->smtp->getError()));
				throw new Exception($this->ErrorInfo, self::STOP_CRITICAL);
			}

			// Attempt to send to all recipients
			foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
				foreach ($togroup as $to) {
					if (!$this->smtp->recipient($to[0], $this->dsn)) {
						$error = $this->smtp->getError();
						$bad_rcpt[] = ['to' => $to[0], 'error' => $error['detail']];
						$isSent = false;
					} else {
						$isSent = true;
					}
				}
			}

			//Create error message for any bad addresses
			if (count($bad_rcpt) > 0) {
				$errstr = '';
				foreach ($bad_rcpt as $bad) {
					$errstr .= $bad['to'] . ': ' . $bad['error'];
				}
				throw new Exception(
					$this->lang('recipients_failed') . $errstr,
					self::STOP_CONTINUE
				);
			} else {
				$extList = $this->smtp->getServerExtList();
				$max_size = $extList['SIZE'] ?: $this->max_message_size;

				if ($max_size > 0) {
					// calculate email size and check if bigger
					$this->message_type = 'alt_inline_attach'; // crmv@201913
					$bodystring = $this->CreateBody();
					$message_size = mb_strlen($bodystring) + $attSize;
					$message_size += 300000; // crmv@192217 add 300 KB
					
					unset($bodystring);
					
					if ($message_size > $max_size) {
						$size_mb = round($max_size/1024/1024,2)." MB";
						$error = sprintf(getTranslatedString('LBL_MESSAGE_TOO_BIG','Emails').$error,$size_mb);
						throw new Exception($error, self::STOP_CRITICAL);
					}
				}
			}

			if ($this->SMTPKeepAlive) {
				$this->smtp->reset();
			} else {
				$this->smtp->quit();
				$this->smtp->close();
			}
			
		} catch (Exception $e) {
			$return_error = $e->getMessage(); // crmv@201913
			return false;
		}

		return true;
    }
	
    // crmv@198780
    function addStringAttachment($string, $filename = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment', $cid = 0) {
    	try {
    		// If a MIME type is not specified, try to work it out from the file name
    		if ('' === $type) {
    			$type = static::filenameToType($filename);
    		}
    		
    		if (!$this->validateEncoding($encoding)) {
    			throw new Exception($this->lang('encoding') . $encoding);
    		}
    		
    		$this->attachment[] = [
    			0 => $string,
    			1 => $filename,
    			2 => $filename,
    			3 => $encoding,
    			4 => $type,
    			5 => true, // isStringAttachment
    			6 => $disposition,
    			7 => $cid,
    		];
    	} catch (Exception $exc) {
    		$this->setError($exc->getMessage());
    		$this->edebug($exc->getMessage());
    		if ($this->exceptions) {
    			throw $exc;
    		}
    		
    		return false;
    	}
    	
    	return true;
    }
    // crmv@198780e
}