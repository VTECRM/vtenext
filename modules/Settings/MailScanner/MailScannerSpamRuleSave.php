<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27618
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailScannerRule.php');
require_once('modules/Settings/MailScanner/core/MailScannerAction.php');

global $adb, $app_strings, $mod_strings, $currentModule, $theme, $current_language,$table_prefix;

$scannername = $_REQUEST['scannername'];
$scannerruleid= $_REQUEST['ruleid'];
$scanneractionid=$_REQUEST['actionid'];
$prev_action=$_REQUEST['prev_action'];

$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843
$scannerrule = new Vtenext_MailScannerRule($scannerruleid);//crmv@207843

$scannerrule->scannerid   = $scannerinfo->scannerid;
$scannerrule->fromaddress = $_REQUEST['rule_from'];
$scannerrule->toaddress = $_REQUEST['rule_to'];
$scannerrule->subjectop = $_REQUEST['rule_subjectop'];
$scannerrule->subject   = $_REQUEST['rule_subject'];
$scannerrule->bodyop    = $_REQUEST['rule_bodyop'];
$scannerrule->body      = $_REQUEST['rule_body'];
$scannerrule->matchusing= $_REQUEST['rule_matchusing'];

$scannerrule->update();

$result = $adb->pquery('SELECT ruleid FROM '.$table_prefix.'_mailscanner_ruleactions WHERE actionid = ?', array($prev_action));//crmv@208173
if (!$result || $adb->num_rows($result) == 0) exit;
$prev_scannerruleid = $adb->query_result($result,0,'ruleid');
$result = $adb->pquery('SELECT sequence FROM '.$table_prefix.'_mailscanner_rules WHERE ruleid = ?', array($prev_scannerruleid));//crmv@208173
if (!$result || $adb->num_rows($result) == 0) exit;
$prev_scannerrule_sequence = $adb->query_result($result,0,'sequence');

$result = $adb->pquery('UPDATE '.$table_prefix.'_mailscanner_rules SET sequence = sequence + 1 WHERE sequence >= ? ORDER BY sequence',array($prev_scannerrule_sequence));	//TODO: verificare se � multi db
$adb->pquery('update '.$table_prefix.'_mailscanner_rules set sequence = ? where ruleid = ?',array($prev_scannerrule_sequence,$scannerrule->ruleid));

$scannerrule->updateAction($scanneractionid, $_REQUEST['rule_actiontext']);

echo '<script>parent.closePopup();</script>';
//crmv@27618e
?>