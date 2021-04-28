<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

class CommonUser {

	protected $id = array();		// per Connector
	protected $etags = array();		// per Connector
	protected $createdTime;			// DateTime object
	protected $modifiedTime;		// DateTime object
	protected $module = 'Users';
	
	// fields shared by every connector
	protected $fields = array(
		'username' => null,
		'title' => null,
		'lastname' => null,
		'firstname' => null,
		'email' => null,
		'phone' => null,
		'mobile' => null,
		'fax' => null,
		'department' => null,
		'active' => true,
		'timezone' => null,		// format: Europe/Rome
		'language' => null,		// format: it_IT / en_US
		// address
		'street' => null,
		'city' => null,
		'postalcode' => null,
		'state' => null,
		'country' => null,
	);

	public function __construct($connector, $module, $id, $etag, $fields = array(), \DateTime $createdTime = null, \DateTime $modifiedTime = null) {
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

}
