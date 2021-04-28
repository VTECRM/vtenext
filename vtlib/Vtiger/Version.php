<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Version.php');

class Vtiger_Version extends Vtecrm_Version {

    static function current() {
        logDeprecated('Deprecated method current called in Vtiger_Utils');
        return parent::current();
    }

    static function check($with_version, $condition='=') {
        logDeprecated('Deprecated method check called in Vtiger_Utils');
        return parent::check($with_version, $condition);
    }

    static function endsWith($string, $endString) {
        logDeprecated('Deprecated method endsWith called in Vtiger_Utils');
        return parent::endsWith($string, $endString);
    }

    static function getUpperLimitVersion($version) {
        logDeprecated('Deprecated method getUpperLimitVersion called in Vtiger_Utils');
        return parent::getUpperLimitVersion($version);
    }
}
