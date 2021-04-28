<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@198038 */

include_once('vtlib/Vtecrm/Utils.php');
include_once('vtlib/Vtecrm/Utils/StringTemplate.php');

/**
 * Provides API to handle custom links
 * @package vtlib
 */
class Vtecrm_Link {
    var $tabid;
    var $linkid;
    var $linktype;
    var $linklabel;
    var $linkurl;
    var $linkicon;
    var $sequence;
    var $status = false;
    var $condition;	//crmv@37303
    var $size;	//crmv@3085m

    // Ignore module while selection
    const IGNORE_MODULE = -1;

    /**
     * Constructor
     */
    function __construct() {
    }

    /**
     * Initialize this instance.
     */
    function initialize($valuemap) {
        $this->tabid  = $valuemap['tabid'];
        $this->linkid = $valuemap['linkid'];
        $this->linktype = $valuemap['linktype'];
        $this->linklabel = $valuemap['linklabel'];
        //crmv@94125
        if ($this->linktype == 'HEADERSCRIPT' || $this->linktype == 'HEADERCSS') { // crmv@144893
            require_once('include/utils/ResourceVersion.php');
            $this->linkurl = resourcever(decode_html($valuemap['linkurl']));
        } else {
            $this->linkurl = decode_html($valuemap['linkurl']);
        }
        //crmv@94125e
        $this->linkicon = decode_html($valuemap['linkicon']);
        $this->sequence = $valuemap['sequence'];
        $this->status = $valuemap['status'];
        $this->condition = $valuemap['cond'];	//crmv@37303
        $this->size = $valuemap['size'];	//crmv@3085m
    }

    /**
     * Get module name.
     */
    function module() {
        if(!empty($this->tabid)) {
            return getTabModuleName($this->tabid);
        }
        return false;
    }

    /**
     * Get unique id for the insertion
     */
    static function __getUniqueId() {
        global $adb, $table_prefix;
        return $adb->getUniqueID($table_prefix.'_links');
    }

    /** Cache (Record) the schema changes to improve performance */
    static $__cacheSchemaChanges = Array();

    /**
     * Initialize the schema (tables)
     */
    static function __initSchema() {
        global $table_prefix;
        if(empty(self::$__cacheSchemaChanges[$table_prefix.'_links'])) {
            if(!Vtecrm_Utils::CheckTable($table_prefix.'_links')) {
                Vtecrm_Utils::CreateTable(
                    $table_prefix.'_links',
                    'linkid I(19) NOTNULL PRIMARY,
					tabid I(19), 
					linktype C(20), 
					linklabel C(30), 
					linkurl C(255), 
					linkicon C(100), 
					sequence I(11), 
					status INT(1) NOTNULL DEFAULT 1
					cond C(200)',	//crmv@37303
                    true);
                Vtecrm_Utils::CreateIndex('link_tabidtype_idx',$table_prefix.'_links','tabid,linktype');
            }
            self::$__cacheSchemaChanges[$table_prefix.'_links'] = true;
        }
    }

    /**
     * Add link given module
     * @param Integer Module ID
     * @param String Link Type (like DETAILVIEW). Useful for grouping based on pages.
     * @param String Label to display
     * @param String HREF value or URL to use for the link
     * @param String ICON to use on the display
     * @param Integer Order or sequence of displaying the link
     */
    static function addLink($tabid, $type, $label, $url, $iconpath='', $sequence=0, $condition='', $size=1) {	//crmv@37303	//crmv@3085m
        global $adb,$table_prefix;
        self::__initSchema();
        $checkres = $adb->pquery('SELECT linkid FROM '.$table_prefix.'_links WHERE tabid=? AND linktype=? AND linkurl=? AND linkicon=? AND linklabel=?',
            Array($tabid, $type, $url, $iconpath, $label));
        if(!$adb->num_rows($checkres)) {
            $uniqueid = self::__getUniqueId();
            //crmv@37303	//crmv@3085m
            $exists_columns = array_keys($adb->datadict->MetaColumns($table_prefix.'_links'));
            $columns = array('linkid','tabid','linktype','linklabel','linkurl','linkicon','sequence');
            $params = array($uniqueid, $tabid, $type, $label, $url, $iconpath, $sequence);
            if (!empty($condition)) {
                $columns[] = 'cond';
                $params[] = $condition;
            }
            if (in_array(strtoupper('size'),$exists_columns)) {
                $columns[] = 'size';
                $params[] = $size;
            }
            $adb->format_columns($columns);
            $adb->pquery('INSERT INTO '.$table_prefix.'_links ('.implode(',',$columns).') VALUES('.generateQuestionMarks($columns).')',$params);
            //crmv@37303e	//crmv@3085me
            self::log("Adding Link ($type - $label) ... DONE");
        }
    }

    /**
     * Delete link of the module
     * @param Integer Module ID
     * @param String Link Type (like DETAILVIEW). Useful for grouping based on pages.
     * @param String Display label
     * @param String URL of link to lookup while deleting
     */
    static function deleteLink($tabid, $type, $label, $url=false) {
        global $adb,$table_prefix;
        self::__initSchema();
        if($url) {
            $adb->pquery('DELETE FROM '.$table_prefix.'_links WHERE tabid=? AND linktype=? AND linklabel=? AND linkurl=?',
                Array($tabid, $type, $label, $url));
            self::log("Deleting Link ($type - $label - $url) ... DONE");
        } else {
            $adb->pquery('DELETE FROM '.$table_prefix.'_links WHERE tabid=? AND linktype=? AND linklabel=?',
                Array($tabid, $type, $label));
            self::log("Deleting Link ($type - $label) ... DONE");
        }
    }

    /**
     * Delete all links related to module
     * @param Integer Module ID.
     */
    static function deleteAll($tabid) {
        global $adb,$table_prefix;
        self::__initSchema();
        $adb->pquery('DELETE FROM '.$table_prefix.'_links WHERE tabid=?', Array($tabid));
        self::log("Deleting Links ... DONE");
    }

    /**
     * Get all the links related to module
     * @param Integer Module ID.
     */
    static function getAll($tabid, $type=false, $parameters=false, $check_condition=true) {
        return self::getAllByType($tabid, $type, $parameters, $check_condition);
    }

    /**
     * Get all the link related to module based on type
     * @param Integer Module ID
     * @param mixed String or List of types to select
     * @param Map Key-Value pair to use for formating the link url
     */
    static function getAllByType($tabid, $type=false, $parameters=false, $check_condition=true) {
        global $adb, $current_user,$table_prefix;
        self::__initSchema();

        $multitype = false;

        if($type) {
            $columnSize = 'size';
            $adb->format_columns($columnSize);
            $order_by = 'ORDER BY '.$columnSize.', sequence';	//crmv@3085m
            // Multiple link type selection?
            if(is_array($type)) {
                $multitype = true;
                if($tabid === self::IGNORE_MODULE) {
                    $sql = 'SELECT * FROM '.$table_prefix.'_links WHERE status = 1 AND linktype IN ('. // crmv@143810
                        Vtecrm_Utils::implodestr('?', count($type), ',') .') ';
                    $params = $type;
                    $permittedTabIdList = getPermittedModuleIdList();
                    if(count($permittedTabIdList) > 0 && $current_user->is_admin !== 'on') {
                        //crmv@sdk
                        if (isModuleInstalled('SDK')) {
                            $permittedTabIdList[] = getTabid('SDK');
                        }
                        //crmv@sdk e
                        $sql .= ' and tabid IN ('.
                            Vtecrm_Utils::implodestr('?', count($permittedTabIdList), ',').')';
                        $params[] = $permittedTabIdList;
                    }
                    $result = $adb->pquery($sql, Array($adb->flatten_array($params)));
                } else {
                    $result = $adb->pquery('SELECT * FROM '.$table_prefix.'_links WHERE status = 1 AND tabid=? AND linktype IN ('. // crmv@143810
                        Vtecrm_Utils::implodestr('?', count($type), ',') .') '.$order_by,
                        Array($tabid, $adb->flatten_array($type)));
                }
            } else {
                // Single link type selection
                if($tabid === self::IGNORE_MODULE) {
                    $result = $adb->pquery('SELECT * FROM '.$table_prefix.'_links WHERE status = 1 AND linktype=? '.$order_by, Array($type)); // crmv@143810
                } else {
                    $result = $adb->pquery('SELECT * FROM '.$table_prefix.'_links WHERE status = 1 AND tabid=? AND linktype=? '.$order_by, Array($tabid, $type)); // crmv@143810
                }
            }
        } else {
            $result = $adb->pquery('SELECT * FROM '.$table_prefix.'_links WHERE status = 1 AND tabid=?', Array($tabid)); // crmv@143810
        }

        $strtemplate = new Vtecrm_StringTemplate();
        if($parameters) {
            foreach($parameters as $key=>$value) $strtemplate->assign($key, $value);
        }

        $instances = Array();
        if($multitype) {
            foreach($type as $t) $instances[$t] = Array();
        }

        $class = get_called_class() ?: get_class();
        while($row = $adb->fetch_array($result)){
            $instance = new $class();
            $instance->initialize($row);
            //crmv@29984
            if ($instance->linktype == 'DETAILVIEWWIDGET'){
                //disabilito i widget di moduli disabilitati
                global $site_URL;
                //in nome del modulo lo prendo dal linkurl..
                parse_str(parse_url($site_URL."index.php?".$instance->linkurl,PHP_URL_QUERY),$params);
                $module = $params['module'];
                if ($module != '' && !vtlib_isModuleActive($module)){
                    continue;
                }
            }
            //crmv@29984e
            //crmv@3085m
            if ($instance->linktype == 'DETAILVIEWBASIC'){
                if (strpos($instance->linkurl,'javascript:') !== false) {
                    $instance->linkurl = str_replace('javascript:','',$instance->linkurl);
                } else {
                    $instance->linkurl = "location.href='{$instance->linkurl}';";
                }
            }
            //crmv@3085me
            if($parameters) {
                $instance->linkurl = $strtemplate->merge($instance->linkurl);
                $instance->linkicon= $strtemplate->merge($instance->linkicon);
            }
            //crmv@37303
            $check = true;
            if ($check_condition && !empty($instance->condition)) {
                $cond = explode(':',$instance->condition);
                if (count($cond) == 2) {
                    require_once($cond[1]);
                    $check = $cond[0]($instance);
                    if (!$check) {
                        continue;
                    }
                }
            }
            //crmv@37303e
            if($multitype) {
                $instances[$instance->linktype][] = $instance;
            } else {
                $instances[] = $instance;
            }
        }
        return $instances;
    }

    // crmv@181170
    public function displayWidgetContent($recordId) {
        if ($this->validateDisplayWidget($recordId)) {
            $context = $this->getWidgetContext($recordId);
            echo vtlib_process_widget($this, $context);
        }
    }

    public function validateDisplayWidget($recordId) {
        if ($this->linktype == 'DETAILVIEWWIDGET') {
            if (preg_match("/^block:\/\/(.*)/", $this->linkurl, $matches)) {
                list($widgetControllerClass_tmp, $widgetControllerClassFile_tmp) = explode(':', $matches[1]);
                if (vtlib_isModuleActive($widgetControllerClass_tmp) || $widgetControllerClassFile_tmp == 'include/utils/DetailViewWidgets.php') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getWidgetContext($recordId) {
        $context = [];
        $context['ID'] = $recordId;
        $context['INSTANCE'] = $this;
        return $context;
    }
    // crmv@181170e

    /**
     * Helper function to log messages
     * @param String Message to log
     * @param Boolean true appends linebreak, false to avoid it
     * @access private
     */
    static function log($message, $delimit=true) {
        Vtecrm_Utils::Log($message, $delimit);
    }
}
