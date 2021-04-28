<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

/**
 * Class representing a record with common properties to be exchanged between connectors
 */
class CommonRecord {

	protected $id = array();		// per Connector
	protected $etags = array();		// per Connector
	protected $createdTime;			// DateTime object
	protected $modifiedTime;		// DateTime object
	protected $owner = array();		// per Connector
	protected $module;
	
	protected $fields = array(
		// to be defined for each module
	);
	
	// special types for some fields
	public static $fieldTypes = array(
		'Leads' => array(
			'source' => array('type' => 'picklist'),
		),
		'Contacts' => array(
			'accountid' => array('type' => 'reference', 'module' => 'Accounts'),
			'lifecyclestage' => array('type' => 'picklist'),
		),
		//crmv@182114
		'Campaigns' => array(
			'type' => array('type' => 'picklist'),
			'status' => array('type' => 'picklist'),
		),
		'Potentials' => array(
			'related_to' => array('type' => 'reference', 'module' => 'Accounts'),
			'campaignid' => array('type' => 'reference', 'module' => 'Campaigns'),
			'sales_stage' => array('type' => 'picklist'),
			'type' => array('type' => 'picklist'),
		),
		'HelpDesk' => array(
			'status' => array('type' => 'picklist'),
			'type' => array('type' => 'picklist'),
			'priority' => array('type' => 'picklist'),
			'contactid' => array('type' => 'reference', 'module' => 'Contacts'),
			'accountid' => array('type' => 'reference', 'module' => 'Accounts'),
			'leadid' => array('type' => 'reference', 'module' => 'Leads'),
			// crmv@190016
			'projectid' => array('type' => 'reference', 'module' => 'ProjectPlan'),
			'projecttaskid' => array('type' => 'reference', 'module' => 'ProjectTask'),
			// crmv@190016e
		),
		'Products' => array(
			'category' => array('type' => 'picklist'),
		),
		'Assets' => array(
			'status' => array('type' => 'picklist'),
			'accountid' => array('type' => 'reference', 'module' => 'Accounts'),
			'productid' => array('type' => 'reference', 'module' => 'Products'),
		),
		//crmv@182114e
		// crmv@190016
		'ProjectTask' => array(
			'projectid' => array('type' => 'reference', 'module' => 'ProjectPlan'),
		),
		'TicketComments' => array(
			'ticketid' => array('type' => 'reference', 'module' => 'HelpDesk'),
		),
		// crmv@190016e
		// crmv@195073
		'Targets_Contacts'=> array(
			'targetid' => array('type' => 'reference', 'module' => 'Targets'),
			'contactid' => array('type' => 'reference', 'module' => 'Contacts'),
		),
		// crmv@195073e
	);
	

	public function __construct($connector, $module, $id, $etag, $fields = array(), \DateTime $createdTime = null, \DateTime $modifiedTime = null) {
		$this->module = $module;
		$this->id[$connector] = $id;
		$this->etags[$connector] = $etag;
		$this->createdTime = $createdTime;
		$this->modifiedTime = $modifiedTime;
		$this->fields = $fields;
	}
	
	
	public function getId($connector) {
		return $this->id[$connector];
	}
	
	public function hasId($connector) {
		return array_key_exists($connector, $this->id);
	}
	
	public function setId($connector, $id) {
		$this->id[$connector] = $id;
	}
	
	public function clearId($connector) {
		unset($this->id[$connector]);
	}

	public function getIds() {
		return $this->id;
	}
	
	// returns first non empty id and connector
	public function getNonEmptyId() {
		foreach ($this->id as $con => $id) {
			if (!empty($id)) {
				return array('connector'=>$con, 'id'=>$id);
			}
		}
		return false;
	}
	
	
	public function setModule($m) {
		$this->module = $m;
	}
	
	public function getModule() {
		return $this->module;
	}
	
	
	public function setEtag($connector, $etag) {
		$this->etags[$connector] = $etag;
	}

	public function getEtag($connector) {
		return $this->etags[$connector];
	}
	
	public function getEtags() {
		return $this->etags;
	}
	
	public function setOwner($connector, $owner) {
		$this->owner[$connector] = $owner;
	}

	public function getOwner($connector) {
		return $this->owner[$connector];
	}
	
	
	public function isMoreRecent($model) {
		$ts1 = $this->modifiedTime->getTimestamp();
		$ts2 = $model->getModifiedTime()->getTimestamp();
		return ($ts1 >= $ts2);
	}
	
	public function getCreatedTime() {
		return $this->createdTime;
	}
	
	public function setModifiedTime($datetime) {
		$this->modifiedTime = $datetime;
	}

	public function getModifiedTime() {
		return $this->modifiedTime;
	}
	
	
	public function addField($fieldname, $value) {
		$this->fields[$fieldname] = $value;
	}

	public function getField($name) {
		return $this->fields[$name];
	}

	public function getFields() {
		return $this->fields;
	}
	
	public static function getFieldsByType($module, $type) {
		$list = array();
		if (is_array(self::$fieldTypes[$module])) {
			foreach (self::$fieldTypes[$module] as $fname => $info) {
				if ($info['type'] == $type) {
					$list[$fname] = $info;
				}
			}
		}
		
		return $list;
	}

}