<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@168297

/**
 * Base class for SOAP Webservices, contains some usefult methods
 */
class SOAPWebservicesBase extends SDKExtendableUniqueClass {

	protected static $sessionidCache = array();

	function getPortalUserid() {
		global $adb,$table_prefix;
		
		static $useridCache = null;

		if (is_null($useridCache)) {
			$res = $adb->pquery("SELECT prefvalue FROM ".$table_prefix."_customerportal_prefs WHERE prefkey = ? AND tabid = 0", array('userid'));
			if ($adb->num_rows($res) > 0) {
				$useridCache = $adb->query_result_no_html($res,0,'prefvalue');
			}
		}
		return $useridCache ;
	}
	
	/**	Function used to validate the session
	*	@param int $id - contact id to which we want the session id
	*	@param string $sessionid - session id which will be passed from customerportal
	*	return true/false - return true if valid session otherwise return false
	**/
	function validateSession($id, $sessionid)
	{
		global $adb;
		$adb->println("Inside function validateSession($id, $sessionid)");

		$server_sessionid = $this->getServerSessionId($id);

		$adb->println("Checking Server session id and customer input session id ==> $server_sessionid == $sessionid");

		if($server_sessionid == $sessionid)
		{
			// crmv@164120
			global $current_auth_record;
			if (empty($current_auth_record)) $current_auth_record = array('module' => 'Contacts', 'id' => $id);
			// crmv@164120e
			$adb->println("Session id match. Authenticated to do the current operation.");
			return true;
		}
		else
		{
			$adb->println("Session id does not match. Not authenticated to do the current operation.");
			return false;
		}
	}


	/**	Function used to get the session id which was set during login time
	*	@param int $id - contact id to which we want the session id
	*	return string $sessionid - return the session id for the customer which is a random alphanumeric character string
	**/
	function getServerSessionId($id) {
		global $adb, $table_prefix;

		$id = (int) $id;
		if (!array_key_exists($id, self::$sessionidCache)) {
			$query = "select sessionid from ".$table_prefix."_soapservice where type='customer' and id=?";
			self::$sessionidCache[$id] = $adb->query_result_no_html($adb->pquery($query, array($id)),0,'sessionid');
		}
		return self::$sessionidCache[$id];
	}

	/**	Function used to unset the server session id for the customer
	*	@param int $id - contact id to which customer we want to unset the session id
	**/
	function unsetServerSessionId($id) {
		global $adb,$table_prefix;

		$id = (int) $id;
		$adb->pquery("delete from ".$table_prefix."_soapservice where type='customer' and id=?", array($id));
		unset(self::$sessionidCache[$id]);

	}

}