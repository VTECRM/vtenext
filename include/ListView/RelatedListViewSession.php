<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/logging.php');
require_once('include/ListView/ListViewSession.php');

/**initializes Related ListViewSession
 * Portions created by vteCRM are Copyright (C) vteCRM.
 * All Rights Reserved.
 */
class RelatedListViewSession {

	var $module = null;
	var $start = null;
	var $sorder = null;
	var $sortby = null;
	var $page_view = null;

	function __construct() {
		global $log,$currentModule;
		$log->debug("Entering RelatedListViewSession() method ...");
		
		$this->module = $currentModule;
		$this->start =1;
	}
	
	public static function addRelatedModuleToSession($relationId, $header) {
		global $currentModule;
		VteSession::setArray(array('relatedlist', $currentModule, $relationId), $header);
		$start = RelatedListViewSession::getRequestStartPage();
		RelatedListViewSession::saveRelatedModuleStartPage($relationId, $start);
	}
	
	public static function removeRelatedModuleFromSession($relationId, $header) {
		global $currentModule;
		if (!empty($relationId)) {
			VteSession::removeArray(array('relatedlist', $currentModule, $relationId));
		} else {
			VteSession::removeArray(array('relatedlist', $currentModule));
		}
	}
		
	public static function getRelatedModulesFromSession() {
		global $currentModule;		

		$allRelatedModuleList = isPresentRelatedLists($currentModule);
		
		$moduleList = array();
		if(is_array(VteSession::getArray(array('relatedlist', $currentModule)))){
			foreach ($allRelatedModuleList as $relationId=>$label) {
				if(array_key_exists($relationId, VteSession::getArray(array('relatedlist', $currentModule)))){
					$moduleList[] = VteSession::getArray(array('relatedlist', $currentModule, $relationId));
				}
			}
		}
		return $moduleList;
	}
	
	public static function saveRelatedModuleStartPage($relationId, $start) {
		global $currentModule;
		
		VteSession::setArray(array('rlvs', $currentModule, $relationId, 'start'), $start);
	}
	
	public static function getCurrentPage($relationId) {
		global $currentModule;
		
		if(!VteSession::isEmptyArray(array('rlvs', $currentModule, $relationId, 'start'))){
			return VteSession::getArray(array('rlvs', $currentModule, $relationId, 'start'));
		}
		return 1;
	}
	
	public static function getRequestStartPage(){
		$start = $_REQUEST['start'];
		if(!is_numeric($start)){
			$start = 1;
		}
		if($start < 1){
			$start = 1;
		}
		$start = ceil($start);
		return $start;
	}
	
	public static function getRequestCurrentPage($relationId, $query) {
		global $list_max_entries_per_page, $adb;
		
		$start = 1;
		if(!empty($_REQUEST['start'])){
			$start = $_REQUEST['start'];
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
		}else {
			$start = RelatedListViewSession::getCurrentPage($relationId);
		}
		return $start;
	}
	
	//crmv@3086m
	function getListViewNavigation($relationId, $currentRecordId){
		global $currentModule,$current_user,$adb,$log,$list_max_entries_per_page,$table_prefix;
		Zend_Json::$useBuiltinEncoderDecoder = true;
		$reUseData = false;
		$displayBufferRecordCount = 10;
		$bufferRecordCount = 15;
		
		if(!VteSession::isEmpty($relationId.'_RelatedList_Navigation')){
			$recordNavigationInfo = Zend_Json::decode(VteSession::get($relationId.'_RelatedList_Navigation'));
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
				$start = RelatedListViewSession::getRequestStartPage();
			}else{
				$start = RelatedListViewSession::getCurrentPage($relationId);
			}
			$startRecord = (($start - 1) * $list_max_entries_per_page) - $bufferRecordCount;
			if($startRecord < 0){
				$startRecord = 0;
			}

			if($currentModule == 'Calendar')
				$mod_listquery = "activity_listquery";
			else
				$mod_listquery = strtolower($currentModule)."_listquery";
			$list_query = VteSession::get($mod_listquery);

			$instance = CRMEntity::getInstance($currentModule);
			$instance->getNonAdminAccessControlQuery($currentModule, $current_user);
			vtlib_setup_modulevars($currentModule, $instance);
			
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
				if (!empty($forAllCRMID[$instance->table_index])) {
					$navigationRecordList[] = $forAllCRMID[$instance->table_index];
				} elseif (!empty($forAllCRMID['crmid'])) {
					$navigationRecordList[] = $forAllCRMID['crmid'];
				}
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
			VteSession::set($relationId.'_RelatedList_Navigation', Zend_Json::encode($recordNavigationInfo));
		}
		return $recordNavigationInfo;
	}
	//crmv@3086me
}
?>