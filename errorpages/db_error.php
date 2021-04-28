<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@138188 */

$mod_strings = array(
	'LBL_DB_ERROR' => 'Database error',
	'LBL_DB_ERROR_DESC' => 'Unable to connect to the database, please check the configuration file.',
);

$errorTitle = $mod_strings['LBL_DB_ERROR'];
$pageTitle = "VTENEXT - ".$errorTitle;
$errorDescription = $mod_strings['LBL_DB_ERROR_DESC'];

require('generic_error.php');