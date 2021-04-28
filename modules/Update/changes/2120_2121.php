<?php

/* crmv@207901 */

global $adb, $table_prefix;

$updateLanguages = $adb->query("UPDATE sdk_language SET module='com_workflow' WHERE module='com_vtiger_workflow'");
$updateCronjob = $adb->query("UPDATE {$table_prefix}_cronjobs SET filename='cron/modules/com_workflow/com_workflow.service.php' WHERE cronname='Workflow'");
$updateEvents = $adb->query("UPDATE {$table_prefix}_eventhandlers SET handler_path='modules/com_vtiger_workflow/VTEventHandler.inc' WHERE handler_class='VTWorkflowEventHandler'");
$updateEvents = $adb->query("UPDATE {$table_prefix}_settings_field SET linkto='index.php?module=com_workflow&action=workflowlist' WHERE name='LBL_WORKFLOW_LIST'");
