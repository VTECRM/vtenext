<?php

// crmv@196923

if (is_dir('install') && is_file('install/schema/DatabaseSchema.xml')) {
	// the schema was copied with the update files, move it to the proper location
	// find install dir
	$dirs = glob('[0-9]*install', GLOB_ONLYDIR);
	if (is_array($dirs) && count($dirs) > 0) {
		$idir = $dirs[0];
		if (!is_dir($idir.'/schema') && !is_file($idir.'/schema/DatabaseSchema.xml')) {
			$r = rename('install/schema', $idir.'/schema');
			// and remove the old folder
			if ($r) rmdir('install');
		}
	}
}


// crmv@171581

// add csrf secret token
$configInc = file_get_contents('config.inc.php');
if (!empty($configInc)) {
	if (strpos($configInc, '$csrf_secret') === false) {
		// alter config inc
		$VTECSRF = new VteCsrf();
		$csrfsecret = $VTECSRF->csrf_generate_secret();
		$configInc = preg_replace('/^\$application_unique_key =.*?;/m', "\\0\n\n\$csrf_secret = '$csrfsecret';\n", $configInc);
		file_put_contents('config.inc.php', $configInc);
	}
}


$VP = VTEProperties::getInstance();
$VP->setProperty('security.csrf.enabled', true);

