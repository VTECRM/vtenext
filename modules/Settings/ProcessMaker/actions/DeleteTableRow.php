<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@115268 */

require_once(dirname(__FILE__).'/Base.php');

class PMActionDeleteTableRow extends PMActionBase {
	
	function edit(&$smarty,$id,$elementid,$retrieve,$action_type,$action_id='') {
		global $adb;
		$PMUtils = ProcessMakerUtils::getInstance();
		if ($action_id != '') {
			$vte_metadata = Zend_Json::decode($retrieve['vte_metadata']);
			if (!empty($vte_metadata[$elementid])) {
				$metadata_action = $vte_metadata[$elementid]['actions'][$action_id];
				$smarty->assign('METADATA', $metadata_action);
			}
		}
	}
	
	function execute($engine,$actionid) {
		global $adb, $table_prefix;
		$action = $engine->vte_metadata['actions'][$actionid];
		//crmv@182891
		if (substr_count($action['cycle_field'],':') === 1)
			list($metaid, $fieldname) = explode(':',$action['cycle_field']);
		else
			list($metaid, $fieldid, $relatedmodule, $fieldname) = explode(':', $action['cycle_field']);
		//crmv@182891e
		if (stripos($fieldname,'ml') !== false) {
			// ModLight
			$module = 'ModLight'.str_replace('ml','',$fieldname);
			$focus = CRMEntity::getInstance($module);
			$focus->trash($module,$this->cycleRow['id']);
		} else {
			// Dynaform
			require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
			$processDynaFormObj = ProcessDynaForm::getInstance();
			$values = $processDynaFormObj->getValues($engine->running_process, $metaid);
			if (!empty($values[$fieldname])) {
				unset($values[$fieldname][$this->cycleIndex]);
				$adb->pquery("update {$table_prefix}_process_dynaform set form = ? where running_process = ? and metaid = ?", array(Zend_Json::encode($values),$engine->running_process,$metaid));
			}
		}
	}
}