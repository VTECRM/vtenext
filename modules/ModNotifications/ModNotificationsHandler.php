<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@start
class ModNotificationsHandler extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $current_user, $currentModule,$table_prefix;
		
		$currentModule_tmp = $currentModule;
		$currentModule = 'ModNotifications';
		$obj = ModNotifications::getInstance(); // crmv@164122
		$already_notified_users = array();
		$id = $data->getId();
		$column_fields = $data->getData();
		$moduleName = $data->getModuleName();
		if ($data->isNew()) {
			$mode = 'create';
		} else {
			$mode = 'edit';
			
			// crmv@198950
			// check if changelog was created. if not, don't create the notification!
			require_once('modules/ChangeLog/ChangeLog.php');
			$clog = ChangeLog::getInstance();
			if (!$clog->hasSavedChangelog($id)) {
				$currentModule = $currentModule_tmp;
				return;
			}
			// and clear them, so i can save the same record more times
			$clog->clearSavedChangelogs($id);
			// crmv@198950e
		}
		//crmv@47905
		/*		
		if ($data->focus instanceof ModNotifications && $mode == 'create') {
			if ($eventName == 'vte.entity.beforesave') {
				$data->notify_me_via_email = false;
				$userFocus = CRMEntity::getInstance('Users');
				$userFocus->retrieve_entity_info($column_fields['assigned_user_id'],'Users');
				if ($userFocus->column_fields['notify_me_via'] == 'Emails') {
					$data->set('seen',1);
					$data->notify_me_via_email = true;
				}
			}
			if ($eventName == 'vte.entity.aftersave.notifications') { // crmv@198950
				if ($column_fields['mod_not_type'] == 'ListView changed') {
					$adb->pquery('update '.$table_prefix.'_crmentity set smcreatorid = ? where crmid = ?',array(0,$id));
				}
				if ($data->notify_me_via_email) {
					require_once('modules/Emails/mail.php');
					global $HELPDESK_SUPPORT_NAME,$HELPDESK_SUPPORT_EMAIL_ID;
					$user_emailid = getUserEmailId('id',$column_fields['assigned_user_id']);
					if ($user_emailid != '') {
						$from_email = $column_fields['from_email'];
						if ($from_email == '') {
							$from_email = $HELPDESK_SUPPORT_EMAIL_ID;
						}
						$from_email_name = $column_fields['from_email_name'];
						if ($from_email_name == '') {
							$from_email_name = $HELPDESK_SUPPORT_NAME;
						}
						$subject = $column_fields['subject'];
						if ($subject == '') {
							$parent_module = getSalesEntityType($column_fields['related_to']);
							$recordName = getEntityName($parent_module,$column_fields['related_to']);
							$entityType = getSingleModuleName($parent_module,$column_fields['related_to']); //crmv@32334
							$subject = trim(getUserFullName($current_user->id)).' '.$obj->translateNotificationType($column_fields['mod_not_type'],'action');
							if ($column_fields['mod_not_type'] == 'Relation') {
								$relation_parent_id = $column_fields['description'];
								$relation_parent_module = getSalesEntityType($relation_parent_id);
								$relation_recordName = getEntityName($relation_parent_module,$relation_parent_id);
								$relation_entityType = getSingleModuleName($relation_parent_module,$relation_parent_id);
								$subject .= ' '.$relation_recordName[$column_fields['description']]." ($relation_entityType) ";
								$subject .= getTranslatedString('LBL_TO','ModComments');
							}
							if ($column_fields['mod_not_type'] == 'ListView changed') {
								$subject = $obj->translateNotificationType($column_fields['mod_not_type'],'action');
								global $app_strings;
								$result = $adb->query('SELECT * FROM '.$table_prefix.'_customview WHERE cvid = '.$column_fields['related_to']);
								if ($result) {
									$module = $adb->query_result($result,0,'entitytype');
									$entityType = getTranslatedString($module,$module);
									$viewname = $adb->query_result($result,0,'viewname');
									if ($viewname == 'All') {
										$viewname = $app_strings['COMBO_ALL'];
									} elseif($this->parent_module == 'Calendar' && in_array($viewname,array('Events','Tasks'))) {
										$viewname = $app_strings[$viewname];
									}
									$subject .= " $viewname ($entityType)";
								}
							} else {
								$subject .= ' '.$recordName[$column_fields['related_to']]." ($entityType)";
							}
						}
						$body = $column_fields['description'];
						if (in_array($column_fields['mod_not_type'],array('Relation','ListView changed'))) {
							$body = '';
						}
						if ($body == '') {
							if ($column_fields['mod_not_type'] == 'ListView changed') {
								$body = $obj->getBodyNotificationCV($id,$column_fields,$from_email_name);
							} else {
								$body = $obj->getBodyNotification($id,$column_fields,$from_email_name);
							}
						}
						$mail_status = send_mail($parent_module,$user_emailid,$from_email_name,$from_email,$subject,$body);
					}
				}
			}
		}
		*/
		//crmv@47905 e
		/*
		if ($data->focus instanceof HelpDesk) {
			if($_REQUEST['service'] == 'customerportal') {	//salvo la notifica direttamente in customerportal.php
				$already_notified_users[] = $column_fields['assigned_user_id'];
			}
			if ($eventName == 'vte.entity.beforesave') {
				if ($mode == 'create') {
					$data->old_ticketstatus = '';
					$data->old_solution = '';
					$data->old_assigned_user_id = '';
				} else {
					$oldHelpDesk = CRMEntity::getInstance('HelpDesk');
					$oldHelpDesk->retrieve_entity_info($id,'HelpDesk');
					$data->old_ticketstatus = $oldHelpDesk->column_fields['ticketstatus'];
					$data->old_solution = $oldHelpDesk->column_fields['solution'];
					$data->old_assigned_user_id = $oldHelpDesk->column_fields['assigned_user_id'];
				}
			}
			if ($eventName == 'vte.entity.aftersave.notifications' && !in_array($column_fields['assigned_user_id'],$already_notified_users)) { // crmv@198950
				if ($mode == 'create') {
					$type = 'Ticket created';
				} else {
					$type = 'Ticket changed';
				}
				if ($mode != 'edit' ||
					($column_fields['ticketstatus'] != $data->old_ticketstatus && $column_fields['ticketstatus'] == getTranslatedString('Closed','HelpDesk')) ||
					$column_fields['comments'] != '' ||
					$column_fields['solution'] != $data->old_solution ||
					$column_fields['assigned_user_id'] != $data->old_assigned_user_id)
				{
					$subject = $column_fields['ticket_no'].' [ '.getTranslatedString('LBL_TICKET_ID','HelpDesk').' : '.$id.' ] '.$column_fields['ticket_title'];
					$info = array();
					$info['mode'] = $mode;
					$info['sub'] = $column_fields['ticket_title'];
					$info['status'] = $column_fields['ticketstatus'];
					$info['category'] = $column_fields['ticketcategories'];
					$info['severity'] = $column_fields['ticketseverities'];
					$info['priority'] = $column_fields['ticketpriorities'];
					$info['description'] = $column_fields['description'];
					$info['solution'] = $column_fields['solution'];
					if (!in_array($column_fields['parent_id'],array('',0))) {
						$recordName = array_values(getEntityName(getSalesEntityType($column_fields['parent_id']), $column_fields['parent_id']));
						$recordName = $recordName[0];
						$info['parent_name'] = $recordName;
					}
					$body = getTicketDetails($id,$info);
					$notified_users = $obj->saveFastNotification(
						array(
							'assigned_user_id' => $column_fields['assigned_user_id'],
							'related_to' => $id,
							'mod_not_type' => $type,
							'createdtime' => $column_fields['modifiedtime'],
							'modifiedtime' => $column_fields['modifiedtime'],
							'subject' => $subject,
							'description' => $body,
						)
					);
					if(!empty($notified_users)) {
						foreach($notified_users as $notified_user) {
							$already_notified_users[] = $notified_user;
						}
					}
				}
			}
		}
		*/
		if ($data->focus instanceof Products) {
			if ($eventName == 'vte.entity.beforesave') {//crmv@207852
				$data->stock_level_check = false;
				if ($mode == 'create') {
					if($column_fields['qtyinstock'] != '' && $column_fields['reorderlevel'] != '' && $column_fields['qtyinstock'] < $column_fields['reorderlevel']) {
						$data->stock_level_check = true;
					}
				} else {
					$oldProducts = CRMEntity::getInstance('Products');
					$oldProducts->retrieve_entity_info($id,'Products');
					$data->old_qtyinstock = $oldProducts->column_fields['qtyinstock'];
					$data->old_reorderlevel = $oldProducts->column_fields['reorderlevel'];
					if ($column_fields['qtyinstock'] == '') {
						$column_fields['qtyinstock'] = 0;
					}
					if ($column_fields['reorderlevel'] == '') {
						$column_fields['reorderlevel'] = 0;
					}
					if(($column_fields['qtyinstock'] != $data->old_qtyinstock || $column_fields['reorderlevel'] != $data->old_reorderlevel) && $column_fields['qtyinstock'] < $column_fields['reorderlevel']) {
						$data->stock_level_check = true;
					}
				}
			}
			if ($eventName == 'vte.entity.aftersave.notifications' && $data->stock_level_check) { // crmv@198950 crmv@207852
				$productname = $column_fields['productname'];
				$qty_stk = $column_fields['qtyinstock'];
				$reord = $column_fields['reorderlevel'];
				$handler = $column_fields['assigned_user_id'];

				$handler_name = getUserName($handler);
				$sender_name = getUserName($current_user->id);
				$to_address= getUserEmail($handler);
				$subject =  $productname.' '.getTranslatedString('MSG_STOCK_LEVEL','Products');
				$body = getTranslatedString('MSG_DEAR','Products').' '.$handler_name.',<br><br>'.
						getTranslatedString('MSG_CURRENT_STOCK','Products').' '.$productname.' '.getTranslatedString('MSG_IN_OUR_WAREHOUSE','Products').' '.$qty_stk.'. '.getTranslatedString('MSG_PROCURE_REQUIRED_NUMBER','Products').' '.$reord.'.<br> '.
						getTranslatedString('MSG_SEVERITY','Products').'<br><br> '.
						getTranslatedString('MSG_THANKS','Products').'<br> '.
						$sender_name;

				$obj->saveFastNotification(
					array(
						'assigned_user_id' => $column_fields['assigned_user_id'],
						'related_to' => $id,
						'mod_not_type' => 'Product stock level',
						'createdtime' => $column_fields['createdtime'],
						'modifiedtime' => $column_fields['createdtime'],
						'subject' => $subject,
						'description' => $body,
						'from_email' => $current_user->email1,
						'from_email_name' => getUserFullName($current_user->id),
					),false
				);
			}
		}

		if ($obj->isEnabled($moduleName)) {

			if($eventName == 'vte.entity.aftersave.notifications') { // crmv@198950 crmv@207852
				
				$following_users = $obj->getFollowingUsers($id);
				if (!empty($following_users)) {
					foreach($following_users as $following_user) {
						if ($current_user->id != $following_user && !in_array($following_user,$already_notified_users)) {
							$notified_users = $obj->saveFastNotification(
								array(
									'assigned_user_id' => $following_user,
									'related_to' => $id,
									'mod_not_type' => 'Changed followed record',
									'createdtime' => $column_fields['modifiedtime'],
									'modifiedtime' => $column_fields['modifiedtime'],
								),false
							);
							if(!empty($notified_users)) {
								foreach($notified_users as $notified_user) {
									$already_notified_users[] = $notified_user;
								}
							}
						}
					}
				}

				if ($mode == 'create') {
					$type = 'Created record';
				} else {
					$type = 'Changed record';
				}
				$interested_users = $obj->getInterestedToModuleUsers($mode,$moduleName);
				
				$users = array();
				$result = $adb->pquery('SELECT id FROM '.$table_prefix.'_users WHERE id = ? AND deleted = 0 AND status = ?', array($column_fields['assigned_user_id'],'Active'));
				if ($result && $adb->num_rows($result) > 0) {
					$users[] = $column_fields['assigned_user_id'];
				} else {
					$result = $adb->pquery('SELECT groupid FROM '.$table_prefix.'_groups WHERE groupid = ?', array($column_fields['assigned_user_id']));
					if($result && $adb->num_rows($result) > 0) {
						$groupid = $adb->query_result($result,0,'groupid');
						require_once('include/utils/GetGroupUsers.php');
						$focus = new GetGroupUsers();
		        		$focus->getAllUsersInGroup($groupid);
		        		$group_users = $focus->group_users;
		        		if (!empty($group_users)) {
		        			$group_users_str = implode(',',$group_users);
		        			$result = $adb->pquery('select id from '.$table_prefix.'_users where id in ('.$group_users_str.') and deleted = 0 and status = ?',array('Active'));
		        			if($result && $adb->num_rows($result) > 0) {
		        				while($row=$adb->fetchByAssoc($result)) {
		        					$users[] = $row['id'];
		        				}
		        			}
		        		}
					}
				}
				foreach($interested_users as $interested_user) {
					if (in_array($interested_user,$already_notified_users)) {
						continue;
					}
					//if ($interested_user == $column_fields['assigned_user_id'] && $interested_user != $current_user->id) {
					if (in_array($interested_user,$users) && $interested_user != $current_user->id) {
						$notified_users = $obj->saveFastNotification(
							array(
								'assigned_user_id' => $interested_user,
								'related_to' => $id,
								'mod_not_type' => $type,
								'createdtime' => $column_fields['modifiedtime'],
								'modifiedtime' => $column_fields['modifiedtime'],
							),false
						);
						if(!empty($notified_users)) {
							foreach($notified_users as $notified_user) {
								$already_notified_users[] = $notified_user;
							}
						}
					}
				}
			}
		}
		
		$currentModule = $currentModule_tmp;
	}
}
//crmv@end