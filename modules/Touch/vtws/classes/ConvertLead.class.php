<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@71387 */

require_once 'include/Webservices/ConvertLead.php';
require_once 'modules/Leads/ConvertLeadUI.php';

class TouchConvertLead extends TouchWSClass {

	// these are the default fields shown, but according to visibility, they may be restricted
	public $convertFields = array(
		'Accounts' => array(
			array(
				'fieldname' => 'accountname',
				'readonly' => true,		// the value cannot be changed from the user
				'mappable' => false,	// the source field can be configured
				//'sourcefield' => '',	// the field in the lead 
			),
			array(
				'fieldname' => 'industry',
				'readonly' => false,
				'mappable' => true,
			)
		),
		'Contacts' => array(
			array(
				'fieldname' => 'lastname',
				'readonly' => false,
				'mappable' => false,
			),
			array(
				'fieldname' => 'firstname',
				'readonly' => false,
				'mappable' => false,
			),
			array(
				'fieldname' => 'email',
				'readonly' => false,
				'mappable' => false,
			),
		),
		'Potentials' => array(
			array(
				'fieldname' => 'potentialname',
				'readonly' => false,
				'mappable' => false,
			),
			array(
				'fieldname' => 'closingdate',
				'readonly' => false,
				'mappable' => true,
			),
			array(
				'fieldname' => 'sales_stage',
				'readonly' => false,
				'mappable' => true,
			),
			array(
				'fieldname' => 'amount',
				'readonly' => false,
				'mappable' => true,
			),
		),
	);

	function process(&$request) {
		global $touchInst;

		$mode = $request['mode'];
		if ($mode == 'getconfig') {
			return $this->getConfig($request);
		} elseif ($mode == 'convert') {
			return $this->convertLead($request);
		} else {
			return array('success' => false, 'error' => 'Unknown mode');
		}
	}
	
	public function getConfig(&$request) {
		global $current_user;
		
		$config = array();
		$uiinfo = new ConvertLeadUI(0, $current_user);
		
		$fields = array();
		foreach ($this->convertFields as $module => $list) {
			if ($uiinfo->isModuleActive($module)) {
				foreach ($list as $finfo) {
					if ($uiinfo->isActive($finfo['fieldname'], $module)) {
						// ok, I can use the field
						$src = $this->getSourceField($module, $finfo['fieldname'], $finfo['mappable']);
						$okfield = $finfo;
						if ($src) $okfield['sourcefield'] = $src;
						$fields[$module][] = $okfield;
					}
				}
			}
		}
		
		$config['modules'] = array_keys($fields);
		$config['fields'] = $fields;
		
		return array('success' => true, 'error' => '', 'result' => $config);
	}
	
	protected function getSourceField($module, $fieldname, $editable = true) {
		global $adb, $table_prefix;
		
		$tabid = getTabid($module);
		if ($module == 'Accounts') {
			$column = 'accountfid';
		} elseif ($module == 'Contacts') {
			$column = 'contactfid';
		} elseif ($module == 'Potentials') {
			$column = 'potentialfid';
		}
		
		$sourcefield = null;
		$res = $adb->pquery("
			SELECT f.fieldname
			FROM {$table_prefix}_field f
			INNER JOIN {$table_prefix}_convertleadmapping clm ON clm.leadfid = f.fieldid
			INNER JOIN {$table_prefix}_field f2 ON f2.fieldid = clm.{$column}
			WHERE f2.tabid = ? AND f2.fieldname = ? AND clm.editable = ?",
			array($tabid, $fieldname, intval($editable))
		);
		if ($res && $adb->num_rows($res) > 0) {
			$sourcefield = $adb->query_result_no_html($res, 0, 'fieldname');
		}
		return $sourcefield;
	}
	
	public function convertLead(&$request) {
		global $adb, $table_prefix, $current_user;
		global $touchInst;
	
		$leadid = intval($request['leadid']);
		$values = Zend_Json::decode($request['values']);
			
		if ($leadid <= 0) {
			return array('success' => false, 'error' => 'Invalid leadid');
		}
		if (empty($values)) {
			return array('success' => false, 'error' => 'Invalid values');
		}
		
		$uiinfo = new ConvertLeadUI($leadid, $current_user);
		
		// get fiere and telemarketing related
		$this->checkFiereAndTmk($leadid);
		
		// prepare the conversion
		try {
			$otype = vtws_getOwnerType($values['Globals']['assigned_user_id']);
			$assignedTo = vtws_getWebserviceEntityId($otype, $values['Globals']['assigned_user_id']);
		} catch (Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}

		$entityValues=array();
		$entityValues['transferRelatedRecordsTo'] = ($values['Globals']['transferto'] == 'transfertoacc' ? 'Accounts' : 'Contacts');
		$entityValues['assignedTo'] = $assignedTo;
		$entityValues['leadId'] = vtws_getWebserviceEntityId('Leads', $leadid);
		
		foreach ($values as $module => $mvalues) {
			if ($module == 'Globals' || !$uiinfo->isModuleActive($module)) continue;
			$entityValues['entities'][$module]['create'] = true;
			$entityValues['entities'][$module]['name'] = $module;
			foreach ($mvalues as $name => $value) {
				if (!$uiinfo->isActive($name, $module)) continue;
				$tvalue = $touchInst->touch2Field($module, $name, $value);
				$entityValues['entities'][$module][$name] = $tvalue;
			}
		}

		//convert!
		try{
			$result = vtws_convertlead($entityValues,$current_user);
		} catch(Exception $e) {
			return array('success' => false, 'error' => $e->getMessage());
		}
		
		// get the resulting ids
		$accountIdComponents = vtws_getIdComponents($result['Accounts']);
		$contactIdComponents = vtws_getIdComponents($result['Contacts']);
		$potentialIdComponents = vtws_getIdComponents($result['Potentials']);
		
		// prepare the result
		$result = array(
			'accountid' => $accountIdComponents[1],
			'contactid' => $contactIdComponents[1],
			'potentialid' => $potentialIdComponents[1],
		);
		
		// update fiere and telemarketing
		$this->saveFiereAndTmk($leadid, $result);
		
		return array('success' => true, 'error' => '', 'result' => $result);
	}
	
	protected function checkFiereAndTmk($leadid) {
		global $adb, $table_prefix;
		
		$this->fiere_ids = null;
		$this->tmk_ids = null;
		
		//crmv@52391
		if (isModuleInstalled('Fiere') && vtlib_isModuleActive('Fiere')) {
			// check if the lead has fiere
			$res = $adb->pquery("select fieraid, leadid from {$table_prefix}_fiere inner join {$table_prefix}_crmentity on fieraid = crmid where {$table_prefix}_crmentity.deleted = 0 and {$table_prefix}_fiere.leadid = ?", array($leadid));
			if ($res) {
				for ($i=0; $i<$adb->num_rows($res); ++$i) {
					$this->fiere_ids[] = intval($adb->query_result_no_html($res, $i, 'fieraid'));
				}
			}
		}
		if (isModuleInstalled('Telemarketing') && vtlib_isModuleActive('Telemarketing')) {
			// check if the lead has telemarketing(s)
			$res = $adb->pquery("select telemarketingid, leadid from {$table_prefix}_telemarketing inner join {$table_prefix}_crmentity on telemarketingid = crmid where {$table_prefix}_crmentity.deleted = 0 and {$table_prefix}_telemarketing.leadid = ?", array($leadid));
			if ($res) {
				for ($i=0; $i<$adb->num_rows($res); ++$i) {
					$this->tmk_ids[] = intval($adb->query_result_no_html($res, $i, 'telemarketingid'));
				}
			}
		}
		//crmv@52391e
	}
	
	protected function saveFiereAndTmk($leadid, $result) {
		global $adb, $table_prefix;
		
		$accountId = $result['accountid'];
		$contactId = $result['contactid'];
		
		//crmv@52391
		// convert fiere
		$updateFiere = false;
		if (is_array($this->fiere_ids) && count($this->fiere_ids) > 0 && !empty($accountId)) {
			$params = array($accountId);
			$params = array_merge($params, $this->fiere_ids);
			$adb->pquery("update {$table_prefix}_fiere set leadid = 0, accountid = ? where fieraid in (".generateQuestionMarks($this->fiere_ids).')', $params);
			$updateFiere = true;
		}
		if (is_array($this->fiere_ids) && count($this->fiere_ids) > 0 && !empty($contactId)) {
			$params = array($contactId);
			$params = array_merge($params, $this->fiere_ids);
			$adb->pquery("update {$table_prefix}_fiere set leadid = 0, contactid = ? where fieraid in (".generateQuestionMarks($this->fiere_ids).')', $params);
			$updateFiere = true;
		}
		// fix the modifiedtime
		if ($updateFiere) {
			$adb->pquery("update {$table_prefix}_crmentity set modifiedtime = ? where crmid in (".generateQuestionMarks($this->fiere_ids).')', array(date('Y-m-d H:i:s'), $this->fiere_ids));
		}
		
		// convert telemarketings
		$updateTmk = false;
		if (is_array($this->tmk_ids) && count($this->tmk_ids) > 0 && !empty($accountId)) {
			$params = array($accountId);
			$params = array_merge($params, $this->tmk_ids);
			$adb->pquery("update {$table_prefix}_telemarketing set leadid = 0, accountid = ? where telemarketingid in (".generateQuestionMarks($this->tmk_ids).')', $params);
			$updateTmk = true;
		}
		if (is_array($this->tmk_ids) && count($this->tmk_ids) > 0 && !empty($contactId)) {
			$params = array($contactId);
			$params = array_merge($params, $this->tmk_ids);
			$adb->pquery("update {$table_prefix}_telemarketing set leadid = 0, contactid = ? where telemarketingid in (".generateQuestionMarks($this->tmk_ids).')', $params);
			$updateTmk = true;
		}
		// fix the modifiedtime
		if ($updateTmk) {
			$adb->pquery("update {$table_prefix}_crmentity set modifiedtime = ? where crmid in (".generateQuestionMarks($this->tmk_ids).')', array(date('Y-m-d H:i:s'), $this->tmk_ids));
		}
		//crmv@52391e
		
	}

}
