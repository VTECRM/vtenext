<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$current_user;//crmv@203484 removed global singlepane
global $table_prefix;

$queryGenerator = QueryGenerator::getInstance(vtlib_purify($_REQUEST["list_type"]), $current_user);
$queryGenerator->initForCustomViewById(vtlib_purify($_REQUEST["cvid"]));
$list_query = $queryGenerator->getQuery();
$list_query = replaceSelectQuery($list_query,$table_prefix.'_crmentity.crmid');
$rs = $adb->query($list_query);

if($_REQUEST["list_type"] == "Leads"){
	$reltable = $table_prefix."_campaignleadrel";
	$relid = "leadid";
}
elseif($_REQUEST["list_type"] == "Contacts"){
	$reltable = $table_prefix."_campaigncontrel";
	$relid = "contactid";
}
elseif($_REQUEST["list_type"] == "Accounts"){
	$reltable = $table_prefix."_campaignaccountrel";
	$relid = "accountid";
}

while($row=$adb->fetch_array($rs)) {
	$sql = "SELECT $relid FROM $reltable WHERE $relid = ? AND campaignid = ?";
	$result = $adb->pquery($sql, array($row['crmid'], $_REQUEST['return_id']));
	if ($adb->num_rows($result) > 0) continue;
	$adb->pquery("INSERT INTO $reltable(campaignid,$relid,campaignrelstatusid) VALUES(?,?,1)", array($_REQUEST["return_id"], $row["crmid"]));
}

header("Location: index.php?module=Campaigns&action=CampaignsAjax&file=CallRelatedList&ajax=true&".
"record=".vtlib_purify($_REQUEST['return_id']));
?>