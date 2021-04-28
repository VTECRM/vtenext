<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'modules/VteCore/EditView.php';	//crmv@30447

//crmv@30447

if($focus->mode == 'edit') {
	$smarty->assign("OLDSMOWNERID", $focus->column_fields['assigned_user_id']);
}

if(isset($_REQUEST['product_id'])) {
	$smarty->assign("PRODUCTID", vtlib_purify($_REQUEST['product_id']));
}

$smarty->display("salesEditView.tpl");