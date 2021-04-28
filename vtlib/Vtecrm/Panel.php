<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

/* crmv@104975 crmv@150751 */

require_once('vtlib/Vtecrm/Utils.php');

/**
 * Provides API to work with CRM Module Panels
 * @package vtlib
 */
class Vtecrm_Panel {

    /** ID of this panel instance */
    var $id;

    /** Label for this panel instance */
    var $label;

    var $sequence;

    var $visible = 0;

    var $module;

    var $record;

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
        $maxblockid = $adb->getUniqueID($table_prefix.'_panels');
        return $maxblockid;
    }

    /**
     * Get next sequence value to use for this block instance
     * @access private
     */
    function __getNextSequence() {
        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT MAX(sequence) as max_sequence from ".$table_prefix."_panels where tabid = ?", Array($this->module->id));
        $maxseq = 0;
        if($adb->num_rows($result)) {
            $maxseq = $adb->query_result_no_html($result, 0, 'max_sequence');
        }
        return ++$maxseq;
    }

    /**
     * Initialize this panel instance
     * @param Array Map of column name and value
     * @param Vtecrm_Module Instance of module to which this panel is associated
     * @access private
     */
    function initialize($valuemap, $moduleInstance=false, $record='') {
        $this->id = $valuemap['panelid'];
        $this->label= $valuemap['panellabel'];
        if (isset($valuemap['visible'])) {
            $this->visible = intval($valuemap['visible']);
        }
        $this->module = $moduleInstance ? $moduleInstance : Vtecrm_Module::getInstance($valuemap['tabid']);
        $this->record = $record;
    }

    /**
     * Create CRM tab
     * @access private
     */
    function __create($moduleInstance) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $this->module = $moduleInstance;

        $this->id = $this->__getUniqueId();
        if(!$this->sequence) $this->sequence = $this->__getNextSequence();

        $adb->pquery(
            "INSERT INTO ".$table_prefix."_panels (panelid,tabid,panellabel,sequence,visible)
			VALUES(?,?,?,?,?)",
            array($this->id, $this->module->id, $this->label,$this->sequence,$this->visible)
        );

        //if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDBLOCK, $this->id); // crmv@49398
        self::log("Creating Panel $this->label ... DONE");
    }

    /**
     * Update CRM tab
     * @access private
     * @internal
     */
    function __update() {
        global $adb,$table_prefix;
        self::log("Updating Panel $this->label ...", false);
        $adb->pquery(
            "UPDATE {$table_prefix}_panels SET panellabel = ?, visible = ? WHERE panelid = ?",
            array($this->label, $this->visible, $this->id)
        );
        self::log("DONE");
    }

    /**
     * Delete this instance
     * @access private
     */
    function __delete() {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        self::log("Deleting Panel $this->label ... ", false);
        $adb->pquery("DELETE FROM ".$table_prefix."_panels WHERE panelid = ?", Array($this->id));
        //if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELBLOCK, $this->id, array('module'=>$this->module->name)); // crmv@49398
        self::log("DONE");
    }

    /**
     * Save this panel instance
     * @param Vtecrm_Module Instance of the module to which this panel is associated
     */
    function save($moduleInstance=false) {
        if ($this->id) $this->__update();
        else $this->__create($moduleInstance);
        return $this->id;
    }

    /**
     * Delete panel instance
     */
    function delete($moveBlocksTo = false) {
        global $adb,$table_prefix;
        // move the blocks
        if ($moveBlocksTo) {
            if (is_numeric($moveBlocksTo)) {
                $moveid = $moveBlocksTo;
            } else {
                $moveid = $moveBlocksTo->id;
            }
            $adb->pquery("UPDATE {$table_prefix}_blocks SET panelid = ? WHERE panelid = ?", array($moveid, $this->id));
        }
        $this->__delete();
    }

    /**
     * Add block to this tab
     * @param Vtecrm_Block Instance of block to add to this tab.
     * @return Reference to this panel instance
     */
    function addBlock($blockInstance) {
        $blockInstance->panel = $this;
        $blockInstance->save($this->module);
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
     * Get instance of tab
     * @param mixed block id or block label
     * @param Vtecrm_Module Instance of the module if block label is passed
     */
    //crmv@39110
    static function getInstance($value, $moduleInstance=false, $record='') {
        global $adb,$table_prefix;
        $instance = false;
        $query = false;
        $queryParams = false;
        $table = $table_prefix.'_panels';
        $versionid_column = '';

        require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
        $PMUtils = ProcessMakerUtils::getInstance();
        $tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$moduleInstance->name,'id'));
        if (!empty($tvh_id)) {
            $table = $table_prefix.'_panels_vh';
            $versionid_column= "versionid = $tvh_id and";
        }

        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM $table WHERE $versionid_column panelid = ?";
            $queryParams = Array($value);
        } else {
            $query = "SELECT * FROM $table WHERE $versionid_column panellabel = ? AND tabid = ?";
            $queryParams = Array($value, $moduleInstance->id);
        }
        $result = $adb->pquery($query, $queryParams);
        if($adb->num_rows($result)) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance, $record);
        }
        return $instance;
    }

    /**
     * Get all tabs instances associated with the module
     * @param Vtecrm_Module Instance of the module
     */
    static function getAllForModule($moduleInstance, $record='') {
        global $adb, $table_prefix;
        $instances = false;

        require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
        $PMUtils = ProcessMakerUtils::getInstance();
        $tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$moduleInstance->name,'id'));
        if (!empty($tvh_id)) {
            $query = "SELECT * FROM ".$table_prefix."_panels_vh WHERE versionid = ? AND tabid = ? ORDER BY sequence ASC";
            $queryParams = Array($tvh_id, $moduleInstance->id);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_panels WHERE tabid = ? ORDER BY sequence ASC";
            $queryParams = Array($moduleInstance->id);
        }
        $class = get_called_class() ?: get_class();
        $result = $adb->pquery($query, $queryParams);
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance, $record);
            $instances[] = $instance;
        }
        return $instances;
    }

    static function getFirstForModule($moduleInstance, $record='') {
        global $adb, $table_prefix;
        $instance = false;

        require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
        $PMUtils = ProcessMakerUtils::getInstance();
        $tvh_id = $PMUtils->getSystemVersion4Record($record,array('tabs',$moduleInstance->name,'id'));
        if (!empty($tvh_id)) {
            $query = "SELECT * FROM ".$table_prefix."_panels_vh WHERE versionid = ? AND tabid = ? ORDER BY sequence ASC";
            $queryParams = Array($tvh_id, $moduleInstance->id);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_panels WHERE tabid = ? ORDER BY sequence ASC";
            $queryParams = Array($moduleInstance->id);
        }
        $result = $adb->limitpQuery($query, 0, 1, $queryParams);
        if ($result && $adb->num_rows($result) > 0) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance, $record);
        }

        return $instance;
    }
    //crmv@39110e

    // crmv@105426
    static function createDefaultPanel($moduleInstance) {
        $panelInstance = new Vtecrm_Panel();
        $panelInstance->label = 'LBL_TAB_MAIN';
        $moduleInstance->addPanel($panelInstance);
        return $panelInstance;
    }
    // crmv@105426e

    /**
     * Delete all tabs associated with module
     * @param Vtecrm_Module Instnace of module to use
     * @access private
     */
    //crmv@146434
    static function deleteForModule($moduleInstance) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        $adb->pquery("DELETE FROM ".$table_prefix."_panels WHERE tabid=?", Array($moduleInstance->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODPANELS, $moduleInstance->id, array('module'=>$moduleInstance->name)); // crmv@49398
        self::log("Deleting panels for module ... DONE");
    }
    //crmv@146434e

    function moveHereBlocks($blocknames){
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $tabid = $this->module->id;
        if (!is_array($blocknames)) $blocknames = array($blocknames);

        foreach($blocknames as $blockname){
            if (is_numeric($blockname)) {
                $query="UPDATE ".$table_prefix."_blocks SET panelid = ? WHERE blockid = ? AND tabid = ?";
            } else {
                $query="UPDATE ".$table_prefix."_blocks SET panelid = ? WHERE blocklabel = ? AND tabid = ?";
            }
            $params=array($this->id,$blockname,$tabid);
            $adb->pquery($query,$params);
            self::log("Move $blockname for $this->label ... DONE");
        }
        //if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFIELDS , $tabid, array('module'=>$this->module->name)); // crmv@49398
    }

    function getRelatedLists() {
        global $adb,$table_prefix;
        $list = array();

        require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
        $PMUtils = ProcessMakerUtils::getInstance();
        $tvh_id = $PMUtils->getSystemVersion4Record($this->record,array('tabs',$this->module->name,'id'));
        $vh_info = array(
            'panel2rlist' => array($table_prefix.'_panel2rlist pr','pr.panelid = ?',$this->id),
            'relatedlists' => array($table_prefix.'_relatedlists rl','rl.relation_id = pr.relation_id'),
            'tab' => array($table_prefix.'_tab rt','rt.tabid = rl.related_tabid'),
        );
        if (!empty($tvh_id)) {
            $vh_info['panel2rlist'] = array($table_prefix.'_panel2rlist_vh pr','pr.versionid = ? and pr.panelid = ?',array($tvh_id,$this->id));
            $vh_info['relatedlists'] = array($table_prefix.'_relatedlists_vh rl',$vh_info['relatedlists'][1].' and rl.versionid = pr.versionid');
            $vh_info['tab'] = array($table_prefix.'_tab_vh rt','rt.tabid = rl.related_tabid and rt.versionid = rl.versionid');
        }

        // check if exists
        $res = $adb->pquery(
            "SELECT pr.relation_id, pr.sequence, rt.name as module
			FROM {$vh_info['panel2rlist'][0]}
			INNER JOIN {$vh_info['relatedlists'][0]} ON {$vh_info['relatedlists'][1]}
			INNER JOIN {$vh_info['tab'][0]} ON {$vh_info['tab'][1]}
			WHERE {$vh_info['panel2rlist'][1]}
			ORDER BY pr.sequence ASC",
            array($vh_info['panel2rlist'][2])
        );
        if ($res && $adb->num_rows($res) > 0) {
            while ($row = $adb->fetchByAssoc($res, -1, false)) {
                $list[] = array(
                    'id' => $row['relation_id'],
                    'module' => $row['module'],
                    'sequence' => $row['sequence'],
                    'label' => getTranslatedString($row['module'], $row['module']),
                );
            }
        }

        return $list;
    }

    //crmv@146434
    function addRelatedList($relid, $seq=false) {
        global $adb,$table_prefix;

        // check if exists
        $res = $adb->pquery("SELECT panelid FROM {$table_prefix}_panel2rlist WHERE panelid = ? AND relation_id = ?", array($this->id, $relid));
        if ($res && $adb->num_rows($res) == 0) {
            if ($seq === false) {
                $rseq = $adb->pquery("SELECT MAX(sequence) as mseq FROM {$table_prefix}_panel2rlist WHERE panelid = ?", array($this->id));
                if ($rseq && $adb->num_rows($rseq) > 0) {
                    $seq = (int)$adb->query_result_no_html($rseq, 0, 'mseq') + 1;
                } else {
                    $seq = 1;
                }
            }
            $adb->pquery("INSERT INTO {$table_prefix}_panel2rlist (panelid, relation_id, sequence) VALUES (?,?,?)", array($this->id, $relid, $seq));
        } elseif ($seq !== false){	// update sequence
            $adb->pquery("UPDATE {$table_prefix}_panel2rlist SET sequence = ? WHERE panelid = ? AND relation_id = ?", array($seq, $this->id, $relid));
        }
    }
    //crmv@146434e

    function addRelatedLists($relids) {
        foreach ($relids as $relid) {
            $this->addRelatedList($relid);
        }
    }

    function removeRelatedList($relid) {
        global $adb,$table_prefix;

        $adb->pquery("DELETE FROM {$table_prefix}_panel2rlist WHERE panelid = ? AND relation_id = ?", array($this->id, $relid));
    }

    function setRelatedLists($relids) {
        global $adb,$table_prefix;

        // delete all
        $adb->pquery("DELETE FROM {$table_prefix}_panel2rlist WHERE panelid = ?", array($this->id));
        $this->addRelatedLists($relids);
    }

}
