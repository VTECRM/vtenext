<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@176547 */

// simple wrapper to authorize oauth2 tokens in VteSync

// quick check
if (empty($_REQUEST['code'])) {
	die('Authorization code not provided');
}

// simulate full url
$_REQUEST['module'] = $_GET['module'] = 'Settings';
$_REQUEST['action'] = $_GET['action'] = 'SettingsAjax';
$_REQUEST['file'] = $_GET['module'] = 'VteSync';
$_REQUEST['ajax'] = $_GET['ajax'] = '1';
$_REQUEST['subaction'] = $_GET['subaction'] = 'token';

// route to the right page
require('index.php');