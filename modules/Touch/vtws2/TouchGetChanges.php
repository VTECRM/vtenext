<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('include/Webservices/GetUpdates.php');
require_once('include/utils/SessionValidator.php'); // crmv@91082
require_once('modules/Users/LoginHistory.php'); // crmv@91082
require_once('include/utils/EmailDirectory.php'); // crmv@107655

class TouchGetChanges extends TouchWSClass {

	public $listLimit = 50; // return max 50 records per module
	public $zeroDate = '1970-01-02 00:00:00';

	public $reloadDate = null;	// date in which a complete meta reload is needed

	protected $rawRequest = null;

	protected $relatedCache = array();

	/*
	 * Get the changes that should be propagated to the app
	 */
	public function process(&$request) {
		global $adb, $table_prefix, $current_user;
		global $touchInst, $touchUtils;

		$this->rawRequest = $request;

		$modules = Zend_Json::decode($request['modules']); // if this is a valid array, these modules will be used instead, if it's empty, all the available mods will be used
		$deviceTime = $request['devicetime'];

		$limit = intval($request['limit']);
		if ($limit > 0) $this->listLimit = $limit;

		// crmv@173184
		$since = intval($request['since']);
		if ($request['since_multiple']) {
			$sinceAll = Zend_Json::decode($request['since_multiple']);
			if (is_array($sinceAll)) {
				foreach ($sinceAll as &$ts) {
					$ts = $this->ts2date($ts);
				}
			} else {
				$sinceAll = $this->ts2date($since);
			}
		} else {
			$sinceAll = $this->ts2date($since);
		}
		// crmv@173184e
		
		$metaSince = intval($request['meta_since']);
		$metaSinceDate = ($metaSince > 0 ? date('Y-m-d H:i:s', $metaSince) : $this->zeroDate);

		$skip_timecheck = ($request['skip_time_check'] == 1);
		$skip_related = ($request['skip_record_related'] == 1);
		$skip_content = ($request['skip_record_content'] == 1);
		$skip_filter = ($request['skip_filter_check'] == 1);
		$skip_folder = ($request['skip_folder_check'] == 1);
		$skip_recents = ($request['skip_recents'] == 1);
		$skip_emaildir = ($request['skip_emaildir'] == 1);

		// TODO: do this comparison with a token and not with a date or pass the server time and the client resend that
		// It's already like this for the modules, but should be extended and this check removed
		// first compare the server and the device time, if the difference is more than 60 seconds... too much!
		$localtime = time();
		$diff = abs($localtime - $deviceTime);
		if ($diff > 60 && !$skip_timecheck) {
			return $this->error('Time difference is too big ('.$diff.'s), you must synchronize the server and the client first.');
		}

		// Check the session
		// crmv@91082
		$SV = SessionValidator::getInstance();
		if ($SV->isStarted()) {
			if ($SV->isValid()) {
				$SV->refresh();
			} else {
				$loghistory = LoginHistory::getInstance();
				$loghistory->user_logout($current_user->user_name, null, null, 'auto', true);
				$touchInst->destroyWSSession();
				$touchInst->outputFailure('Session expired', array('logout_reason' => 'concurrent'));
				exit;
			}
		} else {
			$SV->refresh();
		}
		// crmv@91082e
		
		// crmv@161368
		// get the wipedate if the variable here is not set
		$wipeDate = $touchInst->getWipeDate($current_user->id);
		if (!empty($wipeDate) && $metaSinceDate != $this->zeroDate && ($wipeDate >= $metaSinceDate)) {
			return $this->success(array(
				'meta_changed' => true, 
				'require_reload' => true, 
				'require_wipe' => true, 
				'meta'=> $metaSinceDate, 
				'login_data' => $touchInst->retrieveLoginData($request['deviceid'])
			));
		}
		// crmv@161368e

		// check reload date
		if ($this->reloadDate && ($this->reloadDate >= $metaSinceDate)) {
			return $this->success(array('meta_changed' => true, 'require_reload' => true, 'meta'=> $metaSinceDate));
		}
		$requireReload = false;

		// crmv@93148 - close the session, to allow concurrent requests
		$touchInst->closeWSSession();
		
		// meta changes
		$meta = $this->getMetaChanges($metaSinceDate);
		if ($meta && $meta['total'] > 0) $requireReload = true;
		
		// get modified/deleted records and filters
		$changes = $this->getChangedRecords($modules, $sinceAll, !$skip_content, !$skip_filter, !$skip_folder, !$skip_related); // crmv@173184
		
		// users
		$users = $this->getChangedUsers($metaSinceDate);

		// groups
		$groups = $this->getChangedGroups($metaSinceDate);

		// recents
		if ($skip_recents) {
			$recents = array();
		} else {
			$recents = $this->getRecents();
		}
		
		// crmv@107655
		// email directory
		if ($skip_emaildir) {
			$emailDir = array();
		} else {
			$emailDir = $this->getEmailDirectory($metaSinceDate);
		}
		// crmv@107655e

		$metaChanged = $requireReload || ($users['total'] > 0) || ($groups['total'] > 0);

		$output = array(
			'records'=>$changes, 
			'users' => $users, 
			'groups' => $groups, 
			'recents'=>$recents, 
			'email_directory' => $emailDir, // crmv@107655
			'meta_changed' => $metaChanged, 
			'meta'=>$meta, 
			'require_reload' => $requireReload
		);
		
		if ($touchInst->wsExists('GetOfflineData')) {
			$req = array();
			$offlineInfo = $this->subcall('GetOfflineData', $req);
			if ($offlineInfo && $offlineInfo['success']) {
				$output['offline_info'] = $offlineInfo;
			}
		}
		
		$output['login_data'] = $touchInst->retrieveLoginData($request['deviceid']);
		
		return $this->success($output);
	}
	
	// crmv@173184
	protected function ts2date($ts) {
		if ($ts > 0) {
			$date = max(date('Y-m-d H:i:s', $ts), $this->zeroDate);
		} else {
			$date = $this->zeroDate;
		}
		return $date;
	}
	// crmv@173184e

	function getChangedRecords($modules = null, $sinceDates = null, $content = false, $filters = false, $folders = false, $related = false) { // crmv@173184
		global $adb, $table_prefix;
		global $touchInst, $touchUtils, $current_user;

		// max number of records to return for each module
		// this amount is not strictly observed, since if there are many records with the same timestamp,
		// they will be all returned since there is no way to partition them with several requests
		$limitRecords = $this->listLimit;

		// get the list of modules
		$modulesList = array();

		if (!empty($modules)) {
			if (!is_array($modules)) $modules = array($modules);
			$modulesList = $modules;
		} else {

			$entityModules = array();
			$modulesDetails = vtws_listtypes(null,$current_user);
			$modulesInformation = $modulesDetails["information"];

			foreach ($modulesInformation as $moduleName=>$entityInformation){
				if($entityInformation["isEntity"]) $modulesList[] = $moduleName;
			}
		}

		// crmv@173184
		// find minimum date
		if (is_array($sinceDates)) {
			$minDate = min($sinceDates);
		} else {
			$minDate = $sinceDates;
		}
		if (empty($minDate)) $minDate = $this->zeroDate;
		// crmv@173184e
		
		// Users are done separately
		$modulesList = array_diff($modulesList,$touchInst->excluded_modules, array('Users'));

		if (empty($modulesList)) return array('changes'=>array(), 'total'=>0);

		// do a very quick check to see if globally there are changes (there should be an index, otherwise it's pointless)
		// crmv@177095
		$res = $adb->query(
			"SELECT MAX(maxtime) AS maxmod FROM (
			SELECT MAX(modifiedtime) AS maxtime FROM {$table_prefix}_crmentity	
			UNION ALL
			SELECT MAX(modifiedtime) AS maxtime FROM {$table_prefix}_messages
			) tt"
		);
		// crmv@177095e
		if ($res && $adb->num_rows($res) > 0) {
			$maxmod = $adb->query_result_no_html($res, 0, 'maxmod');
			// crmv@164122
			// check also timestamp of notifications
			if (in_array('ModNotifications', $modulesList)) {
				$res = $adb->query("SELECT MAX(modifiedtime) AS maxmod FROM {$table_prefix}_modnotifications");
				if ($res && $adb->num_rows($res) > 0) {
					$maxmod = max($maxmod, $adb->query_result_no_html($res, 0, 'maxmod'));
				}
			}
			// crmv@164122e
			if ($maxmod && $maxmod < $minDate) { // crmv@173184
				// last global mod time is older than sincetime, therefore no nchanges will be found for any module
				return array('changes'=>array(), 'total'=>0, 'last_timestamp' => time());
			}
		}

		$CustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
		$changes = array();
		$globalLastTs = null; // crmv@173184
		$total = 0;

		// now check for changes
		foreach ($modulesList as $elementType) {
		
			// crmv@173184
			if (is_array($sinceDates)) {
				$sinceDate = $sinceDates[$elementType];
			} else {
				$sinceDate = $sinceDates;
			}
			if (empty($sinceDate)) $sinceDate = $this->zeroDate;
			// crmv@173184e
			
			// crmv@177095 - speed up for messages the first time
			if ($sinceDate == $this->zeroDate && $elementType == 'Messages') {
				$oldRelated = $related;
				$related = false;
			} elseif (isset($oldRelated)) {
				$related = $oldRelated;
			}
			// crmv@177095e

			$moduleInstance = $touchUtils->getModuleInstance($elementType);
			$queries = $this->getChangeQuery($elementType, $sinceDate);
			
			$query = $queries['main'];
			$params = $queries['params'];
			$countQuery = $queries['count'];
			$idColumn = $queries['idcolumn'];
			
			$res = $adb->limitPQuery($query, 0, $limitRecords+1, $params);
			
			// crmv@164122 - removed line
			if ($res && $adb->num_rows($res) > 0) {
				$modCounter = 0;
				$lastTs = 0;
				$secondStep = false;
				$relateds_ids = array();
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$crmid = $row[$idColumn];
					$entry = array('crmid'=>$crmid);
					$entry['timestamp'] = strtotime($row['modifiedtime']);
					$isDeleted = ($row['is_deleted'] == '1');

					if (!$this->rawRequest['latest'] && !$secondStep && $modCounter == $limitRecords) {
						// last+1, check the timestamp

						if ($lastTs != $entry['timestamp'])	break; // ok, different, so end

						// re-run the query and find the first != timestamp
						// suppose no more than 1000 records with same modifiedtime are present
						$query2 = preg_replace('/\) sync_table/i', ' AND modifiedtime < ?) sync_table', $query);
						$params[] = date('Y-m-d H:i:s', $lastTs+1);
						// overwrite old res
						$res = $adb->limitPQuery($query2, $limitRecords, 1000, $params);
						$secondStep = true;
						continue;
					}
					
					// fetch the content of the record
					if ($content && !$isDeleted) {
						$req = array(
							'module' => $elementType,
							'record' => $crmid,
							'set_recent' => 0,
							'set_seen' => 0,
						);
						$record = $this->subcall('GetRecord', $req);
						if (!empty($record['crmid']) || !empty($record['record_id'])) {
							// this is for modComments (they can return a different crmid)
							$entry['crmid'] = ($record['crmid'] ? $record['crmid'] : $record['record_id']);
						}
						$entry['content'] = $record;
					}
					
					if ($filters && !$isDeleted) {
						$CustomView = CRMEntity::getInstance('CustomView'); // crmv@115329
						$filterList = $CustomView->getRecordViews($elementType, $crmid, true);
						$entry['filters'] = $filterList;
					}
					
					if ($folders && !$isDeleted && $moduleInstance->hasFolders()) {
						if ($entry['content']) {
							$folderid = $entry['content']['folderid'];
						} else {
							$folderid = getSingleFieldValue($moduleInstance->table_name, 'folderid', $moduleInstance->table_index, $crmid);
						}
						$entry['folders'] = array(intval($folderid));
					}
					
					if ($related && !$isDeleted) {
						// changed into multi-id retrieval
						//$entry['related'] = $this->getAllRelated($elementType, $crmid);
						$relateds_ids['related_list'][] = $crmid;
						$relateds_ids['related_list_idx'][$crmid] = count($changes[$elementType]['updated']);
						
						// Changed into a multi-id retrieval
						//$entry['related_n1'] = $this->getN1Related($elementType, $crmid);
						$relateds_ids['related_n1_list'][] = $crmid;
						$relateds_ids['related_n1_list_idx'][$crmid] = count($changes[$elementType]['updated']);
					}
					
					$changes[$elementType][$isDeleted ? 'deleted' : 'updated'][] = $entry;
					if ($this->rawRequest['latest']) {
						if (!$lastTs) $lastTs = $entry['timestamp'];
					} else {
						$lastTs = $entry['timestamp'];
					}
					++$modCounter;
					++$total;
					
				}
				
				// retrieve multiple related ids all at once
				if (!empty($relateds_ids['related_list'])) {
					$list = $this->getAllRelated($elementType, $relateds_ids['related_list']);
					foreach ($list as $lid => $lists) {
						$index = $relateds_ids['related_list_idx'][$lid];
						if (is_int($index) && $index >= 0) {
							$changes[$elementType]['updated'][$index]['related'] = $lists;
						}
					}
				}
				
				// retrieve multiple related ids all at once
				if (!empty($relateds_ids['related_n1_list'])) {
					$list = $this->getN1Related($elementType, $relateds_ids['related_n1_list']);
					foreach ($list as $lid => $lists) {
						$index = $relateds_ids['related_n1_list_idx'][$lid];
						if (is_int($index) && $index >= 0) {
							$changes[$elementType]['updated'][$index]['related_n1'] = $lists;
						}
					}
				}
				
			}
			
			if (!empty($changes[$elementType])) {
				$changes[$elementType]['last_timestamp'] = $lastTs;
				$globalLastTs = max($globalLastTs, $lastTs); // crmv@173184
			}

		}
		
		return array('changes'=>$changes, 'total'=>$total, 'last_timestamp' => $globalLastTs ?: time()); // crmv@173184
	}
	
	// crmv@164122
	protected function getChangeQuery($elementType, $sinceDate) {
		global $adb, $table_prefix, $current_user;
		global $touchUtils;
		
		$eventsBadType = array('Task', 'Emails', 'Webmails');
		
		if ($elementType == 'ModNotifications') {
			
			$idColumn = 'modnotificationsid';
			$query = "SELECT 0 as is_deleted, modifiedtime, $idColumn FROM {$table_prefix}_modnotifications WHERE smownerid = '{$current_user->id}'";
			if (!$this->rawRequest['latest']) {
				$query .= " AND modifiedtime >= ?";
				$params = array($sinceDate);
				$query .= " ORDER BY modifiedtime ASC, $idColumn ASC";
			} else {
				$query .= " ORDER BY modifiedtime ASC";
			}
			$countQuery = preg_replace('/select.*?from/i', "SELECT COUNT($idColumn) AS count FROM", $query);
			
		} else {
			$handler = vtws_getModuleHandlerFromName($elementType, $current_user);
			$moduleMeta = $handler->getMeta();
			$deletedQueryCondition = $moduleMeta->getEntityDeletedQuery();
		
			$moduleInstance = $touchUtils->getModuleInstance($elementType);

			if ($elementType == 'Messages') {
				global $current_account, $current_folder;
				//config account
				$mail_accountid = vtlib_purify($this->rawRequest['mail_accountid']);
				if ($mail_accountid) $current_account = $mail_accountid;

				if (empty($current_account)) {
					$current_account = $moduleInstance->getMainUserAccount();
					$current_account = $current_account['id'];
				}
				$moduleInstance->setAccount($current_account);
				if ($current_account != 'all')
					$specialFolders = $moduleInstance->getSpecialFolders(false); // crmv@170276

				// config folder
				$mail_folder = $this->rawRequest['mail_folder'];
				if ($mail_folder) $current_folder = $mail_folder;
			}
			
			$queryGenerator = QueryGenerator::getInstance($elementType, $current_user);
			$queryGenerator->setFields(array('id'));

			// create the extra join
			$baseTable = "{$table_prefix}_crmentity";
			$baseId = 'crmid';
			
			// crmv@173184
			$modField = $queryGenerator->getWSField('modifiedtime', false);
			$modTable = $modField->getTableName() ?: $table_prefix.'_crmentity';
			// crmv@173184e

			// condition on the date
			$setype = $elementType;
			if ($elementType == 'Events') {
				$setype = 'Calendar';
			}

			if ($elementType == 'Users') {
				$dateSql = "INNER JOIN (SELECT id AS crmid FROM {$table_prefix}_users WHERE date_entered >= ?) sync_table ON sync_table.crmid = {$table_prefix}_users.id";
				$params = array($sinceDate);
			} elseif (in_array("{$table_prefix}_crmentity", $moduleInstance->tab_name)) { // crmv@173184 crmv@200661
				// TODO: fix per modcomments, prendere solo il padre
				$dateSql = "INNER JOIN (SELECT crmid FROM $baseTable MARK1 WHERE MARK2 setype = ? AND modifiedtime >= ?) sync_table ON sync_table.crmid = $baseTable.crmid";
				$params = array($setype, $sinceDate);
			}

			if (!$this->rawRequest['latest']) {
				$queryGenerator->appendToFromClause($dateSql);
			}

			if ($elementType == 'Calendar') {
				$queryGenerator->appendToWhereClause(" AND {$table_prefix}_activity.activitytype = 'Task'");

			} elseif ($elementType == 'Events') {
				if (count($eventsBadType) > 0) {
					foreach ($eventsBadType as $caltype) $blist[] = "'$caltype'";
					$queryGenerator->appendToWhereClause(" AND {$table_prefix}_activity.activitytype NOT IN (".implode(',',$blist).")");
				}
				// TODO: tirare su anche gli eventi a cui sono invitato?

			} elseif ($elementType == 'ModComments') {
				// not needed
				// retrieve only parent comments
				//$queryGenerator->appendToWhereClause(" AND {$table_prefix}_modcomments.parent_comments = 0");
			} elseif ($elementType == 'Messages') {
				// Messages in the Shared folder are retrieved when getting the ModComments
				// For all the other folders, messages are private, so I just take mines
				// crmv@173184
				$ownerField = $queryGenerator->getWSField('assigned_user_id', false);
				$ownerTable = $ownerField->getTableName();
				$queryGenerator->appendToWhereClause(" AND $ownerTable.smownerid = ".$current_user->id);
				$queryGenerator->appendToWhereClause(" AND $modTable.modifiedtime >= ?");
				$params[] = $sinceDate;
				// crmv@173184e
			}

			// generate query
			$queryGenerator->setSkipDeletedQuery(true);
			$query = $queryGenerator->getQuery();

			// crmv@95586
			if ($elementType == 'Messages') {
				// fix forced index which is not needed here and slows things down
				$query = preg_replace('/force index\s+\([a-z0-9_ ]+\)/i', '', $query);
			}
			// crmv@95586e
			
			// crmv@173184
			// fix where because some ugly things are added
			$query = preg_replace('/MARK1.+MARK2/', 'WHERE', $query);
			$query = preg_replace('/^select /i', 'select CASE WHEN ('.$deletedQueryCondition.") THEN 0 ELSE 1 END AS is_deleted, $modTable.modifiedtime, ", $query);

			$countQuery = preg_replace('/select.*?from/', 'SELECT COUNT(crmid) AS count FROM', $query);
			
			$idColumn = $moduleMeta->getIdColumn();
			$baseTable = $moduleMeta->getEntityBaseTable();
			
			if ($this->rawRequest['latest']) {
				if ($elementType = 'Messages') {
					$query .= " ORDER BY {$table_prefix}_messages.mdate DESC";
				} else {
					$query .= " ORDER BY $modTable.modifiedtime DESC";
				}
			} else {
				if ($baseTable == $table_prefix.'_crmentity') {
					$query .= " ORDER BY $modTable.modifiedtime ASC, {$table_prefix}_crmentity.crmid ASC";
				} else {
					$query .= " ORDER BY $modTable.modifiedtime ASC, $baseTable.$idColumn ASC";
				}
			}
			// crmv@173184e
		}
		
		$queries = array('main' => $query, 'params' => $params, 'count' => $countQuery, 'idcolumn' => $idColumn);
		return $queries;
	}
	// crmv@164122e

	// TODO: add special related also (invitees....)
	// $crmid can be an array. in this case, optimized subcalls will be used
	// in this case the result will be in the format array(crmid1 => [lists], crmid2 => [lists]... )
	public function getAllRelated($module, $crmid) { // crmv@177095
		global $touchInst, $touchUtils;
		global $adb, $table_prefix;
		
		if (empty($this->relatedCache[$module])) {
			$req = array('module' => $module, 'type'=>'RELATED');
			$blocks = $this->subcall('GetBlocks', $req);
			if (isInventoryModule($module)) {
				$req = array('module' => $module, 'type'=>'PRODUCTS');
				$blocks_prod = $this->subcall('GetBlocks', $req);
				$blocks['blocks'] = array_merge($blocks['blocks'], $blocks_prod['blocks']);
			}
			$this->relatedCache[$module] = array_filter($blocks['blocks']);
		}
		
		$relateds = $this->relatedCache[$module];
		if (empty($relateds)) return array();

		$lists = array();
		foreach ($relateds as $relinfo) {
			$relationid = intval($relinfo['blockid']);
			if ($relationid <= 0) continue;
			
			if (is_array($crmid)) {
				$relatedModule = $relinfo['related_module'];
				$query = $this->getRelationQuery($module, $relinfo, $crmid);
				if ($query) {
					$res = $adb->limitQuery($query, 0, $this->listLimit * count($crmid));
					if ($res) {
						$rlists = array();
						while ($row = $adb->FetchByAssoc($res, -1, false)) {
							$mainCrmid = intval($row['crmid']);
							$relcrmid = intval($row['relcrmid']);
							if ($mainCrmid > 0 && $relcrmid > 0) {
								$rlists[$mainCrmid][] = $relcrmid;
							}
						}
						foreach ($rlists as $mainCrmid => $listrel) {
							$lists[$mainCrmid][] = array(
								'relationid' => $relationid,
								'module' => $module,
								'relmodule' => $relatedModule,
								'extra' => null,
								'total' => count($listrel),
								'list' => $listrel,
								'lineid' => null,
							);
						}
						$missing_crmids = array_diff($crmid, array_keys($rlists));
						foreach ($missing_crmids as $mainCrmid) {
							$lists[$mainCrmid][] = array(
								'relationid' => $relationid,
								'module' => $module,
								'relmodule' => $relatedModule,
								'extra' => null,
								'total' => 0,
								'list' => array(),
								'lineid' => null,
							);
						}

					}
				}
			} else {
				$onlyIds = 1;
				if ($relinfo['type'] == 'PRODUCTS') $onlyIds = 0;
			
				$crmRelationId = $this->getCrmRelationId($relationid);

				$req = array('module' => $module, 'recordid' => $crmid, 'relationid' => $crmRelationId, 'limit' => $this->listLimit, 'onlyids' => $onlyIds);
				$rlist = $this->subcall('GetRelated', $req);

				if ($rlist['success'] && $rlist['entries']) {
					$idlist = $touchUtils->arrayPluck('crmid', $rlist['entries']);
					$extra = ($onlyIds ? null : $touchUtils->arrayPluck('extrafields', $rlist['entries']));
					$lineidlist = ($onlyIds ? null : $touchUtils->arrayPluck('lineid', $rlist['entries']));
					$lists[] = array(
						'relationid' => $relationid,
						'module' => $module,
						'relmodule' => $relinfo['related_module'],
						'extra' => $extra,
						'total' => count($idlist),
						'list' => $idlist,
						'lineid' => $lineidlist,
					);
				}
			}
		}
		
		return $lists;
	}

	protected function getFakeRecordId($module) {
		global $adb, $table_prefix;
		$setype = $module;
		if ($setype == 'Events') $setype = 'Calendar';
		
		$crmid = 1234567;
		
		$res = $adb->limitpQuery("SELECT crmid FROM {$table_prefix}_crmentity WHERE setype = ? AND deleted = 0", 0, 1, array($setype)); // crmv@93148
		if ($res && $adb->num_rows($res) > 0) {
			$crmid = $adb->query_result_no_html($res, 0, 'crmid');
		}
		
		return intval($crmid) ?: 1234567;
	}
	
	protected function getRelationQuery($module, $blockinfo, $crmids) {
		global $touchInst, $touchUtils, $onlyquery;
		global $adb, $table_prefix;
		
		$crmids = array_filter(array_map('intval', $crmids));
		$relationId = $blockinfo['blockid'];
		$relatedModule = $blockinfo['related_module'];
		
		$crmRelationId = $this->getCrmRelationId($relationId);
		$relationInfo = getRelatedListInfoById($crmRelationId);
		$function_name = $relationInfo['functionName'];
		
		if (empty($function_name)) return null;
		
		if ($relatedModule == 'Calendar' || $relatedModule == 'Events') // crmv@54449
			$mod_listquery = "activity_listquery";
		else
			$mod_listquery = strtolower($relatedModule)."_listquery";
	
		$modObj = $touchUtils->getModuleInstance(($module == 'Events' ? 'Calendar' : $module));
		if (!$modObj) return null;
	
		$relmodObj = $touchUtils->getModuleInstance(($relatedModule == 'Events' ? 'Calendar' : $relatedModule)); // crmv@173184
		
		$onlyquery = true;
		$fakeRecordId = $this->getFakeRecordId($module);
		
		// crmv@177095
		if ($module == 'Messages') {
			global $current_user;
			$qgen = QueryGenerator::getInstance('Messages', $current_user);
			$qgen->setFields(array('id'));
			$qgen->appendToFromClause(
				"INNER JOIN {$table_prefix}_messagesrel ON {$table_prefix}_messagesrel.messagehash = {$table_prefix}_messages.messagehash
				INNER JOIN {$relmodObj->table_name} ON {$relmodObj->table_name}.{$relmodObj->table_index} = {$table_prefix}_messagesrel.crmid
				INNER JOIN {$table_prefix}_crmentity c2 ON c2.crmid = {$relmodObj->table_name}.{$relmodObj->table_index} AND c2.deleted = 0"
			);
			$qgen->appendToWhereClause("AND {$table_prefix}_messagesrel.module = '".$adb->sql_escape_string($relatedModule)."'");
			$qgen->appendToWhereClause("AND {$table_prefix}_messages.messagesid = ".intval($fakeRecordId));
			$query = $qgen->getQuery();
		} else {
			// no need to reopen the session, since I don't care to save it
			VteSession::set($mod_listquery, '');
			$relatedListData = $modObj->$function_name($fakeRecordId, $relationInfo['tabid'], $relationInfo['relatedTabId'], array());
			$query = VteSession::get($mod_listquery);
		}
		// crmv@177095e

		// remove the order by clause (it's slow)
		if (preg_match('/order by.+$/i', $query, $orderByMatch)) {
			$query = preg_replace('/\s*order by.+$/i', '', $query);
		}
		
		if (preg_match('/([^\s()]+)\s*=\s*'.$fakeRecordId.'/', $query, $matches)) {
			$relatedIndex = $matches[1];
			//$query = preg_replace('/(and|or)\s*[^\s()]+\s*=\s*'.$fakeRecordId.'/i', '', $query);
			$query = preg_replace('/=\s*'.$fakeRecordId.'/i', ' IN ('.implode(',', $crmids).')', $query);
			$query = preg_replace('/^select .+? from/i', "SELECT {$relmodObj->table_name}.{$relmodObj->table_index} as relcrmid, $relatedIndex AS crmid FROM", $query); // crmv@173184
			// crmv@177095
			if (in_array($table_prefix.'_crmentity', $modObj->tab_name)) {
				// now add a join with crmentity of first module because I have to filter by module
				$query = $this->sql_preg_replace('/\s+where\s+/i', " INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = $relatedIndex WHERE ", $query, 1);
				//and add the condition
				$query .= " AND crm1.setype = '$module'";
			}
			// crmv@177095e
		}
		
		// crmv@54449, really terrible tricks, please please, someone destroy that ugly calendar!!
		if ($relatedModule == 'Events') {
			// only events
			$query = preg_replace("/'Task'/", "'NOTATASK'", $query);
		} else {
			// only tasks
			$query = preg_replace("/activitytype in \(.*?\)/i", "activitytype = 'NOTANEVENT'", $query);
		}
		// crmv@54449e
		
		// restore the order by
		/*if ($orderByMatch[0]) {
			$query .= " ".$orderByMatch[0];
		}*/
		
		// now replace the crmid 
		return $query;
	}
	
	//TODO: this is duplicated code, please remove me!!
	// replaces pieces of a query, without replacing inside subqueries
	// assume subquery starts with "(select"
	protected function sql_preg_replace($regexp, $replace, $sql, $limit = -1) {
		$subQueries = array();
		
		// first replace subqueries with safe strings
		while (preg_match('/\(\s*select/i', $sql, $smatches, PREG_OFFSET_CAPTURE)) {
			$rpos1 = $smatches[0][1];
			$rpos2 = 0;
			$bcount = 1;
			// now walk to find the closing bracket
			for ($pos = $smatches[0][1]+1; $pos<strlen($sql); ++$pos) {
				if ($sql[$pos] == '(') ++$bcount;
				elseif ($sql[$pos] == ')') --$bcount;
				if ($bcount == 0) {
					$rpos2 = $pos+1;
					break;
				}
			}
			
			if ($rpos2 > $rpos1) {
				$subid = '###SUBQUERY'.count($subQueries).'###';
				$subQueries[$subid] = substr($sql, $rpos1, $rpos2-$rpos1);
				$sql = substr($sql, 0, $rpos1) . $subid . substr($sql, $rpos2);
			} else {
				// if the closing bracket wasn't found
				break;
			}
			
		}
		
		// now do the replace
		$sql = preg_replace($regexp, $replace, $sql, $limit);
		
		// and restore the subqueries
		if (count($subQueries) > 0) {
			$sql = str_replace(array_keys($subQueries), array_values($subQueries), $sql);
		}
		
		return $sql;
	}
	
	protected function getCrmRelationId($relationId) {
		global $touchUtils;
		
		if ($touchUtils->isInviteesRelated($relationId)) {
			// invitees
		} elseif ($touchUtils->isProductsRelated($relationId)) {
			// products
		} elseif ($touchUtils->isNotesRelated($relationId)) {
			// MyNotes			
		} elseif ($relationId >= $touchUtils->related_blockids['related_events']) {
			$relationId -= $touchUtils->related_blockids['related_events'];
		} else {
			$relationId -= $touchUtils->related_blockids['related'];
		}

		return $relationId;
	}
	
	// $crmid can be an array. in this case, optimized subcalls will be used
	// in this case the result will be in the format array(crmid1 => [lists], crmid2 => [lists]... )
	protected function getN1Related($module, $crmid) {
		global $touchUtils, $touchInst, $touchCache;

		$RM = RelationManager::getInstance();
		$relations = $RM->getRelations($module, ModuleRelation::$TYPE_NTO1, array(), $touchInst->excluded_modules);
		
		$lists = array();
		if (is_array($relations)) {
			foreach ($relations as $rel) {
				$relmodule = $rel->getSecondModule();
				
				// crmv@93148
				if (empty($this->relatedCache[$relmodule])) {
					$key = 'relcache_'.$relmodule;
					$relateds = $touchCache->get($key);
					if ($relateds === false) {
						$req = array('module' => $relmodule, 'type'=>'RELATED');
						$blocks = $this->subcall('GetBlocks', $req);
						$relateds = array_filter($blocks['blocks']);
						$touchCache->set($key, $relateds);
					}
					$this->relatedCache[$relmodule] = $relateds;
				}

				$relationid = null;
				$relateds = $this->relatedCache[$relmodule];
				// crmv@93148e
				
				// find correct related
				foreach ($relateds as $relinfo) {
					$relationid = intval($relinfo['blockid']) - $touchInst->related_blockids['related'];
					if ($relationid <= 0) continue;
					
					if ($relinfo['related_module'] == $module) break;
				}
				if (empty($relationid)) continue;
			
				if (is_array($crmid)) {
					$idlist = $this->getRelatedIdsNTO1($rel, $crmid);
					if (is_array($idlist) && count($idlist) > 0) {
						foreach ($idlist as $id => $listrel)  {
							$lists[$id][] = array(
								'relationid' => ($rel->relationid ? $rel->relationid : $relationid),
								'module' => $module,
								'relmodule' => $relmodule,
								'extra' => null,
								'total' => count($listrel),
								'list' => $listrel
							);
						}
					}
					$missing_crmids = array_diff($crmid, array_keys($idlist));
					foreach ($missing_crmids as $id) {
						$lists[$id][] = array(
							'relationid' => ($rel->relationid ? $rel->relationid : $relationid),
							'module' => $module,
							'relmodule' => $relmodule,
							'extra' => null,
							'total' => 0,
							'list' => array()
						);
					}
				} else {
					$idlist = $rel->getRelatedIds($crmid);
					$lists[] = array(
						'relationid' => ($rel->relationid ? $rel->relationid : $relationid),
						'module' => $module,
						'relmodule' => $relmodule,
						'extra' => null,
						'total' => count($idlist),
						'list' => $idlist
					);
				}
			}
		}

		return $lists;
	}
	
	// fast version of the ModuleRelation::getRelatedIds, which retrieves multiple ids at once
	protected function getRelatedIdsNTO1($relation, $crmids) {
		global $adb, $table_prefix, $touchInst, $touchUtils;
		$ret = array();
		
		if (!empty($relation->fieldid)) {
			// inline getFieldValue, optimized for many records
			$res = $adb->pquery(
				"select	{$table_prefix}_tab.name as modulename, fieldid, fieldname, tablename, columnname
				from {$table_prefix}_field
				inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
				where fieldid=? and {$table_prefix}_field.presence in (0,2)", array($relation->fieldid)
			);
			if ($res && $adb->num_rows($res) > 0) {

				$row = $adb->FetchByAssoc($res, -1, false);
				$focus = $touchUtils->getModuleInstance($row['modulename']);
				if (empty($focus)) return $ret;

				$indexname = $focus->tab_name_index[$row['tablename']];
				if (empty($indexname)) return $ret;

				if ($row['tablename'] != $table_prefix.'_crmentity') {
					$join = "inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$row['tablename']}.$indexname";
				} else {
					$join = "";
				}
				$res2 = $adb->pquery(
					"select {$row['tablename']}.$indexname AS srcid, {$row['tablename']}.{$row['columnname']} as fieldval 
					from {$row['tablename']} 
					$join 
					inner join {$table_prefix}_crmentity crm2 ON crm2.crmid = {$row['tablename']}.{$row['columnname']}
					where {$table_prefix}_crmentity.deleted = 0 AND crm2.setype = ? AND {$row['tablename']}.$indexname IN (".generateQuestionMarks($crmid).")",
					array($relation->getSecondModule(), $crmids)
					);
				if ($res2 && $adb->num_rows($res2) > 0) {
					while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
						$ret[$row2['srcid']][] = $row2['fieldval'];
					}
				}
			}
		}
		return $ret;
	}
	
	// REMOVE ME
	protected function getRecents() {
		$recents = array();
		
		$req = array('module' => '');
		$result = $this->subcall('GetRecents', $req);
		if ($result && $result['success']) {
			$recents = $result['list'];
		}
		
		return $recents;
	}

	// warning, retrieve all the folders, not only the one visible to the user
	/*function getFoldersForRecord($moduleInstance, $recordid) {
		global $adb, $table_prefix;

		$table = $moduleInstance->table_name;
		$index = $moduleInstance->table_index;

		$res = $adb->pquery("select crm.crmid from {$table_prefix}_crmentity crm inner join $table on $table.$index = crm.crmid where crm.deleted = 0 and $table.folderid = ?")
	}*/

	function getChangedUsers($sinceDate = null) {
		global $adb, $table_prefix, $metaLogs, $touchInst, $touchUtils, $current_user; // crmv@90935

		if (empty($sinceDate)) $sinceDate = $this->zeroDate;
		if (!$metaLogs) $metaLogs = MetaLogs::getInstance();

		$query = "SELECT id FROM {$table_prefix}_users WHERE deleted = 0 AND status = ? AND date_modified >= ?";
		$params = array('Active', $sinceDate);

		$list = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$sparams = array('userid' => $row['id']);
				$user = $this->subcall('GetUsers', $sparams);
				if ($user['success'] && $user['total'] > 0) {
					$list[] = $user['users'][0];
				}
			}
		}

		// now get also deleted users
		$delUsers = $metaLogs->getChanges($metaLogs::OPERATION_DELUSER, $sinceDate);
		if ($delUsers && count($delUsers) > 0) {
			// add a fake user, just to know something has changes
			$list[] = array('userid' => 0, 'deleted' => true);
		}

		//crmv@90935
		// check if my password has changed
		$changePwd = $metaLogs->getChanges($metaLogs::OPERATION_CHANGEUSERPWD, $sinceDate);
		if ($changePwd && count($changePwd) > 0) {
			foreach ($changePwd as $change) {
				if ($change['objectid'] == $current_user->id) {
					$list[] = array('userid' => $current_user->id, 'changed_password' => true);
				}
			}
		}
		//crmv@90935e

		// clear user cache
		if (count($list) > 0) {
			$touchUtils->clearWSCache('GetUsers');
		}
		
		return array('changes' => $list, 'total' => count($list));
	}

	function getChangedGroups($sinceDate = null) {
		global $adb, $table_prefix, $metaLogs, $touchUtils;

		if (empty($sinceDate)) $sinceDate = $this->zeroDate;
		if (!$metaLogs) $metaLogs = MetaLogs::getInstance();

		$query = "SELECT groupid FROM {$table_prefix}_groups WHERE date_modified >= ?";
		$params = array($sinceDate);

		$list = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$sparams = array('groupid' => $row['groupid']);
				$group = $this->subcall('GetGroups', $sparams);
				if ($group['success'] && $group['total'] > 0) {
					$list[] = $group['groups'][0];
				}
			}
		}

		// now get also deleted groups
		$delGroups = $metaLogs->getChanges($metaLogs::OPERATION_DELGROUP, $sinceDate);
		if ($delGroups && count($delGroups) > 0) {
			// add a fake user, just to know something has changes
			$list[] = array('groupid' => 0, 'deleted' => true);
		}
		
		// clear user cache
		if (count($list) > 0) {
			$touchUtils->clearWSCache('GetGroups');
		}

		return array('changes' => $list, 'total' => count($list));
	}

	function getMetaChanges($sinceDate = null) {
		global $adb, $table_prefix, $metaLogs, $touchCache;

		if (empty($sinceDate)) $sinceDate = $this->zeroDate;
		if (!$metaLogs) $metaLogs = MetaLogs::getInstance();

		// ignore these types of operations
		$ignoreTypes = array(
			$metaLogs::OPERATION_UNKNOWN,
			$metaLogs::OPERATION_REBUILDSHARES,
			$metaLogs::OPERATION_ADDUSER,
			$metaLogs::OPERATION_EDITUSER,
			$metaLogs::OPERATION_DELUSER,
			$metaLogs::OPERATION_ADDGROUP,
			$metaLogs::OPERATION_EDITGROUP,
			$metaLogs::OPERATION_DELGROUP,
			$metaLogs::OPERATION_CHANGEUSERPWD, // crmv@90935
			//crmv@146434
			$metaLogs::OPERATION_ADDPANEL,
			$metaLogs::OPERATION_DELPANEL,
			$metaLogs::OPERATION_EDITPANEL,
			$metaLogs::OPERATION_ADDRELATEDLISTTOTAB,
			$metaLogs::OPERATION_EDITRELATEDLISTTOTAB,
			$metaLogs::OPERATION_DELRELATEDLISTTOTAB,
			//crmv@146434e
		);

		$req = array('onlynames' => 1);
		$appModules = $this->subcall('ModulesList', $req);

		$modules = array();
		$changes = $metaLogs->getChanges(null, $sinceDate);
		if (is_array($changes)) {
			foreach ($changes as $k=>$c) {
				// exclude some
				if (in_array($c['operation'], $ignoreTypes)) {
					unset($changes[$k]);
					continue;
				}
				// get modules
				$mods = $metaLogs->getAffectedModules($c);
				$common = array_intersect($mods, $appModules);
				if (in_array('All', $mods) || count($common) > 0) {
					// module is relevant
					$changes[$k]['modules'] = $mods;
				} else {
					unset($changes[$k]);
				}
				// crmv@57366
				// now check if I need to keep it
				if (!$this->filterMetaChange($changes[$k])) {
					unset($changes[$k]);
				}
				// crmv@57366e
			}
		}
		
		// clear cache
		if (count($changes) > 0) {
			$touchCache->clear();
		}

		return array('changes'=>$changes, 'total'=>count($changes), 'last_timestamp' => time()); // crmv@107655
	}

	// crmv@57366
	function filterMetaChange($change) {
		global $adb, $table_prefix, $metaLogs;
		
		$filterchanges = array(
			$metaLogs::OPERATION_ADDFILTER,
			$metaLogs::OPERATION_EDITFILTER,
			// always pass the delete, because I don't know if it was mobile or not
			//$metaLogs::OPERATION_DELFILTER,
		);
	
		// check if it's a filter edit, of a non-mobile filter
		if (in_array($change['operation'], $filterchanges)) {
			$filterid = intval($change['objectid']);
			if ($filterid > 0) {
				$res = $adb->pquery("SELECT setmobile FROM {$table_prefix}_customview WHERE cvid = ?", array($filterid));
				if ($res && $adb->num_rows($res) > 0) {
					$isMobile = $adb->query_result_no_html($res, 0, 'setmobile');
					if (!$isMobile) return false;
				}
			}
		}
		
		return true;
	}
	// crmv@57366e
	
	// crmv@107655
	function getEmailDirectory($sinceDate = null) {
		
		$list = array();
		
		$fakeRequest = array(
			'since' => $sinceDate ? strtotime($sinceDate) : 0,
		);
		$result = $this->subcall('GetEmailDirectory', $fakeRequest);
		if ($result['success']) {
			$list = $result['entries'];
		}
		
		return array('entries' => $list, 'total' => count($list), 'last_timestamp' => time());
	}
	// crmv@107655e

}