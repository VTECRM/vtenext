<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetRelated extends TouchWSClass {

	public $defaultPageLimit = 5000;	// crmv@73256 - default value for page size if no specified

	function process(&$request) {
		global $adb, $table_prefix;
		global $current_user, $currentModule, $touchInst, $touchUtils;

		$module = $request['module'];
		$recordid = intval($request['recordid']);
		$relationId = intval($request['relationid']);
		$actions = $request['actions'];
		$page = intval($request['page']);
		$limit = intval($request['limit']);
		$onlyCrmid = ($request['onlyids'] == '1');

		$currentModule = $module;
		
		$prefModule = null;
		if ($relationId >= $touchUtils->related_blockids['related_events']) {
			$prefModule = 'Events';
		}
		
		if (empty($relationId)) return $this->error('Relation ID not specified');
		if (empty($recordid)) return $this->error('Record ID not specified');

		// crmv@99953 crmv@115378
		// disabilito azioni per la lista
		global $list_max_entries_per_page, $related_list_limit;
		
		PerformancePrefs::setTemp('LISTVIEW_RECORD_CHANGE_INDICATOR', false, 'request');
		
		// crmv@115378e

		// imposto il massimo numero di elementi
		$list_max_entries_per_page = ($limit > 0 ? $limit : $touchInst->listPageLimit);
		$related_list_limit = $list_max_entries_per_page;
		// e l'inizio (clear some request values since they are used somewhere else)
		unset($_REQUEST['limit']);
		unset($_REQUEST['page']);
		$_REQUEST['start'] = $page;
		// crmv@99953e

		$outdata = array();
		$total = 0;
		if ($recordid > 0) {

			// crmv@73256
			if ($module == 'Events' && $touchUtils->isInviteesRelated($relationId )) {
				return $this->getInvitees($request, $recordid, $relationId, $onlyCrmid);
			} elseif (isInventoryModule($module) && $touchUtils->isProductsRelated($relationId)) {
				return $this->getProducts($request, $recordid, $relationId, $onlyCrmid);
			} elseif ($touchUtils->isNotesRelated($relationId)) {
				// MyNotes						
				return $this->getNotes($request, $recordid, $relationId, $onlyCrmid);
			} elseif ($prefModule == 'Events') {
				$relationId -= $touchUtils->related_blockids['related_events'];
			}
			// crmv@73256e

			// prendo la lista
			$modObj = $touchUtils->getModuleInstance($currentModule);
			$modObj->retrieve_entity_info($recordid,$currentModule);
			$modObj->id = $recordid;

			$relationInfo = getRelatedListInfoById($relationId);
			$function_name = $relationInfo['functionName'];
			$relatedModule = vtlib_getModuleNameById($relationInfo['relatedTabId']);

			if (empty($function_name)) return $this->error('Unable to find the relation function');

			$relatedListData = $modObj->$function_name($recordid, getTabid($currentModule),$relationInfo['relatedTabId'], $actions);
			$total = intval($relatedListData['count']);

			// prendo i campi
			$relatedFields = $touchUtils->getRelatedFields($current_user->id, $module, $relatedModule, $relationId, $recordid);


			// formatto nel formato nome->valore

			$relatedInst = $touchUtils->getModuleInstance($relatedModule);

			if (is_array($relatedListData['entries'])) {
				foreach ($relatedListData['entries'] as $relcrmid => $relfields) {
					$i = 0;
					$crmid = 0;
					$fielddata = array();

					// recupero il crmid
					foreach ($relfields as $fieldval) {
						if($crmid == 0 && preg_match('/vtrecordid=[\'"](\d+)[\'"]/', $fieldval, $matches) > 0) {
							$crmid = $matches[1];
							break;
						}
					}

					if (empty($crmid)) continue;

					if (!$onlyCrmid) {

						// recupero i valori dei campi in modo decente
						$relatedInst->retrieve_entity_info_no_html($crmid, $relatedModule, false);
						$record = $relatedInst->column_fields;

						$outSetype = $relatedModule;

						// e faccio il merge
						foreach ($relfields as $fieldval) {
							// nome del campo
							if (preg_match('/vtfieldname=[\'"]([a-zA-Z0-9_]+)[\'"]/', $fieldval, $matches) > 0) {
								$fldname = $matches[1];
							}
							if (empty($fldname)) $fldname = $relatedFields[$i++]['name']; // vado in ordine
							// valore
							if (array_key_exists($fldname, $record)) $fielddata[$fldname] = $touchInst->field2touch($relatedModule, $fldname, $record[$fldname], false, $relatedInst);
							// calendar fix
							if ($relatedModule == 'Calendar' && $fldname == 'activitytype' && $fielddata[$fldname] != 'Task') {
								$outSetype = 'Events';
							}
						}

						$entname = $touchUtils->getEntityNameFromFields($relatedModule, $crmid, $record);

						$outdata[] = array(
							'crmid' => $crmid,
							'tabid' => $relationInfo['relatedTabId'],
							'module' => $outSetype,
							'entityname' => $entname,
							'extrafields' => $fielddata,
						);

					} else {
						$outdata[] = array(
							'crmid' => $crmid,
						);
					}


				}
			}
		}

		return $this->success(array('entries'=>$outdata, 'total'=>$total));
	}
	
	function getProducts(&$request, $recordid, $relationid, $onlyCrmid = false) {
		global $touchInst, $touchUtils;
		
		$module = $request['module'];
		
		$req = array('module' => $module, 'record'=>$recordid);
		$result = $this->subcall('GetAssociatedProducts', $req);
		
		if (!$result['success']) {
			return $result;
		}
		
		$permitted = (isPermitted($module, 'EditView', $recordid) == 'yes');
		
		$total = $result['total'];
		$outdata = array();
		if (is_array($result['entries'])) {
			foreach ($result['entries'] as $prodline) {
				$crmid = $prodline['crmid'];
				
				$row = array();
				$row['crmid'] = $crmid;
				$row['lineid'] = $prodline['lineid'];

				if (!$onlyCrmid) {
					$fields = $prodline;
					$row['entityname'] = $prodline['entityname'];
					$row['module'] = $prodline['entityType'];
					$row['extrafields'] = $fields;
					$row['perm_delete'] = $permitted;
				}
				$outdata[] = $row;
			}
		}
		
		return $this->success(array('entries'=>$outdata, 'total'=>$total));
	}

	// TODO: pagination
	function getInvitees(&$request, $recordid, $relationid, $onlyCrmid = false) {
		global $adb, $table_prefix, $touchInst, $touchUtils;
		global $current_user, $currentModule;

		if ($relationid == $touchUtils->related_blockids['invitees']) {
			$relmod = 'Contacts';
			$table = $table_prefix.'_invitees_con';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_crmentity c on c.crmid = i.inviteeid
				where i.activityid = ? and c.deleted = 0 and c.setype = ?";
			$params = array($recordid, $relmod);
		} elseif ($relationid == $touchUtils->related_blockids['invitees']+1) {
			$relmod = 'Users';
			$table = $table_prefix.'_invitees';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_users u on u.id = i.inviteeid
				where i.activityid = ? and u.deleted = 0";
			$params = array($recordid);
		} else {
			return $this->error('Invalid Module');
		}

		$total = 0;
		$outdata = array();

		$countQuery = replaceSelectQuery($query, 'count(*) as cnt');
		$resCount = $adb->pquery($countQuery, $params);
		if ($resCount) {
			$total = $adb->query_result_no_html($resCount, 0, 'cnt');
		}

		$partValues = array(
			0 => '',
			1 => GetTranslatedString('LBL_NO'),
			2 => GetTranslatedString('LBL_YES'),
		);
		$permitted = (isPermitted('Calendar', 'EditView', $recordid) == 'yes');

		if ($total > 0) {
			$res = $adb->pquery($query, $params);
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$crmid = $row['inviteeid'];
				$row['crmid'] = $crmid;

				if (!$onlyCrmid) {
					$ename = $touchUtils->getEntityNameFromFields($relmod, $crmid);
					$fields = array(
						'participation' => $partValues[$row['partecipation']],	// 0 = not answered, 1 = no, 2 = yes
					);

					$row['entityname'] = $ename;

					$row['module'] = $relmod;
					$row['tabid'] = getTabid($relmod);
					$row['extrafields'] = $fields;
					$row['perm_delete'] = $permitted;
				}
				$outdata[] = $row;
			}
		}

		return $this->success(array('entries'=>$outdata, 'total'=>$total));
	}
	
	// crmv@73256
	public function getNotes(&$request, $recordid, $relationid, $onlyCrmid = false) {
		global $touchInst, $touchUtils;
		
		$total = 0;
		$outdata = array();
		$modObj = $touchUtils->getModuleInstance('MyNotes');
		$rels = $modObj->getRelNotes($recordid);
		
		$total = count($rels);
		foreach ($rels as $noteid) {
			$row = array('crmid' => $noteid);
			
			if (!$onlyCrmid) {
				// add other fields
				$row['entityname'] = $touchUtils->getEntityNameFromFields('MyNotes', $noteid);
				$row['module'] = 'MyNotes';
				$row['module'] = getTabid('MyNotes');
			}
			$outdata[] = $row;
		}
		
		return $this->success(array('entries'=>$outdata, 'total'=>$total));
	}
	// crmv@73256

}