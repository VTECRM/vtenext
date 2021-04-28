<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@106857 crmv@112297 crmv@146434 */

require_once('include/BaseClasses.php');
require_once('modules/Settings/LayoutBlockListUtils.php');

class ModLightUtils extends SDKExtendableUniqueClass {
	
	function getParentModule($fieldname) {
		global $adb, $table_prefix;
		$result = $adb->pquery("select {$table_prefix}_tab.name from {$table_prefix}_field inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid where fieldname = ?", array($fieldname));
		if ($result && $adb->num_rows($result) > 0) {
			return $adb->query_result($result,0,'name');
		}
		return false;
	}
	function getModuleList() {
		global $adb, $table_prefix;
		$modules = array();
		$result = $adb->pquery("SELECT {$table_prefix}_tab.name
			FROM {$table_prefix}_tab_info
			INNER JOIN {$table_prefix}_tab ON {$table_prefix}_tab_info.tabid = {$table_prefix}_tab.tabid
			WHERE prefname = ? AND prefvalue = ?", array('is_mod_light',1));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$modules[] = $row['name'];
			}
		}
		return $modules;
	}
	//crmv@155375
	function getModLights($module) {
		global $adb, $table_prefix;
		$modlights = array();
		$moduleInstance = Vtecrm_Module::getInstance($module);
		$result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance->id, 220));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$fieldname = $row['fieldname'];
				$modulelightid = str_replace('ml','',$fieldname);
				$modulelightname = 'ModLight'.$modulelightid;
				$modLightInstance = Vtecrm_Module::getInstance($modulelightname);
				$modlights[$modLightInstance->id] = $modulelightname;
			}
		}
		return $modlights;
	}
	//crmv@155375e
	function addTableField($blockid, $addfieldno, $properties, $forceSuffix = '') { // crmv@198024
		global $adb, $table_prefix, $metaLogs;
		$result = $adb->pquery("select {$table_prefix}_tab.tabid, name from {$table_prefix}_blocks
			inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_blocks.tabid
			where blockid = ?", array($blockid));
		if ($result && $adb->num_rows($result) > 0) {
			$reltabid = $adb->query_result($result,0,'tabid');
			$relmodule = $adb->query_result($result,0,'name');
			$columns = Zend_Json::decode($properties['columns']);
			
			$suffix = $forceSuffix ?: $adb->getUniqueID($table_prefix.'_modlight'); // crmv@198024
			
			require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
			require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
			require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
			$MMUtils = new ModuleMakerUtils();
			$MMSteps = new ProcessModuleMakerSteps($MMUtils);
			$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
			$newFields = $MMSteps->getNewFields();

			// crmv@198024
			$mlname = 'ModLight'.$suffix;
			$MMGen->setModuleName($mlname);
			// crmv@198024
			
			include_once('vtlib/Vtecrm/Menu.php');
			include_once('vtlib/Vtecrm/Module.php');
			
			// Create module instance and save it first
			$module = new Vtecrm_Module();
			$module->name = $mlname; // crmv@198024
			$module->is_mod_light = true;
			$module->save();
			$module->initTables();
			
			// Do not add the module to any Menu
			
			// Add the basic module block
			$block = new Vtecrm_Block();
			$block->label = 'LBL_INFORMATION';
			$module->addBlock($block);
			
			$filter = new Vtecrm_Filter();
			$filter->name = 'All';
			$filter->isdefault = true;
			$module->addFilter($filter);
			
			$i = 0;
			foreach($columns as $column) {
				$field = $this->newField($module,$block,$column);

				// Set at-least one field to identifier of module record
				if ($i == 0) {
					$module->setEntityIdentifier($field);
					$MMGen->mainField = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));
				}
				$i++;
				if ($i <= 9) $filter->addField($field,$i);
			}
			
			$field = new Vtecrm_Field();
			$field->name = 'parent_id';
			$field->label = getTranslatedString('SINGLE_'.$relmodule);
			$field->table = $module->basetable;
			$field->uitype = 10;
			$field->readonly = 1;
			$field->presence = 2;
			$field->displaytype = 3;
			$field->typeofdata = 'I~O';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$field->columntype = 'I(19)';
			$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));;
			$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
			$block->addField($field);
			$field->setRelatedModules(array($relmodule));
			
			$field = new Vtecrm_Field();
			$field->name = 'seq';
			$field->label = 'Sequence';
			$field->table = $module->basetable;
			$field->uitype = 1;
			$field->readonly = 1;
			$field->presence = 2;
			$field->displaytype = 3;
			$field->typeofdata = 'I~O';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$field->columntype = 'I(19)';
			$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));;
			$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
			$block->addField($field);
						
			$field = new Vtecrm_Field();
			$field->name = 'assigned_user_id';
			$field->label = 'Assigned To';
			$field->table = $table_prefix.'_crmentity';
			$field->column = 'smownerid';
			$field->uitype = 53;
			$field->readonly = 1;
			$field->presence = 2;
			$field->displaytype = 3;
			$field->typeofdata = 'V~M';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));;
			$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
			$block->addField($field);
			
			$field = new Vtecrm_Field();
			$field->name = 'createdtime';
			$field->label= 'Created Time';
			$field->table = $table_prefix.'_crmentity';
			$field->uitype = 70;
			$field->readonly = 1;
			$field->presence = 2;
			$field->displaytype = 3;
			$field->typeofdata = 'T~O';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));;
			$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
			$block->addField($field);
			
			$field = new Vtecrm_Field();
			$field->name = 'modifiedtime';
			$field->label= 'Modified Time';
			$field->table = $table_prefix.'_crmentity';
			$field->uitype = 70;
			$field->readonly = 1;
			$field->presence = 2;
			$field->displaytype = 3;
			$field->typeofdata = 'T~O';
			$field->quickcreate = 3;
			$field->masseditable = 0;
			$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));;
			$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
			$block->addField($field);
			
			$error = '';
			$result = $this->generateFileStructure($module->name, $properties['label'], $MMGen, $error);
			if (!$result) return $error;
			
			// set sharing access of this module without recalculated privileges
			VteSession::set('skip_recalculate', true);
			$module->setDefaultSharing('Public_ReadWriteDelete', 2); // crmv@193317 crmv@200912
			VteSession::remove('skip_recalculate');
			
			// disable available tools
			$module->disableTools(array('Import','Export','Merge')); 
			
			// initialize webservice
			$module->initWebservice();
			
			Vtecrm_Module::fireEvent($module->name, Vtecrm_Module::EVENT_MODULE_POSTINSTALL);
			
			// recalculate $MMSteps with standard class
			//$MMSteps = new ModuleMakerSteps($MMUtils);
			//$newFields = $MMSteps->getNewFields();
			$fields = array();
			$fields[] = array('module'=>$relmodule,'block'=>$blockid,'name'=>'ml'.$suffix,'label'=>$properties['label'],'uitype'=>220); // crmv@198024
			include('modules/SDK/examples/fieldCreate.php');
			
			if ($metaLogs) $this->versionOperation($reltabid,'ml'.$suffix,$metaLogs::OPERATION_ADDFIELD); // crmv@198024
		}
	}
	function generateFileStructure($module, $label, &$MMGen, &$error='') {
		
		// copy dir
		$r = $MMGen->copyBaseFiles();
		if (!$r) {
			$error = getTranslatedString('LBL_COPY_FILES_ERROR');
			return false;
		}
		
		// change file names
		$r = $MMGen->alterFileNames();
		if (!$r) {
			$error = getTranslatedString('LBL_RENAME_FILE_ERROR');
			return false;
		}
		
		// empty the language files
		$dir = 'modules/'.$module.'/language/';
		$files = scandir($dir);
		if (!empty($files)) {
			$trans = array(
				strval($module) => $label,
				'SINGLE_'.$module => $label,
				'Assigned To' => 'Assegnato a',
				'Created Time' => 'Orario creazione',
				'Modified Time' => 'Orario modifica',
			);
			foreach($files as $file) {
				if (!in_array($file,array('.','..','.svn'))) {
					$buffer = "<?php\n\n";
					$buffer .= "/* Automatically generated translations for module {$module} */\n\n";
					$buffer .= '$mod_strings = '.var_export($trans, true).";\n";
					$buffer .= "\n";
					$r = file_put_contents($dir.$file, $buffer);
					if ($r === false) {
						$error = 'Generation of translations files failed';
						return false;
					}
				}
			}
			SDK::file2DbLanguages($module);
		}
		
		// calculate the table structure
		$r = $MMGen->calculateTableStructure(true);
		if (!$r) {
			$error = getTranslatedString('LBL_UT208_GENERIC_ERROR');
			return false;
		}
		
		// alter the file content
		$r = $MMGen->alterFileContent();
		if (!$r) {
			$error = getTranslatedString('ERROR_WHILE_EDITING');
			return false;
		}
		
		return true;
	}
	function editTableField($blockid, $editfieldno, $properties) {
		global $adb, $table_prefix, $current_user, $metaLogs;

		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
		$newFields = $MMSteps->getNewFields();

		$result1 = $adb->pquery("select tabid, fieldname, fieldlabel, readonly, typeofdata from {$table_prefix}_field where fieldid = ?", array($editfieldno)); // crmv@190916
		$fieldname = $adb->query_result($result1,0,'fieldname');
		$fieldlabel = $adb->query_result($result1,0,'fieldlabel');
		$reltabid = $adb->query_result($result1,0,'tabid');
		// crmv@190916
		$readonly = $adb->query_result($result1,0,'readonly');
		$typeofdata = $adb->query_result($result1,0,'typeofdata');
		// crmv@190916e
		$modulelightid = str_replace('ml','',$fieldname);
		$modulelightname = 'ModLight'.$modulelightid;
		$tabid = getTabid($modulelightname);
		$module = Vtecrm_Module::getInstance($modulelightname);
		$block = Vtecrm_Block::getInstance('LBL_INFORMATION',$module);
		
		// crmv@190916
		$typeofdata = explode("~",$typeofdata);
		$mandatory = ($typeofdata[1] == 'M') ? true : false;
		
		$update = array();
		if ($properties['label'] != $fieldlabel) $update['fieldlabel = ?'] = $properties['label'];
		if ($properties['readonly'] != $fieldlabel) $update['readonly = ?'] = $properties['readonly'];
		if ($properties['mandatory'] != $mandatory) {
			$update['typeofdata = ?'] = $typeofdata[0].'~';
			$update['typeofdata = ?'] .= ($properties['mandatory']) ? 'M' : 'O';
		}
		if (!empty($update)) {
			$adb->pquery("update {$table_prefix}_field set ".implode(',',array_keys($update))." where fieldid = ?", array($update,$editfieldno));
		}
		// crmv@190916e

		$filter = Vtecrm_Filter::getInstance('All',$module);
		$adb->pquery("DELETE FROM {$table_prefix}_cvcolumnlist WHERE cvid=?", Array($filter->id));

		$table_fields = array();
		$column_fields = array();
		$result1 = $adb->pquery("select fieldname, displaytype from {$table_prefix}_field where tabid = ?", array($tabid));
		while($row=$adb->fetchByAssoc($result1)) {
			$column_fields[] = $row['fieldname'];
			if ($row['displaytype'] != 3) $table_fields[] = $row['fieldname'];	// skip standard fields (assigned_user_id, parent_id, etc.)
		}
		
		$result = $adb->pquery("select {$table_prefix}_tab.tabid, name from {$table_prefix}_blocks
			inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_blocks.tabid
			where blockid = ?", array($blockid));
		if ($result && $adb->num_rows($result) > 0) {
			$relmodule = $adb->query_result($result,0,'name');
			$columns = Zend_Json::decode($properties['columns']);

			$i = 0;
			$sequence = array();
			foreach($columns as $column) {
				if (!empty($column['fldname']) && in_array($column['fldname'],$column_fields)) {
					// update field
					$updated_fields[] = $column['fldname'];

					$typeofdata = $adb->query_result($adb->pquery("select typeofdata from {$table_prefix}_field where tabid = ? and fieldname = ?", array($tabid,$column['fldname'])), 0,'typeofdata');
					if ($column['mandatory']) $typeofdata = $MMGen->makeTODMandatory($typeofdata);
					else $typeofdata = $MMGen->makeTODOptional($typeofdata);
					$adb->pquery("update {$table_prefix}_field set fieldlabel = ?, readonly = ?, typeofdata = ? where tabid = ? and fieldname = ?", array($column['label'],$column['readonly'],$typeofdata,$tabid,$column['fldname']));
					
					$field = Vtecrm_Field::getInstance($column['fldname'],$module);
					if (isset($column['picklistvalues'])) {
						$picklistid = $adb->query_result($adb->pquery("select picklistid FROM {$table_prefix}_picklist WHERE name = ?",array($column['fldname'])),0,'picklistid');
						$adb->pquery("DELETE FROM {$table_prefix}_role2picklist WHERE picklistid = ? AND picklistvalueid IN (SELECT picklist_valueid FROM {$table_prefix}_{$column['fldname']})", array($picklistid));
						$adb->query("DELETE FROM {$table_prefix}_{$column['fldname']}");
						$picklistValues = array_map('trim', array_unique(explode("\n", $column['picklistvalues'])));
						$field->setPicklistValues($picklistValues);
					}
					if (isset($column['relatedmods'])) {
						//crmv@131239
						(is_array($column['relatedmods'])) ? $relmods = $column['relatedmods'] : $relmods = array_map('trim', array_filter(explode(',', $column['relatedmods'])));
						$adb->pquery('DELETE FROM '.$table_prefix.'_fieldmodulerel WHERE fieldid=?', Array($field->id));	// clean all
						//crmv@131239e
						$field->setRelatedModules($relmods);
					}
					$fieldinfo = array();
					if (isset($column['users'])) {
						$fieldinfo['users'] = $column['users'];
					}
					if (isset($column['newline']) && intval($column['newline']) == 1) {
						$fieldinfo['newline'] = 1;
					}
					$field->setFieldInfo($fieldinfo);
				} else {
					// new
					$field = $this->newField($module,$block,$column);
				}
				$sequence[] = $field->name;
				$i++;
				if ($i <= 9) $filter->addField($field,$i);
			}
			// remove deleted fields
			$remove_fields = array_diff($table_fields,$updated_fields);
			if (!empty($remove_fields)) {
				foreach($remove_fields as $remove_field) {
					$field = Vtecrm_Field::getInstance($remove_field,$module);
					$field->delete();
					// link delete metalog to related module
					if ($metaLogs) $this->versionOperation($field->getModuleId(),$field->id,$metaLogs::OPERATION_DELFIELD,$reltabid);
				}
			}
			// recalculate sequence
			$other_fields = array_diff($column_fields,$sequence);
			$final_fields = array_merge($sequence,$other_fields);
			if (!empty($final_fields)) {
				foreach($final_fields as $seq => $final_field) {
					$adb->pquery("update {$table_prefix}_field set sequence = ? where tabid = ? and fieldname = ?", array($seq+1,$tabid,$final_field));
				}
			}
			// reset at-least one field to identifier of module record
			$module->unsetEntityIdentifier();
			if (!empty($sequence)) $module->setEntityIdentifier(Vtecrm_Field::getInstance($sequence[0],$module));
		}
		
		FieldUtils::invalidateCache($tabid); // crmv@193294
		
		$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
		if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $editfieldno);
		if (!empty($metaLogId)) $layoutBlockListUtils->versionOperation($reltabid,$metaLogId);
	}
	function newField($module,$block,$column) {
		global $adb, $table_prefix;
		
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
		$MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);
		
		// crmv@198024
		if (!$column['uitype']) {
			$newFields = $MMSteps->getNewFields();
		}
		// crmv@198024e
		
		$field = new Vtecrm_Field();
		$field->name = $column['fieldname'] ?: 'f'.$adb->getUniqueID($table_prefix.'_modlightfield'); // crmv@198024
		$field->table = $module->basetable;
		$field->label = $column['label'];
		$field->uitype = intval($column['uitype']) ?: intval($newFields[$column['fieldno']]['uitype']); // crmv@198024
		$column['uitype'] = $field->uitype;
		$field->typeofdata = $MMGen->getTODForField($column);
		if ($column['mandatory']) {
			$field->typeofdata = $MMGen->makeTODMandatory($field->typeofdata);
		}
		$field->columntype = isset($column['columntype']) ? $column['columntype'] : $MMGen->getColumnTypeForField($column); // crmv@203591
		if (isset($column['displaytype'])) $field->displaytype = $column['displaytype']; // crmv@203591
		$field->readonly = $column['readonly'];
		$field->quickcreate = 3;
		$field->masseditable = 0;
		if (!empty($column['helpinfo'])) $field->helpinfo = $column['helpinfo']; // crmv@199115
		$MMGen->moduleInfo['fields'][0]['fields'][] = array_merge((array)$field,array('fieldname'=>$field->name,'fieldlabel'=>$field->label));
		$MMGen->moduleInfo['filters'][0]['columns'][] = $field->name;
		$block->addField($field);
		
		if (isset($column['picklistvalues'])) {
			$picklistValues = array_map('trim', array_unique(explode("\n", $column['picklistvalues'])));
			$field->setPicklistValues($picklistValues);
		}
		if (isset($column['relatedmods'])) {
			(is_array($column['relatedmods'])) ? $relmods = $column['relatedmods'] : $relmods = array_map('trim', array_filter(explode(',', $column['relatedmods'])));	//crmv@131239
			$field->setRelatedModules($relmods);
		}
		$fieldinfo = array();
		if (isset($column['users'])) {
			$fieldinfo['users'] = $column['users'];
		}
		if (isset($column['newline']) && intval($column['newline']) == 1) {
			$fieldinfo['newline'] = 1;
		}
		$field->setFieldInfo($fieldinfo);
		
		return $field;
	}
	function deleteTableField($editfieldno, $log=true) {
		global $adb, $table_prefix, $metaLogs;
		
		$field = Vtecrm_Field::getInstance($editfieldno);
		$modulelightid = str_replace('ml','',$field->name);
		$modulelightname = 'ModLight'.$modulelightid;
		
		// delete module
		$module = Vtecrm_Module::getInstance($modulelightname);
		if ($module) $module->delete();
		
		// delete field
		if ($field) $field->delete();
		
		// drop tables
		if (Vtecrm_Utils::CheckTable("{$table_prefix}_modlight{$modulelightid}")) $adb->query("drop table {$table_prefix}_modlight{$modulelightid}");
		if (Vtecrm_Utils::CheckTable("{$table_prefix}_modlight{$modulelightid}cf")) $adb->query("drop table {$table_prefix}_modlight{$modulelightid}cf");
		$adb->pquery("DELETE FROM {$table_prefix}_crmentity WHERE setype = ?",array($modulelightname)); // crmv@181003
		
		// delete files and folders
		FSUtils::deleteFolder("modules/$modulelightname");
		FSUtils::deleteFolder("Smarty/templates/modules/$modulelightname");
		FSUtils::deleteFolder("cron/modules/$modulelightname");
		
		if ($log && $field && $metaLogs) $this->versionOperation($field->getModuleId(),$editfieldno,$metaLogs::OPERATION_DELFIELD);
	}
	function getColumns($module, $fieldname) {
		static $column_cache = array();
		if (isset($column_cache[$module][$fieldname])) return $column_cache[$module][$fieldname];
		
		global $adb, $table_prefix, $current_user;
		require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$MMUtils = new ModuleMakerUtils();
		$MMSteps = new ProcessModuleMakerSteps($MMUtils);
			
		$modulelightid = str_replace('ml','',$fieldname);
		$modulelightname = 'ModLight'.$modulelightid;
		$tabid = getTabid($modulelightname);
		$result = $adb->pquery("select * from {$table_prefix}_field where tabid = ? and displaytype <> ? order by block, sequence", array($tabid,3));
		$columns = array();
		if ($result && $adb->num_rows($result) > 0) {
			$i = 0;
			//$newFields = $MMSteps->getNewFields();
			// TODO verificare che funzioni tutto anche cosi!!!!!!
			$newFields = $MMSteps->getNewTableFieldColumns();
			while($row=$adb->fetchByAssoc($result)) {
				$webservice_field = WebserviceField::fromArray($adb,$row);
				//crmv@131239
				($row['uitype'] == 300) ? $uitype = 15 : $uitype = $row['uitype'];
				$column_fieldname = $row['fieldname'];
				$metaColumns = $adb->datadict->MetaColumns("{$table_prefix}_modlight{$modulelightid}");
				if ($metaColumns[strtoupper($column_fieldname)]->type == 'decimal') {
					$fieldno = $MMSteps->getNewFieldNoByUitype($uitype, 'decimals');
				} else {
					$fieldno = $MMSteps->getNewFieldNoByUitype($uitype);
				}
				//crmv@131239e
				$properties = array(
					'mandatory' => (strpos($row['typeofdata'],'~M') !== false) ? 1 : 0,
					'readonly' => $row['readonly'],
				);				
				$fieldProperties = $newFields[$fieldno]['properties'];
				if (!empty($fieldProperties)) {
					foreach($fieldProperties as $prop) {
						switch($prop){
							case 'label':
								$properties[$prop] = getTranslatedString($row['fieldlabel'],$modulelightname); // crmv@152807
								break;
							case 'length':
								$properties[$prop] = $metaColumns[strtoupper($column_fieldname)]->max_length;
								break;
							case 'decimals':
								$properties[$prop] = $metaColumns[strtoupper($column_fieldname)]->scale;
								break;
							case 'picklistvalues':
								$values_arr = getAssignedPicklistValues($column_fieldname, $current_user->roleid, $adb, $modulelightname);
								$properties[$prop] = implode("\n",$values_arr);
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
							//TODO 'autoprefix', 'onclick', 'code'
						}
					}
				}
				$tmp = $MMSteps->getNewFieldDefinition($fieldno, $properties, $i, true);
				if ($row['uitype'] == 300) $tmp['uitype'] = $row['uitype'];	//crmv@131239
				if ($tmp['uitype'] == 50) {
					require_once('modules/SDK/src/50/50.php');
					$tmp['selected_values'] = array_keys(getCustomUserList($modulelightname, $column_fieldname));
				}
				$tmp['fieldname'] = $column_fieldname;
				$tmp['fieldwstype'] = $webservice_field->getFieldDataType();
				$tmp['helpinfo'] = getTranslatedString($row['helpinfo'],$modulelightname); // crmv@199115
				$tmp['displaytype'] = $webservice_field->getDisplayType(); // crmv@206140
				$tmp['generatedtype'] = $webservice_field->getGeneratedType(); // crmv@206140
				$columns[] = $tmp;
				$i++;
			}
		}
		$column_cache[$module][$fieldname] = $columns;
		return $columns;
	}
	function getValues($module, $record, $fieldname, $columns) {
		global $adb, $table_prefix;
		$moduleLight = 'ModLight'.str_replace('ml','',$fieldname);
		
		$cols = array();
		$colsSql = '';
		foreach($columns as $c) {
			$cols[] = $c['fieldname'];
		}
		// crmv@198024
		if ($module == 'ConfProducts') {
			$cols[] = 'fieldid';
		}
		// crmv@198024e
		if (count($cols) > 0) {
			$colsSql = ", ".implode(',',$cols);
		}
		$focus = CRMEntity::getInstance($moduleLight);
		$result = $adb->pquery("select {$focus->tab_name_index[$focus->table_name]} $colsSql
			from {$focus->table_name}
			inner join {$focus->entity_table} on {$focus->entity_table}.{$focus->tab_name_index[$focus->entity_table]} = {$focus->tab_name_index[$focus->table_name]}
			where deleted = 0 and {$focus->table_name}.parent_id = ?
			order by {$focus->table_name}.seq", array($record));
		$values = array();
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$id = $row[$focus->tab_name_index[$focus->table_name]];
				unset($row[$focus->tab_name_index[$focus->table_name]]);
				$values[] = array('id'=>$id, 'row'=>$row);
			}
		}
		return $values;
	}
	function saveTableFields($parentFocus) {
		global $adb, $table_prefix, $table_fields;

		foreach($table_fields[$parentFocus->id] as $fieldname => $table_field) {
			$module = 'ModLight'.str_replace('ml','',$fieldname);
			$focus = CRMEntity::getInstance($module);
			
			$new_ids = array();
			$old_ids = array();
			$result = $adb->pquery("select {$focus->tab_name_index[$focus->table_name]}
				from {$focus->table_name}
				inner join {$focus->entity_table} on {$focus->entity_table}.{$focus->tab_name_index[$focus->entity_table]} = {$focus->tab_name_index[$focus->table_name]}
				where deleted = 0 and {$focus->table_name}.parent_id = ?", array($parentFocus->id));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result)) {
					$old_ids[] = $row[$focus->tab_name_index[$focus->table_name]];
				}
			}
			foreach($table_field as $seq => $row) {
				$id = $this->saveRow($module,$seq,$row,$parentFocus);
				$new_ids[] = $id;
				$table_fields[$parentFocus->id][$fieldname][$seq]['id'] = $id;
			}
			$delete_ids = array_diff($old_ids,$new_ids);
			if (!empty($delete_ids)) {
				foreach($delete_ids as $id) {
					$focus->trash($module,$id);
				}
			}
			unset($table_fields[$parentFocus->id][$fieldname]);
		}
	}
	function saveRow($module, $seq=0, $row, $parentFocus) {
		global $current_user; // crmv@154537
		$focus = CRMEntity::getInstance($module);
		if (empty($row['id'])) {
			// create
			$focus->mode = '';
		} else {
			// update
			$focus->mode = 'edit';
			$focus->id = $row['id'];
			$focus->retrieve_entity_info($row['id'],$module);
		}
		foreach($row['row'] as $fieldname => $value) {
			$focus->column_fields[$fieldname] = $value;
		}
		// crmv@154537
		if (empty($focus->column_fields['assigned_user_id'])) {
			$focus->column_fields['assigned_user_id'] = $parentFocus->column_fields['assigned_user_id'] ?: $current_user->id;
		}
		// crmv@154537e
		if (empty($focus->column_fields['parent_id'])) $focus->column_fields['parent_id'] = $parentFocus->id;
		$focus->column_fields['seq'] = $seq;
		$focus->save($module,false,false,false); // crmv@171524
		return $focus->id;
	}
	/*
	 * tabid: tab id of the field
	 * field: fieldid or fieldname
	 * operation: meta log operation
	 * version_tabid: tabid of the module to versionate
	 */
	function versionOperation($tabid, $field, $operation, $version_tabid=null) {
		// get metalogid and relate it to the new version
		global $adb, $table_prefix, $metaLogs;
		if (empty($version_tabid)) $version_tabid = $tabid;
		if (is_numeric($field)) {
			$fieldid = $field;
		} else {
			$result = $adb->pquery("select fieldid from {$table_prefix}_field where tabid = ? and fieldname = ?", array($tabid,$field));
			if ($result && $adb->num_rows($result) > 0) {
				$fieldid = $adb->query_result($result,0,'fieldid');
			}
		}
		if (!empty($fieldid)) {
			$result = $adb->limitpQuery("select logid from {$table_prefix}_meta_logs where objectid = ? and operation = ? order by logid desc",0,1,array($fieldid,$operation));
			if ($result && $adb->num_rows($result) > 0) {
				$metaLogId = $adb->query_result($result,0,'logid');
				if (!empty($metaLogId)) {
					$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
					$layoutBlockListUtils->versionOperation($version_tabid,$metaLogId);
				}
			}
		}
	}
}