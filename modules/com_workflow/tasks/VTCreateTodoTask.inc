<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/Webservices/Utils.php');
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once 'include/Webservices/ModuleTypes.php';
require_once('include/Webservices/Create.php');
require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/WebserviceField.php';
require_once 'include/Webservices/EntityMeta.php';
require_once 'include/Webservices/VtenextWebserviceObject.php';//crmv@207871

require_once("modules/Users/Users.php");

class VTCreateTodoTask extends VTTask{
	public $executeImmediately = true;
	
	public function getFieldNames(){return array('todo', 'description', 'time', 'date', 'status', 'priority', 'days', 'direction', 'datefield');}
	
	function getAdmin(){
		$user = Users::getActiveAdminUser(); //crmv@180676
		global $current_user;
		$this->originalUser = $current_user;
		$current_user = $user;
		return $user;
	}
	
	public function doTask($entityData){
		global $adb, $current_user;
		$userId = $entityData->get('assigned_user_id');
		if($userId===null){
			$userId = vtws_getWebserviceEntityId('Users', 1);
		}
		
		$logger = VTESystemLogger::getLogger('workflow'); // crmv@172616

		$baseDate = $entityData->get($this->datefield);
		$baseDate = date('Y-m-d',strtotime($baseDate)); //crmv@49887
		preg_match('/\d\d\d\d-\d\d-\d\d/', $baseDate, $match);
		if ($match[0] == '') $match[0] = date('Y-m-d');	//crmv@18155
		$baseDate = strtotime($match[0]);
		$date = strftime('%Y-%m-%d', $baseDate+$this->days*24*60*60*($this->direction=='before'?-1:1)); //crmv@69992
		$fields = array(
			'activitytype'=>'Task',
			'description'=>$this->description,
			'subject'=>$this->todo,
			'taskpriority'=>$this->priority,
			'taskstatus'=>$this->status,
			'assigned_user_id'=>$userId,
			'time_start'=>self::conv12to24hour($this->time),	//crmv@80489
			'date_start'=>$date,
			'due_date'=>$date,
			'visibility'=>'all',
			'eventstatus'=>''
		);
		$moduleName = $entityData->getModuleName();
		$adminUser = $this->getAdmin();
		$id = $entityData->getId();
		if($moduleName=='Contacts'){
			$fields['contact_id'] = $id;
		}else{ 
			$data = vtws_describe('Calendar', $adminUser);
			$fieldInfo = $data['fields'];
			foreach($fieldInfo as $field){
				if($field['name']=='parent_id'){
					$parentIdField = $field;
				}
			}
			$refersTo = $parentIdField['type']['refersTo'];
			
			if(in_array($moduleName, $refersTo)){
				$fields['parent_id'] = $id;
			}
		}
		
		//crmv@141112
		$entityInfo = vtws_create('Calendar', $fields, $adminUser);
		if (!empty($entityInfo['id'])){
			$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
			$taskid = $entityIdComponents[1];
			$parentIdComponents = vtws_getIdComponents($id);
			$parentId = $parentIdComponents[1];
			if(!empty($parentId)){
				$parentModule = getSalesEntityType($parentId);
				if($parentModule == 'Messages'){
					$message = CRMEntity::getInstance('Messages');
					$message->save_related_module_small($parentId,'Calendar',$taskid);
				}
			}
			if ($logger) $logger->info("WORKFLOW #{$this->workflowId} TASK #{$this->id}: [CREATETASK] [$parentModule #$parentId] Task created #$taskid"); // crmv@172616
		}
		//crmv@141112e
		
		global $current_user;
		$current_user = $this->originalUser;
	}

	static function conv12to24hour($timeStr){
		$arr = array();
		preg_match('/(\d{1,2}):(\d{1,2})(am|pm)/', $timeStr, $arr);
		if($arr[3]=='am'){
			$hours = ((int)$arr[1]) % 12;
		}else{
			$hours = ((int)$arr[1]) % 12 + 12;
		}
		return str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($arr[2], 2, '0', STR_PAD_LEFT);
	}
}