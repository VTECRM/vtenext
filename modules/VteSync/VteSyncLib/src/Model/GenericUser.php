<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

abstract class GenericUser implements UserInterface {

	protected $id;
	protected $module = 'Users';
	protected $etag;
	protected $fields;
	
	protected $rawData = null;
	
	public function __construct($id, $etag, $fields) {
		$this->id = $id;
		$this->etag = $etag;
		$this->fields = $fields;
	}

	/**
	 * Example of implementation
	 */
	public static function fromRawData($data) {
		$fields = array(
			'username' => $data['username'],
		);
		$user = new static($data['id'], $data['etag'], $fields);
		$user->rawData = $data;
		return $user;
	}
	
	abstract public static function fromCommonUser(CommonUser $crecord);
	
	abstract public function toCommonUser();
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
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

}