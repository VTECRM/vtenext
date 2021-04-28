<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */
global $login, $userId;

$module = $_REQUEST['module'];
$relmodule = $_REQUEST['relmodule'];
$relationid = intval($_REQUEST['relationid']);
$parentid = intval($_REQUEST['parentid']);
$delrecordid = intval($_REQUEST['record']);

if(!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (in_array($module, $touchInst->excluded_modules)) {
	echo "Module not permitted";
} else {



	if ($relationid >= 2700000 && $relationid < 2800000) {
		// it's an invitation
		if ($relationid == 2700000) {
			// contacts
			$inviteeTable = $table_prefix.'_invitees_con';
		} elseif ($relationid == 2700001) {
			// users
			$inviteeTable = $table_prefix.'_invitees';
		}
		if ($inviteeTable && isPermitted('Calendar', 'EditView', $parentid) == 'yes') {
			$adb->pquery("delete from $inviteeTable where activityid = ? and inviteeid = ?", array($parentid, $delrecordid));
		}

	} else {

		$tabid = getTabid($module);
		$reltabid = getTabid($relmodule);

		// controllo dati della relazione
		$res = $adb->pquery("select * from {$table_prefix}_relatedlists where relation_id = ?", array($relationid));

		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->fetchByAssoc($res, -1, false);

			if ($row['tabid'] != $tabid || $row['related_tabid'] != $reltabid) {
				die('ERROR: Invalid relation id');
			}

			$focus = CRMEntity::getInstance($module);
			$focus->delete_related_module($module, $parentid, $relmodule, $delrecordid);
		}
	}

	$returnok = true;
	echo Zend_Json::encode(array('success' => $returnok, 'error' => $errormsg));
}
?>