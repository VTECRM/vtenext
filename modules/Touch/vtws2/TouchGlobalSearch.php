<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGlobalSearch extends TouchWSClass {

	public function process(&$request) {
		global $touchInst, $adb, $current_user;
		
		$module = $request['module'];
		$searchModules = array_filter(explode(':', $request['modules']));
		$where = $request['where'];
		$areaid = intval($request['areaid']);
		$extrafields = $request['extrafields']; // crmv@86915

		$searchstr = $request['search'];
		
		require_once('include/utils/SearchUtils.php');
		$globalSearchModules = array();

		// choose the modules to search in
		if ($where == 'everywhere') {

			$search_onlyin = getAllModulesForTag();
			$globalSearchModules = array_keys(getSearchModules($search_onlyin));

		} elseif ($where == 'area') {

			require_once('modules/Area/Area.php');
			$am = AreaManager::getInstance();
			$area = $am->getModuleList($areaid);

			foreach ($area['info'] as $areamod) {
				$globalSearchModules[] = $areamod['name'];
			}

		} elseif ($where == 'modules') {

			$globalSearchModules = $searchModules;
		}

		// filter them with the ones available in the app
		$globalSearchModules = array_diff($globalSearchModules, $touchInst->excluded_modules);

		// and filter some non searchable modules
		$globalSearchModules = array_diff($globalSearchModules, array('ModComments')); // crmv@164120

		$SearchUtils = SearchUtils::getInstance();

		// do the searches
		$output = array();
		foreach ($globalSearchModules as $mod) {

			// crmv@89008
			// get the search fields
			$queryGenerator = QueryGenerator::getInstance($mod, $current_user);
			$queryGenerator->initForDefaultCustomView();
			$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
			$sfields = $controller->getBasicSearchFieldInfoList();
			$sfields2 = $SearchUtils->getUnifiedSearchFieldInfoList($mod);
			$sfields = array_merge($sfields, $sfields2);
			
			// and add them to the list of passed fields
			if (is_array($sfields) && count($sfields) > 0) {
				$extrafields = Zend_Json::decode($extrafields) ?: array();
				$extrafields = array_merge($extrafields, array_keys($sfields));
				$extrafields = Zend_Json::encode($extrafields);
			}
			// crmv@89008e

			$subrequest = array(
				'module' => $mod,
				'search' => $searchstr,
				'extrafields' => $extrafields, // crmv@86915
			);

			$res = $this->subcall('GetList', $subrequest);
			$output = array_merge($output, $res['entries']);
		}

		$output = array_values($output);
		$list_count = count($output);

		return $this->success(array('entries' => $output, 'total' => $list_count));
	}
}
