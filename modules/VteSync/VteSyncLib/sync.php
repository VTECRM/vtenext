#!/usr/bin/env php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
<?php
/* Generic syncronizer for VTE */

set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

require_once 'config.php';
require_once('autoloader.php');
//require_once 'VteSync.php';

//require_once '../../config.inc.php';
//chdir($root_directory);
//require_once 'include/utils/utils.php';

$fromCli = (php_sapi_name() == 'cli');

if ($sync['only_cli'] && !$fromCli) {
	die('This command can be executed only from CLI');
}

if (!$fromCli) {

	// fix raw encoding in utf8
	header('Content-Type: text/html; charset=utf-8');
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<meta charset="UTF-8">
		</head>
		<body><pre>
<?php
}

// --------------------------------------------
if ($sync['use_pidfile']) {
	$pidfile = dirname(__FILE__).'/vtesync.pid';
	if (is_readable($pidfile)) {
		$oldpid = trim(file_get_contents($pidfile));
		if (is_dir("/proc/$oldpid")) {
			// check if too old
			if (filemtime($pidfile) < time() - $sync['pid_timeout']) {
				echo "Old pid found but it's too old, ignore and reset!\n";
			} else {
				echo "Already running!\n";
				exit();
			}
		} else {
			echo "Old pid found, but no running process is active, reset!\n";
		}
	}
	file_put_contents($pidfile, getmypid());
}

$vs = new \VteSyncLib\Main($sync);
if ($vs->isReady()) $vs->synchronize();

if ($sync['use_pidfile']) {
	unlink($pidfile);
}
// --------------------------------------------

if (!$fromCli) {
	?>
		</pre></body>
	</html>
	<?php
}