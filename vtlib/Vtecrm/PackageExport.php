<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

include_once('vtlib/Vtecrm/Module.php');
include_once('vtlib/Vtecrm/Menu.php');
include_once('vtlib/Vtecrm/Event.php');
include_once('vtlib/Vtecrm/Zip.php');

/* crmv@104975 crmv@146434 */

/**
 * Provides API to package CRM module and associated files.
 * @package vtlib
 */
class Vtecrm_PackageExport {
    var $_export_tmpdir = 'cache/vtlib';
    var $_export_modulexml_filename = null;
    var $_export_modulexml_file = null;
    var $_export_write_mode = 'file';	// file/string write in a file or return a string
    var $_export_modulexml_string = '';

    /**
     * Constructor
     */
    function __construct() {
        if(is_dir($this->_export_tmpdir) === FALSE) {
            mkdir($this->_export_tmpdir);
        }
    }

    /** Output Handlers */

    /** @access private */
    function openNode($node,$delimiter="\n") {
        $this->__write("<$node>$delimiter");
    }
    /** @access private */
    function closeNode($node,$delimiter="\n") {
        $this->__write("</$node>$delimiter");
    }
    /** @access private */
    function outputNode($value, $node='') {
        if($node != '') $this->openNode($node,'');
        $this->__write($value);
        if($node != '') $this->closeNode($node);
    }
    /** @access private */
    function __write($value) {
        if ($this->_export_write_mode == 'file') {
            fwrite($this->_export_modulexml_file, $value);
        } else {
            $this->_export_modulexml_string .= $value;
        }
    }

    /**
     * Set the module.xml file path for this export and
     * return its temporary path.
     * @access private
     */
    function __getManifestFilePath() {
        if(empty($this->_export_modulexml_filename)) {
            // Set the module xml filename to be written for exporting.
            $this->_export_modulexml_filename = "manifest-".time().".xml";
        }
        return "$this->_export_tmpdir/$this->_export_modulexml_filename";
    }

    /**
     * Initialize Export
     * @access private
     */
    // crmv@80710
    function __initExport($module, $moduleInstance) {
        global $default_charset;
        if($moduleInstance->isentitytype) {
            // We will be including the file, so do a security check.
            Vtecrm_Utils::checkFileAccess("modules/$module/$module.php");
        }
        if ($this->_export_write_mode == 'file') {
            $this->_export_modulexml_file = fopen($this->__getManifestFilePath(), 'w');
        } elseif ($this->_export_write_mode == 'string') {
            $this->_export_modulexml_string = '';
        }
        $charset = (empty($default_charset) ? 'UTF-8' : $default_charset);
        $this->__write("<?xml version=\"1.0\" encoding=\"$charset\" ?>\n");
    }
    // crmv@80710e

    /**
     * Post export work.
     * @access private
     */
    function __finishExport() {
        if(!empty($this->_export_modulexml_file)) {
            if ($this->_export_write_mode == 'file') {
                fclose($this->_export_modulexml_file);
            }
            $this->_export_modulexml_file = null;
        }
    }

    function getManifestString() {
        return $this->_export_modulexml_string;
    }

    /**
     * Clean up the temporary files created.
     * @access private
     */
    function __cleanupExport() {
        if($this->_export_write_mode == 'file' && !empty($this->_export_modulexml_filename)) {
            unlink($this->__getManifestFilePath());
        }
    }

    /**
     * Export Module as a zip file.
     * @param Vtecrm_Module Instance of module
     * @param Path Output directory path
     * @param String Zipfilename to use
     * @param Boolean True for sending the output as download
     */
    function export($moduleInstance, $todir='', $zipfilename='', $directDownload=false) {

        $module = $moduleInstance->name;

        $this->__initExport($module, $moduleInstance);

        // Call module export function
        $this->export_Module($moduleInstance);

        $this->__finishExport();

        // Export as Zip
        if($zipfilename == '') $zipfilename = "$module-" . date('YmdHis') . ".zip";
        $zipfilename = "$this->_export_tmpdir/$zipfilename";

        $zip = new Vtecrm_Zip($zipfilename);
        // Add manifest file
        $zip->addFile($this->__getManifestFilePath(), "manifest.xml");
        SDK::db2FileLanguages($module);	//crmv@sdk-18430
        // Copy module directory
        $zip->copyDirectoryFromDisk("modules/$module");
        // Copy templates directory of the module (if any)
        if(is_dir("Smarty/templates/modules/$module"))
            $zip->copyDirectoryFromDisk("Smarty/templates/modules/$module", "templates");
        // Copy cron files of the module (if any)
        if(is_dir("cron/modules/$module"))
            $zip->copyDirectoryFromDisk("cron/modules/$module", "cron");
        SDK::exportPackage($module,$zip);	//crmv@sdk

        $zip->save();

        if($directDownload) {
            $zip->forceDownload($zipfilename);
            unlink($zipfilename);
        }
        $this->__cleanupExport();
    }

    /**
     * Export dependencies
     * @access private
     */
    function export_Dependencies($moduleInstance) {
        global $enterprise_current_version, $adb,$table_prefix;
        $moduleid = $moduleInstance->id;

        $sqlresult = $adb->query("SELECT * FROM ".$table_prefix."_tab_info WHERE tabid = $moduleid");
        $MinVersion = $enterprise_current_version;
        $MaxVersion = false;
        $noOfPreferences = $adb->num_rows($sqlresult);
        for($i=0; $i<$noOfPreferences; ++$i) {
            $prefName = $adb->query_result($sqlresult,$i,'prefname');
            $prefValue = $adb->query_result($sqlresult,$i,'prefvalue');
            if($prefName == 'vtenext_min_version') {//crmv@207991
                $MinVersion = $prefValue;//crmv@207991
            }
            if($prefName == 'vtenext_max_version') {//crmv@207991
                $MaxVersion = $prefValue;//crmv@207991
            }

        }

        $this->openNode('dependencies');
        $this->outputNode($MinVersion, 'vtenext_version');//crmv@207991
        if($MaxVersion !== false)	$this->outputNode($MaxVersion, 'vtenext_max_version');//crmv@207991
        $this->closeNode('dependencies');
    }

    function export_Info($moduleInstance) {
        global $adb, $table_prefix;
        $result = $adb->pquery("SELECT * FROM {$table_prefix}_tab_info WHERE tabid = ? and prefname not in (?,?)", array($moduleInstance->id,'vtenext_min_version','vtenext_max_version'));//crmv@207991
        if ($result && $adb->num_rows($result) > 0) {
            $this->openNode('info');
            while($row=$adb->fetchByAssoc($result)) {
                $this->outputNode($row['prefvalue'], $row['prefname']);
            }
            $this->closeNode('info');
        }
    }

    /**
     * Export Module Handler
     * @access private
     */
    function export_Module($moduleInstance, $version_changes=array()) {
        global $adb,$table_prefix,$cf_prefix; // crmv@195213

        $moduleid = $moduleInstance->id;

        $sqlresult = $adb->query("SELECT * FROM ".$table_prefix."_parenttabrel WHERE tabid = $moduleid");
        $parenttabid = $adb->query_result($sqlresult, 0, 'parenttabid');
        $menu = Vtecrm_Menu::getInstance($parenttabid);
        $parent_name = $menu->label;

        $sqlresult = $adb->query("SELECT * FROM ".$table_prefix."_tab WHERE tabid = $moduleid");
        $tabresultrow = $adb->fetch_array($sqlresult);

        $tabname = $tabresultrow['name'];
        $tablabel= $tabresultrow['tablabel'];
        $tabversion = isset($tabresultrow['version'])? $tabresultrow['version'] : false;

        $this->openNode('module');
        $this->outputNode(date('Y-m-d H:i:s'),'exporttime');
        $this->outputNode($tabname, 'name');
        $this->outputNode($tablabel, 'label');
        $this->outputNode($parent_name, 'parent');

        if(!$moduleInstance->isentitytype) {
            $this->outputNode('extension', 'type');
        }

        if($tabversion) {
            $this->outputNode($tabversion, 'version');
        }

        // Export dependency information
        $this->export_Dependencies($moduleInstance);

        // Export other informations
        $this->export_Info($moduleInstance);

        if (isset($cf_prefix) && $cf_prefix != '') $this->outputNode($cf_prefix,'cf_prefix'); // crmv@195213

        // Export module tables
        $this->export_Tables($moduleInstance);

        // Export module panels
        $this->export_Panels($moduleInstance);

        // Export module blocks without panel
        $this->export_Orphaned_Blocks($moduleInstance);

        // Export module filters
        $this->export_CustomViews($moduleInstance);

        // Export Sharing Access
        $this->export_SharingAccess($moduleInstance);

        // Export Events
        $this->export_Events($moduleInstance);

        // Export Actions
        $this->export_Actions($moduleInstance);

        // Export Related Lists
        $this->export_RelatedLists($moduleInstance);

        // Export Custom Links
        $this->export_CustomLinks($moduleInstance);

        SDK::exportXml($moduleInstance,$this);	//crmv@sdk crmv@154170

        if (!empty($version_changes)) {
            $this->openNode('version_changes');
            foreach($version_changes as $change) {
                $this->openNode('change');
                foreach($change as $k => $v) {
                    $this->outputNode($v, $k);
                }
                $this->closeNode('change');
            }
            $this->closeNode('version_changes');
        }

        $this->export_Modlights($moduleInstance);

        $this->closeNode('module');
    }

    /**
     * Export module base and related tables
     * @access private
     */
    function export_Tables($moduleInstance) {
        global $table_prefix;
        $_exportedTables = Array();

        $modulename = $moduleInstance->name;
        $this->openNode('tables');

        if($moduleInstance->isentitytype) {
            $focus = CRMEntity::getInstance($modulename);
            // Setup required module variables which is need for vtlib API's
            vtlib_setup_modulevars($modulename, $focus);
            $tables = Array ($focus->table_name);
            if(!empty($focus->groupTable)) $tables[] = $focus->groupTable[0];
            if(!empty($focus->customFieldTable)) $tables[] = $focus->customFieldTable[0];
            foreach($tables as $table) {
                //crmv@30456
                $table_real = $table;
                if(strpos($table, $table_prefix) !== false){
                    $table_real = str_replace($table_prefix.'_', 'TABLEPREFIX_', $table);
                }
                $this->openNode('table');
                $this->outputNode($table_real, 'name');
                $this->outputNode('<![CDATA['.Vtecrm_Utils::CreateTableSchema($table).']]>', 'sql');
                $this->closeNode('table');
                $_exportedTables[] = $table_real;
                //crmv@30456e
            }

        }

        // Now export table information recorded in schema file
        if(file_exists("modules/$modulename/schema.xml")) {
            $schema = simplexml_load_file("modules/$modulename/schema.xml");

            if(!empty($schema->tables) && !empty($schema->tables->table)) {
                foreach($schema->tables->table as $tablenode) {
                    $table = trim($tablenode->name);
                    //crmv@30456
                    $table_real = $table;
                    if(strpos($table, $table_prefix) !== false){
                        $table_real = str_replace($table_prefix.'_', 'TABLEPREFIX_', $table);
                    }
                    if(!in_array($table_real,$_exportedTables)) {
                        $this->openNode('table');
                        $this->outputNode($table_real, 'name');
                        $this->outputNode('<![CDATA['.Vtecrm_Utils::CreateTableSchema($table).']]>', 'sql');
                        $this->closeNode('table');
                        $_exportedTables[] = $table_real;
                    }
                    //crmv@30456e
                }
            }
        }
        $this->closeNode('tables');
    }

    /**
     * Export module panels with its related blocks
     * @access private
     */
    function export_Panels($moduleInstance) {
        global $adb, $table_prefix;
        $sqlresult = $adb->pquery("SELECT * FROM ".$table_prefix."_panels WHERE tabid = ? ORDER BY sequence ASC", Array($moduleInstance->id));
        $resultrows= $adb->num_rows($sqlresult);

        if(empty($resultrows)) return;

        $this->openNode('panels');
        for($index = 0; $index < $resultrows; ++$index) {
            $panelid    = $adb->query_result_no_html($sqlresult, $index, 'panelid');
            $panellabel = $adb->query_result($sqlresult, $index, 'panellabel');
            $this->openNode('panel');
            $this->outputNode($panellabel, 'label');
            // Export fields associated with the block
            $this->export_Blocks($moduleInstance, $panelid);
            $this->closeNode('panel'); // crmv@123480
        }
        $this->closeNode('panels'); // crmv@123480
    }

    /**
     * Export module blocks with its related fields
     * @access private
     */
    function export_Orphaned_Blocks($moduleInstance) {
        global $adb,$table_prefix;
        $sqlresult = $adb->pquery("SELECT * FROM ".$table_prefix."_blocks WHERE tabid = ? AND (panelid IS NULL OR panelid = 0)", Array($moduleInstance->id));
        $resultrows= $adb->num_rows($sqlresult);

        if(empty($resultrows)) return;

        $this->openNode('blocks');
        for($index = 0; $index < $resultrows; ++$index) {
            $blockid    = $adb->query_result_no_html($sqlresult, $index, 'blockid');
            $blocklabel = $adb->query_result_no_html($sqlresult, $index, 'blocklabel');
            $display_status = $adb->query_result_no_html($sqlresult, $index, 'display_status');
            $sequence = $adb->query_result_no_html($sqlresult, $index, 'sequence');
            $this->openNode('block');
            $this->outputNode($blocklabel, 'label');
            $this->outputNode($display_status, 'display_status');
            $this->outputNode($sequence, 'sequence');
            // Export fields associated with the block
            $this->export_Fields($moduleInstance, $blockid);
            $this->closeNode('block');
        }
        $this->closeNode('blocks');
    }

    /**
     * Export module blocks with its related fields
     * @access private
     */
    function export_Blocks($moduleInstance,$panelid) {
        global $adb,$table_prefix;
        $sqlresult = $adb->pquery("SELECT * FROM ".$table_prefix."_blocks WHERE tabid = ? AND panelid = ?", Array($moduleInstance->id, $panelid));
        $resultrows= $adb->num_rows($sqlresult);

        if(empty($resultrows)) return;

        $this->openNode('blocks');
        for($index = 0; $index < $resultrows; ++$index) {
            $blockid    = $adb->query_result_no_html($sqlresult, $index, 'blockid');
            $blocklabel = $adb->query_result_no_html($sqlresult, $index, 'blocklabel');
            $display_status = $adb->query_result_no_html($sqlresult, $index, 'display_status');
            $sequence = $adb->query_result_no_html($sqlresult, $index, 'sequence');
            $this->openNode('block');
            $this->outputNode($blocklabel, 'label');
            $this->outputNode($display_status, 'display_status');
            $this->outputNode($sequence, 'sequence');
            // Export fields associated with the block
            $this->export_Fields($moduleInstance, $blockid);
            $this->closeNode('block');
        }
        $this->closeNode('blocks');
    }

    /**
     * Export fields related to a module block
     * @access private
     */
    function export_Fields($moduleInstance, $blockid) {
        global $adb,$table_prefix;
        $fieldresult = $adb->pquery("SELECT * FROM {$table_prefix}_field WHERE tabid=? AND block=?", Array($moduleInstance->id, $blockid));
        $fieldcount = $adb->num_rows($fieldresult);
        if(empty($fieldcount)) return;

        $entityresult = $adb->pquery("SELECT * FROM ".$table_prefix."_entityname WHERE tabid=?", Array($moduleInstance->id));
        $entity_fieldname = $adb->query_result_no_html($entityresult, 0, 'fieldname'); // crmv@80710

        $this->openNode('fields');
        for($index = 0; $index < $fieldcount; ++$index) {
            $this->openNode('field');
            $fieldresultrow = $adb->fetchByAssoc($fieldresult, -1, false); // crmv@80710
            //crmv@30456
            if(strpos($fieldresultrow['tablename'], $table_prefix) !== false){
                $fieldresultrow['tablename']=str_replace($table_prefix.'_', 'TABLEPREFIX_', $fieldresultrow['tablename']);
            }
            //crmv@30456e

            $fieldname = $fieldresultrow['fieldname'];
            $uitype = $fieldresultrow['uitype'];
            $fieldid = $fieldresultrow['fieldid'];
            $this->outputNode($fieldname, 'fieldname');
            $this->outputNode($uitype,    'uitype');
            $this->outputNode($fieldresultrow['columnname'],'columnname');
            $this->outputNode($fieldresultrow['tablename'],     'tablename');
            $this->outputNode($fieldresultrow['generatedtype'], 'generatedtype');
            $this->outputNode('<![CDATA['.$fieldresultrow['fieldlabel'].']]>', 'fieldlabel'); //crmv@176087
            $this->outputNode($fieldresultrow['readonly'],      'readonly');
            $this->outputNode($fieldresultrow['presence'],      'presence');
            $this->outputNode($fieldresultrow['selected'],      'selected');
            $this->outputNode($fieldresultrow['sequence'],      'sequence');
            $this->outputNode($fieldresultrow['maximumlength'], 'maximumlength');
            $this->outputNode($fieldresultrow['typeofdata'],    'typeofdata');
            $this->outputNode($fieldresultrow['quickcreate'],   'quickcreate');
            $this->outputNode($fieldresultrow['quickcreatesequence'],   'quickcreatesequence');
            $this->outputNode($fieldresultrow['displaytype'],   'displaytype');
            $this->outputNode($fieldresultrow['info_type'],     'info_type');
            $this->outputNode('<![CDATA['.$fieldresultrow['helpinfo'].']]>', 'helpinfo');
            if(isset($fieldresultrow['masseditable'])) {
                $this->outputNode($fieldresultrow['masseditable'], 'masseditable');
            }

            // Export Entity Identifier Information
            if($fieldname == $entity_fieldname) {
                $this->openNode('entityidentifier');
                $this->outputNode($adb->query_result($entityresult, 0, 'entityidfield'),    'entityidfield');
                $this->outputNode($adb->query_result($entityresult, 0, 'entityidcolumn'), 'entityidcolumn');
                $this->closeNode('entityidentifier');
            }

            // Export picklist values for picklist fields
            if($uitype == '15' || $uitype == '16' || $uitype == '111' || $uitype == '33' || $uitype == '300' || ($uitype == '55' && $fieldresultrow['displaytype'] == 3)) { //crmv@172745
                //crmv@178307
                /*
                if($uitype == '16') {
                    $picklistvalues = vtlib_getPicklistValues($fieldname);
                } else {
                    $picklistvalues = vtlib_getPicklistValues_AccessibleToAll($fieldname);
                }
                */
                $picklistvalues = vtlib_getPicklistValues($fieldname);
                //crmv@178307e
                $this->openNode('picklistvalues');
                foreach($picklistvalues as $picklistvalue) {
                    $picklistvalue = html_entity_decode($picklistvalue,ENT_QUOTES,$default_charset);
                    $this->outputNode('<![CDATA['.$picklistvalue.']]>', 'picklistvalue');
                }
                $this->closeNode('picklistvalues');
            }

            // Export field to module relations
            if($uitype == '10') {
                $relatedmodres = $adb->pquery("SELECT * FROM ".$table_prefix."_fieldmodulerel WHERE fieldid=?", Array($fieldid));
                $relatedmodcount = $adb->num_rows($relatedmodres);
                if($relatedmodcount) {
                    $this->openNode('relatedmodules');
                    for($relmodidx = 0; $relmodidx < $relatedmodcount; ++$relmodidx) {
                        $this->outputNode($adb->query_result($relatedmodres, $relmodidx, 'relmodule'), 'relatedmodule');
                    }
                    $this->closeNode('relatedmodules');
                }
            }

            $fieldInstance = Vtecrm_Field::getInstance($fieldid);
            $info = $fieldInstance->getFieldInfo();
            if (!empty($info)) {
                $this->openNode('info');
                foreach($info as $k => $v) {
                    if ($uitype == '50' && $k == 'users') {
                        if (!empty($v)) {
                            foreach($v as &$user) $user = getUserName($user,false);
                        }
                    }
                    $this->outputNode('<![CDATA['.Zend_Json::encode($v).']]>',$k); //crmv@176511
                }
                $this->closeNode('info');
            }

            $this->closeNode('field');

        }
        $this->closeNode('fields');
    }

    /**
     * Export Custom views of the module
     * @access private
     */
    function export_CustomViews($moduleInstance) {
        global $adb,$table_prefix;

        $customviewres = $adb->pquery("SELECT * FROM ".$table_prefix."_customview WHERE entitytype = ?", Array($moduleInstance->name));
        $customviewcount=$adb->num_rows($customviewres);

        if(empty($customviewcount)) return;

        $this->openNode('customviews');
        for($cvindex = 0; $cvindex < $customviewcount; ++$cvindex) {

            $cvid = $adb->query_result($customviewres, $cvindex, 'cvid');

            $cvcolumnres = $adb->query("SELECT * FROM ".$table_prefix."_cvcolumnlist WHERE cvid=$cvid");
            $cvcolumncount=$adb->num_rows($cvcolumnres);

            $this->openNode('customview');

            $setdefault = $adb->query_result($customviewres, $cvindex, 'setdefault');
            $setdefault = ($setdefault == 1)? 'true' : 'false';

            $setmetrics = $adb->query_result($customviewres, $cvindex, 'setmetrics');
            $setmetrics = ($setmetrics == 1)? 'true' : 'false';

            $this->outputNode($adb->query_result_no_html($customviewres, $cvindex, 'viewname'), 'viewname'); // crmv@190559
            $this->outputNode($setdefault, 'setdefault');
            $this->outputNode($setmetrics, 'setmetrics');

            $this->openNode('fields');
            for($index = 0; $index < $cvcolumncount; ++$index) {
                $cvcolumnindex = $adb->query_result($cvcolumnres, $index, 'columnindex');
                $cvcolumnname = $adb->query_result($cvcolumnres, $index, 'columnname');
                $cvcolumnnames= explode(':', $cvcolumnname);
                $cvfieldname = $cvcolumnnames[2];

                $this->openNode('field');
                $this->outputNode($cvfieldname, 'fieldname');
                $this->outputNode($cvcolumnindex,'columnindex');

                $cvcolumnruleres = $adb->pquery("SELECT * FROM ".$table_prefix."_cvadvfilter WHERE cvid=? AND columnname=?",
                    Array($cvid, $cvcolumnname));
                $cvcolumnrulecount = $adb->num_rows($cvcolumnruleres);

                if($cvcolumnrulecount) {
                    $this->openNode('rules');
                    for($rindex = 0; $rindex < $cvcolumnrulecount; ++$rindex) {
                        $cvcolumnruleindex = $adb->query_result($cvcolumnruleres, $rindex, 'columnindex');
                        $cvcolumnrulecomp  = $adb->query_result($cvcolumnruleres, $rindex, 'comparator');
                        $cvcolumnrulevalue = $adb->query_result($cvcolumnruleres, $rindex, 'value');
                        $cvcolumnrulecomp  = Vtecrm_Filter::translateComparator($cvcolumnrulecomp, true);

                        $this->openNode('rule');
                        $this->outputNode($cvcolumnruleindex, 'columnindex');
                        $this->outputNode($cvcolumnrulecomp, 'comparator');
                        $this->outputNode($cvcolumnrulevalue, 'value');
                        $this->closeNode('rule');

                    }
                    $this->closeNode('rules');
                }

                $this->closeNode('field');
            }
            $this->closeNode('fields');

            $this->closeNode('customview');
        }
        $this->closeNode('customviews');
    }

    /**
     * Export Sharing Access of the module
     * @access private
     */
    function export_SharingAccess($moduleInstance) {
        global $adb,$table_prefix;

        $deforgshare = $adb->pquery("SELECT * FROM ".$table_prefix."_def_org_share WHERE tabid=?", Array($moduleInstance->id));
        $deforgshareCount = $adb->num_rows($deforgshare);

        if(empty($deforgshareCount)) return;

        $this->openNode('sharingaccess');
        if($deforgshareCount) {
            for($index = 0; $index < $deforgshareCount; ++$index) {
                $permission = $adb->query_result($deforgshare, $index, 'permission');
                $permissiontext = '';
                if($permission == '0') $permissiontext = 'public_readonly';
                if($permission == '1') $permissiontext = 'public_readwrite';
                if($permission == '2') $permissiontext = 'public_readwritedelete';
                if($permission == '3') $permissiontext = 'private';

                $this->outputNode($permissiontext, 'default');
            }
        }
        $this->closeNode('sharingaccess');
    }

    /**
     * Export Events of the module
     * @access private
     */
    function export_Events($moduleInstance) {
        $events = Vtecrm_Event::getAll($moduleInstance);
        if(!$events) return;

        $this->openNode('events');
        foreach($events as $event) {
            $this->openNode('event');
            $this->outputNode($event->eventname, 'eventname');
            $this->outputNode('<![CDATA['.$event->classname.']]>', 'classname');
            $this->outputNode('<![CDATA['.$event->filename.']]>', 'filename');
            $this->outputNode('<![CDATA['.$event->condition.']]>', 'condition');
            $this->closeNode('event');
        }
        $this->closeNode('events');
    }

    /**
     * Export actions (tools) associated with module.
     * TODO: Need to pickup values based on status for all user (profile)
     * @access private
     */
    function export_Actions($moduleInstance) {

        if(!$moduleInstance->isentitytype) return;

        global $adb,$table_prefix;
        $result = $adb->pquery('SELECT distinct(actionname) FROM '.$table_prefix.'_profile2utility, '.$table_prefix.'_actionmapping
			WHERE '.$table_prefix.'_profile2utility.activityid='.$table_prefix.'_actionmapping.actionid and tabid=?', Array($moduleInstance->id));

        if($adb->num_rows($result)) {
            $this->openNode('actions');
            while($resultrow = $adb->fetch_array($result)) {
                $this->openNode('action');
                $this->outputNode('<![CDATA['. $resultrow['actionname'] .']]>', 'name');
                $this->outputNode('enabled', 'status');
                $this->closeNode('action');
            }
            $this->closeNode('actions');
        }
    }

    /**
     * Export related lists associated with module.
     * @access private
     */
    function export_RelatedLists($moduleInstance) {

        if(!$moduleInstance->isentitytype) return;

        global $adb,$table_prefix;
        $result = $adb->pquery("SELECT * FROM ".$table_prefix."_relatedlists WHERE tabid = ?", Array($moduleInstance->id));
        if($adb->num_rows($result)) {
            $this->openNode('relatedlists');

            for($index = 0; $index < $adb->num_rows($result); ++$index) {
                $row = $adb->fetch_array($result);
                $this->openNode('relatedlist');

                $this->outputNode($row['name'], 'function');
                $this->outputNode($row['label'], 'label');
                $this->outputNode($row['sequence'], 'sequence');
                $this->outputNode($row['presence'], 'presence');

                $action_text = $row['actions'];
                if(!empty($action_text)) {
                    $this->openNode('actions');
                    $actions = explode(',', $action_text);
                    foreach($actions as $action) {
                        $this->outputNode($action, 'action');
                    }
                    $this->closeNode('actions');
                }

                $relModuleInstance = Vtecrm_Module::getInstance($row['related_tabid']);
                $this->outputNode($relModuleInstance->name, 'relatedmodule');

                $panels = $moduleInstance->getPanelsForRelatedList($row['relation_id']);
                if (!empty($panels)) {
                    $this->openNode('panels');
                    foreach($panels as $panel) {
                        $this->openNode('panel');
                        $this->outputNode($panel['label'], 'label');
                        $this->outputNode($panel['sequence'], 'sequence');
                        $this->closeNode('panel');
                    }
                    $this->closeNode('panels');
                }

                $this->closeNode('relatedlist');
            }

            $this->closeNode('relatedlists');
        }
    }

    /**
     * Export custom links of the module.
     * @access private
     */
    function export_CustomLinks($moduleInstance) {
        $customlinks = $moduleInstance->getLinks();
        if(!empty($customlinks)) {
            $this->openNode('customlinks');
            foreach($customlinks as $customlink) {
                $this->openNode('customlink');
                $this->outputNode($customlink->linktype, 'linktype');
                $this->outputNode($customlink->linklabel, 'linklabel');
                $this->outputNode("<![CDATA[$customlink->linkurl]]>", 'linkurl');
                $this->outputNode("<![CDATA[$customlink->linkicon]]>", 'linkicon');
                $this->outputNode($customlink->sequence, 'sequence');
                $this->closeNode('customlink');
            }
            $this->closeNode('customlinks');
        }
    }

    function export_Modlights($moduleInstance) {
        global $adb, $table_prefix;
        $result = $adb->pquery("select fieldname from {$table_prefix}_field where tabid = ? and uitype = ?", array($moduleInstance->id, 220));
        if ($result && $adb->num_rows($result) > 0) {
            $this->openNode('modlights');
            while($row=$adb->fetchByAssoc($result)) {
                $fieldname = $row['fieldname'];
                $modulelightid = str_replace('ml','',$fieldname);
                $modulelightname = 'ModLight'.$modulelightid;
                $this->export_Module(Vtecrm_Module::getInstance($modulelightname));
            }
            $this->closeNode('modlights');
        }
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
