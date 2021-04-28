<?php
global $adb, $table_prefix;

$cols = $adb->getColumnNames($table_prefix.'_emails_send_queue');
if (!in_array('send_attempts', $cols)) {
	$adb->addColumnToTable($table_prefix.'_emails_send_queue', 'send_attempts', 'I(1) DEFAULT 0');
}

$VTEP = VTEProperties::getInstance();
$VTEP->setProperty('modules.messages.list_max_entries_first_page', 50);
$VTEP->setProperty('modules.messages.list_max_entries_per_page', 10);
$VTEP->setProperty('modules.messages.messages_by_schedule', 20);
$VTEP->setProperty('modules.messages.messages_by_schedule_inbox', 20);
$VTEP->setProperty('modules.messages.interval_schedulation', '15 days');
$VTEP->setProperty('modules.messages.max_message_cron_uid_attempts', 3);
$VTEP->setProperty('modules.messages.interval_storage', '');
$VTEP->setProperty('modules.messages.messages_cleaned_by_schedule', 500);
$VTEP->setProperty('modules.messages.preserve_search_results_date', '-1 day');
$VTEP->setProperty('modules.messages.fetchBodyInCron', 'yes');
$VTEP->setProperty('modules.messages.IMAPDebug', false);
$VTEP->setProperty('modules.messages.view_related_messages_recipients', false);
$VTEP->setProperty('modules.messages.view_related_messages_drafts', false);
$VTEP->setProperty('modules.messages.interval_inline_cache', '1 month');
$VTEP->setProperty('modules.messages.force_index_querygenerator', false);
$VTEP->setProperty('modules.emails.max_attachment_size', 25);
$VTEP->setProperty('modules.emails.max_message_size', 10240000);
$VTEP->setProperty('modules.emails.max_emails_send_queue_attempts', 5);
