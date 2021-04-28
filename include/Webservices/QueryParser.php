<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	require_once("include/Webservices/VTQL_Lexer.php");
	require_once("include/Webservices/VTQL_Parser.php");
	
	class Parser{
		
		private $query = "";
		private $out;
		private $meta;
		private $hasError ;
		private $error ;
		private $user; 
		function __construct($user, $q){
			$this->query = $q;
			$this->out = array();
			$this->hasError = false;
			$this->user = $user; 
		}
		
		function parse(){
			
			$lex = new VTQL_Lexer($this->query);
			$parser = new VTQL_Parser($this->user, $lex,$this->out);
			while ($lex->yylex()) {
				$parser->doParse($lex->token, $lex->value);
			}
			$parser->doParse(0, 0);
			
			if($parser->isSuccess()){
				$this->hasError = false;
				$this->query = $parser->getQuery();
				$this->meta = $parser->getObjectMetaData();
			}else{
				$this->hasError = true;
				$this->error = $parser->getErrorMsg();
			}
			
			$this->limit = $parser->getLimit(); //crmv@55311
			
			return $this->hasError;
			
		}
		
		//crmv@55311
		public function getLimit(){
			return $this->limit;
		}
		//crmv@55311e
		
		function getSql(){
			return $this->query;
		}
		
		function getObjectMetaData(){
			return $this->meta;
		}
		
		function getError(){
			return $this->error;
		}
		
	}
?>