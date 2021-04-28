<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');

/**
 * Provides API to work with CRM Menu
 * @package vtlib
 */
class Vtecrm_Menu {
    /** ID of this menu instance */
    var $id = false;
    var $label = false;
    var $sequence = false;
    var $visible = 0;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Initialize this instance
     * @param Array Map
     * @access private
     */
    function initialize($valuemap) {
        // crmv@167234
        $this->id       = $valuemap['parenttabid'];
        $this->label    = $valuemap['parenttab_label'];
        $this->sequence = $valuemap['sequence'];
        $this->visible  = $valuemap['visible'];
        // crmv@167234e
    }

    /**
     * Get relation sequence to use
     * @access private
     */
    function __getNextRelSequence() {
        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM ".$table_prefix."_parenttabrel WHERE parenttabid=?",
            Array($this->id));
        $maxseq = $adb->query_result($result, 0, 'max_seq');
        return ++$maxseq;
    }

    /**
     * Add module to this menu instance
     * @param Vtecrm_Module Instance of the module
     */
    function addModule($moduleInstance) {
        if($this->id) {
            global $adb,$table_prefix;
            $relsequence = $this->__getNextRelSequence();
            $adb->pquery("INSERT INTO ".$table_prefix."_parenttabrel (parenttabid,tabid,sequence) VALUES(?,?,?)",
                Array($this->id, $moduleInstance->id, $relsequence));
            $cache = Cache::getInstance('getMenuModuleList');
            $cache->clear();
            self::log("Added to menu $this->label ... DONE");
        } else {
            self::log("Menu could not be found!");
        }
        self::syncfile();
    }

    /**
     * Remove module from this menu instance.
     * @param Vtecrm_Module Instance of the module
     */
    function removeModule($moduleInstance) {
        if(empty($moduleInstance) || empty($moduleInstance)) {
            self::log("Module instance is not set!");
            return;
        }
        if($this->id) {
            global $adb,$table_prefix;
            $adb->pquery("DELETE FROM ".$table_prefix."_parenttabrel WHERE parenttabid = ? AND tabid = ?",
                Array($this->id, $moduleInstance->id));
            $cache = Cache::getInstance('getMenuModuleList');
            $cache->clear();
            self::log("Removed $moduleInstance->name from menu $this->label ... DONE");
        } else {
            self::log("Menu could not be found!");
        }
        self::syncfile();
    }

    /**
     * Detach module from menu
     * @param Vtecrm_Module Instance of the module
     */
    static function detachModule($moduleInstance) {
        global $adb,$table_prefix;
        $adb->pquery("DELETE FROM ".$table_prefix."_parenttabrel WHERE tabid=?", Array($moduleInstance->id));
        $adb->pquery("DELETE FROM tbl_s_menu_modules WHERE tabid=?", Array($moduleInstance->id));
        $cache = Cache::getInstance('getMenuModuleList');
        $cache->clear();
        self::log("Detaching from menu ... DONE");
        self::syncfile();
    }

    /**
     * Get instance of menu by label
     * @param String Menu label
     */
    static function getInstance($value) {
        global $adb,$table_prefix;
        $query = false;
        $instance = false;
        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM ".$table_prefix."_parenttab WHERE parenttabid=?";
        } else {
            $query = "SELECT * FROM ".$table_prefix."_parenttab WHERE parenttab_label=?";
        }
        $result = $adb->pquery($query, Array($value));
        if($adb->num_rows($result)) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result));
        }
        return $instance;
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
     * Synchronize the menu information to flat file
     * @access private
     */
    static function syncfile() {
        self::log("Updating parent_tabdata file ... STARTED");
        //crmv@18160
        if (VteSession::get('skip_recalculate'))
            return;
        //crmv@18160 end
        create_parenttab_data_file();
        self::log("Updating parent_tabdata file ... DONE");
    }
}
