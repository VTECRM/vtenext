<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887

require_once('include/BaseClasses.php');

class SettingsUtils extends SDKExtendableUniqueClass {
	
	/**
	 * This function returns the blocks for the settings page
	 * @return array of setting blocks
	 */
	public static function getBlocks() {
		global $adb, $table_prefix;
		
		$blocksQ = "SELECT * FROM {$table_prefix}_settings_blocks ORDER BY sequence";
		$blocksR = $adb->query($blocksQ);
		$blocks = array();
		
		if ($blocksR && $adb->num_rows($blocksR)) {
			while ($row = $adb->fetchByAssoc($blocksR, -1, false)) {
				$blockid = $row['blockid'];
				
				$image = explode('.', $row['image']);
				$row['image_type'] = $image[1] ? 'image' : 'icon';
				
				$blocks[$blockid] = $row;
			}
		}
		
		return $blocks;
	}
	
	/**
	 * This function returns the fields for the settings page
	 * @return array of setting fields
	 */
	public static function getFields() {
		global $adb, $table_prefix;
		
		$fieldsQ = "SELECT * FROM {$table_prefix}_settings_field WHERE blockid <> ? AND active = 0 ORDER BY blockid, sequence";
		$fieldsR = $adb->pquery($fieldsQ, array(self::getBlockId('LBL_MODULE_MANAGER')));
		$fields = array();
		
		if ($fieldsR && $adb->num_rows($fieldsR)) {
			while ($row = $adb->fetchByAssoc($fieldsR, -1, false)) {
				$blockid = $row['blockid'];
				$iconpath = $row['iconpath'];
				$description = $row['description'];
				$linkto = $row['linkto'];
				$action = getPropertiesFromURL($linkto, 'action');
				$module = getPropertiesFromURL($linkto, 'module');
				$name = $row['name'];
				$formodule = getPropertiesFromURL($linkto, 'formodule');
				
				$fields[$blockid][] = array('icon' => $iconpath, 'description' => $description, 'link' => $linkto, 'name' => $name, 'action' => $action, 'module' => $module, 'formodule' => $formodule);
			}
		}
		
		// add blanks for 4-column layout
		foreach ($fields as $blockid => &$field) {
			if (count($field) > 0 && count($field) < 4) {
				for ($i = count($field); $i < 4; $i++) {
					$field[$i] = array();
				}
			}
		}
		
		return $fields;
	}

	/**
	 * This function is used to get the blockid of the settings block for a given label.
	 * @param $label - settings label
	 * @return string type value
	 */
	public static function getBlockId($label) {
		global $adb, $table_prefix;
		
		$blockid = 0;
		
		$blockQ = "SELECT blockid FROM {$table_prefix}_settings_blocks WHERE label = ?";
		$blockR = $adb->pquery($blockQ, array($label));
		
		if ($blockR && $adb->num_rows($blockR)) {
			$blockid = intval($adb->query_result($blockR, 0, 'blockid'));
		}
		
		return $blockid;
	}

	/**
	 * This function is used to check if the logged in user is admin and if the module 
	 * is an entity module and the module has a Settings.php file within it
	 * @param $module - settings module
	 * @return string yes or no
	 */
	public static function isModulePermitted($module) {
		if (file_exists("modules/$module/Settings.php") && isPermitted('Settings', 'index', '') == 'yes') {
			return 'yes';
		}
		return 'no';
	}

	// crmv@181170
	public static function resetMenuState() {
		if ($_REQUEST['reset_session_menu']) {
			VteSession::remove('settings_last_menu');
		}
		if ($_REQUEST['reset_session_menu_tab']) {
			VteSession::remove('settings_last_menu');
			VteSession::set('settings_last_menu', 'LBL_USERS');
		}
	}
	// crmv@181170e
	
}