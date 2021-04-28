<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@69398 crmv@105127 */

require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
require_once('modules/Settings/ModuleMaker/ModuleMakerSteps.php');
require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

$mode = 'ajax';
$moduleid = intval($_REQUEST['moduleid']);
$action = $_REQUEST['subaction'];
$raw = null;
$tpl = '';
$json = null;

$MMUtils = new ModuleMakerUtils();
//crmv@96450
$processMakerMode = ($_REQUEST['processMakerMode'] == 'yes');
if ($processMakerMode) {
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$MMSteps = new ProcessModuleMakerSteps($MMUtils);
	$smarty->assign("PROCESSMAKERMODE", $processMakerMode);
} else {
	$MMSteps = new ModuleMakerSteps($MMUtils);
}
//crmv@96450e
$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
$MMajax = new ModuleMakerAjax($MMUtils, $MMSteps, $MMGen);

$fieldops = array(
	'addpanel', 'delpanel', 'movepanel', 'reorderpanels',
	'addblock', 'delblock', 'moveblock', 'moveblockpanel', 'editblock', //crmv@160837
	'movefield', 'addfield', 'movefieldstoblock', 'delfield', 'editfield');
$labelsops = array('labels_list', 'labels_edit', 'labels_edit_cell');
$scriptOpts = array('load_script', 'save_script', 'reset_scripts');

if (in_array($action, $fieldops)) {
	$step = 2;

	// convert the input variables in a structurd array
	$vars = $_REQUEST;
	$MMSteps->preprocessStepVars($mode, $step, 0, $vars);
	$vars = $MMSteps->extractStepVars($vars);
	
	// do the operation requested
	if ($action == 'addpanel') {
		$MMajax->addPanel($vars, $_REQUEST['addpanelname']);
	} elseif ($action == 'delpanel') {
		$MMajax->delPanel($vars, $_REQUEST['delpanelno'], $_REQUEST['moveblocksno']);
	} elseif ($action == 'movepanel') {
		// not supported
	} elseif ($action == 'reorderpanels') {
		$MMajax->reorderPanels($vars, Zend_Json::decode($_REQUEST['panelsorder']));
	} elseif ($action == 'moveblockpanel') {
		$MMajax->moveBlockToPanel($vars, $_REQUEST['moveblockno'], $_REQUEST['movepanelno']);
	} elseif ($action == 'addblock') {
		$MMajax->addBlock($vars, $_REQUEST['addblockname'], $_REQUEST['mmaker_currentpanelno'], $_REQUEST['addafterblock']);
	} elseif ($action == 'delblock') {
		$MMajax->delBlock($vars, $_REQUEST['delblockno']);
	} elseif ($action == 'moveblock') {
		$MMajax->moveBlock($vars, $_REQUEST['moveblockno'], $_REQUEST['direction']);
	//crmv@160837
	} elseif ($action == 'editblock') {
		$MMajax->editblock($vars, $_REQUEST['editblockno'], $_REQUEST['editlabel']);
	//crmv@160837e
	} elseif ($action == 'movefield') {
		$MMajax->moveField($vars, $_REQUEST['blockno'], $_REQUEST['movefieldno'], $_REQUEST['direction']);
	} elseif ($action == 'addfield') {
		$MMajax->addField($vars, $_REQUEST['blockno'], $_REQUEST['addfieldno'], Zend_Json::decode($_REQUEST['properties']));
	} elseif ($action == 'movefieldstoblock') {
		$MMajax->moveFieldsToBlock($vars, $_REQUEST['movetoblockno'], Zend_Json::decode($_REQUEST['fields']));
	} elseif ($action == 'delfield') {
		$MMajax->delField($vars, $_REQUEST['blockno'], $_REQUEST['delfieldno']);
	} elseif ($action == 'editfield') {
		$MMajax->editField($vars, $_REQUEST['blockno'], $_REQUEST['editfieldno'], Zend_Json::decode($_REQUEST['properties']));
	}

	// then prepare the vars for the output
	$MMSteps->processStepVars($mode, $step, 0, $vars);
	
	if ($step == 2) {
		$smarty->assign("NEWFIELDS", $MMSteps->getNewFields());
		$smarty->assign("NEWTABLEFIELDCOLUMNS", $MMSteps->getNewTableFieldColumns()); // crmv@102879
	}

	$smarty->assign("STEPVARS", $vars);
	$tpl = 'Step2Fields.tpl';

} elseif ($action == 'getmoduleblocks') {
	$blocks = $MMajax->getModuleBlocks(vtlib_purify($_REQUEST['blockmodule']), vtlib_purify($_REQUEST['firstmodule']));
	$json = $blocks;
	
} elseif ($action == 'addrelation' || $action == 'delrelation') {
	$step = 4;
	
	$vars = $_REQUEST;
	$MMSteps->preprocessStepVars($mode, $step, 0, $vars);
	$vars = $MMSteps->extractStepVars($vars);
	
	if ($action == 'addrelation') {
		$MMajax->addRelation($vars, $_REQUEST['type'], vtlib_purify($_REQUEST['firstrelationmod']), vtlib_purify($_REQUEST['relationmod']), $_REQUEST['blockname'], $_REQUEST['fieldname']);
	} elseif ($action == 'delrelation') {
		if ($_REQUEST['rel_n1'] == '1') {
			$MMajax->delRelationN1($vars, $_REQUEST['delrelationfield']);
		} else {
			$MMajax->delRelation($vars, $_REQUEST['delrelationno']);
		}
	}
	
	// then prepare the vars for the output
	$MMSteps->processStepVars($mode, $step, 0, $vars);
	
	if ($step == 4) {
		$smarty->assign("RELATION_MODULES", $MMSteps->getRelationModules());
		$smarty->assign("RELATIONS_N1", $MMSteps->getRelations_N1());
	}
	$smarty->assign('NEWMODULENAME', $MMSteps->getNewModuleName());	
	$smarty->assign("STEPVARS", $vars);
	$tpl = 'Step4Relations.tpl';
	
} elseif (in_array($action, $labelsops)) {
	$step = 5;
	
	//$vars = $_REQUEST;
	/*$MMSteps->preprocessStepVars($mode, $step, 0, $vars);
	$vars = $MMSteps->extractStepVars($vars);*/
	
	$vars = $MMSteps->getStepVars($step);
	
	if ($action == 'labels_list') {
		$opts = array(
			'page' => intval($_REQUEST['page']),
			'rows' => intval($_REQUEST['rows']),
			'sidx' => $_REQUEST['sidx'],
			'sord' => $_REQUEST['sord'],
			'module' => $_REQUEST['module_select'],
			'language' => $_REQUEST['language_select'],
			'type' => $_REQUEST['filter_select'],
		);
		$json = $MMajax->getLabelsList($vars, $opts);
	} elseif ($action == 'labels_edit' || $action == 'labels_edit_cell') {
		// extract the languages
		$langs = array();
		foreach ($_REQUEST as $k=>$v) {
			if (preg_match('/^[a-z][a-z]_[a-z][a-z]$/', $k)) {
				$langs[$k] = $v;
			}
		}
		$opts = array('languages' => $langs);
		$MMajax->editLabel($vars, $_REQUEST['id'], $opts);
		
		// save the modified values
		$MMSteps->saveStepVars($step, $vars);
		
		$json = array('confirm'=> false, 'msg'=>false, 'success'=>true);
	}
	
} elseif (in_array($action, $scriptOpts)) {

	if ($MMUtils->canEditScripts()) {
		if ($action == 'load_script') {
			$data = $MMUtils->getEditScript($moduleid, $_REQUEST['script_file']);
			$raw = $data;
		} elseif ($action == 'save_script') {
			$ok = $MMUtils->saveEditScript($moduleid, $_REQUEST['script_file'], $_REQUEST['script_data']);
			$json = array('success' => $ok);
		} elseif ($action == 'reset_scripts') {
			$error = $MMUtils->resetEditScripts($moduleid);
			$json = array('success' => empty($error), 'error' => $error);
		}
	} else {
		$error = getTranslatedString('LBL_NOT_ALLOWED_OPERATION');
		$json = array('success' => false, 'error' => $error);
	}
	
} elseif ($action == 'export') {

	$url = "";
	if ($MMUtils->canExport()) {
		$error = $MMUtils->exportScript($moduleid, $url);
		$ok = empty($error);
	} else {
		$ok = false;
		$error = getTranslatedString('LBL_NOT_ALLOWED_OPERATION');
	}
	$json = array('success' => $ok, 'error' => $error, 'url' => $url);
	
} elseif ($action == 'getlog') {

	$logText = '';
	$error = '';
	if ($_REQUEST['logname'] == 'install_log' || $_REQUEST['logname'] == 'uninstall_log') {
		$names = $MMUtils->getScriptFileNames($moduleid);
		$file = $names[$_REQUEST['logname']];
		if (is_readable($file)) {
			$logText = file_get_contents($file);
			$logText = str_replace('<BR>', '', $logText);
			$ok = true;
		} else {
			$error = 'Log not found';
			$ok = false;
		}
	} else {
		$error = "Unknown log";
		$ok = false;
	}
	
	$json = array('success' => $ok, 'error' => $error, 'log' => $logText);

} elseif ($action == 'install_module') {
	
	$error = $MMajax->installModule($moduleid);
	$json = array('success' => empty($error), 'error' => $error);
	
} elseif ($action == 'uninstall_module') {
	
	$error = $MMajax->uninstallModule($moduleid);
	$json = array('success' => empty($error), 'error' => $error);
	
} elseif ($action == 'delete_module') {

	$error = $MMajax->deleteModule($moduleid);
	$json = array('success' => empty($error), 'error' => $error);
}



// output
if (!is_null($raw)) {
	echo $raw;
	exit(); // sorry, I have to do this, some html shit is spitted out at the end of the page
} elseif (!empty($tpl)) {
	$smarty->display('Settings/ModuleMaker/'.$tpl);
} elseif (!empty($json)) {
	echo Zend_Json::encode($json);
	exit(); // idem
} else {
	echo "No data returned";
}


// ---------------------- CLASSES ---------------------


// assume the blocks and fields are ordered sequentially by arraykey
class ModuleMakerAjax {

	protected $mmsteps = null;
	protected $mmutils = null;
	protected $mmgen = null;

	public function __construct($mmutils = null, $mmsteps = null, $mmgen = null) {
		$this->mmutils = $mmutils;
		$this->mmsteps = $mmsteps;
		$this->mmgen = $mmgen;
	}
	
	public function addPanel(&$vars, $panellabel) {
		$panellabel = substr($panellabel, 0, 50);
		$name = 'LBL_TAB_'.strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $panellabel));
		
		if (!is_array($vars['mmaker_panels'])) {
			$vars['mmaker_panels'] = array();
		}
		
		$vars['mmaker_panels'][] = array(
			'panellabel' => $name,
			'label' => $panellabel,
		);
	}
	
	public function delPanel(&$vars, $panelno, $moveblocksno = null) {
		if (is_array($vars['mmaker_panels'])) {
			// move the blocks in the other panel
			if (!is_null($moveblocksno) && is_array($vars['mmaker_blocks'])) {
				foreach ($vars['mmaker_blocks'] as &$block) {
					if ($block['panelno'] == $panelno) {
						if ($panelno < $moveblocksno) {
							$block['panelno'] = $moveblocksno-1;
						} else {
							$block['panelno'] = $moveblocksno;
						}
					} elseif ($block['panelno'] > $panelno) {
						--$block['panelno'];
					}
				}
			}
			unset($vars['mmaker_panels'][$panelno]);
			$vars['mmaker_panels'] = array_values($vars['mmaker_panels']);
			if ($vars['mmaker_currentpanelno'] == $panelno) {
				$vars['mmaker_currentpanelno'] = 0;
			} elseif ($panelno < $vars['mmaker_currentpanelno']) {
				--$vars['mmaker_currentpanelno'];
			}
		}
	}
	
	public function reorderPanels(&$vars, $panelsorder) {
		if (is_array($vars['mmaker_panels']) && is_array($panelsorder)) {
			// No need to do anything, the form already passes the ordered values!
			// I only have to fix the current panel no
			//$newlist = array();
			$currentChanged = false;
			foreach ($panelsorder as $idx => $panelno) {
				if (!$currentChanged && $panelno == $vars['mmaker_currentpanelno']) {
					$vars['mmaker_currentpanelno'] = $idx;
					$currentChanged = true;
				}
				// change the blocks
				if (is_array($vars['mmaker_blocks'])) {
					foreach ($vars['mmaker_blocks'] as &$block) {
						if ($block['panelno'] == $panelno && !$block['reordered']) {
							$block['panelno'] = $idx;
							$block['reordered'] = true;
						}
					}
				}
				
				//$newlist[] = $vars['mmaker_panels'][$panelno];
			}
			//$vars['mmaker_panels'] = $newlist;
			
			// remove the reorderd flag
			if (is_array($vars['mmaker_blocks'])) {
				foreach ($vars['mmaker_blocks'] as &$block) {
					unset($block['reordered']);
				}
			}
		}
		
	}
	
	public function moveBlockToPanel(&$vars, $blockno, $panelno) {
		if (is_array($vars['mmaker_blocks']) && is_array($vars['mmaker_panels'])) {
			$vars['mmaker_blocks'][$blockno]['panelno'] = $panelno;
		}
	}

	public function addBlock(&$vars, $blocklabel, $panelno, $afterblock = 0) {
		$blocklabel = substr($blocklabel, 0, 50);
		
		$name = 'LBL_BLOCK_'.strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $blocklabel));
		
		if (is_array($vars['mmaker_blocks'])) {
			$block = array(
				'blocklabel' => $name,
				'label' => $blocklabel,
				'editable' => true,
				'deletable' => true,
			);
			if (is_array($vars['mmaker_panels'])) {
				$panelLabel = $vars['mmaker_panels'][$panelno]['panellabel'];
				if ($panelLabel) {
					$block['panelno'] = $panelno;
				}
			}
			if (empty($vars['mmaker_blocks']))
				$vars['mmaker_blocks'][] = $block;
			else
				array_splice($vars['mmaker_blocks'], $afterblock+1, 0, array($block));
		}
	}
	
	public function delBlock(&$vars, $blockno) {
		if (is_array($vars['mmaker_blocks'])) {
			unset($vars['mmaker_blocks'][$blockno]);
			$vars['mmaker_blocks'] = array_values($vars['mmaker_blocks']);
		}
	}
	
	public function moveBlock(&$vars, $blockno, $direction) {
		if (is_array($vars['mmaker_blocks'])) {
			$swapIdx = null;
			if ($direction == 'up' && $blockno > 0) {
				$swapIdx = $blockno-1;
			} elseif ($direction == 'down' && $blockno < count($vars['mmaker_blocks'])-1 ) {
				$swapIdx = $blockno+1;
			}
			if (!is_null($swapIdx)) {
				$tb = $vars['mmaker_blocks'][$swapIdx];
				$vars['mmaker_blocks'][$swapIdx] = $vars['mmaker_blocks'][$blockno];
				$vars['mmaker_blocks'][$blockno] = $tb;
			}
		}
	}
	
	//crmv@160837
	public function editBlock(&$vars, $blockno, $blocklabel) {
		if (is_array($vars['mmaker_blocks'])) {
			$vars['mmaker_blocks'][$blockno]['label'] = $blocklabel;
			$vars['mmaker_blocks'][$blockno]['blocklabel'] = 'LBL_BLOCK_'.strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '', $blocklabel));
		}
	}
	//crmv@160837e
	
	public function moveField(&$vars, $blockno, $fieldno, $direction) {
		if (is_array($vars['mmaker_blocks'][$blockno]['fields'])) {
			$swapIdx = null;
			$count = count($vars['mmaker_blocks'][$blockno]['fields']);
			if ($direction == 'up' && $fieldno > 1) {
				$swapIdx = $fieldno-2;
			} elseif ($direction == 'down' && $fieldno < $count-2 ) {
				$swapIdx = $fieldno+2;
			} elseif ($direction == 'left' && ($fieldno % 2 == 1)) {
				$swapIdx = $fieldno-1;
			} elseif ($direction == 'right' && ($fieldno % 2 == 0) && $fieldno < $count-1 ) {
				$swapIdx = $fieldno+1;
 			}
			if (!is_null($swapIdx)) {
				$tb = $vars['mmaker_blocks'][$blockno]['fields'][$swapIdx];
				$vars['mmaker_blocks'][$blockno]['fields'][$swapIdx] = $vars['mmaker_blocks'][$blockno]['fields'][$fieldno];
				$vars['mmaker_blocks'][$blockno]['fields'][$fieldno] = $tb;
			}
		}
	}
	
	public function addField(&$vars, $blockno, $addfieldno, $properties, $forced_properties=array()) { //crmv@160837
		if (is_array($vars['mmaker_blocks'][$blockno])) {
			if (!is_array($vars['mmaker_blocks'][$blockno]['fields'])) {
				$vars['mmaker_blocks'][$blockno]['fields'] = array();
			}
			++$vars['mmaker_lastfieldid'];
			$fieldid = $vars['mmaker_lastfieldid'];
			$field = $this->mmsteps->getNewFieldDefinition($addfieldno, $properties, $fieldid);
			//crmv@160837
			if (!empty($forced_properties)) {
				foreach($forced_properties as $k => $v) {
					$field[$k] = $v;
				}
			}
			//crmv@160837e
			$vars['mmaker_blocks'][$blockno]['fields'][] = $field;
		}
	}
	
	public function moveFieldsToBlock(&$vars, $blockno, $fields) {
		if (is_array($vars['mmaker_blocks'][$blockno])) {
			if (!is_array($vars['mmaker_blocks'][$blockno]['fields'])) {
				$vars['mmaker_blocks'][$blockno]['fields'] = array();
			}

			foreach ($fields as $srcblock=>$listFields) {
				foreach ($listFields as $fno) {
					$vars['mmaker_blocks'][$blockno]['fields'][] = $vars['mmaker_blocks'][$srcblock]['fields'][$fno];
					// now remove the old field
					unset($vars['mmaker_blocks'][$srcblock]['fields'][$fno]);
				}
				// and compact the indexes
				$vars['mmaker_blocks'][$srcblock]['fields'] = array_values($vars['mmaker_blocks'][$srcblock]['fields']);
			}
		}
	}
	
	public function delField(&$vars, $blockno, $delfieldno) {
		if (is_array($vars['mmaker_blocks'][$blockno]['fields'])) {
			unset($vars['mmaker_blocks'][$blockno]['fields'][$delfieldno]);
			$vars['mmaker_blocks'][$blockno]['fields'] = array_values($vars['mmaker_blocks'][$blockno]['fields']);
		}
	}
	
	public function editField(&$vars, $blockno, $editfieldno, $properties) {
		global $default_charset;	//crmv@98570
		if (is_array($vars['mmaker_blocks'][$blockno]['fields'][$editfieldno])) {
			foreach ($properties as $prop=>$val) {
				if (in_array($prop,array('mandatory','qcreate','masseditable')))
					$vars['mmaker_blocks'][$blockno]['fields'][$editfieldno][$prop] = ($val ? 1 : 0);
				// crmv@98570 crmv@102879 crmv@118977
				elseif (in_array($prop,array('code'))) {
					$vars['mmaker_blocks'][$blockno]['fields'][$editfieldno][$prop] = htmlentities($val,ENT_COMPAT,$default_charset);
				} elseif ($prop == 'columns') {
					$cols = Zend_Json::decode($val);
					// update mmaker_lastfieldid with max number
					foreach ($cols as $col) {
						if (!empty($col['fldname'])) {
							$new_fieldid = str_replace('vcf_','',$col['fldname']);
							if ($new_fieldid > $vars['mmaker_lastfieldid']) $vars['mmaker_lastfieldid'] = $new_fieldid;
						}
					}
					$val = array();
					$fieldid = $vars['mmaker_lastfieldid'];
					foreach ($cols as $col) {
						if (!empty($col['fldname'])) {
							$new_fieldid = str_replace('vcf_','',$col['fldname']);
						} else {
							$new_fieldid = ++$fieldid;
						}
						$nfield = $this->mmsteps->getNewFieldDefinition($col['fieldno'], $col, $new_fieldid, true);
						$val[] = $nfield;
					}
					$vars['mmaker_blocks'][$blockno]['fields'][$editfieldno][$prop] = Zend_Json::encode($val);
				} else {
					$vars['mmaker_blocks'][$blockno]['fields'][$editfieldno][$prop] = $val;
				}
				// crmv@98570e crmv@102879e crmv@118977e
			}
		}
	}
	
	public function getModuleBlocks($module, $firstModule = null) {
		global $adb, $table_prefix;
		
		$blocks = array();
		$singlename = '';
		$myself = $this->mmsteps->getNewModuleName();
		
		if ($module == $myself) {
			$stepVars = $this->mmsteps->getStepVars(2);
			if (is_array($stepVars['mmaker_blocks'])) {
				foreach ($stepVars['mmaker_blocks'] as $block) {
					$blocks[] = array(
						'blocklabel' => $block['blocklabel'],
						'label' => $block['label'],
					);
				}
			}
			$singlename = getTranslatedString('SINGLE_'.$firstModule, $firstModule);
		} else {
			$tabid = getTabid($module);
			$res = $adb->pquery(
				"SELECT b.blocklabel, p.panellabel 
				FROM {$table_prefix}_blocks b
				LEFT JOIN {$table_prefix}_panels p ON p.panelid = b.panelid
				WHERE b.blocklabel != '' AND b.tabid = ? AND b.visible = 0 ORDER BY b.sequence ASC", 
				array($tabid)
			);
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$blocks[] = array(
					'blocklabel' => $row['blocklabel'],
					'label' => getTranslatedString($row['blocklabel'], $module),
					'panelno' => 0,
				);
			}
			$stepVars = $this->mmsteps->getStepVars(1);
			$singlename = $stepVars['mmaker_single_modlabel'];
		}
		
		$blocks = array('blocks' => $blocks, 'singlename' => $singlename);
		
		return $blocks;
	}
	
	public function addRelation(&$vars, $type, $firstModule, $module, $block, $field) {
	
		$myself = $this->mmsteps->getNewModuleName();
		
		if (!is_array($vars['mmaker_relations'])) {
			$vars['mmaker_relations'] = array();
		}
		
		if ($type && $module) {
		
			if ($module == $myself) {
				$vars2 = $this->mmsteps->getStepVars(2);
				//find the block
				foreach ($vars2['mmaker_blocks'] as $blockno=>$bl) {
					if ($bl['blocklabel'] == $block) {
						// ok, this is the block
						$fieldno = $this->mmsteps->getNewFieldNoByUitype(10);
						if ($fieldno !== false) {
							$this->addField($vars2, $blockno,$fieldno, array('label' => $field, 'relatedmods' => $firstModule));
							$this->mmsteps->saveStepVars(2, $vars2);
						}
						break;
					}
				}
		
			} else {
				$relation = array(
					'type' => $type,
					'module' => $module,
				);
				if ($type == '1ton') {
					$relation['field'] = $field;
					$relation['block'] = $block;
				}
				$vars['mmaker_relations'][] = $relation;
			}
		}
	}
	
	public function delRelation(&$vars, $relno) {
		if (is_array($vars['mmaker_relations'])) {
			unset($vars['mmaker_relations'][$relno]);
			$vars['mmaker_relations'] = array_values($vars['mmaker_relations']);
		}
	}
	
	public function delRelationN1(&$vars, $fieldlabel) {
		$vars2 = $this->mmsteps->getStepVars(2);
		foreach ($vars2['mmaker_blocks'] as $blockno=>$block) {
			if (is_array($block['fields'])) {
				foreach ($block['fields'] as $fieldno=>$field) {
					if ($field['uitype'] == 10 && $field['fieldlabel'] == $fieldlabel) {
						$this->delField($vars2, $blockno, $fieldno);
						$this->mmsteps->saveStepVars(2, $vars2);
						return true;
					}
				}
			}
		}
		return false;
	}
	
	public function getLabelsList(&$vars, $opts) {
		//get all labels for the module
		$labels = $vars['mmaker_labels'];
		
		$page = intval($opts['page']) ?: 1;
		$pagesize = intval($opts['rows']) ?: 20;
		$sortid = $opts['sidx'];
		$sortord = strtolower($opts['sord']) ?: 'asc';
		$module = $opts['module'];
		$language = $opts['language'];
		$type = $opts['type'];
		
		// set an id (ordinal position in the fuill list)
		foreach ($labels as $k=>&$l) {
			$l['id'] = $k;
		}
		
		// now filter/order them
		
		// apply where
		$dofilter = !empty($module) || !empty($type);
		if ($dofilter) {
			foreach ($labels as $k=>$l) {
				// filter by module
				$remove = false;
				if (!empty($module)) {
					if ($l['modulename'] != $module) $remove = true;
				}
				if (!empty($type)) {
					if ($type == 'other') {
						if (in_array($l['type'], array('fields', 'fieldvalues'))) $remove = true;
					} else {
						if ($l['type'] != $type) $remove = true;
					}
				}
				// remove the label
				if ($remove) unset($labels[$k]);
			}
			$labels = array_values($labels);
		}
		
		// apply order
		if (!empty($sortid)) {
			$sign = ($sortord == 'asc' ? '+' : '-');
			usort($labels, function($a, $b) use ($sign, $sortid) {
				return $sign.strcmp($a[$sortid], $b[$sortid]);
			});
		}
		
		// count the totals
		$total = count($labels);
		$totalpages = ceil($total/$pagesize);
		
		// apply limit
		if ($pagesize > 0) {
			$start = ($page-1)*$pagesize;
			$labels = array_slice($labels, $start, $pagesize);
		}
		
		// encapsulate the result in the right format
		foreach ($labels as $k=>&$l) {
			$id = $l['id'];
			unset($l['type'], $l['id'], $l['label']);
			// filter by language
			if (!empty($language)) {
				foreach ($l as $lk=>$lv) {
					if (preg_match('/^[a-z][a-z]_[a-z][a-z]$/', $lk) && $lk != $language) {
						// ok, it's another language label 
						unset($l[$lk]);
					}
				}
			}
			// create the array
			$l = array(
				'id' => $id,
				'cell' => array_values(array_merge(array(''), $l)),
			);
		}
		
		$labels = array(
			'page' => $page,
			'records' => $total,
			'rows' => $labels,
			'total' => $totalpages,
		);
		
		return $labels;
	}
	
	public function editLabel(&$vars, $id, $opts) {
		$langs = $opts['languages'];
		
		if ($id !== '' && count($langs) > 0)  {
			foreach ($langs as $lang=>$trans) {
				$vars['mmaker_labels'][$id][$lang] = $trans;
			}
		}
		return true;
	}
	
	public function installModule($moduleid) {
		$error = false;
		$info = $this->mmutils->getModuleInfo($moduleid);

		// regenerate the scripts if not modified by user
		$userEdit = $this->mmutils->hasUserEdit($moduleid);

		if (empty($info)) {
			$error = getTranslatedString('LBL_NO_RECORD');
		} elseif ($info['installed'] == 1) {
			$error = getTranslatedString('LBL_MODULE_ALREADY_INSTALLED');
		} elseif (!$userEdit) {
			$error = $this->mmgen->generate($moduleid);
		}

		// if no error, install it!
		if (empty($error)) {
			$error = $this->mmutils->installModule($moduleid);
		}
		
		return $error;
	}
	
	public function uninstallModule($moduleid) {
		$error = $this->mmutils->uninstallModule($moduleid);
		return $error;
	}
	
	public function deleteModule($moduleid) {
		$error = false;
	
		$info = $this->mmutils->getModuleInfo($moduleid);

		if (empty($info)) {
			$error = getTranslatedString('LBL_NO_RECORD');
		} elseif ($info['installed'] == 1) {
			$error = getTranslatedString('LBL_MMAKER_CANT_DELETE_INSTALLED');
		} else {
			$r = $this->mmutils->deleteModule($moduleid, false);
		}
		
		return $error;
	}
	
}