<?php
global $adb, $table_prefix;

$result = $adb->pquery("select {$table_prefix}_cal_tracker.id
	from {$table_prefix}_cal_tracker
	inner join {$table_prefix}_messages on messagesid = {$table_prefix}_cal_tracker.record
	inner join {$table_prefix}_cal_tracker_log on {$table_prefix}_cal_tracker_log.id = {$table_prefix}_cal_tracker.id
	where {$table_prefix}_cal_tracker.id not in (select id from {$table_prefix}_cal_tracker_log where status = 'Stopped')
	and {$table_prefix}_cal_tracker_log.`date` <= ?", array(date('Y-m-d H:i:s',strtotime('-1 day'))));
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result,-1,false)) {
		$adb->pquery("delete from {$table_prefix}_cal_tracker where id = ?", array($row['id']));
		$adb->pquery("delete from {$table_prefix}_cal_tracker_log where id = ?", array($row['id']));
	}
}