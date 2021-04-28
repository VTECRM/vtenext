<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */
/* crmv@104975 */

require_once('vtlib/Vtecrm/Utils.php');


/**
 * Provides API to work with CRM Module Blocks
 * @package vtlib
 */
class Vtecrm_Block {
    /** ID of this block instance */
    var $id;
    /** Label for this block instance */
    var $label;

    var $sequence;
    var $showtitle = 0;
    var $visible = 0;
    var $increateview = 0;
    var $ineditview = 0;
    var $indetailview = 0;

    var $module;

    public $panel;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Get unquie id for this instance
     * @access private
     */
    function __getUniqueId() {
        global $adb,$table_prefix;

        /** Sequence table was added from 5.1.0 */
        $maxblockid = $adb->getUniqueID($table_prefix.'_blocks');
        return $maxblockid;
    }

    /**
     * Get next sequence value to use for this block instance
     * @access private
     */
    function __getNextSequence() {
        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT MAX(sequence) as max_sequence from ".$table_prefix."_blocks where tabid = ?", Array($this->module->id));
        $maxseq = 0;
        if($adb->num_rows($result)) {
            $maxseq = $adb->query_result($result, 0, 'max_sequence');
        }
        return ++$maxseq;
    }

    /**
     * Initialize this block instance
     * @param Array Map of column name and value
     * @param Vtecrm Instance of module to which this block is associated
     * @access private
     */
    function initialize($valuemap, $moduleInstance=false) {
        $this->id = $valuemap['blockid']; // crmv@167234
        $this->label= $valuemap['blocklabel']; // crmv@167234
        $this->module=$moduleInstance? $moduleInstance: Vtecrm_Module::getInstance($valuemap['tabid']); // crmv@167234

        // crmv@185074
        // populate also other fields, otherwise updating the block might reset something!
        if (isset($valuemap['sequence'])) $this->sequence = $valuemap['sequence'];
        if (isset($valuemap['show_title'])) $this->showtitle = $valuemap['show_title'];
        if (isset($valuemap['visible'])) $this->visible = $valuemap['visible'];
        if (isset($valuemap['create_view'])) $this->increateview = $valuemap['create_view'];
        if (isset($valuemap['edit_view'])) $this->ineditview = $valuemap['edit_view'];
        if (isset($valuemap['detail_view'])) $this->indetailview = $valuemap['detail_view'];
        // crmv@185074e

        if ($valuemap['panelid']) {
            $this->panel = Vtecrm_Panel::getInstance($valuemap['panelid']);
        }
    }

    /**
     * Create CRM block
     * @access private
     */
    function __create($moduleInstance) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $this->module = $moduleInstance;

        $this->id = $this->__getUniqueId();
        if(!$this->sequence) $this->sequence = $this->__getNextSequence();

        $panelId = 0;
        if (!$this->panel) {
            $this->panel = Vtecrm_Panel::getFirstForModule($moduleInstance);
            // crmv@105426
            if ($this->panel) {
                $panelId = $this->panel->id;
            } else {
                // create a default one
                $this->panel = Vtecrm_Panel::createDefaultPanel($moduleInstance);
                if ($this->panel) {
                    $panelId = $this->panel->id;
                }
            }
            // crmv@105426e
        } else {
            $panelId = $this->panel->id;
        }

        $adb->pquery("INSERT INTO ".$table_prefix."_blocks(blockid,tabid,panelid,blocklabel,sequence,show_title,visible,create_view,edit_view,detail_view)
			VALUES(?,?,?,?,?,?,?,?,?,?)", Array($this->id, $this->module->id, $panelId, $this->label,$this->sequence,
            $this->showtitle, $this->visible,$this->increateview, $this->ineditview, $this->indetailview));

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDBLOCK, $this->id); // crmv@49398
        self::log("Creating Block $this->label ... DONE");
        self::log("Module language entry for $this->label ... CHECK");
    }

    /**
     * Update CRM block
     * @access private
     */
    function __update() {
        // crmv@185074
        global $adb, $table_prefix;
        $resultUpdateBlock = $adb->pquery("UPDATE {$table_prefix}_blocks SET
			tabid = ?,
			panelid = ?,
			blocklabel = ?,
			sequence = ?,
			show_title = ?,
			visible = ?,
			create_view = ?,
			edit_view = ?,
			detail_view = ?
			WHERE blockid = ?", Array(
                $this->module->id,
                $this->panel ? $this->panel->id : 0,
                $this->label,
                $this->sequence,
                $this->showtitle,
                $this->visible,
                $this->increateview,
                $this->ineditview,
                $this->indetailview,
                $this->id
            )
        );
        if ($adb->getAffectedRowCount($resultUpdateBlock) == 1) {
            self::log("Updating Block $this->label ... DONE");
        } else {
            self::log("Updating block $this->label ... FAILED: NOT FOUND");
        }
        // crmv@185074e
    }

    /**
     * Delete this instance
     * @access private
     */
    function __delete() {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        self::log("Deleting Block $this->label ... ", false);
        $adb->pquery("DELETE FROM ".$table_prefix."_blocks WHERE blockid=?", Array($this->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELBLOCK, $this->id, array('module'=>$this->module->name)); // crmv@49398
        self::log("DONE");
    }

    /**
     * Save this block instance
     * @param Vtecrm Instance of the module to which this block is associated
     */
    function save($moduleInstance=false) {
        if($this->id) $this->__update();
        else $this->__create($moduleInstance);
        return $this->id;
    }

    /**
     * Delete block instance
     * @param Boolean True to delete associated fields, False to avoid it
     */
    function delete($recursive=true) {
        if($recursive) {
            $fields = Vtecrm_Field::getAllForBlock($this);
            foreach($fields as $fieldInstance) $fieldInstance->delete($recursive);
        }
        $this->__delete();
    }

    /**
     * Add field to this block
     * @param Vtecrm_Field Instance of field to add to this block.
     * @return Reference to this block instance
     */
    function addField($fieldInstance) {
        $fieldInstance->save($this);
        return $this;
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

    /**
     * Get instance of block
     * @param mixed block id or block label
     * @param Vtecrm_Module Instance of the module if block label is passed
     */
    static function getInstance($value, $moduleInstance=false) {
        global $adb,$table_prefix;
        $instance = false;

        $query = false;
        $queryParams = false;
        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM ".$table_prefix."_blocks WHERE blockid=?";
            $queryParams = Array($value);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_blocks WHERE blocklabel=? AND tabid=?";
            $queryParams = Array($value, $moduleInstance->id);
        }
        $result = $adb->pquery($query, $queryParams);
        if($adb->num_rows($result)) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
        }
        return $instance;
    }

    /**
     * Get all block instances associated with the module
     * @param Vtecrm_Module Instance of the module
     */
    static function getAllForModule($moduleInstance) {
        global $adb, $table_prefix;
        $instances = false;

        $query = "SELECT * FROM ".$table_prefix."_blocks WHERE tabid=?";
        $queryParams = Array($moduleInstance->id);

        $result = $adb->pquery($query, $queryParams);
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
            $instances[] = $instance;
        }
        return $instances;
    }

    static function getAllForTab($tabInstance) {
        global $adb, $table_prefix;
        $instances = false;

        $query = "SELECT * FROM ".$table_prefix."_blocks WHERE panelid=?";
        $queryParams = Array($tabInstance->id);

        $class = get_called_class() ?: get_class();
        $result = $adb->pquery($query, $queryParams);
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $tabInstance->module, $tabInstance); // crmv@193294
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Delete all blocks associated with module
     * @param Vtecrm_Module Instnace of module to use
     * @param Boolean true to delete associated fields, false otherwise
     * @access private
     */
    static function deleteForModule($moduleInstance, $recursive=true) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        if($recursive) Vtecrm_Field::deleteForModule($moduleInstance);
        $adb->pquery("DELETE FROM ".$table_prefix."_blocks WHERE tabid=?", Array($moduleInstance->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFIELDS , $moduleInstance->id, array('module'=>$moduleInstance->name)); // crmv@49398
        self::log("Deleting blocks for module ... DONE");
    }

    //crmv@18954
    function moveHereFields($fieldnames){
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $tabid=$this->module->id;

        foreach($fieldnames as $fieldname){
            $query="UPDATE ".$table_prefix."_field SET block=? WHERE fieldname=? AND tabid=?";
            $params=array($this->id,$fieldname,$tabid);
            $adb->pquery($query,$params);
            self::log("Move $fieldname for $this->label ... DONE");
        }

        FieldUtils::invalidateCache($tabid); // crmv@193294

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFIELDS , $tabid, array('module'=>$this->module->name)); // crmv@49398
    }
    //crmv@18954e

    //crmv@146434
    function setDisplayStatus($display_status) {
        global $adb, $table_prefix, $metaLogs;
        $adb->pquery("update {$table_prefix}_blocks set display_status=? where blockid=?", array($display_status,$this->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITBLOCK, $this->id);
    }
    function setSequence($sequence) {
        global $adb, $table_prefix, $metaLogs;
        $adb->pquery("update {$table_prefix}_blocks set sequence=? where blockid=?", array($sequence,$this->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITBLOCK, $this->id);
    }
    function setPanel($panel) {
        global $adb, $table_prefix, $metaLogs;
        $adb->pquery("update {$table_prefix}_blocks set panelid=? where blockid=?", array($panel,$this->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITBLOCK, $this->id);
    }
    //crmv@146434e

    // crmv@198024
    /**
     * Moves the current block after the specified one
     */
    public function moveAfter(Vtecrm_Block $afterBlock) {
        global $adb, $table_prefix;

        $tabid = $this->module->id;
        $tabid2 = $afterBlock->module->id;
        if (empty($tabid) || $tabid != $tabid2) return;

        $blockid1 = intval($this->id);
        $sequence1 = intval($this->sequence);

        $blockid2 = intval($afterBlock->id);
        $sequence2 = intval($afterBlock->sequence);

        if ($blockid1 > 0 && $blockid2 > 0 && $sequence1 != $sequence2+1) {
            // get all blocks after this
            $next = $adb->database->GetAll("SELECT blockid FROM {$table_prefix}_blocks WHERE tabid = ? AND sequence > ?", array($tabid, $sequence2));
            $next = array_column($next, 'blockid');
            // increment their sequence
            if (count($next) > 0) {
                $adb->pquery("UPDATE {$table_prefix}_blocks set sequence = sequence + 1 WHERE blockid IN (".generateQuestionMarks($next).")", $next);
            }
            // move here this block
            $adb->pquery("UPDATE {$table_prefix}_blocks set sequence = ? WHERE tabid = ? AND blockid = ?", array($sequence2+1, $tabid, $blockid1));
        }
    }
    // crmv@198024e

}
