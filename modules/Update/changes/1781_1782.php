<?php 

// crmv@172994

global $adb, $table_prefix;

$tabId = getTabId('Campaigns');

$result = $adb->pquery("SELECT MIN(sequence) AS sequence FROM {$table_prefix}_relatedlists WHERE tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%' ORDER BY sequence", array($tabId));
if ($result && $adb->num_rows($result)) {
	$row = $adb->fetchByAssoc($result, -1, false);
	$minSequence = (int) $row['sequence'];
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array($minSequence, 'get_statistics_sent_messages', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_viewed_messages', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_tracked_link', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_unsubscriptions', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_suppression_list', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_bounced_messages', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_failed_messages', $tabId));
	$adb->pquery("UPDATE {$table_prefix}_relatedlists SET sequence = ? WHERE name = ? AND tabid = ? AND related_tabid = 0 AND name LIKE 'get_statistics_%'", array(++$minSequence, 'get_statistics_message_queue', $tabId));
}

SDK::setLanguageEntries('APP_STRINGS', 'Refresh', array('en_us' => 'Refresh'));