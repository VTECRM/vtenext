<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Language.php');
require_once('vtlib/Vtecrm/LanguageImport.php');

class Vtiger_Language extends Vtecrm_LanguageImport {
    function __construct() {
        parent::__construct();
        logDeprecated('Deprecated class Vtiger_Language called');
    }
}
