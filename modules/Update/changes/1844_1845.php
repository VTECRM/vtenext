<?php

// crmv@172616

// add workflow config
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();

$logCfg = $VP->get('performance.log_config');
if ($logCfg && is_array($logCfg) && !isset($logCfg['workflow'])) {
	$logCfg['workflow'] = array(
		'label' => 'Workflow',
		'file' => 'logs/Workflow/Workflow.log',
		'rotate_maxsize' => 5,
		'table' => '_log_workflow',
		'level' => 4,
		'enabled' => 0,
	);
	$VP->set('performance.log_config', $logCfg);
}
