<?php

// crmv@181231
require_once('include/utils/VTEProperties.php');
$VP = VTEProperties::getInstance();

$sess = $VP->get('session.handler');
if (!$sess) {
	$VP->set('session.handler', '');
	$VP->set('session.handler.params', array());
}
