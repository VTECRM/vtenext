<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@88981 */

include_once('include/utils/utils.php');
global $adb,$current_user,$currentModule,$table_prefix, $default_charset;

$record = intval($_REQUEST['record']);
$contentid = intval($_REQUEST['contentid']);

$focus = CRMEntity::getInstance($currentModule);
$focus->retrieve_entity_info($record,$currentModule);

$messagesid = 0;
$error = '';
$success = $focus->parseEML($contentid, $messagesid, $error);

$return_array = array('success'=>$success,'messageid'=>$messagesid, 'error' => $error);
echo Zend_Json::encode($return_array);
die();