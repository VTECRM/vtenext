<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$block = 'Contacts';

global $result;
global $Server_Path;

$customerid = $_SESSION ['customer_id'];
$sessionid = $_SESSION ['customer_sessionid'];
$id = portal_purify($_REQUEST['id']);

if($id != '')
{
	//Get the Basic Information
	$params = array('id' => "$id", 'block'=>"$block", 'contactid'=>"$customerid",'sessionid'=>"$sessionid",'language'=>getPortalCurrentLanguage());	//crmv@slowear
	
	$result = $client->call('unsubscribe_contact', $params, $Server_Path, $Server_Path);
	
	$smarty->assign('CUSTOMERID',$customerid);
	$smarty->assign('UNSUBSCRIBE',$result);
	$smarty->display('unsubscribe.tpl');

}

?>