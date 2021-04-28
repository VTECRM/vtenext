<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/VteCore/EditView.php';	//crmv@30447

$smarty->assign('CHECK', array());

$smarty->display(vtlib_getModuleTemplate('PBXManager', 'EditView.tpl'));
?>