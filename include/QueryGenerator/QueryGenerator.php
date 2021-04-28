<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'data/CRMEntity.php';
require_once 'modules/CustomView/CustomView.php';
require_once 'include/Webservices/Utils.php';
require_once 'include/Webservices/RelatedModuleMeta.php';

/**
 * Description of QueryGenerator
 *
 * @author MAK
 */
class QueryGenerator extends SDKExtendableClass { //crmv@42024
	protected $module;
	protected $customViewColumnList;
	protected $stdFilterList;
	protected $conditionals;
	protected $manyToManyRelatedModuleConditions;
	protected $groupType;
	protected $whereFields;
	/**
	 *
	 * @var VteCRMObjectMeta
	 */
	protected $meta;
	/**
	 *
	 * @var Users
	 */
	protected $user;
	protected $advFilterList;
	protected $fields;
	protected $fieldAlias; // crmv@97237
	protected $referenceModuleMetaInfo;
	protected $moduleNameFields;
	protected $referenceFieldInfoList;
	protected $referenceFieldList;
	protected $ownerFields;
	protected $columns;
	protected $fromClause;
	protected $whereClause;
	protected $appendSelectFields; // crmv@81761
	protected $appendRawSelect;	// crmv@97237
	protected $appendWhereClause; // crmv@37004
	protected $appendFromClause; // crmv@37004
	protected $skipDeletedClause = false; // crmv@49398
	protected $query;
	protected $groupInfo;
	protected $groupInfoTagL = '@#';	//crmv@23687
	protected $groupInfoTagR = '#@';	//crmv@23687
	protected $conditionInstanceCount;
	protected $conditionalWhere;
	public static $AND = 'AND';
	public static $OR = 'OR';
	protected $customViewFields;
	protected $tableNameAlias;	//crmv@31795
	protected $reportFilter;	//crmv@31775
	//crmv@34627
	protected $secondary_fields;
	protected $all_fields;
	protected $module_fields = array();
	//crmv@34627e
	public $fromString = '';	//crmv@2963m
	
	// crmv@133387
	protected $otherFieldsCache = array();
	public $useSelectPermissions = true;
	// crmv@133387e
	
	protected $addedJoins = array(); // crmv@153111

	public function __construct($module, $user) {
		$db = PearDatabase::getInstance();
		$this->module = $module;
		$this->customViewColumnList = null;
		$this->stdFilterList = null;
		$this->conditionals = array();
		$this->user = $user;
		$this->advFilterList = null;
		$this->fields = array();
		$this->fieldAlias = array(); // crmv@97237
		$this->referenceModuleMetaInfo = array();
		$this->moduleNameFields = array();
		$this->whereFields = array();
		$this->groupType = self::$AND;
		$this->meta = $this->getMeta($module);
		$this->moduleNameFields[$module] = $this->meta->getNameFields();
		$this->referenceFieldInfoList = $this->meta->getReferenceFieldDetails();
		$this->referenceFieldList = array_keys($this->referenceFieldInfoList);
		$this->ownerFields = $this->meta->getOwnerFields();
		$this->columns = null;
		$this->fromClause = null;
		$this->whereClause = null;
		$this->appendSelectFields = array(); // crmv@81761
		$this->appendRawSelect = array(); // crmv@97237
		$this->appendWhereClause = ''; // crmv@37004
		$this->appendFromClause = ''; // crmv@37004
		$this->query = null;
		$this->conditionalWhere = null;
		$this->groupInfo = '';
		$this->manyToManyRelatedModuleConditions = array();
		$this->conditionInstanceCount = 0;
		$this->customViewFields = array();
	}

	/**
	 *
	 * @param String:ModuleName $module
	 * @return EntityMeta
	 */
	public function getMeta($module) {
		$db = PearDatabase::getInstance();
		if (empty($this->referenceModuleMetaInfo[$module])) {
			$handler = vtws_getModuleHandlerFromName($module, $this->user);
			$meta = $handler->getMeta();
			$this->referenceModuleMetaInfo[$module] = $meta;
			if($module == 'Users') {
				$this->moduleNameFields[$module] = 'user_name';
			} else {
				$this->moduleNameFields[$module] = $meta->getNameFields();
			}
		}
		return $this->referenceModuleMetaInfo[$module];
	}

	public function reset() {
		$this->fromClause = null;
		$this->whereClause = null;
		$this->columns = null;
		$this->query = null;
	}

	public function setFields($fields) {
		$this->fields = $fields;
		//crmv@34627
		$this->customViewFields = $fields;
		$this->secondary_fields = array();
		$this->module_fields[] = $this->getModule();
		foreach ($fields as $field) {
			$this->all_fields[] = array('module'=>$this->getModule(),'fieldname'=>$field);
		}
		//crmv@34627e
	}

	public function getCustomViewFields() {
		return $this->customViewFields;
	}

	public function getFields() {
		return $this->fields;
	}

	public function getWhereFields() {
		return $this->whereFields;
	}

	public function getOwnerFieldList() {
		return $this->ownerFields;
	}

	public function getModuleNameFields($module) {
		return $this->moduleNameFields[$module];
	}

	public function getReferenceFieldList() {
		return $this->referenceFieldList;
	}

	public function getReferenceFieldInfoList() {
		return $this->referenceFieldInfoList;
	}

	// crmv@49398
	public function setSkipDeletedQuery($value) {
		$this->skipDeletedClause = $value;
	}
	// crmv@49398e

	public function getModule () {
		return $this->module;
	}

	public function getModuleFields () {
		$return = $this->meta->getModuleFields();
		
		//crmv@62929
		if($this->getModule() == 'Calendar'){
			$eventsMeta = $this->getMeta('Events');
			$moduleFieldsEvents = $eventsMeta->getModuleFields();
			foreach($moduleFieldsEvents as $fieldMetaName => $fieldMeta) {
				if (!in_array($fieldMetaName,array_keys($return))) {
					$return[$fieldMetaName] = $fieldMeta;
				}
			}
		}
		//crmv@62929 e

		return $return;
	}
	
	// crmv@133387
	public function getWSField($name, $usePermissions = true) {
		
		// check if in cache (with permissions)
		$modfields = $this->getModuleFields();
		$field = $modfields[$name];
		if (empty($field) && !$usePermissions) {
			// crmv@198024
			if (substr($name, 0, 9) == 'prodattr_') {
				$field = WebserviceField::fromCachedWS($this->module,'confprodinfo');
			} else {				
				$field = WebserviceField::fromCachedWS($this->module,$name);
			}
			// crmv@198024e
		}

		return $field;
	}
	// crmv@133387e
	
	//crmv@34627
	public function getSecondaryFields() {
		return $this->secondary_fields;
	}
	public function getModuleWidthFields($unset_list=array()) {
		if (!empty($unset_list)) {
			$tmp = array();
			foreach ($this->module_fields as $module_field) {
				if ($module_field != $this->module) {
					$tmp[] = $module_field;
				}
			}
			return $tmp;
		} else {
			return $this->module_fields;
		}
	}
	public function getAllFields() {
		return $this->all_fields;
	}
	
	public function setReportFilter($reportid,$module,$prefix='') {
		//crmv@91667 - parformance fix: removed the report generation
		if (!empty($prefix))
			$this->reportFilter = $prefix.'_'.$reportid;
		else
			$this->reportFilter = $reportid;
	}
	//crmv@34627e
	public function getConditionalWhere() {
		return $this->conditionalWhere;
	}

	public function getDefaultCustomViewQuery() {
		$customView = CRMEntity::getInstance('CustomView', $this->module); // crmv@115329
		$viewId = $customView->getViewId($this->module);
		return $this->getCustomViewQueryById($viewId);
	}

	public function initForDefaultCustomView() {
		$customView = CRMEntity::getInstance('CustomView', $this->module); // crmv@115329
		$viewId = $customView->getViewId($this->module);
		$this->initForCustomViewById($viewId);
	}

	//crmv@95082
	public function initForAllCustomView() {
		$customView = CRMEntity::getInstance('CustomView', $this->module); // crmv@115329
		$viewId = $customView->getViewIdByName('All',$this->module);
		$this->initForCustomViewById($viewId);
	}
	//crmv@95082e

	public function initForCustomViewById($viewId) {
		global $table_prefix;
		$customView = CRMEntity::getInstance('CustomView', $this->module); // crmv@115329
		$this->customViewColumnList = $customView->getColumnsListByCvid($viewId);
		if (!empty($this->customViewColumnList))
		foreach ($this->customViewColumnList as $customViewColumnInfo) {
			$details = explode(':', $customViewColumnInfo);
			$tmp = explode('_',$details[3]);
			$module = $tmp[0];
			if ($module == 'Notes') $module = 'Documents';
			if(empty($details[2]) && $details[1] == 'crmid' && $details[0] == $table_prefix.'_crmentity') {
				$name = 'id';
				$this->customViewFields[] = $name;
			//crmv@34627
			} elseif($module == $this->module) {
				$this->fields[] = $details[2];
				$this->customViewFields[] = $details[2];
				$this->all_fields[] = array('module'=>$this->module,'fieldname'=>$details[2]);
				if (!in_array($this->module,$this->module_fields)) {
					$this->module_fields[] = $this->module;
				}
			} else {
				$secondary_module = $module;
				$this->secondary_fields[$secondary_module][] = $details[2];
				$this->all_fields[] = array('module'=>$secondary_module,'fieldname'=>$details[2]);
				if (!in_array($secondary_module,$this->module_fields)) {
					$this->module_fields[] = $secondary_module;
				}
			}
			//crmv@34627e
		}
		
		if($this->module == 'Calendar' && !in_array('activitytype', $this->fields)) {
			$this->appendSelectFields[] = 'activitytype'; // crmv@184460
		}
		
		if($this->module == 'Documents' || $this->module == 'Myfiles') { // crmv@125367
			if(in_array('filename', $this->fields)) {
				// crmv@80764 crmv@81761
				// These 2 fields are necessary to display the filename in lists
				$this->appendSelectFields[] = 'filelocationtype';
				$this->appendSelectFields[] = 'filestatus';
				// crmv@80764e crmv@81761e
			}
		}
		$this->fields[] = 'id';
		
		$this->stdFilterList = $customView->getStdFilterByCvid($viewId);
		$this->advFilterList = $customView->getAdvFilterByCvid($viewId);
		$this->reportFilter = $customView->getReportFilter($viewId);	//crmv@31775
		
		$addedGroup = false; // crmv@192139
		if(is_array($this->stdFilterList)) {
			$value = array();
			if(!empty($this->stdFilterList['columnname'])) {
				//crmv@30702
				$name = explode(':',$this->stdFilterList['columnname']);
				$name = $name[2];
				// crmv@133387 - ignore permissions for where
				$this->startGroup('');
				$addedGroup = true; // crmv@192139
				$value[] = $this->fixDateTimeValue($name, $this->stdFilterList['startdate']);
				$value[] = $this->fixDateTimeValue($name, $this->stdFilterList['enddate'], false);
				//crmv@month_day patch
				if ($this->stdFilterList['only_month_and_day'] == 1)
					$this->addCondition($name, $value, 'BETWEEN_MONTHDAY', null, false, null, false);
				else
					$this->addCondition($name, $value, 'BETWEEN', null, false, null, false);
				//crmv@month_day patch end
				// crmv@133387e
			}
		}
		// crmv@192139
		if($this->conditionInstanceCount <= 0 && is_array($this->advFilterList) && count($this->advFilterList) > 0) {
			$this->startGroup('');
			$addedGroup = true;
		} elseif($this->conditionInstanceCount > 0 && is_array($this->advFilterList) && count($this->advFilterList) > 0) {
		// crmv@192139e
			$this->addConditionGlue(self::$AND);
		}
		//crmv@42329
		if(is_array($this->advFilterList)) {
			// crmv@133387 - removed line
			foreach ($this->advFilterList as $index=>$filter) {
				$name = explode(':',$filter['columnname']);
				if(empty($name[2]) && $name[1] == 'crmid' && $name[0] == $table_prefix.'_crmentity') {
					$name = $this->getSQLColumn('id');
				} else {
					$name = $name[2];
				}
				// crmv@133387 - ignore permissions for where
			}
			$this->advFilterList = array_values($this->advFilterList); // crmv@102331 - Fix indexes in case of deletion
			foreach ($this->advFilterList as $index=>$filter) {
				$name = explode(':',$filter['columnname']);
				if(empty($name[2]) && $name[1] == 'crmid' && $name[0] == $table_prefix.'_crmentity') {
					$name = $this->getSQLColumn('id');
				} else {
					$name = $name[2];
				}
				// crmv@133387 - ignore permissions for where
				$this->addCondition($name, decode_html($filter['value']), $filter['comparator'], null, false, null, false);
				// crmv@133387e
				if(count($this->advFilterList) -1  > $index) {
					$this->addConditionGlue(self::$AND);
				}
			}
		}
		//crmv@42329
		if ($addedGroup) { // crmv@192139
			$this->endGroup();
		}
	}
	//crmv@17997 crmv@166586
	public function getReverseTranslate($value,$operator,&$field=null,$firstMatch=true){
		global $current_language;
		
		$allTrans = array();
		// crmv@31396
		if ($field && $field->getFieldDataType() == 'picklist') {
			$plistvalues = getAllPickListValues($field->getFieldName(), $this->module);
			if (is_array($plistvalues)) {
				foreach ($plistvalues as $val=>$trans) {
					if (stripos($trans, $value) !== false) {
						$allTrans[] = $val;
						if ($firstMatch) return $val;
					}
				}
				if (!$firstMatch) return $allTrans;
			}
		}
		// crmv@31396e
		$val = $value;
		$lang_strings = return_module_language($current_language,$this->module);
		if (in_array($operator,Array('s','ew','c','k','bwt','ewt','cts','dcts'))){
			foreach ($lang_strings as $fieldlabel=>$trans_fieldlabel){
				if (!is_array($trans_fieldlabel) && stripos($trans_fieldlabel,$value)!==false && strpos($fieldlabel,'LBL_')===false){
					$val = $fieldlabel;
					$allTrans[] = $val;
					if ($firstMatch) break;
				}
			}
		}
		else{
			$mod_keys = array_keys(array_map('strtolower',$lang_strings), strtolower($value));
			foreach($mod_keys as $mod_idx=>$mod_key) {
				if (strpos($mod_key, 'LBL_') === false) {
					$val = $mod_key;
					$allTrans[] = $val;
					if ($firstMatch) break;
				}
			}
		}
		return $firstMatch ? $val : $allTrans;
	}
	//crmv@17997e crmv@166586e
	//crmv@52322
	function searchReverseInAllTranslations($value,$operator,&$field=null) {
		
		static $temp_mod_strings = array();
		static $temp_app_strings = array();
		
		$languageInstance = new Vtecrm_Language();
		$languages = $languageInstance->getAll();
		
		if ($field && $field->getFieldDataType() == 'picklist') {
			$plistvalues = getAllPickListValues($field->getFieldName(), $this->module);
			$plistvalues = array_keys($plistvalues);

			foreach ($plistvalues as $val) {
				foreach($languages as $prefix => $descr) {
					if (empty($temp_mod_strings[$prefix][$this->module])) {
						try {
							$temp_mod_strings[$prefix][$this->module] = return_module_language($prefix,$this->module);
						} catch (Exception $e) {}
					}
					if (empty($temp_app_strings[$prefix])) {
						try {
							$temp_app_strings[$prefix] = return_application_language($prefix);
						} catch (Exception $e) {}
					}
					$trans = ($temp_mod_strings[$prefix][$this->module][$val] != '')?$temp_mod_strings[$prefix][$this->module][$val]:(($temp_app_strings[$prefix][$val] != '')?$temp_app_strings[$prefix][$val]:$val);

					// crmv@154469
					if ($operator == 'e' || $operator == 'n') {
						if (strcasecmp($trans, $value) === 0) return $val;
					} else {
						if (stripos($trans, $value) !== false) return $val;
					}
					// crmv@154469e
				}
			}
		} else {	//TODO
			return $this->getReverseTranslate($value,$operator,$field);
		}
	}
	//crmv@52322e
	public function getCustomViewQueryById($viewId) {
		$this->initForCustomViewById($viewId);
		return $this->getQuery();
	}
	//crmv@modify getQuery+ Calendar
	public function getQuery($onlyfields = false) {
		global $table_prefix;
		if(empty($this->query)) {
			$conditionedReferenceFields = array();
			$allFields = array_merge($this->whereFields,$this->fields);
			foreach ($allFields as $fieldName) {
				if(in_array($fieldName,$this->referenceFieldList)) {
					$moduleList = $this->referenceFieldInfoList[$fieldName];
					foreach ($moduleList as $module) {
						if(empty($this->moduleNameFields[$module])) {
							$meta = $this->getMeta($module);
						}
					}
				} elseif(in_array($fieldName, $this->ownerFields )) {
					$meta = $this->getMeta('Users');
					$meta = $this->getMeta('Groups');
				}
			}
			$query = 'SELECT ';
			//crmv@392267
			$this->getSelectClauseColumnSQL($onlyfields);
			//crmv@18124
			if ($onlyfields)
				return explode(',',$this->columns);
			//crmv@18124 end
			$query .= $this->columns;
			//crmv@392267 e
			//crmv@34627
			$secondary_fields = $this->secondary_fields;
			if (!empty($secondary_fields)) {
				$this->related_fields = getRelationFields($this->module, $this->getModuleWidthFields(array($this->module)), null, null, $this->reportFilter);
				//crmv@151722
				if (!empty($this->related_fields['fields']['direct'])) {
					$select_columns = explode(',',$this->columns);
					foreach ($this->related_fields['fields']['direct'] as $fieldid => $rel_info) {
						$sec_fields = $secondary_fields[$rel_info['module']];
						unset($secondary_fields[$rel_info['module']]);
						if (!empty($sec_fields)) {
							$sec_field_count = 0;
							if (!in_array("{$rel_info['tablename']}.{$rel_info['columnname']}",$select_columns)) $query .= ",{$rel_info['tablename']}.{$rel_info['columnname']}"; // crmv@145057
							$this->addModuleJoin($rel_info['tablename'], 'LEFT'); // crmv@159603
							foreach($sec_fields as $sec_field) {
								$query .= ",{$rel_info['tablename']}.{$rel_info['columnname']} as \"{$rel_info['module']}::$sec_field::$sec_field_count\"";
								$sec_field_count++;
							}
						}
					}
				}
				//crmv@151722e
				if (!empty($this->related_fields['fields']['indirect']))
				foreach ($this->related_fields['fields']['indirect'] as $fieldid => $rel_info) {
					$sec_fields = $secondary_fields[$rel_info['module']];
					unset($secondary_fields[$rel_info['module']]);
					if (!empty($sec_fields)) {
						$sec_field_count = 0;
						foreach($sec_fields as $sec_field) {
							$query .= ",{$table_prefix}_crmentity.crmid as \"{$rel_info['module']}::$sec_field::$sec_field_count\"";
							$sec_field_count++;
						}
					}
				}
				if (!empty($this->related_fields['related']))
				foreach ($this->related_fields['related'] as $rel_module) {
					$sec_fields = $secondary_fields[$rel_module];
					unset($secondary_fields[$rel_module]);
					if (!empty($sec_fields)) {
						$sec_field_count = 0;
						foreach($sec_fields as $sec_field) {
							$query .= ",{$table_prefix}_crmentity.crmid as \"{$rel_module}::$sec_field::$sec_field_count\"";
							$sec_field_count++;
						}
					}
				}
			}
			//crmv@34627e
			$query .= $this->getFromClause();
			$query .= $this->getWhereClause();
			$query = $this->cleanUpQuery($query); // crmv@49398
			$query = $this->meta->getEntitylistQueryNonAdminChange($query);
			$this->query = $query;
			return $query;
		} else {			
			return $this->query;
		}
	}

	// crmv@49398 - removes some undesired things from the query
	protected function cleanUpQuery($q) {
		// final where, and, or
		$q = preg_replace('/(where|and|or)\s*$/i', '', $q);
		// ... where and ...
		$q = preg_replace('/where\s+and/i', 'where', $q);
		// double and
		$q = preg_replace('/and\s+and/i', 'AND', $q);
		return $q;
	}
	// crmv@49398e

	// crmv@97237
	public function getSQLColumn($name,$onlyfields, $usePermissions = true) { // crmv@133387
		$aliases = $this->fieldAlias[$name];
		
		if ($name == 'id') {
			$baseTable = $this->meta->getEntityBaseTable();
			$moduleTableIndexList = $this->meta->getEntityTableIndexList();
			$baseTableIndex = $moduleTableIndexList[$baseTable];
			$sqlcolumn = $baseTable.'.'.$baseTableIndex;
		// crmv@198024
		} elseif (substr($name, 0, 9) == 'prodattr_') {
			global $adb;
			$baseTable = $this->meta->getEntityBaseTable();
			if ($adb->supportsJSON()) {
				$sqlcolumn = $adb->jsonValue("{$baseTable}.confprodinfo", "\$.{$name}");
			} else {
				throw new Exception('JSON functions are not supproted in this version of database server');
			}
		// crmv@198024e
		} else {

			$field = $this->getWSField($name, $usePermissions); // crmv@133387
			$sql = '';
			//TODO optimization to eliminate one more lookup of name, incase the field refers to only
			//one module or is of type owner.
			$column = $field->getColumnName();
			if ($onlyfields){
				if ($column == 'crmid' && !$aliases)
					$column .= " as parent_id";
			}
			$sqlcolumn = $field->getTableName().'.'.$column;
		}
		if (!empty($aliases)) {
			$cols = array();
			foreach ($aliases as $alias) {
				$cols[] = $sqlcolumn . " AS \"$alias\"";
			}
			$sqlcolumn = implode(',', $cols);
		}
		return $sqlcolumn;
	}
	// crmv@97237e
	//crmv@modify getQuery+ Calendar end
	
	//crmv@392267
	public function getSelectClauseColumnSQL($onlyfields=false){
		global $adb, $table_prefix;
		$columns = array();
		$moduleFields = $this->getModuleFields();
		$accessibleFieldList = array_keys($moduleFields);
		$accessibleFieldList[] = 'id';
		// crmv@133387
		if ($this->useSelectPermissions) {
			$this->fields = array_intersect($this->fields, $accessibleFieldList);
		}
		// crmv@133387e
		foreach ($this->fields as $field) {
			$sql = $this->getSQLColumn($field,$onlyfields, $this->useSelectPermissions); // crmv@133387
			if (!in_array($sql,$columns)){
				$columns[] = $sql;
			}
		}
		// crmv@81761 - add select-only fields
		if (is_array($this->appendSelectFields) && count($this->appendSelectFields) > 0) {
			$this->appendSelectFields = array_intersect($this->appendSelectFields, $accessibleFieldList);
			foreach ($this->appendSelectFields as $field) {
				$sql = $this->getSQLColumn($field,$onlyfields, $this->useSelectPermissions); // crmv@133387
				if (!in_array($sql,$columns)){
					$columns[] = $sql;
				}
			}
		}
		//crmv@81761e
		if ($this->meta->getEntityName() == 'Calendar' && !$onlyfields){
			if (!in_array($table_prefix.'_activity.activitytype',$columns))
				$columns[] = $table_prefix.'_activity.activitytype';
			//crmv@17986
			if (!in_array($table_prefix.'_activity.eventstatus',$columns))
				$columns[] = $table_prefix.'_activity.eventstatus';
			//crmv@17986 end
			if (!in_array($table_prefix.'_activity.time_start',$columns))
				$columns[] = $table_prefix.'_activity.time_start';
			// crmv@25610
			if (!in_array($table_prefix.'_activity.time_end',$columns))
				$columns[] = $table_prefix.'_activity.time_end';
			// crmv@25610e

		}
		//crmv@17001 : Private Permissions
		if ($this->meta->getEntityName() == 'Calendar' && !in_array($table_prefix.'_activity.visibility',$columns))
			$columns[] = $table_prefix.'_activity.visibility';
		//crmv@17001e
		//crmv@9433
		if (vtlib_isModuleActive('Conditionals')){
	    	//crmv@36505
	    	$conditionals_obj = CRMEntity::getInstance('Conditionals');
	    	$conditional_fields = $conditionals_obj->getConditionalFields($this->module);
	    	//crmv@36505 e
			if (!empty($conditional_fields)){
				foreach ($conditional_fields as $row){
					$field_add = $row['tablename'].".".$row['columnname'];
					if (!in_array($field_add,$columns) && !empty($moduleFields[$row['fieldname']]))
						$columns[] = $field_add;
				}
			}
		}
		//crmv@9433 end
		//crmv@2963m
		if ($this->module == 'Messages' && isset($this->fromString) && !empty($this->fromString)) {
			$columns[] = "CASE WHEN ({$table_prefix}_messages.lastson IS NULL) THEN messageFather.messagesid ELSE NULL END AS thread";
		}
		//crmv@2963me
		//crmv@56233
		if ($this->module == 'HelpDesk' && !in_array($table_prefix.'_troubletickets.mailscanner_action',$columns)) {
			$columns[] = $table_prefix.'_troubletickets.mailscanner_action';
		}
		//crmv@56233e
		//crmv@60279
		if (($key = array_search($this->meta->getEntityBaseTable().'.newsletter_unsubscrpt',$columns)) !== false) {
			unset($columns[$key]);
			$columns[] = 'CASE WHEN (tbl_s_newsletter_g_unsub.email IS NULL) THEN 1 ELSE 0 END AS newsletter_unsubscrpt';
		}
		//crmv@60279e
		//crmv@94282
		if ($this->module == 'Messages' && ($key = array_search($this->meta->getEntityBaseTable().'.cleaned_body',$columns)) !== false) {
			unset($columns[$key]);
			$columns[] = "{$adb->database->substr}({$this->meta->getEntityBaseTable()}.cleaned_body,1,1500) as cleaned_body";
		}
		//crmv@94282e
		// crmv@97237
		if (is_array($this->appendRawSelect) && count($this->appendRawSelect) > 0) {
			foreach ($this->appendRawSelect as $rsel) {
				$columns[] = $rsel;
			}
		}
		// crmv@97237e
		//crmv@sdk-18508
		$sdk_files = SDK::getViews($this->module,'list_related_query');
		if (!empty($sdk_files)) {
			foreach($sdk_files as $sdk_file) {
				include($sdk_file['src']);
			}
		}
		//crmv@sdk-18508e
		if (!empty($columns)) $columns = array_values(array_unique($columns)); //crmv@190160
		$this->columns = implode(',',$columns);
		return $this->columns;
	}
	//crmv@392267e
	public function getFromClause() {
		global $adb,$table_prefix,$current_user,$current_language;  //crmv@74933
		if(!empty($this->query) || !empty($this->fromClause)) {
			return $this->fromClause;
		}
		$moduleFields = $this->getModuleFields();
		$tableList = array();
		$tableJoinMapping = array();
		$tableJoinCondition = array();
		$tableJoinCondAlias = array(); //crmv@74933
		//crmv@fix advanced query
		$instance = CRMEntity::getInstance($this->module);
		$fields = $this->whereFields;
		if ($instance->getListViewAdvSecurityParameter_check($this->module)){
			$arr = $instance->getListViewAdvSecurityParameter_fields($this->module);
			if (!empty($arr)) {
				foreach ($arr as $data){
					$data_exploded = explode(":",$data);
					$fields[] = $data_exploded[2];
				}
			}

		}
		//crmv@18242
		if (!VteSession::isEmpty($this->module.'_ORDER_BY')){
			if ($this->module == 'Calendar' && VteSession::get($this->module.'_ORDER_BY') == 'crmid')
				$fields[] = 'parent_id';
			else {
				//crmv@21856
				$webservice_field = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and columnname = ?',array(getTabid($this->module),VteSession::get($this->module.'_ORDER_BY'))),0);
				$fields[] = $webservice_field->getFieldName();
				//crmv@21856e
			}
		}
		//crmv@18039
		if (vtlib_isModuleActive('Conditionals')){
	    	//crmv@36505
	    	$conditionals_obj = CRMEntity::getInstance('Conditionals');
	    	$conditional_fields = $conditionals_obj->getConditionalFields($this->module);
	    	//crmv@36505 e
			if (!empty($conditional_fields)){
				foreach ($conditional_fields as $row){
					$field_add = $row['fieldname'];
					if (!in_array($field_add,$fields))
						$fields[] = $field_add;
				}
			}
		}
		//crmv@18039 end
		//crmv@95102
		$sdk_files = SDK::getViews($this->module,'list_related_query');
		if (!empty($sdk_files)) {
			$columns = Array();
			foreach($sdk_files as $sdk_file) {
				include($sdk_file['src']);
			}
			if (!empty($columns)){
				$trans_col_field = Array();
				foreach ($moduleFields as $f_name=>$f_obj){
					$trans_col_field[$f_obj->getTableName().".".$f_name] = $f_obj->getColumnName();
				}
				foreach ($columns as $column){
					if (!empty($trans_col_field[$column])){
						$fields[] = $trans_col_field[$column];	//crmv@144972
					}
				}
				unset($trans_col_field);
			}
			unset($columns);
		}
		//crmv@95102e
		foreach ($this->fields as $fieldName) {
			if ($fieldName == 'id') {
				continue;
			}

			$field = $this->getWSField($fieldName, $this->useSelectPermissions); // crmv@133387
			$baseTable = $field->getTableName();
			$tableIndexList = $this->meta->getEntityTableIndexList();
			$baseTableIndex = $tableIndexList[$baseTable];
			if($field->getFieldDataType() == 'reference') {
				$moduleList = $this->referenceFieldInfoList[$fieldName];
				$tableJoinMapping[$field->getTableName()] = 'INNER JOIN';
				if (!empty($moduleList)) {	//crmv@145724
					foreach($moduleList as $module) {
						if($module == 'Users') {
							//crmv@106396
							$appendIndex = '_fld_'.$field->getFieldId(); // crmv@110302
							$tableJoinCondition[$fieldName][$table_prefix.'_users '.$table_prefix.'_users'.$appendIndex] = $field->getTableName().".".$field->getColumnName()." = ".$table_prefix."_users{$appendIndex}.id";
							$tableJoinCondition[$fieldName][$table_prefix.'_groups '.$table_prefix.'_groups'.$appendIndex] = $field->getTableName().".".$field->getColumnName()." = ".$table_prefix."_groups{$appendIndex}.groupid";
							$tableJoinMapping[$table_prefix.'_users '.$table_prefix.'_users'.$appendIndex] = 'LEFT JOIN';
							$tableJoinMapping[$table_prefix.'_groups '.$table_prefix.'_groups'.$appendIndex] = 'LEFT JOIN';
							//crmv@106396e
						}
					}
				}
			//crmv@74933
			}elseif($field->getFieldDataType() == 'picklistmultilanguage'){ 
                $referenceTableName = 'tbl_s_picklist_language';
                $tableJoinCondAlias[$fieldName][$referenceTableName] = "tbl_pick_lang$fieldName";
                $MultiPickJoinAlias = $tableJoinCondAlias[$fieldName][$referenceTableName];
                $referenceTableIndex = 'code';
			    $multiPickCond = " AND $MultiPickJoinAlias.language = '{$current_language}' AND $MultiPickJoinAlias.field = '{$fieldName}' ";
			    $tableJoinMapping[$referenceTableName] = 'LEFT JOIN';
			    $tableJoinCondition[$fieldName][$referenceTableName] = $baseTable.'.'.$field->getColumnName().' = '.$MultiPickJoinAlias.'.'.$referenceTableIndex.$multiPickCond;
		    //crmv@74933e
			} elseif($field->getFieldDataType() == 'owner') {
				$tableList[$table_prefix.'_users'] = $table_prefix.'_users';
				$tableList[$table_prefix.'_groups'] = $table_prefix.'_groups';
				$tableJoinMapping[$table_prefix.'_users'] = 'LEFT JOIN';
				$tableJoinMapping[$table_prefix.'_groups'] = 'LEFT JOIN';
			//crmv@60279
			} elseif ($fieldName == 'newsletter_unsubscrpt') {
				$newsletterFocus = CRMEntity::getInstance('Newsletter');
				$tableJoinMapping['tbl_s_newsletter_g_unsub'] = 'LEFT JOIN';
				$tableJoinCondition[$fieldName]['tbl_s_newsletter_g_unsub'] = $newsletterFocus->email_fields[$this->module]['tablename'].".".$newsletterFocus->email_fields[$this->module]['columnname']." = tbl_s_newsletter_g_unsub.email";
			//crmv@60279e
			//crmv@129272
			} elseif($field->getUIType() == 204){
				$tableJoinMapping[$table_prefix.'_mailscanner_actions'] = 'LEFT JOIN';
				$tableJoinMapping[$table_prefix.'_mailscanner'] = 'LEFT JOIN';
				$tableJoinCondition[$fieldName][$table_prefix.'_mailscanner_actions'] = $table_prefix."_mailscanner_actions.actionid = ".$field->getTableName().".".$field->getColumnName();
				$tableJoinCondition[$fieldName][$table_prefix.'_mailscanner'] = $table_prefix."_mailscanner.scannerid = ".$table_prefix."_mailscanner_actions.scannerid ";
			//crmv@129272e
			}
			$tableList[$field->getTableName()] = $field->getTableName();
				$tableJoinMapping[$field->getTableName()] =
						$this->meta->getJoinClause($field->getTableName());
		}
		$baseTable = $this->meta->getEntityBaseTable();
		$moduleTableIndexList = $this->meta->getEntityTableIndexList();
		$baseTableIndex = $moduleTableIndexList[$baseTable];
		foreach ($fields as $fieldName) {
		//crmv@fix advanced query end
			if(empty($fieldName)) {
				continue;
			}
			$field = $this->getWSField($fieldName, false); // crmv@133387 crmv@138359
			if(empty($field)) {
				// not accessible field.
				continue;
			}
			$baseTable = $field->getTableName();
			//crmv@103450
			if ($this->module == 'Processes' && $fieldName == 'process_actor') {
				// do nothing
			//crmv@103450e
			} elseif($field->getFieldDataType() == 'reference') {
				$moduleList = $this->referenceFieldInfoList[$fieldName];
				//crmv@144125 crmv@152344
				$tableJoinMapping[$field->getTableName()] = 'INNER JOIN';
				if (in_array('Users', $moduleList)) {
					$usersInstance = CRMEntity::getInstance('Users');
					$referenceTable = $usersInstance->table_name;
					$tableIndexList = $usersInstance->tab_name_index;
					$referenceTableIndex = $tableIndexList[$referenceTable];
					
					// add the original table
					if (!isset($tableList[$baseTable])) {
						$tableList[$baseTable] = $baseTable;
						$tableJoinMapping[$baseTable] = 'LEFT JOIN';
					}
					// use alias based on the fieldid
					$appendIndex = '_fld_'.$field->getFieldId();
					$alias = substr($referenceTable.$appendIndex, 0, 29);
					$this->tableNameAlias[$referenceTable][$fieldName] = $alias;
					$referenceTableName = "$referenceTable {$alias}";
					$referenceTable = $alias;
				//crmv@174813
				} elseif (in_array('Groups', $moduleList)) {
					$alias = 'entityname_fld_'.$field->getFieldId();
					
					$referenceTableName = "{$table_prefix}_groups $alias";
					$referenceTableIndex = 'groupid';
					
					$this->tableNameAlias[$referenceTable][$fieldName] = $alias;
				//crmv@174813e
				} else {

					$ENU = EntityNameUtils::getInstance();
					$referenceTable = $ENU->display_table;
					$alias = 'entityname_fld_'.$field->getFieldId();
					
					$referenceTableName = "$referenceTable $alias";
					$referenceTableIndex = 'crmid';
					
					$this->tableNameAlias[$referenceTable][$fieldName] = $alias;
				}
				$tableJoinMapping[$referenceTableName] = 'LEFT JOIN';
				$tableJoinCondition[$fieldName][$referenceTableName] = $baseTable.'.'.$field->getColumnName().' = '.$alias.'.'.$referenceTableIndex;
				//crmv@144125e crmv@152344e
			//crmv@74933
            }elseif($field->getFieldDataType() == 'picklistmultilanguage'){
				// crmv@100376 - add the join with the field table
				$fieldTable = $field->getTableName();
				if (!isset($tableList[$fieldTable])) {
					$tableList[$fieldTable] = $field->getTableName();
					$tableJoinMapping[$fieldTable] = $this->meta->getJoinClause($field->getTableName());
				}
				// crmv@100376e
                $referenceTableName = 'tbl_s_picklist_language';
                $tableJoinCondAlias[$fieldName][$referenceTableName] = "tbl_pick_lang$fieldName";
                $MultiPickJoinAlias = $tableJoinCondAlias[$fieldName][$referenceTableName];
                $referenceTableIndex = 'code';
			    $multiPickCond = " AND $MultiPickJoinAlias.language = '{$current_language}' AND $MultiPickJoinAlias.field = '{$fieldName}' ";
			    $tableJoinMapping[$referenceTableName] = 'LEFT JOIN';
			    $tableJoinCondition[$fieldName][$referenceTableName] = $baseTable.'.'.$field->getColumnName().' = '.$MultiPickJoinAlias.'.'.$referenceTableIndex.$multiPickCond;
            //crmv@74933e
			} elseif($field->getFieldDataType() == 'owner') {
				$tableList[$table_prefix.'_users'] = $table_prefix.'_users';
				$tableList[$table_prefix.'_groups'] = $table_prefix.'_groups';
				$tableJoinMapping[$table_prefix.'_users'] = 'LEFT JOIN';
				$tableJoinMapping[$table_prefix.'_groups'] = 'LEFT JOIN';
			//crmv@60279
			} elseif ($fieldName == 'newsletter_unsubscrpt') {
				$newsletterFocus = CRMEntity::getInstance('Newsletter');
				$tableJoinMapping['tbl_s_newsletter_g_unsub'] = 'LEFT JOIN';
				$tableJoinCondition[$fieldName]['tbl_s_newsletter_g_unsub'] = $newsletterFocus->email_fields[$this->module]['tablename'].".".$newsletterFocus->email_fields[$this->module]['columnname']." = tbl_s_newsletter_g_unsub.email";
			//crmv@60279e
			//crmv@138353
			} elseif($field->getUIType() == 204){
				$tableList[$field->getTableName()] = $field->getTableName();
				$tableJoinMapping[$table_prefix.'_mailscanner_actions'] = 'LEFT JOIN';
				$tableJoinMapping[$table_prefix.'_mailscanner'] = 'LEFT JOIN';
				$tableJoinCondition[$fieldName][$table_prefix.'_mailscanner_actions'] = $table_prefix."_mailscanner_actions.actionid = ".$field->getTableName().".".$field->getColumnName();
				$tableJoinCondition[$fieldName][$table_prefix.'_mailscanner'] = $table_prefix."_mailscanner.scannerid = ".$table_prefix."_mailscanner_actions.scannerid ";
			//crmv@138353e
			} else {
				$tableList[$field->getTableName()] = $field->getTableName();
				$tableJoinMapping[$field->getTableName()] =
						$this->meta->getJoinClause($field->getTableName());
			}
		}

		$defaultTableList = $this->meta->getEntityDefaultTableList();
		//crmv@18242 crmv@31396
		if (in_array($this->module, array('Calendar', 'Events')) && in_array('parent_id',$fields)){ // crmv@117943
			$caltab = $table_prefix.'_seactivityrel';
			$defaultTableList[] = $caltab;
			$tableList[$caltab] = $caltab;
			$tableJoinMapping[$caltab] = 'LEFT JOIN';
		}
		//crmv@18242e crmv@31396e
		foreach ($defaultTableList as $table) {
			if(!in_array($table, $tableList)) {
				$tableList[$table] = $table;
				$tableJoinMapping[$table] = 'INNER JOIN';
			}
		}
		$ownerFields = $this->meta->getOwnerFields();
		if (count($ownerFields) > 0) {
			$ownerField = $ownerFields[0];
		}
		$baseTable = $this->meta->getEntityBaseTable();
		//crmv@2963m
		if (isset($this->fromString) && !empty($this->fromString)) {
			$sql = $this->fromString;
		} else {
			$sql = " FROM $baseTable ";
			// crmv@95586 crmv@146435
			if ($this->module == 'Messages' && $adb->isMySQL()) {
				$focusMessages = CRMEntity::getInstance('Messages');
				if ($focusMessages->force_index_querygenerator) $sql .= " FORCE INDEX ({$table_prefix}_messages_accfolddate) ";
			}
			// crmv@95586e crmv@146435e
		}
		//crmv@2963me
		unset($tableList[$baseTable]);
		foreach ($defaultTableList as $tableName) {
			$sql .= " $tableJoinMapping[$tableName] $tableName ON $baseTable.".
					"$baseTableIndex = $tableName.$moduleTableIndexList[$tableName]";
			unset($tableList[$tableName]);
		}
		foreach ($tableList as $tableName) {
			if($tableName == $table_prefix.'_users') {
				$field = $moduleFields[$ownerField];
				$sql .= " $tableJoinMapping[$tableName] $tableName ON ".$field->getTableName().".".
					$field->getColumnName()." = $tableName.id";
			} elseif($tableName == $table_prefix.'_groups') {
				$field = $moduleFields[$ownerField];
				$sql .= " $tableJoinMapping[$tableName] $tableName ON ".$field->getTableName().".".
					$field->getColumnName()." = $tableName.groupid";
			} else {
				$sql .= " $tableJoinMapping[$tableName] $tableName ON $baseTable.".
					"$baseTableIndex = $tableName.$moduleTableIndexList[$tableName]";
			}
		}
		// crmv@153111
		foreach ($this->addedJoins as $join) {
			$table = $join['table'];
			if (!in_array($table, $defaultTableList) && !in_array($table, $tableList) && $table != $baseTable) { // crmv@160098
				$type = strtoupper($join['type']) ?: 'LEFT';
				$alias = $join['alias'] ?: $table;
				$sql .= " $type JOIN $table $alias ON $alias.{$join['column']} = {$join['othertable']}.{$join['othercolumn']} {$join['conditions']}";
			}
		}
		// crmv@153111e

		//crmv@31775
		if ($this->reportFilter) {
			$customView = CRMEntity::getInstance('CustomView');
			$tableNameTmp = $customView->getReportFilterTableName($this->reportFilter,$current_user->id);
			//crmv@63349 crmv@91667
			if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
				$sql .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
			} else {
				if (strpos($this->reportFilter,'_') !== false){
					list($prefix,$reportid) = explode("_",$this->reportFilter);
					$reportid = intval($reportid);
					$prefix = intval($prefix);
				}
				else{
					$reportid = intval($this->reportFilter);
					$prefix = 0;
				}
				$sql .= " INNER JOIN $tableNameTmp ON 
					$tableNameTmp.userid = {$current_user->id} AND 
					$tableNameTmp.reportid = $reportid AND
					$tableNameTmp.prefix = $prefix AND
					$tableNameTmp.id = {$table_prefix}_crmentity.crmid";
			}
			//crmv@63349e crmv@91667e
		}
		//crmv@31775e

		// crmv@30014 - join aggiuntive
		$moduleInstance = CRMEntity::getInstance($this->module);
		if ($moduleInstance && method_exists($moduleInstance, 'getQueryExtraJoin')) {
			$extraJoin = $moduleInstance->getQueryExtraJoin();
			$sql .= " $extraJoin";
		}
		// crmv@30014e

		if( $this->meta->getTabName() == 'Documents' || $this->meta->getTabName() == 'Myfiles') { // crmv@125367
			$tableJoinCondition['folderid'] = array(
				$table_prefix.'_crmentityfolder'=>"$baseTable.folderid = ".$table_prefix."_crmentityfolder.folderid", // crmv@30967
			);
			$tableJoinMapping[$table_prefix.'_crmentityfolder'] = 'INNER JOIN'; // crmv@30967
		}
		//crmv@25530
		$alias = 2;
		foreach ($tableJoinCondition as $fieldName=>$conditionInfo) {
			foreach ($conditionInfo as $tableName=>$condition) {
				//crmv@74933
			    if(isset($tableJoinCondAlias[$fieldName][$tableName]) && !empty($tableJoinCondAlias[$fieldName][$tableName])){
			        $tableNameAlias = $tableJoinCondAlias[$fieldName][$tableName];
			    }else{
				//crmv@74933e
					if(!empty($tableList[$tableName])) {
						$tableNameAlias = $tableName.$alias;
						$condition = str_replace($tableName, $tableNameAlias, $condition);
						$alias++;
					} else {
						$tableNameAlias = '';
					}
				} //crmv@74933
				$sql .= " $tableJoinMapping[$tableName] $tableName $tableNameAlias ON $condition";
			}
		}
		//crmv@25530e
		foreach ($this->manyToManyRelatedModuleConditions as $conditionInfo) {
			$relatedModuleMeta = RelatedModuleMeta::getInstance($this->meta->getTabName(),
					$conditionInfo['relatedModule']);
			$relationInfo = $relatedModuleMeta->getRelationMeta();
			$relatedModule = $this->meta->getTabName();
			$sql .= ' INNER JOIN '.$relationInfo['relationTable']." ON ".
			$relationInfo['relationTable'].".$relationInfo[$relatedModule]=".
				"$baseTable.$baseTableIndex";
		}
		if ($this->appendFromClause) $sql .= $this->appendFromClause;	// crmv@37004 - extra joins
		$sql .= $this->meta->getEntityAccessControlQuery();
		$this->fromClause = $sql;
		return $sql;
	}

	//crmv@64325 crmv@171021
	protected function addSetypeCondition(){
		global $table_prefix;
		$focus = CRMEntity::getInstance($this->module);
		$cond = '';
		if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED') && $this->module != 'Users' && in_array($table_prefix.'_crmentity',$focus->tab_name)) {
			$seModule = $this->module;
			if ($seModule == 'Events') $seModule = 'Calendar';
			$cond = "AND {$table_prefix}_crmentity.setype = '$seModule' ";
		}
		return $cond;
	}
	//crmv@64325e crmv@171021e

	//crmv@modify where
	public function getWhereClause() {
		global $adb,$table_prefix,$showfullusername;	//crmv@126096
		if(!empty($this->query) || !empty($this->whereClause)) {
			return $this->whereClause;
		}
		// crmv@49398
		$sql = '';
		if (!$this->skipDeletedClause) {
			$deletedQuery = $this->meta->getEntityDeletedQuery();
			if(!empty($deletedQuery)) {
				// crmv@64325
				$setype = $this->addSetypeCondition();
				$sql .= " WHERE $deletedQuery $setype";
				// crmv@64325e
			}
		}
		// crmv@49398e
		if($this->conditionInstanceCount > 0) {
			$sql .= ' AND ';
		} elseif(empty($deletedQuery)) {
			$sql .= ' WHERE ';
		}

		$baseTable = $this->meta->getEntityBaseTable();
		$moduleTableIndexList = $this->meta->getEntityTableIndexList();
		$baseTableIndex = $moduleTableIndexList[$baseTable];
		$groupSql = $this->groupInfo;
		$fieldSqlList = array();
		foreach ($this->conditionals as $index=>$conditionInfo) {
			$fieldName = $conditionInfo['name'];
			
			// crmv@141878
			if ($fieldName == 'raw_condition' && !empty($conditionInfo['sql'])) {
				$fieldSqlList[$index] = $conditionInfo['sql'];
				continue;
			}
			// crmv@141878e
			
			$field = $this->getWSField($fieldName, $conditionInfo['check_permissions']); // crmv@133387
			if(empty($field)) {
				continue;
			}
			$fieldSql = '(';
			$fieldGlue = '';
			$valueSqlList = $this->getConditionValue($conditionInfo['value'],
				$conditionInfo['operator'], $field);
			if(!is_array($valueSqlList)) {
				$valueSqlList = array($valueSqlList);
			}
			foreach ($valueSqlList as $valueSql) {
				//crmv@103450 crmv@126096
				if ($this->module == 'Processes' && $fieldName == 'process_actor') {
					if (in_array($conditionInfo['operator'],array('e','n'))) {
						if (is_numeric($conditionInfo['value']))
							$fieldSql .= "$fieldGlue exists (select running_process from {$table_prefix}_actor_running_processes where running_process = {$baseTable}.running_process and userid {$valueSql})";
							else
								$fieldSql .= "$fieldGlue exists (select {$table_prefix}_actor_running_processes.running_process from {$table_prefix}_actor_running_processes
								inner join {$table_prefix}_users log_user_info on log_user_info.id = {$table_prefix}_actor_running_processes.userid
								where {$table_prefix}_actor_running_processes.running_process = {$baseTable}.running_process and log_user_info.user_name {$valueSql})";
					} elseif (in_array($conditionInfo['operator'],array('s','ew','c','k'))) {
						$accessQuery = trim($this->meta->getEntityAccessControlQuery());
						if (!empty($accessQuery)) $accessQuery = substr($accessQuery, 0, stripos($accessQuery,'.subuserid = ')).'.subuserid = log_user_info.id';
						$fieldSql .= "$fieldGlue exists (select {$table_prefix}_actor_running_processes.running_process from {$table_prefix}_actor_running_processes
						inner join {$table_prefix}_users log_user_info on log_user_info.id = {$table_prefix}_actor_running_processes.userid";
						if (!empty($accessQuery)) $fieldSql .= ' '.$accessQuery;
						$fieldSql .= " where {$table_prefix}_actor_running_processes.running_process = {$baseTable}.running_process and log_user_info.user_name {$valueSql})";
					}
				//crmv@103450e crmv@126096e
				//crmv@174419
				} elseif ($this->module == 'Messages' && isset($this->fromString) && !empty($this->fromString) && $fieldName == 'seen') {
					$casttype = $this->getCastValue($field);
					if ($casttype !==false){
						$casttype = strtoupper($casttype);
						$fieldTable = $field->getTableName();
						$fieldColumn = $field->getColumnName();
						$fieldSql .= "$fieldGlue (COALESCE(".$fieldTable.'.'.$fieldColumn.", cast('' as ".$casttype.")) $valueSql or COALESCE(messageFather.".$fieldColumn.", cast('' as ".$casttype.")) ".$valueSql.")";
					}
					else{
						$fieldSql .= "$fieldGlue (".$field->getTableName().'.'.$field->getColumnName()." $valueSql or messageFather.".$field->getColumnName()." $valueSql)";
					}
				//crmv@174419e
				} elseif (in_array($fieldName, $this->referenceFieldList)) {
					$moduleList = $this->referenceFieldInfoList[$fieldName];
					// crmv@144125
					// special modules should be alone in the array
					if (in_array('DocumentFolders', $moduleList) && $fieldName == 'folderid') {
						//crmv@103337
						if (in_array($conditionInfo['operator'], array('e', 'n')) && is_numeric($conditionInfo['value'])) {
							$fieldSql .= "$fieldGlue ".$table_prefix."_crmentityfolder.folderid $valueSql";
						}else{
							$fieldSql .= "$fieldGlue ".$table_prefix."_crmentityfolder.foldername $valueSql"; //crmv@30967
						}
						//crmv@103337e
					} elseif (in_array('Users', $moduleList)) {
						
						$usersInstance = CRMEntity::getInstance('Users');
						$referenceTable = $usersInstance->table_name;
						// use the aliased table if there is already an owner field or if we are in the quotes/vendors (WTF!!)
						if(count($this->ownerFields) > 0 || in_array($this->getModule(), array('Quotes', 'Vendors'))) { // crmv@146069
							//crmv@121417
							$appendIndex = '_fld_'.$field->getFieldId();
							$referenceTable = substr($referenceTable.$appendIndex, 0, 29);
							//crmv@121417e
						}
						
						$nameFields = $this->moduleNameFields['Users'];
						$nameFieldList = explode(',',$nameFields);
						
						// use the userid of searching for exact numeric match
						if ($nameFieldList[0] == 'user_name' && in_array($conditionInfo['operator'], array('e', 'n')) && is_numeric($conditionInfo['value'])) {
							$columnSql = $referenceTable.'.id';
						} else {
							$columnSql = $usersInstance->formatUserNameSql($adb, $referenceTable, $showfullusername);
						}
						
						$fieldSql .= "$fieldGlue $columnSql $valueSql";
					//crmv@174813
					} elseif (in_array('Groups', $moduleList)) {
						$alias = 'entityname_fld_'.$field->getFieldId();
						$fieldSql .= "$fieldGlue $alias.groupname ".$valueSql;
					//crmv@174813e
					} else {
						// standard modules
						$activeModules = array_filter($moduleList, 'vtlib_isModuleActive');
						if (count($activeModules) > 0) {
							$listMods = $adb->convert2Sql(generateQuestionMarks($activeModules), $activeModules);
							$alias = 'entityname_fld_'.$field->getFieldId();
							// crmv@178793
							if (in_array($conditionInfo['operator'], array('n', 'k'))) {
								$displaycond = "$fieldGlue (($alias.displayname ".$valueSql." AND $alias.setype IN (".$listMods.")) OR $alias.setype IS NULL)";
							} else {
								$displaycond = "$fieldGlue ($alias.displayname ".$valueSql." AND $alias.setype IN (".$listMods."))";
							}
							$fieldSql .= $displaycond;
							// crmv@178793e
						}
					}
					// crmv@144125e
					
				} elseif (in_array($fieldName, $this->ownerFields)) {
					if (in_array($conditionInfo['operator'], array('e', 'n')) && is_numeric($conditionInfo['value'])) {
						$fieldSql .= "$fieldGlue {$table_prefix}_users.id $valueSql ".self::$OR." {$table_prefix}_groups.groupid $valueSql";
					} else {
						//crmv@126096
						$focusUsers = CRMEntity::getInstance('Users');
						$user_field_search = $focusUsers->formatUserNameSql($adb, "{$table_prefix}_users", $showfullusername);
						$fieldSql .= "$fieldGlue $user_field_search $valueSql ".self::$OR." {$table_prefix}_groups.groupname $valueSql";
						//crmv@126096e
					}
				//crmv@60279
				} elseif ($fieldName == 'newsletter_unsubscrpt') {
					if (strtolower($conditionInfo['value']) == 'no' || strtolower($conditionInfo['value']) == 'yes') { // crmv@170050
						if ((in_array($conditionInfo['operator'],array('e','c')) && strtolower($conditionInfo['value']) == 'no') || ($conditionInfo['operator'] == 'n' && strtolower($conditionInfo['value']) == 'yes')) {
							$valueSql = 'IS NOT NULL';
						} else {
							$valueSql = 'IS NULL';
						}
						$fieldSql .= "tbl_s_newsletter_g_unsub.email $valueSql";
					}
				//crmv@60279e
				//crmv@129272
				} elseif ($field->getUIType() == 204) {
					$fieldSql .= "$fieldGlue {$table_prefix}_mailscanner.scannername ".$valueSql;
				//crmv@129272e
				// crmv@198024
				} elseif (substr($fieldName, 0, 9) == 'prodattr_') {
					$fieldTable = $field->getTableName();
					if ($adb->supportsJSON()) {
						$fieldSql .= "$fieldGlue ".$adb->jsonValue("{$fieldTable}.confprodinfo", "\$.{$fieldName}")." ".$valueSql;
					} else {
						throw new Exception('JSON functions are not supproted in this version of database server');
					}
				// crmv@198024e
				} else {
					if(($fieldName == 'birthday' || strtolower($conditionInfo['operator']) == 'between_monthday') && !$this->isRelativeSearchOperators(
							$conditionInfo['operator'])) {

						$fieldSql .= "$fieldGlue ".$adb->database->SQLDate('md',$field->getTableName().".".$field->getColumnName())." ".$valueSql;
					//crmv@54149
					} elseif($adb->isOracle() && $field->getFieldDataType() == 'text'){ //we assume that text datatypes are stored on CLOB columns
						$fieldSql .= "$fieldGlue LOWER(".$field->getTableName().'.'.$field->getColumnName().") ".$valueSql;
					//crmv@54149e
					} else {
						//crmv@36534 crmv@26565 crmv@56982 crmv@149399 crmv@146461
						$casttype = $this->getCastValue($field);
						if ($casttype !==false){
							$casttype = strtoupper($casttype);
							$fieldTable = $field->getTableName();
							$fieldColumn = $field->getColumnName();
							$fieldTabId = $field->getTabId(); // crmv@174171
							if (strpos($valueSql, ':') !== false && ($fieldName == 'date_start' || $fieldName == 'due_date') && in_array($fieldTabId,array(9,16))) { // crmv@174171
								$fieldColumn = ($fieldName == 'date_start' ? 'activity_start' : 'activity_end');
								$casttype = 'DATETIME';
							}
							if ($field->getFieldDataType() == 'datetime' && is_string($conditionInfo['value']) && strlen($conditionInfo['value']) == 10 && in_array($conditionInfo['operator'],array('e','n'))) { // crmv@175933
								$between_conditions = $this->getConditionValue(array($conditionInfo['value'].' 00:00:00',$conditionInfo['value'].' 23:59:59'), 'bw', $field);
								if (!is_array($between_conditions)) $between_conditions = array($between_conditions);
								foreach($between_conditions as $between_condition) {
									($conditionInfo['operator'] == 'n') ? $_o = 'NOT' : $_o = '';
									$fieldSql .= "$fieldGlue {$fieldTable}.{$fieldColumn} {$_o} $between_condition";
								}
							} elseif ($casttype == 'DATE' || $casttype == 'DATETIME') {
									// crmv@186515
									if ($casttype == 'DATE' && ($fieldName == 'createdtime' || $fieldName == 'modifiedtime') && in_array($conditionInfo['operator'], array('e', 'n')) ) {
										if ($adb->isMssql()) {
											$substrFn = 'SUBSTRING';
										} else {
											$substrFn = 'SUBSTR';
										}
										$fieldSql .= "$fieldGlue COALESCE($substrFn(".$fieldTable.'.'.$fieldColumn.",1,10), cast('' as ".$casttype."),'') ".$valueSql;
									} else {
										$fieldSql .= "$fieldGlue COALESCE(".$fieldTable.'.'.$fieldColumn.", cast('' as ".$casttype."),'') ".$valueSql;
									}
									// crmv@186515e
							} else {
								//crmv@176850 crmv@188472 if value is not empty and operator is 'equal', 'not equal' or 'start with' do not use coalesce in order to use indexes
								if (in_array($conditionInfo['operator'], array('e','n','s')) && $conditionInfo['value'] !== '') {
									if ($conditionInfo['operator'] == 'n')
										$fieldSql .= "$fieldGlue (".$fieldTable.'.'.$fieldColumn." ".$valueSql." OR ".$fieldTable.'.'.$fieldColumn." IS NULL)";
									else
										$fieldSql .= "$fieldGlue ".$fieldTable.'.'.$fieldColumn." ".$valueSql;
								} else
								//crmv@176850e crmv@188472e
									$fieldSql .= "$fieldGlue COALESCE(".$fieldTable.'.'.$fieldColumn.", cast('' as ".$casttype.")) ".$valueSql;
							}
						}
						else{
							$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName()." ".$valueSql;
						}
						//crmv@36534e crmv@26565e crmv@56982e crmv@149399e crmv@146461e
					}
				}
				//crmv@16241
				$fieldGlue = $this->getFieldGlue($conditionInfo['operator']);
				//crmv@16241 end
			}
			$fieldSql .= ')';
			$fieldSqlList[$index] = $fieldSql;
		}
		foreach ($this->manyToManyRelatedModuleConditions as $index=>$conditionInfo) {
			$relatedModuleMeta = RelatedModuleMeta::getInstance($this->meta->getTabName(),
					$conditionInfo['relatedModule']);
			$relationInfo = $relatedModuleMeta->getRelationMeta();
			$relatedModule = $this->meta->getTabName();
			$fieldSql = "(".$relationInfo['relationTable'].'.'.
			$relationInfo[$conditionInfo['column']].$conditionInfo['SQLOperator'].
			$conditionInfo['value'].")";
			$fieldSqlList[$index] = $fieldSql;
		}

		$groupSql = $this->makeGroupSqlReplacements($fieldSqlList, $groupSql);
		if($this->conditionInstanceCount > 0) {
			$this->conditionalWhere = $groupSql;
			$sql .= $groupSql;
		}

		// crmv@30014 - condizioni aggiuntive
		$moduleInstance = CRMEntity::getInstance($this->module);
		if ($moduleInstance && method_exists($moduleInstance, 'getQueryExtraWhere')) {
			$sql .= " ".$moduleInstance->getQueryExtraWhere();
		}
		// crmv@30014e

		if ($this->appendWhereClause) $sql .= $this->appendWhereClause;	// crmv@37004 - extra where conditions

		$this->whereClause = $sql;
		return $sql;
	}
	//crmv@modify where end

	// crmv@81761
	public function appendSelectFields($list = array()) {
		if (!is_array($list)) $list = array($list);
		$this->appendSelectFields = array_merge($this->appendSelectFields, $list);
	}
	// crmv@81761e
	
	// crmv@97237
	public function appendRawSelect($selects) {
		if (!is_array($selects)) $selects = array($selects);
		$this->appendRawSelect = array_merge($this->appendRawSelect, $selects);
	}
	// crmv@97237e

	// crmv@37004
	public function appendToWhereClause($sql) {
		$this->appendWhereClause .= " $sql ";
	}

	public function appendToFromClause($sql) {
		$this->appendFromClause .= " $sql ";
	}

	// reset everything but caches from the object
	public function resetAll() {
		$this->reset();
		$this->conditionals = array();
		$this->customViewColumnList = null;
		$this->stdFilterList = null;
		$this->conditionals = array();
		$this->advFilterList = null;
		$this->fields = array();
		$this->referenceModuleMetaInfo = array();
		$this->moduleNameFields = array();
		$this->whereFields = array();
		$this->groupType = self::$AND;
		$this->appendWhereClause = '';
		$this->appendFromClause = '';
		$this->conditionalWhere = null;
		$this->groupInfo = '';
		$this->manyToManyRelatedModuleConditions = array();
		$this->conditionInstanceCount = 0;
		$this->customViewFields = array();
		$this->addedJoins = array(); // crmv@153111
	}
	// crmv@37004e

	/**
	 *
	 * @param mixed $value
	 * @param String $operator
	 * @param WebserviceField $field
	 */
	protected function getConditionValue($value, $operator, $field) {
		global $adb, $current_user; // crmv@25610
		$operator = strtolower($operator);
		$db = PearDatabase::getInstance();
		if(is_string($value)) {
			$valueArray = explode(',' , $value);
			if (count($valueArray) > 1) $valueArray = array_map('trim', $valueArray); // crmv@35839
		} elseif(is_array($value)) {
			$valueArray = $value;
		}else{
			$valueArray = array($value);
		}
		//crmv@17997
		$type = $field->getFieldDataType();
		if ($type == 'picklistmultilanguage' && $value != ''){
			//crmv@49391
			$req = $_REQUEST;
			unset($req['order_by']);
			$is_grid = array_search($field->getFieldName(),$req); 			
			if (strpos($is_grid,'GridFields') !== false) {
				//crmv@168302 code removed
			} else {
				list($valueArray,$operator) = picklistMulti::get_search_values($field->getFieldName(),$valueArray,$operator);
			}
			//crmv@49391e
		}
		elseif($type == 'picklist') { // crmv@154469 - reverted old ticket
			//crmv@70067
			$req = $_REQUEST;
			unset($req['order_by']);
			$is_grid = array_search($field->getFieldName(),$req);
			$grid_flag = false;
			if (strpos($is_grid,'GridFields') !== false) {
				//crmv@168302 code removed
				$grid_flag = true;
			}
			//crmv@70067e
		
			$values_to_add = Array();
			foreach ($valueArray as $val){
				//crmv@52322
				//$val_trans = $this->getReverseTranslate($val,$operator,$field);
				if(!$grid_flag){ //crmv@70067
					$val_trans = $this->searchReverseInAllTranslations($val,$operator,$field);
					if (!empty($val_trans) && $val_trans != $val)
						$valueArray[] = $val_trans;
				} //crmv@70067
				//crmv@52322e
			}
		}
		//crmv@17997 end
		$sql = array();
		//crmv@fix data
		if($operator == 'bw' || strpos($operator,'between') !== false) {
			if($field->getFieldName() == 'birthday' || $operator == 'between_monthday') {
				$sql[] = "BETWEEN ".$db->quote(date('md',strtotime($valueArray[0])))." AND ".$db->quote(date('md',strtotime($valueArray[1])));
			} else {
				$sql[] = "BETWEEN ".$db->quote($valueArray[0])." AND ".
							$db->quote($valueArray[1]);
			}
			return $sql;
		}
		//crmv@fix data end
		foreach ($valueArray as $value) {
			if(!$this->isStringType($field->getFieldDataType())) {
				$value = trim($value);
			}
			if((strtolower(trim($value)) == 'null') ||
					(trim($value) == '' && !$this->isStringType($field->getFieldDataType())) &&
							($operator == 'e' || $operator == 'n')) {
				if($operator == 'e'){
					$sql[] = " = ''"; //crmv@33466
					continue;
				}
				$sql[] = " <> ''"; //crmv@33466
				continue;
			} elseif($field->getFieldDataType() == 'boolean') {
				$value = strtolower($value);
				if ($value == 'yes') {
					$value = 1;
				} elseif($value == 'no') {
					$value = 0;
				}
			} elseif($this->isDateType($field->getFieldDataType())) {
				if($field->getFieldDataType() == 'datetime') {
					$valueList = explode(' ',$value);
					$value = $valueList[0];
				}
				$value = getValidDBInsertDateValue($value);
				if($field->getFieldDataType() == 'datetime') {
					$value .=(' '.$valueList[1]);
					$value = adjustTimezone($value, 0, null, true); // crmv@25610-timezone crmv@50039
				}
			}
			//crmv@fix data
			if($field->getFieldName() == 'birthday' && !$this->isRelativeSearchOperators(
					$operator)) {
				$value = $db->quote(date('md',strtotime($value)));
			} else {
				$value = $db->sql_escape_string($value);
			}
			//crmv@fix data end
			if(trim($value) == '' && ($operator == 's' || $operator == 'ew' || $operator == 'c')
					&& ($this->isStringType($field->getFieldDataType()) ||
					$field->getFieldDataType() == 'picklist' ||
					$field->getFieldDataType() == 'multipicklist' ||
					//crmv@picklistmultilanguage
					$field->getFieldDataType() == 'picklistmultilanguage')) {
					//crmv@picklistmultilanguage end
				$sql[] = "LIKE ''";
				continue;
			}

			if(trim($value) == '' && ($operator == 'k') &&
					$this->isStringType($field->getFieldDataType())) {
				$sql[] = "NOT LIKE ''";
				continue;
			}
			
			//crmv@54149
			if ($adb->isOracle()) {
				if ($field->getFieldDataType() == 'text') $value = strtolower($value);
			}
			//crmv@54149e

			switch($operator) {
				case 'e': $sqlOperator = "=";
					break;
				case 'n': $sqlOperator = "<>";
					break;
				case 's': $sqlOperator = "LIKE";
					$value = "$value%";
					break;
				case 'ew': $sqlOperator = "LIKE";
					$value = "%$value";
					break;
				case 'c': $sqlOperator = "LIKE";
					$value = "%$value%";
					break;
				case 'k': $sqlOperator = "NOT LIKE";
					$value = "%$value%";
					break;
				case 'b':
				case 'l': $sqlOperator = "<";
					break;
				case 'a':
				case 'g': $sqlOperator = ">";
					break;
				case 'm': $sqlOperator = "<=";
					break;
				case 'h': $sqlOperator = ">=";
					break;
			}
			//crmv@25996
			if ($adb->isMssql() || $adb->isOracle()) {
				if ($field->getFieldDataType() == 'text' && $sqlOperator == '=') $sqlOperator = 'LIKE';
			}
			//crmv@25996e
			//crmv@31245
			if( (!$this->isNumericType($field->getFieldDataType()) &&
					($field->getFieldName() != 'birthday' || ($field->getFieldName() == 'birthday' && $this->isRelativeSearchOperators($operator)))
				) || !is_numeric($value)

			){
			// crmv@31245e
				$value = "'$value'";
			}
			$sql[] = "$sqlOperator $value";
		}
		return $sql;
	}

	protected function makeGroupSqlReplacements($fieldSqlList, $groupSql) {
		$pos = 0;
		foreach ($fieldSqlList as $index => $fieldSql) {
			$pos = strpos($groupSql, $this->groupInfoTagL.$index.$this->groupInfoTagR.'');	//crmv@23687
			if($pos !== false) {
				$beforeStr = substr($groupSql,0,$pos);
				$afterStr = substr($groupSql, $pos + strlen($index) + strlen($this->groupInfoTagL) + strlen($this->groupInfoTagR));	//crmv@23687
				$groupSql = $beforeStr.$fieldSql.$afterStr;
			}
		}
		$groupSql = str_replace('OR ()',' ',$groupSql);	//crmv@25266
		$groupSql = str_replace('(AND ','(',$groupSql);
		return $groupSql;
	}

	protected function isRelativeSearchOperators($operator) {
		$nonDaySearchOperators = array('l','g','m','h');
		return in_array($operator, $nonDaySearchOperators);
	}
	protected function isNumericType($type) {
		return ($type == 'integer' || $type == 'double');
	}

	protected function isStringType($type) {
		return ($type == 'string' || $type == 'text' || $type == 'email' || $type == 'picklist');
	}

	protected function isDateType($type) {
		return ($type == 'date' || $type == 'datetime');
	}

	protected function fixDateTimeValue($name, $value, $first = true) {
		$moduleFields = $this->getModuleFields();
		$field = $moduleFields[$name];
		if (is_object($field)) {	//crmv@27037
			$type = $field->getFieldDataType();
			if($type == 'datetime' || in_array($name, array('date_start', 'due_date'))) { // crmv@149399
				if(strrpos($value, ' ') === false) {
					// crmv@149399
					if($first) {
						$value .= ' 00:00:00';
					}else{
						$value .= ' 23:59:59';
					}
					$value = adjustTimezone($value, 0, null, true);
					// crmv@149399e
				}
			}
		}	//crmv@27037
		return $value;
	}

	//crmv@30976
	public function addField($fieldname) {
		$this->fields[] = $fieldname;
	}
	//crmv@30976e
	
	// crmv@153111
	/**
	 * Add a join with another table
	 */
	public function addJoin($table, $columnIdx, $otherTable, $otherIdx, $type = 'LEFT', $alias = '', $otherConditions = '') {
		$key = $table.'_'.$alias;
		$this->addedJoins[$key] = array('type' => $type, 'table' => $table, 'column' => $columnIdx, 'othertable' => $otherTable, 'othercolumn' => $otherIdx, 'alias' => $alias, 'conditions' => $otherConditions);
	}
	
	/**
	 * Add a join with a table for this module
	 */
	public function addModuleJoin($table, $type = 'LEFT', $alias = '', $otherConditions = '') {
		$baseTable = $this->meta->getEntityBaseTable();
		$moduleTableIndexList = $this->meta->getEntityTableIndexList();
		$columnIdx = $moduleTableIndexList[$table];
		$otherTable = $baseTable;
		$otherIdx = $moduleTableIndexList[$baseTable];
		if ($columnIdx && $otherIdx) {
			$key = $table.'_'.$alias;
			$this->addedJoins[$key] = array('type' => $type, 'table' => $table, 'column' => $columnIdx, 'othertable' => $otherTable, 'othercolumn' => $otherIdx, 'alias' => $alias, 'conditions' => $otherConditions);
		}
	}
	// crmv@153111e
	
	// crmv@137410
	// where fields are used to build additional joins
	public function addWhereField($fieldname) {
		$this->whereFields[] = $fieldname;
    }
	// crmv@137410e
	
	// crmv@97237
	
	/**
	 * Remove the field from the list of selected fields
	 */
	public function removeField($fieldname) {
		$k = array_search($fieldname, $this->fields);
		if ($k !== false) {
			unset($this->fields[$k]);
			$this->fields = array_values($this->fields);
			unset($this->fieldAlias[$fieldname]);
		}
	}
	
	/**
	 * Add an alias for a field. It's possible to add multiple alias to the same field. 
	 * In this case, the column will be duplicated several times, with the corresponding alias
	 */
	public function addFieldAlias($fieldname, $alias) {
		$this->fieldAlias[$fieldname][] = $alias;
		$this->fieldAlias[$fieldname] = array_unique($this->fieldAlias[$fieldname]);
	}
	
	/**
	 * Reset all the alias for a given fieldname and use only the provided one
	 */
	public function setFieldAlias($fieldname, $alias) {
		$this->fieldAlias[$fieldname] = array($alias);
	}
	
	/**
	 * Return true if the specified field has at least one alias
	 */
	public function isFieldAliased($fieldname) {
		return !empty($this->fieldAlias[$fieldname]);
	}
	
	/**
	 * Return the aliases defined for the field
	 */
	public function getFieldAliases($fieldname) {
		return $this->fieldAlias[$fieldname];
	}
	
	/**
	 * Remove an alias, or all of them, for the specified field
	 */
	public function removeFieldAlias($fieldname, $alias = null) {
		if ($alias) {
			// remove a specific alias
			if (isset($this->fieldAlias[$fieldname])) {
				$k = array_search($alias, $this->fieldAlias[$fieldname]);
				if ($k !== false) {
					unset($this->fieldAlias[$fieldname][$k]);
				}
			}
		} else {
			// remove all alias
			unset($this->fieldAlias[$fieldname]);
		}
	}
	// crmv@97237e

	public function addCondition($fieldname,$value,$operator,$glue= null,$newGroup = false, $newGroupType = null, $usePermissions = true) { // crmv@133387
		//crmv@15351
		$field = $this->getWSField($fieldname, $usePermissions); // crmv@133387
		if(empty($field)) {
			// not accessible or invalid field
			return;
		}
		//crmv@15351e
		//crmv@17997
		if($this->module == 'Calendar' && ($fieldname == 'taskstatus' || $fieldname == 'eventstatus')){
			$this->startGroup('');
			$conditionNumber = $this->conditionInstanceCount++;
			$this->groupInfo .= $this->groupInfoTagL.$conditionNumber.$this->groupInfoTagR.' ';	//crmv@23687
			$this->whereFields[] = $fieldname;
			$this->reset();
			$this->conditionals[$conditionNumber] = $this->getConditionalArray($fieldname, $value, $operator, $usePermissions); // crmv@133387
			$this->addConditionGlue($this->getFieldGlue($operator));
			$conditionNumber = $this->conditionInstanceCount++;
			$this->groupInfo .= $this->groupInfoTagL.$conditionNumber.$this->groupInfoTagR.' ';	//crmv@23687
			$this->whereFields[] = $fieldname;
			$this->reset();
			$fieldname_add = ($fieldname=='taskstatus')?'eventstatus':'taskstatus';
			$this->conditionals[$conditionNumber] = $this->getConditionalArray($fieldname_add, $value, $operator, $usePermissions); // crmv@133387
			$this->endGroup();
		} else {
			$conditionNumber = $this->conditionInstanceCount++;
			$this->groupInfo .= $this->groupInfoTagL.$conditionNumber.$this->groupInfoTagR.' ';	//crmv@23687
			$this->whereFields[] = $fieldname;
			$this->reset();
			//crmv@128159
			$moduleFields = $this->getModuleFields();
			$field = $moduleFields[$fieldname];
			if ($field) {
				$uitype = $field->getUIType();
				if (SDK::isUitype($uitype)) {
					$sdk_file = SDK::getUitypeFile('php','querygeneratorsearch',$uitype);
					if ($sdk_file != '') {
						include($sdk_file);
					}
				}
			}
			//crmv@128159e
			$this->conditionals[$conditionNumber] = $this->getConditionalArray($fieldname, $value, $operator, $usePermissions); // crmv@133387
		}
		//crmv@17997 end
		return true; // crmv@92808
	}
	
	// crmv@141878
	/**
	 * Add a raw condition to the query
	 */
	public function addRawCondition($sql) {
		$conditionNumber = $this->conditionInstanceCount++;
		$this->groupInfo .= $this->groupInfoTagL.$conditionNumber.$this->groupInfoTagR.' ';	//crmv@23687
		$this->reset();
		$this->conditionals[$conditionNumber] = array('name' => 'raw_condition', 'sql' => $sql);
	}
	// crmv@141878e

	public function addRelatedModuleCondition($relatedModule,$column, $value, $SQLOperator) {
		$conditionNumber = $this->conditionInstanceCount++;
		$this->groupInfo .= $this->groupInfoTagL.$conditionNumber.$this->groupInfoTagR.' ';	//crmv@23687
		$this->manyToManyRelatedModuleConditions[$conditionNumber] = array('relatedModule'=>
			$relatedModule,'column'=>$column,'value'=>$value,'SQLOperator'=>$SQLOperator);
	}

	// crmv@133387
	protected function getConditionalArray($fieldname,$value,$operator,$usePermissions = true) {
		return array('name'=>$fieldname,'value'=>$value,'operator'=>$operator, 'check_permissions' => $usePermissions);
	}
	// crmv@133387e

	// crmv@37004
	public function startGroup($groupType) {
		$this->groupInfo .= "$groupType (";
	}

	public function endGroup() {
		$this->groupInfo .= ')';
	}
	// crmv@37004e

	public function addConditionGlue($glue) {
		$this->groupInfo .= "$glue ";
	}

	public function addUserSearchConditions($input) {
		//crmv@48693
		$focus = CRMEntity::getInstance($this->module);
		if(method_exists($focus, 'addUserSearchConditions')) {
			$return = $focus->addUserSearchConditions($input,$this);
			if ($return != 'continue') {
				return $return;
			}
		}
		//crmv@48693e
		global $log,$default_charset;
		if ($input['searchtype'] == 'advance') {
			if(empty($input['search_cnt'])) {
				return ;
			}
			$noOfConditions = vtlib_purify($input['search_cnt']);
			if($input['matchtype'] == 'all') {
				$matchType = self::$AND;
			} else {
				$matchType = self::$OR;
			}
			if($this->conditionInstanceCount > 0) {
				$this->startGroup(self::$AND);
			} else {
				$this->startGroup('');
			}
			for($i=0; $i<$noOfConditions; $i++) {
				$fieldInfo = 'Fields'.$i;
				$condition = 'Condition'.$i;
				$value = 'Srch_value'.$i;

				list($fieldName,$typeOfData) = explode("::::",str_replace('\'','',
						stripslashes($input[$fieldInfo])));
				$moduleFields = $this->getModuleFields();
				// crmv@157122
				// if the field contains a dot, it's a table+column, so find the fieldname
				if (strpos($fieldName, '.') !== false) {
					list($tableName, $columnName) = explode('.', $fieldName);
					foreach ($moduleFields as $field) {
						if ($field->getTableName() == $tableName && $field->getColumnName() == $columnName) {
							$fieldName = $field->getFieldName();
							break;
						}
					}
				}
				// crmv@157122e
				$field = $moduleFields[$fieldName];
				if (!$field)
					continue;
				$type = $field->getFieldDataType();
				//crmv@23687
				if(($i-1) >= 0 && !empty($this->whereFields)) {
					$this->addConditionGlue($matchType);
				}
				//crmv@23687e
				$operator = str_replace('\'','',stripslashes($input[$condition]));
				$searchValue = $input[$value];
				$searchValue = urldecode($searchValue); //crmv@60585
				$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset, // crmv@167234
						$searchValue) : $searchValue;
				$this->addCondition($fieldName, $searchValue, $operator);
			}
			$this->endGroup();
		} else {//crmv@208472
			// crmv@31245 - ricerca base su tutti i campi della listview
			if(isset($input['search_fields']) && is_array($input['search_fields']) && count($input['search_fields']) > 0) {
				$fieldNames=vtlib_purify($input['search_fields']);
			} elseif (isset($input['search_field']) && $input['search_field'] != '') {
				$fieldNames = array(vtlib_purify($input['search_field']));
			} else {
				return ;
			}
			if(isset($input['search_text']) && $input['search_text']!="") {
				// search other characters like "|, ?, ?" by jagi
				$value = urldecode($input['search_text']);
				$stringConvert = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$value) : $value; // crmv@167234
				if (!$this->isStringType($type)) {
					$value = trim($stringConvert);
				}
			}
			if ($value != '' && $value != getTranslatedString('LBL_SEARCH_TITLE').getTranslatedString($this->module,$this->module)) {
				if($this->conditionInstanceCount > 0) {
					$this->startGroup(self::$AND);
				} else {
					$this->startGroup('');
				}
				$moduleFields = $this->getModuleFields();
				$i = 0;
				foreach ($fieldNames as $fieldName => $fieldLabel) {
					$field = $moduleFields[$fieldName];
					if (!$field) continue;
					$type = $field->getFieldDataType();
	
					if ($type == 'boolean') continue; // crmv@190151
					
					//crmv@23687
					if(($i-1) >= 0 && !empty($this->whereFields)) {
						$this->addConditionGlue(self::$OR);
					}
					//crmv@23687e
	
					if(!empty($input['operator'])) {
						$operator = $input['operator'];
					} elseif(trim(strtolower($value)) == 'null'){
						$operator = 'e';
					} else {
						$operator = 'c';
					}
	
					$this->addCondition($fieldName, $value, $operator);
					++$i;
				}
				// crmv@31245
				$this->endGroup();
			}
		}
		//crmv@3084m crmv@144880
		if(!empty($input['GridSearchCnt'])) {
			$noOfConditions = vtlib_purify($input['GridSearchCnt']);
			$picklist_uitypes = array(15,16,1015,54,56,27,33,300); // crmv@100920
			$matchType = self::$AND;
			$gridFields = array();
			for($i=0; $i<$noOfConditions; $i++) {
				$fieldInfo = 'GridFields'.$i;
				$fieldName = $input[$fieldInfo];
				$moduleFields = $this->getModuleFields();
				$accessibleFieldList = array_keys($moduleFields);
				$fields = $this->getFields();
				$listViewFields = array_intersect($fields, $accessibleFieldList);
				if (in_array($fieldName,$listViewFields)) {
					$gridFields[$i] = $fieldName;
				}
			}
			if (!empty($gridFields)) {
				if($this->conditionInstanceCount > 0) {
					$this->startGroup(self::$AND);
				} else {
					$this->startGroup('');
				}
				foreach($gridFields as $i => $fieldName) {
					$fieldInfo = 'GridFields'.$i;
					$condition = 'GridCondition'.$i;
					$value = 'GridSrch_value'.$i;
					
					$field = $moduleFields[$fieldName];
					$uitype = $field->getUIType();
					$type = $field->getFieldDataType(); //crmv@168302
					//crmv@23687
					if(($i-1) >= 0 && !empty($this->whereFields)) {
						$this->addConditionGlue($matchType);
					}
					//crmv@23687e
					$operator = str_replace('\'','',stripslashes($input[$condition]));
					if (in_array($type,array('picklist','picklistmultilanguage'))) $operator = 'e'; //crmv@168302
					$searchValue = urldecode($input[$value]);
					$searchValue = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$searchValue) : $searchValue; // crmv@167234
					if (in_array($uitype,$picklist_uitypes) && strpos($searchValue,'|##|') !== false) {
						$searchValues = explode('|##|',$searchValue);
						if (!empty($searchValues)) {
							$this->startGroup('');
							$nr = count($searchValues);
							for($j=0; $j<$nr; $j++) {
								if(($j-1) >= 0) {
									$this->addConditionGlue(self::$OR);
								}
								$this->addCondition($fieldName, $searchValues[$j], $operator);
							}
							$this->endGroup();
						}
					} else {
						$this->addCondition($fieldName, $searchValue, $operator);
					}
				}
				$this->endGroup();
			}
		}
		//crmv@3084me crmv@144880e
	}

	//crmv@208472
	//crmv@16241
	protected function getFieldGlue($operator) {
		if (in_array($operator,Array('k','n')))
			return " ".self::$AND;
		return " ".self::$OR;

	}
	//crmv@16241 end
	//crmv@36534
	public static function getCastValue($field){
		global $adb;
		$type = $field->getFieldDataType();
		static $cachedTableFields = array();
		if(empty($cachedTableFields[$field->getTableName()])){
			$cachedTableFields[$field->getTableName()] = array_change_key_case($adb->database->MetaColumns($field->getTableName()),CASE_LOWER);
		}
		switch ($adb->datadict->MetaType($cachedTableFields[$field->getTableName()][$field->getColumnName()])){
			case 'I':
			case 'N':
				if ($field->getFieldType() == 'V'){
					$datatype = 'char';
				}
				else{
					if ($adb->isMySQL()){
						$datatype = 'unsigned';
					}
					else{
						$datatype = 'int';
					}
				}
				break;
			case 'D':
			case 'DT':
			case 'T':
				$datatype = 'date';
				break;
			case 'XL':
				if ($adb->isMySQL()){
					$datatype = 'char';
				} else {
					$datatype = false; //do not cast clobs!
				}
				break;
			default:
				$datatype = 'char';
				break;
		}
		/*
		$datatype = $adb->datadict->ActualType($datadict_type);
		if (strpos($datatype,"(")!==false){
			$datatype = explode(",",$datatype);
			$datatype = $datatype[0];
		}
		*/
		return $datatype;
	}
	//crmv@36534 e
	//crmv@42329
	public function getconditionInstanceCount(){
		return $this->conditionInstanceCount;
	}
	//crmv@42329e
}
?>