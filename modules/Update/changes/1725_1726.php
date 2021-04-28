<?php 

// crmv@164465

$result = $adb->pquery("select cronid from {$table_prefix}_cronjobs where cronname = ?", array('RebuildCache'));
if ($adb->num_rows($result) == 0) {
	require_once('include/utils/CronUtils.php');
	$CU = CronUtils::getInstance();
	
	$cj = new CronJob();
	$cj->name = 'RebuildCache';
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/modules/Cache/RebuildCache.service.php';
	$cj->timeout = 7200;	// 2h timeout
	$cj->repeat = 300;		// run every 5 min
	$CU->insertCronJob($cj);
}

create_tab_data_file();
