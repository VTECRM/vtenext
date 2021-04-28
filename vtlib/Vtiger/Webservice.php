<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Webservice.php');

class Vtiger_Webservice extends Vtecrm_Webservice {
    static function initialize($moduleInstance)
    {
        parent::initialize($moduleInstance);
        logDeprecated('Deprecated method initialize called in Vtiger_Utils');
    }
}
