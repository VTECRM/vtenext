<?php

// crmv@176614
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();

$logCfg = $VP->get('performance.log_config');
if ($logCfg && is_array($logCfg)) {
	foreach ($logCfg as $logname => &$cfg) {
		unset($cfg['filepath']);
	}
	unset($cfg);
	$VP->set('performance.log_config', $logCfg);
}
