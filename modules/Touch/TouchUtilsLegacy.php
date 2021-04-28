<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Funzioni di utilità per Touch - Legacy file used only for compatibility
 */

/* crmv@33097 */
/* crmv@33311 */
/* crmv@33545 - filtri */
/* crmv@34559 - preferiti,filtri */

require_once('modules/CustomView/CustomView.php');
require_once('modules/SDK/src/Favorites/Utils.php');
require_once('modules/Popup/Popup.php');

function wsRequest($userid, $operation, $operationInput, $format='json') {
	global $adb;

	$operation = strtolower($operation);
	$sessionId = vtws_getParameter($_REQUEST,"sessionName");

	$sessionManager = new SessionManager();
	$operationManager = new OperationManager($adb,$operation,$format,$sessionManager);

	try {
		if ($userid) {
			$seed_user = new Users();
			$wsuser = $seed_user->retrieveCurrentUserInfoFromFile($userid);
			$wsuser->id = $userid;
		} else {
			$wsuser = null;
		}
		$includes = $operationManager->getOperationIncludes();
		foreach($includes as $ind=>$path){
			require_once($path);
		}
		$rawOutput = $operationManager->runOperation($operationInput,$wsuser);
		return array('success'=>true,'result'=>$rawOutput);

	} catch (Exception $e) {
		return array('success'=>false,'error'=>$e);
	}
}

function touch_ws_login($username, $password) {
	$user = CRMEntity::getInstance('Users');
	$user->column_fields['user_name'] = $username;
	if(!$user->doLogin($password)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDUSERPWD, "Invalid username or password");
	}
	$userId = $user->retrieve_user_id($username);
	$user = $user->retrieveCurrentUserInfoFromFile($userId);
	if($user->status != 'Inactive'){
		//crmv@37463
		global $current_user;
		$current_user = $user;
		if (isPermitted('Touch', 'DetailView') != 'yes') return '';
		//crmv@37463e
		return $user->column_fields['accesskey'];
	}
	throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED, 'Given user is inactive');
}

// restituisce il linkid se il modulo ha il blocco per i commenti, falso altrimenti
function hasCommentsBlock($module) {
	global $adb, $table_prefix, $current_user;

	$query = "select linkid from {$table_prefix}_links where tabid = ? and linktype = ? and linklabel = ?";
	$params = array(getTabid($module), 'DETAILVIEWWIDGET', 'DetailViewBlockCommentWidget');
	$res = $adb->pquery($query, $params);
	if ($res && $adb->num_rows($res) > 0) {
		return $adb->query_result($res, 0, 'linkid');
	}
	return false;
}

function hasPDFMaker($module) {
	global $adb, $table_prefix, $current_user;
	if (vtlib_isModuleActive('PDFMaker') && isPermitted('PDFMaker', 'DetailView') == 'yes') {
		$res = $adb->pquery(
			"select p.templateid
			from {$table_prefix}_pdfmaker p
			left join {$table_prefix}_pdfmaker_userstatus u on u.templateid = p.templateid and u.userid = ?
			where p.module = ? and (u.is_active is null or u.is_active = 1)",
			array($current_user->id, $module)
			);
		return ($res && $adb->num_rows($res) > 0);
	}
	return false;
}

// restituisce la lista dei campi presenti in una related
// TODO: rimuovere dipendenza da recordid
function getRelatedFields($userid, $module, $relmodule, $relationId, $recordid) {
	global $adb, $table_prefix;
	static $instanceCache = array();

	// creo le istanze delle classi
	if (!array_key_exists($module, $instanceCache)) {
		$instanceCache[$module] = CRMEntity::getInstance($module);
		vtlib_setup_modulevars($module, $instanceCache[$module]);
	}
	if (!array_key_exists($relmodule, $instanceCache)) {
		$instanceCache[$relmodule] = CRMEntity::getInstance($relmodule);
		vtlib_setup_modulevars($relmodule, $instanceCache[$relmodule]);
	}
	if (empty($instanceCache[$module]) || empty($instanceCache[$relmodule])) return array();

	/*$relationInfo = getRelatedListInfoById($relationId);
	 $function_name = $relationInfo['functionName'];
	if (empty($function_name)) return array();
	$relatedListData = $instanceCache[$module]->$function_name(1, getTabid($module), $relationInfo['relatedTabId'], $relationInfo['actions']);
	*/

	$LVU = ListViewUtils::getInstance();

	// prendo tutti i campi del modulo

	$relmoduleWs = $relmodule;
	$response = wsRequest($userid,'describe', array('elementType'=> $relmoduleWs));
	$relfields = array();
	if (is_array($response['result']['fields'])) {
		foreach ($response['result']['fields'] as $k => $f) {
			$f['editable'] = false; // forzo in sola lettura
			$relfields[$f['name']] = $f;
		}
	}

	// prendo i campi related
	$relatedListData = $LVU->getListViewHeader($instanceCache[$relmodule],$relmodule,'',$instanceCache[$relmodule]->default_sort_order,$instanceCache[$relmodule]->default_order_by,$recordid,'',$module,true,true);

	// unisco le informazioni
	$outfields = array();
	foreach ($relatedListData as $k=>$v) {
		$fname = $v['fieldname'];
		if (array_key_exists($fname, $relfields)) $outfields[] = $relfields[$fname];
	}

	return $outfields;
}

// restituisce l'array con la lista dei moduli
function touchModulesList() {
	global $touchInst;
	global $table_prefix, $current_user;

	if (!empty($current_user->column_fields['default_language'])) {
		$default_language = $current_language = $current_user->column_fields['default_language'];
	}

	$resultdata = wsRequest($current_user->id,'listtypes', array('fieldTypeList'=>'' ));

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

	$recordReturn = array();
	$id = 1;
	$simpleModuleList = array();
	foreach ($recordList as $name) {
		if (!isModuleInstalled($name['name']) || in_array($name['name'], $touchInst->excluded_modules) || in_array($name['name'], $touchInst->excluded_modules_list)) continue;
		if (preg_match("/^ModLight/", $name['name'])) continue; // crmv@170741
		if ($name['name'] == 'Events') continue;

		$tabid = getTabid($name['name']);
		if (empty($tabid)) continue;

		$isentity = getSingleFieldValue($table_prefix.'_tab', 'isentitytype', 'tabid', $tabid);
		if (!$isentity) continue;

		// single string
		$singlelabel = getTranslatedString('SINGLE_'.$name['name'], 'APP_STRINGS');
		if ($singlelabel == 'SINGLE_'.$name['name']) $singlelabel = getTranslatedString('SINGLE_'.$name['name'], $name['name']);

		// filtri
		$filters = touchFilterList($name['name']);

		// cartelle
		$folders = touchFoldersList($name['name']);

		// altre impostazioni di default
		$defaults = touchGetModuleDefaults($name['name']);

		// moduli per popup
		$linkModules = $createModules = array();
		if ($popup) {
			$createModules = array_values($popup->getCreateModules($name['name'], '', 'create'));
			$linkModules = array_values($popup->getLinkModules($name['name'], '', 'link'));
			// and add events
			array_unshift($createModules, 'Events', 'Calendar');
		}

		$modinst = CRMEntity::getInstance($name['name']);

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

		$simpleModuleList[] = $name['name'];
		array_push($recordReturn,array(
			'id'=>$id++, // i records sono in ordine di id
			'text'=>$name['label'], // testo da visualizzare
			'label'=>$name['label'], // testo da visualizzare (che differenza c'è da text??)
			'single_label' => $singlelabel,
			'view'=>$name['name'],  // nomefile della view (es Accounts)
			'leaf'=>'true', // lancia il js con nomefile in view (es Accounts.js)
			'tabid'=>$tabid,
			'idfield'=> $modinst->table_index,
			'orderfield'=> $modinst->default_order_by, // TODO: verifica se vuoto
			'access' => $sharingAccess,
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
			'hide_tabs' => $touchInst->hide_module_pages[$name['name']],
		));
	}

	// add calendar (fake module called 'Events')
	array_push($recordReturn,array(
		'id'=>$id++, // i records sono in ordine di id
		'text'=>getTranslatedString('Calendar'), // testo da visualizzare
		'label'=>getTranslatedString('Calendar'), // testo da visualizzare (che differenza c'è da text??)
		'single_label' => getTranslatedString('Event'),
		'view'=>'Events',  // nomefile della view (es Accounts)
		'leaf'=>'true', // lancia il js con nomefile in view (es Accounts.js)
		'tabid'=>getTabid('Events'),
		'idfield'=> '',
		'orderfield'=> '',
		// permessi
		'perm_create' => (strcasecmp(isPermitted('Events','EditView'),'yes') === 0),
		'perm_write' => (strcasecmp(isPermitted('Events','EditView'),'yes') === 0),
		'perm_delete' => (strcasecmp(isPermitted('Events','Delete'),'yes') === 0),
		// filtri
		'filters' => '',
		'folders' => array(),
		'defaults' => array(),
		'hide_tabs' => $touchInst->hide_module_pages['Events'],
	));

	// add more fake modules (recents, favourites, search)
	array_push($recordReturn,array(
		'id'=>$id++,
		'text'=>getTranslatedString('LBL_RECENTS'),
		'label'=>getTranslatedString('LBL_RECENTS'),
		'view'=>'Recents',
	));

	array_push($recordReturn,array(
		'id'=>$id++,
		'text'=>getTranslatedString('LBL_FAVORITES'),
		'label'=>getTranslatedString('LBL_FAVORITES'),
		'view'=>'Favourites',
	));

	array_push($recordReturn,array(
		'id'=>$id++,
		'text'=>getTranslatedString('LBL_SEARCH'),
		'label'=>getTranslatedString('LBL_SEARCH'),
		'view'=>'Search',
	));


	// add special values
	$specModules = array_count_values($touchInst->modules_list_order);
	if ($specModules['PageBreak'] > 0) {
		for ($i=0; $i<$specModules['PageBreak']; ++$i) {
			$recordReturn[] = array(
				'id' => $id++,
				'view' => 'PageBreak',
			);
		}
	}
	$addAreas = false;
	if ($specModules['Areas'] > 0) {
		for ($i=0; $i<$specModules['Areas']; ++$i) {
			$recordReturn[] = array(
				'id' => $id++,
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
		include_once('modules/Touch/vtws/classes/GetAreas.class.php');
		if (class_exists('TouchGetAreas')) {
			$wsclass = new TouchGetAreas();
			$req = array();
			$areas = $wsclass->process($req);
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
						'id' => $id++,
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

	/*print_r($sortedMods);
	die();*/

	return $sortedMods;
}

function touchGetModuleDefaults($module) {
	global $adb, $table_prefix, $current_user;
	$ret = array();

	$tabid = getTabid($module);

	// to get $current_user_profiles
	require('user_privileges/requireUserPrivileges.php');
	require_once('modules/CustomView/CustomView.php');
	if (count($current_user_profiles) > 0) {
		$res = $adb->pquery("select * from {$table_prefix}_profile2mobile where profileid = ? and tabid = ?", array($current_user_profiles[0], $tabid));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			if ($row['cvid'] > 0) {
				$cv = CRMEntity::getInstance('CustomView');
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
		$cv = CRMEntity::getInstance('CustomView');
		$viewid = $cv->getViewIdByName('All', $module);
		$ret['cvid'] = $viewid;
		$ret['cvname'] = getTranslatedString('LBL_ALL');
	}
	return $ret;
}

// ritorna i filtri (id, nome) per il modulo
function touchFilterList($module) {
	global $adb, $table_prefix;
	$cv = CRMEntity::getInstance('CustomView', $module); // crmv@115329
	$query = $cv->getCustomViewQuery($module, $params);

	$ret = array();
	$res = $adb->pquery($query, $params);
	if ($res && $adb->num_rows($res) > 0) {
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			if ($module == 'Calendar' && $row['viewname'] == 'Events') continue;
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

function touchFoldersList($module) {
	$ret = array();

	$focus = CRMEntity::getInstance($module);

	if (method_exists($focus, 'getFolderList')) {
		$flist = $focus->getFolderList();
	} else {
		$flist = getEntityFoldersByName(null, $module);
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

// ritorna il nome della colonna per un certo campo
function getFieldColumn($module, $fieldname) {
	global $adb, $table_prefix;

	$ret = false;
	$q = "
	select columnname
	from {$table_prefix}_field f
	inner join {$table_prefix}_tab t on t.tabid = f.tabid
	where t.name = ? and f.fieldname = ?";

	$res = $adb->pquery($q, array($module, $fieldname));
	if ($res && $adb->num_rows($res) > 0) {
		$ret = $adb->query_result_no_html($res, 0, 'columnname');
	}
	return $ret;
}

function getFieldNameFromColumn($module, $fieldcol) {
	global $adb, $table_prefix;

	$ret = false;
	$q = "
	select fieldname
	from {$table_prefix}_field f
	inner join {$table_prefix}_tab t on t.tabid = f.tabid
	where t.name = ? and f.columnname = ?";

	$res = $adb->pquery($q, array($module, $fieldcol));
	if ($res && $adb->num_rows($res) > 0) {
		$ret = $adb->query_result_no_html($res, 0, 'fieldname');
	}
	return $ret;
}

function touchFindEmailRecipients($module, $record) {
	global $adb, $table_prefix, $current_user;

	$reclist = array();
	$modInst = CRMEntity::getInstance($module);
	// email fields
	$res = $adb->pquery("select tablename,columnname,fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array(getTabid($module), 13));
	if ($res) {
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$idx = $modInst->tab_name_index[$row['tablename']];
			if ($idx) {
				$emvalue = getSingleFieldValue($row['tablename'], $row['columnname'], $idx, $record);
				if (!empty($emvalue)) {
					// split it
					$ename = getEntityName($module, $record);
					$ename = $ename[$record];
					$emlist = preg_split('/[,; ]/', $emvalue, -1, PREG_SPLIT_NO_EMPTY);
					foreach ($emlist as $email) {
						$reclist[] = array(
							'email' => $email,
							'entityname' => $ename,
							'module' => $module,
							'crmid' => intval($record),
						);
					}
				}
			}
		}
	}
	return $reclist;
}

// details for the pdf maker
// list of templates, list of available email addresses
function touchPDFMakerDetails($module, $record) {
	global $adb, $table_prefix, $current_user;

	$ret = array();
	$res = $adb->pquery(
		"select p.templateid, p.filename as templatename, p.module
		from {$table_prefix}_pdfmaker p
		left join {$table_prefix}_pdfmaker_userstatus u on u.templateid = p.templateid and u.userid = ?
		where p.module = ? and (u.is_active is null or u.is_active = 1)
		order by p.filename",
		array($current_user->id, $module)
		);
	if ($res) {
		// templates list
		$pdflist = array();
		while ($row = $adb->FetchByAssoc($res, -1, false)) {
			$pdflist[] = $row;
		}
		$ret['templates'] = $pdflist;
		$ret['actions'] = array('sendemail');
		if (isPermitted('Documents', 'EditView') == 'yes') $ret['actions'][] = 'savedoc';
	}

	return $ret;
}

function touchGetRecord($module, $recordid, $prodblock = 0) {
	global $touchInst, $touchUtils;
	global $adb, $table_prefix, $current_user;

	// crmv@37794
	// read permission check
	$permModule = $module;
	if ($module == 'Events') $permModule = 'Calendar'; // trick to allow events i am invited to
	if (isPermitted($permModule, 'DetailView', $recordid) != 'yes') {
		$record['vtecrm_permissions'] = array(
			'perm_read' => false,
			'perm_write' => (isPermitted($permModule, 'EditView', $recordid) == 'yes'),
			'perm_delete' => (isPermitted($permModule, 'Delete', $recordid) == 'yes'),
		);
		return $record;
	}
	// crmv@37794e

	$focus = CRMEntity::getInstance($module);
	$focus->retrieve_entity_info_no_html($recordid, $module);
	$focus->id = $recordid;

	$record = $focus->column_fields;

	foreach ($record as $fldname=>$fldvalue) {
		$record[$fldname] = $touchInst->field2Touch($module, $fldname, $fldvalue, false, $focus);
	}

	if (empty($record['crmid'])) {
		$record['crmid'] = ($record['record_id'] ? $record['record_id'] : $recordid);
	}

	if (!empty($record['modifiedtime'])) {
		$record['timestamp'] = strtotime($record['modifiedtime']);
	}

	if (empty($record['entityname'])) {
		$ename = getEntityName($permModule, array($recordid));
		$record['entityname'] = $ename[$recordid];
	}

	// aggiungo i campi per il blocco prodotti
	if ($prodblock == 1 && $module == 'Products') {
		$record['crmid'] = $recordid;
		$record['entityType'] = $module;
		$record['entityname'] = $record['productname'];
		$record['comment'] = '';
		$record['productDescription'] = $record['description'];
		$record['productName'] = $record['productname'];
		$record['hdnProductId'] = array('crmid' => $recordid, 'display'=>$record['productname']);
		$record['hdnProductcode'] = $record['productcode'];

		// calcolo id della riga
		$res = $adb->query("select max(lineitem_id) as maxid from {$table_prefix}_inventoryproductrel");
		if ($res && $adb->num_rows($res) > 0) {
			$lineitemid = intval($adb->query_result($res, 0, 'maxid'))+1;
		} else {
			$lineitemid = 1;
		}
		$record['lineItemId'] = $lineitemid;

		$record['qty'] = 1;
		$record['qtyInStock'] = $record['qtyinstock'];
		$record['discountTotal'] = 0;
		$record['discount_amount'] = 0;
		$record['discount_percent'] = 0;
		$record['discount_type'] = 0;
		$record['taxTotal'] = 0;
		$record['unitPrice'] = $record['unit_price'];
		$record['totalAfterdiscount'] = $record['unit_price'];
		$record['lineTotal'] = $record['unit_price'];
		$record['listPrice'] = $record['unit_price'];
		$record['netPrice'] = $record['unit_price'];
		$record['productTotal'] = $record['unit_price'];
	}

	// mail attachment
	if ($module == 'Messages') {
		$attach_info = $focus->getAttachments(null);
		if ($attach_info) $record['attachments'] = $attach_info;
	}

	// add some extra fields
	// TODO: remove this, use the same trick for notifications
	if ($module == 'ModComments') {

		$record['crmid'] = $record['record_id'];
		$record['smcreatorid'] = getSingleFieldValue($table_prefix.'_crmentity', 'smcreatorid', 'crmid', $record['record_id']);
		$record['related_to'] = $record['related_to']['crmid'];
		$record['parent_comments'] = $record['parent_comments']['crmid'];

		//$record['commentcontent'] = str_replace('&amp;', '&', $record['commentcontent']);

		$widgetInstance = $focus->getWidget('DetailViewBlockCommentWidget');
		$model = new ModComments_CommentsModel($record);
		$commentData = $model->content_no_html();

		unset($commentData['timestamp']);
		$record = array_merge($record, $commentData);

		$hasunseen = false;
		$forced = false;
		$replies = $commentData['replies'];

		if (is_array($replies) && count($replies) > 0) {
			foreach ($replies as $rk=>$rv) {
				$replies[$rk]['timestamp'] = strtotime($rv['modifiedtime']);
				// fix per &
				$replies[$rk]['commentcontent'] = str_replace('&amp;', '&', $rv['commentcontent']);
				$hasunseen |= $replies[$rk]['unseen'];
				$forced |= $replies[$rk]['forced'];
			}
			$lastmessage = $replies[count($replies)-1];
		} else {
			$lastmessage = $v;
			$replies = array();
		}

		unset($record['replies']);
		unset($record['crmid']);
		array_unshift($replies, $record);

		$lastmessage['unseen'] |= $commentData['unseen'] | $hasunseen;
		$lastmessage['forced'] |= $commentData['forced'] | $forced;
		$lastmessage['entityname'] = getEntityName('ModComments', $lastmessage['crmid'], true);
		$lastmessage['comments'] = $replies;

		// return the last message as the parent
		$record = $lastmessage;
	}

	// pdfmaker
	if ($touchUtils->hasPDFMaker($module)) {
		$pddet = $touchUtils->getPDFMakerDetails($module, $recordid);
		if (empty($pddet['templates'])) {
			$pddet['actions'] = array();
			// no templates -> no actions available
		}
		// clear everything if there are no actions available
		if (empty($pddet['actions'])) $pddet = array();
		$record['vtecrm_pdfmaker'] = $pddet;
	}

	// aggiungo campo fittizio per preferiti
	if (!isset($record['vtecrm_favourite'])) {
		$favimg = getFavorite($recordid);
		$record['vtecrm_favourite'] = (preg_match('/_on/', $favimg) ? true : false);
	}

	// aggiungo campi fittizi per permessi
	if (!isset($record['vtecrm_permissions'])) {
		$record['vtecrm_permissions'] = array(
			'perm_write' => (isPermitted($module, 'EditView', $recordid) == 'yes'),
			'perm_delete' => (isPermitted($module, 'Delete', $recordid) == 'yes'),
		);
	}

	return $record;
}
