<?php

/* crmv@186724 */

global $adb, $table_prefix;

// update config inc
// change default_theme
$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
	// backup it (only if it doesn't exist
	$newConfigInc = 'config.inc.1855.php';
	if (!file_exists($newConfigInc)) {
		file_put_contents($newConfigInc, $configInc);
	}
	// change value
	if (is_writable('config.inc.php')) {
		$configInc = str_replace('vtigerversion.php', 'vteversion.php', $configInc);
		file_put_contents('config.inc.php', $configInc);
	} else {
		Update::info("Unable to update config.inc.php, please modify it manually.");
	}
}

if (file_exists('hash_version.txt')) {
	$hash_version = file_get_contents('hash_version.txt');
	$hash_version_d = Users::de_cryption($hash_version);
	
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$processMakerUtils = ProcessMakerUtils::getInstance();
	$hash_version_d = preg_replace('/\$this->limit_processes = [0-9]+;/','$this->limit_processes = '.$processMakerUtils->limit_processes.';',$hash_version_d);
	$hash_version = Users::changepassword($hash_version_d);
	
	$adb->updateClob($table_prefix.'_version','hash_version','id=1',$hash_version);
	@unlink('hash_version.txt');
	$cache = Cache::getInstance('vteCacheHV');
	$cache->clear();
}


// fix uninstall scripts
$modfiles = glob('storage/custom_modules/modmaker_*.php');
foreach ($modfiles as $file) {
	if (is_writable($file)) {
		$content = file_get_contents($file);
		if ($content) {
			$content = str_replace("require('vtigerversion.php');", 
"// crmv@181168
if (is_file('vteversion.php')) {
	require('vteversion.php');
} else {
	require('vtigerversion.php');
}
// crmv@181168e", $content);
			file_put_contents($file, $content);
		}
	}
}

$delFiles = array(
	'modules/HelpDesk/CallTimeCardList.php',
	'modules/HelpDesk/TimeCardEdit.php',
	'modules/HelpDesk/TimeCardMvDown.php',
	'modules/HelpDesk/TimeCardMvUp.php',
	'modules/HelpDesk/TimeCardNew.php',
	'modules/HelpDesk/TimeCardUpdate.php',
	'modules/HelpDesk/SortTT.php',
	'modules/Timecards/CallTimeCardList.php',
	'vtigerversion.php', // crmv@181168
);

foreach ($delFiles as $file) {
	if (file_exists($file) && is_writable($file)) {
		@unlink($file);
	}
}

Update::info('The files vtigerservice.php and vtigerversion.php have been renamed to');
Update::info('vteservice.php and vteversion.php respectively.');
Update::info('If you have customizations using the old files, please review them.');
Update::info('');
