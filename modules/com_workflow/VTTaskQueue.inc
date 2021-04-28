<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

/**
 * Time based Queue of tasks ready for execution.
 *
 */
class VTTaskQueue{

    public function __construct($adb){
        $this->adb = $adb;
    }

    /**
     * Get a list of taskId/entityId pairs ready for execution.
     *
     * The method fetches task id/entity id where the when timestamp
     * is less than the current time when the method was called.
     *
     * @return array list of pairs of the form array(taskId, entityId)
     */
    public function getReadyTasks(){
        global $table_prefix;
        $adb = $this->adb;
        $time = time();
        $result = $adb->pquery("select task_id, entity_id from com_{$table_prefix}_workflowtask_queue where do_after<?", array($time));
        $it =  new SqlResultIterator($adb, $result);
        $arr = [];
        foreach($it as $row){
            $arr[] = [$row->task_id, $row->entity_id];
        }
        $adb->pquery("delete from com_{$table_prefix}_workflowtask_queue where do_after<?", array($time));
        return $arr;
    }


    /**
     * Queue a task for execution.
     *
     * @param $taskId The id of the task to queue
     * @param $entityId The id of the crm entity the task is assiciated with.
     * @param $when The time after which the task should be executed. This is
     *        an optional value with a default value of 0.
     */
    public function queueTask($taskId, $entityId, $when=0){
        $adb = $this->adb;
        global $table_prefix;
        $result = $adb->pquery("select * from com_{$table_prefix}_workflowtask_queue 
				where task_id=? and entity_id=?", array($taskId, $entityId));
        if($adb->num_rows($result)==1){
            return false;
        }else{
            $adb->pquery("insert into com_{$table_prefix}_workflowtask_queue (task_id, entity_id, do_after) 
						  	values (?,?,?)", array($taskId, $entityId, $when));
            return true;
        }
    }
}