<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('include/utils/utils.php');
require_once('modules/Picklistmulti/Picklistmulti_utils.php');
class Picklistmulti {
	var $module;
	var $module_name;
	var $module_list;
	var $field;
	var $field_name;
	var $field_list;
	var $languages;
	var $start;
	var $limit;
	var $has_limit;
	var $total;
	var $uitype = Array(1015);
	//carica tutti i campi Picklistmultilinguaggio per tutti i moduli
	function __construct($loadval = false,$module = '',$field = '',$limits=false){
		$this->loadval = $loadval;
		$this->module_name = $module;
		$this->field_name = $field;
		$this->setLang();
		$this->setModules();
		$this->setFields();
		$this->setLimit($limits);
		$this->setFieldValues();
	}
	function setLimit($limits){
		if (is_array($limits)){
			$this->start = $limits[0];
			$this->limit = $limits[1];
			$this->has_limit = true;
		}
		else $this->has_limit = false;
	}
	function setLang(){
		$this->languages=get_active_languages();
		foreach ($this->languages as $key=>$arr){
			$this->columns[$arr['prefix']] = Array('label'=>$arr['label'],'visible'=>1);
		}
	}
	function setModules(){
//		if ($this->module_name !="") return;
		global $adb,$table_prefix;
		// Ignore disabled modules.
		$query = 'select distinct '.$table_prefix.'_field.fieldname,'.$table_prefix.'_field.tabid,'.$table_prefix.'_tab.tablabel, '.$table_prefix.'_tab.name as tabname,uitype from '.$table_prefix.'_field inner join '.$table_prefix.'_tab on '.$table_prefix.'_tab.tabid='.$table_prefix.'_field.tabid where uitype IN ('.generateQuestionMarks($this->uitype).') and '.$table_prefix.'_field.tabid != 29 and '.$table_prefix.'_tab.presence <> 1 and '.$table_prefix.'_field.presence in (0,2) order by '.$table_prefix.'_field.tabid ASC';
		// END
		$result = $adb->pquery($query, array($this->uitype));
		while($row = $adb->fetch_array($result)){
			$modules[$row['tablabel']] = $row['tabname'];
		}
		if (is_array($modules)) $modules_keys = array_keys($modules);	//crmv@22582
		if ($this->module_name == '') $this->module_name = $modules_keys[0];
		$this->module_list = $modules;
	}
	function setFields(){
		if ($this->module_name =="") return;
		global $adb, $log,$table_prefix;
		$user_fld = Array();

		$tabid = getTabid($this->module_name);
		//crmv@18156 crmv@146061
		$query="select ".$table_prefix."_field.fieldlabel,".$table_prefix."_field.columnname,".$table_prefix."_field.fieldname, ".$table_prefix."_field.uitype
				FROM ".$table_prefix."_field left join ".$table_prefix."_picklist on ".$table_prefix."_field.fieldname = ".$table_prefix."_picklist.name
				where ".$table_prefix."_field.tabid=? and ".$table_prefix."_field.uitype in (".generateQuestionMarks($this->uitype).")
				and ".$table_prefix."_field.presence in (0,2) ORDER BY ".$table_prefix."_picklist.picklistid ASC";
		//crmv@18156e crmv@146061e
		$result = $adb->pquery($query, array($tabid,$this->uitype));
//		echo "<br>".$adb->convert2Sql($query,$adb->flatten_array(array($tabid,$this->uitype)));die;
		while ($row=$adb->fetchByAssoc($result)){
			if ($this->field_name == '') $this->field_name = $row['fieldname'];
			if ($this->field_name == $row['fieldname']) {
				$this->field = array();
				$this->field['fieldlabel'] = $row["fieldlabel"];
				$this->field['columnname'] = $row["columnname"];
				$this->field['fieldname'] = $row["fieldname"];
				$this->field['uitype'] = $row["uitype"];
				$this->field['columns'] = $this->getColumns();
			}
			//crmv@18156
			$this->field_list[$row['columnname']] = getTranslatedString($row["fieldlabel"],$this->module_name);
			//crmv@18156 end
		}
		$this->field_label = $this->field['fieldlabel'];
	}
	function getColumns($translate=false){
		$arr[] = 'Code_system';
		$arr[] = 'Code';
		foreach ($this->languages as $key=>$lang)
			if ($translate)
				$arr[] = $lang['label'];
			else
				$arr[] = $lang['prefix'];
		return $arr;
	}
	function getColumnsjson(){
		$i = 0;
		$arr[$i]['name'] = 'code_system';
		$arr[$i]['index'] = 'code_system';
		$arr[$i]['hidden'] = 'true';
		$arr[$i]['sortable'] = 'false';
		$i++;
		$arr[$i]['name'] = 'code';
		$arr[$i]['index'] = 'code';
		$arr[$i]['sortable'] = 'false';
		$arr[$i]['editable'] = 'true';
		$arr[$i]['editrules'] = 'required:true,custom:true,custom_func:checkcode';
		$arr[$i]['formoptions'] = "elmprefix:'(*)'";
		$i++;
		foreach ($this->languages as $key=>$lang){
			$arr[$i]['name'] = $lang['prefix'];
			$arr[$i]['index'] = $lang['prefix'];
			$arr[$i]['sortable'] = 'false';
			$arr[$i]['edittype'] = 'textarea';
			$arr[$i]['editable'] = 'true';
			$arr[$i]['editoptions'] = 'size:"20"'; // crmv@89143
			$i++;
		}
		return $arr;
	}
	function getColumnNames(){
		foreach ($this->getColumns(true) as $column){
			$arr[] = "'".getTranslatedString($column,'Picklistmulti')."'";
		}
		return implode(",",$arr);
	}
	function getPicklistValues(){
		global $adb;
		$var = array();
		//crmv@136987
		$sql = "SELECT code_system,code,field
				FROM tbl_s_picklist_language
				WHERE field = ?
				GROUP BY code_system,code,field";
		$res = $adb->pquery($sql,Array($this->field_name));
		if ($res){
			$this->total = $adb->num_rows($res);
		}
		//crmv@136987e
		if ($this->has_limit){
			$params = Array($this->field);
			$res = $adb->limitpQuery($sql,$this->start,$this->limit,$params);
		}
		if ($res){
			$i=0;
			while($row=$adb->fetchByAssoc($res,-1,false)){
				$arr[$i]['code_system'] = $row['code_system'];
				$arr[$i]['code'] = $row['code'];
				foreach ($this->languages as $key=>$lang){
					$arr[$i][$lang['prefix']] = self::getTranslatedPicklist($row['code'],$this->field,$lang['prefix']);
				}
				$i++;
			}
		}
		return $arr;
	}
	function setFieldValues(){
		if (!$this->loadval) return;
		global $adb;
		//crmv@136987
		$sql = "SELECT code_system,code,field
				FROM tbl_s_picklist_language
				WHERE field = ?
				GROUP BY code_system,code,field";
		$res = $adb->pquery($sql,Array($this->field_name));
		if ($res){
			$this->total = $adb->num_rows($res);
		}
		if ($this->has_limit){
			$params = Array($this->field_name);
			$res = $adb->limitpQuery($sql,$this->start,$this->limit,$params);
		}
		//crmv@136987e
		if ($res){
			$i=0;
			while($row=$adb->fetchByAssoc($res,-1,false)){
				$arr[$i]['code_system'] = $row['code_system'];
				$arr[$i]['code'] = $row['code'];
				foreach ($this->languages as $key=>$lang){
					$arr[$i][$lang['prefix']] = self::getTranslatedPicklist($row['code'],$this->field_name,$lang['prefix']);
				}
				$i++;
			}
		}
		$this->field['value'] = $arr;
	}
	public function getTranslatedPicklist($id = false,$fieldname,$language=false){
		global $adb,$current_language;
		if (!$language) $language = $current_language;
		$sql = "select code,value from tbl_s_picklist_language
				where ";
		if ($id !== false)
			$sql .= " tbl_s_picklist_language.code = ? and ";
		$sql.=" tbl_s_picklist_language.field = ?
				and tbl_s_picklist_language.language = ?";
		if ($id !== false)
			$params = Array($id,$fieldname,$language);
		else {
//			$sql.=" order by value ";
			$params = Array($fieldname,$language);
		}
//		echo "<br>".$adb->convert2Sql($sql,$adb->flatten_array($params));
		$res = $adb->pquery($sql,$params);
		if ($res){
			while ($row = $adb->fetchByAssoc($res,-1,false)){
				$values[trim($row['code'])] = $row['value']; // crmv@36205 - warning, here ending spaces are packed together
				if ($id !== false) return $row['value']; //crmv@32334
			}
		}
		return $values;
	}
	function is_empty(){
		if (!is_array($this->field) || count($this->field) == 0) return 1;
		return 0;
	}
	function editline($arr){
		global $adb, $metaLogs,$table_prefix; // crmv@49398 crmv@136231
		//crmv@136231
		//get previous value
		$sql_prev = "SELECT code
				FROM tbl_s_picklist_language
				WHERE code_system = ? AND field = ?
				GROUP BY code";
		$res = $adb->pquery($sql_prev,Array($arr['code_system'],$this->field_name));
		if ($res){
			$prev_value = $adb->query_result($res,0,'code');
		}
		//crmv@136231e
		//cancel all previous values
//		if (!$this->control_code_unique($arr['code'])) return 'LBL_CODE_NOT_UNIQUE';
		$sql = "delete from tbl_s_picklist_language where code_system = ? and field = ?";
		$params = Array($arr['code_system'],$this->field_name);
//		echo "<br>".$adb->convert2Sql($sql,$adb->flatten_array($params));
		$adb->pquery($sql,$params);
		//insert all languages
		foreach ($this->languages as $language){
			$params = Array($arr['code_system'],$arr['code'],$this->field_name,$language['prefix'],$arr[$language['prefix']]);
			$sql = "insert into tbl_s_picklist_language (code_system,code,field,language,value) values (".generateQuestionMarks($params).")";
//			echo "<br>".$adb->convert2Sql($sql,$adb->flatten_array($params));
			$adb->pquery($sql,$params);
		}
		//crmv@136231
		$qry="SELECT tablename,columnname FROM ".$table_prefix."_field WHERE fieldname=? AND presence IN (0,2)";
		$result = $adb->pquery($qry, array($this->field_name));
		$num = $adb->num_rows($result);
		if($num > 0){
			for($n=0;$n<$num;$n++){
				$table_name = $adb->query_result_no_html($result,$n,'tablename');
				$columnName = $adb->query_result_no_html($result,$n,'columnname');
				
				$sql_update = "UPDATE $table_name SET $columnName=? WHERE $columnName=?";
				$adb->pquery($sql_update, array($arr['code'], $prev_value));
			}
		}
		//crmv@136231e
		
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($this->module_name), $this->field_name)); // crmv@49398
		return true;
	}
	function addline($arr){
		global $adb, $metaLogs; // crmv@49398
//		if (!$this->control_code_unique($arr['code'])) return 'LBL_CODE_NOT_UNIQUE';
		//take unique id
		$id=$adb->getUniqueID("tbl_s_picklist_language");
		//insert all languages
			foreach ($this->languages as $language){
			$params = Array($id,$arr['code'],$this->field_name,$language['prefix'],$arr[$language['prefix']]);
			$sql = "insert into tbl_s_picklist_language (code_system,code,field,language,value) values (".generateQuestionMarks($params).")";
//			echo "<br>".$adb->convert2Sql($sql,$adb->flatten_array($params));
			$adb->pquery($sql,$params);
		}
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($this->module_name), $this->field_name)); // crmv@49398
		return true;
	}
	function removeline($arr){
		global $adb, $metaLogs; // crmv@49398
		//remove all about that id
		$params = $arr['code_system'];
		$sql="delete from tbl_s_picklist_language where code_system in (".generateQuestionMarks($params).") and field = ?";
		$params[] = $this->field_name;
		$adb->pquery($sql,$params);
		if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($this->module_name), $this->field_name)); // crmv@49398
		return true;
	}
	function control_code_unique($code,$code_system,$mode){
		global $adb;
		switch ($mode){
			case 'edit':{
				$sql = "select count(*) as cnt from tbl_s_picklist_language where field = ? and code = ? and code_system <> ?";
				$params = Array($this->field_name,$code,$code_system);
			break;
			}
			case 'add':{
				$sql = "select count(*) as cnt from tbl_s_picklist_language where field = ? and code = ?";
				$params = Array($this->field_name,$code);
			break;
			}
		}
		$res = $adb->pquery($sql,$params);
		if ($res && $adb->num_rows($res) == 1){
				if ($adb->query_result($res,0,'cnt') > 1 ) return 'LBL_CODE_NOT_UNIQUE';
				else return true;
		}
		else return 'LBL_CODE_NOT_UNIQUE';
	}
	function create_picklist($module,$label){
		include_once('vtlib/Vtecrm/Menu.php');//crmv@207871
		include_once('vtlib/Vtecrm/Module.php');//crmv@207871
		//in progress!

	}
	function get_search_values($field,$valueArray,$operator){
		global $current_language,$adb;
		$found = false;
		$values = array();
		$params = array();
		$ssql = "select code from tbl_s_picklist_language where ";
		$cnt = 0;
		//crmv@31346
		foreach ($valueArray as $value){
			switch($operator) {
				case 'e':
				case 'n':
					$sqlOperator = "=";
					break;
				case 's': $sqlOperator = "LIKE";
					$value = "$value%";
					break;
				case 'ew': $sqlOperator = "LIKE";
					$value = "%$value";
					break;
				case 'c':
				case 'k':
					$sqlOperator = "LIKE";
					$value = "%$value%";
					break;
				case 'l': $sqlOperator = "<";
					break;
				case 'g': $sqlOperator = ">";
					break;
				case 'm': $sqlOperator = "<=";
					break;
				case 'h': $sqlOperator = ">=";
					break;
			}
			$value = $adb->sql_escape_string($value);
			if ($cnt > 0)
				$ssql .= "or ";
			else
				$ssql .= "(";
			$ssql .= " value $sqlOperator ? ";
			array_push($params,$value);
			$cnt++;
		}
		if ($cnt > 0)
			$ssql .= ")";
		$ssql.=" and field =? and language = ?";
		array_push($params,$field,$current_language);
//		echo $adb->convert2Sql($ssql,$adb->flatten_array($params));die;
		$res=$adb->pquery($ssql,$params);
		if ($res && $adb->num_rows($res) > 0){
			while ($row = $adb->fetchByAssoc($res))
				array_push($values,$row['code']);
			$found = true;
		}
		if (!$found)
			return Array($valueArray,$operator); //cmrv@33988
		else{
			switch($operator) {
				case 'k':
					$newsqlOperator = "n";
					break;
				case 's':
				case 'ew':
				case 'c':
				case 'l':
				case 'g':
				case 'm':
				case 'h':
					$newsqlOperator = "e";
					break;
				default:
					$newsqlOperator = $operator;
					break;
			}
			return Array(array_unique($adb->flatten_array($values)),$newsqlOperator);
		}
		//crmv@31346 e
	}
}
?>