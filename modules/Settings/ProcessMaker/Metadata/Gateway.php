<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 */

global $adb, $table_prefix, $current_language, $app_strings;

$show_required2go_check = false;
$groups = $PMUtils->getGatewayConditions($id,$elementid,$vte_metadata_arr,$show_required2go_check);
if (empty($groups)) unset($buttons['save']);
$smarty->assign("CONDITION_GROUPS", $groups);
$smarty->assign("SHOW_REQUIRED_CHECK", $show_required2go_check);

$outgoing = $PMUtils->getOutgoing($id,$elementid);
$outgoings = array(''=>getTranslatedString('LBL_NONE'));
foreach($outgoing as $out) {
	$type = $out['shape']['type'];
	$text = $out['shape']['text'];
	$text_conn = $out['connection']['text'];
	$title = $type;
	if (!empty($text)) $title .= ': '.$text;
	if (!empty($text_conn)) $title .= " ($text_conn)";
	
	$outgoings[$out['shape']['id']] = $title;
}
$smarty->assign("OUTGOINGS", $outgoings);
?>