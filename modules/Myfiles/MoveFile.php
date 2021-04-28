<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/logging.php');
require_once('include/database/PearDatabase.php');
global $current_user;
if($current_user->is_admin != 'on')
{
	echo 'NOT_PERMITTED';
	die;	
}
else
{
	$new_folderid = $_REQUEST['folderid'];
	
	if(isset($_REQUEST['idlist']) && $_REQUEST['idlist']!= '')
	{
		$id_array = Array();
		$id_array = explode(';',$_REQUEST['idlist']);
		for($i = 0;$i < count($id_array)-1;$i++)
		{
			ChangeFolder($id_array[$i],$new_folderid);	
		}
		header("Location: index.php?action=MyfilesAjax&file=ListView&mode=ajax&module=Myfiles");
	}
}

/** To Change the Myfiles to another folder
  * @param $recordid -- The file id
  * @param $new_folderid -- The folderid to which the file to be moved
  * @returns nothing 
 */
function ChangeFolder($recordid,$new_folderid)
{
	global $adb;
	global $table_prefix;
	$sql="update ".$table_prefix."_myfiles set folderid=".$new_folderid." where myfilesid in (".$recordid.")";
	$res=$adb->pquery($sql,array());
}
?>