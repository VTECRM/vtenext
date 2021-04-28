<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@174249 */

class TouchMessagesIcs extends TouchWSClass {

	public function process(&$request) {
		global $touchInst, $touchUtils;
		
		$result = array();
		
		if (in_array('Messages', $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$action = $request['action'];
		$crmid = intval($request['crmid']);
		$icalid = $request['icalid'];
		$delEvent = ($request['del_event'] == 1);
		
		$result['crmid'] = $crmid;
		$result['icalid'] = $icalid;
		
		$focus = CRMEntity::getInstance('Messages');
		if ($crmid) {
			$focus->id = $crmid;
		}

		$error = null;
		if ($action == 'reply_yes') {

			$r = $focus->sendIcalReply($icalid, 'yes');
			if ($r) {
				// now create the event
				$activityid = 0;
				$r = $focus->createEventFromIcal($icalid, $activityid);
				$result['activityid'] = $activityid;
				if (!$r) $error = 'Could not create the event';
			} else {
				$error = 'Could not send the reply';
			}
			
			$result = array_merge($result, array('success' => empty($error), 'error' => $error));
			
		} elseif ($action == 'reply_no') {

			$r = $focus->sendIcalReply($icalid, 'no');
			if ($r) {
				// crmv@81126
				if ($delEvent) {
					$r = $focus->deleteEventFromIcal($icalid);
					if (!$r) $error = 'Could not delete the event';
				} else {
					$r = $focus->cancelEventFromIcal($icalid);
				}
				// crmv@81126e
			} else {
				$error = 'Could not send the reply';
			}
		} else {
			$error = 'Unknown action requested';
		}
		
		// ste the modified time
		$touchUtils->updateTimestamp('Messages', $crmid);
		
		// crmv@182005
		if ($error) {
			return $this->error($error);
		} else {
			return $this->success($result);
		}
		// crmv@182005e
	}
	
}
