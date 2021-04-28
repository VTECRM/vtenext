<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;
include_once('modules/Calendar/calendarLayout.php');
//crmv@36555
$cal_class = CRMEntity::getInstance('Calendar');
echo crmvGetUserAssignedToHTML($cal_class->getShownUserId($current_user->id,true),"events",true);
//crmv@36555 e
?>