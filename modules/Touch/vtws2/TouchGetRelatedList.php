<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@57010 - limits */

class TouchGetRelatedList extends TouchWSClass {

	public $defaultPageLimit = 5000;	// default value for page size if no specified
	public $longOperation = true;
	public $excludeMods = array('ModComments');
	
	function process(&$request) {
		global $adb, $table_prefix;
		global $current_user, $currentModule, $touchInst, $touchUtils;

		$relationids = Zend_Json::decode($request['relationids']);
		if (!is_array($relationids)) return array('success'=>false, 'error'=>'Invalid list of relations');
		
		// limits
		$pageLimit = intval($request['limit']);
		if ($pageLimit <= 0) $pageLimit = $this->defaultPageLimit;
		$page = intval($request['page']);
		if ($page <= 0) $page = 1;
		unset($request['page'], $request['limit'], $request['start']);

		$result = array();
		$total = 0;
		foreach ($relationids as $relationId) {
			$pmodule = null;
			if ($relationId >= $touchUtils->related_blockids['related_events']) {
				$pmodule = 'Events';
			}
			$list = $this->getIdsForRelation($relationId, $pmodule, $page, $pageLimit);
			if (intval($list['total']) > 0) {
				$result[] = $list;
				$total += intval($list['total']);
			}
		}

		return array('success'=>true, 'relateds'=>$result, 'total'=>$total);
	}

	function getIdsForRelation($relationId, $prefModule = null, $page = null, $pageLimit = null) {
		global $adb, $table_prefix, $onlyquery; // crmv@54449
		global $current_user, $currentModule, $touchInst, $touchUtils;

		if (empty($relationId)) return array();
		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $this->defaultPageLimit;
		
		$excludeMods = array_merge($this->excludeMods, $touchInst->excluded_modules);
		
		// relationid used internally by the VTE, might be different from the app's one
		$crmRelationId = $relationId;
		
		// check for special relations
		if ($touchUtils->isInviteesRelated($relationId)) {
			// invitees
			return $this->getInvitees($relationId, $page, $pageLimit);
		} elseif ($touchUtils->isProductsRelated($relationId)) {
			// products
			return $this->getProducts($relationId, $page, $pageLimit);
		} elseif ($touchUtils->isNotesRelated($relationId)) {
			// MyNotes			
			return $this->getNotes($relationId, $page, $pageLimit);
		} elseif ($prefModule == 'Events') {
			$crmRelationId -= $touchUtils->related_blockids['related_events'];
		}

		$relinfo = ModuleRelation::createFromRelationId($crmRelationId);

		if ($relinfo && $relinfo->getType() == ModuleRelation::$TYPE_NTON) {
			if (in_array($relinfo->getFirstModule(), $excludeMods) || in_array($relinfo->getSecondModule(), $excludeMods)) return array();
			return $this->getIdsForRelationN2N($relinfo, true, false, $page, $pageLimit);
		}

		$relationInfo = getRelatedListInfoById($crmRelationId);
		$primaryModule = vtlib_getModuleNameById($relationInfo['tabid']);
		$relatedModule = vtlib_getModuleNameById($relationInfo['relatedTabId']);

		if (in_array($relatedModule, $excludeMods) || in_array($primaryModule, $excludeMods)) return array();
		$currentModule = $module = $primaryModule;

		// crmv@54449
		$modObj = $touchUtils->getModuleInstance(($module == 'Events' ? 'Calendar' : $module));
		if (!$modObj) return array();
		// crmv@54449e

		$onlyquery = true;
		$fakeRecordId = 1234567;
		$function_name = $relationInfo['functionName'];
		
		// crmv@55371
		if ($relatedModule == 'Calendar' || $relatedModule == 'Events') // crmv@54449
			$mod_listquery = "activity_listquery";
		else
			$mod_listquery = strtolower($relatedModule)."_listquery";
	
		VteSession::set($mod_listquery, '');
		$relatedListData = $modObj->$function_name($fakeRecordId, getTabid($currentModule),$relationInfo['relatedTabId'], $actions);
		$query = VteSession::get($mod_listquery);
		// crmv@55371e

		if (empty($query)) return array();

		// now parse the query
		if (preg_match('/order by\s+(.+)$/i', $query, $matches)) {
			$order_by = explode(',', $matches[1]);
			$order_by = $order_by[0];
			list($order_by, $order_dir) = array_map('trim', explode(' ', $order_by, 2));

			if (empty($order_dir)) $order_dir = 'ASC';

			list($order_tab, $order_col) = array_map('trim', explode('.', $order_by, 2));

			$res = $adb->pquery("select fieldname from {$table_prefix}_field where columnname = ? and tablename = ?", array($order_col, $order_tab));

			if ($res && $adb->num_rows($res) > 0) {
				$row = $adb->FetchByAssoc($res, -1, false);
				$orderField = $row['fieldname'];
				$orderDir = $order_dir;
			} else {
				$orderField = '';
				$orderDir = '';
			}

			// remove the order by clause
			$query = preg_replace('/\s*order by.+$/i', '', $query);
		}

		if (preg_match('/([^\s()]+)\s*=\s*'.$fakeRecordId.'/', $query, $matches)) {
			$relatedIndex = $matches[1];
			$query = preg_replace('/(and|or)\s*[^\s()]+\s*=\s*'.$fakeRecordId.'/i', '', $query);
			$query = preg_replace('/^select .+? from/i', "SELECT {$table_prefix}_crmentity.crmid as relcrmid, $relatedIndex AS crmid FROM", $query);
			// now add a join with crmentity of first module because I have to filter by module
			$query = $this->sql_preg_replace('/\s+where\s+/i', " INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = $relatedIndex WHERE ", $query, 1);
			//and add the condition
			$query .= " AND crm1.setype = '$primaryModule'";
		}

		// crmv@54449, really terrible tricks, please please, someone destroys that ugly calendar!!
		if ($relatedModule == 'Calendar') {
			if ($prefModule == 'Events') {
				// only events
				$query = preg_replace("/'Task'/", "'NOTATASK'", $query);
				$relatedModule = 'Events';
			} else {
				// only tasks
				$query = preg_replace("/activitytype in \(.*?\)/i", "activitytype = 'NOTANEVENT'", $query);
			}
		}
		// crmv@54449e

		// and
		$list = array(
			'list' => array(),
			'module' => $primaryModule,
			'relmodule' => $relatedModule,
			'relationid' => $relationId,
			'extra' => null,
			'total' => 0,
			'totalcount' => 0
		);
		
		// total
		$list['totalcount'] = $this->countTotalQuery($query);
		
		$start = ($page-1)*$pageLimit;
		$res = $adb->limitQuery($query, $start, $pageLimit);
		if ($res) {
			$total = 0;
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$crmid = intval($row['crmid']);
				$relcrmid = intval(($row['relcrmid']));
				if ($crmid > 0 && $relcrmid > 0) {
					$list['list']['crmid'][] = $crmid;
					$list['list']['relcrmid'][] = $relcrmid;
					++$total;
				}
			}
		}
		$list['total'] = $total;

		return $list;
	}

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

	// TODO: use fetchExtra, TODO: PERMISSIONS!!
	public function getIdsForRelationN2N($relinfo, $fetchExtra = true, $reverse = false, $page = null, $pageLimit = null) {
		global $adb, $table_prefix, $current_user;

		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $this->defaultPageLimit;
		$info = $relinfo->relationinfo;
		
		$list = array(
			'list' => array(),
			'relationid' => $relinfo->relationid,
			'module' => $relinfo->getFirstModule(),
			'relmodule' => $relinfo->getSecondModule(),
			'extra' => null,
			'total' => 0,
			'totalcount' => 0,
		);

		if (empty($info['relidx']) || empty($info['reltab']) || empty($info['relidx2'])) return $list;
		
		$otherJoins = '';
		
		if ($reverse) {
			$firstModule = $relinfo->getSecondModule();
			$secondModule = $relinfo->getFirstModule();
			$firstId = $info['relidx2'];
			$secondId = $info['relidx'];
		} else {
			$firstModule = $relinfo->getFirstModule();
			$secondModule = $relinfo->getSecondModule();
			$firstId = $info['relidx'];
			$secondId = $info['relidx2'];
		}
		
		$firstSQLModule = $firstModule;
		$secondSQLModule = $secondModule;
		
		
		if ($firstModule == 'Events') $firstSQLModule = 'Calendar';
		if ($secondModule == 'Events') $secondSQLModule = 'Calendar';
		
		if (in_array($firstModule, array('Calendar', 'Events'))) {
			$otherJoins .= "INNER JOIN {$table_prefix}_activity activity1 on activity1.activityid = crm1.crmid";
		}
		
		if (in_array($secondModule, array('Calendar', 'Events'))) {
			$otherJoins .= "INNER JOIN {$table_prefix}_activity activity2 on activity2.activityid = crm2.crmid";
		}
		
		if ($firstModule == 'Leads') {
			$otherJoins .= "INNER JOIN {$table_prefix}_leaddetails leads1 on leads1.leadid = crm1.crmid";
		}
		
		if ($secondModule == 'Leads') {
			$otherJoins .= "INNER JOIN {$table_prefix}_leaddetails leads2 on leads2.leadid = crm2.crmid";
		}

		// build the query
		$where = array();
		$params = array();
		$query = "SELECT reltab.$firstId AS crmid, reltab.$secondId AS relcrmid
		FROM {$info['reltab']} reltab
		INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = reltab.$firstId
		INNER JOIN {$table_prefix}_crmentity crm2 ON crm2.crmid = reltab.$secondId
		$otherJoins
		";
		
		if ($firstModule == 'Events') {
			$where[] = "activity1.activitytype not in ('Task', 'Emails')"; // crmv@152701
		} elseif ($firstModule == 'Calendar') {
			$where[] = "activity1.activitytype = 'Task'";
		}
		
		if ($secondModule == 'Events') {
			$where[] = "activity2.activitytype not in ('Task', 'Emails')"; // crmv@152701
		} elseif ($secondModule == 'Calendar') {
			$where[] = "activity2.activitytype = 'Task'";
		}
		
		$skipSetype1 = false;
		$skipSetype2 = false;
		$deletedTab = 'crm2'; // crmv@177095
		
		// special query for messages
		if ($secondModule == 'Messages') {

			$info['relmod1'] = null;
			$info['relmod2'] = null;
			$query = "SELECT MAX(m.messagesid) AS relcrmid, MAX(reltab.crmid) AS crmid
				FROM {$table_prefix}_messagesrel reltab
				INNER JOIN {$table_prefix}_messages m ON reltab.messagehash = m.messagehash
				INNER JOIN {$table_prefix}_crmentity crm1 ON crm1.crmid = reltab.crmid
				$otherJoins"; // crmv@177095
			$where[] = 'reltab.module = ?';
			$where[] = "m.smownerid = ?"; // crmv@177095
			$params[] = $firstSQLModule;
			$params[] = $current_user->id;
			$skipSetype1 = true;
			$skipSetype2 = true;
			$deletedTab = 'm'; // crmv@177095
		}

		if (!$skipSetype1) {
			if ($info['relmod1']) {
				$where[] = "reltab.{$info['relmod1']} = ?";
			} else {
				$where[] = "crm1.setype = ?";
			}
			$params[] = $firstSQLModule;
		}
		

		if (!$skipSetype2) {
			if ($info['relmod2']) {
				$where[] = "reltab.{$info['relmod2']} = ?";
			} else {
				$where[] = "crm2.setype = ?";
			}
			$params[] = $secondSQLModule;
		}
		
		// add clause for deleted records
		$where[] = "crm1.deleted = 0";
		$where[] = "$deletedTab.deleted = 0"; // crmv@177095
		
		if ($firstModule == 'Leads') $where[] = 'leads1.converted = 0';
		if ($secondModule == 'Leads') $where[] = 'leads2.converted = 0';

		if (count($where) > 0) {
			$query .= " WHERE ".implode(' AND ', $where);
		}
		
		if ($secondModule == 'Messages') {
			$query .= " GROUP BY reltab.messagehash";
		}

		// total
		$list['totalcount'] = $this->countTotalQuery($query, $params);
		
		$start = ($page-1)*$pageLimit;
		$res = $adb->limitPQuery($query, $start, $pageLimit, $params);
		if ($res) {
			$total = 0;
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$crmid = intval($row['crmid']);
				$relcrmid = intval(($row['relcrmid']));
				if ($crmid > 0 && $relcrmid > 0) {
					$list['list']['crmid'][] = $crmid;
					$list['list']['relcrmid'][] = $relcrmid;
					++$total;
				}
			}
		}
		$list['total'] = $total;

		return $list;
	}
	
	public function getNotes($relationId, $page = null, $pageLimit = null) { // crmv@73256
		global $touchInst, $touchUtils;
		
		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $this->defaultPageLimit;
		$excludeMods = array_merge($this->excludeMods, $touchInst->excluded_modules);
		
		$modTabId = $relationId - $touchUtils->related_blockids['notes'];
		$primaryModule = vtlib_getModuleNameById($modTabId);
		$relatedModule = 'MyNotes';
		
		if (in_array($relatedModule, $excludeMods) || in_array($primaryModule, $excludeMods)) return array();
		
		$RM = RelationManager::getInstance();
		$relations = $RM->getRelations($primaryModule, ModuleRelation::$TYPE_NTON, $relatedModule);
		
		if (empty($relations[0])) return array();
		$relinfo = $relations[0];
		
		// notes are reversed in crmentityrel
		$list = $this->getIdsForRelationN2N($relinfo, false, true, $page, $pageLimit);
		
		// but keep the custom relationid
		if (is_array($list) && $list['relationid']) {
			$list['relationid'] = $relationId;
		}
		return $list;
	}
	
	protected function getInvitees($relationId, $page = null, $pageLimit = null) {
		global $adb, $table_prefix, $touchInst;

		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $this->defaultPageLimit;
		
		if ($relationId == 2700000) {
			$relmod = 'Contacts';
			$table = $table_prefix.'_invitees_con';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_crmentity c on c.crmid = i.inviteeid
				where c.deleted = 0 and c.setype = ?";
			$params = array($relmod);
		
		} elseif ($relationId == 2700001) {
			$relmod = 'Users';
			$table = $table_prefix.'_invitees';

			$query = "select i.* from {$table} i
				inner join {$table_prefix}_users u on u.id = i.inviteeid
				where u.deleted = 0";
			$params = array();
		} else {
			return array();
		}
		
		$invitees = array(
			'list' => array(),
			'module' => 'Events',
			'relmodule' => $relmod,
			'relationid' => $relationId,
			'total' => 0,
			'totalcount' => 0,
		);
		
		// total
		$invitees['totalcount'] = $this->countTotalQuery($query, $params);
		
		$start = ($page-1)*$pageLimit;
		$res = $adb->limitPQuery($query, $start, $pageLimit, $params);
		if ($res) {
			$total = 0;
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$crmid = intval($row['activityid']);
				$relcrmid = intval($row['inviteeid']);
				$part = intval($row['partecipation']);
				if ($crmid > 0 && $relcrmid > 0) {
					$invitees['list']['crmid'][] = $crmid;
					$invitees['list']['relcrmid'][] = $relcrmid;
					$invitees['list']['extra'][] = array('participation' => $part);
					++$total;
				}
			}
			$invitees['total'] = $total;
		}

		
		return $invitees;
	}
	
	protected function getProducts($relationId, $page = null, $pageLimit = null) {
		global $adb, $table_prefix;
		global $touchInst, $touchUtils, $current_user;
		
		if (empty($page)) $page = 1;
		if (empty($pageLimit)) $pageLimit = $this->defaultPageLimit;
		
		$tabid = $relationId - $touchUtils->related_blockids['products'];
		$module = getTabname($tabid);
		
		if (empty($module) || in_array($module, $touchInst->excluded_modules)) return array();
	
		$prodList = array(
			'list' => array(),
			'module' => $module,
			'relmodule' => 'Products',
			'relationid' => $relationId,
			'total' => 0,
			'totalcount' => 0,
		);

		$total = 0;
				
		$focus = $touchUtils->getModuleInstance($module);
		$focus->mode = 'edit';
		
		$queryGenerator = QueryGenerator::getInstance($module, $current_user);
		$queryGenerator->initForDefaultCustomView();

		$queryGenerator->appendToFromClause("INNER JOIN {$table_prefix}_inventoryproductrel prel ON prel.id = {$table_prefix}_crmentity.crmid");
		$queryGenerator->addField('crmid');

		$params = array();
		$query = $queryGenerator->getQuery();
		$query = preg_replace('/^select\s+.*?\s+from/i', "SELECT {$table_prefix}_crmentity.crmid, {$focus->table_name}.taxtype, prel.lineitem_id as lineid, prel.* FROM", $query);
		
		// total:
		$prodList['totalcount'] = $this->countTotalQuery($query, $params);
		
		$start = ($page-1)*$pageLimit;
		$res = $adb->limitPQuery($query, $start, $pageLimit, $params);
		
		if ($res) {
		
			$InventoryUtils = InventoryUtils::getInstance();
			$modWsClass = $touchInst->getWSClassInstance('GetAssociatedProducts', $this->requestedVersion);
			
			$i = 1;
			while ($prodrow = $adb->FetchByAssoc($res, -1, false)) {
				$prodid =  $prodrow['productid'];

				$prodList['list']['lineid'][] = $prodrow['lineid'];
				$prodList['list']['crmid'][] = $prodrow['crmid'];
				$prodList['list']['relcrmid'][] = $prodid;
				
				$focus->id = $prodrow['crmid'];
				$options = array(
					'taxtype' => $prodrow['taxtype'],
					'skip_subproducts' => true,
				);
				$prodrow = $InventoryUtils->processProductSqlRow($prodrow, $i, $options, $module, $focus);
				$prodrow = $modWsClass->processRow($i, $prodrow);
				
				$prodList['list']['extra'][] = $prodrow;
				
				++$total;
				++$i;
			}
		}
				
		$prodList['total'] = $total;
		
		return $prodList;
	}
	
	protected function countTotalQuery($query, $params = array()) {
		global $adb, $table_prefix;
		
		$list_count = 0;
		$count_query = replaceSelectQuery($query,'count(*) as cnt');
		$res = $adb->pquery($count_query, $params);
		if ($res) {
			$list_count = $adb->query_result_no_html($res,0,'cnt');
		}
		return intval($list_count);
	}

}