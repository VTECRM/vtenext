<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

abstract class GenericRecord implements RecordInterface {

	protected static $connector;		// to be defined in subclasses
	protected static $staticModule;		// to be defined in subclasses
	protected static $fieldMap; 		// = array(); // to be defined in subclasses

	protected $id;
	protected $module;
	protected $etag;
	protected $owner;
	
	protected $fields;
	protected $createdTime;
	protected $modifiedTime;
	
	protected $rawData = null;
	
	public function __construct($module, $id, $etag, $fields) {
		$this->module = $module;
		$this->id = $id;
		$this->etag = $etag;
		$this->fields = $fields;
	}

	// simple field-to-field mapping implementation
	public static function fromRawData($data) {
		$id = static::extractId($data);
		$ownerid = static::extractOwner($data);
		$creatTime = static::extractCreatedTime($data);
		$modTime = static::extractModifiedTime($data);
		$etag = static::extractEtag($data);
		
		$fields = array_intersect_key($data, static::$fieldMap);
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->owner = $ownerid;
		$record->rawData = $data;
		$record->createdTime = $creatTime;
		$record->modifiedTime = $modTime;
		
		return $record;
	}
	
	public static function fromCommonRecord(CommonRecord $crecord) {
		$id = $crecord->getId(static::$connector);
		$etag = $crecord->getEtag(static::$connector);
		$owner = $crecord->getOwner(static::$connector);
		$cfields = $crecord->getFields();
		$fields = array();
		foreach (static::$fieldMap as $sfname => $cname) {
			$value = static::fromCommonField($cname, $cfields[$cname], $crecord);
			$fields[$sfname] = $value;
		}
		$record = new static(static::$staticModule, $id, $etag, $fields);
		$record->setOwner($owner);

		return $record;
	}
	
	public function toCommonRecord() {
		if (empty($this->modifiedTime)) {
			return false;
		}
		$cfields = array();
		foreach (static::$fieldMap as $sfname => $cname) {
			$value = static::toCommonField($cname, $this->fields[$sfname], $this);
			$cfields[$cname] = $value;
		}
		$crecord = new CommonRecord(static::$connector, $this->module, $this->id, $this->etag, $cfields, $this->createdTime, $this->modifiedTime);
		$crecord->setOwner(static::$connector, $this->owner);
		return $crecord;
	}
	
	protected static function fromCommonField($cname, $value, CommonRecord $crecord = null) {
		$typeinfo = CommonRecord::$fieldTypes[static::$staticModule][$cname];
		if (!is_array($typeinfo)) $typeinfo = array('type' => 'string'); // if not set, treat it as simple text
		$type = $typeinfo['type'];
		
		if ($type == 'reference') {
			$value = $value[static::$connector]['id'];
		}
		
		return $value;
	}
	
	protected static function toCommonField($cname, $value, $self = null) {
		$typeinfo = CommonRecord::$fieldTypes[static::$staticModule][$cname];
		if (!is_array($typeinfo)) $typeinfo = array('type' => 'string'); // if not set, treat it as simple text
		$type = $typeinfo['type'];
		
		if ($type == 'reference') {
			// can hold multiple ids
			$value = array(
				static::$connector => array('id' => $value, 'module' => $typeinfo['module'])
			);
		}
		
		return $value;
	}
	
	public static function getCommonFieldName($name) {
		return static::$fieldMap[$name];
	}
	
	public static function getFieldName($cname) {
		$k = array_search($cname, static::$fieldMap);
		if ($k === false) return null;
		return $k;
	}
	
	public function toRawData($mode) {
		return $this->fields;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	
	public function setModule($m) {
		$this->module = $m;
	}
	
	public function getModule() {
		return $this->module;
	}
	
	
	public function setEtag($etag) {
		$this->etag = $etag;
	}
	
	public function getEtag() {
		return $this->etag;
	}
	
	
	public function setOwner($owner) {
		$this->owner = $owner;
	}

	public function getOwner() {
		return $this->owner;
	}
	
	
	public function hasField($name) {
		return array_key_exists($name, $this->fields);
	}
	
	public function getField($name) {
		return $this->fields[$name];
	}

	public function getFields() {
		return $this->fields;
	}
	
	public function addField($fieldname, $value) {
		$this->fields[$fieldname] = $value;
	}
		
	abstract public static function extractId($data);
	abstract public static function extractOwner($data);
	abstract public static function extractCreatedTime($data);
	abstract public static function extractModifiedTime($data);
	abstract public static function extractEtag($data);
	
}
