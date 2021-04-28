<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class SLAHandler extends VTEventHandler {
	function handleEvent($eventName, $entityData) {
		global $log, $adb,$time_obj,$table_prefix;
		include_once('modules/SLA/SLA.php');
		$sla_config_global = SLA::get_config();
		$moduleName = $entityData->getModuleName();
		if (!(count($sla_config_global)>0 && in_array($moduleName,array_keys($sla_config_global))))
			return; // esco se non ho impostazioni oppure non devo gestire il modulo
		$sla_config = $sla_config_global[$moduleName];
		if($eventName == 'vte.entity.beforesave.modifiable') {//crmv@207852
			$now = date('Y-m-d H:i:s');
			if(!$entityData->isNew()) { //entit� modificata
				//salvataggio vecchio conteggio SLA
				$fields = Array('sla_time',$sla_config['status_field']);
				$query = "select tablename,columnname,fieldname from ".$table_prefix."_field where tabid = ? and fieldname in (".generateQuestionMarks($fields).")";
				$params = Array(getTabId($moduleName),$fields);
				$res = $adb->pquery($query,$params);
				if ($res && $adb->num_rows($res)>0){
					while ($row = $adb->fetchByAssoc($res,-1,false)){
						if ($row['fieldname'] == 'sla_time'){
							$wherefield = $entityData->focus->tab_name_index[$row['tablename']];
							$sql = "select {$row['columnname']} from {$row['tablename']} where {$wherefield} = ?";
							$params = array($entityData->getId());
							$entity_res = $adb->pquery($sql,$params);
							if ($entity_res && $adb->num_rows($entity_res)>0)
								$entityData->oldSLA = $adb->query_result_no_html($entity_res,0,$row['columnname']);
						}	
						elseif ($row['fieldname'] == $sla_config['status_field']){
							$wherefield = $entityData->focus->tab_name_index[$row['tablename']];
							$sql = "select {$row['columnname']} from {$row['tablename']} where {$wherefield} = ?";
							$params = array($entityData->getId());
							$entity_res = $adb->pquery($sql,$params);
							if ($entity_res && $adb->num_rows($entity_res)>0)
								$entityData->oldStatus_SLA = $adb->query_result_no_html($entity_res,0,$row['columnname']);
						}	
					}
				}
				$mode = 'edit';
			}
			else{ //entit� creata
				$mode = 'create';
			}
			$data = $entityData->getData();
			//salvo i nuovi sla e status..
			$entityData->newSLA = $data['sla_time'];
			//crmv@68518
			if($mode == 'create') {
				$entityData->focus->column_fields['time_refresh'] = $now;
			} elseif (empty($data['time_refresh'])) {
				// maybe the field is hidden, read from db
				$time = getSingleFieldValue($table_prefix."_troubletickets", 'time_refresh', $entityData->focus->table_index, $entityData->getId());
				$entityData->focus->column_fields['time_refresh'] = $time;
				$entityData->hiddenTimeRefresh = true; // crmv@193588
			}
			//crmv@68518e
			$entityData->newStatus_SLA = $data[$sla_config['status_field']]; //crmv@67447
			//calcolo lo SLA
			
			$time_obj=new CalcTime($sla_config,$entityData,$now);
			$time_obj->process($mode,'beforesave');
		}

		if($eventName == 'vte.entity.aftersave.first') {//crmv@207852
			$data = $entityData->getData();
			//salvo i nuovi oldsla e oldstatus..
			$entityData->oldSLA = $data['sla_time'];
			// crmv@82159
			if ($entityData->isNew()) {
				$entityData->oldStatus_SLA = '';	// in creation, old status should be empty
			} else {
				$entityData->oldStatus_SLA = $data[$sla_config['status_field']];
			}
			// crmv@82159e
		}
		if($eventName == 'vte.entity.aftersave.last') {//crmv@207852
			//salvataggio nuovo conteggio SLA
			$fields = Array('sla_time',$sla_config['status_field']);
			$query = "select tablename,columnname,fieldname from ".$table_prefix."_field where tabid = ? and fieldname in (".generateQuestionMarks($fields).")";
			$params = Array(getTabId($moduleName),$fields);
			$res = $adb->pquery($query,$params);
			if ($res && $adb->num_rows($res)>0){
				while ($row = $adb->fetchByAssoc($res,-1,false)){
					if ($row['fieldname'] == 'sla_time'){
						$wherefield = $entityData->focus->tab_name_index[$row['tablename']];
						$sql = "select {$row['columnname']} from {$row['tablename']} where {$wherefield} = ?";
						$params = array($entityData->getId());
						$entity_res = $adb->pquery($sql,$params);
						if ($entity_res && $adb->num_rows($entity_res)>0)
							$entityData->newSLA = $adb->query_result_no_html($entity_res,0,$row['columnname']);
					}	
					elseif ($row['fieldname'] == $sla_config['status_field']){
						$wherefield = $entityData->focus->tab_name_index[$row['tablename']];
						$sql = "select {$row['columnname']} from {$row['tablename']} where {$wherefield} = ?";
						$params = array($entityData->getId());
						$entity_res = $adb->pquery($sql,$params);
						if ($entity_res && $adb->num_rows($entity_res)>0)
							$entityData->newStatus_SLA = $adb->query_result_no_html($entity_res,0,$row['columnname']); //crmv@67447
					}	
				}
			}
			//calcolo lo SLA
			$time_obj->reset();
			$time_obj->process('edit','aftersave');
		}
	}
}
class CalcTime {
	var $empty =Array(false,0,""," ","00-00-00","0000-00-00 00:00:00","00:00","0000-00-00 00:00");
	var $result=Array(
		'seconds_elapsed'=>Array('present'=>false,'value'=>''),
		'data_fine_sla'=>Array('present'=>false,'value'=>''),
	);
	
	/**
	 * ME: le 3 variabili successive non risultano essere mai utilizzate.
	 */
	var $calculate_start_sla = false;
	var $calculate_end_sla = false;
	var $calculate_time_elapsed = false;
	static $debug_SLA = false; //boolean to switch debug ON
	static $debug_mode = 'LOG';	 //debug method, options STOUT, LOG
	static $debug_log_file = 'logs/SLA.log';	 //debug log file path
	
	function __construct($config,&$entityData,$now){
		$moduleName = $entityData->getModuleName(); // crmv@123658
		$this->entityData = $entityData;
		$this->entityData->trans_obj = CRMEntity::getInstance('Transitions');
		$this->entityData->trans_obj->Initialize($moduleName); // crmv@123658
		$this->config = $config;
		$this->now_time = (!in_array($now,$this->empty))?strtotime($now):false;
		$this->reset();
	}
	public function debug_print($content,$die=false){
		if (self::$debug_SLA){
			switch(self::$debug_mode){
				case 'STDOUT':
					echo "<br> ".$content;
					break;
				case 'LOG':
					file_put_contents(self::$debug_log_file,"\n".$content,FILE_APPEND);
					break;
			}
			if ($die === true){
				die('SLA STOP');
			}
		}
	}
	function reset($force=false){
		$this->data = $this->entityData->getData();
		//Inizializzata alla start_sla dell'oggetto passato solo se e' diversa dai valori contenuti in $empty
		$this->start_sla = (!in_array($this->data['start_sla'],$this->empty))?strtotime($this->data['start_sla']):false;
		$this->end_sla = (!in_array($this->data['end_sla'],$this->empty))?strtotime($this->data['end_sla']):false;
		$this->time_elapsed = (!in_array($this->data['time_elapsed'],$this->empty))?$this->data['time_elapsed']:0;
		$this->time_refresh = (!in_array($this->data['time_refresh'],$this->empty))?strtotime($this->data['time_refresh']):false;
		$this->sla_time = (!in_array($this->data['sla_time'],$this->empty))?$this->convert_to_seconds($this->data['sla_time']):0;
		$this->close_time = (!in_array(getValidDBInsertDateValue($this->data['due_date']),$this->empty) && !in_array($this->data['due_time'],$this->empty))?strtotime(getValidDBInsertDateValue($this->data['due_date'])." ".$this->data['due_time']):false;
		$this->time_change_status = (!in_array($this->data['time_change_status'],$this->empty))?strtotime($this->data['time_change_status']):false;
		$this->time_elapsed_change_status = (!in_array($this->data['time_elapsed_change_status'],$this->empty))?$this->data['time_elapsed_change_status']:0;
		$this->time_elapsed_idle = (!in_array($this->data['time_elapsed_idle'],$this->empty))?$this->data['time_elapsed_idle']:0;
		$this->time_elapsed_out_sla = (!in_array($this->data['time_elapsed_out_sla'],$this->empty))?$this->data['time_elapsed_out_sla']:0;
		$this->createdtime = $this->get_createdtime();
		$this->hours=$this->config['hours'];
		$this->jump_days=$this->config['jump_days'];
		$this->holidays=$this->config['holidays'];
		$this->force_days=$this->config['force_days'];
		$this->force_days_keys=array_keys($this->config['force_days']);
	}
	function convert_to_seconds($value){
		switch ($this->config['time_measure']){ // crmv@167234
			case 'minutes':
				$value = $value*60;
				break;
			case 'hours':
				$value = $value*3600;
				break;
			case 'days':
				$value = $value*86400;
				break;
		}
		return $value;
	}
	function sanitize_array_sql(&$item,&$key){
		global $adb;
		if(is_string($item)) {
			if($item == '') {
				$item = $adb->database->Quote($item);
			}
			else {
				$item = "'".$adb->sql_escape_string($item). "'";
			}
		} 
		if($item === null) {
			$item = "NULL";
		}
	}
	function get_createdtime(){
		global $adb,$table_prefix;
		$sql = "select createdtime from ".$table_prefix."_crmentity where crmid = ?";
		$params = Array($this->entityData->getId());
		$res = $adb->pquery($sql,$params);
		if ($res && $adb->num_rows($res)== 1){
			$row = $adb->fetchByAssoc($res,-1,false);
			return strtotime($row['createdtime']);
		}
		return false;
	}
	function formatdate($date,$mode=false,$savepoint=false){
		if ($mode == 'date')
			return ($savepoint=='beforesave')?getDisplayDate(date('Y-m-d',$date)):date('Y-m-d',$date);
		elseif ($mode == 'time')
			return date('H:i',$date);
		else
			return date('Y-m-d H:i:s',$date);
	}
	function get_time_refresh($mode,&$fields_update,$end_sla = false){
		global $adb;
		if ($end_sla){ // se forzo l'aggiornamento all'ultima data di cambio di stato
			if ($this->time_change_status && $this->time_change_status<$this->close_time){
				$this->time_refresh = $this->time_change_status;
				$this->time_elapsed = $this->time_elapsed_change_status;
			}	
			else{
				$this->time_refresh = $this->createdtime;
				$this->time_elapsed = 0;
			}	
			$this->save($mode,'time_refresh',$this->formatdate($this->time_refresh),$fields_update);
			$this->save($mode,'time_elapsed',$this->time_elapsed,$fields_update);
		}	
		if (!$this->time_refresh){ // se non ho il tempo dell'ultimo aggiornamento SLA
			$this->debug_print("calcolo time_refresh...");
			if ($this->time_change_status) // lo setto al tempo dell'ultimo cambio di stato
				$this->time_refresh = $this->time_change_status; 
			else{ // lo setto al tempo di creazione del record
				$this->time_refresh = $this->createdtime;
			}
			$this->debug_print("time_refresh = ".date('Y-m-d H:i:s',$this->time_refresh));
			$this->save($mode,'time_refresh',$this->formatdate($this->time_refresh),$fields_update);
		}
	}
	function get_start_sla($mode,&$fields_update){
		global $adb,$table_prefix;
		if (!$this->start_sla){ // se non ho il tempo della partenza dello SLA
			$this->debug_print("calcolo start_sla...");
			if ($this->time_change_status) // lo setto al tempo dell'ultimo cambio di stato
				$this->start_sla = $this->time_change_status; 
			else{ // lo setto al tempo di creazione del record
				$sql = "select createdtime from ".$table_prefix."_crmentity where crmid = ?";
				$params = Array($this->entityData->getId());
				$res = $adb->pquery($sql,$params);
				if ($res && $adb->num_rows($res)== 1){
					$row = $adb->fetchByAssoc($res,-1,false);
					$this->start_sla = strtotime($row['createdtime']);
				}
			}
			$this->debug_print("start_sla = ".date('Y-m-d H:i:s',$this->start_sla));
			$this->save($mode,'start_sla',$this->formatdate($this->start_sla),$fields_update);
		}
	}
	function save($mode,$field,$value,&$fields_update){
		switch($mode){
			case 'beforesave':
				$this->entityData->set($field,$value);
				$fields_update[$field] = $value;
				if ($field == 'time_refresh') $this->entityData->updatedTimeRefresh = true; // crmv@193588
				break;
			case 'aftersave':
				$fields_update[$field] = $value;
				break;	
		}
	}
	function reset_all_fields($savepoint,&$fields_update){
		$this->start_sla = $this->now_time;
		$this->save($savepoint,'start_sla',$this->formatdate($this->now_time),$fields_update);
		$this->end_sla = false;
		$this->save($savepoint,'end_sla',NULL,$fields_update);
		$this->time_elapsed = 0;
		$this->save($savepoint,'time_elapsed',$this->time_elapsed,$fields_update);
		$this->time_refresh = $this->now_time;
		$this->save($savepoint,'due_date',NULL,$fields_update);
		$this->save($savepoint,'due_time',NULL,$fields_update);
		$this->time_change_status = false;
		$this->save($savepoint,'time_change_status',$this->formatdate($this->now_time),$fields_update);
		$this->time_elapsed_change_status = 0;
		$this->save($savepoint,'time_elapsed_change_status',0,$fields_update);	
		$this->time_elapsed_idle = 0;
		$this->save($savepoint,'time_elapsed_idle',0,$fields_update);
		$this->save($savepoint,'time_elapsed_out_sla',0,$fields_update);
		$this->save($savepoint,'ended_sla','0',$fields_update);
	}
	function process($mode,$savepoint){
		if (($this->data['ended_sla'] == 1 ||  $this->data['ended_sla'] == 'on') && !($this->data['reset_sla'] == 1 || $this->data['reset_sla'] == 'on')) {
			return;
		}
		$this->debug_print("[ticketid ".$this->entityData->getId()."] sono con $mode e $savepoint");
		global $adb,$table_prefix;
		$calculate = false;
		$force_calc_end_sla = false;
		$count_change_status = false;
		$count_idle = false;
		$count_idle_change_status = false;
		$end_sla = false;
		$changed_status = false;
		$reset_sla = false;
		$fields_update = Array();
		switch($mode){
			case 'create':
				$this->time_refresh = $this->now_time;
				//Il now_time � sempre tipo timestamp
				$this->start_sla = $this->now_time;
				$this->save($savepoint,'start_sla',$this->formatdate($this->start_sla),$fields_update);
				$this->save($savepoint,'time_change_status',$this->formatdate($this->now_time),$fields_update);
				$calculate = true;
				if (in_array($this->entityData->newStatus_SLA,$this->config['status_close_value']) && ($this->close_time || $this->config['auto_set_closing_datetime'])){
					// se lo stato � chiuso e ho settato data e ora chiusura devo calcolare lo sla fino all'ora indicata						
					$end_sla = true;	
				}
				if (in_array($this->entityData->oldStatus_SLA,$this->config['status_idle_value'])){
					// se lo stato precedente non era passibile di conteggio devo conteggiare il tempo di idle
					$count_idle = true;
					$force_calc_end_sla = true;
				}					
			break;
			case 'edit':
				if ($this->data['reset_sla'] == 1 || $this->data['reset_sla'] == 'on'){
					$reset_sla = true;
					$calculate = true;
				}
				$this->debug_print("oldstatusSLA -> ".$this->entityData->oldStatus_SLA);
				$this->debug_print("newStatus -> ".$this->entityData->newStatus_SLA);
				$calculate = true;
				if ($this->entityData->oldStatus_SLA != $this->entityData->newStatus_SLA){
					$this->data[$this->config['status_field']] = $this->entityData->newStatus_SLA;
					$changed_status = true;
					//se lo stato � cambiato
					if (in_array($this->entityData->oldStatus_SLA,$this->config['status_idle_value'])){
						// se lo stato precedente non era passibile di conteggio devo conteggiare il tempo di idle
						$count_idle_change_status = true;
						$force_calc_end_sla = true;
					}
					if (in_array($this->entityData->newStatus_SLA,$this->config['status_close_value']) && ($this->close_time || $this->config['auto_set_closing_datetime'])){
						// se lo stato � chiuso e ho settato data e ora chiusura devo calcolare lo sla fino all'ora indicata							
						$end_sla = true;
					}
				}
				else{
					if (in_array($this->entityData->oldStatus_SLA,$this->config['status_idle_value'])){
						// se lo stato precedente non era passibile di conteggio devo conteggiare il tempo di idle
						$count_idle = true;
						$force_calc_end_sla = true;
					}
					if (in_array($this->entityData->newStatus_SLA,$this->config['status_close_value']) && ($this->close_time || $this->config['auto_set_closing_datetime'])){
						// se lo stato � chiuso e ho settato data e ora chiusura devo calcolare lo sla fino all'ora indicata
						//imposto la procedura di fine sla
						$end_sla = true;
					}
				}
				if ($this->entityData->oldSLA != $this->entityData->newSLA){
					$this->data['sla_time'] = $this->entityData->newSLA;
					$this->sla_time = $this->convert_to_seconds($this->entityData->newSLA);
					$this->debug_print("forzo il calcolo con  ".$this->entityData->newSLA);
					//se lo SLA � cambiato
					$force_calc_end_sla = true;
				}
			break;
		}
		//crmv@54290
		if ($this->sla_time == 0 || $this->sla_time == ''){
			$calculate = false;
		}
		//crmv@54290 e
		if ($calculate){
			if ((($changed_status || $reset_sla) && $savepoint == 'aftersave') || $savepoint == 'beforesave'){
				$this->debug_print("calcolo lo sla con mode $savepoint");
				$this->debug_print("changed_status ".$changed_status);
				$this->debug_print("reset_sla ".$reset_sla);
				$this->debug_print("count_idle_change_status ".$count_idle_change_status);
				//se devo resettare lo sla..
				if ($reset_sla){
					$this->debug_print("resetto lo SLA!");
					$count_idle_change_status = false;
					$changed_status = false;
					$this->reset_all_fields($savepoint,$fields_update);
					$this->debug_print(print_r($fields_update,true));
					$this->save($savepoint,'reset_sla','0',$fields_update);
				}
				//conteggi
				elseif ($count_idle_change_status){ // devo fare un conteggio fino alla data attuale..
					$this->reset(true);
					$this->debug_print("conteggio fino a data attuale di idle!");
					$this->get_time_refresh($savepoint,$fields_update);
					$this->debug_print("tempo di refresh ".date('Y-m-d H:i:s',$this->time_refresh));
					$this->get_start_sla($savepoint,$fields_update);						
					if ($this->sla_time>0){
						$this->debug_print("calcolo time_elapsed_idle...");
						$this->time_elapsed_idle+=$this->set_sec_elapsed();
						$this->debug_print("time_elapsed_idle = ".$this->time_elapsed_idle);
						$this->save($savepoint,'time_elapsed_idle',$this->time_elapsed_idle,$fields_update);
					}
					$this->time_refresh = $this->now_time;
					$this->save($savepoint,'time_refresh',$this->formatdate($this->now_time),$fields_update);
					$this->debug_print("calcolo time_remaining...");
					if (($this->sla_time-$this->time_elapsed)<0){
						$this->time_remaining = 0;
						$this->time_elapsed_out_sla = abs($this->sla_time-$this->time_elapsed);
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);		
					}
					else{
						$this->time_remaining=$this->sla_time-$this->time_elapsed;
						$this->time_elapsed_out_sla = 0;
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);
					}
					$this->debug_print("time_remaining = ".$this->time_remaining);
					$this->save($savepoint,'time_remaining',$this->time_remaining,$fields_update);
					$this->save($savepoint,'time_elapsed_change_status',$this->time_elapsed,$fields_update);
					$this->save($savepoint,'time_change_status',$this->formatdate($this->now_time),$fields_update);					
				}
				elseif ($changed_status){
					$this->reset(true);
					//se lo sla � cambiato me lo resetta...patch
					if ($this->entityData->oldSLA != $this->entityData->newSLA){
						$this->data['sla_time'] = $this->entityData->newSLA;
						$this->sla_time = $this->convert_to_seconds($this->entityData->newSLA);
					}
					//end
					$this->debug_print("conteggio cambio di stato");
					$this->get_time_refresh($savepoint,$fields_update);
					$this->get_start_sla($savepoint,$fields_update);						
					if ($this->sla_time>0){
						$this->debug_print("calcolo time_elapsed...");
						$this->time_elapsed+=$this->set_sec_elapsed();
						$this->debug_print("time_elapsed = ".$this->time_elapsed_idle);
						$this->save($savepoint,'time_elapsed',$this->time_elapsed,$fields_update);
					}
					$this->time_refresh = $this->now_time;
					$this->save($savepoint,'time_refresh',$this->formatdate($this->now_time),$fields_update);
					$this->debug_print("calcolo time_remaining...");
					if (($this->sla_time-$this->time_elapsed)<0){
						$this->time_remaining = 0;
						$this->time_elapsed_out_sla = abs($this->sla_time-$this->time_elapsed);
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);		
					}
					else{
						$this->time_remaining=$this->sla_time-$this->time_elapsed;
						$this->time_elapsed_out_sla = 0;
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);
					}
					$this->debug_print("time_remaining = ".$this->time_remaining);
					$this->save($savepoint,'time_remaining',$this->time_remaining,$fields_update);
					$this->save($savepoint,'time_elapsed_change_status',$this->time_elapsed,$fields_update);
					$this->save($savepoint,'time_change_status',$this->formatdate($this->now_time),$fields_update);					
				}
				else{
					$this->debug_print("conteggio normale...");
					if ($count_idle){
						$this->time_elapsed_idle+=$this->set_sec_elapsed();
						$this->debug_print("time_elapsed_idle = ".$this->time_elapsed_idle);
						$this->save($savepoint,'time_elapsed_idle',$this->time_elapsed_idle,$fields_update);							
					}	
					else{
						$this->time_elapsed+=$this->set_sec_elapsed();
						$this->debug_print("time_elapsed = ".$this->time_elapsed);
						$this->save($savepoint,'time_elapsed',$this->time_elapsed,$fields_update);						
					}
					$this->debug_print("calcolo time_remaining...");
					if (($this->sla_time-$this->time_elapsed)<0){
						$this->time_remaining = 0;
						$this->time_elapsed_out_sla = abs($this->sla_time-$this->time_elapsed);
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);		
					}
					else{
						$this->time_remaining=$this->sla_time-$this->time_elapsed;
						$this->time_elapsed_out_sla = 0;
						$this->save($savepoint,'time_elapsed_out_sla',$this->time_elapsed_out_sla,$fields_update);
					}
					$this->debug_print("time_remaining = ".$this->time_remaining);
					$this->save($savepoint,'time_remaining',$this->time_remaining,$fields_update);						
				}
				if (!$this->end_sla || $force_calc_end_sla){
					$this->debug_print("calcolo end_sla...");
					$this->debug_print("start_sla = ".date('Y-m-d H:i:s',$this->start_sla));
					$this->end_sla = $this->set_fine_sla_date();
					$this->debug_print("end_sla = ".date('Y-m-d H:i:s',$this->end_sla));
					$this->save($savepoint,'end_sla',$this->formatdate($this->end_sla),$fields_update);	
				}
				//aggiorno la data di aggiornamento!
				if ($end_sla){
					if ($this->config['auto_set_closing_datetime']){
						$this->save($savepoint,'due_date',$this->formatdate($this->now_time,'date',$savepoint),$fields_update);
						$this->save($savepoint,'due_time',$this->formatdate($this->now_time,'time'),$fields_update);
					}
					$this->save($savepoint,'ended_sla',1,$fields_update);	
				}
				$this->time_refresh = $this->now_time; // crmv@193588
				$this->debug_print("aggiorno time_refresh = ".date('Y-m-d H:i:s',$this->time_refresh));
				$this->save($savepoint,'time_refresh',$this->formatdate($this->now_time),$fields_update);
				
				if ($savepoint == 'aftersave'){
					if (count($fields_update)>0){
						$params = Array(getTabId($this->entityData->getModuleName()),array_keys($fields_update));
						$query = "select tablename,columnname,fieldname from ".$table_prefix."_field where tabid = ? and fieldname in (".generateQuestionMarks(array_keys($fields_update)).")";
//						echo "<br>".$adb->convert2Sql($query,$adb->flatten_array($params));
						$res = $adb->pquery($query,$params);
						if ($res && $adb->num_rows($res)>0){
							while ($row = $adb->fetchByAssoc($res,-1,false)){
								$fields_update_def[$row['tablename']][$row['columnname']] = $fields_update[$row['fieldname']];
							}
							foreach ($fields_update_def as $table=>$arr){
								array_walk($arr,array($this,'sanitize_array_sql'));
								$wherefield = $this->entityData->focus->tab_name_index[$table];
								$sql = "update $table set";
								$first = true;
								foreach ($arr as $column=>$value){
									if (!$first)
										$sql .=",";
									$sql .=" $column = $value";
									$first = false;	
								}
								$sql.=" where $wherefield = ?";
								$params = Array($this->entityData->getId());
//								echo "<br>".$adb->convert2Sql($sql,$adb->flatten_array($params));
								$entity_res = $adb->pquery($sql,$params);
							}							
						}
					}						
				}
			}
		}
		// crmv@193588
		if ($savepoint == 'aftersave' && $this->entityData->hiddenTimeRefresh && $this->entityData->updatedTimeRefresh) {
			$this->debug_print("aggiorno time_refresh in aftersave = ".$this->entityData->focus->column_fields['time_refresh']);
			$adb->pquery("update {$table_prefix}_troubletickets set time_refresh = ? where ticketid = ?", array($this->entityData->focus->column_fields['time_refresh'],$this->entityData->getId()));
		}
		// crmv@193588e
	}
	function get_hours(){
			//imposto la finestra temporale globale per ogni giorno
		foreach ($this->hours as $day => $arr){
			$count_window = 0;
			foreach ($arr as $arr2) {
				$count_obj= 0;
				foreach ($arr2 as $value){
					$time_arr = explode(":",$value);
					$hours[] = $time_arr[0];
					$mins[] = $time_arr[1];
					$seconds_from_midnight[] = ($time_arr[0]*60*60)+($time_arr[1]*60); //crmv@46872
					if ($count_obj == 0)
						$label="start";
					else 
						$label = "end";
					$this->window[$day][$count_window]["hour_".$label] = $time_arr[0];
					$this->window[$day][$count_window]["min_".$label] = $time_arr[1];
					$count_obj++;
				}
				$time_start = mktime ($this->window[$day][$count_window]['hour_start'],$this->window[$day][$count_window]['min_start'],0,1,1,2000);
				$time_end = mktime ($this->window[$day][$count_window]['hour_end'],$this->window[$day][$count_window]['min_end'],0,1,1,2000);
				$this->duration_window[$day][$count_window] = $time_end - $time_start;
				$count_window++;
			}
			$this->count_window[$day]=$count_window;
			//crmv@46872
			$min_start = min($seconds_from_midnight);
			$max_end = max($seconds_from_midnight);
			$this->hour_w_start[$day] = (int)gmdate("H",$min_start);
			$this->hour_w_end[$day] = (int)gmdate("H",$max_end);
			$this->min_w_start[$day] = (int)gmdate("i",$min_start);
			$this->min_w_end[$day] = (int)gmdate("i",$max_end);
			//crmv@46872 e			
		}
	}
	function get_special_hours(){
		//imposto la finestra temporale globale per ogni giorno
		foreach ($this->force_days as $day => $arr){
			$count_window = 0;
			foreach ($arr as $arr2) {
				$count_obj= 0;
				foreach ($arr2 as $value){
					$time_arr = explode(":",$value);
					$hours[] = $time_arr[0];
					$mins[] = $time_arr[1];
					$seconds_from_midnight[] = ($time_arr[0]*60*60)+($time_arr[1]*60); //crmv@46872
					if ($count_obj == 0)
						$label="start";
					else 
						$label = "end";
					$this->window[$day][$count_window]["hour_".$label] = $time_arr[0];
					$this->window[$day][$count_window]["min_".$label] = $time_arr[1];
					$count_obj++;
				}
				$time_start = mktime ($this->window[$day][$count_window]['hour_start'],$this->window[$day][$count_window]['min_start'],0,1,1,2000);
				$time_end = mktime ($this->window[$day][$count_window]['hour_end'],$this->window[$day][$count_window]['min_end'],0,1,1,2000);
				$this->duration_window[$day][$count_window] = $time_end - $time_start;
				$count_window++;
			}
			$this->count_window[$day]=$count_window;
			//crmv@46872
			$min_start = min($seconds_from_midnight);
			$max_end = max($seconds_from_midnight);
			$this->hour_w_start[$day] = (int)gmdate("H",$min_start);
			$this->hour_w_end[$day] = (int)gmdate("H",$max_end);
			$this->min_w_start[$day] = (int)gmdate("i",$min_start);
			$this->min_w_end[$day] = (int)gmdate("i",$max_end);
			//crmv@46872 e
		}
	}
	function set_special_hours($day,$date){
		//sostituisco la finestra temporale a quella speciale
		unset($this->window[$day]);
		$count_window = 0;
		foreach ($this->force_days[$date] as $arr) {
			$count_obj= 0;
			foreach ($arr as $value) {
				$time_arr = explode(":",$value);
				$hours[] = $time_arr[0];
				$mins[] = $time_arr[1];
				$seconds_from_midnight[] = ($time_arr[0]*60*60)+($time_arr[1]*60); //crmv@46872				
				if ($count_obj == 0)
					$label="start";
				else 
					$label = "end";
				$this->window[$day][$count_window]["hour_".$label] = $time_arr[0];
				$this->window[$day][$count_window]["min_".$label] = $time_arr[1];
				$count_obj++;
			}
			$time_start = mktime ($this->window[$day][$count_window]['hour_start'],$this->window[$day][$count_window]['min_start'],0,1,1,2000);
			$time_end = mktime ($this->window[$day][$count_window]['hour_end'],$this->window[$day][$count_window]['min_end'],0,1,1,2000);
			$this->duration_window[$day][$count_window] = $time_end - $time_start;
			$count_window++;
		}
		$this->count_window[$day]=$count_window;
		//crmv@46872
		$min_start = min($seconds_from_midnight);
		$max_end = max($seconds_from_midnight);
		$this->hour_w_start[$day] = (int)gmdate("H",$min_start);
		$this->hour_w_end[$day] = (int)gmdate("H",$max_end);
		$this->min_w_start[$day] = (int)gmdate("i",$min_start);
		$this->min_w_end[$day] = (int)gmdate("i",$max_end);
		//crmv@46872 e
	}
	//functions
	function is_in_a_window($hour,$min,$sec,$month,$day,$year,$dayofweek){
		$time = mktime($hour,$min,$sec,$month,$day,$year);
//		if ($this->count_window[$dayofweek] < 2 ) return false; //torno false, poich� faccio gi� i controlli sui tempi massimo/minimo, quindi salto
		for ($i=0;$i < $this->count_window[$dayofweek];$i++){
			$time_start = mktime($this->window[$dayofweek][$i]['hour_start'],$this->window[$dayofweek][$i]['min_start'],0,$month,$day,$year);
			$time_end = mktime($this->window[$dayofweek][$i]['hour_end'],$this->window[$dayofweek][$i]['min_end'],0,$month,$day,$year);
			if ($time_start <=  $time  && $time <= $time_end){
				//il tempo sta dentro a questa finestra, per cui torno il numero della finestra
				return Array('result'=>$i);
			}
		}
		return false;
	}
	//crmv@46872
	function find_left_window($hour,$min,$sec,$month,$day,$year,$dayofweek){
		$time = mktime($hour,$min,$sec,$month,$day,$year);
		for ($i=$this->count_window[$dayofweek]-1;$i>0;$i--){
			$time_start = mktime($this->window[$dayofweek][$i]['hour_start'],$this->window[$dayofweek][$i]['min_start'],0,$month,$day,$year);
			$time_end = mktime($this->window[$dayofweek][$i]['hour_end'],$this->window[$dayofweek][$i]['min_end'],0,$month,$day,$year);
			if ($time < $time_start){
				continue;
			}
			elseif ($time <= $time_end){
				return Array('result'=>$i);
			}
		}
		return false;	
	}
	function find_right_window($hour,$min,$sec,$month,$day,$year,$dayofweek){
		$time = mktime($hour,$min,$sec,$month,$day,$year);
		for ($i=0;$i<$this->count_window[$dayofweek];$i++){
			$time_start = mktime($this->window[$dayofweek][$i]['hour_start'],$this->window[$dayofweek][$i]['min_start'],0,$month,$day,$year);
			$time_end = mktime($this->window[$dayofweek][$i]['hour_end'],$this->window[$dayofweek][$i]['min_end'],0,$month,$day,$year);
			if ($time > $time_end){
				continue;
			}
			elseif ($time <= $time_start){
				return Array('result'=>$i);
			}
		}
		return false;	
	}
	//crmv@46872 e
	function set_sec_elapsed(){
		$diff=0;
		//resetto le finestre temporali
		$this->get_hours();
		//setto le ore e i minuti giorno mese anno del giorno di partenza SLA
		list($year,$month,$day,$hour,$min,$sec)=Array(date("Y",$this->time_refresh),date("m",$this->time_refresh),date("d",$this->time_refresh),date("H",$this->time_refresh),date("i",$this->time_refresh),date("s",$this->time_refresh));
		//setto anno mese giorno ore minuti della data attuale (arrivo)
		list($year1,$month1,$day1,$hour1,$min1,$sec1)=Array(date("Y",$this->now_time),date("m",$this->now_time),date("d",$this->now_time),date("H",$this->now_time),date("i",$this->now_time),date("s",$this->now_time));
		
		$time_start = $this->start_sla; //data di partenza conteggio
		$time_now = $this->now_time; //data di fine conteggio
		$time_now_backup = $this->now_time; //data di fine conteggio
		$time_start_backup = $this->start_sla; //data di fine conteggio
		$sec_elapsed = 0;
		$exit_while =false;
		//loop di conteggio finch� le ore rimanenti sono finite
		$cnt = 0;
//		$this->debug_print("tempo start = ".date("Y-m-d H:i:s",$time_start));
//		$this->debug_print("tempo now = ".date("Y-m-d H:i:s",$time_now));
//		$this->debug_print("now time = ".date("Y-m-d H:i:s", $this->now_time));
//		$this->debug_print("refresh time = ".date("Y-m-d H:i:s", $this->time_refresh));
		$cc = 0;
		while ($time_start <= $time_now){
			$cc++;
			$this->debug_print("ciclo $cc e seconds elapsed ".$sec_elapsed);
				//ricarico le finestre temporali se necessario
				if ($this->reload_hours) $this->get_hours();
				//tempo e giorno della settimana di partenza SLA 
				$time_start = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
				$dayofweek=date("w",$time_start);
				//tempo e giorno della settimana della data attuale (arrivo)
				$time_now = mktime($hour1,$min1,$sec1,$month1,$day1,$year1); //ora minuto secondo mese giorno anno
				$this->debug_print("time_start ".date('Y-m-d H:i:s',$time_start));
				$this->debug_print("time_now ".date('Y-m-d H:i:s',$time_now));
				$dayofweek1=date("w",$time_now);
				//----------------------------------- UNIFORMO L'ORARIO DI FINE CONTEGGIO ----------------------------------------------------
				//se sono in un giorno speciale devo utilizzare la finestra temporale diversa da quella standard
				if (in_array(date("d-m",$time_now),$this->force_days_keys)){
					$this->set_special_hours(date("w",$time_now),date("d-m",$time_now));
					$this->reload_hours = true;
				}
				
				//se la data di fine conteggio � vacanza allora riporto indietro di un giorno e la la uniformo al limite massimo giornaliero
				if ((in_array($dayofweek1,$this->jump_days) || in_array(date("d-m",$time_now),$this->holidays)) && !in_array(date("d-m",$time_now),$this->force_days_keys)) {
					$day1--;
					$hour1 = 23;
					$min1 = 59;
					$sec1 = 59;
					//ricalcolo la data attuale (che verr� controllata nel while principale)
					$time_now = mktime($hour1,$min1,$sec1,$month1,$day1,$year1); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print("sposto time now a ".date("d-m-Y H:i",$time_now)." causa vacanza");		
					continue;
				}
				//se la data di fine conteggio � > del limite massimo giornaliero, la uniformo al limite massimo giornaliero
				if ($hour1 > $this->hour_w_end[$dayofweek1] || ($hour1 == $this->hour_w_end[$dayofweek1] && $min1 > $this->min_w_end[$dayofweek1])) {
					$hour1 = $this->hour_w_end[$dayofweek1];
					$min1 = $this->min_w_end[$dayofweek1];
					$sec1 = 0;
					//ricalcolo la data attuale (che verr� controllata nel while principale)
					$time_now = mktime($hour1,$min1,$sec1,$month1,$day1,$year1); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print("sposto time now a ".date("d-m-Y H:i",$time_now)." causa > limite massimo giornaliero");
					continue;
				}				
				
				
				//se la data di fine conteggio � minore del limite minimo giornaliero,la la uniformo al limite massimo del giorno prima
				if ($hour1 < $this->hour_w_start[$dayofweek1] || ($hour1 == $this->hour_w_start[$dayofweek1] && $min1 <= $this->min_w_start[$dayofweek1])){
					$day1--;
					$hour1 = 23;
					$min1 = 59;
					$sec1 = 59;
					//ricalcolo la data attuale (che verr� controllata nel while principale)
					$time_now = mktime($hour1,$min1,$sec1,$month1,$day1,$year1); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print("sposto time now a ".date("d-m-Y H:i",$time_now)." causa < limite minimo giornaliero");
					continue;			
				}
				//se la data di fine conteggio � al di fuori delle finestre temporali, la uniformo al limite massimo/minimo della finestra temporale precendente / successiva
				$window=$this->is_in_a_window($hour1,$min1,$sec1,$month1,$day1,$year1,$dayofweek1);
				if (!$window){ //non mi trovo in nessuna finestra temporale, mi devo spostare alla fine della finestra precendente
					$left_window = $this->find_left_window($hour1,$min1,0,$month1,$day1,$year1,$dayofweek1);
					if ($left_window) { //se ho trovato la finestra precendente, porto la data nella posizione di fine finestra
						$left_window = $left_window['result'];
						$this->debug_print("ho il tempo ".$year1."-".$month1."-".$day1." ".$hour1.":".$min1." utilizo finestra sx ".print_r($this->window[$dayofweek1][$left_window],true));
						$hour1 = $this->window[$dayofweek1][$left_window]['hour_end'];
						$min1 = $this->window[$dayofweek1][$left_window]['min_end'];
						$sec1 = 0;
						//ricalcolo la data attuale (che verr� controllata nel while principale)
						$time_now = mktime($hour1,$min1,$sec1,$month1,$day1,$year1); //ora minuto secondo mese giorno anno	
						//ricomincio il ciclo	
						continue;										
					}
					
				}
				//----------------------------------- FINE ----------------------------------------------------
//				$this->debug_print("1uniformo data fine da ".date("d-m-Y H:i",$time_now_backup)." a ".date("d-m-Y H:i",$time_now));		
//				$this->debug_print("1uniformo data attuale da ".date("d-m-Y H:i",$time_start_backup)." a ".date("d-m-Y H:i",$time_start));		
				//----------------------------------- UNIFORMO L'ORARIO DI INIZIO CONTEGGIO ----------------------------------------------------
				if ($time_start > $time_now_backup){
					$this->debug_print("tempo start > time_now!!!!! esco!");
					break;
				}
				//se sono in un giorno speciale devo utilizzare la finestra temporale diversa da quella standard
				if (in_array(date("d-m",$time_start),$this->force_days_keys)){
					$this->set_special_hours(date("w",$time_start),date("d-m",$time_start));
					$this->reload_hours = true;
				}				
				
				//se la data di inizio conteggio � > del limite massimo giornaliero, la sposto al minimo giornaliero del giorno dopo
				if ($hour > $this->hour_w_end[$dayofweek] || ($hour == $this->hour_w_end[$dayofweek] && $min > $this->min_w_end[$dayofweek])) {
					$day++;
					$hour = 0;
					$min = 0;
					$sec = 0;
					//ricalcolo la data di partenza (che verr� controllata nel while principale)
					$time_start = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print("sposto time start a ".date("d-m-Y H:i",$time_start)." causa > limite massimo giornaliero");
					continue;
				}
				//se la data di inizio conteggio � vacanza allora vado avanti di un giorno e la la uniformo al limite minimo giornaliero
					if ((in_array($dayofweek,$this->jump_days) || in_array(date("d-m",$time_start),$this->holidays)) && !in_array(date("d-m",$time_start),$this->force_days_keys)) {
					$day++;
					$hour = 0;
					$min = 0;
					$sec = 0;
					//ricalcolo la data attuale (che verr� controllata nel while principale)
					$time_start = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print($month . "-" . $day . "-" . $year);					
					$this->debug_print("sposto time start a ".date("d-m-Y H:i",$time_start)." causa vacanza");					
					continue;
				}
				//se la data di inizio conteggio � minore del limite minimo giornaliero,la la uniformo al limite minimo dello stesso giorno
				if ($hour < $this->hour_w_start[$dayofweek] || ($hour == $this->hour_w_start[$dayofweek] && $min < $this->min_w_start[$dayofweek])){
					$hour = $this->hour_w_start[$dayofweek];
					$min = $this->min_w_start[$dayofweek];
					$sec = 0;
					//ricalcolo la data attuale (che verr� controllata nel while principale)
					$time_now = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
					//ricomincio il ciclo
					$this->debug_print("sposto time start a ".date("d-m-Y H:i",$time_start)." causa < limite minimo giornaliero");
					continue;			
				}
				//se la data di inizio conteggio � al di fuori delle finestre temporali, la uniformo al limite massimo/minimo della finestra temporale precendente / successiva
				$window=$this->is_in_a_window($hour,$min,$sec,$month,$day,$year,$dayofweek);
				if (!$window){ //non mi trovo in nessuna finestra temporale, mi devo spostare alla fine della finestra precendente
					$right_window = $this->find_right_window($hour,$min,0,$month,$day,$year,$dayofweek);
					if ($right_window) { //se ho trovato la finestra successiva, porto la data nella posizione di inizio finestra
						$right_window = $right_window['result'];
						$this->debug_print("ho il tempo ".$year."-".$month."-".$day." ".$hour.":".$min." utilizo finestra dx ".print_r($this->window[$dayofweek][$right_window],true));
						$hour = $this->window[$dayofweek][$right_window]['hour_start'];
						$min = $this->window[$dayofweek][$right_window]['min_start'];
						$sec = 0;
						//ricalcolo la data attuale (che verr� controllata nel while principale)
						$time_now = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
						//ricomincio il ciclo
						continue;						
					}
					
				}
//				$this->debug_print("2uniformo data fine da ".date("d-m-Y H:i",$time_now_backup)." a ".date("d-m-Y H:i",$time_now));	
//				$this->debug_print("2uniformo data attuale da ".date("d-m-Y H:i",$time_start_backup)." a ".date("d-m-Y H:i",$time_start));	
				if ($time_start > $time_now_backup){
					$this->debug_print("tempo start > time_now!!!!! esco!");
					break;
				}
				//----------------------------------- FINE ----------------------------------------------------		
				//se sono qui posso calcolare la differenza in secondi tra la data attuale e la fine della finestra temporale
				$window=$this->is_in_a_window($hour,$min,$sec,$month,$day,$year,$dayofweek);
				if ($window){ //se sto in una finestra temporale, $window sar� uguale alla chiave dell'array di configurazione
					$window = $window['result'];
					$this->debug_print("trovata finestra ".print_r($this->window[$dayofweek][$window],true));
					//crmv@46872
					for ($i=$window;$i<$this->count_window[$dayofweek];$i++){
						if ($i != $window){
							$time_start = mktime($this->window[$dayofweek][$i]['hour_start'],$this->window[$dayofweek][$i]['min_start'],0,$month,$day,$year);
						}
						$day_end = mktime($this->window[$dayofweek][$i]['hour_end'],$this->window[$dayofweek][$i]['min_end'],0,$month,$day,$year);
						$this->debug_print("fine finestra ".date("d-m-Y H:i",$day_end)." e time now ".date("d-m-Y H:i",$time_now)." e time start ".date("d-m-Y H:i",$time_start));
						if ($day_end >= $time_now){
							if ($time_now >= $time_start){
								$this->debug_print("aggiungo ".($time_now-$time_start)." secondi ed esco dal ciclo!");
								$sec_elapsed += $time_now-$time_start;
							}
							//esco dal ciclo
							$exit_while=true;
							break;
						}
						$this->debug_print("aggiungo ".($day_end-$time_start)." secondi");
						$sec_elapsed += $day_end-$time_start;
					}
					//crmv@46872 e
				}
				if ($exit_while) break;	
				//resetto l'orario di partenza e aumento di 1 giorno
				$hour=0;
				$min=0;
				$sec=0;
				$day++;
				//ricalcolo la data attuale (che verr� controllata nel while principale)
				$time_now = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
			}
			if ($sec_elapsed > 0){ //se sono passati dei secondi, incremento il tempo trascorso
//				$entity->set('time_elapsed', $this->time_elapsed+$sec_elapsed);
				return $sec_elapsed;
			}
			return 0;
	}
	function set_fine_sla_date(){
		//resetto le finestre temporali
		$this->get_hours();
		//setto le ore e i minuti giorno mese anno del giorno di partenza SLA
		list($year,$month,$day,$hour,$min,$sec)=Array(date("Y",$this->start_sla),date("m",$this->start_sla),date("d",$this->start_sla),date("H",$this->start_sla),date("i",$this->start_sla),date("s",$this->start_sla));
		//setto i secondi da aggiungere
		$seconds_to_add=$this->sla_time;
		//aggiungo anche il tempo fuori sla cos� da spostare la fine sla
		if ($this->time_elapsed_idle > 0){
			$seconds_to_add+=$this->time_elapsed_idle;
		}
		$seconds_remaining=$seconds_to_add;
		$exit_while =false;
		$cnt = 0;
		//loop di conteggio finch� le ore rimanenti sono finite
		while ($seconds_remaining > 0){
			//ricarico le finestre temporali se necessario
			if ($this->reload_hours) $this->get_hours();
			//giorno e ora attuale
			$time_now = mktime($hour,$min,$sec,$month,$day,$year); //ora minuto secondo mese giorno anno
			$this->debug_print("ciclo $cnt time_now ".date('Y-m-d H:i:s',$time_now));
			$cnt++;
			//giorno della settimana (da 0 a 6)
			$dayofweek=date("w",$time_now);
			//se sono in un giorno speciale devo utilizzare la finestra temporale diversa da quella standard
			if (in_array(date("d-m",$time_now),$this->force_days_keys)){
				$this->set_special_hours(date("w",$time_now),date("d-m",$time_now));
				$this->reload_hours = true;
			}				
			//se la data attuale � > del limite massimo giornaliero, la sposto al minimo giornaliero del giorno dopo
			if ($hour > $this->hour_w_end[$dayofweek] || ($hour == $this->hour_w_end[$dayofweek] && $min > $this->min_w_end[$dayofweek])) {
				$day++;
				$hour = 0;
				$min = 0;
				$sec = 0;
				//ricomincio il ciclo
				continue;
			}
			//se la data attuale � vacanza allora vado avanti di un giorno e la la uniformo al limite minimo giornaliero
				if ((in_array($dayofweek,$this->jump_days) || in_array(date("d-m",$time_now),$this->holidays)) && !in_array(date("d-m",$time_now),$this->force_days_keys)) { //crmv@46872
				$day++;
				$hour = 0;
				$min = 0;
				//ricomincio il ciclo
				continue;
			}
			//se la data attuale � minore del limite minimo giornaliero,la la uniformo al limite minimo dello stesso giorno
			if ($hour < $this->hour_w_start[$dayofweek] || ($hour == $this->hour_w_start[$dayofweek] && $min < $this->min_w_start[$dayofweek])){		
				$hour = $this->hour_w_start[$dayofweek];
				$min = $this->min_w_start[$dayofweek];
				$sec = 0;
				//ricomincio il ciclo
				continue;			
			}
			//se la data attuale � al di fuori delle finestre temporali, la uniformo al limite massimo/minimo della finestra temporale precendente / successiva
			$window=$this->is_in_a_window($hour,$min,$month,$sec,$day,$year,$dayofweek);
			if (!$window){ //non mi trovo in nessuna finestra temporale, mi devo spostare alla fine della finestra successiva
				$right_window = $this->find_right_window($hour,$min,$sec,$month,$day,$year,$dayofweek);
				if ($right_window) { //se ho trovato la finestra successiva, porto la data nella posizione di inizio finestra
					$right_window = $right_window['result'];
					$this->debug_print("trovato finestra dx ".print_r($this->window[$dayofweek][$right_window],true));
					$hour = $this->window[$dayofweek][$right_window]['hour_start'];
					$min = $this->window[$dayofweek][$right_window]['min_start'];
					$sec = 0;
					//ricomincio il ciclo
					continue;						
				}
			}
			//se sono qui comincio ad aggiungere i secondi alla data attuale
			$window=$this->is_in_a_window($hour,$min,$sec,$month,$day,$year,$dayofweek);
			if ($window){ //se sto in una finestra temporale, $window sar� uguale alla chiave dell'array di configurazione
				$window = $window['result'];
				//crmv@46872
				for ($i=$window;$i<$this->count_window[$dayofweek];$i++){	//crmv@38076
					if ($i != $window){
						$time_now = mktime($this->window[$dayofweek][$i]['hour_start'],$this->window[$dayofweek][$i]['min_start'],0,$month,$day,$year);
					}
					$window_end = mktime($this->window[$dayofweek][$i]['hour_end'],$this->window[$dayofweek][$i]['min_end'],0,$month,$day,$year);
					//differenza di secondi tra l'orario attuale e l'ora di chiusura
					$diff = $window_end - $time_now;
					//se i secondi di distanza sono >= dei secondi rimanenti del conteggio, allora sono arrivato alla fine del conteggio,esco dal ciclo
					if ($diff >= $seconds_remaining ) {
						$time_now += $seconds_remaining;
						$exit_while=true;
						break;
					}
					// altrimenti decremento i secondi rimanenti
					else {
						$seconds_remaining -=$diff;
					}
				}
				//crmv@46872 e
			}	
			if ($exit_while) break;			
			//mi posiziono nel giorno successivo
			$day++;
			$hour=0;
			$min=0;
			$sec=0;
		}
		return ($time_now >= $this->start_sla)?$time_now:$this->start_sla;
	}	
}
?>