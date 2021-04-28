<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule;
$focus = CRMEntity::getInstance($currentModule);
$focus->mode = 'edit';
$focus->id = $_REQUEST['record'];
$focus->retrieve_entity_info($_REQUEST['record'], $currentModule);
$focus->column_fields['templateemailid'] = $_REQUEST['templateid'];
$focus->save($currentModule);
?>