<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

global $adb;
global $mod_strings;
global $table_prefix;
global $currentModule;

require_once('include/logging.php');
$log = LoggerManager::getLogger();

$focus = CRMEntity::getInstance($currentModule);

//Added to fix 4600
$url = getBasic_Advance_SearchURL();

if(!isset($_REQUEST['record']))
    die($mod_strings['ERR_DELETE_RECORD']);

//Added to delete the pricebook from Product related list
if($_REQUEST['record'] != '' && $_REQUEST['return_id'] != '' && $_REQUEST['module'] == 'PriceBooks'
    && isProductModule($_REQUEST['return_module'])) // crmv@64542
{
    $productid = $_REQUEST['return_id'];
    $pricebookid = $_REQUEST['record'];
    $adb->pquery("delete from {$table_prefix}_pricebookproductrel where pricebookid=? and productid=?", [$pricebookid, $productid]);
}

if($_REQUEST['module'] == $_REQUEST['return_module'])
    $focus->mark_deleted($_REQUEST['record']);

$parenttab = null;

if(isset($_REQUEST['parenttab']) && $_REQUEST['parenttab'] != "") {
    $parenttab = $_REQUEST['parenttab'];
}

header("Location: index.php?module=".vtlib_purify($_REQUEST['return_module'])."&action=".vtlib_purify($_REQUEST['return_action'])."&record=".vtlib_purify($_REQUEST['return_id'])."&parenttab={$parenttab}{$url}");