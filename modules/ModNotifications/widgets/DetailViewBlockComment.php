<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/ModNotifications/models/Comments.php');

class ModNotifications_DetailViewBlockCommentWidget {

	private $_name = 'DetailViewBlockCommentWidget';
	protected $context = false;
	protected $criteria= false;
	protected $defaultCriteria= 20;

	function __construct() {}

	function setDefaultCriteria($value) {
		$this->defaultCriteria = $value;
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
		return getTranslatedString('ModNotifications', 'ModNotifications');
	}

	function name() {
		return $this->_name;
	}

	function uikey() {
		return "ModNotificationsDetailViewBlockCommentWidget";
	}

	function setCriteria($newCriteria) {
		$this->criteria = $newCriteria;
	}

	function getViewer() {
		global $theme, $app_strings, $current_language;

		$smarty = new VteSmarty();
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', return_module_language($current_language,'ModNotifications'));
		$smarty->assign('THEME', $theme);
		$smarty->assign('IMAGE_PATH', "themes/$theme/images/");

		$smarty->assign('UIKEY', $this->uikey());
		$smarty->assign('WIDGET_TITLE', $this->title());
		$smarty->assign('WIDGET_NAME', $this->name());

		return $smarty;
	}

	// crmv@102955
	public function getModels($parentRecordId, $criteria, &$count='no', $only_count=false, $seen='') { //crmv@176619
		global $adb, $table_prefix, $current_user;

		$moduleName = 'ModNotifications';
		if(vtlib_isModuleActive($moduleName)) {
			$entityInstance = ModNotifications::getInstance();

			// crmv@63349
			$parentRecordId = intval($parentRecordId);
			if (PerformancePrefs::getBoolean('USE_TEMP_TABLES', true)) {
				$tmpSelects = "table_alias.modnotificationsid AS id";
			} else {
				$tmpSelects = "{$current_user->id} as userid, $parentRecordId as parentid, table_alias.modnotificationsid AS id";
			}

			$where = '';
			$params = array();
			if ($parentRecordId) {
				$where = " AND {$entityInstance->table_name}.related_to=?";
				$params[] = $parentRecordId;
			}
			
			//crmv@176619
			if ($seen != '') {
				$where .= " AND {$entityInstance->table_name}.seen=?";
				$params[] = ($seen == 'yes') ? 1 : 0;
			}
			//crmv@176619e
			
			$query = $entityInstance->getListQuery($moduleName, $where, true);
			$query .= " ORDER BY {$entityInstance->table_name}.createdtime DESC"; // crmv@164122
			//crmv@32429e	crmv@54005e

			if ($count == 'yes') {
				$count_query = mkCountQuery($adb->convert2Sql($query,$adb->flatten_array($params))); //crmv@176619
				$count_result = $adb->querySlave('BadgeCount',$count_query); // crmv@185894
				if($adb->num_rows($count_result) > 0) {
					$count = $adb->query_result($count_result,0,"count");
				} else {
					$count = $adb->num_rows($count_result);
				}
				if ($only_count) return; //crmv@176619
			}

			if (intval($criteria) > 0)
				$result = $adb->limitpQuery($query, 0, $criteria, $params);
			else
				$result = $adb->pquery($query, $params);
			
			$instances = array();
			if($adb->num_rows($result)) {
				while($resultrow = $adb->fetch_array($result)) {
					$instances[] = new ModNotifications_CommentsModel($resultrow);
				}
			}
		}
		return $instances;
	}
	// crmv@102955e

	// crmv@31780 -- get data as php array, no html please!
	// only unseen
	public function getModelsAsArray($count = 0) {
		$models = $this->getModels(false, $count);
		$out = array();

		foreach ($models as $mod) {
			if (!$mod->isUnseen()) continue;
			$row = $mod->content_no_html();
			$row['crmid'] = $mod->id();
			$row['author'] = $mod->author();
			$row['timestamp'] = $mod->timestamp();
			$row['timestampago'] = $mod->timestampAgo();
			$out[] = $row;
		}
		return $out;
	}
	// crmv@31780e

	function process($context = false) {
		global $current_user;

		$this->context = $context;
		$sourceRecordId =  $this->getFromContext('ID', true);
		$usecriteria = ($this->criteria === false)? $this->defaultCriteria : $this->criteria;

		$viewer = $this->getViewer();
		$viewer->assign('ID', $sourceRecordId);
		$viewer->assign('CRITERIA', $usecriteria);

		$count = 'yes';
		$comments = $this->getModels($sourceRecordId, $usecriteria, $count);
		$viewer->assign('COMMENTS', $comments);
		$viewer->assign('TOTAL', $count);
		$viewer->assign('UNSEEN_IDS', $this->getUnseenComments($comments));

		return $viewer->fetch(vtlib_getModuleTemplate("ModNotifications","widgets/DetailViewBlockComment.tpl"));
	}

	// crmv@43194
	function processItem($model) {
		global $current_user;

		$viewer = $this->getViewer();

		$unseen_ids = array();

		if ($model->isUnseen()) $unseen_ids[] = $model->id();
		$viewer->assign('UNSEEN_IDS', $unseen_ids);
		$viewer->assign('COMMENTMODEL', $model);


		return $viewer->fetch(vtlib_getModuleTemplate("ModNotifications","widgets/DetailViewBlockCommentItem.tpl"));
	}

	// crmv@164122
	function getModel($recordid) {
		global $adb, $table_prefix, $current_user;
		$entityInstance = CRMEntity::getInstance('ModNotifications');
		$res = $adb->pquery(
			"select ".$entityInstance->getListQuerySelect()."
			from {$entityInstance->table_name}
			where smownerid = ? and {$entityInstance->table_index} = ?", array($current_user->id, $recordid));

		if ($adb->num_rows($res) > 0) {
			$resultrow = $adb->fetch_array($res);
			return new ModNotifications_CommentsModel($resultrow);
		}
		return null;
	}
	// crmv@164122e
	// crmv@43194e

	function getUnseenComments($comments='',$context = false) {
		if ($comments == '') {
			if (!$context) {
				return false;
			}
			$this->context = $context;
			$sourceRecordId =  $this->getFromContext('ID', true);
			$comments = $this->getModels($sourceRecordId, $this->defaultCriteria);
		}
		$return = array();
		if (!empty($comments)) {
			foreach($comments as $comment) {
				if ($comment->isUnseen()) {
					$return[] = $comment->id();
				}
			}
		}
		return $return;
	}
}
