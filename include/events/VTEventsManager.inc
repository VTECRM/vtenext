<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

/**
 * Create and trigger events in vte
 */
class VTEventsManager{
    public function __construct($adb){
        $this->adb = $adb;
    }

    /**
     * Register an event handler
     *
     * @param $forEvent The name of the event to handle
     * @param $path The path to the php file containing the handler
     * @param $className The name of the VTEventHandler class.
     * @param $condition A condition which must evaluate true for the event to be accepted.
     */
    public function registerHandler($forEvent, $path, $className, $condition='', $depedentOn='[]'){
        global $table_prefix;
        $adb = $this->adb;
        $result = $adb->pquery("SELECT * FROM {$table_prefix}_eventhandlers WHERE event_name=? AND handler_path=? AND handler_class=?",
            array($forEvent, $path, $className));
        if($adb->num_rows($result) === 0){
            $handlerId = $adb->getUniqueId($table_prefix.'_eventhandlers');
            $columns = array_keys($adb->datadict->MetaColumns($table_prefix.'_eventhandlers'));
            $values_str = '?,?,?,?,?,1'; //crmv@33465
            $column_str = 'eventhandler_id, event_name, handler_path, handler_class, cond, is_active';
            $params = array($handlerId, $forEvent, $path, $className, $condition);
            if (in_array('DEPENDENT_ON', $columns)) {
                $column_str .= ', dependent_on';
                $values_str .= ',?';
                $params[] = $depedentOn;
            }
            $adb->pquery("insert into {$table_prefix}_eventhandlers ($column_str) values ({$values_str})",$params);
            $this->clearTriggerCache($forEvent);
        }
    }

    /**
     * Unregister a registered handler
     *
     * @param $className The name of teh VTEventHandler class to unregister
     */
    public function unregisterHandler($className){
        global $table_prefix;
        $adb = $this->adb;
        $adb->pquery("delete from {$table_prefix}_eventhandlers where handler_class=?",
            array($className));
        $adb->pquery("delete from {$table_prefix}_eventhandler_module where handler_class=?",
            array($className));

        $this->clearTriggerCache();
    }

    /**
     * Get an event triger instance
     *
     * @param $triggerName The name of the event.
     * @return VTEventTrigger trigger object for the event.
     */
    public function getTrigger($triggerName){
        $adb=$this->adb;
        return new VTEventTrigger($adb, $triggerName);
    }

    /**
     * Trigger an event
     *
     * @param $triggerName The name of the event.
     * @return The trigger object for the event.
     */
    public function triggerEvent($triggerName, $data){
        $this->getTrigger($triggerName)->trigger($data);
    }

    /**
     * Initialize Event Trigger Cache for the required event types.
     *
     * @param Object $for Optional String or Array of event_names for initializing.
     * @param Boolean $force Optional Force the initialization of cache?
     */
    public function initTriggerCache($for = false, $force = false) {
        VTEventTrigger::initCache($for, $force);
    }

    /**
     * Clear the Event Trigger Cache
     *
     * @param Object $forEvent
     */
    public function clearTriggerCache($forEvent = false) {
        VTEventTrigger::clearCache($forEvent);
    }

    /**
     * Set an event handler as inactive
     * @param The handler class to set as inactive
     *
     */
    public function setHandlerInActive($handlerClass){
        global $table_prefix;
        $adb = $this->adb;
        $adb->pquery("update {$table_prefix}_eventhandlers set is_active=0 where handler_class=?", array($handlerClass));

        $this->clearTriggerCache();
    }

    /**
     * Set an event handler as active
     *
     * @param The handler class to set as active
     */
    public function setHandlerActive($handlerClass){
        global $table_prefix;
        $adb = $this->adb;
        //crmv@fix boolean value
        $adb->pquery("update {$table_prefix}_eventhandlers set is_active=1 where handler_class=?", array($handlerClass));
        //crmv@fix boolean value end
        $this->clearTriggerCache();
    }

    /**
     * Set the module the handler belongs to
     *
     * @param moduleName - The name of the module
     * @param handlerClass - The name of the handler class
     */
    public function setModuleForHandler($moduleName, $handlerClass){
        global $table_prefix;
        $adb = $this->adb;
        $result = $adb->pquery("SELECT * FROM {$table_prefix}_eventhandler_module WHERE handler_class=?",
            array($handlerClass));
        if($adb->num_rows($result) === 0){
            $handlerModuleId = $adb->getUniqueId($table_prefix.'_eventhandler_module');
            $adb->pquery("insert into ".$table_prefix."_eventhandler_module
					(eventhandler_module_id, module_name, handler_class)
					values (?,?,?)", array($handlerModuleId, $moduleName, $handlerClass));
        }
    }

    /**
     * List handler classes for a module
     *
     * @param moduleName - The name of the module
     */
    public function listHandlersForModule($moduleName){
        global $table_prefix;
        $adb = $this->adb;
        $data = [];
        $result = $adb->pquery('SELECT handler_class FROM '.$table_prefix.'_eventhandler_module WHERE module_name=?', array($moduleName));
        $it = new SqlResultIterator($adb, $result);
        foreach($it as $row){
            $data[] = $row->handler_class;
        }
        return $data;
    }

    private function listEventHandlers($result){
        $adb = $this->adb;
        $it = new SQLResultIterator($adb, $result);
        $out = [];
        foreach($it as $row){
            $el = [];
            $el['eventName'] = $row->event_name;
            $el['handlerPath'] = $row->handler_path;
            $el['handlerClass'] = $row->handler_class;
            $el['condition'] = $row->cond;
            $el['isActive'] = $row->is_active;
            $out[] = $el;
        }
        return $out;
    }

    /**
     * List active events.
     *
     * @return array list of registered events.
     */
    public function listActiveEventHandlers(){
        global $table_prefix;
        $adb = $this->adb;
        //crmv@fix boolean value
        $result = $adb->pquery("select * from {$table_prefix}_eventhandlers where is_active=1", []);
        //crmv@fix boolean value end
        return $this->listEventHandlers($result);
    }

    public function listAllEventHandlers(){
        global $table_prefix;
        $adb = $this->adb;
        $result = $adb->pquery("select * from {$table_prefix}_eventhandlers", []);
        return $this->listEventHandlers($result);
    }
}
?>