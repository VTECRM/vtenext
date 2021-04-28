<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $currentModule;

$focus = CRMEntity::getInstance('Vendors');
//added to fix 4600
$search = vtlib_purify($_REQUEST['search_url']);
if (isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != '') {

    check_duplicate(vtlib_purify($_REQUEST['module']),
        vtlib_purify($_REQUEST['colnames']), vtlib_purify($_REQUEST['fieldnames']),
        vtlib_purify($_REQUEST['fieldvalues']));
    die;
}
setObjectValuesFromRequest($focus);

if ($_REQUEST['assigntype'] == 'U') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif ($_REQUEST['assigntype'] == 'T') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
$focus->save("Vendors");
$returnId = $focus->id;

if (isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") {
    $returnModule = vtlib_purify($_REQUEST['return_module']);
} else {
    $returnModule = "Vendors";
}

if (isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") {
    $returnAction = vtlib_purify($_REQUEST['return_action']);
} else {
    $returnAction = "DetailView";
}

if (isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") {
    $returnId = vtlib_purify($_REQUEST['return_id']);
}

//crmv@54375
if ($_REQUEST['return2detail'] == 'yes') {
    $returnModule = $currentModule;
    $returnAction = 'DetailView';
    $returnId = $focus->id;
}
//crmv@54375e

//code added for returning back to the current view after edit from list view
if ($_REQUEST['return_viewname'] == '') {
    $returnViewname = '0';
}
if ($_REQUEST['return_viewname'] != '') {
    $returnViewname = vtlib_purify($_REQUEST['return_viewname']);
}

$url = "index.php?action={$returnAction}&module={$returnModule}&record={$returnId}&viewname={$returnViewname}&smodule=VENDOR&start=" . vtlib_purify($_REQUEST['pagenumber']) . $search;

$fromModule = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) {
    $url .= "&from_module=$fromModule";
}

header("Location: $url");