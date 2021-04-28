<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/VteCore/EditView.php';	//crmv@30447

//crmv@58337
if($isduplicate == 'true') {
	$focus->column_fields['scheduled'] = 0;
	
	$smarty->assign("BLOCKS",getBlocks($currentModule,$disp_view,$mode,$focus->column_fields,'',$blockVisibility));	//crmv@99316
}
//crmv@58337e

$smarty->display('salesEditView.tpl');