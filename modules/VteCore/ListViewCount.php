<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/

$idlist = vtlib_purify($_REQUEST['idlist']);
$viewid = vtlib_purify($_REQUEST['viewname']);
$module = vtlib_purify($_REQUEST['module']);
$related_module = vtlib_purify($_REQUEST['related_module']);

global $adb;

if(vtlib_purify($_REQUEST['mode'])=='relatedlist') {
	if($related_module == 'Accounts') {
		$result = getCampaignAccountIds($idlist);
	}
	if($related_module == 'Contacts') {
		$result = getCampaignContactIds($idlist);
	}
	if($related_module == 'Leads') {
		$result = getCampaignLeadIds($idlist);
	}
} else {
	$result = getSelectAllQuery($_REQUEST,$module);
}
$numRows = $adb->num_rows($result);
echo $numRows;