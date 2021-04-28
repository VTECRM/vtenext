<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/Webservices/Utils.php');
require_once('include/Webservices/DescribeObject.php');
require_once('vtlib/Vtecrm/Link.php');

/* crmv@96233 crmv@102379 */

/**
 * This class manages wizards' configuration
 */
class WizardUtils extends SDKExtendableUniqueClass {

	public $table;
	
	public function __construct() {
		global $table_prefix;
		
		$this->table = $table_prefix.'_wizards';
	}
	
	/**
	 * Get the list of wizards
	 */
	public function getWizards($module = null, $enabled = null) {
		global $adb, $table_prefix;
		
		$ret = array();
		
		$params = array();
		$sql = "SELECT * FROM {$this->table}";
		$wheres = array();
		
		if (!empty($module)) {
			$wheres['tabid'] = getTabid($module);
		}
		if (!is_null($enabled)) {
			$wheres['enabled'] = intval($enabled);
		}
		
		if (count($wheres) > 0) {
			$sqlwhere = array();
			foreach ($wheres as $col => $val) {
				$sqlwhere[] = "$col = ?";
				$params[] = $val;
			}
			$sql .= " WHERE ".implode(" AND ", $sqlwhere);
		}
		
		$res = $adb->pquery($sql, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res,-1, false)) {
				$orow = $this->transformRowFromDb($row);
				$ret[] = $orow;
			}
		}
		return $ret;
	}
	
	/**
	 * Get informations about a single wizard. Returns false if the id is not found.
	 */
	public function getWizardInfo($wizardid) {
		global $adb, $table_prefix;
		
		$ret = false;
		$res = $adb->pquery("SELECT * FROM {$this->table} WHERE wizardid = ?", array($wizardid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res,-1, false);
			$ret = $this->transformRowFromDb($row);
		}
		return $ret;
	}
	
	/**
	 * Save a new Wizard
	 */
	public function insertWizard($data) {
		global $adb, $table_prefix;
		
		// check if existing
		$r = $adb->pquery("SELECT wizardid FROM {$this->table} WHERE name = ?", array($data['name']));
		if ($r && $adb->num_rows($r) > 0) {
			return intval($adb->query_result_no_html($r, 0, 'wizardid'));
		}

		$now = date('Y-m-d H:i:s');
		$wizardid = $adb->getUniqueID($this->table);
		
		$data = $this->transformRowToDb($data);
		$params = array(
			'wizardid' => $wizardid,
			'tabid' => $data['tabid'],
			'createdtime' => $now,
			'modifiedtime' => $now,
			'enabled' => 1,
			'name' => $data['name'],
			'description' => $data['description'],
			'src' => $data['src'],
			'template' => $data['template'],
		);
		$q = "INSERT INTO {$this->table} (".implode(',', array_keys($params)).") VALUES (".generateQuestionMarks($params).")";

		// insert the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			$jsonFields = array('config');
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table, $f, "wizardid = $wizardid", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		// add the link to parent modules
		$this->addLinkToParents($wizardid);
		
		return $wizardid;
	}
	
	/**
	 * Update a wizard
	 */
	public function updateWizard($wizardid, $data) {
		global $adb, $table_prefix;
		
		$now = date('Y-m-d H:i:s');
		$jsonFields = array('config');
		
		$data = $this->transformRowToDb($data);
		$data['modifiedtime'] = $now;
		
		unset($data['wizardid']);
		unset($data['createdtime']);
		
		$params = $data;
		foreach($jsonFields as $f) {
			unset($params[$f]);
		}
		
		$updSql = array();
		foreach ($params as $col => $val) {
			$updSql[] = "$col = ?";
		}
		$params['wizardid'] = $wizardid;
		
		$q = "UPDATE {$this->table} SET ".implode(', ', $updSql)." WHERE wizardid = ?";
		
		// update the row
		$res = $adb->pquery($q, $params);
		
		// update the long text fields
		if ($res) {
			foreach($jsonFields as $f) {
				if (isset($data[$f])) {
					$adb->updateClob($this->table, $f, "wizardid = $wizardid", $data[$f]);
				}
			}
		} else {
			return false;
		}
		
		// add or remove the link to parent modules
		$info = $this->getWizardInfo($wizardid);
		if ($info['enabled'] == 1) {
			$this->addLinkToParents($wizardid);
		} else {
			$this->removeLinkFromParents($wizardid);
		}
		
		return true;
	}
	
	/**
	 * Remove a wizard
	 */
	public function deleteWizard($wizardid) {
		global $adb;
		
		// remove the links
		$this->removeLinkFromParents($wizardid);
		
		// remove the saved line
		$adb->pquery("DELETE FROM {$this->table} WHERE wizardid = ?", array($wizardid));
			
		return true;
	}
	
	protected function transformRowFromDb($row) {
		$jsonFields = array('config');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::decode($row[$f]);
			}
		}
		$row['module'] = getTabname($row['tabid']);
		if ($row['config']['father']['field']) {
			// extract the parent modules
			$row['parentmodules'] = array();
			$tabid = getTabid($row['config']['module']);
			$parentField = $row['config']['father']['field'];
			$fieldid = getFieldid($tabid, $parentField);
			// crmv@108511
			if ($fieldid) {
				$rels = ModuleRelation::createFromFieldId($fieldid);
				if (is_array($rels)) {
					foreach ($rels as $relation) {
						$row['parentmodules'][] = $relation->getSecondModule();
					}
				}
			}
			// crmv@108511e
		}
		return $row;
	}
	
	protected function transformRowToDb($row) {
		$jsonFields = array('config');
		foreach($jsonFields as $f) {
			if (isset($row[$f])) {
				$row[$f] = Zend_Json::encode($row[$f]);
			}
		}
		return $row;
	}
	
	/**
	 * Add the link to the parent module to start the wizard from there
	 */
	public function addLinkToParents($wizardid) {
		$info = $this->getWizardInfo($wizardid);
		$mainMod = $info['module'];
		$parents = $info['parentmodules'];
		
		$label = getTranslatedString($info['name'], $mainMod);

		// add the links in listview - removed for now
		/*
		$moduleInstance = Vtecrm_Module::getInstance($mainMod);
		if ($moduleInstance) {
			Vtecrm_Link::addLink($moduleInstance->id, 'LISTVIEWBASIC', $label, "javascript:Wizard.openWizard('{$mainMod}',{$wizardid});");
		}
		*/

		if (!empty($parents) && $info['enabled'] == 1) {
			foreach ($parents as $module) {
				$tabid = getTabid($module);
				Vtecrm_Link::addLink($tabid, 'DETAILVIEWBASIC', $label, "javascript:Wizard.openWizard('$mainMod', $wizardid, '$module', '\$RECORD\$');");
			}
		}
	}
	
	/**
	 * Remove the link from the parent module
	 */
	public function removeLinkFromParents($wizardid) {
		$info = $this->getWizardInfo($wizardid);
		$mainMod = $info['module'];
		$parents = $info['parentmodules'];
		
		if (!empty($parents)) {
			$label = getTranslatedString($info['name'], $mainMod);
			foreach ($parents as $module) {
				$tabid = getTabid($module);
				Vtecrm_Link::deleteLink($tabid, 'DETAILVIEWBASIC', $label);
			}
		}
	}
	
	public function getAllParentModules() {
		global $adb, $table_prefix;
		
		$mods = array();
		
		$skipMods = array('MyNotes', 'Users', 'Emails', 'Myfiles', 'Charts', 'Messages', 'PBXManager', 'ModComments'); // crmv@164120 crmv@164122
		$res = $adb->pquery(
			"SELECT name FROM {$table_prefix}_tab 
			LEFT JOIN vte_hide_tab ON ".$table_prefix."_tab.tabid = vte_hide_tab.tabid 
			WHERE name NOT IN (".generateQuestionMarks($skipMods).") AND presence = 0 AND isentitytype = 1 AND 
			(hide_module_manager IS NULL OR hide_module_manager = 0)
			ORDER BY name",
			$skipMods
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$row['label'] = getTranslatedString($row['name'], $row['name']);
				$mods[] = $row;
			}
		}
		
		// sort by label
		usort($mods, function($v1, $v2) {
			return strcasecmp($v1['label'], $v2['label']);
		});
		
		return $mods;
	}
	
	public function getAllMainModules($parent = null) {
		if (empty($parent)) return $this->getAllParentModules();
		
		$mods = array();
		$skipMods = array('MyNotes', 'Users', 'Emails', 'Myfiles', 'Charts', 'Messages', 'PBXManager', 'ModComments'); // crmv@164120 crmv@164122
		
		// return all the modules with a N-1 relation with the parent
		$RM = RelationManager::getInstance();
		
		$rel = $RM->getRelations($parent, ModuleRelation::$TYPE_1TON, array(), $skipMods);
		if (is_array($rel)) {
			foreach ($rel as $relation) {
				$mod = $relation->getSecondModule();
				// at the moment, I don't handle same-module relations
				if ($mod != $parent) {
					$mods[] = array(
						'name' => $mod,
						'label' => getTranslatedString($mod, $mod),
						'fieldname' => $relation->fieldname,
					);
				}
			}
		}
		
		// sort by label
		usort($mods, function($v1, $v2) {
			return strcasecmp($v1['label'], $v2['label']);
		});
		
		return $mods;
	}
	
	// crmv@108511
	/**
	 * Insert the demo wizards
	 */
	public function populateDefaultWizards() {
		// this is a wizard with standard functions
		if (vtlib_isModuleActive('HelpDesk')) {
			$this->insertWizard(array(
				'name' => 'LBL_WIZARD_CREATE_TICKET',
				'tabid' => getTabid('HelpDesk'),
				'description' => 'Example wizard to create a trouble ticket',
				'config' => array(
					'type' => 'create',
					'module' => 'HelpDesk',
					'father' => array(
						'field' => 'parent_id',
						'mandatory' => true,
					),
					'fields' => array(
						array('name' => 'product_id'),
						array('name' => 'ticketpriorities'),
						array('name' => 'ticketcategories'),
						array('name' => 'ticket_title'),
						array('name' => 'description', 'mandatory' => true),
					),
					'relations' => array('Documents'),
				)
			));
			SDK::setLanguageEntry('APP_STRINGS', 'it_it', 'LBL_WIZARD_CREATE_TICKET', 'Crea ticket');
			SDK::setLanguageEntry('APP_STRINGS', 'en_us', 'LBL_WIZARD_CREATE_TICKET', 'Create ticket');		
		}
		// this is a complete custom wizard
		if (vtlib_isModuleActive('Potentials')) {
			$this->insertWizard(array(
				'name' => 'LBL_WIZARD_CREATE_POTENTIAL',
				'tabid' => getTabid('Potentials'),
				'description' => 'Example wizard to create a potential',
				'src' => 'modules/SDK/examples/wizards/new_potential.php',
				'template' => 'modules/SDK/examples/wizards/new_potential.tpl',
				// this config is not necessary for complete custom wizards
				'config' => array(
					'type' => 'create',
					'module' => 'Potentials',
					'father' => array(
						'field' => 'related_to',
						'mandatory' => true,
					),
					'fields' => array(
						array('name' => 'potentialname'),
						array('name' => 'amount'),
					),
					'relations' => array('Products'),
					
				)
			));
			SDK::setLanguageEntry('APP_STRINGS', 'it_it', 'LBL_WIZARD_CREATE_POTENTIAL', 'Crea Opportunità');
			SDK::setLanguageEntry('APP_STRINGS', 'en_us', 'LBL_WIZARD_CREATE_POTENTIAL', 'Create potential');
		}	
	}
	// crmv@108511e
	
}

/**
 * This class generates the necessary information to start a wizard
 */
class WizardGenerator extends SDKExtendableUniqueClass {

	public function getAvailableFields($module) {
		global $current_user;
		
		$modinfo = vtws_describe($module, $current_user);
		if (!$modinfo || !$modinfo['fields']) return false;
		
		// index by fieldname
		$fields = array();
		foreach ($modinfo['fields'] as $field) {
			$fields[$field['name']] = $field;
		}
		
		return $fields;
	}
	
	public function getAvailableRelations($module) {

		$RM = RelationManager::getInstance();
		
		$skipMods = array('ModComments', 'Messages', 'MyNotes'); // crmv@164120 crmv@164122
		$rels = $RM->getRelations($module, ModuleRelation::$TYPE_NTON, array(), $skipMods);
		return $rels;
	}

	/**
	 * Generate the wizard steps
	 */
	public function generateWizardSteps($wizardid, $cfg = null, $params = array())  {
		global $current_user;
		
		$module = $cfg['module'];
		
		if ($cfg['type'] != 'create') {
			throw new Exception("Type {$cfg['type']} is not supported");
		}
		
		$steps = array();
		$jsinfo = array();
		$RM = RelationManager::getInstance();
		
		// by name
		$createFields = array();
		foreach ($cfg['fields'] as $finfo) {
			$createFields[$finfo['name']] = $finfo;
		}
		
		// get module info
		$fields = $this->getAvailableFields($module);
		
		// add mandatory fields
		foreach ($fields as $fieldname => $field) {
			if ($field['mandatory']) {
				$createFields[$fieldname] = array(
					'name' => $field['name'],
					'mandatory' => $field['mandatory'],
				);
			}
		}
		
		// check for a father, this is the first step
		if ($cfg['father'] && $cfg['father']['field']) {
			$fatherField = $cfg['father']['field'];
			$fatherMods = $fields[$fatherField]['type']['refersTo'];

			$lists = array();
			foreach ($fatherMods as $mod) {
				$Slv = SimpleListView::getInstance($mod);
				$Slv->entriesPerPage = 10;
				$Slv->showCreate = false;
				$Slv->showSuggested = false;
				$Slv->showCheckboxes = false;
				$Slv->extraButtonsHTML = '';
				$Slv->selectFunction = 'Wizard.recordSelect1';

				$lists[$mod] = array(
					'module' => $mod,
					'list' => $Slv->render(),
					'listid' => $Slv->listid,
					'label' => getTranslatedString('LBL_CHOOSE').' '.getTranslatedString('SINGLE_'.$mod),
				);
			}
			
			$labelMod = $fatherMods[0];
			
			if ($fields[$fatherField]['mandatory']) {
				$fatherMand = true;
			} elseif (isset($cfg['father']['mandatory'])) {
				$fatherMand = $cfg['father']['mandatory'];
			} else {
				$fatherMand = false;
			}
			
			$steps[] = array(
				'type' => 'select',
				'mode' => 'exclusive',
				'label' => getTranslatedString('LBL_CHOOSE').' '.getTranslatedString('SINGLE_'.$labelMod),
				'mandatory' => $fatherMand,
				'modules' => $fatherMods,
				'field' => $fatherField,
				'fieldid' => $fields[$fatherField]['fieldid'],
				'lists' => $lists,
				'parent' => true,
			);
			unset($createFields[$fatherField]);
		}

		// now add steps for related fields
		foreach ($createFields as $fieldname => $finfo) {
			if ($fields[$fieldname]['type']['name'] == 'reference') {
				if ($fields[$fieldname]['uitype'] == 52) continue;    // crmv@171425
				$referMods = $fields[$fieldname]['type']['refersTo'];
				//crmv@168618 - skip fake modules
				if (in_array('Currency', $referMods)) {
					continue;
				}
				//crmv@168618e
				
				$lists = array();
				foreach ($referMods as $mod) {
					$Slv = SimpleListView::getInstance($mod);
					$Slv->entriesPerPage = 10;
					$Slv->showCreate = false;
					$Slv->showSuggested = false;
					$Slv->showCheckboxes = false;
					$Slv->extraButtonsHTML = '';
					$Slv->selectFunction = 'Wizard.recordSelect1';

					$lists[$mod] = array(
						'module' => $mod,
						'list' => $Slv->render(),
						'listid' => $Slv->listid,
						'label' => getTranslatedString('LBL_CHOOSE').' '.getTranslatedString('SINGLE_'.$mod),
					);
				}
				
				$labelMod = $referMods[0];
			
				$steps[] = array(
					'type' => 'select',
					'mode' => 'exclusive',
					'label' => getTranslatedString('LBL_CHOOSE').' '.getTranslatedString($fields[$fieldname]['label'],$module),
					'mandatory' => $fields[$fieldname]['mandatory'] || $finfo['mandatory'],
					'modules' => $referMods,
					'field' => $fieldname,
					'fieldid' => $fields[$fieldname]['fieldid'],
					'lists' => $lists
				);

				// remove from fields to be filled
				unset($createFields[$fieldname]);
			}
		}
		
		// now steps for relations
		if (is_array($cfg['relations'])) {
			foreach ($cfg['relations'] as $mod) {
				$lists = array();

				$Slv = SimpleListView::getInstance($mod);
				$Slv->entriesPerPage = 10;
				$Slv->showCreate = false;
				$Slv->showSuggested = false;
				$Slv->showCheckboxes = false;
				$Slv->extraButtonsHTML = '';
				$Slv->selectFunction = 'Wizard.recordSelect';

				$lists[$mod] = array(
					'module' => $mod,
					'list' => $Slv->render(),
					'listid' => $Slv->listid
				);

				$steps[] = array(
					'type' => 'select',
					'mode' => 'multiple',
					'label' => getTranslatedString('LBL_CHOOSE').' '.getTranslatedString($mod),
					'mandatory' => false,
					'modules' => array($mod),
					'lists' => $lists
				);
			}
		}

		// now the final creation step
		if (count($createFields) > 0) {
			$stepFields = array();
			foreach ($createFields as $fname => $finfo) {
				$cfg = $fields[$fname];
				if (!$cfg) continue;
				$tplcfg = $this->getTplFieldConfig($wizardid, $module, $cfg, $finfo);
				$stepFields[] = $tplcfg;
			}
			
			$steps[] = array(
				'label' => getTranslatedString('LBL_FIELDS', 'APP_STRINGS').' '.getTranslatedString('SINGLE_'.$module),
				'type' => 'create',
				'module' => $module,
				'mandatory' => true,
				'fields' => $stepFields,
			);
		}
		
		$jsinfo = array('config' => $cfg, 'steps' => $steps);
		foreach ($jsinfo['steps'] as &$s) {
			if (is_array($s['lists'])) {
				foreach ($s['lists'] as &$list) {
					unset($list['list']);
				}
			}
		}
		
		$wizard = array(
			'jsinfo' => Zend_Json::encode($jsinfo),
			'steps' => $steps
		);
		
		return $wizard;
	}
	
	protected function getTplFieldConfig($wizardid, $module, $cfg, $savedInfo = array()) {
		global $adb, $table_prefix;
		global $current_user;
		
		require('user_privileges/requireUserPrivileges.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
		
		if ($cfg['type']['name'] == 'date') {
			$cfg['secondvalue'] = array($cfg['type']['format']);
		} elseif (in_array($cfg['type']['name'], array('picklist', 'multipicklist'))) { // crmv@128382
			if (!empty($cfg['type']['name'])) {
				$value = array();
				foreach($cfg['type']['picklistValues'] as $v) {
					($v['value'] == $cfg['type']['defaultValue']) ? $selected = 'selected' : $selected = '';
					$value[] = array($v['label'],$v['value'],$selected);
				}
				$cfg['value'] = $value;
			}
		} elseif ($cfg['type']['name'] == 'owner') {
		
			$tabid = getTabid($module);
			$assigned_user_id = $current_user->id;
			
			if(!is_admin($current_user) && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$tabid] == 3 || $defaultOrgSharingPermission[$tabid] == 0)) {
				$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
			} else {
				$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id), $assigned_user_id);
			}

			if(!is_admin($current_user) && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$tabid] == 3 || $defaultOrgSharingPermission[$tabid] == 0)) {
				$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
			} else {
				$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_user_id), $assigned_user_id);
			}

			$cfg['value'] = $users_combo;
			$cfg['secondvalue'] = $groups_combo;
		// crmv@171425
		} elseif($cfg['uitype'] == 52) {
		    $tabid = getTabid($module);
		    $assigned_user_id = $current_user->id;
		    
		    if(!is_admin($current_user) && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$tabid] == 3 || $defaultOrgSharingPermission[$tabid] == 0)) {
		        $users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
		    } else {
		        $users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id), $assigned_user_id);
		    }
		    $cfg['value'] = $users_combo;
		// crmv@171425e
		// crmv@168618
		} elseif ($cfg['type']['name'] == 'reference' && $cfg['type']['refersTo'][0] == 'Currency') {
			$value = array();
			$curinfo = getDisplayCurrency();
			foreach ($curinfo as $curid => $curname) {
				($curid == $cfg['type']['defaultValue']) ? $selected = 'selected' : $selected = '';
				$value[$curid] = array($curname => $selected);
			}
			$cfg['value'] = $value;
		}
		// crmv@168618e
		
		// retrieve tod
		$res = $adb->pquery("select typeofdata from {$table_prefix}_field where fieldid = ?", array($cfg['fieldid']));
		if ($res && $adb->num_rows($res) > 0) {
			$cfg['fielddatatype'] = $adb->query_result_no_html($res, 0, 'typeofdata');
		}
		
		if ($savedInfo) {
			if ($savedInfo['mandatory']) {
				$cfg['mandatory'] = true;
			}
		}
		
		return $cfg;
	}
	
}

/**
 * This class process a single wizard instance
 */
class WizardHandler extends SDKExtendableUniqueClass {

	public $wizardid;
	public $wizardInfo;
	
	protected $wu;
	
	public function __construct($wizardid) {
		$this->wu = WizardUtils::getInstance();
		$this->wizardid = $wizardid;
		$this->wizardInfo = $this->wu->getWizardInfo($wizardid);
	}
	
	public function saveWizard(&$request) {
		global $current_user;
		global $adb, $table_prefix, $currentModule;
		
		$info = $this->wizardInfo['config'];

		if ($info['type'] != 'create') return $this->error('This type of wizard is not supported');
		$ret = array();
		
		$currentModule = $info['module'];
		$focus = CRMEntity::getInstance($currentModule);
		$focus->mode = '';
		
		// populate the fields
		$fields = $this->getCreateValues($request);
		
		if ($fields) {
			$focus->column_fields = array_merge($focus->column_fields, $fields);
		}
		if (empty($focus->column_fields['assigned_user_id'])) {
			$focus->column_fields['assigned_user_id'] = $current_user->id;
		}
		
		// save
		$focus->save($currentModule);
		if (!$focus->id) return $this->error('Unable to save the record');
		
		// save the relations
		$r = $this->saveRelations($focus, $request);
		if (!$r) {
			return $this->error('Unable to save the relations with the record');
		}
		
		// TODO: use a config option to decide the url, or the redirect
		$ret['url'] = "index.php?module=$currentModule&action=DetailView&record=".$focus->id;
		
		// TODO: support the direct upload of documents, wait until the filestorage is active
		/*$focus = CRMEntity::getInstance('HelpDesk');
		if (!empty($forms['nlw_RecordFields'])) {
			foreach ($forms['nlw_RecordFields'] as $field) {
				$focus->column_fields[$field['name']] = $field['value'];
			}
			$focus->column_fields['sales_stage'] = 'Open';
			$focus->column_fields['assigned_user_id'] = $current_user->id;
			if (!empty($selectedRecords[6])) {
				$focus->column_fields['parent_id'] = $selectedRecords[6][0];
			}
			if (!empty($selectedRecords[14])) {
				$focus->column_fields['product_id'] = $selectedRecords[14][0];
			}
			$focus->save('HelpDesk');
			if ($focus->id) {
				$uniqueid = '';
				foreach ($forms['documentlist'] as $field) {
					if ($field['name'] == 'documentlist_uniqueid') {
						$uniqueid = $field['value'];
						break;
					}
				}
				if (!empty($uniqueid)) {
					$tmpdir = '/tmp/vte_wizard_upload_'.$uniqueid.'/';
					$files = array();
					if ($handle = opendir($tmpdir)) {
						while (false !== ($entry = readdir($handle))) {
							if ($entry != "." && $entry != "..") {
								$focusDoc = CRMEntity::getInstance('Documents');
								$filetype_fieldname = $focusDoc->getFileTypeFieldName();
								$filename_fieldname = $focusDoc->getFile_FieldName();
								$_REQUEST[$filename_fieldname.'_hidden'] = $entry;
								$_POST['copy_not_move'] = true;
								$_FILES = array($filename_fieldname => array(
									'name' => $entry,
						            'type' => pathinfo($entry, PATHINFO_EXTENSION),
						            'tmp_name' => $tmpdir.$entry,
						            'error' => 0,
						            'size' => filesize($tmpdir.$entry),
								));
								$focusDoc->column_fields['notes_title'] = $entry;
								$focusDoc->column_fields['filename'] = $entry;
								$focusDoc->column_fields['folderid'] = 1;
								$focusDoc->column_fields[$filetype_fieldname] = 'I';
								$focusDoc->column_fields['filestatus'] = 1;
								$focusDoc->column_fields['assigned_user_id'] = $current_user->id;
								$focusDoc->save('Documents');
								$focus->save_related_module('HelpDesk', $focus->id, 'Documents', $focusDoc->id);
							}
						}
						closedir($handle);
						@FSUtils::deleteFolder($tmpdir);
					}
				}
				if ($focus->id) $ret['success'] = '1';
				$ret['url'] = 'index.php?module=HelpDesk&action=DetailView&record='.$focus->id;
			}
		}*/
		
		return $ret;
	}
	
	protected function getCreateValues(&$request) {
		$fields = array();
		
		$forms = Zend_Json::decode($request['forms']);
		if (is_array($forms['EditView'])) {
			$fields = array_filter($forms['EditView']);
		}
		return $fields;
	}
	
	protected function saveRelations($focus, &$request) {
		$relations = Zend_Json::decode($request['selectedRecords']);
		
		if (is_array($relations)) {
			foreach ($relations as $rel) {
				if ($rel && vtlib_isModuleActive($rel['module']))
				$focus->save_related_module($focus->modulename, $focus->id, $rel['module'], $rel['crmid']);
			}
		}
		
		return true;
	}
	
	protected function error($str) {
		throw new Exception($str);
		return false;
	}
	
}