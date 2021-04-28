<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43942 crmv@54707 */

class Area extends SDKExtendableClass {
	
	protected $id;
	protected $name;
	protected $label;
	protected $modules;
	protected $default_list_max_entries = 5;
	
	function constructById($areaid) {
		$areaManager = AreaManager::getInstance();
		$area = $areaManager->getModuleList($areaid);
		$this->id = $area['id'];
		$this->name = $area['name'];
		$this->label = $area['translabel'];
		$this->modules = $area['info'];
	}
	
	function constructByModule($module) {
		$areaManager = AreaManager::getInstance();
		$area = $areaManager->getModuleList(false,$module);
		foreach ($area as $a) {
			$this->id = $a['id'];
			$this->name = $a['name'];
			$this->label = $a['translabel'];
			$this->modules = $a['info'];
			break;
		}
	}
	
	function getId() {
		return $this->id;
	}
	
	function getName() {
		return $this->name;
	}
	
	function getLabel() {
		return $this->label;
	}
	
	function getModules() {
		return $this->modules;
	}
	
	function getListMaxEntries() {
		return $this->default_list_max_entries;
	}
	
	function getLastModified() {
		global $table_prefix;
		return $this->search('',"ORDER BY {$table_prefix}_crmentity.modifiedtime DESC",getTranslatedString('LBL_FOLDER_CONTENT'),true);
	}
	
	//crmv@144823
	function search($search,$orberby='',$header='',$hide_other_button=false) {
		$return = array();
		if (!empty($this->modules)) {
			foreach ($this->modules as $module) {
				$return[$module['tabid']] = $this->searchModule($module['name'],$search,$orberby,$header,$hide_other_button);
			}
		}
		return $return;
	}
	function searchModule($module,$search,$orberby='',$header='',$hide_other_button=false) {
		global $current_user, $adb;
		$list_max_entries = $this->getListMaxEntries();
		$focus = CRMEntity::getInstance($module);
				
		$queryGenerator = QueryGenerator::getInstance($module,$current_user);
		$queryGenerator->initForAllCustomView(); //crmv@128638
		$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
		if (!empty($search)) {
			//crmv@55317
			$SearchUtils = SearchUtils::getInstance();
			$listview_header_search = $controller->getBasicSearchFieldInfoList();
			$listview_header_search_new = $SearchUtils->getUnifiedSearchFieldInfoList($module);
			$listview_header_search = array_merge($listview_header_search, $listview_header_search_new);
			//crmv@55317e
			$_REQUEST['search_fields'] = $listview_header_search;
			$queryGenerator->addUserSearchConditions($_REQUEST);
		}
		$list_query = $queryGenerator->getQuery();
		if (!empty($orberby)) {
			$list_query .= " $orberby";
		} else {
			$customView = CRMEntity::getInstance('CustomView', $module); // crmv@115329
			$viewid = $customView->getViewId($module);
			list($focus->customview_order_by,$focus->customview_sort_order) = $customView->getOrderByFilterSQL($viewid);
			$sorder = $focus->getSortOrder();
			$order_by = $focus->getOrderBy();
			if(!empty($order_by) && $order_by != '' && $order_by != null) {
				$list_query .= $focus->getFixedOrderBy($module,$order_by,$sorder);	//crmv@127820
			}
		}
		$list_result = $adb->limitQuerySlave('Area',$list_query,0,$list_max_entries+1); // crmv@185894
		
		$show_other_button = false;
		if ($adb->num_rows($list_result) > $list_max_entries) {
			$list_result->_numOfRows = $list_max_entries;
			if (!$hide_other_button) {
				$show_other_button = true;
			}
		}

		$listview_entries = $controller->getListViewEntries($focus,$module,$list_result,null,true);
		
		$nameFields = $queryGenerator->getModuleNameFields($module);
		$nameFieldList = explode(',',$nameFields);
		if (!in_array($focus->list_link_field, $nameFieldList)) {
			$nameFieldList[] = $focus->list_link_field;
		}
						
		$fields = $queryGenerator->getFields();
		$nameFieldListPosition = array_intersect($fields,$nameFieldList);
		
		if (empty($header)) {
			$header = getTranslatedString('LBL_SEARCH').': '.urldecode($search);	//crmv@58264
		}

		return array('header'=>$header,'entries'=>$listview_entries,'name_field_position'=>array_keys($nameFieldListPosition),'show_other_button'=>$show_other_button);
	}
	//crmv@144823e
	
	function setSessionVars() {
		if (!empty($this->modules)) {
			foreach ($this->modules as $module) {
				VteSession::setArray(array('areas', $module['name']), $this->id); // crmv@128133
			}
		}
	}
	
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {

			$areaManager = AreaManager::getInstance();
			//$areaManager->setMenuView();
			$areaManager->createTables();
			$areaManager->initTables();
			
			SDK::setMenuButton('contestual','LBL_AREAS_SETTINGS',"ModuleAreaManager.showSettings();",'settings_applications','Area','index');

		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
}

class AreaManager extends SDKExtendableUniqueClass {
	
	protected $table = 'tbl_s_areas';
	protected $table_menu = 'tbl_s_menu_areas';
	protected $table_tools = 'tbl_s_area_tools';
	protected $searchByUser;
	protected $max_menu_entries = 7;
	//crmv@107077
	var $hightlight_fixed_modules = array('Home','Messages');
	var $hide_fixed_modules = array('Charts','PDFMaker','Reports','Rss','Portal','RecycleBin');//crmv@208472
	//crmv@107077e
	
	function __construct($searchByUser=true) {
		$this->searchByUser = $searchByUser;
		// se l'utente non ha fatto nessuna personalizzazione leggo le impostazioni di default
		if ($this->searchByUser && Vtecrm_Utils::CheckTable($this->table_menu)) {
			global $adb, $current_user;
			$check = $adb->pquery("select * from {$this->table_menu} where userid = ?",array($current_user->id));
			if ($check && $adb->num_rows($check) == 0) {
				$this->searchByUser = false;
			}
		}
	}
	
	function setMenuView() {
		global $adb, $table_prefix;
		$adb->pquery("update tbl_s_menu set type = ?",array('areas'));
	}
	
	function createTables() {
		global $adb, $table_prefix;
		
		$tablename = $this->table;
		$schema_table =
		'<schema version="0.3">
			<table name="'.$tablename.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="areaid" type="I" size="19">
					<KEY/>
				</field>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="area" type="C" size="50"/>
				<field name="sequence" type="I" size="19"/>
				<field name="active" type="I" size="1"/>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($tablename)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$tablename = $this->table_menu;
		$schema_table =
		'<schema version="0.3">
			<table name="'.$tablename.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="areaid" type="I" size="19">
					<KEY/>
				</field>
				<field name="tabid" type="I" size="19">
					<KEY/>
				</field>
				<field name="userid" type="I" size="19">
					<KEY/>
				</field>
				<field name="sequence" type="I" size="19"/>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($tablename)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
		}
		
		$tablename = $this->table_tools;
		$schema_table =
		'<schema version="0.3">
			<table name="'.$tablename.'">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="param" type="C" size="50">
					<KEY/>
				</field>
				<field name="val" type="C" size="200"/>
			</table>
		</schema>';
		if(!Vtecrm_Utils::CheckTable($tablename)) {
			$schema_obj = new adoSchema($adb->database);
			$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema_table));
			
			$adb->pquery("insert into $tablename (param,val) values (?,?)",array('enable_areas',1));
			$adb->pquery("insert into $tablename (param,val) values (?,?)",array('block_area_layout',0));
		}
	}
	
	function initTables() {
		include_once('vtlib/Vtecrm/Module.php');
		global $adb, $table_prefix;
		$adb->query("delete from {$this->table}");
		$adb->query("delete from {$this->table_menu}");
		$userid = 0;
		$active = 1;
		$default = array(
			/*
			array(
				'areaid'=>'0','area'=>'HightlightArea','tabs'=>array(3,9,'Messages')
			),*/
			array(
				'areaid'=>'1','area'=>'ClientsArea','tabs'=>array(7,6,4,18)
			),
			array(
				'areaid'=>'2','area'=>'Marketing','tabs'=>array(26,'Newsletter','Targets')
			),
			array(
				'areaid'=>'3','area'=>'Projects','tabs'=>array('ProjectPlan','ProjectMilestone','ProjectTask')
			),
			array(
				'areaid'=>'4','area'=>'Sales','tabs'=>array(2,20,23,22)
			),
			array(
				'areaid'=>'5','area'=>'Inventory','tabs'=>array(14,'Services','PriceBooks','PurchaseOrder','ProductLines')
			),
			/*
			AfterSalesArea
			 */
		);
		$a_seq = 1;
		foreach ($default as $a) {
			$adb->pquery("insert into {$this->table} (areaid,area,sequence,active,userid) values (?,?,?,?,?)",
				array($a['areaid'],$a['area'],$a_seq,$active,$userid)
			);
			$a_seq++;
			$t_seq = 1;
			foreach ($a['tabs'] as $tab) {
				if (is_numeric($tab)) {
					$tabid = $tab;
				} else {
					$moduleInstance = Vtecrm_Module::getInstance($tab);
					$tabid = $moduleInstance->id;
				}
				if (empty($tabid)) {
					continue;
				}
				$adb->pquery("insert into {$this->table_menu} (areaid,tabid,userid,sequence) values (?,?,?,?)",
					array($a['areaid'],$tabid,$userid,$t_seq)
				);
				$t_seq++;
			}
		}
	}
	
	function getSearchByUser() {
		return $this->searchByUser;
	}
	
	function getAreaList() {
		global $adb, $current_user;
		$list = array();
		$result = $adb->pquery("select * from {$this->table} where userid = ? order by sequence",array($current_user->id));
		if ($result && $adb->num_rows($result) == 0) {
			$result = $adb->pquery("select * from {$this->table} where userid = ? order by sequence",array(0));
		}
		if ($result && $adb->num_rows($result)) {
			while($row=$adb->fetchByAssoc($result)) {
				if (isset($list[$row['areaid']])) {
					continue;
				}
				$row['label'] = getTranslatedString($row['area']);
				$list[$row['areaid']] = $row;
			}
		}
		/*
		$list[] = array(
			'areaid'=>-1,
			'area'=>'LBL_MORE',
			'sequence'=>'',
			'userid'=>null,
			'active'=>1,
			'label'=>getTranslatedString('LBL_MORE'),
		);
		*/
		return $list;
	}
	
	function getSelectableModuleList($area,$modules2exclude) {
		$notSelectableModules = array_merge($this->hightlight_fixed_modules,$this->hide_fixed_modules);
		if (!empty($modules2exclude)) {
			foreach($modules2exclude as $module) {
				$notSelectableModules[] = $module['name'];
			}
		}
		$allList = $this->getModuleList('all');
		unset($allList[$area]);
		$list = $modules = array();
		if (!empty($allList)) {
			foreach($allList as $a) {
				foreach($a['info'] as $i) {
					if (in_array($i['name'],$modules)) {
						continue;
					}
					if (!empty($notSelectableModules) && in_array($i['name'],$notSelectableModules)) {
						continue;
					}
					$modules[] = $i['name'];
					$list[] = $i;
				}
			}
		}
		usort($list, function($a, $b) {
			return ($a['translabel'] > $b['translabel']);
		});
		return $list;
	}
	
	/* crmv@59173 */
	function getModuleList($area=false,$module=false) {
		global $adb, $table_prefix, $current_user, $root_directory;
		
		if ($_REQUEST['module'] == 'Update') {
			if (!Vtecrm_Utils::CheckTable($this->table) && !Vtecrm_Utils::CheckTable($this->table_menu)) {
				return false;
			}
		}
		
		require('user_privileges/requireUserPrivileges.php');
		
		$list = array();
		$params = array();
		$sql = "SELECT
				  COALESCE({$this->table_menu}.areaid,-1) AS areaid,
				  {$this->table}.area,
				  {$table_prefix}_tab.tabid,
				  {$table_prefix}_tab.name,
				  {$this->table}.sequence,
				  {$this->table_menu}.sequence
				FROM {$table_prefix}_tab
				  INNER JOIN (SELECT DISTINCT
				                tabid
				              FROM {$table_prefix}_parenttabrel) parenttabrel
				    ON parenttabrel.tabid = {$table_prefix}_tab.tabid
				  LEFT JOIN {$this->table_menu}
				    ON {$table_prefix}_tab.tabid = {$this->table_menu}.tabid AND {$this->table_menu}.userid = ?
				  LEFT JOIN {$this->table}
				    ON {$this->table_menu}.areaid = {$this->table}.areaid AND {$this->table}.userid = ?
				WHERE {$table_prefix}_tab.presence = 0";
		if ($this->searchByUser) {
			$params[] = array($current_user->id,$current_user->id);
		} else {
			$params[] =  array(0,0);
		}
		if (!empty($module)) {
			$sql .= " AND {$table_prefix}_tab.name = ?";
			$params[] = $module;
		} else {
			$sql .= " ORDER BY COALESCE({$this->table}.sequence,999), COALESCE({$this->table_menu}.sequence,999)";
		}
		$res = $adb->pquery($sql,$params);
		$areas = $modules1 = $modules_1 = array();
		while($info=$adb->fetchByAssoc($res)) {
			if ($profileGlobalPermission[2] == 0 || $profileGlobalPermission[1] == 0 || $profileTabsPermission[$info['tabid']] == 0) {
				// module visible
			} else {
				continue;
			}
			
			// crmv@42707
			$info['index_url'] = "index.php?module={$info['name']}&action=index";
			// modules without list
			if (!in_array($info['name'], array('Home'))) {
				$info['list_url'] = "index.php?module={$info['name']}&action=ListView&areaid=".$info['areaid'];	//crmv@107077
			}
			// modules without standard EditView
			if (!in_array($info['name'], array('Home', 'Messages', 'PDFMaker', 'Rss', 'Sms', 'Fax', 'RecycleBin', 'Charts', 'Portal'))) {//crmv@208472
				$info['create_url'] = "index.php?module={$info['name']}&action=EditView";
			}
			// custom editview
			if ($info['name'] == 'Messages') {
				$info['create_url'] = "index.php?module=Emails&action=EmailsAjax&file=EditView";
			}
			// crmv@42707e
			
			if (getTranslatedString($info['name'], 'APP_STRINGS') === $info['name'] || $info['name'] === 'PBXManager')
				$info['translabel'] = getTranslatedString($info['name'],$info['name']);
			else
				$info['translabel'] = getTranslatedString($info['name'], 'APP_STRINGS');
			// crmv@140887
			$info['img'] = "themes/images/modulesimg/{$info['name']}.png";
			$RV = ResourceVersion::getInstance();
			if (file_exists($info['img'])) {
				$info['img'] = $RV->getResource($info['img']);
			}
			if (!file_exists($info['img'])) {
				$info['img'] = $RV->getResource("themes/images/modulesimg/Generic.png");
			}
			// crmv@140887e
			if ($area === 'all' || is_numeric($area)) {
				if (!isset($areas[$info['areaid']])) {
					$areas[$info['areaid']] = array('id'=>$info['areaid'],'name'=>$info['area'],'translabel'=>getTranslatedString($info['area']),'index_url'=>"index.php?module=Area&action=index&area={$info['areaid']}");
				}
				$areas[$info['areaid']]['info'][] = $info;
			} else {
				if ($info['areaid'] == -1) {
					$modules_1[] = $info;
					$area_1 = array('id'=>$info['areaid'],'name'=>'LBL_MORE','translabel'=>getTranslatedString('LBL_MORE','APP_STRINGS'),'index_url'=>"index.php?module=Area&action=index&area={$info['areaid']}");
				} elseif ($info['areaid'] == 0) {
					$modules1[] = $info;
					$area0 = array('id'=>$info['areaid'],'name'=>$info['area'],'translabel'=>getTranslatedString($info['area']),'index_url'=>"index.php?module=Area&action=index&area={$info['areaid']}");
				} elseif ($info['areaid'] > 0) {
					if (!isset($areas[$info['areaid']])) {
						$areas[$info['areaid']] = array('id'=>$info['areaid'],'name'=>$info['area'],'translabel'=>getTranslatedString($info['area']),'index_url'=>"index.php?module=Area&action=index&area={$info['areaid']}");
					}
					$areas[$info['areaid']]['info'][] = $info;
				}
			}
		}
		if ($area === 'all') {
			return $areas;
		} elseif (is_numeric($area)) {
			return $areas[$area];
		} elseif (!empty($module)) {
			return $areas;
		} else {
			$module_list_fast = $module_list_all = array();
			$i=0;
			foreach($areas as $v) {
				$info = array('type'=>'area','info'=>$v);
				if ($i < $this->max_menu_entries) {
					$module_list_fast[] = $info;
				}
				$module_list_all[] = $info;
				$i++;
			}
			return array($module_list_fast,$module_list_all);
		}
	}
	
	function forceDefaultSettings($userid) {
		global $adb;
		
		$adb->pquery("delete from {$this->table} where userid = ?",array($userid));
		$result = $adb->query("select * from {$this->table} where userid = 0");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$adb->pquery("insert into {$this->table} (areaid,area,sequence,active,userid) values (?,?,?,?,?)",
					array($row['areaid'],$row['area'],$row['sequence'],$row['active'],$userid)
				);
			}
		}
		
		$adb->pquery("delete from {$this->table_menu} where userid = ?",array($userid));
		$result = $adb->query("select * from {$this->table_menu} where userid = 0");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				$adb->pquery("insert into {$this->table_menu} (areaid,tabid,userid,sequence) values (?,?,?,?)",
					array($row['areaid'],$row['tabid'],$userid,$row['sequence'])
				);
			}
		}
		
		$this->searchByUser = true;
	}
	
	function createArea($name,$tabids) {
		if (empty($name)) {
			return false;
		}
		global $adb, $current_user;
		($this->searchByUser) ? $userid = $current_user->id : $userid = 0;
		$result = $adb->query("SELECT MAX(areaid) as \"areaid\" FROM {$this->table}");
		if ($result && $adb->num_rows($result) > 0) {
			$areaid = $adb->query_result($result,0,'areaid')+1;
			$sequence = 1;
			$result = $adb->pquery("SELECT MAX(sequence) as \"sequence\" FROM {$this->table} where userid = ?",array($userid));
			if ($result) {
				$sequence = $adb->query_result($result,0,'sequence');
				if (empty($sequence)) {
					$sequence = 1;
				} else {
					$sequence++;
				}
			}			
			$adb->pquery("insert into {$this->table} (areaid,userid,area,sequence,active) values (?,?,?,?,?)",array($areaid,$userid,$name,$sequence,1));
		}
		$this->editArea($areaid,$tabids);
		return $areaid;
	}
	
	function editArea($areaid,$tabids) {
		
		global $adb, $current_user;
		
		($this->searchByUser) ? $userid = $current_user->id : $userid = 0;
		
		if ($areaid == -1) {
			
			if (!empty($this->hide_fixed_modules)) {
				$tmp_tabids = array();
				foreach ($this->hide_fixed_modules as $m) {
					$inst = Vtecrm_Module::getInstance($m);
					$tmp_tabids[] = $inst->id;
				}
				if (!empty($tabids)) {
					$tabids = array_merge($tmp_tabids,$tabids);
				} else {
					$tabids = $tmp_tabids;
				}
			}
			
			$adb->pquery("delete from {$this->table_menu} where userid = ? and tabid in (".implode(',',$tabids).")",array($userid,$tabids));
			
		} else {
			
			if ($areaid == 0) {
				if (!empty($this->hightlight_fixed_modules)) {
					$tmp_tabids = array();
					foreach ($this->hightlight_fixed_modules as $m) {
						$inst = Vtecrm_Module::getInstance($m);
						$tmp_tabids[] = $inst->id;
					}
					if (!empty($tabids)) {
						$tabids = array_merge($tmp_tabids,$tabids);
					} else {
						$tabids = $tmp_tabids;
					}
				}
				$adb->pquery("delete from {$this->table_menu} where userid = ? and tabid in (".implode(',',$tabids).") and areaid <> ?",array($userid,$areaid));
			} else {
				$adb->pquery("delete from {$this->table_menu} where userid = ? and tabid in (".implode(',',$tabids).") and areaid = ?",array($userid,0));
			}
			
			$adb->pquery("delete from {$this->table_menu} where areaid = ? and userid = ?",array($areaid,$userid));
			
			$i = 1;
			foreach($tabids as $tabid) {
				$adb->pquery("insert into {$this->table_menu} (areaid,tabid,userid,sequence) values (?,?,?,?)",array($areaid,$tabid,$userid,$i));
				$i++;
			}
			
		}
	}

	// crmv@64542
	/**
	 * Insert one or more modules in the specified area, for the specified users
	 * If $userid is -1, all users get the module, if $userid = 0, insert the module only in the default area
	 * otherwise the module is insterted for the specific user only
	 */
	public function insertModulesInArea($areaid, $userid, $modules) {
		global $adb;
		
		if (!is_array($modules)) $module = array($modules);
		
		if ($userid == -1) {
			// get all the userids
			$res = $adb->query("SELECT DISTINCT userid FROM {$this->table_menu}");
			if ($res && $adb->num_rows($res) > 0) {
				$userid = array();
				while ($row = $adb->FetchByAssoc($res, -1, false)) {
					$userid[] = $row['userid'];
				}
			} else {
				$userid = array(0);
			}
		} else {
			if (!is_array($userid)) $userid = array($userid);
		}
		
		foreach ($userid as $uid) {
			// get the sequence
			$seq = 1;
			$res = $adb->pquery("SELECT MAX(sequence) as \"sequence\" FROM {$this->table} where areaid = ? AND userid = ?",array($areaid, $uid));
			if ($res) {
				$seq = $adb->query_result_no_html($res,0,'sequence');
				if (empty($seq)) {
					$seq = 1;
				} else {
					++$seq;
				}
			}
			// insert the modules
			foreach ($modules as $module) {
				if (is_numeric($module)) {
					$tabid = $module;
				} else {
					$moduleInstance = Vtecrm_Module::getInstance($module);
					$tabid = $moduleInstance->id;
				}
				if (empty($tabid)) continue;
				$adb->pquery("insert into {$this->table_menu} (areaid,tabid,userid,sequence) values (?,?,?,?)",
					array($areaid,$tabid,$uid,$seq)
				);
				++$seq;
			}
		}
		
	}
	// crmv@64542e
	
	function deleteArea($areaid) {
		global $adb, $current_user;
		($this->searchByUser) ? $userid = $current_user->id : $userid = 0;
		$adb->pquery("delete from {$this->table} where areaid = ? and userid = ?",array($areaid,$userid));
		$adb->pquery("delete from {$this->table_menu} where areaid = ? and userid = ?",array($areaid,$userid));
	}
	
	function propagateLayout() {
		global $adb, $current_user;
		$result = $adb->pquery("select * from {$this->table} where userid <> ?",array(0));
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("delete from {$this->table} where userid <> ?",array($current_user->id));
			$adb->pquery("update {$this->table} set userid = ?",array(0));
			$adb->pquery("delete from {$this->table_menu} where userid <> ?",array($current_user->id));
			$adb->pquery("update {$this->table_menu} set userid = ?",array(0));
		}
	}
	
	function getToolValue($param) {
		global $adb;
		if (Vtecrm_Utils::CheckTable($this->table_tools)) {
			$result = $adb->pquery("select val from {$this->table_tools} where param = ?",array($param));
			if ($result && $adb->num_rows($result) > 0) {
				return $adb->query_result($result,0,'val');
			}
		}
	}
	
	function setToolValue($param,$value) {
		global $adb;
		$adb->pquery("update {$this->table_tools} set val = ? where param = ?",array($value,$param));
	}
	
	function blockLayout($value) {
		$this->setToolValue('block_area_layout',$value);
	}
}
?>