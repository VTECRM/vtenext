<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@83340 crmv@96155 crmv@98431 crmv@102379 crmv@102334 crmv@105882 */

require_once('include/ListView/SimpleListView.php');
require_once('include/utils/WizardUtils.php'); // crmv@96233
require_once('vtlib/Vtecrm/Module.php');

class ModuleHomeView extends SDKExtendableClass {

	public $table;
	public $table_blocks;
	protected $userid;
	protected $module;
	
	protected $tabid;
	
	public function __construct($module, $userid = null) {
		global $table_prefix, $current_user;
		
		$this->table = $table_prefix.'_modulehome';
		$this->table_blocks = $table_prefix.'_modulehome_blocks';
		
		$this->module = $module;
		$this->userid = $userid ?: $current_user->id ?: 0;
		
		$moduleInstance = Vtecrm_Module::getInstance($module);
		$this->tabid = $moduleInstance->id;
	}
	
	static function install($module=null, $userid=null) {
		global $adb, $table_prefix;
		
		if (!empty($userid)) {
			$users = array($userid);
		} else {
			$users = array();
			$result = $adb->query("SELECT id FROM {$table_prefix}_users");
			if ($result && $adb->num_rows($result) > 0) while($row=$adb->fetchByAssoc($result)) $users[] = $row['id'];			
		}

		// per ogni modulo con almeno il filtro All
		$query = "SELECT cvid, entitytype FROM {$table_prefix}_customview WHERE viewname = ?";
		$params = array('All');
		if (!empty($module)) {
			$query .= " AND entitytype = ?";
			$params[] = $module;
		}
		$result = $adb->pquery($query, $params);
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$cvid = $row['cvid'];
				$module = $row['entitytype'];
				$moduleInstance = Vtecrm_Module::getInstance($module);
				if (!$moduleInstance) continue; // crmv@169785
				foreach($users as $userid) {
					$MHW = ModuleHomeView::getInstance($module, $userid);
					$check = $adb->pquery("select modhomeid from {$MHW->table} where userid = ? and tabid = ? and cvid is not null and cvid > 0", array($userid,$moduleInstance->id));
					if ($adb->num_rows($check) == 0) {
						// check if the user has a default filter
						if (Vtecrm_Utils::CheckTable($table_prefix.'_user_module_preferences')) {
							$default_res = $adb->pquery("SELECT default_cvid FROM {$table_prefix}_user_module_preferences WHERE userid = ? AND tabid = ?", array($userid,$moduleInstance->id));
							if ($default_res && $adb->num_rows($default_res) > 0) {
								$MHW->insertView(array(
									'name'=>'LBL_LIST',
									'cvid'=>$adb->query_result($default_res,0,'default_cvid'),
								));
								continue;
							}
						}
						// else use the filter All
						$MHW->insertView(array(
							'name'=>'LBL_LIST',
							'cvid'=>$cvid,
						));
					}
				}
			}
		}
	}
	
	public function setModule($module) {
		$this->module = $module;
		$moduleInstance = Vtecrm_Module::getInstance($module);
		$this->tabid = $moduleInstance->id;
	}
	
	public function setUserid($userid) {
		$this->userid = $userid;
	}
	
	function getModHomeId($views=array(), $saveId = true) { // crmv@123718
		$modhomeid = null;

		if (isset($_REQUEST['modhomeid']) && !empty($_REQUEST['modhomeid']) && $_REQUEST['modhomeid'] != 'undefined') {
			$modhomeid = intval($_REQUEST['modhomeid']);
		} elseif (VteSession::hasKeyArray(array('modhomeids', $this->module))) {
			$modhomeid = VteSession::getArray(array('modhomeids', $this->module));
		} elseif(!empty($views)) {
			$modhomeid = $views[0]['modhomeid'];
		}
		// crmv@123718
		if ($saveId) {
			VteSession::setArray(array('modhomeids', $this->module), $modhomeid);
		}
		// crmv@123718e
		
		return $modhomeid;
	}
	
	// return the cvid of the current tab (if it is a tab of list)
	function getModHomeCvId() {
		global $adb, $table_prefix;
		$modhomeid = $this->getModHomeId(array(), false); // crmv@128133
		if (!empty($modhomeid)) {
			$result = $adb->pquery("select cvid from {$this->table} where modhomeid = ? and userid = ? and tabid =?", array($modhomeid, $this->userid, $this->tabid));
			if ($result && $adb->num_rows($result) > 0) {
				$viewid = $adb->query_result($result,0,'cvid');
				if (!empty($viewid)) return $viewid;
			}
		}
		return false;
	}
	
	// crmv@151995
	function getModHomeListViewEntries() {
		global $adb, $table_prefix;
		$modhomeid = $this->getModHomeId(array(), false);
		if (!empty($modhomeid)) {
			$result = $adb->pquery("select entries from {$this->table} where modhomeid = ? and userid = ? and tabid = ?", array($modhomeid, $this->userid, $this->tabid));
			if ($result && $adb->num_rows($result) > 0) {
				$entries = $adb->query_result($result,0,'entries');
				if (!empty($entries)) return $entries;
			}
		}
		return false;
	}
	// crmv@151995e
	
	function setModHomeListId() {
		$views = $this->getViews(false);
		$modhomeid = $this->getModHomeId($views, false); // crmv@128133
		$currentviewid = '';
		foreach ($views as $view) {
			if (empty($currentviewid) && intval($view['cvid']) > 0) $currentviewid = $view['modhomeid'];	// save the first tab with list
			if ($view['modhomeid'] == $modhomeid && intval($view['cvid']) > 0) {	// check if in session there is a tab with list
				$currentviewid = $view['modhomeid'];
				break;
			}
		}
		// if do not exists tab with list create a default one
		if (empty($currentviewid)) {
			$currentviewid = $this->setDefaultListViewTab();
		}
		VteSession::setArray(array('modhomeids', $this->module), $currentviewid);
		return $currentviewid;
	}
	
	function setDefaultListViewTab() {
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE entitytype = ? AND viewname = ?", array($this->module,'All'));
		if ($result && $adb->num_rows($result) > 0) {
			return $this->insertView(array(
				'name'=>'LBL_LIST',
				'cvid'=>$adb->query_result($result,0,'cvid'),
			));
		}
	}

	// crmv@115445
	/**
	 * Called when a filter is deleted. Delete all blocks with that filter id
	 */
	public function handleRemoveFilter($cvid) {
		global $adb;
		
		$blockids = array();
		$res = $adb->pquery(
			"SELECT tb.blockid, tb.title, tb.config 
			FROM {$this->table_blocks} tb
			WHERE tb.type = ?",
			array('Filter')
		);
		if ($res && $adb->num_rows($res) > 0) {
			// collect the ids
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$this->processBlockRowFromDb($row);
				if ($row['config']['cvid'] == $cvid) {
					$blockids[] = $row['blockid'];
				}
			}
			// now remove the blocks
			if (count($blockids) > 0) {
				$adb->pquery("DELETE FROM {$this->table_blocks} WHERE blockid IN (".generateQuestionMarks($blockids).")", $blockids);
			}
		}
	}
	// crmv@115445e
	
	public function getViews($withBlocks = false) {
		global $adb;
		
		$views = array();
		$res = $adb->pquery(
			"SELECT t.* 
			FROM {$this->table} t
			WHERE t.userid IN (?,?) AND t.tabid = ?
			ORDER BY modhomeid ASC", // make the order consistent
			array(0, $this->userid, $this->tabid)
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				if ($row['name'] == 'All' && intval($row['cvid']) > 0) {
					$row['name'] = getTranslatedString('all', 'APP_STRINGS');
				} elseif ($row['name'] == 'LBL_LIST' && intval($row['cvid']) > 0) {
					$row['name'] = getTranslatedString('LBL_LIST');
				//crmv@115445
				} elseif (strpos($row['name'],'LBL_') !== false && intval($row['userid']) == 0) {
					$row['name'] = getTranslatedString($row['name']);
				}
				//crmv@115445e
				if ($withBlocks) {
					$row['blocks'] = $this->getBlocks($row['modhomeid']);
					$row['blockids'] = array_keys($row['blocks']);
				}
				$views[] = $row;
			}
		}
		
		return $views;
	}
	
	public function getBlocks($homeid) {
		global $adb;
		
		$blocks = array();
		$res = $adb->pquery(
			"SELECT tb.* 
			FROM {$this->table_blocks} tb
			WHERE tb.modhomeid = ?
			ORDER BY tb.sequence ASC",
			array($homeid)
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$this->processBlockRowFromDb($row);
				$check = $this->filterRow($row);
				if ($check) {
					$blocks[$row['blockid']] = $row;
				}
			}
		} else {
			// this is not a good idea
			//$blocks = $this->getDefaultBlocks();
		}
		
		return $blocks;
	}
	
	// Get default blocks for the module. These are the initial blocks, not associated with any user
	public function getDefaultBlocks() {
		global $adb;
		
		$blocks = array();
		$res = $adb->pquery(
			"SELECT tb.* 
			FROM {$this->table} t
			INNER JOIN {$this->table_blocks} tb ON t.modhomeid = tb.modhomeid
			WHERE t.userid = ? AND t.tabid = ?
			ORDER BY tb.sequence ASC",
			array(0, $this->tabid)
		);
		
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$row['config'] = Zend_Json::decode($row['config']);
				$blocks[$row['blockid']] = $row;
			}
		}
		
		return $blocks;
	}
	
	public function getBlockInfo($homeid, $blockid) {
		global $adb;
		
		$block = array();
		$res = $adb->pquery(
			"SELECT tb.* 
			FROM {$this->table_blocks} tb
			WHERE tb.modhomeid = ? AND blockid = ?",
			array($homeid, $blockid)
		);
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			$this->processBlockRowFromDb($row);
			$check = $this->filterRow($row);
			if ($check) {
				$block = $row;
			}
		}
		
		return $block;
	}
	
	protected function filterRow($row) {
		$type = $row['type'];
		if ($type == 'Chart' && !vtlib_isModuleActive('Charts')) return false;
		
		return true;
	}
	
	protected function processBlockRowFromDb(&$row) {
		if (isset($row['config'])) {
			$row['config'] = Zend_Json::decode($row['config']);
		} else {
			$row['config'] = array();
		}
		$type = $row['type'];
		
		if (empty($row['title'])) {
			$row['title'] = $this->getBlockTitle($row);
		}
	}
	
	protected function processBlockRowToDb(&$row) {
		if (isset($row['config'])) {
			$row['config'] = Zend_Json::encode($row['config']);
		}
	}
	
	protected function getBlockTitle($blockinfo) {
		global $adb, $table_prefix;
		
		$type = $blockinfo['type'];

		$title = '';
		if ($type == 'Chart') {
			$chartid = intval($blockinfo['config']['chartid']);
			if ($chartid > 0) {
				$title = getEntityName('Charts', array($chartid), true);
			}
		} elseif ($type == 'Filter' || $type == 'QuickFilter') {
			$cvid = intval($blockinfo['config']['cvid']);
			if ($cvid > 0) {
				$title = getSingleFieldValue($table_prefix.'_customview', 'viewname', 'cvid', $cvid);
				if ($title == 'All') $title = getTranslatedString('all', 'APP_STRINGS');
			}
		}
		
		return $title;
	}
	
	public function insertView($viewinfo) {
		global $adb;
		
		$homeid = $adb->getUniqueId($this->table);
		
		$params = array(
			'modhomeid' => $homeid,
			'userid' => $this->userid,
			'tabid' => $this->tabid,
			'name' => $viewinfo['name'],
		);
		
		if ($viewinfo['reportid'] > 0) {
			$params['reportid'] = intval($viewinfo['reportid']);
		} elseif ($viewinfo['cvid'] > 0) {
			$params['cvid'] = intval($viewinfo['cvid']);
		}
		
		$cols = array_keys($params);
		$adb->format_columns($cols);
		$adb->pquery("INSERT INTO {$this->table} (".implode(', ',$cols).") VALUES (".generateQuestionMarks($params).")", $params);
		
		return $homeid;
	}

	//crmv@199319
    public function editView($viewinfo) {
        global $adb;

        $params = array(
            'name' => $viewinfo['name'],
            'reportid' => $viewinfo['reportid'],
            'cvid' => $viewinfo['cvid'],
        );

        $homeid = $viewinfo['homeid'];
        if(empty($homeid) || $homeid <= 0) return -1;

        foreach($params as $column => $value)
        {
            if(empty($value) || (is_int($value) && $value <= 0)) continue;
            $adb->pquery("UPDATE {$this->table} SET {$column}=? WHERE modhomeid = ?", array($value, $homeid));
        }
        return $homeid;
    }
    //crmv@199319e
	
	public function insertBlock($homeid, $blockinfo) {
		global $adb;
		
		$blockid = $adb->getUniqueId($this->table_blocks);
		
		$params = array_merge($blockinfo, array(
			'blockid' => $blockid,
			'modhomeid' => $homeid,
		));
		$this->processBlockRowToDb($params);
		$cols = array_keys($params);
		$adb->format_columns($cols);
		$adb->pquery("INSERT INTO {$this->table_blocks} (".implode(', ',$cols).") VALUES (".generateQuestionMarks($params).")", $params);
		
		return $blockid;
	}
	
	public function reorderBlocks($homeid, $blockids) {
		global $adb;
		
		$seq = 1;
		foreach ($blockids as $blockid) {
			$adb->pquery("UPDATE {$this->table_blocks} SET sequence = ? WHERE modhomeid = ? AND blockid = ?", array($seq++, $homeid, $blockid));
		}
		
		return true;
	}
	
	//crmv@106616
	public function setKanabaView($homeid, $kanban) {
		global $adb;
		$adb->pquery("UPDATE {$this->table} SET kanban = ? WHERE modhomeid = ?", array($kanban, $homeid));
	}
	//crmv@106616e
	
	// crmv@151995
	public function setModHomeEntries($homeid, $entries) {
		global $adb;
		$entries = intval($entries);
		$adb->pquery("UPDATE {$this->table} SET entries = ? WHERE modhomeid = ? and userid = ? and tabid = ?", array($entries, $homeid, $this->userid, $this->tabid));
	}
	// crmv@151995e
	
	public function deleteView($homeid) {
		global $adb;
		
		$adb->pquery("DELETE FROM {$this->table} WHERE modhomeid = ?", array($homeid));
		$adb->pquery("DELETE FROM {$this->table_blocks} WHERE modhomeid = ?", array($homeid));
		
		if ($homeid == VteSession::getArray(array('modhomeids', $this->module))) VteSession::removeArray(array('modhomeids', $this->module));	//crmv@104259
	}
	
	public function deleteBlock($homeid, $blockid) {
		global $adb;
		
		$adb->pquery("DELETE FROM {$this->table_blocks} WHERE modhomeid = ? AND blockid = ?", array($homeid, $blockid));
	}
	
	public function deleteAllViews() {
		global $adb;
		
		$adb->query("DELETE FROM {$this->table}");
		$adb->query("DELETE FROM {$this->table_blocks}");
	}

	public function getSupportedBlocks() {
		$list = array();
		
		if (isModuleInstalled('Charts') && vtlib_isModuleActive('Charts')) {
			$list[] = array(
				'type' => 'Chart',
				'label' => getTranslatedString('SINGLE_Charts', 'Charts'),
			);
		}
		
		$list[] = array(
			'type' => 'QuickFilter',
			'label' => getTranslatedString('QuickFilter', 'APP_STRINGS'),
		);
		
		$list[] = array(
			'type' => 'Filter',
			'label' => getTranslatedString('LBL_FILTER', 'APP_STRINGS'),
		);
		
		$list[] = array(
			'type' => 'Wizards',
			'label' => getTranslatedString('Wizard', 'APP_STRINGS'),
		);
		
		// Not implemented yet!
		/*if (isModuleInstalled('Processes') && vtlib_isModuleActive('Processes')) {
			$list[] = array(
				'type' => 'Processes',
				'label' => getTranslatedString('SINGLE_Processes', 'Processes'),
			);
		}*/
		
		return $list;
	}
	
	public function getBlockContent($homeid, $blockid, $options = array()) {
		global $adb;
		
		$out = null;
		$blockinfo = $this->getBlockInfo($homeid, $blockid);
		$type = $blockinfo['type'];
		
		switch ($type) {
			case 'Chart':
				$out = $this->getChartContent($blockinfo, $options['reload']);
				break;
			case 'QuickFilter':
				$out = $this->getQuickFilterContent($blockinfo);
				break;
			case 'Filter':
				$out = $this->getFilterContent($blockinfo);
				break;
			case 'Wizards':
				$out = $this->getWizardsContent($blockinfo);
				break;
			case 'Processes':
				$out = $this->getProcessesContent($blockinfo);
				break;
			default:
				$out = 'Block type not supported';
				break;
		}
		
		return $out;
	}
	
	protected function getChartContent($blockinfo, $forceReload = false) {
		$blockid = $blockinfo['blockid'];
		$size = $blockinfo['size'];
		$config = $blockinfo['config'];
		
		$out = '';
		$chid = $config['chartid'];
		if (!empty($chid)) {
			$chartInst = CRMEntity::getInstance('Charts');
			//$chartInst->setCacheField('chart_file_home');
			$chartInst->retrieve_entity_info($chid, 'Charts');
			$chartInst->homestuffid = $blockid;
	
			$chartInst->homestuffsize = $size;
			if ($forceReload) {
				$chartInst->reloadReport(); // when clicking on reload, reload report data
			}
			$out = $chartInst->renderModuleHomeBlock();
		}
		
		return $out;
	}
	
	protected function getQuickFilterContent($blockinfo) {
		$blockid = $blockinfo['blockid'];
		$size = $blockinfo['size'];
		$config = $blockinfo['config'];
		
		$out = '';
		$cvid = intval($config['cvid']);
		$cols = $config['columns'];	// number of columns to show. omit it or use "auto" to show 2 * size columns
		if (!empty($cvid)) {
			$Slv = SimpleListView::getInstance($this->module);
			$Slv->listid = $blockid;
			$Slv->setViewId($cvid);
			$Slv->entriesPerPage = 10;
			$Slv->maxFields = (empty($cols) || $cols == 'auto' ? 2*$size : $cols);
			$Slv->showSearch = false;
			$Slv->showSuggested = false;
			$Slv->showCreate = false;
			$Slv->showCheckboxes = false;
			$Slv->showFilters = false;
			$Slv->showNavigation = false;
			$Slv->showSorting = false;
			$Slv->showExtraFieldsRow = false;
			$Slv->selectFunction = 'ModuleHome.clickRecord';
			// now render the list
			$out = $Slv->render();
		}
		
		return $out;
	}
	
	public function getFilterContent($blockinfo) {
		$blockid = $blockinfo['blockid'];
		$size = $blockinfo['size'];
		$config = $blockinfo['config'];

		$out = '';
		$cvid = intval($config['cvid']);
		if (!empty($cvid)) {
			$_REQUEST['viewname'] = $cvid;
			$_REQUEST['ajax'] = 'true';
			$_REQUEST['fetch_only'] = true;
			$_REQUEST['hide_switchview_buttons'] = '1';
			$_REQUEST['hide_list_checkbox'] = '1';
			$_REQUEST['hide_custom_links'] = '1';
			$_REQUEST['hide_cv_follow'] = '1';
			include('modules/VteCore/ListView.php');
			$outputHtml = str_replace('&#&#&#', '', $outputHtml);
			$smarty->assign('LISTVIEWHTML', $outputHtml);
			$smarty->assign('BLOCK', $blockinfo);
			$out = $smarty->fetch('ModuleHome/ListView.tpl');
		}
		
		return $out;
	}
	
	// crmv@96233
	protected function getWizardsContent($blockinfo) {
		global $app_strings, $mod_strings;
		
		$config = $blockinfo['config'];
		if (is_array($config['wizardids']) && count($config['wizardids']) > 0) {
			// show the selected wizards
			$wizardids = array_filter(array_map('intval', $config['wizardids']));
		} else {
			// show wizards of the current module
			$wizardids = null;
		}
		
		$smarty = new VteSmarty();
		
		$smarty->assign('MODULE', $this->module);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('BLOCK', $blockinfo);
		
		$WU = WizardUtils::getInstance();
		
		$wizards = $WU->getWizards($wizardids ? null : $this->module, true);
		
		$buttons = array();
		foreach ($wizards as $wiz) {
			$wizardid = $wiz['wizardid'];
			// skip if not listed
			if ($wizardids && !in_array($wizardid, $wizardids)) continue;
			$buttons[$wizardid] = array(
				'wizardid' => $wizardid,
				'module' => $this->module,
				'title' => getTranslatedString($wiz['name']),
				'handler' => "Wizard.openWizard('{$this->module}', $wizardid);"
			);
		}
		$smarty->assign('WIZARDS', $buttons);
		
		$out = $smarty->fetch('ModuleHome/Wizards.tpl');
		return $out;
	}
	// crmv@96233e
	
	protected function getProcessesContent($blockinfo) {
		global $app_strings, $mod_strings;
		$smarty = new VteSmarty();
		
		$smarty->assign('MODULE', $this->module);
		$smarty->assign('APP', $app_strings);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('BLOCK', $blockinfo);
	
		// TODO: retrieve something!
	
		$out = $smarty->fetch('ModuleHome/Processes.tpl');
		return $out;
	}
	
	// crmv@97209 crmv@101227
	public function getAvailableCharts($module, $modhomeid = null) {
		global $adb, $table_prefix, $current_user;
		$charts = array();
		
		require_once('modules/Reports/Reports.php');
		
		if (vtlib_isModuleActive('Charts')) {
			$reports = Reports::getInstance();
			$queryGenerator = QueryGenerator::getInstance('Charts', $current_user);
			$queryGenerator->initForDefaultCustomView();
			$list_query = $queryGenerator->getQuery();
			$res = $adb->query($list_query);
			
			$chartBlocks = array();
			if ($modhomeid > 0) {
				$blocks = $this->getBlocks($modhomeid);
				foreach ($blocks as $block) {
					if ($block['type'] == 'Chart') {
						$chartBlocks[] = $block['config']['chartid'];
					}
				}
			}
			
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$reportid = intval($row['reportid']);
					if ($reportid > 0) {
						$modules = $reports->getAllModules($reportid);
						if (in_array($module, $modules) && !in_array($row['chartid'], $chartBlocks)) {
							$charts[] = $row;
						}
					}
				}
			}
		}
		
		return $charts;
	}
	// crmv@97209e crmv@101227e
	
	/**
	 * 
	 */
	public function populateDefaultViews() {
		
		$views = ModuleHomeViewDefaults::getViews(); // crmv@120138
		foreach ($views as $module => $list) {
			$this->setModule($module);
			foreach ($list as $view) {
				if (!empty($view['blocks'])) {
					$viewid = $this->insertView($view);
					if ($viewid > 0) {
						foreach ($view['blocks'] as $block) {
							$this->insertBlock($viewid, $block);
						}
					}
				}
			}
		}
	}

	// crmv@173746
	public function getPermissions($permission_type) {
		// Permission type can be: 
		// CAN_ADD_HOME_BLOCKS
		// CAN_EDIT_COUNTS
		// CAN_ADD_HOME_VIEWS
		// CAN_DELETE_HOME_VIEWS
		return true;
	}
	// crmv@173746e	
}

// crmv@120138
class ModuleHomeViewDefaults {

	public static function getViews() {
		global $adb, $table_prefix;

		// get filter ids
		$res = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE viewname = ? AND entitytype = ?", array('All', 'HelpDesk'));
		if ($res && $adb->num_rows($res) > 0) {
			$cvidHelpdesk = intval($adb->query_result_no_html($res, 0, 'cvid'));
		} else {
			$cvidHelpdesk = 7;
		}
		$res = $adb->pquery("SELECT cvid FROM {$table_prefix}_customview WHERE viewname = ? AND entitytype = ?", array('All', 'Potentials'));
		if ($res && $adb->num_rows($res) > 0) {
			$cvidPotentials = intval($adb->query_result_no_html($res, 0, 'cvid'));
		} else {
			$cvidPotentials = 4;
		}

		$views = array(
			'HelpDesk' => array(
				array(
					'name' => 'LBL_MODHOME_VIS',
					'blocks' => array(
						/*array(
							'type' => 'Chart',
							'title' => 'Ticket per stato',
							'size' => 1,
							'sequence' => 1,
							'config' => array(
								'chartid' => 134,
							)
						),*/
						/*array(
							'type' => 'QuickFilter',
							'title' => '',	// if empty, the filter's name will be used
							'size' => 1,
							'sequence' => 2,
							'config' => array(
								'cvid' => 57,
							)
						),
						array(
							'type' => 'QuickFilter',
							'title' => '',
							'size' => 1,
							'sequence' => 3,
							'config' => array(
								'cvid' => 58,
							)
						),*/
						array(
							'type' => 'Wizards',
							'title' => 'Wizard',
							'size' => 1,
							'sequence' => 4,
							'config' => array(
								
							)
						),
						array(
							'type' => 'Filter',
							'title' => '',
							'size' => 4,
							'sequence' => 5,
							'config' => array(
								'cvid' => $cvidHelpdesk,
							)
						)
					),
				),
				array(
					'name' => 'LBL_MODHOME_AGGR',
					'blocks' => array(
						/*array(
							'type' => 'Chart',
							'title' => 'Ticket per stato',
							'size' => 1,
							'sequence' => 1,
							'config' => array(
								'chartid' => 134,
							)
						),*/
						array(
							'type' => 'Wizards',
							'title' => 'Wizard',
							'size' => 1,
							'sequence' => 2,
							'config' => array(
								
							)
						),
					),
				),
			),
			'Potentials' => array(
				array(
					'name' => 'LBL_MODHOME_VIS',
					'blocks' => array(
						/*array(
							'type' => 'Chart',
							'title' => 'Totale per stato',
							'size' => 1,
							'sequence' => 1,
							'config' => array(
								'chartid' => 138,
							)
						),*/
						/*array(
							'type' => 'QuickFilter',
							'title' => '',
							'size' => 2,
							'sequence' => 2,
							'config' => array(
								'cvid' => 59,
							)
						),*/
						array(
							'type' => 'Wizards',
							'title' => 'Wizard',
							'size' => 1,
							'sequence' => 3,
							'config' => array(
								
							)
						),
						array(
							'type' => 'Filter',
							'title' => '',
							'size' => 4,
							'sequence' => 4,
							'config' => array(
								'cvid' => $cvidPotentials,
							)
						)
					),
				),
				array(
					'name' => 'LBL_MODHOME_PROC',
					'blocks' => array(
						array(
							'type' => 'Wizards',
							'title' => 'Wizard',
							'size' => 1,
							'sequence' => 2,
							'config' => array(
								
							)
						),
					),
				),
			)
		);

		return $views;
	}
	
}
// crmv@120138e