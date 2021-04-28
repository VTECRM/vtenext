<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@48267 */

require_once('include/Webservices/Utils.php');
require_once('include/Webservices/GetUpdates.php');


class VteSyncAPI {
	
	/**
	 * Retrieve the changes (modified or deleted records) since a certain date
	 *
	 *
	 * $module		: the module to check (Webservice style, so Calendar = Task, Events = Activities)
	 * $dateFrom 	: date in ISO 8601 format (with timezone) from which check for changes
	 * $maxEntries	: max num of records returned (-1 for no limit)
	 * $user		: the owner of the records (only admin can use this parameter), if not passed and admin, retrieve records of all users
	 *
	 * Returns:
	 * array(
	 * 	'created' => array of records created
	 *  'updated' => array of existing records modified afger the dateFrom
	 *  'deleted' => array of deleted records
	 *  'last_update' => timestamp with last time seen
	 *  'more' => true if not all records have been retrieved
	 * )
	 */
	// TODO: filter by owner ??
	public function getChanges($module, $dateFrom, $dateTo = null, $dateField = '', $maxEntries = 100, $showDeleted = true, $user = null) {
		global $adb, $table_prefix, $recordString,$modifiedTimeString;
		global $current_user, $default_timezone;
		
		if (!vtlib_isModuleActive('VteSync')) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"VteSync module not active");
		}

		// set timezone (avoid php warning)
		if (function_exists('date_default_timezone_set')) {
			date_default_timezone_set($default_timezone);
		}
		
		$useOwnerFilter = true;

		if (!empty($user)) {
			if (!is_admin($current_user)) {
				throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Only admin users can access other users's data");
			}
			$old_current_user = $current_user;

			$current_user = CRMEntity::getInstance('Users');
			$userId = $current_user->retrieve_user_id($user);

			if (empty($userId)) {
				throw new WebServiceException("INVALID_USER","Specified user is invalid");
			} else {
				$current_user = $current_user->retrieveCurrentUserInfoFromFile($userId);
				if ($current_user->status == 'Inactive') {
					throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
				}
			}
		} else {
			$useOwnerFilter = !is_admin($current_user);
		}

		/*if (empty($module) || !isModuleInstalled($module) || !vtlib_isModuleActive($module)) {
			throw new WebServiceException("INVALID_MODULE","Module is not active or not available for the specified user");
		}*/

		// convert the date to the local format
		if (!empty($dateFrom)) {
			$dateFrom = DateTime::createFromFormat(DateTime::ISO8601 , $dateFrom);
			if (!$dateFrom) {
				throw new WebServiceException('INVALID_DATE','Date is not in a valid format');
			}
		
			// transform the date to the local db timezone
			if (function_exists('date_default_timezone_get')) {
				$local_tz = date_default_timezone_get();
			} elseif (!empty($default_timezone)) {
				$local_tz = $default_timezone;
			} else {
				throw new WebServiceException('NO_TIMEZONE','Unable to retrieve the local timezone');
			}
			$dateFrom->setTimezone(new DateTimeZone($local_tz));
			$dateFrom = $dateFrom->format('Y-m-d H:i:s');
		}
		
		if (!empty($dateTo)) {
			$dateTo = DateTime::createFromFormat(DateTime::ISO8601 , $dateTo);
			if (!$dateTo) {
				throw new WebServiceException('INVALID_DATE','Date is not in a valid format');
			}
			$dateTo->setTimezone(new DateTimeZone($local_tz));
			$dateTo = $dateTo->format('Y-m-d H:i:s');
		}

		// sanitize dateField
		if (empty($dateField)) $dateField = 'modifiedtime';
		$dateField = preg_replace('/[^a-z0-9_]/i', '', $dateField);

		if ($dateField == 'time_start' && in_array($module, array('Events', 'Calendar'))) {
			if ($adb->isMysql()) {
				$dateField = "CAST(CONCAT(date_start, ' ', time_start) as datetime)";
			} elseif ($adb->isOracle()) {
				$dateField = "TO_DATE(SUBSTR(date_start, 0, 10) || ' ' || SUBSTR(time_start, 0, 5), 'YYYY-MM-DD HH24:MI')";
			} else {
				// TODO mssql
			}
		}

		if (empty($maxEntries)) $maxEntries = 100;
		$maxEntries = intval($maxEntries);

		if ($showDeleted === '') {
			$showDeleted = true;
		} else {
			$showDeleted = in_array(strtolower($showDeleted), array('1', 'true'));
		}

		// End params check

		$ignoreModules = array("Users", 'Messages', 'ModNotifications', 'ChangeLog', 'ModComments');

		$setypeArray = array();
		$setypeData = array();
		$setypeHandler = array();
		$setypeNoAccessArray = array();

		$output = array();
		$output["created"] = array();
		$output["updated"] = array();
		$output["deleted"] = array();

		$ownerIds = array($current_user->id);

		$adb->startTransaction();

		$accessableModules = array();
		$entityModules = array();
		$modulesDetails = vtws_listtypes(null,$current_user);
		$moduleTypes = $modulesDetails['types'];
		$modulesInformation = $modulesDetails["information"];

		foreach ($modulesInformation as $moduleName=>$entityInformation) {
			if ($entityInformation["isEntity"]) $entityModules[] = $moduleName;
		}
		
		// crmv@190016
		if ($module == "TicketComments") {
			$output['more'] = false;
			$output['local_timezone'] = $local_tz;
			$output['user_timezone'] = $current_user->user_timezone;
			$output['last_update'] = time();
			return $output;
		}
		// crmv@190016e
		
		if (!in_array($module,$entityModules)) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
		}

		$accessableModules[] = $module;
		$accessableModules = array_diff($accessableModules,$ignoreModules);

		if (count($accessableModules)<=0 || $maxEntries <= 0) {
			$output['more'] = false;
			$output['local_timezone'] = $local_tz;
			$output['user_timezone'] = $current_user->user_timezone;
			$output['last_update'] = time();
			return $output;
		}

		// find the maximum date in the range
		$handler = vtws_getModuleHandlerFromName($module, $current_user);
		$moduleMeta = $handler->getMeta();
		$entityDefaultBaseTables = $moduleMeta->getEntityDefaultTableList();
		$entityDefaultBaseIndexes = $moduleMeta->getEntityTableIndexList();
		//since there will be only one base table for all entities
		$baseCRMTable = $entityDefaultBaseTables[0];
		$baseCRMIndex = $entityDefaultBaseIndexes[$baseCRMTable];
		$joins = '';
		
		if ($module=="Calendar" || $module=="Events" ){
			$baseCRMTable = getSyncQueryBaseTable($module);
		} elseif ($module == 'Leads') {
			// TODO: generalize!
			$joins .= " INNER JOIN {$table_prefix}_leaddetails ON {$table_prefix}_leaddetails.leadid = $baseCRMTable.$baseCRMIndex";
		}

		// get all the recent changes (even deleted records)
		$params = array();
		$wheres = array();
		
		if (!empty($dateFrom)) {
			$wheres[] = "$dateField >= ?";
			$params[] = $dateFrom;
		}
		
		if (!empty($dateTo)) {
			$wheres[] = "$dateField <= ?";
			$params[] = $dateTo;
		}
		if (!$showDeleted) {
			$wheres[] = $moduleMeta->getEntityDeletedQuery();
		}

		if (count($wheres) > 0) {
			$whereSql = implode(' AND ', $wheres);
		} else {
			$whereSql = '';
		}
		$q = "SELECT $dateField as datefield FROM $baseCRMTable $joins WHERE ".($whereSql ? $whereSql.' AND ' : '')."setype IN (".generateQuestionMarks($accessableModules).") ";

		foreach ($accessableModules as $entityModule) {
			if ($entityModule == "Events") $entityModule = "Calendar";
			$params[] = $entityModule;
		}

		if ($useOwnerFilter) {
			$q .= ' AND smownerid IN ('.generateQuestionMarks($ownerIds).')';
			$params = array_merge($params,$ownerIds);
		}

		$q .= " ORDER BY $dateField ASC";

		// Execute the query
		$result = $adb->limitpQuery($q,0,$maxEntries,$params);

		$modTime = array();
		for($i=0;$i<$adb->num_rows($result);$i++){
			$modTime[] = $adb->query_result($result,$i, 'datefield');
		}
		if(!empty($modTime)){
			$maxModifiedTime = max($modTime);
		}
		if(!$maxModifiedTime){
			$maxModifiedTime = $dateFrom;
		}
		// end find maximum date

		$lastModTime = null;
		foreach ($accessableModules as $elementType) {
			$handler = vtws_getModuleHandlerFromName($elementType, $current_user);
			$moduleMeta = $handler->getMeta();
			$deletedQueryCondition = $moduleMeta->getEntityDeletedQuery();
			preg_match_all("/(?:\s+\w+[ \t\n\r]+)?([^=]+)\s*=([^\s]+|'[^']+')/",$deletedQueryCondition,$deletedFieldDetails);
			$fieldNameDetails = $deletedFieldDetails[1];
			$deleteFieldValues = $deletedFieldDetails[2];
			$deleteColumnNames = array();
			foreach($fieldNameDetails as $tableName_fieldName){
				$fieldComp = explode(".",$tableName_fieldName);
				$deleteColumnNames[$tableName_fieldName] = $fieldComp[1];
			}
			$params = array($moduleMeta->getTabName());
			
			if ($dateFrom) {
				$sqlDateFrom = "AND $dateField >= ?";
				$params[] = $dateFrom;
			} else {
				$sqlDateFrom = '';
			}

			$queryGenerator = QueryGenerator::getInstance($elementType, $current_user);
			$fields = array();
			$moduleFields = $moduleMeta->getModuleFields();
			$moduleFeildNames = array_keys($moduleFields);
			$moduleFeildNames[]='id';
			$queryGenerator->setFields($moduleFeildNames);
			$selectClause = "SELECT ".$queryGenerator->getSelectClauseColumnSQL();
			// adding the fieldnames that are present in the delete condition to the select clause
			// since not all fields present in delete condition will be present in the fieldnames of the module
			foreach ($deleteColumnNames as $table_fieldName=>$columnName) {
				if (!in_array($columnName,$moduleFeildNames)){
					$selectClause .=", ".$table_fieldName;
				}
			}
			if ($elementType=="Emails")
				$fromClause = vtws_getEmailFromClause();
			else
				$fromClause = $queryGenerator->getFromClause();

			$upperDateLimit = " AND $dateField <= ? ";
			$params[] = $maxModifiedTime;

			if (!$showDeleted) {
				$deletedQuery = " AND deleted = 0";
			} else {
				$deletedQuery = '';
			}

			$fromClause .= "
				INNER JOIN (
					SELECT createdtime, modifiedtime, crmid,deleted
					FROM $baseCRMTable
					WHERE setype = ? $sqlDateFrom $upperDateLimit $deletedQuery ".($useOwnerFilter ? "AND smownerid IN (".generateQuestionMarks($ownerIds).")" : "")."
				) {$table_prefix}_ws_sync ON ({$table_prefix}_crmentity.crmid = {$table_prefix}_ws_sync.crmid)";
				
			if ($useOwnerFilter) {
				$params = array_merge($params,$ownerIds);
			}

			$q = $selectClause." ".$fromClause;
			$result = $adb->pquery($q, $params);

			$recordDetails = array();
			$deleteRecordDetails = array();

			if ($elementType == 'Events') {
				// retrieve invitees
				$calInstance = CRMEntity::getInstance('Activity');
			}

			while ($arre = $adb->fetchByAssoc($result)) {
				$key = $arre[$moduleMeta->getIdColumn()];
				$createdTime = $arre['createdtime'];
				$modifiedTime = $arre['modifiedtime'];
				$dateFieldTime = $arre[$dateField];
				
				if ($modifiedTime > $lastModTime) $lastModTime = $modifiedTime;
				
				if (vtws_isRecordDeleted($arre,$deleteColumnNames,$deleteFieldValues)) {
					if(!$moduleMeta->hasAccess()){
						continue;
					}
					$output["deleted"][] = array(
						'id' => vtws_getId($moduleMeta->getEntityId(), $key),
						'modifiedtime' => $modifiedTime,
						'createdtime' => $createdTime,
					);

				} else {
					if (!$moduleMeta->hasAccess() ||!$moduleMeta->hasPermission(EntityMeta::$RETRIEVE,$key)){
						continue;
					}

					try {
						$data = DataTransform::sanitizeDataReadWithTimezone($arre,$moduleMeta);
						$data = $this->fixDateFormat($data, $moduleMeta);
						
						if ($elementType == 'Events') {
							// retrieve invitees
							$data['invitees'] = $this->sanitizeInviteesList($calInstance->getInvitees($key));
						}


						if ($createdTime && (!$dateFrom || $createdTime >= $dateFrom) && $modifiedTime == $createdTime) {
							$output["created"][] = $data;
						} else {
							$output["updated"][] = $data;
						}
					} catch (WebServiceException $e) {
						//ignore records the user doesn't have access to.
						continue;
					} catch (Exception $e) {
						throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request");
					}
				}
			}

			// now add changed owners as deleted (skip other fields, so first sync ignores this)
			if ($dateField == 'modifiedtime' && $showDeleted && $useOwnerFilter) {

				// take the record moved from current user to another one (but not me again)
				$params = array($ownerIds, $ownerIds);
				$upperDateLimit = '';
				if ($dateFrom) {
					$upperDateLimit .= " AND oc.changetime >= ? ";
					$params[] = $dateFrom;
				}
				if ($dateTo) {
					$upperDateLimit .= " AND oc.changetime <= ? ";
					$params[] = $dateTo;
				}
				$q = "select oc.crmid, createdtime, oc.changetime as modifiedtime
				from $baseCRMTable
				inner join vtesync_ownerchanges oc on oc.crmid = {$table_prefix}_crmentity.crmid
				where
					prev_smownerid in (".generateQuestionMarks($ownerIds).") and
					smownerid not in (".generateQuestionMarks($ownerIds).") and
					$upperDateLimit";
				$res = $adb->pquery($q, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						$output["deleted"][] = array(
							'id' => vtws_getId($moduleMeta->getEntityId(), $row['crmid']),
							'modifiedtime' => $row['modifiedtime'],
							'createdtime' => $row['createdtime'],
						);
					}
				}
			}
		}

		// final part
		// TODO: add lead condition!
		if (!$showDeleted) {
			$deletedQuery = " AND deleted = 0";
		} else {
			$deletedQuery = '';
		}

		// now check if there are more records left
		$q = "SELECT crmid FROM $baseCRMTable WHERE $dateField > ? $deletedQuery AND setype IN (".generateQuestionMarks($accessableModules).")";
		$params = array($maxModifiedTime);

		foreach ($accessableModules as $entityModule) {
			if ($entityModule == "Events") $entityModule = "Calendar";
			$params[] = $entityModule;
		}
		if ($useOwnerFilter) {
			$q .= 'AND smownerid IN ('.generateQuestionMarks($ownerIds).')';
			$params = array_merge($params,$ownerIds);
		}

		$result = $adb->limitpQuery($q,0,1,$params);
		if ($adb->num_rows($result)>0) {
			$output['more'] = true;
		} else {
			$output['more'] = false;
		}

		$output['local_timezone'] = $local_tz;
		$output['user_timezone'] = $current_user->user_timezone;
		
		$error = $adb->hasFailedTransaction();
		$adb->completeTransaction();
		
		if ($output['more']) {
			$output['last_update'] = strtotime($lastModTime);
		} else {
			$output['last_update'] = time();
		}

		if($error){
			throw new WebServiceException(
				WebServiceErrorCode::$DATABASEQUERYERROR,
				vtws_getWebserviceTranslatedString('LBL_'.WebServiceErrorCode::$DATABASEQUERYERROR)
			);
		}
		VTWS_PreserveGlobal::flush();

		if ($old_current_user) {
			$current_user = $old_current_user;
		}
		
		return $output;
	}
	
	// crmv@195073
	public function getAllRelatedIds($firstModule, $secondModule, $relationId, $crmid = null) {
	
		if (!vtlib_isModuleActive('VteSync')) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"VteSync module not active");
		}
		
		$RM = RelationManager::getInstance();
		$rels = $RM->getRelations($firstModule, null, $secondModule);
		
		if (empty($rels)) {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"No relations found with the provided modules");
		}
		
		if ($firstModule != 'Targets' && $secondModule != 'Contacts') {
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Only Target-Contact relation is supported");
		}
		
		global $adb, $table_prefix;
		
		$list = [];
		$res = $adb->pquery("SELECT crel.crmid, crel.relcrmid
			FROM {$table_prefix}_crmentityrel crel
			INNER JOIN {$table_prefix}_crmentity c ON c.crmid = crel.crmid AND c.deleted = 0
			INNER JOIN {$table_prefix}_crmentity c2 ON c2.crmid = crel.relcrmid AND c2.deleted = 0
			WHERE module = ? AND relmodule = ?", 
			array($firstModule, $secondModule)
		);
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$targetid = '39x'.$row['crmid']; // TODO: check these numbers
			$contactid = '4x'.$row['relcrmid'];
			$list[$targetid][] = $contactid;
		}
		
		$ret = [
			'first_module' => $firstModule,
			'second_module' => $secondModule,
			'records' => $list
		];
		
		return $ret;
	}
	// crmv@195073e

	protected function sanitizeInviteesList($list) {
		foreach ($list as &$item) {
			$entityname = getEntityName($item['type'], array($item['id']));
			$item['entityname'] = $entityname[$item['id']];
			$item['id'] = vtws_getWebserviceEntityId($item['type'], $item['id']);

		}
		return $list;
	}
	
	// revert date format to DB one (YYYY-MM-DD)
	function fixDateFormat($record,$meta){
		$moduleFields = $meta->getModuleFields();
		foreach($moduleFields as $fieldName=>$fieldObj){
			if($fieldObj->getFieldDataType()=="date"){
				if(!empty($record[$fieldName])){
					$dbDate = getValidDBInsertDateValue($record[$fieldName]);
					if ($dbDate) {
						$record[$fieldName] = $dbDate;
					}
				}
			}
		}
		return $record;
	}

}

// wrapper for webservices
function vteSync_getChanges() {
	$class = new VteSyncAPI();
	return call_user_func_array(array($class, 'getChanges'), func_get_args());
}

// crmv@195073
function vteSync_getAllRelatedIds() {
	$class = new VteSyncAPI();
	return call_user_func_array(array($class, 'getAllRelatedIds'), func_get_args());
}
// crmv@195073e