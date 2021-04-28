<?php
/* crmv@198038 */

/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/SettingsBlock.php');

class Vtiger_SettingsBlock extends Vtecrm_SettingsBlock {
    public function __construct()
    {
        parent::__construct();
        logDeprecated('The class Vtiger_SettingsBlock called');
    }
}
