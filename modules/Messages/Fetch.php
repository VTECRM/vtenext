<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $current_user;

$account = vtlib_purify($_REQUEST['account']);
$folder = mb_convert_encoding($_REQUEST['folder'] , "UTF7-IMAP", "UTF-8" ); //crmv@61520
$only_news = ($_REQUEST['only_news'] == 'yes');

$focus = CRMEntity::getInstance('Messages');
$result = $focus->fetch($account, $folder, $only_news);
//crmv@62821
if (empty($result)) {
	$focus->interval_schedulation = '';
	//crmv@OPER8279
	if (!empty($focus->interval_storage)) {	// if isset interval_storage set the limit of sync
		$interval_storage = $focus->getIntervalStorage();
		$focus->interval_schedulation = $interval_storage['interval'];
	}
	//crmv@OPER8279e
	$result = $focus->fetch($account, $folder, $only_news);
}
//crmv@62821e

echo $result;
exit();
?>