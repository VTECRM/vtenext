<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//TODO: verificare che non basti soltanto l'inclusione di include_webservices.php al posto di tutte le seguenti
require_once('include/Webservices/Utils.php');
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once('include/Webservices/ModuleTypes.php');
require_once('include/Webservices/Retrieve.php');
require_once('include/Webservices/Update.php');
require_once('include/Webservices/WebserviceField.php');
require_once('include/Webservices/EntityMeta.php');
require_once('include/Webservices/VtenextWebserviceObject.php');//crmv@207871
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901

class VTWorkflowEntity{
	function __construct($user, $id){
		$this->moduleName = null;
		$this->id = $id;
		$this->user = $user;
		//crmv@44747
		try {
			$data = vtws_retrieve($id, $user);
		} catch (Exception $e) {
			return false;
		}
		//crmv@44747e
		foreach($data as $key => $value){
			if(is_string($value)){
				$data[$key] = html_entity_decode($value, ENT_QUOTES, 'utf-8');
			}
		}
		$this->data = $data;
		$this->getModuleName();
	}
	/**
	 * Get the data from the entity object as an array.
	 *
	 * @return An array representation of the module data.
	 */
	function getData(){
		return $this->data;
	}
	
	/**
	 * Get the entity id.
	 *
	 * @return The entity id.
	 */
	function getId(){
		return $this->data['id'];
	}
	
	/**
	 * Get the name of the module represented by the entity data object.
	 *
	 * @return The module name.
	 */
	function getModuleName(){
		if($this->moduleName==null){
			global $adb, $table_prefix;
			$wsId = $this->data['id'];
			if (empty($wsId)) $wsId = $this->id;	//crmv@105312
			$parts = explode('x', $wsId);
			$result = $adb->pquery('select name from '.$table_prefix.'_ws_entity where id=?', array($parts[0]));
			$rowData = $adb->raw_query_result_rowdata($result, 0);
			$this->moduleName = $rowData['name'];
		}
		return $this->moduleName;
	}
	
	function get($fieldName){
		return $this->data[$fieldName];
	}
	
	function set($fieldName, $value){
		$this->data[$fieldName] = $value;
	}
	
	function save(){
		vtws_update($this->data,$this->user);
	}
	
}

//crmv@OPER10174
class VTEntityCache {
	
	// enable the cache to be cleared when a field changes
	public static $resetCache = array(); // crmv@79058
	public static $cache = array();
	
	function __construct($user){
		$this->user = $user;
	}
	
	public static function setResetCache($id) {
		self::$resetCache[$id] = true;
	}
	
	function forId($id){
		// impersonate admin
		global $iAmAProcess;
		if ($iAmAProcess) {
			global $current_user;
			$tmp_this_user = $this->user;
			$tmp_current_user = $current_user;
			$user = Users::getActiveAdminUser(); //crmv@180676
			$current_user = $this->user = $user;
			$PMUtils = ProcessMakerUtils::getInstance();
			$PMUtils->setDefaultDataFormat();
		}
		
		// crmv@79058
		if (self::$resetCache[$id]){
			self::$cache[$id] = null;
			unset(self::$resetCache[$id]);
		}
		// crmv@79058e
		if(self::$cache[$id]==null){
			$data = new VTWorkflowEntity($this->user, $id);
			if ($data !== false) self::$cache[$id] = $data;	//crmv@44747
		}
		
		// restore admin
		if ($iAmAProcess) {
			$current_user = $tmp_current_user;
			$this->user = $tmp_this_user;
			$PMUtils->restoreDataFormat();
		}
		
		return self::$cache[$id];
	}
}
//crmv@OPER10174e