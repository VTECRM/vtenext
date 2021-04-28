<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/FieldBasic.php');

class Vtiger_FieldBasic extends Vtecrm_FieldBasic {
    public function __construct()
    {
        parent::__construct();
        logDeprecated('Used deprecated class Vtiger_FieldBasic');
    }
}
