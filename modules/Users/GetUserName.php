<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@35153
$record = vtlib_purify($_REQUEST['record']);
echo getUserName($record);
exit;
//crmv@35153e
?>