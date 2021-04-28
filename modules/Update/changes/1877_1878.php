<?php

/* crmv@187404 */

global $adb, $table_prefix;

$GDPRWS = GDPRWS::getInstance();

$gdprFields = $GDPRWS->gdprFields;
$gdprFields[] = 'gdpr_deleted';
$gdprFields[] = 'gdpr_sentdate';

$gdprModules = GDPRWS::$supportedModules;

foreach ($gdprModules as $module) {
	$tabId = getTabid($module);
	
	foreach ($gdprFields as $field) {
		$field1 = $field;
		$field2 = $field . '_checkedtime';
		$field3 = $field . '_remote_addr';
		
		$params = array($field1, $field2, $field3, $tabId);
		
		$adb->pquery("
			UPDATE {$table_prefix}_field SET quickcreate = 1, quickcreatesequence = NULL
			WHERE fieldname IN (?, ?, ?) AND quickcreate = 2 AND quickcreatesequence > 0 AND tabid = ?
		", $params);
	}
}

/* crmv@187406 */

if (!function_exists('moveFieldAfter')) {
	function moveFieldAfter($module, $field, $afterField) {
		global $adb, $table_prefix;
		
		$tabid = getTabid($module);
		if (empty($tabid)) return;
		
		$res = $adb->pquery("SELECT fieldid, sequence FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array($tabid, $field));
		if ($res && $adb->num_rows($res) > 0) {
			$fieldid1 = intval($adb->query_result_no_html($res, 0, 'fieldid'));
			$sequence1 = intval($adb->query_result_no_html($res, 0, 'sequence'));
		}
		
		$res = $adb->pquery("SELECT fieldid, sequence FROM {$table_prefix}_field WHERE tabid = ? AND fieldname = ?", array($tabid, $afterField));
		if ($res && $adb->num_rows($res) > 0) {
			$fieldid2 = intval($adb->query_result_no_html($res, 0, 'fieldid'));
			$sequence2 = intval($adb->query_result_no_html($res, 0, 'sequence'));
		}
		
		if ($fieldid1 > 0 && $fieldid2 > 0) {
			// get the ids to update
			$updateIds = array();
			$res = $adb->pquery("SELECT fieldid FROM {$table_prefix}_field WHERE tabid = ? AND sequence > ?", array($tabid, $sequence2));
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->fetchByAssoc($res)) {
					$updateIds[] = intval($row['fieldid']);
				}
			}
			if (count($updateIds) > 0) {
				$adb->pquery("UPDATE {$table_prefix}_field set sequence = sequence + 1 WHERE fieldid IN (" . generateQuestionMarks($updateIds) . ")", $updateIds);
			}
			$adb->pquery("UPDATE {$table_prefix}_field set sequence = ? WHERE tabid = ? AND fieldid = ?", array($sequence2 + 1, $tabid, $fieldid1));
		}
	}
}

$fields = array(
	'language' => array(
		'module' => 'Users',
		'block' => 'LBL_USERLOGIN_ROLE',
		'name' => 'dark_mode',
		'label' => 'DarkMode',
		'table' => $table_prefix . '_users',
		'columntype' => 'I(1)',
		'typeofdata' => 'C~O',
		'uitype' => 56
	)
);

Update::create_fields($fields);

moveFieldAfter('Users', 'dark_mode', 'default_theme');

$trans = array(
	'Users' => array(
		'it_it' => array('DarkMode' => 'Tema Scuro'),
		'en_us' => array('DarkMode' => 'Dark Mode'),
	),
);

$languages = vtlib_getToggleLanguageInfo();
foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}
