<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6288 */

class KanbanView extends SDKExtendableUniqueClass {
	
	var $id = false;
	var $module;
	var $json;
	var $columns;
	var $actions;
	var $relation = array();
	var $operations = array(
		"is"=>'e',
		"is not"=>'n',
		"contains"=>'c',
		"does not contain"=>'k',
		"starts with"=>'s',
		"ends with"=>'ew',
		"has changed"=>'e',
		"equal to"=>'e',
		"less than"=>'l',
		"greater than"=>'g',
		"does not equal"=>'n',
		"less than or equal to"=>'m',
		"greater than or equal to"=>'h',
	);
	var $list_max_entries_per_page;
	
	function __construct($id) {
		global $adb, $table_prefix, $list_max_entries_per_page;

		$result = $adb->pquery("SELECT {$table_prefix}_kanbanview.*, entitytype
			FROM {$table_prefix}_kanbanview
			INNER JOIN {$table_prefix}_customview ON {$table_prefix}_kanbanview.cvid = {$table_prefix}_customview.cvid
			WHERE {$table_prefix}_customview.cvid = ?", array($id));
		if ($result && $adb->num_rows($result) > 0) {
			$kanbaninfo = $adb->fetch_array_no_html($result);
			$this->id = $id;
			$this->module = $kanbaninfo['entitytype'];
			$this->json = $kanbaninfo['json'];
			$conditions = Zend_Json::decode($this->json);
			$this->columns = array();
			$this->actions = array();
			if (!empty($conditions)) {
				foreach($conditions as $i => $condition) {
					(isset($condition['parentgroup'])) ? $this->actions[$condition['parentgroup']] = $condition : $this->columns[$i] = $condition;
				}
			}
			if (!empty($kanbaninfo['relation_id'])) {
				$this->relation['id'] = $kanbaninfo['relation_id'];
				$result = $adb->pquery("SELECT related_tabid, {$table_prefix}_tab.name
					FROM {$table_prefix}_relatedlists
					INNER JOIN {$table_prefix}_tab ON {$table_prefix}_tab.tabid = {$table_prefix}_relatedlists.related_tabid
					WHERE relation_id = ?", array($this->relation['id']));
				if ($result && $adb->num_rows($result) > 0) {
					$this->relation['tabid'] = $adb->query_result($result,0,'related_tabid');
					$this->relation['module'] = $adb->query_result($result,0,'name');
				}
			}
		}
		
		$this->list_max_entries_per_page = $list_max_entries_per_page;
	}
	
	function getId() {
		return $this->id;
	}
	
	function getJson() {
		return $this->json;
	}
	
	function getColumns($column='') {
		$columns = $this->columns;
		if ($column !== '') {
			return $columns[$column];
		} else {
			return $columns;
		}
	}
	function getActions($column='') {
		$actions = $this->actions;
		if ($column !== '') {
			return $actions[$column];
		} else {
			return $actions;
		}
	}
	
	function getGrid($user_id='') {
		$arr = array();
		$columns = $this->getColumns();
		foreach($columns as $columnId => $condition) {
			$arr[$columnId] = $this->getList($columnId,$user_id);
		}
		return $arr;
	}
	
	function getList($columnId,$user_id='',$page=0) {
		global $adb, $table_prefix, $current_user;

		$viewid = $this->id;
		$module = $this->module;
		$focus = CRMEntity::getInstance($module);
		$column = $this->getColumns($columnId);
		$conditions = $column['conditions'];
		
		$where = '';
		if($user_id == "all" || $user_id == "") { // all event (normal rule)

		} else if ( $user_id == "mine") { // only assigned to me
			$where .= " and {$table_prefix}_crmentity.smownerid = ".$current_user->id." ";
		} else if ( $user_id == "others") { // only assigneto others
			$where .= " and {$table_prefix}_crmentity.smownerid <> ".$current_user->id." ";
		} else { // a selected userid
			$where .= " and {$table_prefix}_crmentity.smownerid = ".$user_id." ";
		}

		$queryGenerator = QueryGenerator::getInstance($module,$current_user);
		$customView = CRMEntity::getInstance('CustomView', $module); // crmv@115329
		$queryGenerator->initForCustomViewById($viewid);
		$qgWhereFields = $queryGenerator->getWhereFields();
		//crmv@106318
		if (!empty($conditions)) {
			$counditions_count = count($conditions);
			if ($counditions_count > 1) (!empty($qgWhereFields)) ? $queryGenerator->startGroup(QueryGenerator::$AND) : $queryGenerator->startGroup(''); // crmv@166750
			elseif (!empty($qgWhereFields)) $queryGenerator->addConditionGlue(QueryGenerator::$AND);
			foreach($conditions as $condition) {
				$queryGenerator->addCondition($condition['fieldname'], $condition['value'], $this->operations[$condition['operation']]);
				if ($condition['glue'] == 'and') $queryGenerator->addConditionGlue(QueryGenerator::$AND);
				elseif ($condition['glue'] == 'or') $queryGenerator->addConditionGlue(QueryGenerator::$OR);
			}
			if ($counditions_count > 1) $queryGenerator->endGroup();
		}
		//crmv@106318e
		$list_query = $queryGenerator->getQuery();
		$list_query .= $where;
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
		$limit_start_rec = intval($page) * $this->list_max_entries_per_page; // crmv@172864
		$list_result = $adb->limitQuery($list_query,$limit_start_rec,$this->list_max_entries_per_page);

		$controller = ListViewController::getInstance($adb, $current_user, $queryGenerator);
		$entries = $controller->getListViewEntries($focus,$module,$list_result,null,true,'',array('KanbanView'=>true,'doNotEvaluate'=>array('assigned_user_id'),'moreInformation'=>false)); //crmv@161440
		
		// if related_module is not set I use the first module
		if (!empty($this->relation)) {
			foreach($entries as $id => $values) {
				$rm = RelationManager::getInstance();
				$relations = $rm->getTurboliftRelations($focus, $module, $id, $this->relation['module']);
				if (!empty($relations['turbolift'][0]) && isset($relations['turbolift'][0]['count'])) {
					$other_information[$id]['related_module'] = getTranslatedString($this->relation['module'],$this->relation['module']);
					$other_information[$id]['related_count'] = $relations['turbolift'][0]['count'];
				}
			}
		}

		$fields = $queryGenerator->getFields();
		$nameFields = $queryGenerator->getModuleNameFields($module);
		$nameFieldList = explode(',',$nameFields);
		if (!in_array($focus->list_link_field, $nameFieldList)) {
			$nameFieldList[] = $focus->list_link_field;
		}
		$nameFieldListPosition = array_intersect($fields,$nameFieldList);

		return array('label'=>$column['label'],'entries'=>$entries,'other_information'=>$other_information,'name_field_position'=>array_keys($nameFieldListPosition),'user_field_position'=>array_search('assigned_user_id',$fields));
	}
}

class KanbanLib extends SDKExtendableUniqueClass {

	public $excludeModules = array(
		'Calendar',
		'Home','Emails','Rss','Reports','Portal',
		'Users','SDK','WSAPP','FieldFormulas',
		'Webforms','Sms','Fax','ModComments',
		'Charts','M','Myfiles', // crmv@164122
		'MyNotes','ProductLines','Messages' // crmv@164120
	);
	
	public function save($cvid, $json, $relation='') {
		global $adb, $table_prefix;
		if (!empty($relation) && !is_numeric($relation)) {
			$result = $adb->pquery("select entitytype from {$table_prefix}_customview where cvid = ?", array($cvid));
			if ($result && $adb->num_rows($result) > 0) {
				$module = $adb->query_result($result,0,'entitytype');
			}
			$moduleInstance = Vtecrm_Module::getInstance($module);
			$relModuleInstance = Vtecrm_Module::getInstance($relation);
			$result = $adb->pquery("SELECT relation_id FROM {$table_prefix}_relatedlists WHERE tabid = ? and related_tabid = ?", array($moduleInstance->id, $relModuleInstance->id));
			if ($result && $adb->num_rows($result) > 0) {
				$relation = $adb->query_result($result,0,'relation_id');
			}
		}
		$result = $adb->pquery("select cvid from {$table_prefix}_kanbanview where cvid = ?", array($cvid));
		if ($result && $adb->num_rows($result) > 0) {
			$adb->pquery("update {$table_prefix}_kanbanview set json=?, relation_id=? where cvid=?", array($json, $relation, $cvid));
		} else {
			$adb->pquery("insert into {$table_prefix}_kanbanview(cvid,json,relation_id) values(?,?,?)", array($cvid, $json, $relation));
		}
	}
	
	public function populateDefault() {
		global $adb, $table_prefix;

		$fieldnames = array(
			'Campaigns'=>'campaignstatus',
			'Accounts'=>'rating',
			'ProjectTask'=>'projecttaskpriority',
			'HelpDesk'=>'ticketstatus',
		);
		$relations = array(
			'Accounts'=>'Potentials',
			'ProjectTask'=>'HelpDesk',
			'HelpDesk'=>'Calendar',
		);
		$relations = array();	// TODO al momento disattivato (ammazza performance)

		$sql = "SELECT cvid, tabid, name
			FROM {$table_prefix}_customview
			INNER JOIN {$table_prefix}_tab ON {$table_prefix}_customview.entitytype = {$table_prefix}_tab.name
			WHERE viewname = ? AND presence = 0 AND isentitytype = 1 AND name NOT IN (".generateQuestionMarks($this->excludeModules).")";
		$res = $adb->pquery($sql, array('All', $this->excludeModules));
		if ($res && $adb->num_rows($res) > 0) {
			while($rw=$adb->fetchByAssoc($res)){
				$cvid = $rw['cvid'];
				$tabid = $rw['tabid'];
				$module = $rw['name'];
				$result = $adb->pquery("SELECT cvid FROM {$table_prefix}_kanbanview WHERE cvid = ?", array($cvid));
				if ($result && $adb->num_rows($result) > 0) {
					$this->log("Kanban already exists for the view $cvid");
				} else {
					if (isset($fieldnames[$module])) {
						$fieldname = $fieldnames[$module];
					} else {
						$fieldname = '';
						$result = $adb->query("SELECT fieldname FROM {$table_prefix}_field WHERE tabid = $tabid AND uitype = '15' AND fieldname LIKE '%status%'");
						if ($result && $adb->num_rows($result) > 0) {
							$fieldname = $adb->query_result($result,0,'fieldname');
						} else {
							$result = $adb->query("SELECT fieldname FROM {$table_prefix}_field WHERE tabid = $tabid AND uitype = '15' AND fieldname LIKE '%type%'");
							if ($result && $adb->num_rows($result) > 0) {
								$fieldname = $adb->query_result($result,0,'fieldname');
							} else {
								$result = $adb->query("SELECT fieldname FROM {$table_prefix}_field WHERE tabid = $tabid AND uitype = '15'");
								if ($result && $adb->num_rows($result) > 0) {
									$fieldname = $adb->query_result($result,0,'fieldname');
								}
							}
						}
					}
					if ($fieldname != '') {
						$json = array();
						$picktable = $table_prefix.'_'.$fieldname;
						if (Vtecrm_Utils::CheckTable($picktable)) {
							$result = $adb->query("SELECT * FROM {$picktable}");
							if ($result && $adb->num_rows($result) > 0) {
								$i = 0;
								while($row=$adb->fetchByAssoc($result)) {
									if ($i >= 10) break;
									$json[$i] = array(
										'conditions'=>array(array('fieldname'=>$fieldname,'operation'=>'is','value'=>$row[$fieldname])),
										'glue'=>'and',
										'label'=>getTranslatedString($row[$fieldname],$module),
									);
									$json[$i+1] = array(
										'conditions'=>array(array('fieldname'=>$fieldname,'operation'=>'is','value'=>$row[$fieldname])),
										'glue'=>'and',
										'parentgroup'=>$i,
									);
									$i+=2;
								}
							}
						}
						if (!empty($json)) {
							if (isset($relations[$module])) {
								$this->save($cvid, Zend_Json::encode($json), $relations[$module]);
							} else {
								$this->save($cvid, Zend_Json::encode($json));
							}
							$this->log("Created kanban $cvid for $module with field {$fieldname}");
						}
					} else {
						$this->log("<b>Unable to create kanban for $module, fieldname not found</b>");
					}
				}
			}
		}
	}

	protected function log($text) {
		// disabled
		//echo $text."<br>\n";
	}
}