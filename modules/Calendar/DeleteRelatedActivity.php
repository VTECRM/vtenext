<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if (isPermitted('Calendar','Delete',$_REQUEST['related_id'] == 'no')) {
	echo "<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>";	
	echo "<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>";
	echo "<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src='". resourcever('denied.gif') . "' ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>$app_strings[LBL_PERMISSION]</span></td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>			   	
		<a href='javascript:window.history.back();'>$app_strings[LBL_GO_BACK]</a><br>								   						     </td>
		</tr>
		</tbody></table> 
		</div>";
	echo "</td></tr></table>";
	die;
}

require_once('modules/Calendar/Activity.php');

$focus = CRMEntity::getInstance('Activity');

//Added to fix 4600
$url = getBasic_Advance_SearchURL();

if(!isset($_REQUEST['record']))
	die($mod_strings['ERR_DELETE_RECORD']);

global $adb,$table_prefix;
if ($_REQUEST['type'] == 'children')
	$adb->pquery('delete from '.$table_prefix.'_seactivityrel where crmid = ? and activityid = ?',array($_REQUEST['record'],$_REQUEST['related_id']));
elseif ($_REQUEST['type'] == 'fathers')
	$adb->pquery('delete from '.$table_prefix.'_seactivityrel where crmid = ? and activityid = ?',array($_REQUEST['related_id'],$_REQUEST['record']));

header("Location: index.php?module=Calendar&action=DetailView&record=".$_REQUEST['record']);
//crmv@17001e
?>