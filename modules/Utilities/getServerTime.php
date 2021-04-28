<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@62394 */

$format = $_REQUEST['oformat'];

$ts = time();

if ($format == 'raw') {
	echo $ts;
} elseif ($format == 'json') {
	echo Zend_Json::encode(array('success' => true, 'timestamp'=>$ts));
}