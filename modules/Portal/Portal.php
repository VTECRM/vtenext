<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@208173 */

/** Function to save the portal in database
 *  @param $portalName : Type String
 *  @param $portalUrl : Type String
 *  This function saves the portal with the given $portalname,$portalurl
 *  This Returns $portalid
 */
function SavePortal($portalName, $portalUrl)
{
	global $adb;
	global $table_prefix;
	$adb->println("just entered the SavePortal method");
	$portalId = $adb->getUniqueID($table_prefix.'_portal');

	$query = "insert into {$table_prefix}_portal values(?,?,?,?,?)";
	$params = array($portalId,$portalName,$portalUrl,0,0);

	$adb->println($query);
	$adb->pquery($query,$params);

	return $portalId;
}
/** Function to update the portal in database 
 *  @param $portalName : Type String
 *  @param $portalUrl : Type String
 *  @param $portalId : Type Integer
 *  This function updates the portal with the given $portalname,$portalurl
 *  This Returns $portalid 
 */
function UpdatePortal($portalName, $portalUrl, $portalId)
{
	global $adb;
	global $table_prefix;

	$adb->println("just entered the SavePortal method");

	$query="update {$table_prefix}_portal set portalname=? ,portalurl=? where portalid=?";
	$params = [$portalName, $portalUrl, $portalId];

	$adb->println($query);
	$adb->pquery($query, $params);

	return $portalId;
}