<?php 

// crmv@161554

global $site_URL;

// add variables in config.inc
$configInc = file_get_contents('config.inc.php');
if (empty($configInc)) {
	Update::info("Unable to get config.inc.php contents, please modify it manually.");
} else {
	if (strpos($configInc, '$gdpr_URL') === false) {
		// backup it (only if it doesn't exist)
		$newConfigInc = 'config.inc.1683.php';
		if (!file_exists($newConfigInc)) {
			file_put_contents($newConfigInc, $configInc);
		}
		// alter config inc
		$gdprUrl = $site_URL.'/gdpr';
		$configInc = str_replace('?>', "\n// gdpr root directory, without final \"/\"\n\$gdpr_URL = '{$gdprUrl}';\n\n?>", $configInc);
		if (is_writable('config.inc.php')) {
			file_put_contents('config.inc.php', $configInc);
		} else {
			Update::info("Unable to update config.inc.php, please modify it manually.");
		}
	}
}

$PPU = PrivacyPolicyUtils::getInstance();
$PPU->install();

$GDPRWS = GDPRWS::getInstance();
$GDPRWS->install();
