<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Mailer.php');

class Vtiger_Mailer extends Vtecrm_Mailer {
    function __construct() {
        parent::__construct();
        logDeprecated('Deprecated class Vtiger_Mailer called');
    }
}
