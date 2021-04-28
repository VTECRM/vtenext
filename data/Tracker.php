<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@31780 - gran parte del file

include_once('config.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');

/** This class is used to track the recently viewed items on a per user basis.
 * It is intended to be called by each module when rendering the detail form.
 */
class Tracker {

    var $log;
    var $db;
    var $table_name;

    // Tracker vte_table
    var $column_fields = Array(
        "id",
        "user_id",
        "module_name",
        "item_id",
        "item_summary"
    );

    function __construct() {
    	global $adb, $table_prefix;
    	$this->log = LoggerManager::getLogger('Tracker');
    	$this->table_name = $table_prefix."_tracker";
    	// $this->db = PearDatabase::getInstance();
    	$this->db = $adb;
    }

    /**
     * Add this new item to the vte_tracker vte_table.  If there are too many items (global config for now)
     * then remove the oldest item.  If there is more than one extra item, log an error.
     * If the new item is the same as the most recent item then do not change the list
     */
	function track_view($user_id, $current_module, $item_id, $item_summary) {
    	global $log, $adb, $table_prefix;
    	$this->delete_history($user_id, $item_id);
    	$log->info("in  track view method ".$current_module);

    	//No genius required. Just add an if case and change the query so that it puts the tracker entry whenever you touch on the DetailView of the required entity
    	//get the first name and last name from the respective modules
    	if($current_module != '') {
    		$query = "select fieldname,tablename,entityidfield from ".$table_prefix."_entityname where modulename = ?";
    		$result = $adb->pquery($query, array($current_module));
    		$fieldsname = $adb->query_result($result,0,'fieldname');
    		$tablename = $adb->query_result($result,0,'tablename');
    		$entityidfield = $adb->query_result($result,0,'entityidfield');
    		if ((strpos($fieldsname,',') !== false)) {
    			// concatenate multiple fields with an whitespace between them
    			$fieldlists = explode(',',$fieldsname);
    			$fl = array();
    			foreach($fieldlists as $w => $c) {
    				if (count($fl)) {
    					$fl[] = "' '";
    				}
    				//crmv@24944
    				$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array(getTabid($current_module),$c)),0);
    				$fl[] = $wsfield->getColumnName();
    				//crmv@24944e
    			}
    			$fieldsname = $adb->sql_concat($fl);
    		//crmv@24944
    		} else {
    			$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array(getTabid($current_module),$fieldsname)),0);
    			$fieldsname = $wsfield->getColumnName();
    		}
    		//crmv@24944e
    		$query1 = "select $fieldsname as entityname from $tablename where $entityidfield = ?";
    		$result = $adb->pquery($query1, array($item_id));
    		$item_summary = $adb->query_result_no_html($result,0,'entityname'); // crmv@33097
    		if(strlen($item_summary) > 30) {
    			$item_summary = substr($item_summary,0,30).'...';
    		}
    	}

    	//if condition added to skip vte_faq in last viewed history
    	//crmv@add seq for vte_tracker
    	$id = $adb->getUniqueId($this->table_name);
    	$query = "INSERT into $this->table_name (id,user_id, module_name, item_id, item_summary) values (?,?,?,?,?)";
    	$qparams = array($id,$user_id, $current_module, $item_id, $item_summary);
    	//crmv@add seq for vte_tracker end

    	$this->log->info("Track Item View: ".$query);
    	$this->db->pquery($query, $qparams, true);

    	$this->prune_history($user_id);
    }

    /**
     * param $user_id - The id of the user to retrive the history for
     * param $module_name - Filter the history to only return records from the specified module.  If not specified all records are returned
     * return - return the array of result set rows from the query.  All of the vte_table vte_fields are included
     */
    function get_recently_viewed($user_id, $module_name = "") {
    	if (empty($user_id)) {
    		return;
    	}
    	global $history_max_viewed, $current_user, $table_prefix;
		//crmv@36504
    	$query = "SELECT t.*,a.activitytype,c.crmid from $this->table_name t
    	inner join ".$table_prefix."_crmentity c on c.crmid=t.item_id 
    	left join {$table_prefix}_activity a on a.activityid = c.crmid
    	WHERE t.user_id=? and c.deleted=0 ORDER BY id DESC";
    	$this->log->debug("About to retrieve list: $query");
    	$result = $this->db->limitpQuery($query, 0, $history_max_viewed, array($user_id));
    	$list = Array();
    	while($row = $this->db->fetchByAssoc($result, -1, false)) {

    		// If the module was not specified or the module matches the module of the row, add the row to the list
    		if ($module_name == "" || $row['module_name'] == $module_name) {

    			//Adding Security check
    			require_once('include/utils/utils.php');
    			require_once('include/utils/UserInfoUtil.php');
    			$entity_id = $row['item_id'];
    			$module = $row['module_name'];
    			$row['module_type'] = $row['module_name']; //crmv@36504
    			if($module == "Users") {
    				if(is_admin($current_user)) {
    					$per = 'yes';
    				}
    			} else {
    				//crmv@36504
    				if ($module == 'Calendar'){
    					if ($row['activitytype'] != 'Task'){
    						$row['module_type'] = 'Events';
    					}
    				}
    				//crmv@36504 e
    				$per = isPermitted($module,'DetailView',$entity_id);
    			}
    			if($per == 'yes') {
    				$list[] = $row;
    			}
    		}
    	}
    	return $list;
    }



    /**
     * INTERNAL -- This method cleans out any entry for a record for a user.
     * It is used to remove old occurances of previously viewed items.
     */
    function delete_history($user_id, $item_id) {
        $query = "DELETE from $this->table_name WHERE user_id=? and item_id=?";
       	$this->db->pquery($query, array($user_id, $item_id), true);
    }

    /**
     * INTERNAL -- This method cleans out any entry for a record.
     */
    function delete_item_history($item_id) {
		$query = "DELETE from $this->table_name WHERE item_id=?";
		$this->db->pquery($query, array($item_id), true);
    }

    /*
     * INTERNAL -- This function will clean out old history records for this user if necessary.
     */
    function prune_history($user_id) {
        global $history_max_viewed;
        $sql_limit = $history_max_viewed * 10; // keep more data in the db

        $this->log->debug("About to verify history size: $query");

        // get the minimum good id for the user
        $query = "SELECT id from $this->table_name WHERE user_id = ? ORDER BY id DESC";
        $res = $this->db->limitpQuery($query, 0, $sql_limit, array($user_id));
        if ($res && $this->db->num_rows($res) > 0)  {
			$lastid = $this->db->query_result($res, $this->db->num_rows($res)-1, 'id');

			// delete old items
			$this->log->debug("About to delete oldest items:");
			$query = "DELETE from $this->table_name WHERE user_id = ? AND id < ?";
			$res = $this->db->pquery($query, array($user_id, $lastid));
        }

    }

}
?>
