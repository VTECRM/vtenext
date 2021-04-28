<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97575 crmv@136524 crmv@161211 */
$smarty->assign("MODE",$type);
$smarty->assign("HEADER", $PMUtils->getHeaderList(true));
$skip_ids = array($id);
$sub_processes = $PMUtils->getSubprocesses($id);
if (!empty($sub_processes)) {
	foreach($sub_processes as $sub_process) {
		$skip_ids[] = $sub_process['subprocess'];
	}
}
$skip_ids = array_diff($skip_ids,array($vte_metadata_arr['subprocess']));
$smarty->assign("LIST", $PMUtils->getList(true,$skip_ids,$vte_metadata_arr['subprocess']));
$smarty->assign("SUBPROCESS", $vte_metadata_arr['subprocess']);
//crmv@185705
global $current_language;
$smarty->assign("CURRENT_LANGUAGE", $current_language);
//crmv@185705e
$sub_template = 'Settings/ProcessMaker/List.tpl';