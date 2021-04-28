<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/logging.php');
require_once('modules/CustomView/CustomView.php');

class ListViewSession {

	var $module = null;
	var $viewname = null;
	var $start = null;
	var $sorder = null;
	var $sortby = null;
	var $page_view = null;

/**initializes ListViewSession
 * Portions created by VteCRM are Copyright (C) VteCRM.
 * All Rights Reserved.
*/

	function __construct()
	{
		global $log,$currentModule;
		$log->debug("Entering ListViewSession() method ...");

		$this->module = $currentModule;
		$this->sortby = 'ASC';
		$this->start =1;
	}

	static function getCurrentPage($currentModule,$viewId){
		$start = getLVSDetails($currentModule,$viewId,'start');
		if (!empty($start)) return $start;
		return 1;
	}

	static function getRequestStartPage(){
		$start = $_REQUEST['start'];
		if (isset($_REQUEST['last']) && $start > $_REQUEST['last'])
			$start = $_REQUEST['last'];
		if(!is_numeric($start)){
			$start = 1;
		}
		if($start < 1){
			$start = 1;
		}
		$start = ceil($start);
		return $start;
	}

	static function getListViewNavigation($currentRecordId){
		global $currentModule,$current_user,$adb,$log,$list_max_entries_per_page,$table_prefix;
		Zend_Json::$useBuiltinEncoderDecoder = true;
		$reUseData = false;
		$displayBufferRecordCount = 10;
		$bufferRecordCount = 15;
		if($currentModule == 'Documents'){
			$sql = "select folderid from {$table_prefix}_notes where notesid=?";
			$params = array($currentRecordId);
			$result = $adb->pquery($sql,$params);
			$folderId = $adb->query_result($result,0,'folderid');
		}
		$cv = CRMEntity::getInstance('CustomView'); // crmv@115329
		$viewId = $cv->getViewId($currentModule);
		if(!VteSession::isEmpty($currentModule.'_DetailView_Navigation'.$viewId)){
			$recordNavigationInfo = Zend_Json::decode(VteSession::get($currentModule.'_DetailView_Navigation'.$viewId));
			$pageNumber =0;
			if(count($recordNavigationInfo) == 1){
				foreach ($recordNavigationInfo as $recordIdList) {
					if(in_array($currentRecordId,$recordIdList)){
						$reUseData = true;
					}
				}
			}else{
				$recordList = array();
				$recordPageMapping = array();
				foreach ($recordNavigationInfo as $start=>$recordIdList){
					foreach ($recordIdList as $index=>$recordId) {
						$recordList[] = $recordId;
						$recordPageMapping[$recordId] = $start;
						if($recordId == $currentRecordId){
							$searchKey = count($recordList)-1;
						}
					}
				}
				if($searchKey > $displayBufferRecordCount -1 && $searchKey < count($recordList)-$displayBufferRecordCount){
					$reUseData= true;
				}
			}
		}

		if($reUseData === false){
			$recordNavigationInfo = array();
			if(!empty($_REQUEST['start'])){
				$start = ListViewSession::getRequestStartPage();
			}else{
				$start = ListViewSession::getCurrentPage($currentModule,$viewId);
			}
			$startRecord = (($start - 1) * $list_max_entries_per_page) - $bufferRecordCount;
			if($startRecord < 0){
				$startRecord = 0;
			}

			$list_query = VteSession::get($currentModule.'_listquery');
			$instance = CRMEntity::getInstance($currentModule);
			$instance->getNonAdminAccessControlQuery($currentModule, $current_user);
			vtlib_setup_modulevars($currentModule, $instance);
			// crmv@30967 - removed
			/*if($currentModule=='Documents' && !empty($folderId)){
			 $list_query = preg_replace("/[\n\r\s]+/"," ",$list_query);
			//crmv@16312
			$list_query .= " AND {$table_prefix}_notes.folderid=$folderId";
			$order_by = $instance->getOrderByForFolder($folderId);
			$sorder = $instance->getSortOrderForFolder($folderId);
			$tablename = getTableNameForField($currentModule,$order_by);
			$tablename = (($tablename != '')?($tablename."."):'');
			if(!empty($order_by)){
			$list_query .= ' ORDER BY '.$tablename.$order_by.' '.$sorder;
			}
			//crmv@16312 end
			}*/
			if($start !=1){
				$recordCount = ($list_max_entries_per_page+2 * $bufferRecordCount);
			}else{
				$recordCount = ($list_max_entries_per_page+ $bufferRecordCount);
			}
			//crmv@fix limit
			$resultAllCRMIDlist_query = $adb->limitQuery($list_query,$startRecord,$recordCount);
			//crmv@fix limit end
			$navigationRecordList = array();
			while($forAllCRMID = $adb->fetch_array($resultAllCRMIDlist_query)) {
				$navigationRecordList[] = $forAllCRMID[$instance->table_index];
			}

			$pageCount = 0;
			$current = $start;
			if($start ==1){
				$firstPageRecordCount = $list_max_entries_per_page;
			}else{
				$firstPageRecordCount = $bufferRecordCount;
				$current -=1;
			}

			$searchKey = array_search($currentRecordId,$navigationRecordList);
			$recordNavigationInfo = array();
			if($searchKey !== false){
				foreach ($navigationRecordList as $index => $recordId) {
					if(!is_array($recordNavigationInfo[$current])){
						$recordNavigationInfo[$current] = array();
					}
					if($index == $firstPageRecordCount  || $index == ($firstPageRecordCount+$pageCount * $list_max_entries_per_page)){
						$current++;
						$pageCount++;
					}
					$recordNavigationInfo[$current][] = $recordId;
				}
			}
			VteSession::set($currentModule.'_DetailView_Navigation'.$viewId, Zend_Json::encode($recordNavigationInfo));
		}
		return $recordNavigationInfo;
	}

	static function getRequestCurrentPage($currentModule, $query, $viewid, $queryMode = false) {
		global $list_max_entries_per_page, $adb;
		$start = 1;
		if(isset($_REQUEST['query']) && $_REQUEST['query'] == 'true'){
			return ListViewSession::getRequestStartPage();
		}
		//crmv@fix count
		if(!empty($_REQUEST['start'])){
			$start = $_REQUEST['start'];
			if (isset($_REQUEST['last']) && $start > $_REQUEST['last'])
				$start = $_REQUEST['last'];
			if($start == 'last'){
				$count_result = $adb->query( mkCountQuery( $query));
				$noofrows = $adb->query_result($count_result,0,"count");
				if($noofrows > 0){
					$start = ceil($noofrows/$list_max_entries_per_page);
				}
			}
			if(!is_numeric($start)){
				$start = 1;
			}elseif($start < 1){
				$start = 1;
			}
			$start = ceil($start);
		}
		//crmv@fix count end
		else {
			$lvs_start = getLVSDetails($currentModule,$viewid,'start');
			if (!empty($lvs_start)) $start = $lvs_start;
		}
		if(!$queryMode) {
			setLVSDetails($currentModule,$viewid,intval($start),'start');
		}
		return $start;
	}
	//crmv@16312
	static function setSessionQuery($currentModule,$query,$viewid){
		if(VteSession::hasKey($currentModule.'_listquery')){
			if(VteSession::get($currentModule.'_listquery') != $query){
				VteSession::remove($currentModule.'_DetailView_Navigation'.$viewid);
			}
		}
		VteSession::set($currentModule.'_listquery', $query);
	}
	static function hasViewChanged($currentModule) {
		$lvs = getLVS($currentModule,'viewname');
		if(empty($lvs)) return true;
		if(empty($_REQUEST['viewname'])) return false;
		if($_REQUEST['viewname'] != getLVS($currentModule,'viewname')) return true;
		return false;
	}
	//crmv@16312 end

}
?>