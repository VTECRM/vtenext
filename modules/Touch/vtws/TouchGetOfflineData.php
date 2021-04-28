<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33097 - prende tutti i record */
/* DEPRECATED ---- This webservice is deprecated ---- DEPRECATED */

global $adb, $table_prefix;
global $login, $userId, $current_user, $currentModule;

$page = intval($_REQUEST['page']);
if ($page == 0) $page = 1;

if (!$login || empty($userId)) {
	echo 'Login Failed';
} elseif (!$touchInst->isOfflineEnabled()) {
	echo 'Offline disabled';
} else {

	$listLimit = $touchInst->offline_max_items;

	// creo tabella per offline
	if (!Vtecrm_Utils::CheckTable($table_prefix.'_offline_app')) {
		$schema = '<?xml version="1.0"?>
		<schema version="0.3">
			<table name="'.$table_prefix.'_offline_app">
				<opt platform="mysql">ENGINE=InnoDB</opt>
				<field name="userid" type="I" size="11">
					<key/>
				</field>
				<field name="crmid" type="I" size="11">
					<key/>
				</field>
				<field name="tabid" type="I" size="11" />
				<field name="createdtime" type="T" />
				<field name="modifiedtime" type="T" />
				<index name="offline_app_tabid">
					<col>tabid</col>
				</index>
			</table>
		</schema>';
		$schema_obj = new adoSchema($adb->database);
		$schema_obj->ExecuteSchema($schema_obj->ParseSchemaString($schema));
	}

	// pulisco la tabella per l'utente
	$adb->pquery("delete from {$table_prefix}_offline_app where userid = ?", array($current_user->id));

	// prendo i moduli che mi interessano
	$modulesQuery = array();
	$totalRows = 0;
	$modulesList = touchModulesList();
	foreach ($modulesList as $modinfo) {
		$module = $modinfo['view'];

		// calcolo la query per la lista
		$focus = CRMEntity::getInstance($module);
		$queryGenerator = QueryGenerator::getInstance($module, $current_user);
		$queryGenerator->initForDefaultCustomView();
		$queryGenerator->addField('crmid');
		$queryGenerator->addField('createdtime');
		$queryGenerator->addField('modifiedtime');
		$list_query = $queryGenerator->getQuery();

		if ($module == 'Calendar') {
			$list_query .= " AND {$table_prefix}_activity.activitytype = 'Task'";
		}

		// ordinamento (record modificati più di recente)
		$list_query .= $focus->getFixedOrderBy($currentModule,'modifiedtime','DESC');

		// prendo i valori (max 1000 per modulo)
		// TODO: usare una insert select?
		$result = $adb->limitQuery($list_query, 0,$listLimit);

		// salvo nella tabella offline
		if ($result && $adb->num_rows($result) > 0) {
			$totalRows += $adb->num_rows($result);
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				if (empty($row['crmid']))
					$row['crmid'] = $row[$focus->tab_name_index[$focus->table_name]];
				if (empty($row['crmid']))
					continue;

				$insquery = "insert into {$table_prefix}_offline_app (userid, crmid, tabid, createdtime, modifiedtime) values (?,?,?,?,?)";
				$params = array($current_user->id, $row['crmid'], $modinfo['tabid'], $row['createdtime'], $row['modifiedtime']);
				$adb->pquery($insquery, $params);
			}
		}

	}

	// ------ lettura record ------------

	$entries = array();
	$pagesize = $touchInst->offline_chunks;
	$start = ($page-1)*$pagesize;
	$end = $page*$pagesize;
	if ($end <= $listLimit) {

		$query = "
			select
				crmid, vtab.name as module
			from {$table_prefix}_offline_app offline
				inner join {$table_prefix}_tab vtab on vtab.tabid = offline.tabid
			where offline.userid = ?
			order by offline.modifiedtime DESC, offline.crmid DESC";
		$result = $adb->limitpQuery($query, $start,$pagesize, array($current_user->id));

		if ($result && $adb->num_rows($result) > 0) {
			while ($row = $adb->fetchByAssoc($result, -1, false)) {
				$crmid = $row['crmid'];
				$module = $row['module'];

				$record = touchGetRecord($module, $crmid);
				$entries[] = $record;
			}
		}

		/*if ($result && $adb->num_rows($result) > 0) {
			$lastdate = $adb->query_result($result, $adb->num_rows($result)-1, 'modifiedtime');
			$lastcrmid = $adb->query_result($result, $adb->num_rows($result)-1, 'crmid');

			if ($lastdate && $lastcrmid) {
				// elimino le righe che eccedono
				$adb->pquery("delete from {$table_prefix}_offline_app where userid = ? and modifiedtime < ? and crmid < ?", array($current_user->id, $lastdate, $lastcrmid));
			}
		}*/
	}

	// a questo punto nella tabella temporanea ho solamente gli id da prelevare
	/*$result = $adb->pquery("select crmid from {$table_prefix}_offline_app where userid = ?", array($current_user->id));
	if ($result) {
		while ($row = $adb->fetchByAssoc)
	}*/



	echo Zend_Json::encode(array('entries' => $entries, 'total' => min($totalRows, $listLimit)));
}
?>