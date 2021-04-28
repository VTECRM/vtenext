<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@105127 */

require_once('vtlib/Vtecrm/Utils.php');

/**
 * Class which generates the script
 */
class ModuleMakerGenerator {

	public $fields_per_table = 30;
	
	// file in the templates folder
	public $templates = array(
		'install_script' => 'install.php.txt',
		'uninstall_script' => 'uninstall.php.txt',
	);
		
	public $default_modOps = array(
		//'QuickCreate' => false, // crmv@104956 - not handled in Tools, but with a separate table
		'Import' => false,
		'Export' => false,
		'DuplicatesHandling' => false,
	);
	
	public $default_modSharing = 'Private'; // One of 'Public_ReadWriteDelete', 'Public_ReadOnly', 'Public_ReadWrite', 'Private'

	// TODO: use these
	public $default_block_prop = array(
		'showtitle' => '',
		'visible' => '',
		'increateview' => '',
		'ineditview' => '',
		'indetailview' => '',
	);

	public $templates_dir;
	public $script_dir;
	public $base_module_dir;

	protected $mmsteps = null;
	protected $mmutils = null;
	
	/* temporary variables used during generation */
	protected $moduleId = null;
	protected $moduleName = null;
	public $moduleInfo = null;
	protected $moduleScript = null;
	protected $moduleScriptU = null;	
	protected $moduleDir = null;
	
	public $mainField = null;
	protected $mainTable = null;
	protected $indexId = null;
	protected $cfTable = null;
	protected $tables = null;
	
	protected $mandatoryFields = null;
	protected $allFilterFields = null;
	public $relFilterFields = null; // crmv@69398
	protected $sequencePrefix = '';

	public function __construct($mmutils, $mmsteps) {
		require('vteversion.php'); // crmv@181168
		$this->mmutils = $mmutils;
		$this->mmsteps = $mmsteps;
		$this->templates_dir = $mmutils->templates_dir;
		$this->script_dir = $mmutils->script_dir;
		$this->base_module_dir = 'vtlib/ModuleDir/'.$enterprise_current_version;
	}
	
	public function setModuleName($modulename) {
		$this->moduleName = $modulename;
		$this->moduleDir = 'modules/'.$modulename;
	}
	
	/**
	 * Generates the script for the module
	 */
	public function generate($moduleid) {
		$info = $this->mmutils->getModuleInfo($moduleid);
		
		if (empty($info)) return getTranslatedString('LBL_NO_RECORD');
		if ($info['installed'] == 1) return getTranslatedString('LBL_MMAKER_CANT_DELETE_INSTALLED');
		if (preg_match('/[^a-zA-Z0-9]/', $info['modulename'])) return str_replace('%s', getTranslatedString('VTLIB_LBL_MODULE_NAME', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));
		
		$this->moduleId = $moduleid;
		$this->moduleName = $info['modulename'];
		$this->moduleInfo = $info;
		
		$names = $this->mmutils->getScriptFileNames($moduleid);

		$this->moduleScript = $names['install_script'];
		$this->moduleScriptU = $names['uninstall_script'];
		$this->moduleDir = $names['module_dir'];
		
		if (!is_writable($this->script_dir)) return str_replace('%s', $this->script_dir, getTranslatedString('LBL_DIRECTORY_NOT_WRITEABLE_N'));
		if (!is_readable($this->base_module_dir)) return str_replace('%s', $this->base_module_dir, getTranslatedString('LBL_DIRECTORY_NOT_READABLE_N'));
		
		// clean up
		$this->mmutils->deleteScript($moduleid);

		// change names
		$this->changeFieldNames(); // crmv@82317
		
		// copy dir
		$r = $this->copyBaseFiles();
		if (!$r) return getTranslatedString('LBL_COPY_FILES_ERROR');
		
		// change file names
		$r = $this->alterFileNames();
		if (!$r) return getTranslatedString('LBL_RENAME_FILE_ERROR');
		
		// create the language files
		$r = $this->generateLanguageFiles($info['labels']);
		if (!$r) return getTranslatedString('LBL_ERROR_LANGUAGE_RENAME');
		
		// calculate the table structure
		$r = $this->calculateTableStructure();
		if (!$r) return getTranslatedString('LBL_UT208_GENERIC_ERROR');
		
		// alter the file content
		$r = $this->alterFileContent();
		if (!$r) return getTranslatedString('ERROR_WHILE_EDITING');

		$r = $this->fillScript();
		if (!$r) return getTranslatedString('LBL_ERROR_CREATING_INSTALL_SCRIPT');
		
		return false;
	}
	
	function copyBaseFiles() {
		
		// copy the directory structure
		$r = FSUtils::rcopy($this->base_module_dir, $this->moduleDir);
		if (!$r) return false;
		
		// ensure the existence of the language dir
		@mkdir($this->moduleDir.'/language');
		
		$tpl = $this->templates_dir.'/'.$this->templates['install_script'];
		if (!is_readable($tpl)) return false;
		
		$tpl2 = $this->templates_dir.'/'.$this->templates['uninstall_script'];
		if (!is_readable($tpl2)) return false;
		
		// copy the templates
		if (!empty($this->moduleScript)) {
			$r = copy($tpl, $this->moduleScript);
			if (!$r) return false;
		}
		if (!empty($this->moduleScriptU)) {
			$r = copy($tpl2, $this->moduleScriptU);
			if (!$r) return false;
		}
		
		return true;
	}
	
	function alterFileNames() {
		$filesToCheck = array('ModuleFile.php', 'ModuleFile.js', 'ModuleFileAjax.php');
		
		// change the name of the files according to the module
		foreach ($filesToCheck as $f) {
			$newName = str_replace('ModuleFile', $this->moduleName, $f);
			if ($f != $newName) {
				$srcPath= $this->moduleDir."/$f";
				$destPath= $this->moduleDir."/$newName";
				if (file_exists($srcPath)) {
					$r = rename($srcPath, $destPath);
					if (!$r) return false;
				}
			}
		}
		
		return true;
	}
	
	function alterFileContent() {
		global $table_prefix;
		
		// main file (NOTE: order is important in subsitutions)
		$mainFile = $this->moduleDir.'/'.$this->moduleName.'.php';
		$mainReplace = array(
			// fix newlines
			"\r\n" => "\n",
			'ModuleClass' => $this->moduleName,
			'_payslipcf' => str_replace($table_prefix, '', $this->cfTable),
			'_payslip' => str_replace($table_prefix, '', $this->mainTable),
			'payslipid' => $this->indexId,
			'payslipname' => $this->mainField['fieldname'],
			'Payslip Name' => $this->mainField['fieldlabel'],
		);
		$r = $this->replaceInFile($mainFile, array_keys($mainReplace), array_values($mainReplace), true);
		if (!$r) return false;
		
		// tab names index
		$tabNameIndex = array();
		$tabName = array();
		foreach ($this->tables as $tab=>$fields) {
			$index = $this->indexId;
			if ($tab == $table_prefix.'_crmentity') $index = 'crmid';
			$tabNameIndex[$tab] = $index;
			$tabName[] = $tab;
		}
		
		// now order tabname (crmentity must be the first)
		$idx = array_search($table_prefix.'_crmentity', $tabName);
		if ($idx !== false && $idx > 0) {
			$t = $tabName[$idx];
			$tabName[$idx] = $tabName[0];
			$tabName[0] = $t;
		}
		
		// alter the list fields
		$listFields = array();
		$listFieldsName = array();
		foreach ($this->relFilterFields as $field) { // crmv@69398
			$listFields[$field['fieldlabel']] = array($field['tablename'], $field['columnname']);
			$listFieldsName[$field['fieldlabel']] = $field['fieldname'];
		}
		
		// do the replacements
		$mainReplaceRE = array(
			'/var \$list_fields_name\s*=\s*.*?\);/s' => 'var $list_fields_name = '.var_export($listFieldsName, true).";",
			'/\$this->list_fields\s*=\s*.*?\);/s' => '$this->list_fields = '.var_export($listFields, true).";",
			'/\$this->tab_name\s*=\s*.*?\);/s' => '$this->tab_name = '.var_export($tabName, true).";",
			'/\$this->tab_name_index\s*=\s*.*?\);/s' => '$this->tab_name_index = '.var_export($tabNameIndex, true).";",
		);
		$r = $this->replaceInFile($mainFile, array_keys($mainReplaceRE), array_values($mainReplaceRE));
		if (!$r) return false;
		
		return true;
	}
	
	function generateLanguageFiles($labels) {
		$langs = array();
		
		// divide them by language
		foreach ($labels as $l) {
			if ($l['modulename'] == $this->moduleName) {
				$label = $l['label'];
				foreach ($l as $k=>$trans) {
					if (preg_match('/^[a-z][a-z]_[a-z][a-z]$/', $k)) {
						$langs[$k][$label] = $trans;
					}
				}
			}
		}
		
		// write them in the files
		foreach ($langs as $lang=>$trans) {
			if (is_array($trans) && count($trans) > 0) {
				$file = $this->moduleDir."/language/{$lang}.lang.php";
				$buffer = "<?php\n\n";
				$buffer .= "/* Automatically generated translations for module {$this->moduleName} */\n\n";
				$buffer .= '$mod_strings = '.var_export($trans, true).";\n";
				$buffer .= "\n";
				$r = file_put_contents($file, $buffer);
				if ($r === false) return $r;
			}
		}
		
		return true;
	}
	
	/**
	 * Calculate the tables, indexname, ...
	 */
	function calculateTableStructure($skip_check_table=false) {
		global $table_prefix;
		
		$crmentityFields = array('creator', 'assigned_user_id', 'modifiedtime', 'createdtime'); // crmv@150773
		$crmentityColumns = array(
			'assigned_user_id' => 'smownerid',
			'smcreatorid' => 'smcreatorid', 
			'modifiedtime' => 'modifiedtime',
			'createdtime' => 'createdtime',
		);
	
		// the index for the tables
		$this->indexId = strtolower($this->moduleName).'id';
		
		// the main table
		$propTableBase = $table_prefix.'_'.strtolower($this->moduleName);
		$propTable = $propTableBase;
		if (!$skip_check_table) {
			$i = 1;
			while (Vtecrm_Utils::CheckTable($propTable) && $i <= 5) {
				$propTable = $propTableBase.$i;
				++$i;
			}
			if ($i > 5) return false; // unable to find a suitable name for the table
		}
		$this->mainTable = $propTable;
		
		// the cf table
		$this->cfTable = $this->mainTable.'cf';
		
		// prepare the other tables
		$this->tables = array();

		$lastTable = $this->mainTable;
		$tableIndex = 2;
		$tableCount = array($lastTable => 0);
		
		$allFilter = $this->moduleInfo['filters'][0];
		$relFilter = $this->moduleInfo['filters'][1];	// crmv@69398
		if (empty($relFilter)) $relFilter = $allFilter;

		foreach ($this->moduleInfo['fields'] as &$block) {
			if (is_array($block['fields'])) {
				foreach ($block['fields'] as &$field) {
					// fix the fieldname
					// TODO
					// choose the right table for the field
					if (in_array($field['fieldname'], $crmentityFields)) {
						$table = $table_prefix.'_crmentity';
						$column = $crmentityColumns[$field['fieldname']];
					} else {
						if (!$skip_check_table && $tableCount[$lastTable] >= $this->fields_per_table) {
							// new table
							$lastTable = $this->mainTable.($tableIndex++);
						}
						$table = $lastTable;
						$column = $field['fieldname'];
					}
					$field['tablename'] = $table;
					$field['columnname'] = $column;
					$tableCount[$table]++;
					$this->tables[$table][] = $field['fieldname'];
					
					// do also some other checks
					if ($field['fieldname'] == 'vcf_1' || preg_match('/^vcf_[0-9]+_1$/',$field['fieldname'])) $this->mainField = $field; // crmv@82317
					if ($field['mandatory'] == 1) $this->mandatoryFields[] = $field['fieldname'];
					if (in_array($field['fieldname'], $allFilter['columns'])) $this->allFilterFields[$field['fieldname']] = $field;
					if (in_array($field['fieldname'], $relFilter['columns'])) $this->relFilterFields[$field['fieldname']] = $field; // crmv@69398
					if ($field['uitype'] == 4) $this->sequencePrefix = $field['autoprefix'];
				}
			}
		}

		//crmv@69568
		// now sort the "all" and related filter
		if (is_array($allFilter['columns'])) {
			$this->allFilterFields = $this->sortArrayByArray($this->allFilterFields, $allFilter['columns']);
		}
		if (is_array($relFilter['columns'])) {
			$this->relFilterFields = $this->sortArrayByArray($this->relFilterFields, $relFilter['columns']);
		}
		//crmv@69568e
		
		if (empty($this->mainField)) return "No main field specified";
		
		// now put the crmentity first
		if (array_key_exists($table_prefix.'_crmentity', $this->tables)) {
			$first = array($table_prefix.'_crmentity' => $this->tables[$table_prefix.'_crmentity']);
			unset($this->tables[$table_prefix.'_crmentity']);
			$this->tables = array_merge($first, $this->tables);
		}
		
		// add the cf table
		if ($this->cfTable) {
			$this->tables[$this->cfTable] = array();
		}
		
		return true;
	}
	
	//crmv@69568
	protected function sortArrayByArray(array $toSort, array $sortByValuesAsKeys) {
		$commonKeysInOrder = array_intersect_key(array_flip($sortByValuesAsKeys), $toSort);
		$commonKeysWithValue = array_intersect_key($toSort, $commonKeysInOrder);
		$sorted = array_merge($commonKeysInOrder, $commonKeysWithValue);
		return $sorted;
	}
	//crmv@69568e

	// crmv@82317
	// alter the fieldnames to be unique between modules
	protected function changeFieldNames() {
		$fieldMapping = array();
		
		// fields
		if (is_array($this->moduleInfo['fields'])) {
			foreach ($this->moduleInfo['fields'] as $k=>$block) {
				if (is_array($block['fields'])) {
					foreach ($block['fields'] as $j=>$field) {
						$name = $field['fieldname'];
						$matches = null;
						if (preg_match('/^vcf_([0-9]+)/', $name, $matches)) {
							$newname = 'vcf_'.$this->moduleId.'_'.$matches[1];
							$this->moduleInfo['fields'][$k]['fields'][$j]['fieldname'] = $newname;
							$fieldMapping[$name] = $newname;
						}
					}
				}
			}
		}
		
		//filters
		if (is_array($this->moduleInfo['filters'])) {
			foreach ($this->moduleInfo['filters'] as $k=>$filter) {
				if (is_array($filter['columns'])) {
					foreach ($filter['columns'] as $j=>$col) {
						if (array_key_exists($col, $fieldMapping)) {
							$this->moduleInfo['filters'][$k]['columns'][$j] = $fieldMapping[$col];
						}
					}
				}
			}
		}
	}
	// crmv@82317e
	
	protected function fillScript() {
		global $table_prefix;
		
		require('vteversion.php'); // crmv@181168
		
		$createTables = array();
		foreach ($this->tables as $tname=>$fields) {
			if ($tname != $table_prefix.'_crmentity') $createTables[$tname] = $this->indexId;
		}
		$createTables[$this->cfTable] = $this->indexId;
		
		$modOperations = array();
		$keyOpts = array(
			'enable_quickcreate' => 'QuickCreate',
			'enable_import' => 'Import',
			'enable_export' => 'Export',
			'enable_dupcheck' => 'DuplicatesHandling',
		);
		
		foreach ($keyOpts as $key=>$oname) {
			if (isset($this->moduleInfo['moduleinfo'][$key])) {
				$useit = !!($this->moduleInfo['moduleinfo'][$key]);
			} else {
				$useit = $this->default_modOps[$oname];
			}
			if ($useit) $modOperations[] = $oname;
		}
		
		// panels
		$panels = array();
		if (is_array($this->moduleInfo['panels'])) {
			foreach ($this->moduleInfo['panels'] as $k=>$panel) {
				$label = $panel['panellabel'];
				$panels[$label] = array('module' => $this->moduleName, 'label' => $label);
			}
		} else {
			// no panels? add a default one!!
			$panels['LBL_TAB_MAIN'] = array('module' => $this->moduleName, 'label' => 'LBL_TAB_MAIN');
		}
		
		// blocks and fields
		$blocks = array();
		$fields = array();
		if (is_array($this->moduleInfo['fields'])) {
			foreach ($this->moduleInfo['fields'] as $k=>$block) {
				$label = $block['blocklabel'];
				$panelno = $block['panelno'];
				$panelname = $this->moduleInfo['panels'][$panelno]['panellabel'] ?: 'LBL_TAB_MAIN';
				$blocks[$label] = array('module' => $this->moduleName, 'label' => $label, 'panel' => $panelname);
				
				// now cycle the fields
				if (is_array($block['fields'])) {
					foreach ($block['fields'] as $field) {
						$key = $field['fieldname'];
						$cfield = array();

						// fix the field format
						$cfield['module'] = $this->moduleName;
						$cfield['block'] = $label;
						$cfield['name'] = $key;
						$cfield['uitype'] = intval($field['uitype']);
						
						$cfield['typeofdata'] = $this->getTODForField($field);
						if ($field['mandatory']) {
							$cfield['typeofdata'] = $this->makeTODMandatory($cfield['typeofdata']);
						}
						
						$cfield['columntype'] = $this->getColumnTypeForField($field);
						
						if (isset($field['fieldlabel'])) {
							$cfield['label'] = $field['fieldlabel'];
						} else {
							$cfield['label'] = $key;
						}
						
						if (isset($field['tablename'])) {
							$cfield['table'] = $field['tablename'];
						}
						
						if (isset($field['columnname']) && $field['columnname'] != $key) {
							$cfield['column'] = $field['columnname'];
						}
						
						if (isset($field['displaytype'])) {
							$cfield['displaytype'] = intval($field['displaytype']);
						}
						
						if (isset($field['readonly']) && $field['readonly'] != 1) {
							$cfield['readonly'] = intval($field['readonly']);
						}
						
						if (isset($field['quickcreate'])) {
							$cfield['quickcreate'] = intval($field['quickcreate']);
						}
						
						if (isset($field['masseditable'])) {
							$cfield['masseditable'] = intval($field['masseditable']);
						}

						if (isset($field['picklistvalues'])) {
							$cfield['picklist'] = array_map('trim', array_unique(explode("\n", $field['picklistvalues'])));	//crmv@113771
						}
						
						if (isset($field['relatedmods'])) {
							$relmods = array_map('trim', array_filter(explode(',', $field['relatedmods'])));
							$cfield['relatedModules'] = $relmods;
						}
						
						// store them
						$fields[$key] = $cfield;
					}
				}
			}
		}
		
		if ($this->moduleInfo['moduleinfo']['mmaker_inventory']) {
			// add the block for the related products
			$blocks['LBL_RELATED_PRODUCTS'] = array('module' => $this->moduleName, 'label' => 'LBL_RELATED_PRODUCTS', 'panel' => 'LBL_TAB_MAIN');
			$firstBlock = reset($blocks);
			$firstBlock = $firstBlock['label'];
			$fields['hdnSubTotal'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnSubTotal',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'N(25.3)',
				'label' => 'Sub Total',
				'table' => $this->mainTable,
				'column' => 'subtotal',
				'displaytype' => 3,
			);
			$fields['hdnGrandTotal'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnGrandTotal',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'N(25.3)',
				'label' => 'Total',
				'table' => $this->mainTable,
				'column' => 'total',
				'displaytype' => 3,
			);
			$fields['hdnTaxType'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnTaxType',
				'uitype' => 16,
				'typeofdata' => 'V~O',
				'columntype' => 'C(25)',
				'label' => 'Tax Type',
				'table' => $this->mainTable,
				'column' => 'taxtype',
				'displaytype' => 3,
			);
			$fields['hdnDiscountPercent'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnDiscountPercent',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'C(127)',
				'label' => 'Discount Percent',
				'table' => $this->mainTable,
				'column' => 'discount_percent',
				'helpinfo' => 'LBL_DISCOUNT_PERCENT_INFO',
				'displaytype' => 3,
			);
			$fields['hdnDiscountAmount'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnDiscountAmount',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'C(127)',
				'label' => 'Discount Amount',
				'table' => $this->mainTable,
				'column' => 'discount_amount',
				'displaytype' => 3,
			);
			$fields['hdnS_H_Amount'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'hdnS_H_Amount',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'N(25.3)',
				'label' => 'S&H Amount',
				'table' => $this->mainTable,
				'column' => 's_h_amount',
				'displaytype' => 3,
			);
			$fields['txtAdjustment'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'txtAdjustment',
				'uitype' => 1,
				'typeofdata' => 'NN~O',
				'columntype' => 'N(25.3)',
				'label' => 'Adjustment',
				'table' => $this->mainTable,
				'column' => 'adjustment',
				'displaytype' => 3,
			);
			$fields['currency_id'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'currency_id',
				'uitype' => 117,
				'typeofdata' => 'I~O',
				'columntype' => 'I(19)',
				'label' => 'Currency',
				'table' => $this->mainTable,
				'column' => 'currency_id',
				'displaytype' => 3,
			);
			$fields['conversion_rate'] = array(
				'module' => $this->moduleName,
				'block' => $firstBlock,
				'name' => 'conversion_rate',
				'uitype' => 1,
				'typeofdata' => 'N~O',
				'columntype' => 'N(10.3)',
				'label' => 'Conversion Rate',
				'table' => $this->mainTable,
				'column' => 'conversion_rate',
				'displaytype' => 3,
			);
		}
		
		// filters
		$filters = array();
		if (is_array($this->moduleInfo['filters'])) {
			foreach ($this->moduleInfo['filters'] as $k=>$filter) {
				if ($filter['all'] || empty($filter['name'])) {
					$name = 'All';
					$isdefault = true;
					$inmobile = true; // crmv@174922
				} else {
					$name = $filter['name'];
					$isdefault = false;
					$inmobile = false; // crmv@174922
				}
				$key = $name;
				// crmv@69398
				if ($isdefault) {
					$filt = array(
						'module' => $this->moduleName,
						'name' => $name,
						'isdefault' => $isdefault,
						'inmobile' => $inmobile, // crmv@174922
						'fields' => $filter['columns'],
					);
					$filters[$key] = $filt;
				}
				// crmv@69398e
				// crmv@115811 - first filter only
				break;
			}
		}
		
		// labels
		$labels = array();
		if (is_array($this->moduleInfo['labels'])) {
			foreach ($this->moduleInfo['labels'] as $label) {
				if ($label['modulename'] != $this->moduleName) {
					$langs = $label;
					unset($langs['modulename'], $langs['type'], $langs['label']);
					foreach ($langs as $lang=>$trans) {
						$labels[$label['modulename']][$lang][$label['label']] = $trans;
					}
				}
			}
		}
		
		// other fields and relations
		$otherfields = array();
		$relations = array();
		$addRelations = array();
		if (is_array($this->moduleInfo['relations'])) {
			foreach ($this->moduleInfo['relations'] as $relation) {
				if ($relation['type'] == '1ton') {
					if ($relation['module'] == 'Calendar') {
						// this is an existing field
						$addRelations[] = array(
							'module' => $relation['module'],
							'actions' => array('ADD'),
							'function' => 'get_activities',
							'field' => $relation['field'] ?: 'parent_id',
						);
					} else {
						$key = $relation['module'].'_'.$this->indexId;
						$otherfields[$key] = array(
							'module' => $relation['module'],
							'block' => $relation['block'],
							'name' => $this->indexId,
							'columnname' => $this->indexId,
							'uitype' => 10,
							'label' => $relation['field'],
							'relatedModules' => array($this->moduleName),
						);
					}
				} elseif ($relation['type'] == 'nton') {
					// default function and actions
					$f = 'get_related_list';
					$f2 = "";
					$actions = array('ADD', 'SELECT');
					// special cases
					if ($relation['module'] == 'Messages') {
						$f = 'get_messages_list';
						$actions = array('ADD');
					} elseif ($relation['module'] == 'Documents') {
						$f = 'get_attachments';
						$f2 = 'get_documents_dependents_list';
					}
					$saverelation = array(
						'module' => $relation['module'],
						'actions' => $actions,
						'function' => $f,
					);
					if (!empty($f2)) {
						$saverelation['reverse_function'] = $f2;
					}
					$relations[] = $saverelation;
				}
			}
		}

		$values = array(
			'vars' => array(
				array(
					'name' => 'VTETARGETREVISION',
					'type' => 'int',
					'value' => $enterprise_current_build
				),
				array(
					'name' => 'VTETARGETVERSION',
					'type' => 'string',
					'value' => $enterprise_current_version
				),
				array(
					'name' => 'MODULENAME',
					'type' => 'string',
					'value' => $this->moduleName,
				),
				array(
					'name' => 'DEFAULTSHARING',
					'type' => 'string',
					'value' => $this->moduleInfo['moduleinfo']['sharing_access'] ?: $this->default_modSharing,
				),
				array(
					'name' => 'MAINFIELD',
					'type' => 'string',
					'value' => $this->mainField['fieldname'],
				),
				array(
					'name' => 'MAINTABLE',
					'type' => 'string',
					'value' => $this->mainTable,
				),
				array(
					'name' => 'TABLEID',
					'type' => 'string',
					'value' => $this->indexId,
				),
				array(
					'name' => 'MODULEFILESDIR',
					'type' => 'string',
					'value' => $this->moduleDir,
				),
				array(
					'name' => 'SEQUENCEPREFIX',
					'type' => 'string',
					'value' => $this->sequencePrefix,
				),
				array(
					'name' => 'AREAID',
					'type' => 'int',
					'value' => $this->moduleInfo['moduleinfo']['mmaker_areaid'],
				),
				array(
					'name' => 'ISINVENTORY',
					'type' => 'bool',
					'value' => $this->moduleInfo['moduleinfo']['mmaker_inventory'],
				),
				array(
					'name' => 'CREATETABLES',
					'type' => 'array',
					'value' => $createTables,
				),
				array(
					'name' => 'ALLOPERATIONS',
					'type' => 'array',
					'value' => array_keys($this->default_modOps),
				),
				array(
					'name' => 'MODOPERATIONS',
					'type' => 'array',
					'value' => $modOperations,
				),
				array(
					'name' => 'PANELS',
					'type' => 'compact_array',
					'value' => $panels,
				),
				array(
					'name' => 'BLOCKS',
					'type' => 'compact_array',
					'value' => $blocks,
				),
				array(
					'name' => 'FIELDS',
					'type' => 'compact_array',
					'value' => $fields,
				),
				array(
					'name' => 'OTHERFIELDS',
					'type' => 'compact_array',
					'value' => $otherfields,
				),
				array(
					'name' => 'FILTERS',
					'type' => 'compact_array',
					'value' => $filters,
				),
				array(
					'name' => 'RELATIONS',
					'type' => 'compact_array',
					'value' => $relations,
				),
				array(
					'name' => 'ADDRELATIONS',
					'type' => 'compact_array',
					'value' => $addRelations,
				),
				array(
					'name' => 'LANGUAGES',
					'type' => 'array',
					'value' => $labels,
				),
			),
		);
		
		// apply the substitutions
		$tpl = new ModuleMakerScriptTemplate($this->moduleScript);
		$tpl->setValues($values);
		$tpl->process();
		$tpl->save();
		
		
		// and now the removal script
		
		// remove extra values from other fields
		if (is_array($otherfields)) {
			foreach ($otherfields as &$f) {
				$f = array('module' => $f['module'], 'name' => $f['name']);
			}
		}
		
		$values = array(
			'vars' => array(
				array(
					'name' => 'MODULENAME',
					'type' => 'string',
					'value' => $this->moduleName,
				),
				array(
					'name' => 'OTHERFIELDS',
					'type' => 'compact_array',
					'value' => $otherfields,
				),
				array(
					'name' => 'ADDRELATIONS',
					'type' => 'compact_array',
					'value' => $addRelations,
				),
				array(
					'name' => 'LANGUAGES',
					'type' => 'array',
					'value' => $labels,
				),
			),
		);
		
		$tpl = new ModuleMakerScriptTemplate($this->moduleScriptU);
		$tpl->setValues($values);
		$tpl->process();
		$tpl->save();

		return true;
	}
	
	public function getTODForField($field) { // crmv@102879
		$tod = $field['typeofdata'];
		if (empty($tod)) {
			$uitype = $field['uitype'];
			if ($uitype == 2 || $uitype == 20 || $uitype == 22) {
				$tod = 'V~M';
			} elseif ($uitype == 13) {
				$tod = 'E~O';
			} elseif ($uitype == 5) {
				$tod = 'D~O';
			} elseif ($uitype == 7) {
				if ($field['decimals'] > 0) {
					$tod = 'N~O';
				} else {
					$tod = 'I~O';
				}
			} elseif ($uitype == 9) {
				$tod = 'N~O~2~2';
			} elseif ($uitype == 71) {
				$tod = 'N~O';
			} elseif ($uitype == 10) {
				$tod = 'I~O';
			} elseif ($uitype == 53) {
				$tod = 'I~M';
			} elseif ($uitype == 56) {
				$tod = 'C~O';
			} elseif ($uitype == 70 || $uitype == 73) {	//crmv@146187
				$tod = 'T~O';
			} else {
				$tod = 'V~O';
			}
			if ($field['allow_negative'] && strpos($tod,'N~') === 0) $tod = 'N'.$tod; //crmv@162803
		}
		return $tod;
	}
	
	public function getColumnTypeForField($field) {
		$ct = "";
		$uitype = $field['uitype'];
		if ($uitype >= 19 && $uitype <= 22) {
			$ct = 'X';
		} elseif ($uitype == 13) {
			$ct = "C(100)";
		} elseif ($uitype == 5) {
			$ct = 'D';
		} elseif ($uitype == 7) {
			if (isset($field['length'])) {
				$len = max(3, min(intval($field['length']), 30));
			} else {
				$len = 25;
			}
			if ($field['decimals'] > 0) {
				$dec = max(1, min(intval($field['decimals']), $len-2));	// crmv@73971
				$ct = "N({$len}.{$dec})";
			} else {
				$ct = "I({$len})";
			}
		} elseif ($uitype == 71) {
			$ct = 'N(25.3)';
		} elseif ($uitype == 9) {
			$ct = 'N(7.3)';
		} elseif ($uitype == 53 || $uitype == 10) {
			$ct = 'I(19)';
		} elseif ($uitype == 56) {
			$ct = 'I(1)';
		} elseif ($uitype == 70) {
			$ct = 'T';
		//crmv@146187
		} elseif ($uitype == 73) {
			$ct = 'I(5)';
		//crmv@146187e
		} else {
			if (isset($field['length'])) {
				$len = max(1, min(intval($field['length']), 4000));
			} else {
				$len = 200;
			}
			$ct = "C($len)";
		}
		return $ct;
	}
	
	public function makeTODMandatory($tod) {
		if (!empty($tod)) {
			$pieces = explode('~', $tod);
			if (count($pieces) >= 2) $pieces[1] = 'M';
			$tod = implode('~', $pieces);
		}
		return $tod;
	}
	public function makeTODOptional($tod) {
		if (!empty($tod)) {
			$pieces = explode('~', $tod);
			if (count($pieces) >= 2) $pieces[1] = 'O';
			$tod = implode('~', $pieces);
		}
		return $tod;
	}	
	
	protected function replaceInFile($file, $regexp, $replacement, $plainText = false) {
		
		// read file
		$buffer = file_get_contents($file);
		if ($buffer === false) return false;
			
		if ($plainText) {
			$buffer = str_replace($regexp, $replacement, $buffer);
		} else {
			$buffer = preg_replace($regexp, $replacement, $buffer);
		}
		
		// write file
		$r = file_put_contents($file, $buffer);
		if ($r === false) return false;
		
		return true;
	}
	
}


/**
 * Class to handle basic script template files
 */
class ModuleMakerScriptTemplate {

	protected $file = null;
	protected $buffer = null;
	protected $values = array();

	public function __construct($file) {
		$this->file = $file;
		$this->open();
	}
	
	public function open() {
		$this->buffer = file_get_contents($this->file);
	}
	
	public function save() {
		file_put_contents($this->file, $this->buffer);
	}
	
	public function setValues($values) {
		$this->values = $values;
	}
	
	public function process() {
		$this->delComments();
		foreach ($this->values as $vtype=>$data) {
			if ($vtype == 'vars') {
				$this->replacePhpVars($data);
			}
		}
		return true;
	}
	
	// delete comments marked with the DEL tag
	protected function delComments() {
		$this->buffer = preg_replace('#/\*@ DEL.*?@\*/#s', '', $this->buffer);
	}
	
	protected function replaceComments($vars) {
		// TODO
	}
	
	protected function replacePhpVars($vars) {
		$repl = array();
		foreach ($vars as $vinfo) {
			$key = "#\\\$TPL_{$vinfo['name']}#";
			
			if ($vinfo['type'] == 'int') {
				$value = intval($vinfo['value']);
				
			} elseif ($vinfo['type'] == 'bool') {
				$value = ($vinfo['value'] ? 'true' : 'false');
				
			} elseif ($vinfo['type'] == 'string') {
				$value = "'".addslashes($vinfo['value'])."'";
				
			} elseif ($vinfo['type'] == 'array') {
				$value = var_export($vinfo['value'], true);
				
			} elseif ($vinfo['type'] == 'compact_array') {
				$value = $this->var_export_compact($vinfo['value']);
				
			} else {
				$value = $vinfo['value'];
			}
			
			$repl[$key] = $value;
		}

		if (count($repl) > 0) {
			$this->buffer = preg_replace(array_keys($repl), array_values($repl), $this->buffer);
		}
	}
	
	protected function var_export_compact($var) {
		$v = var_export($var, true);
		
		if (is_array($var)) {
			$prefix = substr($v, 0, 8);
			$postfix = str_replace(array("\n", '),'), array("", "),\n"), substr($v, 8));
			$postfix = preg_replace(array('/ {2,}/', '/^/m', '/=>\s*/'), array(' ', "\t", "=>\t"), $postfix);
			$v = $prefix . $postfix;
		}
		
		return $v;
	}
}