<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetAreas extends TouchWSClass {

	protected $cacheLife = 21600;	// 6 hours

	public function clearCache() {
		global $touchCache;
		$touchCache->delete('areas');
	}
	
	function process(&$request) {
		global $touchInst, $touchCache;
		
		// check cache
		$cachedAreas = $touchCache->get('areas');
		if ($cachedAreas) return $this->success(array('areas' => $cachedAreas));
		
		
		if (!is_readable('modules/Area/Area.php')) return $this->error('Area module not installed');
		require_once('modules/Area/Area.php');

		$am = AreaManager::getInstance();

		$alist = $am->getModuleList('all');

		$outlist = array();
		foreach ($alist as $k=>$area) {
			if ($area['name'] == 'HightlightArea' || $area['id'] < 0) continue;

			$modlist = array();
			foreach ($area['info'] as $modinfo) {
				if (in_array($modinfo['name'], $touchInst->excluded_modules)) continue;
				$modlist[] = array('module'=>$modinfo['name'], 'label'=>getTranslatedString($modinfo['name'], $modinfo['name']));
			}
			if (count($modlist) == 0) continue;
			$outlist[] = array(
				'areaid' => $area['id'],
				'name' => $area['name'],
				'label' => $area['translabel'],
				'modules' => $modlist,
			);
		}

		$touchCache->set('areas', $outlist, $this->cacheLife);
		return $this->success(array('areas' => $outlist));
	}

}
