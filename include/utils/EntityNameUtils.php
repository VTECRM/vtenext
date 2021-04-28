<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@144125 */

/**
 * Handle the cache table for entity names
 */
class EntityNameUtils extends SDKExtendableUniqueClass {

	public $table;
	public $display_table;
	
	public $excludedModules = array('Fax', 'Sms'); // crmv@164120
	public $nameSeparator = ' ';
	
	protected $entityFieldsCache = array();
	
	public function __construct() {
		global $table_prefix;
		
		$this->table = $table_prefix.'_entityname';
		$this->display_table = $table_prefix.'_entity_displayname';
	}
	
	public function saveCachedName($module, $id, $name = null) {
		global $adb, $table_prefix;
		
		if (in_array($module, $this->excludedModules)) return;
		
		if (is_null($name)) {
			// TODO: this can be optimized by getting the column fields
			$name = $this->getEntityName($module, array($id), true, false);
			if ($name === false || empty($name)) return;
		}
		$now = date('Y-m-d H:i:s');
		
		if ($adb->isMysql()) {
			$sql = "REPLACE INTO {$this->display_table} (crmid, setype, displayname, lastupdate) VALUES (?,?,?,?)";
			$params = array($id, $module, $name, $now);
		} else {
			$oldName = $this->getCachedName($module, $id);
			if ($oldName === false) {
				$sql = "INSERT INTO {$this->display_table} (crmid, setype, displayname, lastupdate) VALUES (?,?,?,?)";
				$params = array($id, $module, $name, $now);
			} else {
				$sql = "UPDATE {$this->display_table} SET displayname = ?, lastupdate = ? WHERE crmid = ?";
				$params = array($name, $now, $id);
			}
		}
		
		$adb->pquery($sql, $params);
	}
	
	public function removeCachedNames($module, $ids) {
		global $adb, $table_prefix;
		
		if (!is_array($ids)) $ids = array($ids);
		
		if (count($ids) > 0) {
			$chunks = array_chunk($ids, 100);
			foreach ($chunks as $idlist) {
				$adb->pquery("DELETE FROM {$this->display_table} WHERE crmid IN (".generateQuestionMarks($idlist).")", $idlist);
			}
		}
	}
	
	/**
	 * Return the name in the cache table
	 */
	public function getCachedName($module, $id) {
		global $adb, $table_prefix;
		
		$name = false;
		$res = $adb->pquery("SELECT displayname FROM {$this->display_table} WHERE crmid = ? AND setype = ?", array($id, $module));
		if ($res && $adb->num_rows($res) > 0) {
			$name = $adb->query_result($res, 0, 'displayname');
		}
		return $name;
	}
	
	/**
	 * Return multiple names for several ids
	 */
	public function getCachedNames($module, $ids) {
		global $adb, $table_prefix;
		
		$names = array();
		$res = $adb->pquery("SELECT crmid, displayname FROM {$this->display_table} WHERE crmid IN (".generateQuestionMarks($ids).") AND setype = ?", array($ids, $module));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res)) {
				$names[$row['crmid']] = $row['displayname'];
			}
		}
		
		// crmv@154947
		// fix for the stupid calendar module!!
		if (empty($names) && $module == 'Calendar') return $this->getCachedNames('Events', $ids);
		// crmv@154947e
		
		return $names;
	}
		
	/**
	 *	Get the "name" for the specified list of records (or just one for 1 record)
	 **/
	// crmv@39110
	public function getEntityName($module, $ids_list, $single_result = false, $useCache = true) { // crmv@44187
		global $log, $adb;
		
		$log->debug("Entering getEntityName(".$module.") method ...");
		
		if ($module == '' || in_array($module, $this->excludedModules)) return false;
		if (!is_array($ids_list)) $ids_list = array($ids_list); // crmv@167234
		$ids_list = array_filter(array_map('intval', $ids_list)); // crmv@172538
		if (count($ids_list) <= 0) return array();
		
		if ($module == 'Activity') $module = 'Calendar';
		
		if ($useCache) {
			$names = $this->getCachedNames($module, $ids_list);
			if ($single_result) $names = array_shift($names);
			
			// crmv@154947
			if (!empty($names)) {
				$log->debug("Exiting getEntityName method ...");
				return $names;
			} else {
				$cacheMiss = true;
			}
			// crmv@154947e
		}
		
		$entity_info = array();
		$efields = $this->getFieldNames($module, true);
		$query = $this->getEntityNameQuery($module, true, $ids_list);
		if($query == '') return null;//crmv@204903
		$result = $adb->query($query);
		while ($row = $adb->FetchByAssoc($result, -1, false)) { // crmv@155482
			$entity_id = $row['crmid'];
			$value = $row['entityname'];
			
			// special transformations should be translated,
			// but this is supportend only when there is a single field
			if (!is_array($efields['uitype'])) {
				// crmv@39106
				if ($efields['uitype'] == 1015 && class_exists('PickListMulti')) {
					$value = PickListMulti::getTranslatedPicklist($value,$efields['fieldname']);
				// crmv@39106e
				//crmv@180237
				} elseif ($efields['uitype'] == 5) {
					$value = getDisplayDate($value);
				// crmv@180237e
				}
			}
			if ($single_result) {
				$entity_info = $value; // crmv@44187
				break;
			}
			$entity_info[$entity_id] = $value;
		}
		
		// crmv@154947
		if ($cacheMiss) {
			if (is_array($entity_info)) {
				foreach ($entity_info as $id => $name) {
					$this->saveCachedName($module, $id, $name);
				}
			} else {
				$this->saveCachedName($module, $entity_id, $entity_info);
			}
		}
		// crmv@154947e
		
		if (is_array($entity_info) && count($entity_info) == 0 && $single_result) $entity_info = ''; // crmv@155585
			
		$log->debug("Exiting getEntityName method ...");
		return $entity_info;
	}
	// crmv@39110e
	
	// crmv@185356
	/**
	 * Search entities by name, optionally restricting the search to the passed modules
	 *
	 * @param string $name The entity name to search (exact match, collation as per DB)
	 * @param string/array $modules List of modules (or single module) to search in
	 * @param string $returnType What/How to return results:
	 *   'single':	return just the first matching crmid, or false if not found
	 *   'all': 	return all the matching crmids as a flat array, or empty array if not found
	 *   'grouped':	return all the matching crmids, grouped by module, or empty array if not found
	 * @param bool $searchDeleted If true (default) search also in deleted entries
	 *
	 * @return false/int/array according to the $returnType parameter
	 */
	public function searchEntity($name, $modules = array(), $returnType = 'single', $searchDeleted = true) { // crmv@192957
		global $adb, $table_prefix;
		
		if (!is_array($modules)) $modules = array($modules);
		$modules = array_filter($modules);
		
		// crmv@192957
		$joins = '';
		if (!$searchDeleted) {
			$joins = "INNER JOIN {$table_prefix}_crmentity c ON c.crmid = dt.crmid AND c.deleted = 0";
		}
		
		$sql = "SELECT dt.crmid, dt.setype as module FROM {$this->display_table} dt $joins WHERE dt.displayname = ?";
		$params = array($name);
		// crmv@192957e
		
		if (count($modules) > 0) {
			$sql .= " AND dt.setype IN (".generateQuestionMarks($modules).")";
			$params[] = $modules;
		}
		
		
		if ($returnType == 'single') {
			$res = $adb->limitpQuery($sql, 0, 1, $params);
			$return = false;
		} else {
			$res = $adb->pquery($sql, $params);
			$return = array();
		}
		
		if ($res && $adb->num_rows($res) > 0) {
			if ($returnType == 'single') {
				$return = intval($adb->query_result_no_html($res, 0, 'crmid'));
			} else {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$id = intval($row['crmid']);
					if ($returnType == 'grouped') {
						$return[$row['module']] = $id;
					} else {
						$return[] = $id;
					}
				}
			}
		}
		
		return $return;
	}
	// crmv@185356e
	
	/**
	 * this function returns the entity field name for a given module; for e.g. for Contacts module it return concat(lastname, ' ', firstname)
	 * @param string $module - the module name
	 * @return string $fieldsname - the entity field name for the module
	 */
	public function getEntityField($module, $useProfile = false){
		$fnames = $this->getFieldNames($module, $useProfile);
		
		$table = $fnames['tablename'];
		$fieldsname = $this->columns2Sql($fnames['columnname'], $table);
		
		return array("tablename"=>$table, "fieldname"=>$fieldsname, "entityidfield" => $fnames['entityidfield']);
	}
	
	/**
	 * this function returns the entity information for a given module; for e.g. for Contacts module
	 * it returns the information of tablename, modulename, fieldsname and id gets from vte_entityname
	 * @param string $module - the module name
	 * @return array $data - the entity information for the module
	*/
	public function getFieldNames($module, $useProfile = false) {
		global $adb, $table_prefix, $current_user;
		
		if (empty($module)) return array();
		
		$key = $module.'_'.intval($useProfile);
		
		if (!isset($this->entityFieldsCache[$key])) {
		
			// get field names
			
			if ($useProfile && $current_user && $current_user->id > 0) {
				// get profile (only the first one)
				require('user_privileges/requireUserPrivileges.php'); // crmv@39110
				$userProfile = $current_user_profiles[0];
				// override default field with the profile one
				$query = "SELECT COALESCE(p2en.fieldname, en.fieldname) AS fieldname, en.tablename, en.entityidfield
					FROM {$table_prefix}_entityname en
					LEFT JOIN {$table_prefix}_profile2entityname p2en ON p2en.profileid = ? AND p2en.tabid = en.tabid
					WHERE en.modulename = ?";
				$params = array($userProfile, $module);
			} else {
				$query = "SELECT fieldname,tablename,entityidfield FROM {$table_prefix}_entityname WHERE modulename = ?";
				$params = array($module);
			}
			
			$result = $adb->pquery($query, $params);
			$row = $adb->FetchByAssoc($result, -1, false);
			$fieldsName = $row['fieldname'];
			if (strpos($fieldsName, ',') !== false) {
				$fieldsName = explode(',', $fieldsName);
			}
			
			// get column names and uitypes
			$columns = array();
			$uitypes = array();
			$res2 = $adb->pquery(
				"SELECT f.columnname, f.fieldname, f.uitype FROM {$table_prefix}_field f
				INNER JOIN {$table_prefix}_tab t ON t.tabid = f.tabid
				WHERE t.name = ? AND f.fieldname IN (".generateQuestionMArks($fieldsName).")", array($module, $fieldsName));
			while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
				$columns[$row2['fieldname']] = $row2['columnname'];
				$uitypes[$row2['fieldname']] = $row2['uitype'];
			}
			// order 
			if (is_array($fieldsName)) {
				$columns = array_merge(array_flip($fieldsName), $columns);
				$uitypes = array_merge(array_flip($fieldsName), $uitypes);
			}
			$columns = array_values($columns);
			$uitypes = array_values($uitypes);
			if (count($columns) == 1) $columns = $columns[0];
			if (count($uitypes) == 1) $uitypes = $uitypes[0];

			$this->entityFieldsCache[$key] = array("tablename" => $row['tablename'], "modulename" => $module, "fieldname" => $fieldsName, "columnname" => $columns, "entityidfield" => $row['entityidfield'], 'uitype' => $uitypes);
		}
		
		return $this->entityFieldsCache[$key];
	}
	
	/**
	 * @deprecated
	 */
	public function getSqlForNameInDisplayFormat($input, $module, $glue = ' ') {
		global $adb;
		
		$entity_field_info = $this->getFieldNames($module);
		$tableName = $entity_field_info['tablename'];
		$fieldsName = $entity_field_info['columnname'];
		if(is_array($fieldsName)) {
			foreach($fieldsName as $key => $value) {
				$formattedNameList[] = $input[$value];
			}
			$formattedNameListString = implode(",'" . $glue . "',", $formattedNameList);
		} else {
			$formattedNameListString = $input[$fieldsName];
		}
		$formattedNameList = explode(",",$formattedNameListString);
		$sqlString = $adb->sql_concat($formattedNameList);
		
		return $sqlString;
	}
	
	/**
	 * Change the entity field for the specified module.
	 * If more than one field is specified, they must be on the same table
	 */
	public function changeEntityField($module, $fieldnames) {
		global $adb, $table_prefix;
		
		if (!is_array($fieldnames)) $fieldnames = array($fieldnames);
		
		// first, get some info about the field
		$res = $adb->pquery(
			"SELECT f.tablename, f.tabid FROM {$table_prefix}_field f
			INNER JOIN {$table_prefix}_tab t ON t.tabid = f.tabid
			WHERE t.name = ? AND f.fieldname IN (".generateQuestionMarks($fieldnames).")",
			array($module, $fieldnames)
		);
		$row = $adb->FetchByAssoc($res, -1, false);
		if (empty($row)) return false;
		
		$tabid = $row['tabid'];
		$tablename = $row['tablename'];
		
		$focus = CRMEntity::getInstance($module);
		$index = $focus->tab_name_index[$tablename];

		$adb->pquery(
			"UPDATE {$this->table} SET tablename = ?, fieldname = ?, entityidfield = ?, entityidcolumn = ? WHERE tabid = ?",
			array($tablename, implode(',',$fieldnames), $index, $index, $tabid)
		);
		
		unset($this->entityFieldsCache[$module.'_0']);
		unset($this->entityFieldsCache[$module.'_1']);
	
		return $this->rebuildForModule($module);
	}
	
	/**
	 * Rebuild the names for all modules
	 */
	public function rebuildForAll() {
		global $adb, $table_prefix;
		
		$res = $adb->pquery(
			"SELECT t.name FROM {$table_prefix}_tab t 
			WHERE t.isentitytype = 1 AND t.name NOT IN (".generateQuestionMarks($this->excludedModules).")
			ORDER BY t.tabid",
			$this->excludedModules
		);
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$this->rebuildForModule($row['name']);
		}
	}
	
	/**
	 * Rebuild the cache for the specified module
	 */
	public function rebuildForModule($module, $ids = array()) {
		global $adb, $table_prefix;
		
		if ($module == '' || in_array($module, $this->excludedModules)) return false;
		
		$query = $this->getEntityNameQuery($module, false, $ids);
		if (!$query) return false;
		
		if ($adb->isMysql()) {
			$query = "REPLACE INTO {$this->display_table} $query";
		} else {
			// fallback on delete + insert
			if (count($ids) > 0) {
				$this->removeCachedNames($module, $ids);
			} else {
				$adb->pquery("DELETE FROM {$this->display_table} WHERE setype = ?", array($module));
			}
			$query = "INSERT INTO {$this->display_table} $query";
		}
		
		$adb->query($query);
		
		return true;
	}
	
	/**
	 * Rebuild the cache for several records of the specified module
	 */
	public function rebuildForRecords($module, $ids) {
		return $this->rebuildForModule($module, $ids);
	}
	
	/**
	 * Remove the names for deleted (gone from crmentity, not in the bin) records
	 */
	public function removeDeletedEntries() {
		global $adb, $table_prefix;
		
		$adb->query("DELETE d FROM {$this->display_table} d LEFT JOIN {$table_prefix}_crmentity c ON c.crmid = d.crmid WHERE c.crmid IS NULL");
	}
	
	/**
	 * Return the query to extract the entity name for the module
	 */
	public function getEntityNameQuery($module, $useProfile = false, $idlist = array()) {
		global $adb, $table_prefix;
		
		$efields = $this->getFieldNames($module, $useProfile);
		$table = $efields['tablename'];
		$columns = $efields['columnname'];
		$tableindex = $efields['entityidfield'];
		
		if (empty($columns)) return false;

		$select = $this->columns2Sql($columns);
		
		if ($module == 'Calendar') {
			$whereCond = "t.activitytype = 'Task'";
		} elseif ($module == 'Events') {
			$whereCond = "t.activitytype NOT IN ('Task', 'Emails')"; // crmv@152701
		} elseif (in_array($module, array('Emails')) && $table == $table_prefix.'_activity') { // crmv@152701
			$whereCond = "t.activitytype = '$module'";
		} elseif ($table == $table_prefix.'_crmentity') {
			$whereCond = "t.setype= '$module'";
		} else {
			$whereCond = null;
		}
		
		if (is_array($idlist) && count($idlist) > 0) {
			$idlist = array_filter(array_map('intval', $idlist));
			if (!empty($idlist)) {
				if ($whereCond) $whereCond .= ' AND ';
				$whereCond .= "$tableindex IN (".implode(',', $idlist).")";
			}
		// crmv@154947
		} elseif (!empty($idlist)) {
			if ($whereCond) $whereCond .= ' AND ';
			$whereCond .= "$tableindex = ".intval($idlist);
		}
		// crmv@154947e
		
		$now = date('Y-m-d H:i:s');
		$query = "SELECT t.{$tableindex} AS crmid, '$module' AS module, $select AS entityname, '$now' as lastupdate FROM $table t ";
		
		// crmv@192957
		$modInstance = CRMEntity::getInstance($module);
		if (in_array($table_prefix.'_crmentity', $modInstance->tab_name)) {
			$query .= "INNER JOIN {$table_prefix}_crmentity c ON c.crmid = t.{$tableindex} AND c.deleted = 0 ";
		} else {
			if ($whereCond) $whereCond .= ' AND ';
			$whereCond .= "t.deleted = 0";
		}
		// crmv@192957e
		
		if ($whereCond) {
			$query .= "WHERE $whereCond";
		}
		
		return $query;
	}
	
	/**
	 * Return the SQL expression to extract the entityname with the specified columns,
	 * assuming all the columns are in the same table
	 */
	public function columns2Sql($columns, $table = '') {
		global $adb;
		
		$sqlTable = ($table ? $table.'.' : '');
		
		if (is_array($columns)) {
			if (count($columns) > 1) {
				// insert spaces inside
				$arglist = array();
				foreach ($columns as $col) {
					$arglist[] = "COALESCE(".$sqlTable.$col.",'')"; // crmv@180551
					if (strlen($this->nameSeparator) > 0) {
						$arglist[] = "'".$adb->sql_escape_string($this->nameSeparator)."'";
					}
				}
				if (strlen($this->nameSeparator) > 0) {
					array_pop($arglist);
				}
				$fieldsname = call_user_func_array(array($adb, 'sql_concat'), array($arglist));
			} else {
				$fieldsname = $sqlTable.$columns[0];
			}
		} elseif (is_string($columns)) {
			$fieldsname = $sqlTable.$columns;
			// crmv@165801
			if (!$adb->isMysql() && $columns == 'commentcontent') {
				$fieldsname = "CAST($fieldsname AS ".$adb->datadict->ActualType('C')."(200))";
			}
			// crmv@165801e
		}
		
		if ($adb->isMssql()) {
			// crmv@155585
			$trimfn = 'LTRIM(RTRIM(CAST(';
			$trimfne = ' AS VARCHAR(250))))';
			// crmv@155585e
		} else {
			$trimfn = 'TRIM(';
			$trimfne = ')';
		}
		
		$fieldsname = "{$trimfn}COALESCE($fieldsname, ''){$trimfne}";
		
		return $fieldsname;
	}
	
}