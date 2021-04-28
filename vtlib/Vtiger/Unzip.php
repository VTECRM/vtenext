<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Unzip.php');

class Vtiger_Unzip extends Vtecrm_Unzip {
    public function __construct($fileName)
    {
        parent::__construct($fileName);
        logDeprecated('Deprecated class Vtiger_Unzip called');
    }
}
