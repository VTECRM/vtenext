<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetRelated extends TouchWSClass {

	function process(&$request) {
		global $adb, $table_prefix;
		global $current_user, $currentModule, $touchInst, $touchUtils;

		$module = $request['module'];
		$recordid = intval($request['recordid']);
		$relationId = intval($request['relationid']);
		$actions = $request['actions'];

		$currentModule = $module;

		// disabilito azioni per la lista
		global $list_max_entries_per_page;
		PerformancePrefs::setTemp('LISTVIEW_RECORD_CHANGE_INDICATOR', false, 'request'); // crmv@115378

		// imposto il massimo numero di elementi
		$list_max_entries_per_page = $touchInst->listPageLimit;
		// e l'inizio (clear some request values since they are used somewhere else)
		unset($_REQUEST['limit']);
		$_REQUEST['start'] = $request['page'];
		unset($_REQUEST['page']);

		$outdata = array();
		$total = 0;
		if ($recordid > 0) {

			if ($module == 'Events' && $relationId >= 2700000 && $relationId < 2800000) {
				return $this->getInvitees($request, $recordid, $relationId);
			} elseif ($touchUtils->isNotesRelated($relationId)) {
				// MyNotes			
				return $this->getNotes($request, $recordid, $relationId);
			}

			// prendo la lista
			$modObj = CRMEntity::getInstance($currentModule);
			$modObj->retrieve_entity_info($recordid,$currentModule);
			$modObj->id = $recordid;

			$relationInfo = getRelatedListInfoById($relationId);
			$function_name = $relationInfo['functionName'];
			$relatedModule = vtlib_getModuleNameById($relationInfo['relatedTabId']);

			$relatedListData = $modObj->$function_name($recordid, getTabid($currentModule),$relationInfo['relatedTabId'], $actions);
			$total = intval($relatedListData['count']);

			// prendo i campi
			$relatedFields = getRelatedFields($current_user->id, $module, $relatedModule, $relationId, $recordid);


			// formatto nel formato nome->valore

			$relatedInst = CRMEntity::getInstance($relatedModule);

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

					$entname = getEntityName($relatedModule, array($crmid));
					if (is_array($entname) && count($entname) > 0) $entname = $entname[$crmid]; else $entname = '';
					$outdata[] = array(
						'crmid' => $crmid,
						'tabid' => $relationInfo['relatedTabId'],
						'setype' => $outSetype,
						'entityname' => $entname,
						'fields' => Zend_Json::encode($fielddata),

					);
				}
			}
		}

		return array('entries'=>$outdata, 'total'=>$total);

	}

	protected function getNotes($request, $recordid, $relationId, $page = null, $pageLimit = null) {
		global $adb, $table_prefix;
		global $touchInst, $touchUtils;
		global $currentModule, $current_user;
		
		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $touchInst->listPageLimit;;
		$excludeMods = $touchInst->excluded_modules;
		
		$modTabId = $relationId - $touchUtils->related_blockids['notes'];
		$primaryModule = vtlib_getModuleNameById($modTabId);
		$relatedModule = 'MyNotes';
		
		if (in_array($relatedModule, $excludeMods) || in_array($primaryModule, $excludeMods)) return array();
		
		$total = 0;
		$outdata = array();
		
		$notesFocus = CRMEntity::getInstance($relatedModule);
		$ids = $notesFocus->getRelNotes($recordid, $pageLimit);
		if (is_array($ids)) {
			foreach ($ids as $noteid) {
				$row = array('crmid' => $noteid);
				$ename = getEntityName($relatedModule, $noteid, true);

				$row['entityname'] = $ename;
				$row['setype'] = $relatedModule;
				$row['tabid'] = getTabid($relatedModule);
				//$row['fields'] = Zend_Json::encode($fields);
				$row['perm_delete'] = false;
				$outdata[] = $row;
			}
		}
		
		return array('entries'=>$outdata, 'total'=>$total);
		
	}

	// TODO: pagination
	function getInvitees(&$request, $recordid, $relationid) {
		global $adb, $table_prefix;
		global $current_user, $currentModule;

		if ($relationid == 2700000) {
			$relmod = 'Contacts';
			$table = $table_prefix.'_invitees_con';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_crmentity c on c.crmid = i.inviteeid
				where i.activityid = ? and c.deleted = 0 and c.setype = ?";
			$params = array($recordid, $relmod);
		} elseif ($relationdid = 2700001) {
			$relmod = 'Users';
			$table = $table_prefix.'_invitees';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_users u on u.id = i.inviteeid
				where i.activityid = ? and u.deleted = 0";
			$params = array($recordid);
		} else {
			return 'Invalid Module';
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
				$ename = getEntityName($relmod, $crmid, true);
				$fields = array(
					'participation' => $partValues[$row['partecipation']],	// 0 = not answered, 1 = no, 2 = yes
				);

				$row['entityname'] = $ename;
				$row['crmid'] = $crmid;
				$row['setype'] = $relmod;
				$row['tabid'] = getTabid($relmod);
				$row['fields'] = Zend_Json::encode($fields);
				$row['perm_delete'] = $permitted;
				$outdata[] = $row;
			}
		}

		return array('entries'=>$outdata, 'total'=>$total);
	}

}

?>