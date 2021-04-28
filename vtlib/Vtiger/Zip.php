<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Zip.php');

class Vtiger_Zip extends Vtecrm_Zip {
    public function __construct($filename, $overwrite=true)
    {
        parent::__construct($filename, $overwrite);
        logDeprecated('Deprecated class Vtiger_Zip called');
    }
}
