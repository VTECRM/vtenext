<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@30447
global $table_prefix;

//crmv@175759
$record = intval($_REQUEST['record']);
if (empty($record)) { // create but not duplicate
	if (empty($_REQUEST['projectplanid']) && !empty($_REQUEST['projecttaskid'])) {
		// get projectplan
		$res = $adb->pquery("select projectid from {$table_prefix}_projecttask
		inner join {$table_prefix}_crmentity on crmid = projectid
		where deleted = 0 and projecttaskid = ?", array(intval($_REQUEST['projecttaskid'])));
		if ($res && $adb->num_rows($res) > 0) {
			$projid = $adb->query_result($res, 0, 'projectid');
			if (!empty($projid)) $_REQUEST['projectplanid'] = $projid;
		}
	}
}
//crmv@175759e

require_once('modules/VteCore/EditView.php');

if(isset($_REQUEST['record']) && $_REQUEST['record'] !='')
{
	$focus->id = $_REQUEST['record'];
	$focus->mode = 'edit';
	$focus->retrieve_entity_info($_REQUEST['record'],"HelpDesk");
	$focus->name=$focus->column_fields['ticket_title'];
}

if($isduplicate == 'true') {
	$focus->id = '';
	$focus->mode = '';
}

$smarty->assign("ID", $focus->id);
$smarty->assign("OLD_ID", $old_id );
if($focus->mode == 'edit')
{
	$smarty->assign("UPDATEINFO",updateInfo($focus->id));
	$smarty->assign("MODE", $focus->mode);
	$smarty->assign("OLDSMOWNERID", $focus->column_fields['assigned_user_id']);
  
}

if($_REQUEST['record'] != '')
{
	//Added to display the ticket comments information
	$smarty->assign("COMMENT_BLOCK",$focus->getCommentInformation($_REQUEST['record']));
}

$smarty->display("salesEditView.tpl");