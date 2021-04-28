<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@86915 */

// Only account is supported at the moment

class TouchGeocoding extends TouchWSClass {

	public $validateModule = true;

	public function process(&$request) {
		global $touchInst, $touchUtils;
		global $adb, $table_prefix, $current_user;
		
		if (!isModuleInstalled('Geolocalization')) return $this->error('Gelocalization module not installed');
		if (!vtlib_isModuleActive('Geolocalization')) return $this->error('Gelocalization module not active');
		
		$module = $request['module'];
		if ($module != 'Accounts') return $this->error($module.' module is not supported yet');
		
		$clat = floatval($request['clat']);
		$clng = floatval($request['clng']);
		$radius = intval($request['radius']);
		//$wizard = $request['wizard'] == 'true' ? 'true' : 'false';
		
		$filterName = vtlib_purify($request['filterName']);
		$filterField = vtlib_purify($request['filterField']);
		$filterValues = Zend_Json::decode($request['filterValues']) ?: array();
		
		$checkVars = array($clat, $clng, $radius);
		foreach ($checkVars as $checkVar) {
			if (empty($checkVar)) return $this->error('Invalid parameters');
		}
		
		$returndata = array();
		
		if ($filterField && count($filterValues) == 0) {
			return $this->success(array('result' => $returndata));
		}
	
		$q = $this->getQuery($module, $clat, $clng, $radius, $filterName, $filterField, $filterValues);
		$res = $adb->pquery($q[0], $q[1]);
		
		if ($res && $adb->num_rows($res)) {
			while ($row = $adb->fetchByAssoc($res)) {
				$latitude = $row['latitude'];
				$longitude = $row['longitude'];
				
				$entityData = array();
				$entityData['crmid'] = $row['crmid'];
				$entityData['setype'] = $row['setype'];
				$entityData['entityname'] = $row['entityname'];
				$entityData['latitude'] = $latitude;
				$entityData['longitude'] = $longitude;
				
				// add the filter
				if ($filterField && $filterValues) {
					$entityData[$filterField] = $row['filterfield'];
				}
				
				$returndata[] = $entityData;
			}
		}
		
		return $this->success(array('result' => $returndata));
	}
	
	// only accounts!!
	protected function getQuery($module, $clat, $clng, $radius, $filterName = null, $filterField = null, $filterValues = array()) {
		global $touchInst, $touchUtils;
		global $adb, $table_prefix, $current_user;
		
		$entity = $touchUtils->getModuleInstance($module);
		$tabid = getTabid($module);
		
		$filterSel = '';
		if ($filterField && count($filterValues) > 0)  {
			$col = $this->getFullFieldColumn($tabid, $filterField);
			if ($col) {
				$filterSel = "$col AS filterfield, ";
			}
		}
		
		$params = array();
		$entityQuery = "SELECT {$table_prefix}_crmentity.crmid,
				{$table_prefix}_crmentity.setype,
				$filterSel
				{$table_prefix}_geocoding.latitude,
				{$table_prefix}_geocoding.longitude,
				{$table_prefix}_account.accountname AS entityname,
				ROUND(6378137*(ACOS(
				COS(RADIANS(latitude)) * COS(RADIANS(" . $clat . ")) * COS(RADIANS(longitude) - RADIANS(" . $clng . "))
			 		+ SIN(RADIANS(latitude)) * SIN(RADIANS(" . $clat . "))
	 			))) AS distance
 			FROM {$table_prefix}_account
 			INNER JOIN {$table_prefix}_crmentity ON {$table_prefix}_account.accountid = {$table_prefix}_crmentity.crmid
 			INNER JOIN {$table_prefix}_geocoding ON {$table_prefix}_crmentity.crmid = {$table_prefix}_geocoding.crmid";
		
		$entityQuery .= $entity->getNonAdminAccessControlQuery($module, $current_user);
		
		$entityQuery .= " WHERE {$table_prefix}_crmentity.deleted = 0 ";
		
		if ($filterSel && $col) {
			$entityQuery .= " AND $col IN (".generateQuestionMarks($filterValues).") ";
			$params[] = $filterValues;
		}
		
		if (!empty($filterName)) {
			$entityQuery .= " AND entityname LIKE ?";
			$params[] = "%$filterName%";
		}
		
		$entityQuery = $entity->listQueryNonAdminChange($entityQuery, $module);

		// crmv@106451
		$entityQuery .= " HAVING distance <= ? ";
		$params[] = $radius;
		// crmv@106451e
		
		return array($entityQuery, $params);
	}
	
	protected function getFullFieldColumn($tabid, $fieldname) {
		global $adb, $table_prefix;
		
		$res = $adb->pquery("SELECT tablename, columnname FROM {$table_prefix}_field WHERE tabid=? AND fieldname=?", array($tabid, $fieldname));
		if ($res && $adb->num_rows($res) > 0) {
			$row = $adb->FetchByAssoc($res, -1, false);
			return $row['tablename'].'.'.$row['columnname'];
		}
		return null;
	}

}
