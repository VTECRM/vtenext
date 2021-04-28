<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33097 */

class TouchGetRecents extends TouchWSClass {

	public $validateModule = false;	// module can be null

	public function process(&$request) {
		global $touchInst, $current_user;

		$module = $request['module'];
		
		// get allowable modules
		$req = array('onlynames' => 1);
		$modList = $this->subcall('ModulesList', $req);

		require_once('data/Tracker.php');
		$tracker = new Tracker();
		$rec = $tracker->get_recently_viewed($current_user->id, $module);
		$rec2 = array();

		// crmv@54449
		$excludedModules = $touchInst->excluded_modules;
		$excludedModules[] = 'Users';

		if (is_array($modList)) {
			$modList = array_filter(array_values($modList));
		} else {
			$modList = array();
		}
		$modList = array_diff($modList, $excludedModules);
		// crmv@54449e

		$sequence = 1; // crmv@73256
		// filtro per tenere solo l'essenziale
		foreach ($rec as $row) {
			if (!in_array($row['module_name'], $modList, true)) continue; // crmv@33311

			// crmv@37370 fox for calendar events
			if ($row['module_name'] == 'Calendar') {
				$activitytype = getActivityType($row['item_id']);
				// skip non events stuff
				if (in_array($activitytype, array('Webmails', 'Emails'))) continue; // crmv@152701

				// the rest is either a task or an event
				if ($activitytype != 'Task') $row['module_name'] = 'Events';
			}
			// crmv@37370e
			$row2 = array(
				'crmid'=>$row['item_id'],
				'module'=>$row['module_name'],
				'entityname'=>html_entity_decode($row['item_summary'], ENT_QUOTES, 'UTF-8'),
				'sequence' => $sequence++,	// crmv@73256
			);
			$rec2[] = $row2;
		}

		return $this->success(array('list'=>$rec2, 'total'=>count($rec2)));
	}
}
