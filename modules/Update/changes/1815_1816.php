<?php

global $adb, $table_prefix;

/* crmv@180132 */

$adb->pquery("UPDATE {$table_prefix}_tracking_unit SET presence = ? WHERE tracking_unit = ?", array(0, 'Incidents'));

// crmv@180638
$adb->pquery("UPDATE {$table_prefix}_links SET cond = ? WHERE linklabel = ?", array('checkFaxWidgetPermission:modules/Fax/widgets/CheckWidgetPermission.php', 'TITLE_COMPOSE_FAX'));
