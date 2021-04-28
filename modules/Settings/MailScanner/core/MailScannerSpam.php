<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@56233 */

require_once('modules/SDK/src/204/204.php');
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailBox.php');

class Vtecrm_MailScannerSpam {
	
	var $_table;
	
	function __construct() {
		global $table_prefix;
		$this->_table = $table_prefix.'_mailscanner_spam';
	}
	
	function add2Queue($scannername, $xuid, $folder, $spam_folder) {
		global $adb;
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into {$this->_table} (scannername, xuid, folder, spam_folder) values (?,?,?,?)",array($scannername, $xuid, $folder, $spam_folder));
		} else {
			$this->removeFromQueue($scannername, $xuid, $folder);
			$adb->pquery("insert into {$this->_table} (scannername, xuid, folder, spam_folder) values (?,?,?,?)",array($scannername, $xuid, $folder, $spam_folder));
		}
	}
	
	function removeFromQueue($scannername, $xuid, $folder) {
		global $adb;
		$adb->pquery("delete from {$this->_table} where scannername = ? and xuid = ? and folder = ?",array($scannername, $xuid, $folder));
	}
	
	function processQueue($scannername='') {
		global $adb;
		$query = "select * from {$this->_table}";
		//crmv@208173
		if (!empty($scannername)) {
			$query .= " where scannername = ?";
            $result = $adb->pquery($query, array($scannername));
        } else{
            $result = $adb->pquery($query, array());
        }
        //crmv@208173e
        if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$scannerinfo = new Vtenext_MailScannerInfo($row['scannername']);//crmv@207843
				$mailbox = new Vtenext_MailBox($scannerinfo);//crmv@207843
				$mailbox->connect($row['folder']);
				$return = $mailbox->moveMessage($row['xuid'], $row['spam_folder'], true);
				$this->removeFromQueue($row['scannername'], $row['xuid'], $row['folder']);
			}
		}
	}
	
	function spam($record) {
		global $adb, $table_prefix;
		
		$module = getSalesEntityType($record);
		
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($record, $module);
		
		$deepClear = false;
		$record2Trash = array();
		$move2Spam = array();		
		
		if (!empty($focus->column_fields['parent_id'])) {
			$parent_id = $focus->column_fields['parent_id'];
			$parent_module = getSalesEntityType($parent_id);
			if ($parent_module == 'Leads') {
				$parentFocus = CRMEntity::getInstance($parent_module);
				$parentFocus->retrieve_entity_info($parent_id, $parent_module);
				if ($parentFocus->column_fields['leadsource'] == 'Mail Converter') {
					// check if all related tickets are created by MailConverter
					$result = $adb->pquery("SELECT crmid, mailscanner_action FROM {$table_prefix}_troubletickets
						INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_troubletickets.ticketid
						WHERE {$table_prefix}_crmentity.deleted = 0 AND parent_id = ?
						AND (mailscanner_action IS NULL OR mailscanner_action = '' OR mailscanner_action = 0)",
						array($parent_id));
					if ($adb->num_rows($result) == 0) {
						$deepClear = true;
					}
				}
			}
		}
		
		if ($deepClear) {
			$record2Trash[$parent_module][] = $parent_id;	// trash parent
			
			$result = $adb->pquery("SELECT crmid, mailscanner_action, scannername
						FROM {$table_prefix}_troubletickets
						INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_crmentity.crmid = {$table_prefix}_troubletickets.ticketid
						INNER JOIN {$table_prefix}_mailscanner_actions ON {$table_prefix}_mailscanner_actions.actionid = mailscanner_action
		  				INNER JOIN {$table_prefix}_mailscanner ON {$table_prefix}_mailscanner.scannerid = {$table_prefix}_mailscanner_actions.scannerid
						WHERE {$table_prefix}_crmentity.deleted = 0 AND parent_id = ?",
						array($parent_id));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByASsoc($result)) {
					$record2Trash[$module][] = $row['crmid'];	// trash all tickets associated
					
					$result2 = $adb->pquery("SELECT foldername
						FROM {$table_prefix}_mailscanner_folders
						INNER JOIN {$table_prefix}_mailscanner_actions ON {$table_prefix}_mailscanner_actions.scannerid = {$table_prefix}_mailscanner_folders.scannerid
						WHERE {$table_prefix}_mailscanner_actions.actionid = ? AND spam = 1",array($row['mailscanner_action']));
					if ($result2 && $adb->num_rows($result2) > 0) {
						$foldername = $adb->query_result($result2,0,'foldername');
					}
					
					// spam and trash all messages associated to tickets
					//crmv@171021
					$result1 = $adb->pquery("SELECT messagesid, folder, xuid
						FROM {$table_prefix}_messages
						INNER JOIN {$table_prefix}_messagesrel ON {$table_prefix}_messages.messagehash = {$table_prefix}_messagesrel.messagehash
						WHERE deleted = 0 AND mtype = ? AND {$table_prefix}_messagesrel.crmid = ?",array('Link',$row['crmid']));
					//crmv@171021e
					if ($result1 && $adb->num_rows($result1) > 0) {
						while($row1=$adb->fetchByAssoc($result1)) {
							$move2Spam[$row['scannername']][] = array('folder'=>$row1['folder'],'xuid'=>$row1['xuid'],'spam_folder'=>$foldername);
							$record2Trash['Messages'][] = $row1['messagesid'];	
						}
					}
				}
			}
		} else {
			$record2Trash[$module][] = $record;
			$scannername = getScannerNameFromAction($focus->column_fields['mailscanner_action']);
			
			$result2 = $adb->pquery("SELECT foldername
				FROM {$table_prefix}_mailscanner_folders
				INNER JOIN {$table_prefix}_mailscanner_actions ON {$table_prefix}_mailscanner_actions.scannerid = {$table_prefix}_mailscanner_folders.scannerid
				WHERE {$table_prefix}_mailscanner_actions.actionid = ? AND spam = 1",array($focus->column_fields['mailscanner_action']));
			if ($result2 && $adb->num_rows($result2) > 0) {
				$foldername = $adb->query_result($result2,0,'foldername');
			}
			
			// spam and trash all messages associated to tickets
			//crmv@171021
			$result1 = $adb->pquery("SELECT messagesid, folder, xuid
				FROM {$table_prefix}_messages
				INNER JOIN {$table_prefix}_messagesrel ON {$table_prefix}_messages.messagehash = {$table_prefix}_messagesrel.messagehash
				WHERE deleted = 0 AND mtype = ? AND {$table_prefix}_messagesrel.crmid = ?",array('Link',$record));
			//crmv@171021e
			if ($result1 && $adb->num_rows($result1) > 0) {
				while($row1=$adb->fetchByAssoc($result1)) {
					$move2Spam[$scannername][] = array('folder'=>$row1['folder'],'xuid'=>$row1['xuid'],'spam_folder'=>$foldername);
					$record2Trash['Messages'][] = $row1['messagesid'];	
				}
			}
		}

		if (!empty($move2Spam)) {
			foreach($move2Spam as $scannername => $info) {
				foreach($info as $i) {
					$this->add2Queue($scannername, $i['xuid'], $i['folder'], $i['spam_folder']);
				}
			}
		}
		if (!empty($record2Trash)) {
			foreach($record2Trash as $mod => $ids) {
				$tmpFocus = CRMEntity::getInstance($mod);
				foreach($ids as $id) {
					$tmpFocus->trash($module, $id);
				}
			}
		}
	}
}
?>