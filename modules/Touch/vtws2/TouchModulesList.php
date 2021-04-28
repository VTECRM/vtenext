<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchModulesList extends TouchWSClass {

	protected $cacheLife = 86400;	// 1 day
	
	public function clearCache() {
		global $touchCache;
		$touchCache->delete('modules_list');
		$touchCache->delete('modules_list_names');
	}
	
	function process(&$request) {
		global $touchInst, $touchUtils, $touchCache;
		global $table_prefix, $current_user, $current_language;

		$onlyNames = ($request['onlynames'] ? true : false);
		
		// check cache
		if ($onlyNames) {
			$cachedNames = $touchCache->get('modules_list_names');
			if ($cachedNames) return $cachedNames;
		} else {
			$cachedList = $touchCache->get('modules_list');
			if ($cachedList) return $cachedList;
		}
		

		if (!empty($current_user->column_fields['default_language'])) {
			$default_language = $current_language = $current_user->column_fields['default_language'];
		}

		$resultdata = $touchUtils->wsRequest($current_user->id,'listtypes', array('fieldTypeList'=>'' ));

		$modulenames = $resultdata['result']['types'];
		$modulelabel = $resultdata['result']['information'];
		$recordList = Array();

		foreach($modulenames as $modulename) {
			$recordList[$modulename] = Array (
				'name' => $modulename,
				'label' => $resultdata['result']['information'][$modulename]['label']
			);
		}
		$recordList['Calendar'] = Array (
			'name' => 'Calendar',	//crmv@017
			'label' => getTranslatedString('Tasks', 'APP_STRINGS'),
		);

		$popup = Popup::getInstance();

		$sharingTypes = array(
			0 => 'public_readonly',
			1 => 'public_readwrite',
			2 => 'public_readwritedelete',
			3 => 'private',
		);

		$addTicketComments = false;

		$maxTabid = 0;
		$recordReturn = array();
		$simpleModuleList = array();
		foreach ($recordList as $name) {
			if (!isModuleInstalled($name['name']) || in_array($name['name'], $touchInst->excluded_modules)) continue;
			if (preg_match("/^ModLight/", $name['name'])) continue; // crmv@170741
			if ($name['name'] == 'Events') continue; // added later

			$tabid = getTabid($name['name']);
			if (empty($tabid)) continue;

			if ($tabid > $maxTabid) $maxTabid = $tabid;

			$isentity = getSingleFieldValue($table_prefix.'_tab', 'isentitytype', 'tabid', $tabid);
			if (!$isentity) continue;

			$simpleModuleList[] = $name['name'];
			if ($onlyNames) continue;

			$listHidden = in_array($name['name'], $touchInst->excluded_modules_list);

			if ($name['name'] == 'HelpDesk') $addTicketComments = true;

			// single string
			$singlelabel = getTranslatedString('SINGLE_'.$name['name'], 'APP_STRINGS');
			if ($singlelabel == 'SINGLE_'.$name['name']) $singlelabel = getTranslatedString('SINGLE_'.$name['name'], $name['name']);

			// filtri
			$filters = $this->getFilterList($name['name']);

			// cartelle
			$folders = $this->getFoldersList($name['name']);

			// altre impostazioni di default
			$defaults = $this->getModuleDefaults($name['name']);

			// entityname fields
			$entityfields = $this->getEntityFields($name['name']);

			// moduli per popup
			$linkModules = $createModules = array();
			if ($popup) { // crmv@93148
				$createModules = array_values($popup->getCreateModules($name['name'], '', 'create'));
				$linkModules = array_values($popup->getLinkModules($name['name'], '', 'link'));
				// and add events
				array_unshift($createModules, 'Events', 'Calendar');
			}

			$modinst = $touchUtils->getModuleInstance($name['name']);

			// permissions
			$permCreate = $touchInst->force_module_premissions[$name['name']]['perm_create'];
			if (!is_bool($permCreate)) $permCreate = (strcasecmp(isPermitted($name['name'],'EditView'),'yes') === 0);
			$permWrite = $touchInst->force_module_premissions[$name['name']]['perm_write'];
			if (!is_bool($permWrite)) $permWrite = (strcasecmp(isPermitted($name['name'],'EditView'),'yes') === 0);
			$permDelete = $touchInst->force_module_premissions[$name['name']]['perm_delete'];
			if (!is_bool($permDelete)) $permDelete = (strcasecmp(isPermitted($name['name'],'Delete'),'yes') === 0);

			$sharingAccess = getSingleFieldValue($table_prefix.'_def_org_share', 'permission', 'tabid', $tabid);
			if ($sharingAccess != '') {
				$sharingAccess = $sharingTypes[$sharingAccess];
			}

			array_push($recordReturn,array(
				'text'=>$name['label'], // testo da visualizzare
				'label'=>$name['label'], // testo da visualizzare (che differenza c'è da text??)
				'single_label' => $singlelabel,
				'view'=>$name['name'],  // nomefile della view (es Accounts)
				'tabid'=>$tabid,
				'idfield'=> $modinst->table_index,
				'orderfield'=> $modinst->default_order_by, // TODO: verifica se vuoto
				'access' => $sharingAccess,
				'is_inventory'=> (isInventoryModule($name['name']) ? 1 : 0),
				'is_product'=> (isProductModule($name['name']) ? 1 : 0),
				// permessi
				'perm_create' => $permCreate,
				'perm_write' => $permWrite,
				'perm_delete' => $permDelete,
				// filtri
				'filters' => $filters,
				'folders' => $folders,
				'defaults' => $defaults,
				// list of modules available for the new popup (link/create)
				'mods_link' => $linkModules,
				'mods_create' => $createModules,
				'namefields' => $entityfields,
				'hide_tabs' => $touchInst->hide_module_pages[$name['name']],
				'list_hidden' => $listHidden,
			));
		}

		// add calendar (fake module called 'Events')
		$simpleModuleList[] = 'Events';
		array_push($recordReturn,array(
			'text'=>getTranslatedString('Calendar'), // testo da visualizzare
			'label'=>getTranslatedString('Calendar'), // testo da visualizzare (che differenza c'è da text??)
			'single_label' => getTranslatedString('Event'),
			'view'=>'Events',  // nomefile della view (es Accounts)
			'tabid'=>getTabid('Events'),
			'idfield'=> '',
			'orderfield'=> '',
			'is_inventory'=> 0,
			'is_product'=> 0,
			// permessi
			'perm_create' => (strcasecmp(isPermitted('Events','EditView'),'yes') === 0),
			'perm_write' => (strcasecmp(isPermitted('Events','EditView'),'yes') === 0),
			'perm_delete' => (strcasecmp(isPermitted('Events','Delete'),'yes') === 0),
			// filtri
			'filters' => $this->getFilterList('Events'),
			'folders' => array(),
			'defaults' => array(),
			'namefields' => $this->getEntityFields('Calendar'),
			'hide_tabs' => $touchInst->hide_module_pages['Events'],
			'list_hidden' => in_array('Events', $touchInst->excluded_modules_list),
		));
		
		// crmv@164122
		// add notifications, which is not an entity module anymore
		$module = 'ModNotifications';
		if (vtlib_isModuleActive($module) && !in_array($module, $touchInst->excluded_modules)) {
			$simpleModuleList[] = $module;
			array_push($recordReturn,array(
				'text'=>getTranslatedString($module), // testo da visualizzare
				'label'=>getTranslatedString($module), // testo da visualizzare (che differenza c'è da text??)
				'single_label' => getTranslatedString('SINGLE_ModNotifications'),
				'view'=>$module,  // nomefile della view (es Accounts)
				'tabid'=>getTabid($module),
				'idfield'=> '',
				'orderfield'=> '',
				'is_inventory'=> 0,
				'is_product'=> 0,
				// permessi
				'perm_create' => false,
				'perm_write' => true,
				'perm_delete' => false,
				// filtri
				'filters' => array(),
				'folders' => array(),
				'defaults' => array(),
				'namefields' => 'subject',
				'hide_tabs' => $touchInst->hide_module_pages[$module],
				'list_hidden' => in_array($module, $touchInst->excluded_modules_list),
			));
		}
		// crmv@164122e

		$touchCache->set('modules_list_names', $simpleModuleList, $this->cacheLife);
		
		if ($onlyNames) return $simpleModuleList;

		// add more fake modules (recents, favourites, search)
		array_push($recordReturn,array(
			'text'=>getTranslatedString('LBL_RECENTS'),
			'label'=>getTranslatedString('LBL_RECENTS'),
			'view'=>'Recents',
		));

		array_push($recordReturn,array(
			'text'=>getTranslatedString('LBL_FAVORITES'),
			'label'=>getTranslatedString('LBL_FAVORITES'),
			'view'=>'Favourites',
		));

		array_push($recordReturn,array(
			'text'=>getTranslatedString('LBL_SEARCH'),
			'label'=>getTranslatedString('LBL_SEARCH'),
			'view'=>'Search',
		));
		
		// crmv@124979
		array_push($recordReturn,array(
			'text' => getTranslatedString('LBL_TRACK_MANAGER'), 
			'label' => getTranslatedString('LBL_TRACK_MANAGER'), 
			'view' => 'Tracking'
		));
		// crmv@124979e

		// add special values
		$specModules = array_count_values($touchInst->modules_list_order);
		if ($specModules['PageBreak'] > 0) {
			for ($i=0; $i<$specModules['PageBreak']; ++$i) {
				$recordReturn[] = array(
					'view' => 'PageBreak',
				);
			}
		}
		$addAreas = false;
		if ($specModules['Areas'] > 0) {
			for ($i=0; $i<$specModules['Areas']; ++$i) {
				$recordReturn[] = array(
					'view' => 'Areas',
				);
			}
			$addAreas = true;
		}

		// generate keys for sorting
		$sortedMods = array();
		foreach ($recordReturn as $mod) {
			$sortedMods[$mod['view']][] = $mod;
		}

		$unsortedMods = $sortedMods;
		$sortedMods = array();
		foreach ($touchInst->modules_list_order as $omod) {
			if (array_key_exists($omod, $unsortedMods) && count($unsortedMods[$omod]) > 0) {
				$sortedMods[] = array_shift($unsortedMods[$omod]);
				if (count($unsortedMods[$omod]) == 0) {
					unset($unsortedMods[$omod]);
				}
			}
		}

		// sort the remaining ones
		uasort($unsortedMods, function($m1, $m2) {
			return strcmp($m1[0]["label"], $m2[0]["label"]);
		});

		$unsortedMods = array_map(function($e) {
			return $e[0];
		}, $unsortedMods);

		// merge them!
		$sortedMods = array_merge($sortedMods, $unsortedMods);
		$sortedMods = array_values(array_filter($sortedMods));

		// now replace "Areas" with the right modules
		if ($addAreas) {
			$req = array();
			$areas = $this->subcall('GetAreas', $req);

			$areas = $areas['areas'];
			$arealist = array();
			$removeids = array();
			foreach ($areas as $area) {
				$modlist = $area['modules'];
				$addedmods = false;
				foreach ($modlist as $areamod)  {
					$modname = $areamod['module'];
					// check if available
					if (in_array($modname, $simpleModuleList)) {
						// search the module
						foreach ($sortedMods as $k=>$smod) {
							if ($smod['view'] == $modname) {
								// add to the area and remove from the main list
								$smod['areaid'] = $area['areaid'];
								$arealist[] = $smod;
								$removeids[] = $k;
								$addedmods = true;
								break;
							}
						}
					}
				}
				// add pagebreak after area
				if ($addedmods) {
					$arealist[] = array(
						'view' => 'PageBreak',
					);
				}
			}
			// remove modules already in areas
			$removeids = array_unique($removeids);
			sort($removeids);
			for ($j=count($removeids)-1; $j>=0; --$j) {
				unset($sortedMods[$removeids[$j]]);
			}
			$sortedMods = array_values($sortedMods);

			// now replace the areas fake module (only 1 areas allowed)
			foreach ($sortedMods as $k=>$smod) {
				if ($smod['view'] == 'Areas') {
					array_splice($sortedMods, $k, 1, $arealist);
					$sortedMods = array_values($sortedMods);
					break;
				}
			}
		}

		// remove breaks from begin/end
		for ($i=0; $i<count($sortedMods); ++$i) {
			if ($sortedMods[$i]['view'] != 'PageBreak') break;
		}
		for ($j=count($sortedMods)-1; $j>=0; --$j) {
			if ($sortedMods[$j]['view'] != 'PageBreak') break;
		}
		if ($i > 0 || $j < (count($sortedMods)-1)) {
			$sortedMods = array_slice($sortedMods, $i, $j+1);
		}

		// remove adjacent breaks
		// not needed

		// add sequence and tabid for special modules
		$seq = 1;
		$tabid = 1000;
		foreach ($sortedMods as &$m) {
			if (in_array($m['view'], array('PageBreak', 'Search', 'Settings', 'Favourites', 'Recents', 'Tracking'))) { // crmv@124979
				$m['tabid'] = $tabid++;
			}
			$m['sequence'] = $seq++;
		}

		$touchCache->set('modules_list', $sortedMods, $this->cacheLife);
		
		return $sortedMods;
	}

	public function getModuleDefaults($module) {
		global $adb, $table_prefix, $current_user, $touchUtils;
		$ret = array();

		$tabid = getTabid($module);

		// to get $current_user_profiles
		require('user_privileges/requireUserPrivileges.php');
		require_once('modules/CustomView/CustomView.php');
		if (is_array($current_user_profiles) && count($current_user_profiles) > 0) { // crmv@198545
			$res = $adb->pquery("select * from {$table_prefix}_profile2mobile where profileid = ? and tabid = ?", array($current_user_profiles[0], $tabid));
			if ($res && $adb->num_rows($res) > 0) {
				$row = $adb->FetchByAssoc($res, -1, false);
				if ($row['cvid'] > 0) {
					$cv = $touchUtils->getModuleInstance('CustomView');
					$cvid = $row['cvid'];
					$cvinfo = $cv->getCustomViewByCvid($cvid);
					$cvname = $cvinfo['viewname'];
				}
				$extrafields = array_map('trim', array_filter(explode(',',$row['extrafields'])));
				foreach ($extrafields as $k=>$f) {
					$res2 = $adb->pquery("select fieldlabel from {$table_prefix}_field where tabid = ? and fieldname = ?", array($tabid, $f));
					$fieldlabel = $adb->query_result_no_html($res2, 0, 'fieldlabel');
					if (empty($fieldlabel)) continue;
					$extrafields[$k] = array(
						'name' => $f,
						'label' => getTranslatedString($fieldlabel, $module),
					);
				}
				$ret = array(
					'cvid' => $cvid,
					'cvname' => $cvname,
					'sortfield' => $row['sortfield'],
					'sortorder' => $row['sortorder'],
					// todo con nomi
					'extrafields' => $extrafields,
					'mobiletab' => $row['mobiletab'],
				);
			}
		}
		// for notes, select the "all" filter by default
		if ($module == 'MyNotes' && empty($ret['cvid'])) {
			$cv = $touchUtils->getModuleInstance('CustomView');
			$viewid = $cv->getViewIdByName('All', $module);
			$ret['cvid'] = $viewid;
			$ret['cvname'] = getTranslatedString('LBL_ALL');
			// crmv@70992
			$ret['sortfield'] = 'timestamp';
			$ret['sortorder'] = 'DESC';
			// crmv@70992e
			// crmv@107199
			if (empty($ret['extrafields'])) {
				$ret['extrafields'] = array(
					array(
						'name' => 'modifiedtime',
						'label' =>getTranslatedString('Modified Time', $module)
					)
				);
			}
			// crmv@107199
		}
		
		// crmv@188277
		if ($module == 'Processes' && empty($ret['cvid'])) {
			$cv = $touchUtils->getModuleInstance('CustomView');
			$viewid = $cv->getViewIdByName('Pending', $module, $current_user->id);
			$ret['cvid'] = $viewid;
			$ret['cvname'] = 'Pending';
			$ret['sortfield'] = 'timestamp';
			$ret['sortorder'] = 'ASC';
			if (empty($ret['extrafields'])) {
				$ret['extrafields'] = array(
					array(
						'name' => 'related_to',
						'label' =>getTranslatedString('Related To', $module)
					)
				);
			}
		}
		// crmv@188277e
		
		$ret['statusfield'] = $this->getEntityStatusFields($module); // crmv@174424
		
		return $ret;
	}

	// ritorna i filtri (id, nome) per il modulo
	// crmv@70992
	public function getFilterList($module) {
		global $touchUtils;
		global $adb, $table_prefix;
		
		// skip non entity modules
		if (in_array($module, array('ModNotifications'))) return array();
		
		$realModule = $module;
		if ($realModule == 'Events') $realModule = 'Calendar';
		
		$modinst = $touchUtils->getModuleInstance($module);
		
		$cv = CRMEntity::getInstance('CustomView', $realModule); // crmv@115329
		$query = $cv->getCustomViewQuery($realModule, $params);

		$query = preg_replace('/where /i', "where {$table_prefix}_customview.setmobile = 1 AND ", $query);

		$ret = array();
		$res = $adb->pquery($query, $params);
		if ($res && $adb->num_rows($res) > 0) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				if ($module == 'Calendar' && $row['viewname'] == 'Events') continue;
				if ($module == 'Events' && $row['viewname'] == 'All') continue;
				$isall = false;
				if ($row['viewname'] == 'All') {
					$row['viewname'] = getTranslatedString('COMBO_ALL', 'APP_STRINGS');
					$isall = true;
				}
				//TODO: campi disponibili
				//$cvinfo = $cv->getColumnsListByCvid($row['cvid']);
				// campo ordinamento
				$sortfield = '';
				$sortorder = 'ASC';
				$cvord = $cv->getOrderByFilterByCvid($row['cvid']);
				if (is_array($cvord) && is_array($cvord[0])) {
					$sortfield = explode(':', $cvord[0]['columnname']);
					$sortfield = $sortfield[2];
					$sortorder = $cvord[0]['ordertype'];
				}
				if (empty($sortfield) && $isall && $modinst->default_order_by) {
					$sortfield = $modinst->default_order_by;
					$sortorder = $modinst->default_sort_order ?: 'ASC';
				}
				if ($sortfield == 'modifiedtime') $sortfield = 'timestamp';
				$ret[] = array(
					'cvid' => $row['cvid'],
					'viewname' => $row['viewname'],
					'all' => $isall,
					'sortfield' => $sortfield,
					'sortorder' => $sortorder,
				);
			}
		}

		return $ret;
	}
	// crmv@70992e

	public function getFoldersList($module) {
		global $touchUtils;
		
		$ret = array();
		
		$realModule = $module;
		if ($realModule == 'Events') $realModule = 'Calendar';

		$focus = $touchUtils->getModuleInstance($realModule);

		if (method_exists($focus, 'getFolderList')) {
			$flist = $focus->getFolderList();
		} else {
			$flist = getEntityFoldersByName(null, $realModule);
		}

		foreach ($flist as $finfo) {
			$ret[] = array(
				'folderid' => $finfo['folderid'],
				'foldername' => $finfo['foldername'],
				'description' => $finfo['description'],
			);
		}

		return $ret;
	}

	public function getEntityFields($module) {
		global $adb, $table_prefix, $current_user;

		if ($module == 'Events') $module = 'Calendar';

		if ($current_user && $current_user->id > 0) {

			// get profile (only the first one)
			require('user_privileges/requireUserPrivileges.php');
			$userProfile = $current_user_profiles[0];

			// override default field with the profile one
			$query =
				"SELECT COALESCE(p2en.fieldname, en.fieldname) as fieldname FROM {$table_prefix}_entityname en
				LEFT JOIN {$table_prefix}_profile2entityname p2en ON p2en.profileid = ? AND p2en.tabid = en.tabid
				WHERE en.modulename = ?";
			$params = array($userProfile, $module);
		} else {
			$query = "SELECT fieldname FROM {$table_prefix}_entityname WHERE modulename = ?";
			$params = array($module);
		}
		$result = $adb->pquery($query, $params);
		$fieldsname = $adb->query_result_no_html($result,0,'fieldname');

		// split and clean up
		$fieldsname = array_filter(array_map('trim', explode(',', $fieldsname)));

		return $fieldsname;
	}
	
	// crmv@174424
	public function getEntityStatusFields($module) {
		$entityStatusField = array();
		
		$ECU = EntityColorUtils::getInstance();
		if (in_array($module, $ECU->getUnsupportedModules())) {
			return $entityStatusField;
		}
		
		$activeStatusField = $ECU->getUsedStatusField($module);
		if ($activeStatusField === null) {
			return $entityStatusField;
		}
		
		$statusFields = $ECU->getStatusFields($module);
		
		if (is_array($statusFields)) {
			foreach ($statusFields as $values) {
				if ($values['fieldname'] === $activeStatusField) {
					$entityStatusField['fieldname'] = $values['fieldname'];

					$statusValues = array();
					foreach ($values['values'] as $v) {
						$statusValues[] = array(
							'id' => $v['id'], 
							'value' => $v['value'],
							'trans_value' => getTranslatedString($v['value'], $module),
							'color' => $v['color'],
						);
					}

					$entityStatusField['values'] = $statusValues;
					break;
				}
			}
		}
		
		return $entityStatusField;
	}
	// crmv@174424e

}
