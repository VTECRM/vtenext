<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

global $current_user, $currentModule;

$localLog = LoggerManager::getLogger();

$focus = CRMEntity::getInstance('PurchaseOrder');
//added to fix 4600
$search = vtlib_purify($_REQUEST['search_url']);

setObjectValuesFromRequest($focus);

//Added code for auto product stock updation on receiving goods
$focus->update_prod_stock = '';
if ($focus->column_fields['postatus'] == 'Received Shipment') {
    if ($focus->mode != 'edit')
        $focus->update_prod_stock = 'true';
    else {
        $prev_postatus = getPoStatus($focus->id);
        if ($focus->column_fields['postatus'] != $prev_postatus) {
            $focus->update_prod_stock = 'true';
        }
    }
}

$focus->column_fields['currency_id'] = $_REQUEST['inventory_currency'];
$currencySymRate = getCurrencySymbolandCRate($_REQUEST['inventory_currency']);
$focus->column_fields['conversion_rate'] = $currencySymRate['rate'];

if ($_REQUEST['assigntype'] == 'U') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif ($_REQUEST['assigntype'] == 'T') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
$focus->save("PurchaseOrder");

$returnId = $focus->id;

if (isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $returnModule = vtlib_purify($_REQUEST['return_module']);
else $returnModule = "PurchaseOrder";
if (isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") $returnAction = vtlib_purify($_REQUEST['return_action']);
else $returnAction = "DetailView";
if (isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") $returnId = vtlib_purify($_REQUEST['return_id']);
$parenttab = getParentTab();

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

$localLog->debug("Saved record with id of " . $returnId);

$url = "index.php?action=$returnAction&module=$returnModule&record=$returnId&parenttab=$parenttab&viewname=$returnViewname&start=" . vtlib_purify($_REQUEST['pagenumber']) . $search;

$fromModule = vtlib_purify($_REQUEST['module']);
if (!empty($fromModule)) {
    $url .= "&from_module=$fromModule";
}

RequestHandler::outputRedirect($url); // crmv@150748
