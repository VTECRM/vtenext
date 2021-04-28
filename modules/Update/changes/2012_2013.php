<?php 

global $adb, $table_prefix;

$eventlist = array();

$result = $adb->query("SELECT DISTINCT(activitytype) FROM {$table_prefix}_activitytype");
if ($result && $adb->num_rows($result)) {
	while ($row = $adb->fetchByAssoc($result, -1, false)) {
		$eventlist[] = $row['activitytype'];
	}
}

$languages = vtlib_getToggleLanguageInfo();
$trans = array('ALERT_ARR' => array());

foreach ($eventlist as $i => $event) {
	$event = strtolower($event);
	$eventCode = preg_replace('/[\W_]+/', '_', $event);
	foreach ($languages as $lang => $langinfo) {
		$r = $adb->pquery("SELECT trans_label FROM sdk_language WHERE module = ? AND language = ? AND label = ?", array('Calendar', $lang, $event));
		if ($r && $adb->num_rows($r)) {
			$translabel = $adb->query_result($r, 0, 'trans_label');
			$trans['ALERT_ARR'][$lang][$eventCode] = $translabel;
		} else {
			$r = $adb->pquery("SELECT trans_label FROM sdk_language WHERE module = ? AND language = ? AND label = ?", array('APP_STRINGS', $lang, $event));
			if ($r && $adb->num_rows($r)) {
				$translabel = $adb->query_result($r, 0, 'trans_label');
				$trans['ALERT_ARR'][$lang][$eventCode] = $translabel;
			} else {
				$trans['ALERT_ARR'][$lang][$eventCode] = $eventlist[$i];
			}
		}
	}
}

foreach ($trans as $module => $modlang) {
	foreach ($modlang as $lang => $translist) {
		if (array_key_exists($lang, $languages)) {
			foreach ($translist as $label => $translabel) {
				SDK::setLanguageEntry($module, $lang, $label, $translabel);
			}
		}
	}
}
