<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

/**
 * Provides basic API to work with CRM Fields
 * @package vtlib
 */
class Vtecrm_FieldBasic {
    /** ID of this field instance */
    var $id;
    var $name;
    var $label = false;
    var $table = false;
    var $column = false;
    var $columntype = false;
    var $helpinfo = '';
    var $masseditable = 1; // Default: Enable massedit for field

    var $uitype = 1;
    var $typeofdata = 'V~O';
    var	$displaytype   = 1;

    var $generatedtype = 1;
    var	$readonly      = 1;
    var	$presence      = 2;
    var	$selected      = 0;
    var	$maximumlength = 100;
    var	$sequence      = false;
    var	$quickcreate   = 1;
    var	$quicksequence = false;
    var	$info_type     = 'BAS';

    var $block;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Initialize this instance
     * @param Array
     * @param Vtecrm_Module Instance of module to which this field belongs
     * @param Vtecrm_Block Instance of block to which this field belongs
     * @access private
     */
    function initialize($valuemap, $moduleInstance=false, $blockInstance=false) {
        $this->id = $valuemap['fieldid'];
        $this->name = $valuemap['fieldname'];
        $this->label= $valuemap['fieldlabel'];
        $this->column = $valuemap['columnname'];
        $this->table  = $valuemap['tablename'];
        $this->uitype = $valuemap['uitype'];
        $this->typeofdata = $valuemap['typeofdata'];
        $this->helpinfo = $valuemap['helpinfo'];
        $this->masseditable = $valuemap['masseditable'];
        /* crmv@146434
        if (isset($valuemap['displaytype'])) $this->displaytype = $valuemap['displaytype'];
        if (isset($valuemap['generatedtype'])) $this->generatedtype = $valuemap['generatedtype'];
        if (isset($valuemap['readonly'])) $this->readonly = $valuemap['readonly'];
        if (isset($valuemap['presence'])) $this->presence = $valuemap['presence'];
        if (isset($valuemap['selected'])) $this->selected = $valuemap['selected'];
        if (isset($valuemap['maximumlength'])) $this->maximumlength = $valuemap['maximumlength'];
        if (isset($valuemap['sequence'])) $this->sequence = $valuemap['sequence'];
        if (isset($valuemap['quickcreate'])) $this->quickcreate = $valuemap['quickcreate'];
        if (isset($valuemap['quickcreatesequence'])) $this->quicksequence = $valuemap['quickcreatesequence'];
        if (isset($valuemap['info_type'])) $this->info_type = $valuemap['info_type'];
        */
        $this->block= $blockInstance? $blockInstance : Vtecrm_Block::getInstance($valuemap['block'], $moduleInstance);
    }

    /** Cache (Record) the schema changes to improve performance */
    static $__cacheSchemaChanges = Array();

    /**
     * Initialize schema changes.
     * @access private
     */
    function __handleVteCoreSchemaChanges() {
        // Add helpinfo column to the vte_field table
        global $table_prefix;
        if(empty(self::$__cacheSchemaChanges[$table_prefix.'_field.helpinfo'])) {
            Vtecrm_Utils::AlterTable($table_prefix.'_field', 'helpinfo  X');
            self::$__cacheSchemaChanges[$table_prefix.'_field.helpinfo'] = true;
        }
    }

    /**
     * Get unique id for this instance
     * @access private
     */
    function __getUniqueId() {
        global $adb,$table_prefix;
        return $adb->getUniqueID($table_prefix.'_field');
    }

    /**
     * Get next sequence id to use within a block for this instance
     * @access private
     */
    function __getNextSequence() {
        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM ".$table_prefix."_field WHERE tabid=? AND block=?",
            Array($this->getModuleId(), $this->getBlockId()));
        $maxseq = 0;
        if($result && $adb->num_rows($result)) {
            $maxseq = $adb->query_result($result, 0, 'max_seq');
            $maxseq += 1;
        }
        return $maxseq;
    }

    /**
     * Get next quick create sequence id for this instance
     * @access private
     */
    function __getNextQuickCreateSequence() {
        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT MAX(quickcreatesequence) AS max_quickcreateseq FROM ".$table_prefix."_field WHERE tabid=?",
            Array($this->getModuleId()));
        $max_quickcreateseq = 0;
        if($result && $adb->num_rows($result)) {
            $max_quickcreateseq = $adb->query_result($result, 0, 'max_quickcreateseq');
            $max_quickcreateseq += 1;
        }
        return $max_quickcreateseq;
    }

    /**
     * Create this field instance
     * @param Vtecrm_Block Instance of the block to use
     * @access private
     */
    function __create($blockInstance) {
        $this->__handleVteCoreSchemaChanges();

        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $this->block = $blockInstance;

        $moduleInstance = $this->getModuleInstance();

        $this->id = $this->__getUniqueId();

        if(!$this->sequence) {
            $this->sequence = $this->__getNextSequence();
        }
        //crmv@fix quickcreate
        if ($this->quickcreate === ''){
            $this->quickcreate = 1;
        }
        //crmv@fix quickcreate end
        if($this->quickcreate != 1) { // If enabled for display
            if(!$this->quicksequence) {
                $this->quicksequence = $this->__getNextQuickCreateSequence();
            }
        } else {
            $this->quicksequence = 0;
        }

        // Initialize other variables which are not done
        if(!$this->table) $this->table = $moduleInstance->basetable;
        if(!$this->column) {
            $this->column = strtolower($this->name);
            if(!$this->columntype) $this->columntype = 'C(100)';
        }
        //crmv@27654
        if($this->table == $table_prefix.'_crmentity' && in_array($this->column,array('smcreatorid','smownerid','modifiedby','description','createdtime','modifiedtime','viewedtime'))) {
            $this->columntype = null;	//skip alter table
        }
        //crmv@27654e
        if(!$this->label) $this->label = $this->name;
        //crmv@fix quicksequence
        if ($this->quicksequence == '')
            $this->quicksequence = null;
        //crmv@fix quicksequence end
        //crmv@30456
        if(strpos($this->table, 'TABLEPREFIX') !== false){
            $this->table=str_replace('TABLEPREFIX', $table_prefix, $this->table);
        }
        //crmv@30456e
        $params = Array($this->getModuleId(), $this->id, $this->column, $this->table, $this->generatedtype,
            $this->uitype, $this->name, $this->label, $this->readonly, $this->presence, $this->selected,
            $this->maximumlength, $this->sequence, $this->getBlockId(), $this->displaytype, $this->typeofdata,
            $this->quickcreate, $this->quicksequence, $this->info_type, $this->helpinfo);
        $sql ="INSERT INTO ".$table_prefix."_field (tabid, fieldid, columnname, tablename, generatedtype,
			uitype, fieldname, fieldlabel, readonly, presence, selected, maximumlength, sequence,
			block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, helpinfo)
			VALUES (".generateQuestionMarks($params).")";
        $adb->pquery($sql,$params);
        // Set the field status for mass-edit (if set)
        $adb->pquery('UPDATE '.$table_prefix.'_field SET masseditable=? WHERE fieldid=?', Array($this->masseditable, $this->id));

        FieldUtils::invalidateCache($this->getModuleId()); // crmv@193294

        Vtecrm_Profile::initForField($this);

        if(!empty($this->columntype)) {
            Vtecrm_Utils::AlterTable($this->table, $this->column." ".$this->columntype);
        }

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDFIELD, $this->id); //crmv@49398
        self::log("Creating Field $this->name ... DONE");
        self::log("Module language mapping for $this->label ... CHECK");
    }

    /**
     * Update this field instance
     * @access private
     * @internal TODO
     */
    //crmv@146434 crmv@49398
    function __update() {
        global $adb, $table_prefix, $metaLogs;
        $update = array();
        if (isset($this->block)) $update['block'] = $this->block->id;
        if (isset($this->label)) $update['fieldlabel'] = $this->label;
        if (isset($this->helpinfo)) $update['helpinfo'] = $this->helpinfo;
        if (isset($this->masseditable)) $update['masseditable'] = $this->masseditable;
        if (isset($this->uitype)) $update['uitype'] = $this->uitype;
        if (isset($this->typeofdata)) $update['typeofdata'] = $this->typeofdata;
        if (isset($this->displaytype)) $update['displaytype'] = $this->displaytype;
        if (isset($this->generatedtype)) $update['generatedtype'] = $this->generatedtype;
        if (isset($this->readonly)) $update['readonly'] = $this->readonly;
        if (isset($this->presence)) $update['presence'] = $this->presence;
        if (isset($this->selected)) $update['selected'] = $this->selected;
        if (isset($this->maximumlength)) $update['maximumlength'] = $this->maximumlength;
        if (isset($this->sequence)) $update['sequence'] = $this->sequence;
        if (isset($this->quickcreate)) $update['quickcreate'] = $this->quickcreate;
        if (isset($this->quicksequence)) $update['quickcreatesequence'] = $this->quicksequence;
        if (isset($this->info_type)) $update['info_type'] = $this->info_type;
        if (!empty($update)) {
            $columns = array();
            foreach($update as $column => $value) $columns[] = "$column = ?";
            $adb->pquery("update {$table_prefix}_field set ".implode(', ',$columns)." where fieldid = ?", array($update,$this->id));

            FieldUtils::invalidateCache($this->getModuleId()); // crmv@193294
        }
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id);
        self::log("Updating Field $this->name ... DONE");
    }
    //crmv@146434e crmv@49398e

    /**
     * Delete this field instance
     * @access private
     */
    function __delete() {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        Vtecrm_Profile::deleteForField($this);

        $adb->pquery("DELETE FROM ".$table_prefix."_field WHERE fieldid=?", Array($this->id));

        FieldUtils::invalidateCache($this->getModuleId()); // crmv@193294

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELFIELD, $this->id, array('module'=>$this->getModuleName(),'fieldname'=>$this->name)); //crmv@49398
        self::log("Deleting Field $this->name ... DONE");
    }

    /**
     * Get block id to which this field instance is associated
     */
    function getBlockId() {
        return $this->block->id;
    }

    /**
     * Get module id to which this field instance is associated
     */
    function getModuleId() {
        return $this->block->module->id;
    }

    /**
     * Get module name to which this field instance is associated
     */
    function getModuleName() {
        return $this->block->module->name;
    }

    /**
     * Get module instance to which this field instance is associated
     */
    function getModuleInstance(){
        return $this->block->module;
    }

    /**
     * Save this field instance
     * @param Vtecrm_Block Instance of block to which this field should be added.
     */
    function save($blockInstance=false) {
        if($this->id) $this->__update();
        else $this->__create($blockInstance);
        return $this->id;
    }

    /**
     * Delete this field instance
     */
    function delete() {
        $this->__delete();
    }

    /**
     * Set Help Information for this instance.
     * @param String Help text (content)
     */
    function setHelpInfo($helptext) {
        // Make sure to initialize the core tables first
        $this->__handleVteCoreSchemaChanges();

        global $adb,$table_prefix;
        $adb->pquery('UPDATE '.$table_prefix.'_field SET helpinfo=? WHERE fieldid=?', Array($helptext, $this->id));

        FieldUtils::invalidateCache($this->getModuleId()); // crmv@193294

        self::log("Updated help information of $this->name ... DONE");
    }

    /**
     * Set Masseditable information for this instance.
     * @param Integer Masseditable value
     */
    function setMassEditable($value) {
        global $adb,$table_prefix;
        $adb->pquery("UPDATE ".$table_prefix."_field SET masseditable=? WHERE fieldid=?", Array($value, $this->id));

        FieldUtils::invalidateCache($this->getModuleId()); // crmv@193294

        self::log("Updated masseditable information of $this->name ... DONE");
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
}
