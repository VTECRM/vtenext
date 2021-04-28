<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@64542

require_once 'modules/VteCore/EditView.php';	//crmv@30447

global $currentModule;

$templates = array(
	'inventory' => 'Inventory/InventoryEditView.tpl',
	'standard' => 'salesEditView.tpl',
);

$templateMode = isInventoryModule($currentModule) ? 'inventory' : 'standard';

$smarty->display($templates[$templateMode]);