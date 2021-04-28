<?php

// crmv@155585

global $adb;
if ($adb->dbType == 'mssql') {
	Update::info('VTE now supports the native sqlsrv driver for SQL Server.');
	Update::info('With PHP7, this is the only available driver, so if you plan to migrate to PHP7,');
	Update::info('please install the driver and update config.inc.php using "mssqlnative" as db_type.');
	Update::info('');
}
