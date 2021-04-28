<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//Display the error message
if($record != '' && VteSession::get('image_type_error') != '') {
	echo '<font color="red">'.VteSession::get('image_type_error').'</font>';
	VteSession::remove('image_type_error');
}
$sql = $adb->pquery('select accountid from '.$table_prefix.'_contactdetails where contactid=?', array($focus->id));
$accountid = $adb->query_result($sql,0,'accountid');
if ($accountid == 0) {
	$accountid = '';
}
$smarty->assign("accountid",$accountid);

/* crmv@55961 */
$focusNewsletter = CRMEntity::getInstance('Newsletter');
$email = $focus->column_fields[$focusNewsletter->email_fields[$currentModule]['fieldname']];
$newsletter_unsub_status = $focusNewsletter->receivingNewsletter($email);
$smarty->assign('RECEIVINGNEWSLETTER',$newsletter_unsub_status);
?>