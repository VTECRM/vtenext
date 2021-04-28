<?php

// crmv@178158 - add setlocale to config

$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} elseif (!is_writable('config.inc.php')) {
	Update::info("config.inc.php is not writable, please modify it manually.");
} else {
	$modified = false;
	
	if (strpos($configInc, 'setlocale') === false) {
		// replace only first
		$pos = strpos($configInc, '//crmv@replace FCKEDITOR'); 
		if ($pos !== false) {
			$configInc = substr_replace($configInc, "
// crmv@178158 - be sure numbers and dates are always in C format
setlocale(LC_NUMERIC, 'C');
setlocale(LC_TIME, 'C');
// crmv@178158e

//crmv@replace FCKEDITOR", $pos, strlen('//crmv@replace FCKEDITOR'));
			$modified = true;
		}
	}
	
	// crmv@180714
	if (strpos($configInc, '$CHAT_DISPLAY') !== false) {
		$configInc = preg_replace('/\$CHAT_DISPLAY.*?\n/', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$CALENDAR_DISPLAY') !== false) {
		$configInc = preg_replace('/\$CALENDAR_DISPLAY.*?\n/', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$includeDirectory =') !== false) {
		$configInc = preg_replace('#// full path to include directory.*?;#s', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$create_default_user =') !== false) {
		$configInc = preg_replace('#// create user with default.*?;#s', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$default_user_is_admin =') !== false) {
		$configInc = preg_replace('#// default_user_is_admin default.*?;#s', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$disable_persistent_connections =') !== false) {
		$configInc = preg_replace('#// if your MySQL/PHP configuration does.*?;#s', '', $configInc);
 		$modified = true;
	}
	
	if (strpos($configInc, '$disable_stats_tracking =') !== false) {
		$configInc = preg_replace('#//Disable Stat Tracking.*?;#s', '', $configInc);
 		$modified = true;
	}
	
	// crmv@180714e
	
	if ($modified) {
		// clean double empty lines
		$configInc = preg_replace('/\n(\s*\n){2,}/', "\n\n", $configInc);
		// backup it (only if it doesn't exist)
		$newConfigInc = 'config.inc.1816.php';
		if (!file_exists($newConfigInc)) {
			file_put_contents($newConfigInc, $configInc);
		}
		// write file
		file_put_contents('config.inc.php', $configInc);
	}
}
