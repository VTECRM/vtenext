<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector;

abstract class BaseConnector implements ConnectorInterface {

	/**
	 * The name of the connector. Must match the class name
	 */
	static public $name;
	
	/**
	 * If true, no real modifications are made to server
	 */
	public $simulate = false;
	
	protected $modulesHandled = array();
	protected $supportEtag = true;
	protected $supportMeta = true;
	protected $config;
	protected $storage;
	protected $log;
	
	protected $classes = array(); // see an existing connector for the structure
	
	public function __construct($config = array(), $storage = null) {
		$this->config = $config;
		$this->storage = $storage;
		$this->log = new \VteSyncLib\Logger($config['loglevel']);
		$this->log->setPrefix('['.static::$name.']');
	}

	/*
	public $firstSync = false;

	protected $userMapping;*/

	abstract public function connect();
	abstract public function isConnected();
	
	abstract public function pull($module, $userinfo, \DateTime $date = null, $maxEntries = 100);
	abstract public function push($module, $userinfo, &$records);
	
	abstract public function getObject($module, $id);
	abstract public function setObject($module, $id, $object);
	abstract public function deleteObject($module, $id);

	/*public function setUserMapping($mapping) {
		$this->userMapping = $mapping;
	}*/

	public function canHandleModule($module) {
		return in_array($module, $this->modulesHandled);
	}
	
	public function supportEtag() {
		return $this->supportEtag;
	}
	
	public function supportMeta() {
		return $this->supportMeta;
	}
	
	public function getSyncID() {
		return $this->config['syncid'];
	}
	
	public function setStorage(\VteSyncLib\Storage\StorageInterface $storage) {
		$this->storage = $storage;
	}
	
	public function getStorage() {
		return $this->storage;
	}
	
	protected function getLocalModule($module) {
		$sobject = $this->classes[$module]['module'];
		return $sobject;
	}
	
	protected function getCommonClass($module) {
		$class = $this->classes[$module]['commonClass'];
		return $class;
	}
	
	protected function getModelClass($module) {
		$class = $this->classes[$module]['class'];
		return $class;
	}
	
	/**
	 * Merge the default fields in creation/update.
	 * The record param is a specific subclass of GenericUser/GenericRecord
	 */
	protected function mergeDefaults($module, &$record) {
		
		$id = $record->getId();
		$from = (empty($id) ? 'create' : 'update');
		$defaults = $this->config['defaults'][$module][$from];

		if (is_array($defaults)) {
			foreach ($defaults as $fname => $value) {
				if (!$record->hasField($fname) || ($from == 'create' && $record->getField($fname) == '')) {
					$record->addField($fname, $value);
				}
			}
		}
		
		return true;
	}
	
	// crmv@190016
	/**
	 * Merge the passed fields, regardless of the previous value
	 */
	protected function mergeForcedFields($module, &$record) {
		
		$id = $record->getId();
		$from = (empty($id) ? 'create' : 'update');
		$values = $this->config['forcefields'][$module][$from];

		if (is_array($values)) {
			foreach ($values as $fname => $value) {
				$record->addField($fname, $value);
			}
		}
		
		return true;
	}
	// crmv@190016e
	
	// convert meta changes to connector format
	protected function convertMetaChanges($module, &$changes) {
		$recordClass = $this->getModelClass($module);
		
		if (is_array($changes['picklist']['add'])) {
			$add = array();
			foreach ($changes['picklist']['add'] as $pname => $addValues) {
				$lname = $recordClass::getFieldName($pname);
				if ($lname) {
					$add[$lname] = $addValues;
				}
			}
			$changes['picklist']['add'] = $add;
		}

		return $changes;
	}
}