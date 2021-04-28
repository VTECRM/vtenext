<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include('config.inc.php');
require_once('include/utils/utils.php');

/* 
  Retrieve the graph. This wrapper is necessary, because non-admin users
  don't have access to the Settings directory.
*/

global $app_strings;

$mode = $_REQUEST['mode'];

if ($mode == 'download') {
	require('modules/Settings/ProcessMaker.php');
} else {
	die($app_strings['LBL_PERMISSION']);
}