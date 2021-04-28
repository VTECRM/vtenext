<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@194723

require_once('modules/Calendar/Appointment.php');
require_once('modules/Calendar/wdCalendar/php/functions.php');

$action = $_REQUEST['subaction'];

$calendarResourcesController = new CalendarResourcesController();

if ($action === 'fetch_data') {
	$events = $calendarResourcesController->fetchData($_REQUEST);
	echo Zend_Json::encode(['data' => $events]);
} elseif ($action === 'fetch_roles') {
	echo Zend_Json::encode($calendarResourcesController->fetchRoles($_REQUEST));
} elseif ($action === 'save_resources') {
	echo Zend_Json::encode($calendarResourcesController->saveResources($_REQUEST));
} else {
	echo Zend_Json::encode($calendarResourcesController->index());
}

exit();

class CalendarResourcesController {
	
	protected $cacheUserResourceList = array();
	protected $cacheUserShownList = array();
	
	public function index() {
		global $current_user;
		
		$output = array();
		
		$smarty = $this->initSmarty();
		
		$output['columns'] = $this->getColumns();
		$output['html'] = $smarty->fetch('modules/Calendar/CalendarResources.tpl');
		
		return $output;
	}
	
	public function fetchData($request) {
		global $current_user;
		
		$startDate = $request['start_date'] ?? '';
		$endDate = $request['end_date'] ?? '';
		
		/* Get the Monday and Sunday of the week */
		$ts = strtotime($startDate);
		$start = (date('w', $ts) == 1) ? $ts : strtotime('last monday', $ts);
		
		$startDate = date('Y-m-d', $start);
		$endDate = date('Y-m-d', strtotime('next sunday', $start));
		
		$events = $this->getAppointments($current_user->id, $startDate, $endDate);
		
		return $events;
	}
	
	public function fetchRoles($request) {
		global $current_user;
		
		$output = array();
		
		$output['title'] = getTranslatedString('LBL_SELECT_RESOURCES', 'Calendar');
		
		$output['labels'] = array(
			'LBL_UNSELECT_ALL' => getTranslatedString('LBL_UNSELECT_ALL'),
			'LBL_CLOSE' => getTranslatedString('LBL_CLOSE'),
			'LBL_SAVE_LABEL' => getTranslatedString('LBL_SAVE_LABEL')
		);
		
		$currentUser = $current_user->id;
		
		$rolesStructure = $this->getRolesStructure();
		$allRoleDetails = getAllRoleDetails();
		$currentRoleId = $current_user->roleid;
		$maskRole = getRoleAndSubordinatesRoleIds($currentRoleId);
		
		$smarty = $this->initSmarty();
		
		$rolesHtml = '';
		$rolesHtml .= $this->generateRolesHtml($rolesStructure, $rolesHtml, $allRoleDetails, $maskRole, $currentUser);
		
		$smarty->assign('ROLES_HTML', $rolesHtml);
		
		$output['html'] = $smarty->fetch('modules/Calendar/CalendarResourcesSettings.tpl');
		
		return $output;
	}
	
	public function saveResources($request) {
		global $adb, $current_user;
		
		$output = array();
		
		$currentUser = $current_user->id;
		
		$delquery = "DELETE FROM tbl_s_calendar_resources WHERE userid = ?";
		$adb->pquery($delquery, array($currentUser));
		
		$selectedResources = $request['selected_resources'] ?: array();
		$selectedResources = array_map('intval', $selectedResources);
		$selectedResources = array_filter($selectedResources);
		
		$currentRoleId = $current_user->roleid;
		
		foreach ($selectedResources as $userId) {
			$isAvailable = $this->isResourceAvailable($userId, $currentUser);
			if ($isAvailable) {
				$sql = "INSERT INTO tbl_s_calendar_resources (userid, shownid) VALUES (?, ?)";
				$adb->pquery($sql, array($currentUser, $userId));
			}
		}
		
		$output['success'] = true;
		
		return $output;
	}
	
	protected function getAppointments($userId, $startDate, $endDate) {
		global $adb, $table_prefix;
		
		$calFocus = CRMEntity::getInstance('Calendar');
		$users = $calFocus->getUserResourceList($userId);
		
		$events = $this->initStructure($users, $startDate, $endDate);
		
		$startDateObj = new DateTime($startDate);
		$endDateObj = new DateTime($endDate);
		$vtStartDate = new vt_DateTime(array('ts' => $startDateObj->getTimestamp()), true);
		$vtEndDate = new vt_DateTime(array('ts' => $endDateObj->getTimestamp()), true);
		$viewFilter = implode(',', array_keys($users));
		
		$appointmentInstance = Appointment::getInstance();
		$sql = $appointmentInstance->readAppointment($userId, $vtStartDate, $vtEndDate, '', $viewFilter, true);
		
		$result = $adb->query($sql);
		
		if (!!$result && $adb->num_rows($result)) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$activityId = (int) $row['crmid'];
				$smownerid = (int) $row['smownerid'];
				
				/* Get the list of users who can see the event */
				$inviteeList = $this->getInviteeList($activityId);
				$activityUserList = array_merge(array($smownerid), array_keys($inviteeList));
				$activityUserList = array_unique($activityUserList);
				
				/* Add the event for each user on the list who can see it */
				
				foreach ($activityUserList as $activityUser) {
					/* If the user has not been selected in the resource list, skip */
					if (!isset($events[$activityUser])) continue;
					
					$activitytype = $row['activitytype'];
					$eventType = strtolower($activitytype);
					
					$addActivity = true;
					$invitedIcon = "";
					$movable = $activityUser === $smownerid;
					
					$activityTypeIcon = 'phone';
					
					if ($eventType === 'meeting') {
						$activityTypeIcon = 'people';
					} elseif ($eventType === 'cose da fare') {
						$activityTypeIcon = 'assignment';
					} elseif ($eventType === 'visita cliente') {
						$activityTypeIcon = 'work';
					}
					
					$userCalColor = '#' . substr(getUserColorDb($smownerid, $activityId), 0, 6);
					
					/* Check the status of the invitation if the user is an invitee */
					if (isset($inviteeList[$activityUser])) {
						$answer = '';
						
						$partecipation = $inviteeList[$activityUser]['partecipation'];
						
						switch ($partecipation) {
							case 0:
								$answer = 'pending';
								break;
							case 1:
								$answer = 'no';
								break;
							case 2:
								$answer = 'yes';
								break;
							case 3:
								$answer = 'maybe';
								break;
						}
						
						if ($answer === 'no') {
							$addActivity = false;
						}
						
						if ($eventType != 'meeting' && $eventType != 'call') {
							$eventType = 'default';
						}
						
						$invitedIcon = resourcever("modules/Calendar/wdCalendar/css/images/calendar/" . $eventType . $answer . ".png");
					}
					
					if (!$addActivity) continue;
					
					/* If the current event has a duration of several days, add multiple events from start to the end of the event */
					
					$fromDate = new DateTime(date('Y-m-d', strtotime($row['date_start'])));
					$interval = DateInterval::createFromDateString('1 day');
					$toDate = new DateTime(date('Y-m-d', strtotime($row['due_date'])));
					$toDate->modify('+1 day');
					
					$period = new DatePeriod($fromDate, $interval, $toDate);
					
					$eventSequence = 0;
					
					foreach ($period as $dt) {
						if (!($dt >= $startDateObj && $dt <= $endDateObj)) continue;
						
						$dateStart = $dt->format('Y-m-d');
						$dayOfWeek = strtolower($dt->format('D'));
						
						$event = array(
							'crmid' => $activityId,
							'subject' => $row['subject'],
							'current_date_start' => $dateStart,
							'date_start' => $row['date_start'],
							'due_date' => $row['due_date'],
							'time_start' => $row['time_start'],
							'time_end' => $row['time_end'],
							'owner_id' => $smownerid,
							'user_id' => $activityUser,
							'activitytype' => $row['activitytype'],
							'activitytype_icon' => $activityTypeIcon,
							'cal_color' => $userCalColor,
							'invited_icon' => $invitedIcon,
							'movable' => $movable ? 1 : 0,
							'sequence' => $eventSequence,
						);
						
						$events[$activityUser]['all_events'][$dayOfWeek]['events'][] = $event;
						
						++$eventSequence;
					}
				}
			}
		}
		
		$events = array_values($events);
		
		return $events;
	}
	
	protected function initStructure($users, $startDate, $endDate) {
		$fromDate = new DateTime(date('Y-m-d', strtotime($startDate)));
		$interval = DateInterval::createFromDateString('1 day');
		$toDate = new DateTime(date('Y-m-d', strtotime($endDate)));
		$toDate->modify('+1 day');
		
		$period = new DatePeriod($fromDate, $interval, $toDate);
		
		$structure = array();
		
		foreach ($users as $user => $userInfo) {
			$userStructure = array('user' => array(), 'all_events' => array());
			
			$userStructure['user']['id'] = $user;
			$userStructure['user']['user_name'] = $userInfo['user_name'];
			$userStructure['user']['first_name'] = $userInfo['first_name'];
			$userStructure['user']['last_name'] = $userInfo['last_name'];
			
			$firstName = ucfirst(strtolower($userInfo['first_name']));
			$lastName = ucfirst(strtolower($userInfo['last_name']));
			$completeName = $firstName . ' ' . $lastName;
			
			$userStructure['user']['complete_name'] = $completeName;
			$userStructure['user']['cal_color'] = '#' . substr($userInfo['cal_color'], 0, 6);
			
			$userStructure['user']['avatar'] = getUserAvatar($user);
			
			foreach ($period as $dt) {
				$dayOfWeek = strtolower($dt->format('D'));
				$userStructure['all_events'][$dayOfWeek] = array(
					'slot_date' => $dt->format('Y-m-d'),
					'slot_date_display' => getDisplayDate($dt->format('Y-m-d')),
					'events' => array(),
				);
			}
			
			$structure[$user] = $userStructure;
		}
		
		return $structure;
	}
	
	protected function getInviteeList($activityId) {
		global $adb, $table_prefix;
		
		$activityUserList = array();
		
		$checkInvitationRes = $adb->pquery("SELECT * FROM {$table_prefix}_invitees WHERE activityid = ?", array($activityId));
		if (!!$checkInvitationRes && $adb->num_rows($checkInvitationRes) > 0) {
			while ($inviteeRow = $adb->fetchByAssoc($checkInvitationRes, -1, false)) {
				$inviteeid = (int) $inviteeRow['inviteeid'];
				$activityUserList[$inviteeid] = $inviteeRow;
			}
		}
		
		return $activityUserList;
	}
	
	protected function getColumns() {
		$columns = array(
			array(
				'title' => getTranslatedString('LBL_WEEK', 'Calendar'),
				'visible' => true,
				'data' => 'user',
				'className' => 'column-user user-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY1', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.mon',
				'className' => 'column-mon day-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY2', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.tue',
				'className' => 'column-tue day-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY3', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.wed',
				'className' => 'column-wed day-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY4', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.thu',
				'className' => 'column-thu day-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY5', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.fri',
				'className' => 'column-fri day-cell'
			),
			array(
				'title' => getTranslatedString('LBL_DAY6', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.sat',
				'className' => 'column-sat day-cell slot-date-holiday'
			),
			array(
				'title' => getTranslatedString('LBL_DAY0', 'Calendar'),
				'visible' => true,
				'data' => 'all_events.sun',
				'className' => 'column-sun day-cell slot-date-holiday'
			),
		);
		
		return $columns;
	}
	
	protected function getRolesStructure() {
		global $adb, $table_prefix;
		
		$rolesStructure = array();
		
		$roleRes = $adb->query("SELECT * FROM {$table_prefix}_role ORDER BY parentrole ASC");
		
		if (!!$roleRes && $adb->num_rows($roleRes) > 0) {
			while ($row = $adb->fetchByAssoc($roleRes, -1, false)) {
				$roleid = $row['roleid'];
				$parent = $row['parentrole'];
				$parentList = explode('::', $parent);
				$size = sizeof($parentList);
				
				$i = 0;
				$k = array();
				$y = $rolesStructure;
				
				if (sizeof($rolesStructure) === 0) {
					$rolesStructure[$parentList[0]] = array();
				} else {
					while ($i < $size - 1) {
						$y = $y[$parentList[$i]];
						$k[$parentList[$i]] = $y;
						$i++;
					}
					$y[$roleid] = array();
					$k[$roleid] = array();
					
					$reverseParentList = array_reverse($parentList);
					$j = 0;
					
					foreach ($reverseParentList as $value) {
						if ($j === $size - 1) {
							$rolesStructure[$value] = $k[$value];
						} else {
							$k[$reverseParentList[$j + 1]][$value] = $k[$value];
						}
						$j++;
					}
				}
			}
		}
		
		return $rolesStructure;
	}
	
	protected function generateRolesHtml($rolesStructure, $html, $allRoleDetails, $maskRole, $currentUser) {
		foreach ($rolesStructure as $roleid => $value) {
			$details = $allRoleDetails[$roleid];
			
			$rolename = to_html($details[0]);
			$roledepth = $details[1];
			$roleids = $details[2];
			
			$html .= '<ul class="uil" id="' . $roleid . '">';
			
			$html .= '<li>';
			
			if (sizeof($value) > 0 && $roledepth != 0) {
				$html .= '<i class="vteicon md-sm md-link valign-bottom" id="img_' . $roleid . '" title="' . getTranslatedString('LBL_EXPAND_COLLAPSE') . '" onclick="CalendarResources.toggleRoleVisibility(\'' . $roleids . '\',\'' . $roleid . '\')" >indeterminate_check_box</i>';
			} elseif ($roledepth != 0) {
				$html .= '<i class="vteicon md-sm valign-bottom" id="img_' . $roleid . '" title="' . getTranslatedString('LBL_EXPAND_COLLAPSE') . '">label_outline</i>';
			} else {
				$html .= '<i class="vteicon valign-bottom" id="img_' . $roleid . '" title="' . getTranslatedString('LBL_ROOT') . '">apps</i>';
			}
			
			if ($roledepth == 0 || !in_array($roleid, $maskRole)) {
				$html .= '&nbsp;<b class="genHeaderGray">' . $rolename . '</b>';
			} else {
				$html .= '&nbsp;<a class="x" href="javascript:void(0);" onclick="CalendarResources.checkAllRoleUsers(this, \'' . $roleid . '\');">' . $rolename . '</a>';
			}
			
			$html .= '</li>';
			
			if (!($roledepth == 0)) {
				$roleUsers = $this->getRoleUserIds($roleid);
				foreach ($roleUsers as $userId => $userName) {
					$isAvailable = $this->isResourceAvailable($userId, $currentUser);
					$isChecked = $isAvailable && $this->isResourceSelected($userId, $currentUser);
					
					$checkChecked = $isChecked ? 'checked' : '';
					$checkDisabled = !$isAvailable ? 'disabled' : '';
					$userItemClass = !$isAvailable ? 'useritem-disabled' : 'useritem';
					
					$html .= '<li class="' . $userItemClass . ' useritem_' . $roleid . '"><div class="checkbox"><label><input type="checkbox" class="usercheck usercheck_' . $roleid . '" value="' . $userId . '" ' . $checkChecked . ' ' . $checkDisabled . '>' . $userName . '</label></div></li>';
				}
			}
			
			if (sizeof($value) > 0) {
				$html = $this->generateRolesHtml($value, $html, $allRoleDetails, $maskRole, $currentUser);
			}
			
			$html .= '</ul>';
		}
		
		return $html;
	}
	
	protected function getRoleUserIds($roleId) {
		global $adb, $table_prefix;
		
		$roleRelatedUsers = array();
		
		$query = "SELECT {$table_prefix}_users.id 
			FROM {$table_prefix}_user2role 
			INNER JOIN {$table_prefix}_users ON {$table_prefix}_users.id = {$table_prefix}_user2role.userid 
			WHERE roleid = ? AND {$table_prefix}_users.status = 'Active'
			ORDER BY {$table_prefix}_users.user_name";
		
		$result = $adb->pquery($query, array($roleId));
		
		if (!!$result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$roleRelatedUsers[$row['id']] = getOwnerName($row['id'], true);
			}
		}

		return $roleRelatedUsers;
	}
	
	// crmv@202029
	protected function isResourceSelected($userId, $currentUser) {
		if (!isset($this->cacheUserResourceList[$currentUser])) {
			$calFocus = CRMEntity::getInstance('Calendar');
			$users = array_keys($calFocus->getUserResourceList($currentUser));
			$this->cacheUserResourceList[$currentUser] = $users;
		} else {
			$users = $this->cacheUserResourceList[$currentUser];
		}
		
		return in_array($userId, $users);
	}
	// crmv@202029e
	
	// crmv@202029
	protected function isResourceAvailable($userId, $currentUser) {
		if (!isset($this->cacheUserShownList[$currentUser])) {
			$calFocus = CRMEntity::getInstance('Calendar');
			$users = array_keys($calFocus->getShownUserId($currentUser));
			$users[] = $currentUser;
			$this->cacheUserShownList[$currentUser] = $users;
		} else {
			$users = $this->cacheUserShownList[$currentUser];
		}
		
		return in_array($userId, $users);
	}
	// crmv@202029e
	
	protected function initSmarty() {
		global $mod_strings, $app_strings, $theme;
		
		$smarty = new VteSmarty();
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH", "themes/$theme/images/");
		
		return $smarty;
	}
	
}