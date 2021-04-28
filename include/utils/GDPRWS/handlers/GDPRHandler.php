<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

class GDPRHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $table_prefix;
		
		$id = $data->getId();
		$module = $data->focus->modulename;
		
		$GDPRWS = GDPRWS::getInstance();
		if (!$GDPRWS->isEnabledForModule($module)) {
			return false;
		}
		
		if ($eventName == 'vte.entity.beforesave') {//crmv@207852
			if (!$data->isNew()) {
				$oldNotifyChange = getSingleFieldValue($data->focus->table_name, 'gdpr_notifychange', $data->focus->table_index, $id);
				if ($oldNotifyChange === '1') {
					if ($data->get('gdpr_notifychange') === '1') {
						$GDPRWS->sendContactNotifyChangeEmail($module, $id);
					}
				}
			}
		}
	}

}