<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
global $login, $userId, $current_user, $currentModule;

$module = $_REQUEST['module'];
$relModule = $_REQUEST['relmodule'];
$recordid = intval($_REQUEST['record']);
$relRecordid = intval($_REQUEST['relrecord']);
$relationId = intval($_REQUEST['relationid']);


if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {

	$currentModule = $module;

	if ($recordid > 0 && $relRecordid > 0) {
		$skip_check = false;

		// Calendar fix (Oh tu orrenda creatura, possa morire tra le fiamme!)
		if ($module == 'Events') {
			$currentModule = $module = 'Calendar';
		}

		$focus = CRMEntity::getInstance($currentModule);

		if ($relationId >= 2700000 && $relationId < 2800000) {
			// it's an invitation

			$focus->retrieve_entity_info($recordid, $currentModule);
			if ($relationId == 2700000) {
				// contacts
				$inviteeTable = $table_prefix.'_invitees_con';
			} elseif ($relationId == 2700001) {
				// users
				$inviteeTable = $table_prefix.'_invitees';
			}
			$check = $adb->pquery("select activityid from $inviteeTable where activityid = ? and inviteeid = ?", array($recordid, $relRecordid));
			if ($check && $adb->num_rows($check) == 0) {
				// insert invitee
				$adb->pquery("insert into $inviteeTable (activityid, inviteeid, partecipation) values(?,?,?)", array($recordid, $relRecordid, 0));

				// send notification
				$mail_contents = $focus->getRequestData($recordid,$focus);
				$focus->sendInvitation($relRecordid,'edit',$focus->column_fields['subject'],$mail_contents,$recordid,$relRecordid);
			}

		} else {
			$focus->save_related_module($currentModule, $recordid, $relModule, $relRecordid, $skip_check);
		}
	}

	//echo Zend_Json::encode(array('success' => $returnok, 'result' => $returndata, 'error' => $errormsg));
}
?>