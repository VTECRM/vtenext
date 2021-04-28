<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

global $image_path, $theme, $adb, $current_user, $table_prefix;

$theme_path = "themes/{$theme}/";
$image_path = $theme_path . "images/";

$log = LoggerManager::getLogger('Activity_Reminder');

$cbaction = $_REQUEST['cbaction'];
$cbmodule = $_REQUEST['cbmodule'];
$cbrecord = $_REQUEST['cbrecord'];
$cbparams = $_REQUEST['cbparams']; // crmv@98866

// crmv@103354
$interval = getSingleFieldValue($table_prefix."_users", 'reminder_interval', 'id', $current_user->id);
$intervalInSeconds = $interval != 'None' ? intval(ConvertToMinutes($interval)*60) : null;
// crmv@103354e

if($cbaction == 'POSTPONE') {
    if(!empty($cbmodule) && !empty($cbrecord)) { // crmv@98866
        $reminderid = $_REQUEST['cbreminderid'];
        if(!empty($reminderid) ) {
            // crmv@103354
            VteSession::remove('next_reminder_time');
            if ($intervalInSeconds) {
                VteSession::set('next_reminder_time', time() + $intervalInSeconds);
            }
            // crmv@103354e
            $reminder_query = "UPDATE ".$table_prefix."_act_reminder_popup set status = 0 WHERE reminderid = ? AND semodule = ? AND recordid = ?";
            $adb->pquery($reminder_query, [$reminderid, $cbmodule, $cbrecord]);
            echo ":#:SUCCESS";
        } else {
            echo ":#:FAILURE";
        }
        // crmv@98866
    } else if (!empty($cbparams)) {
        $cbparams = json_decode($cbparams, true);
        if (is_array($cbparams) && !empty($cbparams)) {
            // crmv@103354
            VteSession::remove('next_reminder_time');
            if ($intervalInSeconds) {
                VteSession::set('next_reminder_time', time() + $intervalInSeconds);
            }
            // crmv@103354e
            foreach ($cbparams as $cbparam) {
                $module = $cbparam['module'];
                $record = $cbparam['record'];
                $reminderid = $cbparam['reminderid'];
                if(!empty($reminderid)) {
                    $reminder_query = "UPDATE ".$table_prefix."_act_reminder_popup set status = 0 WHERE reminderid = ? AND semodule = ? AND recordid = ?";
                    $adb->pquery($reminder_query, [$reminderid, $module, $record]);
                } else {
                    echo ":#:FAILURE";
                    exit();
                }
            }
            echo ":#:SUCCESS";
        }
    }
    // crmv@98866 end
}
else if($cbaction == 'CLOSE') {
    if(!empty($cbmodule) && !empty($cbrecord)) { // crmv@98866
        $reminderid = $_REQUEST['cbreminderid'];
        if(!empty($reminderid) ) {
            $reminder_query = "UPDATE ".$table_prefix."_act_reminder_popup set status = 1 WHERE reminderid = ? AND semodule = ? AND recordid = ?";
            $adb->pquery($reminder_query, [$reminderid, $cbmodule, $cbrecord]);
            echo ":#:SUCCESS";
        } else {
            echo ":#:FAILURE";
        }
        // crmv@98866
    } else if (!empty($cbparams)) {
        $cbparams = json_decode($cbparams, true);
        if (is_array($cbparams) && !empty($cbparams)) {
            foreach ($cbparams as $cbparam) {
                $module = $cbparam['module'];
                $record = $cbparam['record'];
                $reminderid = $cbparam['reminderid'];
                if(!empty($reminderid)) {
                    $reminder_query = "UPDATE ".$table_prefix."_act_reminder_popup set status = 1 WHERE reminderid = ? AND semodule = ? AND recordid = ?";
                    $adb->pquery($reminder_query, [$reminderid, $module, $record]);
                } else {
                    echo ":#:FAILURE";
                    exit();
                }
            }
            echo ":#:SUCCESS";
        }
    }
    // crmv@98866 end
}