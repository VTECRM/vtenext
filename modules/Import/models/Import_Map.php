<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;

class Import_Map {

	var $map;
	var $user;

	public function  __construct($map, $user) {
		global $table_prefix;
	
		$this->map = $map;
		$this->user = $user;
	}

	public static function getInstanceFromDb($row, $user) {
		$map = array();
		foreach($row as $key=>$value) {
			if($key == 'content') {
				$content = array();
				$pairs = explode("&", $value);
				foreach($pairs as $pair) {
					list($mappedName, $sequence) = explode("=", $pair);
					$mappedName = str_replace('/eq/', '=', $mappedName);
					$mappedName = str_replace('/amp/', '&', $mappedName);
					$content["$mappedName"] = $sequence;
				}
				$map[$key] = $content;
			// crmv@83878
			} elseif ($key == 'defaults' || $key == 'formats') {
				$map[$key] = Zend_Json::decode($value);
			// crmv@83878e
			} else {
				$map[$key] = $value;
			}
		}
		return new Import_Map($map, $user);
	}

	public static function markAsDeleted($mapId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery('UPDATE '.Import_Map::getGetTablename().' SET deleted=1 WHERE id=?', array($mapId));
	}

	public function getId() {
		$map = $this->map;
		return $map['id'];
	}

	public function getAllValues() {
		return $this->map;
	}

	public function getGetTablename() {
		global $table_prefix;
		return $table_prefix.'_import_maps';
	}

	public function getValue($key) {
		$map = $this->map;
		return $map[$key];
	}

	// crmv@83878
	public function getStringifiedContent($data = null) {
		if (is_null($data)) $data = $this->map['content'];
		if(empty($data)) return;
		$content = $data;
		$keyValueStrings = array();
		foreach($content as $key => $value) {
			$key = str_replace('=', '/eq/', $key);
			$key = str_replace('&', '/amp/', $key);
			$keyValueStrings[] = $key.'='.$value;
		}
		$stringifiedContent = implode('&', $keyValueStrings);
		return $stringifiedContent;
	}
	
	// crmv@136181
	public function getJsData() {
		$jsdata = array();
		// fix this stupid formats
		$mapping = array();
		$badmap = htmlspecialchars_decode($this->map[3]);
		$pairs = explode("&", $badmap);
		foreach($pairs as $pair) {
			list($mappedName, $sequence) = explode("=", $pair);
			$mappedName = str_replace('/eq/', '=', $mappedName);
			$mappedName = str_replace('/amp/', '&', $mappedName);
			$mapping[$mappedName] = $sequence;
		}
		$jsdata['mapping'] = $mapping;
		$defaults = htmlspecialchars_decode($this->map[4]);
		$defaults = Zend_Json::decode($defaults);
		$jsdata['defaults'] = $defaults;
		$formats = htmlspecialchars_decode($this->map[5]);
		$formats = Zend_Json::decode($formats);
		$jsdata['formats'] = $formats;
		return Zend_Json::encode($jsdata);
	}
	// crmv@136181e

	public function save() {
		$adb = PearDatabase::getInstance();

		$map = $this->getAllValues();
		//crmv@33544
		if ($adb->isMssql()) {
			$map['content'] = $this->getStringifiedContent($this->map['content']);
			$map['defaults'] = Zend_Json::encode($this->map['defaults']);
			$map['formats'] = Zend_Json::encode($this->map['formats']);
		} else {
			$map['content'] = $adb->getEmptyBlob();
			$map['defaults'] = $adb->getEmptyBlob();
			$map['formats'] = $adb->getEmptyBlob();
		}
		//crmv@33544e
		$map['id'] = $adb->getUniqueID(Import_Map::getGetTablename());
		$columnNames = array_keys($map);
		$columnValues = array_values($map);
		if(count($map) > 0) {
			$adb->pquery('INSERT INTO '.Import_Map::getGetTablename().' ('. implode(',',$columnNames).') VALUES ('. generateQuestionMarks($columnValues).')', array($columnValues));
			
			$where = "name='".$adb->sql_escape_string($this->getValue('name'))."' AND module='".$adb->sql_escape_string($this->getValue('module'))."'";
			$adb->updateBlob(Import_Map::getGetTablename(),"content",$where,$this->getStringifiedContent($this->map['content']));
			$adb->updateBlob(Import_Map::getGetTablename(),"defaults",$where,Zend_Json::encode($this->map['defaults']));
			$adb->updateBlob(Import_Map::getGetTablename(),"formats",$where,Zend_Json::encode($this->map['formats']));
		}
	}
	// crmv@83878e

	public static function getAllByModule($moduleName) {
		global $current_user;
		$adb = PearDatabase::getInstance();

		$result = $adb->pquery('SELECT * FROM '.Import_Map::getGetTablename().' WHERE deleted=0 AND module=?', array($moduleName));
		$noOfMaps = $adb->num_rows($result);

		$savedMaps = array();
		for($i=0; $i<$noOfMaps; ++$i) {
			$importMap = Import_Map::getInstanceFromDb($adb->query_result_rowdata($result, $i), $current_user);
			$savedMaps[$importMap->getId()] = $importMap;
		}

		return $savedMaps;
	}

}
?>