<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class DetailViewWidgets {
	static function getWidget($name) {
		if (class_exists($name)) {
			return new $name($name);
		}
	}
	static function reorder($tabid='') {
		if (empty($module)) {
			self::reorderAll();
		} else {
			self::reorderModule($tabid);
		}
	}
	static function reorderAll() {
		global $adb, $table_prefix;
		$result = $adb->pquery("SELECT tabid FROM {$table_prefix}_links WHERE linktype = ? GROUP BY tabid",array('DETAILVIEWWIDGET'));
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				self::reorderModule($row['tabid']);
			}
		}
	}
	static function reorderModule($tabid) {
		global $adb, $table_prefix;
		$result1 = $adb->pquery("SELECT linkid, linklabel FROM {$table_prefix}_links WHERE tabid = ? AND linktype = ?",array($tabid,'DETAILVIEWWIDGET'));
		if ($result1 && $adb->num_rows($result1) > 0) {
			$seq = 4;
			while($row1=$adb->fetchByAssoc($result1)) {
				if ($row1['linklabel'] == 'DetailViewBlockCommentWidget') {
					$sequence = 1;
				} elseif ($row1['linklabel'] == 'PendingHistory') {
					$sequence = 2;
				} elseif ($row1['linklabel'] == 'DetailViewMyNotesWidget') {
					$sequence = 3;
				} else {
					$sequence = $seq;
					$seq++;
				}
				$adb->pquery("UPDATE {$table_prefix}_links SET sequence = ? WHERE linkid = ?",array($sequence,$row1['linkid']));
			}
		}
	}
}

class DefaultWidget {
	
	protected $class_name;
	protected $criteria = false;
	protected $context;
	
	function _construct($name) {
		$this->name = $name;
	}
	
	function setContext($context) {
		if (empty($this->context)) {
			$this->context = $context;
		}
	}
	
	function getFromContext($key, $purify=false) {
		if ($this->context) {
			$value = $this->context[$key];
			if ($purify && !empty($value)) {
				$value = vtlib_purify($value);
			}
			return $value;
		}
		return false;
	}
	
	function title($context = false) {
		$this->setContext($context);
		$sourceModule = $this->getFromContext('MODULE', true);
		return getTranslatedString($this->name,$sourceModule);
	}
}

class AccountsHierarchy extends DefaultWidget {
	
	function install() {
		$moduleInstance = Vtecrm_Module::getInstance('Accounts');
		Vtecrm_Link::addLink($moduleInstance->id, 'DETAILVIEWWIDGET', 'AccountsHierarchy', 'block://DetailViewWidgets:include/utils/DetailViewWidgets.php');
	}
	
	function process($context = false) {
		$this->setContext($context);
		$sourceRecordId = $this->getFromContext('ID', true);
		$content = '';
		if (!empty($sourceRecordId)) {
			$content = GetHierarchy($sourceRecordId);
		}
		$smarty = new VteSmarty();
		$smarty->assign('CONTENT', $content);
		$smarty->display('widgets/AccountsHierarchy.tpl');
	}
}

class PendingHistory extends DefaultWidget {
	
	function install() {
		global $adb, $table_prefix;
		$result = $adb->query("SELECT tabid FROM {$table_prefix}_tab WHERE isentitytype = 1 AND presence = 0 AND name not in ('Emails','Fax','Sms','PBXManager','ModComments','Charts','Messages','MyNotes')"); // crmv@164120 crmv@164122
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				Vtecrm_Link::addLink($row['tabid'], 'DETAILVIEWWIDGET', 'PendingHistory', 'block://DetailViewWidgets:include/utils/DetailViewWidgets.php');
			}
		}
	}
	
	protected $config = array(
		'Accounts'=>array(
			'field'=>array('fieldname'=>'rating','columnname'=>'rating','tablename'=>'_account','tableid'=>'accountid'),
			'pending'=>array('Acquired','Active'),
			'history'=>'NOTPENDING',
		),
		'Calendar'=>array(
			'field'=>array('fieldname'=>'taskstatus','columnname'=>'status','tablename'=>'_activity','tableid'=>'activityid'),
			'pending'=>'NOTHISTORY',
			'history'=>array('Completed','Deferred'),
		),
		'Events'=>array(
			'field'=>array('fieldname'=>'eventstatus','columnname'=>'eventstatus','tablename'=>'_activity','tableid'=>'activityid'),
			'pending'=>'NOTHISTORY',
			'history'=>array('Held'),
		),
		'Potentials'=>array(
			'field'=>array('fieldname'=>'sales_stage','columnname'=>'sales_stage','tablename'=>'_potential','tableid'=>'potentialid'),
			'pending'=>'NOTHISTORY',
			'history'=>array('Closed Won','Closed Lost'),
		),
		'Quotes'=>array(
			'field'=>array('fieldname'=>'quotestage','columnname'=>'quotestage','tablename'=>'_quotes','tableid'=>'quoteid'),
			'pending'=>'NOTHISTORY',
			'history'=>array('Rejected'),
		),
		'SalesOrder'=>array(
			'field'=>array('fieldname'=>'sostatus','columnname'=>'sostatus','tablename'=>'_salesorder','tableid'=>'salesorderid'),
			'pending'=>'NOTHISTORY',
			'history'=>array('Cancelled'),
		),
		// TODO...
	);
	
	function title($context = false) {
		return '<table cellpadding="0" cellspacing="0" width="100%"><tr><td width="50%">History</td><td width="50%">Pending</td></tr></table>';
	}
	
	function process($context = false) {
		$this->setContext($context);
		$sourceRecordId = $this->getFromContext('ID', true);
		$sourceModule = $this->getFromContext('MODULE', true);
		$smarty = new VteSmarty();
		$records = $this->getRecords($sourceModule,$sourceRecordId);
		$smarty->assign('PENDING',$records['pending']);
		$smarty->assign('HISTORY',$records['history']);
		$smarty->display('widgets/PendingHistory.tpl');
	}
	
	function recordInfo($module,$id) {
		if ($module == 'Events') {
			$name = getEntityName('Calendar',$id);
		} else {
			$name = getEntityName($module,$id);
		}
		$singleName = getSingleModuleName($module);
		if ($module == 'Events') {
			$module = 'Calendar';
		}
		$link = "<a href='index.php?module=$module&action=DetailView&record=$id'>{$name[$id]}</a>";
		return array(
			$singleName,
			$link,
		);
	}
	
	function getRecords($module,$record) {
		global $adb, $table_prefix;
		$rm = RelationManager::getInstance();
		$relations = $rm->getRelatedIds($module, $record, null, array('ModComments'), false, true); // crmv@164120
		$pending_ids = $history_ids = array();
		if (!empty($relations)) {
			foreach ($relations as $module => $ids) {
				if (!empty($ids) && !empty($this->config[$module])) {
					if (!empty($this->config[$module]['field'])) {
						$field = $this->config[$module]['field'];
						$pending = $this->config[$module]['pending'];
						$history = $this->config[$module]['history'];
						if (!empty($pending)) {
							$queryP = "select {$field['tableid']} from {$table_prefix}{$field['tablename']} where {$field['tableid']} in (".generateQuestionMarks($ids).")";
							$paramsP = array($ids);
							if ($pending == 'NOTHISTORY' && !empty($history)) {
								$queryP .= " and {$field['columnname']} not in (".generateQuestionMarks($history).")";
								$paramsP[] = $history;
							} elseif (is_array($pending)) {
								$queryP .= " and {$field['columnname']} in (".generateQuestionMarks($pending).")";
								$paramsP[] = $pending;
							}
							$resultP = $adb->pquery($queryP,$paramsP);
							if ($resultP) {
								while($row=$adb->fetchByAssoc($resultP)) {
									$pending_ids[] = $this->recordInfo($module,$row[$field['tableid']]);
								}
							}
						}
						if (!empty($history)) {
							$queryH = "select {$field['tableid']} from {$table_prefix}{$field['tablename']} where {$field['tableid']} in (".generateQuestionMarks($ids).")";
							$paramsH = array($ids);
							if ($history == 'NOTPENDING' && !empty($pending)) {
								$queryH .= " and {$field['columnname']} not in (".generateQuestionMarks($pending).")";
								$paramsH[] = $pending;
							} elseif (is_array($history)) {
								$queryH .= " and {$field['columnname']} in (".generateQuestionMarks($history).")";
								$paramsH[] = $history;
							}
							$resultH = $adb->pquery($queryH,$paramsH);
							if ($resultH) {
								while($row=$adb->fetchByAssoc($resultH)) {
									$history_ids[] = $this->recordInfo($module,$row[$field['tableid']]);
								}
							}
						}
					} else {
						//TODO function
					}
				}
			}
		}
		return array('pending'=>$pending_ids,'history'=>$history_ids);
	}
}