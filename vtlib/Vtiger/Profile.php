<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Profile.php');

class Vtiger_Profile extends Vtecrm_Profile {

    static function log($message, $delimit=true) {
        parent::log($message, $delimit);
        logDeprecated('Deprecated method log called in Vtiger_Profile');
    }

    static function initForField($fieldInstance) {
        parent::initForField($fieldInstance);
        logDeprecated('Deprecated method initForField called in Vtiger_Profile');
    }

    static function deleteForField($fieldInstance) {
        parent::deleteForField($fieldInstance);
        logDeprecated('Deprecated method deleteForField called in Vtiger_Profile');
    }

    static function getAllIds() {
        logDeprecated('Deprecated method getAllIds called in Vtiger_Profile');
        return parent::getAllIds();
    }

    static function initForModule($moduleInstance) {
        parent::initForModule($moduleInstance);
        logDeprecated('Deprecated method initForModule called in Vtiger_Profile');
    }

    static function deleteForModule($moduleInstance) {
        parent::deleteForModule($moduleInstance);
        logDeprecated('Deprecated method deleteForModule called in Vtiger_Profile');
    }
}
