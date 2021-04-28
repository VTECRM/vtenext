<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@68357

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$messageid = intval($_REQUEST['messageid']);
$icalid = intval($_REQUEST['icalid']);
$action = $_REQUEST['subaction'];
$raw = null;
//$tpl = '';
$json = null;

$focus = CRMEntity::getInstance('Messages');
if ($messageid) {
	$focus->id = $messageid;
}

$result = array();
$error = null;
if ($action == 'ReplyYes') {

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
	
	$json = array('success' => empty($error), 'error' => $error, 'result' => $result);
	
} elseif ($action == 'ReplyNo') {

	$r = $focus->sendIcalReply($icalid, 'no');
	if ($r) {
		// crmv@81126
		if ($_REQUEST['del_event'] == 1) {
			$r = $focus->deleteEventFromIcal($icalid);
			if (!$r) $error = 'Could not delete the event';
		} else {
			$r = $focus->cancelEventFromIcal($icalid);
		}
		// crmv@81126e
	} else {
		$error = 'Could not send the reply';
	}
	$json = array('success' => empty($error), 'error' => $error);
	
}

// output
if (!is_null($raw)) {
	echo $raw;
	exit(); // sorry, I have to do this, some html shit is spitted out at the end of the page
/*
} elseif (!empty($tpl)) {
	$smarty->display('Settings/DataImporter/'.$tpl);
*/
} elseif (!empty($json)) {
	echo Zend_Json::encode($json);
	exit(); // idem
} else {
	echo "No data returned";
}