<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@127526 */

require_once('include/QueryGenerator/QueryGenerator.php');

class ProductsBLockMeta {
	
	public function getEntityBaseTable() {
		global $table_prefix;
		return $table_prefix.'_inventoryproductrel';
	}
	
	public function getIdColumn() {
		return 'lineitem_id';
	}
}

class NewsletterStatsMeta {
	
	public function getEntityBaseTable() {
		return 'tbl_s_newsletter_queue';
	}
	
	public function getIdColumn() {
		return 'queueid';
	}
}

class NewsletterTLinksMeta {
	
	public function getEntityBaseTable() {
		return 'tbl_s_newsletter_tl';
	}
	
	public function getIdColumn() {
		return 'trackid';
	}
}

// crmv@150533
class PriceBooksPricesMeta {
	
	public function getEntityBaseTable() {
		global $table_prefix;
		return $table_prefix.'_pricebookproductrel';
	}
	
	public function getIdColumn() {
		return 'pbrelid';
	}
}
// crmv@150533e

/**
 * Class to generate query for the fake NewsletterStats module
 */
class FakeQueryGenerator extends QueryGenerator {
	
	public function __construct($module, $user, $reportrun, $reports) { // crmv@113569 - rimosso reference parametro 2 e 3. PHP 5.6 bug ?
		$this->module = $module;
		$this->reportrun = $reportrun;
		$this->reports = $reports;
		
		$this->fields = array();
		$this->fieldAlias = array();
		$this->referenceModuleMetaInfo = array();
		$this->moduleNameFields = array();
		$this->whereFields = array();
		$this->appendSelectFields = array();
		$this->appendRawSelect = array();
		$this->appendWhereClause = '';
		$this->appendFromClause = '';
		
		switch ($this->module) {
			case 'ProductsBlock': // crmv@135260
				$meta = new ProductsBLockMeta();
				break;
			case 'NewsletterStats':
				$meta = new NewsletterStatsMeta();
				break;
			case 'NewsletterTLinks':
				$meta = new NewsletterTLinksMeta();
				break;
			// crmv@150533
			case 'PriceBooksPrices':
				$meta = new PriceBooksPricesMeta();
				break;
			// crmv@150533e
			default:
				throw new Exception("Unknown fake module $module");
		}
		
		$this->meta = $meta;
		$this->referenceModuleMetaInfo[$this->module] = $meta;
	}
	
	public function getModuleFields() {
		return FakeModules::getWSFields($this->module);
	}
	
	// used when searching in picklist fields
	public function getReverseTranslate($value,$operator,&$field=null,$firstMatch=true){
		global $current_language;
		
		$fields = FakeModules::getFields($this->module);
		foreach ($fields as $fieldname => $fld) {
			if (is_array($fld['allowed_values'])) {
				foreach ($fld['allowed_values'] as $val=>$trans) {
					if (stripos($trans, $value) !== false) {
						return $val;
					}
				}
			}
		}

		return $value;
	}
	
	public function getQuery($onlyFields = false) {
		global $table_prefix;
		
		if(empty($this->query)) {
			$allFields = array_merge($this->whereFields,$this->fields);
			$allFields = array_unique($allFields);
			
			$query = 'SELECT ';
			$query .= $this->getSelectClauseColumnSQL();
			$query .= $this->getFromClause();
			$query .= $this->getWhereClause();
			$query = $this->cleanUpQuery($query); // crmv@49398

			$this->query = $query;
		}
		
		return $this->query;
	}
	
	public function getSelectClauseColumnSQL($onlyfields=false){
		$columns = array();
		foreach ($this->fields as $field) {
			$sql = $this->getSQLColumn($field);
			if (!in_array($sql,$columns)){
				$columns[] = $sql;
			}
		}
		if (is_array($this->appendSelectFields) && count($this->appendSelectFields) > 0) {
			foreach ($this->appendSelectFields as $field) {
				$sql = $this->getSQLColumn($field);
				if (!in_array($sql,$columns)){
					$columns[] = $sql;
				}
			}
		}
		if (is_array($this->appendRawSelect) && count($this->appendRawSelect) > 0) {
			foreach ($this->appendRawSelect as $rsel) {
				$columns[] = $rsel;
			}
		}
		$this->columns = implode(',',$columns);
		
		return $this->columns;
	}
	
	public function getFromClause() {
		global $adb,$table_prefix,$current_user,$current_language;  //crmv@74933
		
		if(!empty($this->fromClause)) {
			return $this->fromClause;
		}
		
		$baseTable = $this->meta->getEntityBaseTable();
		$baseTableAlias = $baseTable;
		
		$tableList = array();
		
		if ($this->module == 'NewsletterTLinks') {
			$tableList[] = array(
				'type' => 'left',
				'table' => 'tbl_s_newsletter_links',
				'alias' => 'tbl_s_newsletter_links',
				'condition' => 'tbl_s_newsletter_links.linkid = tbl_s_newsletter_tl.linkurlid'
			);
		}
		
		// this is not needed now
		/*$allfields = array_merge($this->whereFields, $this->fields);
		$allfields = array_unique($allfields);
		foreach ($allfields as $fieldname) {
			$finfo = $this->reports->getFieldInfoByName($this->module, $fieldname);
			
			if ($finfo['wstype'] == 'reference') {
				$moduleList = $finfo['relmodules'];
				$crmalias = 'crmentityRel'.$finfo['fieldid'];
				$tableList[$crmalias] = array(
					'type' => 'left',
					'table' => $table_prefix.'_crmentity',
					'alias' => $crmalias,
					'condition' => "$crmalias.crmid = $baseTableAlias.{$finfo['columnname']} AND $crmalias.deleted = 0",
				);
				foreach ($moduleList as $relmod) {
					$modmeta = $this->getMeta($relmod);
					$modtable = $modmeta->getEntityBaseTable();
					$tableIndexList = $modmeta->getEntityTableIndexList();
					$modidx = $tableIndexList[$modtable];
					
					$alias = substr(strtolower($relmod).'Rel'.$this->module.$finfo['fieldid'], 0, 29);
					$tableList[$alias] = array(
						'type' => 'left',
						'table' => $modtable,
						'alias' => $alias,
						'condition' => "$alias.$modidx = $crmalias.crmid",
					);
				}
			}
		}*/
		
		$sql = " FROM $baseTable ";
		
		unset($tableList[$baseTable]);
		foreach ($tableList as $joininfo) {
			$join = ($joininfo['type'] == 'left' ? 'LEFT JOIN' : 'INNER JOIN');
			$sql .= " $join {$joininfo['table']} {$joininfo['alias']} ON {$joininfo['condition']}";
		}
		
		// here are all the joins!!
		if ($this->appendFromClause) $sql .= $this->appendFromClause;

		$this->fromClause = $sql;
		
		return $sql;		
	}
	
	// simplified version of the getwhereclause
	public function getWhereClause() {
		
		$moduleFieldList = $this->getModuleFields();
		$fieldSqlList = array();
		$sql = '';
		
		if (!is_array($this->conditionals)) return $sql;
		
		foreach ($this->conditionals as $index=>$conditionInfo) {
			$fieldName = $conditionInfo['name'];
			$field = $moduleFieldList[$fieldName];
			$dataType = $field->getFieldDataType();
			// TODO: only supports datetime filters
			if ($dataType == 'date' || $dataType == 'datetime') {
				$fieldSql = '(';
				$fieldGlue = '';
				$valueSqlList = $this->getConditionValue($conditionInfo['value'], $conditionInfo['operator'], $field);
				foreach ($valueSqlList as $valueSql) {
					$casttype = $this->getCastValue($field);
					if ($casttype !==false){
						if (strtoupper($casttype) == 'DATE')
							$fieldSql .= "$fieldGlue COALESCE(".$field->getTableName().'.'.$field->getColumnName().", cast('' as ".$casttype."),'') ".$valueSql;
						//crmv@176850 if value is not empty and operator is 'equal' or 'start with' do not use coalesce in order to use indexes
						else {
							if (in_array($conditionInfo['operator'], array('e','s')) && $conditionInfo['value'] !== '')
								$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName()." ".$valueSql;
							else
								$fieldSql .= "$fieldGlue COALESCE(".$field->getTableName().'.'.$field->getColumnName().", cast('' as ".$casttype.")) ".$valueSql;
						}
						//crmv@176850e
					} else{
						$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName()." ".$valueSql;
					}
					$fieldGlue = $this->getFieldGlue($conditionInfo['operator']);
				}
				$fieldSql .= ')';
				$fieldSqlList[] = $fieldSql;
			}
		}
		// TODO: no group handling
		if (count($fieldSqlList) > 0) {
			$sql .= ' WHERE ('.implode(' AND ', $fieldSqlList).')';
		}
		
		if ($this->appendWhereClause) $sql .= $this->appendWhereClause;	// crmv@37004 - extra where conditions

		$this->whereClause = $sql;

		return $sql;
	}
	
	public function getSQLColumn($name,$onlyfields = false, $usePermissions = true) { // crmv@146653
		global $table_prefix;
		
		$fieldInfo = FakeModules::getFieldInfo($name, $this->module);
		$aliases = $this->fieldAlias[$name];
		
		$baseTable = $fieldInfo['tablename'] ?: $this->meta->getEntityBaseTable();
		$column = $fieldInfo['columnname'] ?: $name;
		$sqlcolumn = $baseTable.'.'.$column;
		
		if (!empty($aliases)) {
			$cols = array();
			foreach ($aliases as $alias) {
				$cols[] = $sqlcolumn . " AS \"$alias\"";
			}
			$sqlcolumn = implode(',', $cols);
		}
		return $sqlcolumn;
	}
}
