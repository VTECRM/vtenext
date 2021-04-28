<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@106069 */
/* Clean old log files and other cache/temporary files in various locations */

require_once('include/utils/utils.php');
require_once('include/utils/CronUtils.php');


// clean/rotate cron logs
CronUtils::cleanLogs();

// clean/rotate general logs
$rotateOpts = array(
	'maxsize' => 5,		// rotate only when they reach 5 MB
);
$logs = glob('logs/*.log');
if ($logs && is_array($logs)) {
	foreach ($logs as $logfile) {
		LogUtils::rotateLog($logfile, $rotateOpts);
	}
}

// remove old charts files
purgeDir('cache/charts/', 90, '/^chart_/');

// remove old xls files
purgeDir('cache/images/', 30, '/^merge2/');

// remove temporary pdfmaker files
purgeDir('cache/pdfmaker/', 30, '/\.html$/');

// remove temporary vtlib files
purgeDir('cache/', 30, '/\.zip$/');

// remove old generated pdf
// disabled
//purgeDir('storage/', 90, '/\.pdf$/');

// clean storage/upload_email_* / *
// not implemented, disabled

// crmv@138170
// clean old report tables
require_once('modules/Reports/Reports.php');
$Reports = Reports::getInstance();
$Reports->cleanOldTables();
// crmv@138170e

//crmv@173186 clean/rotate all logs in vteprop performance.log_config
$logUtils = LogUtils::getInstance();
$logUtils->rotateAllLogConfig();
//crmv@173186e

// crmv@202301
require_once('modules/Settings/AuditTrail.php');
$AuditTrail = new AuditTrail();
$AuditTrail->cleanOldEntries();
// crmv@202301e

// ------------- FUNCTIONS ---------------

// remove files in $dir older than $daysOld days
function purgeDir($dir, $daysOld, $match = null) {
	$now = time();
	$tlimit = $now - $daysOld*3600*24;
	if (substr($dir, -1) != '/') $dir .= '/';
	if (is_dir($dir)) {
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry !== '.' && $entry !== '..') {
					if ($match == null || preg_match($match, $entry)) {
						$path = $dir.$entry;
						if (is_writable($path) && filemtime($path) < $tlimit) {
							// should be erased
							removeFile($path);
						}
					}
				}
			}
			closedir($handle);
		}
	}
}

function removeFile($filename) {
	$r = @unlink($filename);
	if ($r) {
		echo "Deleted file $filename\n";
	}
}