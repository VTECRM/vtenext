<?php
/* crmv@183872 */
$moduleInstance = Vtecrm_Module::getInstance('Processes');
$fieldInstance = Vtecrm_Field::getInstance('assigned_user_id',$moduleInstance);
$adb->pquery("UPDATE {$table_prefix}_field SET typeofdata = ? WHERE fieldid = ?", array('V~M',$fieldInstance->id));
$fieldInstance = Vtecrm_Field::getInstance('related_to',$moduleInstance);
$adb->pquery("UPDATE {$table_prefix}_field SET typeofdata = ? WHERE fieldid = ?", array('V~O',$fieldInstance->id));

$focusUsers = CRMEntity::getInstance('Users');
$focusUsers->createCustomFiltersForAll();