<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@26030m crmv@OPER4876 crmv@189362 */

require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');

class CalendarHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $adb, $current_user, $table_prefix;

		$moduleName = $entityData->getModuleName();
		$data = $entityData->getData();
		$id = $entityData->getId();

		if ($moduleName == 'Activity' && $eventName == 'vte.entity.beforesave' && !empty($id)) {

			$focus = $entityData->getData();
			$focus_beforesave = CRMEntity::getInstance('Calendar');
			$focus_beforesave->id = $id;
			$focus_beforesave->retrieve_entity_info($focus_beforesave->id,'Events');
			if ($entityData->isNew()) {
				$mode = 'create';
			} else {
				$mode = 'edit';
			}
			
			//crmv@32334
			if (isZMergeAgent()) {
				//do nothing
			} else {
			//crmv@32334e
				if ((getValidDBInsertDateValue($focus['date_start']) != $focus_beforesave->column_fields['date_start']) ||
					(getValidDBInsertDateValue($focus['due_date'])   != $focus_beforesave->column_fields['due_date'])   ||
					($focus['time_start'] != $focus_beforesave->column_fields['time_start']) ||
					($focus['time_end']   != $focus_beforesave->column_fields['time_end']))  {

					if(isset($_REQUEST['inviteesid']) && $_REQUEST['inviteesid']!='' && $_REQUEST['inviteesid']!= '--none--') {	//crmv@27443
						$inviteesid = $selected_users_string =  $_REQUEST['inviteesid'];
						$this->resetInvitees($table_prefix.'_invitees',array_filter(explode(';', $selected_users_string)),$id);
					} else {
						$partecipations = array();
						$res = $adb->pquery("select inviteeid, partecipation from ".$table_prefix."_invitees where activityid=?", array($id));
						if ($res && $adb->num_rows($res)>0) {
							$inviteesid = array();
							while($row=$adb->fetchByAssoc($res)) {
								$inviteesid[] = $row['inviteeid'];
								$partecipations[$row['inviteeid']] = $row['partecipation'];
							}
							$inviteesid = implode(';',$inviteesid);
							$this->resetInvitees($table_prefix.'_invitees',array_keys($partecipations),$id);
						}
					}

					if(isset($_REQUEST['inviteesid_con']) && $_REQUEST['inviteesid_con']!='' && $_REQUEST['inviteesid_con']!= '--none--') {	//crmv@27443
						$inviteesid_con = $selected_users_string =  $_REQUEST['inviteesid_con'];
						$this->resetInvitees($table_prefix.'_invitees_con',array_filter(explode(';', $selected_users_string)),$id);
					} else {
						$partecipations_con = array();
						$res = $adb->pquery("select inviteeid, partecipation from ".$table_prefix."_invitees_con where activityid=?", array($id));
						if ($res && $adb->num_rows($res)>0) {
							$inviteesid_con = array();
							while($row=$adb->fetchByAssoc($res)) {
								$inviteesid_con[] = $row['inviteeid'];
								$partecipations_con[$row['inviteeid']] = $row['partecipation'];
							}
							$inviteesid_con = implode(';',$inviteesid_con);
							$this->resetInvitees($table_prefix.'_invitees_con',array_keys($partecipations_con),$id);
						}
					}

					// crmv@114646
					// reset the reminder flag also
					if ($mode == 'edit') {
						$adb->pquery("UPDATE {$table_prefix}_activity_reminder SET reminder_sent = 0 WHERE activity_id = ?", array($id));
					}
					// crmv@114646e
				}
				if ($mode == 'edit') {
					// crmv@76088 - use new values!
					$focus_beforesave->column_fields = array_merge($focus_beforesave->column_fields, $focus);
					// crmv@76088e
					$focus_beforesave->column_fields['date_start'] = getValidDBInsertDateValue($focus['date_start']);
					$focus_beforesave->column_fields['due_date'] = getValidDBInsertDateValue($focus['due_date']);
					$focus_beforesave->column_fields['time_start'] = $focus['time_start'];
					$focus_beforesave->column_fields['time_end'] = $focus['time_end'];
					$mail_contents = $focus_beforesave->getRequestData($id,$focus_beforesave); //crmv@32334
					$focus_beforesave->sendInvitation($inviteesid,$mode,$focus_beforesave->column_fields['subject'],$mail_contents,$id,$inviteesid_con); //crmv@32334
				}
			}
		}
		
		if ($eventName == 'vte.entity.beforesave') {
			if ($moduleName == 'Activity') {
				// split all day events in multiple events using SLA intervals
				if ($entityData->isNew() && !isset($entityData->duplicate_intervals) && ($data['is_all_day_event'] == 'on' || $data['is_all_day_event'] == 1)) {
					$parent_module = getSalesEntityType($data['parent_id']);
					if (in_array($parent_module,array('ProjectTask','HelpDesk'))) ($parent_module == 'ProjectTask') ? $projecttaskid = $data['parent_id'] : $projecttaskid = getSingleFieldValue($table_prefix.'_troubletickets', 'projecttaskid', 'ticketid', $data['parent_id']);
					$servicetype = getSingleFieldValue($table_prefix.'_projecttask', 'servicetype', 'projecttaskid', $projecttaskid);
					if (!empty($servicetype)) {
						$entityData->duplicate_intervals = array();
						$dayofweek = date('w');
						require_once('modules/SLA/SLA.php');
						$SLAconfig = SLA::get_module_config('HelpDesk');
						if (isset($SLAconfig['hours'][$dayofweek])) {
							$entityData->set('is_all_day_event','off');
							$entityData->set('time_start',$SLAconfig['hours'][$dayofweek][0][0]);
							$entityData->set('time_end',$SLAconfig['hours'][$dayofweek][0][1]);
							if (count($SLAconfig['hours'][$dayofweek]) > 1) {
								foreach($SLAconfig['hours'][$dayofweek] as $day_interval => $day_interval_info) {
									if ($day_interval == 0) continue;
									$entityData->duplicate_intervals[] = $day_interval_info;
								}
							}
						}
					}
				}				
			} elseif ($moduleName == 'ProjectTask') {
				
				$calculateFields = false;
				if ($entityData->isNew()) {
					$calculateFields = true;
				} else {
					$focus = CRMEntity::getInstance($moduleName);
					$focus->retrieve_entity_info_no_html($id, $moduleName);
					if (
						($focus->column_fields['servicetype'] != $data['servicetype']) ||
						($data['servicetype'] == 'Project' && $focus->column_fields['salesprice'] != $data['salesprice']) ||
						($data['servicetype'] == 'Project' && $focus->column_fields['used_budget'] != $data['used_budget']) ||
						($data['servicetype'] == 'Project' && $focus->column_fields['assigned_user_id'] != $data['assigned_user_id'])
					) {
						$calculateFields = true;
					}
					$old_used_hours = $focus->column_fields['used_hours'];
					$old_invoiced_hours = $focus->column_fields['invoiced_hours'];
				}
				
				// use SLA monday configuration for the number of hours in a day
				$hours_in_a_day = 8;
				require_once('modules/SLA/SLA.php');
				$SLAconfig = SLA::get_module_config('HelpDesk');
				if (isset($SLAconfig['hours'][1])) {
					$minutes = 0;
					foreach($SLAconfig['hours'][1] as $day_interval_info) {
						$date1 = new DateTime($day_interval_info[0]);
						$date2 = new DateTime($day_interval_info[1]);
						$diff = $date2->diff($date1);
						$minutes += ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
					}
					if ($minutes > 0) $hours_in_a_day = $minutes/60;
				}
				
				if ($calculateFields) {
					if ($data['servicetype'] == 'Project') {
						$daily_cost = CalendarTracking::getDailyCost($id, $data['projectid']);
						($daily_cost > 0) ? $expected_hours = $data['salesprice'] / ($daily_cost / $hours_in_a_day) : $expected_hours = 0;
						$entityData->set('expected_hours',$expected_hours);
						$entityData->set('expected_days', round($expected_hours / $hours_in_a_day));
						$entityData->set('residual_budget', $data['salesprice'] - $data['used_budget']);
					} elseif ($data['servicetype'] == 'Package') {
						$entityData->set('residual_hours', $data['package_hours'] - $data['used_hours']);
					}
				}
				
				if ($data['used_hours'] != $old_used_hours || $data['invoiced_hours'] != $old_invoiced_hours) {
					if ($data['servicetype'] == 'Project') {
						$daily_cost = CalendarTracking::getDailyCost($id, $data['projectid']);
						$used_budget = $data['used_hours'] * ($daily_cost / $hours_in_a_day);
						$entityData->set('used_budget', $used_budget);
						
						$residual_budget = $data['salesprice'] - $used_budget;
						$entityData->set('residual_budget',$residual_budget);
						// DEPRECATED if ($residual_budget <= $residual_budget_limit) CalendarTracking::notifyResidualBudgetLimitReached($residual_budget_not_user, $id, $data['projecttaskname']);
					} elseif ($data['servicetype'] == 'Package') {
						$residual_hours = $data['package_hours'] - $data['used_hours'];
						$entityData->set('residual_hours', $residual_hours);
						// DEPRECATED if ($residual_hours <= $residual_hour_limit) CalendarTracking::notifyResidualHourLimitReached($residual_hour_not_user, $id, $data['projecttaskname']);
					} elseif ($data['servicetype'] == 'Consumptive') {
						$daily_cost = CalendarTracking::getDailyCost($id, $data['projectid']);
						$used_budget = $data['used_hours'] * ($daily_cost / $hours_in_a_day);
						$entityData->set('used_budget', $used_budget);
						$entityData->set('hours_to_be_invoiced', $data['used_hours'] - $data['invoiced_hours']);
					}
				}
			}
		} elseif($eventName == 'vte.entity.aftersave') {

			if ($moduleName == 'Activity') {
				$parent_module = getSalesEntityType($data['parent_id']);
				if (in_array($parent_module,array('ProjectTask','HelpDesk'))) {
					($parent_module == 'ProjectTask') ? $projecttaskid = $data['parent_id'] : $projecttaskid = getSingleFieldValue($table_prefix.'_troubletickets', 'projecttaskid', 'ticketid', $data['parent_id']);
					if ($parent_module == 'HelpDesk') {
						$focus = CRMEntity::getInstance('HelpDesk');
						$focus->retrieve_entity_info_no_html($data['parent_id'], 'HelpDesk');
						$hd_hours = CalendarTracking::roundHours(CalendarTracking::recalculateHours($data['parent_id'], 'HelpDesk'));
						if ($hd_hours != round($focus->column_fields['hours'],2)) {
							$focus->id = $data['parent_id'];
							$focus->mode = 'edit';
							$focus->column_fields['hours'] = $hd_hours;
							$focus->column_fields['comments'] = '';
							$focus->save('HelpDesk');
						}
					}
					if (!empty($projecttaskid)) {
						$focus = CRMEntity::getInstance('ProjectTask');
						$focus->retrieve_entity_info_no_html($projecttaskid, 'ProjectTask');
						$pt_hours = CalendarTracking::roundHours(CalendarTracking::recalculateHours($projecttaskid, 'ProjectTask'));
						if ($pt_hours != round($focus->column_fields['used_hours'],2)) {
							$focus->id = $projecttaskid;
							$focus->mode = 'edit';
							$focus->column_fields['used_hours'] = $pt_hours;
							$focus->save('ProjectTask');
						}
					}
				}
				// split all day events in multiple events using SLA intervals
				if (!empty($entityData->duplicate_intervals)) {
					foreach($entityData->duplicate_intervals as $duplicate_interval) {
						$focus = CRMEntity::getInstance('Activity');
						$focus->retrieve_entity_info_no_html($id, 'Events');
						$focus->column_fields['time_start'] = $duplicate_interval[0];
						$focus->column_fields['time_end'] = $duplicate_interval[1];
						$focus->save('Events');
					}
				}
			}
		}
	}
	function resetInvitees($table,$invitees_array,$activityid) {
		global $adb;
		if (!empty($invitees_array) && $activityid != '') {
			$invitees = implode(',',$invitees_array);
			$resetInvitees_query = "UPDATE $table SET partecipation = 0 WHERE activityid = $activityid AND inviteeid IN (".$invitees.")";
			$resetInvitees_params = array($activityid);
			$adb->pquery($resetInvitees_query, $resetInvitees_params);
		}
	}
}