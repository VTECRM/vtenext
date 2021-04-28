<?php

// crmv@193294
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();

$keys = $VP->get('performance.global_cache_keys');
if (is_array($keys) && !in_array('tablecache', $keys)) {
	$keys[] = 'tablecache';
	$VP->set('performance.global_cache_keys', $keys);
}
