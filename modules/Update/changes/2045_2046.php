<?php

// crmv@197423

if (isModuleInstalled('VteSync')) {
	require_once('modules/VteSync/VteSync.php');
	$vsync = VteSync::getInstance();
	$vsync->vtlib_handler('VteSync', 'module.postupdate');
}
