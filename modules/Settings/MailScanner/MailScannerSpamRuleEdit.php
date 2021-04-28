<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27618
global $app_strings, $small_page_title, $small_page_title,$table_prefix;
$small_page_title = getTranslatedString('LBL_RULE','Settings').' '.getTranslatedString('LBL_MAIL_SCANNER','Settings');
$small_page_buttons = '
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="100%" style="padding:5px"></td>
 	<td align="right" style="padding: 5px;" nowrap>
 		<input type="button" class="crmbutton small save" value="'.$app_strings['LBL_SAVE_LABEL'].'" onclick="document.form.submit();"/>
 	</td>
 </tr>
 </table>
';
include('themes/SmallHeader.php');

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
global $adb, $mod_strings, $currentModule, $theme, $current_language;

$record = $_REQUEST['record'];
$module = $_REQUEST['rel_module'];
$focus = CRMEntity::getInstance($module);
$focus->retrieve_entity_info($record,$module);
$prev_action = $focus->column_fields['mailscanner_action'];

$result = $adb->query('SELECT scannername FROM '.$table_prefix.'_mailscanner_actions
						INNER JOIN '.$table_prefix.'_mailscanner ON '.$table_prefix.'_mailscanner_actions.scannerid = '.$table_prefix.'_mailscanner.scannerid
						WHERE '.$table_prefix.'_mailscanner_actions.actionid = '.$prev_action);
if (!$result || $adb->num_rows($result) == 0) exit;
$scannername = $adb->query_result($result,0,'scannername');

$smarty = new VteSmarty();
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH","themes/$theme/images/");

$smarty->assign("PREV_ACTION", $prev_action);
$smarty->assign("EMAIL_FROM", $focus->column_fields['email_from']);
$smarty->assign("TICKET_TITLE", $focus->column_fields['ticket_title']);
$scannerruleid = '';

$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843
$scannerrule = new Vtenext_MailScannerRule($scannerruleid);//crmv@207843

$smarty->assign("SCANNERINFO", $scannerinfo->getAsMap());
$smarty->assign("SCANNERRULE", $scannerrule);

$smarty->display('MailScanner/MailScannerSpamRuleEdit.tpl');

include('themes/SmallFooter.php');
//crmv@27618e
?>