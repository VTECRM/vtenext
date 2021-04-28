<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class ModComments_DetailViewBlockCommentWidget {
	private $_name = 'DetailViewBlockCommentWidget';
	private $defaultCriteria = 'Last5';
	protected $context = false;
	protected $criteria= false;
	protected $defaultDisplayBlock = 'block';	// block/none
	protected $searchkey = ''; //crmv@31301

	function __construct() {
		//crmv@2963m
		if (($_REQUEST['module'] == 'Messages' || $_REQUEST['module'] == 'Emails') && $_REQUEST['file'] != 'PreView') {
			$this->defaultDisplayBlock = 'block;';
		}
		//crmv@2963me
	}

	function getFromContext($key, $purify=false) {
		if ($this->context) {
			$value = $this->context[$key];
			if ($purify && !empty($value)) {
				$value = vtlib_purify($value);
			}
			return $value;
		}
		return false;
	}

	function title() {
		return getTranslatedString('LBL_MODCOMMENTS_COMMUNICATIONS', 'ModComments');
	}

	function name() {
		return $this->_name;
	}

	function uikey() {
		return "ModCommentsDetailViewBlockCommentWidget";
	}

	function setCriteria($newCriteria) {
		$this->criteria = $newCriteria;
	}

	//crmv@31301
	function SetSearchKey($newSearchKey) {
		$this->searchkey = $newSearchKey;
	}
	//crmv@31301e

	function getViewer() {
		global $theme, $app_strings, $current_language;

		$smarty = new VteSmarty();
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', return_module_language($current_language,'ModComments'));
		$smarty->assign('THEME', $theme);
		$smarty->assign('IMAGE_PATH', "themes/$theme/images/");

		$smarty->assign('UIKEY', $this->uikey());
		$smarty->assign('WIDGET_TITLE', $this->title());
		$smarty->assign('WIDGET_NAME', $this->name());

		return $smarty;
	}

	protected function getModels($parentRecordId, $criteria) {
		global $adb, $current_user,$table_prefix;

		$moduleName = 'ModComments';
		if(vtlib_isModuleActive($moduleName)) {
			$entityInstance = CRMEntity::getInstance($moduleName);

			$queryCriteria  = '';
			switch($criteria) {
				case 'All':
				case 'Last5':
					$queryCriteria = '';
					break;
				case 'Mine':
					$queryCriteria = ' AND '.$table_prefix.'_crmentity.smownerid = '.$current_user->id;
					break;
			}
			$queryCriteria .= sprintf(" ORDER BY %s.%s DESC", $entityInstance->table_name, $entityInstance->table_index);
			
			//crmv@44610
			if (getSalesEntityType($parentRecordId) == 'Messages') {
				$messagesInstance = CRMEntity::getInstance('Messages');
				$parents = $messagesInstance->getMessagesWithSameHash($parentRecordId);
				$where = sprintf(" AND %s.related_to IN (".generateQuestionMarks($parents).")", $entityInstance->table_name);
				$params = array($parents);
			} else {
			//crmv@44610e
				$where = sprintf(" AND %s.related_to=?", $entityInstance->table_name);
				$params = array($parentRecordId);
			} //crmv@44610

			$query = $entityInstance->getListQuery($moduleName, $where, false, true, false, $entityInstance->visibilityInherited);	// crmv@101978
			$query .= $queryCriteria;
			//crmv@fix
			if ($criteria == 'Last5')
				$result = $adb->limitpQuery($query, 0, 5, $params);
			else
				//crmv@fix-e
				$result = $adb->pquery($query, $params);

			$instances = array();
			if($adb->num_rows($result)) {
				while($resultrow = $adb->fetch_array($result)) {
					$instances[] = new ModComments_CommentsModel($resultrow);
				}
			}
		}
		return $instances;
	}

	// crmv@84383
	function getBaseQuery($parentRecordId, $dateFrom = null, $lastchildid = null, $lastseen = null) {
		global $adb, $current_user, $table_prefix;

		$moduleName = 'ModComments';
		$entityInstance = CRMEntity::getInstance($moduleName);
		
		$userColumn = 'user';
		$adb->format_columns($userColumn);

		// add search (search all comments and save the id in a temp table)
		$sqlSearch = null;
		if (!empty($this->searchkey)) {
			
			$sqlSearch = $adb->sql_escape_string($this->searchkey);//crmv@208173
			$where = " AND (commentcontent like '%".$sqlSearch."%'
							OR modcommUser.last_name like '%".$sqlSearch."%' OR modcommUser.first_name like '%".$sqlSearch."%'
							OR recipientUser.last_name like '%".$sqlSearch."%' OR recipientUser.first_name like '%".$sqlSearch."%'
							)";
			$searchQuery = $entityInstance->getListQuery($moduleName, $where, true, true,true);
			//$searchQuery = $entityInstance->listQueryNonAdminChange($searchQuery, $moduleName);
			
			$other_joins = "INNER JOIN {$table_prefix}_users modcommUser ON {$table_prefix}_crmentity.smcreatorid = modcommUser.id
							LEFT JOIN {$table_prefix}_modcomments_users ON {$table_prefix}_modcomments_users.id = {$table_prefix}_modcomments.modcommentsid
  							LEFT JOIN {$table_prefix}_users recipientUser ON {$table_prefix}_modcomments_users.$userColumn = recipientUser.id";
			$searchQuery = preg_replace('/WHERE/i', $other_joins.' WHERE', $searchQuery, 1);
			$searchQuery = replaceSelectQuery($searchQuery, "distinct case when ({$table_prefix}_modcomments.parent_comments = 0 or {$table_prefix}_modcomments.parent_comments is null) then {$table_prefix}_modcomments.modcommentsid else {$table_prefix}_modcomments.parent_comments end as id");
			
			// put in temporary table
			$tableName = 'tmp_modcomments_u_'.$current_user->id;
			if ($adb->isMysql()) {
				$adb->query("drop table if exists $tableName",false,'',true);	//crmv@59626 crmv@70475
				$query = "create temporary table IF NOT EXISTS $tableName(id int(11) primary key) ignore ".$searchQuery;
				$result = $adb->query($query);
			} else {
				if (!$adb->table_exist($tableName,true)){
					Vtecrm_Utils::CreateTable($tableName,"id I(11) NOTNULL PRIMARY",true,true);
				//crmv@59626
				} else {
					$adb->query("delete from $tableName");
				//crmv@59626e
				}
				$tableName = $adb->datadict->changeTableName($tableName);
				$result = $adb->query("delete from $tableName");
				$query = "insert into $tableName select id from ($searchQuery) un_table where not exists (select * from $tableName where $tableName.id = un_table.id)";
				$result = $adb->query($query);
			}
		}
		
		$where = '';
		if (!empty($dateFrom)) $where .= " AND crmentityReplies.modifiedtime >= '$dateFrom'"; // crmv@49398

		// crmv@80503
		// This piece is necessary for pagination. Why so complicated? Well, think what would happen if I opened the Talks
		// and in the meanwhile someone writes or deletes a comment (which would be inserted/removed at the top of the list).
		// When I go to the next page I can either see duplicates or missing comments. So this condition ensures that I see
		// only comments OLDER than the last displayed one.
		if (!empty($lastchildid)) {
			if ($lastseen) {
				$where .= " AND (COALESCE(notif.type, fathernotif.type) IS NULL AND child.modcommentsid < ".intval($lastchildid).")";
			} else {
				$where .= " AND ((COALESCE(notif.type, fathernotif.type) = 'ModComments' AND child.modcommentsid < ".intval($lastchildid).") OR (COALESCE(notif.type, fathernotif.type) IS NULL))";
			}
		}
		// crmv@80503e
		
		$query = $entityInstance->getListQuery($moduleName, $where, false, true,true);	//crmv@32429 //crmv@36796
				
		$query = preg_replace('/WHERE/i', 
			"INNER JOIN {$table_prefix}_modcomments child on child.modcommentsid = {$table_prefix}_modcomments.lastchild ".
			($sqlSearch ? "inner join $tableName on $tableName.id = {$table_prefix}_modcomments.modcommentsid " : "").
			" LEFT JOIN (
				SELECT DISTINCT parent_comments, vte_notifications.type
				FROM vte_notifications
				INNER JOIN {$table_prefix}_modcomments ON vte_notifications.id = {$table_prefix}_modcomments.modcommentsid
				WHERE {$table_prefix}_modcomments.parent_comments != 0 AND vte_notifications.userid = {$current_user->id}
			) notif ON notif.parent_comments = {$table_prefix}_modcomments.modcommentsid
			LEFT JOIN vte_notifications fathernotif ON fathernotif.id = {$table_prefix}_modcomments.modcommentsid AND fathernotif.userid = {$current_user->id}
			WHERE ", $query, 1
		);

		// oracle doesn't like the same column name twice
		if ($adb->isOracle()) {
			$query = replaceSelectQuery($query,"{$table_prefix}_modcomments.*, {$table_prefix}_crmentity.*, child.modcommentsid as lastchildid"); // crmv@80503
		} else {
			$query = replaceSelectQuery($query,"{$table_prefix}_modcomments.*, {$table_prefix}_modcommentscf.*, {$table_prefix}_crmentity.*, child.modcommentsid as lastchildid"); // crmv@80503
		}
		
		$orderBy = array();
		if ($adb->isMssql()) {
			$orderBy[] = 'CASE WHEN COALESCE(notif.type, fathernotif.type) IS NULL THEN 1 ELSE 0 END';
		} elseif ($adb->isOracle()) {
			$orderBy[] = 'COALESCE(notif.type, fathernotif.type) DESC NULLS LAST';
		} else {
			$orderBy[] = 'COALESCE(notif.type, fathernotif.type) DESC';
		}
		$orderBy[] = "child.modcommentsid DESC";
		$query .= " ORDER BY ".implode(',', $orderBy);
		
		return $query;
	}
	// crmv@84383e

	// crmv@53565 - performance fix for talks
	function getNewsModels($parentRecordId, $criteria, &$count, $dateFrom = null, $lastchildid = null, $lastseen = null) { // crmv@49398 crmv@80503
		global $adb, $current_user, $table_prefix;

		$moduleName = 'ModComments';
		if(vtlib_isModuleActive($moduleName)) {
		
			$query = $this->getBaseQuery($parentRecordId, $dateFrom, $lastchildid, $lastseen); // crmv@84383
			
			$start = 0;
			// criteria can be: LastXXNews or PageYYNews
			if (preg_match('/Last(\d+)News/', $criteria, $matches)) {
				$length = empty($matches[1]) ? 40 : intval($matches[1]);
			} elseif (preg_match('/Page(\d+)News/', $criteria, $matches)) {
				$page = empty($matches[1]) ? 1 : intval($matches[1]);
				$length = 40;
				if (empty($lastchildid)) $start = ($page-1)*$length; // crmv@80503
			} else {
				$length = 40;
			}
			
			$params = array();
			
			// count query
			$countQuery = replaceSelectQuery($query,"count(*) as count");

			$resCount = $adb->pquery($countQuery, $params);
			$count = @$adb->query_result_no_html($resCount, 0, 'count');
			if ($count == 0) return $output;
			
			// do the query
			$output = array();
			$res = $adb->limitPQuery($query, $start,$length, $params);
			while ($row = $adb->FetchByAssoc($res)) {				
				$output[$row['modcommentsid']] = $row;
			}
			
			// now get replies
			$replies = array();
			// crmv@64325
			$setypeCond = '';
			if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
				$setypeCond = "AND {$table_prefix}_crmentity.setype = 'ModComments'";
			}
			$query2 = "select * 
				from {$table_prefix}_modcomments 
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$table_prefix}_modcomments.modcommentsid
				inner join {$table_prefix}_modcommentscf on {$table_prefix}_modcommentscf.modcommentsid = {$table_prefix}_modcomments.modcommentsid
				where {$table_prefix}_crmentity.deleted = 0 $setypeCond and {$table_prefix}_modcomments.parent_comments in (".implode(',', array_map('intval', array_keys($output))).")
				order by {$table_prefix}_modcomments.modcommentsid ASC";
			// crmv@64325e
			$res2 = $adb->pquery($query2, array());
			while ($row2 = $adb->FetchByAssoc($res2)) {
				$replies[$row2['parent_comments']][] = $row2;
			}

			// merge parent and children
			foreach ($output as &$parentComm) {
				$parentReplies = array($parentComm['modcommentsid'] => $replies[$parentComm['modcommentsid']]);
				$parentComm = new ModComments_CommentsModel($parentComm, $this->searchkey, $parentReplies);
			}
						
			return $output;
			
		}
	}
	// crmv@53565e

	// crmv@31780 -- get data as php array
	public function getModelsAsArray($context = false, &$count = null, $dateFrom = null) { // crmv@49398
		$this->context = $context;
		$sourceRecordId =  $this->getFromContext('ID', true);
		$usecriteria = ($this->criteria === false)? $this->defaultCriteria : $this->criteria;

		if (strpos($usecriteria,'News') !== false) {
			$models = $this->getNewsModels($sourceRecordId, $usecriteria, $count, $dateFrom); // crmv@49398
		} else {
			$models = $this->getModels($sourceRecordId, $usecriteria);
			$count = count($models);
		}

		$out = array();
		if (is_array($models)) {
			foreach ($models as $mod) {
				$row = $mod->content_no_html();
				$out[] = $row;
			}
		}

		return $out;
	}
	// crmv@31780e

	function processItem($model) {
		$viewer = $this->getViewer();
		// crmv@43050	crmv@59626
		if (strpos($_REQUEST['criteria'],'News') !== false) $viewer->assign('NEWS_MODE', 'yes');
		if ($_REQUEST['show_preview'] == 'yes') $show_preview = true;
		if (strpos($_REQUEST['criteria'],'News') === false) $show_preview = false;
		$viewer->assign('SHOWPREVIEW', $show_preview);
		// crmv@43050e	crmv@59626e
		$viewer->assign('UNSEEN_IDS', $this->getUnseenComments(array($model))); // crmv@43448
		$viewer->assign('COMMENTMODEL', $model);
		$viewer->assign('DEFAULT_REPLY_TEXT', getTranslatedString('LBL_DEFAULT_REPLY_TEXT','ModComments'));
		return $viewer->fetch(vtlib_getModuleTemplate("ModComments","widgets/DetailViewBlockCommentItem.tpl"));
	}

	function process($context = false) {
		global $current_user;

		$this->context = $context;
		$sourceRecordId =  $this->getFromContext('ID', true);
		$usecriteria = ($this->criteria === false)? $this->defaultCriteria : $this->criteria;

		$viewer = $this->getViewer();
		$viewer->assign('ID', $sourceRecordId);
		$viewer->assign('CRITERIA', $usecriteria);
		$viewer->assign('DEFAULT_DISPLAY_BLOCK', $this->defaultDisplayBlock);
		$viewer->assign('DEFAULT_REPLY_TEXT', getTranslatedString('LBL_DEFAULT_REPLY_TEXT','ModComments')); // crmv@80503

		$allow_generic_talks = 'yes';
		$show_preview = true;	//crmv@59626
		if (strpos($usecriteria,'News') !== false) {
			$viewer->assign('NEWS_MODE', 'yes');
			// crmv@80503
			$lastchild = intval($_REQUEST['lastchildid']);
			$lastseen = intval($_REQUEST['lastseen']);
			$pageMode = (substr($usecriteria, 0, 4) == 'Page');
			$comments = $this->getNewsModels($sourceRecordId, $usecriteria, $count, null, $lastchild, $lastseen);
			// crmv@80503e
			$length = explode('News',$usecriteria);
			$length = explode('Last',$length[0]);
			$length = $length[1];
			$viewer->assign('MAX_NUM_OF_NEWS', $length);
			$viewer->assign('TOTAL_NUM_OF_NEWS', $count);

			if ($current_user->column_fields['allow_generic_talks'] != 1) {
				$allow_generic_talks = 'no';
			}
		} else {
			$show_preview = false;	//crmv@59626
			$comments = $this->getModels($sourceRecordId, $usecriteria);
		}

		$viewer->assign('ALLOW_GENERIC_TALKS', $allow_generic_talks);
		$viewer->assign('SHOWPREVIEW', $show_preview);	//crmv@59626

		$viewer->assign('COMMENTS', $comments);
		$viewer->assign('UNSEEN_IDS', $this->getUnseenComments($comments));

		//crmv@35267
		if ($current_user->column_fields['receive_public_talks'] == 1) {
			$viewer->assign('ENABLE_PUBLIC_TALKS', true);
		}
		//crmv@35267e

		//crmv@2963m crmv@80503
		if ($pageMode) {
			$tpl = "widgets/DetailViewBlockCommentPage.tpl";
		} elseif ($_REQUEST['module'] == 'Messages' || $_REQUEST['module'] == 'Emails') {
			$tpl = "widgets/DetailViewBlockCommentMessages.tpl";
		} else {
			$tpl = "widgets/DetailViewBlockComment.tpl";
		}
		return $viewer->fetch(vtlib_getModuleTemplate("ModComments",$tpl));
		//crmv@2963me crmv@80503e
	}

	function processItemReply($model) {
		$viewer = $this->getViewer();
		$viewer->assign('REPLYMODEL', $model);
		$viewer->assign('UIKEY', $this->uikey());
		//crmv@55743
		$usecriteria = ($this->criteria === false)? $this->defaultCriteria : $this->criteria;
		if (strpos($usecriteria,'News') !== false) {
			$viewer->assign('INDICATOR', "parent.$('indicatorModCommentsNews')");
		} else {
			$viewer->assign('INDICATOR', "$('indicator".$this->uikey()."')");
		}
		//crmv@55743e
		return $viewer->fetch(vtlib_getModuleTemplate("ModComments","widgets/DetailViewBlockReplyItem.tpl"));
	}

	function getUnseenComments($comments) {
		$return = array();
		if (!empty($comments)) {
			foreach($comments as $comment) {
				if ($comment->isUnseen()) {
					$return[] = $comment->id();
				}
				if (!empty($comment->replies)) {
					foreach($comment->replies as $reply) {
						if ($reply->isUnseen()) {
							$return[] = $reply->id();
						}
					}
				}
			}
		}
		return $return;
	}
}
?>