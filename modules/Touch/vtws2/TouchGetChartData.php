<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@99131 */

class TouchGetChartData extends TouchWSClass {

	function process(&$request) {
		
		$module = 'Charts';
		$recordid = intval($request['record']);
		$level = intval($request['level']) ?: 1;
		if ($level > 1) {
			$levelIds = Zend_Json::decode($request['level_ids']) ?: array();
		} else {
			$levelIds = array();
		}
		
		if (!$recordid) {
			return $this->error("No record specified");
		} elseif (isPermitted($module, 'DetailView', $recordid) != 'yes') {
			return $this->error("Not permitted");
		}
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($recordid, $module);
		
		if ($level == 1) {
			$focus->reloadReport();
		}
		
		$data = $focus->getChartData($level, $levelIds);

		if (!$data) return $this->error("Invalid data returned");
		
		// fix html chars
		if (is_array($data['labels'])) {
			foreach ($data['labels'] as &$label) {
				$label = html_entity_decode($label, ENT_QUOTES, 'UTF-8');
			}
		}
		
		$data['level'] = $level;
		$data['levelids'] = $focus->getLevelIdsValues($levelIds);
		$data['maxlevels'] = $focus->getMaxLevels();
		
		if ($data['maxlevels'] > 1) {
			$data['leveltitles'] = $focus->getLevelTitles();
		}
		
		return $this->success(array('data' => $data));
	}
	
}