<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@138188 */

$mod_strings = array(
	'LBL_DB_ERROR' => 'PHP Version Check',
	'LBL_DB_ERROR_DESC' => 'PHP 7.0 or above is required. Your current PHP version is %s <br>Kindly upgrade the PHP installation and try again!', // crmv@180737
);

$errorTitle = $mod_strings['LBL_DB_ERROR'];
$pageTitle = "VTENEXT - ".$errorTitle;
$errorDescription = $mod_strings['LBL_DB_ERROR_DESC'];

$errorDescription = str_replace('%s', phpversion(), $errorDescription);

require('generic_error.php');