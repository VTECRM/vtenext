<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

include('vteversion.php'); // crmv@181168

// more than 8MB memory needed for graphics
// memory limit default value = 128M
ini_set('memory_limit','128M');

// crmv@178158 - be sure numbers and dates are always in C format
setlocale(LC_NUMERIC, 'C');
setlocale(LC_TIME, 'C');
// crmv@178158e

//crmv@replace FCKEDITOR
// show or hide world clock, calculator and FCKEditor
// Do NOT remove the quotes if you set these to false!
$WORLD_CLOCK_DISPLAY = 'false';
$FCKEDITOR_DISPLAY = 'true';
//crmv@replace FCKEDITOR end
// url for customer portal (Example: http://www.vte.com/portal)
$PORTAL_URL = '_SITE_URL_/portal';

// helpdesk support email id and support name (Example: 'support@vte.biz' and 'VTE Support')
$HELPDESK_SUPPORT_EMAIL_ID = '_USER_SUPPORT_EMAIL_';
$HELPDESK_SUPPORT_NAME = 'VTE Notification System';
$HELPDESK_SUPPORT_EMAIL_REPLY_ID = $HELPDESK_SUPPORT_EMAIL_ID;

//crmv@10488
$REMINDER_EMAIL_ID ='_USER_SUPPORT_EMAIL_';
$REMINDER_NAME = 'VTE Notification System';
//crmv@10488 e\

/* database configuration
      db_server
      db_port
      db_hostname
      db_username
      db_password
      db_name
*/

$dbconfig['db_server'] = '_DBC_SERVER_';
$dbconfig['db_port'] = '_DBC_PORT_';
$dbconfig['db_username'] = '_DBC_USER_';
$dbconfig['db_password'] = '_DBC_PASS_';
$dbconfig['db_name'] = '_DBC_NAME_';
$dbconfig['db_type'] = '_DBC_TYPE_';
$dbconfig['db_status'] = '_DB_STAT_';
//crmv@add db options
$dbconfig['db_charset'] = '_DB_CHARSET_';
$dbconfig['db_dieOnError'] = '_DB_DIEONERROR_';
//crmv@add db options end
// TODO: test if port is empty
// TODO: set db_hostname dependending on db_type
$dbconfig['db_hostname'] = $dbconfig['db_server'].$dbconfig['db_port'];

// switch to the new mysql driver if available
if ($dbconfig['db_type'] == 'mysql' && !function_exists('mysql_connect') && function_exists('mysqli_connect')) {
	$dbconfig['db_type'] = 'mysqli';
}

// log_sql default value = false
$dbconfig['log_sql'] = false;

// persistent default value = true
$dbconfigoption['persistent'] = true;

// autofree default value = false
$dbconfigoption['autofree'] = false;

// debug default value = 0
$dbconfigoption['debug'] = 0;

// seqname_format default value = '%s_seq'
$dbconfigoption['seqname_format'] = '%s_seq';

// portability default value = 0
$dbconfigoption['portability'] = 0;

// ssl default value = false
$dbconfigoption['ssl'] = false;

// table prefix (ex. vte_account)
$table_prefix = 'vte';

$host_name = $dbconfig['db_hostname'];

// without final "/"
$site_URL = '_SITE_URL_';

// root directory path	(with final "/")
$root_directory = '_VT_ROOTDIR_';

// cache direcory path
$cache_dir = '_VT_CACHEDIR_';

// tmp_dir default value prepended by cache_dir = images/
$tmp_dir = '_VT_TMPDIR_';

// import_dir default value prepended by cache_dir = import/
$import_dir = 'cache/import/';

// upload_dir default value prepended by cache_dir = upload/
$upload_dir = '_VT_UPLOADDIR_';

// maximum file size for uploaded files in bytes also used when uploading import files
// upload_maxsize default value = 3000000
$upload_maxsize = 3000000;

// flag to allow export functionality
// 'all' to allow anyone to use exports
// 'admin' to only allow admins to export
// 'none' to block exports completely
// allow_exports default value = all
$allow_exports = 'all';

// files with one of these extensions will have '.txt' appended to their filename on upload
// upload_badext default value = php, php3, php4, php5, pl, cgi, py, asp, cfm, js, vbs, html, htm
//crmv@16312 crmv@189149 crmv@195993
$upload_badext = array(
	'php', 'php3', 'php4', 'php5', 'pht', 'phtml', 'phps', 'phar',
	'htm', 'html', 'xhtml', 'js', 'pl', 'py', 'rb',
	'cgi', 'asp', 'cfm', 'vbs', 'jsp',
	'exe', 'bin', 'bat', 'com', 'sh', 'dll', 'msi',
	'htaccess', 'htpasswd'
);
//crmv@16312e crmv@189149e crmv@195993e

// list_max_entries_per_page default value = 20
$list_max_entries_per_page = '20';

// limitpage_navigation default value = 5
$limitpage_navigation = '5';

// history_max_viewed default value = 10
$history_max_viewed = '10';

// default_module default value = Home
$default_module = 'Home';

// default_action default value = index
$default_action = 'index';

// set default theme
// default_theme default value = blue
$default_theme = 'next';

// show or hide time to compose each page
// calculate_response_time default value = true
$calculate_response_time = false;

// default text that is placed initially in the login form for user name
// no default_user_name default value
$default_user_name = '';

// default text that is placed initially in the login form for password
// no default_password default value
$default_password = '';

//Master currency name
$currency_name = '_MASTER_CURRENCY_';

// default charset
// default charset default value = 'UTF-8' or 'ISO-8859-1'
$default_charset = '_VT_CHARSET_';

// default language
// default_language default value = en_us
$default_language = 'en_us';

// separators to display float values
$default_decimal_separator = '.';
$default_thousands_separator = '';
$default_decimals_num = 2;

// add the language pack name to every translation string in the display.
// translation_string_prefix default value = false
$translation_string_prefix = false;

//Option to cache tabs permissions for speed.
$cache_tab_perms = true;

//Option to hide empty home blocks if no entries.
$display_empty_home_blocks = false;

// Generating Unique Application Key
$application_unique_key = '_VT_APP_UNIQKEY_';

// Secret key used to generate anti-csrf tokens
$csrf_secret = '_CSRF_SECRET_'; // crmv@171581

// trim descriptions, titles in listviews to this value
$listview_max_textlength = 40;

// Maximum time limit for PHP script execution (in seconds)
$php_max_execution_time = 0;

// Set the default timezone as per your preference
$default_timezone = 'Europe/Rome';

/** If timezone is configured, try to set it */
if(isset($default_timezone) && function_exists('date_default_timezone_set')) {
	@date_default_timezone_set($default_timezone);
}
//crmv@show full name user
// get username (lastname firstname) in every user selection
$showfullusername = true;
//crmv@show full name user end
//crmv@show reflection under the company logo
$reflection_logo = true;
//crmv@show reflection under the company logo end

$new_folder_storage_owner = array('user'=>'','group'=>'');	//crmv@98116

// gdpr root directory, without final "/"
$gdpr_URL = '_SITE_URL_/gdpr'; // crmv@161554

// custom field prefix
$cf_prefix = '_CF_PREFIX_';

// option to overwrite the parameters of all emails sent, you can enable one or more of the following properties
$send_mail_ow = array(/*'to_email'=>'','cc'=>'','bcc'=>'','subject_prefix'=>''*/);

?>