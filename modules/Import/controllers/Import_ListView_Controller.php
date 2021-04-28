<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'modules/Import/resources/Utils.php';
require_once 'modules/Import/ui/Viewer.php';
require_once 'include/QueryGenerator/QueryGenerator.php';

class Import_ListView_Controller {

	var $user;
	var $module;
	static $_cached_module_meta;

	public function  __construct() {
	}

	public static function getModuleMeta($moduleName, $user) {
		if(empty(self::$_cached_module_meta[$moduleName][$user->id])) {
			$moduleHandler = vtws_getModuleHandlerFromName($moduleName, $user);
			self::$_cached_module_meta[$moduleName][$user->id] = $moduleHandler->getMeta();
		}
		return self::$_cached_module_meta[$moduleName][$user->id];
	}

	public static function render($userInputObject, $user) {
		global $list_max_entries_per_page;
		$adb = PearDatabase::getInstance();

		$viewer = new Import_UI_Viewer();

		$ownerId = $userInputObject->get('foruser');
		$owner = CRMEntity::getInstance('Users');
		$owner->id = $ownerId;
		$owner->retrieve_entity_info($ownerId, 'Users');
		if(!is_admin($user) && $user->id != $owner->id) {
			$viewer->display('OperationNotPermitted.tpl', 'VteCore');
			exit;
		}
		$userDBTableName = Import_Utils::getDbTableName($owner);

		$moduleName = $userInputObject->get('module');
		$moduleMeta = self::getModuleMeta($moduleName, $user);

		$result = $adb->query('SELECT recordid FROM '.$userDBTableName.' WHERE status is NOT NULL AND recordid IS NOT NULL');
		$noOfRecords = $adb->num_rows($result);

		$importedRecordIds = array();
		for($i=0; $i<$noOfRecords; ++$i) {
			$importedRecordIds[] = $adb->query_result($result, $i, 'recordid');
		}
		if(count($importedRecordIds) == 0) $importedRecordIds[] = 0;

		$focus = CRMEntity::getInstance($moduleName);
		$queryGenerator = QueryGenerator::getInstance($moduleName, $user); // crmv@139359
		$customView = CRMEntity::getInstance('CustomView', $moduleName); // crmv@115329
		$viewId = $customView->getViewIdByName('All', $moduleName);
		$queryGenerator->initForCustomViewById($viewId);
		$list_query = $queryGenerator->getQuery();

		// Fetch only last imported records
		$list_query .= ' AND '.$focus->table_name.'.'.$focus->table_index.' IN ('. implode(',', $importedRecordIds).')';

		$count_result = $adb->query( mkCountQuery( $list_query));
		$noofrows = $adb->query_result($count_result,0,"count");

		$start = ListViewSession::getRequestCurrentPage($moduleName, $list_query, $viewId, false);

		$navigation_array = VT_getSimpleNavigationValues($start,$list_max_entries_per_page,$noofrows);

		$limit_start_rec = ($start-1) * $list_max_entries_per_page;

		$list_result = $adb->limitpQuery($list_query,$limit_start_rec, $list_max_entries_per_page, array()); //crmv@73988

		$recordListRangeMsg = getRecordRangeMessage($list_result, $limit_start_rec,$noofrows);
		$viewer->assign('recordListRange',$recordListRangeMsg);

		$controller = new ListViewController($adb, $user, $queryGenerator);
		$listview_header = $controller->getListViewHeader($focus,$moduleName,$url_string,$sorder,$order_by,true);
		$listview_entries = $controller->getListViewEntries($focus,$moduleName,$list_result,$navigation_array,true);

		$viewer->assign('CURRENT_PAGE', $start);
		$viewer->assign('LISTHEADER', $listview_header);
		$viewer->assign('LISTENTITY', $listview_entries);

		$viewer->assign('FOR_MODULE', $moduleName);
		$viewer->assign('FOR_USER', $ownerId);

		$isAjax = $userInputObject->get('ajax');
		if(!empty($isAjax)) {
			echo $viewer->fetch('ListViewEntries.tpl');
		} else {
			$viewer->display('ImportListView.tpl');
		}
	}
}
?>