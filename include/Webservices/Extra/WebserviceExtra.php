<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/utils.php');

class WebserviceExtra{
	var $name = '';
	var $id = '';
	function __construct($name,$id){
		$this->name = $name;
		$this->id = $id;
	}
	function getExtraModulesRelatedTo($module,&$rm){
		$related_modules = Array();
		$modules = self::getAllExtraModules();
		foreach ($modules as $id=>$mod){
			$relmod_obj = self::getInstance($mod);
			if ($relmod_obj !== false){
				$relation = $relmod_obj->relatedto($module,$rm);
				if ($relation !== false){
					$related_modules[$mod] = $relation;
				}
			}
		}
		return $related_modules;
	}
	function getAllExtraModules(){
		global $adb,$table_prefix;
		$sql = "select id,name from {$table_prefix}_ws_entity_extra";
		$res = $adb->query($sql);
		$return_modules = Array();
		if ($res){
			while($row = $adb->fetchByAssoc($res,-1,false)){
				$return_modules[$row['id']] = $row['name'];
			}
		}
		return $return_modules;
	}
	function getInstanceFromID($id){
		global $adb,$table_prefix;
		$sql = "select * from {$table_prefix}_ws_entity_extra where id = ?";
		$res = $adb->pquery($sql,Array($id));
		if ($res && $adb->num_rows($res) == 1){
			$row = $adb->fetchByAssoc($res,-1,false);
			if (file_exists($row['handler_path'])){
				@include($row['handler_path']);
				if (class_exists($row['handler_class'])){
					return new $row['handler_class']($row['name'],$row['id']);
				}
			}
		}
		return false;
	}
	function getInstance($module){
		global $adb,$table_prefix;
		$sql = "select * from {$table_prefix}_ws_entity_extra where name = ?";
		$res = $adb->pquery($sql,Array($module));
		if ($res && $adb->num_rows($res) == 1){
			$row = $adb->fetchByAssoc($res,-1,false);
			if (file_exists($row['handler_path'])){
				@include_once($row['handler_path']);
				if (class_exists($row['handler_class'])){
					return new $row['handler_class']($row['name'],$row['id']);
				}
			}
		}
		return false;
	}
	/*
	 * function to show name of the extra module
	 */		
	function get_listtype(){
		return Array(
			'label'=>getTranslatedString($this->name,$this->name),
			'singular'=>getTranslatedString("SINGLE_".$this->name,$this->name),
		);
	}
	/*
	 * function to show fields of the extra module
	 */	
	function describe(){
		$entity = Array();
		$entity['label'] = getTranslatedString($this->name,$this->name);
		$entity['name'] = $this->name;
		$entity['createable'] = 0;
		$entity['updateable'] = 0;
		$entity['deleteable'] = 0;
		$entity['retrieveable'] = 1;
		$entity['fields'] = Array(
		);
		$entity['idPrefix'] = $this->id;
		$entity['isEntity'] = 2;
		$entity['labelfields'] = '';
		return $entity;
	}
	/*
	 * function to map table fields to logical fields logical field => real field
	 */		
	function columnMapping(){
		return Array();
	}
	/*
	 * function to return variables to construct the query:
	 * $function => Array key: name of the field related to another module (not extra), value: name of the field in select that contains name of related module
	 * $add_fields_arr => Array value: field to add to select statement on every query (used to extract related module name for $function parameter)
	 * $replace => String subquery that will replace module name of the query
	 */
	function query_parameters(&$function,&$add_fields_arr,&$replace){
		
	}
	/*
	 * function to return variables to construct retrieve function:
	 * $params => Array that contains parameters with conditions
	 * $q => String query to retrieve records
	 * $fields => Array key: field, value: if fixed "function" -> look at function the id of the module related, else Integer: id of the module related
	 * $function => Array with key: name of the field related to another module (not extra), value: name of the field in select that contains name of related module
	 */
	function retrieve_parameters($id,$module_ids,&$params,&$q,&$fields,&$function){
		
	}
	/*
	 * function to return if a extra module is related to normal module
	 */
	function relatedto($module,&$rm){
		return false;
	}
	/*
	 * function to get related ids
	 */
	function getRelatedIds($module,$relmodule,$crmid, $start, $limit, $onlycount){
		$obj = self::getInstance($relmodule);
		if ($obj){
			return $obj->getRelatedIds($module,$relmodule,$crmid, $start, $limit, $onlycount);
		}
		return Array();
	}
}
?>