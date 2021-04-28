<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetRecords extends TouchWSClass {

	public $longOperation = true;

	function process(&$request) {
		global $touchInst, $touchUtils;
		
		$reqModule = $request['module'];	// this param is optional, but if provided, it is used as the module for all the crmid passed, saving some time
		$crmids = array_filter(array_map('intval', Zend_Json::decode($request['records']) ?: array()));
		$folderList = $request['get_folders_list'];
		$filterList = $request['get_filters_list'];
		
		$ret = array();

		if (is_array($crmids)) {

			foreach ($crmids as $crmid) {
				$module = $reqModule ?: $touchUtils->getTouchModuleNameFromId($crmid);
				if (empty($module) || in_array($module, $touchInst->excluded_modules)) {
					continue;
				}

				$fakeRequest = array(
					'module' => $module,
					'record' => $crmid,
					'skip_pdflist' => $request['skip_pdflist'],
					'get_folders_list' => $folderList,
					'get_filters_list' => $filterList,
				);
				$record = $this->subcall('GetRecord', $fakeRequest);
				if ($record['success']) {
					$ret[] = $record;
				}
			}
		}

		return array('success'=>true, 'records' => $ret, 'total'=>count($ret));
	}
	

}
