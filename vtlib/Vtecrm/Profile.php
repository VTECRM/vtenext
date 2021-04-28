<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include_once('vtlib/Vtecrm/Utils.php');

/* crmv@198038 */

/* crmv@208173 */

/**
 * Provides API to work with CRM Profile
 * @package vtlib
 */
class Vtecrm_Profile {
    /**
     * Initialize profile setup for Field
     * @param Vtecrm_Field Instance of the field
     * @access private
     */
    static function initForField($fieldInstance) {
        global $adb, $table_prefix;

        // Allow field access to all
        $adb->pquery("INSERT INTO {$table_prefix}_def_org_field (tabid, fieldid, visible, readonly) VALUES(?,?,?,?)",
            Array($fieldInstance->getModuleId(), $fieldInstance->id, '0', '1'));

        $profileIds = self::getAllIds();
        foreach($profileIds as $profileId) {
            $adb->pquery("INSERT INTO {$table_prefix}_profile2field (profileid, tabid, fieldid, visible, readonly) VALUES(?,?,?,?,?)",
                Array($profileId, $fieldInstance->getModuleId(), $fieldInstance->id, '0', '1'));
        }
    }

    /**
     * Delete profile information related with field.
     * @param Vtecrm_Field Instance of the field
     * @access private
     */
    static function deleteForField($fieldInstance) {
        global $adb, $table_prefix;

        $adb->pquery("DELETE FROM ".$table_prefix."_def_org_field WHERE fieldid=?", Array($fieldInstance->id));
        $adb->pquery("DELETE FROM ".$table_prefix."_profile2field WHERE fieldid=?", Array($fieldInstance->id));
    }

    /**
     * Get all the existing profile ids
     * @access private
     */
    static function getAllIds() {
        global $adb, $table_prefix;
        $profileIds = [];
        $result = $adb->query('SELECT profileid FROM '.$table_prefix.'_profile');
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $profileIds[] = $adb->query_result($result, $index, 'profileid');
        }
        return $profileIds;
    }

    /**
     * Initialize profile setup for the module
     * @param Vtecrm_Module Instance of module
     * @access private
     */
    static function initForModule($moduleInstance) {
        global $adb, $table_prefix;

        $actionids = [];
        $result = $adb->query("SELECT actionid from {$table_prefix}_actionmapping WHERE actionname IN 
			('Save','EditView','Delete','index','DetailView')");
        /*
         * NOTE: Other actionname (actionid >= 5) is considered as utility (tools) for a profile.
         * Gather all the actionid for associating to profile.
         */
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $actionids[] = $adb->query_result($result, $index, 'actionid');
        }

        $profileids = self::getAllIds();

        foreach($profileids as $profileid) {
            $adb->pquery("INSERT INTO {$table_prefix}_profile2tab (profileid, tabid, permissions) VALUES (?,?,?)",
                Array($profileid, $moduleInstance->id, 0));

            if($moduleInstance->isentitytype) {
                foreach($actionids as $actionid) {
                    $adb->pquery(
                        "INSERT INTO {$table_prefix}_profile2standardperm (profileid, tabid, Operation, permissions) VALUES(?,?,?,?)",
                        Array($profileid, $moduleInstance->id, $actionid, 0));
                }
            }
        }
        self::log("Initializing module permissions ... DONE");
    }

    /**
     * Delete profile setup of the module
     * @param Vtecrm_Module Instance of module
     * @access private
     */
    static function deleteForModule($moduleInstance) {
        global $adb, $table_prefix;
        $adb->pquery("DELETE FROM {$table_prefix}_profile2tab WHERE tabid=?", Array($moduleInstance->id));
        $adb->pquery("DELETE FROM {$table_prefix}_profile2standardperm WHERE tabid=?", Array($moduleInstance->id));
    }


    /**
     * Helper function to log messages
     * @param String Message to log
     * @param Boolean true appends linebreak, false to avoid it
     * @access private
     */
    static function log($message, $delimit=true) {
        Vtecrm_Utils::Log($message, $delimit);
    }
}
