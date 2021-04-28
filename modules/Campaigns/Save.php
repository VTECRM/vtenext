<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$focus = CRMEntity::getInstance('Campaigns');
 global $current_user, $currentModule;
 $currencyid=fetchCurrency($current_user->id);
 $rate_symbol = getCurrencySymbolandCRate($currencyid);
 $rate = $rate_symbol['rate'];
//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);
setObjectValuesFromRequest($focus);

// crmv@83877
if(isset($_REQUEST['expectedrevenue']))
{
	$value = convertToDollar(parseUserNumber($_REQUEST['expectedrevenue']),$rate);
	$focus->column_fields['expectedrevenue'] = formatUserNumber($value);	
}
if(isset($_REQUEST['budgetcost']))
{
	$value = convertToDollar(parseUserNumber($_REQUEST['budgetcost']),$rate);
	$focus->column_fields['budgetcost'] = formatUserNumber($value);
}
if(isset($_REQUEST['actualcost']))
{
	$value = convertToDollar(parseUserNumber($_REQUEST['actualcost']),$rate);
	$focus->column_fields['actualcost'] = formatUserNumber($value);
}
if(isset($_REQUEST['actualroi']))
{
	$value = convertToDollar(parseUserNumber($_REQUEST['actualroi']),$rate);
	$focus->column_fields['actualroi'] = formatUserNumber($value);
}
if(isset($_REQUEST['expectedroi']))
{
	$value = convertToDollar(parseUserNumber($_REQUEST['expectedroi']),$rate);
	$focus->column_fields['expectedroi'] = formatUserNumber($value);
}
// crmv@83877e

if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save("Campaigns");
$return_id = $focus->id;

$parenttab = getParentTab();
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = vtlib_purify($_REQUEST['return_module']);
else $return_module = "Campaigns";
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

$url = "index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");
?>