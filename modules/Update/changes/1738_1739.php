<?php
// clean/rotate general logs
$logs = glob('logs/webservices/*.log');
if ($logs && is_array($logs)) {
	foreach ($logs as $log) {
		LogUtils::rotateLog($log);
	}
}
$logs = glob('logs/ProcessEngine/*.log');
if ($logs && is_array($logs)) {
	foreach ($logs as $log) {
		LogUtils::rotateLog($log);
	}
}