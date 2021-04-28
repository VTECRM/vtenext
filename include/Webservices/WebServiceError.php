<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	require_once("include/Webservices/WebServiceErrorCode.php");
	class WebServiceException extends Exception {
		
		public $code;
		public $message;
		
		function __construct($errCode,$msg){
			$this->code = $errCode;
			$this->message = $msg;
		}
		
	}
	
?>