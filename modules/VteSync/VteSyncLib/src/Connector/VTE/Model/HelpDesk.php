<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\VTE\Model;

class HelpDesk extends GenericVTERecord {

	protected static $staticModule = 'HelpDesk';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'ticket_title' => 'subject',
		'ticketstatus' => 'status',
		'ticketcategories' => 'type',
		'ticketpriorities' => 'priority',
		'projectplanid' => 'projectid', // crmv@190016
		'projecttaskid' => 'projecttaskid', // crmv@190016
		'description' => 'description',
	);
	
	public static function fromRawData($data) {
		if (!empty($data['parent_id'])) {
			global $adb;
			$parent_id = vtws_getIdComponents($data['parent_id']);
			$referenceObject = \VtenextWebserviceObject::fromId($adb, $parent_id[0]);//crmv@207871
			if ($referenceObject->getEntityName() == 'Contacts') {
				self::$fieldMap['parent_id'] = 'contactid';
			} elseif ($referenceObject->getEntityName() == 'Accounts') {
				self::$fieldMap['parent_id'] = 'accountid';
			} elseif ($referenceObject->getEntityName() == 'Leads') {
				self::$fieldMap['parent_id'] = 'leadid';
			}
		}
		return parent::fromRawData($data);
	}
	
	public function toCommonRecord() {
		if (!empty($this->fields['parent_id'])) {
			global $adb;
			$parent_id = vtws_getIdComponents($this->fields['parent_id']);
			$referenceObject = \VtenextWebserviceObject::fromId($adb, $parent_id[0]);//crmv@207871
			if ($referenceObject->getEntityName() == 'Contacts') {
				self::$fieldMap['parent_id'] = 'contactid';
				self::$fieldMap['accountid'] = 'accountid';
				
				$focus = \CRMEntity::getInstance($referenceObject->getEntityName());
				$accountsObject = \VtenextWebserviceObject::fromName($adb, 'Accounts');//crmv@207871
				$this->fields['accountid'] = vtws_getId($accountsObject->getEntityId(), getSingleFieldValue($focus->table_name, 'accountid', $focus->table_index, $parent_id[1]));
			}
		}
		return parent::toCommonRecord();
	}
}