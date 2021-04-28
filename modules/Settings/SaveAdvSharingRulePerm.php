<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;
$sharing_module=$_REQUEST['sharing_module'];
$tabid=getTabid($sharing_module);
$sharedby = explode('::',$_REQUEST[$sharing_module.'_share']);
$sharedto = explode('::',$_REQUEST[$sharing_module.'_access']);
$entityid = $_REQUEST['record'];
if (!isset($_REQUEST['entity'])) $entity='Users';
	else $entity = $_REQUEST['entity'];

$share_entity_type = $sharedby[0];
$to_entity_type = $sharedto[0];

$share_entity_id= $sharedby[1];
$to_entity_id=$sharedto[1];

$module_sharing_access=$_REQUEST['share_memberType'];

$mode=$_REQUEST['mode'];


$relatedShareModuleArr=getRelatedAdvSharingModules($tabid);
if($mode == 'create')
{
	$shareId=$_REQUEST[$sharing_module.'_share'];
	addAdvSharingRulePerm($shareId,$sharing_module,$module_sharing_access,$entityid,$entity);

	//Adding the Related ModulePermission Sharing
	foreach($relatedShareModuleArr as $reltabid=>$ds_rm_id)
	{
		$reltabname=getTabModuleName($reltabid);
		$relSharePermission=$_REQUEST[$reltabname.'_accessopt'];	
		addRelatedModuleAdvSharingPerm($shareId,$reltabid,$relSharePermission,$entityid,$entity);	
	}
	
}
elseif($mode == 'edit')
{
	$shareId=$_REQUEST['shareId'];
	updateAdvSharingRulePerm($shareId,$sharing_module,$module_sharing_access,$entityid,$entity);
	//Adding the Related ModulePermission Sharing
	foreach($relatedShareModuleArr as $reltabid=>$ds_rm_id)
	{
		$reltabname=getTabModuleName($reltabid);
		$relSharePermission=$_REQUEST[$reltabname.'_accessopt'];	
		updateRelatedModuleAdvSharingRulePerm($shareId,$reltabid,$relSharePermission,$entityid,$entity);	
	}	
}
require_once('modules/Users/CreateUserPrivilegeFile.php');
createUserSharingPrivilegesfile($entityid);

$loc = "Location: index.php?action=DetailView&module=Users&parenttab=Settings&record=".$entityid."&adv_sharing=true";
header($loc);
?>