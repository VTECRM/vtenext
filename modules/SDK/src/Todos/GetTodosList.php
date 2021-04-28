<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@28295
global $current_user;
require_once('modules/SDK/src/Todos/Utils.php');
echo getHtmlTodosList($current_user->id,$_REQUEST['mode']);
//crmv@28295e
?>