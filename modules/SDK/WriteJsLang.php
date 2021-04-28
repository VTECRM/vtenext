<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$language = vtlib_purify($_REQUEST['language']);
$mod_strings = Zend_Json::decode(vtlib_purify($_REQUEST['params']));
insert_language('ALERT_ARR',$language,$mod_strings);
exit;
?>