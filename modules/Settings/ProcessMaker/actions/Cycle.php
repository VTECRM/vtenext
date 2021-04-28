<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@104180 crmv@115268 */

require_once('modules/com_workflow/VTEntityCache.inc');//crmv@207901
require_once('modules/com_workflow/VTWorkflowUtils.php');//crmv@207901
require_once('modules/com_workflow/VTSimpleTemplate.inc');//crmv@207901
require_once('include/Webservices/DescribeObject.php');
require_once(dirname(__FILE__).'/Base.php');

class PMActionCycle extends PMActionBase {

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
		
		$actionType = $this->pmutils->getActionTypes($subaction);
		
		$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
		if (!empty($vte_metadata[$elementid])) {
			$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
		}
		
		$subOptions = array('cycle_field' => $cfieldname);
		$subclass = $this->getSubclass($subaction, $subOptions);
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
			foreach($blocks as $block) {
				foreach($block['fields'] as $field) {
					if ($field['fieldname'] == $cfieldname) {
						$cfieldlabel .= $field['label'];
						break 2;
					}
				}
			}
			$cfieldlabel .= ' '.getTranslatedString('LBL_LIST_OF').' '.$processDynaFormObj->getLabel($id,$metaid);
		}
		$smarty->assign('CYCLE_FIELDLABEL', $cfieldlabel);
		//crmv@115268e
		$smarty->assign('METAID', $metaid);
		$smarty->assign('SHOW_ACTION_CONDITIONS', true);
		$smarty->assign('ACTION_CONDITIONS', $metadata_action['conditions']);
		
	}
	
	function execute($engine,$actionid) {

		$action = $engine->vte_metadata['actions'][$actionid];

		$subaction = $action['cycle_action'];
		$cfield = $action['cycle_field'];
		$conditions = $action['conditions'];
		//crmv@182891
		if (substr_count($cfield,':') === 1)
			list($metaid, $cfieldname) = explode(':', $cfield);
		else
			list($metaid, $fieldid, $relatedmodule, $cfieldname) = explode(':', $cfield);
		//crmv@182891e
		
		//crmv@106857 crmv@195745
		if ($cfieldname === 'prodblock') {
			$IU = InventoryUtils::getInstance();
			$parentMod = getSalesEntityType($engine->entity_id, true);
			if ($parentMod && isInventoryModule($parentMod)) {
				$rows = $IU->getProductBlockRows($parentMod, $engine->entity_id, true);
				// remove the "id" otherwise the cycle index gets screwed up :(
				foreach ($rows as &$row) unset($row['id']);
			}
		// crmv@195745e
		} elseif (stripos($cfieldname,'ml') === 0) {
			require_once('include/utils/ModLightUtils.php');
			$modLightUtils = ModLightUtils::getInstance();
			$relmetaid = $metaid;
			$record = $engine->getCrmid($relmetaid, $engine->running_process, $fieldid); //crmv@182891
			$columns = $modLightUtils->getColumns('', $cfieldname);
			$rows = $modLightUtils->getValues('', $record, $cfieldname, $columns);
		} else {
			$PDyna = ProcessDynaForm::getInstance();
			$values = $PDyna->getValues($engine->running_process, $metaid);
			$rows = $values[$cfieldname];
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
				$subclass->execute($engine, $actionid);
			}
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
    	$id = intval($row['productid'] ?: 0);
    	if ($id > 0) {
	        $id = $row['productid'];
	        $entityType = $row['extra']['entity_type'];
	
	        $prodModule = CRMEntity::getInstance($entityType);
	        $prodModule->retrieve_entity_info_no_html($id, $entityType);
	
	        return array_merge($row, $prodModule->column_fields);
    	}
    	return $row;
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