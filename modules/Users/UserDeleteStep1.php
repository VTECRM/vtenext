<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $current_user;

// crmv@184240
if (!is_admin($current_user)) {
	echo getTranslatedString('LBL_UNAUTHORIZED_ACCESS', 'Users');
	die();
}
// crmv@184240e

global $mod_strings, $app_strings;
global $theme;
$theme_path="themes/".$theme."/";

$delete_user_id = $_REQUEST['record'];
$delete_user_name = getUserName($delete_user_id);

$output ='<div id="DeleteLay" class="crmvDiv">
<div class="closebutton" onclick="document.getElementById(\'DeleteLay\').style.display=\'none\'"></div>
<form name="newProfileForm" action="index.php" onsubmit="VteJS_DialogBox.block();">
<input type="hidden" name="module" value="Users">
<input type="hidden" name="action" value="DeleteUser">
<input type="hidden" name="delete_user_id" value="'.$delete_user_id.'">	
<table border=0 cellspacing=0 cellpadding=5 width=100% class=level3Bg>
<tr>
	<td align="left"><b>'.$mod_strings['LBL_DELETE'].' '.$mod_strings['LBL_USER'].'</b></td>
	<td align=right>
		<input type="button" onclick="transferUser('.$delete_user_id.')" name="Delete" value="'.$mod_strings['LBL_DELETE'].'" class="crmbutton small delete">
	</td>
</tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% align=center> 
<tr>	
	<td class="small">
	<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
	<tr>
	
		<td width="50%" class="cellLabel small">'.$mod_strings['LBL_DELETE_USER'].'</td>
		<td width="50%" class="cellText small">'.$delete_user_name.'</td>
	</tr>
	<tr>
		<td align="left" class="cellLabel small" nowrap>'.$mod_strings['LBL_TRANSFER_USER'].'</td>
		<td align="left" class="cellText small">';
           
		$output.='<select class="select" name="transfer_user_id" id="transfer_user_id">';
	     
		global $adb;	
		global $table_prefix;
         	$sql = "select * from ".$table_prefix."_users";
	        $result = $adb->pquery($sql, array());
         	$temprow = $adb->fetch_array($result);
         	do
         	{
         		$user_name=$temprow["user_name"];
			$user_id=$temprow["id"];
		    	if($delete_user_id 	!= $user_id)
		    	{	 
            			$output.='<option value="'.$user_id.'">'.$user_name.'</option>';
		    	}	
         	}while($temprow = $adb->fetch_array($result));

		$output.='</td>
	</tr>
	
	</table>
	</td>
</tr>
</table>
</form></div>';

echo $output;
?>