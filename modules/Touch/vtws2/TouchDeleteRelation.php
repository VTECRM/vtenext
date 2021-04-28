<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchDeleteRelation extends TouchWSClass {

	public $validateModule = true;

	public function process(&$request) {
		global $adb, $table_prefix, $touchUtils;

		$module = $request['module'];
		$relmodule = $request['relmodule'];
		$relationid = intval($request['relationid']);
		$parentid = intval($request['parentid']);
		$delrecordid = intval($request['record']);

		if ($touchUtils->isInviteesRelated($relationid)) {
			// it's an invitation
			if ($relationid == $touchUtils->related_blockids['invitees']) {
				// contacts
				$inviteeTable = $table_prefix.'_invitees_con';
			} elseif ($relationid == $touchUtils->related_blockids['invitees']+1) {
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
					return $this->error("Invalid relation id");
				}

				$focus = $touchUtils->getModuleInstance($module);
				$focus->delete_related_module($module, $parentid, $relmodule, $delrecordid);
			}
		}

		return $this->success();
	
	}
}
