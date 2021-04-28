<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@30447
require_once 'modules/VteCore/EditView.php';

if($_REQUEST['record'] != '')
{
	//Added to display the Faq comments information
	$smarty->assign("COMMENT_BLOCK",$focus->getFAQComments($_REQUEST['record']));
}

$smarty->display("salesEditView.tpl");