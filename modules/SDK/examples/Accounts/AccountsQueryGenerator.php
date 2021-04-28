<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
die('exit');

require('../../../../config.inc.php');
chdir($root_directory);

require_once('include/utils/utils.php');
global $adb, $table_prefix, $current_user;

$module = 'Accounts';
$userid = 1;			// utente usato per applicare i permessi di visualizzazione alla query
$viewid = '';			// definisco una customview(filtro), se lascio vuoto viene usato il filtro Tutti
$listFields = array('accountname','phone','email1');	// campi del modulo da estrarre (SELECT)
$conditions = array(									// condizioni sui campi	(WHERE)
	array('fieldname'=>'accountname','operation'=>'c','value'=>'vte','glue'=>'or'),
	array('fieldname'=>'accountname','operation'=>'e','value'=>'X-CEED INC 99','glue'=>''),
);
/* operations:
	e: equal
	n: not equal
	c: contains
	k: does not contain
	s: starts with
	ew: ends with
	l: less than
	g: greater than
	m: less than or equal to
	h: greater than or equal to
*/

$current_user = CRMEntity::getInstance('Users');
$current_user->retrieveCurrentUserInfoFromFile($userid);
$queryGenerator = QueryGenerator::getInstance($module,$current_user);
if ($viewid != "0") {
	$queryGenerator->initForCustomViewById($viewid);
} else {
	$queryGenerator->initForDefaultCustomView();
}
if (!empty($listFields)) $queryGenerator->setFields($listFields);
if (!empty($conditions)) {
	$counditions_count = count($conditions);
	$qgWhereFields = $queryGenerator->getWhereFields();
	if ($counditions_count > 1) (!empty($qgWhereFields)) ? $queryGenerator->startGroup(QueryGenerator::$AND) : $queryGenerator->startGroup('');
	elseif (!empty($qgWhereFields)) $queryGenerator->addConditionGlue(QueryGenerator::$AND);
	foreach($conditions as $condition) {
		$queryGenerator->addCondition($condition['fieldname'], $condition['value'], $condition['operation']);
		if ($condition['glue'] == 'and') $queryGenerator->addConditionGlue(QueryGenerator::$AND);
		elseif ($condition['glue'] == 'or') $queryGenerator->addConditionGlue(QueryGenerator::$OR);
	}
	if ($counditions_count > 1) $queryGenerator->endGroup();
}

$list_query = $queryGenerator->getQuery();
echo $list_query.'<br>';

$result = $adb->query($list_query);
if ($result && $adb->num_rows($result) > 0) {
	while($row=$adb->fetchByAssoc($result)) {
		preprint($row);
	}
}