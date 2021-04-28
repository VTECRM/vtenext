<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@124979 */

require_once "modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php";

class TouchTrackingList extends TouchWSClass {
	
	public function process(&$request) {
		
		$method = $request['method'];
		
		if ($method == 'list') {
			return $this->getTrackingList($request);
		} elseif ($method == 'save_state') {
			return $this->saveTrackingState($request);
		} elseif ($method == 'change_state') {
			return $this->changeTrackingState($request);
		} elseif ($method == 'get_tracker_data') {
			return $this->getTrackerData($request);
		} else {
			return $this->error('Unknown method');
		}
	}
	
	public function getTrackingList(&$request) {
		global $current_user, $table_prefix;
		
		$success = false;
		
		try {
			// TODO: get rid of this shit!
			ob_start();
			require "modules/SDK/src/CalendarTracking/TrackerManager.php";
			ob_end_clean();
			$success = true;
		} catch (Exception $e) {
			$error = $e->getMessage();
			$success = false;
		}
		
		$trackingList = array();
		
		$list = $list ?: array();
		
		foreach ($list as $crmid => $details) {
			$active = $active_tracked && $crmid != $active_tracked;
			
			$trackingList[] = array(
				'record' => $crmid,
				'module' => $details['module'],
				'entityname' => $details['name'],
				'enable' => $details['enable'],
				'active' => $active,
			);
		}
		
		if ($success) {
			return $this->success(array('entries' => $trackingList, 'total' => count($trackingList)));
		} else {
			return $this->error($error);
		}
	}
	
	public function saveTrackingState(&$request) {
		
		$success = false;
		
		try {
			$record = intval($request['record']);
			
			switch ($request['type']) {
				case 'play':
					if (getActiveTracked() === false) {
						activateTrack($record);
					}
					break;
				case 'pause':
					break;
				case 'stop':
					break;
				case 'eject':
					deleteTracking($record);
					break;
			}
			
			$success = true;
		} catch (Exception $e) {
			$error = $e->getMessage();
			$success = false;
		}
		
		if ($success) {
			return $this->success();
		} else {
			return $this->error($error);
		}
	}
	
	public function changeTrackingState(&$request) {
		global $adb, $touchInst, $current_user, $table_prefix;
	
		$success = false;
		
		try {
			$record = intval($request['record']);
			$description = vtlib_purify($request['description']);
			$type = vtlib_purify($request['type']);
			$method = vtlib_purify($request['method']);
			$createTicket = vtlib_purify($request['create_ticket']);
			
			if ($type == 'play') $type = 'start';
			
			$requestBkp = $_REQUEST;
			$_REQUEST = array();
			$_REQUEST['record'] = $record;
			$_REQUEST['description'] = $description;
			$_REQUEST['type'] = $type;
			$_REQUEST['mode'] = $method;
			$_REQUEST['create_ticket'] = $createTicket;
			$_REQUEST['dont_die'] = 1;
			
			ob_start();
			require "modules/SDK/src/CalendarTracking/ChangeTrackState.php";
			$out = ob_get_contents();
			ob_end_clean();
			
			$_REQUEST = $requestBkp;
			
			// Salvo commento sul ticket, soluzione brutta ma funziona
			if ($currentModule == 'HelpDesk' && !empty($description)) {
				$requestBkp = $_REQUEST;
				$_REQUEST = array();
				$_REQUEST['module'] = 'HelpDesk';
				$_REQUEST['action'] = 'DetailViewAjax';
				$_REQUEST['record'] = $record;
				$_REQUEST['recordid'] = $record;
				$_REQUEST['fldName'] = 'comments';
				$_REQUEST['fieldValue'] = $description;
				$_REQUEST['ajxaction'] = 'DETAILVIEW';
				
				ob_start();
				require "modules/HelpDesk/DetailViewAjax.php";
				$out = ob_get_contents();
				ob_end_clean();
				
				$_REQUEST = $requestBkp;
			}
			
			$success = true;
		} catch (Exception $e) {
			$error = $e->getMessage();
			$success = false;
		}
		
		if ($success) {
			return $this->success();
		} else {
			return $this->error($error);
		}
	}
	
	public function getTrackerData(&$request) {
		
		$success = false;
		
		try {
			$module = intval($request['module']);
			$record = intval($request['record']);
			
			$trackerData = CalendarTracking::getTrackerData($module, $record);
			
			$success = true;
		} catch (Exception $e) {
			$error = $e->getMessage();
			$success = false;
		}
		
		if ($success) {
			return $this->success(array('data' => $trackerData));
		} else {
			return $this->error($error);
		}
	}
	
}