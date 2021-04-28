<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user,$current_language;
require_once('modules/SDK/src/Events/Utils.php');
echo getHtmlEventList($current_user->id,$_REQUEST['mode'],$_REQUEST['year'],$_REQUEST['month'],$_REQUEST['day']);
?>