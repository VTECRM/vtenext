<?php

// crmv@195993 - add extension

$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
	
	// change value
	if (is_writable('config.inc.php')) {
		global $upload_badext;
		require('config.inc.php');
		
		if (!in_array('pht', $upload_badext)) {
			$upload_badext[] = 'pht';
			$configInc = preg_replace('/^\$upload_badext.*?;/sm', "\$upload_badext = array('".implode("','", $upload_badext)."');", $configInc);
		
			// backup it (only if it doesn't exist
			$newConfigInc = 'config.inc.1963.php';
			if (!file_exists($newConfigInc)) {
				file_put_contents($newConfigInc, $configInc);
			}
			
			// save the file
			file_put_contents('config.inc.php', $configInc);
		}
		
	} else {
		Update::info("Unable to update config.inc.php, please modify it manually.");
	}
}
