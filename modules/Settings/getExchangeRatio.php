<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42266 */

$from = substr(trim($_REQUEST['from']), 0, 3);
$to = substr(trim($_REQUEST['to']), 0, 3);

if (empty($from) || empty($to)) die();

// get ratio
$CU = CurrencyUtils::getInstance();
try {
	$ratio = $CU->getExchangeRatio($from, $to);
} catch (Exception $e) {
	// do nothing
}

// output
echo $ratio;
exit;
?>