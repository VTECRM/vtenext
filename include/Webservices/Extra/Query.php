<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("include/Webservices/Extra/ModuleTypes.php");
require_once("include/Webservices/Extra/DescribeObject.php");

function vtws_queryExtra($q,$user,$limit=false){
	global $adb,$table_prefix;
	$moduleRegex = "/[fF][rR][Oo][Mm]\s+([^\s;]+)/";
	$moduleName = '';
	if(preg_match($moduleRegex, $q, $m)) $moduleName = trim($m[1]);
	$module_obj = WebserviceExtra::getInstance($moduleName);
	if (!$module_obj){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	$obj = $module_obj->describe();
	$mapping = $module_obj->columnMapping();
	$fields = Array();
	if (!empty($obj['fields'])){
		foreach ($obj['fields'] as $field_arr){
			$fields[$field_arr['name']] = $mapping[$field_arr['name']];
		}
	}
	$modules_ids = Array();
	$sql = "select id,name from ".$table_prefix."_ws_entity";
	$res = $adb->query($sql);
	if ($res){
		while($row = $adb->fetchByAssoc($res,-1,false)){
			$module_ids[$row['name']] = $row['id'];
		}
	}
	$extramodule_id = $module_obj->id;
	$function = Array();
	$add_fields_arr = Array();
	$replace = '';
	$module_obj->query_parameters($function,$add_fields_arr,$replace);
	if ($replace == ''){
		throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX,"No query defined for this module");
	}
	require_once 'include/Webservices/Extra/SQLParser/PHPSQLParser.php';
	require_once 'include/Webservices/Extra/SQLParser/PHPSQLCreator.php';
	$parser = new PHPSQLParser($q);
	if (!isset($parser->parsed['SELECT']) || isset($parser->parsed['UPDATE']) || isset($parser->parsed['DELETE']) || isset($parser->parsed['CREATE']) || isset($parser->parsed['DROP'])  || isset($parser->parsed['ALTER'])){
		throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Only select statements allowed.");
	}
	//replace from fields
	$add_fields = true;
	$onlycount = false;
	//permit cont(*)
	if ($parser->parsed['SELECT'][0]['expr_type'] == 'aggregate_function'){
		if ($parser->parsed['SELECT'][0]['base_expr'] == 'count' && $parser->parsed['SELECT'][0]['sub_tree'][0]['base_expr'] == '*' && empty($parser->parsed['SELECT'][0]['alias'])){
			$add_fields = false;
			$parser->parsed['SELECT'][0]['alias'] = Array(
				'as'=>1,
				'name'=>'count',
				'base_expr'=>'as count',
				'no_quotes'=>'count',
			);
			$parser->parsed['SELECT'] = Array($parser->parsed['SELECT'][0]);
			$onlycount = true;
		}
		else{
			throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Only count(*) is permitted as aggregate function");
		}
	}
	//permit *
	elseif ($parser->parsed['SELECT'][0]['expr_type'] == 'colref' && $parser->parsed['SELECT'][0]['base_expr'] == '*' ){
		$parser->parsed['SELECT'] = Array($parser->parsed['SELECT'][0]);
		$add_fields = false;
	}
	//control from fields
	else{
		foreach ($parser->parsed['SELECT'] as &$select){
			if (!isset($fields[$select['base_expr']])){
				throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Unknown column '{$select['base_expr']}' for select statement");
			}
			elseif (!empty($select['alias'])){
				throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Alias for column '{$select['base_expr']}' not permitted in select statement");
			}
			else{ //transform select mapping real field
				$select['base_expr'] = $select['no_quotes'] = $fields[$select['base_expr']];
			}
		}
	}
	//control where fields
	if (isset($parser->parsed['WHERE'])){
		foreach ($parser->parsed['WHERE'] as &$where){
			if ($where['expr_type'] == 'colref'){
				if(!isset($fields[$where['base_expr']])){
					throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Unknown column '{$where['base_expr']}' for where statement");
				}
				else{//transform where mapping real field
					$where['base_expr'] = $where['no_quotes'] = $fields[$where['base_expr']];
				}
			}
		}
	}
	//control order by fields
	if (isset($parser->parsed['ORDER'])){
		foreach ($parser->parsed['ORDER'] as &$order){
			if ($order['expr_type'] == 'colref'){
				if(!isset($fields[$order['base_expr']])){
					throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Unknown column '{$order['base_expr']}' for order by statement");
				}
				else{//transform where mapping real field
					$order['base_expr'] = $order['no_quotes'] = $fields[$order['base_expr']];
				}
			}
		}
	}
	$limit = false;
	//control limit
	if (isset($parser->parsed['LIMIT'])){
		if ($parser->parsed['LIMIT']['offset'] != '' && $parser->parsed['LIMIT']['offset'] < 0){
			throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Limit offset cannot be negative");
		}
		if ($parser->parsed['LIMIT']['rowcount'] != '' && $parser->parsed['LIMIT']['rowcount'] < 0){
			throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Limit rowcount cannot be negative");
		}
		$limit = true;
		if ($parser->parsed['LIMIT']['offset'] == ''){
			$parser->parsed['LIMIT']['offset'] = 0;
		}
		$limit_arr = Array(
			'offset'=>$parser->parsed['LIMIT']['offset'],
			'rowcount'=>$parser->parsed['LIMIT']['rowcount'],
		);
		unset($parser->parsed['LIMIT']); //use adodb limit instead query
	}
	if ($add_fields){
		//add extra fields
		if (!empty($add_fields_arr)){
			foreach ($add_fields_arr as $field_add){
				$parser->parsed['SELECT'][] = Array(
	               'expr_type' => 'colref',
	               'alias' => '',
	               'base_expr' => $field_add,
	               'sub_tree' => '',
	               'delim' => ''
				);
			}
		}
		//add id field
		$found_id = false;
		foreach ($parser->parsed['SELECT'] as $select_){
			if ($select_['base_expr'] == $mapping['id']){
				$found_id = true;
			}
		}
		if (!$found_id){
			$parser->parsed['SELECT'][] = Array(
               'expr_type' => 'colref',
               'alias' => '',
               'base_expr' => $mapping['id'],
               'sub_tree' => '',
               'delim' => ''
			);
		}
	}
	$cnt = 1;
	$max = count($parser->parsed['SELECT']);
	foreach ($parser->parsed['SELECT'] as &$select){
		if ($cnt < $max){
			$select['delim'] = ',';
		}
		else{
			$select['delim'] = '';
		}
		$cnt++;
	}
	$creator = new PHPSQLCreator($parser->parsed);
	$q = $creator->created;
	$q = str_replace(" {$moduleName}",$replace,$q);
	try{
		if ($limit){
			$res = $adb->limitQuery($q,$limit_arr['offset'],$limit_arr['rowcount']);
		}
		else{
			$res = $adb->query($q);
		}
		if ($res){
			$return_arr = Array();
			while($row = $adb->fetchByAssoc($res)){
				$row_change = Array();
				if ($onlycount){
					$row_change['count'] = $row['count'];
				}
				else{
					foreach ($obj['fields'] as $field_arr){
						if (isset($row[$mapping[$field_arr['name']]])){
							//process field
							switch($field_arr['type']['name']){
								case 'autogenerated':
									//transform id_modulexid_record
									$row_change[$field_arr['name']] = $extramodule_id."x".$row[$mapping[$field_arr['name']]];
									break;
								case 'owner':
									$row_change[$field_arr['name']] = $module_ids['Users']."x".$row[$mapping[$field_arr['name']]];
									break;
								case 'reference':
									if (count($field_arr['type']['refersTo']) > 1){
										if (isset($function[$field_arr['name']]) && $function[$field_arr['name']] != '' && $row[$mapping[$function[$field_arr['name']]]] != ''){
											//take related module from another field
											$row_change[$field_arr['name']] = $module_ids[$row[$mapping[$function[$field_arr['name']]]]]."x".$row[$mapping[$field_arr['name']]];
										}
										else{
											$row_change[$field_arr['name']] = '';
											//take related module from id
											if ($row[$mapping[$field_arr['name']]] != ''){
												//crmv@171021
												$setype =  getSalesEntityType($row[$mapping[$field_arr['name']]]);
												if (!empty($setype)) {
													$row_change[$field_arr['name']] = $module_ids[$setype]."x".$row[$mapping[$field_arr['name']]];
												}
												//crmv@171021e
											}
										}
									}
									else{
										$row_change[$field_arr['name']] = $module_ids[$field_arr['type']['refersTo'][0]]."x".$row[$mapping[$field_arr['name']]];
									}
									break;
								default:
									$row_change[$field_arr['name']] = $row[$mapping[$field_arr['name']]];
									break;			
							}
						}
					}
				}
				$return_arr[] = $row_change;
			}
		}
		
	}
	catch(Exception $e) {
		throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error running query");
	}
	
	return $return_arr;
}
?>