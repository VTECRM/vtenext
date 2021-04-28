<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44037 */
$account = vtlib_purify($_REQUEST['account']);
$focus = CRMEntity::getInstance('Emails');
$account = $focus->getFromEmailAccount($account);
$focusMessages = CRMEntity::getInstance('Messages');
$signature = $focusMessages->getAccountSignature($account);
echo $signature;
exit;
?>