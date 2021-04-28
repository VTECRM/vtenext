<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetTodos extends TouchWSClass {

	function process(&$request) {
		global $touchInst, $current_user, $table_prefix; // crmv@73256
	
		require_once('modules/SDK/src/Todos/Utils.php');
	
		$list = getTodosList($current_user->id, '', $count);

		if ($request['onlycount'] == 'true') {
			return $this->success(array('total'=>$count));
			
		} else {

			$output = array();
			foreach ($list as $k=>$v) {
				//if (in_array($v['module'], $touchInst->excluded_modules)) continue;

				if ($v['is_now'] == 'yes') {
					$timestampAgo = getTranslatedString('LBL_TODAY');
				} else {
					$timestampAgo = getFriendlyDate($v['date']);
					// controllo se nel futuro
					if (strpos($timestampAgo,'-') !== false) {
						$timestampAgo = getTranslatedString('LBL_DAY'.date('w',strtotime($v['date'])),'Calendar').' '.getDisplayDate($v['date']);
					}
				}

				// crmv@39110 - get full description
				$v['description'] = getSingleFieldValue($table_prefix.'_activity', 'description', 'activityid', $v['activityid']); // crmv@150773 crmv@160250

				$record = array(
					'crmid' => $v['activityid'],
					'module' => 'Calendar',
					'entityname' => $v['subject'],
					'description' => $v['description'],
					'expired' => ($v['expired'] == 'yes'),
					'is_now' => ($v['is_now'] == 'yes'),
					'date' => $v['date'],
					'timestampago' => $timestampAgo,
				);
				$output[] = $record;
			}
			return $this->success(array('todos'=>$output, 'total'=>$count));
		}

	}
}
