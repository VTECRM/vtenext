<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if(isPermitted("Leads","EditView",$_REQUEST['record']) == 'yes' && isPermitted("Leads","ConvertLead") =='yes' && (isPermitted("Accounts","EditView") =='yes' || isPermitted("Contacts","EditView") == 'yes') && (vtlib_isModuleActive('Contacts') || vtlib_isModuleActive('Accounts')))
{
	$smarty->assign("CONVERTLEAD","permitted");
}

/* crmv@55961 */
$focusNewsletter = CRMEntity::getInstance('Newsletter');
$email = $focus->column_fields[$focusNewsletter->email_fields[$currentModule]['fieldname']];
$newsletter_unsub_status = $focusNewsletter->receivingNewsletter($email);
$smarty->assign('RECEIVINGNEWSLETTER',$newsletter_unsub_status);
?>