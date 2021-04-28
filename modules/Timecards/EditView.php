<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@30447 crmv@104568 */
 
require_once('modules/VteCore/EditView.php');

global $adb, $table_prefix;

if($disp_view != 'edit_view') {
    if ($isduplicate != 'true') { // crmv@191459
        $focus->column_fields['tcunits']='1';
        $focus->column_fields['worktime']='00:05';	//crmv@14132
        $focus->column_fields['timecardtypes']='Comment';
        $focus->column_fields['newtc']=0;	//crmv@14132
        //crmv@19396
		if ($_REQUEST['ticket_id'] != '') {
			global $adb;
			$res = $adb->pquery("select status from {$table_prefix}_troubletickets where ticketid = ?",array($_REQUEST['ticket_id']));
			if ($res && $adb->num_rows($res)>0)
				$focus->column_fields['ticketstatus']=$adb->query_result($res,0,'status');
			$focus->column_fields['ticket_id']=$_REQUEST['ticket_id'];
		}
		//crmv@19396e
		
		$smarty->assign('BLOCKS', getBlocks($currentModule, $disp_view, $focus->mode, $focus->column_fields));
    }
}

$smarty->assign("UMOD", array('LBL_CHANGE'=>$mod_strings['LBL_CHANGE']));
$smarty->assign("FCKEDITOR_DISPLAY",$FCKEDITOR_DISPLAY);

$smarty->display('salesEditView.tpl');