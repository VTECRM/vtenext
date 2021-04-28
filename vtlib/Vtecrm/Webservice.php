<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');

/**
 * Provides API to work with CRM Webservice
 * @package vtlib
 */
class Vtecrm_Webservice {

    /**
     * Initialize webservice for the given module
     * @param Vtecrm_Module Instance of the module.
     */
    static function initialize($moduleInstance) {
        if($moduleInstance->isentitytype) {
            // TODO: Enable support when webservice API support is added.
            if(function_exists('vtws_addDefaultModuleTypeEntity')) {
                vtws_addDefaultModuleTypeEntity($moduleInstance->name);
                self::log("Initializing webservices support ...DONE");
            }
        }
    }

    /**
     * Helper function to log messages
     * @param String Message to log
     * @param Boolean true appends linebreak, false to avoid it
     * @access private
     */
    static function log($message, $delim=true) {
        Vtecrm_Utils::Log($message, $delim);
    }
}
