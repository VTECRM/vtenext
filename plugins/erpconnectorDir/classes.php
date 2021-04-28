<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 - some cleaning */

class log{
	public $start;
	public $stop;
	public $content;
	public $line_termination;
	function __construct($line_termination="\n"){
		$this->format = $format;
		$this->format_content = $format_content;
		$this->line_termination = $line_termination;
	}
	function get_microtime(){
		list($misec,$sec) = explode(' ', microtime());
		return (int) ((float)$misec + (float)$sec);
	}
	function start(){
		$this->start=$this->get_microtime();
	}
	function stop($string){
		$this->stop=$this->get_microtime();
		$seconds=($this->stop-$this->start)." s ";
		$minutes=(int) ($seconds/60)." m ".($seconds%60)." s ";
		$this->content.="time of $string : $seconds | $minutes  ".$this->line_termination;
	}
	function get_content(){
		return $this->content;
	}
}

class importer{
	public $erpdir;
	public $module;
	public $data=Array();
	public $fields=Array();
	public $fields_auto_create=Array();
	public $fields_auto_update=Array();
	public $fields_runtime=Array();
	public $external_code;
	public $existing_entity;
	public $mapping;
	public $mapping_inverse;
	public $query_get;
	public $object;
	public $sql_file;
	public $sql_file_name;
	public $time_start;
	public $records_updated;
	public $records_created;
	public $records_deleted;
	public $create_query;
	public $time_field;
	public $table;
	public $fields_functions=Array();
	public $mapping_column=Array();
	public $entity_created = array();
	public $entity_updated = array();	
	public $entity_deleted = array();	
	public $fields_jump_update = array();
	public $postprocess_function;
	public $postprocess;
	public $bidirectional;	// NOT USED
	
	protected $cache = array();
	
	function __construct($module,$fields,$external_code,$time_start,$fields_auto_create,$fields_auto_update,$table,$import_id=false,$fields_jump_update=Array()){
		global $root_directory, $erpconnector_dir,$table_prefix;
		if ($import_id){
			$this->import_id = $import_id;
		}	
		else{
			$this->import_id = rand();
		}
		$this->erpdir = $erpconnector_dir;
		$this->time_start = $time_start;
		$this->records_updated = 0;
		$this->records_created = 0;
		//setto il modulo
		$this->module = $module;
		$this->object = CRMEntity::getInstance($this->module);
		if ($this->module == 'Users') {
			$this->object->tab_name = Array($table_prefix.'_users');
			$this->object->tab_name_index = Array($table_prefix.'_users'=>'id');
		}
		//check table: rimuovere tabelle che non fanno parte del "core" del modulo (tipo ticketcomments)
		foreach ($this->object->tab_name_index as $tablename=>$index){
			if (!in_array($tablename,$this->object->tab_name))
				unset ($this->object->tab_name_index[$tablename]);
		}
		foreach ($fields as $key=>$item) {
			//Per gestire gli 'as' nei campi della select
			$tmp_key = explode(' as ',$key);
			if(sizeof($tmp_key) > 0){
				if ($tmp_key[sizeof($tmp_key)-1] != '') {
					$key = $tmp_key[sizeof($tmp_key)-1];
				} else {
					$key = $tmp_key[0];
				}
			}
			//end
			$fields_real[strtolower($key)] = strtolower($item);
		}
		$this->mapping_real = $fields;
		$fields = 	$fields_real;
		$this->mapping = $fields;
		$this->mapping_inverse = array_flip($fields);
		$this->fields_auto_create = $fields_auto_create;
		$this->fields_auto_update = $fields_auto_update;
		$this->time_field = $time_field;
		$this->table = $table;
		$this->get_fields($fields);
		$this->external_code = $external_code;
		$this->get_assigned_user_global();
		$this->fields_jump_update = $fields_jump_update;
		$this->file_=$root_directory.$this->erpdir."sql/total.sql";
		$this->file_config = $file_config;
	}
	
	private function get_fields($fields){
		global $adb,$current_user,$table_prefix;
		//setto i campi/tabelle da importare
		$sql = "select tablename,columnname,fieldname,uitype from ".$table_prefix."_field where fieldname in (".generateQuestionMarks($fields).") and tabid = ?";
		$params = array_values($fields);
		$params[] = getTabid($this->module);
		$res = $adb->pquery($sql,$params);
		if ($res){
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				$this->fields[$row['tablename']][] = $row['columnname'];
				$this->fields_name[$row['tablename']][$row['columnname']] = strtolower($row['fieldname']);
				switch($row['uitype']){
					case 99: //password
						$this->fields_functions[$row['fieldname']] = 'set_password';
						break;
					case 5: //data
						$this->fields_functions[$row['fieldname']] = 'get_date_short_value';
						break;
					case 117: //valuta
						$this->fields_functions[$row['fieldname']] = 'get_valuta';
						break;
					/*
					case 56: //checkbox
						$this->fields_functions[$row['fieldname']] = 'get_checkbox_value';
						break;
					case 53: //assegnatario
						$this->fields_functions[$row['fieldname']] = 'get_assigned_user';
						break;
					case 70: //data e ora
						$this->fields_functions[$row['fieldname']] = 'get_date_value';
						break;
					case 10: //uitype10 field
						$this->fields_functions[$row['fieldname']] = 'get_ui10_value';
						break;
					*/
					default:
						break;	
				}
				if (in_array($this->mapping_inverse[$row['fieldname']],array('account_external_code','contact_external_code','quote_external_code','salesorder_external_code','user_external_code'))) {
					$this->fields_functions[$row['fieldname']] = 'crmv_get_crmid_from_external_code';
				}
			}
		}
		//aggiungo i campi da importare di default nella crmentity
		if (in_array($table_prefix.'_crmentity',$this->object->tab_name)){
			if (array_search('createdtime',$this->fields_name) === false){
				$this->fields_auto_create[$table_prefix.'_crmentity']['createdtime'] = $this->time_start;
			}
			if (array_search('modifiedtime',$this->fields_name) === false){	
				$this->fields_auto_update[$table_prefix.'_crmentity']['modifiedtime'] = $this->time_start;
				$this->fields_auto_create[$table_prefix.'_crmentity']['modifiedtime'] = $this->time_start;
			}
			$this->fields_auto_create[$table_prefix.'_crmentity']['setype'] = $this->module;
		}
	}
	
	private function get_existing_entity(){
		global $adb,$log,$table_prefix;
		$LVU = ListViewUtils::getInstance();
		$sql = "select tablename from ".$table_prefix."_field where tabid = ? and fieldname = ?";
		$params[] = getTabid($this->module);
		$params[] = $this->mapping[$this->external_code];
		$res = $adb->pquery($sql,$params);
		if ($res){
			$external_code = $adb->query_result($res,0,'tablename').".".$this->mapping[$this->external_code];
		}
		$qry = $LVU->getListQuery($this->module,"and $external_code is not NULL and ".$table_prefix."_crmentity.deleted = 0");
		$qry = replaceSelectQuery($qry,$this->getkey('full').",".$external_code);
		$res=$adb->query($qry);
		while($row=$adb->fetchByAssoc($res,-1,false)){
			$this->existing_entity[$row[$this->mapping[$this->external_code]]] = $row[$this->getkey()];
		}
	}
	
	private function get_existing_entity_runtime_unique($row){
		global $adb,$log,$table_prefix;
		if (!isset($this->cache[$this->module]['query_unique'])){
			$LVU = ListViewUtils::getInstance();
			$sql = "select tablename,columnname,fieldname from ".$table_prefix."_field where tabid = ?";
			$params[] = getTabid($this->module);
			foreach ($this->external_code as $f){
				$params[] = $this->mapping[$f];
			}
			$sql.=" and fieldname in (".generateQuestionMarks($this->external_code).")";
			$res = $adb->pquery($sql,$params);
			$external_code_expression = '';
			$params = Array();
			if ($res){
				while($r = $adb->fetchByAssoc($res,-1,false)){
					$external_code_expression.=" and ".$r['tablename'].".".$r['columnname']." = ?";
					$fieldnames[] = $this->mapping_inverse[$r['fieldname']];
//					$params[] = $row[$this->mapping_inverse[$r['fieldname']]];
				}
			}
			if ($this->module == 'SalesOrder') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_salesorder '.
				  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_salesorder.salesorderid '.
				  'INNER JOIN '.$table_prefix.'_sobillads ON '.$table_prefix.'_salesorder.salesorderid = '.$table_prefix.'_sobillads.sobilladdressid '.
				  'INNER JOIN '.$table_prefix.'_soshipads ON '.$table_prefix.'_salesorder.salesorderid = '.$table_prefix.'_soshipads.soshipaddressid '.
				  'INNER JOIN '.$table_prefix.'_salesordercf ON '.$table_prefix.'_salesordercf.salesorderid = '.$table_prefix.'_salesorder.salesorderid '.
				  'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Products') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_products '.
				  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_products.productid '.
				  'INNER JOIN '.$table_prefix.'_productcf ON '.$table_prefix.'_products.productid = '.$table_prefix.'_productcf.productid '.
				  'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Accounts') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_account '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_account.accountid '.
			  'INNER JOIN '.$table_prefix.'_accountbillads ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountbillads.accountaddressid '.
			  'INNER JOIN '.$table_prefix.'_accountshipads ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountshipads.accountaddressid '.
			  'INNER JOIN '.$table_prefix.'_accountscf ON '.$table_prefix.'_account.accountid = '.$table_prefix.'_accountscf.accountid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Vendors') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_vendor '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_vendor.vendorid '.
			  'INNER JOIN '.$table_prefix.'_vendorcf ON '.$table_prefix.'_vendor.vendorid = '.$table_prefix.'_vendorcf.vendorid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Invoice') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_invoice '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_invoice.invoiceid '.
			  'INNER JOIN '.$table_prefix.'_invoicebillads ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoicebillads.invoicebilladdressid '.
			  'INNER JOIN '.$table_prefix.'_invoiceshipads ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoiceshipads.invoiceshipaddressid '.				
			  'INNER JOIN '.$table_prefix.'_invoicecf ON '.$table_prefix.'_invoice.invoiceid = '.$table_prefix.'_invoicecf.invoiceid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Quotes') {
				$qry = 'SELECT crmid FROM '.$table_prefix.'_quotes '.
			  'INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_quotes.quoteid '.
			  'INNER JOIN '.$table_prefix.'_quotesbillads ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotesbillads.quotebilladdressid '.
			  'INNER JOIN '.$table_prefix.'_quotesshipads ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotesshipads.quoteshipaddressid '.								
			  'INNER JOIN '.$table_prefix.'_quotescf ON '.$table_prefix.'_quotes.quoteid = '.$table_prefix.'_quotescf.quoteid '.
			   'WHERE '.$table_prefix.'_crmentity.deleted = 0';
				$qry.=" $external_code_expression";
			} elseif ($this->module == 'Users') {
				$qry = $LVU->getListQuery($this->module,$external_code_expression);
				$qry = replaceSelectQuery($qry,'id');
			} else {
				$qry = $LVU->getListQuery($this->module,$external_code_expression);
				$qry = replaceSelectQuery($qry,'crmid');
			}
			$this->cache[$this->module]['query_unique'] = $qry;
			$this->cache[$this->module]['query_fieldnames'] = $fieldnames;
			$this->cache[$this->module]['query_params'] = $params;
		}
		$qry = $this->cache[$this->module]['query_unique'];
		$params = $this->cache[$this->module]['query_params'];
		foreach ($this->cache[$this->module]['query_fieldnames'] as $fname){
			$params[] = $row[$fname];
		}
		$res=$adb->pquery($qry,$params);
		if ($res && $adb->num_rows($res)>0){
			if ($this->module == 'Users'){
				return $adb->query_result_no_html($res,0,'id');
			} else {
				return $adb->query_result_no_html($res,0,'crmid');
			}
		}
		return false;
	}
	
	private function get_existing_entity_runtime($row){
		global $log,$table_prefix;
		if (!$row || $row == ''){
			return false;
		}
		global $adb,$log;
		if (!isset($this->cache[$this->module]['query_unique'])){
			$LVU = ListViewUtils::getInstance();
			$sql = "select tablename from ".$table_prefix."_field where tabid = ? and fieldname = ?";
			$params[] = getTabid($this->module);
			$params[] = $this->mapping[$this->external_code];
			$res = $adb->pquery($sql,$params);
			if ($res){
				$external_code = $adb->query_result($res,0,'tablename').".".$this->mapping[$this->external_code];
			}
			$condition = "and $external_code =?";
			if ($this->module == 'Users'){
				$qry = $LVU->getListQuery($this->module,"and $external_code = ?");
				$qry = replaceSelectQuery($qry,'id');
			} else {
				$qry = $LVU->getListQuery($this->module,"and $external_code = ?");
				$qry = replaceSelectQuery($qry,$table_prefix.'_crmentity.crmid'); // crmv@155146
			}
			$this->cache[$this->module]['query_unique'] = $qry;
		}
		$qry = $this->cache[$this->module]['query_unique'];
		$res=$adb->pquery($qry,Array($row[$this->external_code]));
//		$log->fatal($res->sql);
		if ($res && $adb->num_rows($res)>0){
			if ($this->module == 'Users'){
				return $adb->query_result_no_html($res,0,'id');
			} else {
				return $adb->query_result_no_html($res,0,'crmid');
			}
		}
		return false;
	}
	
	private function make_create_files(){
		foreach ($this->object->tab_name_index as $t=>$k){
			//file creazione
			$this->sql_file_name_create[$t] = $this->erpdir."sql/".$this->module."_sql_create_".$t."_".$this->import_id.".csv";
			@unlink($this->sql_file_name_create[$t]);
			$this->sql_file_create[$t] = fopen($this->sql_file_name_create[$t] , 'w+');
			//file aggiornamento
			$this->sql_file_name_update[$t] = $this->erpdir."sql/".$this->module."_sql_update_".$t."_".$this->import_id.".sql";
			@unlink($this->sql_file_name_update[$t]);
			$this->sql_file_update[$t] = fopen($this->sql_file_name_update[$t] , 'w+');
		}
		@unlink($this->file_create);
		$this->file_create = $this->erpdir."sql/".$this->module."_sql_create_global_".$this->import_id.".sql";
	}
	
	private function get_column_create($table_name){
		$create = $this->getcached_create_arr(false);
		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				$create[$table][$field] = '';
			}
			$create[$table][$this->object->tab_name_index[$table]] = '';
		}	
		foreach ($this->object->tab_name_index as $t=>$k){
			if (!$create[$t][$k])
				$create[$t][$k] = '';
		}
		foreach ($this->fields_runtime as $table=>$arr){
			foreach ($arr as $column=>$value){
				$create[$table][$column] = '';
			}
		}
		$sql_number_create = $this->sequence_number();
		if ($sql_number_create[0]){
			$create[$sql_number_create[1]][$sql_number_create[2]] = '';
		}
		return array_keys($create[$table_name]);
	}
	
	public function go(&$res){
		global $adb;
		if ($res){
			$this->make_create_files();
			$records = 0;
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				if (!isset($this->cache[$this->module]['existing_function'])){
					if (is_array($this->external_code)){
						$this->cache[$this->module]['existing_function'] = 'get_existing_entity_runtime_unique';
					}
					else{
						$this->cache[$this->module]['existing_function'] = 'get_existing_entity_runtime';
					}
				}
				$found = false;
				if (method_exists($this,"preprocess")){
					$this->{"preprocess"}($row);
				}
				$found = $this->{$this->cache[$this->module]['existing_function']}($row);
				if ($found !== false){
					$this->update($row,$found);
				}	
				else{
					$this->create($row);
				}
				$records++;
			}
			$this->close_files();


			if ($records > 0){
				if (method_exists($this,"preexecute")) {
					$this->{"preexecute"}();
				}

				$this->execute();

				if (method_exists($this,"postprocess")) {
					$this->{"postprocess"}();
				}
			}

			$this->delete_files();
			return Array('records_created'=>count($this->entity_created),'records_updated'=>count($this->entity_updated),'records_deleted'=>count($this->entity_deleted));
		}
	}
	
	private function delete($data,$id){
		global $table_prefix;
		$table = $table_prefix.'_crmentity';
		if(!in_array($table,$this->object->tab_name)){
			$table = $this->object->tab_name[0];
		}			
		if ($table == $table_prefix.'_crmentity')
			$sql  = "update $table set deleted = 1,modifiedtime='".$this->time_start."'";
		elseif ($table == $table_prefix.'_users')
			$sql  = "update $table set status = 'Inactive',date_modified='".$this->time_start."'";
		$sql.=" where ".$this->getkey('full',$table)." = $id\n";
		if ($id){
			fwrite($this->sql_file_update[$table],$sql);
			$this->entity_deleted[] = $id;
		}				
	}
	
	private function close_files(){
		foreach ($this->object->tab_name_index as $t=>$k){
			fclose($this->sql_file_create[$t]);
			fclose($this->sql_file_update[$t]);
		}
	}
	
	private function delete_files(){
		foreach ($this->object->tab_name_index as $t=>$k){
			@unlink($this->sql_file_name_create[$t]);
			@unlink($this->sql_file_name_update[$t]);
		}
		@unlink($this->file_create);
	}
	
	private function getcached_update_arr() {
		if ($this->update_arr)
			return $this->update_arr;
		if (is_array($this->fields_auto_update)){
			foreach ($this->fields_auto_update as $table => $arr){
					foreach ($arr as $field=>$def_value){
						$this->update_arr[$table][$field] = $def_value;
					}			
			}

		}
		return $this->update_arr;
	}	
	
	private function update($data,$id){
		global $adb;
		$update = $this->getcached_update_arr();
		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				if (in_array($this->fields_name[$table][$field],$this->fields_jump_update))
					continue;
				if ($this->fields_functions[$this->fields_name[$table][$field]] != '' && method_exists($this,$this->fields_functions[$this->fields_name[$table][$field]])) {
					$update[$table][$field] = $this->{$this->fields_functions[$this->fields_name[$table][$field]]}($this->fields_name[$table][$field],$data,$table,$field,'update');
				}		
				else{
					$update[$table][$field] = $data[$this->mapping_inverse[$this->fields_name[$table][$field]]];		
				}
			}
		}
		foreach ($this->fields_runtime as $table=>$arr){
			foreach ($arr as $column=>$value){
				$update[$table][$column] = $value;
			}
		}
		if ($this->skip_entity)
			return; //se c'� qualche motivo per ignorarlo salto!
		foreach ($update as $table=>$arr){
//			array_walk($arr,'sanitize_array_sql');
			$sql  = "update $table set ";
			$first = true;
			$params = Array();
			foreach ($arr as $field=>$value){
				if (!$first)
					$sql .=",";
				$adb->format_columns($field); //crmv@147096
				$sql .=" $field = ?";
				$first = false;
				$params[] = $value;
			}
			$sql.=" where ".$this->getkey('full',$table)." = ?";
			$params[] = $id;
			$sql=$adb->convert2Sql($sql,$adb->flatten_array($params));
			if ($id){
				fwrite($this->sql_file_update[$table],$sql.";\n");
			}	
			unset($params);
			unset($sql);
		}
		$this->entity_updated[] = $id;
	}
	
	private function getkey($mode = '',$table = false){
		global $table_prefix;
		if (!$table){
			$table = $table_prefix.'_crmentity';
			if(!in_array($table,$this->object->tab_name)){
				$table = $this->object->tab_name[0];
			}
		}
		if ($mode == 'full')
			return $table.".".$this->object->tab_name_index[$table];
		else
			return $this->object->tab_name_index[$table];
	}
	
	private function getcached_create_arr($data = true) {
		if ($this->create_arr)
			return $this->create_arr;
		foreach ($this->fields_auto_create as $table => $arr){
				foreach ($arr as $field=>$def_value){
					if (!$data) 
						$def_value = '';
					$this->create_arr[$table][$field] = $def_value;
				}			
		}
		return $this->create_arr;
	}	
	
	private function create($data){
		global $adb,$table_prefix;
		$table = $table_prefix.'_crmentity';
		if(!in_array($table,$this->object->tab_name)){
			$table = $this->object->tab_name[0];
		}
		$id = $adb->getUniqueID($table);
		$create = $this->getcached_create_arr();
		if (!empty($this->mapping_entity)){
			foreach ($this->mapping_entity as $f){
				$f = strtolower($f);
				if (trim($data[$f]) != '')
					$create[$table_prefix."_crmentity"][$this->mapping[$f]] = $data[$f];
			}
		}
		foreach ($this->fields as $table => $arr){
			foreach ($arr as $field){
				if ($this->fields_functions[$this->fields_name[$table][$field]] != '' && method_exists($this,$this->fields_functions[$this->fields_name[$table][$field]])) {
					$create[$table][$field] = $this->{$this->fields_functions[$this->fields_name[$table][$field]]}($this->fields_name[$table][$field],$data,$table,$field,'create');
				}		
				else{
					$create[$table][$field] = $data[$this->mapping_inverse[$this->fields_name[$table][$field]]];
				}
			}
			$create[$table][$this->object->tab_name_index[$table]] = $id;
		}	
		foreach ($this->object->tab_name_index as $t=>$k){
			if (!$create[$t][$k])
				$create[$t][$k] = $id;
		}
		foreach ($this->fields_runtime as $table=>$arr){
			foreach ($arr as $column=>$value){
				$create[$table][$column] = $value;
			}
		}				
		$sql_number_create = $this->sequence_number();
		if ($sql_number_create[0]){
			$create[$sql_number_create[1]][$sql_number_create[2]] = $this->object->setModuleSeqNumber("increment",$this->module);
		}
		foreach ($this->object->tab_name_index as $t=>$k){
			$this->insert_into_create_file($t,$create);		

		}
		$this->entity_created[] = $id;
		if ($this->module == 'Users') {
			//crmv@131239
			(!empty($this->cache[$this->module]['roleid'])) ? $roleid = $this->cache[$this->module]['roleid'] : $roleid = $create[$table_prefix.'_user2role']['roleid'];
			fwrite($this->sql_file_update[$table_prefix.'_users'],"insert into ".$table_prefix."_user2role values ($id,'".$roleid."');\n");
			//crmv@131239e
		}
	}
	
	private function insert_into_create_file($table,$create){
		global $adb;
		if ($adb->isMySQL()){
			fputcsv2($this->sql_file_create[$table],$create[$table]);
		}
		else{
			array_walk($create[$table],'sanitize_array_sql');
			fwrite($this->sql_file_create[$table],"insert into $table (".implode(",",array_keys($create[$table])).") values ");
			fwrite($this->sql_file_create[$table],"(".implode(",",$create[$table]).")\r\n");
		}
	}
	
	private function sequence_number(){
		if ($this->sequence_number)
			return $this->sequence_number;
		global $adb,$table_prefix;
		$sql = "select tablename,columnname from ".$table_prefix."_field where tabid = ? and uitype = 4";
		$res = $adb->pquery($sql,Array(getTabid($this->module)));
		if ($res && $adb->num_rows($res) > 0){

			$this->sequence_number = Array(true,$adb->query_result($res,0,'tablename'),$adb->query_result($res,0,'columnname'));	

		}
		else
			$this->sequence_number = Array(false);
		return 	$this->sequence_number;
	}
	
	private function execute(){
		global $dbconfig,$adb,$root_directory,$log;
		if ($adb->isMySQL()) {
			$create_file = fopen($this->file_create,'w+');
			$pre_create = "/*!40101 SET NAMES utf8 */;\n/*!40101 SET SQL_MODE=''*/;\n/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n";
			fwrite($create_file,$pre_create);
			foreach ($this->object->tab_name_index as $t=>$k){
				//faccio le create
				$fields = $this->get_column_create($t);
				$adb->format_columns($fields); //crmv@147096
				$file = $root_directory.$this->sql_file_name_create[$t];
				if (filesize($file)>0){
					$sql_load = 'LOAD DATA LOCAL INFILE \''.$file.'\' INTO TABLE '.$t.' FIELDS ESCAPED BY \'\' TERMINATED BY \',\' OPTIONALLY ENCLOSED BY \'"\' LINES TERMINATED BY \'\n\' ('.implode(",",$fields).');'."\n";
					fwrite($create_file,$sql_load);
				}
			}
			$post_create = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";
			fwrite($create_file,$post_create);			
			fclose($create_file);
			if (filesize($this->file_create)>0){
				$filename = $root_directory.$this->file_create;
				$port = str_replace(":","",$dbconfig['db_port']);
				$string_create = "mysql --local-infile -h {$dbconfig['db_server']} -u {$dbconfig['db_username']} --password=\"{$dbconfig['db_password']}\" -P {$port} {$dbconfig['db_name']} < {$filename}";
				system($string_create,$result);
				if ($result != '0'){
					$str_err = "Error updating file $this->sql_file_name_update";
					$log->fatal($str_err);
				}
			}
			foreach ($this->object->tab_name_index as $t=>$k){	
				//faccio le insert
				if (filesize($this->sql_file_name_update[$t])>0){
					$filename = $root_directory.$this->sql_file_name_update[$t];
					$port = str_replace(":","",$dbconfig['db_port']);
					$string_update = "mysql --local-infile -h {$dbconfig['db_server']} -u {$dbconfig['db_username']} --password=\"{$dbconfig['db_password']}\" -P {$port} {$dbconfig['db_name']} < {$filename}";
					system($string_update,$result);
					if ($result != '0'){
						$str_err = "Error updating file {$this->sql_file_name_update[$t]}";
						$log->fatal($str_err);
					}
				}								
			}
		} else {
			foreach ($this->object->tab_name_index as $t=>$k){
				//faccio le create
				$file = $root_directory.$this->sql_file_name_create[$t];
				$create_file = fopen($file,'rb');
				while (($buffer = fgets($create_file, 4096)) !== false) {
					if (trim($buffer) != ''){
						$adb->query($buffer);
					}
				}
			}
			foreach ($this->object->tab_name_index as $t=>$k){	
				//faccio le insert
				if (filesize($this->sql_file_name_update[$t])>0){
					$file = $root_directory.$this->sql_file_name_update[$t];
					$update_file = fopen($file,'rb');
					while (($buffer = fgets($update_file, 4096)) !== false) {
						if (trim($buffer) != ''){
							$adb->query($buffer);
						}
					}
				}								
			}
		}
	}
	
	/*
	 * funzioni per compiere modifiche/conversioni dei dati in base all'uitype
	 */
	private function set_password($password_field,$data,$table,$field,$mode){
		global $table_prefix;
		$user_name=$data[$this->mapping_inverse[$this->fields_name[$table_prefix."_users"]['user_name']]];
		$password=$data[$this->mapping_inverse[$this->fields_name[$table_prefix."_users"][$password_field]]];
		$DEFAULT_PASSWORD_CRYPT_TYPE = (version_compare(PHP_VERSION, '5.3.0') >= 0)?
				'PHP5.3MD5': 'MD5';
		$encripted_paasword = $this->getEncryptedPassword($user_name,$DEFAULT_PASSWORD_CRYPT_TYPE,$password);
		return $encripted_paasword;
	}
	
	private function get_checkbox_value($field,$data,$table,$columnname,$mode){
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		return $value; //il valore � gi� formattato con 0=>no 1=>si
	}
	
	private function get_assigned_user($field,$data,$table,$columnname,$mode){
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		if ($this->users[$value] != ''){
			$value = $this->users[$value];
		}
		else{
			$value = 1;
		}
		return $value;
	}
	
	private function get_date_value($field,$data,$table,$columnname,$mode){
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		if (trim($value) == ''){
			return 'NULL';
		}
		$date_decoded = crmv_decode_date($value);
		return $date_decoded;
	}
	
	private function get_date_short_value($field,$data,$table,$columnname,$mode){
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		if ($value != '') {
			$value = substr($value,0,10);
		}
		return $value;
		
	}
	
	private function get_valuta($field,$data,$table,$columnname,$mode){
		global $adb;
		$ret_val = 1; //fallback su euro se valuta non presente
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		if ($value != '') {
			$valuta = crmv_get_valuta($value); //funzione in utils.php
			if ($valuta != 0) {
				$ret_val = $valuta;
			}
		}
		return $ret_val;
	}
	
	private function get_ui10_value($field,$data,$table,$columnname,$mode){
		global $adb;
		$value = $data[$this->mapping_inverse[$this->fields_name[$table][$columnname]]];
		if ($value == ''){
			return $value;
		}
		return $value;
	}
	
	private function getEncryptedPassword($userName, $cryptType, $userPassword) {
		$salt = substr($userName, 0, 2);
		if($cryptType == 'MD5') {
			$salt = '$1$' . $salt . '$';
		} elseif($cryptType == 'BLOWFISH') {
			$salt = '$2$' . $salt . '$';
		} elseif($cryptType == 'PHP5.3MD5') {
			$salt = '$1$' . str_pad($salt, 9, '0');
		}
		$computedEncryptedPassword = crypt($userPassword, $salt);
		return $computedEncryptedPassword;
	}
	
	/*
	 * funzioni per compiere modifiche/conversioni dei dati alla fine del processo di importazione
	 * formato postprocess_nomemodulo()
	 */	
	public function postprocess(){
		global $adb,$root_directory,$table_prefix;
		
		//crmv@144125
		if ($this->module != 'Users') {
			$this->updateEntityNameCache();
		}
		//crmv@144125e
		
		switch($this->module){
			case 'Users':
				$crypt_type = (version_compare(PHP_VERSION, '5.3.0') >= 0)?'PHP5.3MD5':'MD5';
				include_once('modules/Users/Users.php');
				require_once('modules/Users/CreateUserPrivilegeFile.php');
				foreach ($this->entity_created as $id){
					$obj = CRMEntity::getInstance('Users');
					$obj->retrieve_entity_info($id,'Users');
					$obj->id = $id;
					
					// crmv@OPER8590
					$color = substr(str_shuffle('abcdef0123456789'), 0, 6);
					$cal_color = $color.$color;
					
					if ($this->fields_auto_create[$table_prefix.'_users']['user_password']) {
						$encrypted_password = $obj->encrypt_password($this->fields_auto_create[$table_prefix.'_users']['user_password'], $crypt_type);
						$query = "UPDATE ".$table_prefix."_users SET cal_color = ?, user_password=?, crypt_type=? where id=?";
						$params = array($cal_color,$encrypted_password,$crypt_type, $id);
					} else {
						$query = "UPDATE ".$table_prefix."_users SET cal_color = ?, crypt_type=? where id=?";
						$params = array($cal_color,$crypt_type, $id);
					}
					
					$adb->pquery($query,$params);
					// crmv@OPER8590e
					
					createUserPrivilegesfile($id);
					createUserSharingPrivilegesfile($id);
					$obj->createAccessKey();
					$obj->saveHomeStuffOrder($id);
					/*TODO: scrivere setreminder
					 $focus->resetReminderInterval($prev_reminder_interval);
					*/
					
					// crmv@63349 - if create a new user and public notification
					if (isModuleInstalled('ModComments')) {
						$newPublicTalks = in_array($obj->column_fields['receive_public_talks'], array('1', 'on'));
						if ($newPublicTalks) {
							$modMsg = CRMEntity::getInstance('Messages');
							$modMsg->regenCommentsMsgRelTable($obj->id);
						} else if ($oldPublicTalks != $newPublicTalks) {
							$modMsg = CRMEntity::getInstance('Messages');
							$modMsg->regenCommentsMsgRelTable($obj->id);
						}
					}
					// crmv@63349e
					
					//crmv@105882 - initialize home for all modules
					require_once('include/utils/ModuleHomeView.php');
					$MHW = ModuleHomeView::install(null, $obj->id);
					//crmv@105882e
				}
				if (isModuleInstalled('ModNotifications')) {
					$modNotificationsFocus = ModNotifications::getInstance(); // crmv@164122
					$modNotificationsFocus->saveDefaultModuleSettings($this->entity_created);
				}
				break;
			case 'Invoice':
//				$entities = array_merge($this->entity_created,$this->entity_updated);
//				if (count($entities)>0){
//					$sql = "update vte_invoice set taxtype = ?,subject = numero_fattura_erp,taxtype=? where invoiceid in (".generateQuestionMarks($entities).")";
//					$params = Array('percent','group',$entities);
//					$adb->pquery($sql,$params);
//				}
				break;	
			case 'SalesOrder':
//				$entities = array_merge($this->entity_created,$this->entity_updated);
//				if (count($entities)>0){
//					$sql = "update vte_salesorder set taxtype = ?,subject = numero_ordine_erp,taxtype=? where salesorderid in (".generateQuestionMarks($entities).")";
//					$params = Array('percent','group',$entities);
//					$adb->pquery($sql,$params);
//				}
				break;	
			default:
				//$this->launch_workflows();	//crmv@91571
				//$this->launch_processes();	//crmv@117355
				break;		
		}		
	}
	
	// crmv@91571
	protected function launch_workflows(){
		require_once('include/events/SqlResultIterator.inc');
		require_once('modules/com_workflow/VTWorkflowManager.inc');//crmv@207901
		require_once('modules/com_workflow/VTTaskManager.inc');//crmv@207901
		require_once('modules/com_workflow/VTTaskQueue.inc');//crmv@207901
		require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
		require_once('include/Webservices/Utils.php');
		require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
		require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
		require_once("include/Webservices/DataTransform.php");
		require_once("include/Webservices/WebServiceError.php");
		require_once('include/Webservices/ModuleTypes.php');
		require_once('include/Webservices/Retrieve.php');
		require_once('include/Webservices/Update.php');
		require_once('include/Webservices/WebserviceField.php');
		require_once('include/Webservices/EntityMeta.php');
		require_once('include/Webservices/VtenextWebserviceObject.php');//crmv@207871
		require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
		require_once('modules/com_workflow/VTEventHandler.inc');//crmv@207901
		
		$wfh = new VTWorkflowEventHandler();
		
		// now save the workflows with a special optimized method
		// note: workflow with the clause ("has changed into") won't work.
		if (is_array($this->entity_created) && count($this->entity_created) > 0) {
			$wfh->massWorkflows('create', $this->module, $this->entity_created);
		}
		if (is_array($this->entity_updated) && count($this->entity_updated) > 0) {
			$wfh->massWorkflows('update', $this->module, $this->entity_updated);
		}
		
	}
	// crmv@91571e
	
	//crmv@117355
	protected function launch_processes() {
		$entities = array_merge($this->entity_created,$this->entity_updated);
		if (!empty($entities)) {
			foreach($entities as $id) {
				$focus = CRMEntity::getInstance($this->module);
				$focus->retrieve_entity_info_no_html($id,$this->module);
				(in_array($id,$this->entity_created)) ?  $focus->mode = '' : $focus->mode = 'edit';
				// crmv@189720
				if ($focus->mode == 'edit') {
					require_once('data/VTEntityDelta.php');
					$entityDelta = new VTEntityDelta();
					$entityDelta->setOldEntity(getSalesEntityType($id),$id);
				}
				// crmv@189720e
				require_once("include/events/include.inc");
				require_once("modules/Settings/ProcessMaker/ProcessMakerHandler.php");
				$em = new VTEventsManager($adb);
				// Initialize Event trigger cache
				$em->initTriggerCache();
				$entityData  = VTEntityData::fromCRMEntity($focus);
				if (in_array($id,$this->entity_created)) $entityData->setNew(true);
				$processMakerHandler = new ProcessMakerHandler();
				if (in_array($id,$this->entity_updated)) $processMakerHandler->real_save = false;	// comment this line in order to enable also the event "on change"
				$processMakerHandler->handleEvent('vte.entity.aftersave.processes', $entityData); // crmv@177677 crmv@207852
				
				//echo "$id $this->module '$focus->mode'\n";
			}
		}
	}
	//crmv@117355e
	
	//crmv@144125
	protected function updateEntityNameCache() {
		$ENU = EntityNameUtils::getInstance();
		
		if (is_array($this->entity_created) && count($this->entity_created) > 0) {
			$ENU->rebuildForRecords($this->module, $this->entity_created);
		}
		if (is_array($this->entity_updated) && count($this->entity_updated) > 0) {
			$ENU->rebuildForRecords($this->module, $this->entity_updated);
		}
	}
	//crmv@144125e
	
	/*
	 * funzioni per compiere modifiche/conversioni dei dati all'inizio dell'importazione
	 * formato preprocess()
	 */	
	/*
	private function preprocess(&$row){
		global $adb;
		switch ($this->module){
			case 'Products':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				$this->fields_runtime['vte_products']['productcode'] = $row['company'].$row['item'].$row['colour'].$row['size'];
				break;
			case 'SalesOrder':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				$this->fields_runtime['vte_salesorder']['subtotal'] = $row['val_net'];
				break;
			case 'Invoice':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				$this->fields_runtime['vte_invoice']['subtotal'] = $row['val_net'];
				$this->fields_runtime['vte_invoice']['total'] = $row['val_fat'];
				break;
			case 'Accounts':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				break;
			case 'Vendors':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				break;
			case 'Scadenziario':
				$smownerid = $this->get_smownerid($row['company']);
				$this->fields_runtime['vte_crmentity']['smownerid'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['modifiedby'] = $smownerid;
				$this->fields_runtime['vte_crmentity']['smcreatorid'] = $smownerid;
				//campo customer
				$fields_key = Array(
					'company'=>'cf_525',
					'customer'=>'cf_469',
				);
				$row_key = Array(
					'company'=>$row['company'],
					'customer'=>$row['customer'],
				);
				$found_customer = get_existing_entity_runtime_unique_offline('Accounts',$fields_key,$row_key);
				if ($found_customer!==false){
					$row['customer'] = $found_customer;
				}
				else{
					$row['customer'] = 0;
				}
				//campo fattura
				$fields_key = Array(
					'company'=>'cf_1004',
					'year_key'=>'cf_1032',
					'n_register'=>'cf_1005',
					'n_doc'=>'subject',
				);
				$row_key = Array(
					'company'=>$row['company'],
					'year_key'=>$row['year_key'],
					'n_register'=>$row['n_register'],
					'n_doc'=>$row['n_doc'],
				);
				$found_invoice = get_existing_entity_runtime_unique_offline('Invoice',$fields_key,$row_key);
				if ($found_invoice!==false){
					$row['year_key'] = $found_invoice;
				}
				else{
					$row['year_key'] = 0;
				}
				break;
			default:
				break;	
		}
	}
	
	function get_smownerid($ditta){
		switch($ditta){
			case '5':
				return 6597;
			case '8':
				return 1;
			case '11':
				return 6545;
			default:
				return 1;	 		
		}
	}
	*/
	
	/*
	 * funzioni per inserire l'assegnatario
	 */
	function get_assigned_user_global(){
		global $adb;
		switch ($this->module){
			default:{
//				$sql =" select id,codice_agente_erp from vte_users where codice_agente_erp <> ''";
//				$res = $adb->query($sql);
//				if ($res){
//					while ($row=$adb->fetchByAssoc($res,-1,false)){
//						$this->users[$row['codice_agente_erp']] = $row['id'];
//					}
//				}
				break;		
			}
		}
	}
	
	private function crmv_get_crmid_from_external_code($fieldname,$data,$table,$columnname,$mode){
		global $adb,$table_prefix;
		$value = '';
		$external_code = $data[$this->mapping_inverse[$fieldname]];
		$mapping = array(
			'account_external_code'=>'Accounts',
			'contact_external_code'=>'Contacts',
			'quote_external_code'=>'Quotes',
			'salesorder_external_code'=>'SalesOrder',
			'user_external_code'=>'Users',
		);
		$module = '';
		if (in_array($this->mapping_inverse[$fieldname],array_keys($mapping))) {
			$module = $mapping[$this->mapping_inverse[$fieldname]];
		}
		if ($module == '') {
			return '';
		}
		if ($this->cache[$module]['external_codes'][$external_code] == '') {
			if ($module == 'Users') {
				$query = "select id from ".$table_prefix."_users where ".$table_prefix."_users.external_code = ?";
				$result = $adb->pquery($query,array($external_code));
				if ($result && $adb->num_rows($result) > 0) {
					$this->cache[$module]['external_codes'][$external_code] = $adb->query_result($result,0,'id');
				} else {
					$this->cache[$module]['external_codes'][$external_code] = 1;	//se non trovo una corrispondenza associo ad admin
				}
			} else {
				$focus = CRMEntity::getInstance($module);
				$query = "select ".$table_prefix."_crmentity.crmid from $focus->table_name inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = $focus->table_name.$focus->table_index where deleted = 0 and $focus->table_name.external_code = ?";
				$result = $adb->pquery($query,array($external_code));
				if ($result && $adb->num_rows($result) > 0) {
					$this->cache[$module]['external_codes'][$external_code] = $adb->query_result($result,0,'crmid');
				}
			}
		}
		return $this->cache[$module]['external_codes'][$external_code];
	}
	
}