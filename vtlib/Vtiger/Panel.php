<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@104568 */

require_once('vtlib/Vtiger/Utils.php');
require_once('vtlib/Vtecrm/Panel.php');

/**
 * Provides API to work with vtiger CRM Module Panels
 * @package vtlib
 */
class Vtiger_Panel extends Vtecrm_Panel {
    public function __construct()
    {
        parent::__construct();
        logDeprecated('Deprecated class Vtiger_Panel called');
    }
}