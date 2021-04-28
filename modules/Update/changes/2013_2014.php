<?php
global $adb, $table_prefix;

// crmv@198950
$adb->pquery("UPDATE {$table_prefix}_eventhandlers SET event_name = ? WHERE event_name = ? AND handler_class = ?", array('vtiger.entity.aftersave.notifications', 'vtiger.entity.aftersave', 'ModNotificationsHandler'));