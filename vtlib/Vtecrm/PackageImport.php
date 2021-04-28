<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

include_once('vtlib/Vtecrm/PackageExport.php');
include_once('vtlib/Vtecrm/Unzip.php');
include_once('vtlib/Vtecrm/Module.php');
include_once('vtlib/Vtecrm/Event.php');

/* crmv@104975 crmv@146434 */

/**
 * Provides API to import module into Vtenext
 * @package vtlib
 */
class Vtecrm_PackageImport extends Vtecrm_PackageExport {

    /**
     * Module Meta XML File (Parsed)
     * @access private
     */
    var $_modulexml;
    /**
     * Module Fields mapped by [modulename][fieldname] which
     * will be used to create customviews.
     * @access private
     */
    var $_modulefields_cache = Array();

    /**
     * License of the package.
     * @access private
     */
    var $_licensetext = false;

    protected $firstPanel;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Parse the manifest file
     * @access private
     */
    function __parseManifestFile($unzip) {
        $manifestfile = $this->__getManifestFilePath();
        $unzip->unzip('manifest.xml', $manifestfile);
        $this->_modulexml = simplexml_load_file($manifestfile);
        unlink($manifestfile);
    }

    /**
     * Get type of package (as specified in manifest)
     */
    function type() {
        if(!empty($this->_modulexml) && !empty($this->_modulexml->type)) {
            return $this->_modulexml->type;
        }
        return false;
    }

    /**
     * XPath evaluation on the root module node.
     * @param String Path expression
     */
    function xpath($path) {
        return $this->_modulexml->xpath($path);
    }

    /**
     * Get the value of matching path (instead of complete xpath result)
     * @param String Path expression for which value is required
     */
    function xpath_value($path) {
        $xpathres = $this->xpath($path);
        foreach($xpathres as $pathkey=>$pathvalue) {
            if($pathkey == $path) return $pathvalue;
        }
        return false;
    }

    /**
     * Are we trying to import language package?
     */
    function isLanguageType($zipfile =null) {
        if(!empty($zipfile)) {
            if(!$this->checkZip($zipfile)) {
                return false;
            }
        }
        $packagetype = $this->type();

        if($packagetype) {
            $lcasetype = strtolower($packagetype);
            if($lcasetype == 'language') return true;
        }
        return false;
    }

    /**
     * checks whether a package is module bundle or not.
     * @param String $zipfile - path to the zip file.
     * @return Boolean - true if given zipfile is a module bundle and false otherwise.
     */
    function isModuleBundle($zipfile = null) {
        // If data is not yet available
        if(!empty($zipfile)) {
            if(!$this->checkZip($zipfile)) {
                return false;
            }
        }

        return (boolean)$this->_modulexml->modulebundle;
    }

    /**
     * @return Array module list available in the module bundle.
     */
    function getAvailableModuleInfoFromModuleBundle() {
        $list = (Array)$this->_modulexml->modulelist;
        return (Array)$list['dependent_module'];
    }

    /**
     * Get the license of this package
     * NOTE: checkzip should have been called earlier.
     */
    function getLicense() {
        return $this->_licensetext;
    }

    /**
     * Check if zipfile is a valid package
     * @access private
     */
    function checkZip($zipfile, &$error='') { // crmv@195213
        $unzip = new Vtecrm_Unzip($zipfile);
        $filelist = $unzip->getList();

        $manifestxml_found = false;
        $languagefile_found = false;
        $vtenextversion_found = false;

        $modulename = null;
        $language_modulename = null;

        foreach($filelist as $filename=>$fileinfo) {
            $matches = Array();
            preg_match('/manifest.xml/', $filename, $matches);
            if(count($matches)) {
                $manifestxml_found = true;
                $this->__parseManifestFile($unzip);
                $modulename = $this->_modulexml->name;
                $isModuleBundle = (string)$this->_modulexml->modulebundle;

                if($isModuleBundle === 'true' && (!empty($this->_modulexml)) &&
                    (!empty($this->_modulexml->dependencies)) &&
                    (!empty($this->_modulexml->dependencies->vtenext_version || !empty($this->_modulexml->dependencies->vtiger_version)))) {//crmv@207991
                    return true;
                }

                // Do we need to check the zip further?
                if($this->isLanguageType()) {
                    $languagefile_found = true; // No need to search for module language file.
                    break;
                } else {
                    // crmv@195213
                    if (isset($this->_modulexml->cf_prefix)) {
                        global $cf_prefix;
                        $package_cf_prefix = strval($this->_modulexml->cf_prefix);
                        if ($package_cf_prefix == $cf_prefix) {
                            $error = getTranslatedString('LBL_IMPORT_CF_PREFIX_ERROR', 'Settings');
                        }
                    }
                    if (empty($error) && !$this->checkPicklistDuplicates($this->_modulexml, Vtecrm_Module::getInstance($modulename))) {
                        $error = getTranslatedString('LBL_IMPORT_PICKLIST_DUPLICATES_ERROR', 'Settings');
                    }
                    // crmv@195213e
                    // crmv@197127
                    if (empty($error) && !$this->checkModlights($this->_modulexml, Vtecrm_Module::getInstance($modulename))) {
                        $error = getTranslatedString('LBL_IMPORT_MODLIGHTS_ERROR', 'Settings');
                    }
                    // crmv@197127e
                    continue;
                }
            }
            // Check for language file.
            preg_match("/modules\/([^\/]+)\/language\/en_us.lang.php/", $filename, $matches);
            if(count($matches)) { $language_modulename = $matches[1]; continue; }
        }

        // Verify module language file.
        if(!empty($language_modulename) && $language_modulename == $modulename) {
            $languagefile_found = true;
        }

        if(!empty($this->_modulexml) &&
            !empty($this->_modulexml->dependencies) &&
            (!empty($this->_modulexml->dependencies->vtenext_version) || !empty($this->_modulexml->dependencies->vtiger_version))) {//crmv@207991
            $vtenextversion_found = true;
        }

        $validzip = false;
        if($manifestxml_found && $languagefile_found && $vtenextversion_found)
            $validzip = true;

        if($validzip) {
            if(!empty($this->_modulexml->license)) {
                if(!empty($this->_modulexml->license->inline)) {
                    $this->_licensetext = $this->_modulexml->license->inline;
                } else if(!empty($this->_modulexml->license->file)) {
                    $licensefile = $this->_modulexml->license->file;
                    $licensefile = "$licensefile";
                    if(!empty($filelist[$licensefile])) {
                        $this->_licensetext = $unzip->unzip($licensefile);
                    } else {
                        $this->_licensetext = "Missing $licensefile!";
                    }
                }
            }
        }

        if($unzip) $unzip->close();

        return $validzip;
    }

    // crmv@195213
    function checkPicklistDuplicates($modulenode, $moduleInstance=null) {
        global $adb, $table_prefix;
        $system_shared_picklists = array(
            'carrier' => array('Quotes','PurchaseOrder','SalesOrder'),
            'glacct' => array('Vendors','Products'),
            'industry' => array('Accounts','Leads'),
            'invoicestatus' => array('Invoice','SalesOrder'),
            'leadsource' => array('Leads','Contacts','Potentials'),
            'rating' => array('Leads','Accounts'),
            'ticketstatus' => array('HelpDesk','Timecards'),
        );
        if (!empty($modulenode->panels) && !empty($modulenode->panels->panel)) {
            foreach($modulenode->panels->panel as $panelnode) {
                if (!empty($panelnode->blocks) && !empty($panelnode->blocks->block)) {
                    foreach($panelnode->blocks->block as $blocknode) {
                        if (!empty($blocknode->fields) && !empty($blocknode->fields->field)) {
                            foreach($blocknode->fields->field as $fieldnode) {
                                $fieldname = strval($fieldnode->fieldname);
                                if (!empty($moduleInstance) && isset($system_shared_picklists[$fieldname]) && in_array($moduleInstance->name,$system_shared_picklists[$fieldname])) continue;

                                $result = $adb->pquery("select fieldtype from {$table_prefix}_ws_fieldtype where uitype = ?", array($fieldnode->uitype));
                                if ($result && $adb->num_rows($result) > 0) {
                                    $fieldtype = $adb->query_result($result,0,'fieldtype');
                                    if ($fieldtype == 'picklist' || $fieldtype == 'multipicklist') {
                                        $query = "select tabid, fieldid
											from {$table_prefix}_field
											inner join {$table_prefix}_ws_fieldtype on {$table_prefix}_ws_fieldtype.uitype = {$table_prefix}_field.uitype
											where fieldtype in (?,?) and fieldname = ?";
                                        $params = array('picklist','multipicklist',$fieldname);
                                        if (!empty($moduleInstance)) {
                                            $query .= ' and tabid <> ?';
                                            $params[] = $moduleInstance->id;
                                        }
                                        $result = $adb->pquery($query,$params);
                                        if ($result && $adb->num_rows($result) > 0) {
                                            return false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // check orphaned blocks
        if (!empty($modulenode->blocks) && !empty($modulenode->blocks->block)) {
            foreach($modulenode->blocks->block as $blocknode) {
                if (!empty($blocknode->fields) && !empty($blocknode->fields->field)) {
                    foreach($blocknode->fields->field as $fieldnode) {
                        $fieldname = strval($fieldnode->fieldname);
                        if (!empty($moduleInstance) && isset($system_shared_picklists[$fieldname]) && in_array($moduleInstance->name,$system_shared_picklists[$fieldname])) continue;

                        $result = $adb->pquery("select fieldtype from {$table_prefix}_ws_fieldtype where uitype = ?", array($fieldnode->uitype));
                        if ($result && $adb->num_rows($result) > 0) {
                            $fieldtype = $adb->query_result($result,0,'fieldtype');
                            if ($fieldtype == 'picklist' || $fieldtype == 'multipicklist') {
                                $query = "select tabid, fieldid
									from {$table_prefix}_field
									inner join {$table_prefix}_ws_fieldtype on {$table_prefix}_ws_fieldtype.uitype = {$table_prefix}_field.uitype
									where fieldtype in (?,?) and fieldname = ?";
                                $params = array('picklist','multipicklist',$fieldname);
                                if (!empty($moduleInstance)) {
                                    $query .= ' and tabid <> ?';
                                    $params[] = $moduleInstance->id;
                                }
                                $result = $adb->pquery($query,$params);
                                if ($result && $adb->num_rows($result) > 0) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    // crmv@195213e

    // crmv@197127
    function checkModlights($modulenode, $moduleInstance=null) {
        global $adb, $table_prefix;

        // in update mode skip the check
        if (!empty($moduleInstance)) return true;

        if (!empty($modulenode->panels) && !empty($modulenode->panels->panel)) {
            foreach($modulenode->panels->panel as $panelnode) {
                if (!empty($panelnode->blocks) && !empty($panelnode->blocks->block)) {
                    foreach($panelnode->blocks->block as $blocknode) {
                        if (!empty($blocknode->fields) && !empty($blocknode->fields->field)) {
                            foreach($blocknode->fields->field as $fieldnode) {
                                $fieldname = strval($fieldnode->fieldname);

                                $result = $adb->pquery("select fieldtype from {$table_prefix}_ws_fieldtype where uitype = ?", array($fieldnode->uitype));
                                if ($result && $adb->num_rows($result) > 0) {
                                    $fieldtype = $adb->query_result($result,0,'fieldtype');
                                    if ($fieldtype == 'table') {
                                        $query = "select tabid, fieldid
											from {$table_prefix}_field
											inner join {$table_prefix}_ws_fieldtype on {$table_prefix}_ws_fieldtype.uitype = {$table_prefix}_field.uitype
											where fieldtype = ? and fieldname = ?";
                                        $params = array('table',$fieldname);
                                        /*if (!empty($moduleInstance)) {
                                            $query .= ' and tabid <> ?';
                                            $params[] = $moduleInstance->id;
                                        }*/
                                        $result = $adb->pquery($query,$params);
                                        if ($result && $adb->num_rows($result) > 0) {
                                            return false;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // check orphaned blocks
        if (!empty($modulenode->blocks) && !empty($modulenode->blocks->block)) {
            foreach($modulenode->blocks->block as $blocknode) {
                if (!empty($blocknode->fields) && !empty($blocknode->fields->field)) {
                    foreach($blocknode->fields->field as $fieldnode) {
                        $fieldname = strval($fieldnode->fieldname);

                        $result = $adb->pquery("select fieldtype from {$table_prefix}_ws_fieldtype where uitype = ?", array($fieldnode->uitype));
                        if ($result && $adb->num_rows($result) > 0) {
                            $fieldtype = $adb->query_result($result,0,'fieldtype');
                            if ($fieldtype == 'table') {
                                $query = "select tabid, fieldid
									from {$table_prefix}_field
									inner join {$table_prefix}_ws_fieldtype on {$table_prefix}_ws_fieldtype.uitype = {$table_prefix}_field.uitype
									where fieldtype = ? and fieldname = ?";
                                $params = array('table',$fieldname);
                                /*if (!empty($moduleInstance)) {
                                    $query .= ' and tabid <> ?';
                                    $params[] = $moduleInstance->id;
                                }*/
                                $result = $adb->pquery($query,$params);
                                if ($result && $adb->num_rows($result) > 0) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    // crmv@197127e

    /**
     * Get module name packaged in the zip file
     * @access private
     */
    // crmv@195213
    function getModuleNameFromZip($zipfile, &$error='') {
        if(!$this->checkZip($zipfile,$error)) return null;

        return (string)$this->_modulexml->name;
    }
    // crmv@195213e

    /**
     * returns the name of the module.
     * @return String - name of the module as given in manifest file.
     */
    function getModuleName() {
        return (string)$this->_modulexml->name;
    }

    /**
     * Cache the field instance for re-use
     * @access private
     */
    function __AddModuleFieldToCache($moduleInstance, $fieldname, $fieldInstance) {
        $this->_modulefields_cache["$moduleInstance->name"]["$fieldname"] = $fieldInstance;
    }

    /**
     * Get field instance from cache
     * @access private
     */
    function __GetModuleFieldFromCache($moduleInstance, $fieldname) {
        return $this->_modulefields_cache["$moduleInstance->name"]["$fieldname"];
    }

    /**
     * Initialize Import
     * @access private
     */
    function initImport($zipfile, $overwrite) {
        $module = $this->getModuleNameFromZip($zipfile);
        if($module != null) {

            $unzip = new Vtecrm_Unzip($zipfile, $overwrite);

            // Unzip selectively
            $unzip->unzipAllEx( ".",
                Array(
                    // Include only file/folders that need to be extracted
                    'include' => Array('templates', "modules/$module", 'cron', 'sdk'),	//crmv@sdk
                    //'exclude' => Array('manifest.xml')
                    // NOTE: If excludes is not given then by those not mentioned in include are ignored.
                ),
                // What files needs to be renamed?
                Array(
                    // Templates folder
                    'templates' => "Smarty/templates/modules/$module",
                    // Cron folder
                    'cron' => "cron/modules/$module",
                    'sdk' => 'modules/SDK/tmp'	//crmv@sdk
                )
            );

            if($unzip) $unzip->close();
        }
        return $module;
    }

    function getTemporaryFilePath($filepath=false) {
        return 'cache/'. $filepath;
    }
    /**
     * Get dependent version
     * @access private
     */
    function getDependentVersion() {//crmv@207991
        return $this->_modulexml->dependencies->vtenext_version;//crmv@207991
    }

    /**
     * Get dependent Maximum version
     * @access private
     */
    function getDependentMaxVersion() {
        return $this->_modulexml->dependencies->vtenext_max_version;//crmv@207991
    }

    /**
     * Get package version
     * @access private
     */
    function getVersion() {
        return $this->_modulexml->version;
    }

    /**
     * Import Module from zip file
     * @param String Zip file name
     * @param Boolean True for overwriting existing module
     *
     * @todo overwrite feature is not functionally currently.
     */
    function import($zipfile, $overwrite=false,$tabidtouse=false) { //crmv@36557
        $module = $this->getModuleNameFromZip($zipfile);
        if($module != null) {
            // If data is not yet available
            if(empty($this->_modulexml)) {
                $this->__parseManifestFile($unzip);
            }

            $buildModuleArray = array();
            $installSequenceArray = array();
            $moduleBundle = (boolean)$this->_modulexml->modulebundle;
            if($moduleBundle == true) {
                $moduleList = (Array)$this->_modulexml->modulelist;
                foreach($moduleList as $moduleInfos) {
                    foreach($moduleInfos as $moduleInfo) {
                        $moduleInfo = (Array)$moduleInfo;
                        $buildModuleArray[] = $moduleInfo;
                        $installSequenceArray[] = $moduleInfo['install_sequence'];
                    }
                }
                sort($installSequenceArray);
                $unzip = new Vtecrm_Unzip($zipfile);
                $unzip->unzipAllEx($this->getTemporaryFilePath());
                foreach ($installSequenceArray as $sequence) {
                    foreach ($buildModuleArray as $moduleInfo) {
                        if($moduleInfo['install_sequence'] == $sequence) {
                            $this->import($this->getTemporaryFilePath($moduleInfo['filepath']), $overwrite);
                        }
                    }
                }
            } else {
                $module = $this->initImport($zipfile, $overwrite);
                // Call module import function
                $this->import_Module($tabidtouse); //crmv@36557
            }
        }
    }

    //crmv@2963m
    function importByManifest($moduleName) {
        $manifestfile = "modules/$moduleName/manifest.xml";
        if (file_exists($manifestfile)) {
            $this->_modulexml = simplexml_load_file($manifestfile);
            $this->import_Module();
            @rename($manifestfile,$manifestfile.'.installed');
        }
    }
    //crmv@2963me

    /**
     * Import Module
     * @access private
     */
    function import_Module($tabidtouse=false) { //crmv@36557
        global $adb, $table_prefix;
        $tabname = $this->_modulexml->name;
        //crmv@30456
        if(strpos($tabname, 'TABLEPREFIX') !== false){
            $tabname=str_replace('TABLEPREFIX', $table_prefix, $tabname);
        }
        //crmv@30456e
        $parenttab=(string)$this->_modulexml->parent;
        //crmv@30456
        if(strpos($parenttab, 'TABLEPREFIX') !== false){
            $parenttab=str_replace('TABLEPREFIX', $table_prefix, $parenttab);
        }
        //crmv@30456e
        $tabversion=$this->_modulexml->version;

        $isextension= false;
        if(!empty($this->_modulexml->type)) {
            $type = strtolower($this->_modulexml->type);
            if($type == 'extension' || $type == 'language')
                $isextension = true;
        }

        $MinVersion = $this->_modulexml->dependencies->vtenext_version ?: $this->_modulexml->dependencies->vtiger_version;//crmv@207991
        $MaxVersion = $this->_modulexml->dependencies->vtenext_max_version ?: $this->_modulexml->dependencies->vtiger_max_version;//crmv@207991

        $moduleInstance = new Vtecrm_Module();
        $moduleInstance->name = $tabname;
        $moduleInstance->label= $tablabel;
        $moduleInstance->isentitytype = ($isextension != true);
        $moduleInstance->version = (!$tabversion)? 0 : $tabversion;
        $moduleInstance->minversion = (!$MinVersion)? false : $MinVersion;
        $moduleInstance->maxversion = (!$MaxVersion)?  false : $MaxVersion;
        // import info
        if (!empty($this->_modulexml->info)) {
            foreach($this->_modulexml->info as $info) {
                foreach($info as $name => $value) {
                    if (isset($moduleInstance->$name)) $moduleInstance->$name = strval($value);
                }
            }
        }
        $moduleInstance->save($tabidtouse); //crmv@36557

        if(!empty($parenttab)) {
            $menuInstance = Vtecrm_Menu::getInstance($parenttab);
            $menuInstance->addModule($moduleInstance);
        }

        if (isset($this->_modulexml->info->is_mod_light) && $this->_modulexml->info->is_mod_light == '1') {
            VteSession::set('skip_recalculate', true);
            @mkdir("modules/{$moduleInstance->name}");
        }

        $this->import_Tables($this->_modulexml);
        $this->import_Panels($this->_modulexml, $moduleInstance);
        $this->import_Orphaned_Blocks($this->_modulexml, $moduleInstance);
        $this->import_CustomViews($this->_modulexml, $moduleInstance);
        $this->import_SharingAccess($this->_modulexml, $moduleInstance);
        $this->import_Events($this->_modulexml, $moduleInstance);
        $this->import_Actions($this->_modulexml, $moduleInstance);
        $this->import_RelatedLists($this->_modulexml, $moduleInstance);
        $this->import_CustomLinks($this->_modulexml, $moduleInstance);
        $this->import_Modlights($this->_modulexml, $moduleInstance); // crmv@197127

        if (isset($this->_modulexml->info->is_mod_light) && $this->_modulexml->info->is_mod_light == '1') {

            $modulelightid = str_replace('ModLight','',$moduleInstance->name);
            $result = $adb->pquery("select fieldlabel from {$table_prefix}_field where fieldname = ? and uitype = ?", array('ml'.$modulelightid,'220'));
            ($result && $adb->num_rows($result) > 0) ? $fieldlabel = $adb->query_result($result,0,'fieldlabel') : $fieldlabel = $moduleInstance->name;

            require_once('include/utils/ModLightUtils.php');
            $MLUtils = ModLightUtils::getInstance();

            require_once('modules/Settings/ModuleMaker/ModuleMakerUtils.php');
            require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
            require_once('modules/Settings/ModuleMaker/ModuleMakerGenerator.php');
            $MMUtils = new ModuleMakerUtils();
            $MMSteps = new ProcessModuleMakerSteps($MMUtils);
            $MMGen = new ModuleMakerGenerator($MMUtils, $MMSteps);

            // init ModuleMakerGenerator
            $MMGen->setModuleName($moduleInstance->name);
            $result = $adb->pquery("SELECT {$table_prefix}_field.fieldname, {$table_prefix}_field.fieldlabel
				FROM {$table_prefix}_entityname
				INNER JOIN {$table_prefix}_field ON {$table_prefix}_entityname.fieldname = {$table_prefix}_field.fieldname AND {$table_prefix}_field.tabid = {$table_prefix}_entityname.tabid
				WHERE {$table_prefix}_entityname.modulename = ?", array($moduleInstance->name));
            $MMGen->mainField = array('fieldname'=>$adb->query_result($result,0,'fieldname'),'fieldlabel'=>$adb->query_result($result,0,'fieldlabel'));

            $result = $adb->pquery("SELECT fieldname, fieldlabel FROM {$table_prefix}_field WHERE tabid = ?", array($moduleInstance->id));
            if ($result && $adb->num_rows($result) > 0) {
                while($row=$adb->fetchByAssoc($result)) {
                    $MMGen->moduleInfo['fields'][0]['fields'][] = array('fieldname'=>$row['fieldname'],'fieldlabel'=>$row['fieldlabel']);
                }
            }
            $result = $adb->pquery("select columnname from {$table_prefix}_cvcolumnlist
				inner join {$table_prefix}_customview on {$table_prefix}_customview.cvid = {$table_prefix}_cvcolumnlist.cvid
				where entitytype = ?
				order by columnindex", array($moduleInstance->name));
            if ($result && $adb->num_rows($result) > 0) {
                while($row=$adb->fetchByAssoc($result)) {
                    $t = explode(':',$row['columnname']);
                    $MMGen->moduleInfo['filters'][0]['columns'][] = $t[2];
                }
            }

            $MLUtils->generateFileStructure($moduleInstance->name, $fieldlabel, $MMGen);

            VteSession::remove('skip_recalculate');
            create_tab_data_file();
            create_parenttab_data_file();
        }

        SDK::importPackage($this->_modulexml, $moduleInstance);	//crmv@sdk

        $moduleInstance->initWebservice();
        Vtecrm_Module::fireEvent($moduleInstance->name, Vtecrm_Module::EVENT_MODULE_POSTINSTALL);
    }

    /**
     * Import Tables of the module
     * @access private
     */
    function import_Tables($modulenode) {
        global $table_prefix;
        if(empty($modulenode->tables) || empty($modulenode->tables->table)) return;

        /**
         * Record the changes in schema file
         */
        $schemafile = fopen("modules/$modulenode->name/schema.xml", 'w');
        if($schemafile) {
            fwrite($schemafile, "<?xml version='1.0'?>\n");
            fwrite($schemafile, "<schema>\n");
            fwrite($schemafile, "\t<tables>\n");
        }

        // Import the table via queries
        foreach($modulenode->tables->table as $tablenode) {
            $tablename = $tablenode->name;
            //crmv@30456
            if(strpos($tablename, 'TABLEPREFIX') !== false){
                $tablename=str_replace('TABLEPREFIX', $table_prefix, $tablename);
            }
            //crmv@30456e
            $tablesql  = "$tablenode->sql"; // Convert to string format
            //crmv@30456
            if(strpos($tablesql, 'TABLEPREFIX') !== false){
                $tablesql=str_replace('TABLEPREFIX', $table_prefix, $tablesql);
            }
            //crmv@30456e
            // Save the information in the schema file.
            fwrite($schemafile, "\t\t<table>\n");
            fwrite($schemafile, "\t\t\t<name>$tablename</name>\n");
            fwrite($schemafile, "\t\t\t<sql><![CDATA[$tablesql]]></sql>\n");
            fwrite($schemafile, "\t\t</table>\n");

            // Avoid executing SQL that will DELETE or DROP table data
            if(Vtecrm_Utils::IsCreateSql($tablesql)) {
                if(!Vtecrm_Utils::checkTable($tablename)) {
                    self::log("SQL: $tablesql ... ", false);
                    Vtecrm_Utils::ExecuteSchema($tablesql);
                    self::log("DONE");
                }
            } else {
                if(Vtecrm_Utils::IsDestructiveSql($tablesql)) {
                    self::log("SQL: $tablesql ... SKIPPED");
                } else {
                    self::log("SQL: $tablesql ... ", false);
                    Vtecrm_Utils::ExecuteSchema($tablesql);
                    self::log("DONE");
                }
            }
        }
        if($schemafile) {
            fwrite($schemafile, "\t</tables>\n");
            fwrite($schemafile, "</schema>\n");
            fclose($schemafile);
        }
    }

    /**
     * Import Panels of the module
     * @access private
     */
    function import_Panels($modulenode, $moduleInstance) {
        if(empty($modulenode->panels) || empty($modulenode->panels->panel)) return;
        foreach($modulenode->panels->panel as $panelnode) {
            $panelInstance = $this->import_Panel($modulenode, $moduleInstance, $panelnode);
            if (!$this->firstPanel) {
                // save a reference to the first panel
                $this->firstPanel = $panelInstance;
            }
            $this->import_Blocks($panelnode, $panelInstance, $moduleInstance);
        }
    }

    /**
     * Import Panel of the module
     * @access private
     */
    function import_Panel($modulenode, $moduleInstance, $panelnode) {
        $panellabel = $panelnode->label;

        $panelInstance = new Vtecrm_Panel();
        $panelInstance->label = $panellabel;
        $moduleInstance->addPanel($panelInstance);
        return $panelInstance;
    }

    /**
     * Create the main panel for a module
     */
    function create_Main_Panel($moduleInstance) {
        $panelInstance = new Vtecrm_Panel();
        $panelInstance->label = 'LBL_TAB_MAIN';
        $moduleInstance->addPanel($panelInstance);
        return $panelInstance;
    }

    /**
     * Import Blocks of the module
     * @access private
     */
    function import_Blocks($panelnode, $panelInstance, $moduleInstance) {
        if(empty($panelnode->blocks) || empty($panelnode->blocks->block)) return;
        foreach($panelnode->blocks->block as $blocknode) {
            $blockInstance = $this->import_Block($panelnode, $panelInstance, $moduleInstance, $blocknode);
            $this->import_Fields($blocknode, $blockInstance, $moduleInstance);
        }
    }

    /**
     * Import Blocks without panel in the first panel (or create one if not present)
     */
    function import_Orphaned_Blocks($modulenode, $moduleInstance) {
        if(empty($modulenode->blocks) || empty($modulenode->blocks->block)) return;
        if (!$this->firstPanel) {
            $this->firstPanel = $this->create_Main_Panel($moduleInstance);
        }
        foreach($modulenode->blocks->block as $blocknode) {
            $blockInstance = $this->import_Block(null, $this->firstPanel, $moduleInstance, $blocknode);
            $this->import_Fields($blocknode, $blockInstance, $moduleInstance);
        }
    }

    /**
     * Import Block of the module
     * @access private
     */
    function import_Block($panelnode, $panelInstance, $moduleInstance, $blocknode) {
        $blocklabel = $blocknode->label;
        $blocksequence = $blocknode->sequence;

        $blockInstance = new Vtecrm_Block();
        $blockInstance->label = $blocklabel;
        $blockInstance->panel = $panelInstance;
        $blockInstance->sequence = $blocksequence;
        $moduleInstance->addBlock($blockInstance);
        return $blockInstance;
    }

    /**
     * Import Fields of the module
     * @access private
     */
    function import_Fields($blocknode, $blockInstance, $moduleInstance) {
        if(empty($blocknode->fields) || empty($blocknode->fields->field)) return;

        foreach($blocknode->fields->field as $fieldnode) {
            $fieldInstance = $this->import_Field($blocknode, $blockInstance, $moduleInstance, $fieldnode);
        }
    }

    /**
     * Import Field of the module
     * @access private
     */
    function import_Field($blocknode, $blockInstance, $moduleInstance, $fieldnode) {
        $fieldInstance = new Vtecrm_Field();
        $fieldInstance->name         = $fieldnode->fieldname;
        $fieldInstance->label        = $fieldnode->fieldlabel;
        $fieldInstance->table        = $fieldnode->tablename;
        $fieldInstance->column       = $fieldnode->columnname;
        $fieldInstance->uitype       = $fieldnode->uitype;
        $fieldInstance->generatedtype= $fieldnode->generatedtype;
        $fieldInstance->readonly     = $fieldnode->readonly;
        $fieldInstance->presence     = $fieldnode->presence;
        $fieldInstance->selected     = $fieldnode->selected;
        $fieldInstance->maximumlength= $fieldnode->maximumlength;
        $fieldInstance->sequence     = $fieldnode->sequence;
        $fieldInstance->quickcreate  = $fieldnode->quickcreate;
        $fieldInstance->quicksequence= $fieldnode->quickcreatesequence;
        $fieldInstance->typeofdata   = $fieldnode->typeofdata;
        $fieldInstance->displaytype  = $fieldnode->displaytype;
        $fieldInstance->info_type    = $fieldnode->info_type;

        if(!empty($fieldnode->helpinfo))
            $fieldInstance->helpinfo = $fieldnode->helpinfo;

        if(isset($fieldnode->masseditable))
            $fieldInstance->masseditable = $fieldnode->masseditable;

        if(isset($fieldnode->columntype) && !empty($fieldnode->columntype))
            $fieldInstance->columntype = $fieldnode->columntype;

        $blockInstance->addField($fieldInstance);

        // Set the field as entity identifier if marked.
        if(!empty($fieldnode->entityidentifier)) {
            $moduleInstance->entityidfield = $fieldnode->entityidentifier->entityidfield;
            $moduleInstance->entityidcolumn= $fieldnode->entityidentifier->entityidcolumn;
            $moduleInstance->setEntityIdentifier($fieldInstance);
        }

        // Check picklist values associated with field if any.
        if(!empty($fieldnode->picklistvalues) && isset($fieldnode->picklistvalues->picklistvalue)) {
            $picklistvalues = Array();
            foreach($fieldnode->picklistvalues->picklistvalue as $picklistvaluenode) {
                $picklistvalues[] = $picklistvaluenode;
            }
            $fieldInstance->setPicklistValues( $picklistvalues );
        }

        // Check related modules associated with this field
        if(!empty($fieldnode->relatedmodules) && !empty($fieldnode->relatedmodules->relatedmodule)) {
            $relatedmodules = Array();
            foreach($fieldnode->relatedmodules->relatedmodule as $relatedmodulenode) {
                $relatedmodules[] = $relatedmodulenode;
            }
            $fieldInstance->setRelatedModules($relatedmodules);
        }

        if(!empty($fieldnode->info)) {
            $fieldinfo = array();
            foreach($fieldnode->info as $info) {
                foreach($info as $k => $v) {
                    $fieldinfo[$k] = Zend_Json::decode(strval($v));
                    if ($fieldInstance->uitype == '50' && !empty($fieldinfo[$k])) {
                        foreach($fieldinfo[$k] as &$user) if ($user != '') $user = getUserId_Ol($user);
                    }
                }
            }
            if (!empty($fieldinfo)) $fieldInstance->setFieldInfo($fieldinfo);
        }

        $this->__AddModuleFieldToCache($moduleInstance, $fieldnode->fieldname, $fieldInstance);
        return $fieldInstance;
    }

    /**
     * Import Custom views of the module
     * @access private
     */
    function import_CustomViews($modulenode, $moduleInstance) {
        if(empty($modulenode->customviews) || empty($modulenode->customviews->customview)) return;
        foreach($modulenode->customviews->customview as $customviewnode) {
            $filterInstance = $this->import_CustomView($modulenode, $moduleInstance, $customviewnode);
        }
    }

    /**
     * Import Custom View of the module
     * @access private
     */
    function import_CustomView($modulenode, $moduleInstance, $customviewnode) {
        $viewname = $customviewnode->viewname;
        $setdefault=$customviewnode->setdefault;
        $setmetrics=$customviewnode->setmetrics;
        $setmobile=$customviewnode->setmobile; // crmv@49398

        $filterInstance = new Vtecrm_Filter();
        $filterInstance->name = $viewname;
        $filterInstance->isdefault = $setdefault;
        $filterInstance->inmetrics = $setmetrics;
        $filterInstance->inmobile = $setmobile; // crmv@49398

        $moduleInstance->addFilter($filterInstance);

        foreach($customviewnode->fields->field as $fieldnode) {
            $fieldInstance = $this->__GetModuleFieldFromCache($moduleInstance, $fieldnode->fieldname);
            $filterInstance->addField($fieldInstance, $fieldnode->columnindex);

            if(!empty($fieldnode->rules->rule)) {
                foreach($fieldnode->rules->rule as $rulenode) {
                    $filterInstance->addRule($fieldInstance, $rulenode->comparator, $rulenode->value, $rulenode->columnindex);
                }
            }
        }
    }

    /**
     * Import Sharing Access of the module
     * @access private
     */
    function import_SharingAccess($modulenode, $moduleInstance) {
        if(empty($modulenode->sharingaccess)) return;

        if(!empty($modulenode->sharingaccess->default)) {
            foreach($modulenode->sharingaccess->default as $defaultnode) {
                $moduleInstance->setDefaultSharing($defaultnode);
            }
        }
    }

    /**
     * Import Events of the module
     * @access private
     */
    function import_Events($modulenode, $moduleInstance) {
        if(empty($modulenode->events) || empty($modulenode->events->event))	return;

        if(Vtecrm_Event::hasSupport()) {
            foreach($modulenode->events->event as $eventnode) {
                $this->import_Event($modulenode, $moduleInstance, $eventnode);
            }
        }
    }

    /**
     * Import Event of the module
     * @access private
     */
    function import_Event($modulenode, $moduleInstance, $eventnode) {
        $event_condition = '';
        if(!empty($eventnode->condition)) $event_condition = "$eventnode->condition";
        Vtecrm_Event::register($moduleInstance,
            (string)$eventnode->eventname, (string)$eventnode->classname,
            (string)$eventnode->filename, (string)$event_condition
        );
    }

    /**
     * Import actions of the module
     * @access private
     */
    function import_Actions($modulenode, $moduleInstance) {
        if(empty($modulenode->actions) || empty($modulenode->actions->action)) return;
        foreach($modulenode->actions->action as $actionnode) {
            $this->import_Action($modulenode, $moduleInstance, $actionnode);
        }
    }

    /**
     * Import action of the module
     * @access private
     */
    function import_Action($modulenode, $moduleInstance, $actionnode) {
        $actionstatus = $actionnode->status;
        if($actionstatus == 'enabled')
            $moduleInstance->enableTools($actionnode->name);
        else
            $moduleInstance->disableTools($actionnode->name);
    }

    /**
     * Import related lists of the module
     * @access private
     */
    function import_RelatedLists($modulenode, $moduleInstance) {
        if(empty($modulenode->relatedlists) || empty($modulenode->relatedlists->relatedlist)) return;
        foreach($modulenode->relatedlists->relatedlist as $relatedlistnode) {
            $relModuleInstance = $this->import_Relatedlist($modulenode, $moduleInstance, $relatedlistnode);
        }
    }

    /**
     * Import related list of the module.
     * @access private
     */
    function import_Relatedlist($modulenode, $moduleInstance, $relatedlistnode) {
        $relModuleInstance = Vtecrm_Module::getInstance($relatedlistnode->relatedmodule);
        $label = $relatedlistnode->label;
        $actions = false;
        if(!empty($relatedlistnode->actions) && !empty($relatedlistnode->actions->action)) {
            $actions = Array();
            foreach($relatedlistnode->actions->action as $actionnode) {
                $actions[] = "$actionnode";
            }
        }
        if($relModuleInstance) {
            $moduleInstance->setRelatedList($relModuleInstance, "$label", $actions, "$relatedlistnode->function");
        }
        return $relModuleInstance;
    }

    /**
     * Import custom links of the module.
     * @access private
     */
    function import_CustomLinks($modulenode, $moduleInstance) {
        if(empty($modulenode->customlinks) || empty($modulenode->customlinks->customlink)) return;

        foreach($modulenode->customlinks->customlink as $customlinknode) {
            $moduleInstance->addLink(
                "$customlinknode->linktype",
                "$customlinknode->linklabel",
                "$customlinknode->linkurl",
                "$customlinknode->linkicon",
                "$customlinknode->sequence",
                "$customlinknode->condition",
                "$customlinknode->size"
            );
        }
    }

    // crmv@197127
    function import_Modlights($modulenode, $moduleInstance) {
        if(empty($modulenode->modlights) || empty($modulenode->modlights->module)) return;

        foreach($modulenode->modlights->module as $modlightnode) {
            if (!isModuleInstalled("$modlightnode->name")) {
                $this->import_Modlight($modlightnode);
            }
        }
    }
    function import_Modlight($modlightnode) {
        global $adb, $table_prefix;

        $package = new Vtecrm_Package();
        $package->_modulexml = $modlightnode;
        $package->import_Module();

        // update modlight seq
        $ml_no = str_replace('ModLight','',$modlightnode->name);
        // crmv@198024
        if (is_numeric($ml_no)) {
            if ($adb->table_exist("{$table_prefix}_modlight_seq") == 0) $adb->getUniqueID($table_prefix.'_modlight');
            $res = $adb->query("select id from {$table_prefix}_modlight_seq");
            if ($res && $adb->num_rows($res) > 0) $ml_seq = intval($adb->query_result($res,0,'id'));
            if ($ml_seq < $ml_no) $adb->pquery("update {$table_prefix}_modlight_seq set id = ?", array($ml_no));
        }
        // crmv@198024e
    }
    // crmv@197127e
}
