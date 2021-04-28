<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb,$table_prefix;
$idlist = $_REQUEST['idlist'];

//echo '<pre>'; print_r($_REQUEST['entityid']); echo '</pre>';

if(isset($_REQUEST['idlist']) && $_REQUEST['idlist'] != '')
{
	//split the strings & store in an array
	$storearray = explode (";",$idlist);
	foreach($storearray as $id)
	{
		if($id != '')
		{
			$record = $_REQUEST["parentid"];
			$sql = "insert into ".$table_prefix."_seactivityrel values (?,?)";
			$adb->pquery($sql, array($id, $_REQUEST["parentid"]));
		}
	}
	header("Location: index.php?action=CallRelatedList&module=Sms&record=".$record);
}
elseif (isset($_REQUEST['entityid']) && $_REQUEST['entityid'] != '')
{
	$record = $_REQUEST["parid"];
	$sql = "insert into ".$table_prefix."_seactivityrel values (?,?)";
	$adb->pquery($sql, array($_REQUEST["entityid"], $_REQUEST["parid"]));
	header("Location: index.php?action=CallRelatedList&module=Sms&record=".$record);
}



if(isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '')
{
	$record = $_REQUEST['record'];
	$sql = "insert into ".$table_prefix."_salesmanactivityrel values (?,?)";
	$adb->pquery($sql, array($_REQUEST["user_id"], $_REQUEST["record"]));
	header("Location: index.php?action=CallRelatedList&module=Sms&record=".$record);
}