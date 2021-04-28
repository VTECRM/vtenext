<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@100731 */
require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');

class Processes_DetailViewProcessesAdvPerm {
	
	private $_name;
	private $title;
	protected $context = false;

	function __construct() {
		$this->_name = 'DetailViewProcessesAdvPerm';
		$this->title = getTranslatedString('LBL_PM_ADVANCED_PERMISSIONS_WIDGET','Settings');
	}

	function name() {
		return $this->_name;
	}

	function title() {
		return $this->title;
	}
	
	function getFromContext($key, $purify=false) {
		if ($this->context) {
			$value = $this->context[$key];
			if ($purify && !empty($value)) {
				$value = vtlib_purify($value);
			}
			return $value;
		}
		return false;
	}
	
	function process($context = false) {
		global $theme, $app_strings;
		$this->context = $context;
		$sourceRecordId = $this->getFromContext('ID', true);
		$smarty = new VteSmarty();

		$PMUtils = ProcessMakerUtils::getInstance();
		$resources = $PMUtils->getAdvancedPermissionsResources($sourceRecordId);
		$smarty->assign('RESOURCES', $resources);

		$smarty->display('modules/Processes/widgets/DetailViewProcessesAdvPerm.tpl');
	}
}