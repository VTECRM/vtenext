<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@86915 */

class TouchModuleAutocomplete extends TouchWSClass {

	public $validateModule = true;
	
	function process(&$request) {
		global $adb, $table_prefix;
		global $touchInst, $touchUtils;
		
		// gather parameters
		$module = $request['module'];
		$search = $request['searchtext'];
		$searchField = $request['searchfield'];
		$pageLimit = intval($request['limit']) ?: $touchInst->autocompleteLimit;
		
		// params check
		if (empty($search)) return $this->error('No search term specified');
		if (!vtlib_isModuleActive($module)) return $this->error('Module is not active');
		
		//  get the search query
		$query = $this->getQuery($module, $search, $searchField);
		if (empty($query)) return $this->error('Internal error');
		
		// execute it!
		$search_result = array();
		$result = $adb->limitQuery($query, 0, $pageLimit);
		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result)) {
				$sres = $this->processResultRow($row, $search);
				$search_result[] = $sres;
			}
		}		
		
		return $this->success(array('result' => $search_result));
	}
	
	protected function processResultRow($row, $search) {
		
		$crmid = $row['crmid'];
		$entityname = $row['entityname'];
		
		// search the matching field
		$matchfields = array();
		$matchvalues = array();
		foreach ($row as $col=>$val) {
			if (strpos($col, 'match_') === 0 && stripos($val, $search) !== false) {
				$fname = substr($col, 6);
				$matchfields[] = $fname;
				$matchvalues[] = $val;
			}
		}
		$result = array(
			'crmid' => $crmid,
			'entityname' => $entityname,
			'matchfields' => $matchfields,
			'matchvalues' => $matchvalues
		);
				
		return $result;
	}
	
	protected function getQuery($module, $search, $searchField = null) {
		global $adb, $table_prefix, $current_user;
		global $touchInst, $touchUtils;
		
		$tabid = getTabid($module);
		if (!$tabid) return null;
		
		$moduleEntity = $touchUtils->getModuleInstance($module);
		
		$query = "SELECT fieldname, tablename, entityidfield FROM {$table_prefix}_entityname WHERE modulename=?";
		$result = $adb->pquery($query, array($module));
		$fieldsname = $adb->query_result_no_html($result, 0, 'fieldname');
		$tablename = $adb->query_result_no_html($result, 0, 'tablename');
		$entityidfield = $adb->query_result_no_html($result, 0, 'entityidfield');
		
		// prepare search columns
		$search_fields = array();
		if (strpos($fieldsname,',') !== false) {
			$fl = array();
			$fieldlists = explode(',', $fieldsname);
			foreach ($fieldlists as $w => $c) {
				if (count($fl)) {
					$fl[] = "' '";
				}
				$colfield = $this->getFullFieldColumn($tabid, $c);
				$fl[] = $colfield;
				if (empty($searchField)) $search_fields[$c] = $colfield;
			}
			$fieldsname = $adb->sql_concat($fl);
		} else {
			$colfield = $this->getFullFieldColumn($tabid, $fieldsname);
			if (empty($searchField)) $search_fields[$fieldsname] = $colfield;
			// add the table 
			$fieldsname = $colfield;
		}
		if ($module == 'Users') {
			$fieldsname = "{$table_prefix}_users.user_name";
			if (empty($searchField)) $search_fields['user_name'] = "{$table_prefix}_users.user_name";
		}
		
		// search in this field
		if (!empty($searchField)) {
			$colfield = $this->getFullFieldColumn($tabid, $searchField);
			if (empty($colfield)) return null;
			$search_fields[$searchField] = $colfield;
		}
		
		// search query
		$searchcols = array();
		foreach ($search_fields as $fname => $f) {
			$searchcols[] = "$f AS match_{$fname}";
		}
		$query = "SELECT $tablename.$entityidfield AS crmid, ".implode(', ', $searchcols).", $fieldsname AS entityname FROM {$tablename}";
		
		// append joins
		if ($module != 'Users') {
			$query .= " INNER JOIN {$table_prefix}_crmentity ON $tablename.$entityidfield = {$table_prefix}_crmentity.crmid";
		}
		if (!empty($moduleEntity->customFieldTable)) {
			$query .= " INNER JOIN ".$moduleEntity->customFieldTable[0]." ON $tablename.$entityidfield = ".$moduleEntity->customFieldTable[0].".".$moduleEntity->customFieldTable[1];
		}
		
		// append non-deleted condition
		if ($module == 'Users') {
			$query .= " WHERE status = 'Active'";
		} elseif ($module == 'Leads') {
			$query .= " WHERE {$table_prefix}_crmentity.deleted = 0 AND converted = 0";
		} else {
			$query .= " WHERE {$table_prefix}_crmentity.deleted = 0";
		}
		
		// append search term
		$search = str_replace("'", "\\'", $search);
		$search_conditions = array();
		foreach($search_fields as $fname => $col) {
			$search_conditions[] = "$col LIKE '%$search%'";
		}
		if (count($search_conditions) > 0) {
			$query .= ' AND ('.implode(' OR ',$search_conditions).')';
		}
		
		// append permissions
		if ($module != 'Users') {
			$secQuery = getNonAdminAccessControlQuery($module, $current_user);
			if (strlen($secQuery) > 1) {
				$query = appendFromClauseToQuery($query, $secQuery);
			}
		}
		
		$query .= " ORDER BY entityname ASC, {$tablename}.{$entityidfield} ASC";
		
		return $query;
	}
	
	protected function getFullFieldColumn($tabid, $fieldname) {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT tablename, columnname FROM {$table_prefix}_field WHERE tabid=? AND fieldname=?", array($tabid, $fieldname));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			return $row['tablename'].'.'.$row['columnname'];
		}
		return null;
	}
	
}
