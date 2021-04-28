<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if (vtlib_isModuleActive('Invoice') && isPermitted("Invoice","EditView",$_REQUEST['record']) == 'yes') {
	$smarty->assign("CONVERTINVOICE","permitted");
	$smarty->assign("ACCOUNTID",$focus->column_fields['related_to']);
}
?>