<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@69398 crmv@105127 crmv@131239 */

require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

class ModuleMakerSteps {

	public $allowedSpecialChars = '';

	// crmv@92682
	// these names (case insensitive) can't be used as a module's name
	public $forbiddenNames = array(
		'__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 
		'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 
		'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 
		'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 
		'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 
		'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'
	);
	// crmv@92682
	
	protected $mmutils = null;
	
	public function __construct($mmutils) {
		$this->mmutils = $mmutils;
	}

	/**
	 * Extract the array of variables from the request, to be stored for the step
	 */
	public function extractStepVars(&$request) {
		$vars = array();
		if (is_array($request)) {
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 7) == 'mmaker_') {
					$vars[$k] = $v;
				}
			}
		}
		return $vars;
	}
	
	/**
	 * Save in the session the variables from $_REQUEST starting with "mmaker_"
	 */
	public function saveStepVars($step, &$request) {
		VteSession::setArray(array('mmaker', 'step_vars', $step), $this->extractStepVars($request));
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
	
		$mlabel = $vars['mmaker_modlabel'];
		$mslabel = $vars['mmaker_single_modlabel'];
		$modname = $vars['mmaker_modname'];
		$mainfield = $vars['mmaker_mainfield'];
		
		// check emptyness
		if (empty($mlabel)) return getTranslatedString('LBL_MODULELABEL', 'Settings').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		if (empty($mslabel)) return getTranslatedString('LBL_MODULESINGLELABEL', 'Settings').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		if (empty($modname)) return getTranslatedString('LBL_MODULENAME', 'Settings').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		if (empty($mainfield)) return getTranslatedString('LBL_RECORD_IDENTIFIER', 'Settings').' '.getTranslatedString('CANNOT_BE_EMPTY', 'APP_STRINGS');
		
		// check length
		if (strlen($mlabel) > 40) return getTranslatedString('LBL_MODULELABEL', 'Settings').' '.getTranslatedString('LBL_TOO_LONG', 'APP_STRINGS');
		if (strlen($mslabel) > 40) return getTranslatedString('LBL_MODULESINGLELABEL', 'Settings').' '.getTranslatedString('LBL_TOO_LONG', 'APP_STRINGS');
		if (strlen($modname) > 20) return getTranslatedString('LBL_MODULENAME', 'Settings').' '.getTranslatedString('LBL_TOO_LONG', 'APP_STRINGS');
		if (strlen($mainfield) > 40) return getTranslatedString('LBL_RECORD_IDENTIFIER', 'Settings').' '.getTranslatedString('LBL_TOO_LONG', 'APP_STRINGS');
		
		// check bad chars
		if (preg_match('/[^A-Za-z0-9_ '.$this->allowedSpecialChars.'-]/', $mlabel)) return str_replace('%s', getTranslatedString('LBL_MODULELABEL', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));
		if (preg_match('/[^A-Za-z0-9_ '.$this->allowedSpecialChars.'-]/', $mslabel)) return str_replace('%s', getTranslatedString('LBL_MODULESINGLELABEL', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));
		if (preg_match('/[^A-Za-z0-9]/', $modname)) return str_replace('%s', getTranslatedString('VTLIB_LBL_MODULE_NAME', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));
		if (preg_match('/[^A-Za-z0-9_ '.$this->allowedSpecialChars.'-]/', $mainfield)) return str_replace('%s', getTranslatedString('LBL_RECORD_IDENTIFIER', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));

		// crmv@92682
		// check for reserved words
		$smodname = strtolower($modname);
		if (in_array($smodname, $this->forbiddenNames)) return getTranslatedString('LBL_MODULENAME_NOT_ALLOWED', 'Settings');
		// crmv@92682e

		// check for module existance (weak, since can be added later, but check anyway)
		$tid = getTabid($modname);
		if ($tid && $tid > 0) return getTranslatedString('LBL_MODULE_EXISTING', 'APP_STRINGS');

		return null;
	}
	
	protected function validateStep2($vars) {
		$blocks = $vars['mmaker_blocks'];
		$panels = $vars['mmaker_panels'];
		
		if (empty($blocks) || !is_array($blocks)) return "No blocks found";
		if (empty($panels) || !is_array($panels)) return "No panels found";
		
		$countUityp4 = 0;
		$countRelMods = array();
		
		foreach ($blocks as $block) {
			if (!isset($block['panelno'])) return "Block {$block['label']} doesn't have a panel";
			if (is_array($block['fields'])) {
				foreach ($block['fields'] as $field) {
					if ($field['uitype'] == 4) {
						if (++$countUityp4 > 1) return "Too many auto numbering fields";
					} elseif ($field['uitype'] == 10) {
						$relmods = explode(',', $field['relatedmods']);
						foreach ($relmods as $rmod) {
							if (array_search($rmod, $countRelMods) !== false) {
								$err = getTranslatedString('LBL_MMAKER_ERR_SAMEMODULERELATED');
								$err = str_replace('%s', getTranslatedString($rmod, $rmod), $err);
								return $err;
							} else {
								$countRelMods[] = $rmod;
							}
						}
					}
				}
			}
		}
		
		return null;
	}
	
	protected function validateStep3($vars) {
		$cols = array();
		
		foreach ($vars['mmaker_filters'] as $filter) {
			if (!is_array($filter['columns']) || empty($filter['columns'])) {
				return getTranslatedString('LBL_NO_FIELDS_FOR_FILTER');
			}
			$uniq = array_unique($filter['columns']);
			if (count($uniq) != count($filter['columns'])) {
				return getTranslatedString('LBL_DUPLICATE_FIELDS_FOR_FILTER');
			}
				
		}
		
		return null;
	}
	
	protected function validateStep4($vars) {
		$relmods = array();
		
		$relN1 = $this->getRelations_N1();
		
		// crmv@113738
		foreach ($relN1 as $rel) {
			if (is_array($rel['modules'])) {
				foreach ($rel['modules'] as $m) {
					if (in_array($m, $relmods)) {
						return str_replace('%s', getTranslatedString($m, $m), getTranslatedString('LBL_MMAKER_ERR_SAMEMODULERELATED'));
					} else {
						$relmods[] = $m;
					}
				}
			}
		}
		// crmv@113738e
		
		if (is_array($vars['mmaker_relations'])) {
			foreach ($vars['mmaker_relations'] as $rel) {
				$m = $rel['module'];
				if (in_array($m, $relmods)) {
					return str_replace('%s', getTranslatedString($m, $m), getTranslatedString('LBL_MMAKER_ERR_SAMEMODULERELATED'));
				} else {
					$relmods[] = $m;
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Clear the variables for the specified step
	 */
	public function clearStepVars($step) {
		VteSession::removeArray(array('mmaker', 'step_vars', $step));
	}
	
	/**
	 * Clear all the variables for all the steps
	 */
	public function clearAllStepsVars() {
		VteSession::removeArray(array('mmaker', 'step_vars'));
	}
	
	/**
	 * Retrieve the variables for the specified step
	 */
	public function getStepVars($step) {
		$vars = array();
		
		if (VteSession::hasKeyArray(array('mmaker', 'step_vars', $step))) {
			$vars = VteSession::getArray(array('mmaker', 'step_vars', $step));
		}
		
		return $vars;
	}
	
	public function preprocessStepVars($mode, $step, $prevstep, &$request) {
		if (($mode == 'ajax' && $step == 2) || $prevstep == 2) {
			// convert the blocks/fields values in a practical array
			$panels = $blocks = array();
			$lastfieldid = 1;
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 6) == 'panel_') {
					list($xx, $panelid, $panelprop) = explode('_', $k, 3);
					if ($panelprop)	$panels[$panelid][$panelprop] = $v;
				} elseif (substr($k, 0, 6) == 'block_') {
					list($xx, $blockid, $blockprop) = explode('_', $k, 3);
					if ($blockprop)	$blocks[$blockid][$blockprop] = $v;
				} elseif (substr($k, 0, 6) == 'field_') {
					list($xx, $blockid, $fieldid, $fieldprop) = explode('_', $k, 4);
					if ($fieldprop)	$blocks[$blockid]['fields'][$fieldid][$fieldprop] = $v;
					if ($fieldprop == 'fieldname' && preg_match('/vcf_([0-9]+)/', $v, $matches)) {
						$fid = intval($matches[1]);
						if ($fid > $lastfieldid) $lastfieldid = $fid;
					}
					//crmv@118977
					if ($fieldprop == 'columns' && !empty($v)) {
						$columns = Zend_Json::decode($v);
						foreach($columns as $column) {
							preg_match('/vcf_([0-9]+)/', $column['fieldname'], $matches);
							$fid = intval($matches[1]);
							if ($fid > $lastfieldid) $lastfieldid = $fid;
						}
					}
					//crmv@118977e
				}
			}
			
			// now cleanup indices
			global $default_charset;	//crmv@98570
			$panels = array_values($panels);
			$blocks = array_values($blocks);
			foreach ($blocks as &$b) {
				if (is_array($b['fields'])) {
					$b['fields'] = array_values($b['fields']);
					//crmv@98570
					foreach($b['fields'] as &$field) {
						if (isset($field['code'])) $field['code'] = htmlentities($field['code'],ENT_COMPAT,$default_charset);
					}
					//crmv@98570e
				}
			}
			
			$request['mmaker_panels'] = $panels;
			$request['mmaker_blocks'] = $blocks;
			$request['mmaker_lastfieldid'] = $lastfieldid;
		
		} elseif ($prevstep == 3) {
			$filters = array();
			$cols = array();
			
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 7) == 'filter_') {
					// filter properties
					list($xx, $filterno, $filterprop) = explode('_', $k, 3);
					if ($filterprop) $filters[$filterno][$filterprop] = $v;
				} elseif (substr($k, 0, 10) == 'filtercol_') {
					// filter columns
					list($xx, $filterno, $filtercolidx) = explode('_', $k, 3);
					if (!empty($v)) {
						$filters[$filterno]['columns'][] = $v;
					}
				}
			}
			
			// clean up indices
			$filters = array_values($filters);
		
			$request['mmaker_filters'] = $filters;
		
		} elseif (($mode == 'ajax' && $step == 4) || $prevstep == 4) {
			$relations = array();
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 9) == 'relation_') {
					// filter properties
					list($xx, $relno, $relprop) = explode('_', $k, 3);
					if ($relprop && is_numeric($relno)) $relations[$relno][$relprop] = $v;
				}
			}
			
			// clean up indices
			$relations = array_values($relations);
			
			$request['mmaker_relations'] = $relations;
		
		} elseif ($step == 6 && $prevstep == 5) {
			$v5 = $this->getStepVars($prevstep);
			$request['mmaker_labels'] = $v5['mmaker_labels'];
		}
		
	}
	
	/**
	 * Add or modify variables
	 */
	public function processStepVars($mode, $step, $prevstep, &$stepVars) {
		global $current_language;
		
		if (empty($prevstep) || $step <= $prevstep) return;
		
		if ($mode == 'create' && $step == 2) {
			// inject new fields
			$prevStepVars = $this->getStepVars($step-1);
			$modname = $prevStepVars['mmaker_modname'];
			$modnameUp = strtoupper($modname);
			
			$addPanels = array(
				array(
					'panellabel' => 'LBL_TAB_MAIN',
					'label' => getTranslatedString('LBL_TAB_MAIN'),
				)
			);
			
			$addFieldsMain = array(
				array(
					'fieldname' => 'vcf_1',
					// editor properties
					'editable' => false,
					'deletable' => false,
					'mandatory' => 1,
					'label' => $prevStepVars['mmaker_mainfield'],
					// field properties
					'fieldlabel' => $prevStepVars['mmaker_mainfield'],
					'uitype' => 1,
					'readonly' => 1,
					'presence' => 2,
					'displaytype' => 1,
					'typeofdata' => 'V~M',
					'info_type' => 'BAS',
					'quickcreate' => 0,
					'masseditable' => 1,
				),
				array(
					'fieldname' => 'assigned_user_id',
					// editor properties
					'editable' => false,
					'deletable' => false,
					'mandatory' => 1,
					'label' => getTranslatedString('Assigned To'),
					// field properties
					'fieldlabel' => 'Assigned To',
					'uitype' => 53,
					'readonly' => 1,
					'presence' => 0,
					'displaytype' => 1,
					'typeofdata' => 'I~M',
					'info_type' => 'BAS',
					'masseditable' => 1,
				),
				array(
					'fieldname' => 'createdtime',
					// editor properties
					'editable' => false,
					'deletable' => false,
					'mandatory' => 0,
					'label' => getTranslatedString('Created Time'),
					// field properties
					'fieldlabel' => 'Created Time',
					'uitype' => 70,
					'readonly' => 1,
					'presence' => 0,
					'displaytype' => 2,
					'typeofdata' => 'T~O',
					'info_type' => 'BAS',
					'quickcreate' => 1,
					'masseditable' => 0,
				),
				array(
					'fieldname' => 'modifiedtime',
					// editor properties
					'editable' => false,
					'deletable' => false,
					'mandatory' => 0,
					'label' => getTranslatedString('Modified Time'),
					// field properties
					'fieldlabel' => 'Modified Time',
					'uitype' => 70,
					'readonly' => 1,
					'presence' => 0,
					'displaytype' => 2,
					'typeofdata' => 'T~O',
					'info_type' => 'BAS',
					'quickcreate' => 1,
					'masseditable' => 1,
				),
			);
			// add fields for products
			// fields for products are added in the script
			
			$addFieldsDesc = array(
				array(
					'fieldname' => 'description',
					// editor properties
					'editable' => false,
					'deletable' => false,
					'mandatory' => 0,
					'label' => getTranslatedString('Description'),
					// field properties
					'fieldlabel' => 'Description',
					'uitype' => 19,
					'readonly' => 1,
					'presence' => 2,
					'displaytype' => 1,
					'typeofdata' => 'V~O',
					'info_type' => 'BAS',
					'quickcreate' => 1,
					'masseditable' => 1,
				),
			);
			if ($current_language == 'en_us') {
				$infoLabel = $prevStepVars['mmaker_single_modlabel'].' '.getTranslatedString('LBL_INFORMATION');
			} else {
				$infoLabel = getTranslatedString('LBL_INFORMATION').' '.$prevStepVars['mmaker_single_modlabel'];
			}
			$addBlocks = array(
				array(
					'blocklabel' => "LBL_{$modnameUp}_INFORMATION",
					'editable' => true,
					'deletable' => false,
					'label' => $infoLabel,
					// these flags are mapped to db in this way: true=>1, false=>0
					'show_title' => false,
					'visible' => false,
					'create_view' => false,
					'edit_view' => false,
					'detail_view' => false,
					'panelno' => 0,
					// list of fields
					'fields' => $addFieldsMain
				),
				array(
					'blocklabel' => "LBL_CUSTOM_INFORMATION",
					'editable' => true,
					'deletable' => false,
					'label' => getTranslatedString('LBL_CUSTOM_INFORMATION'),
					// these flags are mapped to db in this way: true=>1, false=>0
					'show_title' => false,
					'visible' => false,
					'create_view' => false,
					'edit_view' => false,
					'detail_view' => false,
					'panelno' => 0,
					// list of fields
					'fields' => array()
				)
			);
			$addBlocks[] = array(
				'blocklabel' => "LBL_DESCRIPTION_INFORMATION",
				'editable' => true,
				'deletable' => false,
				'label' => getTranslatedString('LBL_DESCRIPTION_INFORMATION'),
				// these flags are mapped to db in this way: true=>1, false=>0
				'show_title' => false,
				'visible' => false,
				'create_view' => false,
				'edit_view' => false,
				'detail_view' => false,
				'panelno' => 0,
				// list of fields
				'fields' => $addFieldsDesc
			);
			
			// add basic fields
			$stepVars['mmaker_currentpanelno'] = 0;
			$stepVars['mmaker_panels'] = $addPanels;
			$stepVars['mmaker_blocks'] = $addBlocks;
			$stepVars['mmaker_lastfieldid'] = 1;
		
		} elseif ($mode == 'edit' && $step == 2 && $prevstep == 1) {
		
			$prevStepVars = $this->getStepVars($step-1);
			
			// change the first field name
			if (is_array($stepVars['mmaker_blocks'])) {
				foreach ($stepVars['mmaker_blocks'] as &$block) {
					if (is_array($block['fields'])) {
						foreach ($block['fields'] as &$f) {
							if ($f['fieldname'] == 'vcf_1') {
								$f['fieldlabel'] = $prevStepVars['mmaker_mainfield'];
								$f['label'] = $prevStepVars['mmaker_mainfield'];
							}
						}
					}
				}
			}
		
		} elseif ($mode == 'create' && $step == 3) {
			// add the default filter in creation
			// crmv@69398
			$defFilt = array(
				'all' => 1,	// this is the "all" filter
				'name' => 'All',
				'label' => getTranslatedString('LBL_ALL'),
				'columns' => array('vcf_1', 'assigned_user_id'),
			);
			$stepVars['mmaker_filters'][] = $defFilt;
			$stepVars['mmaker_filters'][] = $defFilt;
			// crmv@69398e
		} elseif ($mode == 'create' && $step == 4) {
			// add default relations
			$stepVars['mmaker_relations'] = array();
			if (isModuleInstalled('Calendar')) {
				$stepVars['mmaker_relations'][] = array('type' => '1ton', 'module' => 'Calendar', 'field' => 'parent_id', 'fieldlabel' => getTranslatedString('Related To', 'Calendar') );
			}
			if (isModuleInstalled('Messages')) {
				$stepVars['mmaker_relations'][] = array('type' => 'nton', 'module' => 'Messages');
			}
			if (isModuleInstalled('Documents')) {
				$stepVars['mmaker_relations'][] = array('type' => 'nton', 'module' => 'Documents');
			}
		} elseif ($step == 5 && $prevstep == 4) {
			if ($mode == 'create') {
				// generate the array with translations
				$stepVars['mmaker_labels'] = $this->generateLabels();
			} else {
				// other modes, merge the saved labels with the new ones
				$stepVars['mmaker_labels'] = $this->mergeLabels($stepVars['mmaker_labels']);
			}
			// put them in session
			$this->saveStepVars($step, $stepVars);
		
		} elseif ($mode == 'create' && $step == 6) {
			// crmv@205449
			// enable tools by default if not inventory
			$step1Vars = $this->getStepVars(1);
			$isInventory = ($step1Vars['mmaker_inventory'] == 'on');
			$stepVars['mmaker_enable_import'] = $isInventory ? 0 : 1;
			$stepVars['mmaker_enable_export'] = $isInventory ? 0 : 1;
			$stepVars['mmaker_enable_quickcreate'] = $isInventory ? 0 : 1;
			$stepVars['mmaker_enable_dupcheck'] = 0;
			$stepVars['mmaker_sharing_access'] = 'Private';
			// crmv@205449e
		}
		
	}
	
	/**
	 * Insert all the information in the session cache, for the edit mode
	 */
	public function saveAllVarsFromDb($dbdata) {
		VteSession::setArray(array('mmaker', 'step_vars'), array());
		
		$step1 = $dbdata['moduleinfo'];
		$step1['mmaker_modname'] = $dbdata['modulename'];
		VteSession::setArray(array('mmaker', 'step_vars', 1), $step1);
		
		if (empty($dbdata['panels'])) {
			// an old module without tabs, create a default one
			$dbdata['panels'] = array(
				array(
					'panellabel' => 'LBL_TAB_MAIN',
					'label' => getTranslatedString('LBL_TAB_MAIN'),
				)
			);
			if (is_array($dbdata['fields'])) {
				foreach ($dbdata['fields'] as &$block) {
					$block['panelno'] = 0;
				}
			}
		}
		
		VteSession::setArray(array('mmaker', 'step_vars', 2, 'mmaker_blocks'), $dbdata['fields']);
		VteSession::setArray(array('mmaker', 'step_vars', 2, 'mmaker_panels'), $dbdata['panels']);
		VteSession::setArray(array('mmaker', 'step_vars', 2, 'mmaker_currentpanelno'), 0);
		VteSession::setArray(array('mmaker', 'step_vars', 2, 'mmaker_lastfieldid'), $this->calculateLastFieldId($dbdata['fields']));
		VteSession::setArray(array('mmaker', 'step_vars', 3, 'mmaker_filters'), $dbdata['filters']);
		VteSession::setArray(array('mmaker', 'step_vars', 4, 'mmaker_relations'), $dbdata['relations']);
		VteSession::setArray(array('mmaker', 'step_vars', 5, 'mmaker_labels'), $dbdata['labels']);
		
		VteSession::setArray(array('mmaker', 'step_vars', 6, 'mmaker_enable_quickcreate'), $dbdata['moduleinfo']['enable_quickcreate']);
		VteSession::setArray(array('mmaker', 'step_vars', 6, 'mmaker_enable_import'), $dbdata['moduleinfo']['enable_import']);
		VteSession::setArray(array('mmaker', 'step_vars', 6, 'mmaker_enable_export'), $dbdata['moduleinfo']['enable_export']);
		VteSession::setArray(array('mmaker', 'step_vars', 6, 'mmaker_enable_dupcheck'), $dbdata['moduleinfo']['enable_dupcheck']);
		VteSession::setArray(array('mmaker', 'step_vars', 6, 'mmaker_sharing_access'), $dbdata['moduleinfo']['sharing_access'] ?: 'Private');

	}
	
	protected function calculateLastFieldId($blocks) {
		$lastfieldid = 1;
		if (is_array($blocks)) {
			foreach ($blocks as $b) {
				if (is_array($b['fields'])) {
					foreach ($b['fields'] as $f) {
						if (preg_match('/vcf_([0-9]+)/', $f['fieldname'], $matches)) {
							$fid = intval($matches[1]);
							if ($fid > $lastfieldid) $lastfieldid = $fid;
						}
					}
				}
			}
		}
		return $lastfieldid;
	}
	
	/**
	 * Get all the variables ready to be inserted in the database
	 */
	public function getAllVarsForSave() {
		$out = array();
		if (is_array(VteSession::getArray(array('mmaker', 'step_vars')))) {
			foreach (VteSession::getArray(array('mmaker', 'step_vars')) as $step=>$svars) {
				if ($step == 1) {
					$out['modulename'] = $svars['mmaker_modname'];
					unset($svars['mmaker_modname']);
					$out['moduleinfo'] = $svars;
				} elseif ($step == 2) {
					$out['fields'] = $svars['mmaker_blocks'];
					$out['panels'] = $svars['mmaker_panels'];
				} elseif ($step == 3) {
					$out['filters'] = $svars['mmaker_filters'];
				} elseif ($step == 4) {
					$out['relations'] = $svars['mmaker_relations'];
				} elseif ($step == 5) {
					$out['labels'] = $svars['mmaker_labels'];
				} elseif ($step == 6) {
					$out['moduleinfo']['enable_quickcreate'] = $svars['mmaker_enable_quickcreate'];
					$out['moduleinfo']['enable_import'] = $svars['mmaker_enable_import'];
					$out['moduleinfo']['enable_export'] = $svars['mmaker_enable_export'];
					$out['moduleinfo']['enable_dupcheck'] = $svars['mmaker_enable_dupcheck'];
					$out['moduleinfo']['sharing_access'] = $svars['mmaker_sharing_access'];
				}
			}
		}
		
		return $out;
	}
	
	public function getNewModuleName() {
		$v1 = $this->getStepVars(1);
		return $v1['mmaker_modname'];
	}
		
	// crmv@102879
	public function getNewFieldDefinition($fieldno, $properties, $fieldid = 0, $tableField = false) {
		global $default_charset;	//crmv@98570
		if ($tableField) {
			$addFields = $this->getNewTableFieldColumns();
		} else {
			$addFields = $this->getNewFields();
		}
		$field = $addFields[$fieldno];
		if (!$fieldid) {
			$fieldid = rand(10,10000);
		}
		$allFieldProps = array('length', 'decimals', 'picklistvalues', 'relatedmods', 'relatedmods_selected', 'autoprefix', 'onclick', 'code', 'users', 'newline', 'columns');	//crmv@98570 crmv@101683 crmv@160837
		$fieldProps = array_intersect($field['properties'], $allFieldProps);
		unset($field['properties']);
		$field['fieldname'] = 'vcf_'.$fieldid;
		$field['editable'] = true;
		$field['deletable'] = true;
		$field['mandatory'] = isset($properties['mandatory']) ? (bool)$properties['mandatory'] : false;
		$field['label'] = $properties['label'];
		$field['fieldlabel'] = $properties['label'];
		$field['readonly'] = $properties['readonly'] ?: 1;
		$field['newline'] = isset($properties['newline']) ? (bool)$properties['newline'] : false;
		foreach ($fieldProps as $prop) {
			if (!empty($properties[$prop])) {
				$val = $properties[$prop];
				if (is_array($val)) {
					$val = implode(',', $val);
				}
				/* TODO per picklist, split by \n
				if ($prop == 'picklistvalues' && !is_array($val)) {
					$val = explode("\n", $val);
				}*/
				//crmv@98570
				if (in_array($prop,array('code'))) {
					$val = htmlentities($val,ENT_COMPAT,$default_charset);
				}
				//crmv@98570e
				$field[$prop] = $val;
			}
		}
		if ($field['uitype'] == 220 && $properties['columns']) {
			$MMGen = new ModuleMakerGenerator($this->mmutils, $this);
			$columns = Zend_Json::decode($properties['columns']);
			if (is_array($columns)) {
				$tablecols = array();
				$i = 0;
				foreach ($columns as $coldef) {
					$tfid = ++$i + $fieldid;
					//crmv@160837
					if (!isset($coldef['fieldno'])) {
						if (isset($coldef['decimals']) && $coldef['decimals'] > 0) {
							$fieldno = $this->getNewFieldNoByUitype($coldef['uitype'], 'decimals');
						} else {
							$fieldno = $this->getNewFieldNoByUitype($coldef['uitype']);
						}						
						$coldef['fieldno'] = $fieldno;
					}
					//crmv@160837e
					$col = $this->getNewFieldDefinition($coldef['fieldno'], $coldef, $tfid, true);
					if ($col) {
						$col['typeofdata'] = $MMGen->getTODForField($col);
						$tablecols[] = $col;
					}
				}
				$field['columns'] = Zend_Json::encode($tablecols);
			}
		}
		return $field;
	}
	
	function getNewTableFieldColumns() {
		//crmv@115268
		static $fields = array();
		if (empty($fields)) {
			$unsupported_uitypes = array(213,220/*,10*/);
			$fields = $this->getNewFields();
			foreach($fields as $i => $field) {
				if(in_array($field['uitype'],$unsupported_uitypes)) {
					unset($fields[$i]);
					continue;
				}
				// add other properties
				$fields[$i]['properties'][] = 'readonly';
				$fields[$i]['properties'][] = 'mandatory';
				// add defaults for other properties
				$fields[$i]['defaults']['readonly'] = 1;
				$fields[$i]['defaults']['mandatory'] = false;
			}
			$fields = array_values($fields);
		}
		return $fields;
		//crmv@115268e
	}
	// crmv@102879e
	
	public function getRelationModules() {
		global $adb, $table_prefix;
		
		$skipMods = array('PBXManager', 'Messages', 'ModComments', 'Charts', 'Emails', 'MyNotes', 'Sms', 'Fax', 'Calendar'); // crmv@164120 crmv@164122
		//crmv@170937
		require_once('include/utils/ModLightUtils.php');
		$MLUtils = ModLightUtils::getInstance();
		$moduleLightList = $MLUtils->getModuleList();
		$skipMods = array_merge($skipMods, $moduleLightList);
		//crmv@170937e
		
		$list = array();
		$res = $adb->pquery("SELECT name FROM {$table_prefix}_tab WHERE presence = 0 AND isentitytype = 1 AND name NOT IN (".generateQuestionMarks($skipMods).")", $skipMods);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$list[$row['name']] = getTranslatedString($row['name'], $row['name']);
			}
		}
		asort($list);
		return $list;
	}
	
	/**
	 * Get a list of fields that can be created
	 */
	public function getNewFields() {
		// prop: label, length, decimals, picklistvalues, relatedmods, autoprefix
		//crmv@115268
		static $list = array();
		if (!empty($list)) return $list;
		//crmv@115268e
		
		$v1 = $this->getStepVars(1);
		$modName = $v1['mmaker_modname'];
		
		$list = array();
		$list[] = array(
			'uitype' => 1,
			'label' => getTranslatedString('Text'),
			'vteicon' => 'text_fields',
			'properties' => array('label', 'length'),
			'defaults' => array('length' => 200),
		);
		$list[] = array(
			'uitype' => 21,
			'label' => getTranslatedString('LBL_TEXT_AREA'),
			'vteicon' => 'text_fields',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 7,
			'label' => getTranslatedString('Number'),
			'vteicon' => 'N',
			'properties' => array('label', 'length'),
			'defaults' => array('length' => 7),
		);
		$list[] = array(
			'uitype' => 7,
			'label' => getTranslatedString('LBL_NUMBER_WITH_DECIMALS', 'APP_STRINGS'),
			'vteicon' => 'N',
			'properties' => array('label', 'length', 'decimals'),
			'defaults' => array('length' => 11, 'decimals' => 3),
		);
		$list[] = array(
			'uitype' => 9,
			'label' => getTranslatedString('Percent'),
			'vteicon2' => 'fa-percent', // crmv@102879
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 71,
			'label' => getTranslatedString('Currency'),
			'vteicon' => 'attach_money',
			'properties' => array('label', 'length', 'decimals'),
			'defaults' => array('length' => 15, 'decimals' => 3),
		);
		$list[] = array(
			'uitype' => 5,
			'label' => getTranslatedString('Date'),
			'vteicon' => 'date_range',
			'properties' => array('label'),
		);
		//crmv@146187
		if (SDK::isUitype(73)) {
			$list[] = array(
				'uitype' => 73,
				'label' => getTranslatedString('LBL_HOUR'),
				'vteicon' => 'access_time',
				'properties' => array('label'),
			);
		}
		//crmv@146187e
		$list[] = array(
			'uitype' => 13,
			'label' => getTranslatedString('Email'),
			'vteicon' => 'email',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 11,
			'label' => getTranslatedString('Phone'),
			'vteicon' => 'phone',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 207, // crmv@80653
			'label' => getTranslatedString('LBL_URL'),
			'vteicon' => 'http',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 56,
			'label' => getTranslatedString('LBL_CHECK_BOX'),
			'vteicon' => 'check_box',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 15,
			'label' => getTranslatedString('PickList'),
			'vteicon' => 'list',
			'properties' => array('label', 'picklistvalues'),
		);
		$list[] = array(
			'uitype' => 33,
			'label' => getTranslatedString('LBL_MULTISELECT_COMBO'),
			'vteicon' => 'list',
			'properties' => array('label', 'picklistvalues'),
		);
		$list[] = array(
			'uitype' => 1015,
			'label' => getTranslatedString('Picklistmulti'),
			'vteicon' => 'list',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 4,
			'label' => getTranslatedString('LBL_FIELD_AUTONUMBER'),
			'vteicon' => 'A',
			'properties' => array('label', 'autoprefix'),
			'defaults' => array('autoprefix' => (empty($modName) ? '' : strtoupper(substr($modName, 0, 3)))),
		);
		$list[] = array(
			'uitype' => 85,
			'label' => getTranslatedString('Skype'),
			'vteicon2' => 'fa-skype',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 10,
			'label' => getTranslatedString('LBL_RELATED_TO'),
			'vteicon' => 'link',
			'properties' => array('label', 'relatedmods', 'relatedmods_selected'),
			'relatedmods' => $this->getRelationModules(),
			'relatedmods_selected' => array(),
		);
		//crmv@96450	crmv@101683
		$list[] = array(
			'uitype' => 52,
			'label' => getTranslatedString('LBL_USER','Users'),
			'vteicon' => 'person',
			'properties' => array('label'),
		);
		$list[] = array(
			'uitype' => 51,
			'label' => getTranslatedString('LBL_SELECT_ALL_USER','Users'),
			'vteicon' => 'person',
			'properties' => array('label'),
		);
		// TODO uitype 50
		$list[] = array(
			'uitype' => 50,
			'label' => getTranslatedString('LBL_SELECT_CUSTOM_USER','Users'),
			'vteicon' => 'person',
			'properties' => array('label', 'users'),
			'users' => get_user_array(true, "Active"),
		);
		$list[] = array(
			'uitype' => 54,
			'label' => getTranslatedString('Group','Users'),
			'vteicon' => 'group',
			'properties' => array('label'),
		);
		//crmv@96450e	crmv@101683e
		//crmv@115268
		if (SDK::isUitype(29)) {
			$list[] = array(
				'uitype' => 29,
				'label' => getTranslatedString('LBL_ATTACH_DOCUMENTS'),
				'vteicon' => 'attach_file',
				'properties' => array('label'),
			);
		}
		//crmv@115268e
		return $list;
	}
	
	public function getNewFieldNoByUitype($uitype, $prop=false) {
		if (!empty($prop)) {
			$fields = $this->getNewFields();
			foreach ($fields as $fieldno => $fld) {
				if ($uitype == $fld['uitype'] && in_array($prop,$fld['properties'])) {
					return $fieldno;
				}
			}
		}
		// standard case
		static $fields = array();
		static $fields_no = array();
		if (empty($fields)) $fields = $this->getNewFields();
		if (empty($fields_no)) {
			foreach ($fields as $fieldno => $fld) {
				$fields_no[$fieldno] = $fld['uitype'];
			}
		}
		return array_search($uitype,$fields_no);
	}
	
	public function getFilterFields() {
		$v2 = $this->getStepVars(2);
		
		$fields = array();
		
		if (is_array($v2['mmaker_blocks'])) {
			foreach ($v2['mmaker_blocks'] as $block) {
				if (is_array($block['fields'])) {
					$blockFields = array();
					foreach ($block['fields'] as $field) {
						$blockFields[] = array(
							'label' =>  $field['label'],
							'fieldname' => $field['fieldname'],

						);
					}
					$fields[] = array(
						'blocklabel' => $block['label'],
						'fields' => $blockFields,
					);
				}
			}
		}
		
		return $fields;
	}
	
	public function getRelations_N1() {
		$rel = array();
		$v2 = $this->getStepVars(2);
		
		// use the uitype 10 fields
		if (is_array($v2['mmaker_blocks'])) {
			foreach ($v2['mmaker_blocks'] as $block) {
				if (is_array($block['fields'])) {
					foreach ($block['fields'] as $field) {
						if ($field['uitype'] == 10) {
							// crmv@113738
							$rmods = $modnames = $field['relatedmods'];
							if (is_string($rmods)) {
								$rmods = $modnames = explode(',', $rmods);
								foreach ($rmods as &$m) {
									$m = getTranslatedString($m, $m);
								}
							}
							$rel[] = array(
								'type' => 'NTO1',
								'fieldname' => $field['fieldname'],
								'label' => $field['label'],
								'mods' => $rmods,
								'modules' => $modnames
							);
							// crmv@113738e
						}
					}
				}
			}
		}
		return $rel;
	}
	
	public function getTranslationsForGrid() {
		$list = array();
		
		$lbl = array(
			'LBL_TRANS_ALL', 'LBL_TRANS_LANGUAGE', 'LBL_TRANS_MANDATORY', 'LBL_TRANS_MODULE', 'LBL_TRANS_LABEL', 'LBL_TRANS_SEARCH', 'LBL_TRANS_ACTIONS', 
		);
		foreach ($lbl as $l) {
			$list[$l] = getTranslatedString($l);
		}
		
		return $list;
	}
	
	public function getLabelsModules($vars) {
		$modules = array();
		$labels = $vars['mmaker_labels'];
		
		if (is_array($labels)) {
			foreach ($labels as $l) {
				$mod = $l['modulename'];
				if (!array_key_exists($mod, $modules)) {
					if ($mod == 'APP_STRINGS') {
						$mlab = getTranslatedString('LBL_TRANS_APP_STRINGS');
					} else {
						$mlab = getTranslatedString($mod, $mod);
					}
					$modules[$mod] = $mlab;
				}
			}
		}
		
		return $modules;
	}
	
	public function generateLabels() {
		global $current_language;
	
		$labels = array();
		
		$v1 = $this->getStepVars(1);
		$module = $v1['mmaker_modname'];
		
		// module general labels
		$labels[] = array('modulename' => $module, 'type'=>'general', 'label' => $module, 'trans' => $v1['mmaker_modlabel']);
		$labels[] = array('modulename' => 'APP_STRINGS', 'type'=>'general', 'label' => $module, 'trans' => $v1['mmaker_modlabel']);
		$labels[] = array('modulename' => $module, 'type'=>'general', 'label' => 'SINGLE_'.$module, 'trans' => $v1['mmaker_single_modlabel']);
		$labels[] = array('modulename' => 'APP_STRINGS', 'type'=>'general', 'label' => 'SINGLE_'.$module, 'trans' => $v1['mmaker_single_modlabel']);
		
		$v2 = $this->getStepVars(2);
		
		// panels
		if (is_array($v2['mmaker_panels'])) {
			foreach ($v2['mmaker_panels'] as $panel) {
				if ($panel['panellabel'] != 'LBL_TAB_MAIN') {
					$labels[] = array('modulename' => $module, 'type'=>'fields', 'label' => $panel['panellabel'], 'trans' => $panel['label']);
				}
			}
		}
		
		// blocks
		foreach ($v2['mmaker_blocks'] as $block) {
			$labels[] = array('modulename' => $module, 'type'=>'fields', 'label' => $block['blocklabel'], 'trans' => $block['label']);
		}
		
		// these fields already have a valid translation in other languages
		$fieldsWithTrans = array('createdtime', 'modifiedtime', 'description', 'assigned_user_id');
		$fieldSkipTrans = array(); // no special fields to skip
		$langs = vtlib_getToggleLanguageInfo();
		
		// fields
		foreach ($v2['mmaker_blocks'] as $block) {
			if (is_array($block['fields'])) {
				foreach ($block['fields'] as $field) {
					// skip the field
					if (in_array($field['fieldname'], $fieldSkipTrans)) {
						continue;
					}
					
					// set the label
					$labels[] = array('modulename' => $module, 'type'=>'fields', 'label' => $field['fieldlabel'], 'trans' => $field['label']);
					
					// set the translated labels for some default fields
					if (in_array($field['fieldname'], $fieldsWithTrans)) {
						$lastidx = count($labels)-1;
						$oldLang = $current_language;
						foreach ($langs as $lang=>$langinfo) {
							if ($langinfo['active']) {
								$current_language = $lang;
								$trans = getTranslatedString($field['fieldlabel'], 'APP_STRINGS');
								$labels[$lastidx][$lang] = $trans;
							}
						}
						$current_language = $oldLang;
					}
					if (!empty($field['picklistvalues'])) {
						$values = array_map('trim', array_unique(explode("\n", $field['picklistvalues'])));	//crmv@113771
						foreach ($values as $pval) {
							$labels[] = array('modulename' => $module, 'type'=>'fieldvalues', 'label' => $pval, 'trans' => $pval);
						}
					}
				}
			}
		}
		
		// related fields
		$v4 = $this->getStepVars(4);
		if (is_array($v4['mmaker_relations'])) {
			foreach ($v4['mmaker_relations'] as $rel) {
				if ($rel['type'] == '1ton' && !empty($rel['field'])) {
					// skip this special field
					if ($rel['field'] == 'parent_id') continue;
					// add the relation field
					$labels[] = array('modulename' => $module, 'type'=>'fields', 'label' => $rel['field'], 'trans' => $rel['field']);
					$labels[] = array('modulename' => $rel['module'], 'type'=>'fields', 'label' => $rel['field'], 'trans' => $rel['field']);
				}
			}
		}
		
		// now for each language, use the same translation
		foreach ($labels as &$l) {
			foreach ($langs as $lang=>$langinfo) {
				if ($langinfo['active']) {
					if (!array_key_exists($lang, $l)) $l[$lang] = $l['trans'];
				}
			}
			unset($l['trans']);
		}
		
		return $labels;
	}
	
	public function mergeLabels($labels) {
		// merge the saved labels with the default ones
		
		$defaults = $this->generateLabels();
		
		if (!is_array($labels)) return $defaults;
		
		// not very efficient, but there shouln't be many labels anyway
		foreach ($defaults as $deflab) {
			// search for a corresponding label in the saved ones
			$found = null;
			foreach ($labels as $k=>$lab) {
				if ($deflab['modulename'] == $lab['modulename'] && $deflab['label'] == $lab['label']) {
					$found = $k;
					break;
				}
			}
			if (is_null($found)) {
				// not present, add it!
				$labels[] = $deflab;
			} else {
				// found it, merge empty labels!
				$orig = $labels[$found];
				foreach ($deflab as $k=>$v) {
					if (preg_match('/^[a-z][a-z]_[a-z][a-z]$/', $k)) {
						// it's a language
						if (!array_key_exists($k, $orig) || empty($orig[$k])) {
							$labels[$found][$k] = $v;
						}
					}
				}
			}
		}
		
		return $labels;
	}
	
	
}