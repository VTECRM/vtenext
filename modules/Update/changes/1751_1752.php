<?php
SDK::setUitype(86,'modules/SDK/src/86/86.php','modules/SDK/src/86/86.tpl','modules/SDK/src/86/86.js','whatsapp');

global $adb, $table_prefix;
$focus = CRMEntity::getInstance('Processes');
$focus->enableAll();
$adb->pquery("update {$table_prefix}_links set cond = ? where linklabel = ?", array('checkProcessesWidgetPermission:modules/Processes/widgets/CheckWidgetPermission.php','DetailViewProcessesAdvPerm'));