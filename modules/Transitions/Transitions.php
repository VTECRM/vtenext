<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class Transitions {
	var $table = 'tbl_s_transitions';
	var $table_fields = 'tbl_s_transitions_fields';
	var $table_initial_fields = 'tbl_s_transitions_init_fields';
	var $history_table = 'tbl_s_transitions_history';
	var $modulename;
	var $status_field;
	var $all_status_field = Array();
	var $roleid;
	var $is_managed = false;
	var $module_is_managed = false;
	
	var $mod_strings;
// 	//crmv@31357
	function __construct()	{
		global $current_language;
		$this->log = LoggerManager::getLogger('Transitions');
	}
	function Initialize($modulename=false,$roleid="",$field=""){
		global $current_language;
		if ($modulename){
			$this->modulename = $modulename;
			$this->roleid = $roleid;
			$this->getStatusField($field);
			$this->mod_strings = return_module_language($current_language,'Transitions');
		}
	}
	//crmv@31357e
	function getStatusField($field = ""){
		if ($this->status_field) return true;
		global $adb;
		$sql = "select field from ".$this->table_fields." where module = ?";
//		echo $adb->convert2Sql($query,$adb->flatten_array(array_values(Array($this->modulename))));die;
		$res = $adb->pquery($sql,Array($this->modulename));
		if ($res){
			$this->all_status_field = Array();
			$cnt = 0;
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				$fieldname = $adb->query_result($res,0,"field");
				if ($fieldname != ""){
					$found = true;
					$this->all_status_field[] = $fieldname;
					if ($fieldname == $field){
						$this->status_field = $fieldname;
						$this->is_managed = true;
					}	
				}	
				$cnt++;	
			}
			if ($cnt > 0) $this->module_is_managed = true;
			if ($field == "" && count($this->all_status_field) >=1){ //crmv@167234
				$this->status_field = $this->all_status_field[0];
				$this->is_managed = true;
			}
			else {
				$this->status_field = $field;
			}	
		}
		if ($found) return true;
		return false;
	}
	function getStTable(&$state_num) {
		global $adb,$table_prefix;
		$this->generateStTable(); 
		if($this->getStatusField()) {
		    $query = "select 
			ruleid,status,next_status,enable from ".$this->table." 
			inner join ".$table_prefix."_field on ".$table_prefix."_field.fieldname = field
			inner join ".$table_prefix."_".$this->status_field." on ".$table_prefix."_".$this->status_field.".".$this->status_field." = status
			inner join ".$table_prefix."_".$this->status_field." fieldtable_next on fieldtable_next.".$this->status_field." = next_status
			inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
			inner join ".$table_prefix."_picklist on ".$table_prefix."_picklist.name = field		
			inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_".$this->status_field.".picklist_valueid and ".$table_prefix."_role2picklist.picklistid = ".$table_prefix."_picklist.picklistid
			inner join ".$table_prefix."_role2picklist ".$table_prefix."_role2picklist2 on ".$table_prefix."_role2picklist2.picklistvalueid = fieldtable_next.picklist_valueid and ".$table_prefix."_role2picklist2.picklistid = ".$table_prefix."_picklist.picklistid
			where ".$this->table.".module = ? and ".$this->table.".roleid = ? and ".$this->table.".field = ? 
			and ".$table_prefix."_def_org_field.visible = 0
			and ".$table_prefix."_role2picklist.roleid = ?
			and ".$table_prefix."_role2picklist2.roleid = ?
			order by ".$table_prefix."_role2picklist.sortid,".$table_prefix."_role2picklist2.sortid asc
			";
		    $params = Array($this->modulename,$this->roleid,$this->status_field,$this->roleid,$this->roleid);
//		    echo $adb->convert2Sql($query,$adb->flatten_array($params));die;
			$result = $adb->pquery($query,$params);
			$ret_val = Array();
			if($result) {
				while($row = $adb->fetchByAssoc($result,-1,false)) {
					$ruleid = $row["ruleid"];
					$status = $row["status"];
					$next_status = $row["next_status"];
					$enabled = $row["enable"];
					$ret_val[$status][$next_status] = Array($ruleid,$enabled);
				}
			}
			$state_num = is_array($ret_val[$status]) ? count($ret_val[$status]) : 0; // crmv@172864
			return $ret_val;
		} else echo "<font color=red>".$this->mod_strings['LBL_NO_DATA']."</font>";
	}
	
	function generateStTable($reset = false) {
		global $adb,$table_prefix;
		if($this->getStatusField()){
			//prendo tutti i valori della picklist
			$pick_query="select distinct $this->status_field 
				from ".$table_prefix."_$this->status_field 
				inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$this->status_field.picklist_valueid 
				where picklistid in (select picklistid from ".$table_prefix."_picklist)";
//			echo $adb->convert2Sql($pick_query,$adb->flatten_array(array_values($params)));die;
			$params = array();
			$pickListResult = $adb->pquery($pick_query, $params);
			$noofpickrows = $adb->num_rows($pickListResult);
			for($j = 0; $j < $noofpickrows; $j++)
			{
				$pickListValue[]=decode_html($adb->query_result($pickListResult,$j,strtolower($this->status_field)));
			}
			$cnt = 0;
			//popolo eventualmente la tabella con dati vuoti..
			foreach ($pickListValue as $val){
				foreach ($pickListValue as $val2){
					$sql = "select count(*) as presence from $this->table where module = ? and status = ? and next_status = ? and roleid = ? and field = ?";
					$params = Array($this->modulename,$val,$val2,$this->roleid,$this->status_field);
					$res = $adb->pquery($sql,$params);
					if ($res){
						if ($adb->query_result($res,0,'presence') == 0){
							$id = $adb->getUniqueID($this->table);
							$params = Array(
								"ruleid" =>$id,
								"module" =>$this->modulename,
								"field" =>$this->status_field,
								"status" =>$val,
								"next_status" =>$val2,
								"enable" =>0,
								"sequence" =>$cnt,
								"roleid" =>$this->roleid
							);
							$sql = "insert into $this->table (".implode(",",array_keys($params)).")
							values (".generateQuestionMarks(array_values($params)).")";
	//						echo $adb->convert2Sql($sql,$adb->flatten_array(array_values($params)));die;
							$adb->pquery($sql,$params);					
							$cnt++;	
						}
						elseif ($reset){
							$sql = "update $this->table set enable = 0 where module = ? and status = ? and next_status = ? and roleid = ? and field = ?";
							$params = Array($this->modulename,$val,$val2,$this->roleid,$this->status_field);
							$res = $adb->pquery($sql,$params);						 
						}	
					}	
				}	
			}	
		} 
		else return false;
		
	}
	function copy($destination_roleid){
		global $adb;
		//resetto la tabella per il ruolo di destinazione
		$rel_obj = CRMEntity::getInstance('Transitions');
		$rel_obj->Initialize($this->modulename,$destination_roleid);
		$rel_obj->generateStTable(true);
		$query = "select 
			$this->table.ruleid,
			$this->table.status,
			$this->table.next_status,
			$this->table.enable
			from $this->table
			where module = ? and roleid = ? and field = ?";
		$params = Array($this->modulename,$this->roleid,$this->status_field);
//		echo $adb->convert2Sql($query,$adb->flatten_array(array_values($params)));die;
		$result = $adb->pquery($query,$params);
		for($i=0;$i<$adb->num_rows($result);$i++) {
			$enabled = $adb->query_result($result,$i,"enable");
			$status  = $adb->query_result($result,$i,"status");
			$next_status  = $adb->query_result($result,$i,"next_status");
			$params = Array($enabled,$status,$next_status,$rel_obj->modulename,$rel_obj->roleid,$rel_obj->status_field);
			$query = " update $this->table set enable = ? where 
			status = ? and next_status = ? and module = ? and roleid = ? and field = ?";
//			echo $adb->convert2Sql($query,$adb->flatten_array(array_values($params)));die;
			$adb->pquery($query,$params);
		}
		$rel_obj->save_initial_status($this->status_field,$this->getInitialState());
	}
	function save_initial_status($field_status,$field_status_value){
		global $adb;
		$sql = "select initial_value from {$this->table_initial_fields} where roleid=? and module=? and field = ?";
		$params = Array($this->roleid,$this->modulename,$field_status);
		$res = $adb->pquery($sql,$params);
		if ($res && $adb->num_rows($res)>0){
			$query = "update {$this->table_initial_fields} set initial_value = ? where roleid = ? and field = ?";
			$params = Array($field_status_value,$this->roleid,$field_status);					
		}
		else {
			$params = Array($field_status,$field_status_value,$this->roleid,$this->modulename);
			$query = "insert into {$this->table_initial_fields} (field,initial_value,roleid,module) values(".generateQuestionMarks($params).")";
		}
		$adb->pquery($query,$params);			
	}
	function getFieldPicklist(){
		global $adb,$table_prefix;
		$tabid = getTabid($this->modulename);
		//crmv@18586 - filtrato anche per uitype = 15
		$query="SELECT
		  ".$table_prefix."_field.fieldname,
		  ".$table_prefix."_field.fieldlabel
		FROM ".$table_prefix."_field
		  INNER JOIN ".$table_prefix."_picklist
		    ON ".$table_prefix."_field.fieldname = ".$table_prefix."_picklist.name
		   INNER JOIN ".$table_prefix."_def_org_field
		   ON  ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
		WHERE (
		       ".$table_prefix."_field.tabid = ?
		       AND ".$table_prefix."_field.uitype IN (?,?)
		       AND ".$table_prefix."_def_org_field.visible = 0
		)
		ORDER BY ".$table_prefix."_picklist.picklistid ASC";
//		echo $adb->convert2Sql($query,$adb->flatten_array(array_values(array($tabid, $tabid))));die;
		$result = $adb->pquery($query, array($tabid,'15','300'));
		//crmv@18586e
		if ($result){
			$picklist[]= '<select id="status_field" name="status_field" onChange="status_field_selection_change();" style="width: 200px;">';
			$picklist[]='<option value="-1" selected>'.getTranslatedString('LBL_NONE').'</option>';
			while ($row = $adb->FetchByAssoc($result,-1,false)){
				if ($row['fieldname'] == $this->status_field)
					{
						$selected = 'selected';
						$found = true;
					}
				else $selected = '';
				if (in_array($row['fieldname'],$this->all_status_field)){
					$style = 'style="background-color:red;"';
				}
				else {
					$style = '';
				}
				$label = getTranslatedString($row['fieldlabel'],$this->modulename);
				$picklist[]='<option '.$style.' value="'.$row['fieldname'].'" '.$selected.'>'.$label.'</option>';
			}
			$picklist[]="</select>";
			if ($found){
				$picklist[1] ='<option value="-1" >'.getTranslatedString('LBL_NONE').'</option>';
			}
			$picklist = implode("",$picklist);
		}
		return $picklist;
	}
	private function updateHandler(){
		global $adb;
		require 'include/events/include.inc';
		$em = new VTEventsManager($adb);
		//unregister event handler
		$em->unregisterHandler('TransitionHandler');
		//get all modules handled
		$sql = "select distinct module from {$this->table_fields}";
		$res = $adb->query($sql);
		if ($res && $adb->num_rows($res)>0){
			$em->registerHandler('vte.entity.beforesave','modules/Transitions/TransitionHandler.php','TransitionHandler');//crmv@207852
			$em->registerHandler('vte.entity.aftersave','modules/Transitions/TransitionHandler.php','TransitionHandler');//crmv@207852
		}
	}
	function saveField($field){
		global $adb,$table_prefix;
		include_once('vtlib/Vtecrm/Utils.php');//crmv@207871
		include_once('vtlib/Vtecrm/Menu.php');//crmv@207871
		include_once('vtlib/Vtecrm/Module.php');//crmv@207871
		$link = new Vtecrm_Link;
		if ($field == '-1'){
			$sql = "delete from $this->table_fields where module = ?";
			$res = $adb->pquery($sql,Array($this->modulename));
			//delete from vte_links
			$link->deleteLink(getTabId($this->modulename),'DETAILVIEWWIDGET','LBL_STATUS_BLOCK');
			$this->updateHandler();
			//remove history relatedlist
			$query = "delete from ".$table_prefix."_relatedlists where tabid = ? and related_tabid = ? and name = ?";
			$params = Array(getTabId($this->modulename),getTabId('Transitions'),'get_transitions_history');
			$adb->pquery($query,$params);
			return true;
		}
		elseif($this->status_field == '') {
			$sql = "insert into $this->table_fields (module,field) values (?,?)";
//			echo $adb->convert2Sql($sql,$adb->flatten_array(array_values(Array($this->modulename,$field))));die;
			$res = $adb->pquery($sql,Array($this->modulename,$field));
			//insert into vte_links
			$link->addLink(getTabId($this->modulename),'DETAILVIEWWIDGET','LBL_STATUS_BLOCK','module=Transitions&action=TransitionsAjax&file=Statusblock&record=$RECORD$','workflow.gif');
			$this->updateHandler();
			//insert history relatedlist
			$instancemodule = Vtecrm_Module::getInstance($this->modulename);
			//crmv@167234
			$params = Array(
			'relation_id'=>$adb->getUniqueId($table_prefix.'_relatedlists'),
			'tabid' => getTabId($this->modulename),
			'related_tabid' => getTabId('Transitions'),
			'name' => 'get_transitions_history',
			'label' => 'Transitions History',
			'sequence' => $instancemodule->__getNextRelatedListSequence(),
			);
			//crmv@167234e
			$query = "insert into  ".$table_prefix."_relatedlists (".implode(",",array_keys($params)).") values (".generateQuestionMarks($params).")";
//			echo $adb->convert2Sql($query,$adb->flatten_array(array_values($params)));
//			return false;die;
			$adb->pquery($query,$params);			
			return true;
		}
		return false;
	}
	function getInitialState(){
		global $adb;
		$sql = "select initial_value from {$this->table_initial_fields} where roleid = ? and module=? and field = ?";
//		echo $adb->convert2Sql($sql,$adb->flatten_array(Array($this->roleid,$this->modulename,$this->status_field)));die;
		$res = $adb->pquery($sql,Array($this->roleid,$this->modulename,$this->status_field));
		if ($res && $adb->num_rows($res)>0){
			return $adb->query_result($res,0,'initial_value');
		}
		return false;
		
	}
	function getFieldStateInfo(){
		global $adb,$table_prefix;
		if ($this->status_field){
			$values = Array($this->status_field=>$this->getInitialState());
			$sql = "select uitype,fieldname,fieldlabel,maximumlength,generatedtype,readonly,typeofdata from ".$table_prefix."_field where fieldname = ? and tabid = ?";
//			echo $adb->convert2Sql($sql,$adb->flatten_array(Array($this->status_field,getTabId($this->modulename))));die;
			$res = $adb->pquery($sql,Array($this->status_field,getTabId($this->modulename)));
			if ($res && $adb->num_rows($res)>0){
				$params[] = $adb->query_result($res,0,'uitype');
				$params[] = $adb->query_result($res,0,'fieldname');
				$params[] = $adb->query_result($res,0,'fieldlabel');
				$params[] = $adb->query_result($res,0,'maximumlength');
				$params[] = $values;
				$params[] = $adb->query_result($res,0,'generatedtype');
				$params[] = $this->module;
				$params[] = 'edit';
				$params[] = $adb->query_result($res,0,'readonly');
				$params[] = $adb->query_result($res,0,'typeofdata');
				return $params;
			}
		}
		return false;
	}
	//crmv@16600
	function getFieldStateColumnname(){
		global $adb,$table_prefix;
		if ($this->status_field){
			$values = Array($this->status_field=>$this->getInitialState());
			$sql = "select columnname from ".$table_prefix."_field where fieldname = ? and tabid = ?";
			$res = $adb->pquery($sql,Array($this->status_field,getTabId($this->modulename)));
			if ($res && $adb->num_rows($res)>0)
				return $adb->query_result($res,0,'columnname');
		}
		return false;
	}
	//crmv@16600e
	public function ismanaged_global(){
		if ($this->status_field){
			return true;
		}
		return false;
	}
	public function ismanaged($fieldname){
		if ($this->status_field == $fieldname){
			return true;
		}
		return false;
	}
	public function handle_managed_fields($fieldname,$fieldcolname,&$readonly,&$col_fields,$mode,$type){
		global $processMakerView; // crmv@177591
		if ($this->ismanaged($fieldname)){
			$readonly = 99;
			if ($type == 'EditView' && $mode == '' && !$processMakerView){ // crmv@177591
				$col_fields[$fieldname] = $this->getInitialState();
			}
		}
	}
	public function insertIntoHistoryTable($old_status,$status,$id,$motivation){
		global $current_user,$adb;
		//crmv@167234
		$params = Array(
		'historyid' => $adb->getUniqueId($this->history_table),
		'tabid' => getTabid($this->modulename),
		'field' => $this->status_field,
		'old_status' => $old_status,
		'new_status' => $status,
		'userid' => $current_user->id,
		'motivation' => utf8_encode($motivation),	//crmv@18642
		'entity_id' => $id,
		'changetime' => date('Y-m-d H:i:s'),
		);
		//crmv@167234e
		$sql = "insert into {$this->history_table} (".implode(",",array_keys($params)).") values (".generateQuestionMarks($params).")";
		$res = $adb->pquery($sql,$params);

	}
	public function get_transitions_history($id){
		global $adb,$current_user;
		$sql = "select old_status,new_status,userid,changetime,motivation from {$this->history_table} where entity_id = ? and tabid = ? and field = ?";
		$params = Array($id,getTabId($this->modulename),$this->status_field);
		$res = $adb->pquery($sql,$params);
		//crmv@167234
		$header[] = getTranslatedString('HEADER_ID','Transitions');
		$header[] = getTranslatedString('HEADER_OLD_STATUS','Transitions');
		$header[] = getTranslatedString('HEADER_NEW_STATUS','Transitions');
		$header[] = getTranslatedString('HEADER_USER','Transitions');
		$header[] = getTranslatedString('HEADER_NOTE','Transitions');
		$header[] = getTranslatedString('HEADER_DATE','Transitions');
		//crmv@167234e
		$status_access = (getFieldVisibilityPermission($this->modulename, $current_user->id, $this->status_field) != '0')? 1 : 0;
		$picklistarray = getAccessPickListValues($this->modulename);
		//crmv@16600
		$status_field_columnname = $this->getFieldStateColumnname();
		$status_array = $picklistarray[$status_field_columnname];
		//crmv@16600e
		$error_msg = getTranslatedString('Not Accessible');		
		$cnt = 1;
		if ($res && $adb->num_rows($res)>0){
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				$entries = Array();
				$entries[] = $cnt;
				//crmv@167234
				$entries[] = (in_array($row['old_status'],$status_array))?getTranslatedString($row['old_status'],$this->modulename):$error_msg;
				$entries[] = (in_array($row['new_status'],$status_array))?getTranslatedString($row['new_status'],$this->modulename):$error_msg;
				$entries[] = getUserName($row['userid']);
				$entries[] = $row['motivation']; 
				$entries[] = getDisplayDate($row['changetime']);
				//crmv@167234e
				$entries_list[] = $entries;
				$cnt++;
			}
		}
		return Array('header'=>$header,'entries'=>$entries_list);
	}

	function get_permitted_states($status, $record = null, $extra_text = false) { // crmv@sdk-27926
		global $current_user,$adb,$table_prefix;
		$query = "select next_status from {$this->table} 
			where status = ?
			and module = ?
			and roleid = ?
			and enable = ?
			and field = ?
		";
		$params = Array(
			$status,
			$this->modulename,
			$current_user->roleid,
			1,
			$this->status_field
		);	
//		echo $adb->convert2Sql($query,$adb->flatten_array($params));die;
		$states = Array($status);
		$result = $adb->pquery($query,$params);
		if($result && $adb->num_rows($result)>0) {
			while($row = $adb->fetchByAssoc($result)) {
				$states[] = $row["next_status"];
			}
		} 
		$sql = "select columnname from ".$table_prefix."_field where tabid = ? and fieldname = ?";
		$params = Array(getTabid($this->modulename),$this->status_field);
		$res = $adb->pquery($sql,$params);
		$ret_val = Array();
		$status_array = getAssignedPicklistValues($this->status_field,$this->roleid,$adb,$this->modulename);
		$ret_val = array_intersect(array_keys($status_array),$states);
		// crmv@sdk-27926
		$sdk_ret_message = '';
		$sdk_ret_values = $ret_val;
		if (vtlib_isModuleActive('SDK')) {
			$sdk_trans = SDK::getTransition($this->modulename, $this->status_field);
			if (!empty($sdk_trans)) {
				if (is_readable($sdk_trans['file'])) {
					include_once($sdk_trans['file']);
					$sdk_ret = $sdk_trans['function']($this->modulename, $this->status_field, $record, $status, $ret_val);
					if (is_array($sdk_ret)) {
						$sdk_ret_values = $sdk_ret['values']; 
						$sdk_ret_message = $sdk_ret['message'];
					}
				}
			}
		}
		if ($extra_text) $ret_val = array('values'=>$sdk_ret_values, 'message'=>$sdk_ret_message);
		// crmv@sdk-27926
		return $ret_val;
	}
	function get_last_change_state($id) {
		global $current_user,$adb;
		$query = "select old_status,new_status,changetime,motivation,userid from {$this->history_table} 
		where entity_id = ? and tabid = ? and field = ? order by changetime desc";
		$params = Array($id,getTabId($this->modulename),$this->status_field);
		//echo $adb->convert2Sql($query,$adb->flatten_array($params));die;
		$result = $adb->pquery($query,$params);
		$row = false;
		if($result && $adb->num_rows($result)>0) {
			$row = $adb->fetchByAssoc($result);
			//crmv@167234
			$row['username'] = getUserName($row['userid']);
			$row['date'] = getDisplayDate($row['changetime']);
			$row['numrows'] = $adb->num_rows($result);
			//crmv@167234
		} 
		return $row;
	}
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
 					
		require_once('include/utils/utils.php');			
		global $adb,$mod_strings,$table_prefix;
 		global $table_prefix;
 		if($eventType == 'module.postinstall') {			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			
			$blockid = getSettingsBlockId('LBL_STUDIO');
			$fieldid = $adb->getUniqueID($table_prefix.'_settings_field');

			// changed, to put it after the workflows
			$sequence = 20;
			$seq_res = $adb->pquery("SELECT sequence FROM {$table_prefix}_settings_field WHERE blockid = ? AND name = ?", array($blockid, 'LBL_LIST_WORKFLOWS'));
			if ($adb->num_rows($seq_res) > 0) {
				$cur_seq = intval($adb->query_result_no_html($seq_res, 0, 'sequence'));
				// shift all the following ones
				$adb->pquery("UPDATE {$table_prefix}_settings_field SET sequence = sequence + 1 WHERE blockid = ? AND sequence > ?", array($blockid, $cur_seq));
				$sequence = $cur_seq+1;
			}
			
			$adb->pquery('INSERT INTO '.$table_prefix.'_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence) 
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'LBL_ST_MANAGER', 'Transitions.gif', 'LBL_ST_MANAGER_DESCRIPTION', 'index.php?module=Transitions&action=index&parenttab=Settings', $sequence));

			vtws_addExtraTypeWebserviceEntity('Transitions','modules/Transitions/WebserviceExtra.php','WebserviceExtraTrans'); //crmv@OPER4380
			
		} else if($eventType == 'module.disabled') {
		// TODO Handle actions when this module is disabled.
		} else if($eventType == 'module.enabled') {
		// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			vtws_addExtraTypeWebserviceEntity('Transitions','modules/Transitions/WebserviceExtra.php','WebserviceExtraTrans'); //crmv@OPER4380
		// TODO Handle actions after this module is updated.
		}
 	}	
}
?>