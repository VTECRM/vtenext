<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

include_once('include/utils/utils.php');

/**
 * Provides few utility functions
 * @package vtlib
 */
class Vtecrm_Utils {

    /**
     * Check if given value is a number or not
     * @param mixed String or Integer
     */
    static function isNumber($value) {
        return is_numeric($value)? intval($value) == $value : false;
    }

    /**
     * Implode the prefix and suffix as string for given number of times
     * @param String prefix to use
     * @param Integer Number of times
     * @param String suffix to use (optional)
     */
    static function implodestr($prefix, $count, $suffix=false) {
        $strvalue = '';
        for($index = 0; $index < $count; ++$index) {
            $strvalue .= $prefix;
            if($suffix && $index != ($count-1)) {
                $strvalue .= $suffix;
            }
        }
        return $strvalue;
    }

    /**
     * Function to check the file access is made within web root directory.
     * @param String File path to check
     * @param Boolean False to avoid die() if check fails
     */
    static function checkFileAccess($filepath, $dieOnFail=true) {
        global $root_directory;

        // Set the base directory to compare with
        $use_root_directory = $root_directory;
        if(empty($use_root_directory)) {
            $use_root_directory = realpath(dirname(__FILE__).'/../../.');
        }

        //crmv@34862
        if (function_exists('realpath_nolinks'))
            $realfilepath = realpath_nolinks($filepath);
        else
            $realfilepath = realpath($filepath);
        if (!$realfilepath && $dieOnFail) die("Sorry! Attempt to access restricted file. ($filepath)");
        //crmv@34862e

        /** Replace all \\ with \ first */
        $realfilepath = str_replace('\\\\', '\\', $realfilepath);
        $rootdirpath  = str_replace('\\\\', '\\', $use_root_directory);

        /** Replace all \ with / now */
        $realfilepath = str_replace('\\', '/', $realfilepath);
        $rootdirpath  = str_replace('\\', '/', $rootdirpath);

        if(stripos($realfilepath, $rootdirpath) !== 0) {
            if($dieOnFail) {
                die("Sorry! Attempt to access restricted file.");
            }
            return false;
        }
        return true;
    }

    /**
     * Log the debug message
     * @param String Log message
     * @param Boolean true to append end-of-line, false otherwise
     */
    static function Log($message, $delimit=true) {
        global $Vtiger_Utils_Log, $log, $vtlib_Utils_Log;//crmv@208038

        $log->debug($message);
        if((!isset($Vtiger_Utils_Log) || $Vtiger_Utils_Log == false) && (!isset($vtlib_Utils_Log) || $vtlib_Utils_Log == false)) return;

        print_r($message);
        if($delimit) {
            if (php_sapi_name() == 'cli') echo "\n"; else echo "<BR>\n"; // crmv@64542
        }
    }

    /**
     * Escape the string to avoid SQL Injection attacks.
     * @param String Sql statement string
     */
    static function SQLEscape($value) {
        if($value == null) return $value;
        global $adb;
        return $adb->sql_escape_string($value);
    }

    /**
     * Check if table is present in database
     * @param String tablename to check
     */
    static function CheckTable($tablename) {
        global $adb;
        if ($adb->table_exist($tablename)) return true;
        return false;
    }

    /**
     * Create table (supressing failure)
     * @param String tablename to create
     * @param String table creation criteria like '(columnname columntype, ....)'
     * @param String Optional suffix to add during table creation
     * <br>
     * will be appended to CREATE TABLE $tablename SQL
     */
    static function CreateTable($tablename, $criteria, $suffixTableMeta=false, $temporary=false, $skipClearCache=false) {	//crmv@70475
        global $adb;
        //if table exist return
        //crmv@70475
        global $globSkipClearCache;
        $globSkipClearCache = $skipClearCache;
        if(!$globSkipClearCache && $adb->table_exist($tablename)) return;
        //crmv@70475e
        $org_dieOnError = $adb->dieOnError;
        $adb->dieOnError = false;
        if($suffixTableMeta !== false) {
            if($suffixTableMeta === true) {
                $options['MYSQL'] = ' ENGINE=InnoDB DEFAULT CHARSET=utf8';
                if ($temporary)
                    $options['OCI8'] = 'ON COMMIT PRESERVE ROWS';
            }
        }
        $sql = $adb->datadict->CreateTableSQL($tablename,$criteria,$options,$temporary);
        if ($sql)
            $adb->datadict->ExecuteSQLArray($sql);
        $adb->dieOnError = $org_dieOnError;
        //crmv@47905bis		crmv@70475
        if (!$temporary && !$globSkipClearCache) {
            $cache = Cache::getInstance('table_exist');
            $cache->clear();
        }
        //crmv@47905bis e	crmv@70475e
    }

    /**
     * Alter existing table
     * @param String tablename to alter
     * @param String alter criteria like ' ADD columnname columntype' <br>
     * will be appended to ALTER TABLE $tablename SQL
     */
    static function AlterTable($tablename, $criteria) {
        global $adb;
        $sql = $adb->datadict->ChangeTableSQL($tablename,$criteria);
        if ($sql){
            $adb->datadict->ExecuteSQLArray($sql);
        }
    }

    /**
     * Add column to existing table
     * @param String tablename to alter
     * @param String columnname to add
     * @param String columntype (criteria like 'VARCHAR(100)')
     */
    static function AddColumn($tablename, $columnname, $criteria) {
        global $adb;
        if(!in_array($columnname, $adb->getColumnNames($tablename))) {
            self::AlterTable($tablename, " $columnname $criteria");	//crmv@fix
        }
    }

    /**
     * Get SQL query
     * @param String SQL query statement
     */
    static function ExecuteQuery($sqlquery, $supressdie=false) {
        global $adb;
        $old_dieOnError = $adb->dieOnError;

        if($supressdie) $adb->dieOnError = false;

        $adb->query($sqlquery);

        $adb->dieOnError = $old_dieOnError;
    }

    /**
     * Get SQL query
     * @param String axmls schema
     */
    static function ExecuteSchema($schema, $supressdie=false) {
        global $adb;
        $old_dieOnError = $adb->dieOnError;
        if($supressdie) $adb->dieOnError = false;
        $schema_obj = new adoSchema( $adb->database );
        $schema_obj->ParseSchemaString(trim($schema));
//		$adb->database->debug=true;
//		$schema_obj->debug =true;
        $schema_obj->ExecuteSchema(null,$supressdie);
        $adb->dieOnError = $old_dieOnError;
    }

    /**
     * Get CREATE SQL for given table
     * @param String tablename for which CREATE SQL is requried
     */
    static function CreateTableSql($tablename) {
        global $adb;

        $create_table = $adb->query("SHOW CREATE TABLE $tablename");
        $sql = decode_html($adb->query_result($create_table, 0, 1));
        return $sql;
    }
    /**
     * Get CREATE SCHEMA for given table
     * @param String tablename for which CREATE SCHEMA is requried
     */
    static function CreateTableSchema($tablename,$data=false) {	//crmv@fix
        global $adb;
        $schema_obj = new adoSchema( $adb->database );
        $sql = $schema_obj->ExtractSchema_singletable($tablename,$data);	//crmv@fix
        return $sql;
    }

    /**
     * Check if the given SQL is a CREATE statement
     * @param String SQL String
     */
    static function IsCreateSql($sql) {
        if(preg_match('/(CREATE TABLE)/', strtoupper($sql))) {
            return true;
        }
        return false;
    }

    /**
     * Check if the given SQL is destructive (DELETE's DATA)
     * @param String SQL String
     */
    static function IsDestructiveSql($sql) {
        if(preg_match('/(DROP TABLE)|(DROP COLUMN)|(DELETE FROM)/',
            strtoupper($sql))) {
            return true;
        }
        return false;
    }

    static function CreateIndex($name,$table,$fields,$options=false){ //crmv@118852
        global $adb;
        $sql = $adb->datadict->CreateIndexSQL($name,$table,$fields,$options); //crmv@118852
        if ($sql)
            $adb->datadict->ExecuteSQLArray($sql);
    }
}
