<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// Account is used to store vte_account information.
class Accounts extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'accountid';
	var $tab_name = Array();
	var $tab_name_index = Array();

	var $entity_table;

	var $column_fields = Array();

	var $sortby_fields = Array('accountname','bill_city','website','phone','smownerid');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array();

	var $list_fields_name = Array(
			'Account Name'=>'accountname',
			'City'=>'bill_city',
			'Website'=>'website',
			'Phone'=>'phone',
			'Assigned To'=>'assigned_user_id'
			);
	var $list_link_field= 'accountname';

	var $search_fields = Array();

	var $search_fields_name = Array(
			'Account Name'=>'accountname',
			'City'=>'bill_city',
			);
	// This is the list of vte_fields that are required
	var $required_fields =  array("accountname"=>1);

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'accountname';
	var $default_sort_order = 'ASC';

	var $customFieldTable = Array(); //vtc

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'createdtime', 'modifiedtime', 'accountname');

	//Default Fields for Email Templates -- Pavani
	var $emailTemplate_defaultFields = array('accountname','account_type','industry','annualrevenue','phone','email1','rating','website','fax');

	//crmv@10759
	var $search_base_field = 'accountname';
	//crmv@10759 e
	function __construct() {
		global $log;
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_account";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_account',$table_prefix.'_accountbillads',$table_prefix.'_accountshipads',$table_prefix.'_accountscf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_account'=>'accountid',$table_prefix.'_accountbillads'=>'accountaddressid',$table_prefix.'_accountshipads'=>'accountaddressid',$table_prefix.'_accountscf'=>'accountid');
	    $this->list_fields = Array(
			'Account Name'=>Array($table_prefix.'_account'=>'accountname'),
			'City'=>Array($table_prefix.'_accountbillads'=>'bill_city'),
			'Website'=>Array($table_prefix.'_account'=>'website'),
			'Phone'=>Array($table_prefix.'_account'=> 'phone'),
			'Assigned To'=>Array($table_prefix.'_crmentity'=>'smownerid')
			);
		$this->entity_table = $table_prefix."_crmentity";
		$this->search_fields = Array(
			'Account Name'=>Array($table_prefix.'_account'=>'accountname'),
			'City'=>Array($table_prefix.'_accountbillads'=>'bill_city'),
			);
		$this->customFieldTable = Array($table_prefix.'_accountscf', 'accountid');
		$this->column_fields = getColumnFields('Accounts');
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
	}

	/** Function to handle module specific operations when saving a entity
	*/
	function save_module($module)
	{
	}

	/**
	* Function to get Account related Tickets
	* @param  integer   $id      - accountid
	* returns related Ticket record in array format
	*/
	function get_tickets($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_tickets(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		if($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'parent_id') == '0') {
			$button .= $this->get_related_buttons($this_module, $id, $related_module, $actions); // crmv@43864
		}

		// crmv@103106
		$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name, ".$table_prefix."_users.id,
			".$table_prefix."_troubletickets.title, ".$table_prefix."_troubletickets.ticketid AS crmid,
			".$table_prefix."_troubletickets.status, ".$table_prefix."_troubletickets.priority,
			".$table_prefix."_troubletickets.parent_id, ".$table_prefix."_troubletickets.ticket_no,
			".$table_prefix."_crmentity.smownerid, ".$table_prefix."_crmentity.modifiedtime
			FROM ".$table_prefix."_troubletickets
			INNER JOIN ".$table_prefix."_crmentity
				ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_troubletickets.ticketid
			INNER JOIN ".$table_prefix."_ticketcf
				ON ".$table_prefix."_ticketcf.ticketid = ".$table_prefix."_troubletickets.ticketid
			LEFT JOIN ".$table_prefix."_account
				ON ".$table_prefix."_account.accountid = ".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_contactdetails
			        ON ".$table_prefix."_contactdetails.contactid=".$table_prefix."_troubletickets.parent_id
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
			WHERE  ".$table_prefix."_crmentity.deleted = 0 ";
		//crmv@16643
		$parentIds = array((int)$id);
		
		// use a separate query and a IN () clause, since it's much faster!
		$query_contacts = "
			SELECT ".$table_prefix."_contactdetails.contactid
			FROM ".$table_prefix."_contactdetails
			INNER JOIN ".$table_prefix."_crmentity ".$table_prefix."_crmentityCont
				ON ".$table_prefix."_crmentityCont.crmid = ".$table_prefix."_contactdetails.contactid
			LEFT JOIN ".$table_prefix."_groups
				ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentityCont.smownerid
			LEFT JOIN ".$table_prefix."_users
				ON ".$table_prefix."_crmentityCont.smownerid = ".$table_prefix."_users.id
			WHERE ".$table_prefix."_crmentityCont.deleted = 0
			AND ".$table_prefix."_contactdetails.accountid = ".$id;
			$secQuery = getNonAdminAccessControlQuery('Contacts', $current_user, 'Cont');
		if(strlen($secQuery) > 1) {
			$query_contacts = appendFromClauseToQuery($query_contacts, $secQuery);
		}
		
		$res = $this->db->query($query_contacts);
		if ($res && $this->db->num_rows($res) > 0) {
			while ($row = $this->db->fetchByAssoc($res, -1, false)) {
				$parentIds[] = (int)$row['contactid'];
			}
		}
		
		$query .= " AND ".$table_prefix."_troubletickets.parent_id IN (".implode(',', $parentIds).")" ;		
		//crmv@16643e crmv@103106e

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_tickets method ...");
		return $return_value;
	}

	/**
	* Function to get Account related Products
	* @param  integer   $id      - accountid
	* returns related Products record in array format
	*/
	// crmv@43864
	function get_products($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$return_value = $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions);

		if($return_value == null) $return_value = Array();
		else $return_value = $this->add_ordered_quantity($return_value,$id,$related_module); //crmv@16644

		return $return_value;
	}
	// crmv@43864e

	/** Function to export the account records in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Accounts Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $current_user;
		global $table_prefix;
                $log->debug("Entering create_export_query(".$where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Accounts", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
	       			FROM ".$this->entity_table."
				INNER JOIN ".$table_prefix."_account
					ON ".$table_prefix."_account.accountid = ".$table_prefix."_crmentity.crmid
				LEFT JOIN ".$table_prefix."_accountbillads
					ON ".$table_prefix."_accountbillads.accountaddressid = ".$table_prefix."_account.accountid
				LEFT JOIN ".$table_prefix."_accountshipads
					ON ".$table_prefix."_accountshipads.accountaddressid = ".$table_prefix."_account.accountid
				LEFT JOIN ".$table_prefix."_accountscf
					ON ".$table_prefix."_accountscf.accountid = ".$table_prefix."_account.accountid
	                        LEFT JOIN ".$table_prefix."_groups
                        	        ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users
					ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid and ".$table_prefix."_users.status = 'Active'
				LEFT JOIN ".$table_prefix."_account ".$table_prefix."_account2
					ON ".$table_prefix."_account2.accountid = ".$table_prefix."_account.parentid
				";//vte_account2 is added to get the Member of account

		//crmv@94838
		$focus = CRMEntity::getInstance('Newsletter');
		$unsubscribe_table = $focus->email_fields['Accounts']['tablename'];
		$unsubscribe_field = $focus->email_fields['Accounts']['columnname'];
		$query .= " LEFT JOIN tbl_s_newsletter_g_unsub 
					ON tbl_s_newsletter_g_unsub.email = {$unsubscribe_table}.{$unsubscribe_field} ";
		//crmv@94838e

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e

		$query .= $this->getNonAdminAccessControlQuery('Accounts',$current_user);
		$where_auto = " ".$table_prefix."_crmentity.deleted = 0 ";

		if($where != "")
			$query .= " WHERE ($where) AND ".$where_auto;
		else
			$query .= " WHERE ".$where_auto;
		$query = $this->listQueryNonAdminChange($query, 'Accounts');
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

	// crmv@186735 - removed code

	// crmv@152701
	/** Returns a list of the associated faxes
	*/
	function get_faxes($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $currentModule;

		$log->debug("Entering get_faxes(".$id.") method ...");
		
		$this_module = $currentModule;
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$singular_modname = vtlib_toSingular($related_module);

		$button = '<input type="hidden" name="fax_directing_module"><input type="hidden" name="record">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendfax_cont\");sendfax(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."'>&nbsp;";
			}
		}

		// call standard function
		$ret = $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions);
		
		// override button
		$ret['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_faxes method ...");
		return $ret;
	}
	// crmv@152701e

	//crmv@43864
	function get_services($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		return $this->get_products($id, $cur_tab_id, $rel_tab_id, $actions);
	}
	//crmv@43864e

	function add_ordered_quantity($related_list,$id,$module)
	{
		global $adb,$mod_strings,$table_prefix;

		$fieldPos = count($related_list['header'])-1;
		$related_list['header'][] = $related_list['header'][$fieldPos];
		$related_list['header'][$fieldPos] = $mod_strings['LBL_SOLD_QUANTITY'];

		$result = $adb->query("SELECT ".$table_prefix."_inventoryproductrel.productid,SUM(".$table_prefix."_inventoryproductrel.quantity) AS quantity FROM crmv_inventorytoacc
								INNER JOIN ".$table_prefix."_crmentity crmentityServices ON crmentityServices.crmid = crmv_inventorytoacc.id
								INNER JOIN ".$table_prefix."_crmentity crmentityOrders ON crmentityOrders.crmid = crmv_inventorytoacc.sorderid
								INNER JOIN ".$table_prefix."_inventoryproductrel ON ".$table_prefix."_inventoryproductrel.id = crmv_inventorytoacc.sorderid AND ".$table_prefix."_inventoryproductrel.productid = crmv_inventorytoacc.id
								WHERE crmv_inventorytoacc.type = '$module' AND crmentityOrders.deleted = 0 AND crmentityServices.deleted = 0 AND crmv_inventorytoacc.accountid = $id
								GROUP BY productid");
		$ordered_quantity = array();
		while($row=$adb->fetchByAssoc($result)) {
			$ordered_quantity[$row['productid']] = $row['quantity'];
		}

		if (!empty($related_list['entries'])) {	//crmv@26896
			foreach($related_list['entries'] as $key => &$entry)
			{
				$entry[] = $entry[$fieldPos];
				$entry[$fieldPos] = $ordered_quantity[$key];
			}
		}	//crmv@26896
		return $related_list;
	}
	//crmv@16644e

	// Function to unlink the dependent records of the given record by id
	function unlinkDependencies($module, $id) {
		global $log,$table_prefix;
		//Deleting Account related Potentials.
		$pot_q = 'SELECT '.$table_prefix.'_crmentity.crmid FROM '.$table_prefix.'_crmentity
			INNER JOIN '.$table_prefix.'_potential ON '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_potential.potentialid
			LEFT JOIN '.$table_prefix.'_account ON '.$table_prefix.'_account.accountid='.$table_prefix.'_potential.related_to
			WHERE '.$table_prefix.'_crmentity.deleted=0 AND '.$table_prefix.'_potential.related_to=?';
		$pot_res = $this->db->pquery($pot_q, array($id));
		$pot_ids_list = array();
		for($k=0;$k < $this->db->num_rows($pot_res);$k++)
		{
			$pot_id = $this->db->query_result($pot_res,$k,"crmid");
			$pot_ids_list[] = $pot_id;
			$sql = 'UPDATE '.$table_prefix.'_crmentity SET deleted = 1 WHERE crmid = ?';
			$this->db->pquery($sql, array($pot_id));
		}
		//Backup deleted Account related Potentials.
		$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_crmentity', 'deleted', 'crmid', implode(",", $pot_ids_list));
		$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES(?,?,?,?,?,?)', $params);

		//Deleting Account related Quotes.
		$quo_q = 'SELECT '.$table_prefix.'_crmentity.crmid FROM '.$table_prefix.'_crmentity
			INNER JOIN '.$table_prefix.'_quotes ON '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_quotes.quoteid
			INNER JOIN '.$table_prefix.'_account ON '.$table_prefix.'_account.accountid='.$table_prefix.'_quotes.accountid
			WHERE '.$table_prefix.'_crmentity.deleted=0 AND '.$table_prefix.'_quotes.accountid=?';
		$quo_res = $this->db->pquery($quo_q, array($id));
		$quo_ids_list = array();
		for($k=0;$k < $this->db->num_rows($quo_res);$k++)
		{
			$quo_id = $this->db->query_result($quo_res,$k,"crmid");
			$quo_ids_list[] = $quo_id;
			$sql = 'UPDATE '.$table_prefix.'_crmentity SET deleted = 1 WHERE crmid = ?';
			$this->db->pquery($sql, array($quo_id));
		}
		//Backup deleted Account related Quotes.
		$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_crmentity', 'deleted', 'crmid', implode(",", $quo_ids_list));
		$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES(?,?,?,?,?,?)', $params);

		//Backup Contact-Account Relation
		$con_q = 'SELECT contactid FROM '.$table_prefix.'_contactdetails WHERE accountid = ?';
		$con_res = $this->db->pquery($con_q, array($id));
		if ($this->db->num_rows($con_res) > 0) {
			$con_ids_list = array();
			for($k=0;$k < $this->db->num_rows($con_res);$k++)
			{
				$con_ids_list[] = $this->db->query_result($con_res,$k,"contactid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_contactdetails', 'accountid', 'contactid', implode(",", $con_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES(?,?,?,?,?,?)', $params);
		}
		//Deleting Contact-Account Relation.
		$con_q = 'UPDATE '.$table_prefix.'_contactdetails SET accountid = 0 WHERE accountid = ?';
		$this->db->pquery($con_q, array($id));

		//Backup Trouble Tickets-Account Relation
		$tkt_q = 'SELECT ticketid FROM '.$table_prefix.'_troubletickets WHERE parent_id = ?';
		$tkt_res = $this->db->pquery($tkt_q, array($id));
		if ($this->db->num_rows($tkt_res) > 0) {
			$tkt_ids_list = array();
			for($k=0;$k < $this->db->num_rows($tkt_res);$k++)
			{
				$tkt_ids_list[] = $this->db->query_result($tkt_res,$k,"ticketid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_troubletickets', 'parent_id', 'ticketid', implode(",", $tkt_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES(?,?,?,?,?,?)', $params);
		}

		//Deleting Trouble Tickets-Account Relation.
		$tt_q = 'UPDATE '.$table_prefix.'_troubletickets SET parent_id = 0 WHERE parent_id = ?';
		$this->db->pquery($tt_q, array($id));

		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log, $table_prefix;
		if(empty($return_module) || empty($return_id)) return;
		//crmv@15157
		if($return_module == 'Campaigns') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaignaccountrel WHERE accountid=? AND campaignid=?';
			$this->db->pquery($sql, array($id, $return_id));
		}
		//crmv@15157 end
		elseif($return_module == 'Products') {
			$sql = 'DELETE FROM '.$table_prefix.'_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}
	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log,$table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Contacts"=>$table_prefix."_contactdetails","Potentials"=>$table_prefix."_potential","Quotes"=>$table_prefix."_quotes",
					"SalesOrder"=>$table_prefix."_salesorder","Invoice"=>$table_prefix."_invoice","Activities"=>$table_prefix."_seactivityrel",
					"Documents"=>$table_prefix."_senotesrel","Attachments"=>$table_prefix."_seattachmentsrel","HelpDesk"=>$table_prefix."_troubletickets",
					"Products"=>$table_prefix."_seproductsrel");

		$tbl_field_arr = Array($table_prefix."_contactdetails"=>"contactid",$table_prefix."_potential"=>"potentialid",$table_prefix."_quotes"=>"quoteid",
					$table_prefix."_salesorder"=>"salesorderid",$table_prefix."_invoice"=>"invoiceid",$table_prefix."_seactivityrel"=>"activityid",
					$table_prefix."_senotesrel"=>"notesid",$table_prefix."_seattachmentsrel"=>"attachmentsid",$table_prefix."_troubletickets"=>"ticketid",
					$table_prefix."_seproductsrel"=>"productid");

		$entity_tbl_field_arr = Array($table_prefix."_contactdetails"=>"accountid",$table_prefix."_potential"=>"related_to",$table_prefix."_quotes"=>"accountid",
					$table_prefix."_salesorder"=>"accountid",$table_prefix."_invoice"=>"accountid",$table_prefix."_seactivityrel"=>"crmid",
					$table_prefix."_senotesrel"=>"crmid",$table_prefix."_seattachmentsrel"=>"crmid",$table_prefix."_troubletickets"=>"parent_id",
					$table_prefix."_seproductsrel"=>"crmid");

		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		//crmv@15526
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		//crmv@15526 end
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables =  array (
			"Contacts" => array($table_prefix."_contactdetails"=>array("accountid","contactid"),$table_prefix."_account"=>"accountid"),
			"Potentials" => array($table_prefix."_potential"=>array("related_to","potentialid"),$table_prefix."_account"=>"accountid"),
			"Quotes" => array($table_prefix."_quotes"=>array("accountid","quoteid"),$table_prefix."_account"=>"accountid"),
			"SalesOrder" => array($table_prefix."_salesorder"=>array("accountid","salesorderid"),$table_prefix."_account"=>"accountid"),
			"Invoice" => array($table_prefix."_invoice"=>array("accountid","invoiceid"),$table_prefix."_account"=>"accountid"),
			"Calendar" => array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_account"=>"accountid"),
			"HelpDesk" => array($table_prefix."_troubletickets"=>array("parent_id","ticketid"),$table_prefix."_account"=>"accountid"),
			"Products" => array($table_prefix."_seproductsrel"=>array("crmid","productid"),$table_prefix."_account"=>"accountid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_account"=>"accountid"),
			"Campaigns" => array($table_prefix."_campaignaccountrel"=>array("accountid","campaignid"),$table_prefix."_account"=>"accountid"),
		);
		return $rel_tables[$secmodule];
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_account","accountid");
		$query .= " left join ".$table_prefix."_accountbillads on ".$table_prefix."_account.accountid=".$table_prefix."_accountbillads.accountaddressid
			left join ".$table_prefix."_accountshipads on ".$table_prefix."_account.accountid=".$table_prefix."_accountshipads.accountaddressid
			left join ".$table_prefix."_accountscf on ".$table_prefix."_account.accountid = ".$table_prefix."_accountscf.accountid
			left join ".$table_prefix."_account ".$table_prefix."_accountAccounts on ".$table_prefix."_accountAccounts.accountid = ".$table_prefix."_account.parentid
			left join ".$table_prefix."_groups ".$table_prefix."_groupsAccounts on ".$table_prefix."_groupsAccounts.groupid = ".$table_prefix."_crmentityAccounts.smownerid
			left join ".$table_prefix."_users ".$table_prefix."_usersAccounts on ".$table_prefix."_usersAccounts.id = ".$table_prefix."_crmentityAccounts.smownerid ";
		return $query;
	}
	//crmv@38798e

	//crmv@22700
	function get_campaigns_newsletter($id, $cur_tab_id, $rel_tab_id, $actions=false)
	{
		global $log,$currentModule,$current_user,$table_prefix;//crmv@203484 removed global singlepane
        //crmv@203484
        $VTEP = VTEProperties::getInstance();
        $singlepane_view = $VTEP->getProperty('layout.singlepane_view');
        //crmv@203484e
        $log->debug("Entering get_visitreport(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
            $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		// crmv@120052
		// remove the sortering from the request, since I'm, loading another related
		$oldReq = $_REQUEST;
		unset($_REQUEST['order_by'], $_REQUEST['sorder']);
		// crmv@120052e

		global $adb, $onlybutton;
		$onlyquery_bck = $onlyquery; $onlyquery = true;
		$onlybutton_bck = $onlybutton; $onlybutton = false;
		$targetsModule = Vtecrm_Module::getInstance('Targets');
		CRMEntity::get_related_list($id, $cur_tab_id, $targetsModule->id);
		$result = $adb->query(VteSession::get('targets_listquery'));
		$onlyquery = $onlyquery_bck;
		$onlybutton = $onlybutton_bck;
		//TODO: trovare anche i Target inclusi in questi Target
		$campaigns = array();
		if ($result && $adb->num_rows($result)>0) {
			// crmv@120052
			$currentModuleBackup = $currentModule;
			$currentModule = $targetsModule->name; //crmv@46974
			// crmv@120052e
			while($row=$adb->fetchByAssoc($result)) {
				$onlyquery_bck = $onlyquery; $onlyquery = true;
				$onlybutton_bck = $onlybutton; $onlybutton = false;
				CRMEntity::get_related_list($row['crmid'], $targetsModule->id, 26);
				$result1 = $adb->query(VteSession::get('campaigns_listquery'));
				$onlyquery = $onlyquery_bck;
				$onlybutton = $onlybutton_bck;
				if ($result1 && $adb->num_rows($result1)>0) {
					while($row1=$adb->fetchByAssoc($result1)) {
						$campaigns[$row1['crmid']] = '';
					}
				}
			}
			$currentModule = $currentModuleBackup; //crmv@46974 crmv@120052
		}

		// restore the request
		$_REQUEST = $oldReq; // crmv@120052

		$campaigns = array_keys($campaigns);
		if (!empty($campaigns)) {
			$query = " SELECT
				CASE WHEN (".$table_prefix."_users.user_name is not null) THEN ".$table_prefix."_users.user_name ELSE ".$table_prefix."_groups.groupname END AS user_name,
				".$table_prefix."_campaign.campaignid,
				".$table_prefix."_campaign.campaignname,
				".$table_prefix."_campaign.campaigntype,
				  ".$table_prefix."_campaign.campaignstatus,
				  ".$table_prefix."_campaign.expectedrevenue,
				  ".$table_prefix."_campaign.closingdate,
				  ".$table_prefix."_crmentity.crmid,
				  ".$table_prefix."_crmentity.smownerid,
				  ".$table_prefix."_crmentity.modifiedtime
				FROM ".$table_prefix."_campaign
				  INNER JOIN ".$table_prefix."_campaignscf
				    ON ".$table_prefix."_campaignscf.campaignid = ".$table_prefix."_campaign.campaignid
				  INNER JOIN ".$table_prefix."_crmentity
				    ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_campaign.campaignid
				  LEFT JOIN ".$table_prefix."_groups
				    ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				  LEFT JOIN ".$table_prefix."_users
				    ON ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
				WHERE ".$table_prefix."_campaign.campaignid in (".implode(',',$campaigns).")
				    AND ".$table_prefix."_crmentity.deleted = 0";
			$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);
		}
		if($return_value == null) $return_value = Array();
		else {
			unset($return_value['header'][0]);
			if(is_array($return_value['entries'])){
				foreach ($return_value['entries'] as $id => $info) {
					unset($return_value['entries'][$id][0]);
				}
			}
		}
		$log->debug("Exiting get_faxes method ...");
		return $return_value;
	}
	//crmv@22700e
	
	// crmv@193226
	public function updateContactsAddress() {
		global $adb, $table_prefix;
		$query = 
			"UPDATE {$table_prefix}_contactaddress SET 
			mailingcity=?,mailingstreet=?,mailingcountry=?,mailingzip=?,mailingpobox=?,mailingstate=?,
			othercountry=?,othercity=?,otherstate=?,otherzip=?,otherstreet=?,otherpobox=? 
			WHERE contactaddressid IN (
				SELECT contactid FROM {$table_prefix}_contactdetails WHERE accountid=?
			)" ;
		$params = array(
			$this->column_fields['bill_city'], $this->column_fields['bill_street'], $this->column_fields['bill_country'], 
			$this->column_fields['bill_code'], $this->column_fields['bill_pobox'], $this->column_fields['bill_state'], $this->column_fields['ship_country'], 
			$this->column_fields['ship_city'], $this->column_fields['ship_state'], $this->column_fields['ship_code'],
			$this->column_fields['ship_street'], $this->column_fields['ship_pobox'], $this->id);
		$adb->pquery($query, $params);
	}
	// crmv@193226e
	
	/* crmv@181281 moved code in CRMEntity and ExportUtils */
	
}