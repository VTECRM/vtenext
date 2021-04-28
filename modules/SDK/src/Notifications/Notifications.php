<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
class Notifications {

	var $user;
	var $type;

	function __construct($userid,$type) {
		$this->user = $userid;
		$this->type = $type;
	}

	function getUserNotificationNo() {
		global $adb, $table_prefix;
		if ($this->type == 'ModComments') {
			$no = 0;
			$parent_comments = array();
			// crmv@64325
			$setypeCond = '';
			if (PerformancePrefs::getBoolean('CRMENTITY_PARTITIONED')) {
				$setypeCond = "AND {$table_prefix}_crmentity.setype = 'ModComments'";
			}
			$result = $adb->pquerySlave('BadgeCount','select id, parent_comments from vte_notifications inner join '.$table_prefix.'_modcomments on '.$table_prefix.'_modcomments.modcommentsid = vte_notifications.id inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_modcomments.modcommentsid where deleted = 0 '.$setypeCond.' and userid = ?',array($this->user)); // crmv@185894
			// crmv@64325e
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$parent_comments[] = $row['id'];
					if (empty($row['parent_comments']) || !in_array($row['parent_comments'],$parent_comments)) {
						$no++;
						$parent_comments[] = $row['parent_comments'];
					}
				}
			}
			return $no;
		} else {
			$result = $adb->pquerySlave('BadgeCount','select id from vte_notifications where userid = ? and type = ?',array($this->user,$this->type)); // crmv@185894
			if ($result) {
				return $adb->num_rows($result);
			} else {
				return 0;
			}
		}
	}

	//crmv@43448
	function addNotification($id, $forced = 0) {
		global $adb;
		$result = $adb->pquery('select id from vte_notifications where id = ? and userid = ? and type = ?',array($id,$this->user,$this->type));
		if (!$result || $adb->num_rows($result) == 0) {
			$result = $adb->pquery('insert into vte_notifications (id,userid,type, forced) values (?,?,?,?)',array($id,$this->user,$this->type, $forced));
		}
	}

	function deleteNotification($id, $deleteForced = false) {
		global $adb;
		if ($deleteForced)
			$whereForced = '';
		else
			$whereForced = ' AND forced = 0';
		$result = $adb->pquery('delete from vte_notifications where id = ? and userid = ? and type = ? '.$whereForced,array($id,$this->user,$this->type));
	}
	//crmv@43448e

	function deleteAllNotificationForComment($id) {
		global $adb;
		$result = $adb->pquery('delete from vte_notifications where id = ? and type = ?',array($id,$this->type));
	}
}
?>