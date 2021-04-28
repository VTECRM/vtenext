<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $result;
global $client;

$modules = $_REQUEST['module']; // crmv@5946
$smarty = new VTECRM_Smarty();
$smarty->assign('TITLE',$module);

$onlymine=$_REQUEST['onlymine'];
if($onlymine == 'true') {
    $mine_selected = 'selected';
    $all_selected = '';
} else {
    $mine_selected = '';
    $all_selected = 'selected';
}
if ($customerid != '')
{
	$params = array();
	$allow_all = $client->call('show_all',array('module'=>$modules),$Server_Path, $Server_Path);
	
    if($allow_all == 'true') {
    	$smarty->assign('ALLOW_ALL',$allow_all);
    	$smarty->assign('MINE_SELECTED',$mine_selected);
    	$smarty->assign('ALL_SELECTED',$all_selected);
	}
	  
// 	$block = "Contacts";
	$module = $block;
	$module = $modules; // crmv@5946
	$block = $modules; // crmv@5946
	
	$sessionid = $_SESSION['customer_sessionid'];
	$params = array('id' => "$customerid", 'block'=>"$block",'sessionid'=>"$sessionid",'onlymine'=>$onlymine);
	// 
	$result = $client->call('get_list_values', $params, $Server_Path, $Server_Path);

	// Check for Authorization
	if (count($result) == 1 && $result[0] == "#NOT AUTHORIZED#") {
		$smarty->display('NotAuthorized.tpl');
		die();
	}
	$smarty->assign('MODULE',$module);
	$smarty->assign('FIELDLISTVIEW',$result);//getblock_fieldlistview($result,$block));
	
	if(!empty($result)){
		$smarty->assign('ENTRIES2',$result[1][$modules]['data']);
	}
}
$smarty->display($block.'List.tpl');
?>