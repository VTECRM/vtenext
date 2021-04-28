<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/Webforms/model/WebformsFieldModel.php';

class Webforms_Model {

	public $data;
	protected $fields = array();

	function __construct($values = array()) {
		$this->setData($values);
	}

	protected function addField(Webforms_Field_Model $field) {
		$this->fields[] = $field;
	}

	function setData($data) {
		$this->data = $data;
		if (isset($data["fields"])) {
			$this->setFields(vtlib_purify($data["fields"]), vtlib_purify($data["required"]), $data["value"], vtlib_purify($data["hidden"]));	//crmv@31573	//crmv@32257
		}
		if (isset($data['id'])) {
			if (($data['enabled'] == 'on') || ($data['enabled'] == 1)) {
				$this->setEnabled(1);
			} else {
				$this->setEnabled(0);
			}
		} else {
			$this->setEnabled(1);
		}
	}

	function hasId() {
		return!empty($this->data['id']);
	}

	function setId($id) {
		$this->data["id"] = $id;
	}

	function setName($name) {
		$this->data["name"] = $name;
	}

	function setTargetModule($module) {
		$this->data["targetmodule"] = $module;
	}

	protected function setPublicId($publicid) {
		$this->data["publicid"] = $publicid;
	}

	function setEnabled($enabled) {
		$this->data["enabled"] = $enabled;
	}

	function setDescription($description) {
		$this->data["description"] = $description;
	}

	function setReturnUrl($returnurl) {
		$this->data["returnurl"] = $returnurl;
	}

	function setOwnerId($ownerid) {
		$this->data["ownerid"];
	}

	function setFields(array $fieldNames, $required, $value, $hidden) {	//crmv@32257
		require_once 'include/fields/DateTimeField.php';
		foreach ($fieldNames as $ind => $fieldname) {
			$fieldInfo = Webforms::getFieldInfo($this->getTargetModule(), $fieldname);
			$fieldModel = new Webforms_Field_Model();
			$fieldModel->setFieldName($fieldname);
			$fieldModel->setNeutralizedField($fieldname, $fieldInfo['label']);
			$field = Webforms::getFieldInfo('Leads', $fieldname);
			if (($field['type']['name'] == 'date')) {
				$defaultvalue = DateTimeField::convertToDBFormat($value[$fieldname]);
			}else if (($field['type']['name'] == 'boolean')){
				if(in_array($fieldname,$required)){
					if(empty($value[$fieldname])){
						$defaultvalue='off';
					}else{
						$defaultvalue='on';
					}
				}else{
					$defaultvalue=$value[$fieldname];
				}
			} else {
				$defaultvalue = vtlib_purify($value[$fieldname]);
			}
			$fieldModel->setDefaultValue($defaultvalue);
			if ((!empty($required) && in_array($fieldname, $required))) {
				$fieldModel->setRequired(1);
			} else {
				$fieldModel->setRequired(0);
			}
			//crmv@32257
			if ((!empty($hidden) && in_array($fieldname, $hidden))) {
				$fieldModel->setHidden(1);
			} else {
				$fieldModel->setHidden(0);
			}
			//crmv@32257e
			$this->addField($fieldModel);
		}
	}

	function getId() {
		return vtlib_purify($this->data["id"]);
	}

	function getName() {
		return html_entity_decode(vtlib_purify($this->data["name"]));
	}

	function getTargetModule() {
		return vtlib_purify($this->data["targetmodule"]);
	}

	function getPublicId() {
		return vtlib_purify($this->data["publicid"]);
	}

	function getEnabled() {
		return vtlib_purify($this->data["enabled"]);
	}

	function getDescription() {
		return vtlib_purify($this->data["description"]);
	}

	function getReturnUrl() {
		return vtlib_purify($this->data["returnurl"]);
	}

	function getOwnerId() {
		return vtlib_purify($this->data["ownerid"]);
	}

	function getFields() {
		return $this->fields;
	}

	function generatePublicId($name) {
		global $adb, $log;
		// crmv@167234
		$string = microtime(true)."".$name;
		$uid = md5($string);
		// crmv@167234e
		return $uid;
	}

	function retrieveFields() {
		global $adb,$table_prefix;
		$fieldsResult = $adb->pquery("SELECT * FROM ".$table_prefix."_webforms_field WHERE webformid=?", array($this->getId()));
		while ($fieldRow = $adb->fetch_array($fieldsResult)) {
			$this->addField(new Webforms_Field_Model($fieldRow));
		}
		return $this;
	}

	function save() {
		global $adb, $log,$table_prefix;

		$isNew = !$this->hasId();

		// Create?
		if ($isNew) {
			if (self::existWebformWithName($this->getName())) {
				throw new Exception('LBL_DUPLICATE_NAME');
			}
			$this->setPublicId($this->generatePublicId($this->getName()));
			$id = $adb->getUniqueID($table_prefix."_webforms");
			$insertSQL = "INSERT INTO ".$table_prefix."_webforms(id, name, targetmodule, publicid, enabled, description,ownerid,returnurl) VALUES(?,?,?,?,?,?,?,?)";
			$result = $adb->pquery($insertSQL, array($id, $this->getName(), $this->getTargetModule(), $this->getPublicid(), $this->getEnabled(), $this->getDescription(), $this->getOwnerId(), $this->getReturnUrl()));
			$this->setId($id);
		} else {
			// Update
			$updateSQL = "UPDATE ".$table_prefix."_webforms SET description=? ,returnurl=?,ownerid=?,enabled=? WHERE id=?";
			$result = $adb->pquery($updateSQL, array($this->getDescription(), $this->getReturnUrl(), $this->getOwnerId(), $this->getEnabled(), $this->getId()));
		}

		// Delete fields and re-add enabled once
		$adb->pquery("DELETE FROM ".$table_prefix."_webforms_field WHERE webformid=?", array($this->getId()));
		$fieldInsertSQL = "INSERT INTO ".$table_prefix."_webforms_field(id, webformid, fieldname, neutralizedfield, defaultvalue, required, hidden) VALUES(?,?,?,?,?,?,?)";	//crmv@32257
		foreach ($this->fields as $field) {
			$params = array();
			$params[] = $adb->getUniqueID($table_prefix."_webforms_field");
			$params[] = $this->getId();
			$params[] = $field->getFieldName();
			$params[] = $field->getNeutralizedField();
			$params[] = $field->getDefaultValue();
			$params[] = $field->getRequired();
			$params[] = $field->getHidden();	//crmv@32257
			$adb->pquery($fieldInsertSQL, $params);
		}
		return true;
	}

	function delete() {
		global $adb, $log,$table_prefix;

		$adb->pquery("DELETE from ".$table_prefix."_webforms_field where webformid=?", array($this->getId()));
		$adb->pquery("DELETE from ".$table_prefix."_webforms where id=?", array($this->getId()));
		return true;
	}

	static function retrieveWithPublicId($publicid) {
		global $adb, $log,$table_prefix;

		$model = false;
		// Retrieve model and populate information
		$result = $adb->pquery("SELECT * FROM ".$table_prefix."_webforms WHERE publicid=? AND enabled=?", array($publicid, 1));
		if ($adb->num_rows($result)) {
			$model = new Webforms_Model($adb->fetch_array($result));
			$model->retrieveFields();
		}
		return $model;
	}

	static function retrieveWithId($data) {
		global $adb, $log,$table_prefix;

		$id = $data;
		$model = false;
		// Retrieve model and populate information
		$result = $adb->pquery("SELECT * FROM ".$table_prefix."_webforms WHERE id=?", array($id));
		if ($adb->num_rows($result)) {
			$model = new Webforms_Model($adb->fetch_array($result));
			$model->retrieveFields();
		}
		return $model;
	}

	static function listAll() {
		global $adb, $log,$table_prefix;
		$webforms = array();

		$sql = "SELECT * FROM ".$table_prefix."_webforms";
		$result = $adb->pquery($sql, array());

		for ($index = 0, $len = $adb->num_rows($result); $index < $len; $index++) {
			$webform = new Webforms_Model($adb->fetch_array($result));
			$webforms[] = $webform;
		}


		return $webforms;
	}
	
	//crmv@162158
	static function isWebformFieldPermitted($fieldInstance) {
		static $gdprFieldsExtended = array();
		if (empty($gdprFieldsExtended)) {
			require_once('include/utils/GDPRWS/GDPRWS.php');
			$GDPRWS = GDPRWS::getInstance();
			foreacH($GDPRWS->gdprFields as $gdprField) {
				$gdprFieldsExtended[] = $gdprField;
				$gdprFieldsExtended[] = $gdprField.'_checkedtime';
				$gdprFieldsExtended[] = $gdprField.'_remote_addr';
			}
		}
		return (in_array($fieldInstance['name'],$gdprFieldsExtended) || ($fieldInstance['editable'] && $fieldInstance['type']['name'] != 'reference' && $fieldInstance['name'] != 'assigned_user_id'));
	}
	//crmv@162158e

	static function isWebformField($webformid, $fieldname) {
		global $adb, $log,$table_prefix;

		$checkSQL = "SELECT 1 from ".$table_prefix."_webforms_field where webformid=? AND fieldname=?";
		$result = $adb->pquery($checkSQL, array($webformid, $fieldname));
		return (($adb->num_rows($result)) ? true : false);
	}

	static function isCustomField($fieldname) {
		if (substr($fieldname, 0, 3) === "cf_") {
			return true;
		}
		return false;
	}

	static function isRequired($webformid, $fieldname) {
		global $adb,$table_prefix;
		$sql = "SELECT required FROM ".$table_prefix."_webforms_field where webformid=? AND fieldname=?";
		$result = $adb->pquery($sql, array($webformid, $fieldname));
		$required = false;
		if ($adb->num_rows($result)) {
			$required = $adb->query_result($result, 0, "required");
		}
		return $required;
	}

	static function retrieveDefaultValue($webformid, $fieldname) {
		require_once 'include/fields/DateTimeField.php';
		global $adb,$current_user,$current_;
		$dateformat=$current_user->date_format;
		global $table_prefix;
		$sql = "SELECT defaultvalue FROM ".$table_prefix."_webforms_field WHERE webformid=? and fieldname=?";
		$result = $adb->pquery($sql, array($webformid, $fieldname));
		$defaultvalue = false;
		if ($adb->num_rows($result)) {
			$defaultvalue = $adb->query_result($result, 0, "defaultvalue");
			$field = Webforms::getFieldInfo('Leads', $fieldname);
			if (($field['type']['name'] == 'date') && !empty($defaultvalue)) {
				$defaultvalue = DateTimeField::convertToUserFormat($defaultvalue);
			}
			$defaultvalue = explode(' |##| ', $defaultvalue);
		}
		return $defaultvalue;
	}

	static function existWebformWithName($name) {
		global $adb,$table_prefix;
		$checkSQL = "SELECT 1 FROM ".$table_prefix."_webforms WHERE name=?";
		$check = $adb->pquery($checkSQL, array($name));
		if ($adb->num_rows($check) > 0) {
			return true;
		}
		return false;
	}

	static function isActive($field, $mod) {
		global $adb,$table_prefix;
		$tabid = getTabid($mod);
		$query = 'SELECT 1 FROM '.$table_prefix.'_field WHERE fieldname = ?  AND tabid = ? AND presence IN (0,2)';
		$res = $adb->pquery($query, array($field, $tabid));
		$rows = $adb->num_rows($res);
		if ($rows > 0) {
			return true;
		}else
			return false;
	}
	
	//crmv@32257
	static function isHidden($webformid, $fieldname) {
		global $adb,$table_prefix;
		$sql = "SELECT hidden FROM ".$table_prefix."_webforms_field where webformid=? AND fieldname=?";
		$result = $adb->pquery($sql, array($webformid, $fieldname));
		$required = false;
		if ($adb->num_rows($result)) {
			$required = $adb->query_result($result, 0, "hidden");
		}
		return $required;
	}
	//crmv@32257e
}
?>