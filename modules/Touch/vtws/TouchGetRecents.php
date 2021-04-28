<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
/* crmv@33097 */
require_once('data/Tracker.php');

global $login, $userId, $current_user;

$module = $_REQUEST['module'];

if (!$login || !$userId) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$tracker = new Tracker();
	$rec = $tracker->get_recently_viewed($userId, $module);
	$rec2 = array();

	// filtro per tenere solo l'essenziale
	foreach ($rec as $row) {
		if (in_array($row['module_name'], $touchInst->excluded_modules)) continue; // crmv@33311
		if ($row['module_name'] == 'Users') continue;

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
			'tabid'=>getTabId($row['module_name']),
			'entityname'=>html_entity_decode($row['item_summary'], ENT_QUOTES, 'UTF-8'),
			'recent' => 1
		);
		$rec2[] = $row2;
	}

	echo Zend_Json::encode($rec2);
}
?>