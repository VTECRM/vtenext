<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');
include_once('vtlib/Vtecrm/FieldBasic.php');

/**
 * Provides APIs to control CRM Field
 * @package vtlib
 */
class Vtecrm_Field extends Vtecrm_FieldBasic {

    /**
     * Get unique picklist id to use
     * @access private
     */
    function __getPicklistUniqueId() {
        global $adb,$table_prefix;
        return $adb->getUniqueID($table_prefix.'_picklist');
    }

    /**
     * Set values for picklist field (for all the roles)
     * @param Array List of values to add.
     *
     * @internal Creates picklist base if it does not exists
     */
    function setPicklistValues($values) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        // Non-Role based picklist values
        if($this->uitype == '16') {
            $this->setNoRolePicklistValues($values);
            return;
        }

        $picklist_table = $table_prefix.'_'.$this->name;
        $picklist_idcol = $this->name.'id';

        if(!Vtecrm_Utils::CheckTable($picklist_table)) {
            Vtecrm_Utils::CreateTable(
                $picklist_table,
                "$picklist_idcol I(19) NOTNULL PRIMARY ,
				$this->name C(200) NOTNULL,
				presence I(1) NOTNULL DEFAULT 1,
				picklist_valueid I(19) NOT NULL DEFAULT 0",
                true);
            $new_picklistid = $this->__getPicklistUniqueId();
            $adb->pquery("INSERT INTO ".$table_prefix."_picklist (picklistid,name) VALUES(?,?)",Array($new_picklistid, $this->name));
            self::log("Creating table $picklist_table ... DONE");
        } else {
            $new_picklistid = $adb->query_result(
                $adb->pquery("SELECT picklistid FROM ".$table_prefix."_picklist WHERE name=?", Array($this->name)), 0, 'picklistid');

            // crmv@44323
            // check id column for old stupid tables
            $picklist_idcol_alt = $this->name.'_id';
            $allcols = $adb->getColumnNames($picklist_table);
            if (!in_array($picklist_idcol, $allcols) && in_array($picklist_idcol_alt, $allcols)) $picklist_idcol = $picklist_idcol_alt;
            // crmv@44323e
            if (!in_array($picklist_idcol, $allcols) && $this->name == 'salutationtype') $picklist_idcol = 'salutationid';	//crmv@55200
        }

        //crmv@64964
        $roles = array();
        $result = $adb->query("SELECT roleid FROM {$table_prefix}_role");
        while($row=$adb->fetchByAssoc($result)) $roles[] = $row['roleid'];

        // Add value to picklist now
        foreach($values as $value) {
            $new_picklistvalueid = getUniquePicklistID();
            $presence = 1; // 0 - readonly, Refer function in include/ComboUtil.php
            $new_id = $adb->getUniqueID($picklist_table);
            $adb->pquery("INSERT INTO $picklist_table($picklist_idcol, $this->name, presence, picklist_valueid) VALUES(?,?,?,?)",
                Array($new_id, $value, $presence, $new_picklistvalueid));

            // Associate picklist values to all the role
            foreach($roles as $roleid) {
                $res = $adb->pquery("select max(sortid)+1 as sortid from {$table_prefix}_role2picklist left join $picklist_table on $picklist_table.picklist_valueid = {$table_prefix}_role2picklist.picklistvalueid where roleid = ? and picklistid = ?", array($roleid, $new_picklistid));
                // crmv@69568
                $sortid = intval($adb->query_result_no_html($res, 0, 'sortid'));
                if ($sortid == 0) $sortid = 1;
                // crmv@69568
                $adb->pquery("INSERT INTO {$table_prefix}_role2picklist(roleid, picklistvalueid, picklistid, sortid) VALUES (?,?,?,?)",
                    Array($roleid, $new_picklistvalueid, $new_picklistid, $sortid));
            }
        }
        //crmv@64964e

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398
    }

    /**
     * Set values for picklist field (non-role based)
     * @param Array List of values to add
     *
     * @internal Creates picklist base if it does not exists
     * @access private
     */
    function setNoRolePicklistValues($values) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398

        $picklist_table = $table_prefix.'_'.$this->name;
        $picklist_idcol = $this->name.'id';

        if(!Vtecrm_Utils::CheckTable($picklist_table)) {
            // crmv@155585
            Vtecrm_Utils::CreateTable(
                $picklist_table,
                "$picklist_idcol I(19) NOTNULL PRIMARY,
				$this->name C(200) NOTNULL,
				sortorderid I(11),
				presence I(1) NOTNULL DEFAULT 1",
                true);
            // crmv@155585e
            self::log("Creating table $picklist_table ... DONE");
        }

        // Add value to picklist now
        $sortid = 1;
        foreach($values as $value) {
            $presence = 1; // 0 - readonly, Refer function in include/ComboUtil.php
            $new_id = $adb->getUniqueId($picklist_table);
            $adb->pquery("INSERT INTO $picklist_table($picklist_idcol, $this->name, sortorderid, presence) VALUES(?,?,?,?)",
                Array($new_id, $value, $sortid, $presence));

            $sortid = $sortid+1;
        }
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398
    }

    /**
     * Set relation between field and modules (UIType 10)
     * @param Array List of module names
     *
     * @internal Creates table vte_fieldmodulerel if it does not exists
     */
    function setRelatedModules($moduleNames) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        // We need to create core table to capture the relation between the field and modules.
        Vtecrm_Utils::CreateTable(
            $table_prefix.'_fieldmodulerel',
            'fieldid I(11) NOTNULL,
			module C(100) NOTNULL,
			relmodule C(100) NOTNULL,
			status C(10),
			sequence I(11)',
            true
        );
        // END


        foreach($moduleNames as $relmodule) {
            $checkres = $adb->pquery('SELECT * FROM '.$table_prefix.'_fieldmodulerel WHERE fieldid=? AND module=? AND relmodule=?',
                Array($this->id, $this->getModuleName(), $relmodule));

            // If relation already exist continue
            if($adb->num_rows($checkres)) continue;

            $adb->pquery('INSERT INTO '.$table_prefix.'_fieldmodulerel(fieldid, module, relmodule) VALUES(?,?,?)',
                Array($this->id, $this->getModuleName(), $relmodule));

            self::log("Setting $this->name relation with $relmodule ... DONE");
        }
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398
        return true;
    }

    /**
     * Remove relation between the field and modules (UIType 10)
     * @param Array List of module names
     */
    function unsetRelatedModules($moduleNames) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        foreach($moduleNames as $relmodule) {
            $adb->pquery('DELETE FROM '.$table_prefix.'_fieldmodulerel WHERE fieldid=? AND module=? AND relmodule = ?',
                Array($this->id, $this->getModuleName(), $relmodule));

            Vtecrm_Utils::Log("Unsetting $this->name relation with $relmodule ... DONE");
        }
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398
        return true;
    }

    //crmv@146434
    function getFieldInfo() {
        global $adb, $table_prefix;
        $info = array();
        $fieldinfo = $adb->pquery("select info from {$table_prefix}_fieldinfo where fieldid = ?", array($this->id));
        if ($fieldinfo && $adb->num_rows($fieldinfo) > 0) {
            $info = Zend_Json::decode($adb->query_result_no_html($fieldinfo,0,'info'));
        }
        return $info;
    }
    //crmv@146434e

    //crmv@106857
    function setFieldInfo($fieldinfo) {
        global $adb, $table_prefix;
        $result = $adb->pquery("select fieldid from {$table_prefix}_fieldinfo where fieldid = ?", array($this->id));
        if ($result && $adb->num_rows($result) > 0) {
            if (empty($fieldinfo)) {
                $adb->pquery("delete from {$table_prefix}_fieldinfo where fieldid = ?", array($this->id));
            } else {
                $adb->pquery("update {$table_prefix}_fieldinfo set info = ? where fieldid = ?", array(Zend_Json::encode($fieldinfo), $this->id));
            }
        } elseif (!empty($fieldinfo)) {
            $adb->pquery("insert into {$table_prefix}_fieldinfo(fieldid,info) values(?,?)", array($this->id, Zend_Json::encode($fieldinfo)));
        }
    }
    //crmv@106857e

    /**
     * Get Vtecrm_Field instance by fieldid or fieldname
     * @param mixed fieldid or fieldname
     * @param Vtecrm_Module Instance of the module if fieldname is used
     */
    static function getInstance($value, $moduleInstance=false) {
        global $adb,$table_prefix;
        $instance = false;

        $query = false;
        $queryParams = false;
        if(Vtecrm_Utils::isNumber($value)) {
            $query = "SELECT * FROM ".$table_prefix."_field WHERE fieldid=?";
            $queryParams = Array($value);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_field WHERE fieldname=? AND tabid=?";
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
     * Get Vtecrm_Field instances related to block
     * @param Vtecrm_Block Instnace of block to use
     * @param Vtecrm_Module Instance of module to which block is associated
     */
    static function getAllForBlock($blockInstance, $moduleInstance=false) {
        global $adb,$table_prefix;
        $instances = false;

        $query = false;
        $queryParams = false;
        if($moduleInstance) {
            $query = "SELECT * FROM ".$table_prefix."_field WHERE block=? AND tabid=?";
            $queryParams = Array($blockInstance->id, $moduleInstance->id);
        } else {
            $query = "SELECT * FROM ".$table_prefix."_field WHERE block=?";
            $queryParams = Array($blockInstance->id);
        }
        $result = $adb->pquery($query, $queryParams);
        for($index = 0; $index < $adb->num_rows($result); ++$index) {
            $class = get_called_class() ?: get_class();
            $instance = new $class();
            $instance->initialize($adb->fetch_array($result), $moduleInstance, $blockInstance);
            $instances[] = $instance;
        }
        return $instances;
    }

    /**
     * Get Vtecrm_Field instances related to module
     * @param Vtecrm_Module Instance of module to use
     */
    static function getAllForModule($moduleInstance) {
        global $adb,$table_prefix;
        $instances = false;

        $query = "SELECT * FROM ".$table_prefix."_field WHERE tabid=?";
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

    /**
     * Delete fields associated with the module
     * @param Vtecrm_Module Instance of module
     * @access private
     */
    static function deleteForModule($moduleInstance) {
        global $adb,$table_prefix, $metaLogs; // crmv@49398
        $adb->pquery("DELETE FROM ".$table_prefix."_field WHERE tabid=?", Array($moduleInstance->id));

        FieldUtils::invalidateCache($moduleInstance->id); // crmv@193294

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITMODFIELDS, $moduleInstance->id, array('module'=>$moduleInstance->name)); // crmv@49398
        self::log("Deleting fields of the module ... DONE");
    }

    //crmv@18954
    function unsetPicklistValues($values) {
        global $adb,$table_prefix, $metaLogs; //crmv@49398

        $picklist_table = $table_prefix.'_'.$this->name;

        // Remove value to picklist now
        foreach($values as $value) {
            $adb->pquery("DELETE FROM $picklist_table WHERE $this->name = ?",Array($value));
        }
        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398
    }
    //crmv@18954e

    //crmv@21046
    function setPicklistValuesCSV($csv_name,$separator=',',$full_path='') {
        global $root_directory;
        if($full_path == '')
            $path=$root_directory.'cache/vtlib/';
        else
            $path=$full_path;

        if(is_file($path.$csv_name)){
            $handle = fopen($path.$csv_name, "r");
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                $values[]=$data[0];
            }
            fclose($handle);

            if(is_array($values))
                $this->setPicklistValues($values);
            else
                $this->setPicklistValues(array());
        }
    }
    //crmv@21046e

    //crmv@20534
    function setPicklistValuesDBQuery($query,$column2fetch='') {
        global $adb;

        if(!$query || $query == ''){
            self::log("No Query given for $this->name ... SKIP");
            return;
        }

        $result=$adb->query($query);
        if($result){
            while ($data=$adb->fetchByAssoc($result,-1,false)) {
                //se � stato fornito un column_name uso quello per estrarre i valori di picklist
                //altrimenti uso il nome del campo
                if($column2fetch=='')
                    $values[]=$data[$this->name];
                else
                    $values[]=$data[$column2fetch];
            }

            if(is_array($values))
                $this->setPicklistValues($values);
            else
                $this->setPicklistValues(array());
        }
        else{
            self::log("Bad Query given for $this->name ... SKIP");
            return;
        }
    }
    //crmv@20534e
    //crmv@36557
    function setMultiPicklistValues($values) {
        $pick_obj = new Picklistmulti(false,$this->getModuleName(),$this->name);
        foreach ($values as $code=>$arr){
            $edit_arr['code_system'] = 'empty';
            $edit_arr['code'] = $code;
            foreach ($pick_obj->languages as $language){
                $edit_arr[$language['prefix']] = $arr[$language['prefix']];
            }
            $pick_obj->addline($edit_arr);
        }
    }
    function setMultiPicklistValuesDBQuery($query,$columns2fetch=Array()) {
        global $adb;

        if(!$query || $query == ''){
            self::log("No Query given for $this->name ... SKIP");
            return;
        }
        $result=$adb->query($query);
        if($result){
            $first = true;
            while ($data=$adb->fetchByAssoc($result,-1,false)) {
                if ($first){
                    //se � stato fornito un column_name uso quello per estrarre i valori di picklist
                    if(!empty($columns2fetch)){
                        if (!isset($columns2fetch['code'])){
                            $usecode = $columns2fetch['value'];
                        }
                        else{
                            $usecode = $columns2fetch['code'];
                        }
                        $usevalue = $columns2fetch['value'];
                    }
                    else{
                        $fieldnames = array_keys($data);
                        $usecode = $fieldnames[0];
                        if (!isset($fieldnames[1])){
                            $usevalue = $fieldnames[0];
                        }else{
                            $usevalue = $fieldnames[1];
                        }
                    }
                    $first = false;
                }
                $values[$data[$usevalue]]=$data[$usevalue];
            }
            $pick_obj = new Picklistmulti(false,$this->getModuleName(),$this->name);
            foreach ($values as $code=>$value){
                $edit_arr['code_system'] = 'empty';
                $edit_arr['code'] = $code;
                foreach ($pick_obj->languages as $language){
                    $edit_arr[$language['prefix']] = $value;
                }
                $pick_obj->addline($edit_arr);
            }
        }
    }
    function setMultiPicklistValuesCSV($csv_name,$separator=',',$full_path='') {
        global $root_directory;
        if($full_path == '')
            $path=$root_directory.'cache/vtlib/';
        else
            $path=$full_path;

        if(is_file($path.$csv_name)){
            $handle = fopen($path.$csv_name, "r");
            $values = Array();
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                $values[$data[0]]=$data[1];
            }
            fclose($handle);
            $pick_obj = new Picklistmulti(false,$this->getModuleName(),$this->name);
            foreach ($values as $code=>$value){
                $edit_arr['code_system'] = 'empty';
                $edit_arr['code'] = $code;
                foreach ($pick_obj->languages as $language){
                    $edit_arr[$language['prefix']] = $value;
                }
                $pick_obj->addline($edit_arr);
            }
        }
    }
    //crmv@36557 e

    //crmv@110306
    function convertNoRolePicklist2Picklist() {
        global $adb,$table_prefix, $metaLogs;

        // Non-Role based picklist values
        if($this->uitype != '16') {
            self::log("{$this->name} is not a non role based picklist... SKIP");
            return;
        }

        $tablename = $table_prefix.'_'.$this->name;
        //get picklist values
        $values = array();
        $query = "SELECT {$this->name} FROM $tablename";
        $result=$adb->query($query);
        if($result){
            while ($data=$adb->fetchByAssoc($result,-1,false)) {
                $values[]=$data[$this->name];
            }

        }

        //add picklist_valueid into picklist table
        $columnname = 'picklist_valueid';
        $cols = $adb->getColumnNames($tablename);
        if (!in_array($columnname, $cols)) {
            $col = $columnname.' I(19) NOT NULL DEFAULT 0';
            $adb->alterTable($tablename, $col, 'Add_Column');
        }

        $new_picklistid = $this->__getPicklistUniqueId();
        $adb->pquery("INSERT INTO ".$table_prefix."_picklist (picklistid,name) VALUES(?,?)",Array($new_picklistid, $this->name));

        $roles = array();
        $result1 = $adb->query("SELECT roleid FROM {$table_prefix}_role");
        while($row=$adb->fetchByAssoc($result1)) $roles[] = $row['roleid'];

        // update picklist_valueid to picklist now
        foreach($values as $value) {
            $new_picklistvalueid = getUniquePicklistID();

            $adb->pquery("UPDATE $tablename set picklist_valueid=? WHERE {$this->name} =?",
                Array($new_picklistvalueid, $value));

            // Associate picklist values to all the role
            foreach($roles as $roleid) {
                $res = $adb->pquery("select max(sortid)+1 as sortid from {$table_prefix}_role2picklist left join $tablename on $tablename.picklist_valueid = {$table_prefix}_role2picklist.picklistvalueid where roleid = ? and picklistid = ?", array($roleid, $new_picklistid));
                // crmv@69568
                $sortid = intval($adb->query_result_no_html($res, 0, 'sortid'));
                if ($sortid == 0) $sortid = 1;
                // crmv@69568
                $adb->pquery("INSERT INTO {$table_prefix}_role2picklist(roleid, picklistvalueid, picklistid, sortid) VALUES (?,?,?,?)",
                    Array($roleid, $new_picklistvalueid, $new_picklistid, $sortid));
            }
        }

        $adb->pquery('UPDATE '.$table_prefix.'_field SET uitype=? WHERE fieldid=?', Array(15, $this->id));

        FieldUtils::invalidateCache($moduleInstance->id); // crmv@193294

        if ($metaLogs) $metaLogs->log($metaLogs::OPERATION_EDITFIELD, $this->id); // crmv@49398

        self::log("Migrating {$this->name} ... DONE");
    }
    //crmv@110306e

}
