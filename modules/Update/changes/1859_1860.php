<?php

require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();
$gck = $VP->get('performance.global_cache_keys');
if (is_array($gck) && !in_array('tabdata', $gck)) {
	$gck[] = 'tabdata';
	$VP->set('performance.global_cache_keys', $gck);
}
