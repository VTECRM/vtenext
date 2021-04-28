<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

die('Remove die');

require("../../../../config.inc.php");
chdir($root_directory);
require_once('include/utils/utils.php');

/*
 * This is an example script used to update the geocoding info for all the records of the specified module.
 */

global $adb, $table_prefix;

$moduleName = 'Accounts';

$geo = Geolocalization::getInstance();

$q = 
	"SELECT accountaddressid AS crmid
	FROM {$table_prefix}_accountbillads a 			
	INNER JOIN {$table_prefix}_crmentity c ON a.accountaddressid = c.crmid
	LEFT JOIN {$table_prefix}_geocoding g ON a.accountaddressid = g.crmid
	WHERE deleted = 0 			
	AND (latitude IS NULL OR longitude IS NULL OR latitude = '' OR longitude = '') ";
	
$result = $adb->query($q);

while($row = $adb->fetchByAssoc($result)){

	$address = $geo->getAddress($moduleName, $row['crmid'], $row);
	$geo->saveAddressCoords($moduleName, $row['crmid'], $address);
	
	echo ".";
}

echo "\nDONE";