<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//require_once('include/database/PearDatabase.php');
// Assunzioni: in database non ci sono percorsi ciclici
/* crmv@131239 */

/*
 * Gets an array of valid selections of other boxes in the form
 * array( 1=>array(picklist1, array(selections1), array(translations1)), 2=>... )
 */
function linkedListGetChanges($name, $sel, $mod, $fieldinfo=array()) { // crmv@30528 crmv@131239
	global $adb;
	global $current_user;
	global $currentModule;
	global $table_prefix;
	$roleid = $current_user->roleid;	//crmv@83345
	
	$modlight = false;
	if (strpos($mod,'ModLight') !== false) {
		$modlight = true;
		list($ml_prefix,$name,$ml_sequence) = explode('_',$name);
	}
	// crmv@30528
	$ret = array();
	$res = $adb->pquery("select pickdest,pickdest_ids from {$table_prefix}_linkedlist where module = ? and picksrc = ? and picksrc_id = ?", array($mod, $name, $sel));
	// crmv@30528e
	//crmv@36522 crmv@131239
	if($res && $adb->num_rows($res) > 0) {
		while ($row=$adb->fetch_array_no_html($res)) {
			$values = Zend_Json::decode($row[1]);
			if (crmv_checkmandatory($row['pickdest'], $fieldinfo)){
				array_unshift($values,'');
			}
			$real_values = array();	//crmv@83345
			$values_trans = array();
			// translations
			if (is_array($values)) {
				//crmv@83345
				$pick_values = array_values(getAssignedPicklistValues($row['pickdest'],$roleid,$adb,$currentModule,'',false,false));
				foreach ($pick_values as $k=>$v){
					if (in_array($v,$values)) {
						$real_values[] = $v;
						$values_trans[] = getTranslatedString($v,$mod); //crmv@129679
					}
				}
				//crmv@83345e
			}
			//crmv@83345
			//crmv@67430
			if (crmv_checkmandatory($row['pickdest'], $fieldinfo)){
				array_unshift($real_values,'');
				array_unshift($values_trans,getTranslatedString("LBL_PLEASE_SELECT"));
			}
			//crmv@67430e
			$ret[] = array(($modlight)?"{$ml_prefix}_{$row[0]}_{$ml_sequence}":$row[0], $real_values, $values_trans);
			//crmv@83345e
		}
	} else{ // no rule, all rows
		// TODO: more code, see from 300.php/edit
		$children = linkedListGetConnectedLists($name, $mod); // crmv@30528
		foreach ($children as $c) {
			if ($sel == ''){
				$ret[] = array($c, Array(''), Array(''));
				continue;
			}
			$values = array_values(getAssignedPicklistValues($c, $roleid, $adb,$currentModule));
			if (crmv_checkmandatory($c, $fieldinfo)){
				array_unshift($values,'');
			}
			$values_trans = array();
			// translations
			if (is_array($values)) {
				foreach ($values as $v) $values_trans[] = getTranslatedString($v,$mod); //crmv@129679
			}
			//crmv@67430
			if (crmv_checkmandatory($c, $fieldinfo)){
				$values_trans[0] = getTranslatedString("LBL_PLEASE_SELECT");
			}
			//crmv@67430e
			$ret[] = array(($modlight)?"{$ml_prefix}_{$c}_{$ml_sequence}":$c, $values, $values_trans);
		}
	}
	//crmv@36522e crmv@131239e
	return $ret;
}


/*
 * Prende tutte le picklist che dipendono (direttamente) da name
 */
function linkedListGetConnectedLists($name, $mod) { // crmv@30528
	global $adb,$table_prefix;

	// crmv@30528 crmv@78052
	$ret = array();
	$res = $adb->pquery("select distinct pickdest from {$table_prefix}_linkedlist where module = ? and picksrc = ?", array($mod, $name));
	// crmv@30528e

	if ($res && $adb->num_rows($res) > 0) {
		while ($row = $adb->fetch_array_no_html($res)) {
			$ret[] = $row[0];
		}
	}

	return $ret;
	// crmv@78052e
}

/*
 * Cancella una connessione esistente tra 2 picklist
 * TODO: aggiungere modulo
 */
function linkedListDeleteLink($src, $module, $dest = NULL, $srcid = NULL) { // crmv@30528
	global $adb,$table_prefix;

	$params = array();
	$query = "delete from ".$table_prefix."_linkedlist where module = ? and picksrc = ?";
	array_push($params, $module, $src);

	if ($dest) {
		$query .= " and pickdest = ?";
		array_push($params, $dest);
	}
	if ($srcid) {
		$query .= " and picksrc_id = ?";
		array_push($params, $srcid);
	}
	$res = $adb->pquery($query, $params);
}


/*
 * Restituisce VERO se con l'aggiunta della nuova connessione si creano cicli
 * // TODO: ci sono degli errori in alcuni casi, usare algoritmo riscorsivo
 */
function checkCyclicPaths($src, $dest, $module) { // crmv@30528
	global $adb,$table_prefix;

	// controllo banale, stessa picklist
	if ($src === $dest) return true;

	// prendo la lista delle connessioni, includendo la nuova
	$rows = array();
	$res = $adb->pquery('select distinct picksrc, pickdest from '.$table_prefix.'_linkedlist where module = ?', array($module)); // crmv@30528
	if ($res && $adb->num_rows($res) > 0) {
		while ($row = $adb->fetch_array_no_html($res)) {
			$rows[] = array('picksrc'=>$row['picksrc'], 'pickdest'=>$row['pickdest']);
		}
		$newrow = array('picksrc'=>$src, 'pickdest'=>$dest);
		if (!in_array($newrow, $rows)) $rows[] = $newrow;
	} else {
		return false;
	}

	// costruisco la struttura delle connessioni
	$links = array();
	foreach ($rows as $v) {
		$picksrc = $v['picksrc'];
		$pickdest = $v['pickdest'];
		$justadd = false;
		if (!array_key_exists($picksrc, $links)) {
			$links[$picksrc] = array($pickdest);
			$justadd = true;
		}

		if (!$justadd && in_array($pickdest, $links[$picksrc])) {
			// pi� di un genitore
			return true;
		} else {
			// controllo e aggiungo alle altre picklist
			foreach ($links as $lk=>$lv) {
				if (in_array($picksrc, $lv)) {
					if ($pickdest == $lk || in_array($pickdest, $lv)) return true;
					else $links[$lk][] = $pickdest;
				}
			}
		}
	}
	return false;
}

// crmv@30528
function linkedListInitTables() {
	global $table_prefix;

	if(!Vtecrm_Utils::CheckTable($table_prefix.'_linkedlist')) {
		Vtecrm_Utils::CreateTable(
			$table_prefix.'_linkedlist',
			"id I(19) PRIMARY,
			module C(100),
			picksrc C(255),
			pickdest C(255),
			picksrc_id C(255),
			pickdest_ids X",
			true
		);
		return true;
	}
	return false;
}

// restituisce un array comodo per la configurazione
function linkedListGetAllOptions($plist1, $plist2, $module = 'All') {
	global $adb, $table_prefix, $currentModule, $current_user;

	$roleid = $current_user->roleid;

	$values1 = getAssignedPicklistValues($plist1, $roleid, $adb,$currentModule);
	$values2 = getAssignedPicklistValues($plist2, $roleid, $adb,$currentModule);
	if (isset($values1[''])) $values1[''] = getTranslatedString('LBL_EMPTY_LABEL','Charts');
	if (isset($values2[''])) $values2[''] = getTranslatedString('LBL_EMPTY_LABEL','Charts');

	$matrix = array();

	// reset matrix
	foreach ($values1 as $pkey=>$pval) {
		foreach ($values2 as $pkey2=>$pval2) {
			$matrix[$pkey][$pkey2] = 0;
		}
	}

	$res = $adb->pquery("select picksrc_id,pickdest_ids from {$table_prefix}_linkedlist where module = ? and picksrc = ? and pickdest = ?", array($module, $plist1, $plist2));
	if ($res) {
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$src = $row['picksrc_id'];
			$dest = Zend_Json::decode($row['pickdest_ids']);
			foreach ($dest as $dkey=>$dval) {
				$matrix[$src][$dval] = 1;
			}
		}
	}

	return array('values1'=>$values1, 'values2'=>$values2, 'matrix'=>$matrix);
}
// crmv@30528e

/*
 * Aggiunge una connessione tra 2 picklist
 */
function linkedListAddLink($src, $dest, $module, $srcval, $destval) { // crmv@30528
	global $adb,$table_prefix;

	// create table if it doesn't exist
	// crmv@30528
	if(!linkedListInitTables()) {

		// check duplicates
		$query = 'select id from '.$table_prefix.'_linkedlist where module = ? and picksrc = ? and pickdest = ? and picksrc_id = ?';
		$qparam = array($module, $src, $dest, $srcval);
		// crmv@30528e
		$res = $adb->pquery($query, $qparam);
		if ($res && $adb->num_rows($res) > 0) {
			return false;
		}

		// controllo cicli
		if (checkCyclicPaths($src, $dest, $module)) {  //crmv@30528
			return false;
		}
	}

	if (!is_array($destval)) $destval = array($destval);  // crmv@30528

	$id = $adb->getUniqueID($table_prefix."_linkedlist");
	// crmv@30528
	$params = array($id, $module, $src, $dest, $srcval, Zend_Json::encode($destval));
	$res = $adb->pquery("insert into ".$table_prefix."_linkedlist (id,module,picksrc,pickdest,picksrc_id,pickdest_ids) values (".generateQuestionMarks($params).")", array($params));
	// crmv@30528e
	return true;
}
//crmv@36522 crmv@131239
function crmv_checkmandatory($c, $fieldinfo=array()) {
	global $adb,$currentModule,$table_prefix;
	static $crmv_mandatoryfields = Array();
	if (isset($crmv_mandatoryfields[$currentModule][$c])){
		return $crmv_mandatoryfields[$currentModule][$c];
	}
	else{
		$crmv_mandatoryfields[$currentModule][$c] = false;
		if (isset($fieldinfo[$c])) {
			$typeOfData = $fieldinfo[$c];
			$typeOfData = explode("~",$typeOfData);
			$crmv_mandatoryfields[$currentModule][$c] = ($typeOfData[1] == 'M');
		} else {
			$sql = "select typeofdata from {$table_prefix}_field f
			inner join {$table_prefix}_tab t on t.tabid = f.tabid where f.fieldname = ?";
			$res = $adb->pquery($sql,Array($c));
			if ($res){
				$typeOfData = $adb->query_result_no_html($res,0,'typeofdata');
				$typeOfData = explode("~",$typeOfData);
				$crmv_mandatoryfields[$currentModule][$c] = ($typeOfData[1] == 'M');
			}
		}
	}
	return $crmv_mandatoryfields[$currentModule][$c];
}
//crmv@36522e crmv@131239e

//restituisce un array con le picklist disponibili per modulo
//TODO: rimuovi le picklist che provocan cicli?
//TODO: traduci label picklist
//crmv@122200 add join to _blocks
/* crmv@155047 add tab presence 2 */
function getAllPicklists() {
	global $adb, $table_prefix;
	
	$plist = array();
	
	$query = "
	SELECT vtab.name, vfield.fieldid, vfield.fieldname, vfield.uitype, vfield.fieldlabel
	from {$table_prefix}_field vfield
	inner join {$table_prefix}_blocks vblocks on vblocks.blockid = vfield.block
	inner join {$table_prefix}_tab vtab on vfield.tabid = vtab.tabid
	where vfield.uitype in (15,16,300) and vfield.presence in (0,2) and vtab.presence in (0,2) and vtab.tabid != 29
	order by vfield.tabid ASC";
	$res = $adb->query($query);

	if ($res) {
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$modname = $row['name'];
			unset($row['name']);
			$row['fieldlabel'] = getTranslatedString($row['fieldlabel'], $modname);
			$plist[$modname][] = $row;
		}
		// rimuovo moduli con una sola picklist
		$plist = array_filter($plist, function($v) {
			return (count($v) > 1);
		});
	}

	return $plist;
}

//restituisce un array con le connessioni esistenti (coppie di campi)
function getConnections($mod = 'All') {
	global $adb, $table_prefix;

	$params = array();
	if (!empty($mod) && $mod != 'All') {
		$extrawhere = ' and vlink.module = ?';
		$params[] = $mod;
	}

	//crmv@57238 - add ",vfield.tabid"
	$query = "
	SELECT distinct vlink.picksrc, vlink.pickdest, vtab.name as modulename, vfield.fieldid, vfield.uitype, vfield.fieldlabel, vfield2.fieldlabel as fieldlabel2,vfield.tabid
	from {$table_prefix}_linkedlist vlink
	inner join {$table_prefix}_tab vtab on vlink.module = vtab.name
	inner join {$table_prefix}_field vfield on vfield.fieldname = vlink.picksrc and vfield.tabid = vtab.tabid
	inner join {$table_prefix}_field vfield2 on vfield2.fieldname = vlink.pickdest and vfield2.tabid = vtab.tabid
	where vfield.uitype in (15,16,300) and vfield.presence in (0,2) and vtab.presence in (0,2) $extrawhere
	order by vfield.tabid ASC";
	$res = $adb->pquery($query, $params);

	$plist = array();
	if ($res) {
		while ($row = $adb->fetchByAssoc($res, -1, false)) {
			$modname = $row['modulename'];
			unset($row['name']);
			$row['modulelabel'] = getTranslatedString($modname, $modname);
			$row['label1'] = getTranslatedString($row['fieldlabel'], $modname);
			$row['label2'] = getTranslatedString($row['fieldlabel2'], $modname);
			$plist[] = $row;
		}
	}
	usort($plist, function($a, $b) {
		return ($a['modulelabel'] > $b['modulelabel']);
	});
	return $plist;
}