<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$plugin = vtlib_purify($_REQUEST['plugin']);
include('modules/SDK/src/Notifications/plugins/'.$plugin.'DeleteChanges.php');
exit;
?>