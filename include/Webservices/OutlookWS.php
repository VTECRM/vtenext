<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@5687 */

function ol_is_sdk($client_version='', $user) {
	// >= version 3.0.0
	require_once('include/utils/VTEProperties.php');
	$VTEP = VTEProperties::getInstance();
	
	if($client_version=='') return 'false';
	if(str_replace('.','',$client_version) >= 300) {
		
		if ($VTEP->getProperty('outlook_sdk') == '1') {
			return 'true';
		}else{
			return 'false';
		}
	}else{
		return 'false';	
	}
	
}

function ol_get_filters($module){
	global $table_prefix;

	if (file_exists('include/Webservices/WSOverride.php')) {
		require('include/Webservices/WSOverride.php');
	}

	if($module == '') return '';
	if (!is_array($ws_filters)) $ws_filters = array();
	
	$db = PearDatabase::getInstance();
	$dbresult = $db->pquery("SELECT tabid FROM ".$table_prefix."_tab WHERE name=?", array($module));
	if ($db->num_rows($dbresult)){
		$tabid = $db->query_result($dbresult, 0, 'tabid'); 
	}else{
		return ''; 
	}
	
	$filter = '';
	if(isset($ws_filters[$tabid]) && $ws_filters[$tabid] != ''){
		$filter = $ws_filters[$tabid];
	}
	
	return $filter;
	
}

function ol_clientsearch($area_modules, $search_text){
	global $table_prefix; 

	// areas are needed
	if (!file_exists('modules/Area/Area.php')) return array();

	$usemods = array('Accounts', 'Leads', 'Contacts', 'Vendors');
	$modules = array_combine(array_map('getTabid', $usemods), $usemods);

	$areaid = 1; //AREA ANAGRAFICA
	require_once('modules/Area/Area.php');

	$area = Area::getInstance();
	$area->constructById($areaid);
	$area->setSessionVars();
	$areaid = $area->getId();
	
	$list = $area->search($search_text);
	
	$db = PearDatabase::getInstance();
	$query = "SELECT id, ws.name, tabid
		FROM {$table_prefix}_ws_entity ws
		INNER JOIN {$table_prefix}_tab t ON ws.name = t.name
		WHERE ws.name IN (".generateQuestionMarks($area_modules).")";
	$dbresult = $db->pquery($query, array($area_modules));
	$ws_modulesid = array();
	if ($db->num_rows($dbresult)){
		while($row = $db->fetchByAssoc($dbresult, -1, false)){
			$ws_modulesid[$row['tabid']] = $row['id'];
		}
	}else{
		return array();
	}
	
	$search_result = array();
	foreach ($list as $tabid => $value){
		$entries = $value['entries'];
		foreach($entries as $record => $data){
			$record_ws = $ws_modulesid[$tabid]."x".$record;
			$search_result[$modules[$tabid]][] = array($modules[$tabid], $record_ws, strip_tags($data[1].$data[2]), strip_tags($data[0]));
		}
	}
	
	$result = array();
	foreach($area_modules as $module){
		if(isset($search_result[$module])){
			$result = array_merge($result, $search_result[$module]);
		}
	}
	
	return $result;
	
}

function ol_doquery($module, $search_fields, $search_value, $user, $use_ws_id = false){
	global $table_prefix;
	
	require_once('include/QueryGenerator/QueryGenerator.php');

	$db = PearDatabase::getInstance();
	$query = "SELECT f.tabid, fieldname, columnname 
				FROM {$table_prefix}_ws_entity ws
				INNER JOIN {$table_prefix}_tab t ON ws.name = t.name 
				INNER JOIN {$table_prefix}_field f ON t.tabid = f.tabid
				WHERE ws.name = ? ";
	
	$dbresult = $db->pquery($query,array($module));
	$fields_info = array();
	if ($db->num_rows($dbresult)){
		while($row = $db->fetchByAssoc($dbresult, -1, false)){
			$fields_info[$row['columnname']] = $row['fieldname'];
		}
	}else{
		return array();
	}
	
	$queryGenerator = QueryGenerator::getInstance($module, $user);
	$queryGenerator->initForDefaultCustomView();
	foreach($search_fields as $field){
		$queryGenerator->addField($field);
	}
	//$queryGenerator->setFields($search_fields);
	//$queryGenerator->addField('crmid');
	
	// sanitize module name
	$module = str_replace(array('.', ':', '/', '\\'), '', $module);
	if (!file_exists("modules/$module/$module.php")) 

	// TODO: Check and rewrite this using CRMEntity::getInstance
	require_once("modules/$module/$module.php");
	$obj = new $module();
	$moduleKey = $obj->table_index;
	
	if(!is_array($search_fields)) $search_fields = array($search_fields);
	
	$queryGenerator->addConditionGlue("(");
	for($i = 0; $i < sizeof($search_fields); $i++){
		$queryGenerator->addCondition($search_fields[$i], $search_value, 'c');
		
		if($i < sizeof($search_fields)-1){
			$queryGenerator->addConditionGlue("OR");
		}
	}
	$queryGenerator->addConditionGlue(")");
	
	$query = $queryGenerator->getQuery();
	$query .= ol_get_filters($module);
	
	$db = PearDatabase::getInstance();
	$res = $db->query($query);
	$result = array();
	
	while($row = $db->fetchByAssoc($res, -1, false)){
		$row_fieldnames = array();
		
		foreach($row as $key=>$value){
			$row_fieldnames[$fields_info[$key]] = $value;
		}
		$row_fieldnames['id'] = construct_ws_id($row[$moduleKey],getSalesEntityType($row[$moduleKey]));
		$result[] = $row_fieldnames;
	}
	
	return $result;
	
}