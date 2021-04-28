<?php


/* crmv@207901 */

global $adb, $table_prefix;

$adb->query("UPDATE {$table_prefix}_wsapp_handlerdetails SET type='vtenextCRM', handlerclass='vtenextCRMHandler', handlerpath='modules/WSAPP/Handlers/vtenextCRMHandler.php' WHERE type='vtigerCRM'");
$adb->query("UPDATE {$table_prefix}_wsapp SET name='vtenextCRM' WHERE name='vtigerCRM'");

