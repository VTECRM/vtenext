<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TimecardsHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $adb, $current_user;
		global $table_prefix;
		// check irs a timcard we're saving.
		if (!($entityData->focus instanceof Timecards)) {
			return;
		}

		if($eventName == 'vte.entity.beforesave') {//crmv@207852
			//crmv@14132
			$timecardsid = $entityData->getId();
			$old_worktime = '';
			if(!empty($timecardsid)) {
				$result = $adb->pquery('SELECT worktime,ticket_id FROM '.$table_prefix.'_timecards WHERE timecardsid = ?', array($timecardsid)); // crmv@136739
				if($adb->num_rows($result) > 0) {
					$old_worktime = $adb->query_result($result,0,'worktime');
					$old_ticket_id = $adb->query_result_no_html($result,0,'ticket_id'); // crmv@136739
				}
			}
			$entityData->old_worktime = $old_worktime;
			$entityData->old_ticket_id = $old_ticket_id; // crmv@136739
			//crmv@14132e
		}

		if($eventName == 'vte.entity.aftersave') {//crmv@207852
			//crmv@14132
			$data = $entityData->getData();
			// crmv@136739
			if ($data['ticket_id'] != $entityData->old_ticket_id && $entityData->old_ticket_id > 0) {
				$change_hours = 0;
				$change_days = 0;
				
				//recalc old ticket
				$setypeCond = '';
				if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
					$setypeCond = "AND {$table_prefix}_crmentity.setype = 'Timecards'";
				}
		
				$result = $adb->pquery(
					"SELECT worktime
					FROM {$table_prefix}_timecards 
					INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_timecards.timecardsid $setypeCond
					WHERE deleted=0 AND ticket_id = ?", 
					array($entityData->old_ticket_id)
				);
				while($row = $adb->fetchByAssoc($result)){
					$change_sec = Timecards::get_seconds($row['worktime']);
					$change_hours += $change_sec/60/60;
					$change_days += $change_hours/24;
				}
				
				$tktFocus = CRMEntity::getInstance('HelpDesk');
				$tktFocus->id = $entityData->old_ticket_id;
				$tktFocus->mode = 'edit';
				$tktFocus->retrieve_entity_info_no_html($tktFocus->id,'HelpDesk');
				
				$tktFocus->column_fields['hours'] = $change_hours;
				$tktFocus->column_fields['days'] = $change_days;

				// force an update, because it's not working for unknown reasons
				$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET hours = ? WHERE ticketid = ?', array($tktFocus->column_fields['hours'],$tktFocus->id));
				$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET days = ? WHERE ticketid = ?', array($tktFocus->column_fields['days'],$tktFocus->id));
				
				// Empty Comments
				$_REQUEST['comments'] = '';
				$tktFocus->column_fields['comments'] = '';
			
				$tktFocus->save('HelpDesk');
				
				$change_hours = 0;
				$change_days = 0;
				
				//recalc new ticket
				$result_new = $adb->pquery(
					"SELECT worktime 
					FROM {$table_prefix}_timecards 
					INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_timecards.timecardsid $setypeCond
					WHERE deleted=0 AND ticket_id = ?", 
					array($data['ticket_id'])
				);
				while($row_new = $adb->fetchByAssoc($result_new)){
					$change_sec = Timecards::get_seconds($row_new['worktime']);
					$change_hours += $change_sec/60/60;
					$change_days += $change_hours/24;
				}
				
				$tktFocus_new = CRMEntity::getInstance('HelpDesk');
				$tktFocus_new->id = $data['ticket_id'];
				$tktFocus_new->mode = 'edit';
				$tktFocus_new->retrieve_entity_info_no_html($tktFocus_new->id,'HelpDesk');
				
				$tktFocus_new->column_fields['hours'] = $change_hours;
				$tktFocus_new->column_fields['days'] = $change_days;

				// force an update, because it's not working for unknown reasons
				$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET hours = ? WHERE ticketid = ?', array($tktFocus_new->column_fields['hours'],$tktFocus_new->id));
				$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET days = ? WHERE ticketid = ?', array($tktFocus_new->column_fields['days'],$tktFocus_new->id));
				
				// Empty Comments
				$_REQUEST['comments'] = '';
				$tktFocus_new->column_fields['comments'] = '';
			
				$tktFocus_new->save('HelpDesk');
			
			} else {
			
				if ($data['worktime'] != $entityData->old_worktime) {
					$change_sec = Timecards::get_seconds($data['worktime']) - Timecards::get_seconds($entityData->old_worktime);
					$change_hours = $change_sec/60/60;
					$change_days = $change_hours/24;
					// crmv@101363
					$tktFocus = CRMEntity::getInstance('HelpDesk');
					$tktFocus->id = $data['ticket_id'];
					$tktFocus->mode = 'edit';
					$tktFocus->retrieve_entity_info_no_html($data['ticket_id'],'HelpDesk');
					
					$tktFocus->column_fields['hours'] += $change_hours;
					$tktFocus->column_fields['days'] += $change_days;

					// force an update, because it's not working for unknown reasons
					$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET hours = ? WHERE ticketid = ?', array($tktFocus->column_fields['hours'],$tktFocus->id));
					$adb->pquery('UPDATE '.$table_prefix.'_troubletickets SET days = ? WHERE ticketid = ?', array($tktFocus->column_fields['days'],$tktFocus->id));
					
					// Empty Comments
					$_REQUEST['comments'] = '';
					$tktFocus->column_fields['comments'] = '';
				
					$tktFocus->save('HelpDesk');
					// crmv@101363e
				}
			}
			// crmv@136739e
			
		}
	}
}
?>