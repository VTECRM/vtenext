<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
	class State{
		
		var $success ;
		var $result ;
		var $error;
		
		function __construct(){
			$this->success = false;
			$this->result = array();
			$this->error = array();
		}
		
		
	}
?>