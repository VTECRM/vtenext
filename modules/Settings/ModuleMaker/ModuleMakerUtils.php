<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@64542 crmv@105127 */


class ModuleMakerUtils {

	public $table_name = '';
	public $templates_dir = 'modules/Settings/ModuleMaker/templates';
	public $script_dir = 'storage/custom_modules';
	public $temp_script_dir = 'cache/vtlib';
	
	protected $_cache_tabid = array();
	protected $_cache_names = array();
	
	public function __construct() {
		global $table_prefix;
		
		$this->table_name = $table_prefix.'_modulemaker';

		eval(Users::m_de_cryption());
		eval($hash_version[21]);
	}
	
	public function canEditScripts() {
		return $this->can_edit_scripts;
	}
	
	public function canImport() {
		return $this->can_import;
	}
	
	public function canExport() {
		return $this->can_export;
	}

	public function canCreateInventory() {
		return $this->can_create_inventory;
	}
	
	/**
	 * Get the list of custom modules created
	 */
	public function getList() {
		global $adb, $table_prefix;
		
		$ret = array();
		$res = $adb->query("SELECT * FROM {$this->table_name}");
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$ret[] = $this->transformRowFromDb($row);
			}
		}
		return $ret;
	}
	
	/**
	 * Save a new custom module, which can be installed in the VTE or exported
	 */
	public function insertModule($data) {
		global $adb, $table_prefix;

		$now = date('Y-m-d H:i:s');
		$id = $adb->getUniqueID($this->table_name);
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'id' => $id,
			'modulename' => $data['modulename'],
			'createdtime' => $now,
			'modifiedtime' => $now,
			'installed' => 0,
			'useredit' => 0,
		);
		$q = "INSERT INTO {$this->table_name} (".implode(',', array_keys($params)).") VALUES (".generateQuestionMarks($params).")";
		
		// insert the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('moduleinfo', 'fields', 'panels', 'filters', 'relations', 'labels');
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "id = $id", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		return $id;
	}
	
	/**
	 * Update an existing entry in the custom modules list.
	 * The modulename cannot be changed
	 */
	public function updateModule($id, $data) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'modifiedtime' => $now,
			'installed' => ($data['installed'] ? 1 : 0),
			'useredit' => ($data['useredit'] ? 1 : 0),
			'id' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, installed = ?, useredit = ? WHERE id = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('moduleinfo', 'fields', 'panels', 'filters', 'relations', 'labels');
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table_name, $f, "id = $id", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		return true;
	}
	
	public function updateSingleField($id, $field, $value) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		$params = array(
			'modifiedtime' => $now,
			$field => $value,
			'id' => $id,
		);
		$q = "UPDATE {$this->table_name} SET modifiedtime = ?, {$field} = ? WHERE id = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
	}
	
	public function setUserEdit($id, $value = 1) {
		return $this->updateSingleField($id, 'useredit', ($value ? 1 : 0));
	}
	
	public function setInstalled($id, $value = 1) {
		return $this->updateSingleField($id, 'installed', ($value ? 1 : 0));
	}
	
	public function setShowLogs($id, $value = 1) {
		return $this->updateSingleField($id, 'showlogs', ($value ? 1 : 0));
	}
	
	public function hasUserEdit($id) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		
		
		$q = "SELECT useredit FROM {$this->table_name} WHERE id = ?";
		$params = array($id);
		
		// update the row
		$res = $adb->pquery($q, $params);
		$hasEdit = $adb->query_result_no_html($res, 0, 'useredit') ? true : false;
		return $hasEdit;
	}
	
	/**
	 * Get informations about a single custom module. Returns false if the id is not found.
	 */
	public function getModuleInfo($id) {
		global $adb, $table_prefix;
		
		$ret = false;
		$res = $adb->pquery("SELECT * FROM {$this->table_name} WHERE id = ?", array($id));
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$ret = $this->transformRowFromDb($row);
			}
		}
		return $ret;
	}
	
	protected function transformRowFromDb($row) {
		$jsonFields = array('moduleinfo', 'fields', 'panels', 'filters', 'relations', 'labels');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::decode($row[$f]);
			}
		}
		return $row;
	}
	
	protected function transformRowToDb($row) {
		$jsonFields = array('moduleinfo', 'fields', 'panels', 'filters', 'relations', 'labels');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::encode($row[$f]);
			}
		}
		return $row;
	}
	
	public function getScriptFileNames($id) {
		
		$modname = $this->getModuleNameById($id);
		if (empty($modname)) return false;
		
		$list = array();
		$scriptBase = "modmaker_{$id}_{$modname}";
		
		$list['install_script_name'] = $scriptBase.".php";
		$list['uninstall_script_name'] = $scriptBase."_u.php";
		
		$list['module_dir'] = $this->script_dir.'/'.$scriptBase;
		$list['install_script'] = $this->script_dir.'/'.$list['install_script_name'];
		$list['install_log'] = $this->script_dir.'/'.$scriptBase.'.log';
		$list['uninstall_script'] = $this->script_dir.'/'.$list['uninstall_script_name'];
		$list['uninstall_log'] = $this->script_dir.'/'.$scriptBase.'_u.log';
		$list['export_zip'] = $this->script_dir.'/'.$scriptBase.'.zip';
		$list['export_manifest'] = $this->script_dir.'/'.$scriptBase.'_manifest.xml';
		
		return $list;
	}
	
	/**
	 * Check if the custom module already has the script
	 */
	public function hasScript($id) {
		
		$names = $this->getScriptFileNames($id);
		
		if (!is_readable($names['install_script'])) return false;
		if (!is_readable($names['uninstall_script'])) return false;
		if (!is_dir($names['module_dir'])) return false;
		
		return true;
	}
		
	/**
	 * Remove the saved script for the module
	 */
	public function deleteScript($id) {
		
		$names = $this->getScriptFileNames($id);
		
		if (is_readable($names['install_script'])) unlink($names['install_script']);
		if (is_readable($names['uninstall_script'])) unlink($names['uninstall_script']);
		if (is_readable($names['install_log'])) unlink($names['install_log']);
		if (is_readable($names['uninstall_log'])) unlink($names['uninstall_log']);
		if (is_dir($names['module_dir'])) $this->rrmdir($names['module_dir']);
		if (is_readable($names['export_zip'])) unlink($names['export_zip']);
		if (is_readable($names['export_manifest'])) unlink($names['export_manifest']);
		
		return true;
	}
	
	public function installModule($id) {
		global $adb, $table_prefix;
		global $root_directory;
		
		$names = $this->getScriptFileNames($id);
		
		if (!$this->hasScript($id)) {
			return "Install script missing";
		}
		
		if (!defined('MODULEMAKERSCRIPT')) {
			define('MODULEMAKERSCRIPT', true);
		}
		
		$oldEOE = $adb->exceptOnError;
		$adb->setExceptOnError(true);
		try {
			file_put_contents($names['install_log'], date('Y-m-d H:i:s')." ---- BEGIN MODULE INSTALLATION ----\n\n");
			ob_start();
			ob_clean();
			chdir(dirname($names['module_dir']));
			require($names['install_script_name']);
			chdir($root_directory);
			$output = ob_get_clean();
			ob_end_flush();
			file_put_contents($names['install_log'], $output."\n", FILE_APPEND);
			file_put_contents($names['install_log'], date('Y-m-d H:i:s')." ---- MODULE INSTALLATION COMPLETED ----\n\n", FILE_APPEND);
		} catch (Exception $e) {
			chdir($root_directory);
			// try to save the buffer
			$output = ob_get_clean();
			ob_end_flush();
			file_put_contents($names['install_log'], $output."\n", FILE_APPEND);
			
			// set the error
			$error = true;
			file_put_contents($names['install_log'], "\n".date('Y-m-d H:i:s')." ---- EXCEPTION CAUGHT ----\n", FILE_APPEND);
			file_put_contents($names['install_log'], $e->getMessage()."\n", FILE_APPEND);
			file_put_contents($names['install_log'], $e->getTraceAsString()."\n", FILE_APPEND);
		}
		$adb->setExceptOnError($oldEOE);

		if ($error) {
			// rollback install
			$this->uninstallModule($id);
			// enable the log view
			$this->setShowLogs($id);
			return getTranslatedString('LBL_MMAKER_INSTALL_ERROR');
		}
		
		// set install flag
		$this->setInstalled($id);
		$this->setShowLogs($id);
		
		// empty the uninstall log
		file_put_contents($names['uninstall_log'], 'EMPTY LOG');
		
		return null;
	}
	
	/**
	 * Removes the module and the saved records from the VTE, but keep the line in the list for further modifications
	 */
	public function uninstallModule($id) {
		global $adb, $table_prefix;
		global $root_directory;
		
		$names = $this->getScriptFileNames($id);
		
		if (!$this->hasScript($id)) {
			return "Uninstall script missing";
		}
		
		// this is done in the uninstall script
		/*$r = $this->deleteModuleData($id);
		if ($r) return $r;
		$r = $this->deleteModuleFiles($id);
		if ($r) return $r;
		*/
		
		if (!defined('MODULEMAKERSCRIPT')) {
			define('MODULEMAKERSCRIPT', true);
		}
		$oldEOE = $adb->exceptOnError;
		$adb->setExceptOnError(true);
		try {
			file_put_contents($names['uninstall_log'], date('Y-m-d H:i:s')." ---- BEGIN MODULE UNINSTALLATION ----\n\n");
			ob_start();
			ob_clean();
			chdir(dirname($names['module_dir']));
			require($names['uninstall_script_name']);
			chdir($root_directory);
			$output = ob_get_clean();
			ob_end_flush();
			file_put_contents($names['uninstall_log'], $output."\n", FILE_APPEND);
			file_put_contents($names['uninstall_log'], date('Y-m-d H:i:s')." ---- MODULE UNINSTALLATION COMPLETED ----\n\n", FILE_APPEND);
		} catch (Exception $e) {
			chdir($root_directory);
			// try to save the buffer
			$output = ob_get_clean();
			ob_end_flush();
			file_put_contents($names['uninstall_log'], $output."\n", FILE_APPEND);
			
			// set the error
			$error = true;
			file_put_contents($names['uninstall_log'], "\n".date('Y-m-d H:i:s')." ---- EXCEPTION CAUGHT ----\n", FILE_APPEND);
			file_put_contents($names['uninstall_log'], $e->getMessage()."\n", FILE_APPEND);
			file_put_contents($names['uninstall_log'], $e->getTraceAsString()."\n", FILE_APPEND);
		}
		$adb->setExceptOnError($oldEOE);

		if ($error) {
			$this->setShowLogs($id);
			return getTranslatedString('LBL_MMAKER_UNINSTALL_ERROR');
		}
		
		// hide logs if everything went ok
		$this->setShowLogs($id, false);
		$this->setInstalled($id, false);
		return null;
	}
	
	/**
	 * Returns null if no error, or a string with the error
	 */
	public function exportScript($id, &$url) {
	
		require_once('vtlib/Vtecrm/Zip.php');
		
		if (!$this->hasScript($id)) {
			$error = $this->resetEditScripts($id);
			if ($error) return $error;
		}
		
		$userEdit = $this->hasUserEdit($id);
		$names = $this->getScriptFileNames($id);
		
		$r = $this->createExportManifest($id);
		if (!$r) return "Error creating manifest file";
		
		$zip = new Vtecrm_Zip($names['export_zip']);
		// Add manifest file
		$zip->addFile($names['export_manifest'], "manifest.xml");
		if ($userEdit) {
			$zip->addFile($names['install_script'], basename($names['install_script']));
			if (is_readable($names['uninstall_script'])) $zip->addFile($names['uninstall_script'], basename($names['uninstall_script']));
			$zip->copyDirectoryFromDisk($names['module_dir'], basename($names['module_dir']));
		}
		$zip->save();
		@unlink($names['export_manifest']);
		
		$url = $names['export_zip'];
		
		return null;
	}
	
	public function importModule($uploadFile) {
		global $adb, $table_prefix;
	
		require_once('vtlib/Vtecrm/Unzip.php');

		if (empty($uploadFile) || !is_readable($uploadFile['tmp_name']) || $uploadFile['size'] == 0) return getTranslatedString('LBL_UPLOAD_FAILED');
		
		if (!preg_match('/\.zip$/i', $uploadFile['name'])) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		$unzip = new Vtecrm_Unzip($uploadFile['tmp_name']);
		$filelist = $unzip->getList();
		
		if (!is_array($filelist) || count($filelist) == 0) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		// search for a valid manifest
		$hasManifest = false;
		foreach ($filelist as $zf) {
			if ($zf['file_name'] == 'manifest.xml') {
				$hasManifest = true;
				break;
			}
		}
		if (!$hasManifest) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		// extract the manifest
		$tmpManifest = $this->temp_script_dir.'/manifest-'.rand(100,100000).'.xml';
		$unzip->unzip('manifest.xml', $tmpManifest);
		
		if (!is_readable($tmpManifest)) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		$xml = simplexml_load_file($tmpManifest);
		@unlink($tmpManifest);
		
		$type = strval($xml->type);
		$moduleName = strval($xml->name);
		$useredit = (strval($xml->onlymeta) == '0');

		// check type
		if ($type != 'custommodule') return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		// check module name
		if (preg_match('/[^A-Za-z0-9]/', $moduleName)) return str_replace('%s', getTranslatedString('LBL_MODULENAME', 'Settings'), getTranslatedString('LBL_NO_SPECIAL_CHARS_IN_FIELD', 'APP_STRINGS'));
		
		// check permission to import custom files
		if ($useredit && !$this->canEditScripts()) return getTranslatedString('LBL_NOT_ALLOWED_UPLOAD_SCRIPTS');
		
		// check if the module exists already
		$tabid = getTabid($moduleName);
		if ($tabid > 0) return getTranslatedString('LBL_MODULE_EXISTING');
		
		$res = $adb->pquery("SELECT id FROM {$this->table_name} WHERE modulename = ?", array($moduleName));
		if ($res && $adb->num_rows($res) > 0) {
			return getTranslatedString('LBL_MODULE_EXISTING');
		}
		
		$modinfo = Zend_Json::decode(strval($xml->data->moduleinfo));
		$fields = Zend_Json::decode(strval($xml->data->fields));
		$panels = Zend_Json::decode(strval($xml->data->panels));
		$filters = Zend_Json::decode(strval($xml->data->filters));
		$relations = Zend_Json::decode(strval($xml->data->relations));
		$labels = Zend_Json::decode(strval($xml->data->labels));
		
		if (empty($modinfo) || empty($fields) || empty($filters) || empty($labels)) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		
		if ($useredit) {
			$zipInstall = strval($xml->paths->install_script);
			$zipUninstall = strval($xml->paths->uninstall_script);
			$zipdir = strval($xml->paths->module_dir);
			if (empty($zipInstall) || empty($zipdir)) return getTranslatedString('VTLIB_LBL_INVALID_FILE');
		}
		
		// now validate every step
		$mmsteps = new ModuleMakerSteps($this);
		$mmsteps->saveAllVarsFromDb(array(
			'modulename' => $moduleName,
			'moduleinfo' => $modinfo,
			'fields' => $fields,
			'panels' => $panels,
			'filters' => $filters,
			'relations' => $relations,
			'labels' => $labels,
		));
		
		for ($i = 1; $i<=6; ++$i) {
			$r = $mmsteps->validateStepVars($i);
			if (!empty($r)) {
				$mmsteps->clearAllStepsVars();
				return $r;
			}
		}
		$mmsteps->clearAllStepsVars();
		
		// insert the line
		$data = array(
			'modulename' => $moduleName,
			'moduleinfo' => $modinfo,
			'fields' => $fields,
			'panels' => $panels,
			'filters' => $filters,
			'relations' => $relations,
			'labels' => $labels,
		);
		$id = $this->insertModule($data);
		
		if (!$id) return "Save Error";
		
		if ($useredit) {
			// extract the files
			$names = $this->getScriptFileNames($id);
			if ($zipInstall) $unzip->unzip($zipInstall, $names['install_script'], 0644);
			if ($zipUninstall) $unzip->unzip($zipUninstall, $names['uninstall_script'], 0644);
			if ($zipdir) $unzip->unzipAllEx($this->temp_script_dir, array(
				'include' => array($zipdir)
			), false, false, "", 0755);
			$tmpModuleDir = $this->temp_script_dir.'/'.$zipdir;
			if (is_dir($tmpModuleDir)) rename($tmpModuleDir, $names['module_dir']);
			// set the flag
			$this->setUserEdit($id);
		} else {
			// generate the scripts for the new module
			$MMGen = new ModuleMakerGenerator($this, null);
			$error = $MMGen->generate($id);
			
			if ($error) {
				$this->deleteModule($id);
				return $error;
			}
		}
		
		$unzip->close();
		
		return null;
	}
	
	public function createExportManifest($id) {
		require('vteversion.php'); // crmv@181168
		
		$names = $this->getScriptFileNames($id);
		$info = $this->getModuleInfo($id);
		
		$xml = new SimpleXMLElement('<module/>');
		
		$xml->addChild('type', 'custommodule');
		$xml->addChild('name', $info['modulename']);
		$xml->addChild('onlymeta', $info['useredit'] ? 0 : 1);
		$xml->addChild('target_version', $enterprise_current_version);
		$xml->addChild('target_revision', $enterprise_current_build);
		$dataNode = $xml->addChild('data');
		$dataNode->addChild('moduleinfo', Zend_Json::encode($info['moduleinfo']));
		$dataNode->addChild('fields', Zend_Json::encode($info['fields']));
		$dataNode->addChild('panels', Zend_Json::encode($info['panels']));
		$dataNode->addChild('filters', Zend_Json::encode($info['filters']));
		$dataNode->addChild('relations', Zend_Json::encode($info['relations']));
		$dataNode->addChild('labels', Zend_Json::encode($info['labels']));
		if ($info['useredit']) {
			$pathNode = $xml->addChild('paths');
			$pathNode->addChild('install_script', $names['install_script_name']);
			$pathNode->addChild('uninstall_script', $names['uninstall_script_name']);
			$pathNode->addChild('module_dir', basename($names['module_dir']));
		}
		
		// do this to format the output nicely
		$dom = new DOMDocument();
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
		$dom->encoding = 'UTF-8';
		$formattedXML = $dom->saveXML();
		
		$r = file_put_contents($names['export_manifest'], $formattedXML);
		if (!$r) return false;
	
		return true;
	}
	
	
	/**
	 * Remove a module definition from the list and all the associated records and data if removeAll is true
	 */
	public function deleteModule($id, $removeAll = false) {
		global $adb, $table_prefix;
		
		if ($removeAll) {
			$this->deleteModuleData($id);
			$this->deleteModuleFiles($id);
		}
		
		// remove the script
		$this->deleteScript($id);
		
		// remove the saved line
		$adb->pquery("DELETE FROM {$this->table_name} WHERE id = ?", array($id));
		
		return true;
	}
		
	/**
	 * Remove ALL the record and metadata for the specified custom module
	 */
	public function deleteModuleData($id) {
		global $adb, $table_prefix;
		
		require_once('vtlib/Vtecrm/Module.php');
		
		$modname = $this->getModuleNameById($id);
		if (!$modname) return false;
		
		$tabid = $this->getTabId($id);
		
		// remove the module tables
		$this->dropModuleTables($modname);
		
		// delete module with vtlib
		$module = Vtecrm_Module::getInstance($modname);
		if ($module) $module->__delete();
		unset($this->_cache_tabid[$id]);
		
		// removes all the rows with references to this module
		if ($tabid > 0) {
			$this->deleteRowsWithColumnValue('tabid', $tabid, true);
		}
		$this->deleteRowsWithColumnValue('entitytype', $modname, false);
		$this->deleteRowsWithColumnValue('entity_type', $modname, false);
		$this->deleteRowsWithColumnValue('setype', $modname, false);
		$this->deleteRowsWithColumnValue('module', $modname, false);
		$this->deleteRowsWithColumnValue('relmodule', $modname, false);
		$this->deleteRowsWithColumnValue('semodule', $modname, false);
		
		return true;
	}
	
	protected function dropModuleTables($modname) {
		global $adb, $table_prefix;
		
		$tables = array();
		@include_once("modules/$modname/{$modname}.php");
		if (class_exists($modname)) {
			$instance = CRMEntity::getInstance($modname);
			if ($instance) {
				$tables[] = $instance->table_name;
				$tables[] = $instance->customFieldTable[0];
			}
		}
		$tables = array_filter($tables);
		if (count($tables) > 0) {
			foreach ($tables as $modTable) {
				if (Vtecrm_Utils::CheckTable($modTable)) {
					$dropq = $adb->datadict->DropTableSQL($modTable);
					$adb->datadict->ExecuteSQLArray($dropq);
				}
			}
		}
		
		return true;
	}
	
	
	/**
	 * Delete all the files for the specified custom module.
	 */
	public function deleteModuleFiles($id) {
		global $adb, $table_prefix;
		
		$modname = $this->getModuleNameById($id);
		if (!$modname) return false;
		
		$this->rrmdir("modules/$modname");
		$this->rrmdir("cron/modules/$modname");
		$this->rrmdir("Smarty/templates/modules/$modname");
		
		return true;
	}
	
	public function getEditableFiles($moduleid) {
		$list = array();
		
		$modname = $this->getModuleNameById($moduleid);
		if (!$modname) return false;
		
		$scriptBase = "modmaker_{$moduleid}_{$modname}";
		$scriptName = $scriptBase.".php";
		$moduleDir = $this->script_dir.'/'.$scriptBase;
		
		$list['install_script'] = getTranslatedString('LBL_MMAKER_INSTALLSCRIPT');
		$list['uninstall_script'] = getTranslatedString('LBL_MMAKER_UNINSTALLSCRIPT');
		
		$files = glob($moduleDir.'/*.{php,js}', GLOB_BRACE);
		if ($files && is_array($files)) {
			foreach ($files as $f) {
				$key = 'MODULE/'.basename($f);
				$list[$key] = $modname.'/'.basename($f);
			}
		}
		
		return $list;
	}
	
	public function getEditScript($moduleid, $file) {
		
		$names = $this->getScriptFileNames($moduleid);
		
		$realFile = false;
		if ($file == 'install_script') {
			$realFile = $names['install_script'];
		} elseif ($file == 'uninstall_script') {
			$realFile = $names['uninstall_script'];
		} elseif (substr($file, 0, 6) == 'MODULE') {
			$realFile = $names['module_dir'].'/'.str_replace(array('..', '/', '\\', ':'), '', substr($file, 7));
		}
		
		if (!empty($realFile) && is_readable($realFile)) {
			return file_get_contents($realFile);
		}
		return false;
	}
	
	public function saveEditScript($moduleid, $file, $content) {
		
		$names = $this->getScriptFileNames($moduleid);
		
		$realFile = false;
		if ($file == 'install_script') {
			$realFile = $names['install_script'];
		} elseif ($file == 'uninstall_script') {
			$realFile = $names['uninstall_script'];
		} elseif (substr($file, 0, 6) == 'MODULE') {
			$realFile = $names['module_dir'].'/'.str_replace(array('..', '/', '\\', ':'), '', substr($file, 7));
		}
		
		if (!empty($realFile) && is_readable($realFile)) {
			$r = file_put_contents($realFile, $content);
			if ($r !== false) {
				$this->setUserEdit($moduleid);
				return true;
			}
		}
		return false;
	}
	
	public function resetEditScripts($moduleid) {
		$generator = new ModuleMakerGenerator($this, null);
		$res = $generator->generate($moduleid);
		if (empty($res)) {
			$this->setUserEdit($moduleid, 0);
		}
		return $res;
	}
	
	protected function rrmdir($dir) { 
		if (is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					$subpath = $dir.DIRECTORY_SEPARATOR.$object;
					if (filetype($subpath) == "dir") $this->rrmdir($subpath); else unlink($subpath); 
				} 
			} 
			reset($objects); 
			rmdir($dir); 
		} elseif (is_file($dir)) {
			unlink($dir);
		}
	}
	
	protected function deleteRowsWithColumnValue($column, $value, $fuzzy = false) {
		global $adb, $table_prefix;
		
		$list = $this->getTablesWithColumn($column, $fuzzy);
		if ($list && is_array($list)) {
			foreach ($list as $table) {
				if ($fuzzy) {
					list($tablename, $columnname) = explode('.', $table, 2);
				} else {
					$tablename = $table;
					$columnname = $column;
				}
				$adb->format_columns($columnname);
				$q = "DELETE FROM $tablename WHERE $columnname = ?";
				$adb->pquery($q, array($value));
			}
		}
		
		return true;
	}
	
	// use only alphanumeric names for the column
	protected function getTablesWithColumn($column, $fuzzy = false) {
		global $adb, $dbconfig;
		
		$dbname = $dbconfig['db_name'];
		$params = array();
		
		if ($adb->isMysql() || $adb->isMssql()) {
			if ($fuzzy) {
				$query = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = '$dbname' AND column_name LIKE '%$column%'";
			} else {
				$query = "SELECT table_name FROM information_schema.columns WHERE table_schema = '$dbname' AND column_name = ?";
				$params = array($column);
			}
		} elseif ($adb->isOracle()) {
			if ($fuzzy) {
				$query = 
					"SELECT object_name AS table_name, user_tab_cols.column_name
						FROM user_objects 
						INNER JOIN user_tab_cols ON user_objects.object_name = user_tab_cols.table_name 
					WHERE user_objects.object_type = 'TABLE' AND user_tab_cols.column_name LIKE '%$column%'";
			} else {
				$query = 
					"SELECT object_name AS table_name
						FROM user_objects 
						INNER JOIN user_tab_cols ON user_objects.object_name = user_tab_cols.table_name 
					WHERE user_objects.object_type = 'TABLE' AND user_tab_cols.column_name = ?";
				$params = array(strtoupper($column));
			}
		} else {
			// database type not supported
			return false;
		}
		
		$list = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				if ($fuzzy) {
					$col = $row['table_name'].'.'.$row['column_name'];
				} else {
					$col = $row['table_name'];
				}
				$list[] = $col;
			}
		}
		return $list;
	}
	
	/**
	 * Return the modulename of the custom module by its id
	 */
	public function getModuleNameById($id) {
		global $adb, $table_prefix;
		
		$modname = false;
		if (!isset($this->_cache_names[$id])) {
			$res = $adb->pquery("SELECT modulename FROM {$this->table_name} WHERE id = ?", array($id));
			if ($res && $adb->num_rows($res) > 0) {
				$modname = $adb->query_result_no_html($res, 0, 'modulename');
			}
			$this->_cache_names[$id] = $modname;
		}
		return $this->_cache_names[$id];
	}
	
	/**
	 * Get the tabid for a custom module. Returns false if it's not installed
	 */
	public function getTabId($id) {
		global $adb, $table_prefix;
		
		$ret = false;
		$modname = $this->getModuleNameById($id);
		if ($modname) {
			return $this->getTabIdByName($modname);
		}
		return $ret;
	}
	
	/**
	 * Get the tabid for a custom module by name, or false if it's not installed
	 */
	public function getTabIdByName($name) {
		global $adb, $table_prefix;
		
		$tabid = getTabId($name) ?: false;
		return $tabid;
	}

	public function getSharingMapping() {
		global $adb, $table_prefix;
		
		$share = array(
			0 => array('id' => 0, 'code' => 'Public_ReadOnly', 			'name' => '',  'label' => ''),
			1 => array('id' => 1, 'code' => 'Public_ReadWrite', 		'name' => '',  'label' => ''),
			2 => array('id' => 2, 'code' => 'Public_ReadWriteDelete', 	'name' => '',  'label' => ''),
			3 => array('id' => 3, 'code' => 'Private', 					'name' => '',  'label' => ''),
		);
		
		// now fill the labels
		$ids = array_map(function($v) {
			return $v["id"];
		}, $share);
		$res = $adb->pquery("SELECT share_action_id, share_action_name FROM {$table_prefix}_org_share_act_mapping WHERE share_action_id IN (".generateQuestionMarks($ids).")", $ids);
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$id = intval($row['share_action_id']);
				$share[$id]['name'] = $row['share_action_name'];
				$share[$id]['label'] = getTranslatedString($row['share_action_name']);
			}
		}
		
		return $share;
	}
	
	//crmv@160837
	function getModuleMakerFieldProperties($module, $fieldname, &$fieldno=null, $row=null) {
		global $adb, $table_prefix, $current_user;
		
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$MMSteps = new ProcessModuleMakerSteps($this);
		$newFields = $MMSteps->getNewFields();
		
		if (empty($row)) {
			$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and fieldname = ?", array(getTabid($module), $fieldname));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					break;
				}
			}
		}
		//crmv@131239
		($row['uitype'] == 300) ? $uitype = 15 : $uitype = $row['uitype'];
		$column_fieldname = $row['fieldname'];
		$metaColumns = $adb->datadict->MetaColumns($row['tablename']);
		if (empty($fieldno)) {
			if ($metaColumns[strtoupper($column_fieldname)]->type == 'decimal') {
				$fieldno = $MMSteps->getNewFieldNoByUitype($uitype, 'decimals');
			} else {
				$fieldno = $MMSteps->getNewFieldNoByUitype($uitype);
			}
		}
		//crmv@131239e
		
		$webservice_field = WebserviceField::fromArray($adb,$row);
		
		$properties = array(
				'mandatory' => (strpos($row['typeofdata'],'~M') !== false) ? 1 : 0,
				'readonly' => $row['readonly'],
		);
		$fieldProperties = $newFields[$fieldno]['properties'];
		if (!empty($fieldProperties)) {
			foreach($fieldProperties as $prop) {
				switch($prop){
					case 'label':
						$properties[$prop] = getTranslatedString($row['fieldlabel'],$module); // crmv@152807
						break;
					case 'length':
						$properties[$prop] = $metaColumns[strtoupper($column_fieldname)]->max_length;
						break;
					case 'decimals':
						$properties[$prop] = $metaColumns[strtoupper($column_fieldname)]->scale;
						break;
					case 'picklistvalues':
						$values_arr = getAssignedPicklistValues($column_fieldname, $current_user->roleid, $adb, $module);
						$properties[$prop] = implode("\n",array_keys($values_arr)); // fix
						break;
					case 'newline':
						$properties[$prop] = 0;
						$fieldinfo = $adb->pquery("select info from {$table_prefix}_field
						inner join {$table_prefix}_fieldinfo on {$table_prefix}_field.fieldid = {$table_prefix}_fieldinfo.fieldid
						where tabid = ? and fieldname = ?", array($tabid,$column_fieldname));
						if ($fieldinfo && $adb->num_rows($fieldinfo) > 0) {
							$info = Zend_Json::decode($adb->query_result_no_html($fieldinfo,0,'info'));
							if (isset($info['newline'])) $properties[$prop] = $info['newline'];
						}
						break;
						//crmv@131239
					case 'relatedmods_selected':
						$properties[$prop] = $webservice_field->getReferenceList();
						break;
						//crmv@131239e
					case 'columns':
						$modLightUtils = ModLightUtils::getInstance();
						$columns = $modLightUtils->getColumns($module, $fieldname);
						$properties[$prop] = Zend_Json::encode($columns);
						break;
						//TODO 'autoprefix', 'onclick', 'code'
				}
			}
		}
		return $properties;
	}
	//crmv@160837e
}