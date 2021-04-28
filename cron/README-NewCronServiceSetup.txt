VTE CRON CONFIGURATION - Run automated operations without user intervention



Global setup
============

Make sure you have cron/RunCron.sh (or .bat for Windows) in your /etc/crontab system file.

Example:

*/1 *   * * *   root    /var/www/vte_root/cron/RunCron.sh >> /var/www/vte_root/logs/cron.log 2>&1




To create a new cron operation
==============================

1. Create a new php script inside the cron/ directory which does what you want.
Remember that when launched, this script will be executed in the VTE root folder,
so it's safe to use these includes:

require_once('config.inc.php');
require_once('include/utils/utils.php');
require_once('include/logging.php');


2. To register it, launch the following php code

require_once('include/utils/utils.php');
require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();

$cj = CronJob::getByName('YourCronName');
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = 'YourCronName';
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/YourCronFile.php';
	$cj->timeout = 300;             // 5min timeout
	$cj->repeat = 600;              // run every 10 min
	$CU->insertCronJob($cj);
}

adjusting the variables accordingly to the desired behaviour.

3. You can later modify it editing the table vte_cronjobs

NOTE: all the logs for the cron jobs are in logs/cron/




Migrating from old cron setup
=============================

1. Update VTE to a revision >= 851
2. Make sure in your /etc/crontab file there's only the RunCron.sh script active

