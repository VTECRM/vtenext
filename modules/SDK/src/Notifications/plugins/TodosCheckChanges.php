<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@32280
global $current_user;
require_once('modules/SDK/src/Todos/Utils.php');
getTodosList($current_user->id,'all',$count, true); // crmv@36871
if ($count > 0) {
	echo $count;
} else {
	echo 0;
}
//crmv@32280e
?>