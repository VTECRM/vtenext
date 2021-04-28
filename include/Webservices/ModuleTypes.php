<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
	function vtws_listtypes($fieldTypeList, $user=false){
		global $table_prefix;
		// Bulk Save Mode: For re-using information
		static $webserviceEntities = false;
		// END

		static $types = array();
		if(!empty($fieldTypeList)) {
			$fieldTypeList = array_map('strtolower', $fieldTypeList); // crmv@201099
			sort($fieldTypeList);
			$fieldTypeString = implode(',', $fieldTypeList);
		} else {
			$fieldTypeString = 'all';
		}
		if(!empty($types[$user->id][$fieldTypeString])) {
			return $types[$user->id][$fieldTypeString];
		}
		try{
			global $log;
			/**
			 * @var PearDatabase
			 */
			$db = PearDatabase::getInstance();
			
			vtws_preserveGlobal('current_user',$user);
			//get All the modules the current user is permitted to Access.
			$allModuleNames = getPermittedModuleNames();
			if(array_search('Calendar',$allModuleNames) !== false){
				array_push($allModuleNames,'Events');
			}

			if(!empty($fieldTypeList)) {
				$sql = "SELECT distinct(".$table_prefix."_field.tabid) as tabid FROM ".$table_prefix."_field LEFT JOIN ".$table_prefix."_ws_fieldtype ON ".
				"".$table_prefix."_field.uitype=".$table_prefix."_ws_fieldtype.uitype
				 INNER JOIN ".$table_prefix."_profile2field ON ".$table_prefix."_field.fieldid = ".$table_prefix."_profile2field.fieldid
				 INNER JOIN ".$table_prefix."_def_org_field ON ".$table_prefix."_def_org_field.fieldid = ".$table_prefix."_field.fieldid
				 INNER JOIN ".$table_prefix."_role2profile ON ".$table_prefix."_profile2field.profileid = ".$table_prefix."_role2profile.profileid
				 INNER JOIN ".$table_prefix."_user2role ON ".$table_prefix."_user2role.roleid = ".$table_prefix."_role2profile.roleid
				 where ".$table_prefix."_profile2field.visible=0 and ".$table_prefix."_def_org_field.visible = 0
				 and ".$table_prefix."_field.presence in (0,2)
				 and ".$table_prefix."_user2role.userid=? and fieldtype in (".generateQuestionMarks($fieldTypeList).')';
				
				//crmv@5687 - overrides for webservices
				if (file_exists('include/Webservices/WSOverride.php')) {
					require('include/Webservices/WSOverride.php');
					if(isset($ws_replace_sql) && $ws_replace_sql != ''){
						$sql = $ws_replace_sql;
					}
					$sql .= $ws_additional_modules;
				}
				//crmv@5687e

				$params = array();
				$params[] = $user->id;
				foreach($fieldTypeList as $fieldType)
					$params[] = $fieldType;
				$result = $db->pquery($sql, $params);
				$it = new SqlResultIterator($db, $result);
				$moduleList = array();
				foreach ($it as $row) {
					$moduleList[] = getTabModuleName($row->tabid);
				}
				$allModuleNames = array_intersect($moduleList, $allModuleNames);

				$params = $fieldTypeList;

				$sql = "select name from ".$table_prefix."_ws_entity inner join ".$table_prefix."_ws_entity_tables on ".
				$table_prefix."_ws_entity.id=".$table_prefix."_ws_entity_tables.webservice_entity_id inner join ".
				$table_prefix."_ws_entity_fieldtype on ".$table_prefix."_ws_entity_fieldtype.table_name=".
				$table_prefix."_ws_entity_tables.table_name where fieldtype in (".generateQuestionMarks($params).')';
				$result = $db->pquery($sql, $params);
				$it = new SqlResultIterator($db, $result);
				$entityList = array();
				foreach ($it as $row) {
					$entityList[] = $row->name;
				}
			}
			//get All the CRM entity names.
			if($webserviceEntities === false || !CRMEntity::isBulkSaveMode()) {
				// Bulk Save Mode: For re-using information
				$webserviceEntities = vtws_getWebserviceEntities();
			}

			$accessibleModules = array_values(array_intersect($webserviceEntities['module'],$allModuleNames));
			$entities = $webserviceEntities['entity'];
			$accessibleEntities = array();
			if(empty($fieldTypeList)) {
				foreach($entities as $entity){
					$webserviceObject = VtenextWebserviceObject::fromName($db,$entity);//crmv@207871
					$handlerPath = $webserviceObject->getHandlerPath();
					$handlerClass = $webserviceObject->getHandlerClass();

					require_once $handlerPath;
					$handler = new $handlerClass($webserviceObject,$user,$db,$log);
					$meta = $handler->getMeta();
					if($meta->hasAccess()===true){
						array_push($accessibleEntities,$entity);
					}
				}
			}
		}catch(WebServiceException $exception){
			throw $exception;
		}catch(Exception $exception){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
				"An Database error occured while performing the operation");
		}
		
		$default_language = VTWS_PreserveGlobal::getGlobal('default_language');
		$current_language = vtws_preserveGlobal('current_language',VteSession::get('authenticated_user_language'));	//crmv@30166
		
		$appStrings = return_application_language($current_language);
		$appListString = return_app_list_strings_language($current_language);
		vtws_preserveGlobal('app_strings',$appStrings);
		vtws_preserveGlobal('app_list_strings',$appListString);
		
		$informationArray = array();
		foreach ($accessibleModules as $module) {
			$vteModule = ($module == 'Events')? 'Calendar':$module;
			$informationArray[$module] = array('isEntity'=>true,'label'=>getTranslatedString($module,$vteModule),
				'singular'=>getTranslatedString('SINGLE_'.$module,$vteModule));
		}
		
		foreach ($accessibleEntities as $entity) {
			$label = (isset($appStrings[$entity]))? $appStrings[$entity]:$entity;
			$singular = (isset($appStrings['SINGLE_'.$entity]))? $appStrings['SINGLE_'.$entity]:$entity;
			$informationArray[$entity] = array('isEntity'=>false,'label'=>$label,
				'singular'=>$singular);
		}
		
		VTWS_PreserveGlobal::flush();
		$types[$user->id][$fieldTypeString] = array("types"=>array_merge($accessibleModules,$accessibleEntities),
			'information'=>$informationArray);
		return $types[$user->id][$fieldTypeString];
	}

?>