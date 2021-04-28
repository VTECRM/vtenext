<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require_once 'include/utils/utils.php';
require_once 'include/utils/CommonUtils.php';

require_once 'include/events/SqlResultIterator.inc';
//crmv@207901
require_once 'modules/com_workflow/VTSimpleTemplate.inc';
require_once 'modules/com_workflow/VTEntityCache.inc';
require_once 'modules/com_workflow/VTWorkflowUtils.php';
require_once 'modules/com_workflow/VTWorkflowManager.inc';
require_once 'modules/com_workflow/VTTaskManager.inc';
require_once 'modules/com_workflow/VTWorkflowTemplateManager.inc';
require_once 'modules/com_workflow/VTTaskQueue.inc';
//crmv@207901e

global $adb;

function vtRunTaskJob($adb){
    $util = new VTWorkflowUtils();
    $adminUser = $util->adminUser();
    $tq = new VTTaskQueue($adb);
    $readyTasks = $tq->getReadyTasks();
    $tm = new VTTaskManager($adb);
    foreach($readyTasks as $pair){
        list($taskId, $entityId) = $pair;
        $task = $tm->retrieveTask($taskId);
        $entity = new VTWorkflowEntity($adminUser, $entityId);
        $task->doTask($entity);
    }
}

vtRunTaskJob($adb);