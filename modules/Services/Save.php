<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user, $currentModule;
if (isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != '') {

    check_duplicate(vtlib_purify($_REQUEST['module']),
        vtlib_purify($_REQUEST['colnames']), vtlib_purify($_REQUEST['fieldnames']),
        vtlib_purify($_REQUEST['fieldvalues']));
    die;
}

$focus = CRMEntity::getInstance($currentModule);
setObjectValuesFromRequest($focus);

$mode = $_REQUEST['mode'];
$record = $_REQUEST['record'];
if ($mode) {
    $focus->mode = $mode;
}
if ($record) {
    $focus->id = $record;
}

$currencyId = fetchCurrency($current_user->id);
$rateSymbol = getCurrencySymbolandCRate($currencyId);
$rate = $rateSymbol['rate'];

if ($_REQUEST['assigntype'] == 'U') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif ($_REQUEST['assigntype'] == 'T') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save($currentModule);
$returnId = $focus->id;

$search = vtlib_purify($_REQUEST['search_url']);

$parentTab = getParentTab();
if ($_REQUEST['return_module'] != '') {
    $returnModule = vtlib_purify($_REQUEST['return_module']);
} else {
    $returnModule = $currentModule;
}

if ($_REQUEST['return_action'] != '') {
    $returnAction = vtlib_purify($_REQUEST['return_action']);
} else {
    $returnAction = "DetailView";
}

if ($_REQUEST['return_id'] != '') {
    $returnId = vtlib_purify($_REQUEST['return_id']);
}

//crmv@54375
if ($_REQUEST['return2detail'] == 'yes') {
    $returnModule = $currentModule;
    $returnAction = 'DetailView';
    $returnId = $focus->id;
}
//crmv@54375e

if (isset($_REQUEST['activity_mode'])) $returnAction .= '&activity_mode=' . vtlib_purify($_REQUEST['activity_mode']);

$url = "index.php?action=$returnAction&module=$returnModule&record=$returnId&parenttab=$parentTab&start=" . vtlib_purify($_REQUEST['pagenumber']) . $search;

$fromModule = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) {
    $url .= "&from_module=$fromModule";
}

header("Location: $url");
?>