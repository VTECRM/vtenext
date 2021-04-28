<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97566 */

//crmv@185361
$smarty->assign("TIMER_TYPE", $type);
$error_string = '';
$attach_father = $PMUtils->getAttacherFather($id,$elementid);
if (!empty($attach_father)) {
	$attach_structure = $PMUtils->getStructureElementInfo($id,$attach_father,'shapes');
	$type = $PMUtils->formatType($attach_structure['type']);
	if ($type != 'Task' || $PMUtils->isStartTask($id,$attach_father)) {
		unset($buttons['save']);
		$error_string = getTranslatedString('LBL_PM_TIMER_ERROR_NOT_SUPPORTED','Settings');
	}
}
//crmv@185361e

$outgoing = $PMUtils->getOutgoing($id,$elementid);
if (!empty($outgoing)) {
	$type = $outgoing[0]['shape']['type'];
	$text = $outgoing[0]['shape']['text'];
	$text_conn = $outgoing[0]['connection']['text'];
	$next_title = $type;
	if (!empty($text)) $next_title .= ': '.$text;
	if (!empty($text_conn)) $next_title .= " ($text_conn)";
//crmv@185361
} elseif (empty($error_string)) {
	unset($buttons['save']);
	$error_string = getTranslatedString('LBL_PM_ERROR_OUTGOING_MISSING','Settings');
}
//crmv@185361e

$timerOptions = array(
	array(0,13,'months',getTranslatedString('LBL_MONTHS'),$vte_metadata_arr['months']), //crmv@179893
	array(0,32,'days',getTranslatedString('LBL_DAYS'),$vte_metadata_arr['days']),
	array(0,24,'hours',getTranslatedString('LBL_HOURS'),$vte_metadata_arr['hours']),
	array(0,60,'min',getTranslatedString('LBL_MINUTES'),$vte_metadata_arr['min'])
);
$smarty->assign("TIMEROPTIONS", $timerOptions);
//crmv@182148
global $app_strings, $current_user;
$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
($vte_metadata_arr['trigger_date_type'] == 'date' && !empty($vte_metadata_arr['trigger_date_value'])) ? $trigger_date_value = getDisplayDate(substr($vte_metadata_arr['trigger_date_value'],0,10)) : $trigger_date_value = '';
($vte_metadata_arr['trigger_date_type'] == 'date' && !empty($vte_metadata_arr['trigger_hour_value'])) ? $trigger_hour_value = $vte_metadata_arr['trigger_hour_value'] : $trigger_hour_value = '';
($vte_metadata_arr['trigger_date_type'] == 'other' && !empty($vte_metadata_arr['trigger_other_value'])) ? $trigger_other_value = $vte_metadata_arr['trigger_other_value'] : $trigger_other_value = '';
$smarty->assign("TIMERTRIGGER", array(
	'direction' => $vte_metadata_arr['trigger_direction'],
	'date_type' => $vte_metadata_arr['trigger_date_type'],
	'date_ui' => array('fldvalue'=>array($trigger_date_value=>''),'secondvalue'=>array($date_format=>getTranslatedString($current_user->date_format,'Users'))),
	'hour_ui' => array('fldvalue'=>$trigger_hour_value),
	'other_ui' => array('fldvalue'=>$trigger_other_value),
));
$smarty->assign('SDK_CUSTOM_FUNCTIONS',SDK::getFormattedProcessMakerFieldActions());
//crmv@182148e

$smarty->assign("START_LABEL", getTranslatedString('LBL_PM_WAIT','Settings'));
$smarty->assign("END_LABEL", getTranslatedString('LBL_PM_TO_GO_TO_NEXT_STEP','Settings'));
$smarty->assign("NEXT_ELEMENT", $next_title);
$smarty->assign("ERROR_STRING", $error_string); //crmv@185361