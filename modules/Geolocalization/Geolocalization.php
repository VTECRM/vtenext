<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@103054 */

class Geolocalization extends SDKExtendableClass {

	// modules for which the button is installed (used only during install)
	public $installModules = array('Accounts', 'Contacts', 'Leads', 'Vendors');
	
	// dynamic list of modules for which the geolocation is available
	public $installedModules = array();
	
	// modules with address fields
	public $addressFields = array(
		'Accounts' => array('bill_street', 'bill_city', 'bill_code', 'bill_state', 'bill_country'),
		'Contacts' => array('mailingstreet', 'mailingcity', 'mailingzip', 'mailingstate', 'mailingcountry'),
		'Leads' => array('lane', 'city', 'code', 'state', 'country'),
		'Vendors' => array('street', 'city', 'postalcode', 'state', 'country'),
	);
	
	// fields used to search the address with the Google APIs
	// The order is important!
	public $geocodingFields = array(
		'Accounts' => array('bill_street', 'bill_code', 'bill_city'),
		'Contacts' => array('mailingstreet', 'mailingzip', 'mailingcity'),
		'Leads' => array('lane', 'code', 'city'),
		'Vendors' => array('street', 'postalcode', 'city'),
	);	
	
	// modules with parent field
	// TODO: find a way to avoid hardcoding them here
	public $parentFields = array(
		'HelpDesk' => 'parent_id',
		'Potentials' => 'related_to',
		'Quotes' => array('account_id', 'contact_id'),
		'PurchaseOrder' => array('contact_id', 'vendor_id'),
		'SalesOrder' => array('account_id', 'contact_id'),
		'Invoice' => array('account_id', 'contact_id'),
		'Ddt' => array('accountid', 'salesorderid'),
		'ProjectPlan' => 'linktoaccountscontacts',
		'ProjectMilestone' => 'projectid',
		'ProjectTask' => 'projectid',
		'Projects' => 'projectid',
		'Visitreport' => 'accountid',
		'Timecards' => 'ticket_id',
		'Assets' => 'account',
		// stupid calendar!
		'Calendar' => array('parent_id', 'contact_id'),
		'Events' => array('parent_id', 'contact_id'),
		'Activity' => array('parent_id', 'contact_id'),
	);
	
	public $infoFields = array(
		'Accounts' => array('phone'),
		'Contacts' => array('phone'),
		'Leads' => array('phone'),
		'Vendors' => array('phone'),
		// other modules
		'HelpDesk' => array('ticketstatus', 'ticketcategories'),
		'Potentials' => array('amount', 'sales_stage'),
		// calendar
		'Calendar' => array('assigned_user_id', 'activitytype'),
		'Events' => array('assigned_user_id', 'activitytype'),
		'Activity' => array('assigned_user_id', 'activitytype'),
	);

	/**
	 * Default Google API key to use. After installation, the key is modifiable
	 * through VTEProperties class (geolocalization.api_key)
	 */
	protected static $defaultKey = 'AIzaSyDe5Q6vdipVFiFoOFj3VJe2hEfSQrO3ihM'; // crmv@124745 crmv@174250
	
	public function __construct() {
		global $adb, $table_prefix;
		
		// populate the installed modules array
		// use the session as a cache
		
		$cache = Cache::getInstance('geoloc_modcache');
		$list = $cache->get();
		if (!$list) {
			$res = $adb->pquery(
				"SELECT t.name 
				FROM {$table_prefix}_links l
				INNER JOIN {$table_prefix}_tab t on t.tabid = l.tabid
				WHERE l.linktype = ? and l.linklabel = ?", 
				array('LISTVIEWBASIC', 'Geolocalization')
			);
			$list = array();
			if ($res && $adb->num_rows($res) > 0) {
				while ($row = $adb->fetchByAssoc($res, -1, false)) {
					$list[] = $row['name'];
				}
			}
			$cache->set($list, 600);	// 10 min cache
		}
		
		$this->installedModules = $list;

	}
	
	// crmv@174250
	/**
	 * Get the currently used api key
	 */
	public static function getApiKey() {
		$VP = VTEProperties::getInstance();
		$key = $VP->get('geolocalization.api_key') ?: self::$defaultKey;
		return $key;
	}
	
	/**
	 * Save the api key to use (if empty, use the default)
	 */
	public static function saveApiKey($key = null) {
		$VP = VTEProperties::getInstance();
		if (!$key) $key = self::$defaultKey;
		$VP->set("geolocalization.api_key", $key);
		return $key;
	}
	// crmv@174250e
	
	/**
	 * Return true if the module supports the geolocation
	 */
	public function isModuleHandled($module) {
		return in_array($module, $this->installedModules);
	}

	/**
	 * Get the complete address for the specified module and record.
	 */
	public function getAddress($module, $crmid, $column_fields = array()) {

		if (empty($column_fields)) {
			// retrieve the values
			$column_fields = $this->getRecordValues($module, $crmid);
			if (!$column_fields) return '';
		}
		
		$address = '';
		if (!empty($this->addressFields[$module])) {
			// modules with address fields
			$afields = $this->addressFields[$module];
			$address = $this->joinAddressFields($afields, $column_fields);
		} elseif (array_key_exists($module, $this->parentFields)) {
			// modules with parent id
			$parent = $this->getParentRecord($module, $crmid, $column_fields);
			if ($parent && $parent['module']) {
				$address = $this->getAddress($parent['module'],$parent['crmid']);
			}
		}
		
		return $address;
	}
	
	public function getParentRecord($module, $crmid, $column_fields) {

		$parentField = $this->parentFields[$module];
		if (is_array($parentField)) {
			// get the first non empty id
			for ($i=0; $i<count($parentField) && empty($column_fields[$parentField[$i]]); ++$i) ;
			if ($i<count($parentField)) {
				$parentField = $parentField[$i];
			} else {
				$parentField = null;
			}
			
		}
		
		if ($parentField) {
			$parentId = intval($column_fields[$parentField]);
			if ($parentId > 0) {
				$parentModule = getSalesEntityType($parentId);
				return array('crmid' => $parentId, 'module' => $parentModule);
			}
		}

		return null;
	}
	
	protected function joinAddressFields($fields, $column_fields, $stripCommas = false) {
		$string = '';
		foreach ($fields as $f) {
			if ($column_fields[$f] != '') {
				if ($stripCommas) {
					$string .= str_replace(',', '', $column_fields[$f]).', ';
				} else {
					$string .= $column_fields[$f].', ';
				}
			}
		}
		return rtrim($string, ', ');
	}
	
	/**
	 * Generates the query needed to get the minimum column fields necessary
	 */
	protected function getInfoQuery($module, $crmid) {
		global $current_user, $adb, $table_prefix;
		
		if (!$current_user) {
			$current_user = CRMEntity::getInstance('Users');
			$current_user->id = 1;
		}
		
		$crmid = intval($crmid);
		
		$qg = QueryGenerator::getInstance($module, $current_user);
		$idcol = $qg->getSQLColumn('id', false);
		
		if (array_key_exists($module, $this->addressFields)) {
			$fields = $this->addressFields[$module];
		} else {
			$fields = $this->parentFields[$module];
			if (!is_array($fields)) $fields = array($fields);
		}
		
		// add more fields
		if (is_array($this->infoFields[$module])) {
			$fields = array_merge($fields, $this->infoFields[$module]);
		}
		
		foreach ($fields as $field) {
			$qg->addField($field);
			$qg->setFieldAlias($field, $field); // be sure that the column name is the fieldname
		}
		
		$qg->appendRawSelect(array('geo.latitude', 'geo.longitude'));
		$qg->appendToFromClause("LEFT JOIN {$table_prefix}_geocoding geo ON $idcol = geo.crmid");
		
		$q = $qg->getQuery();
		$q .= " AND $idcol = $crmid";
		
		return $q;
	}
	
	/**
	 * Get the necessary fields for the record
	 */
	protected function getRecordValues($module, $crmid) {
		global $adb;
		
		// old code, quite slow
		/*$focus = CRMEntity::getInstance($module);
		$r = $focus->retrieve_entity_info($crmid, $module, false);
		if ($r == 'LBL_RECORD_DELETE' || $r == 'LBL_RECORD_NOT_FOUND') return false;
		return $focus->column_fields;
		*/
		
		$q = $this->getInfoQuery($module, $crmid);
		$res = $adb->query($q);
		
		if ($res && $adb->num_rows($res) > 0) {
			$values = $adb->fetchByAssoc($res);
			return $values;
		}
		return false;
	}
	
	/**
	 * Fills the missing coordinates
	 */
	public function fillMissingCoords($ids) {
		global $adb, $table_prefix;
		
		// fast query for direct modules
		$q = "SELECT 
			c.crmid,
			CASE 
				WHEN c.setype = 'Accounts' THEN aa.bill_street	
				WHEN c.setype = 'Contacts' THEN ca.mailingstreet
				WHEN c.setype = 'Leads' THEN la.lane
				WHEN c.setype = 'Vendors' THEN va.street
				ELSE NULL
			END AS street,
			CASE 
				WHEN c.setype = 'Accounts' THEN aa.bill_city	
				WHEN c.setype = 'Contacts' THEN ca.mailingcity
				WHEN c.setype = 'Leads' THEN la.city
				WHEN c.setype = 'Vendors' THEN va.city
				ELSE NULL
			END AS city,
			CASE 
				WHEN c.setype = 'Accounts' THEN aa.bill_code	
				WHEN c.setype = 'Contacts' THEN ca.mailingzip
				WHEN c.setype = 'Leads' THEN la.code
				WHEN c.setype = 'Vendors' THEN va.postalcode
				ELSE NULL
			END AS code,
			CASE 
				WHEN c.setype = 'Accounts' THEN aa.bill_state	
				WHEN c.setype = 'Contacts' THEN ca.mailingstate
				WHEN c.setype = 'Leads' THEN la.state
				WHEN c.setype = 'Vendors' THEN va.state
				ELSE NULL
			END AS state,
			CASE 
				WHEN c.setype = 'Accounts' THEN aa.bill_country	
				WHEN c.setype = 'Contacts' THEN ca.mailingcountry
				WHEN c.setype = 'Leads' THEN la.country
				WHEN c.setype = 'Vendors' THEN va.country
				ELSE NULL
			END AS country,
			c.setype
			FROM {$table_prefix}_crmentity c
			LEFT JOIN {$table_prefix}_geocoding g ON c.crmid = g.crmid
			LEFT JOIN {$table_prefix}_accountbillads aa ON aa.accountaddressid = c.crmid
			LEFT JOIN {$table_prefix}_contactaddress ca ON ca.contactaddressid = c.crmid
			LEFT JOIN {$table_prefix}_leadaddress la ON la.leadaddressid = c.crmid
			LEFT JOIN {$table_prefix}_vendor va ON va.vendorid = c.crmid
			WHERE deleted = 0 AND c.crmid IN ('".implode("','",$ids)."') AND (latitude IS NULL OR longitude IS NULL OR latitude = '' OR longitude = '')  
			GROUP BY c.crmid ";
	
		$result = $adb->query($q);

		//Aggiornamento delle coordinate mancanti
		while($row = $adb->fetchByAssoc($result)){
			
			$crmid = intval($row['crmid']);
			$setype = $row['setype'];
			$address = '';
			
			if (array_key_exists($setype, $this->parentFields)) {
				// moduli con parent	
				$address = $this->getAddress($setype, $crmid);
				
			} else {
				// faster way to create the address
				$fields = array('street', 'city', 'code', 'state', 'country');
				$address = $this->joinAddressFields($fields, $row, true);
			}
			
			if ($address) {
				$this->saveAddressCoords($setype, $crmid, $address);
			}
		}
	}
	
	/**
	 * Retrieve addresses and other info for all the selected ids
	 */
	public function retrieveAddresses($ids) {
		
		$this->fillMissingCoords($ids);
		
		$addresses = array();
		
		for ($i=0; $i < sizeof($ids); ++$i) {
			
			$record = intval($ids[$i]);
			if (empty($record)) continue;
			
			$type = getSalesEntityType($record);
			
			if (!array_key_exists($type, $this->addressFields) && !array_key_exists($type, $this->parentFields)) {
				// module not handled!
				continue;
			}
				
			$name = getEntityName($type, $record, true);
			$values = $this->getRecordValues($type, $record);
			
			$phone = '';
			$address = '';
			$latitude = $values['latitude'];
			$longitude = $values['longitude'];
			
			if (array_key_exists($type, $this->addressFields)) {
				
				// a basic module
				if ($values) {
					$phone = $values['phone'];
					
					$afields = $this->geocodingFields[$type];
					$address = $this->joinAddressFields($afields, $values, true);
					
				}
			
			} elseif (array_key_exists($type, $this->parentFields)) {
				
				// a module with parent				
				$r = $this->getParentRecord($type, $record, $values);
				$parentId = $r['crmid'];
				$parentMod = $r['module'];
				
				if ($parentId) {
					$parentName = getEntityName($parentMod, $parentId, true);
					$parentValues = $this->getRecordValues($parentMod, $parentId);
					
					// now create the values for specific modules
					if ($type=='HelpDesk') {
						$name .= ' - '.$parentName;
						$address = getTranslatedString($values['ticketstatus'],$type);
						$phone = $values['ticketcategories'];

					} elseif($type=='Potentials') {
				
						$name .= ' - '.$parentName;
						$address = $values['amount'];
						$phone = getTranslatedString($values['sales_stage'],$type);

					} elseif($type=='Calendar') {
				
						$assigned_to = getUserFullName($values['assigned_user_id']);
						$address = getTranslatedString($values['activitytype']).' - '.$assigned_to;
						$phone = getTranslatedString('SINGLE_'.$parentMod, $parentMod).": ".$parentName;
						
					// crmv@183377
					} else {
						$name .= ' - '.$parentName;
						$address = ' ';
					}
					// crmv@183377e
					
				}
			
			}
			
			$addresses[$i][0] = $name;
			$addresses[$i][1] = $address;
			$addresses[$i][2] = $phone;
			$addresses[$i][3] = $latitude;
			$addresses[$i][4] = $longitude;
			
		}

		return $addresses;
	}

	/**
	 * Translate an address into coordinates and save them for the record
	 */
	public function saveAddressCoords($module, $crmid, $address) {
		$coords = $this->getCoordinates($address);
		if ($coords) {
			$this->saveCoordinates($module, $crmid, $coords);
		}
	}
	
	/**
	 * Saves the coordinates associated to the specified record
	 */
	public function saveCoordinates($module, $crmid, $coords) {
		global $adb, $table_prefix;
		
		$q = "SELECT longitude,latitude FROM {$table_prefix}_geocoding WHERE crmid = ? ";
		$res = $adb->pquery($q, array($crmid));
		if($adb->num_rows($res) > 0){
			$sql = "UPDATE {$table_prefix}_geocoding SET latitude = ?, longitude = ? WHERE crmid = ? ";
			$params = array($coords['lat'], $coords['long'], $crmid);
		}else{
			$params = array($crmid, $module, $coords['lat'], $coords['long']);
			$sql = "INSERT INTO {$table_prefix}_geocoding (crmid, setype, latitude, longitude) VALUES (".generateQuestionMarks($params).") ";
		}
		$adb->pquery($sql, $params);
	}

	/**
	 * Returns the coordinates for the specified address
	 */
	public function getCoordinates($address) {
		
		if (empty($address)) return false;
		
		$url = 'https://maps.google.com/maps/api/geocode/json?address='.urlencode($address).'&key='.self::getApiKey(); // crmv@167836 crmv@174250

		$geocode = @file_get_contents($url);
		if ($geocode !== false) {
			$output= Zend_Json::decode($geocode);
			$status = $output['status'];
			
			if($status == 'OK'){
				$lat = $output['results'][0]['geometry']['location']['lat'];
				$long = $output['results'][0]['geometry']['location']['lng'];
				
				$res = array(
					'lat' => $lat,
					'long' => $long,
				);
				return $res;
			}
		}
		return false;
	}

	public function vtlib_handler($moduleName, $eventType) {
		global $adb, $table_prefix;

		if($eventType == 'module.postinstall') {
			
			$adb->pquery("UPDATE {$table_prefix}_tab SET customized = 0 WHERE name=?", array($moduleName));
			
			$sdkInstance = Vtecrm_Module::getInstance('SDK');
			SDK::clearSessionValues();
			
			// save the key
			self::saveApiKey(); // crmv@174250
			
			// add Javascript
			Vtecrm_Link::addLink($sdkInstance->id, 'HEADERSCRIPT', 'SDKScript', 'modules/Geolocalization/Geolocalization.js');

			// add buttons in ListView
			$condition = 'checkGeoButton:modules/Geolocalization/GeolocalizationUtils.php';
			foreach ($this->installModules as $module) {
				$modInstance = Vtecrm_Module::getInstance($module);
				Vtecrm_Link::addLink($modInstance->id, 'LISTVIEWBASIC', 'Geolocalization', "VTEGeolocalization.getLocalization('\$MODULE\$');", '', 0, $condition);
			}
			
			// Remove old registered sdk class (now a handler is used, registered in the xml)
			$oldModules = array('Accounts', 'Contacts', 'Leads', 'Vendors', 'Potentials', 'HelpDesk', 'Calendar');
			foreach ($oldModules as $module) {
				// check for existing extension
				$res = $adb->pquery("SELECT extends FROM sdk_class WHERE module = ?", array($module.'Geo'));
				if ($res && $adb->num_rows($res) > 0) {
					$extended = $adb->query_result_no_html($res, 0, 'extends');
					if ($extended) {
						// update the children extension to skip the *geo
						$adb->pquery("UPDATE sdk_class SET extends = ? WHERE extends = ?", array($extended, $module.'Geo'));
						// and remove the orphaned extension
						$res = $adb->pquery("DELETE FROM sdk_class WHERE module = ?", array($module.'Geo'));
					}
				}
			}
			
			$em = new VTEventsManager($adb);
			$em->setHandlerInActive('GeolocalizationHandler');
				
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($eventType == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	// crmv@194390
	public function setAddressFieldForModule($module, $fields) {
		$this->addressFields[$module] = $fields;
	}
	// crmv@194390e

}