<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once('include/logging.php');
$cal_log =& LoggerManager::getLogger('calendar');
global $mod_strings;

// crmv@189225
$inIcal = $_REQUEST['useical'] == 'true' && isset($_REQUEST['icalid']) && $_REQUEST['from_module'] == 'Messages' && $_REQUEST['from_crmid'] > 0;
define("IN_ICAL", $inIcal);
// crmv@189225e

include ('modules/Calendar/new_calendar.php');

?>