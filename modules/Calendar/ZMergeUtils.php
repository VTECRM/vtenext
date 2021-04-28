<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@zmerge
class ZMerge extends VTEventHandler {

	function handleEvent($eventName, $data) {
		global $adb, $current_user,$table_prefix;

		if (!($data->focus instanceof Activity)) {
			return;
		}
		if($eventName == 'vte.entity.aftersave') {
			$id = $data->getId();
			$this->setZMergeEvents($id);
		}
	}
	function setZMergeEvents($id) {
		global $adb,$table_prefix;
		if (isZMergeAgent()) {
			$zmerge = 1;	//tramite zmerge con web services
		} else {
			$zmerge = 0;	//tramite interfaccia
		}
		$query = "select subject, activitytype from ".$table_prefix."_activity where activityid=?";
		$result = $adb->pquery($query, array($id));
		$actType = $adb->query_result($result,0,'activitytype');
		$subject = $adb->query_result($result,0,'subject');
		$type_values = getActivityTypeValues('event','array');
		$outgoing_call = false;
		if (strpos($subject,'Outgoing call') !== false && $actType == 'Call') {
			$outgoing_call = true;
		}
		if(is_array($type_values) && in_array($actType,$type_values) && !$outgoing_call) //crmv@42329
		{
			$modid = $adb->getUniqueID("tbl_s_zmerge_events");
			$params = array($modid,$id,date('Y-m-d H:i:s'),$zmerge);
			$adb->pquery('insert into tbl_s_zmerge_events values ('.generateQuestionMarks($params).')',array($params));
		}
	}
}
function getLastChanges($modid,$complete_changes) {
	global $adb;
	if ($complete_changes) {
		$chtype = '';
	} else {
		$chtype = 'and zmerge = 0';
	}
	$params = array($modid);
	$result = $adb->pquery("select distinct(crmid) as crmid, max(modid) as modid, modtime from tbl_s_zmerge_events where modid > ? $chtype group by crmid order by modid",array($params));
	$res = array();
	while($row=$adb->fetchByAssoc($result)) {
//crmv@zmergelollo
		$res[$row['modid']] = array('crmid' => $row['crmid'], 'modtime' => $row['modtime']);
//crmv@zmergelollo e
	}
	return $res;
}
function isDeleted($id) {
	global $adb,$table_prefix;
	$id = explode('x',$id);
	$id = $id[1];
	$result = $adb->pquery("select * from ".$table_prefix."_crmentity where crmid=?", array($id));
    if(!$result || $adb->query_result($result,0,"deleted") == 1) {
    	return true;
    }
    return false;
}
function getAttendee($id) {
	global $adb,$table_prefix;
	$id = explode('x',$id);
	$id = $id[1];
    $attendees = array();
	$sql = 'select '.$table_prefix.'_users.id, '.$table_prefix.'_invitees.partecipation from '.$table_prefix.'_invitees left join '.$table_prefix.'_users on '.$table_prefix.'_invitees.inviteeid='.$table_prefix.'_users.id where activityid=?';
	$result = $adb->pquery($sql,array($id));
    while($row = $adb->fetchByAssoc($result)) {
	//crmv@zmergelollo
	// return the attendees CRMID instead of email
	$attendees[$row['id']] = $row['partecipation'];
	//crmv@zmergelollo e	
	}
	$sql = 'select email, partecipation from '.$table_prefix.'_other_invitees where deleted = 0 and activityid=?';
	$result = $adb->pquery($sql,array($id));
    while($row = $adb->fetchByAssoc($result)) {
    	$attendees[$row['email']] = $row['partecipation'];
	}
	return $attendees;
}
function setAttendee($id,$attendees,$mode) {
	$id = explode('x',$id);
	$id = $id[1];
	$users = array();
	$partecipations = array();
	$other_partecipations = array();
	foreach ($attendees as $attendee => $partecipation) {
		//crmv@zmergelollo
		if (is_numeric($attendee))
		{
			// attendee che hanno un utente in CRM
			$users[] = $attendee;
			$partecipations[$attendee] = $partecipation;
		}
		else		
		{
			//altri indirizzi
			$other_partecipations[$attendee] = $partecipation;
		}
		//crmv@zmergelollo e
	}
	$focus = CRMEntity::getInstance('Events');
	$focus->id = $id;
	$focus->retrieve_entity_info($id,'Events');
	$focus->mode = $mode;
	$focus->insertIntoInviteeTable('Events',$users,$partecipations,'',$other_partecipations);

	require_once('modules/Calendar/CalendarCommon.php');
	$mail_contents = $focus->getRequestData($focus->id,$focus); //crmv@32334
	if ($mode == '') {
		$mail_contents['mode'] = '';
	}
	$focus->sendInvitation(implode(';',$users),$mode,$focus->column_fields['subject'],$mail_contents,$focus->id); //crmv@32334
}
function getAssignedUserIdFromZEmail($email) {
	//crmv@zmergelollo

	// return the user id associated with the email
	$userId = getAssignedUserIdFromField($email,'email1');
	if ($userId) return $userId;

	$userId = getAssignedUserIdFromField($email,'zimbra_username');
	if ($userId) return $userId;

	$userId = getAssignedUserIdFromField($email,'google_username');
	if ($userId) return $userId;

	$userId = getAssignedUserIdFromField($email,'exchange_username');
	if ($userId) return $userId;

	// email doesn't match any user field
	return false;
	//crmv@zmergelollo e
}
function getAssignedUserIdFromField($email,$field) {
	global $adb,$table_prefix;
	$result = $adb->pquery("SELECT id FROM ".$table_prefix."_users WHERE $field = ? AND status = 'Active'",array($email));
	if ($result && $adb->num_rows($result)>0) {
		return $adb->query_result($result,0,'id');
	}
	return false;
}
// crmv@77797
function getEmailFromUserId($id) {
	global $adb,$table_prefix;

	$columns = array();
	$fieldNames = array('zimbra_username', 'google_username', 'exchange_username');
	$res = $adb->pquery("SELECT fieldid, columnname FROM {$table_prefix}_field WHERE tabid = ? AND fieldname IN (".generateQuestionMarks($fieldNames).")", array(getTabid('Users'), $fieldNames));
	if ($res && $adb->num_rows($res) > 0) {
		while($row = $adb->fetchByAssoc($res, -1, false)) {
			$columns[] = $row['columnname'];
		}
	}

	$result = $adb->pquery("SELECT email1".(count($columns) > 0 ? ", ".implode(", ", $columns) : '')." FROM ".$table_prefix."_users WHERE id = ? AND status = 'Active'",array($id));
	if ($result && $adb->num_rows($result)>0) {
		//crmv@zmergelollo
		$email1 = $adb->query_result($result,0,'email1');
		if ($email1 != '') {
			return $email1;
		}
		$zimbra_username = $adb->query_result($result,0,'zimbra_username');
		if ($zimbra_username != '') {
			return $zimbra_username;
		}
		$google_username = $adb->query_result($result,0,'google_username');
		if ($google_username != '') {
			return $google_username;
		}
		$exchange_username = $adb->query_result($result,0,'exchange_username');
		if ($exchange_username != '') {
			return $exchange_username;
		}
		//crmv@zmergelollo e
	}
	return false;
}
// crmv@77797e
function getZMergeUsers($connector) {
	global $adb,$table_prefix;
	$zm_users = array();
	if ($connector != '') {
		// crmv@36039
		$result = $adb->query("SELECT id, {$connector}_username, {$connector}_password, user_timezone FROM ".$table_prefix."_users WHERE {$connector}_username <> '' AND {$connector}_username IS NOT NULL AND {$connector}_password <> '' AND {$connector}_password IS NOT NULL");
		if ($result && $adb->num_rows($result)>0) {
			while($row=$adb->fetchByAssoc($result)) {
				$zm_users[$row['id']] = array('username'=>$row["{$connector}_username"],'password'=>Users::de_cryption($row["{$connector}_password"]), 'timezone'=>$row["user_timezone"]);
			}
		}
		// crmv@36039e
	}
	return $zm_users;
}
//crmv@zmerge e
?>