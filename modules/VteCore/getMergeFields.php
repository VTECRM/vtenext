<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@69201
$module = vtlib_purify($_REQUEST['forModule']);

$fields = array();
$fields[] = getMergeFields($module,"available_fields");
$fields[] = getMergeFields($module,"fileds_to_merge");

echo Zend_Json::encode($fields);
//crmv@69201e
?>