<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $current_user, $table_prefix;

$mode = vtlib_purify($_REQUEST['mode']);
$search = vtlib_purify($_REQUEST['term']);
$uidlist = vtlib_purify($_REQUEST['uidlist']);
$cidlist = vtlib_purify($_REQUEST['cidlist']);

if ($uidlist != '') {
	$uidlist = array_filter(explode('|', $uidlist));
}

if ($cidlist != '') {
	$cidlist = array_filter(explode('|', $cidlist));
}

$return = array();

if ($mode == 'Users') {
	$query = 'select id, user_name, first_name, last_name, avatar from ' . $table_prefix . '_users where status = ? and (user_name like ? or first_name like ? or last_name like ?)';
	$params = array('Active', "%$search%", "%$search%", "%$search%");
	$query .= ' and id <> ?';
	$params[] = $current_user->id;
	if (!empty($uidlist)) {
		$query .= ' and id not in (' . generateQuestionMarks($uidlist) . ')';
		$params[] = $uidlist;
	}
	$result = $adb->pquery($query, $params);
	if ($result && $adb->num_rows($result) > 0) {
		while ($row = $adb->fetchByAssoc($result)) {
			$avatar = $row['avatar'];
			if ($avatar == '') {
				$avatar = getDefaultUserAvatar();
			}
			$full_name = trim($row['first_name'] . ' ' . $row['last_name']);
			$return[] = array('value' => $row['id'], 'label' => $row['user_name'] . ' (' . $full_name . ')', 'full_name' => $full_name, 'user_name' => $row['user_name'], 'img' => $avatar);
		}
	}
} else {
	if (vtlib_isModuleActive($mode) && $moduleInstance = Vtecrm_Module::getInstance($mode)) {
		$moduleEntity = CRMEntity::getInstance($mode);
		
		$query = "SELECT fieldname, tablename, entityidfield FROM {$table_prefix}_entityname WHERE modulename=?";
		$result = $adb->pquery($query, array($mode));
		$fieldsname = $adb->query_result($result, 0, 'fieldname');
		$tablename = $adb->query_result($result, 0, 'tablename');
		$entityidfield = $adb->query_result($result, 0, 'entityidfield');
		
		if (!(strpos($fieldsname, ',') === false)) {
			$fieldlists = explode(',', $fieldsname);
			foreach ($fieldlists as $w => $c) {
				if (count($fl)) {
					$fl[] = "' '";
				}
				$wsfield = WebserviceField::fromQueryResult($adb, $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid=? AND fieldname=?", array($moduleInstance->id, $c)), 0);
				$fl[] = $wsfield->getTableName() . '.' . $wsfield->getColumnName();
				$search_fields[] = $wsfield->getTableName() . '.' . $wsfield->getColumnName();
			}
			$fieldsname = $adb->sql_concat($fl);
		} else {
			$wsfield = WebserviceField::fromQueryResult($adb, $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid=? AND fieldname=?", array($moduleInstance->id, $fieldsname)), 0);
			$fieldsname = $wsfield->getTableName() . '.' . $wsfield->getColumnName();
			$search_fields[] = $wsfield->getTableName() . '.' . $wsfield->getColumnName();
		}
		
		$query = "SELECT crmid, $tablename.$entityidfield, $fieldsname entityname FROM {$tablename}";
		
		if ($mode != 'Users') {
			$query .= " INNER JOIN {$table_prefix}_crmentity ON $tablename.$entityidfield = {$table_prefix}_crmentity.crmid";
		}
		
		if (!empty($moduleEntity->customFieldTable)) {
			$query .= " INNER JOIN " . $moduleEntity->customFieldTable[0] . " ON $tablename.$entityidfield = " . $moduleEntity->customFieldTable[0] . "." . $moduleEntity->customFieldTable[1];
		}
		
		if ($mode == 'Leads') {
			$query .= " WHERE {$table_prefix}_crmentity.deleted = 0 AND converted = 0";
		} else {
			$query .= " WHERE {$table_prefix}_crmentity.deleted = 0";
		}
		
		foreach ($search_fields as $field) {
			$search_conditions[] = "$field LIKE '%$search%'";
		}
		
		if (!empty($search_conditions)) {
			$query .= ' AND (' . implode(' OR ', $search_conditions) . ')';
		}
		
		$params = array();
		if (!empty($cidlist)) {
			$query .= " AND {$table_prefix}_crmentity.crmid NOT IN (" . generateQuestionMarks($cidlist) . ")";
			$params[] = $cidlist;
		}
		
		if ($mode != 'Users') {
			$secQuery = getNonAdminAccessControlQuery($mode, $current_user);
			if (strlen($secQuery) > 1) {
				$query = appendFromClauseToQuery($query, $secQuery);
			}
		}
		
		$query .= " ORDER BY entityname, $tablename.$entityidfield";
		
		$result = $adb->limitPQuery($query, 0, 12, $params);
		if ($result && $adb->num_rows($result)) {
			while ($row = $adb->fetchByAssoc($result)) {
				$crmid = $row['crmid'];
				$entityname = $row['entityname'];
				$return[] = array('value' => $crmid, 'label' => $entityname, 'entityname' => $entityname);
			}
		}
	}
}

echo Zend_Json::encode($return);
exit();

?>