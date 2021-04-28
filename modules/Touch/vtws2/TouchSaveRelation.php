<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */

class TouchSaveRelation extends TouchWSClass {
	
	public $validateModule = true;
	
	function process(&$request) {
		global $adb, $table_prefix, $touchInst, $touchUtils,  $currentModule;
		
		$module = $request['module'];
		$relModule = $request['relmodule'];
		$recordid = intval($request['record']);
		$relRecordid = intval($request['relrecord']);
		$relationId = intval($request['relationid']);
		
		// TODO: validations
		
		$currentModule = $module;

		if ($recordid > 0 && $relRecordid > 0) {
			$skip_check = false;

			// Calendar fix (Oh tu orrenda creatura, possa morire tra le fiamme!)
			if ($module == 'Events') {
				$currentModule = $module = 'Calendar';
			}

			$focus = $touchUtils->getModuleInstance($currentModule);

			if ($touchUtils->isInviteesRelated($relationId)) {
				// it's an invitation

				$focus->retrieve_entity_info($recordid, $currentModule);
				if ($relationId == $touchUtils->related_blockids['invitees']) {
					// contacts
					$inviteeTable = $table_prefix.'_invitees_con';
				} elseif ($relationId == $touchUtils->related_blockids['invitees'] + 1) {
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

		return $this->success();
	}
}
