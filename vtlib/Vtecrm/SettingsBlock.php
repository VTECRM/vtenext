<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

/**
 * Provides basic API to work with CRM Settings Blocks
 * @package vtlib
 */
class Vtecrm_SettingsBlock {
    /** ID of this field instance */
    var $id;
    var $label;
    var	$sequence = false;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Helper function to log messages
     * @param String Message to log
     * @param Boolean true appends linebreak, false to avoid it
     * @access private
     */
    static function log($message, $delim=true) {
        Vtecrm_Utils::Log($message, $delim);
    }

    static function getInstance($value) {
        global $adb, $table_prefix;
        $instance = false;

        $query = false;
        $queryParams = false;
        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM ".$table_prefix."_settings_blocks WHERE blockid=?";
            $queryParams = Array($value);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_settings_blocks WHERE label=?";
            $queryParams = Array($value);
        }
        $result = $adb->pquery($query, $queryParams);
        if($adb->num_rows($result)) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result));
        }
        return $instance;
    }

    /**
     * Initialize this instance
     * @param Array
     * @param Vtecrm_SettingsBlock Instance of block to which this field belongs
     * @access private
     */
    function initialize($valuemap) {
        $this->id = $valuemap['blockid'];
        $this->label = $valuemap['label'];
        $this->sequence = $valuemap['sequence'];
    }

    /**
     * Get unique id for this instance
     * @access private
     */
    function __getUniqueId() {
        global $adb, $table_prefix;
        return $adb->getUniqueID($table_prefix.'_settings_blocks');
    }

    /**
     * Get next sequence id to use within a block for this instance
     * @access private
     */
    function __getNextSequence() {
        global $adb;
        $result = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM ".$table_prefix."_settings_blocks",Array());
        $maxseq = 0;
        if($result && $adb->num_rows($result)) {
            $maxseq = $adb->query_result($result, 0, 'max_seq');
            $maxseq += 1;
        }
        return $maxseq;
    }

    /**
     * Save this field instance
     * @param Vtecrm_SettingsBlock Instance of block to which this field should be added.
     */
    function save() {
        if($this->id) $this->__update();
        else $this->__create();
        return $this->id;
    }

    function __create() {
        global $adb, $table_prefix;

        $this->id = $this->__getUniqueId();
        if(!$this->sequence) {
            $this->sequence = $this->__getNextSequence();
        }

        $sql ="INSERT INTO ".$table_prefix."_settings_blocks (blockid, label, sequence) 
				VALUES (?,?,?)";
        $params = Array($this->id, $this->label, $this->sequence);

        $adb->pquery($sql,$params);

        self::log("Creating Block $this->name ... DONE");
    }

    /**
     * Update this field instance
     * @access private
     * @internal TODO
     */
    function __update() {
        self::log("Updating Block $this->name ... TODO");
    }

    /**
     * Delete this field instance
     */
    function delete() {
        global $adb,$table_prefix;

        $adb->pquery("DELETE FROM ".$table_prefix."_settings_blocks WHERE blockid=?", Array($this->id));
        self::log("Deleteing Block $this->name ... DONE");
    }

    /**
     * Add field to this block
     * @param Vtecrm_SettingField Instance of field to add to this block.
     * @return Reference to this block instance
     */
    function addField($fieldInstance) {
        $fieldInstance->save($this);
        return $this;
    }
}
