<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetList extends TouchWSClass {

	function process(&$request) {
		global $adb, $table_prefix;
		global $current_user, $currentModule, $touchInst, $touchUtils;
		global $current_folder, $current_account; // for Messages

		$module = $request['module'];
		$searchstr = $request['search'];
		$viewid = intval($request['viewid']);
		$folderid = intval($request['folderid']); // automatically used by modules
		if ($folderid <= 0) unset($request['folderid']);
		$req_sorder = $request['sorder'];
		$req_sortby = vtlib_purify($request['sfield']);
		$extraFields = Zend_Json::decode($request['extrafields']);
		$extraFieldsRaw = ($request['extrafields_raw'] == '1' ? true : false); // if true, extrafields won't be formatted for the display
		$whereConditions = Zend_Json::decode($request['conditions']);
		$onlyCrmid = ($request['onlyids'] == '1'); // crmv@73256

		$pageLimit = intval($request['limit']);
		if ($pageLimit <= 0) $pageLimit = $touchInst->listPageLimit;

		$currentModule = $module;

		// pulisco request
		unset($request['sorder'], $request['sfield'], $request['extrafields']);

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
				$mail_folder = mb_convert_encoding(html_entity_decode($request['mail_folder'], ENT_QUOTES, 'UTF-8'), "UTF7-IMAP", "UTF-8" ); //crmv@61520
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
			//$viewid = $customView->getViewId($currentModule);
			//$viewinfo = $customView->getCustomViewByCvid($viewid);
			list($focus->customview_order_by,$focus->customview_sort_order) = $customView->getOrderByFilterSQL($viewid);
			$sorder = $focus->getSortOrder();
			$order_by = $focus->getOrderBy();
			
			// crmv@54561 crmv@54449
			if ($viewid == 0 && !empty($_REQUEST['viewname'])) {
				// get the viewid from the name (stored in the request)
				$_REQUEST['action'] = 'index';
				$vid = $customView->getViewId($module);
				if ($vid > 0) $viewid = $vid;
				unset($_REQUEST['viewname'], $_REQUEST['action']);
			}
			// crmv@54561e crmv@54449e

			// richiesto ordinamento diverso
			if (!empty($req_sortby) && in_array($req_sorder, array('ASC', 'DESC'))) {
				$order_by = $this->getFieldColumn($module, $req_sortby);
				$sorder = $req_sorder;
			}

			// campi per il nome
			$nameFields = vtws_getEntityNameFields($module);
			if (is_array($nameFields) && empty($sorder)) {
				// trovo il nome della colonna se non c'è ordinamento
				$order_by = $this->getFieldColumn($module, $nameFields[0]);
			}

			// nomi delle colonne per campi extra
			$extraFields_col = array();
			if (is_array($extraFields))
				foreach ($extraFields as $nf) $extraFields_col[$nf] = $this->getFieldColumn($module, $nf);

			$queryGenerator = QueryGenerator::getInstance($module, $current_user);
			if ($viewid > 0) {
				$queryGenerator->initForCustomViewById($viewid);
			} else {
				$queryGenerator->initForDefaultCustomView();
			}

			// aggiungo campi alla query così posso ordinare
			if (!empty($order_by)) {
				$fname = $this->getFieldNameFromColumn($module, $order_by);
				$queryGenerator->addField($fname);
				VteSession::set($module.'_ORDER_BY', $order_by);
				foreach ($nameFields as $nf)
					$queryGenerator->addField($nf);
			}
			if (is_array($extraFields))
				foreach ($extraFields as $nf) $queryGenerator->addField($nf);
			// per aggiungere join con altri moduli in caso di ordinamento per campi reference
			$queryGenerator->addField('crmid');

			if ($searchstr != '') {
				$searchRequest = array();
				$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
				$searchFields = $nameFields;
				if (is_array($extraFields)) $searchFields = array_merge($searchFields, $extraFields);
				$searchRequest['search_fields'] = array_flip($searchFields);
				$searchRequest['search_text'] = $searchstr;
				$queryGenerator->addUserSearchConditions($searchRequest);
			}

			if ($module == 'Events') {
				$queryGenerator->addField('subject');
				$queryGenerator->addField('visibility'); // crmv@92772
			}

			$unionConditions = array();

			// crmv@101353 crmv@103023
			// add custom conditions to query
			if (!empty($whereConditions)) {
				$inGroup = false;
				$queryGenerator->startGroup('');
				foreach ($whereConditions as $wc) {
					if ($wc['type'] == 'daterange') {
						// this is used to optimize the calendar query
						$range = $wc['range'];
						$startCol = $this->getFieldColumn($module, $wc['fields'][0], true);
						$endCol = $this->getFieldColumn($module, $wc['fields'][1], true);
						$unionConditions = array(
							array(
								"($startCol between ? AND ?)",
								array($range[0], $range[1])
							),
							array(
								"($startCol < ? AND $endCol > ?)",
								array($range[0], $range[1])
							),
							array(
								"($endCol between ? AND ?)",
								array($range[0], $range[1])
							),
						);
					} elseif ($wc['type'] == 'group') {
						if (!$inGroup) $queryGenerator->startGroup('');
						$inGroup = true;
						$groupglue = ($wc['glue'] ? $wc['glue'] : 'and');
						for ($i=0; $i<count($wc['conds']); ++$i) {
							$stat = $wc['conds'][$i];
							$queryGenerator->addCondition($stat['field'], $stat['value'], $stat['operator']);
							if ($i < count($wc['conds'])-1) $queryGenerator->addConditionGlue(' '.$groupglue.' ');
						}
						$queryGenerator->endGroup();
						$inGroup = false;
					} elseif ($wc['type'] == 'groupglue') {
						if (!$inGroup) $queryGenerator->startGroup($wc['glue'] ? $wc['glue'] : 'and');
						$inGroup = true;
					}
					if ($wc['last'] == true) break;
				}
				if ($inGroup) $queryGenerator->endGroup();
				$queryGenerator->endGroup();
			}
			// crmv@101353e crmv@103023e

			$list_query = $queryGenerator->getQuery();

			// crmv@48677
			// fix for users module when I'm not admin
			if ($searchstr != '' && $module == 'Users' && !is_admin($current_user)) {
				$searchstr = addslashes(substr($searchstr, 0, 50));
				$list_query .= " AND (first_name LIKE '%$searchstr%' OR last_name LIKE '%$searchstr%')";
			}
			// crmv@48677e

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
						$groupSql = "OR {$table_prefix}_groups.groupid IN (".implode(',', $current_user_groups).")";
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
						$groupSql = "OR {$table_prefix}_groups.groupid IN (".implode(',', $current_user_groups).")";
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

			// crmv@103023
			
			// crmv@73256 crmv@173184
			if ($onlyCrmid) {
				if (!in_array($table_prefix.'_crmentity',$focus->tab_name))
					$list_query = preg_replace('/^select .*? from/i', "SELECT {$focus->table_name}.{$focus->table_index} FROM", $list_query);
				else
					$list_query = preg_replace('/^select .*? from/i', "SELECT {$table_prefix}_crmentity.crmid FROM", $list_query);
			}
			// crmv@73256e crmv@173184e
			
			// conteggio
			$list_count = 0;
			
			// optimize the calendar query with unions
			if (count($unionConditions) > 0) {
				$list_query = $adb->makeUnionSelect($list_query, $unionConditions);
				$count_query = "SELECT COUNT(*) AS cnt FROM ($list_query) listtable";
			} else {
				$count_query = replaceSelectQuery($list_query,'count(*) as cnt');
			}
			
			$res = $adb->query($count_query);
			if ($res){
				$list_count = $adb->query_result($res,0,'cnt');
			}

			// ordinamento
			if (!empty($order_by) && !empty($sorder)) {
				/* crmv@127820 */
				if (count($unionConditions) > 0) {
					$ordercol = trim($focus->getFixedOrderBy($currentModule,$order_by,$sorder));
					if (preg_match('/^order by\s+([^.]+\.)?([^\s]+)/i',$ordercol, $matches)) {
						$tab = str_replace('.', '', $matches[1]);
						$col = $matches[2];
						if ($tab && $col) {
							$list_query = "SELECT * FROM ($list_query) $tab $ordercol";
						}
						
					}
				} else {
					$list_query .= $focus->getFixedOrderBy($currentModule,$order_by,$sorder); //crmv@25403
				}
			}
			
			// crmv@103023e

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

			if ($module == 'Messages') {
				$fetched = false;
				// crmv@107655
				$_REQUEST['app_key'] = '12345';
				$_REQUEST['service'] = 'Messages';
				try {
					// if force, get new emails
					if ($request['force_fetch'] == '1') {
						$focus->fetch($current_account, $current_folder, true);
						$fetched = true;
					// get old emails if request last or last-1 page
					} elseif ($page >= $totpages-1) {
						$focus->fetch($current_account, $current_folder, false);
						$fetched = true;
					}
				} catch (Exception $e) {
					$fetched = false;
					return $this->error('Unable to retrieve messages. Please check connection parameters.');
				}
				// crmv@107655e
				if ($fetched) {
					// and count again
					$res = $adb->query($count_query);
					$list_count = $adb->query_result($res,0,'cnt');
				}
			}

			$result = $adb->limitQuery($list_query, $start,$list_max_entries_per_page);

			if ($module == 'Messages') {
				// if 0 results, it might be the first time, download them!
				if ($result && $adb->num_rows($result) == 0 && $request['force_fetch'] != '1') {
					$focus->fetch($current_account, $current_folder, true);
					$result = $adb->limitQuery($list_query, $start,$list_max_entries_per_page);
				}

			}

			$resultArray = array();
			if ($result && $adb->num_rows($result) > 0)	{
				while ($row = $adb->fetchByAssoc($result)) {
					$nrow = array();
					$nrow['tabid'] = $tabid;
					$nrow['module'] = $module;
					// crmv@73256
					if ($onlyCrmid && array_key_exists('crmid', $row)) { // crmv@173184
						$nrow['crmid'] = $row['crmid'];
					} else {
						$nrow['crmid'] = $row[$focus->tab_name_index[$focus->table_name]];
					}
					// crmv@73256e
					if ($module == 'Events') {
						// crmv@92772
						$row['assigned_user_id'] = $row['smownerid']; // crmv@187823
						if ($focus->hasMaskedFields($nrow['crmid'], $row)) { // crmv@187823
							$entname = getTranslatedString('Private Event', 'Calendar');
						} else {
							$entname = $row['subject'];
						}
						// crmv@92772e
						// crmv@187823
						$nrow['perm_read'] = (isPermitted('Calendar', 'DetailView', $nrow['crmid']) == 'yes');
						$nrow['perm_write'] = (isPermitted('Calendar', 'EditView', $nrow['crmid']) == 'yes');
						$nrow['perm_delete'] = (isPermitted('Calendar', 'Delete', $nrow['crmid']) == 'yes');
						// crmv@187823e
					} elseif ($nrow['crmid'] > 0) {
						$entname = $touchUtils->getEntityNameFromFields($module, $nrow['crmid'], $row);
					} else {
						foreach ($nameFields as $nf)
							$entname .= $row[$nf]." ";
					}
					$nrow['entityname'] = html_entity_decode($entname, ENT_QUOTES, 'UTF-8');
					
					// crmv@73256
					if ($onlyCrmid) {
						$resultArray[] = $nrow;
						continue;
					}
					// crmv@73256e

					// aggiunta campi speciali
					if ($module == 'Messages') {

						if (is_array($specialFolders) && in_array($current_folder, array($specialFolders['Sent'], $specialFolders['Drafts']))) {
							$mto = $row['mto'];
							$mto_n = $row['mto_n'];
							$mto_f = $row['mto_f'];
						} else {
							$mto = $row['mfrom'];
							$mto_n = $row['mfrom_n'];
							$mto_f = $row['mfrom_f'];
						}
						$from_or_to = $focus->getAddressName($mto,$mto_n,$mto_f,false);
						$row['from_or_to'] = $from_or_to;
						$extraFields_col['from_or_to'] = 'from_or_to';

						$row['has_attachments'] = ($focus->haveAttachments($nrow['crmid'], null) ? '1' : '0'); // crmv@48677
						$extraFields_col['has_attachments'] = 'has_attachments';

						if (array_key_exists('mdate', $row)) {
							$row['mdate_friendly'] = $focus->getFriendlyDate($row['mdate']);
							$extraFields_col['mdate_friendly'] = 'mdate_friendly';
						}

						$row['has_relations'] = ($focus->haveRelations($nrow['crmid']) ? '1' : '0');
						$extraFields_col['has_relations'] = 'has_relations';

						$row['has_comments'] = ($focus->haveRelations($nrow['crmid'], 'ModComments') ? '1' : '0');
						$extraFields_col['has_comments'] = 'has_comments';
					}

					// campi aggiuntivi
					if (is_array($extraFields) && count($extraFields) > 0) {
						global $default_charset; // crmv@158468
						$efields = array();
						$emptyValue = ($extraFieldsRaw ? '' : '-');
						$focus->column_fields = $row; // crmv@53679
						foreach ($extraFields as $fname) {
							$rawval = $row[$extraFields_col[$fname]];
							if (is_null($rawval) || $rawval === '') {
								$efields[$fname] = $emptyValue;
							} else {
								$procval = $touchInst->field2Touch($module, $fname, $rawval, !$extraFieldsRaw, $focus);
								$procval = $this->adjustFields($module, $fname, $procval, $focus); // crmv@53679
								if (is_null($procval) || $procval === '')
									$procval = $emptyValue;
								// crmv@158468
								elseif (!$extraFieldsRaw && mb_strlen($procval, $default_charset) > 60)
									$procval = mb_substr($procval, 0, 57, $default_charset).'...';
								// crmv@158468e

								if ($extraFieldsRaw && is_string($procval)) { // crmv@119869
									// re-encode
									$procval = html_entity_decode($procval, ENT_QUOTES, 'UTF-8');
								}
								$efields[$fname] = $procval;
							}
						}
						$nrow['extrafields'] = $efields;
					}

					$resultArray[] = $nrow;
				}
			}

		return $this->success(array('entries' => $resultArray, 'total' => $list_count));

	}

	// crmv@103023
	// ritorna il nome della colonna per un certo campo
	protected function getFieldColumn($module, $fieldname, $withTable = false) {
		global $adb, $table_prefix;

		$ret = false;
		$q = "
		select tablename, columnname
			from {$table_prefix}_field f
			inner join {$table_prefix}_tab t on t.tabid = f.tabid
		where t.name = ? and f.fieldname = ?";

		$res = $adb->pquery($q, array($module, $fieldname));
		if ($res && $adb->num_rows($res) > 0) {
			$column = $adb->query_result_no_html($res, 0, 'columnname');
			$table = $adb->query_result_no_html($res, 0, 'tablename');
			if ($withTable) {
				$ret = $table.".".$column;
			} else {
				$ret = $column;
			}
		}
		return $ret;
	}
	// crmv@103023e

	protected function getFieldNameFromColumn($module, $fieldcol) {
		global $adb, $table_prefix;

		$ret = false;
		$q = "
		select fieldname
			from {$table_prefix}_field f
			inner join {$table_prefix}_tab t on t.tabid = f.tabid
		where t.name = ? and f.columnname = ?";

		$res = $adb->pquery($q, array($module, $fieldcol));
		if ($res && $adb->num_rows($res) > 0) {
			$ret = $adb->query_result_no_html($res, 0, 'fieldname');
		}
		return $ret;
	}
	
	// crmv@53679
	// TODO: change the old call to the new one
	protected function adjustFields($module, $fieldname, $fieldvalue, &$focus) {
		global $current_user;
		
		if ($focus && $module == 'Events') {
			if ($fieldname == 'date_start') {
				$newval = $fieldvalue.' '.$focus->column_fields['time_start'];
				$newval = adjustTimezone($newval, $current_user->timezonediff);
				//$newval = adjustTimezone($newval, 0, null, false);
				if ($newval) $fieldvalue = substr($newval, 0, 10);
			} elseif ($fieldname == 'due_date') {
				$newval = $fieldvalue.' '.$focus->column_fields['time_end'];
				$newval = adjustTimezone($newval, $current_user->timezonediff);
				//$newval = adjustTimezone($newval, 0, null, false);
				if ($newval) $fieldvalue = substr($newval, 0, 10);
			} elseif ($fieldname == 'time_start') {
				$newval = substr($focus->column_fields['date_start'], 0, 10).' '.$fieldvalue; // crmv@139338
				$newval = adjustTimezone($newval, $current_user->timezonediff);
				//$newval = adjustTimezone($newval, 0, null, false);
				if ($newval) $fieldvalue = substr($newval, 11, 5);
			} elseif ($fieldname == 'time_end') {
				$newval = substr($focus->column_fields['due_date'], 0, 10).' '.$fieldvalue; // crmv@139338
				$newval = adjustTimezone($newval, $current_user->timezonediff);
				//$newval = adjustTimezone($newval, 0, null, false);
				if ($newval) $fieldvalue = substr($newval, 11, 5);
			}
		}
		return $fieldvalue;
	}
	// crmv@53679e

}
