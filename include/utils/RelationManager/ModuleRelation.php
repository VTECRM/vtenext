<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/*
 * crmv@151308 - split in 2 files to support autoloading
 */


// represent a single relation between 2 modules
class ModuleRelation {

	// relation type
	public static $TYPE_1TO1 = 1;	// TODO
	public static $TYPE_1TON = 2;
	public static $TYPE_NTO1 = 4;
	public static $TYPE_NTON = 8;

	public static $TYPE_ALL = 0x0F;

	// modules
	protected $type;
	protected $module1;
	protected $module2;

	// relation info 1-N, N-1
	public $fieldid;
	public $fieldname;
	public $fieldtable;
	public $fieldcolumn;

	// relation info N-N
	public $relationid;
	public $relationfn; // crmv@155041
	public $relationinfo; // crmv@49398
	public $direction; // crmv@125816
	
	function __construct($module1, $module2, $type) {
		$this->module1 = $module1;
		$this->module2 = $module2;
		$this->type = $type;
	}

	function getFirstModule() {
		return $this->module1;
	}

	function getSecondModule() {
		return $this->module2;
	}

	function getType() {
		return $this->type;
	}

	function getFieldId() {
		return $this->fieldid;
	}
	//crmv@47905
	function getNtoNinfo(){
		global $table_prefix;
		
		if (in_array($this->module1, FakeModules::$fakeModules)) {
			$mod1Inst = new stdClass();
		} else {
			$mod1Inst = CRMEntity::getInstance($this->module1);
		}
		if (in_array($this->module2, FakeModules::$fakeModules)) {
			$mod2Inst = new stdClass();
		} else {
			$mod2Inst = CRMEntity::getInstance($this->module2);
		}
		// module1 -> module2
		$reltab = $mod1Inst->relation_table;
		$relidxCrmid = $mod1Inst->table_index;
		$relidx = $mod1Inst->relation_table_id;
		$relidx2 = $mod1Inst->relation_table_otherid;
		$relmod1 = $mod1Inst->relation_table_module;
		$relmod2 = $mod1Inst->relation_table_othermodule;
		
		// crmv@125816
		// ordered N-N relation
		if ($this->direction) {
			$reltab = $mod1Inst->relation_table_ord;
			if ($this->direction == 'inverse') {
				$relidx = $mod1Inst->relation_table_otherid;
				$relidx2 = $mod1Inst->relation_table_id;
				$relmod1 = $mod1Inst->relation_table_othermodule;
				$relmod2 = $mod1Inst->relation_table_module;
			}
		}
		// crmv@125816e
		
		//crmv@43864
		if ($this->module1 == 'Messages') {
			$relidxCrmid = 'messagehash';
		} elseif (isInventoryModule($this->module1) && isProductModule($this->module2)) {
			// inventorymod->products
			$reltab = $table_prefix."_inventoryproductrel";
			$relidx = 'id';
			$relidx2 = 'productid';
			$relmod1 = $relmod2 = '';
		} elseif (isProductModule($this->module1) && isInventoryModule($this->module2)) {
			// products->inventorymod
			$reltab = $table_prefix."_inventoryproductrel";
			$relidx = 'productid';
			$relidx2 = 'id';
			$relmod1 = $relmod2 = '';
		} elseif ($this->module1 == 'PriceBooks' && isProductModule($this->module2)) {
			$reltab = $table_prefix."_pricebookproductrel";
			$relidx = 'pricebookid';
			$relidx2 = 'productid';
			$relmod1 = $relmod2 = '';
		} elseif (isProductModule($this->module1) && $this->module2 == 'PriceBooks') {
			$reltab = $table_prefix."_pricebookproductrel";
			$relidx = 'productid';
			$relidx2 = 'pricebookid';
			$relmod1 = $relmod2 = '';
			// crmv@44187
			// TODO: use this system also for products/pricebooks/inventory
		} elseif ($this->module2 == 'Products' || $this->module2 == 'Documents'){
			$reltab = $mod2Inst->relation_table;
			$relidx = $mod2Inst->relation_table_otherid;
			$relidx2 = $mod2Inst->relation_table_id;
			$relmod1 = $relmod2 = '';
		} else if (!empty($mod1Inst->extra_relation_tables[$this->module2])) {
			$reltab = $mod1Inst->extra_relation_tables[$this->module2]['relation_table'];
			$relidx = $mod1Inst->extra_relation_tables[$this->module2]['relation_table_id'];
			$relidx2 = $mod1Inst->extra_relation_tables[$this->module2]['relation_table_otherid'];
			$relmod1 = $mod1Inst->extra_relation_tables[$this->module2]['relation_table_module'];
			$relmod2 = $mod1Inst->extra_relation_tables[$this->module2]['relation_table_othermodule'];
		}
		// crmv@44187e
		return Array(
			'reltab'=>$reltab,
			'relidxCrmid'=>$relidxCrmid,
			'relidx'=>$relidx,
			'relidx2'=>$relidx2,
			'relmod1'=>$relmod1,
			'relmod2'=>$relmod2,
		);
	}
	//crmv@47905 e
	// returns related ids for this relation, starting with specified module
	// TODO: i permessi!!
	// TODO: fix limit for N-N relations
	// crmv@43611	crmv@51605
	function getRelatedIds($crmid, $start = 0, $limit = 0, $onlycount = false, $use_user_permissions = false, $slave = false) { // crmv@185894
		global $adb, $table_prefix, $current_user;

		$ret = array();
		$count = 0;
		
		switch ($this->type) {
			case self::$TYPE_1TON: {
				// fieldname is in secondary module
				$r = self::searchFieldValue($this->fieldid, $crmid, $start, $limit, $use_user_permissions, $slave); // crmv@185894
				if ($r) {
					$ret = $r;
					++$count;
				}
				break;
			}
			case self::$TYPE_NTO1: {
				// TODO $use_user_permissions
				// fieldname is in primary module
				if (!empty($this->fieldid)) {
					$v = getFieldValue($this->fieldid, $crmid);
					if ($v && getSalesEntityType($v) == $this->module2) { // crmv@44187
						$ret[] = $v;
						++$count;
					}
				}
				break;
			}
			case self::$TYPE_NTON: {
				$ntoninfo = $this->getNtoNinfo(); //crmv@47905
				$mod1Inst = CRMEntity::getInstance($this->module1);
				$mod2Inst = CRMEntity::getInstance($this->module2);
				$reltab = $ntoninfo['reltab'];
				$relidxCrmid = $ntoninfo['relidxCrmid'];
				$relidx = $ntoninfo['relidx'];
				$relidx2 = $ntoninfo['relidx2'];
				$relmod1 = $ntoninfo['relmod1'];
				$relmod2 = $ntoninfo['relmod2'];
				//crmv@43864e
				//crmv@171021
				$query =
					"select ".($onlycount ? 'count('.$table_prefix.'_crmentity2.crmid) as cnt' : $table_prefix.'_crmentity2.crmid')."
					from $reltab r
					inner join {$mod1Inst->table_name} mtab on mtab.$relidxCrmid = r.$relidx";
				if (in_array($table_prefix.'_crmentity',$mod1Inst->tab_name)) {
					$query .= " inner join {$table_prefix}_crmentity crm on crm.crmid = mtab.{$mod1Inst->table_index}";
				}
				$query .= " inner join {$table_prefix}_crmentity {$table_prefix}_crmentity2 on {$table_prefix}_crmentity2.crmid = r.$relidx2";
				$query .= " inner join {$mod2Inst->table_name} on {$mod2Inst->table_name}.{$mod2Inst->table_index} = {$table_prefix}_crmentity2.crmid";
				if ($use_user_permissions) {
					//crmv@26650
					if ($this->module2 == 'Calendar')
						$query .= " inner join {$mod2Inst->table_name} {$mod2Inst->table_name}2 on {$mod2Inst->table_name}2.{$mod2Inst->table_index} = {$table_prefix}_crmentity2.crmid";
					//crmv@26650e
					$query .= $mod2Inst->getNonAdminAccessControlQuery($this->module2, $current_user, '2');
				}
				$mod1Inst_delete_column = (in_array($table_prefix.'_crmentity',$mod1Inst->tab_name)) ? 'crm.deleted' : 'mtab.deleted';
				$query .= " where {$mod1Inst_delete_column} = 0 and {$table_prefix}_crmentity2.deleted = 0 and mtab.{$mod1Inst->table_index} = ? and {$table_prefix}_crmentity2.setype = ?";
				//crmv@171021e
				//crmv@52414
				if ($this->module2 == 'Leads') {
					$query .= " and {$mod2Inst->table_name}.converted = 0";
				}
				//crmv@52414e
				if ($use_user_permissions) {
					$query = $mod2Inst->listQueryNonAdminChange($query, $this->module2, '2');
				}
				$params = array($crmid, $this->module2);
				if ($relmod1) {
					$query .= " and r.$relmod1 = ?";
					$params[] = $this->module1;
				}
				if ($relmod2) {
					$query .= " and r.$relmod2 = ?";
					$params[] = $this->module2;
				}

				if ($reltab && $relidxCrmid && $relidx && $relidx2) {
					// crmv@185894
					if ($limit > 0 && !$onlycount) {
						if ($slave)
							$res = $adb->limitpQuerySlave('TurboliftCount', $query, $start, $limit, $params);
						else
							$res = $adb->limitpQuery($query, $start, $limit, $params);
					} else {
						if ($slave)
							$res = $adb->pquerySlave('TurboliftCount', $query, $params);
						else
							$res = $adb->pquery($query, $params);
					}
					// crmv@185894e
					if ($res && $adb->num_rows($res) > 0) {
						if ($onlycount) {
							$count += intval($adb->query_result_no_html($res, 0, 'cnt'));
						} else {
							while ($row = $adb->FetchByAssoc($res, -1, false)) {
								$ret[] = $row['crmid'];
							}
						}
					}
				}

				// module2 -> module1
				// crmv@125816
				if (!$this->direction) {
					$reltab = $mod2Inst->relation_table;
					$relidxCrmid = $mod2Inst->table_index;
					$relidx = $mod2Inst->relation_table_id;
					$relidx2 = $mod2Inst->relation_table_otherid;
					$relmod1 = $mod2Inst->relation_table_module;
					$relmod2 = $mod2Inst->relation_table_othermodule;
					if ($this->module2 == 'Messages') {
						$relidxCrmid = 'messagehash';
						global $onlyquery;
						$onlyquery = true;
						$mod1Inst = CRMEntity::getInstance($this->module1);
						//crmv@60771
						include_once('vtlib/Vtecrm/Module.php');
						$module1_instance = Vtecrm_Module::getInstance($this->module1);
						$module2_instance = Vtecrm_Module::getInstance($this->module2);
						//crmv@60771e
						//crmv@97260
						require('user_privileges/requireUserPrivileges.php');
						if($profileTabsPermission[$module2_instance->id] == 0){ //se l'utente loggato ha il modulo attivo nel profilo allora procedo
							$mod1Inst->get_messages_list($crmid, $module1_instance->id, $module2_instance->id);
							$query = VteSession::get(strtolower($this->module2)."_listquery");
							$params = array();
						} else {
							$relidxCrmid = '';
						}
						//crmv@97260e
					} else {
						//crmv@171021
						$query =
							"select ".($onlycount ? 'count('.$table_prefix.'_crmentity.crmid) as cnt' : $table_prefix.'_crmentity.crmid')."
							from $reltab r
								inner join {$mod2Inst->table_name} on {$mod2Inst->table_name}.$relidxCrmid = r.$relidx
								inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$mod2Inst->table_name}.{$mod2Inst->table_index}";
						if (in_array($table_prefix.'_crmentity',$mod1Inst->tab_name)) {
							$query .= " inner join {$table_prefix}_crmentity crm2 on crm2.crmid = r.$relidx2";
						} else {
							$query .= " inner join {$mod1Inst->table_name} mtab on mtab.{$mod1Inst->table_index} = r.$relidx2";
						}
						if ($use_user_permissions) {
							$query .= $mod2Inst->getNonAdminAccessControlQuery($this->module2, $current_user);
						}
						$mod1Inst_delete_column = (in_array($table_prefix.'_crmentity',$mod1Inst->tab_name)) ? 'crm2.deleted' : 'mtab.deleted';
						$mod1Inst_key_column = (in_array($table_prefix.'_crmentity',$mod1Inst->tab_name)) ? 'crm2.crmid' : 'mtab.'.$mod1Inst->table_index;
						$query .= " where {$table_prefix}_crmentity.deleted = 0 and {$mod1Inst_delete_column} = 0 and {$mod1Inst_key_column} = ? and {$table_prefix}_crmentity.setype = ?";
						//crmv@171021e
						//crmv@52414
						if ($this->module2 == 'Leads') {
							$query .= " and {$mod2Inst->table_name}.converted = 0";
						}
						//crmv@52414e
						if ($use_user_permissions) {
							$query = $mod2Inst->listQueryNonAdminChange($query, $this->module2);
						}
						$params = array($crmid, $this->module2);
						if ($relmod1) {
							$query .= " and r.$relmod1 = ?";
							$params[] = $this->module2;
						}
						if ($relmod2) {
							$query .= " and r.$relmod2 = ?";
							$params[] = $this->module1;
						}
					}
					
					if ($reltab && $relidxCrmid && $relidx && $relidx2) {
						// crmv@185894
						if ($limit > 0 && !$onlycount) {
							if ($slave)
								$res = $adb->limitpQuerySlave('TurboliftCount', $query, $start, $limit, $params);
							else
								$res = $adb->limitpQuery($query, $start, $limit, $params);
						} else {
							if ($slave)
								$res = $adb->pquerySlave('TurboliftCount', $query, $params);
							else
								$res = $adb->pquery($query, $params);
						}
						// crmv@185894e
						if ($res && $adb->num_rows($res) > 0) {
							if ($onlycount) {
								$count += intval($adb->query_result_no_html($res, 0, 'cnt'));
							} else {
								while ($row = $adb->FetchByAssoc($res, -1, false)) {
									$ret[] = $row['crmid'];
								}
							}
						}
					}
				}
				// crmv@125816e

				break;
			}
		}

		return ($onlycount ? $count : $ret);
	}
	
	//crmv@OPER4380
	function getRelatedIdsExtra($crmid, $start = 0, $limit = 0, $onlycount = false) {
		global $adb, $table_prefix,$current_user;
		$ret = array();
		$count = 0;
		include_once('include/Webservices/Extra/WebserviceExtra.php');
		return WebserviceExtra::getRelatedIds($this->module1,$this->module2,$crmid, $start, $limit, $onlycount);
	}	
	//crmv@OPER4380 e

	// crmv@49398
	/**
	 *
	 * Returns an instance of ModuleRelation using the relationid
	 * Supported only N-to-N relations for now
	 */
	static function createFromRelationId($relationId) {
		global $adb, $table_prefix;

		$relation = null;
		$nton_functions = array(
			'get_related_list', 'get_related_list_target', 'get_messages_list', 
			'get_documents_dependents_list', 'get_attachments', 'get_campaigns_newsletter', 
			'get_newsletter_emails', 'get_faxes', 'get_sms', 'get_services', 'get_products', 
			'get_pricebook_products', 'get_product_pricebooks',
			'get_corsi', 'get_contacts_corso', 'get_leads_corso', 'get_accounts_corso', // crmv@155041
		); // crmv@98500
		$nton_ord_functions = array('get_parents_list', 'get_children_list'); // crmv@125816

		if (self::isFakeRelationId($relationId)) {
			$mods = self::getModulesFromFakeRelationId($relationId);
			$relinfo = array(
				'module' => $mods[0],
				'relmodule' => $mods[1],
			);
		} else {
			$res = $adb->pquery(
				"select r.*, t1.name as module, t2.name as relmodule
				from {$table_prefix}_relatedlists r
				inner join {$table_prefix}_tab t1 on t1.tabid = r.tabid
				inner join {$table_prefix}_tab t2 on t2.tabid = r.related_tabid
				where relation_id = ?", array($relationId)
			);
			if (!$res || $adb->num_rows($res) <= 0) return $relation;
			
			$relinfo = $adb->FetchByAssoc($res, -1, false);
		}
		
		// hack for the stupid calendar-contacts relation
		// should be N-N, but of course, there's a stupid field!
		if ($relinfo['module'] == 'Calendar' && $relinfo['relmodule'] == 'Contacts') {
			$nton_functions[] = 'get_contacts';
		} elseif ($relinfo['module'] == 'Contacts' && $relinfo['relmodule'] == 'Calendar') {
			$nton_functions[] = 'get_activities';
		}
		
		// crmv@155041
		// ugly patch for the shitty module Corsi
		if ($relinfo['module'] == 'Corsi' && $relinfo['relmodule'] == 'Contacts') {
			$nton_functions[] = 'get_contacts';
		}
		// crmv@155041e

		// determine type
		if (in_array($relinfo['name'], $nton_functions)) {
			$type = self::$TYPE_NTON;
		// crmv@125816
		} elseif (in_array($relinfo['name'], $nton_ord_functions)) {
			$type = self::$TYPE_NTON;
			$direction = ($relinfo['name'] == 'get_parents_list' ? 'inverse' : 'direct');
		// crmv@125816e
		} elseif (isInventoryModule($relinfo['module']) && isProductModule($relinfo['relmodule'])) {
			$type = self::$TYPE_NTON;
		} elseif (isInventoryModule($relinfo['relmodule']) && isProductModule($relinfo['module'])) {
			$type = self::$TYPE_NTON;
		}

		if ($type) {
			$relation = new ModuleRelation($relinfo['module'], $relinfo['relmodule'], $type);
			if ($type == $type = self::$TYPE_NTON) {
				$relation->relationid = $relationId;
				$relation->relationfn = $relinfo['name']; // crmv@155041
				// crmv@125816
				if ($direction) {
					$relation->direction = $direction;
				}
				// crmv@125816e
				$relation->relationinfo = $relation->getNtoNinfo();
			}
		}

		return $relation;
	}
	// crmv@49398e

	// crmv@96233
	
	/**
	 * @deprecated
	 */
	static function isFakeRelationId($relid) {
		return ($relid >= FakeModules::$baseFakeRelationId);
	}
	
	static function isFakeFieldId($fieldid, &$module = null) {
		foreach (FakeModules::$fakeModules as $fakemod => $fakeinfo) {
			$base = FakeModules::$baseFakeFieldId + $fakeinfo['offset'];
			if ($fieldid >= $base && $fieldid <= $base + $fakeinfo['fields']) {
				$module = $fakemod;
				return true;
			}
		}
		return false;
	}
	
	/**
	 * @deprecated
	 */
	static function getModulesFromFakeRelationId($relid) {
		if (self::isFakeRelationId($relid)) {
			$relid -= FakeModules::$baseFakeRelationId;
			$tabid1 = (int)($relid / FakeModules::$maxRealTabid);
			$tabid2 = (int)($relid % FakeModules::$maxRealTabid);
			$module1 = $module2 = null;
			foreach (FakeModules::$fakeModules as $fakemod => $fakeinfo) {
				if (!$module1) {
					$module1 = ($tabid1 == $fakeinfo['tabid'] ? $fakemod : getTabName($tabid1));
				}
				if (!$module2) {
					$module2 = ($tabid2 == $fakeinfo['tabid'] ? $fakemod : getTabName($tabid2));
				}
			}
			return array($module1, $module2);
		}
		return null;
	}
	
	/**
	 * Returns an array of instances of ModuleRelation using the fieldid (must be an uitype 10)
	 * The relations returned are N-to-1
	 */
	static function createFromFieldId($fieldId) {
		global $adb, $table_prefix;
		
		$fakemod = '';
		if (FakeModules::isFakeFieldId($fieldId, $fakemod)) {
			return self::createFromFakeFieldId($fieldId, $fakemod);
		}
		
		$relations = array();
		
		// get field info
		$res = $adb->pquery("SELECT t.tabid, t.name, f.uitype FROM {$table_prefix}_tab t INNER JOIN {$table_prefix}_field f ON f.tabid = t.tabid WHERE f.fieldid = ?", array($fieldId));
		if ($res && $adb->num_rows($res) > 0) {
			$fieldInfo = $adb->FetchByAssoc($res, -1, false);
			$module = $fieldInfo['name'];
		} else {
			return $relations;
		}
		
		// TODO: handle the special uitypes
		if ($fieldInfo['uitype'] != 10) return $relations;
		
		$query = "select fmr.fieldid,fmr.relmodule, f.fieldname, f.tablename, f.columnname from {$table_prefix}_fieldmodulerel fmr inner join {$table_prefix}_field f on f.fieldid = fmr.fieldid where fmr.fieldid = ?";
		$params = array($fieldId);

		$res = $adb->pquery($query, $params);
		if ($res) {
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$newrel = new ModuleRelation($module, $row['relmodule'], ModuleRelation::$TYPE_NTO1);
				$newrel->fieldid = $row['fieldid'];
				$newrel->fieldname = $row['fieldname'];
				$newrel->fieldtable = $row['tablename'];
				$newrel->fieldcolumn = $row['columnname'];
				$relations[] = $newrel;
			}
		}
		
		return $relations;
	}
	
	static function createFromFakeFieldId($fieldId, $module) {
		
		$relations = array();
		
		$finfo = FakeModules::getFieldInfoById($fieldId, $module);
		
		if ($finfo['uitype'] != 10 || !is_array($finfo['relmodules'])) return $relations;
		
		foreach ($finfo['relmodules'] as $relmod) {
			$newrel = new ModuleRelation($module, $relmod, ModuleRelation::$TYPE_NTO1);
			$newrel->fieldid = $finfo['fieldid'];
			$newrel->fieldname = $finfo['fieldname'];
			$newrel->fieldtable = $finfo['tablename'];
			$newrel->fieldcolumn = $finfo['columnname'];
			$relations[] = $newrel;
		}
		
		return $relations;
	}

	// crmv@96233e

	function countRelatedIds($crmid) {
		return $this->getRelatedIds($crmid, 0,0, true);
	}
	// crmv@43611e	crmv@51605e

	// search for crmids which field value are $value
	// returns array of ids
	static function searchFieldValue($fieldid, $value, $start = 0, $limit = 0, $use_user_permissions = false, $slave = false) {	//crmv@51605 crmv@185894
		global $adb, $table_prefix, $current_user;

		$query =
			"select
				{$table_prefix}_tab.name as modulename,	fieldid, fieldname, tablename, columnname
			from {$table_prefix}_field
				inner join {$table_prefix}_tab on {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
			where fieldid=? and {$table_prefix}_field.presence in (0,2)";

		if ($limit > 0) {
			$res = $adb->limitpQuery($query, $start, $limit, array($fieldid));
		} else {
			$res = $adb->pquery($query, array($fieldid));
		}

		if ($res && $adb->num_rows($res) > 0) {

			$row = $adb->FetchByAssoc($res, -1, false);
			$focus = CRMEntity::getInstance($row['modulename']);
			if (empty($focus)) return null;

			$indexname = $focus->tab_name_index[$row['tablename']];
			if (empty($indexname)) return null;
			
			//crmv@185647
			$use_crmentity = true;
			$entity_table = $table_prefix.'_crmentity';
			if (!in_array($table_prefix.'_crmentity',$focus->tab_name)) {
				$use_crmentity = false;
				$entity_table = $focus->table_name;
			}
			//crmv@185647e

			$ret = array();
			if ($row['tablename'] != "{$table_prefix}_crmentity" && $use_crmentity) { //crmv@185647
				$join = "inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$row['tablename']}.$indexname";
			} else {
				$join = "";
			}
			
			// crmv@72192
			if ($row['tablename'] != $focus->table_name){
				$tname = $focus->table_name;
				$join .= " inner join {$tname} on {$tname}.{$focus->tab_name_index[$tname]} = {$row['tablename']}.$indexname";
			} else{
				//crmv@51605
				if (in_array($row['modulename'],array('Calendar','Events')) && $row['tablename'] != "{$table_prefix}_activity") {
					$join .= " inner join {$table_prefix}_activity on {$table_prefix}_activity.activityid = {$table_prefix}_crmentity.crmid";
				}
			}
			// crmv@72192e

			// crmv@71354 crmv@185647
			$join .= " left join {$table_prefix}_users on {$entity_table}.smownerid = {$table_prefix}_users.id";
			$join .= " left join {$table_prefix}_groups on {$entity_table}.smownerid = {$table_prefix}_groups.groupid";
			// crmv@71354e crmv@185647e

			$query2 = "select {$focus->table_name}.{$focus->tab_name_index[$focus->table_name]} as crmid from {$row['tablename']} $join"; //crmv@185647
			if ($use_user_permissions) {
				$query2 .= $focus->getNonAdminAccessControlQuery($row['modulename'], $current_user);
			}
			//crmv@185647
			$query2 .= " where {$entity_table}.deleted = 0 and {$row['tablename']}.{$row['columnname']} = ?";
			if ($use_crmentity) $query2 .= " and {$table_prefix}_crmentity.setype = ?";
			//crmv@185647e
			if ($use_user_permissions) {
				$query2 = $focus->listQueryNonAdminChange($query2, $row['modulename']);
			}
			if($row['modulename'] == 'Leads') {
				$query2 .= " AND ".$table_prefix."_leaddetails.converted = 0";
			}
			//crmv@185894
			if ($slave)
				$res2 = $adb->pquerySlave('TurboliftCount', $query2, array($value,$row['modulename'])); // crmv@43765
			else
				$res2 = $adb->pquery($query2, array($value,$row['modulename'])); // crmv@43765
			//crmv@185894e
			//crmv@51605e
			if ($res2 && $adb->num_rows($res2) > 0) {
				while ($row2 = $adb->FetchByAssoc($res2, -1, false)) {
					$ret[] = $row2['crmid'];
				}
				return $ret;
			}
		}
		return null;
	}
	
	/**
	 * Invert the relation
	 */
	public function invert() {
		$m1 = $this->module1;
		$this->module1 = $this->module2;
		$this->module2 = $m1;
		
		if ($this->type == self::$TYPE_NTON) {
			if ($this->relationinfo) {
				$i1 = $this->relationinfo['relidx'];
				$this->relationinfo['relidx'] = $this->relationinfo['relidx2'];
				$this->relationinfo['relidx2'] = $i1;
				$m1 = $this->relationinfo['relmod1'];
				$this->relationinfo['relmod1'] = $this->relationinfo['relmod2'];
				$this->relationinfo['relmod2'] = $m1;
				// TODO: the relation id should be changed too!
			}
		} else {
			if ($this->type == self::$TYPE_NTO1) {
				$this->type = self::$TYPE_1TON;
			} else {
				$this->type = self::$TYPE_NTO1;
			}
		}
	}
	
}