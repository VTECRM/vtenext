<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@22700
require_once('include/utils/utils.php'); 

function migrateRelatedToTarget() {
	global $adb;
	global $table_prefix;
	$result = $adb->query('SELECT campaignid, campaignname, smownerid FROM '.$table_prefix.'_campaign INNER JOIN '.$table_prefix.'_crmentity ON crmid = campaignid WHERE deleted = 0');
	if ($result && $adb->num_rows($result)>0) {
		while($row=$adb->fetchByAssoc($result)) {
			$campaignid = $row['campaignid'];
			
			$focusTarget = CRMEntity::getInstance('Targets');
			$focusTarget->mode = '';
			$focusTarget->column_fields['targetname'] = $row['campaignname'];
			$focusTarget->column_fields['assigned_user_id'] = $row['smownerid'];
			$focusTarget->save('Targets');
			$targetid = $focusTarget->id;
			$focusTarget->save_related_module('Targets', $targetid, 'Campaigns', $campaignid);
			
			$result1 = $adb->query("SELECT accountid FROM ".$table_prefix."_campaignaccountrel WHERE campaignid = $campaignid");
			if ($result1 && $adb->num_rows($result1)>0) {
				while($row1=$adb->fetchByAssoc($result1)) {
					$focusAccount = CRMEntity::getInstance('Accounts');
					$focusAccount->save_related_module('Targets', $targetid, 'Accounts', $row1['accountid']);
				}
			}

			$result1 = $adb->query("SELECT contactid FROM ".$table_prefix."_campaigncontrel WHERE campaignid = $campaignid");
			if ($result1 && $adb->num_rows($result1)>0) {
				while($row1=$adb->fetchByAssoc($result1)) {
					$focusContact = CRMEntity::getInstance('Contacts');
					$focusContact->save_related_module('Targets', $targetid, 'Contacts', $row1['contactid']);
				}
			}

			$result1 = $adb->query("SELECT leadid FROM ".$table_prefix."_campaignleadrel WHERE campaignid = $campaignid");
			if ($result1 && $adb->num_rows($result1)>0) {
				while($row1=$adb->fetchByAssoc($result1)) {
					$focusLead = CRMEntity::getInstance('Leads');
					$focusLead->save_related_module('Targets', $targetid, 'Leads', $row1['leadid']);
				}
			}
		}
	}
}
//crmv@22700e
?>