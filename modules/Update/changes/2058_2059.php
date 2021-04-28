<?php 

global $adb, $table_prefix;

// crmv@203532 crmv@202475

// remove Settings.php and CallRelatedList.php from custom modules
$keepSetList = array('Calendar', 'ModComments', 'PBXManager');
$keepRelList = array('Campaigns', 'Emails', 'PBXManager');
$res = $adb->query("SELECT name FROM {$table_prefix}_tab WHERE isentitytype = 1");
while ($row = $adb->fetchByAssoc($res, -1, false)) {
	$mod = $row['name'];
	$dir = 'modules/'.$mod.'/';
	if (!in_array($mod, $keepSetList) && is_dir($dir) && is_file($dir.'Settings.php')) {
		$file = $dir.'Settings.php';
		if (is_readable($file) && is_writeable($file)) {
			$content = file_get_contents($file);
			if ($content !== false && strpos($content, 'mycrmv') === false) {
				@unlink($file);
			}
		}
	}
	if (!in_array($mod, $keepRelList) && is_dir($dir) && is_file($dir.'CallRelatedList.php')) {
		$file = $dir.'CallRelatedList.php';
		if (is_readable($file) && is_writeable($file)) {
			$content = file_get_contents($file);
			if ($content !== false && strpos($content, 'mycrmv') === false) {
				@unlink($file);
			}
		}
	}
}
