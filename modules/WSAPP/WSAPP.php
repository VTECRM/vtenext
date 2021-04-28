<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/Utils.php';

class WSAPP {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb, $table_prefix;
		if($event_type == 'module.postinstall') {
			$this->initCustomWebserviceOperations();
            $this->registerHandlers();
            $this->registerVtenextCRMApp();//crmv@208112
			$this->registerWsappWorkflowhandler();

			$adb->pquery("UPDATE {$table_prefix}_tab SET customized=0 WHERE name=?", array($modulename));
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			return;
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			return;
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			return;		
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
			return;			
		} else if($event_type == 'module.postupdate') {
			$this->registerSynclibEventHandler();
		}
	}
	
	function initCustomWebserviceOperations() {
		$operations = array();

		$wsapp_register_parameters    = array('type' => 'string','synctype'=>'string');
		$operations['wsapp_register'] = array(
			'file' => 'modules/WSAPP/api/ws/Register.php', 'handler' => 'wsapp_register', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $wsapp_register_parameters );

		$wsapp_deregister_parameters    = array('type' => 'string', 'key' => 'string');
		$operations['wsapp_deregister'] = array(
			'file' => 'modules/WSAPP/api/ws/DeRegister.php', 'handler' => 'wsapp_deregister', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $wsapp_deregister_parameters );

		$wsapp_get_parameters    = array('key' => 'string', 'module' => 'string', 'token' => 'string');
		$operations['wsapp_get'] = array(
			'file' => 'modules/WSAPP/api/ws/Get.php', 'handler' => 'wsapp_get', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $wsapp_get_parameters );

		$wsapp_put_parameters    = array('key' => 'string', 'element' => 'encoded');
		$operations['wsapp_put'] = array(
			'file' => 'modules/WSAPP/api/ws/Put.php', 'handler' => 'wsapp_put', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $wsapp_put_parameters );
			
		$wsapp_put_parameters    = array('key' => 'string', 'element' => 'encoded');
		$operations['wsapp_map'] = array(
			'file' => 'modules/WSAPP/api/ws/Map.php', 'handler' => 'wsapp_map', 'reqtype' => 'POST', 'prelogin' => '0',
			'parameters' => $wsapp_put_parameters );
			
		$this->registerCustomWebservices( $operations );
	}
	
	function registerCustomWebservices( $operations ) {
		global $adb,$table_prefix;
		
		foreach($operations as $operation_name => $operation_info) {	
			$checkres = $adb->pquery("SELECT operationid FROM ".$table_prefix."_ws_operation WHERE name=?", array($operation_name));
			if($checkres && $adb->num_rows($checkres) < 1) {
				$operation_id = $adb->getUniqueId($table_prefix.'_ws_operation');
			
				$operation_res = $adb->pquery(
					"INSERT INTO ".$table_prefix."_ws_operation (operationid, name, handler_path, handler_method, type, prelogin) 
					VALUES (?,?,?,?,?,?)",
					array($operation_id, $operation_name, $operation_info['file'], $operation_info['handler'], 
						$operation_info['reqtype'], $operation_info['prelogin'])
				);

				$operation_parameters = $operation_info['parameters'];
				$parameter_index = 0;	
				foreach($operation_parameters as $parameter_name => $parameter_type) {
					$adb->pquery(
						"INSERT INTO ".$table_prefix."_ws_operation_parameters (operationid, name, type, sequence) 
						VALUES(?,?,?,?)", array($operation_id, $parameter_name, $parameter_type, ($parameter_index+1))
					);
					++$parameter_index;
				}
				Vtecrm_Utils::Log("Opearation $operation_name enabled successfully.");
			} else {
				Vtecrm_Utils::Log("Operation $operation_name already exists.");
			}
		}
	}

    function registerHandlers(){
        global $adb,$table_prefix;

        $handlerDetails = array();

        $appTypehandler = array();
        $appTypehandler['type'] = "Outlook";
        $appTypehandler['handlerclass'] = "OutlookHandler";
        $appTypehandler['handlerpath'] = "modules/WSAPP/Handlers/OutlookHandler.php";
        $handlerDetails[] = $appTypehandler;

        $appTypehandler = array();
        $appTypehandler['type'] = "vtenextCRM";//crmv@208112
        $appTypehandler['handlerclass'] = "vtenextCRMHandler";//crmv@208112
        $appTypehandler['handlerpath'] = "modules/WSAPP/Handlers/vtenextCRMHandler.php";//crmv@208112
        $handlerDetails[] = $appTypehandler;

        foreach($handlerDetails as $appHandlerDetails)
             $adb->pquery("INSERT INTO ".$table_prefix."_wsapp_handlerdetails VALUES(?,?,?)",array($appHandlerDetails['type'],$appHandlerDetails['handlerclass'],$appHandlerDetails['handlerpath']));
    }
    
    function registerVtenextCRMApp(){//crmv@208112
    	global $table_prefix;
    	$db = PearDatabase::getInstance();
    	$appName = "vtenextCRM";//crmv@208112
    	$type  ="user";
    	$uid = uniqid();
    	$id = $db->getUniqueID($table_prefix.'_wsapp');
    	$db->pquery("INSERT INTO ".$table_prefix."_wsapp (appid,name, appkey,type) VALUES(?,?,?,?)", array($id,$appName, $uid,$type));
    }

	function registerWsappWorkflowhandler(){
		$db = PearDatabase::getInstance();
		$em = new VTEventsManager($db);
		$dependentEventHandlers = array('VTEntityDelta');
		$dependentEventHandlersJson = Zend_Json::encode($dependentEventHandlers);
		$em->registerHandler('vte.entity.aftersave', 'modules/WSAPP/WorkFlowHandlers/WSAPPAssignToTracker.php', 'WSAPPAssignToTracker','',$dependentEventHandlersJson);//crmv@207852
	}

	function registerSynclibEventHandler(){
		$className='WSAPP_VtenextSyncEventHandler';//crmv@208112
		$path = 'modules/WSAPP/synclib/handlers/VtenextSyncEventHandler.php';//crmv@208112
		$type = 'vtenextSyncLib';//crmv@208112
		wsapp_RegisterHandler($type, $className, $path);
	}
}
 
?>