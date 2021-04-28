<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104562 */
class ProjectTaskHandler extends VTEventHandler {
	function handleEvent($eventName, $data) {
		if (!($data->focus instanceof ProjectTask)) {
			return;
		}
		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			$id = $data->getId();
			$module = $data->getModuleName();
			$focus = $data->getData();
			if ($focus['auto_working_days'] == 'on' || $focus['auto_working_days'] == 1) {
				$crmv_utils = CRMVUtils::getInstance();
				$working_days = $crmv_utils->number_of_working_days(getValidDBInsertDateValue($focus['startdate']), getValidDBInsertDateValue($focus['enddate']));
				$data->set('working_days', $working_days);
			}
		}
	}
}