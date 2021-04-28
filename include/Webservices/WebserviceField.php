<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class WebserviceField{
	private $fieldId;
	private $uitype;
	private $blockId;
	private $blockName;
	private $panelId; // crmv@104568
	private $nullable;
	private $default;
	private $tableName;
	private $columnName;
	private $fieldName;
	private $fieldLabel;
	private $editable;
	private $fieldType;
	private $displayType;
	private $mandatory;
	private $massEditable;
	private $tabid;
	private $presence;
	private $sequence; // crmv@31780
	private $generatedType; // crmv@206140

	/**
	 *
	 * @var PearDatabase
	 */
	private $pearDB;
	private $typeOfData;
	private $fieldDataType;
	private $dataFromMeta;
	private static $tableMeta = array();
	private static $fieldTypeMapping = array();
	private $referenceList;
	private $defaultValuePresent;
	private $explicitDefaultValue;

	private $genericUIType = 10;

	private $readOnly = 0;

	private function __construct($adb,$row){
		$this->uitype = $row['uitype'];
		$this->blockId = $row['block'];
		$this->blockName = null;
		$this->panelId = $row['panelid']; // crmv@104568
		$this->tableName = $row['tablename'];
		$this->columnName = $row['columnname'];
		$this->fieldName = $row['fieldname'];
		$this->fieldLabel = $row['fieldlabel'];
		$this->displayType = $row['displaytype'];
		$this->massEditable = ($row['masseditable'] === '1')? true: false;
		$typeOfData = $row['typeofdata'];
		$this->presence = $row['presence'];
		$this->typeOfData = $typeOfData;
		$typeOfData = explode("~",$typeOfData);
		$this->mandatory = ($typeOfData[1] == 'M')? true: false;
		if($this->uitype == 4){
			$this->mandatory = false;
		}
		$this->fieldType = $typeOfData[0];
		$this->tabid = $row['tabid'];
		$this->fieldId = $row['fieldid'];
		$this->sequence = (empty($row['profile_sequence']) ? $row['sequence'] : $row['profile_sequence']); // crmv@31780 crmv@39110
		$this->pearDB = $adb;
		$this->fieldDataType = null;
		$this->dataFromMeta = false;
		$this->defaultValuePresent = false;
		$this->referenceList = null;
		$this->explicitDefaultValue = false;

		$this->readOnly = (isset($row['readonly']))? $row['readonly'] : 0;

		if(isset($row['defaultvalue']) && $row['defaultvalue'] != '') {	//crmv@fix
			$this->setDefault($row['defaultvalue']);
		}

		$this->generatedType = $row['generatedtype']; // crmv@206140
	}

	public static function fromQueryResult($adb,$result,$rowNumber){
		 return new WebserviceField($adb,$adb->query_result_rowdata($result,$rowNumber));
	}

	public static function fromArray($adb,$row){
		return new WebserviceField($adb,$row);
	}
	
	// crmv@129138
	/**
	 * Retrieve a field instance from the cache, if present
	 */
	public static function fromCachedWS($module, $fieldname){
		global $adb, $table_prefix;
		static $wsCache = array();
		$key = $module.'_'.$fieldname;
		if (!isset($wsCache[$key])) {
			// crmv@193294
			$fieldrow = FieldUtils::getField($module, $fieldname); // here calendar = tasks and events = activities
			$wsCache[$key] = WebserviceField::fromArray($adb,$fieldrow);
			// crmv@193294e
		}
		return $wsCache[$key];
	}
	// crmv@129138e

	public function getTableName(){
		return $this->tableName;
	}

	public function getFieldName(){
		return $this->fieldName;
	}

	public function getFieldLabelKey(){
		return $this->fieldLabel;
	}

	public function getFieldType(){
		return $this->fieldType;
	}

	//crmv@49510 crmv@162449
	public function isMandatory($user=''){
		if (!empty($user)) {
			static $mandatory_arr = false;
			if ($mandatory_arr === false){
				$mandatory_arr = Array();
				global $adb, $table_prefix, $current_user;
				$profileList = getCurrentUserProfileList();
				//crmv@60969
				$mandCol = 'mandatory';
				$adb->format_columns($mandCol);
				$query = "select {$table_prefix}_profile2field.fieldid, min($mandCol) as \"mandatory\" from {$table_prefix}_profile2field
				inner join {$table_prefix}_field on {$table_prefix}_field.fieldid = {$table_prefix}_profile2field.fieldid
				inner join {$table_prefix}_profile2tab on {$table_prefix}_profile2tab.tabid = {$table_prefix}_profile2field.tabid and {$table_prefix}_profile2tab.profileid = {$table_prefix}_profile2field.profileid and {$table_prefix}_profile2tab.permissions = 0
				where {$table_prefix}_profile2field.visible = 0 and {$table_prefix}_profile2field.profileid IN (".generateQuestionMarks($profileList).")
				group by {$table_prefix}_profile2field.fieldid";
				//crmv@60969e
				$result = $adb->pquery($query, array($profileList));
				if ($result && $adb->num_rows($result) > 0) {
					while($row_res = $adb->fetchByAssoc($result,-1,false)){
						$mandatory_arr[$row_res['fieldid']] = $row_res['mandatory'];
					}
				}
			}
			if (isset($mandatory_arr[$this->fieldId]) && $mandatory_arr[$this->fieldId] == '0'){
				return true;
			}
		}
		return $this->mandatory;
	}
	//crmv@49510e crmv@162449e
	
	// crmv@98500
	public function isEntityNameField() {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT tablename, fieldname FROM {$table_prefix}_entityname WHERE tabid = ?", array($this->tabid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->fetchByAssoc($res,-1, false);
			$fields = array_map('trim', explode(',', $row['fieldname']));
			if ($this->tableName == $row['tablename'] && in_array($this->fieldName, $fields)) {
				return true;
				
			}
		}
		return false;
	}
	// crmv@98500e

	public function getTypeOfData(){
		return $this->typeOfData;
	}

	public function getDisplayType(){
		return $this->displayType;
	}

	public function getMassEditable(){
		return $this->massEditable;
	}

	public function getFieldId(){
		return $this->fieldId;
	}

	// crmv@31780
	public function getSequence() {
		return $this->sequence;
	}
	// crmv@31780e

	public function getDefault(){
		if($this->dataFromMeta !== true && $this->explicitDefaultValue !== true){
			$this->fillColumnMeta();
		}
		return $this->default;
	}

	public function getColumnName(){
		return $this->columnName;
	}

	public function getBlockId(){
		return $this->blockId;
	}
	
	// crmv@104568
	public function getPanelId(){
		return $this->panelId;
	}
	// crmv@104568e

	public function getBlockName(){
		if(empty($this->blockName)) {
			$this->blockName = getBlockName($this->blockId);
		}
		return $this->blockName;
	}

	public function getTabId(){
		return $this->tabid;
	}

	public function isNullable(){
		if($this->dataFromMeta !== true){
			$this->fillColumnMeta();
		}
		return $this->nullable;
	}

	public function hasDefault(){
		if($this->dataFromMeta !== true && $this->explicitDefaultValue !== true){
			$this->fillColumnMeta();
		}
		return $this->defaultValuePresent;
	}

	public function getUIType(){
		return $this->uitype;
	}

	public function isReadOnly() {
		if($this->readOnly == 99) return true;
		return false;
	}

	private function setNullable($nullable){
		$this->nullable = $nullable;
	}

	public function setDefault($value){
		$this->default = $value;
		$this->explicitDefaultValue = true;
		$this->defaultValuePresent = true;
	}

	public function setFieldDataType($dataType){
		$this->fieldDataType = $dataType;
	}

	public function setReferenceList($referenceList){
		$this->referenceList = $referenceList;
	}

	public function getTableFields(){
		$tableFields = null;
		if(isset(WebserviceField::$tableMeta[$this->getTableName()])){
			$tableFields = WebserviceField::$tableMeta[$this->getTableName()];
		}else{
			$dbMetaColumns = $this->pearDB->database->MetaColumns($this->getTableName());
			$tableFields = array();
			if (is_array($dbMetaColumns)) {
				foreach ($dbMetaColumns as $key => $dbField) {
					$tableFields[$dbField->name] = $dbField;
				}
			}
			WebserviceField::$tableMeta[$this->getTableName()] = $tableFields;
		}
		return $tableFields;
	}
	public function fillColumnMeta(){
		$tableFields = $this->getTableFields();
		foreach ($tableFields as $fieldName => $dbField) {
			if(strcmp($fieldName,$this->getColumnName())===0){
				$this->setNullable(!$dbField->not_null);
				if($dbField->has_default === true && !$this->explicitDefaultValue){
					$this->defaultValuePresent = $dbField->has_default;
					$this->setDefault($dbField->default_value);
				}
			}
		}
		$this->dataFromMeta = true;
	}

	public function getFieldDataType(){
		if($this->fieldDataType === null){
			$fieldDataType = $this->getFieldTypeFromUIType();
			if($fieldDataType === null){
				$fieldDataType = $this->getFieldTypeFromTypeOfData();
			}
			//crmv@15893 fix datetime
			$tableFieldDataType = $this->getFieldTypeFromTable();
			if(($fieldDataType != 'date' && $fieldDataType != 'time') && ($tableFieldDataType == 'datetime' || $tableFieldDataType == 'timestamp')){	//crmv@21249
				$fieldDataType = 'datetime';
			}
			//crmv@15893 fix datetime end
			// crmv@31780
			if ($this->getUIType() == '55' && $this->getFieldName() == 'salutationtype') {
				$fieldDataType = 'picklist';
			}
			// crmv@31780e
			$this->fieldDataType = $fieldDataType;
		}
		return $this->fieldDataType;
	}

	public function getReferenceList(){
		global $table_prefix;
		static $referenceList = array();
		if($this->referenceList === null){
			if(isset($referenceList[$this->getFieldId()])){
				$this->referenceList = $referenceList[$this->getFieldId()];
				return $referenceList[$this->getFieldId()];
			}
			if(!isset(WebserviceField::$fieldTypeMapping[$this->getUIType()])){
				$this->getFieldTypeFromUIType();
			}
			$fieldTypeData = WebserviceField::$fieldTypeMapping[$this->getUIType()];
			$referenceTypes = array();
			if($this->getUIType() != $this->genericUIType){
				$sql = "select * from ".$table_prefix."_ws_referencetype where fieldtypeid=?";
				$params = array($fieldTypeData['fieldtypeid']);
			}else{
				$sql = 'select relmodule as type from '.$table_prefix.'_fieldmodulerel where fieldid=?';
				$params = array($this->getFieldId());
			}
			$result = $this->pearDB->pquery($sql,$params);
			$numRows = $this->pearDB->num_rows($result);
			for($i=0;$i<$numRows;++$i){
				array_push($referenceTypes,$this->pearDB->query_result($result,$i,"type"));
			}

			//to handle hardcoding done for Calendar module todo activities.
			//crmv@23515
			if(in_array($this->tabid,array(9,16)) && $this->fieldName =='parent_id'){
				$relatedto = getCalendarRelatedToModules();
				foreach($relatedto as $relatedto_module) {
					if (!in_array($relatedto_module,$referenceTypes)) {
						$referenceTypes[] = $relatedto_module;
					}
				}
			}
			//crmv@23515e
			//crmv@392267
			global $current_user;
			$types = vtws_listtypes(null, $current_user);
			$accessibleTypes = $types['types'];
			if(!is_admin($current_user)) {
				array_push($accessibleTypes, 'Users');
			}
			$referenceTypes = array_values(array_intersect($accessibleTypes,$referenceTypes));
			$referenceTypes = array_unique($referenceTypes); //crmv@115268 remove duplicates
			$referenceList[$this->getFieldId()] = $referenceTypes;
			$this->referenceList = $referenceTypes;
			return $referenceTypes;
		}
		return $this->referenceList;
	}
	//crmv@fix index column
	public static function getIndexColumn($adb,$tableName){
		global $table_prefix;
		$sql = "select index_field from ".$table_prefix."_ws_entity_name where table_name = ?";
		$params = array($tableName);
		$res = $adb->pquery($sql,$params);
		if ($res)
			$index_field = $adb->query_result($res,0,'index_field');
		return 	$index_field;
	}
	//crmv@fix index column end
	private function getFieldTypeFromTable(){
		$tableFields = $this->getTableFields();
		$colname = $this->getColumnName();
		if (array_key_exists($colname, $tableFields)) {
			return $tableFields[$colname]->type;
		}
		//This should not be returned if entries in DB are correct.
		return null;
	}

	private function getFieldTypeFromTypeOfData(){
		switch($this->fieldType){
			case 'T': return "time";
			case 'D':
			case 'DT': return "date";
			case 'E': return "email";
			case 'N':
			case 'NN': return "double";
			case 'P': return "password";
			case 'I': return "integer";
			case 'V':
			default: return "string";
		}
	}

	private function getFieldTypeFromUIType(){
		global $table_prefix;
		// Cache all the information for futher re-use
		if(empty(self::$fieldTypeMapping)) {
			$result = $this->pearDB->pquery("select * from ".$table_prefix."_ws_fieldtype", array());
			while($resultrow = $this->pearDB->fetch_array($result)) {
				self::$fieldTypeMapping[$resultrow['uitype']] = $resultrow;
			}
		}

		if(isset(WebserviceField::$fieldTypeMapping[$this->getUIType()])){
			if(WebserviceField::$fieldTypeMapping[$this->getUIType()] === false){
				return null;
			}
			$row = WebserviceField::$fieldTypeMapping[$this->getUIType()];
			return $row['fieldtype'];
		} else {
			WebserviceField::$fieldTypeMapping[$this->getUIType()] = false;
			return null;
		}
	}

	function getPicklistDetails(){
		$hardCodedPickListNames = array("hdntaxtype","email_flag");
		$hardCodedPickListValues = array(
				"hdntaxtype"=>array(
					array("label"=>"Individual","value"=>"individual"),
					array("label"=>"Group","value"=>"group")
				),
				"email_flag" => array(
					array('label'=>'SAVED','value'=>'SAVED'),
					array('label'=>'SENT','value' => 'SENT'),
					array('label'=>'MAILSCANNER','value' => 'MAILSCANNER')
				)
			);
		if(in_array(strtolower($this->getFieldName()),$hardCodedPickListNames)){
			return $hardCodedPickListValues[strtolower($this->getFieldName())];
		}
		return $this->getPickListOptions($this->getFieldName());
	}

	// crmv@79748
	function getPickListOptions(){
		// crmv@34374
		global $table_prefix;
		global $current_language, $default_language;
		// crmv@34374e
		$fieldName = $this->getFieldName();
		$moduleName = getTabModuleName($this->getTabId());
		if($moduleName == 'Events') $moduleName = 'Calendar';
		$default_charset = VTWS_PreserveGlobal::getGlobal('default_charset');
		$options = array();
		$sql = "select * from ".$table_prefix."_picklist where name=?";
		$result = $this->pearDB->pquery($sql,array($fieldName));
		$numRows = $this->pearDB->num_rows($result);
		if($numRows == 0 && $this->pearDB->table_exist($table_prefix."_$fieldName")){
			//crmv@67583
			$sql = "select * from ".$table_prefix."_$fieldName";
			if ($moduleName  == 'Calendar' && $fieldName == 'visibility') {
				$sql .= ' ORDER BY sortorderid ASC';
			}
			$result = $this->pearDB->pquery($sql,array());
			$numRows = $this->pearDB->num_rows($result);
			for($i=0;$i<$numRows;++$i){
				$elem = array();
				$picklistValue = $this->pearDB->query_result($result,$i,$fieldName);
				$picklistValue = decode_html($picklistValue);
				$elem["label"] = getTranslatedString($picklistValue,$moduleName);
				$elem["value"] = $picklistValue;
				array_push($options,$elem);
			}
			//crmv@67583
		}else{
			$user = VTWS_PreserveGlobal::getGlobal('current_user');
			// crmv@34374 - picklist multilingua
			if ($this->getUIType() == '1015') {
				if (empty($current_language)) {
					$current_language = empty($default_language) ? 'it_it' : $default_language;
				}
				$details = Picklistmulti::getTranslatedPicklist(false,$this->getFieldName());
				if (is_array($details)) {
					foreach ($details as $k => $v) {
						$elem = array("label"=>$v, "value"=>$k);
						array_push($options,$elem);
					}
				}
			} elseif ($this->getUIType() == '133') {
				// bu_mc picklist
				$details = getAssignedPicklistValues($fieldName,$user->roleid, $adb, $moduleName);
				foreach ($details as $picklistValue=>$translated_value){
					$elem = array();
					//$picklistValue = decode_html($details[$i]);
					$elem["label"] = $translated_value;
					$elem["value"] = $picklistValue;
					array_push($options,$elem);
				}
			// crmv@95157
			} elseif ($this->getUIType() == '212') {
				$SBU = StorageBackendUtils::getInstance();
				$list = $SBU->getAvailableBackends($moduleName);
				foreach ($list as $name => $label) {
					array_push($options,array(
						'label' => $label,
						'value' => $name,
					));
				}
			// crmv@95157e
			// crmv@201442
			} elseif ($this->getUIType() == '310' || $this->getUIType() == '311') {
				require_once('modules/SDK/src/310/310Utils.php');
				$CFU = new CountriesFieldUtils();
				$countries = $CFU->getAllValues($this->fieldId);
				foreach ($countries as $name => $label) {
					array_push($options,array(
						'label' => $label,
						'value' => $name,
					));
				}
			// crmv@201442e
			} else {
				$details = getPickListValues($fieldName,$user->roleid);
				for($i=0;$i<sizeof($details);++$i){
					$elem = array();
					$picklistValue = decode_html($details[$i]);
					$elem["label"] = getTranslatedString($picklistValue,$moduleName);
					$elem["value"] = $picklistValue;
					array_push($options,$elem);
				}
			}
			// crmv@34374e
		}
		return $options;
	}
	// crmv@79748e

	// crmv@78052
	function getLinkedPicklistDetails() {
		require_once('modules/SDK/examples/uitypePicklist/300Utils.php');
		
		$details = array();
		$module = getTabModuleName($this->getTabId());
		$dependents = linkedListGetConnectedLists($this->getFieldName(), $module);
		$details['dependents'] = $dependents;
		
		if (is_array($dependents) && count($dependents) > 0) {
			foreach ($dependents as $secPlist) {
				$maps = linkedListGetAllOptions($this->getFieldName(), $secPlist, $module);
				if (is_array($maps['matrix'])) {
					$map = array_map('array_keys', array_map('array_filter', $maps['matrix']));
					$details['mapping'][$secPlist] = $map;
				}
			}
		}
		
		return $details;
	}
	// crmv@78052e

	function getPresence() {
		return $this->presence;
	}

	//crmv@sdk-18508
	function getReadOnly() {
		return $this->readonly;
	}
	//crmv@sdk-18508e

	// crmv@206140
	function getGeneratedType() {
		return $this->generatedType;
	}
	// crmv@206140e
	
	//crmv@45034
	function isEmpty($value) {
		$is_empty = false;
		switch ($this->getFieldDataType()) {
			case 'date':
			case 'datetime':
				if ($value == '0000-00-00' || $value == '0000-00-00 00:00:00' || empty($value)) {
					$is_empty = true;
				}
				break;
			case 'picklist':
			case 'picklistmultilanguage':
			case 'multipicklist':
				$clean_value = str_replace(' ','',strtolower($value));
				$clean_value_trans = str_replace(' ','',strtolower(getTranslatedString($value)));
				if (empty($value)
					|| in_array($clean_value,array(
						'--none--',
						'--nd--','-- nd --','-nd-','- nd -',
						str_replace(' ','',strtolower(getTranslatedString('LBL_NONE'))),
						str_replace('--','',str_replace(' ','',strtolower(getTranslatedString('LBL_NONE')))),
						str_replace(' ','',strtolower(getTranslatedString('LBL_NO_F'))),
						str_replace('--','',str_replace(' ','',strtolower(getTranslatedString('LBL_NO_F')))),
					))
					|| in_array($clean_value_trans,array(
						'--none--',
						'--nd--','-- nd --','-nd-','- nd -',
						str_replace(' ','',strtolower(getTranslatedString('LBL_NONE'))),
						str_replace('--','',str_replace(' ','',strtolower(getTranslatedString('LBL_NONE')))),
						str_replace(' ','',strtolower(getTranslatedString('LBL_NO_F'))),
						str_replace('--','',str_replace(' ','',strtolower(getTranslatedString('LBL_NO_F')))),
					))
				) {
					$is_empty = true;
				}
				break;
			case 'integer':
			case 'double':
				if ($value == 0) {
					$is_empty = true;
				}
			default:
				if (empty($value)) {
					$is_empty = true;
				}
				break;
		}
		return $is_empty;
	}
	function isExcludedBySummary() {
		if ($this->getBlockName() == 'LBL_ADDRESS_INFORMATION' && !in_array($this->fieldName,array('bill_city','ship_city','mailingcity','othercity','city'))) {
			return true;
		}
		if ($this->getBlockName() == 'LBL_VENDOR_ADDRESS_INFORMATION' && !in_array($this->fieldName,array('city'))) {
			return true;
		}
		if (in_array($this->fieldName,array('createdtime','modifiedtime'))) {
			return true;
		}
		if ($this->getBlockName() == 'LBL_SLA') {
			return true;
		}
		return false;
	}
	function showInSummary() {
		if (in_array($this->fieldName,array('comments'))) {
			return true;
		}
	}
	//crmv@45034e
}
?>