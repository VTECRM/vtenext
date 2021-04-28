<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/*
 * crmv@37004 - get relations between modules
 * crmv@97862 - support for fake relationid for inventory relations
 * crmv@127526 - support for generic fake modules
 * crmv@151308 - split in 2 files to support autoloading
 */


class RelationManager extends SDKExtendableUniqueClass { // crmv@42024

	// user object to use when getting privileges
	protected $user = null;

	// special uitypes for relations (uitype->array(destination modules))
	protected $uitype_rel = array(
		// removed 51
		'57' => array("Contacts"),
		'58' => array("Campaigns"),
		'59' => array("Products"),
		// Calendar related_to : dynamically populated
		'66' => array(),
		'68' => array('Accounts','Contacts'),
		// removed 73
		'75' => array("Vendors"),
		'76' => array("Potentials"),
		'78' => array("Quotes"),
		'81' => array("Vendors"),
		'80' => array("SalesOrder"),
		'206' => array("Reports"),
		// TODO: users
	);

	protected $uitype_relid;
	protected $relations; // cache (by module and relation type)
	protected $disableCache = false;
	
	protected $useFakeRelations = false; // if true, use fake modules 
	

	public function __construct($user = null) {
		global $current_user;

		$this->user = (empty($user) ? $current_user : $user);
		$this->relations = array();

		// create dynamic uitype modules list
		$calmod = getCalendarRelatedToModules();
		if (count($calmod) > 0)  $this->uitype_rel['66'] = $calmod;

		// get the tabids for special  uitypes
		foreach ($this->uitype_rel as $uitype=>$modlist) {
			$list = array_map('getTabid', $modlist);
			$this->uitype_relid[$uitype] = $list;
		}

	}
	
	/**
	 * @deprecated
	 */
	public function enablePBRelations() {
		$this->enableFakeRelations();
	}
	
	/**
	 * @deprecated
	 */
	public function disablePBRelations() {
		$this->disableFakeRelations();
	}

	/**
	 * Enable the use of fake modules when retrieving relations
	 */
	public function enableFakeRelations() {
		$this->useFakeRelations = true;
	}
	
	/**
	 * Disable the use of fake modules when retrieving relations
	 */
	public function disableFakeRelations() {
		$this->useFakeRelations = false;
	}
	
	// ----- cache related functions -----
	function disableCache() {
		$this->disableCache = true;
		$this->clearCache();
	}

	function enableCache() {
		$this->disableCache = false;
	}

	function getCachedRelations($module, $type, $relmodules = array(), $excludeModules = array()) {
		$ret = $this->relations[$module][$type];
		// FILTER modules
		if (is_array($ret) && (count($relmodules) > 0 || count($excludeModules) > 0)) {
			foreach ($ret as $k=>$relation) {
				$destmod = $relation->getSecondModule();
				if ((count($relmodules) > 0 && !in_array($destmod, $relmodules)) || (count($excludeModules) > 0 && in_array($destmod, $excludeModules))) unset($ret[$k]); // crmv@171029
			}
		}
		return $ret;
	}

	function hasCachedRelation($module, $type) {
		return (!$this->disableCache && is_array($this->relations[$module][$type]));
	}

	function initializeCache($module, $type) {
		$this->relations[$module][$type] = array();
	}

	function addRelationToCache($relation) {
		$module = $relation->getFirstModule();
		$type = $relation->getType();
		$this->relations[$module][$type][] = $relation;
	}

	function clearCache() {
		$this->relations = null;
	}
	// ----- end cache related functions -----

	// return a list of all modules related to the specified module (optionally filtered for destination module)
	// type is a OR between ModuleRelation::$TYPE_*
	function getRelations($module, $type = null, $relmodules = array(), $excludeModules = array(), $crmid = null) { //crmv@150751
		global $adb, $table_prefix;
		
		// check if first module is fake
		$fakeModules = FakeModules::getModules();
		if ($this->useFakeRelations && in_array($module, $fakeModules)) return $this->getFakeRelations($module, $type, $relmodules, $excludeModules);

		if (is_null($type)) $type = ModuleRelation::$TYPE_ALL;
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		if (!is_array($excludeModules)) $excludeModules = array($excludeModules);

		$relmodules = array_diff($relmodules, $excludeModules);
		
		// save cache only if no module filtering
		$saveCache = (!$this->disableCache && (count($relmodules) == 0) && (count($excludeModules) == 0));

		$moduleid = getTabid($module);
		$moduleInst = CRMEntity::getInstance($module);

		$relations = array();

		// N-1
		if ($type & ModuleRelation::$TYPE_NTO1) {
			if ($this->hasCachedRelation($module, ModuleRelation::$TYPE_NTO1)) {
				$relations = array_merge($relations, $this->getCachedRelations($module, ModuleRelation::$TYPE_NTO1, $relmodules, $excludeModules));
			} else {
				if ($saveCache) $this->initializeCache($module, ModuleRelation::$TYPE_NTO1);
				$query = "select fmr.fieldid,fmr.relmodule, f.fieldname, f.tablename, f.columnname from {$table_prefix}_fieldmodulerel fmr inner join {$table_prefix}_field f on f.fieldid = fmr.fieldid where fmr.module = ?"; // crmv@42752
				$params = array($module);

				if (!empty($relmodules)) {
					$query .= " and fmr.relmodule in (".generateQuestionMarks($relmodules).")";
					$params[] = $relmodules;
				}
				if (!empty($excludeModules)) {
					$query .= " and fmr.relmodule not in (".generateQuestionMarks($excludeModules).")";
					$params[] = $excludeModules;
				}
				$res = $adb->pquery($query, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						// crmv@100399 - Events have a field, but it's a NtoN relation
						if ($module == 'Events' && $row['relmodule'] == 'Contacts') {
							continue;
						}
						// crmv@100399e
						$newrel = new ModuleRelation($module, $row['relmodule'], ModuleRelation::$TYPE_NTO1);
						$newrel->fieldid = $row['fieldid'];
						$newrel->fieldname = $row['fieldname'];
						$newrel->fieldtable = $row['tablename'];
						$newrel->fieldcolumn = $row['columnname'];
						$relations[] = $newrel;
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}

				// special uitypes, N-1
				$uitypeRelFiltered = $this->uitype_rel;
				if (!empty($relmodules)) {
					foreach ($uitypeRelFiltered as $ukey=>$kmods) {
						$kmods2 = array_intersect($kmods, $relmodules);
						if (count($kmods2) == 0) {
							unset($uitypeRelFiltered[$ukey]);
						} elseif (count($kmods2) != count($kmods)) {
							$uitypeRelFiltered[$ukey] = $kmods2;
						}
					}
				} elseif (!empty($excludeModules)) {
					foreach ($uitypeRelFiltered as $ukey=>$kmods) {
						$kmods2 = array_diff($kmods, $excludeModules);
						if (count($kmods2) == 0) {
							unset($uitypeRelFiltered[$ukey]);
						} elseif (count($kmods2) != count($kmods)) {
							$uitypeRelFiltered[$ukey] = $kmods2;
						}
					}
				}
				if (count($uitypeRelFiltered) > 0) {
					$uitypeFields = array_keys($uitypeRelFiltered);
					$res = $adb->pquery("select fld.fieldid, fld.uitype, fld.fieldname, fld.tablename, fld.columnname from {$table_prefix}_field fld where fld.tabid = ? and fld.uitype in (".generateQuestionMarks($uitypeFields).")", array($moduleid, $uitypeFields)); //crmv@42752
					if ($res) {
						while ($row = $adb->FetchByAssoc($res, -1, false)) {
							$uitype = $row['uitype'];
							foreach ($uitypeRelFiltered[$uitype] as $relmod) {
								$newrel = new ModuleRelation($module, $relmod, ModuleRelation::$TYPE_NTO1);
								$newrel->fieldid = $row['fieldid'];
								$newrel->fieldname = $row['fieldname'];
								$newrel->fieldtable = $row['tablename'];
								$newrel->fieldcolumn = $row['columnname'];
								$relations[] = $newrel;
								if ($saveCache) $this->addRelationToCache($newrel);
							}
						}
					}
				}
			}
		}

		// 1-N
		if ($type & ModuleRelation::$TYPE_1TON) {
			if ($this->hasCachedRelation($module, ModuleRelation::$TYPE_1TON)) {
				$relations = array_merge($relations, $this->getCachedRelations($module, ModuleRelation::$TYPE_1TON, $relmodules, $excludeModules));
			} else {
				if ($saveCache) $this->initializeCache($module, ModuleRelation::$TYPE_1TON);
				$query = "select fmr.fieldid,fmr.module, f.fieldname, f.tablename, f.columnname from {$table_prefix}_fieldmodulerel fmr inner join {$table_prefix}_field f on f.fieldid = fmr.fieldid where fmr.relmodule = ?"; //crmv@42752
				$params = array($module);
				if (!empty($relmodules)) {
					$query .= " and fmr.module in (".generateQuestionMarks($relmodules).")";
					$params[] = $relmodules;
				}
				if (!empty($excludeModules)) {
					$query .= " and fmr.module not in (".generateQuestionMarks($excludeModules).")";
					$params[] = $excludeModules;
				}
				$res = $adb->pquery($query, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						// crmv@100399 - Events have a field, but it's a NtoN relation
						if ($module == 'Contacts' && $row['module'] == 'Events') {
							continue;
						}
						// crmv@100399e
						$newrel = new ModuleRelation($module, $row['module'], ModuleRelation::$TYPE_1TON);
						$newrel->fieldid = $row['fieldid'];
						$newrel->fieldname = $row['fieldname'];
						$newrel->fieldtable = $row['tablename'];
						$newrel->fieldcolumn = $row['columnname'];
						// crmv@43864 crmv@150751 - search for a related
						require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
						$PMUtils = ProcessMakerUtils::getInstance();
						if (!empty($crmid)) $tvh_id = $PMUtils->getSystemVersion4Record($crmid,array('tabs',$module,'id'));
						if (!empty($tvh_id)) {
							$relres = $adb->pquery("select relation_id, name from {$table_prefix}_relatedlists_vh where versionid = ? and tabid = ? and related_tabid = ?", array($tvh_id, getTabid($module), getTabid($row['module'])));
						} else {
							$relres = $adb->pquery("select relation_id, name from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ?", array(getTabid($module), getTabid($row['module']))); // crmv@155041
						}
						if ($relres && $adb->num_rows($relres) == 1) {
							$newrel->relationid = $adb->query_result_no_html($relres, 0, 'relation_id');
							$newrel->relationfn = $adb->query_result_no_html($relres, 0, 'name'); // crmv@155041
						}
						// crmv@43864e crmv@150751e
						$relations[] = $newrel;
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}

				// special uitypes, 1-N
				$uitypeFields = array();
				foreach ($this->uitype_rel as $uitype=>$listmod) {
					if (in_array($module, $listmod)) $uitypeFields[] = $uitype;
				}
				if (count($uitypeFields) > 0) {
					$query = "select fld.fieldid, fld.tabid,fld.fieldname,fld.tablename,fld.columnname, tab.name from {$table_prefix}_field fld inner join {$table_prefix}_tab tab on tab.tabid = fld.tabid where fld.uitype in (".generateQuestionMarks($uitypeFields).")"; //crmv@42752
					$params = array($uitypeFields);
					if (!empty($relmodules)) {
						$query .= " and tab.name in (".generateQuestionMarks($relmodules).")";
						$params[] = $relmodules;
					}
					if (!empty($excludeModules)) {
						$query .= " and tab.name not in (".generateQuestionMarks($excludeModules).")";
						$params[] = $excludeModules;
					}
					$res = $adb->pquery($query, $params);
					if ($res) {
						while ($row = $adb->FetchByAssoc($res, -1, false)) {
							$newrel = new ModuleRelation($module, $row['name'], ModuleRelation::$TYPE_1TON);
							$newrel->fieldid = $row['fieldid'];
							$newrel->fieldname = $row['fieldname'];
							$newrel->fieldtable = $row['tablename'];
							$newrel->fieldcolumn = $row['columnname'];
							$relations[] = $newrel;
							if ($saveCache) $this->addRelationToCache($newrel);
						}
					}
				}
				
				// fake modules
				if ($this->useFakeRelations) {
					foreach ($fakeModules as $fakeModule) {
						$fields = FakeModules::getFields($fakeModule);
						foreach ($fields as $finfo) {
							if ($finfo['uitype'] == 10 && in_array($module, $finfo['relmodules'])) {
								$newrel = new ModuleRelation($module, $fakeModule, ModuleRelation::$TYPE_1TON);
								$newrel->fieldid = $finfo['fieldid'];
								$newrel->fieldname = $finfo['fieldname'];
								$newrel->fieldtable = $finfo['tablename'];
								$newrel->fieldcolumn = $finfo['columnname'];
								$relations[] = $newrel;
								if ($saveCache) $this->addRelationToCache($newrel);
							}
						}
					}
				}
			}
		}

		// N-N - MOLTO BETA!!
		// 1. usare relatedlists e vedere se ci sono 2 related (nelle 2 direzioni)
		// 2. vedere se ci sono relatedlist che usano le funzioni N-N (get_related_list-> per ora cablate!! ARGH)
		// 3. aggiungere relazioni per i moduli con prodotti
		if ($type & ModuleRelation::$TYPE_NTON) {
			if ($this->hasCachedRelation($module, ModuleRelation::$TYPE_NTON)) {
				$relations = array_merge($relations, $this->getCachedRelations($module, ModuleRelation::$TYPE_NTON, $relmodules, $excludeModules));
			} else {
				if ($saveCache) $this->initializeCache($module, ModuleRelation::$TYPE_NTON);
				
				$found_relid = array();
				
				// crmv@125816
				$ntonFunctions = array('get_related_list', 'get_related_list_target', 'get_messages_list', 'get_documents_dependents_list', 'get_attachments', 'get_campaigns_newsletter', 'get_newsletter_emails', 'get_faxes', 'get_sms'); // crmv@38798 crmv@43765
				$ntonOrdFunctions = array('get_parents_list', 'get_children_list');
				
				// 1
						
				$query = "
					SELECT r1.relation_id, r2.relation_id as relation_id2, tab1.name as mod1, tab2.name as mod2, r1.name
					FROM {$table_prefix}_relatedlists r1
						INNER JOIN {$table_prefix}_relatedlists r2 on r2.tabid = r1.related_tabid and r2.related_tabid = r1.tabid
						INNER JOIN {$table_prefix}_tab tab1 on tab1.tabid = r1.tabid
						INNER JOIN {$table_prefix}_tab tab2 on tab2.tabid = r1.related_tabid
					WHERE r1.tabid = ? and r1.relation_id <> r2.relation_id"; // crmv@43611
				$params = array($moduleid);
				// crmv@125816e
				
				if (!empty($relmodules)) {
					$query .= " and tab2.name in (".generateQuestionMarks($relmodules).")";
					$params[] = $relmodules;
				}
				if (!empty($excludeModules)) {
					$query .= " and tab2.name not in (".generateQuestionMarks($excludeModules).")";
					$params[] = $excludeModules;
				}
				$res = $adb->pquery($query, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						$newrel = new ModuleRelation($module, $row['mod2'], ModuleRelation::$TYPE_NTON);
						$newrel->relationid = $row['relation_id'];
						$newrel->relationfn = $row['name']; // crmv@155041
						// crmv@125816
						if (in_array($row['name'], $ntonOrdFunctions)) {
							$newrel->direction = ($row['name'] == 'get_parents_list' ? 'inverse' : 'direct');
						}
						// crmv@125816e
						$newrel->relationinfo = $newrel->getNtoNinfo(); //crmv@47905
						$relations[] = $newrel;
						$found_relid[$row['relation_id']] = $row['relation_id'];
						$found_relid[$row['relation_id2']] = $row['relation_id2'];
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}

				//2.1
				
				$query = "
					SELECT r.relation_id, tab1.name as mod1, tab2.name as mod2, r.name
						FROM {$table_prefix}_relatedlists r
						INNER JOIN {$table_prefix}_tab tab1 on tab1.tabid = r.tabid
						INNER JOIN {$table_prefix}_tab tab2 on tab2.tabid = r.related_tabid
					WHERE r.related_tabid = ? and r.tabid != r.related_tabid and r.name in (".generateQuestionMarks($ntonFunctions).")";
				$params = array($moduleid, $ntonFunctions);
				if (!empty($found_relid)) {
					$query .= " and r.relation_id not in (".generateQuestionMarks($found_relid).")";
					$params[] = $found_relid;
				}
				if (!empty($relmodules)) {
					$query .= " and tab1.name in (".generateQuestionMarks($relmodules).")";	// crmv@43611
					$params[] = $relmodules;
				}
				if (!empty($excludeModules)) {
					$query .= " and tab1.name not in (".generateQuestionMarks($excludeModules).")"; // crmv@43611
					$params[] = $excludeModules;
				}
				$res = $adb->pquery($query, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						$newrel = new ModuleRelation($module, $row['mod1'], ModuleRelation::$TYPE_NTON);
						$newrel->relationid = $row['relation_id'];
						$newrel->relationfn = $row['name']; // crmv@155041
						$newrel->relationinfo = $newrel->getNtoNinfo(); // crmv@47905 crmv@54449
						$relations[] = $newrel;
						$found_relid[$row['relation_id']] = $row['relation_id'];
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}

				//2.2
				// crmv@155041
				$query = "
					SELECT r.relation_id, tab1.name as mod1, tab2.name as mod2, r.name
						FROM {$table_prefix}_relatedlists r
						INNER JOIN {$table_prefix}_tab tab1 on tab1.tabid = r.tabid
						INNER JOIN {$table_prefix}_tab tab2 on tab2.tabid = r.related_tabid
					WHERE r.tabid = ? and r.tabid != r.related_tabid and r.name in (".generateQuestionMarks($ntonFunctions).")";
				$params = array($moduleid, $ntonFunctions);
				if (!empty($found_relid)) {
					$query .= " and r.relation_id not in (".generateQuestionMarks($found_relid).")";
					$params[] = $found_relid;
				}
				if (!empty($relmodules)) {
					$query .= " and tab2.name in (".generateQuestionMarks($relmodules).")"; // crmv@43611
					$params[] = $relmodules;
				}
				if (!empty($excludeModules)) {
					$query .= " and tab2.name not in (".generateQuestionMarks($excludeModules).")"; // crmv@43611
					$params[] = $excludeModules;
				}
				$res = $adb->pquery($query, $params);
				if ($res) {
					while ($row = $adb->FetchByAssoc($res, -1, false)) {
						$newrel = new ModuleRelation($module, $row['mod2'], ModuleRelation::$TYPE_NTON);
						$newrel->relationid = $row['relation_id'];
						$newrel->relationfn = $row['name']; // crmv@155041
						$relations[] = $newrel;
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}
				
				// crmv@100399
				//2.3 Special relation Events - Contacts (there's a field, but it's actually a N-N)
				if ($module == 'Events' && (empty($relmodules) || in_array('Contacts', $relmodules)) && (empty($excludeModules) || !in_array('Contacts', $excludeModules))) {
					$query = "SELECT r.relation_id FROM {$table_prefix}_relatedlists r WHERE r.tabid = ? and r.name = ?";
					$res = $adb->pquery($query, array(9, 'get_contacts'));
					if ($res && $adb->num_rows($res) > 0) {
						$relid = $adb->query_result_no_html($res, 0, 'relation_id');
						$newrel = new ModuleRelation($module, 'Contacts', ModuleRelation::$TYPE_NTON);
						$newrel->relationid = $relid;
						$newrel->relationinfo = $newrel->getNtoNinfo(); // crmv@47905 crmv@54449
						$relations[] = $newrel;
						$found_relid[$relid] = $relid;
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				} elseif ($module == 'Contacts' && (empty($relmodules) || in_array('Events', $relmodules)) && (empty($excludeModules) || !in_array('Events', $excludeModules))) {
					$query = "SELECT r.relation_id FROM {$table_prefix}_relatedlists r WHERE r.tabid = ? and r.name = ?";
					$res = $adb->pquery($query, array(getTabid('Contacts'), 'get_activities'));
					if ($res && $adb->num_rows($res) > 0) {
						$relid = $adb->query_result_no_html($res, 0, 'relation_id');
						$newrel = new ModuleRelation($module, 'Events', ModuleRelation::$TYPE_NTON);
						$newrel->relationid = $relid;
						$newrel->relationinfo = $newrel->getNtoNinfo(); // crmv@47905 crmv@54449
						$relations[] = $newrel;
						$found_relid[$relid] = $relid;
						if ($saveCache) $this->addRelationToCache($newrel);
					}
				}
				// crmv@100399e
				
				//3.1
				if (isInventoryModule($module)) {
					if (!$this->useFakeRelations) { // with fake relations I have 2 N-1 relations
						$prodmods = getProductModules();
						// crmv@38798
						foreach ($prodmods as $prodmod) {
							if ((empty($relmodules) || in_array($prodmod, $relmodules)) && !in_array($prodmod, $excludeModules)) {
								$newrel = new ModuleRelation($module, $prodmod, ModuleRelation::$TYPE_NTON);
								$newrel->relationinfo = $newrel->getNtoNinfo();
								$relations[] = $newrel;
								if ($saveCache) $this->addRelationToCache($newrel);
							}
						}
					}
					// crmv@38798e
				}

				// 3.2
				if (isProductModule($module) && !$this->useFakeRelations) { // crmv@38798 crmv@64542
					$imods = getInventoryModules();
					if (!empty($relmodules)) $imods = array_intersect($imods, $relmodules);
					$imods = array_diff($imods, $excludeModules);
					if (!empty($imods)) {
						//crmv@3086m
						$relation_ids = array();
						$query = "
							SELECT r.relation_id, tab1.name as mod1, tab2.name as mod2
								FROM {$table_prefix}_relatedlists r
								INNER JOIN {$table_prefix}_tab tab1 on tab1.tabid = r.tabid
								INNER JOIN {$table_prefix}_tab tab2 on tab2.tabid = r.related_tabid
							WHERE r.tabid = ? and tab2.name in (".generateQuestionMarks($imods).")";
						$params = array($moduleid, $imods);
						$res = $adb->pquery($query, $params);
						if ($res) {
							while ($row = $adb->FetchByAssoc($res, -1, false)) {
								$relation_ids[$row['mod2']] = $row['relation_id'];
							}
						}
						//crmv@3086me
						foreach ($imods as $imod) {
							$newrel = new ModuleRelation($module, $imod, ModuleRelation::$TYPE_NTON);
							if (!empty($relation_ids[$imod])) $newrel->relationid = $relation_ids[$imod];	//crmv@3086m
							$newrel->relationinfo = $newrel->getNtoNinfo();
							$relations[] = $newrel;
							if ($saveCache) $this->addRelationToCache($newrel);
						}
					}
				}
			}
		}

		return $relations;
	}
	
	/**
	 * Return relations from the special "ProductsBlock" module
	 */
	function getFakeRelations($module, $type = null, $relmodules = array(), $excludeModules = array()) {
		global $adb, $table_prefix;
		
		if (is_null($type)) $type = ModuleRelation::$TYPE_ALL;
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		if (!is_array($excludeModules)) $excludeModules = array($excludeModules);

		$relmodules = array_diff($relmodules, $excludeModules);

		// save cache only if no module filtering
		$saveCache = (!$this->disableCache && (count($relmodules) == 0) && (count($excludeModules) == 0));

		$relations = array();

		// N-1
		if ($type & ModuleRelation::$TYPE_NTO1) {
			if ($this->hasCachedRelation($module, ModuleRelation::$TYPE_NTO1)) {
				$relations = array_merge($relations, $this->getCachedRelations($module, ModuleRelation::$TYPE_NTO1, $relmodules, $excludeModules));
			} else {
				if ($saveCache) $this->initializeCache($module, ModuleRelation::$TYPE_NTO1);
				
				$fields = FakeModules::getFields($module);
				foreach ($fields as $finfo) {
					if ($finfo['uitype'] == 10 && is_array($finfo['relmodules'])) {
						foreach ($finfo['relmodules'] as $relmod) {
							if (empty($relmodules) || in_array($relmod, $relmodules)) {
								$newrel = new ModuleRelation($module, $relmod, ModuleRelation::$TYPE_NTO1);
								$newrel->fieldid = $finfo['fieldid'];
								$newrel->fieldname = $finfo['fieldname'];
								$newrel->fieldtable = $finfo['tablename'];
								$newrel->fieldcolumn = $finfo['columnname'];
								$relations[] = $newrel;
								if ($saveCache) $this->addRelationToCache($newrel);
							}
						}
					}
				}
			}
		}

		return $relations;
	}
	
	//crmv@OPER4380
	// return a list of all modules related to the specified module (optionally filtered for destination module)
	// type is a OR between ModuleRelation::$TYPE_*
	function getRelationsExtra($module, $type = null, $relmodules = array(), $excludeModules = array()) {
		global $adb, $table_prefix,$current_user;

		if (is_null($type)) $type = ModuleRelation::$TYPE_ALL;
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		if (!is_array($excludeModules)) $excludeModules = array($excludeModules);
		//crmv@47013
		$result = $adb->query("SELECT tabid, name FROM {$table_prefix}_tab WHERE presence = 1");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!in_array($row['name'],$excludeModules)) $excludeModules[] = $row['name'];
			}
		}
		//crmv@47013e

		$relmodules = array_diff($relmodules, $excludeModules);

		// save cache only if no module filtering
		$saveCache = (!$this->disableCache && (count($relmodules) == 0) && (count($excludeModules) == 0));
		
		$moduleid = getTabid($module);
		$moduleInst = CRMEntity::getInstance($module);
		// possible extra relations: Transitions
		include_once('include/Webservices/Extra/WebserviceExtra.php');
		$relations = WebserviceExtra::getExtraModulesRelatedTo($module,$this);
		return $relations;
	}
	//crmv@OPER4380 e

	function getRelatedModules($module) {
		$modrel = $this->getRelations($module);

		$ret = array();
		if (is_array($modrel)) {
			foreach ($modrel as $relobj) {
				if(getTabid($relobj->getSecondModule())) //crmv@68610
					$ret[$relobj->getSecondModule()] = true;
			}
			$ret = array_keys($ret);
		}

		return $ret;
	}

	// true if module1 is somehow related to module2
	function isModuleRelated($module1, $module2) {
		return in_array($module1, $this->getRelatedModules($module2));
	}

	// return a list of all related records of a record
	// return: array( id=>array('setype'=>type, 'field'=>fieldid, 'related'=>array(relid,...) ... )
	// TODO: gestire profondit� maggiore di 1 (record collegato a x -> collegato a... )
	function getRelatedIds($module, $crmid, $relmodules = array(), $excludeMods = array(), $recursive = false, $groupByModule = false) {
		global $adb, $table_prefix;

		$ret = array();
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		$relmodules = array_filter($relmodules);

		// get relations filtered by module
		$relations = $this->getRelations($module, null, $relmodules, $excludeMods);

		if (is_array($relations)) {
			foreach ($relations as $relation) {
				$ids = $relation->getRelatedIds($crmid);
				if (!empty($ids)) {
					$relation->getSecondModule();
					if ($groupByModule) {
						if (empty($ret[$relation->getSecondModule()])) {
							$ret[$relation->getSecondModule()] = $ids;
						} else {
							$ret[$relation->getSecondModule()] = array_merge($ret[$relation->getSecondModule()], $ids);
						}
						$ret[$relation->getSecondModule()] = array_unique($ret[$relation->getSecondModule()]);
					} else {
						$ret = array_merge($ret, $ids);
					}
				}
			}
		}
		if ($groupByModule) {
			return $ret;
		} else {
			return array_unique($ret);
		}
	}
	
	//crmv@OPER4380
	function getRelatedIdsExtra($module, $crmid, $relmodules = array(), $excludeMods = array(), $recursive = false, $groupByModule = false) {
		global $adb, $table_prefix;
		$ret = array();
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		$relmodules = array_filter($relmodules);

		// get relations filtered by module
		$relations = $this->getRelationsExtra($module, null, $relmodules, $excludeMods);
		if (is_array($relations)) {
			foreach ($relations as $relmodule=>$relation) {
				$ids = $relation->getRelatedIdsExtra($crmid);
				if (!empty($ids)) {
					$relation->getSecondModule();
					if ($groupByModule) {
						if (empty($ret[$relation->getSecondModule()])) {
							$ret[$relation->getSecondModule()] = $ids;
						} else {
							$ret[$relation->getSecondModule()] = array_merge($ret[$relation->getSecondModule()], $ids);
						}
						$ret[$relation->getSecondModule()] = array_unique($ret[$relation->getSecondModule()]);
					} else {
						$ret = array_merge($ret, $ids);
					}
				}
			}
		}
		if ($groupByModule) {
			return $ret;
		} else {
			return array_unique($ret);
		}
	}	
	//crmv@OPER4380 e

	function countRelatedIds($module, $crmid, $relmodules = array(), $excludeMods = array(), $recursive = false) {
		global $adb, $table_prefix;

		$ret = array();
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		$relmodules = array_filter($relmodules);

		// get relations filtered by module
		$relations = $this->getRelations($module, null, $relmodules, $excludeMods);

		if (is_array($relations)) {
			foreach ($relations as $relation) {
				$cnt = $relation->countRelatedIds($crmid);
				$ret[$relation->getSecondModule()] = $cnt;
			}
		}

		return $ret;
	}
	
	// crmv@193294
	public function getTabRelated($panelid, $module, $crmid) {
		static $tabrelCache = array();
		
		if (!$panelid) return array();
		
		$ckey = "{$panelid}_{$module}_{$crmid}";
		if (!isset($tabrelCache[$ckey])) {
			$tabRelated = array();
			$tab = Vtecrm_Panel::getInstance($panelid, Vtecrm_Module::getInstance($module), $crmid);
			if ($tab) {
				$tabrels = $tab->getRelatedLists();
				// relation id as key
				if (is_array($tabrels)) {
					foreach ($tabrels as $rel) {
						$tabRelated[$rel['id']] = $rel;
					}
				}
			}
			$tabrelCache[$ckey] = $tabRelated;
		}
		
		return $tabrelCache[$ckey];
	}
	// crmv@193294e

	//crmv@43864 crmv@3086m	crmv@57221 crmv@104568 crmv@150751
	function getTurboliftRelations($focus, $module, $crmid, $relmodules = array(), $excludeMods = array(), $recursive = false, $panelid = null) {
		global $adb, $table_prefix;
		
		$relations = $pins = array();
		if (!is_array($relmodules)) $relmodules = array($relmodules);
		$relmodules = array_filter($relmodules);
		
		$CRMVUtils = CRMVUtils::getInstance();
		//TODO:per relationid
		$pinRelatedLists = $CRMVUtils->getPinRelatedLists(getTabid($module),$crmid);
		$pinRelationIds = $CRMVUtils->getPinRelationIds(getTabid($module),$crmid); //crmv@62415
		$oldStyle = $CRMVUtils->getConfigurationLayout('old_style');
		$tbRelationsOrder = $CRMVUtils->getConfigurationLayout('tb_relations_order');

		// crmv@193294
		$allTabRelated = array();
		$tabRelated = $this->getTabRelated($panelid, $module, $crmid);
		// crmv@193294e
				
		// get all fixed related lists
		$vh_info = array(
			'panel2rlist' => array($table_prefix.'_panel2rlist pr'),
			'panels' => array($table_prefix.'_panels p','p.panelid = pr.panelid','p.tabid = ?',getTabid($module)),
		);
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$PMUtils = ProcessMakerUtils::getInstance();
		$tvh_id = $PMUtils->getSystemVersion4Record($crmid,array('tabs',$module,'id'));
		if (!empty($tvh_id)) {
			$vh_info = array(
				'panel2rlist' => array($table_prefix.'_panel2rlist_vh pr'),
				'panels' => array($table_prefix.'_panels_vh p','p.panelid = pr.panelid and p.versionid = pr.versionid','p.tabid = ? and p.versionid = ?',array(getTabid($module),$tvh_id)),
			);
		}
		$res = $adb->pquery(
			"SELECT pr.relation_id FROM {$vh_info['panel2rlist'][0]}
			INNER JOIN {$vh_info['panels'][0]} ON {$vh_info['panels'][1]}
			WHERE {$vh_info['panels'][2]}",
			array($vh_info['panels'][3])
		);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$allTabRelated[] = (int)$row['relation_id'];
			}
		}
		
		$sdkInfo = SDK::getTurboliftCountInfo();	//crmv@51605
		
		// get related lists
		$related_array = getRelatedLists($module,$focus);
		if (!empty($related_array)) {
			$related_array_info = array();
			$sequence = 0;
			foreach ($related_array as $header => $info) {
				if ($info['presence'] == 2) continue; //crmv@170167 presence 2 for skip in turbolift
				if (!empty($excludeMods) && in_array($info['related_tabname'], $excludeMods)) continue; // crmv@109871
				$relinfo = array(
					'sequence'=>$sequence,
					'header'=>$header,
					'related_module'=>$info['related_tabname'],
					'relationId'=>$info['relationId'],
					'actions'=>$info['actions'],
					'name'=>$info['name'],
				);
				if (in_array($info['relationId'], $allTabRelated)) {
					$relinfo['fixed'] = true;
				}
				if (array_key_exists($info['relationId'], $tabRelated)) {
					$relinfo['sequence'] = $tabRelated[$info['relationId']]['sequence'];
				} else {
					// make sure the ones added by the user are after
					$relinfo['sequence'] += 100;
				}
				$related_array_info[$info['relationId']] = $relinfo;
				// crmv@140887
				$relatedImg = 'themes/images/modulesimg/'.$info['related_tabname'].'.png';
				if (file_exists($relatedImg)) {
					$RV = ResourceVersion::getInstance();
					$related_array_info[$info['relationId']]['img'] = $RV->getResource($relatedImg);
				}
				// crmv@140887e
				//crmv@51605
				if (!empty($sdkInfo[$info['relationId']])) {
					// crmv@115634 - do not exclude used mods, allow repetitions!
					if (PerformancePrefs::getBoolean('RELATED_LIST_COUNT')) { // crmv@115378
						$related_array_info[$info['relationId']]['count'] = SDK::getTurboliftCount($info['relationId'], $crmid);
					}
				}
				//crmv@51605e
				if ($oldStyle) {
					$related_array_info[$info['relationId']]['buttons'] = $this->getRelatedListButtons($info, $module, $crmid);
				}
				$sequence++;
			}
		}
		
		if (PerformancePrefs::getBoolean('RELATED_LIST_COUNT')) { // crmv@115378
			$relations1N = $this->getRelations($module, ModuleRelation::$TYPE_1TON, $relmodules, $excludeMods, $crmid); //crmv@150751
			$relationsNN = $this->getRelations($module, ModuleRelation::$TYPE_NTON, $relmodules, $excludeMods, $crmid); //crmv@150751
			$rl = array();
			if (is_array($relations1N)) {
				foreach ($relations1N as $relation) {
					$relationid = (empty($relation->relationid) ? 'fld_'.$relation->fieldid : $relation->relationid);
					$ids = $relation->getRelatedIds($crmid, null, null, null, true, true);	//crmv@51605 // crmv@185894
					if (!empty($ids)) {
						$rl[$relationid] = array('secmod'=>$relation->getSecondModule(), 'ids'=>$ids);
					}
				}
			}
			if (is_array($relationsNN)) {
				foreach ($relationsNN as $relation) {
					$relationid = $relation->relationid;
				  	$ids = $relation->getRelatedIds($crmid, null, null, null, true, true);	//crmv@51605 // crmv@185894
					if (!empty($ids)) {
						if (empty($rl[$relationid])) {
							$rl[$relationid] = array('secmod'=>$relation->getSecondModule(), 'ids'=>$ids);
						} else {
							$rl[$relationid]['ids'] = array_merge($rl[$relationid]['ids'], $ids);
						}
					}
				}
			}
			foreach ($rl as $relationid => $relinfo) {
				$mod = $relinfo['secmod'];
				$ids = array_unique($relinfo['ids']);
				// TODO: services??
				if (isInventoryModule($module) && in_array($mod,array('Products'))) {
					continue;
				}
				$pin = false;
				$display = 'block';
				if (!empty($pinRelatedLists[$mod])) {
					$pins[$pinRelatedLists[$mod]] = $related_array[$pinRelatedLists[$mod]];
					$pins[$pinRelatedLists[$mod]] = array_merge($pins[$pinRelatedLists[$mod]],$related_array_info[$relationid]);
					$pin = true;
					$display = 'none';
				} elseif (array_key_exists($relationid, $tabRelated)) {
					$pins[$mod] = $related_array[$mod];
					if ($mod == 'Calendar' && empty($pins[$mod]) && isset($related_array['Activities'])) $pins[$mod] = $related_array['Activities'];
					$pins[$mod] = array_merge($pins[$mod],$related_array_info[$relationid]);
					$pin = true;
					$display = 'none';
				}
				$tmp = array(
					'type'=>'other',
					'display'=>$display,
					'module'=>$mod,
					'count'=>count($ids),
					'sequence'=>$related_array_info[$relationid]['sequence']
				);
				//crmv@62415
				if (in_array($relationid,$pinRelationIds) || array_key_exists($relationid, $tabRelated)){
					$tmp['pinned'] = true;
				}
				else{
					$tmp['pinned'] = false;
				}
				//crmv@62415 e
				if (!empty($related_array_info[$relationid])) {
					$tmp = array_merge($tmp,$related_array_info[$relationid]);
					unset($related_array_info[$relationid]);
				}
				if (empty($tmp['relationId'])) continue;
				$relations[] = $tmp;
			}
			//crmv@62415 crmv@128523
			//FORCE ODLSTYLE FOR PINS
			foreach ($pinRelatedLists as $mod => $prl) {
				if (!isset($pins[$prl]) && array_key_exists($prl,$related_array)) {	//crmv@72900
					$relationid = $related_array[$prl]['relationId'];
					$pins[$prl] = $related_array[$prl];
					$pins[$prl] = array_merge($pins[$prl],$related_array_info[$relationid]);
				}
			}
			foreach ($tabRelated as $relid => $pinrel) {
				$pinmod = $pinrel['module'];
				if (!isset($pins[$pinmod]) && array_key_exists($pinmod,$related_array)) {	//crmv@72900
					$pins[$pinmod] = $related_array[$pinmod] ?: array();
					$pins[$pinmod] = array_merge($pins[$pinmod],$related_array_info[$relid]);
				}
			}
			//crmv@62415e crmv@128523e
		}
		if (!empty($related_array_info)) {
			foreach($related_array_info as $relationid => $info) {
				$mod = $info['related_module'];
				if ($module == 'Calendar' && in_array($mod,array('','Contacts'))) {	// remove related list of Users and Contacts
					continue;
				}
				$tmp = array(
					'type'=>'other',
					'display'=>'none',
					'module'=>$mod
				);
				//crmv@62415
				if (in_array($relationid,$pinRelationIds) || array_key_exists($relationid, $tabRelated)){
					$tmp['pinned'] = true;
				}
				else{
					$tmp['pinned'] = false;
				}
				//crmv@62415 e
				$tmp = array_merge($tmp,$related_array_info[$relationid]);
				$relations[] = $tmp;
			}
		}
		//crmv@51605
		if (!function_exists('array_sort_by_count')) {
			function array_sort_by_count(&$arr, $col, $col2 = null, $dir = SORT_ASC) {
				$sort_col = array();
				$others = array();
				foreach ($arr as $key=> $row) {
					if (empty($row[$col])) {
						$others[] = $row;
						unset($arr[$key]);
					} else {
						$sort_col[$key] = $row[$col];
					}
				}
				array_multisort($sort_col, $dir, $arr);
				if ($col2) {
					array_sort_by_count($arr, $col2, null, SORT_ASC);
					array_sort_by_count($others, $col2, null, SORT_ASC);
				}
				$arr = array_merge($arr, $others);
			}
		}
		if (!function_exists('array_sort_by_sequence')) {
			function array_sort_by_sequence(&$arr, $col, $dir = SORT_ASC) {
				$sort_col = array();
				foreach ($arr as $key=> $row) {
					$sort_col[$key] = $row[$col];
				}
				array_multisort($sort_col, $dir, $arr);
			}
		}
		if ($tbRelationsOrder == 'num_of_records') {
			array_sort_by_count($relations, 'count', null, SORT_DESC);
			array_sort_by_count($pins, 'count', 'sequence', SORT_DESC); //crmv@62415
		} else {
			array_sort_by_sequence($relations, 'sequence');
			array_sort_by_sequence($pins, 'sequence'); //crmv@62415
		}
		//crmv@51605e
		return array('turbolift'=>$relations,'pin'=>$pins);
	}
	//crmv@43864e crmv@3086me crmv@57221 crmv@104568e crmv@150751e
	
	//crmv@57221
	function getRelatedListButtons($relation, $module, $crmid) {
		global $onlyquery, $onlybutton, $currentModule;
		$onlyquery = true;
		$onlybutton = true;

		$focus = CRMEntity::getInstance($module);
		$method = $relation['name'];
		$return = $focus->$method($crmid, getTabid($module), $relation['related_tabid'], $relation['actions']);
		$custom_button = str_replace('&nbsp;','',$return['CUSTOM_BUTTON']);
		
		$onlyquery = false;
		$onlybutton = false;
		$currentModule = $module;
		
		return $custom_button;
	}
	//crmv@57221e

	//crmv@44609 crmv@128159
	function relate($module1, $record1, $module2, $record2, $relationType=null) {
		if (!is_array($record2)) $record2 = array($record2);
		if (empty($record2)) return false;
		
		if ($this->isModuleRelated($module1, $module2)) {
			$moduleRelations = $this->getRelations($module1, $relationType, $module2);
			if (!empty($moduleRelations)) {
				foreach($moduleRelations as $moduleRelation) {
					$moduleRelationType = $moduleRelation->getType();
					if ($moduleRelationType == ModuleRelation::$TYPE_1TO1) {
						// TODO
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_1TON) {
						foreach ($record2 as $r) {
							$focus = CRMEntity::getInstance($module2);
							$focus->retrieve_entity_info_no_html($r, $module2);
							$focus->mode = 'edit';
							$focus->id = $r;
							$focus->column_fields[$moduleRelation->fieldname] = $record1;
							$focus->save($module2);
						}
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_NTO1) {
						$focus = CRMEntity::getInstance($module1);
						$focus->retrieve_entity_info_no_html($record1, $module1);
						$focus->mode = 'edit';
						$focus->id = $record1;
						$focus->column_fields[$moduleRelation->fieldname] = $record2[0];
						$focus->save($module1);
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_NTON) {
						$focus = CRMEntity::getInstance($module1);
						$focus->save_related_module($module1, $record1, $module2, $record2);
					}
					break;
				}
			}
		}
	}
	//crmv@44609e crmv@128159e

	function unrelate($module1, $record1, $module2, $record2, $relationType=null) {
		if (!is_array($record2)) $record2 = array($record2);
		if (empty($record2)) return false;
		
		if ($this->isModuleRelated($module1, $module2)) {
			$moduleRelations = $this->getRelations($module1, $relationType, $module2);
			if (!empty($moduleRelations)) {
				foreach($moduleRelations as $moduleRelation) {
					$moduleRelationType = $moduleRelation->getType();
					if ($moduleRelationType == ModuleRelation::$TYPE_1TO1) {
						// TODO
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_1TON) {
						foreach ($record2 as $r) {
							$focus = CRMEntity::getInstance($module2);
							$focus->retrieve_entity_info_no_html($r, $module2);
							$focus->mode = 'edit';
							$focus->id = $r;
							$focus->column_fields[$moduleRelation->fieldname] = '';
							$focus->save($module2);
						}
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_NTO1) {
						$focus = CRMEntity::getInstance($module1);
						$focus->retrieve_entity_info_no_html($record1, $module1);
						$focus->mode = 'edit';
						$focus->id = $record1;
						$focus->column_fields[$moduleRelation->fieldname] = '';
						$focus->save($module1);
					} elseif ($moduleRelationType == ModuleRelation::$TYPE_NTON) {
						$focus = CRMEntity::getInstance($module1);
						$focus->delete_related_module($module1, $record1, $module2, $record2);
					}
					break;
				}
			}
		}
	}

}