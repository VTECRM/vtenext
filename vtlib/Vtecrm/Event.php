<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');
include_once('modules/Users/Users.php');
@include_once('include/events/include.inc');

/**
 * Provides API to work with CRM Eventing
 * @package vtlib
 */
class Vtecrm_Event
{
    /** Filename where class is defined */
    public $filename;
    /** Condition for the event */
    public $condition;
    /** Event name like: vte.entity.aftersave, vte.entity.beforesave */
    public $eventname;
    /** Event handler class to use */
    public $classname;

    /** Internal caching */
    static $is_supported = '';

    /**
     * Handle event registration for module
     * @param Vtecrm_Module Instance of the module to use
     * @param String Name of the Event like vte.entity.aftersave, vte.entity.beforesave
     * @param String Name of the Handler class (should extend VTEventHandler)
     * @param String File path which has Handler class definition
     * @param String Condition for the event to trigger (default blank)
     */
    static function register($moduleInstance, $eventname, $classname, $filename, $condition = '')
    {
        // Security check on fileaccess, don't die if it fails
        if (Vtecrm_Utils::checkFileAccess($filename, false)) {
            global $adb;
            $eventsManager = new VTEventsManager($adb);
            $eventsManager->registerHandler($eventname, $filename, $classname, $condition);
            $eventsManager->setModuleForHandler($moduleInstance->name, $classname);

            self::log("Registering Event $eventname with [$filename] $classname ... DONE");
        }
    }

    /**
     * Trigger event based on CRM Record
     * @param String Name of the Event to trigger
     * @param Integer CRM record id on which event needs to be triggered.
     */
    static function trigger($eventname, $crmid)
    {
        if (!self::hasSupport()) return;

        global $adb, $table_prefix;
        $checkres = $adb->pquery("SELECT setype, crmid, deleted FROM {$table_prefix}_crmentity WHERE crmid=?", array($crmid));
        if ($adb->num_rows($checkres)) {
            $result = $adb->fetch_array($checkres, 0);
            if ($result['deleted'] == '0') {
                $module = $result['setype'];
                $moduleInstance = CRMEntity::getInstance($module);
                $moduleInstance->retrieve_entity_info($result['crmid'], $module);
                $moduleInstance->id = $result['crmid'];

                global $current_user;
                if (!$current_user) {
                    $current_user = CRMEntity::getInstance('Users');
                    $current_user->id = $moduleInstance->column_fields['assigned_user_id'];
                }

                // Trigger the event
                $em = new VTEventsManager($adb);
                $em->triggerEvent($eventname, VTEntityData::fromCRMEntity($moduleInstance));
            }
        }
    }

    /**
     * Get all the registered module events
     * @param Vtecrm_Module Instance of the module to use
     */
    static function getAll($moduleInstance)
    {
        global $adb, $table_prefix;
        $events = false;
        if (self::hasSupport()) {
            // Get all events related to module
            $records = $adb->pquery("SELECT * FROM {$table_prefix}_eventhandlers WHERE handler_class IN 
				(SELECT handler_class FROM {$table_prefix}_eventhandler_module WHERE module_name=?)", array($moduleInstance->name));
            if ($records) {
                while ($record = $adb->fetch_array($records)) {
                    $event = new Vtecrm_Event();
                    $event->eventname = $record['event_name'];
                    $event->classname = $record['handler_class'];
                    $event->filename = $record['handler_path'];
                    $event->condition = $record['condition'];
                    $events[] = $event;
                }
            }
        }
        return $events;
    }

    /**
     * Helper function to log messages
     * @param String Message to log
     * @param Boolean true appends linebreak, false to avoid it
     * @access private
     */
    static function log($message, $delim = true)
    {
        Vtecrm_Utils::Log($message, $delim);
    }

    /**
     * Check if CRM support Events
     */
    static function hasSupport()
    {
        global $table_prefix;
        if (self::$is_supported === '') {
            self::$is_supported = Vtecrm_Utils::checkTable($table_prefix . '_eventhandlers');
        }
        return self::$is_supported;
    }
}
