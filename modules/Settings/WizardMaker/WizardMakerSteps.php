<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@96233 */


class WizardMakerSteps {

	protected $wu = null;
	
	public function __construct($wu) {
		$this->wu = $wu;
		$this->wg = WizardGenerator::getInstance();
	}

	/**
	 * Extract the array of variables from the request, to be stored for the step
	 */
	public function extractStepVars(&$request) {
		$vars = array();
		if (is_array($request)) {
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 7) == 'wmaker_') {
					$vars[$k] = $v;
				}
			}
		}
		return $vars;
	}
	
	/**
	 * Save in the session the variables from $_REQUEST starting with "wmaker_"
	 */
	public function saveStepVars($step, &$request) {
		VteSession::setArray(array('wmaker', 'step_vars', $step), $this->extractStepVars($request));
	}
	
	/**
	 * Validate the variables for the specified step. Return a string of error if something is wrong.
	 */
	public function validateStepVars($step) {
		$func = "validateStep$step";
		if (method_exists($this, $func)) {
			$vars = $this->getStepVars($step);
			return $this->$func($vars);
		}
		return null;
	}
	
	protected function validateStep1($vars) {
		$wname = $vars['wmaker_name'];
		
		// check emptyness
		if (empty($wname)) return getTranslatedString('LBL_WIZARDLABEL', 'Settings').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		
		return null;
	}
	
	protected function validateStep2($vars) {
		$wname = $vars['wmaker_module'];
		
		// check emptyness
		if (empty($wname)) return getTranslatedString('LBL_MODULE').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		
		return null;
	}
	
	/**
	 * Clear the variables for the specified step
	 */
	public function clearStepVars($step) {
		VteSession::removeArray(array('wmaker', 'step_vars', $step));
	}
	
	/**
	 * Clear all the variables for all the steps
	 */
	public function clearAllStepsVars() {
		VteSession::removeArray(array('wmaker', 'step_vars'));
	}
	
	/**
	 * Retrieve the variables for the specified step
	 */
	public function getStepVars($step) {
		$vars = array();
		
		if (VteSession::hasKeyArray(array('wmaker', 'step_vars', $step))) {
			$vars = VteSession::getArray(array('wmaker', 'step_vars', $step));
		}
		
		return $vars;
	}
	
	public function preprocessStepVars($mode, $step, $prevstep, &$request) {
		if ($prevstep == 3) {
			// convert the fields values in a practical array
			$fields = array();
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 11) == 'field_mand_') {
					list($xx, $yy, $fieldid) = explode('_', $k);
					$fields[$fieldid]['fieldid'] = $fieldid;
					$fields[$fieldid]['mandatory'] = true;
					$fields[$fieldid]['visible'] = true;
				} elseif (substr($k, 0, 6) == 'field_') {
					list($xx, $fieldid) = explode('_', $k);
					$fields[$fieldid]['fieldid'] = $fieldid;
					$fields[$fieldid]['visible'] = true;
				}
			}
			
			$request['wmaker_fields'] = array_values($fields);
			
		} elseif ($prevstep == 4) {
			$selected = $request['wmaker_relations'] ?: array();
			$request['wmaker_relations'] = $this->mergeRelationsSimple($selected);
		}
	}
	
	/**
	 * Add or modify variables
	 */
	public function processStepVars($mode, $step, $prevstep, &$stepVars) {
		
		//if (empty($prevstep) || $step <= $prevstep) return;
		
		if ($step == 3) {
			if ($mode == 'create') {
				// generate the array with translations
				$stepVars['wmaker_fields'] = $this->generateFields();
			} else {
				// other modes, merge the saved labels with the new ones
				$stepVars['wmaker_fields'] = $this->mergeFields($stepVars['wmaker_fields']);
			}
		
		} elseif ($step == 4) {
		
			if ($mode == 'create') {
				// generate the array with translations
				$stepVars['wmaker_relations'] = $this->generateRelations();
			} else {
				// other modes, merge the saved labels with the new ones
				$stepVars['wmaker_relations'] = $this->mergeRelations($stepVars['wmaker_relations']);
			}
		
		}
		
	}
	
	/**
	 * Insert all the information in the session cache, for the edit mode
	 */
	public function saveAllVarsFromDb($dbdata) {
		VteSession::setArray(array('wmaker', 'step_vars'), array());
		
		$step1 = array();
		$step1['wmaker_name'] = $dbdata['name'];
		$step1['wmaker_description'] = $dbdata['description'];
		$step1['wmaker_parentmodule'] = $dbdata['parentmodules'][0];
		VteSession::setArray(array('wmaker', 'step_vars', 1), $step1);
		VteSession::setArray(array('wmaker', 'step_vars', 2, 'wmaker_module'), $dbdata['module']);

		$config = $dbdata['config'];
		$fields = $config['fields'];
		$relations = $config['relations'];
		
		if (!empty($fields)) {
			$flds = array();
			$gfields = $this->generateFields();
			foreach ($gfields as $grel) {
				foreach ($fields as $savedField) {
					if ($savedField['name'] == $grel['name']) {
						$grel['visible'] = true;
						if ($savedField['mandatory']) {
							$grel['mandatory'] = true;
						}
						$flds[] = $grel;
						break;
					}
				}
			}
			VteSession::setArray(array('wmaker', 'step_vars', 3, 'wmaker_fields'), $flds);
		}
		
		if (!empty($relations)) {
			$rels = array();
			$grels = $this->generateRelations();
			foreach ($grels as $grel) {
				if (in_array($grel['module'], $relations)) {
					$grel['selected'] = true;
					$rels[] = $grel;
				}
			}
			VteSession::setArray(array('wmaker', 'step_vars', 4, 'wmaker_relations'), $rels);
		}
		
	}
	
	/**
	 * Get all the variables ready to be inserted in the database
	 */
	public function getAllVarsForSave() {
		$out = array();
		$cfg = array();
		if (is_array(VteSession::getArray(array('wmaker', 'step_vars')))) {
		
			$module = VteSession::getArray(array('wmaker', 'step_vars', 2, 'wmaker_module'));
			
			$cfg['type'] = 'create';
			$cfg['module'] = $module;
			
			foreach (VteSession::getArray(array('wmaker', 'step_vars')) as $step=>$svars) {
				if ($step == 1) {
					$out['name'] = $svars['wmaker_name'];
					$out['description'] = $svars['wmaker_description'];
					if ($svars['wmaker_parentmodule']) {
						$mains = $this->wu->getAllMainModules($svars['wmaker_parentmodule']);
						foreach ($mains as $rmod) {
							if ($rmod['name'] == $module) {
								$cfg['father'] = array(
									'field' => $rmod['fieldname'],
									'mandatory' => true,
								);
								break;
							}
						}
						
					}
				} elseif ($step == 2) {
					$out['tabid'] = getTabid($svars['wmaker_module']);
				} elseif ($step == 3) {
					if (is_array($svars['wmaker_fields'])) {
						$fields = $this->mergeFields($svars['wmaker_fields']);
						foreach ($fields as $fld) {
							if ($fld['visible']) {
								$fieldCfg = array('name' => $fld['name']);
								if ($fld['mandatory']) $fieldCfg['mandatory'] = true;
								$cfg['fields'][] = $fieldCfg;
							}
						}
					}
				} elseif ($step == 4) {
					if (is_array($svars['wmaker_relations'])) {
						$relations = $this->mergeRelations($svars['wmaker_relations']);
						foreach ($relations as $fld) {
							if ($fld['selected']) {
								$cfg['relations'][] = $fld['module'];
							}
						}
					}
				}
			}
		}
		
		$out['config'] = $cfg;
		return $out;
	}
	
	public function generateFields() {
		$fields = array();
		
		$v1 = $this->getStepVars(1);
		$v2 = $this->getStepVars(2);
		$parentModule = $v1['wmaker_parentmodule'];
		$module = $v2['wmaker_module'];
		
		// check the parent field
		if ($parentModule) {
			$RM = RelationManager::getInstance();
			
			$rels = $RM->getRelations($module, ModuleRelation::$TYPE_NTO1, array($parentModule));
			if ($rels && $rels[0]) {
				$parentFieldid = $rels[0]->getFieldId();
			}
			
		}
		
		$wsfields = $this->wg->getAvailableFields($module);
		foreach ($wsfields as $wsfield) {
			// skip non editable fields
			if (!$wsfield['editable']) continue;
			$fieldid = $wsfield['fieldid'];
			$visible = ($wsfield['mandatory'] == true);
			$mand = ($wsfield['mandatory'] == true);
			$edit = ($wsfield['mandatory'] != true);
			$parentField = ($fieldid == $parentFieldid);
			
			// create the field array
			$field = array(
				'fieldid' => $fieldid,
				'name' => $wsfield['name'],
				'label' => $wsfield['label'],
				'visible' => $visible,
				'mandatory' => $mand,
				'editable' => $edit,
				'parent' => $parentField,
			);
			$fields[] = $field;
		}
		
		return $fields;
	}
	
	public function generateRelations() {
		$relations = array();
		
		$v2 = $this->getStepVars(2);
		$module = $v2['wmaker_module'];
		
		$rels = $this->wg->getAvailableRelations($module);
		
		if (is_array($rels)) {
			foreach ($rels as $rel)  {
				$mod = $rel->getSecondModule();
				$relations[] = array(
					'module' => $mod,
					'label' => getTranslatedString($mod, $mod), 
					'relationid' => $rel->relationid,
				);
			}
		}
		
		return $relations;
	}

	public function mergeFields($fields) {
		// merge the saved fields with the default ones
		
		$defaults = $this->generateFields();

		if (!is_array($fields)) return $defaults;
		
		$kfields = array();
		foreach ($fields as $f) {
			$kfields[$f['fieldid']] = $f;
		}

		foreach ($defaults as &$f) {
			$fieldid = $f['fieldid'];
			if (isset($kfields[$fieldid])) {
				$f = array_merge($f, $kfields[$fieldid]);
			}
		}
		
		return $defaults;
	}
	
	public function mergeRelations($relations) {
		// merge the saved relations with the default ones

		$defaults = $this->generateRelations();
		
		if (!is_array($relations)) return $defaults;
		
		$krels = array();
		foreach ($relations as $r) {
			$krels[$r['relationid']] = $r;
		}

		foreach ($defaults as &$r) {
			$relid = $r['relationid'];
			if (isset($krels[$relid])) {
				$r = array_merge($r, $krels[$relid]);
				$r['selected'] = true;
			}
		}
		
		return $defaults;
	}
	
	public function mergeRelationsSimple($relations) {
		// merge the saved relations with the default ones

		$defaults = $this->generateRelations();
		
		if (!is_array($relations)) return $defaults;

		$rels = array();
		foreach ($defaults as $r) {
			if (in_array($r['module'], $relations)) {
				$r['selected'] = true;
				$rels[] = $r;
			}
		}
		
		return $rels;
	}
	
}