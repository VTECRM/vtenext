<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@90004 */

global $result;
global $client;

$smarty = new VTECRM_Smarty();
$smarty->assign('TITLE',getTranslatedString("LBL_NOTES_INFORMATION"));

$onlymine=$_REQUEST['onlymine'];
if($onlymine == 'true') {
    $mine_selected = 'selected';
    $all_selected = '';
} else {
    $mine_selected = '';
    $all_selected = 'selected';
}


// echo '<tr>
// 	 			<td><span class="lvtHeaderText">'.getTranslatedString("LBL_NOTES_INFORMATION").'</span</td>';    
	$allow_all = $client->call('show_all',array('Documents'),$Server_Path, $Server_Path);
	
	    if($allow_all == 'true'){
	    	$smarty->assign('ALLOW_ALL',$allow_all);
	    	$smarty->assign('MINE_SELECTED',$mine_selected);
	    	$smarty->assign('ALL_SELECTED',$all_selected);
	    	
// 	      		echo '<td align="right" style="padding-right:50px;"> <b>'.getTranslatedString('SHOW').'</b>&nbsp; <select name="list_type" onchange="getList(this, \'Documents\');">
// 	 			<option value="mine" '. $mine_selected .'>'.getTranslatedString('MINE').'</option>
// 				<option value="all"'. $all_selected .'>'.getTranslatedString('ALL').'</option>
// 				</select></td></tr>';
	    		}
	      		
// 	      		echo '<tr><td colspan="2"><hr noshade="noshade" size="1" width="100%" align="left">
// 	      		<table width="100%"  border="0" cellspacing="0" cellpadding="5" align="center">';

if ($customerid != '' )
{
	$block = "Documents";
	
	$params = array('id' => "$customerid", 'block'=>"$block",'sessionid'=>$sessionid,'onlymine'=>$onlymine);
	$result = $client->call('get_folder', $params, $Server_Path, $Server_Path);
	
	$smarty->assign('MODULE',$module);

	if(!empty($result)){
	 	$smarty->assign('FIELDLISTVIEWFOLDER',$result);
	}
}

$smarty->display('DocumentsListFolder.tpl');
