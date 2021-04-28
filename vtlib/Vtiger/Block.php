<?php
/* crmv@198038 */
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtecrm/Block.php');

class Vtiger_Block extends Vtecrm_Block {
    public function __construct(){
        logDeprecated('Deprecated class called in Vtiger_Block');
        parent::__construct();
    }
}
