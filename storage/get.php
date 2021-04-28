<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198833 */

function outputForbidden() {
	header("HTTP/1.1 403 Forbidden");
	exit();
}
// get the file parameter
$file = $_GET['file'];
// crmv@193042 retrieve record number

if (strpos($file, '/') !== false) {
    $parts = explode('/', $file);
    $parts = $parts[count($parts)-1];
    $parts = explode('_', $parts);
    $record = $parts[0];
}
else
{
    $parts = explode('-', $file);
    $record = $parts[0];
}
// crmv@193042e
if (empty($file)) outputForbidden();

// this trick is necessary to avoid sending headers with the session cookie
@ini_set('session.use_only_cookies', false);
@ini_set('session.use_cookies', false);
@ini_set('session.use_trans_sid', false);
@ini_set('session.cache_limiter', null);

$cookiename = session_name();
$sessid = $_REQUEST['Touch-Session-Id'] ?: $_COOKIE[$cookiename]; // crmv@204438

if (empty($sessid)) outputForbidden();

session_id($sessid);
session_start();

$authid = $_SESSION['authenticated_user_id'];
if (empty($authid)) outputForbidden();

// check app key
$appkey = $_SESSION['app_unique_key'];
if (empty($appkey)) outputForbidden();

// close session now, to release the lock, since the download might require some time
session_write_close();

require('../config.inc.php');
if ($application_unique_key != $appkey) outputForbidden();

// check if file is in storage
$fullpath = __DIR__ .'/'.str_replace('..', '', $file);
if (!is_readable($fullpath)) outputForbidden();

// exclude bad extensions
$ext = pathinfo($fullpath, PATHINFO_EXTENSION);
if (is_array($upload_badext) && in_array($ext, $upload_badext)) outputForbidden();

// crmv@193042 check access to file
//run env
chdir ('../');
global $log, $adb, $table_prefix, $dbconfig;
global $default_language, $default_timezone;
global $current_user;
require('config.inc.php');
include('include/utils/utils.php');
require_once('include/database/PearDatabase.php');

SDK::getUtils();	//crmv@sdk-18503

$current_user = CRMEntity::getInstance('Users');
$current_user->id = $authid;
$fileclass = FileStorage::getInstance();
if (isPermitted('Documents', 'DetailView', $fileclass->getParentId($record)) != 'yes')//getting fileid by attachmentid
    outputForbidden();

chdir('storage');
//// crmv@193042e
// output!
$fp = fopen($fullpath, 'rb');

// send the right headers
$type = mime_content_type($fullpath);
header("Content-Type: ".$type);
header("Content-Length: " . filesize($fullpath));

fpassthru($fp);
exit;