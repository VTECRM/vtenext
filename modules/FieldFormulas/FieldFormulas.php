<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
class FieldFormulas {
 	
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
 	function vtlib_handler($moduleName, $eventType) {
 					
		global $adb,$table_prefix;
		
 		if($eventType == 'module.postinstall') {		
			$fieldid = $adb->getUniqueID($table_prefix.'_settings_field');
			$blockid = getSettingsBlockId('LBL_MODULE_MANAGER');
			
			$seq_res = $adb->query("SELECT max(sequence) AS max_seq FROM ".$table_prefix."_settings_field");
			$seq = 1;
			if ($adb->num_rows($seq_res) > 0) {
				$cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
				if ($cur_seq != null)	$seq = $cur_seq + 1;
			}
			
			$adb->pquery('INSERT INTO '.$table_prefix.'_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence) 
				VALUES (?,?,?,?,?,?,?)', array($fieldid, $blockid, 'LBL_FIELDFORMULAS', 'modules/FieldFormulas/resources/FieldFormulas.png', 'LBL_FIELDFORMULAS_DESCRIPTION', 'index.php?module=FieldFormulas&action=index&parenttab=Settings', $seq));
			
			$tabid = getTabid('FieldFormulas');
			if(isset($tabid) && $tabid!='') {
				$adb->pquery('DELETE FROM '.$table_prefix.'_profile2tab WHERE tabid = ?', array($tabid));
			}
			
			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($moduleName));
			
		} else if($eventType == 'module.disabled') {
			$em = new VTEventsManager($adb);
			$em->setHandlerInActive('VTFieldFormulasEventHandler');
			
		} else if($eventType == 'module.enabled') {
			$em = new VTEventsManager($adb);
			$em->setHandlerActive('VTFieldFormulasEventHandler');

		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
		// TODO Handle actions after this module is updated.
		}
 	}
}
?>