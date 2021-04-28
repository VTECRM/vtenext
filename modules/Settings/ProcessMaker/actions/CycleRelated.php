<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104180 crmv@115268 */

//new file crmv@203075

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
require_once('modules/com_workflow/VTSimpleTemplate.inc');//crmv@207901
require_once('include/Webservices/DescribeObject.php');
require_once(dirname(__FILE__).'/Base.php');

class PMActionCycleRelated extends PMActionBase {

    public function __construct($options = array()) {
        parent::__construct($options);
        $this->pmutils = ProcessMakerUtils::getInstance();
    }

    function getSubclass($action_type, $actionOptions = array()) {
        $actionType = $this->pmutils->getActionTypes($action_type);
        require_once($actionType['php_file']);
        $action = new $actionType['class']($actionOptions);
        $action->isCycleAction = true;
        return $action;
    }

    //crmv@203075
    /**
     * It will add a new record to MetaRec
     * @param $id
     * @param $elementid
     * @param $action
     * @param $moduleToAdd
     * @throws Exception
     */
    public function addToMetaRec($id, $elementid, $action, $moduleToAdd)
    {
        global $adb, $table_prefix, $default_charset;	//crmv@132240
        $result = $adb->pquery("select id from {$table_prefix}_processmaker_metarec where processid = ? and elementid = ? and module = ?", array($id,$elementid,$moduleToAdd));

        if ($adb->num_rows($result) <= 0) {
            $metarecid = $adb->getUniqueID("{$table_prefix}_processmaker_metarec");
            $adb->pquery("insert into {$table_prefix}_processmaker_metarec values (?,?,?,?,?,?,?,?)", array($metarecid,$id,$elementid,'','Task',$moduleToAdd,$action,0));
        }
    }
    //crmv@203075e

    function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
        $opts = $this->getOptions();
        $subaction = $opts['cycle_action'];
        $cfield = $opts['cycle_field'];
        //crmv@182891
        if (substr_count($cfield,':') === 1)
            list($metaid, $cfieldname) = explode(':', $cfield);
        else
            list($metaid, $fieldid, $relatedmodule, $cfieldname) = explode(':', $cfield);
        //crmv@182891e
        $this->addToMetaRec($id, $elementid, $action_id, $cfieldname);

        $actionType = $this->pmutils->getActionTypes($subaction);

        $vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
        if (!empty($vte_metadata[$elementid])) {
            $metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
        }

        $subOptions = array('cycle_field' => $cfieldname);

        $subclass = $this->getSubclass($subaction, $subOptions);
        $subclass->cycleRelModule = $cfieldname;
        $subclass->edit($smarty,$id,$elementid,$retrieve,$action_type,$action_id);

        // override the template
        $smarty->assign('TEMPLATE', $actionType['tpl_file']);

        // add some cycle fields
        $smarty->assign('CYCLE_ACTION', $subaction);
        $smarty->assign('CYCLE_FIELD', $cfield);
        $smarty->assign('CYCLE_FIELDNAME', $cfieldname);
        //crmv@115268
        $cfieldlabel = ' '.getTranslatedString('LBL_ON_FIELD').' ';
        if (stripos($cfieldname,'ml') === 0) {
            global $adb, $table_prefix;
            $result = $adb->pquery("select * from {$table_prefix}_processmaker_metarec where id = ? and processid = ?", array($metaid,$id));
            if ($result && $adb->num_rows($result) > 0) {
                $row = $adb->fetchByAssoc($result);
                //crmv@182891
                if (!empty($relatedmodule)) {
                    $moduleInstance = Vtecrm_Module::getInstance($row['module']);
                    $result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldid = ?", array($moduleInstance->id,$fieldid));
                    if ($result && $adb->num_rows($result) > 0) {
                        $cfieldlabel .= getTranslatedString($adb->query_result($result,0,'fieldlabel'),$row['module']).' ('.getTranslatedString($relatedmodule,$relatedmodule).') : ';
                    }
                    $relModuleInstance = Vtecrm_Module::getInstance($relatedmodule);
                    $result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($relModuleInstance->id,$cfieldname));
                    if ($result && $adb->num_rows($result) > 0) {
                        $cfieldlabel .= $adb->query_result($result,0,'fieldlabel');
                    }
                } else {
                    $moduleInstance = Vtecrm_Module::getInstance($row['module']);
                    $result = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($moduleInstance->id,$cfieldname));
                    if ($result && $adb->num_rows($result) > 0) {
                        $cfieldlabel .= $adb->query_result($result,0,'fieldlabel');
                    }
                }
                //crmv@182891e
            }
            $cfieldlabel .= ' '.getTranslatedString('LBL_LIST_OF').' '.$this->pmutils->getRecordsInvolvedLabel($id,$metaid,$row);
            // crmv@195745
        } elseif ($cfieldname === 'prodblock') {
            // nothing yet
            // crmv@195745e
        } else {
            require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
            $processDynaFormObj = ProcessDynaForm::getInstance();
            $blocks = $processDynaFormObj->getStructure($id, false, $metaid);
            if(!empty($blocks))
            {
                foreach($blocks as $block) {
                    foreach($block['fields'] as $field) {
                        if ($field['fieldname'] == $cfieldname) {
                            $cfieldlabel .= $field['label'];
                            break 2;
                        }
                    }
                }
            }
            $cfieldlabel .= ' '.getTranslatedString('LBL_LIST_OF').' '.$processDynaFormObj->getLabel($id,$metaid);
        }
        //crmv@115268e
        $smarty->assign('METAID', $metaid);
        $smarty->assign('SHOW_ACTION_CONDITIONS', true);
        $smarty->assign('ACTION_CONDITIONS', $metadata_action['conditions']);
    }

    function execute($engine,$actionid) {
        global $adb;
        $action = $engine->vte_metadata['actions'][$actionid];
        $subaction = $action['cycle_action'];
        $cfield = $action['cycle_field'];
        $conditions = $action['conditions'];
        //crmv@182891
        if (substr_count($cfield,':') === 1)
            list($metaid, $cfieldname) = explode(':', $cfield);
        else
            list($metaid, $fieldid, $module, $cfieldname) = explode(':', $cfield);
        //crmv@182891e

        $rows = [];
        $rm = RelationManager::getInstance();
        $ids = $rm->getRelatedIds($module, $engine->entity_id, $cfieldname);
        $focus = CRMEntity::getInstance($cfieldname);
        foreach($ids as $k => $row)
        {
            $data = VTEntityData::fromEntityId($adb, $row);
            $rows[] = ['row' => $data->focus->column_fields];
            //$engine->trackRecord($data->focus->column_fields['record_id'], $this->getMetaIdRelatedModule($cfieldname, $action['id'], $action['elementid']), null, $action['elementid']);
        }

        //crmv@106857e

        if (is_array($rows) && count($rows) > 0) {

            $engine->log("Action Cycle","action $actionid - {$action['action_title']}, rows ".count($rows)."");
            $subOptions = array('cycle_field' => $cfieldname);

            $subclass = $this->getSubclass($subaction, $subOptions);

            foreach ($rows as $rowidx => $tablerow) {
                if (!empty($conditions)) {
                    if (isset($tablerow['row']) && is_array($tablerow['row'])) $tablerow = $tablerow['row']; // crmv@195745
                    $ok = $this->checkRowConditions($engine, $tablerow, $conditions);
                    if (!$ok) {
                        $engine->log("Action Cycle","Conditions evaluated to FALSE, action skipped for row index #{$rowidx}");
                        continue;
                    }
                }
                $subclass->cycleIndex = $rowidx;
                $subclass->cycleRow = $rows[$rowidx];
                $subclass->cycleRelModule = $cfieldname;
                $subclass->execute($engine, $actionid);

            }
        }

    }

    function getMetaIdRelatedModule($modulename, $processid, $elementid)
    {
        global $adb, $table_prefix;
        $result = $adb->pquery("select id from {$table_prefix}_processmaker_metarec where processid = ? and elementid = ? and module = ?", array($processid,$elementid,$modulename));
        if ($result && $adb->num_rows($result) > 0) {
            return($adb->query_result($result,0,'id'));
        }
    }

    //crmv@106857
    // se viene passato anche $cycleIndex allora al posto di $row va passata tutta l'entita'
    public function checkRowConditions($engine, $row, $conditions, $cycleIndex=null) {
        global $current_user;

        $row = $this->addFullProductDataToRow($row); //crmv@203278
        $entityCache = new PMCycleEntityCache($current_user, $row);
        $result = $this->pmutils->evaluateCondition($entityCache, 0, $conditions, $cycleIndex);

        return $result;
    }
    //crmv@106857e

    //crmv@203278
    function addFullProductDataToRow($row) {
        $id = $row['record_id'];
        $entityType = $row['record_module'];

        $prodModule = CRMEntity::getInstance($entityType);
        $prodModule->retrieve_entity_info_no_html($id, $entityType);

        return array_merge($row, $prodModule->column_fields);
    }
    //crmv@203278e
}

class PMCycleWorkflowEntity extends VTWorkflowEntity {

    function __construct($user, $data) {
        $this->user = $user;
        $this->moduleName = 'CycleEntity';
        $this->data = $data;
    }

    function save() {
        // do nothing!
    }
}

/* fake classes to hold the row data */
class PMCycleEntityCache extends VTEntityCache {

    function __construct($user, $data) {
        parent::__construct($user);
        $this->cache = new PMCycleWorkflowEntity($this->user, $data);
    }

    function forId($id){
        return $this->cache;
    }
}