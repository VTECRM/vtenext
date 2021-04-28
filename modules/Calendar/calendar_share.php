<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@20209 crmv@36511 crmv@3085m crmv@187823 crmv@181170 */

global $mode;

$record = intval($_REQUEST['record']);

$cal_class = CRMEntity::getInstance('Calendar');
echo $cal_class->getCalendarShareContent($record, $mode);