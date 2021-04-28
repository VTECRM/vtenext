<?php
$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.emails.save_unknown_contacts', true);

require_once('include/utils/CronUtils.php');
$CU = CronUtils::getInstance();
$cj = CronJob::getByName('MessagesSendOutOfOfficeReplies');
if (empty($cj)) {
	$cj = new CronJob();
	$cj->name = 'MessagesSendOutOfOfficeReplies';
	$cj->active = 1;
	$cj->singleRun = false;
	$cj->fileName = 'cron/modules/Messages/SendOutOfOfficeReplies.service.php';
	$cj->timeout = 300;
	$cj->repeat = 60;
	$cj->maxAttempts = 0;
	$CU->insertCronJob($cj);
}

SDK::setLanguageEntries('Messages', 'LBL_OUT_OF_OFFICE', array('it_it'=>'Fuori ufficio','en_us'=>'Out of office'));