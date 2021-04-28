<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');
include_once('vtlib/Vtecrm/Version.php');

/**
 * Provides API to work with CRM Custom View (Filter)
 * @package vtlib
 */
class Vtecrm_Filter {
    /** ID of this filter instance */
    var $id;
    var $name;
    var $isdefault;

    var $status    = false; // 5.1.0 onwards
    var $inmetrics = false;
    var $inmobile = false; // crmv@49398
    var $entitytype= false;

    var $module;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Get unique id for this instance
     * @access private
     */
    function __getUniqueId() {
        global $adb,$table_prefix;
        return $adb->getUniqueID($table_prefix.'_customview');
    }

    /**
     * Initialize this filter instance
     * @param Vtecrm_Module Instance of the module to which this filter is associated.
     * @access private
     */
    function initialize($valuemap, $moduleInstance=false) {
        $this->id = $valuemap['cvid'];
        $this->name= $valuemap['viewname'];
        $this->module=$moduleInstance? $moduleInstance: Vtecrm_Module::getInstance($valuemap['tabid']);
    }

    // crmv@49398
    /**
     * Create this instance
     * @param Vtecrm_Module Instance of the module to which this filter should be associated with
     * @access private
     */
    function __create($moduleInstance) {
        global $adb,$table_prefix, $metaLogs;
        $this->module = $moduleInstance;

        $this->id = $this->__getUniqueId();
        $this->isdefault = ($this->isdefault===true||$this->isdefault=='true')?1:0;
        $this->inmetrics = ($this->inmetrics===true||$this->inmetrics=='true')?1:0;
        $this->inmobile = ($this->inmobile===true||$this->inmobile=='true')?1:0;

        $adb->pquery("INSERT INTO ".$table_prefix."_customview(cvid,viewname,setdefault,setmetrics,setmobile,entitytype) VALUES(?,?,?,?,?,?)",
            Array($this->id, $this->name, $this->isdefault, $this->inmetrics, $this->inmobile, $this->module->name));

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_ADDFILTER, $this->id);
        self::log("Creating Filter $this->name ... DONE");

        // Filters are role based from 5.1.0 onwards
        if(!$this->status) {
            if(strtoupper(trim($this->name)) == 'ALL') $this->status = '0'; // Default
            else $this->status = '3'; // Public
            $adb->pquery("UPDATE ".$table_prefix."_customview SET status=? WHERE cvid=?", Array($this->status, $this->id));

            self::log("Setting Filter $this->name to status [$this->status] ... DONE");
        }
        // END
    }
    // crmv@49398e

    /**
     * Update this instance
     * @access private
     * @internal TODO
     */
    function __update() {
        self::log("Updating Filter $this->name ... DONE");
    }

    /**
     * Delete this instance
     * @access private
     */
    function __delete() {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        $adb->pquery("DELETE FROM ".$table_prefix."_cvadvfilter WHERE cvid=?", Array($this->id));
        $adb->pquery("DELETE FROM ".$table_prefix."_cvcolumnlist WHERE cvid=?", Array($this->id));
        $adb->pquery("DELETE FROM ".$table_prefix."_customview WHERE cvid=?", Array($this->id));
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_DELFILTER, $this->id, array('module'=>$this->module->name)); // crmv@49398
    }

    /**
     * Save this instance
     * @param Vtecrm_Module Instance of the module to use
     */
    function save($moduleInstance=false) {
        if($this->id) $this->__update();
        else $this->__create($moduleInstance);
        return $this->id;
    }

    /**
     * Delete this instance
     * @access private
     */
    function delete() {
        $this->__delete();
    }

    /**
     * Get the column value to use in custom view tables.
     * @param Vtecrm_Field Instance of the field
     * @access private
     */
    function __getColumnValue($fieldInstance) {
        $tod = explode('~', $fieldInstance->typeofdata);
        $displayinfo = $fieldInstance->getModuleName().'_'.str_replace(' ','_',$fieldInstance->label).':'.$tod[0];
        $cvcolvalue = "$fieldInstance->table:$fieldInstance->column:$fieldInstance->name:$displayinfo";
        return $cvcolvalue;
    }

    /**
     * Add the field to this filer instance
     * @param Vtecrm_Field Instance of the field
     * @param Integer Index count to use
     */
    function addField($fieldInstance, $index=0) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        // crmv@63472
        $res = $adb->pquery("SELECT MAX(columnindex) AS maxcolumnindex FROM {$table_prefix}_cvcolumnlist WHERE cvid = ?", array($this->id));
        if ($res && $adb->num_rows($res) > 0) {
            $row = $adb->fetchByAssoc($res, -1, false);
            $maxColumnIndex = intval($row["maxcolumnindex"]);
        } else {
            $maxColumnIndex = -1;
        }

        while ($maxColumnIndex >= $index) {
            $adb->pquery("UPDATE ".$table_prefix."_cvcolumnlist SET columnindex = columnindex+1 WHERE cvid=? AND columnindex=?", Array($this->id, $maxColumnIndex));
            $maxColumnIndex--;
        }
        // crmv@63472e

        $adb->pquery("INSERT INTO ".$table_prefix."_cvcolumnlist(cvid,columnindex,columnname) VALUES(?,?,?)", Array($this->id, $index, $cvcolvalue));

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFILTER, $this->id); // crmv@49398
        $this->log("Adding $fieldInstance->name to $this->name filter ... DONE");
        return $this;
    }

    /*
     * remove field from the columnlist (and from filters if 2nd param = true)
     */
    function removeField($fieldInstance, $from_filters = false) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        // get field index
        $res = $adb->pquery("SELECT columnindex as idx FROM ".$table_prefix."_cvcolumnlist WHERE cvid=? AND columnname=?", Array($this->id, $cvcolvalue));

        if ($res && $adb->num_rows($res) > 0) {
            // get index
            $index = $adb->query_result($res, 0, 'idx');

            // remove field
            $adb->pquery("DELETE FROM ".$table_prefix."_cvcolumnlist WHERE cvid=? AND columnname=?", Array($this->id, $cvcolvalue));
            if ($from_filters) {
                $adb->pquery("DELETE FROM ".$table_prefix."_cvadvfilter WHERE cvid=? AND columnname=?", Array($this->id, $cvcolvalue));
                $adb->pquery("DELETE FROM ".$table_prefix."_cvstdfilter WHERE cvid=? AND columnname=?", Array($this->id, $cvcolvalue));
            }

            // reorder indexes
            if ($index != '') {
                $adb->pquery("UPDATE ".$table_prefix."_cvcolumnlist SET columnindex=columnindex-1 WHERE cvid=? AND columnindex>?",Array($this->id, $index));
            }

        }

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFILTER, $this->id); // crmv@49398
        $this->log("Removing $fieldInstance->name to $this->name filter ... DONE");
        return $this;
    }

    /**
     * Add rule to this filter instance
     * @param Vtecrm_Field Instance of the field
     * @param String One of [EQUALS, NOT_EQUALS, STARTS_WITH, ENDS_WITH, CONTAINS, DOES_NOT_CONTAINS, LESS_THAN,
     *                       GREATER_THAN, LESS_OR_EQUAL, GREATER_OR_EQUAL]
     * @param String Value to use for comparision
     * @param Integer Index count to use
     */
    function addRule($fieldInstance, $comparator, $comparevalue, $index=0) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        if(empty($comparator)) return $this;

        $comparator = self::translateComparator($comparator);
        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        $adb->pquery("UPDATE ".$table_prefix."_cvadvfilter set columnindex=columnindex+1 WHERE cvid=? AND columnindex>=?",
            Array($this->id, $index));
        $adb->pquery("INSERT INTO ".$table_prefix."_cvadvfilter(cvid, columnindex, columnname, comparator, value) VALUES(?,?,?,?,?)",
            Array($this->id, $index, $cvcolvalue, $comparator, $comparevalue));

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFILTER, $this->id); // crmv@49398
        Vtecrm_Utils::Log("Adding Condition " . self::translateComparator($comparator,true) ." on $fieldInstance->name of $this->name filter ... DONE");

        return $this;
    }

    //crmv@26370
    /**
     * Add standard rule to this filter instance
     * @param Vtecrm_Field Instance of the field
     * @param String One of [custom ,prevfy, thisfy, nextfy, prevfq, thisfq, nextfq, yesterday, today,
     *      tomorrow, lastweek, thisweek, nextweek, lastmonth, thismonth, nextmonth,
     *      last7days, last30days, last60days, last90days, last120days, next30days,
     *      next60days, next90days, next120days]
     * @param String Start Date in case $duration is "custom"
     * @param String End Date in case $duration is "custom"
     * @param Bool Consider only month and day
     * TODO: do update if exists, check if field is a date field
     */
    function addStandardRule($fieldInstance, $duration, $date_start = null, $date_end = null, $only_month= false) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        $valid_durations = array(
            "custom", "prevfy", "thisfy", "nextfy", "prevfq", "thisfq", "nextfq", "yesterday",
            "today", "tomorrow", "lastweek", "thisweek", "nextweek", "lastmonth", "thismonth",
            "nextmonth", "last7days", "last30days", "last60days", "last90days", "last120days",
            "next30days", "next60days", "next90days", "next120days"
        );

        if(empty($duration)) return $this;

        if (!in_array($duration, $valid_durations)) {
            Vtecrm_Utils::log("Error adding standard condition, unknown duration : $duration");
            return $this;
        }

        if ($duration == 'custom' && (empty($date_start) || empty($date_end))) {
            Vtecrm_Utils::log("Error adding standard condition, dates cannot be null in custom mode");
            return $this;
        }

        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        // remove type
        $cols = explode(':', $cvcolvalue);
        if (strlen(end($cols)) == 1) {
            reset($cols);
            array_pop($cols);
            $cvcolvalue = implode(':', $cols);
        }

        $params = array($this->id, $cvcolvalue, $duration, $date_start, $date_end, intval($only_month));
        $res = $adb->pquery("INSERT INTO ".$table_prefix."_cvstdfilter(cvid, columnname, stdfilter, startdate, enddate, only_month_and_day) VALUES(?,?,?,?,?,?)", $params);

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFILTER, $this->id); // crmv@49398
        Vtecrm_Utils::Log("Adding Standard Condition $duration on {$fieldInstance->name} of {$this->name} filter ... DONE");

        return $this;
    }
    //crmv@26370e

    /**
     * Translate comparator (condition) to long or short form.
     * @access private
     * @internal Used from Vtecrm_PackageExport also
     */
    static function translateComparator($value, $tolongform=false) {
        $comparator = false;
        if($tolongform) {
            $comparator = strtolower($value);
            if($comparator == 'e') $comparator = 'EQUALS';
            else if($comparator == 'n') $comparator = 'NOT_EQUALS';
            else if($comparator == 's') $comparator = 'STARTS_WITH';
            else if($comparator == 'ew') $comparator = 'ENDS_WITH';
            else if($comparator == 'c') $comparator = 'CONTAINS';
            else if($comparator == 'k') $comparator = 'DOES_NOT_CONTAINS';
            else if($comparator == 'l') $comparator = 'LESS_THAN';
            else if($comparator == 'g') $comparator = 'GREATER_THAN';
            else if($comparator == 'm') $comparator = 'LESS_OR_EQUAL';
            else if($comparator == 'h') $comparator = 'GREATER_OR_EQUAL';
        } else {
            $comparator = strtoupper($value);
            if($comparator == 'EQUALS') $comparator = 'e';
            else if($comparator == 'NOT_EQUALS') $comparator = 'n';
            else if($comparator == 'STARTS_WITH') $comparator = 's';
            else if($comparator == 'ENDS_WITH') $comparator = 'ew';
            else if($comparator == 'CONTAINS') $comparator = 'c';
            else if($comparator == 'DOES_NOT_CONTAINS') $comparator = 'k';
            else if($comparator == 'LESS_THAN') $comparator = 'l';
            else if($comparator == 'GREATER_THAN') $comparator = 'g';
            else if($comparator == 'LESS_OR_EQUAL') $comparator = 'm';
            else if($comparator == 'GREATER_OR_EQUAL') $comparator = 'h';
        }
        return $comparator;
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
     * Get instance by filterid or filtername
     * @param mixed filterid or filtername
     * @param Vtecrm_Module Instance of the module to use when filtername is used
     */
    static function getInstance($value, $moduleInstance=false) {
        global $adb,$table_prefix;
        $instance = false;

        $query = false;
        $queryParams = false;
        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM ".$table_prefix."_customview WHERE cvid=?";
            $queryParams = Array($value);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_customview WHERE viewname=? AND entitytype=?";
            $queryParams = Array($value, $moduleInstance->name);
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
     * Get all instances of filter for the module
     * @param Vtecrm_Module Instance of module
     */
    static function getAllForModule($moduleInstance) {
        global $adb,$table_prefix;
        $instances = false;

        $query = "SELECT * FROM ".$table_prefix."_customview WHERE entitytype=?";
        $queryParams = Array($moduleInstance->name);

        $result = $adb->pquery($query, $queryParams);
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Delete filter associated for module
     * @param Vtecrm_Module Instance of module
     */
    static function deleteForModule($moduleInstance) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $cvidres = $adb->pquery("SELECT cvid FROM ".$table_prefix."_customview WHERE entitytype=?", Array($moduleInstance->name));
        if($adb->num_rows($cvidres)) {
            $cvids = Array();
            for($index = 0; $index < $adb->num_rows($cvidres); ++$index) {
                $cvids[] = $adb->query_result($cvidres, $index, 'cvid');
            }
            if(!empty($cvids)) {
                $adb->query("DELETE FROM ".$table_prefix."_cvadvfilter WHERE cvid  IN (" . implode(',', $cvids) . ")");
                $adb->query("DELETE FROM ".$table_prefix."_cvcolumnlist WHERE cvid IN (" . implode(',', $cvids) . ")");
                $adb->query("DELETE FROM ".$table_prefix."_customview WHERE cvid   IN (" . implode(',', $cvids) . ")");
                if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFILTERS, 0, array('module'=>$moduleInstance->name)); // crmv@49398
            }
        }
    }
}
