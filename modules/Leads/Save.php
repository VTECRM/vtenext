<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Leads/Leads.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');
require_once('include/utils/UserInfoUtil.php');

$local_log =& LoggerManager::getLogger('index');
global $log,$adb,$currentModule;
global $table_prefix;
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

$focus = CRMEntity::getInstance('Leads');
global $current_user;
$currencyid=fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];
$curr_symbol=$rate_symbol['symbol'];
//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);

if(isset($_REQUEST['record']))
{
	$focus->id = $_REQUEST['record'];
}
if(isset($_REQUEST['mode']))
{
	$focus->mode = $_REQUEST['mode'];
}

setObjectValuesFromRequest($focus); // crmv@186949

if(isset($_REQUEST['annualrevenue'])) {
	// crmv@83877
	$value = convertToDollar(parseUserNumber($_REQUEST['annualrevenue']),$rate);
	$focus->column_fields['annualrevenue'] = formatUserNumber($value);
	// crmv@83877e
}

if($_REQUEST['assigntype'] == 'U') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

$focus->save("Leads");

$return_id = $focus->id;
$log->info("the return id is ".$return_id);
$parenttab = getParentTab();
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = vtlib_purify($_REQUEST['return_module']);
else $return_module = "Leads";
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
if($_REQUEST['return_viewname'] == '') $return_viewname='0';
if($_REQUEST['return_viewname'] != '')$return_viewname=vtlib_purify($_REQUEST['return_viewname']);

if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] == "Campaigns")
{
	if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "")
	{
		 $campLeadStatusResult = $adb->pquery("select campaignrelstatusid from ".$table_prefix."_campaignleadrel where campaignid=? AND leadid=?",array($_REQUEST['return_id'], $focus->id));
		 $leadStatus = $adb->query_result($campLeadStatusResult,0,'campaignrelstatusid');
		 $sql = "delete from ".$table_prefix."_campaignleadrel where leadid = ?";
		 $adb->pquery($sql, array($focus->id));
		 if(isset($leadStatus) && $leadStatus !=''){
		 	$sql = "insert into ".$table_prefix."_campaignleadrel values (?,?,?)";
		 	$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id,$leadStatus));
		 }
		 else{
		 	$sql = "insert into ".$table_prefix."_campaignleadrel values (?,?,1)";
		 	$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id));
		}
	}
}

$url = "index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&viewname=$return_viewname&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");