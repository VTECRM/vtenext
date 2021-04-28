<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97566 */

($vte_metadata_arr['date_start'] == '') ? $date_start_disp_value = getNewDisplayDate() : $date_start_disp_value = getDisplayDate(substr($vte_metadata_arr['date_start'],0,10));
$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
$smarty->assign('START_DATE_FLDVALUE',array($date_start_disp_value=>''));
$smarty->assign('START_DATE_SECONDVALUE',array($date_format=>getTranslatedString($current_user->date_format,'Users')));

($vte_metadata_arr['starthr'] == '') ? $starthr = date('H') : $starthr = $vte_metadata_arr['starthr']; 
($vte_metadata_arr['startmin'] == '') ? $startmin = date('i') : $startmin = $vte_metadata_arr['startmin']; 
$smarty->assign('START_TIME_COMBO',getTimeCombo('am','start',$starthr,$startmin,'',true,false));

($vte_metadata_arr['date_end'] == '') ? $date_end_disp_value = '' : $date_end_disp_value = getDisplayDate(substr($vte_metadata_arr['date_end'],0,10));
$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
$smarty->assign('END_DATE_FLDVALUE',array($date_end_disp_value=>''));
$smarty->assign('END_DATE_SECONDVALUE',array($date_format=>getTranslatedString($current_user->date_format,'Users')));

($vte_metadata_arr['endhr'] == '') ? $endhr = 0 : $endhr = $vte_metadata_arr['endhr']; 
($vte_metadata_arr['endmin'] == '') ? $endmin = 0 : $endmin = $vte_metadata_arr['endmin']; 
$smarty->assign('END_TIME_COMBO',getTimeCombo('am','end',$endhr,$endmin,'',true,false));

global $current_language;
$jqCronLang = substr($current_language,0,strpos($current_language,'_'));
if (!file_exists("modules/Settings/ProcessMaker/thirdparty/jqcron/src/jqCron.$jqCronLang.js")) $jqCronLang = 'en';
$smarty->assign('JQCRONLANG',$jqCronLang);