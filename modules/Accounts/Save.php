<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


global $currentModule;
global $table_prefix;
//added to fix 4600
$search=vtlib_purify($_REQUEST['search_url']);
if(isset($_REQUEST['dup_check']) && $_REQUEST['dup_check'] != ''){
	
	check_duplicate(vtlib_purify($_REQUEST['module']),
	vtlib_purify($_REQUEST['colnames']),vtlib_purify($_REQUEST['fieldnames']),
	vtlib_purify($_REQUEST['fieldvalues']));
	die;
}

//crmv@7213
if(isset($_REQUEST['EXT_CODE']) && $_REQUEST['EXT_CODE'] != '')
{
	$value = vtlib_purify($_REQUEST['external_code']); // crmv@189298
	$query = 
		"SELECT accountid 
		FROM {$table_prefix}_account, {$table_prefix}_crmentity 
		WHERE {$table_prefix}_account.external_code = ? and {$table_prefix}_account.accountid = {$table_prefix}_crmentity.crmid and {$table_prefix}_crmentity.deleted = 1";
	$result = $adb->pquery($query, array($value));
	if($adb->num_rows($result) > 0)
	{
		echo "duplicate";
	}
	else
	{
		$query2 = 
			"SELECT accountid 
			FROM {$table_prefix}_account, {$table_prefix}_crmentity 
			WHERE {$table_prefix}_account.external_code = ? and {$table_prefix}_account.accountid = {$table_prefix}_crmentity.crmid and {$table_prefix}_crmentity.deleted != 1";
		$result2 = $adb->pquery($query2, array($value));
		if($adb->num_rows($result2) > 0)
		{
			require_once('modules/Accounts/MergeCodeAccount.php');
			$ext_code=$adb->query_result($result2,0,0);
			if (check_merge_permission_CODE($_REQUEST['record'],$ext_code)){
				echo $ext_code;
			}
			else echo "owner";
		}
		else
		{
			echo "false";
		}
	}
	die;	
}

if(isset($_REQUEST['MergeCode']) && $_REQUEST['MergeCode'] != ''){
	include ('modules/Accounts/MergeCodeAccount.php');
	$focus2=CRMEntity::getInstance('Accounts');
	$focus2->mode = "edit";
	$focus2->retrieve_entity_info($_REQUEST['idEXT'],"Accounts");
	$focus=CRMEntity::getInstance('Accounts');
	$focus->mode = "edit";
	$focus->retrieve_entity_info($_REQUEST['idCRM'],"Accounts");
	if ($focus2->crmv_compare_column_fields($focus2->column_fields,$focus->column_fields)) {
		$focus->crmv_save_ajax_code($focus2->column_fields['external_code']);
		$focus->id=$_REQUEST['idCRM'];
		$focus->save("Accounts");
		$focus2->id=$_REQUEST['idEXT'];
		$focus2->save("Accounts");
		crmv_merge_account($_REQUEST['idCRM'],$_REQUEST['idEXT']);
		echo "true";
	}
	else echo "false";
	die;
}
//crmv@7213e

$local_log =& LoggerManager::getLogger('index');
global $log;
$focus = CRMEntity::getInstance('Accounts');
global $current_user;
$currencyid=fetchCurrency($current_user->id);
$rate_symbol = getCurrencySymbolandCRate($currencyid);
$rate = $rate_symbol['rate'];
$curr_symbol = $rate_symbol['symbol'];
if(isset($_REQUEST['record']))
{
	$focus->id = $_REQUEST['record'];
$log->info("id is ".$focus->id);
}
if(isset($_REQUEST['mode']))
{
	$focus->mode = $_REQUEST['mode'];
}

setObjectValuesFromRequest($focus); // crmv@186949

if(isset($_REQUEST['annual_revenue']))
{
	// crmv@83877
	$value = convertToDollar(parseUserNumber($_REQUEST['annual_revenue']),$rate);
	$focus->column_fields['annual_revenue'] = formatUserNumber($value);
	// crmv@83877e
}

if($_REQUEST['assigntype'] == 'U')  {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
} elseif($_REQUEST['assigntype'] == 'T') {
	$focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
}

//When changing the Account Address Information  it should also change the related contact address - dina
if($focus->mode == 'edit' && $_REQUEST['address_change'] == 'yes')
{
	$focus->updateContactsAddress(); // crmv@193226
}
//Changing account address - Ends

$focus->save("Accounts");
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] == "Campaigns")
{
	if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != "")
	{
		$campAccStatusResult = $adb->pquery("select campaignrelstatusid from ".$table_prefix."_campaignaccountrel where campaignid=? AND accountid=?",array($_REQUEST['return_id'], $focus->id));
		$accountStatus = $adb->query_result($campAccStatusResult,0,'campaignrelstatusid');
		$sql = "delete from ".$table_prefix."_campaignaccountrel where accountid = ?";
		$adb->pquery($sql, array($focus->id));
		if(isset($accountStatus) && $accountStatus!=''){
			$sql = "insert into ".$table_prefix."_campaignaccountrel values (?,?,?)";
			$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id,$accountStatus));
		}
		else{
			$sql = "insert into ".$table_prefix."_campaignaccountrel values (?,?,1)";
			$adb->pquery($sql, array($_REQUEST['return_id'], $focus->id));		
		}
	}
}
$return_id = $focus->id;

$parenttab = getParentTab();
if(isset($_REQUEST['return_module']) && $_REQUEST['return_module'] != "") $return_module = vtlib_purify($_REQUEST['return_module']);
else $return_module = "Accounts";
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
if($_REQUEST['return_viewname'] != '') $return_viewname=vtlib_purify($_REQUEST['return_viewname']);

$url = "index.php?action=$return_action&module=$return_module&parenttab=$parenttab&record=$return_id&viewname=$return_viewname&start=".vtlib_purify($_REQUEST['pagenumber']).$search;

$from_module = vtlib_purify($_REQUEST['module']);
if (!empty($from_module)) $url .= "&from_module=$from_module";

header("Location: $url");