<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92034 */

include('config.inc.php');

global $current_user;

// I must be logged!
if (!$current_user || empty($current_user->id)) die();

// check if enabled
// you should also enable the LOG4PHP_DEBUG in the config.performance
if (!PerformancePrefs::getBoolean('JS_DEBUG', false)) die();

$log = LoggerManager::getLogger('JSLOGGER');
$log->setAdditivity(false);	// manually disable additivity, since the config file is ignored

$str = substr($_REQUEST['error'], 0, 300)."\n";
$str .= "in file ".substr($_REQUEST['source'], 0, 300);

$line = intval($_REQUEST['line']);
$col = intval($_REQUEST['column']);
if ($line >= 0 || $col >= 0) {
	$str .= " at line $line column $col\n";
} else {
	$str .= "\n";
}

$url = substr($_REQUEST['url'], 0, 300);
if ($url) {
	$str .= "The opened page was $url\n";
}

$uagent = substr($_REQUEST['useragent'], 0, 200);
if ($uagent) {
	$str .= "The User Agent was $uagent\n";
}

if ($current_user->user_name) {
	$str .= "The logged in user was: {$current_user->user_name}\n";
}

$trace = substr($_REQUEST['trace'], 0, 10000);
if (!empty($trace)) {
	$str .= "Trace: $trace\n";
}


$log->error($str);

// terminate, no answer
die();