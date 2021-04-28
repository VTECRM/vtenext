<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@30447
require_once('modules/VteCore/EditView.php');

// crmv@104568

// Added to set price book active when creating a new pricebook
if($focus->mode != 'edit' && $_REQUEST['isDuplicate'] != 'true')
	$smarty->assign('PRICE_BOOK_MODE', 'create');

$smarty->display('Inventory/InventoryEditView.tpl');