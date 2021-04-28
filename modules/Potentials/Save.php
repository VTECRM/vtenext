<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

require_once('modules/Potentials/Potentials.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');

$local_log =& LoggerManager::getLogger();

$focus = CRMEntity::getInstance('Potentials');
//added to fix 4600
$search = vtlib_purify($_REQUEST['search_url']);
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

global $current_user, $currentModule;
$currencyid = fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$curr_symbol= $rate_symbol['symbol'];
$rate = $rate_symbol['rate'];

setObjectValuesFromRequest($focus);

if(isset($_REQUEST['amount']))
{
	// crmv@83877
	$value = convertToDollar(parseUserNumber($_REQUEST['amount']),$rate);
	$focus->column_fields['amount'] = formatUserNumber($value);
	// crmv@83877e
}

if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}
$focus->save("Potentials");
$pot_id = $return_id = $focus->id;

$parenttab = getParentTab();
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = vtlib_purify($_REQUEST['return_module']);
else $return_module = "Potentials";

if(isset($_REQUEST['return_action']) && $_REQUEST['return_action'] != "") $return_action = vtlib_purify($_REQUEST['return_action']);
else $return_action = "DetailView";

if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "") $return_id = vtlib_purify($_REQUEST['return_id']);

//crmv@54375
if($_REQUEST['return2detail'] == 'yes') {
	$return_module = $currentModule;
	$return_action = 'DetailView';
	$return_id = $focus->id;
}
//crmv@54375e

$local_log->debug("Saved record with id of ".$return_id);

//code added for returning back to the current view after edit from list view

$return_viewname = $_REQUEST['return_viewname'] == '' ? '0' : vtlib_purify($_REQUEST['return_viewname']);
$url = "index.php?action={$return_action}&module={$return_module}&parenttab={$parenttab}&record={$return_id}&pot_id={$pot_id}&viewname={$return_viewname}&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");