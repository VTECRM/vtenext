<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
require_once 'modules/Webforms/model/WebformsModel.php';
require_once 'modules/Webforms/Webforms.php';

class Webforms_Field_Model
{

    protected $data;

    function __construct($data = array())
    {
        $this->data = $data;
    }

    function setId($id)
    {
        $this->data["id"] = $id;
    }

    function setWebformId($webformid)
    {
        $this->data["webformid"] = $webformid;
    }

    function setFieldName($fieldname)
    {
        $this->data["fieldname"] = $fieldname;
    }

    function setNeutralizedField($fieldname, $fieldlabel = false)
    {
        // crmv@179954
        //$fieldlabel = str_replace(array(" ", "."), "_", $fieldlabel);
        $this->data["neutralizedfield"] = $fieldname;
        // crmv@179954e
    }

    function setEnabled($enabled)
    {
        $this->data["enabled"] = $enabled;
    }

    function setDefaultValue($defaultvalue)
    {
        if (is_array($defaultvalue)) {
            $defaultvalue = implode(" |##| ", $defaultvalue);
        }
        $this->data["defaultvalue"] = $defaultvalue;
    }

    function setRequired($required)
    {
        $this->data["required"] = $required;
    }

    //crmv@32257
    function setHidden($hidden)
    {
        $this->data["hidden"] = $hidden;
    }

    //crmv@32257e

    function getId()
    {
        return $this->data["id"];
    }

    function getWebformId()
    {
        return $this->data["webformid"];
    }

    function getFieldName()
    {
        return $this->data["fieldname"];
    }

    function getNeutralizedField()
    {
        $neutralizedfield = str_replace(array(" ", "."), "_", $this->data['neutralizedfield']); // crmv@179954
        return $neutralizedfield;
    }

    function getEnabled()
    {
        return $this->data["enabled"];
    }

    function getDefaultValue()
    {
        $data = $this->data["defaultvalue"];
        return $data;
    }

    function getRequired()
    {
        return $this->data["required"];
    }

    //crmv@32257
    function getHidden()
    {
        return $this->data["hidden"];
    }

    //crmv@32257e

    static function retrieveNeutralizedField($webformid, $fieldname)
    {
        global $adb, $table_prefix;
        $sql = "SELECT neutralizedfield FROM " . $table_prefix . "_webforms_field WHERE webformid=? and fieldname=?";
        $result = $adb->pquery($sql, array($webformid, $fieldname));
        //$model = false;
        if ($adb->num_rows($result)) {
            $neutralizedfield = $adb->query_result($result, 0, "neutralizedfield");
        }
        return $neutralizedfield;
    }
}

?>