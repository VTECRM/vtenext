<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailScannerRule.php');
require_once('modules/Settings/MailScanner/core/MailScannerAction.php');

global $app_strings, $mod_strings, $currentModule, $theme, $current_language;

$scannername = $_REQUEST['scannername'];
$scannerruleid= $_REQUEST['ruleid'];
$scanneractionid=$_REQUEST['actionid'];

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
($_REQUEST['compare_parentid'] == 'on') ? $scannerrule->compare_parentid = 1 : $scannerrule->compare_parentid = 0;	//crmv@78745
($scannerrule->subjectop == 'Regex' && $_REQUEST['rule_actiontext'] == 'UPDATE,HelpDesk,SUBJECT') ? $scannerrule->match_field = $_REQUEST['match_field'] : $scannerrule->match_field = '';	//crmv@81643

$scannerrule->update();

$scannerrule->updateAction($scanneractionid, $_REQUEST['rule_actiontext']);

include('modules/Settings/MailScanner/MailScannerRule.php');