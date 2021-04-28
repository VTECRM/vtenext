<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('include/utils/utils.php');
global $current_language;
echo Zend_Json::encode(get_lang_strings('ALERT_ARR',$current_language));
exit;
?>