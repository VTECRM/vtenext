<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 */


class DataImporterSteps {

	
	protected $diutils = null;
	
	public function __construct($diutils) {
		$this->diutils = $diutils;
	}

	/**
	 * Extract the array of variables from the request, to be stored for the step
	 */
	public function extractStepVars(&$request) {
		$vars = array();
		if (is_array($request)) {
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 8) == 'dimport_') {
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
		VteSession::setArray(array('dimport', 'step_vars', $step), $this->extractStepVars($request));
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
		$module = $vars['dimport_module'];
		
		if (empty($module)) return getTranslatedString('LBL_INVALID_MODULE');
		
		if ($module == 'ProductRows') {
			// check if there are already other imports for an inventory module and product
			$count = 0;
			$list = $this->diutils->getList();
			foreach ($list as $import) {
				$imod = $import['module'];
				if (isInventoryModule($imod) || isProductModule($imod)) ++$count;
			}
			if ($count < 2) return getTranslatedString('LBL_CANT_IMPORT_PRODUCT_ROWS');
		} else {
			$tabid = getTabid($module);
			if (empty($tabid)) return getTranslatedString('LBL_INVALID_MODULE');
		}
		
		return null;
	}
	
	protected function validateStep2($vars) {
		$validSrc = array('database', 'csv');
		$src = $vars['dimport_sourcetype'];
		if (!in_array($src, $validSrc)) return getTranslatedString('LBL_INVALID_SOURCETYPE');
		return null;
	}
	
	protected function validateStep3($vars) {
		$vars2 = $this->getStepVars(2);
		
		$type =  $vars2['dimport_sourcetype'];
		
		if ($type == 'database') {
			return $this->validateStep3_database($vars);
		} elseif ($type == 'csv') {
			return $this->validateStep3_csv($vars);
		}
		
		return null;
	}
	
	protected function validateStep3_csv($vars) {
		$file = $vars['dimport_csvpath'];
		
		// basic sanitization
		$file = str_replace('..', '', $file);
		
		// emptyness check
		if (empty($file)) return getTranslatedString('LBL_DIMPORT_CSVPATH').' '.getTranslatedString('CANNOT_BE_EMPTY');
		
		// absolute path
		if ($file[0] == '/') return getTranslatedString('LBL_CSVPATH_MUST_NOT_BE_ABSOLUTE');
		
		// no hidden files
		if ($file[0] == '.') return "Invalid file name";

		$file = $this->diutils->getOneCSVFile($file);
		if (!$file) return getTranslatedString('LBL_NO_MATCHING_FILES');

		if (substr($this->diutils->import_folder, -1) != '/') $this->diutils->import_folder .= '/';
		$path = $this->diutils->import_folder . $file;
		
		// check extension
		$epos = strrpos($file, '.');
		if ($epos !== false) {
			$ext = strtolower(substr($file, $epos+1));
		} else {
			$ext = '';
		}
		if (is_array($this->diutils->import_extensions) && count($this->diutils->import_extensions) > 0) {
			if (!in_array($ext, $this->diutils->import_extensions)) return getTranslatedString('LBL_INVALID_FILE_EXTENSION');
		}
	
		if (!is_file($path)) return getTranslatedString('ERR_FILE_DOESNT_EXIST', 'Import');
		if (!is_readable($path) || filesize($path) == 0) return getTranslatedString('LBL_FILE_NOT_READABLE');

		return null;
	}
	
	protected function validateStep3_database($vars) {
		$dbtype = $vars['dimport_dbtype'];
		$dbhost = trim($vars['dimport_dbhost']);
		$dbport = trim($vars['dimport_dbport']);
		$dbuser = trim($vars['dimport_dbuser']);
		$dbpass = $vars['dimport_dbpass'];
		$dbname = trim($vars['dimport_dbname']);
		
		$validDbs = $this->diutils->getSupportedDbType();
		
		if (empty($dbtype)) return getTranslatedString('LBL_DATABASE_TYPE').' '.getTranslatedString('CANNOT_BE_EMPTY');
		if (empty($dbhost)) return getTranslatedString('LBL_HOSTNAME').' '.getTranslatedString('CANNOT_BE_EMPTY');
		if (empty($dbport)) return getTranslatedString('LBL_PROXY_PORT').' '.getTranslatedString('CANNOT_BE_EMPTY');
		if (empty($dbuser)) return getTranslatedString('LBL_LIST_USER_NAME').' '.getTranslatedString('CANNOT_BE_EMPTY');
		if (empty($dbname)) return getTranslatedString('LBL_DATABASE_NAME').' '.getTranslatedString('CANNOT_BE_EMPTY');
		
		if (!array_key_exists($dbtype, $validDbs)) return getTranslatedString('INVALID').' '.getTranslatedString('LBL_DATABASE_TYPE');
		if (preg_match('/[^0-9]/', $dbport)) return getTranslatedString('INVALID').' '.getTranslatedString('LBL_PROXY_PORT');
		
		// now check connection
		try {
			$importRow = $this->getAllVarsForSave();
			$importRow['srcinfo'] = $vars; // crmv@193141
			$adbCheck = $this->diutils->connectToExternalDb($importRow);
			if (!$adbCheck) return getTranslatedString('LBL_UNABLE_TO_CONNECT_TO_DB');
		} catch (Exception $e) {
			return getTranslatedString('LBL_UNABLE_TO_CONNECT_TO_DB').' : '.$e->getMessage();
		}

		return null;
	}

	// crmv@111926
	protected function validateStep5($vars) {
		$CU = CRMVUtils::getInstance();
		$r = $CU->checkMaxInputVars('form_var_count');
		if (!$r) {
			return getTranslatedString('LBL_TOO_MANY_INPUT_VARS');
		}
		return null;
	}
	// crmv@111926e
	
	public function retrieveAllTables() {
		$importRow = $this->getAllVarsForSave();
		$adbCheck = $this->diutils->connectToExternalDb($importRow);
		
		$list = $adbCheck->get_tables();
		sort($list); // crmv@155585
		return $list;
	}
	
	protected function validateStep4($vars) {
		$table = $vars['dimport_dbtable'];
		$query = trim($vars['dimport_dbquery']);
		
		$canDoQuery = $this->diutils->canEditQuery();
		if ($canDoQuery) {
			if (empty($table) && empty($query)) return getTranslatedString('LBL_IMPORT_TABLE').', Query '.getTranslatedString('CANNOT_BE_EMPTY');
			
			$query = str_replace(array("\n","\r"), ' ', $query); //crmv@198391
			if (!empty($query) && !preg_match('/^select.*from.*/im', $query)) return getTranslatedString('LBL_INVALID_QUERY'); // crmv@77830
		} else {
			if (empty($table)) return getTranslatedString('LBL_IMPORT_TABLE').' '.getTranslatedString('CANNOT_BE_EMPTY');
		}
		
		return null;
	}
	
	public function mergeMapping($mapping) {
		$defaults = $this->generateMapping();
		
		if (!is_array($defaults)) return $defaults;
		
		if (is_array($mapping)) {
			foreach ($defaults as $k=>$dm) {
				if (array_key_exists($k, $mapping)) {
					$mapping[$k]['srcvalue'] = $dm['srcvalue'];
					$defaults[$k] = $mapping[$k];
					// crmv@105144
					if (empty($defaults[$k]['label'])) {
						$defaults[$k]['label'] = $this->fixSpaceNames($k, false);
					}
					// crmv@105144e
				}
			}
		}
		
		return $defaults;
	}
	
	public function mergeDefaults($olddefaults) {
		// use the saved ones
		return $olddefaults;
	}
	
	public function generateMapping() {
		$importRow = $this->getAllVarsForSave();
		$cols = $this->diutils->getImportColumns($importRow);
		if (!is_array($cols)) return $cols;

		$firstRow = $this->diutils->getFirstImportRow($importRow);
		if (!is_array($firstRow)) return $firstRow;

		$fields = $this->diutils->getMappableFields($importRow);
		if (!is_array($fields)) return $fields;

		// now merge all
		$mapping = array();
		foreach ($cols as $colname) {
			$formatValue = '';
			$fname = $this->guessMapField($colname, $fields, $firstRow[$colname], 3);
			$format = $this->guessMapFormat($colname, $fields[$fname], $firstRow[$colname], $formatValue);
			$default = $this->guessMapDefault($colname, $fields[$fname], $firstRow[$colname]);
			$reference = $this->guessReference($colname, $fields[$fname], $firstRow[$colname]);
			$mapping[$colname] = array(
				'srccol' => $colname,
				'label' => $this->fixSpaceNames($colname, false), // crmv@105144
				'srcvalue' => $firstRow[$colname],
				'srcformat' => $format,
				'srcformatval' => $formatValue,
				'formula' => '',
				'field' => $fname,
				'reference' => $reference,
				'default' => $default,
				'usedefault' => $fields[$fname] ? $fields[$fname]['mandatory'] : false,
			);
		}
		
		// TODO:merge with existing values

		return $mapping;
	}
	
	protected function guessMapFormat($column, $field, $content, &$formatValue = null) {
		$format = null;
		
		if ($field) {
			$uitype = $field['uitype'];
			$type = $field['type'];
		}

		if ($type == 'email') return 'EMAIL_REGEX';
		if ($type == 'url' || $uitype == 207) return 'URL_REGEX';
		if ($type == 'phone' || $uitype == 1013) return 'PHONE_REGEX';
		if ($type == 'boolean') {
			if ($content === 0 || $content === 1 || $content === '0' || $content === '1') return 'BOOL_INT_REGEX';
			return 'BOOL_NULL_REGEX';
		}
		if ($type == 'datetime') {
			$format = 'DATE_TIME_REGEX';
			if (!empty($content)) {
				$formatValue = $this->guessDateFormat($content);
				if (empty($formatValue)) $formatValue = 'Y-m-d H:i:s';
			}
		}
		if ($type == 'date') {
			$format = 'DATE_TIME_REGEX';
			if (!empty($content)) {
				$formatValue = $this->guessDateFormat($content);
				if (empty($formatValue)) $formatValue = 'Y-m-d';
			}
		}
		
		// TODO: valuta
		
		return $format;
	}
	
	protected function guessDateFormat($date) {
		$format = null;
		$separators = array(
			' ' => array('', 'T'),
			'-' => array('', '.', '/', '\\'),
			':' => array('', '.'),
		);
		$tries = array(
			'Y-m-d H:i:sO',
			'Y-m-d H:i:sP',
			'Y-m-d H:i:s',
			'Y-m-d H:i:s',
			'Y-m-d H:i',
			'Y-m-d',
			'd-m-Y H:i:s',
			'd-m-Y H:i',
			'd-m-Y',
			'm-d-Y H:i:s',
			'm-d-Y H:i',
			'm-d-Y',
			'j-M-Y',
			'U',
			'c',
			'r',
		);
		
		$date = trim($date);
		$out = strtotime($date);
		if ($out === false) return $format;
		
		// try various formats to find the correct one
		foreach ($tries as $fmt) {
			$new = date($fmt, $out);
			if ($new === $date) return $fmt;
			
			// now try various combination of separators
			if (strpos($new, ' ') !== false) {
				foreach ($separators[' '] as $sep1) {
					$fmt2 = str_replace(' ', $sep1, $fmt); 
					$new = date($fmt2, $out);
					if ($new === $date) return $fmt2;
					
					if (strpos($new, '-') !== false) {
						foreach ($separators['-'] as $sep2) {
							$fmt2 = str_replace(' ', $sep2, $fmt2); 
							$new = date($fmt2, $out);
							if ($new === $date) return $fmt2;
							
							if (strpos($new, ':') !== false) {
								foreach ($separators[':'] as $sep3) {
									$fmt2 = str_replace(' ', $sep3, $fmt2); 
									$new = date($fmt2, $out);
									if ($new === $date) return $fmt2;
								}
							}
						}
					}
				}
			}
		}
		
		return $format;
	}
	
	/**
	 * Guess the mapping field using the source column name and some basic matching rules.
	 * The value of the column is not used yet to guess the name.
	 * The fuzzyness parameter tells how to match the name or field label:
	 * 0 = match exactly (case sensitive)
	 * 1 = match exactly (case insensitive)
	 * 2 = match skipping non alphanumeric chars, like space, comma, semicolon, case insensitive
	 * 3 = match like before, and match also for string pairs with Levenshtein distance <= 1 or with similarity > 70%
	 * Some special cases are also guessed in every level:
	 *   *owner* -> assigned_user_id
	 */
	protected function guessMapField($column, $fields, $content = null, $fuzzyness = 2) {
		$guess = '';
		if ($fuzzyness >= 2) {
			$columnFuzzy = preg_replace('/[^a-z0-9]/i', '', $column);
		}
		// TODO: it would be better to try all the most strict filter first
		// TODO: also, for similar text and levenshtein, choose the best match first
		$hasAssigned = false;
		// crmv@125345
		foreach ($fields as $finfo) {
			$fieldname = $finfo['fieldname'];
			$fnames = array($finfo['fieldlabel'], $fieldname);
			foreach ($fnames as $fname) {
				switch ($fuzzyness) {
					case 0:
						if (strcmp($column, $fname) == 0) return $fieldname;
						break;
					case 1:
						if (strcasecmp($column, $fname) == 0) return $fieldname;
						break;
					case 2:
						if (strcasecmp($column, $fname) == 0) return $fieldname;
						$nameFuzzy = preg_replace('/[^a-z0-9]/i', '', $fname);
						if (strcasecmp($columnFuzzy, $nameFuzzy) == 0) return $fieldname;
						break;
					case 3:
						if (strcasecmp($column, $fname) == 0) return $fieldname;
						$nameFuzzy = preg_replace('/[^a-z0-9]/i', '', $fname);
						if (strcasecmp($columnFuzzy, $nameFuzzy) == 0) return $fieldname;
						$dist = levenshtein(strtolower($column), strtolower($fname));
						if ($dist >= 0 && $dist <= 1) return $fieldname;
						$sim = similar_text(strtolower($column), strtolower($fname), $perc);
						if ($sim > 3 && $perc > 70) return $fieldname;
						break;
					default:
						break;
				}
				if ($fname == 'assigned_user_id' && preg_match('/.*owner.*/', $column)) return $fieldname;
			}
		}
		// crmv@125345e

		return $guess;
	}
	
	protected function guessMapDefault($column, $field, $content = null) {
		global $current_user;
		$default = '';
		
		if ($field) {
			$fname = $field['fieldname'];
			if ($fname == 'assigned_user_id') $default = $current_user->id;
		}
	
		return $default;
	}
	
	protected function generateDefaults() {
		global $current_user;
		$defaults = array('create' => array(), 'update' => array());
		
		$defaults['create'][] = array(
			'field' => 'assigned_user_id',
			'default' => $current_user->id,
		);
		
		return $defaults;
	}
	
	protected function guessReference($column, $field, $content = null) {
		$ref = '';
		// nothing to guess for the moment
		return $ref;
	}
	
	/**
	 * Clear the variables for the specified step
	 */
	public function clearStepVars($step) {
		VteSession::removeArray(array('dimport', 'step_vars', $step));
	}
	
	/**
	 * Clear all the variables for all the steps
	 */
	public function clearAllStepsVars() {
		VteSession::removeArray(array('dimport', 'step_vars'));
	}
	
	/**
	 * Retrieve the variables for the specified step
	 */
	public function getStepVars($step) {
		$vars = array();
		
		if (VteSession::hasKeyArray(array('dimport', 'step_vars', $step))) {
			$vars = VteSession::getArray(array('dimport', 'step_vars', $step));
		}
		
		return $vars;
	}
	
	public function preprocessStepVars($mode, $step, $prevstep, &$request) {
		if (($mode == 'ajax' && $step == 5) || $prevstep == 5) {
			// convert the mapping in a practical array
			$mapping = array();
			$deffields = array('create' => array(), 'update' => array());
			foreach ($request as $k=>$v) {
				if (substr($k, 0, 12) == 'dimport_map_') {
					$field = substr($k, 12);
					$lastU = strrpos($field, '_');
					if ($lastU >= 0) {
						$type = substr($field, $lastU+1);
						$field = substr($field, 0, $lastU);
						if ($field != '') $mapping[$field][$type] = $v; // crmv@90286
					}
					unset($request[$k]);
				} elseif (substr($k, 0, 15) == 'dimport_dfield_') {
					$type = $k[15] == 'c' ? 'create' : 'update';
					$field = substr($k, 17);
					$lastU = strrpos($field, '_');
					if ($lastU >= 0) {
						$key = substr($field, 0, $lastU);
						$field = substr($field, $lastU+1);
						if (!empty($field)) $deffields[$type][$key][$field] = $v;
					}
					unset($request[$k]);
				}
			}
			$deffields['create'] = array_values($deffields['create']);
			$deffields['update'] = array_values($deffields['update']);
			$mapping = $this->fixSpaceNames($mapping, false); // crmv@71496
			$request['dimport_mapping'] = $mapping;
			$request['dimport_deffields'] = $deffields;
			
		}
	}
	
	/**
	 * Add or modify variables
	 */
	public function processStepVars($mode, $step, $prevstep, &$stepVars) {
		global $current_user;
		
		if ($step == 3 && $prevstep == 2) {
			if ($mode == 'create') {
				$stepVars['dimport_csvhasheader'] = true;
			}
		} elseif ($step == 5 && ($prevstep == 4 || $prevstep == 3)) {
			if ($mode == 'create') {
				// generate the array with mapping
				$mapping = $this->generateMapping();
				$defaults = $this->generateDefaults();
			} else {
				// other modes, merge the saved labels with the new ones
				$mapping = $this->mergeMapping($stepVars['dimport_mapping']);
				$defaults = $this->mergeDefaults($stepVars['dimport_deffields']);
			}
			if (!is_array($mapping)) return $mapping;
			if (!is_array($defaults)) return $defaults;
			$mapping = $this->fixSpaceNames($mapping, true); // crmv@71496
			$stepVars['dimport_mapping'] = $mapping;
			$stepVars['dimport_deffields'] = $defaults;
			// put them in session
			
			$this->saveStepVars($step, $stepVars);
		} elseif ($step == 6 && $prevstep == 5) {
			if ($mode == 'create') {
				$stepVars['dimport_sched_every'] = 1;
				$stepVars['dimport_sched_everywhat'] = 'day';
				$stepVars['dimport_sched_at'] = '22:00';
			}
		} elseif ($step == 7 && $prevstep == 6) {
			if ($mode == 'create') {
				$stepVars['dimport_notifyto'] = $current_user->id;
			}
		}
		
		
		return null;
	}
	
	// crmv@71496 crmv@105144
	public function fixSpaceNames($mapping, $toHtml = true) {
		return $this->diutils->fixSpaceNames($mapping, $toHtml);
	}
	// crmv@71496e crmv@105144e
	
	/**
	 * Insert all the information in the session cache, for the edit mode
	 */
	public function saveAllVarsFromDb($dbdata) {
		VteSession::setArray(array('dimport', 'step_vars'), array());

		VteSession::setArray(array('dimport', 'step_vars', 1, 'dimport_module'), $dbdata['module']);
		VteSession::setArray(array('dimport', 'step_vars', 1, 'dimport_invmodule'), $dbdata['invmodule']);
		
		VteSession::setArray(array('dimport', 'step_vars', 2, 'dimport_sourcetype'), $dbdata['srcinfo']['dimport_sourcetype']);
		VteSession::setArray(array('dimport', 'step_vars', 3), $dbdata['srcinfo']);
		if ($dbdata['srcinfo']['dimport_sourcetype'] == 'csv') {
			VteSession::setArray(array('dimport', 'step_vars', 4), array());
		} else {
			VteSession::setArray(array('dimport', 'step_vars', 4), $dbdata['srcinfo']);
		}

		VteSession::setArray(array('dimport', 'step_vars', 5, 'dimport_mapping'), $dbdata['mapping']['fields']);
		VteSession::setArray(array('dimport', 'step_vars', 5, 'dimport_mapping_keycol'), $dbdata['mapping']['dimport_mapping_keycol']);
		VteSession::setArray(array('dimport', 'step_vars', 5, 'dimport_deffields'), $dbdata['mapping']['deffields']);
		
		VteSession::setArray(array('dimport', 'step_vars', 6), $dbdata['scheduling']);
		VteSession::setArray(array('dimport', 'step_vars', 7, 'dimport_notifyto'), $dbdata['notifyto']);

	}
	
	/**
	 * Get all the variables ready to be inserted in the database
	 */
	public function getAllVarsForSave() {
		$out = array();
		if (is_array(VteSession::getArray(array('dimport', 'step_vars')))) {
			$srcinfo = array();
			foreach (VteSession::getArray(array('dimport', 'step_vars')) as $step=>$svars) {
				if ($step == 1) {
					$out['module'] = $svars['dimport_module'];
					$out['invmodule'] = $svars['dimport_invmodule'];
				} elseif ($step == 2) {
					$srcinfo['dimport_sourcetype'] = $svars['dimport_sourcetype'];
				} elseif ($step == 3) {
					$srcinfo = array_merge($srcinfo, $svars);
				} elseif ($step == 4) {
					$srcinfo = array_merge($srcinfo, $svars);
				} elseif ($step == 5) {
					$mapping = array(
						'fields' => $svars['dimport_mapping'],
						'dimport_mapping_keycol' => $svars['dimport_mapping_keycol'],
						'deffields' => $svars['dimport_deffields'],
					);
					$out['mapping'] = $mapping;
				} elseif ($step == 6) {
					$out['scheduling'] = $svars;
				} elseif ($step == 7) {
					$out['notifyto'] = $svars['dimport_notifyto'];
				} 
			}
			$out['srcinfo'] = $srcinfo;
		}

		return $out;
	}
	
	
}