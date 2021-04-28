<?php
/* crmv@198038 */

/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Access.php');

class Vtiger_Access extends Vtecrm_Access {

    static function log($message, $delim=true) {
        logDeprecated('Deprecated method log called in Vtiger_Access');
        parent::log($message, $delim);
    }

    static function __getDefaultSharingAccessId() {
        logDeprecated('Deprecated method __getDefaultSharingAccessId called in Vtiger_Access');
        return parent::__getDefaultSharingAccessId();
    }

    static function syncSharingAccess() {
        logDeprecated('Deprecated method syncSharingAccess called in Vtiger_Access');
        parent::syncSharingAccess();
    }

    static function allowSharing($moduleInstance, $enable=true) {
        logDeprecated('Deprecated method allowSharing called in Vtiger_Access');
        parent::allowSharing($moduleInstance, $enable);
    }

    static function initSharing($moduleInstance) {
        logDeprecated('Deprecated method initSharing called in Vtiger_Access');
        parent::initSharing($moduleInstance);
    }

    static function deleteSharing($moduleInstance) {
        logDeprecated('Deprecated method deleteSharing called in Vtiger_Access');
        parent::deleteSharing($moduleInstance);
    }

    static function setDefaultSharing($moduleInstance, $permission_text='Public_ReadWriteDelete', $editstatus=0) {
        logDeprecated('Deprecated method setDefaultSharing called in Vtiger_Access');
        parent::setDefaultSharing($moduleInstance, $permission_text, $editstatus);
    }

    static function updateTool($moduleInstance, $toolAction, $flag, $profileid=false) {
        logDeprecated('Deprecated method updateTool called in Vtiger_Access');
        parent::updateTool($moduleInstance, $toolAction, $flag, $profileid);
    }

    static function deleteTools($moduleInstance) {
        logDeprecated('Deprecated method deleteTools called in Vtiger_Access');
        parent::deleteTools($moduleInstance);
    }
}
