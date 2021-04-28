<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetFilterList extends TouchWSClass {

	public $pageLimit = 5000;

	function process(&$request) {
		global $current_user, $currentModule, $touchInst;

		$module = $request['module'];
		$modules = Zend_Json::decode($request['modules']);
		$viewid = intval($request['viewid']);
		$folderid = intval($request['folderid']); // automatically used by modules
		if ($folderid <= 0) unset($request['folderid']);

		// pulisco request
		unset($request['sorder'], $request['sfield'], $request['extrafields']);
		
		if (!is_array($modules)) $modules = array();
		if ($module) $modules[] = $module;

		$modWsClass = $touchInst->getWSClassInstance('ModulesList', $this->requestedVersion);

		$globalTotal = 0;
		foreach ($modules as $module) {

			$filterIds = array();
			$folderIds = array();

			if ($viewid > 0) {
				$filterIds[] = $viewid;
			} elseif ($folderid > 0) {
				$folderIds[] = $folderid;
			} else {
				// all lists

				$filtList = $modWsClass->getFilterList($module);
				$foldList = $modWsClass->getFoldersList($module);

				foreach ($filtList as $f) {
					$filterIds[] = $f['cvid'];
				}

				foreach ($foldList as $f) {
					$folderIds[] = $f['folderid'];
				}
			}

			$filters = array();
			$folders = array();

			foreach ($filterIds as $cvid) {
				$list = $this->getSingleList($module, $cvid, 0, $request);
				$total = count($list);
				$filters[] = array(
					'filterid' => $cvid,
					'total' => $total,
					'list' => $list,
				);
				$globalTotal += $total;
			}

			foreach ($folderIds as $foldid) {
				$list = $this->getSingleList($module, 0, $foldid, $request);
				$total = count($list);
				$folders[] = array(
					'folderid' => $foldid,
					'total' => $total,
					'list' => $list,
				);
				$globalTotal += $total;
			}

			$globalResult[] = array(
				'module' => $module,
				'filters' => $filters,
				'folders' => $folders,
			);
		}

		return array('success'=>true, 'lists' => $globalResult, 'total' => $globalTotal);

	}
	
	// crmv@54449
	function getSingleList($module, $filterid, $folderid, &$request) {
		global $adb, $table_prefix, $touchInst, $touchUtils;
		global $currentModule, $current_user;
		global $current_folder, $current_account; // for Messages

		$pageLimit = intval($request['limit']);
		if ($pageLimit <= 0) $pageLimit = $this->pageLimit;

		$currentModule = $module;

		if ($folderid > 0) {
			$_REQUEST['folderid'] = $folderid;
		} else {
			unset($_REQUEST['folderid']);
		}

		$focus = $touchUtils->getModuleInstance($module);
		$tabid = GetTabid($module);

		if ($module == 'Messages') {

			//config account
			$mail_accountid = vtlib_purify($request['mail_accountid']);
			if ($mail_accountid) $current_account = $mail_accountid;

			if (empty($current_account)) {
				$current_account = $focus->getMainUserAccount();
				$current_account = $current_account['id'];
			}
			$focus->setAccount($current_account);
			if ($current_account != 'all')
				$specialFolders = $focus->getSpecialFolders();

			// config folder
			$mail_folder = $request['mail_folder'];
			if ($mail_folder) $current_folder = $mail_folder;

			if (empty($current_folder)) {
				$current_folder = $specialFolders['INBOX'];
			}

		} elseif ($module == 'Events') {
			// for calendar, enable view of other people events
			if (!empty($request['show_users'])) {
				$show_cal_users = array_filter(explode(',', $request['show_users']));
				$available_shareds = array_keys($focus->getShownUserId($current_user->id));
				$show_cal_users = array_intersect($show_cal_users, $available_shareds);
			}
		}


		// inizializzo ordinamento
		$customView = CRMEntity::getInstance('CustomView', $currentModule); // crmv@115329


		$queryGenerator = QueryGenerator::getInstance($module, $current_user);
		if ($filterid > 0) {
			$queryGenerator->initForCustomViewById($filterid);
		} else {
			$queryGenerator->initForDefaultCustomView();
		}

		// per aggiungere join con altri moduli in caso di ordinamento per campi reference
		$queryGenerator->addField('crmid');
		
		// crmv@84383
		if ($module == 'ModComments') {
			require_once('modules/ModComments/widgets/DetailViewBlockComment.php');
			$widget = new ModComments_DetailViewBlockCommentWidget();
			$list_query = $widget->getBaseQuery(null);
		} else {
			$list_query = $queryGenerator->getQuery();
		}
		// crmv@84383e

		// crmv@181863
		if (!in_array($table_prefix.'_crmentity',$focus->tab_name))
			$list_query = preg_replace('/^select\s+.*?\s+from/i', "SELECT {$focus->table_name}.{$focus->table_index} FROM", $list_query);
		else
			$list_query = preg_replace('/^select\s+.*?\s+from/i', "SELECT {$table_prefix}_crmentity.crmid FROM", $list_query);
		// crmv@181863e

		// prendo solo i compiti
		if ($module == 'Calendar') {
			$list_query .= " AND {$table_prefix}_activity.activitytype = 'Task'";
			
		} elseif ($module == 'Events') {
			// solo i miei eventi (dei miei gruppi o quelli a cui sono invitato)
			$list_query .= " AND {$table_prefix}_activity.activitytype not in ('Task', 'Emails') "; // crmv@152701
			if (count($show_cal_users) > 0) {
				$show_cal_users[] = $current_user->id;
				$show_cal_users = array_map('intval', $show_cal_users);
				
				// add query for groups
				$groupSql = '';
				
				if (!is_admin($current_user)) { // crmv@159553
					require_once('modules/Users/CreateUserPrivilegeFile.php');
					$userGroupFocus = new GetUserGroups();
					$userGroupFocus->getAllUserGroups($current_user->id);	
					$current_user_groups = $userGroupFocus->user_groups;
				} else {
					$allGroups = array();
					foreach ($show_cal_users as $calUser) {
						require('user_privileges/user_privileges_'.$calUser.'.php');
						require('user_privileges/sharing_privileges_'.$calUser.'.php');
						if (count($current_user_groups) > 0) {
							$allGroups = array_merge($allGroups, $current_user_groups);
						}
					}
					$current_user_groups = array_unique(array_filter($allGroups));
				}
				if (count($current_user_groups) > 0) {
					$groupSql = "OR {$table_prefix}_crmentity.smownerid IN (".implode(',', $current_user_groups).")";
				}					
				
				$list_query .= "
					AND ( {$table_prefix}_crmentity.smownerid IN (".implode(',', $show_cal_users).")
						$groupSql
				 		OR {$table_prefix}_activity.activityid IN (
							SELECT activityid
							FROM {$table_prefix}_invitees
							WHERE {$table_prefix}_activity.activityid > 0 AND inviteeid IN (".implode(',', $show_cal_users).")
						)
					)";
			} else {
				// only me
				require('user_privileges/user_privileges_'.$current_user->id.'.php');
				require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
				if ($current_user->is_admin == 'on') {					
					require_once('modules/Users/CreateUserPrivilegeFile.php');
					$userGroupFocus = new GetUserGroups();
					$userGroupFocus->getAllUserGroups($current_user->id);	
					$current_user_groups = $userGroupFocus->user_groups;					
				}
				if (count($current_user_groups) > 0) {
					$groupSql = "OR {$table_prefix}_crmentity.smownerid IN (".implode(',', $current_user_groups).")";
				} else {
					$groupSql = '';
				}
			
				$list_query .= "
					AND ( {$table_prefix}_crmentity.smownerid = '{$current_user->id}'
						$groupSql
						OR {$table_prefix}_activity.activityid IN (
							SELECT activityid
							FROM {$table_prefix}_invitees
							WHERE {$table_prefix}_activity.activityid > 0 AND inviteeid = '{$current_user->id}'
						)
					)";
			}
		// crmv@154947
		} elseif ($module == 'MyNotes') {
			// force owner for notes
			$list_query .= " AND {$table_prefix}_crmentity.smownerid = {$current_user->id}";
		}
		// crmv@154947e

		// ordinamento (get the latest), and for the Talks, the unread first
		if ($module == 'ModComments') {
			// crmv@84383 - already done in the main query
		// crmv@181863
		} elseif (!in_array("{$table_prefix}_crmentity", $focus->tab_name)) { // crmv@200661
			$list_query .= " ORDER BY {$focus->table_name}.modifiedtime DESC";
		// crmv@181863e
		} else {
			$list_query .= " ORDER BY {$table_prefix}_crmentity.modifiedtime DESC";
		}

		// disabilito azioni per la lista
		PerformancePrefs::setTemp('LISTVIEW_RECORD_CHANGE_INDICATOR', false, 'request'); // crmv@115378

		// imposto il massimo numero di elementi
		$list_max_entries_per_page = $pageLimit;
		// e l'inizio
		$page = $request['page'];
		if (empty($page)) $page = 1;
		$start = ($page-1)*$list_max_entries_per_page;
		$totpages = ceil($list_count / $list_max_entries_per_page);

		unset($request['page'], $request['limit'], $request['start']);

		$result = $adb->limitQuery($list_query, $start,$list_max_entries_per_page);

		$resultArray = array();
		if ($result && $adb->num_rows($result) > 0)	{
			while ($row = $adb->fetchByAssoc($result)) {
				$crmid = $row['crmid'];
				$resultArray[] = intval($crmid);
			}
		}

		return $resultArray;
	}
	// crmv@54449e

}
